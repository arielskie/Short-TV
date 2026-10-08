<?php
/**
 * ShortTV Genre & Categories Browse Page Template
 *
 * @package Short_Stream
 */

get_header();

// 1. Identify Requested Genre Slug or ID
$raw_slug = get_query_var( 'short_genre_slug' );
if ( empty( $raw_slug ) && ! empty( $_GET['genre'] ) ) {
	$raw_slug = sanitize_text_field( $_GET['genre'] );
}
if ( empty( $raw_slug ) ) {
	$queried = get_queried_object();
	if ( $queried instanceof WP_Term ) {
		$raw_slug = $queried->slug;
	}
}
if ( empty( $raw_slug ) ) {
	$term_var = get_query_var( 'video_genre' ) ?: get_query_var( 'term' );
	if ( ! empty( $term_var ) ) {
		$raw_slug = $term_var;
	}
}
if ( empty( $raw_slug ) ) {
	$req_path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '', '/' );
	$site_path = trim( parse_url( home_url(), PHP_URL_PATH ) ?: '', '/' );
	if ( $site_path && 0 === strpos( $req_path, $site_path ) ) {
		$req_path = trim( substr( $req_path, strlen( $site_path ) ), '/' );
	}
	$req_path = preg_replace( '#^index\.php/?#i', '', $req_path );
	if ( 0 === strpos( $req_path, 'genre/' ) ) {
		$raw_slug = trim( substr( $req_path, strlen( 'genre/' ) ), '/' );
	}
}
$genre_slug = strtolower( trim( (string) $raw_slug ) );

// 2. Identify Sort Order (views, rating, latest, episodes)
$sort_param = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'views';
if ( ! in_array( $sort_param, array( 'views', 'rating', 'latest', 'episodes', 'vip' ), true ) ) {
	$sort_param = 'views';
}

// 3. Query all ShortTV Dramas
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

// Format Drama Items
$all_formatted = array();
$genre_counts  = array();
$unique_seen   = array();

foreach ( $db_dramas as $db_item ) {
	$post_id = $db_item['post_id'] ?? 0;
	if ( ! $post_id && ! empty( $db_item['slug'] ) ) {
		$post_obj = get_page_by_path( $db_item['slug'], OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
		if ( $post_obj ) $post_id = $post_obj->ID;
	}

	$title = ! empty( $db_item['title'] ) ? $db_item['title'] : ( get_the_title( $post_id ) ?: 'Short Drama' );
	$title_key = strtolower( trim( $title ) );
	if ( isset( $unique_seen[ $title_key ] ) ) {
		continue;
	}
	$unique_seen[ $title_key ] = true;

	$raw_poster = $db_item['cover_assets']['vertical_poster'] ?? '';
	if ( empty( $raw_poster ) && $post_id ) {
		$raw_poster = get_post_meta( $post_id, '_shorttv_vertical_poster', true );
	}
	if ( empty( $raw_poster ) && $post_id && has_post_thumbnail( $post_id ) ) {
		$raw_poster = get_the_post_thumbnail_url( $post_id, 'full' );
	}
	$poster = function_exists( 'short_normalize_url' ) ? short_normalize_url( $raw_poster ) : $raw_poster;

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

	$genres_list = ! empty( $db_item['genres'] ) ? (array) $db_item['genres'] : array();
	if ( $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'video_genre', array( 'fields' => 'names' ) );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			$terms = wp_get_post_terms( $post_id, 'genre', array( 'fields' => 'names' ) );
		}
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$genres_list = array_unique( array_merge( $genres_list, $terms ) );
		}
	}
	if ( empty( $genres_list ) && ! empty( $db_item['trope'] ) ) {
		$genres_list = array( $db_item['trope'] );
	}

	$tag     = ! empty( $db_item['tag'] ) ? $db_item['tag'] : 'Trending';
	$trope   = ! empty( $genres_list[0] ) ? $genres_list[0] : ( $db_item['trope'] ?? 'Romance' );
	$subline = ! empty( $genres_list ) ? implode( ' | ', $genres_list ) : 'Series | Romance';

	// Record counts per genre
	foreach ( $genres_list as $g ) {
		$g_norm = strtolower( trim( $g ) );
		$g_slug_key = sanitize_title( $g );
		if ( ! empty( $g_norm ) ) {
			$genre_counts[ $g_norm ] = ( $genre_counts[ $g_norm ] ?? 0 ) + 1;
		}
		if ( ! empty( $g_slug_key ) && $g_slug_key !== $g_norm ) {
			$genre_counts[ $g_slug_key ] = ( $genre_counts[ $g_slug_key ] ?? 0 ) + 1;
		}
	}

	$is_vip = false;
	if ( ! empty( $db_item['is_vip'] ) ) {
		$is_vip = true;
	} elseif ( $post_id && '1' === (string) get_post_meta( $post_id, '_shorttv_is_vip', true ) ) {
		$is_vip = true;
	} elseif ( ! empty( $db_item['episodes'] ) && is_array( $db_item['episodes'] ) ) {
		foreach ( $db_item['episodes'] as $ep_data ) {
			$u_t = strtolower( $ep_data['access_control']['unlock_type'] ?? '' );
			if ( 'vip' === $u_t ) {
				$is_vip = true;
				break;
			}
		}
	}

	$all_formatted[] = array(
		'id'             => $post_id ?: ( $db_item['media_id'] ?? 'db_' . $post_id ),
		'post_id'        => $post_id,
		'title'          => $title,
		'slug'           => $db_item['slug'] ?? 'short-drama',
		'tag'            => $tag,
		'trope'          => $trope,
		'genres'         => $genres_list,
		'subline'        => $subline,
		'is_vip'         => $is_vip,
		'overview'       => $db_item['overview'] ?? ( $post_id ? get_post_field( 'post_excerpt', $post_id ) : '' ),
		'views_num'      => $views_num,
		'views_str'      => $views_fmt,
		'likes_num'      => $likes_num,
		'comments_num'   => $comments_num,
		'rating_val'     => $rating_val,
		'rating_count'   => $rating_count,
		'episodes_count' => $episodes_count,
		'date_ts'        => $date_ts,
		'poster'         => $poster ?: 'https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=600&auto=format&fit=crop&q=80',
		'badge'          => function_exists( 'short_calculate_drama_badge' ) ? short_calculate_drama_badge( $is_vip, $date_ts, $views_num, $likes_num ) : ( $is_vip ? 'VIP' : '' ),
		'watch_url'      => home_url( '/watch/' . ( $post_id ?: '' ) ),
	);
}

