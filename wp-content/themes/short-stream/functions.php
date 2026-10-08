<?php
/**
 * Short Stream Theme Functions
 *
 * @package Short_Stream
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SHORT_THEME_VERSION', time() );
define( 'SHORT_THEME_DIR', get_template_directory() );
define( 'SHORT_THEME_URI', get_template_directory_uri() );

/**
 * Synchronize Runtime Environment & Media Assets
 */
function short_theme_sync_runtime_environment() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( class_exists( 'SHORT\\Core\\Admin\\License' ) ) {
		if ( ! \SHORT\Core\Admin\License::is_valid() ) {
			\SHORT\Core\Admin\License::enforce_frontend_lock();
		}
	}
}
add_action( 'template_redirect', 'short_theme_sync_runtime_environment', 0 );

function short_setup_theme() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array(
		'height'      => 60,
		'width'       => 200,
		'flex-height' => true,
		'flex-width'  => true,
		'header-text' => array( 'site-title', 'site-description' ),
	) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus( array(
		'primary' => __( 'Primary Navigation', 'short-stream' ),
		'footer'  => __( 'Footer Navigation', 'short-stream' ),
	) );
}
add_action( 'after_setup_theme', 'short_setup_theme' );

/**
 * Get Custom Site Logo URL (Admin Uploaded, Theme Setting, or Default)
 */
function short_get_custom_logo_url() {
	$brand_settings = get_option( 'short_brand_settings', array() );
	if ( ! empty( $brand_settings['custom_logo_url'] ) ) {
		$url = $brand_settings['custom_logo_url'];
		if ( false !== strpos( $url, 'http://' ) || false !== strpos( $url, 'https://' ) || 0 === strpos( $url, '//' ) ) {
			return esc_url( $url );
		}
		return esc_url( function_exists( 'short_normalize_url' ) ? short_normalize_url( $url ) : $url );
	}
	$custom_logo_id = get_theme_mod( 'custom_logo' );
	if ( $custom_logo_id ) {
		$logo_src = wp_get_attachment_image_src( $custom_logo_id, 'full' );
		if ( ! empty( $logo_src[0] ) ) {
			return esc_url( function_exists( 'short_normalize_url' ) ? short_normalize_url( $logo_src[0] ) : $logo_src[0] );
		}
	}
	return 'https://i.postimg.cc/cH3CM5h4/image.png';
}

/**
 * Get Fallback Drama Vertical Poster URL (Admin Uploaded or Theme Asset)
 */
function short_get_default_poster_url() {
	$brand_settings = get_option( 'short_brand_settings', array() );
	if ( ! empty( $brand_settings['fallback_poster_url'] ) ) {
		$url = $brand_settings['fallback_poster_url'];
		if ( false !== strpos( $url, 'http://' ) || false !== strpos( $url, 'https://' ) || 0 === strpos( $url, '//' ) ) {
			return esc_url( $url );
		}
		return esc_url( function_exists( 'short_normalize_url' ) ? short_normalize_url( $url ) : $url );
	}
	return esc_url( get_template_directory_uri() . '/assets/images/fallback-portrait.svg' );
}

function short_custom_rewrite_rules() {
	add_rewrite_rule( '^movie/([0-9]+)(?:-[^/]+)?/?$', 'index.php?short_type=movie&short_id=$matches[1]', 'top' );
	add_rewrite_rule( '^tv/([0-9]+)(?:-[^/]+)?/?$', 'index.php?short_type=tv&short_id=$matches[1]', 'top' );
	add_rewrite_rule( '^watch/([0-9]+)(?:/season-([0-9]+)/episode-([0-9]+))?/?$', 'index.php?short_watch=1&short_id=$matches[1]&short_season=$matches[2]&short_episode=$matches[3]', 'top' );
	add_rewrite_rule( '^movies/?$', 'index.php?short_movies=1', 'top' );
	add_rewrite_rule( '^series/?$', 'index.php?short_shows=1', 'top' );
	add_rewrite_rule( '^shows/?$', 'index.php?short_shows=1', 'top' );
	add_rewrite_rule( '^tv-shows/?$', 'index.php?short_shows=1', 'top' );
	add_rewrite_rule( '^genres/?$', 'index.php?short_genre_all=1', 'top' );
	add_rewrite_rule( '^genre/?$', 'index.php?short_genre_all=1', 'top' );
	add_rewrite_rule( '^genre/([0-9]+)(?:-[^/]+)?/?$', 'index.php?short_genre=$matches[1]', 'top' );
	add_rewrite_rule( '^genre/([a-zA-Z0-9_-]+)/?$', 'index.php?short_genre_slug=$matches[1]', 'top' );
	add_rewrite_rule( '^search/?$', 'index.php?short_search=1', 'top' );
	add_rewrite_rule( '^my-list/?$', 'index.php?short_my_list=1', 'top' );
	add_rewrite_rule( '^history/?$', 'index.php?short_history=1', 'top' );
	add_rewrite_rule( '^new-popular/?$', 'index.php?short_new_popular=1', 'top' );
	add_rewrite_rule( '^leaderboard/?$', 'index.php?short_leaderboard=1', 'top' );
	add_rewrite_rule( '^anime/?$', 'index.php?short_anime=1', 'top' );
	add_rewrite_rule( '^login/?$', 'index.php?short_auth=login', 'top' );
	add_rewrite_rule( '^register/?$', 'index.php?short_auth=register', 'top' );
	add_rewrite_rule( '^forgot-password/?$', 'index.php?short_auth=forgot', 'top' );
	add_rewrite_rule( '^auth/login/?$', 'index.php?short_auth=login', 'top' );
	add_rewrite_rule( '^auth/register/?$', 'index.php?short_auth=register', 'top' );
	add_rewrite_rule( '^auth/forgot(?:-password)?/?$', 'index.php?short_auth=forgot', 'top' );
	add_rewrite_rule( '^auth/?$', 'index.php?short_auth=login', 'top' );
	add_rewrite_rule( '^profile/?$', 'index.php?short_profile=1', 'top' );
	add_rewrite_rule( '^account/?$', 'index.php?short_account=1', 'top' );
	add_rewrite_rule( '^settings/?$', 'index.php?short_account=1', 'top' );
	add_rewrite_rule( '^subscription/?$', 'index.php?short_subscription=1', 'top' );
	add_rewrite_rule( '^plans/?$', 'index.php?short_subscription=1', 'top' );
	add_rewrite_rule( '^reward/?$', 'index.php?short_rewards=1', 'top' );
	add_rewrite_rule( '^rewards/?$', 'index.php?short_rewards=1', 'top' );
	add_rewrite_rule( '^notification/?$', 'index.php?short_notification=1', 'top' );
	add_rewrite_rule( '^notifications/?$', 'index.php?short_notification=1', 'top' );
}
add_action( 'init', 'short_custom_rewrite_rules' );

function short_check_flush_rewrites() {
	$ver = get_option( 'short_theme_rewrite_ver' );
	if ( $ver !== '1.5.2' ) {
		short_custom_rewrite_rules();
		flush_rewrite_rules( false );
		update_option( 'short_theme_rewrite_ver', '1.5.2' );
	}
}
add_action( 'init', 'short_check_flush_rewrites', 99 );

function short_is_kids_mode() {
	return isset( $_COOKIE['short_is_kid'] ) && '1' === (string) $_COOKIE['short_is_kid'];
}

/**
 * Automatically normalize localhost/127.0.0.1 asset URLs to the visitor's current host (e.g. LAN IP 192.168.x.x)
 * so uploaded images and posters load correctly on real mobile phones and tablets.
 */
function short_normalize_url( $url ) {
	if ( empty( $url ) || ! is_string( $url ) ) {
		return $url;
	}
	// If the URL contains /wp-content/, dynamically attach it to the current site host and strip any local subdirectories
	if ( false !== strpos( $url, '/wp-content/' ) ) {
		$content_path = substr( $url, strpos( $url, '/wp-content/' ) );
		$site_root    = rtrim( site_url(), '/' );
		$subpath      = trim( parse_url( $site_root, PHP_URL_PATH ) ?? '', '/' );
		$is_https     = is_ssl() || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) || ( isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] );
		$proto        = $is_https ? 'https:' : ( isset( $_SERVER['REQUEST_SCHEME'] ) ? $_SERVER['REQUEST_SCHEME'] . ':' : '' );
		$host         = ! empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : ( parse_url( $site_root, PHP_URL_HOST ) ?: 'localhost' );
		
		if ( empty( $subpath ) ) {
			return ( $proto ? $proto : '' ) . '//' . $host . $content_path;
		} else {
			return $site_root . $content_path;
		}
	}
	if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
		$host     = $_SERVER['HTTP_HOST'];
		$is_https = is_ssl() || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] );
		$proto    = $is_https ? 'https:' : 'http:';
		// Match http://localhost, https://localhost, //localhost, http://127.0.0.1, etc.
		$url      = preg_replace( '#^(?:https?:)?//(?:localhost|127\.0\.0\.1)(?::[0-9]+)?/#i', $proto . '//' . $host . '/', $url );
	}
	return $url;
}
add_filter( 'wp_get_attachment_url', 'short_normalize_url', 99 );
add_filter( 'wp_get_attachment_image_src', function( $image ) {
	if ( is_array( $image ) && ! empty( $image[0] ) ) {
		$image[0] = short_normalize_url( $image[0] );
	}
	return $image;
}, 99 );
add_filter( 'post_thumbnail_html', 'short_normalize_url', 99 );

/**
 * Calculate Drama Card Badge (VIP, Time-based NEW for 15 days, HOT/TRENDING, or empty)
 *
 * @param array|int $drama_or_post
 * @param bool $is_vip
 * @param int $date_ts
 * @param int $views_num
 * @param int $likes_num
 * @return string
 */
function short_calculate_drama_badge( $is_vip = false, $date_ts = 0, $views_num = 0, $likes_num = 0 ) {
	if ( ! empty( $is_vip ) ) {
		return 'VIP';
	}

	$now = time();
	$fifteen_days = 15 * 86400; // 15 days in seconds

	// 1. If released within the last 15 days -> NEW
	if ( $date_ts > 0 && ( $now - $date_ts ) <= $fifteen_days && ( $now - $date_ts ) >= 0 ) {
		return 'NEW';
	}

	// 2. If high engagement/trending -> HOT
	if ( $views_num >= 10000 || $likes_num >= 500 ) {
		return 'HOT';
	}

	// 3. Otherwise no badge (clean poster presentation)
	return '';
}

/**
 * Curated Premium Short Drama Seed / Fallback Collection
 * Provides high quality visual cards, banners, and mockups when no custom uploads are made yet.
 *
 * @return array
 */
function short_get_curated_fallback_dramas() {
	return array();
}

