<?php
if(!class_exists('Rm_Mailer_Email_Subscribers')){
	class Rm_Mailer_Email_Subscribers{
		private $slug;
		private $setting;
		function __construct(){
	        add_action( 'rainmaker_post_lead', array( &$this, 'email_subscribers_add_subscriber' ), 10, 2); 
			add_filter( 'rainmaker_mailers', array( $this,'init' ) );
			$this->setting  = array(
				'name' => 'Email Subscribers',
			);
			$this->slug = 'email_subscribers';
		}

		//Init function
		function init($mailers){
			$mailers[$this->slug] = $this->setting;
			return $mailers;
		}

		/*
		* Add subscriber to Email Subscriber
		* @Since 1.0
		*/
		function email_subscribers_add_subscriber($lead, $form_settings){
				
			if(empty($form_settings['rm_enable_list']) || empty($form_settings['rm_list_provider']) || $form_settings['rm_list_provider'] !== 'email_subscribers'){
				return;
        	}
			$es_settings = es_cls_settings::es_setting_select();
			$contact['es_email_name'] = (!empty($lead['name'])) ? $lead['name'] : '';
			$contact['es_email_status'] = (!empty($es_settings['es_c_optinoption']) && $es_settings['es_c_optinoption'] == 'Double Opt In' )?'Unconfirmed' : 'Single Opt In';
			$contact['es_email_group'] = 'Public';
			$contact['es_email_mail'] = $lead['email'];
			es_cls_dbquery::es_view_subscriber_ins($contact, $action = "insert");
			return true;
		}
	}
	new Rm_Mailer_Email_Subscribers;

}