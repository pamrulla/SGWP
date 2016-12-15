<?php
/**
 * Course difficulty template
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;

$course = new LLMS_Course( $post );

if ( ! $course->get_difficulty() ) {
	return;
}
?>

<div class="llms-meta llms-difficulty">
	<div class="col-sm-6">
		<span>Difficulty</span>
	</div>
	<div class="col-sm-6">
		<?php printf( __( '<span class="difficulty">%s</span>', 'lifterlms' ), $course->get_difficulty() ); ?>
	</div>
</div>
