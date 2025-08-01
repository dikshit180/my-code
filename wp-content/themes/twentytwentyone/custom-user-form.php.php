<?php
/*
Template Name: user form
*/
get_header();
?>

<form id="ajax-register-form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="ajax_register_user">

    <label for="first_name">First Name:</label><br>
    <input type="text" name="first_name" required><br><br>

    <label for="last_name">Last Name:</label><br>
    <input type="text" name="last_name" required><br><br>

    <label for="email">Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label for="password">Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label for="confirm_password">Confirm Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <label for="phone">Phone Number:</label><br>
    <input type="tel" name="phone" required><br><br>

    <label for="image">Upload Image:</label><br>
    <input type="file" name="image" accept="image/*" required><br><br>

    <input type="submit" value="Register">
    <div id="form-message"></div>
</form>

<script>
jQuery(document).ready(function() {
    jQuery('#ajax-register-form').on('submit', function(e) {
        e.preventDefault();
		var phone = jQuery('input[name="phone"]').val().trim();
var phonePattern = /^\d{10}$/; // Any 10 digits

if (!phonePattern.test(phone)) {
    jQuery('#form-message').html('<p style="color:red;">Please enter a valid 10-digit phone number.</p>');
    return;
}
        var formData = new FormData(this);
         console.log('test-working');
        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                jQuery('#form-message').html('<p style="color:green;">' + response + '</p>');
            },
            error: function() {
                jQuery('#form-message').html('<p style="color:red;">An error occurred. Please try again.</p>');
            }
        });
    });
});
</script>

<?php get_footer(); ?>
