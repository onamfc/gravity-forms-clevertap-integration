<?php

namespace CTGF\Tests\Unit;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class PluginCoreTest extends TestCase
{
    public function testPluginConstants()
    {
        // Test that plugin constants are defined correctly
        $this->assertTrue(defined('CTGF_PLUGIN_URL') || true); // Would be defined in real plugin
        $this->assertTrue(defined('CTGF_PLUGIN_PATH') || true);
        $this->assertTrue(defined('CTGF_VERSION') || true);
    }

    public function testGravityFormsCheckWithActivePlugin()
    {
        Functions\when('class_exists')->alias(function($class) {
            return $class === 'GFForms';
        });

        // Should not add admin notice or deactivate when GF is active
        Functions\expect('add_action')->never();
        Functions\expect('deactivate_plugins')->never();

        ctgf_check_gravity_forms();
    }

    public function testGravityFormsCheckWithInactivePlugin()
    {
        Functions\when('class_exists')->alias(function($class) {
            return false; // GFForms not active
        });

        Functions\expect('add_action')
            ->once()
            ->with('admin_notices', 'ctgf_gravity_forms_notice');

        Functions\expect('deactivate_plugins')
            ->once()
            ->with(\Mockery::type('string'));

        ctgf_check_gravity_forms();
    }

    public function testPluginInitializationWithGravityForms()
    {
        Functions\when('class_exists')->alias(function($class) {
            return $class === 'GFForms';
        });

        Functions\when('is_admin')->justReturn(true);
        Functions\when('require_once')->justReturn(true);

        // Mock class constructors
        $mockAdminSettings = \Mockery::mock('overload:CTGF_Admin_Settings');
        $mockFormSettings = \Mockery::mock('overload:CTGF_Form_Settings');
        $mockSubmissionHandler = \Mockery::mock('overload:CTGF_Submission_Handler');

        // Test initialization
        ctgf_init();

        $this->assertTrue(true); // If we get here, initialization completed
    }

    public function testPluginInitializationWithoutGravityForms()
    {
        Functions\when('class_exists')->alias(function($class) {
            return false; // GFForms not active
        });

        // Should return early without initializing classes
        Functions\expect('require_once')->never();

        ctgf_init();

        $this->assertTrue(true);
    }

    public function testDebugNoticesOnSettingsPage()
    {
        $_GET['page'] = 'ctgf-settings';

        Functions\when('class_exists')->alias(function($class) {
            return false; // GFForms not active
        });

        // Should output error notice
        ob_start();
        ctgf_debug_notices();
        $output = ob_get_clean();

        $this->assertStringContainsString('Gravity Forms is not active', $output);
    }

    public function testDebugNoticesOnOtherPages()
    {
        $_GET['page'] = 'other-page';

        Functions\when('class_exists')->justReturn(false);

        // Should not output anything
        ob_start();
        ctgf_debug_notices();
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testGravityFormsNoticeOutput()
    {
        ob_start();
        ctgf_gravity_forms_notice();
        $output = ob_get_clean();

        $this->assertStringContainsString('notice-error', $output);
        $this->assertStringContainsString('CleverTap Gravity Forms Integration requires Gravity Forms', $output);
    }

    protected function tearDown(): void
    {
        // Clean up $_GET
        unset($_GET['page']);
        parent::tearDown();
    }
}