jQuery(document).ready(function($) {
    // Toggle API key visibility
    $('#toggle_api_key').on('click', function() {
        const $input = $('#duffel_api_key');
        const $icon = $(this).find('.dashicons');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
        }
    });

    // Test API connection
    $('#test_api_connection').on('click', function() {
        const $button = $(this);
        const $spinner = $('#test_api_spinner');
        const $result = $('#test_api_result');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.empty().removeClass('success error');

        $.ajax({
            url: duffel_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'duffel_test_api_connection',
                nonce: duffel_admin.nonce
            },
            success: function(response) {
                $result.text(response.data.message)
                    .addClass(response.success ? 'success' : 'error');
            },
            error: function() {
                $result.text('Error processing request').addClass('error');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});