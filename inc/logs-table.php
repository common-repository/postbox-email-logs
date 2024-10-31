<?php

namespace PBEML\Table;

use PBEML\Utility;

/**
 * Logs table
 */

if (!defined( 'ABSPATH')) exit;


// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class PBEML_List_Table extends \WP_List_Table
{
    private $column_titles;

    public function __construct() {

        parent::__construct(
            array(
                'singular' => 'email_log',
                'plural'   => 'email_logs',
                'ajax'     => false
            )
        );

    }

    /**
     * Prepare the items for the table to process
     *
     * @return Void
    */
    public function prepare_items()
    {

        $search = empty( $_REQUEST['s'] ) ? false : sanitize_text_field( $_REQUEST['s'] );

        global $wpdb;

        $this->process_bulk_action();

        $db          = apply_filters('pbeml_database', $wpdb);
        $table_name  = $db->prefix.'postbox_email_log';
        $columns     = $this->get_columns();
        $hidden      = $this->get_hidden_columns();
        $sortable    = $this->get_sortable_columns();
        $data        = $this->table_data();
        $perPage     = 100;
        $currentPage = $this->get_pagenum();
        $query       = '';

        if( !empty($_GET['from-date'])){
            $from_date = sanitize_text_field( $_GET['from-date'] );
            $to_date   = empty($_GET['to-date']) ? date('d/m/Y') : sanitize_text_field( $_GET['to-date'] );
            $from_date = \DateTime::createFromFormat('d/m/Y', $from_date );
            $from_date = $from_date->format("Y-m-d");
            $to_date   = \DateTime::createFromFormat('d/m/Y', $to_date );
            $to_date   = $to_date->format("Y-m-d");
            $to_date   = esc_sql( $to_date );
            $from_date = esc_sql( $from_date );
            $query    .= "DATE_FORMAT(sent_date ,'%Y-%m-%d') BETWEEN '$from_date' AND  '$to_date' ";
        }

        if ( ! empty($search) ) {
            $query   = empty( $query ) ? '' : ' AND '.$query;
            $totalItems  = $db->get_var(
                "SELECT COUNT(id) FROM $table_name WHERE 
                (to_email LIKE "."'%".$wpdb->esc_like($search)."%'"." OR 
                subject LIKE "."'%".$wpdb->esc_like($search)."%'"." OR 
                headers LIKE "."'%".$wpdb->esc_like($search)."%'"." OR 
                message LIKE "."'%".$wpdb->esc_like($search)."%'".") $query"
            );
        }else{
            $query       = empty( $query ) ? '' : 'WHERE '.$query;
            $totalItems  = $db->get_var("SELECT COUNT(id) FROM $table_name $query");
        }

        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

        $this->_column_headers = array($columns, $hidden ,$sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
                        'cb'          => '', 
                        'sent_date'   => __('Sent at', 'postbox-email-logs'),
                        'sent_status' => __('Sent Status', 'postbox-email-logs'),
                        'to_email'    => __('To', 'postbox-email-logs'),
                        'reply_to'    => __('Reply-to', 'postbox-email-logs'),
                        'subject'     => __('Subject', 'postbox-email-logs'),
                    );


        $this->column_titles = array_keys( $columns );
        $this->column_titles = apply_filters('pbeml_column_titles', $this->column_titles);
        return $columns;
    }

    /**
     * Define check box for bulk action (each row)
     * @param  $item
     * @return checkbox
     */
    public function column_cb($item){  
        return sprintf(
             '<input type="checkbox" name="%1$s[]" value="%2$s" />',
             $this->_args['singular'],
             $item['id']
        );
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return  array('id');
    }
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
       return array('sent_date' => array('email-date', true));
    }
    /**
     * Define bulk action
     * @return Array
     */
    public function get_bulk_actions() {

        return array(
            'read'   => __( 'Read', 'contact-form-cfdb7' ),
            'unread' => __( 'Unread', 'contact-form-cfdb7' ),
            'delete'     => __( 'Delete', 'postbox-email-logs' ),
            'delete_all' => __( 'Delete All', 'postbox-email-logs' )
        );

    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        global $wpdb;

        $data         = array();
        $db           = apply_filters('pbeml_database', $wpdb);
        $table_name   = $db->prefix.'postbox_email_log';
        $search       = empty( $_REQUEST['s'] ) ? false :  sanitize_text_field( $_REQUEST['s'] );
        $page         = $this->get_pagenum();
        $page         = $page - 1;
        $start        = $page * 100;
        $orderby      = isset($_GET['orderby']) ? 'sent_date' : 'id';
        $order        = isset($_GET['order']) && $_GET['order'] == 'asc' ? 'ASC' : 'DESC';
        $query        = '';

        if( !empty($_GET['from-date'])){
            $from_date = sanitize_text_field( $_GET['from-date'] );
            $to_date   = empty($_GET['to-date']) ? date('d/m/Y') : sanitize_text_field($_GET['to-date']);
            $from_date = \DateTime::createFromFormat('d/m/Y', $from_date );
            $from_date = $from_date->format("Y-m-d");
            $to_date   = \DateTime::createFromFormat('d/m/Y', $to_date );
            $to_date   = $to_date->format("Y-m-d");
            $from_date = esc_sql( $from_date ); 
            $to_date   = esc_sql( $to_date ); 
            $query    .= "DATE_FORMAT(sent_date ,'%Y-%m-%d') BETWEEN '$from_date' AND  '$to_date' ";
        }

        if ( ! empty($search) ) {
           $query   = empty( $query ) ? '' : ' AND '.$query;
           $results = $db->get_results( 
                        "SELECT * FROM $table_name WHERE 
                        (to_email LIKE "."'%".$wpdb->esc_like($search)."%'"." OR 
                        subject LIKE "."'%".$wpdb->esc_like($search)."%'"." OR
                        headers LIKE "."'%".$wpdb->esc_like($search)."%'"." OR 
                        message LIKE "."'%".$wpdb->esc_like($search)."%')
                        $query 
                        ORDER BY $orderby $order
                        LIMIT $start,100", 
                        ARRAY_A 
                    );
        }else{
            $query   = empty( $query ) ? '' : 'WHERE '.$query;
            $results = $db->get_results( "SELECT * FROM $table_name 
                        $query 
                        ORDER BY $orderby $order
                        LIMIT $start,100", ARRAY_A 
                    );
        }

        $row = array();

        foreach ( $results as $result ) {
            $row['id'] = $result['id'];

            foreach ( $this->column_titles as $col_title) {

                if( $col_title == 'sent_date' ){
                    $date_time = \DateTime::createFromFormat('Y-m-d H:i:s', $result[ $col_title ]);
                    $result[ $col_title ] = sprintf( '%1$s %2$s %3$s', 
                                                $date_time->format( 'F d, Y'),
                                                __('at','postbox-email-logs'),
                                                $date_time->format('g:i a')
                                            );
                    $result[ $col_title ] .= '<span style="color:silver"> (id:'.$row['id'].')</span>';
                }

                $row[ $col_title ] = isset( $result[ $col_title ] ) ? $result[ $col_title ] : '';
                $row[ $col_title ] = ( strlen($row[ $col_title ]) > 100 ) ? substr($row[ $col_title ], 0, 100).'...': $row[ $col_title ];
                $row[ $col_title ] = stripslashes( $row[ $col_title ] );

                if($col_title == 'sent_status'){
                    $success_icon      = '<a href="javascript:void(0)" aria-label="'. __("Successfully sent", "postbox-email-logs").'" class="hint--top hint--success"><span class="dashicons dashicons-yes-alt pbeml-success"></span></a>';
                    $error_icon        = '<a href="javascript:void(0)" aria-label="'.$result['error_message'].'" class="hint--top hint--error"><span class="dashicons dashicons-dismiss pbeml-error"></span></a>';
                    $icon              = empty($result['error_message']) ? $success_icon : $error_icon; 
                    $row[ $col_title ] = $icon;
                    continue;
                }

                if( $col_title == 'reply_to' ){ 
                    $headers         = unserialize( $result['headers'] );
                    $row['reply_to'] = isset($headers['reply-to']) ? $headers['reply-to']:'99';
                }  

                $row[ $col_title ] = sprintf('<a href="" data-log="'.$row['id'].'" class="pbeml-row">%s</a>', $row[ $col_title ]);
                
                if( empty( $result ['read_status'] ) ||  $result ['read_status'] == 'unread' ){
                    $row[ $col_title ] = '<b>' . $row[ $col_title ] . '</b>';
                } 
                
            }
            $row    = apply_filters('pbeml_row_data', $row);
            $data[] = $row;
        }

        return $data;
    }

    public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id  = $input_id . '-search-input';
        $from_date = isset( $_REQUEST['from-date'] ) ? sanitize_text_field( $_REQUEST['from-date'] ) : '';
        $to_date   = isset( $_REQUEST['to-date'] ) ? sanitize_text_field( $_REQUEST['to-date'] ): '';
        
        echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $text ); ?>:</label>
            <input type="search" placeholder="<?php _e('From Date', 'postbox-email-logs') ?>"  name="from-date" class="pbeml-date" value="<?php echo esc_attr( $from_date ) ?>" autocomplete="off" />
            <input type="search" placeholder="<?php _e('To Date', 'postbox-email-logs') ?>"  name="to-date" class="pbeml-date" value="<?php echo esc_attr( $to_date ) ?>"  autocomplete="off"/>
            <input type="search" placeholder="<?php _e('Search by Term', 'postbox-email-logs') ?>" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
            <?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
        </p>
		<?php
	}

    /**
     * Define bulk action
     *
     */
    public function process_bulk_action(){

        global $wpdb;

        $db           = apply_filters('pbeml_database', $wpdb);
        $table_name   = $db->prefix.'postbox_email_log';
        $action       = $this->current_action();
        $pagenum      = $this->get_pagenum();

        if ( !empty( $action ) ) {

            $nonce        = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
            $nonce_action = 'bulk-' . $this->_args['plural'];

            if ( !wp_verify_nonce( $nonce, $nonce_action ) ){

                wp_die( 'Not valid..!!' );
            }
        }

        $ids = isset( $_REQUEST['email_log'] ) ? Utility\recursive_sanitize_text_field( $_REQUEST['email_log'] ) : array();

        if( 'delete' === $action ) {
            foreach ((array)$ids as $id):
                $id = (int) $id;
                $db->delete(
                    $table_name ,
                    array( 'id' => $id ),
                    array( '%d' )
                );
            endforeach;
            echo "<script type='text/javascript'>window.top.location='admin.php?page=postbox-email-logs.php&paged=$pagenum';</script>";  
        }else if( 'delete_all' === $action ){
            $db->query("TRUNCATE TABLE $table_name");
            echo "<script type='text/javascript'>window.top.location='admin.php?page=postbox-email-logs.php';</script>";  
            exit;
        }else if( 'read' === $action ){
            foreach ((array)$ids as $id):
                $id = (int) $id;
                $db->query( "UPDATE $table_name SET read_status = 'read' WHERE id = $id LIMIT 1");
            endforeach;
            echo "<script type='text/javascript'>window.top.location='admin.php?page=postbox-email-logs.php&paged=$pagenum';</script>";  
            exit;
        }else if( 'unread' === $action ){
            foreach ((array)$ids as $id):
                $id = (int) $id;
                $db->query( "UPDATE $table_name SET read_status = 'unread' WHERE id = $id LIMIT 1");
            endforeach;
            echo "<script type='text/javascript'>window.top.location='admin.php?page=postbox-email-logs.php&paged=$pagenum';</script>";  
            exit;
        }
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    { 
        return $item[ $column_name ];

    }
    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    public function sort_data( $a, $b )
    {
        // Set defaults
        $orderby = 'sent_date';
        $order = 'ASC';
        // If orderby is set, use this as the sort column
        if(!empty($_GET['orderby']))
        {
            $orderby = $_GET['orderby'] === 'form_id' ? 'form_id' : 'sent_date';
        }
        // If order is set use this as the order
        if(!empty($_GET['order']))
        {
            $order = $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
        }
        $result = strcmp( $a[$orderby], $b[$orderby] );
        if($order === 'asc')
        {
            return $result;
        }
        return -$result;
    }

    /**
     * Display the bulk actions dropdown.
     *
     * @since 3.1.0
     * @access protected
     *
     * @param string $which The location of the bulk actions: 'top' or 'bottom'.
     *                      This is designated as optional for backward compatibility.
     */
    protected function bulk_actions( $which = '' ) {
        if ( is_null( $this->_actions ) ) {
            $this->_actions = $this->get_bulk_actions();
            /**
             * Filters the list table Bulk Actions drop-down.
             *
             * The dynamic portion of the hook name, `$this->screen->id`, refers
             * to the ID of the current screen, usually a string.
             *
             * This filter can currently only be used to remove bulk actions.
             *
             * @since 3.5.0
             *
             * @param array $actions An array of the available bulk actions.
             */
            $this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
            $two = '';
        } else {
            $two = '2';
        }

        if ( empty( $this->_actions ) )
            return;

        echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action', 'postbox-email-logs' ) . '</label>';
        echo '<select name="action' . esc_attr( $two ) . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
        echo '<option value="-1">' . __( 'Bulk Actions', 'postbox-email-logs' ) . "</option>\n";

        foreach ( $this->_actions as $name => $title ) {
            $class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

            echo "\t" . '<option value="' . esc_attr( $name ) . '"' .  esc_attr( $class ) . '>' . esc_html( $title ) . "</option>\n";
        }

        echo "</select>\n";

        submit_button( __( 'Apply', 'postbox-email-logs' ), 'action', '', false, array( 'id' => "doaction$two" ) );
        do_action('pbeml_export_button');
    }

    
}