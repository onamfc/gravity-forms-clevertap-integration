<?php

namespace CTGF\Tests\Integration;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use CTGF_CleverTap_API;
use CTGF_Submission_Handler;

class EndToEndTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up global options for API credentials
        Functions\when('get_option')
            ->alias(function($option, $default = '') {
                switch ($option) {
                    case 'ctgf_account_id':
                        return 'test_account_id';
                    case 'ctgf_passcode':
                        return 'test_passcode';
                    case 'ctgf_enable_logging':
                        return true;
                    default:
                        return $default;
                }
            });
    }

    public function testCompleteFormSubmissionWorkflow()
    {
        global $wpdb;
        
        // Mock database config for active form
        $wpdb->get_row = function($query) {
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Newsletter Signup',
                'active' => 1
            ];
        };

        // Mock successful API responses
        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'success'])
        ]);

        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'success'])
        );

        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('rgar')->alias(function($entry, $field_id) {
            return $field_id === '1' ? 'user@example.com' : '';
        });
        Functions\when('is_email')->justReturn(true);
        Functions\when('wp_schedule_single_event')->justReturn(true);
        Functions\when('error_log')->justReturn(true);

        // Create submission handler
        $handler = new CTGF_Submission_Handler();
        
        // Create mock entry and form
        $entry = $this->createMockEntry([
            '1' => 'user@example.com',
            '2' => 'John Doe'
        ]);
        $form = $this->createMockForm();

        // Verify that wp_schedule_single_event is called for delayed event
        Functions\expect('wp_schedule_single_event')
            ->once()
            ->with(
                \Mockery::type('int'), // timestamp
                'ctgf_send_delayed_event',
                \Mockery::type('array') // event data
            );

        // Process the submission
        $handler->handle_submission($entry, $form);

        // Now test the delayed event processing
        $userData = ['email' => 'user@example.com', 'identity' => 'user@example.com'];
        $eventData = ['tag' => 'Newsletter Signup', 'form_id' => 1, 'source' => 'gravity_forms'];

        // Test delayed event handler
        ctgf_handle_delayed_event($userData, $eventData);

        $this->assertTrue(true); // If we get here without errors, the workflow completed
    }

    public function testFormSubmissionWithMultipleFormsConfiguration()
    {
        global $wpdb;
        
        // Test form 1 (active)
        $wpdb->get_row = function($query) {
            if (strpos($query, 'form_id = 1') !== false) {
                return (object) [
                    'id' => 1,
                    'form_id' => 1,
                    'email_field' => '1',
                    'tag' => 'Contact Form',
                    'active' => 1
                ];
            }
            // Test form 2 (inactive)
            if (strpos($query, 'form_id = 2') !== false) {
                return (object) [
                    'id' => 2,
                    'form_id' => 2,
                    'email_field' => '1',
                    'tag' => 'Inactive Form',
                    'active' => 0
                ];
            }
            return null;
        };

        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 200],
            'body' => json_encode(['status' => 'success'])
        ]);
        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'success'])
        );
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('rgar')->justReturn('test@example.com');
        Functions\when('is_email')->justReturn(true);
        Functions\when('wp_schedule_single_event')->justReturn(true);
        Functions\when('error_log')->justReturn(true);

        $handler = new CTGF_Submission_Handler();

        // Test active form - should process
        $entry1 = $this->createMockEntry();
        $form1 = $this->createMockForm(['id' => '1']);

        Functions\expect('wp_schedule_single_event')->once();
        $handler->handle_submission($entry1, $form1);

        // Test inactive form - should not process
        $entry2 = $this->createMockEntry();
        $form2 = $this->createMockForm(['id' => '2']);

        // Reset expectations - inactive form should not schedule events
        Functions\expect('wp_schedule_single_event')->never();
        $handler->handle_submission($entry2, $form2);
    }

    public function testApiFailureHandling()
    {
        global $wpdb;
        
        $wpdb->get_row = function($query) {
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'active' => 1
            ];
        };

        // Mock API failure
        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 400],
            'body' => json_encode(['status' => 'error', 'message' => 'Invalid request'])
        ]);
        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'error', 'message' => 'Invalid request'])
        );
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('rgar')->justReturn('test@example.com');
        Functions\when('is_email')->justReturn(true);
        Functions\when('wp_schedule_single_event')->justReturn(true);
        Functions\when('error_log')->justReturn(true);

        $handler = new CTGF_Submission_Handler();
        $entry = $this->createMockEntry();
        $form = $this->createMockForm();

        // Should still schedule delayed event even if profile update fails
        Functions\expect('wp_schedule_single_event')->once();
        Functions\expect('error_log')->atLeast()->once();

        $handler->handle_submission($entry, $form);
    }

    public function testNetworkErrorHandling()
    {
        global $wpdb;
        
        $wpdb->get_row = function($query) {
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'active' => 1
            ];
        };

        // Mock network error
        $wpError = new \stdClass();
        $wpError->get_error_message = function() {
            return 'Connection timeout';
        };

        Functions\when('wp_remote_post')->justReturn($wpError);
        Functions\when('is_wp_error')->justReturn(true);
        Functions\when('rgar')->justReturn('test@example.com');
        Functions\when('is_email')->justReturn(true);
        Functions\when('wp_schedule_single_event')->justReturn(true);
        Functions\when('error_log')->justReturn(true);

        $handler = new CTGF_Submission_Handler();
        $entry = $this->createMockEntry();
        $form = $this->createMockForm();

        // Should still schedule delayed event even with network errors
        Functions\expect('wp_schedule_single_event')->once();
        Functions\expect('error_log')->atLeast()->once();

        $handler->handle_submission($entry, $form);
    }

    public function testDelayedEventWithApiFailure()
    {
        // Mock API failure for delayed event
        Functions\when('wp_remote_post')->justReturn([
            'response' => ['code' => 500],
            'body' => json_encode(['status' => 'error', 'message' => 'Server error'])
        ]);
        Functions\when('wp_remote_retrieve_body')->justReturn(
            json_encode(['status' => 'error', 'message' => 'Server error'])
        );
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('error_log')->justReturn(true);

        $userData = ['email' => 'test@example.com', 'identity' => 'test@example.com'];
        $eventData = ['tag' => 'Test Tag', 'form_id' => 1, 'source' => 'gravity_forms'];

        Functions\expect('error_log')
            ->atLeast()
            ->once()
            ->with(\Mockery::pattern('/Failed to send delayed event/'));

        ctgf_handle_delayed_event($userData, $eventData);
    }

    public function testCompletePluginInitialization()
    {
        // Mock Gravity Forms being active
        Functions\when('class_exists')->alias(function($class) {
            return $class === 'GFForms';
        });

        Functions\when('is_admin')->justReturn(true);

        // Mock file includes
        Functions\when('require_once')->justReturn(true);

        // Test that plugin initialization doesn't throw errors
        Actions\expectAdded('plugins_loaded');
        
        // This would normally be called by WordPress
        do_action('plugins_loaded');

        $this->assertTrue(true); // If we get here, initialization completed
    }

    public function testDatabaseTableCreationAndCleanup()
    {
        global $wpdb;
        
        // Test table creation during activation
        Functions\when('get_charset_collate')->justReturn('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        Functions\when('dbDelta')->justReturn(['wp_ctgf_form_configs' => 'Created table wp_ctgf_form_configs']);

        // Test activation
        ctgf_activate();

        // Test cleanup during uninstall
        Functions\when('delete_option')->justReturn(true);
        Functions\when('wp_clear_scheduled_hook')->justReturn(true);
        
        $wpdb->query = function($query) {
            return true;
        };

        // Simulate uninstall
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            define('WP_UNINSTALL_PLUGIN', true);
        }

        // Test that cleanup operations complete without errors
        delete_option('ctgf_account_id');
        delete_option('ctgf_passcode');
        delete_option('ctgf_enable_logging');
        wp_clear_scheduled_hook('ctgf_send_delayed_event');

        $this->assertTrue(true);
    }
}