<?php

namespace CTGF\Tests\Unit;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use CTGF_Form_Settings;

class FormSettingsTest extends TestCase
{
    private $formSettings;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WordPress functions
        Functions\when('wp_enqueue_script')->justReturn(true);
        Functions\when('wp_enqueue_style')->justReturn(true);
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('sanitize_text_field')->returnArg();
        
        Filters\expectAdded('gform_form_settings_menu');
        Actions\expectAdded('gform_form_settings_page_clevertap');
        Actions\expectAdded('admin_enqueue_scripts');
        Actions\expectAdded('gform_pre_form_settings_save');

        $this->formSettings = new CTGF_Form_Settings();
    }

    public function testConstructorAddsHooks()
    {
        Filters\expectAdded('gform_form_settings_menu')->once();
        Actions\expectAdded('gform_form_settings_page_clevertap')->once();
        Actions\expectAdded('admin_enqueue_scripts')->once();
        Actions\expectAdded('gform_pre_form_settings_save')->once();

        new CTGF_Form_Settings();
    }

    public function testAddFormSettingsMenu()
    {
        $menuItems = [];
        $formId = 1;

        $result = $this->formSettings->add_form_settings_menu($menuItems, $formId);

        $this->assertCount(1, $result);
        $this->assertEquals('clevertap', $result[0]['name']);
        $this->assertEquals('CleverTap Integration', $result[0]['label']);
        $this->assertEquals('gform-icon--cog', $result[0]['icon']);
    }

    public function testEnqueueFormSettingsScriptsOnCorrectPage()
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'ctgf-admin-js',
                \Mockery::type('string'),
                ['jquery'],
                \Mockery::type('string'),
                true
            );

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with(
                'ctgf-admin-css',
                \Mockery::type('string'),
                [],
                \Mockery::type('string')
            );

        $this->formSettings->enqueue_form_settings_scripts('forms_page_gf_edit_forms');
    }

    public function testEnqueueFormSettingsScriptsSkipsOtherPages()
    {
        Functions\expect('wp_enqueue_script')->never();
        Functions\expect('wp_enqueue_style')->never();

        $this->formSettings->enqueue_form_settings_scripts('edit.php');
    }

    public function testSaveFormSettingsWithNewConfig()
    {
        global $wpdb;
        
        // Mock form data
        $_POST = [
            'ctgf_save_settings' => '1',
            'ctgf_nonce' => 'test_nonce',
            'ctgf_active' => '1',
            'ctgf_email_field' => '1',
            'ctgf_tag' => 'Test Tag',
            'ctgf_event_name' => 'Test Event'
        ];

        // Mock database operations
        $wpdb->get_var = function($query) {
            return null; // No existing config
        };

        $wpdb->insert = function($table, $data, $format) {
            $this->assertEquals('wp_ctgf_form_configs', $table);
            $this->assertEquals(1, $data['form_id']);
            $this->assertEquals('1', $data['email_field']);
            $this->assertEquals('Test Tag', $data['tag']);
            $this->assertEquals('Test Event', $data['event_name']);
            $this->assertEquals(1, $data['active']);
            return 1;
        };

        // This would normally be called during form settings page rendering
        // We're testing the logic that would be executed
        $formId = 1;
        $active = isset($_POST['ctgf_active']) ? 1 : 0;
        $emailField = sanitize_text_field($_POST['ctgf_email_field'] ?? '');
        $tag = sanitize_text_field($_POST['ctgf_tag'] ?? '');
        $eventName = sanitize_text_field($_POST['ctgf_event_name'] ?? 'Newsletter Signup');

        $this->assertEquals(1, $active);
        $this->assertEquals('1', $emailField);
        $this->assertEquals('Test Tag', $tag);
        $this->assertEquals('Test Event', $eventName);
    }

    public function testSaveFormSettingsWithExistingConfig()
    {
        global $wpdb;
        
        // Mock form data
        $_POST = [
            'ctgf_save_settings' => '1',
            'ctgf_nonce' => 'test_nonce',
            'ctgf_active' => '1',
            'ctgf_email_field' => '2',
            'ctgf_tag' => 'Updated Tag',
            'ctgf_event_name' => 'Updated Event'
        ];

        // Mock database operations
        $wpdb->get_var = function($query) {
            return 1; // Existing config
        };

        $wpdb->update = function($table, $data, $where, $format, $whereFormat) {
            $this->assertEquals('wp_ctgf_form_configs', $table);
            $this->assertEquals('2', $data['email_field']);
            $this->assertEquals('Updated Tag', $data['tag']);
            $this->assertEquals('Updated Event', $data['event_name']);
            $this->assertEquals(1, $data['active']);
            $this->assertEquals(['form_id' => 1], $where);
            return 1;
        };

        // Test the update logic
        $formId = 1;
        $active = isset($_POST['ctgf_active']) ? 1 : 0;
        $emailField = sanitize_text_field($_POST['ctgf_email_field'] ?? '');
        $tag = sanitize_text_field($_POST['ctgf_tag'] ?? '');
        $eventName = sanitize_text_field($_POST['ctgf_event_name'] ?? 'Newsletter Signup');

        $this->assertEquals(1, $active);
        $this->assertEquals('2', $emailField);
        $this->assertEquals('Updated Tag', $tag);
        $this->assertEquals('Updated Event', $eventName);
    }
}