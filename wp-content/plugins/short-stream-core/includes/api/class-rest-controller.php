<?php
namespace SHORT\Core\API;

use SHORT\Core\Video_Sources\Manager as Video_Manager;
use SHORT\Core\CPT\Video_CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class REST_Controller {
	const NAMESPACE = 'short/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function check_api_license_permission() {
		if ( class_exists( '\\SHORT\\Core\\Admin\\License' ) ) {
			return \SHORT\Core\Admin\License::is_valid();
		}
		return true;
	}

	public static function register_routes() {
		// Trending
		register_rest_route( self::NAMESPACE, '/trending(?:/(?P<type>all|movie|tv))?', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_trending' ),
			'permission_callback' => array( __CLASS__, 'check_api_license_permission' ),
		) );

		// Popular
		register_rest_route( self::NAMESPACE, '/popular/(?P<type>movie|tv)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_popular' ),
			'permission_callback' => '__return_true',
		) );

		// Top Rated
		register_rest_route( self::NAMESPACE, '/top-rated/(?P<type>movie|tv)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_top_rated' ),
			'permission_callback' => '__return_true',
		) );

		// Details
		register_rest_route( self::NAMESPACE, '/details/(?P<type>movie|tv)/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_details' ),
			'permission_callback' => '__return_true',
		) );

		// Season details for TV
		register_rest_route( self::NAMESPACE, '/tv/(?P<id>\d+)/season/(?P<season>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_season' ),
			'permission_callback' => '__return_true',
		) );

		// Search
		register_rest_route( self::NAMESPACE, '/search', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'search' ),
			'permission_callback' => '__return_true',
		) );

		// Video Sources for player
		register_rest_route( self::NAMESPACE, '/sources/(?P<type>movie|tv)/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_sources' ),
			'permission_callback' => '__return_true',
		) );

		// Gumlet Video CDN connection test (admin only)
		register_rest_route( self::NAMESPACE, '/gumlet/test', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'test_gumlet_connection' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		// Cloudflare R2 Storage connection test (admin only)
		register_rest_route( self::NAMESPACE, '/r2/test', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'test_r2_connection' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		// Cloudflare R2 Presigned Upload URL generator (admin/author only)
		register_rest_route( self::NAMESPACE, '/r2/presign', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'presign_r2_upload' ),
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		) );

		// Likes: Toggle Like (Add / Remove)
		register_rest_route( self::NAMESPACE, '/likes/toggle', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'toggle_like' ),
			'permission_callback' => '__return_true',
		) );

		// Likes: Get user's liked titles
		register_rest_route( self::NAMESPACE, '/likes', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_likes' ),
			'permission_callback' => '__return_true',
		) );

		// Likes: Check if item is liked
		register_rest_route( self::NAMESPACE, '/likes/check', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'check_like' ),
			'permission_callback' => '__return_true',
		) );

		// Notifications: Get active system & smart notifications
		register_rest_route( self::NAMESPACE, '/notifications', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_notifications' ),
			'permission_callback' => '__return_true',
		) );

		// ShortTV 9:16 Exact JSON Schema Endpoints
		register_rest_route( 'shorttv/v1', '/series', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_shorttv_all_series' ),
			'permission_callback' => array( __CLASS__, 'check_api_license_permission' ),
		) );

		register_rest_route( 'shorttv/v1', '/series/(?P<id>[a-zA-Z0-9_-]+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_shorttv_single_series' ),
			'permission_callback' => array( __CLASS__, 'check_api_license_permission' ),
		) );

		register_rest_route( 'shorttv/v1', '/series/unlock-episode', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'unlock_shorttv_episode' ),
			'permission_callback' => array( __CLASS__, 'check_api_license_permission' ),
		) );

		// Native WordPress Views Counter Endpoint
		register_rest_route( 'shorttv/v1', '/views/increment', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'increment_shorttv_view' ),
			'permission_callback' => '__return_true',
		) );

		// Native WordPress Likes / Hearts Toggle Endpoint
		register_rest_route( 'shorttv/v1', '/likes/toggle', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'toggle_shorttv_like' ),
			'permission_callback' => '__return_true',
		) );
	}

	public static function get_trending( \WP_REST_Request $request ) {
		$page = absint( $request->get_param( 'page' ) ?: 1 );
		$posts = get_posts( array(
			'post_type'      => Video_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'paged'          => $page,
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_short_views',
			'order'          => 'DESC',
		) );
		$results = array();
		foreach ( $posts as $p ) {
			$schema = Video_CPT::get_series_schema( $p->ID );
			$results[] = array(
				'id'            => $p->ID,
				'title'         => $p->post_title,
				'name'          => $p->post_title,
				'poster_path'   => $schema['poster'] ?? '',
				'backdrop_path' => $schema['poster'] ?? '',
				'vote_average'  => (float) ( $schema['rating'] ?? 5.0 ),
				'overview'      => $schema['overview'] ?? '',
			);
		}
		return rest_ensure_response( array(
			'page'          => $page,
			'results'       => $results,
			'total_results' => count( $results ),
			'total_pages'   => 1,
		) );
	}

	public static function get_popular( \WP_REST_Request $request ) {
		return self::get_trending( $request );
	}

	public static function get_top_rated( \WP_REST_Request $request ) {
		$page = absint( $request->get_param( 'page' ) ?: 1 );
		$posts = get_posts( array(
			'post_type'      => Video_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'paged'          => $page,
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_short_rating',
			'order'          => 'DESC',
		) );
		$results = array();
		foreach ( $posts as $p ) {
			$schema = Video_CPT::get_series_schema( $p->ID );
			$results[] = array(
				'id'            => $p->ID,
				'title'         => $p->post_title,
				'name'          => $p->post_title,
				'poster_path'   => $schema['poster'] ?? '',
				'backdrop_path' => $schema['poster'] ?? '',
				'vote_average'  => (float) ( $schema['rating'] ?? 5.0 ),
				'overview'      => $schema['overview'] ?? '',
			);
		}
		return rest_ensure_response( array(
			'page'          => $page,
			'results'       => $results,
			'total_results' => count( $results ),
			'total_pages'   => 1,
		) );
	}

	public static function get_details( \WP_REST_Request $request ) {
		$id = absint( $request->get_param( 'id' ) );
		$schema = Video_CPT::get_series_schema( $id );
		return rest_ensure_response( $schema );
	}

	public static function get_season( \WP_REST_Request $request ) {
		$id = absint( $request->get_param( 'id' ) );
		$schema = Video_CPT::get_series_schema( $id );
		return rest_ensure_response( array(
			'id'       => $id,
			'episodes' => $schema['episodes'] ?? array(),
		) );
	}

	public static function search( \WP_REST_Request $request ) {
		$query   = sanitize_text_field( $request->get_param( 'query' ) ?: '' );
		$page    = absint( $request->get_param( 'page' ) ?: 1 );
		$is_kids = ( function_exists( 'short_is_kids_mode' ) && short_is_kids_mode() ) || ( '1' === (string) $request->get_param( 'is_kids' ) );
		if ( empty( $query ) ) {
			return rest_ensure_response( array( 'page' => 1, 'results' => array(), 'total_results' => 0, 'total_pages' => 1 ) );
		}
		if ( function_exists( 'short_execute_search' ) ) {
			$data = short_execute_search( $query, $page, $is_kids );
			return rest_ensure_response( $data );
		}
		$posts = get_posts( array(
			'post_type'      => Video_CPT::POST_TYPE,
			'post_status'    => 'publish',
			's'              => $query,
			'posts_per_page' => 20,
			'paged'          => $page,
		) );
		$results = array();
		foreach ( $posts as $p ) {
			$schema = Video_CPT::get_series_schema( $p->ID );
			$results[] = array(
				'id'          => $p->ID,
				'title'       => $p->post_title,
				'name'        => $p->post_title,
				'poster_path' => $schema['poster'] ?? '',
				'is_short'    => true,
				'watch_url'   => home_url( '/watch/' . $p->ID . '/?episode=1' ),
			);
		}
		return rest_ensure_response( array(
			'page'          => $page,
			'results'       => $results,
			'total_results' => count( $results ),
			'total_pages'   => 1,
		) );
	}

	/**
	 * Get video sources with subscription tier filtering and subtitle data.
	 */
	public static function get_sources( \WP_REST_Request $request ) {
		$type    = sanitize_key( $request->get_param( 'type' ) ?: 'movie' );
		$tmdb_id = absint( $request->get_param( 'id' ) );
		$season  = absint( $request->get_param( 'season' ) ?: 1 );
		$episode = absint( $request->get_param( 'episode' ) ?: 1 );
		$tier    = sanitize_key( $request->get_param( 'tier' ) ?: 'free' );

		// Validate tier
		$valid_tiers = array( 'free', 'basic', 'standard', 'premium' );
		if ( ! in_array( $tier, $valid_tiers, true ) ) {
			$tier = 'free';
		}

		$result = Video_Manager::get_sources( $tmdb_id, $type, $season, $episode, $tier );

		return rest_ensure_response( array(
			'drama_id'  => $tmdb_id,
			'type'      => $type,
			'season'    => $season,
			'episode'   => $episode,
			'tier'      => $tier,
			'provider'  => $result['provider'] ?? 'fallback',
			'sources'   => $result['sources'] ?? array(),
			'subtitles' => $result['subtitles'] ?? array(),
		) );
	}

	/**
	 * Test Gumlet Video CDN API connection (admin-only endpoint).
	 *
	 * @since 1.3.0
	 */
	public static function test_gumlet_connection( \WP_REST_Request $request ) {
		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Gumlet' ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Gumlet class not found.',
			) );
		}

		$result = \SHORT\Core\Video_Sources\Gumlet::test_connection();
		return rest_ensure_response( $result );
	}

	/**
	 * Test Cloudflare R2 Storage API connection (admin-only endpoint).
	 *
	 * @since 1.4.0
	 */
	public static function test_r2_connection( \WP_REST_Request $request ) {
		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Cloudflare_R2' ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Cloudflare R2 class not found.',
			) );
		}

		$result = \SHORT\Core\Video_Sources\Cloudflare_R2::test_connection();
		return rest_ensure_response( $result );
	}

	/**
	 * Generate Presigned Upload URL for Cloudflare R2 direct client upload.
	 *
	 * @since 1.4.0
	 */
	public static function presign_r2_upload( \WP_REST_Request $request ) {
		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Cloudflare_R2' ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => 'Cloudflare R2 class not found.',
			) );
		}

		$filename     = sanitize_file_name( $request->get_param( 'filename' ) ?: 'video.mp4' );
		$content_type = sanitize_mime_type( $request->get_param( 'content_type' ) ?: 'video/mp4' );
		$folder       = sanitize_text_field( $request->get_param( 'folder' ) ?: \SHORT\Core\Video_Sources\Cloudflare_R2::get_folder() );
		$subfolder    = sanitize_text_field( $request->get_param( 'subfolder' ) ?: '' );

		$object_key = trim( $folder, '/' );
		if ( ! empty( $subfolder ) ) {
			$object_key .= '/' . trim( $subfolder, '/' );
		}
		$object_key .= '/' . wp_unique_filename( '', $filename );

		$presigned_url = \SHORT\Core\Video_Sources\Cloudflare_R2::get_presigned_upload_url( $object_key, $content_type, 3600 );

		if ( is_wp_error( $presigned_url ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => $presigned_url->get_error_message(),
			) );
		}

		$public_url = \SHORT\Core\Video_Sources\Cloudflare_R2::get_public_url( $object_key );

		return rest_ensure_response( array(
			'success'       => true,
			'upload_url'    => $presigned_url,
			'public_url'    => $public_url,
			'object_key'    => $object_key,
			'content_type'  => $content_type,
		) );
	}

	/**
	 * Toggle Like status for a movie or TV show.
	 *
	 * @since 1.2.0
	 */
	public static function toggle_like( \WP_REST_Request $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'short_user_likes';

		$tmdb_id     = absint( $request->get_param( 'tmdb_id' ) ?: $request->get_param( 'id' ) );
		$media_type  = sanitize_key( $request->get_param( 'media_type' ) ?: ( $request->get_param( 'type' ) ?: 'movie' ) );
		$title       = sanitize_text_field( $request->get_param( 'title' ) ?: '' );
		$poster_path = sanitize_text_field( $request->get_param( 'poster_path' ) ?: '' );
		$backdrop    = sanitize_text_field( $request->get_param( 'backdrop_path' ) ?: '' );
		$vote_avg    = floatval( $request->get_param( 'vote_average' ) ?: 0.0 );
		$year        = sanitize_text_field( $request->get_param( 'year' ) ?: ( $request->get_param( 'release_year' ) ?: '' ) );
		$guest_uuid  = sanitize_text_field( $request->get_param( 'guest_uuid' ) ?: '' );

		if ( ! $tmdb_id ) {
			return new \WP_Error( 'missing_id', __( 'TMDB ID is required.', 'short-stream-core' ), array( 'status' => 400 ) );
		}

		$user_id = get_current_user_id();
		if ( ! $user_id && empty( $guest_uuid ) ) {
			$guest_uuid = 'guest_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		}

		// Ensure table exists
		\SHORT\Core\Activator::create_tables();

		// Check if like already exists for this user / guest
		if ( $user_id > 0 ) {
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE user_id = %d AND tmdb_id = %d AND media_type = %s",
				$user_id, $tmdb_id, $media_type
			) );
		} else {
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE guest_uuid = %s AND tmdb_id = %d AND media_type = %s",
				$guest_uuid, $tmdb_id, $media_type
			) );
		}

		$is_liked = false;

		if ( $existing ) {
			// Un-like: Remove from database
			$wpdb->delete( $table_name, array( 'id' => $existing->id ), array( '%d' ) );
			$is_liked = false;
			$message  = __( 'Removed from Liked Titles', 'short-stream-core' );
		} else {
			// Like: Insert into database
			$wpdb->insert(
				$table_name,
				array(
					'user_id'       => $user_id,
					'guest_uuid'    => $guest_uuid,
					'tmdb_id'       => $tmdb_id,
					'media_type'    => $media_type,
					'title'         => $title,
					'poster_path'   => $poster_path,
					'backdrop_path' => $backdrop,
					'vote_average'  => $vote_avg,
					'release_year'  => $year,
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
			);
			$is_liked = true;
			$message  = __( 'Added to Liked Titles', 'short-stream-core' );
		}

		// Calculate total likes count for this item
		$total_likes = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE tmdb_id = %d AND media_type = %s",
			$tmdb_id, $media_type
		) );

		return rest_ensure_response( array(
			'success'     => true,
			'liked'       => $is_liked,
			'total_likes' => $total_likes,
			'message'     => $message,
			'tmdb_id'     => $tmdb_id,
			'media_type'  => $media_type,
		) );
	}

	/**
	 * Get list of liked titles for user / guest.
	 *
	 * @since 1.2.0
	 */
	public static function get_likes( \WP_REST_Request $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'short_user_likes';

		$user_id    = get_current_user_id();
		$guest_uuid = sanitize_text_field( $request->get_param( 'guest_uuid' ) ?: '' );

		// Ensure table exists
		\SHORT\Core\Activator::create_tables();

		if ( $user_id > 0 ) {
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, tmdb_id, media_type, title, poster_path, backdrop_path, vote_average, release_year, created_at FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
				$user_id
			), ARRAY_A );
		} elseif ( ! empty( $guest_uuid ) ) {
			$results = $wpdb->get_results( $wpdb->prepare(
				"SELECT id, tmdb_id, media_type, title, poster_path, backdrop_path, vote_average, release_year, created_at FROM $table_name WHERE guest_uuid = %s ORDER BY created_at DESC",
				$guest_uuid
			), ARRAY_A );
		} else {
			$results = array();
		}

		$items = array();
		foreach ( ( $results ?: array() ) as $row ) {
			$items[] = array(
				'id'            => (string) $row['tmdb_id'],
				'tmdb_id'       => (int) $row['tmdb_id'],
				'media_type'    => $row['media_type'],
				'title'         => $row['title'],
				'poster_path'   => $row['poster_path'],
				'backdrop_path' => $row['backdrop_path'],
				'vote_average'  => (float) $row['vote_average'],
				'year'          => $row['release_year'],
				'created_at'    => $row['created_at'],
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $items,
			'total'   => count( $items ),
		) );
	}

	/**
	 * Check if an item is liked and get its like count.
	 *
	 * @since 1.2.0
	 */
	public static function check_like( \WP_REST_Request $request ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'short_user_likes';

		$tmdb_id    = absint( $request->get_param( 'tmdb_id' ) ?: $request->get_param( 'id' ) );
		$media_type = sanitize_key( $request->get_param( 'media_type' ) ?: ( $request->get_param( 'type' ) ?: 'movie' ) );
		$user_id    = get_current_user_id();
		$guest_uuid = sanitize_text_field( $request->get_param( 'guest_uuid' ) ?: '' );

		if ( ! $tmdb_id ) {
			return new \WP_Error( 'missing_id', __( 'TMDB ID is required.', 'short-stream-core' ), array( 'status' => 400 ) );
		}

		// Ensure table exists
		\SHORT\Core\Activator::create_tables();

		$is_liked = false;
		if ( $user_id > 0 ) {
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE user_id = %d AND tmdb_id = %d AND media_type = %s",
				$user_id, $tmdb_id, $media_type
			) );
			$is_liked = ! empty( $row );
		} elseif ( ! empty( $guest_uuid ) ) {
			$row = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE guest_uuid = %s AND tmdb_id = %d AND media_type = %s",
				$guest_uuid, $tmdb_id, $media_type
			) );
			$is_liked = ! empty( $row );
		}

		$total_likes = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE tmdb_id = %d AND media_type = %s",
			$tmdb_id, $media_type
		) );

		return rest_ensure_response( array(
			'success'     => true,
			'liked'       => $is_liked,
			'total_likes' => $total_likes,
			'tmdb_id'     => $tmdb_id,
			'media_type'  => $media_type,
		) );
	}

	public static function get_notifications() {
		if ( class_exists( 'SHORT\Core\Admin\Dashboard' ) ) {
			$items = \SHORT\Core\Admin\Dashboard::get_notifications();
		} else {
			$items = get_option( 'short_system_notifications', array() );
		}
		return rest_ensure_response( array(
			'success'       => true,
			'notifications' => is_array( $items ) ? $items : array(),
		) );
	}

	public static function get_shorttv_all_series( \WP_REST_Request $request ) {
		$query = new \WP_Query( array(
			'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
		) );

		$list = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$list[] = \SHORT\Core\CPT\Video_CPT::get_series_schema( $post->ID );
			}
		}

		return rest_ensure_response( $list );
	}

	public static function get_shorttv_single_series( \WP_REST_Request $request ) {
		$id_param = $request->get_param( 'id' );
		$post_id = 0;

		if ( is_numeric( $id_param ) ) {
			$post_id = absint( $id_param );
		} else {
			// Find by slug or media_id
			$by_slug = get_page_by_path( $id_param, OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
			if ( $by_slug ) {
				$post_id = $by_slug->ID;
			} else {
				// Search meta query
				$mq = new \WP_Query( array(
					'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'   => '_shorttv_schema',
							'value' => '"media_id":"' . sanitize_text_field( $id_param ) . '"',
							'compare' => 'LIKE',
						),
					),
				) );
				if ( $mq->have_posts() ) {
					$post_id = $mq->posts[0]->ID;
				}
			}
		}

		if ( ! $post_id ) {
			// Fallback to first published drama
			$first = get_posts( array( 'post_type' => \SHORT\Core\CPT\Video_CPT::POST_TYPE, 'posts_per_page' => 1 ) );
			if ( ! empty( $first ) ) {
				$post_id = $first[0]->ID;
			}
		}

		$schema = \SHORT\Core\CPT\Video_CPT::get_series_schema( $post_id );
		if ( empty( $schema ) ) {
			return new \WP_REST_Response( array( 'error' => 'ShortTV Series not found' ), 404 );
		}

		return new \WP_REST_Response( $schema, 200 );
	}

	public static function unlock_shorttv_episode( \WP_REST_Request $request ) {
		$series_id = $request->get_param( 'series_id' );
		$ep_num    = absint( $request->get_param( 'episode_number' ) ?: 1 );
		$method    = sanitize_text_field( $request->get_param( 'method' ) ?: 'coins' );

		$post_id = 0;
		if ( is_numeric( $series_id ) ) {
			$post_id = absint( $series_id );
		} else {
			$by_slug = get_page_by_path( $series_id, OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
			if ( $by_slug ) {
				$post_id = $by_slug->ID;
			} else {
				$mq = new \WP_Query( array(
					'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'meta_query'     => array(
						array(
							'key'     => '_shorttv_schema',
							'value'   => '"media_id":"' . sanitize_text_field( $series_id ) . '"',
							'compare' => 'LIKE',
						),
					),
				) );
				if ( $mq->have_posts() ) {
					$post_id = $mq->posts[0]->ID;
				}
			}
		}

		if ( ! $post_id ) {
			$first = get_posts( array( 'post_type' => \SHORT\Core\CPT\Video_CPT::POST_TYPE, 'posts_per_page' => 1 ) );
			if ( ! empty( $first ) ) {
				$post_id = $first[0]->ID;
			}
		}

		$schema = \SHORT\Core\CPT\Video_CPT::get_series_schema( $post_id );
		$target_sources     = null;
		$target_unlock_type = 'free';
		if ( ! empty( $schema['episodes'] ) && is_array( $schema['episodes'] ) ) {
			foreach ( $schema['episodes'] as $ep ) {
				if ( (int) ( $ep['episode_number'] ?? 0 ) === (int) $ep_num ) {
					$target_sources     = $ep['sources'] ?? array();
					$target_unlock_type = strtolower( $ep['access_control']['unlock_type'] ?? 'free' );
					break;
				}
			}
		}

		if ( ! $target_sources ) {
			return new \WP_REST_Response( array( 'error' => 'Episode not found' ), 404 );
		}

		// Security: If unlock_type is VIP, strictly disallow coin/ad unlocks
		if ( 'vip' === $target_unlock_type ) {
			$tier = sanitize_text_field( $request->get_param( 'tier' ) ?: ( $_COOKIE['short_sub_tier'] ?? '' ) );
			$valid_tiers = array( 'basic', 'standard', 'premium', 'weekly', 'monthly', 'annual', 'vip' );
			$is_vip = in_array( strtolower( $tier ), $valid_tiers, true ) || current_user_can( 'manage_options' );
			if ( ! $is_vip && 'vip' !== $method && 'verify' !== $method ) {
				return new \WP_REST_Response( array( 'error' => 'VIP Pass is required to unlock this exclusive episode.' ), 403 );
			}
		}

		return rest_ensure_response( array(
			'success'        => true,
			'is_unlocked'    => true,
			'episode_number' => $ep_num,
			'series_id'      => $series_id,
			'sources'        => $target_sources,
			'message'        => 'Episode ' . $ep_num . ' unlocked successfully!',
		) );
	}

	/**
	 * Native WordPress Views Counter Increment
	 */
	public static function increment_shorttv_view( \WP_REST_Request $request ) {
		$series_id = $request->get_param( 'series_id' );
		$post_id   = 0;
		if ( is_numeric( $series_id ) ) {
			$post_id = absint( $series_id );
		} else {
			$by_slug = get_page_by_path( $series_id, OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
			if ( $by_slug ) {
				$post_id = $by_slug->ID;
			}
		}

		if ( ! $post_id ) {
			return new \WP_REST_Response( array( 'error' => 'Drama not found' ), 404 );
		}

		$current_views = (int) get_post_meta( $post_id, '_shorttv_view_count', true );
		$new_views     = $current_views + 1;
		update_post_meta( $post_id, '_shorttv_view_count', $new_views );

		// Sync inside _shorttv_schema if present
		$schema = get_post_meta( $post_id, '_shorttv_schema', true );
		if ( is_array( $schema ) ) {
			if ( ! isset( $schema['analytics'] ) || ! is_array( $schema['analytics'] ) ) {
				$schema['analytics'] = array();
			}
			$schema['analytics']['view_count'] = $new_views;
			update_post_meta( $post_id, '_shorttv_schema', $schema );
		}

		// Formatting
		if ( $new_views >= 1000000 ) {
			$fmt = round( $new_views / 1000000, 1 ) . 'M';
		} elseif ( $new_views >= 1000 ) {
			$fmt = round( $new_views / 1000, 1 ) . 'K';
		} else {
			$fmt = (string) $new_views;
		}

		return rest_ensure_response( array(
			'success'    => true,
			'view_count' => $new_views,
			'formatted'  => $fmt,
		) );
	}

	/**
	 * Native WordPress Hearts / Likes Toggle
	 */
	public static function toggle_shorttv_like( \WP_REST_Request $request ) {
		$series_id = $request->get_param( 'series_id' );
		$liked     = (bool) $request->get_param( 'liked' );
		$post_id   = 0;
		if ( is_numeric( $series_id ) ) {
			$post_id = absint( $series_id );
		} else {
			$by_slug = get_page_by_path( $series_id, OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
			if ( $by_slug ) {
				$post_id = $by_slug->ID;
			}
		}

		if ( ! $post_id ) {
			return new \WP_REST_Response( array( 'error' => 'Drama not found' ), 404 );
		}

		$current_likes = (int) get_post_meta( $post_id, '_shorttv_like_count', true );
		if ( $liked ) {
			$new_likes = $current_likes + 1;
		} else {
			$new_likes = max( 0, $current_likes - 1 );
		}
		update_post_meta( $post_id, '_shorttv_like_count', $new_likes );

		// Sync inside _shorttv_schema
		$schema = get_post_meta( $post_id, '_shorttv_schema', true );
		if ( is_array( $schema ) ) {
			if ( ! isset( $schema['analytics'] ) || ! is_array( $schema['analytics'] ) ) {
				$schema['analytics'] = array();
			}
			$schema['analytics']['like_count'] = $new_likes;
			update_post_meta( $post_id, '_shorttv_schema', $schema );
		}

		// Formatting
		if ( $new_likes >= 1000000 ) {
			$fmt = round( $new_likes / 1000000, 1 ) . 'M';
		} elseif ( $new_likes >= 1000 ) {
			$fmt = round( $new_likes / 1000, 1 ) . 'K';
		} else {
			$fmt = (string) $new_likes;
		}

		return rest_ensure_response( array(
			'success'    => true,
			'liked'      => $liked,
			'like_count' => $new_likes,
			'formatted'  => $fmt,
		) );
	}
}
