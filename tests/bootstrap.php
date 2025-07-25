<?php
/**
 * PHPUnit bootstrap file for CleverTap Gravity Forms Integration
 */

// Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Initialize Brain Monkey
\Brain\Monkey\setUp();

// Mock WordPress functions and constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins');
}

// Mock WordPress functions that are commonly used
\Brain\Monkey\Functions\when('wp_remote_post')->justReturn([
    'response' => ['code' => 200],
    'body' => json_encode(['status' => 'success'])
]);

\Brain\Monkey\Functions\when('wp_remote_retrieve_body')->returnArg();
\Brain\Monkey\Functions\when('is_wp_error')->justReturn(false);
\Brain\Monkey\Functions\when('get_option')->justReturn('');
\Brain\Monkey\Functions\when('update_option')->justReturn(true);
\Brain\Monkey\Functions\when('delete_option')->justReturn(true);
\Brain\Monkey\Functions\when('wp_create_nonce')->justReturn('test_nonce');
\Brain\Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
\Brain\Monkey\Functions\when('current_user_can')->justReturn(true);
\Brain\Monkey\Functions\when('admin_url')->returnArg();
\Brain\Monkey\Functions\when('plugin_dir_url')->justReturn('http://example.com/wp-content/plugins/clevertap-gravityforms/');
\Brain\Monkey\Functions\when('plugin_dir_path')->justReturn('/tmp/wordpress/wp-content/plugins/clevertap-gravityforms/');
\Brain\Monkey\Functions\when('is_email')->justReturn(true);
\Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();
\Brain\Monkey\Functions\when('esc_attr')->returnArg();
\Brain\Monkey\Functions\when('esc_html')->returnArg();
\Brain\Monkey\Functions\when('wp_schedule_single_event')->justReturn(true);
\Brain\Monkey\Functions\when('wp_clear_scheduled_hook')->justReturn(true);
\Brain\Monkey\Functions\when('time')->justReturn(1640995200);
\Brain\Monkey\Functions\when('wp_send_json_success')->justReturn(null);
\Brain\Monkey\Functions\when('wp_send_json_error')->justReturn(null);
\Brain\Monkey\Functions\when('wp_die')->justReturn(null);
\Brain\Monkey\Functions\when('check_ajax_referer')->justReturn(true);
\Brain\Monkey\Functions\when('rgget')->justReturn('1');
\Brain\Monkey\Functions\when('rgar')->justReturn('test@example.com');
\Brain\Monkey\Functions\when('selected')->justReturn(' selected="selected"');
\Brain\Monkey\Functions\when('checked')->justReturn(' checked="checked"');

// Mock WordPress actions and filters
\Brain\Monkey\Actions\expectAdded('plugins_loaded');
\Brain\Monkey\Actions\expectAdded('admin_init');
\Brain\Monkey\Actions\expectAdded('admin_menu');
\Brain\Monkey\Actions\expectAdded('gform_after_submission');
\Brain\Monkey\Filters\expectAdded('gform_form_settings_menu');

// Mock global $wpdb
global $wpdb;
$wpdb = new stdClass();
$wpdb->prefix = 'wp_';
$wpdb->prepare = function($query, ...$args) {
    return vsprintf(str_replace('%s', "'%s'", str_replace('%d', '%d', $query)), $args);
};
$wpdb->get_row = function($query) {
    if (strpos($query, 'SELECT event_name') !== false) {
        return (object) [
            'event_name' => 'Test Event'
        ];
    }
    return (object) [
        'id' => 1,
        'form_id' => 1,
        'email_field' => '1',
        'tag' => 'Test Tag',
        'event_name' => 'Test Event',
        'active' => 1
    ];
};
$wpdb->get_var = function($query) {
    return 1;
};
$wpdb->insert = function($table, $data, $format) {
    return 1;
};
$wpdb->update = function($table, $data, $where, $format, $where_format) {
    return 1;
};
$wpdb->query = function($query) {
    return true;
};

// Mock Gravity Forms classes
if (!class_exists('GFForms')) {
    class GFForms {
        public static function get_version() {
            return '2.7.0';
        }
    }
}

if (!class_exists('GFAPI')) {
    class GFAPI {
        public static function get_form($form_id) {
            return [
                'id' => $form_id,
                'title' => 'Test Form',
                'fields' => [
                    (object) [
                        'id' => '1',
                        'type' => 'email',
                        'label' => 'Email Address',
                        'inputType' => 'email'
                    ],
                    (object) [
                        'id' => '2',
                        'type' => 'text',
                        'label' => 'Name',
                        'inputType' => 'text'
                    ]
                ]
            ];
        }
    }
}

if (!class_exists('GFCommon')) {
    class GFCommon {
        public static function log_debug($message) {
            error_log($message);
        }
    }
}

// Include the plugin files
require_once dirname(__DIR__) . '/includes/class-clevertap-api.php';
require_once dirname(__DIR__) . '/includes/class-admin-settings.php';
require_once dirname(__DIR__) . '/includes/class-form-settings.php';
require_once dirname(__DIR__) . '/includes/class-submission-handler.php';