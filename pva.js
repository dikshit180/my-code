jQuery(document).ready(function ($) {
    $('#create_post').click(function (event) {
        event.preventDefault(); // Prevent default form submission
        post_via_ajax();
    });

    function post_via_ajax() {
        var pva_ajax_url = pva_params.pva_ajax_url;

        // Get form data
        var newPostTitleValue = $('#post_title').val();
        var fileData = $('#post_featured_image')[0].files[0]; // Get the file

        // Prepare form data
        var formData = new FormData();
        formData.append('action', 'pva_create');
        formData.append('post_title', newPostTitleValue);
        formData.append('post_featured_image', fileData);
        $('#loading').show();
		$('#success').hide();
		$('#message').text('');
        $.ajax({
            type: 'POST',
            url: pva_ajax_url,
            data: formData,
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            beforeSend: function () {
                console.log('Sending...');
            },
            success: function (response) {
                //console.log('Success:', response);
				$('#loading').hide();
				
				if (response.success) {
                    console.log('Success:', response.data);
                   $('#message').css('color', 'green').text(response.data);
					$('#success').show();
                } else {
                    console.log('Error:', response.data);
                    $('#message').css('color', 'red').text(response.data);
                }
            
				
            },
          error: function () {
                $('#loading').hide();
                console.log('Error occurred.');
                $('#message').text('An error occurred while processing your request.');
            },
        });
    }
});
