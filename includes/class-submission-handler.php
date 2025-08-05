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
        
        $this->send_to_clevertap($email, $config, $entry, $form['id']);
    }
    
    private function send_to_clevertap($email, $config, $entry, $form_id) {
        $this->log_debug('Sending to CleverTap - Email: ' . $email . ', Form ID: ' . $form_id);
        
        $event_name = !empty($config->event_name) ? $config->event_name : 'Newsletter Signup';
        
        // Build properties from form data
        $properties = array();
        
        // Add the tag if specified (legacy support)
        if (!empty($config->tag)) {
            $properties['Form Signups'] = array(
                '$add' => array($config->tag)
            );
        }
        
        // Process property mappings
        if (!empty($config->property_mappings)) {
            $property_mappings = json_decode($config->property_mappings, true);
            if (is_array($property_mappings)) {
                foreach ($property_mappings as $mapping) {
                    if (!empty($mapping['property_name']) && !empty($mapping['form_field'])) {
                        $field_value = rgar($entry, $mapping['form_field']);
                        if (!empty($field_value)) {
                            $properties[$mapping['property_name']] = $field_value;
                            $this->log_debug('Mapped property: ' . $mapping['property_name'] . ' = ' . $field_value);
                        }
                    }
                }
            }
        }
        
        $api = new CTGF_CleverTap_API();
        
        $user_data = array(
            'email' => $email,
            'identity' => $email // Use email as identity
        );

        // Send properties to CleverTap if we have any
        $success = false;
        if (!empty($properties)) {
            $success = $api->update_customer_attributes($email, $properties);
            
            if ($success) {
                $this->log_debug('Successfully updated customer attributes with ' . count($properties) . ' properties');
            } else {
                $this->log_debug('Failed to update customer attributes');
            }
        } else {
            $this->log_debug('No properties to send to CleverTap');
        }
        
        // Build custom event data from mappings
        $event_data = array(
            'form_id' => $form_id
        );
        
        // Add legacy tag if present
        if (!empty($config->tag)) {
            $event_data['tag'] = $config->tag;
        }
        
        // Process event mappings
        if (!empty($config->event_mappings)) {
            $event_mappings = json_decode($config->event_mappings, true);
            if (is_array($event_mappings)) {
                foreach ($event_mappings as $mapping) {
                    if (!empty($mapping['event_key']) && !empty($mapping['form_field'])) {
                        $field_value = rgar($entry, $mapping['form_field']);
                        if (!empty($field_value)) {
                            $event_data[$mapping['event_key']] = $field_value;
                            $this->log_debug('Mapped event data: ' . $mapping['event_key'] . ' = ' . $field_value);
                        }
                    }
                }
            }
        }
        
        // Send event with delay (using wp_schedule_single_event for delay)
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