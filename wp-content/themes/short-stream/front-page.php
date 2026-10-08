<?php
/**
 * ShortTV Homepage - Authentic ReelShort UI/UX Experience
 *
 * @package Short_Stream
 */

get_header();

// Fetch short drama series (any status to ensure user-created dramas always appear in incognito)
$dramas_query  = new WP_Query( array(
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

// Map database dramas into frontend format
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

		$likes_num = (int) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_like_count', true ) : 0 ) ?: ( $db_item['analytics']['like_count'] ?? 0 ) );
		$comments_num = (int) ( $post_id ? get_comments_number( $post_id ) : 0 );
		$rating_val = (float) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_rating', true ) : 0 ) ?: ( $db_item['rating'] ?? ( $db_item['vote_average'] ?? 0 ) ) );
		$rating_count = (int) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_rating_count', true ) : 0 ) ?: ( $db_item['rating_count'] ?? ( $db_item['vote_count'] ?? 0 ) ) );
		$episodes_count = ! empty( $db_item['episodes'] ) ? count( (array) $db_item['episodes'] ) : ( (int) ( $post_id ? get_post_meta( $post_id, '_shorttv_episode_count', true ) : 0 ) ?: 1 );
		$date_ts = $post_id ? get_post_time( 'U', true, $post_id ) : time();

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

		$tag = ! empty( $db_item['tag'] ) ? $db_item['tag'] : 'Trending';
		$trope = ! empty( $genres_list[0] ) ? $genres_list[0] : ( $db_item['trope'] ?? 'Romance' );
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

// Real database short dramas or curated premium fallback cards
$unique_dramas = array();
$seen_titles = array();
foreach ( $db_formatted as $d ) {
	$key = strtolower( trim( $d['title'] ) );
	if ( ! isset( $seen_titles[ $key ] ) ) {
		$seen_titles[ $key ] = true;
		$unique_dramas[] = $d;
	}
}

// Fallback to high-definition curated drama cards and hero banner if site is new or has no uploads
if ( empty( $unique_dramas ) && function_exists( 'short_get_curated_fallback_dramas' ) ) {
	$unique_dramas = short_get_curated_fallback_dramas();
}

// 1. Check user-configured homepage sections from Short Stream -> Content Rows & Feeds
$desktop_sections = get_option( 'short_homepage_sections', array() );
$mobile_sections  = get_option( 'short_mobile_home_sections', array() );

$has_desktop_sections = ! empty( $desktop_sections ) && is_array( $desktop_sections );
$has_mobile_sections  = ! empty( $mobile_sections ) && is_array( $mobile_sections );
$has_custom_sections  = $has_desktop_sections || $has_mobile_sections;

// Helper function to fetch items for a section configuration
$fetch_section_dramas = function( $sec, $unique_dramas ) {
	$type     = $sec['type'] ?? 'shorttv_popular';
	$endpoint = strtolower( trim( $sec['endpoint'] ?? '' ) );
	$limit    = ! empty( $sec['limit'] ) ? (int) $sec['limit'] : 15;
	$pool     = $unique_dramas;

	// Genre filter matching
	$genre_keyword = '';
	if ( 0 === strpos( $endpoint, 'genre/' ) ) {
		$genre_keyword = sanitize_title( str_replace( 'genre/', '', $endpoint ) );
	} elseif ( 0 === strpos( $type, 'genre/' ) ) {
		$genre_keyword = sanitize_title( str_replace( 'genre/', '', $type ) );
	} elseif ( 0 === strpos( $type, 'shorttv_' ) && ! in_array( $type, array( 'shorttv_hero', 'shorttv_popular', 'shorttv_top_rated', 'shorttv_latest', 'shorttv_binge', 'shorttv_genre' ), true ) ) {
		$genre_keyword = sanitize_title( str_replace( 'shorttv_', '', $type ) );
	} elseif ( 'shorttv_genre' === $type && ! empty( $endpoint ) ) {
		$genre_keyword = sanitize_title( $endpoint );
	}

	if ( ! empty( $genre_keyword ) ) {
		$target_keywords = array( $genre_keyword );
		if ( $genre_keyword === 'ceo' ) $target_keywords[] = 'billionaire';
		if ( $genre_keyword === 'billionaire' ) $target_keywords[] = 'ceo';
		if ( $genre_keyword === 'vampire' ) $target_keywords[] = 'werewolf';
		if ( $genre_keyword === 'werewolf' ) $target_keywords[] = 'vampire';

		$pool = array_filter( $pool, function( $d ) use ( $target_keywords ) {
			foreach ( $d['genres'] as $g ) {
				$g_clean = sanitize_title( $g );
				foreach ( $target_keywords as $tk ) {
					if ( $g_clean === $tk || false !== strpos( $g_clean, $tk ) || false !== strpos( $tk, $g_clean ) ) {
						return true;
					}
				}
			}
			$trope_clean = sanitize_title( $d['trope'] ?? '' );
			foreach ( $target_keywords as $tk ) {
				if ( $trope_clean === $tk || false !== strpos( $trope_clean, $tk ) ) {
					return true;
				}
			}
			return false;
		} );
	}

	// VIP Exclusives filter
	if ( $type === 'vip' || $endpoint === 'vip' || $type === 'shorttv_vip' ) {
		$pool = array_filter( $pool, function( $d ) {
			return ! empty( $d['is_vip'] );
		} );
	}

	// Sort logic: prioritize $type or $endpoint
	if ( $type === 'rating' || $endpoint === 'rating' || $type === 'shorttv_top_rated' || ( $sec['layout'] ?? '' ) === 'top10' || $type === 'leaderboard' || $endpoint === 'leaderboard' || ( $sec['layout'] ?? '' ) === 'leaderboard' ) {
		usort( $pool, function( $a, $b ) {
			$score_a = ( $a['rating_count'] > 0 ) ? ( $a['rating_val'] * 1000 + $a['rating_count'] * 50 + $a['likes_num'] * 2 + $a['views_num'] * 0.01 ) : ( $a['likes_num'] * 2 + $a['views_num'] * 0.01 );
			$score_b = ( $b['rating_count'] > 0 ) ? ( $b['rating_val'] * 1000 + $b['rating_count'] * 50 + $b['likes_num'] * 2 + $b['views_num'] * 0.01 ) : ( $b['likes_num'] * 2 + $b['views_num'] * 0.01 );
			return $score_b <=> $score_a;
		} );
	} elseif ( $type === 'latest' || $endpoint === 'latest' || $type === 'shorttv_latest' ) {
		usort( $pool, function( $a, $b ) {
			if ( $a['date_ts'] === $b['date_ts'] ) {
				return $b['post_id'] <=> $a['post_id'];
			}
			return $b['date_ts'] <=> $a['date_ts'];
		} );
	} elseif ( $type === 'episodes' || $endpoint === 'episodes' || $type === 'shorttv_binge' ) {
		usort( $pool, function( $a, $b ) {
			$score_a = ( $a['episodes_count'] * 50 ) + ( $a['comments_num'] * 20 ) + $a['likes_num'];
			$score_b = ( $b['episodes_count'] * 50 ) + ( $b['comments_num'] * 20 ) + $b['likes_num'];
			return $score_b <=> $score_a;
		} );
	} else {
		// Most Viewed (Popular / Views)
		usort( $pool, function( $a, $b ) {
			if ( $a['views_num'] === $b['views_num'] ) {
				return $b['likes_num'] <=> $a['likes_num'];
			}
			return $b['views_num'] <=> $a['views_num'];
		} );
	}

	return array_slice( array_values( $pool ), 0, $limit );
};

// 2. Featured Items for the Hero Showcase Carousel
$hero_items = array();
$hero_search_pool = $has_desktop_sections ? $desktop_sections : ( $has_mobile_sections ? $mobile_sections : array() );
if ( ! empty( $hero_search_pool ) ) {
	foreach ( $hero_search_pool as $key => $sec ) {
		if ( 0 === strpos( $sec['type'] ?? '', 'shorttv_' ) && class_exists( 'Short_Stream_Dashboard' ) ) {
			$sec = Short_Stream_Dashboard::migrate_legacy_section( $sec );
		}
		if ( ! empty( $sec['enabled'] ) && ( $sec['layout'] ?? '' ) === 'hero' ) {
			$hero_items = $fetch_section_dramas( $sec, $unique_dramas );
			break;
		}
	}
}
if ( empty( $hero_items ) ) {
	$hero_pool = $unique_dramas;
	usort( $hero_pool, function( $a, $b ) {
		$score_a = ( $a['views_num'] * 2 ) + ( $a['rating_val'] * 100 ) + $a['likes_num'];
		$score_b = ( $b['views_num'] * 2 ) + ( $b['rating_val'] * 100 ) + $b['likes_num'];
		return $score_b <=> $score_a;
	} );
	$hero_items = array_slice( $hero_pool, 0, 5 );
}
$active_hero = ! empty( $hero_items[0] ) ? $hero_items[0] : null;

// Helper function to render a list of section rows
$render_section_rows = function( $sections_array, $unique_dramas, $fetch_section_dramas ) {
	foreach ( $sections_array as $sec_idx => $sec ) {
		if ( empty( $sec['enabled'] ) ) continue;
		if ( ( $sec['layout'] ?? '' ) === 'hero' ) continue; // Hero billboard handled above

		if ( 0 === strpos( $sec['type'] ?? '', 'shorttv_' ) && class_exists( 'Short_Stream_Dashboard' ) ) {
			$sec = Short_Stream_Dashboard::migrate_legacy_section( $sec );
		}

		$row_title  = ! empty( $sec['title'] ) ? $sec['title'] : 'Featured Dramas';
		$row_layout = $sec['layout'] ?? 'portrait';
		$row_type   = $sec['type'] ?? 'views';
		$row_ep     = $sec['endpoint'] ?? '';
		$is_cw      = ( 'continue_watching' === $row_type || 'continue_watching' === $row_ep || false !== stripos( $row_title, 'watch history' ) || false !== stripos( $row_title, 'continue watching' ) );

		if ( $is_cw ) continue; // Dedicated dynamic row is already rendered at the top of the feed

		$row_dramas = $fetch_section_dramas( $sec, $unique_dramas );
		if ( empty( $row_dramas ) ) continue;

		// Destination URL for 'View all'
		$view_all_url = home_url( '/genre/' );
		if ( $is_cw ) {
			$view_all_url = home_url( '/history/' );
		} elseif ( 'vip' === $row_type || 'vip' === $row_ep ) {
			$view_all_url = home_url( '/genre/?sort=vip' );
		} elseif ( 0 === strpos( $row_ep, 'genre/' ) ) {
			$view_all_url = home_url( '/' . trim( $row_ep, '/' ) . '/' );
		} elseif ( 'shorttv_top_rated' === $row_type || 'shorttv_latest' === $row_type || 'top10' === $row_layout || 'rating' === $row_type || 'latest' === $row_type ) {
			$view_all_url = home_url( '/new-popular/' );
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
					<span>View all</span>
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
		<section class="reel-row-wrapper <?php echo $is_cw ? 'reel-continue-watching-row' : ''; ?>" data-section-type="<?php echo esc_attr( $row_type ); ?>" style="<?php echo $is_cw ? 'display:none;' : ''; ?> margin-bottom:44px; position:relative;">
			<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
				<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
					<?php if ( $is_cw ) : ?>
						<span class="reel-cw-title-icon">
							<svg viewBox="0 0 24 24" fill="#ffffff"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
						</span>
					<?php endif; ?>
					<span><?php echo esc_html( $row_title ); ?></span>
				</h2>
				<a href="<?php echo esc_url( $view_all_url ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
					<span>View all</span>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</a>
			</div>

			<div class="reel-carousel-container" style="position:relative;">
				<!-- Track Navigation Arrow Left -->
				<button type="button" class="reel-track-arrow prev" aria-label="Previous">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>

				<div class="reel-cards-track" style="display:flex; <?php echo ( 'top10' === $row_layout ) ? 'gap:22px;' : 'gap:18px;'; ?> overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
					<?php if ( ! $is_cw ) : ?>
					<?php foreach ( $row_dramas as $idx => $drama ) : 
						$rank_num = $idx + 1;
						$cw_pct = min( 95, max( 18, ( ( $idx * 31 + 45 ) % 78 ) + 18 ) );
						$cw_ep = ( $idx % 6 ) + 1;
					?>
						<div class="reel-card-item <?php echo $is_cw ? 'reel-cw-card' : ''; ?>" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>" style="flex:0 0 186px; width:186px;">
							<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" style="text-decoration:none; color:inherit; display:block;">
								<!-- 2:3 Vertical Poster Container -->
								<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5); transition:transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;">
									<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s ease;" loading="lazy">
									
									<?php if ( $is_cw ) : ?>
										<!-- Top-Left Resume Badge -->
										<div class="reel-cw-badge-wrap" style="position:absolute; top:8px; left:8px; z-index:3;">
											<span class="reel-cw-resume-badge" style="background:var(--theme-accent, #e11d48); color:#ffffff; font-size:9.5px; font-weight:800; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.3px; display:inline-flex; align-items:center; gap:3px; box-shadow:0 2px 6px rgba(var(--theme-accent-rgb, 225,29,72),0.45);">
												<svg width="7" height="7" viewBox="0 0 24 24" fill="#ffffff"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
												<span>RESUME</span>
											</span>
										</div>

										<!-- Bottom Progress Overlay -->
										<div style="position:absolute; bottom:0; left:0; right:0; z-index:3; background:linear-gradient(0deg, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.5) 60%, transparent 100%); padding:18px 10px 8px 10px;">
											<div style="display:flex; align-items:center; justify-content:space-between; font-size:10.5px; font-weight:700; color:#f1f5f9; margin-bottom:5px;">
												<span class="reel-cw-ep-badge" style="display:inline-flex; align-items:center; gap:3px;">
													<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
													<span class="reel-cw-ep-text">Ep. <?php echo $cw_ep; ?></span>
												</span>
												<span class="reel-cw-pct-text" style="color:#fb7185; font-weight:800;"><?php echo $cw_pct; ?>%</span>
											</div>
											<div class="reel-cw-progress-track" style="width:100%; height:4px; background:rgba(255,255,255,0.22); border-radius:999px; overflow:hidden;">
												<div class="reel-cw-progress-fill" style="width:<?php echo $cw_pct; ?>%; height:100%; background:var(--theme-accent, #e11d48); border-radius:999px; box-shadow:0 0 8px rgba(var(--theme-accent-rgb, 225,29,72),0.9);"></div>
											</div>
										</div>
									<?php elseif ( 'top10' === $row_layout ) : ?>
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
										<?php elseif ( ! empty( $drama['badge'] ) ) : ?>
											<div class="reel-card-badge-left" style="position:absolute; top:8px; left:8px; z-index:2;">
												<span class="reel-poster-badge" style="background:var(--theme-accent, #e11d48); color:#fff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.3px;">
													<?php echo esc_html( $drama['badge'] ); ?>
												</span>
											</div>
										<?php endif; ?>
									<?php endif; ?>

									<!-- Top-Right Rating Badge -->
									<?php if ( ! $is_cw && ! empty( $drama['rating_val'] ) && (float) $drama['rating_val'] > 0 ) : ?>
										<div class="reel-card-rating" style="position:absolute; top:8px; right:8px; z-index:2; display:flex; align-items:center; gap:3px; background:rgba(0,0,0,0.7); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px); color:#ffc107; font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px; border:none;">
											<span style="font-size:9.5px; line-height:1;">★</span>
											<span style="color:#ffffff; line-height:1;"><?php echo number_format( (float) $drama['rating_val'], 1 ); ?></span>
										</div>
									<?php endif; ?>

									<!-- Bottom-Right View Count Badge -->
									<?php if ( ! $is_cw && ! empty( $drama['views_num'] ) && (int) $drama['views_num'] > 0 ) : ?>
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
					<?php endif; ?>
				</div>

				<!-- Track Navigation Arrow Right -->
				<button type="button" class="reel-track-arrow next" aria-label="Next">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</button>
			</div>
		</section>
		<?php endif; ?>
		<?php
	}
};

// 3. Fallback Curated Row Collections
$reel_originals = $unique_dramas;
usort( $reel_originals, function( $a, $b ) {
	if ( $a['views_num'] === $b['views_num'] ) {
		return $b['likes_num'] <=> $a['likes_num'];
	}
	return $b['views_num'] <=> $a['views_num'];
} );
$reel_originals = array_slice( $reel_originals, 0, 15 );

$top_ranked = $unique_dramas;
usort( $top_ranked, function( $a, $b ) {
	$score_a = ( $a['rating_val'] * 100 ) + ( $a['rating_count'] * 15 ) + ( $a['views_num'] * 0.5 );
	$score_b = ( $b['rating_val'] * 100 ) + ( $b['rating_count'] * 15 ) + ( $b['views_num'] * 0.5 );
	return $score_b <=> $score_a;
} );
$top_ranked = array_slice( $top_ranked, 0, 10 );

$new_releases = $unique_dramas;
usort( $new_releases, function( $a, $b ) {
	if ( $a['date_ts'] === $b['date_ts'] ) {
		return $b['post_id'] <=> $a['post_id'];
	}
	return $b['date_ts'] <=> $a['date_ts'];
} );
$new_releases = array_slice( $new_releases, 0, 15 );

$binge_ready = $unique_dramas;
usort( $binge_ready, function( $a, $b ) {
	$score_a = ( $a['episodes_count'] * 50 ) + ( $a['comments_num'] * 20 ) + $a['likes_num'];
	$score_b = ( $b['episodes_count'] * 50 ) + ( $b['comments_num'] * 20 ) + $b['likes_num'];
	return $score_b <=> $score_a;
} );
$binge_ready = array_slice( $binge_ready, 0, 15 );

// Dynamic Genre Groupings (for fallback)
$genre_group_rows = array();
$genre_emojis = array(
	'romance'     => '💕',
	'fantasy'     => '✨',
	'vampire'     => '🧛',
	'werewolf'    => '🐺',
	'ceo'         => '👑',
	'billionaire' => '💎',
	'revenge'     => '⚡',
	'urban'       => '🏙️',
	'modern'      => '🌆',
	'suspense'    => '🔍',
	'action'      => '🔥',
	'drama'       => '🎭',
);
foreach ( $unique_dramas as $drama ) {
	$g_array = ! empty( $drama['genres'] ) ? $drama['genres'] : array( $drama['trope'] );
	foreach ( $g_array as $g_name ) {
		$g_name_trimmed = trim( $g_name );
		if ( empty( $g_name_trimmed ) ) continue;
		$g_key = strtolower( $g_name_trimmed );
		if ( ! isset( $genre_group_rows[ $g_key ] ) ) {
			$genre_group_rows[ $g_key ] = array(
				'title' => $g_name_trimmed,
				'slug'  => sanitize_title( $g_name_trimmed ),
				'emoji' => $genre_emojis[ $g_key ] ?? '🎬',
				'items' => array(),
			);
		}
		$genre_group_rows[ $g_key ]['items'][] = $drama;
	}
}
?>

<div class="reel-home-page" style="background:var(--bg-app, #121212); background-color:var(--bg-app, #121212); color:#ffffff; min-height:100vh; overflow-x:hidden;">

	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<!-- 1. REELSHORT INTERACTIVE HERO SHOWCASE CAROUSEL                             -->
	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<?php if ( $active_hero ) : ?>
	<section class="reel-hero-showcase" id="reel-hero-carousel" style="position:relative; width:100%; min-height:660px; display:flex; align-items:flex-end; padding-bottom:95px; box-sizing:border-box;">
		
		<!-- Dynamic Cinematic Backdrop Image with Seamless Dark Edge Shadows -->
		<div class="reel-hero-bg-layer" id="reel-hero-bg" style="position:absolute; inset:0; background: url('<?php echo esc_url( $active_hero['backdrop'] ); ?>') center top / cover no-repeat; transition: background-image 0.5s ease-in-out;">
			<!-- Top & Bottom Gradient Overlay -->
			<div class="reel-hero-overlay-vertical"></div>
			<!-- Deep Left Shadow (Solid dark tone at outer left edge fading in seamlessly) -->
			<div class="reel-hero-overlay-left"></div>
			<!-- Deep Right Shadow (Solid dark tone at outer right edge fading in seamlessly) -->
			<div class="reel-hero-overlay-right"></div>
		</div>

		<!-- Vertically Centered Left & Right Hero Navigation Arrows (Larger on Desktop) -->
		<button type="button" class="reel-hero-side-arrow prev" id="btn-hero-prev-side" aria-label="Previous Drama">
			<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
		</button>
		<button type="button" class="reel-hero-side-arrow next" id="btn-hero-next-side" aria-label="Next Drama">
			<svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
		</button>

		<div class="reel-container reel-hero-container" style="position:relative; z-index:4; max-width:100%; width:100%; margin:0 auto; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:28px;">
			
			<!-- Left Column: Title, Badges, Synopsis & Play CTA with Inlined Dots -->
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

				<!-- Main Title -->
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

				<!-- 3-line Synopsis -->
				<p id="reel-hero-overview" style="font-size:14.5px; color:#cbd5e1; line-height:1.65; margin:0 0 22px 0; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; text-shadow:0 2px 8px rgba(0,0,0,0.8);">
					<?php echo esc_html( $active_hero['overview'] ); ?>
				</p>

				<!-- Action Row: High-contrast Play Button + My List Button + Inlined Dots Indicator on the Right -->
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

					<!-- Inlined Small Hero Dots on Right -->
					<div class="reel-hero-dots-wrap" id="reel-hero-dots">
						<?php foreach ( $hero_items as $d_idx => $d_item ) : ?>
							<button type="button" class="reel-hero-dot <?php echo $d_idx === 0 ? 'active' : ''; ?>" data-index="<?php echo $d_idx; ?>" aria-label="Slide <?php echo $d_idx + 1; ?>"></button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<!-- Right Column: Interactive 5-Thumbnail Rail Switcher (Desktop Only) -->
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
	<div class="reel-content-container" style="max-width:100%; margin:0 auto; padding:0 32px 80px 32px;">

		<!-- DYNAMIC WATCH HISTORY / CONTINUE WATCHING ROW (Rendered when watch history exists) -->
		<section class="reel-row-wrapper reel-continue-watching-row" data-section-type="continue_watching" style="display:none; margin-bottom:44px; position:relative;">
			<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
				<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
					<span class="reel-cw-title-icon" style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; background:var(--theme-accent, #e11d48); border-radius:7px; box-shadow:0 2px 8px rgba(var(--theme-accent-rgb, 225,29,72),0.45); font-size:12px;">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="#ffffff"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
					</span>
					<span>Watch History</span>
				</h2>
				<a href="<?php echo esc_url( home_url( '/history/' ) ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
					<span>View all</span>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</a>
			</div>

			<div class="reel-carousel-container" style="position:relative;">
				<!-- Track Navigation Arrow Left -->
				<button type="button" class="reel-track-arrow prev" aria-label="Previous">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>

				<div class="reel-cards-track" style="display:flex; gap:18px; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
					<!-- Injected via JavaScript from localStorage / Firebase -->
				</div>

				<!-- Track Navigation Arrow Right -->
				<button type="button" class="reel-track-arrow next" aria-label="Next">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</button>
			</div>
		</section>

		<?php if ( empty( $unique_dramas ) ) : ?>
			<div style="text-align:center; padding: 100px 20px; color:#94a3b8;">
				<div style="font-size:52px; margin-bottom:16px;">🎬</div>
				<h2 style="color:#ffffff; font-size:24px; font-weight:800; margin-bottom:8px;">No Dramas Available Yet</h2>
				<p style="font-size:14.5px; max-width:440px; margin:0 auto 24px auto; line-height:1.6; color:#94a3b8;">
					Start creating short dramas and uploading episodes in your WordPress admin dashboard to populate your homepage.
				</p>
				<?php if ( current_user_can( 'edit_posts' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=short_title' ) ); ?>" style="display:inline-flex; align-items:center; gap:8px; background:#e11d48; color:#ffffff; font-weight:800; font-size:14px; padding:12px 28px; border-radius:999px; text-decoration:none; box-shadow:0 4px 15px rgba(225,29,72,0.4);">
						<span>+ Create New Drama</span>
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $has_custom_sections ) : ?>
			<!-- DESKTOP SECTIONS WRAPPER (Visible on >= 769px) -->
			<div class="reel-desktop-sections-wrap">
				<?php 
				if ( $has_desktop_sections ) {
					$render_section_rows( $desktop_sections, $unique_dramas, $fetch_section_dramas );
				} elseif ( $has_mobile_sections ) {
					$render_section_rows( $mobile_sections, $unique_dramas, $fetch_section_dramas );
				}
				?>
			</div>

			<!-- MOBILE SECTIONS WRAPPER (Visible on <= 768px, rendering exact configured mobile sections) -->
			<div class="reel-mobile-sections-wrap">
				<?php 
				if ( $has_mobile_sections ) {
					$render_section_rows( $mobile_sections, $unique_dramas, $fetch_section_dramas );
				} elseif ( $has_desktop_sections ) {
					$render_section_rows( $desktop_sections, $unique_dramas, $fetch_section_dramas );
				}
				?>
			</div>
		<?php else : ?>
			<!-- DEFAULT REELSHORT CURATED SECTIONS (Fallback when not customized) -->

			<!-- ROW 1: Reel Original 🌍 -->
			<?php if ( ! empty( $reel_originals ) ) : ?>
			<section class="reel-row-wrapper" style="margin-bottom:44px; position:relative;">
				<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
					<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
						<span>Reel Original</span>
						<span style="font-size:20px;">🌍</span>
					</h2>
					<a href="<?php echo esc_url( home_url( '/genre/' ) ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
						<span>View all</span>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</a>
				</div>

				<div class="reel-carousel-container" style="position:relative;">
					<!-- Track Navigation Arrow Left -->
					<button type="button" class="reel-track-arrow prev" aria-label="Previous">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>

					<div class="reel-cards-track" style="display:flex; gap:18px; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
						<?php foreach ( $reel_originals as $idx => $drama ) : ?>
							<div class="reel-card-item" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>" style="flex:0 0 186px; width:186px;">
								<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" style="text-decoration:none; color:inherit; display:block;">
									<!-- 2:3 Vertical Poster Container -->
									<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5); transition:transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;">
										<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s ease;" loading="lazy">
										
										<!-- Top-Left Badge (New / Hot) -->
										<?php if ( ! empty( $drama['badge'] ) ) : ?>
											<div style="position:absolute; top:8px; left:8px; z-index:2;">
												<span class="reel-poster-badge" style="background:var(--theme-accent, #e11d48); color:#fff; font-size:10px; font-weight:800; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.3px;">
													<?php echo esc_html( $drama['badge'] ); ?>
												</span>
											</div>
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


			<!-- ROW 2: TOP 🏆 (Ranked 1-10 Chart) -->
			<?php if ( ! empty( $top_ranked ) ) : ?>
			<section class="reel-row-wrapper" style="margin-bottom:44px; position:relative;">
				<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
					<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
						<span>TOP</span>
						<span style="font-size:20px;">🏆</span>
					</h2>
					<a href="<?php echo esc_url( home_url( '/new-popular/' ) ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
						<span>View all</span>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</a>
				</div>

				<div class="reel-carousel-container" style="position:relative;">
					<!-- Track Navigation Arrow Left -->
					<button type="button" class="reel-track-arrow prev" aria-label="Previous">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>

					<div class="reel-cards-track" style="display:flex; gap:22px; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
						<?php foreach ( $top_ranked as $idx => $drama ) : 
							$rank_num = $idx + 1;
						?>
							<div class="reel-card-item" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>" style="flex:0 0 186px; width:186px;">
								<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" style="text-decoration:none; color:inherit; display:block;">
									<!-- 2:3 Vertical Poster Container with Big Overlaid Rank Number -->
									<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5); transition:transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;">
										<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s ease;" loading="lazy">
										
										<!-- Giant Stylized Rank Number at Bottom-Left (ReelShort Signature) -->
										<div class="reel-rank-overlay" style="position:absolute; bottom:-10px; left:4px; z-index:3; font-size:62px; font-weight:900; line-height:1; font-family:'Impact', 'Arial Black', sans-serif; font-style:italic; color:<?php echo ( $rank_num === 1 ) ? '#f59e0b' : ( ( $rank_num === 2 ) ? '#94a3b8' : ( ( $rank_num === 3 ) ? '#d97706' : '#ffffff' ) ); ?>; text-shadow:0 4px 14px rgba(0,0,0,0.95), 0 0 2px rgba(0,0,0,0.8); pointer-events:none;">
											<?php echo $rank_num; ?>
										</div>

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


			<!-- ROW 3: New Releases 🚀 -->
			<?php if ( ! empty( $new_releases ) ) : ?>
			<section class="reel-row-wrapper" style="margin-bottom:44px; position:relative;">
				<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
					<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
						<span>New Releases</span>
						<span style="font-size:20px;">🚀</span>
					</h2>
					<a href="<?php echo esc_url( home_url( '/new-popular/' ) ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
						<span>View all</span>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</a>
				</div>

				<div class="reel-carousel-container" style="position:relative;">
					<!-- Track Navigation Arrow Left -->
					<button type="button" class="reel-track-arrow prev" aria-label="Previous">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>

					<div class="reel-cards-track" style="display:flex; gap:18px; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
						<?php foreach ( $new_releases as $idx => $drama ) : ?>
							<div class="reel-card-item" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>" style="flex:0 0 186px; width:186px;">
								<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" style="text-decoration:none; color:inherit; display:block;">
									<!-- 2:3 Vertical Poster Container -->
									<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5); transition:transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;">
										<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s ease;" loading="lazy">
										
										<!-- Top-Left AI Badge -->
										<div style="position:absolute; top:8px; left:8px; z-index:2;">
											<span style="background:rgba(0,0,0,0.6); backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,0.2); color:#fff; font-size:10px; font-weight:800; padding:2px 6px; border-radius:4px;">
												AI
											</span>
										</div>

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


			<!-- ROW 4: Binge-Ready 🔥 -->
			<?php if ( ! empty( $binge_ready ) ) : ?>
			<section class="reel-row-wrapper" style="margin-bottom:44px; position:relative;">
				<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
					<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
						<span>Binge-Ready</span>
						<span style="font-size:20px;">🔥</span>
					</h2>
					<a href="<?php echo esc_url( home_url( '/genre/' ) ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
						<span>View all</span>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</a>
				</div>

				<div class="reel-carousel-container" style="position:relative;">
					<!-- Track Navigation Arrow Left -->
					<button type="button" class="reel-track-arrow prev" aria-label="Previous">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>

					<div class="reel-cards-track" style="display:flex; gap:18px; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
						<?php foreach ( $binge_ready as $idx => $drama ) : ?>
							<div class="reel-card-item" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>" style="flex:0 0 186px; width:186px;">
								<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" style="text-decoration:none; color:inherit; display:block;">
									<!-- 2:3 Vertical Poster Container -->
									<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5); transition:transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;">
										<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s ease;" loading="lazy">
										
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

			<!-- Dynamic Genre-Specific Rows (e.g. Romance, Fantasy, Vampire, Werewolf, CEO, Revenge, etc.) -->
			<?php if ( ! empty( $genre_group_rows ) ) : ?>
				<?php foreach ( $genre_group_rows as $g_key => $g_data ) : 
					if ( empty( $g_data['items'] ) ) continue;
					$g_items = array_slice( $g_data['items'], 0, 15 );
				?>
				<section class="reel-row-wrapper" style="margin-bottom:44px; position:relative;">
					<div class="reel-row-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
						<h2 style="font-size:22px; font-weight:900; color:#ffffff; margin:0; display:flex; align-items:center; gap:8px;">
							<span><?php echo esc_html( $g_data['title'] ); ?></span>
							<span style="font-size:20px;"><?php echo esc_html( $g_data['emoji'] ); ?></span>
						</h2>
						<a href="<?php echo esc_url( home_url( '/genre/' . $g_data['slug'] . '/' ) ); ?>" class="reel-view-all" style="color:#94a3b8; font-size:13.5px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.15s;">
							<span>View all</span>
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</a>
					</div>

					<div class="reel-carousel-container" style="position:relative;">
						<!-- Track Navigation Arrow Left -->
						<button type="button" class="reel-track-arrow prev" aria-label="Previous">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
						</button>

						<div class="reel-cards-track" style="display:flex; gap:18px; overflow-x:auto; scrollbar-width:none; -ms-overflow-style:none; scroll-behavior:smooth; padding:6px 0 16px 0;">
							<?php foreach ( $g_items as $idx => $drama ) : ?>
								<div class="reel-card-item" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>" style="flex:0 0 186px; width:186px;">
									<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" style="text-decoration:none; color:inherit; display:block;">
										<!-- 2:3 Vertical Poster Container -->
										<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5); transition:transform 0.2s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease;">
											<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" style="width:100%; height:100%; object-fit:cover; display:block; transition:transform 0.3s ease;" loading="lazy">
											
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
				<?php endforeach; ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<!-- 3. FLOATING ACTION CONTROLS (App Download & Back-to-Top)                     -->
	<!-- ═══════════════════════════════════════════════════════════════════════════ -->
	<div class="reel-floating-controls" style="position:fixed; bottom:30px; right:26px; z-index:99; display:flex; flex-direction:column; gap:16px; align-items:center;">
		<!-- Floating Gift Rewards Button (Desktop Only) -->
		<a href="<?php echo esc_url( home_url( '/account/#view=rewards' ) ); ?>" class="reel-floating-gift-btn" id="reel-btn-gift-rewards" title="<?php esc_attr_e( 'Claim Rewards & Free Coins 🎁', 'short-stream' ); ?>" aria-label="<?php esc_attr_e( 'Claim Rewards', 'short-stream' ); ?>">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/floating-gift.png' ); ?>" alt="Claim Rewards" class="floating-gift-img" />
			<span class="floating-gift-badge"></span>
		</a>

		<!-- Floating Download App Button -->
		<a href="javascript:void(0)" class="reel-floating-btn" id="reel-btn-download-app" role="button" title="Download ShortTV App" style="width:44px; height:44px; border-radius:50%; background:rgba(30,41,59,0.85); backdrop-filter:blur(8px); border:none; outline:none; color:#ffffff; display:flex; align-items:center; justify-content:center; text-decoration:none; box-shadow:0 6px 18px rgba(0,0,0,0.4); transition:transform 0.15s, background 0.2s; cursor:pointer;">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
		</a>

		<!-- Floating Back-to-Top Button -->
		<button type="button" class="reel-floating-btn" id="reel-btn-top" title="Back to Top" style="width:44px; height:44px; border-radius:50%; background:var(--theme-accent, #e11d48); border:none; outline:none; color:#ffffff; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 6px 18px rgba(var(--theme-accent-rgb, 225,29,72),0.45); transition:transform 0.15s, opacity 0.2s; opacity:0.9;">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg>
		</button>
	</div>

