<?php
namespace SHORT\Core\Video_Sources;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gumlet Video CDN Integration for Short TV & Dramas
 *
 * Fetches direct HLS/MP4 playback streams from the Gumlet Video API
 * for uploaded short drama episodes.
 *
 * Settings (stored as individual WP options):
 *   short_gumlet_enabled         — '1' | '0'
 *   short_gumlet_workspace_id    — Gumlet workspace / source ID
 *   short_gumlet_folder_id       — Gumlet folder / parent ID
 *   short_gumlet_api_secret      — Gumlet API secret key
 *   short_gumlet_drm_enabled     — '1' | '0'  (request signed URLs)
 *   short_gumlet_cache_ttl       — integer (minutes, default 60)
 *
 * @since 1.3.0
 */
class Gumlet {

	/** Gumlet public API base. */
	const API_BASE = 'https://api.gumlet.com/v1';

	/** Cache key prefix. */
	const CACHE_PREFIX = 'shorttv_gumlet_';

	// ── Public API ──────────────────────────────────────────────────────────

	/**
	 * Check whether the Gumlet integration is enabled and configured.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled   = get_option( 'short_gumlet_enabled', '0' );
		$secret    = get_option( 'short_gumlet_api_secret', '' );
		return ( '1' === $enabled && ! empty( $secret ) );
	}

	/**
	 * Fetch playback streams from Gumlet for a given Drama ID and Episode.
	 *
	 * @param int|string $drama_id   Drama post ID.
	 * @param int        $episode    Episode number.
	 * @return array { 'sources' => array[], 'subtitles' => array[] }
	 */
	public static function get_streams( $drama_id, $media_type = 'drama', $season = 1, $episode = 1 ) {
		if ( ! self::is_enabled() ) {
			return array( 'sources' => array(), 'subtitles' => array() );
		}

		$drama_id = (int) $drama_id;
		$season   = (int) $season;
		$episode  = (int) $episode;

		// Build cache key
		$cache_key = self::CACHE_PREFIX . $media_type . '_' . $drama_id;
		if ( $season > 0 && $episode > 0 ) {
			$cache_key .= '_s' . $season . '_e' . $episode;
		}

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Resolve asset ID
		$asset_id = self::resolve_asset_id( $drama_id, $media_type, $season, $episode );
		if ( empty( $asset_id ) ) {
			return array( 'sources' => array(), 'subtitles' => array() );
		}

		// Fetch asset detail from Gumlet API
		$result = self::fetch_asset( $asset_id );

		// Cache the result
		$ttl = (int) get_option( 'short_gumlet_cache_ttl', 60 ) * MINUTE_IN_SECONDS;
		set_transient( $cache_key, $result, $ttl );

		return $result;
	}

