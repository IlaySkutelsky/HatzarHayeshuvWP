<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SeipImportEditorMediaFile')) {


    class SeipImportEditorMediaFile
    {
        protected $source_domain;

        public function __construct($source_domain)
        {
            $this->source_domain = $source_domain;
        }

        public function download_editor_media_files($content)
        {
            $content = $this->download_editor_image($content);
            $content = $this->replace_editor_link($content);
            return $this->download_editor_object($content);
        }

        public function download_editor_image($content)
        {
            $reg = '/(?<=src=")(.*?)(?=")/m';

            return $this->matched_url_download($reg, $content);
        }

        public function download_editor_object($content)
        {
            $reg = '/(?<=data=")(.*?)(?=")/m';

            return $this->matched_url_download($reg, $content);
        }


        function matched_url_download($reg, $content)
        {
            preg_match_all($reg, $content, $matches, PREG_SET_ORDER, 0);

            if (empty($matches)) {
                return $content;
            }

            $SeipImport = new SeipImport();

            foreach ($matches as $match) {
                $source_url = $match[0];

                # if different source, this will not download
                if (str_contains($this->source_domain, $source_url)) {
                    continue;
                }

                $upload = $SeipImport->download($source_url);

                if (!$upload) {
                    continue;
                }

                $SeipImport->attach($upload, [], 0);
                $content = str_replace($source_url, $upload['url'], $content);
            }

            return $content;
        }

        public function replace_editor_link($content)
        {
            $reg = '/(?<=href=")(.*?)(?=")/m';

            return $this->replace_url($reg, $content);
        }

        public function replace_url($reg, $content)
        {

            preg_match_all($reg, $content, $matches, PREG_SET_ORDER, 0);

            if (empty($matches)) {
                return $content;
            }

            $target_domain = home_url();

            foreach ($matches as $match) {
                $source_url = $match[0];

                # if different source, this will not download
                if (str_contains($this->source_domain, $source_url)) {
                    continue;
                }

                $content = str_replace($this->source_domain, $target_domain, $content);
            }

            return $content;
        }

    }
}