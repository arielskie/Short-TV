<?php
get_header();

use SHORT\Core\TMDB\API as TMDB_API;

$tv_id = absint( get_query_var( 'short_id' ) );
$tv = TMDB_API::get_tv_details( $tv_id );

if ( is_wp_error( $tv ) || empty( $tv ) ) {
	echo '<div class="short-error-view"><h2>' . __( 'Content temporarily unavailable.', 'short-stream' ) . '</h2><a href="' . esc_url( home_url() ) . '" class="btn-stream-login">' . __( 'Back to Home', 'short-stream' ) . '</a></div>';
	get_footer();
	exit;
}

$title       = $tv['name'] ?? 'TV Show';

// Kids Mode Restriction Check
if ( function_exists( 'short_is_kids_mode' ) && short_is_kids_mode() ) {
	$is_safe = function_exists( 'short_is_item_kids_friendly' ) ? short_is_item_kids_friendly( $tv ) : true;
	if ( ! $is_safe ) {
		?>
		<div class="short-page-container" style="padding: 100px 20px; text-align: center; max-width: 680px; margin: 0 auto;">
			<div style="background: rgba(20, 20, 20, 0.95); border: 1px solid rgba(229, 9, 20, 0.4); border-radius: 16px; padding: 48px 36px; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
				<div style="width: 72px; height: 72px; background: rgba(229, 9, 20, 0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
					<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#e50914" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
				</div>
				<h2 style="font-size: 2rem; font-weight: 800; color: #ffffff; margin-bottom: 12px;"><?php _e( 'Parental Control Restricted', 'short-stream' ); ?></h2>
				<p style="color: #cccccc; font-size: 1.05rem; line-height: 1.6; margin-bottom: 28px;">
					<?php printf( __( '"%s" is not available in Kids Profile mode because it contains mature or restricted content.', 'short-stream' ), esc_html( $title ) ); ?>
				</p>
				<a href="<?php echo esc_url( home_url() ); ?>" class="btn-hero-play" style="display: inline-flex; justify-content: center; align-items: center; padding: 12px 30px; font-size: 1.05rem; border-radius: 6px; text-decoration: none;">
					<?php _e( 'Explore Kids Titles', 'short-stream' ); ?>
				</a>
			</div>
		</div>
		<?php
		get_footer();
		exit;
	}
}
$overview    = $tv['overview'] ?? '';
$backdrop    = TMDB_API::get_image_url( $tv['backdrop_path'] ?? '', 'original' );
$poster      = TMDB_API::get_image_url( $tv['poster_path'] ?? '', 'w500' );
$rating      = number_format( (float) ( $tv['vote_average'] ?? 0 ), 1 );
$seasons     = $tv['seasons'] ?? array();
$genres      = array_column( $tv['genres'] ?? array(), 'name' );
$cast        = array_slice( $tv['credits']['cast'] ?? array(), 0, 12 );
$recommendations = array_slice( $tv['recommendations']['results'] ?? $tv['similar']['results'] ?? array(), 0, 12 );

// Ensure we always have recommendations
if ( empty( $recommendations ) || count( $recommendations ) < 4 ) {
	$genre_ids = array_column( $tv['genres'] ?? array(), 'id' );
	$genre_str = ! empty( $genre_ids ) ? implode( ',', array_slice( $genre_ids, 0, 2 ) ) : '10759';
	$disc = TMDB_API::discover( 'tv', array( 'with_genres' => $genre_str ), 1 );
	if ( ! is_wp_error( $disc ) && ! empty( $disc['results'] ) ) {
		$extra = array_filter( $disc['results'], function( $it ) use ( $tv_id ) { return $it['id'] != $tv_id; } );
		$recommendations = array_merge( $recommendations, $extra );
		$recommendations = array_slice( $recommendations, 0, 12 );
	}
}

// Load Season 1 by default
$season_1_res = method_exists( 'SHORT\\Core\\TMDB\\API', 'get_season_episodes' )
	? TMDB_API::get_season_episodes( $tv_id, 1 )
	: ( method_exists( 'SHORT\\Core\\TMDB\\API', 'get_tv_season' ) ? TMDB_API::get_tv_season( $tv_id, 1 ) : array() );
$season_1_episodes = ( ! is_wp_error( $season_1_res ) && ! empty( $season_1_res['episodes'] ) ) ? $season_1_res['episodes'] : array();
?>

