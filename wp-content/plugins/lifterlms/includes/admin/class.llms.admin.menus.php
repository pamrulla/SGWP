<?php
/**
 * Admin Assets Class
 *
 * Sets up admin menu items.
 * @since   1.0.0
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LLMS_Admin_Menus {

	/**
	 * Constructor
	 * @since   1.0.0
	 * @version 3.0.0
	 */
	public function __construct() {

		add_filter( 'custom_menu_order', array( $this, 'submenu_order' ) );
		add_action( 'admin_menu', array( $this, 'display_admin_menu' ) );

	}

	/**
	 * Remove the default menu page from the submenu
	 * @param  array
	 * @return array
	 * @since   1.0.0
	 * @version 3.2.0
	 */
	public function submenu_order( $menu_ord ) {
		global $submenu;

		if ( isset( $submenu['lifterlms'] ) ) {
			unset( $submenu['lifterlms'][0] );
		}

		return $menu_ord;
	}

	/**
	 * Admin Menu
	 * @return void
	 * @since   1.0.0
	 * @version 3.2.0
	 */
	public function display_admin_menu() {

		global $menu;

		if ( current_user_can( apply_filters( 'lifterlms_admin_menu_access', 'manage_options' ) ) ) {

			$menu[51] = array( '', 'read', 'llms-separator','','wp-menu-separator' );

			add_menu_page( 'lifterlms', 'LifterLMS', apply_filters( 'lifterlms_admin_settings_access', 'manage_options' ), 'lifterlms', array( $this, 'settings_page_init' ), plugin_dir_url( LLMS_PLUGIN_FILE ) . 'assets/images/lifterLMS-wp-menu-icon.png', 51 );

			add_submenu_page( 'lifterlms', __( 'LifterLMS Settings', 'lifterlms' ), __( 'Settings', 'lifterlms' ), apply_filters( 'lifterlms_admin_settings_access', 'manage_options' ), 'llms-settings', array( $this, 'settings_page_init' ) );

			add_submenu_page( 'lifterlms', __( 'LifterLMS Reporting', 'lifterlms' ), __( 'Reporting', 'lifterlms' ), apply_filters( 'lifterlms_admin_reporting_access', 'manage_options' ), 'llms-reporting', array( $this, 'reporting_page_init' ) );

			add_submenu_page( 'lifterlms', __( 'LifterLMS System report', 'lifterlms' ), __( 'System Report', 'lifterlms' ), apply_filters( 'lifterlms_admin_system_report_access', 'manage_options' ), 'llms-system-report', array( $this, 'system_report_page_init' ) );

		}

	}

	/**
	 * Output the HTLM for admin settings screens
	 * @return void
	 */
	public function settings_page_init() {
		include_once( 'class.llms.admin.settings.php' );
		LLMS_Admin_Settings::output();
	}

	/**
	 * Output the HTML for the reporting screens
	 * @return   void
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function reporting_page_init() {
		require_once 'reporting/class.llms.admin.reporting.php';
		$gb = new LLMS_Admin_Reporting();
		$gb->output();
	}

	/**
	 * Output the HTLM for the System Report page
	 * @return void
	 */
	public function system_report_page_init() {
		include_once( 'class.llms.admin.system-report.php' );
		LLMS_Admin_System_Report::output();
	}
}

return new LLMS_Admin_Menus();
