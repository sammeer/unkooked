<?php
/**
 * REST API Customers controller
 *
 * Handles requests to the /customers endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Customers controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Notification_V1_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';
	protected $api_access_key = "AAAA85JpDXM:APA91bGBRdP2NXczZW0AHvYBQnA2OzglADRC1Rhmqx29QmnGUo-kNEXYXHNU_5QgNCqZKVVGRA7Lb9GMCC9NvyMKrcy1ZTw58S7zD1fjU_AVGFjSrJtPdMsfQrY69hoy1tWmEL2ASXQJ";
	protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'notification';

	/**
	 * Register the routes for customers.
	 */
	public function register_routes() {
		


		register_rest_route( $this->namespace, '/' . $this->rest_base . '/send_notification', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'send_push_notification' ),
				'permission_callback' => array(),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(

					'message' => array(
						'required' => true,
						'type'     => 'string',
						'description' => __( 'New user email address.', 'woocommerce' ),
					),
					'title' => array(
						'required' => 'no' === get_option( 'woocommerce_registration_generate_username', 'yes' ),
						'description' => __( 'New user username.', 'woocommerce' ),
						'type'     => 'string',
					),
					'device_id' => array(
						'required' => 'no' === get_option( 'woocommerce_registration_generate_password', 'no' ),
						'description' => __( 'New user password.', 'woocommerce' ),
						'type'     => 'string',
					),
					
					) ),
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );





	}

	/**
	 * Check whether a given request has permission to read customers.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
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

	public function send_push_notification_customer($request)
	{
		////get user device id and type
		global $wpdb;
		$flag =  $request['flag'];
		$order_id = $request['order_id'];
		$user_type = $request['user_type'];
		$table_name = $wpdb->prefix . "notofication_msgs";
		//echo "SELECT * FROM $table_name where user_type=1 AND flag = $flag";
		$message_data = $wpdb->get_results( "SELECT * FROM $table_name where user_type=$user_type AND flag = '$flag'" );
		$user_data = get_userdata( $request['customer_id'] );
		//print_r($message_data[0]->message);exit;
		$title = $message_data[0]->title;
		$message_to_send = $message_data[0]->message;
		$message_to_send = str_replace("##order_id##","#".$order_id,$message_to_send);
		//echo $message_to_send;exit;
		
		 $notification = [
            'title' =>$title,
            'body' => $message_to_send,
            'icon' =>'myIcon', 
            'sound' => 'mySound'
        ];
        $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

        $fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to'        => $user_data->device_id, //single token
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


	public function send_push_notification_admin($request)
	{
		////get user device id and type
		global $wpdb;
		$flag =  $request['flag'];
		$order_id = $request['order_id'];
		$user_type = $request['user_type'];

		///find users with admin roles

		$args = array('role' => 'author');
		$users = get_users( $args );
		
		$device_ids = array();
		foreach($users as $user){
			$device_ids[] = $user->device_id;
		}
		/*print_r($device_ids);exit;*/

		$table_name = $wpdb->prefix . "notofication_msgs";
		//echo "SELECT * FROM $table_name where user_type=1 AND flag = $flag";
		$message_data = $wpdb->get_results( "SELECT * FROM $table_name where user_type=$user_type AND flag = '$flag'" );
		

		$title = $message_data[0]->title;
		$message_to_send = $message_data[0]->message;
		$message_to_send = str_replace("##order_id##","#".$order_id,$message_to_send);
		//echo $message_to_send;exit;
		
		 $notification = [
            'title' =>$title,
            'body' => $message_to_send,
            'icon' =>'myIcon', 
            'sound' => 'mySound'
        ];
        $extraNotificationData = ["message" => $notification,"moredata" =>'dd'];

        $fcmNotification = [
            //'registration_ids' => $tokenList, //multple token array
            'to'        => json_encode($device_ids), //single token
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
        print_r($result);
        print_r($err);exit;
        
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

	public function get_items_permissions_check( $request ) {}

	/**
	 * Check if a given request has access create customers.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {}

	/**
	 * Check if a given request has access to read a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {}

	/**
	 * Check if a given request has access update a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {}

	/**
	 * Check if a given request has access delete a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {}

	/**
	 * Check if a given request has access batch create, update and delete items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function batch_items_permissions_check( $request ) {}


	public function send_otp_permissions_check( $request ) {}

	/**
	 * Get all customers.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {}

	/**
	 * Create a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {}

	/**
	 * Get a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {}

	/**
	 * Update a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {}

	/**
	 * Delete a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {}

	/**
	 * Prepare a single customer output for response.
	 *
	 * @param  WP_User          $user_data User object.
	 * @param  WP_REST_Request  $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $user_data, $request ) {}

	/**
	 * Update customer meta fields.
	 *
	 * @param WC_Customer $customer
	 * @param WP_REST_Request $request
	 */
	protected function update_customer_meta_fields( $customer, $request ) {}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_User $customer Customer object.
	 * @return array Links for the given customer.
	 */
	protected function prepare_links( $customer ) {}

	/**
	 * Get the Customer's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {}

	/**
	 * Get role names.
	 *
	 * @return array
	 */
	protected function get_role_names() {
		global $wp_roles;

		return array_keys( $wp_roles->role_names );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {}
}
