<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// List all option names used by the plugin
$options = [
    'nl_search_openai_api_key',
    'nl_search_qdrant_url',
    'nl_search_qdrant_api_key',
    'nl_search_use_keyword_fallback',
    'nl_search_index_posts',
    'nl_search_index_pages',
    'nl_search_include_excerpt',
];

foreach ($options as $option) {
    delete_option($option);
} 