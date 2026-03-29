<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APH_API {
	private string $option_name;
	private string $guest_cookie_name = 'aph_chatkit_user';

	public function __construct( string $option_name ) {
		$this->option_name = $option_name;
	}

	public function register() : void {
		add_action( 'wp_ajax_ml_chatbot_start_session', array( $this, 'handle_session_request' ) );
		add_action( 'wp_ajax_nopriv_ml_chatbot_start_session', array( $this, 'handle_session_request' ) );
		add_action( 'wp_ajax_ml_chatbot_refresh_session', array( $this, 'handle_session_request' ) );
		add_action( 'wp_ajax_nopriv_ml_chatbot_refresh_session', array( $this, 'handle_session_request' ) );
	}

	public function handle_session_request() : void {
		if ( ! $this->is_valid_session_request() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Session request blocked.', 'ml-chatbot-ai' ),
				),
				403
			);
		}

		$settings = APH_Plugin::get_settings();

		if ( empty( $settings['enabled'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'The chatbot is currently unavailable.', 'ml-chatbot-ai' ) ),
				403
			);
		}

		if ( empty( $settings['api_key'] ) || empty( $settings['workflow_id'] ) ) {
			wp_send_json_error(
				array( 'message' => __( 'The chatbot is not configured yet.', 'ml-chatbot-ai' ) ),
				500
			);
		}

		$client_secret = $this->create_chatkit_session( $settings );

		if ( is_wp_error( $client_secret ) ) {
			wp_send_json_error(
				array(
					'message' => $client_secret->get_error_message(),
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'client_secret' => $client_secret,
			)
		);
	}

	private function create_chatkit_session( array $settings ) {
		$workflow = array(
			'id' => (string) $settings['workflow_id'],
		);

		if ( ! empty( $settings['workflow_version'] ) ) {
			$workflow['version'] = (string) $settings['workflow_version'];
		}

		$payload = array(
			'user'     => $this->get_chatkit_user(),
			'workflow' => $workflow,
		);

		$payload = apply_filters( 'aph_chatkit_session_payload', $payload, $settings );

		$request_args = array(
			'timeout' => (int) apply_filters( 'aph_chatkit_timeout', 25 ),
			'headers' => array(
				'Authorization' => 'Bearer ' . trim( (string) $settings['api_key'] ),
				'Content-Type'  => 'application/json',
				'OpenAI-Beta'   => 'chatkit_beta=v1',
			),
			'body'    => wp_json_encode( $payload ),
		);

		$request_args = apply_filters( 'aph_chatkit_request_args', $request_args, $payload, $settings );

		$request = wp_remote_post(
			'https://api.openai.com/v1/chatkit/sessions',
			$request_args
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$status = wp_remote_retrieve_response_code( $request );
		$body   = json_decode( (string) wp_remote_retrieve_body( $request ), true );

		if ( $status < 200 || $status >= 300 ) {
			$error_message = is_array( $body ) && isset( $body['error']['message'] )
				? sanitize_text_field( (string) $body['error']['message'] )
				: __( 'OpenAI request failed.', 'ml-chatbot-ai' );

			return new WP_Error(
				'ml_chatbot_chatkit_error',
				$error_message,
				array(
					'status' => $status,
					'body'   => $body,
				)
			);
		}

		$client_secret = $body['client_secret'] ?? '';

		if ( ! is_string( $client_secret ) || '' === $client_secret ) {
			return new WP_Error(
				'ml_chatbot_missing_client_secret',
				__( 'Empty client secret from OpenAI.', 'ml-chatbot-ai' ),
				array(
					'status' => $status,
					'body'   => $body,
				)
			);
		}

		return $client_secret;
	}

	private function is_valid_session_request() : bool {
		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['nonce'] ) ) : '';

		if ( '' !== $nonce && wp_verify_nonce( $nonce, 'ml_chatbot_nonce' ) ) {
			return true;
		}

		return $this->is_same_origin_request();
	}

	private function is_same_origin_request() : bool {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! is_string( $site_host ) || '' === $site_host ) {
			return false;
		}

		$origin  = isset( $_SERVER['HTTP_ORIGIN'] ) ? (string) wp_unslash( $_SERVER['HTTP_ORIGIN'] ) : '';
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';

		foreach ( array( $origin, $referer ) as $candidate ) {
			if ( '' === $candidate ) {
				continue;
			}

			$candidate_host = wp_parse_url( $candidate, PHP_URL_HOST );

			if ( is_string( $candidate_host ) && strtolower( $candidate_host ) === strtolower( $site_host ) ) {
				return true;
			}
		}

		return false;
	}

	private function get_chatkit_user() : string {
		if ( is_user_logged_in() ) {
			return (string) apply_filters( 'aph_chatkit_user_identifier', 'wp_user_' . get_current_user_id() );
		}

		if ( ! empty( $_COOKIE[ $this->guest_cookie_name ] ) ) {
			$cookie_value = sanitize_key( wp_unslash( (string) $_COOKIE[ $this->guest_cookie_name ] ) );

			if ( '' !== $cookie_value ) {
				return (string) apply_filters( 'aph_chatkit_user_identifier', $cookie_value );
			}
		}

		$guest_id = 'guest_' . strtolower( wp_generate_password( 24, false, false ) );
		$expires  = time() + MONTH_IN_SECONDS;

		setcookie( $this->guest_cookie_name, $guest_id, $expires, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE[ $this->guest_cookie_name ] = $guest_id;

		return (string) apply_filters( 'aph_chatkit_user_identifier', $guest_id );
	}
}
