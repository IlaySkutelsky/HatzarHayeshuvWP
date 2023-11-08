<?php

if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('SeipImport')) {
    class SeipImport
    {
        private $post_metas;
        private $execution_time;
        private $current_post_id;

        public static function init()
        {
            $self = new self;
            add_action('admin_post_seip_import', [$self, 'seip_import']);
            add_action('admin_post_seip_option_import', [$self, 'seip_import_options']);
        }

        public function upload()
        {
            if (empty($_FILES['file']['size'])) {
                seip_notices_with_redirect('msg1', __('No file selected', 'simple-export-import-for-acf-data'),
                    'error');
            }

            $file = $_FILES['file'];

            if ($file['error']) {
                seip_notices_with_redirect('msg1',
                    __('Error uploading file. Please try again', 'simple-export-import-for-acf-data'), 'error');
            }

            if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
                seip_notices_with_redirect('msg1', __('Incorrect file type', 'simple-export-import-for-acf-data'),
                    'error');
            }

            if (function_exists('wp_json_file_decode')) {
                $posts = wp_json_file_decode($file['tmp_name'], ['associative' => true]);
            } else {
                $content = file_get_contents($file['tmp_name']);
                if (empty($content)) {
                    wp_send_json_error(['message' => "File is empty"]);
                }
                $posts = json_decode($content, 1);
            }

            if (!$posts || !is_array($posts)) {
                seip_notices_with_redirect('msg1', __('Import file empty', 'simple-export-import-for-acf-data'),
                    'error');
            }

            return $posts;
        }

        public function seip_import()
        {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(
                    $_POST['_wpnonce'],
                    'seip_import'
                ) || (!current_user_can('administrator') && !current_user_can('editor'))) {
                wp_send_json_error([
                    'message' => __('You are not allowed to submit data.', 'simple-export-import-for-acf-data')
                ]);
            }

            $post_id  = (int) $_POST['post_id'];
            $settings = [
                'bulk_import'           => isset($_POST['bulk_import']),
                'update_post_page_ttl'  => isset($_POST['update_post_page_ttl']),
                'update_post_page_slug' => isset($_POST['update_post_page_slug']),
                'single_post_id'        => $post_id,
                'post_type'             => sanitize_text_field($_POST['post_type'])
            ];

            $posts = $this->upload();

            if (isset($_POST['bulk_import'])) {
                $this->execution_time = time();
            }

            foreach ($posts as $post) {
                if(empty($post)){
                    continue;
                }
                $this->post_data($post, $settings);

                $this->sleep();
            }

            seip_notices_with_redirect('msg1', __('Successfully imported', 'simple-export-import-for-acf-data'),
                'success');
        }

        public function seip_import_options()
        {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(
                    $_POST['_wpnonce'],
                    'seip_option_import'
                ) || (!current_user_can('administrator') && !current_user_can('editor'))) {
                wp_send_json_error([
                    'message' => __('You are not allowed to submit data.', 'simple-export-import-for-acf-data')
                ]);
            }

            $data = $this->upload();

            foreach ($data['options'] as $key => $value) {
                update_field($key, $value, 'option');
            }

            seip_notices_with_redirect('msg1', __('Successfully imported', 'simple-export-import-for-acf-data'),
                'success');
        }

        private function post_data($data, $settings)
        {

            if (SeipOpcodespace::isPaid()) {
                $SeipImportEditorMediaFile = new SeipImportEditorMediaFile($data['source_domain']);
                $content = wp_kses_post($SeipImportEditorMediaFile->download_editor_media_files($data['post_content']));
            }
            else{
                $content = wp_kses_post($data['post_content']);
            }

            $post_data = [
                'post_content'  => $content,
                'post_status'   => sanitize_text_field($data['post_status']),
                'post_excerpt'  => sanitize_textarea_field($data['post_excerpt']),
                'post_password' => sanitize_text_field($data['post_password']),
            ];

            if ($settings['update_post_page_ttl']) {
                $post_data['post_title'] = sanitize_text_field($data['post_title']);
            }
            if ($settings['update_post_page_slug']) {
                $post_data['post_name'] = sanitize_title($data['post_name']);
            }

            if ($settings['bulk_import']) {

                if (!SeipOpcodespace::isPaid()) {
                    wp_send_json_error([
                        'message' => __('You are using free plugin. Please upgrade to access this feature.',
                            'simple-export-import-for-acf-data')
                    ]);
                }

                $post = get_posts([
                    'name'      => sanitize_title($data['post_name']),
                    'post_type' => sanitize_text_field($settings['post_type'])
                ]);

                if (empty($post)) {
                    $primary_data = [
                        'post_date'     => sanitize_text_field($data['post_date']),
                        'post_content'  => $content,
                        'post_title'    => sanitize_text_field($data['post_title']),
                        'post_status'   => sanitize_text_field($data['post_status']),
                        'post_excerpt'  => sanitize_textarea_field($data['post_excerpt']),
                        'post_password' => sanitize_text_field($data['post_password']),
                        'post_type'     => sanitize_text_field($settings['post_type'])
                    ];

                    $post_id = wp_insert_post($primary_data);
                } else {
                    $post_id         = $post[0]->ID;
                    $post_data['ID'] = $post_id;
                    wp_update_post($post_data);
                }
            } else {
                $post_id         = (int) $settings['single_post_id'];
                $post_data['ID'] = $post_id;

                wp_update_post(
                    $post_data
                );
            }

            $this->current_post_id = $post_id;
            $this->post_metas      = $data['metas'];

            if(isset($data['metas']) && !empty($data['metas'])){
                foreach ($data['metas'] as $key => $value) {
                    update_post_meta($post_id, $key, $this->get_field_value($key, $value));
                }
            }

            # Adding Featured image
            $featured_image = (array) $data['featured_image'];

            if (!empty($featured_image) && isset($featured_image['url'])) {
                $upload = $this->download($featured_image['url']);
                $this->set_featured_image($post_id, $upload, $featured_image);
            }

            # Setting Terms
            $this->set_terms($post_id, $data['terms']);
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

            if(empty($value)){
                return $value;
            }

            if (!isset($this->post_metas['_'.$key]) || empty($this->post_metas['_'.$key])) {
                return $value;
            }

            $related_field = get_field_object($this->post_metas['_'.$key]);

            if (!$related_field) {
                return $value;
            }

            if ($related_field['type'] === 'checkbox') {
                return maybe_unserialize($value);
            }

            if ($related_field['type'] === 'select' && $related_field['multiple']) {
                return maybe_unserialize($value);
            }


            if ($related_field['type'] === 'image' && !SeipOpcodespace::isPaid()) {
                $seip_settings = get_option('seip_settings');
                $total_uploaded_images = $seip_settings['import_images'];

                if($total_uploaded_images >= 10){
                    return $value;
                }

                $total_uploaded_images++;
                $seip_settings['import_images'] = $total_uploaded_images;
                update_option( 'seip_settings', $seip_settings);

                $upload = $this->download($value['url']);
                return $this->attach($upload, $value);

            }

            if (!SeipOpcodespace::isPaid()) {
                return $value;
            }

            if ($related_field['type'] === 'flexible_content') {
                return maybe_unserialize($value);
            }

            if ($related_field['type'] === 'link') {
                return $this->link_field($value);
            }

            if ($related_field['type'] === 'image') {
                $upload = $this->download($value['url']);
                return $this->attach($upload, $value);
            }

            if ($related_field['type'] === 'file') {
                $upload = $this->download($value['url']);
                return $this->attach($upload, $value);
            }

            if ($related_field['type'] === 'gallery') {
                $images = maybe_unserialize($value);

                $new_images = [];
                foreach ($images as $image) {
                    $upload       = $this->download($image['url']);
                    $new_images[] = $this->attach($upload, $image);
                }

                return $new_images;
            }

            return $value;
        }

        /**
         * @param $value
         * @return array|false
         */
        public function download($value)
        {
            if (empty($value)) {
//                seip_log('Empty file ', $value);
                return false;
            }

            $response = wp_remote_get(
                $value,
                array(
                    'timeout'  => 300,
                    'filename' => basename($value)
                )
            );

            $response_code = wp_remote_retrieve_response_code($response);
            $content       = wp_remote_retrieve_body($response);

            if ($response_code != 200) {
//                seip_log('Error while fetching file ', 'Response code: ' . $response_code . $value);
                return false;
            }

            $upload = wp_upload_bits(basename($value), null, $content);

            if (!empty($upload['error'])) {
//                seip_log('Error while uploading file ', $value .' '. $upload['error']);
                return false;
            }

            return $upload;
        }

        /**
         * @param $upload
         * @param $media_data
         * @return mixed
         */
        public function attach($upload, $media_data)
        {
            if (empty($upload)) {
                return false;
            }

            $attachment = array(
                'post_mime_type' => $upload['type'],
                'guid'           => $upload['url'],
                'post_title'     => empty($media_data['post_title']) ? sanitize_title(basename($upload['file'])) : sanitize_text_field($media_data['post_title']),
                'post_content'   => isset($media_data['post_content']) ? sanitize_text_field($media_data['post_content']) : '',
                'post_excerpt'   => isset($media_data['post_excerpt']) ? sanitize_text_field($media_data['post_excerpt']) : '',
                'post_parent'    => $this->current_post_id > 0 ? $this->current_post_id : 0,
            );
            $attach_id  = wp_insert_attachment($attachment, $upload['file']);

            if (is_wp_error($attach_id)) {
//                seip_log('Error while attaching media', $attach_id->get_error_messages());
                return $attach_id;
            }

            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            if (isset($media_data['_wp_attachment_image_alt'])) {
                update_post_meta($attach_id, '_wp_attachment_image_alt', $media_data['_wp_attachment_image_alt']);
            }

            return $attach_id;
        }

        /**
         * @param $post
         * @param $upload
         * @param $data
         * @return int|WP_Error
         */
        protected function set_featured_image($post, $upload, $media_data)
        {
            if (empty($upload)) {
                return false;
            }

            $attachment_id = $this->attach($upload, $media_data);

            if (is_wp_error($attachment_id)) {
                return false;
            }

            return set_post_thumbnail($post, $attachment_id);
        }


        protected function set_terms($post, $terms)
        {
            if (!SeipOpcodespace::isPaid()) {
                return false;
            }

            if (empty($terms)) {
                return false;
            }

            foreach ($terms as $_term) {
                foreach ($_term as $term) {
                    if (empty($term)) {
                        continue;
                    }

                    $post_term = get_term_by('slug', $term['slug'], $term['taxonomy']);

                    if (!$post_term) {
                        wp_set_object_terms($post, $term['name'], $term['taxonomy'], true);
                        continue;
                    }

                    wp_set_object_terms($post, [$post_term->term_id], $post_term->taxonomy, true);
                }

            }

            return true;
        }

        private function sleep()
        {
            if ($this->execution_time > 0 && (time() - $this->execution_time) > 20) {
                sleep(1);
            }
        }

        protected function link_field($value)
        {
            $url         = str_replace($value['source_domain'], home_url(), $value['link']['url']);
            $link        = $value['link'];
            $link['url'] = $url;
            return $link;
        }
    }

}
