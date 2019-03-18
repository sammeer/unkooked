<?php
/**
 * REST API Cart controller
 *
 * Handles requests to the /cart endpoint.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  Cart REST API for WooCommerce/API
 * @since    1.0.0
 * @version  1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Cart controller class.
 *
 * @package WooCommerce Cart REST API/API
 */
class WC_REST_Geolocation_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'geolocation';

	/**
	 * Register the routes for cart.
	 *
	 * @access public
	 */
	protected $api_access_key = "AAAAjODuDnI:APA91bEWWQAO4e0T17ZITuRCcm6PAJ-_og4jL9bZMUN0FlsJvCmbIqYgSqvyDc4OO4s9M6sk1rr5aEUvsB4jsG_2aALUsP93dvgehDUzDYvohtKD87TsmRl44i7iKB-gW8t_Vc_vZM-Y";
	protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

	public function register_routes() {
		// View Cart - wc/v2/cart (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'getGeolocs' )
		));

		
	} // register_routes()

	/**
	 * Get cart.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 1.0.6
	 * @param   array $data
	 * @return  WP_REST_Response
	 */

	public function getGeolocs(){

		$result  = array(
						'lodhamajiwada' => array(
												"serviceable" => true,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "50000"
												),
						'runwalgerdern' => array(
												"serviceable" => true,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "1000"
												),
						'rustomjee' => array(
												"serviceable" => true,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "500"
												),
						'prestigegarden' => array(
												"serviceable" => true,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "1500"
												)
						 );

		$result2[]  = array('lodhamajiwada' => array(
												"serviceable" => true,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "50000"
												)
												
						);
		$result2[] = array('runwalgerdern' => array(
												"serviceable" => true,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "50000"
												)
												);

		$result2[] = array('rustomjee' => array(
												"serviceable" => false,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "50000"
												)
												);

		$result2[] = array('prestigegarden' => array(
												"serviceable" => true,
												"location" => array(
															"lat" => "19.2136372",
															"long"=> "72.9844176"
															),
												"radius"	=> "50000"
												)
												);
												
						
		//print_r($result);
		$response = new stdClass();
		$response->code     = 'success';
		$response->message  = 'Geolocation sent successfully';
		$response->data 	= $result2;
		return new WP_REST_Response( $response , 200 );
	}

	

} // END class
