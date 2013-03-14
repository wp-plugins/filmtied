<?php
/*
Plugin Name: Filmtied
Version: 0.1
Description: Replace IMDB links with Filmtied links
Author: Filmtied Team
Author URI: http://www.filmtied.com/
Plugin URI: http://www.filmtied.com/
*/

require_once( plugin_dir_path(__FILE__) . 'lib/FilmTiedApi.php');

if (!class_exists('Filmtied')) {

define('FILMTIED_API_SERVER_ADDRESS', 'http://api.filmtied.com/');

    class Filmtied
    {
        public static $_filmTiedApi;

        public static function updateContent($content)
        {
            switch (get_option('filmtied_link_position')) {
                case 'replace':
                    $content = self::replaceLinks($content);
                    break;
                case 'right':
                    $content = self::addLinks($content, 'right');
                    break;
                case 'left':
                    $content = self::addLinks($content, 'left');
                    break;
            }

            return $content;
        }

        public static function replaceLinks($content)
        {
            $pattern = '/http:\/\/www.imdb.com\/[a-zA-Z]+\/([a-zA-Z0-9]+)\/?/';
            preg_match_all($pattern, $content, $matches);

            if (!empty($matches[0])) {
                $uniqueUrls = array_unique($matches[0]);

                if (!empty($uniqueUrls)) {
                    foreach ($uniqueUrls as $url) {
                        $newUrl = self::getFilmTiedUrl($url);
                        $content = str_ireplace($url, $newUrl, $content);
                    }
                }
            }

            return $content;
        }

        public static function getNameFromUrl($url)
        {
            if (strpos($url, "#") !== false) {
                $url = substr($url, 0, strpos($url, "#"));
            }
            $name = substr($url, strlen($url) - strpos(strrev($url), "/"));
            $name = str_ireplace('-', ' ', $name);
            $name = ucwords($name);

            return $name;
        }

        public static function addLinks($content, $position = 'right')
        {
            $pattern = '/<a .*?href="(http:\/\/www.imdb.com\/[a-zA-Z]+\/(?:[a-zA-Z0-9]+)\/?)".*?>(.+?)<\/a>/';
            preg_match_all($pattern, $content, $matches);

            $lookup = array();
            if (!empty($matches[1])) {
                $uniqueUrls = array_unique($matches[1]);

                if (!empty($uniqueUrls)) {
                    foreach ($uniqueUrls as $url) {
                        $newUrl = self::getFilmTiedUrl($url);
                        $lookup[$url] = $newUrl;
                    }
                }
            }

            if (!empty($matches[0])) {
                foreach ($matches[0] as $key => $node) {

                    $url = $matches[1][$key];
                    $newUrl = $lookup[$url];

                    if ($newUrl) {
                        $newNode = str_ireplace($url, $newUrl, $node);

                        $nodeContent = $matches[2][$key];
                        if ($nodeContent !== $newUrl) {
                            $newNode = str_ireplace($nodeContent, self::getNameFromUrl($newUrl), $newNode);
                        }

                        if ($position === 'right') {
                            $new = $node . ' ' . $newNode;
                        } elseif ($position === 'left') {
                            $new = $newNode . ' ' . $node;
                        }
                        $content = str_ireplace($node, $new, $content);
                    }
                }
            }

            return $content;
        }


        public static function getFilmTiedUrl($url)
        {
            global $table_prefix;

            $params = array(
                'apiServerAddress' => FILMTIED_API_SERVER_ADDRESS
            );

            $cacheType = get_option('filmtied_cache_type');
            if ($cacheType === "file") {
                $params['cache'] = $cacheType;
                $params['cacheDirPath'] = get_option('filmtied_cache_dir');
            } elseif ($cacheType === "database") {
                $params['cache'] = $cacheType;
                $params['cacheServerAddress']         = DB_HOST;
                $params['cacheServerUsername']        = DB_USER;
                $params['cacheServerPassword']        = DB_PASSWORD;
                $params['cacheServerDbName']          = DB_NAME;
                $params['cacheServerCharset']         = DB_CHARSET;
                $params['cacheServerTable']           = $table_prefix . 'filmtied_cache';
                $params['cacheServerAutoCreateTable'] = true;
            }

            $token = get_option('filmtied_api_token');
            try {
                if (!self::$_filmTiedApi) {
                    self::$_filmTiedApi = new FilmTiedApi($token, $params);
                }
                $filmtiedurl = self::$_filmTiedApi->changeUrl($url);

                $affiliateId = get_option('filmtied_affiliate_id');
                if (!empty($affiliateId)) {
                    $filmtiedurl .= '#af=' . $affiliateId;
                }
                return $filmtiedurl;
            } catch (FilmTiedApiException $e) {
            }

            return '';
        }

        public static function getApiToken()
        {
            $token = '';
            $siteUrl = get_option('siteurl');
            $apiServerAddress = FILMTIED_API_SERVER_ADDRESS . 'get-token';
            $postData = array(
                'url' => $siteUrl
            );

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiServerAddress);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

            $result = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code < 400) {
                $data = @json_decode($result, true);
                $token = (!empty($data['token'])) ? $data['token'] : '';
            }

            return $token;
        }

        public static function pluginInstall()
        {
            $apiToken = self::getApiToken();

            add_option('filmtied_link_position', 'replace');
            add_option('filmtied_affiliate_id', '');
            add_option('filmtied_api_token', $apiToken ? $apiToken : '');

            $cacheType = '';
            $cacheDir = plugin_dir_path(__FILE__) . 'cache';
            if (!@is_dir($cacheDir)) {
                if (!mkdir($cacheDir) && is_writeable($cacheDir)) {
                    $cacheType = 'file';
                }
            } elseif (is_writeable($cacheDir)) {
                $cacheType = 'file';
            }

            if ($cacheType === 'file') {
                add_option('filmtied_cache_type', $cacheType);
                add_option('filmtied_cache_dir', $cacheDir);
            } elseif ($cacheType === 'database') {


            }
        }

        public static function pluginRemove()
        {
            delete_option('filmtied_link_position');
            delete_option('filmtied_affiliate_id');
            delete_option('filmtied_api_token');
            delete_option('filmtied_cache_type');
            delete_option('filmtied_cache_dir');
        }

        public static function adminMenu()
        {
            add_options_page('Filmtied', 'Filmtied', 'manage_options', 'filmtied', array('Filmtied', 'optionsPage'));
        }

        public static function optionsPage()
        {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
            include(plugin_dir_path(__FILE__) . 'options.php');
        }
    }

    if (is_admin()) {
        register_activation_hook(__FILE__, array('Filmtied', 'pluginInstall'));
        register_deactivation_hook(__FILE__, array('Filmtied', 'pluginRemove'));
        add_action('admin_menu', array('Filmtied', 'adminMenu'));
    }
    add_filter('the_content', array('Filmtied', 'updateContent'));

}