	/**
	 * Test the Gumlet API connection using the stored credentials.
	 *
	 * @return array { 'success' => bool, 'message' => string, 'plan' => string }
	 */
	public static function test_connection() {
		$secret = self::get_api_secret();
		if ( empty( $secret ) ) {
			return array(
				'success' => false,
				'message' => 'Gumlet API Secret is not configured.',
				'plan'    => '',
			);
		}

		$workspace_id = self::get_workspace_id();
		$headers      = self::build_headers();

		// 1. First test the API key against the /video/sources endpoint
		$response = wp_remote_get( self::API_BASE . '/video/sources', array(
			'timeout' => 10,
			'headers' => $headers,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => 'Connection failed: ' . $response->get_error_message(),
				'plan'    => '',
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status >= 200 && $status < 300 ) {
			$source_count = is_array( $body ) ? count( $body ) : 0;
			$workspace_matched = false;
			$matched_name = '';

			if ( is_array( $body ) && ! empty( $workspace_id ) ) {
				foreach ( $body as $src ) {
					$sid = $src['id'] ?? $src['source_id'] ?? $src['collection_id'] ?? '';
					if ( $sid === $workspace_id ) {
						$workspace_matched = true;
						$matched_name = $src['name'] ?? $sid;
						break;
					}
				}
			}

			if ( ! empty( $workspace_id ) && $workspace_matched ) {
				return array(
					'success' => true,
					'message' => 'Connected successfully to Gumlet! Found source/workspace: ' . ( $matched_name ?: $workspace_id ),
					'plan'    => 'Active (' . $source_count . ' sources)',
				);
			} elseif ( ! empty( $workspace_id ) ) {
				return array(
					'success' => true,
					'message' => 'Connected to Gumlet API! (Workspace ID ' . esc_html( $workspace_id ) . ' configured).',
					'plan'    => 'Active (' . $source_count . ' sources)',
				);
			}

			return array(
				'success' => true,
				'message' => 'Connected to Gumlet API successfully! Found ' . $source_count . ' source(s).',
				'plan'    => 'Active',
			);
		}

		// 2. Fallback check on /video/collections if sources returned 404
		if ( 404 === $status ) {
			$fallback = wp_remote_get( self::API_BASE . '/video/collections', array(
				'timeout' => 10,
				'headers' => $headers,
			) );

			if ( ! is_wp_error( $fallback ) && wp_remote_retrieve_response_code( $fallback ) >= 200 && wp_remote_retrieve_response_code( $fallback ) < 300 ) {
				return array(
					'success' => true,
					'message' => 'Connected to Gumlet API successfully!',
					'plan'    => 'Active',
				);
			}
		}

		$err_msg = $body['message'] ?? $body['error'] ?? ( 'HTTP ' . $status . ' error' );
		return array(
			'success' => false,
			'message' => 'Gumlet API returned HTTP ' . $status . ': ' . $err_msg,
			'plan'    => '',
		);
	}

	// ── Private Helpers ─────────────────────────────────────────────────────

	/**
	 * Resolve the Gumlet asset ID from several lookup sources.
	 *
	 * @param int    $tmdb_id    TMDB ID.
	 * @param string $media_type 'movie' | 'tv'.
	 * @param int    $season     Season number.
	 * @param int    $episode    Episode number.
	 * @return string Gumlet asset ID, or empty string on failure.
	 */
	private static function resolve_asset_id( $drama_id, $media_type, $season, $episode ) {
		// 1. Check manual WP option map set via admin UI
		$opt_key = 'short_gumlet_map_' . $drama_id . '_' . $media_type;
		if ( $season > 0 && $episode > 0 ) {
			$ep_opt  = get_option( $opt_key . '_s' . $season . '_e' . $episode, '' );
			if ( ! empty( $ep_opt ) ) {
				return sanitize_text_field( $ep_opt );
			}
		}
		$mapped = get_option( $opt_key, '' );
		if ( ! empty( $mapped ) ) {
			return sanitize_text_field( $mapped );
		}

		// 2. Search CPT post-meta _short_gumlet_asset_id on any matching post
		$posts = get_posts( array(
			'post_type'  => array( 'short_video', 'short_video_source' ),
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_short_video_id',
					'value'   => $drama_id,
					'compare' => '=',
				),
				array(
					'key'     => '_short_gumlet_asset_id',
					'compare' => 'EXISTS',
				),
			),
			'posts_per_page' => 1,
			'fields'         => 'ids',
		) );
		if ( ! empty( $posts ) ) {
			$aid = get_post_meta( $posts[0], '_short_gumlet_asset_id', true );
			if ( ! empty( $aid ) ) {
				return sanitize_text_field( $aid );
			}
		}

