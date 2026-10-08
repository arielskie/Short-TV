<?php
/**
 * New & Popular Page Template - ShortTV Experience
 *
 * @package Short_Stream
 */

get_header();

// 1. Fetch short drama series
$dramas_query = new WP_Query( array(
	'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
	'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
	'posts_per_page' => 50,
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

$db_dramas = array();
if ( $dramas_query->have_posts() ) {
	foreach ( $dramas_query->posts as $p ) {
		$schema = \SHORT\Core\CPT\Video_CPT::get_series_schema( $p->ID );
		$schema['post_id']     = $p->ID;
		$schema['post_status'] = $p->post_status;
		$schema['title']       = ! empty( $p->post_title ) ? $p->post_title : ( $schema['title'] ?? 'Short Drama' );
		$db_dramas[]           = $schema;
	}
}

$db_formatted = array();
if ( ! empty( $db_dramas ) ) {
	foreach ( $db_dramas as $db_item ) {
		$post_id = $db_item['post_id'] ?? 0;
		if ( ! $post_id && ! empty( $db_item['slug'] ) ) {
			$post_obj = get_page_by_path( $db_item['slug'], OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
			if ( $post_obj ) $post_id = $post_obj->ID;
		}

		$raw_poster = $db_item['cover_assets']['vertical_poster'] ?? '';
		if ( empty( $raw_poster ) && $post_id ) {
			$raw_poster = get_post_meta( $post_id, '_shorttv_vertical_poster', true );
		}
		if ( empty( $raw_poster ) && $post_id && has_post_thumbnail( $post_id ) ) {
			$raw_poster = get_the_post_thumbnail_url( $post_id, 'full' );
		}
		$poster = function_exists( 'short_normalize_url' ) ? short_normalize_url( $raw_poster ) : $raw_poster;

		$raw_back = $db_item['cover_assets']['horizontal_banner'] ?? $raw_poster;
		if ( empty( $raw_back ) && $post_id ) {
			$raw_back = get_post_meta( $post_id, '_shorttv_horizontal_banner', true );
		}
		$backdrop = function_exists( 'short_normalize_url' ) ? short_normalize_url( $raw_back ) : $raw_back;

		$views_num = (int) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_view_count', true ) : 0 ) ?: ( $db_item['analytics']['view_count'] ?? 0 ) );
		if ( $views_num >= 1000000 ) {
			$views_fmt = round( $views_num / 1000000, 1 ) . 'M';
		} elseif ( $views_num >= 1000 ) {
			$views_fmt = round( $views_num / 1000, 1 ) . 'K';
		} elseif ( $views_num > 0 ) {
			$views_fmt = (string) $views_num;
		} else {
			$views_fmt = '0';
		}

		$likes_num      = (int) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_like_count', true ) : 0 ) ?: ( $db_item['analytics']['like_count'] ?? 0 ) );
		$comments_num   = (int) ( $post_id ? get_comments_number( $post_id ) : 0 );
		$rating_val     = (float) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_rating', true ) : 0 ) ?: ( $db_item['rating'] ?? ( $db_item['vote_average'] ?? 0 ) ) );
		$rating_count   = (int) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_rating_count', true ) : 0 ) ?: ( $db_item['rating_count'] ?? ( $db_item['vote_count'] ?? 0 ) ) );
		$episodes_count = ! empty( $db_item['episodes'] ) ? count( (array) $db_item['episodes'] ) : ( (int) ( $post_id ? get_post_meta( $post_id, '_shorttv_episode_count', true ) : 0 ) ?: 1 );
		$date_ts        = $post_id ? get_post_time( 'U', true, $post_id ) : time();

		$is_vip = false;
		if ( $post_id && '1' === get_post_meta( $post_id, '_shorttv_is_vip', true ) ) {
			$is_vip = true;
		}
		if ( ! $is_vip && ! empty( $db_item['episodes'] ) ) {
			foreach ( (array) $db_item['episodes'] as $ep_data ) {
				$u_t = strtolower( $ep_data['access_control']['unlock_type'] ?? '' );
				if ( 'vip' === $u_t ) {
					$is_vip = true;
					break;
				}
			}
		}

		$genres_list = ! empty( $db_item['genres'] ) ? (array) $db_item['genres'] : array();
		if ( $post_id ) {
			$terms = wp_get_post_terms( $post_id, 'video_genre', array( 'fields' => 'names' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$genres_list = array_unique( array_merge( $genres_list, $terms ) );
			}
		}

		$tag     = ! empty( $db_item['tag'] ) ? $db_item['tag'] : 'Trending';
		$trope   = ! empty( $genres_list[0] ) ? $genres_list[0] : ( $db_item['trope'] ?? 'Romance' );
		$subline = ! empty( $genres_list ) ? implode( ' | ', $genres_list ) : 'Series | Romance';

		$db_formatted[] = array(
			'id'             => $post_id ?: ( $db_item['media_id'] ?? 'db_' . $post_id ),
			'post_id'        => $post_id,
			'title'          => ! empty( $db_item['title'] ) ? $db_item['title'] : ( get_the_title( $post_id ) ?: 'Short Drama' ),
			'slug'           => $db_item['slug'] ?? 'short-drama',
			'tag'            => $tag,
			'trope'          => $trope,
			'genres'         => $genres_list,
			'subline'        => $subline,
			'overview'       => $db_item['overview'] ?? ( $post_id ? get_post_field( 'post_excerpt', $post_id ) : '' ),
			'views_num'      => $views_num,
			'views_str'      => $views_fmt,
			'likes_num'      => $likes_num,
			'comments_num'   => $comments_num,
			'rating_val'     => $rating_val,
			'rating_count'   => $rating_count,
			'episodes_count' => $episodes_count,
			'is_vip'         => $is_vip,
			'date_ts'        => $date_ts,
			'poster'         => $poster ?: 'https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=600&auto=format&fit=crop&q=80',
			'backdrop'       => $backdrop ?: ( $poster ?: 'https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=1600&auto=format&fit=crop&q=80' ),
			'badge'          => function_exists( 'short_calculate_drama_badge' ) ? short_calculate_drama_badge( $is_vip, $date_ts, $views_num, $likes_num ) : ( $is_vip ? 'VIP' : '' ),
			'watch_url'      => home_url( '/watch/' . ( $post_id ?: '' ) ),
		);
	}
}

$unique_dramas = array();
$seen_titles = array();
foreach ( $db_formatted as $d ) {
	$key = strtolower( trim( $d['title'] ) );
	if ( ! isset( $seen_titles[ $key ] ) ) {
		$seen_titles[ $key ] = true;
		$unique_dramas[] = $d;
	}
}

if ( empty( $unique_dramas ) && function_exists( 'short_get_curated_fallback_dramas' ) ) {
	$unique_dramas = short_get_curated_fallback_dramas();
}

// 2. Check if a Hero Showcase section is configured
$db_sections = get_option( 'short_new_popular_sections', array() );
if ( empty( $db_sections ) && class_exists( 'SHORT\\Core\\Admin\\Dashboard' ) ) {
	$db_sections = \SHORT\Core\Admin\Dashboard::get_default_sections( 'new-popular' );
}

// Helper function for New & Popular sections
$fetch_popular_section = function( $sec, $unique_dramas ) {
	$type     = $sec['type'] ?? 'views';
	$endpoint = strtolower( trim( $sec['endpoint'] ?? '' ) );
	$limit    = ! empty( $sec['limit'] ) ? (int) $sec['limit'] : 20;
	$pool     = $unique_dramas;

	// VIP Exclusives filter
	if ( $type === 'vip' || $endpoint === 'vip' || $type === 'shorttv_vip' ) {
		$pool = array_filter( $pool, function( $d ) {
			return ! empty( $d['is_vip'] );
		} );
	}

	if ( $type === 'rating' || $endpoint === 'rating' || ( $sec['layout'] ?? '' ) === 'top10' || $type === 'leaderboard' || $endpoint === 'leaderboard' || ( $sec['layout'] ?? '' ) === 'leaderboard' ) {
		usort( $pool, function( $a, $b ) {
			$score_a = ( $a['rating_count'] > 0 ) ? ( $a['rating_val'] * 1000 + $a['rating_count'] * 50 + $a['likes_num'] * 2 + $a['views_num'] * 0.01 ) : ( $a['likes_num'] * 2 + $a['views_num'] * 0.01 );
			$score_b = ( $b['rating_count'] > 0 ) ? ( $b['rating_val'] * 1000 + $b['rating_count'] * 50 + $b['likes_num'] * 2 + $b['views_num'] * 0.01 ) : ( $b['likes_num'] * 2 + $b['views_num'] * 0.01 );
			return $score_b <=> $score_a;
		} );
	} elseif ( $type === 'latest' || $endpoint === 'latest' ) {
		usort( $pool, function( $a, $b ) {
			if ( $a['date_ts'] === $b['date_ts'] ) {
				return $b['post_id'] <=> $a['post_id'];
			}
			return $b['date_ts'] <=> $a['date_ts'];
		} );
	} elseif ( $type === 'episodes' || $endpoint === 'episodes' ) {
		usort( $pool, function( $a, $b ) {
			$score_a = ( $a['episodes_count'] * 50 ) + ( $a['comments_num'] * 20 ) + $a['likes_num'];
			$score_b = ( $b['episodes_count'] * 50 ) + ( $b['comments_num'] * 20 ) + $b['likes_num'];
			return $score_b <=> $score_a;
		} );
	} else {
		// Most Viewed (Popular)
		usort( $pool, function( $a, $b ) {
			if ( $a['views_num'] === $b['views_num'] ) {
				return $b['likes_num'] <=> $a['likes_num'];
			}
			return $b['views_num'] <=> $a['views_num'];
		} );
	}

	return array_slice( array_values( $pool ), 0, $limit );
};

$has_hero_sec = false;
if ( ! empty( $db_sections ) && is_array( $db_sections ) ) {
	foreach ( $db_sections as $key => $sec ) {
		// Migrate legacy shorttv_* type values on the fly
		if ( 0 === strpos( $sec['type'] ?? '', 'shorttv_' ) && class_exists( 'Short_Stream_Dashboard' ) ) {
			$sec = Short_Stream_Dashboard::migrate_legacy_section( $sec );
			$db_sections[ $key ] = $sec;
		}
		if ( ! empty( $sec['enabled'] ) && ( $sec['layout'] ?? '' ) === 'hero' ) {
			$has_hero_sec = true;
			break;
		}
	}
}

$hero_items = array();
if ( $has_hero_sec && ! empty( $db_sections ) && is_array( $db_sections ) ) {
	foreach ( $db_sections as $key => $sec ) {
		if ( ! empty( $sec['enabled'] ) && ( $sec['layout'] ?? '' ) === 'hero' ) {
			$hero_items = $fetch_popular_section( $sec, $unique_dramas );
			break;
		}
	}
}
if ( empty( $hero_items ) && ! empty( $unique_dramas ) ) {
	$hero_pool = $unique_dramas;
	usort( $hero_pool, function( $a, $b ) {
		$score_a = ( $a['views_num'] * 2 ) + ( $a['rating_val'] * 100 ) + $a['likes_num'];
		$score_b = ( $b['views_num'] * 2 ) + ( $b['rating_val'] * 100 ) + $b['likes_num'];
		return $score_b <=> $score_a;
	} );
	$hero_items = array_slice( $hero_pool, 0, 5 );
}
$active_hero = ! empty( $hero_items[0] ) ? $hero_items[0] : null;

$sections_to_render = ! empty( $db_sections ) ? $db_sections : array(
	array( 'id' => 'top_10_today', 'title' => 'TOP 🏆 (Ranked 1-10)', 'type' => 'rating', 'layout' => 'top10', 'endpoint' => 'rating', 'limit' => 10, 'enabled' => 1 ),
	array( 'id' => 'trending_today', 'title' => 'Most Viewed Dramas 🔥', 'type' => 'views', 'layout' => 'portrait', 'endpoint' => 'views', 'limit' => 20, 'enabled' => 1 ),
	array( 'id' => 'new_releases', 'title' => 'New Releases 🚀', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 20, 'enabled' => 1 ),
	array( 'id' => 'binge_ready', 'title' => 'Binge-Ready (Long Series) 📺', 'type' => 'episodes', 'layout' => 'portrait', 'endpoint' => 'episodes', 'limit' => 20, 'enabled' => 1 ),
);
?>

<div class="short-homepage-wrapper <?php echo ! $active_hero ? 'no-hero-wrapper' : ''; ?>" style="background:#121212; min-height:100vh; color:#ffffff; <?php echo ! $active_hero ? 'padding-top: 100px;' : ''; ?> padding-bottom: 60px;">
	
	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<!-- 1. REELSHORT INTERACTIVE HERO SHOWCASE SLIDER                               -->
	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<?php if ( $active_hero ) : ?>
	<section class="reel-hero-showcase" id="reel-hero-carousel" style="position:relative; width:100%; min-height:660px; display:flex; align-items:flex-end; padding-bottom:95px; box-sizing:border-box; margin-bottom: 40px;">
		
		<!-- Dynamic Cinematic Backdrop Image -->
		<div class="reel-hero-bg-layer" id="reel-hero-bg" style="position:absolute; inset:0; background: url('<?php echo esc_url( $active_hero['backdrop'] ); ?>') center top / cover no-repeat; transition: background-image 0.5s ease-in-out;">
			<div style="position:absolute; inset:0; pointer-events:none; background: linear-gradient(180deg, rgba(18,18,18,0.85) 0%, rgba(18,18,18,0.2) 30%, rgba(18,18,18,0.4) 65%, #121212 100%);"></div>
			<div style="position:absolute; inset:0; pointer-events:none; background: linear-gradient(90deg, #121212 0%, rgba(18,18,18,0.95) 8%, rgba(18,18,18,0.7) 25%, rgba(18,18,18,0.15) 50%, transparent 70%);"></div>
			<div style="position:absolute; inset:0; pointer-events:none; background: linear-gradient(270deg, #121212 0%, rgba(18,18,18,0.95) 8%, rgba(18,18,18,0.65) 20%, transparent 50%);"></div>
		</div>

		<!-- Hero Navigation Arrows -->
		<button type="button" class="reel-hero-side-arrow prev" id="btn-hero-prev-side" aria-label="Previous Drama">
			<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
		</button>
		<button type="button" class="reel-hero-side-arrow next" id="btn-hero-next-side" aria-label="Next Drama">
			<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
		</button>

		<div class="reel-container" style="position:relative; z-index:4; max-width:1440px; width:100%; margin:0 auto; padding:0 72px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:36px;">
			
			<!-- Left Column: Title, Badges, Synopsis & Play CTA -->
			<div class="reel-hero-info-col" style="max-width:620px; flex:1 1 450px;">
				<!-- Rank Top Row (Fills upper space above Trending/Genre) -->
				<div class="reel-hero-rank-row" style="margin-bottom:8px;">
					<span id="reel-hero-badge-rank" style="background:linear-gradient(135deg, #ff2d55 0%, #d97706 100%); color:#ffffff; font-size:12.5px; font-weight:900; padding:4px 13px; border-radius:999px; letter-spacing:0.4px; text-transform:uppercase; box-shadow:0 3px 12px rgba(255,45,85,0.45); display:inline-flex; align-items:center; gap:5px;">
						🏆 TOP 1
					</span>
				</div>

				<!-- Badge Row (Tag + Genre) -->
				<div class="reel-hero-badges" style="display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
					<span id="reel-hero-badge-tag" style="background:var(--theme-accent, #e11d48); color:#fff; font-size:12px; font-weight:800; padding:4px 12px; border-radius:999px; letter-spacing:0.3px; text-transform:uppercase;">
						<?php echo esc_html( $active_hero['tag'] ); ?>
					</span>
					<span id="reel-hero-badge-trope" style="background:rgba(255,255,255,0.18); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); color:#f8fafc; font-size:12px; font-weight:700; padding:4px 12px; border-radius:999px;">
						<?php echo esc_html( $active_hero['trope'] ); ?>
					</span>
				</div>

				<h1 id="reel-hero-title" style="font-size:clamp(2.1rem, 4.2vw, 3.4rem); font-weight:900; line-height:1.15; margin:0 0 14px 0; color:#ffffff; text-shadow:0 3px 15px rgba(0,0,0,0.9); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
					<?php echo esc_html( $active_hero['title'] ); ?>
				</h1>

				<!-- Meta Badges: Rating, Total Episodes, Views -->
				<div id="reel-hero-meta-row" style="display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:nowrap;">
					<div id="reel-hero-rating-badge" style="display:inline-flex; align-items:center; gap:4px; background:rgba(255,255,255,0.12); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); color:#ffc107; font-size:12px; font-weight:800; padding:3px 9px; border-radius:6px; border:none;">
						<span style="font-size:11px;">★</span>
						<span id="reel-hero-rating-val" style="color:#ffffff;"><?php echo ! empty( $active_hero['rating_val'] ) ? number_format( (float) $active_hero['rating_val'], 1 ) : '4.9'; ?></span>
					</div>
					<div id="reel-hero-episodes-badge" style="display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,0.12); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); color:#f8fafc; font-size:12px; font-weight:700; padding:3px 10px; border-radius:6px; border:none;">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg>
						<span id="reel-hero-episodes-val"><?php echo (int) ( $active_hero['episodes_count'] ?? 1 ); ?> <?php echo ( (int) ( $active_hero['episodes_count'] ?? 1 ) === 1 ) ? 'Episode' : 'Episodes'; ?></span>
					</div>
					<div id="reel-hero-views-badge" style="display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,0.12); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); color:#cbd5e1; font-size:12px; font-weight:700; padding:3px 10px; border-radius:6px; border:none;">
						<svg width="11" height="11" viewBox="0 0 24 24" fill="#ffffff"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
						<span id="reel-hero-views-val"><?php echo esc_html( $active_hero['views_str'] ?? '0' ); ?> Views</span>
					</div>
				</div>

				<p id="reel-hero-overview" style="font-size:14.5px; color:#cbd5e1; line-height:1.65; margin:0 0 22px 0; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; text-shadow:0 2px 8px rgba(0,0,0,0.8);">
					<?php echo esc_html( $active_hero['overview'] ); ?>
				</p>

				<div class="reel-hero-actions-row" style="display:flex; align-items:center; justify-content:space-between; gap:16px;">
					<div class="reel-hero-btn-group" style="display:inline-flex; align-items:center; gap:12px; flex-wrap:wrap;">
						<a href="<?php echo esc_url( $active_hero['watch_url'] ); ?>" id="reel-hero-play-link" class="btn-reel-hero-play" style="display:inline-flex; align-items:center; gap:10px; background:#ffffff; color:#000000; font-size:16px; font-weight:800; padding:13px 36px; border-radius:999px; text-decoration:none; box-shadow:0 8px 25px rgba(0,0,0,0.6); transition:transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), background 0.2s ease;">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="#000000"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
							<span>Play</span>
						</a>

						<button type="button" id="btn-hero-my-list" class="btn-reel-hero-mylist" aria-label="Save to My List" data-id="<?php echo esc_attr( $active_hero['post_id'] ?? $active_hero['id'] ); ?>" data-title="<?php echo esc_attr( $active_hero['title'] ); ?>" data-poster="<?php echo esc_url( $active_hero['poster'] ); ?>" data-overview="<?php echo esc_attr( $active_hero['overview'] ?? '' ); ?>" data-url="<?php echo esc_url( $active_hero['watch_url'] ); ?>">
							<svg class="icon-hero-plus" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
							<svg class="icon-hero-check" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><polyline points="20 6 9 17 4 12"></polyline></svg>
							<span class="hero-mylist-label">My List</span>
						</button>
					</div>

					<div class="reel-hero-dots-wrap" id="reel-hero-dots">
						<?php foreach ( $hero_items as $d_idx => $d_item ) : ?>
							<button type="button" class="reel-hero-dot <?php echo $d_idx === 0 ? 'active' : ''; ?>" data-index="<?php echo $d_idx; ?>" aria-label="Slide <?php echo $d_idx + 1; ?>"></button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Right Column: Interactive 5-Thumbnail Rail Switcher -->
			<div class="reel-hero-rail-col" style="flex:0 0 auto; max-width:100%;">
				<div class="reel-thumb-track" style="display:flex; align-items:center; gap:12px;">
					<?php foreach ( $hero_items as $h_idx => $h_item ) : 
						$is_active_thumb = ( $h_idx === 0 );
					?>
						<div class="reel-hero-thumb-item <?php echo $is_active_thumb ? 'active-thumb' : ''; ?>"
							data-index="<?php echo $h_idx; ?>"
							data-id="<?php echo esc_attr( $h_item['post_id'] ?? $h_item['id'] ); ?>"
							data-title="<?php echo esc_attr( $h_item['title'] ); ?>"
							data-tag="<?php echo esc_attr( $h_item['tag'] ); ?>"
							data-trope="<?php echo esc_attr( $h_item['trope'] ); ?>"
							data-rating="<?php echo esc_attr( ! empty( $h_item['rating_val'] ) ? number_format( (float) $h_item['rating_val'], 1 ) : '4.9' ); ?>"
							data-episodes="<?php echo esc_attr( (int) ( $h_item['episodes_count'] ?? 1 ) ); ?>"
							data-views="<?php echo esc_attr( $h_item['views_str'] ?? '0' ); ?>"
							data-overview="<?php echo esc_attr( $h_item['overview'] ); ?>"
							data-poster="<?php echo esc_url( $h_item['poster'] ); ?>"
							data-backdrop="<?php echo esc_url( $h_item['backdrop'] ); ?>"
							data-url="<?php echo esc_url( $h_item['watch_url'] ); ?>"
							style="width:96px; height:136px; border-radius:10px; overflow:hidden; position:relative; cursor:pointer; flex-shrink:0; border:2px solid <?php echo $is_active_thumb ? '#ffffff' : 'rgba(255,255,255,0.18)'; ?>; transition:all 0.25s cubic-bezier(0.16, 1, 0.3, 1); box-shadow:<?php echo $is_active_thumb ? '0 0 16px rgba(255,255,255,0.7)' : '0 4px 12px rgba(0,0,0,0.5)'; ?>; transform:<?php echo $is_active_thumb ? 'scale(1.06)' : 'scale(1)'; ?>;">
							<img src="<?php echo esc_url( $h_item['poster'] ); ?>" alt="<?php echo esc_attr( $h_item['title'] ); ?>" style="width:100%; height:100%; object-fit:cover;" loading="lazy">
						</div>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
	</section>
	<?php endif; ?>

	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<!-- 2. CONTENT ROWS (HORIZONTAL CAROUSEL FEEDS)                                 -->
	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<div class="short-content-rows" style="max-width:1800px; margin:0 auto; padding:0 28px;">
		<?php foreach ( $sections_to_render as $sec ) :
			if ( isset( $sec['enabled'] ) && empty( $sec['enabled'] ) ) {
				continue;
			}
			// If this section is Hero Slider, it is rendered in the top billboard above
			if ( ( $sec['layout'] ?? '' ) === 'hero' ) {
				continue;
			}
			$row_dramas = $fetch_popular_section( $sec, $unique_dramas );
			if ( empty( $row_dramas ) ) {
				continue;
			}
			$row_layout = $sec['layout'] ?? 'portrait';
			$row_title  = $sec['title'] ?? __( 'Short Dramas', 'short-stream' );
			$row_type   = $sec['type'] ?? '';
			$row_ep     = $sec['endpoint'] ?? '';
			$view_all_url = home_url( '/genre/' );
			if ( 'vip' === $row_type || 'vip' === $row_ep ) {
				$view_all_url = home_url( '/genre/?sort=vip' );
			} elseif ( 0 === strpos( $row_ep, 'genre/' ) ) {
				$view_all_url = home_url( '/' . trim( $row_ep, '/' ) . '/' );
			}
		?>
		<?php if ( 'leaderboard' === $row_layout ) : 
			$top3 = array_slice( $row_dramas, 0, 3 );
			$rest = array_slice( $row_dramas, 3 );
			$podium_ranks = array(
				0 => array( 'rank' => 1, 'label' => '👑 TOP 1', 'badge_bg' => 'linear-gradient(135deg, #ffd700 0%, #f59e0b 100%)', 'badge_color' => '#000000', 'border' => '1.5px solid rgba(255,215,0,0.55)', 'glow' => '0 8px 24px rgba(245,158,11,0.22)' ),
				1 => array( 'rank' => 2, 'label' => '🥈 TOP 2', 'badge_bg' => 'linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%)', 'badge_color' => '#0f172a', 'border' => '1.5px solid rgba(226,232,240,0.3)', 'glow' => '0 6px 18px rgba(0,0,0,0.35)' ),
				2 => array( 'rank' => 3, 'label' => '🥉 TOP 3', 'badge_bg' => 'linear-gradient(135deg, #d97706 0%, #92400e 100%)', 'badge_color' => '#ffffff', 'border' => '1.5px solid rgba(217,119,6,0.35)', 'glow' => '0 6px 18px rgba(0,0,0,0.35)' ),
			);
		?>
		<section class="reel-row-wrapper reel-leaderboard-section" id="leaderboard" data-section-type="<?php echo esc_attr( $row_type ); ?>" style="margin-bottom:44px; position:relative;">
			<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
				<div>
					<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0 0 3px 0; display:flex; align-items:center; gap:8px;">
						<span style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:linear-gradient(135deg, #ffd700 0%, #f59e0b 100%); border-radius:7px; box-shadow:0 2px 8px rgba(245,158,11,0.45); font-size:14px;">
							🏆
						</span>
						<span><?php echo esc_html( $row_title ); ?></span>
					</h2>
					<p style="margin:0; font-size:12px; color:#94a3b8; font-weight:600;">Top Ranked &amp; Most Popular Drama Leaderboard</p>
				</div>
				<a href="<?php echo esc_url( $view_all_url ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
					<span><?php esc_html_e( 'View all', 'short-stream' ); ?></span>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</a>
			</div>

			<!-- Top 3 Podium Spotlight -->
			<div class="reel-leaderboard-podium-grid">
				<?php foreach ( $top3 as $p_idx => $p_drama ) : 
					$meta = $podium_ranks[ $p_idx ] ?? $podium_ranks[2];
				?>
					<div class="reel-podium-card rank-<?php echo $meta['rank']; ?>" style="<?php echo $meta['border'] ? 'border:' . $meta['border'] . ';' : ''; ?> box-shadow:<?php echo $meta['glow']; ?>;">
						<a href="<?php echo esc_url( $p_drama['watch_url'] ); ?>" class="reel-podium-link">
							<div class="reel-podium-poster-wrap">
								<img src="<?php echo esc_url( $p_drama['poster'] ); ?>" alt="<?php echo esc_attr( $p_drama['title'] ); ?>" loading="lazy">
								<div class="reel-podium-rank-badge" style="background:<?php echo $meta['badge_bg']; ?>; color:<?php echo $meta['badge_color']; ?>;">
									<?php echo esc_html( $meta['label'] ); ?>
								</div>
								<div class="reel-podium-play-btn">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="#ffffff"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
								</div>
								<div class="reel-podium-score-pill">
									★ <?php echo number_format( (float) $p_drama['rating_val'], 1 ); ?>
								</div>
							</div>
							<div class="reel-podium-info">
								<div class="reel-podium-meta-top">
									<span class="reel-podium-genre"><?php echo esc_html( $p_drama['trope'] ?: ( $p_drama['genres'][0] ?? 'Drama' ) ); ?></span>
									<span class="reel-podium-eps"><?php echo (int) $p_drama['episodes_count']; ?> Eps</span>
								</div>
								<h3 class="reel-podium-title"><?php echo esc_html( $p_drama['title'] ); ?></h3>
								<div class="reel-podium-stats">
									<span class="reel-podium-heat">🔥 <?php echo esc_html( $p_drama['views_str'] ); ?> views</span>
								</div>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Ranked Board List (Ranks 4+) -->
			<?php if ( ! empty( $rest ) ) : ?>
			<div class="reel-leaderboard-ranked-list">
				<?php foreach ( $rest as $r_idx => $r_drama ) : 
					$cur_rank = $r_idx + 4;
				?>
					<a href="<?php echo esc_url( $r_drama['watch_url'] ); ?>" class="reel-ranked-row-item">
						<div class="reel-ranked-num"><?php echo $cur_rank; ?></div>
						<div class="reel-ranked-thumb">
							<img src="<?php echo esc_url( $r_drama['poster'] ); ?>" alt="<?php echo esc_attr( $r_drama['title'] ); ?>" loading="lazy">
						</div>
						<div class="reel-ranked-content">
							<h4 class="reel-ranked-title"><?php echo esc_html( $r_drama['title'] ); ?></h4>
							<div class="reel-ranked-sub">
								<span class="reel-ranked-tag"><?php echo esc_html( $r_drama['trope'] ?: ( $r_drama['genres'][0] ?? 'Drama' ) ); ?></span>
								<span class="reel-ranked-views">🔥 <?php echo esc_html( $r_drama['views_str'] ); ?></span>
								<span class="reel-ranked-score">★ <?php echo number_format( (float) $r_drama['rating_val'], 1 ); ?></span>
							</div>
						</div>
						<div class="reel-ranked-action">
							<span class="reel-ranked-play-btn">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
								<span>Play</span>
							</span>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</section>
		<?php else : ?>
			<section class="reel-row-wrapper" style="margin-bottom:44px; position:relative;">
				<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
					<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
						<span><?php echo esc_html( $row_title ); ?></span>
					</h2>
					<a href="<?php echo esc_url( $view_all_url ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
						<span><?php esc_html_e( 'View all', 'short-stream' ); ?></span>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</a>
				</div>

				<div class="reel-carousel-container" style="position:relative;">
					<!-- Track Navigation Arrow Left -->
					<button type="button" class="reel-track-arrow prev" aria-label="Previous">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>

					<div class="reel-cards-track" style="display:flex; <?php echo ( 'top10' === $row_layout ) ? 'gap:22px;' : 'gap:18px;'; ?> overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
						<?php foreach ( $row_dramas as $idx => $drama ) : 
							$rank_num = $idx + 1;
						?>
							<div class="reel-card-item" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>" style="flex:0 0 186px; width:186px;">
								<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" style="text-decoration:none; color:inherit; display:block;">
									<!-- Vertical Poster Container (identical to homepage) -->
									<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5); transition:transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;">
										<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s ease;" loading="lazy">
										
										<?php if ( 'top10' === $row_layout ) : ?>
											<!-- Giant Stylized Rank Number -->
											<div class="reel-rank-overlay" style="position:absolute; bottom:-10px; left:4px; z-index:3; font-size:62px; font-weight:900; line-height:1; font-family:'Impact', 'Arial Black', sans-serif; font-style:italic; color:<?php echo ( $rank_num === 1 ) ? '#f59e0b' : ( ( $rank_num === 2 ) ? '#94a3b8' : ( ( $rank_num === 3 ) ? '#d97706' : '#ffffff' ) ); ?>; text-shadow:0 4px 14px rgba(0,0,0,0.95), 0 0 2px rgba(0,0,0,0.8); pointer-events:none;">
												<?php echo $rank_num; ?>
											</div>
										<?php else : ?>
											<!-- Top-Left Badge -->
											<?php if ( ! empty( $drama['is_vip'] ) ) : ?>
												<div class="reel-card-badge-left" style="position:absolute; top:8px; left:8px; z-index:2;">
													<span class="reel-poster-badge is-vip-badge" style="background:linear-gradient(135deg, #ff2d55 0%, #d97706 100%); color:#ffffff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.4px; display:inline-flex; align-items:center; gap:3px; box-shadow:0 2px 8px rgba(0,0,0,0.5);">
														👑 VIP
													</span>
												</div>
											<?php elseif ( ! empty( $drama['tag'] ) ) : ?>
												<div class="reel-card-badge-left" style="position:absolute; top:8px; left:8px; z-index:2;">
													<span class="reel-poster-badge" style="background:#e11d48; color:#fff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.3px;">
														<?php echo esc_html( $drama['tag'] ); ?>
													</span>
												</div>
											<?php endif; ?>
										<?php endif; ?>

										<!-- Top-Right Rating Badge -->
										<?php if ( ! empty( $drama['rating_val'] ) && (float) $drama['rating_val'] > 0 ) : ?>
											<div class="reel-card-rating" style="position:absolute; top:8px; right:8px; z-index:2; display:flex; align-items:center; gap:3px; background:rgba(0,0,0,0.7); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); color:#ffc107; font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; border:none;">
												<span style="font-size:9.5px; line-height:1;">★</span>
												<span style="color:#ffffff; line-height:1;"><?php echo number_format( (float) $drama['rating_val'], 1 ); ?></span>
											</div>
										<?php endif; ?>

										<!-- Bottom-Right View Count Badge -->
										<?php if ( ! empty( $drama['views_num'] ) && (int) $drama['views_num'] > 0 ) : ?>
											<div style="position:absolute; bottom:8px; right:8px; z-index:2; display:flex; align-items:center; gap:4px; background:rgba(0,0,0,0.65); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); color:#fff; font-size:11px; font-weight:800; padding:2px 7px; border-radius:999px;">
												<svg width="10" height="10" viewBox="0 0 24 24" fill="#ffffff"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
												<span class="reel-card-views"><?php echo esc_html( $drama['views_str'] ); ?></span>
											</div>
										<?php endif; ?>
									</div>

									<!-- Title Under Poster -->
									<h3 style="font-size:14px; font-weight:800; color:#f1f5f9; margin:10px 0 4px 0; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
										<?php echo esc_html( $drama['title'] ); ?>
									</h3>

									<!-- Sub-line Metadata -->
									<div style="font-size:11.5px; color:#94a3b8; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
										<?php echo esc_html( $drama['subline'] ); ?>
									</div>
								</a>
							</div>
						<?php endforeach; ?>
					</div>

					<!-- Track Navigation Arrow Right -->
					<button type="button" class="reel-track-arrow next" aria-label="Next">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
				</div>
			</section>
		<?php endif; ?>
		<?php endforeach; ?>

		<?php if ( empty( $unique_dramas ) ) : ?>
			<div style="text-align:center; padding: 80px 20px; color:#94a3b8;">
				<div style="font-size:48px; margin-bottom:14px;">🚀</div>
				<h2 style="color:#ffffff; font-size:22px; font-weight:800; margin-bottom:8px;"><?php esc_html_e( 'No Dramas Available Yet', 'short-stream' ); ?></h2>
				<p style="font-size:14px; max-width:420px; margin:0 auto 20px auto; line-height:1.6; color:#94a3b8;">
					<?php esc_html_e( 'New releases and trending dramas will appear here once episodes are published.', 'short-stream' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// 1. Hero Showcase Carousel Logic
	var heroDramas = <?php echo json_encode( array_values( $hero_items ) ); ?>;
	var currentHeroIdx = 0;
	var autoSlideInterval = null;

	var $heroBg = document.getElementById('reel-hero-bg');
	var $heroRank = document.getElementById('reel-hero-badge-rank');
	var $heroTag = document.getElementById('reel-hero-badge-tag');
	var $heroTrope = document.getElementById('reel-hero-badge-trope');
	var $heroTitle = document.getElementById('reel-hero-title');
	var $heroRatingVal = document.getElementById('reel-hero-rating-val');
	var $heroEpisodesVal = document.getElementById('reel-hero-episodes-val');
	var $heroViewsVal = document.getElementById('reel-hero-views-val');
	var $heroOverview = document.getElementById('reel-hero-overview');
	var $heroPlayLink = document.getElementById('reel-hero-play-link');
	var $heroDots = document.querySelectorAll('.reel-hero-dot');
	var $heroThumbs = document.querySelectorAll('.reel-hero-thumb-item');

	function setHeroSlide(idx) {
		if (!heroDramas || heroDramas.length === 0) return;
		currentHeroIdx = (idx + heroDramas.length) % heroDramas.length;
		var drama = heroDramas[currentHeroIdx];
		if (!drama) return;

		if ($heroBg) {
			$heroBg.style.backgroundImage = "url('" + drama.backdrop + "')";
		}
		if ($heroRank) $heroRank.innerHTML = '🏆 TOP ' + (currentHeroIdx + 1);
		if ($heroTag) $heroTag.textContent = drama.tag || 'Trending';
		if ($heroTrope) $heroTrope.textContent = drama.trope || 'Short Drama';
		if ($heroTitle) $heroTitle.textContent = drama.title || '';
		if ($heroRatingVal) {
			$heroRatingVal.textContent = drama.rating_val ? Number(drama.rating_val).toFixed(1) : '4.9';
		}
		if ($heroEpisodesVal) {
			var eps = parseInt(drama.episodes_count || 1, 10);
			$heroEpisodesVal.textContent = eps + (eps === 1 ? ' Episode' : ' Episodes');
		}
		if ($heroViewsVal) {
			$heroViewsVal.textContent = (drama.views_str || '0') + ' Views';
		}
		if ($heroOverview) $heroOverview.textContent = drama.overview || '';
		if ($heroPlayLink) $heroPlayLink.href = drama.watch_url || '#';

		var dramaId = String(drama.post_id || drama.id || '');
		var $heroMyListBtn = document.getElementById('btn-hero-my-list');
		if ($heroMyListBtn && dramaId) {
			$heroMyListBtn.setAttribute('data-id', dramaId);
			$heroMyListBtn.setAttribute('data-title', drama.title || '');
			$heroMyListBtn.setAttribute('data-poster', drama.poster || '');
			$heroMyListBtn.setAttribute('data-overview', drama.overview || '');
			$heroMyListBtn.setAttribute('data-url', drama.watch_url || '#');
			updateHeroMyListBtnState(dramaId);
		}

		if ($heroDots) {
			$heroDots.forEach(function(dot, dIdx) {
				if (dIdx === currentHeroIdx) {
					dot.classList.add('active');
				} else {
					dot.classList.remove('active');
				}
			});
		}

		if ($heroThumbs) {
			$heroThumbs.forEach(function(thumb, tIdx) {
				if (tIdx === currentHeroIdx) {
					thumb.classList.add('active-thumb');
					thumb.style.borderColor = '#ffffff';
					thumb.style.boxShadow = '0 0 16px rgba(255,255,255,0.7)';
					thumb.style.transform = 'scale(1.06)';
				} else {
					thumb.classList.remove('active-thumb');
					thumb.style.borderColor = 'rgba(255,255,255,0.18)';
					thumb.style.boxShadow = '0 4px 12px rgba(0,0,0,0.5)';
					thumb.style.transform = 'scale(1)';
				}
			});
		}
	}

	function isDramaInMyList(dramaId) {
		if (!dramaId) return false;
		dramaId = String(dramaId);
		try {
			for (var k in localStorage) {
				if (k.startsWith('short_my_list') || k.startsWith('short_watchlist') || k === 'stv_watchlist_guest') {
					var list = JSON.parse(localStorage.getItem(k) || '{}');
					if (list && list[dramaId]) return true;
				}
			}
		} catch(e){}
		return false;
	}

	function updateHeroMyListBtnState(dramaId) {
		var btn = document.getElementById('btn-hero-my-list');
		if (!btn) return;
		var saved = isDramaInMyList(dramaId);
		var lbl = btn.querySelector('.hero-mylist-label');
		if (saved) {
			btn.classList.add('is-saved');
			if (lbl) lbl.textContent = 'Saved';
		} else {
			btn.classList.remove('is-saved');
			if (lbl) lbl.textContent = 'My List';
		}
	}

	var $heroMyListBtn = document.getElementById('btn-hero-my-list');
	if ($heroMyListBtn) {
		var initId = $heroMyListBtn.getAttribute('data-id');
		if (initId) updateHeroMyListBtnState(initId);

		$heroMyListBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var dId = this.getAttribute('data-id');
			if (!dId) return;
			var dTitle = this.getAttribute('data-title') || '';
			var dPoster = this.getAttribute('data-poster') || '';
			var dOverview = this.getAttribute('data-overview') || '';
			var dUrl = this.getAttribute('data-url') || '#';

			var isSaved = isDramaInMyList(dId);
			var uid = (window.firebaseUser && window.firebaseUser.uid) ? window.firebaseUser.uid : 'guest';
			var storageKey = 'short_my_list_' + uid;

			var itemData = {
				id: dId,
				title: dTitle,
				poster: dPoster,
				overview: dOverview,
				watchUrl: dUrl,
				savedAt: Date.now()
			};

			try {
				if (isSaved) {
					// Remove from all local lists
					for (var k in localStorage) {
						if (k.startsWith('short_my_list') || k.startsWith('short_watchlist') || k === 'stv_watchlist_guest') {
							var l = JSON.parse(localStorage.getItem(k) || '{}');
							if (l[dId]) {
								delete l[dId];
								localStorage.setItem(k, JSON.stringify(l));
							}
						}
					}
					// Remove from Firebase if user is logged in
					var currentUid = (window.firebaseUser && window.firebaseUser.uid) || uid;
					if (currentUid) {
						if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) {
							try {
								firebase.database().ref('users/' + currentUid + '/watchlist/' + dId).remove();
								firebase.database().ref('users/' + currentUid + '/my_list/' + dId).remove();
							} catch(e){}
						}
						var rtdbUrl = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
						fetch(rtdbUrl + '/users/' + currentUid + '/watchlist/' + dId + '.json', { method: 'DELETE' }).catch(function(){});
						fetch(rtdbUrl + '/users/' + currentUid + '/my_list/' + dId + '.json', { method: 'DELETE' }).catch(function(){});
					}
					updateHeroMyListBtnState(dId);
				} else {
					// Add to local lists
					var list = JSON.parse(localStorage.getItem(storageKey) || '{}');
					list[dId] = itemData;
					localStorage.setItem(storageKey, JSON.stringify(list));
					localStorage.setItem('short_watchlist_' + uid, JSON.stringify(list));
					localStorage.setItem('short_my_list_' + uid, JSON.stringify(list));

					// Save to Firebase RTDB if user is logged in
					var currentUid = (window.firebaseUser && window.firebaseUser.uid) || uid;
					if (currentUid) {
						if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) {
							try {
								firebase.database().ref('users/' + currentUid + '/watchlist/' + dId).set(itemData);
								firebase.database().ref('users/' + currentUid + '/my_list/' + dId).set(itemData);
							} catch(e){}
						}
						var rtdbUrl = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
						fetch(rtdbUrl + '/users/' + currentUid + '/watchlist/' + dId + '.json', {
							method: 'PUT',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(itemData)
						}).catch(function(){});
						fetch(rtdbUrl + '/users/' + currentUid + '/my_list/' + dId + '.json', {
							method: 'PUT',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(itemData)
						}).catch(function(){});
					}
					updateHeroMyListBtnState(dId);
				}
			} catch(err){}
		});
	}

	function startAutoSlide() {
		stopAutoSlide();
		if (heroDramas && heroDramas.length > 1) {
			autoSlideInterval = setInterval(function() {
				setHeroSlide(currentHeroIdx + 1);
			}, 6000);
		}
	}

	function stopAutoSlide() {
		if (autoSlideInterval) clearInterval(autoSlideInterval);
	}

	var $btnPrev = document.getElementById('btn-hero-prev-side');
	var $btnNext = document.getElementById('btn-hero-next-side');

	if ($btnPrev) {
		$btnPrev.addEventListener('click', function() {
			setHeroSlide(currentHeroIdx - 1);
			startAutoSlide();
		});
	}
	if ($btnNext) {
		$btnNext.addEventListener('click', function() {
			setHeroSlide(currentHeroIdx + 1);
			startAutoSlide();
		});
	}

	if ($heroDots) {
		$heroDots.forEach(function(dot) {
			dot.addEventListener('click', function() {
				var idx = parseInt(this.getAttribute('data-index'), 10) || 0;
				setHeroSlide(idx);
				startAutoSlide();
			});
		});
	}

	if ($heroThumbs) {
		$heroThumbs.forEach(function(thumb) {
			thumb.addEventListener('click', function() {
				var idx = parseInt(this.getAttribute('data-index'), 10) || 0;
				setHeroSlide(idx);
				startAutoSlide();
			});
		});
	}

	startAutoSlide();

	// 2. Content Row Track Navigation Arrows
	function updateTrackArrowsVisibility(track, prevBtn, nextBtn) {
		if (!track) return;
		var maxScroll = track.scrollWidth - track.clientWidth;
		if (maxScroll <= 5) {
			if (prevBtn) prevBtn.classList.add('is-disabled');
			if (nextBtn) nextBtn.classList.add('is-disabled');
			return;
		}
		if (prevBtn) {
			if (track.scrollLeft <= 10) {
				prevBtn.classList.add('is-disabled');
			} else {
				prevBtn.classList.remove('is-disabled');
			}
		}
		if (nextBtn) {
			if (track.scrollLeft >= maxScroll - 10) {
				nextBtn.classList.add('is-disabled');
			} else {
				nextBtn.classList.remove('is-disabled');
			}
		}
	}

	document.querySelectorAll('.reel-carousel-container').forEach(function(container) {
		var track = container.querySelector('.reel-cards-track');
		var prevBtn = container.querySelector('.reel-track-arrow.prev');
		var nextBtn = container.querySelector('.reel-track-arrow.next');

		if (prevBtn && track) {
			prevBtn.addEventListener('click', function(e) {
				e.preventDefault();
				track.scrollBy({ left: -400, behavior: 'smooth' });
			});
		}

		if (nextBtn && track) {
			nextBtn.addEventListener('click', function(e) {
				e.preventDefault();
				track.scrollBy({ left: 400, behavior: 'smooth' });
			});
		}

		if (track) {
			track.addEventListener('scroll', function() {
				updateTrackArrowsVisibility(track, prevBtn, nextBtn);
			}, { passive: true });
			// Initialize
			updateTrackArrowsVisibility(track, prevBtn, nextBtn);
		}
	});

	window.addEventListener('resize', function() {
		document.querySelectorAll('.reel-carousel-container').forEach(function(container) {
			var track = container.querySelector('.reel-cards-track');
			var prevBtn = container.querySelector('.reel-track-arrow.prev');
			var nextBtn = container.querySelector('.reel-track-arrow.next');
			updateTrackArrowsVisibility(track, prevBtn, nextBtn);
		});
	});
});
</script>

