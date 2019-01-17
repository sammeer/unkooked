<?php
/**
 * REST API Customers controller
 *
 * Handles requests to the /customers endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Customers controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Customers_V1_Controller
 */
class WC_REST_Multiaddress_Controller extends WC_REST_Multiaddress_V1_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Get formatted item data.
	 *
	 * @since  3.0.0
	 * @param  WC_Data $object WC_Data instance.
	 * @return array
	 */
	protected function get_formatted_item_data( $object ) {
		$data        = $object->get_data();
		$format_date = array( 'date_created', 'date_modified' );

		// Format date values.
		foreach ( $format_date as $key ) {
			$datetime              = $data[ $key ];
			$data[ $key ]          = wc_rest_prepare_date_response( $datetime, false );
			$data[ $key . '_gmt' ] = wc_rest_prepare_date_response( $datetime );
		}

		return array(
			'id'                 => $object->get_id(),
			'date_created'       => $data['date_created'],
			'date_created_gmt'   => $data['date_created_gmt'],
			'date_modified'      => $data['date_modified'],
			'date_modified_gmt'  => $data['date_modified_gmt'],
			'email'              => $data['email'],
			'first_name'         => $data['first_name'],
			'last_name'          => $data['last_name'],
			'role'               => $data['role'],
			'username'           => $data['username'],
			'billing'            => $data['billing'],
			'shipping'           => $data['shipping'],
			'is_paying_customer' => $data['is_paying_customer'],
			'orders_count'       => $object->get_order_count(),
			'total_spent'        => $object->get_total_spent(),
			'avatar_url'         => $object->get_avatar_url(),
			'meta_data'          => $data['meta_data'],
		);
	}

	/**
	 * Prepare a single customer output for response.
	 *
	 * @param  WP_User         $user_data User object.
	 * @param  WP_REST_Request $request   Request object.
	 * @return WP_REST_Response $response  Response data.
	 */
	public function prepare_item_for_response( $user_data, $request ) {
		$customer = new WC_Customer( $user_data->ID );
		$data     = $this->get_formatted_item_data( $customer );
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $user_data ) );

		/**
		 * Filter customer data returned from the REST API.
		 *
		 * @param WP_REST_Response $response   The response object.
		 * @param WP_User          $user_data  User object used to create response.
		 * @param WP_REST_Request  $request    Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_customer', $response, $user_data, $request );
	}

	/**
	 * Update customer meta fields.
	 *
	 * @param WC_Customer     $customer Customer data.
	 * @param WP_REST_Request $request  Request data.
	 */


	/**
	 * Get the Customer's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
}
