<?php
/**
 * Course Outline Small List
 */
?>
<div class="llms-widget-syllabus<?php echo ( $collapse ) ? ' llms-widget-syllabus--collapsible' : ''; ?>">
<h3>Syllabus</h3>
	<?php do_action( 'lifterlms_outline_before' ); ?>

	<ul class="llms-course-outline">
		<?php if ( $collapse && $toggles ) : ?>

			<li class="llms-section llms-syllabus-footer">

				<?php do_action( 'lifterlms_outline_before_footer' ); ?>

				<a class="roll-button roll-small-button border llms-button-text llms-collapse-toggle" data-action="open" href="#"><?php _e( 'Open All', 'lifterlms' ); ?></a>
				
				<a class="roll-button roll-small-button border llms-button-text llms-collapse-toggle" data-action="close" href="#"><?php _e( 'Close All', 'lifterlms' ); ?></a>

				<?php do_action( 'lifterlms_outline_after_footer' ); ?>

			</li>

		<?php endif; ?>
		<?php //get section data
		foreach ( $sections as $section ) : ?>

			<li class="panel panel-default llms-section<?php echo ( $collapse ) ? ( $current_section && $section['id'] == $current_section ) ? ' llms-section--opened' : ' llms-section--closed' : ''; ?>">

				<div class="panel-heading section-header">

					<?php do_action( 'lifterlms_outline_before_header' ); ?>

					<?php if ( $collapse ) : ?>

						<span class="llms-collapse-caret">

							<i class="fa fa-caret-down"></i>
							<i class="fa fa-caret-right"></i>

						</span>

					<?php endif; ?>

					<span class="section-title"><?php echo $section['title']; ?></span>

					<?php do_action( 'lifterlms_outline_after_header' ); ?>

				</div>

				<?php //loop through sections
				foreach ( $syllabus->lessons as $lesson ) :

					if ( $lesson['parent_id'] == $section['id'] ) : ?>

						<ul class="panel-body llms-lesson">

							<li>

								<span class="llms-lesson-complete <?php echo ( $lesson['is_complete'] ? 'done' : '' ); ?>">

									<i class="fa fa-check-circle"></i>

								</span>

								<?php do_action( 'lifterlms_outline_before_lesson_title', $lesson ); ?>

								<span class="lesson-title <?php echo ( $lesson['is_complete'] ? 'done' : '' ); ?>">

									<?php $l = new LLMS_Lesson( $lesson['id'] ); ?>

									<?php if ( $l->is_free() || llms_is_user_enrolled( get_current_user_id(), $course->id ) ) : ?>

										<a href="<?php echo get_permalink( $lesson['id'] ); ?>"><?php echo $lesson['title']; ?></a>

									<?php else :

										echo $lesson['title'];

									endif; ?>

								</span>

								<?php do_action( 'lifterlms_outline_after_lesson_title', $lesson ); ?>

							</li>

						</ul>

					<?php endif;

				endforeach; ?>

			</li>

		<?php endforeach; ?>

	</ul>

	<?php do_action( 'lifterlms_outline_after' ); ?>

</div>
