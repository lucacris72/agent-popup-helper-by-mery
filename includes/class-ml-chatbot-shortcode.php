<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APH_Shortcode {
	private string $option_name;

	public function __construct( string $option_name ) {
		$this->option_name = $option_name;
	}

	public function register() : void {
		add_shortcode( 'ml_chatbot', array( $this, 'render_shortcode' ) );
		add_action( 'wp_footer', array( $this, 'render_popup' ) );
	}

	public function render_shortcode() : string {
		$settings = APH_Plugin::get_settings();

		if ( empty( $settings['enabled'] ) ) {
			return '<div class="ml-chatbot-ai-unavailable">' . esc_html__( 'Assistant unavailable.', 'ml-chatbot-ai' ) . '</div>';
		}

		if ( 'popup' === $settings['display_mode'] ) {
			return '';
		}

		$this->enqueue_assets( $settings );

		return $this->get_chat_markup( $settings, 'embedded' );
	}

	public function render_popup() : void {
		$settings = APH_Plugin::get_settings();

		if ( empty( $settings['enabled'] ) || 'popup' !== $settings['display_mode'] ) {
			return;
		}

		$this->enqueue_assets( $settings );

		echo $this->get_chat_markup( $settings, 'popup' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private function enqueue_assets( array $settings ) : void {
		$is_active = ! empty( $settings['enabled'] ) && ! empty( $settings['api_key'] ) && ! empty( $settings['workflow_id'] );
		$css_path  = APH_PATH . 'assets/css/ml-chatbot.css';
		$js_path   = APH_PATH . 'assets/js/ml-chatbot.js';
		$css_ver   = file_exists( $css_path ) ? (string) filemtime( $css_path ) : APH_VERSION;
		$js_ver    = file_exists( $js_path ) ? (string) filemtime( $js_path ) : APH_VERSION;

		wp_enqueue_style(
			'ml-chatbot-ai',
			APH_URL . 'assets/css/ml-chatbot.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'ml-chatbot-ai-chatkit',
			'https://cdn.platform.openai.com/deployments/chatkit/chatkit.js',
			array(),
			null,
			true
		);

		wp_enqueue_script(
			'ml-chatbot-ai',
			APH_URL . 'assets/js/ml-chatbot.js',
			array( 'ml-chatbot-ai-chatkit' ),
			$js_ver,
			true
		);

		wp_localize_script(
			'ml-chatbot-ai',
			'mlChatbotConfig',
			apply_filters(
				'aph_frontend_config',
				array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'ml_chatbot_nonce' ),
				'error'     => __( 'Mi dispiace, al momento non riesco a connettere la chat. Riprova tra poco.', 'ml-chatbot-ai' ),
				'chatkit'   => array(
					'startAction'   => 'ml_chatbot_start_session',
					'refreshAction' => 'ml_chatbot_refresh_session',
					'popupOpenDelay' => isset( $settings['popup_open_delay'] ) ? (int) $settings['popup_open_delay'] : 0,
					'popupVisibility' => isset( $settings['popup_visibility'] ) ? (string) $settings['popup_visibility'] : 'all',
				),
				'status'    => array(
					'isActive' => $is_active,
					'online'   => __( 'Online', 'ml-chatbot-ai' ),
					'offline'  => __( 'Offline', 'ml-chatbot-ai' ),
				),
				),
				$settings
			)
		);
	}

	private function get_chat_markup( array $settings, string $variant ) : string {
		$logo_url      = $this->get_logo_url( $settings );
		$brand_name    = trim( (string) $settings['brand_name'] );
		$title         = (string) apply_filters( 'aph_chatbot_title', $settings['title'], $settings, $variant );
		$inline_style  = $this->build_theme_css_variables(
			(string) $settings['theme_color'],
			isset( $settings['hover_color'] ) ? (string) $settings['hover_color'] : ''
		);
		$root_classes  = 'ml-chatbot-ai ml-chatbot';
		$popup_classes = '';
		$is_active     = ! empty( $settings['enabled'] ) && ! empty( $settings['api_key'] ) && ! empty( $settings['workflow_id'] );

		if ( 'popup' === $variant ) {
			$root_classes .= ' ml-chatbot-ai--popup ml-chatbot--floating';
			$popup_classes = ' ml-chatbot-ai--popup-' . esc_attr( (string) $settings['popup_position'] );
			if ( empty( $settings['popup_show_label'] ) ) {
				$popup_classes .= ' ml-chatbot-ai--launcher-no-label';
			}
		} else {
			$root_classes .= ' ml-chatbot-ai--embedded ml-chatbot--embedded';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $root_classes . $popup_classes ); ?>" data-ml-chatbot data-ml-chatbot-variant="<?php echo esc_attr( $variant ); ?>" style="<?php echo esc_attr( $inline_style ); ?>">
			<?php if ( 'popup' === $variant ) : ?>
				<button type="button" class="ml-chatbot-ai__launcher" data-ml-chatbot-toggle aria-expanded="false">
					<?php if ( $logo_url ) : ?>
						<img class="ml-chatbot-ai__launcher-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="" />
					<?php else : ?>
						<span class="ml-chatbot-ai__launcher-badge"><?php echo esc_html( strtoupper( mb_substr( $brand_name ?: $title, 0, 1 ) ) ); ?></span>
					<?php endif; ?>
					<span class="ml-chatbot-ai__launcher-text"><?php echo esc_html( $brand_name ?: $title ); ?></span>
				</button>
			<?php endif; ?>
			<div class="ml-chatbot-ai__panel" data-ml-chatbot-panel<?php echo 'popup' === $variant ? ' hidden' : ''; ?>>
				<div class="ml-chatbot-ai__card ml-chatbot__surface">
					<div class="ml-chatbot-ai__frame ml-chatbot__container">
						<div class="ml-chatbot-ai__frame-header ml-chatbot__header">
							<div class="ml-chatbot-ai__header-main">
								<?php if ( $logo_url ) : ?>
									<img class="ml-chatbot-ai__logo" src="<?php echo esc_url( $logo_url ); ?>" alt="" />
								<?php else : ?>
									<div class="ml-chatbot-ai__logo ml-chatbot-ai__logo--fallback"><?php echo esc_html( strtoupper( mb_substr( $brand_name ?: $title, 0, 1 ) ) ); ?></div>
								<?php endif; ?>
								<div>
									<?php if ( $brand_name ) : ?>
										<p class="ml-chatbot-ai__brand"><?php echo esc_html( $brand_name ); ?></p>
									<?php endif; ?>
									<h3 class="ml-chatbot-ai__title"><?php echo esc_html( $title ); ?></h3>
									<p class="ml-chatbot-ai__subtitle">
										<span class="ml-chatbot-ai__status-dot<?php echo $is_active ? '' : ' is-offline'; ?>" aria-hidden="true"></span>
										<span><?php echo $is_active ? esc_html__( 'Online', 'ml-chatbot-ai' ) : esc_html__( 'Offline', 'ml-chatbot-ai' ); ?></span>
									</p>
								</div>
							</div>
							<?php if ( 'popup' === $variant ) : ?>
								<button type="button" class="ml-chatbot-ai__close" data-ml-chatbot-close aria-label="<?php echo esc_attr__( 'Close chat', 'ml-chatbot-ai' ); ?>">&times;</button>
							<?php endif; ?>
						</div>
						<div class="ml-chatbot-ai__chatkit-shell ml-chatbot__body">
							<div class="ml-chatbot-ai__chatkit-fallback" data-ml-chatkit-fallback><?php echo esc_html__( 'Loading chat...', 'ml-chatbot-ai' ); ?></div>
							<openai-chatkit class="ml-chatbot-ai__chatkit" data-ml-chatkit></openai-chatkit>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	private function get_logo_url( array $settings ) : string {
		$logo_id = isset( $settings['logo_id'] ) ? absint( $settings['logo_id'] ) : 0;

		if ( ! $logo_id ) {
			return '';
		}

		return (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' );
	}

	private function build_theme_css_variables( string $theme_color, string $hover_color = '' ) : string {
		$theme_color = sanitize_hex_color( $theme_color );
		$hover_color = sanitize_hex_color( $hover_color );

		if ( ! $theme_color ) {
			$theme_color = '#1d4ed8';
		}

		$primary_dark  = $this->adjust_color_brightness( $theme_color, -24 );
		$primary_hover = $hover_color ?: $primary_dark;
		$border        = $this->hex_to_rgba( $theme_color, 0.18 );

		return implode(
			';',
			array(
				'--ml-chatbot-primary:' . $theme_color,
				'--ml-chatbot-primary-dark:' . $primary_dark,
				'--ml-chatbot-primary-hover:' . $primary_hover,
				'--ml-chatbot-border:' . $border,
			)
		);
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

	private function hex_to_rgba( string $hex, float $alpha ) : string {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		$red   = hexdec( substr( $hex, 0, 2 ) );
		$green = hexdec( substr( $hex, 2, 2 ) );
		$blue  = hexdec( substr( $hex, 4, 2 ) );

		return sprintf( 'rgba(%d, %d, %d, %.2f)', $red, $green, $blue, $alpha );
	}
}
