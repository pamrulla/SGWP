<?php
/**
 * Lesson Video embed
 * @since    1.0.0
 * @version  3.1.1
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;

$lesson = new LLMS_Lesson( $post );

if ( ! $lesson->get( 'video_embed' ) ) { return; }
?>

<div class="llms-video-wrapper">
	<div class="center-video">
		<?php echo '<iframe src="https://player.vimeo.com/video/' . $lesson->get_video() . '?title=0&byline=0&portrait=0" height="200" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>'; ?>
	</div>
</div>
