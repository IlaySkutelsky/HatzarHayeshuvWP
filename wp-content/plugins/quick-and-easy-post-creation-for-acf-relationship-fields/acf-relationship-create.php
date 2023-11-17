<?php
/*
Plugin Name: Quick and easy Post creation for ACF Relationship Fields
Description: Quick & Easy post creation on your Advanced Custom Fields (ACF) 'Relationship' & 'Post Object' Fields (free version)
Author: Bazalt
Version: 2.2
Author URI: http://bazalt.fr/
License: GPL2
Text Domain: quick-and-easy-post-creation-for-acf-relationship-fields
Domain Path: /languages/
*/

/*  Copyright 2016 Cyril Batillat (email : contact@bazalt.fr)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'ACF_Relationship_Create_Free' ) ) :

    class ACF_Relationship_Create_Free
    {

        private static $_instance;

        /**
         * Singleton pattern
         * @return ACF_Relationship_Create_Free
         */
        public static function getInstance() {
            if( self::$_instance instanceof self ) return self::$_instance;
            self::$_instance = new self();
            return self::$_instance;
        }

        /**
         * Avoid creation of an instance from outside
         */
        private function __clone() {}


        /**
         * Private constructor (part of singleton pattern)
         * Declare WordPress Hooks
         */
        private function __construct() {

            // Load the plugin's translated strings
            add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

            // Init
            add_action(
                'init',
                array($this, 'init'),
                2 // Right after ACF
            );

            // AJAX: increment plugin usage (only in admin)
            add_action( 'wp_ajax_acf_rc_increment', array( $this, 'ajax_increment_user_usage' ) );
        }

        /**
         * Load the plugin's translated strings
         *
         * @hook action plugins_loaded
         */
        public function load_text_domain() {
            load_plugin_textdomain( self::get_text_domain(), false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
        }

        /**
         * Get the plugin Text domain
         *
         * @return string
         */
        public static function get_text_domain() {
            return 'quick-and-easy-post-creation-for-acf-relationship-fields';
        }

        /**
         * Check if ACF is installed
         *
         * @return bool
         */
        public static function is_acf_installed() {
            return class_exists( 'acf' );
        }

        /**
         * Is ACF in PRO version?
         *
         * @return bool
         */
        public static function is_acf_pro_version() {
            return class_exists( 'acf_pro' );
        }

        /**
         * Admin notice if ACF version is PRO
         *
         * @hook action admin_notices
         * @see ACF_Relationship_Create_Free::register_assets
         */
        public function admin_notice_bad_ACF_version() {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <?php _e( 'You are using the PRO version of Advanced Custom Fields plugin.', self::get_text_domain() ); ?>
                    <?php _e( 'You have to upgrade `Quick and Easy Post creation for ACF Relationship Fields` plugin to PRO version too!', self::get_text_domain() ); ?>
                    <a href="https://codecanyon.net/item/quick-and-easy-post-creation-for-acf-relationship-fields-pro/17201274" target="_blank"><?php _e( 'Download the PRO version', self::get_text_domain() ); ?></a>
                </p>
            </div>
            <?php
        }

        /**
         * Init method, called right after ACF
         *
         * @hook action init
         */
        public function init() {

            if( !self::is_acf_installed() ) return;

            // Bail early with an error notice if ACF version is PRO
            if( self::is_acf_pro_version() ) {
                add_action( 'admin_notices', array( $this, 'admin_notice_bad_ACF_version' ) );
                return;
            }



            /**
             * Register scripts
             */

            // Tools
            wp_register_script(
                'acf-relationship-create',
                plugins_url('assets/js/acf-relationship-create' . ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min' ) . '.js', __FILE__),
                array('jquery'),
                '2.1'
            );

            // Relationship field script
            wp_register_script(
                'acf-relationship-create-field',
                plugins_url('assets/js/acf-relationship-create-field' . ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min' ) . '.js', __FILE__),
                array( 'acf-relationship-create', 'thickbox', 'acf-input' ),
                '2.1'
            );

            // iframe script
            wp_register_script(
                'acf-relationship-create-iframe',
                plugins_url('assets/js/acf-relationship-create-iframe' . ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min' ) . '.js', __FILE__),
                array('acf-relationship-create'),
                '2.1'
            );

            wp_register_style(
                'acf-relationship-create',
                plugins_url( 'assets/css/acf-relationship-create.css', __FILE__ ),
                array('acf-input'),
                '2.1'
            );



            /**
             * Admin enqueue scripts
             */
            add_action(
                'admin_enqueue_scripts',
                array( $this, 'admin_scripts'),
                11 // Right after ACF
            );



            /**
             * ACF Hooks
             */

            // Enqueue assets for ACF fields
            add_action('acf/input/admin_enqueue_scripts', array( $this, 'enqueue_acf_assets' ), 11); // Just after ACF scripts

            // Alter query params for AJAX calls on ACF Relationship fields
            add_filter( 'acf/fields/relationship/query', array( $this, 'acf_field_alter_ajax' ), 10, 3 );

            // Add new setting for ACF relationship fields
            add_action( 'acf/create_field_options', array( $this, 'acf_relationship_settings' ), 50);

            // Alter markup of ACF relationship fields
            add_action( 'acf/create_field', array( $this, 'acf_render_relationship_field' ), 10, 1 );
        }

        /**
         * Include scripts
         *
         * @hook action admin_enqueue_scripts
         *
         * @param $hook
         */
        public function admin_scripts( $hook ) {
            // This script will only be enqueued on saved posts. Not on post-new.php
            if( in_array( $hook, array( 'post.php' ) ) ) {
                wp_enqueue_script( 'acf-relationship-create-iframe' );
            }
        }

        /**
         * Enqueue assets for ACF fields
         *
         * @hook action acf/input/admin_enqueue_scripts
         */
        public function enqueue_acf_assets() {
            // CSS
            wp_enqueue_style( 'acf-relationship-create' );

            // JS
            wp_enqueue_script( 'acf-relationship-create-field' );

            // JS: pass Post Types labels
            $post_types_labels = array();
            $post_types = get_post_types( array(), 'objects' );
            foreach( $post_types as $post_type )
                $post_types_labels[$post_type->name] = $post_type->labels->name;

            wp_localize_script(
                'acf-relationship-create-field',
                'acf_relationship_create_field',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'current_user' => get_current_user_id(),
                    'post_types_labels' => $post_types_labels,
                    'i18n' => array(
                        'no_title' => __( '(No title)', self::get_text_domain() ),
                        'please_review' => __( "Looks like you're enjoying creating on the fly content with \"Quick and easy Post creation for ACF Relationship Fields\" plugin.\n\nWould you take 30 seconds to rate this plugin?\nOh pleeease ❤ ... think about all the time you saved using this plugin!\n(This will open in a new window)", self::get_text_domain() ),
                    ),
                    'activation_date' => (int) get_option( 'acf-rc-activation-date' ),
                    'dismiss_review_msg' => get_option( 'acf-rc-dismiss-review-msg' )
                )
            );
        }

        /**
         * AJAX: increment plugin usage (only in admin)
         *
         * @hook action wp_ajax_acf_rc_increment
         */
        public function ajax_increment_user_usage() {
            $user_id = get_current_user_id();
            if( empty( $user_id ) )
                wp_send_json_error('anonymous_user');

            // Increment user uses
            $nb_usages = get_user_meta( get_current_user_id(), 'acf-rc-uses', true );
            if( empty( $nb_usages ) ) $nb_usages = 0;
            $nb_usages = (int) $nb_usages;
            $nb_usages++;
            update_user_meta(
                get_current_user_id(),
                'acf-rc-uses',
                $nb_usages
            );

            // Ajax response
            wp_send_json_success(array(
                'uses' => $nb_usages,

                // Rate message recurrence
                'rating_message_recurrence' => apply_filters(
                    'acf-relationship-create/rating_message_recurrence',
                    15
                ),

                // Dismiss rating message?
                'review_msg_dismissed' => apply_filters(
                    'acf-relationship-create/review_msg_dismissed',
                    get_user_meta( get_current_user_id(), 'acf-rc-review-msg-dismissed', true ) == '' ? false : true,
                    get_current_user_id(),
                    $nb_usages
                )
            ));
        }

        /**
         * Alter query params for AJAX calls on ACF Relationship fields
         *
         * @hook filter acf/fields/relationship/query
         *
         * @param $args
         * @param $field
         * @param WP_Post $post
         * @return mixed
         */
        public function acf_field_alter_ajax( $args, $field, $post ) {
            if( empty( $_POST['acf_relationship_created_post_id'] ) ) return $args;

            $post_params = explode( '-', $_POST['acf_relationship_created_post_id'] );
            $created_post_id = absint( $post_params[0] );
            if( empty( $created_post_id ) ) return $args;

            // We're only looking for this particular post ID
            $args['p'] = $created_post_id;
            unset($args['s']);
            unset($args['tax_query']);

            return $args;
        }

        /**
         * Alter markup of ACF relationship fields
         *
         * @hook action acf/create_field/type=relationship
         *
         * @param $field
         */
        public function acf_render_relationship_field( $field ) {

            if( empty( $field['class'] ) || !in_array( $field['class'], array( 'relationship', 'post_object' ) ) ) return;
            if( empty( $field['acf_relationship_create'] ) ) return;

            $post_types = empty( $field['post_type'] ) ? array() : $field['post_type'];
            if( count( $post_types ) == 1 && $post_types[0] == 'all' )
                $post_types = array();

            $post_types = empty( $post_types ) ? apply_filters('acf/get_post_types', array() ) : $post_types;
            if( empty( $post_types ) ) return;
            $tooltip_links = array();
            foreach( $post_types as $post_type ) {
                if( $post_type == 'attachment' ) continue;

                $post_type_obj = get_post_type_object( $post_type );

                if( !user_can( get_current_user_id(), $post_type_obj->cap->create_posts ) )
                    continue;
                $tooltip_links[ $post_type ] = array(
                    'label' => $post_type_obj->labels->singular_name,
                    'url' => admin_url(
                        add_query_arg(
                            array(
                                'acf_rc_original_field_uniqid' => '__acf_rc_original_field_uniqid__', // token that will be replaced dynamically in JS
                                'acf_rc_from_content_type' => '__acf_rc_from_content_type__',
                                'acf_rc_from_content_ID' => '__acf_rc_from_content_ID__',
                                'TB_iframe' => 1 // Force loading as iframe
                            ),
                            'post-new.php?post_type=' . $post_type
                        )
                    )
                );
            }
            if( empty( $tooltip_links ) ) return;
            ?>
            <a href="#" class="acf-relationship-create-link">
                <span class="dashicons dashicons-plus"></span>
                <span class="screen-reader-text"><?php esc_html_e( 'Create', self::get_text_domain() ); ?></span>
            </a>

            <input type="hidden" name="acf-relationship-created_post_id" data-filter="acf_relationship_created_post_id" data-uniqid=""/>

            <script type="text-html" class="acf-rc-popup-wrapper">
                <div id="acf-rc-popup">
                    <ul>
                        <li>
                            <?php foreach( $tooltip_links as $post_type => $post_type_data ) : ?>
                                <a href="#"
                                    data-create-url="<?php echo esc_attr( $post_type_data['url'] ); ?>"
                                    title="<?php printf( esc_attr__( 'Create new %s', self::get_text_domain() ), $post_type_data['label'] ); ?>">
                                    <?php echo $post_type_data['label']; ?>
                                    <span class="status"></span>
                                </a>
                            <?php endforeach; ?>
                        </li>
                    </ul>
                    <a href="#" class="focus"></a>
                </div>
            </script>
            <?php
        }


        /**
         * Add new setting for ACF relationship fields
         *
         * @hook action acf/create_field_options
         *
         * @param $field
         */
        public function acf_relationship_settings( $field ) {
            if( empty( $field['type'] ) || !in_array( $field['type'], array( 'relationship', 'post_object' ) ) )
                return;

            $key = $field['name'];
            ?>
            <tr class="field_option field_option_<?php echo esc_attr( $key ); ?>">
                <td class="label">
                    <label><?php _e( 'Display a link to create content on the fly?', self::get_text_domain() ); ?></label>
                </td>
                <td>
                    <?php
                    do_action('acf/create_field', array(
                        'type'	=>	'radio',
                        'layout'	=>	'horizontal',
                        'name'	=>	'fields['.$key.'][acf_relationship_create]',
                        'value'	=>	empty( $field['acf_relationship_create'] ) ? 0 : 1,
                        'choices' => array(
                            0				=> __("No",'acf'),
                            1				=> __("Yes",'acf'),
                        )
                    ));
                    ?>
                </td>
            </tr>
            <?php
        }
    }

    ACF_Relationship_Create_Free::getInstance();
endif;