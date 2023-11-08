<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SeipOpcodespace
{
    protected $token;
    protected $url = 'https://opcodespace.com/wp-admin/admin-post.php?action=check_seip_validation';

    public function __construct($token = null)
    {
        if($token === null){
            $this->token = sanitize_text_field(get_option('seip_license_key'));
        }
        else{
            $this->token = $token;
        }
    }

    public static function isPaid()
    {
        return get_option('seip_subscription');
    }

    public function request()
    {
        $response = wp_remote_request($this->url . '&site=' . get_site_url() . '&version=' . SEIP_PLUGIN_VERSION, [
            'method'  => 'GET',
            'headers' => ['Authorization' => $this->token],
            'sslverify' => apply_filters( 'https_local_ssl_verify', false )
        ]);

        $code = wp_remote_retrieve_response_code($response);

        if($code != 200){
            throw new Exception(wp_remote_retrieve_body($response));
        }

        // TODO: Log
        return json_decode(wp_remote_retrieve_body($response));
    }

    public function setSubscriptionStatus()
    {
        try {
            $response = $this->request();
            if ($response->success) {
                update_option('seip_subscription', true);
                wp_send_json_success( ['message' => __('Congratulation! Your account has been activated.', 'simple-export-import-for-acf-data')] );
            }

            update_option('seip_subscription', false);
            wp_send_json_error( ['message' => $response->data->message] );

        }
        catch (Exception $e){
            wp_send_json_error( ['message' => $e->getMessage()] );
        }
    }
}