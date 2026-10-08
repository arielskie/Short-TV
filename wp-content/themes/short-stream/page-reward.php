<?php
/**
 * Template Name: Rewards & Daily Coins Portal
 * Route: /reward/ or /rewards/
 *
 * Provides daily check-in streaks, rewarded video ads, drama watch time milestone rewards,
 * and share rewards matching native ShortTV app.
 */

get_header();

$short_ads_opt   = get_option( 'short_ad_settings', array() );
$ad_urls         = array();
if ( ! empty( $short_ads_opt['rewarded_ad_urls'] ) && is_array( $short_ads_opt['rewarded_ad_urls'] ) ) {
	$ad_urls = array_values( array_filter( array_map( 'trim', $short_ads_opt['rewarded_ad_urls'] ) ) );
}
if ( empty( $ad_urls ) ) {
	$ad_urls = array_values( array_filter( array(
		$short_ads_opt['rewarded_ad_url'] ?? '',
		$short_ads_opt['rewarded_ad_url_2'] ?? '',
		$short_ads_opt['rewarded_ad_url_3'] ?? '',
	) ) );
}
if ( empty( $ad_urls ) ) {
	$ad_urls = array( 'https://omg10.com/4/11932682' );
}
$ad_url_json         = wp_json_encode( $ad_urls );
$rewarded_video_url  = ! empty( $short_ads_opt['rewarded_video_url'] ) ? esc_url_raw( trim( $short_ads_opt['rewarded_video_url'] ) ) : '';
$ad_countdown_s      = ! empty( $short_ads_opt['ad_countdown_seconds'] ) ? (int) $short_ads_opt['ad_countdown_seconds'] : 15;
$ad_reward_coins     = ! empty( $short_ads_opt['ad_reward_coins'] ) ? (int) $short_ads_opt['ad_reward_coins'] : 30;
?>

