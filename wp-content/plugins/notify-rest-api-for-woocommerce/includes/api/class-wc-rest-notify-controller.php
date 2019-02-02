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
class WC_REST_Notify_Controller {

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
	protected $rest_base = 'notify';

	/**
	 * Register the routes for cart.
	 *
	 * @access public
	 */
	protected $api_access_key = "AAAAjODuDnI:APA91bEWWQAO4e0T17ZITuRCcm6PAJ-_og4jL9bZMUN0FlsJvCmbIqYgSqvyDc4OO4s9M6sk1rr5aEUvsB4jsG_2aALUsP93dvgehDUzDYvohtKD87TsmRl44i7iKB-gW8t_Vc_vZM-Y";
	protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

	public function register_routes() {
		// View Cart - wc/v2/cart (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base .'/send_notification', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, 'send_push_notification' ),
			'args'     => array(
				'thumb' => array(
					'default' => null
				),
			),
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

		//////////////send otp for registration////////////////////////
	public function send_push_notification($request)
	{
		//print_r($request['message']);
		
		 $notification = [
            'title' =>$request['title'],
            'body' => $request['message'],
            'icon' =>'myIcon', 
            'sound' => 'mySound'
        ];
        $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

        $fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to'        => $request['device_id'], //single token
            'notification' => $notification,
            'data' => $extraNotificationData
        ];

        $headers = [
            'Authorization: key=' . $this->api_access_key,
            'Content-Type: application/json'
        ];


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
		  //echo "cURL Error #:" . $err;
		  //throw new WC_REST_Exception( 'woocommerce_rest_send_otp_fail', __( 'Cannot send otp.', 'woocommerce' ), 400 );
			$response = new stdClass();
		    $response->code     = 'failure';
		    $response->message  = 'Cannot send notification';
		    $response->data  = $err;
		    return new WP_REST_Response( $response , 400 );
		} else {
		  
		 // $response = $this->prepare_item_for_response( $response, $request );
			$response = new stdClass();
		    $response->code     = 'success';
		    $response->message  = 'Notification sent successfully';
		    $response->data 	= json_decode($result);
		    return new WP_REST_Response( $response , 200 );
			//return $response;
		}
	}

} // END class
