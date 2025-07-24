<?php

namespace CTGF\Tests\Unit;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;
use CTGF_CleverTap_API;

class CleverTapApiTest extends TestCase
{
    private $api;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WordPress options
        Functions\when('get_option')
            ->alias(function($option, $default = '') {
                switch ($option) {
                    case 'ctgf_account_id':
                        return 'test_account_id';
                    case 'ctgf_passcode':
                        return 'test_passcode';
                    default:
                        return $default;
                }
            });

        $this->api = new CTGF_CleverTap_API();
    }

    public function testUpdateCustomerAttributesSuccess()
    {
        // Mock successful API response
        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'success'])
        ]);

        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'success'])
        );

        $result = $this->api->update_customer_attributes('test@example.com', 'Test Tag');
        
        $this->assertTrue($result);
    }

    public function testUpdateCustomerAttributesFailure()
    {
        // Mock failed API response
        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 400],
            'body' => json_encode(['status' => 'error', 'message' => 'Invalid request'])
        ]);

        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'error', 'message' => 'Invalid request'])
        );

        $result = $this->api->update_customer_attributes('test@example.com', 'Test Tag');
        
        $this->assertFalse($result);
    }

    public function testSendEventSuccess()
    {
        // Mock successful API response
        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'success'])
        ]);

        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'success'])
        );

        $eventData = ['tag' => 'Test Tag', 'form_id' => 1];
        $result = $this->api->send_event('test@example.com', 'Newsletter Signup', $eventData);
        
        $this->assertTrue($result);
    }

    public function testSendEventWithMissingCredentials()
    {
        // Mock empty credentials
        Functions\when('get_option')
            ->alias(function($option, $default = '') {
                return '';
            });

        $api = new CTGF_CleverTap_API();
        $result = $api->send_event('test@example.com', 'Newsletter Signup', []);
        
        $this->assertFalse($result);
    }

    public function testTestConnectionSuccess()
    {
        // Mock successful API response
        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'success'])
        ]);

        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'success'])
        );

        $result = $this->api->test_connection();
        
        $this->assertTrue($result);
    }

    public function testApiRequestWithWpError()
    {
        // Mock WP_Error response
        $wpError = new \stdClass();
        $wpError->get_error_message = function() {
            return 'Connection timeout';
        };

        Functions\when('wp_remote_post')->justReturn($wpError);
        Functions\when('is_wp_error')->justReturn(true);

        $result = $this->api->update_customer_attributes('test@example.com', 'Test Tag');
        
        $this->assertFalse($result);
    }
}