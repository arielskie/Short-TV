<?php
namespace SHORT\Core\Video_Sources;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Video Source Manager
 *
 * Resolves streaming sources for dramas and videos.
 *
 * @since 1.0.0
 */
class Manager {

	/**
	 * Get streaming sources for a given piece of content.
	 *
	 * @param int    $tmdb_id    Drama / Media ID.
	 * @param string $media_type 'movie' or 'tv'.
	 * @param int    $season     Season number (TV only).
	 * @param int    $episode    Episode number (TV only).
	 * @param string $user_tier  Subscription tier: free|basic|standard|premium.
	 * @return array {
	 *     @type array $sources   Array of video source entries.
	 *     @type array $subtitles Array of subtitle tracks.
	 *     @type string $provider Source provider identifier: 'gumlet', 'manual', 'fallback'.
	 * }
	 */
	public static function get_sources( $tmdb_id, $media_type = 'movie', $season = 0, $episode = 0, $user_tier = 'free' ) {
		$sources   = array();
		$subtitles = array();
		$provider  = 'fallback';

		// ── Priority 0: Native Video Post Direct Servers & Episodes ──
		if ( $tmdb_id > 0 && get_post_type( $tmdb_id ) === \SHORT\Core\CPT\Video_CPT::POST_TYPE ) {
			$post_type_meta = get_post_meta( $tmdb_id, '_short_type', true ) ?: 'movie';
			if ( 'tv' === $post_type_meta && ( $season > 0 || $episode > 0 ) ) {
				$episodes = get_post_meta( $tmdb_id, '_short_episodes', true ) ?: array();
				foreach ( $episodes as $ep ) {
					if ( (int) ( $ep['season'] ?? 1 ) === (int) max( 1, $season ) && (int) ( $ep['episode'] ?? 1 ) === (int) max( 1, $episode ) ) {
						if ( ! empty( $ep['url'] ) ) {
							$is_mp4 = (bool) preg_match( '/\.(mp4|webm|ogg)($|\?)/i', $ep['url'] );
							$is_hls = (bool) preg_match( '/\.m3u8($|\?)/i', $ep['url'] );
							$stype  = $is_hls ? 'hls' : ( $is_mp4 ? 'mp4' : 'iframe' );
							$sources[] = array(
								'name'      => 'Server 1 (Primary Episode Stream)',
								'url'       => $ep['url'],
								'type'      => $stype,
								'quality'   => '1080p',
								'language'  => 'Original',
								'subtitles' => '',
								'enabled'   => 1,
							);
							$provider = 'manual_post';
						}
						break;
					}
				}
			}
			$post_servers = get_post_meta( $tmdb_id, '_short_servers', true ) ?: array();
			if ( ! empty( $post_servers ) && is_array( $post_servers ) ) {
				foreach ( $post_servers as $srv ) {
					if ( ! empty( $srv['url'] ) ) {
						$sources[] = $srv;
						$provider = 'manual_post';
					}
				}
			}
		}

		// ── Priority 1: Gumlet Video CDN (uploaded/managed video assets) ──
		if ( class_exists( 'SHORT\\Core\\Video_Sources\\Gumlet' ) && Gumlet::is_enabled() ) {
			$gumlet_data = Gumlet::get_streams( $tmdb_id, $media_type, $season, $episode );
			if ( ! empty( $gumlet_data['sources'] ) ) {
				$sources   = array_merge( $sources, $gumlet_data['sources'] );
				$subtitles = array_merge( $subtitles, $gumlet_data['subtitles'] ?? array() );
				if ( 'fallback' === $provider || 'manual_post' === $provider ) {
					$provider = 'gumlet';
				}
			}
		}

		// ── Priority 2: Manual & Global CPT video sources from WP Admin ──
		$cpt_sources = self::get_cpt_sources( $tmdb_id, $media_type, $season, $episode );
		if ( ! empty( $cpt_sources ) ) {
			$sources = array_merge( $sources, $cpt_sources );
			if ( 'fallback' === $provider ) {
				$provider = 'cpt_admin';
			}
		}

		// ── Priority 4: Direct Extractor & Embed Mirrors (if no CPT sources) ─
		if ( empty( $sources ) && class_exists( 'SHORT\\Core\\Video_Sources\\Direct_Extractor' ) ) {
			$direct_data = Direct_Extractor::get_streams( $tmdb_id, $media_type, $season, $episode, $user_tier );
			if ( ! empty( $direct_data['sources'] ) ) {
				$sources   = array_merge( $sources, $direct_data['sources'] );
				if ( empty( $subtitles ) ) {
					$subtitles = $direct_data['subtitles'] ?? array();
				}
				if ( 'fallback' === $provider ) {
					$provider = 'direct';
				}
			}
		}

		// ── Priority 5: Clean Fallback Mirrors (Last Resort) ───────────
		if ( empty( $sources ) ) {
			$fallback_sources = self::get_fallback_sources( $tmdb_id, $media_type, $season, $episode );
			if ( ! empty( $fallback_sources ) ) {
				$sources = array_merge( $sources, $fallback_sources );
			}
		}

		// Re-index servers nicely
		$indexed_sources = array();
		$s_num = 1;
		foreach ( $sources as $src ) {
			if ( empty( $src['url'] ) ) continue;
			$src['server_num'] = $s_num;
			$indexed_sources[] = $src;
			$s_num++;
		}

		return array(
			'sources'   => $indexed_sources,
			'subtitles' => $subtitles,
			'provider'  => $provider,
		);
	}