<main class="short-reward-page-wrap">
	<!-- Ambient Background Watermark Icons & Irregular Glows -->
	<div class="reward-ambient-bg-layer" aria-hidden="true">
		<div class="ambient-orb ambient-orb-1"></div>
		<div class="ambient-orb ambient-orb-2"></div>
		<div class="ambient-orb ambient-orb-3"></div>
		<div class="ambient-orb ambient-orb-4"></div>

		<!-- Irregular Floating Icon Watermarks -->
		<!-- Gift Box (Top-Right) -->
		<svg class="ambient-icon ambient-icon-gift1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5" rx="1"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>

		<!-- VIP Crown (Mid-Left) -->
		<svg class="ambient-icon ambient-icon-vip1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14v2H5v-2z"></path></svg>

		<!-- Coin (Mid-Right) -->
		<svg class="ambient-icon ambient-icon-coin1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v12M15 9.5a2.5 2.5 0 0 0-5 0c0 2 5 2 5 5a2.5 2.5 0 0 1-5 0"></path></svg>

		<!-- Trophy (Bottom-Left) -->
		<svg class="ambient-icon ambient-icon-trophy1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.45 1-1 1H8c-.55 0-1 .45-1 1v1c0 .55.45 1 1 1h8c.55 0 1-.45 1-1v-1c0-.55-.45-1-1-1h-1c-.55 0-1-.45-1-1v-2.34"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path></svg>

		<!-- Sparkle Star (Bottom-Right) -->
		<svg class="ambient-icon ambient-icon-star1" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0L14.59 9.41L24 12L14.59 14.59L12 24L9.41 14.59L0 12L9.41 9.41L12 0Z"></path></svg>

		<!-- Gift Box (Bottom-Center) -->
		<svg class="ambient-icon ambient-icon-gift2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5" rx="1"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
	</div>

	<!-- Sticky Mobile Top Navigation (< Rewards) - ZERO BORDER -->
	<div class="reward-mobile-top-navbar">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="rew-mobile-nav-back" title="<?php esc_attr_e( 'Back', 'short-stream' ); ?>">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
		</a>
		<h1 class="rew-mobile-nav-title"><?php _e( 'Rewards', 'short-stream' ); ?></h1>
		<div style="width:36px;"></div>
	</div>

	<div class="short-reward-container">

		<!-- Top Bar Wrapper: Desktop Inlined (< Rewards on Left, My Coins on Right) -->
		<div class="reward-top-bar-wrapper">
			<!-- Desktop Top Navigation (< Rewards) - ZERO BORDER, ZERO BACKGROUND -->
			<div class="reward-desktop-top-navbar">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="reward-desktop-back-btn" title="<?php esc_attr_e( 'Back', 'short-stream' ); ?>">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
					<span><?php _e( 'Rewards', 'short-stream' ); ?></span>
				</a>
			</div>

			<!-- Top Header: My Coins (No Top Up) -->
			<div class="reward-hero-header">
				<div class="reward-my-coins-section">
					<div class="reward-coins-left">
						<div class="reward-coins-label"><?php _e( 'My Coins', 'short-stream' ); ?></div>
						<div class="reward-coins-counter">
							<span class="coin-sparkle-icon">
								<svg width="34" height="34" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1.2" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
							</span>
							<span id="reward-user-coins-display" class="reward-coins-num">100</span>
						</div>
						<div class="reward-rules-link" id="btn-open-reward-rules">
							<span><?php _e( 'Rules', 'short-stream' ); ?></span>
							<span class="rules-question-circle">?</span>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Main Rewards Container -->
		<div class="reward-main-container" style="display:flex; flex-direction:column; gap:28px; width:100%;">

			<!-- 1. Check-in 7-Day Streak Section (Full Width) -->
			<section class="reward-section-block reward-checkin-section">
				<div class="reward-section-header">
					<div class="reward-section-title-wrap">
						<h3><?php _e( 'Check-in:', 'short-stream' ); ?> <span class="highlight-streak"><span id="streak-days-count">3</span> <?php _e( 'days in a row', 'short-stream' ); ?></span></h3>
					</div>
					<div class="reward-section-icon">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l1 6H5l1-6z"></path><path d="M5 9c0 7 3.5 12 7 12s7-5 7-12"></path><circle cx="12" cy="13" r="2"></circle></svg>
					</div>
				</div>

				<div class="checkin-days-grid" id="checkin-days-track">
					<!-- Day 1 -->
					<div class="checkin-day-item is-completed" data-day="1" data-reward="100">
						<span class="day-reward-amount">+100</span>
						<span class="day-status-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
						<span class="day-label"><?php _e( 'Day 1', 'short-stream' ); ?></span>
					</div>

					<!-- Day 2 -->
					<div class="checkin-day-item is-completed" data-day="2" data-reward="150">
						<span class="day-reward-amount">+150</span>
						<span class="day-status-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
						<span class="day-label"><?php _e( 'Day 2', 'short-stream' ); ?></span>
					</div>

					<!-- Day 3 (Today) -->
					<div class="checkin-day-item is-today is-active" id="checkin-today-node" data-day="3" data-reward="150">
						<span class="day-reward-amount">+150</span>
						<span class="day-status-icon checkmark-circle"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
						<span class="day-label font-bold"><?php _e( 'Today', 'short-stream' ); ?></span>
					</div>

					<!-- Day 4 -->
					<div class="checkin-day-item" data-day="4" data-reward="150">
						<span class="day-reward-amount">+150</span>
						<span class="day-coin-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg></span>
						<span class="day-label"><?php _e( 'Day 4', 'short-stream' ); ?></span>
					</div>

					<!-- Day 5 -->
					<div class="checkin-day-item" data-day="5" data-reward="200">
						<span class="day-reward-amount">+200</span>
						<span class="day-coin-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg></span>
						<span class="day-label"><?php _e( 'Day 5', 'short-stream' ); ?></span>
					</div>

					<!-- Day 6 -->
					<div class="checkin-day-item" data-day="6" data-reward="200">
						<span class="day-reward-amount">+200</span>
						<span class="day-coin-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg></span>
						<span class="day-label"><?php _e( 'Day 6', 'short-stream' ); ?></span>
					</div>

					<!-- Day 7 -->
					<div class="checkin-day-item is-mega-bonus" data-day="7" data-reward="300">
						<span class="day-reward-amount">+300</span>
						<span class="day-coin-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><polygon points="12 7.5 13.3 10.6 16.5 11 14.1 13.1 14.8 16.3 12 14.6 9.2 16.3 9.9 13.1 7.5 11 10.7 10.6" fill="#ffffff"/></svg></span>
						<span class="day-label"><?php _e( 'Day 7', 'short-stream' ); ?></span>
					</div>
				</div>

				<!-- Check-in Status Line -->
				<div class="checkin-status-banner" id="checkin-status-banner">
					<span class="checkin-status-dot"></span>
					<span id="checkin-status-text"><?php _e( 'Checked in for Today (+150 Coins) · Next reward tomorrow: +150 Coins', 'short-stream' ); ?></span>
				</div>
			</section>

			<!-- 2. Middle Row: Daily Activities & Watch Ads Inlined (2-Column Grid) -->
			<div class="reward-inlined-pair-grid reward-activities-ads-grid">
				<!-- Daily Activities -->
				<section class="reward-section-block reward-activity-card-block">
					<div class="reward-section-header">
						<div class="reward-section-title-wrap">
							<h3><?php _e( 'Daily Activities', 'short-stream' ); ?></h3>
						</div>
						<div class="reward-section-icon">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="13" rx="2"></rect><path d="M12 8v13M3 12h18"></path><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.5 4.5 0 0 1 12 7.5a4.5 4.5 0 0 1 4.5-4.5 2.5 2.5 0 0 1 0 5"></path></svg>
						</div>
					</div>

					<div class="activity-card-inner">
						<div class="activity-subheading">
							<h4><?php _e( 'Watch series and earn coins effortlessly', 'short-stream' ); ?></h4>
							<p id="activity-watch-progress-text"><?php _e( 'Find series you like · Watched today: 0m / 45m', 'short-stream' ); ?></p>
						</div>

						<!-- Milestone Red Envelope Cards Row -->
						<div class="milestones-envelopes-row" id="milestones-envelopes-row">
							<div class="milestone-envelope-card is-locked" data-mins="5" data-coins="10" title="5 mins watch time">
								<span class="red-dot-badge" style="display:none;"></span>
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">10</div>
							</div>
							<div class="milestone-envelope-card is-locked" data-mins="10" data-coins="20" title="10 mins watch time">
								<span class="red-dot-badge" style="display:none;"></span>
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">20</div>
							</div>
							<div class="milestone-envelope-card is-locked" data-mins="20" data-coins="50" title="20 mins watch time">
								<span class="red-dot-badge" style="display:none;"></span>
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">50</div>
							</div>
							<div class="milestone-envelope-card is-locked" data-mins="30" data-coins="60" title="30 mins watch time">
								<span class="red-dot-badge" style="display:none;"></span>
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">60</div>
							</div>
							<div class="milestone-envelope-card is-locked" data-mins="45" data-coins="100" title="45 mins watch time">
								<span class="red-dot-badge" style="display:none;"></span>
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">100</div>
							</div>
						</div>

						<!-- Timeline Stepper Track -->
						<div class="milestones-timeline-track">
							<div class="timeline-progress-bar" id="milestones-timeline-bar" style="width: 0%;"></div>
							<div class="timeline-nodes-row" id="milestones-nodes-row">
								<div class="timeline-node" data-mins="5">
									<span class="node-dot"></span>
									<span class="node-label">5mins</span>
								</div>
								<div class="timeline-node" data-mins="10">
									<span class="node-dot"></span>
									<span class="node-label">10mins</span>
								</div>
								<div class="timeline-node" data-mins="20">
									<span class="node-dot"></span>
									<span class="node-label">20mins</span>
								</div>
								<div class="timeline-node" data-mins="30">
									<span class="node-dot"></span>
									<span class="node-label">30mins</span>
								</div>
								<div class="timeline-node" data-mins="45">
									<span class="node-dot"></span>
									<span class="node-label">45mins</span>
								</div>
							</div>
						</div>

						<!-- Big Claim / Action Button -->
						<button type="button" class="reward-action-btn-primary" id="btn-claim-watch-milestone">
							<span id="btn-claim-watch-text"><?php _e( 'Watch Series to Earn', 'short-stream' ); ?></span>
						</button>
					</div>
				</section>

				<!-- Watch Ads for Coins Standalone Block -->
				<section class="reward-section-block reward-ads-card-block">
					<div class="reward-section-header">
						<div class="reward-section-title-wrap">
							<h3><?php _e( 'Watch Ads for Coins', 'short-stream' ); ?></h3>
						</div>
						<div class="reward-section-icon">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
						</div>
					</div>

					<div class="activity-card-inner">
						<div class="activity-subheading">
							<h4><?php _e( 'Watch short video ads & earn bonus coins', 'short-stream' ); ?></h4>
							<p id="activity-ads-progress-text"><?php _e( 'Earn up to 800 coins daily', 'short-stream' ); ?> &middot; <?php _e( 'Watched today:', 'short-stream' ); ?> <span id="ad-watched-counter">0</span> / 10</p>
						</div>

						<!-- 5 Ad Milestones Envelopes / Badges -->
						<div class="milestones-envelopes-row" id="ad-milestones-envelopes-track">
							<div class="milestone-envelope-card is-locked ad-milestone-step" data-ad-step="1" data-coins="30" title="1st Ad: +30 Coins">
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">+30</div>
							</div>
							<div class="milestone-envelope-card is-locked ad-milestone-step" data-ad-step="2" data-coins="30" title="2nd Ad: +30 Coins">
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">+30</div>
							</div>
							<div class="milestone-envelope-card is-locked ad-milestone-step" data-ad-step="3" data-coins="30" title="3rd Ad: +30 Coins">
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">+30</div>
							</div>
							<div class="milestone-envelope-card is-locked ad-milestone-step" data-ad-step="4" data-coins="30" title="4th Ad: +30 Coins">
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">+30</div>
							</div>
							<div class="milestone-envelope-card is-locked ad-milestone-step" data-ad-step="5" data-coins="30" title="5th Ad: +30 Coins">
								<div class="envelope-top-coins">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
								</div>
								<div class="envelope-value">+30</div>
							</div>
						</div>

						<!-- Timeline Stepper Track for Ads -->
						<div class="milestones-timeline-track">
							<div class="timeline-progress-bar" id="ad-timeline-bar" style="width: 0%;"></div>
							<div class="timeline-nodes-row" id="ad-nodes-row">
								<div class="timeline-node ad-timeline-node" data-ad-node="1">
									<span class="node-dot"></span>
									<span class="node-label">1 time</span>
								</div>
								<div class="timeline-node ad-timeline-node" data-ad-node="2">
									<span class="node-dot"></span>
									<span class="node-label">2 times</span>
								</div>
								<div class="timeline-node ad-timeline-node" data-ad-node="3">
									<span class="node-dot"></span>
									<span class="node-label">3 times</span>
								</div>
								<div class="timeline-node ad-timeline-node" data-ad-node="4">
									<span class="node-dot"></span>
									<span class="node-label">4 times</span>
								</div>
								<div class="timeline-node ad-timeline-node" data-ad-node="5">
									<span class="node-dot"></span>
									<span class="node-label">5 times</span>
								</div>
							</div>
						</div>

						<!-- Big Watch Ad Action Button -->
						<button type="button" class="reward-action-btn-primary" id="btn-watch-ad-task">
							<span id="btn-watch-ad-text"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; vertical-align:middle; margin-right:6px;"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg><?php _e( 'Watch Ad (+30 Coins)', 'short-stream' ); ?></span>
						</button>
					</div>
				</section>
			</div>

			<!-- 3. Tasks & Missions Section (Header + Inlined Pair Grid for Share with Friends & VIP Bonus) -->
			<section class="reward-section-block reward-missions-section">
				<div class="reward-section-header">
					<div class="reward-section-title-wrap">
						<h3><?php _e( 'Tasks & Missions', 'short-stream' ); ?></h3>
					</div>
					<div class="reward-section-icon">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
					</div>
				</div>

				<div class="reward-inlined-pair-grid reward-tasks-grid">
					<!-- Task 1: Share with friends -->
					<div class="reward-task-row task-share-row">
						<div class="task-top-split">
							<div class="task-icon-col icon-orange-share">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
							</div>
							<div class="task-info-col">
								<h5 class="task-title"><?php _e( 'Share with friends', 'short-stream' ); ?></h5>
								<p class="task-sub"><?php _e( 'Good series are meant to be shared', 'short-stream' ); ?></p>
								<div class="task-coin-reward">+50 <span class="coin-mini"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg></span></div>
							</div>
							<div class="task-action-col">
								<button type="button" class="task-btn-secondary is-completed" id="btn-share-task">
									<?php _e( 'Completed', 'short-stream' ); ?>
								</button>
							</div>
						</div>
					</div>

					<!-- Task 2 (Perk): VIP Member Multiplier -->
					<div class="reward-task-row reward-vip-perk-card" style="background: linear-gradient(135deg, rgba(255, 45, 85, 0.08) 0%, rgba(217, 119, 6, 0.08) 100%); border: 1px solid rgba(255, 193, 7, 0.25);">
						<div class="task-top-split">
							<div class="task-icon-col" style="background: linear-gradient(135deg, #ff2d55 0%, #d97706 100%); box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2"><path d="M2 4l3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14v2H5v-2z"></path></svg>
							</div>
							<div class="task-info-col">
								<h5 class="task-title" style="color:#ffc107;"><?php _e( 'VIP Pass Bonus', 'short-stream' ); ?></h5>
								<p class="task-sub"><?php _e( 'Get unlimited instant access & 2x coin multiplier with VIP Pass', 'short-stream' ); ?></p>
								<div class="task-coin-reward" style="color:#ffc107;">+2,000 <span class="coin-mini"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg></span></div>
							</div>
							<div class="task-action-col">
								<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="task-btn-go" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#ff2d55,#d97706); box-shadow:0 4px 14px rgba(217,119,6,0.4);">
									<?php _e( 'Get VIP', 'short-stream' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>
			</section>

		</div>

	</div>
</main>

<!-- Floating Toast Feedback -->
<div id="reward-toast" class="reward-toast-popup" style="display:none;">
	<div class="reward-toast-content">
		<span class="toast-icon">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
		</span>
		<span id="reward-toast-msg">+150 Coins Claimed!</span>
	</div>
</div>

<!-- Rewarded Video Ad Modal -->
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
				<span id="reward-video-countdown-text">Reward in <strong id="reward-video-countdown-num"><?php echo $ad_countdown_s; ?></strong>s</span>
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
				<span class="reward-coins-tag">+<span id="reward-video-coins-amount"><?php echo $ad_reward_coins; ?></span> <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block; vertical-align:middle; margin-left:2px;"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a2.5 2.5 0 0 0-5 0c0 2 5 2 5 5a2.5 2.5 0 0 1-5 0"/></svg> upon completion</span>
				<span class="reward-unskippable-notice"><?php _e( 'Do not close until timer finishes to earn coins', 'short-stream' ); ?></span>
			</div>
		</div>

		<!-- Completion Overlay -->
		<div class="reward-video-completed-overlay" id="reward-video-completed-overlay" style="display:none;">
			<div class="completed-content">
				<div class="completed-icon">
					<svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
				</div>
				<h4 class="completed-title"><?php _e( 'Video Ad Completed!', 'short-stream' ); ?></h4>
				<div class="completed-reward-amount">+<span id="completed-coins-val"><?php echo $ad_reward_coins; ?></span> <span>Coins Earned</span></div>
			</div>
		</div>
	</div>
</div>

<!-- Custom Exit Video Ad Confirmation Modal -->
<div class="short-reward-modal-overlay" id="reward-exit-confirm-modal" style="display:none; z-index: 100000000;">
	<div class="reward-modal-backdrop" id="reward-exit-backdrop"></div>
	<div class="reward-modal-card reward-confirm-card">
		<div class="reward-confirm-icon">
			<svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
		</div>
		<h4 class="reward-confirm-title"><?php _e( 'Video Ad is not finished!', 'short-stream' ); ?></h4>
		<p class="reward-confirm-desc"><?php _e( 'If you leave now, you will lose your', 'short-stream' ); ?> <strong style="color:#fbbf24;">+<span id="exit-confirm-coins-num">50</span> <?php _e( 'Coins', 'short-stream' ); ?></strong> <?php _e( 'reward.', 'short-stream' ); ?><br><?php _e( 'Are you sure you want to exit without rewards?', 'short-stream' ); ?></p>
		<div class="reward-confirm-actions">
			<button type="button" class="reward-confirm-btn-continue" id="btn-ad-continue-watching">
				<?php _e( 'Continue Watching', 'short-stream' ); ?>
			</button>
			<button type="button" class="reward-confirm-btn-exit" id="btn-ad-confirm-exit">
				<?php _e( 'Exit Without Reward', 'short-stream' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Rules Modal -->
<div class="short-reward-modal-overlay" id="reward-rules-modal" style="display:none;">
	<div class="reward-modal-backdrop"></div>
	<div class="reward-modal-card">
		<div class="reward-modal-header">
			<h4><?php _e( 'Rewards & Coins Rules', 'short-stream' ); ?></h4>
			<button type="button" class="reward-modal-close" id="btn-close-reward-rules">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
			</button>
		</div>
		<div class="reward-modal-body">
			<div class="rule-block">
				<h5><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" style="display:inline-block; vertical-align:middle; margin-right:4px;"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z"/></svg><?php _e( '1. Daily Check-in Streak', 'short-stream' ); ?></h5>
				<p><?php _e( 'Check in every day to earn progressively higher coin rewards. Reach Day 7 for a +300 bonus! Missing a day will reset your streak counter.', 'short-stream' ); ?></p>
			</div>
			<div class="rule-block">
				<h5><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" style="display:inline-block; vertical-align:middle; margin-right:4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg><?php _e( '2. Drama Watch Time Milestones', 'short-stream' ); ?></h5>
				<p><?php _e( 'Watch vertical short drama episodes to unlock watch time chests up to 45 minutes daily and claim hundreds of free coins.', 'short-stream' ); ?></p>
			</div>
			<div class="rule-block">
				<h5><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#a855f7" stroke-width="2" style="display:inline-block; vertical-align:middle; margin-right:4px;"><rect x="2" y="7" width="20" height="15" rx="2" ry="2"></rect><polyline points="17 2 12 7 7 2"></polyline></svg><?php _e( '3. Video Ads & Missions', 'short-stream' ); ?></h5>
				<p><?php _e( 'Watch sponsored video ads up to 10 times daily for +30 coins each. Limits reset daily at 00:00 midnight.', 'short-stream' ); ?></p>
			</div>
			<div class="rule-block">
				<h5><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" style="display:inline-block; vertical-align:middle; margin-right:4px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><?php _e( '4. Using Your Coins', 'short-stream' ); ?></h5>
				<p><?php _e( 'Coins can be used directly on the video player to unlock VIP drama episodes, fast-track new series, and access exclusive content.', 'short-stream' ); ?></p>
			</div>
		</div>
		<div class="reward-modal-footer">
			<button type="button" class="reward-action-btn-primary" id="btn-rules-got-it"><?php _e( 'Got It', 'short-stream' ); ?></button>
		</div>
	</div>
</div>

<style>
/* ─────────────────────────────────────────────────────────────
   REWARDS PAGE BORDERLESS SPACIOUS REDESIGN (#121212 THEME)
   ───────────────────────────────────────────────────────────── */
/* Completely Hide Site Header & Mobile Genre Bar on Reward Page */
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

body.page-template-page-reward,
.short-reward-page-wrap {
	min-height: 100vh !important;
	position: relative !important;
	background: #0d0e12 !important;
	background-color: #0d0e12 !important;
	padding: calc(env(safe-area-inset-top, 0px) + 24px) 20px 48px 20px !important;
	box-sizing: border-box !important;
	color: #ffffff !important;
	border: none !important;
	box-shadow: none !important;
	font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
	overflow-x: hidden !important;
}

/* Ambient Background Layer: Irregular Soft Glows & Icon Watermarks */
.reward-ambient-bg-layer {
	position: absolute !important;
	top: 0 !important;
	left: 0 !important;
	right: 0 !important;
	bottom: 0 !important;
	width: 100% !important;
	height: 100% !important;
	overflow: hidden !important;
	pointer-events: none !important;
	user-select: none !important;
	z-index: 0 !important;
}

/* Irregular Asymmetric Glow Orbs scattered around sides & bottom */
.ambient-orb {
	position: absolute !important;
	border-radius: 50% !important;
	filter: blur(85px) !important;
	pointer-events: none !important;
	opacity: 0.7 !important;
}

.ambient-orb-1 {
	top: 6%;
	right: -120px;
	width: 380px;
	height: 380px;
	background: radial-gradient(circle, rgba(255, 45, 85, 0.14) 0%, transparent 70%) !important;
}

.ambient-orb-2 {
	top: 32%;
	left: -130px;
	width: 440px;
	height: 440px;
	background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, transparent 70%) !important;
}

.ambient-orb-3 {
	bottom: 24%;
	right: -70px;
	width: 460px;
	height: 460px;
	background: radial-gradient(circle, rgba(255, 110, 0, 0.15) 0%, transparent 70%) !important;
}

.ambient-orb-4 {
	bottom: 4%;
	left: 5%;
	width: 520px;
	height: 520px;
	background: radial-gradient(circle, rgba(245, 158, 11, 0.13) 0%, transparent 70%) !important;
}

/* Watermark Shape Icons (VIP, Gift, Coin, Trophy, Sparkle) */
.ambient-icon {
	position: absolute !important;
	pointer-events: none !important;
	user-select: none !important;
	z-index: 1 !important;
	transition: opacity 0.3s ease !important;
}

.ambient-icon-gift1 {
	top: 30px;
	right: 4%;
	width: 110px;
	height: 110px;
	color: #ff5e00;
	opacity: 0.055;
	transform: rotate(18deg);
}

.ambient-icon-vip1 {
	top: 250px;
	left: 3%;
	width: 130px;
	height: 130px;
	color: #fbbf24;
	opacity: 0.06;
	transform: rotate(-14deg);
}

.ambient-icon-coin1 {
	top: 430px;
	right: 5%;
	width: 95px;
	height: 95px;
	color: #f59e0b;
	opacity: 0.05;
	transform: rotate(12deg);
}

.ambient-icon-trophy1 {
	bottom: 290px;
	left: 4%;
	width: 120px;
	height: 120px;
	color: #a855f7;
	opacity: 0.05;
	transform: rotate(-10deg);
}

.ambient-icon-star1 {
	bottom: 150px;
	right: 10%;
	width: 80px;
	height: 80px;
	color: #fbbf24;
	opacity: 0.065;
	transform: rotate(25deg);
}

.ambient-icon-gift2 {
	bottom: 30px;
	left: 26%;
	width: 135px;
	height: 135px;
	color: #ff3366;
	opacity: 0.045;
	transform: rotate(-8deg);
}

.reward-mobile-top-navbar {
	display: none !important;
}

/* Desktop Back Button Navigation - ZERO BORDER, ZERO BACKGROUND */
.reward-desktop-top-navbar {
	display: flex !important;
	align-items: center !important;
	margin-bottom: 28px !important;
	background: transparent !important;
	border: none !important;
}

.reward-desktop-back-btn {
	background: transparent !important;
	background-color: transparent !important;
	border: none !important;
	box-shadow: none !important;
	outline: none !important;
	color: #ffffff !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 12px !important;
	font-size: 26px !important;
	font-weight: 800 !important;
	text-decoration: none !important;
	cursor: pointer !important;
	padding: 0 !important;
	letter-spacing: -0.4px !important;
	transition: opacity 0.15s ease, transform 0.15s ease !important;
}

.reward-desktop-back-btn:hover {
	opacity: 0.8 !important;
	transform: translateX(-4px) !important;
}

.reward-desktop-back-btn svg {
	width: 28px !important;
	height: 28px !important;
	stroke: #ffffff !important;
	stroke-width: 2.8 !important;
}

@media (max-width: 768px) {
	.short-reward-page-wrap {
		padding: calc(env(safe-area-inset-top, 0px) + 58px) 16px calc(env(safe-area-inset-bottom, 0px) + 36px) 16px !important;
	}
	.reward-desktop-top-navbar {
		display: none !important;
	}
	.reward-mobile-top-navbar {
		position: fixed !important;
		top: 0 !important;
		left: 0 !important;
		right: 0 !important;
		z-index: 999 !important;
		height: 54px !important;
		background: var(--bg-nav-top, var(--bg-header, var(--bg-app, #121212))) !important;
		background-color: var(--bg-nav-top, var(--bg-header, var(--bg-app, #121212))) !important;
		backdrop-filter: blur(16px) !important;
		-webkit-backdrop-filter: blur(16px) !important;
		border: none !important;
		border-bottom: none !important;
		box-shadow: none !important;
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		padding: env(safe-area-inset-top, 0px) 16px 0 16px !important;
		box-sizing: content-box !important;
	}
	.rew-mobile-nav-back {
		background: transparent !important;
		border: none !important;
		color: #ffffff !important;
		width: 36px !important;
		height: 36px !important;
		display: inline-flex !important;
		align-items: center !important;
		justify-content: flex-start !important;
		cursor: pointer !important;
		padding: 0 !important;
		text-decoration: none !important;
	}
	.rew-mobile-nav-title {
		font-size: 18px !important;
		font-weight: 800 !important;
		color: #ffffff !important;
		margin: 0 !important;
		letter-spacing: -0.3px !important;
		text-align: center !important;
		flex: 1 !important;
	}
	.rew-nav-coins-badge {
		background: rgba(255, 193, 7, 0.12) !important;
		border: 1px solid rgba(255, 193, 7, 0.25) !important;
		padding: 4px 11px !important;
		border-radius: 999px !important;
		color: #fbbf24 !important;
		font-size: 12.5px !important;
		font-weight: 800 !important;
		display: inline-flex !important;
		align-items: center !important;
		gap: 5px !important;
		flex-shrink: 0 !important;
	}
}

.short-reward-container {
	position: relative !important;
	z-index: 2 !important;
	width: 100% !important;
	max-width: 1040px !important;
	margin: 0 auto !important;
	box-sizing: border-box !important;
}

/* Inlined Pair Grids (Daily Activities + Ads and Share + VIP Bonus) */
.reward-inlined-pair-grid {
	display: grid !important;
	grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
	gap: 20px !important;
	width: 100% !important;
	align-items: stretch !important;
}

@media (max-width: 860px) {
	.reward-inlined-pair-grid {
		grid-template-columns: 1fr !important;
		gap: 16px !important;
	}
}

.reward-activity-card-block,
.reward-ads-card-block {
	display: flex !important;
	flex-direction: column !important;
	width: 100% !important;
	height: 100% !important;
}

.activity-card-inner,
.task-ads-standalone-card {
	background: rgba(255, 255, 255, 0.035) !important;
	border-radius: 16px !important;
	padding: 20px !important;
	border: 1px solid rgba(255, 255, 255, 0.06) !important;
	box-sizing: border-box !important;
	display: flex !important;
	flex-direction: column !important;
	justify-content: space-between !important;
	height: 100% !important;
	flex: 1 !important;
	gap: 16px !important;
}

.reward-tasks-grid .reward-task-row {
	height: 100% !important;
	min-height: 88px !important;
	display: flex !important;
	flex-direction: column !important;
	justify-content: center !important;
	background: rgba(255, 255, 255, 0.035) !important;
	border-radius: 16px !important;
	padding: 18px 20px !important;
	border: 1px solid rgba(255, 255, 255, 0.06) !important;
	box-sizing: border-box !important;
}

.reward-top-bar-wrapper {
	display: block !important;
}



/* Hero Header: My Coins */
.reward-hero-header {
	padding: 8px 0 26px 0 !important;
	border: none !important;
	background: transparent !important;
}

.reward-my-coins-section {
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	width: 100% !important;
	text-align: center !important;
}

.reward-coins-label {
	font-size: 14px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	margin-bottom: 4px !important;
	letter-spacing: 0.2px !important;
}

.reward-coins-counter {
	display: flex !important;
	align-items: center !important;
	gap: 10px !important;
	font-size: 40px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	line-height: 1.05 !important;
	letter-spacing: -0.5px !important;
	text-shadow: 0 0 28px rgba(251, 191, 36, 0.28) !important;
}

.coin-sparkle-icon {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	filter: drop-shadow(0 2px 12px rgba(251, 191, 36, 0.6)) !important;
}

.reward-rules-link {
	display: inline-flex !important;
	align-items: center !important;
	gap: 5px !important;
	font-size: 12.5px !important;
	color: #94a3b8 !important;
	cursor: pointer !important;
	margin-top: 8px !important;
	user-select: none !important;
	transition: color 0.15s ease !important;
}

.reward-rules-link:hover {
	color: #ffffff !important;
}

.rules-question-circle {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	width: 15px !important;
	height: 15px !important;
	border-radius: 50% !important;
	background: rgba(255, 255, 255, 0.12) !important;
	border: none !important;
	font-size: 10px !important;
	font-weight: 800 !important;
	color: #cbd5e1 !important;
	line-height: 1 !important;
}

.reward-redeem-pill-btn {
	display: inline-flex !important;
	align-items: center !important;
	gap: 7px !important;
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	text-decoration: none !important;
	font-size: 13.5px !important;
	font-weight: 800 !important;
	padding: 10px 20px !important;
	border-radius: 999px !important;
	border: none !important;
	box-shadow: 0 4px 16px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease, filter 0.15s ease !important;
}

.reward-redeem-pill-btn:hover {
	transform: scale(1.04) !important;
	filter: brightness(1.1) !important;
}

/* Sections - ZERO CARD, ZERO BORDER */
.reward-section-block {
	margin-bottom: 28px !important;
	background: transparent !important;
	border: none !important;
	box-shadow: none !important;
	padding: 0 !important;
}

.reward-section-block:last-of-type {
	margin-bottom: 0 !important;
}

.reward-section-header {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	margin-bottom: 14px !important;
	padding: 0 !important;
	background: transparent !important;
	border: none !important;
}

.reward-section-title-wrap h3 {
	margin: 0 !important;
	font-size: 17px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	letter-spacing: -0.2px !important;
}

.highlight-streak {
	color: #fbbf24 !important;
	margin-left: 2px !important;
}

.reward-section-icon {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
}

/* 7-Day Check-in Grid - Spacious, Modern & Sleek (Smooth ambient glow, zero clipping) */
.checkin-days-grid {
	display: flex !important;
	justify-content: space-between !important;
	gap: 10px !important;
	margin-bottom: 22px !important;
	overflow: visible !important;
	overflow-x: visible !important;
	overflow-y: visible !important;
	padding: 14px 4px 18px 4px !important;
}

@media (max-width: 640px) {
	.checkin-days-grid {
		overflow-x: auto !important;
		overflow-y: visible !important;
		-webkit-overflow-scrolling: touch !important;
		padding: 16px 14px 20px 14px !important;
		margin-left: -14px !important;
		margin-right: -14px !important;
		scrollbar-width: none !important;
	}
}

.checkin-days-grid::-webkit-scrollbar {
	display: none !important;
}

.checkin-day-item {
	flex: 1 1 0 !important;
	min-width: 58px !important;
	background: rgba(255, 255, 255, 0.04) !important;
	border: none !important;
	outline: none !important;
	border-radius: 12px !important;
	padding: 14px 4px 12px 4px !important;
	display: flex !important;
	flex-direction: column !important;
	align-items: center !important;
	justify-content: space-between !important;
	min-height: 104px !important;
	text-align: center !important;
	box-sizing: border-box !important;
	cursor: pointer !important;
	transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
	position: relative !important;
}

.checkin-day-item:hover {
	background: rgba(255, 255, 255, 0.07) !important;
	transform: translateY(-2px) !important;
}

.checkin-day-item.is-completed {
	background: rgba(255, 255, 255, 0.02) !important;
	opacity: 0.5 !important;
}

.checkin-day-item.is-completed .day-status-icon {
	color: #64748b !important;
}

.checkin-day-item.is-today.is-active {
	background: linear-gradient(180deg, #ff5e00 0%, #e03200 100%) !important;
	border: none !important;
	outline: none !important;
	box-shadow: 0 4px 18px rgba(255, 94, 0, 0.45), 0 0 26px rgba(255, 94, 0, 0.2) !important;
	transform: translateY(-3px) scale(1.03) !important;
	opacity: 1 !important;
	position: relative !important;
	z-index: 10 !important;
}

.checkin-day-item.is-today.is-active::before {
	content: '' !important;
	position: absolute !important;
	inset: -6px !important;
	border-radius: 18px !important;
	background: radial-gradient(circle at center, rgba(255, 94, 0, 0.35) 0%, rgba(255, 94, 0, 0) 72%) !important;
	z-index: -1 !important;
	pointer-events: none !important;
	filter: blur(4px) !important;
}

.day-reward-amount {
	font-size: 13px !important;
	font-weight: 800 !important;
	color: #cbd5e1 !important;
	letter-spacing: -0.2px !important;
	line-height: 1.1 !important;
}

.checkin-day-item.is-today .day-reward-amount {
	color: #ffffff !important;
	font-weight: 900 !important;
	font-size: 13.5px !important;
}

.day-status-icon,
.day-coin-icon {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	margin: 6px 0 !important;
}

.checkmark-circle {
	width: 24px !important;
	height: 24px !important;
	border-radius: 50% !important;
	background: #ffffff !important;
	color: #e03200 !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	margin: 4px 0 !important;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
}

.day-label {
	font-size: 11px !important;
	color: #64748b !important;
	font-weight: 600 !important;
	letter-spacing: 0.1px !important;
}

.checkin-day-item.is-today .day-label {
	color: #ffffff !important;
	font-weight: 800 !important;
}

.checkin-day-item.is-mega-bonus {
	background: rgba(251, 191, 36, 0.07) !important;
}

.checkin-day-item.is-mega-bonus .day-reward-amount {
	color: #fbbf24 !important;
}

/* Check-in Status Banner */
.checkin-status-banner {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	font-size: 12px !important;
	color: #94a3b8 !important;
	margin: 2px 0 16px 0 !important;
	padding: 0 4px !important;
	width: 100% !important;
	box-sizing: border-box !important;
}

.checkin-status-banner .checkin-status-dot {
	width: 7px !important;
	height: 7px !important;
	border-radius: 50% !important;
	background: #10b981 !important;
	box-shadow: 0 0 6px rgba(16, 185, 129, 0.7) !important;
	flex-shrink: 0 !important;
}

/* Dedicated Check-in Bonus Booster Card */
.checkin-bonus-booster-card {
	background: rgba(255, 255, 255, 0.03) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 14px !important;
	padding: 14px 18px !important;
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	gap: 16px !important;
	width: 100% !important;
	box-sizing: border-box !important;
	transition: all 0.2s ease !important;
}

.checkin-bonus-booster-card:hover {
	background: rgba(255, 255, 255, 0.05) !important;
	border-color: rgba(255, 107, 0, 0.25) !important;
}

.booster-left {
	display: flex !important;
	align-items: center !important;
	gap: 12px !important;
	flex-grow: 1 !important;
	min-width: 0 !important;
}

.booster-icon-wrap {
	width: 38px !important;
	height: 38px !important;
	border-radius: 50% !important;
	background: linear-gradient(135deg, #ff6b00 0%, #ff4500 100%) !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	color: #ffffff !important;
	flex-shrink: 0 !important;
	box-shadow: 0 4px 10px rgba(255, 69, 0, 0.3) !important;
}

.booster-info {
	flex-grow: 1 !important;
	min-width: 0 !important;
}

.booster-title {
	font-size: 13.5px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
	display: flex !important;
	align-items: center !important;
	gap: 7px !important;
	margin-bottom: 2px !important;
}

.booster-tag {
	font-size: 12px !important;
	font-weight: 800 !important;
	color: #fbbf24 !important;
	background: rgba(251, 191, 36, 0.12) !important;
	border: 1px solid rgba(251, 191, 36, 0.25) !important;
	padding: 1px 7px !important;
	border-radius: 999px !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 3px !important;
	line-height: 1.2 !important;
}

.booster-desc {
	font-size: 12px !important;
	color: #94a3b8 !important;
	margin: 0 !important;
	line-height: 1.3 !important;
}

.booster-btn {
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	border: none !important;
	outline: none !important;
	border-radius: 999px !important;
	padding: 8px 18px !important;
	font-size: 12.5px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 6px !important;
	box-shadow: 0 4px 12px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease, filter 0.15s ease !important;
	white-space: nowrap !important;
	flex-shrink: 0 !important;
}

.booster-btn:hover {
	filter: brightness(1.1) !important;
	transform: scale(1.03) !important;
}

.booster-btn.is-completed {
	background: rgba(255, 255, 255, 0.08) !important;
	color: #64748b !important;
	box-shadow: none !important;
	cursor: default !important;
}

/* Primary Action Button (Big Red/Orange Pill) */
.reward-action-btn-primary {
	width: 100% !important;
	height: 52px !important;
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	border: none !important;
	outline: none !important;
	border-radius: 999px !important;
	padding: 0 20px !important;
	font-size: 14.5px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 9px !important;
	box-shadow: 0 6px 20px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease, filter 0.15s ease !important;
	box-sizing: border-box !important;
}

.reward-action-btn-primary:hover {
	filter: brightness(1.1) !important;
	transform: translateY(-1px) !important;
}

.reward-action-btn-primary:active {
	transform: scale(0.98) !important;
}

/* Daily Activities Subheading */
.activity-subheading {
	margin-bottom: 16px !important;
}

.activity-subheading h4 {
	margin: 0 0 3px 0 !important;
	font-size: 14.5px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
}

.activity-subheading p {
	margin: 0 !important;
	font-size: 12.5px !important;
	color: #94a3b8 !important;
}

/* Milestones Red Envelopes Row - Borderless & Clean */
.milestones-envelopes-row {
	display: grid !important;
	grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
	gap: 8px !important;
	margin-bottom: 16px !important;
}

.milestone-envelope-card {
	border: none !important;
	border-radius: 12px !important;
	padding: 10px 4px 12px 4px !important;
	text-align: center !important;
	position: relative !important;
	cursor: pointer !important;
	transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
	user-select: none !important;
}

.milestone-envelope-card:hover {
	transform: translateY(-2px) !important;
}

/* 1. Locked (Default / Not Enough Watch Time Yet) */
.milestone-envelope-card.is-locked {
	background: rgba(255, 255, 255, 0.05) !important;
	opacity: 0.7 !important;
	box-shadow: none !important;
}

.milestone-envelope-card.is-locked .envelope-top-coins svg {
	stroke: #64748b !important;
}

.milestone-envelope-card.is-locked .envelope-value {
	color: #94a3b8 !important;
}

/* 2. Ready to Claim (Reached Watch Time & Not Yet Claimed) */
.milestone-envelope-card.is-ready {
	background: linear-gradient(180deg, #ff5533 0%, #e02800 100%) !important;
	opacity: 1 !important;
	box-shadow: 0 6px 18px rgba(255, 69, 0, 0.45) !important;
	animation: pulseReadyEnvelope 2s infinite ease-in-out !important;
}

.milestone-envelope-card.is-ready .envelope-top-coins svg {
	stroke: #fbbf24 !important;
}

.milestone-envelope-card.is-ready .envelope-value {
	color: #ffffff !important;
}

@keyframes pulseReadyEnvelope {
	0%, 100% { transform: translateY(0); box-shadow: 0 6px 18px rgba(255, 69, 0, 0.45); }
	50% { transform: translateY(-3px) scale(1.02); box-shadow: 0 8px 24px rgba(255, 69, 0, 0.65); }
}

/* 3. Completed / Claimed Today */
.milestone-envelope-card.is-completed,
.milestone-envelope-card.is-unlocked {
	background: rgba(255, 255, 255, 0.03) !important;
	opacity: 0.45 !important;
	box-shadow: none !important;
}

.milestone-envelope-card.is-completed .envelope-top-coins svg {
	stroke: #475569 !important;
}

.milestone-envelope-card.is-completed .envelope-value {
	color: #64748b !important;
}

.envelope-top-coins {
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 2px !important;
	margin-bottom: 5px !important;
}

.envelope-value {
	font-size: 15px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	line-height: 1 !important;
}

.red-dot-badge {
	position: absolute !important;
	top: -3px !important;
	right: -3px !important;
	width: 9px !important;
	height: 9px !important;
	border-radius: 50% !important;
	background: #ff1e00 !important;
	border: none !important;
	box-shadow: 0 0 6px rgba(255, 30, 0, 0.8) !important;
}

.reward-action-btn-primary.is-disabled {
	background: rgba(255, 255, 255, 0.08) !important;
	color: #64748b !important;
	box-shadow: none !important;
	cursor: default !important;
}

/* Stepper Timeline - Borderless */
.milestones-timeline-track {
	position: relative !important;
	margin: 18px 0 22px 0 !important;
	padding: 0 10px !important;
}

.timeline-progress-bar {
	position: absolute !important;
	top: 6px !important;
	left: 14px !important;
	height: 4px !important;
	background: #ff4500 !important;
	z-index: 1 !important;
	border-radius: 4px !important;
	border: none !important;
}

.milestones-timeline-track::before {
	content: "" !important;
	position: absolute !important;
	top: 6px !important;
	left: 14px !important;
	right: 14px !important;
	height: 4px !important;
	background: rgba(255, 255, 255, 0.1) !important;
	z-index: 0 !important;
	border-radius: 4px !important;
	border: none !important;
}

.timeline-nodes-row {
	display: flex !important;
	justify-content: space-between !important;
	position: relative !important;
	z-index: 2 !important;
}

.timeline-node {
	display: flex !important;
	flex-direction: column !important;
	align-items: center !important;
	gap: 8px !important;
}

.node-dot {
	width: 14px !important;
	height: 14px !important;
	border-radius: 50% !important;
	background: #121212 !important;
	border: 3px solid rgba(255, 255, 255, 0.25) !important;
	box-sizing: border-box !important;
}

.timeline-node.is-done .node-dot {
	border-color: #ff4500 !important;
	background: #ff4500 !important;
}

.timeline-node.is-active .node-dot {
	border-color: #ff4500 !important;
	background: #121212 !important;
	box-shadow: 0 0 0 3px rgba(255, 69, 0, 0.35) !important;
}

.node-label {
	font-size: 11px !important;
	color: #94a3b8 !important;
	white-space: nowrap !important;
	font-weight: 600 !important;
}

/* Tasks List - Mobile Stacked, Desktop inlined */
@media (max-width: 768px) {
	.reward-tasks-list {
		margin-top: 14px !important;
		display: flex !important;
		flex-direction: column !important;
		gap: 14px !important;
	}
	.reward-task-row {
		display: flex !important;
		flex-direction: column !important;
		gap: 14px !important;
		background: rgba(255, 255, 255, 0.045) !important;
		border-radius: 16px !important;
		padding: 16px 18px !important;
		border: 1px solid rgba(255, 255, 255, 0.06) !important;
		box-sizing: border-box !important;
	}
}

.reward-task-row {
	background: rgba(255, 255, 255, 0.045) !important;
	border: 1px solid rgba(255, 255, 255, 0.06) !important;
	box-sizing: border-box !important;
}

.task-ads-row {
	flex-direction: column !important;
	align-items: stretch !important;
	gap: 14px !important;
}

.task-top-split {
	display: flex !important;
	align-items: center !important;
	gap: 14px !important;
	width: 100% !important;
}

.task-icon-col {
	width: 44px !important;
	height: 44px !important;
	border-radius: 50% !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	color: #ffffff !important;
	flex-shrink: 0 !important;
	border: none !important;
}

.icon-orange-share,
.icon-orange-video {
	background: linear-gradient(135deg, #ff6b00 0%, #ff4500 100%) !important;
	box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3) !important;
}

.task-info-col {
	flex-grow: 1 !important;
	min-width: 0 !important;
}

.task-title {
	margin: 0 0 3px 0 !important;
	font-size: 14.5px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
}

.task-sub {
	margin: 0 0 4px 0 !important;
	font-size: 12px !important;
	color: #94a3b8 !important;
}

.task-coin-reward {
	font-size: 13.5px !important;
	font-weight: 800 !important;
	color: #fb923c !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 5px !important;
	line-height: 1 !important;
}

.task-coin-reward .coin-mini,
.task-coin-reward svg {
	display: inline-flex !important;
	align-items: center !important;
	vertical-align: middle !important;
	line-height: 1 !important;
}

.task-btn-secondary {
	background: rgba(255, 255, 255, 0.08) !important;
	color: #94a3b8 !important;
	border: none !important;
	outline: none !important;
	padding: 8px 18px !important;
	border-radius: 999px !important;
	font-size: 12.5px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	white-space: nowrap !important;
}

.task-btn-go {
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	border: none !important;
	outline: none !important;
	padding: 8px 24px !important;
	border-radius: 999px !important;
	font-size: 13.5px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
	box-shadow: 0 4px 12px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease !important;
	white-space: nowrap !important;
}

.task-btn-go:hover {
	transform: scale(1.05) !important;
}

.ad-steps-track {
	display: grid !important;
	grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
	gap: 8px !important;
	margin-top: 2px !important;
}

.ad-step-pill {
	background: rgba(255, 255, 255, 0.04) !important;
	border: none !important;
	border-radius: 10px !important;
	padding: 8px 2px !important;
	text-align: center !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 3px !important;
}

.ad-step-pill.is-active-step {
	background: linear-gradient(180deg, #ff6b00 0%, #ff4500 100%) !important;
	border: none !important;
}

.step-reward {
	font-size: 11.5px !important;
	font-weight: 800 !important;
	color: #fbbf24 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 3px !important;
}

.ad-step-pill.is-active-step .step-reward {
	color: #ffffff !important;
}

.step-label {
	font-size: 10px !important;
	color: #94a3b8 !important;
}

.ad-step-pill.is-active-step .step-label {
	color: #ffffff !important;
	font-weight: 700 !important;
}

.reward-modal-card {
	position: relative;
	background: #18181e;
	border: none !important;
	border-radius: 20px;
	max-width: 440px;
	width: 100%;
	padding: 24px 26px;
	box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7);
	color: #ffffff;
	z-index: 10;
}

/* Toast Notification */
.reward-toast-popup {
	position: fixed !important;
	bottom: calc(env(safe-area-inset-bottom, 0px) + 74px) !important;
	top: auto !important;
	left: 50% !important;
	transform: translateX(-50%) !important;
	z-index: 999999 !important;
	animation: toastBottomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
	pointer-events: none !important;
	width: max-content !important;
	min-width: 0 !important;
	max-width: min(380px, 90vw) !important;
	box-sizing: border-box !important;
}

.reward-toast-content {
	background: #1c1d24 !important;
	background-color: #1c1d24 !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	outline: none !important;
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.7) !important;
	backdrop-filter: blur(12px) !important;
	-webkit-backdrop-filter: blur(12px) !important;
	padding: 9px 18px !important;
	border-radius: 999px !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 7px !important;
	font-size: 13px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
	white-space: nowrap !important;
	box-sizing: border-box !important;
}

.toast-icon {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

@keyframes toastBottomIn {
	from { opacity: 0; transform: translate(-50%, 20px); }
	to { opacity: 1; transform: translate(-50%, 0); }
}

@media (min-width: 769px) {
	.reward-toast-popup {
		bottom: 36px !important;
	}
}

/* Modal */
.short-reward-modal-overlay {
	position: fixed;
	inset: 0;
	z-index: 9999999;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 16px;
	box-sizing: border-box;
}

.reward-modal-backdrop {
	position: absolute;
	inset: 0;
	background: rgba(0, 0, 0, 0.75);
	backdrop-filter: blur(6px);
}

.reward-modal-card {
	position: relative;
	background: #18181e;
	border: none !important;
	border-radius: 20px;
	max-width: 440px;
	width: 100%;
	padding: 24px 26px;
	box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7);
	color: #ffffff;
	z-index: 10;
}

/* Custom Exit Confirm Dialog */
.reward-confirm-card {
	text-align: center !important;
	padding: 28px 24px 22px 24px !important;
	max-width: 360px !important;
	box-sizing: border-box !important;
}

.reward-confirm-icon {
	margin-bottom: 12px !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
}

.reward-confirm-title {
	font-size: 18px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 0 10px 0 !important;
	letter-spacing: -0.2px !important;
}

.reward-confirm-desc {
	font-size: 13.5px !important;
	color: #cbd5e1 !important;
	line-height: 1.5 !important;
	margin: 0 0 22px 0 !important;
}

.reward-confirm-actions {
	display: flex !important;
	flex-direction: column !important;
	gap: 10px !important;
}

.reward-confirm-btn-continue {
	width: 100% !important;
	height: 48px !important;
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	border: none !important;
	outline: none !important;
	border-radius: 999px !important;
	font-size: 14.5px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
	box-shadow: 0 4px 16px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease, filter 0.15s ease !important;
	box-sizing: border-box !important;
}

.reward-confirm-btn-continue:hover {
	filter: brightness(1.1) !important;
	transform: scale(1.02) !important;
}

.reward-confirm-btn-exit {
	width: 100% !important;
	height: 38px !important;
	background: transparent !important;
	color: #94a3b8 !important;
	border: none !important;
	outline: none !important;
	border-radius: 999px !important;
	font-size: 13px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	transition: color 0.15s ease !important;
	box-sizing: border-box !important;
}

.reward-confirm-btn-exit:hover {
	color: #ef4444 !important;
}

.reward-modal-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16px;
}

.reward-modal-header h4 {
	margin: 0;
	font-size: 17px;
	font-weight: 800;
}

.reward-modal-close {
	background: none;
	border: none;
	color: #94a3b8;
	font-size: 24px;
	cursor: pointer;
	padding: 0;
	line-height: 1;
}

.reward-modal-body {
	display: flex;
	flex-direction: column;
	gap: 12px;
	font-size: 13px;
	color: #cbd5e1;
	line-height: 1.5;
	margin-bottom: 20px;
	max-height: 320px;
	overflow-y: auto;
}

.rule-block h5 {
	margin: 0 0 3px 0;
	font-size: 13.5px;
	color: #fbbf24;
}

.rule-block p {
	margin: 0;
}

/* Mobile Center Rewards Nav Button Styling */
@media screen and (max-width: 768px) {
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="mobile_rewards"],
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="rewards"],
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="reward"] {
		color: #ff6b00 !important;
		font-weight: 800 !important;
	}
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="mobile_rewards"].active,
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="rewards"].active,
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="reward"].active {
		color: #ff4500 !important;
	}
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="mobile_rewards"] svg,
	.short-mobile-bottom-nav .mobile-nav-item[data-nav="rewards"] svg {
		stroke: #ff6b00 !important;
		filter: drop-shadow(0 2px 6px rgba(255, 107, 0, 0.4));
	}
}

/* ─────────────────────────────────────────────────────────────
   REWARDED VIDEO AD 100% FULLSCREEN CINEMATIC PLAYER (EDGE TO EDGE)
   ───────────────────────────────────────────────────────────── */
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
.reward-video-backdrop {
	display: none !important;
}
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
	animation: none !important;
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
	background: linear-gradient(180deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.2) 60%, transparent 100%) !important;
	border-bottom: none !important;
	box-sizing: border-box !important;
}
.reward-video-badge {
	display: inline-flex;
	align-items: center;
	gap: 8px;
}
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
	background: transparent !important;
	backdrop-filter: none !important;
	-webkit-backdrop-filter: none !important;
	border: none !important;
	padding: 0 !important;
	border-radius: 0 !important;
	font-size: 13.5px !important;
	font-weight: 800 !important;
	color: #fbbf24 !important;
	box-shadow: none !important;
	text-shadow: 0 1px 4px rgba(0, 0, 0, 0.9), 0 2px 8px rgba(0, 0, 0, 0.8) !important;
}
.reward-video-close-btn {
	background: transparent !important;
	border: none !important;
	border-radius: 0 !important;
	box-shadow: none !important;
	backdrop-filter: none !important;
	-webkit-backdrop-filter: none !important;
	width: 38px !important;
	height: 38px !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	color: #ffffff !important;
	cursor: pointer !important;
	padding: 0 !important;
	filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.9)) !important;
	transition: all 0.15s ease !important;
}
.reward-video-close-btn:hover {
	color: #ffffff !important;
	background: transparent !important;
	transform: scale(1.15) !important;
}
.reward-video-close-btn svg {
	width: 22px !important;
	height: 22px !important;
	display: block !important;
}

