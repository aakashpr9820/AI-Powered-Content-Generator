/**************
* JavaScript code for plugin itself
* Author: Aakash Sharma
****************/

jQuery(document).ready(function ($) {
	
    $('.settings_admin #openaiDiv').hide();

    document.querySelectorAll('input[name="api_type"]').forEach((radio) => {
        radio.addEventListener("change", function () {
            document.getElementById("geminiDiv").style.display = this.value === "gemini_radio_btn" ? "block" : "none";
            document.getElementById("openaiDiv").style.display = this.value === "openai_radio_btn" ? "block" : "none";
        });
    });
    

	/***************
	* GENERATE BLOG
	****************/
    $('#generate-content').on('click', function () {
        const keywords = $('#input-keywords').val();

        if (!keywords) {
            alert('Please enter your blog content prompt.');
            return;
        }
        // alert(keywords);
        // return false;
        // Display a loading message 
        $('#content-preview').hide(); 
        
		$('.loading').css('display','block');

        // AJAX request to generate content
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'generate_blog_content',
                keywords: keywords,
				_ajax_nonce: ajax_object.nonce, // Include nonce
            },
            success: function (response) {
                if (response.success) {
					$('#post_title').val(response.data.title);
					$('.template_one h2').text(response.data.title);
					
					if (tinymce.get('generated-content')) {
						tinymce.get('generated-content').setContent(response.data.content);
						tinymce.triggerSave();
					} else {
						// If TinyMCE is not initialized, fallback to the textarea
						$('#generated-content').val(response.data.content);
					}
					
					$('.preview_content').html(response.data.content);
                    $('#content-preview').show();
					$('#image-preview').css('display','block');
					$('#keyword-image').attr('src', 'data:image/png;base64,'+response.data.image_url);
					$('.preview_image').attr('src', 'data:image/png;base64,'+response.data.image_url);
					$('#feature_image').val('data:image/png;base64,'+response.data.image_url);
					$('.loading').css('display','none');
					$('#template1, #template2, #template3').css('-webkit-filter','blur(0px)');
					
                } else {
					$('.loading').css('display','none');
                    alert('Error: ' + response.data);
                }
            },
            error: function () {
				$('.loading').css('display','none');
                alert('Failed to generate content. Please try again.');
            },
        });
    });


	/**********************
	* SAVING DATA FOR 
	* PLUGIN SETTINGS PAGE
	**********************/
	$('#save_settings').on('click', function () {
    
        let radioValue = $('input[name="api_type"]:checked').val(); // Get initial value
        console.log('radio value: '+radioValue);

        $('.loading').css('display','block');

        let data = {
            action: 'save_api_settings',
            _ajax_nonce: ajax_object.nonce, // Include nonce
            radio_value: radioValue
        };

        if (radioValue === 'gemini_radio_btn') {
            
            const google_api_key = $('#google_api_key').val();
            const api_url        = $('#api_uri').val();
            const api_uri_image  = $('#api_uri_image').val(); 

            if (!google_api_key || !api_url) {
                alert('Please enter required API credentials of Gemini.');
                return;
            }

            // Assign Gemini-specific values
            data.google_api_key = google_api_key;
            data.api_url = api_url;
            data.api_uri_image = api_uri_image;
        } 
        else if (radioValue === 'openai_radio_btn') {
           
            const openai_api_key = $('#openai_api_key').val();
            const api_uri_openai = $('#api_uri_openai').val(); 
            const api_uri_image_openai = $('#api_uri_image_openai').val(); 

            if (!openai_api_key || !api_uri_openai) {
                alert('Please enter required API credentials of OpenAI.');
                return;
            }
            
            // Assign OpenAI-specific values
            data.openai_api_key = openai_api_key;
            data.api_uri_openai = api_uri_openai;
            data.api_uri_image_openai = api_uri_image_openai;
        }

        // AJAX request to generate content
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: data,
            success: function (response) {
                console.log(response);
                if (response.success) {
					alert(response.data.message);
                } else {
					alert('Error: ' + response.data);
                }
				$('.loading').css('display','none');
            },
            error: function () {
				$('.loading').css('display','none');
                alert('Failed to save content. Please try again.');
            },
        });
    });
	
	
	/*****************
	* SAVING POST DATA
	******************/
    $('#save-post').on('click', function () {
		
		const title    = $('#post_title').val();
        //const content  = $('#generated-content').val();
		
		var content;
        if (tinymce.get('generated-content')) {
            content = tinymce.get('generated-content').getContent();
        } else {
            // Fallback to the textarea value
            content = $('#generated-content').val();
        }
		
        const category = $('#post-category').val();
		const template = $('#post_template').val();
		const postImg  = $('#feature_image').val();

		if (!title) {
            alert('ERROR: Title cannot be empty.');
            return;
        }
        if (!content) {
            alert('ERROR: Content cannot be empty.');
			$('#post-category').focus();
            return;
        }
		if (!category) {
            alert('ERROR: Category cannot be empty.');
            $('#post-category').focus();
            return;
        }
		
		$('.loading').css('display','block');
		
        // AJAX request to save the post
        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: {
                action: 'save_blog_post',
                title: title,
				content: content,
                category: category,
				template: template,
				postImg:  postImg,
            },
            success: function (response) {
                if (response.success) {
                    //alert(response.data.message);
					
					// AJAX request to save the feature image
					$.ajax({
						url: ajax_object.ajax_url,
						method: 'POST',
						data: {
							action: 'save_blog_post_image',
							postImg:  postImg,
							post_id: response.data.post_id
						},
						success: function (response) {
							console.log(response);
							$('.loading').css('display','none');
							if (response.success) {
								$('.loading').css('display','none');
								alert(response.data.message);
								location.reload();
							} else {
								$('.loading').css('display','none');
								alert('ERROR: ' + response.data);
							}
						},
						error: function () {
							$('.loading').css('display','none');
							alert('Failed to save the post. Please try again.');
						},
					});
					
                } else {
					$('.loading').css('display','none');
                    alert('ERROR: ' + response.data);
                }
            },
            error: function () {
				$('.loading').css('display','none');
                alert('Failed to save the post. Please try again.');
            },
        });
    });
	
	// Load post title instantly in preview as admin change content
	var postTitle;
	$('#post_title').on('keyup change', function(){
		postTitle = $(this).val();
		console.log(postTitle);
		$('.template_one h2').text(postTitle);
	});
	
	// Load post content instantly in preview as admin change content
	if (tinymce.get('generated-content')) {
        tinymce.remove('#generated-content'); // Remove any existing instance to avoid duplication
    }
	tinymce.init({
        selector: '#generated-content', // Replace with your TinyMCE selector (default WordPress selector is #content)
        setup: function (editor) {
            // Detect keyup event
            editor.on('keyup', function (e) {
                var content = editor.getContent(); // Get the current content
                console.log('Keyup detected. Content:', content);
				$('.template_one .preview_content').html(content);
            });
        }
    });

});

// Handle template switching and show preview
const radioButtons = document.querySelectorAll('input[name="template"]'); 
const templates = {
	template1: document.getElementById('template1'),
	template2: document.getElementById('template2'),
	template3: document.getElementById('template3')
};

var template = 'template1';   
radioButtons.forEach(radio => {
	radio.addEventListener('change', (event) => {
		for (const key in templates) {
			templates[key].classList.remove('active');
		}
		templates[event.target.value].classList.add('active');
		template = event.target.value;
		var hiddenInput = document.getElementById('post_template'); 
		hiddenInput.value = template;
	});
});

function autoResize(textarea) {
  textarea.style.height = "auto"; // Reset the height to auto to calculate new height
  textarea.style.height = textarea.scrollHeight + "px"; // Set height based on scrollHeight
}
