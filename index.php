<?php
/**
 * Plugin Name: WP QRCode by Google Chart
 * Plugin URI: http://blog.kmusiclife.com/p/wp-qrcode-gc/
 * Description: This plugin gives you making QR Code for your article permalink with Google Chart request. You are able to write shortcode  like [qrcode-gc alr:altname class:classname id:idname] If you require to apply new size QRCode, you might remove wp-qrcode-gc directory.
 * Version: 1.0.0
 * Author: Yuta Konishi
 * Author URI: http://blog.kmusiclife.com/
 * License: GPLv2
 */

if(!class_exists('WP_Qrcode_GC')) {
	class WP_Qrcode_GC {
	    
		/// Constants
        private static $params = array(
            'upload_dir' => '',
            'upload_url' => '',
            'permalink' => '',
            'key' => '',
            'ext' => '.png',
            'key' => '',
            'qr_img_name' => '',
            'qr_img_url' => '',
            'qr_img_file' => ''
        );
		const VERSION = '1.0.0';
		const SETTINGS_NAME = '_WP_Qrcode_GC_settings';
		const SETTINGS_PAGE_SLUG = 'wp-qrcode-gc-settings';
		const SETTINGS_UPLOAD_BASE_NAME = 'wp-qrcode-gc';
		private static $default_settings = null;

		public static function init() {
		    
		    $upload_dir = wp_upload_dir();
		    self::$params['upload_dir'] = $upload_dir['basedir'].'/'. self::SETTINGS_UPLOAD_BASE_NAME;
		    self::$params['upload_url'] = $upload_dir['baseurl'].'/'. self::SETTINGS_UPLOAD_BASE_NAME;
		    self::$params['permalink'] = home_url().add_query_arg();
		    self::$params['key'] = md5(self::$params['permalink']);
		    
		    self::$params['qr_img_name'] = self::$params['key'] . self::$params['ext'];
		    self::$params['qr_img_url'] = self::$params['upload_url'] . '/' . self::$params['qr_img_name'];
		    self::$params['qr_img_file'] = self::$params['upload_dir'] . '/' . self::$params['qr_img_name'];
		    
			self::add_actions();
			self::add_filters();
			self::register_shortcodes();

			register_activation_hook(__FILE__, array(__CLASS__, 'do_activation_actions'));
			register_deactivation_hook(__FILE__, array(__CLASS__, 'do_deactivation_actions'));
		}
		private static function add_actions() {
			add_action('init', array(__CLASS__, 'register_resources'), 0);
			if(is_admin()) {
				add_action('admin_init', array(__CLASS__, 'register_settings'));
				add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
			}
		}

		private static function add_filters() {}
		private static function make_qrcode_tag($options=array())
		{
		    return '<img src="' . self::$params['qr_img_url'] . '" alt="'.$options['alt'].'" class="'.$options['class'].'" id="'.$options['id'].'" />';
		}
		public function wp_qrcode_gc_add_shortcode_func($atts, $content = null, $code = ''){
            
            if( is_array($atts) ){
                foreach($atts as $att){
                    list($id, $value) = explode(':', $att);
                    $value = preg_replace("/'(.*)'/", '$1', $value);
                    $value = preg_replace('/"(.*)"/', '$1', $value);
                    $options[$id] = htmlspecialchars($value);
                }
            }
            
            if( file_exists(self::$params['qr_img_file']) ){
                return self::make_qrcode_tag($options);
            } else {
                
                $url = 'http://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode(self::$params['permalink']);
                $args = array('timeout' => 15);
                $response = wp_remote_get( $url, $args );
                
                if( !is_dir(self::$params['upload_dir']) and !is_writeable(self::$params['upload_dir']) )
                {
                    wp_mkdir_p(self::$params['upload_dir']);
                }
                if( $response['response']['code'] == 200 ){
                    file_put_contents( self::$params['qr_img_file'], $response['body'] );
                    chmod(self::$params['qr_img_file'], 0664);
                    return self::make_qrcode_tag($options);
                }
                
            }
		}
		private static function register_shortcodes() {
	        add_shortcode( 'qrcode-gc', array(__CLASS__, 'wp_qrcode_gc_add_shortcode_func') );
		}

		public static function do_activation_actions() {}
		public static function do_deactivation_actions() {}
		public static function register_resources() {}
		
		public static function add_settings_page() {
			$settings_page_hook_suffix = add_options_page(__('WP QRCode by Google Chart - Settings'), __('WP QRCode by Google Chart'), 'manage_options', self::SETTINGS_PAGE_SLUG, array(__CLASS__, 'display_settings_page'));
			if($settings_page_hook_suffix) {
				add_action("load-{$settings_page_hook_suffix}", array(__CLASS__, 'load_settings_page'));
			}
		}

		public static function display_settings_page() {
			$settings = self::_get_settings();
			include('views/backend/settings.php');
		}
		public static function load_settings_page() {}

		public static function register_settings() {
			register_setting(self::SETTINGS_NAME, self::SETTINGS_NAME, array(__CLASS__, 'sanitize_settings'));
		}
		public static function sanitize_settings($settings) {
			$defaults = self::_get_settings_default();

			if(isset($settings['size'])) {
				$settings['size'] = trim(strip_tags($settings['size']));
				if( ! preg_match("/^[0-9]+$/", $settings['size']) ){
    				$settings['size'] = $defaults['size'];
				}
				
			}
			return shortcode_atts($defaults, $settings);
		}

		private static function _get_settings($settings_key = null) {
			$defaults = self::_get_settings_default();
			$settings = get_option(self::SETTINGS_NAME, $defaults);
			$settings = shortcode_atts($defaults, $settings);
			return is_null($settings_key) ? $settings : (isset($settings[$settings_key]) ? $settings[$settings_key] : false);
		}

		private static function _get_settings_default() {
			if(is_null(self::$default_settings)) {
				self::$default_settings = array(
					'size' => '150'
				);
			}
			return self::$default_settings;
		}

		private static function _settings_id($key, $echo = true) {
			$settings_name = self::SETTINGS_NAME;
			$id = "{$settings_name}-{$key}";
			if($echo) {
				echo $id;
			}
			return $id;
		}

		private static function _settings_name($key, $echo = true) {
			$settings_name = self::SETTINGS_NAME;
			$name = "{$settings_name}[{$key}]";
			if($echo) {
				echo $name;
			}
			return $name;
		}

		public static function get_template_tag() {
			return '';
		}
	}

	WP_Qrcode_GC::init();
}
