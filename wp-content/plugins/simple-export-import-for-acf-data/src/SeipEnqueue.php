<?php
if ( ! defined( 'ABSPATH' ) ) {exit;}

class SeipEnqueue
{

    public static function init()
    {
        $self = new self;
        add_action('admin_enqueue_scripts', array($self, 'enqueue_admin_script' ));
    }

    public function enqueue_admin_script()
    {
        $version = SEIP_PLUGIN_VERSION;
        if($_GET['page'] === 'seip-simple-export-import'){
            wp_enqueue_style('chosen-style', SEIP_ASSETSURL . "add-on/chosen-js/chosen.min.css");
            wp_enqueue_style('virtual-select', SEIP_ASSETSURL . "add-on/virtual-select/virtual-select.min.css", false, $version);
            wp_enqueue_style('grid', SEIP_ASSETSURL . "add-on/grid/grid.css", false, '1.0.0');
            wp_enqueue_style('tooltip', SEIP_ASSETSURL . "add-on/virtual-select/tooltip.min.css", false, $version);

            wp_enqueue_style('main-style', SEIP_ASSETSURL . "add-on/style.css", array(), 'time()');


            wp_enqueue_script('tooltip-js', SEIP_ASSETSURL . "add-on/virtual-select/tooltip.min.js", array(), $version, true);
            wp_enqueue_script('virtual-select-js', SEIP_ASSETSURL . "add-on/virtual-select/virtual-select.min.js", array(), $version, true);
            wp_enqueue_script('chosen-js', SEIP_ASSETSURL . "add-on/chosen-js/chosen.jquery.min.js", array(), $version, true);
            wp_enqueue_script('admin-script', SEIP_ASSETSURL . "add-on/admin-script.js", array(), $version, true);


            wp_localize_script(
                'admin-script',
                'frontend_form_object',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php')
                )
            );
        }

    }
}
