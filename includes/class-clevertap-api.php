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
        $this->api_url    = 'https://us1.api.clevertap.com/1/';
    }

    /**
     * Update customer attributes
     */
    public function update_customer_attributes($email, $tag_value) {
        if (empty($this->account_id) || empty($this->passcode)) {
            error_log('CleverTap API credentials not configured');
            return false;
        }

        $endpoint = $this->api_url . 'upload';

        $payload = array(
            'd' => array(
                array(
                    'identity'    => $email,
                    'type'        => 'profile',
                    'profileData' => array(
                        'Form Signups' => array(
                            '$add' => array($tag_value)
                        )
                    )
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

    /**
     * Send event to CleverTap
     */
    public function send_event($email, $event_name, $event_data = array()) {
        if (empty($this->account_id) || empty($this->passcode)) {
            error_log('CleverTap API credentials not configured');
            return false;
        }

        $endpoint = $this->api_url . 'events.json';

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
        $test_email = 'test@example.com';
        $test_tag   = 'Test Tag';

        return $this->update_customer_attributes($test_email, $test_tag);
    }
}