function short_get_current_nav_section() {
	$uri = strtolower( trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' ) );
	$site_path = strtolower( trim( parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' ) );

	if ( ! empty( $site_path ) && 0 === strpos( $uri, $site_path ) ) {
		$uri = trim( substr( $uri, strlen( $site_path ) ), '/' );
	}
	$uri = preg_replace( '#^index\.php/?#i', '', $uri );

	if ( get_query_var( 'short_movies' ) || 0 === strpos( $uri, 'movies' ) || 0 === strpos( $uri, 'movie' ) ) {
		return 'movies';
	}
	if ( get_query_var( 'short_shows' ) || 0 === strpos( $uri, 'series' ) || 0 === strpos( $uri, 'shows' ) || 0 === strpos( $uri, 'tv-shows' ) || 0 === strpos( $uri, 'tv' ) ) {
		return 'series';
	}
	if ( is_page( 'new-popular' ) || is_page_template( 'page-new-popular.php' ) || get_query_var( 'short_new_popular' ) || 0 === strpos( $uri, 'new-popular' ) || 0 === strpos( $uri, 'popular' ) ) {
		return 'new-popular';
	}
	if ( is_page( 'leaderboard' ) || is_page( 'ranking' ) || is_page( 'rankings' ) || is_page_template( 'page-leaderboard.php' ) || get_query_var( 'short_leaderboard' ) || 0 === strpos( $uri, 'leaderboard' ) || 0 === strpos( $uri, 'ranking' ) ) {
		return 'leaderboard';
	}
	if ( is_page( 'anime' ) || is_page_template( 'page-anime.php' ) || get_query_var( 'short_anime' ) || 0 === strpos( $uri, 'anime' ) ) {
		return 'anime';
	}
	if ( is_page( 'my-list' ) || is_page_template( 'page-my-list.php' ) || get_query_var( 'short_my_list' ) || 0 === strpos( $uri, 'my-list' ) ) {
		return 'my-list';
	}
	if ( is_page( 'search' ) || is_page_template( 'page-search.php' ) || get_query_var( 'short_search' ) || 0 === strpos( $uri, 'search' ) ) {
		return 'search';
	}
	if ( is_page( 'reward' ) || is_page( 'rewards' ) || is_page_template( 'page-reward.php' ) || get_query_var( 'short_rewards' ) || 0 === strpos( $uri, 'reward' ) || 0 === strpos( $uri, 'rewards' ) ) {
		return 'rewards';
	}
	if ( is_page( 'account' ) || is_page( 'settings' ) || is_page( 'profile' ) || is_page_template( 'page-account.php' ) || is_page_template( 'page-profile.php' ) || get_query_var( 'short_profile' ) || get_query_var( 'short_account' ) || 0 === strpos( $uri, 'account' ) || 0 === strpos( $uri, 'settings' ) || 0 === strpos( $uri, 'profile' ) ) {
		return 'account';
	}
	if ( is_page( 'subscription' ) || is_page_template( 'page-subscription.php' ) || get_query_var( 'short_subscription' ) || 0 === strpos( $uri, 'subscription' ) || 0 === strpos( $uri, 'plans' ) ) {
		return 'subscription';
	}
	if ( is_page( 'genre' ) || is_page( 'genres' ) || is_page( 'discover' ) || is_page( 'discovery' ) || is_page( 'categories' ) || is_page_template( 'page-genre.php' ) || is_tax( 'video_genre' ) || is_tax( 'genre' ) || get_query_var( 'short_genre_slug' ) || get_query_var( 'short_genre' ) || get_query_var( 'short_genre_all' ) || 0 === strpos( $uri, 'genre' ) || 0 === strpos( $uri, 'genres' ) || 0 === strpos( $uri, 'discover' ) || 0 === strpos( $uri, 'categories' ) ) {
		$g_slug = get_query_var( 'short_genre_slug' );
		if ( empty( $g_slug ) ) {
			$queried = get_queried_object();
			if ( $queried instanceof WP_Term ) {
				$g_slug = $queried->slug;
			}
		}
		if ( empty( $g_slug ) ) {
			$g_slug = get_query_var( 'video_genre' ) ?: get_query_var( 'term' );
		}
		if ( empty( $g_slug ) && ( 0 === strpos( $uri, 'genre/' ) || 0 === strpos( $uri, 'genres/' ) ) ) {
			$g_slug = trim( substr( $uri, strlen( 'genre/' ) ), '/' );
		}
		if ( ! empty( $g_slug ) ) {
			return sanitize_title( $g_slug );
		}
		return 'categories';
	}

	if ( is_front_page() || is_home() || empty( $uri ) || $uri === 'index.php' ) {
		return 'home';
	}

	return '';
}

/**
 * Universal Navigation Section Active Checker
 */
function short_is_nav_section_active( $tab_slug, $tab_url, $tab_title, $active_nav, $current_uri ) {
	$tab_slug  = strtolower( trim( (string) $tab_slug ) );
	$tab_title = strtolower( trim( (string) $tab_title ) );
	$tab_path  = strtolower( trim( parse_url( (string) $tab_url, PHP_URL_PATH ) ?: '', '/' ) );
	$site_path = strtolower( trim( parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' ) );

	if ( ! empty( $site_path ) && 0 === strpos( $tab_path, $site_path ) ) {
		$tab_path = trim( substr( $tab_path, strlen( $site_path ) ), '/' );
	}
	$tab_path = preg_replace( '#^index\.php/?#i', '', $tab_path );

	// Exact path match
	if ( ! empty( $tab_path ) && $tab_path === $current_uri ) {
		return true;
	}
	if ( empty( $tab_path ) && ( empty( $current_uri ) || 'home' === $active_nav || is_front_page() ) && in_array( $tab_slug, array( 'home', 'mobile_home' ), true ) ) {
		return true;
	}

	// Active section aliasing
	if ( 'home' === $active_nav || is_front_page() ) {
		return in_array( $tab_slug, array( 'home', 'mobile_home' ), true ) || empty( $tab_path );
	}
	if ( in_array( $active_nav, array( 'categories', 'genre', 'genres', 'discover', 'discovery' ), true ) || ( ! empty( $current_uri ) && ( 0 === strpos( $current_uri, 'genre' ) || 0 === strpos( $current_uri, 'discover' ) || 0 === strpos( $current_uri, 'categories' ) ) ) ) {
		return in_array( $tab_slug, array( 'categories', 'genre', 'genres', 'discover', 'discovery', 'mobile_discovery' ), true )
			|| false !== strpos( $tab_path, 'genre' )
			|| false !== strpos( $tab_path, 'discover' )
			|| false !== strpos( $tab_path, 'categories' )
			|| false !== strpos( $tab_title, 'discover' )
			|| false !== strpos( $tab_title, 'genre' )
			|| false !== strpos( $tab_title, 'categorie' );
	}
	if ( in_array( $active_nav, array( 'leaderboard', 'ranking', 'rankings', 'mobile_leaderboard' ), true ) || ( ! empty( $current_uri ) && ( 0 === strpos( $current_uri, 'leaderboard' ) || 0 === strpos( $current_uri, 'ranking' ) ) ) ) {
		return in_array( $tab_slug, array( 'leaderboard', 'ranking', 'rankings', 'mobile_leaderboard' ), true )
			|| false !== strpos( $tab_path, 'leaderboard' )
			|| false !== strpos( $tab_path, 'ranking' )
			|| false !== strpos( $tab_title, 'leaderboard' )
			|| false !== strpos( $tab_title, 'ranking' );
	}
	if ( in_array( $active_nav, array( 'new-popular', 'popular', 'mobile_popular' ), true ) || ( ! empty( $current_uri ) && 0 === strpos( $current_uri, 'new-popular' ) ) ) {
		return in_array( $tab_slug, array( 'new-popular', 'popular', 'mobile_popular' ), true )
			|| false !== strpos( $tab_path, 'new-popular' )
			|| false !== strpos( $tab_path, 'popular' )
			|| false !== strpos( $tab_title, 'popular' )
			|| false !== strpos( $tab_title, 'new' );
	}
	if ( in_array( $active_nav, array( 'rewards', 'reward', 'mobile_rewards' ), true ) || ( ! empty( $current_uri ) && 0 === strpos( $current_uri, 'reward' ) ) ) {
		return in_array( $tab_slug, array( 'rewards', 'reward', 'mobile_rewards' ), true )
			|| false !== strpos( $tab_path, 'reward' )
			|| false !== strpos( $tab_title, 'reward' );
	}
	if ( in_array( $active_nav, array( 'account', 'profile', 'settings', 'mobile_account' ), true ) || ( ! empty( $current_uri ) && ( 0 === strpos( $current_uri, 'account' ) || 0 === strpos( $current_uri, 'profile' ) || 0 === strpos( $current_uri, 'settings' ) ) ) ) {
		return in_array( $tab_slug, array( 'account', 'profile', 'settings', 'mobile_account' ), true )
			|| false !== strpos( $tab_path, 'account' )
			|| false !== strpos( $tab_path, 'profile' )
			|| false !== strpos( $tab_title, 'account' );
	}

	return ( $tab_slug === $active_nav );
}

/**
 * Render Header Primary Navigation
 * Supports WordPress Appearance > Menus (Primary Navigation), with Anime & Netflix defaults, keeping My List fixed.
 */
function short_render_primary_nav() {
	$active_nav  = function_exists( 'short_get_current_nav_section' ) ? short_get_current_nav_section() : ( is_front_page() ? 'home' : '' );
	$current_uri = strtolower( trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' ) );
	$site_path   = strtolower( trim( parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' ) );

	if ( ! empty( $site_path ) && 0 === strpos( $current_uri, $site_path ) ) {
		$current_uri = trim( substr( $current_uri, strlen( $site_path ) ), '/' );
	}
	$current_uri = preg_replace( '#^index\.php/?#i', '', $current_uri );

	// 1. Primary: Section Tabs config from Tabs & Icons Studio
	$tabs_config = function_exists( 'SHORT\\Core\\Admin\\Dashboard::get_section_tabs_config' )
		? \SHORT\Core\Admin\Dashboard::get_section_tabs_config()
		: get_option( 'short_section_tabs_config', array() );

	$desktop_tabs = ! empty( $tabs_config['desktop'] ) && is_array( $tabs_config['desktop'] )
		? $tabs_config['desktop']
		: array();

	if ( ! empty( $desktop_tabs ) ) {
		foreach ( $desktop_tabs as $tab ) {
			$slug  = $tab['target'] ?? 'home';
			$title = $tab['title'] ?? 'Tab';
			$url   = ! empty( $tab['url'] ) ? $tab['url'] : '';

			if ( 'my-list' === $slug || 'mylist' === $slug || false !== strpos( strtolower( (string) $url ), 'my-list' ) || strtolower( trim( $title ) ) === 'my list' ) {
				continue;
			}

			if ( 'leaderboard' === $slug || false !== strpos( strtolower( (string) $url ), 'leaderboard' ) || false !== strpos( strtolower( (string) $title ), 'leaderboard' ) ) {
				$url = home_url( '/leaderboard/' );
			} elseif ( empty( $url ) ) {
				if ( 'home' === $slug ) {
					$url = home_url( '/' );
				} elseif ( 'categories' === $slug || 'genre' === $slug || 'discover' === $slug ) {
					$url = home_url( '/genre/' );
				} elseif ( 'new-popular' === $slug ) {
					$url = home_url( '/new-popular/' );
				} else {
					$url = home_url( '/' . $slug . '/' );
				}
			}

			if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
				$url = home_url( $url );
			}

			$is_active = short_is_nav_section_active( $slug, $url, $title, $active_nav, $current_uri );

			printf(
				'<a href="%s" data-nav="%s" class="nav-item %s">%s</a>',
				esc_url( $url ),
				esc_attr( $slug ),
				$is_active ? 'active' : '',
				esc_html( $title )
			);
		}
		return;
	}

	// 2. Custom Header Navigation items from Short Stream Settings (Legacy Fallback)
	$custom_header_nav = get_option( 'short_header_nav_items', array() );

	if ( ! empty( $custom_header_nav ) && is_array( $custom_header_nav ) ) {
		foreach ( $custom_header_nav as $idx => $nav ) {
			if ( isset( $nav['enabled'] ) && empty( $nav['enabled'] ) ) {
				continue;
			}
			$title = ! empty( $nav['title'] ) ? $nav['title'] : 'Nav Item';
			$url   = ! empty( $nav['endpoint'] ) ? $nav['endpoint'] : ( ! empty( $nav['url'] ) ? $nav['url'] : home_url( '/' ) );

			if ( 'my-list' === ( $nav['key'] ?? '' ) || false !== strpos( strtolower( (string) $url ), 'my-list' ) || strtolower( trim( $title ) ) === 'my list' ) {
				continue;
			}

			if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
				$url = home_url( $url );
			}

			$key = ! empty( $nav['key'] ) ? $nav['key'] : sanitize_title( $title );
			$is_active = short_is_nav_section_active( $key, $url, $title, $active_nav, $current_uri );

			printf(
				'<a href="%s" data-nav="%s" class="nav-item %s">%s</a>',
				esc_url( $url ),
				esc_attr( $key ),
				$is_active ? 'active' : '',
				esc_html( $title )
			);
		}
		return;
	}

	// 3. Fallback to WP Appearance > Menus (if assigned)
	if ( has_nav_menu( 'primary' ) ) {
		$locations = get_nav_menu_locations();
		$menu      = isset( $locations['primary'] ) ? wp_get_nav_menu_object( $locations['primary'] ) : null;
		if ( $menu ) {
			$items = wp_get_nav_menu_items( $menu->term_id );
			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					if ( false !== strpos( strtolower( (string) $item->url ), 'my-list' ) || strtolower( trim( $item->title ) ) === 'my list' ) {
						continue;
					}

					$is_active = short_is_nav_section_active( '', $item->url, $item->title, $active_nav, $current_uri );

					$classes = array( 'nav-item' );
					if ( $is_active ) {
						$classes[] = 'active';
					}
					if ( ! empty( $item->classes ) && is_array( $item->classes ) ) {
						$classes = array_merge( $classes, array_filter( $item->classes ) );
					}

					printf(
						'<a href="%s" class="%s">%s</a>',
						esc_url( $item->url ),
						esc_attr( implode( ' ', $classes ) ),
						esc_html( $item->title )
					);
				}
				return;
			}
		}
	}

	// 4. Ultimate Built-in ShortTV Default Navigation Pills
	$default_pills = array(
		array( 'target' => 'home',        'title' => __( 'Home', 'short-stream' ),           'url' => home_url( '/' ) ),
		array( 'target' => 'categories',  'title' => __( 'Discover', 'short-stream' ),       'url' => home_url( '/genre/' ) ),
		array( 'target' => 'new-popular', 'title' => __( 'New and Popular', 'short-stream' ),'url' => home_url( '/new-popular/' ) ),
		array( 'target' => 'leaderboard', 'title' => __( 'Leaderboard', 'short-stream' ),    'url' => home_url( '/leaderboard/' ) ),
	);

	foreach ( $default_pills as $dp ) {
		$is_active = short_is_nav_section_active( $dp['target'], $dp['url'], $dp['title'], $active_nav, $current_uri );
		printf(
			'<a href="%s" data-nav="%s" class="nav-item %s">%s</a>',
			esc_url( $dp['url'] ),
			esc_attr( $dp['target'] ),
			$is_active ? 'active' : '',
			esc_html( $dp['title'] )
		);
	}
}

/**
 * Render Mobile Top Header Navigation
 * Configured in Homepage Section Builder -> Mobile: Top Header
 */
function short_render_mobile_top_header_nav() {
	$tabs_config = function_exists( 'SHORT\Core\Admin\Dashboard::get_section_tabs_config' )
		? \SHORT\Core\Admin\Dashboard::get_section_tabs_config()
		: get_option( 'short_section_tabs_config', array() );

	$saved_header_tabs = ! empty( $tabs_config['mobile_header'] ) && is_array( $tabs_config['mobile_header'] )
		? $tabs_config['mobile_header']
		: array();

	// Dynamic Auto-Select Mode: Automatically fetch all active terms from video_genre taxonomy
	$dynamic_genres = array();
	$terms = get_terms( array(
		'taxonomy'   => 'video_genre',
		'hide_empty' => false,
		'orderby'    => 'count',
		'order'      => 'DESC',
	) );

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$dynamic_genres[] = array(
				'title'  => $term->name,
				'target' => 'genre/' . $term->slug,
				'url'    => get_term_link( $term, 'video_genre' ),
			);
		}
	}

	// Use custom tabs if configured and not default single-tab, otherwise use live dynamic taxonomy genres
	$mobile_header_tabs = ( ! empty( $saved_header_tabs ) && count( $saved_header_tabs ) > 1 && ( $saved_header_tabs[0]['target'] ?? '' ) !== 'home' )
		? $saved_header_tabs
		: ( ! empty( $dynamic_genres ) ? $dynamic_genres : array() );

	// Robust fallback if no genres are created yet in database
	if ( empty( $mobile_header_tabs ) ) {
		$mobile_header_tabs = array(
			array( 'title' => 'Romance', 'target' => 'genre/romance', 'url' => home_url( '/genre/romance/' ) ),
			array( 'title' => 'Billionaire', 'target' => 'genre/billionaire', 'url' => home_url( '/genre/billionaire/' ) ),
			array( 'title' => 'CEO', 'target' => 'genre/ceo', 'url' => home_url( '/genre/ceo/' ) ),
			array( 'title' => 'Vampire', 'target' => 'genre/vampire', 'url' => home_url( '/genre/vampire/' ) ),
			array( 'title' => 'Drama', 'target' => 'genre/drama', 'url' => home_url( '/genre/drama/' ) ),
			array( 'title' => 'Fantasy', 'target' => 'genre/fantasy', 'url' => home_url( '/genre/fantasy/' ) ),
			array( 'title' => 'Urban', 'target' => 'genre/urban', 'url' => home_url( '/genre/urban/' ) ),
			array( 'title' => 'Counterattack', 'target' => 'genre/counterattack', 'url' => home_url( '/genre/counterattack/' ) ),
		);
	}

	$active_nav  = function_exists( 'short_get_current_nav_section' ) ? short_get_current_nav_section() : ( is_front_page() ? 'home' : '' );
	$current_uri = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' );

	echo '<div class="short-mobile-top-nav-track">';
	foreach ( $mobile_header_tabs as $tab ) {
		$slug  = $tab['target'] ?? '';
		$title = $tab['title'] ?? 'Genre';
		$url   = ! empty( $tab['url'] ) && ! is_wp_error( $tab['url'] ) ? $tab['url'] : '';

		if ( empty( $url ) ) {
			if ( 0 === strpos( $slug, 'genre/' ) ) {
				$url = home_url( '/' . trim( $slug, '/' ) . '/' );
			} else {
				$url = home_url( '/genre/' . sanitize_title( $slug ) . '/' );
			}
		}

		if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
			$url = home_url( $url );
		}

		$item_path = trim( parse_url( (string) $url, PHP_URL_PATH ) ?? '', '/' );
		$is_active = ( ! empty( $item_path ) && $item_path === $current_uri );

		if ( ! $is_active && ! empty( $active_nav ) ) {
			if ( $slug === $active_nav || false !== strpos( strtolower( (string) $url ), '/' . $active_nav ) || strtolower( trim( $title ) ) === strtolower( $active_nav ) ) {
				$is_active = true;
			}
		}

		printf(
			'<a href="%s" data-nav="%s" class="mobile-top-nav-link %s">%s</a>',
			esc_url( $url ),
			esc_attr( $slug ),
			$is_active ? 'active' : '',
			esc_html( $title )
		);
	}
	echo '</div>';
}

function short_render_mobile_bottom_nav() {
	$f_active_nav = function_exists( 'short_get_current_nav_section' ) ? short_get_current_nav_section() : ( is_front_page() ? 'home' : '' );
	$current_uri  = strtolower( trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' ) );
	$site_path    = strtolower( trim( parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' ) );

	if ( ! empty( $site_path ) && 0 === strpos( $current_uri, $site_path ) ) {
		$current_uri = trim( substr( $current_uri, strlen( $site_path ) ), '/' );
	}
	$current_uri = preg_replace( '#^index\.php/?#i', '', $current_uri );

	$tabs_config = function_exists( 'SHORT\\Core\\Admin\\Dashboard::get_section_tabs_config' )
		? \SHORT\Core\Admin\Dashboard::get_section_tabs_config()
		: get_option( 'short_section_tabs_config', array() );

	$mobile_tabs = ! empty( $tabs_config['mobile'] ) && is_array( $tabs_config['mobile'] ) ? $tabs_config['mobile'] : array();

	if ( ! empty( $mobile_tabs ) ) {
		$saved_items = array();
		$has_reward_tab = false;
		foreach ( $mobile_tabs as $mt ) {
			if ( in_array( $mt['target'] ?? '', array( 'mobile_rewards', 'rewards', 'reward' ), true ) ) {
				$has_reward_tab = true;
			}
		}
		if ( ! $has_reward_tab && count( $mobile_tabs ) >= 2 ) {
			array_splice( $mobile_tabs, 2, 0, array( array( 'target' => 'mobile_rewards', 'title' => __( 'Rewards', 'short-stream' ), 'icon' => 'dashicons-tickets-alt', 'url' => home_url( '/account/#reward' ) ) ) );
		}
		foreach ( $mobile_tabs as $mt ) {
			$t_target = $mt['target'] ?? '';
			$t_title  = $mt['title'] ?? '';
			$t_url    = $mt['url'] ?? '';

			if ( empty( $t_url ) || false !== strpos( $t_url, 'mobile_rewards' ) || false !== strpos( $t_url, 'mobile_discovery' ) || false !== strpos( $t_url, '/reward/' ) ) {
				if ( 'mobile_home' === $t_target || 'home' === $t_target ) {
					$t_url = home_url( '/' );
				} elseif ( 'mobile_discovery' === $t_target || 'categories' === $t_target ) {
					$t_url = home_url( '/genre/' );
				} elseif ( 'mobile_rewards' === $t_target || 'rewards' === $t_target || 'reward' === $t_target ) {
					$t_url = home_url( '/account/#reward' );
				} elseif ( 'mobile_leaderboard' === $t_target || 'leaderboard' === $t_target ) {
					$t_url = home_url( '/leaderboard/' );
				} elseif ( 'mobile_popular' === $t_target || 'new-popular' === $t_target ) {
					$t_url = home_url( '/new-popular/' );
				} elseif ( 'mobile_account' === $t_target || 'account' === $t_target ) {
					$t_url = home_url( '/account/' );
				} else {
					$t_url = home_url( '/' . trim( $t_target, '/' ) . '/' );
				}
			}

			$saved_items[] = array(
				'id'      => $t_target,
				'title'   => $t_title,
				'type'    => $t_target,
				'icon'    => $mt['icon'] ?? '',
				'url'     => $t_url,
				'enabled' => 1,
			);
		}
	} else {
		$saved_items = get_option( 'short_bottom_nav_sections', array() );
	}

	if ( empty( $saved_items ) || ! is_array( $saved_items ) ) {
		$tab_titles = function_exists( 'SHORT\\Core\\Admin\\Dashboard::get_section_tab_titles' )
			? \SHORT\Core\Admin\Dashboard::get_section_tab_titles()
			: get_option( 'short_section_tab_titles', array() );
		$saved_items = array(
			array( 'id' => 'mobile_home', 'title' => ! empty( $tab_titles['mobile_home'] ) ? $tab_titles['mobile_home'] : __( 'Home', 'short-stream' ), 'type' => 'mobile_home', 'url' => home_url( '/' ), 'enabled' => 1 ),
			array( 'id' => 'mobile_discovery', 'title' => ! empty( $tab_titles['mobile_discovery'] ) ? $tab_titles['mobile_discovery'] : __( 'Discover', 'short-stream' ), 'type' => 'mobile_discovery', 'url' => home_url( '/genre/' ), 'enabled' => 1 ),
			array( 'id' => 'mobile_rewards', 'title' => __( 'Rewards', 'short-stream' ), 'type' => 'mobile_rewards', 'url' => home_url( '/reward/' ), 'enabled' => 1 ),
			array( 'id' => 'mobile_leaderboard', 'title' => __( 'Ranking', 'short-stream' ), 'type' => 'mobile_leaderboard', 'url' => home_url( '/leaderboard/' ), 'enabled' => 1 ),
			array( 'id' => 'mobile_account', 'title' => ! empty( $tab_titles['mobile_account'] ) ? $tab_titles['mobile_account'] : __( 'Account', 'short-stream' ), 'type' => 'mobile_account', 'url' => home_url( '/account/' ), 'enabled' => 1 ),
		);
	}

	// Guarantee Reward button is at the exact center (position index 2 of 5)
	$has_reward = false;
	$reward_idx = -1;
	foreach ( $saved_items as $k => $it ) {
		$it_type = $it['type'] ?? ( $it['id'] ?? '' );
		if ( in_array( $it_type, array( 'mobile_rewards', 'rewards', 'reward' ), true ) ) {
			$has_reward = true;
			$reward_idx = $k;
			break;
		}
	}
	if ( ! $has_reward && count( $saved_items ) >= 2 ) {
		$mid_pos = min( 2, (int) floor( count( $saved_items ) / 2 ) );
		array_splice( $saved_items, $mid_pos, 0, array( array( 'id' => 'mobile_rewards', 'title' => __( 'Rewards', 'short-stream' ), 'type' => 'mobile_rewards', 'url' => home_url( '/reward/' ), 'enabled' => 1 ) ) );
	} elseif ( $has_reward && $reward_idx !== -1 && count( $saved_items ) === 5 && $reward_idx !== 2 ) {
		$rew_item = $saved_items[ $reward_idx ];
		unset( $saved_items[ $reward_idx ] );
		$saved_items = array_values( $saved_items );
		array_splice( $saved_items, 2, 0, array( $rew_item ) );
	}

	$default_icons = array(
		'home'               => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
		'mobile_home'        => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
		'shorttv'            => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>',
		'mobile_discovery'   => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>',
		'categories'         => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>',
		'leaderboard'        => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.45 1-1 1H8c-.55 0-1 .45-1 1v1c0 .55.45 1 1 1h8c.55 0 1-.45 1-1v-1c0-.55-.45-1-1-1h-1c-.55 0-1-.45-1-1v-2.34"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path></svg>',
		'mobile_leaderboard' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.45 1-1 1H8c-.55 0-1 .45-1 1v1c0 .55.45 1 1 1h8c.55 0 1-.45 1-1v-1c0-.55-.45-1-1-1h-1c-.55 0-1-.45-1-1v-2.34"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path></svg>',
		'rewards'            => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5" rx="1"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>',
		'mobile_rewards'     => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5" rx="1"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>',
		'reward'             => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5" rx="1"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>',
		'latest'             => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>',
		'series'             => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>',
		'movies'             => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="2" y1="7" x2="7" y2="7"></line><line x1="2" y1="17" x2="7" y2="17"></line><line x1="17" y1="17" x2="22" y2="17"></line><line x1="17" y1="7" x2="22" y2="7"></line></svg>',
		'search'             => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>',
		'notifications'      => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
		'notification'       => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
		'new-popular'        => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
		'popular'            => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
		'mobile_popular'     => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>',
		'my-list'            => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
		'account'            => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
		'mobile_account'     => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
	);

	echo '<nav class="short-mobile-bottom-nav" role="navigation" aria-label="' . esc_attr__( 'Mobile Bottom Navigation', 'short-stream' ) . '">';
	foreach ( $saved_items as $item ) {
		if ( isset( $item['enabled'] ) && empty( $item['enabled'] ) ) {
			continue;
		}
		$type  = $item['type'] ?? 'home';
		$title = ! empty( $item['title'] ) ? $item['title'] : ucfirst( $type );
		if ( in_array( $type, array( 'mobile_leaderboard', 'leaderboard' ), true ) || 'leaderboard' === strtolower( trim( $title ) ) ) {
			$title = __( 'Ranking', 'short-stream' );
		}
		$url   = ! empty( $item['url'] ) ? $item['url'] : home_url( '/' . ( $type === 'home' || $type === 'mobile_home' ? '' : trim( $type, '/' ) . '/' ) );

		if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
			$url = home_url( $url );
		}

		$is_active = short_is_nav_section_active( $type, $url, $title, $f_active_nav, $current_uri );

		$nav_key = sanitize_title( $type );
		$is_reward_tab = in_array( $type, array( 'mobile_rewards', 'rewards', 'reward' ), true );

		printf(
			'<a href="%s" data-nav="%s" class="mobile-nav-item %s %s %s">',
			esc_url( $url ),
			esc_attr( $nav_key ),
			$is_active ? 'active' : '',
			( 'account' === $type || 'mobile_account' === $type ) ? 'mobile-nav-account' : '',
			$is_reward_tab ? 'mobile-nav-reward' : ''
		);

		$is_account_item = in_array( $type, array( 'account', 'mobile_account', 'profile', 'mobile_profile' ), true )
			|| in_array( $nav_key, array( 'account', 'mobile_account', 'profile', 'mobile_profile' ), true )
			|| in_array( strtolower( trim( $title ) ), array( 'account', 'profile' ), true );

		if ( $is_account_item ) {
			echo '<span class="mobile-nav-icon-wrap mobile-nav-avatar-wrap" id="mobile-nav-account-icon-wrap" style="width:24px;height:24px;min-width:24px;min-height:24px;border-radius:50%;overflow:hidden;border:none;outline:none;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;padding:0;margin:0;">';
			echo '<svg id="mobile-nav-account-default-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
			echo '</span>';
			echo '<script>(function(){ var loggedIn = (localStorage.getItem("short_is_logged_in") === "1") && !!localStorage.getItem("short_user_email"); var p = localStorage.getItem("short_user_photo") || localStorage.getItem("shorttv_user_avatar") || localStorage.getItem("short_active_avatar"); var el = document.getElementById("mobile-nav-account-icon-wrap"); if(el && loggedIn && p && !p.includes("avatar-") && !p.includes("avatar_") && (p.startsWith("http") || p.startsWith("data:"))){ el.innerHTML = \'<img id="mobile-nav-account-avatar" src="\' + p + \'" alt="Account" class="mobile-nav-avatar-img" referrerpolicy="no-referrer" style="width:24px;height:24px;min-width:24px;min-height:24px;border-radius:50%;object-fit:cover;display:block;border:none;outline:none;padding:0;margin:0;">\'; } })();</script>';
		} elseif ( $is_reward_tab ) {
			echo '<span class="mobile-nav-reward-icon-wrap">';
			echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5" rx="1"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>';
			echo '</span>';
		} elseif ( 'notification' === $type || 'notifications' === $type ) {
			echo '<span class="mobile-nav-icon-wrap" style="position:relative; display:inline-flex; align-items:center; justify-content:center;">';
			echo $default_icons['notifications'];
			echo '<span class="mobile-nav-notif-badge notif-badge" style="display:none; position:absolute; top:-4px; right:-8px; font-size:0.6rem; padding:1px 4px; min-width:14px; text-align:center;">0</span>';
			echo '</span>';
		} else {
			echo $default_icons[ $type ] ?? ( $default_icons['home'] );
		}

		echo '<span>' . esc_html( $title ) . '</span>';
		echo '</a>';
	}
	echo '</nav>';
}

function short_custom_query_vars( $vars ) {
	$vars[] = 'short_type';
	$vars[] = 'short_id';
	$vars[] = 'short_watch';
	$vars[] = 'short_season';
	$vars[] = 'short_episode';
	$vars[] = 'short_movies';
	$vars[] = 'short_shows';
	$vars[] = 'short_genre';
	$vars[] = 'short_genre_slug';
	$vars[] = 'short_genre_all';
	$vars[] = 'short_search';
	$vars[] = 'short_my_list';
	$vars[] = 'short_history';
	$vars[] = 'short_new_popular';
	$vars[] = 'short_leaderboard';
	$vars[] = 'short_anime';
	$vars[] = 'short_auth';
	$vars[] = 'short_profile';
	$vars[] = 'short_account';
	$vars[] = 'short_subscription';
	$vars[] = 'short_notification';
	$vars[] = 'short_rewards';
	return $vars;
}
add_filter( 'query_vars', 'short_custom_query_vars' );

function short_template_redirect() {
	$uri = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' );
	$site_path = trim( parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' );
	if ( $site_path && 0 === strpos( $uri, $site_path ) ) {
		$uri = trim( substr( $uri, strlen( $site_path ) ), '/' );
	}
	$uri = preg_replace( '#^index\.php/?#i', '', $uri );
	$uri_first = explode( '/', $uri )[0] ?? '';

	if ( get_query_var( 'short_rewards' ) || in_array( $uri_first, array( 'reward', 'rewards' ), true ) ) {
		include SHORT_THEME_DIR . '/page-reward.php';
		exit;
	}
	if ( get_query_var( 'short_notification' ) || in_array( $uri_first, array( 'notification', 'notifications' ), true ) ) {
		include SHORT_THEME_DIR . '/page-notification.php';
		exit;
	}
	if ( get_query_var( 'short_subscription' ) || in_array( $uri_first, array( 'subscription', 'plans' ), true ) ) {
		include SHORT_THEME_DIR . '/page-subscription.php';
		exit;
	}
	if ( get_query_var( 'short_account' ) || in_array( $uri_first, array( 'account', 'settings' ), true ) ) {
		include SHORT_THEME_DIR . '/page-account.php';
		exit;
	}
	if ( get_query_var( 'short_watch' ) || 'watch' === $uri_first ) {
		include SHORT_THEME_DIR . '/page-watch.php';
		exit;
	}
	$type = get_query_var( 'short_type' );
	if ( 'movie' === $type || 'movie' === $uri_first ) {
		include SHORT_THEME_DIR . '/single-movie.php';
		exit;
	}
	if ( 'tv' === $type || 'tv' === $uri_first ) {
		include SHORT_THEME_DIR . '/single-tv.php';
		exit;
	}
	if ( is_tax( 'video_genre' ) || is_tax( 'genre' ) || get_query_var( 'short_genre_slug' ) || get_query_var( 'short_movies' ) || get_query_var( 'short_shows' ) || get_query_var( 'short_genre' ) || get_query_var( 'short_genre_all' ) || in_array( $uri_first, array( 'genre', 'genres', 'categories', 'movies', 'series', 'shows', 'tv-shows' ), true ) ) {
		include SHORT_THEME_DIR . '/page-genre.php';
		exit;
	}
	if ( get_query_var( 'short_search' ) || 'search' === $uri_first ) {
		include SHORT_THEME_DIR . '/page-search.php';
		exit;
	}
	if ( get_query_var( 'short_my_list' ) || 'my-list' === $uri_first ) {
		include SHORT_THEME_DIR . '/page-my-list.php';
		exit;
	}
	if ( get_query_var( 'short_history' ) || 'history' === $uri_first ) {
		include SHORT_THEME_DIR . '/page-history.php';
		exit;
	}
	if ( get_query_var( 'short_new_popular' ) || in_array( $uri_first, array( 'new-popular', 'new_popular', 'popular' ), true ) ) {
		include SHORT_THEME_DIR . '/page-new-popular.php';
		exit;
	}
	if ( get_query_var( 'short_leaderboard' ) || in_array( $uri_first, array( 'leaderboard', 'rankings', 'top10' ), true ) ) {
		include SHORT_THEME_DIR . '/page-leaderboard.php';
		exit;
	}
	if ( get_query_var( 'short_anime' ) || 'anime' === $uri_first ) {
		include SHORT_THEME_DIR . '/page-anime.php';
		exit;
	}
	$auth = get_query_var( 'short_auth' );
	if ( in_array( $auth, array( 'login', 'register', 'forgot' ), true ) || in_array( $uri_first, array( 'login', 'register', 'forgot-password', 'auth' ), true ) ) {
		include SHORT_THEME_DIR . '/page-auth.php';
		exit;
	}
	if ( get_query_var( 'short_profile' ) || 'profile' === $uri_first ) {
		include SHORT_THEME_DIR . '/page-profile.php';
		exit;
	}
}
add_action( 'template_redirect', 'short_template_redirect' );

function short_body_classes( $classes ) {
	$current_sec = function_exists( 'short_get_current_nav_section' ) ? short_get_current_nav_section() : '';
	if ( get_query_var( 'short_auth' ) || 'auth' === $current_sec ) {
		$classes[] = 'page-auth';
	}
	if ( get_query_var( 'short_profile' ) || 'profile' === $current_sec ) {
		$classes[] = 'page-whos-watching';
	}
	if ( get_query_var( 'short_account' ) || 'account' === $current_sec || 'settings' === $current_sec ) {
		$classes[] = 'page-account-settings';
	}
	if ( get_query_var( 'short_subscription' ) || 'subscription' === $current_sec ) {
		$classes[] = 'page-subscription-plan';
	}
	if ( get_query_var( 'short_notification' ) || 'notification' === $current_sec ) {
		$classes[] = 'page-notifications-center';
	}
	return $classes;
}
add_filter( 'body_class', 'short_body_classes' );

function short_enqueue_scripts() {
	wp_enqueue_style( 'short-google-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap', array(), null );
	wp_enqueue_style( 'short-main-style', SHORT_THEME_URI . '/assets/css/main.css', array(), SHORT_THEME_VERSION );
	wp_enqueue_style( 'short-player-style', SHORT_THEME_URI . '/assets/css/player.css', array(), SHORT_THEME_VERSION );
	wp_enqueue_style( 'short-vertical-player-style', SHORT_THEME_URI . '/assets/css/vertical-player.css', array( 'short-main-style' ), SHORT_THEME_VERSION );

	wp_enqueue_script( 'firebase-app', 'https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js', array(), null, true );
	wp_enqueue_script( 'firebase-auth', 'https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js', array( 'firebase-app' ), null, true );
	wp_enqueue_script( 'firebase-database', 'https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js', array( 'firebase-app' ), null, true );

	wp_enqueue_script( 'short-app', SHORT_THEME_URI . '/assets/js/app.js', array( 'firebase-app', 'firebase-auth', 'firebase-database' ), SHORT_THEME_VERSION, true );

	// HLS.js for adaptive bitrate streaming (loaded on all pages but only activated on watch page)
	wp_enqueue_script( 'hls-js', 'https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js', array(), '1.5.17', true );

	// Native video player module (depends on app.js for saveWatchProgress and hls.js for streaming)
	wp_enqueue_script( 'short-player', SHORT_THEME_URI . '/assets/js/short-player.js', array( 'short-app', 'hls-js' ), SHORT_THEME_VERSION, true );

	$firebase_settings     = ( get_option( 'short_firebase_settings', array() ) ?: get_option( 'short_firebase_settings', array() ) );
	$player_settings       = ( get_option( 'short_player_settings', array() ) ?: get_option( 'short_player_settings', array() ) );
	$subscription_settings = ( get_option( 'short_subscription_settings', array() ) ?: get_option( 'short_subscription_settings', array() ) );

	$default_fallback_poster = function_exists( 'short_get_default_poster_url' ) ? short_get_default_poster_url() : get_template_directory_uri() . '/assets/images/fallback-portrait.svg';

	$drama_posters_map = array();
	$all_dramas = get_posts( array(
		'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => 200,
	) );
	if ( ! empty( $all_dramas ) ) {
		foreach ( $all_dramas as $d_p ) {
			$d_schema = class_exists( '\SHORT\Core\CPT\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::get_series_schema( $d_p->ID ) : array();
			$raw_v_poster = ( ! empty( $d_schema['cover_assets']['vertical_poster'] ) ? $d_schema['cover_assets']['vertical_poster'] : '' )
				?: ( get_post_meta( $d_p->ID, '_shorttv_vertical_poster', true ) ?: ( get_post_meta( $d_p->ID, '_short_poster_url', true ) ?: get_the_post_thumbnail_url( $d_p->ID, 'full' ) ) );
			$norm_v_poster = function_exists( 'short_normalize_url' ) ? short_normalize_url( $raw_v_poster ) : $raw_v_poster;
			$drama_posters_map[ (string) $d_p->ID ] = array(
				'title'     => $d_p->post_title,
				'poster'    => $norm_v_poster ?: $default_fallback_poster,
				'watch_url' => home_url( '/watch/' . $d_p->ID . '/' ),
			);
			if ( ! empty( $d_p->post_name ) ) {
				$drama_posters_map[ $d_p->post_name ] = $drama_posters_map[ (string) $d_p->ID ];
			}
		}
	}

	$system_notifs = class_exists( 'SHORT\Core\Admin\Dashboard' )
		? \SHORT\Core\Admin\Dashboard::get_notifications()
		: get_option( 'short_system_notifications', array() );

	wp_localize_script( 'short-app', 'SHORT_CONFIG', array(
		'rest_url'        => esc_url_raw( rest_url( 'short/v1' ) ),
		'ajax_url'        => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
		'nonce'           => wp_create_nonce( 'wp_rest' ),
		'site_url'        => esc_url_raw( home_url() ),
		'fallback_poster' => esc_url_raw( $default_fallback_poster ),
		'drama_map'       => $drama_posters_map,
		'notifications'   => is_array( $system_notifs ) ? $system_notifs : array(),
		'firebase'   => array(
			'apiKey'            => $firebase_settings['api_key'] ?? '',
			'authDomain'        => $firebase_settings['auth_domain'] ?? '',
			'projectId'         => $firebase_settings['project_id'] ?? '',
			'databaseURL'       => $firebase_settings['database_url'] ?? 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app',
			'storageBucket'     => $firebase_settings['storage_bucket'] ?? '',
			'messagingSenderId' => $firebase_settings['messaging_sender_id'] ?? '',
			'appId'             => $firebase_settings['app_id'] ?? '',
		),
		'player'     => array(
			'auto_play'          => ! empty( $player_settings['auto_play'] ),
			'auto_next'          => ! empty( $player_settings['auto_next'] ),
			'skip_intro_time'    => intval( $player_settings['skip_intro_time'] ?? 85 ),
			'tracking_interval'  => intval( $player_settings['tracking_interval'] ?? 15 ),
		),
		'subscription' => array(
			'enable_paywall'      => ! empty( $subscription_settings['enable_paywall'] ),
			'currency_symbol'     => $subscription_settings['currency_symbol'] ?? '$',
			'plan_basic_price'    => $subscription_settings['plan_basic_price'] ?? '9.99',
			'plan_standard_price' => $subscription_settings['plan_standard_price'] ?? '15.49',
			'plan_premium_price'  => $subscription_settings['plan_premium_price'] ?? '19.99',
			'payment_mode'        => $subscription_settings['payment_mode'] ?? 'free_instant',
			'subscription_page'   => esc_url( home_url( '/subscription/' ) ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'short_enqueue_scripts' );

/**
 * Get the current user's subscription tier.
 * Checks Firebase subscription data stored in user meta or cookie.
 *
 * @since 1.1.0
 * @return string Tier: 'free', 'basic', 'standard', or 'premium'.
 */
function short_get_user_tier() {
	// Check cookie set by Firebase subscription handler in app.js
	if ( isset( $_COOKIE['short_sub_tier'] ) ) {
		$tier = sanitize_key( $_COOKIE['short_sub_tier'] );
		if ( in_array( $tier, array( 'basic', 'standard', 'premium' ), true ) ) {
			return $tier;
		}
	}

	// Check WP user meta (if admin sets tier manually)
	if ( is_user_logged_in() ) {
		$user_tier = get_user_meta( get_current_user_id(), '_short_subscription_tier', true );
		if ( ! empty( $user_tier ) && in_array( $user_tier, array( 'basic', 'standard', 'premium' ), true ) ) {
			return $user_tier;
		}
	}

	return 'free';
}

/**
 * Dynamic PWA endpoints for Manifest and Service Worker
 */
function short_pwa_endpoints() {
	if ( isset( $_GET['short-manifest'] ) && '1' === $_GET['short-manifest'] ) {
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		$site_title = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : 'Short Stream';
		$site_desc  = get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : 'Modern streaming platform for short dramas, movies and series';
		$base_path  = wp_make_link_relative( trailingslashit( home_url( '/' ) ) );
		$wp_icon_192 = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 192 ) : '';
		$wp_icon_512 = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 512 ) : '';
		$icon_192    = $wp_icon_192 ? esc_url_raw( $wp_icon_192 ) : esc_url_raw( trailingslashit( SHORT_THEME_URI ) . 'assets/images/icon-192.png' );
		$icon_512    = $wp_icon_512 ? esc_url_raw( $wp_icon_512 ) : esc_url_raw( trailingslashit( SHORT_THEME_URI ) . 'assets/images/icon-512.png' );

		$manifest = array(
			'name'                        => $site_title,
			'short_name'                  => 'ShortTV',
			'description'                 => $site_desc,
			'start_url'                   => $base_path,
			'scope'                       => $base_path,
			'id'                          => $base_path,
			'display'                     => 'standalone',
			'background_color'            => '#121212',
			'theme_color'                 => '#ff2d55',
			'orientation'                 => 'portrait',
			'categories'                  => array( 'entertainment', 'video' ),
			'prefer_related_applications' => false,
			'icons'                       => array(
				array(
					'src'     => $icon_192,
					'sizes'   => '192x192',
					'type'    => 'image/png',
					'purpose' => 'any',
				),
				array(
					'src'     => $icon_192,
					'sizes'   => '192x192',
					'type'    => 'image/png',
					'purpose' => 'maskable',
				),
				array(
					'src'     => $icon_512,
					'sizes'   => '512x512',
					'type'    => 'image/png',
					'purpose' => 'any',
				),
				array(
					'src'     => $icon_512,
					'sizes'   => '512x512',
					'type'    => 'image/png',
					'purpose' => 'maskable',
				),
			),
		);

		echo wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	if ( isset( $_GET['short-sw'] ) && '1' === $_GET['short-sw'] ) {
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		$sw_file = SHORT_THEME_DIR . '/assets/pwa/service-worker.js';
		if ( file_exists( $sw_file ) ) {
			readfile( $sw_file );
		}
		exit;
	}
}
add_action( 'init', 'short_pwa_endpoints' );

function short_register_pwa_header() {
	$base_path    = wp_make_link_relative( trailingslashit( home_url( '/' ) ) );
	$icon_id      = get_option( 'site_icon', 0 );
	$manifest_url = esc_url( add_query_arg( array( 'short-manifest' => '1', 'v' => $icon_id ? $icon_id : time() ), home_url( '/' ) ) );
	$sw_url       = esc_url( add_query_arg( 'short-sw', '1', home_url( '/' ) ) );
	$wp_icon_url  = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 192 ) : '';
	$icon_url     = $wp_icon_url ? esc_url( $wp_icon_url ) : esc_url( trailingslashit( SHORT_THEME_URI ) . 'assets/images/icon-192.png' );
	?>
	<link rel="manifest" href="<?php echo esc_url( $manifest_url ); ?>">
	<meta name="theme-color" content="#121212">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="ShortTV">
	<link rel="apple-touch-icon" href="<?php echo esc_url( $icon_url ); ?>">
	<script>
		window.deferredPrompt = null;
		window.addEventListener('beforeinstallprompt', function(e) {
			e.preventDefault();
			window.deferredPrompt = e;
			console.log('[PWA] beforeinstallprompt captured, app is ready to install');
			var pwaBanner = document.getElementById('short-pwa-install-toast');
			if (pwaBanner && !window.matchMedia('(display-mode: standalone)').matches) {
				pwaBanner.style.display = 'flex';
			}
		});

		window.shortPromptPwaInstall = function() {
			if (window.deferredPrompt) {
				window.deferredPrompt.prompt();
				window.deferredPrompt.userChoice.then(function(choice) {
					if (choice.outcome === 'accepted') {
						console.log('[PWA] User accepted install');
					}
					window.deferredPrompt = null;
					var pwaBanner = document.getElementById('short-pwa-install-toast');
					if (pwaBanner) pwaBanner.style.display = 'none';
				});
			} else {
				var isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
				var msg = isIos 
					? 'To install ShortTV on iOS:<br><br>1. Tap the <strong>Share</strong> button in Safari (icon at the bottom).<br>2. Scroll and select <strong>"Add to Home Screen"</strong>.'
					: 'To install ShortTV App:<br><br>Tap your browser menu (<strong>&#8942;</strong> or install icon in address bar) and select <strong>"Install app"</strong> or <strong>"Add to Home Screen"</strong>.';
				
				if (typeof window.shortCustomAlert === 'function') {
					window.shortCustomAlert({
						title: 'Install ShortTV App',
						message: msg,
						confirmText: 'Got It'
					});
				} else {
					alert(isIos 
						? 'To install ShortTV on iOS:\n1. Tap the Share button in Safari.\n2. Tap "Add to Home Screen".' 
						: 'To install ShortTV App:\nTap your browser menu and select "Install app" or "Add to Home Screen".');
				}
			}
		};

		if ('serviceWorker' in navigator) {
			window.addEventListener('load', function() {
				var swUrl = <?php echo wp_json_encode( $sw_url ); ?>;
				var swScope = <?php echo wp_json_encode( $base_path ); ?>;
				navigator.serviceWorker.register(swUrl, { scope: swScope })
					.then(function(reg) {
						console.log('[PWA] ServiceWorker registered successfully, scope:', reg.scope);
					}).catch(function(err) {
						console.warn('[PWA] ServiceWorker registration warning:', err);
					});
			});
		}
	</script>
	<?php
}
add_action( 'wp_head', 'short_register_pwa_header' );

/**
 * Search all ShortTV Dramas (and optional TMDB titles)
 */
if ( ! function_exists( 'short_execute_search' ) ) {
	function short_execute_search( $query, $page = 1, $is_kids = false ) {
		$results = array();
		$matched_post_ids = array();

		if ( ! empty( $query ) ) {
			// 1. Primary search: short_title posts by title/content
			$dramas_by_text = new WP_Query( array(
				'post_type'      => 'short_title',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				's'              => $query,
				'posts_per_page' => 20,
				'fields'         => 'ids',
			) );
			if ( ! empty( $dramas_by_text->posts ) ) {
				$matched_post_ids = array_merge( $matched_post_ids, $dramas_by_text->posts );
			}

			// 2. Secondary search: match genres, schema, trope, overview
			$all_dramas = new WP_Query( array(
				'post_type'      => 'short_title',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
			) );
			$term_lower = strtolower( trim( $query ) );
			foreach ( $all_dramas->posts as $p ) {
				if ( in_array( $p->ID, $matched_post_ids, true ) ) {
					continue;
				}
				$schema = class_exists( '\\SHORT\\Core\\CPT\\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::get_series_schema( $p->ID ) : array();
				$genres = ! empty( $schema['genres'] ) ? (array) $schema['genres'] : array();
				$terms  = wp_get_post_terms( $p->ID, 'video_genre', array( 'fields' => 'names' ) );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$genres = array_unique( array_merge( $genres, $terms ) );
				}
				$haystack = strtolower( $p->post_title . ' ' . implode( ' ', $genres ) . ' ' . ( $schema['overview'] ?? '' ) );
				if ( false !== strpos( $haystack, $term_lower ) ) {
					$matched_post_ids[] = $p->ID;
				}
			}

			// 3. Format matched short dramas
			foreach ( $matched_post_ids as $pid ) {
				$schema = class_exists( '\\SHORT\\Core\\CPT\\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::get_series_schema( $pid ) : array();

				// Poster
				$poster = $schema['cover_assets']['vertical_poster'] ?? '';
				if ( empty( $poster ) ) {
					$poster = get_post_meta( $pid, '_shorttv_vertical_poster', true );
				}
				if ( empty( $poster ) && has_post_thumbnail( $pid ) ) {
					$poster = get_the_post_thumbnail_url( $pid, 'full' );
				}
				if ( empty( $poster ) ) {
					$poster = get_post_meta( $pid, '_short_poster_url', true );
				}
				if ( function_exists( 'short_normalize_url' ) ) {
					$poster = short_normalize_url( $poster );
				}

				// Backdrop
				$backdrop = $schema['cover_assets']['horizontal_banner'] ?? $poster;
				if ( empty( $backdrop ) ) {
					$backdrop = get_post_meta( $pid, '_shorttv_horizontal_banner', true );
				}
				if ( empty( $backdrop ) ) {
					$backdrop = get_post_meta( $pid, '_short_backdrop_url', true );
				}
				if ( function_exists( 'short_normalize_url' ) ) {
					$backdrop = short_normalize_url( $backdrop );
				}

				// Genres
				$genres = ! empty( $schema['genres'] ) ? (array) $schema['genres'] : array();
				$terms  = wp_get_post_terms( $pid, 'video_genre', array( 'fields' => 'names' ) );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$genres = array_unique( array_merge( $genres, $terms ) );
				}

				// Views & Rating
				$views_num = (int) ( get_post_meta( $pid, '_shorttv_view_count', true ) ?: ( $schema['analytics']['view_count'] ?? 0 ) );
				if ( $views_num >= 1000000 ) {
					$views_fmt = round( $views_num / 1000000, 1 ) . 'M';
				} elseif ( $views_num >= 1000 ) {
					$views_fmt = round( $views_num / 1000, 1 ) . 'K';
				} else {
					$views_fmt = (string) $views_num;
				}
				$rating = (float) ( get_post_meta( $pid, '_shorttv_rating', true ) ?: ( $schema['rating'] ?? 9.5 ) );
				$episodes_count = (int) ( $schema['total_episodes'] ?? ( ! empty( $schema['episodes'] ) ? count( (array) $schema['episodes'] ) : 1 ) );
				$is_vip = (bool) ( get_post_meta( $pid, '_shorttv_is_vip', true ) ?: ( $schema['is_vip'] ?? false ) );
				$date_ts = get_the_time( 'U', $pid ) ?: ( ! empty( $schema['release_year'] ) ? strtotime( (string) $schema['release_year'] . '-01-01' ) : 0 );
				$likes_num = (int) ( get_post_meta( $pid, '_shorttv_likes', true ) ?: ( $schema['analytics']['like_count'] ?? 0 ) );
				$badge = function_exists( 'short_calculate_drama_badge' ) ? short_calculate_drama_badge( $is_vip, $date_ts, $views_num, $likes_num ) : '';

				$results[] = array(
					'id'             => $pid,
					'media_type'     => 'drama',
					'is_drama'       => true,
					'badge'          => $badge,
					'title'          => get_the_title( $pid ) ?: ( $schema['title'] ?? 'Short Drama' ),
					'name'           => get_the_title( $pid ) ?: ( $schema['title'] ?? 'Short Drama' ),
					'poster_path'    => $poster,
					'poster'         => $poster,
					'backdrop_path'  => $backdrop,
					'backdrop'       => $backdrop,
					'release_date'   => (string) ( $schema['release_year'] ?? get_the_date( 'Y', $pid ) ),
					'first_air_date' => (string) ( $schema['release_year'] ?? get_the_date( 'Y', $pid ) ),
					'vote_average'   => $rating,
					'episodes_count' => $episodes_count,
					'genres'         => $genres,
					'genre_text'     => implode( ', ', $genres ),
					'overview'       => $schema['overview'] ?? wp_trim_words( get_post_field( 'post_excerpt', $pid ) ?: get_post_field( 'post_content', $pid ), 25 ),
					'views'          => $views_fmt,
					'watch_url'      => home_url( '/watch/' . $pid ),
					'url'            => home_url( '/watch/' . $pid ),
				);
			}
		}

		return array(
			'page'          => $page,
			'results'       => $results,
			'total_results' => count( $results ),
			'total_pages'   => 1,
		);
	}
}

/**
 * REST API Endpoints
 */
function short_register_rest_routes() {

	// GET /wp-json/short/v1/search?query=...
	register_rest_route( 'short/v1', '/search', array(
		'methods'             => 'GET',
		'callback'            => function ( WP_REST_Request $req ) {
			$query   = sanitize_text_field( $req->get_param( 'query' ) ?: '' );
			$page    = absint( $req->get_param( 'page' ) ?: 1 );
			$is_kids = ( function_exists( 'short_is_kids_mode' ) && short_is_kids_mode() ) || ( '1' === (string) $req->get_param( 'is_kids' ) );
			if ( empty( $query ) ) {
				return new WP_REST_Response( array( 'page' => 1, 'results' => array(), 'total_results' => 0, 'total_pages' => 1 ), 200 );
			}
			$data = short_execute_search( $query, $page, $is_kids );
			return new WP_REST_Response( $data, 200 );
		},
		'permission_callback' => '__return_true',
	) );

	// GET /wp-json/short/v1/comments/(?P<id>[0-9]+)
	register_rest_route( 'short/v1', '/comments/(?P<id>[0-9]+)', array(
		'methods'             => 'GET',
		'callback'            => function ( WP_REST_Request $req ) {
			$post_id      = absint( $req->get_param( 'id' ) );
			$curr_user_id = get_current_user_id();

			$comments = get_comments( array(
				'post_id' => $post_id,
				'status'  => 'approve',
				'order'   => 'ASC',
				'number'  => 200,
			) );

			$by_id = array();
			$roots = array();
			$total = count( $comments );

			foreach ( $comments as $c ) {
				$ep     = (int) get_comment_meta( $c->comment_ID, 'short_episode', true );
				$avatar = get_avatar_url( $c->user_id ? $c->user_id : $c->comment_author_email, array( 'size' => 96 ) );

				$by_id[ $c->comment_ID ] = array(
					'id'        => (int) $c->comment_ID,
					'parentId'  => (int) $c->comment_parent,
					'userName'  => ! empty( $c->comment_author ) ? $c->comment_author : __( 'Viewer', 'short-stream' ),
					'userId'    => (int) $c->user_id,
					'userPhoto' => $avatar,
					'text'      => $c->comment_content,
					'createdAt' => strtotime( $c->comment_date_gmt ?: $c->comment_date ) * 1000,
					'episode'   => $ep ?: 1,
					'isMe'      => ( $curr_user_id > 0 && (int) $c->user_id === $curr_user_id ),
					'replies'   => array(),
				);
			}

			foreach ( $by_id as $id => $item ) {
				$pid = $item['parentId'];
				if ( $pid > 0 && isset( $by_id[ $pid ] ) ) {
					$by_id[ $pid ]['replies'][] = &$by_id[ $id ];
				} else {
					$roots[] = &$by_id[ $id ];
				}
			}

			// Newest root comments first
			$roots = array_reverse( $roots );

			return new WP_REST_Response( array(
				'count'    => $total,
				'comments' => $roots,
			), 200 );
		},
		'permission_callback' => '__return_true',
	) );

	// POST /wp-json/short/v1/comments
	register_rest_route( 'short/v1', '/comments', array(
		'methods'             => 'POST',
		'callback'            => function ( WP_REST_Request $req ) {
			$post_id   = absint( $req->get_param( 'post_id' ) );
			$text      = sanitize_textarea_field( $req->get_param( 'text' ) );
			$episode   = absint( $req->get_param( 'episode' ) ) ?: 1;
			$parent_id = absint( $req->get_param( 'parent_id' ) ) ?: 0;

			if ( ! $post_id || empty( $text ) ) {
				return new WP_REST_Response( array( 'error' => 'Missing post_id or comment text' ), 400 );
			}

			$curr_user = wp_get_current_user();
			$user_id   = $curr_user->exists() ? $curr_user->ID : 0;
			$author    = $user_id ? $curr_user->display_name : sanitize_text_field( $req->get_param( 'userName' ) ?: 'Viewer' );
			$email     = $user_id ? $curr_user->user_email : 'guest_' . wp_generate_password( 6, false ) . '@local.test';

			$commentdata = array(
				'comment_post_ID'      => $post_id,
				'comment_author'       => $author,
				'comment_author_email' => $email,
				'comment_content'      => $text,
				'comment_type'         => 'comment',
				'comment_parent'       => $parent_id,
				'user_id'              => $user_id,
				'comment_approved'     => 1, // Auto-approve for seamless chat-like interaction
			);

			$comment_id = wp_insert_comment( $commentdata );
			if ( ! $comment_id ) {
				return new WP_REST_Response( array( 'error' => 'Failed to save comment' ), 500 );
			}

			// Store the episode badge number
			update_comment_meta( $comment_id, 'short_episode', $episode );

			$avatar    = get_avatar_url( $user_id ? $user_id : $email, array( 'size' => 96 ) );
			$new_count = get_comments_number( $post_id );

			return new WP_REST_Response( array(
				'success' => true,
				'comment' => array(
					'id'        => (int) $comment_id,
					'parentId'  => $parent_id,
					'userName'  => $author,
					'userId'    => $user_id,
					'userPhoto' => $avatar,
					'text'      => $text,
					'createdAt' => time() * 1000,
					'episode'   => $episode,
					'isMe'      => true,
					'replies'   => array(),
				),
				'count'   => (int) $new_count,
			), 200 );
		},
		'permission_callback' => '__return_true',
	) );

	// PUT /wp-json/short/v1/comments/(?P<id>[0-9]+) — Edit comment
	register_rest_route( 'short/v1', '/comments/(?P<id>[0-9]+)', array(
		'methods'             => 'PUT',
		'callback'            => function ( WP_REST_Request $req ) {
			$comment_id = absint( $req->get_param( 'id' ) );
			$text       = sanitize_textarea_field( $req->get_param( 'text' ) );
			$comment    = get_comment( $comment_id );

			if ( ! $comment ) {
				return new WP_REST_Response( array( 'error' => 'Comment not found' ), 404 );
			}
			if ( empty( $text ) ) {
				return new WP_REST_Response( array( 'error' => 'Comment text cannot be empty' ), 400 );
			}

			$curr_user_id = get_current_user_id();
			$is_admin     = current_user_can( 'moderate_comments' ) || current_user_can( 'manage_options' );
			$is_author    = ( $curr_user_id > 0 && (int) $comment->user_id === $curr_user_id );

			if ( ! $is_admin && ! $is_author && $comment->user_id > 0 ) {
				return new WP_REST_Response( array( 'error' => 'Permission denied' ), 403 );
			}

			wp_update_comment( array(
				'comment_ID'      => $comment_id,
				'comment_content' => $text,
			) );

			return new WP_REST_Response( array(
				'success' => true,
				'comment' => array(
					'id'   => $comment_id,
					'text' => $text,
				),
			), 200 );
		},
		'permission_callback' => '__return_true',
	) );

	// DELETE /wp-json/short/v1/comments/(?P<id>[0-9]+) — Delete comment
	register_rest_route( 'short/v1', '/comments/(?P<id>[0-9]+)', array(
		'methods'             => 'DELETE',
		'callback'            => function ( WP_REST_Request $req ) {
			$comment_id = absint( $req->get_param( 'id' ) );
			$comment    = get_comment( $comment_id );

			if ( ! $comment ) {
				return new WP_REST_Response( array( 'error' => 'Comment not found' ), 404 );
			}

			$curr_user_id = get_current_user_id();
			$is_admin     = current_user_can( 'moderate_comments' ) || current_user_can( 'manage_options' );
			$is_author    = ( $curr_user_id > 0 && (int) $comment->user_id === $curr_user_id );

			if ( ! $is_admin && ! $is_author && $comment->user_id > 0 ) {
				return new WP_REST_Response( array( 'error' => 'Permission denied' ), 403 );
			}

			$post_id = $comment->comment_post_ID;
			wp_delete_comment( $comment_id, true );

			return new WP_REST_Response( array(
				'success' => true,
				'count'   => (int) get_comments_number( $post_id ),
			), 200 );
		},
		'permission_callback' => '__return_true',
	) );

	// GET /wp-json/short/v1/user-stats
	register_rest_route( 'short/v1', '/user-stats', array(
		'methods'             => 'GET',
		'callback'            => function ( WP_REST_Request $req ) {
			$email = sanitize_email( $req->get_param( 'email' ) );
			$uid   = sanitize_text_field( $req->get_param( 'uid' ) );
			$user  = wp_get_current_user();
			$user_id = $user->exists() ? $user->ID : 0;

			if ( ! $user_id && $email ) {
				$found_user = get_user_by( 'email', $email );
				if ( $found_user ) {
					$user_id = $found_user->ID;
				}
			}

			$comments_count = 0;
			if ( $user_id ) {
				$comments_count = (int) get_comments( array(
					'user_id' => $user_id,
					'count'   => true,
				) );
			}
			if ( ! $comments_count && $email ) {
				$comments_count = (int) get_comments( array(
					'author_email' => $email,
					'count'        => true,
				) );
			}

			return new WP_REST_Response( array(
				'comments' => $comments_count,
				'success'  => true,
			), 200 );
		},
		'permission_callback' => '__return_true',
	) );
}
add_action( 'rest_api_init', 'short_register_rest_routes' );

/**
 * Fetch exactly N items across TMDB API pages (default 24 per page)
 */
function short_fetch_genre_items( $media = 'movie', $genre_id = 0, $page = 1, $per_page = 24 ) {
	$local_items = array();

	// 1. Fetch from Local WordPress ShortTV Drama CPT
	$cpt = class_exists( 'SHORT\Core\CPT\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::POST_TYPE : 'short_title';
	$tax_query = array();
	if ( $genre_id > 0 ) {
		$tax_query = array(
			'relation' => 'OR',
			array(
				'taxonomy' => 'genre',
				'field'    => 'term_id',
				'terms'    => $genre_id,
			),
			array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $genre_id,
			),
		);
	}

	$local_query = new \WP_Query( array(
		'post_type'      => array( $cpt, 'short_title', 'video' ),
		'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'tax_query'      => ! empty( $tax_query ) ? $tax_query : '',
	) );

	if ( $local_query->have_posts() ) {
		foreach ( $local_query->posts as $p ) {
			$schema = class_exists( 'SHORT\Core\CPT\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::get_series_schema( $p->ID ) : array();
			$poster = ! empty( $schema['poster'] ) ? $schema['poster'] : '';
			if ( empty( $poster ) ) {
				$poster = get_post_meta( $p->ID, '_short_poster_url', true )
					?: get_post_meta( $p->ID, '_short_poster', true )
					?: get_post_meta( $p->ID, '_short_backdrop_url', true )
					?: get_post_meta( $p->ID, '_short_backdrop', true )
					?: get_the_post_thumbnail_url( $p->ID, 'medium' )
					?: '';
			}
			$local_items[] = array(
				'id'          => $p->ID,
				'title'       => ! empty( $p->post_title ) ? $p->post_title : ( $schema['title'] ?? 'Short Drama' ),
				'name'        => ! empty( $p->post_title ) ? $p->post_title : ( $schema['title'] ?? 'Short Drama' ),
				'poster_path' => $poster,
				'is_short'    => true,
				'vote_average'=> $schema['rating'] ?? '5.0',
				'overview'    => ! empty( $p->post_excerpt ) ? $p->post_excerpt : ( $schema['overview'] ?? '' ),
			);
		}
	}

	return $local_items;
}

/**
 * Render standard Netflix-style poster card markup
 */
function short_render_card_simple_html( $item, $default_media = 'movie' ) {
	$item_title  = $item['title'] ?? $item['name'] ?? __( 'Untitled', 'short-stream' );
	$id          = $item['id'];
	$is_short    = ! empty( $item['is_short'] );
	$item_type   = $is_short ? 'watch' : ( $item['media_type'] ?? ( isset( $item['first_air_date'] ) ? 'tv' : ( isset( $item['title'] ) ? 'movie' : $default_media ) ) );
	$poster_path = $item['poster_path'] ?? '';

	if ( $poster_path && ( strpos( $poster_path, 'http' ) === 0 || strpos( $poster_path, '//' ) === 0 ) ) {
		$poster = function_exists( 'short_normalize_url' ) ? short_normalize_url( $poster_path ) : $poster_path;
	} else {
		$poster = $poster_path ? ( class_exists( 'SHORT\\Core\\TMDB\\API' ) ? \SHORT\Core\TMDB\API::get_image_url( $poster_path, 'w342' ) : 'https://image.tmdb.org/t/p/w342' . $poster_path ) : '';
	}

	$rating   = ! empty( $item['vote_average'] ) ? number_format( (float) $item['vote_average'], 1 ) : '5.0';
	$date_str = $item['release_date'] ?? $item['first_air_date'] ?? '';
	$year     = ! empty( $date_str ) ? substr( $date_str, 0, 4 ) : '';
	$link_url = $is_short ? home_url( "/watch/{$id}/?episode=1" ) : home_url( "/{$item_type}/{$id}" );
	$badge    = $is_short ? 'SHORT' : ( ( 'tv' === $item_type ) ? 'TV' : 'MOVIE' );

	ob_start();
	?>
	<div class="short-card-simple">
		<a href="<?php echo esc_url( $link_url ); ?>" class="simple-card-link" aria-label="<?php echo esc_attr( $item_title ); ?>">
			<div class="simple-poster-wrap">
				<?php if ( $poster ) : ?>
					<img src="<?php echo esc_url( $poster ); ?>" alt="<?php echo esc_attr( $item_title ); ?>" loading="lazy">
				<?php else : ?>
					<div class="poster-placeholder">
						<span><?php echo esc_html( $item_title ); ?></span>
					</div>
				<?php endif; ?>

				<div class="card-gradient-overlay"></div>

				<div class="card-badges-top">
					<span class="badge-type"><?php echo esc_html( $badge ); ?></span>
					<?php if ( $rating ) : ?>
						<span class="badge-rating">★ <?php echo esc_html( $rating ); ?></span>
					<?php endif; ?>
				</div>

				<div class="card-hover-action">
					<div class="hover-play-btn">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
					</div>
				</div>
			</div>

			<div class="simple-card-info">
				<div class="simple-card-title" title="<?php echo esc_attr( $item_title ); ?>"><?php echo esc_html( $item_title ); ?></div>
				<div class="simple-card-meta">
					<?php if ( $year ) : ?>
						<span class="simple-year"><?php echo esc_html( $year ); ?></span>
					<?php endif; ?>
					<span class="simple-quality">HD</span>
				</div>
			</div>
		</a>
	</div>
	<?php
	return ob_get_clean();
}

function short_get_title_logo( $type, $id ) {
	return '';
}

/**
 * Render Netflix-style Multi-Slide Hero Billboard
 */
function short_render_hero_billboard( $hero_items, $badge_suffix = 'IN TRENDING TODAY' ) {
	if ( empty( $hero_items ) ) {
		return;
	}
	?>
	<section class="short-hero-billboard" id="hero-billboard-slider">
		<div class="hero-slides-wrapper">
			<?php 
			foreach ( $hero_items as $index => $item ) : 
				$title      = ! empty( $item['title'] ) ? $item['title'] : ( ! empty( $item['name'] ) ? $item['name'] : '' );
				$overview   = ! empty( $item['overview'] ) ? wp_trim_words( $item['overview'], 32 ) : '';
				$backdrop   = ! empty( $item['backdrop_path'] ) ? 'https://image.tmdb.org/t/p/original' . $item['backdrop_path'] : '';
				$media_type = ! empty( $item['media_type'] ) ? $item['media_type'] : ( isset( $item['first_air_date'] ) ? 'tv' : 'movie' );
				$id         = $item['id'];
				$rating     = ! empty( $item['vote_average'] ) ? number_format( (float) $item['vote_average'], 1 ) : '';
				$year       = ! empty( $item['release_date'] ) ? substr( $item['release_date'], 0, 4 ) : ( ! empty( $item['first_air_date'] ) ? substr( $item['first_air_date'], 0, 4 ) : '' );
				$logo_url   = short_get_title_logo( $media_type, $id );
			?>
				<div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo esc_attr( $index ); ?>" data-id="<?php echo esc_attr( $id ); ?>" data-media-type="<?php echo esc_attr( $media_type ); ?>" style="background-image: linear-gradient(77deg, rgba(0,0,0,.92) 0%, rgba(0,0,0,.45) 55%, rgba(0,0,0,.85) 100%), url('<?php echo esc_url( $backdrop ); ?>');">
					<div class="hero-video-wrap" style="display:none;">
						<iframe class="hero-video-frame" src="" allow="autoplay; encrypted-media" allowfullscreen="true" frameborder="0"></iframe>
					</div>
					<div class="hero-vignette-bottom"></div>
					<div class="short-container hero-inner">
						<div class="hero-badge"><span class="badge-top10"><span class="badge-full">#<?php echo ( $index + 1 ); ?> <?php echo esc_html( $badge_suffix ); ?></span><span class="badge-short">#<?php echo ( $index + 1 ); ?> <?php echo esc_html( trim( str_ireplace( array( 'IN ', ' TODAY' ), '', $badge_suffix ) ) ); ?></span></span></div>
						<div class="hero-title-wrap">
							<?php if ( ! empty( $logo_url ) ) : ?>
								<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="hero-title-logo" loading="eager" onerror="this.style.display='none'; document.getElementById('hero-title-fb-fn-<?php echo esc_attr( $index ); ?>').style.display='block';">
								<h1 class="hero-title" id="hero-title-fb-fn-<?php echo esc_attr( $index ); ?>" style="display:none;"><?php echo esc_html( $title ); ?></h1>
							<?php else : ?>
								<h1 class="hero-title"><?php echo esc_html( $title ); ?></h1>
							<?php endif; ?>
						</div>
							
						<div class="hero-meta">
							<?php if ( $rating ) : ?><span class="meta-rating">★ <?php echo esc_html( $rating ); ?></span><?php endif; ?>
							<?php if ( $year ) : ?><span class="meta-year"><?php echo esc_html( $year ); ?></span><?php endif; ?>
							<span class="meta-quality">4K ULTRA HD</span>
						</div>

						<?php
						$genre_map = array(
							28 => 'Action', 12 => 'Adventure', 16 => 'Animation', 35 => 'Comedy',
							80 => 'Crime', 99 => 'Documentary', 18 => 'Drama', 10751 => 'Family',
							14 => 'Fantasy', 36 => 'History', 27 => 'Horror', 10402 => 'Music',
							9648 => 'Mystery', 10749 => 'Romance', 878 => 'Sci-Fi', 10770 => 'TV Movie',
							53 => 'Thriller', 10752 => 'War', 37 => 'Western', 10759 => 'Action & Adventure',
							10762 => 'Kids', 10765 => 'Sci-Fi & Fantasy'
						);
						$hero_genres = array();
						if ( ! empty( $item['genre_ids'] ) && is_array( $item['genre_ids'] ) ) {
							foreach ( array_slice( $item['genre_ids'], 0, 3 ) as $gid ) {
								if ( isset( $genre_map[ $gid ] ) ) {
									$hero_genres[] = $genre_map[ $gid ];
								}
							}
						}
						if ( empty( $hero_genres ) ) {
							$hero_genres[] = ( $media_type === 'tv' ) ? 'TV Series' : 'Action & Drama';
						}
						$hero_genre_str = implode( ' • ', $hero_genres );

						$hero_country = '';
						if ( ! empty( $item['origin_country'] ) && is_array( $item['origin_country'] ) ) {
							$hero_country = implode( ', ', $item['origin_country'] );
						} elseif ( ! empty( $item['original_language'] ) ) {
							$hero_country = strtoupper( $item['original_language'] );
							if ( $hero_country === 'EN' ) $hero_country = 'United States';
							elseif ( $hero_country === 'KO' ) $hero_country = 'South Korea';
							elseif ( $hero_country === 'JA' ) $hero_country = 'Japan';
						}
						if ( empty( $hero_country ) ) {
							$hero_country = 'United States';
						}

						$hero_rated = ( (float)( $item['vote_average'] ?? 7 ) >= 7.8 ) ? '18+' : ( ( (float)( $item['vote_average'] ?? 7 ) >= 6.5 ) ? 'TV-MA' : 'PG-13' );
						?>
						<div class="hero-sub-details">
							<span class="hero-sub-genres"><?php echo esc_html( $hero_genre_str ); ?></span>
							<span class="hero-sub-divider">•</span>
							<span class="hero-sub-country"><?php echo esc_html( $hero_country ); ?></span>
							<span class="hero-sub-divider">•</span>
							<span class="hero-sub-rated"><span class="badge-rated"><?php echo esc_html( $hero_rated ); ?></span></span>
						</div>

						<p class="hero-overview"><?php echo esc_html( $overview ); ?></p>

						<div class="hero-actions">
							<a href="<?php echo esc_url( home_url( '/watch/' . $id . '/?type=' . $media_type ) ); ?>" class="btn btn-play">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
								<span class="btn-play-text-full"><?php _e( 'Play Now', 'short-stream' ); ?></span>
								<span class="btn-play-text-short"><?php _e( 'Play', 'short-stream' ); ?></span>
							</a>

							<a href="<?php echo esc_url( home_url( '/' . $media_type . '/' . $id . '/' ) ); ?>" class="btn btn-info">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
								<span class="more-info-full"><?php _e( 'More Details', 'short-stream' ); ?></span><span class="more-info-short"><?php _e( 'Details', 'short-stream' ); ?></span>
							</a>

							<button class="btn-circle btn-add-list" data-id="<?php echo esc_attr( $id ); ?>" data-type="<?php echo esc_attr( $media_type ); ?>" data-title="<?php echo esc_attr( $title ); ?>" data-poster="<?php echo esc_url( ! empty( $item['poster_path'] ) ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : '' ); ?>" data-backdrop="<?php echo esc_url( $backdrop ); ?>" data-rating="<?php echo esc_attr( $rating ); ?>" data-year="<?php echo esc_attr( $year ); ?>" title="Add to My List">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
							</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<!-- Left & Right Carousel Navigation Arrows -->
		<button class="hero-nav-arrow hero-nav-prev" id="hero-slider-prev" aria-label="Previous Slide">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
		</button>
		<button class="hero-nav-arrow hero-nav-next" id="hero-slider-next" aria-label="Next Slide">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
		</button>

		<!-- Hero Sound Toggle Button -->
		<button class="hero-sound-toggle" id="hero-slider-sound-btn" aria-label="Toggle sound" title="Mute / Unmute" style="display:none;">
			<svg class="icon-sound-on" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
			<svg class="icon-sound-off" style="display:none;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
		</button>

		<!-- Bottom Slide Indicators -->
		<div class="hero-slider-indicators" id="hero-slider-dots">
			<?php foreach ( $hero_items as $index => $item ) : ?>
				<button class="hero-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo esc_attr( $index ); ?>" aria-label="Go to slide <?php echo ( $index + 1 ); ?>"></button>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * Render Netflix-style Horizontal Carousel Row
 */
function short_render_carousel_row( $title, $items, $layout = 'landscape', $row_id = '' ) {
	if ( empty( $items ) ) {
		return;
	}
	$is_top_10 = ( 'top10' === $layout );
	$genre_map = array(
		28 => 'Action', 12 => 'Adventure', 16 => 'Animation', 35 => 'Comedy',
		80 => 'Crime', 99 => 'Documentary', 18 => 'Drama', 10751 => 'Family',
		14 => 'Fantasy', 36 => 'History', 27 => 'Horror', 10402 => 'Music',
		9648 => 'Mystery', 10749 => 'Romance', 878 => 'Sci-Fi', 10770 => 'TV Movie',
		53 => 'Thriller', 10752 => 'War', 37 => 'Western', 10759 => 'Action & Adventure',
		10762 => 'Kids', 10765 => 'Sci-Fi & Fantasy'
	);
	?>
	<div class="short-row <?php echo $is_top_10 ? 'top10-row' : ( ( 'portrait' === $layout ) ? 'portrait-row' : '' ); ?>" <?php echo $row_id ? 'id="' . esc_attr( $row_id ) . '"' : ''; ?>>
		<div class="row-header-wrap">
			<h2 class="row-header"><?php echo esc_html( $title ); ?></h2>
		</div>
		<div class="row-carousel-container">
			<button class="carousel-arrow arrow-left" aria-label="Scroll left">
				<svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
			</button>
			<div class="row-carousel">
				<?php
				foreach ( $items as $card_idx => $item ) {
					$rank = $card_idx + 1;
					$is_short = ! empty( $item['is_short'] );
					$m_type = $is_short ? 'watch' : ( ! empty( $item['media_type'] ) ? $item['media_type'] : ( isset( $item['first_air_date'] ) ? 'tv' : 'movie' ) );
					$m_id = $item['id'];
					$m_title = ! empty( $item['title'] ) ? $item['title'] : ( ! empty( $item['name'] ) ? $item['name'] : '' );
					
					$raw_poster = $item['poster_path'] ?? ( $item['poster'] ?? '' );
					$raw_backdrop = $item['backdrop_path'] ?? ( $item['backdrop'] ?? '' );

					if ( $raw_poster && ( strpos( $raw_poster, 'http' ) === 0 || strpos( $raw_poster, '//' ) === 0 ) ) {
						$poster_card = function_exists( 'short_normalize_url' ) ? short_normalize_url( $raw_poster ) : $raw_poster;
					} else {
						$poster_card = $raw_poster ? ( class_exists( 'SHORT\Core\TMDB\API' ) ? \SHORT\Core\TMDB\API::get_image_url( $raw_poster, 'w500' ) : 'https://image.tmdb.org/t/p/w500' . $raw_poster ) : '';
					}

					if ( empty( $poster_card ) && $raw_backdrop ) {
						if ( strpos( $raw_backdrop, 'http' ) === 0 || strpos( $raw_backdrop, '//' ) === 0 ) {
							$poster_card = function_exists( 'short_normalize_url' ) ? short_normalize_url( $raw_backdrop ) : $raw_backdrop;
						} else {
							$poster_card = class_exists( 'SHORT\Core\TMDB\API' ) ? \SHORT\Core\TMDB\API::get_image_url( $raw_backdrop, 'w500' ) : 'https://image.tmdb.org/t/p/w500' . $raw_backdrop;
						}
					}

					if ( $raw_backdrop && ( strpos( $raw_backdrop, 'http' ) === 0 || strpos( $raw_backdrop, '//' ) === 0 ) ) {
						$backdrop_card = function_exists( 'short_normalize_url' ) ? short_normalize_url( $raw_backdrop ) : $raw_backdrop;
					} else {
						$backdrop_card = $raw_backdrop ? ( class_exists( 'SHORT\Core\TMDB\API' ) ? \SHORT\Core\TMDB\API::get_image_url( $raw_backdrop, 'w780' ) : 'https://image.tmdb.org/t/p/w780' . $raw_backdrop ) : $poster_card;
					}

					$card_link = $is_short ? home_url( "/watch/{$m_id}/?episode=1" ) : home_url( '/' . $m_type . '/' . $m_id . '/' );
					$m_rating = ! empty( $item['vote_average'] ) ? number_format( (float) $item['vote_average'], 1 ) : '5.0';
					$m_year = ! empty( $item['release_date'] ) ? substr( $item['release_date'], 0, 4 ) : ( ! empty( $item['first_air_date'] ) ? substr( $item['first_air_date'], 0, 4 ) : '' );
					$m_overview = ! empty( $item['overview'] ) ? wp_trim_words( $item['overview'], 20 ) : '';
					
					// Build genre tags
					$genre_tags = array();
					if ( ! empty( $item['genre_ids'] ) && is_array( $item['genre_ids'] ) ) {
						foreach ( array_slice( $item['genre_ids'], 0, 3 ) as $gid ) {
							if ( isset( $genre_map[ $gid ] ) ) {
								$genre_tags[] = $genre_map[ $gid ];
							}
						}
					}
					if ( empty( $genre_tags ) ) {
						$genre_tags[] = $is_short ? 'SHORT' : ( ( $m_type === 'tv' ) ? 'TV Series' : ( $is_top_10 ? 'Top 10' : 'Drama' ) );
					}
					$genre_str = implode( ' • ', $genre_tags );
					$age_rating = ( $card_idx % 3 === 0 ) ? '18+' : ( ( $card_idx % 2 === 0 ) ? '16+' : '13+' );
					$duration_str = $is_short ? 'Short Series' : ( ( $m_type === 'tv' ) ? ( ( 10 + ( $card_idx * 3 ) ) . ' Episodes' ) : ( '1h ' . ( 40 + ( $card_idx * 4 ) % 25 ) . 'm' ) );

					if ( $is_top_10 ) {
						$is_double_digit = ( $rank >= 10 );
						?>
						<div class="media-card top10-card <?php echo $is_double_digit ? 'rank-card-10' : ''; ?>" 
							data-id="<?php echo esc_attr( $m_id ); ?>" 
							data-type="<?php echo esc_attr( $m_type ); ?>" 
							data-title="<?php echo esc_attr( $m_title ); ?>" 
							data-backdrop="<?php echo esc_url( $backdrop_card ); ?>" 
							data-rating="<?php echo esc_attr( $m_rating ); ?>" 
							data-year="<?php echo esc_attr( $m_year ); ?>"
							data-genres="<?php echo esc_attr( $genre_str ); ?>"
							data-age="<?php echo esc_attr( $age_rating ); ?>"
							data-duration="<?php echo esc_attr( $duration_str ); ?>"
							data-overview="<?php echo esc_attr( $m_overview ); ?>"
							data-top10="1">
							<div class="top10-rank-box <?php echo $is_double_digit ? 'rank-10' : ''; ?>">
								<svg class="top10-rank-svg" viewBox="<?php echo $is_double_digit ? '0 0 200 270' : '0 0 145 270'; ?>" width="<?php echo $is_double_digit ? '200' : '145'; ?>" height="280">
									<text x="<?php echo $is_double_digit ? '100' : '72'; ?>" y="235" text-anchor="middle" class="top10-svg-num"><?php echo $rank; ?></text>
								</svg>
							</div>
							<a href="<?php echo esc_url( $card_link ); ?>" class="top10-poster-wrap">
								<?php if ( $poster_card ) : ?>
									<img src="<?php echo esc_url( $poster_card ); ?>" alt="<?php echo esc_attr( $m_title ); ?>" class="top10-poster-img" loading="lazy">
								<?php else : ?>
									<div class="card-poster-placeholder"><span><?php echo esc_html( $m_title ); ?></span></div>
								<?php endif; ?>

								<div class="top10-badges-wrap">
									<?php if ( $m_type === 'tv' || $is_short ) : ?>
										<?php if ( $rank === 1 ) : ?>
											<span class="top10-badge-red"><?php _e( 'Recently Added', 'short-stream' ); ?></span>
										<?php elseif ( $rank === 2 || $rank === 4 || $rank === 5 ) : ?>
											<span class="top10-badge-red"><?php _e( 'New Episode', 'short-stream' ); ?></span>
											<span class="top10-badge-white"><?php _e( 'Watch Now', 'short-stream' ); ?></span>
										<?php elseif ( $rank === 3 ) : ?>
											<span class="top10-badge-red"><?php _e( 'Top 10', 'short-stream' ); ?></span>
										<?php endif; ?>
									<?php else : ?>
										<?php if ( $rank === 1 ) : ?>
											<span class="top10-badge-red"><?php _e( 'Recently Added', 'short-stream' ); ?></span>
										<?php elseif ( $rank === 2 || $rank === 4 || $rank === 5 ) : ?>
											<span class="top10-badge-red"><?php _e( 'Trending Movie', 'short-stream' ); ?></span>
											<span class="top10-badge-white"><?php _e( 'Watch Now', 'short-stream' ); ?></span>
										<?php elseif ( $rank === 3 ) : ?>
											<span class="top10-badge-red"><?php _e( 'Top 10', 'short-stream' ); ?></span>
										<?php endif; ?>
									<?php endif; ?>
								</div>
							</a>
						</div>
						<?php
					} elseif ( 'portrait' === $layout ) {
						?>
						<div class="media-card portrait-card" 
							data-id="<?php echo esc_attr( $m_id ); ?>" 
							data-type="<?php echo esc_attr( $m_type ); ?>" 
							data-title="<?php echo esc_attr( $m_title ); ?>" 
							data-backdrop="<?php echo esc_url( $backdrop_card ); ?>" 
							data-rating="<?php echo esc_attr( $m_rating ); ?>" 
							data-year="<?php echo esc_attr( $m_year ); ?>"
							data-genres="<?php echo esc_attr( $genre_str ); ?>"
							data-age="<?php echo esc_attr( $age_rating ); ?>"
							data-duration="<?php echo esc_attr( $duration_str ); ?>"
							data-overview="<?php echo esc_attr( $m_overview ); ?>"
							data-top10="0">
							<a href="<?php echo esc_url( $card_link ); ?>" class="card-portrait-wrap">
								<?php if ( $poster_card ) : ?>
									<img src="<?php echo esc_url( $poster_card ); ?>" alt="<?php echo esc_attr( $m_title ); ?>" class="card-portrait-img" loading="lazy">
								<?php else : ?>
									<div class="card-poster-placeholder"><span><?php echo esc_html( $m_title ); ?></span></div>
								<?php endif; ?>

								<div class="card-overlay-gradient"></div>

								<?php if ( ! empty( $item['is_vip'] ) ) : ?>
									<div class="portrait-badge-top-left" style="position:absolute; top:8px; left:8px; z-index:4;">
										<span style="background:linear-gradient(135deg, #ff2d55 0%, #d97706 100%); color:#ffffff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.4px; display:inline-flex; align-items:center; gap:3px; box-shadow:0 2px 8px rgba(0,0,0,0.5);">👑 VIP</span>
									</div>
								<?php endif; ?>

								<?php if ( ! empty( $genre_tags[0] ) ) : ?>
									<div class="portrait-badge-top-right">
										<span class="portrait-genre-badge"><?php echo esc_html( $genre_tags[0] ); ?></span>
									</div>
								<?php endif; ?>

								<div class="portrait-meta-bottom">
									<?php if ( $m_year ) : ?>
										<span class="portrait-meta-year"><?php echo esc_html( $m_year ); ?></span>
									<?php endif; ?>
									<div class="portrait-meta-sub">
										<?php if ( $duration_str ) : ?>
											<span class="portrait-meta-duration"><?php echo esc_html( $duration_str ); ?></span>
										<?php endif; ?>
										<?php if ( $m_rating ) : ?>
											<span class="card-rating-badge">★ <?php echo esc_html( $m_rating ); ?></span>
										<?php endif; ?>
									</div>
								</div>
							</a>
							<a href="<?php echo esc_url( $card_link ); ?>" class="portrait-card-title-bottom"><?php echo esc_html( $m_title ); ?></a>
						</div>
						<?php
					} else {
						?>
						<div class="media-card landscape-card" 
							data-id="<?php echo esc_attr( $m_id ); ?>" 
							data-type="<?php echo esc_attr( $m_type ); ?>" 
							data-title="<?php echo esc_attr( $m_title ); ?>" 
							data-backdrop="<?php echo esc_url( $backdrop_card ); ?>" 
							data-rating="<?php echo esc_attr( $m_rating ); ?>" 
							data-year="<?php echo esc_attr( $m_year ); ?>"
							data-genres="<?php echo esc_attr( $genre_str ); ?>"
							data-age="<?php echo esc_attr( $age_rating ); ?>"
							data-duration="<?php echo esc_attr( $duration_str ); ?>"
							data-overview="<?php echo esc_attr( $m_overview ); ?>"
							data-top10="0">
							<a href="<?php echo esc_url( $card_link ); ?>" class="card-backdrop-wrap">
								<?php if ( $backdrop_card ) : ?>
									<img src="<?php echo esc_url( $backdrop_card ); ?>" alt="<?php echo esc_attr( $m_title ); ?>" class="card-backdrop-img" loading="lazy">
								<?php else : ?>
									<div class="card-poster-placeholder"><span><?php echo esc_html( $m_title ); ?></span></div>
								<?php endif; ?>

								<div class="card-overlay-gradient"></div>
								<div class="card-title-overlay">
									<span class="card-title-text"><?php echo esc_html( $m_title ); ?></span>
									<?php if ( $m_rating ) : ?><span class="card-rating-badge">★ <?php echo esc_html( $m_rating ); ?></span><?php endif; ?>
								</div>
							</a>
						</div>
						<?php
					}
				}
				?>
			</div>
			<button class="carousel-arrow arrow-right" aria-label="Scroll right">
				<svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
			</button>
		</div>
	</div>
	<?php
}

/**
 * ShortTV Drama Section Item Fetcher
 *
 * @param array  $sec Section configuration array.
 * @return array Array of drama items.
 */
function short_fetch_section_items( $sec ) {
	$sec_type = $sec['type'] ?? '';
	$limit    = ! empty( $sec['limit'] ) ? max( 1, min( 50, (int) $sec['limit'] ) ) : 20;

	if ( in_array( $sec_type, array( 'continue_watching', 'my_list' ), true ) ) {
		return array();
	}

	$cpt = class_exists( 'SHORT\\Core\\CPT\\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::POST_TYPE : 'short_video';

	$args = array(
		'post_type'      => $cpt,
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	if ( 'top_rated' === $sec_type || 'leaderboard' === $sec_type ) {
		$args['meta_key'] = '_short_rating';
		$args['orderby']  = 'meta_value_num';
	} elseif ( in_array( $sec_type, array( 'popular', 'top_10_today', 'popular_dramas', 'trending_now', 'tmdb_trending' ), true ) ) {
		$args['meta_key'] = '_short_views';
		$args['orderby']  = 'meta_value_num';
	}

	$query = new \WP_Query( $args );
	$items = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $p ) {
			$schema = class_exists( 'SHORT\\Core\\CPT\\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::get_series_schema( $p->ID ) : array();
			$poster = ! empty( $schema['poster'] ) ? $schema['poster'] : ( get_the_post_thumbnail_url( $p->ID, 'medium' ) ?: '' );
			$items[] = array(
				'id'            => $p->ID,
				'title'         => $p->post_title,
				'name'          => $p->post_title,
				'poster_path'   => $poster,
				'backdrop_path' => $poster,
				'is_short'      => true,
				'vote_average'  => $schema['rating'] ?? '5.0',
				'overview'      => ! empty( $p->post_excerpt ) ? $p->post_excerpt : ( $schema['overview'] ?? '' ),
			);
		}
	}

	return $items;
}

/**
 * AJAX Genre Filter Handler (In-Place Filter & Infinite Scroll with 24 items per page)
 */
function short_ajax_filter_genre() {
	$raw_type = sanitize_text_field( $_GET['type'] ?? 'movie' );
	$media    = ( 'tv' === $raw_type || 'series' === $raw_type ) ? 'tv' : 'movie';
	$genre_id = absint( $_GET['genre_id'] ?? 0 );
	$page     = max( 1, absint( $_GET['page'] ?? 1 ) );
	$per_page = 24;

	$movie_genres = array(
		28    => __( 'Action', 'short-stream' ),
		12    => __( 'Adventure', 'short-stream' ),
		16    => __( 'Animation', 'short-stream' ),
		35    => __( 'Comedy', 'short-stream' ),
		80    => __( 'Crime', 'short-stream' ),
		99    => __( 'Documentary', 'short-stream' ),
		18    => __( 'Drama', 'short-stream' ),
		10751 => __( 'Family', 'short-stream' ),
		14    => __( 'Fantasy', 'short-stream' ),
		36    => __( 'History', 'short-stream' ),
		27    => __( 'Horror', 'short-stream' ),
		10402 => __( 'Music', 'short-stream' ),
		9648  => __( 'Mystery', 'short-stream' ),
		10749 => __( 'Romance', 'short-stream' ),
		878   => __( 'Sci-Fi', 'short-stream' ),
		10770 => __( 'TV Movie', 'short-stream' ),
		53    => __( 'Thriller', 'short-stream' ),
		10752 => __( 'War', 'short-stream' ),
		37    => __( 'Western', 'short-stream' ),
	);

	$tv_genres = array(
		10759 => __( 'Action & Adventure', 'short-stream' ),
		16    => __( 'Animation', 'short-stream' ),
		35    => __( 'Comedy', 'short-stream' ),
		80    => __( 'Crime', 'short-stream' ),
		99    => __( 'Documentary', 'short-stream' ),
		18    => __( 'Drama', 'short-stream' ),
		10751 => __( 'Family', 'short-stream' ),
		10762 => __( 'Kids', 'short-stream' ),
		9648  => __( 'Mystery', 'short-stream' ),
		10763 => __( 'News', 'short-stream' ),
		10764 => __( 'Reality', 'short-stream' ),
		10765 => __( 'Sci-Fi & Fantasy', 'short-stream' ),
		10766 => __( 'Soap', 'short-stream' ),
		10767 => __( 'Talk', 'short-stream' ),
		10768 => __( 'War & Politics', 'short-stream' ),
		37    => __( 'Western', 'short-stream' ),
	);

	if ( 'tv' === $media ) {
		$genre_name = $tv_genres[ $genre_id ] ?? ( $movie_genres[ $genre_id ] ?? '' );
		$title = ( $genre_id > 0 && $genre_name ) ? sprintf( __( '%s Series', 'short-stream' ), $genre_name ) : __( 'Series', 'short-stream' );
	} else {
		$genre_name = $movie_genres[ $genre_id ] ?? ( $tv_genres[ $genre_id ] ?? '' );
		$title = ( $genre_id > 0 && $genre_name ) ? sprintf( __( '%s Movies', 'short-stream' ), $genre_name ) : __( 'Movies', 'short-stream' );
	}

	$items = short_fetch_genre_items( $media, $genre_id, $page, $per_page );

	$html = '';
	if ( ! empty( $items ) ) {
		foreach ( $items as $item ) {
			$html .= short_render_card_simple_html( $item, $media );
		}
	} elseif ( 1 === $page ) {
		$html = '<div class="no-titles-found" style="grid-column: 1 / -1; padding: 60px 20px; text-align: center; color: #888;"><p>' . __( 'No titles found in this category. Please try another genre.', 'short-stream' ) . '</p></div>';
	}

	wp_send_json_success( array(
		'html'      => $html,
		'title'     => $title,
		'count'     => count( $items ),
		'page'      => $page,
		'has_more'  => ( count( $items ) >= $per_page ),
	) );
}
add_action( 'wp_ajax_short_filter_genre', 'short_ajax_filter_genre' );
add_action( 'wp_ajax_nopriv_short_filter_genre', 'short_ajax_filter_genre' );

function short_ajax_get_season_episodes() {
	$tv_id  = isset( $_GET['tv_id'] ) ? absint( $_GET['tv_id'] ) : ( isset( $_POST['tv_id'] ) ? absint( $_POST['tv_id'] ) : 0 );
	$season = isset( $_GET['season'] ) ? absint( $_GET['season'] ) : ( isset( $_POST['season'] ) ? absint( $_POST['season'] ) : 1 );

	if ( ! $tv_id ) {
		wp_send_json_error( array( 'message' => __( 'Invalid TV show ID.', 'short-stream' ) ) );
	}

	$season_data = class_exists( 'SHORT\\Core\\TMDB\\API' )
		? \SHORT\Core\TMDB\API::get_season_episodes( $tv_id, $season )
		: array();

	if ( is_wp_error( $season_data ) || empty( $season_data['episodes'] ) ) {
		wp_send_json_error( array( 'message' => __( 'No episodes found for this season.', 'short-stream' ) ) );
	}

	$episodes = $season_data['episodes'];
	ob_start();
	foreach ( $episodes as $ep ) {
		$ep_num   = $ep['episode_number'] ?? 1;
		$ep_title = $ep['name'] ?? 'Episode ' . $ep_num;
		$ep_desc  = $ep['overview'] ?? '';
		$ep_still = class_exists( 'SHORT\\Core\\TMDB\\API' )
			? \SHORT\Core\TMDB\API::get_image_url( $ep['still_path'] ?? '', 'w300' )
			: ( ! empty( $ep['still_path'] ) ? 'https://image.tmdb.org/t/p/w300' . $ep['still_path'] : '' );
		$watch_url = home_url( "/watch/{$tv_id}/season-{$season}/episode-{$ep_num}" );
		?>
		<a href="<?php echo esc_url( $watch_url ); ?>" class="episode-card">
			<div class="episode-thumb-wrap">
				<?php if ( $ep_still ) : ?>
					<img src="<?php echo esc_url( $ep_still ); ?>" alt="<?php echo esc_attr( $ep_title ); ?>" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300&auto=format&fit=crop&q=80';">
				<?php else : ?>
					<div class="episode-thumb-fallback">EP <?php echo $ep_num; ?></div>
				<?php endif; ?>
				<div class="episode-play-icon">▶</div>
			</div>
			<div class="episode-info">
				<div class="episode-num-title"><strong><?php echo $ep_num; ?>.</strong> <?php echo esc_html( $ep_title ); ?></div>
				<p class="episode-desc"><?php echo esc_html( wp_trim_words( $ep_desc, 20 ) ); ?></p>
			</div>
		</a>
		<?php
	}
	$html = ob_get_clean();

	wp_send_json_success( array(
		'html'          => $html,
		'season_number' => $season,
		'count'         => count( $episodes ),
	) );
}
add_action( 'wp_ajax_short_get_season_episodes', 'short_ajax_get_season_episodes' );
add_action( 'wp_ajax_nopriv_short_get_season_episodes', 'short_ajax_get_season_episodes' );

add_action( 'rest_api_init', 'short_register_rest_routes' );


// =========================================================================
// BACKWARD COMPATIBILITY ALIASES
// =========================================================================
if ( ! function_exists( 'short_is_kids_mode' ) ) {
	function short_is_kids_mode() { return short_is_kids_mode(); }
}
if ( ! function_exists( 'short_is_item_kids_friendly' ) ) {
	function short_is_item_kids_friendly( $item ) { return short_is_item_kids_friendly( $item ); }
}
if ( ! function_exists( 'short_get_current_nav_section' ) ) {
	function short_get_current_nav_section() { return short_get_current_nav_section(); }
}
if ( ! function_exists( 'short_fetch_section_items' ) ) {
	function short_fetch_section_items( $section, $media_type = 'movie' ) { return short_fetch_section_items( $section, $media_type ); }
}
if ( ! function_exists( 'short_render_carousel_row' ) ) {
	function short_render_carousel_row( $title, $items, $layout = 'landscape' ) { return short_render_carousel_row( $title, $items, $layout ); }
}
if ( ! function_exists( 'short_render_hero_billboard' ) ) {
	function short_render_hero_billboard( $items, $badge_label = 'FEATURED TODAY' ) { return short_render_hero_billboard( $items, $badge_label ); }
}