// Fallback to high quality curated dramas if no custom dramas exist
if ( empty( $all_formatted ) && function_exists( 'short_get_curated_fallback_dramas' ) ) {
	$all_formatted = short_get_curated_fallback_dramas();
	foreach ( $all_formatted as $fb_item ) {
		foreach ( (array) ( $fb_item['genres'] ?? array() ) as $g ) {
			$g_norm = strtolower( trim( $g ) );
			$g_slug_key = sanitize_title( $g );
			if ( ! empty( $g_norm ) ) {
				$genre_counts[ $g_norm ] = ( $genre_counts[ $g_norm ] ?? 0 ) + 1;
			}
			if ( ! empty( $g_slug_key ) && $g_slug_key !== $g_norm ) {
				$genre_counts[ $g_slug_key ] = ( $genre_counts[ $g_slug_key ] ?? 0 ) + 1;
			}
		}
	}
}

// 4. Curate Genre Tabs — reads admin config from Genre Filter Tabs Manager
$genre_tabs_config = function_exists( 'get_option' ) ? get_option( 'short_genre_filter_tabs', array() ) : array();
$tabs_auto_mode    = empty( $genre_tabs_config ) || ! empty( $genre_tabs_config['auto'] );
$admin_tabs        = ! empty( $genre_tabs_config['tabs'] ) ? $genre_tabs_config['tabs'] : array();
$auto_sort         = $genre_tabs_config['auto_sort'] ?? 'count_desc';

// Always include "All Dramas" first
$available_genres = array(
	'all' => array( 'title' => __( 'All Dramas', 'short-stream' ), 'emoji' => '🔥' ),
);

