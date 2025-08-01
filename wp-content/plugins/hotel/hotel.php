<?php
/**
 * Plugin Name: Hotel API Plugin with Form
 * Description: A WordPress plugin to manage hotels via REST API and a submission form.
 * Version: 1.1
 * Author: Your Name
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
add_action('init', 'register_hotel_post_type');

function register_hotel_post_type() {
    register_post_type('hotel', [
        'labels' => [
            'name'               => 'Hotels',
            'singular_name'      => 'Hotel',
            'add_new'            => 'Add New Hotel',
            'add_new_item'       => 'Add New Hotel',
            'edit_item'          => 'Edit Hotel',
            'new_item'           => 'New Hotel',
            'view_item'          => 'View Hotel',
            'search_items'       => 'Search Hotels',
            'not_found'          => 'No hotels found',
            'not_found_in_trash' => 'No hotels found in Trash',
            'all_items'          => 'All Hotels',
        ],
        'public' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'hotels'],
        'menu_icon' => 'dashicons-building',
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true, // Enable Gutenberg and REST API access
    ]);
}

// Register REST API routes.
add_action('rest_api_init', 'hotel_api_register_routes');

/**
 * Register custom REST API routes for Hotel CRUD operations.
 */
function hotel_api_register_routes() {
    register_rest_route('hotel-api/v1', '/hotel', [
        'methods'  => 'POST',
        'callback' => 'hotel_api_create_hotel',
        'permission_callback' => '__return_true', // Adjust permissions as needed.
    ]);
	register_rest_route('hotel-api/v1', '/hotels', [
        'methods'  => 'GET',
        'callback' => 'hotel_api_get_hotels',
        'permission_callback' => '__return_true',
    ]);
}


/**
 * Create a new hotel (POST).
 */
function hotel_api_create_hotel($request) {
    $params = $request->get_json_params();

    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($params['title']),
        'post_content' => sanitize_textarea_field($params['description']),
        'post_status'  => 'publish',
        'post_type'    => 'hotel',
    ]);

    if (is_wp_error($post_id)) {
        return new WP_Error('create_failed', 'Failed to create hotel.', ['status' => 400]);
    }
	if (!empty($params['image_id'])) {
        set_post_thumbnail($post_id, intval($params['image_id']));
    }

    return rest_ensure_response(['message' => 'Hotel created', 'id' => $post_id], 201);
}
function hotel_api_get_hotels($request) {
    // Fetch hotel posts using WP_Query.
    $args = [
        'post_type'   => 'hotel',
        'post_status' => 'publish',
        'numberposts' => -1, // Retrieve all posts.
    ];

    $posts = get_posts($args);
    $data = [];

    // Format the response.
    foreach ($posts as $post) {
        $data[] = [
            'id'      => $post->ID,
            'title'   => $post->post_title,
            'content' => $post->post_content,
        ];
    }

    return rest_ensure_response($data);
}
// Enqueue scripts and styles.
add_action('wp_enqueue_scripts', 'hotel_api_enqueue_scripts');
function hotel_api_enqueue_scripts() {
    wp_enqueue_script('hotel-api-script', plugin_dir_url(__FILE__) . 'assets/js/hotel.js', ['jquery'], null, true);
    wp_localize_script('hotel-api-script', 'hotelApi', [
        'apiUrl' => home_url('/wp-json/hotel-api/v1/hotel'),
        'nonce'  => wp_create_nonce('wp_rest'),
    ]);
}

// Add a shortcode to display the form.
add_shortcode('hotel_form', 'hotel_api_render_form');
function hotel_api_render_form() {
	wp_enqueue_media(); 
    ob_start();
    ?>
    <form id="hotel-form">
        <label for="title">Hotel Title:</label>
        <input type="text" id="title" name="title" required><br><br>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required></textarea><br><br>
<button id="upload-image">Upload Featured Image</button>
        <img id="image-preview" style="display:none; max-width: 200px; margin-top: 10px;"><br><br>
        <button type="submit">Submit</button>
        <div id="response-message" style="margin-top: 10px;"></div>
    </form>
    <?php
    return ob_get_clean();
}

