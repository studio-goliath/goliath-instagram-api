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

require_once( plugin_dir_path( __FILE__ ) . '/inc/Gins_Api_Call.php' );


function gins_instagram_self_recent_media( $params = array()){

    $inst_api_call = new Gins_Api_Call();

    return $inst_api_call->call( '/users/self/media/recent', $params );

}