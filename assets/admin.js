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
    
    // Form submission validation
    $('form').submit(function(e) {
        if ($('#ctgf_active').is(':checked')) {
            var emailField = $('#ctgf_email_field').val();
            var tag = $('#ctgf_tag').val();
            var eventName = $('#ctgf_event_name').val();
            
            if (!emailField || !tag || !eventName) {
                e.preventDefault();
                alert('Please select an email field, enter a tag, and specify an event name when CleverTap integration is enabled.');
                return false;
            }
        }
    });
});