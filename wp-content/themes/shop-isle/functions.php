<?php
/**
 * Main functions file
 *
 * @package WordPress
 * @subpackage Shop Isle
 */
$vendor_file = trailingslashit( get_template_directory() ) . 'vendor/autoload.php';
if ( is_readable( $vendor_file ) ) {
	require_once $vendor_file;
}

if ( ! defined( 'WPFORMS_SHAREASALE_ID' ) ) {
	define( 'WPFORMS_SHAREASALE_ID', '848264' );
}

add_filter( 'themeisle_sdk_products', 'shopisle_load_sdk' );
/**
 * Loads products array.
 *
 * @param array $products All products.
 *
 * @return array Products array.
 */
function shopisle_load_sdk( $products ) {
	$products[] = get_template_directory() . '/style.css';

	return $products;
}


function my_awesome_func( $data ) {
	$posts = get_posts( array(
		'author' => $data['id'],
	) );

	if ( empty( $posts ) ) {
		return null;
	}

	return $posts[0]->post_title;
}


add_action( 'rest_api_init', function () {
	register_rest_route( 'walden/v1', '/author/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'my_awesome_func',
	) );
} );


// New order status AFTER woo 2.2
//add_action( 'init', 'register_paid_order_statuses' );

/*function register_paid_order_statuses() {
    register_post_status( 'wc-paid', array(
        'label'                     => _x( 'Paid', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Paid<span class="count">(%s)</span>', 'Paid<span class="count">(%s)</span>', 'woocommerce' )
    ) );
}

add_filter( 'wc_order_statuses', 'payment_paid_wc_order_statuses' );
 */
// Register in wc_order_statuses.
/*function payment_paid_wc_order_statuses( $order_statuses ) {
    $order_statuses['wc-paid'] = _x( 'Paid', 'Order status', 'woocommerce' );

    return $order_statuses;
}
 */


/*add_filter( 'wc_order_statuses', 'wc_renaming_order_status' );
function wc_renaming_order_status( $order_statuses ) {
	//var_dump($order_statuses);exit;
    foreach ( $order_statuses as $key => $status ) {
        if ( 'wc-pending' === $key ) 
            $order_statuses['wc-pending'] = _x( 'Pending', 'Order status', 'woocommerce' );
    }

    foreach ( $order_statuses as $key => $status ) {
        if ( 'wc-completed' === $key ) 
            $order_statuses['wc-completed'] = _x( 'Delivered', 'Order status', 'woocommerce' );
    }

    foreach ( $order_statuses as $key => $status ) {
        if ( 'wc-processing' === $key ) 
            $order_statuses['wc-processing'] = _x( 'Out For Delivery', 'Order status', 'woocommerce' );
    }
    return $order_statuses;
}*/

/**
 * Initialize all the things.
 */
require get_template_directory() . '/inc/init.php';

/**
 * Note: Do not add any custom code here. Please use a child theme so that your customizations aren't lost during updates.
 * http://codex.wordpress.org/Child_Themes
 */
