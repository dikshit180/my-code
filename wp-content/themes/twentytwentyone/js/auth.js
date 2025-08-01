jQuery(document).ready(function ($) {
    // Signup Form
    $('#signup-form').on('submit', function (e) {
        e.preventDefault();

        const username = $('input[name="username"]').val();
        const email = $('input[name="email"]').val();
        const password = $('input[name="password"]').val();

        $.ajax({
            url: auth_ajax_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'custom_signup',
                nonce: auth_ajax_obj.nonce,
                username: username,
                email: email,
                password: password
            },
            success: function (response) {
                if (response.success) {
                    $('#signup-response').html(response.data);
                } else {
                    $('#signup-response').html('❌ ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                $('#signup-response').html('❌ An error occurred.');
                console.error('Signup AJAX error:', error);
            }
        });
    });

    // Login Form
    $('#login-form').on('submit', function (e) {
        e.preventDefault();

        const username = $('input[name="username"]').val();
        const password = $('input[name="password"]').val();

        $.ajax({
            url: auth_ajax_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'custom_login',
                nonce: auth_ajax_obj.nonce,
                username: username,
                password: password
            },
            success: function (response) {
                if (response.success) {
                    $('#login-response').html(response.data);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    $('#login-response').html('❌ ' + response.data);
                }
            },
            error: function (xhr, status, error) {
                $('#login-response').html('❌ An error occurred.');
                console.error('Login AJAX error:', error);
            }
        });
    });
});
