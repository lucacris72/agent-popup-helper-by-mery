<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once APH_PATH . 'includes/class-ml-chatbot-admin.php';
require_once APH_PATH . 'includes/class-ml-chatbot-api.php';
require_once APH_PATH . 'includes/class-ml-chatbot-shortcode.php';

class APH_Plugin {
	public const OPTION_NAME = 'ml_chatbot_settings';

	private static ?APH_Plugin $instance = null;

	private APH_Admin $admin;
	private APH_API $api;
	private APH_Shortcode $shortcode;

	public static function instance() : APH_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->admin     = new APH_Admin( self::OPTION_NAME );
		$this->api       = new APH_API( self::OPTION_NAME );
		$this->shortcode = new APH_Shortcode( self::OPTION_NAME );

		add_action( 'plugins_loaded', array( $this, 'load' ) );
	}

	public function load() : void {
		$this->admin->register();
		$this->api->register();
		$this->shortcode->register();
	}

	public static function activate() : void {
		$defaults = self::get_default_settings();
		$current  = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $current ) ) {
			$current = array();
		}

		update_option( self::OPTION_NAME, wp_parse_args( $current, $defaults ) );
	}

	public static function get_default_settings() : array {
		return array(
			'enabled'             => 1,
			'display_mode'        => 'shortcode',
			'popup_position'      => 'right',
			'popup_open_delay'    => 0,
			'popup_visibility'    => 'all',
			'popup_show_label'    => 1,
			'api_key'             => '',
			'workflow_id'         => '',
			'workflow_version'    => '',
			'brand_name'          => '',
			'logo_id'             => 0,
			'theme_color'         => '#1d4ed8',
			'title'               => 'Agent PopUp Helper',
		);
	}

	public static function get_settings() : array {
		$settings = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, self::get_default_settings() );
	}
}
