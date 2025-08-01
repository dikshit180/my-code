jQuery(document).ready(function ($) {
    $('#toy-form').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);

        $.ajax({
            url: toy_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function () {
                $('#toy-response').html('Submitting...');
            },
            success: function (response) {
                if (response.success) {
                    $('#toy-response').html('<span style="color:green;">' + response.data + '</span>');
                    $('#toy-form')[0].reset();
                } else {
                    $('#toy-response').html('<span style="color:red;">' + response.data + '</span>');
                }
            },
            error: function () {
                $('#toy-response').html('<span style="color:red;">An error occurred.</span>');
            }
        });
    });
});
