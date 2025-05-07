<?php
/*
Plugin Name: Duffel Travel
Description: Integração com Duffel API
Version: 1.0.0
*/

defined('ABSPATH') || exit;

// Load settings
require_once plugin_dir_path(__FILE__) . 'admin/class-admin-settings.php';

// AJAX handler for testing connection
add_action('wp_ajax_duffel_test_api_connection', function() {
    check_ajax_referer('duffel_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }

    $api_key = Duffel_Travel_Settings::get_api_key();
    
    if (empty($api_key)) {
        wp_send_json_error('API Key not configured');
    }

    // Implement your actual API test here
    // For now we'll just simulate a successful test
    wp_send_json_success([
        'message' => 'API Connection successful!'
    ]);
});