if ( $tabs_auto_mode || empty( $admin_tabs ) ) {
	// AUTO MODE: show only genres that have actual posts (dynamic from $genre_counts)
	$known_genres_map = array(
		'billionaire' => array( 'title' => 'Billionaire',       'emoji' => '💎' ),
		'throne'      => array( 'title' => 'Throne',            'emoji' => '👑' ),
		'superpower'  => array( 'title' => 'Superpower',        'emoji' => '⚡' ),
		'big-shot'    => array( 'title' => 'Big Shot',          'emoji' => '🕶️' ),
		'ceo'         => array( 'title' => 'CEO',               'emoji' => '💼' ),
		'romance'     => array( 'title' => 'Romance',           'emoji' => '💕' ),
		'fantasy'     => array( 'title' => 'Fantasy',           'emoji' => '✨' ),
		'vampire'     => array( 'title' => 'Vampire',           'emoji' => '🧛' ),
		'werewolf'    => array( 'title' => 'Werewolf',          'emoji' => '🐺' ),
		'revenge'     => array( 'title' => 'Revenge',           'emoji' => '🔥' ),
		'urban'       => array( 'title' => 'Urban',             'emoji' => '🏙️' ),
		'modern'      => array( 'title' => 'Modern',            'emoji' => '🌆' ),
		'suspense'    => array( 'title' => 'Suspense',          'emoji' => '🔍' ),
		'action'      => array( 'title' => 'Action',            'emoji' => '💥' ),
		'drama'       => array( 'title' => 'Drama',             'emoji' => '🎭' ),
	);
	// Also merge any admin-configured tabs into the known map (for emoji/label)
	foreach ( $admin_tabs as $atab ) {
		$aslug = sanitize_title( $atab['slug'] ?? '' );
		if ( $aslug && ! isset( $known_genres_map[ $aslug ] ) ) {
			$known_genres_map[ $aslug ] = array( 'title' => $atab['label'] ?? ucwords( $aslug ), 'emoji' => $atab['emoji'] ?? '🎬' );
		} elseif ( $aslug ) {
			// Admin override of emoji/label takes priority
			$known_genres_map[ $aslug ] = array( 'title' => $atab['label'] ?? $known_genres_map[$aslug]['title'], 'emoji' => $atab['emoji'] ?? $known_genres_map[$aslug]['emoji'] );
		}
	}

	// Build genre entries (only genres with posts > 0)
	$auto_genre_entries = array(); // slug => ['title', 'emoji', 'count']
	foreach ( $genre_counts as $g_key => $g_count ) {
		if ( $g_count > 0 ) {
			$info = isset( $known_genres_map[ $g_key ] )
				? $known_genres_map[ $g_key ]
				: array( 'title' => ucwords( str_replace( '-', ' ', $g_key ) ), 'emoji' => '🎬' );
			$auto_genre_entries[ $g_key ] = array_merge( $info, array( 'count' => $g_count ) );
		}
	}

	// Apply sort order
	switch ( $auto_sort ) {
		case 'count_asc':
			uasort( $auto_genre_entries, function( $a, $b ) { return $a['count'] <=> $b['count']; } );
			break;
		case 'alpha_asc':
			uasort( $auto_genre_entries, function( $a, $b ) { return strcasecmp( $a['title'], $b['title'] ); } );
			break;
		case 'alpha_desc':
			uasort( $auto_genre_entries, function( $a, $b ) { return strcasecmp( $b['title'], $a['title'] ); } );
			break;
		case 'count_desc':
		default:
			uasort( $auto_genre_entries, function( $a, $b ) { return $b['count'] <=> $a['count']; } );
			break;
	}

	// Add sorted genres to $available_genres (after "All Dramas")
	foreach ( $auto_genre_entries as $g_key => $entry ) {
		$available_genres[ $g_key ] = array( 'title' => $entry['title'], 'emoji' => $entry['emoji'] );
	}
} else {
	// MANUAL MODE: use exactly the admin-configured tabs list (enabled only)
	foreach ( $admin_tabs as $atab ) {
		if ( empty( $atab['enabled'] ) ) continue;
		$aslug = sanitize_title( $atab['slug'] ?? '' );
		if ( ! $aslug ) continue;
		$available_genres[ $aslug ] = array(
			'title' => sanitize_text_field( $atab['label'] ?? ucwords( $aslug ) ),
			'emoji' => sanitize_text_field( $atab['emoji'] ?? '🎬' ),
		);
	}
}

// Active Genre Details
$is_all_genre   = empty( $genre_slug ) || 'all' === $genre_slug;
$active_g_info  = ! $is_all_genre && isset( $available_genres[ $genre_slug ] ) ? $available_genres[ $genre_slug ] : ( $available_genres['all'] ?? array( 'title' => 'All Dramas', 'emoji' => '🔥' ) );
$current_title  = $is_all_genre ? __( 'All Short Dramas', 'short-stream' ) : sprintf( __( '%s Dramas', 'short-stream' ), $active_g_info['title'] );

