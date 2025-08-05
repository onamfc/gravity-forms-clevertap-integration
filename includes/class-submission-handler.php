<?php
/**
 * Form Submission Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTGF_Submission_Handler {
    
    public function __construct() {
        add_action('gform_after_submission', array($this, 'handle_submission'), 10, 2);
    }
    
    public function handle_submission($entry, $form) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE form_id = %d AND active = 1", $form['id']));
        
        if (!$config) {
            return; // No configuration or not active
        }
        
        $email = rgar($entry, $config->email_field);
        
        if (empty($email)) {
            $this->log_debug('No email found in field ' . $config->email_field . ' for form ' . $form['id']);
            return;
        }
        
        if (!is_email($email)) {
            $this->log_debug('Invalid email format: ' . $email . ' for form ' . $form['id']);
            return;
        }
        
        $this->send_to_clevertap($email, $config->tag, $form['id']);
    }
    
    private function send_to_clevertap($email, $tag, $form_id) {
        $this->log_debug('Sending to CleverTap - Email: ' . $email . ', Tag: ' . $tag . ', Form ID: ' . $form_id);
        
        // Get the event name and profile key from the config
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        $config = $wpdb->get_row($wpdb->prepare("SELECT event_name, profile_key FROM $table_name WHERE form_id = %d", $form_id));
        $event_name = $config && !empty($config->event_name) ? $config->event_name : 'Newsletter Signup';
        $profile_key = $config && !empty($config->profile_key) ? $config->profile_key : 'Form Signups';
        
        $api = new CTGF_CleverTap_API();
        
        $user_data = array(
            'email' => $email,
            'identity' => $email // Use email as identity
        );

        $success = $api->update_customer_attributes($email, $tag, $profile_key);
        
        if ($success) {
            $this->log_debug('Successfully updated customer attributes');
        } else {
            $this->log_debug('Failed to update customer attributes');
        }
        
        // Send event with delay (using wp_schedule_single_event for delay)
        $event_data = array(
            'tag' => $tag,
            'form_id' => $form_id,
            'event_name' => $event_name,
            'source' => 'gravity_forms'
        );
        
        wp_schedule_single_event(time() + 240, 'ctgf_send_delayed_event', array($user_data, $event_data)); // 4 minutes delay
    }
    
    private function log_debug($message) {
        if (get_option('ctgf_enable_logging')) {
            error_log('CleverTap GF Integration: ' . $message);
            GFCommon::log_debug('CleverTap GF Integration: ' . $message);
        }
    }
}

// Handle delayed event sending
add_action('ctgf_send_delayed_event', 'ctgf_handle_delayed_event', 10, 2);

function ctgf_handle_delayed_event($user_data, $event_data) {
    $api = new CTGF_CleverTap_API();
    $event_name = isset($event_data['event_name']) ? $event_data['event_name'] : 'Newsletter Signup';
    $success = $api->send_event($user_data['email'], $event_name, $event_data);

    if (get_option('ctgf_enable_logging')) {
        $message = $success ? "Delayed event '$event_name' sent successfully" : "Failed to send delayed event '$event_name'";
        error_log('CleverTap GF Integration: ' . $message);
        GFCommon::log_debug('CleverTap GF Integration: ' . $message);
    }
}