<?php
namespace SHORT\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activator {
	public static function activate() {
		// ── Migrate old short_ option keys → short_ keys (one-time) ──────────
		$migrations = array(
			'short_firebase_settings'     => 'short_firebase_settings',
			'short_player_settings'       => 'short_player_settings',
			'short_subscription_settings' => 'short_subscription_settings',
			'short_homepage_sections'     => 'short_homepage_sections',
		);
		foreach ( $migrations as $old_key => $new_key ) {
			$old_val = get_option( $old_key );
			if ( $old_val && ! get_option( $new_key ) ) {
				update_option( $new_key, $old_val );
			}
		}

		if ( ! get_option( 'short_firebase_settings' ) ) {
			update_option( 'short_firebase_settings', array(
				'api_key'             => '',
				'auth_domain'         => '',
				'project_id'          => '',
				'storage_bucket'      => '',
				'messaging_sender_id' => '',
				'app_id'              => '',
				'measurement_id'      => '',
				'enable_google_auth'  => '1',
				'enable_email_verify' => '1',
			) );
		}

		if ( ! get_option( 'short_player_settings' ) ) {
			update_option( 'short_player_settings', array(
				'default_server'     => 'Server 1',
				'auto_play'          => '1',
				'auto_next'          => '1',
				'track_interval'     => 15,
			) );
		}

		// 1. Default Homepage Sections (Authentic ReelShort / ShortTV Architecture)
		if ( ! get_option( 'short_homepage_sections' ) ) {
			update_option( 'short_homepage_sections', array(
				array( 'id' => 'hero_showcase', 'title' => 'Hero Slider Showcase', 'type' => 'views', 'layout' => 'hero', 'endpoint' => 'views', 'limit' => 5, 'enabled' => 1 ),
				array( 'id' => 'reel_original', 'title' => 'Reel Original 🎬', 'type' => 'views', 'layout' => 'portrait', 'endpoint' => 'views', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'top_10_today', 'title' => 'TOP 🔟 (Ranked 1-10)', 'type' => 'rating', 'layout' => 'top10', 'endpoint' => 'rating', 'limit' => 10, 'enabled' => 1 ),
				array( 'id' => 'new_releases', 'title' => 'New Releases 🚀', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'binge_ready', 'title' => 'Binge-Ready 🔥', 'type' => 'episodes', 'layout' => 'portrait', 'endpoint' => 'episodes', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'romance_dramas', 'title' => 'Romance & Passion 💕', 'type' => 'genre/romance', 'layout' => 'portrait', 'endpoint' => 'genre/romance', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'fantasy_dramas', 'title' => 'Fantasy & Supernatural ✨', 'type' => 'genre/fantasy', 'layout' => 'portrait', 'endpoint' => 'genre/fantasy', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'vampire_dramas', 'title' => 'Vampire & Werewolf 🐺', 'type' => 'genre/vampire', 'layout' => 'portrait', 'endpoint' => 'genre/vampire', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'ceo_dramas', 'title' => 'Billionaire & CEO 👔', 'type' => 'genre/ceo', 'layout' => 'portrait', 'endpoint' => 'genre/ceo', 'limit' => 15, 'enabled' => 1 ),
			) );
		}

		// 2. Default Section Tabs & Navigation Config (Desktop & Mobile)
		if ( ! get_option( 'short_section_tabs_config' ) ) {
			update_option( 'short_section_tabs_config', array(
				'desktop' => array(
					array( 'target' => 'home',        'title' => __( 'Home', 'short-stream-core' ),           'icon' => 'dashicons-admin-home',  'url' => '/' ),
					array( 'target' => 'categories',  'title' => __( 'Discover', 'short-stream-core' ),       'icon' => 'dashicons-category',    'url' => '/genre/' ),
					array( 'target' => 'new-popular', 'title' => __( 'New and Popular', 'short-stream-core' ),'icon' => 'dashicons-star-filled', 'url' => '/new-popular/' ),
					array( 'target' => 'leaderboard', 'title' => __( 'Leaderboard', 'short-stream-core' ),    'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
					array( 'target' => 'my-list',     'title' => __( 'My List', 'short-stream-core' ),        'icon' => 'dashicons-list-view',   'url' => '/my-list/' ),
				),
				'mobile_header' => array(
					array( 'target' => 'home',        'title' => __( 'Home', 'short-stream-core' ),           'icon' => 'dashicons-admin-home',  'url' => '/' ),
					array( 'target' => 'categories',  'title' => __( 'Discover', 'short-stream-core' ),       'icon' => 'dashicons-category',    'url' => '/genre/' ),
					array( 'target' => 'new-popular', 'title' => __( 'New and Popular', 'short-stream-core' ),'icon' => 'dashicons-star-filled', 'url' => '/new-popular/' ),
					array( 'target' => 'leaderboard', 'title' => __( 'Leaderboard', 'short-stream-core' ),    'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
					array( 'target' => 'my-list',     'title' => __( 'My List', 'short-stream-core' ),        'icon' => 'dashicons-list-view',   'url' => '/my-list/' ),
				),
				'mobile'  => array(
					array( 'target' => 'mobile_home',        'title' => __( 'Home', 'short-stream-core' ),        'icon' => 'dashicons-admin-home',  'url' => '/' ),
					array( 'target' => 'mobile_discovery',   'title' => __( 'Discover', 'short-stream-core' ),    'icon' => 'dashicons-video-alt3',  'url' => '/genre/' ),
					array( 'target' => 'mobile_rewards',     'title' => __( 'Rewards', 'short-stream-core' ),     'icon' => 'dashicons-tickets-alt', 'url' => '/reward/' ),
					array( 'target' => 'mobile_leaderboard', 'title' => __( 'Ranking', 'short-stream-core' ),     'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
					array( 'target' => 'mobile_account',     'title' => __( 'Account', 'short-stream-core' ),     'icon' => 'dashicons-admin-users', 'url' => '/account/' ),
				),
			) );
		}

		// 3. Default Mobile Home & Discovery Sections
		if ( ! get_option( 'short_mobile_home_sections' ) ) {
			update_option( 'short_mobile_home_sections', array(
				array( 'id' => 'hero_showcase', 'title' => 'Hero Slider Showcase', 'type' => 'views', 'layout' => 'hero', 'endpoint' => 'views', 'limit' => 5, 'enabled' => 1 ),
				array( 'id' => 'reel_original', 'title' => 'Reel Original 🎬', 'type' => 'views', 'layout' => 'portrait', 'endpoint' => 'views', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'top_10_today', 'title' => 'TOP 🔟 (Ranked 1-10)', 'type' => 'rating', 'layout' => 'top10', 'endpoint' => 'rating', 'limit' => 10, 'enabled' => 1 ),
				array( 'id' => 'new_releases', 'title' => 'New Releases 🚀', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 15, 'enabled' => 1 ),
			) );
		}

		// 4. Default Brand Settings (Logo, Colors, Name)
		if ( ! get_option( 'short_brand_settings' ) ) {
			update_option( 'short_brand_settings', array(
				'brand_name'        => 'ShortTV',
				'custom_logo_url'   => 'https://i.postimg.cc/cH3CM5h4/image.png',
				'theme_color'       => '#E50914',
				'display_mode'      => 'cinematic-dark',
				'logo_display_mode' => 'image_only',
				'logo_display'      => 'image',
			) );
		}

		// 5. Default Security Settings
		if ( ! get_option( 'short_security_settings' ) ) {
			update_option( 'short_security_settings', array(
				'allow_right_click' => '0',
				'allow_inspection'  => '0',
			) );
		}

		// Register ShortTV Post Types
		if ( class_exists( '\\SHORT\\Core\\CPT\\Video_CPT' ) ) {
			\SHORT\Core\CPT\Video_CPT::register_post_type_and_taxonomies();
		}

		// Only flush rewrite rules and create tables if in admin context or explicit activation
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			flush_rewrite_rules( false );
			self::create_tables();
		}
	}

	public static function create_tables() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'short_user_likes';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			guest_uuid varchar(64) NOT NULL DEFAULT '',
			tmdb_id bigint(20) unsigned NOT NULL,
			media_type varchar(20) NOT NULL DEFAULT 'movie',
			title varchar(255) NOT NULL DEFAULT '',
			poster_path varchar(255) NOT NULL DEFAULT '',
			backdrop_path varchar(255) NOT NULL DEFAULT '',
			vote_average decimal(3,1) NOT NULL DEFAULT 0.0,
			release_year varchar(10) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY guest_uuid (guest_uuid),
			KEY tmdb_id (tmdb_id),
			UNIQUE KEY user_item (user_id, guest_uuid, tmdb_id, media_type)
		) $charset_collate;";

		if ( ! function_exists( 'dbDelta' ) ) {
			if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}
		}
		if ( function_exists( 'dbDelta' ) ) {
			dbDelta( $sql );
		}
	}
}
