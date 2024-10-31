<?php 

namespace PBEML\Utility;

if (!defined( 'ABSPATH')) exit;

/**
 * Stringify data
 */
function  stringify( $var, $delimiter = ',' ) {
	if ( ! is_array( $var ) ) {
		return (string) $var;
	}

	return implode( $delimiter, $var );
}

/**
 * Parser header data
 */
function email_header_parser( $headers ){

    $cc           = array();
    $bcc          = array();
    $reply_to     = array();
    $from_name    = '';
    $from_email   = '';
    $content_type = '';


    if ( ! is_array( $headers ) ) {
        // Explode the headers out, so this function can take
        // both string headers and an array of headers.
        $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
    } else {
        $tempheaders = $headers;
    }
    $headers = array();

    foreach ( (array) $tempheaders as $header ) {
        if ( strpos( $header, ':' ) === false ) {
            if ( false !== stripos( $header, 'boundary=' ) ) {
                $parts    = preg_split( '/boundary=/i', trim( $header ) );
                $boundary = trim( str_replace( array( "'", '"' ), '', $parts[1] ) );
            }
            continue;
        }
        // Explode them out.
        list( $name, $content ) = explode( ':', trim( $header ), 2 );

        // Cleanup crew.
        $name    = trim( $name );
        $content = trim( $content );

        switch ( strtolower( $name ) ) {
            // Mainly for legacy -- process a "From:" header if it's there.
            case 'from':
                $bracket_pos = strpos( $content, '<' );
                if ( false !== $bracket_pos ) {
                    // Text before the bracketed email is the "From" name.
                    if ( $bracket_pos > 0 ) {
                        $from_name = substr( $content, 0, $bracket_pos - 1 );
                        $from_name = str_replace( '"', '', $from_name );
                        $from_name = trim( $from_name );
                    }

                    $from_email = substr( $content, $bracket_pos + 1 );
                    $from_email = str_replace( '>', '', $from_email );
                    $from_email = trim( $from_email );

                    // Avoid setting an empty $from_email.
                } elseif ( '' !== trim( $content ) ) {
                    $from_email = trim( $content );
                }
                break;
            case 'content-type':
                if ( strpos( $content, ';' ) !== false ) {
                    list( $type, $charset_content ) = explode( ';', $content );
                    $content_type                   = trim( $type );
                    if ( false !== stripos( $charset_content, 'charset=' ) ) {
                        $charset = trim( str_replace( array( 'charset=', '"' ), '', $charset_content ) );
                    } elseif ( false !== stripos( $charset_content, 'boundary=' ) ) {
                        $boundary = trim( str_replace( array( 'BOUNDARY=', 'boundary=', '"' ), '', $charset_content ) );
                        $charset  = '';
                    }

                    // Avoid setting an empty $content_type.
                } elseif ( '' !== trim( $content ) ) {
                    $content_type = trim( $content );
                }
                break;
            case 'cc':
                $cc = array_merge( (array) $cc, explode( ',', $content ) );
                break;
            case 'bcc':
                $bcc = array_merge( (array) $bcc, explode( ',', $content ) );
                break;
            case 'reply-to':
                $reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
                break;
            default:
                $headers[ trim( $name ) ] = trim( $content );
                break;
        }
    }

    $headers['from']         = $from_name.'<'.$from_email.'>';
    $headers['content-type'] = $content_type;
    $headers['cc']           = stringify( $cc );
    $headers['bcc']          = stringify( $bcc );
    $headers['reply-to']     = stringify( $reply_to );

    return $headers;
}


/**
 * Recursive sanitation for an array
 * 
 * @param $array
 *
 * @return mixed
 */
function recursive_sanitize_text_field($array) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = recursive_sanitize_text_field($value);
        }
        else {
            $value = sanitize_text_field( $value );
        }
    }

    return $array;
}

function sanitize_richtext_field( $value ) {
    $value = wp_check_invalid_utf8( $value );
	$value = convert_invalid_entities( $value );
	return force_balance_tags( $value );
}

function esc_html( $html ){
    $html        = \esc_html( $html );
    $html        = str_replace( array('\r\n', '\n'), '<br/>', $html );
    $html        = nl2br( $html );
    $html        = stripslashes( $html );
    return $html;
}

function allowed_html(){
    $allowed_tags = wp_kses_allowed_html('post');
    $allowed_tags['link'] = array(
        'rel'   => true,
        'href'  => true,
        'type'  => true,
        'media' => true,
    );
    return $allowed_tags;
}



/**
 * Create table
 */
function create_table(){

    global $wpdb;
    $db         = apply_filters( 'pbeml_database', $wpdb );
    $table_name = $db->prefix.'postbox_email_log';

    if( $db->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {

        $charset_collate = $db->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email text NULL,
            subject text NULL,
            headers text NULL,
            attachments text NULL,
            message longtext NULL,
            ip_address varchar(15) NULL,
            error_message varchar(255) NULL,
            read_status varchar(30) NULL,
            sent_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    //Backward compatibility
    if ( ! $db->get_var( "SHOW COLUMNS FROM `$table_name` LIKE 'read_status';" ) ) {
        $db->query( "ALTER TABLE $table_name ADD `read_status` varchar(30) NULL;" );
    }
}
