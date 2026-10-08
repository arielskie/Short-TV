</main>

<?php if ( ! get_query_var( 'short_watch' ) && empty( $GLOBALS['short_is_watch_page'] ) && ! is_singular( array( 'short_title', 'video', 'movie', 'tv' ) ) && ! get_query_var( 'short_auth' ) ) : ?>
<footer class="short-footer">
	<div class="short-footer-container">
		<div class="footer-brand-summary">
			<?php
			$footer_brand_settings = get_option( 'short_brand_settings', array() );
			$footer_logo_url       = function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : 'https://i.postimg.cc/cH3CM5h4/image.png';
			$footer_brand_name     = ! empty( $footer_brand_settings['brand_name'] ) && 'My Blog' !== $footer_brand_settings['brand_name'] ? $footer_brand_settings['brand_name'] : ( get_bloginfo( 'name' ) !== 'My Blog' ? get_bloginfo( 'name' ) : 'ShortTV' );
			$footer_disp_mode      = $footer_brand_settings['logo_display_mode'] ?? 'image_only';
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="short-brand-logo reel-brand-logo-link" style="display:inline-flex; align-items:center; gap:8px;">
				<img src="<?php echo esc_url( $footer_logo_url ); ?>" alt="<?php echo esc_attr( $footer_brand_name ); ?>" class="reel-brand-logo-img" style="height:34px; width:auto; object-fit:contain;" onerror="this.src='https://i.postimg.cc/cH3CM5h4/image.png';" />
				<?php if ( 'image_text' === $footer_disp_mode ) : ?>
					<span class="brand-text-label" style="font-weight:800; font-size:1.1rem; letter-spacing:0.5px; color:#fff;"><?php echo esc_html( $footer_brand_name ); ?></span>
				<?php endif; ?>
			</a>
			<p class="footer-tagline"><?php _e( 'Unlimited short dramas, mini-series, and cinematic entertainment on any device.', 'short-stream' ); ?></p>
		</div>
		<div class="footer-links-grid">
			<div class="footer-col">
				<h4><?php _e( 'Explore', 'short-stream' ); ?></h4>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php _e( 'Home', 'short-stream' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/short-tv/' ) ); ?>"><?php _e( 'Short TV', 'short-stream' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/new-popular/' ) ); ?>"><?php _e( 'New & Popular', 'short-stream' ); ?></a>
				<?php 
				$footer_sub_settings = ( get_option( 'short_subscription_settings', array() ) ?: get_option( 'short_subscription_settings', array() ) );
				if ( ! isset( $footer_sub_settings['enable_paywall'] ) || ! empty( $footer_sub_settings['enable_paywall'] ) ) : ?>
				<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>"><?php _e( 'Plans & Pricing', 'short-stream' ); ?></a>
				<?php endif; ?>
			</div>
			<div class="footer-col auth-required-inline" style="display:none;">
				<h4><?php _e( 'My Space', 'short-stream' ); ?></h4>
				<a href="<?php echo esc_url( home_url( '/my-list/' ) ); ?>"><?php _e( 'My List', 'short-stream' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/history/' ) ); ?>"><?php _e( 'Watch History', 'short-stream' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/account/' ) ); ?>"><?php _e( 'Account Settings', 'short-stream' ); ?></a>
			</div>
		</div>
		<div class="footer-bottom-bar">
			<p>&copy; <?php echo date( 'Y' ); ?> Short Stream. <?php _e( 'Powered by WordPress & Firebase.', 'short-stream' ); ?></p>
		</div>
	</div>
</footer>

<?php
if ( function_exists( 'short_render_mobile_bottom_nav' ) ) {
	short_render_mobile_bottom_nav();
}
?>
<?php endif; ?>

