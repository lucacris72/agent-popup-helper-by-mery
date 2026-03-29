<?php
/**
 * Plugin Name: Agent PopUp Helper by Mery
 * Plugin URI: https://github.com/MarianoAkaMery/agent-popup-helper-by-mery
 * Description: WordPress plugin for embedding an OpenAI-hosted ChatKit agent as a popup or embedded chatbot.
 * Version: 1.2.9
 * Author: MarianoAkaMery
 * Author URI: https://www.linkedin.com/in/salvatore-mariano-librici-0aaab3202/
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: ml-chatbot-ai
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APH_VERSION', '1.2.9' );
define( 'APH_PATH', plugin_dir_path( __FILE__ ) );
define( 'APH_URL', plugin_dir_url( __FILE__ ) );

require_once APH_PATH . 'includes/class-ml-chatbot-plugin.php';

function agent_popup_helper() : APH_Plugin {
	return APH_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'APH_Plugin', 'activate' ) );

agent_popup_helper();
