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
class WC_REST_Multiaddress_V1_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'multiaddress';

	/**
	 * Register the routes for customers.
	 */
	public function register_routes() {
		


		register_rest_route( $this->namespace, '/' . $this->rest_base . '/add_ship_address', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_ship_address' )
				
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );


		register_rest_route( $this->namespace, '/' . $this->rest_base . '/get_ship_address', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_ship_address' )
				
			),
			'schema' => array( $this, 'get_public_batch_schema' ),
		) );


		register_rest_route( $this->namespace, '/' . $this->rest_base . '/resend_otp', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'resend_otp' ),
				'permission_callback' => array( $this, 'send_otp_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(

					'mobile' => array(
						'required' => true,
						'type'     => 'string',
						'description' => __( 'New user email address.', 'woocommerce' ),
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

	public function add_ship_address($request){

		///validate user
		$response = new stdClass();
		$id        = (int) $request['customer_id'];
		$user_data = get_userdata( $id );

		if ( empty( $id ) || empty(  $user_data->data->ID ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$ship_add = array(
						'label' => $request['label'],
						'shipping_first_name' => $request['shipping_first_name'],
						'shipping_last_name' => $request['shipping_last_name'],
						'shipping_country' => $request['shipping_country'],
						'shipping_address_1' => $request['shipping_address_1'],
						'shipping_address_2' => $request['shipping_address_2'],
						'shipping_city' => $request['shipping_city'],
						'shipping_state' => $request['shipping_state'],
						'shipping_postcode' => $request['shipping_postcode'],
						'shipping_address_is_default' => $request['shipping_address_is_default']
						 );
		
		
		////get users previous address 
		$otherAddr = get_user_meta( $user_data->data->ID, 'wc_multiple_shipping_addresses', true );
		
		////add them to current data
		if(count($otherAddr)==0){
			
			$addresses = $ship_add;
			
		}else{
			
			$otherAddr[] = $ship_add;
			$addresses = $otherAddr;
		}

		////update all the addresses
		if(update_user_meta( $user_data->data->ID, 'wc_multiple_shipping_addresses', $addresses )){
				$response->code     = 'success';
				$response->message  = 'Address Saved successfully';
				$error_code = 200;
		}else{
				$response->code     = 'error';
				$response->message  = 'Some issue occured';
				$error_code = 400;
		}
		return new WP_REST_Response( $response , $error_code );
		
	}


	public function get_ship_address($request){
		$id        = (int) $request['customer_id'];
		$user_data = get_userdata( $id );

		if ( empty( $id ) || empty( $user_data->ID ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}
	}
	//////////////send otp for registration////////////////////////
	public function send_reg_otp($request)
	{
		//print_r($request);exit;
		$authkey = $this->authkey;
		$message = urlencode($request['message']);
		$sender = $this->sender;
		$mobile = $request['mobile'];
		$curl = curl_init();
		
		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "http://control.msg91.com/api/sendotp.php?template=1&otp_length=4&authkey=".$authkey."&message=".$message."&sender=".$sender."&mobile=".$mobile,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "",
		  CURLOPT_SSL_VERIFYHOST => 0,
		  CURLOPT_SSL_VERIFYPEER => 0,
		));

		$result = curl_exec($curl);
		$err = curl_error($curl);
		
		curl_close($curl);
		if ($err) {
		  //echo "cURL Error #:" . $err;
		  //throw new WC_REST_Exception( 'woocommerce_rest_send_otp_fail', __( 'Cannot send otp.', 'woocommerce' ), 400 );
			$response = new stdClass();
		    $response->code     = 'failure';
		    $response->message  = 'Cannot send otp';
		    $response->data  = $err;
		    return new WP_REST_Response( $response , 400 );
		} else {
		  
		 // $response = $this->prepare_item_for_response( $response, $request );
			$response = new stdClass();
		    $response->code     = 'success';
		    $response->message  = 'Otp sent successfully';
		    $response->data 	= json_decode($result);
		    return new WP_REST_Response( $response , 200 );
			//return $response;
		}
	}



	//////////////verify otp for registration///////////////
	public function verify_otp($request){
		$authkey = $this->authkey;
		$sender = $this->sender;
		$mobile = $request['mobile'];
		$otp = $request['otp'];
		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://control.msg91.com/api/verifyRequestOTP.php?authkey=".$authkey."&mobile=".$mobile."&otp=".$otp,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => "",
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_HTTPHEADER => array(
		   "content-type: application/x-www-form-urlencoded"
		 	),
		));

		$result = curl_exec($curl);
		$err = curl_error($curl);
		$result_msg = json_decode($result);

		curl_close($curl);

		
		/*print_r($err);
		print_r($result_msg->type);exit;*/
		if ($err) {
		  //echo "cURL Error #:" . $err;
		  //throw new WC_REST_Exception( 'woocommerce_rest_send_otp_fail', __( 'Cannot send otp.', 'woocommerce' ), 400 );
			$response = new stdClass();
		    $response->code     = 'failure';
		    $response->message  = 'Invalid OTP';
		    $response->data  = $err;
		    return new WP_REST_Response( $response , 400 );
		} else {
			$response = new stdClass();

			if($result_msg->type=="error"){
				$response->code     = $result_msg->type;
		    	$response->message  = 'OTP verification failed';
		    	$response->data 	= json_decode($result);
		    	return new WP_REST_Response( $response , 200 );

			}else if($result_msg->type=="success"){
				$response->code     = $result_msg->type;
		    	$response->message  = 'OTP verified successfully';
		    	$response->data 	= json_decode($result);
		    	return new WP_REST_Response( $response , 200 );
			}
		 // $response = $this->prepare_item_for_response( $response, $request )
		    
		    
			//return $response;
		}
	}


	////////////////resend otp //////////////////////////////
	public function resend_otp($request){
		$authkey = $this->authkey;
		$sender = $this->sender;
		$mobile = $request['mobile'];
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "http://control.msg91.com/api/retryotp.php?authkey=".$authkey."&mobile=".$mobile."&retrytype=text",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "",
		  CURLOPT_SSL_VERIFYHOST => 0,
		  CURLOPT_SSL_VERIFYPEER => 0,
		  CURLOPT_HTTPHEADER => array(
		    "content-type: application/x-www-form-urlencoded"
		  ),
		));

		$result = curl_exec($curl);
		$err = curl_error($curl);
		$result_msg = json_decode($result);


		

		curl_close($curl);

		if ($err) {
		  //echo "cURL Error #:" . $err;
		  //throw new WC_REST_Exception( 'woocommerce_rest_send_otp_fail', __( 'Cannot send otp.', 'woocommerce' ), 400 );
			$response = new stdClass();
		    $response->code     = 'failure';
		    $response->message  = 'Cannot send otp';
		    $response->data  = $err;
		    return new WP_REST_Response( $response , 400 );
		} else {
		  
		 // $response = $this->prepare_item_for_response( $response, $request );
			$response = new stdClass();
		    $response->code     = 'success';
		    $response->message  = $result_msg->message;
		    $response->data 	= json_decode($result);
		    return new WP_REST_Response( $response , 200 );
			//return $response;
		}


	}



    	//////////////send otp for registration////////////////////////
	public function send_transact_sms($request)
	{
		$authkey = $this->authkey;
		$message = urlencode($request['message']);
		$sender = $this->sender;
		$mobile = $request['mobile'];
		$curl = curl_init();
		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "http://api.msg91.com/api/v2/sendsms?message=".$message."&encrypt=SOCKET&authkey=".$authkey."&mobiles=".$mobile."&route=4&sender=".$sender."&country=91",

		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "{ \"sender\": \"$sender\", \"route\": \"4\", \"country\": \"91\", \"sms\": [ { \"message\": \"$message\", \"to\": [ \"$mobile\"] } ] }",
		
		  CURLOPT_SSL_VERIFYHOST => 0,
		  CURLOPT_SSL_VERIFYPEER => 0,
		  CURLOPT_HTTPHEADER => array(
		    "authkey: ".$authkey,
		    "content-type: application/json"
		  ),
		));

		$result = curl_exec($curl);
		$err = curl_error($curl);
		$result_msg = json_decode($result);		
		curl_close($curl);
		if ($err) {
		  //echo "cURL Error #:" . $err;
		  //throw new WC_REST_Exception( 'woocommerce_rest_send_otp_fail', __( 'Cannot send otp.', 'woocommerce' ), 400 );
			$response = new stdClass();
		    $response->code     = 'failure';
		    $response->message  = 'Cannot send message';
		    $response->data  = $err;
		    return new WP_REST_Response( $response , 400 );
		} else {
		  
		 // $response = $this->prepare_item_for_response( $response, $request );
			$response = new stdClass();
		    $response->code     = $result_msg->type;
		    $response->message  = $result_msg->message;
		    $response->data 	= json_decode($result);
		    return new WP_REST_Response( $response , 200 );
			//return $response;
		}
	}



	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_user_permissions( 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access create customers.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_user_permissions( 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$id = (int) $request['id'];

		if ( ! wc_rest_check_user_permissions( 'read', $id ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access update a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function update_item_permissions_check( $request ) {
		$id = (int) $request['id'];

		if ( ! wc_rest_check_user_permissions( 'edit', $id ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access delete a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$id = (int) $request['id'];

		if ( ! wc_rest_check_user_permissions( 'delete', $id ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access batch create, update and delete items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function batch_items_permissions_check( $request ) {
		//echo 123;exit;
		if ( ! wc_rest_check_user_permissions( 'batch' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	public function send_otp_permissions_check( $request ) {
		//echo 123;exit;
		if ( ! wc_rest_check_user_permissions( 'batch' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all customers.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$prepared_args = array();
		$prepared_args['exclude'] = $request['exclude'];
		$prepared_args['include'] = $request['include'];
		$prepared_args['order']   = $request['order'];
		$prepared_args['number']  = $request['per_page'];
		if ( ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
		}
		$orderby_possibles = array(
			'id'              => 'ID',
			'include'         => 'include',
			'name'            => 'display_name',
			'registered_date' => 'registered',
		);
		$prepared_args['orderby'] = $orderby_possibles[ $request['orderby'] ];
		$prepared_args['search']  = $request['search'];

		if ( '' !== $prepared_args['search'] ) {
			$prepared_args['search'] = '*' . $prepared_args['search'] . '*';
		}

		// Filter by email.
		if ( ! empty( $request['email'] ) ) {
			$prepared_args['search']         = $request['email'];
			$prepared_args['search_columns'] = array( 'user_email' );
		}

		// Filter by role.
		if ( 'all' !== $request['role'] ) {
			$prepared_args['role'] = $request['role'];
		}

		/**
		 * Filter arguments, before passing to WP_User_Query, when querying users via the REST API.
		 *
		 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
		 *
		 * @param array           $prepared_args Array of arguments for WP_User_Query.
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( 'woocommerce_rest_customer_query', $prepared_args, $request );

		$query = new WP_User_Query( $prepared_args );

		$users = array();
		foreach ( $query->results as $user ) {
			$data = $this->prepare_item_for_response( $user, $request );
			$users[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $users );

		// Store pagination values for headers then unset for count query.
		$per_page = (int) $prepared_args['number'];
		$page = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

		$prepared_args['fields'] = 'ID';

		$total_users = $query->get_total();
		if ( $total_users < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $prepared_args['number'] );
			unset( $prepared_args['offset'] );
			$count_query = new WP_User_Query( $prepared_args );
			$total_users = $count_query->get_total();
		}
		$response->header( 'X-WP-Total', (int) $total_users );
		$max_pages = ceil( $total_users / $per_page );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Create a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		try {
			if ( ! empty( $request['id'] ) ) {
				throw new WC_REST_Exception( 'woocommerce_rest_customer_exists', __( 'Cannot create existing resource.', 'woocommerce' ), 400 );
			}

			// Sets the username.
			$request['username'] = ! empty( $request['username'] ) ? $request['username'] : '';

			// Sets the password.
			$request['password'] = ! empty( $request['password'] ) ? $request['password'] : '';

			// Create customer.
			$customer = new WC_Customer;
			$customer->set_username( $request['username'] );
			$customer->set_password( $request['password'] );
			$customer->set_email( $request['email'] );
			$this->update_customer_meta_fields( $customer, $request );
			$customer->save();

			if ( ! $customer->get_id() ) {
				throw new WC_REST_Exception( 'woocommerce_rest_cannot_create', __( 'This resource cannot be created.', 'woocommerce' ), 400 );
			}

			$user_data = get_userdata( $customer->get_id() );
			$this->update_additional_fields_for_object( $user_data, $request );

			/**
			 * Fires after a customer is created or updated via the REST API.
			 *
			 * @param WP_User         $user_data Data used to create the customer.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating customer, false when updating customer.
			 */
			do_action( 'woocommerce_rest_insert_customer', $user_data, $request, true );

			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $user_data, $request );
			$response = rest_ensure_response( $response );
			$response->set_status( 201 );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $customer->get_id() ) ) );

			return $response;
		} catch ( Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id        = (int) $request['id'];
		$user_data = get_userdata( $id );

		if ( empty( $id ) || empty( $user_data->ID ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$customer = $this->prepare_item_for_response( $user_data, $request );
		$response = rest_ensure_response( $customer );

		return $response;
	}

	/**
	 * Update a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		try {
			$id       = (int) $request['id'];
			$customer = new WC_Customer( $id );

			if ( ! $customer->get_id() ) {
				throw new WC_REST_Exception( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), 400 );
			}

			if ( ! empty( $request['email'] ) && email_exists( $request['email'] ) && $request['email'] !== $customer->get_email() ) {
				throw new WC_REST_Exception( 'woocommerce_rest_customer_invalid_email', __( 'Email address is invalid.', 'woocommerce' ), 400 );
			}

			if ( ! empty( $request['username'] ) && $request['username'] !== $customer->get_username() ) {
				throw new WC_REST_Exception( 'woocommerce_rest_customer_invalid_argument', __( "Username isn't editable.", 'woocommerce' ), 400 );
			}

			// Customer email.
			if ( isset( $request['email'] ) ) {
				$customer->set_email( sanitize_email( $request['email'] ) );
			}

			// Customer password.
			if ( isset( $request['password'] ) ) {
				$customer->set_password( wc_clean( $request['password'] ) );
			}

			$this->update_customer_meta_fields( $customer, $request );
			$customer->save();

			$user_data = get_userdata( $customer->get_id() );
			$this->update_additional_fields_for_object( $user_data, $request );

			if ( ! is_user_member_of_blog( $user_data->ID ) ) {
				$user_data->add_role( 'customer' );
			}

			/**
			 * Fires after a customer is created or updated via the REST API.
			 *
			 * @param WP_User         $customer  Data used to create the customer.
			 * @param WP_REST_Request $request   Request object.
			 * @param boolean         $creating  True when creating customer, false when updating customer.
			 */
			do_action( 'woocommerce_rest_insert_customer', $user_data, $request, false );

			$request->set_param( 'context', 'edit' );
			$response = $this->prepare_item_for_response( $user_data, $request );
			$response = rest_ensure_response( $response );
			return $response;
		} catch ( Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Delete a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id       = (int) $request['id'];
		$reassign = isset( $request['reassign'] ) ? absint( $request['reassign'] ) : null;
		$force    = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Customers do not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$user_data = get_userdata( $id );
		if ( ! $user_data ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource id.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $reassign ) ) {
			if ( $reassign === $id || ! get_userdata( $reassign ) ) {
				return new WP_Error( 'woocommerce_rest_customer_invalid_reassign', __( 'Invalid resource id for reassignment.', 'woocommerce' ), array( 'status' => 400 ) );
			}
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $user_data, $request );

		/** Include admin customer functions to get access to wp_delete_user() */
		require_once ABSPATH . 'wp-admin/includes/user.php';

		$customer = new WC_Customer( $id );

		if ( ! is_null( $reassign ) ) {
			$result = $customer->delete_and_reassign( $reassign );
		} else {
			$result = $customer->delete();
		}

		if ( ! $result ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'The resource cannot be deleted.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a customer is deleted via the REST API.
		 *
		 * @param WP_User          $user_data User data.
		 * @param WP_REST_Response $response  The response returned from the API.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'woocommerce_rest_delete_customer', $user_data, $response, $request );

		return $response;
	}

	/**
	 * Prepare a single customer output for response.
	 *
	 * @param  WP_User          $user_data User object.
	 * @param  WP_REST_Request  $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $user_data, $request ) {
		$customer    = new WC_Customer( $user_data->ID );
		$_data       = $customer->get_data();
		$last_order  = wc_get_customer_last_order( $customer->get_id() );
		$format_date = array( 'date_created', 'date_modified' );

		// Format date values.
		foreach ( $format_date as $key ) {
			$_data[ $key ] = $_data[ $key ] ? wc_rest_prepare_date_response( $_data[ $key ] ) : null; // v1 API used UTC.
		}

		$data = array(
			'id'            => $_data['id'],
			'date_created'  => $_data['date_created'],
			'date_modified' => $_data['date_modified'],
			'email'         => $_data['email'],
			'first_name'    => $_data['first_name'],
			'last_name'     => $_data['last_name'],
			'username'      => $_data['username'],
			'last_order'    => array(
				'id'   => is_object( $last_order ) ? $last_order->get_id() : null,
				'date' => is_object( $last_order ) ? wc_rest_prepare_date_response( $last_order->get_date_created() ) : null, // v1 API used UTC.
			),
			'orders_count'  => $customer->get_order_count(),
			'total_spent'   => $customer->get_total_spent(),
			'avatar_url'    => $customer->get_avatar_url(),
			'billing'       => $_data['billing'],
			'shipping'      => $_data['shipping'],
		);

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
	 * @param WC_Customer $customer
	 * @param WP_REST_Request $request
	 */
	protected function update_customer_meta_fields( $customer, $request ) {
		$schema = $this->get_item_schema();

		// Customer first name.
		if ( isset( $request['first_name'] ) ) {
			$customer->set_first_name( wc_clean( $request['first_name'] ) );
		}

		// Customer last name.
		if ( isset( $request['last_name'] ) ) {
			$customer->set_last_name( wc_clean( $request['last_name'] ) );
		}

		// Customer billing address.
		if ( isset( $request['billing'] ) ) {
			foreach ( array_keys( $schema['properties']['billing']['properties'] ) as $field ) {
				if ( isset( $request['billing'][ $field ] ) && is_callable( array( $customer, "set_billing_{$field}" ) ) ) {
					$customer->{"set_billing_{$field}"}( $request['billing'][ $field ] );
				}
			}
		}

		// Customer shipping address.
		if ( isset( $request['shipping'] ) ) {
			foreach ( array_keys( $schema['properties']['shipping']['properties'] ) as $field ) {
				if ( isset( $request['shipping'][ $field ] ) && is_callable( array( $customer, "set_shipping_{$field}" ) ) ) {
					$customer->{"set_shipping_{$field}"}( $request['shipping'][ $field ] );
				}
			}
		}
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_User $customer Customer object.
	 * @return array Links for the given customer.
	 */
	protected function prepare_links( $customer ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $customer->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Customer's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'customer',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( 'The date the customer was created, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( 'The date the customer was last modified, as GMT.', 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'email' => array(
					'description' => __( 'The email address for the customer.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
				),
				'first_name' => array(
					'description' => __( 'Customer first name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'last_name' => array(
					'description' => __( 'Customer last name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'username' => array(
					'description' => __( 'Customer login name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_user',
					),
				),
				'password' => array(
					'description' => __( 'Customer password.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'last_order' => array(
					'description' => __( 'Last order data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id' => array(
							'description' => __( 'Last order ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date' => array(
							'description' => __( 'The date of the customer last order, as GMT.', 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'orders_count' => array(
					'description' => __( 'Quantity of orders made by the customer.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_spent' => array(
					'description' => __( 'Total amount spent.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'avatar_url' => array(
					'description' => __( 'Avatar URL.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing' => array(
					'description' => __( 'List of billing address data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties' => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address line 1.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address line 2.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'ISO code of the country.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email' => array(
							'description' => __( 'Email address.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone' => array(
							'description' => __( 'Phone number.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping' => array(
					'description' => __( 'List of shipping address data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties' => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address line 1.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address line 2.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'ISO code of the country.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

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
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['include'] = array(
			'description'       => __( 'Limit result set to specific IDs.', 'woocommerce' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['offset'] = array(
			'description'        => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order'] = array(
			'default'            => 'asc',
			'description'        => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'enum'               => array( 'asc', 'desc' ),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'default'            => 'name',
			'description'        => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'enum'               => array(
				'id',
				'include',
				'name',
				'registered_date',
			),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['email'] = array(
			'description'        => __( 'Limit result set to resources with a specific email.', 'woocommerce' ),
			'type'               => 'string',
			'format'             => 'email',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['role'] = array(
			'description'        => __( 'Limit result set to resources with a specific role.', 'woocommerce' ),
			'type'               => 'string',
			'default'            => 'customer',
			'enum'               => array_merge( array( 'all' ), $this->get_role_names() ),
			'validate_callback'  => 'rest_validate_request_arg',
		);
		return $params;
	}
}