<?php
/**
 * Plugin Name: CleverTap Gravity Forms Integration
 * Description: Integrates Gravity Forms with CleverTap for seamless user tracking and tagging
 * Version: 1.2.0
 * Author: Brandon Estrella
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CTGF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CTGF_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CTGF_VERSION', '1.2.0');

// Check if Gravity Forms is active
add_action('admin_init', 'ctgf_check_gravity_forms');

function ctgf_check_gravity_forms() {
    if (!class_exists('GFForms')) {
        add_action('admin_notices', 'ctgf_gravity_forms_notice');
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

function ctgf_gravity_forms_notice() {
    echo '<div class="notice notice-error"><p>CleverTap Gravity Forms Integration requires Gravity Forms to be installed and activated.</p></div>';
}

// Initialize the plugin
add_action('plugins_loaded', 'ctgf_init');

function ctgf_init() {
    if (!class_exists('GFForms')) {
        return;
    }
    
    // Include required files
    require_once CTGF_PLUGIN_PATH . 'includes/class-clevertap-api.php';
    require_once CTGF_PLUGIN_PATH . 'includes/class-admin-settings.php';
    require_once CTGF_PLUGIN_PATH . 'includes/class-form-settings.php';
    require_once CTGF_PLUGIN_PATH . 'includes/class-submission-handler.php';
    
    // Initialize classes with proper timing
    if (is_admin()) {
        new CTGF_Admin_Settings();
        new CTGF_Form_Settings();
    }
    
    // Initialize submission handler for all contexts
    new CTGF_Submission_Handler();
}

// Add admin notices for debugging
add_action('admin_notices', 'ctgf_debug_notices');

function ctgf_debug_notices() {
    if (isset($_GET['page']) && $_GET['page'] === 'ctgf-settings') {
        if (!class_exists('GFForms')) {
            echo '<div class="notice notice-error"><p>Gravity Forms is not active or installed.</p></div>';
        }
    }
}

// Activation hook
register_activation_hook(__FILE__, 'ctgf_activate');

function ctgf_activate() {
    // Create database table for form configurations
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ctgf_form_configs';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_id mediumint(9) NOT NULL,
        email_field varchar(10) NOT NULL,
        tag varchar(255) NOT NULL,
        event_name varchar(255) NOT NULL DEFAULT 'Newsletter Signup',
        property_mappings TEXT,
        event_mappings TEXT,
        active tinyint(1) DEFAULT 1,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY form_id (form_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add event_name column to existing installations
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'event_name'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN event_name varchar(255) NOT NULL DEFAULT 'Newsletter Signup' AFTER tag");
    }
    
    // Add property_mappings column to existing installations
    $property_mappings_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'property_mappings'");
    if (empty($property_mappings_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN property_mappings TEXT AFTER event_name");
    }
    
    // Add event_mappings column to existing installations
    $event_mappings_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'event_mappings'");
    if (empty($event_mappings_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN event_mappings TEXT AFTER property_mappings");
    }
    
    // Migrate existing profile_key data to property_mappings if needed
    $profile_key_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'profile_key'");
    if (!empty($profile_key_exists)) {
        // Migrate existing profile_key configurations
        $configs_with_profile_key = $wpdb->get_results("SELECT id, profile_key, tag FROM $table_name WHERE profile_key IS NOT NULL AND profile_key != ''");
        foreach ($configs_with_profile_key as $config) {
            if (!empty($config->tag)) {
                $legacy_mapping = array(
                    array(
                        'property_name' => $config->profile_key,
                        'form_field' => 'tag_legacy' // Special marker for legacy tag
                    )
                );
                $wpdb->update(
                    $table_name,
                    array('property_mappings' => json_encode($legacy_mapping)),
                    array('id' => $config->id),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        // Remove the old profile_key column
        $wpdb->query("ALTER TABLE $table_name DROP COLUMN profile_key");
    }
}