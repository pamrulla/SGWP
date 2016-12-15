<?php
/**
 * Course categories template
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;
?>

<div class="llms-meta llms-categories">
	<div class="col-sm-6">
		<span>Categories</span>
	</div>
	<div class="col-sm-6">
		<?php echo get_the_term_list( $post->ID, 'course_cat', '', ', ', '' ); ?>
	</div>
</div>
