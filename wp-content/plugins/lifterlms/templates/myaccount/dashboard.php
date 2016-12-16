<?php
/**
 * My Account page
 *
 * @author 		codeBOX
 * @package 	lifterlMS/Templates
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

llms_print_notices();
?>

<div class="llms-sd-tab dashboard">

	<?php do_action( 'lifterlms_before_student_dashboard_tab' ); ?>

	<?php printf( __( '<p>Hello <strong>%1$s</strong></p>', 'lifterlms' ), $current_user->display_name ); ?>

	<?php echo apply_filters( 'lifterlms_account_greeting', '' ); ?>

	<?php do_action( 'lifterlms_after_student_dashboard_greeting' ); ?>

	<div class="row">
		<div class="col-sm-12 col-md-6">
			<?php llms_get_template( 'myaccount/my-courses.php', array(
				'courses' => $courses,
				'student' => $student,
			) ); ?>
		</div>
		<div class="col-sm-12 col-md-6">
			<?php llms_get_template( 'myaccount/my-certificates.php' ); ?>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12 col-md-6">
			<?php llms_get_template( 'myaccount/my-achievements.php' ); ?>
		</div>
		<div class="col-sm-12 col-md-6">
			<?php llms_get_template( 'myaccount/my-memberships.php' ); ?>
		</div>
	</div>
	<?php do_action( 'lifterlms_after_student_dashboard_tab' ); ?>

</div>
