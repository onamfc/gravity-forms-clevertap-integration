<?php

namespace CTGF\Tests\Unit;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use CTGF_Submission_Handler;

class SubmissionHandlerTest extends TestCase
{
    private $submissionHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        Actions\expectAdded('gform_after_submission');

        $this->submissionHandler = new CTGF_Submission_Handler();
    }

    public function testConstructorAddsHooks()
    {
        Actions\expectAdded('gform_after_submission')->once();

        new CTGF_Submission_Handler();
    }

    public function testHandleSubmissionWithValidConfig()
    {
        global $wpdb;
        
        // Mock database config
        $wpdb->get_row = function($query) {
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'active' => 1
            ];
        };

        // Mock form entry and form
        $entry = $this->createMockEntry();
        $form = $this->createMockForm();

        // Mock functions
        Functions\when('rgar')->alias(function($entry, $field_id) {
            return $entry[$field_id] ?? '';
        });

        Functions\when('is_email')->justReturn(true);
        Functions\when('get_option')->justReturn(false); // Disable logging
        Functions\when('wp_schedule_single_event')->justReturn(true);

        // Mock the API class
        $mockApi = \Mockery::mock('overload:CTGF_CleverTap_API');
        $mockApi->shouldReceive('update_customer_attributes')
               ->once()
               ->with('test@example.com', 'Test Tag')
               ->andReturn(true);

        Functions\expect('wp_schedule_single_event')
            ->once()
            ->with(
                \Mockery::type('int'),
                'ctgf_send_delayed_event',
                \Mockery::type('array')
            );

        $this->submissionHandler->handle_submission($entry, $form);
    }

    public function testHandleSubmissionWithNoConfig()
    {
        global $wpdb;
        
        // Mock no database config
        $wpdb->get_row = function($query) {
            return null;
        };

        $entry = $this->createMockEntry();
        $form = $this->createMockForm();

        // Should return early without doing anything
        Functions\expect('rgar')->never();
        Functions\expect('wp_schedule_single_event')->never();

        $this->submissionHandler->handle_submission($entry, $form);
    }

    public function testHandleSubmissionWithInactiveConfig()
    {
        global $wpdb;
        
        // Mock inactive database config
        $wpdb->get_row = function($query) {
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'active' => 0
            ];
        };

        $entry = $this->createMockEntry();
        $form = $this->createMockForm();

        // Should return early without doing anything
        Functions\expect('rgar')->never();
        Functions\expect('wp_schedule_single_event')->never();

        $this->submissionHandler->handle_submission($entry, $form);
    }

    public function testHandleSubmissionWithEmptyEmail()
    {
        global $wpdb;
        
        // Mock database config
        $wpdb->get_row = function($query) {
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'active' => 1
            ];
        };

        $entry = $this->createMockEntry(['1' => '']); // Empty email
        $form = $this->createMockForm();

        Functions\when('rgar')->alias(function($entry, $field_id) {
            return $entry[$field_id] ?? '';
        });

        Functions\when('get_option')->justReturn(true); // Enable logging
        Functions\when('error_log')->justReturn(true);

        // Should return early without scheduling event
        Functions\expect('wp_schedule_single_event')->never();

        $this->submissionHandler->handle_submission($entry, $form);
    }

    public function testHandleSubmissionWithInvalidEmail()
    {
        global $wpdb;
        
        // Mock database config
        $wpdb->get_row = function($query) {
            return (object) [
                'id' => 1,
                'form_id' => 1,
                'email_field' => '1',
                'tag' => 'Test Tag',
                'active' => 1
            ];
        };

        $entry = $this->createMockEntry(['1' => 'invalid-email']);
        $form = $this->createMockForm();

        Functions\when('rgar')->alias(function($entry, $field_id) {
            return $entry[$field_id] ?? '';
        });

        Functions\when('is_email')->justReturn(false);
        Functions\when('get_option')->justReturn(true); // Enable logging
        Functions\when('error_log')->justReturn(true);

        // Should return early without scheduling event
        Functions\expect('wp_schedule_single_event')->never();

        $this->submissionHandler->handle_submission($entry, $form);
    }

    public function testDelayedEventHandling()
    {
        // Mock the API class
        $mockApi = \Mockery::mock('overload:CTGF_CleverTap_API');
        $mockApi->shouldReceive('send_event')
               ->once()
               ->with(
                   'test@example.com',
                   'Newsletter Signup',
                   \Mockery::type('array')
               )
               ->andReturn(true);

        Functions\when('get_option')->justReturn(true); // Enable logging
        Functions\when('error_log')->justReturn(true);

        $userData = ['email' => 'test@example.com', 'identity' => 'test@example.com'];
        $eventData = ['tag' => 'Test Tag', 'form_id' => 1, 'source' => 'gravity_forms'];

        // Call the delayed event handler function
        ctgf_handle_delayed_event($userData, $eventData);
    }
}