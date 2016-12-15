<?php
/**
 * Template for the Course Syllabus Displayed on individual course pages
 *
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 * @since       1.0.0
 * @version     3.0.0 - refactored for sanity's sake
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;

$course = null;
$parent_course = null;

if( $post->post_type == 'lesson'){
    $parent_course = get_post_meta( $post->ID, '_parent_course', true );
    $course = new LLMS_Course($parent_course);
}
else {
    $course = new LLMS_Course($post);
}

// retrieve sections to use in the template
$sections = $course->get_sections( 'posts' );
?>

<div class="clear"></div>

<div class="llms-syllabus-wrapper">

	<?php if ( ! $sections ) : ?>

		<?php _e( 'This course does not have any sections.', 'lifterlms' );
		print_r($post);?>

	<?php else : ?>

		<?php 
			$collapse = 1;
			$toggles = 1;
			echo do_shortcode( '[lifterlms_course_outline collapse="' . $collapse . '" toggles="' . $toggles . '"]' ); 
		?>

	<?php endif; ?>

	<div class="clear"></div>
</div>