	/**
	 * Query manually-configured video sources and Global Defaults from the CPT.
	 *
	 * @param int    $tmdb_id    TMDB ID.
	 * @param string $media_type 'movie' or 'tv'.
	 * @param int    $season     Season number.
	 * @param int    $episode    Episode number.
	 * @return array Array of source entries.
	 */
	private static function get_cpt_sources( $video_id, $media_type, $season, $episode ) {
		$video_id   = (int) $video_id;
		$season     = (int) $season;
		$episode    = (int) $episode;
		$media_type = sanitize_key( $media_type );

		// 1. Try Specific Video ID Match First
		$specific_query = new WP_Query( array(
			'post_type'      => array( 'short_video_source' ),
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_short_video_id',
					'value'   => $video_id,
					'compare' => '=',
				),
			),
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		$sources = array();

		if ( $specific_query->have_posts() ) {
			while ( $specific_query->have_posts() ) {
				$specific_query->the_post();
				$post_id = get_the_ID();
				$enabled = get_post_meta( $post_id, '_short_enabled', true );
				if ( '0' === (string) $enabled ) {
					continue;
				}
				$servers = get_post_meta( $post_id, '_short_servers', true );
				if ( is_array( $servers ) && ! empty( $servers ) ) {
					foreach ( $servers as $srv ) {
						if ( ! empty( $srv['enabled'] ) || ! isset( $srv['enabled'] ) ) {
							$srv['url']     = self::replace_placeholders( $srv['url'] ?? '', $video_id, $season, $episode );
							$srv['locked']  = false;
							$srv['headers'] = array();
							if ( ! empty( $srv['url'] ) ) {
								$sources[] = $srv;
							}
						}
					}
				}
			}
			wp_reset_postdata();
		}

		return $sources;
	}

	/**
	 * Replace dynamic URL placeholders in video source URLs.
	 */
	private static function replace_placeholders( $url, $video_id, $season, $episode ) {
		if ( empty( $url ) ) return '';
		$s = $season > 0 ? $season : 1;
		$e = $episode > 0 ? $episode : 1;

		$replacements = array(
			'{id}'      => $video_id,
			'{video_id}'=> $video_id,
			'{s}'       => $s,
			'{season}'  => $s,
			'{e}'       => $e,
			'{episode}' => $e,
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $url );
	}

	/**
	 * Get fallback embed sources for video item.
	 *
	 * @param int    $video_id   Video ID.
	 * @param string $media_type Media type.
	 * @param int    $season     Season number.
	 * @param int    $episode    Episode number.
	 * @return array Array of fallback sources.
	 */
	private static function get_fallback_sources( $video_id, $media_type, $season, $episode ) {
		return array();
	}
}
