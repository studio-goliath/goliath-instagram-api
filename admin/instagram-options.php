<?php
/**
 * Options for the plugin
 *
 * Oauth authentification
 *
 */

class Goliath_Instagram_Api_Option{


    private $admin_notice;

    /**
     * On est sur le hook admin_init
     */
    function __construct() {

        $gins_admin_page = add_options_page('Instagram API', 'Instagram API', 'manage_options', 'gins-options', array( $this, 'gins_option_page_content') );

        add_action('load-' . $gins_admin_page, array( $this, 'gins_get_the_instagram_access_token' ) );

        add_action( 'admin_init', array( $this, 'gins_api_option_page_settings' ));

        add_action( 'admin_notices', array( $this, 'gins_admin_notices' ));


        /**
         * Quand on change le client ID ou le client secret on kill le access token
         */
        add_action( 'update_option_gins_client_info_id', array( $this, 'kill_access_token' ));
        add_action( 'update_option_gins_client_info_secret', array( $this, 'kill_access_token' ));

        $this->admin_notice = false;

    }

    public function gins_api_option_page_settings() {

        // Add the section gins option page
        add_settings_section( 'gins_client_info', __('Client Info', 'goliath-instagram-api') , array( $this, 'gins_client_info_callback' ), 'gins-options-group' );

        add_settings_section( 'gins_authentication', __('Authentication', 'goliath-instagram-api') , array( $this, 'gins_authentication_callback' ), 'gins-options-group' );

        // Add the field
        add_settings_field( 'gins_client_info_id_filed', __('Client ID', 'goliath-instagram-api'), array( $this, 'gins_input_callback' ), 'gins-options-group', 'gins_client_info', array( 'label_for' => 'gins_api_url', 'option' => 'gins_client_info_id') );
        add_settings_field( 'gins_client_info_secret_filed', __('Client Secret', 'goliath-instagram-api'), array( $this, 'gins_input_callback' ), 'gins-options-group', 'gins_client_info', array( 'label_for' => 'gins_api_url', 'option' => 'gins_client_info_secret') );

        // Register our setting so that $_POST handling is done for us
        register_setting( 'gins-options-group', 'gins_client_info_id' );
        register_setting( 'gins-options-group', 'gins_client_info_secret' );

    }


    public function gins_client_info_callback() {

        echo '<p>';
        printf( __('View the <a href="%1s">Manage Client Instagram Page</a>', 'goliath-instagram-api'), 'https://instagram.com/developer/clients/manage/');
        echo '</p>';

        echo '<p>' . sprintf( __('Add %s in "REDIRECT URI" in your client info', 'goliath-instagram-api'), '<strong>' . admin_url( 'options-general.php') . '</strong>' ) .'</p>';

    }



    public function gins_option_page_content(){


        ?>
        <div class="wrap">
            <h1><?php _e('Instagram API Option Page', 'goliath-instagram-api') ?></h1>

            <form method="post" action="options.php">

                <?php

                settings_fields( 'gins-options-group' );

                do_settings_sections( 'gins-options-group' );

                submit_button();
                ?>
            </form>
        </div>

        <?php
    }


    public function gins_get_the_instagram_access_token(){

        $instagram_code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : false;

        if( $instagram_code ){

            $get_acces_token_args['body'] = array(
                    'client_id'     => get_option('gins_client_info_id'),
                    'client_secret' => get_option('gins_client_info_secret'),
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => $admin_url = admin_url( 'options-general.php?page=gins-options' ),
                    'code'          => $instagram_code,
                );


            $get_acces_token_post = wp_remote_post( 'https://api.instagram.com/oauth/access_token',  $get_acces_token_args );

            if( ! is_wp_error( $get_acces_token_post ) ){

                $acces_token_response = json_decode( wp_remote_retrieve_body( $get_acces_token_post ) );

                if( property_exists( $acces_token_response, 'access_token' ) ){

                    update_option( 'gins_client_access_token', $acces_token_response->access_token );

                    update_option( 'gins_client_user_name', $acces_token_response->user->username );

                    $this->admin_notice = ( object ) array(
                        'code'         => '200',
                        'error_message' => __('You are authenticated', 'goliath-instagram-api'),
                        );

                } else {

                    $this->admin_notice = $acces_token_response;

                }

            }

        }

    }

    function gins_admin_notices(){

        if( $this->admin_notice ){

            if( property_exists( $this->admin_notice, 'code' ) ){

                $class = $this->admin_notice->code == '200' ? 'updated' :'error';
                $message = $this->admin_notice->error_message;

                echo "<div class='$class'> <p>$message</p></div>";
            }

        }
    }


    function gins_authentication_callback(){


        $gins_client_access_token = get_option( 'gins_client_access_token' );
        $gins_client_user_name = get_option( 'gins_client_user_name' );

        if( $gins_client_access_token ){

            echo '<p>' . sprintf( __('You are authenticated %s', 'goliath-instagram-api'), "<strong>{$gins_client_user_name}</strong>" ) . '</p>';

        } else {

            $gins_client_info_id = get_option( 'gins_client_info_id' );

            if( $gins_client_info_id ){

                $admin_url = admin_url( 'options-general.php?page=gins-options' );

                echo '<p>You need to authenticate your website : </p>';
                echo "<p><a href='https://api.instagram.com/oauth/authorize/?client_id={$gins_client_info_id}&redirect_uri={$admin_url}&response_type=code' class='button'>Authenticate</a></p>";

            } else {
                echo '<p>' . __('Enter your ID and secret first', 'goliath-instagram-api') . '</p>';
            }
        }



    }


    function gins_input_callback( $callback ){

        $option = $callback['option'];
        $type = isset( $callback['type'] ) ? $callback['type'] : 'text';
        $content = get_option( $option );

        ?>
        <input type="<?php echo $type ?>" class="widefat" name="<?php echo $option; ?>" value="<?php echo $content; ?>" />
        <?php
    }

    /**
     * On supprime les access token
     */
    function kill_access_token(){
        delete_option( 'gins_client_access_token' );
    }

}





function gins_init_option_page() {

    $goliath_option = new Goliath_Instagram_Api_Option();

}
add_action( 'admin_menu', 'gins_init_option_page' );




