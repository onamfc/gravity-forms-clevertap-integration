/* Admin JavaScript for CleverTap Gravity Forms Integration */

jQuery(document).ready(function($) {
    // Form settings page functionality
    $('#ctgf_active').change(function() {
        if ($(this).is(':checked')) {
            $('.ctgf-config-fields').show();
        } else {
            $('.ctgf-config-fields').hide();
        }
    });
    
    // Initialize form settings visibility
    if (!$('#ctgf_active').is(':checked')) {
        $('.ctgf-config-fields').hide();
    }
    
    // Email field validation
    $('#ctgf_email_field').change(function() {
        var fieldId = $(this).val();
        if (fieldId) {
            // You could add AJAX validation here if needed
            console.log('Selected email field: ' + fieldId);
        }
    });
    
    // Tag input validation
    $('#ctgf_tag').on('input', function() {
        var tag = $(this).val();
        if (tag.length > 0) {
            $(this).removeClass('error');
        }
    });
    
    // Event name input validation
    $('#ctgf_event_name').on('input', function() {
        var eventName = $(this).val();
        if (eventName.length > 0) {
            $(this).removeClass('error');
        }
    });
    
    // Property mapping validation
    $(document).on('input', '.ctgf-property-name', function() {
        var propertyName = $(this).val();
        if (propertyName.length > 0) {
            $(this).removeClass('error');
        }
    });
    
    $(document).on('change', '.ctgf-form-field', function() {
        var formField = $(this).val();
        if (formField.length > 0) {
            $(this).removeClass('error');
        }
    });
    
    // Event mapping validation
    $(document).on('input', '.ctgf-event-key', function() {
        var eventKey = $(this).val();
        if (eventKey.length > 0) {
            $(this).removeClass('error');
        }
    });
    
    $(document).on('change', '.ctgf-event-form-field', function() {
        var formField = $(this).val();
        if (formField.length > 0) {
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
            
            // Check event mappings for completeness
            $('.ctgf-event-mapping').each(function() {
                var eventKey = $(this).find('.ctgf-event-key').val();
                var formField = $(this).find('.ctgf-event-form-field').val();
                
                if (eventKey || formField) {
                    // If either field has a value, both must be filled
                    if (!eventKey || !formField) {
                        hasErrors = true;
                        if (!eventKey) {
                            $(this).find('.ctgf-event-key').addClass('error');
                        }
                        if (!formField) {
                            $(this).find('.ctgf-event-form-field').addClass('error');
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
});