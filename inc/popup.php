<?php

namespace PBEML\Popup;

use PBEML\Utility;

if (!defined( 'ABSPATH')) exit;

class PBEML_Popup{

    public static function get_popup_data( $data ){

        global $wpdb;

        $db          = apply_filters('pbeml_database', $wpdb);
        $table_name  = $db->prefix.'postbox_email_log';
        $id          = (int) $data['id'];

        $db->query( "UPDATE $table_name SET read_status = 'read' 
            WHERE id = $id LIMIT 1"
        );

        preg_match("/<body[^>]*>(.*?)<\/body>/is", $data['message'], $matches);
        $source       = $data['message'];
        $headers      = unserialize( $data['headers'] );
        $text         = isset($matches[1]) ? $matches[1] : $source;
        $text         = preg_replace_callback('/\<[\w.]+@[\w.]+\>/', function($arr){
                            return esc_html( $arr[0] );
                        }, $text);
        if(empty($headers['content-type']) || $headers['content-type'] == 'text/plain'){
            $text     = nl2br( $text );
        }
        if(isset($headers['content-type']) && $headers['content-type'] == 'text/html'){
            $text     = str_replace( array('\r\n','\n'), PHP_EOL, $text );
        }

        $text         = stripslashes( $text );
        $reply_to     = sanitize_email( $headers['reply-to'] );
        $to_email     = sanitize_email( $data['to_email'] );
        $sent_date    = $data['sent_date'];
        $subject      = stripslashes( $data['subject'] );
        $sent_date    = \DateTime::createFromFormat('Y-m-d H:i:s', $sent_date);
        $sent_date    = sprintf( '%1$s %2$s %3$s', 
                            $sent_date->format( 'F d, Y'),
                            __('at','postbox-email-logs'),
                            $sent_date->format('g:i a')
                        );
        ob_start();
        ?>
        <div class="tabel">
            <table>
                <tr>
                    <td class="td-4"><?php _e('ID', 'postbox-email-logs') ?></td>
                    <td class="td-8 send-at"><?php echo esc_html( $data['id'] ) ?></td>
                </tr>
                <tr>
                    <td class="td-4"><?php _e('Sent at', 'postbox-email-logs') ?></td>
                    <td class="td-8 send-at"><?php echo esc_html( $sent_date ) ?></td>
                </tr>
                <tr>  
                    <td class="td-4"><?php _e('To', 'postbox-email-logs') ?></td>
                    <td class="td-8 to"><?php echo esc_html($to_email) ?></td>
                </tr>
                <tr>
                    <td class="td-4"><?php _e('Reply-To', 'postbox-email-logs') ?></td>
                    <td class="td-8 reply-to"><?php echo esc_html($reply_to) ?></td>
                </tr>
                <tr>
                    <td class="td-4"><?php _e('Subject', 'postbox-email-logs') ?></td>
                    <td class="td-8 subject"><?php echo esc_html($subject) ?></td>
                </tr>
                <tr>
                    <td class="td-4"><?php _e('Status', 'postbox-email-logs') ?></td>
                    <td class="td-8 status">
                        <?php echo empty($data['error_message']) ?
                        _e('Successfully Sent', 'postbox-email-logs') :
                        esc_html($data['error_message']) ?>
                    </td>
                </tr>
            </table>
        </div>
        <div id="pbeml-tabs">
            <ul>
                <li><a href="#tab-preview"><?php _e('Message Preview','postbox-email-logs') ?></a></li>
                <li><a href="#tab-source"><?php _e('Source','postbox-email-logs') ?></a></li>
                <li><a href="#tab-headers"><?php _e('Headers','postbox-email-logs') ?></a></li>
            </ul>

            <div id="tab-preview">
                <?php echo wp_kses( $text, Utility\allowed_html( 'post' ) ); ?>
            </div>
            <div id="tab-source">
                <?php echo Utility\esc_html( $source ) ?>
            </div>
            <div id="tab-headers">
                <div class="tabel">
                    <table>
                        <?php foreach($headers as $key => $header): 
                                $key = ucfirst( $key );
                        ?>
                            <tr>
                                <td class="td-4"><?php echo esc_html($key) ?></td>
                                <td class="td-8"><?php echo esc_html($header) ?></td>
                            </tr>
                        <?php endforeach ?>
                    </table>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

}

