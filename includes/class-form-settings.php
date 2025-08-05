<?php
/**
 * Form Settings Integration - Modern Gravity Forms Approach
 */

if (!defined('ABSPATH')) {
    exit;
}

class CTGF_Form_Settings {
    
    public function __construct() {
        // Only initialize if we're in admin
        if (!is_admin()) {
            return;
        }
        
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
        
        if (!$form_id || !is_numeric($form_id)) {
            wp_die('Form ID is required');
        }
        
        $form = GFAPI::get_form($form_id);
        
        if (!$form || !is_array($form)) {
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
        $event_name = $config ? $config->event_name : 'Newsletter Signup';
        $active = $config ? $config->active : 0;
        
        // Get property mappings
        $property_mappings = $config && !empty($config->property_mappings) ? 
            json_decode($config->property_mappings, true) : array();
        
        // Get event mappings
        $event_mappings = $config && !empty($config->event_mappings) ? 
            json_decode($config->event_mappings, true) : array();
        
        // Check if global settings are configured
        $account_id = get_option('ctgf_account_id');
        $passcode = get_option('ctgf_passcode');
        $global_configured = !empty($account_id) && !empty($passcode);
        
        ?>
        <div class="gform-settings-panel">
            <header class="gform-settings-panel__header">
                <h3 class="gform-settings-panel__title">CleverTap Integration Settings</h3>
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
                                    When enabled, form submissions will be sent to CleverTap with the specified configuration.
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
                                        Select the field that contains the user's email address (used as CleverTap identity).
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
                                        The tag to add to the user in CleverTap (e.g., "Anxiety", "Retreat2025").
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="ctgf_event_name">Event Name</label>
                                </th>
                                <td>
                                    <input type="text" id="ctgf_event_name" name="ctgf_event_name" value="<?php echo esc_attr($event_name); ?>" class="gform-settings-input__container" />
                                    <span class="gform-settings-description">
                                        The event name to send to CleverTap (e.g., "Newsletter Signup", "Contact Form Submission").
                                    </span>
                                </td>
                            </tr>
                        </table>
                        
                        <h3 style="margin-top: 30px; margin-bottom: 15px;">Property Mappings</h3>
                        <p class="gform-settings-description" style="margin-bottom: 20px;">
                            Map form fields to CleverTap profile properties. Each property will be sent to CleverTap when the form is submitted.
                        </p>
                        
                        <div id="ctgf-property-mappings">
                            <?php if (!empty($property_mappings)): ?>
                                <?php foreach ($property_mappings as $index => $mapping): ?>
                                    <div class="ctgf-property-mapping" data-index="<?php echo $index; ?>">
                                        <table class="gforms_form_settings" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label>Property Name</label>
                                                </th>
                                                <td style="width: 250px;">
                                                    <input type="text" 
                                                           name="ctgf_property_mappings[<?php echo $index; ?>][property_name]" 
                                                           value="<?php echo esc_attr($mapping['property_name']); ?>" 
                                                           class="gform-settings-input__container ctgf-property-name" 
                                                           placeholder="e.g., Phone, Company, Source" />
                                                </td>
                                                <th scope="row" style="width: 150px;">
                                                    <label>Form Field</label>
                                                </th>
                                                <td style="width: 250px;">
                                                    <select name="ctgf_property_mappings[<?php echo $index; ?>][form_field]" 
                                                            class="gform-settings-input__container ctgf-form-field">
                                                        <option value="">Select Field</option>
                                                        <?php echo $this->get_all_field_options($form, $mapping['form_field']); ?>
                                                    </select>
                                                </td>
                                                <td style="width: 100px;">
                                                    <button type="button" class="button ctgf-remove-mapping" style="color: #dc3232;">Remove</button>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ctgf-add-mapping-button-container">
                            <button type="button" id="ctgf-add-mapping" class="button">Add Property Mapping</button>
                        </div>
                        
                        <h3 style="margin-top: 30px; margin-bottom: 15px;">Event Data Mappings</h3>
                        <p class="gform-settings-description" style="margin-bottom: 20px;">
                            Map form fields to custom event data that will be sent with the CleverTap event. This allows you to include additional context with your events.
                        </p>
                        
                        <div id="ctgf-event-mappings">
                            <?php if (!empty($event_mappings)): ?>
                                <?php foreach ($event_mappings as $index => $mapping): ?>
                                    <div class="ctgf-event-mapping" data-index="<?php echo $index; ?>">
                                        <table class="gforms_form_settings" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label>Event Data Key</label>
                                                </th>
                                                <td style="width: 250px;">
                                                    <input type="text" 
                                                           name="ctgf_event_mappings[<?php echo $index; ?>][event_key]" 
                                                           value="<?php echo esc_attr($mapping['event_key']); ?>" 
                                                           class="gform-settings-input__container ctgf-event-key" 
                                                           placeholder="e.g., lead_source, campaign, referrer" />
                                                </td>
                                                <th scope="row" style="width: 150px;">
                                                    <label>Value</label>
                                                </th>
                                                <td style="width: 250px;">
                                                    <input type="text" 
                                                           name="ctgf_event_mappings[<?php echo $index; ?>][event_value]" 
                                                           value="<?php echo esc_attr($mapping['event_value']); ?>" 
                                                           class="gform-settings-input__container ctgf-event-value" 
                                                           placeholder="e.g., Google Ads, Newsletter, Facebook" />
                                                </td>
                                                <td style="width: 100px;">
                                                    <button type="button" class="button ctgf-remove-event-mapping" style="color: #dc3232;">Remove</button>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ctgf-add-mapping-button-container">
                            <button type="button" id="ctgf-add-event-mapping" class="button">Add Event Data Mapping</button>
                        </div>
                    </div>
                    
                    <div class="gform-settings-save-container">
                        <button type="submit" class="button button-primary gfbutton">
                            <?php esc_html_e('Update Settings'); ?>
                        </button>
                    </div>
                </form>
                
                <?php if ($config && $active): ?>
                    <div class="gform-settings-panel__content ctgf-current-config" style="margin-top: 20px;">
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
                            <tr>
                                <th scope="row">Event Name</th>
                                <td><?php echo esc_html($event_name); ?></td>
                            </tr>
                            <?php if (!empty($property_mappings)): ?>
                                <tr>
                                    <th scope="row">Property Mappings</th>
                                    <td>
                                        <?php foreach ($property_mappings as $mapping): ?>
                                            <div class="ctgf-mapping-summary-item">
                                                <strong><?php echo esc_html($mapping['property_name']); ?>:</strong> 
                                                Field <?php echo esc_html($mapping['form_field']); ?>
                                                <?php 
                                                $field_label = $this->get_field_label($form, $mapping['form_field']);
                                                if ($field_label) echo ' - ' . esc_html($field_label);
                                                ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($event_mappings)): ?>
                                <tr>
                                    <th scope="row">Event Data Mappings</th>
                                    <td>
                                        <?php foreach ($event_mappings as $mapping): ?>
                                            <div class="ctgf-mapping-summary-item">
                                                <strong><?php echo esc_html($mapping['event_key']); ?>:</strong> 
                                                <?php echo esc_html($mapping['event_value']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Templates for dynamic mappings -->
        <div id="ctgf-property-mapping-template" style="display: none;">
            <div class="ctgf-property-mapping" data-index="__INDEX__">
                <div class="ctgf-mapping-row">
                    <div class="ctgf-mapping-field">
                        <label>Property Name</label>
                        <input type="text" 
                               name="ctgf_property_mappings[__INDEX__][property_name]" 
                               value="" 
                               class="gform-settings-input__container ctgf-property-name" 
                               placeholder="e.g., Phone, Company, Source" />
                    </div>
                    <div class="ctgf-mapping-field">
                        <label>Form Field</label>
                        <select name="ctgf_property_mappings[__INDEX__][form_field]" 
                                class="gform-settings-input__container ctgf-form-field">
                            <option value="">Select Field</option>
                            <?php echo $this->get_all_field_options($form, ''); ?>
                        </select>
                    </div>
                    <div class="ctgf-mapping-actions">
                        <button type="button" class="button ctgf-remove-mapping">Remove</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="ctgf-event-mapping-template" style="display: none;">
            <div class="ctgf-event-mapping" data-index="__INDEX__">
                <div class="ctgf-mapping-row">
                    <div class="ctgf-mapping-field">
                        <label>Event Data Key</label>
                        <input type="text" 
                               name="ctgf_event_mappings[__INDEX__][event_key]" 
                               value="" 
                               class="gform-settings-input__container ctgf-event-key" 
                               placeholder="e.g., lead_source, campaign, referrer" />
                    </div>
                    <div class="ctgf-mapping-field">
                        <label>Value</label>
                        <input type="text" 
                               name="ctgf_event_mappings[__INDEX__][event_value]" 
                               value="" 
                               class="gform-settings-input__container ctgf-event-value" 
                               placeholder="e.g., Google Ads, Newsletter, Facebook" />
                    </div>
                    <div class="ctgf-mapping-actions">
                        <button type="button" class="button ctgf-remove-event-mapping">Remove</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Initialize form settings with mapping indices
        var ctgfForm = {
            mappingIndex: <?php echo !empty($property_mappings) ? max(array_keys($property_mappings)) + 1 : 0; ?>,
            eventMappingIndex: <?php echo !empty($event_mappings) ? max(array_keys($event_mappings)) + 1 : 0; ?>
        };
        </script>
        <?php
    }
    
    /**
     * Get email field options for the dropdown
     */
    private function get_email_field_options($form, $selected_field) {
        $options = '';
        
        if (!is_array($form) || !isset($form['fields']) || !is_array($form['fields'])) {
            return $options;
        }
        
        // First, add email fields
        foreach ($form['fields'] as $field) {
            if (isset($field->type) && isset($field->id) && isset($field->label) && 
                ($field->type === 'email' || (isset($field->inputType) && $field->inputType === 'email'))) {
                $selected = selected($selected_field, $field->id, false);
                $options .= '<option value="' . $field->id . '"' . $selected . '>Field ' . $field->id . ' - ' . esc_html($field->label) . ' (Email)</option>';
            }
        }
        
        // Also include text fields in case email is in a text field
        foreach ($form['fields'] as $field) {
            if (isset($field->type) && isset($field->id) && isset($field->label) && 
                ($field->type === 'text' || $field->type === 'hidden')) {
                $selected = selected($selected_field, $field->id, false);
                $options .= '<option value="' . $field->id . '"' . $selected . '>Field ' . $field->id . ' - ' . esc_html($field->label) . ' (Text)</option>';
            }
        }
        
        return $options;
    }
    
    /**
     * Get all field options for property mapping
     */
    private function get_all_field_options($form, $selected_field) {
        $options = '';
        
        if (!is_array($form) || !isset($form['fields']) || !is_array($form['fields'])) {
            return $options;
        }
        
        foreach ($form['fields'] as $field) {
            // Skip fields that don't make sense for CleverTap
            if (!isset($field->type) || !isset($field->id) || !isset($field->label) || 
                in_array($field->type, array('section', 'page', 'html', 'captcha', 'fileupload'))) {
                continue;
            }
            
            $selected = selected($selected_field, $field->id, false);
            $field_type = ucfirst($field->type);
            $options .= '<option value="' . $field->id . '"' . $selected . '>Field ' . $field->id . ' - ' . esc_html($field->label) . ' (' . $field_type . ')</option>';
        }
        
        return $options;
    }
    
    /**
     * Get field label by ID
     */
    private function get_field_label($form, $field_id) {
        if (!is_array($form) || !isset($form['fields']) || !is_array($form['fields'])) {
            return '';
        }
        
        foreach ($form['fields'] as $field) {
            if (isset($field->id) && $field->id == $field_id && isset($field->label)) {
                return $field->label;
            }
        }
        return '';
    }
    
    /**
     * Save form settings immediately (called during page load)
     */
    private function save_form_settings_immediate($form_id) {
        if (!is_numeric($form_id) || $form_id <= 0) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ctgf_form_configs';
        
        $active = isset($_POST['ctgf_active']) ? 1 : 0;
        $email_field = sanitize_text_field($_POST['ctgf_email_field'] ?? '');
        $tag = sanitize_text_field($_POST['ctgf_tag'] ?? '');
        $event_name = sanitize_text_field($_POST['ctgf_event_name'] ?? 'Newsletter Signup');
        
        // Validate required fields when active
        if ($active && (empty($email_field) || empty($event_name))) {
            echo '<div class="notice notice-error is-dismissible" style="margin: 20px 0;"><p><strong>Error:</strong> Email field and event name are required when integration is enabled.</p></div>';
            return;
        }
        
        // Process property mappings
        $property_mappings = array();
        if (isset($_POST['ctgf_property_mappings']) && is_array($_POST['ctgf_property_mappings'])) {
            foreach ($_POST['ctgf_property_mappings'] as $mapping) {
                $property_name = sanitize_text_field($mapping['property_name'] ?? '');
                $form_field = sanitize_text_field($mapping['form_field'] ?? '');
                
                if (!empty($property_name) && !empty($form_field)) {
                    $property_mappings[] = array(
                        'property_name' => $property_name,
                        'form_field' => $form_field
                    );
                }
            }
        }
        
        $property_mappings_json = json_encode($property_mappings);
        
        // Process event mappings
        $event_mappings = array();
        if (isset($_POST['ctgf_event_mappings']) && is_array($_POST['ctgf_event_mappings'])) {
            foreach ($_POST['ctgf_event_mappings'] as $mapping) {
                $event_key = sanitize_text_field($mapping['event_key'] ?? '');
                $event_value = sanitize_text_field($mapping['event_value'] ?? '');
                
                if (!empty($event_key) && !empty($event_value)) {
                    $event_mappings[] = array(
                        'event_key' => $event_key,
                        'event_value' => $event_value
                    );
                }
            }
        }
        
        $event_mappings_json = json_encode($event_mappings);
        
        // Check if config exists
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE form_id = %d", $form_id));
        
        if ($existing) {
            // Update existing config
            $result = $wpdb->update(
                $table_name,
                array(
                    'email_field' => $email_field,
                    'tag' => $tag,
                    'event_name' => $event_name,
                    'property_mappings' => $property_mappings_json,
                    'event_mappings' => $event_mappings_json,
                    'active' => $active
                ),
                array('form_id' => $form_id),
                array('%s', '%s', '%s', '%s', '%s', '%d'),
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
                    'event_name' => $event_name,
                    'property_mappings' => $property_mappings_json,
                    'event_mappings' => $event_mappings_json,
                    'active' => $active
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%d')
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