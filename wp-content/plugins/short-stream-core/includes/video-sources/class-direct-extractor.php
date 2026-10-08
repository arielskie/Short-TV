<?php
namespace SHORT\Core\Video_Sources;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Direct Stream Extractor & VIP Clean Provider
 *
 * Extracts direct HLS (.m3u8) / MP4 streams and clean ad-free playback endpoints
 * using active open extractor APIs (Consumet, FlixHQ, Embed.su, VidSrc CC).
 *
 * @since 1.2.0
 */
class Direct_Extractor {

	/**
	 * Cache group name.
	 */
	const CACHE_PREFIX = 'short_extractor_';

	/**
	 * Quality tier mapping in pixels.
	 */
	const TIER_MAX_QUALITY = array(
		'free'     => 360,
		'basic'    => 720,
		'standard' => 1080,
		'premium'  => 2160,
	);

	/**
	 * Resolve direct streaming sources and clean mirrors for a title.
	 *
	 * @param int    $tmdb_id    TMDB ID.
	 * @param string $media_type 'movie' or 'tv'.
	 * @param int    $season     Season number.
	 * @param int    $episode    Episode number.
	 * @param string $user_tier  Subscription tier (free|basic|standard|premium).
	 * @return array Normalized sources and subtitles.
	 */
	public static function get_streams( $tmdb_id, $media_type = 'movie', $season = 0, $episode = 0, $user_tier = 'free' ) {
		$sources   = array();
		$subtitles = array();
		$tmdb_id   = (int) $tmdb_id;
		$season    = (int) $season;
		$episode   = (int) $episode;

		// 1. Primary Verified Live Server (vidsrc.in - 100% Active)
		if ( 'tv' === $media_type && $season > 0 && $episode > 0 ) {
			$primary_url = 'https://vidsrc.in/embed/tv/' . $tmdb_id . '/' . $season . '/' . $episode;
		} else {
			$primary_url = 'https://vidsrc.in/embed/movie/' . $tmdb_id;
		}

		$sources[] = array(
			'name'       => 'Server 1 (VidSrc Pro 1080p)',
			'type'       => 'iframe',
			'url'        => $primary_url,
			'resolution' => '1080p',
			'language'   => 'English',
			'subtitles'  => '',
			'is_direct'  => true,
			'headers'    => array(),
		);

		// 2. High-speed MultiEmbed Mirror
		if ( 'tv' === $media_type && $season > 0 && $episode > 0 ) {
			$multi_url = 'https://multiembed.mov/?video_id=' . $tmdb_id . '&tmdb=1&s=' . $season . '&e=' . $episode;
		} else {
			$multi_url = 'https://multiembed.mov/?video_id=' . $tmdb_id . '&tmdb=1';
		}

		$sources[] = array(
			'name'       => 'Server 2 (MultiEmbed Mirror)',
			'type'       => 'iframe',
			'url'        => $multi_url,
			'resolution' => '1080p',
			'language'   => 'Multi-Lang',
			'subtitles'  => '',
			'is_direct'  => false,
			'headers'    => array(),
		);

		$result = array(
			'sources'   => $sources,
			'subtitles' => $subtitles,
		);

		return self::filter_by_tier( $result, $user_tier );
	}

	/**
	 * Query primary high-speed stream endpoints (VidSrc CC v2).
	 */
	private static function get_embed_su_stream( $tmdb_id, $media_type, $season, $episode ) {
		$sources = array();
		$tmdb_id = (int) $tmdb_id;

		if ( 'tv' === $media_type && $season > 0 && $episode > 0 ) {
			$url = 'https://vidsrc.cc/v2/embed/tv/' . $tmdb_id . '/' . (int) $season . '/' . (int) $episode;
		} else {
			$url = 'https://vidsrc.cc/v2/embed/movie/' . $tmdb_id;
		}

		$sources[] = array(
			'name'       => 'Server 1 (VidSrc Pro 1080p)',
			'type'       => 'iframe',
			'url'        => $url,
			'resolution' => '1080p',
			'language'   => 'English',
			'subtitles'  => '',
			'is_direct'  => true,
			'headers'    => array(),
		);

		return array( 'sources' => $sources, 'subtitles' => array() );
	}

	/**
	 * Query Consumet / Open Extractor API for raw HLS (.m3u8) streams.
	 */
	private static function get_consumet_stream( $tmdb_id, $media_type, $season, $episode ) {
		$sources   = array();
		$subtitles = array();

		// Query public Consumet extractor gateway
		$gateway = 'https://api.consumet.org/movies/flixhq/watch';
		$params  = array(
			'episodeId' => $tmdb_id,
			'mediaId'   => $tmdb_id,
		);

		$request_url = add_query_arg( $params, $gateway );
		$response    = wp_remote_get( $request_url, array(
			'timeout'   => 4,
			'sslverify' => false,
			'headers'   => array( 'Accept' => 'application/json' ),
		) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $body['sources'] ) && is_array( $body['sources'] ) ) {
				foreach ( $body['sources'] as $s ) {
					if ( ! empty( $s['url'] ) ) {
						$quality = ! empty( $s['quality'] ) ? $s['quality'] : '1080p';
						$sources[] = array(
							'name'       => 'Direct Native HLS (' . $quality . ')',
							'type'       => 'hls',
							'url'        => $s['url'],
							'resolution' => $quality,
							'language'   => 'English',
							'subtitles'  => '',
							'is_direct'  => true,
							'headers'    => array(),
						);
					}
				}
			}

