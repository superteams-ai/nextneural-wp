<?php
/*
Plugin Name: Natural Language Search (AI)
Description: Replaces WP search with natural language search. Uses vector search and OpenAI. 
Version: 1.0
Author: Superteams.ai
*/

defined('ABSPATH') or die('No script kiddies please!');

require_once plugin_dir_path(__FILE__) . 'includes/class-nl-search.php';

function nl_search_init() {
    $plugin = new NL_Search();
    $plugin->run();
}
add_action('plugins_loaded', 'nl_search_init');

// Register admin menu for plugin settings
add_action('admin_menu', function () {
    add_options_page('NL Search Settings', 'NL Search', 'manage_options', 'nl-search', 'nl_search_settings_page');
});

// Register plugin settings
add_action('admin_init', function () {
    register_setting('nl_search_settings', 'nl_search_openai_api_key');
    register_setting('nl_search_settings', 'nl_search_qdrant_url');
    register_setting('nl_search_settings', 'nl_search_qdrant_api_key');
    register_setting('nl_search_settings', 'nl_search_use_keyword_fallback');
});



// Test connection to OpenAI and Qdrant Cloud
add_action('wp_ajax_nl_search_test_connection', function () {
    $service = $_POST['service'] ?? '';

    if ($service === 'openai') {
        $key = get_option('nl_search_openai_api_key');
        $response = wp_remote_post('https://api.openai.com/v1/models', [
            'headers' => ['Authorization' => 'Bearer ' . $key]
        ]);
        if (is_wp_error($response)) {
            wp_send_json_error('OpenAI test failed: ' . $response->get_error_message());
        } else {
            wp_send_json_success('✅ OpenAI connection successful!');
        }
    }

    if ($service === 'qdrant') {
        $url = rtrim(get_option('nl_search_qdrant_url'), '/') . '/collections';
        $headers = ['Content-Type' => 'application/json'];
        $key = get_option('nl_search_qdrant_api_key');
        if (!empty($key)) {
            $headers['api-key'] = $key;
        }
        $response = wp_remote_get($url, ['headers' => $headers]);
        if (is_wp_error($response)) {
            wp_send_json_error('Qdrant test failed: ' . $response->get_error_message());
        } else {
            wp_send_json_success('✅ Qdrant connection successful!');
        }
    }

    wp_send_json_error('Unknown service.');
});

// Helper function to create collection name (for unique collection names)
function nl_search_get_collection_name() {
    // Use home_url to make it tenant-specific and URL-safe
    $site_url = home_url();
    $parsed = parse_url($site_url);
    $host = $parsed['host'] ?? 'default';

    // Sanitize the hostname
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '_', $host));
    return "posts_" . $slug;
}

// Create Qdrant collection if missing
function nl_search_create_qdrant_collection_if_missing() {
    $qdrant_url = rtrim(get_option('nl_search_qdrant_url'), '/');
    $qdrant_key = get_option('nl_search_qdrant_api_key');
    $collection = nl_search_get_collection_name();
    $headers = ['Content-Type' => 'application/json'];
    if (!empty($qdrant_key)) $headers['api-key'] = $qdrant_key;

    // 1. Check if the collection already exists
    $check = wp_remote_get("$qdrant_url/collections/$collection", ['headers' => $headers]);
    if (!is_wp_error($check) && wp_remote_retrieve_response_code($check) === 200) {
        return true; // already exists
    }

    // 2. Create the collection
    $create = wp_remote_request("$qdrant_url/collections/$collection", [
        'method'  => 'PUT',
        'headers' => $headers,
        'body'    => json_encode([
            'vectors' => [
                'size' => 1536,
                'distance' => 'Cosine'
            ]
        ])
    ]);

    return !is_wp_error($create) && wp_remote_retrieve_response_code($create) === 200;
}

// Helper: Fetch all point IDs from Qdrant
function nl_search_get_qdrant_point_ids($qdrant_url, $collection, $headers) {
    $ids = [];
    $offset = 0;
    $limit = 1000;
    do {
        $response = wp_remote_post("$qdrant_url/collections/$collection/points/scroll", [
            'headers' => $headers,
            'body'    => json_encode([
                'limit' => $limit,
                'offset' => $offset,
                'with_payload' => false,
                'with_vector' => false
            ])
        ]);
        if (is_wp_error($response)) break;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['result']['points'])) break;
        foreach ($body['result']['points'] as $pt) {
            if (isset($pt['id'])) $ids[] = $pt['id'];
        }
        $count = count($body['result']['points']);
        $offset += $count;
    } while ($count === $limit);
    return $ids;
}

