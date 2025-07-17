<?php
/**
 * Uninstall script
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Remove options
delete_option('ctgf_account_id');
delete_option('ctgf_passcode');
delete_option('ctgf_enable_logging');

// Remove database table
$table_name = $wpdb->prefix . 'ctgf_form_configs';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear scheduled events
wp_clear_scheduled_hook('ctgf_send_delayed_event');