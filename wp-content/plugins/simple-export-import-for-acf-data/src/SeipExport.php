<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SeipExport')) {
    class SeipExport
    {
        private $post_metas;

        public static function init()
        {
            $self = new self;
            add_action('admin_post_seip_export', [$self, 'seip_export']);
            add_action('admin_post_seip_option_export', [$self, 'seip_export_options']);
        }

        public function seip_export()
        {

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(
                    $_POST['_wpnonce'],
                    'seip_export'
                ) || (!current_user_can('administrator') && !current_user_can('editor'))) {
                wp_send_json_error([
                    'message' => __('You are not allowed to submit data.', 'simple-export-import-for-acf-data')
                ]);
            }

            $post_data = [];
            if (isset($_POST['bulk_export'])) {
                if (!SeipOpcodespace::isPaid()) {
                    seip_notices_with_redirect('msg1',
                        __('You are using free plugin. Please upgrade to access this feature.',
                            'simple-export-import-for-acf-data'), 'error');
                }

                $post_ids = isset($_POST['post_ids']) ? explode(',', $_POST['post_ids']) : false;

                if(empty($post_ids)){
                    seip_notices_with_redirect('msg1',
                        __('Please select single post.',
                            'simple-export-import-for-acf-data'), 'error');
                }

                foreach (explode(',', $_POST['post_ids']) as $post_id) {
                    $post_data[] = $this->post_data((int) trim($post_id));
                }
            } else {
                $post_id     = isset($_POST['post_id']) ? (int) $_POST['post_id'] : false;
                if(empty($post_id)){
                    seip_notices_with_redirect('msg1',
                        __('Please select single post.',
                            'simple-export-import-for-acf-data'), 'error');
                }
                $post_data[] = $this->post_data($post_id);
            }

            $data = wp_json_encode($post_data);

            $json_file_name = 'post-export-'.date('y-m-d').'.json';

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename='.$json_file_name);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Content-Length: '.strlen($data));
            file_put_contents('php://output', $data);
        }

        private function post_data($post_id)
        {
            $post       = get_post($post_id);
            $post_metas = get_post_meta($post_id);

            $this->post_metas = $post_metas;

            $sorted_metas = [];

            foreach ($post_metas as $key => $value) {
                $sorted_metas[$key] = $this->get_field_value($key, $value[0]);
            }

            return [
                'post_date'      => $post->post_date,
                'post_title'     => $post->post_title,
                'post_name'      => $post->post_name,
                'post_content'   => $post->post_content,
                'post_status'    => $post->post_status,
                'post_excerpt'   => $post->post_excerpt,
                'post_password'  => $post->post_password,
                'featured_image' => $this->media_file(get_post_thumbnail_id($post_id)),
                'metas'          => $sorted_metas,
                'terms'          => isset($_POST['export_taxonomy']) ? $this->terms($post) : '',
                'source_domain'     => home_url()
            ];
        }

        public function seip_export_options()
        {

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(
                    $_POST['_wpnonce'],
                    'seip_option_export'
                ) || (!current_user_can('administrator') && !current_user_can('editor'))) {
                wp_send_json_error(['message' => 'You are not allowed to submit data.']);
            }

            if (!SeipOpcodespace::isPaid()) {
                seip_notices_with_redirect('msg1',
                    __('You are using a free plugin. Please upgrade to access this feature.',
                        'simple-export-import-for-acf-data'), 'error');
            }

            $options = get_fields('options');

            $sorted_metas = [];

            foreach ($options as $key => $value) {
                // Need work here
                $field = get_field_object(get_option('_options_'.$key));

                if (empty($field)) {
                    $sorted_metas[$key] = $value;
                    continue;
                }

                $sorted_metas[$key] = $this->get_value_based_on_field_type($field, $value);
            }

            $data = [
                'type'    => 'options',
                'options' => $sorted_metas
            ];

            $data = json_encode($data);

            $json_file_name = 'Options-'.date('y-m-d').'.json';

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename='.$json_file_name);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Content-Length: '.strlen($data));
            file_put_contents('php://output', $data);
        }

        /**
         * @param $key
         * @param $value
         * @return false|mixed|string
         */
        public function get_field_value($key, $value)
        {
            if (!function_exists('get_field_object')) {
                return $value;
            }

            if (!isset($this->post_metas['_'.$key][0]) || empty($this->post_metas['_'.$key][0])) {
                return $value;
            }

            $related_field = get_field_object($this->post_metas['_'.$key][0]);

            // echo '<pre>'; print_r($related_field); echo '</pre>';

            // echo $value;


            if (!$related_field) {
                return $value;
            }

            return $this->get_value_based_on_field_type($related_field, $value);
        }

        private function get_value_based_on_field_type($field, $value)
        {

            if ($field['type'] === 'image' && !SeipOpcodespace::isPaid()) {
                $seip_settings = get_option('seip_settings');
                $total_uploaded_images = $seip_settings['import_import'];

                if($total_uploaded_images >= 10){
                    return $value;
                }

                $this->get_image_link($value);
                $total_uploaded_images++;
                $seip_settings['import_import'] = $total_uploaded_images;
                update_option( 'seip_settings', $seip_settings);

            }

            if (!SeipOpcodespace::isPaid()) {
                return $value;
            }

            if ($field['type'] === 'image') {
                return $this->get_image_link($value);
            }

            if ($field['type'] === 'file') {
                return $this->get_file_link($value);
            }

            if ($field['type'] === 'link') {
                return $this->link_field($value);
            }

            if ($field['type'] === 'gallery') {
                $image_links = [];
                $attach_ids  = maybe_unserialize($value);
                if (!empty($attach_ids)) {
                    foreach ($attach_ids as $attach_id) {
                        $image_links[] = $this->media_file($attach_id);
                    }
                }

                return maybe_serialize($image_links);
            }

            return $value;
        }


        /**
         * @param $acf_field_value
         * @return false|string
         */
        public function get_image_link($acf_field_value)
        {
            if (empty($acf_field_value)) {
                return '';
            }

            return $this->media_file($acf_field_value);
        }

        /**
         * @param $acf_field_value
         * @return false|string
         */
        public function get_file_link($acf_field_value)
        {
            if (empty($acf_field_value)) {
                return '';
            }

            return $this->media_file($acf_field_value);
        }

        protected function link_field($value)
        {
            return [
                'link'          => maybe_unserialize($value),
                'source_domain' => home_url()
            ];
        }

        protected function taxonomies($post_type)
        {
            return get_object_taxonomies($post_type);
        }

        protected function terms($post)
        {
            $terms = [];

            if (!SeipOpcodespace::isPaid()) {
                return $terms;
            }

            $taxonomies = $this->taxonomies($post->post_type);

            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {
                    $term = get_the_terms($post, $taxonomy);
                    if (!empty($term)) {
                        $terms[] = (array) $term;
                    }
                }
            }


            return $terms;
        }

        protected function media_file($attach_id)
        {
            if (empty($attach_id)) {
                return false;
            }

            $post = get_post($attach_id);

            if (empty($post)) {
                return false;
            }

            $data = [
                'post_title'   => $post->post_title,
                'post_content' => $post->post_content,
                'post_excerpt' => $post->post_excerpt,
                'url'          => wp_get_attachment_url($attach_id)
            ];

            $alter_text = get_post_meta($attach_id, '_wp_attachment_image_alt', true);

            if ($alter_text) {
                $data['_wp_attachment_image_alt'] = get_post_meta($attach_id, '_wp_attachment_image_alt', true);
            }

            return $data;
        }
    }
}
