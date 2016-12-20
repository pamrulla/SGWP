<?php
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'Rainmaker' ) ) {

	class Rainmaker {
		var $plugin_url;
    	var $plugin_path;
    	var $version;
		function __construct() {
		    $this->plugin_url   = untrailingslashit( plugins_url( '/', __FILE__ ) ) .'/';
		    $this->plugin_path  = untrailingslashit( plugin_dir_path( __FILE__ ) );
		    $this->version = "0.16.1";
		    //welcome
		    add_action( 'admin_init', array( &$this, 'welcome' ) );
			add_action( 'init', array( &$this, 'register_rainmaker_form_post_type' ) );
			add_action( 'init', array( &$this, 'register_lead_post_type' ) );
			add_action( 'admin_init', array( &$this, 'import_ig_forms' ) );
		    add_action( 'edit_form_before_permalink', array( &$this, 'form_design_content' ) );
		    add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_admin_styles_and_scripts' ) );
		    add_action( 'wp_footer', array( &$this, 'enqueue_frontend_styles_and_scripts' ) );
			add_action( 'save_post', array( &$this, 'save_form_settings' ), 10, 2 );
			add_shortcode( 'rainmaker_form', array( &$this, 'execute_shortcode' ) );
			
			//remove all actions
			add_filter( 'post_row_actions', array(&$this, 'remove_rm_form_action'), 10, 2 );
			//remove bulk action
			add_filter('bulk_actions-edit-rainmaker_lead', array(&$this, 'remove_lead_bulk_action'), 10, 2);

			//add columns
			add_filter( 'manage_edit-rainmaker_form_columns', array( &$this, 'edit_form_columns' ) );
		    add_action( 'manage_rainmaker_form_posts_custom_column', array( &$this, 'custom_form_columns' ), 2 );
			
			add_filter( 'manage_edit-rainmaker_lead_columns', array( &$this, 'edit_lead_columns' ) );
		    add_action( 'manage_rainmaker_lead_posts_custom_column', array( &$this, 'custom_lead_columns' ), 2 );
			//sort_lead_columns
			add_filter( 'manage_edit-rainmaker_lead_sortable_columns', array( &$this, 'sort_lead_columns' ), 2 );
	        
	        add_filter( 'rainmaker_prepare_lead', array( &$this , 'maipoet_prepare_lead' ), 10, 2 );
	        add_filter( 'rainmaker_prepare_lead', array( &$this , 'madmimi_prepare_lead' ), 10, 2 );
	        add_filter( 'rainmaker_prepare_lead', array( &$this , 'rainmaker_prepare_lead' ), 100, 2 );
	        add_filter( 'rainmaker_validate_request', array( &$this , 'rainmaker_validate_request' ), 100, 2 );
	        add_action( 'rainmaker_post_lead', array( &$this, 'trigger_webhook' ), 10, 2); 

	        //mail on form submission 
	        add_action( 'rainmaker_post_lead', array( &$this, 'rm_send_mail' ), 10, 2); 
			
			//filter lead data
        	add_filter( 'rainmaker_filter_lead', array(&$this, 'rm_filter_lead_data'));

			// execute shortcode in sidebar
        	add_filter( 'widget_text', array(&$this, 'rm_widget_text_filter') );

		    if ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) {
		        add_action( 'wp_ajax_rm_rainmaker_add_lead', array( &$this, 'rm_rainmaker_add_lead' ) );
		        add_action( 'wp_ajax_nopriv_rm_rainmaker_add_lead', array( &$this, 'rm_rainmaker_add_lead' ) );
		    }
		    if(is_admin()){
		    	require_once( 'mailers/config.php' );
		    }
		    //include plus features
		    $plus_file = (array) glob( $this->plugin_path . '/../plus/*.php' );
	        if (empty($plus_file)) {
	            return;		
	        }
	        foreach ($plus_file as $file) {
	            if (is_file ( $file )) {
                    include_once( $file );
	            }
	        }
		}

		function welcome(){
			if ( false === get_option( '_icegram_rm_activation_redirect' ) )
            return;
	        // Delete the redirect transient
	        delete_option( '_icegram_rm_activation_redirect' );
	        $this->import_sample_data();
	        wp_redirect( admin_url( 'edit.php?post_type=rainmaker_form' ) );
	        exit;
		}

		public function rainmaker_validate_request($request) {
	        return $request;
	    }

	    // TODO :: for Test 
		// Formate madmimi leadata according to Rainmaker Lead
		public function madmimi_prepare_lead($lead_data, $rm_form_settings) {
			if(!empty($lead_data['signup']) ){
				$lead_data['rm_lead_email'] = !empty($lead_data['signup']['email']) ? $lead_data['signup']['email'] : '';
				$lead_data['rm_lead_name'] = !empty($lead_data['signup']['name']) ? $lead_data['signup']['name'] : '';
			}
			return $lead_data;
		}

		// Formate mailpoet leadata according to Rainmaker Lead
		public function maipoet_prepare_lead($lead_data, $rm_form_settings) {
			if(!empty($lead_data['wysija']) && !empty($lead_data['wysija']['user'])){
				$lead_data['rm_lead_email'] = !empty($lead_data['wysija']['user']['email']) ? $lead_data['wysija']['user']['email'] : '';
				$lead_data['rm_lead_name'] = !empty($lead_data['wysija']['user']['firstname']) ? $lead_data['wysija']['user']['firstname'] : '';
			}
			return $lead_data;
		}
		
		public function rainmaker_prepare_lead($lead_data, $rm_form_settings) {

			if(!empty($lead_data) ){

				//Email Field
				if(empty($lead_data['rm_lead_email'])){
					$email = array();
					if(!empty($lead_data['email'])){
						$email[] = $lead_data['email'];
					}else{
						foreach ($lead_data as $key => $value) {
							if(filter_var($lead_data[$key], FILTER_VALIDATE_EMAIL)){
								$email[] = $lead_data[$key];
							}
						}
					}

					//if Email field is empty or invalid then return, when form type='subscription'
					if(empty($email) && $rm_form_settings['type'] == 'subscription'){
						return array();
					} 

					$lead_data['rm_lead_email'] = !empty($email) ? array_shift($email) : '';
				}

				//Name Field
				if(empty($lead_data['rm_lead_name'])){
					$name = array();
					$name_keys = array('name', 'your-name', 'first-name', 'fname', 'firstname');
					foreach ($name_keys as $key) {
						if(isset($lead_data[$key])){
							$name[] = $lead_data[$key];
						}
					}
					$lead_data['rm_lead_name'] = !empty($name) ? array_shift($name) : ''; 
				}
			}
	        return $lead_data;
	    }

		public static function rm_rainmaker_add_lead(){

			//TODO :: senetize data, if Required
			$lead_data = $_REQUEST;

			//remove prefix from data
			if(!empty($_REQUEST['rmfpx_added'])){
				$lead_data = array(); 
				foreach ($_REQUEST as $key => $value) {
					$new_key = explode('rmfpx_', $key);
					if(!empty($new_key[1])){
						$lead_data[$new_key[1]] = $value; 
					}
				}
			}

			$lead_data = apply_filters('rainmaker_validate_request', $lead_data);

			if($lead_data['is_remote'] == true || $lead_data['ig_is_remote'] == true){
			    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
			} 

			if (empty($lead_data['is_remote']) && empty($lead_data['ig_is_remote']) && ( empty( $lead_data['rm_nonce_field'] ) || !wp_verify_nonce( $lead_data['rm_nonce_field'], 'rm_form_submission' ) ) ) {
				wp_die('Authentication failed', 'Invalid Submission', array('response' => 500) );
			}

			if(empty($lead_data)){
				$response['error'] = __('No lead Data', 'icegram-rainmaker');
				echo json_encode($response);
				exit;
			}


			$response = array();
			$form_id = $lead_data['rm_form-id'];
			$client_ip = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

			// Remove Rainmaker fileds
			unset($lead_data['action']);
			unset($lead_data['is_remote']);
			unset($lead_data['ig_is_remote']);
			unset($lead_data['rm_form-id']);
			unset($lead_data['rm_nonce_field']);
			unset($lead_data['added']);

			$rm_form_settings = get_post_meta($form_id, 'rm_form_settings', true);
			$rm_form_settings['form_id'] = $form_id;
			
			// Process Data
			$lead = apply_filters('rainmaker_prepare_lead', $lead_data, $rm_form_settings);

			if(empty($lead)){
				$response['error'] = __('No lead Data', 'icegram-rainmaker');
				echo json_encode($response);
				exit;
			} 

			// add leads to database
			$args = array(  'post_content'   =>   '',
		                    'post_name'      =>   '',
		                    'post_title'     =>   '',
		                    'post_status'    =>   'publish',
		                    'post_type'      =>   'rainmaker_lead'
		                 );
	        $new_lead_id = wp_insert_post( $args );
	        //TODO :: Adding  default values for lead, only for rainmaker lead.
	        // This can be done with and additional filter
	        $email = !empty($lead['rm_lead_email']) ? $lead['rm_lead_email'] : 'unknown@' . $client_ip;
	        $name = !empty($lead['rm_lead_name']) ? $lead['rm_lead_name'] : 'unknown:user';

	        update_post_meta( $new_lead_id, 'email', sanitize_email($email));
	        update_post_meta( $new_lead_id, 'name', sanitize_text_field($name));
	        update_post_meta( $new_lead_id, 'rm_form_id', $form_id);
	        update_post_meta( $new_lead_id, 'rm_raw_data', serialize($lead_data));
	       
	        //filter lead data
	        $lead = apply_filters('rainmaker_filter_lead', $lead);
	       	
			// post leads
	        do_action('rainmaker_post_lead', $lead, $rm_form_settings);

	        //TODO :: Success mesage
	        $response['success'] = __('Lead added successfully', 'icegram-rainmaker');
	        $response['redirection_url'] = (!empty($rm_form_settings['rm_enable_redirection']) && $rm_form_settings['rm_enable_redirection'] == 'yes' && !empty($rm_form_settings['redirection_url'])) ? $rm_form_settings['redirection_url'] : '';
			echo json_encode($response);
			exit;

		}

		function enqueue_admin_styles_and_scripts() {
		    wp_register_script( 'rainmaker_tiptip', $this->plugin_url . '../assets/js/jquery.tipTip.min.js', array ( 'jquery' ), $this->version );
		    wp_enqueue_script( 'rainmaker_tiptip' );
		    wp_register_script( 'rainmaker_admin', $this->plugin_url . '../assets/js/admin.js', array ( 'jquery','jquery-ui-core', 'jquery-ui-tabs','rainmaker_tiptip'), $this->version );
		    wp_enqueue_script( 'rainmaker_admin' );
		    wp_enqueue_style( 'rainmaker_admin_styles', $this->plugin_url . '../assets/css/admin.css', array(), $this->version  );
		}

		//enqueue frontend sctipt
		function enqueue_frontend_styles_and_scripts(){
		    wp_register_script('rm_main_js', $this->plugin_url . '../assets/js/main.js', array(), $this->version);
			$rm_pre_data['ajax_url'] = admin_url( 'admin-ajax.php' );
			$rm_pre_data['rm_nonce_field'] = wp_create_nonce( "rm_form_submission" );
			
			if( !wp_script_is( 'rm_main_js' ) ) {
		        wp_enqueue_script( 'rm_main_js' );
		        wp_localize_script( 'rm_main_js', 'rm_pre_data'  , $rm_pre_data);
		    }
		}

		//form submmision js
		function register_rainmaker_form_post_type() {
		    $labels = array(
		        'name'               => __( 'Rainmaker', 'icegram-rainmaker' ),
		        'singular_name'      => __( 'Form', 'icegram-rainmaker' ),
		        'add_new'            => __( 'Create New', 'icegram-rainmaker' ),
		        'add_new_item'       => __( 'Create New Form', 'icegram-rainmaker' ),
		        'edit_item'          => __( 'Edit Form', 'icegram-rainmaker' ),
		        'new_item'           => __( 'New Form', 'icegram-rainmaker' ),
		        'all_items'          => __( 'Forms', 'icegram-rainmaker' ),
		        'view_item'          => __( 'View Form', 'icegram-rainmaker' ),
		        'search_items'       => __( 'Search Forms', 'icegram-rainmaker' ),
		        'not_found'          => __( 'No Forms found', 'icegram-rainmaker' ),
		        'not_found_in_trash' => __( 'No Forms found in Trash', 'icegram-rainmaker' ),
		        'parent_item_colon'  => __( '', 'icegram-rainmaker' ),
		        'menu_name'          => __( 'Rainmaker', 'icegram-rainmaker' )
		    );
		    $args = array(
		        'labels'             => $labels,
		        'public'             => false,
		        'publicly_queryable' => false,
		        'show_ui'            => true,
		        'show_in_menu'       => true,
		        'query_var'          => true,
		        'rewrite'            => array( 'slug' => 'rainmaker_form' ),
		        'capability_type'    => 'post',
		        'has_archive'        => false,
		        'hierarchical'       => false,
		        'menu_position'      => null,
		        'menu_icon'          => $this->plugin_url . '../assets/images/rm_logo_18.png' ,
		        'supports'           => array( 'title' )
		    );

		    register_post_type( 'rainmaker_form', $args );
		}

		 // Register lead post type
		function register_lead_post_type() {
		    $labels = array(
		        'name'               => __( 'Leads', 'icegram-rainmaker' ),
		        'singular_name'      => __( 'Lead', 'icegram-rainmaker' ),
		        'add_new'            => __( 'Create New', 'icegram-rainmaker' ),
		        'add_new_item'       => __( 'Create New Lead', 'icegram-rainmaker' ),
		        'edit_item'          => __( 'Edit Lead', 'icegram-rainmaker' ),
		        'new_item'           => __( 'New Lead', 'icegram-rainmaker' ),
		        'all_items'          => __( 'Leads', 'icegram-rainmaker' ),
		        'view_item'          => __( 'View Lead', 'icegram-rainmaker' ),
		        'search_items'       => __( 'Search Lead', 'icegram-rainmaker' ),
		        'not_found'          => __( 'No lead found', 'icegram-rainmaker' ),
		        'not_found_in_trash' => __( 'No lead found in Trash', 'icegram-rainmaker' ),
		        'parent_item_colon'  => __( '', 'icegram-rainmaker' ),
		        'menu_name'          => __( 'Leads', 'icegram-rainmaker' )
		    );

		    $args = array(
		        'labels'             => $labels,
		        'public'             => false,
		        'publicly_queryable' => false,
		        'show_ui'            => true,
		        'show_in_menu'       => 'edit.php?post_type=rainmaker_form',
		        'query_var'          => true,
		        'rewrite'            => array( 'slug' => 'rainmaker_lead' ),
		        'capability_type'    => 'post',
		        'capabilities'       => array('create_posts' => false),
		        'map_meta_cap'       => true, 
		        'has_archive'        => false,
		        'hierarchical'       => false,
		        'menu_position'      => null,
		        'supports'           => array( '' )
		    );

		    register_post_type( 'rainmaker_lead', $args );
		}

		/* import Icegram forms and save them as rainmaker post type */
		function import_ig_forms(){

			if(get_option('ig_forms_imported')) return;

	        $active_plugins = get_option( 'active_plugins', array() );
			if(in_array('icegram/icegram.php', $active_plugins)){
				$args = array(
					'post_type'	=> 'ig_message',
					'post_status' => 'publish',
					'numberposts' => -1
				);
				$posts = get_posts($args);

	            if( !empty($posts) && is_array($posts) ) {
	            	$rm_data = array();
	            	$ig_rm_map = array();

	            	foreach ($posts as $post) {

						$ig_msg = get_post_meta( $post->ID, 'icegram_message_data', true );
						if(!empty($ig_msg['form_html_original'])){
							if(preg_match('/rainmaker_form/i', $ig_msg['form_html_original'])){
	                			//get the ID from the Rainmaker shortcode
	                			$sc_part = explode('"', $ig_msg['form_html_original']);
	                			if(!empty($sc_part[1]) && is_numeric($sc_part[1])){
	                				if(!in_array($ig_msg['form_html_original'], $rm_data)){
			                			$rm_data[$sc_part[1]] = $ig_msg['form_html_original'];
	                				}
		                			$ig_rm_map[$post->ID] = $sc_part[1];
	                			}
	                		}else{
	                			if(!in_array($ig_msg['form_html_original'], $rm_data)){
									$post_title = !empty($post->post_title) ? $post->post_title : 'Form';
				                	$rm_args = array(	
										'post_name' => '',
					              		'post_title' => 'IG-' . $post_title .'-'. $post->ID,
					              		'post_type' => 'rainmaker_form',
					              		'post_status' => 'publish' 
					              	);

					              	$form_id = wp_insert_post($rm_args);
			                		if(!empty($form_id)){
										$meta_values = array(
										    'type' => 'custom',
										    'form_code' => $ig_msg['form_html_original'],
										    'form_style' => 'rm-form-style0',
										    'rm_list_provider' => 'rainmaker',
										    'success_message' => '',
										);
										update_post_meta( $form_id, 'rm_form_settings', $meta_values );
			                			$ig_rm_map[$post->ID] = $form_id;
					                	$rm_data[$form_id] = $ig_msg['form_html_original'];
									}
	                			}else{
	                				$ig_rm_map[$post->ID] = array_search($ig_msg['form_html_original'], $rm_data);
	                			}
	                		}
						}
	                } // post loop

	                // Add Rainmaker form ids to Icegram messages
	                if(!empty($ig_rm_map)){
	                	foreach ($ig_rm_map as $msg_id => $rm_id) {
							$rm_form = get_post($rm_id);
							if($rm_form && $rm_form->post_status == 'publish'){
		                		$ig_msg = get_post_meta( $msg_id, 'icegram_message_data', true );
		                		$ig_msg['rainmaker_form_code'] = $rm_id;
								update_post_meta($msg_id, 'icegram_message_data', $ig_msg );
							}
                		}
	                }
	                ?>
	                	<div id="message" class="updated notice notice-success"><p><?php echo count($rm_data) .__(' Forms are imported from Icegram messages to Rainmaker','icegram-rainmaker')?></p></div>
	                <?php

	            }
           		update_option('ig_forms_imported', true); 
			}	

		}

		function import_sample_data(){
			if(get_option('rainmaker_sample_form_imported'))
				return;
			$args = array('post_name'     => '',
                          'post_title'    => 'My First Form',
                          'post_type'     => 'rainmaker_form',
                          'post_status'   => 'draft');

			$new_form_id = wp_insert_post( $args );
			if ( !empty( $new_form_id) ) {
				$meta_values = array(
				    'type' => 'subscription',
				    'fileds' => array(
				             array(
				                    'show' => 'yes',
				                    'label' => 'Name',
				                    'input_type' => 'text',
				                    'field_type' => 'name',
				                ),

				            array(
				                    'show' => 'yes',
				                    'label' => 'Email',
				                    'input_type' => 'text',
				                    'field_type' => 'email',
				                ),

				            array(
				                    'show' => 'yes',
				                    'label' => 'Submit',
				                    'input_type' => 'submit',
				                    'field_type' => 'button',
				                )

				        ),

				    'form_style' => 'rm-form-style2',
				    'rm_list_provider' => 'rainmaker',
				    'success_message' => '',
				);
				update_post_meta( $new_form_id, 'rm_form_settings', $meta_values );

			}
           	update_option('rainmaker_sample_form_imported', true); 
		}

		function remove_rm_form_action($actions, $post){
			if ($post->post_type != 'rainmaker_lead') return $actions;
			$actions = array();
			return $actions;

		}
		//remove_lead_bulk_action
		function remove_lead_bulk_action($actions){
			unset($actions['edit']);
			return $actions;
		}
		// Add lead columns to lead dashboard
		function edit_form_columns( $existing_columns ) {
			$date = $existing_columns['date'];
			unset( $existing_columns['date'] );
			$existing_columns['shortcode'] 	= __( 'Shortcode', 'icegram-rainmaker' );
			$existing_columns['date'] 		= $date;

			return $existing_columns ;
		}

		// Add lead columns data to lead dashboard
		function custom_form_columns( $column ) {
			global $post ;

			if( ( is_object( $post ) && $post->post_type != 'rainmaker_form' ) )
				return;
			switch ($column) {
				case 'shortcode':
					echo '<code>[rainmaker_form id="' . $post->ID . '"]</code>';
					break;
			}

		}

		// Add lead columns to lead dashboard
		function edit_lead_columns( $existing_columns ) {
			$date = $existing_columns['date'];
			unset( $existing_columns['date'] );
			unset( $existing_columns['title'] );
			
			$existing_columns['lead_email'] = __( 'Email', 'icegram-rainmaker' );
			$existing_columns['lead_name']  = __( 'Name', 'icegram-rainmaker' );
			$existing_columns['lead_date'] 	= __( 'Submission Date', 'icegram-rainmaker' );
			// $existing_columns['date'] 		= $date;
			return $existing_columns ;
		}

		// Add lead columns data to lead dashboard
		function custom_lead_columns( $column ) {
			global $post ;
			if( ( is_object( $post ) && $post->post_type != 'rainmaker_lead' ) )
				return;

			switch ($column) {
				case 'lead_email':
					$email = get_post_meta( $post->ID, 'email', true );
					echo esc_attr($email);
					break;

				case 'lead_name':
					$name = get_post_meta( $post->ID, 'name', true );
					$name = (!empty($name)) ? $name : '-';
					echo $name;
					break;

				case 'lead_date':
					echo date_format(date_create($post->post_date), 'Y/m/d');
					break;
			}

		}

		//sort custom column
		function sort_lead_columns( $columns ){
			$columns['lead_email'] = 'lead_email';
			$columns['lead_name'] = 'lead_name';
			$columns['lead_date'] = 'lead_date';
			return $columns;
		}

		//execute shortcode
		function execute_shortcode($atts = array()){
			$html = '';
			if(get_post_status($atts['id']) !== 'publish'){
				return $html;
			}

			$rm_form_settings = get_post_meta($atts['id'], 'rm_form_settings', true);
			if(!empty($rm_form_settings['rm_list_provider'])){
				$include_path = 'mailers/'.$rm_form_settings['rm_list_provider'].'.php';
				if(!file_exists($include_path)){
					if(file_exists($this->plugin_path.'/../plus/'.$include_path)){
						$include_path = $this->plugin_path.'/../plus/'.$include_path;
					}elseif(file_exists($this->plugin_path.'/../pro/'.$include_path)){
						$include_path = $this->plugin_path.'/../pro/'.$include_path;
					}
				}
				if(file_exists($include_path)){
					require_once($include_path);
				}
			}
			$form_html = '';
	        $response_text = $rm_form_settings['success_message'];
			
			if($rm_form_settings['type'] == 'custom'){
				$form_html = do_shortcode($rm_form_settings['form_code']);
			}else{
				$form_type_data = array();
				if(!empty($rm_form_settings['contact_fields']) && $rm_form_settings['type'] == 'contact'){
					$form_type_data = $rm_form_settings['contact_fields'];
				}else if(!empty($rm_form_settings['fileds']) && $rm_form_settings['type'] == 'subscription'){
					$form_type_data = $rm_form_settings['fileds'];
				}
					
		        foreach ($form_type_data as  $field) {
		        	if(empty($field['show']) ){
		        		continue;
		        	}

		    		$attr = 'required placeholder="'. trim($field['label']) .'"';
		    		$form_html .= '<div class="rm_form_el_set rm_form_el_' . $field['field_type'] .'">';
		    		$label = '<label class="rm_form_label" >'. $field['label'] .'</label>';

		    		if($field['field_type'] == 'button' ){
		    			$label = '';
		    			$attr = ' value="'. $field['label'] .'"';
		    		}
		    		$form_html .= $label;
		    		if($field['input_type'] == "textarea" && $field['field_type'] == "message")
		    			$form_html .= '<textarea rows="3" autocomplete="off" cols="65" class="rm_form_field" type="'. $field['input_type'] .'" name="'. $field['field_type'] . '"  ' . $attr . '></textarea></div>';	
		    		else
		    			$form_html .= '<input class="rm_form_field" type="'. $field['input_type'] .'" name="'. $field['field_type'] . '"  ' . $attr . '/></div>';	
		        }
				$form_html = !empty($form_html) ? '<form action="'. esc_url(add_query_arg( array()) .'#rainmaker_form_'. $atts['id']) .'">'. $form_html .'</form>' : '';
			}

			if(empty($response_text)){
		        $response_text = __('Thank you!', 'icegram-rainmaker');
			}

			if(!empty($form_html)){

				//Add Style, if form is for Remote site
				wp_enqueue_style( 'rainmaker_form_style', $this->plugin_url . '../assets/css/form.css', array(), $this->version  );

				if(!empty($_SERVER['HTTP_ORIGIN']) && site_url() !== $_SERVER['HTTP_ORIGIN']){
					$html .= '<style id="rm_style">';
					$content = file_get_contents(dirname( __FILE__ ). '/../assets/css/form.css');
					$html .= (!empty($content)) ? $content : '';
					$html .= '</style>' ;
				}
				
				$html .= '<div id="rainmaker_form_'. $atts['id'] .'" class="rm_form_container rainmaker_form '. $rm_form_settings['form_style'] .'" data-type="rm_'. $rm_form_settings['type'] .'" data-form-id="'. $atts['id'] .'">';
				$html .= $form_html;
				$html .= '<img style="display:none" class="rm-loader" src="'. plugins_url( "../assets/images/spinner-2x.gif", __FILE__ ) .'" /></div><div id="rm_form_message_'. $atts['id'] .'" class="dummyclass rm_form_message" style="display:none">'. $response_text .'</div>';

				//Add script, if form is for Remote site
				if(!empty($_SERVER['HTTP_ORIGIN']) && site_url() !== $_SERVER['HTTP_ORIGIN']){
					$html .= '<script id="rm_script">' ;
					$html .= 'var rm_pre_data = {"ajax_url":"'. admin_url( 'admin-ajax.php' ) . '"';
					$html .= ', "rm_nonce_field":"'. wp_create_nonce( "rm_form_submission" ) .'"';
					$html .= '};' ;
					$content = file_get_contents(dirname( __FILE__ ). '/../assets/js/main.js');
					$html .= (!empty($content)) ? $content : '';
					$html .='</script>' ;
				}

				$form_html = '';
			}

			return $html;
		}

        
		//form settings
		function form_design_content(){
			global $post;
			if( ( is_object( $post ) && $post->post_type != 'rainmaker_form' ) )
				return;

			$form_data = get_post_meta($post->ID, 'rm_form_settings', true);
			if(!empty($form_data)){
				echo '<div class="rm-form-shortcode">' . __('Put this shortcode', 'icegram-rainmaker'). ' <code>[rainmaker_form id="'. $post->ID . '"]</code>'.__(' wherever you want to show this form',  'icegram-rainmaker').'</div>';
			}else{
				$form_data = array();
				$form_data['fileds'] = array(array(), array(), array());
			}
			//TODO : create fileds rows in loop.
		?>
		<div id="rm-form-tabs">
			<ul class="rm-tabs-nav">
				<li><a href="#rm-tabs-1"><?php _e( 'Form', 'icegram-rainmaker' );?></a></li>
				<li><a href="#rm-tabs-2"><?php _e( 'Design', 'icegram-rainmaker' );?></a></li>
				<li><a href="#rm-tabs-3"><?php _e( 'Form Actions', 'icegram-rainmaker' );?></a></li>
			</ul>
			<div id="rm-tabs-1" class="rm-tab">
				<label class="rm_show_label form_selection"><input type="radio" class="form_type" name="form_data[type]" id="form_subscription" value="subscription" <?php echo (isset( $form_data['type'])) ? checked( $form_data['type'], 'subscription', false ) : 'checked="checked"'; ?> />
				<?php _e( 'Subscription Form', 'icegram-rainmaker' );?></label>

				<ul class="rm-form-field-settings subscription_settings" <?php echo ( !empty( $form_data['type']) && $form_data['type'] == 'subscription' ) ? ''  : 'style="display:none"' ; ?> >
					<!-- <input type="hidden" name="form_data[type]" value="<?php //echo ( !empty( $form_data['type']) && isset( $form_data['type']) ? $form_data['type']  : 'subscription' ); ?>"> -->
					<li class="rm-field-row rm-row-header">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><?php _e( 'Show?', 'icegram-rainmaker' );?></label>
							<label><?php _e( 'Field', 'icegram-rainmaker' );?></label>
							<label><?php _e( 'Label', 'icegram-rainmaker' );?></label>
						</div>
					</li>
					<!-- Name Field -->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" name="form_data[fileds][name][show]" value="yes" <?php ( !empty( $form_data['fileds']['name']['show']) ) ? checked( $form_data['fileds']['name']['show'], 'yes' ) : ''; ?> /></label>
							<label><?php _e( 'Name', 'icegram-rainmaker' );?></label>
							<input type="text" name="form_data[fileds][name][label]" value="<?php echo ( !empty( $form_data['fileds']['name']['label']) ? esc_attr($form_data['fileds']['name']['label'])  : __('Name', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[fileds][name][input_type]" value="text">
						<input type="hidden" name="form_data[fileds][name][field_type]" value="name">
					</li>
					<!-- Email Field -->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" checked disabled/></label>
							<label>
							<?php _e( 'Email', 'icegram-rainmaker' );?>
							</label>
							<input type="text" name="form_data[fileds][email][label]" value="<?php echo ( !empty( $form_data['fileds']['email']['label']) ? esc_attr($form_data['fileds']['email']['label'])  : __('Email', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[fileds][email][show]" value="yes" /> 
						<input type="hidden" name="form_data[fileds][email][input_type]" value="email">
						<input type="hidden" name="form_data[fileds][email][field_type]" value="email">
					</li>
					<!-- Button Field -->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" checked disabled/></label>
							<label><?php _e( 'Button', 'icegram-rainmaker' );?></label>
							<input type="hidden" name="form_data[fileds][button][show]" value="yes" /> 
							<input type="text" name="form_data[fileds][button][label]" value="<?php echo ( !empty( $form_data['fileds']['button']['label']) ? $form_data['fileds']['button']['label']  : __('Submit', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[fileds][button][input_type]" value="submit">
						<input type="hidden" name="form_data[fileds][button][field_type]" value="button">
					</li>
				</ul>
				<br>
				

				<!-- Contact Form -->
				<label class="rm_show_label form_selection"> <input type="radio" class="form_type" name="form_data[type]" id="form_contact" value="contact" <?php echo (isset($form_data['type'] ) ) ? checked( $form_data['type'], 'contact', false) : '' ; ?></label>
				<?php _e( 'Contact Form', 'icegram-rainmaker' );?></label>

				<ul class="rm-form-field-settings contact_settings" <?php echo ( !empty( $form_data['type']) && $form_data['type'] == 'contact' ) ? ''  : 'style="display:none"' ; ?> >
					
					<li class="rm-field-row rm-row-header">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><?php _e( 'Show?', 'icegram-rainmaker' );?></label>
							<label><?php _e( 'Field', 'icegram-rainmaker' );?></label>
							<label><?php _e( 'Label', 'icegram-rainmaker' );?></label>
						</div>
					</li>

					<!-- Name Field -->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" name="form_data[contact_fields][name][show]" value="yes" <?php ( !empty( $form_data['contact_fields']['name']['show']) ) ? checked( $form_data['contact_fields']['name']['show'], 'yes' ) : ''; ?> /></label>
							<label><?php _e( 'Name', 'icegram-rainmaker' );?></label>
							<input type="text" name="form_data[contact_fields][name][label]" value="<?php echo ( !empty( $form_data['contact_fields']['name']['label']) ? esc_attr($form_data['contact_fields']['name']['label'])  : __('Name', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[contact_fields][name][input_type]" value="text">
						<input type="hidden" name="form_data[contact_fields][name][field_type]" value="name">
					</li>

					<!-- Email Field -->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" checked disabled/></label>
							<label>
							<?php _e( 'Email', 'icegram-rainmaker' );?>
							</label>
							<input type="text" name="form_data[contact_fields][email][label]" value="<?php echo ( !empty( $form_data['contact_fields']['email']['label']) ? esc_attr($form_data['contact_fields']['email']['label'])  : __('Email', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[contact_fields][email][show]" value="yes" /> 
						<input type="hidden" name="form_data[contact_fields][email][input_type]" value="email">
						<input type="hidden" name="form_data[contact_fields][email][field_type]" value="email">
					</li>
					
					<!-- Subject Field -->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" name="form_data[contact_fields][subject][show]" value="yes" <?php ( !empty( $form_data['contact_fields']['subject']['show']) ) ? checked( $form_data['contact_fields']['subject']['show'], 'yes' ) : ''; ?> /></label>
							<label><?php _e( 'Subject', 'icegram-rainmaker' );?></label>
							<input type="text" name="form_data[contact_fields][subject][label]" value="<?php echo ( !empty( $form_data['contact_fields']['subject']['label']) ? esc_attr($form_data['contact_fields']['subject']['label'])  : __('Subject', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[contact_fields][subject][input_type]" value="text">
						<input type="hidden" name="form_data[contact_fields][subject][field_type]" value="subject">
					</li>
					<!-- Message Field -->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" name="form_data[contact_fields][msg][show]" value="yes" <?php ( !empty( $form_data['contact_fields']['msg']['show']) ) ? checked( $form_data['contact_fields']['msg']['show'], 'yes' ) : ''; ?> /></label>
							<label><?php _e( 'Message', 'icegram-rainmaker' );?></label>
							<input type="text" name="form_data[contact_fields][msg][label]" value="<?php echo ( !empty( $form_data['contact_fields']['msg']['label']) ? esc_attr($form_data['contact_fields']['msg']['label'])  : __('Message', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[contact_fields][msg][input_type]" value="textarea">
						<input type="hidden" name="form_data[contact_fields][msg][field_type]" value="message">
					</li>
					<!--Button-->
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm_show_label"><input type="checkbox" checked disabled/></label>
							<label><?php _e( 'Button', 'icegram-rainmaker' );?></label>
							<input type="hidden" name="form_data[contact_fields][button][show]" value="yes" /> 
							<input type="text" name="form_data[contact_fields][button][label]" value="<?php echo ( !empty( $form_data['contact_fields']['button']['label']) ? $form_data['contact_fields']['button']['label']  : __('Submit', 'icegram-rainmaker') ); ?>">
						</div>
						<input type="hidden" name="form_data[contact_fields][button][input_type]" value="submit">
						<input type="hidden" name="form_data[contact_fields][button][field_type]" value="button">
					</li>
				</ul>
				<br>
				<!-- Contact Form End-->

				<!-- custom form-->
				<label class="rm_show_label form_selection"><input type="radio" class="form_type" name="form_data[type]" id="form_custom" value="custom" <?php echo ( isset( $form_data['type'] ) ) ? checked( $form_data['type'], 'custom', false ) : ''; ?> />
				<?php _e( 'Custom Form', 'icegram-rainmaker' );?></label>
				<ul class="rm-form-field-settings custom_settings" <?php echo ( !empty( $form_data['type']) && $form_data['type'] == 'custom' ) ? ''  : 'style="display:none"' ; ?>>
					<li class="rm-field-row rm-row-header">
				        <textarea rows="10" autocomplete="off" cols="65" name="form_data[form_code]" placeholder="<?php _e( 'Paste your custom form html here', 'icegram-rainmaker' );?>" ><?php if( isset($form_data['form_code'] ) ) echo esc_attr($form_data['form_code']); ?></textarea>
				    </li>
				</ul>
				
			</div>
			<div id="rm-tabs-2" class="rm-tab">
				<ul class="rm-form-field-settings">
					<li class="rm-field-row"><div><label><?php _e( 'Select Form style', 'icegram-rainmaker' );?></label></div></li>
					<li class="rm-field-row">
						<input id="rm_style_selector" name="form_data[form_style]" type="hidden" value="<?php echo (!empty($form_data['form_style'])) ? $form_data['form_style'] : ''?>"/>
						<div class="rm_grid rm_clear_fix">
							<div class="rm_grid_item" data-style="rm-form-style0" >
								<label><?php _e( 'Classic', 'icegram-rainmaker') ?></label>
								<div class="rm_item_inner rm_style_classic"></div>
							</div>
							<div class="rm_grid_item" data-style="rm-form-style1" >
								<label><?php _e( 'Iconic', 'icegram-rainmaker') ?></label>
								<div class="rm_item_inner rm_style_iconic"></div>
							</div>
							<div class="rm_grid_item" data-style="rm-form-style2" >
								<label><?php _e( 'Material', 'icegram-rainmaker') ?></label>
								<div class="rm_item_inner rm_style_material"></div>
							</div>
							<div class="rm_grid_item" data-style="rm-form-style" >
								<label><?php _e( 'None', 'icegram-rainmaker') ?></label>
								<div class="rm_item_inner rm_style_none"><span><?php _e( 'Inherit wordpress theme style', 'icegram-rainmaker') ?></span></div>
							</div>
						</div>
					</li>
				</ul>
			</div>
			<div id="rm-tabs-3" class="rm-tab">
				<ul class="rm-form-field-settings rm-form-action-settings">
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<span class="rm_save_db"><?php _e('Leads will be always collected in database','icegram-rainmaker')?></span>
						</div>
					</li>
					<li class="rm-field-row">
						<div class="rm-form-field-set">
						<label class="rm-bold-text"><input class="rm_checkbox" type="checkbox"  disabled readonly  checked="checked" /> <?php _e( 'Show a Thank You message', 'icegram-rainmaker' );?></label>
			            <textarea rows="3" autocomplete="off" cols="65" name="form_data[success_message]" placeholder="<?php _e( 'Thank You!', 'icegram-rainmaker' );?>" ><?php if( isset($form_data['success_message'] ) ) echo esc_attr($form_data['success_message']); ?></textarea>
						</div>
					</li>
					<li class="rm-field-row">
						<div class="rm-form-field-set">
						<label class="rm-bold-text"><input class="rm_checkbox" type="checkbox" name="form_data[rm_enable_redirection]" value="yes" <?php ( !empty( $form_data['rm_enable_redirection']) ) ? checked( $form_data['rm_enable_redirection'], 'yes' ) : ''; ?>/> <?php _e( 'Redirect to URL', 'icegram-rainmaker' );?></label>
			            <input type="text" name="form_data[redirection_url]" placeholder="<?php _e( 'Enter link URL here', 'icegram-rainmaker' );?>" value="<?php if( isset($form_data['redirection_url'] ) ) echo esc_attr($form_data['redirection_url']); ?> "/>
						</div>
					</li>
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm-bold-text"><input id="rm_enable_list" class="rm_checkbox" type="checkbox" name="form_data[rm_enable_list]" value="yes" <?php ( !empty( $form_data['rm_enable_list']) ) ? checked( $form_data['rm_enable_list'], 'yes' ) : ''; ?>/>
							<?php _e( 'Subscribe to a mailing list', 'icegram-rainmaker' );?></label>
							<?php 
								$mailers = array();
								$mailers = apply_filters('rainmaker_mailers' , $mailers);
								$form_data['rm_list_provider'] = (!empty($form_data['rm_list_provider'])) ? $form_data['rm_list_provider'] : '';
							?>
							<select id="rm-list-provider" class="rm-select" name="form_data[rm_list_provider]">
							  	<!-- <option value="rainmaker" <?php echo selected( $form_data['rm_list_provider'], 'rainmaker' );?>>Rainmaker</option> -->
								<?php
								if( !empty( $mailers ) ) {
									foreach( $mailers as $slug => $setting ){
										echo '<option value="' . $slug . '" '.selected( $form_data['rm_list_provider'], $slug, false ).'>' . $setting['name'] . '</option>';
									}
								}
								?>
							</select>
							<div id="rm-list-details" class="rm-form-field-subset">
								<div class="rm-loader loader" style="display:none;"><img src="<?php echo plugins_url( '../assets/images/spinner-2x.gif', __FILE__ );  ?>" /> </div>
								<div id="rm-list-details-container" class="rm-list-details-container"></div>
							</div>
						</div>
					</li>
				   <li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm-bold-text"><input id='rm_mail_send' class="rm_checkbox" type="checkbox" name="form_data[rm_mail_send]" value="yes" <?php (!empty($form_data['rm_mail_send'])) ? checked($form_data['rm_mail_send'], 'yes') : ''; ?>/><?php _e('Email form data to','icegram-rainmaker');?></label>
							<input type="email" name="form_data[rm_mail_to]" value="<?php echo (!empty($form_data['rm_mail_to'])) ? $form_data['rm_mail_to'] : '' ?>" placeholder = '<?php _e('Enter Email Id', 'icegram-rainmaker'); ?>'/>
						</div>
					</li>
					<li class="rm-field-row">
						<div class="rm-form-field-set">
							<label class="rm-bold-text"><input class="rm_checkbox" type="checkbox" name="form_data[rm_enable_webhook]" value="yes" <?php ( !empty( $form_data['rm_enable_webhook']) ) ? checked( $form_data['rm_enable_webhook'], 'yes' ) : ''; ?>/>
							<?php _e( 'Trigger a Webhook', 'icegram-rainmaker' );?></label>
							<input type="text" name="form_data[webhook_url]" value="<?php echo (!empty($form_data['webhook_url'])) ? $form_data['webhook_url'] : '' ?>" placeholder="Enter webhook url"/>
					</li>
				</ul>
			</div>

		</div>
		<?php
		}

		// Save all list of messages and targeting rules
		function save_form_settings( $post_id, $post ) {
			if (empty( $post_id ) || empty( $post ) || empty( $_POST )) return;
			if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) return;
			if (is_int( wp_is_post_revision( $post ) )) return;
			if (is_int( wp_is_post_autosave( $post ) )) return;
			if (! current_user_can( 'edit_post', $post_id )) return;
			if ($post->post_type != 'rainmaker_form') return;
			if (empty( $_POST['form_data'])) return;
			update_post_meta( $post_id, 'rm_form_settings', $_POST['form_data'] );
		}	

		//fetch all available form list
		public static function get_rm_form_id_name_map(){
			$rm_form_id_name_map = array();
			$post_types = array('rainmaker_form');
			$args = array(
						'post_type'			=> $post_types,
						'post_status'	 	=> 'publish',
						'posts_per_page' 	=> -1,
						'fields'			=> 'ids',
					);

			$rm_from_ids = get_posts( $args );
			if(!empty($rm_from_ids)){
				foreach ($rm_from_ids as $id) {
					$rm_form_id_name_map[$id] = get_the_title($id);
				}
			}
			return $rm_form_id_name_map;
		}

		//execute shortcode in text widget
	    function rm_widget_text_filter($content){
	        if ( ! preg_match( '/\[[\r\n\t ]*rainmaker_form?[\r\n\t ].*?\]/', $content ) )
	        return $content;

	        $content = do_shortcode( $content );

	        return $content;
	    }

	    //filter lead data: remove unwanted
	    function rm_filter_lead_data($lead){
	    	if(!empty($lead)){
	    		foreach($lead as $key => $value){
	    			if(substr($key, 0, 3) == 'ig_' || substr($key, 0, 3) == 'rm_'){
	    				unset($lead[$key]);
	    			}
	    		}
	    		
	    	}
	    	return $lead;
	    }
	    public static function trigger_webhook($params, $form_settings){
	    	if(!empty($form_settings['rm_enable_webhook']) && !empty($form_settings['webhook_url'])){
			    $url = $form_settings['webhook_url'];

				$options = array(
					'timeout' => 15,
					'method' => 'POST',
					'body' => http_build_query( $params )
					);
				$response = wp_remote_post( $url, $options );
				$response_code = wp_remote_retrieve_response_code( $response );
				if ( is_wp_error( $response ) ) {
					error_log(wp_strip_all_tags( $response->get_error_message() ));
					// wp_die();
				}else if ( $response_code == 200 ) {
					//TODO :: log in response
					//error_log($response['body']);
					// wp_die();
				} else {
					//wp_die($response['body'], 'Error in Submission', array('response' => $response_code) );
					error_log('Error in Submission');
				}
			}
		} // trigger_webhook

		public function rm_send_mail($lead, $rm_form_settings){
			if(!empty($rm_form_settings['rm_mail_send']) && $rm_form_settings['rm_mail_send'] == 'yes' && !empty($rm_form_settings['rm_mail_to']) ){
				$style = '<style>
							th.rm-heading{
								text-align: left;
							}
							table, th, td {
							    border: 1px solid #ccc;
							    border-collapse: collapse;
							}
							th, td{
								padding:0.3em;
							}

						 </style>';
				$heading = __('*** Form submmision ***', 'icegram-rainmaker');
				$html = $style.$heading.'<table><thead><th class="rm-heading">'.__('Name', 'icegram-rainmaker').'</th> <th class="rm-heading">'.__('Value', 'icegram-rainmaker').'</th></thead><tbody>';
				foreach ($lead as $key => $value) {
					if(!empty($value)){
					    $html .= "<tr>";
						$html .= '<td>'.$key.'</td>';
						$html .= '<td>'.$value.'</td>'; 
						$html .= "</tr>";
					}
				}
				$html .= '</tbody></table>';
				$headers = 'Content-Type: text/html; charset=UTF-8';
				$form_title = get_the_title($rm_form_settings['form_id']);
				$subject = __('Lead added from: ', 'icegram-rainmaker').$form_title;
				wp_mail($rm_form_settings['rm_mail_to'], $subject, $html, $headers);
			}
			return true;
		}

	}
}

