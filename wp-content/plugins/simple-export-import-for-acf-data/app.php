<?php
/*
Plugin Name: Simple Export Import for ACF Data
Description: Exporting and importing ACF field value. It can export and import options, page, custom post type data of ACF field from one site to another site.
Plugin URI: https://www.opcodespace.com
Author: Opcodespace <mehedee@opcodespace.com>
Version: 1.3.13
Text Domain: simple-export-import-for-acf-data
*/

if ( ! defined( 'ABSPATH' ) ) {exit;}

define("SEIP_VIEW_PATH", wp_normalize_path(plugin_dir_path(__FILE__) . "view/"));
define("SEIP_ASSETSURL", plugins_url("assets/", __FILE__));
define('SEIP_PLUGIN_VERSION', '1.3.13');
define('PAID_TEXT', '<small class="paid_text">(This is for paid user)</small>');

include_once 'functions.php';
include_once 'src/SeipFront.php';
include_once 'src/SeipExport.php';
include_once 'src/SeipEnqueue.php';
include_once 'src/SeipImport.php';
include_once 'src/SeipOpcodespace.php';
include_once 'src/SeipTransientAdminNotices.php';
include_once 'src/SeipImportEditorMediaFile.php';

add_action('plugins_loaded', array('SeipFront', 'init'));
add_action('plugins_loaded', array('SeipExport', 'init'));
add_action('plugins_loaded', array('SeipImport', 'init'));
add_action('plugins_loaded', array('SeipEnqueue', 'init'));

add_action( 'admin_init', function() {
    $transient_name = md5( 'seip_notices' . get_current_user_id() );
    $notices = new SeipTransientAdminNotices( $transient_name );

    if(isset($_GET['page']) && $_GET['page'] === 'seip-simple-export-import'){
        $notices->add( 'msg2', '<strong>Simple Export Import for ACF Data: </strong>Please keep your site backup before importing data.', 'warning' );
    }
} );