// 5. Filter Dramas by Selected Genre
$filtered_dramas = array();
if ( $is_all_genre ) {
	$filtered_dramas = $all_formatted;
} else {
	foreach ( $all_formatted as $d ) {
		$matches = false;
		foreach ( $d['genres'] as $g ) {
			$g_norm = strtolower( trim( $g ) );
			$g_slug_norm = sanitize_title( $g );
			if ( $g_norm === $genre_slug || $g_slug_norm === $genre_slug || false !== strpos( $g_norm, $genre_slug ) ) {
				$matches = true;
				break;
			}
		}
		if ( ! $matches && strtolower( trim( $d['trope'] ) ) === $genre_slug ) {
			$matches = true;
		}
		if ( $matches ) {
			$filtered_dramas[] = $d;
		}
	}
}

// 6. Sort & Filter Dramas based on selected order
if ( 'vip' === $sort_param ) {
	// VIP Exclusives Only
	$filtered_dramas = array_values( array_filter( $filtered_dramas, function( $d ) {
		return ! empty( $d['is_vip'] );
	} ) );
	usort( $filtered_dramas, function( $a, $b ) {
		return $b['views_num'] <=> $a['views_num'];
	} );
	if ( $is_all_genre ) {
		$current_title          = __( 'VIP Exclusives', 'short-stream' );
		$active_g_info['emoji'] = '👑';
	}
} elseif ( 'views' === $sort_param ) {
	// Popular (Most Views)
	usort( $filtered_dramas, function( $a, $b ) {
		if ( $a['views_num'] === $b['views_num'] ) {
			return $b['likes_num'] <=> $a['likes_num'];
		}
		return $b['views_num'] <=> $a['views_num'];
	} );
} elseif ( 'rating' === $sort_param ) {
	// Top Rated
	usort( $filtered_dramas, function( $a, $b ) {
		$score_a = ( $a['rating_val'] * 100 ) + ( $a['rating_count'] * 15 ) + ( $a['views_num'] * 0.2 );
		$score_b = ( $b['rating_val'] * 100 ) + ( $b['rating_count'] * 15 ) + ( $b['views_num'] * 0.2 );
		return $score_b <=> $score_a;
	} );
} elseif ( 'latest' === $sort_param ) {
	// New Releases
	usort( $filtered_dramas, function( $a, $b ) {
		if ( $a['date_ts'] === $b['date_ts'] ) {
			return $b['post_id'] <=> $a['post_id'];
		}
		return $b['date_ts'] <=> $a['date_ts'];
	} );
} elseif ( 'episodes' === $sort_param ) {
	// Most Episodes / Comments
	usort( $filtered_dramas, function( $a, $b ) {
		$score_a = ( $a['episodes_count'] * 50 ) + ( $a['comments_num'] * 20 );
		$score_b = ( $b['episodes_count'] * 50 ) + ( $b['comments_num'] * 20 );
		return $score_b <=> $score_a;
	} );
}
?>

