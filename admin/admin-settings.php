<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function terrareach_enqueue_admin_scripts($hook) {
    if ($hook !== 'woocommerce_page_wc-settings') {
        return;
    }

    wp_enqueue_script('terrareach-admin-js', plugin_dir_url(__FILE__) . 'js/admin-scripts.js', array('jquery'), '1.0.0', true);

    wp_enqueue_style('terrareach-admin-css', plugin_dir_url(__FILE__) . 'css/admin-styles.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'terrareach_enqueue_admin_scripts');

function terrareach_add_settings_tab($settings_tabs) {
    $settings_tabs['terrareach_sms'] = __('TerraReach SMS', 'terrareach-sms-for-woocommerce');
    return $settings_tabs;
}

function terrareach_settings_tab() {
    woocommerce_admin_fields(terrareach_get_settings());
}

function terrareach_update_settings() {
    woocommerce_update_options(terrareach_get_settings());
}

function terrareach_get_settings() {
    $prefix = 'terrareach_sms_';
    $fields = array();

    // TerraReach Settings
    $fields[] = array('type' => 'sectionend', 'id' => $prefix . 'apisettings');
    $fields[] = array(
        'title' => __('TerraReach Settings', 'terrareach-sms-for-woocommerce'),
        'type' => 'title',
        'desc' => 'Provide following details from your TerraReach account. <a href="https://app.terrareach.com/settings/api-keys" target="_blank">Click here</a> to go to API KEY section.',
        'id' => $prefix . 'terrareach_settings'
    );

    $fields[] = array(
        'title' => __('API Key', 'terrareach-sms-for-woocommerce'),
        'id' => $prefix . 'api_key',
        'desc_tip' => __('API key available in your TerraReach account.', 'terrareach-sms-for-woocommerce'),
        'type' => 'text',
        'css' => 'min-width:300px;',
        'custom_attributes' => array(
            'required' => 'required',
            'pattern' => '^tr_prd_[a-z0-9]{32}$'
    )
    );
    $fields[] = array(
        'title' => __('Sender ID', 'terrareach-sms-for-woocommerce'),
        'id' => $prefix . 'sender_id',
        'desc_tip' => __('Enter your TerraReach Sender ID.', 'terrareach-sms-for-woocommerce'),
        'type' => 'text',
        'css' => 'min-width:300px;',
        'custom_attributes' => array(
            'required' => 'required',
            'maxlength' => '11'
        )
    );

    $fields[] = array('type' => 'sectionend', 'id' => $prefix . 'apisettings');

    // Available Shortcodes
    $avbShortcodes = array(
        '{{first_name}}' => "First name of the customer.",
        '{{last_name}}' => "Last name of the customer.",
        '{{shop_name}}' => 'Your shop name (' . get_bloginfo('name') . ').',
        '{{order_id}}' => 'The order ID.',
        '{{order_amount}}' => "Current order amount.",
        '{{order_status}}' => 'Current order status (Pending, Failed, Processing, etc...).',
        '{{billing_city}}' => 'The city in the customer billing address (If available).',
        '{{customer_phone}}' => 'Customer mobile number (If given).'
    );

    $shortcode_desc = '';
    foreach ($avbShortcodes as $handle => $description) {
        $shortcode_desc .= '<b>' . $handle . '</b> - ' . $description . '<br>';
    }

    $fields[] = array(
        'title' => __('Available Shortcodes', 'terrareach-sms-for-woocommerce'),
        'type' => 'title',
        'desc' => 'These shortcodes can be used in your message body contents. <br><br>' . $shortcode_desc,
        'id' => $prefix . 'shortcodes'
    );

    // Notifications for Customer
    $fields[] = array(
        'title' => 'Notifications for Customer',
        'type' => 'title',
        'desc' => 'Send SMS to customer\'s mobile phone. Will be sent to the phone number which customer is providing while checkout process.',
        'id' => $prefix . 'customersettings'
    );

    $fields[] = array(
        'title' => 'Default Message',
        'id' => $prefix . 'default_sms_template',
        'desc_tip' => __('This message will be sent by default if there are no any text in the following event message fields.', 'terrareach-sms-for-woocommerce'),
        'default' => __('Your order #{{order_id}} is now {{order_status}}. Thank you for shopping at {{shop_name}}.', 'terrareach-sms-for-woocommerce'),
        'type' => 'textarea',
        'css' => 'min-width:300px;min-height:80px;'
    );

    $all_statuses = wc_get_order_statuses();
    foreach ($all_statuses as $key => $val) {
        $key = str_replace("wc-", "", $key);
        $fields[] = array(
            'title' => $val,
            'desc' => 'Enable "' . $val . '" status alert',
            'id' => $prefix . 'send_sms_' . $key,
            'default' => 'yes',
            'type' => 'checkbox',
        );
        $fields[] = array(
            'id' => $prefix . $key . '_sms_template',
            'type' => 'textarea',
            'placeholder' => 'SMS Content for the ' . $val . ' event',
            'css' => 'min-width:300px;margin-top:-25px;min-height:80px;'
        );
    }

    // Customer Note Notifications
    $fields[] = array('type' => 'sectionend', 'id' => $prefix . 'notesettings');
    $fields[] = array(
        'title' => 'Customer Note Notifications',
        'type' => 'title',
        'desc' => 'Enable SMS notifications for new customer notes.',
        'id' => $prefix . 'notesettings'
    );

    $fields[] = array(
        'title' => 'Send Notes Alerts',
        'id' => $prefix . 'enable_notes_sms',
        'default' => 'no',
        'type' => 'checkbox',
        'desc' => 'Enable SMS alerts for new customer notes'
    );

    $fields[] = array(
        'title' => 'Note Message Prefix',
        'id' => $prefix . 'note_sms_template',
        'desc_tip' => 'Text you provide here will be prepended to your customer note.',
        'css' => 'min-width:500px;',
        'default' => 'You have a new note: ',
        'type' => 'textarea'
    );

    // Notification for Admin
    $fields[] = array('type' => 'sectionend', 'id' => $prefix . 'adminsettings');
    $fields[] = array(
        'title' => 'Notification for Admin',
        'type' => 'title',
        'desc' => 'Enable admin notifications for new customer orders.',
        'id' => $prefix . 'adminsettings'
    );

    $fields[] = array(
        'title' => 'Receive Admin Notifications',
        'id' => $prefix . 'enable_admin_sms',
        'desc' => 'Enable admin notifications for new customer orders.',
        'default' => 'no',
        'type' => 'checkbox'
    );
    $fields[] = array(
        'title' => 'Admin Mobile Number',
        'id' => $prefix . 'admin_sms_recipients',
        'desc' => 'Enter admin mobile numbers. You can use multiple numbers by separating with a comma.<br> Example: 0777123456, 0776252120.',
        'default' => '',
        'type' => 'text'
    );
    $fields[] = array(
        'title' => 'Message',
        'id' => $prefix . 'admin_sms_template',
        'desc_tip' => 'Customization tags for new order SMS: {{shop_name}}, {{order_id}}, {{order_amount}}.',
        'css' => 'min-width:300px;',
        'default' => 'You have a new customer order for {{shop_name}}. Order #{{order_id}}, Total Value: {{order_amount}}',
        'type' => 'textarea'
    );

    $fields[] = array('type' => 'sectionend', 'id' => $prefix . 'customersettings');

    return apply_filters('terrareach_sms_settings', $fields);
}

function terrareach_validate_api_key($value) {
    if (!preg_match('/^tr_prd_[a-z0-9]{32}$/', $value)) {
        WC_Admin_Settings::add_error(__('Invalid API Key format.', 'terrareach-sms-for-woocommerce'));
        return '';
    }
    return $value;
}

add_filter('woocommerce_admin_settings_sanitize_option_terrareach_sms_api_key', 'terrareach_validate_api_key', 10, 1);

// Add admin notices
function terrareach_admin_notices() {
    settings_errors('terrareach_sms_messages');
}
add_action('admin_notices', 'terrareach_admin_notices');
