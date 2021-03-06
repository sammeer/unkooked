<?php
/**
 * Cart REST API for WooCommerce
 *
 * Handles cart endpoints requests for WC-API.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  Cart REST API for WooCommerce/API
 * @since    1.0.0
 * @version  1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart REST API class.
 */
class WC_Geolocation_Rest_API {

	/**
	 * Setup class.
	 *
	 * @access public
	 */
	public function __construct() {
		// WC Cart REST API.
		$this->geolocation_rest_api_init();
	} // END __construct()

	/**
	 * Init WC Cart REST API.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @version 1.0.3
	 */
	private function geolocation_rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		$this->include_geolocation_controller();

		// Init Cart REST API route.
		add_action( 'rest_api_init', array( $this, 'register_geolocation_routes' ), 0 );
	} // cart_rest_api_init()

	/**
	 * Include Cart REST API controller.
	 *
	 * @access private
	 * @since  1.0.0
	 */
	private function include_geolocation_controller() {
		// REST API v2 controller.
		include_once( dirname( __FILE__ ) . '/api/class-wc-rest-geolocation-controller.php' );
	} // include()

	/**
	 * Register Cart REST API routes.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function register_geolocation_routes() {
		$controller = new WC_REST_Geolocation_Controller();
		$controller->register_routes();
	} // END register_cart_route

} // END class

return new WC_Geolocation_Rest_API();
