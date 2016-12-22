<?php
/**
* PayU India Payment Gateway
*
* Provides a PayU India Payment Gateway.
*
* @class WC_Gateway_Payu_In
* @package WooCommerce
* @category Payment Gateways
* @author Daniel Dudzic
*
*
* Table Of Contents
*
* __construct()
* init_form_fields()
* plugin_url()
* admin_options()
* payment_fields()
* generate_payu_in_form()
* process_payment()
* receipt_page()
* check_transaction_status()
* payment_success()
* payment_pending()
* payment_failure()
* payu_in_transaction_verification()
* send_request()
* calculate_hash_before_transaction()
* check_hash_after_transaction()
* calculate_hash_before_verification()
* get_post_var()
* get_get_var()
* add_payu_in_gateway()
*/

class WC_Gateway_Payu_In extends WC_Payment_Gateway {

	/**
	 * __construct function.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		global $woocommerce;

        $this->id				= 'payu_in';
        $this->method_title = __( 'PayUMoney', 'woocommerce_payu_in' );
        $this->icon 			= $this->plugin_url() . '/assets/images/icon.png';
        $this->has_fields 		= true;
        $this->liveurl			= 'https://secure.payu.in/_payment';
        $this->testurl			= 'https://test.payu.in/_payment';

      	// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

      	// Check if the currency is set to INR. If not we disable the plugin here.
		if ( get_option( 'woocommerce_currency' ) == 'INR' ) {
			$payu_in_enabled = $this->settings['enabled'];
		} else {
			$payu_in_enabled = 'no';
		} // End check currency

      	$this->enabled			= $payu_in_enabled;
		$this->title 			= $this->settings['title'];
		$this->description  	= $this->settings['description'];
		$this->merchantid   	= $this->settings['merchantid'];
		$this->salt   			= $this->settings['salt'];
		$this->cc_method 		= $this->settings['cc_method'];
		$this->dc_method    	= $this->settings['dc_method'];
		$this->nb_method   	    = $this->settings['nb_method'];
		$this->emi_method   	= $this->settings['emi_method'];
		$this->cod_method		= $this->settings['cod_method'];
		$this->testmode			= $this->settings['testmode'];

		// IPN
		if ( isset( $_GET[ 'payu_in_callback' ] ) && esc_attr( $_GET[ 'payu_in_callback' ] ) == '1' ) {
			$this->check_transaction_status();
		}

		// Receipt
		add_action( 'woocommerce_receipt_payu_in', array( $this, 'receipt_page' ) );

		/* 1.6.6 */
		add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );

		/* 2.0.0 */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

    } // End Constructor


   /**
	* Initialise Gateway Settings Form Fields
	*
	* @since 1.0.0
	*/
	function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
							'title' => __( 'Enable/Disable', 'woocommerce_payu_in' ),
							'type' => 'checkbox',
							'label' => __( 'Enable PayUMoney', 'woocommerce_payu_in' ),
							'default' => 'yes'
						),
			'title' => array(
							'title' => __( 'Title', 'woocommerce_payu_in' ),
							'type' => 'text',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce_payu_in' ),
							'default' => __( 'PayUMoney', 'woocommerce_payu_in' )
						),
			'description' => array(
							'title' => __( 'Description', 'woocommerce_payu_in' ),
							'type' => 'textarea',
							'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce_payu_in' ),
							'default' => __( 'Direct payment via PayUMoney. PayUMoney accepts VISA, MasterCard, Debit Cards and the Net Banking of all major banks.', 'woocommerce_payu_in' ),
						),
			'merchantid' => array(
							'title' => __( 'Merchant Key', 'woocommerce_payu_in' ),
							'type' => 'text',
							'description' => __( 'This key is generated at the time of activation of your site and helps to uniquely identify you to PayUMoney', 'woocommerce_payu_in' ),
							'default' => ''
						),
			'salt' => array(
							'title' => __( 'SALT', 'woocommerce_payu_in' ),
							'type' => 'text',
							'description' => __( 'String of characters provided by PayUMoney', 'woocommerce_payu_in' ),
							'default' => ''
						),

			'cc_method' => array(
							'title' => __( 'Credit Card', 'woocommerce_payu_in' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Credit Card payment method tab pre-select on the Checkout page', 'woocommerce_payu_in' ),
							'default' => 'no'
						),

			'dc_method' => array(
							'title' => __( 'Debit Card', 'woocommerce_payu_in' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Debit Card payment method tab pre-select on the Checkout page', 'woocommerce_payu_in' ),
							'default' => 'no'
						),

			'nb_method' => array(
							'title' => __( 'Net Banking', 'woocommerce_payu_in' ),
							'type' => 'checkbox',
							'label' => __( 'Enable Net Banking payment method tab pre-select on the Checkout page', 'woocommerce_payu_in' ),
							'default' => 'no'
						),

			'emi_method' => array(
							'title' => __( 'EMI', 'woocommerce_payu_in' ),
							'type' => 'checkbox',
							'label' => __( 'Enable EMI payment method tab pre-select on the Checkout page', 'woocommerce_payu_in' ),
							'default' => 'no'
						),

			'cod_method' => array(
							'title' => __( 'COD', 'woocommerce_payu_in' ),
							'type' => 'checkbox',
							'label' => __( 'Enable COD payment method tab pre-select on the Checkout page', 'woocommerce_payu_in' ),
							'default' => 'no'
						),

			'testmode' => array(
							'title' => __( 'Test Mode', 'woocommerce_payu_in' ),
							'type' => 'checkbox',
							'label' => __( 'Enable PayUMoney Test Mode', 'woocommerce_payu_in' ),
							'default' => 'no'
						)
			);
	} // End init_form_fields()


	/**
	 * Get the plugin URL
	 *
	 * @since 1.0.0
	 */
	function plugin_url() {
		if( isset( $this->plugin_url ) ) return $this->plugin_url;

		if ( is_ssl() ) {
			return $this->plugin_url = str_replace( 'http://', 'https://', WP_PLUGIN_URL ) . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) );
		} else {
			return $this->plugin_url = WP_PLUGIN_URL . '/' . plugin_basename( dirname( dirname( __FILE__ ) ) );
		}
	} // End plugin_url()


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
    	?>
    	<h3><?php _e( 'PayUMoney', 'woocommerce_payu_in' ); ?></h3>
    	<p><?php _e( 'PayUMoney works by sending the user to <a href="https://www.payumoney.com/">PayUMoney</a> to enter their payment information. Note that PayUMoney will only take payments in Indian Rupee.', 'woocommerce_payu_in' ); ?></p>
		<?php
			if ( get_option( 'woocommerce_currency' ) == 'INR' ) {
		?>
			<table class="form-table">
		<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
		?>
			</table><!--/.form-table-->
		<?php
			} else {
		?>
		<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce_payu_in' ); ?></strong> <?php echo sprintf( __( 'Choose Indian Rupee (Rs.) as your store currency in <a href="%s">Pricing Options</a> to enable the PayUMoney Gateway.', 'woocommerce_payu_in' ), admin_url( '?page=woocommerce&tab=general' ) ); ?></p></div>
		<?php
		} // End check currency
	} // End admin_options()


    /**
	 * Add fields to pre-select method of payment
	 *
	 * @since 1.4
	 */
    function payment_fields() {
		if ( $this->description ) { echo wpautop( wptexturize( $this->description ) ); }

		if($this->cc_method == 'yes' || $this->dc_method == 'yes' || $this->nb_method == 'yes' || $this->emi_method == 'yes' || $this->cod_method == 'yes') {
		?>

		<fieldset>
			<p class="form-row">
				<label for="pg"><?php echo __("Choose a Payment Method", 'woocommerce_payu_in') ?></label>
				<?php if($this->cc_method == 'yes') { ?><input type="radio" name="pg" value="CC">Credit Card<br /><?php } ?>
				<?php if($this->dc_method == 'yes') { ?><input type="radio" name="pg" value="DC">Debit Card<br /><?php } ?>
				<?php if($this->nb_method == 'yes') { ?><input type="radio" name="pg" value="NB">Net Banking<br /><?php } ?>
				<?php if($this->emi_method == 'yes') { ?><input type="radio" name="pg" value="EMI">EMI<br /><?php } ?>
				<?php if($this->cod_method == 'yes') { ?><input type="radio" name="pg" value="COD">COD<?php } ?>
			</p>
			<div class="clear"></div>
		</fieldset>

		<?php
		}

    } // End payment_fields()


	/**
	 * Generate the PayU India button link.
	 *
	 * @since 1.0.0
	 */
    function generate_payu_in_form($order_id) {
		global $woocommerce;
		$order = new WC_Order($order_id);

		//$productinfo = sprintf( __( 'Order #%s from ', 'woocommerce_payu_in' ), $order_id ) . get_bloginfo( 'name' );
$productinfo = "productinfo";
		//Hash data
		$hash_data['key']				 = $this->merchantid;
		$hash_data['txnid'] 			 = substr(hash('sha256', mt_rand() . microtime()), 0, 20); // Unique alphanumeric Transaction ID
		$hash_data['amount'] 			 = $order->order_total;
		$hash_data['productinfo'] 		 = $productinfo;
		$hash_data['firstname']			 = $order->billing_first_name;
		$hash_data['email'] 			 = $order->billing_email;
		$hash_data['hash'] 				 = $this->calculate_hash_before_transaction($hash_data);


		update_post_meta( $order_id, '_order_txnid', $hash_data['txnid'] );

		// PayU Args
		$payu_in_args = array(

			// Merchant details
			'key'					=> $this->merchantid,
			'surl'					=> add_query_arg( 'payu_in_callback', 1, $this->get_return_url( $order) ),
			'furl'					=> add_query_arg(array('payu_in_callback' => 1, 'payu_in_status' => 'failed'), $this->get_return_url( $order)),
			'curl'					=> add_query_arg(array('payu_in_callback' => 1, 'payu_in_status' => 'cancelled'), $this->get_return_url( $order)),

			// Customer details
			'firstname'				=> $order->billing_first_name,
			'lastname'				=> $order->billing_last_name,
			'email'					=> $order->billing_email,
			'address1'				=> $order->billing_address_1,
			'address2'				=> $order->billing_address_2,
			'city'					=> $order->billing_city,
			'state'					=> $order->billing_state,
			'zipcode'				=> $order->billing_postcode,
			'country'				=> $order->billing_country,
			'phone' 	            => $order->billing_phone,
            'service_provider' => 'payu_paisa',
			// Item details
			'productinfo'		    => $productinfo,
			'amount'			   	=> $order->order_total,

			// Pre-selection of the payment method tab
			'pg'			   	    => $this->get_get_var('pg')

		);

		$payuform = '';

		foreach( $payu_in_args as $key => $value ) {
   			if( $value ) {
	   			$payuform .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
   			}
		}


		$payuform .= '<input type="hidden" name="txnid" value="' . $hash_data['txnid'] . '" />' . "\n";
		$payuform .= '<input type="hidden" name="hash" value="' . $hash_data['hash'] . '" />' . "\n";

		// Get the right URL in case the test mode is enabled
		$posturl = $this->liveurl;
		if ( $this->testmode == 'yes' ) { $posturl = $this->testurl; }


		// The form
		return '<form action="' . $posturl . '" method="POST" name="payform" id="payform">
				' . $payuform . '
				<input type="submit" class="button" id="submit_payu_in_payment_form" value="' . __( 'Pay via PayU', 'woocommerce_payu_in' ) . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">'.__( 'Cancel order &amp; restore cart', 'woocommerce_payu_in' ) . '</a>
				<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block(
							{
								message: "<img src=\"' . $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />'.__('Thank you for your order. We are now redirecting you to PayU to make payment.', 'woothemes').'",
								overlayCSS:
								{
									background: "#fff",
									opacity: 0.6
								},
								css: {
							        padding:        20,
							        textAlign:      "center",
							        color:          "#555",
							        border:         "3px solid #aaa",
							        backgroundColor:"#fff",
							        cursor:         "wait"
							    }
							});
							jQuery("#payform").attr("action","'.$posturl.'");
						jQuery("#submit_payu_in_payment_form").click();
					});
				</script>
			</form>';
	} // End generate_payu_in_form()


	/**
	 * Process the payment and return the result.
	 *
	 * @since 1.0.0
	 */
	function process_payment($order_id) {
		$order = new WC_Order($order_id);

		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg( 'order', $order->id, add_query_arg( 'key', $order->order_key, add_query_arg( 'pg', $this->get_post_var('pg'), $order->get_checkout_payment_url( true ) ) ) )
		);
	} // End process_payment()


	/**
	 * Receipt page.
	 *
	 * Display text and a button to direct the user to the payment screen.
	 *
	 * @since 1.0.0
	 */
	function receipt_page($order) {
		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with PayU.', 'woocommerce_payu_in' ) . '</p>';

		echo $this->generate_payu_in_form( $order );
	} // End receipt_page()


	/**
	 * Check the validity of data recived in $_POST and the status of transaction
	 *
	 * @since 1.0.0
	 */
	function check_transaction_status() {

		global $woocommerce;

		$salt = $this->salt;

		if(!empty($_POST)) {
			foreach($_POST as $key => $value) {
				$txnRs[$key] = htmlentities($value, ENT_QUOTES);
           }
         } else {
	         die('No transaction data was passed!');
         }

         $txnid = $txnRs['txnid'];


		/* Checking hash / true or false */
		if($this->check_hash_after_transaction($salt, $txnRs)) {

			if($txnRs['status']=='success'){
				$this->payment_success($txnid);
			}

			if($txnRs['status']=='pending'){
				$this->payment_pending($txnid);
			}

			if($txnRs['status']=='failure'){
				$this->payment_failure($txnid);
			}

		} else {
			die('Hash incorrect!');
		}

	} // End check_transaction_status()


	/**
	 * @since 1.0.0
	 */