<div class="reel-genre-page">
	<div class="reel-genre-container">
		
		<!-- Header Area: Title & Sort Options -->
		<div class="reel-genre-header">
			<div class="reel-genre-title-group">
				<h1 class="reel-genre-title">
					<span><?php echo esc_html( $current_title ); ?></span>
					<span class="reel-genre-emoji"><?php echo esc_html( $active_g_info['emoji'] ?? '🎬' ); ?></span>
				</h1>
				<p class="reel-genre-count">
					<?php printf( esc_html__( 'Showing %d short dramas', 'short-stream' ), count( $filtered_dramas ) ); ?>
				</p>
			</div>

			<!-- Sorting Pills (Swipeable on Mobile) -->
			<div class="reel-sort-pills">
				<span class="reel-sort-label"><?php esc_html_e( 'Sort by:', 'short-stream' ); ?></span>
				<?php
				$sort_options = array(
					'views'    => array( 'label' => __( '🔥 Most Viewed', 'short-stream' ) ),
					'rating'   => array( 'label' => __( '⭐ Top Rated', 'short-stream' ) ),
					'latest'   => array( 'label' => __( '🚀 New Releases', 'short-stream' ) ),
					'episodes' => array( 'label' => __( '📺 Episodes', 'short-stream' ) ),
					'vip'      => array( 'label' => __( '👑 VIP Exclusives', 'short-stream' ) ),
				);
				$current_base_url = $is_all_genre ? home_url( '/genre/' ) : home_url( '/genre/' . $genre_slug . '/' );
				foreach ( $sort_options as $s_key => $s_opt ) :
					$s_url = add_query_arg( 'sort', $s_key, $current_base_url );
					$is_s_active = ( $sort_param === $s_key );
				?>
					<a href="<?php echo esc_url( $s_url ); ?>" class="reel-sort-btn <?php echo $is_s_active ? 'active' : ''; ?>">
						<?php echo esc_html( $s_opt['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Category Filter Pills Bar (Horizontal Swipe Track) -->
		<div class="reel-category-pills-bar">
			<div class="reel-pills-track">
				<?php foreach ( $available_genres as $g_slug_key => $g_meta ) : 
					$is_this_active = ( $is_all_genre && 'all' === $g_slug_key ) || ( ! $is_all_genre && $genre_slug === $g_slug_key );
					$tab_url = ( 'all' === $g_slug_key ) ? home_url( '/genre/' ) : home_url( '/genre/' . $g_slug_key . '/' );
					if ( 'views' !== $sort_param ) {
						$tab_url = add_query_arg( 'sort', $sort_param, $tab_url );
					}
					$count_badge = ( 'all' === $g_slug_key ) ? count( $all_formatted ) : ( $genre_counts[ $g_slug_key ] ?? 0 );
				?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="reel-cat-pill <?php echo $is_this_active ? 'active-cat-pill' : ''; ?>">
						<span class="pill-emoji"><?php echo esc_html( $g_meta['emoji'] ?? '🎬' ); ?></span>
						<span class="pill-title"><?php echo esc_html( $g_meta['title'] ); ?></span>
						<?php if ( $count_badge > 0 ) : ?>
							<span class="pill-badge"><?php echo $count_badge; ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Main 9:16 Vertical Grid -->
		<?php if ( ! empty( $filtered_dramas ) ) : ?>
			<div class="reel-grid-layout">
				<?php foreach ( $filtered_dramas as $drama ) : ?>
					<div class="reel-grid-card" data-series-id="<?php echo esc_attr( $drama['post_id'] ?? $drama['id'] ); ?>">
						<a href="<?php echo esc_url( $drama['watch_url'] ); ?>" class="reel-grid-link">
							<!-- 9:16 Vertical Poster Box -->
							<div class="reel-poster-box">
								<img src="<?php echo esc_url( $drama['poster'] ); ?>" alt="<?php echo esc_attr( $drama['title'] ); ?>" class="reel-poster-img" loading="lazy">
								
								<!-- Top-Left Badge -->
								<?php if ( ! empty( $drama['is_vip'] ) ) : ?>
									<div class="reel-badge-tl">
										<span class="is-vip-badge">
											👑 VIP
										</span>
									</div>
								<?php elseif ( ! empty( $drama['tag'] ) ) : ?>
									<div class="reel-badge-tl">
										<span class="reel-tag-badge">
											<?php echo esc_html( $drama['tag'] ); ?>
										</span>
									</div>
								<?php endif; ?>

								<!-- Top-Right Rating Badge -->
								<?php if ( ! empty( $drama['rating_val'] ) && (float) $drama['rating_val'] > 0 ) : ?>
									<div class="reel-card-rating">
										<span class="star-icon">★</span>
										<span class="rating-num"><?php echo number_format( (float) $drama['rating_val'], 1 ); ?></span>
									</div>
								<?php endif; ?>

								<!-- Bottom-Right View Count Badge -->
								<?php if ( ! empty( $drama['views_num'] ) && (int) $drama['views_num'] > 0 ) : ?>
									<div class="reel-card-views-badge">
										<svg width="10" height="10" viewBox="0 0 24 24" fill="#ffffff"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
										<span><?php echo esc_html( $drama['views_str'] ); ?></span>
									</div>
								<?php endif; ?>

								<!-- Bottom-Left Episodes Badge -->
								<?php if ( $drama['episodes_count'] > 1 ) : ?>
									<div class="reel-card-eps-badge">
										<?php echo sprintf( esc_html__( '%d Eps', 'short-stream' ), $drama['episodes_count'] ); ?>
									</div>
								<?php endif; ?>
							</div>

							<!-- Title Under Poster -->
							<h3 class="reel-card-title">
								<?php echo esc_html( $drama['title'] ); ?>
							</h3>

							<!-- Sub-line Metadata -->
							<div class="reel-card-subline">
								<?php echo esc_html( $drama['subline'] ); ?>
							</div>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="reel-empty-state">
				<div class="empty-icon">🎬</div>
				<h2 class="empty-title">
					<?php esc_html_e( 'No Dramas Found in this Genre', 'short-stream' ); ?>
				</h2>
				<p class="empty-desc">
					<?php esc_html_e( 'We currently do not have dramas in this category. Check out all available dramas or select another genre.', 'short-stream' ); ?>
				</p>
				<a href="<?php echo esc_url( home_url( '/genre/' ) ); ?>" class="btn-browse-all">
					<span><?php esc_html_e( 'Browse All Dramas', 'short-stream' ); ?></span>
				</a>
			</div>
		<?php endif; ?>

	</div>
</div>

<style>
/* Discover / Genre Page Core Styles */
.reel-genre-page {
	background: var(--bg-app, #121212);
	background-color: var(--bg-app, #121212);
	color: #ffffff;
	min-height: 100vh;
	padding: 105px 0 80px 0;
	box-sizing: border-box;
	width: 100%;
	overflow-x: hidden;
}
.reel-genre-container {
	max-width: 1440px;
	margin: 0 auto;
	padding: 0 32px !important;
	box-sizing: border-box;
	width: 100%;
}
.reel-genre-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	flex-wrap: wrap;
	gap: 16px;
	margin-bottom: 22px;
	padding-bottom: 16px;
	border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.reel-genre-title {
	font-size: clamp(1.5rem, 3vw, 2.2rem);
	font-weight: 900;
	margin: 0 0 4px 0;
	color: #ffffff;
	display: flex;
	align-items: center;
	gap: 8px;
}
.reel-genre-emoji {
	font-size: 24px;
}
.reel-genre-count {
	font-size: 13px;
	color: #94a3b8;
	margin: 0;
}
.reel-sort-pills {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-wrap: wrap;
}
.reel-sort-label {
	font-size: 13px;
	color: #64748b;
	font-weight: 700;
	margin-right: 4px;
}
.reel-sort-btn {
	display: inline-flex;
	align-items: center;
	padding: 6px 14px;
	border-radius: 999px;
	font-size: 12.5px;
	font-weight: 700;
	text-decoration: none;
	transition: all 0.15s ease;
	background: rgba(255, 255, 255, 0.08);
	color: #94a3b8;
	border: 1px solid rgba(255, 255, 255, 0.06);
	white-space: nowrap;
}
.reel-sort-btn.active {
	background: var(--theme-accent, #e11d48) !important;
	color: #ffffff !important;
	border-color: var(--theme-accent, #e11d48) !important;
	box-shadow: 0 2px 10px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.35);
}

/* Category Pills Bar */
.reel-category-pills-bar {
	margin-bottom: 26px;
}
.reel-pills-track {
	display: flex;
	gap: 10px;
	overflow-x: auto;
	scrollbar-width: none;
	-ms-overflow-style: none;
	padding: 4px 0 8px 0;
}
.reel-pills-track::-webkit-scrollbar {
	display: none;
}
.reel-cat-pill {
	display: inline-flex;
	align-items: center;
	gap: 7px;
	padding: 8px 18px;
	border-radius: 999px;
	font-size: 13.5px;
	font-weight: 800;
	white-space: nowrap;
	text-decoration: none;
	flex-shrink: 0;
	transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
	background: rgba(255, 255, 255, 0.06);
	color: #cbd5e1;
	border: 1.5px solid rgba(255, 255, 255, 0.12);
}
.reel-cat-pill.active-cat-pill {
	background: var(--theme-accent, #e11d48) !important;
	color: #ffffff !important;
	border-color: var(--theme-accent, #e11d48) !important;
	box-shadow: 0 4px 16px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.35);
}
.reel-cat-pill:hover {
	border-color: var(--theme-accent, #e11d48) !important;
	color: #ffffff !important;
}
.pill-badge {
	font-size: 11px;
	font-weight: 700;
	opacity: 0.85;
	padding: 1px 6px;
	border-radius: 999px;
	background: rgba(0, 0, 0, 0.35);
}

/* Grid layout */
.reel-grid-layout {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
	gap: 24px 18px;
}
.reel-grid-card {
	width: 100%;
}
.reel-grid-link {
	text-decoration: none;
	color: inherit;
	display: block;
}
.reel-poster-box {
	position: relative;
	aspect-ratio: 2 / 3 !important;
	border-radius: 10px;
	overflow: hidden;
	background: #18181b;
	box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
	transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
}
.reel-poster-box:hover {
	transform: translateY(-4px);
	box-shadow: 0 12px 28px rgba(0, 0, 0, 0.8) !important;
}
.reel-poster-img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
	transition: transform 0.3s ease;
}
.reel-poster-box:hover .reel-poster-img {
	transform: scale(1.05);
}
.reel-badge-tl {
	position: absolute;
	top: 8px;
	left: 8px;
	z-index: 2;
}
.reel-tag-badge {
	background: var(--theme-accent, #e11d48);
	color: #fff;
	font-size: 10px;
	font-weight: 800;
	padding: 2px 7px;
	border-radius: 4px;
	text-transform: uppercase;
	letter-spacing: 0.3px;
	display: inline-flex;
	align-items: center;
	white-space: nowrap;
}
.is-vip-badge {
	background: linear-gradient(135deg, #ff2d55 0%, #d97706 100%) !important;
	color: #ffffff !important;
	font-size: 10px;
	font-weight: 900;
	padding: 2px 7px;
	border-radius: 4px;
	display: inline-flex;
	align-items: center;
	gap: 3px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.6);
	letter-spacing: 0.4px;
	text-transform: uppercase;
	white-space: nowrap;
}
.reel-card-rating {
	position: absolute;
	top: 8px;
	right: 8px;
	z-index: 2;
	display: flex;
	align-items: center;
	gap: 3px;
	background: rgba(0, 0, 0, 0.7);
	backdrop-filter: blur(6px);
	-webkit-backdrop-filter: blur(6px);
	color: #ffc107;
	font-size: 10px;
	font-weight: 800;
	padding: 2px 6px;
	border-radius: 4px;
	border: 1px solid rgba(255, 255, 255, 0.12);
}
.reel-card-rating .star-icon {
	font-size: 9.5px;
	line-height: 1;
}
.reel-card-rating .rating-num {
	color: #ffffff;
	line-height: 1;
}
.reel-card-views-badge {
	position: absolute;
	bottom: 8px;
	right: 8px;
	z-index: 2;
	display: flex;
	align-items: center;
	gap: 4px;
	background: rgba(0, 0, 0, 0.65);
	backdrop-filter: blur(6px);
	-webkit-backdrop-filter: blur(6px);
	color: #fff;
	font-size: 11px;
	font-weight: 800;
	padding: 2px 7px;
	border-radius: 999px;
}
.reel-card-eps-badge {
	position: absolute;
	bottom: 8px;
	left: 8px;
	z-index: 2;
	background: rgba(15, 23, 42, 0.85);
	backdrop-filter: blur(4px);
	color: #cbd5e1;
	font-size: 10.5px;
	font-weight: 700;
	padding: 2px 6px;
	border-radius: 4px;
}
.reel-card-title {
	font-size: 14px;
	font-weight: 800;
	color: #f1f5f9;
	margin: 10px 0 4px 0;
	line-height: 1.35;
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
.reel-card-subline {
	font-size: 11.5px;
	color: #94a3b8;
	font-weight: 600;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.reel-empty-state {
	text-align: center;
	padding: 80px 20px;
	background: #111113;
	border-radius: 16px;
	border: 1px dashed rgba(255, 255, 255, 0.12);
	margin-top: 20px;
}
.empty-icon {
	font-size: 48px;
	margin-bottom: 14px;
}
.empty-title {
	font-size: 20px;
	font-weight: 800;
	color: #ffffff;
	margin-bottom: 6px;
}
.empty-desc {
	font-size: 14px;
	color: #94a3b8;
	max-width: 440px;
	margin: 0 auto 20px auto;
	line-height: 1.5;
}
.btn-browse-all {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	background: var(--theme-accent, #e11d48);
	color: #ffffff;
	font-weight: 800;
	font-size: 13.5px;
	padding: 10px 24px;
	border-radius: 999px;
	text-decoration: none;
	box-shadow: 0 4px 15px rgba(var(--theme-accent-rgb, 225, 29, 72), 0.4);
}

/* ══════════════════════════════════════════════════════════════ */
/* ULTRA-COMPACT MOBILE STYLING (< 768px)                         */
/* ══════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
	.reel-genre-page {
		padding-top: calc(env(safe-area-inset-top, 0px) + 72px) !important;
		padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 75px) !important;
		padding-left: 0 !important;
		padding-right: 0 !important;
		background: var(--bg-app, #121212) !important;
		background-color: var(--bg-app, #121212) !important;
		width: 100% !important;
		box-sizing: border-box !important;
	}
	.reel-genre-container {
		padding: 0 14px !important;
		width: 100% !important;
		box-sizing: border-box !important;
	}
	.reel-genre-header {
		flex-direction: column !important;
		align-items: flex-start !important;
		gap: 6px !important;
		margin-top: 10px !important;
		margin-bottom: 8px !important;
		padding-bottom: 6px !important;
		border-bottom: none !important;
	}
	.reel-genre-title {
		font-size: 1.25rem !important;
		margin-bottom: 2px !important;
		gap: 6px !important;
	}
	.reel-genre-emoji {
		font-size: 18px !important;
	}
	.reel-genre-count {
		font-size: 11.5px !important;
	}
	.reel-sort-pills {
		width: 100% !important;
		overflow-x: auto !important;
		flex-wrap: nowrap !important;
		gap: 6px !important;
		scrollbar-width: none !important;
		-ms-overflow-style: none !important;
		padding: 2px 0 !important;
	}
	.reel-sort-pills::-webkit-scrollbar {
		display: none !important;
	}
	.reel-sort-label {
		display: none !important;
	}
	.reel-sort-btn {
		font-size: 11px !important;
		padding: 4px 10px !important;
		flex-shrink: 0 !important;
	}
	.reel-category-pills-bar {
		margin-bottom: 12px !important;
	}
	.reel-pills-track {
		gap: 6px !important;
		padding: 2px 0 2px 0 !important;
	}
	.reel-cat-pill {
		padding: 5px 11px !important;
		font-size: 11.5px !important;
		gap: 4px !important;
	}
	.pill-emoji {
		font-size: 12px !important;
	}
	.pill-badge {
		font-size: 9.5px !important;
		padding: 0 4px !important;
	}
	.reel-grid-layout {
		grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
		gap: 16px 10px !important;
	}
	.reel-poster-box {
		aspect-ratio: 10 / 13.5 !important;
		border-radius: 8px !important;
	}
	.reel-card-title {
		font-size: 12px !important;
		margin: 6px 0 2px 0 !important;
		line-height: 1.25 !important;
	}
	.reel-card-subline {
		font-size: 10px !important;
	}
	.reel-badge-tl {
		top: 4px !important;
		left: 4px !important;
		display: flex !important;
		flex-direction: column !important;
		align-items: flex-start !important;
		gap: 3px !important;
		z-index: 3 !important;
	}
	.reel-tag-badge {
		font-size: 7.5px !important;
		font-weight: 800 !important;
		padding: 1.5px 4.5px !important;
		border-radius: 3px !important;
		letter-spacing: 0.2px !important;
		line-height: 1.1 !important;
	}
	.is-vip-badge {
		font-size: 7.5px !important;
		font-weight: 900 !important;
		padding: 1.5px 4.5px !important;
		border-radius: 3px !important;
		letter-spacing: 0.2px !important;
		gap: 2px !important;
		line-height: 1.1 !important;
	}
	.reel-card-rating {
		top: 4px !important;
		right: 4px !important;
		font-size: 7.5px !important;
		padding: 1.5px 4px !important;
		gap: 2px !important;
		border-radius: 3px !important;
		z-index: 2 !important;
	}
	.reel-card-rating .star-icon,
	.reel-card-rating .rating-num {
		font-size: 7.5px !important;
		line-height: 1 !important;
	}
	.reel-card-views-badge {
		bottom: 4px !important;
		right: 4px !important;
		font-size: 8px !important;
		padding: 1.5px 4.5px !important;
	}
	.reel-card-eps-badge {
		bottom: 4px !important;
		left: 4px !important;
		font-size: 7.5px !important;
		padding: 1.5px 4px !important;
		border-radius: 3px !important;
	}

	.reel-genre-page {
		padding-top: calc(env(safe-area-inset-top, 0px) + 98px) !important;
		padding-bottom: calc(env(safe-area-inset-bottom, 0px) + 74px) !important;
		background: #121212 !important;
		background-color: #121212 !important;
	}
}
</style>

<?php
get_footer();
