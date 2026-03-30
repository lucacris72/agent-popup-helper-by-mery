<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APH_Admin {
	private string $option_name;
	private string $page_slug = 'ml-chatbot-ai';
	private string $page_hook = '';

	public function __construct( string $option_name ) {
		$this->option_name = $option_name;
	}

	public function register() : void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function add_menu_page() : void {
		$this->page_hook = add_options_page(
			__( 'Agent PopUp Helper by Mery', 'ml-chatbot-ai' ),
			__( 'Agent PopUp Helper by Mery', 'ml-chatbot-ai' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() : void {
		register_setting(
			'ml_chatbot_ai_settings_group',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => APH_Plugin::get_default_settings(),
			)
		);

		add_settings_section(
			'ml_chatbot_ai_main_section',
			__( 'OpenAI ChatKit Agent Settings', 'ml-chatbot-ai' ),
			array( $this, 'render_section_description' ),
			$this->page_slug
		);

		$this->add_field( 'enabled', __( 'Enable chatbot', 'ml-chatbot-ai' ), array( $this, 'render_checkbox_field' ) );
		$this->add_field( 'display_mode', __( 'Display mode', 'ml-chatbot-ai' ), array( $this, 'render_select_field' ) );
		$this->add_field( 'popup_position', __( 'Popup position', 'ml-chatbot-ai' ), array( $this, 'render_select_field' ) );
		$this->add_field( 'popup_open_delay', __( 'Popup open delay', 'ml-chatbot-ai' ), array( $this, 'render_number_field' ) );
		$this->add_field( 'popup_visibility', __( 'Popup visibility', 'ml-chatbot-ai' ), array( $this, 'render_select_field' ) );
		$this->add_field( 'popup_show_label', __( 'Show launcher text', 'ml-chatbot-ai' ), array( $this, 'render_checkbox_field' ) );
		$this->add_field( 'api_key', __( 'OpenAI API key', 'ml-chatbot-ai' ), array( $this, 'render_api_key_field' ) );
		$this->add_field( 'workflow_id', __( 'Workflow ID', 'ml-chatbot-ai' ), array( $this, 'render_text_field' ) );
		$this->add_field( 'workflow_version', __( 'Workflow version', 'ml-chatbot-ai' ), array( $this, 'render_text_field' ) );
		$this->add_field( 'brand_name', __( 'Brand name', 'ml-chatbot-ai' ), array( $this, 'render_text_field' ) );
		$this->add_field( 'logo_id', __( 'Brand logo', 'ml-chatbot-ai' ), array( $this, 'render_logo_field' ) );
		$this->add_field( 'theme_color', __( 'Theme color', 'ml-chatbot-ai' ), array( $this, 'render_color_field' ) );
		$this->add_field( 'hover_color', __( 'Hover color', 'ml-chatbot-ai' ), array( $this, 'render_color_field' ) );
		$this->add_field( 'title', __( 'Chatbot title', 'ml-chatbot-ai' ), array( $this, 'render_text_field' ) );
	}

	public function enqueue_assets( string $hook ) : void {
		if ( $hook !== $this->page_hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'ml-chatbot-ai-admin',
			APH_URL . 'assets/js/ml-chatbot-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			APH_VERSION,
			true
		);
	}

	private function add_field( string $key, string $label, callable $callback ) : void {
		add_settings_field(
			$key,
			$label,
			$callback,
			$this->page_slug,
			'ml_chatbot_ai_main_section',
			array( 'key' => $key )
		);
	}

	public function sanitize_settings( array $input ) : array {
		if ( ! current_user_can( 'manage_options' ) ) {
			return APH_Plugin::get_settings();
		}

		$current   = APH_Plugin::get_settings();
		$sanitized = array();

		$sanitized['enabled'] = empty( $input['enabled'] ) ? 0 : 1;
		$sanitized['display_mode'] = $this->sanitize_select_value(
			isset( $input['display_mode'] ) ? (string) $input['display_mode'] : '',
			array( 'shortcode', 'popup' ),
			'shortcode'
		);
		$sanitized['popup_position'] = $this->sanitize_select_value(
			isset( $input['popup_position'] ) ? (string) $input['popup_position'] : '',
			array( 'right', 'left' ),
			'right'
		);
		$sanitized['popup_open_delay'] = isset( $input['popup_open_delay'] ) ? max( 0, min( 30, absint( $input['popup_open_delay'] ) ) ) : 0;
		$sanitized['popup_visibility'] = $this->sanitize_select_value(
			isset( $input['popup_visibility'] ) ? (string) $input['popup_visibility'] : '',
			array( 'all', 'desktop', 'mobile' ),
			'all'
		);
		$sanitized['popup_show_label'] = empty( $input['popup_show_label'] ) ? 0 : 1;

		$api_key = isset( $input['api_key'] ) ? trim( (string) $input['api_key'] ) : '';
		$sanitized['api_key'] = '' !== $api_key ? sanitize_text_field( $api_key ) : $current['api_key'];
		if ( '' !== $api_key && 0 !== strpos( $api_key, 'sk-' ) ) {
			add_settings_error( 'ml_chatbot_ai_messages', 'ml_chatbot_ai_api_key_format', __( 'The OpenAI API key should normally start with sk-. Please verify that you pasted the correct key.', 'ml-chatbot-ai' ), 'warning' );
		}

		$workflow_id = isset( $input['workflow_id'] ) ? sanitize_text_field( (string) $input['workflow_id'] ) : '';
		$sanitized['workflow_id'] = $workflow_id;
		if ( '' !== $workflow_id && 0 !== strpos( $workflow_id, 'wf_' ) ) {
			add_settings_error( 'ml_chatbot_ai_messages', 'ml_chatbot_ai_workflow_id_format', __( 'The workflow ID should normally start with wf_. Please verify the value copied from OpenAI.', 'ml-chatbot-ai' ), 'warning' );
		}
		$workflow_version = isset( $input['workflow_version'] ) ? sanitize_text_field( (string) $input['workflow_version'] ) : '';
		$sanitized['workflow_version'] = $workflow_version;

		$brand_name = isset( $input['brand_name'] ) ? sanitize_text_field( (string) $input['brand_name'] ) : '';
		$sanitized['brand_name'] = $brand_name;

		$sanitized['logo_id'] = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;

		$theme_color = isset( $input['theme_color'] ) ? sanitize_hex_color( (string) $input['theme_color'] ) : '';
		$sanitized['theme_color'] = $theme_color ?: APH_Plugin::get_default_settings()['theme_color'];
		$hover_color = isset( $input['hover_color'] ) ? sanitize_hex_color( (string) $input['hover_color'] ) : '';
		$sanitized['hover_color'] = $hover_color ?: '';

		$title = isset( $input['title'] ) ? sanitize_text_field( (string) $input['title'] ) : '';
		$sanitized['title'] = '' !== $title ? $title : APH_Plugin::get_default_settings()['title'];

		add_settings_error( 'ml_chatbot_ai_messages', 'ml_chatbot_ai_saved', __( 'Settings saved.', 'ml-chatbot-ai' ), 'updated' );

		return $sanitized;
	}

	private function sanitize_select_value( string $value, array $allowed, string $fallback ) : string {
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	public function render_section_description() : void {
		$settings               = APH_Plugin::get_settings();
		$has_api_key            = ! empty( $settings['api_key'] );
		$has_workflow_id        = ! empty( $settings['workflow_id'] );
		$is_ready               = ! empty( $settings['enabled'] ) && $has_api_key && $has_workflow_id;
		$status_label           = $is_ready ? __( 'Ready', 'ml-chatbot-ai' ) : __( 'Setup incomplete', 'ml-chatbot-ai' );
		$status_color           = $is_ready ? '#15803d' : '#b45309';
		$display_mode_label     = 'popup' === $settings['display_mode'] ? __( 'Floating popup', 'ml-chatbot-ai' ) : __( 'Shortcode window', 'ml-chatbot-ai' );
		$configuration_messages = $this->get_configuration_messages( $settings );

		echo '<p>' . esc_html__( 'Configure your OpenAI ChatKit agent, popup behaviour, and chatbot branding from one place.', 'ml-chatbot-ai' ) . '</p>';
		echo '<div style="margin:16px 0 0;padding:16px;border:1px solid #d0d7e2;border-radius:14px;background:#fff;">';
		echo '<p style="margin:0 0 10px;font-size:13px;font-weight:700;color:' . esc_attr( $status_color ) . ';">' . esc_html( $status_label ) . '</p>';
		echo '<p style="margin:0 0 8px;">' . esc_html__( 'Current display mode:', 'ml-chatbot-ai' ) . ' <strong>' . esc_html( $display_mode_label ) . '</strong></p>';
		echo '<p style="margin:0 0 8px;">' . esc_html__( 'API key saved:', 'ml-chatbot-ai' ) . ' <strong>' . esc_html( $has_api_key ? __( 'Yes', 'ml-chatbot-ai' ) : __( 'No', 'ml-chatbot-ai' ) ) . '</strong></p>';
		echo '<p style="margin:0;">' . esc_html__( 'Workflow ID saved:', 'ml-chatbot-ai' ) . ' <strong>' . esc_html( $has_workflow_id ? __( 'Yes', 'ml-chatbot-ai' ) : __( 'No', 'ml-chatbot-ai' ) ) . '</strong></p>';
		if ( ! empty( $configuration_messages ) ) {
			echo '<ul style="margin:12px 0 0 18px;">';
			foreach ( $configuration_messages as $message ) {
				echo '<li>' . esc_html( $message ) . '</li>';
			}
			echo '</ul>';
		}
		echo '</div>';
		echo '<div style="margin:16px 0 0;padding:14px 16px;border:1px solid #d0d7e2;border-radius:12px;background:#fff;">';
		echo '<p style="margin:0 0 8px;"><strong>' . esc_html__( 'Important notes', 'ml-chatbot-ai' ) . '</strong></p>';
		echo '<p style="margin:0 0 6px;">' . esc_html__( 'If you choose Floating popup mode, do not place the [ml_chatbot] shortcode inside pages for that same usage. The popup is injected automatically in the site footer.', 'ml-chatbot-ai' ) . '</p>';
		echo '<p style="margin:0 0 6px;">' . esc_html__( 'If you choose Shortcode window mode, place [ml_chatbot] only where you want the chatbot to appear.', 'ml-chatbot-ai' ) . '</p>';
		echo '<p style="margin:0 0 6px;">' . esc_html__( 'To connect an OpenAI-hosted agent, save both a valid OpenAI API key and a Workflow ID from OpenAI ChatKit / Agent Builder.', 'ml-chatbot-ai' ) . '</p>';
		echo '<p style="margin:0;">' . esc_html__( 'Theme color, brand name and logo affect only this plugin UI. They do not change your WordPress theme.', 'ml-chatbot-ai' ) . '</p>';
		echo '</div>';
	}

	private function get_configuration_messages( array $settings ) : array {
		$messages = array();

		if ( empty( $settings['enabled'] ) ) {
			$messages[] = __( 'The chatbot is currently disabled, so nothing will be shown on the frontend.', 'ml-chatbot-ai' );
		}

		if ( empty( $settings['api_key'] ) ) {
			$messages[] = __( 'Add your OpenAI API key to allow WordPress to create ChatKit sessions.', 'ml-chatbot-ai' );
		}

		if ( empty( $settings['workflow_id'] ) ) {
			$messages[] = __( 'Add your OpenAI Workflow ID to connect the plugin to your hosted agent.', 'ml-chatbot-ai' );
		}

		if ( 'shortcode' === $settings['display_mode'] ) {
			$messages[] = __( 'Shortcode mode requires placing [ml_chatbot] in a page or post.', 'ml-chatbot-ai' );
		} else {
			$messages[] = __( 'Popup mode is injected automatically in the site footer.', 'ml-chatbot-ai' );
		}

		return $messages;
	}

	public function render_settings_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Agent PopUp Helper by Mery', 'ml-chatbot-ai' ); ?></h1>
			<p><?php echo esc_html__( 'Agent PopUp Helper by Mery. Programmed by MarianoAkaMery.', 'ml-chatbot-ai' ); ?></p>
			<?php settings_errors( 'ml_chatbot_ai_messages' ); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'ml_chatbot_ai_settings_group' );
				do_settings_sections( $this->page_slug );
				submit_button( __( 'Save Settings', 'ml-chatbot-ai' ) );
				?>
			</form>
		</div>
		<?php
	}

	public function render_checkbox_field( array $args ) : void {
		$settings = APH_Plugin::get_settings();
		$key      = $args['key'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( $this->option_name . '[' . $key . ']' ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
			<?php
			if ( 'enabled' === $key ) {
				echo esc_html__( 'Enable the chatbot on the frontend.', 'ml-chatbot-ai' );
			} elseif ( 'popup_show_label' === $key ) {
				echo esc_html__( 'Show text next to the launcher icon on larger screens.', 'ml-chatbot-ai' );
			}
			?>
		</label>
		<?php
	}

	public function render_select_field( array $args ) : void {
		$settings = APH_Plugin::get_settings();
		$key      = $args['key'];
		$options  = array();

		if ( 'display_mode' === $key ) {
			$options = array(
				'shortcode' => __( 'Shortcode window', 'ml-chatbot-ai' ),
				'popup'     => __( 'Floating popup', 'ml-chatbot-ai' ),
			);
		} elseif ( 'popup_position' === $key ) {
			$options = array(
				'right' => __( 'Bottom right', 'ml-chatbot-ai' ),
				'left'  => __( 'Bottom left', 'ml-chatbot-ai' ),
			);
		} elseif ( 'popup_visibility' === $key ) {
			$options = array(
				'all'     => __( 'Desktop and mobile', 'ml-chatbot-ai' ),
				'desktop' => __( 'Desktop only', 'ml-chatbot-ai' ),
				'mobile'  => __( 'Mobile only', 'ml-chatbot-ai' ),
			);
		}
		?>
		<select name="<?php echo esc_attr( $this->option_name . '[' . $key . ']' ); ?>">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $settings[ $key ], $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php if ( 'display_mode' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Use shortcode mode for page-embedded chat, or popup mode for a floating launcher on the whole site.', 'ml-chatbot-ai' ); ?></p>
			<p class="description"><?php echo esc_html__( 'Disclaimer: popup mode renders automatically in the footer and should not be relied on through shortcode placement.', 'ml-chatbot-ai' ); ?></p>
		<?php elseif ( 'popup_position' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Used only when display mode is set to floating popup.', 'ml-chatbot-ai' ); ?></p>
		<?php elseif ( 'popup_visibility' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Control whether the floating popup is visible on desktop, mobile, or both.', 'ml-chatbot-ai' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_number_field( array $args ) : void {
		$settings = APH_Plugin::get_settings();
		$key      = $args['key'];
		?>
		<input
			type="number"
			class="small-text"
			min="0"
			max="30"
			step="1"
			name="<?php echo esc_attr( $this->option_name . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>"
		/>
		<?php if ( 'popup_open_delay' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Delay in seconds before automatically opening the floating popup. Use 0 to keep it closed by default.', 'ml-chatbot-ai' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_api_key_field( array $args ) : void {
		$settings = APH_Plugin::get_settings();
		$key      = $args['key'];
		$has_key  = ! empty( $settings['api_key'] );
		?>
		<input
			type="password"
			class="regular-text"
			name="<?php echo esc_attr( $this->option_name . '[' . $key . ']' ); ?>"
			value=""
			placeholder="<?php echo $has_key ? esc_attr__( 'Saved. Enter a new key to replace it.', 'ml-chatbot-ai' ) : esc_attr__( 'sk-...', 'ml-chatbot-ai' ); ?>"
			autocomplete="off"
		/>
		<p class="description"><?php echo esc_html__( 'The key is stored server-side only and never exposed to visitors.', 'ml-chatbot-ai' ); ?></p>
		<?php
	}

	public function render_text_field( array $args ) : void {
		$settings = APH_Plugin::get_settings();
		$key      = $args['key'];
		?>
		<input
			type="text"
			class="regular-text"
			name="<?php echo esc_attr( $this->option_name . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>"
		/>
		<?php if ( 'workflow_id' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'OpenAI Workflow ID used to create ChatKit sessions for your hosted agent.', 'ml-chatbot-ai' ); ?></p>
		<?php elseif ( 'workflow_version' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Optional. Leave empty to use the production version, or enter a specific version such as 2.', 'ml-chatbot-ai' ); ?></p>
		<?php elseif ( 'brand_name' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Optional brand label shown in the chatbot header and popup launcher.', 'ml-chatbot-ai' ); ?></p>
			<p class="description"><?php echo esc_html__( 'Leave empty to use the chatbot title only.', 'ml-chatbot-ai' ); ?></p>
		<?php elseif ( 'title' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Displayed in the chatbot header and used as the fallback launcher label.', 'ml-chatbot-ai' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function render_logo_field() : void {
		$settings = APH_Plugin::get_settings();
		$logo_id  = absint( $settings['logo_id'] );
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
		?>
		<div class="ml-chatbot-ai-logo-control" data-ml-chatbot-logo-control>
			<input type="hidden" name="<?php echo esc_attr( $this->option_name . '[logo_id]' ); ?>" value="<?php echo esc_attr( (string) $logo_id ); ?>" data-ml-chatbot-logo-id />
			<div data-ml-chatbot-logo-preview>
				<?php if ( $logo_url ) : ?>
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:64px;height:auto;border-radius:12px;display:block;margin-bottom:10px;" />
				<?php endif; ?>
			</div>
			<button type="button" class="button" data-ml-chatbot-logo-upload><?php echo esc_html__( 'Choose logo', 'ml-chatbot-ai' ); ?></button>
			<button type="button" class="button button-link-delete" data-ml-chatbot-logo-remove><?php echo esc_html__( 'Remove logo', 'ml-chatbot-ai' ); ?></button>
			<p class="description"><?php echo esc_html__( 'Optional square logo used as brand identity in the chatbot header and popup button.', 'ml-chatbot-ai' ); ?></p>
			<p class="description"><?php echo esc_html__( 'Recommended: a square logo with transparent background for best visual results.', 'ml-chatbot-ai' ); ?></p>
		</div>
		<?php
	}

	public function render_color_field( array $args ) : void {
		$settings = APH_Plugin::get_settings();
		$key      = $args['key'];
		$defaults = APH_Plugin::get_default_settings();
		$value    = isset( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
		$default  = 'theme_color' === $key ? (string) $defaults['theme_color'] : $this->get_default_hover_color( $defaults );
		?>
		<input
			type="text"
			class="ml-chatbot-color-field"
			name="<?php echo esc_attr( $this->option_name . '[' . $key . ']' ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			data-default-color="<?php echo esc_attr( $default ); ?>"
		/>
		<?php if ( 'theme_color' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Main brand color. The chatbot gradients and accents are generated from this color.', 'ml-chatbot-ai' ); ?></p>
			<p class="description"><?php echo esc_html__( 'Disclaimer: some very light colors may reduce contrast and readability.', 'ml-chatbot-ai' ); ?></p>
		<?php elseif ( 'hover_color' === $key ) : ?>
			<p class="description"><?php echo esc_html__( 'Optional custom hover color for the popup launcher button.', 'ml-chatbot-ai' ); ?></p>
			<p class="description"><?php echo esc_html__( 'If left unchanged, the plugin uses an automatically generated darker shade of the theme color.', 'ml-chatbot-ai' ); ?></p>
		<?php endif; ?>
		<?php
	}

	private function get_default_hover_color( array $defaults ) : string {
		$theme_color = isset( $defaults['theme_color'] ) ? sanitize_hex_color( (string) $defaults['theme_color'] ) : '';

		if ( ! $theme_color ) {
			$theme_color = '#1d4ed8';
		}

		return $this->adjust_color_brightness( $theme_color, -24 );
	}

	private function adjust_color_brightness( string $hex, int $steps ) : string {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$steps  = max( -255, min( 255, $steps ) );
		$colors = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$color = hexdec( substr( $hex, $i * 2, 2 ) );
			$color = max( 0, min( 255, $color + $steps ) );
			$colors[] = str_pad( dechex( $color ), 2, '0', STR_PAD_LEFT );
		}

		return '#' . implode( '', $colors );
	}

}
