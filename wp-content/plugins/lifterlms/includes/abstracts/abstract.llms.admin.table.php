<?php
/**
 * Admin GradeBook Tables
 *
 * @since   3.2.0
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

abstract class LLMS_Admin_Table {

	/**
	 * Unique ID for the Table
	 * @var  string
	 */
	protected $id = '';

	/**
	 * When pagination is enabled, the current page
	 * @var  integer
	 */
	protected $current_page = 1;

	/**
	 * When pagination enabled, determines if this is the last page of results
	 * @var  boolean
	 */
	protected $is_last_page = true;

	/**
	 * If true, tfoot will add ajax pagination links
	 * @var  boolean
	 */
	protected $is_paginated = false;

	/**
	 * Determine of the table is searchable
	 * @var  boolean
	 */
	protected $is_searchable = false;

	/**
	 * If true, tbody will be zebra striped
	 * @var  boolean
	 */
	protected $is_zebra = true;

	/**
	 * Results sort order
	 * 'ASC' or 'DESC'
	 * Only applicable of $orderby is not set
	 * @var  string
	 */
	protected $order = '';

	/**
	 * Field results are sorted by
	 * @var  string
	 */
	protected $orderby = '';

	/**
	 * The search query submitted for a searchable table
	 * @var  string
	 */
	protected $search = '';

	/**
	 * Table Data
	 * Array of objects or arrays
	 * each item represents as row in the table's body, each item is a cell
	 * @var  array
	 */
	protected $tbody_data = array();

	/**
	 * Table Title Displayed on Screen
	 * @var  string
	 */
	protected $title = '';

	/**
	 * Retrieve data for a cell
	 * @param    string     $key   the column id / key
	 * @param    mixed      $data  object / array of data that the function can use to extract the data
	 * @return   mixed
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	abstract protected function get_data( $key, $data );

	/**
	 * Execute a query to retrieve results from the table
	 * @param    array      $args  array of query args
	 * @return   void
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	abstract public function get_results( $args = array() );

	/**
	 * Define the structure of arguments used to pass to the get_results method
	 * @return   array
	 * @since    2.3.0
	 * @version  2.3.0
	 */
	abstract public function set_args();

	/**
	 * Define the structure of the table
	 * @return   array
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	abstract protected function set_columns();

	/**
	 * Constructor
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Ensure that a valid array of data is passed to a query
	 * Used by AJAX methods to clean unnecssarry parameters before passing the request data
	 * to the get_results function
	 * @param    array      $args  array of arguments
	 * @return   array
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	protected function clean_args( $args = array() ) {

		$allowed = array_keys( $this->get_args() );

		foreach ( $args as $key => $val ) {
			if ( ! in_array( $key, $allowed ) ) {
				unset( $args[ $key ] );
			}
		}

		return $args;

	}

	/**
	 * Ensures that all data requested by $this->get_data if filterable
	 * before being output on screen
	 * @param    mixed     $value  value to be displayed
	 * @param    string    $key    column key / id
	 * @param    mixed     $data   original data object / array
	 * @return   mixed
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	protected function filter_get_data( $value, $key, $data ) {
		return apply_filters( 'llms_gradebook_get_data_' . $this->id, $value, $key, $data );
	}

	/**
	 * Retrieve the arguments defined in `set_args`
	 * @return   array
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_args() {

		$default = array(
			'page'    => $this->get_current_page(),
			'order'   => $this->get_order(),
			'orderby' => $this->get_orderby(),
		);

		if ( $this->is_searchable ) {
			$default['search'] = $this->get_search();
		}

		$args = wp_parse_args( $this->set_args(), $default );

		return apply_filters( 'llms_gradebook_get_args_' . $this->id, $args );
	}

	/**
	 * Retrieve the array of columns defined by set_columns
	 * @return   array
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_columns() {
		return apply_filters( 'llms_gradebook_get_' . $this->id . '_columns', $this->set_columns() );
	}

	/**
	 * Get the current page
	 * @return   int
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_current_page() {
		return $this->current_page;
	}

	/**
	 * Get $this->empty_msg string
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_empty_message() {
		return apply_filters( 'llms_gradebook_get_' . $this->id . '_empty_message', $this->set_empty_message() );
	}

	/**
	 * Retrieve a modified classname that can be passed via AJAX for new queries
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_handler() {
		return str_replace( 'LLMS_Table_', '', get_class( $this ) );
	}

	/**
	 * Get the current sort order
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Get the current field results are ordered by
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_orderby() {
		return $this->orderby;
	}

	/**
	 * Gets the opposite of the current order
	 * Used to determine what order should be displayed when resorting
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	protected function get_new_order( $orderby = '' ) {

		// current order matches submitted order, return oppossite
		if ( $this->orderby === $orderby ) {
			return ( 'ASC' === $this->order ) ? 'DESC' : 'ASC';
		} // return ASC
		else {
			return 'ASC';
		}

	}

	/**
	 * Retrieves the current search query
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_search() {
		return esc_attr( trim( $this->search ) );
	}

	/**
	 * Get the HTML for the entire table
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_table_html() {
		ob_start();
		?>
		<div class="llms-table-wrap">
			<header class="llms-table-header">
				<?php echo $this->get_table_title_html(); ?>
				<?php if ( $this->is_searchable ) : ?>
					<?php echo $this->get_table_search_form_html(); ?>
				<?php endif; ?>
			</header>
			<table
				class="llms-table llms-gb-table llms-gb-table-<?php echo $this->id; ?><?php echo $this->is_zebra ? ' zebra' : ''; ?>"
				data-args='<?php echo json_encode( $this->get_args() ); ?>'
				data-handler="<?php echo $this->get_handler(); ?>"
				id="llms-gb-table-<?php echo $this->id; ?>"
			>
				<?php echo $this->get_thead_html(); ?>
				<?php echo $this->get_tbody_html(); ?>
				<?php echo $this->get_tfoot_html(); ?>
			</table>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the HTML of the search form for a searchable table
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_table_search_form_html() {
		ob_start();
		?>
		<div class="llms-table-search">
			<input class="regular-text" id="<?php echo $this->id; ?>" placeholder="<?php echo $this->get_table_search_form_placeholder(); ?>" type="text">
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the Text to be used as the placeholder in a searchable tables search input
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_table_search_form_placeholder() {
		return apply_filters( 'llms_gradebook_get_' . $this->id . '_search_placeholder', __( 'Search', 'lifterlms' ) );
	}

	/**
	 * Get the HTML for the table's title
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_table_title_html() {
		$title = apply_filters( 'llms_gradebook_get_' . $this->id . '_table_title', $this->title );
		if ( $title ) {
			return '<h2 class="llms-table-title">' . $title . '</h2>';
		} else {
			return '';
		}
	}

	/**
	 * Get $this->tbody_data array
	 * @return   array
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_tbody_data() {
		return apply_filters( 'llms_gradebook_get_' . $this->id . '_tbody_data', $this->tbody_data );
	}

	/**
	 * Get a tbody element for the table
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_tbody_html() {
		$data = $this->get_tbody_data();
		ob_start();
		?>
		<tbody>
			<?php if ( $data ) : ?>
				<?php foreach ( $data as $row ) : ?>
					<?php echo $this->get_tr_html( $row ); ?>
				<?php endforeach; ?>
			<?php else : ?>
				<tr><td class="llms-gb-table-empty" colspan="<?php echo $this->get_columns_count(); ?>"><p><?php echo $this->get_empty_message(); ?></p></td></tr>
			<?php endif; ?>
		</tbody>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get a tfoot element for the table
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_tfoot_html() {
		ob_start();
		?>
		<tfoot>
			<tr>
				<th colspan="<?php echo $this->get_columns_count(); ?>">
					<?php if ( $this->is_paginated ) : ?>
						<?php if ( 1 !== $this->get_current_page() ) : ?>
							<button class="llms-button-primary small" data-dir="back" name="llms-table-paging"><span class="dashicons dashicons-arrow-left-alt2"></span> <?php _e( 'Back', 'lifterlms' ); ?></button>
						<?php endif; ?>
						<?php if ( ! $this->is_last_page ) : ?>
							<button class="llms-button-primary small" data-dir="next" name="llms-table-paging"><?php _e( 'Next', 'lifterlms' ); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></button>
						<?php endif; ?>
					<?php endif; ?>
				</th>
			</tr>
		</tfoot>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get a thead element for the table
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_thead_html() {
		ob_start();
		?>
		<thead>
			<tr>
			<?php foreach ( $this->get_columns() as $id => $data ) : ?>
				<th class="<?php echo $id; ?>">
					<?php if ( is_array( $data ) ) : ?>
						<?php if ( isset( $data['sortable'] ) && $data['sortable'] ) : ?>
							<a class="llms-sortable<?php echo ( $this->get_orderby() === $id ) ? ' active' : ''; ?>" data-order="<?php echo $this->get_new_order( $id ); ?>" data-orderby="<?php echo $id; ?>" href="#llms-gb-table-resort">
								<?php echo $data['title']; ?>
								<span class="dashicons dashicons-arrow-up asc"></span>
								<span class="dashicons dashicons-arrow-down desc"></span>
							</a>
						<?php else : ?>
							<?php echo $data['title']; ?>
						<?php endif; ?>
					<?php else : ?>
						<?php echo $data; ?>
					<?php endif; ?>
				</th>
			<?php endforeach; ?>
			</tr>
		</thead>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the HTML for a single row in the body of the table
	 * @param    mixed     $row  array/object of data describing a single row in the table
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_tr_html( $row ) {
		ob_start();
		do_action( 'llms_gradebook_table_before_tr', $row );
		?>
		<tr>
		<?php foreach ( $this->get_columns() as $id => $title ) : ?>
			<td class="<?php echo $id; ?>"><?php echo $this->get_data( $id, $row ); ?></td>
		<?php endforeach; ?>
		</tr>
		<?php
		do_action( 'llms_gradebook_table_after_tr', $row );
		return ob_get_clean();
	}

	/**
	 * Get the total number of columns in the table
	 * Useful for creating full with tds via colspan
	 * @return   int
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_columns_count() {
		return count( $this->get_columns() );
	}

	/**
	 * Get the HTML for a WP Post Link
	 * @param    int        $post_id  WP Post ID
	 * @param    string     $text     Optional text to display within the anchor, if none supplied $post_id if used
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	public function get_post_link( $post_id, $text = '' ) {
		if ( ! $text ) {
			$text = $post_id;
		}
		return '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . $text . '</a>';
	}

	/**
	 * Allow custom hooks to be registered for use within the class
	 * @return   void
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	protected function register_hooks() {}

	/**
	 * Setter
	 * @param    string     $key  variable name
	 * @param    mixed      $val  variable data
	 * @since    2.3.0
	 * @version  2.3.0
	 */
	public function set( $key, $val ) {
		$this->$key = $val;
	}

	/**
	 * Empty message displayed when no results are found
	 * @return   string
	 * @since    3.2.0
	 * @version  3.2.0
	 */
	protected function set_empty_message() {
		return apply_filters( 'llms_gradebook_default_empty_message', __( 'No results were found.', 'lifterlms' ) );
	}

}
