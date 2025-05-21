<?php
/*
Plugin Name: Natural Language AI Chatbot (NL-Chatbot)
Description: Adds a floating AI-powered help chatbot to your site. Uses OpenAI and vector search over FAQs.
Version: 1.0.0
Author: Superteams.ai
*/

defined('ABSPATH') or die('No script kiddies please!');

// Register FAQ custom post type
add_action('init', function() {
    register_post_type('nlcb_faq', [
        'labels' => [
            'name' => 'Chatbot FAQs',
            'singular_name' => 'Chatbot FAQ',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-editor-help',
    ]);
});

// Register settings
add_action('admin_init', function() {
    register_setting('nlcb_settings', 'nlcb_openai_api_key');
    register_setting('nlcb_settings', 'nlcb_vector_api_key');
    register_setting('nlcb_settings', 'nlcb_vector_endpoint');
    // Appearance settings - must be registered for saving!
    register_setting('nlcb_settings', 'nlcb_appearance_title');
    register_setting('nlcb_settings', 'nlcb_appearance_bubble_icon');
    register_setting('nlcb_settings', 'nlcb_appearance_titlebar_color');
    register_setting('nlcb_settings', 'nlcb_appearance_bot_bubble_color');
    register_setting('nlcb_settings', 'nlcb_appearance_user_bubble_color');
    // New: Chatbot name and company name
    register_setting('nlcb_settings', 'nlcb_appearance_chatbot_name');
    register_setting('nlcb_settings', 'nlcb_appearance_company_name');
});

// Add settings page
add_action('admin_menu', function() {
    add_options_page('NL Chatbot Settings', 'NL Chatbot', 'manage_options', 'nlcb-settings', 'nlcb_settings_page');
});

// Enqueue admin script
add_action('admin_enqueue_scripts', function($hook_suffix) {
    // Check if we are on the plugin settings page
    if ($hook_suffix === 'settings_page_nlcb-settings') {
        wp_enqueue_script('nlcb-admin-script', plugin_dir_url(__FILE__) . 'admin/nlcb-admin.js', ['jquery'], null, true);
        wp_localize_script('nlcb-admin-script', 'nlcbAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            '_ajax_nonce' => wp_create_nonce('nlcb_reindex_nonce')
        ]);
    }
});