.reward-video-sound-float-btn {
	position: absolute !important;
	right: 18px !important;
	bottom: 82px !important;
	z-index: 35 !important;
	background: transparent !important;
	border: none !important;
	border-radius: 0 !important;
	box-shadow: none !important;
	backdrop-filter: none !important;
	-webkit-backdrop-filter: none !important;
	color: #ffffff !important;
	width: 40px !important;
	height: 40px !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	padding: 0 !important;
	filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.9)) !important;
	transition: transform 0.15s ease, color 0.15s ease !important;
}
.reward-video-sound-float-btn:hover {
	transform: scale(1.15) !important;
	color: #fbbf24 !important;
}
.reward-video-sound-float-btn svg {
	width: 24px !important;
	height: 24px !important;
	display: block !important;
}
.reward-video-player-wrap {
	position: absolute !important;
	inset: 0 !important;
	top: 0 !important;
	left: 0 !important;
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
	max-height: 100% !important;
	object-fit: contain !important;
	background: #000000 !important;
	display: block !important;
}
.reward-video-spinner {
	position: absolute;
	inset: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	background: rgba(0, 0, 0, 0.6);
	z-index: 15;
}
.reward-spinner-ring {
	width: 38px;
	height: 38px;
	border: 3px solid rgba(255, 255, 255, 0.2);
	border-top-color: #ff6b00;
	border-radius: 50%;
	animation: rewardSpin 0.8s linear infinite;
}
@keyframes rewardSpin {
	to { transform: rotate(360deg); }
}
.reward-video-bottom-bar {
	position: absolute !important;
	bottom: 0 !important;
	left: 0 !important;
	right: 0 !important;
	z-index: 30 !important;
	padding: 30px 20px max(18px, env(safe-area-inset-bottom, 18px)) 20px !important;
	background: linear-gradient(0deg, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.5) 70%, transparent 100%) !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 12px !important;
	box-sizing: border-box !important;
}
.reward-video-progress-track {
	width: 100%;
	height: 5px;
	background: rgba(255, 255, 255, 0.18);
	border-radius: 999px;
	overflow: hidden;
}
.reward-video-progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #ff6b00 0%, #fbbf24 100%);
	border-radius: 999px;
	transition: width 0.3s linear;
	box-shadow: 0 0 10px rgba(255, 107, 0, 0.6);
}
.reward-video-footer-info {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 12.5px;
	color: #e2e8f0;
}
.reward-coins-tag {
	color: #fbbf24;
	font-weight: 800;
	font-size: 13.5px;
}
.reward-unskippable-notice {
	font-size: 11.5px;
	color: #94a3b8;
}
.reward-video-completed-overlay {
	position: absolute !important;
	inset: 0 !important;
	background: rgba(0, 0, 0, 0.92) !important;
	backdrop-filter: blur(16px) !important;
	display: none;
	align-items: center !important;
	justify-content: center !important;
	z-index: 40 !important;
	animation: rewardCompletePop 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
}
.reward-video-completed-overlay.show {
	display: flex !important;
}
@keyframes rewardCompletePop {
	from { opacity: 0; transform: scale(0.9); }
	to { opacity: 1; transform: scale(1); }
}
.reward-video-completed-overlay .completed-content {
	text-align: center;
	padding: 24px;
}
.completed-icon {
	font-size: 52px;
	margin-bottom: 12px;
}
.completed-title {
	margin: 0 0 8px 0;
	font-size: 22px;
	font-weight: 800;
	color: #fff;
}
.completed-reward-amount {
	font-size: 28px;
	font-weight: 900;
	color: #fbbf24;
}
.completed-reward-amount span {
	font-size: 16px;
	font-weight: 600;
	color: #cbd5e1;
	margin-left: 6px;
}

