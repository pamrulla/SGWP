<?php
/**
 * Display a course progress bar and
 * a button for the next incomplete lesson in the course
 *
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

global $post;

$isLesson = false;
if( $post->post_type == 'lesson'){
    $parent_course = get_post_meta( $post->ID, '_parent_course', true );
    $post = get_post($parent_course);
    $isLesson = true;
}

if ( ! llms_is_user_enrolled( get_current_user_id(), $post->ID ) ) {
	return;
}

$student = new LLMS_Student();

$progress = $student->get_progress( $post->ID, 'course' );
?>

<div class="llms-course-progress">

	<?php if ( apply_filters( 'lifterlms_display_course_progress_bar', true ) ) : ?>
        <h3>Progress</h3>
		<?php lifterlms_course_progress_bar( $progress, false, false ); ?>

	<?php endif; ?>

    <?php if(!$isLesson) : ?>
        <?php if ( 100 == $progress ) : ?>

            <p><?php _e( 'Course Complete', 'lifterlms' ); ?></p>

        <?php else : ?>

            <?php if ( $lesson = $student->get_next_lesson( $post->ID ) ) : ?>

                <a class="llms-button-primary" href="<?php echo get_permalink( $lesson ); ?>">

                    <?php if ( 0 == $progress ) : ?>

                        <?php _e( 'Get Started', 'lifterlms' ); ?>

                    <?php else : ?>

                        <?php _e( 'Continue', 'lifterlms' ); ?>

                    <?php endif; ?>

                </a>

            <?php endif; ?>

        <?php endif; ?>
    <?php endif; ?>

</div>
