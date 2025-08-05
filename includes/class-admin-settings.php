<?php
/**
 * Admin Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTGF_Admin_Settings {
    
    public function __construct() {
        // Only initialize if we're in admin
        if (!is_admin()) {
            return;
        }
        
        add_action('admin_menu', array($this, 'add_admin_menu'), 99);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_ctgf_test_connection', array($this, 'test_connection'));
    }
    
    public function add_admin_menu() {
        $page_hook = add_submenu_page(
            'gf_edit_forms',
            'CleverTap Integration',
            'CleverTap Integration',
            'manage_options',
            'ctgf-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only load on our settings page
        if (strpos($hook, 'ctgf-settings') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('ctgf-admin-js', CTGF_PLUGIN_URL . 'assets/admin.js', array('jquery'), CTGF_VERSION, true);
        wp_enqueue_style('ctgf-admin-css', CTGF_PLUGIN_URL . 'assets/admin.css', array(), CTGF_VERSION);
        
        // Localize script for AJAX
        wp_localize_script('ctgf-admin-js', 'ctgfAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ctgf_test_connection')
        ));
    }
    
    public function register_settings() {
        register_setting('ctgf_settings', 'ctgf_account_id');
        register_setting('ctgf_settings', 'ctgf_passcode');
        register_setting('ctgf_settings', 'ctgf_enable_logging');
    }
    
    public function settings_page() {
        // Double-check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        ?>
        <div class="wrap ctgf-admin-wrap">
            <h1>CleverTap Gravity Forms Integration</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ctgf_settings');
                do_settings_sections('ctgf_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">CleverTap Account ID</th>
                        <td>
                            <input type="text" name="ctgf_account_id" value="<?php echo esc_attr(get_option('ctgf_account_id')); ?>" class="regular-text" />
                            <p class="description">Your CleverTap Account ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">CleverTap Passcode</th>
                        <td>
                            <input type="password" name="ctgf_passcode" value="<?php echo esc_attr(get_option('ctgf_passcode')); ?>" class="regular-text" />
                            <p class="description">Your CleverTap Passcode</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Logging</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ctgf_enable_logging" value="1" <?php checked(get_option('ctgf_enable_logging'), 1); ?> />
                                Enable detailed logging for debugging
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>Test Connection</h2>
            <p>Test your CleverTap API connection:</p>
            <button type="button" id="ctgf-test-connection" class="button">Test Connection</button>
            <div id="ctgf-test-result"></div>
        </div>
        <?php
    }
    
    public function test_connection() {
        check_ajax_referer('ctgf_test_connection', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $api = new CTGF_CleverTap_API();
        $result = $api->test_connection();
        
        if ($result) {
            wp_send_json_success('Connection test passed');
        } else {
            wp_send_json_error('Connection test failed');
        }
    }
}