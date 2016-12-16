<?php
/**
 * My Account Navigation Links
 * @since  2.?.?
 * @version 3.0.4
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$sep = apply_filters( 'lifterlms_my_account_navigation_link_separator', '&bull;' );
?>
<div class="row text-center">
	<?php foreach ( LLMS_Student_Dashboard::get_tabs() as $var => $data ) : ?>
		<div class="col-sm-2">
			<?php if($data['title'] == "Dashboard") { ?>
				<a href="<?php echo isset( $data['url'] ) ? $data['url'] : llms_get_endpoint_url( $var, '', llms_get_page_url( 'myaccount' ) ); ?>" title="Example tile shortcut" class="tile-box tile-box-shortcut btn-danger">
				    <div class="tile-header"><?php echo $data['title']; ?></div>
				    <div class="tile-content-wrapper">
				        <i class="fa fa-tachometer" aria-hidden="true"></i>
				    </div>
				</a>
			<?php } else if($data['title'] == "My Courses") { ?>
				<a href="<?php echo isset( $data['url'] ) ? $data['url'] : llms_get_endpoint_url( $var, '', llms_get_page_url( 'myaccount' ) ); ?>" title="Example tile shortcut" class="tile-box tile-box-shortcut btn-success">
				    <div class="tile-header"><?php echo $data['title']; ?></div>
				    <div class="tile-content-wrapper">
				        <i class="fa fa-cubes" aria-hidden="true"></i>
				    </div>
				</a>
			<?php } else if($data['title'] == "Edit Account") { ?>
			<a href="<?php echo isset( $data['url'] ) ? $data['url'] : llms_get_endpoint_url( $var, '', llms_get_page_url( 'myaccount' ) ); ?>" title="Example tile shortcut" class="tile-box tile-box-shortcut btn-info">
				    <div class="tile-header"><?php echo $data['title']; ?></div>
				    <div class="tile-content-wrapper">
				        <i class="fa fa-address-book" aria-hidden="true"></i>
				    </div>
				</a>
			<?php } else if($data['title'] == "Redeem a Voucher") { ?>
			<a href="<?php echo isset( $data['url'] ) ? $data['url'] : llms_get_endpoint_url( $var, '', llms_get_page_url( 'myaccount' ) ); ?>" title="Example tile shortcut" class="tile-box tile-box-shortcut btn-warning">
				    <div class="tile-header"><?php echo $data['title']; ?></div>
				    <div class="tile-content-wrapper">
				        <i class="fa fa-tag" aria-hidden="true"></i>
				    </div>
				</a>
			<?php } else if($data['title'] == "Order History") { ?>
			<a href="<?php echo isset( $data['url'] ) ? $data['url'] : llms_get_endpoint_url( $var, '', llms_get_page_url( 'myaccount' ) ); ?>" title="Example tile shortcut" class="tile-box tile-box-shortcut btn-primary">
				    <div class="tile-header"><?php echo $data['title']; ?></div>
				    <div class="tile-content-wrapper">
				        <i class="fa fa-history" aria-hidden="true"></i>
				    </div>
				</a>
			<?php } else if($data['title'] == "Sign Out") { ?>
                <a href="<?php echo isset( $data['url'] ) ? $data['url'] : llms_get_endpoint_url( $var, '', llms_get_page_url( 'myaccount' ) ); ?>" title="Example tile shortcut" class="tile-box tile-box-shortcut btn-danger">
                    <div class="tile-header"><?php echo $data['title']; ?></div>
                    <div class="tile-content-wrapper">
                        <i class="fa fa-sign-out" aria-hidden="true"></i>
                    </div>
                </a>
            <?php } ?>
		</div>
	<?php endforeach; ?>
</div>