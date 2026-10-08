<?php
namespace SHORT\Core\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domain Lock & License Manager for ShortTV (Lemon Squeezy API & Cryptographic Signatures)
 */
class License {
	const OPTION_KEY          = 'shorttv_license_data';
	const LEMONSQUEEZY_API_URL = 'https://api.lemonsqueezy.com/v1/licenses';

	/**
	 * Initialize license hooks
	 */
	public static function init() {
		add_action( 'wp_ajax_shorttv_activate_license', array( __CLASS__, 'ajax_activate_license' ) );
		add_action( 'wp_ajax_shorttv_deactivate_license', array( __CLASS__, 'ajax_deactivate_license' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_license_notice' ) );
		add_action( 'template_redirect', array( __CLASS__, 'enforce_frontend_lock' ), 0 );
		add_action( 'shorttv_license_heartbeat_check', array( __CLASS__, 'cron_verify_license_heartbeat' ) );

		if ( ! wp_next_scheduled( 'shorttv_license_heartbeat_check' ) ) {
			wp_schedule_event( time() + 86400, 'daily', 'shorttv_license_heartbeat_check' );
		}
	}

	/**
	 * Get current host / domain cleanly
	 */
	public static function get_current_domain() {
		$host = $_SERVER['HTTP_HOST'] ?? '';
		if ( empty( $host ) ) {
			$site_url = get_site_url();
			$host = wp_parse_url( $site_url, PHP_URL_HOST );
		}
		// Strip port if exists
		$parts = explode( ':', $host );
		return strtolower( trim( $parts[0] ) );
	}

	/**
	 * Check if current domain is a local or staging development environment
	 */
	public static function is_local_environment() {
		$domain = self::get_current_domain();
		if ( empty( $domain ) ) {
			return true;
		}

		$local_patterns = array(
			'localhost',
			'127.0.0.1',
			'::1',
			'.local',
			'.test',
			'.staging',
			'.dev',
			'.example',
			'ct.ws', // infinityfree / free hosting staging test
		);

		foreach ( $local_patterns as $pattern ) {
			if ( $domain === $pattern || ( strlen( $domain ) >= strlen( $pattern ) && substr( $domain, -strlen( $pattern ) ) === $pattern ) || false !== strpos( $domain, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get stored license data
	 */
	public static function get_license_data() {
		$defaults = array(
			'status'         => 'inactive',
			'license_key'    => '',
			'instance_id'    => '',
			'customer_name'  => '',
			'customer_email' => '',
			'order_id'       => '',
			'product_name'   => 'ShortTV WordPress Pro Theme',
			'activated_at'   => '',
			'expires_at'     => 'Lifetime',
			'signature'      => '',
			'domain'         => '',
		);

		$data = get_option( self::OPTION_KEY, array() );
		return wp_parse_args( $data, $defaults );
	}

	/**
	 * Generate tamper-proof HMAC signature for local database verification
	 */
	public static function generate_signature( $key, $domain, $status ) {
		$secret = defined( 'NONCE_SALT' ) ? NONCE_SALT : 'shorttv_secret_salt_2026';
		return hash_hmac( 'sha256', "{$key}|{$domain}|{$status}", $secret );
	}

	/**
	 * Check if the theme has a valid active license
	 */
	public static function is_valid() {
		if ( self::is_local_environment() ) {
			return true;
		}

		$data = self::get_license_data();
		if ( 'active' !== $data['status'] || empty( $data['license_key'] ) ) {
			return false;
		}

		$current_domain = self::get_current_domain();
		if ( ! empty( $data['domain'] ) && $data['domain'] !== $current_domain ) {
			return false; // Domain mismatch lock
		}

		// Verify tamper-proof signature
		$expected_sig = self::generate_signature( $data['license_key'], $data['domain'], $data['status'] );
		return hash_equals( $expected_sig, $data['signature'] );
	}

	/**
	 * Verify if a key is a valid Developer Master Key via mathematical checksum
	 * Format: SHORTTV-XXXX-XXXX-XXXX
	 * The key is verified mathematically using SHA-256 modulus checksum.
	 * No actual developer master key is stored in plain text anywhere in the code.
	 */
	public static function verify_master_key_checksum( $key ) {
		$key = strtoupper( trim( $key ) );
		if ( ! preg_match( '/^SHORTTV-([A-Z0-9]{4})-([A-Z0-9]{4})-([A-Z0-9]{4})$/', $key, $matches ) ) {
			return false;
		}

		$chunk1 = $matches[1];
		$chunk2 = $matches[2];
		$chunk3 = $matches[3];

		// Checksum formula: last 4 chars must match hex checksum of (chunk1 + chunk2 + secret seed)
		$seed = 'SHORTTV_ENGINE_CORE_SECURE_SEED_2026';
		$expected_chk = strtoupper( substr( hash( 'sha256', "{$chunk1}-{$chunk2}-{$seed}" ), 0, 4 ) );

		return hash_equals( $expected_chk, $chunk3 );
	}

	/**
	 * AJAX: Activate License with Lemon Squeezy API & Local Domain Lock
	 */
	public static function ajax_activate_license() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized permission.', 'short-stream-core' ) );
		}

		$license_key = sanitize_text_field( $_POST['license_key'] ?? '' );
		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'Please enter a valid license key.', 'short-stream-core' ) );
		}

		$domain = self::get_current_domain();
		$instance_name = $domain . ' (' . get_bloginfo( 'name' ) . ')';

		// 1. Check if it's a valid mathematical Developer Master Key first (No plain text key in code)
		if ( self::verify_master_key_checksum( $license_key ) ) {
			$license_data = array(
				'status'         => 'active',
				'license_key'    => $license_key,
				'instance_id'    => 'inst_dev_' . md5( $domain . $license_key ),
				'customer_name'  => 'Authorized Developer (' . esc_html( $domain ) . ')',
				'customer_email' => get_option( 'admin_email' ),
				'order_id'       => 'DEV-MASTER-PASS',
				'product_name'   => 'ShortTV WordPress Pro Theme (Developer Master Pass)',
				'activated_at'   => current_time( 'mysql' ),
				'expires_at'     => 'Lifetime',
				'domain'         => $domain,
				'signature'      => self::generate_signature( $license_key, $domain, 'active' ),
			);

			update_option( self::OPTION_KEY, $license_data );
			wp_send_json_success( array(
				'message'      => __( '👑 Developer Master Pass activated! Domain locked to: ', 'short-stream-core' ) . $domain,
				'license_data' => $license_data,
			) );
		}

		// 2. Online Activation via Lemon Squeezy Licenses API (Standard Customers)
		$response = wp_remote_post( self::LEMONSQUEEZY_API_URL . '/activate', array(
			'timeout' => 15,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/x-www-form-urlencoded',
			),
			'body'    => array(
				'license_key'   => $license_key,
				'instance_name' => $instance_name,
			),
		) );

		if ( ! is_wp_error( $response ) ) {
			$status_code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 === $status_code && ! empty( $body['activated'] ) && true === $body['activated'] ) {
				$meta = $body['meta'] ?? array();
				$license_key_data = $body['license_key'] ?? array();

				$license_data = array(
					'status'         => 'active',
					'license_key'    => $license_key,
					'instance_id'    => $body['instance']['id'] ?? '',
					'customer_name'  => $meta['customer_name'] ?? ( $license_key_data['user_name'] ?? 'ShortTV Licensee' ),
					'customer_email' => $meta['customer_email'] ?? ( $license_key_data['user_email'] ?? '' ),
					'order_id'       => $license_key_data['order_id'] ?? '',
					'product_name'   => $license_key_data['product_name'] ?? 'ShortTV WordPress Pro Theme',
					'activated_at'   => current_time( 'mysql' ),
					'expires_at'     => $license_key_data['expires_at'] ? date( 'Y-m-d', strtotime( $license_key_data['expires_at'] ) ) : 'Lifetime',
					'domain'         => $domain,
					'signature'      => self::generate_signature( $license_key, $domain, 'active' ),
				);

				update_option( self::OPTION_KEY, $license_data );
				wp_send_json_success( array(
					'message'      => __( '🎉 License activated successfully! Domain locked to: ', 'short-stream-core' ) . $domain,
					'license_data' => $license_data,
				) );
			} elseif ( ! empty( $body['error'] ) ) {
				wp_send_json_error( $body['error'] );
			}
		}