function nlcb_settings_page() {
    ?>
    <div class="wrap">
        <h1>NL-Chatbot (Natural Language AI Support Chatbot)</h1>
        <p style="max-width:800px;font-size:1.05em;line-height:1.6;margin-bottom:22px;">
            NL-Chatbot (Natural Language AI Chatbot) is a plugin that uses LLMs and Vector Search to enable natural language support chatbot on your Wordpress website. Currently the plugin supports OpenAI's LLM models, and Qdrant to power the Vector Search. Support for 100+ LLMs and a wide range of Vector Search technologies is coming soon. Developed by <a href="https://superteams.ai" target="_blank">Superteams.ai</a>.
        </p>
        <form method="post" action="options.php">
            <?php
            settings_fields('nlcb_settings');
            do_settings_sections('nlcb_settings');
            ?>
            <fieldset style="border:1px solid #ccd0d4; border-radius:6px; padding:18px 20px 10px 20px; margin-bottom:24px; background:#f9f9f9; max-width:700px;">
                <legend style="font-weight:bold; padding:0 8px;">OpenAI & Qdrant Settings</legend>
                <p style="color:#555; font-size:13px; margin-bottom:16px;">Configure your API keys and endpoints for OpenAI (for LLM) and Qdrant (for vector search). Required for the chatbot to function.</p>
                <table class="form-table">
                    <tr>
                        <th><label for="nlcb_openai_api_key">OpenAI API Key</label></th>
                        <td>
                            <input type="text" name="nlcb_openai_api_key" value="<?php echo esc_attr(get_option('nlcb_openai_api_key')); ?>" class="regular-text code" />
                            <br><small><a href="https://platform.openai.com/api-keys" target="_blank">Get your API key from OpenAI</a></small>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_vector_api_key">Qdrant API Key</label></th>
                        <td>
                            <input type="text" name="nlcb_vector_api_key" value="<?php echo esc_attr(get_option('nlcb_vector_api_key')); ?>" class="regular-text code" />
                            <br><small><a href="https://cloud.qdrant.io/signup" target="_blank">Get your Qdrant API key</a></small>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_vector_endpoint">Qdrant Cloud Endpoint</label></th>
                        <td>
                            <input type="text" name="nlcb_vector_endpoint" value="<?php echo esc_attr(get_option('nlcb_vector_endpoint')); ?>" class="regular-text code" placeholder="https://your-instance.cloud.qdrant.io" />
                            <br><small><a href="https://cloud.qdrant.io/signup" target="_blank">Get your Qdrant Cloud endpoint</a></small>
                        </td>
                    </tr>
                </table>
            </fieldset>

            <fieldset style="border:1px solid #ccd0d4; border-radius:6px; padding:18px 20px 10px 20px; margin-bottom:24px; background:#f9f9f9; max-width:700px;">
                <legend style="font-weight:bold; padding:0 8px;">Appearance</legend>
                <p style="margin:0 0 10px 2px;color:#555;max-width:700px;font-size:13px;">
                    Customize the look, branding, and personality of your chatbot. These settings control the chatbot’s name, company, title, icon, and colors as seen by your site visitors.
                </p>
                <table class="form-table">
                    <tr>
                        <th><label for="nlcb_appearance_chatbot_name">Chatbot Name</label></th>
                        <td><input type="text" name="nlcb_appearance_chatbot_name" value="<?php echo esc_attr(get_option('nlcb_appearance_chatbot_name', 'Alexa')); ?>" class="regular-text" placeholder="e.g. Alexa, Siri, etc." /></td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_appearance_company_name">Company Name</label></th>
                        <td><input type="text" name="nlcb_appearance_company_name" value="<?php echo esc_attr(get_option('nlcb_appearance_company_name', 'Your Company')); ?>" class="regular-text" placeholder="e.g. Acme Corp" /></td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_appearance_title">Chatbot Title</label></th>
                        <td><input type="text" name="nlcb_appearance_title" value="<?php echo esc_attr(get_option('nlcb_appearance_title', 'AI Chatbot')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_appearance_bubble_icon">Bubble Icon (emoji or SVG)</label></th>
                        <td><input type="text" name="nlcb_appearance_bubble_icon" value="<?php echo esc_attr(get_option('nlcb_appearance_bubble_icon', '✨')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_appearance_titlebar_color">Title Bar Color</label></th>
                        <td><input type="color" name="nlcb_appearance_titlebar_color" value="<?php echo esc_attr(get_option('nlcb_appearance_titlebar_color', '#0073aa')); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_appearance_bot_bubble_color">Bot Bubble Color</label></th>
                        <td><input type="color" name="nlcb_appearance_bot_bubble_color" value="<?php echo esc_attr(get_option('nlcb_appearance_bot_bubble_color', '#e3f1fa')); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="nlcb_appearance_user_bubble_color">User Bubble Color</label></th>
                        <td><input type="color" name="nlcb_appearance_user_bubble_color" value="<?php echo esc_attr(get_option('nlcb_appearance_user_bubble_color', '#d1e7dd')); ?>" /></td>
                    </tr>
                </table>
            </fieldset>
            <?php submit_button('Save Settings'); ?>
        </form>
        <script>
        function nlcbReindexFaqs() {
            var btn = document.getElementById('nlcb-reindex-faqs');
            var status = document.getElementById('nlcb-reindex-status');
            btn.disabled = true;
            status.textContent = '⏳ Reindexing...';
            jQuery.post(ajaxurl, {
                action: 'nlcb_reindex_faqs',
                _ajax_nonce: nlcbAdmin._ajax_nonce
            }, function(resp) {
                btn.disabled = false;
                if (resp.success) {
                    status.textContent = '✅ ' + (resp.data || 'Reindex complete!');
                } else {
                    status.textContent = '❌ ' + (resp.data || 'Reindex failed.');
                }
            });
        }
        </script>

        <hr />
        <h2>Manage FAQs</h2>
        <p><a href="<?php echo admin_url('edit.php?post_type=nlcb_faq'); ?>" class="button">View/Edit FAQs</a></p>
        <hr />
        <h2>Reindex FAQs</h2>
        <p>Generate embeddings for all published FAQs and sync them to your vector search collection.</p>
        <button type="button" id="nlcb-reindex-button" class="button button-secondary">Reindex FAQs</button>
        <div id="nlcb-reindex-log" style="margin-top: 15px; background: #f9f9f9; padding: 10px; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; font-family: monospace;"></div>
    </div>
    <?php
}

