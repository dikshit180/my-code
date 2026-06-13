

<?php
/*
Plugin Name: WooCommerce Product API
Description: Product CRUD APIs for WooCommerce
Version: 1.0
Author: Dikshit
*/

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {

    register_rest_route('custom/v1', '/product', [
        'methods' => 'POST',
        'callback' => 'wc_api_create_product',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('custom/v1', '/product/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'wc_api_get_product',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('custom/v1', '/product/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'wc_api_update_product',
        'permission_callback' => '__return_true'
    ]);

    register_rest_route('custom/v1', '/product/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'wc_api_delete_product',
        'permission_callback' => '__return_true'
    ]);
});

function wc_api_create_product($request)
{
    $product = new WC_Product_Simple();

    $product->set_name($request->get_param('name'));
    $product->set_regular_price($request->get_param('price'));

    $product_id = $product->save();

    $image_url = $request->get_param('image_url');

    if (!empty($image_url)) {

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $attachment_id = media_sideload_image(
            $image_url,
            $product_id,
            null,
            'id'
        );

        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }

    return rest_ensure_response([
        'success'   => true,
        'product_id'=> $product_id
    ]);
}
function wc_api_get_product($request)
{
    $product = wc_get_product($request['id']);

    if (!$product) {
        return new WP_Error(
            'product_not_found',
            'Product not found',
            ['status' => 404]
        );
    }

    return rest_ensure_response([
        'id'    => $product->get_id(),
        'name'  => $product->get_name(),
        'price' => $product->get_price()
    ]);
}

function wc_api_update_product($request)
{
    $product = wc_get_product($request['id']);

    if (!$product) {
        return new WP_Error(
            'product_not_found',
            'Product not found',
            ['status' => 404]
        );
    }

    $product->set_name($request->get_param('name'));
    $product->set_regular_price($request->get_param('price'));
    $product->save();

    return rest_ensure_response([
        'success' => true,
        'message' => 'Product updated'
    ]);
}

function wc_api_delete_product($request)
{
    $deleted = wp_delete_post($request['id'], true);

    if (!$deleted) {
        return new WP_Error(
            'delete_failed',
            'Unable to delete product',
            ['status' => 400]
        );
    }

    return rest_ensure_response([
        'success' => true,
        'message' => 'Product deleted'
    ]);
}