		wp_send_json_error( __( 'Invalid license key. Please check your Lemon Squeezy receipt or purchase an authorized copy.', 'short-stream-core' ) );
	}

	/**
	 * Daily cron heartbeat to verify live license status with Lemon Squeezy
	 */
	public static function cron_verify_license_heartbeat() {
		$data = self::get_license_data();
		if ( 'active' !== $data['status'] || empty( $data['license_key'] ) || empty( $data['instance_id'] ) ) {
			return;
		}

		// Skip developer master keys
		if ( self::verify_master_key_checksum( $data['license_key'] ) ) {
			return;
		}

		// Re-validate against Lemon Squeezy API
		$response = wp_remote_post( self::LEMONSQUEEZY_API_URL . '/validate', array(
			'timeout' => 15,
			'body'    => array(
				'license_key' => $data['license_key'],
				'instance_id' => $data['instance_id'],
			),
		) );

		if ( ! is_wp_error( $response ) ) {
			$status_code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $status_code || empty( $body['valid'] ) || false === $body['valid'] ) {
				// License was refunded, revoked, or expired -> deactivate
				delete_option( self::OPTION_KEY );
			}
		}
	}

	/**
	 * AJAX: Deactivate License (Frees up slot in Lemon Squeezy)
	 */
	public static function ajax_deactivate_license() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized permission.', 'short-stream-core' ) );
		}

		$data = self::get_license_data();
		if ( ! empty( $data['license_key'] ) && ! empty( $data['instance_id'] ) ) {
			wp_remote_post( self::LEMONSQUEEZY_API_URL . '/deactivate', array(
				'timeout' => 15,
				'body'    => array(
					'license_key' => $data['license_key'],
					'instance_id' => $data['instance_id'],
				),
			) );
		}

		delete_option( self::OPTION_KEY );
		wp_send_json_success( array(
			'message' => __( 'License deactivated successfully. You can now activate it on another domain.', 'short-stream-core' ),
		) );
	}

	/**
	 * Admin Banner Notice if unlicensed
	 */
	public static function admin_license_notice() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'short' ) === false ) {
			return;
		}

		if ( ! self::is_valid() ) {
			$license_url = admin_url( 'admin.php?page=short-settings&tab=license' );
			?>
			<div class="notice notice-warning is-dismissible" style="border-left-color:#f59e0b; padding:12px 18px;">
				<p style="font-size:13.5px; margin:0; display:flex; align-items:center; gap:8px;">
					<span style="font-size:18px;">🔐</span>
					<span><strong>ShortTV Domain Lock:</strong> This domain (<code><?php echo esc_html( self::get_current_domain() ); ?></code>) is not activated. Features require an active license.</span>
					<a href="<?php echo esc_url( $license_url ); ?>" class="button button-primary" style="margin-left:auto; background:#0284c7; border-color:#0284c7;">
						<?php _e( 'Activate License', 'short-stream-core' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Block frontend access and render license lock screen if unlicensed
	 */
	public static function enforce_frontend_lock() {
		// Allow WordPress login/admin and REST/AJAX endpoints
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// If license is valid, allow normal browsing
		if ( self::is_valid() ) {
			return;
		}

		$current_domain = self::get_current_domain();
		$admin_url      = admin_url( 'admin.php?page=short-settings&tab=license' );

		status_header( 403 );
		nocache_headers();
		?>
		<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php _e( 'ShortTV License Required - Domain Locked', 'short-stream-core' ); ?></title>
			<style>
				* { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif; }
				body {
					background: #090d16;
					color: #f8fafc;
					min-height: 100vh;
					display: flex;
					align-items: center;
					justify-content: center;
					padding: 24px;
				}
				.lock-container {
					background: #131b2e;
					border: 1px solid #1e293b;
					border-top: 4px solid #ef4444;
					border-radius: 16px;
					max-width: 540px;
					width: 100%;
					padding: 40px 36px;
					text-align: center;
					box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
				}
				.lock-icon {
					width: 72px;
					height: 72px;
					background: rgba(239, 68, 68, 0.12);
					color: #ef4444;
					border-radius: 50%;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					font-size: 32px;
					margin-bottom: 20px;
					border: 1px solid rgba(239, 68, 68, 0.25);
				}
				h1 {
					font-size: 24px;
					font-weight: 800;
					margin-bottom: 12px;
					color: #ffffff;
					letter-spacing: -0.5px;
				}
				p {
					font-size: 14.5px;
					line-height: 1.6;
					color: #94a3b8;
					margin-bottom: 24px;
				}
				.domain-tag {
					background: #0b1120;
					border: 1px solid #334155;
					padding: 10px 18px;
					border-radius: 8px;
					font-family: Consolas, Monaco, monospace;
					font-size: 14px;
					color: #38bdf8;
					display: inline-block;
					margin-bottom: 24px;
				}
				.btn-activate {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					gap: 8px;
					background: #0284c7;
					color: #ffffff;
					text-decoration: none;
					font-weight: 700;
					font-size: 15px;
					padding: 14px 28px;
					border-radius: 10px;
					transition: all 0.2s ease;
					box-shadow: 0 4px 14px rgba(2, 132, 199, 0.4);
				}
				.btn-activate:hover {
					background: #0369a1;
					transform: translateY(-2px);
				}
				.footer-note {
					margin-top: 24px;
					font-size: 12px;
					color: #64748b;
				}
			</style>
		</head>
		<body>
			<div class="lock-container">
				<div class="lock-icon">🔒</div>
				<h1><?php _e( 'Theme License Required', 'short-stream-core' ); ?></h1>
				<p>
					<?php _e( 'This website is running the <strong>ShortTV Pro Theme</strong>, but it has not yet been activated with a valid license key for this server domain.', 'short-stream-core' ); ?>
				</p>
				<div>
					<span class="domain-tag"><?php echo esc_html( $current_domain ); ?></span>
				</div>
				<a href="<?php echo esc_url( $admin_url ); ?>" class="btn-activate">
					<span>🔑 <?php _e( 'Activate License in Admin', 'short-stream-core' ); ?></span>
				</a>
				<div class="footer-note">
					<?php _e( 'Only authorized administrators can log in to register license keys.', 'short-stream-core' ); ?>
				</div>
			</div>
		</body>
		</html>
		<?php
		exit;
	}
}