/* ==========================================================================
   Desktop Responsive Enhancement (min-width: 900px) - Cascaded at End
   ========================================================================== */
@media (min-width: 900px) {
	.short-reward-page-wrap {
		padding: 38px 48px 64px 48px !important;
	}
	.short-reward-container {
		max-width: 1380px !important;
		width: 100% !important;
	}
	.reward-top-bar-wrapper {
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		margin-bottom: 32px !important;
	}
	.reward-desktop-top-navbar {
		margin-bottom: 0 !important;
	}
	.reward-hero-header {
		padding: 0 !important;
	}
	.reward-my-coins-section {
		display: flex !important;
		align-items: center !important;
		justify-content: flex-end !important;
	}
	.reward-coins-left {
		display: inline-flex !important;
		flex-direction: row !important;
		align-items: center !important;
		gap: 14px !important;
		background: rgba(255, 255, 255, 0.04) !important;
		border: 1px solid rgba(255, 255, 255, 0.08) !important;
		padding: 8px 18px 8px 20px !important;
		border-radius: 999px !important;
		box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25) !important;
		backdrop-filter: blur(12px) !important;
		-webkit-backdrop-filter: blur(12px) !important;
	}
	.reward-coins-label {
		font-size: 13.5px !important;
		font-weight: 700 !important;
		color: #94a3b8 !important;
		margin-bottom: 0 !important;
		letter-spacing: 0.2px !important;
		white-space: nowrap !important;
	}
	.reward-coins-counter {
		display: inline-flex !important;
		align-items: center !important;
		gap: 7px !important;
		font-size: 22px !important;
		font-weight: 900 !important;
		color: #ffffff !important;
		line-height: 1 !important;
		white-space: nowrap !important;
	}
	.reward-rules-link {
		display: inline-flex !important;
		align-items: center !important;
		gap: 5px !important;
		margin-top: 0 !important;
		padding-left: 14px !important;
		border-left: 1px solid rgba(255, 255, 255, 0.14) !important;
		font-size: 12.5px !important;
		white-space: nowrap !important;
	}

	/* Main 2-Column Desktop Grid */
	.reward-main-2col-layout {
		display: grid !important;
		grid-template-columns: 1.15fr 0.85fr !important;
		gap: 48px !important;
		align-items: start !important;
	}
	.reward-col-left {
		display: flex !important;
		flex-direction: column !important;
		gap: 36px !important;
	}
	.reward-col-right {
		display: flex !important;
		flex-direction: column !important;
		gap: 36px !important;
	}
	.reward-checkin-section,
	.reward-activity-section {
		display: flex !important;
		flex-direction: column !important;
		align-items: flex-start !important;
		justify-content: flex-start !important;
		width: 100% !important;
		margin-bottom: 0 !important;
	}
	.reward-section-header,
	.checkin-days-grid,
	.activity-subheading,
	.milestones-envelopes-row,
	.milestones-timeline-track {
		width: 100% !important;
		box-sizing: border-box !important;
	}

	/* Primary Action Buttons on Desktop: Centered with Uniform Width */
	.reward-action-btn-primary {
		width: 360px !important;
		max-width: 100% !important;
		height: 46px !important;
		font-size: 13.5px !important;
		display: flex !important;
		align-items: center !important;
		justify-content: center !important;
		align-self: center !important;
		margin: 14px auto 0 auto !important;
		padding: 0 24px !important;
		border-radius: 999px !important;
		box-shadow: 0 4px 14px rgba(255, 69, 0, 0.3) !important;
		text-align: center !important;
	}

	.reward-missions-section {
		width: 100% !important;
		margin-top: 0 !important;
	}
	.reward-tasks-list {
		display: flex !important;
		flex-direction: column !important;
		gap: 18px !important;
	}
	.checkin-days-grid {
		gap: 10px !important;
		padding: 6px 0 16px 0 !important;
	}
	.checkin-day-item {
		max-width: none !important;
		min-width: 0 !important;
		min-height: 106px !important;
		padding: 14px 6px 12px 6px !important;
		border-radius: 14px !important;
	}
	.day-reward-amount {
		font-size: 13.5px !important;
	}
	.day-label {
		font-size: 11px !important;
	}
	.milestones-envelopes-row {
		gap: 12px !important;
	}
	.milestone-envelope-card {
		padding: 14px 8px 16px 8px !important;
		border-radius: 14px !important;
	}
	.envelope-value {
		font-size: 16px !important;
	}
	.milestones-timeline-track {
		margin: 22px 0 26px 0 !important;
		padding: 0 20px !important;
	}
	.timeline-progress-bar,
	.milestones-timeline-track::before {
		left: 24px !important;
		right: 24px !important;
	}
	.node-label {
		font-size: 11.5px !important;
	}
	.reward-task-row {
		padding: 20px 22px !important;
		border-radius: 18px !important;
		box-sizing: border-box !important;
	}
	.task-title {
		font-size: 15px !important;
	}
	.task-sub {
		font-size: 12.5px !important;
	}
}

