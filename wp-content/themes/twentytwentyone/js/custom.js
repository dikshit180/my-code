jQuery(document).ready(function ($) {
    $('#form-hotel').on('submit', function(e) {
         e.preventDefault();
       // console.log('working');
	   var formData = new FormData(this);
	   $.ajax({
		   url:my_ajax_object.ajax_url,
		   type:'POST',
		   data:formData,
		  contentType:false,
		  processData:false,
		  beforeSend: function() {
			  $('.submit-hotel').html('Submiting...');
		
			
		 },
		 success: function (response) {
                if (response.success) {
                    $('.submit-hotel').html('<span style="color:green;">' + response.data + '</span>');
                    $('#form-hotel')[0].reset();
                } else {
                    $('.submit-hotel').html('<span style="color:red;">' + response.data + '</span>');
                }
            },
            error: function () {
                $('.submit-hotel').html('<span style="color:red;">An error occurred.</span>');
            },
         
        });
    });
});