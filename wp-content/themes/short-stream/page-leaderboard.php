<?php
/**
 * Template Name: Drama Leaderboard & Power Rankings
 *
 * @package Short_Stream
 */

get_header();

// 1. Fetch short drama series
$dramas_query = new WP_Query( array(
	'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
	'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
	'posts_per_page' => 100,
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

		$raw_back = $db_item['cover_assets']['horizontal_banner'] ?? '';
		if ( empty( $raw_back ) && $post_id ) {
			$raw_back = get_post_meta( $post_id, '_shorttv_horizontal_banner', true );
		}
		if ( empty( $raw_back ) ) {
			$raw_back = $raw_poster;
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
		$raw_rating_val = $post_id ? get_post_meta( $post_id, '_shorttv_rating', true ) : '';
		$rating_count   = (int) ( ( $post_id ? get_post_meta( $post_id, '_shorttv_rating_count', true ) : 0 ) ?: ( $db_item['rating_count'] ?? ( $db_item['vote_count'] ?? 0 ) ) );
		if ( $raw_rating_val !== '' && is_numeric( $raw_rating_val ) ) {
			$rating_val = (float) $raw_rating_val;
		} elseif ( ! empty( $db_item['rating'] ) && is_numeric( $db_item['rating'] ) ) {
			$rating_val = (float) $db_item['rating'];
		} elseif ( ! empty( $db_item['vote_average'] ) && is_numeric( $db_item['vote_average'] ) ) {
			$rating_val = (float) $db_item['vote_average'];
		} elseif ( ! empty( $db_item['analytics']['rating'] ) && is_numeric( $db_item['analytics']['rating'] ) ) {
			$rating_val = (float) $db_item['analytics']['rating'];
		} elseif ( $rating_count > 0 ) {
			$rating_val = 5.0;
		} else {
			$rating_val = 0.0;
		}
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

		$db_formatted[] = array(
			'id'             => $post_id ?: ( $db_item['media_id'] ?? 'db_' . $post_id ),
			'post_id'        => $post_id,
			'title'          => $db_item['title'] ?? 'Short Drama',
			'tag'            => $tag,
			'trope'          => $trope,
			'genres'         => $genres_list,
			'rating_val'     => $rating_val,
			'rating_count'   => $rating_count,
			'episodes_count' => $episodes_count,
			'views_num'      => $views_num,
			'views_str'      => $views_fmt,
			'likes_num'      => $likes_num,
			'comments_num'   => $comments_num,
			'date_ts'        => $date_ts,
			'overview'       => $db_item['synopsis'] ?? ( $db_item['overview'] ?? '' ),
			'poster'         => $poster ?: get_template_directory_uri() . '/assets/images/placeholder-portrait.jpg',
			'backdrop'       => $backdrop ?: ( $poster ?: get_template_directory_uri() . '/assets/images/placeholder-backdrop.jpg' ),
			'watch_url'      => home_url( '/watch/' . ( $post_id ?: ( $db_item['media_id'] ?? 1 ) ) ),
			'is_vip'         => $is_vip,
		);
	}
}

// Deduplicate
$unique_dramas = array();
$seen = array();
foreach ( $db_formatted as $d ) {
	$k = strtolower( trim( $d['title'] ) );
	if ( ! isset( $seen[ $k ] ) ) {
		$seen[ $k ] = true;
		$unique_dramas[] = $d;
	}
}

if ( empty( $unique_dramas ) && function_exists( 'short_get_curated_fallback_dramas' ) ) {
	$unique_dramas = short_get_curated_fallback_dramas();
}

// Current Filter Sort Mode
$current_sort = sanitize_key( $_GET['sort'] ?? 'all' );
$pool = $unique_dramas;

if ( 'vip' === $current_sort ) {
	$pool = array_filter( $pool, function( $d ) {
		return ! empty( $d['is_vip'] );
	} );
}

if ( 'views' === $current_sort || 'popular' === $current_sort ) {
	usort( $pool, function( $a, $b ) {
		if ( $a['views_num'] === $b['views_num'] ) {
			return $b['likes_num'] <=> $a['likes_num'];
		}
		return $b['views_num'] <=> $a['views_num'];
	} );
} elseif ( 'rating' === $current_sort ) {
	usort( $pool, function( $a, $b ) {
		if ( $a['rating_val'] == $b['rating_val'] ) {
			if ( $a['rating_count'] == $b['rating_count'] ) {
				return $b['views_num'] <=> $a['views_num'];
			}
			return $b['rating_count'] <=> $a['rating_count'];
		}
		return ( $b['rating_val'] > $a['rating_val'] ) ? 1 : -1;
	} );
} elseif ( 'latest' === $current_sort ) {
	usort( $pool, function( $a, $b ) {
		return $b['date_ts'] <=> $a['date_ts'];
	} );
} elseif ( 'episodes' === $current_sort ) {
	usort( $pool, function( $a, $b ) {
		$score_a = ( $a['episodes_count'] * 50 ) + ( $a['comments_num'] * 20 ) + $a['likes_num'];
		$score_b = ( $b['episodes_count'] * 50 ) + ( $b['comments_num'] * 20 ) + $b['likes_num'];
		return $score_b <=> $score_a;
	} );
} else {
	// Default: Power Score (Rating + Views + Likes)
	usort( $pool, function( $a, $b ) {
		$score_a = ( $a['rating_count'] > 0 ? ( $a['rating_val'] * 1000 + $a['rating_count'] * 50 ) : 0 ) + ( $a['likes_num'] * 2 ) + ( $a['views_num'] * 0.01 );
		$score_b = ( $b['rating_count'] > 0 ? ( $b['rating_val'] * 1000 + $b['rating_count'] * 50 ) : 0 ) + ( $b['likes_num'] * 2 ) + ( $b['views_num'] * 0.01 );
		return $score_b <=> $score_a;
	} );
}

$ranked_items = array_values( $pool );
$top5 = array_slice( $ranked_items, 0, 5 );
$rest = array_slice( $ranked_items, 5, 25 );

$podium_ranks = array(
	0 => array( 'rank' => 1, 'label' => '👑 TOP 1', 'badge_bg' => 'linear-gradient(135deg, #ffd700 0%, #f59e0b 100%)', 'badge_color' => '#000000', 'border' => '2px solid rgba(255,215,0,0.7)', 'glow' => '0 12px 35px rgba(245,158,11,0.32)' ),
	1 => array( 'rank' => 2, 'label' => '🥈 TOP 2', 'badge_bg' => 'linear-gradient(135deg, #e2e8f0 0%, #94a3b8 100%)', 'badge_color' => '#0f172a', 'border' => '1.5px solid rgba(226,232,240,0.45)', 'glow' => '0 8px 22px rgba(0,0,0,0.45)' ),
	2 => array( 'rank' => 3, 'label' => '🥉 TOP 3', 'badge_bg' => 'linear-gradient(135deg, #d97706 0%, #92400e 100%)', 'badge_color' => '#ffffff', 'border' => '1.5px solid rgba(217,119,6,0.45)', 'glow' => '0 8px 22px rgba(0,0,0,0.45)' ),
	3 => array( 'rank' => 4, 'label' => '⭐ TOP 4', 'badge_bg' => 'linear-gradient(135deg, #38bdf8 0%, #0284c7 100%)', 'badge_color' => '#ffffff', 'border' => '1.5px solid rgba(56,189,248,0.35)', 'glow' => '0 6px 18px rgba(0,0,0,0.4)' ),
	4 => array( 'rank' => 5, 'label' => '🔥 TOP 5', 'badge_bg' => 'linear-gradient(135deg, #ec4899 0%, #be185d 100%)', 'badge_color' => '#ffffff', 'border' => '1.5px solid rgba(236,72,153,0.35)', 'glow' => '0 6px 18px rgba(0,0,0,0.4)' ),
);

$sort_tabs = array(
	'all'      => array( 'label' => __( '🏆 Top Ranked', 'short-stream' ),    'url' => home_url( '/leaderboard/' ) ),
	'views'    => array( 'label' => __( '🔥 Most Viewed', 'short-stream' ),    'url' => home_url( '/leaderboard/?sort=views' ) ),
	'rating'   => array( 'label' => __( '⭐ Highest Rated', 'short-stream' ),  'url' => home_url( '/leaderboard/?sort=rating' ) ),
	'episodes' => array( 'label' => __( '📺 Binge Leaders', 'short-stream' ),  'url' => home_url( '/leaderboard/?sort=episodes' ) ),
	'vip'      => array( 'label' => __( '👑 VIP Exclusives', 'short-stream' ),'url' => home_url( '/leaderboard/?sort=vip' ) ),
);

$top_drama_1 = $top5[0] ?? ( $ranked_items[0] ?? null );
$top_bg_img  = ! empty( $top_drama_1['backdrop'] ) ? $top_drama_1['backdrop'] : ( $top_drama_1['poster'] ?? '' );
?>

<div class="short-leaderboard-page-wrapper">
	<?php if ( ! empty( $top_bg_img ) ) : ?>
		<div class="short-leaderboard-ambient-bg" style="background-image: url('<?php echo esc_url( $top_bg_img ); ?>');"></div>
		<div class="short-leaderboard-ambient-overlay"></div>
	<?php endif; ?>

	<!-- Sticky Mobile Top Navigation (< Leaderboard) - ZERO BORDER -->
	<div class="leaderboard-mobile-top-navbar">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="lb-mobile-nav-back" title="<?php esc_attr_e( 'Back', 'short-stream' ); ?>">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
		</a>
		<h1 class="lb-mobile-nav-title"><?php _e( 'Leaderboard', 'short-stream' ); ?></h1>
		<div style="width:38px;"></div>
	</div>

	<div class="short-leaderboard-container">
		
		<!-- ═══════════════════════════════════════════════════════════════════════════ -->
		<!-- 1. LEADERBOARD HERO BANNER & FILTER TABS                                    -->
		<!-- ═══════════════════════════════════════════════════════════════════════════ -->
		<div class="leaderboard-header-banner">
			<div class="lb-banner-text-col">
				<div class="lb-banner-meta-row">
					<span class="lb-badge-official">👑 OFFICIAL RANKINGS</span>
				</div>
				<h2 class="lb-banner-title">
					<span>Drama Leaderboard</span>
					<span class="lb-trophy">🏆</span>
				</h2>
				<p class="lb-banner-desc">
					<?php _e( 'Discover the top-ranked short dramas & popularity leaderboards voted worldwide.', 'short-stream' ); ?>
				</p>
			</div>

			<!-- Filter Pills -->
			<div class="leaderboard-filter-pills">
				<?php foreach ( $sort_tabs as $k => $tab_data ) : 
					$is_tab_active = ( $current_sort === $k );
				?>
					<a href="<?php echo esc_url( $tab_data['url'] ); ?>" class="lb-filter-pill <?php echo $is_tab_active ? 'active' : ''; ?>">
						<?php echo esc_html( $tab_data['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- ═══════════════════════════════════════════════════════════════════════════ -->
		<!-- 2. TOP 5 DRAMAS PODIUM SPOTLIGHT                                            -->
		<!-- ═══════════════════════════════════════════════════════════════════════════ -->
		<?php if ( ! empty( $top5 ) ) : ?>
		<div class="leaderboard-spotlight-section" style="margin-bottom:44px;">
			<div class="leaderboard-section-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
				<h2 class="leaderboard-section-title" style="font-size:20px; font-weight:900; margin:0; color:#ffffff; display:flex; align-items:center; gap:8px;">
					<span>Top Dramas Spotlight</span>
					<span class="leaderboard-range-badge spotlight-badge-desktop" style="font-size:13px; font-weight:800; white-space:nowrap; padding:2px 8px; border-radius:999px; background:rgba(255,215,0,0.15); color:#ffd700; border:1px solid rgba(255,215,0,0.3);">👑 1–5</span>
					<span class="leaderboard-range-badge spotlight-badge-mobile" style="display:none; font-size:11px; font-weight:800; white-space:nowrap; padding:2px 8px; border-radius:999px; background:rgba(255,215,0,0.15); color:#ffd700; border:1px solid rgba(255,215,0,0.3);">👑 TOP 3</span>
				</h2>
				<span class="leaderboard-range-sub" style="font-size:12.5px; color:#64748b; font-weight:600; white-space:nowrap;">Ranked #1 — #5</span>
			</div>

			<div class="reel-leaderboard-podium-grid">
				<?php foreach ( $top5 as $p_idx => $p_drama ) : 
					$meta = $podium_ranks[ $p_idx ] ?? $podium_ranks[0];
				?>
					<div class="reel-podium-card rank-<?php echo $meta['rank']; ?>" style="<?php echo $meta['border'] ? 'border:' . $meta['border'] . ';' : ''; ?> box-shadow:<?php echo $meta['glow']; ?>;">
						<a href="<?php echo esc_url( $p_drama['watch_url'] ); ?>" class="reel-podium-link">
							<div class="reel-podium-poster-wrap">
								<img src="<?php echo esc_url( $p_drama['poster'] ); ?>" alt="<?php echo esc_attr( $p_drama['title'] ); ?>" loading="lazy">
								<div class="reel-podium-rank-badge" style="background:<?php echo $meta['badge_bg']; ?>; color:<?php echo $meta['badge_color']; ?>;">
									<?php echo esc_html( $meta['label'] ); ?>
								</div>
								<div class="reel-podium-score-pill">
									★ <?php echo number_format( (float) $p_drama['rating_val'], 1 ); ?>
								</div>
								<div class="reel-podium-play-btn">
									<svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><polygon points="6 3 20 12 6 21 6 3"></polygon></svg>
								</div>
								
								<div class="reel-podium-overlay">
									<div class="reel-podium-meta-top">
										<span class="reel-podium-genre"><?php echo esc_html( $p_drama['trope'] ?: ( $p_drama['genres'][0] ?? 'Drama' ) ); ?></span>
										<span class="reel-podium-eps"><?php echo (int) $p_drama['episodes_count']; ?> Eps</span>
									</div>
									<h3 class="reel-podium-title"><?php echo esc_html( $p_drama['title'] ); ?></h3>
									<div class="reel-podium-stats">
										<span class="reel-podium-heat">🔥 <?php echo esc_html( $p_drama['views_str'] ); ?> views</span>
										<span class="reel-podium-watch-cta">Watch Now ▶</span>
									</div>
								</div>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<!-- ═══════════════════════════════════════════════════════════════════════════ -->
		<!-- 3. POWER RANKINGS BOARD LIST (RANKS 4+ ON MOBILE, 6+ ON DESKTOP)             -->
		<!-- ═══════════════════════════════════════════════════════════════════════════ -->
		<?php 
		$ranks_4_5 = array_slice( $ranked_items, 3, 2 );
		if ( ! empty( $rest ) || ! empty( $ranks_4_5 ) ) : 
		?>
		<div class="leaderboard-power-section">
			<div class="leaderboard-section-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:18px;">
				<h2 class="leaderboard-section-title" style="font-size:20px; font-weight:900; margin:0; color:#ffffff; display:flex; align-items:center; gap:8px;">
					<span>Power Rankings Board</span>
					<span style="font-size:16px;">📊</span>
				</h2>
				<span class="leaderboard-range-sub" style="font-size:12.5px; color:#64748b; font-weight:600; white-space:nowrap;">Ranked #6 — #<?php echo count( $rest ) + 5; ?></span>
			</div>

			<div class="reel-leaderboard-ranked-list">
				<?php 
				// Ranks 4 & 5: Rendered here for mobile/tablet where podium only shows Top 3
				foreach ( $ranks_4_5 as $r45_idx => $r_drama ) : 
					$cur_rank = $r45_idx + 4;
				?>
					<a href="<?php echo esc_url( $r_drama['watch_url'] ); ?>" class="reel-ranked-row-item reel-ranked-item-mobile-only">
						<!-- LEFT DIVISION: Rank + Thumbnail -->
						<div class="reel-ranked-left-col">
							<div class="reel-ranked-num"><?php echo $cur_rank; ?></div>
							<div class="reel-ranked-thumb">
								<img src="<?php echo esc_url( $r_drama['poster'] ); ?>" alt="<?php echo esc_attr( $r_drama['title'] ); ?>" loading="lazy">
							</div>
						</div>

						<!-- RIGHT DIVISION: Content Stack -->
						<div class="reel-ranked-right-col">
							<h4 class="reel-ranked-title"><?php echo esc_html( $r_drama['title'] ); ?></h4>
							
							<div class="reel-ranked-line reel-ranked-meta-line">
								<span class="reel-ranked-tag"><?php echo esc_html( $r_drama['trope'] ?: ( $r_drama['genres'][0] ?? 'Drama' ) ); ?></span>
								<span class="reel-ranked-dot">•</span>
								<span class="reel-ranked-eps"><?php echo (int) $r_drama['episodes_count']; ?> Episodes</span>
							</div>

							<div class="reel-ranked-line reel-ranked-stats-line">
								<span class="reel-ranked-views">🔥 <?php echo esc_html( $r_drama['views_str'] ); ?> views</span>
								<span class="reel-ranked-dot">•</span>
								<span class="reel-ranked-score-badge">★ <?php echo number_format( (float) $r_drama['rating_val'], 1 ); ?></span>
							</div>

							<div class="reel-ranked-btn-row">
								<button type="button" class="reel-btn-play-mini" tabindex="-1">
									<svg class="reel-play-icon-svg" width="12" height="12" viewBox="0 0 24 24" fill="#000000" style="display:inline-block; vertical-align:middle; margin-right:2px;"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
									<span>Play</span>
								</button>
							</div>
						</div>
					</a>
				<?php endforeach; ?>

				<?php foreach ( $rest as $r_idx => $r_drama ) : 
					$cur_rank = $r_idx + 6;
				?>
					<a href="<?php echo esc_url( $r_drama['watch_url'] ); ?>" class="reel-ranked-row-item">
						<!-- LEFT DIVISION: Rank + Thumbnail -->
						<div class="reel-ranked-left-col">
							<div class="reel-ranked-num"><?php echo $cur_rank; ?></div>
							<div class="reel-ranked-thumb">
								<img src="<?php echo esc_url( $r_drama['poster'] ); ?>" alt="<?php echo esc_attr( $r_drama['title'] ); ?>" loading="lazy">
							</div>
						</div>

						<!-- RIGHT DIVISION: Content Stack -->
						<div class="reel-ranked-right-col">
							<h4 class="reel-ranked-title"><?php echo esc_html( $r_drama['title'] ); ?></h4>
							
							<div class="reel-ranked-line reel-ranked-meta-line">
								<span class="reel-ranked-tag"><?php echo esc_html( $r_drama['trope'] ?: ( $r_drama['genres'][0] ?? 'Drama' ) ); ?></span>
								<span class="reel-ranked-dot">•</span>
								<span class="reel-ranked-eps"><?php echo (int) $r_drama['episodes_count']; ?> Episodes</span>
							</div>

							<div class="reel-ranked-line reel-ranked-stats-line">
								<span class="reel-ranked-views">🔥 <?php echo esc_html( $r_drama['views_str'] ); ?> views</span>
								<span class="reel-ranked-dot">•</span>
								<span class="reel-ranked-score-badge">★ <?php echo number_format( (float) $r_drama['rating_val'], 1 ); ?></span>
							</div>

							<div class="reel-ranked-btn-row">
								<button type="button" class="reel-btn-play-mini" tabindex="-1">
									<svg class="reel-play-icon-svg" width="12" height="12" viewBox="0 0 24 24" fill="#000000" style="display:inline-block; vertical-align:middle; margin-right:2px;"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
									<span>Play</span>
								</button>
							</div>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( empty( $ranked_items ) ) : ?>
			<div style="text-align:center; padding: 80px 20px; color:#94a3b8;">
				<div style="font-size:48px; margin-bottom:14px;">🏆</div>
				<h2 style="color:#ffffff; font-size:22px; font-weight:800; margin-bottom:8px;"><?php esc_html_e( 'No Ranked Dramas Yet', 'short-stream' ); ?></h2>
				<p style="font-size:14px; max-width:420px; margin:0 auto 20px auto; line-height:1.6; color:#94a3b8;">
					<?php esc_html_e( 'Dramas and user ranking metrics will appear here once videos are uploaded.', 'short-stream' ); ?>
				</p>
			</div>
		<?php endif; ?>

	</div>
</div>

<style>
/* ─────────────────────────────────────────────────────────────────────────────
   LEADERBOARD STYLES (PORTRAIT 5-CARD SPOTLIGHT & POWER BOARD LIST)
───────────────────────────────────────────────────────────────────────────── */
.short-leaderboard-page-wrapper {
	position: relative;
	background: var(--bg-app, #121212);
	background-color: var(--bg-app, #121212);
	min-height: 100vh;
	color: #ffffff;
	padding-top: 105px;
	padding-bottom: 80px;
	overflow-x: hidden;
	width: 100%;
	box-sizing: border-box;
}

.short-leaderboard-ambient-bg {
	position: fixed;
	top: -5%;
	left: -5%;
	width: 110vw;
	height: 110vh;
	background-position: center top;
	background-size: cover;
	background-repeat: no-repeat;
	filter: blur(12px) saturate(1.25) brightness(0.65);
	opacity: 0.38;
	pointer-events: none;
	z-index: 0;
	transform: translateZ(0);
}

.short-leaderboard-ambient-overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100vw;
	height: 100vh;
	background: linear-gradient(180deg, rgba(var(--bg-app-rgb, 18, 18, 18), 0.45) 0%, rgba(var(--bg-app-rgb, 18, 18, 18), 0.78) 45%, rgba(var(--bg-app-rgb, 18, 18, 18), 0.95) 85%, var(--bg-app, #121212) 100%);
	pointer-events: none;
	z-index: 1;
}

.short-leaderboard-container {
	position: relative;
	z-index: 2;
	max-width: 1420px;
	margin: 0 auto;
	padding: 0 24px;
	width: 100%;
	box-sizing: border-box;
}

.lb-filter-pill {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	font-size: 13px;
	font-weight: 700;
	padding: 8px 18px;
	border-radius: 999px;
	text-decoration: none;
	background: rgba(255, 255, 255, 0.06);
	color: #cbd5e1;
	border: 1px solid rgba(255, 255, 255, 0.12);
	transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.lb-filter-pill:hover {
	background: rgba(255, 255, 255, 0.15);
	color: #ffffff;
	border-color: rgba(255, 255, 255, 0.25);
	transform: translateY(-1px);
}
.lb-filter-pill.active {
	background: #ffffff;
	color: #0b0b0f;
	border: 1px solid #ffffff;
	font-weight: 800;
	box-shadow: none !important;
}
.lb-filter-pill.active:hover {
	background: #f1f5f9;
	color: #000000;
	transform: translateY(-1px);
	box-shadow: none !important;
}

.reel-leaderboard-podium-grid {
	display: flex;
	justify-content: center;
	align-items: flex-end;
	gap: 20px;
	margin: 28px auto 44px;
	max-width: 100%;
	width: 100%;
	box-sizing: border-box;
}

.reel-podium-card {
	background: #111116;
	border-radius: 14px;
	overflow: hidden;
	position: relative;
	flex: 1 1 0px;
	max-width: 250px;
	min-width: 0;
	box-sizing: border-box;
	transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.35s ease;
}

/* 5-Card visual ladder: [Rank 4] < [Rank 2] < [👑 Rank 1] > [Rank 3] > [Rank 5] */
.reel-podium-card.rank-1 {
	order: 3;
	max-width: 270px;
	transform: translateY(-24px);
	z-index: 5;
}
.reel-podium-card.rank-1:hover {
	transform: translateY(-32px);
}
.reel-podium-card.rank-1 .reel-podium-poster-wrap {
	aspect-ratio: 9 / 14.2;
}

.reel-podium-card.rank-2 {
	order: 2;
	max-width: 252px;
	transform: translateY(-12px);
	z-index: 4;
}
.reel-podium-card.rank-2:hover {
	transform: translateY(-18px);
}
.reel-podium-card.rank-2 .reel-podium-poster-wrap {
	aspect-ratio: 9 / 13.5;
}

.reel-podium-card.rank-3 {
	order: 4;
	max-width: 252px;
	transform: translateY(-12px);
	z-index: 4;
}
.reel-podium-card.rank-3:hover {
	transform: translateY(-18px);
}
.reel-podium-card.rank-3 .reel-podium-poster-wrap {
	aspect-ratio: 9 / 13.5;
}

.reel-podium-card.rank-4 {
	order: 1;
	max-width: 232px;
	transform: translateY(0);
	z-index: 2;
	opacity: 0.94;
}
.reel-podium-card.rank-4:hover {
	transform: translateY(-6px);
	opacity: 1;
}
.reel-podium-card.rank-4 .reel-podium-poster-wrap {
	aspect-ratio: 9 / 12.8;
}

.reel-podium-card.rank-5 {
	order: 5;
	max-width: 232px;
	transform: translateY(0);
	z-index: 2;
	opacity: 0.94;
}
.reel-podium-card.rank-5:hover {
	transform: translateY(-6px);
	opacity: 1;
}
.reel-podium-card.rank-5 .reel-podium-poster-wrap {
	aspect-ratio: 9 / 12.8;
}

.reel-podium-link {
	text-decoration: none;
	color: inherit;
	display: block;
	position: relative;
	width: 100%;
}

.reel-podium-poster-wrap {
	position: relative;
	width: 100%;
	overflow: hidden;
	background: #0a0a0e;
}
.reel-podium-poster-wrap img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center center;
	transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.reel-podium-card:hover .reel-podium-poster-wrap img {
	transform: scale(1.06);
}

.reel-podium-rank-badge {
	position: absolute;
	top: 10px;
	left: 10px;
	font-size: 11px;
	font-weight: 900;
	padding: 3.5px 9px;
	border-radius: 999px;
	letter-spacing: 0.3px;
	text-transform: uppercase;
	box-shadow: 0 4px 12px rgba(0,0,0,0.6);
	z-index: 3;
}

.reel-podium-score-pill {
	position: absolute;
	top: 10px;
	right: 10px;
	background: rgba(0, 0, 0, 0.75);
	backdrop-filter: blur(8px);
	-webkit-backdrop-filter: blur(8px);
	color: #fbbf24;
	font-size: 10.5px;
	font-weight: 800;
	padding: 3px 7px;
	border-radius: 6px;
	border: 1px solid rgba(251, 191, 36, 0.35);
	box-shadow: 0 4px 10px rgba(0,0,0,0.4);
	z-index: 3;
}

.reel-podium-play-btn {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%) scale(0.85);
	width: 50px;
	height: 50px;
	border-radius: 50%;
	background: rgba(0, 0, 0, 0.75);
	backdrop-filter: blur(6px);
	-webkit-backdrop-filter: blur(6px);
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2px solid rgba(255, 255, 255, 0.9);
	opacity: 0;
	transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
	z-index: 4;
}
.reel-podium-play-btn svg {
	margin-left: 3px;
}
.reel-podium-card:hover .reel-podium-play-btn {
	opacity: 1;
	transform: translate(-50%, -50%) scale(1);
	background: #ff2d55;
	border-color: #ff2d55;
	box-shadow: 0 0 20px rgba(255, 45, 85, 0.8);
}

.reel-podium-overlay {
	position: absolute;
	inset: auto 0 0 0;
	padding: 32px 14px 14px;
	background: linear-gradient(180deg, rgba(10, 10, 14, 0) 0%, rgba(10, 10, 14, 0.8) 32%, rgba(10, 10, 14, 0.98) 100%);
	display: flex;
	flex-direction: column;
	gap: 4px;
	z-index: 2;
}

.reel-podium-meta-top {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 10.5px;
	color: #94a3b8;
	font-weight: 700;
}
.reel-podium-genre {
	color: #38bdf8;
	text-transform: uppercase;
	letter-spacing: 0.4px;
	font-size: 10px;
}
.reel-podium-eps {
	color: #94a3b8;
	font-size: 10.5px;
}

.reel-podium-title {
	font-size: 14px;
	font-weight: 800;
	color: #ffffff;
	margin: 1px 0 2px 0;
	line-height: 1.25;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-shadow: 0 2px 4px rgba(0,0,0,0.8);
}

.reel-podium-stats {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 11px;
	color: #cbd5e1;
	font-weight: 600;
	padding-top: 4px;
	border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.reel-podium-heat {
	color: #f59e0b;
	font-weight: 700;
}
.reel-podium-watch-cta {
	color: #ffffff;
	font-size: 10.5px;
	font-weight: 700;
	opacity: 0.85;
}
.reel-podium-card:hover .reel-podium-watch-cta {
	color: #ffd700;
	opacity: 1;
}

/* ─────────────────────────────────────────────────────────────────────────────
   RANKED BOARD LIST (#6 to #30) - 2 DIVISION DESIGN
───────────────────────────────────────────────────────────────────────────── */
.reel-leaderboard-ranked-list {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 18px;
}

.reel-ranked-item-mobile-only {
	display: none !important;
}

.spotlight-badge-mobile {
	display: none !important;
}
.spotlight-badge-desktop {
	display: inline-block !important;
}

.reel-ranked-row-item {
	display: flex;
	align-items: stretch;
	gap: 16px;
	background: #14141a;
	border: none;
	border-radius: 14px;
	padding: 14px;
	text-decoration: none;
	color: inherit;
	box-sizing: border-box;
	transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
.reel-ranked-row-item:hover {
	background: #1c1c24;
	transform: translateY(-3px);
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.55);
}

/* Division 1: Left (Rank + Big Thumbnail) */
.reel-ranked-left-col {
	display: flex;
	align-items: center;
	gap: 12px;
	flex-shrink: 0;
}

.reel-ranked-num {
	font-size: 20px;
	font-weight: 900;
	color: #64748b;
	width: 24px;
	text-align: center;
	flex-shrink: 0;
	letter-spacing: -0.5px;
}
.reel-ranked-row-item:hover .reel-ranked-num {
	color: #ffd700;
}

.reel-ranked-thumb {
	width: 86px;
	height: 120px;
	border-radius: 8px;
	overflow: hidden;
	flex-shrink: 0;
	background: #0a0a0e;
	box-shadow: 0 4px 14px rgba(0,0,0,0.5);
}
.reel-ranked-thumb img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	object-position: center;
	transition: transform 0.35s ease;
}
.reel-ranked-row-item:hover .reel-ranked-thumb img {
	transform: scale(1.06);
}

/* Division 2: Right (Text Stack) */
.reel-ranked-right-col {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	gap: 6px;
}

.reel-ranked-title {
	font-size: 15px;
	font-weight: 800;
	color: #f8fafc;
	margin: 0;
	line-height: 1.3;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
.reel-ranked-row-item:hover .reel-ranked-title {
	color: #ffffff;
}

.reel-ranked-line {
	display: flex;
	align-items: center;
	gap: 6px;
	font-size: 12px;
	color: #94a3b8;
	flex-wrap: wrap;
}

.reel-ranked-meta-line .reel-ranked-tag {
	color: #38bdf8;
	font-weight: 700;
	font-size: 11px;
	text-transform: uppercase;
	letter-spacing: 0.4px;
}
.reel-ranked-meta-line .reel-ranked-eps {
	color: #cbd5e1;
	font-weight: 600;
}

.reel-ranked-stats-line .reel-ranked-views {
	color: #f59e0b;
	font-weight: 700;
}

.reel-ranked-score-badge {
	font-size: 11px;
	font-weight: 800;
	color: #fbbf24;
	background: rgba(251, 191, 36, 0.14);
	border: none;
	padding: 1.5px 6px;
	border-radius: 4px;
	display: inline-flex;
	align-items: center;
}

.reel-ranked-dot {
	color: #475569;
	font-size: 9px;
}

.reel-ranked-btn-row {
	margin-top: 2px;
}
.reel-btn-play-mini {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	background: #ffffff;
	color: #000000;
	border: none;
	border-radius: 999px;
	padding: 5px 14px;
	font-size: 12px;
	font-weight: 800;
	cursor: pointer;
	transition: all 0.18s;
}
.reel-ranked-row-item:hover .reel-btn-play-mini {
	background: #ffd700;
	box-shadow: 0 0 12px rgba(255, 215, 0, 0.55);
	transform: scale(1.04);
}

/* ─────────────────────────────────────────────────────────────────────────────
   MOBILE RESPONSIVENESS
───────────────────────────────────────────────────────────────────────────── */
@media (max-width: 1200px) {
	.reel-leaderboard-ranked-list {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 1080px) {
	.reel-leaderboard-podium-grid {
		display: flex;
		justify-content: center;
		align-items: flex-end;
		gap: 14px;
	}
	.reel-podium-card.rank-4,
	.reel-podium-card.rank-5 {
		display: none !important;
	}
	.reel-podium-card:hover,
	.reel-podium-card.rank-2:hover,
	.reel-podium-card.rank-3:hover,
	.reel-podium-card.rank-4:hover,
	.reel-podium-card.rank-5:hover {
		transform: none !important;
	}
	.reel-podium-card.rank-1:hover {
		transform: translateY(-4px) !important;
	}
	.reel-ranked-item-mobile-only {
		display: flex !important;
	}
	.spotlight-badge-desktop {
		display: none !important;
	}
	.spotlight-badge-mobile {
		display: inline-block !important;
	}
}

@media (max-width: 768px) {
	.reel-leaderboard-ranked-list {
		grid-template-columns: 1fr;
	}
	.short-leaderboard-page-wrapper {
		padding-top: calc(env(safe-area-inset-top, 0px) + 72px) !important;
		padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 75px) !important;
		background: var(--bg-app, #121212) !important;
		background-color: var(--bg-app, #121212) !important;
	}
	.short-leaderboard-container {
		padding: 0 14px !important;
	}
	/* Compact Mobile Section Headers */
	.leaderboard-spotlight-section {
		margin-bottom: 20px !important;
	}
	.leaderboard-power-section {
		margin-bottom: 24px !important;
	}
	.leaderboard-section-header {
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		margin-bottom: 12px !important;
		gap: 6px !important;
		flex-wrap: nowrap !important;
	}
	.leaderboard-section-title {
		font-size: 13.5px !important;
		font-weight: 800 !important;
		margin: 0 !important;
		display: flex !important;
		align-items: center !important;
		gap: 5px !important;
		white-space: nowrap !important;
	}
	.leaderboard-range-badge {
		font-size: 9.5px !important;
		padding: 1.5px 5.5px !important;
		white-space: nowrap !important;
		line-height: 1.2 !important;
		flex-shrink: 0 !important;
	}
	.leaderboard-range-sub {
		display: none !important;
	}
	.reel-leaderboard-podium-grid {
		display: flex !important;
		flex-direction: row !important;
		justify-content: center !important;
		gap: 8px !important;
		margin: 6px auto 20px !important;
	}
	.reel-podium-card {
		min-width: 0 !important;
		flex: 1 !important;
		border-radius: 10px !important;
		transition: box-shadow 0.25s ease !important;
	}
	.reel-podium-card.rank-1 {
		order: 2 !important;
		transform: translateY(-4px) !important;
	}
	.reel-podium-card.rank-2 {
		order: 1 !important;
		transform: none !important;
	}
	.reel-podium-card.rank-3 {
		order: 3 !important;
		transform: none !important;
	}
	/* Disable excessive upward jumping hover transitions on mobile to avoid overlapping header */
	.reel-podium-card:hover,
	.reel-podium-card.rank-2:hover,
	.reel-podium-card.rank-3:hover,
	.reel-podium-card.rank-4:hover,
	.reel-podium-card.rank-5:hover {
		transform: none !important;
	}
	.reel-podium-card.rank-1:hover {
		transform: translateY(-4px) !important;
	}
	.reel-podium-card:hover .reel-podium-poster-wrap img {
		transform: scale(1.02) !important;
	}
	.reel-podium-play-btn {
		width: 32px !important;
		height: 32px !important;
	}
	.reel-podium-play-btn svg {
		width: 13px !important;
		height: 13px !important;
	}
	/* Compact Card Labels & Badges */
	.reel-podium-rank-badge {
		font-size: 7.5px !important;
		font-weight: 800 !important;
		padding: 2px 4.5px !important;
		top: 5px !important;
		left: 5px !important;
		letter-spacing: 0.1px !important;
	}
	.reel-podium-score-pill {
		font-size: 7.5px !important;
		font-weight: 800 !important;
		padding: 1.5px 4.5px !important;
		top: 5px !important;
		right: 5px !important;
	}
	.reel-podium-overlay {
		padding: 16px 6px 6px !important;
		gap: 2px !important;
	}
	.reel-podium-meta-top {
		font-size: 8px !important;
		gap: 3px !important;
		margin-bottom: 1px !important;
	}
	.reel-podium-genre {
		font-size: 8px !important;
		letter-spacing: 0.2px !important;
		white-space: nowrap !important;
		overflow: hidden !important;
		text-overflow: ellipsis !important;
		max-width: 60% !important;
	}
	.reel-podium-eps {
		font-size: 8px !important;
		white-space: nowrap !important;
		flex-shrink: 0 !important;
	}
	.reel-podium-title {
		font-size: 10px !important;
		font-weight: 800 !important;
		line-height: 1.25 !important;
		margin: 1px 0 !important;
		-webkit-line-clamp: 2 !important;
	}
	.reel-podium-stats {
		padding-top: 2px !important;
		font-size: 8.5px !important;
	}
	.reel-podium-heat {
		font-size: 8.5px !important;
		white-space: nowrap !important;
	}
	.reel-podium-watch-cta {
		display: none !important;
	}
	.leaderboard-header-banner {
		padding: 14px 14px !important;
		margin-bottom: 16px !important;
		border-radius: 12px !important;
		gap: 10px !important;
	}
	.leaderboard-header-banner div:first-child > div:first-child {
		font-size: 9px !important;
		padding: 2px 7px !important;
		margin-bottom: 5px !important;
	}
	.leaderboard-header-banner h1 {
		font-size: 19px !important;
		margin: 0 0 3px 0 !important;
	}
	.leaderboard-header-banner h1 span:last-child {
		font-size: 18px !important;
	}
	.leaderboard-header-banner p {
		font-size: 11.5px !important;
		line-height: 1.35 !important;
		display: -webkit-box !important;
		-webkit-line-clamp: 2 !important;
		-webkit-box-orient: vertical !important;
		overflow: hidden !important;
		margin-bottom: 8px !important;
	}
	.leaderboard-filter-pills {
		display: flex !important;
		flex-wrap: nowrap !important;
		overflow-x: auto !important;
		scrollbar-width: none !important;
		-ms-overflow-style: none !important;
		-webkit-overflow-scrolling: touch !important;
		gap: 6px !important;
		padding: 3px 0 !important;
		width: 100% !important;
	}
	.leaderboard-filter-pills::-webkit-scrollbar {
		display: none !important;
	}
	.lb-filter-pill {
		font-size: 11.5px !important;
		padding: 5px 12px !important;
		white-space: nowrap !important;
		flex-shrink: 0 !important;
		border-radius: 999px !important;
	}

	header#short-header,
	.short-header,
	.site-header,
	.reel-header,
	.short-mobile-genre-bar,
	.mobile-genre-bar,
	.genre-bar-scroll-wrapper {
		display: none !important;
		height: 0 !important;
		min-height: 0 !important;
		max-height: 0 !important;
		padding: 0 !important;
		margin: 0 !important;
		overflow: hidden !important;
		pointer-events: none !important;
		visibility: hidden !important;
	}

	.leaderboard-mobile-top-navbar {
		position: fixed !important;
		top: 0 !important;
		left: 0 !important;
		right: 0 !important;
		z-index: 999 !important;
		height: 54px !important;
		background: var(--bg-header, var(--bg-app, #121212)) !important;
		background-color: var(--bg-header, var(--bg-app, #121212)) !important;
		backdrop-filter: blur(16px) !important;
		-webkit-backdrop-filter: blur(16px) !important;
		border: none !important;
		border-bottom: none !important;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4) !important;
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		padding: env(safe-area-inset-top, 0px) 16px 0 16px !important;
		margin: 0 !important;
		box-sizing: content-box !important;
	}

	.lb-mobile-nav-back {
		background: transparent !important;
		border: none !important;
		color: #ffffff !important;
		width: 38px !important;
		height: 38px !important;
		display: inline-flex !important;
		align-items: center !important;
		justify-content: flex-start !important;
		cursor: pointer !important;
		padding: 0 !important;
	}

	.lb-mobile-nav-title {
		font-size: 19px !important;
		font-weight: 800 !important;
		color: #ffffff !important;
		margin: 0 !important;
		letter-spacing: -0.3px !important;
		text-align: center !important;
		flex: 1 !important;
	}
}

/* ═══════════════════════════════════════════════════════════════════════════
   DESKTOP HERO BANNER & FILTER PILLS
═══════════════════════════════════════════════════════════════════════════ */
.leaderboard-header-banner {
	margin-bottom: 34px;
	background: linear-gradient(135deg, rgba(255, 215, 0, 0.08) 0%, rgba(20, 20, 28, 0.85) 100%) !important;
	backdrop-filter: blur(14px);
	-webkit-backdrop-filter: blur(14px);
	border: 1px solid rgba(255, 215, 0, 0.2);
	border-radius: 18px;
	padding: 24px 30px;
	display: flex;
	justify-content: space-between;
	align-items: center;
	flex-wrap: wrap;
	gap: 18px;
	position: relative;
	z-index: 2;
}

.lb-banner-meta-row {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 8px;
}

.lb-badge-official {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	background: linear-gradient(135deg, #ffd700 0%, #f59e0b 100%);
	color: #000000;
	font-size: 11px;
	font-weight: 900;
	text-transform: uppercase;
	letter-spacing: 0.8px;
	padding: 3px 10px;
	border-radius: 999px;
}

.lb-badge-live {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	background: rgba(239, 68, 68, 0.15);
	color: #f87171;
	border: 1px solid rgba(239, 68, 68, 0.35);
	font-size: 10.5px;
	font-weight: 800;
	letter-spacing: 0.5px;
	padding: 2px 8px;
	border-radius: 999px;
}

.lb-live-dot {
	width: 6px;
	height: 6px;
	border-radius: 50%;
	background: #ef4444;
	box-shadow: 0 0 6px #ef4444;
	display: inline-block;
}

.lb-banner-title {
	font-size: 28px;
	font-weight: 900;
	margin: 0 0 5px 0;
	color: #ffffff;
	letter-spacing: -0.5px;
	display: flex;
	align-items: center;
	gap: 8px;
}

.lb-banner-desc {
	margin: 0;
	font-size: 13.5px;
	color: #94a3b8;
	max-width: 580px;
	line-height: 1.45;
}

.leaderboard-filter-pills {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-wrap: wrap;
}

@media (max-width: 768px) {
	.leaderboard-header-banner {
		padding: 16px !important;
		margin-top: 10px !important;
		margin-bottom: 20px !important;
		gap: 12px !important;
	}

	.lb-banner-meta-row {
		margin-bottom: 6px !important;
		gap: 6px !important;
	}

	.lb-badge-official {
		font-size: 9.5px !important;
		padding: 2px 8px !important;
	}

	.lb-badge-live {
		font-size: 9px !important;
		padding: 2px 6px !important;
	}

	.lb-banner-title {
		font-size: 20px !important;
		margin-bottom: 4px !important;
	}

	.lb-banner-desc {
		font-size: 12px !important;
		line-height: 1.35 !important;
		color: #94a3b8 !important;
		display: block !important;
	}

	.leaderboard-filter-pills {
		width: 100% !important;
		margin-top: 4px !important;
	}
}

@media (min-width: 769px) {
	.leaderboard-mobile-top-navbar {
		display: none !important;
		visibility: hidden !important;
		height: 0 !important;
		pointer-events: none !important;
	}
}

/* Footer High-Visibility Styles */
.short-footer {
	background: var(--bg-primary, #121212) !important;
	color: #94a3b8 !important;
	position: relative !important;
	z-index: 2 !important;
}
.short-footer .footer-tagline {
	color: #94a3b8 !important;
}
.short-footer .footer-col h4 {
	color: #ffffff !important;
	font-weight: 700 !important;
}
.short-footer .footer-col a {
	color: #cbd5e1 !important;
	text-decoration: none !important;
	transition: color 0.15s ease !important;
}
.short-footer .footer-col a:hover {
	color: #ffffff !important;
}
.short-footer .footer-bottom-bar p {
	color: #64748b !important;
}
</style>

<?php
get_footer();
