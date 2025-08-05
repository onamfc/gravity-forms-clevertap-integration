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
        $this->passcode   = get_option('ctgf_passcode', '');
        
        // Set API URL based on region or use default
        $api_region = get_option('ctgf_api_region', 'us1');
        $this->api_url = 'https://' . $api_region . '.api.clevertap.com/1/';
    }

    /**
     * Update customer attributes with flexible properties (main method)
     */
    public function update_customer_profile($email, $properties = array()) {
        if (empty($this->account_id) || empty($this->passcode)) {
            error_log('CleverTap API credentials not configured');
            return false;
        }

        if (empty($properties)) {
            error_log('No properties provided for CleverTap profile update');
            return false;
        }

        $endpoint = $this->api_url . 'upload';

        // Build profile data from properties array
        $profileData = array();
        foreach ($properties as $key => $value) {
            if (!empty($key) && !empty($value)) {
                $profileData[$key] = $value;
            }
        }

        if (empty($profileData)) {
            error_log('No valid properties to send to CleverTap');
            return false;
        }

        $payload = array(
            'd' => array(
                array(
                    'identity'    => $email,
                    'type'        => 'profile',
                    'profileData' => $profileData
                )
            )
        );

        $response = $this->make_request($endpoint, $payload);

        if ($response && isset($response['status']) && $response['status'] === 'success') {
            return true;
        }

        error_log('CleverTap update customer attributes failed: ' . print_r($response, true));
        return false;
    }

    public function update_customer_attributes($email, $properties = array()) {
        // Handle both old signature (email, tag) and new signature (email, properties_array)
        if (is_string($properties)) {
            // Legacy call: update_customer_attributes($email, $tag)
            return $this->send_customer_tag($email, $properties);
        }
        return $this->update_customer_profile($email, $properties);
    }

    private function send_customer_tag($email, $tag_value, $profile_key = 'Form Signups') {
        $properties = array(
            $profile_key => array(
                '$add' => array($tag_value)
            )
        );
        
        return $this->update_customer_profile($email, $properties);
    }

    /**
     * Send event to CleverTap
     */
    public function send_event($email, $event_name, $event_data = array()) {
        if (empty($this->account_id) || empty($this->passcode)) {
            error_log('CleverTap API credentials not configured');
            return false;
        }

        $endpoint = $this->api_url . 'upload';

        $event_payload = array(
            'identity' => $email,
            'type'     => 'event',
            'evtName'  => $event_name,
            'evtData'  => $event_data
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
            'X-CleverTap-Passcode'   => $this->passcode,
            'Content-Type'           => 'application/json'
        );

        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => json_encode($payload),
            'timeout' => 30
        );

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            error_log('CleverTap API request error: ' . $response->get_error_message());
            return false;
        }

        $body    = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        return $decoded;
    }

    /**
     * Test API connection
     */
    public function test_connection() {
        $test_properties = array(
            'Test Connection' => array(
                '$add' => array('Plugin Test')
            )
        );
        return $this->update_customer_profile('test@example.com', $test_properties);
    }
}