		// 3. Search Gumlet workspace by tag via API
		$workspace_id = self::get_workspace_id();
		if ( ! empty( $workspace_id ) ) {
			$tag      = 'video:' . $drama_id;
			$api_url  = self::API_BASE . '/video/assets?collection_id=' . rawurlencode( $workspace_id ) . '&tag=' . rawurlencode( $tag ) . '&limit=5';
			$response = wp_remote_get( $api_url, array(
				'timeout' => 8,
				'headers' => self::build_headers(),
			) );

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				$assets = $body['data'] ?? $body['assets'] ?? array();
				if ( ! empty( $assets ) ) {
					// For TV, prefer one tagged season:X-episode:Y
					if ( 'tv' === $media_type && $season > 0 && $episode > 0 ) {
						$ep_tag = 'season:' . $season . '-episode:' . $episode;
						foreach ( $assets as $asset ) {
							$tags = $asset['tags'] ?? array();
							if ( in_array( $ep_tag, $tags, true ) ) {
								return $asset['id'] ?? $asset['asset_id'] ?? '';
							}
						}
					}
					// Fallback: first matched asset
					$first = reset( $assets );
					return $first['id'] ?? $first['asset_id'] ?? '';
				}
			}
		}

		return '';
	}

	/**
	 * Fetch and normalize a single Gumlet asset's playback data.
	 *
	 * @param string $asset_id Gumlet asset ID.
	 * @return array Normalized { 'sources' => array[], 'subtitles' => array[] }
	 */
	private static function fetch_asset( $asset_id ) {
		$response = wp_remote_get( self::API_BASE . '/video/assets/' . rawurlencode( $asset_id ), array(
			'timeout' => 15,
			'headers' => self::build_headers(),
		) );

		if ( is_wp_error( $response ) ) {
			error_log( '[Short Gumlet] Asset fetch failed: ' . $response->get_error_message() );
			return array( 'sources' => array(), 'subtitles' => array() );
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			error_log( '[Short Gumlet] Asset fetch returned HTTP ' . $status . ' for asset ' . $asset_id );
			return array( 'sources' => array(), 'subtitles' => array() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body ) || ! is_array( $body ) ) {
			return array( 'sources' => array(), 'subtitles' => array() );
		}

		return self::normalize_asset( $body );
	}

	/**
	 * Normalize a Gumlet asset object into the Short internal source format.
	 *
	 * @param array $asset Raw asset JSON from Gumlet API.
	 * @return array { 'sources' => array[], 'subtitles' => array[] }
	 */
	private static function normalize_asset( $asset ) {
		$sources   = array();
		$subtitles = array();

		$asset_id   = $asset['id'] ?? $asset['asset_id'] ?? '';
		$asset_name = $asset['name'] ?? $asset['title'] ?? 'Gumlet Stream';
		$status     = strtolower( $asset['status'] ?? '' );

		// Only serve assets that are fully processed
		if ( ! empty( $status ) && ! in_array( $status, array( 'ready', 'completed', 'done' ), true ) ) {
			error_log( '[Short Gumlet] Asset ' . $asset_id . ' not ready (status: ' . $status . ')' );
			return array( 'sources' => array(), 'subtitles' => array() );
		}

		// ── HLS Master Playlist (preferred) ────────────────────────────────
		$hls_url = $asset['output']['playback_url']
			?? $asset['output']['hls_url']
			?? $asset['stream_url']
			?? $asset['playback_url']
			?? $asset['hls_url']
			?? '';

		// Gumlet sometimes provides a playback_url per output rendition
		if ( empty( $hls_url ) && ! empty( $asset['outputs'] ) && is_array( $asset['outputs'] ) ) {
			foreach ( $asset['outputs'] as $out ) {
				$fmt = strtolower( $out['format'] ?? '' );
				if ( 'hls' === $fmt && ! empty( $out['url'] ) ) {
					$hls_url = $out['url'];
					break;
				}
			}
		}

		if ( ! empty( $hls_url ) ) {
			// Optionally generate a signed (DRM) URL
			if ( '1' === get_option( 'short_gumlet_drm_enabled', '1' ) ) {
				$hls_url = self::sign_url( $hls_url );
			}

			$sources[] = array(
				'name'       => 'Gumlet (HLS · Auto Quality)',
				'type'       => 'hls',
				'url'        => $hls_url,
				'resolution' => 'Auto',
				'language'   => 'Original',
				'subtitles'  => '',
				'is_gumlet'  => true,
				'headers'    => array(),
				'locked'     => false,
				'provider'   => 'Gumlet',
			);
		}

		// ── MP4 Renditions (per-quality fallback) ──────────────────────────
		$mp4_renditions = $asset['output']['mp4_renditions']
			?? $asset['mp4_renditions']
			?? array();

		if ( empty( $mp4_renditions ) && ! empty( $asset['outputs'] ) && is_array( $asset['outputs'] ) ) {
			foreach ( $asset['outputs'] as $out ) {
				$fmt = strtolower( $out['format'] ?? '' );
				if ( 'mp4' === $fmt && ! empty( $out['url'] ) ) {
					$mp4_renditions[] = $out;
				}
			}
		}

		foreach ( $mp4_renditions as $rend ) {
			$mp4_url  = $rend['url'] ?? $rend['mp4_url'] ?? '';
			$quality  = $rend['resolution'] ?? $rend['quality'] ?? $rend['height'] ?? 'Auto';
			if ( is_numeric( $quality ) ) {
				$quality = $quality . 'p';
			}
			if ( empty( $mp4_url ) ) {
				continue;
			}

			if ( '1' === get_option( 'short_gumlet_drm_enabled', '1' ) ) {
				$mp4_url = self::sign_url( $mp4_url );
			}

			$sources[] = array(
				'name'       => 'Gumlet MP4 (' . $quality . ')',
				'type'       => 'mp4',
				'url'        => $mp4_url,
				'resolution' => $quality,
				'language'   => 'Original',
				'subtitles'  => '',
				'is_gumlet'  => true,
				'headers'    => array(),
				'locked'     => false,
				'provider'   => 'Gumlet',
			);
		}

		// ── Subtitles / Captions ───────────────────────────────────────────
		$raw_subs = $asset['subtitles'] ?? $asset['captions'] ?? $asset['output']['subtitles'] ?? array();
		foreach ( $raw_subs as $sub ) {
			$sub_url = $sub['url'] ?? $sub['file'] ?? '';
			if ( empty( $sub_url ) ) {
				continue;
			}
			$lang = $sub['language'] ?? $sub['lang'] ?? $sub['label'] ?? 'English';
			$subtitles[] = array(
				'url'     => $sub_url,
				'label'   => $lang,
				'srclang' => strtolower( substr( $lang, 0, 2 ) ),
				'default' => strtolower( $lang ) === 'english' || strtolower( substr( $lang, 0, 2 ) ) === 'en',
			);
		}

		return array(
			'sources'   => $sources,
			'subtitles' => $subtitles,
		);
	}

	/**
	 * Generate a time-limited signed URL for Gumlet DRM/signed-URL delivery.
	 *
	 * Gumlet token signing uses HMAC-SHA256 on the path + expiry timestamp.
	 * See: https://docs.gumlet.com/reference/signed-urls
	 *
	 * @param string $url Original Gumlet CDN URL.
	 * @return string Signed URL.
	 */
	private static function sign_url( $url ) {
		$secret  = self::get_api_secret();
		if ( empty( $secret ) ) {
			return $url;
		}

		$expiry    = time() + 3600; // 1 hour
		$parsed    = wp_parse_url( $url );
		$path      = $parsed['path'] ?? '';
		$signature = hash_hmac( 'sha256', $path . $expiry, $secret );

		return add_query_arg( array(
			'token'   => $signature,
			'expiry'  => $expiry,
		), $url );
	}

	/**
	 * Build default HTTP request headers for Gumlet API calls.
	 *
	 * @return array
	 */
	private static function build_headers() {
		return array(
			'Authorization' => 'Bearer ' . self::get_api_secret(),
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
		);
	}

	/**
	 * Create a direct upload asset URL via Gumlet Video API
	 * POST /v1/video/assets/upload
	 *
	 * @param array $args Format, collection_id, tags, etc.
	 * @return array Response containing asset_id, upload_url, etc.
	 */
	public static function create_direct_upload_asset( $args = array() ) {
		$secret = self::get_api_secret();
		if ( empty( $secret ) ) {
			return array(
				'success' => false,
				'message' => 'Gumlet API Secret is not configured.',
			);
		}

		$workspace_id = self::get_workspace_id();
		$folder_id    = ! empty( $args['parent_id'] ) ? $args['parent_id'] : ( ! empty( $args['folder_id'] ) ? $args['folder_id'] : self::get_folder_id() );

		$payload = array(
			'format' => 'hls',
		);
		if ( ! empty( $workspace_id ) ) {
			$payload['source_id']     = $workspace_id;
			$payload['workspace_id']  = $workspace_id;
			$payload['collection_id'] = $workspace_id;
		}
		if ( ! empty( $folder_id ) ) {
			$payload['parent_id'] = $folder_id;
			$payload['folder_id'] = $folder_id;
		}
		if ( ! empty( $args['tags'] ) && is_array( $args['tags'] ) ) {
			$payload['tags'] = $args['tags'];
		}
		if ( ! empty( $args['title'] ) ) {
			$payload['title'] = sanitize_text_field( $args['title'] );
		}

		$response = wp_remote_post( self::API_BASE . '/video/assets/upload', array(
			'timeout'   => 15,
			'headers'   => self::build_headers(),
			'sslverify' => false,
			'body'      => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body ) ) {
			return array(
				'success'    => true,
				'asset_id'   => $body['asset_id'] ?? $body['id'] ?? '',
				'upload_url' => $body['upload_url'] ?? $body['url'] ?? '',
				'data'       => $body,
			);
		}

		$err_msg = $body['error']['message'] ?? $body['message'] ?? ( 'Gumlet API returned HTTP ' . $code );
		return array(
			'success' => false,
			'message' => $err_msg,
		);
	}

	/**
	 * Get asset status from Gumlet API
	 *
	 * @param string $asset_id
	 * @return array
	 */
	public static function get_asset_status( $asset_id ) {
		$secret = self::get_api_secret();
		if ( empty( $secret ) || empty( $asset_id ) ) {
			return array(
				'success' => false,
				'message' => 'Missing credentials or asset ID.',
			);
		}

		$response = wp_remote_get( self::API_BASE . '/video/assets/' . rawurlencode( $asset_id ), array(
			'timeout' => 12,
			'headers' => self::build_headers(),
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body ) ) {
			$normalized = self::normalize_asset( $body );
			return array(
				'success'   => true,
				'status'    => $body['status'] ?? 'ready',
				'sources'   => $normalized['sources'] ?? array(),
				'subtitles' => $normalized['subtitles'] ?? array(),
				'raw'       => $body,
			);
		}

		return array(
			'success' => false,
			'message' => $body['message'] ?? ( 'Gumlet API returned HTTP ' . $code ),
		);
	}

	/**
	 * Get the configured Gumlet API secret.
	 *
	 * @return string
	 */
	public static function get_api_secret() {
		return get_option( 'short_gumlet_api_secret', '' );
	}

	/**
	 * Get the configured Gumlet workspace / collection ID.
	 *
	 * @return string
	 */
	public static function get_workspace_id() {
		return get_option( 'short_gumlet_workspace_id', '' );
	}

	/**
	 * Get the configured Gumlet folder / parent ID.
	 *
	 * @return string
	 */
	public static function get_folder_id() {
		return get_option( 'short_gumlet_folder_id', '' );
	}

	/**
	 * Retrieve folders/collections from Gumlet API
	 *
	 * @return array
	 */
	public static function get_folders() {
		$secret = self::get_api_secret();
		if ( empty( $secret ) ) {
			return array(
				'success' => false,
				'message' => 'Gumlet API Secret is not configured.',
				'folders' => array(),
			);
		}

		$workspace_id = self::get_workspace_id();
		$headers      = self::build_headers();

		// Primary endpoint: /video/workspaces/{workspace_id}/folders
		$url = ! empty( $workspace_id )
			? self::API_BASE . '/video/workspaces/' . rawurlencode( $workspace_id ) . '/folders'
			: self::API_BASE . '/video/folders';

		$response = wp_remote_get( $url, array(
			'timeout'   => 12,
			'headers'   => $headers,
			'sslverify' => false,
		) );

		$folders = array();
		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$raw_list = $body['data'] ?? $body['folders'] ?? ( is_array( $body ) ? $body : array() );
			if ( is_array( $raw_list ) ) {
				foreach ( $raw_list as $f ) {
					$fid = $f['id'] ?? $f['folder_id'] ?? '';
					$raw_name = $f['name'] ?? $f['title'] ?? '';

					if ( empty( $fid ) && empty( $raw_name ) ) {
						continue;
					}

					// Format nested folder hierarchy path if available (e.g. "Short / Season 1")
					$display_name = $raw_name ?: $fid;
					if ( ! empty( $f['path_names'] ) && is_array( $f['path_names'] ) ) {
						$all_parts = array_merge( $f['path_names'], array( $raw_name ) );
						$display_name = implode( ' / ', array_unique( array_filter( $all_parts ) ) );
					} elseif ( ! empty( $f['depth'] ) && $f['depth'] > 0 ) {
						$display_name = str_repeat( '— ', (int) $f['depth'] ) . $display_name;
					}

					$folders[] = array(
						'id'        => $fid ?: $raw_name,
						'name'      => $display_name,
						'parent_id' => $f['parent_id'] ?? null,
					);
				}
			}
		}

		// Fallback check on /video/folders?workspace={id} if primary failed
		if ( empty( $folders ) && ! empty( $workspace_id ) ) {
			$fb_resp = wp_remote_get( self::API_BASE . '/video/folders?workspace=' . rawurlencode( $workspace_id ), array(
				'timeout'   => 10,
				'headers'   => $headers,
				'sslverify' => false,
			) );

			if ( ! is_wp_error( $fb_resp ) && wp_remote_retrieve_response_code( $fb_resp ) === 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $fb_resp ), true );
				$raw_list = $body['data'] ?? $body['folders'] ?? ( is_array( $body ) ? $body : array() );
				if ( is_array( $raw_list ) ) {
					foreach ( $raw_list as $f ) {
						$fid = $f['id'] ?? $f['folder_id'] ?? '';
						$name = $f['name'] ?? $f['title'] ?? $fid;
						if ( ! empty( $fid ) || ! empty( $name ) ) {
							$folders[] = array(
								'id'        => $fid ?: $name,
								'name'      => $name ?: $fid,
								'parent_id' => $f['parent_id'] ?? null,
							);
						}
					}
				}
			}
		}

		// If user configured a default folder ID in settings, ensure it's selectable
		$default_folder = self::get_folder_id();
		if ( ! empty( $default_folder ) ) {
			$found = false;
			foreach ( $folders as $f ) {
				if ( $f['id'] === $default_folder ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				array_unshift( $folders, array(
					'id'        => $default_folder,
					'name'      => 'Configured Folder (' . substr( $default_folder, 0, 8 ) . '...)',
					'parent_id' => null,
				) );
			}
		}

		if ( ! empty( $folders ) ) {
			$folder_map = array();
			foreach ( $folders as $f ) {
				if ( ! empty( $f['id'] ) && ! empty( $f['name'] ) ) {
					$folder_map[ $f['id'] ] = $f['name'];
				}
			}
			if ( ! empty( $folder_map ) ) {
				$existing_map = get_option( 'short_gumlet_cached_folders_map', array() );
				if ( ! is_array( $existing_map ) ) {
					$existing_map = array();
				}
				update_option( 'short_gumlet_cached_folders_map', array_merge( $existing_map, $folder_map ) );
			}
		}

		return array(
			'success' => true,
			'folders' => $folders,
		);
	}

	/**
	 * Create a new folder in Gumlet Video API (supports nested subfolders via parent_id)
	 *
	 * @param string $folder_name
	 * @param string $parent_id Optional parent folder ID to nest inside
	 * @return array
	 */
	public static function create_folder( $folder_name, $parent_id = '' ) {
		$secret = self::get_api_secret();
		if ( empty( $secret ) ) {
			return array(
				'success' => false,
				'message' => 'Gumlet API Secret is not configured.',
			);
		}

		$workspace_id = self::get_workspace_id();
		$payload = array(
			'name' => sanitize_text_field( $folder_name ),
		);
		if ( ! empty( $parent_id ) ) {
			$payload['parent_id'] = sanitize_text_field( $parent_id );
		} else {
			$payload['parent_id'] = null;
		}

		$url = ! empty( $workspace_id )
			? self::API_BASE . '/video/workspaces/' . rawurlencode( $workspace_id ) . '/folders'
			: self::API_BASE . '/video/folders';

		$response = wp_remote_post( $url, array(
			'timeout'   => 12,
			'headers'   => self::build_headers(),
			'sslverify' => false,
			'body'      => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body ) ) {
			return array(
				'success'   => true,
				'folder_id' => $body['id'] ?? $body['folder_id'] ?? $body['parent_id'] ?? '',
				'name'      => $body['name'] ?? $folder_name,
				'data'      => $body,
			);
		}

		return array(
			'success' => false,
			'message' => $body['message'] ?? ( 'Gumlet API returned HTTP ' . $code ),
		);
	}

	/**
	 * Get existing folder ID or auto-create a new subfolder under parent_id
	 *
	 * @param string $folder_name Subfolder name (e.g. drama title)
	 * @param string $parent_id Parent folder ID (e.g. "Short" folder ID)
	 * @return string Resolved Folder ID (or parent_id on fallback)
	 */
	public static function get_or_create_subfolder( $folder_name, $parent_id = '' ) {
		$folder_name = trim( sanitize_text_field( $folder_name ) );
		if ( empty( $folder_name ) ) {
			return $parent_id;
		}

		$folders_res = self::get_folders();
		if ( ! empty( $folders_res['folders'] ) && is_array( $folders_res['folders'] ) ) {
			foreach ( $folders_res['folders'] as $f ) {
				$raw_name = $f['name'];
				$f_parent = $f['parent_id'] ?? null;

				// Match folder name directly or nested path
				$is_match = ( 0 === strcasecmp( $raw_name, $folder_name ) ) 
					|| ( substr( $raw_name, -strlen( ' / ' . $folder_name ) ) === ( ' / ' . $folder_name ) );

				if ( $is_match ) {
					if ( empty( $parent_id ) || $f_parent === $parent_id || empty( $f_parent ) ) {
						return $f['id'];
					}
				}
			}
		}

		// If not existing, create the subfolder under parent_id
		$create_res = self::create_folder( $folder_name, $parent_id );
		if ( ! empty( $create_res['success'] ) && ! empty( $create_res['folder_id'] ) ) {
			return $create_res['folder_id'];
		}

		return $parent_id;
	}
}