<div class="short-details-page">
	<div class="details-hero-backdrop" style="background-image: url('<?php echo esc_url( $backdrop ); ?>');">
		<div class="details-hero-vignette"></div>
		<div class="details-content-wrap">
			<div class="details-poster-col">
				<img src="<?php echo esc_url( $poster ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="details-poster-img">
			</div>
			<div class="details-info-col">
				<h1 class="details-title"><?php echo esc_html( $title ); ?></h1>
				
				<div class="details-meta-row">
					<span class="badge-rating">★ <?php echo esc_html( $rating ); ?></span>
					<span class="meta-tag"><?php echo count( $seasons ); ?> <?php _e( 'Seasons', 'short-stream' ); ?></span>
					<span class="badge-quality">HD</span>
				</div>

				<div class="details-genres-row">
					<?php foreach ( $genres as $g ) : ?>
						<span class="genre-pill"><?php echo esc_html( $g ); ?></span>
					<?php endforeach; ?>
				</div>

				<p class="details-overview"><?php echo esc_html( $overview ); ?></p>

				<div class="details-action-buttons">
					<a href="<?php echo esc_url( home_url( "/watch/{$tv_id}/season-1/episode-1" ) ); ?>" class="btn-hero-play">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
						<?php _e( 'Start Watching S1 E1', 'short-stream' ); ?>
					</a>
					<button class="btn-action-round btn-add-list" data-id="<?php echo esc_attr( $tv_id ); ?>" data-type="tv" data-title="<?php echo esc_attr( $title ); ?>" data-backdrop="<?php echo esc_url( $backdrop ); ?>" data-rating="<?php echo esc_attr( $rating ); ?>" title="Add to My List">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<button class="btn-action-round btn-like" data-id="<?php echo esc_attr( $tv_id ); ?>" data-type="tv" data-title="<?php echo esc_attr( $title ); ?>" data-backdrop="<?php echo esc_url( $backdrop ); ?>" data-poster="<?php echo esc_url( $poster ); ?>" data-rating="<?php echo esc_attr( $rating ); ?>" data-year="<?php echo esc_attr( $year ); ?>" title="I like this">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Season & Episode Picker -->
	<div class="details-section-wrap">
		<div class="episodes-header-row">
			<h3 class="details-section-heading"><?php _e( 'Episodes', 'short-stream' ); ?></h3>

			<?php
			$valid_seasons = array_values( array_filter( $seasons, function( $s ) {
				return ( $s['season_number'] ?? 0 ) > 0;
			} ) );

			$first_season = ! empty( $valid_seasons ) ? $valid_seasons[0] : null;
			$first_s_num  = $first_season['season_number'] ?? 1;
			$first_s_name = $first_season['name'] ?? __( 'Season 1', 'short-stream' );
			$first_s_count = $first_season['episode_count'] ?? count( $season_1_episodes );
			$first_label  = "{$first_s_name} ({$first_s_count} Episodes)";
			?>

			<div class="custom-season-dropdown" id="custom-season-dropdown" data-tv-id="<?php echo esc_attr( $tv_id ); ?>">
				<button type="button" class="custom-season-trigger" id="custom-season-trigger" aria-haspopup="listbox" aria-expanded="false">
					<span class="custom-season-selected-text" id="custom-season-selected-text"><?php echo esc_html( $first_label ); ?></span>
					<span class="custom-season-chevron-wrap">
						<svg class="custom-season-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<polyline points="6 9 12 15 18 9"></polyline>
						</svg>
					</span>
				</button>

				<?php if ( count( $valid_seasons ) > 1 ) : ?>
					<div class="custom-season-menu" id="custom-season-menu" role="listbox">
						<?php foreach ( $valid_seasons as $idx => $s ) : 
							$s_num   = $s['season_number'];
							$s_name  = $s['name'] ?? "Season {$s_num}";
							$s_count = $s['episode_count'] ?? 0;
							$s_label = "{$s_name} ({$s_count} Episodes)";
							$is_active = ( $idx === 0 );
							?>
							<div class="custom-season-option <?php echo $is_active ? 'selected' : ''; ?>" 
								 role="option" 
								 aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
								 data-season-number="<?php echo esc_attr( $s_num ); ?>"
								 data-label="<?php echo esc_attr( $s_label ); ?>"
								 data-name="<?php echo esc_attr( $s_name ); ?>"
								 data-count="<?php echo esc_attr( $s_count ); ?>">
								<span class="season-opt-name"><?php echo esc_html( $s_name ); ?></span>
								<span class="season-opt-count"><?php echo esc_html( $s_count ); ?> <?php _e( 'Episodes', 'short-stream' ); ?></span>
								<svg class="season-opt-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
									<polyline points="20 6 9 17 4 12"></polyline>
								</svg>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="episodes-list-grid" id="episodes-list-grid" data-current-season="<?php echo esc_attr( $first_s_num ); ?>">
			<?php if ( ! empty( $season_1_episodes ) ) : ?>
				<?php foreach ( $season_1_episodes as $ep ) : 
					$ep_num   = $ep['episode_number'] ?? 1;
					$ep_title = $ep['name'] ?? 'Episode ' . $ep_num;
					$ep_desc  = $ep['overview'] ?? '';
					$ep_still = TMDB_API::get_image_url( $ep['still_path'] ?? '', 'w300' );
					?>
					<a href="<?php echo esc_url( home_url( "/watch/{$tv_id}/season-{$first_s_num}/episode-{$ep_num}" ) ); ?>" class="episode-card">
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
				<?php endforeach; ?>
			<?php else : ?>
				<div class="no-episodes-found" style="padding: 40px 20px; text-align: center; color: #888;">
					<p><?php _e( 'Episode information currently unavailable.', 'short-stream' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Cast Carousel -->
	<?php if ( ! empty( $cast ) ) : ?>
		<div class="details-section-wrap">
			<h3 class="details-section-heading"><?php _e( 'Top Cast', 'short-stream' ); ?></h3>
			<div class="cast-cards-track">
				<?php foreach ( $cast as $actor ) : 
					$profile_img = TMDB_API::get_image_url( $actor['profile_path'] ?? '', 'w185' );
					?>
					<div class="cast-card">
						<div class="cast-avatar">
							<?php if ( $profile_img ) : ?>
								<img src="<?php echo esc_url( $profile_img ); ?>" alt="<?php echo esc_attr( $actor['name'] ); ?>" loading="lazy" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'cast-avatar-fallback\'><?php echo esc_html( substr( $actor['name'], 0, 1 ) ); ?></div>';">
							<?php else : ?>
								<div class="cast-avatar-fallback"><?php echo esc_html( substr( $actor['name'], 0, 1 ) ); ?></div>
							<?php endif; ?>
						</div>
						<div class="cast-name"><?php echo esc_html( $actor['name'] ); ?></div>
						<div class="cast-role"><?php echo esc_html( $actor['character'] ?? '' ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Recommendations -->
	<?php if ( ! empty( $recommendations ) ) : ?>
		<div class="details-section-wrap">
			<h3 class="details-section-heading"><?php _e( 'More Like This', 'short-stream' ); ?></h3>
			<div class="recommendations-grid">
				<?php foreach ( $recommendations as $rec ) : 
					$r_title    = $rec['title'] ?? $rec['name'] ?? 'Untitled';
					$r_id       = $rec['id'] ?? 0;
					$r_type     = isset( $rec['name'] ) && !isset( $rec['title'] ) ? 'tv' : 'movie';
					$r_backdrop = ! empty( $rec['backdrop_path'] ) ? TMDB_API::get_image_url( $rec['backdrop_path'], 'w780' ) : ( ! empty( $rec['poster_path'] ) ? TMDB_API::get_image_url( $rec['poster_path'], 'w500' ) : '' );
					$r_rating   = ! empty( $rec['vote_average'] ) ? number_format( (float) $rec['vote_average'], 1 ) : '';
					$r_year     = ! empty( $rec['release_date'] ) ? substr( $rec['release_date'], 0, 4 ) : ( ! empty( $rec['first_air_date'] ) ? substr( $rec['first_air_date'], 0, 4 ) : '' );
					$r_seasons  = ! empty( $rec['number_of_seasons'] ) ? $rec['number_of_seasons'] . ' Season' . ( $rec['number_of_seasons'] > 1 ? 's' : '' ) : '';
					$r_overview = ! empty( $rec['overview'] ) ? wp_trim_words( $rec['overview'], 18 ) : '';
					$r_url      = home_url( "/{$r_type}/{$r_id}" );
					?>
					<div class="rec-card-item">
						<a href="<?php echo esc_url( $r_url ); ?>" class="rec-card-thumb">
							<?php if ( $r_backdrop ) : ?>
								<img src="<?php echo esc_url( $r_backdrop ); ?>" alt="<?php echo esc_attr( $r_title ); ?>" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=780&auto=format&fit=crop&q=80';">
							<?php else : ?>
								<div class="rec-thumb-fallback"><span><?php echo esc_html( $r_title ); ?></span></div>
							<?php endif; ?>
							<?php if ( $r_seasons ) : ?>
								<span class="rec-duration-badge"><?php echo esc_html( $r_seasons ); ?></span>
							<?php endif; ?>
							<div class="rec-play-overlay">
								<svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
							</div>
						</a>
						<div class="rec-card-body">
							<div class="rec-card-meta-row">
								<div class="rec-card-badges">
									<span class="rec-age-badge">13+</span>
									<span class="rec-hd-badge">HD</span>
									<?php if ( $r_year ) : ?><span class="rec-year"><?php echo esc_html( $r_year ); ?></span><?php endif; ?>
								</div>
								<button class="rec-add-btn btn-add-list" data-id="<?php echo esc_attr( $r_id ); ?>" data-type="<?php echo esc_attr( $r_type ); ?>" data-title="<?php echo esc_attr( $r_title ); ?>" data-backdrop="<?php echo esc_url( $r_backdrop ); ?>" data-rating="<?php echo esc_attr( $r_rating ); ?>" title="Add to My List">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
								</button>
							</div>
							<?php if ( $r_overview ) : ?>
								<p class="rec-card-desc"><?php echo esc_html( $r_overview ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();

