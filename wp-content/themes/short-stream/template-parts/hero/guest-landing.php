<?php
/**
 * Netflix Guest Landing Page Template Part with i18n
 * 
 * @package Short_Stream
 */

$trending_posts = get_posts( array(
	'post_type'      => class_exists( 'SHORT\Core\CPT\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::POST_TYPE : 'short_video',
	'post_status'    => 'publish',
	'posts_per_page' => 10,
	'orderby'        => 'meta_value_num',
	'meta_key'       => '_short_views',
	'order'          => 'DESC',
) );

$trending_items = array();
foreach ( $trending_posts as $p ) {
	$schema = class_exists( 'SHORT\Core\CPT\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::get_series_schema( $p->ID ) : array();
	$trending_items[] = array(
		'id'          => $p->ID,
		'title'       => $p->post_title,
		'poster_path' => $schema['poster'] ?? ( get_the_post_thumbnail_url( $p->ID, 'medium' ) ?: '' ),
		'media_type'  => 'watch',
	);
}

$bg_image_url = 'https://assets.nflxext.com/ffe/siteui/vlv3/4a59f124-030b-417a-9565-8362f395bdb0/web/PH-en-20260914-TRIFECTA-perspective_fec04215-8ea5-4067-9e35-259a3339325d_large.jpg';
?>

<div class="netflix-guest-landing-wrapper" id="netflix-guest-landing">
	<!-- Top Hero Banner with Perspective Posters Background -->
	<section class="netflix-guest-hero" style="background-image: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.45) 55%, rgba(0, 0, 0, 0.88) 100%), radial-gradient(circle at center, rgba(0, 0, 0, 0.25) 0%, rgba(0, 0, 0, 0.82) 100%), url('<?php echo esc_url( $bg_image_url ); ?>');">
		
		<!-- Floating Header Bar for Guests -->
		<header class="netflix-guest-header">
			<div class="netflix-guest-header-inner">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="netflix-brand-logo-large">
					<span class="logo-accent">S</span>HORT TV
				</a>

				<div class="netflix-guest-header-actions">
					<!-- Custom Netflix-Styled Language Dropdown -->
					<div class="netflix-custom-lang-dropdown" id="netflix-lang-dropdown">
						<button type="button" class="netflix-lang-trigger" id="netflix-lang-trigger" aria-expanded="false">
							<svg class="icon-globe" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
							<span class="lang-current-label" id="lang-current-text">English</span>
							<svg class="icon-caret" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
						</button>
						<div class="netflix-lang-menu" id="netflix-lang-menu">
							<div class="lang-option active" data-value="en">English</div>
							<div class="lang-option" data-value="fil">Filipino</div>
						</div>
					</div>

					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="btn-netflix-red-signin" data-i18n="sign_in">
						<?php _e( 'Sign In', 'short-stream' ); ?>
					</a>
				</div>
			</div>
		</header>

		<!-- Hero Content Card -->
		<div class="netflix-hero-center-content">
			<h1 class="netflix-hero-headline" data-i18n="hero_title">
				<?php _e( 'All the hits you keep hearing about', 'short-stream' ); ?>
			</h1>
			<p class="netflix-hero-price-tag" data-i18n="hero_price">
				<?php _e( 'Starts at ₱169. Cancel anytime.', 'short-stream' ); ?>
			</p>
			<p class="netflix-hero-cta-instruction" data-i18n="hero_cta">
				<?php _e( 'Ready to watch? Enter your email to create or restart your membership.', 'short-stream' ); ?>
			</p>

			<form class="netflix-email-cta-form" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" method="GET">
				<div class="netflix-input-group">
					<input type="email" name="email" class="netflix-hero-email-input" placeholder="Email address" required autocomplete="email" data-i18n-placeholder="email_placeholder">
					<button type="submit" class="netflix-hero-submit-btn">
						<span data-i18n="get_started"><?php _e( 'Get Started', 'short-stream' ); ?></span>
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
					</button>
				</div>
			</form>
		</div>

		<!-- Curved Glowing Arc Divider -->
		<div class="netflix-curve-divider-wrap">
			<div class="netflix-curve-arc"></div>
		</div>
	</section>

	<!-- Section: Trending Now Top 10 Carousel -->
	<?php if ( ! empty( $trending_items ) ) : ?>
	<section class="netflix-guest-section netflix-trending-section">
		<div class="netflix-section-container wide-container">
			<h2 class="netflix-section-title" data-i18n="trending_now"><?php _e( 'Trending Now', 'short-stream' ); ?></h2>
			
			<div class="netflix-top10-carousel-wrap">
				<button class="netflix-carousel-arrow arrow-prev" id="top10-arrow-prev" aria-label="Previous titles">
					<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>

				<div class="netflix-top10-track" id="top10-track">
					<?php foreach ( $trending_items as $rank => $item ) : 
						$rank_num = $rank + 1;
						$title = ! empty( $item['title'] ) ? $item['title'] : ( ! empty( $item['name'] ) ? $item['name'] : '' );
						$poster_path = ! empty( $item['poster_path'] ) ? 'https://image.tmdb.org/t/p/w500' . $item['poster_path'] : '';
						$media_type = ! empty( $item['media_type'] ) ? $item['media_type'] : ( isset( $item['first_air_date'] ) ? 'tv' : 'movie' );
						$item_id = $item['id'];
						$is_double_digit = ( $rank_num >= 10 );
					?>
						<div class="netflix-top10-card <?php echo $is_double_digit ? 'rank-card-10' : ''; ?>" data-id="<?php echo esc_attr( $item_id ); ?>" data-type="<?php echo esc_attr( $media_type ); ?>">
							<div class="top10-rank-box <?php echo $is_double_digit ? 'rank-10' : ''; ?>">
								<svg class="top10-rank-svg" viewBox="<?php echo $is_double_digit ? '0 0 200 270' : '0 0 145 270'; ?>" width="<?php echo $is_double_digit ? '200' : '145'; ?>" height="280">
									<text x="<?php echo $is_double_digit ? '100' : '72'; ?>" y="235" text-anchor="middle" class="top10-svg-num"><?php echo $rank_num; ?></text>
								</svg>
							</div>
							<div class="top10-poster-wrap">
								<img src="<?php echo esc_url( $poster_path ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="top10-poster-img" loading="lazy">
								<div class="top10-card-overlay">
									<a href="<?php echo esc_url( home_url( '/login/?redirect=' . urlencode( '/' . $media_type . '/' . $item_id . '/' ) ) ); ?>" class="top10-quick-view-btn">
										<span data-i18n="watch_now"><?php _e( 'Watch Now', 'short-stream' ); ?></span>
									</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<button class="netflix-carousel-arrow arrow-next" id="top10-arrow-next" aria-label="Next titles">
					<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
				</button>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Section: More Reasons to Join (4 Gradient Cards with Netflix Icons) -->
	<section class="netflix-guest-section netflix-features-section">
		<div class="netflix-section-container wide-container">
			<h2 class="netflix-section-title" data-i18n="reasons_title"><?php _e( 'More Reasons to Join', 'short-stream' ); ?></h2>

			<div class="netflix-features-grid">
				<!-- Card 1: Enjoy on your TV -->
				<div class="netflix-feature-card">
					<div class="feature-card-text">
						<h3 class="feature-card-heading" data-i18n="card1_title"><?php _e( 'Enjoy on your TV', 'short-stream' ); ?></h3>
						<p class="feature-card-desc" data-i18n="card1_desc"><?php _e( 'Watch on Smart TVs, Playstation, Xbox, Chromecast, Apple TV, Blu-ray players, and more.', 'short-stream' ); ?></p>
					</div>
					<div class="feature-card-badge">
						<!-- TV Screen with Glow & Stand Icon -->
						<svg width="60" height="52" viewBox="0 0 64 54" fill="none">
							<rect x="2" y="2" width="60" height="40" rx="6" fill="#140718" stroke="#E50914" stroke-width="2.5"/>
							<rect x="6" y="6" width="52" height="32" rx="3" fill="url(#tvScreenGrad)"/>
							<path d="M24 46H40" stroke="#E50914" stroke-width="3" stroke-linecap="round"/>
							<path d="M32 42V46" stroke="#E50914" stroke-width="3"/>
							<defs>
								<linearGradient id="tvScreenGrad" x1="0%" y1="0%" x2="100%" y2="100%">
									<stop offset="0%" stop-color="#4a044e"/>
									<stop offset="50%" stop-color="#db2777"/>
									<stop offset="100%" stop-color="#701a75"/>
								</linearGradient>
							</defs>
						</svg>
					</div>
				</div>

				<!-- Card 2: Download offline -->
				<div class="netflix-feature-card">
					<div class="feature-card-text">
						<h3 class="feature-card-heading" data-i18n="card2_title"><?php _e( 'Download your shows to watch offline', 'short-stream' ); ?></h3>
						<p class="feature-card-desc" data-i18n="card2_desc"><?php _e( 'Save your favorites easily and always have something to watch.', 'short-stream' ); ?></p>
					</div>
					<div class="feature-card-badge">
						<!-- 3D Glossy Circle Download Badge -->
						<div class="netflix-glossy-download-badge">
							<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
								<line x1="12" y1="3" x2="12" y2="15"></line>
								<polyline points="7 10 12 15 17 10"></polyline>
								<path d="M4 19H20"></path>
							</svg>
						</div>
					</div>
				</div>

				<!-- Card 3: Watch everywhere -->
				<div class="netflix-feature-card">
					<div class="feature-card-text">
						<h3 class="feature-card-heading" data-i18n="card3_title"><?php _e( 'Watch everywhere', 'short-stream' ); ?></h3>
						<p class="feature-card-desc" data-i18n="card3_desc"><?php _e( 'Stream unlimited movies and TV shows on your phone, tablet, laptop, and TV.', 'short-stream' ); ?></p>
					</div>
					<div class="feature-card-badge">
						<!-- Telescope / Stream Popper with Glowing Stars -->
						<div class="netflix-telescope-wrap">
							<svg class="icon-star star-1" width="12" height="12" viewBox="0 0 24 24" fill="#f43f5e"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 12 2"></polygon></svg>
							<svg class="icon-star star-2" width="10" height="10" viewBox="0 0 24 24" fill="#fb7185"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 12 2"></polygon></svg>
							<svg class="icon-star star-3" width="8" height="8" viewBox="0 0 24 24" fill="#e11d48"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 12 2"></polygon></svg>
							<svg width="46" height="46" viewBox="0 0 48 48" fill="none">
								<g transform="rotate(-30 24 24)">
									<ellipse cx="24" cy="10" rx="9" ry="4" fill="#ec4899"/>
									<path d="M15 10L18 36H30L33 10Z" fill="url(#popperGrad)"/>
									<rect x="21" y="36" width="6" height="6" rx="2" fill="#be185d"/>
								</g>
								<defs>
									<linearGradient id="popperGrad" x1="0%" y1="0%" x2="100%" y2="100%">
										<stop offset="0%" stop-color="#f43f5e"/>
										<stop offset="100%" stop-color="#be123c"/>
									</linearGradient>
								</defs>
							</svg>
						</div>
					</div>
				</div>

				<!-- Card 4: Profiles for kids -->
				<div class="netflix-feature-card">
					<div class="feature-card-text">
						<h3 class="feature-card-heading" data-i18n="card4_title"><?php _e( 'Create profiles for kids', 'short-stream' ); ?></h3>
						<p class="feature-card-desc" data-i18n="card4_desc"><?php _e( 'Send kids on adventures with their favorite characters in a space made just for them — free with your membership.', 'short-stream' ); ?></p>
					</div>
					<div class="feature-card-badge">
						<!-- Double Smiling Squircle Emoji Characters -->
						<div class="netflix-kids-duo-avatars">
							<div class="kids-avatar-box avatar-pink">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round">
									<circle cx="8" cy="9" r="1.5" fill="#ffffff" stroke="none"/>
									<circle cx="16" cy="9" r="1.5" fill="#ffffff" stroke="none"/>
									<path d="M7 14c1.5 2 4.5 2 6 0"/>
								</svg>
							</div>
							<div class="kids-avatar-box avatar-red">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round">
									<path d="M7 8c-.8 0-1.5.7-1.5 1.5 0 1.2 1.5 2.5 1.5 2.5s1.5-1.3 1.5-2.5C8.5 8.7 7.8 8 7 8z" fill="#ffffff" stroke="none"/>
									<path d="M17 8c-.8 0-1.5.7-1.5 1.5 0 1.2 1.5 2.5 1.5 2.5s1.5-1.3 1.5-2.5C18.5 8.7 17.8 8 17 8z" fill="#ffffff" stroke="none"/>
									<path d="M8 15c2 2.5 6 2.5 8 0" fill="none"/>
								</svg>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Section: Frequently Asked Questions Accordion -->
	<section class="netflix-guest-section netflix-faq-section">
		<div class="netflix-section-container faq-container">
			<h2 class="netflix-section-title text-center" data-i18n="faq_title"><?php _e( 'Frequently Asked Questions', 'short-stream' ); ?></h2>

			<div class="netflix-faq-accordion">
				<!-- FAQ 1 -->
				<div class="faq-item">
					<button type="button" class="faq-question-btn" aria-expanded="false">
						<span data-i18n="faq1_q"><?php _e( 'What is Short?', 'short-stream' ); ?></span>
						<svg class="faq-plus-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<div class="faq-answer-panel">
						<p data-i18n="faq1_a1"><?php _e( 'Short is a streaming service that offers a wide variety of award-winning TV shows, movies, anime, documentaries, and more on thousands of internet-connected devices.', 'short-stream' ); ?></p>
						<p data-i18n="faq1_a2"><?php _e( 'You can watch as much as you want, whenever you want without a single commercial – all for one low monthly price. There\'s always something new to discover and new TV shows and movies are added every week!', 'short-stream' ); ?></p>
					</div>
				</div>

				<!-- FAQ 2 -->
				<div class="faq-item">
					<button type="button" class="faq-question-btn" aria-expanded="false">
						<span data-i18n="faq2_q"><?php _e( 'How much does Short cost?', 'short-stream' ); ?></span>
						<svg class="faq-plus-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<div class="faq-answer-panel">
						<p data-i18n="faq2_a1"><?php _e( 'Watch Short on your smartphone, tablet, Smart TV, laptop, or streaming device, all for one fixed monthly fee. Plans range from ₱169 to ₱549 a month. No extra costs, no contracts.', 'short-stream' ); ?></p>
					</div>
				</div>

				<!-- FAQ 3 -->
				<div class="faq-item">
					<button type="button" class="faq-question-btn" aria-expanded="false">
						<span data-i18n="faq3_q"><?php _e( 'Where can I watch?', 'short-stream' ); ?></span>
						<svg class="faq-plus-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<div class="faq-answer-panel">
						<p data-i18n="faq3_a1"><?php _e( 'Watch anywhere, anytime. Sign in with your Short account to watch instantly on the web from your personal computer or on any internet-connected device that offers the app, including smart TVs, smartphones, tablets, streaming media players and game consoles.', 'short-stream' ); ?></p>
						<p data-i18n="faq3_a2"><?php _e( 'You can also download your favorite shows with the iOS, Android, or Windows 10 app. Use downloads to watch while you\'re on the go and without an internet connection. Take Short with you anywhere.', 'short-stream' ); ?></p>
					</div>
				</div>

				<!-- FAQ 4 -->
				<div class="faq-item">
					<button type="button" class="faq-question-btn" aria-expanded="false">
						<span data-i18n="faq4_q"><?php _e( 'How do I cancel?', 'short-stream' ); ?></span>
						<svg class="faq-plus-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<div class="faq-answer-panel">
						<p data-i18n="faq4_a1"><?php _e( 'Short is flexible. There are no pesky contracts and no commitments. You can easily cancel your account online in two clicks. There are no cancellation fees – start or stop your account anytime.', 'short-stream' ); ?></p>
					</div>
				</div>

				<!-- FAQ 5 -->
				<div class="faq-item">
					<button type="button" class="faq-question-btn" aria-expanded="false">
						<span data-i18n="faq5_q"><?php _e( 'What can I watch on Short?', 'short-stream' ); ?></span>
						<svg class="faq-plus-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<div class="faq-answer-panel">
						<p data-i18n="faq5_a1"><?php _e( 'Short has an extensive library of feature films, documentaries, TV shows, anime, award-winning originals, and more. Watch as much as you want, anytime you want.', 'short-stream' ); ?></p>
					</div>
				</div>

				<!-- FAQ 6 -->
				<div class="faq-item">
					<button type="button" class="faq-question-btn" aria-expanded="false">
						<span data-i18n="faq6_q"><?php _e( 'Is Short good for kids?', 'short-stream' ); ?></span>
						<svg class="faq-plus-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					</button>
					<div class="faq-answer-panel">
						<p data-i18n="faq6_a1"><?php _e( 'The Short Kids experience is included in your membership to give parents control while kids enjoy family-friendly TV shows and movies in their own space.', 'short-stream' ); ?></p>
						<p data-i18n="faq6_a2"><?php _e( 'Kids profiles come with PIN-protected parental controls that let you restrict the maturity rating of content kids can watch and block specific titles you don’t want kids to see.', 'short-stream' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Bottom CTA Form -->
			<div class="netflix-faq-bottom-cta">
				<p class="netflix-hero-cta-instruction" data-i18n="hero_cta">
					<?php _e( 'Ready to watch? Enter your email to create or restart your membership.', 'short-stream' ); ?>
				</p>
				<form class="netflix-email-cta-form" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" method="GET">
					<div class="netflix-input-group">
						<input type="email" name="email" class="netflix-hero-email-input" placeholder="Email address" required autocomplete="email" data-i18n-placeholder="email_placeholder">
						<button type="submit" class="netflix-hero-submit-btn">
							<span data-i18n="get_started"><?php _e( 'Get Started', 'short-stream' ); ?></span>
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</button>
					</div>
				</form>
			</div>
		</div>
	</section>
</div>
