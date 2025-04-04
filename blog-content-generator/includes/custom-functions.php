<?php

function curlFunction($url, $json_data){
    // Initialize cURL session
    $ch = curl_init($url);
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    // Execute the request
    $response = curl_exec($ch);
    // Close cURL session
    curl_close($ch);

    // Decode the response
    $response_data = json_decode($response, true);
    return $response_data; 
}

function curlImageFunction($url, $json_data){ 
    // Initialize cURL session
    $ch = curl_init($url);
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    // Execute request
    $response = curl_exec($ch);
    // Close cURL session
    curl_close($ch);
    // Decode response
    $response_data = json_decode($response, true);

    // Display response
    $imageData = $response_data["candidates"][0]["content"]["parts"][0]["inlineData"]["data"];
    return $imageData;
}

function save_base64_image_as_featured_image($base64_string, $post_id) {
    // Check if Base64 string is valid
    if (empty($base64_string) || strpos($base64_string, 'base64') === false) {
        return new WP_Error('invalid_base64', 'Invalid Base64 image data.');
    }

    // Extract the file type (e.g., png, jpg)
    if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $matches)) {
        $image_type = $matches[1]; // Get image type
        $base64_string = preg_replace('/^data:image\/\w+;base64,/', '', $base64_string); // Remove metadata
        $base64_string = base64_decode($base64_string); // Decode Base64

        if (!$base64_string) {
            return new WP_Error('decode_failed', 'Base64 decoding failed.');
        }
    } else {
        return new WP_Error('invalid_format', 'Invalid image format.');
    }

    // Generate a unique file name
    $upload_dir = wp_upload_dir(); // Get WordPress upload directory
    $filename = 'image-' . time() . '.' . $image_type; // Unique filename
    $file_path = $upload_dir['path'] . '/' . $filename; // Full file path

    // Save the decoded image to a file
    file_put_contents($file_path, $base64_string);

    // Prepare the file array for WordPress media insertion
    $file_array = [
        'name'     => $filename,
        'type'     => 'image/' . $image_type,
        'tmp_name' => $file_path,
        'size'     => filesize($file_path),
    ];

    // Include WordPress file handling functions
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Upload the image to the Media Library
    $attachment_id = media_handle_sideload($file_array, $post_id);

    if (is_wp_error($attachment_id)) {
        return $attachment_id; // Return error if upload fails
    }

    // Set the image as the featured image (post thumbnail)
    set_post_thumbnail($post_id, $attachment_id);

    return $attachment_id; // Return the attachment ID
}