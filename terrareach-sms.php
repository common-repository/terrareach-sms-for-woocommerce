<?php
/*
Plugin Name: TerraReach SMS for Woocommerce
Plugin URI: https://terrareach.com
Description: Send SMS notifications for WooCommerce orders using TerraReach SMS gateway API
Version: 1.0.1
Author: Origyn
Author URI: http://origyn.company
License: GPLv2 or later
Text Domain: terraReach-sms-for-woocommerce
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include other files
require_once plugin_dir_path(__FILE__) . 'admin/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/api/send-sms.php';

// Add TerraReach SMS tab to WooCommerce settings
add_filter('woocommerce_settings_tabs_array', 'terrareach_add_settings_tab', 50);
add_action('woocommerce_settings_tabs_terrareach_sms', 'terrareach_settings_tab');
add_action('woocommerce_update_options_terrareach_sms', 'terrareach_update_settings');

function terrareach_sms_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=terrareach_sms">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'terrareach_sms_settings_link');

function terrareach_sms_meta_links($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $links[] = '<a href="https://www.terrareach.com/sms-for-woocommerce" target="_blank">Plugin Website</a>';
        $links[] = '<a href="https://docs.terrareach.com" target="_blank">Documentation</a>';
    }
    return $links;
}

add_filter('plugin_row_meta', 'terrareach_sms_meta_links', 10, 2);