function payment_success($txnid) {

		    global $woocommerce;

			$args = array(
			    'meta_key'        => '_order_txnid',
			    'meta_value'	  => $txnid,
			    'post_type'       => 'shop_order',
			    'post_status'     => 'publish'
          		);

			$results = get_posts( $args );

			if ( $results ) {
				foreach ( $results as $result ) {
					$order = new WC_Order();
					$order->populate( $result );
					break;
				}
			} else {
				return false;
			}

			$order->add_order_note( __( 'PayUMoney payment completed', 'woocommerce_payu_in' ) . ' (Transaction id: ' . $txnid . ')' );
			$order->payment_complete();

			$woocommerce->cart->empty_cart();
			return true;

              }

	   // End payment_success()


	/**
	 * @since 1.0.0
	 */
	function payment_pending($txnid) {

		if($this->payu_in_transaction_verification($txnid) == 'pending') {

			global $woocommerce;

			$args = array(
			    'meta_key'        => '_order_txnid',
			    'meta_value'	  => $txnid,
			    'post_type'       => 'shop_order',
			    'post_status'     => 'publish'
			);

			$results = get_posts( $args );

			if ( $results ) {
				foreach ( $results as $result ) {
					$order = new WC_Order();
					$order->populate( $result );
					break;
				}
			} else {
				return false;
			}

			$order->update_status('pending');
			$order->add_order_note(sprintf( __( 'PayU payment pending (Transaction id: %s)', 'woocommerce_payu_in' ), $txnid ));

			return false;

		} else {

			die('PayU verification failed!');
		}

	} // End payment_pending()


	/**
	 * @since 1.0.0
	 */
	function payment_failure($txnid) {
		global $woocommerce;

			$args = array(
			    'meta_key'        => '_order_txnid',
			    'meta_value'	  => $txnid,
			    'post_type'       => 'shop_order',
			    'post_status'     => 'publish'
			);

			$results = get_posts( $args );

			if ( $results ) {
				foreach ( $results as $result ) {
					$order = new WC_Order();
					$order->populate( $result );
					break;
				}
			} else {
				return false;
			}

			$order->update_status('failed');
			$order->add_order_note(sprintf( __( 'PayU payment failed (Transaction id: %s)', 'woocommerce_payu_in' ), $txnid ));

			return false;

	} // End payment_failure()


	/**
	 * @since 1.0.0
	 */
	function payu_in_transaction_verification($txnid) {

		$this->verification_liveurl	= 'https://info.payu.in/merchant/postservice';
		$this->verification_testurl	= 'https://test.payu.in/merchant/postservice';

		$host = $this->verification_liveurl;
		if ( $this->testmode == 'yes' ) { $host = $this->verification_testurl; }

		$hash_data['key'] = $this->merchantid;
		$hash_data['command'] = 'verify_payment';
		$hash_data['var1'] =  $txnid;
		$hash_data['hash'] = $this->calculate_hash_before_verification($hash_data);
        
		// Call the PayU, and verify the status
		$response = $this->send_request($host, $hash_data);

		$response = unserialize($response);

		return $response['transaction_details'][$txnid]['status'];
       
	} // End payu_in_transaction_verification()


	/**
	 * @since 1.0.0
	 */
	function send_request($host, $data) {

		$response = wp_remote_post($host, array(
                    'method' => 'POST',
                    'body' => $data,
                    'timeout' => 70,
                    'sslverify' => false
                    ));

        if (is_wp_error($response))
            throw new Exception(__('There was a problem connecting to the payment gateway.', 'woocommerce_payu_in'));

        if (empty($response['body']))
            throw new Exception(__('Empty PayUMoney response.', 'woocommerce_payu_in'));

        $parsed_response = $response['body'];

        return $parsed_response;

	} // End send_request()



	/**
	 * @since 1.0.0
	 */
	function calculate_hash_before_transaction($hash_data) {

		$hash_sequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
		$hash_vars_seq = explode('|', $hash_sequence);
		$hash_string = '';

		foreach($hash_vars_seq as $hash_var) {
		  $hash_string .= isset($hash_data[$hash_var]) ? $hash_data[$hash_var] : '';
		  $hash_string .= '|';
		}

		$hash_string .= $this->salt;
		$hash_data['hash'] = strtolower(hash('sha512', $hash_string));

		return $hash_data['hash'];

	} // End calculate_hash_before_transaction()


	/**
	 * @since 1.0.0
	 */
	function check_hash_after_transaction($salt, $txnRs) {

		$hash_sequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
		$hash_vars_seq = explode('|', $hash_sequence);
		//generation of hash after transaction is = salt + status + reverse order of variables
		$hash_vars_seq = array_reverse($hash_vars_seq);

		$merc_hash_string = $salt . '|' . $txnRs['status'];

		foreach ($hash_vars_seq as $merc_hash_var) {
			$merc_hash_string .= '|';
			$merc_hash_string .= isset($txnRs[$merc_hash_var]) ? $txnRs[$merc_hash_var] : '';
		}

		$merc_hash = strtolower(hash('sha512', $merc_hash_string));
                 
		/* The hash is valid */
		if($merc_hash == $txnRs['hash']) {
			return true;
		} else {
			return false;
		}

	} // End check_hash_after_transaction()


	/**
	 * @since 1.0.0
	 */
	function calculate_hash_before_verification($hash_data) {

		$hash_sequence = "key|command|var1";
		$hash_vars_seq = explode('|', $hash_sequence);
		$hash_string = '';

		foreach($hash_vars_seq as $hash_var) {
		  $hash_string .= isset($hash_data[$hash_var]) ? $hash_data[$hash_var] : '';
		  $hash_string .= '|';
		}

		$hash_string .= $this->salt;
		$hash_data['hash'] = strtolower(hash('sha512', $hash_string));

		return $hash_data['hash'];

	} // End calculate_hash_before_verification()


	 /**
	 *  Get post data if set
	 *
	 * @since 1.4
	 */
	function get_post_var($name) {
		if(isset($_POST[$name])) {
			return $_POST[$name];
		}
		return NULL;
	}

	/**
	 *  Get get data if set
	 *
	 * @since 1.4
	 */
	function get_get_var($name) {
		if(isset($_GET[$name])) {
			return $_GET[$name];
		}
		return NULL;
	}

} //  End Class