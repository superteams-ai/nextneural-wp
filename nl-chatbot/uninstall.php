<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Delete plugin options
$option_names = [
    'nlcb_openai_api_key',
    'nlcb_qdrant_api_key',
    'nlcb_qdrant_url',
    'nlcb_title',
    'nlcb_bubble_icon',
    'nlcb_titlebar_color',
    'nlcb_bot_bubble_color',
    'nlcb_user_bubble_color',
    'nlcb_chatbot_name',
    'nlcb_company_name',
];
foreach ($option_names as $option) {
    delete_option($option);
    delete_site_option($option); // Multisite
}

// Delete all Chatbot FAQs (custom post type)
if (post_type_exists('nlcb_faq')) {
    $faqs = get_posts([
        'post_type' => 'nlcb_faq',
        'numberposts' => -1,
        'post_status' => 'any',
    ]);
    foreach ($faqs as $faq) {
        wp_delete_post($faq->ID, true);
    }
}