<!-- Netflix Full Details Big Modal Dialog -->
<div class="short-details-modal-overlay" id="short-details-modal" style="display:none;">
	<div class="short-details-modal-backdrop" id="details-modal-backdrop"></div>
	<div class="short-details-modal-container">
		<button class="modal-close-btn" id="details-modal-close" aria-label="Close">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
		</button>
		
		<div class="details-modal-hero">
			<img id="modal-hero-img" class="modal-hero-img" src="" alt="">
			<div class="modal-hero-video-wrap" id="modal-hero-video-wrap" style="display:none;">
				<iframe id="modal-hero-video-frame" class="modal-hero-video-frame" src="" allow="autoplay; encrypted-media" allowfullscreen="true" frameborder="0"></iframe>
			</div>
			<div class="modal-hero-vignette"></div>
			<div class="modal-hero-content">
				<span class="modal-brand-tag">SHORT STREAM</span>
				<h2 id="modal-title" class="modal-title"></h2>
				<div class="modal-hero-actions">
					<a href="#" id="modal-btn-play" class="btn btn-play">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
						<span><?php _e( 'Play', 'short-stream' ); ?></span>
					</a>
					<button class="btn-circle btn-add-list" id="modal-btn-list" title="Add to My List">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<button class="btn-circle btn-like" id="modal-btn-like" title="I like this">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
					</button>
					<button class="btn-circle modal-sound-toggle" id="modal-sound-btn" title="Toggle sound">
						<svg class="icon-sound-on" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
						<svg class="icon-sound-off" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
					</button>
				</div>
			</div>
		</div>

		<div class="details-modal-body">
			<div class="details-modal-two-cols">
				<div class="modal-left-col">
					<div class="modal-meta-row">
						<span id="modal-match" class="meta-match">98% Match</span>
						<span id="modal-year" class="meta-year">2024</span>
						<span id="modal-duration" class="meta-duration">3 Seasons</span>
						<span class="meta-quality">HD</span>
						<span class="meta-audio">5.1</span>
					</div>
					<div class="modal-maturity-rating">
						<span id="modal-age" class="age-pill">16+</span>
						<span id="modal-advisories" class="advisory-text">violence, language, substances, psychological tension</span>
					</div>
					<p id="modal-overview" class="modal-overview"></p>
				</div>

				<div class="modal-right-col">
					<div class="modal-info-item">
						<span class="info-label"><?php _e( 'Cast:', 'short-stream' ); ?></span>
						<span id="modal-cast" class="info-value">Popular Ensemble Cast</span>
					</div>
					<div class="modal-info-item">
						<span class="info-label"><?php _e( 'Genres:', 'short-stream' ); ?></span>
						<span id="modal-genres" class="info-value">Action, Thriller, Sci-Fi</span>
					</div>
					<div class="modal-info-item">
						<span class="info-label"><?php _e( 'This Title Is:', 'short-stream' ); ?></span>
						<span id="modal-tags" class="info-value">Surreal, Violent, Gripping, Suspenseful</span>
					</div>
				</div>
			</div>

			<!-- Modal TV Episodes & Seasons Section (Series Only) -->
			<div class="modal-episodes-section" id="modal-episodes-section" style="display:none; margin-top:28px; border-top:1px solid rgba(255,255,255,0.1); padding-top:24px;">
				<div class="episodes-header-row">
					<h3 class="section-heading"><?php _e( 'Episodes', 'short-stream' ); ?></h3>
					<div class="custom-season-dropdown" id="modal-season-dropdown" data-tv-id="">
						<button type="button" class="custom-season-trigger" id="modal-season-trigger" aria-haspopup="listbox" aria-expanded="false">
							<span class="custom-season-selected-text" id="modal-season-selected-text"><?php _e( 'Season 1', 'short-stream' ); ?></span>
							<span class="custom-season-chevron-wrap">
								<svg class="custom-season-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
									<polyline points="6 9 12 15 18 9"></polyline>
								</svg>
							</span>
						</button>
						<div class="custom-season-menu" id="modal-season-menu" role="listbox"></div>
					</div>
				</div>
				<div class="episodes-list-grid modal-episodes-grid" id="modal-episodes-list" data-current-season="1">
					<div class="episodes-loading" style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #888;">
						<?php _e( 'Loading episodes...', 'short-stream' ); ?>
					</div>
				</div>
				<div class="modal-episodes-loadmore-wrap" id="modal-episodes-loadmore-wrap" style="display:none; text-align:center; margin-top:20px; margin-bottom:10px;">
					<button type="button" class="btn-load-more-episodes" id="btn-load-more-episodes">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
						<span id="btn-load-more-episodes-text"><?php _e( 'View More Episodes', 'short-stream' ); ?></span>
					</button>
				</div>
			</div>

			<!-- More Like This Section -->
			<div class="modal-more-like-this" id="modal-more-like-this-section">
				<h3 class="section-heading"><?php _e( 'More Like This', 'short-stream' ); ?></h3>
				<div class="modal-more-grid recommendations-grid" id="modal-more-grid"></div>
			</div>
		</div>
	</div>
</div>

<?php wp_footer(); ?>
</body>
</html>
