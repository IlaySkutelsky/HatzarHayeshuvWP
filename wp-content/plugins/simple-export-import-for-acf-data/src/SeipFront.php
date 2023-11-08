<?php

if ( ! defined( 'ABSPATH' ) ) {exit;}

if(!class_exists('SeipFront')) {
    class SeipFront
    {
        public static function init()
        {
            $self = new self;
            add_action( 'admin_menu', [$self, 'export_import'] );
            add_action('wp_ajax_seip_get_all_posts', [$self, 'seip_get_all_posts']);
            add_action('wp_ajax_seip_get_all_taxonomies', [$self, 'seip_get_all_taxonomies']);
            add_action('wp_ajax_seip_get_all_terms', [$self, 'seip_get_all_terms']);
            add_action('wp_ajax_seip_save_license_key', [$self, 'seip_save_license_key']);

        }

        public function export_import()
        {
            add_submenu_page(
                'options-general.php',
                'Simple Export Import',
                'Simple Export Import',
                'manage_options',
                'seip-simple-export-import',
                [$this, 'display_export_import'] );
        }

        public function display_export_import()
        {
            ob_start();
            include_once SEIP_VIEW_PATH . 'export_import.php';
            $html = ob_get_contents();
            ob_end_clean();
            echo $html;
        }

        public function seip_get_all_taxonomies()
        {
             if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'seip_export_import' ) ) {
                 wp_send_json_error(['message' => 'You are not allowed to submit data.']);
             }

             $post_type = sanitize_text_field($_POST['post_type']);

            $taxonomies = get_object_taxonomies( $post_type, 'objects' );

            wp_send_json_success(['taxonomies' => $taxonomies]);
        }

        public function seip_get_all_terms()
        {
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'seip_export_import' ) ) {
                wp_send_json_error(['message' => 'You are not allowed to submit data.']);
            }

            $taxonomy = sanitize_text_field($_POST['taxonomy']);

            $terms = get_terms([
                'taxonomy' => $taxonomy
            ]);

            wp_send_json_success(['terms' => $terms]);
        }

        public function seip_get_all_posts()
        {
             if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'seip_export_import' ) ) {
                 wp_send_json_error(['message' => 'You are not allowed to submit data.']);
             }

            $post_type = sanitize_text_field($_POST['post_type']);
            $taxonomy = sanitize_text_field($_POST['taxonomy']);
            $terms = (array)($_POST['terms']);

            $args = [
                'post_type' => $post_type,
                'numberposts' => - 1
            ];

            if(!empty($terms) && !empty($taxonomy)){
                $args['tax_query'] = [
                    [
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $terms
                    ]
                ];
            }
            else if(!empty($taxonomy)){
                $args['tax_query'] = [
                    [
                        'taxonomy' => $taxonomy,
                        'operator' => 'EXISTS'
                    ]
                ];
            }


            $posts = get_posts($args);

            $sorted_posts = [];

            foreach($posts as $post){
                $sorted_posts[] = [
                  'ID' => (int)$post->ID,
                  'post_name' => esc_attr($post->post_name)
                ];
            }

            wp_send_json_success(['posts' => $sorted_posts]);
        }

        public function seip_save_license_key()
        {

            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'seip_save_license_key' ) ) {
                wp_send_json_error(['message' => 'You are not allowed to submit data.']);
            }

            $license_key = sanitize_text_field( $_POST['seip_license_key'] );

            update_option('seip_license_key', $license_key);

            $SeipOpcodespace = new SeipOpcodespace($license_key);
            $SeipOpcodespace->setSubscriptionStatus();

        }
    }
}