<style>
/* ══════════════════════════════════════════════════════════════ */
/* POPULAR PAGE HERO & CAROUSEL STYLES                            */
/* ══════════════════════════════════════════════════════════════ */
.reel-hero-showcase {
	padding-top: 110px;
	padding-bottom: 95px !important;
}
body.admin-bar .reel-hero-showcase {
	padding-top: 145px;
	padding-bottom: 95px !important;
}

.reel-hero-side-arrow {
	position: absolute;
	top: 50%;
	transform: translateY(-50%);
	width: 52px;
	height: 52px;
	border-radius: 50%;
	background: transparent !important;
	border: none !important;
	color: #ffffff;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	z-index: 15;
	filter: drop-shadow(0 3px 12px rgba(0, 0, 0, 0.95));
	transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), color 0.18s ease, opacity 0.18s ease;
	opacity: 0.9;
	padding: 0;
}
.reel-hero-side-arrow.prev { left: 14px; }
.reel-hero-side-arrow.next { right: 14px; }
.reel-hero-side-arrow:hover {
	color: #ffffff;
	transform: translateY(-50%) scale(1.22);
	opacity: 1;
}

.reel-hero-dots-wrap {
	display: none;
	align-items: center;
	gap: 5px;
}
.reel-hero-dot {
	width: 6px;
	height: 6px;
	border-radius: 999px;
	background: rgba(255, 255, 255, 0.35);
	border: none;
	padding: 0;
	cursor: pointer;
	transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.reel-hero-dot:hover {
	background: rgba(255, 255, 255, 0.7);
}
.reel-hero-dot.active {
	width: 18px;
	background: var(--theme-accent, #e11d48);
	box-shadow: 0 0 10px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.6);
}

.btn-reel-hero-play {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
	background: #ffffff;
	color: #000000;
	font-size: 16px;
	font-weight: 800;
	height: 48px;
	padding: 0 32px;
	border-radius: 999px;
	text-decoration: none;
	box-shadow: 0 8px 25px rgba(0, 0, 0, 0.6);
	transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), background 0.2s ease;
	box-sizing: border-box;
}
.btn-reel-hero-play:hover {
	transform: scale(1.05) !important;
	background: #f1f5f9 !important;
}