</div>

<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<!-- 4. REELSHORT DYNAMIC STYLES & INTERACTIVE SCRIPT                           -->
<!-- ═══════════════════════════════════════════════════════════════════════════ -->
<style>
/* ReelShort Header */
.reel-header:not(.scrolled) {
	background: linear-gradient(180deg, rgba(var(--bg-app-rgb, 18, 18, 18), 0.88) 0%, rgba(var(--bg-app-rgb, 18, 18, 18), 0.45) 60%, transparent 100%) !important;
	background-color: transparent !important;
	border-bottom: none !important;
	box-shadow: none !important;
	position: fixed !important;
	top: 0;
	left: 0;
	width: 100%;
	z-index: 999;
	transition: background 0.25s ease, background-color 0.25s ease !important;
}
.reel-header.scrolled,
.short-header.scrolled {
	background: var(--bg-header, var(--bg-app, #121212)) !important;
	background-color: var(--bg-header, var(--bg-app, #121212)) !important;
	backdrop-filter: none !important;
	-webkit-backdrop-filter: none !important;
	filter: none !important;
	box-shadow: none !important;
	-webkit-box-shadow: none !important;
	border: none !important;
	border-bottom: none !important;
	position: fixed !important;
	top: 0;
	left: 0;
	width: 100%;
	z-index: 999;
	transition: background 0.25s ease, background-color 0.25s ease !important;
}
.reel-brand-logo {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	text-decoration: none !important;
}
.reel-logo-icon {
	background: var(--theme-accent, #e11d48);
	color: #fff;
	width: 30px;
	height: 30px;
	border-radius: 7px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 19px;
	font-weight: 900;
	box-shadow: 0 2px 10px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.4);
}
.reel-logo-text {
	font-size: 20px;
	font-weight: 900;
	color: #ffffff;
	letter-spacing: -0.5px;
}
.reel-nav-desktop a.nav-item {
	color: #cbd5e1 !important;
	font-size: 14.5px !important;
	font-weight: 700 !important;
	text-decoration: none !important;
	padding: 8px 14px !important;
	transition: color 0.15s ease !important;
}
.reel-nav-desktop a.nav-item:hover {
	color: #ffffff !important;
}
.reel-nav-desktop a.nav-item.active {
	color: #ffffff !important;
	font-weight: 800 !important;
}
.reel-header-icon-btn {
	color: #cbd5e1 !important;
	background: transparent !important;
	border: none !important;
	padding: 7px !important;
	border-radius: 50% !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	transition: color 0.15s, background 0.15s !important;
	text-decoration: none !important;
}
.reel-header-icon-btn:hover {
	color: #ffffff !important;
	background: rgba(255, 255, 255, 0.08) !important;
}
.reel-header-mhistory-btn,
.reel-header-mylist-btn {
	border-radius: 6px !important;
	background: transparent !important;
}
.reel-header-mhistory-btn:hover,
.reel-header-mylist-btn:hover {
	background: transparent !important;
	color: var(--theme-accent, #ff2d55) !important;
}
.btn-reel-topup {
	background: var(--theme-accent, #e11d48) !important;
	color: #ffffff !important;
	font-size: 13px !important;
	font-weight: 800 !important;
	padding: 6px 16px !important;
	border-radius: 999px !important;
	text-decoration: none !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	white-space: nowrap !important;
	flex-shrink: 0 !important;
	box-shadow: 0 4px 14px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.4) !important;
	transition: transform 0.15s, box-shadow 0.15s !important;
}
.btn-reel-topup:hover {
	transform: scale(1.04) !important;
	box-shadow: 0 6px 20px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.55) !important;
}

/* ReelShort Hero */
.reel-hero-showcase {
	padding-top: 110px;
	padding-bottom: 95px !important;
}
body.admin-bar .reel-hero-showcase {
	padding-top: 145px;
	padding-bottom: 95px !important;
}

/* Hero Carousel Side Arrows (Larger and prominent on desktop) */
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
	box-shadow: none !important;
	filter: drop-shadow(0 3px 12px rgba(0, 0, 0, 0.95));
	transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), color 0.18s ease, opacity 0.18s ease;
	opacity: 0.9;
	padding: 0;
}
.reel-hero-side-arrow.prev {
	left: 14px;
}
.reel-hero-side-arrow.next {
	right: 14px;
}
.reel-hero-side-arrow:hover {
	color: #ffffff;
	transform: translateY(-50%) scale(1.22);
	opacity: 1;
	background: transparent !important;
	border: none !important;
}

/* Hide hero side arrows on mobile / touch devices */
@media (max-width: 899px) {
	.reel-hero-side-arrow {
		display: none !important;
	}
	.reel-hero-container {
		padding: 0 18px !important;
	}
}

/* Desktop Body & Container Side Padding */
@media (min-width: 900px) {
	.reel-home-page {
		padding: 0 !important;
		width: 100%;
		overflow-x: hidden;
	}
	.reel-container {
		max-width: 100% !important;
		padding: 0 32px !important;
	}
	.reel-hero-container {
		padding: 0 56px !important;
	}
	.reel-content-container {
		max-width: 100% !important;
		padding: 0 32px 80px 32px !important;
	}
}
@media (min-width: 1300px) {
	.reel-hero-container {
		padding: 0 62px !important;
	}
}

/* Inline Small Hero Dots (Inlined with Play Button on Mobile) */
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
.reel-hero-next-arrow:hover,
.reel-hero-prev-arrow:hover {
	background: rgba(0, 0, 0, 0.85) !important;
	border-color: #ffffff !important;
}

/* Card Sizing & Interactions */
.reel-poster-box {
	aspect-ratio: 2 / 3 !important;
}
.reel-card-item:hover .reel-poster-box {
	box-shadow: 0 14px 28px rgba(225, 29, 72, 0.22);
}
.reel-card-item:hover .reel-poster-box img {
	transform: scale(1.05);
}
.reel-view-all:hover {
	color: #ffffff !important;
}
.reel-floating-btn:hover {
	transform: scale(1.08);
}

/* Carousel Track Arrows (Left & Right - Pure Minimalist Icon, Zero Border, Zero Background) */
.reel-track-arrow {
	position: absolute;
	top: 45% !important;
	transform: translateY(-50%) !important;
	width: 52px !important;
	height: 52px !important;
	background: transparent !important;
	backdrop-filter: none !important;
	-webkit-backdrop-filter: none !important;
	border: none !important;
	border-radius: 0 !important;
	box-shadow: none !important;
	color: #ffffff !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	z-index: 15 !important;
	padding: 0 !important;
	transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
	opacity: 1;
}
.reel-track-arrow svg {
	width: 36px !important;
	height: 36px !important;
	stroke-width: 3.2 !important;
	stroke: #ffffff !important;
	filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.95)) drop-shadow(0 0 6px rgba(0, 0, 0, 0.85));
	transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), stroke 0.2s ease, filter 0.2s ease !important;
}
.reel-track-arrow.prev {
	left: -26px !important;
	right: auto !important;
}
.reel-track-arrow.next {
	right: -26px !important;
	left: auto !important;
}
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
.reel-track-arrow.is-disabled {
	opacity: 0 !important;
	pointer-events: none !important;
	visibility: hidden !important;
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
	border: none;
}
.reel-cw-title-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 20px;
	height: 20px;
	background: var(--theme-accent, #e11d48);
	border-radius: 5px;
	box-shadow: 0 2px 6px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.35);
	flex-shrink: 0;
	margin-right: 3px;
}
.reel-cw-title-icon svg {
	width: 9px;
	height: 9px;
	margin-left: 1px;
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

/* Responsive */
@media (max-width: 900px) {
	.reel-hero-showcase {
		min-height: auto;
		padding-top: 80px;
		padding-bottom: 24px;
	}
	.reel-hero-rail-col {
		width: 100%;
		overflow-x: auto;
		padding-bottom: 8px;
	}
	.reel-hero-thumb-item {
		width: 76px !important;
		height: 108px !important;
	}
}

/* Desktop & Mobile Sections Wrapping Rules */
.reel-desktop-sections-wrap {
	display: block;
}
.reel-mobile-sections-wrap {
	display: none;
}

/* Floating Gift Rewards Button (Desktop Only) */
@keyframes giftShakeZoom {
	0% {
		transform: translateY(0) scale(1) rotate(0deg);
		filter: drop-shadow(0 4px 12px rgba(255, 60, 100, 0.45));
	}
	4% {
		transform: translateY(-3px) scale(1.1) rotate(-8deg);
		filter: drop-shadow(0 6px 16px rgba(255, 60, 100, 0.7));
	}
	8% {
		transform: translateY(-6px) scale(1.18) rotate(12deg);
		filter: drop-shadow(0 8px 22px rgba(255, 180, 0, 0.9));
	}
	12% {
		transform: translateY(-5px) scale(1.16) rotate(-10deg);
		filter: drop-shadow(0 6px 18px rgba(255, 60, 100, 0.8));
	}
	16% {
		transform: translateY(-6px) scale(1.18) rotate(10deg);
		filter: drop-shadow(0 8px 22px rgba(255, 180, 0, 0.9));
	}
	20% {
		transform: translateY(-3px) scale(1.14) rotate(-6deg);
		filter: drop-shadow(0 6px 16px rgba(255, 60, 100, 0.7));
	}
	24% {
		transform: translateY(-1px) scale(1.08) rotate(4deg);
		filter: drop-shadow(0 5px 14px rgba(255, 60, 100, 0.55));
	}
	28% {
		transform: translateY(0) scale(1.02) rotate(0deg);
	}
	32%, 100% {
		transform: translateY(0) scale(1) rotate(0deg);
		filter: drop-shadow(0 4px 12px rgba(255, 60, 100, 0.45));
	}
}

@keyframes giftBadgePing {
	0%, 100% { transform: translate(50%, -50%) scale(1); opacity: 1; }
	50% { transform: translate(50%, -50%) scale(1.25); opacity: 0.85; }
}

.reel-floating-gift-btn {
	position: relative;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 50px;
	height: 50px;
	margin-bottom: 6px;
	border-radius: 50%;
	background: radial-gradient(circle, rgba(255, 60, 100, 0.22) 0%, rgba(20, 20, 28, 0.88) 75%);
	border: 1.5px solid rgba(255, 75, 110, 0.65);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45), 0 0 16px rgba(255, 45, 85, 0.35);
	backdrop-filter: blur(8px);
	-webkit-backdrop-filter: blur(8px);
	text-decoration: none;
	cursor: pointer;
	animation: giftShakeZoom 5s infinite ease-in-out;
	transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, border-color 0.2s ease;
	z-index: 10;
}

