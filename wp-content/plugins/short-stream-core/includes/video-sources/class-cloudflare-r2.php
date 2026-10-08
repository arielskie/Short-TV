<?php
/**
 * Cloudflare R2 S3-Compatible Object Storage & CDN Integration.
 *
 * Provides native S3 SigV4 direct uploads, presigned upload URLs,
 * streaming URL generation, and bucket connection health tests.
 *
 * @package Short_Stream_Core
 * @subpackage Video_Sources
 * @since 1.4.0
 */

namespace SHORT\Core\Video_Sources;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cloudflare_R2 {

	const REGION  = 'auto';
	const SERVICE = 's3';

	/**
	 * Check if Cloudflare R2 is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return ( '1' === (string) get_option( 'short_r2_enabled', '0' ) );
	}

	/**
	 * Get Cloudflare Account ID.
	 *
	 * @return string
	 */
	public static function get_account_id() {
		return trim( (string) get_option( 'short_r2_account_id', '' ) );
	}

	/**
	 * Get R2 Access Key ID.
	 *
	 * @return string
	 */
	public static function get_access_key_id() {
		return trim( (string) get_option( 'short_r2_access_key_id', '' ) );
	}

	/**
	 * Get R2 Secret Access Key.
	 *
	 * @return string
	 */
	public static function get_secret_access_key() {
		return trim( (string) get_option( 'short_r2_secret_access_key', '' ) );
	}

	/**
	 * Get R2 Bucket Name.
	 *
	 * @return string
	 */
	public static function get_bucket_name() {
		return trim( (string) get_option( 'short_r2_bucket_name', '' ) );
	}

	/**
	 * Get R2 Public Domain / Custom CDN URL.
	 *
	 * @return string
	 */
	public static function get_public_domain() {
		$domain = trim( (string) get_option( 'short_r2_public_domain', '' ) );
		if ( ! empty( $domain ) ) {
			$domain = rtrim( $domain, '/' );
			if ( ! preg_match( '#^https?://#i', $domain ) ) {
				$domain = 'https://' . $domain;
			}
		}
		return $domain;
	}

	/**
	 * Get default asset folder.
	 *
	 * @return string
	 */
	public static function get_folder() {
		$folder = trim( (string) get_option( 'short_r2_folder', 'short' ) );
		return ! empty( $folder ) ? trim( $folder, '/' ) : 'short';
	}

	/**
	 * Get S3 endpoint URL for Cloudflare R2.
	 *
	 * @return string
	 */
	public static function get_endpoint() {
		$account_id = self::get_account_id();
		if ( empty( $account_id ) ) {
			return '';
		}
		return sprintf( 'https://%s.r2.cloudflarestorage.com', $account_id );
	}

	/**
	 * Calculate AWS Signature V4 Signing Key.
	 *
	 * @param string $secret_key
	 * @param string $date_stamp YYYYMMDD
	 * @param string $region
	 * @param string $service
	 * @return string Binary HMAC hash
	 */
	private static function get_signature_key( $secret_key, $date_stamp, $region, $service ) {
		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $secret_key, true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', $service, $k_region, true );
		return hash_hmac( 'sha256', 'aws4_request', $k_service, true );
	}

