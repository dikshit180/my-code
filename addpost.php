<?php

/**
* Plugin Name: Post via Ajax
 */

/* Enqueue JS
----- */

function pva_scripts() {
	wp_register_script( 'pva-js', plugin_dir_url( __FILE__ ) . 'pva.js', array( 'jquery' ), '', true );
	wp_localize_script( 'pva-js', 'pva_params', array( 'pva_ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	wp_enqueue_script( 'pva-js' );
};

add_action('wp_enqueue_scripts', 'pva_scripts');

// creating Ajax call for WordPress
add_action( 'wp_ajax_nopriv_pva_create', 'pva_create' );
add_action( 'wp_ajax_pva_create', 'pva_create' );

/* WP Insert Post Function
----- */

function pva_create() {
    if (!isset($_POST['post_title']) || empty($_POST['post_title'])) {
        wp_send_json_error('Post title is required.');
    }

    // Sanitize the post title
    $post_title = sanitize_text_field($_POST['post_title']);

    // Check if a post with the same title already exists
    $existing_post = get_page_by_title($post_title, OBJECT, 'toys');
    if ($existing_post) {
        wp_send_json_error('A post with the same title already exists.');
    }
    // Create the post
    $new_pva_post = array(
        'post_type'   => 'toys',
        'post_title'  => $post_title,
        'post_status' => 'publish',
        'post_author' => 1,
    );
    $post_id = wp_insert_post($new_pva_post);

    if (is_wp_error($post_id)) {
        wp_send_json_error('Failed to create the post.');
    }

    // Handle the file upload for the featured image
    if (isset($_FILES['post_featured_image']) && !empty($_FILES['post_featured_image']['name'])) {
        $uploaded_file = $_FILES['post_featured_image'];
        $upload = wp_handle_upload($uploaded_file, array('test_form' => false));

        if (isset($upload['file'])) {
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title'     => sanitize_file_name($upload['file']),
                'post_content'   => '',
                'post_status'    => 'inherit',
            );

            $attachment_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attach_data);

            // Set the attachment as the post's featured image
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    wp_send_json_success('Post created successfully.');
}


/* Form Shortcode
----- */

function pva_shortcode( $atts, $content = null ) {
	ob_start();
	include(plugin_dir_path( __FILE__ ) . 'post_via_ajax_field.php');
	$ret = ob_get_contents();
	ob_end_clean();
	return $ret;
	//pva();
};

add_shortcode( 'pva', 'pva_shortcode' );