@media (min-width: 1300px) {
	.short-reward-page-wrap {
		padding: 42px 64px 72px 64px !important;
	}
	.short-reward-container {
		max-width: 1440px !important;
	}
	.reward-main-2col-layout {
		gap: 56px !important;
	}
	.checkin-days-grid {
		gap: 12px !important;
	}
	.milestones-envelopes-row {
		gap: 14px !important;
	}
}
</style>

<script>
(function(){
	// Dynamic Ad URLs and Video Ad Configurations
	var rewardedUrls      = <?php echo $ad_url_json; ?>;
	var rawVideoAdUrl     = <?php echo wp_json_encode( $rewarded_video_url ); ?>;
	var configCountdown   = <?php echo (int) $ad_countdown_s; ?> || 15;
	var configRewardCoins = <?php echo (int) $ad_reward_coins; ?> || 30;
	var defaultTestVideo  = 'https://storage.googleapis.com/interactive-media-ads/media/android.mp4';

	// Toast Helper
	function showRewardToast(msg) {
		var toast = document.getElementById('reward-toast');
		var txt = document.getElementById('reward-toast-msg');
		if (txt) txt.textContent = msg;
		if (toast) {
			toast.style.display = 'block';
			setTimeout(function(){
				toast.style.display = 'none';
			}, 2800);
		}
	}

	// Firebase RTDB & Auth Reference
	var currentUid = null;
	var rtdb = null;
	function getFirebaseRtdbUrl() {
		return (typeof window.shortFirebaseSettings !== 'undefined' && window.shortFirebaseSettings.databaseURL)
			? window.shortFirebaseSettings.databaseURL
			: ((typeof window.SHORT_CONFIG !== 'undefined' && window.SHORT_CONFIG.firebase && window.SHORT_CONFIG.firebase.databaseURL)
				? window.SHORT_CONFIG.firebase.databaseURL
				: 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app');
	}
	function getLocalTodayStr() {
		var now = new Date();
		var y = now.getFullYear();
		var m = String(now.getMonth() + 1).padStart(2, '0');
		var d = String(now.getDate()).padStart(2, '0');
		return y + '-' + m + '-' + d;
	}
	var todayStr = getLocalTodayStr();
	var lastCheckinDate = localStorage.getItem('shorttv_reward_checkin_date') || '';
	var streakCount = parseInt(localStorage.getItem('shorttv_reward_streak') || '3', 10);
	var adsCount = parseInt(localStorage.getItem('shorttv_reward_ads_count_' + todayStr) || '0', 10);
	var sharedToday = localStorage.getItem('shorttv_reward_shared_' + todayStr) === '1';

	// Coins Sync Function
	function getCoins() {
		return parseInt(localStorage.getItem('shorttv_user_coins') || localStorage.getItem('short_user_coins') || '100', 10);
	}

	function updateCoinsDisplay(val) {
		var c = (typeof val === 'number' && !isNaN(val)) ? val : getCoins();
		var el = document.getElementById('reward-user-coins-display');
		if (el) el.textContent = c;
		var navEl = document.getElementById('rew-nav-coins-display');
		if (navEl) navEl.textContent = c;
		if (typeof window.syncHeaderCoins === 'function') {
			window.syncHeaderCoins();
		}
	}

	function addCoins(amount, reason) {
		var uid = currentUid || (window.firebaseUser && window.firebaseUser.uid) || localStorage.getItem('short_user_uid');
		var cur = getCoins() + amount;
		localStorage.setItem('shorttv_user_coins', String(cur));
		localStorage.setItem('short_user_coins', String(cur));
		updateCoinsDisplay(cur);

		var txItem = {
			amount: amount,
			reason: reason || 'Reward Bonus',
			type: 'reward',
			status: 'Completed',
			order_id: 'RW-' + Math.floor(Math.random() * 89999 + 10000),
			ts: Date.now()
		};

		// 1. Record via global recorder helper (saves local + dispatches live events + pushes to RTDB)
		if (typeof window.recordShortTransaction === 'function') {
			window.recordShortTransaction(txItem);
		} else {
			try {
				var localHistory = JSON.parse(localStorage.getItem('short_local_coin_history') || '[]');
				if (!Array.isArray(localHistory)) localHistory = [];
				localHistory.unshift(txItem);
				localStorage.setItem('short_local_coin_history', JSON.stringify(localHistory.slice(0, 50)));
				if (uid) {
					var stvH = JSON.parse(localStorage.getItem('stv_coin_history_' + uid) || '[]');
					stvH.unshift(txItem);
					localStorage.setItem('stv_coin_history_' + uid, JSON.stringify(stvH.slice(0, 50)));
				}
			} catch(e){}
		}

		// 2. Atomic Sync to Firebase RTDB (SDK Transaction or REST)
		if (rtdb && uid) {
			try {
				var coinsRef = rtdb.ref('users/' + uid + '/coins');
				coinsRef.transaction(function(currentVal) {
					return (parseInt(currentVal, 10) || 0) + amount;
				}, function(err, committed, snap) {
					if (committed && snap) {
						var authoritativeCoins = parseInt(snap.val(), 10);
						if (!isNaN(authoritativeCoins)) {
							localStorage.setItem('shorttv_user_coins', String(authoritativeCoins));
							localStorage.setItem('short_user_coins', String(authoritativeCoins));
							updateCoinsDisplay(authoritativeCoins);
						}
					}
				});
				if (typeof window.recordShortTransaction !== 'function') {
					rtdb.ref('users/' + uid + '/coinHistory').push(txItem);
				}
			} catch(e) {
				console.warn('[Reward RTDB Sync Error]', e);
			}
		} else if (uid) {
			var RTDB_URL = getFirebaseRtdbUrl();
			if (RTDB_URL) {
				fetch(RTDB_URL + '/users/' + uid + '/coins.json')
					.then(function(res) { return res.json(); })
					.then(function(cloudCoins) {
						var baseCoins = (typeof cloudCoins === 'number') ? cloudCoins : cur;
						var finalCoins = (typeof cloudCoins === 'number') ? (baseCoins + amount) : cur;
						localStorage.setItem('shorttv_user_coins', String(finalCoins));
						localStorage.setItem('short_user_coins', String(finalCoins));
						updateCoinsDisplay(finalCoins);
						return fetch(RTDB_URL + '/users/' + uid + '/coins.json', {
							method: 'PUT',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(finalCoins)
						});
					})
					.catch(function(){});

				if (typeof window.recordShortTransaction !== 'function') {
					fetch(RTDB_URL + '/users/' + uid + '/coinHistory.json', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify(txItem)
					}).catch(function(){});
				}
			}
		}
	}

	// ─────────────────────────────────────────────────────────────
	// REWARDED VIDEO AD MODAL ENGINE (UNSKIPPABLE TIMER & COIN GRANT)
	// ─────────────────────────────────────────────────────────────
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
	var coinsBadge       = document.getElementById('reward-video-coins-amount');
	var completedVal     = document.getElementById('completed-coins-val');

	var adTimerInterval  = null;
	var adRemainingSecs  = 0;
	var adTotalSecs      = configCountdown;
	var currentAdReward  = configRewardCoins;
	var isRewardGranted  = false;
	var currentTaskDoneCb = null;

	var soundBtn         = document.getElementById('btn-toggle-reward-sound');
	var exitConfirmModal = document.getElementById('reward-exit-confirm-modal');
	var exitBackdrop     = document.getElementById('reward-exit-backdrop');
	var btnContinueWatch = document.getElementById('btn-ad-continue-watching');
	var btnConfirmExit   = document.getElementById('btn-ad-confirm-exit');
	var exitConfirmCoins = document.getElementById('exit-confirm-coins-num');
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

	// Extract MediaFile URL from VAST XML or fallback safely
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

		// Other URL format -> fallback to working test video
		callback(defaultTestVideo);
	}

	function playRewardedVideo(rewardCoins, onCompletedCallback) {
		currentAdReward = rewardCoins || configRewardCoins;
		currentTaskDoneCb = onCompletedCallback;
		isRewardGranted = false;
		adTotalSecs = configCountdown || 15;
		adRemainingSecs = adTotalSecs;

		if (coinsBadge) coinsBadge.textContent = currentAdReward;
		if (completedVal) completedVal.textContent = currentAdReward;
		if (timerPill) timerPill.textContent = adRemainingSecs;
		if (timerText) timerText.innerHTML = 'Reward in <strong id="reward-video-countdown-num">' + adRemainingSecs + '</strong>s';
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
			adTimerInterval = setInterval(function(){
				adRemainingSecs--;
				if (timerPill) timerPill.textContent = Math.max(0, adRemainingSecs);
				if (timerText) timerText.innerHTML = 'Reward in <strong id="reward-video-countdown-num">' + Math.max(0, adRemainingSecs) + '</strong>s';
				
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

		if (timerText) timerText.innerHTML = '<strong style="color:#22c55e;">Reward Earned!</strong>';
		if (progressFill) progressFill.style.width = '100%';
		if (completedOverlay) completedOverlay.classList.add('show');

		// Grant Coins
		addCoins(currentAdReward, 'Watched Rewarded Video Ad (+' + currentAdReward + ' Coins)');

		if (typeof currentTaskDoneCb === 'function') {
			currentTaskDoneCb();
		}

		setTimeout(function(){
			closeRewardVideoModal(true);
			showRewardToast('+' + currentAdReward + ' Coins Awarded!');
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
		if (videoEl) {
			videoEl.pause();
		}
		if (exitConfirmCoins) exitConfirmCoins.textContent = currentAdReward;
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
				if (timerText) timerText.innerHTML = 'Reward in <strong id="reward-video-countdown-num">' + Math.max(0, adRemainingSecs) + '</strong>s';
				
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
			showRewardToast('Ad skipped (no coins)');
		});
	}

	if (closeVideoBtn) {
		closeVideoBtn.addEventListener('click', function(){
			closeRewardVideoModal(false);
		});
	}

	// ─────────────────────────────────────────────────────────────
	// PAGE TASKS & EVENT HANDLERS
	// ─────────────────────────────────────────────────────────────
	document.addEventListener('DOMContentLoaded', function(){
		updateCoinsDisplay();

		// Today's Date String for Daily Reset
		var todayStr = getLocalTodayStr();
		var lastCheckinDate = localStorage.getItem('shorttv_reward_checkin_date') || '';
		var streakCount = parseInt(localStorage.getItem('shorttv_reward_streak') || '3', 10);
		var adsCount = parseInt(localStorage.getItem('shorttv_reward_ads_count_' + todayStr) || '0', 10);
		var sharedToday = localStorage.getItem('shorttv_reward_shared_' + todayStr) === '1';

		var streakEl = document.getElementById('streak-days-count');
		if (streakEl) streakEl.textContent = streakCount;

		function updateAdUI() {
			var adsCounterEl = document.getElementById('ad-watched-counter');
			if (adsCounterEl) adsCounterEl.textContent = adsCount;
			var adProgressBar = document.getElementById('ad-timeline-bar');
			if (adProgressBar) {
				var pct = Math.min(100, Math.max(0, (adsCount / 5) * 100));
				adProgressBar.style.width = pct + '%';
			}
			var adCards = document.querySelectorAll('.ad-milestone-step');
			adCards.forEach(function(card){
				var step = parseInt(card.getAttribute('data-ad-step'), 10) || 0;
				if (adsCount >= step) {
					card.classList.remove('is-locked');
					card.classList.add('is-completed');
				} else {
					card.classList.add('is-locked');
					card.classList.remove('is-completed');
				}
			});
			var adNodes = document.querySelectorAll('.ad-timeline-node');
			adNodes.forEach(function(node){
				var step = parseInt(node.getAttribute('data-ad-node'), 10) || 0;
				if (adsCount >= step) {
					node.classList.add('is-done');
					node.classList.remove('is-active');
				} else if (step === adsCount + 1) {
					node.classList.add('is-active');
					node.classList.remove('is-done');
				} else {
					node.classList.remove('is-done', 'is-active');
				}
			});
			var btnWatchAd = document.getElementById('btn-watch-ad-task');
			var btnWatchAdText = document.getElementById('btn-watch-ad-text');
			if (btnWatchAd) {
				if (adsCount >= 10) {
					btnWatchAd.disabled = true;
					btnWatchAd.classList.add('is-disabled');
					if (btnWatchAdText) btnWatchAdText.textContent = 'Daily Limit Reached (10/10)';
				} else {
					btnWatchAd.disabled = false;
					btnWatchAd.classList.remove('is-disabled');
					if (btnWatchAdText) {
						btnWatchAdText.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; vertical-align:middle; margin-right:6px;"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>Watch Ad (+30 Coins)';
					}
				}
			}
		}
		updateAdUI();

		// 1. Check-in Today Handler & UI
		var todayNode = document.getElementById('checkin-today-node');
		var checkinStatusText = document.getElementById('checkin-status-text');
		var checkinStatusBanner = document.getElementById('checkin-status-banner');

		function updateCheckinUI() {
			if (lastCheckinDate === todayStr) {
				if (todayNode) todayNode.classList.add('is-completed');
				if (checkinStatusText) {
					checkinStatusText.textContent = 'Checked in for Today (+150 Coins) · Next reward tomorrow: +150 Coins';
				}
			} else {
				if (todayNode) todayNode.classList.remove('is-completed');
				if (checkinStatusText) {
					checkinStatusText.textContent = 'Tap Today on the streak calendar above to check in & earn +150 Coins';
				}
			}
		}
		updateCheckinUI();

		// Check-in Today Click Handler with Cloud Concurrency Protection
		if (todayNode) {
			todayNode.addEventListener('click', function(){
				if (lastCheckinDate === todayStr) {
					showRewardToast('Already checked in today!');
					return;
				}
				var uid = currentUid || (window.firebaseUser && window.firebaseUser.uid) || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser ? firebase.auth().currentUser.uid : null) || localStorage.getItem('short_user_uid');

				if (rtdb && uid) {
					todayNode.style.pointerEvents = 'none';
					rtdb.ref('users/' + uid + '/rewardCheckin').transaction(function(curr) {
						if (curr && curr.lastDate === todayStr) {
							// Already claimed on another device! Abort transaction.
							return;
						}
						var prevStreak = (curr && (typeof curr.streak === 'number' || typeof curr.streak === 'string')) ? parseInt(curr.streak, 10) : (streakCount > 0 ? (streakCount - 1) : 0);
						return {
							lastDate: todayStr,
							streak: prevStreak + 1,
							updatedAt: Date.now()
						};
					}, function(error, committed, snapshot) {
						todayNode.style.pointerEvents = '';
						if (error) {
							console.warn('[Checkin RTDB Error]', error);
							// Fallback local claim if RTDB had network glitch
							lastCheckinDate = todayStr;
							streakCount++;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							localStorage.setItem('shorttv_reward_streak', String(streakCount));
							if (streakEl) streakEl.textContent = streakCount;
							updateCheckinUI();
							addCoins(150, 'Daily Check-in Day ' + streakCount);
							showRewardToast('+150 Coins Claimed!');
						} else if (!committed) {
							// Blocked: already claimed on another device!
							lastCheckinDate = todayStr;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							updateCheckinUI();
							showRewardToast('Already checked in on another device today!');
						} else {
							// Successful claim
							var val = snapshot ? snapshot.val() : null;
							streakCount = (val && val.streak) ? parseInt(val.streak, 10) : (streakCount + 1);
							lastCheckinDate = todayStr;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							localStorage.setItem('shorttv_reward_streak', String(streakCount));
							if (streakEl) streakEl.textContent = streakCount;
							updateCheckinUI();
							addCoins(150, 'Daily Check-in Day ' + streakCount);
							showRewardToast('+150 Coins Claimed!');
						}
					});
				} else if (uid) {
					// REST fallback check before claiming
					var RTDB_URL = getFirebaseRtdbUrl();
					todayNode.style.pointerEvents = 'none';
					fetch(RTDB_URL + '/users/' + uid + '/rewardCheckin.json')
						.then(function(res) { return res.json(); })
						.then(function(cloudCheckin) {
							todayNode.style.pointerEvents = '';
							if (cloudCheckin && cloudCheckin.lastDate === todayStr) {
								lastCheckinDate = todayStr;
								if (cloudCheckin.streak) streakCount = parseInt(cloudCheckin.streak, 10);
								localStorage.setItem('shorttv_reward_checkin_date', todayStr);
								localStorage.setItem('shorttv_reward_streak', String(streakCount));
								if (streakEl) streakEl.textContent = streakCount;
								updateCheckinUI();
								showRewardToast('Already checked in on another device today!');
								return;
							}
							var prevStreak = (cloudCheckin && cloudCheckin.streak) ? parseInt(cloudCheckin.streak, 10) : (streakCount > 0 ? (streakCount - 1) : 0);
							streakCount = prevStreak + 1;
							lastCheckinDate = todayStr;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							localStorage.setItem('shorttv_reward_streak', String(streakCount));
							if (streakEl) streakEl.textContent = streakCount;
							updateCheckinUI();

							fetch(RTDB_URL + '/users/' + uid + '/rewardCheckin.json', {
								method: 'PUT',
								headers: { 'Content-Type': 'application/json' },
								body: JSON.stringify({
									lastDate: todayStr,
									streak: streakCount,
									updatedAt: Date.now()
								})
							}).catch(function(){});

							addCoins(150, 'Daily Check-in Day ' + streakCount);
							showRewardToast('+150 Coins Claimed!');
						})
						.catch(function() {
							todayNode.style.pointerEvents = '';
							lastCheckinDate = todayStr;
							streakCount++;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							localStorage.setItem('shorttv_reward_streak', String(streakCount));
							if (streakEl) streakEl.textContent = streakCount;
							updateCheckinUI();
							addCoins(150, 'Daily Check-in Day ' + streakCount);
							showRewardToast('+150 Coins Claimed!');
						});
				} else {
					// Guest claim
					lastCheckinDate = todayStr;
					streakCount++;
					localStorage.setItem('shorttv_reward_checkin_date', todayStr);
					localStorage.setItem('shorttv_reward_streak', String(streakCount));
					if (streakEl) streakEl.textContent = streakCount;
					updateCheckinUI();
					addCoins(150, 'Daily Check-in Day ' + streakCount);
					showRewardToast('+150 Coins Claimed!');
				}
			});
		}

		// 2. Watch Video Bonus (+50 Coins)
		var btnWatchVideo = document.getElementById('btn-watch-video-bonus');
		var btnWatchVideoText = document.getElementById('btn-watch-video-text');
		var videoBonusWatchedToday = localStorage.getItem('shorttv_reward_video_bonus_' + todayStr) === '1';

		if (btnWatchVideo && videoBonusWatchedToday) {
			btnWatchVideo.classList.add('is-completed');
			if (btnWatchVideoText) btnWatchVideoText.textContent = 'Completed';
		}

		if (btnWatchVideo) {
			btnWatchVideo.addEventListener('click', function(){
				if (localStorage.getItem('shorttv_reward_video_bonus_' + todayStr) === '1') {
					showRewardToast('Bonus video already claimed today!');
					return;
				}
				var uid = currentUid || (window.firebaseUser && window.firebaseUser.uid) || localStorage.getItem('short_user_uid');
				playRewardedVideo(50, function(){
					localStorage.setItem('shorttv_reward_video_bonus_' + todayStr, '1');
					if (btnWatchVideo) btnWatchVideo.classList.add('is-completed');
					if (btnWatchVideoText) btnWatchVideoText.textContent = 'Completed';
					if (rtdb && uid) {
						rtdb.ref('users/' + uid + '/rewardTasks/' + todayStr + '/videoBonus').set(true);
					}
					showRewardToast('+50 Bonus Coins Earned!');
				});
			});
		}

		// 3. Daily Activities Watch Time Milestones Engine
		var watchMilestones = [
			{ mins: 5, coins: 10, label: '5mins' },
			{ mins: 10, coins: 20, label: '10mins' },
			{ mins: 20, coins: 50, label: '20mins' },
			{ mins: 30, coins: 60, label: '30mins' },
			{ mins: 45, coins: 100, label: '45mins' }
		];

		function getDailyWatchSecs() {
			var storageKey = 'shorttv_daily_watch_secs_' + todayStr;
			return parseInt(localStorage.getItem(storageKey) || '0', 10);
		}

		function getClaimedMilestones() {
			var storageKey = 'shorttv_claimed_milestones_' + todayStr;
			try {
				var parsed = JSON.parse(localStorage.getItem(storageKey) || '[]');
				return Array.isArray(parsed) ? parsed : [];
			} catch(e) {
				return [];
			}
		}

		function saveClaimedMilestones(arr) {
			var storageKey = 'shorttv_claimed_milestones_' + todayStr;
			localStorage.setItem(storageKey, JSON.stringify(arr));
			var uid = currentUid || (window.firebaseUser && window.firebaseUser.uid) || localStorage.getItem('short_user_uid');
			if (rtdb && uid) {
				rtdb.ref('users/' + uid + '/rewardTasks/' + todayStr + '/claimedMilestones').set(arr);
			}
		}

		function renderWatchMilestones() {
			var watchSecs = getDailyWatchSecs();
			var watchMins = Math.floor(watchSecs / 60);
			var claimed = getClaimedMilestones();

			var progressTextEl = document.getElementById('activity-watch-progress-text');
			if (progressTextEl) {
				progressTextEl.textContent = 'Find series you like · Watched today: ' + watchMins + 'm / 45m';
			}

			// Timeline progress bar percentage (0 to 100%)
			var maxMins = 45;
			var percent = Math.min(100, Math.max(0, (watchMins / maxMins) * 100));
			var timelineBar = document.getElementById('milestones-timeline-bar');
			if (timelineBar) {
				timelineBar.style.width = percent + '%';
			}

			var envelopeCards = document.querySelectorAll('#milestones-envelopes-track .milestone-envelope-card');
			var timelineNodes = document.querySelectorAll('#milestones-nodes-row .timeline-node');
			var unclaimedReadyCoins = 0;
			var unclaimedReadyMins = [];
			var nextLockedMins = null;

			watchMilestones.forEach(function(m, idx) {
				var isClaimed = claimed.indexOf(m.mins) !== -1;
				var isUnlocked = watchMins >= m.mins;
				var isReady = isUnlocked && !isClaimed;

				if (isReady) {
					unclaimedReadyCoins += m.coins;
					unclaimedReadyMins.push(m.mins);
				} else if (!isUnlocked && nextLockedMins === null) {
					nextLockedMins = m.mins;
				}

				// Envelope card styling
				if (envelopeCards[idx]) {
					var card = envelopeCards[idx];
					var redDot = card.querySelector('.red-dot-badge');
					card.classList.remove('is-locked', 'is-ready', 'is-completed', 'is-unlocked');

					if (isClaimed) {
						card.classList.add('is-completed');
						if (redDot) redDot.style.display = 'none';
					} else if (isReady) {
						card.classList.add('is-ready');
						if (redDot) redDot.style.display = 'block';
					} else {
						card.classList.add('is-locked');
						if (redDot) redDot.style.display = 'none';
					}
				}

				// Stepper nodes styling
				if (timelineNodes[idx]) {
					var node = timelineNodes[idx];
					node.classList.remove('is-done', 'is-active');
					if (isClaimed || isUnlocked) {
						node.classList.add('is-done');
					} else if (m.mins === nextLockedMins) {
						node.classList.add('is-active');
					}
				}
			});

			// Big Action Button
			var btnClaimMilestone = document.getElementById('btn-claim-watch-milestone');
			var btnTextEl = document.getElementById('btn-claim-watch-text');
			if (btnClaimMilestone && btnTextEl) {
				btnClaimMilestone.classList.remove('is-disabled');
				if (unclaimedReadyCoins > 0) {
					btnTextEl.textContent = 'Claim +' + unclaimedReadyCoins + ' Coins';
				} else if (claimed.length >= watchMilestones.length) {
					btnTextEl.textContent = 'All Completed Today';
					btnClaimMilestone.classList.add('is-disabled');
				} else if (nextLockedMins !== null) {
					btnTextEl.textContent = 'Watch More (' + watchMins + 'm / ' + nextLockedMins + 'm)';
				} else {
					btnTextEl.textContent = 'Watch Series to Earn';
				}
			}
		}

		// Initial render of watch milestones
		renderWatchMilestones();

		// Click handler for main claim button
		var btnClaimMilestone = document.getElementById('btn-claim-watch-milestone');
		if (btnClaimMilestone) {
			btnClaimMilestone.addEventListener('click', function(){
				var watchSecs = getDailyWatchSecs();
				var watchMins = Math.floor(watchSecs / 60);
				var claimed = getClaimedMilestones();
				var unclaimedReadyCoins = 0;
				var unclaimedReadyMins = [];
				var nextLockedMins = null;

				watchMilestones.forEach(function(m) {
					var isClaimed = claimed.indexOf(m.mins) !== -1;
					var isUnlocked = watchMins >= m.mins;
					if (isUnlocked && !isClaimed) {
						unclaimedReadyCoins += m.coins;
						unclaimedReadyMins.push(m.mins);
					} else if (!isUnlocked && nextLockedMins === null) {
						nextLockedMins = m.mins;
					}
				});

				if (unclaimedReadyCoins > 0) {
					var updatedClaimed = claimed.concat(unclaimedReadyMins);
					saveClaimedMilestones(updatedClaimed);
					addCoins(unclaimedReadyCoins, 'Watch Milestone (' + unclaimedReadyMins.join('m, ') + 'm)');
					renderWatchMilestones();
					showRewardToast('+' + unclaimedReadyCoins + ' Coins Claimed!');
				} else if (claimed.length >= watchMilestones.length) {
					showRewardToast('All watch rewards completed!');
				} else if (nextLockedMins !== null) {
					var remain = nextLockedMins - watchMins;
					var nextRewardCoins = (watchMilestones.find(function(x){return x.mins===nextLockedMins;}) || {}).coins || 10;
					showRewardToast('Watch ' + remain + 'm more for +' + nextRewardCoins + ' coins');
				} else {
					showRewardToast('Watch dramas to earn coins');
				}
			});
		}

		// Individual Envelope click handler
		var envelopeCards = document.querySelectorAll('#milestones-envelopes-track .milestone-envelope-card');
		envelopeCards.forEach(function(card){
			card.addEventListener('click', function(){
				var targetMins = parseInt(card.getAttribute('data-mins') || '0', 10);
				var rewardCoins = parseInt(card.getAttribute('data-coins') || '0', 10);
				var watchSecs = getDailyWatchSecs();
				var watchMins = Math.floor(watchSecs / 60);
				var claimed = getClaimedMilestones();

				if (claimed.indexOf(targetMins) !== -1) {
					showRewardToast('Already claimed (' + targetMins + 'm)');
					return;
				}

				if (watchMins >= targetMins) {
					claimed.push(targetMins);
					saveClaimedMilestones(claimed);
					addCoins(rewardCoins, 'Watch Milestone ' + targetMins + 'm');
					renderWatchMilestones();
					showRewardToast('+' + rewardCoins + ' Coins Claimed!');
				} else {
					var remain = targetMins - watchMins;
					showRewardToast('Watch ' + remain + 'm more for +' + rewardCoins + ' coins');
				}
			});
		});

		// 4. Share with Friends Task (+50 Coins)
		var btnShare = document.getElementById('btn-share-task');
		if (btnShare) {
			if (sharedToday) {
				btnShare.textContent = 'Completed';
				btnShare.classList.add('is-completed');
			} else {
				btnShare.textContent = 'Share';
				btnShare.classList.remove('is-completed');
			}

			btnShare.addEventListener('click', function(){
				var uid = currentUid || (window.firebaseUser && window.firebaseUser.uid) || localStorage.getItem('short_user_uid');
				if (navigator.share) {
					navigator.share({
						title: document.title,
						text: 'Watch thrilling vertical short drama series on ShortTV!',
						url: window.location.origin
					}).catch(function(){});
				} else {
					navigator.clipboard.writeText(window.location.origin);
					showRewardToast('Link copied!');
				}

				if (!sharedToday) {
					sharedToday = true;
					localStorage.setItem('shorttv_reward_shared_' + todayStr, '1');
					btnShare.textContent = 'Completed';
					btnShare.classList.add('is-completed');
					if (rtdb && uid) {
						rtdb.ref('users/' + uid + '/rewardTasks/' + todayStr + '/shared').set(true);
					}
					addCoins(50, 'Shared with Friends Reward');
					showRewardToast('+50 Coins Awarded!');
				}
			});
		}

		// 5. Watch Ads Task (+30 Coins each, up to 10)
		var btnWatchAd = document.getElementById('btn-watch-ad-task');
		if (btnWatchAd) {
			btnWatchAd.addEventListener('click', function(){
				if (adsCount >= 10) {
					showRewardToast('Daily limit reached (10/10)');
					return;
				}
				var uid = currentUid || (window.firebaseUser && window.firebaseUser.uid) || localStorage.getItem('short_user_uid');

				playRewardedVideo(30, function(){
					adsCount++;
					localStorage.setItem('shorttv_reward_ads_count_' + todayStr, String(adsCount));
					updateAdUI();

					if (rtdb && uid) {
						rtdb.ref('users/' + uid + '/rewardTasks/' + todayStr + '/adsCount').set(adsCount);
					}
				});
			});
		}

		// 6. Rules Modal Triggers
		var btnOpenRules = document.getElementById('btn-open-reward-rules');
		var btnCloseRules = document.getElementById('btn-close-reward-rules');
		var btnGotItRules = document.getElementById('btn-rules-got-it');
		var modalRules = document.getElementById('reward-rules-modal');

		if (btnOpenRules && modalRules) {
			btnOpenRules.addEventListener('click', function(){
				modalRules.style.display = 'flex';
			});
		}
		if (btnCloseRules && modalRules) {
			btnCloseRules.addEventListener('click', function(){
				modalRules.style.display = 'none';
			});
		}
		if (btnGotItRules && modalRules) {
			btnGotItRules.addEventListener('click', function(){
				modalRules.style.display = 'none';
			});
		}
		if (modalRules) {
			modalRules.addEventListener('click', function(e){
				if (e.target.classList.contains('reward-modal-backdrop')) {
					modalRules.style.display = 'none';
				}
			});
		}

		// 7. Initialize Instant Cloud Sync & Real-Time Firebase Synchronization across devices
		function syncAllFromCloud(uid) {
			if (!uid) return;
			var RTDB_URL = getFirebaseRtdbUrl();
			if (!RTDB_URL) return;

			fetch(RTDB_URL + '/users/' + uid + '.json')
				.then(function(res) { return res.json(); })
				.then(function(userData) {
					if (!userData) return;

					// 1. Authoritative Coins Balance
					if (userData.coins !== undefined && userData.coins !== null) {
						var coinsVal = parseInt(userData.coins, 10);
						if (!isNaN(coinsVal)) {
							localStorage.setItem('shorttv_user_coins', String(coinsVal));
							localStorage.setItem('short_user_coins', String(coinsVal));
							updateCoinsDisplay(coinsVal);
						}
					}

					// 2. Check-in Date & Streak
					if (userData.rewardCheckin) {
						var checkin = userData.rewardCheckin;
						if (checkin.lastDate) {
							lastCheckinDate = checkin.lastDate;
							localStorage.setItem('shorttv_reward_checkin_date', checkin.lastDate);
						}
						if (checkin.streak !== undefined) {
							streakCount = parseInt(checkin.streak, 10) || 1;
							localStorage.setItem('shorttv_reward_streak', String(streakCount));
							if (streakEl) streakEl.textContent = streakCount;
						}
						updateCheckinUI();
					}

					// 3. Reward Tasks for Today
					if (userData.rewardTasks && userData.rewardTasks[todayStr]) {
						var tasks = userData.rewardTasks[todayStr];
						if (typeof tasks.adsCount === 'number') {
							adsCount = tasks.adsCount;
							localStorage.setItem('shorttv_reward_ads_count_' + todayStr, String(adsCount));
							updateAdUI();
						}
						if (tasks.videoBonus === true || tasks.videoBonus === 1) {
							localStorage.setItem('shorttv_reward_video_bonus_' + todayStr, '1');
							if (btnWatchVideo) btnWatchVideo.classList.add('is-completed');
							if (btnWatchVideoText) btnWatchVideoText.textContent = 'Completed';
						}
						if (Array.isArray(tasks.claimedMilestones)) {
							localStorage.setItem('shorttv_claimed_milestones_' + todayStr, JSON.stringify(tasks.claimedMilestones));
							renderWatchMilestones();
						}
						if (tasks.shared === true) {
							sharedToday = true;
							localStorage.setItem('shorttv_reward_shared_' + todayStr, '1');
							if (btnShare) {
								btnShare.textContent = 'Completed';
								btnShare.classList.add('is-completed');
							}
						}
					}
				})
				.catch(function(e) {
					console.warn('[Reward Cloud Fetch Warning]', e);
				});
		}

		function attachUserRealtimeListeners(uid) {
			if (!uid) return;
			syncAllFromCloud(uid);

			if (typeof firebase !== 'undefined' && firebase.database) {
				try {
					if (!rtdb) rtdb = firebase.database();
				} catch(e){}
			}
			if (!rtdb) return;

			// Listen to live Coins balance from Firebase RTDB
			rtdb.ref('users/' + uid + '/coins').on('value', function(snap) {
				var v = snap.val();
				if (v !== null && v !== undefined) {
					var coinsVal = parseInt(v, 10);
					if (!isNaN(coinsVal)) {
						localStorage.setItem('shorttv_user_coins', String(coinsVal));
						localStorage.setItem('short_user_coins', String(coinsVal));
						updateCoinsDisplay(coinsVal);
					}
				}
			});

			// Listen to live Check-in status & streak from Firebase RTDB
			rtdb.ref('users/' + uid + '/rewardCheckin').on('value', function(snap) {
				var data = snap.val();
				if (data) {
					if (data.lastDate) {
						lastCheckinDate = data.lastDate;
						localStorage.setItem('shorttv_reward_checkin_date', data.lastDate);
					}
					if (typeof data.streak === 'number' || typeof data.streak === 'string') {
						streakCount = parseInt(data.streak, 10) || 1;
						localStorage.setItem('shorttv_reward_streak', String(streakCount));
						if (streakEl) streakEl.textContent = streakCount;
					}
					updateCheckinUI();
				}
			});

			// Listen to live Reward Tasks for Today
			rtdb.ref('users/' + uid + '/rewardTasks/' + todayStr).on('value', function(snap) {
				var tasks = snap.val();
				if (tasks) {
					if (typeof tasks.adsCount === 'number') {
						adsCount = tasks.adsCount;
						localStorage.setItem('shorttv_reward_ads_count_' + todayStr, String(adsCount));
						updateAdUI();
					}
					if (tasks.videoBonus === true || tasks.videoBonus === 1) {
						localStorage.setItem('shorttv_reward_video_bonus_' + todayStr, '1');
						if (btnWatchVideo) btnWatchVideo.classList.add('is-completed');
						if (btnWatchVideoText) btnWatchVideoText.textContent = 'Completed';
					}
					if (Array.isArray(tasks.claimedMilestones)) {
						localStorage.setItem('shorttv_claimed_milestones_' + todayStr, JSON.stringify(tasks.claimedMilestones));
						renderWatchMilestones();
					}
					if (tasks.shared === true) {
						sharedToday = true;
						localStorage.setItem('shorttv_reward_shared_' + todayStr, '1');
						if (btnShare) {
							btnShare.textContent = 'Completed';
							btnShare.classList.add('is-completed');
						}
					}
				}
			});
		}

		// Cross-tab and window synchronization
		window.addEventListener('storage', function(e) {
			if (e.key === 'shorttv_user_coins' || e.key === 'short_user_coins') {
				updateCoinsDisplay();
			}
			if (e.key === 'shorttv_reward_checkin_date' || e.key === 'shorttv_reward_streak') {
				lastCheckinDate = localStorage.getItem('shorttv_reward_checkin_date') || '';
				streakCount = parseInt(localStorage.getItem('shorttv_reward_streak') || '1', 10);
				if (streakEl) streakEl.textContent = streakCount;
				updateCheckinUI();
			}
			if (e.key && e.key.indexOf('shorttv_reward_ads_count_') === 0) {
				adsCount = parseInt(localStorage.getItem('shorttv_reward_ads_count_' + todayStr) || '0', 10);
				updateAdUI();
			}
		});

		// Immediate proactive startup
		var initialStoredUid = localStorage.getItem('short_user_uid');
		if (initialStoredUid) {
			currentUid = initialStoredUid;
			syncAllFromCloud(initialStoredUid);
			attachUserRealtimeListeners(initialStoredUid);
		}

		if (typeof firebase !== 'undefined') {
			if (firebase.database) {
				try { rtdb = firebase.database(); } catch(e){}
			}
			if (firebase.auth) {
				firebase.auth().onAuthStateChanged(function(user){
					if (user) {
						currentUid = user.uid;
						window.firebaseUser = user;
						attachUserRealtimeListeners(user.uid);
					} else {
						var storedUid = localStorage.getItem('short_user_uid');
						if (storedUid) {
							currentUid = storedUid;
							attachUserRealtimeListeners(storedUid);
						}
					}
				});
			}
		}
	});
})();
</script>

<script>
(function() {
	function syncRewardNav() {
		var allItems = document.querySelectorAll('.short-mobile-bottom-nav .mobile-nav-item, .mobile-bottom-nav .mobile-nav-item');
		if (allItems && allItems.length) {
			allItems.forEach(function(it) {
				var k = (it.getAttribute('data-nav') || '').toLowerCase();
				if (k === 'mobile_rewards' || k === 'rewards' || k === 'reward') {
					it.classList.add('active');
				} else {
					it.classList.remove('active');
				}
			});
		}
	}
	document.addEventListener('DOMContentLoaded', syncRewardNav);
	window.addEventListener('load', syncRewardNav);
	setTimeout(syncRewardNav, 50);
	setTimeout(syncRewardNav, 300);
	setInterval(syncRewardNav, 1500);
})();
</script>

<?php get_footer(); ?>