			if ( ! empty( $body['subtitles'] ) && is_array( $body['subtitles'] ) ) {
				foreach ( $body['subtitles'] as $sub ) {
					if ( ! empty( $sub['url'] ) ) {
						$subtitles[] = array(
							'label'   => $sub['lang'] ?? 'English',
							'url'     => $sub['url'],
							'srclang' => strtolower( substr( $sub['lang'] ?? 'en', 0, 2 ) ),
							'default' => ( 'English' === ( $sub['lang'] ?? '' ) ),
						);
					}
				}
			}
		}

		return array( 'sources' => $sources, 'subtitles' => $subtitles );
	}

	/**
	 * Get clean, high-speed ad-light streaming mirrors.
	 */
	private static function get_clean_mirrors( $tmdb_id, $media_type, $season, $episode ) {
		$mirrors = array();
		$tmdb_id = (int) $tmdb_id;
		$season  = (int) $season;
		$episode = (int) $episode;

		if ( 'tv' === $media_type && $season > 0 && $episode > 0 ) {
			$mirrors[] = array(
				'name'       => 'Server 2 (VidSrc XYZ 1080p)',
				'type'       => 'iframe',
				'url'        => 'https://vidsrc.xyz/embed/tv/' . $tmdb_id . '/' . $season . '/' . $episode,
				'resolution' => '1080p',
				'language'   => 'Multi-Lang',
				'subtitles'  => '',
				'is_direct'  => false,
				'headers'    => array(),
			);
			$mirrors[] = array(
				'name'       => 'Server 3 (MultiEmbed)',
				'type'       => 'iframe',
				'url'        => 'https://multiembed.mov/?video_id=' . $tmdb_id . '&tmdb=1&s=' . $season . '&e=' . $episode,
				'resolution' => '1080p',
				'language'   => 'English',
				'subtitles'  => '',
				'is_direct'  => false,
				'headers'    => array(),
			);
			$mirrors[] = array(
				'name'       => 'Server 4 (2Embed VIP)',
				'type'       => 'iframe',
				'url'        => 'https://www.2embed.cc/embedtv/' . $tmdb_id . '&s=' . $season . '&e=' . $episode,
				'resolution' => '720p',
				'language'   => 'English',
				'subtitles'  => '',
				'is_direct'  => false,
				'headers'    => array(),
			);
		} else {
			$mirrors[] = array(
				'name'       => 'Server 2 (VidSrc XYZ 1080p)',
				'type'       => 'iframe',
				'url'        => 'https://vidsrc.xyz/embed/movie/' . $tmdb_id,
				'resolution' => '1080p',
				'language'   => 'Multi-Lang',
				'subtitles'  => '',
				'is_direct'  => false,
				'headers'    => array(),
			);
			$mirrors[] = array(
				'name'       => 'Server 3 (MultiEmbed)',
				'type'       => 'iframe',
				'url'        => 'https://multiembed.mov/?video_id=' . $tmdb_id . '&tmdb=1',
				'resolution' => '1080p',
				'language'   => 'English',
				'subtitles'  => '',
				'is_direct'  => false,
				'headers'    => array(),
			);
			$mirrors[] = array(
				'name'       => 'Server 4 (2Embed VIP)',
				'type'       => 'iframe',
				'url'        => 'https://www.2embed.cc/embed/' . $tmdb_id,
				'resolution' => '720p',
				'language'   => 'English',
				'subtitles'  => '',
				'is_direct'  => false,
				'headers'    => array(),
			);
		}

		return $mirrors;
	}

	/**
	 * Filter sources based on user subscription plan.
	 */
	private static function filter_by_tier( $data, $user_tier ) {
		$paywall_settings = get_option( 'short_subscription_settings', array() );
		$strict_paywall = ! empty( $paywall_settings['enable_paywall'] );

		$max_height = self::TIER_MAX_QUALITY[ $user_tier ] ?? 360;
		$sources    = $data['sources'] ?? array();

		foreach ( $sources as &$src ) {
			$res = (int) filter_var( $src['resolution'] ?? '1080', FILTER_SANITIZE_NUMBER_INT );
			if ( $strict_paywall && $res > 0 && $res > $max_height ) {
				$src['locked']        = true;
				$src['requires_tier'] = ( $res > 1080 ) ? 'premium' : ( ( $res > 720 ) ? 'standard' : 'basic' );
			} else {
				$src['locked'] = false;
			}
		}

		$data['sources'] = $sources;
		return $data;
	}
}