// Helper: Delete points by IDs from Qdrant
function nl_search_delete_qdrant_points($qdrant_url, $collection, $headers, $ids_to_delete) {
    if (empty($ids_to_delete)) return true;
    $response = wp_remote_post("$qdrant_url/collections/$collection/points/delete", [
        'headers' => $headers,
        'body'    => json_encode(['points' => array_values($ids_to_delete)])
    ]);
    if (is_wp_error($response)) {
        error_log('Qdrant delete error: ' . $response->get_error_message());
        return false;
    }
    error_log('Qdrant delete response: ' . wp_remote_retrieve_body($response));
    return true;
}

// Reindex posts in the collection
add_action('wp_ajax_nl_search_reindex_posts', function () {
    $openai_key = get_option('nl_search_openai_api_key');
    $qdrant_url = rtrim(get_option('nl_search_qdrant_url'), '/');
    $qdrant_key = get_option('nl_search_qdrant_api_key');
    $collection = nl_search_get_collection_name();

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($qdrant_key)) $headers['api-key'] = $qdrant_key;

    // 1. Get all WP post IDs
    $wp_post_ids = array_map('intval', get_posts(['numberposts' => -1, 'post_type' => 'post', 'fields' => 'ids']));

    // 2. Get all Qdrant point IDs
    $qdrant_point_ids = nl_search_get_qdrant_point_ids($qdrant_url, $collection, $headers);

    // 3. Find IDs in Qdrant but not in WP
    $ids_to_delete = array_diff($qdrant_point_ids, $wp_post_ids);
    if (!empty($ids_to_delete)) {
        error_log('Deleting Qdrant points not in WP: ' . json_encode($ids_to_delete));
        nl_search_delete_qdrant_points($qdrant_url, $collection, $headers, $ids_to_delete);
    }

    // 4. Proceed with batch upsert for current posts
    $points_array = [];
    foreach (get_posts(['numberposts' => -1, 'post_type' => 'post']) as $post) {
        $text = $post->post_title . "\n\n" . wp_strip_all_tags($post->post_content);
        $embedding_response = wp_remote_post('https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $openai_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'input' => $text,
                'model' => 'text-embedding-3-small'
            ])
        ]);
        $embedding_data = json_decode(wp_remote_retrieve_body($embedding_response), true);
        if (!isset($embedding_data['data'][0]['embedding'])) {
            error_log('OpenAI embedding error for post ID ' . $post->ID . ': ' . wp_remote_retrieve_body($embedding_response));
            continue;
        }
        $embedding = $embedding_data['data'][0]['embedding'];
        $points_array[] = [
            'id' => (int) $post->ID,
            'vector' => $embedding,
            'payload' => ['title' => $post->post_title]
        ];
    }
    error_log('Qdrant batch points array: ' . json_encode($points_array));
    $qdrant_response = wp_remote_request("$qdrant_url/collections/$collection/points", [
        'method'  => 'PUT',
        'headers' => $headers,
        'body'    => json_encode(['points' => $points_array])
    ]);
    if (!is_wp_error($qdrant_response)) {
        wp_send_json_success("✅ Reindexed " . count($points_array) . " posts. Deleted: " . count($ids_to_delete));
    } else {
        error_log('Qdrant API error (batch): ' . wp_remote_retrieve_body($qdrant_response));
        wp_send_json_error('❌ Failed to upsert points in Qdrant. See debug.log for details.');
    }
});

// AJAX: Get all post IDs
add_action('wp_ajax_nl_search_get_post_ids', function () {
    $posts = get_posts(['numberposts' => -1, 'post_type' => 'post', 'fields' => 'ids']);
    wp_send_json_success($posts);
});

