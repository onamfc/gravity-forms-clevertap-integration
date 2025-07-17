<?php
/**
 * CleverTap API Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTGF_CleverTap_API {
    
    private $account_id;
    private $passcode;
    private $api_url;
    
    public function __construct() {
        $this->account_id = get_option('ctgf_account_id', '');
        $this->passcode = get_option('ctgf_passcode', '');
        $this->api_url = 'https://api.clevertap.com/1/';
    }
    
    /**
     * Update customer attributes
     */
    public function update_customer_attributes($user_data, $update_data) {
        if (empty($this->account_id) || empty($this->passcode)) {
            error_log('CleverTap API credentials not configured');
            return false;
        }
        
        $endpoint = $this->api_url . 'profiles.json';
        
        $payload = array(
            'd' => array(
                array_merge($user_data, $update_data)
            )
        );
        
        $response = $this->make_request($endpoint, $payload);
        
        if ($response && isset($response['status']) && $response['status'] === 'success') {
            return true;
        }
        
        error_log('CleverTap update customer attributes failed: ' . print_r($response, true));
        return false;
    }
    
    /**
     * Send event to CleverTap
     */
    public function send_event($user_data, $event_name, $event_data = array()) {
        if (empty($this->account_id) || empty($this->passcode)) {
            error_log('CleverTap API credentials not configured');
            return false;
        }
        
        $endpoint = $this->api_url . 'events.json';
        
        $event_payload = array_merge(
            $user_data,
            array(
                'evtName' => $event_name,
                'evtData' => $event_data
            )
        );
        
        $payload = array(
            'd' => array($event_payload)
        );
        
        $response = $this->make_request($endpoint, $payload);
        
        if ($response && isset($response['status']) && $response['status'] === 'success') {
            return true;
        }
        
        error_log('CleverTap send event failed: ' . print_r($response, true));
        return false;
    }
    
    /**
     * Make API request to CleverTap
     */
    private function make_request($endpoint, $payload) {
        $headers = array(
            'X-CleverTap-Account-Id' => $this->account_id,
            'X-CleverTap-Passcode' => $this->passcode,
            'Content-Type' => 'application/json'
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => $headers,
            'body' => json_encode($payload),
            'timeout' => 30
        );
        
        $response = wp_remote_post($endpoint, $args);
        
        if (is_wp_error($response)) {
            error_log('CleverTap API request error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        return $decoded;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $test_data = array(
            'email' => 'test@example.com',
            'identity' => 'test_user_' . time()
        );
        
        return $this->update_customer_attributes($test_data, array('test_field' => 'test_value'));
    }
}