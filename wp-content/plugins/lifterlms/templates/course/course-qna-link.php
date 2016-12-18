<?php
/**
 * LifterLMS Course Meta Information Wrapper Start
 * @author 		LifterLMS
 * @package 	LifterLMS/Templates
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }


global $post;

$course = null;

if( $post->post_type == 'lesson'){
    $parent_course = get_post_meta( $post->ID, '_parent_course', true );
    $course = new LLMS_Course($parent_course);
}
else {
    $course = new LLMS_Course($post);
}

$title = $course->get_title();
$link = '#';

foreach (get_terms('dwqa-question_category') as $cat){
    if($cat->name == $title){
        $link = get_category_link($cat->term_id);
        break;
    }
}
?>

<div class="llms-syllabus-wrapper center-button">
    <a href="<?php echo $link; ?>" target="_blank" class="roll-button button-slider">Q&A Section</a>
</div>