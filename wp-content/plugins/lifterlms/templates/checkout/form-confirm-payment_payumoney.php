<?php
/**
 * Checkout Form
 *
 * @author 	LifterLMS
 * @package LifterLMS/Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>

<form action="<?php echo $selected_gateway->url; ?>" class="llms-checkout llms-confirm llms-checkout-cols-<?php echo $cols; ?>" method="POST" id="llms-product-purchase-confirm-form">

	<?php do_action( 'lifterlms_before_checkout_confirm_form' ); ?>

	<div class="llms-checkout-col llms-col-1">

		<section class="llms-checkout-section">

			<h4 class="llms-form-heading"><?php _e( 'Billing Information', 'lifterlms' ); ?></h4>

			<div class="llms-checkout-section-content">
				<?php do_action( 'lifterlms_checkout_confirm_before_billing_info' ); ?>
				<?php foreach ( LLMS_Person_Handler::get_available_fields( 'checkout', $field_data ) as $field ) : ?>
					<span class="llms-field-display <?php echo $field['id']; ?>"><?php echo $field['value']; ?></span><?php echo $field['last_column'] ? '<br>' : ' '; ?>

					<?php
						if($field['id'] == 'first_name'){
							$firstname = $field['value'];
							echo '<input type="text" value="'.$field['value'].'" name="firstname" hidden/>';
						}
						else if($field['id'] == 'last_name'){
							echo '<input type="text" value="'.$field['value'].'" name="lastname" hidden/>';
						}
						else if($field['id'] == 'llms_billing_address_1'){
							echo '<input type="text" value="'.$field['value'].'" name="address1" hidden/>';
						}
						else if($field['id'] == 'llms_billing_address_2'){
							echo '<input type="text" value="'.$field['value'].'" name="address2" hidden/>';
						}
						else if($field['id'] == 'llms_billing_city'){
							echo '<input type="text" value="'.$field['value'].'" name="city" hidden/>';
						}
						else if($field['id'] == 'llms_billing_state'){
							echo '<input type="text" value="'.$field['value'].'" name="state" hidden/>';
						}
						else if($field['id'] == 'llms_billing_zip'){
							echo '<input type="text" value="'.$field['value'].'" name="zipcode" hidden/>';
						}
						else if($field['id'] == 'llms_billing_country'){
							echo '<input type="text" value="'.$field['options'][$field['value']].'" name="country" hidden/>';
						}
						else if($field['id'] == 'llms_phone'){
							echo '<input type="text" value="'.$field['value'].'" name="phone" hidden/>';
						}						
					?>

				<?php endforeach; ?>
				<?php do_action( 'lifterlms_checkout_confirm_after_billing_info' ); ?>
			</div>

		</section>

	</div>

	<div class="llms-checkout-col llms-col-2">

		<section class="llms-checkout-section">

			<h4 class="llms-form-heading"><?php _e( 'Order Summary', 'lifterlms' ); ?></h4>

			<div class="llms-checkout-section-content">

				<?php llms_get_template( 'checkout/form-summary.php', array(
					'coupon' => $coupon,
					'plan' => $plan,
					'product' => $product,
				) ); ?>

				<?php
					$productinfo = $product->get_post_type_label( 'singular_name' ) . ': ' .
						$product->get( 'title' ) . ' :Access Plan: '. $plan->get( 'title' );
					echo '<input type="text" name="productinfo" hidden value="'. 
						$productinfo . '">';
					$amount = $plan->get_price_with_coupon( $plan->is_on_sale() ? 'sale_price' : 'price', $coupon, array(), 'float' );
					echo '<input type="text" name="amount" hidden value="'. $amount .'" >'; 
				?>

			</div>

		</section>

		<section class="llms-checkout-section">

			<h4 class="llms-form-heading"><?php _e( 'Payment Details', 'lifterlms' ); ?></h4>
			<div class="llms-checkout-section-content llms-form-fields">

				<div class="llms-payment-method">
					<?php do_action( 'lifterlms_checkout_confirm_before_payment_method', $selected_gateway->get_id() ); ?>
					<span class="llms-gateway-title"><span class="llms-label"><?php _e( 'Payment Method:', 'lifterlms' ); ?></span> <?php echo $selected_gateway->get_title(); ?></span>
					<?php if ( $selected_gateway->get_icon() ) : ?>
						<span class="llms-gateway-icon"><?php echo $selected_gateway->get_icon(); ?></span>
					<?php endif; ?>
					<?php if ( $selected_gateway->get_description() ) : ?>
						<div class="llms-gateway-description"><?php echo wpautop( wptexturize( $selected_gateway->get_description() ) ); ?></div>
					<?php endif; ?>
					<?php do_action( 'lifterlms_checkout_confirm_after_payment_method', $selected_gateway->get_id() ); ?>
				</div>

				<footer class="llms-checkout-confirm llms-form-fields flush">

					<?php llms_form_field( array(
						'columns' => 12,
						'classes' => 'llms-button-action',
						//'id' => 'llms_confirm_pending_order',
						'value' => apply_filters( 'lifterlms_checkout_confirm_button_text', __( 'Confirm Payment', 'lifterlms' ) ),
						'last_column' => true,
						'required' => false,
						'type'  => 'submit',
					) ); ?>

				</footer>

			</div>

		</section>

	</div>

	<?php
		$txnid = $order->id;
		$email = wp_get_current_user()->user_email;
		$udf1 = ''; 
		$udf2 = ''; 
		$udf3 = ''; 
		$udf4 = ''; 
		$udf5 = ''; 
		$checkout_url = esc_url( get_permalink( llms_get_page_id( 'checkout' ) ) );
		global $wp;
	?>
	<?php /*wp_nonce_field( 'confirm_pending_order' ); 
	<input name="action" type="hidden" value="confirm_pending_order">
	<input name="llms_order_key" type="hidden" value="<?php echo $_GET['order']; ?>"> */?>
	<input name='key' type="hidden" value="<?php echo $selected_gateway->key; ?>">
	<input name='txnid' type="hidden" value="<?php echo $txnid; ?>">
	<input name='surl' type="hidden" value="<?php echo $checkout_url . $selected_gateway->surl; ?>">
	<input name='furl' type="hidden" value="<?php echo $checkout_url . $selected_gateway->furl; ?>">
	<input name='service_provider' type="hidden" value="<?php echo $selected_gateway->service_provider; ?>">
	<input name='salt' type="hidden" value="<?php echo $selected_gateway->salt; ?>">
	<input name='email' type="hidden" value="<?php echo $email; ?>">
	<input name='udf1' type="hidden" value="<?php echo $udf1; ?>">
	<input name='udf2' type="hidden" value="<?php echo $udf2; ?>">
	<input name='udf3' type="hidden" value="<?php echo $udf3; ?>">
	<input name='udf4' type="hidden" value="<?php echo $udf4; ?>">
	<input name='udf5' type="hidden" value="<?php echo $udf5; ?>">
	
	<?php
		$hashstring = $selected_gateway->key.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|'.$udf1.'|'.$udf2.'|'.$udf3.'|'.$udf4.'|'.$udf5.'||||||'.$selected_gateway->salt;
		$hash = hash('sha512', $hashstring);
	?>

	<input name='hash' type="hidden" value="<?php echo $hash; ?>">

	<?php do_action( 'lifterlms_after_checkout_confirm_form' ); ?>

