<?php
/**
 * Plugin Name: Blog Content Generator
 * Description: Generate blog content from a string or keywords, preview, edit, and save posts directly from the WordPress admin backend.
 * Version: 1.0
 * Author: Aakash sharma (Dotsquares)
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 * Text Domain: blog-content-generator
 * Requires at least: 5.8
 * Tested up to: 6.4
 * PHP Version: 7.4
 */


// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class BlogContentGenerator {

	/**
	* Constructor function to initialize the class and set up WordPress hooks.
	*/
    public function __construct() {
        // Add a custom menu item in the WordPress admin panel.
		add_action('admin_menu', [$this, 'add_admin_menu']);

		// Enqueue necessary scripts and styles for the plugin in the admin panel.
		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

		// Register an AJAX handler for generating blog content.
		// This is triggered via the 'wp_ajax_generate_blog_content' action in admin-ajax.php.
		add_action('wp_ajax_generate_blog_content', [$this, 'generate_blog_content']);

		// Register an AJAX handler for saving blog posts.
		// This is triggered via the 'wp_ajax_save_blog_post' action in admin-ajax.php.
		add_action('wp_ajax_save_blog_post', [$this, 'save_blog_post']);

		// Register an AJAX handler for saving blog post images.
		// This is triggered via the 'wp_ajax_save_blog_post_image' action in admin-ajax.php.
		add_action('wp_ajax_save_blog_post_image', [$this, 'save_blog_post_image']);
		
		add_action('wp_ajax_save_api_settings', [$this, 'save_api_settings']);
		
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);

		add_filter('template_include', [$this, 'custom_atdw_news_template']); // cusotm single post page
		
    }
	
	
    public function add_admin_menu() {
        add_menu_page(
            'Blog Content Generator',   // The title of the parent menu page
            'Blog Generator', 			// The label of the parent menu item
            'manage_options',           // The capability required to access the parent menu
            'blog-content-generator',   // The slug of the parent menu
            [$this, 'admin_page'],      // The function to display the parent menu page content  
            'dashicons-format-status',  // The Icon for the parent menu page content
            20
        );
		// Add a submenu item under the "Blog Generator" menu
		add_submenu_page(
			'blog-content-generator', 	// The slug of the parent menu
			'Settings',               	// The title of the submenu page
			'Settings',               	// The label of the submenu item
			'manage_options',         	// The capability required to access the submenu
			'bcg_settings',           	// The slug for the submenu page
			[$this, 'bcg_setting']    	// The function to display the submenu page content
		);
    }

	/**
	* Enqueue scripts and styles for the plugin's admin page.
	*
	* @param string $hook The current admin page hook suffix.
	*/
    public function enqueue_scripts($hook) {
		if ($hook === 'toplevel_page_blog-content-generator' || $hook === 'blog-generator_page_bcg_settings') {
            wp_enqueue_script('blog-generator-script', plugin_dir_url(__FILE__) . 'js/script.js', ['jquery'], '1.0', true);
            wp_enqueue_style('blog-generator-style', plugin_dir_url(__FILE__) . 'css/style.css', [], '1.0');
            wp_localize_script('blog-generator-script', 'ajax_object', [
                'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('blog_generator_nonce'),
            ]);
        }
    }

	/**
	* Render the content for the plugin's admin page.
	*/
    public function admin_page() {
    ?>
		<div class="loading" style="display:none;">Loading&#8230;</div>
		<h1>AI Powered Content Generator</h1>
		<div class="main-wrapper">
			<div class="wrap">
				<h2>Input your prompt here</h2>
				<div class="form-layout">
					<div class="textarea-container">
					  <textarea placeholder="Enter keywords here..." oninput="autoResize(this)" wrap="soft" id="input-keywords" class="regular-text" style="width: 100%; height: 80px;"></textarea>
					  <button id="generate-content" class="send-button">➤</button> <!-- click event -->
					</div>
					<em>Your blog writing assistant, ready to create. Turn your ideas into compelling blog posts effortlessly.</em>
				</div>

				<div id="content-preview" style="margin-top: 20px; display: none;">
					<h2>Generated Blog Content:</h2>
					<label for="post_title">Post heading:</label>
					<input type="text" id="post_title" class="regular-text" />
					
					<label for="generated-content">Blog post content:</label>
					
					<?php
					// Render the WordPress Editor
					$editor_id = 'generated-content';
					$editor_content = ''; // You can populate this with default content if needed
					$editor_settings = array(
						'textarea_name' => 'generated-content', // Matches the ID for saving content
						'media_buttons' => true,                // Enable media upload
						'teeny'         => false,               // Full editor
						'quicktags'     => true,                // Enable Quicktags toolbar
					);
					wp_editor($editor_content, $editor_id, $editor_settings);
					?>
					
					<div id="image-preview" style="margin-top: 20px; display: none;">
						<h2 style="font-size: 14px; color: #000;">Image Preview:</h2>
						<img id="keyword-image" src="" alt="Image preview" style="max-width: 400px; border: 1px solid #ccc; border-radius: 5px;">
					</div>
					
					<label for="post-category">Select Category:</label>
					<select id="post-category" name="post-category">
						<option value="">-Choose Category-</option>
						<?php
						$categories = get_categories(['hide_empty' => false, 'exclude' => array(1,5)]);
						foreach ($categories as $category) {
							echo '<option value="' . $category->term_id . '">' . $category->name . '</option>';
						}
						?>
					</select>

					<div style="margin-top: 20px; display:flex;">
						<button id="save-post" class="button button-primary">Insert Post</button>
						<input type="hidden" value="template1" id="post_template" />
						<input type="hidden" value="" id="feature_image" />
					</div>
					<div class="info">
						<em>NOTE: Please properly check the preview of post and make corrections in the content before saving.</em>
					</div>
				</div>
			</div>
			
			<div class="wrap preview">
				<h2>Blog Preview</h2>
				<label>
					<input type="radio" name="template" value="template1" checked>
					Template 1
				</label>
				<label>
					<input type="radio" name="template" value="template2">
					Template 2
				</label>
				<label>
					<input type="radio" name="template" value="template3">
					Template 3
				</label>

				<!-- Templates -->
				<div id="template1" class="active">
					<div class="template_one">
						<span>December 19, 2024</span>
						<h2>Sample Post Title</h2>
						<img class="preview_image" src="https://placehold.co/600x400" alt="" />
						<div class="preview_content"></div>
					</div>
				</div>
				<div id="template2">
					<div class="template_one">
						<img class="preview_image" src="https://placehold.co/600x400" alt="" />
						<h2>Sample Post Title</h2>
						<span>December 19, 2024</span>
						<div class="preview_content"></div>
					</div>
				</div>
				<div id="template3">
					<div class="template_one">
						<img class="preview_image" src="https://placehold.co/600x400" alt="" />
						<span>December 19, 2024</span>
						<h2>Sample Post Title</h2>
						<div class="preview_content"></div>
					</div>
				</div>
				
			</div>
		</div>
    <?php
    }
	
	
	/**
	* Render the content for the plugin's admin sub menu page.
	* @description: Function to display the submenu setting page For API configuration
	*/
	public function bcg_setting() {
		?>
		<div class="loading" style="display:none;">Loading&#8230;</div>
		<h1>API Settings</h1>
		<div class="main-wrapper settings_admin">
			<?php
			// Get the option value
			$api_source      = get_option('bcg_api_source', '');
			
			if($api_source == "gemini_radio_btn"){
				$api_key         = get_option('bcg_api_key', '');
				$api_content_url = get_option('bcg_api_endpoint', '');
				$api_image_url   = get_option('bcg_image_api_endpoint', '');
			}
			else{
				$api_key         = '';
				$api_content_url = '';
				$api_image_url   = '';
			}

			if($api_source == "openai_radio_btn"){
				$api_key_Openai         = get_option('bcg_api_key', '');
				$api_content_url_Openai = get_option('bcg_api_endpoint', '');
				$api_image_url_Openai   = get_option('bcg_image_api_endpoint', '');
			}
			else{
				$api_key_Openai         = '';
				$api_content_url_Openai = '';
				$api_image_url_Openai   = '';
			}
			
			?>
			<div class="switch-btn-block">
				<ul>
					<li> <label for="gemini"><input type="radio" value="gemini_radio_btn" name="api_type" checked /> Google Gemini</label></li>
					<li> <label for="openai"><input type="radio" value="openai_radio_btn" name="api_type" /> Open AI</label> </li>
				</ul>
			</div>
			<div class="wrap api_settings_block" id="geminiDiv">
				<h2>Google Gemini - API Configuration: <?php if($api_source == "gemini_radio_btn"){ echo '<span>Activated</span>'; }?></h2>
				<div class="api-form-wrapper">
					<div class="form-group">
						<label for="api_key">Google Gemini API Key: *</label>
						<input type="text" id="google_api_key" placeholder="Enter Api key" name="google_api_key" class="form-input regular-text" value="<?php echo $api_key; ?>"  />
					</div>
					<div class="form-group">
						<label for="api_uri">API Endpoint URL for Content: *</label>
						<input type="text" id="api_uri" placeholder="Api endpoint for content" name="api_uri" class="form-input regular-text" value="<?php echo $api_content_url; ?>" />
					</div>
					<div class="form-group">
						<label for="api_uri">API Endpoint URL for Image:</label>
						<input type="text" id="api_uri_image" placeholder="Api endpoint for image" name="api_uri_image" class="form-input regular-text" value="<?php echo $api_image_url; ?>" />
					</div>
				</div>	
			</div>
			<div class="wrap api_settings_block" id="openaiDiv">
				<h2>OpenAI - API Configuration: <?php if($api_source == "openai_radio_btn"){ echo '<span>Activated</span>'; }?> </h2>
				<div class="api-form-wrapper">
					<div class="form-group">
						<label for="api_key">Open AI API Key: *</label>
						<input type="text" id="openai_api_key" placeholder="Enter Api key" name="openai_api_key" class="form-input regular-text" value="<?php echo $api_key_Openai; ?>" />
					</div>
					<div class="form-group">
						<label for="api_uri">API Endpoint URL for Content: *</label>
						<input type="text" id="api_uri_openai" placeholder="Api endpoint for content" name="api_uri_openai" class="form-input regular-text" value="<?php echo $api_content_url_Openai; ?>" />
					</div>	
					<div class="form-group">
						<label for="api_uri">API Endpoint URL for Image:</label>
						<input type="text" id="api_uri_image_openai" placeholder="Api endpoint for image" name="api_uri_image_openai" class="form-input regular-text" value="<?php echo $api_image_url_Openai; ?>" />
					</div>
				</div>	
			</div>
			<button id="save_settings" name="save_settings" class="button button-primary">Save Settings</button>
		</div> 
		<?php
	}

	/**
	* GENERATE BLOG
	*/
    public function generate_blog_content() {
        
		if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'blog_generator_nonce')) {
			wp_send_json_error('Invalid nonce', 403);
		}
		
		if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
		if (empty($keywords)) {
			wp_send_json_error('Keywords are required.');
		}

		$response_data = '';
		// Get the option value
		$api_source      = get_option('bcg_api_source', '');
		
		// check options for Gemini API
		if($api_source == "gemini_radio_btn"){
			$api_key         = get_option('bcg_api_key', '');
			$api_content_url = get_option('bcg_api_endpoint', '');
			$api_image_url   = get_option('bcg_image_api_endpoint', '');

			$api_key = $api_key; // Define API Key
			$url = $api_content_url . "?key=" . $api_key; // Define the API URL
			//$keyword = $keywords;  // Define the keyword for content generation

			// Construct the request payload
			$data = [
				"contents" => [
					[
						"parts" => [
						// ["text" => "Write a detailed blog post about $keyword."]
						["text" => "Generate a blog post with a title and content. Return the result in the following format: \nTitle: [title]\n\nContent: [content].\n\nTopic: $keywords."]
						]
					]
				]
			];

			// Convert the data to JSON
			$json_data = json_encode($data);
			$response_data = curlFunction($url, $json_data); // Calling api here

			// Extract and display generated content
			if (isset($response_data["candidates"][0]["content"]["parts"][0]["text"])) {
				$response_text = $response_data["candidates"][0]["content"]["parts"][0]["text"] ?? '';
				// Use regex to find the title
				if (preg_match('/Title:\s*(.+?)\n\nContent:/is', $response_text, $matches)) {
					$post_title = trim($matches[1]); // Extracted title
				} else {
					$post_title = "Untitled"; // Fallback if no title is found
				}

				// Extract the content after "Content:"
				$content = preg_replace('/^.*Content:\s*/is', '', $response_text);

				// API URL for image generation
				$imageGenerationUrl = $api_image_url . "?key=" . $api_key;

				// Request payload
				$dataImage = [
					"contents" => [
						[
							"parts" => [
								["text" => "Hi, can you create a 3D rendered image about $keywords."]
							]
						]
					],
					"generationConfig" => ["responseModalities" => ["Text", "Image"]]
				];

				// Convert data to JSON
				$json_data_img = json_encode($dataImage);
				$response_image = curlImageFunction($imageGenerationUrl, $json_data_img); // Calling api here

				// Display title and content
				$image_url = $response_image;
			
				$response = [
					'title' => $post_title,
					'content' => nl2br($content),
					'image_url' => $image_url, 
				];
				wp_send_json_success($response);
			} else {
				wp_send_json_error('Failed to generate content. Check API key or request format.');
			}
			// api call ends here
		}
		else if($api_source == "openai_radio_btn"){ // check options for OpenAI API
			$api_key_Openai         = get_option('bcg_api_key', '');
			$api_content_url_Openai = get_option('bcg_api_endpoint', '');
			$api_image_url_Openai   = get_option('bcg_image_api_endpoint', '');
		}
		else{
			wp_send_json_error('Failed to generate content. Check API key or request format.2');
		}

    }

	/**
	* SAVE BLOG POST
	*/
    public function save_blog_post() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }
		
		$content 		= isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
		$category_id    = isset($_POST['category']) ? intval($_POST['category']) : 0;
		$template_name  = isset($_POST['template']) ? sanitize_text_field($_POST['template']) : '';
		$post_title 	= isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
		$image_url      = $_POST['postImg'];
		
        $post_id = wp_insert_post([
            'post_title'    => $post_title,
            'post_content'  => $content,
            'post_status'   => 'draft',
            'post_category' => [$category_id]
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error('Failed to save the post.');
        }
		
		if ($post_id) {
			// Add custom meta field
			$meta_key = 'post_template'; // Replace with your meta key
			$meta_value = $template_name; // Replace with your meta value
			add_post_meta($post_id, $meta_key, $meta_value, true);
			
		}

		$response = [
			'message' => 'SUCCESS: Post saved successfully.',
			'post_id' => $post_id
		];
		wp_send_json_success($response);
    }
	
	/**
	* SAVE BLOG FEATURE IMAGE
	*/
	public function save_blog_post_image() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $post_id   = intval($_POST['post_id']);		
		$image_url = $_POST['postImg'];

		$result = save_base64_image_as_featured_image($image_url, $post_id);

		if (is_wp_error($result)) {
			wp_send_json_error('Failed to save the post thumbnail.');
		} else {
			//echo "Featured image set successfully! Attachment ID: " . $result;
			wp_send_json_success(['message' => 'SUCCESS: Post saved successfully.', 'post_id' => $post_id]);
		}
		//die;

		/*
		if (filter_var($image_url, FILTER_VALIDATE_URL)) {
			
			// Get the WordPress upload directory information
			$upload_dir = wp_upload_dir();

			// Get the file name
			$filename = basename($image_url);

			// Initialize cURL
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $image_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Optional: Disable SSL verification
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Optional: Disable SSL verification
			$image_data = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($image_data && $http_code == 200) {
				// Create a unique file path in the uploads directory
				$file_path = $upload_dir['path'] . '/' . $filename;

				// Save the file to the uploads directory
				file_put_contents($file_path, $image_data);

				// Insert the file as an attachment
				$attachment_id = wp_insert_attachment([
					'guid'           => $upload_dir['url'] . '/' . $filename,
					'post_mime_type' => mime_content_type($file_path),
					'post_title'     => sanitize_file_name($filename),
					'post_content'   => '',
					'post_status'    => 'inherit',
				], $file_path, $post_id);

				if ($attachment_id) {
					// Generate metadata for the attachment and update it
					require_once(ABSPATH . 'wp-admin/includes/image.php');
					$attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
					wp_update_attachment_metadata($attachment_id, $attach_data);

					// Set the attachment as the featured image
					set_post_thumbnail($post_id, $attachment_id);
					wp_send_json_success(['message' => 'SUCCESS: Post saved successfully.', 'post_id' => $post_id]);
				}
			}else{
				wp_send_json_error('Failed to save the post');
			}
		}else{
			wp_send_json_error('Failed to save the post');
		}*/
	   
    }
	
	/**
	* SAVE API SETTINGS DATA
	*/
	public function save_api_settings(){
		if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'blog_generator_nonce')) {
			wp_send_json_error('Invalid nonce', 403);
		}
		
		if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

		$radio_value = isset($_POST['radio_value']) ? sanitize_text_field($_POST['radio_value']) : '';

		if($radio_value == 'gemini_radio_btn'){
			$api_key = isset($_POST['google_api_key']) ? sanitize_text_field($_POST['google_api_key']) : '';
			$api_url = isset($_POST['api_url']) ? sanitize_text_field($_POST['api_url']) : '';
			$api_uri_image = isset($_POST['api_uri_image']) ? sanitize_text_field($_POST['api_uri_image']) : '';
		}else{
			$api_key = isset($_POST['openai_api_key']) ? sanitize_text_field($_POST['openai_api_key']) : '';
			$api_url = isset($_POST['api_uri_openai']) ? sanitize_text_field($_POST['api_uri_openai']) : '';
			$api_uri_image = isset($_POST['api_uri_image_openai']) ? sanitize_text_field($_POST['api_uri_image_openai']) : '';
		}
       
		if (empty($api_key)) {
			wp_send_json_error('API Key is required.');
		}
		if (empty($api_url)) {
			wp_send_json_error('API Endpoint is required.');
		}
		
		if (!empty($api_key) && !empty($api_url) && filter_var($api_url, FILTER_VALIDATE_URL) && filter_var($api_uri_image, FILTER_VALIDATE_URL)) {
			
			// Sanitize the URL (if validation passes)
			$sanitized_content_api_url  = sanitize_text_field($api_url);
			$sanitized_api_key 			= sanitize_text_field($api_key);
			$sanitized_image_api_url    = sanitize_text_field($api_uri_image);
			
			// Update the option in the wp_options table
			update_option('bcg_api_source', $radio_value, 'no');
			update_option('bcg_api_key', $sanitized_api_key, 'no');
			update_option('bcg_api_endpoint', $sanitized_content_api_url, 'no');
			update_option('bcg_image_api_endpoint', $sanitized_image_api_url, 'no');
			
			$response = [
				'message' => 'SUCCESS: Settings saved successfully.'
			];
			wp_send_json_success($response); 
			
		} else { 
			// Handle invalid input
			wp_send_json_error('Invalid API Endpoint URL.');
		}
		
	}
	
	// Add settings link in the Plugins list
	public function add_settings_link($links) {
		$settings_link = '<a href="admin.php?page=bcg_settings">Settings</a>';
		array_push($links, $settings_link);
		return $links;
	}
	
	private function get_template_path($template_name) {
        $theme_template = get_stylesheet_directory() . "/ai-posts/{$template_name}";
        $plugin_template = plugin_dir_path(__FILE__) . "templates/{$template_name}";

        return file_exists($theme_template) ? $theme_template : $plugin_template;
    }

	// Custom Single post page
    public function custom_atdw_news_template($template) {
        if (is_singular('post')) {
            // Path to the custom template inside your plugin
            $plugin_template = $this->get_template_path('single-post.php');
    
            // Check if the file exists before overriding
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }
}

new BlogContentGenerator();

// Include additional files
require_once plugin_dir_path(__FILE__) . 'includes/custom-functions.php';