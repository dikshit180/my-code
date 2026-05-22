<?php
/*
Plugin Name: Book API Plugin
Version: 1.0
*/

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| REGISTER BOOK POST TYPE
|--------------------------------------------------------------------------
*/

function register_books_cpt()
{
    register_post_type(
        'book',
        [
            'label' => 'Books',
            'public' => true,

            'supports' => [
                'title',
                'editor',
                'thumbnail'
            ],

            'show_in_rest' => true
        ]
    );
}

add_action('init', 'register_books_cpt');


/*
|--------------------------------------------------------------------------
| REGISTER BOOK GENRE
|--------------------------------------------------------------------------
*/

function register_book_genre()
{
    register_taxonomy(
        'book_genre',
        'book',
        [
            'label' => 'Book Genre',
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true
        ]
    );
}

add_action('init', 'register_book_genre');


/*
|--------------------------------------------------------------------------
| REGISTER AUTHOR META
|--------------------------------------------------------------------------
*/

function register_book_author()
{
    register_post_meta(
        'book',
        'author',
        [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true
        ]
    );
}

add_action('init', 'register_book_author');


/*
|--------------------------------------------------------------------------
| CREATE BOOK API
|--------------------------------------------------------------------------
*/

add_action(
    'rest_api_init',
    function () {

        register_rest_route(
            'books/v1',
            '/create',
            [
                'methods' => 'POST',
                'callback' => 'create_book',

                'permission_callback' => function () {

                    $auth =
                        $_SERVER['HTTP_AUTHORIZATION']
                        ?? '';

                    return $auth === 'Bearer abc123';
                }
            ]
        );
    }
);


/*
|--------------------------------------------------------------------------
| FEATURE IMAGE FROM URL
|--------------------------------------------------------------------------
*/

function set_featured_image_from_url(
    $image_url,
    $post_id
) {

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $temp_file =
        download_url(
            $image_url
        );

    if (
        is_wp_error(
            $temp_file
        )
    ) {
        return false;
    }

    $file = [

        'name' =>
        time() . '.jpg',

        'tmp_name' =>
        $temp_file

    ];

    $attachment_id =
        media_handle_sideload(
            $file,
            $post_id
        );

    if (
        is_wp_error(
            $attachment_id
        )
    ) {

        @unlink(
            $temp_file
        );

        return false;
    }

    set_post_thumbnail(
        $post_id,
        $attachment_id
    );

    return $attachment_id;
}


/*
|--------------------------------------------------------------------------
| CREATE BOOK
|--------------------------------------------------------------------------
*/

function create_book($request)
{

    $title =
        sanitize_text_field(
            $request->get_param(
                'title'
            )
        );

    $content =
        sanitize_textarea_field(
            $request->get_param(
                'content'
            )
        );

    $author =
        sanitize_text_field(
            $request->get_param(
                'author'
            )
        );

    $genre =
        sanitize_text_field(
            $request->get_param(
                'genre'
            )
        );

    $image_url =
        esc_url_raw(
            $request->get_param(
                'image_url'
            )
        );

    $post_id =
        wp_insert_post(
            [
                'post_type' => 'book',
                'post_title' => $title,
                'post_content' => $content,
                'post_status' => 'publish'
            ]
        );

    update_post_meta(
        $post_id,
        'author',
        $author
    );

    wp_set_object_terms(
        $post_id,
        $genre,
        'book_genre'
    );

    if (
        !empty(
            $image_url
        )
    ) {

        $featured =
            set_featured_image_from_url(
                $image_url,
                $post_id
            );

        update_post_meta(
            $post_id,
            'featured_result',
            $featured
        );
    }

    return [

        'success' => true,

        'post_id' => $post_id
    ];
}


/*
|--------------------------------------------------------------------------
| GET BOOK API
|--------------------------------------------------------------------------
*/

add_action(
    'rest_api_init',
    function () {

        register_rest_route(
            'books/v1',
            '/list',
            [
                'methods' => 'GET',

                'callback' => 'get_books',

                'permission_callback' => '__return_true'
            ]
        );
    }
);


/*
|--------------------------------------------------------------------------
| GET BOOKS
|--------------------------------------------------------------------------
*/

function get_books()
{

    $query =
        new WP_Query(
            [
                'post_type' => 'book'
            ]
        );

    $data = [];

    while (
        $query->have_posts()
    ) {

        $query->the_post();

        $data[] = [

            'id' =>
            get_the_ID(),

            'title' =>
            get_the_title(),

            'content' =>
            get_the_content(),

            'url' =>
            get_permalink(),

            'author' =>
            get_post_meta(
                get_the_ID(),
                'author',
                true
            ),

            'genre' =>
            wp_get_post_terms(
                get_the_ID(),
                'book_genre',
                [
                    'fields' => 'names'
                ]
            ),

            'featured_image' =>
            get_the_post_thumbnail_url(
                get_the_ID(),
                'full'
            )

        ];
    }

    wp_reset_postdata();

    return $data;
}