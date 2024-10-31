<?php
/**
 * Plugin Name: PostBox Email Logs 
 * Plugin URI: https://wpdebuglog.com/
 * Description: Store WP mail logs. It will capture WP mail SMTP traffic. 
 * Author: wpdebuglog 
 * Text Domain: postbox-email-logs
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.0.4
*/ 


require plugin_dir_path(__FILE__).'/inc/email-logging.php';
require plugin_dir_path(__FILE__).'/inc/utility.php';
require plugin_dir_path(__FILE__).'/inc/logs-table.php';
require plugin_dir_path(__FILE__).'/inc/menu-page.php';
require plugin_dir_path(__FILE__).'/inc/popup.php';

use PBEML\Core;
use PBEML\Utility;
use PBEML\Menu;
use PBEML\Popup;

Core\Email_Logging::get_instance();
Menu\PBEML_Admin_Menu::get_instance( __FILE__ );

/**
 * Trigger while activating time 
 */
function pbeml_on_activate( $network_wide ){
    global $wpdb;
    if ( is_multisite() && $network_wide ) {
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            Utility\create_table();
            restore_current_blog();
        }
    } else {
        Utility\create_table();
    }
}

register_activation_hook( __FILE__, 'pbeml_on_activate' );
add_action( 'wp_ajax_pbeml_get_log_data', 'pbeml_get_log_data' );
function pbeml_get_log_data() {
    global $wpdb;
    $db         = apply_filters( 'pbeml_database', $wpdb );
    $table_name = $db->prefix.'postbox_email_log';  
    $id         = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $data       = $db->get_row("SELECT * from $table_name WHERE id = '$id' LIMIT 1 ", ARRAY_A);

    $popup_html = Popup\PBEML_Popup::get_popup_data( $data );
    echo apply_filters( 'pbeml_popup_html',  $popup_html);
	die();
}

function pbeml_plugin_page_view_link( $links ) { 
    $settings_link = '<a href="admin.php?page=postbox-email-logs.php">'.
                        __('View Logs', 'postbox-email-logs').
                    '</a>'; 
    array_unshift($links, $settings_link); 
    return $links; 
  }
  
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'pbeml_plugin_page_view_link' );

add_action( 'init', 'pbeml_postbox_load_text_domain' );

function pbeml_postbox_load_text_domain() {
	load_plugin_textdomain( 'postbox-email-logs', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' ); 
}


function pbeml_plugin_activate() {
  
  update_option( 'pbeml_postbox_install_date', date('Y-m-d G:i:s') );

}
register_activation_hook( __FILE__, 'pbeml_plugin_activate' );

/* Display a notice that can be dismissed */
add_action('admin_notices', 'pbeml_admin_notice');

function pbeml_admin_notice() {

	$install_date = get_option( 'pbeml_postbox_install_date', '');
	$install_date = empty( $install_date ) ? date('Y-m-d G:i:s') : '';
	$install_date = date_create( $install_date );
	$date_now	  = date_create( date('Y-m-d G:i:s') );
	$date_diff    = date_diff( $install_date, $date_now );

	if ( $date_diff->format("%a") < 7 ) return false;
	

	$rated  = get_option( 'pbeml_postbox_ignore_notice', false );

    if ( empty( $rated ) && isset($_GET['page']) && $_GET['page'] == 'postbox-email-logs.php' ) {

        echo '<div class="updated"><p>'; 

        printf(__('Awesome, you\'ve been using <a href="admin.php?page=postbox-email-logs.php">PostBox Email Log</a> for more than 1 week. May we ask you to give it a 5-star rating on WordPress? | <a href="%2$s" target="_blank">Ok, you deserved it</a> | <a href="%1$s">I alredy did</a> | <a href="%1$s">No, not good enough</a>'), 
                'admin.php?page=postbox-email-logs.php&pbeml_postbox_ignore_notice=0',
                'https://wordpress.org/plugins/postbox-email-logs/'
            );
        echo "</p></div>";
    }
}

add_action('admin_init', 'pbeml_postbox_ignore_notice');

function pbeml_postbox_ignore_notice() {

    if ( isset($_GET['pbeml_postbox_ignore_notice'])  ) 
        update_option( 'pbeml_postbox_ignore_notice', 'true');
    
}

function pbeml_upgrade_function( $upgrader_object, $options ) {

    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        $plugin_slug = plugin_basename(__FILE__);  

        if (in_array($plugin_slug, $options['plugins'])) {
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'postbox_email_log';

            $column_exists = $wpdb->get_results(
                $wpdb->prepare(
                    "SHOW COLUMNS FROM $table_name LIKE read_status"
                )
            );

            if (empty($column_exists)) {
                $wpdb->query(
                    "ALTER TABLE $table_name ADD read_status varchar(30) NULL"
                );
            }
        }
    } 

}

add_action( 'upgrader_process_complete', 'pbeml_upgrade_function', 10, 2);