// Helper function to get vector collection name
function nlcb_get_vector_collection_name() {
    // Use home_url to make it site-specific and URL-safe
    $site_url = home_url();
    $parsed = parse_url($site_url);
    $host = $parsed['host'] ?? 'default';

    // Sanitize the hostname
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '_', $host));
    return "faqs_" . $slug;
}

// AJAX handler for reindexing FAQs
add_action('wp_ajax_nlcb_reindex_faqs', function() {
    check_ajax_referer('nlcb_reindex_nonce', 'nonce');

    $openai_key = get_option('nlcb_openai_api_key');
    $vector_endpoint = rtrim(get_option('nlcb_vector_endpoint'), '/');
    $vector_api_key = get_option('nlcb_vector_api_key');
    $collection_name = nlcb_get_vector_collection_name();

    if (empty($openai_key) || empty($vector_endpoint)) {
        wp_send_json_error('OpenAI API Key and Vector Search Endpoint must be set in settings.');
    }

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($vector_api_key)) {
        $headers['api-key'] = $vector_api_key;
    }

    // 1. Fetch all published FAQs
    $faqs = get_posts([
        'post_type' => 'nlcb_faq',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);

    if (empty($faqs)) {
        wp_send_json_success('No FAQs found to reindex.');
    }

    $points_array = [];
    $indexed_count = 0;
    $errors = [];

    // 2. Process each FAQ: get embedding and prepare for upsert
    foreach ($faqs as $faq) {
        $text = $faq->post_title . "\n\n" . wp_strip_all_tags($faq->post_content);

        // Get OpenAI Embedding
        $embedding_response = wp_remote_post('https://api.openai.com/v1/embeddings', [
            'headers' => [
                'Authorization' => 'Bearer ' . $openai_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'input' => $text,
                'model' => 'text-embedding-3-small'
            ]),
            'timeout' => 60, // Increase timeout for API call
        ]);

        $embedding_body = wp_remote_retrieve_body($embedding_response);
        $embedding_data = json_decode($embedding_body, true);

        if (is_wp_error($embedding_response) || wp_remote_retrieve_response_code($embedding_response) !== 200 || !isset($embedding_data['data'][0]['embedding'])) {
            $errors[] = 'Failed to get embedding for FAQ ID ' . $faq->ID . ': ' . ($embedding_response->get_error_message() ?? $embedding_body);
            continue;
        }

        $embedding = $embedding_data['data'][0]['embedding'];

        $points_array[] = [
            'id' => (int) $faq->ID,
            'vector' => $embedding,
            'payload' => [
                'question' => $faq->post_title,
                'answer' => apply_filters('the_content', $faq->post_content) // Store processed answer
            ]
        ];
        $indexed_count++;

        // Optional: Upsert in batches if you have many FAQs
        // if (count($points_array) >= 100) {
        //     // Perform upsert for current batch
        //     // $upsert_response = wp_remote_request("$vector_endpoint/collections/$collection_name/points", [...]);
        //     // Handle response and clear $points_array
        // }
    }

    // 3. Perform bulk upsert to Vector Search (Qdrant)
    if (!empty($points_array)) {
         // First, ensure the collection exists (optional but good practice)
        $create_collection_url = "$vector_endpoint/collections/$collection_name";
        $create_collection_response = wp_remote_request($create_collection_url, [
            'method'  => 'PUT',
            'headers' => $headers,
            'body'    => json_encode([
                'vectors' => [
                    'size' => 1536, // OpenAI text-embedding-3-small size
                    'distance' => 'Cosine'
                ]
            ]),
             'timeout' => 60,
        ]);

        if (is_wp_error($create_collection_response) && wp_remote_retrieve_response_code($create_collection_response) !== 400) { // Allow 400 if collection already exists
             $errors[] = 'Failed to create or check vector collection: ' . $create_collection_response->get_error_message();
        }

        // Now, upsert the points
        $upsert_url = "$vector_endpoint/collections/$collection_name/points?wait=true"; // wait=true for synchronous operation
        $upsert_response = wp_remote_request($upsert_url, [
            'method'  => 'PUT',
            'headers' => $headers,
            'body'    => json_encode(['points' => $points_array]),
            'timeout' => 300, // Increase timeout for bulk upsert
        ]);

        $upsert_body = wp_remote_retrieve_body($upsert_response);

        if (is_wp_error($upsert_response) || wp_remote_retrieve_response_code($upsert_response) !== 200) {
            $errors[] = 'Failed to upsert points to vector search: ' . ($upsert_response->get_error_message() ?? $upsert_body);
        }
    }

    // 4. Report results
    if (empty($errors)) {
        wp_send_json_success("Successfully indexed $indexed_count FAQs.");
    } else {
        wp_send_json_error('Reindexing completed with errors: ' . implode("\n", $errors));
    }

});

