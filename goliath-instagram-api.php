<?php
/**
 * Plugin Name: Goliath Instagram API
 * Description: Call instagram API to display your last pictures
 * Author: Studio Goliath
 * Author URI: http://studio-goliath.fr/
 * Version: 1
 */


// Option page
require_once( plugin_dir_path( __FILE__ ) . '/admin/instagram-options.php' );


// Instagram Stuff
function gins_generate_sig($endpoint, $params, $secret) {

    $sig = $endpoint;
    ksort($params);

    foreach ($params as $key => $val) {
        $sig .= "|$key=$val";
    }

    return hash_hmac('sha256', $sig, $secret, false);
}

function gins_get_instagram_last_picture(){

    $instagram_last_picture = get_transient( 'instagram_last_picture' );

    if( ! $instagram_last_picture ){

        $endpoint = '/users/self/media/recent';
        $params = array(
          'access_token' => get_option( 'gins_client_access_token' ),
          'count' => 5,
        );
        $secret = get_option( 'gins_client_info_secret' );

        $sig = gins_generate_sig($endpoint, $params, $secret);
        $params['sig'] = $sig;

        $url = add_query_arg( $params, 'https://api.instagram.com/v1' . $endpoint);

        $instagram_last_picture_get = wp_remote_get( $url );

        if( ! is_wp_error( $instagram_last_picture_get ) ){

            $instagram_last_picture_body = json_decode( wp_remote_retrieve_body( $instagram_last_picture_get ) );

            if( $instagram_last_picture_body->meta->code != 400 ){

                $instagram_last_picture = $instagram_last_picture_body;

                set_transient( 'instagram_last_picture', $instagram_last_picture, HOUR_IN_SECONDS );
            }


        }
    }

    return $instagram_last_picture;
}