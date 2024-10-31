<?php 


namespace PBEML\Core;

if (!defined( 'ABSPATH')) exit;

use PBEML\Utility;

class Email_Logging{

    private static $instance;
    private $id;

    private function __construct(){
        add_filter('wp_mail', array($this, 'logging'));
        add_filter('wp_mail_failed', array($this, 'mail_failed'));
        
    }

    /**
     * 
     */
    public static function get_instance(){
        if (!isset(self::$instance)) {
            self::$instance = new Email_Logging();
        }
        return self::$instance;
    }

    /**
     * Loggin data 
     * @param mail data
     */
    public function logging( $org_mail ){

        $mail = wp_parse_args(
			$org_mail,
			array(
				'to'          => '',
				'subject'     => '',
				'message'     => '',
				'headers'     => '',
				'attachments' => array(),
			)
		);

        $log                = array();
        $headers            = Utility\email_header_parser( $mail['headers'] );
        $attachments        = Utility\stringify( $mail['attachments'] );
        $log['headers']     = serialize( $headers );
        $log['sent_date']   = current_time('Y-m-d H:i:s');
        $log['to_email']    = is_array( $mail['to'] ) ? implode( $mail['to'] ) :  $mail['to'];
        $log['to_email']    = sanitize_text_field( $log['to_email'] );        
        $log['ip_address']  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
        $log['subject']     = sanitize_text_field( $mail['subject'] );
        $log['message']     = Utility\sanitize_richtext_field( $mail['message'] );
        $log['attachments'] = sanitize_text_field( $attachments );
        $log['read_status'] = 'unread';

        global $wpdb;
        $db         = apply_filters( 'pbeml_database', $wpdb );
        $table_name = $db->prefix.'postbox_email_log';

        $db->insert( $table_name, $log );

        $this->id =  $db->insert_id;
        
        return $org_mail;
    }

    /**
     * Mail failed status
     */
    public function mail_failed( $error ){

        global $wpdb;

        $db         = apply_filters( 'pbeml_database', $wpdb );
        $table_name = $db->prefix.'postbox_email_log';

        if ( ! is_wp_error( $error ) ) {
			return;
		}

		$error_message = $error->get_error_message( 'wp_mail_failed' );

        $db->update($table_name,  array('error_message'=> $error_message), array('id' => $this->id));
         
    }

}

