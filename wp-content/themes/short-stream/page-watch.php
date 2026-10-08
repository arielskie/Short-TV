<?php
/**
 * ShortTV / ReelShort 9:16 Vertical Video Watch Experience — Mobile-First Redesign
 *
 * @package Short_Stream
 */

$id      = absint( get_query_var( 'short_id' ) ?: ( isset( $_GET['id'] ) ? $_GET['id'] : 0 ) );
$episode = absint( get_query_var( 'short_episode' ) ?: ( isset( $_GET['episode'] ) ? $_GET['episode'] : 0 ) );

if ( ! $id ) {
	$req_path  = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' );
	$site_path = trim( parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' );
	if ( $site_path && 0 === strpos( $req_path, $site_path ) ) {
		$req_path = trim( substr( $req_path, strlen( $site_path ) ), '/' );
	}
	$parts = explode( '/', $req_path );
	if ( ( $parts[0] ?? '' ) === 'watch' && ! empty( $parts[1] ) ) {
		if ( is_numeric( $parts[1] ) ) {
			$id = absint( $parts[1] );
		} else {
			$by_slug = get_page_by_path( $parts[1], OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
			if ( $by_slug ) {
				$id = $by_slug->ID;
			}
		}
	}
	if ( ! $episode && ! empty( $parts[2] ) ) {
		if ( preg_match( '/episode-([0-9]+)/i', $parts[2], $m ) ) {
			$episode = absint( $m[1] );
		} elseif ( is_numeric( $parts[2] ) ) {
			$episode = absint( $parts[2] );
		}
	}
	if ( ! $episode && ! empty( $parts[3] ) ) {
		if ( preg_match( '/episode-([0-9]+)/i', $parts[3], $m ) ) {
			$episode = absint( $m[1] );
		} elseif ( is_numeric( $parts[3] ) ) {
			$episode = absint( $parts[3] );
		}
	}
}

if ( ! $id ) {
	$id = get_the_ID();
}

if ( ! $id ) {
	$first = get_posts( array( 'post_type' => \SHORT\Core\CPT\Video_CPT::POST_TYPE, 'posts_per_page' => 1 ) );
	if ( ! empty( $first ) ) {
		$id = $first[0]->ID;
	}
}

$schema      = \SHORT\Core\CPT\Video_CPT::get_series_schema( $id );
$schema['post_id'] = $id;

// Server-side paywall protection: strip video stream URLs for locked episodes
$is_admin = current_user_can( 'manage_options' );
$client_schema = $schema;
if ( ! empty( $client_schema['episodes'] ) && is_array( $client_schema['episodes'] ) ) {
	foreach ( $client_schema['episodes'] as &$ep_item ) {
		$unlock_type = strtolower( $ep_item['access_control']['unlock_type'] ?? 'free' );
		$is_locked   = in_array( $unlock_type, array( 'coins', 'premium', 'vip' ), true ) && empty( $ep_item['access_control']['is_unlocked'] );
		if ( $is_locked && ! $is_admin ) {
			$ep_item['sources']['hls_stream_url'] = '';
			$ep_item['sources']['fallback_mp4']   = '';
			$ep_item['access_control']['is_unlocked'] = false;
		} else {
			if ( ! empty( $ep_item['sources']['hls_stream_url'] ) && function_exists( 'short_normalize_url' ) ) {
				$ep_item['sources']['hls_stream_url'] = short_normalize_url( $ep_item['sources']['hls_stream_url'] );
			}
			if ( ! empty( $ep_item['sources']['fallback_mp4'] ) && function_exists( 'short_normalize_url' ) ) {
				$ep_item['sources']['fallback_mp4'] = short_normalize_url( $ep_item['sources']['fallback_mp4'] );
			}
		}
	}
	unset( $ep_item );
}

if ( ! empty( $client_schema['cover_assets'] ) && is_array( $client_schema['cover_assets'] ) ) {
	foreach ( $client_schema['cover_assets'] as $k => $v ) {
		if ( is_string( $v ) && function_exists( 'short_normalize_url' ) ) {
			$client_schema['cover_assets'][ $k ] = short_normalize_url( $v );
		}
	}
}

$schema_json = wp_json_encode( $client_schema, JSON_UNESCAPED_SLASHES );
$title       = $schema['title'] ?? get_the_title( $id );
$genres      = $schema['genres'] ?? array( 'Drama', 'Romance', 'Billionaire' );
$raw_poster  = ( ! empty( $schema['cover_assets']['vertical_poster'] ) ? $schema['cover_assets']['vertical_poster'] : '' )
	?: ( get_post_meta( $id, '_shorttv_vertical_poster', true ) ?: ( get_post_meta( $id, '_short_poster_url', true ) ?: get_the_post_thumbnail_url( $id, 'full' ) ) );
$fallback_poster = function_exists( 'short_get_default_poster_url' ) ? short_get_default_poster_url() : get_template_directory_uri() . '/assets/images/fallback-portrait.svg';
$poster      = ( function_exists( 'short_normalize_url' ) && $raw_poster ) ? short_normalize_url( $raw_poster ) : ( $raw_poster ?: $fallback_poster );
$overview    = $schema['overview'] ?? '';

$episodes_list = ! empty( $schema['episodes'] ) && is_array( $schema['episodes'] ) ? $schema['episodes'] : array();

if ( empty( $episodes_list ) ) {
	$episodes_list = array(
		array(
			'episode_number' => 1,
			'title'          => 'Episode 1',
			'overview'       => $overview,
			'access_control' => array( 'unlock_type' => 'free', 'coin_cost' => 0, 'is_unlocked' => true ),
			'sources'        => array(
				'aspect_ratio'   => '9:16',
				'hls_stream_url' => '',
				'fallback_mp4'   => '',
			),
		),
	);
}

usort( $episodes_list, function( $a, $b ) {
	return ( (int) ( $a['episode_number'] ?? 0 ) ) - ( (int) ( $b['episode_number'] ?? 0 ) );
} );

$total_uploaded_count = count( $episodes_list );
$first_ep_num         = (int) ( $episodes_list[0]['episode_number'] ?? 1 );
if ( ! $episode ) {
	$episode = $first_ep_num;
}

$current_ep_data = null;
foreach ( $episodes_list as $ep_item ) {
	if ( (int) ( $ep_item['episode_number'] ?? 0 ) === (int) $episode ) {
		$current_ep_data = $ep_item;
		break;
	}
}
if ( ! $current_ep_data ) {
	$current_ep_data = $episodes_list[0];
	$episode         = (int) ( $current_ep_data['episode_number'] ?? 1 );
}

$ep_synopsis = ! empty( $current_ep_data['overview'] ) ? $current_ep_data['overview'] : ( $overview ?: __( 'An thrilling episode of this short drama series.', 'short-stream' ) );

$raw_views = (int) ( get_post_meta( $id, '_shorttv_view_count', true ) ?: ( $schema['analytics']['view_count'] ?? 0 ) );
$raw_likes = (int) ( get_post_meta( $id, '_shorttv_like_count', true ) ?: ( $schema['analytics']['like_count'] ?? 0 ) );

$raw_rating_val   = get_post_meta( $id, '_shorttv_rating', true );
$raw_rating_count = (int) get_post_meta( $id, '_shorttv_rating_count', true );
if ( empty( $raw_rating_count ) && ! empty( $schema['rating_count'] ) ) {
	$raw_rating_count = (int) $schema['rating_count'];
}
$display_rating = ( $raw_rating_val !== '' && is_numeric( $raw_rating_val ) ) ? number_format( (float) $raw_rating_val, 1 ) : ( ! empty( $schema['rating'] ) ? number_format( (float) $schema['rating'], 1 ) : ( $raw_rating_count > 0 ? '5.0' : '0.0' ) );

$view_count_fmt = ( $raw_views >= 1000000 ) ? ( round( $raw_views / 1000000, 1 ) . 'M' ) : ( ( $raw_views >= 1000 ) ? ( round( $raw_views / 1000, 1 ) . 'K' ) : (string) $raw_views );
$like_count_fmt = ( $raw_likes >= 1000000 ) ? ( round( $raw_likes / 1000000, 1 ) . 'M' ) : ( ( $raw_likes >= 1000 ) ? ( round( $raw_likes / 1000, 1 ) . 'K' ) : (string) $raw_likes );
$like_count     = $like_count_fmt;

set_query_var( 'short_watch', 1 );
$GLOBALS['short_is_watch_page'] = true;
get_header();
?>

<style>
/* CSS from my HTML */
  :root {
    --bg: #0a0a0a;
    --surface: #1a1a1a;
    --surface2: #252525;
    --border: #333;
    --text: #fff;
    --text2: #aaa;
    --text3: #666;
    --accent: #7c4dff;
    --accent2: #ff4081;
    --pink: #ff4c8b;
  }

  *, *::before, *::after {
    -webkit-tap-highlight-color: transparent !important;
    -webkit-touch-callout: none !important;
  }

  body {
    background: #000;
    margin: 0;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    overflow: hidden;
    -webkit-tap-highlight-color: transparent !important;
    -webkit-user-select: none !important;
    user-select: none !important;
  }
  
  .phone {
    width: 100vw;
    height: 100vh;
    height: 100dvh;
    background: var(--bg);
    overflow: hidden;
    position: relative;
    -webkit-tap-highlight-color: transparent !important;
  }

  /* SCREENS */
  .screen {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.35s ease;
  }

  .screen.hidden { opacity: 0; pointer-events: none; transform: translateX(100%); }
  .screen.slide-left { transform: translateX(-100%); opacity: 0; pointer-events: none; }

  /* VIDEO PLAYER SCREEN */
  #player-screen {
    background: #000;
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  .video-area { position: relative; width: 100%; height: 100%; flex: 1; overflow: hidden; cursor: pointer; }
  
  .stv-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    inset: 0;
    z-index: 2;
  }
  
  .stv-poster-bg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    inset: 0;
    z-index: 1;
    filter: blur(10px) brightness(0.5);
    transition: opacity 0.4s ease;
  }

  /* ── 9:16 VERTICAL FULLSCREEN (DESKTOP CENTERED PILLARBOX & MOBILE FULL EDGE-TO-EDGE) ── */
  :fullscreen,
  :-webkit-full-screen,
  :-moz-full-screen {
    background: #000000 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    overflow: hidden !important;
  }

  #player-screen:fullscreen,
  #player-screen:-webkit-full-screen,
  .video-area:fullscreen,
  .video-area:-webkit-full-screen,
  .watch-left-col:fullscreen,
  .watch-left-col:-webkit-full-screen {
    background: #000000 !important;
    width: 100vw !important;
    height: 100vh !important;
    height: 100dvh !important;
    max-width: 100vw !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    border-radius: 0 !important;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  /* Desktop Fullscreen (Screens wider than 9:16 portrait ratio) */
  @media (min-width: 900px) and (min-aspect-ratio: 9/16) {
    #player-screen:fullscreen .video-area,
    #player-screen:-webkit-full-screen .video-area,
    .watch-left-col:fullscreen .video-area,
    .watch-left-col:-webkit-full-screen .video-area {
      position: relative !important;
      width: 100% !important;
      max-width: calc(100vh * (9 / 16)) !important;
      height: 100vh !important;
      aspect-ratio: 9 / 16 !important;
      margin: 0 auto !important;
      background: #000000 !important;
      overflow: hidden !important;
    }

    #player-screen:fullscreen .stv-video,
    #player-screen:-webkit-full-screen .stv-video,
    .video-area:fullscreen .stv-video,
    .video-area:-webkit-full-screen .stv-video,
    .watch-left-col:fullscreen .stv-video,
    .watch-left-col:-webkit-full-screen .stv-video,
    :fullscreen .stv-video,
    :-webkit-full-screen .stv-video {
      width: 100% !important;
      height: 100% !important;
      max-width: calc(100vh * (9 / 16)) !important;
      object-fit: contain !important;
      position: absolute !important;
      top: 0 !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      inset: 0 auto 0 50% !important;
    }

    #player-screen:fullscreen .stv-poster-bg,
    #player-screen:-webkit-full-screen .stv-poster-bg,
    .video-area:fullscreen .stv-poster-bg,
    .video-area:-webkit-full-screen .stv-poster-bg {
      max-width: calc(100vh * (9 / 16)) !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
    }

    #player-screen:fullscreen .player-top,
    #player-screen:-webkit-full-screen .player-top,
    #player-screen:fullscreen .player-bottom,
    #player-screen:-webkit-full-screen .player-bottom,
    .video-area:fullscreen .player-top,
    .video-area:-webkit-full-screen .player-top,
    .video-area:fullscreen .player-bottom,
    .video-area:-webkit-full-screen .player-bottom {
      max-width: calc(100vh * (9 / 16)) !important;
      width: 100% !important;
      left: 50% !important;
      transform: translateX(-50%) !important;
      right: auto !important;
    }

    #player-screen:fullscreen .player-sidebar,
    #player-screen:-webkit-full-screen .player-sidebar,
    .video-area:fullscreen .player-sidebar,
    .video-area:-webkit-full-screen .player-sidebar {
      right: calc(50% - (100vh * 9 / 32) + 14px) !important;
      left: auto !important;
    }
  }

  /* Mobile Fullscreen & Portrait screens (Prevents sidebar clipping at right edge) */
  @media (max-width: 899px), (max-aspect-ratio: 9/16) {
    #player-screen:fullscreen .video-area,
    #player-screen:-webkit-full-screen .video-area,
    .watch-left-col:fullscreen .video-area,
    .watch-left-col:-webkit-full-screen .video-area,
    .video-area:fullscreen,
    .video-area:-webkit-full-screen {
      position: relative !important;
      width: 100vw !important;
      max-width: 100vw !important;
      height: 100vh !important;
      height: 100dvh !important;
      margin: 0 !important;
      background: #000000 !important;
      overflow: hidden !important;
    }

    #player-screen:fullscreen .stv-video,
    #player-screen:-webkit-full-screen .stv-video,
    .video-area:fullscreen .stv-video,
    .video-area:-webkit-full-screen .stv-video,
    .watch-left-col:fullscreen .stv-video,
    .watch-left-col:-webkit-full-screen .stv-video,
    :fullscreen .stv-video,
    :-webkit-full-screen .stv-video {
      width: 100vw !important;
      height: 100% !important;
      max-width: 100vw !important;
      object-fit: cover !important;
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      transform: none !important;
      inset: 0 !important;
    }

    #player-screen:fullscreen .stv-poster-bg,
    #player-screen:-webkit-full-screen .stv-poster-bg,
    .video-area:fullscreen .stv-poster-bg,
    .video-area:-webkit-full-screen .stv-poster-bg {
      max-width: 100vw !important;
      width: 100vw !important;
      left: 0 !important;
      transform: none !important;
    }

    #player-screen:fullscreen .player-top,
    #player-screen:-webkit-full-screen .player-top,
    #player-screen:fullscreen .player-bottom,
    #player-screen:-webkit-full-screen .player-bottom,
    .video-area:fullscreen .player-top,
    .video-area:-webkit-full-screen .player-top,
    .video-area:fullscreen .player-bottom,
    .video-area:-webkit-full-screen .player-bottom {
      max-width: 100vw !important;
      width: 100vw !important;
      left: 0 !important;
      right: 0 !important;
      transform: none !important;
    }

    #player-screen:fullscreen .player-sidebar,
    #player-screen:-webkit-full-screen .player-sidebar,
    .video-area:fullscreen .player-sidebar,
    .video-area:-webkit-full-screen .player-sidebar,
    .player-sidebar {
      right: calc(env(safe-area-inset-right, 0px) + 12px) !important;
      left: auto !important;
      max-width: 60px !important;
      display: flex !important;
    }
  }

  /* Top controls */
  .player-top {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    padding: calc(env(safe-area-inset-top, 0px) + 12px) 16px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: linear-gradient(180deg, rgba(0,0,0,0.7) 0%, transparent 100%);
    z-index: 10;
  }

  .player-back { display: flex; align-items: center; gap: 6px; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
  .player-back svg { width: 20px; height: 20px; }
  .player-top-right { display: flex; align-items: center; gap: 20px; }
  .player-top-btn { display: flex; align-items: center; gap: 5px; color: #fff; font-size: 14px; font-weight: 500; cursor: pointer; opacity: 0.9; }
  .player-top-btn svg { width: 18px; height: 18px; }

  /* Right sidebar */
  .player-sidebar {
    position: absolute;
    right: 14px;
    bottom: 140px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    align-items: center;
    z-index: 10;
  }

  .sidebar-btn { display: flex; flex-direction: column; align-items: center; gap: 4px; cursor: pointer; color: #fff; }
  .sidebar-icon {
    width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.12); border-radius: 50%; backdrop-filter: blur(4px);
    transition: transform 0.15s, background 0.15s;
  }
  .sidebar-icon svg { width: 22px; height: 22px; }
  .sidebar-label { font-size: 11px; color: rgba(255,255,255,0.85); font-weight: 500; }

  /* Subtitle */
  .subtitle { position: absolute; bottom: 160px; left: 0; right: 70px; text-align: center; padding: 0 20px; z-index: 10; pointer-events: none;}
  .subtitle p { color: #fff; font-size: 20px; font-weight: 700; line-height: 1.4; text-shadow: 0 2px 8px rgba(0,0,0,0.9), 0 0 20px rgba(0,0,0,0.6); }

  /* Bottom info */
  .player-bottom {
    position: absolute; bottom: 0; left: 0; right: 0; padding: 0 16px 24px;
    background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, transparent 100%); z-index: 10;
  }

  .show-info { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
  .show-title-wrap { flex: 1; }
  .show-title { color: #fff; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 4px; cursor: pointer; }
  .show-title svg { width: 14px; height: 14px; opacity: 0.7; }
  .show-desc { color: rgba(255,255,255,0.65); font-size: 12px; margin-top: 2px; line-height: 1.3; }
  .download-btn { color: #fff; font-size: 13px; font-weight: 500; opacity: 0.85; cursor: pointer; }

  /* Progress Bar with Drag & Scrub support */
  .progress-wrap {
    margin-bottom: 8px;
    cursor: pointer;
    padding: 8px 0; /* Expanded hit area for easy touch / drag */
    user-select: none;
    -webkit-user-select: none;
    touch-action: none;
  }
  .progress-track {
    height: 3px;
    background: rgba(255,255,255,0.25);
    border-radius: 3px;
    position: relative;
    transition: height 0.15s ease;
  }
  .progress-wrap:hover .progress-track,
  .progress-wrap.is-dragging .progress-track {
    height: 5px;
  }
  .progress-fill {
    height: 100%;
    background: #fff;
    border-radius: 3px;
    width: 0%;
    position: relative;
    pointer-events: none;
  }
  .progress-thumb {
    width: 12px;
    height: 12px;
    background: #fff;
    border-radius: 50%;
    position: absolute;
    right: -6px;
    top: 50%;
    transform: translateY(-50%) scale(1);
    box-shadow: 0 0 6px rgba(0,0,0,0.6);
    transition: transform 0.15s ease;
    pointer-events: none;
  }
  .progress-wrap:hover .progress-thumb,
  .progress-wrap.is-dragging .progress-thumb {
    transform: translateY(-50%) scale(1.3);
  }

  /* Details Screen */
  #details-screen { background: var(--bg); }
  .details-video-thumb { height: 260px; background: linear-gradient(160deg, #1a2a3a 0%, #0d1b2a 50%, #162030 100%); position: relative; cursor: pointer; overflow: hidden; flex-shrink: 0; }
  .thumb-scene { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
  
  .details-content { flex: 1; overflow-y: auto; background: var(--surface); padding: 16px 16px 0; }
  .details-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
  .details-title { color: var(--text); font-size: 20px; font-weight: 700; margin-bottom: 6px; }
  .details-views { color: var(--text2); font-size: 13px; margin-bottom: 6px; }
  .details-rating { display: flex; align-items: center; gap: 8px; color: var(--text2); font-size: 13px; }
  .rating-star { color: #ffc107; font-size: 15px; }
  .chat-btn { width: 64px; height: 64px; border-radius: 50%; background: var(--accent); display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; font-size: 10px; font-weight: 600; cursor: pointer; }
  .chat-btn svg { width: 22px; height: 22px; }

  .details-tabs { display: flex; gap: 24px; border-bottom: 1px solid var(--border); margin-bottom: 16px; }
  .tab-btn { padding: 10px 0; font-size: 15px; font-weight: 500; color: var(--text3); cursor: pointer; }
  .tab-btn.active { color: var(--text); font-weight: 700; border-bottom: 2px solid var(--text); }
  .tab-content { display: none; }
  .tab-content.active { display: block; }

  .synopsis-text { color: var(--text2); font-size: 14px; line-height: 1.7; padding-bottom: 24px; }
  
  .episode-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; padding-bottom: 30px; }
  .ep-btn { aspect-ratio: 1; border-radius: 10px; background: var(--surface2); color: var(--text2); font-size: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; position: relative;}
  .ep-btn.active-ep { color: var(--text); border: 1.5px solid #555; }
  .ep-progress-icon { position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); display: flex; gap: 1.5px; align-items: flex-end; height: 8px; }
  .ep-bar { width: 2px; background: var(--accent); border-radius: 1px; }
  
  /* Settings / Comments Sheets */
  .sheet-screen { background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
  .sheet-backdrop { flex: 1; cursor: pointer; }
  .sheet-content { background: var(--surface); border-radius: 24px 24px 0 0; padding: 20px 0 40px; animation: slideUp 0.3s; }
  @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
  #episodes-sheet.is-open,
  #comments-sheet.is-open,
  #settings-sheet.is-open,
  #auth-modal.is-open {
    transform: translateY(0) !important;
  }
  .sheet-header { display: flex; justify-content: space-between; padding: 0 20px 16px; border-bottom: 1px solid var(--border); }
  .sheet-title { color: var(--text); font-size: 17px; font-weight: 600; }
  .close-btn { color: var(--text2); cursor: pointer; }
  
  /* Settings specific */
  .settings-item { display: flex; align-items: center; padding: 16px 20px; gap: 14px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); }
  .settings-item-icon { color: var(--text2); }
  .settings-item-icon svg { width: 24px; height: 24px; }
  .settings-item-label { flex: 1; color: var(--text); font-size: 15px; }
  .settings-item-value { color: var(--text2); font-size: 14px; display: flex; align-items: center; gap: 4px; }

  /* Status bar */
  .status-bar { position: absolute; top: 0; left: 0; right: 0; height: 44px; display: flex; align-items: center; justify-content: space-between; padding: 0 24px; z-index: 100; pointer-events: none; }
  .status-time { color: #fff; font-size: 15px; font-weight: 600; }
  .status-icons { display: flex; align-items: center; gap: 6px; }
  .status-icons svg { width: 16px; height: 16px; color: #fff; }
  
  /* Center Play/Pause Overlay */
  .center-play-pause-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 25;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    user-select: none;
    transition: opacity 0.25s ease, transform 0.25s ease;
    padding: 16px; /* Generous touch target area */
  }
  .center-play-icon-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    box-shadow: none;
    transition: transform 0.15s ease;
  }
  .center-play-pause-btn:active .center-play-icon-circle {
    transform: scale(0.9);
  }
  .center-play-icon-circle svg {
    width: 44px;
    height: 44px;
    fill: #ffffff;
    filter: drop-shadow(0 3px 10px rgba(0,0,0,0.7)) drop-shadow(0 1px 2px rgba(0,0,0,0.9));
  }
  
  /* Desktop 2-Column Responsive Layout */
  @media (min-width: 900px) {
    body {
      background: #0a0a0d !important;
      overflow-y: auto !important;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .watch-desktop-layout {
      display: flex !important;
      flex-direction: row !important;
      max-width: 1300px;
      width: 95%;
      height: 90vh;
      max-height: 880px;
      min-height: 620px;
      margin: 44px auto 32px;
      background: transparent !important;
      border-radius: 0 !important;
      border: none !important;
      box-shadow: none !important;
      overflow: visible;
      position: relative;
      gap: 36px;
    }

    .watch-left-col {
      width: 440px !important;
      min-width: 380px !important;
      max-width: 450px !important;
      height: 100% !important;
      max-height: 100% !important;
      margin: 0 !important;
      border-radius: 14px !important;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.6) !important;
      background: #000;
      flex-shrink: 0;
      position: relative;
      border-right: none !important;
      overflow: hidden;
    }

    .watch-left-col .screen {
      position: relative;
      height: 100%;
    }

    .watch-left-col .video-area {
      height: 100%;
    }

    .watch-right-col {
      flex: 1;
      min-width: 0;
      height: 100%;
      background: transparent !important;
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      padding: 0 !important;
      box-sizing: border-box;
    }

    /* Modal / Bottom sheet centering on desktop */
    #settings-sheet {
      width: 100% !important;
      max-width: 440px !important;
      left: 50% !important;
      right: auto !important;
      bottom: 0 !important;
      border-radius: 20px 20px 0 0 !important;
      transform: translate(-50%, 100%) !important;
      transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    #settings-sheet.is-open {
      transform: translate(-50%, 0) !important;
    }

    #auth-modal {
      width: 90% !important;
      max-width: 420px !important;
      left: 50% !important;
      right: auto !important;
      top: 50% !important;
      bottom: auto !important;
      border-radius: 20px !important;
      border: 1px solid rgba(255, 255, 255, 0.12) !important;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85) !important;
      transform: translate(-50%, -46%) scale(0.96) !important;
      opacity: 0 !important;
      pointer-events: none !important;
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.25s ease !important;
    }
    #auth-modal.is-open {
      transform: translate(-50%, -50%) scale(1) !important;
      opacity: 1 !important;
      pointer-events: auto !important;
    }
    #auth-modal .modal-drag-handle {
      display: none !important;
    }

    /* Never show mobile bottom sheets on desktop */
    #episodes-sheet,
    #comments-sheet {
      display: none !important;
    }

    /* Hide floating mobile sidebar, description, and user/speed buttons inside video on desktop */
    .watch-left-col .player-sidebar {
      display: none !important;
    }
    .watch-left-col .player-top-right {
      display: none !important;
    }
    .watch-left-col .player-bottom .show-info {
      display: none !important;
    }
    .watch-left-col .subtitle {
      display: none !important;
    }
    .watch-left-col .player-top {
      justify-content: flex-start !important;
      padding: 14px 16px !important;
      background: linear-gradient(180deg, rgba(0,0,0,0.4) 0%, transparent 100%) !important;
    }
    .watch-left-col .player-bottom {
      padding: 0 16px 14px !important;
      background: linear-gradient(0deg, rgba(0,0,0,0.5) 0%, transparent 100%) !important;
    }
  }

  @media (max-width: 899px) {
    body {
      background: #000 !important;
      overflow: hidden !important;
      width: 100vw !important;
      height: 100vh !important;
      height: 100dvh !important;
    }
    .watch-right-col {
      display: none !important;
    }
    .watch-desktop-layout {
      width: 100vw !important;
      max-width: 100vw !important;
      height: 100vh !important;
      height: 100dvh !important;
      margin: 0 !important;
      padding: 0 !important;
      border-radius: 0 !important;
      border: none !important;
      box-shadow: none !important;
      display: block !important;
      position: relative !important;
      overflow: hidden !important;
    }
    .watch-left-col {
      width: 100vw !important;
      max-width: 100vw !important;
      height: 100vh !important;
      height: 100dvh !important;
      margin: 0 !important;
      padding: 0 !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      position: relative !important;
      overflow: hidden !important;
    }
    #player-screen {
      width: 100% !important;
      height: 100% !important;
      position: relative !important;
      display: flex !important;
      flex-direction: column !important;
    }
    .video-area {
      width: 100% !important;
      height: 100% !important;
      flex: 1 !important;
      position: relative !important;
    }
  }

  /* Right column styling */
  .dt-back-crumb { display: inline-flex; align-items: center; gap: 6px; color: #888; text-decoration: none; font-size: 13px; font-weight: 500; transition: color 0.2s; }
  .dt-back-crumb:hover { color: #fff; }
  .dt-crumb-sep { color: #555; margin: 0 8px; font-size: 12px; }
  .dt-crumb-ep { color: #ff2d55; font-size: 13px; font-weight: 600; }
  .dt-series-title { color: #fff; font-size: 24px; font-weight: 800; margin: 12px 0 10px; line-height: 1.25; }
  
  .dt-meta-row { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
  .dt-meta-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 12px; background: rgba(255,255,255,0.06); color: #ccc; font-size: 12px; font-weight: 600; }
  .dt-genre-tag { padding: 4px 10px; border-radius: 12px; background: rgba(124, 77, 255, 0.15); color: #b388ff; border: 1px solid rgba(124, 77, 255, 0.3); font-size: 11px; font-weight: 600; }
  
  .dt-actions-row { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
  .dt-action-btn { display: inline-flex; align-items: center; gap: 8px; background: #242429; color: #fff; border: 1px solid rgba(255,255,255,0.08); padding: 9px 18px; border-radius: 22px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
  .dt-action-btn:hover { background: #2f2f36; border-color: rgba(255,255,255,0.18); transform: translateY(-1px); }
  .dt-action-btn svg { width: 18px; height: 18px; }
  .dt-action-btn.is-active svg { color: #ff2d55; }
  .dt-action-btn.is-active { border-color: rgba(255,45,85,0.4); background: rgba(255,45,85,0.12); color: #ff2d55; }

  .dt-vip-cta { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #ff9800, #ff5722); color: #fff; text-decoration: none; padding: 9px 20px; border-radius: 22px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 15px rgba(255,87,34,0.35); margin-left: auto; transition: transform 0.2s; }
  .dt-vip-cta:hover { transform: translateY(-1px); color: #fff; }

  .dt-plot-card { background: transparent; border: none; border-radius: 0; padding: 0; margin-bottom: 20px; }
  .dt-plot-head { color: #fff; font-size: 14px; font-weight: 700; margin-bottom: 6px; }
  .dt-plot-body { color: #aaa; font-size: 13px; line-height: 1.6; margin: 0; max-height: 60px; overflow: hidden; transition: max-height 0.3s; }
  .dt-plot-body.is-expanded { max-height: 400px; }
  .dt-plot-more { display: none; background: none; border: none; color: #ff2d55; font-size: 12px; font-weight: 600; padding: 4px 0 0; cursor: pointer; }

  .dt-rating-widget { background: transparent; border: none; border-radius: 0; padding: 0; margin-bottom: 22px; }

  /* Player Bottom Controls (Speed, Quality, Fullscreen) */
  .player-ctrls-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 10px;
    position: relative;
    z-index: 12;
  }
  .player-ctrl-btn {
    background: rgba(0, 0, 0, 0.38);
    border: none !important;
    outline: none !important;
    color: #fff;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: all 0.2s ease;
  }
  .player-ctrl-btn:hover {
    background: rgba(0, 0, 0, 0.65);
    border: none !important;
    color: #fff;
  }
  .player-ctrl-btn svg {
    width: 14px;
    height: 14px;
  }

  /* Volume dropdown container & vertical popup menu */
  .volume-dropdown-wrap {
    position: relative;
    display: inline-block;
  }
  .volume-popup-menu {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: none !important;
    border-radius: 14px;
    padding: 10px 8px;
    min-width: 44px;
    width: 46px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.5);
    display: none;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    z-index: 25;
    box-sizing: border-box;
  }
  .volume-popup-menu.show {
    display: flex;
    animation: volumePopupFade 0.15s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes volumePopupFade {
    from { opacity: 0; transform: translate(-50%, 6px); }
    to { opacity: 1; transform: translate(-50%, 0); }
  }
  .volume-popup-val {
    font-size: 11px;
    font-weight: 700;
    color: #fff;
    text-align: center;
    line-height: 1;
    user-select: none;
  }
  .volume-vertical-track-wrap {
    position: relative;
    width: 24px;
    height: 90px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .volume-vertical-range {
    -webkit-appearance: slider-vertical;
    appearance: slider-vertical;
    writing-mode: bt-lr;
    width: 8px;
    height: 90px;
    accent-color: #e11d48;
    cursor: pointer;
    background: transparent;
    border-radius: 4px;
    outline: none;
    border: none;
    margin: 0;
  }
  .volume-popup-mute-btn {
    background: rgba(255, 255, 255, 0.12);
    border: none !important;
    border-radius: 6px;
    color: #cbd5e1;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 0;
    width: 100%;
    text-align: center;
    cursor: pointer;
    line-height: 1.2;
    transition: all 0.15s ease;
  }
  .volume-popup-mute-btn:hover {
    background: rgba(225, 29, 72, 0.4);
    color: #fff;
  }
  .volume-popup-mute-btn.is-muted {
    background: rgba(225, 29, 72, 0.4);
    color: #fca5a5;
  }

  /* Quality dropdown container & menu */
  .quality-dropdown-wrap {
    position: relative;
    display: inline-block;
  }
  .quality-menu {
    position: absolute;
    bottom: calc(100% + 10px);
    right: 0;
    background: rgba(18, 19, 26, 0.96);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: none !important;
    outline: none !important;
    border-radius: 14px;
    padding: 6px;
    min-width: 175px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.8) !important;
    display: none;
    flex-direction: column;
    gap: 3px;
    z-index: 40;
  }
  .quality-menu.show {
    display: flex;
    animation: shortQMenuPop 0.18s cubic-bezier(0.16, 1, 0.3, 1);
  }
  @keyframes shortQMenuPop {
    from { opacity: 0; transform: translateY(6px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }
  .quality-menu-header {
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #94a3b8;
    padding: 6px 10px 6px 10px;
    border: none !important;
    border-bottom: none !important;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .quality-menu-item {
    background: transparent;
    border: none;
    outline: none;
    color: #e2e8f0;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 10px;
    border-radius: 8px;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.15s ease;
    gap: 10px;
    width: 100%;
    box-sizing: border-box;
  }
  .quality-menu-item:hover {
    background: rgba(255, 255, 255, 0.09);
    color: #ffffff;
  }
  .quality-menu-item.active {
    background: rgba(var(--theme-accent-rgb, 255, 45, 85), 0.18) !important;
    color: #ffffff !important;
    font-weight: 700;
  }
  .quality-menu-item-left {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .quality-menu-check {
    width: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--theme-accent, #ff2d55);
    font-size: 13px;
    font-weight: 900;
  }
  .quality-menu-name {
    font-size: 13px;
    color: #ffffff;
  }
  .quality-menu-badge {
    font-size: 10px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
    letter-spacing: 0.3px;
  }
  .quality-menu-badge.is-vip {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: #ffffff !important;
    padding: 2px 7px !important;
    border-radius: 6px !important;
    font-size: 10px !important;
    font-weight: 800 !important;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35) !important;
    letter-spacing: 0.3px !important;
  }
  .quality-menu-item.active .quality-menu-badge:not(.is-vip) {
    background: var(--theme-accent, #ff2d55);
    color: #ffffff;
  }
  .quality-menu-item.active .quality-menu-badge.is-vip {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: #ffffff !important;
  }

  .dt-tabs-bar { display: flex; gap: 20px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 18px; }
  .dt-tab { background: none; border: none; padding: 10px 4px; color: #777; font-size: 15px; font-weight: 600; cursor: pointer; border-bottom: 2px solid transparent; transition: all 0.2s; }
  .dt-tab:hover { color: #bbb; }
  .dt-tab.active { color: #fff; border-bottom-color: #ff2d55; }
  .dt-tab-count { font-size: 12px; opacity: 0.7; font-weight: 400; }

  /* Desktop Episodes Grid */
  .desktop-episodes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(52px, 1fr));
    gap: 8px;
    padding-bottom: 20px;
  }
  .reel-ep-btn {
    aspect-ratio: 1;
    border-radius: 10px;
    background: #232328;
    border: 1px solid rgba(255,255,255,0.06);
    color: #bbb;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.15s ease;
    text-decoration: none;
  }
  .reel-ep-btn:hover {
    background: #2c2c33;
    color: #fff;
    border-color: rgba(255,255,255,0.15);
    transform: translateY(-2px);
  }
  .reel-ep-btn.is-active {
    background: rgba(124, 77, 255, 0.2);
    border: 1.5px solid #7c4dff;
    color: #fff;
    box-shadow: 0 0 12px rgba(124, 77, 255, 0.35);
  }
  .reel-ep-lock-icon {
    font-size: 10px;
    position: absolute;
    top: 3px;
    right: 4px;
  }

  /* Soundwave equalizer */
  .reel-ep-equalizer {
    display: inline-flex;
    align-items: flex-end;
    gap: 1.5px;
    height: 10px;
    margin-top: 3px;
  }
  .reel-ep-equalizer span {
    width: 2px;
    background: #7c4dff;
    border-radius: 1px;
    animation: stv-eq 0.7s ease-in-out infinite alternate;
  }
  .reel-ep-equalizer span:nth-child(1) { height: 4px; animation-delay: 0.1s; }
  /* Rewarded Video Ad Modal Styles */
  .short-reward-video-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    width: 100% !important;
    height: 100vh !important;
    height: 100dvh !important;
    z-index: 99999999 !important;
    display: none;
    padding: 0 !important;
    margin: 0 !important;
    background: #000000 !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
  }
  .reward-video-backdrop { display: none !important; }
  .reward-video-card {
    position: absolute !important;
    inset: 0 !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: 100% !important;
    max-height: 100% !important;
    background: #000000 !important;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: hidden !important;
  }
  .reward-video-top-bar {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 30 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: max(16px, env(safe-area-inset-top, 16px)) 20px 24px 20px !important;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.4) 70%, transparent 100%) !important;
    box-sizing: border-box !important;
  }
  .reward-video-badge { display: inline-flex; align-items: center; gap: 8px; }
  .reward-video-badge .ad-tag {
    background: #ff6b00;
    color: #fff;
    font-size: 11px;
    font-weight: 900;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.5px;
  }
  .reward-video-badge .ad-title {
    font-size: 13.5px;
    font-weight: 700;
    color: #ffffff;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
  }
  .reward-video-timer-pill {
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 800;
    color: #fbbf24;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
  }
  .reward-video-close-btn {
    background: transparent !important;
    border: none !important;
    width: 38px !important;
    height: 38px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    color: #ffffff !important;
    cursor: pointer !important;
    filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.9)) !important;
  }
  .reward-video-player-wrap {
    position: absolute !important;
    inset: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: #000000 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 10 !important;
  }
  .reward-video-player-wrap video {
    width: 100% !important;
    height: 100% !important;
    object-fit: contain !important;
    display: block !important;
    background: #000 !important;
  }
  .reward-video-sound-float-btn {
    position: absolute !important;
    bottom: 90px !important;
    right: 20px !important;
    z-index: 25 !important;
    width: 44px !important;
    height: 44px !important;
    border-radius: 50% !important;
    background: rgba(0, 0, 0, 0.65) !important;
    backdrop-filter: blur(8px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #ffffff !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
  }
  .reward-video-bottom-bar {
    position: absolute !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 30 !important;
    padding: 24px 20px max(20px, env(safe-area-inset-bottom, 20px)) 20px !important;
    background: linear-gradient(0deg, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.5) 70%, transparent 100%) !important;
  }
  .reward-video-progress-track {
    width: 100% !important;
    height: 4px !important;
    background: rgba(255, 255, 255, 0.2) !important;
    border-radius: 2px !important;
    margin-bottom: 12px !important;
    overflow: hidden !important;
  }
  .reward-video-progress-fill {
    height: 100% !important;
    background: linear-gradient(90deg, #ff5e00 0%, #ff3700 100%) !important;
    width: 0%;
    transition: width 0.3s ease !important;
  }
  .reward-video-footer-info {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    color: #cbd5e1 !important;
    font-size: 13px !important;
    font-weight: 700 !important;
  }
  .reward-coins-tag { color: #fbbf24 !important; font-weight: 800 !important; }
  .reward-video-completed-overlay {
    position: absolute !important;
    inset: 0 !important;
    z-index: 50 !important;
    background: rgba(0, 0, 0, 0.88) !important;
    backdrop-filter: blur(12px) !important;
    display: none;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
  }
  .reward-video-completed-overlay.show { display: flex !important; }
  .completed-content { display: flex; flex-direction: column; align-items: center; }
  .completed-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(251, 191, 36, 0.15);
    border: 2px solid #fbbf24;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 16px;
    animation: pulseRewardBadge 1s infinite alternate;
  }
  @keyframes pulseRewardBadge {
    0% { transform: scale(1); box-shadow: 0 0 10px rgba(251, 191, 36, 0.3); }
    100% { transform: scale(1.08); box-shadow: 0 0 30px rgba(251, 191, 36, 0.7); }
  }
  .completed-title { font-size: 20px; font-weight: 800; color: #fff; margin: 0 0 8px 0; }
  .completed-reward-amount { font-size: 26px; font-weight: 900; color: #fbbf24; }
  .short-reward-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(10px);
    z-index: 100000000;
  }
  .reward-modal-backdrop { position: absolute; inset: 0; }
  .reward-modal-card {
    position: relative;
    background: #181920;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 24px;
    max-width: 320px;
    width: 90%;
    text-align: center;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.8);
  }
  .reward-confirm-title { font-size: 16px; font-weight: 800; color: #fff; margin: 12px 0 6px; }
  .reward-confirm-desc { font-size: 12.5px; color: #94a3b8; line-height: 1.4; margin: 0 0 20px; }
  .reward-confirm-actions { display: flex; flex-direction: column; gap: 8px; }
  .reward-confirm-btn-continue {
    background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%);
    border: none; border-radius: 12px; height: 44px; color: #fff; font-weight: 800; font-size: 13.5px; cursor: pointer;
  }
  .reward-confirm-btn-exit {
    background: rgba(255, 255, 255, 0.08);
    border: none; border-radius: 12px; height: 40px; color: #94a3b8; font-weight: 700; font-size: 12.5px; cursor: pointer;
  }

  /* Custom VIP Quality & Dialog Modals */
  .watch-vip-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.78);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 100000000;
    opacity: 0;
    transition: opacity 0.22s ease;
  }
  .watch-vip-modal-overlay.is-active {
    opacity: 1;
  }
  .watch-vip-modal-backdrop {
    position: absolute;
    inset: 0;
  }
  .watch-vip-modal-card {
    position: relative;
    background: linear-gradient(180deg, #1f1f27 0%, #131418 100%);
    border: 1px solid rgba(251, 191, 36, 0.35);
    border-radius: 22px;
    padding: 28px 24px;
    max-width: 380px;
    width: 90%;
    text-align: center;
    color: #fff;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.85), 0 0 35px rgba(245, 158, 11, 0.15);
    transform: scale(0.92);
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .watch-vip-modal-overlay.is-active .watch-vip-modal-card {
    transform: scale(1);
  }
  .watch-vip-modal-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }
  .watch-vip-modal-icon {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.2) 0%, rgba(245, 158, 11, 0.05) 100%);
    border: 1px solid rgba(251, 191, 36, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fbbf24;
    box-shadow: 0 4px 16px rgba(251, 191, 36, 0.25);
  }
  .watch-vip-pill-badge {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #000;
    font-size: 10.5px;
    font-weight: 900;
    letter-spacing: 0.8px;
    padding: 3px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4);
  }
  .watch-vip-modal-title {
    font-size: 20px;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 8px 0;
    line-height: 1.3;
  }
  .watch-vip-modal-desc {
    font-size: 13px;
    color: #94a3b8;
    line-height: 1.45;
    margin: 0 0 18px 0;
  }
  .watch-vip-modal-perks {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 14px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
    text-align: left;
  }
  .watch-vip-perk-item {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 12.5px;
    font-weight: 600;
    color: #e2e8f0;
  }
  .watch-vip-perk-item svg {
    flex-shrink: 0;
  }
  .watch-vip-modal-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .watch-vip-btn-upgrade {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #000 !important;
    font-size: 14px;
    font-weight: 800;
    text-decoration: none;
    height: 44px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.35);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
  }
  .watch-vip-btn-upgrade:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 22px rgba(245, 158, 11, 0.5);
    color: #000 !important;
  }
  .watch-vip-btn-cancel {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    height: 38px;
    color: #94a3b8;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
  }
  .watch-vip-btn-cancel:hover {
    background: rgba(255, 255, 255, 0.06);
    color: #ffffff;
  }

  /* Generic Watch Custom Confirm Modal */
  .watch-confirm-modal-overlay {
    position: fixed !important;
    inset: 0 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 100000000;
    opacity: 0;
    transition: opacity 0.2s ease;
  }
  .watch-confirm-modal-overlay.is-active {
    opacity: 1;
  }
  .watch-confirm-modal-backdrop {
    position: absolute;
    inset: 0;
  }
  .watch-confirm-modal-card {
    position: relative;
    background: #181920;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 24px;
    max-width: 340px;
    width: 90%;
    text-align: center;
    color: #fff;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.8);
    transform: scale(0.92);
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .watch-confirm-modal-overlay.is-active .watch-confirm-modal-card {
    transform: scale(1);
  }
  .watch-confirm-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
  }
  .watch-confirm-title {
    font-size: 17px;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 6px 0;
  }
  .watch-confirm-msg {
    font-size: 13px;
    color: #94a3b8;
    line-height: 1.45;
    margin: 0 0 20px 0;
  }
  .watch-confirm-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .watch-confirm-btn-ok {
    background: linear-gradient(135deg, var(--theme-accent, #e11d48) 0%, #be123c 100%);
    border: none;
    border-radius: 12px;
    height: 42px;
    color: #fff;
    font-weight: 700;
    font-size: 13.5px;
    cursor: pointer;
  }
  .watch-confirm-btn-cancel {
    background: rgba(255, 255, 255, 0.08);
    border: none;
    border-radius: 12px;
    height: 38px;
    color: #94a3b8;
    font-weight: 600;
    font-size: 12.5px;
    cursor: pointer;
  }
</style>

<div class="watch-desktop-layout" id="shorttv-app-container">
  <!-- LEFT COLUMN: VIDEO PLAYER (9:16) -->
  <div class="phone watch-left-col" id="shorttv-phone-col">

  <!-- PLAYER SCREEN -->
  <div class="screen" id="player-screen">
    

    <?php 
    $security_settings = get_option('short_security_settings', array());
    $anti_click = empty($security_settings['allow_right_click']) ? 'oncontextmenu="return false;"' : ''; 
    ?>
    <div class="video-area" id="video-area" onclick="toggleUI(event)" <?php echo $anti_click; ?>>
      
      <?php if ( $poster ) : ?>
      <img id="shorttv-poster-bg" class="stv-poster-bg" src="<?php echo esc_url( $poster ); ?>" alt="" <?php echo $anti_click; ?> ondragstart="return false;" />
      <?php endif; ?>
      
      <video id="shorttv-video" class="stv-video" playsinline webkit-playsinline x5-playsinline preload="auto" controlsList="nodownload noplaybackrate nofullscreen" disablePictureInPicture <?php echo $anti_click; ?> ondragstart="return false;"></video>
      <div id="center-play-pause-btn" onclick="togglePlayPause(event)" class="center-play-pause-btn" title="Play / Pause">
        <div class="center-play-icon-circle">
          <svg viewBox="0 0 24 24" fill="white" id="center-play-icon"><path d="M8 5v14l11-7z"/></svg>
        </div>
      </div>

      <!-- Floating Unmute Audio Pill -->
      <div id="player-unmute-pill" onclick="unmuteFromBanner(event)" style="display:none; position:absolute; top:58px; left:50%; transform:translateX(-50%); z-index:35; background:rgba(225,29,72,0.92); backdrop-filter:blur(8px); color:#fff; font-size:12px; font-weight:800; padding:7px 16px; border-radius:999px; box-shadow:0 4px 16px rgba(0,0,0,0.6); cursor:pointer; align-items:center; gap:6px; border:1px solid rgba(255,255,255,0.3);">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
        <span>Tap to Unmute Audio</span>
      </div>

      <!-- ══ EPISODE PAYWALL OVERLAY ══ -->
      <div id="shorttv-paywall-modal" class="shorttv-paywall-modal" style="display:none;position:absolute;inset:0;z-index:40;flex-direction:column;align-items:center;justify-content:center;padding:20px;text-align:center;box-sizing:border-box;overflow:hidden;">

        <!-- Paywall Back Button (Top Left) -->
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="paywall-top-back-btn" title="<?php esc_attr_e( 'Back', 'short-stream' ); ?>" style="position:absolute; top:calc(env(safe-area-inset-top, 0px) + 16px); left:16px; z-index:10; width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,0.12); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); display:flex; align-items:center; justify-content:center; color:#ffffff; text-decoration:none; border:none; cursor:pointer; transition:background 0.2s, transform 0.15s;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </a>

        <!-- Blurred backdrop from poster -->
        <div style="position:absolute;inset:0;background:rgba(0,0,0,0.82);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);z-index:0;"></div>

        <!-- Content -->
        <div style="position:relative;z-index:1;width:100%;max-width:300px;">

          <!-- Lock Icon -->
          <div id="paywall-lock-icon-wrap" style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,rgba(255,45,85,0.2),rgba(255,152,0,0.1));border:1.5px solid rgba(255,45,85,0.5);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:#ff2d55;">
            <svg id="paywall-lock-svg" width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
          </div>

          <h3 id="paywall-ep-title" style="color:#fff;font-size:17px;font-weight:800;margin:0 0 4px;">Episode Locked</h3>
          <p id="paywall-ep-desc" style="color:#999;font-size:11px;margin:0 0 6px;line-height:1.5;">This episode requires <strong style="color:#ffc107;">💰 <span id="paywall-coin-cost">15</span> Coins</strong> to unlock.</p>

          <!-- Coin Balance Row -->
          <div id="paywall-coin-balance-wrap" style="display:flex;align-items:center;justify-content:center;gap:6px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:7px 14px;margin-bottom:16px;">
            <span style="color:#aaa;font-size:11px;">Your balance:</span>
            <span id="paywall-user-coins" style="color:#ffc107;font-size:13px;font-weight:800;">100</span>
            <span style="color:#aaa;font-size:11px;">💰</span>
          </div>

          <!-- VIP Direct Unlock Button (Shown only for VIP Exclusives) -->
          <a id="btn-unlock-vip-primary" href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>"
            style="display:none;width:100%;height:48px;background:linear-gradient(135deg,#ff2d55 0%,#d97706 100%);border:none;border-radius:14px;color:#fff;font-size:13.5px;font-weight:800;cursor:pointer;margin-bottom:12px;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 18px rgba(217,119,6,0.45);text-decoration:none;transition:transform 0.15s, box-shadow 0.15s;">
            <span style="font-size:16px;">👑</span> Upgrade to VIP Pass
          </a>

          <!-- ── Option 1: Use Coins ── -->
          <button type="button" id="btn-unlock-coins" onclick="paywallUnlockCoins()"
            style="width:100%;height:46px;background:linear-gradient(135deg,#ff2d55,#ff5f7e);border:none;border-radius:14px;color:#fff;font-size:13px;font-weight:700;cursor:pointer;margin-bottom:8px;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 16px rgba(255,45,85,0.35);transition:opacity 0.2s;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
            💰 Use <span id="paywall-cost-inline">15</span> Coins to Unlock
          </button>
          <div id="paywall-coin-warning" style="display:none;background:rgba(255,82,82,0.12);border:1px solid rgba(255,82,82,0.3);border-radius:999px;padding:6px 10px;margin-bottom:8px;font-size:11px;color:#ff7070;">
            ⚠️ Not enough coins. <a href="<?php echo esc_url( home_url( '/subscription/?tab=coins' ) ); ?>" style="color:#ffc107;font-weight:700;">Buy more coins →</a>
          </div>

          <!-- ── Option 2: Ad Unlock ── -->
          <?php 
          $short_ads_opt   = get_option( 'short_ad_settings', array() );
          $ad_enabled      = ! isset( $short_ads_opt['enable_rewarded_ad'] ) || ! empty( $short_ads_opt['enable_rewarded_ad'] );
          $ad_mode         = $short_ads_opt['ad_unlock_mode'] ?? 'clicks';
          $ad_req_clicks   = ! empty( $short_ads_opt['ad_required_clicks'] ) ? (int) $short_ads_opt['ad_required_clicks'] : 3;
          $ad_reward_val   = ! empty( $short_ads_opt['ad_reward_coins'] ) ? (int) $short_ads_opt['ad_reward_coins'] : 5;
          $ad_cd_val       = ! empty( $short_ads_opt['ad_countdown_seconds'] ) ? (int) $short_ads_opt['ad_countdown_seconds'] : 15;
          if ( $ad_enabled ) :
          ?>
          <button type="button" id="btn-unlock-ad" onclick="paywallWatchAd()"
            style="width:100%;height:44px;background:#1e1e28;border:1.5px solid rgba(66,165,245,0.4);border-radius:14px;color:#fff;font-size:12.5px;font-weight:700;cursor:pointer;margin-bottom:8px;display:flex;align-items:center;justify-content:center;gap:8px;transition:background 0.2s, transform 0.15s;box-shadow:0 4px 14px rgba(66,165,245,0.15);">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="#42a5f5"><path d="M8 5v14l11-7z"/></svg>
            <?php if ( 'video_coins' === $ad_mode ) : ?>
              <span id="paywall-ad-btn-label" style="color:#42a5f5;font-weight:700;">Watch Ad to Earn Coins</span>
              <span style="font-size:10.5px;color:#ffc107;background:rgba(255,193,7,0.15);padding:1px 6px;border-radius:999px;font-weight:800;">+<?php echo $ad_reward_val; ?> 💰</span>
            <?php else : ?>
              <span id="paywall-ad-btn-label" style="color:#42a5f5;font-weight:700;">Click Ad to Unlock (<span id="paywall-ad-clicks-done">0</span>/<span id="paywall-ad-clicks-req"><?php echo $ad_req_clicks; ?></span>)</span>
            <?php endif; ?>
          </button>

          <!-- Ad Feedback / Progress -->
          <div id="paywall-ad-countdown" style="display:none;background:rgba(66,165,245,0.1);border:1px solid rgba(66,165,245,0.3);border-radius:10px;padding:8px 12px;margin-bottom:8px;font-size:11.5px;color:#42a5f5;">
            <div id="paywall-ad-bar-wrap" style="width:100%;height:4px;background:rgba(255,255,255,0.1);border-radius:2px;margin-bottom:6px;overflow:hidden;">
              <div id="paywall-ad-bar" style="height:100%;width:0%;background:#42a5f5;border-radius:2px;transition:width 0.3s;"></div>
            </div>
            <span id="paywall-ad-status-msg">
              <?php if ( 'video_coins' === $ad_mode ) : ?>
                🚀 Sponsored ad opened! <span id="paywall-ad-timer"><?php echo $ad_cd_val; ?></span>s remaining to unlock free...
              <?php else : ?>
                🚀 Click <span id="paywall-ad-remaining"><?php echo $ad_req_clicks; ?></span> more time(s) to unlock free.
              <?php endif; ?>
            </span>
          </div>

          <!-- Daily Ad Limit Notice (shown when all daily unlocks consumed) -->
          <div id="paywall-ad-limit-notice" style="display:none;background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);border-radius:10px;padding:8px 12px;margin-bottom:8px;font-size:11.5px;color:#ffc107;line-height:1.4;">
            🔒 <span id="paywall-ad-limit-msg">Daily free ad unlock limit reached. Use Coins or VIP Pass to continue watching!</span>
          </div>
          <?php endif; ?>

          <div id="paywall-bottom-actions" style="display:flex;gap:7px;margin-bottom:6px;">
            <!-- ── Option 3: Buy Coins ── -->
            <button type="button" id="paywall-btn-buy-coins" onclick="window.location.href='<?php echo esc_js( home_url( '/subscription/?tab=coins' ) ); ?>'"
              style="flex:1;height:40px;background:#19191d;border:1.5px solid rgba(255,193,7,0.4);border-radius:12px;color:#ffc107;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:background 0.2s;">
              💰 Buy Coins
            </button>

            <!-- ── Option 4: VIP Pass ── -->
            <a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>"
              style="flex:1;height:40px;background:linear-gradient(135deg,rgba(124,77,255,0.2),rgba(255,45,85,0.1));border:1.5px solid rgba(124,77,255,0.5);border-radius:12px;color:#b388ff;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;text-decoration:none;transition:background 0.2s;">
              👑 VIP Pass
            </a>
          </div>

          <div id="paywall-footer-note" style="color:#555;font-size:10px;">VIP Pass unlocks all episodes forever</div>
        </div>
      </div>


      <div class="player-top">
        <div style="display:flex;align-items:center;gap:8px;">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#fff;display:flex;align-items:center;text-decoration:none;padding:4px;cursor:pointer;" title="Back to Home">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:22px;height:22px;"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
          </a>
          <div id="player-ep-top-btn" style="color:#fff;font-size:15px;font-weight:700;display:flex;align-items:center;gap:4px;">
            <span id="player-ep-top-label">EP.<?php echo (int) $episode; ?></span>
          </div>
        </div>
        <div class="player-top-right" style="display:flex;align-items:center;gap:10px;">
          <!-- User / Auth Profile Button -->
          <div id="player-user-btn" onclick="handleUserAuthClick(event)" style="display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.35);padding:6px 10px;border-radius:20px;backdrop-filter:blur(4px);cursor:pointer;color:#fff;font-size:12px;gap:5px;">
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            <span id="player-user-name">Sign In</span>
          </div>
        </div>
      </div>


      <div class="player-sidebar">
        <!-- Like / Heart -->
        <div class="sidebar-btn" id="sidebar-like-btn" onclick="toggleLike(this)">
          <div class="sidebar-icon"><svg id="sidebar-like-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg></div>
          <span class="sidebar-label" id="reel-like-count"><?php echo esc_html( $like_count ); ?></span>
        </div>

        <!-- Bookmark / Save -->
        <div class="sidebar-btn" id="sidebar-save-btn" onclick="toggleBookmark(this)">
          <div class="sidebar-icon"><svg id="sidebar-save-icon" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M6 0h12a3 3 0 0 1 3 3v19.86a.72.72 0 0 1-1.14.66L12 18.78l-7.86 4.74A.72.72 0 0 1 3 22.86V3a3 3 0 0 1 3-3zm6 3.84l1.74 3.84 4.08.72-2.94 2.88.66 4.32L12 13.68l-3.54 1.92.66-4.32-2.94-2.88 4.08-.72z"/></svg></div>
          <span class="sidebar-label" id="sidebar-save-label">Save</span>
        </div>

        <!-- Comments -->
        <div class="sidebar-btn" onclick="showComments(event)">
          <div class="sidebar-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 6.5C21 5.12 19.88 4 18.5 4h-13C4.12 4 3 5.12 3 6.5v8C3 15.88 4.12 17 5.5 17H7l-2 4 5.33-4H18.5c1.38 0 2.5-1.12 2.5-2.5v-8z"/></svg></div>
          <span class="sidebar-label" id="sidebar-comments-count">0</span>
        </div>

        <!-- Episodes -->
        <div class="sidebar-btn" onclick="showDetails(event, 'episodes')">
          <div class="sidebar-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg></div>
          <span class="sidebar-label">Episodes</span>
        </div>

        <!-- Share -->
        <div class="sidebar-btn" onclick="if(navigator.share){navigator.share({title:document.title,url:location.href});}">
          <div class="sidebar-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg></div>
          <span class="sidebar-label">Share</span>
        </div>
      </div>

      <!-- (Subtitle text overlay removed so video screen remains clean) -->
    </div>

    <div class="player-bottom">
      <div class="show-info">
        <div class="show-title-wrap">
          <div class="show-title" onclick="showDetails(event, 'synopsis')">
            <?php echo esc_html( wp_trim_words( $title, 5, '...' ) ); ?>
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </div>
          <div class="show-desc" onclick="showDetails(event, 'synopsis')" style="cursor:pointer"><?php echo esc_html( wp_trim_words( $overview, 12, '...' ) ); ?></div>
        </div>
        
      </div>
      <div class="progress-wrap" id="progress-wrap">
        <div class="progress-track">
          <div class="progress-fill" id="progress-fill">
            <div class="progress-thumb"></div>
          </div>
        </div>
      </div>
      <!-- Bottom Video Player Controls: Speed, Volume, Quality, Fullscreen -->
      <div class="player-ctrls-row" id="player-ctrls-row">
        <!-- Speed -->
        <button type="button" class="player-ctrl-btn" onclick="cycleSpeed()" title="Playback Speed">
          <span id="speed-val-bottom">1.0x</span>
        </button>

        <!-- Volume Dropdown & Vertical Slider Popup -->
        <div class="volume-dropdown-wrap" id="volume-dropdown-wrap">
          <button type="button" class="player-ctrl-btn" id="volume-btn-toggle" onclick="toggleVolumeMenu(event)" title="Volume (M to Mute)">
            <svg id="icon-volume-high" viewBox="0 0 24 24" fill="currentColor" style="width:15px;height:15px;"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/></svg>
            <svg id="icon-volume-muted" viewBox="0 0 24 24" fill="#ff4d4f" style="width:15px;height:15px;display:none;"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/></svg>
          </button>
          
          <div class="volume-popup-menu" id="volume-popup-menu">
            <div class="volume-popup-header">
              <span class="volume-popup-val" id="volume-popup-val">100%</span>
            </div>
            <div class="volume-vertical-track-wrap">
              <input type="range" id="volume-slider-range" min="0" max="1" step="0.02" value="1" orient="vertical" oninput="setPlayerVolume(this.value)" class="volume-vertical-range" title="Volume">
            </div>
          </div>
        </div>

        <!-- Quality Dropdown Menu -->
        <div class="quality-dropdown-wrap" id="quality-dropdown-wrap">
          <button type="button" class="player-ctrl-btn" id="quality-btn-toggle" onclick="toggleQualityMenu(event)" title="Video Quality">
            <span id="quality-val-bottom">Auto</span>
            <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px;opacity:0.8;"><path d="M7 14l5-5 5 5z"/></svg>
          </button>
          <div class="quality-menu" id="quality-menu">
            <!-- Dynamically populated -->
          </div>
        </div>

        <!-- Fullscreen -->
        <button type="button" class="player-ctrl-btn" onclick="togglePlayerFullscreen()" title="Fullscreen">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- ── BOTTOM SHEET BACKDROP ── -->
  <div id="sheet-backdrop" onclick="closeAllSheets()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:50;transition:opacity 0.3s;"></div>

  <!-- ── EPISODES BOTTOM SHEET ── -->
  <div id="episodes-sheet" style="
    position:fixed; left:0; right:0; bottom:0; z-index:51;
    background:#1a1a1a; border-radius:20px 20px 0 0;
    transform:translateY(100%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);
    max-height:72vh; display:flex; flex-direction:column;
  ">
    <!-- Drag handle -->
    <div style="display:flex;align-items:center;justify-content:center;padding:10px 0 0;">
      <div style="width:36px;height:4px;background:#444;border-radius:4px;"></div>
    </div>

    <!-- Show info header -->
    <div style="padding:14px 16px 0;display:flex;align-items:flex-start;gap:12px;">
      <?php if ($poster): ?>
      <img src="<?php echo esc_url($poster); ?>" style="width:54px;height:72px;object-fit:cover;border-radius:8px;flex-shrink:0;" />
      <?php endif; ?>
      <div style="flex:1;min-width:0;">
        <div style="color:#fff;font-size:16px;font-weight:700;line-height:1.3;margin-bottom:4px;"><?php echo esc_html($title); ?></div>
        <div style="color:#aaa;font-size:12px;margin-bottom:4px;"><span id="mobile-view-count"><?php echo esc_html( $view_count_fmt ); ?></span> Views</div>
        <div style="color:#aaa;font-size:12px;display:flex;align-items:center;gap:4px;">
          <span style="color:#ffc107;">★</span>
          <span id="mobile-rating-val" style="color:#fff;font-weight:600;"><?php echo esc_html( $display_rating ); ?></span>
          <span id="mobile-rating-count" style="color:#888;font-size:11px;margin-left:2px;">(<?php echo $raw_rating_count; ?>)</span>
        </div>
      </div>
      <button onclick="closeAllSheets()" style="background:none;border:none;color:#aaa;cursor:pointer;padding:4px;flex-shrink:0;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>

    <!-- Tabs -->
    <div style="display:flex;gap:24px;padding:12px 16px 0;border-bottom:1px solid #333;">
      <div id="ep-synopsis-tab" onclick="switchSheetTab('synopsis')" style="padding:8px 0;font-size:14px;color:#777;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;">Synopsis</div>
      <div id="ep-episodes-tab" onclick="switchSheetTab('episodes')" style="padding:8px 0;font-size:14px;font-weight:700;color:#fff;cursor:pointer;border-bottom:2px solid #fff;margin-bottom:-1px;">Episodes</div>
    </div>

    <!-- Synopsis content -->
    <div id="ep-synopsis-content" style="display:none;padding:14px 16px;overflow-y:auto;-webkit-overflow-scrolling:touch;">
      <p style="color:#aaa;font-size:14px;line-height:1.7;margin:0;"><?php echo esc_html($overview); ?></p>
    </div>

    <!-- Episodes content -->
    <div id="ep-episodes-content" style="display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden;">
      <!-- Range selector -->
      <?php
        $ep_ranges = [];
        $batch = 30;
        $total = count($episodes_list);
        for ($r = 0; $r < $total; $r += $batch) {
          $from = (int)($episodes_list[$r]['episode_number'] ?? $r + 1);
          $to_idx = min($r + $batch - 1, $total - 1);
          $to = (int)($episodes_list[$to_idx]['episode_number'] ?? $to_idx + 1);
          $ep_ranges[] = ['from' => $from, 'to' => $to, 'start_idx' => $r];
        }
      ?>
      <?php if (count($ep_ranges) > 1): ?>
      <div style="display:flex;gap:8px;padding:10px 16px 6px;flex-shrink:0;overflow-x:auto;">
        <?php foreach ($ep_ranges as $ri => $range): ?>
        <button onclick="filterEpRange(<?php echo $range['from']; ?>,<?php echo $range['to']; ?>,this)"
          class="ep-range-btn <?php echo ($episode >= $range['from'] && $episode <= $range['to']) ? 'ep-range-active' : ''; ?>"
          style="padding:4px 12px;border-radius:6px;border:1px solid #444;background:#252525;color:#aaa;font-size:12px;cursor:pointer;white-space:nowrap;flex-shrink:0;">
          <?php echo $range['from']; ?>-<?php echo $range['to']; ?>
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Grid -->
      <div style="overflow-y:auto;padding:8px 16px 24px;-webkit-overflow-scrolling:touch;">
        <div id="ep-sheet-grid" style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;">
          <?php foreach ($episodes_list as $idx => $ep_item):
            $ep_num      = (int)($ep_item['episode_number'] ?? $idx + 1);
            $is_cur      = ($ep_num === (int)$episode);
            $u_type      = strtolower($ep_item['access_control']['unlock_type'] ?? 'free');
            $is_locked   = in_array($u_type, ['coins','premium','vip'], true) && empty($ep_item['access_control']['is_unlocked']);
            $is_vip_ep   = ('vip' === $u_type);
          ?>
          <a href="?episode=<?php echo $ep_num; ?>"
             class="ep-chip <?php echo $is_cur ? 'ep-chip-active' : ''; ?>"
             style="aspect-ratio:1;border-radius:10px;background:#252525;<?php echo $is_cur ? 'border:1.5px solid #7c4dff;color:#fff;' : 'color:#aaa;'; ?> font-size:13px;font-weight:500;display:flex;flex-direction:column;align-items:center;justify-content:center;text-decoration:none;position:relative;transition:transform 0.1s;"
             data-ep="<?php echo $ep_num; ?>">
            <?php echo $ep_num; ?>
            <?php if ($is_cur): ?>
            <div class="ep-chip-equalizer" style="position:absolute;bottom:4px;left:50%;transform:translateX(-50%);display:flex;gap:1.5px;align-items:flex-end;height:8px;">
              <div style="width:2px;height:4px;background:#7c4dff;border-radius:1px;"></div>
              <div style="width:2px;height:7px;background:#7c4dff;border-radius:1px;"></div>
              <div style="width:2px;height:5px;background:#7c4dff;border-radius:1px;"></div>
            </div>
            <?php endif; ?>
            <?php if ($is_locked): ?><span class="ep-chip-lock-icon" style="font-size:9px;position:absolute;top:3px;right:3px;"><?php echo $is_vip_ep ? '👑' : '🔒'; ?></span><?php endif; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ── SETTINGS BOTTOM SHEET (DramaBox / Image 2 style) ── -->
  <div id="settings-sheet" style="
    position:fixed; left:0; right:0; bottom:0; z-index:51;
    background:#1c1c1e; border-radius:20px 20px 0 0;
    transform:translateY(100%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);
    max-height:80vh; overflow-y:auto; -webkit-overflow-scrolling:touch;
  ">
    <div style="display:flex;align-items:center;justify-content:center;padding:10px 0 0;">
      <div style="width:36px;height:4px;background:#444;border-radius:4px;"></div>
    </div>
    
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px 12px;border-bottom:1px solid rgba(255,255,255,0.08);">
      <div style="color:#fff;font-size:16px;font-weight:700;">Playback Settings</div>
      <button onclick="closeAllSheets()" style="background:none;border:none;color:#aaa;cursor:pointer;padding:4px;"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>

    <!-- 1. Current Quality -->
    <div style="display:flex;align-items:center;padding:16px 20px;gap:14px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);" onclick="cycleQualityOption()">
      <div style="width:28px;display:flex;align-items:center;justify-content:center;color:#fff;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15V9m4 6V9m4 6V9"/></svg>
      </div>
      <div style="flex:1;color:#fff;font-size:15px;font-weight:500;">Current Quality</div>
      <div style="color:#aaa;font-size:13px;display:flex;align-items:center;gap:4px;">
        <span id="quality-display-label">Auto</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </div>
    </div>

    <!-- 2. Speed -->
    <div style="display:flex;align-items:center;padding:16px 20px;gap:14px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);" onclick="cycleSpeed()">
      <div style="width:28px;display:flex;align-items:center;justify-content:center;color:#fff;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div style="flex:1;color:#fff;font-size:15px;font-weight:500;">Speed</div>
      <div style="color:#aaa;font-size:13px;display:flex;align-items:center;gap:4px;">
        <span id="speed-val">1.0x</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </div>
    </div>

    <!-- 3. Subtitles -->
    <div style="display:flex;align-items:center;padding:16px 20px;gap:14px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);" onclick="cycleSubtitlesOption()">
      <div style="width:28px;display:flex;align-items:center;justify-content:center;color:#fff;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="14" y2="13"/></svg>
      </div>
      <div style="flex:1;color:#fff;font-size:15px;font-weight:500;">Subtitles</div>
      <div style="color:#aaa;font-size:13px;display:flex;align-items:center;gap:4px;">
        <span id="subtitles-display-label">Off</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </div>
    </div>

    <!-- 4. Bullet Comments (Danmaku) -->
    <div style="display:flex;align-items:center;padding:16px 20px;gap:14px;border-bottom:1px solid rgba(255,255,255,0.05);">
      <div style="width:28px;display:flex;align-items:center;justify-content:center;color:#fff;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="13" y2="12"/></svg>
      </div>
      <div style="flex:1;color:#fff;font-size:15px;font-weight:500;">Bullet Comments</div>
      <div id="bullet-toggle-switch" onclick="toggleBulletComments(this)" style="width:48px;height:26px;border-radius:13px;background:#3a3a3c;position:relative;cursor:pointer;transition:background 0.25s;">
        <div id="bullet-toggle-circle" style="width:22px;height:22px;border-radius:50%;background:#fff;position:absolute;top:2px;left:2px;transition:left 0.25s;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>
      </div>
    </div>

    <!-- 5. Picture-in-Picture -->
    <div style="display:flex;align-items:center;padding:16px 20px;gap:14px;border-bottom:1px solid rgba(255,255,255,0.05);">
      <div style="width:28px;display:flex;align-items:center;justify-content:center;color:#fff;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><rect x="12" y="10" width="8" height="8" rx="1"/></svg>
      </div>
      <div style="flex:1;color:#fff;font-size:15px;font-weight:500;">Picture-in-Picture</div>
      <div id="pip-toggle-switch" onclick="togglePipMode(this)" style="width:48px;height:26px;border-radius:13px;background:#ff2d55;position:relative;cursor:pointer;transition:background 0.25s;">
        <div id="pip-toggle-circle" style="width:22px;height:22px;border-radius:50%;background:#fff;position:absolute;top:2px;left:24px;transition:left 0.25s;box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>
      </div>
    </div>

    <!-- Footer link -->
    <div style="text-align:center;padding:20px 16px;color:#8e8e93;font-size:12px;">
      Found subtitle issues? <a href="javascript:void(0)" onclick="alert('Thank you for reporting! Our team will review this episode.');" style="color:#0a84ff;text-decoration:none;">Tap to report</a>
    </div>

    <div style="padding-bottom:env(safe-area-inset-bottom,20px);"></div>
  </div>

  <!-- ── COMMENTS BOTTOM SHEET ── -->
  <div id="comments-sheet" style="
    position:fixed; left:0; right:0; bottom:0; z-index:52;
    background:#1c1c1e; border-radius:20px 20px 0 0;
    transform:translateY(100%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);
    max-height:75vh; display:flex; flex-direction:column;
  ">
    <div style="display:flex;align-items:center;justify-content:center;padding:10px 0 0;">
      <div style="width:36px;height:4px;background:#444;border-radius:4px;"></div>
    </div>
    
    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid rgba(255,255,255,0.08);">
      <div style="color:#fff;font-size:16px;font-weight:700;display:flex;align-items:center;gap:6px;">
        <span>Comments</span>
        <span id="comments-sheet-count" style="color:#888;font-size:13px;font-weight:400;">(0)</span>
      </div>
      <button onclick="closeAllSheets()" style="background:none;border:none;color:#aaa;cursor:pointer;padding:4px;"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>

    <!-- Comments List Area -->
    <div id="comments-list-container" style="flex:1;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:14px;-webkit-overflow-scrolling:touch;">
      <div id="comments-empty-state" style="text-align:center;padding:30px 10px;color:#888;font-size:13px;">
        No comments yet on this drama. Be the first to leave a comment!
      </div>
    </div>

    <!-- Comment Input Bar with Reply Banner -->
    <div style="padding:10px 16px;border-top:none;background:#161618;display:flex;flex-direction:column;gap:6px;">
      <!-- Active Reply Banner -->
      <div id="comment-reply-banner" style="display:none;align-items:center;justify-content:space-between;background:rgba(255,45,85,0.12);border:1px solid rgba(255,45,85,0.3);border-radius:8px;padding:5px 12px;font-size:12px;color:#fb7185;">
        <span>Replying to <strong id="comment-reply-target-name">@User</strong></span>
        <button type="button" onclick="cancelReplyTarget()" style="background:none;border:none;color:#aaa;cursor:pointer;font-size:14px;padding:0 4px;line-height:1;" title="Cancel Reply">✕</button>
      </div>

      <div style="display:flex;align-items:center;gap:10px;">
        <div id="comment-input-avatar-wrap" style="width:34px;height:34px;border-radius:50%;overflow:hidden;flex-shrink:0;background:rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:center;">
          <img id="comment-user-avatar-img" src="" style="width:100%;height:100%;object-fit:cover;display:none;" />
          <svg id="comment-user-avatar-placeholder" width="18" height="18" viewBox="0 0 24 24" fill="#999"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
        </div>
        <input type="text" id="comment-input-field" placeholder="Add a comment..." style="flex:1;height:38px;background:#242426;border:none;border-radius:19px;padding:0 14px;color:#fff;font-size:13px;outline:none;" onkeydown="if(event.key==='Enter') submitComment()" />
        <button onclick="submitComment()" style="background:linear-gradient(135deg, #ff2d55, #e11d48);border:none;border-radius:19px;height:38px;padding:0 18px;color:#fff;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 10px rgba(255,45,85,0.35);">Post</button>
      </div>
    </div>
    <div style="padding-bottom:env(safe-area-inset-bottom,12px);"></div>
  </div>

  <!-- ── AUTH / SIGN IN MODAL ── -->
  <div id="auth-modal" style="
    position:fixed; left:0; right:0; bottom:0; z-index:55;
    background:#1c1c1e; border-radius:20px 20px 0 0;
    transform:translateY(100%); transition:transform 0.35s cubic-bezier(0.4,0,0.2,1);
    max-height:85vh; overflow-y:auto; padding:20px; box-sizing:border-box;
  ">
    <div class="modal-drag-handle" style="display:flex;align-items:center;justify-content:center;padding:0 0 10px;">
      <div style="width:36px;height:4px;background:#444;border-radius:4px;"></div>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <div id="auth-modal-title" style="color:#fff;font-size:18px;font-weight:800;">Sign In / Sign Up</div>
      <button onclick="closeAllSheets()" style="background:none;border:none;color:#aaa;cursor:pointer;padding:4px;"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg></button>
    </div>

    <!-- Auth message / error feedback -->
    <div id="auth-feedback-box" style="display:none;background:rgba(255,45,85,0.15);border:1px solid #ff2d55;color:#ff6b81;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;"></div>

    <div style="display:flex;gap:12px;margin-bottom:18px;border-bottom:1px solid rgba(255,255,255,0.1);">
      <button type="button" id="auth-tab-login" onclick="switchAuthMode('login')" style="flex:1;background:none;border:none;padding:10px;color:#fff;font-weight:700;font-size:14px;border-bottom:2px solid #ff2d55;cursor:pointer;">Log In</button>
      <button type="button" id="auth-tab-signup" onclick="switchAuthMode('signup')" style="flex:1;background:none;border:none;padding:10px;color:#888;font-weight:500;font-size:14px;border-bottom:2px solid transparent;cursor:pointer;">Sign Up</button>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px;">
      <div>
        <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Email</label>
        <input type="email" id="auth-input-email" placeholder="name@example.com" style="width:100%;height:42px;background:#2c2c2e;border:1px solid #3a3a3c;border-radius:8px;padding:0 12px;color:#fff;font-size:14px;box-sizing:border-box;outline:none;" />
      </div>

      <div>
        <label style="color:#aaa;font-size:12px;display:block;margin-bottom:4px;">Password</label>
        <input type="password" id="auth-input-password" placeholder="Password (min 6 characters)" style="width:100%;height:42px;background:#2c2c2e;border:1px solid #3a3a3c;border-radius:8px;padding:0 12px;color:#fff;font-size:14px;box-sizing:border-box;outline:none;" />
      </div>

      <button type="button" id="auth-submit-btn" onclick="submitAuthForm()" style="width:100%;height:44px;background:#ff2d55;border:none;border-radius:8px;color:#fff;font-size:15px;font-weight:700;margin-top:8px;cursor:pointer;">Log In</button>
    </div>

    <div style="text-align:center;margin-top:16px;">
      <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" style="color:#888;font-size:12px;text-decoration:none;">Go to full auth page</a>
    </div>
    <div style="padding-bottom:env(safe-area-inset-bottom,20px);"></div>
  </div>

  </div><!-- /.watch-left-col -->

  <!-- RIGHT COLUMN: DESKTOP DETAILS, EPISODES & INTERACTIONS -->
  <div class="watch-right-col" id="shorttv-desktop-col">
    <!-- Breadcrumbs -->
    <div style="display:flex;align-items:center;margin-bottom:8px;">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="dt-back-crumb" title="Back to Home">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
        <span>Back to Home</span>
      </a>
      <span class="dt-crumb-sep">/</span>
      <span id="reel-crumb-ep" class="dt-crumb-ep">Episode <?php echo (int) $episode; ?></span>
    </div>

    <!-- Series Main Title -->
    <h1 id="reel-watch-main-title" class="dt-series-title">Episode <?php echo (int) $episode; ?> - <?php echo esc_html( $title ); ?></h1>

    <!-- Metadata Row -->
    <div class="dt-meta-row">
      <div class="dt-meta-pill" title="Average Rating and Total Ratings">
        <span style="color:#ffc107;">★</span>
        <span id="desktop-rating-val"><?php echo esc_html( $display_rating ); ?></span>
        <span id="desktop-rating-count" style="color:#888;font-size:11px;margin-left:3px;">(<?php echo $raw_rating_count; ?>)</span>
      </div>
      <div class="dt-meta-pill">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
        <span id="desktop-view-count"><?php echo esc_html( $view_count_fmt ); ?></span> Views
      </div>
      <div class="dt-meta-pill">
        <span id="reel-ep-range-label"><?php echo count($episodes_list); ?> Episodes</span>
      </div>
      <?php foreach ( (array) $genres as $g ) : ?>
        <span class="dt-genre-tag"><?php echo esc_html( $g ); ?></span>
      <?php endforeach; ?>
    </div>

    <!-- Action Buttons -->
    <div class="dt-actions-row">
      <button type="button" id="reel-btn-like" class="dt-action-btn" onclick="toggleLike(this)" title="Like this drama">
        <svg id="desktop-like-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        <span id="reel-like-count"><?php echo esc_html( $like_count ); ?></span>
      </button>

      <button type="button" id="reel-btn-bookmark" class="dt-action-btn" onclick="toggleBookmark(this)" title="Save to My List">
        <svg id="desktop-bookmark-icon" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M6 0h12a3 3 0 0 1 3 3v19.86a.72.72 0 0 1-1.14.66L12 18.78l-7.86 4.74A.72.72 0 0 1 3 22.86V3a3 3 0 0 1 3-3zm6 3.84l1.74 3.84 4.08.72-2.94 2.88.66 4.32L12 13.68l-3.54 1.92.66-4.32-2.94-2.88 4.08-.72z"/></svg>
        <span id="desktop-bookmark-label">Save to List</span>
      </button>

      <button type="button" class="dt-action-btn" onclick="if(navigator.share){navigator.share({title:document.title,url:location.href});}else{navigator.clipboard.writeText(location.href);alert('Link copied to clipboard!');}" title="Share drama">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
        <span>Share</span>
      </button>

      <a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="dt-vip-cta">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        <span>VIP Pass</span>
      </a>
    </div>

    <!-- Synopsis Card -->
    <div class="dt-plot-card">
      <div class="dt-plot-head" id="reel-plot-heading">Synopsis</div>
      <p class="dt-plot-body" id="reel-plot-text"><?php echo esc_html( $ep_synopsis ); ?></p>
      <button type="button" class="dt-plot-more" id="reel-plot-more-btn" onclick="togglePlotExpanded()">More</button>
    </div>

    <!-- Rating Widget -->
    <div id="rating-widget" class="dt-rating-widget">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <div style="color:#fff;font-size:13px;font-weight:600;">Rate this drama</div>
        <span id="rating-total-count" style="color:#888;font-size:12px;">(<?php echo $raw_rating_count . ( $raw_rating_count === 1 ? ' rating' : ' ratings' ); ?>)</span>
      </div>
      <div style="display:flex;align-items:center;gap:16px;">
        <div id="star-rating-row" style="display:flex;gap:6px;">
          <?php for ($s = 1; $s <= 5; $s++): ?>
          <span class="rating-star-btn" data-star="<?php echo $s; ?>"
            onmouseenter="highlightStars(<?php echo $s; ?>)"
            onmouseleave="resetStarHighlight()"
            onclick="submitRating(<?php echo $s; ?>)"
            style="font-size:26px;cursor:pointer;color:#555;transition:color 0.15s,transform 0.15s;display:inline-block;">★</span>
          <?php endfor; ?>
        </div>
        <span id="rating-feedback-txt" style="color:#aaa;font-size:12px;min-width:80px;"></span>
      </div>
    </div>

    <!-- Tabs Navigation (Episodes & Comments) -->
    <div class="dt-tabs-bar">
      <button type="button" class="dt-tab active" id="dt-tab-episodes" onclick="switchDesktopTab('episodes')">
        Episodes <span class="dt-tab-count">(<?php echo count($episodes_list); ?>)</span>
      </button>
      <button type="button" class="dt-tab" id="dt-tab-comments" onclick="switchDesktopTab('comments')">
        Comments <span class="dt-tab-count" id="dt-tab-comments-count">(0)</span>
      </button>
    </div>

    <!-- TAB 1: EPISODES CONTENT -->
    <div id="dt-panel-episodes" style="display:block;">
      <?php if (count($ep_ranges) > 1): ?>
      <div style="display:flex;gap:8px;padding-bottom:14px;overflow-x:auto;">
        <?php foreach ($ep_ranges as $ri => $range): ?>
        <button onclick="filterEpRange(<?php echo $range['from']; ?>,<?php echo $range['to']; ?>,this)"
          class="ep-range-btn <?php echo ($episode >= $range['from'] && $episode <= $range['to']) ? 'ep-range-active' : ''; ?>"
          style="padding:5px 14px;border-radius:14px;border:1px solid #444;background:#242429;color:#aaa;font-size:12px;cursor:pointer;white-space:nowrap;flex-shrink:0;">
          <?php echo $range['from']; ?>-<?php echo $range['to']; ?>
        </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="desktop-episodes-grid" id="reel-episodes-grid">
        <?php foreach ($episodes_list as $idx => $ep_item):
          $ep_num      = (int)($ep_item['episode_number'] ?? $idx + 1);
          $is_cur      = ($ep_num === (int)$episode);
          $u_type      = strtolower($ep_item['access_control']['unlock_type'] ?? 'free');
          $is_locked   = in_array($u_type, ['coins','premium','vip'], true) && empty($ep_item['access_control']['is_unlocked']);
          $is_vip_ep   = ('vip' === $u_type);
        ?>
        <button type="button" class="reel-ep-btn <?php echo $is_cur ? 'is-active' : ''; ?>" data-ep="<?php echo $ep_num; ?>" title="Episode <?php echo $ep_num; ?>">
          <span><?php echo $ep_num; ?></span>
          <?php if ($is_cur): ?>
          <div class="reel-ep-equalizer"><span></span><span></span><span></span></div>
          <?php endif; ?>
          <?php if ($is_locked): ?>
          <span class="reel-ep-lock-icon"><?php echo $is_vip_ep ? '👑' : '🔒'; ?></span>
          <?php endif; ?>
        </button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TAB 2: COMMENTS CONTENT -->
    <div id="dt-panel-comments" style="display:none;flex-direction:column;flex:1;min-height:380px;">
      <!-- Active Reply Banner for Desktop -->
      <div id="desktop-reply-banner" style="display:none;align-items:center;justify-content:space-between;background:rgba(255,45,85,0.12);border:1px solid rgba(255,45,85,0.3);border-radius:10px;padding:6px 14px;margin-bottom:10px;font-size:12.5px;color:#fb7185;">
        <span>Replying to <strong id="desktop-reply-target-name">@User</strong></span>
        <button type="button" onclick="cancelReplyTarget()" style="background:none;border:none;color:#aaa;cursor:pointer;font-size:14px;padding:0 6px;line-height:1;" title="Cancel Reply">✕</button>
      </div>

      <!-- Comment Input Bar -->
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-shrink:0;">
        <input type="text" id="desktop-comment-input" placeholder="Write a comment about this drama..." style="flex:1;height:42px;background:#232328;border:none;border-radius:21px;padding:0 16px;color:#fff;font-size:13px;outline:none;" onkeydown="if(event.key==='Enter') submitComment()" />
        <button type="button" onclick="submitComment()" style="background:#ff2d55;border:none;border-radius:21px;height:42px;padding:0 20px;color:#fff;font-size:13px;font-weight:700;cursor:pointer;transition:transform 0.15s;flex-shrink:0;">Post</button>
      </div>

      <!-- Realtime Comments List -->
      <div id="desktop-comments-list" style="display:flex;flex-direction:column;gap:12px;padding-right:6px;padding-bottom:30px;">
        <div style="text-align:center;padding:30px 10px;color:#888;font-size:13px;">
          No comments yet on this drama. Be the first to leave a comment!
        </div>
      </div>
    </div>
  </div><!-- /.watch-right-col -->
</div><!-- /.watch-desktop-layout -->

<?php 
$short_ads_opt      = get_option( 'short_ad_settings', array() );
$ad_urls            = array();
if ( ! empty( $short_ads_opt['rewarded_ad_urls'] ) && is_array( $short_ads_opt['rewarded_ad_urls'] ) ) {
    $ad_urls = array_values( array_filter( array_map( 'trim', $short_ads_opt['rewarded_ad_urls'] ) ) );
}
if ( empty( $ad_urls ) ) {
    $ad_urls = array_values( array_filter( array(
        $short_ads_opt['rewarded_ad_url'] ?? '',
        $short_ads_opt['rewarded_ad_url_2'] ?? '',
        $short_ads_opt['rewarded_ad_url_3'] ?? ''
    ) ) );
}
if ( empty( $ad_urls ) ) {
    $ad_urls = array( 'https://omg10.com/4/11932682' );
}

$rewarded_video_url = ! empty( $short_ads_opt['rewarded_video_url'] ) ? esc_url_raw( trim( $short_ads_opt['rewarded_video_url'] ) ) : '';
$ad_unlock_mode     = $short_ads_opt['ad_unlock_mode'] ?? 'clicks';
$ad_req_clicks      = ! empty( $short_ads_opt['ad_required_clicks'] ) ? (int) $short_ads_opt['ad_required_clicks'] : 3;
$ad_cooldown        = ! empty( $short_ads_opt['ad_click_cooldown'] ) ? (int) $short_ads_opt['ad_click_cooldown'] : 2;
$ad_countdown_s     = ! empty( $short_ads_opt['ad_countdown_seconds'] ) ? (int) $short_ads_opt['ad_countdown_seconds'] : 15;
$ad_reward_coins    = ! empty( $short_ads_opt['ad_reward_coins'] ) ? (int) $short_ads_opt['ad_reward_coins'] : 5;
$ad_is_enabled      = ! isset( $short_ads_opt['enable_rewarded_ad'] ) || ! empty( $short_ads_opt['enable_rewarded_ad'] );

$limit_mode         = $short_ads_opt['ad_daily_limit_mode'] ?? 'auto_links';
$custom_limit       = ! empty( $short_ads_opt['ad_custom_daily_limit'] ) ? (int) $short_ads_opt['ad_custom_daily_limit'] : 5;
$total_links        = count( $ad_urls );

if ( 'auto_links' === $limit_mode ) {
    $max_daily_unlocks = max( 1, (int) floor( $total_links / max( 1, $ad_req_clicks ) ) );
} elseif ( 'custom' === $limit_mode ) {
    $max_daily_unlocks = $custom_limit;
} else {
    $max_daily_unlocks = 0; // 0 = unlimited
}
?>

<!-- Rewarded Video Ad Modal for Watch Page -->
<div class="short-reward-video-modal-overlay" id="reward-video-modal" style="display:none;">
	<div class="reward-video-backdrop"></div>
	<div class="reward-video-card">
		<!-- Top Ad Header -->
		<div class="reward-video-top-bar">
			<div class="reward-video-badge">
				<span class="ad-tag">AD</span>
				<span class="ad-title"><?php _e( 'Sponsored Video', 'short-stream' ); ?></span>
			</div>
			<div class="reward-video-timer-pill">
				<span id="reward-video-countdown-text">Unlock in <strong id="reward-video-countdown-num"><?php echo $ad_countdown_s; ?></strong>s</span>
			</div>
			<button type="button" class="reward-video-close-btn" id="btn-close-reward-video" title="<?php esc_attr_e( 'Close Ad', 'short-stream' ); ?>">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
			</button>
		</div>

		<!-- Video Container -->
		<div class="reward-video-player-wrap">
			<video id="reward-ad-video-element" playsinline webkit-playsinline muted autoplay preload="auto"></video>
			<button type="button" class="reward-video-sound-float-btn" id="btn-toggle-reward-sound" title="<?php esc_attr_e( 'Toggle Sound', 'short-stream' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
			</button>
			<div class="reward-video-spinner" id="reward-video-spinner" style="display:none;">
				<div class="reward-spinner-ring"></div>
			</div>
		</div>

		<!-- Bottom Progress Track & Notice -->
		<div class="reward-video-bottom-bar">
			<div class="reward-video-progress-track">
				<div class="reward-video-progress-fill" id="reward-video-progress-fill" style="width:0%;"></div>
			</div>
			<div class="reward-video-footer-info">
				<span class="reward-coins-tag"><span>🔓 Unlocks Episode upon completion</span></span>
				<span class="reward-unskippable-notice"><?php _e( 'Do not close until timer finishes to unlock episode', 'short-stream' ); ?></span>
			</div>
		</div>

		<!-- Completion Overlay -->
		<div class="reward-video-completed-overlay" id="reward-video-completed-overlay" style="display:none;">
			<div class="completed-content">
				<div class="completed-icon">
					<svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
				</div>
				<h4 class="completed-title"><?php _e( 'Episode Unlocked!', 'short-stream' ); ?></h4>
				<div class="completed-reward-amount"><span>Enjoy Watching Now</span></div>
			</div>
		</div>
	</div>
</div>

<!-- Custom Exit Video Ad Confirmation Modal -->
<div class="short-reward-modal-overlay" id="reward-exit-confirm-modal" style="display:none; z-index: 100000000;">
	<div class="reward-modal-backdrop" id="reward-exit-backdrop"></div>
	<div class="reward-modal-card reward-confirm-card" style="background:#18181c; border-radius:18px; padding:24px 20px; max-width:340px; width:90%; text-align:center; color:#fff; border:1px solid rgba(255,255,255,0.1); box-shadow:0 20px 40px rgba(0,0,0,0.7); margin:auto;">
		<div class="reward-confirm-icon" style="margin-bottom:12px;">
			<svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
		</div>
		<h4 class="reward-confirm-title" style="font-size:17px; font-weight:700; margin:0 0 8px 0;"><?php _e( 'Video Ad is not finished!', 'short-stream' ); ?></h4>
		<p class="reward-confirm-desc" style="font-size:13px; color:#aaa; line-height:1.45; margin:0 0 20px 0;"><?php _e( 'If you leave now, the episode will remain locked.<br>Are you sure you want to exit without unlocking?', 'short-stream' ); ?></p>
		<div class="reward-confirm-actions" style="display:flex; flex-direction:column; gap:10px;">
			<button type="button" class="reward-confirm-btn-continue" id="btn-ad-continue-watching" style="background:linear-gradient(135deg, #ff2d55, #ff5500); color:#fff; border:none; padding:12px; border-radius:12px; font-size:14px; font-weight:600; cursor:pointer;">
				<?php _e( 'Continue Watching', 'short-stream' ); ?>
			</button>
			<button type="button" class="reward-confirm-btn-exit" id="btn-ad-confirm-exit" style="background:transparent; color:#888; border:1px solid rgba(255,255,255,0.1); padding:10px; border-radius:12px; font-size:13px; font-weight:500; cursor:pointer;">
				<?php _e( 'Exit Without Unlocking', 'short-stream' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Custom VIP Quality Upgrade Modal -->
<div class="watch-vip-modal-overlay" id="watch-vip-quality-modal" style="display:none;">
	<div class="watch-vip-modal-backdrop" id="watch-vip-backdrop" onclick="closeVipQualityModal()"></div>
	<div class="watch-vip-modal-card">
		<div class="watch-vip-modal-header">
			<div class="watch-vip-modal-icon">
				<svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
					<path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"></path>
				</svg>
			</div>
			<span class="watch-vip-pill-badge"><?php _e( 'VIP EXCLUSIVE', 'short-stream' ); ?></span>
		</div>
		<h3 class="watch-vip-modal-title"><span id="vip-modal-quality-label">720p HD</span> <?php _e( 'Streaming', 'short-stream' ); ?></h3>
		<p class="watch-vip-modal-desc">
			<?php _e( 'High-definition playback is an exclusive VIP feature. Upgrade now for crystal-clear HD streaming and unlimited access to all dramas!', 'short-stream' ); ?>
		</p>
		<div class="watch-vip-modal-perks">
			<div class="watch-vip-perk-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
				<span><?php _e( 'Ultra HD (1080p & 720p) Video', 'short-stream' ); ?></span>
			</div>
			<div class="watch-vip-perk-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
				<span><?php _e( 'Instant Unlock on All Episodes', 'short-stream' ); ?></span>
			</div>
			<div class="watch-vip-perk-item">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
				<span><?php _e( '100% Ad-Free Video Streaming', 'short-stream' ); ?></span>
			</div>
		</div>
		<div class="watch-vip-modal-actions">
			<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="watch-vip-btn-upgrade">
				<span><?php _e( 'Upgrade to VIP Pass', 'short-stream' ); ?></span>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
			</a>
			<button type="button" class="watch-vip-btn-cancel" onclick="closeVipQualityModal()">
				<?php _e( 'Stay in Standard Quality', 'short-stream' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Generic Custom Confirm Modal for Watch Page -->
<div class="watch-confirm-modal-overlay" id="watch-custom-confirm-modal" style="display:none;">
	<div class="watch-confirm-modal-backdrop" id="watch-confirm-backdrop"></div>
	<div class="watch-confirm-modal-card">
		<div class="watch-confirm-icon" id="watch-confirm-icon-box">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
		</div>
		<h4 class="watch-confirm-title" id="watch-confirm-title"><?php _e( 'Confirm Action', 'short-stream' ); ?></h4>
		<p class="watch-confirm-msg" id="watch-confirm-msg"><?php _e( 'Are you sure you want to proceed?', 'short-stream' ); ?></p>
		<div class="watch-confirm-actions">
			<button type="button" class="watch-confirm-btn-ok" id="watch-confirm-btn-ok"><?php _e( 'Confirm', 'short-stream' ); ?></button>
			<button type="button" class="watch-confirm-btn-cancel" id="watch-confirm-btn-cancel"><?php _e( 'Cancel', 'short-stream' ); ?></button>
		</div>
	</div>
</div>

<script>
  window.SHORT_AD_CONFIG = {
    enabled: <?php echo $ad_is_enabled ? 'true' : 'false'; ?>,
    mode: <?php echo wp_json_encode( $ad_unlock_mode ); ?>,
    limit_mode: <?php echo wp_json_encode( $limit_mode ); ?>,
    required_clicks: <?php echo (int) $ad_req_clicks; ?>,
    rewarded_urls: <?php echo wp_json_encode( $ad_urls ); ?>,
    video_url: <?php echo wp_json_encode( $rewarded_video_url ); ?>,
    total_links_count: <?php echo (int) $total_links; ?>,
    max_daily_unlocks: <?php echo (int) $max_daily_unlocks; ?>,
    cooldown: <?php echo (int) $ad_cooldown; ?>,
    countdown: <?php echo (int) $ad_countdown_s; ?>,
    reward_coins: <?php echo (int) $ad_reward_coins; ?>,
    server_date: <?php echo wp_json_encode( gmdate( 'Y-m-d' ) ); ?>,
    server_timestamp: <?php echo (int) ( time() * 1000 ); ?>
  };

  // ─── Bottom Sheet helpers ───────────────────────────────
  function openSheet(id) {
    var rightCol = document.getElementById('shorttv-desktop-col');
    var isDesktop = window.innerWidth >= 900 || (rightCol && window.getComputedStyle(rightCol).display !== 'none');
    if (isDesktop && (id === 'comments-sheet' || id === 'episodes-sheet')) {
      if (id === 'comments-sheet') switchDesktopTab('comments');
      if (id === 'episodes-sheet') switchDesktopTab('episodes');
      return;
    }
    var backdrop = document.getElementById('sheet-backdrop');
    var sheet = document.getElementById(id);
    if (backdrop) backdrop.style.display = 'block';
    if (sheet) {
      sheet.classList.add('is-open');
      if (window.innerWidth < 900) {
        sheet.style.transform = 'translateY(0)';
      } else {
        sheet.style.transform = '';
      }
    }
  }

  function closeAllSheets() {
    var backdrop = document.getElementById('sheet-backdrop');
    if (backdrop) backdrop.style.display = 'none';
    ['episodes-sheet', 'settings-sheet', 'comments-sheet', 'auth-modal'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.classList.remove('is-open');
        if (window.innerWidth < 900) {
          el.style.transform = 'translateY(100%)';
        } else {
          el.style.transform = '';
        }
      }
    });
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeAllSheets();
    }
  });

  function showDetails(e, tab) {
    if (e) e.stopPropagation();
    var rightCol = document.getElementById('shorttv-desktop-col');
    if (window.innerWidth >= 900 || (rightCol && window.getComputedStyle(rightCol).display !== 'none')) {
      switchDesktopTab('episodes');
      if (rightCol) rightCol.scrollTo({ top: 300, behavior: 'smooth' });
      return;
    }
    openSheet('episodes-sheet');
    switchSheetTab(tab || 'episodes');
  }

  function showSettings(e) {
    if (e) e.stopPropagation();
    if (typeof updateDynamicQualities === 'function') updateDynamicQualities();
    if (typeof updateDynamicSubtitles === 'function') updateDynamicSubtitles();
    openSheet('settings-sheet');
  }

  function showComments(e) {
    if (e) e.stopPropagation();
    var rightCol = document.getElementById('shorttv-desktop-col');
    if (window.innerWidth >= 900 || (rightCol && window.getComputedStyle(rightCol).display !== 'none')) {
      switchDesktopTab('comments');
      if (rightCol) rightCol.scrollTo({ top: 400, behavior: 'smooth' });
      if (typeof fetchRealtimeComments === 'function') fetchRealtimeComments();
      return;
    }
    openSheet('comments-sheet');
    if (typeof fetchRealtimeComments === 'function') fetchRealtimeComments();
  }

  function switchDesktopTab(tab) {
    var epTab = document.getElementById('dt-tab-episodes');
    var comTab = document.getElementById('dt-tab-comments');
    var epPanel = document.getElementById('dt-panel-episodes');
    var comPanel = document.getElementById('dt-panel-comments');

    if (tab === 'comments') {
      if (epTab) epTab.classList.remove('active');
      if (comTab) comTab.classList.add('active');
      if (epPanel) epPanel.style.display = 'none';
      if (comPanel) comPanel.style.display = 'flex';
      if (typeof fetchRealtimeComments === 'function') fetchRealtimeComments();
    } else {
      if (comTab) comTab.classList.remove('active');
      if (epTab) epTab.classList.add('active');
      if (comPanel) comPanel.style.display = 'none';
      if (epPanel) epPanel.style.display = 'block';
    }
  }

  function checkSynopsisOverflow() {
    var text = document.getElementById('reel-plot-text');
    var btn = document.getElementById('reel-plot-more-btn');
    if (!text || !btn) return;
    if (text.classList.contains('is-expanded')) {
      btn.style.display = 'inline-block';
      btn.textContent = 'Less';
      return;
    }
    // Check if the scrollable content exceeds the client visible height
    if (text.scrollHeight > text.clientHeight + 4) {
      btn.style.display = 'inline-block';
      btn.textContent = 'More';
    } else {
      btn.style.display = 'none';
    }
  }

  function togglePlotExpanded() {
    var text = document.getElementById('reel-plot-text');
    var btn = document.getElementById('reel-plot-more-btn');
    if (text) {
      text.classList.toggle('is-expanded');
      checkSynopsisOverflow();
    }
  }

  function showAuthModal(e) {
    if (e) e.stopPropagation();
    openSheet('auth-modal');
  }

  var authCurrentMode = 'login';
  function switchAuthMode(mode) {
    authCurrentMode = mode;
    var loginTab = document.getElementById('auth-tab-login');
    var signupTab = document.getElementById('auth-tab-signup');
    var submitBtn = document.getElementById('auth-submit-btn');
    var modalTitle = document.getElementById('auth-modal-title');
    var feedback = document.getElementById('auth-feedback-box');
    if (feedback) feedback.style.display = 'none';

    if (mode === 'signup') {
      if (loginTab) { loginTab.style.color = '#888'; loginTab.style.fontWeight = '500'; loginTab.style.borderBottomColor = 'transparent'; }
      if (signupTab) { signupTab.style.color = '#fff'; signupTab.style.fontWeight = '700'; signupTab.style.borderBottomColor = '#ff2d55'; }
      if (submitBtn) submitBtn.textContent = 'Create Account';
      if (modalTitle) modalTitle.textContent = 'Create Account';
    } else {
      if (signupTab) { signupTab.style.color = '#888'; signupTab.style.fontWeight = '500'; signupTab.style.borderBottomColor = 'transparent'; }
      if (loginTab) { loginTab.style.color = '#fff'; loginTab.style.fontWeight = '700'; loginTab.style.borderBottomColor = '#ff2d55'; }
      if (submitBtn) submitBtn.textContent = 'Log In';
      if (modalTitle) modalTitle.textContent = 'Sign In';
    }
  }

  function handleUserAuthClick(e) {
    if (e) e.stopPropagation();
    if (window.firebaseUser) {
      showWatchCustomConfirm({
        title: 'Sign Out',
        message: 'Signed in as ' + (window.firebaseUser.email || 'User') + '. Do you want to log out?',
        confirmText: 'Log Out',
        cancelText: 'Cancel'
      }).then(function(confirmed) {
        if (confirmed) {
          if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
            firebase.auth().signOut().then(function() {
              window.location.reload();
            });
          }
        }
      });
    } else {
      showAuthModal(e);
    }
  }

  function switchSheetTab(t) {
    var synTab = document.getElementById('ep-synopsis-tab');
    var epTab  = document.getElementById('ep-episodes-tab');
    var synCon = document.getElementById('ep-synopsis-content');
    var epCon  = document.getElementById('ep-episodes-content');
    if (!synTab) return;

    if (t === 'synopsis') {
      synTab.style.color = '#fff'; synTab.style.fontWeight = '700'; synTab.style.borderBottomColor = '#fff';
      epTab.style.color  = '#777'; epTab.style.fontWeight  = '500'; epTab.style.borderBottomColor  = 'transparent';
      if (synCon) synCon.style.display = 'block';
      if (epCon) epCon.style.display  = 'none';
    } else {
      epTab.style.color  = '#fff'; epTab.style.fontWeight  = '700'; epTab.style.borderBottomColor  = '#fff';
      synTab.style.color = '#777'; synTab.style.fontWeight = '500'; synTab.style.borderBottomColor = 'transparent';
      if (epCon) epCon.style.display  = 'flex';
      if (synCon) synCon.style.display = 'none';
    }
  }

  function filterEpRange(from, to, btn) {
    document.querySelectorAll('.ep-range-btn').forEach(function(b) {
      b.style.color = '#aaa'; b.style.borderColor = '#444';
    });
    if (btn) { btn.style.color = '#fff'; btn.style.borderColor = '#fff'; }

    document.querySelectorAll('#ep-sheet-grid a[data-ep]').forEach(function(a) {
      var ep = parseInt(a.getAttribute('data-ep'), 10);
      a.style.display = (ep >= from && ep <= to) ? 'flex' : 'none';
    });
  }

  // ─── UI toggle (hide/show overlays on video background tap) ──
  var uiVisible = true;
  function toggleUI(e) {
    if (e) {
      if (
        e.target.closest('.player-top') ||
        e.target.closest('.player-sidebar') ||
        e.target.closest('.player-bottom') ||
        e.target.closest('.player-ctrls-row') ||
        e.target.closest('.progress-wrap') ||
        e.target.closest('.volume-dropdown-wrap') ||
        e.target.closest('.quality-dropdown-wrap') ||
        e.target.closest('#center-play-pause-btn') ||
        e.target.closest('#shorttv-paywall-modal') ||
        e.target.closest('#player-unmute-pill') ||
        e.target.closest('.ep-sheet-backdrop') ||
        e.target.closest('.modal-backdrop') ||
        e.target.closest('#settings-sheet') ||
        e.target.closest('#episodes-sheet') ||
        e.target.closest('#comments-sheet') ||
        e.target.closest('#auth-modal')
      ) {
        return;
      }
    }

    var paywall = document.getElementById('shorttv-paywall-modal');
    if (paywall && (paywall.style.display === 'flex' || paywall.classList.contains('is-active'))) {
      return;
    }

    // Toggle overlay visibility for controls and center play button
    uiVisible = !uiVisible;
    var els = document.querySelectorAll('.player-top, .player-sidebar, .subtitle, .player-bottom, #center-play-pause-btn');
    els.forEach(function(el) {
      el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
      el.style.opacity = uiVisible ? '1' : '0';
      el.style.pointerEvents = uiVisible ? 'auto' : 'none';
    });
  }

  // ─── Center play/pause & State synchronization ─────────
  function updateCenterPlayIcon(isPaused) {
    var centerBtn = document.getElementById('center-play-pause-btn');
    var icon = document.getElementById('center-play-icon');
    if (!centerBtn) return;

    var paywall = document.getElementById('shorttv-paywall-modal');
    if (paywall && (paywall.style.display === 'flex' || paywall.classList.contains('is-active'))) {
      centerBtn.style.display = 'none';
      return;
    }

    centerBtn.style.display = 'flex';
    if (icon) {
      if (isPaused) {
        // Show Play icon (triangle) with slight optical offset
        icon.innerHTML = '<path d="M8 5v14l11-7z"/>';
        icon.style.marginLeft = '3px';
      } else {
        // Show Pause icon (two vertical bars)
        icon.innerHTML = '<path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>';
        icon.style.marginLeft = '0px';
      }
    }
  }

  function togglePlayPause(e) {
    if (e) {
      if (typeof e.stopPropagation === 'function') e.stopPropagation();
      if (typeof e.preventDefault === 'function') e.preventDefault();
    }
    var paywall = document.getElementById('shorttv-paywall-modal');
    if (paywall && (paywall.style.display === 'flex' || paywall.classList.contains('is-active'))) {
      return;
    }
    var vid = document.getElementById('shorttv-video');
    if (!vid) return;

    if (vid.paused || vid.ended) {
      var playPromise = vid.play();
      if (playPromise !== undefined) {
        playPromise.then(function() {
          updateCenterPlayIcon(false);
        }).catch(function(err) {
          console.warn('Playback error or user gesture required:', err);
          updateCenterPlayIcon(true);
        });
      } else {
        updateCenterPlayIcon(false);
      }
    } else {
      vid.pause();
      updateCenterPlayIcon(true);
    }
  }

  function initCenterPlayBtn() {
    var vid = document.getElementById('shorttv-video');
    if (!vid) return;

    vid.addEventListener('play', function() {
      updateCenterPlayIcon(false);
    });
    vid.addEventListener('playing', function() {
      updateCenterPlayIcon(false);
    });
    vid.addEventListener('pause', function() {
      updateCenterPlayIcon(true);
    });
    vid.addEventListener('ended', function() {
      updateCenterPlayIcon(true);
    });

    // Initial icon state reflects current playback state
    updateCenterPlayIcon(vid.paused);
  }

  // ─── Playback Settings Dynamic Stream Integration ──────
  var qualityLevelsList = [
    { label: 'Auto', badge: 'Recommended', isVip: false, levelIndex: -1, display: 'Auto' },
    { label: '1080p', badge: '👑 VIP', isVip: true, levelIndex: -1, res: 1080, display: '1080p' },
    { label: '720p', badge: '👑 VIP', isVip: true, levelIndex: -1, res: 720, display: '720p' },
    { label: '480p', badge: 'SD', isVip: false, levelIndex: -1, res: 480, display: '480p' },
    { label: '360p', badge: 'Data Saver', isVip: false, levelIndex: -1, res: 360, display: '360p' }
  ];
  var qualityIdx = 0;

  function isUserVip() {
    if (window.currentShortTV && typeof window.currentShortTV.checkVip === 'function') {
      return window.currentShortTV.checkVip();
    }
    var tier = localStorage.getItem('short_sub_tier') || '';
    var validTiers = ['basic', 'standard', 'premium', 'weekly', 'monthly', 'annual', 'vip'];
    return localStorage.getItem('short_is_vip') === '1' || validTiers.indexOf(tier.toLowerCase()) !== -1;
  }

  function extractResolutionFromLevel(lvl) {
    if (!lvl) return null;
    var w = lvl.width;
    var h = lvl.height;
    if ((!w || !h) && lvl.attrs && lvl.attrs.RESOLUTION) {
      if (typeof lvl.attrs.RESOLUTION === 'string') {
        var parts = lvl.attrs.RESOLUTION.split('x');
        if (parts.length === 2) {
          w = parseInt(parts[0], 10);
          h = parseInt(parts[1], 10);
        }
      } else if (typeof lvl.attrs.RESOLUTION === 'object') {
        w = lvl.attrs.RESOLUTION.width || w;
        h = lvl.attrs.RESOLUTION.height || h;
      }
    }
    if (!w && !h && (lvl.bitrate || lvl.bandwidth)) {
      var br = lvl.bitrate || lvl.bandwidth || 0;
      if (br >= 3500000) { w = 1080; h = 1920; }
      else if (br >= 1800000) { w = 720; h = 1280; }
      else if (br >= 800000) { w = 480; h = 854; }
      else if (br >= 350000) { w = 360; h = 640; }
      else { w = 240; h = 426; }
    }
    if (!w && !h) return null;
    return (w && h) ? Math.min(w, h) : (h || w);
  }

  function updateDynamicQualities() {
    var hls = window.currentShortTV && window.currentShortTV.hls;
    var list = [];

    // Always start with Auto
    list.push({ label: 'Auto', badge: 'Recommended', isVip: false, levelIndex: -1, display: 'Auto' });

    if (hls && hls.levels && hls.levels.length > 0) {
      var parsed = [];
      hls.levels.forEach(function(lvl, i) {
        var res = extractResolutionFromLevel(lvl);
        if (res) {
          var label = res + 'p';
          var isVipRes = (res >= 720);
          var badge = isVipRes ? '👑 VIP' : (res >= 480 ? 'SD' : 'Data Saver');
          if (!parsed.some(function(item) { return item.res === res; })) {
            parsed.push({
              label: label,
              badge: badge,
              isVip: isVipRes,
              levelIndex: i,
              res: res,
              display: label
            });
          }
        }
      });

      // Sort highest quality to lowest
      parsed.sort(function(a, b) { return b.res - a.res; });
      parsed.forEach(function(item) {
        list.push(item);
      });
    }

    // If only Auto was found (e.g. static manifest or direct MP4 stream), provide standard Cloudflare Stream options
    if (list.length <= 1) {
      list = [
        { label: 'Auto', badge: 'Recommended', isVip: false, levelIndex: -1, display: 'Auto' },
        { label: '1080p', badge: '👑 VIP', isVip: true, levelIndex: -1, res: 1080, display: '1080p' },
        { label: '720p', badge: '👑 VIP', isVip: true, levelIndex: -1, res: 720, display: '720p' },
        { label: '480p', badge: 'SD', isVip: false, levelIndex: -1, res: 480, display: '480p' },
        { label: '360p', badge: 'Data Saver', isVip: false, levelIndex: -1, res: 360, display: '360p' }
      ];
    }

    qualityLevelsList = list;
    if (qualityIdx >= qualityLevelsList.length) qualityIdx = 0;

    var curItem = qualityLevelsList[qualityIdx] || qualityLevelsList[0];
    var el = document.getElementById('quality-display-label');
    if (el) el.textContent = curItem.display;
    var elBottom = document.getElementById('quality-val-bottom');
    if (elBottom) elBottom.textContent = curItem.display;

    renderQualityMenu();
  }
  window.updateDynamicQualities = updateDynamicQualities;

  function renderQualityMenu() {
    var menu = document.getElementById('quality-menu');
    if (!menu) return;
    menu.innerHTML = '<div class="quality-menu-header"><span>Video Quality</span></div>';
    
    qualityLevelsList.forEach(function(item, idx) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'quality-menu-item' + (idx === qualityIdx ? ' active' : '');
      
      var left = document.createElement('div');
      left.className = 'quality-menu-item-left';
      
      var check = document.createElement('span');
      check.className = 'quality-menu-check';
      check.textContent = (idx === qualityIdx) ? '✓' : '';
      
      var name = document.createElement('span');
      name.className = 'quality-menu-name';
      name.textContent = item.label;
      
      left.appendChild(check);
      left.appendChild(name);
      btn.appendChild(left);
      
      if (item.badge) {
        var badge = document.createElement('span');
        badge.className = 'quality-menu-badge' + (item.isVip ? ' is-vip' : '');
        badge.textContent = item.badge;
        btn.appendChild(badge);
      }
      
      btn.onclick = function(e) {
        if (e) e.stopPropagation();
        selectQuality(idx);
      };
      
      menu.appendChild(btn);
    });
  }

  function toggleQualityMenu(e) {
    if (e) {
      e.stopPropagation();
      e.preventDefault();
    }
    updateDynamicQualities();
    var menu = document.getElementById('quality-menu');
    if (!menu) return;
    renderQualityMenu();
    menu.classList.toggle('show');
  }

  function openVipQualityModal(label) {
    var modal = document.getElementById('watch-vip-quality-modal');
    var labelEl = document.getElementById('vip-modal-quality-label');
    if (labelEl) {
      labelEl.textContent = (label || '720p') + ' HD';
    }
    if (modal) {
      modal.style.display = 'flex';
      requestAnimationFrame(function() {
        modal.classList.add('is-active');
      });
    }
  }

  function closeVipQualityModal() {
    var modal = document.getElementById('watch-vip-quality-modal');
    if (modal) {
      modal.classList.remove('is-active');
      setTimeout(function() {
        modal.style.display = 'none';
      }, 200);
    }
  }

  function showWatchCustomConfirm(options) {
    return new Promise(function(resolve) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      var modal = document.getElementById('watch-custom-confirm-modal');
      if (!modal) {
        resolve(confirm(options.message || 'Are you sure?'));
        return;
      }
      var titleEl = document.getElementById('watch-confirm-title');
      var msgEl = document.getElementById('watch-confirm-msg');
      var okBtn = document.getElementById('watch-confirm-btn-ok');
      var cancelBtn = document.getElementById('watch-confirm-btn-cancel');
      var backdrop = document.getElementById('watch-confirm-backdrop');

      if (titleEl) titleEl.textContent = options.title || 'Confirm Action';
      if (msgEl) msgEl.textContent = options.message || 'Are you sure you want to proceed?';
      if (okBtn) okBtn.textContent = options.confirmText || 'Confirm';
      if (cancelBtn) cancelBtn.textContent = options.cancelText || 'Cancel';

      modal.style.display = 'flex';
      requestAnimationFrame(function() {
        modal.classList.add('is-active');
      });

      function cleanup(result) {
        modal.classList.remove('is-active');
        setTimeout(function() {
          modal.style.display = 'none';
        }, 200);
        if (okBtn) okBtn.onclick = null;
        if (cancelBtn) cancelBtn.onclick = null;
        if (backdrop) backdrop.onclick = null;
        resolve(result);
      }

      if (okBtn) {
        okBtn.onclick = function() { cleanup(true); };
      }
      if (cancelBtn) {
        cancelBtn.onclick = function() { cleanup(false); };
      }
      if (backdrop) {
        backdrop.onclick = function() { cleanup(false); };
      }
    });
  }

  function selectQuality(idx) {
    var chosen = qualityLevelsList[idx] || qualityLevelsList[0];
    
    // Check VIP requirement for 720p / 1080p
    if (chosen && chosen.isVip && !isUserVip()) {
      var qMenu = document.getElementById('quality-menu');
      if (qMenu) qMenu.classList.remove('show');
      openVipQualityModal(chosen.label);
      return;
    }

    qualityIdx = idx;
    var el = document.getElementById('quality-display-label');
    if (el) el.textContent = chosen.display;
    var elBottom = document.getElementById('quality-val-bottom');
    if (elBottom) elBottom.textContent = chosen.display;

    var menu = document.getElementById('quality-menu');
    if (menu) menu.classList.remove('show');
    renderQualityMenu();

    // Switch HLS level
    var hls = window.currentShortTV && window.currentShortTV.hls;
    if (hls) {
      if (chosen.levelIndex !== undefined && chosen.levelIndex >= 0) {
        hls.currentLevel = chosen.levelIndex;
        hls.loadLevel = chosen.levelIndex;
      } else if (chosen.label === 'Auto' || chosen.levelIndex === -1) {
        hls.currentLevel = -1; // Auto ABR mode
        hls.loadLevel = -1;
      } else if (chosen.res) {
        // Find closest match in hls.levels
        var targetH = chosen.res;
        var match = -1;
        (hls.levels || []).forEach(function(lv, i) {
          var res = extractResolutionFromLevel(lv);
          if (res && Math.abs(res - targetH) < 80) match = i;
        });
        if (match !== -1) {
          hls.currentLevel = match;
          hls.loadLevel = match;
        }
      }
    }
  }

  function onHlsLevelSwitched(levelIndex) {
    var hls = window.currentShortTV && window.currentShortTV.hls;
    if (qualityIdx === 0 && hls && hls.levels && hls.levels[levelIndex]) {
      var res = extractResolutionFromLevel(hls.levels[levelIndex]);
      if (res) {
        var elBottom = document.getElementById('quality-val-bottom');
        if (elBottom) elBottom.textContent = 'Auto (' + res + 'p)';
      }
    }
  }
  window.onHlsLevelSwitched = onHlsLevelSwitched;

  function cycleQualityOption() {
    if (qualityLevelsList.length <= 1) updateDynamicQualities();
    var nextIdx = (qualityIdx + 1) % qualityLevelsList.length;
    selectQuality(nextIdx);
  }

  // Close dropdowns if clicked outside
  document.addEventListener('click', function(e) {
    var qMenu = document.getElementById('quality-menu');
    if (qMenu && qMenu.classList.contains('show')) {
      if (!e.target.closest('#quality-dropdown-wrap')) {
        qMenu.classList.remove('show');
      }
    }
    var vMenu = document.getElementById('volume-popup-menu');
    if (vMenu && vMenu.classList.contains('show')) {
      if (!e.target.closest('#volume-dropdown-wrap')) {
        vMenu.classList.remove('show');
      }
    }
  });

  function toggleVolumeMenu(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    var menu = document.getElementById('volume-popup-menu');
    if (!menu) return;
    menu.classList.toggle('show');
    // Close quality menu if open
    var qMenu = document.getElementById('quality-menu');
    if (qMenu && menu.classList.contains('show')) qMenu.classList.remove('show');
  }

  function cycleQualityOption() {
    if (qualityOptions.length <= 1) updateDynamicQualities();
    var nextIdx = (qualityIdx + 1) % qualityOptions.length;
    selectQuality(nextIdx);
  }

  var speeds = ['0.5x','0.75x','1.0x','1.25x','1.5x','2.0x'];
  var speedIdx = 2;
  function cycleSpeed() {
    speedIdx = (speedIdx + 1) % speeds.length;
    var el = document.getElementById('speed-val');
    if (el) el.textContent = speeds[speedIdx];
    var elBottom = document.getElementById('speed-val-bottom');
    if (elBottom) elBottom.textContent = speeds[speedIdx];
    var vid = document.getElementById('shorttv-video');
    if (vid) vid.playbackRate = parseFloat(speeds[speedIdx]);
  }

  // ─── Volume & Mute Controls ─────────────────────────────
  function updateVolumeUI(isMuted, vol) {
    var iconHigh = document.getElementById('icon-volume-high');
    var iconMuted = document.getElementById('icon-volume-muted');
    var slider = document.getElementById('volume-slider-range');
    var unmutePill = document.getElementById('player-unmute-pill');
    var popVal = document.getElementById('volume-popup-val');
    var muteBtn = document.getElementById('volume-popup-mute-btn');
    var muteText = document.getElementById('volume-popup-mute-text');

    var effectiveVol = (isMuted ? 0 : (vol !== undefined ? vol : 1));
    var pct = Math.round(effectiveVol * 100);

    if (slider) slider.value = effectiveVol;
    if (popVal) popVal.textContent = isMuted ? '0%' : pct + '%';

    if (isMuted || effectiveVol <= 0) {
      if (iconHigh) iconHigh.style.display = 'none';
      if (iconMuted) iconMuted.style.display = 'block';
      if (unmutePill) unmutePill.style.display = 'inline-flex';
      if (muteBtn) muteBtn.classList.add('is-muted');
      if (muteText) muteText.textContent = 'Unmute';
    } else {
      if (iconHigh) iconHigh.style.display = 'block';
      if (iconMuted) iconMuted.style.display = 'none';
      if (unmutePill) unmutePill.style.display = 'none';
      if (muteBtn) muteBtn.classList.remove('is-muted');
      if (muteText) muteText.textContent = 'Mute';
    }
  }

  function togglePlayerMute(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    var vid = document.getElementById('shorttv-video');
    if (!vid) return;
    vid.muted = !vid.muted;
    if (!vid.muted && vid.volume === 0) {
      vid.volume = 1;
    }
    updateVolumeUI(vid.muted, vid.volume);
  }

  function setPlayerVolume(val) {
    var vid = document.getElementById('shorttv-video');
    if (!vid) return;
    var v = parseFloat(val);
    vid.volume = v;
    vid.muted = (v <= 0);
    updateVolumeUI(vid.muted, v);
  }

  function unmuteFromBanner(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    var vid = document.getElementById('shorttv-video');
    if (!vid) return;
    vid.muted = false;
    vid.volume = 1;
    updateVolumeUI(false, 1);
  }

  // Desktop Keyboard Shortcut Controller (Matches Player Gesture Matrix)
  document.addEventListener('keydown', function(e) {
    if (['INPUT', 'TEXTAREA', 'SELECT'].indexOf(e.target.tagName) !== -1 || e.target.isContentEditable) return;
    var vid = document.getElementById('shorttv-video');
    if (!vid) return;

    var key = e.key;

    // 1. Next Episode: ArrowDown or J
    if (key === 'ArrowDown' || key === 'j' || key === 'J') {
      e.preventDefault();
      if (window.currentShortTV && typeof window.currentShortTV.nextEpisode === 'function') {
        window.currentShortTV.nextEpisode();
        if (typeof updateTopEpBadge === 'function') updateTopEpBadge();
      }
    }
    // 2. Previous Episode: ArrowUp or K
    else if (key === 'ArrowUp' || key === 'k' || key === 'K') {
      e.preventDefault();
      if (window.currentShortTV && typeof window.currentShortTV.prevEpisode === 'function') {
        window.currentShortTV.prevEpisode();
        if (typeof updateTopEpBadge === 'function') updateTopEpBadge();
      }
    }
    // 3. Play / Pause: Spacebar
    else if (key === ' ' || key === 'Spacebar') {
      e.preventDefault();
      togglePlayPause(e);
    }
    // 4. Like / Bookmark: L
    else if (key === 'l' || key === 'L') {
      e.preventDefault();
      var likeBtn = document.getElementById('player-like-btn') || document.querySelector('.btn-player-like') || document.querySelector('.action-btn-like');
      if (typeof toggleLike === 'function') {
        toggleLike(likeBtn);
      } else if (likeBtn) {
        likeBtn.click();
      }
    }
    // 5. Seek Left / Right: ArrowLeft (-10s) / ArrowRight (+10s)
    else if (key === 'ArrowLeft') {
      e.preventDefault();
      vid.currentTime = Math.max(0, vid.currentTime - 10);
    } else if (key === 'ArrowRight') {
      e.preventDefault();
      vid.currentTime = Math.min(vid.duration || 0, vid.currentTime + 10);
    }
    // 6. Mute / Unmute: M
    else if (key === 'm' || key === 'M') {
      e.preventDefault();
      togglePlayerMute(e);
    }
    // 7. Fullscreen Toggle: F
    else if (key === 'f' || key === 'F') {
      e.preventDefault();
      togglePlayerFullscreen();
    }
    // 8. Episodes Panel Toggle: E
    else if (key === 'e' || key === 'E') {
      e.preventDefault();
      var epSheet = document.getElementById('episodes-sheet');
      if (epSheet && epSheet.classList.contains('active')) {
        if (typeof closeSheet === 'function') closeSheet('episodes-sheet');
      } else {
        showDetails(e, 'episodes');
      }
    }
    // 9. Playback Speed Cycle: S
    else if (key === 's' || key === 'S') {
      e.preventDefault();
      cycleSpeed();
    }
  });

  function togglePlayerFullscreen() {
    var playerElem = document.getElementById('player-screen') || document.getElementById('video-area') || document.getElementById('shorttv-phone-col') || document.getElementById('shorttv-video');
    if (!playerElem) return;

    if (!document.fullscreenElement && !document.webkitFullscreenElement) {
      if (playerElem.requestFullscreen) {
        playerElem.requestFullscreen().catch(function(err){ console.warn(err); });
      } else if (playerElem.webkitRequestFullscreen) {
        playerElem.webkitRequestFullscreen();
      } else if (playerElem.webkitEnterFullscreen) {
        // iOS WebKit video fallback
        var vid = document.getElementById('shorttv-video');
        if (vid && vid.webkitEnterFullscreen) vid.webkitEnterFullscreen();
      }
    } else {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen();
      }
    }
  }

  var subtitleOptions = ['Off'];
  var subtitleIdx = 0;

  function updateDynamicSubtitles() {
    var vid = document.getElementById('shorttv-video');
    var hls = window.currentShortTV && window.currentShortTV.hls;
    var subs = ['Off'];

    // 1. Check native HTML5 video textTracks
    if (vid && vid.textTracks && vid.textTracks.length > 0) {
      for (var i = 0; i < vid.textTracks.length; i++) {
        var track = vid.textTracks[i];
        var name = track.label || track.language || ('Track ' + (i + 1));
        if (subs.indexOf(name) === -1) subs.push(name);
      }
    }

    // 2. Check HLS subtitle tracks
    if (hls && hls.subtitleTracks && hls.subtitleTracks.length > 0) {
      hls.subtitleTracks.forEach(function(st, idx) {
        var name = st.name || st.lang || ('Subtitle ' + (idx + 1));
        if (subs.indexOf(name) === -1) subs.push(name);
      });
    }

    // If stream provides subtitle tracks, default to available languages, otherwise show Off
    subtitleOptions = subs;
    if (subtitleIdx >= subtitleOptions.length) subtitleIdx = 0;
    var el = document.getElementById('subtitles-display-label');
    if (el) el.textContent = subtitleOptions[subtitleIdx];
  }

  function cycleSubtitlesOption() {
    if (subtitleOptions.length <= 1) updateDynamicSubtitles();
    subtitleIdx = (subtitleIdx + 1) % subtitleOptions.length;
    var chosen = subtitleOptions[subtitleIdx];
    var el = document.getElementById('subtitles-display-label');
    var subEl = document.getElementById('subtitle-text');
    if (el) el.textContent = chosen;

    var vid = document.getElementById('shorttv-video');
    var hls = window.currentShortTV && window.currentShortTV.hls;

    if (chosen === 'Off') {
      if (subEl) subEl.style.display = 'none';
      if (hls) hls.subtitleTrack = -1;
      if (vid && vid.textTracks) {
        for (var i = 0; i < vid.textTracks.length; i++) {
          vid.textTracks[i].mode = 'disabled';
        }
      }
    } else {
      if (subEl) subEl.style.display = 'block';
      if (hls && hls.subtitleTracks) {
        var trackIdx = -1;
        hls.subtitleTracks.forEach(function(st, idx) {
          if ((st.name || st.lang) === chosen) trackIdx = idx;
        });
        if (trackIdx !== -1) hls.subtitleTrack = trackIdx;
      }
      if (vid && vid.textTracks) {
        for (var i = 0; i < vid.textTracks.length; i++) {
          var t = vid.textTracks[i];
          if ((t.label || t.language) === chosen) {
            t.mode = 'showing';
          } else {
            t.mode = 'disabled';
          }
        }
      }
    }
  }

  var bulletCommentsOn = false;
  function toggleBulletComments(wrapper) {
    bulletCommentsOn = !bulletCommentsOn;
    var circle = document.getElementById('bullet-toggle-circle');
    if (bulletCommentsOn) {
      wrapper.style.background = '#ff2d55';
      if (circle) circle.style.left = '24px';
    } else {
      wrapper.style.background = '#3a3a3c';
      if (circle) circle.style.left = '2px';
    }
  }

  var pipActive = false;
  function togglePipMode(wrapper) {
    pipActive = !pipActive;
    var circle = document.getElementById('pip-toggle-circle');
    var vid = document.getElementById('shorttv-video');
    if (pipActive) {
      wrapper.style.background = '#ff2d55';
      if (circle) circle.style.left = '24px';
      if (document.pictureInPictureEnabled && vid && !document.pictureInPictureElement) {
        vid.requestPictureInPicture().catch(function() {});
      }
    } else {
      wrapper.style.background = '#3a3a3c';
      if (circle) circle.style.left = '2px';
      if (document.exitPictureInPicture && document.pictureInPictureElement) {
        document.exitPictureInPicture().catch(function() {});
      }
    }
  }

  // ─── Interactive Progress Bar (Click & Drag / Scrub) ───
  (function() {
    var isDraggingProgress = false;
    var vid = document.getElementById('shorttv-video');
    var wrap = document.getElementById('progress-wrap');
    var fill = document.getElementById('progress-fill');

    // Keep progress bar in sync with video playback when not dragging
    setInterval(function() {
      if (!isDraggingProgress && vid && fill && vid.duration) {
        fill.style.width = ((vid.currentTime / vid.duration) * 100) + '%';
      }
    }, 150);

    function seekFromEvent(e) {
      if (!vid || !wrap || !vid.duration) return;
      var rect = wrap.getBoundingClientRect();
      var clientX = e.clientX;
      if (e.touches && e.touches.length > 0) {
        clientX = e.touches[0].clientX;
      } else if (e.changedTouches && e.changedTouches.length > 0) {
        clientX = e.changedTouches[0].clientX;
      }
      var offsetX = clientX - rect.left;
      var ratio = Math.max(0, Math.min(1, offsetX / rect.width));
      if (fill) fill.style.width = (ratio * 100) + '%';
      vid.currentTime = ratio * vid.duration;
    }

    if (wrap) {
      // Mouse down / start drag
      wrap.addEventListener('mousedown', function(e) {
        e.preventDefault();
        e.stopPropagation();
        isDraggingProgress = true;
        wrap.classList.add('is-dragging');
        seekFromEvent(e);
      });

      // Touch start / start drag on mobile
      wrap.addEventListener('touchstart', function(e) {
        e.stopPropagation();
        isDraggingProgress = true;
        wrap.classList.add('is-dragging');
        seekFromEvent(e);
      }, { passive: false });

      // Window-level dragging & release handlers so fast drags never lose focus
      window.addEventListener('mousemove', function(e) {
        if (!isDraggingProgress) return;
        e.preventDefault();
        seekFromEvent(e);
      });

      window.addEventListener('mouseup', function(e) {
        if (isDraggingProgress) {
          seekFromEvent(e);
          isDraggingProgress = false;
          wrap.classList.remove('is-dragging');
        }
      });

      window.addEventListener('touchmove', function(e) {
        if (!isDraggingProgress) return;
        seekFromEvent(e);
      }, { passive: false });

      window.addEventListener('touchend', function(e) {
        if (isDraggingProgress) {
          seekFromEvent(e);
          isDraggingProgress = false;
          wrap.classList.remove('is-dragging');
        }
      });
    }
  })();

  // ─── Continuous Watch Time Tracker for Daily Activities / Rewards ───
  (function() {
    var vid = document.getElementById('shorttv-video');
    if (!vid) return;
    var lastTick = Date.now();
    setInterval(function() {
      var now = Date.now();
      var elapsed = Math.round((now - lastTick) / 1000);
      lastTick = now;
      if (vid && !vid.paused && !vid.ended && !vid.seeking && elapsed > 0 && elapsed < 8) {
        var todayKey = new Date().toISOString().slice(0, 10);
        var storageKey = 'shorttv_daily_watch_secs_' + todayKey;
        var curSecs = parseInt(localStorage.getItem(storageKey) || '0', 10);
        curSecs += elapsed;
        localStorage.setItem(storageKey, String(curSecs));

        // Sync with Firebase RTDB if logged in
        if (typeof rtdb !== 'undefined' && rtdb && window.firebaseUser) {
          try {
            rtdb.ref('users/' + window.firebaseUser.uid + '/dailyWatchSecs/' + todayKey).set(curSecs);
          } catch(e) {}
        }
      }
    }, 2000);
  })();

  // ─── Vertical Swipe Up/Down (Next / Previous Episode) ───
  (function() {
    var touchStartY = 0;
    var touchStartX = 0;
    var touchStartTime = 0;
    var isSwiping = false;

    var videoArea = document.getElementById('video-area') || document.getElementById('player-screen');
    if (!videoArea) return;

    videoArea.addEventListener('touchstart', function(e) {
      if (e.touches.length !== 1) return;
      var sheet = document.getElementById('episodes-sheet');
      var settings = document.getElementById('settings-sheet');
      if ((sheet && sheet.style.transform === 'translateY(0px)') || 
          (settings && settings.style.transform === 'translateY(0px)')) {
        return; // Don't trigger episode switch if a bottom sheet is open
      }
      touchStartY = e.touches[0].clientY;
      touchStartX = e.touches[0].clientX;
      touchStartTime = Date.now();
      isSwiping = true;
    }, { passive: true });

    videoArea.addEventListener('touchend', function(e) {
      if (!isSwiping || e.changedTouches.length !== 1) return;
      isSwiping = false;

      var deltaY = e.changedTouches[0].clientY - touchStartY;
      var deltaX = e.changedTouches[0].clientX - touchStartX;
      var deltaTime = Date.now() - touchStartTime;

      if (Math.abs(deltaY) > 50 && Math.abs(deltaY) > Math.abs(deltaX) * 1.5 && deltaTime < 700) {
        if (deltaY < 0) {
          // Swiped UP -> Next Episode
          if (window.currentShortTV && typeof window.currentShortTV.nextEpisode === 'function') {
            window.currentShortTV.nextEpisode();
            updateTopEpBadge();
          }
        } else {
          // Swiped DOWN -> Previous Episode
          if (window.currentShortTV && typeof window.currentShortTV.prevEpisode === 'function') {
            window.currentShortTV.prevEpisode();
            updateTopEpBadge();
          }
        }
      }
    }, { passive: true });

    function updateTopEpBadge() {
      setTimeout(function() {
        if (window.currentShortTV) {
          var ep = window.currentShortTV.currentEpisodeNum;
          var label = document.getElementById('player-ep-top-label');
          if (label && ep) {
            label.textContent = 'EP.' + ep;
          }
        }
      }, 50);
    }
  })();

  // ─── Direct Page Watch Configuration ───────────────────
  var seriesId = <?php echo (int) $id; ?>;
  var currentEp = <?php echo (int) $episode; ?>;
  var seriesTitle = <?php echo wp_json_encode( $title ); ?>;
  var seriesPoster = <?php echo wp_json_encode( $poster ); ?>;
  var seriesOverview = <?php echo wp_json_encode( $overview ); ?>;

  window.firebaseUser = null;
  var rtdb = null;
  var rtdbBaseUrl = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';

  window.SHORTTV_CONFIG = window.SHORTTV_CONFIG || {
    seriesId: <?php echo (int) $id; ?>,
    currentEp: <?php echo (int) $episode; ?>,
    restBase: <?php echo wp_json_encode( esc_url_raw( rest_url() ) ); ?>,
    ajaxUrl: <?php echo wp_json_encode( esc_url_raw( admin_url( 'admin-ajax.php' ) ) ); ?>,
    nonce: <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>
  };

  // ── 1. Native WordPress View Counter (Zero Firebase Dependency) ──
  function trackWordPressView() {
    var restBase = (window.SHORTTV_CONFIG && window.SHORTTV_CONFIG.restBase) || (window.SHORT_CONFIG && window.SHORT_CONFIG.rest_base) || (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
    if (!restBase.endsWith('/')) restBase += '/';

    var endpoint = restBase + 'shorttv/v1/views/increment';
    var ajaxUrl = (window.SHORTTV_CONFIG && window.SHORTTV_CONFIG.ajaxUrl) || (window.SHORT_CONFIG && window.SHORT_CONFIG.ajax_url) || '/wordpress/wp-admin/admin-ajax.php';

    function sendAjaxFallback() {
      fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=shorttv_increment_view&series_id=' + encodeURIComponent(seriesId)
      })
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (resp && resp.success && resp.data && resp.data.formatted) {
          var dtEl = document.getElementById('desktop-view-count');
          var mobEl = document.getElementById('mobile-view-count');
          if (dtEl) dtEl.textContent = resp.data.formatted;
          if (mobEl) mobEl.textContent = resp.data.formatted;
        }
      })
      .catch(function(err) {
        console.warn('View tracking AJAX error:', err);
      });
    }

    fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ series_id: seriesId })
    })
    .then(function(res) {
      if (!res.ok) throw new Error('REST view HTTP ' + res.status);
      return res.json();
    })
    .then(function(data) {
      if (data && data.success && data.formatted) {
        var dtEl = document.getElementById('desktop-view-count');
        var mobEl = document.getElementById('mobile-view-count');
        if (dtEl) dtEl.textContent = data.formatted;
        if (mobEl) mobEl.textContent = data.formatted;
      } else {
        sendAjaxFallback();
      }
    })
    .catch(function(e) {
      sendAjaxFallback();
    });
  }

  // ── 2. Realtime Watch History Tracking (LocalStorage + RTDB / Firestore) ──
  function normalizeMediaUrl(url) {
    if (!url || typeof url !== 'string') return '';
    url = url.trim();
    if (!url) return '';
    if (url.startsWith('/')) {
      return window.location.origin + url;
    }
    try {
      var parsed = new URL(url, window.location.origin);
      if ((parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1' || parsed.hostname === '0.0.0.0') && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        return window.location.origin + parsed.pathname + parsed.search;
      }
      if (parsed.pathname.includes('/wp-content/') && parsed.hostname !== window.location.hostname) {
        return window.location.origin + parsed.pathname + parsed.search;
      }
    } catch (e) {
      url = url.replace(/^(?:https?:)?\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?/i, window.location.origin);
    }
    return url;
  }

  function recordWatchHistory(customEp) {
    try {
      var epNum = parseInt(customEp || currentEp || 1, 10);
      var totalEps = <?php echo count( $episodes_list ); ?> || 1;
      var watchLink = '<?php echo esc_url( home_url( '/watch/' . (int) $id ) ); ?>?episode=' + epNum;
      var dramaRecord = {
        id: seriesId,
        title: seriesTitle || 'Short Drama',
        poster: normalizeMediaUrl(seriesPoster) || '',
        episode: epNum,
        lastEpisode: epNum,
        totalEpisodes: totalEps,
        watchUrl: watchLink,
        watchedAt: Date.now()
      };

      var user = window.firebaseUser || (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth && firebase.auth().currentUser) || (localStorage.getItem('short_is_logged_in') === '1' && localStorage.getItem('short_user_uid') ? { uid: localStorage.getItem('short_user_uid') } : null);
      var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';

      if (user && user.uid) {
        // 1. Signed-in User by Firebase UID & Firestore Profile
        try {
          var userHist = JSON.parse(localStorage.getItem('shorttv_history_' + user.uid) || '{}');
          userHist[seriesId] = dramaRecord;
          localStorage.setItem('shorttv_history_' + user.uid, JSON.stringify(userHist));

          var scopedCwKey = 'short_continue_watching_' + user.uid + '_' + activeProfile;
          var scopedCw = JSON.parse(localStorage.getItem(scopedCwKey) || '{}');
          scopedCw[String(seriesId)] = dramaRecord;
          localStorage.setItem(scopedCwKey, JSON.stringify(scopedCw));

          fetch(rtdbBaseUrl + '/users/' + user.uid + '/history/' + seriesId + '.json', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dramaRecord)
          }).catch(function() {});

          if (rtdb) {
            try { rtdb.ref('users/' + user.uid + '/history/' + seriesId).set(dramaRecord); } catch(e) {}
          } else if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) {
            try { firebase.database().ref('users/' + user.uid + '/history/' + seriesId).set(dramaRecord); } catch(e) {}
          }

          if (typeof firebase !== 'undefined' && firebase.firestore) {
            try {
              firebase.firestore().collection('users').doc(user.uid).collection('profiles').doc(activeProfile).collection('watch_history').doc(String(seriesId)).set(dramaRecord, { merge: true }).catch(function() {});
              firebase.firestore().collection('users').doc(user.uid).collection('profiles').doc(activeProfile).collection('continue_watching').doc(String(seriesId)).set(dramaRecord, { merge: true }).catch(function() {});
            } catch(e) {}
          }
        } catch(e) {}
      } else {
        // 2. Guest Local Storage Only
        var historyKey = 'short_guest_watch_history';
        var history = JSON.parse(localStorage.getItem(historyKey) || '{}');
        history[seriesId] = dramaRecord;

        var keys = Object.keys(history).sort(function(a, b) {
          return (history[b].watchedAt || 0) - (history[a].watchedAt || 0);
        });
        if (keys.length > 50) {
          var trimmed = {};
          keys.slice(0, 50).forEach(function(k) { trimmed[k] = history[k]; });
          history = trimmed;
        }
        localStorage.setItem(historyKey, JSON.stringify(history));

        var guestScopedCwKey = 'short_continue_watching_guest_' + activeProfile;
        try {
          var cwObj = JSON.parse(localStorage.getItem(guestScopedCwKey) || '{}');
          cwObj[String(seriesId)] = dramaRecord;
          localStorage.setItem(guestScopedCwKey, JSON.stringify(cwObj));
        } catch(e) {}
      }

      <?php if ( is_user_logged_in() ) : ?>
      try {
        fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=shorttv_save_history&series_id=' + encodeURIComponent(seriesId) + '&episode=' + encodeURIComponent(epNum)
        }).catch(function() {});
      } catch(e) {}
      <?php endif; ?>
    } catch(e) {
      console.warn('Watch history error:', e);
    }
  }

  // Record initial view in watch history
  recordWatchHistory();

  // Listen for episode switch clicks
  document.addEventListener('click', function(e) {
    var epBtn = e.target.closest('.reel-ep-btn, .ep-chip, [data-ep]');
    if (epBtn) {
      var epVal = epBtn.getAttribute('data-ep');
      if (epVal) {
        currentEp = parseInt(epVal, 10) || currentEp;
        recordWatchHistory(currentEp);
      }
    }
  });

  // Optional Firebase integration if configured
  function initWatchFirebase() {
    if (typeof firebase !== 'undefined') {
      try {
        if (!firebase.apps || !firebase.apps.length) {
          firebase.initializeApp({
            apiKey: (window.SHORT_CONFIG && window.SHORT_CONFIG.firebase && window.SHORT_CONFIG.firebase.apiKey) || 'AIzaSyDummyKeyForRTDB',
            databaseURL: rtdbBaseUrl,
            projectId: 'shorttv-fd9ef',
            authDomain: 'shorttv-fd9ef.firebaseapp.com'
          });
        }
        if (firebase.database) {
          try { rtdb = firebase.database(); } catch(e) {}
        }
        if (firebase.auth) {
          firebase.auth().onAuthStateChanged(function(user) {
            window.firebaseUser = user;
            updateUserAuthUI(user);
            syncUserSavedState();
            syncUserLikedState();
            if (user) {
              recordWatchHistory();
            }
            if (user && rtdb) {
              // Load user's own rating
              rtdb.ref('series/' + seriesId + '/ratings/' + user.uid).once('value', function(snap) {
                var myRating = snap.val();
                if (myRating) renderUserRatingStars(myRating);
              });
              // Sync real-time user coins
              rtdb.ref('users/' + user.uid + '/coins').on('value', function(snap) {
                var v = snap.val();
                if (v !== null && v !== undefined) {
                  var cVal = parseInt(v, 10);
                  localStorage.setItem('shorttv_user_coins', String(cVal));
                  var cEl = document.getElementById('paywall-user-coins');
                  if (cEl) cEl.textContent = cVal;
                  if (window.shortTVPlayer) window.shortTVPlayer.userCoins = cVal;
                  if (typeof window.syncHeaderCoins === 'function') window.syncHeaderCoins();
                }
              });
              // Sync authoritative daily ad unlocks from Firebase RTDB
              var todayKey = getAuthoritativeDate();
              rtdb.ref('users/' + user.uid + '/dailyAdUnlocks/' + todayKey).on('value', function(snap) {
                var v = snap.val();
                if (v !== null && v !== undefined) {
                  var count = parseInt(v, 10) || 0;
                  var storageKey = 'shorttv_daily_ad_unlocks_' + user.uid;
                  try {
                    localStorage.setItem(storageKey, JSON.stringify({ date: todayKey, count: count }));
                  } catch(e) {}
                }
              });
            } else if (!user) {
              localStorage.setItem('shorttv_user_coins', '100');
              var cEl = document.getElementById('paywall-user-coins');
              if (cEl) cEl.textContent = '100';
              if (window.shortTVPlayer) window.shortTVPlayer.userCoins = 100;
              if (typeof window.syncHeaderCoins === 'function') window.syncHeaderCoins();
            }
          });
        }
      } catch(e) {
        console.warn('Firebase init:', e);
      }
    }
  }

  // ══ PAYWALL OVERLAY FUNCTIONS ══
  var currentAdClicks = 0;

  // Helper to get authoritative date from server anchor (prevents OS clock change bypass)
  function getAuthoritativeDate() {
    var config = window.SHORT_AD_CONFIG || {};
    if (config.server_date) {
      // Calculate elapsed real time using performance.now() (monotonic timer cannot be manipulated by changing system clock)
      var elapsedMs = 0;
      if (typeof performance !== 'undefined' && performance.now) {
        elapsedMs = performance.now();
      }
      var serverTs = (config.server_timestamp || Date.now()) + elapsedMs;
      return new Date(serverTs).toISOString().slice(0, 10);
    }
    return new Date().toISOString().slice(0, 10);
  }

  // Daily Ad Unlock Tracking (Isolated per logged-in User UID & synced with RTDB)
  function getDailyAdUnlocks() {
    var today = getAuthoritativeDate();
    var uidKey = (window.firebaseUser && window.firebaseUser.uid) ? window.firebaseUser.uid : 'guest';
    var storageKey = 'shorttv_daily_ad_unlocks_' + uidKey;
    var stored = {};
    try {
      stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
    } catch(e) {
      stored = {};
    }
    if (stored.date !== today) {
      stored = { date: today, count: 0 };
      try {
        localStorage.setItem(storageKey, JSON.stringify(stored));
      } catch(e) {}
    }
    return stored;
  }

  function incrementDailyAdUnlock() {
    var today = getAuthoritativeDate();
    var uidKey = (window.firebaseUser && window.firebaseUser.uid) ? window.firebaseUser.uid : 'guest';
    var storageKey = 'shorttv_daily_ad_unlocks_' + uidKey;
    var stored = getDailyAdUnlocks();
    stored.count = (stored.count || 0) + 1;
    stored.date = today;
    try {
      localStorage.setItem(storageKey, JSON.stringify(stored));
    } catch(e) {}

    // Also sync to Firebase RTDB for logged-in users so it syncs across mobile/desktop
    if (typeof rtdb !== 'undefined' && rtdb && window.firebaseUser) {
      try {
        rtdb.ref('users/' + window.firebaseUser.uid + '/dailyAdUnlocks/' + today).set(stored.count);
      } catch(e) {}
    }

    return stored;
  }

  // Sync coin balance & daily ad limit on paywall open
  function paywallOpen(cost, epNum, unlockType) {
    if (!unlockType && window.shortTVPlayer) {
      var curEp = window.shortTVPlayer.getEpisode(epNum || window.shortTVPlayer.currentEpisodeNum);
      if (curEp && curEp.access_control) {
        unlockType = (curEp.access_control.unlock_type || 'free').toLowerCase();
      }
    }
    unlockType = (unlockType || 'coins').toLowerCase();

    var coins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
    var costEl1 = document.getElementById('paywall-coin-cost');
    var costEl2 = document.getElementById('paywall-cost-inline');
    var coinsEl = document.getElementById('paywall-user-coins');
    var epTitleEl = document.getElementById('paywall-ep-title');
    var epDescEl = document.getElementById('paywall-ep-desc');
    var lockWrap = document.getElementById('paywall-lock-icon-wrap');
    var lockSvg = document.getElementById('paywall-lock-svg');
    var balanceWrap = document.getElementById('paywall-coin-balance-wrap');
    var coinBtn = document.getElementById('btn-unlock-coins');
    var vipPrimaryBtn = document.getElementById('btn-unlock-vip-primary');
    var bottomActions = document.getElementById('paywall-bottom-actions');
    var warn = document.getElementById('paywall-coin-warning');
    var adDiv = document.getElementById('paywall-ad-countdown');
    var adBtn = document.getElementById('btn-unlock-ad');
    var limitNotice = document.getElementById('paywall-ad-limit-notice');

    if (costEl1) costEl1.textContent = cost;
    if (costEl2) costEl2.textContent = cost;
    if (coinsEl) coinsEl.textContent = coins;
    if (warn) warn.style.display = 'none';
    if (adDiv) adDiv.style.display = 'none';

    // ── VIP EXCLUSIVE LOCK MODE ──
    if (unlockType === 'vip') {
      if (epTitleEl) epTitleEl.innerHTML = '👑 VIP Exclusive Episode';
      if (epDescEl) epDescEl.innerHTML = 'This episode is exclusive to <strong style="color:#ffc107;">VIP Pass</strong> members and cannot be unlocked with coins.<br>Upgrade to VIP for unlimited instant access!';
      if (lockWrap) {
        lockWrap.style.background = 'linear-gradient(135deg,rgba(255,45,85,0.3),rgba(217,119,6,0.3))';
        lockWrap.style.borderColor = 'rgba(255,193,7,0.8)';
        lockWrap.style.color = '#ffc107';
        lockWrap.style.boxShadow = '0 0 20px rgba(255,193,7,0.35)';
      }
      if (lockSvg) {
        lockSvg.innerHTML = '<path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm14 3c0 .6-.4 1-1 1H6c-.6 0-1-.4-1-1v-1h14v1z"/>';
      }
      if (balanceWrap) balanceWrap.style.display = 'none';
      if (coinBtn) coinBtn.style.display = 'none';
      if (adBtn) adBtn.style.display = 'none';
      if (limitNotice) limitNotice.style.display = 'none';
      if (vipPrimaryBtn) vipPrimaryBtn.style.display = 'flex';
      if (bottomActions) bottomActions.style.display = 'none';
      return;
    }

    // ── STANDARD COINS / ADS LOCK MODE ──
    if (epTitleEl) epTitleEl.innerHTML = epNum ? ('Episode ' + epNum + ' Locked') : 'Episode Locked';
    if (epDescEl) epDescEl.innerHTML = 'This episode requires <strong style="color:#ffc107;">💰 <span id="paywall-coin-cost">' + cost + '</span> Coins</strong> to unlock.';
    if (lockWrap) {
      lockWrap.style.background = 'linear-gradient(135deg,rgba(255,45,85,0.2),rgba(255,152,0,0.1))';
      lockWrap.style.borderColor = 'rgba(255,45,85,0.5)';
      lockWrap.style.color = '#ff2d55';
      lockWrap.style.boxShadow = 'none';
    }
    if (lockSvg) {
      lockSvg.innerHTML = '<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>';
    }
    if (balanceWrap) balanceWrap.style.display = 'flex';
    if (coinBtn) coinBtn.style.display = 'flex';
    if (vipPrimaryBtn) vipPrimaryBtn.style.display = 'none';
    if (bottomActions) bottomActions.style.display = 'flex';

    // Reset current episode click counter
    currentAdClicks = 0;
    var doneEl = document.getElementById('paywall-ad-clicks-done');
    var reqEl = document.getElementById('paywall-ad-clicks-req');
    var remEl = document.getElementById('paywall-ad-remaining');
    var adBar = document.getElementById('paywall-ad-bar');
    var adBtnLabel = document.getElementById('paywall-ad-btn-label');
    var limitMsg = document.getElementById('paywall-ad-limit-msg');

    var config = window.SHORT_AD_CONFIG || {};
    var req = config.required_clicks || 3;
    var maxDaily = config.max_daily_unlocks || 0;
    var daily = getDailyAdUnlocks();
    var isLimitReached = (maxDaily > 0 && daily.count >= maxDaily);

    if (doneEl) doneEl.textContent = '0';
    if (reqEl) reqEl.textContent = req;
    if (remEl) remEl.textContent = req;
    if (adBar) adBar.style.width = '0%';

    // Check if daily limit reached
    if (isLimitReached) {
      if (adBtn) {
        adBtn.style.display = 'flex';
        adBtn.disabled = true;
        adBtn.style.opacity = '0.45';
        adBtn.style.cursor = 'not-allowed';
      }
      if (adBtnLabel) {
        adBtnLabel.innerHTML = '🔒 Daily Ad Limit Reached (' + daily.count + '/' + maxDaily + ')';
      }
      if (limitNotice) {
        limitNotice.style.display = 'block';
        if (limitMsg) {
          limitMsg.textContent = 'You have unlocked all ' + maxDaily + ' free episodes with ads today. Come back tomorrow or use Coins / VIP Pass to continue watching!';
        }
      }
    } else {
      if (adBtn) {
        adBtn.style.display = 'flex';
        adBtn.disabled = false;
        adBtn.style.opacity = '1';
        adBtn.style.cursor = 'pointer';
      }
      if (limitNotice) limitNotice.style.display = 'none';
      if (adBtnLabel && config.mode !== 'video_coins') {
        var dailyBadge = maxDaily > 0 ? ' <span style="font-size:10px; opacity:0.8; font-weight:500;">(' + (daily.count + 1) + '/' + maxDaily + ' free today)</span>' : '';
        adBtnLabel.innerHTML = 'Click Ad to Unlock (<span id="paywall-ad-clicks-done">0</span>/<span id="paywall-ad-clicks-req">' + req + '</span>)' + dailyBadge;
      }
    }

    // Show/dim coin button based on balance
    if (coinBtn) {
      if (coins < cost) {
        coinBtn.style.opacity = '0.5';
      } else {
        coinBtn.style.opacity = '1';
      }
    }
  }

  // Option 1 — Use Coins
  function paywallUnlockCoins() {
    if (window.shortTVPlayer) {
      var ep = window.shortTVPlayer.getEpisode(window.shortTVPlayer.currentEpisodeNum);
      var uType = (ep && ep.access_control && ep.access_control.unlock_type ? ep.access_control.unlock_type : 'free').toLowerCase();
      if (uType === 'vip') {
        alert('This episode is VIP Exclusive and cannot be unlocked with coins. Please upgrade to VIP!');
        return;
      }
    }

    var costEl = document.getElementById('paywall-cost-inline');
    var cost = parseInt(costEl ? costEl.textContent : '15', 10) || 15;
    var coins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);

    if (coins < cost) {
      var warn = document.getElementById('paywall-coin-warning');
      if (warn) warn.style.display = 'block';
      return;
    }

    // Deduct coins
    coins -= cost;
    localStorage.setItem('shorttv_user_coins', String(coins));

    // Save to RTDB + log transaction
    var txEpisode = {
      amount: -cost,
      reason: 'Episode Unlock (EP' + (typeof currentEp !== 'undefined' ? currentEp : '') + ')',
      type: 'unlock',
      status: 'Completed',
      order_id: 'UNL-' + Math.floor(Math.random() * 89999 + 10000),
      ts: Date.now()
    };

    if (typeof window.recordShortTransaction === 'function') {
      window.recordShortTransaction(txEpisode);
    }

    if (typeof rtdb !== 'undefined' && rtdb && window.firebaseUser) {
      var uid = window.firebaseUser.uid;
      rtdb.ref('users/' + uid + '/coins').set(coins);
      if (typeof window.recordShortTransaction !== 'function') {
        rtdb.ref('users/' + uid + '/coinHistory').push(txEpisode);
      }
    }

    // Unlock via player
    if (window.shortTVPlayer) {
      window.shortTVPlayer.unlockCurrentEpisode('coins');
    } else {
      var paywall = document.getElementById('shorttv-paywall-modal');
      if (paywall) paywall.style.display = 'none';
    }
  }

  // Option 2 — Ad Unlock (Multi-click direct unlock or video countdown)
  function paywallWatchAd() {
    if (window.shortTVPlayer) {
      var ep = window.shortTVPlayer.getEpisode(window.shortTVPlayer.currentEpisodeNum);
      var uType = (ep && ep.access_control && ep.access_control.unlock_type ? ep.access_control.unlock_type : 'free').toLowerCase();
      if (uType === 'vip') {
        alert('This episode is VIP Exclusive and cannot be unlocked with ads. Please upgrade to VIP!');
        return;
      }
    }
    var config = window.SHORT_AD_CONFIG || {
      enabled: true,
      mode: 'clicks',
      required_clicks: 3,
      rewarded_urls: ['https://omg10.com/4/11932682'],
      max_daily_unlocks: 3,
      countdown: 15,
      reward_coins: 5
    };

    var daily = getDailyAdUnlocks();
    var maxDaily = config.max_daily_unlocks || 0;
    if (maxDaily > 0 && daily.count >= maxDaily) {
      alert('You have reached the maximum free ad unlocks for today (' + maxDaily + '/' + maxDaily + '). Please use Coins or VIP Pass to continue!');
      return;
    }

    var adDiv = document.getElementById('paywall-ad-countdown');
    var adBtn = document.getElementById('btn-unlock-ad');
    var bar = document.getElementById('paywall-ad-bar');
    var timer = document.getElementById('paywall-ad-timer');
    var coinBtn = document.getElementById('btn-unlock-coins');
    var statusMsg = document.getElementById('paywall-ad-status-msg');
    var doneEl = document.getElementById('paywall-ad-clicks-done');
    var reqEl = document.getElementById('paywall-ad-clicks-req');
    var remEl = document.getElementById('paywall-ad-remaining');
    var btnLabel = document.getElementById('paywall-ad-btn-label');

    // ── MODE A: Multi-Click Direct Unlock ──
    if (config.mode !== 'video_coins') {
      var req = config.required_clicks || 3;
      var urls = (config.rewarded_urls && config.rewarded_urls.length) ? config.rewarded_urls : ['https://omg10.com/4/11932682'];
      
      // Calculate link index based on today's completed unlocks so every episode gets fresh links:
      // E.g. Ep 1 uses links 0..2, Ep 2 uses links 3..5, Ep 3 uses links 6..8
      var epOffset = (daily.count || 0) * req;
      var linkIndex = (epOffset + currentAdClicks) % urls.length;
      var targetUrl = urls[linkIndex] || urls[0];
      
      currentAdClicks++;

      // Launch selected direct link in a new tab
      if (targetUrl) {
        try {
          window.open(targetUrl, '_blank');
        } catch (err) {
          console.warn('Ad popup failed:', err);
        }
      }

      if (adDiv) adDiv.style.display = 'block';
      if (doneEl) doneEl.textContent = currentAdClicks;
      if (bar) bar.style.width = Math.min(100, Math.round((currentAdClicks / req) * 100)) + '%';

      var rem = req - currentAdClicks;

      if (currentAdClicks < req) {
        if (remEl) remEl.textContent = rem;
        if (statusMsg) {
          statusMsg.innerHTML = '🚀 Click <strong>' + rem + ' more time' + (rem > 1 ? 's' : '') + '</strong> to unlock free.';
        }
        if (btnLabel) {
          var dailyBadge = maxDaily > 0 ? ' <span style="font-size:10px; opacity:0.8; font-weight:500;">(' + (daily.count + 1) + '/' + maxDaily + ' free today)</span>' : '';
          btnLabel.innerHTML = 'Click Ad to Unlock (<span id="paywall-ad-clicks-done">' + currentAdClicks + '</span>/<span id="paywall-ad-clicks-req">' + req + '</span>)' + dailyBadge;
        }

        // Brief cooldown between clicks
        var cooldownSecs = config.cooldown || 2;
        if (cooldownSecs > 0 && adBtn) {
          adBtn.disabled = true;
          adBtn.style.opacity = '0.6';
          var cdCount = cooldownSecs;
          var cdTimer = setInterval(function() {
            cdCount--;
            if (cdCount <= 0) {
              clearInterval(cdTimer);
              if (adBtn) {
                adBtn.disabled = false;
                adBtn.style.opacity = '1';
              }
            }
          }, 1000);
        }
      } else {
        // All required clicks reached for this episode!
        incrementDailyAdUnlock();

        if (statusMsg) {
          statusMsg.innerHTML = '🎉 Unlocked! Enjoy the episode free.';
        }
        if (btnLabel) {
          btnLabel.textContent = '🎉 Unlocked! Enjoy episode...';
        }
        if (adBtn) { adBtn.disabled = true; adBtn.style.opacity = '0.7'; }

        setTimeout(function() {
          currentAdClicks = 0;
          if (adDiv) adDiv.style.display = 'none';
          if (adBtn) { adBtn.disabled = false; adBtn.style.opacity = '1'; }

          if (window.shortTVPlayer && typeof window.shortTVPlayer.unlockCurrentEpisode === 'function') {
            window.shortTVPlayer.unlockCurrentEpisode('ad');
          } else {
            var paywall = document.getElementById('shorttv-paywall-modal');
            if (paywall) paywall.style.display = 'none';
          }
        }, 500);
      }
      return;
    }

    // ── MODE B: Full Rewarded Video Ad Modal ──
    playRewardedVideo(function() {
      // Give reward coins if configured
      var rewardAmount = config.reward_coins || 5;
      if (rewardAmount > 0) {
        var coins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10) + rewardAmount;
        localStorage.setItem('shorttv_user_coins', String(coins));

        var userCoinEls = document.querySelectorAll('#header-user-coins, .header-coin-count, #paywall-user-coins');
        userCoinEls.forEach(function(el) {
          if (el) el.textContent = coins;
        });

        var txAdReward = {
          amount: rewardAmount,
          reason: 'Watched Rewarded Video Ad',
          type: 'reward',
          status: 'Completed',
          order_id: 'AD-' + Math.floor(Math.random() * 89999 + 10000),
          ts: Date.now()
        };

        if (typeof window.recordShortTransaction === 'function') {
          window.recordShortTransaction(txAdReward);
        }

        if (typeof rtdb !== 'undefined' && rtdb && window.firebaseUser) {
          var uid = window.firebaseUser.uid;
          rtdb.ref('users/' + uid + '/coins').set(coins);
          if (typeof window.recordShortTransaction !== 'function') {
            rtdb.ref('users/' + uid + '/coinHistory').push(txAdReward);
          }
        }
      }

      // Unlock current episode and resume player
      if (window.shortTVPlayer && typeof window.shortTVPlayer.unlockCurrentEpisode === 'function') {
        window.shortTVPlayer.unlockCurrentEpisode('ad');
      } else {
        var paywall = document.getElementById('shorttv-paywall-modal');
        if (paywall) paywall.style.display = 'none';
      }
    });
  }

  // ── Rewarded Video Ad Modal Engine for Watch Page ──
  // ── Rewarded Video Ad Modal Engine for Watch Page ──
  var rawVideoAdUrl    = (window.SHORT_AD_CONFIG && window.SHORT_AD_CONFIG.video_url) ? window.SHORT_AD_CONFIG.video_url : '';
  var defaultAdVideos  = [
    'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
    'https://vjs.zencdn.net/v/oceans.mp4',
    'https://raw.githubusercontent.com/bower-media-samples/big-buck-bunny-480p-30s/master/video.mp4'
  ];
  var defaultTestVideo = defaultAdVideos[0];
  var videoModal       = document.getElementById('reward-video-modal');
  var videoEl          = document.getElementById('reward-ad-video-element');
  var closeVideoBtn    = document.getElementById('btn-close-reward-video');
  var timerPill        = document.getElementById('reward-video-countdown-num');
  var timerText        = document.getElementById('reward-video-countdown-text');
  var progressFill     = document.getElementById('reward-video-progress-fill');
  var completedOverlay = document.getElementById('reward-video-completed-overlay');
  var spinnerEl        = document.getElementById('reward-video-spinner');
  var soundBtn         = document.getElementById('btn-toggle-reward-sound');
  var exitConfirmModal = document.getElementById('reward-exit-confirm-modal');
  var btnContinueWatch = document.getElementById('btn-ad-continue-watching');
  var btnConfirmExit   = document.getElementById('btn-ad-confirm-exit');
  var exitBackdrop     = document.getElementById('reward-exit-backdrop');

  var adTimerInterval  = null;
  var adRemainingSecs  = 0;
  var adTotalSecs      = (window.SHORT_AD_CONFIG && window.SHORT_AD_CONFIG.countdown) || 15;
  var isRewardGranted  = false;
  var currentTaskDoneCb = null;

  var iconSoundMuted   = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>';
  var iconSoundActive  = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>';

  if (soundBtn && videoEl) {
    soundBtn.addEventListener('click', function(e){
      e.preventDefault();
      e.stopPropagation();
      videoEl.muted = !videoEl.muted;
      soundBtn.innerHTML = videoEl.muted ? iconSoundMuted : iconSoundActive;
    });
  }

  if (videoEl) {
    videoEl.addEventListener('error', function() {
      var cur = videoEl.currentSrc || videoEl.src || '';
      for (var i = 0; i < defaultAdVideos.length; i++) {
        if (cur.indexOf(defaultAdVideos[i]) === -1) {
          videoEl.src = defaultAdVideos[i];
          videoEl.load();
          videoEl.play().catch(function(){});
          break;
        }
      }
    });
  }

  function resolveVideoUrl(targetUrl, callback) {
    if (!targetUrl || typeof targetUrl !== 'string' || !targetUrl.trim()) {
      callback(defaultTestVideo);
      return;
    }
    targetUrl = targetUrl.trim();

    // If direct video file (mp4, webm, m3u8)
    if (/\.(mp4|webm|m3u8)(\?|$)/i.test(targetUrl)) {
      callback(targetUrl);
      return;
    }

    // Google Doubleclick test tags / non-video link -> use official working sample MP4 directly
    if (targetUrl.indexOf('pubads.g.doubleclick.net') !== -1 || targetUrl.indexOf('googleads') !== -1 || targetUrl.indexOf('omg10.com') !== -1) {
      callback(defaultTestVideo);
      return;
    }

    // If third-party VAST XML
    if (targetUrl.indexOf('output=vast') !== -1 || /\.xml(\?|$)/i.test(targetUrl)) {
      if (spinnerEl) spinnerEl.style.display = 'flex';
      fetch(targetUrl)
        .then(function(res){ return res.text(); })
        .then(function(xmlText){
          if (spinnerEl) spinnerEl.style.display = 'none';
          try {
            var parser = new DOMParser();
            var xmlDoc = parser.parseFromString(xmlText, 'text/xml');
            var mediaFiles = xmlDoc.getElementsByTagName('MediaFile');
            var chosenUrl = '';
            for (var i = 0; i < mediaFiles.length; i++) {
              var mf = mediaFiles[i];
              var type = mf.getAttribute('type') || '';
              var content = (mf.textContent || '').trim();
              if (type.indexOf('mp4') !== -1 || /\.mp4/i.test(content)) {
                chosenUrl = content;
                break;
              }
              if (!chosenUrl && content) {
                chosenUrl = content;
              }
            }
            callback(chosenUrl || defaultTestVideo);
          } catch(e) {
            callback(defaultTestVideo);
          }
        })
        .catch(function(err){
          if (spinnerEl) spinnerEl.style.display = 'none';
          callback(defaultTestVideo);
        });
      return;
    }

    // Fallback
    callback(defaultTestVideo);
  }

  function playRewardedVideo(onCompletedCallback) {
    currentTaskDoneCb = onCompletedCallback;
    isRewardGranted = false;
    adTotalSecs = (window.SHORT_AD_CONFIG && window.SHORT_AD_CONFIG.countdown) || 15;
    adRemainingSecs = adTotalSecs;

    if (timerPill) timerPill.textContent = adRemainingSecs;
    if (timerText) timerText.innerHTML = 'Unlock in <strong id="reward-video-countdown-num">' + adRemainingSecs + '</strong>s';
    if (progressFill) progressFill.style.width = '0%';
    if (completedOverlay) completedOverlay.classList.remove('show');
    if (soundBtn) soundBtn.innerHTML = iconSoundMuted;

    if (videoModal) videoModal.style.display = 'flex';

    resolveVideoUrl(rawVideoAdUrl, function(finalSrc){
      if (videoEl) {
        videoEl.muted = true;
        videoEl.src = finalSrc;
        videoEl.currentTime = 0;
        var playPromise = videoEl.play();
        if (playPromise !== undefined) {
          playPromise.catch(function(){
            videoEl.muted = true;
            videoEl.play().catch(function(){});
          });
        }
      }

      // Start countdown tick
      clearInterval(adTimerInterval);
      isAdPausedForExit = false;
      adTimerInterval = setInterval(function(){
        if (isAdPausedForExit) return;
        adRemainingSecs--;
        if (timerPill) timerPill.textContent = Math.max(0, adRemainingSecs);
        if (timerText) timerText.innerHTML = 'Unlock in <strong id="reward-video-countdown-num">' + Math.max(0, adRemainingSecs) + '</strong>s';
        
        var pct = Math.min(100, Math.round(((adTotalSecs - adRemainingSecs) / adTotalSecs) * 100));
        if (progressFill) progressFill.style.width = pct + '%';

        if (adRemainingSecs <= 0) {
          finishRewardedVideo();
        }
      }, 1000);
    });
  }

  function finishRewardedVideo() {
    clearInterval(adTimerInterval);
    if (isRewardGranted) return;
    isRewardGranted = true;

    if (timerText) timerText.innerHTML = '<strong style="color:#22c55e;">Episode Unlocked!</strong>';
    if (progressFill) progressFill.style.width = '100%';
    if (completedOverlay) completedOverlay.classList.add('show');

    incrementDailyAdUnlock();

    if (typeof currentTaskDoneCb === 'function') {
      currentTaskDoneCb();
    }

    setTimeout(function(){
      closeRewardVideoModal(true);
    }, 1800);
  }

  var isAdPausedForExit = false;

  function closeRewardVideoModal(force) {
    if (force || isRewardGranted || adRemainingSecs <= 0) {
      clearInterval(adTimerInterval);
      isAdPausedForExit = false;
      if (videoEl) {
        videoEl.pause();
        videoEl.removeAttribute('src');
        videoEl.load();
      }
      if (videoModal) videoModal.style.display = 'none';
      if (completedOverlay) completedOverlay.classList.remove('show');
      if (exitConfirmModal) exitConfirmModal.style.display = 'none';
      return;
    }

    // User tried to exit before countdown ended - pause timer & pause video
    isAdPausedForExit = true;
    clearInterval(adTimerInterval);
    if (videoEl) videoEl.pause();
    if (exitConfirmModal) exitConfirmModal.style.display = 'flex';
  }

  function resumeAdPlayback() {
    if (exitConfirmModal) exitConfirmModal.style.display = 'none';
    if (!isRewardGranted && adRemainingSecs > 0) {
      isAdPausedForExit = false;
      if (videoEl) {
        videoEl.play().catch(function(){});
      }
      clearInterval(adTimerInterval);
      adTimerInterval = setInterval(function(){
        if (isAdPausedForExit) return;
        adRemainingSecs--;
        if (timerPill) timerPill.textContent = Math.max(0, adRemainingSecs);
        if (timerText) timerText.innerHTML = 'Unlock in <strong id="reward-video-countdown-num">' + Math.max(0, adRemainingSecs) + '</strong>s';
        
        var pct = Math.min(100, Math.round(((adTotalSecs - adRemainingSecs) / adTotalSecs) * 100));
        if (progressFill) progressFill.style.width = pct + '%';

        if (adRemainingSecs <= 0) {
          finishRewardedVideo();
        }
      }, 1000);
    }
  }

  if (btnContinueWatch) {
    btnContinueWatch.addEventListener('click', function(){
      resumeAdPlayback();
    });
  }

  if (exitBackdrop) {
    exitBackdrop.addEventListener('click', function(){
      resumeAdPlayback();
    });
  }

  if (btnConfirmExit) {
    btnConfirmExit.addEventListener('click', function(){
      isAdPausedForExit = false;
      if (exitConfirmModal) exitConfirmModal.style.display = 'none';
      clearInterval(adTimerInterval);
      if (videoEl) {
        videoEl.pause();
        videoEl.removeAttribute('src');
        videoEl.load();
      }
      if (videoModal) videoModal.style.display = 'none';
    });
  }

  if (closeVideoBtn) {
    closeVideoBtn.addEventListener('click', function(){
      closeRewardVideoModal(false);
    });
  }

  // ── Format large numbers (1200 → 1.2K, 1500000 → 1.5M) ──
  function formatViewCount(n) {
    n = parseInt(n, 10) || 0;
    if (n >= 1000000) return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
    return String(n);
  }

  // ── Update rating display in all spans ──
  function updateRatingDisplay(avg, cnt) {
    cnt = parseInt(cnt, 10) || 0;
    var dtEl = document.getElementById('desktop-rating-val');
    var mobEl = document.getElementById('mobile-rating-val');
    var dtCnt = document.getElementById('desktop-rating-count');
    var mobCnt = document.getElementById('mobile-rating-count');
    var totalCnt = document.getElementById('rating-total-count');

    if (dtEl) dtEl.textContent = avg;
    if (mobEl) mobEl.textContent = avg;

    var formattedCount = formatViewCount(cnt);
    var label = cnt === 1 ? '1 rating' : formattedCount + ' ratings';

    if (dtCnt) dtCnt.textContent = '(' + formattedCount + ')';
    if (mobCnt) mobCnt.textContent = '(' + formattedCount + ')';
    if (totalCnt) totalCnt.textContent = '(' + label + ')';
  }

  // ── Star Rating: hover effects & submission ──
  var userRating = 0;
  try {
    var storedRating = localStorage.getItem('shorttv_rating_' + seriesId);
    if (storedRating) {
      userRating = parseInt(storedRating, 10) || 0;
    }
  } catch(e) {}

  function highlightStars(n) {
    document.querySelectorAll('.rating-star-btn').forEach(function(s) {
      var v = parseInt(s.getAttribute('data-star'), 10);
      s.style.color = v <= n ? '#ffc107' : '#555';
      s.style.transform = v <= n ? 'scale(1.2)' : 'scale(1)';
    });
  }
  function resetStarHighlight() {
    renderUserRatingStars(userRating);
  }
  function renderUserRatingStars(n) {
    userRating = n || 0;
    document.querySelectorAll('.rating-star-btn').forEach(function(s) {
      var v = parseInt(s.getAttribute('data-star'), 10);
      s.style.color = v <= userRating ? '#ffc107' : '#555';
      s.style.transform = 'scale(1)';
    });
    var fb = document.getElementById('rating-feedback-txt');
    if (fb) fb.textContent = userRating ? 'You rated ' + userRating + '/5' : '';
  }

  // Initial render if previously rated
  if (userRating > 0) {
    setTimeout(function() { renderUserRatingStars(userRating); }, 100);
  }

  // ── Submit rating to WordPress DB & Realtime DB ──
  function submitRating(n) {
    n = Math.max(1, Math.min(5, parseInt(n, 10) || 5));
    renderUserRatingStars(n);
    try {
      localStorage.setItem('shorttv_rating_' + seriesId, n);
      var rMap = JSON.parse(localStorage.getItem('shorttv_user_ratings') || '{}');
      rMap[seriesId] = n;
      localStorage.setItem('shorttv_user_ratings', JSON.stringify(rMap));
      if (typeof rtdb !== 'undefined' && rtdb && window.firebaseUser) {
        rtdb.ref('users/' + window.firebaseUser.uid + '/ratings/' + seriesId).set(n);
        rtdb.ref('series/' + seriesId + '/ratings/' + window.firebaseUser.uid).set(n);
      }
    } catch(e) {}

    var fb = document.getElementById('rating-feedback-txt');
    if (fb) fb.textContent = 'Saving rating...';

    // Generate/retrieve persistent anonymous user token if not logged in
    var localUserKey = '';
    try {
      localUserKey = localStorage.getItem('shorttv_user_key');
      if (!localUserKey) {
        localUserKey = 'anon_' + Math.random().toString(36).substring(2, 12) + '_' + Date.now();
        localStorage.setItem('shorttv_user_key', localUserKey);
      }
    } catch(e) {}

    var userKey = (window.firebaseUser && window.firebaseUser.uid) ? window.firebaseUser.uid : localUserKey;

    // 1. Submit to WordPress database via native fetch
    var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
    var postBody = 'action=shorttv_submit_rating' +
      '&series_id=' + encodeURIComponent(seriesId) +
      '&rating=' + encodeURIComponent(n) +
      '&user_key=' + encodeURIComponent(userKey);

    fetch(ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: postBody
    })
    .then(function(r) { return r.json(); })
    .then(function(resp) {
      if (resp && resp.success && resp.data) {
        var rVal = resp.data.rating_formatted || (resp.data.rating ? resp.data.rating.toFixed(1) : n.toFixed(1));
        var rCnt = resp.data.rating_count || 1;
        var dtEl = document.getElementById('desktop-rating-val');
        var mobEl = document.getElementById('mobile-rating-val');
        var dtCnt = document.getElementById('desktop-rating-count');
        var mobCnt = document.getElementById('mobile-rating-count');
        var totalCnt = document.getElementById('rating-total-count');

        if (dtEl) dtEl.textContent = rVal;
        if (mobEl) mobEl.textContent = rVal;
        if (dtCnt) dtCnt.textContent = '(' + rCnt + ')';
        if (mobCnt) mobCnt.textContent = '(' + rCnt + ')';
        if (totalCnt) totalCnt.textContent = '(' + (resp.data.rating_count_formatted || (rCnt + ' ratings')) + ')';

        if (fb) fb.textContent = 'You rated ' + n + '/5';
      }
    })
    .catch(function(err) {
      console.error('Rating save error:', err);
      if (fb) fb.textContent = 'You rated ' + n + '/5';
    });

    // Handled exclusively by WordPress database and localStorage
  }

  function updateUserAuthUI(user) {
    var nameEl = document.getElementById('player-user-name');
    if (user && nameEl) {
      nameEl.textContent = user.displayName || (user.email ? user.email.split('@')[0] : 'Profile');
    } else if (nameEl) {
      nameEl.textContent = 'Sign In';
    }

    var commentAvImg = document.getElementById('comment-user-avatar-img');
    var commentAvPlaceholder = document.getElementById('comment-user-avatar-placeholder');
    if (user && user.photoURL && commentAvImg) {
      commentAvImg.src = user.photoURL;
      commentAvImg.style.display = 'block';
      if (commentAvPlaceholder) commentAvPlaceholder.style.display = 'none';
    } else if (commentAvImg) {
      commentAvImg.style.display = 'none';
      if (commentAvPlaceholder) commentAvPlaceholder.style.display = 'block';
    }
  }

  // ─── Auth Submit (Sign In / Sign Up) ────────────────────
  function submitAuthForm() {
    var email = (document.getElementById('auth-input-email').value || '').trim();
    var pass = (document.getElementById('auth-input-password').value || '').trim();
    var feedback = document.getElementById('auth-feedback-box');

    if (!email || !pass) {
      if (feedback) {
        feedback.textContent = 'Please enter both email and password.';
        feedback.style.display = 'block';
      }
      return;
    }

    if (typeof firebase === 'undefined' || !firebase.auth) {
      if (feedback) {
        feedback.textContent = 'Firebase Auth is loading, please try again in a moment.';
        feedback.style.display = 'block';
      }
      return;
    }

    var auth = firebase.auth();
    var promise = (authCurrentMode === 'signup') 
      ? auth.createUserWithEmailAndPassword(email, pass)
      : auth.signInWithEmailAndPassword(email, pass);

    promise.then(function(cred) {
      closeAllSheets();
      if (feedback) feedback.style.display = 'none';
      alert('Welcome ' + (cred.user.email || '') + '!');
    }).catch(function(err) {
      if (feedback) {
        feedback.textContent = err.message || 'Authentication failed.';
        feedback.style.display = 'block';
      }
    });
  }

  // ─── Bookmark / Save (Watchlist) ────────────────────────
  var isBookmarked = false;
  function toggleBookmark(btn) {
    var user = window.firebaseUser || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser);
    var uid = (user && user.uid) || localStorage.getItem('short_user_uid');

    isBookmarked = !isBookmarked;
    updateBookmarkUI(isBookmarked);

    var itemData = isBookmarked ? {
      id: String(seriesId),
      title: seriesTitle,
      poster: seriesPoster,
      overview: seriesOverview || '',
      savedAt: Date.now()
    } : null;

    if (uid) {
      // 1. Save to Firebase Realtime Database SDK
      try {
        if (typeof firebase !== 'undefined' && firebase.database) {
          if (isBookmarked) {
            firebase.database().ref('users/' + uid + '/watchlist/' + seriesId).set(itemData).catch(function(){});
            firebase.database().ref('users/' + uid + '/my_list/' + seriesId).set(itemData).catch(function(){});
          } else {
            firebase.database().ref('users/' + uid + '/watchlist/' + seriesId).remove().catch(function(){});
            firebase.database().ref('users/' + uid + '/my_list/' + seriesId).remove().catch(function(){});
          }
        }
      } catch(e) {}

      // 2. ALSO send direct REST PUT / DELETE to RTDB
      fetch(rtdbBaseUrl + '/users/' + uid + '/watchlist/' + seriesId + '.json', {
        method: isBookmarked ? 'PUT' : 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: isBookmarked ? JSON.stringify(itemData) : null
      }).catch(function(err){
        console.warn('[Watchlist REST error]', err);
      });
      fetch(rtdbBaseUrl + '/users/' + uid + '/my_list/' + seriesId + '.json', {
        method: isBookmarked ? 'PUT' : 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: isBookmarked ? JSON.stringify(itemData) : null
      }).catch(function(){});

      // 3. Sync to user-specific localStorage
      try {
        var localList = JSON.parse(localStorage.getItem('short_watchlist_' + uid) || '{}');
        if (isBookmarked) {
          localList[seriesId] = itemData;
        } else {
          delete localList[seriesId];
        }
        localStorage.setItem('short_watchlist_' + uid, JSON.stringify(localList));
        localStorage.setItem('short_my_list_' + uid, JSON.stringify(localList));
      } catch(e){}
    }

    // Always sync to global/guest local storage keys
    try {
      var globalList = JSON.parse(localStorage.getItem('short_watchlist') || localStorage.getItem('short_my_list') || '{}');
      if (isBookmarked) {
        globalList[seriesId] = itemData;
      } else {
        delete globalList[seriesId];
      }
      localStorage.setItem('short_watchlist', JSON.stringify(globalList));
      localStorage.setItem('short_my_list', JSON.stringify(globalList));
      localStorage.setItem('short_guest_watchlist', JSON.stringify(globalList));
    } catch(e){}
  }

  function updateBookmarkUI(saved) {
    var icon = document.getElementById('sidebar-save-icon');
    var label = document.getElementById('sidebar-save-label');
    var dtBtn = document.getElementById('reel-btn-bookmark');
    var dtLabel = document.getElementById('desktop-bookmark-label');
    var dtIcon = document.getElementById('desktop-bookmark-icon');

    if (saved) {
      if (icon) icon.style.color = '#ff2d55';
      if (label) { label.textContent = 'Saved'; label.style.color = '#ff2d55'; }
      if (dtBtn) dtBtn.classList.add('is-active');
      if (dtLabel) dtLabel.textContent = 'Saved';
      if (dtIcon) dtIcon.style.color = '#ff2d55';
    } else {
      if (icon) icon.style.color = '#fff';
      if (label) { label.textContent = 'Save'; label.style.color = '#fff'; }
      if (dtBtn) dtBtn.classList.remove('is-active');
      if (dtLabel) dtLabel.textContent = 'Save to List';
      if (dtIcon) dtIcon.style.color = '#fff';
    }
  }

  function syncUserSavedState() {
    var user = window.firebaseUser || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser);
    var uid = (user && user.uid) || localStorage.getItem('short_user_uid');

    // First check local storage for instant UI state
    try {
      var localList = JSON.parse((uid && localStorage.getItem('short_watchlist_' + uid)) || localStorage.getItem('short_watchlist') || localStorage.getItem('short_my_list') || '{}');
      if (localList && localList[seriesId]) {
        isBookmarked = true;
        updateBookmarkUI(true);
      }
    } catch(e){}

    if (!uid) return;

    fetch(rtdbBaseUrl + '/users/' + uid + '/watchlist/' + seriesId + '.json')
      .then(function(res){ return res.json(); })
      .then(function(data){
        if (data && data !== null) {
          isBookmarked = true;
          updateBookmarkUI(true);
        } else {
          // Check fallback path
          fetch(rtdbBaseUrl + '/users/' + uid + '/my_list/' + seriesId + '.json')
            .then(function(r2){ return r2.json(); })
            .then(function(d2){
              if (d2 && d2 !== null) {
                isBookmarked = true;
                updateBookmarkUI(true);
              }
            }).catch(function(){});
        }
      })
      .catch(function(){});
  }

  // Check saved state immediately on script evaluation
  syncUserSavedState();

  // ─── Native WordPress Likes / Hearts (Zero Firebase Dependency) ─────
  var isLiked = false;
  var clientLikedKey = 'stv_liked_' + seriesId;

  function initWordPressLikedState() {
    if (localStorage.getItem(clientLikedKey) === '1') {
      isLiked = true;
      updateLikeUI(true);
    }
  }

  function toggleLike(btn) {
    isLiked = !isLiked;
    if (isLiked) {
      localStorage.setItem(clientLikedKey, '1');
    } else {
      localStorage.removeItem(clientLikedKey);
    }
    try {
      var lkMap = JSON.parse(localStorage.getItem('shorttv_user_likes') || '{}');
      if (isLiked) { lkMap[seriesId] = 1; } else { delete lkMap[seriesId]; }
      localStorage.setItem('shorttv_user_likes', JSON.stringify(lkMap));
      if (typeof rtdb !== 'undefined' && rtdb && window.firebaseUser) {
        rtdb.ref('users/' + window.firebaseUser.uid + '/likes/' + seriesId).set(isLiked ? true : null);
      }
    } catch(e) {}
    updateLikeUI(isLiked);

    var restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
    if (!restBase.endsWith('/')) restBase += '/';

    fetch(restBase + 'shorttv/v1/likes/toggle', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        series_id: seriesId,
        liked: isLiked
      })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data && data.success && data.formatted !== undefined) {
        var spans = document.querySelectorAll('#reel-like-count');
        spans.forEach(function(s) { s.textContent = data.formatted; });
      }
    })
    .catch(function(e) {
      // Fallback to admin AJAX
      var ajaxUrl = (window.SHORTTV_CLOUDINARY && window.SHORTTV_CLOUDINARY.ajaxUrl) || (window.SHORT_CONFIG && window.SHORT_CONFIG.ajaxUrl) || '/wordpress/wp-admin/admin-ajax.php';
      fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=shorttv_toggle_like&series_id=' + encodeURIComponent(seriesId) + '&liked=' + (isLiked ? 1 : 0)
      }).then(function(r){ return r.json(); }).then(function(resp){
        if (resp && resp.success && resp.data && resp.data.formatted !== undefined) {
          var spans = document.querySelectorAll('#reel-like-count');
          spans.forEach(function(s) { s.textContent = resp.data.formatted; });
        }
      }).catch(function(){});
    });

    // Handled exclusively by WordPress database and localStorage
  }

  function updateLikeUI(liked) {
    var icon = document.getElementById('sidebar-like-icon');
    var dtBtn = document.getElementById('reel-btn-like');
    var dtIcon = document.getElementById('desktop-like-icon');

    if (icon) {
      icon.style.color = liked ? '#ff2d55' : '#fff';
      icon.style.transform = liked ? 'scale(1.2)' : 'scale(1)';
      icon.style.transition = 'all 0.2s';
    }
    if (dtBtn) {
      if (liked) dtBtn.classList.add('is-active');
      else dtBtn.classList.remove('is-active');
    }
    if (dtIcon) {
      dtIcon.style.color = liked ? '#ff2d55' : 'currentColor';
    }
  }

  function syncUserLikedState() {
    initWordPressLikedState();
    if (!window.firebaseUser || !rtdb) return;
    var uid = window.firebaseUser.uid;
    rtdb.ref('users/' + uid + '/likes/' + seriesId).once('value', function(snap) {
      if (snap.val() !== null) {
        isLiked = !!snap.val();
        updateLikeUI(isLiked);
      }
    });
  }

  // ─── Native WordPress Comments ──────────────────────────────────
  function fetchRealtimeComments() {
    var restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
    if (!restBase.endsWith('/')) restBase += '/';
    var url = restBase + 'short/v1/comments/' + seriesId;

    fetch(url)
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data && Array.isArray(data.comments)) {
          renderComments(data.comments, data.count);
        } else {
          renderComments([], 0);
        }
      })
      .catch(function(err) {
        console.warn('Comments fetch error:', err);
      });
  }

  var activeReplyParentId = 0;

  function setReplyTarget(parentId, authorName) {
    activeReplyParentId = parseInt(parentId, 10) || 0;
    var name = authorName || 'User';

    var bannerMob = document.getElementById('comment-reply-banner');
    var bannerDt  = document.getElementById('desktop-reply-banner');
    var targetMob = document.getElementById('comment-reply-target-name');
    var targetDt  = document.getElementById('desktop-reply-target-name');

    if (targetMob) targetMob.textContent = '@' + name;
    if (targetDt)  targetDt.textContent  = '@' + name;

    if (bannerMob) bannerMob.style.display = 'flex';
    if (bannerDt)  bannerDt.style.display  = 'flex';

    var inputMob = document.getElementById('comment-input-field');
    var inputDt  = document.getElementById('desktop-comment-input');

    if (inputMob) {
      inputMob.placeholder = 'Reply to @' + name + '...';
      inputMob.focus();
    }
    if (inputDt) {
      inputDt.placeholder = 'Reply to @' + name + '...';
      inputDt.focus();
    }
  }

  function cancelReplyTarget() {
    activeReplyParentId = 0;

    var bannerMob = document.getElementById('comment-reply-banner');
    var bannerDt  = document.getElementById('desktop-reply-banner');

    if (bannerMob) bannerMob.style.display = 'none';
    if (bannerDt)  bannerDt.style.display  = 'none';

    var inputMob = document.getElementById('comment-input-field');
    var inputDt  = document.getElementById('desktop-comment-input');

    if (inputMob) inputMob.placeholder = 'Add a comment...';
    if (inputDt)  inputDt.placeholder  = 'Write a comment about this drama...';
  }

  function renderCommentItem(c, isReply, rootParentId) {
    var name = c.userName || 'Viewer';
    var initial = name.charAt(0).toUpperCase();
    var timeAgo = c.createdAt ? new Date(c.createdAt).toLocaleDateString(undefined, { month:'short', day:'numeric' }) : 'Just now';
    var avatarSize = isReply ? 30 : 38;
    var photoHtml = '';

    if (c.userPhoto) {
      photoHtml = '<img src="' + escapeHtml(c.userPhoto) + '" alt="' + escapeHtml(name) + '" style="width:' + avatarSize + 'px;height:' + avatarSize + 'px;border-radius:50%;object-fit:cover;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.4);border:1.5px solid rgba(255,255,255,0.15);" onerror="this.style.display=\'none\'; if(this.nextElementSibling) this.nextElementSibling.style.display=\'flex\';" />' +
        '<div style="display:none;width:' + avatarSize + 'px;height:' + avatarSize + 'px;border-radius:50%;background:linear-gradient(135deg,#e11d48,#7c3aed);align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:' + (isReply ? '12px' : '14px') + ';flex-shrink:0;">' + initial + '</div>';
    } else {
      photoHtml = '<div style="width:' + avatarSize + 'px;height:' + avatarSize + 'px;border-radius:50%;background:linear-gradient(135deg,#e11d48,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:' + (isReply ? '12px' : '14px') + ';flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.3);">' + initial + '</div>';
    }

    var currentUserId = (window.SHORT_CONFIG && window.SHORT_CONFIG.userId) || 0;
    var currentName = localStorage.getItem('short_user_name') || '';
    var isMe = c.isMe || (currentUserId && c.userId == currentUserId) || (currentName && name.toLowerCase() === currentName.toLowerCase());

    var targetReplyId = rootParentId || c.id;

    var replyBtnHtml = '<button type="button" onclick="setReplyTarget(' + targetReplyId + ', \'' + escapeHtml(name).replace(/'/g, "\\'") + '\')" style="background:none;border:none;color:#94a3b8;font-size:11.5px;font-weight:600;cursor:pointer;padding:3px 0;display:inline-flex;align-items:center;gap:4px;transition:color 0.2s;" onmouseover="this.style.color=\'#ff2d55\'" onmouseout="this.style.color=\'#94a3b8\'">' +
      '<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z"/></svg>' +
      'Reply' +
    '</button>';

    var editBtnHtml = isMe ? '<button type="button" onclick="startEditComment(' + c.id + ')" style="background:none;border:none;color:#94a3b8;font-size:11px;font-weight:500;cursor:pointer;padding:3px 0;transition:color 0.2s;" onmouseover="this.style.color=\'#fff\'" onmouseout="this.style.color=\'#94a3b8\'">Edit</button>' : '';
    var deleteBtnHtml = isMe ? '<button type="button" onclick="deleteComment(' + c.id + ')" style="background:none;border:none;color:#ef4444;font-size:11px;font-weight:500;cursor:pointer;padding:3px 0;opacity:0.8;transition:opacity 0.2s;" onmouseover="this.style.opacity=\'1\'" onmouseout="this.style.opacity=\'0.8\'">Delete</button>' : '';

    var actionsHtml = '<div style="display:flex;align-items:center;gap:12px;margin-top:6px;">' +
      replyBtnHtml +
      (editBtnHtml ? '<span style="color:#444;font-size:10px;">•</span>' + editBtnHtml : '') +
      (deleteBtnHtml ? '<span style="color:#444;font-size:10px;">•</span>' + deleteBtnHtml : '') +
    '</div>';

    var repliesHtml = '';
    if (c.replies && c.replies.length > 0) {
      repliesHtml += '<div style="margin-top:12px;padding-left:36px;display:flex;flex-direction:column;gap:10px;">';
      c.replies.forEach(function(r) {
        repliesHtml += renderCommentItem(r, true, c.id);
      });
      repliesHtml += '</div>';
    }

    var bgStyle = 'background:transparent;padding:' + (isReply ? '4px 0;' : '8px 0;') + 'border:none;';

    return '<div style="display:flex;gap:' + (isReply ? '10px' : '12px') + ';align-items:flex-start;' + bgStyle + 'transition:background 0.2s;" id="comment-node-' + c.id + '">' +
      photoHtml +
      '<div style="flex:1;min-width:0;">' +
        '<div style="display:flex;align-items:center;gap:7px;margin-bottom:3px;flex-wrap:wrap;">' +
          '<span style="color:#ffffff;font-size:' + (isReply ? '12.5px' : '13.5px') + ';font-weight:700;letter-spacing:-0.2px;">' + escapeHtml(name) + '</span>' +
          (isMe ? '<span style="background:rgba(225,29,72,0.2);color:#fb7185;font-size:9.5px;font-weight:800;padding:1px 5px;border-radius:4px;border:1px solid rgba(225,29,72,0.3);">YOU</span>' : '') +
          (c.episode ? '<span style="background:rgba(255,255,255,0.08);color:#94a3b8;font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:4px;">EP ' + escapeHtml(String(c.episode)) + '</span>' : '') +
          '<span style="color:#64748b;font-size:11px;margin-left:auto;">' + timeAgo + '</span>' +
        '</div>' +
        '<div id="comment-text-' + c.id + '" style="color:#e2e8f0;font-size:' + (isReply ? '12.5px' : '13px') + ';line-height:1.55;word-break:break-word;">' + escapeHtml(c.text || '') + '</div>' +
        '<div id="comment-edit-box-' + c.id + '" style="display:none;margin-top:6px;">' +
          '<textarea id="comment-edit-input-' + c.id + '" style="width:100%;background:#1b1b1f;border:1px solid rgba(255,255,255,0.18);border-radius:8px;padding:8px 10px;color:#fff;font-size:13px;resize:vertical;min-height:50px;box-sizing:border-box;outline:none;font-family:inherit;"></textarea>' +
          '<div style="display:flex;gap:6px;margin-top:6px;justify-content:flex-end;">' +
            '<button type="button" onclick="cancelEditComment(' + c.id + ')" style="background:transparent;border:1px solid rgba(255,255,255,0.15);color:#aaa;border-radius:12px;padding:3px 10px;font-size:11px;cursor:pointer;">Cancel</button>' +
            '<button type="button" onclick="saveEditComment(' + c.id + ')" style="background:#ff2d55;border:none;color:#fff;border-radius:12px;padding:3px 12px;font-size:11px;font-weight:700;cursor:pointer;">Save</button>' +
          '</div>' +
        '</div>' +
        actionsHtml +
        repliesHtml +
      '</div>' +
    '</div>';
  }

  function startEditComment(commentId) {
    var textEl = document.getElementById('comment-text-' + commentId);
    var editBox = document.getElementById('comment-edit-box-' + commentId);
    var input = document.getElementById('comment-edit-input-' + commentId);
    if (textEl && editBox && input) {
      input.value = textEl.textContent || '';
      textEl.style.display = 'none';
      editBox.style.display = 'block';
      input.focus();
    }
  }

  function cancelEditComment(commentId) {
    var textEl = document.getElementById('comment-text-' + commentId);
    var editBox = document.getElementById('comment-edit-box-' + commentId);
    if (textEl && editBox) {
      textEl.style.display = 'block';
      editBox.style.display = 'none';
    }
  }

  function saveEditComment(commentId) {
    var input = document.getElementById('comment-edit-input-' + commentId);
    if (!input) return;
    var newText = input.value.trim();
    if (!newText) return;

    var restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
    if (!restBase.endsWith('/')) restBase += '/';
    var url = restBase + 'short/v1/comments/' + commentId;

    fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': (window.SHORT_CONFIG && window.SHORT_CONFIG.nonce) || ''
      },
      body: JSON.stringify({ text: newText })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      if (data && data.success) {
        fetchRealtimeComments();
      } else {
        alert(data.error || 'Failed to update comment');
      }
    })
    .catch(function(err) {
      console.warn('Comment edit error:', err);
      fetchRealtimeComments();
    });
  }

  function deleteComment(commentId) {
    showWatchCustomConfirm({
      title: 'Delete Comment',
      message: 'Are you sure you want to delete this comment?',
      confirmText: 'Delete',
      cancelText: 'Cancel'
    }).then(function(confirmed) {
      if (!confirmed) return;

      var restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
      if (!restBase.endsWith('/')) restBase += '/';
      var url = restBase + 'short/v1/comments/' + commentId;

      fetch(url, {
        method: 'DELETE',
        headers: {
          'X-WP-Nonce': (window.SHORT_CONFIG && window.SHORT_CONFIG.nonce) || ''
        }
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        fetchRealtimeComments();
      })
      .catch(function(err) {
        console.warn('Comment delete error:', err);
        fetchRealtimeComments();
      });
    });
  }

  function renderComments(items, totalCount) {
    var listEl = document.getElementById('comments-list-container');
    var dtListEl = document.getElementById('desktop-comments-list');
    var countEl = document.getElementById('comments-sheet-count');
    var countSidebar = document.getElementById('sidebar-comments-count');
    var countDtTab = document.getElementById('dt-tab-comments-count');

    var count = typeof totalCount === 'number' ? totalCount : (items ? items.length : 0);

    if (countEl) countEl.textContent = '(' + count + ')';
    if (countSidebar) countSidebar.textContent = count;
    if (countDtTab) countDtTab.textContent = '(' + count + ')';

    if (!items || !items.length) {
      var emptyHtml = '<div style="text-align:center;padding:30px 10px;color:#888;font-size:13px;">No comments yet on this drama. Be the first to leave a comment!</div>';
      if (listEl) listEl.innerHTML = emptyHtml;
      if (dtListEl) dtListEl.innerHTML = emptyHtml;
      return;
    }

    var html = '';
    items.forEach(function(c) {
      html += renderCommentItem(c, false, c.id);
    });

    if (listEl) listEl.innerHTML = html;
    if (dtListEl) dtListEl.innerHTML = html;
  }

  function submitComment() {
    var inputMob = document.getElementById('comment-input-field');
    var inputDt  = document.getElementById('desktop-comment-input');
    var text = ((inputDt && inputDt.value) || (inputMob && inputMob.value) || '').trim();
    if (!text) return;

    var epNum = (window.currentShortTV && window.currentShortTV.currentEpisodeNum) || (typeof currentEp !== 'undefined' ? currentEp : 1);
    var savedName = localStorage.getItem('short_user_name') || 'Viewer';
    var parentId = activeReplyParentId || 0;

    var restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
    if (!restBase.endsWith('/')) restBase += '/';
    var url = restBase + 'short/v1/comments';

    if (inputMob) inputMob.disabled = true;
    if (inputDt)  inputDt.disabled = true;

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': (window.SHORT_CONFIG && window.SHORT_CONFIG.nonce) || ''
      },
      body: JSON.stringify({
        post_id: seriesId,
        text: text,
        episode: epNum,
        parent_id: parentId,
        userName: savedName
      })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
      try {
        var myComms = JSON.parse(localStorage.getItem('shorttv_user_comments') || '[]');
        myComms.push({ seriesId: seriesId, ep: epNum, text: text, ts: Date.now() });
        localStorage.setItem('shorttv_user_comments', JSON.stringify(myComms));
        if (typeof rtdb !== 'undefined' && rtdb && window.firebaseUser) {
          rtdb.ref('users/' + window.firebaseUser.uid + '/comments').push({ seriesId: seriesId, ep: epNum, text: text, ts: Date.now() });
        }
      } catch(e) {}
      if (inputMob) { inputMob.value = ''; inputMob.disabled = false; }
      if (inputDt)  { inputDt.value = ''; inputDt.disabled = false; }
      cancelReplyTarget();
      fetchRealtimeComments();
    })
    .catch(function(err) {
      console.warn('Comment post error:', err);
      if (inputMob) inputMob.disabled = false;
      if (inputDt)  inputDt.disabled = false;
      cancelReplyTarget();
      fetchRealtimeComments();
    });
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function(m) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    trackWordPressView();
    initWordPressLikedState();
    initWatchFirebase();
    fetchRealtimeComments();
    if (typeof initCenterPlayBtn === 'function') {
      initCenterPlayBtn();
    }
    if (typeof checkSynopsisOverflow === 'function') {
      setTimeout(checkSynopsisOverflow, 100);
      window.addEventListener('resize', checkSynopsisOverflow);
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js"></script>
<script src="<?php echo esc_url( get_template_directory_uri() . '/assets/js/shorttv-player.js?v=' . time() ); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
	var seriesData = <?php echo $schema_json ?: '{}'; ?>;
	var initialEp  = <?php echo (int) $episode; ?>;
	if (typeof ShortTVPlayer !== 'undefined') {
		window.currentShortTV = new ShortTVPlayer('#shorttv-app-container', seriesData, initialEp);
		window.shortTVPlayer = window.currentShortTV;
	}
});
</script>

<?php get_footer(); ?>

