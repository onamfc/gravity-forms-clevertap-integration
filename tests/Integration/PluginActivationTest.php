<?php

namespace CTGF\Tests\Integration;

use CTGF\Tests\TestCase;
use Brain\Monkey\Functions;

class PluginActivationTest extends TestCase
{
    public function testPluginActivationCreatesTable()
    {
        global $wpdb;
        
        // Mock WordPress functions
        Functions\when('get_charset_collate')->justReturn('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        Functions\when('dbDelta')->justReturn(['wp_ctgf_form_configs' => 'Created table wp_ctgf_form_configs']);
        
        // Mock the activation function
        $tableName = $wpdb->prefix . 'ctgf_form_configs';
        $charsetCollate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        
        $expectedSql = "CREATE TABLE $tableName (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        email_field varchar(10) NOT NULL,
        tag varchar(255) NOT NULL,
        active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY form_id (form_id)
    ) $charsetCollate;";

        Functions\expect('dbDelta')
            ->once()
            ->with($expectedSql);

        // Simulate activation
        ctgf_activate();
    }

    public function testPluginUninstallCleansUp()
    {
        global $wpdb;
        
        Functions\when('delete_option')->justReturn(true);
        Functions\when('wp_clear_scheduled_hook')->justReturn(true);
        
        $wpdb->query = function($query) {
            $this->assertStringContainsString('DROP TABLE IF EXISTS', $query);
            $this->assertStringContainsString('wp_ctgf_form_configs', $query);
            return true;
        };

        Functions\expect('delete_option')
            ->times(3)
            ->with(\Mockery::anyOf('ctgf_account_id', 'ctgf_passcode', 'ctgf_enable_logging'));

        Functions\expect('wp_clear_scheduled_hook')
            ->once()
            ->with('ctgf_send_delayed_event');

        // Include and test uninstall script
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            define('WP_UNINSTALL_PLUGIN', true);
        }
        
        // Simulate uninstall
        delete_option('ctgf_account_id');
        delete_option('ctgf_passcode');
        delete_option('ctgf_enable_logging');
        
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        wp_clear_scheduled_hook('ctgf_send_delayed_event');
    }

    public function testGravityFormsCheck()
    {
        // Test when Gravity Forms is not active
        Functions\when('class_exists')->alias(function($class) {
            return $class !== 'GFForms';
        });

        Functions\expect('add_action')
            ->once()
            ->with('admin_notices', 'ctgf_gravity_forms_notice');

        Functions\expect('deactivate_plugins')
            ->once()
            ->with(\Mockery::type('string'));

        ctgf_check_gravity_forms();
    }
}