.btn-reel-hero-mylist {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	background: rgba(255, 255, 255, 0.16);
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
	color: #ffffff;
	border: none;
	font-size: 16px;
	font-weight: 700;
	height: 48px;
	padding: 0 28px;
	border-radius: 999px;
	cursor: pointer;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
	transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
	outline: none;
	text-decoration: none;
	box-sizing: border-box;
}
.btn-reel-hero-mylist:hover {
	background: rgba(255, 255, 255, 0.26);
	color: #ffffff;
	transform: scale(1.04);
}
.btn-reel-hero-mylist:active {
	transform: scale(0.96);
}
.btn-reel-hero-mylist.is-saved {
	background: rgba(255, 255, 255, 0.16);
	border: none;
	color: #ffffff;
	box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}
.btn-reel-hero-mylist.is-saved:hover {
	background: rgba(255, 255, 255, 0.26);
}
.btn-reel-hero-mylist.is-saved .icon-hero-plus {
	display: none !important;
}
.btn-reel-hero-mylist.is-saved .icon-hero-check {
	display: inline-block !important;
	stroke: #22c55e !important;
}

.reel-hero-thumb-item:hover {
	border-color: #ffffff !important;
	transform: scale(1.04) !important;
}

/* Card Sizing & Interactions */
.reel-poster-box {
	aspect-ratio: 2 / 3 !important;
}
.reel-card-item:hover .reel-poster-box {
	transform: translateY(-5px);
	box-shadow: 0 14px 28px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.22);
}
.reel-card-item:hover .reel-poster-box img {
	transform: scale(1.05);
}
.reel-view-all:hover {
	color: #ffffff !important;
}

