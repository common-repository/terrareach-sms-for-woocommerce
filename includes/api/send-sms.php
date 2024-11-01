<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function terrareach_order_status_changed($order_id, $old_status, $new_status) {
    // Load order
    $order = wc_get_order($order_id);

    // Get customer phone number
    $billing_phone = $order->get_billing_phone();

    if (!$billing_phone) {
        return;
    }

    // Load settings
    $prefix = 'terrareach_sms_';
    $api_key = get_option($prefix . 'api_key');
    $sender_id = get_option($prefix . 'sender_id');
    $default_message = get_option($prefix . 'default_sms_template');
    $status_option = get_option($prefix . 'send_sms_' . $new_status);
    $status_message = get_option($prefix . $new_status . '_sms_template');

    if ('yes' === $status_option) {
        $message = !empty($status_message) ? $status_message : $default_message;

        // Prepare message with shortcodes
        $message = str_replace('{{first_name}}', $order->get_billing_first_name(), $message);
        $message = str_replace('{{last_name}}', $order->get_billing_last_name(), $message);
        $message = str_replace('{{shop_name}}', get_bloginfo('name'), $message);
        $message = str_replace('{{order_id}}', $order_id, $message);
        $message = str_replace('{{order_amount}}', $order->get_total(), $message);
        $message = str_replace('{{order_status}}', wc_get_order_status_name($new_status), $message);
        $message = str_replace('{{billing_city}}', $order->get_billing_city(), $message);
        $message = str_replace('{{customer_phone}}', $billing_phone, $message);

        // Send SMS
        terrareach_send_sms($billing_phone, $message, $api_key, $sender_id);
    }
}

function terrareach_send_sms($phone_number, $message, $api_key, $sender_id) {
    $url = 'https://api.terrareach.com/api/v1/sms';

    $args = array(
        'body' => wp_json_encode(array(
            'apiKey' => $api_key,
            'phoneNumber' => $phone_number,
            'message' => $message,
            'mask' => $sender_id
        )),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'timeout' => 30
    );

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        error_log('TerraReach SMS error: ' . $response->get_error_message());
    } else {
        $response_body = wp_remote_retrieve_body($response);
        error_log('TerraReach SMS response: ' . $response_body);
    }
}

function terrareach_customer_note_added($order_note_id, $order) {
    // Get the order note
    $order_note = wc_get_order_note($order_note_id);

    // Check if it's a customer-facing note
    if (!$order_note || !$order_note->customer_note) {
        return;
    }

    // Get customer phone number
    $billing_phone = $order->get_billing_phone();

    if (!$billing_phone) {
        return;
    }

    // Load settings
    $prefix = 'terrareach_sms_';
    $api_key = get_option($prefix . 'api_key');
    $sender_id = get_option($prefix . 'sender_id');
    $enable_notes_sms = get_option($prefix . 'enable_notes_sms', 'no');
    $note_sms_template = get_option($prefix . 'note_sms_template', 'You have a new note: ');

    if ('yes' === $enable_notes_sms) {
        // Prepare message with shortcodes
        $message = $note_sms_template . $order_note->content;

        // Send SMS
        terrareach_send_sms($billing_phone, $message, $api_key, $sender_id);
    }
}

add_action('woocommerce_order_note_added', 'terrareach_customer_note_added', 10, 2);

function terrareach_admin_new_order($order_id) {
    // Load order
    $order = wc_get_order($order_id);

    // Load settings
    $prefix = 'terrareach_sms_';
    $api_key = get_option($prefix . 'api_key');
    $sender_id = get_option($prefix . 'sender_id');
    $enable_admin_sms = get_option($prefix . 'enable_admin_sms', 'no');
    $admin_sms_recipients = get_option($prefix . 'admin_sms_recipients', '');
    $admin_sms_template = get_option($prefix . 'admin_sms_template', 'You have a new customer order for {{shop_name}}. Order #{{order_id}}, Total Value: {{order_amount}}');

    if ('yes' === $enable_admin_sms && !empty($admin_sms_recipients)) {
        $recipients = explode(',', $admin_sms_recipients);

        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);

            if (!empty($recipient)) {
                // Prepare message with shortcodes
                $message = str_replace('{{shop_name}}', get_bloginfo('name'), $admin_sms_template);
                $message = str_replace('{{order_id}}', $order_id, $message);
                $message = str_replace('{{order_amount}}', $order->get_total(), $message);

                // Send SMS
                terrareach_send_sms($recipient, $message, $api_key, $sender_id);
            }
        }
    }
}

// Hook into order status change
add_action('woocommerce_order_status_changed', 'terrareach_order_status_changed', 10, 3);

// Hook into customer note addition
add_action('woocommerce_order_note_added', 'terrareach_customer_note_added', 10, 2);

// Hook into new order creation
add_action('woocommerce_thankyou', 'terrareach_admin_new_order', 10, 1);
