jQuery(document).ready(function ($) {
    let imageId = ''; // Store the selected image ID.

    // Open WordPress Media Library on button click.
    $('#upload-image').click(function (e) {
        e.preventDefault();

        let frame = wp.media({
            title: 'Select or Upload Image',
            button: {
                text: 'Use this image',
            },
            multiple: false,
        });

        frame.on('select', function () {
            let attachment = frame.state().get('selection').first().toJSON();
            imageId = attachment.id; // Store the image ID.
            $('#image-preview').attr('src', attachment.url).show(); // Show image preview.
        });

        frame.open();
    });

    // Submit form with AJAX.
    $('#hotel-form').on('submit', function (e) {
        e.preventDefault();

        const title = $('#title').val();
        const description = $('#description').val();

        $.ajax({
            url: hotelApi.apiUrl,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                title: title,
                description: description,
                image_id: imageId, // Include image ID in the request.
            }),
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', hotelApi.nonce);
				$('#response-message').html('<p style="color: blue;">Loading...</p>');
            },
            success: function (response) {
                $('#response-message').html('<p style="color: green;">Hotel created successfully!</p>');
                $('#hotel-form')[0].reset();
                $('#image-preview').hide(); // Hide the image preview.
            },
            error: function (error) {
                $('#response-message').html('<p style="color: red;">Failed to create hotel. Please try again.</p>');
            },
        });
    });
});

