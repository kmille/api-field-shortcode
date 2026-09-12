<?php
/**
 * Plugin Name:       API Field Shortcode
 * Plugin URI:        https://github.com/kmille/api-field-shortcode
 * Description:       Wordpress Shortcode that sends a GET request to an API and displays a single field from the JSON (object) response, e.g. [api_field url="https://example.com/api" field="registrations"].
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            kmille
 * Author URI:        https://github.com/kmille
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       api-field-shortcode
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AFS_VERSION', '1.0.0' );

class API_Field_Shortcode {

	const DEFAULT_CACHE_TTL = 900;
	const DEFAULT_TIMEOUT = 10;

	public function __construct() {
		add_shortcode( 'api_field', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Shortcode callback.
	 *
	 * Supported attributes:
	 * - url      (required) The endpoint to send the GET request to.
	 * - field    (required) Dot-notation path to the value inside the JSON object,
	 *                       e.g. "count" or "data.registrations.total".
	 * - error    (optional) Text shown if the request or extraction fails. Default: empty string.
	 * - cache    (optional) Cache duration in seconds. 0 disables caching. Default: 900.
	 * - timeout  (optional) HTTP timeout in seconds. Default: 10.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'url'     => '',
				'field'   => '',
				'error'   => '',
				'cache'   => self::DEFAULT_CACHE_TTL,
				'timeout' => self::DEFAULT_TIMEOUT,
			),
			$atts,
			'api_field'
		);

		$url         = trim( $atts['url'] );
		$field_path  = trim( $atts['field'] );
		$error_text  = (string) $atts['error'];
		$cache_ttl   = max( 0, (int) $atts['cache'] );
		$timeout     = max( 1, (int) $atts['timeout'] );

		// Validate required attributes.
		if ( '' === $url || ! wp_http_validate_url( $url ) ) {
			$this->log( 'Missing or invalid "url" attribute.', $atts );
			return esc_html( $error_text );
		}

		if ( '' === $field_path ) {
			$this->log( 'Missing "field" attribute.', $atts );
			return esc_html( $error_text );
		}

		$cache_key = 'afs_' . md5( $url . '|' . $field_path);

		if ( $cache_ttl > 0 ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				return esc_html( $cached );
			}
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => $timeout,
				'headers' => array(
					'Accept' => 'application/json',
					'User-Agent' => 'API Field Shortcode Wordpress Plugin',
				),
			)
		);

		// Network-level error (DNS failure, timeout, expired/invalid TLS certificate, ...).
		if ( is_wp_error( $response ) ) {
			$this->log( 'Request failed: ' . $response->get_error_message(), $atts );
			return esc_html( $error_text );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			$this->log( sprintf( 'Unexpected HTTP status %d.', $status_code ), $atts );
			return esc_html( $error_text );
		}

		$raw_response_body = wp_remote_retrieve_body( $response );
		$data               = json_decode( $raw_response_body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$this->log( 'Response body is not valid JSON.', $atts );
			return esc_html( $error_text );
		}

		if ( ! is_array( $data ) || wp_is_numeric_array( $data ) ) {
			// wp_is_numeric_array() being true means we got a JSON array/list, not an object.
			$this->log( 'Response is not a JSON object (dictionary).', $atts );
			return esc_html( $error_text );
		}

		$value = $this->extract_field( $data, $field_path );

		if ( null === $value ) {
			$this->log( sprintf( 'Field "%s" was not found in the response.', $field_path ), $atts );
			return esc_html( $error_text );
		}

		if ( is_array( $value ) ) {
			$this->log( sprintf( 'Field "%s" resolves to an array/object, not a scalar value.', $field_path ), $atts );
			return esc_html( $error_text );
		}

		$output = (string) $value;

		if ( $cache_ttl > 0 ) {
			set_transient( $cache_key, $output, $cache_ttl );
		}

		return esc_html( $output );
	}

	/**
	 * Extract a value from a nested associative array using dot notation.
	 * Example: extract_field( $data, 'data.registrations.total' ).
	 *
	 * @param array  $data       Decoded JSON object.
	 * @param string $field_path Dot-notation path.
	 * @return mixed|null The value, or null if the path does not exist.
	 */
	private function extract_field( array $data, string $field_path ) {
		$segments = explode( '.', $field_path );
		$current  = $data;

		foreach ( $segments as $segment ) {
			if ( ! is_array( $current ) || ! array_key_exists( $segment, $current ) ) {
				return null;
			}
			$current = $current[ $segment ];
		}

		return $current;
	}

	/**
	 * Log an error. Writes to the standard PHP/WordPress debug log (visible via
	 * WP_DEBUG_LOG or your hosting error log) whenever WP_DEBUG is enabled, and
	 * additionally always logs through error_log() so failures are never silent
	 * even on sites without WP_DEBUG turned on.
	 *
	 * @param string $message Human-readable error description.
	 * @param array  $atts    The shortcode attributes involved, for context.
	 */
	private function log( string $message, array $atts ) {
		$context = sprintf(
			'[API Field Shortcode] %s | url=%s field=%s',
			$message,
			isset( $atts['url'] ) ? $atts['url'] : '',
			isset( $atts['field'] ) ? $atts['field'] : ''
		);

		if ( function_exists( 'error_log' ) ) {
			error_log( $context );
		}
	}
}

new API_Field_Shortcode();
