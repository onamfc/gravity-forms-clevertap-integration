<?php
/**
 * Form Settings Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTGF_Form_Settings {
    
    public function __construct() {
        add_action('gform_form_settings', array($this, 'add_form_settings'), 10, 2);
        add_action('gform_pre_form_settings_save', array($this, 'save_form_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_form_settings_scripts'));
        add_action('wp_ajax_ctgf_get_form_fields', array($this, 'get_form_fields'));
    }
    
    public function enqueue_form_settings_scripts($hook) {
        // Check if we're on the Gravity Forms edit form page
        if ($hook === 'forms_page_gf_edit_forms' || strpos($hook, 'gf_edit_forms') !== false) {
            wp_enqueue_script('ctgf-admin-js', CTGF_PLUGIN_URL . 'assets/admin.js', array('jquery'), CTGF_VERSION, true);
            wp_enqueue_style('ctgf-admin-css', CTGF_PLUGIN_URL . 'assets/admin.css', array(), CTGF_VERSION);
        }
    }
    
    public function add_form_settings($settings, $form) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        $config = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE form_id = %d", $form['id']));
        
        $email_field = $config ? $config->email_field : '';
        $tag = $config ? $config->tag : '';
        $active = $config ? $config->active : 0;
        
        $settings['CleverTap Integration'] = '
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ctgf_active">Enable CleverTap Integration</label>
                </th>
                <td>
                    <input type="checkbox" id="ctgf_active" name="ctgf_active" value="1" ' . checked($active, 1, false) . ' />
                    <label for="ctgf_active">Enable CleverTap integration for this form</label>
                </td>
            </tr>
        </table>
        <div class="ctgf-config-fields">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ctgf_email_field">Email Field</label>
                    </th>
                    <td>
                        <select id="ctgf_email_field" name="ctgf_email_field">
                            <option value="">Select Email Field</option>
                            ' . $this->get_email_field_options($form, $email_field) . '
                        </select>
                        <p class="description">Select the field that contains the user\'s email address</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="ctgf_tag">CleverTap Tag</label>
                    </th>
                    <td>
                        <input type="text" id="ctgf_tag" name="ctgf_tag" value="' . esc_attr($tag) . '" class="regular-text" />
                        <p class="description">The tag to add to the user in CleverTap (e.g., "Newsletter Signup")</p>
                    </td>
                </tr>
            </table>
        </div>
        <table class="form-table">
        </table>';
        
        return $settings;
    }
    
    private function get_email_field_options($form, $selected_field) {
        $options = '';
        
        foreach ($form['fields'] as $field) {
            if ($field->type === 'email' || $field->inputType === 'email') {
                $selected = selected($selected_field, $field->id, false);
                $options .= '<option value="' . $field->id . '"' . $selected . '>Field ' . $field->id . ' - ' . $field->label . '</option>';
            }
        }
        
        // Also include text fields in case email is in a text field
        foreach ($form['fields'] as $field) {
            if ($field->type === 'text' || $field->type === 'hidden') {
                $selected = selected($selected_field, $field->id, false);
                $options .= '<option value="' . $field->id . '"' . $selected . '>Field ' . $field->id . ' - ' . $field->label . ' (Text)</option>';
            }
        }
        
        return $options;
    }
    
    public function save_form_settings($form) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        $form_id = $form['id'];
        
        $active = isset($_POST['ctgf_active']) ? 1 : 0;
        $email_field = sanitize_text_field($_POST['ctgf_email_field'] ?? '');
        $tag = sanitize_text_field($_POST['ctgf_tag'] ?? '');
        
        // Check if config exists
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE form_id = %d", $form_id));
        
        if ($existing) {
            // Update existing config
            $wpdb->update(
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
            $wpdb->insert(
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
    }
}