// Settings page UI
function nl_search_settings_page() {
    $use_fallback = get_option('nl_search_use_keyword_fallback') ? 'checked' : '';
    $collection_name = nl_search_get_collection_name();
    // Enqueue jQuery if not already
    wp_enqueue_script('jquery');
    ?>
    <style>
        .nl-search-tabs { display: flex; border-bottom: 1px solid #ccd0d4; margin-bottom: 20px; }
        .nl-search-tab {
            padding: 10px 24px;
            cursor: pointer;
            border: 1px solid #ccd0d4;
            border-bottom: none;
            background: #f1f1f1;
            margin-right: 4px;
            border-radius: 6px 6px 0 0;
            font-weight: 500;
        }
        .nl-search-tab.active {
            background: #fff;
            border-bottom: 1px solid #fff;
        }
        .nl-search-tab-content { display: none; }
        .nl-search-tab-content.active { display: block; }
        .nl-search-fieldset {
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            padding: 18px 20px 10px 20px;
            margin-bottom: 24px;
            background: #f9f9f9;
        }
        .nl-search-fieldset legend {
            font-weight: bold;
            padding: 0 8px;
        }
        #nl-search-log {
            background: #222; color: #eee; font-family: monospace; font-size: 14px;
            padding: 12px; border-radius: 6px; margin-top: 18px; min-height: 120px; max-height: 300px; overflow-y: auto;
        }
    </style>
    <div class="wrap">
        <h1>🔍 Natural Language Search – Settings</h1>
        <div style="max-width: 700px; margin-bottom: 32px; margin-top: 8px; font-size: 15px; color: #333; line-height: 1.6;">
            NL-Search (Natural Language Search) is a plugin that uses LLMs and Vector Search to enable natural language search on your Wordpress website. Currently the plugin supports OpenAI's LLM models, and Qdrant to power the Vector Search. Support for 100+ LLMs and a wide range of Vector Search technologies is coming soon. Developed by <a href="https://www.superteams.ai">Superteams.ai</a>.
        </div>
        <div class="nl-search-tabs">
            <div class="nl-search-tab active" id="nl-search-tab-general" onclick="nlSearchShowTab('general')">General Settings</div>
            <div class="nl-search-tab" id="nl-search-tab-reindex" onclick="nlSearchShowTab('reindex')">Reindex Content</div>
        </div>
        <div class="nl-search-tab-content active" id="nl-search-tab-content-general">
            <form method="post" action="options.php">
                <?php
                settings_fields('nl_search_settings');
                do_settings_sections('nl_search_settings');
                ?>
                <table class="form-table" style="max-width: 700px;">
                    <tr><td colspan="2">
                        <fieldset class="nl-search-fieldset">
                            <legend>OpenAI Settings</legend>
                            <table style="width:100%">
                                <tr>
                                    <th style="width:200px;"><label for="nl_search_openai_api_key">OpenAI API Key</label></th>
                                    <td>
                                        <input type="text" id="nl_search_openai_api_key" name="nl_search_openai_api_key"
                                               value="<?php echo esc_attr(get_option('nl_search_openai_api_key')); ?>"
                                               class="regular-text code" style="width: 100%;" />
                                        <p class="description">Get from <a href=\"https://platform.openai.com/account/api-keys\" target=\"_blank\">OpenAI dashboard</a>.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-top:10px;">
                                        <button type="button" class="button" onclick="testConnection('openai')">Test OpenAI</button>
                                        <span id="nl-search-loading-openai" style="display:none;margin-left:10px;">⏳ Testing...</span>
                                        <span id="nl-search-result-openai" style="margin-left:10px;"></span>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>
                    </td></tr>
                    <tr><td colspan="2">
                        <fieldset class="nl-search-fieldset">
                            <legend>Qdrant Settings</legend>
                            <table style="width:100%">
                                <tr>
                                    <th style="width:200px;"><label for="nl_search_qdrant_api_key">Qdrant API Key</label></th>
                                    <td>
                                        <input type="text" id="nl_search_qdrant_api_key" name="nl_search_qdrant_api_key"
                                               value="<?php echo esc_attr(get_option('nl_search_qdrant_api_key')); ?>"
                                               class="regular-text code" style="width: 100%;" />
                                        <p class="description">Leave blank if your Qdrant instance allows public access.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th style="width:200px;"><label for="nl_search_qdrant_url">Qdrant Endpoint</label></th>
                                    <td>
                                        <input type="text" id="nl_search_qdrant_url" name="nl_search_qdrant_url"
                                               value="<?php echo esc_attr(get_option('nl_search_qdrant_url')); ?>"
                                               class="regular-text code" style="width: 100%;"
                                               placeholder="https://your-instance.cloud.qdrant.io" />
                                        <p class="description">No trailing slash.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-top:10px;">
                                        <button type="button" class="button" onclick="testConnection('qdrant')">Test Qdrant</button>
                                        <span id="nl-search-loading-qdrant" style="display:none;margin-left:10px;">⏳ Testing...</span>
                                        <span id="nl-search-result-qdrant" style="margin-left:10px;"></span>
                                    </td>
                                </tr>
                            </table>
                        </fieldset>
                    </td></tr>
                    <tr>
                        <th><label for="nl_search_use_keyword_fallback">Fallback to keyword search?</label></th>
                        <td>
                            <input type="checkbox" name="nl_search_use_keyword_fallback" <?php echo $use_fallback; ?> />
                            <label for="nl_search_use_keyword_fallback">Enable fallback if semantic search fails</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <div class="nl-search-tab-content" id="nl-search-tab-content-reindex">
            <h2>🔄 Reindex Content</h2>
            <p>Generate embeddings for all published posts and sync them to your Qdrant collection.</p>
            <button type="button" class="button button-primary" onclick="reindexPosts()">Reindex Posts</button>
            <div id="nl-search-log"></div>
        </div>
        <div id="nl-search-message" style="margin-top: 20px; font-weight: bold;"></div>
        <script type="text/javascript">
            // Ensure ajaxurl is defined
            if (typeof ajaxurl === 'undefined') {
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            }
            function nlSearchShowTab(tab) {
                // Tab buttons
                document.getElementById('nl-search-tab-general').classList.remove('active');
                document.getElementById('nl-search-tab-reindex').classList.remove('active');
                // Tab contents
                document.getElementById('nl-search-tab-content-general').classList.remove('active');
                document.getElementById('nl-search-tab-content-reindex').classList.remove('active');
                if (tab === 'general') {
                    document.getElementById('nl-search-tab-general').classList.add('active');
                    document.getElementById('nl-search-tab-content-general').classList.add('active');
                } else {
                    document.getElementById('nl-search-tab-reindex').classList.add('active');
                    document.getElementById('nl-search-tab-content-reindex').classList.add('active');
                }
            }
            function testConnection(service) {
                // Clear previous messages
                if (service === 'openai') {
                    document.getElementById('nl-search-result-openai').innerText = '';
                    document.getElementById('nl-search-loading-openai').style.display = 'inline';
                    document.getElementById('nl-search-loading-qdrant').style.display = 'none';
                } else if (service === 'qdrant') {
                    document.getElementById('nl-search-result-qdrant').innerText = '';
                    document.getElementById('nl-search-loading-qdrant').style.display = 'inline';
                    document.getElementById('nl-search-loading-openai').style.display = 'none';
                }
                document.getElementById('nl-search-message').innerText = '';
                const data = {
                    action: 'nl_search_test_connection',
                    service
                };
                jQuery.post(ajaxurl, data)
                    .done(function (response) {
                        if (service === 'openai') {
                            document.getElementById('nl-search-result-openai').innerText = response.data || response.message;
                        } else if (service === 'qdrant') {
                            document.getElementById('nl-search-result-qdrant').innerText = response.data || response.message;
                        } else {
                            document.getElementById('nl-search-message').innerText = response.data || response.message;
                        }
                    })
                    .fail(function (xhr, status, error) {
                        if (service === 'openai') {
                            document.getElementById('nl-search-result-openai').innerText = '❌ AJAX error: ' + (xhr.responseText || status);
                        } else if (service === 'qdrant') {
                            document.getElementById('nl-search-result-qdrant').innerText = '❌ AJAX error: ' + (xhr.responseText || status);
                        } else {
                            document.getElementById('nl-search-message').innerText = '❌ AJAX error: ' + (xhr.responseText || status);
                        }
                    })
                    .always(function () {
                        if (service === 'openai') {
                            document.getElementById('nl-search-loading-openai').style.display = 'none';
                        } else if (service === 'qdrant') {
                            document.getElementById('nl-search-loading-qdrant').style.display = 'none';
                        }
                    });
            }
            function reindexPosts() {
                const confirmed = confirm("This will reindex all posts for natural language search. Continue?");
                if (!confirmed) return;
                document.getElementById('nl-search-log').innerText = 'Fetching posts...\n';
                // Get post count first
                jQuery.post(ajaxurl, { action: 'nl_search_get_post_ids' }, function(response) {
                    if (!response.success || !Array.isArray(response.data) || response.data.length === 0) {
                        document.getElementById('nl-search-log').innerText += 'No posts found.\n';
                        return;
                    }
                    const postCount = response.data.length;
                    document.getElementById('nl-search-log').innerText += `Indexing ${postCount} posts...\n`;
                    // Call batch reindex endpoint
                    jQuery.post(ajaxurl, { action: 'nl_search_reindex_posts' }, function(res) {
                        document.getElementById('nl-search-log').innerText += (res.data || res.message) + '\n';
                    }).fail(function(xhr, status, error) {
                        document.getElementById('nl-search-log').innerText += `❌ AJAX error: ` + (xhr.responseText || status) + '\n';
                    });
                });
            }
        </script>
    </div>
    <?php
}