/* Carousel Track Arrows (Pure Minimalist, Zero Border, Zero Background) */
.reel-track-arrow {
	position: absolute;
	top: 45% !important;
	transform: translateY(-50%) !important;
	width: 52px !important;
	height: 52px !important;
	background: transparent !important;
	border: none !important;
	box-shadow: none !important;
	color: #ffffff !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	z-index: 15 !important;
	padding: 0 !important;
	transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.reel-track-arrow svg {
	width: 36px !important;
	height: 36px !important;
	stroke-width: 3.2 !important;
	stroke: #ffffff !important;
	filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 6px rgba(0, 0, 0, 0.85));
	transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), stroke 0.2s ease, filter 0.2s ease !important;
}
.reel-track-arrow.prev { left: -26px !important; right: auto !important; }
.reel-track-arrow.next { right: -26px !important; left: auto !important; }
.reel-track-arrow:hover {
	background: transparent !important;
	border: none !important;
	box-shadow: none !important;
	color: #ffffff !important;
	transform: translateY(-50%) scale(1.22) !important;
}
.reel-track-arrow:hover svg {
	stroke: var(--theme-accent, #ff2d55) !important;
	filter: drop-shadow(0 0 14px rgba(255, 45, 85, 0.85)) drop-shadow(0 2px 8px rgba(0, 0, 0, 0.95));
}
.reel-track-arrow.prev:hover svg {
	transform: translateX(-4px);
}
.reel-track-arrow.next:hover svg {
	transform: translateX(4px);
}
/* Leaderboard Section Styles */
.reel-leaderboard-section {
	width: 100%;
	box-sizing: border-box;
}
.reel-leaderboard-podium-grid {
	display: grid;
	grid-template-columns: repeat(3, minmax(0, 1fr));
	gap: 16px;
	margin-bottom: 20px;
}
.reel-podium-card {
	background: rgba(24, 26, 32, 0.85);
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
	border-radius: 14px;
	overflow: hidden;
	transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease;
	position: relative;
	box-sizing: border-box;
}
.reel-podium-card:hover {
	transform: translateY(-4px) scale(1.01);
}
.reel-podium-link {
	display: flex;
	flex-direction: column;
	text-decoration: none;
	color: inherit;
	height: 100%;
}
.reel-podium-poster-wrap {
	position: relative;
	aspect-ratio: 16 / 9;
	overflow: hidden;
	background: #111;
}
.reel-podium-poster-wrap img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
	transition: transform 0.3s ease;
}
.reel-podium-card:hover .reel-podium-poster-wrap img {
	transform: scale(1.06);
}
.reel-podium-rank-badge {
	position: absolute;
	top: 8px;
	left: 8px;
	z-index: 3;
	font-size: 10px;
	font-weight: 900;
	padding: 3px 8px;
	border-radius: 999px;
	letter-spacing: 0.4px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
}
.reel-podium-score-pill {
	position: absolute;
	top: 8px;
	right: 8px;
	z-index: 3;
	font-size: 10px;
	font-weight: 800;
	padding: 2.5px 7px;
	border-radius: 6px;
	background: rgba(0, 0, 0, 0.7);
	backdrop-filter: blur(6px);
	color: #ffc107;
	border: 1px solid rgba(255, 255, 255, 0.12);
}
.reel-podium-play-btn {
	position: absolute;
	bottom: 8px;
	right: 8px;
	z-index: 3;
	width: 32px;
	height: 32px;
	border-radius: 50%;
	background: var(--theme-accent, #e11d48);
	display: flex;
	align-items: center;
	justify-content: center;
	box-shadow: 0 4px 12px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.6);
	transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.reel-podium-card:hover .reel-podium-play-btn {
	transform: scale(1.15);
}
.reel-podium-info {
	padding: 12px 14px;
	display: flex;
	flex-direction: column;
	flex: 1;
}
.reel-podium-meta-top {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 11px;
	font-weight: 700;
	color: #94a3b8;
	margin-bottom: 4px;
}
.reel-podium-genre {
	color: var(--theme-accent, #e11d48);
	font-weight: 800;
	text-transform: uppercase;
	font-size: 10.5px;
	letter-spacing: 0.3px;
}
.reel-podium-title {
	font-size: 15px;
	font-weight: 800;
	color: #ffffff;
	margin: 0 0 6px 0;
	line-height: 1.3;
	display: -webkit-box;
	-webkit-line-clamp: 1;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
.reel-podium-stats {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 11.5px;
	font-weight: 700;
	color: #cbd5e1;
	margin-top: auto;
}
.reel-leaderboard-ranked-list {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 10px;
}
.reel-ranked-row-item {
	display: flex;
	align-items: center;
	gap: 12px;
	background: rgba(255, 255, 255, 0.035);
	border: 1px solid rgba(255, 255, 255, 0.07);
	border-radius: 10px;
	padding: 8px 12px;
	text-decoration: none;
	color: inherit;
	transition: all 0.15s ease;
	box-sizing: border-box;
}
.reel-ranked-row-item:hover {
	background: rgba(255, 255, 255, 0.07);
	border-color: rgba(255, 255, 255, 0.18);
	transform: translateX(2px);
}
.reel-ranked-num {
	font-size: 20px;
	font-weight: 900;
	color: #64748b;
	font-family: 'Impact', 'Arial Black', sans-serif;
	font-style: italic;
	min-width: 22px;
	text-align: center;
	line-height: 1;
}
.reel-ranked-thumb {
	width: 42px;
	height: 56px;
	border-radius: 6px;
	overflow: hidden;
	background: #18181b;
	flex-shrink: 0;
}
.reel-ranked-thumb img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
}
.reel-ranked-content {
	flex: 1;
	min-width: 0;
}
.reel-ranked-title {
	font-size: 13.5px;
	font-weight: 800;
	color: #f1f5f9;
	margin: 0 0 3px 0;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	line-height: 1.25;
}
.reel-ranked-sub {
	display: flex;
	align-items: center;
	gap: 8px;
	font-size: 11px;
	font-weight: 700;
	color: #94a3b8;
}
.reel-ranked-tag {
	color: var(--theme-accent, #e11d48);
	font-weight: 800;
	text-transform: uppercase;
	font-size: 10px;
}
.reel-ranked-action {
	flex-shrink: 0;
}
.reel-ranked-play-btn {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	font-size: 11px;
	font-weight: 800;
	color: #ffffff;
	background: rgba(255, 255, 255, 0.08);
	border: 1px solid rgba(255, 255, 255, 0.15);
	padding: 4px 9px;
	border-radius: 6px;
	transition: background 0.15s ease;
}
.reel-ranked-row-item:hover .reel-ranked-play-btn {
	background: var(--theme-accent, #e11d48);
	border-color: transparent;
}

/* ══════════════════════════════════════════════════════════════ */
/* ULTRA-COMPACT MOBILE STYLING (< 768px)                         */
/* ══════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
	.no-hero-wrapper {
		padding-top: 68px !important;
	}
	.reel-hero-side-arrow {
		display: none !important;
	}
	.reel-track-arrow {
		display: none !important;
	}
	.reel-hero-showcase {
		min-height: 380px !important;
		padding-top: calc(env(safe-area-inset-top, 0px) + 54px) !important;
		padding-bottom: 28px !important;
		margin-bottom: 12px !important;
	}
	.reel-hero-showcase .reel-container {
		padding: 0 16px !important;
		gap: 10px !important;
	}
	.reel-hero-info-col {
		flex: 1 1 100% !important;
		max-width: 100% !important;
		width: 100% !important;
	}
	.reel-hero-rank-row {
		margin-bottom: 5px !important;
	}
	.reel-hero-badges {
		margin-bottom: 6px !important;
		gap: 6px !important;
	}
	#reel-hero-badge-rank {
		font-size: 10.5px !important;
		padding: 3px 9px !important;
		font-weight: 900 !important;
	}
	#reel-hero-badge-tag,
	#reel-hero-badge-trope {
		font-size: 10px !important;
		padding: 2.5px 8px !important;
	}
	#reel-hero-title {
		font-size: 20px !important;
		line-height: 1.18 !important;
		margin-bottom: 6px !important;
		max-width: 85% !important;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}
	#reel-hero-meta-row {
		display: flex !important;
		flex-wrap: nowrap !important;
		align-items: center !important;
		gap: 6px !important;
		margin-bottom: 8px !important;
		max-width: 100% !important;
		overflow-x: auto !important;
		scrollbar-width: none !important;
		-ms-overflow-style: none !important;
	}
	#reel-hero-meta-row::-webkit-scrollbar {
		display: none !important;
	}
	#reel-hero-rating-badge,
	#reel-hero-episodes-badge,
	#reel-hero-views-badge {
		font-size: 10.5px !important;
		padding: 2.5px 7px !important;
		white-space: nowrap !important;
		flex-shrink: 0 !important;
		background: rgba(255, 255, 255, 0.12) !important;
		backdrop-filter: blur(8px) !important;
		-webkit-backdrop-filter: blur(8px) !important;
		border: none !important;
		border-radius: 6px !important;
	}
	#reel-hero-rating-badge svg,
	#reel-hero-episodes-badge svg,
	#reel-hero-views-badge svg {
		width: 10px !important;
		height: 10px !important;
	}
	#reel-hero-overview {
		font-size: 12px !important;
		line-height: 1.4 !important;
		margin-bottom: 10px !important;
		max-width: 85% !important;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}
	.reel-hero-actions-row {
		width: 100% !important;
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		margin-top: 4px !important;
		padding-bottom: 4px !important;
	}
	.reel-hero-btn-group {
		gap: 8px !important;
	}
	.btn-reel-hero-play {
		font-size: 13px !important;
		height: 36px !important;
		padding: 0 16px !important;
		gap: 6px !important;
		box-sizing: border-box !important;
	}
	.btn-reel-hero-play svg {
		width: 14px !important;
		height: 14px !important;
	}
	.btn-reel-hero-mylist {
		font-size: 12.5px !important;
		height: 36px !important;
		padding: 0 14px !important;
		gap: 5px !important;
		border: none !important;
		box-sizing: border-box !important;
	}
	.btn-reel-hero-mylist svg {
		width: 14px !important;
		height: 14px !important;
	}
	.reel-hero-dots-wrap {
		display: flex !important;
		align-items: center !important;
		gap: 5px !important;
		margin-left: auto !important;
	}
	.reel-hero-dot {
		width: 5px !important;
		height: 5px !important;
	}
	.reel-hero-dot.active {
		width: 14px !important;
	}
	.reel-hero-rail-col {
		display: none !important;
	}

	/* Card Badges on Mobile: Smaller, Closer to Edge corners, Visible Gap in between */
	.reel-card-badge-left,
	.reel-badge-tl,
	.portrait-badge-top-left,
	.reel-poster-box > div[style*="top:8px; left:8px;"] {
		top: 4px !important;
		left: 4px !important;
	}
	.reel-card-rating {
		top: 4px !important;
		right: 4px !important;
		font-size: 8px !important;
		padding: 1.5px 4px !important;
		gap: 2px !important;
		border-radius: 3px !important;
	}
	.reel-card-rating span {
		font-size: 8px !important;
		line-height: 1 !important;
	}
	.reel-poster-badge,
	.is-vip-badge,
	.reel-tag-badge,
	.badge-type {
		font-size: 7.5px !important;
		padding: 1.5px 4px !important;
		border-radius: 3px !important;
		letter-spacing: 0.2px !important;
		gap: 2px !important;
	}
	.reel-poster-box div[style*="bottom:8px; right:8px;"] {
		bottom: 4px !important;
		right: 4px !important;
		font-size: 8.5px !important;
		padding: 1.5px 5px !important;
	}

	/* Leaderboard Mobile Styling */
	.reel-leaderboard-podium-grid {
		grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
		gap: 8px !important;
		margin-bottom: 12px !important;
	}
	.reel-podium-card {
		border-radius: 9px !important;
	}
	.reel-podium-poster-wrap {
		aspect-ratio: 1 / 1 !important;
	}
	.reel-podium-rank-badge {
		top: 4px !important;
		left: 4px !important;
		font-size: 8px !important;
		padding: 2px 5px !important;
	}
	.reel-podium-score-pill {
		top: 4px !important;
		right: 4px !important;
		font-size: 8px !important;
		padding: 1.5px 4px !important;
	}
	.reel-podium-play-btn {
		bottom: 4px !important;
		right: 4px !important;
		width: 22px !important;
		height: 22px !important;
	}
	.reel-podium-play-btn svg {
		width: 11px !important;
		height: 11px !important;
	}
	.reel-podium-info {
		padding: 6px 8px !important;
	}
	.reel-podium-title {
		font-size: 11.5px !important;
		margin-bottom: 2px !important;
	}
	.reel-podium-meta-top,
	.reel-podium-stats {
		font-size: 9.5px !important;
	}
	.reel-podium-genre {
		font-size: 8.5px !important;
	}
	.reel-leaderboard-ranked-list {
		grid-template-columns: 1fr !important;
		gap: 6px !important;
	}
	.reel-ranked-row-item {
		padding: 6px 8px !important;
		gap: 8px !important;
		border-radius: 8px !important;
	}
	.reel-ranked-num {
		font-size: 15px !important;
		min-width: 16px !important;
	}
	.reel-ranked-thumb {
		width: 34px !important;
		height: 46px !important;
		border-radius: 4px !important;
	}
	.reel-ranked-title {
		font-size: 12px !important;
	}
	.reel-ranked-sub {
		font-size: 9.5px !important;
		gap: 5px !important;
	}
	.reel-ranked-tag {
		font-size: 8.5px !important;
	}
	.reel-ranked-play-btn {
		font-size: 9.5px !important;
		padding: 3px 6px !important;
	}

	.short-content-rows {
		padding: 0 14px 60px 14px !important;
	}
	.reel-row-wrapper {
		margin-bottom: 24px !important;
	}
	.reel-row-header {
		margin-bottom: 10px !important;
	}
	.reel-row-header h2 {
		font-size: 17px !important;
	}
	.reel-cards-track {
		gap: 10px !important;
		padding: 4px 18px 10px 0 !important;
		scroll-padding-right: 24px !important;
	}
	.reel-cards-track .reel-card-item:last-child {
		margin-right: 18px !important;
	}
	.reel-card-item {
		flex: 0 0 105px !important;
		width: 105px !important;
	}
	.reel-poster-box {
		border-radius: 8px !important;
		aspect-ratio: 10 / 14 !important;
	}
	.reel-rank-overlay {
		font-size: 38px !important;
		bottom: -6px !important;
		left: 2px !important;
	}
	.reel-card-item h3 {
		font-size: 11.5px !important;
		margin: 5px 0 2px 0 !important;
		line-height: 1.25 !important;
	}
	.reel-card-item div {
		font-size: 10px !important;
	}
	.reel-card-views {
		font-size: 9.5px !important;
	}
}
</style>

<script>
if (window.location.hash.toLowerCase() === '#leaderboard') {
	window.location.replace('<?php echo esc_url( home_url( '/leaderboard/' ) ); ?>');
}
window.addEventListener('hashchange', function() {
	if (window.location.hash.toLowerCase() === '#leaderboard') {
		window.location.replace('<?php echo esc_url( home_url( '/leaderboard/' ) ); ?>');
	}
});
</script>

<?php
get_footer();
