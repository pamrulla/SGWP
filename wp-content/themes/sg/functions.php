<?php
/**
 * Sydney functions and definitions
 *
 * @package sg
 */


// This theme uses wp_nav_menu() in one location.
register_nav_menus( array(
    'primary' => __( 'Primary Menu', 'sg' ),
    'secondary' => __('Secondary Navigation', 'sg')
) );


/**
 * Display LifterLMS Course and Lesson sidebars
 * on courses and lessons in place of the sidebar returned by
 * this function
 * @param    string     $id    default sidebar id (an empty string)
 * @return   string
 */
function my_llms_sidebar_function( $id ) {
	
	$my_sidebar_id = 'sidebar-1'; // replace this with your theme's sidebar ID

	return $my_sidebar_id;

}
add_filter( 'llms_get_theme_default_sidebar', 'my_llms_sidebar_function' );

/**
 * Register widget area.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_sidebar
 */
function sg_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Sidebar', 'sg' ),
		'id'            => 'sidebar-1',
		'description'   => '',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );

	//Footer widget areas
	$widget_areas = get_theme_mod('footer_widget_areas', '3');
	for ($i=1; $i<=$widget_areas; $i++) {
		register_sidebar( array(
			'name'          => __( 'Footer ', 'sg' ) . $i,
			'id'            => 'footer-' . $i,
			'description'   => '',
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		) );
	}

	//Register the front page widgets
	if ( function_exists('siteorigin_panels_activate') ) {
		register_widget( 'SG_Widget_Recent_Courses' );
        register_widget( 'SG_Services_Type_A' );
        register_widget( 'Sg_Portfolio' );
	}

}
add_action( 'widgets_init', 'sg_widgets_init' );

/**
 * Load the front page widgets.
 */
if ( function_exists('siteorigin_panels_activate') ) {
	require get_stylesheet_directory() . "/widgets/widget-recent-courses.php";
    require get_stylesheet_directory() . "/widgets/fp-services-type-a.php";
    require get_stylesheet_directory() . "/widgets/fp-portfolio.php";
}