// Enqueue Vue app for frontend
add_action('wp_enqueue_scripts', function() {
    // Enqueue Tailwind CSS from CDN
    // wp_enqueue_style('nlcb-chatbot-tailwind', 'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css');
    // Use custom CSS
    wp_enqueue_style('nlcb-chatbot-custom-style', plugin_dir_url(__FILE__) . 'assets/nlcb-chatbot-custom.css');
    wp_enqueue_script('nlcb-chatbot-vue', 'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js', [], null, true);
    wp_enqueue_script('nlcb-chatbot-app', plugin_dir_url(__FILE__) . 'assets/nlcb-chatbot-app.js', ['nlcb-chatbot-vue'], null, true);
    wp_localize_script('nlcb-chatbot-app', 'nlcbChatbot', [
        'restUrl' => esc_url_raw(rest_url('nlcb/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
        'appearance' => [
            'title' => get_option('nlcb_appearance_title', 'AI Chatbot'),
            'bubble_icon' => get_option('nlcb_appearance_bubble_icon', '✨'),
            'titlebar_color' => get_option('nlcb_appearance_titlebar_color', '#0073aa'),
            'bot_bubble_color' => get_option('nlcb_appearance_bot_bubble_color', '#e3f1fa'),
            'user_bubble_color' => get_option('nlcb_appearance_user_bubble_color', '#d1e7dd'),
            'chatbot_name' => get_option('nlcb_appearance_chatbot_name', 'Alexa'),
            'company_name' => get_option('nlcb_appearance_company_name', 'Your Company'),
        ]
    ]);
});

// Add chatbot container to footer
add_action('wp_footer', function() {
    echo '<div id="nlcb-chatbot-root"></div>';
});

// REST API: Get FAQs
add_action('rest_api_init', function() {
    register_rest_route('nlcb/v1', '/faqs', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function() {
            $faqs = get_posts([
                'post_type' => 'nlcb_faq',
                'post_status' => 'publish',
                'numberposts' => -1
            ]);
            return array_map(function($post) {
                return [
                    'id' => $post->ID,
                    'question' => $post->post_title,
                    'answer' => apply_filters('the_content', $post->post_content)
                ];
            }, $faqs);
        }
    ]);
    // Chat endpoint (stub)
    register_rest_route('nlcb/v1', '/chat', [
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => function($request) {
            $params = $request->get_json_params();
            $question = $params['question'] ?? '';
            // 1. Get settings
            $openai_key = get_option('nlcb_openai_api_key');
            $vector_endpoint = rtrim(get_option('nlcb_vector_endpoint'), '/');
            $vector_api_key = get_option('nlcb_vector_api_key');
            $collection_name = nlcb_get_vector_collection_name();

            if (empty($openai_key) || empty($vector_endpoint)) {
                return [
                    'answer' => 'Chatbot is not configured. Please set OpenAI and Vector Search settings.'
                ];
            }

            // 2. Get embedding for the question
            $embedding_response = wp_remote_post('https://api.openai.com/v1/embeddings', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openai_key,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'input' => $question,
                    'model' => 'text-embedding-3-small'
                ]),
                'timeout' => 60,
            ]);
            $embedding_body = wp_remote_retrieve_body($embedding_response);
            $embedding_data = json_decode($embedding_body, true);
            if (is_wp_error($embedding_response) || empty($embedding_data['data'][0]['embedding'])) {
                return [
                    'answer' => 'Sorry, there was a problem connecting to OpenAI for embeddings.'
                ];
            }
            $query_embedding = $embedding_data['data'][0]['embedding'];

            // 3. Query Qdrant for similar FAQs
            $headers = ['Content-Type' => 'application/json'];
            if (!empty($vector_api_key)) {
                $headers['api-key'] = $vector_api_key;
            }
            $search_url = "$vector_endpoint/collections/$collection_name/points/search";
            $search_body = [
                'vector' => $query_embedding,
                'limit' => 3, // Top 3 for richer context
                'with_payload' => true
            ];
            $search_response = wp_remote_post($search_url, [
                'headers' => $headers,
                'body'    => json_encode($search_body),
                'timeout' => 30,
            ]);
            $search_result = json_decode(wp_remote_retrieve_body($search_response), true);
            if (is_wp_error($search_response) || empty($search_result['result'][0]['payload']['answer'])) {
                return [
                    'answer' => 'Sorry, I could not find a relevant answer.'
                ];
            }
            // Prepare FAQ context for LLM
            $faq_context = "";
            foreach ($search_result['result'] as $i => $hit) {
                if (!empty($hit['payload']['question']) && !empty($hit['payload']['answer'])) {
                    $faq_context .= "FAQ #" . ($i+1) . ":\nQ: " . wp_strip_all_tags($hit['payload']['question']) . "\nA: " . wp_strip_all_tags($hit['payload']['answer']) . "\n\n";
                }
            }
            $system_prompt = "You are a helpful support assistant. Use the following FAQs to answer the user's question as helpfully as possible. If the answer is not in the FAQs, and related to the company, then respond to the user saying
            that you do not have answer to the question, and that the user should get in touch through the contact or connect with the support team. If the question is not related to the company, then respond to the user saying that you can only answer questions related to the company. When you respond to the user, refer to the company as 'we' and the user as 'you'. Keep the responses concise. End the respond with a leading question based on the conversation history.";
            // Prepare messages array for OpenAI
            $messages = [
                ['role' => 'system', 'content' => $system_prompt]
            ];
            // Add conversation history if provided
            $history = $params['history'] ?? [];
            if (is_array($history)) {
                foreach ($history as $msg) {
                    if (!empty($msg['role']) && !empty($msg['content'])) {
                        // Only allow 'user' or 'assistant' roles
                        if ($msg['role'] === 'user' || $msg['role'] === 'assistant') {
                            $messages[] = [
                                'role' => $msg['role'],
                                'content' => $msg['content']
                            ];
                        }
                    }
                }
            }
            // Add the latest user prompt (with FAQ context)
            $messages[] = [
                'role' => 'user',
                'content' => "FAQs:\n" . $faq_context . "\nUser Question: " . $question
            ];
            // Call OpenAI Chat Completion
            $chat_response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $openai_key,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'max_tokens' => 512,
                    'temperature' => 0.3
                ]),
                'timeout' => 60,
            ]);
            $chat_body = wp_remote_retrieve_body($chat_response);
            $chat_data = json_decode($chat_body, true);
            if (is_wp_error($chat_response) || empty($chat_data['choices'][0]['message']['content'])) {
                return [
                    'answer' => 'Sorry, there was a problem generating an answer.'
                ];
            }
            $llm_answer = $chat_data['choices'][0]['message']['content'];
            // Clean up the LLM answer
            $clean_answer = wp_strip_all_tags($llm_answer);
            $clean_answer = html_entity_decode($clean_answer, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $clean_answer = str_replace("\xC2\xA0", ' ', $clean_answer);
            $clean_answer = str_replace("\u00A0", ' ', $clean_answer);
            $clean_answer = str_replace("&nbsp;", ' ', $clean_answer);
            return [
                'answer' => $clean_answer,
                'faq_context' => $faq_context,
                'score' => $search_result['result'][0]['score'] ?? null
            ];
        }
    ]);
}); 