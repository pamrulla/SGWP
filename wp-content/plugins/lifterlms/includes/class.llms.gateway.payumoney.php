<?php
/**
* Manual Payment Gateway Class
*
* @version 3.0.0
*/
if ( ! defined( 'ABSPATH' ) ) { exit; }
class LLMS_Payment_Gateway_PayUMoney extends LLMS_Payment_Gateway {

	public $url = 'https://secure.payu.in/_payment';
	
	public $key = 'D77ZO7';

	public $surl = '?status=1';

	public $furl = '?status=0';

	public $service_provider = 'payu_paisa';

	public $salt = 'KezrgVWb';

	/**
	 * Constructor
	 * @return  void
	 * @since  3.0.0
	 * @version 3.0.0
	 */
	public function __construct() {

		$this->id = 'payumoney';
		$this->admin_description = __( 'Provides a PayU India gateway for any orders during checkout.', 'lifterlms' );
		$this->admin_title = __( 'PayUMoney', 'lifterlms' );
		$this->title = __( 'PayUMoney', 'lifterlms' );
		$this->description = __( 'Secure your payment with pay u money.', 'lifterlms' );
		$this->payment_instructions = ''; // fields

		$this->supports = array(
			'checkout_fields' => false,
			'refunds' => false, // manual refunds are available always for all gateways and are not handled by this class
			'single_payments' => true,
			'recurring_payments' => true, // @todo make it work for recurring
			'test_mode' => false,
		);

		add_filter( 'llms_get_gateway_settings_fields', array( $this, 'get_settings_fields' ), 10, 2 );
		add_action( 'lifterlms_before_view_order_table', array( $this, 'before_view_order_table' ) );

	}

	/**
	 * Output payment instructions if the order is pending
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function before_view_order_table() {
		global $wp;

		if ( ! empty( $wp->query_vars['orders'] ) ) {

			$order = new LLMS_Order( intval( $wp->query_vars['orders'] ) );

			if ( in_array( $order->get( 'status' ), array( 'llms-pending' ) ) ) {

				echo $this->get_payment_instructions();

			}

		}

	}

	/**
	 * Get fields displayed on the checkout form
	 * @return   string
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function get_payment_instructions() {
		if ( $opt = $this->get_option( 'payment_instructions' ) ) {
			$fields = '<div class="llms-notice llms-debug">' . wpautop( wptexturize( wp_kses_post( $opt ) ) ) . '</div>';
		} else {
			$fields = '';
		}
		return apply_filters( 'llms_get_payment_instructions', $fields, $this->id );
	}

	/**
	 * Get admin setting fields
	 * @param    array      $fields      default fields
	 * @param    string     $gateway_id  gateway ID
	 * @return   array
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function get_settings_fields( $fields, $gateway_id ) {

		if ( $this->id !== $gateway_id ) {
			return $fields;
		}

		$fields[] = array(
			'id'            => $this->get_option_name( 'payment_instructions' ),
			'desc'          => '<br>' . __( 'Displayed to the user when this gateway is selected during checkout. Add information here instructing the student on how to send payment.', 'lifterlms' ),
			'title'         => __( 'Payment Instructions', 'lifterlms' ),
			'type'          => 'textarea',
		);

		return $fields;

	}

	/**
	 * Handle a Pending Order
	 * Called by LLMS_Controller_Orders->create_pending_order() on checkout form submission
	 * All data will be validated before it's passed to this function
	 *
	 * @param   obj       $order   Instance LLMS_Order for the order being processed
	 * @param   obj       $plan    Instance LLMS_Access_Plan for the order being processed
	 * @param   obj       $person  Instance of LLMS_Student for the purchasing customer
	 * @param   obj|false $coupon  Instance of LLMS_Coupon applied to the order being processed, or false when none is being used
	 * @return  void
	 * @since   3.0.0
	 * @version 3.0.0
	 */
	public function handle_pending_order( $order, $plan, $person, $coupon = false ) {

		if ( $order->is_recurring() ) {
			//return llms_add_notice( __( 'This gateway cannot process recurring transactions', 'lifterlms' ), 'error' );
		}

		// no payment (free orders)
		if ( floatval( 0 ) === $order->get_initial_price( array(), 'float' ) ) {

			$order->set( 'status', 'llms-completed' );
			$this->complete_transaction( $order );

		} else {

			//do_action( 'lifterlms_handle_pending_order_complete', $order );
			wp_redirect( llms_confirm_payment_url($order->order_key) );
			exit;

		}

	}

		/**
	 * This should be called by the gateway after verifying the transaction was completed successfully
	 *
	 * @param    obj        $order   Instance of an LLMS_Order object
	 * @param    string     $msg     optional message to display on the redirect screen
	 * @return   void
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function complete_transaction( $order, $msg = '', $isSuccess = false, $payuMoneyId = 0 ) {

		$this->log( $this->get_admin_title() . ' `complete_transaction()` started', $order, $msg );

		// filter the notice
		$msg = apply_filters( 'lifterlms_completed_transaction_message', $msg, $order );

		// ouput the notice
		if($isSuccess){
			llms_add_notice( $msg, 'success' );
			$order->set( 'status', 'llms-completed');
			$order->record_transaction(array(
				'amount' => $order->get_initial_price(array(), 'float'),
				'transaction_id' => $payuMoneyId,
				'status' => 'llms-txn-succeeded'
			));
		}
		else{
			llms_add_notice( $msg, 'fail' );
			$order->set( 'status', 'llms-failed');
			$tx = $order->record_transaction(array(
				'amount' => $order->get_initial_price(array(), 'float'),
				'transaction_id' => $payuMoneyId,
				'status' => 'llms-txn-failed'
			));
			$this->log( $this->get_admin_title() . ' transactopn recorded', $tx );
		}
		
		$this->log( $this->get_admin_title() . ' `complete_transaction()` finished', $order, $msg );
	}

	public function return_transaction($isSuccess, $orderid, $mode, $payuMoneyId, $error){
		$order = new LLMS_Order($orderid);

		if($isSuccess){
			$msg = sprintf( __( 'Congratulations! Your purchase was successful and you\'ve been enrolled in %s.', 'lifterlms' ), $order->get( 'product_title' ) );
			$this->complete_transaction($order, $msg, true, $payuMoneyId);
		}
		else {
			$msg = sprintf( __( 'Sorry! Your purchase was unsuccessful for %s and please try again.', 'lifterlms' ), $order->get( 'product_title' ) );
			$this->complete_transaction($order, $msg, false, $payuMoneyId);
		}

		return $msg;
	}

	/**
	 * Determine if the gateway is enabled according to admin settings checkbox
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function is_enabled() {
		return ( 'yes' === $this->get_enabled() ) ? true : false;
	}

}
