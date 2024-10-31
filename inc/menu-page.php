<?php 

namespace PBEML\Menu;

use PBEML\Table;

if (!defined( 'ABSPATH')) exit;

class PBEML_Admin_Menu{

    private static $instance;
    private $path;

    private function __construct( $path ){

        $this->path = $path;

        add_action( 'admin_menu', array($this, 'admin_list_table_page' ) );
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts' ) );

    }

    public static function get_instance( $path ){
        
        if( empty(self::$instance) )
            self::$instance = new PBEML_Admin_Menu( $path );
        
        return self::$instance;
    }


    public function enqueue_scripts( $hook ){
        if( $hook != 'toplevel_page_postbox-email-logs') return;
        wp_enqueue_style('jquery-ui-css', plugin_dir_url($this->path).'/css/jquery-ui/jquery-ui.min.css');
        wp_enqueue_style('pbeml-style', plugin_dir_url($this->path).'/css/style.css');
        wp_enqueue_script('jquery-ui');
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script('pbeml-script', plugin_dir_url($this->path).'/js/script.js', array(), '1.0.3');
        wp_localize_script('pbeml-script', 'pbeml',
            array( 
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'confirm' => __('Are you sure to delete all?', 'postbox-email-logs' )
            )
        );

    }


    /**
     * Menu item will allow us to load the page to display the table
     */
    public function admin_list_table_page(){

		$cap = ( current_user_can( 'pbeml_access') ) ? 'pbeml_access' : 'manage_options';

        add_menu_page( 
            __( 'Email Logs', 'pbemal-email-logs' ), 
            __( 'Email Logs', 'pbemal-email-logs' ), $cap, 'postbox-email-logs.php',
            array($this, 'list_table_page'), 'dashicons-email-alt' 
        );

    }

    public function list_table_page(){
        $ListTable = new Table\PBEML_List_Table();
        $ListTable->prepare_items();
        ?>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h2><?php _e('PostBox Email Logs', 'postbox-email-logs') ?></h2>
                <form method="get">
                    <?php $ListTable->search_box( __('Search','postbox-email-logs') , 'search'); ?>
                    <?php $ListTable->display(); ?>
                </form>
            </div>
            <div class="pbeml">
                <div class="popup">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="close">×</span>
                            <h2>Email Log</h2>
                        </div>
                        <div class="modal-body">
                            <div class="loader"></div>
                            <div class="data"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="button button-defult footer-colse">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }


}