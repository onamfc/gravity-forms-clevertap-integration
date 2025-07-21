<?php
/**
 * Form Settings Integration - Modern Gravity Forms Approach
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTGF_Form_Settings {
    
    public function __construct() {
        // Use modern Gravity Forms settings approach
        add_filter('gform_form_settings_menu', array($this, 'add_form_settings_menu'), 10, 2);
        add_action('gform_form_settings_page_clevertap', array($this, 'form_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_form_settings_scripts'));
        
        // Handle form saving
        add_action('gform_pre_form_settings_save', array($this, 'save_form_settings'));
    }
    
    public function enqueue_form_settings_scripts($hook) {
        // Check if we're on the Gravity Forms edit form page
        if (strpos($hook, 'gf_edit_forms') !== false || strpos($hook, 'forms_page_gf_edit_forms') !== false) {
            wp_enqueue_script('ctgf-admin-js', CTGF_PLUGIN_URL . 'assets/admin.js', array('jquery'), CTGF_VERSION, true);
            wp_enqueue_style('ctgf-admin-css', CTGF_PLUGIN_URL . 'assets/admin.css', array(), CTGF_VERSION);
        }
    }
    
    /**
     * Add CleverTap menu item to form settings
     */
    public function add_form_settings_menu($menu_items, $form_id) {
        $menu_items[] = array(
            'name' => 'clevertap',
            'label' => 'CleverTap Integration',
            'icon' => 'gform-icon--cog'
        );
        return $menu_items;
    }
    
    /**
     * Display the CleverTap settings page
     */
    public function form_settings_page() {
        $form_id = rgget('id');
        $form = GFAPI::get_form($form_id);
        
        if (!$form) {
            wp_die('Form not found');
        }
        
        // Handle form submission first
        if (isset($_POST['ctgf_save_settings']) && wp_verify_nonce($_POST['ctgf_nonce'], 'ctgf_form_settings')) {
            $this->save_form_settings_immediate($form_id);
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE form_id = %d", $form_id));
        
        $email_field = $config ? $config->email_field : '';
        $tag = $config ? $config->tag : '';
        $active = $config ? $config->active : 0;
        
        // Check if global settings are configured
        $account_id = get_option('ctgf_account_id');
        $passcode = get_option('ctgf_passcode');
        $global_configured = !empty($account_id) && !empty($passcode);
        
        ?>
        <div class="gform-settings-panel">
            <header class="gform-settings-panel__header">
                <h4 class="gform-settings-panel__title">CleverTap Integration Settings</h4>
            </header>
            
            <div class="gform-settings-panel__content">
                <?php if (!$global_configured): ?>
                    <div class="gform-alert gform-alert--warning">
                        <p><strong>Configuration Required:</strong> Please configure your CleverTap API credentials in 
                        <a href="<?php echo admin_url('admin.php?page=ctgf-settings'); ?>">Forms > CleverTap Integration</a> 
                        before enabling form-specific settings.</p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <?php wp_nonce_field('ctgf_form_settings', 'ctgf_nonce'); ?>
                    <input type="hidden" name="ctgf_save_settings" value="1" />
                    
                    <table class="gforms_form_settings" cellspacing="0" cellpadding="0">
                        <tr>
                            <th scope="row">
                                <label for="ctgf_active">Enable Integration</label>
                            </th>
                            <td>
                                <input type="checkbox" id="ctgf_active" name="ctgf_active" value="1" <?php checked($active, 1); ?> />
                                <label for="ctgf_active">Enable CleverTap integration for this form</label>
                                <span class="gform-settings-description">
                                    When enabled, form submissions will be sent to CleverTap with the specified tag.
                                </span>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="ctgf-config-fields" style="<?php echo $active ? 'display:block;' : 'display:none;'; ?>">
                        <table class="gforms_form_settings" cellspacing="0" cellpadding="0">
                            <tr>
                                <th scope="row">
                                    <label for="ctgf_email_field">Email Field</label>
                                </th>
                                <td>
                                    <select id="ctgf_email_field" name="ctgf_email_field" class="gform-settings-input__container">
                                        <option value="">Select Email Field</option>
                                        <?php echo $this->get_email_field_options($form, $email_field); ?>
                                    </select>
                                    <span class="gform-settings-description">
                                        Select the field that contains the user's email address.
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ctgf_tag">CleverTap Tag</label>
                                </th>
                                <td>
                                    <input type="text" id="ctgf_tag" name="ctgf_tag" value="<?php echo esc_attr($tag); ?>" class="gform-settings-input__container" />
                                    <span class="gform-settings-description">
                                        The tag to add to the user in CleverTap (e.g., "Newsletter Signup", "Contact Form").
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="gform-settings-save-container">
                        <button type="submit" class="button button-primary gfbutton">
                            <?php esc_html_e('Update Settings'); ?>
                        </button>
                    </div>
                </form>
                
                <?php if ($config && $active): ?>
                    <div class="gform-settings-panel__content" style="margin-top: 20px;">
                        <h4>Current Configuration</h4>
                        <table class="gforms_form_settings" cellspacing="0" cellpadding="0">
                            <tr>
                                <th scope="row">Status</th>
                                <td>
                                    <span class="ctgf-status-indicator ctgf-status-active"></span>
                                    Active
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Email Field</th>
                                <td>
                                    <?php 
                                    $field_label = $this->get_field_label($form, $email_field);
                                    echo 'Field ' . esc_html($email_field) . ($field_label ? ' - ' . esc_html($field_label) : '');
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Tag</th>
                                <td><?php echo esc_html($tag); ?></td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .ctgf-config-fields {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .gform-settings-description {
            display: block;
            margin-top: 5px;
            font-style: italic;
            color: #666;
        }
        .gform-settings-save-container {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .ctgf-status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .ctgf-status-active {
            background-color: #46b450;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#ctgf_active').change(function() {
                if ($(this).is(':checked')) {
                    $('.ctgf-config-fields').slideDown();
                } else {
                    $('.ctgf-config-fields').slideUp();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Get email field options for the dropdown
     */
    private function get_email_field_options($form, $selected_field) {
        $options = '';
        
        // First, add email fields
        foreach ($form['fields'] as $field) {
            if ($field->type === 'email' || $field->inputType === 'email') {
                $selected = selected($selected_field, $field->id, false);
                $options .= '<option value="' . $field->id . '"' . $selected . '>Field ' . $field->id . ' - ' . esc_html($field->label) . ' (Email)</option>';
            }
        }
        
        // Also include text fields in case email is in a text field
        foreach ($form['fields'] as $field) {
            if ($field->type === 'text' || $field->type === 'hidden') {
                $selected = selected($selected_field, $field->id, false);
                $options .= '<option value="' . $field->id . '"' . $selected . '>Field ' . $field->id . ' - ' . esc_html($field->label) . ' (Text)</option>';
            }
        }
        
        return $options;
    }
    
    /**
     * Get field label by ID
     */
    private function get_field_label($form, $field_id) {
        foreach ($form['fields'] as $field) {
            if ($field->id == $field_id) {
                return $field->label;
            }
        }
        return '';
    }
    
    /**
     * Save form settings immediately (called during page load)
     */
    private function save_form_settings_immediate($form_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        
        $active = isset($_POST['ctgf_active']) ? 1 : 0;
        $email_field = sanitize_text_field($_POST['ctgf_email_field'] ?? '');
        $tag = sanitize_text_field($_POST['ctgf_tag'] ?? '');
        
        // Check if config exists
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE form_id = %d", $form_id));
        
        if ($existing) {
            // Update existing config
            $result = $wpdb->update(
                $table_name,
                array(
                    'email_field' => $email_field,
                    'tag' => $tag,
                    'active' => $active
                ),
                array('form_id' => $form_id),
                array('%s', '%s', '%d'),
                array('%d')
            );
        } else {
            // Insert new config
            $result = $wpdb->insert(
                $table_name,
                array(
                    'form_id' => $form_id,
                    'email_field' => $email_field,
                    'tag' => $tag,
                    'active' => $active
                ),
                array('%d', '%s', '%s', '%d')
            );
        }
        
        if ($result !== false) {
            // Add success message
            echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0;"><p><strong>Success!</strong> CleverTap settings saved successfully!</p></div>';
        } else {
            // Add error message  
            echo '<div class="notice notice-error is-dismissible" style="margin: 20px 0;"><p><strong>Error:</strong> Failed to save CleverTap settings. Please try again.</p></div>';
        }
    }
    
    /**
     * Save form settings
     */
    public function save_form_settings($form) {
        // This method is kept for compatibility but the actual saving is now handled in save_form_settings_immediate
        return $form;
    }
}