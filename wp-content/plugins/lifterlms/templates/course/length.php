<?php
/**
 * LifterLMS Course Length Meta Info
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;

$course = new LLMS_Course( $post );

if ( ! $course->get( 'length' ) ) {	return; }
?>

<div class="llms-meta llms-course-length">
	<div class="col-sm-6">
		<span>Estimated Time</span>
	</div>
	<div class="col-sm-6">
		<?php printf( __( '<span class="length">%s</span>', 'lifterlms' ), $course->get( 'length' ) ); ?>
	</div>
</div>