	/**
	 * Generate SigV4 Authorization Headers for an S3 REST Request.
	 *
	 * @param string $method GET, PUT, HEAD, POST, DELETE
	 * @param string $uri Request path e.g. /bucket-name
	 * @param array  $query_params Associative array of query parameters
	 * @param array  $headers Associative array of headers
	 * @param string $payload_hash SHA256 of payload or 'UNSIGNED-PAYLOAD'
	 * @return array
	 */
	public static function sign_request( $method, $uri, $query_params = array(), $headers = array(), $payload_hash = '' ) {
		$account_id = self::get_account_id();
		$access_key = self::get_access_key_id();
		$secret_key = self::get_secret_access_key();

		if ( empty( $account_id ) || empty( $access_key ) || empty( $secret_key ) ) {
			return new \WP_Error( 'missing_credentials', 'Cloudflare R2 Account ID, Access Key ID, and Secret Access Key are required.' );
		}

		$host       = sprintf( '%s.r2.cloudflarestorage.com', $account_id );
		$time       = time();
		$amz_date   = gmdate( 'Ymd\THis\Z', $time );
		$date_stamp = gmdate( 'Ymd', $time );
		$region     = self::REGION;
		$service    = self::SERVICE;

		if ( empty( $payload_hash ) ) {
			$payload_hash = hash( 'sha256', '' );
		}

		$headers['host']                 = $host;
		$headers['x-amz-date']           = $amz_date;
		$headers['x-amz-content-sha256'] = $payload_hash;

		// Canonical headers
		ksort( $headers );
		$canonical_headers = '';
		$signed_headers_arr = array();
		foreach ( $headers as $k => $v ) {
			$k_lower = strtolower( trim( $k ) );
			$canonical_headers .= $k_lower . ':' . trim( (string) $v ) . "\n";
			$signed_headers_arr[] = $k_lower;
		}
		$signed_headers = implode( ';', $signed_headers_arr );

		// Canonical query string
		ksort( $query_params );
		$canonical_query_arr = array();
		foreach ( $query_params as $k => $v ) {
			$canonical_query_arr[] = rawurlencode( (string) $k ) . '=' . rawurlencode( (string) $v );
		}
		$canonical_query = implode( '&', $canonical_query_arr );

		// Canonical Request
		$canonical_request = strtoupper( $method ) . "\n"
			. ( empty( $uri ) ? '/' : $uri ) . "\n"
			. $canonical_query . "\n"
			. $canonical_headers . "\n"
			. $signed_headers . "\n"
			. $payload_hash;

		// String to Sign
		$credential_scope = $date_stamp . '/' . $region . '/' . $service . '/aws4_request';
		$string_to_sign   = "AWS4-HMAC-SHA256\n"
			. $amz_date . "\n"
			. $credential_scope . "\n"
			. hash( 'sha256', $canonical_request );

		// Signature
		$signing_key = self::get_signature_key( $secret_key, $date_stamp, $region, $service );
		$signature   = hash_hmac( 'sha256', $string_to_sign, $signing_key );

		$authorization = "AWS4-HMAC-SHA256 Credential={$access_key}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";

		$headers['Authorization'] = $authorization;
		return $headers;
	}

	/**
	 * Generate a Presigned PUT URL for client-side direct upload to R2.
	 *
	 * @param string $object_key Target file key in bucket (e.g. short/my-drama/ep1.mp4)
	 * @param string $content_type MIME type
	 * @param int    $expires_in Seconds until expiration (default 3600 = 1 hour)
	 * @return string|WP_Error
	 */
	/**
	 * Helper: Correctly rawurlencode each path segment in a URI for SigV4 and cURL.
	 *
	 * @param string $path
	 * @return string
	 */
	public static function encode_uri_path( $path ) {
		$segments = explode( '/', (string) $path );
		$encoded  = array();
		foreach ( $segments as $seg ) {
			if ( '' !== $seg ) {
				$encoded[] = rawurlencode( $seg );
			}
		}
		return '/' . implode( '/', $encoded );
	}

	/**
	 * Generate a Presigned PUT URL for client-side direct upload to R2.
	 *
	 * @param string $object_key Target file key in bucket (e.g. short/my-drama/ep1.mp4)
	 * @param string $content_type MIME type
	 * @param int    $expires_in Seconds until expiration (default 3600 = 1 hour)
	 * @return string|WP_Error
	 */
	public static function get_presigned_upload_url( $object_key, $content_type = 'video/mp4', $expires_in = 3600 ) {
		$account_id  = self::get_account_id();
		$access_key  = self::get_access_key_id();
		$secret_key  = self::get_secret_access_key();
		$bucket_name = self::get_bucket_name();

		if ( empty( $account_id ) || empty( $access_key ) || empty( $secret_key ) || empty( $bucket_name ) ) {
			return new \WP_Error( 'missing_config', 'Cloudflare R2 is not fully configured.' );
		}

		$host       = sprintf( '%s.r2.cloudflarestorage.com', $account_id );
		$time       = time();
		$amz_date   = gmdate( 'Ymd\THis\Z', $time );
		$date_stamp = gmdate( 'Ymd', $time );
		$region     = self::REGION;
		$service    = self::SERVICE;

		$object_key  = ltrim( $object_key, '/' );
		$raw_uri     = '/' . $bucket_name . '/' . $object_key;
		$encoded_uri = self::encode_uri_path( $raw_uri );

		$credential_scope = $date_stamp . '/' . $region . '/' . $service . '/aws4_request';

		$query_params = array(
			'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
			'X-Amz-Credential'    => $access_key . '/' . $credential_scope,
			'X-Amz-Date'          => $amz_date,
			'X-Amz-Expires'       => (string) $expires_in,
			'X-Amz-SignedHeaders' => 'host',
		);

		ksort( $query_params );
		$canonical_query_arr = array();
		foreach ( $query_params as $k => $v ) {
			$canonical_query_arr[] = rawurlencode( $k ) . '=' . rawurlencode( $v );
		}
		$canonical_query = implode( '&', $canonical_query_arr );

		$canonical_headers = "host:{$host}\n";
		$signed_headers    = 'host';
		$payload_hash      = 'UNSIGNED-PAYLOAD';

		$canonical_request = "PUT\n"
			. $encoded_uri . "\n"
			. $canonical_query . "\n"
			. $canonical_headers . "\n"
			. $signed_headers . "\n"
			. $payload_hash;

		$string_to_sign = "AWS4-HMAC-SHA256\n"
			. $amz_date . "\n"
			. $credential_scope . "\n"
			. hash( 'sha256', $canonical_request );

		$signing_key = self::get_signature_key( $secret_key, $date_stamp, $region, $service );
		$signature   = hash_hmac( 'sha256', $string_to_sign, $signing_key );

		return sprintf( 'https://%s%s?%s&X-Amz-Signature=%s', $host, $encoded_uri, $canonical_query, $signature );
	}