.reel-floating-gift-btn:hover {
	transform: scale(1.22) translateY(-3px) !important;
	border-color: rgba(255, 215, 0, 0.95);
	box-shadow: 0 10px 28px rgba(0, 0, 0, 0.5), 0 0 24px rgba(255, 180, 0, 0.8);
}

.floating-gift-img {
	width: 36px;
	height: 36px;
	object-fit: contain;
	pointer-events: none;
	transition: transform 0.2s ease;
}

.reel-floating-gift-btn:hover .floating-gift-img {
	transform: scale(1.1);
}

.floating-gift-badge {
	position: absolute;
	top: 7px;
	right: 7px;
	width: 11px;
	height: 11px;
	background: #ff2d55;
	border: 2px solid #14141a;
	border-radius: 50%;
	transform: translate(50%, -50%);
	animation: giftBadgePing 1.8s infinite cubic-bezier(0, 0, 0.2, 1);
	pointer-events: none;
}

@media (max-width: 768px) {
	.reel-desktop-sections-wrap {
		display: none !important;
	}
	.reel-mobile-sections-wrap {
		display: block !important;
	}

	/* Hide Back to Top, Floating Gift, and Floating Controls on Mobile */
	#reel-btn-top,
	.reel-floating-gift-btn,
	.reel-floating-controls {
		display: none !important;
	}

	/* Mobile Carousel & Poster Overrides */
	.reel-cards-track {
		gap: 10px !important;
		padding: 4px 14px 10px 0 !important;
		margin-right: -14px !important;
	}
	.reel-poster-box::after {
		width: 32px !important;
		height: 32px !important;
		background-size: 20px !important; /* Keep triangle exactly the same size */
	}

	/* Mobile Image-only Logo (Compact Header) */
	.reel-brand-logo-text {
		display: none !important;
	}
	.btn-reel-topup {
		font-size: 11px !important;
		padding: 3px 10px !important;
		height: 26px !important;
		line-height: 18px !important;
		white-space: nowrap !important;
		flex-shrink: 0 !important;
	}
	.reel-hero-side-arrow {
		width: 32px !important;
		height: 32px !important;
		background: transparent !important;
		border: none !important;
		box-shadow: none !important;
		filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.9)) !important;
	}
	.reel-hero-side-arrow svg {
		width: 20px !important;
		height: 20px !important;
	}
	.reel-hero-side-arrow.prev {
		left: 4px !important;
	}
	.reel-hero-side-arrow.next {
		right: 4px !important;
	}
	.reel-hero-dot {
		width: 5px !important;
		height: 5px !important;
	}
	.reel-hero-dot.active {
		width: 14px !important;
	}
	.reel-hero-showcase {
		min-height: 380px !important;
		padding-top: calc(env(safe-area-inset-top, 0px) + 54px) !important;
		padding-bottom: 34px !important;
		margin-bottom: 12px !important;
	}
	.reel-hero-showcase .reel-container {
		padding: 0 16px !important;
		gap: 12px !important;
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
		max-width: 84% !important;
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
		max-width: 84% !important;
		-webkit-line-clamp: 2 !important;
	}
	.reel-hero-actions-row {
		width: 100% !important;
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		margin-top: 6px !important;
		padding-bottom: 8px !important;
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
		padding-bottom: 4px !important;
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
	/* Resume badge & Continue Watching on Mobile */
	.reel-cw-badge-wrap {
		top: 4px !important;
		left: 4px !important;
	}
	.reel-cw-resume-badge {
		font-size: 7.5px !important;
		padding: 1.5px 5px !important;
		border-radius: 3px !important;
		letter-spacing: 0.2px !important;
		gap: 2.5px !important;
		box-shadow: 0 1px 4px rgba(0,0,0,0.4) !important;
	}
	.reel-cw-resume-badge svg {
		width: 6px !important;
		height: 6px !important;
	}
	.reel-cw-title-icon {
		width: 15px !important;
		height: 15px !important;
		border-radius: 4px !important;
		margin-right: 2px !important;
		box-shadow: 0 1px 4px rgba(0, 0, 0, 0.4) !important;
	}
	.reel-cw-title-icon svg {
		width: 7px !important;
		height: 7px !important;
		margin-left: 0.5px !important;
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

	.reel-poster-box div[style*="bottom:8px; right:8px;"] {
		bottom: 4px !important;
		right: 4px !important;
		font-size: 8.5px !important;
		padding: 1.5px 5px !important;
	}
	.reel-content-container {
		padding: 0 14px 30px 14px !important;
	}
	.reel-row-wrapper {
		margin-bottom: 28px !important;
	}
	.reel-row-header {
		margin-bottom: 12px !important;
	}
	.reel-row-header h2 {
		font-size: 18px !important;
	}
	.reel-cards-track {
		gap: 10px !important;
		padding: 4px 18px 10px 0 !important;
		scroll-padding-right: 24px !important;
	}
	.reel-cards-track .reel-card-item:last-child,
	.reel-cards-track .reel-cw-card:last-child {
		margin-right: 18px !important;
	}
	.reel-card-item {
		flex: 0 0 105px !important;
		width: 105px !important;
	}
	.reel-poster-box {
		border-radius: 8px !important;
		aspect-ratio: 10 / 14 !important; /* Shorter thumbnail height */
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
	.reel-rank-overlay {
		font-size: 38px !important;
		bottom: -4px !important;
		left: 2px !important;
	}
	.reel-track-arrow {
		display: none !important;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	// 1. ReelShort Interactive Hero Carousel Thumbnail & Dots Switcher
	var $heroBg = document.getElementById('reel-hero-bg');
	var $heroTitle = document.getElementById('reel-hero-title');
	var $heroRank = document.getElementById('reel-hero-badge-rank');
	var $heroTag = document.getElementById('reel-hero-badge-tag');
	var $heroTrope = document.getElementById('reel-hero-badge-trope');
	var $heroRatingVal = document.getElementById('reel-hero-rating-val');
	var $heroEpisodesVal = document.getElementById('reel-hero-episodes-val');
	var $heroViewsVal = document.getElementById('reel-hero-views-val');
	var $heroOverview = document.getElementById('reel-hero-overview');
	var $heroPlayLink = document.getElementById('reel-hero-play-link');
	var $thumbItems = document.querySelectorAll('.reel-hero-thumb-item');
	var $heroDots = document.querySelectorAll('.reel-hero-dot');
	var $nextThumbBtn = document.getElementById('btn-hero-next-thumb');
	var $prevThumbBtn = document.getElementById('btn-hero-prev-thumb');
	var $sidePrevBtn = document.getElementById('btn-hero-prev-side');
	var $sideNextBtn = document.getElementById('btn-hero-next-side');

	var currentHeroIdx = 0;
	var totalThumbs = $thumbItems.length;
	var autoRotateTimer = null;

	function switchHeroDrama(idx) {
		if (idx < 0) idx = totalThumbs - 1;
		if (idx >= totalThumbs) idx = 0;
		currentHeroIdx = idx;

		var item = $thumbItems[idx];
		if (!item) return;

		var dramaId = item.getAttribute('data-id') || '';
		var title = item.getAttribute('data-title') || '';
		var tag = item.getAttribute('data-tag') || 'Trending';
		var trope = item.getAttribute('data-trope') || 'Drama';
		var rating = item.getAttribute('data-rating') || '4.9';
		var episodes = parseInt(item.getAttribute('data-episodes') || '1', 10);
		var views = item.getAttribute('data-views') || '0';
		var overview = item.getAttribute('data-overview') || '';
		var poster = item.getAttribute('data-poster') || '';
		var backdrop = item.getAttribute('data-backdrop') || '';
		var url = item.getAttribute('data-url') || '#';

		// Update active thumb styles
		$thumbItems.forEach(function(el, i) {
			if (i === idx) {
				el.classList.add('active-thumb');
				el.style.borderColor = '#ffffff';
				el.style.boxShadow = '0 0 16px rgba(255,255,255,0.7)';
				el.style.transform = 'scale(1.06)';
			} else {
				el.classList.remove('active-thumb');
				el.style.borderColor = 'rgba(255,255,255,0.18)';
				el.style.boxShadow = '0 4px 12px rgba(0,0,0,0.5)';
				el.style.transform = 'scale(1)';
			}
		});

		// Update inlined small dots
		$heroDots.forEach(function(dot, i) {
			if (i === idx) {
				dot.classList.add('active');
			} else {
				dot.classList.remove('active');
			}
		});

		// Animate text and backdrop update
		if ($heroBg && backdrop) {
			$heroBg.style.backgroundImage = "url('" + backdrop + "')";
		}
		if ($heroRank) $heroRank.innerHTML = '🏆 TOP ' + (idx + 1);
		if ($heroTitle) $heroTitle.textContent = title;
		if ($heroTag) $heroTag.textContent = tag;
		if ($heroTrope) $heroTrope.textContent = trope;
		if ($heroRatingVal) $heroRatingVal.textContent = rating;
		if ($heroEpisodesVal) $heroEpisodesVal.textContent = episodes + (episodes === 1 ? ' Episode' : ' Episodes');
		if ($heroViewsVal) $heroViewsVal.textContent = views + ' Views';
		if ($heroOverview) $heroOverview.textContent = overview;
		if ($heroPlayLink) $heroPlayLink.setAttribute('href', url);

		var $heroMyListBtn = document.getElementById('btn-hero-my-list');
		if ($heroMyListBtn) {
			$heroMyListBtn.setAttribute('data-id', dramaId);
			$heroMyListBtn.setAttribute('data-title', title);
			$heroMyListBtn.setAttribute('data-poster', poster);
			$heroMyListBtn.setAttribute('data-overview', overview);
			$heroMyListBtn.setAttribute('data-url', url);
			updateHeroMyListBtnState(dramaId);
		}
	}

	function isDramaInMyList(dramaId) {
		if (!dramaId) return false;
		dramaId = String(dramaId);
		try {
			if (window.SHORT && window.SHORT.myLocalList && window.SHORT.myLocalList.has(dramaId)) {
				return true;
			}
			for (var k in localStorage) {
				if (k.startsWith('short_my_list') || k.startsWith('short_watchlist') || k === 'stv_watchlist_guest' || k === 'short_guest_watchlist') {
					var list = JSON.parse(localStorage.getItem(k) || '{}');
					if (list && list[dramaId]) return true;
				}
			}
		} catch(e){}
		return false;
	}

	function updateHeroMyListBtnState(dramaId, forceSaved) {
		var btn = document.getElementById('btn-hero-my-list');
		if (!btn) return;
		var saved = (typeof forceSaved === 'boolean') ? forceSaved : isDramaInMyList(dramaId);
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
		if (initId) {
			updateHeroMyListBtnState(initId);
			var uId = (window.firebaseUser && window.firebaseUser.uid) || localStorage.getItem('short_user_uid');
			if (uId) {
				var rtdbUrl = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
				fetch(rtdbUrl + '/users/' + uId + '/watchlist/' + initId + '.json')
					.then(function(r){ return r.json(); })
					.then(function(d){
						if (d !== null) updateHeroMyListBtnState(initId, true);
					}).catch(function(){});
			}
		}

		$heroMyListBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var dId = this.getAttribute('data-id');
			if (!dId) return;
			var dTitle = this.getAttribute('data-title') || '';
			var dPoster = this.getAttribute('data-poster') || '';
			var dOverview = this.getAttribute('data-overview') || '';
			var dUrl = this.getAttribute('data-url') || '#';

			var user = window.firebaseUser || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser);
			var uid = (user && user.uid) || localStorage.getItem('short_user_uid');
			var rtdbUrl = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';

			var isSaved = isDramaInMyList(dId);

			var itemData = {
				id: String(dId),
				title: dTitle,
				poster: dPoster,
				overview: dOverview,
				watchUrl: dUrl,
				savedAt: Date.now()
			};

			try {
				if (isSaved) {
					// 1. Remove from all local lists
					for (var k in localStorage) {
						if (k.startsWith('short_my_list') || k.startsWith('short_watchlist') || k === 'stv_watchlist_guest' || k === 'short_guest_watchlist') {
							var l = JSON.parse(localStorage.getItem(k) || '{}');
							if (l && l[dId]) {
								delete l[dId];
								localStorage.setItem(k, JSON.stringify(l));
							}
						}
					}
					if (window.SHORT && window.SHORT.myLocalList) {
						window.SHORT.myLocalList.delete(String(dId));
					}

					// 2. Remove from Firebase RTDB if uid exists
					if (uid) {
						if (typeof firebase !== 'undefined' && firebase.database) {
							try {
								firebase.database().ref('users/' + uid + '/watchlist/' + dId).remove().catch(function(){});
								firebase.database().ref('users/' + uid + '/my_list/' + dId).remove().catch(function(){});
							} catch(e){}
						}
						fetch(rtdbUrl + '/users/' + uid + '/watchlist/' + dId + '.json', { method: 'DELETE' }).catch(function(){});
						fetch(rtdbUrl + '/users/' + uid + '/my_list/' + dId + '.json', { method: 'DELETE' }).catch(function(){});
					}
					updateHeroMyListBtnState(dId, false);
				} else {
					// 1. Add to all local lists
					if (uid) {
						var userList = JSON.parse(localStorage.getItem('short_watchlist_' + uid) || '{}');
						userList[dId] = itemData;
						localStorage.setItem('short_watchlist_' + uid, JSON.stringify(userList));
						localStorage.setItem('short_my_list_' + uid, JSON.stringify(userList));
					}
					var globalList = JSON.parse(localStorage.getItem('short_watchlist') || localStorage.getItem('short_my_list') || '{}');
					globalList[dId] = itemData;
					localStorage.setItem('short_watchlist', JSON.stringify(globalList));
					localStorage.setItem('short_my_list', JSON.stringify(globalList));
					localStorage.setItem('short_guest_watchlist', JSON.stringify(globalList));

					if (window.SHORT && window.SHORT.myLocalList) {
						window.SHORT.myLocalList.add(String(dId));
					}

					// 2. Save to Firebase RTDB if uid exists
					if (uid) {
						if (typeof firebase !== 'undefined' && firebase.database) {
							try {
								firebase.database().ref('users/' + uid + '/watchlist/' + dId).set(itemData).catch(function(){});
								firebase.database().ref('users/' + uid + '/my_list/' + dId).set(itemData).catch(function(){});
							} catch(e){}
						}
						fetch(rtdbUrl + '/users/' + uid + '/watchlist/' + dId + '.json', {
							method: 'PUT',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(itemData)
						}).catch(function(){});
						fetch(rtdbUrl + '/users/' + uid + '/my_list/' + dId + '.json', {
							method: 'PUT',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(itemData)
						}).catch(function(){});
					}
					updateHeroMyListBtnState(dId, true);
				}
			} catch(err){}
		});
	}

	$thumbItems.forEach(function(el, i) {
		el.addEventListener('click', function() {
			switchHeroDrama(i);
			restartHeroTimer();
		});
	});

	$heroDots.forEach(function(dot, i) {
		dot.addEventListener('click', function() {
			switchHeroDrama(i);
			restartHeroTimer();
		});
	});

	if ($nextThumbBtn) {
		$nextThumbBtn.addEventListener('click', function(e) {
			e.preventDefault();
			switchHeroDrama((currentHeroIdx + 1) % totalThumbs);
			restartHeroTimer();
		});
	}

	if ($prevThumbBtn) {
		$prevThumbBtn.addEventListener('click', function(e) {
			e.preventDefault();
			switchHeroDrama((currentHeroIdx - 1 + totalThumbs) % totalThumbs);
			restartHeroTimer();
		});
	}

	if ($sideNextBtn) {
		$sideNextBtn.addEventListener('click', function(e) {
			e.preventDefault();
			switchHeroDrama((currentHeroIdx + 1) % totalThumbs);
			restartHeroTimer();
		});
	}

	if ($sidePrevBtn) {
		$sidePrevBtn.addEventListener('click', function(e) {
			e.preventDefault();
			switchHeroDrama((currentHeroIdx - 1 + totalThumbs) % totalThumbs);
			restartHeroTimer();
		});
	}

	// Mobile Touch Swipe Gesture Support on Hero Showcase
	var $heroShowcase = document.getElementById('reel-hero-carousel');
	if ($heroShowcase) {
		var touchStartX = 0;
		var touchStartY = 0;
		var touchEndX = 0;
		var touchEndY = 0;
		var minSwipeDistance = 40; // minimum pixels for a valid swipe

		$heroShowcase.addEventListener('touchstart', function(e) {
			if (e.touches && e.touches.length > 0) {
				touchStartX = e.touches[0].clientX;
				touchStartY = e.touches[0].clientY;
				touchEndX = touchStartX;
				touchEndY = touchStartY;
			}
		}, { passive: true });

		$heroShowcase.addEventListener('touchmove', function(e) {
			if (e.touches && e.touches.length > 0) {
				touchEndX = e.touches[0].clientX;
				touchEndY = e.touches[0].clientY;
			}
		}, { passive: true });

		$heroShowcase.addEventListener('touchend', function(e) {
			var diffX = touchEndX - touchStartX;
			var diffY = touchEndY - touchStartY;

			// Ensure it is a horizontal swipe (horizontal movement greater than vertical)
			if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > minSwipeDistance) {
				if (diffX < 0) {
					// Swiped Left -> Next Drama
					switchHeroDrama((currentHeroIdx + 1) % totalThumbs);
				} else {
					// Swiped Right -> Previous Drama
					switchHeroDrama((currentHeroIdx - 1 + totalThumbs) % totalThumbs);
				}
				restartHeroTimer();
			}
		}, { passive: true });
	}

	// 2. Horizontal Content Row Navigation Arrows (Left & Right)
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

	function bindTrackArrowEvents(container) {
		if (!container || container._arrowsBound) return;
		var track = container.querySelector('.reel-cards-track');
		var prevBtn = container.querySelector('.reel-track-arrow.prev');
		var nextBtn = container.querySelector('.reel-track-arrow.next');

		if (prevBtn && track) {
			prevBtn.addEventListener('click', function(e) {
				e.preventDefault();
				var scrollAmount = Math.max(280, Math.floor(track.clientWidth * 0.75));
				track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
			});
		}

		if (nextBtn && track) {
			nextBtn.addEventListener('click', function(e) {
				e.preventDefault();
				var scrollAmount = Math.max(280, Math.floor(track.clientWidth * 0.75));
				track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
			});
		}

		if (track) {
			track.addEventListener('scroll', function() {
				updateTrackArrowsVisibility(track, prevBtn, nextBtn);
			}, { passive: true });
			updateTrackArrowsVisibility(track, prevBtn, nextBtn);
		}
		container._arrowsBound = true;
	}

	document.querySelectorAll('.reel-carousel-container').forEach(function(container) {
		bindTrackArrowEvents(container);
	});

	window.addEventListener('resize', function() {
		document.querySelectorAll('.reel-carousel-container').forEach(function(container) {
			var track = container.querySelector('.reel-cards-track');
			var prevBtn = container.querySelector('.reel-track-arrow.prev');
			var nextBtn = container.querySelector('.reel-track-arrow.next');
			updateTrackArrowsVisibility(track, prevBtn, nextBtn);
		});
	});

	// 3. Floating Controls (App Download & Back to Top)
	var $btnDownload = document.getElementById('reel-btn-download-app');
	if ($btnDownload) {
		$btnDownload.addEventListener('click', function(e) {
			e.preventDefault();
			if (typeof window.shortPromptPwaInstall === 'function') {
				window.shortPromptPwaInstall();
			} else if (typeof window.shortCustomAlert === 'function') {
				window.shortCustomAlert({
					title: 'Install ShortTV App',
					message: 'To install the ShortTV App, tap your browser menu and select "Install app" or "Add to Home Screen".',
					confirmText: 'Got It'
				});
			} else {
				alert('To install the ShortTV App, tap your browser menu and select "Install app" or "Add to Home Screen".');
			}
		});
	}

	var $btnTop = document.getElementById('reel-btn-top');
	if ($btnTop) {
		$btnTop.addEventListener('click', function() {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}

	function fetchUserCloudHistory(uid, callback) {
		if (!uid) return;
		var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';
		var results = {};
		var pending = 2;

		function onPartDone() {
			pending--;
			if (pending <= 0) {
				if (results && Object.keys(results).length > 0) {
					try {
						localStorage.setItem('shorttv_history_' + uid, JSON.stringify(results));
						localStorage.setItem('short_continue_watching_' + uid + '_' + activeProfile, JSON.stringify(results));
					} catch(e) {}
				} else {
					try {
						localStorage.setItem('shorttv_history_' + uid, JSON.stringify({}));
						localStorage.setItem('short_continue_watching_' + uid + '_' + activeProfile, JSON.stringify({}));
					} catch(e) {}
				}
				if (typeof callback === 'function') callback(results);
			}
		}

		// 1. Firestore query (continue_watching + watch_history)
		if (typeof firebase !== 'undefined' && firebase.firestore) {
			try {
				var proRef = firebase.firestore().collection('users').doc(uid).collection('profiles').doc(activeProfile);
				Promise.all([
					proRef.collection('continue_watching').get().catch(function(){ return { forEach: function(){} }; }),
					proRef.collection('watch_history').get().catch(function(){ return { forEach: function(){} }; })
				]).then(function(snaps) {
					snaps.forEach(function(snap) {
						if (snap && typeof snap.forEach === 'function') {
							snap.forEach(function(doc) {
								if (doc.exists && doc.data()) {
									results[doc.id] = Object.assign({ id: doc.id }, doc.data());
								}
							});
						}
					});
					onPartDone();
				}).catch(function() {
					onPartDone();
				});
			} catch(e) {
				onPartDone();
			}
		} else {
			onPartDone();
		}

		// 2. RTDB query (Direct REST fetch for guaranteed immediate response)
		var rtdbBase = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
		fetch(rtdbBase + '/users/' + uid + '/history.json')
			.then(function(r) { return r.json(); })
			.then(function(rtdbData) {
				if (rtdbData && typeof rtdbData === 'object') {
					Object.assign(results, rtdbData);
				}
				onPartDone();
			})
			.catch(function() {
				onPartDone();
			});
	}

	function normalizeMediaUrl(url, dramaId) {
		if (window.SHORT_CONFIG && window.SHORT_CONFIG.drama_map && dramaId && window.SHORT_CONFIG.drama_map[String(dramaId)] && window.SHORT_CONFIG.drama_map[String(dramaId)].poster) {
			return window.SHORT_CONFIG.drama_map[String(dramaId)].poster;
		}
		if (!url || typeof url !== 'string') {
			return (window.SHORT_CONFIG && window.SHORT_CONFIG.fallback_poster) ? window.SHORT_CONFIG.fallback_poster : '<?php echo esc_js( short_get_default_poster_url() ); ?>';
		}
		url = url.trim();
		if (!url) {
			return (window.SHORT_CONFIG && window.SHORT_CONFIG.fallback_poster) ? window.SHORT_CONFIG.fallback_poster : '<?php echo esc_js( short_get_default_poster_url() ); ?>';
		}
		
		var siteUrl = (window.SHORT_CONFIG && window.SHORT_CONFIG.site_url) ? window.SHORT_CONFIG.site_url : window.location.origin;

		var wpIdx = url.indexOf('/wp-content/');
		if (wpIdx !== -1) {
			return siteUrl.replace(/\/+$/, '') + url.substring(wpIdx);
		}

		if (url.startsWith('/')) {
			return siteUrl.replace(/\/+$/, '') + '/' + url.replace(/^\/+/, '');
		}

		try {
			var parsed = new URL(url, window.location.origin);
			if ((parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1' || parsed.hostname === '0.0.0.0') && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
				return siteUrl.replace(/\/+$/, '') + parsed.pathname + parsed.search;
			}
		} catch(e) {}

		return url;
	}

	// 4. Live Sync Continue Watching / Watch History (Isolated Local storage for Guests, Cloud UID-synced for logged in)
	function syncContinueWatchingRow() {
		try {
			var uid = window._cwUid || (localStorage.getItem('short_is_logged_in') === '1' ? localStorage.getItem('short_user_uid') : null);
			var isLoggedIn = (localStorage.getItem('short_is_logged_in') === '1') || !!uid;
			var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';
			var storage = {};

			if (isLoggedIn && uid) {
				// Signed in: ONLY load this user's UID history and profile cache
				try {
					var rawUid = localStorage.getItem('shorttv_history_' + uid);
					if (rawUid) Object.assign(storage, JSON.parse(rawUid) || {});
				} catch(e) {}
				try {
					var rawScoped = localStorage.getItem('short_continue_watching_' + uid + '_' + activeProfile);
					if (rawScoped) Object.assign(storage, JSON.parse(rawScoped) || {});
				} catch(e) {}
			} else if (!isLoggedIn) {
				// Guest: ONLY load guest watch history
				try {
					var rawGuest = localStorage.getItem('short_guest_watch_history') || localStorage.getItem('short_continue_watching_guest_' + activeProfile);
					if (rawGuest) Object.assign(storage, JSON.parse(rawGuest) || {});
				} catch(e) {}
			}

			var cwItems = Object.values(storage).filter(function(item){
				return item && (item.post_id || item.tmdb_id || item.id || item.series_id) && (item.title || item.poster || item.poster_path || item.watchUrl || item.url);
			});

			// Deduplicate by drama ID
			var uniqueItems = {};
			cwItems.forEach(function(item) {
				var dId = String(item.post_id || item.tmdb_id || item.id || item.series_id);
				if (!uniqueItems[dId] || (item.watchedAt || item.updatedAt || item.updated_at || 0) >= (uniqueItems[dId].watchedAt || uniqueItems[dId].updatedAt || uniqueItems[dId].updated_at || 0)) {
					uniqueItems[dId] = item;
				}
			});
			var finalItems = Object.values(uniqueItems);

			// Sort newest watched first
			finalItems.sort(function(a, b) {
				var timeA = a.watchedAt || a.updatedAt || a.updated_at || 0;
				var timeB = b.watchedAt || b.updatedAt || b.updated_at || 0;
				return timeB - timeA;
			});

			var rows = document.querySelectorAll('.reel-continue-watching-row');
			if (!finalItems || finalItems.length === 0) {
				rows.forEach(function(row){
					row.style.setProperty('display', 'none', 'important');
				});
				return;
			}

			rows.forEach(function(row){
				var rowTrack = row.querySelector('.reel-cards-track');
				var carousel = row.querySelector('.reel-carousel-container');
				var prevBtn = row.querySelector('.reel-track-arrow.prev');
				var nextBtn = row.querySelector('.reel-track-arrow.next');
				if (!rowTrack) return;
				row.style.removeProperty('display');
				row.style.display = 'block';
				rowTrack.innerHTML = '';

				finalItems.forEach(function(item){
					var dramaId = String(item.post_id || item.tmdb_id || item.id || item.series_id);
					var liveDrama = (window.SHORT_CONFIG && window.SHORT_CONFIG.drama_map && window.SHORT_CONFIG.drama_map[dramaId]) || {};
					var title = liveDrama.title || item.title || 'Short Drama';
					var rawPoster = liveDrama.poster || item.poster || item.poster_path || item.cover || '';
					var poster = normalizeMediaUrl(rawPoster, dramaId);
					var ep = item.lastEpisode || item.episode || item.last_episode || item.season || 1;
					var totalEps = parseInt(item.totalEpisodes || item.total_episodes || item.episodes_count || 1, 10);
					var watchUrl = liveDrama.watch_url || item.watchUrl || item.watch_url || item.url || ('<?php echo esc_url( home_url( '/watch/' ) ); ?>' + dramaId + '/?episode=' + ep);
					var pct = Math.min(99, Math.max(5, Math.round(item.percent || (totalEps > 1 ? (ep / totalEps) * 100 : (item.currentTime && item.duration ? (item.currentTime / item.duration) * 100 : 35)))));

					var fallbackImg = (window.SHORT_CONFIG && window.SHORT_CONFIG.fallback_poster) ? window.SHORT_CONFIG.fallback_poster : '<?php echo esc_js( short_get_default_poster_url() ); ?>';
					var card = document.createElement('div');
					card.className = 'reel-card-item reel-cw-card';
					card.setAttribute('data-series-id', dramaId);
					card.style.cssText = 'flex:0 0 186px; width:186px;';
					card.innerHTML = '<a href="' + watchUrl + '" style="text-decoration:none; color:inherit; display:block;">' +
						'<div class="reel-poster-box" style="position:relative; aspect-ratio:2/3; border-radius:12px; overflow:hidden; background:#18181b; box-shadow:0 8px 20px rgba(0,0,0,0.5);">' +
							'<img src="' + (poster || fallbackImg) + '" alt="' + title + '" style="width:100%; height:100%; object-fit:cover; display:block;" loading="lazy" onerror="this.onerror=null; this.src=\'' + fallbackImg + '\';">' +
							'<div class="reel-cw-badge-wrap" style="position:absolute; top:8px; left:8px; z-index:3;">' +
								'<span class="reel-cw-resume-badge" style="background:var(--theme-accent, #e11d48); color:#ffffff; font-size:9.5px; font-weight:800; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.3px; display:inline-flex; align-items:center; gap:3px; box-shadow:0 2px 6px rgba(var(--theme-accent-rgb, 225,29,72),0.45);">' +
									'<svg width="7" height="7" viewBox="0 0 24 24" fill="#ffffff"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>' +
									'<span>RESUME</span>' +
								'</span>' +
							'</div>' +
							'<div style="position:absolute; bottom:0; left:0; right:0; z-index:3; background:linear-gradient(0deg, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.5) 60%, transparent 100%); padding:18px 10px 8px 10px;">' +
								'<div style="display:flex; align-items:center; justify-content:space-between; font-size:10.5px; font-weight:700; color:#f1f5f9; margin-bottom:5px;">' +
									'<span class="reel-cw-ep-badge" style="display:inline-flex; align-items:center; gap:3px;">' +
										'<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#f43f5e" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>' +
										'<span class="reel-cw-ep-text">Ep. ' + ep + '</span>' +
									'</span>' +
									'<span class="reel-cw-pct-text" style="color:#fb7185; font-weight:800;">' + pct + '%</span>' +
								'</div>' +
								'<div class="reel-cw-progress-track" style="width:100%; height:4px; background:rgba(255,255,255,0.22); border-radius:999px; overflow:hidden;">' +
									'<div class="reel-cw-progress-fill" style="width:' + pct + '%; height:100%; background:var(--theme-accent, #e11d48); border-radius:999px; box-shadow:0 0 8px rgba(var(--theme-accent-rgb, 225,29,72),0.9);"></div>' +
								'</div>' +
							'</div>' +
						'</div>' +
						'<div style="padding:10px 4px 4px 4px;">' +
							'<div style="font-size:14px; font-weight:800; color:#ffffff; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + title + '</div>' +
						'</div>' +
					'</a>';
					rowTrack.appendChild(card);
				});

				if (carousel) {
					bindTrackArrowEvents(carousel);
				}
				setTimeout(function() {
					updateTrackArrowsVisibility(rowTrack, prevBtn, nextBtn);
				}, 50);
			});
		} catch(e) {}
	}

	window.syncContinueWatchingRow = syncContinueWatchingRow;
	syncContinueWatchingRow();

	var curInitUid = localStorage.getItem('short_user_uid');
	if (curInitUid && localStorage.getItem('short_is_logged_in') === '1') {
		fetchUserCloudHistory(curInitUid, function() {
			syncContinueWatchingRow();
		});
	}

	if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
		firebase.auth().onAuthStateChanged(function(user) {
			window._cwUid = user ? user.uid : null;
			if (user && user.uid) {
				fetchUserCloudHistory(user.uid, function() {
					syncContinueWatchingRow();
				});
			} else {
				syncContinueWatchingRow();
			}
		});
	}
});
</script>

<?php
get_footer();
