<?php
/**
 * My Courses List
 * Used in My Account and My Courses shortcodes
 *
 * @version  3.0.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
global $wp_query;
?>

<div class="llms-sd-section llms-my-courses">
	<h5 class="llms-sd-section-title"><?php echo apply_filters( 'lifterlms_my_courses_title', __( 'Courses In-Progress', 'lifterlms' ) ); ?></h5>
	<?php $current_tab = LLMS_Student_Dashboard::get_current_tab( 'slug' ); ?>
	<?php if ( ! $courses['results'] ) : ?>
		<p><?php _e( 'You are not enrolled in any courses.', 'lifterlms' ); ?></p>
	<?php else : ?>
		<table class="table table-striped">
			<thead>
				<tr>
					<th>#</th>
					<th>Title</th>
					<th>Started</th>
					<th>Progress</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
			<?php $count = 0;
			foreach ( $courses['results'] as $c ) : $c = new LLMS_Course( $c ); ?>
				<?php $count++; if( $count > 5 && $current_tab == 'dashboard') { break; } ?>
				<tr>
					<td> 
						<div class="course-image llms-image-thumb effect">
						<?php echo lifterlms_get_featured_image( $c->get_id() ); ?> 
						</div>
					</td>
					<td> <strong><?php echo $c->get_title(); ?></strong> </td>
					<td>
						<?php echo apply_filters('lifterlms_my_courses_start_date_html',
									$student->get_enrollment_date( $c->get_id(), 'enrolled' )
								); ?>
					</td>
					<td>
						<div class="llms-progress">
						<?php $progress = $c->get_percent_complete( $student->get_id() ); ?>
							<div class="progress__indicator"><?php echo $progress; ?>%</div>
							<div class="llms-progress-bar">
							    <div class="progress-bar-complete" style="width:<?php echo $progress ?>%"></div>
							</div>
						</div>
					</td>
					<td>
						<a href="<?php echo $c->get_permalink(); ?>" class="button llms-button-primary"><?php echo apply_filters( 'lifterlms_my_courses_course_button_text', __( 'View Course', 'lifterlms' ) ); ?></a>
					</td>
				</tr>				
			<?php endforeach; ?>
			</tbody>
		</table>

		<footer class="llms-sd-pagination llms-my-courses-pagination text-right" style="margin-right: 5px;">
			<?php if ( isset( $wp_query->query_vars['my-courses'] ) ) : ?>
				<?php if ( $courses['skip'] > 0 ) : ?>
					<a class="llms-button-text" href="<?php echo esc_url( add_query_arg( array(
						'limit' => $courses['limit'],
						'skip' => $courses['skip'] - $courses['limit'],
					), llms_person_my_courses_url() ) ); ?>"><?php _e( 'Back', 'lifterlms' ); ?></a>
				<?php endif; ?>

				<?php if ( $courses['more'] ) : ?>
					<a class="llms-button-text" href="<?php echo esc_url( add_query_arg( array(
						'limit' => $courses['limit'],
						'skip' => $courses['skip'] + $courses['limit'],
					), llms_person_my_courses_url() ) ); ?>"><?php _e( 'Next', 'lifterlms' ); ?></a>
				<?php endif; ?>
			<?php else : ?>

				<?php if ( count( $courses['results'] ) ) : ?>
					<a class="llms-button-text" href="<?php echo esc_url( llms_person_my_courses_url() ); ?>"><?php _e( 'View All My Courses', 'lifterlms' ); ?></a>
				<?php endif; ?>

			<?php endif; ?>
		</footer>

	<?php endif; ?>
</div>
