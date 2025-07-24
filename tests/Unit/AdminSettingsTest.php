<?php

namespace CTGF\Tests\Unit;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use CTGF_Admin_Settings;

class AdminSettingsTest extends TestCase
{
    private $adminSettings;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock WordPress functions
        Functions\when('add_submenu_page')->justReturn('forms_page_ctgf-settings');
        Functions\when('register_setting')->justReturn(true);
        Functions\when('wp_enqueue_script')->justReturn(true);
        Functions\when('wp_enqueue_style')->justReturn(true);
        
        Actions\expectAdded('admin_menu');
        Actions\expectAdded('admin_init');
        Actions\expectAdded('admin_enqueue_scripts');
        Actions\expectAdded('wp_ajax_ctgf_test_connection');

        $this->adminSettings = new CTGF_Admin_Settings();
    }

    public function testConstructorAddsHooks()
    {
        // Verify that hooks are added during construction
        Actions\expectAdded('admin_menu')->once();
        Actions\expectAdded('admin_init')->once();
        Actions\expectAdded('admin_enqueue_scripts')->once();
        Actions\expectAdded('wp_ajax_ctgf_test_connection')->once();

        new CTGF_Admin_Settings();
    }

    public function testAddAdminMenu()
    {
        Functions\expect('add_submenu_page')
            ->once()
            ->with(
                'gf_edit_forms',
                'CleverTap Integration',
                'CleverTap Integration',
                'manage_options',
                'ctgf-settings',
                \Mockery::type('array')
            )
            ->andReturn('forms_page_ctgf-settings');

        $this->adminSettings->add_admin_menu();
    }

    public function testRegisterSettings()
    {
        Functions\expect('register_setting')
            ->times(3)
            ->with('ctgf_settings', \Mockery::type('string'));

        $this->adminSettings->register_settings();
    }

    public function testEnqueueAdminScriptsOnCorrectPage()
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with('jquery');

        $this->adminSettings->enqueue_admin_scripts('forms_page_ctgf-settings');
    }

    public function testEnqueueAdminScriptsSkipsOtherPages()
    {
        Functions\expect('wp_enqueue_script')->never();

        $this->adminSettings->enqueue_admin_scripts('edit.php');
    }

    public function testTestConnectionSuccess()
    {
        // Mock successful API test
        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        
        // Mock the API class
        $mockApi = \Mockery::mock('overload:CTGF_CleverTap_API');
        $mockApi->shouldReceive('test_connection')->andReturn(true);

        Functions\expect('wp_send_json_success')
            ->once()
            ->with('Connection test passed');

        $this->adminSettings->test_connection();
    }

    public function testTestConnectionFailure()
    {
        // Mock failed API test
        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        
        // Mock the API class
        $mockApi = \Mockery::mock('overload:CTGF_CleverTap_API');
        $mockApi->shouldReceive('test_connection')->andReturn(false);

        Functions\expect('wp_send_json_error')
            ->once()
            ->with('Connection test failed');

        $this->adminSettings->test_connection();
    }

    public function testTestConnectionInsufficientPermissions()
    {
        Functions\when('check_ajax_referer')->justReturn(true);
        Functions\when('current_user_can')->justReturn(false);

        Functions\expect('wp_die')
            ->once()
            ->with('Insufficient permissions');

        $this->adminSettings->test_connection();
    }
}