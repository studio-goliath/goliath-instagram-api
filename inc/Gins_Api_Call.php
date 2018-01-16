<?php
/**
 * Created by PhpStorm.
 * User: goliath
 * Date: 23/11/2017
 * Time: 11:06
 */

Class Gins_Api_Call
{

    private $instagram_api_url;

    private $client_access_token;

    private $client_info_secret;

    public function __construct()
    {

        $this->instagram_api_url = 'https://api.instagram.com/v1';

        $this->client_access_token = get_option( 'gins_client_access_token' );

        $this->client_info_secret = get_option( 'gins_client_info_secret' );

    }

    /**
     * @param $endpoint
     * @param array $params
     *
     * @return array|mixed|object|\WP_Error
     */
    public function call( $endpoint, $params = array() )
    {

        $params = wp_parse_args(
            $params,
            array(
                'access_token'  =>  $this->client_access_token,
                'count'         => 10
            )
        );

        $transient_key = $this->get_transient_key( $endpoint, $params );

        $response = get_transient( $transient_key );

        if( ! $response ){

            $params['sig'] = $this->generate_sig($endpoint, $params);

            $url = add_query_arg( $params, $this->instagram_api_url . $endpoint);

            $response = wp_remote_get( $url );

            if( ! is_wp_error( $response ) ){


                if( wp_remote_retrieve_response_code( $response ) == 200 ){

                    $response = json_decode( wp_remote_retrieve_body( $response ) );

                    set_transient( $transient_key, $response, HOUR_IN_SECONDS );

                } else {

                    $response = new WP_Error( '400', wp_remote_retrieve_response_message( $response ) );

                }

            }
        }

        return $response;

    }


    /**
     * Generate call signature
     *
     * @param string $endpoint
     * @param array $params
     *
     * @return false|string
     */
    private function generate_sig($endpoint, $params)
    {

        $sig = $endpoint;
        ksort($params);

        foreach ($params as $key => $val) {
            $sig .= "|$key=$val";
        }

        return hash_hmac('sha256', $sig, $this->client_info_secret, false);
    }


    /**
     * Get transient key
     *
     * @param string $endpoint
     * @param array $params
     *
     * @return string
     */
    private function get_transient_key( $endpoint, $params) {

        $transient_key_md5 = md5( $endpoint . ',' . implode( ',', $params ) );
        return "_gins_{$transient_key_md5}";

    }
}