<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * @param boolean $success
 * @param string $message
 * @param array $data
 * @return array
 */
if(!function_exists('_return')) {
    function _return($success, $message = "", $data = [])
    {
        $data['success'] = $success;
        $data['message'] = $message;

        return $data;
    }
}

if(!function_exists('seip_notices')) {
    function seip_notices($key, $message, $type = 'error')
    {
        $transient_name = md5('seip_notices'.get_current_user_id());
        $notices        = new SeipTransientAdminNotices($transient_name);

        $notices->add($key, $message, $type);
    }
}

if(!function_exists('seip_notices_with_redirect')) {
    function seip_notices_with_redirect($key, $message, $type = 'error')
    {
        seip_notices($key, $message, $type);
        wp_redirect( $_POST['_wp_http_referer'] );
        exit();
    }
}

if(!function_exists('seip_log')) {
    function seip_log($name, $message)
    {
//        $log_file = WP_CONTENT_DIR . '/uploads/seip_log.txt';
//        $message  = date('Y-m-d H:i:s') . ' == ' . $name . ' == ' . $message . PHP_EOL;
//        file_put_contents($log_file, $message, FILE_APPEND);
    }
}