	/**
	 * Upload a local file from disk directly to Cloudflare R2 bucket.
	 *
	 * @param string $local_file_path Local filesystem path e.g. $_FILES['file']['tmp_name']
	 * @param string $object_key Destination key (e.g. short/drama-1/ep1.mp4)
	 * @param string $content_type MIME type
	 * @return array
	 */
	public static function upload_file( $local_file_path, $object_key, $content_type = 'video/mp4', $target_bucket = '' ) {
		$account_id  = self::get_account_id();
		$bucket_name = ! empty( $target_bucket ) ? trim( $target_bucket ) : self::get_bucket_name();

		if ( empty( $account_id ) || empty( $bucket_name ) ) {
			return array(
				'success' => false,
				'message' => 'Cloudflare R2 account or bucket name is missing.',
			);
		}

		if ( ! file_exists( $local_file_path ) || ! is_readable( $local_file_path ) ) {
			return array(
				'success' => false,
				'message' => 'Source file not found or not readable on server.',
			);
		}

		$file_size    = (int) filesize( $local_file_path );
		$object_key   = ltrim( $object_key, '/' );
		$raw_uri      = '/' . $bucket_name . '/' . $object_key;
		$encoded_uri  = self::encode_uri_path( $raw_uri );
		$payload_hash = hash_file( 'sha256', $local_file_path );

		$headers = array(
			'content-type'   => $content_type,
			'content-length' => (string) $file_size,
		);

		$signed_headers = self::sign_request( 'PUT', $encoded_uri, array(), $headers, $payload_hash );
		if ( is_wp_error( $signed_headers ) ) {
			return array(
				'success' => false,
				'message' => $signed_headers->get_error_message(),
			);
		}

		$endpoint = sprintf( 'https://%s.r2.cloudflarestorage.com%s', $account_id, $encoded_uri );

		if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init();
			$fp = fopen( $local_file_path, 'rb' );

			$curl_headers = array();
			foreach ( $signed_headers as $h_key => $h_val ) {
				$curl_headers[] = $h_key . ': ' . $h_val;
			}

			curl_setopt( $ch, CURLOPT_URL, $endpoint );
			curl_setopt( $ch, CURLOPT_PUT, true );
			curl_setopt( $ch, CURLOPT_INFILE, $fp );
			curl_setopt( $ch, CURLOPT_INFILESIZE, $file_size );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $curl_headers );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 600 );

			$body      = curl_exec( $ch );
			$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$curl_err  = curl_error( $ch );
			fclose( $fp );
			curl_close( $ch );

			if ( $curl_err ) {
				return array(
					'success' => false,
					'message' => 'cURL upload error: ' . $curl_err,
				);
			}

			if ( $http_code >= 200 && $http_code < 300 ) {
				return array(
					'success'    => true,
					'url'        => self::get_public_url( $object_key, $bucket_name ),
					'object_key' => $object_key,
					'bucket'     => $bucket_name,
				);
			}

			return array(
				'success' => false,
				'message' => 'Upload failed with HTTP ' . $http_code . ': ' . wp_strip_all_tags( (string) $body ),
			);
		}

		$content = file_get_contents( $local_file_path );
		return self::upload_data( $content, $object_key, $content_type, $bucket_name );
	}

	/**
	 * Upload file contents directly to Cloudflare R2 bucket.
	 *
	 * @param string $content Binary or string data to upload
	 * @param string $object_key Destination key (e.g. short/drama-1/ep1.mp4)
	 * @param string $content_type MIME type
	 * @param string $target_bucket Target bucket (optional)
	 * @return array
	 */
	public static function upload_data( $content, $object_key, $content_type = 'video/mp4', $target_bucket = '' ) {
		$account_id  = self::get_account_id();
		$bucket_name = ! empty( $target_bucket ) ? trim( $target_bucket ) : self::get_bucket_name();

		if ( empty( $account_id ) || empty( $bucket_name ) ) {
			return array(
				'success' => false,
				'message' => 'Cloudflare R2 account or bucket name is missing.',
			);
		}

		$object_key   = ltrim( $object_key, '/' );
		$raw_uri      = '/' . $bucket_name . '/' . $object_key;
		$encoded_uri  = self::encode_uri_path( $raw_uri );
		$payload_hash = hash( 'sha256', $content );

		$headers = array(
			'content-type'   => $content_type,
			'content-length' => (string) strlen( $content ),
		);

		$signed_headers = self::sign_request( 'PUT', $encoded_uri, array(), $headers, $payload_hash );
		if ( is_wp_error( $signed_headers ) ) {
			return array(
				'success' => false,
				'message' => $signed_headers->get_error_message(),
			);
		}

		$response = wp_remote_request( $endpoint, array(
			'method'    => 'PUT',
			'headers'   => $signed_headers,
			'body'      => $content,
			'timeout'   => 60,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return array(
				'success'    => true,
				'url'        => self::get_public_url( $object_key ),
				'object_key' => $object_key,
			);
		}

		$body = wp_remote_retrieve_body( $response );
		return array(
			'success' => false,
			'message' => 'Upload failed with HTTP ' . $code . ': ' . wp_strip_all_tags( $body ),
		);
	}

	public static function get_public_url( $object_key, $bucket = '' ) {
		$object_key    = ltrim( $object_key, '/' );
		$encoded_path  = ltrim( self::encode_uri_path( $object_key ), '/' );
		$public_domain = self::get_public_domain();

		if ( ! empty( $public_domain ) ) {
			return $public_domain . '/' . $encoded_path;
		}

		// Fallback to S3 endpoint if public domain is not set
		$account_id  = self::get_account_id();
		$bucket_name = ! empty( $bucket ) ? $bucket : self::get_bucket_name();
		return sprintf( 'https://%s.r2.cloudflarestorage.com/%s/%s', $account_id, $bucket_name, $encoded_path );
	}

	/**
	 * Test Cloudflare R2 connection & permissions.
	 *
	 * @return array
	 */
	public static function test_connection() {
		$account_id  = self::get_account_id();
		$access_key  = self::get_access_key_id();
		$secret_key  = self::get_secret_access_key();
		$bucket_name = self::get_bucket_name();
		$pub_domain  = self::get_public_domain();

		if ( empty( $account_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Missing Cloudflare Account ID.', 'short-stream-core' ),
			);
		}
		if ( empty( $access_key ) || empty( $secret_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Missing R2 Access Key ID or Secret Access Key.', 'short-stream-core' ),
			);
		}
		if ( empty( $bucket_name ) ) {
			return array(
				'success' => false,
				'message' => __( 'Missing R2 Bucket Name.', 'short-stream-core' ),
			);
		}

		// Perform HeadBucket / ListObjects S3 request
		$uri            = '/' . $bucket_name;
		$query_params   = array( 'max-keys' => '1' );
		$signed_headers = self::sign_request( 'GET', $uri, $query_params, array(), hash( 'sha256', '' ) );

		if ( is_wp_error( $signed_headers ) ) {
			return array(
				'success' => false,
				'message' => $signed_headers->get_error_message(),
			);
		}

		$endpoint = sprintf( 'https://%s.r2.cloudflarestorage.com%s?max-keys=1', $account_id, $uri );

		$response = wp_remote_get( $endpoint, array(
			'headers'   => $signed_headers,
			'timeout'   => 15,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Connection failed: ', 'short-stream-core' ) . $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code === 200 ) {
			$msg = sprintf( __( 'Connected successfully to R2 bucket "%s"!', 'short-stream-core' ), $bucket_name );
			if ( ! empty( $pub_domain ) ) {
				$msg .= ' ' . sprintf( __( 'Public CDN URL: %s', 'short-stream-core' ), $pub_domain );
			}
			return array(
				'success' => true,
				'message' => $msg,
				'bucket'  => $bucket_name,
			);
		}

		if ( $code === 403 ) {
			return array(
				'success' => false,
				'message' => __( 'Authentication failed (HTTP 403 Forbidden). Please check your R2 Access Key ID, Secret Access Key, and bucket permissions.', 'short-stream-core' ),
			);
		}

		if ( $code === 404 ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Bucket "%s" not found (HTTP 404). Please verify your bucket name and Cloudflare Account ID.', 'short-stream-core' ), $bucket_name ),
			);
		}

		return array(
			'success' => false,
			'message' => sprintf( __( 'R2 API returned HTTP %d: %s', 'short-stream-core' ), $code, wp_strip_all_tags( $body ) ),
		);
	}

	/**
	 * Retrieve all real buckets and folders from Cloudflare R2.
	 *
	 * @return array
	 */
	public static function get_folders() {
		$account_id  = self::get_account_id();
		$main_bucket = self::get_bucket_name();

		if ( empty( $account_id ) ) {
			return array(
				'success' => false,
				'message' => 'Cloudflare R2 is not configured.',
				'folders' => array(),
			);
		}

		$saved_extra = get_option( 'short_r2_known_buckets', array() );
		if ( ! is_array( $saved_extra ) ) {
			$saved_extra = array();
		}
		$candidate_buckets = array_values( array_unique( array_filter( array_merge( array( $main_bucket, 'short', 'movies' ), $saved_extra ) ) ) );

		$result_tree = array();

		foreach ( $candidate_buckets as $bucket ) {
			$uri          = '/' . $bucket;
			$query_params = array(
				'list-type' => '2',
				'max-keys'  => '1000',
			);
			$signed_headers = self::sign_request( 'GET', $uri, $query_params, array(), hash( 'sha256', '' ) );
			if ( is_wp_error( $signed_headers ) ) {
				continue;
			}

			$endpoint = sprintf( 'https://%s.r2.cloudflarestorage.com/%s?list-type=2&max-keys=1000', $account_id, $bucket );
			$response = wp_remote_get( $endpoint, array(
				'headers'   => $signed_headers,
				'timeout'   => 12,
				'sslverify' => false,
			) );

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				continue; // Bucket does not exist or access not permitted
			}

			$body = wp_remote_retrieve_body( $response );
			$xml  = simplexml_load_string( $body );
			$subs = array();

			if ( $xml && isset( $xml->Contents ) ) {
				foreach ( $xml->Contents as $item ) {
					$key   = trim( (string) $item->Key, '/' );
					$parts = explode( '/', $key );
					if ( count( $parts ) > 1 ) {
						$sub = trim( $parts[0] );
						if ( ! empty( $sub ) && stripos( $sub, '.keep' ) === false && ! in_array( $sub, $subs, true ) ) {
							$subs[] = $sub;
						}
					}
				}
			}

			if ( $xml && isset( $xml->CommonPrefixes ) ) {
				foreach ( $xml->CommonPrefixes as $cp ) {
					$sub = trim( (string) $cp->Prefix, '/' );
					if ( ! empty( $sub ) && ! in_array( $sub, $subs, true ) ) {
						$subs[] = $sub;
					}
				}
			}

			$result_tree[] = array(
				'name'      => $bucket,
				'id'        => $bucket,
				'is_bucket' => true,
				'subs'      => $subs,
			);
		}

		if ( ! empty( $result_tree ) ) {
			$found_names = wp_list_pluck( $result_tree, 'name' );
			update_option( 'short_r2_known_buckets', $found_names );
		}

		return array(
			'success' => true,
			'folders' => $result_tree,
		);
	}

	/**
	 * Create a real folder on Cloudflare R2 bucket (by uploading a .keep placeholder).
	 *
	 * @param string $folder_name
	 * @param string $parent
	 * @return array
	 */
	public static function create_folder( $folder_name, $parent = '' ) {
		$folder_name = trim( sanitize_file_name( $folder_name ), '/' );
		if ( empty( $folder_name ) ) {
			return array(
				'success' => false,
				'message' => 'Invalid folder name.',
			);
		}

		$known_buckets = get_option( 'short_r2_known_buckets', array( 'short', 'movies' ) );
		$target_bucket = self::get_bucket_name();
		$key           = $folder_name . '/.keep';

		if ( ! empty( $parent ) && in_array( $parent, $known_buckets, true ) ) {
			$target_bucket = $parent;
			$key           = $folder_name . '/.keep';
		} elseif ( ! empty( $parent ) ) {
			$key = trim( $parent, '/' ) . '/' . $folder_name . '/.keep';
		}

		$upload = self::upload_data( '', $key, 'text/plain', $target_bucket );
		if ( ! empty( $upload['success'] ) ) {
			return array(
				'success'     => true,
				'folder_name' => $folder_name,
				'folder_id'   => $folder_name,
				'bucket'      => $target_bucket,
				'message'     => 'Folder created successfully on Cloudflare R2.',
			);
		}

		return array(
			'success' => false,
			'message' => $upload['message'] ?? 'Failed to create folder on Cloudflare R2.',
		);
	}
}