</form>






















<?php



return;
$session = LLMS()->session->get( 'llms_order' );
if ( $session ) {
	$order = llms_get_order_by_key( $session );
	if ( $order ) {
		$product = new LLMS_Product( $order->get_product_id() );
	}
}

if ( ! $product || ! $order ) {
	llms_add_notice( __( 'The order for this transaction could not be located.', 'lifterlms' ) );
	return;
}
?>

<?php llms_print_notices(); ?>

<?php do_action( 'lifterlms_before_checkout_confirm_form' ); ?>

<div class="llms-checkout-wrapper">

	<div class="llms-checkout">

		<h4><?php _e( 'Confirm Purchase', 'lifterlms' ); ?></h4>

		<!-- Product information -->
		<div class="llms-title-wrapper">
			<p class="llms-title"><?php echo $product->get_title(); ?></p>
		</div>

		<?php do_action( 'lifterlms_checkout_confirm_after_title' ); ?>

		<!-- pricing options -->
		<div class="llms-price-wrapper">
			<div class="llms-payment-options llms-notice-box">

				<?php if ( 'recurring' == $order->get_type() ) : ?>

					<label><?php _e( 'Payment Terms:', 'lifterlms' ); ?></label>
					<strong><?php echo apply_filters( 'lifterlms_confirm_payment_get_recurring_price_html', ucfirst( $product->get_subscription_price_html( $order->get_product_subscription_array( false ), $order->get_coupon_id() ) ) ); ?></strong>

				<?php elseif ( 'single' == $order->get_type() ) : ?>

					<label><?php _e( 'Price:', 'lifterlms' ); ?></label>
					<strong><?php echo apply_filters( 'lifterlms_confirm_payment_get_single_price_html', ucfirst( $product->get_single_price_html( $order->get_coupon_id() ) ) ); ?></strong>

				<?php else : ?>
					<?php
					/**
					 * Allow themes / plugins / extensions to create custom confirmation messages
					 */
					do_action( 'lifterlms_checkout_confirm_html_' . $order->get_type(), $order );
					?>
				<?php endif; ?>

				<br />
				<label><?php echo __( 'Payment Method', 'lifterlms' ); ?>:</label>
				<strong><?php echo apply_filters( 'lifterlms_confirm_payment_method_text', $order->get_payment_gateway_title() ); ?></strong>

			</div>

		</div>

		<form action="" method="POST">

			<div class="llms-clear-box llms-center-content">
				<input type="submit" class="button llms-button-primary" name="llms_confirm_order" value="<?php _e( 'Confirm Purchase', 'lifterlms' ); ?>" />
			</div>

			<?php wp_nonce_field( 'llms_confirm_order' ); ?>

			 <input type="hidden" name="action" value="llms_confirm_order" />

		</form>


	</div>


</div>

<?php do_action( 'lifterlms_after_checkout_confirm_form' ); ?>
