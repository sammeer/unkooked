<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customizer Setup
 * Heavily borrowed from rightpress Decorator
 */
if ( ! class_exists( 'Kadence_Woomail_Preview' ) ) {

	class Kadence_Woomail_Preview {
		// WooCommerce email classes
		public static $current_order            = null;
		public static $current_product_title    = null;
		public static $current_recipients       = null;
		public static $email_types_class_names  = array(
			'new_order'                         => 'WC_Email_New_Order',
			'cancelled_order'                   => 'WC_Email_Cancelled_Order',
			'customer_processing_order'         => 'WC_Email_Customer_Processing_Order',
			'customer_completed_order'          => 'WC_Email_Customer_Completed_Order',
			'customer_refunded_order'           => 'WC_Email_Customer_Refunded_Order',
			'customer_on_hold_order'            => 'WC_Email_Customer_On_Hold_Order',
			'customer_invoice'                  => 'WC_Email_Customer_Invoice',
			'failed_order'                      => 'WC_Email_Failed_Order',
			'customer_new_account'              => 'WC_Email_Customer_New_Account',
			'customer_note'                     => 'WC_Email_Customer_Note',
			'customer_reset_password'           => 'WC_Email_Customer_Reset_Password',
			// WooCommerce Subscriptions Plugin
			'new_renewal_order'                 => 'WCS_Email_New_Renewal_Order',
			'customer_processing_renewal_order' => 'WCS_Email_Processing_Renewal_Order',
			'customer_completed_renewal_order'  => 'WCS_Email_Completed_Renewal_Order',
			'customer_completed_switch_order'   => 'WCS_Email_Completed_Switch_Order',
			'customer_renewal_invoice'          => 'WCS_Email_Customer_Renewal_Invoice',
			'customer_payment_retry'            => 'WCS_Email_Customer_Payment_Retry',
			'cancelled_subscription'            => 'WCS_Email_Cancelled_Subscription',
			// Waitlist Plugin
			'woocommerce_waitlist_mailout'      => 'Pie_WCWL_Waitlist_Mailout',
		);
		public static $email_types_order_status = array(
			'new_order'                         => 'processing',
			'cancelled_order'                   => 'cancelled',
			'customer_processing_order'         => 'processing',
			'customer_completed_order'          => 'completed',
			'customer_refunded_order'           => 'refunded',
			'customer_on_hold_order'            => 'on-hold',
			'customer_invoice'                  => 'processing',
			'failed_order'                      => 'failed',
			'customer_new_account'              => null,
			'customer_note'                     => 'processing',
			'customer_reset_password'           => null,
			// WooCommerce Subscriptions Plugin.
			'new_renewal_order'                 => 'processing',
			'customer_processing_renewal_order' => 'processing',
			'customer_completed_renewal_order'  => 'completed',
			'customer_completed_switch_order'   => 'completed',
			'customer_renewal_invoice'          => 'failed',
			'customer_payment_retry'            => 'on-hold',
			'cancelled_subscription'            => 'cancelled',
			// Waitlist Plugin
			'woocommerce_waitlist_mailout'      => null,
		);
		/**
		* @var null
		*/
		private static $instance = null;

		/**
		 * Instance Control
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Class constructor
		 *
		 * @access public
		 * @return void
		 */
		public function __construct() {
			// Set up preview.
			add_action( 'parse_request', array( $this, 'set_up_preview' ) );
		}

		/**
		 * Set up preview
		 *
		 * @access public
		 * @return void
		 */
		public function set_up_preview() {
			// Make sure this is own preview request.
			if ( ! Kadence_Woomail_Designer::is_own_preview_request() ) {
				return;
			}
			// Load main view.
			include KT_WOOMAIL_PATH . 'preview.php';

			// Do not load any further elements.
			exit;
		}

		/**
		 * Get the email order status
		 *
		 */
		public static function get_email_order_status( $email_template ) {
			$order_status = apply_filters( 'kadence_woomail_email_type_order_status_array', Kadence_Woomail_Preview::$email_types_order_status );
			if ( isset( $order_status[ $email_template ] ) ) {
				return $order_status[ $email_template ];
			} else {
				return false;
			}
		}
		/**
		 * Get the email class name
		 *
		 */
		public static function get_email_class_name( $email_template ) {
			$class_names = apply_filters( 'kadence_woomail_email_type_class_name_array', Kadence_Woomail_Preview::$email_types_class_names );
			if ( isset( $class_names[ $email_template ] ) ) {
				return $class_names[ $email_template ];
			} else {
				return 'processing';
			}
		}
		/**
		 * Get the email content
		 *
		 */
		public static function get_preview_email( $send_email = false, $email_addresses = null ) {
			// Load WooCommerce emails.
			$wc_emails      = WC_Emails::instance();
			$emails         = $wc_emails->get_emails();
			$email_template = Kadence_Woomail_Customizer::opt( 'email_type' );
			$preview_id     = Kadence_Woomail_Customizer::opt( 'preview_order_id' );
			$email_type     = Kadence_Woomail_Preview::get_email_class_name( $email_template );
			if ( false === $email_type ) {
				return false;
			}
			$order_status = Kadence_Woomail_Preview::get_email_order_status( $email_template );

			//$order_status = Kadence_Woomail_Preview::$email_types_order_status[$email_template];

			if ( 'customer_invoice' == $email_template ) {
				$invoice_paid = Kadence_Woomail_Customizer::opt( 'customer_invoice_switch' );
				if ( ! $invoice_paid ) {
					$order_status = 'pending';
				}
			}
			if ( 'customer_refunded_order' == $email_template ) {
				$partial_preview = Kadence_Woomail_Customizer::opt( 'customer_refunded_order_switch' );
				if ( ! $partial_preview ) {
					$partial_status = true;
				} else {
					$partial_status = false;
				}
			}
			// Reference email.
			if ( isset( $emails[ $email_type ] ) && is_object( $emails[ $email_type ] ) ) {
				$email = $emails[ $email_type ];
			};

			// Get an order
			$order               = Kadence_Woomail_Preview::get_wc_order_for_preview( $order_status, $preview_id );
			self::$current_order = $order;

			if ( is_object( $order ) ) {
				// Get user ID from order, if guest get current user ID.
				if ( 0 === ( $user_id = (int) get_post_meta( $order->get_id(), '_customer_user', true ) ) ) {
					$user_id = get_current_user_id();
				}
			} else {
				$user_id = get_current_user_id();
			}
			// Get user object
			$user = get_user_by( 'id', $user_id );
			self::$current_product_title = 'Product Title Example';
			if ( 'woocommerce_waitlist_mailout' == $email_template ) {

				$product_id = -1;
				if ( is_object( $order ) ) {
					$items = $order->get_items();
					foreach ( $items as $item ) {
						$product_id = $item['product_id'];
						if ( null !== get_post( $product_id ) ) {
							break;
						}
					}
				}

				if ( null === get_post( $product_id ) ) {

					$args           = array(
						'posts_per_page' => 1,
						'orderby'        => 'date',
						'post_type'      => 'product',
						'post_status'    => 'publish',
					);
					$products_array = get_posts( $args );

					if ( isset( $products_array[0]->ID ) ) {
						$product_id = $products_array[0]->ID;
					}
				}
			}

			if ( isset( $email ) ) {
				// Make sure gateways are running in case the email needs to input content from them.
				WC()->payment_gateways();
				// Make sure shipping is running in case the email needs to input content from it.
				WC()->shipping();

				switch ( $email_template ) {
					/**
					 * WooCommerce (default transactional mails).
					 */
					case 'new_order':
					case 'cancelled_order':
					case 'customer_processing_order':
					case 'customer_completed_order':
					case 'customer_on_hold_order':
					case 'customer_invoice':
					case 'failed_order':
						$email->object               = $order;
						$email->find['order-date']   = '{order_date}';
						$email->find['order-number'] = '{order_number}';
						if ( is_object( $order ) ) {
							$email->replace['order-date']   = wc_format_datetime( $email->object->get_date_created() );
							$email->replace['order-number'] = $email->object->get_order_number();
							// Other properties
							$email->recipient = $email->object->get_billing_email();
						}
						break;
					case 'customer_refunded_order':
						$email->object               = $order;
						$email->partial_refund       = $partial_status;
						$email->find['order-date']   = '{order_date}';
						$email->find['order-number'] = '{order_number}';
						if ( is_object( $order ) ) {
							$email->replace['order-date']   = wc_format_datetime( $email->object->get_date_created() );
							$email->replace['order-number'] = $email->object->get_order_number();
							// Other properties
							$email->recipient = $email->object->get_billing_email();
						}
						break;
					case 'customer_new_account':
						$email->object             = $user;
						$email->user_pass          = '{user_pass}';
						$email->user_login         = stripslashes( $email->object->user_login );
						$email->user_email         = stripslashes( $email->object->user_email );
						$email->recipient          = $email->user_email;
						$email->password_generated = true;
						break;
					case 'customer_note':
						$email->object                  = $order;
						$email->customer_note           = __( 'Hello! This is an example note', 'kadence-woocommerce-email-designer' );
						$email->find['order-date']      = '{order_date}';
						$email->find['order-number']    = '{order_number}';
						$email->replace['order-date']   = wc_format_datetime( $email->object->get_date_created() );
						$email->replace['order-number'] = $email->object->get_order_number();
						// Other properties
						$email->recipient = $email->object->get_billing_email();
						break;
					case 'customer_reset_password':
						$email->object     = $user;
						$email->user_id    = $user_id;
						$email->user_login = $user->user_login;
						$email->user_email = stripslashes( $email->object->user_email );
						$email->reset_key  = '{{reset-key}}';
						$email->recipient  = stripslashes( $email->object->user_email );
						break;
					/**
					 * WooCommerce Subscriptions Plugin (from WooCommerce).
					 */
					case 'new_renewal_order':
					case 'new_switch_order':
					case 'customer_processing_renewal_order':
					case 'customer_completed_renewal_order':
					case 'customer_completed_switch_order':
					case 'customer_renewal_invoice':
						$email->object                  = $order;
						$email->find['order-date']      = '{order_date}';
						$email->find['order-number']    = '{order_number}';
						$email->replace['order-date']   = wc_format_datetime( $email->object->get_date_created() );
						$email->replace['order-number'] = $email->object->get_order_number();
						// Other properties
						$email->recipient = $email->object->get_billing_email();
						break;
					case 'cancelled_subscription':
						$subscription = false;
						if ( ! empty( $preview_id ) && 'mockup' != $preview_id ) {
							if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {
								$subscriptions_ids = wcs_get_subscriptions_for_order( $preview_id );
								// We get the related subscription for this order.
								if ( $subscriptions_ids ) {
									foreach ( $subscriptions_ids as $subscription_id => $subscription_obj ) {
										if ( $subscription_obj->order->id == $preview_id ) {
											$subscription = $subscription_obj;
											break; // Stop the loop).
										}
									}
								}
							}
						}
						if ( $subscription ) {
							$email->object = $subscription;
						} else {
							$email->object = 'subscription';
						}
						break;
					/**
					 * WooCommerce Wait-list Plugin (from WooCommerce).
					 */
					case 'woocommerce_waitlist_mailout':
						$email->object               = get_product( $product_id );
						$email->find[]               = '{product_title}';
						$email->replace[]            = $email->object->get_title();
						self::$current_product_title = $email->object->get_title();
						break;
					/**
					 * Everything else.
					 */
					default:
						$email->object = $order;
						// Allow unnamed emails preview to be filtered by plugin
						$email = apply_filters( 'kadence_woomail_preview_email_object', $email );
						break;
				}

				if ( true === $send_email && ! empty( $email_addresses ) ) {

					self::$current_recipients = $email_addresses;

					add_filter( 'woocommerce_email_recipient_' . $email->id, array( 'Kadence_Woomail_Preview', 'change_recipient' ), 99 );

					if ( $email->get_recipient() ) {
						$content = $email->send( $email->get_recipient(), $email->get_subject(), $email->get_content(), $email->get_headers(), $email->get_attachments() );
					}

					remove_filter( 'woocommerce_email_recipient_' . $email->id, array( 'Kadence_Woomail_Preview', 'change_recipient' ), 99 );

				} else {
					if ( false == $email->object ) {
						$content = '<div style="padding: 35px 40px; background-color: white;">' . __( 'This email type can not be previewed please try a differnet order or email type.', 'kadence-woocommerce-email-designer' ) . '</div>';
					} else if ( 'subscription' == $email->object ) {
						$content = '<div style="padding: 35px 40px; background-color: white;">' . __( 'This email type requires that an order containing a subscription be selected as the preview order.', 'kadence-woocommerce-email-designer' ) . '</div>';
					} else {
						// Get email content and apply styles.
						$content = $email->get_content();
						$content = $email->style_inline( $content );
						$content = apply_filters( 'woocommerce_mail_content', $content );

						if ( 'plain' === $email->email_type ) {
							$content = '<div style="padding: 35px 40px; background-color: white;">' . str_replace( "\n", '<br/>', $content ) . '</div>';
						}
					}
				}
			} else {
				$content = false;
			}

			return $content;
		}

		/**
		 * Change Recipient to a custom one.
		 *
		 * @access public
		 * @return string
		 */
		public static function change_recipient( $recipient ) {

			if ( ! empty( self::$current_recipients ) ) {
				$recipient = self::$current_recipients;
			} else {
				// Don't send if not set
				$recipient = '';
			}
			return $recipient;
		}
		/**
		 * Print preview email
		 *
		 * @access public
		 * @return void
		 */
		public static function print_preview_email() {

			$content = Kadence_Woomail_Preview::get_preview_email();
			if ( false == $content ) {
				echo __( 'An error occurred trying to load this email type. Make sure this email type is enabled or please try another type.', 'kadence-woocommerce-email-designer' );
			} elseif ( ! empty( $content ) ) {
				// Print email content
				echo $content;
				// Print live preview scripts in footer
				add_action( 'wp_footer', array( 'Kadence_Woomail_Preview', 'print_live_preview_scripts' ), 99 );
			}
		}

		/**
		 * send preview email
		 *
		 * @access public
		 * @return void
		 */
		public static function send_preview_email() {
			$content = Kadence_Woomail_Preview::get_preview_email();
			if ( ! empty( $content ) ) {
				// Print email content
				echo $content;
			}
		}

		/**
		* Get WooCommerce order for preview
		*
		* @access public
		* @param string $order_status
		* @return object
		*/
		public static function get_wc_order_for_preview( $order_status = null, $order_id = null ) {
			if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
				return wc_get_order( $order_id );
			} else {
				// Use mockup order (WC 2.7+)

				// Instantiate order object
				$order = new WC_Order();

				// Other order properties
				$order->set_props( array(
					'id'                 => 1,
					'status'             => ( null === $order_status ? 'processing' : $order_status ),
					'billing_first_name' => 'Sherlock',
					'billing_last_name'  => 'Holmes',
					'billing_company'    => 'Detectives Ltd.',
					'billing_address_1'  => '221B Baker Street',
					'billing_city'       => 'London',
					'billing_postcode'   => 'NW1 6XE',
					'billing_country'    => 'GB',
					'billing_email'      => 'sherlock@holmes.co.uk',
					'billing_phone'      => '02079304832',
					'date_created'       => date( 'Y-m-d H:i:s' ),
					'total'              => 24.90,
				) );

				// Item #1
				$order_item = new WC_Order_Item_Product();
				$order_item->set_props( array(
					'name'     => 'A Study in Scarlet',
					'subtotal' => '9.95',
					'sku'      => 'kwd_ex_1',
				) );
				$order->add_item( $order_item );

				// Item #2
				$order_item = new WC_Order_Item_Product();
				$order_item->set_props( array(
					'name'     => 'The Hound of the Baskervilles',
					'subtotal' => '14.95',
					'sku'      => 'kwd_ex_2',
				) );
				$order->add_item( $order_item );

				// Return mockup order
				return $order;
			}

		}

		/**
		* Print live preview scripts
		*
		* @access public
		* @return void
		*/
		public static function print_live_preview_scripts() {
			// Open container
			$scripts = '<script type="text/javascript">jQuery(document).ready(function() {';

			// Font family mapping
			$scripts .= 'var font_family_mapping = ' . json_encode( Kadence_Woomail_Settings::$font_family_mapping ) . ';';

			// Get information from email to use in placeholders.
			$scripts .= 'var pl_site_title = "' . get_bloginfo( 'name', 'display' ) . '";';
			$scripts .= 'var pl_year = "' . date( 'Y' ) . '";';
			if ( is_object( self::$current_order ) ) {
				$scripts .= 'var pl_order_date = "' . wc_format_datetime( self::$current_order->get_date_created() ) . '";';
				$scripts .= 'var pl_order_number = "' . self::$current_order->get_order_number() . '";';
				$scripts .= 'var pl_customer_first_name = "' . self::$current_order->get_billing_first_name() . '";';
				$scripts .= 'var pl_customer_last_name = "' . self::$current_order->get_billing_last_name() . '";';
				$scripts .= 'var pl_customer_full_name = "' . self::$current_order->get_formatted_billing_full_name() . '";';
			}
			$scripts .= 'var pl_product_title = "' . self::$current_product_title . '";';
			// Function to handle special cases.
			$scripts .= "function prepare(value, key, selector) {
					if (key === 'border_radius' && selector === '#template_header') {
						value = value.replace('!important', '').trim();
						value = value + ' ' + value + ' 0 0 !important';
					} else if (key === 'footer_padding' && selector === '#template_footer #credit') {
						value = '0 ' + value + ' ' + value + ' ' + value;
					}  else if (key === 'footer_content_text' && value !== '') {
						value = '<p>' + value + '</p>';
					} else if (key === 'shadow') {
						value = '0 ' + (value > 0 ? 1 : 0) + 'px ' + (value * 4) + 'px ' + value + 'px rgba(0,0,0,0.1) !important';
					} else if (key.match(/font_family$/)) {
						value = typeof font_family_mapping[value] !== 'undefined' ? font_family_mapping[value] : value;
					} else if (key === 'woocommerce_email_header_image') {
						value = '<p style=\"margin-top:0; margin-bottom:0;\"><img src=\"' + value + '\" style=\"border: none; display: inline; font-weight: bold; height: auto; line-height: 100%; outline: none; text-decoration: none; text-transform: capitalize;\" /></p>';
					} else if (value && value.includes('{site_title}') ) {
						value = value.replace('{site_title}', pl_site_title);
						if (value && value.includes('{year}') ) {
							value = value.replace('{year}', pl_year);
						}
					} else if (value && value.includes('{order_date}') ) {
						value = value.replace('{order_date}', pl_order_date);
					} else if (value && value.includes('{order_number}') ) {
						value = value.replace('{order_number}', pl_order_number);
					} else if (value && value.includes('{customer_first_name}') ) {
						value = value.replace('{customer_first_name}', pl_customer_first_name);
					} else if (value && value.includes('{customer_last_name}') ) {
						value = value.replace('{customer_last_name}', pl_customer_last_name);
					} else if (value && value.includes('{customer_full_name}') ) {
						value = value.replace('{customer_full_name}', pl_customer_full_name);
					} else if (value && value.includes('{product_title}') ) {
						value = value.replace('{product_title}', pl_product_title);
					} else if (value && value.includes('{year}') ) {
						value = value.replace('{year}', pl_year);
					}
					return value;
				}";
			// Get CSS suffixes
			$scripts .= 'var suffixes = ' . json_encode( Kadence_Woomail_Customizer::get_css_suffix() ) . ';';

			// Go through kadence woomail settings
			foreach ( Kadence_Woomail_Settings::get_settings() as $setting_key => $setting ) {

				// No live method
				if ( ! isset( $setting['live_method'] ) ) {
					continue;
				}

				// Iterate over selectors
				if ( in_array( $setting['live_method'], array( 'css', 'property' ) ) && ! empty( $setting['selectors'] ) ) {
					foreach ( $setting['selectors'] as $selector => $properties ) {

						// Iterate over properties
						foreach ( $properties as $property ) {

							// CSS value change
							if ( ! isset( $setting['live_method'] ) || $setting['live_method'] === 'css' ) {
								$scripts .= "wp.customize('kt_woomail[$setting_key]', function(value) {
								value.bind(function(newval) {
								newval = newval + (typeof suffixes['$setting_key'] !== 'undefined' ? suffixes['$setting_key'] : '');
								newval = prepare(newval, '$setting_key', '$selector');
								jQuery('$selector').css('$property', '').attr('style', function(i, s) { return (s||'') + '$property: ' + newval + ';' });
								});
								});";
							}

							// DOM object property
							if ( $setting['live_method'] === 'property' ) {
								$scripts .= "wp.customize('kt_woomail[$setting_key]', function(value) {
								value.bind(function(newval) {
								newval = newval + (typeof suffixes['$setting_key'] !== 'undefined' ? suffixes['$setting_key'] : '');
								newval = prepare(newval, '$setting_key', '$selector');
								jQuery('$selector').prop('$property', newval);
								});
								});";
							}
						}
					}
				}

				// HTML Replace
				if ( $setting['live_method'] === 'replace' && ! empty( $setting[ 'selectors' ] ) ) {
					foreach ( $setting['selectors'] as $selector ) {
						$original = json_encode( $setting['original'] );
						$scripts .= "wp.customize('kt_woomail[$setting_key]', function(value) {
						value.bind(function(newval) {
						newval = (newval !== '' ? newval : $original);
						newval = prepare(newval, '$setting_key', '$selector');
						jQuery('$selector').html(newval);
						});
						});";
					}
				}
			}
			// Go through woo settings
			foreach ( Kadence_Woomail_Settings::get_woo_settings() as $setting_key => $setting ) {

				// No live method
				if ( ! isset( $setting['live_method'] ) ) {
					continue;
				}

				// Iterate over selectors
				if ( in_array( $setting['live_method'], array( 'css', 'property' ) ) && ! empty( $setting['selectors'] ) ) {
					foreach ( $setting['selectors'] as $selector => $properties ) {

						// Iterate over properties
						foreach ( $properties as $property ) {

							// CSS value change
							if ( ! isset( $setting['live_method'] ) || $setting['live_method'] === 'css' ) {
								$scripts .= "wp.customize('$setting_key', function(value) {
								value.bind(function(newval) {
								newval = newval + (typeof suffixes['$setting_key'] !== 'undefined' ? suffixes['$setting_key'] : '');
								newval = prepare(newval, '$setting_key', '$selector');
								jQuery('$selector').css('$property', '').attr('style', function(i, s) { return (s||'') + '$property: ' + newval + ';' });
								});
								});";
							}

							// DOM object property
							if ( $setting['live_method'] === 'property' ) {
								$scripts .= "wp.customize('$setting_key', function(value) {
								value.bind(function(newval) {
								newval = newval + (typeof suffixes['$setting_key'] !== 'undefined' ? suffixes['$setting_key'] : '');
								newval = prepare(newval, '$setting_key', '$selector');
								jQuery('$selector').prop('$property', newval);
								});
								});";
							}
						}
					}
				}

				// HTML Replace
				if ( $setting['live_method'] === 'replace' && ! empty( $setting[ 'selectors' ] ) ) {
					foreach ( $setting['selectors'] as $selector ) {
						$original = ( ! empty( $setting['original'] ) ? json_encode( $setting['original'] ) : 'placeholder' );
						$scripts .= "wp.customize('$setting_key', function(value) {
						value.bind(function(newval) {
						newval = (newval !== '' ? newval : $original);

						newval = prepare(newval, '$setting_key', '$selector');
						jQuery('$selector').html(newval);
						});
						});";
					}
				}
			}

			// Close container and return
			echo $scripts . '});</script>';
		}

	}

	Kadence_Woomail_Preview::get_instance();

}
