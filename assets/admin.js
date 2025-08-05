/* Admin JavaScript for CleverTap Gravity Forms Integration */

// Global CTGF Admin object
var CTGF_Admin = {
    
    // Initialize admin settings page functionality
    initAdminSettingsPage: function() {
        var $ = jQuery;
        
        $('#ctgf-test-connection').click(function() {
            var button = $(this);
            var result = $('#ctgf-test-result');
            
            button.prop('disabled', true).text('Testing...');
            result.html('');
            
            $.ajax({
                url: ctgfAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ctgf_test_connection',
                    nonce: ctgfAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        result.html('<div class="notice notice-success"><p>Connection successful!</p></div>');
                    } else {
                        result.html('<div class="notice notice-error"><p>Connection failed: ' + response.data + '</p></div>');
                    }
                },
                error: function() {
                    result.html('<div class="notice notice-error"><p>Request failed</p></div>');
                },
                complete: function() {
                    button.prop('disabled', false).text('Test Connection');
                }
            });
        });
    },
    
    // Initialize form settings page functionality
    initFormSettingsPage: function(initialMappingIndex, initialEventMappingIndex) {
        var $ = jQuery;
        var mappingIndex = initialMappingIndex || 0;
        var eventMappingIndex = initialEventMappingIndex || 0;
        
        // Toggle config fields visibility
        $('#ctgf_active').change(function() {
            if ($(this).is(':checked')) {
                $('.ctgf-config-fields').slideDown();
            } else {
                $('.ctgf-config-fields').slideUp();
            }
        });
        
        // Add new property mapping
        $('#ctgf-add-mapping').click(function() {
            var template = $('#ctgf-property-mapping-template').html();
            var newMapping = template.replace(/__INDEX__/g, mappingIndex);
            $('#ctgf-property-mappings').append(newMapping);
            mappingIndex++;
        });
        
        // Remove property mapping
        $(document).on('click', '.ctgf-remove-mapping', function() {
            $(this).closest('.ctgf-property-mapping').fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Add new event mapping
        $('#ctgf-add-event-mapping').click(function() {
            var template = $('#ctgf-event-mapping-template').html();
            var newMapping = template.replace(/__INDEX__/g, eventMappingIndex);
            $('#ctgf-event-mappings').append(newMapping);
            eventMappingIndex++;
        });
        
        // Remove event mapping
        $(document).on('click', '.ctgf-remove-event-mapping', function() {
            $(this).closest('.ctgf-event-mapping').fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Input validation - remove error class on input
        $(document).on('input', '.ctgf-property-name, .ctgf-event-key', function() {
            if ($(this).val().length > 0) {
                $(this).removeClass('error');
            }
        });
        
        $(document).on('input', '.ctgf-event-value', function() {
            if ($(this).val().length > 0) {
                $(this).removeClass('error');
            }
        });
        
        $(document).on('change', '.ctgf-form-field', function() {
            if ($(this).val().length > 0) {
                $(this).removeClass('error');
            }
        });
        
        // Form submission validation
        $('form').submit(function(e) {
            if ($('#ctgf_active').is(':checked')) {
                var emailField = $('#ctgf_email_field').val();
                var eventName = $('#ctgf_event_name').val();
                var hasValidMappings = false;
                var hasErrors = false;
                
                // Check required fields
                if (!emailField || !eventName) {
                    e.preventDefault();
                    alert('Please select an email field and specify an event name when CleverTap integration is enabled.');
                    return false;
                }
                
                // Check if we have at least one valid property mapping or a tag
                var tag = $('#ctgf_tag').val();
                if (tag) {
                    hasValidMappings = true;
                }
                
                // Validate property mappings
                $('.ctgf-property-mapping').each(function() {
                    var propertyName = $(this).find('.ctgf-property-name').val();
                    var formField = $(this).find('.ctgf-form-field').val();
                    
                    if (propertyName && formField) {
                        hasValidMappings = true;
                    } else if (propertyName || formField) {
                        // Incomplete mapping
                        hasErrors = true;
                        if (!propertyName) {
                            $(this).find('.ctgf-property-name').addClass('error');
                        }
                        if (!formField) {
                            $(this).find('.ctgf-form-field').addClass('error');
                        }
                    }
                });
                
                // Validate event mappings
                $('.ctgf-event-mapping').each(function() {
                    var eventKey = $(this).find('.ctgf-event-key').val();
                    var eventValue = $(this).find('.ctgf-event-value').val();
                    
                    if (eventKey || eventValue) {
                        // If either field has a value, both must be filled
                        if (!eventKey || !eventValue) {
                            hasErrors = true;
                            if (!eventKey) {
                                $(this).find('.ctgf-event-key').addClass('error');
                            }
                            if (!eventValue) {
                                $(this).find('.ctgf-event-value').addClass('error');
                            }
                        }
                    }
                });
                
                if (hasErrors) {
                    e.preventDefault();
                    alert('Please complete all property and event mappings or remove incomplete ones.');
                    return false;
                }
                
                if (!hasValidMappings) {
                    e.preventDefault();
                    alert('Please either enter a tag or add at least one property mapping when CleverTap integration is enabled.');
                    return false;
                }
            }
        });
    }
};

// Initialize appropriate functionality based on page context
jQuery(document).ready(function($) {
    // Check if we're on the admin settings page
    if (typeof ctgfAdmin !== 'undefined') {
        CTGF_Admin.initAdminSettingsPage();
    }
    
    // Check if we're on the form settings page
    if (typeof ctgfForm !== 'undefined') {
        CTGF_Admin.initFormSettingsPage(ctgfForm.mappingIndex, ctgfForm.eventMappingIndex);
    }
});