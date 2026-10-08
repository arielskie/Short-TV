<?php
/**
 * Template Name: Account & Settings Page
 *
 * Modern Mobile-First Settings Hub & Detailed Views (Account, VIP Billing, Theme & Appearance, Connected Services, Security & Cache).
 *
 * @package Short
 */

get_header();

$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
$user_email   = $is_logged_in ? $current_user->user_email : '';
$display_name = $is_logged_in ? ( $current_user->display_name ?: $current_user->user_login ) : 'Guest Viewer';
$member_since = $is_logged_in ? date( 'F Y', strtotime( $current_user->user_registered ) ) : 'Guest Access';

$user_wp_comments_count = 0;
if ( $is_logged_in && $current_user->ID ) {
	$user_wp_comments_count = (int) get_comments( array(
		'user_id' => $current_user->ID,
		'count'   => true,
	) );
}
if ( ! $user_wp_comments_count && ! empty( $user_email ) ) {
	$user_wp_comments_count = (int) get_comments( array(
		'author_email' => $user_email,
		'count'        => true,
	) );
}

// Subscription check
$sub_status = 'Free Member';
$sub_badge  = 'Free Tier';
$plan_perks = 'Standard Drama Access • Ad-supported Streaming • Free Starter Coins';
$sub_price  = '$0.00';
$sub_period = '/ free';

if ( class_exists( 'SHORT_Subscription' ) && method_exists( 'SHORT_Subscription', 'get_user_plan' ) && $is_logged_in ) {
	$plan_info = SHORT_Subscription::get_user_plan( $current_user->ID );
	if ( ! empty( $plan_info['name'] ) && 'Free Member' !== $plan_info['name'] ) {
		$sub_status = esc_html( $plan_info['name'] );
		$sub_badge  = esc_html( $plan_info['badge'] ?? 'Active' );
		$plan_perks = esc_html( $plan_info['perks'] ?? 'All Dramas Unlocked • 100% Ad-Free • 1080p Full HD' );
		$p_id       = strtolower( $plan_info['plan_id'] ?? '' );
		$p_name     = strtolower( $plan_info['name'] ?? '' );
		if ( strpos( $p_id, 'week' ) !== false || strpos( $p_name, 'week' ) !== false || 'basic' === $p_id ) {
			$sub_price  = '$4.99';
			$sub_period = '/ weekly';
		} elseif ( strpos( $p_id, 'year' ) !== false || strpos( $p_name, 'year' ) !== false || strpos( $p_id, 'annu' ) !== false || 'premium' === $p_id ) {
			$sub_price  = '$49.99';
			$sub_period = '/ yearly';
		} else {
			$sub_price  = '$14.99';
			$sub_period = '/ monthly';
		}
	}
}

$brand_settings = get_option( 'short_brand_settings', array() );
$brand_logo_url = function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : 'https://i.postimg.cc/cH3CM5h4/image.png';
$brand_title    = ! empty( $brand_settings['brand_name'] ) && 'My Blog' !== $brand_settings['brand_name'] ? $brand_settings['brand_name'] : ( get_bloginfo( 'name' ) !== 'My Blog' ? get_bloginfo( 'name' ) : 'ShortTV' );

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

<div class="short-settings-page-wrapper">
	<div class="short-settings-container">

		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 1: SETTINGS SUMMARY HUB (MAIN LANDING)
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="settings-view-pane is-active" id="settings-view-hub">
			
			<!-- Top Bar: Back Button, Title, Search Toggle Button -->
			<div class="settings-top-navbar">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="settings-nav-back" title="<?php esc_attr_e( 'Back to Home', 'short-stream' ); ?>">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</a>
				<h1 class="settings-nav-title"><?php _e( 'Settings', 'short-stream' ); ?></h1>
				<button type="button" class="settings-nav-search-btn" id="btn-toggle-hub-search" title="<?php esc_attr_e( 'Search settings', 'short-stream' ); ?>">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				</button>
			</div>

			<!-- Collapsible Instant Search Dropdown (Toggled by search icon) -->
			<div class="settings-search-bar-wrap" id="settings-search-bar-wrap" style="display:none;">
				<div class="settings-search-box">
					<svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					<input type="text" id="settings-search-input" placeholder="<?php esc_attr_e( 'Search settings...', 'short-stream' ); ?>" autocomplete="off" />
					<button type="button" id="btn-clear-settings-search" class="search-clear-btn">&times;</button>
				</div>
			</div>

			<!-- Section: Account Group -->
			<div class="settings-group-block" data-group="account">
				<div class="settings-group-header">
					<h3 class="settings-group-title"><?php _e( 'Account', 'short-stream' ); ?></h3>
					<p class="settings-group-sub"><?php _e( 'Update your info to keep your account safe and personalized', 'short-stream' ); ?></p>
				</div>
				<div class="settings-menu-card">
					<!-- Account Information -->
					<div class="settings-menu-item" data-search="account profile name email photo display identity" data-target-view="account-info" data-requires-auth="true">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-account">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Account information', 'short-stream' ); ?></div>
								<div class="menu-item-desc" id="hub-account-sub"><?php echo esc_html( $display_name ); ?> &bull; <?php echo esc_html( $user_email ?: 'Guest' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>

					<!-- Appearances (Theme & Colors) -->
					<div class="settings-menu-item" data-search="theme color accent customize appearance dark mode red green blue" data-target-view="theme-color">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-theme">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a7 7 0 0 0 0 14h2a2 2 0 0 0 2-2v-1a2 2 0 0 0-2-2h-2"></path><circle cx="7.5" cy="9.5" r=".5"></circle><circle cx="10.5" cy="6.5" r=".5"></circle><circle cx="13.5" cy="6.5" r=".5"></circle></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Appearances', 'short-stream' ); ?></div>
								<div class="menu-item-desc" id="hub-theme-sub"><?php _e( 'Custom player colors and interface style', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>
				</div>
			</div>

			<!-- Section: Library & Activity Group -->
			<div class="settings-group-block" data-group="library">
				<div class="settings-group-header">
					<h3 class="settings-group-title"><?php _e( 'Library & Activity', 'short-stream' ); ?></h3>
					<p class="settings-group-sub"><?php _e( 'Saved short dramas, bookmarks & watch history', 'short-stream' ); ?></p>
				</div>
				<div class="settings-menu-card">
					<!-- Saved Dramas & Bookmarks -->
					<div class="settings-menu-item" data-search="saved dramas bookmarks watchlist my list favorites collection" data-href="<?php echo esc_url( home_url( '/my-list/' ) ); ?>">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-watchlist" style="background:rgba(239, 68, 68, 0.14); color:#f87171;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Saved & Bookmarks', 'short-stream' ); ?></div>
								<div class="menu-item-desc"><?php _e( 'Access your saved drama collection', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>

					<!-- Watch History -->
					<div class="settings-menu-item" data-search="history watch history recently played watched continue watching" data-href="<?php echo esc_url( home_url( '/history/' ) ); ?>">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-history" style="background:rgba(168, 85, 247, 0.14); color:#c084fc;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Watch History', 'short-stream' ); ?></div>
								<div class="menu-item-desc"><?php _e( 'Resume previously watched short dramas', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>
				</div>
			</div>

			<!-- Section: Membership & Wallet Group -->
			<div class="settings-group-block" data-group="membership">
				<div class="settings-group-header">
					<h3 class="settings-group-title"><?php _e( 'Membership & Wallet', 'short-stream' ); ?></h3>
					<p class="settings-group-sub"><?php _e( 'Manage VIP status, drama coins balance & billing', 'short-stream' ); ?></p>
				</div>
				<div class="settings-menu-card">
					<!-- Membership & Wallet (Merged) -->
					<!-- Membership / VIP -->
					<div class="settings-menu-item" data-search="membership subscription vip plans pricing payment tier manage cancel perks" data-target-view="billing-plans">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-billing">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Membership', 'short-stream' ); ?></div>
								<div class="menu-item-desc" id="hub-billing-sub"><?php echo esc_html( $sub_status ); ?> &bull; <?php echo $is_logged_in ? esc_html__( 'Manage VIP Tier', 'short-stream' ) : esc_html__( 'Free Guest Access', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>

					<!-- Rewards (Daily Coins & Check-in) -->
					<div class="settings-menu-item" data-search="rewards daily checkin free coins bonus streak ads missions watch tasks" data-target-view="rewards">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-rewards" style="background:rgba(251, 191, 36, 0.14); color:#fbbf24;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5" rx="1"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Rewards', 'short-stream' ); ?></div>
								<div class="menu-item-desc"><?php _e( 'Daily check-in, free coins & missions', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>

					<!-- Transactions -->
					<div class="settings-menu-item" data-search="transactions orders receipts purchases billing history payments" data-target-view="transactions" data-requires-auth="true">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-transactions" style="background:rgba(59, 130, 246, 0.14); color:#60a5fa;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Transactions', 'short-stream' ); ?></div>
								<div class="menu-item-desc"><?php _e( 'Coin purchases & order history', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>

					<!-- Security & Privacy (Merged) -->
					<div class="settings-menu-item" data-search="security login details password auth logout signout sessions privacy storage cache clear data reset history temporary cookies" data-target-view="security-privacy" data-requires-auth="true">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-security">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title"><?php _e( 'Security & Privacy', 'short-stream' ); ?></div>
								<div class="menu-item-desc"><?php _e( 'Sessions, cached memory & watch history', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>

					<!-- Notifications (Mobile Hub) -->
					<div class="settings-menu-item" data-search="notifications alerts updates announcements push messages bell releases" data-href="<?php echo esc_url( home_url( '/notification/' ) ); ?>">
						<div class="menu-item-left">
							<div class="menu-item-icon icon-notifications" style="background:rgba(239, 68, 68, 0.14); color:#f87171;">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
							</div>
							<div class="menu-item-text">
								<div class="menu-item-title" style="display:flex; align-items:center; gap:8px;">
									<span><?php _e( 'Notifications', 'short-stream' ); ?></span>
									<span class="settings-inline-notif-badge" id="settings-hub-notif-unread-count" style="display:none; position:static !important; background:var(--theme-accent, #ff2d55); color:#ffffff; font-size:10px; font-weight:800; padding:1px 6px; border-radius:999px; line-height:1.2; vertical-align:middle;"></span>
								</div>
								<div class="menu-item-desc"><?php _e( 'New releases, system announcements & alerts', 'short-stream' ); ?></div>
							</div>
						</div>
						<div class="menu-item-right">
							<svg class="chevron-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</div>
					</div>
				</div>
			</div>

			<!-- App Version Footer -->
			<div class="settings-hub-footer">
				<p class="settings-version-note"><?php _e( 'ShortTV Streaming Portal &bull; v2.4.0', 'short-stream' ); ?></p>
			</div>
		</div>


		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 2: ACCOUNT INFORMATION DETAIL VIEW
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="settings-view-pane" id="settings-view-account-info">
			<div class="settings-top-navbar">
				<button type="button" class="settings-nav-back btn-back-to-hub">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<h2 class="settings-nav-title"><?php _e( 'Account information', 'short-stream' ); ?></h2>
				<div style="width:36px;"></div>
			</div>

			<div class="settings-detail-body">
				<!-- Profile Avatar Center Header -->
				<div class="detail-avatar-hero">
					<div class="detail-profile-identity">
						<div class="detail-avatar-wrap">
							<img id="detail-account-avatar" src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 36 36' fill='none'><circle cx='18' cy='18' r='18' fill='%231f232b'/><circle cx='18' cy='13.5' r='5.5' fill='%2394a3b8'/><path d='M8 29.5c0-5.5 4.5-9 10-9s10 3.5 10 9' fill='%2394a3b8'/></svg>" alt="Avatar" />
							<button type="button" class="detail-change-photo-btn" id="btn-detail-change-photo" title="<?php esc_attr_e( 'Change photo', 'short-stream' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
							</button>
						</div>
						<div class="detail-profile-meta">
							<div class="detail-name-badge-row">
								<h3 class="detail-user-name" id="detail-display-name"><?php echo esc_html( $display_name ); ?></h3>
								<span class="detail-user-badge" id="detail-auth-badge"><?php _e( 'Verified User', 'short-stream' ); ?></span>
							</div>
							
							<!-- Integrated Profile UID Badge & Copy -->
							<div class="detail-uid-row">
								<span class="detail-uid-badge" id="detail-account-uid-pill">UID: guest_session</span>
								<button type="button" class="btn-detail-copy-uid" id="btn-copy-account-uid-inline"><?php _e( 'Copy UID', 'short-stream' ); ?></button>
							</div>
						</div>
					</div>

					<!-- Integrated Profile Stats Strip -->
					<div class="detail-stats-strip">
						<div class="detail-stat-col">
							<div class="detail-stat-val" id="detail-stat-watchlist">0</div>
							<div class="detail-stat-lbl"><?php _e( 'Saved', 'short-stream' ); ?></div>
						</div>
						<div class="detail-stat-col">
							<div class="detail-stat-val" id="detail-stat-likes">0</div>
							<div class="detail-stat-lbl"><?php _e( 'Liked', 'short-stream' ); ?></div>
						</div>
						<div class="detail-stat-col">
							<div class="detail-stat-val" id="detail-stat-ratings">0</div>
							<div class="detail-stat-lbl"><?php _e( 'Rated', 'short-stream' ); ?></div>
						</div>
						<div class="detail-stat-col">
							<div class="detail-stat-val" id="detail-stat-comments"><?php echo esc_html( $user_wp_comments_count ); ?></div>
							<div class="detail-stat-lbl"><?php _e( 'Comments', 'short-stream' ); ?></div>
						</div>
					</div>
				</div>

				<!-- Form Fields Card -->
				<div class="settings-detail-card account-form-card">
					<div class="detail-form-group">
						<label class="detail-label"><?php _e( 'Display Name', 'short-stream' ); ?></label>
						<input type="text" id="detail-input-name" class="detail-input" value="<?php echo esc_attr( $display_name ); ?>" placeholder="<?php esc_attr_e( 'Enter your name', 'short-stream' ); ?>" />
					</div>
					<div class="detail-form-group">
						<label class="detail-label"><?php _e( 'Email Address', 'short-stream' ); ?></label>
						<input type="email" id="detail-input-email" class="detail-input is-readonly" value="<?php echo esc_attr( $user_email ); ?>" readonly />
					</div>
					<div class="detail-form-group">
						<label class="detail-label"><?php _e( 'Member Since', 'short-stream' ); ?></label>
						<input type="text" id="detail-input-since" class="detail-input is-readonly" value="<?php echo esc_attr( $member_since ); ?>" readonly />
					</div>
				</div>

				<div style="display:flex; align-items:center; gap:16px; margin-bottom:28px;">
					<button type="button" class="btn-settings-primary-action" id="btn-save-account-info">
						<?php _e( 'Save Profile Details', 'short-stream' ); ?>
					</button>
					<span id="account-info-saved-toast" style="color:#4ade80; font-size:13px; font-weight:700; opacity:0; transition:opacity 0.2s ease;">✓ Changes saved</span>
				</div>

				<!-- Recent Transactions & Order History -->
				<div class="account-tx-section" style="margin-top:20px;">
					<div class="account-tx-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
						<div>
							<h4 style="font-size:14.5px; font-weight:800; color:#ffffff; margin:0 0 2px; letter-spacing:-0.2px;"><?php _e( 'Recent Transactions', 'short-stream' ); ?></h4>
							<p style="font-size:11.5px; color:#64748b; margin:0;"><?php _e( 'Coins, VIP activations, and rewards activity.', 'short-stream' ); ?></p>
						</div>
						<button type="button" class="btn-open-transactions-view" data-jump-view="transactions" style="background:rgba(255,45,85,0.08) !important; border:1px solid rgba(255,45,85,0.18) !important; outline:none !important; padding:4px 10px !important; border-radius:6px; font-size:12px; font-weight:700; color:var(--theme-accent, #ff2d55); cursor:pointer; display:inline-flex; align-items:center; gap:3px; transition:all 0.15s ease;">
							<span><?php _e( 'View All', 'short-stream' ); ?></span>
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
						</button>
					</div>

					<div class="account-tx-list" id="account-recent-tx-list" style="display:flex; flex-direction:column; background:rgba(255,255,255,0.025); border:1px solid rgba(255,255,255,0.05); border-radius:12px; overflow:hidden;">
						<!-- Dynamic transactions rendered via JS from Firebase / LocalStorage, with clean fallback items -->
						<div class="account-tx-row" style="display:flex; align-items:center; justify-content:space-between; padding:11px 14px; border-bottom:none;">
							<div style="display:flex; align-items:center; gap:11px; min-width:0;">
								<div style="width:32px; height:32px; border-radius:8px; background:rgba(234,179,8,0.14); color:#fbbf24; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a2.5 2.5 0 0 0-5 0c0 2 5 2 5 5a2.5 2.5 0 0 1-5 0"/></svg>
								</div>
								<div style="min-width:0;">
									<div style="font-size:13px; font-weight:700; color:#ffffff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" id="tx-item-title-1"><?php _e( 'Welcome Bonus', 'short-stream' ); ?></div>
									<div style="font-size:11px; color:#64748b; margin-top:2px;" id="tx-item-date-1">Account Creation · Completed</div>
								</div>
							</div>
							<div style="text-align:right; flex-shrink:0;">
								<div style="font-size:13.5px; font-weight:800; color:#4ade80;">+100 Coins</div>
								<div style="font-size:10.5px; color:#64748b; margin-top:1px;">Free Credit</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 3: THEME ACCENT & APPEARANCE DETAIL VIEW
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="settings-view-pane" id="settings-view-theme-color">
			<div class="settings-top-navbar">
				<button type="button" class="settings-nav-back btn-back-to-hub">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<h2 class="settings-nav-title"><?php _e( 'Appearances', 'short-stream' ); ?></h2>
				<div style="width:36px;"></div>
			</div>

			<div class="settings-detail-body">
				<div class="settings-section-intro">
					<h4><?php _e( 'Personalize Player & UI', 'short-stream' ); ?></h4>
					<p><?php _e( 'Choose a vibrant brand color for playback progress bars, VIP badges, and buttons.', 'short-stream' ); ?></p>
				</div>

				<!-- Preset Color Grid -->
				<div class="theme-palette-grid" id="theme-color-palette">
					<button type="button" class="color-swatch-btn" data-color="#E50914" data-name="Crimson Red">
						<span class="swatch-color" style="background-color: #E50914;"></span>
						<span class="swatch-label"><?php _e( 'Crimson Red', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>

					<button type="button" class="color-swatch-btn" data-color="#00DF82" data-name="Neon Green">
						<span class="swatch-color" style="background-color: #00DF82;"></span>
						<span class="swatch-label"><?php _e( 'Neon Green', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>

					<button type="button" class="color-swatch-btn" data-color="#0ea5e9" data-name="Cyber Blue">
						<span class="swatch-color" style="background-color: #0ea5e9;"></span>
						<span class="swatch-label"><?php _e( 'Cyber Blue', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>

					<button type="button" class="color-swatch-btn" data-color="#a855f7" data-name="Royal Purple">
						<span class="swatch-color" style="background-color: #a855f7;"></span>
						<span class="swatch-label"><?php _e( 'Royal Purple', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>

					<button type="button" class="color-swatch-btn" data-color="#f59e0b" data-name="Golden Amber">
						<span class="swatch-color" style="background-color: #f59e0b;"></span>
						<span class="swatch-label"><?php _e( 'Golden Amber', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>

					<button type="button" class="color-swatch-btn" data-color="#ec4899" data-name="Hot Pink">
						<span class="swatch-color" style="background-color: #ec4899;"></span>
						<span class="swatch-label"><?php _e( 'Hot Pink', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>

					<button type="button" class="color-swatch-btn" data-color="#f97316" data-name="Sunset Orange">
						<span class="swatch-color" style="background-color: #f97316;"></span>
						<span class="swatch-label"><?php _e( 'Sunset Orange', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>

					<button type="button" class="color-swatch-btn" data-color="#14b8a6" data-name="Teal Emerald">
						<span class="swatch-color" style="background-color: #14b8a6;"></span>
						<span class="swatch-label"><?php _e( 'Teal Emerald', 'short-stream' ); ?></span>
						<svg class="swatch-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</button>
				</div>

				<!-- Custom Color Picker Input -->
				<div class="custom-color-row">
					<div class="custom-picker-group">
						<label for="theme-custom-picker" class="picker-label"><?php _e( 'Custom Hex Color', 'short-stream' ); ?></label>
						<div class="picker-input-wrapper">
							<input type="color" id="theme-custom-picker" class="native-color-picker" value="#E50914" />
							<input type="text" id="theme-hex-input" class="hex-text-input" placeholder="#E50914" maxlength="7" />
						</div>
					</div>

					<!-- Live Mini Preview -->
					<div class="theme-preview-box">
						<span class="preview-label"><?php _e( 'Live Player Preview', 'short-stream' ); ?></span>
						<div class="mini-player-preview">
							<div class="mini-player-bar">
								<div class="mini-progress-fill" id="mini-preview-progress"></div>
								<div class="mini-progress-knob" id="mini-preview-knob"></div>
							</div>
							<div class="mini-player-controls">
								<span class="mini-badge-preview" id="mini-preview-badge">HD</span>
								<span class="mini-button-preview" id="mini-preview-button">DUB</span>
							</div>
						</div>
					</div>
				</div>

				<div class="theme-actions-row">
					<button type="button" id="btn-reset-theme-color" class="btn-account-ghost">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
						<span><?php _e( 'Reset to Crimson Red', 'short-stream' ); ?></span>
					</button>
					<span class="theme-saved-toast" id="theme-saved-toast"><?php _e( 'Theme updated!', 'short-stream' ); ?></span>
				</div>

				<!-- Section 2: Display Mode & Theme Contrast -->
				<div class="settings-section-intro" style="margin-top: 36px;">
					<h4><?php _e( 'Display Mode & Theme Contrast', 'short-stream' ); ?></h4>
					<p><?php _e( 'Choose your preferred background tone and contrast level for streaming and interface navigation.', 'short-stream' ); ?></p>
				</div>

				<div class="display-mode-cards-grid" id="display-mode-cards-grid">
					<!-- Mode 1: Cinematic Dark -->
					<div class="display-mode-card is-active" data-mode="cinematic-dark">
						<div class="mode-card-preview bg-cinematic-dark">
							<div class="mode-preview-bar"></div>
							<div class="mode-preview-dot"></div>
						</div>
						<div class="mode-card-info">
							<div class="mode-card-title-row">
								<h5 class="mode-card-name"><?php _e( 'Cinematic Dark', 'short-stream' ); ?></h5>
								<span class="mode-check-badge">✓</span>
							</div>
							<p class="mode-card-sub"><?php _e( 'Ambient obsidian dark mode (Default)', 'short-stream' ); ?></p>
						</div>
					</div>

					<!-- Mode 2: OLED Pitch Black -->
					<div class="display-mode-card" data-mode="oled-black">
						<div class="mode-card-preview bg-oled-black">
							<div class="mode-preview-bar"></div>
							<div class="mode-preview-dot"></div>
						</div>
						<div class="mode-card-info">
							<div class="mode-card-title-row">
								<h5 class="mode-card-name"><?php _e( 'OLED Pitch Black', 'short-stream' ); ?></h5>
								<span class="mode-check-badge">✓</span>
							</div>
							<p class="mode-card-sub"><?php _e( 'Pure #000 true black for OLED battery efficiency', 'short-stream' ); ?></p>
						</div>
					</div>

					<!-- Mode 3: Midnight Navy -->
					<div class="display-mode-card" data-mode="midnight-navy">
						<div class="mode-card-preview bg-midnight-navy">
							<div class="mode-preview-bar"></div>
							<div class="mode-preview-dot"></div>
						</div>
						<div class="mode-card-info">
							<div class="mode-card-title-row">
								<h5 class="mode-card-name"><?php _e( 'Midnight Navy', 'short-stream' ); ?></h5>
								<span class="mode-check-badge">✓</span>
							</div>
							<p class="mode-card-sub"><?php _e( 'Deep royal navy blue theater atmosphere', 'short-stream' ); ?></p>
						</div>
					</div>
				</div>

			</div>
		</div>





		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 5: MEMBERSHIP DETAIL VIEW
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="settings-view-pane" id="settings-view-billing-plans">
			<div class="settings-top-navbar">
				<button type="button" class="settings-nav-back btn-back-to-hub">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<h2 class="settings-nav-title"><?php _e( 'Membership', 'short-stream' ); ?></h2>
				<div style="width:36px;"></div>
			</div>

			<div class="settings-detail-body">
				<!-- Section 1: VIP Subscription -->
				<div class="settings-section-intro">
					<h4><?php _e( 'Current Subscription', 'short-stream' ); ?></h4>
					<p><?php _e( 'Manage your VIP pass tier, active drama perks, and subscription benefits.', 'short-stream' ); ?></p>
				</div>

				<!-- Top Hero Subscription Card -->
				<div class="settings-detail-card billing-plan-card" style="margin-bottom:28px;">
					<div class="billing-card-header">
						<div class="billing-plan-title-col">
							<span class="billing-plan-tag" id="detail-plan-badge"><?php echo esc_html( $sub_badge ); ?></span>
							<h3 class="billing-plan-name" id="detail-plan-name"><?php echo esc_html( $sub_status ); ?></h3>
						</div>
						<div class="billing-plan-price-col">
							<span class="billing-price-val" id="detail-plan-price"><?php echo esc_html( $sub_price ); ?></span>
							<span class="billing-price-period" id="detail-plan-period"><?php echo esc_html( $sub_period ); ?></span>
						</div>
					</div>

					<div class="billing-perks-list">
						<div class="billing-perk-item">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
							<span id="detail-plan-perks"><?php echo esc_html( $plan_perks ); ?></span>
						</div>
						<div class="billing-perk-item">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
							<span id="detail-plan-email"><?php echo $is_logged_in ? esc_html( sprintf( __( 'Bound to %s', 'short-stream' ), $user_email ) ) : esc_html__( 'Free Guest Access', 'short-stream' ); ?></span>
						</div>
					</div>

					<div class="membership-plan-actions-row" style="margin-top:16px;">
						<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="btn-settings-primary-action btn-plan-upgrade-action">
							<?php _e( 'Upgrade VIP', 'short-stream' ); ?>
						</a>
						<button type="button" class="btn-plan-cancel-action" id="detail-btn-cancel-sub" style="display:none !important;">
							<?php _e( 'Cancel Plan', 'short-stream' ); ?>
						</button>
					</div>
				</div>

				<!-- Section 2: Coin Top-Up Packages -->
				<div class="settings-section-intro" style="margin-top: 32px;">
					<h4><?php _e( 'Coin Top-Up Packages', 'short-stream' ); ?></h4>
					<p><?php _e( 'Instant coins to unlock premium drama episodes individually without a recurring pass.', 'short-stream' ); ?></p>
				</div>

				<div class="membership-coin-packages-grid">
					<!-- Pack 1: Starter Pack -->
					<div class="membership-coin-card">
						<div class="membership-coin-header">
							<div class="membership-coin-icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
							</div>
							<div class="membership-coin-amount">300 Coins</div>
						</div>
						<p class="membership-coin-sub"><?php _e( 'Unlock ~20 drama episodes', 'short-stream' ); ?></p>
						<div class="membership-coin-price-row">
							<span class="membership-coin-price">$1.99</span>
						</div>
						<a href="<?php echo esc_url( home_url( '/subscription/?tab=coins' ) ); ?>" class="btn-membership-coin-buy">
							<?php _e( 'Get 300 Coins', 'short-stream' ); ?>
						</a>
					</div>

					<!-- Pack 2: Popular Value Pack -->
					<div class="membership-coin-card is-popular">
						<div class="membership-popular-badge"><?php _e( 'POPULAR · +200 BONUS', 'short-stream' ); ?></div>
						<div class="membership-coin-header">
							<div class="membership-coin-icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
							</div>
							<div class="membership-coin-amount">1,200 Coins</div>
						</div>
						<p class="membership-coin-sub"><?php _e( 'Unlock ~80 episodes (Full drama)', 'short-stream' ); ?></p>
						<div class="membership-coin-price-row">
							<span class="membership-coin-price">$4.99</span>
						</div>
						<a href="<?php echo esc_url( home_url( '/subscription/?tab=coins' ) ); ?>" class="btn-membership-coin-buy is-popular-btn">
							<?php _e( 'Get 1,200 Coins', 'short-stream' ); ?>
						</a>
					</div>

					<!-- Pack 3: Super Binge Pack -->
					<div class="membership-coin-card">
						<div class="membership-popular-badge is-green"><?php _e( 'BEST VALUE · +600 BONUS', 'short-stream' ); ?></div>
						<div class="membership-coin-header">
							<div class="membership-coin-icon">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
							</div>
							<div class="membership-coin-amount">3,000 Coins</div>
						</div>
						<p class="membership-coin-sub"><?php _e( 'Unlock ~200 episodes (2-3 dramas)', 'short-stream' ); ?></p>
						<div class="membership-coin-price-row">
							<span class="membership-coin-price">$9.99</span>
						</div>
						<a href="<?php echo esc_url( home_url( '/subscription/?tab=coins' ) ); ?>" class="btn-membership-coin-buy">
							<?php _e( 'Get 3,000 Coins', 'short-stream' ); ?>
						</a>
					</div>
				</div>

				<!-- Section 3: Available VIP Passes -->
				<div class="settings-section-intro" style="margin-top: 36px;">
					<h4><?php _e( 'VIP Pass Options', 'short-stream' ); ?></h4>
					<p><?php _e( 'Unlimited short drama bingeing, 1080p Ultra HD streaming, zero ads, and instant episode unlocks.', 'short-stream' ); ?></p>
				</div>

				<div class="membership-vip-plans-grid">
					<!-- Plan 1: Weekly VIP -->
					<div class="membership-vip-card">
						<div class="membership-vip-header">
							<span class="membership-vip-tier-tag"><?php _e( 'FLEXIBLE', 'short-stream' ); ?></span>
							<h4 class="membership-vip-title"><?php _e( 'Weekly VIP', 'short-stream' ); ?></h4>
							<p class="membership-vip-desc"><?php _e( '7 Days Pass · Flexible weekly access', 'short-stream' ); ?></p>
						</div>
						<div class="membership-vip-price">
							<span class="vip-currency">$</span><span class="vip-val">4.99</span><span class="vip-period">/week</span>
						</div>
						<ul class="membership-vip-perks">
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Unlock All Episodes</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 100% Ad-Free Bingeing</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 720p HD Streaming</li>
						</ul>
						<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="btn-membership-vip-subscribe">
							<?php _e( 'Subscribe Weekly', 'short-stream' ); ?>
						</a>
					</div>

					<!-- Plan 2: Monthly VIP (Featured) -->
					<div class="membership-vip-card is-featured">
						<div class="membership-popular-badge"><?php _e( 'MOST POPULAR', 'short-stream' ); ?></div>
						<div class="membership-vip-header">
							<span class="membership-vip-tier-tag is-accent"><?php _e( 'RECOMMENDED', 'short-stream' ); ?></span>
							<h4 class="membership-vip-title"><?php _e( 'Monthly VIP', 'short-stream' ); ?></h4>
							<p class="membership-vip-desc"><?php _e( '30 Days Full Access · Best Monthly Value', 'short-stream' ); ?></p>
						</div>
						<div class="membership-vip-price">
							<span class="vip-currency">$</span><span class="vip-val">14.99</span><span class="vip-period">/month</span>
						</div>
						<ul class="membership-vip-perks">
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Unlimited Episode Unlocks</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 1080p Ultra HD Quality</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 100% Zero Video Ads</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Exclusive VIP Badge & Multipliers</li>
						</ul>
						<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="btn-membership-vip-subscribe is-featured-btn">
							<?php _e( 'Subscribe Monthly', 'short-stream' ); ?>
						</a>
					</div>

					<!-- Plan 3: Annual VIP -->
					<div class="membership-vip-card">
						<div class="membership-popular-badge is-green"><?php _e( 'SAVE 40%', 'short-stream' ); ?></div>
						<div class="membership-vip-header">
							<span class="membership-vip-tier-tag"><?php _e( 'BEST DEAL', 'short-stream' ); ?></span>
							<h4 class="membership-vip-title"><?php _e( 'Annual VIP', 'short-stream' ); ?></h4>
							<p class="membership-vip-desc"><?php _e( '365 Days Unlimited · Maximum Savings', 'short-stream' ); ?></p>
						</div>
						<div class="membership-vip-price">
							<span class="vip-currency">$</span><span class="vip-val">49.99</span><span class="vip-period">/year</span>
						</div>
						<ul class="membership-vip-perks">
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 365 Days Full Unlimited Pass</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 1080p Ultra HD Streaming</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> 2,000 Bonus Starter Coins</li>
							<li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Priority Early Releases</li>
						</ul>
						<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="btn-membership-vip-subscribe">
							<?php _e( 'Subscribe Annual', 'short-stream' ); ?>
						</a>
					</div>
				</div>

			</div>
		</div>


		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 6: SECURITY & PRIVACY DETAIL VIEW (MERGED)
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="settings-view-pane" id="settings-view-security-privacy">
			<div class="settings-top-navbar">
				<button type="button" class="settings-nav-back btn-back-to-hub">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<h2 class="settings-nav-title"><?php _e( 'Security & Privacy', 'short-stream' ); ?></h2>
				<div style="width:36px;"></div>
			</div>

			<div class="settings-detail-body security-privacy-view-body">
				<!-- Security Health Hero Banner -->
				<div class="security-hero-banner">
					<div class="security-hero-icon-box">
						<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
					</div>
					<div class="security-hero-meta">
						<div class="security-hero-badge-row">
							<span class="security-status-badge">
								<span class="security-pulse-dot"></span>
								<?php _e( 'Protection Active', 'short-stream' ); ?>
							</span>
							<span class="security-enc-badge">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
								<?php _e( 'Encrypted Storage', 'short-stream' ); ?>
							</span>
						</div>
						<h3 class="security-hero-title"><?php _e( 'Account Security & Privacy Center', 'short-stream' ); ?></h3>
						<p class="security-hero-desc"><?php _e( 'Manage your unique identity credentials, storage cache, diagnostic telemetry, and active device sessions.', 'short-stream' ); ?></p>
					</div>
				</div>

				<!-- Section 1: Authentication & Identity Grid -->
				<div class="settings-section-intro">
					<h4><?php _e( 'Identity & Authentication', 'short-stream' ); ?></h4>
					<p><?php _e( 'Your unique account identifier and active device session credentials.', 'short-stream' ); ?></p>
				</div>

				<div class="security-identity-grid">
					<!-- Account UID Card -->
					<div class="security-info-tile">
						<div class="security-tile-header">
							<span class="security-tile-label"><?php _e( 'Account UID (Unique Identifier)', 'short-stream' ); ?></span>
							<span class="security-tile-tag"><?php _e( 'Read-Only', 'short-stream' ); ?></span>
						</div>
						<div class="security-uid-copy-wrap">
							<input type="text" id="detail-input-uid" class="security-uid-input" value="guest_session" readonly />
							<button type="button" class="btn-security-copy" id="btn-copy-account-uid" title="<?php esc_attr_e( 'Copy UID', 'short-stream' ); ?>">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
								<span><?php _e( 'Copy', 'short-stream' ); ?></span>
							</button>
						</div>
					</div>

					<!-- Active Session Card -->
					<div class="security-info-tile">
						<div class="security-tile-header">
							<span class="security-tile-label"><?php _e( 'Current Device Session', 'short-stream' ); ?></span>
							<span class="security-tile-tag tag-green"><?php _e( 'Live', 'short-stream' ); ?></span>
						</div>
						<div class="security-session-box">
							<div class="session-device-icon">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
							</div>
							<div class="session-device-info">
								<div class="session-device-name"><?php _e( 'Web Browser · Current Device', 'short-stream' ); ?></div>
								<div class="session-device-meta">
									<span class="session-dot-green"></span>
									<span><?php _e( 'Active Session · Last synced just now', 'short-stream' ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Section 2: Storage & Cache Data Management -->
				<div class="settings-section-intro" style="margin-top: 28px;">
					<h4><?php _e( 'Manage Cached Data & Local Storage', 'short-stream' ); ?></h4>
					<p><?php _e( 'Free up browser memory, purge pre-buffered video chunks, and reset watch history.', 'short-stream' ); ?></p>
				</div>

				<!-- Visual Storage Meter Card -->
				<div class="security-storage-card">
					<div class="storage-meter-top">
						<div class="storage-meter-info">
							<div class="storage-meter-label"><?php _e( 'Estimated Local Data Cache', 'short-stream' ); ?></div>
							<div class="storage-meter-value">~14.2 MB <span>/ 100 MB Allocation</span></div>
						</div>
						<div class="storage-status-chip">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
							<?php _e( 'Healthy', 'short-stream' ); ?>
						</div>
					</div>
					<div class="storage-bar-track">
						<div class="storage-bar-seg seg-video" style="width: 55%;" title="<?php esc_attr_e( 'Video Playback Buffers (7.8 MB)', 'short-stream' ); ?>"></div>
						<div class="storage-bar-seg seg-history" style="width: 25%;" title="<?php esc_attr_e( 'Watch History & Markers (3.5 MB)', 'short-stream' ); ?>"></div>
						<div class="storage-bar-seg seg-cache" style="width: 20%;" title="<?php esc_attr_e( 'App State & Preferences (2.9 MB)', 'short-stream' ); ?>"></div>
					</div>
					<div class="storage-legend-row">
						<div class="storage-legend-item"><span class="legend-dot dot-video"></span><?php _e( 'Video Buffers (7.8 MB)', 'short-stream' ); ?></div>
						<div class="storage-legend-item"><span class="legend-dot dot-history"></span><?php _e( 'Watch History (3.5 MB)', 'short-stream' ); ?></div>
						<div class="storage-legend-item"><span class="legend-dot dot-cache"></span><?php _e( 'State Cache (2.9 MB)', 'short-stream' ); ?></div>
					</div>
				</div>

				<!-- Cache Action Items List -->
				<div class="security-actions-card">
					<!-- Item 1: Video Playback Cache -->
					<div class="security-action-row">
						<div class="security-action-left">
							<div class="security-action-icon-circle icon-orange">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
							</div>
							<div class="security-action-text">
								<h5 class="action-row-title"><?php _e( 'Video Playback Cache', 'short-stream' ); ?></h5>
								<p class="action-row-desc"><?php _e( 'Temporary video chunks, stream buffer segments, and preloaded drama frames.', 'short-stream' ); ?></p>
							</div>
						</div>
						<div class="security-action-right">
							<button type="button" class="btn-security-action" id="btn-clear-playback-cache">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
								<span><?php _e( 'Clear Cache', 'short-stream' ); ?></span>
							</button>
						</div>
					</div>

					<!-- Item 2: Reset Watch History -->
					<div class="security-action-row">
						<div class="security-action-left">
							<div class="security-action-icon-circle icon-red">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
							</div>
							<div class="security-action-text">
								<h5 class="action-row-title"><?php _e( 'Reset Watch History', 'short-stream' ); ?></h5>
								<p class="action-row-desc"><?php _e( 'Clear continue watching series list, resume timestamps, and episode checkmarks.', 'short-stream' ); ?></p>
							</div>
						</div>
						<div class="security-action-right">
							<button type="button" class="btn-security-action btn-action-outline-danger" id="btn-reset-watch-history">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
								<span><?php _e( 'Reset History', 'short-stream' ); ?></span>
							</button>
						</div>
					</div>
				</div>

				<!-- Section 3: Privacy & Telemetry Preferences -->
				<div class="settings-section-intro" style="margin-top: 28px;">
					<h4><?php _e( 'Privacy & Telemetry Preferences', 'short-stream' ); ?></h4>
					<p><?php _e( 'Control your personal recommendation algorithms and diagnostic stream telemetry.', 'short-stream' ); ?></p>
				</div>

				<div class="security-privacy-toggles-card">
					<div class="privacy-toggle-row">
						<div class="privacy-toggle-info">
							<h5 class="privacy-toggle-title"><?php _e( 'Personalized Recommendations', 'short-stream' ); ?></h5>
							<p class="privacy-toggle-desc"><?php _e( 'Tailor trending drama feeds and bonus coin tasks according to your viewing patterns.', 'short-stream' ); ?></p>
						</div>
						<label class="theme-toggle-switch">
							<input type="checkbox" id="pref-personalized-recs" checked>
							<span class="theme-toggle-slider"></span>
						</label>
					</div>

					<div class="privacy-toggle-row">
						<div class="privacy-toggle-info">
							<h5 class="privacy-toggle-title"><?php _e( 'Anonymous Playback Telemetry', 'short-stream' ); ?></h5>
							<p class="privacy-toggle-desc"><?php _e( 'Send non-identifying stream bitrate and buffering metrics to optimize playback speed.', 'short-stream' ); ?></p>
						</div>
						<label class="theme-toggle-switch">
							<input type="checkbox" id="pref-playback-telemetry" checked>
							<span class="theme-toggle-slider"></span>
						</label>
					</div>
				</div>

				<!-- Section 4: Danger Zone / Session Control -->
				<div class="security-danger-zone-card" style="margin-top: 28px;">
					<div class="danger-zone-header">
						<div class="danger-zone-icon">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
						</div>
						<div class="danger-zone-meta">
							<h4 class="danger-zone-title"><?php _e( 'Revoke & Sign Out Everywhere', 'short-stream' ); ?></h4>
							<p class="danger-zone-desc"><?php _e( 'Immediately invalidate all active session tokens, clear guest credentials, and disconnect all browser tabs.', 'short-stream' ); ?></p>
						</div>
					</div>
					<div class="danger-zone-action">
						<button type="button" class="btn-settings-signout-full" id="btn-security-signout">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
							<span><?php _e( 'Sign Out Everywhere', 'short-stream' ); ?></span>
						</button>
					</div>
				</div>
			</div>
		</div>





		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 8: TRANSACTIONS & ORDER HISTORY DETAIL VIEW
		     ═══════════════════════════════════════════════════════════════ -->
		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 8: TRANSACTIONS & ORDER HISTORY DETAIL VIEW (FINTECH SAAS STYLE)
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="settings-view-pane" id="settings-view-transactions">
			<div class="settings-top-navbar">
				<button type="button" class="settings-nav-back btn-back-to-hub">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<h2 class="settings-nav-title"><?php _e( 'Transactions', 'short-stream' ); ?></h2>
				<div style="width:36px;"></div>
			</div>

			<div class="settings-detail-body" style="max-width:1000px; margin:0 auto; padding-bottom:40px;">
				
				<!-- SaaS Header & Search Bar -->
				<div class="saas-tx-header-wrap">
					<div class="saas-tx-title-box" style="margin-bottom:14px;">
						<h3 style="font-size:20px; font-weight:900; color:#ffffff; margin:0 0 4px; letter-spacing:-0.2px;"><?php _e( 'Transactions Ledger', 'short-stream' ); ?></h3>
						<p style="font-size:13px; color:#94a3b8; margin:0;"><?php _e( 'Real-time billing, credit history, and itemized transaction invoices.', 'short-stream' ); ?></p>
					</div>

					<!-- Unified Controls Grid (Desktop Flex / Mobile 2x2 Grid) -->
					<div class="saas-controls-grid">
						
						<!-- Control 1: Search Input (Row 1 Left on Mobile) -->
						<div class="saas-ctrl-item saas-ctrl-search">
							<label class="saas-ctrl-label"><?php _e( 'Search', 'short-stream' ); ?></label>
							<div class="saas-search-input-wrap">
								<svg class="saas-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
								<input type="text" id="saas-tx-search-input" placeholder="<?php esc_attr_e( 'Search...', 'short-stream' ); ?>" />
							</div>
						</div>

						<!-- Control 2: Date Filter (Row 1 Right on Mobile) -->
						<div class="saas-ctrl-item saas-ctrl-date">
							<label class="saas-ctrl-label"><?php _e( 'Date', 'short-stream' ); ?></label>
							<div class="saas-custom-dropdown" data-filter-id="saas-filter-date">
								<button type="button" class="saas-dropdown-trigger" id="saas-trigger-date">
									<span class="saas-dropdown-label"><?php _e( 'All Time', 'short-stream' ); ?></span>
									<svg class="saas-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
								</button>
								<input type="hidden" id="saas-filter-date" value="all" />
								<div class="saas-dropdown-menu">
									<div class="saas-dropdown-option is-selected" data-value="all"><?php _e( 'All Time', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="7"><?php _e( 'Past 7 Days', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="30"><?php _e( 'Past 30 Days', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="90"><?php _e( 'Past 90 Days', 'short-stream' ); ?></div>
								</div>
							</div>
						</div>

						<!-- Control 3: Type Filter (Row 2 Left on Mobile) -->
						<div class="saas-ctrl-item saas-ctrl-type">
							<label class="saas-ctrl-label"><?php _e( 'Type', 'short-stream' ); ?></label>
							<div class="saas-custom-dropdown" data-filter-id="saas-filter-type">
								<button type="button" class="saas-dropdown-trigger" id="saas-trigger-type">
									<span class="saas-dropdown-label"><?php _e( 'All Types', 'short-stream' ); ?></span>
									<svg class="saas-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
								</button>
								<input type="hidden" id="saas-filter-type" value="all" />
								<div class="saas-dropdown-menu">
									<div class="saas-dropdown-option is-selected" data-value="all"><?php _e( 'All Types', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="topup"><?php _e( 'Coin Purchases', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="vip"><?php _e( 'VIP Membership', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="reward"><?php _e( 'Check-in & Rewards', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="unlock"><?php _e( 'Episode Unlocks', 'short-stream' ); ?></div>
								</div>
							</div>
						</div>

						<!-- Control 4: Status Filter (Row 2 Right on Mobile) -->
						<div class="saas-ctrl-item saas-ctrl-status">
							<label class="saas-ctrl-label"><?php _e( 'Status', 'short-stream' ); ?></label>
							<div class="saas-custom-dropdown" data-filter-id="saas-filter-status">
								<button type="button" class="saas-dropdown-trigger" id="saas-trigger-status">
									<span class="saas-dropdown-label"><?php _e( 'All Statuses', 'short-stream' ); ?></span>
									<svg class="saas-dropdown-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
								</button>
								<input type="hidden" id="saas-filter-status" value="all" />
								<div class="saas-dropdown-menu">
									<div class="saas-dropdown-option is-selected" data-value="all"><?php _e( 'All Statuses', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="completed"><?php _e( 'Success / Completed', 'short-stream' ); ?></div>
									<div class="saas-dropdown-option" data-value="pending"><?php _e( 'Pending', 'short-stream' ); ?></div>
								</div>
							</div>
						</div>

					</div>
				</div>

				<!-- SaaS Table Card -->
				<div class="saas-tx-card">
					<div class="saas-tx-table-container" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
						<table class="saas-tx-table" style="width:100%; min-width:760px; border-collapse:collapse; text-align:left;">
							<thead>
								<tr style="border-bottom:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.015);">
									<th style="padding:14px 18px; font-size:11.5px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.6px; max-width:240px; width:30%;"><?php _e( 'Name / Item', 'short-stream' ); ?></th>
									<th style="padding:14px 16px; font-size:11.5px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.6px; white-space:nowrap; width:18%;"><?php _e( 'Date', 'short-stream' ); ?></th>
									<th style="padding:14px 16px; font-size:11.5px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.6px; white-space:nowrap; width:15%;"><?php _e( 'Invoice ID', 'short-stream' ); ?></th>
									<th style="padding:14px 16px; font-size:11.5px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.6px; text-align:right; white-space:nowrap; width:15%;"><?php _e( 'Amount', 'short-stream' ); ?></th>
									<th style="padding:14px 16px; font-size:11.5px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.6px; text-align:center; white-space:nowrap; width:12%;"><?php _e( 'Status', 'short-stream' ); ?></th>
									<th style="padding:14px 18px; font-size:11.5px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.6px; text-align:right; white-space:nowrap; width:10%;"><?php _e( 'Action', 'short-stream' ); ?></th>
								</tr>
							</thead>
							<tbody id="saas-tx-table-body">
								<!-- Dynamic SaaS rows injected by JS -->
							</tbody>
						</table>
					</div>

					<!-- SaaS Compact List for Mobile View -->
					<div class="saas-mobile-tx-list" id="saas-mobile-tx-list">
						<!-- Injected by JS -->
					</div>

					<div id="saas-tx-empty-state" style="display:none; padding:48px 20px; text-align:center;">
						<div style="width:48px; height:48px; border-radius:12px; background:rgba(255,255,255,0.04); color:#64748b; display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px;">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
						</div>
						<div style="font-size:14px; font-weight:700; color:#ffffff; margin-bottom:4px;"><?php _e( 'No transactions found', 'short-stream' ); ?></div>
						<div style="font-size:12px; color:#64748b;"><?php _e( 'Try clearing your search or adjusting the filters.', 'short-stream' ); ?></div>
					</div>
				</div>

			</div>
		</div>

		<!-- ═══════════════════════════════════════════════════════════════
		     VIEW 8: REWARDS & DAILY COINS PORTAL
		     ═══════════════════════════════════════════════════════════════ -->
		<div class="settings-view-pane" id="settings-view-rewards">
			<div class="settings-top-navbar">
				<button type="button" class="settings-nav-back btn-back-to-hub">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
				</button>
				<h2 class="settings-nav-title"><?php _e( 'Rewards', 'short-stream' ); ?></h2>
				<div style="width:36px;"></div>
			</div>

			<div class="settings-detail-body reward-settings-detail-body">
				<!-- Top Hero Header: My Coins & Rules -->
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

				</div><!-- /.reward-main-2col-layout -->
			</div>
		</div>

	</div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     TRANSACTION RECEIPT POPUP MODAL (INSPIRED BY CORPORATE SUMMARY INVOICE)
     ═══════════════════════════════════════════════════════════════ -->
<div id="modal-transaction-receipt" class="tx-receipt-backdrop" style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:20px; box-sizing:border-box;">
	<div class="tx-receipt-card" style="background:#13161f; border:1px solid rgba(255,255,255,0.12); border-radius:20px; width:100%; max-width:580px; box-shadow:0 30px 80px rgba(0,0,0,0.85); overflow:hidden; display:flex; flex-direction:column; animation: receiptPop 0.25s cubic-bezier(0.16, 1, 0.3, 1); position:relative;">
		
		<!-- Modal Top Action Header (Screen only) -->
		<div class="rcpt-screen-header" style="background:linear-gradient(135deg, #181b26 0%, #0f1118 100%); padding:18px 24px; border-bottom:1px solid rgba(255,255,255,0.08); display:flex; justify-content:space-between; align-items:center;">
			<div style="display:flex; align-items:center; gap:8px;">
				<div style="width:8px; height:8px; border-radius:50%; background:#22c55e; box-shadow:0 0 10px #22c55e;"></div>
				<span style="font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.8px; color:#fbbf24;">Official Digital Invoice</span>
			</div>
			<button type="button" id="btn-close-tx-receipt" style="background:rgba(255,255,255,0.06); border:none; color:#94a3b8; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.15s;" title="Close">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
			</button>
		</div>

		<!-- Scrollable Document Container -->
		<div class="tx-receipt-scroll-wrap">
			<!-- Printable Receipt Document Body Area -->
			<div id="tx-receipt-printable-area" style="padding:28px 32px 24px; font-size:13.5px; line-height:1.5;">
				
				<!-- Document Top Brand Header -->
				<div class="rcpt-doc-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; padding-bottom:20px;">
					<div style="display:flex; flex-direction:column; gap:6px;">
						<div class="rcpt-logo-container" style="display:inline-flex; align-items:center; gap:10px;">
							<img src="<?php echo esc_url( $brand_logo_url ); ?>" alt="<?php echo esc_attr( $brand_title ); ?>" class="rcpt-brand-logo-img" style="height:36px; max-width:160px; object-fit:contain;" />
						</div>
						<div class="rcpt-text-sub" style="font-size:11px; letter-spacing:0.3px;">Official Streaming Entertainment Portal</div>
					</div>
					<div class="rcpt-doc-company-info" style="text-align:right; font-size:11.5px; line-height:1.5; padding-right:12px;">
						<div class="rcpt-text-primary" style="font-weight:700; font-size:12.5px;"><?php echo esc_html( $brand_title ); ?> Online Services</div>
						<div class="rcpt-text-sub">Digital Services &bull; Order Fulfillment</div>
						<div style="font-family:monospace; color:#0284c7; font-weight:700;" id="rcpt-support-domain">support@<?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></div>
					</div>
				</div>

				<!-- Salutation Message -->
				<div class="rcpt-salutation-block" style="margin-bottom:20px;">
					<div class="rcpt-text-title" style="font-size:14px; font-weight:800; margin-bottom:6px;">
						Dear Customer (<span id="rcpt-customer-salutation" style="color:#0284c7; font-weight:700;">user@example.com</span>),
					</div>
					<p class="rcpt-text-sub" style="margin:0; font-size:12.5px; line-height:1.5;">
						Thank you for your transaction with <?php echo esc_html( $brand_title ); ?>. Your purchase information and wallet credits have been verified and processed below.
					</p>
				</div>

				<!-- Summary Section Header -->
				<div class="rcpt-summary-heading" style="margin-bottom:14px; padding-left:10px;">
					<h4 style="margin:0; font-size:15px; font-weight:900; color:#d97706; letter-spacing:0.8px; text-transform:uppercase;">SUMMARY</h4>
					<div class="rcpt-summary-row" style="display:flex; flex-wrap:wrap; gap:18px; margin-top:8px; font-size:12px;">
						<div><strong class="rcpt-label">DATE:</strong> <span id="rcpt-summary-date" class="rcpt-data" style="font-weight:700;">Oct 5, 2026, 04:02 PM</span></div>
						<div><strong class="rcpt-label">PAYMENT GATEWAY:</strong> <span id="rcpt-summary-mode" class="rcpt-data" style="font-weight:700;">Lemon Squeezy</span></div>
						<div><strong class="rcpt-label">ORDER REF:</strong> <span id="rcpt-summary-ref" style="font-weight:800; font-family:monospace; color:#0284c7;">LS-974217</span></div>
					</div>
				</div>

				<!-- High-Contrast Structured Pricing Table (Treble Style) -->
				<div class="rcpt-table-wrap" style="border-radius:10px; overflow:hidden; margin-bottom:20px;">
					<table style="width:100%; border-collapse:collapse; text-align:left; font-size:12.5px;">
						<thead>
							<tr class="rcpt-table-thead" style="background:#f59e0b; color:#ffffff; font-weight:800; font-size:11.5px; text-transform:uppercase; letter-spacing:0.5px;">
								<th style="padding:10px 14px; color:#ffffff !important;">ITEM / PACKAGE</th>
								<th style="padding:10px 14px; text-align:center; color:#ffffff !important;">CREDITS</th>
								<th style="padding:10px 14px; text-align:center; color:#ffffff !important;">PRICE / PAID</th>
								<th style="padding:10px 14px; text-align:center; color:#ffffff !important; vertical-align:middle;">STATUS</th>
							</tr>
						</thead>
						<tbody class="rcpt-table-tbody">
							<tr class="rcpt-table-row">
								<td style="padding:12px 14px; font-weight:700; vertical-align:middle;" id="rcpt-table-item">Starter Pack (300 Coins)</td>
								<td style="padding:12px 14px; text-align:center; font-weight:800; color:#15803d !important; vertical-align:middle;" id="rcpt-table-coins">+300 Coins</td>
								<td style="padding:12px 14px; text-align:center; font-weight:800; vertical-align:middle;" id="rcpt-table-price">$1.99</td>
								<td style="padding:12px 14px; text-align:center; vertical-align:middle;" id="rcpt-table-status-cell">
									<svg width="92" height="24" viewBox="0 0 92 24" style="display:inline-block; vertical-align:middle;">
										<rect width="92" height="24" rx="12" fill="#dcfce7" stroke="#86efac" stroke-width="1"/>
										<text x="46" y="16" text-anchor="middle" font-size="11.5" font-weight="800" fill="#15803d" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif">Completed</text>
									</svg>
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<!-- Treble Disclaimer & Terms Note -->
				<div class="rcpt-disclaimer-box" style="margin-bottom:18px; font-size:11.5px; line-height:1.5;">
					<p class="rcpt-text-sub" style="margin:0 0 6px;">
						Your wallet coins have been added to your balance immediately. Coins can be used across all desktop and mobile devices without expiration.
					</p>
					<p class="rcpt-text-sub" style="margin:0;">
						Need assistance with this order? Visit our <a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" style="color:#d97706; text-decoration:none; font-weight:700;">Account Support Hub</a> or email our 24/7 customer service.
					</p>
				</div>

				<!-- Document Bottom Footer Brand Line -->
				<div class="rcpt-doc-footer" style="padding-top:14px; display:flex; justify-content:space-between; align-items:center; font-size:10.5px;">
					<div>&copy; <?php echo date('Y'); ?> <?php echo esc_html( $brand_title ); ?> &bull; All Rights Reserved</div>
					<div style="display:flex; gap:12px;">
						<span>Privacy Policy</span>
						<span>&bull;</span>
						<span>Terms of Service</span>
					</div>
				</div>

			</div>
		</div>

		<!-- Modal Action Buttons (Screen Only) -->
		<div class="rcpt-action-bar" style="padding:14px 24px 18px; background:rgba(0,0,0,0.35); border-top:1px solid rgba(255,255,255,0.08); display:flex; flex-wrap:wrap; gap:10px;">
			<button type="button" id="btn-print-tx-receipt" style="flex:1; min-width:140px; height:42px; background:linear-gradient(135deg, #e11d48, #be123c); color:#ffffff; border:none; border-radius:10px; font-size:13px; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 6px 20px rgba(225,29,72,0.35); transition:all 0.15s;">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
				<span>Print / Save PDF</span>
			</button>
			<button type="button" id="btn-download-tx-receipt-img" style="flex:1; min-width:140px; height:42px; background:linear-gradient(135deg, #2563eb, #1d4ed8); color:#ffffff; border:none; border-radius:10px; font-size:13px; font-weight:800; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 6px 20px rgba(37,99,235,0.35); transition:all 0.15s;">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
				<span>Download Image</span>
			</button>
			<button type="button" id="btn-close-tx-receipt-secondary" style="height:42px; padding:0 18px; background:rgba(255,255,255,0.06); color:#cbd5e1; border:1px solid rgba(255,255,255,0.08); border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; transition:all 0.15s;">
				Close
			</button>
		</div>

	</div>
</div>

<style>
@keyframes receiptPop {
	0% { opacity:0; transform:scale(0.94); }
	100% { opacity:1; transform:scale(1); }
}
.account-tx-row {
	padding: 16px 20px !important;
	border-radius: 14px !important;
}
.account-tx-row.has-receipt {
	cursor: pointer;
}
.account-tx-row.has-receipt:hover {
	background: rgba(255,255,255,0.06) !important;
	transform: translateY(-1px);
}
.account-tx-receipt-btn {
	background: rgba(255, 255, 255, 0.08);
	border: 1px solid rgba(255, 255, 255, 0.14);
	color: #e2e8f0;
	font-size: 13px;
	font-weight: 700;
	padding: 8px 16px;
	border-radius: 10px;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 7px;
	transition: all 0.2s ease;
	margin-left: 14px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}
.account-tx-receipt-btn:hover {
	background: rgba(234, 179, 8, 0.2);
	border-color: rgba(234, 179, 8, 0.5);
	color: #fbbf24;
	transform: translateY(-1px);
	box-shadow: 0 4px 14px rgba(234, 179, 8, 0.25);
}

/* SaaS Transactions Table & Details Button */
.saas-tx-table tr.saas-row {
	border-bottom: 1px solid rgba(255, 255, 255, 0.05);
	transition: background 0.15s ease;
	cursor: pointer;
}
.saas-tx-table tr.saas-row:last-child {
	border-bottom: none;
}
.saas-tx-table tr.saas-row:hover {
	background: rgba(255, 255, 255, 0.04);
}
.saas-btn-details {
	background: rgba(255, 255, 255, 0.06);
	border: 1px solid rgba(255, 255, 255, 0.14);
	color: #f1f5f9;
	font-size: 12px;
	font-weight: 700;
	padding: 6px 16px;
	border-radius: 8px;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 6px;
	transition: all 0.18s ease;
}
.saas-btn-details:hover {
	background: #ffffff;
	border-color: #ffffff;
	color: #0f172a;
	box-shadow: 0 4px 14px rgba(255, 255, 255, 0.2);
	transform: translateY(-1px);
}

/* SaaS Controls Grid & Dropdown Styles */
.saas-tx-card {
	background: rgba(255, 255, 255, 0.02);
	border: 1px solid rgba(255, 255, 255, 0.07);
	border-radius: 18px;
	overflow: hidden;
	box-shadow: 0 12px 36px rgba(0, 0, 0, 0.35);
}

.saas-controls-grid {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
	align-items: flex-end;
	margin-bottom: 18px;
}
.saas-ctrl-item {
	display: flex;
	flex-direction: column;
}
.saas-ctrl-search {
	flex: 2;
	min-width: 220px;
}
.saas-ctrl-type,
.saas-ctrl-date,
.saas-ctrl-status {
	flex: 1;
	min-width: 140px;
}
.saas-ctrl-label {
	display: block;
	font-size: 11.5px;
	font-weight: 700;
	color: #94a3b8;
	margin-bottom: 6px;
}
.saas-search-input-wrap {
	position: relative;
	width: 100%;
}
.saas-search-icon {
	position: absolute;
	left: 12px;
	top: 50%;
	transform: translateY(-50%);
	color: #64748b;
	pointer-events: none;
}
.saas-search-input-wrap input {
	width: 100%;
	height: 40px;
	background: rgba(255, 255, 255, 0.04);
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 10px;
	padding: 0 12px 0 36px;
	color: #ffffff;
	font-size: 13px;
	box-sizing: border-box;
	outline: none;
	transition: all 0.2s;
}
.saas-search-input-wrap input:focus {
	border-color: rgba(234, 179, 8, 0.5);
	background: rgba(255, 255, 255, 0.07);
}

/* Custom SaaS Dropdown Styles */
.saas-custom-dropdown {
	position: relative;
	width: 100%;
}
.saas-dropdown-trigger {
	width: 100%;
	height: 40px;
	background: rgba(255, 255, 255, 0.04);
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-radius: 10px;
	padding: 0 12px;
	color: #ffffff;
	font-size: 13px;
	font-weight: 600;
	display: flex;
	align-items: center;
	justify-content: space-between;
	cursor: pointer;
	box-sizing: border-box;
	transition: all 0.2s ease;
	outline: none;
}
.saas-dropdown-trigger:hover,
.saas-custom-dropdown.is-open .saas-dropdown-trigger {
	background: rgba(255, 255, 255, 0.08);
	border-color: rgba(234, 179, 8, 0.5);
}
.saas-dropdown-arrow {
	color: #94a3b8;
	transition: transform 0.2s ease;
}
.saas-custom-dropdown.is-open .saas-dropdown-arrow {
	transform: rotate(180deg);
	color: #fbbf24;
}
.saas-dropdown-menu {
	display: none;
	position: absolute;
	top: calc(100% + 6px);
	left: 0;
	right: 0;
	background: #181b26;
	border: 1px solid rgba(255, 255, 255, 0.14);
	border-radius: 12px;
	padding: 6px;
	z-index: 1000;
	box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7);
	animation: saasDropFade 0.15s ease-out;
}
.saas-custom-dropdown.is-open .saas-dropdown-menu {
	display: block;
}
@keyframes saasDropFade {
	0% { opacity: 0; transform: translateY(-4px); }
	100% { opacity: 1; transform: translateY(0); }
}
.saas-dropdown-option {
	padding: 9px 12px;
	font-size: 12.5px;
	font-weight: 600;
	color: #cbd5e1;
	border-radius: 8px;
	cursor: pointer;
	transition: all 0.15s ease;
}
.saas-dropdown-option:hover {
	background: rgba(255, 255, 255, 0.08);
	color: #ffffff;
}
.saas-dropdown-option.is-selected {
	background: rgba(234, 179, 8, 0.15);
	color: #fbbf24;
	font-weight: 700;
}

/* Mobile Responsive Compact Mode */
@media (max-width: 680px) {
	#settings-view-transactions .settings-detail-body {
		padding: 0 !important;
	}
	.saas-tx-header-wrap {
		margin-bottom: 14px;
	}
	.saas-controls-grid {
		display: grid !important;
		grid-template-columns: 1fr 1fr !important;
		gap: 8px !important;
		margin-bottom: 12px !important;
	}
	.saas-ctrl-search {
		grid-column: 1 / 2 !important;
		min-width: 0 !important;
	}
	.saas-ctrl-date {
		grid-column: 2 / 3 !important;
		min-width: 0 !important;
	}
	.saas-ctrl-type {
		grid-column: 1 / 2 !important;
		min-width: 0 !important;
	}
	.saas-ctrl-status {
		grid-column: 2 / 3 !important;
		min-width: 0 !important;
	}
	.saas-tx-card {
		background: transparent !important;
		border: none !important;
		border-radius: 0 !important;
		box-shadow: none !important;
		padding: 0 !important;
	}
	.saas-tx-table-container {
		display: none !important;
	}
	.saas-mobile-tx-list {
		display: flex !important;
		flex-direction: column;
		gap: 8px;
		padding: 0 !important;
	}
	.saas-mobile-card {
		background: rgba(255, 255, 255, 0.035);
		border: 1px solid rgba(255, 255, 255, 0.07);
		border-radius: 12px;
		padding: 10px 12px;
		display: flex;
		flex-direction: column;
		gap: 8px;
		transition: background 0.15s ease;
		cursor: pointer;
	}
	.saas-mobile-card:active,
	.saas-mobile-card:hover {
		background: rgba(255, 255, 255, 0.06);
	}
	.saas-mob-card-top {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	.saas-mob-avatar {
		width: 34px;
		height: 34px;
		border-radius: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}
	.saas-mob-info {
		flex: 1;
		min-width: 0;
	}
	.saas-mob-title {
		font-size: 13px;
		font-weight: 700;
		color: #ffffff;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}
	.saas-mob-sub {
		font-size: 10.5px;
		color: #94a3b8;
		margin-top: 1px;
	}
	.saas-mob-amount {
		font-size: 13px;
		font-weight: 800;
		text-align: right;
		white-space: nowrap;
	}
	.saas-mob-card-bottom {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding-top: 6px;
		border-top: 1px solid rgba(255, 255, 255, 0.04);
	}
	.saas-mob-btn-details {
		padding: 4px 10px !important;
		font-size: 11px !important;
		border-radius: 6px !important;
	}
}

@media (min-width: 681px) {
	.saas-tx-table-container {
		display: block !important;
	}
	.saas-mobile-tx-list {
		display: none !important;
	}
}
/* Modal Receipt Responsive & Mobile Full-Screen Styles */
.tx-receipt-backdrop {
	display: none;
	position: fixed;
	inset: 0;
	z-index: 999999;
	background: rgba(0, 0, 0, 0.88);
	backdrop-filter: blur(12px);
	-webkit-backdrop-filter: blur(12px);
	align-items: center;
	justify-content: center;
	padding: 20px;
	box-sizing: border-box;
}
.tx-receipt-card {
	background: #13161f;
	border: 1px solid rgba(255, 255, 255, 0.12);
	border-radius: 20px;
	width: 100%;
	max-width: 580px;
	max-height: 90vh;
	box-shadow: 0 30px 80px rgba(0, 0, 0, 0.85);
	overflow: hidden;
	display: flex;
	flex-direction: column;
	animation: receiptPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
	position: relative;
}
.tx-receipt-scroll-wrap {
	flex: 1;
	overflow-y: auto;
	-webkit-overflow-scrolling: touch;
	overscroll-behavior: contain;
}

@media (max-width: 680px) {
	.tx-receipt-backdrop {
		padding: 0 !important;
		align-items: stretch !important;
		justify-content: stretch !important;
	}
	.tx-receipt-card {
		max-width: 100% !important;
		max-height: 100% !important;
		height: 100% !important;
		border-radius: 0 !important;
		border: none !important;
	}
	.rcpt-screen-header {
		padding: calc(env(safe-area-inset-top, 0px) + 14px) 16px 14px 16px !important;
	}
	#tx-receipt-printable-area {
		padding: 18px 16px 20px 16px !important;
	}
	.rcpt-doc-header {
		flex-direction: column !important;
		gap: 14px !important;
		align-items: flex-start !important;
		padding-bottom: 14px !important;
		margin-bottom: 16px !important;
	}
	.rcpt-doc-company-info {
		text-align: left !important;
		border-right: none !important;
		border-left: 3px solid #fbbf24 !important;
		padding-right: 0 !important;
		padding-left: 10px !important;
	}
	.rcpt-summary-row {
		flex-direction: column !important;
		gap: 6px !important;
	}
	.rcpt-action-bar {
		padding: 12px 16px calc(env(safe-area-inset-bottom, 0px) + 14px) 16px !important;
		gap: 8px !important;
	}
	.rcpt-action-bar button {
		height: 44px !important;
		font-size: 13px !important;
	}
}

#tx-receipt-printable-area {
	color: #f8fafc;
	background: #13161f;
}
#tx-receipt-printable-area .rcpt-doc-header {
	border-bottom: 1px solid rgba(255,255,255,0.08);
}
#tx-receipt-printable-area .rcpt-doc-company-info {
	border-right: 3px solid #fbbf24;
}
#tx-receipt-printable-area .rcpt-text-primary {
	color: #ffffff;
}
#tx-receipt-printable-area .rcpt-text-title {
	color: #ffffff;
}
#tx-receipt-printable-area .rcpt-text-sub {
	color: #94a3b8;
}
#tx-receipt-printable-area .rcpt-summary-heading {
	border-left: 3px solid #fbbf24;
}
#tx-receipt-printable-area .rcpt-label {
	color: #94a3b8;
}
#tx-receipt-printable-area .rcpt-data {
	color: #ffffff;
}
#tx-receipt-printable-area .rcpt-table-wrap {
	border: 1px solid rgba(255,255,255,0.1);
	box-shadow: 0 4px 14px rgba(0,0,0,0.25);
}
#tx-receipt-printable-area .rcpt-table-tbody {
	background: rgba(255,255,255,0.03);
}
#tx-receipt-printable-area .rcpt-table-row {
	color: #ffffff;
}
#tx-receipt-printable-area .rcpt-table-row td {
	border-right: 1px solid rgba(255,255,255,0.06);
	color: #ffffff;
}
#tx-receipt-printable-area .rcpt-table-row td:last-child {
	border-right: none;
}
#tx-receipt-printable-area .rcpt-doc-footer {
	border-top: 1px solid rgba(255,255,255,0.08);
	color: #64748b;
}
#rcpt-table-status {
	display: inline-block !important;
	font-size: 11px !important;
	font-weight: 800 !important;
	padding: 2px 10px 4px 10px !important;
	line-height: 14px !important;
	border-radius: 999px !important;
	background: #dcfce7 !important;
	color: #15803d !important;
	border: 1px solid #86efac !important;
	text-align: center !important;
	vertical-align: middle !important;
	box-sizing: border-box !important;
}

/* ═══════════════════════════════════════════════════════════════
   PREMIUM 1-PAGE STRICT PRINT & PDF STYLESHEET
   ═══════════════════════════════════════════════════════════════ */
@media print {
	@page {
		margin: 10mm 12mm !important;
		size: letter portrait;
	}
	html, body {
		background: #ffffff !important;
		color: #0f172a !important;
		height: auto !important;
		min-height: auto !important;
		overflow: visible !important;
		margin: 0 !important;
		padding: 0 !important;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
	}
	/* Hide everything outside of print iframe */
	body > *:not(#tx-receipt-print-frame),
	.short-settings-page-wrapper,
	#modal-transaction-receipt,
	#page,
	header,
	footer,
	nav {
		display: none !important;
		visibility: hidden !important;
		height: 0 !important;
	}
}
</style>


<!-- ═══ AUTH-GATE MODAL (shown when guest taps a protected setting) ═══ -->
<div id="auth-gate-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); align-items:center; justify-content:center; padding:20px; box-sizing:border-box;">
	<div style="background:#1a1d27; border-radius:22px; max-width:340px; width:100%; padding:32px 24px 28px; text-align:center; position:relative; border:none !important; outline:none !important; box-shadow:0 24px 60px rgba(0,0,0,0.85);">
		<button id="auth-gate-close" style="position:absolute;top:14px;right:16px;background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer;line-height:1;">&#x2715;</button>
		<div style="width:56px;height:56px;border-radius:50%;background:rgba(229,9,20,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
			<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#E50914" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
		</div>
		<h3 style="color:#fff;font-size:18px;font-weight:800;margin:0 0 8px;">Sign In Required</h3>
		<p style="color:#94a3b8;font-size:14px;margin:0 0 24px;line-height:1.5;" id="auth-gate-msg">Please sign in to access this setting.</p>
		<button id="auth-gate-google-btn" style="width:100%;display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#0f172a;border:none;border-radius:50px;padding:13px 20px;font-size:15px;font-weight:700;cursor:pointer;margin-bottom:12px;box-shadow:0 4px 14px rgba(0,0,0,0.25);">
			<svg width="20" height="20" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.9C6.2 7.4 8.9 5 12 5z"/><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/><path fill="#FBBC05" d="M5.3 14.7c-.2-.7-.4-1.5-.4-2.4s.2-1.7.4-2.4L1.6 7c-.8 1.6-1.3 3.4-1.3 5.3 0 1.9.5 3.7 1.3 5.3l3.7-2.9z"/><path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.4-6.7-5.3L1.6 16C3.5 19.8 7.4 23 12 23z"/></svg>
			Sign In with Google
		</button>
		<a id="auth-gate-login-link" href="<?php echo esc_url( home_url( '/login/' ) ); ?>" style="display:block;width:100%;box-sizing:border-box;text-align:center;background:rgba(255,255,255,0.08);color:#cbd5e1;border:none;border-radius:50px;padding:12px 20px;font-size:14px;font-weight:600;text-decoration:none;">Sign In / Sign Up</a>
	</div>
</div>

<!-- Floating Toast Feedback for Rewards -->
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
   MODERN MOBILE-FIRST SETTINGS SUMMARY & DETAIL VIEWS (#121212)
   ───────────────────────────────────────────────────────────── */
/* Suppress site header, genre bar & footer on Settings page */
header#short-header,
.short-header,
.site-header,
.reel-header,
.short-mobile-genre-bar,
.mobile-genre-bar,
.genre-bar-scroll-wrapper,
.short-footer,
.site-footer,
footer.short-footer,
footer.site-footer,
footer {
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

html,
body,
#page,
.site,
.short-main-content {
	background: #121212 !important;
	background-color: #121212 !important;
	min-height: 100vh !important;
}

.short-settings-page-wrapper {
	min-height: 100vh !important;
	background: #121212 !important;
	background-color: #121212 !important;
	padding: 0 16px 20px 16px !important;
	box-sizing: border-box !important;
	color: #ffffff !important;
	font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
}

@media (max-width: 768px) {
	.short-settings-page-wrapper {
		padding: 0 14px calc(env(safe-area-inset-bottom, 0px) + 84px) 14px !important;
	}
	.short-settings-container {
		padding-top: calc(env(safe-area-inset-top, 0px) + 76px) !important;
	}
	.settings-detail-body {
		padding-top: 8px !important;
	}
}

.short-settings-container {
	max-width: 540px !important;
	margin: 0 auto !important;
	box-sizing: border-box !important;
	position: relative !important;
	padding-top: calc(env(safe-area-inset-top, 0px) + 74px) !important;
}

/* View Pane Switching */
.settings-view-pane {
	display: none;
	animation: settingsFadeIn 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}

.settings-view-pane.is-active {
	display: block;
}

@keyframes settingsFadeIn {
	from {
		opacity: 0;
		transform: translateY(6px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

/* Fixed Top Navbar (< Settings) - ZERO BORDER */
.settings-top-navbar {
	position: fixed !important;
	top: 0 !important;
	left: 0 !important;
	right: 0 !important;
	z-index: 100 !important;
	height: 58px !important;
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
	margin-bottom: 0 !important;
	box-sizing: content-box !important;
	max-width: 540px !important;
	margin-left: auto !important;
	margin-right: auto !important;
}

.settings-inline-notif-badge,
#settings-hub-notif-unread-count {
	position: static !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	background: var(--theme-accent, #ff2d55) !important;
	background-color: var(--theme-accent, #ff2d55) !important;
	color: #ffffff !important;
	font-size: 10px !important;
	font-weight: 800 !important;
	padding: 1.5px 6.5px !important;
	border-radius: 999px !important;
	line-height: 1 !important;
	min-width: 16px !important;
	height: 16px !important;
	box-sizing: border-box !important;
	vertical-align: middle !important;
	top: auto !important;
	right: auto !important;
	bottom: auto !important;
	left: auto !important;
	margin: 0 0 0 6px !important;
}

.settings-nav-back {
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
	transition: transform 0.15s ease !important;
}

.settings-nav-back:hover {
	transform: translateX(-2px) !important;
}

.settings-nav-title {
	font-size: 19px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 !important;
	letter-spacing: -0.3px !important;
	text-align: center !important;
	flex: 1 !important;
}

.settings-nav-search-btn {
	width: 38px !important;
	height: 38px !important;
	border-radius: 50% !important;
	background: transparent !important;
	border: none !important;
	color: #ffffff !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	padding: 0 !important;
	transition: color 0.15s ease, background 0.15s ease !important;
}

.settings-nav-search-btn:hover {
	color: #ff5e00 !important;
	background: rgba(255, 255, 255, 0.06) !important;
}

/* Search Bar */
.settings-search-bar-wrap {
	margin-bottom: 26px !important;
}

.settings-search-box {
	display: flex !important;
	align-items: center !important;
	background: rgba(255, 255, 255, 0.05) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 14px !important;
	padding: 0 14px !important;
	height: 48px !important;
	box-sizing: border-box !important;
	transition: border-color 0.2s ease, background 0.2s ease !important;
}

.settings-search-box:focus-within {
	border-color: rgba(255, 255, 255, 0.25) !important;
	background: rgba(255, 255, 255, 0.08) !important;
}

.settings-search-box .search-icon {
	color: #64748b !important;
	margin-right: 10px !important;
	flex-shrink: 0 !important;
}

.settings-search-box input {
	background: transparent !important;
	border: none !important;
	outline: none !important;
	color: #ffffff !important;
	font-size: 14.5px !important;
	font-weight: 500 !important;
	width: 100% !important;
}

.settings-search-box input::placeholder {
	color: #64748b !important;
}

.search-clear-btn {
	background: transparent !important;
	border: none !important;
	color: #94a3b8 !important;
	font-size: 18px !important;
	cursor: pointer !important;
	padding: 0 4px !important;
	line-height: 1 !important;
}

/* Group Headers */
.settings-group-block {
	margin-bottom: 28px !important;
}

.settings-group-header {
	margin-bottom: 12px !important;
	padding: 0 4px !important;
}

.settings-group-title {
	font-size: 18px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 0 3px 0 !important;
	letter-spacing: -0.2px !important;
}

.settings-group-sub {
	font-size: 12.5px !important;
	color: #64748b !important;
	margin: 0 !important;
	line-height: 1.35 !important;
}

/* Menu Card Box */
.settings-menu-card {
	background: rgba(255, 255, 255, 0.035) !important;
	border-radius: 18px !important;
	overflow: hidden !important;
	border: 1px solid rgba(255, 255, 255, 0.04) !important;
}

.settings-menu-item {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	padding: 16px 18px !important;
	cursor: pointer !important;
	transition: background 0.15s ease !important;
	border-bottom: 1px solid rgba(255, 255, 255, 0.035) !important;
}

.settings-menu-item:last-child {
	border-bottom: none !important;
}

.settings-menu-item:hover {
	background: rgba(255, 255, 255, 0.065) !important;
}

.settings-menu-item:active {
	background: rgba(255, 255, 255, 0.09) !important;
}

.menu-item-left {
	display: flex !important;
	align-items: center !important;
	gap: 14px !important;
	flex: 1 !important;
	min-width: 0 !important;
}

.menu-item-icon {
	width: 38px !important;
	height: 38px !important;
	border-radius: 10px !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

.icon-account { background: rgba(59, 130, 246, 0.15) !important; color: #60a5fa !important; }
.icon-theme { background: rgba(244, 63, 94, 0.15) !important; color: #fb7185 !important; }
.icon-apps { background: rgba(168, 85, 247, 0.15) !important; color: #c084fc !important; }
.icon-billing { background: rgba(245, 158, 11, 0.15) !important; color: #fbbf24 !important; }
.icon-wallet { background: rgba(234, 179, 8, 0.15) !important; color: #fbbf24 !important; }
.icon-security { background: rgba(34, 197, 94, 0.15) !important; color: #4ade80 !important; }
.icon-storage { background: rgba(148, 163, 184, 0.15) !important; color: #cbd5e1 !important; }

.menu-item-text {
	flex: 1 !important;
	min-width: 0 !important;
}

.menu-item-title {
	font-size: 15px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
	margin-bottom: 2px !important;
	white-space: nowrap !important;
	overflow: hidden !important;
	text-overflow: ellipsis !important;
}

.menu-item-desc {
	font-size: 12px !important;
	color: #64748b !important;
	white-space: nowrap !important;
	overflow: hidden !important;
	text-overflow: ellipsis !important;
}

.menu-item-right {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	flex-shrink: 0 !important;
	padding-left: 10px !important;
}

.chevron-right {
	color: #475569 !important;
}

.menu-item-pill {
	font-size: 11px !important;
	font-weight: 800 !important;
	padding: 3px 9px !important;
	border-radius: 999px !important;
	text-transform: uppercase !important;
	letter-spacing: 0.3px !important;
}

.is-active-pill {
	background: rgba(34, 197, 94, 0.15) !important;
	color: #4ade80 !important;
}

.is-vip-pill {
	background: rgba(245, 158, 11, 0.15) !important;
	color: #fbbf24 !important;
}

.is-coins-pill {
	background: rgba(234, 179, 8, 0.15) !important;
	color: #fbbf24 !important;
}

.status-indicator-dot {
	width: 8px !important;
	height: 8px !important;
	border-radius: 50% !important;
	background: #64748b !important;
	display: inline-block !important;
}

.status-indicator-dot.is-connected {
	background: #22c55e !important;
	box-shadow: 0 0 8px rgba(34, 197, 94, 0.6) !important;
}

.hub-active-color-dot {
	width: 14px !important;
	height: 14px !important;
	border-radius: 50% !important;
	background: var(--theme-accent, #E50914) !important;
	box-shadow: 0 0 8px var(--theme-accent, #E50914) !important;
	display: inline-block !important;
}

/* Wallet Detail Styles */
.wallet-balance-hero-card {
	background: linear-gradient(135deg, rgba(234, 179, 8, 0.12) 0%, rgba(245, 158, 11, 0.04) 100%) !important;
	border: 1px solid rgba(234, 179, 8, 0.25) !important;
	border-radius: 20px !important;
	padding: 22px 20px !important;
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	margin-bottom: 8px !important;
}

.wallet-hero-left {
	display: flex !important;
	flex-direction: column !important;
	gap: 6px !important;
}

.wallet-hero-label {
	font-size: 12px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	text-transform: uppercase !important;
	letter-spacing: 0.5px !important;
}

.wallet-hero-amount {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	font-size: 28px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
}

.wallet-unit-text {
	font-size: 14px !important;
	font-weight: 700 !important;
	color: #fbbf24 !important;
}

.btn-wallet-hero-topup {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
	color: #000000 !important;
	font-size: 13px !important;
	font-weight: 800 !important;
	padding: 8px 18px !important;
	border-radius: 999px !important;
	text-decoration: none !important;
	display: inline-flex !important;
	align-items: center !important;
	box-shadow: 0 4px 14px rgba(245, 158, 11, 0.3) !important;
	transition: transform 0.15s ease !important;
}

.btn-wallet-hero-topup:hover {
	transform: scale(1.04) !important;
}

/* Coin Top-Up Grid & Cards */
.wallet-coin-packs-grid {
	display: grid !important;
	grid-template-columns: repeat(4, 1fr) !important;
	gap: 14px !important;
	margin-bottom: 24px !important;
	width: 100% !important;
	box-sizing: border-box !important;
}

@media (max-width: 900px) {
	.wallet-coin-packs-grid {
		grid-template-columns: repeat(2, 1fr) !important;
	}
}

@media (max-width: 480px) {
	.wallet-coin-packs-grid {
		grid-template-columns: 1fr !important;
	}
}

.coin-pack-tile {
	background: rgba(255, 255, 255, 0.035) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 16px !important;
	padding: 22px 16px 18px !important;
	text-align: center !important;
	display: flex !important;
	flex-direction: column !important;
	align-items: center !important;
	justify-content: space-between !important;
	position: relative !important;
	overflow: hidden !important;
	transition: all 0.2s ease !important;
	gap: 10px !important;
	box-sizing: border-box !important;
}

.coin-pack-tile:hover {
	background: rgba(255, 255, 255, 0.06) !important;
	border-color: rgba(251, 191, 36, 0.45) !important;
	transform: translateY(-3px) !important;
	box-shadow: 0 10px 28px rgba(0, 0, 0, 0.45) !important;
}

.coin-pack-tile.is-popular {
	background: linear-gradient(180deg, rgba(234, 179, 8, 0.09) 0%, rgba(255, 255, 255, 0.035) 100%) !important;
	border-color: rgba(245, 158, 11, 0.45) !important;
	box-shadow: 0 4px 20px rgba(245, 158, 11, 0.12) !important;
}

.pack-popular-ribbon {
	position: absolute !important;
	top: 0 !important;
	right: 0 !important;
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
	color: #000000 !important;
	font-size: 9.5px !important;
	font-weight: 900 !important;
	letter-spacing: 0.6px !important;
	padding: 3px 10px !important;
	border-bottom-left-radius: 8px !important;
	text-transform: uppercase !important;
}

.pack-coin-amount {
	font-size: 20px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	margin-top: 4px !important;
	letter-spacing: -0.2px !important;
}

.pack-coin-amount .unit {
	font-size: 13px !important;
	font-weight: 700 !important;
	color: #fbbf24 !important;
}

.pack-bonus-tag {
	font-size: 11px !important;
	font-weight: 800 !important;
	color: #94a3b8 !important;
	background: rgba(255, 255, 255, 0.05) !important;
	padding: 3px 10px !important;
	border-radius: 999px !important;
}

.pack-bonus-tag.font-amber {
	color: #fbbf24 !important;
	background: rgba(245, 158, 11, 0.15) !important;
	border: 1px solid rgba(245, 158, 11, 0.3) !important;
}

.pack-bonus-tag.font-green {
	color: #4ade80 !important;
	background: rgba(34, 197, 94, 0.15) !important;
	border: 1px solid rgba(34, 197, 94, 0.3) !important;
}

.pack-price {
	font-size: 18px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	margin: 2px 0 !important;
}

.btn-pack-buy {
	width: 100% !important;
	padding: 10px 14px !important;
	border-radius: 10px !important;
	font-size: 12.5px !important;
	font-weight: 800 !important;
	text-decoration: none !important;
	display: inline-block !important;
	background: rgba(255, 255, 255, 0.08) !important;
	color: #ffffff !important;
	border: 1px solid rgba(255, 255, 255, 0.12) !important;
	transition: all 0.15s ease !important;
	box-sizing: border-box !important;
}

.btn-pack-buy:hover {
	background: rgba(255, 255, 255, 0.16) !important;
	color: #ffffff !important;
	border-color: rgba(255, 255, 255, 0.25) !important;
}

.btn-pack-buy.is-primary {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
	color: #000000 !important;
	border: none !important;
	box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35) !important;
}

.btn-pack-buy.is-primary:hover {
	transform: scale(1.02) !important;
	box-shadow: 0 6px 18px rgba(245, 158, 11, 0.45) !important;
}

/* Hub Footer */
.settings-hub-footer {
	text-align: center !important;
	margin-top: 36px !important;
	padding-bottom: 20px !important;
}

.btn-settings-signout-hub {
	background: rgba(239, 68, 68, 0.1) !important;
	color: #f87171 !important;
	border: 1px solid rgba(239, 68, 68, 0.25) !important;
	font-size: 13.5px !important;
	font-weight: 700 !important;
	padding: 10px 24px !important;
	border-radius: 999px !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 8px !important;
	cursor: pointer !important;
	transition: all 0.15s ease !important;
}

.btn-settings-signout-hub:hover {
	background: rgba(239, 68, 68, 0.2) !important;
	color: #ff8585 !important;
}

.btn-settings-signin-hub {
	background: #ffffff !important;
	color: #0f172a !important;
	border: none !important;
	font-size: 13.5px !important;
	font-weight: 800 !important;
	padding: 11px 26px !important;
	border-radius: 999px !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 9px !important;
	cursor: pointer !important;
	box-shadow: 0 4px 16px rgba(255, 255, 255, 0.15) !important;
	transition: all 0.15s ease !important;
}

.btn-settings-signin-hub:hover {
	transform: scale(1.03) !important;
	box-shadow: 0 6px 20px rgba(255, 255, 255, 0.25) !important;
}

.settings-version-note {
	font-size: 11.5px !important;
	color: #475569 !important;
	margin-top: 14px !important;
}

/* ─────────────────────────────────────────────────────────────
   DETAIL VIEW STYLES
   ───────────────────────────────────────────────────────────── */
.settings-detail-body {
	padding-bottom: 20px !important;
}

.settings-section-intro {
	margin-bottom: 12px !important;
	padding: 0 2px !important;
}

.settings-section-intro h4 {
	font-size: 15.5px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 0 3px 0 !important;
}

.settings-section-intro p {
	font-size: 12.5px !important;
	color: #64748b !important;
	margin: 0 !important;
	line-height: 1.35 !important;
}

.settings-detail-card {
	background: rgba(255, 255, 255, 0.035) !important;
	border-radius: 16px !important;
	padding: 16px !important;
	border: 1px solid rgba(255, 255, 255, 0.04) !important;
	margin-bottom: 14px !important;
}

/* Avatar Hero in Account Info */
.detail-avatar-hero {
	text-align: center !important;
	margin-bottom: 24px !important;
}

.detail-profile-identity {
	display: flex !important;
	flex-direction: column !important;
	align-items: center !important;
}

.detail-name-badge-row {
	display: flex !important;
	flex-direction: column !important;
	align-items: center !important;
	gap: 4px !important;
}

.detail-avatar-wrap {
	position: relative !important;
	width: 80px !important;
	height: 80px !important;
	margin: 0 auto 12px auto !important;
}

.detail-avatar-wrap img {
	width: 100% !important;
	height: 100% !important;
	border-radius: 50% !important;
	object-fit: cover !important;
	border: 2px solid rgba(255, 255, 255, 0.15) !important;
	background: #181a20 !important;
}

.detail-change-photo-btn {
	position: absolute !important;
	bottom: 0 !important;
	right: 0 !important;
	width: 28px !important;
	height: 28px !important;
	border-radius: 50% !important;
	background: #ff4500 !important;
	color: #ffffff !important;
	border: 2px solid #121212 !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
}

.detail-user-name {
	font-size: 18px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 0 4px 0 !important;
}

.detail-user-badge {
	font-size: 11px !important;
	font-weight: 800 !important;
	color: #4ade80 !important;
	background: rgba(34, 197, 94, 0.15) !important;
	padding: 2px 10px !important;
	border-radius: 999px !important;
	display: inline-block !important;
}

.detail-uid-row {
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 8px !important;
	margin-top: 8px !important;
}

.detail-uid-badge {
	font-size: 11px !important;
	color: #64748b !important;
	background: rgba(255, 255, 255, 0.05) !important;
	padding: 3px 10px !important;
	border-radius: 6px !important;
	font-family: monospace !important;
}

.btn-detail-copy-uid {
	background: transparent !important;
	border: none !important;
	color: #ff5e00 !important;
	font-size: 11px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	padding: 0 !important;
}

.detail-stats-strip {
	display: grid !important;
	grid-template-columns: repeat(4, 1fr) !important;
	gap: 8px !important;
	background: rgba(255, 255, 255, 0.035) !important;
	border: 1px solid rgba(255, 255, 255, 0.05) !important;
	border-radius: 16px !important;
	padding: 14px 8px !important;
	margin-top: 16px !important;
}

.detail-stat-col {
	text-align: center !important;
}

.detail-stat-val {
	font-size: 18px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	line-height: 1.2 !important;
}

.detail-stat-lbl {
	font-size: 10px !important;
	font-weight: 700 !important;
	color: #64748b !important;
	text-transform: uppercase !important;
	letter-spacing: 0.5px !important;
	margin-top: 2px !important;
}

/* Form Groups */
.detail-form-group {
	margin-bottom: 14px !important;
}

.detail-form-group:last-child {
	margin-bottom: 0 !important;
}

.detail-label {
	display: block !important;
	font-size: 12px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	margin-bottom: 6px !important;
	text-transform: uppercase !important;
	letter-spacing: 0.3px !important;
}

.detail-input {
	width: 100% !important;
	background: rgba(255, 255, 255, 0.05) !important;
	border: 1px solid rgba(255, 255, 255, 0.1) !important;
	border-radius: 12px !important;
	padding: 12px 14px !important;
	color: #ffffff !important;
	font-size: 14px !important;
	font-weight: 600 !important;
	box-sizing: border-box !important;
	outline: none !important;
}

.detail-input:focus {
	border-color: rgba(255, 255, 255, 0.3) !important;
	background: rgba(255, 255, 255, 0.08) !important;
}

.detail-input.is-readonly {
	color: #64748b !important;
	background: rgba(255, 255, 255, 0.02) !important;
	border-color: transparent !important;
}

/* Primary Action Button */
.btn-settings-primary-action {
	width: 100% !important;
	height: 50px !important;
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	font-size: 14.5px !important;
	font-weight: 800 !important;
	border: none !important;
	outline: none !important;
	border-radius: 999px !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	text-decoration: none !important;
	box-shadow: 0 6px 20px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease, filter 0.15s ease !important;
	box-sizing: border-box !important;
}

.btn-settings-primary-action:hover {
	filter: brightness(1.1) !important;
	transform: translateY(-1px) !important;
}

/* Membership Plan Actions Row */
.membership-plan-actions-row {
	display: flex !important;
	flex-direction: row !important;
	align-items: center !important;
	justify-content: flex-start !important;
	flex-wrap: wrap !important;
	gap: 12px !important;
	margin-top: 22px !important;
	width: auto !important;
}

.membership-plan-actions-row .btn-plan-upgrade-action {
	flex: 0 0 auto !important;
	width: auto !important;
	min-width: 150px !important;
	height: 44px !important;
	font-size: 13.5px !important;
	padding: 0 28px !important;
	white-space: nowrap !important;
	text-align: center !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	border-radius: 999px !important;
}

.membership-plan-actions-row .btn-plan-cancel-action {
	flex: 0 0 auto !important;
	width: auto !important;
	min-width: 130px !important;
	height: 44px !important;
	padding: 0 22px !important;
	font-size: 13px !important;
	font-weight: 700 !important;
	white-space: nowrap !important;
	border-radius: 999px !important;
	color: #ef4444 !important;
	border: 1px solid rgba(239, 68, 68, 0.35) !important;
	background: rgba(239, 68, 68, 0.08) !important;
	display: none !important;
	align-items: center !important;
	justify-content: center !important;
	box-sizing: border-box !important;
	cursor: pointer !important;
}

.membership-plan-actions-row .btn-plan-cancel-action.is-visible {
	display: inline-flex !important;
}

.membership-plan-actions-row .btn-plan-cancel-action:hover {
	background: rgba(239, 68, 68, 0.16) !important;
	border-color: rgba(239, 68, 68, 0.5) !important;
}

.btn-settings-signout-full {
	width: 100% !important;
	height: 50px !important;
	background: rgba(239, 68, 68, 0.12) !important;
	color: #f87171 !important;
	border: 1px solid rgba(239, 68, 68, 0.3) !important;
	font-size: 14.5px !important;
	font-weight: 800 !important;
	border-radius: 999px !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 8px !important;
	cursor: pointer !important;
	transition: all 0.15s ease !important;
}

.btn-settings-signout-full:hover {
	background: rgba(239, 68, 68, 0.22) !important;
	color: #ff8585 !important;
}

/* Social & Apps Rows */
.social-conn-row {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	padding: 14px 16px !important;
	border-bottom: 1px solid rgba(255, 255, 255, 0.035) !important;
}

.social-conn-row:last-child {
	border-bottom: none !important;
}

.social-conn-left {
	display: flex !important;
	align-items: center !important;
	gap: 12px !important;
	flex: 1 !important;
	min-width: 0 !important;
}

.social-logo-circle {
	width: 38px !important;
	height: 38px !important;
	border-radius: 50% !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

.social-logo-circle.bg-white { background: #ffffff !important; }
.social-logo-circle.bg-orange { background: rgba(255, 152, 0, 0.15) !important; }
.social-logo-circle.bg-dark { background: rgba(255, 255, 255, 0.08) !important; }

.social-name {
	margin: 0 0 2px 0 !important;
	font-size: 14.5px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
}

.social-status-sub {
	font-size: 12px !important;
	color: #64748b !important;
}

.btn-conn-toggle {
	background: rgba(255, 255, 255, 0.06) !important;
	border: 1px solid rgba(255, 255, 255, 0.12) !important;
	color: #e2e8f0 !important;
	font-size: 12.5px !important;
	font-weight: 700 !important;
	padding: 6px 14px !important;
	border-radius: 999px !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 6px !important;
	cursor: pointer !important;
	text-decoration: none !important;
	transition: all 0.15s ease !important;
}

.btn-conn-toggle.is-connected {
	background: rgba(34, 197, 94, 0.12) !important;
	border-color: rgba(34, 197, 94, 0.3) !important;
	color: #4ade80 !important;
}

.btn-conn-toggle.is-action {
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	border: none !important;
	color: #ffffff !important;
}

/* VIP Billing Card */
.billing-plan-card {
	background: linear-gradient(135deg, rgba(255, 69, 0, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%) !important;
	border: 1px solid rgba(255, 69, 0, 0.25) !important;
}

.billing-card-header {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	margin-bottom: 16px !important;
	padding-bottom: 14px !important;
	border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.billing-plan-tag {
	font-size: 10.5px !important;
	font-weight: 800 !important;
	padding: 3px 8px !important;
	border-radius: 6px !important;
	background: #ff4500 !important;
	color: #ffffff !important;
	text-transform: uppercase !important;
	letter-spacing: 0.5px !important;
	display: inline-block !important;
	margin-bottom: 4px !important;
}

.billing-plan-name {
	font-size: 20px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	margin: 0 !important;
}

.billing-price-val {
	font-size: 22px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
}

.billing-price-period {
	font-size: 11px !important;
	color: #94a3b8 !important;
}

.billing-perks-list {
	display: flex !important;
	flex-direction: column !important;
	gap: 8px !important;
}

.billing-perk-item {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	font-size: 12.5px !important;
	color: #cbd5e1 !important;
}

/* Membership View: Coin Packages Grid (Base Desktop) */
.membership-coin-packages-grid {
	display: grid !important;
	grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
	gap: 16px !important;
	margin-bottom: 28px !important;
}

.membership-coin-card {
	background: rgba(255, 255, 255, 0.035) !important;
	border: 1px solid rgba(255, 255, 255, 0.07) !important;
	border-radius: 14px !important;
	padding: 18px 16px !important;
	display: flex !important;
	flex-direction: column !important;
	justify-content: space-between !important;
	position: relative !important;
	transition: transform 0.2s ease, border-color 0.2s ease !important;
}

.membership-coin-card:hover {
	transform: translateY(-2px) !important;
	border-color: rgba(245, 158, 11, 0.4) !important;
}

.membership-coin-card.is-popular {
	background: linear-gradient(180deg, rgba(245, 158, 11, 0.08) 0%, rgba(255, 255, 255, 0.035) 100%) !important;
	border: 1px solid rgba(245, 158, 11, 0.35) !important;
}

.membership-popular-badge {
	position: absolute !important;
	top: -10px !important;
	right: 14px !important;
	background: linear-gradient(135deg, #f59e0b, #d97706) !important;
	color: #ffffff !important;
	font-size: 9.5px !important;
	font-weight: 900 !important;
	padding: 2px 8px !important;
	border-radius: 999px !important;
	letter-spacing: 0.4px !important;
	box-shadow: 0 4px 10px rgba(217, 119, 6, 0.3) !important;
}

.membership-popular-badge.is-green {
	background: linear-gradient(135deg, #10b981, #059669) !important;
	box-shadow: 0 4px 10px rgba(5, 150, 105, 0.3) !important;
}

.membership-coin-header {
	display: flex !important;
	align-items: center !important;
	gap: 10px !important;
	margin-bottom: 8px !important;
}

.membership-coin-amount {
	font-size: 17px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
}

.membership-coin-sub {
	font-size: 11.5px !important;
	color: #94a3b8 !important;
	margin: 0 0 12px 0 !important;
	line-height: 1.3 !important;
}

.membership-coin-price-row {
	margin-bottom: 14px !important;
}

.membership-coin-price {
	font-size: 20px !important;
	font-weight: 900 !important;
	color: #f59e0b !important;
}

.btn-membership-coin-buy {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	width: 100% !important;
	height: 38px !important;
	border-radius: 999px !important;
	background: rgba(255, 255, 255, 0.08) !important;
	color: #ffffff !important;
	font-size: 12.5px !important;
	font-weight: 700 !important;
	text-decoration: none !important;
	transition: all 0.2s ease !important;
	box-sizing: border-box !important;
}

.btn-membership-coin-buy:hover {
	background: rgba(255, 255, 255, 0.14) !important;
	color: #ffffff !important;
}

.btn-membership-coin-buy.is-popular-btn {
	background: linear-gradient(135deg, #f59e0b, #d97706) !important;
	box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35) !important;
}

/* Membership View: VIP Plans Grid (Base Desktop) */
.membership-vip-plans-grid {
	display: grid !important;
	grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
	gap: 16px !important;
	margin-bottom: 24px !important;
}

.membership-vip-card {
	background: rgba(255, 255, 255, 0.035) !important;
	border: 1px solid rgba(255, 255, 255, 0.07) !important;
	border-radius: 14px !important;
	padding: 20px 18px !important;
	display: flex !important;
	flex-direction: column !important;
	justify-content: space-between !important;
	position: relative !important;
	transition: transform 0.2s ease, border-color 0.2s ease !important;
}

.membership-vip-card:hover {
	transform: translateY(-2px) !important;
	border-color: rgba(255, 45, 85, 0.4) !important;
}

.membership-vip-card.is-featured {
	background: linear-gradient(180deg, rgba(255, 45, 85, 0.08) 0%, rgba(255, 255, 255, 0.035) 100%) !important;
	border: 1px solid rgba(255, 45, 85, 0.35) !important;
}

.membership-vip-tier-tag {
	font-size: 10px !important;
	font-weight: 800 !important;
	padding: 2px 7px !important;
	border-radius: 5px !important;
	background: rgba(255, 255, 255, 0.08) !important;
	color: #94a3b8 !important;
	display: inline-block !important;
	margin-bottom: 6px !important;
	letter-spacing: 0.4px !important;
}

.membership-vip-tier-tag.is-accent {
	background: rgba(255, 45, 85, 0.18) !important;
	color: #ff2d55 !important;
}

.membership-vip-title {
	font-size: 18px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 0 4px 0 !important;
}

.membership-vip-desc {
	font-size: 11.5px !important;
	color: #94a3b8 !important;
	margin: 0 0 12px 0 !important;
}

.membership-vip-price {
	display: flex !important;
	align-items: baseline !important;
	gap: 2px !important;
	margin-bottom: 14px !important;
}

.membership-vip-price .vip-currency {
	font-size: 15px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
}

.membership-vip-price .vip-val {
	font-size: 26px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	line-height: 1 !important;
}

.membership-vip-price .vip-period {
	font-size: 11.5px !important;
	font-weight: 600 !important;
	color: #94a3b8 !important;
	margin-left: 2px !important;
}

.membership-vip-perks {
	list-style: none !important;
	padding: 0 !important;
	margin: 0 0 16px 0 !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 8px !important;
}

.membership-vip-perks li {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	font-size: 11.5px !important;
	color: #cbd5e1 !important;
	line-height: 1.3 !important;
}

.btn-membership-vip-subscribe {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	width: 100% !important;
	height: 38px !important;
	border-radius: 999px !important;
	background: rgba(255, 255, 255, 0.08) !important;
	color: #ffffff !important;
	font-size: 12.5px !important;
	font-weight: 700 !important;
	text-decoration: none !important;
	transition: all 0.2s ease !important;
	box-sizing: border-box !important;
}

.btn-membership-vip-subscribe:hover {
	background: rgba(255, 255, 255, 0.14) !important;
	color: #ffffff !important;
}

.btn-membership-vip-subscribe.is-featured-btn {
	background: linear-gradient(135deg, #ff2d55, #d97706) !important;
	box-shadow: 0 4px 14px rgba(255, 45, 85, 0.35) !important;
}

/* Membership View: Mobile Horizontal Scroll Carousel (Proper cascade override) */
@media (max-width: 768px) {
	.membership-coin-packages-grid,
	.membership-vip-plans-grid {
		display: flex !important;
		flex-direction: row !important;
		flex-wrap: nowrap !important;
		overflow-x: auto !important;
		overflow-y: visible !important;
		-webkit-overflow-scrolling: touch !important;
		scroll-snap-type: x mandatory !important;
		gap: 14px !important;
		padding: 10px 16px 20px 0 !important;
		padding-left: 0 !important;
		margin-left: 0 !important;
		margin-right: 0 !important;
		margin-bottom: 24px !important;
		scrollbar-width: none !important;
		width: 100% !important;
		box-sizing: border-box !important;
	}

	.membership-coin-packages-grid::-webkit-scrollbar,
	.membership-vip-plans-grid::-webkit-scrollbar {
		display: none !important;
	}

	.membership-coin-card,
	.membership-vip-card {
		flex: 0 0 270px !important;
		min-width: 270px !important;
		max-width: 270px !important;
		width: 270px !important;
		scroll-snap-align: start !important;
		box-sizing: border-box !important;
	}
}

/* Theme Swatches in Detail View */
.theme-palette-grid {
	display: grid !important;
	grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
	gap: 8px !important;
	margin-bottom: 16px !important;
}

.color-swatch-btn {
	display: flex !important;
	align-items: center !important;
	gap: 10px !important;
	background: rgba(255, 255, 255, 0.04) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 12px !important;
	padding: 10px 12px !important;
	cursor: pointer !important;
	transition: all 0.15s ease !important;
}

.color-swatch-btn:hover {
	background: rgba(255, 255, 255, 0.08) !important;
}

.color-swatch-btn.is-active {
	border-color: var(--theme-accent, #E50914) !important;
	background: rgba(var(--theme-accent-rgb, 229, 9, 20), 0.15) !important;
}

.swatch-color {
	width: 18px !important;
	height: 18px !important;
	border-radius: 50% !important;
	flex-shrink: 0 !important;
}

.swatch-label {
	font-size: 13px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
	flex: 1 !important;
	text-align: left !important;
}

.swatch-check {
	color: var(--theme-accent, #E50914) !important;
	opacity: 0 !important;
}

.color-swatch-btn.is-active .swatch-check {
	opacity: 1 !important;
}

.custom-color-row {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	flex-wrap: wrap !important;
	gap: 12px !important;
	background: rgba(255, 255, 255, 0.03) !important;
	padding: 12px 14px !important;
	border-radius: 14px !important;
	margin-bottom: 14px !important;
}

.picker-label {
	font-size: 11.5px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	text-transform: uppercase !important;
	display: block !important;
	margin-bottom: 4px !important;
}

.picker-input-wrapper {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
}

.native-color-picker {
	width: 32px !important;
	height: 32px !important;
	border-radius: 8px !important;
	border: none !important;
	padding: 0 !important;
	background: transparent !important;
	cursor: pointer !important;
}

.hex-text-input {
	padding: 4px 8px !important;
	height: 32px !important;
	width: 86px !important;
	background: rgba(255, 255, 255, 0.06) !important;
	border: 1px solid rgba(255, 255, 255, 0.12) !important;
	border-radius: 8px !important;
	color: #ffffff !important;
	font-size: 13px !important;
	font-weight: 700 !important;
}

.theme-preview-box {
	min-width: 140px !important;
}

.preview-label {
	font-size: 11px !important;
	font-weight: 700 !important;
	color: #64748b !important;
	display: block !important;
	margin-bottom: 4px !important;
}

.mini-player-preview {
	background: #000000 !important;
	padding: 6px 10px !important;
	border-radius: 8px !important;
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
}

.mini-player-bar {
	position: relative !important;
	flex: 1 !important;
	height: 4px !important;
	background: rgba(255, 255, 255, 0.2) !important;
	border-radius: 4px !important;
}

.mini-progress-fill {
	position: absolute !important;
	left: 0 !important;
	top: 0 !important;
	height: 100% !important;
	width: 60% !important;
	background: var(--theme-accent, #E50914) !important;
	border-radius: 4px !important;
}

.mini-progress-knob {
	position: absolute !important;
	left: 60% !important;
	top: -3px !important;
	width: 10px !important;
	height: 10px !important;
	border-radius: 50% !important;
	background: #ffffff !important;
	box-shadow: 0 0 6px var(--theme-accent, #E50914) !important;
}

.mini-player-controls {
	display: flex !important;
	align-items: center !important;
	gap: 4px !important;
}

.mini-badge-preview {
	font-size: 9px !important;
	font-weight: 800 !important;
	padding: 1px 4px !important;
	border-radius: 3px !important;
	background: rgba(229, 9, 20, 0.2) !important;
	color: var(--theme-accent, #E50914) !important;
}

.mini-button-preview {
	font-size: 9px !important;
	font-weight: 800 !important;
	padding: 1px 4px !important;
	border-radius: 3px !important;
	background: var(--theme-accent, #E50914) !important;
	color: #ffffff !important;
}

.theme-actions-row {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
}

.btn-account-ghost {
	background: transparent !important;
	border: 1px solid rgba(255, 255, 255, 0.12) !important;
	color: #94a3b8 !important;
	font-size: 12px !important;
	font-weight: 600 !important;
	padding: 6px 12px !important;
	border-radius: 8px !important;
	cursor: pointer !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 6px !important;
}

.theme-saved-toast {
	font-size: 12px !important;
	color: #4ade80 !important;
	opacity: 0 !important;
	transition: opacity 0.2s ease !important;
}

.theme-saved-toast.show {
	opacity: 1 !important;
}

/* ─────────────────────────────────────────────────────────────
   DISPLAY MODE, TOGGLES & LIVE SHOWCASE STYLES
   ───────────────────────────────────────────────────────────── */
.display-mode-cards-grid {
	display: grid !important;
	grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
	gap: 14px !important;
	width: 100% !important;
	margin-bottom: 24px !important;
	box-sizing: border-box !important;
}

@media (max-width: 680px) {
	.display-mode-cards-grid {
		grid-template-columns: 1fr !important;
	}
}

.display-mode-card {
	background: rgba(255, 255, 255, 0.025) !important;
	border: 1.5px solid rgba(255, 255, 255, 0.06) !important;
	border-radius: 14px !important;
	padding: 14px 16px !important;
	cursor: pointer !important;
	transition: all 0.2s ease !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 12px !important;
	box-sizing: border-box !important;
}

.display-mode-card:hover {
	background: rgba(255, 255, 255, 0.05) !important;
	border-color: rgba(255, 255, 255, 0.15) !important;
	transform: translateY(-2px) !important;
}

.display-mode-card.is-active {
	border-color: var(--theme-accent, #E50914) !important;
	background: rgba(var(--theme-accent-rgb, 229, 9, 20), 0.08) !important;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
}

.mode-card-preview {
	height: 52px !important;
	border-radius: 8px !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	position: relative !important;
	overflow: hidden !important;
	padding: 8px 10px !important;
	box-sizing: border-box !important;
}

.bg-cinematic-dark {
	background: #121212 !important;
}

.bg-oled-black {
	background: #000000 !important;
}

.bg-midnight-navy {
	background: #0b101b !important;
}

.mode-preview-bar {
	width: 60% !important;
	height: 6px !important;
	background: rgba(255, 255, 255, 0.12) !important;
	border-radius: 3px !important;
	margin-bottom: 6px !important;
}

.mode-preview-dot {
	width: 14px !important;
	height: 14px !important;
	border-radius: 50% !important;
	background: var(--theme-accent, #E50914) !important;
	box-shadow: 0 0 8px var(--theme-accent-glow, rgba(229, 9, 20, 0.4)) !important;
}

.mode-card-info {
	display: flex !important;
	flex-direction: column !important;
	gap: 3px !important;
}

.mode-card-title-row {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
}

.mode-card-name {
	font-size: 14px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 !important;
}

.mode-check-badge {
	width: 18px !important;
	height: 18px !important;
	border-radius: 50% !important;
	background: var(--theme-accent, #E50914) !important;
	color: #ffffff !important;
	font-size: 10px !important;
	font-weight: 900 !important;
	display: none !important;
	align-items: center !important;
	justify-content: center !important;
}

.display-mode-card.is-active .mode-check-badge {
	display: inline-flex !important;
}

.mode-card-sub {
	font-size: 11.5px !important;
	color: #64748b !important;
	margin: 0 !important;
	line-height: 1.3 !important;
}

/* Modern iOS Style Toggle Switch */
.theme-toggle-switch {
	position: relative !important;
	display: inline-block !important;
	width: 44px !important;
	height: 24px !important;
	margin: 0 !important;
	cursor: pointer !important;
}

.theme-toggle-switch input {
	opacity: 0 !important;
	width: 0 !important;
	height: 0 !important;
	margin: 0 !important;
}

.theme-toggle-slider {
	position: absolute !important;
	top: 0 !important;
	left: 0 !important;
	right: 0 !important;
	bottom: 0 !important;
	background-color: rgba(255, 255, 255, 0.12) !important;
	transition: 0.25s ease !important;
	border-radius: 24px !important;
}

.theme-toggle-slider:before {
	position: absolute !important;
	content: "" !important;
	height: 18px !important;
	width: 18px !important;
	left: 3px !important;
	bottom: 3px !important;
	background-color: #ffffff !important;
	transition: 0.25s ease !important;
	border-radius: 50% !important;
	box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3) !important;
}

.theme-toggle-switch input:checked + .theme-toggle-slider {
	background-color: var(--theme-accent, #E50914) !important;
}

.theme-toggle-switch input:checked + .theme-toggle-slider:before {
	transform: translateX(20px) !important;
}

/* Live Component Showcase Box */
.theme-live-showcase-box {
	background: rgba(255, 255, 255, 0.025) !important;
	border: 1px solid rgba(255, 255, 255, 0.07) !important;
	border-radius: 16px !important;
	padding: 20px 24px !important;
	margin-bottom: 24px !important;
	width: 100% !important;
	box-sizing: border-box !important;
}

.showcase-card-inner {
	display: flex !important;
	align-items: center !important;
	gap: 24px !important;
	width: 100% !important;
}

@media (max-width: 680px) {
	.showcase-card-inner {
		flex-direction: column !important;
		align-items: flex-start !important;
	}
}

.showcase-visual-side {
	width: 130px !important;
	height: 180px !important;
	border-radius: 12px !important;
	background: linear-gradient(135deg, #1e222d 0%, #11141c 100%) !important;
	border: 1px solid rgba(255, 255, 255, 0.1) !important;
	position: relative !important;
	overflow: hidden !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

.showcase-tag-chip {
	position: absolute !important;
	top: 8px !important;
	left: 8px !important;
	font-size: 8.5px !important;
	font-weight: 900 !important;
	padding: 2px 6px !important;
	border-radius: 4px !important;
	letter-spacing: 0.5px !important;
}

.showcase-play-circle {
	width: 44px !important;
	height: 44px !important;
	border-radius: 50% !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
	transition: all 0.25s ease !important;
}

.showcase-play-circle:hover {
	transform: scale(1.1) !important;
}

.showcase-visual-side.has-ambient-glow {
	box-shadow: 0 0 35px var(--theme-accent-glow, rgba(229, 9, 20, 0.35)) !important;
}

.showcase-subtitle-caption {
	position: absolute !important;
	bottom: 12px !important;
	left: 8px !important;
	right: 8px !important;
	text-align: center !important;
	font-size: 9.5px !important;
	line-height: 1.3 !important;
	color: rgba(255, 255, 255, 0.95) !important;
	padding: 4px 6px !important;
	border-radius: 6px !important;
	background: transparent !important;
	text-shadow: 0 1px 3px rgba(0, 0, 0, 0.9), 0 0 2px #000000 !important;
	transition: all 0.25s ease !important;
	pointer-events: none !important;
}

.showcase-subtitle-caption.is-high-contrast {
	background: rgba(0, 0, 0, 0.82) !important;
	color: #ffffff !important;
	border: 1px solid rgba(255, 255, 255, 0.22) !important;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6) !important;
	font-weight: 700 !important;
	text-shadow: none !important;
}

.showcase-card-inner.hud-autohide-active .showcase-badges-row,
.showcase-card-inner.hud-autohide-active .showcase-scrubber-row,
.showcase-card-inner.hud-autohide-active .showcase-actions-row {
	transition: opacity 0.35s ease, filter 0.35s ease !important;
}

.showcase-details-side {
	flex: 1 !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 10px !important;
	width: 100% !important;
}

.showcase-badges-row {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	flex-wrap: wrap !important;
}

.badge-pill-hd {
	font-size: 10px !important;
	font-weight: 800 !important;
	padding: 2px 7px !important;
	border-radius: 5px !important;
	border: 1px solid currentColor !important;
	background: rgba(255, 255, 255, 0.04) !important;
}

.badge-pill-status {
	font-size: 10.5px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
}

.showcase-drama-title {
	font-size: 16.5px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 !important;
}

.showcase-drama-desc {
	font-size: 12.5px !important;
	color: #808d9e !important;
	margin: 0 !important;
	line-height: 1.4 !important;
}

.showcase-scrubber-row {
	display: flex !important;
	align-items: center !important;
	gap: 12px !important;
	margin: 4px 0 !important;
}

.showcase-scrubber-bar {
	flex: 1 !important;
	height: 5px !important;
	background: rgba(255, 255, 255, 0.1) !important;
	border-radius: 3px !important;
	position: relative !important;
}

.showcase-scrubber-fill {
	height: 100% !important;
	border-radius: 3px !important;
}

.showcase-scrubber-knob {
	position: absolute !important;
	top: -3.5px !important;
	width: 12px !important;
	height: 12px !important;
	border-radius: 50% !important;
	background: #ffffff !important;
	border: 2px solid;
	transform: translateX(-50%) !important;
}

.showcase-time-text {
	font-size: 11px !important;
	color: #64748b !important;
	font-family: monospace !important;
}

.showcase-actions-row {
	display: flex !important;
	align-items: center !important;
	gap: 12px !important;
	flex-wrap: wrap !important;
	margin-top: 4px !important;
}

.btn-showcase-primary {
	display: inline-flex !important;
	align-items: center !important;
	gap: 6px !important;
	padding: 8px 18px !important;
	border-radius: 8px !important;
	border: none !important;
	font-size: 12.5px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
	transition: all 0.2s ease !important;
}

.btn-showcase-ghost {
	display: inline-flex !important;
	align-items: center !important;
	gap: 6px !important;
	padding: 8px 16px !important;
	border-radius: 8px !important;
	background: rgba(255, 255, 255, 0.04) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	color: #cbd5e1 !important;
	font-size: 12.5px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	transition: all 0.2s ease !important;
}

.btn-showcase-ghost:hover {
	background: rgba(255, 255, 255, 0.08) !important;
	color: #ffffff !important;
}

/* ─────────────────────────────────────────────────────────────
   DESKTOP MASTER-DETAIL LAYOUT (Borderless / Frameless Clean UI)
   ───────────────────────────────────────────────────────────── */
@media (min-width: 992px) {
	.short-settings-page-wrapper {
		padding: 0 32px 0 28px !important;
		margin: 0 !important;
		margin-left: 0 !important;
		background: #121212 !important;
		background-color: #121212 !important;
		min-height: 100vh !important;
		box-sizing: border-box !important;
	}

	.short-settings-container {
		max-width: 100% !important;
		width: 100% !important;
		margin: 0 !important;
		margin-left: 0 !important;
		display: grid !important;
		grid-template-columns: 290px 1fr !important;
		gap: 36px !important;
		align-items: start !important;
		padding-top: 0 !important;
		padding-bottom: 0 !important;
		min-height: 100vh !important;
	}

	/* Left Sidebar (Hub) - Fixed / Sticky full-height navigation with independent vertical scroll */
	#settings-view-hub {
		display: flex !important;
		flex-direction: column !important;
		grid-column: 1 !important;
		grid-row: 1 !important;
		position: sticky !important;
		top: 0 !important;
		height: 100vh !important;
		max-height: 100vh !important;
		overflow-y: auto !important;
		overflow-x: hidden !important;
		overscroll-behavior: contain !important;
		scrollbar-width: none !important;
		-ms-overflow-style: none !important;
		background: transparent !important;
		border: none !important;
		border-radius: 0 !important;
		padding: 24px 0 24px 0 !important;
		margin: 0 !important;
		margin-left: 0 !important;
		box-shadow: none !important;
		box-sizing: border-box !important;
	}

	#settings-view-hub::-webkit-scrollbar {
		display: none !important;
		width: 0 !important;
		height: 0 !important;
	}

	#settings-view-hub::-webkit-scrollbar-thumb {
		display: none !important;
		background: transparent !important;
	}

	#settings-view-hub::-webkit-scrollbar-track {
		display: none !important;
		background: transparent !important;
	}

	#settings-view-hub .settings-top-navbar {
		position: static !important;
		max-width: 100% !important;
		height: auto !important;
		padding: 0 0 14px 0 !important;
		margin: 0 0 16px 0 !important;
		margin-left: 0 !important;
		margin-right: 0 !important;
		background: transparent !important;
		background-color: transparent !important;
		backdrop-filter: none !important;
		-webkit-backdrop-filter: none !important;
		border: none !important;
		border-bottom: none !important;
		box-shadow: none !important;
		display: flex !important;
		align-items: center !important;
		justify-content: flex-start !important;
		gap: 12px !important;
	}

	#settings-view-hub .settings-nav-back {
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		width: auto !important;
		height: auto !important;
		margin: 0 !important;
		margin-left: 0 !important;
		padding: 4px 6px 4px 0 !important;
		border-radius: 0 !important;
		border: none !important;
		box-shadow: none !important;
		color: #ffffff !important;
		background: transparent !important;
		background-color: transparent !important;
		text-decoration: none !important;
		transition: color 0.15s ease, transform 0.15s ease !important;
		flex-shrink: 0 !important;
	}

	#settings-view-hub .settings-nav-back:hover {
		background: transparent !important;
		background-color: transparent !important;
		color: var(--theme-accent, #ff2d55) !important;
		transform: translateX(-3px) !important;
	}

	#settings-view-hub .settings-nav-search-btn,
	#settings-view-hub .settings-search-bar-wrap,
	#btn-toggle-hub-search,
	#settings-search-bar-wrap {
		display: none !important;
	}

	#settings-view-hub .settings-nav-title {
		text-align: left !important;
		font-size: 24px !important;
		font-weight: 800 !important;
		letter-spacing: -0.4px !important;
		color: #ffffff !important;
		flex: unset !important;
		margin: 0 !important;
		padding: 0 !important;
	}

	.settings-group-block {
		margin-bottom: 24px !important;
	}

	.settings-group-title {
		font-size: 11px !important;
		font-weight: 800 !important;
		color: #64748b !important;
		text-transform: uppercase !important;
		letter-spacing: 0.8px !important;
		padding: 0 8px 6px 8px !important;
	}

	.settings-group-sub {
		display: none !important;
	}

	.settings-menu-card {
		background: transparent !important;
		border: none !important;
		border-radius: 0 !important;
		box-shadow: none !important;
	}

	.settings-menu-item {
		padding: 10px 14px !important;
		border-radius: 12px !important;
		border: 1px solid transparent !important;
		background: transparent !important;
		margin-bottom: 6px !important;
		transition: all 0.18s ease !important;
	}

	.settings-menu-item:hover {
		background: rgba(255, 255, 255, 0.04) !important;
	}

	/* Active sidebar selection highlight (Distinct from hover) */
	.settings-menu-item.is-selected-item {
		background: rgba(var(--theme-accent-rgb, 229, 9, 20), 0.16) !important;
		border: none !important;
		outline: none !important;
	}

	.settings-menu-item.is-selected-item .menu-item-title {
		color: #ffffff !important;
		font-weight: 800 !important;
	}

	.settings-menu-item.is-selected-item .menu-item-icon {
		background: var(--theme-accent, #E50914) !important;
		color: #ffffff !important;
		box-shadow: 0 4px 14px var(--theme-accent-glow, rgba(229, 9, 20, 0.45)) !important;
	}

	.settings-menu-item .chevron-right {
		display: none !important;
	}

	/* Right Content Panel (Detail views) - Seamless, cardless, borderless */
	.settings-view-pane:not(#settings-view-hub) {
		display: none;
		grid-column: 2 !important;
		grid-row: 1 !important;
		background: transparent !important;
		border: none !important;
		border-radius: 0 !important;
		padding: 32px 0 40px 0 !important;
		box-shadow: none !important;
		min-height: 100vh !important;
		box-sizing: border-box !important;
	}

	.settings-view-pane.is-active:not(#settings-view-hub) {
		display: block !important;
	}

	.settings-view-pane:not(#settings-view-hub) .settings-top-navbar {
		display: none !important;
	}

	.settings-view-pane:not(#settings-view-hub) .settings-detail-body {
		padding-top: 8px !important;
		padding-bottom: 10px !important;
	}

	/* Cardless inner components on desktop */
	.settings-detail-card {
		background: transparent !important;
		border: none !important;
		border-radius: 0 !important;
		padding: 0 !important;
		box-shadow: none !important;
		margin-top: 16px !important;
		margin-bottom: 28px !important;
	}

	/* Hide Global and Hub Footers on Settings Page */
	.site-footer,
	footer.site-footer,
	footer,
	.short-footer,
	.main-footer,
	.settings-hub-footer {
		display: none !important;
	}

	.settings-section-intro {
		margin-top: 32px !important;
		margin-bottom: 16px !important;
		padding: 0 !important;
	}

	.settings-detail-body > .settings-section-intro:first-of-type,
	.settings-detail-body > .settings-section-intro:first-child {
		margin-top: 8px !important;
	}

	.settings-section-intro h4 {
		font-size: 16px !important;
		font-weight: 700 !important;
		color: #ffffff !important;
		margin-bottom: 4px !important;
	}

	.settings-section-intro p {
		font-size: 13px !important;
		color: #808d9e !important;
		margin-bottom: 14px !important;
	}

	/* Connected Accounts & Social list items */
	.social-conn-row {
		background: rgba(255, 255, 255, 0.025) !important;
		border: 1px solid rgba(255, 255, 255, 0.06) !important;
		border-radius: 12px !important;
		padding: 14px 18px !important;
		margin-bottom: 12px !important;
		width: 100% !important;
		max-width: 100% !important;
		box-sizing: border-box !important;
	}

	.account-tx-section {
		margin-top: 36px !important;
		width: 100% !important;
		max-width: 100% !important;
		box-sizing: border-box !important;
	}

	/* Form Inputs: 3-Column Desktop Grid for compact single-screen fit */
	#settings-view-account-info .settings-detail-card {
		display: grid !important;
		grid-template-columns: repeat(3, 1fr) !important;
		gap: 16px !important;
		width: 100% !important;
		max-width: 100% !important;
		margin-top: 16px !important;
		margin-bottom: 18px !important;
		box-sizing: border-box !important;
	}

	.detail-form-group {
		margin-bottom: 0 !important;
		max-width: 100% !important;
		width: 100% !important;
	}

	.detail-input {
		background: rgba(255, 255, 255, 0.04) !important;
		border: 1px solid rgba(255, 255, 255, 0.1) !important;
		border-radius: 10px !important;
		height: 40px !important;
		font-size: 13.5px !important;
		width: 100% !important;
		box-sizing: border-box !important;
	}

	/* Account Info Hero: Sleek Horizontal Layout */
	.detail-avatar-hero {
		text-align: left !important;
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		flex-wrap: wrap !important;
		gap: 20px !important;
		margin-bottom: 20px !important;
		padding-bottom: 18px !important;
		border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
		width: 100% !important;
		max-width: 100% !important;
		box-sizing: border-box !important;
	}

	.detail-profile-identity {
		display: flex !important;
		flex-direction: row !important;
		align-items: center !important;
		gap: 16px !important;
		text-align: left !important;
	}

	.detail-name-badge-row {
		display: flex !important;
		flex-direction: row !important;
		align-items: center !important;
		gap: 10px !important;
	}

	.detail-avatar-wrap {
		width: 60px !important;
		height: 60px !important;
		margin: 0 !important;
		flex-shrink: 0 !important;
	}

	.detail-user-name {
		margin: 0 !important;
		font-size: 19px !important;
		font-weight: 800 !important;
		color: #ffffff !important;
	}

	.detail-user-badge {
		display: inline-block !important;
		width: fit-content !important;
		max-width: max-content !important;
		font-size: 10.5px !important;
		padding: 2px 10px !important;
	}

	.detail-uid-row {
		justify-content: flex-start !important;
		margin-top: 4px !important;
	}

	.detail-stats-strip {
		margin: 0 !important;
		display: flex !important;
		align-items: center !important;
		gap: 16px !important;
		background: rgba(255, 255, 255, 0.025) !important;
		border: 1px solid rgba(255, 255, 255, 0.06) !important;
		border-radius: 12px !important;
		padding: 8px 16px !important;
	}

	.detail-stat-col {
		text-align: center !important;
		min-width: 44px !important;
	}

	.detail-stat-val {
		font-size: 15px !important;
	}

	.detail-stat-lbl {
		font-size: 9px !important;
	}

	/* Action Buttons */
	.btn-settings-primary-action {
		max-width: 220px !important;
		height: 42px !important;
		border-radius: 8px !important;
		font-size: 13.5px !important;
	}

	.btn-settings-signout-full {
		max-width: 240px !important;
		height: 42px !important;
		border-radius: 8px !important;
		font-size: 13.5px !important;
	}

	/* Security & Privacy View 2-col inputs */
	#settings-view-security-privacy .settings-detail-card {
		display: grid !important;
		grid-template-columns: repeat(2, 1fr) !important;
		gap: 16px !important;
		width: 100% !important;
		max-width: 100% !important;
		margin-bottom: 20px !important;
		box-sizing: border-box !important;
	}

	/* Theme Color Swatches - Balanced Desktop Grid (Spanned 100% to the right) */
	.theme-palette-grid {
		display: grid !important;
		grid-template-columns: repeat(4, 1fr) !important;
		gap: 14px !important;
		width: 100% !important;
		max-width: 100% !important;
		margin-bottom: 20px !important;
		box-sizing: border-box !important;
	}

	.color-swatch-btn {
		padding: 16px 20px !important;
		border-radius: 12px !important;
		background: rgba(255, 255, 255, 0.035) !important;
		border: 1px solid rgba(255, 255, 255, 0.08) !important;
		gap: 12px !important;
		width: 100% !important;
		box-sizing: border-box !important;
	}

	.color-swatch-btn:hover {
		background: rgba(255, 255, 255, 0.06) !important;
		border-color: rgba(255, 255, 255, 0.16) !important;
		transform: translateY(-1px) !important;
	}

	.swatch-color {
		width: 24px !important;
		height: 24px !important;
		box-shadow: 0 2px 8px rgba(0,0,0,0.25) !important;
	}

	.swatch-label {
		font-size: 14px !important;
		font-weight: 700 !important;
	}

	.custom-color-row {
		width: 100% !important;
		max-width: 100% !important;
		background: rgba(255, 255, 255, 0.03) !important;
		border: 1px solid rgba(255, 255, 255, 0.07) !important;
		border-radius: 14px !important;
		padding: 18px 24px !important;
		margin-bottom: 20px !important;
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		box-sizing: border-box !important;
	}

	.theme-preview-box {
		min-width: 320px !important;
	}

	.mini-player-preview {
		padding: 10px 14px !important;
		border-radius: 10px !important;
	}

	.mini-player-bar {
		height: 5px !important;
	}

	.mini-progress-knob {
		width: 11px !important;
		height: 11px !important;
		top: -3px !important;
	}

	.mini-badge-preview,
	.mini-button-preview {
		font-size: 10px !important;
		padding: 2px 6px !important;
		border-radius: 4px !important;
	}

	.native-color-picker {
		width: 38px !important;
		height: 38px !important;
		border-radius: 10px !important;
	}

	.hex-text-input {
		height: 38px !important;
		width: 110px !important;
		font-size: 14px !important;
		border-radius: 10px !important;
	}

	/* Billing & Wallet Cards on Desktop (100% extended to right) */
	.billing-plan-card {
		background: rgba(255, 255, 255, 0.025) !important;
		border: 1px solid rgba(255, 255, 255, 0.06) !important;
		border-radius: 14px !important;
		padding: 20px 24px !important;
		width: 100% !important;
		max-width: 100% !important;
		box-sizing: border-box !important;
	}

	.wallet-balance-hero-card {
		background: rgba(234, 179, 8, 0.06) !important;
		border: 1px solid rgba(234, 179, 8, 0.18) !important;
		border-radius: 14px !important;
		padding: 20px 24px !important;
		width: 100% !important;
		max-width: 100% !important;
		box-sizing: border-box !important;
	}

	/* Transaction & Activity List in Detail View */
	.account-tx-row {
		transition: background 0.15s ease, transform 0.15s ease !important;
	}

	.account-tx-row:hover {
		background: rgba(255, 255, 255, 0.04) !important;
		transform: translateY(-1px) !important;
	}
}

/* ─────────────────────────────────────────────────────────────
   REWARDS VIEW STYLES (#settings-view-rewards)
   ───────────────────────────────────────────────────────────── */
.reward-settings-detail-body {
	position: relative !important;
	width: 100% !important;
	box-sizing: border-box !important;
}

.reward-hero-header {
	padding: 4px 0 20px 0 !important;
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

@media (min-width: 992px) {
	.reward-my-coins-section {
		justify-content: flex-end !important;
	}
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

.coin-sparkle-icon {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	filter: drop-shadow(0 2px 10px rgba(251, 191, 36, 0.6)) !important;
}

.reward-rules-link {
	display: inline-flex !important;
	align-items: center !important;
	gap: 5px !important;
	padding-left: 14px !important;
	border-left: 1px solid rgba(255, 255, 255, 0.14) !important;
	font-size: 12.5px !important;
	color: #94a3b8 !important;
	cursor: pointer !important;
	user-select: none !important;
	white-space: nowrap !important;
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
	font-size: 10px !important;
	font-weight: 800 !important;
	color: #cbd5e1 !important;
	line-height: 1 !important;
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

.reward-section-block {
	background: transparent !important;
	border: none !important;
	box-shadow: none !important;
	padding: 0 !important;
	width: 100% !important;
}

.reward-section-header {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	margin-bottom: 14px !important;
	padding: 0 !important;
}

.reward-section-title-wrap h3 {
	margin: 0 !important;
	font-size: 16.5px !important;
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

/* 7-Day Check-in Grid (Smooth rounded ambient glow, zero linear cutoff) */
.checkin-days-grid {
	display: flex !important;
	justify-content: space-between !important;
	gap: 10px !important;
	margin-bottom: 14px !important;
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
		padding: 16px 14px 20px 0 !important;
		padding-left: 0 !important;
		margin-left: 0 !important;
		margin-right: 0 !important;
		width: 100% !important;
		scrollbar-width: none !important;
	}
}

.checkin-days-grid::-webkit-scrollbar {
	display: none !important;
}

.checkin-day-item {
	flex: 1 1 0 !important;
	min-width: 52px !important;
	background: rgba(255, 255, 255, 0.04) !important;
	border: none !important;
	outline: none !important;
	border-radius: 12px !important;
	padding: 12px 4px 10px 4px !important;
	display: flex !important;
	flex-direction: column !important;
	align-items: center !important;
	justify-content: space-between !important;
	min-height: 98px !important;
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
	box-shadow: 0 4px 16px rgba(255, 94, 0, 0.45), 0 0 24px rgba(255, 94, 0, 0.2) !important;
	transform: translateY(-2px) scale(1.02) !important;
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
	font-size: 12.5px !important;
	font-weight: 800 !important;
	color: #cbd5e1 !important;
	letter-spacing: -0.2px !important;
	line-height: 1.1 !important;
}

.checkin-day-item.is-today .day-reward-amount {
	color: #ffffff !important;
	font-weight: 900 !important;
	font-size: 13px !important;
}

.day-status-icon,
.day-coin-icon {
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	margin: 4px 0 !important;
}

.checkmark-circle {
	width: 22px !important;
	height: 22px !important;
	border-radius: 50% !important;
	background: #ffffff !important;
	color: #e03200 !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	margin: 2px 0 !important;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
}

.day-label {
	font-size: 10.5px !important;
	color: #64748b !important;
	font-weight: 600 !important;
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

.checkin-status-banner {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	font-size: 11.5px !important;
	color: #94a3b8 !important;
	margin: 0 !important;
	padding: 0 2px !important;
}

.checkin-status-banner .checkin-status-dot {
	width: 7px !important;
	height: 7px !important;
	border-radius: 50% !important;
	background: #10b981 !important;
	box-shadow: 0 0 6px rgba(16, 185, 129, 0.7) !important;
	flex-shrink: 0 !important;
}

/* Watch Time Milestones */
.activity-subheading {
	margin-bottom: 14px !important;
}

.activity-subheading h4 {
	margin: 0 0 3px 0 !important;
	font-size: 14px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
}

.activity-subheading p {
	margin: 0 !important;
	font-size: 12px !important;
	color: #94a3b8 !important;
}

.milestones-envelopes-row {
	display: grid !important;
	grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
	gap: 8px !important;
	margin-bottom: 14px !important;
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

.milestone-envelope-card.is-locked {
	background: rgba(255, 255, 255, 0.05) !important;
	opacity: 0.7 !important;
}

.milestone-envelope-card.is-locked .envelope-value {
	color: #94a3b8 !important;
}

.milestone-envelope-card.is-ready {
	background: linear-gradient(180deg, #ff5533 0%, #e02800 100%) !important;
	opacity: 1 !important;
	box-shadow: 0 6px 18px rgba(255, 69, 0, 0.45) !important;
	animation: pulseReadyEnvelope 2s infinite ease-in-out !important;
}

.milestone-envelope-card.is-ready .envelope-value {
	color: #ffffff !important;
}

.milestone-envelope-card.is-completed,
.milestone-envelope-card.is-unlocked {
	background: rgba(255, 255, 255, 0.03) !important;
	opacity: 0.45 !important;
}

.milestone-envelope-card.is-completed .envelope-value {
	color: #64748b !important;
}

.envelope-top-coins {
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 2px !important;
	margin-bottom: 4px !important;
}

.envelope-value {
	font-size: 14px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	line-height: 1 !important;
}

.milestones-timeline-track {
	position: relative !important;
	margin: 16px 0 20px 0 !important;
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
	gap: 6px !important;
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
	font-size: 10.5px !important;
	color: #94a3b8 !important;
	white-space: nowrap !important;
	font-weight: 600 !important;
}

.reward-action-btn-primary {
	width: 100% !important;
	height: 46px !important;
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	border: none !important;
	outline: none !important;
	border-radius: 999px !important;
	padding: 0 20px !important;
	font-size: 13.5px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 8px !important;
	box-shadow: 0 4px 16px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease, filter 0.15s ease !important;
	box-sizing: border-box !important;
}

.reward-action-btn-primary:hover {
	filter: brightness(1.1) !important;
	transform: translateY(-1px) !important;
}

.reward-action-btn-primary.is-disabled {
	background: rgba(255, 255, 255, 0.08) !important;
	color: #64748b !important;
	box-shadow: none !important;
	cursor: default !important;
}

/* Tasks & Missions */
.reward-tasks-list,
.reward-tasks-grid {
	display: grid !important;
	grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
	gap: 20px !important;
	width: 100% !important;
	align-items: stretch !important;
}

@media (max-width: 860px) {
	.reward-tasks-list,
	.reward-tasks-grid {
		grid-template-columns: 1fr !important;
		gap: 14px !important;
	}
}

.reward-task-row {
	display: flex !important;
	flex-direction: column !important;
	justify-content: center !important;
	gap: 12px !important;
	background: rgba(255, 255, 255, 0.035) !important;
	border-radius: 16px !important;
	padding: 18px 20px !important;
	border: 1px solid rgba(255, 255, 255, 0.06) !important;
	box-sizing: border-box !important;
	height: 100% !important;
	min-height: 88px !important;
}

.task-top-split {
	display: flex !important;
	align-items: center !important;
	gap: 12px !important;
	width: 100% !important;
}

.task-icon-col {
	width: 40px !important;
	height: 40px !important;
	border-radius: 50% !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	color: #ffffff !important;
	flex-shrink: 0 !important;
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
	margin: 0 0 2px 0 !important;
	font-size: 13.5px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
}

.task-sub {
	margin: 0 0 3px 0 !important;
	font-size: 11.5px !important;
	color: #94a3b8 !important;
}

.task-coin-reward {
	font-size: 12.5px !important;
	font-weight: 800 !important;
	color: #fb923c !important;
	display: inline-flex !important;
	align-items: center !important;
	gap: 4px !important;
	line-height: 1 !important;
}

.task-btn-secondary {
	background: rgba(255, 255, 255, 0.08) !important;
	color: #94a3b8 !important;
	border: none !important;
	outline: none !important;
	padding: 7px 16px !important;
	border-radius: 999px !important;
	font-size: 12px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	white-space: nowrap !important;
}

.task-btn-go {
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	border: none !important;
	outline: none !important;
	padding: 7px 20px !important;
	border-radius: 999px !important;
	font-size: 12.5px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
	box-shadow: 0 4px 12px rgba(255, 69, 0, 0.35) !important;
	transition: transform 0.15s ease !important;
	white-space: nowrap !important;
}

.task-btn-go:hover {
	transform: scale(1.04) !important;
}

.ad-steps-track {
	display: grid !important;
	grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
	gap: 6px !important;
}

.ad-step-pill {
	background: rgba(255, 255, 255, 0.04) !important;
	border-radius: 8px !important;
	padding: 6px 2px !important;
	text-align: center !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 2px !important;
}

.ad-step-pill.is-active-step {
	background: linear-gradient(180deg, #ff6b00 0%, #ff4500 100%) !important;
}

.step-reward {
	font-size: 11px !important;
	font-weight: 800 !important;
	color: #fbbf24 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	gap: 2px !important;
}

.ad-step-pill.is-active-step .step-reward {
	color: #ffffff !important;
}

.step-label {
	font-size: 9.5px !important;
	color: #94a3b8 !important;
}

.ad-step-pill.is-active-step .step-label {
	color: #ffffff !important;
	font-weight: 700 !important;
}

/* Toast Notification */
.reward-toast-popup {
	position: fixed !important;
	bottom: calc(env(safe-area-inset-bottom, 0px) + 74px) !important;
	left: 50% !important;
	transform: translateX(-50%) !important;
	z-index: 999999 !important;
	animation: toastBottomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
	pointer-events: none !important;
	max-width: min(380px, 90vw) !important;
}

.reward-toast-content {
	background: #1c1d24 !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	box-shadow: 0 8px 24px rgba(0, 0, 0, 0.7) !important;
	padding: 9px 18px !important;
	border-radius: 999px !important;
	display: flex !important;
	align-items: center !important;
	gap: 7px !important;
	font-size: 13px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
	white-space: nowrap !important;
}

/* Modals */
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
	border: 1px solid rgba(255, 255, 255, 0.08);
	border-radius: 20px;
	max-width: 440px;
	width: 100%;
	padding: 24px 26px;
	box-shadow: 0 16px 40px rgba(0, 0, 0, 0.7);
	color: #ffffff;
	z-index: 10;
}

.reward-confirm-card {
	text-align: center !important;
	padding: 28px 24px 22px 24px !important;
	max-width: 360px !important;
}

.reward-confirm-title {
	font-size: 18px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	margin: 0 0 10px 0 !important;
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
	height: 46px !important;
	background: linear-gradient(135deg, #ff5e00 0%, #ff3700 100%) !important;
	color: #ffffff !important;
	border: none !important;
	border-radius: 999px !important;
	font-size: 14px !important;
	font-weight: 800 !important;
	cursor: pointer !important;
}

.reward-confirm-btn-exit {
	width: 100% !important;
	height: 38px !important;
	background: transparent !important;
	color: #94a3b8 !important;
	border: none !important;
	font-size: 13px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
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

/* Rewarded Video Ad Modal Fullscreen Player */
.short-reward-video-modal-overlay {
	position: fixed !important;
	inset: 0 !important;
	width: 100% !important;
	height: 100% !important;
	height: 100dvh !important;
	z-index: 99999999 !important;
	display: none;
	padding: 0 !important;
	margin: 0 !important;
	background: #000000 !important;
	box-sizing: border-box !important;
	overflow: hidden !important;
}

.reward-video-card {
	position: absolute !important;
	inset: 0 !important;
	width: 100% !important;
	height: 100% !important;
	background: #000000 !important;
	border: none !important;
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
	background: linear-gradient(180deg, rgba(0, 0, 0, 0.7) 0%, transparent 100%) !important;
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
}

.reward-video-badge .ad-title {
	font-size: 13.5px;
	font-weight: 700;
	color: #ffffff;
}

.reward-video-timer-pill {
	font-size: 13.5px !important;
	font-weight: 800 !important;
	color: #fbbf24 !important;
	text-shadow: 0 1px 4px rgba(0, 0, 0, 0.9) !important;
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
}

.reward-video-sound-float-btn {
	position: absolute !important;
	right: 18px !important;
	bottom: 82px !important;
	z-index: 35 !important;
	background: transparent !important;
	border: none !important;
	color: #ffffff !important;
	width: 40px !important;
	height: 40px !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	cursor: pointer !important;
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
	background: #000000 !important;
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

.reward-video-bottom-bar {
	position: absolute !important;
	bottom: 0 !important;
	left: 0 !important;
	right: 0 !important;
	z-index: 30 !important;
	padding: 30px 20px max(18px, env(safe-area-inset-bottom, 18px)) 20px !important;
	background: linear-gradient(0deg, rgba(0, 0, 0, 0.95) 0%, transparent 100%) !important;
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
}

.reward-video-completed-overlay.show {
	display: flex !important;
}

.reward-video-completed-overlay .completed-content {
	text-align: center;
	padding: 24px;
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
/* ─────────────────────────────────────────────────────────────
   SECURITY & PRIVACY REDESIGN (SaaS POLISH)
   ───────────────────────────────────────────────────────────── */
.security-hero-banner {
	display: flex !important;
	align-items: flex-start !important;
	gap: 16px !important;
	background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(6, 182, 212, 0.04) 100%) !important;
	border: 1px solid rgba(16, 185, 129, 0.22) !important;
	border-radius: 16px !important;
	padding: 20px 22px !important;
	margin-bottom: 26px !important;
}

.security-hero-icon-box {
	width: 48px !important;
	height: 48px !important;
	border-radius: 14px !important;
	background: rgba(16, 185, 129, 0.15) !important;
	border: 1px solid rgba(16, 185, 129, 0.3) !important;
	color: #10b981 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

.security-hero-meta {
	flex: 1 !important;
	min-width: 0 !important;
}

.security-hero-badge-row {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	flex-wrap: wrap !important;
	margin-bottom: 8px !important;
}

.security-status-badge {
	display: inline-flex !important;
	align-items: center !important;
	gap: 6px !important;
	background: rgba(16, 185, 129, 0.15) !important;
	color: #34d399 !important;
	font-size: 11.5px !important;
	font-weight: 700 !important;
	padding: 3px 10px !important;
	border-radius: 999px !important;
	border: 1px solid rgba(16, 185, 129, 0.25) !important;
}

.security-pulse-dot {
	width: 7px !important;
	height: 7px !important;
	border-radius: 50% !important;
	background: #10b981 !important;
	box-shadow: 0 0 8px #10b981 !important;
	animation: securityPulse 2s infinite ease-in-out !important;
}

@keyframes securityPulse {
	0% { opacity: 0.6; transform: scale(0.9); }
	50% { opacity: 1; transform: scale(1.15); box-shadow: 0 0 12px #34d399; }
	100% { opacity: 0.6; transform: scale(0.9); }
}

.security-enc-badge {
	display: inline-flex !important;
	align-items: center !important;
	gap: 5px !important;
	background: rgba(255, 255, 255, 0.05) !important;
	color: #94a3b8 !important;
	font-size: 11px !important;
	font-weight: 600 !important;
	padding: 3px 9px !important;
	border-radius: 999px !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
}

.security-hero-title {
	margin: 0 0 4px 0 !important;
	font-size: 17px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	letter-spacing: -0.2px !important;
}

.security-hero-desc {
	margin: 0 !important;
	font-size: 13px !important;
	color: #94a3b8 !important;
	line-height: 1.45 !important;
}

/* Identity & Authentication Grid */
.security-identity-grid {
	display: grid !important;
	grid-template-columns: repeat(2, 1fr) !important;
	gap: 14px !important;
	margin-bottom: 8px !important;
}

@media (max-width: 768px) {
	.security-hero-banner {
		display: none !important;
	}
}

@media (max-width: 680px) {
	.security-identity-grid {
		grid-template-columns: 1fr !important;
	}
}

.security-info-tile {
	background: rgba(255, 255, 255, 0.03) !important;
	border: 1px solid rgba(255, 255, 255, 0.07) !important;
	border-radius: 14px !important;
	padding: 16px !important;
	display: flex !important;
	flex-direction: column !important;
	justify-content: space-between !important;
}

.security-tile-header {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	gap: 8px !important;
	margin-bottom: 12px !important;
}

.security-tile-label {
	font-size: 12px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	text-transform: uppercase !important;
	letter-spacing: 0.4px !important;
}

.security-tile-tag {
	font-size: 10px !important;
	font-weight: 700 !important;
	color: #64748b !important;
	background: rgba(255, 255, 255, 0.05) !important;
	padding: 2px 7px !important;
	border-radius: 6px !important;
}

.security-tile-tag.tag-green {
	color: #34d399 !important;
	background: rgba(16, 185, 129, 0.12) !important;
	border: 1px solid rgba(16, 185, 129, 0.25) !important;
}

.security-uid-copy-wrap {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	background: rgba(0, 0, 0, 0.3) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 10px !important;
	padding: 4px 6px 4px 12px !important;
}

.security-uid-input {
	flex: 1 !important;
	min-width: 0 !important;
	background: transparent !important;
	border: none !important;
	color: #f1f5f9 !important;
	font-family: monospace !important;
	font-size: 13.5px !important;
	font-weight: 600 !important;
	padding: 6px 0 !important;
	outline: none !important;
	cursor: text !important;
}

.btn-security-copy {
	display: inline-flex !important;
	align-items: center !important;
	gap: 5px !important;
	background: rgba(255, 255, 255, 0.08) !important;
	border: 1px solid rgba(255, 255, 255, 0.12) !important;
	color: #ffffff !important;
	padding: 6px 12px !important;
	border-radius: 8px !important;
	font-size: 12px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	transition: all 0.15s ease !important;
	flex-shrink: 0 !important;
}

.btn-security-copy:hover {
	background: rgba(255, 255, 255, 0.16) !important;
	transform: translateY(-1px) !important;
}

.security-session-box {
	display: flex !important;
	align-items: center !important;
	gap: 12px !important;
	background: rgba(0, 0, 0, 0.2) !important;
	border: 1px solid rgba(255, 255, 255, 0.06) !important;
	border-radius: 10px !important;
	padding: 10px 12px !important;
}

.session-device-icon {
	width: 34px !important;
	height: 34px !important;
	border-radius: 8px !important;
	background: rgba(255, 255, 255, 0.05) !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	color: #38bdf8 !important;
	flex-shrink: 0 !important;
}

.session-device-info {
	flex: 1 !important;
	min-width: 0 !important;
}

.session-device-name {
	font-size: 13px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
	white-space: nowrap !important;
	overflow: hidden !important;
	text-overflow: ellipsis !important;
}

.session-device-meta {
	display: flex !important;
	align-items: center !important;
	gap: 6px !important;
	font-size: 11.5px !important;
	color: #64748b !important;
	margin-top: 2px !important;
}

.session-dot-green {
	width: 6px !important;
	height: 6px !important;
	border-radius: 50% !important;
	background: #10b981 !important;
	box-shadow: 0 0 5px #10b981 !important;
}

/* Storage & Cache Management */
.security-storage-card {
	background: rgba(255, 255, 255, 0.03) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 16px !important;
	padding: 18px 20px !important;
	margin-bottom: 14px !important;
}

.storage-meter-top {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	gap: 12px !important;
	margin-bottom: 12px !important;
}

.storage-meter-label {
	font-size: 12px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	text-transform: uppercase !important;
	letter-spacing: 0.3px !important;
	margin-bottom: 2px !important;
}

.storage-meter-value {
	font-size: 18px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
}

.storage-meter-value span {
	font-size: 13px !important;
	font-weight: 500 !important;
	color: #64748b !important;
	margin-left: 4px !important;
}

.storage-status-chip {
	display: inline-flex !important;
	align-items: center !important;
	gap: 5px !important;
	background: rgba(16, 185, 129, 0.12) !important;
	color: #34d399 !important;
	font-size: 11.5px !important;
	font-weight: 700 !important;
	padding: 4px 10px !important;
	border-radius: 999px !important;
	border: 1px solid rgba(16, 185, 129, 0.25) !important;
}

.storage-bar-track {
	height: 8px !important;
	border-radius: 6px !important;
	background: rgba(255, 255, 255, 0.06) !important;
	display: flex !important;
	overflow: hidden !important;
	margin-bottom: 14px !important;
	gap: 2px !important;
}

.storage-bar-seg {
	height: 100% !important;
	border-radius: 3px !important;
	transition: width 0.3s ease !important;
}

.storage-bar-seg.seg-video {
	background: linear-gradient(90deg, #f97316, #ea580c) !important;
}

.storage-bar-seg.seg-history {
	background: linear-gradient(90deg, #38bdf8, #0284c7) !important;
}

.storage-bar-seg.seg-cache {
	background: linear-gradient(90deg, #a855f7, #9333ea) !important;
}

.storage-legend-row {
	display: flex !important;
	align-items: center !important;
	gap: 16px !important;
	flex-wrap: wrap !important;
}

.storage-legend-item {
	display: flex !important;
	align-items: center !important;
	gap: 6px !important;
	font-size: 11.5px !important;
	color: #94a3b8 !important;
	font-weight: 500 !important;
}

.legend-dot {
	width: 8px !important;
	height: 8px !important;
	border-radius: 50% !important;
}

.legend-dot.dot-video { background: #f97316 !important; }
.legend-dot.dot-history { background: #38bdf8 !important; }
.legend-dot.dot-cache { background: #a855f7 !important; }

/* Security Actions List */
.security-actions-card {
	background: rgba(255, 255, 255, 0.03) !important;
	border: 1px solid rgba(255, 255, 255, 0.07) !important;
	border-radius: 16px !important;
	overflow: hidden !important;
	margin-bottom: 8px !important;
}

.security-action-row {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	gap: 16px !important;
	padding: 16px 20px !important;
	border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
	transition: background 0.15s ease !important;
}

.security-action-row:last-child {
	border-bottom: none !important;
}

.security-action-row:hover {
	background: rgba(255, 255, 255, 0.02) !important;
}

@media (max-width: 600px) {
	.security-action-row {
		flex-direction: column !important;
		align-items: stretch !important;
		gap: 12px !important;
	}
	.security-action-right {
		display: flex !important;
		justify-content: flex-end !important;
	}
}

.security-action-left {
	display: flex !important;
	align-items: center !important;
	gap: 14px !important;
	flex: 1 !important;
	min-width: 0 !important;
}

.security-action-icon-circle {
	width: 40px !important;
	height: 40px !important;
	border-radius: 12px !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

.security-action-icon-circle.icon-orange {
	background: rgba(249, 115, 22, 0.12) !important;
	color: #f97316 !important;
	border: 1px solid rgba(249, 115, 22, 0.25) !important;
}

.security-action-icon-circle.icon-red {
	background: rgba(239, 68, 68, 0.12) !important;
	color: #ef4444 !important;
	border: 1px solid rgba(239, 68, 68, 0.25) !important;
}

.security-action-text {
	flex: 1 !important;
	min-width: 0 !important;
}

.action-row-title {
	margin: 0 0 3px 0 !important;
	font-size: 14.5px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
}

.action-row-desc {
	margin: 0 !important;
	font-size: 12px !important;
	color: #808d9e !important;
	line-height: 1.4 !important;
}

.btn-security-action {
	display: inline-flex !important;
	align-items: center !important;
	gap: 6px !important;
	background: rgba(255, 255, 255, 0.08) !important;
	border: 1px solid rgba(255, 255, 255, 0.15) !important;
	color: #ffffff !important;
	padding: 8px 16px !important;
	border-radius: 999px !important;
	font-size: 12.5px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	transition: all 0.15s ease !important;
	white-space: nowrap !important;
}

.btn-security-action:hover {
	background: rgba(255, 255, 255, 0.15) !important;
	border-color: rgba(255, 255, 255, 0.3) !important;
	transform: translateY(-1px) !important;
}

.btn-security-action.btn-action-outline-danger {
	background: rgba(239, 68, 68, 0.1) !important;
	border-color: rgba(239, 68, 68, 0.25) !important;
	color: #fca5a5 !important;
}

.btn-security-action.btn-action-outline-danger:hover {
	background: rgba(239, 68, 68, 0.2) !important;
	border-color: rgba(239, 68, 68, 0.45) !important;
	color: #ffffff !important;
}

/* Privacy & Telemetry Toggles Card */
.security-privacy-toggles-card {
	background: rgba(255, 255, 255, 0.03) !important;
	border: 1px solid rgba(255, 255, 255, 0.07) !important;
	border-radius: 16px !important;
	overflow: hidden !important;
	margin-bottom: 8px !important;
}

.privacy-toggle-row {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	gap: 16px !important;
	padding: 16px 20px !important;
	border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
}

.privacy-toggle-row:last-child {
	border-bottom: none !important;
}

.privacy-toggle-info {
	flex: 1 !important;
	min-width: 0 !important;
}

.privacy-toggle-title {
	margin: 0 0 3px 0 !important;
	font-size: 14.5px !important;
	font-weight: 700 !important;
	color: #ffffff !important;
}

.privacy-toggle-desc {
	margin: 0 !important;
	font-size: 12px !important;
	color: #808d9e !important;
	line-height: 1.4 !important;
}

/* Danger Zone Card */
.security-danger-zone-card {
	background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.03) 100%) !important;
	border: 1px solid rgba(239, 68, 68, 0.25) !important;
	border-radius: 16px !important;
	padding: 20px 22px !important;
	margin-bottom: 30px !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 16px !important;
}

.danger-zone-header {
	display: flex !important;
	align-items: flex-start !important;
	gap: 14px !important;
}

.danger-zone-icon {
	width: 36px !important;
	height: 36px !important;
	border-radius: 10px !important;
	background: rgba(239, 68, 68, 0.15) !important;
	border: 1px solid rgba(239, 68, 68, 0.3) !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

.danger-zone-meta {
	flex: 1 !important;
	min-width: 0 !important;
}

.danger-zone-title {
	margin: 0 0 4px 0 !important;
	font-size: 15.5px !important;
	font-weight: 800 !important;
	color: #fca5a5 !important;
}

.danger-zone-desc {
	margin: 0 !important;
	font-size: 12.5px !important;
	color: #cbd5e1 !important;
	line-height: 1.45 !important;
}

.danger-zone-action {
	width: 100% !important;
}
</style>

<script>
(function() {
	'use strict';

	// ─────────────────────────────────────────────────────────────
	// VIEW SWITCHING ENGINE (SPA ROUTING IN SETTINGS)
	// ─────────────────────────────────────────────────────────────
	function switchSettingsView(targetViewId) {
		var isDesktop = window.innerWidth >= 992;
		
		if (targetViewId === 'reward') {
			targetViewId = 'rewards';
		}

		// If on desktop and target is 'hub', default to 'account-info' so right panel is not empty
		if (isDesktop && (!targetViewId || targetViewId === 'hub')) {
			targetViewId = 'account-info';
		}

		var views = document.querySelectorAll('.settings-view-pane');
		views.forEach(function(v) {
			v.classList.remove('is-active');
		});

		var targetPane = document.getElementById('settings-view-' + targetViewId);
		if (targetPane) {
			targetPane.classList.add('is-active');
		}

		// On desktop, the hub is the left sidebar and stays active
		if (isDesktop) {
			var hubPane = document.getElementById('settings-view-hub');
			if (hubPane) hubPane.classList.add('is-active');
		}

		// Update sidebar menu item active selection highlight
		var allMenuItems = document.querySelectorAll('.settings-menu-item[data-target-view]');
		allMenuItems.forEach(function(item) {
			if (item.getAttribute('data-target-view') === targetViewId) {
				item.classList.add('is-selected-item');
			} else {
				item.classList.remove('is-selected-item');
			}
		});

		// Update mobile bottom nav active state according to view
		syncAccountBottomNav(targetViewId);

		if (!isDesktop) {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		}

		// Update URL hash without reload
		if (targetViewId !== 'hub') {
			history.pushState(null, null, '#view=' + targetViewId);
		} else {
			history.pushState(null, null, window.location.pathname);
		}
	}
	window.switchSettingsView = switchSettingsView;

	function syncAccountBottomNav(targetViewId) {
		if (!targetViewId) {
			targetViewId = parseAccountHashView() || (window.innerWidth >= 992 ? 'account-info' : 'hub');
		}
		var isRewardView = (targetViewId === 'rewards' || targetViewId === 'reward' || (window.location.hash && window.location.hash.indexOf('reward') !== -1));
		var bottomNavItems = document.querySelectorAll('.short-mobile-bottom-nav .mobile-nav-item, .mobile-bottom-nav .mobile-nav-item');
		if (bottomNavItems && bottomNavItems.length > 0) {
			bottomNavItems.forEach(function(navItem) {
				var navKey = (navItem.getAttribute('data-nav') || '').toLowerCase();
				var navHref = (navItem.getAttribute('href') || '').toLowerCase();
				var isRewardItem = (navKey === 'mobile_rewards' || navKey === 'rewards' || navKey === 'reward' || navHref.indexOf('reward') !== -1);
				var isAccountItem = (navKey === 'mobile_account' || navKey === 'account' || navKey === 'profile' || (navHref.indexOf('account') !== -1 && !isRewardItem));
				
				if (isRewardView) {
					if (isRewardItem) {
						navItem.classList.add('active');
					} else if (isAccountItem) {
						navItem.classList.remove('active');
					}
				} else {
					if (isAccountItem) {
						navItem.classList.add('active');
					} else if (isRewardItem) {
						navItem.classList.remove('active');
					}
				}
			});
		}
	}
	window.syncAccountBottomNav = syncAccountBottomNav;

	// Mobile bottom nav click synchronization for account & rewards on page-account
	function bindAccountBottomNavEvents() {
		syncAccountBottomNav();
		var rewItem = document.querySelector('.short-mobile-bottom-nav [data-nav="mobile_rewards"], .short-mobile-bottom-nav [data-nav="rewards"], .short-mobile-bottom-nav [data-nav="reward"], .short-mobile-bottom-nav a[href*="reward"]');
		if (rewItem && !rewItem._boundRewardClick) {
			rewItem._boundRewardClick = true;
			rewItem.addEventListener('click', function(e) {
				e.preventDefault();
				switchSettingsView('rewards');
				syncAccountBottomNav('rewards');
			});
		}
		var accItem = document.querySelector('.short-mobile-bottom-nav [data-nav="mobile_account"], .short-mobile-bottom-nav [data-nav="account"], .short-mobile-bottom-nav a[href*="account"]:not([href*="reward"])');
		if (accItem && !accItem._boundAccountClick) {
			accItem._boundAccountClick = true;
			accItem.addEventListener('click', function(e) {
				if (window.innerWidth < 992) {
					e.preventDefault();
					switchSettingsView('hub');
					syncAccountBottomNav('hub');
				}
			});
		}
	}
	document.addEventListener('DOMContentLoaded', bindAccountBottomNavEvents);
	window.addEventListener('load', syncAccountBottomNav);
	window.addEventListener('hashchange', function() {
		var target = parseAccountHashView();
		if (target) {
			switchSettingsView(target);
		} else {
			switchSettingsView('hub');
		}
		syncAccountBottomNav(target);
	});
	setTimeout(syncAccountBottomNav, 50);
	setTimeout(syncAccountBottomNav, 150);
	setTimeout(syncAccountBottomNav, 400);
	setTimeout(syncAccountBottomNav, 1000);
	setInterval(syncAccountBottomNav, 1500);

	// Auth-gate modal logic
	var authGateModal   = document.getElementById('auth-gate-modal');
	var authGateClose   = document.getElementById('auth-gate-close');
	var authGateGoogleBtn = document.getElementById('auth-gate-google-btn');
	var authGateMsg     = document.getElementById('auth-gate-msg');

	var AUTH_GATE_LABELS = {
		'account-info':     'Please sign in to view and edit your account information.',
		'connected-apps':   'Please sign in to manage connected accounts.',
		'billing-plans':    'Please sign in to manage your membership.',
		'wallet-coins':     'Please sign in to view your drama coins wallet.',
		'security-privacy': 'Please sign in to manage security & privacy details.'
	};

	function showAuthGate(viewId) {
		if (authGateMsg) authGateMsg.textContent = AUTH_GATE_LABELS[viewId] || 'Please sign in to access this setting.';
		if (authGateModal) { authGateModal.style.display = 'flex'; }
	}
	function hideAuthGate() {
		if (authGateModal) authGateModal.style.display = 'none';
	}
	if (authGateClose)  authGateClose.addEventListener('click', hideAuthGate);
	if (authGateModal)  authGateModal.addEventListener('click', function(e) { if (e.target === authGateModal) hideAuthGate(); });
	if (authGateGoogleBtn) {
		authGateGoogleBtn.addEventListener('click', function() {
			hideAuthGate();
			triggerGoogleSignIn();
		});
	}

	// Bind Menu Item Clicks in Hub
	var menuItems = document.querySelectorAll('.settings-menu-item');
	menuItems.forEach(function(item) {
		item.addEventListener('click', function() {
			var href = this.getAttribute('data-href');
			if (href) {
				window.location.href = href;
				return;
			}
			var viewId      = this.getAttribute('data-target-view');
			var needsAuth   = this.getAttribute('data-requires-auth') === 'true';
			var isLoggedIn  = (localStorage.getItem('short_is_logged_in') === '1') && !!localStorage.getItem('short_user_email');
			if (!isLoggedIn && needsAuth) {
				showAuthGate(viewId);
				return;
			}
			if (viewId) switchSettingsView(viewId);
		});
	});

	// Bind Back Buttons
	var backBtns = document.querySelectorAll('.btn-back-to-hub');
	backBtns.forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			switchSettingsView('hub');
		});
	});

	function parseAccountHashView() {
		var raw = (window.location.hash || '').replace(/^#/, '').trim();
		if (!raw) return null;
		if (raw === 'reward' || raw === 'rewards') return 'rewards';
		var m = raw.match(/view=([a-zA-Z0-9_-]+)/);
		if (m && m[1]) {
			return m[1] === 'reward' ? 'rewards' : m[1];
		}
		if (document.getElementById('settings-view-' + raw)) {
			return raw;
		}
		return null;
	}

	// Handle browser back/forward buttons
	window.addEventListener('popstate', function() {
		var target = parseAccountHashView();
		if (target) {
			switchSettingsView(target);
		} else {
			switchSettingsView('hub');
		}
	});

	// Check hash or screen width on load
	var initTarget = parseAccountHashView();
	if (initTarget) {
		switchSettingsView(initTarget);
	} else if (window.innerWidth >= 992) {
		switchSettingsView('account-info');
	}

	// Handle screen resize between mobile and desktop
	window.addEventListener('resize', function() {
		var isDesktop = window.innerWidth >= 992;
		var activePane = document.querySelector('.settings-view-pane.is-active:not(#settings-view-hub)');
		if (isDesktop && !activePane) {
			switchSettingsView('account-info');
		}
	});

	// ─────────────────────────────────────────────────────────────
	// SETTINGS SEARCH FILTER & TOGGLE
	// ─────────────────────────────────────────────────────────────
	var searchToggleBtn = document.getElementById('btn-toggle-hub-search');
	var searchWrap = document.getElementById('settings-search-bar-wrap');
	var searchInput = document.getElementById('settings-search-input');
	var clearBtn = document.getElementById('btn-clear-settings-search');

	if (searchToggleBtn && searchWrap) {
		searchToggleBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var isVisible = searchWrap.style.display !== 'none';
			if (isVisible) {
				searchWrap.style.display = 'none';
				if (searchInput) {
					searchInput.value = '';
					searchInput.dispatchEvent(new Event('input'));
				}
			} else {
				searchWrap.style.display = 'block';
				if (searchInput) searchInput.focus();
			}
		});
	}

	if (searchInput) {
		searchInput.addEventListener('input', function() {
			var query = this.value.trim().toLowerCase();
			if (clearBtn) clearBtn.style.display = query ? 'inline-block' : 'none';

			var items = document.querySelectorAll('.settings-menu-item');
			var groups = document.querySelectorAll('.settings-group-block');

			items.forEach(function(item) {
				var searchKeywords = (item.getAttribute('data-search') || '').toLowerCase();
				var titleText = (item.querySelector('.menu-item-title') ? item.querySelector('.menu-item-title').textContent : '').toLowerCase();
				var descText = (item.querySelector('.menu-item-desc') ? item.querySelector('.menu-item-desc').textContent : '').toLowerCase();

				var isMatch = !query || searchKeywords.includes(query) || titleText.includes(query) || descText.includes(query);
				item.style.display = isMatch ? 'flex' : 'none';
			});

			// Hide group if all its items are hidden
			groups.forEach(function(grp) {
				var visibleItems = grp.querySelectorAll('.settings-menu-item:not([style*="display: none"])');
				grp.style.display = visibleItems.length ? 'block' : 'none';
			});
		});
	}

	if (clearBtn) {
		clearBtn.addEventListener('click', function() {
			if (searchInput) {
				searchInput.value = '';
				searchInput.dispatchEvent(new Event('input'));
				searchInput.focus();
			}
		});
	}

	// ─────────────────────────────────────────────────────────────
	// STATE HYDRATION (FIREBASE / LOCALSTORAGE)
	// ─────────────────────────────────────────────────────────────
	function syncSettingsState(user) {
		var hubAvatar = document.getElementById('settings-hub-avatar');
		var detailAvatar = document.getElementById('detail-account-avatar');
		var hubAccountSub = document.getElementById('hub-account-sub');
		var hubGooglePill = document.getElementById('hub-google-conn-pill');
		var hubSigninBtn = document.getElementById('btn-hub-signin');
		var detailName = document.getElementById('detail-display-name');
		var detailInputName = document.getElementById('detail-input-name');
		var detailInputEmail = document.getElementById('detail-input-email');
		var detailInputUid = document.getElementById('detail-input-uid');
		var socialGoogleSub = document.getElementById('social-google-sub');
		var btnGoogleAuth = document.getElementById('btn-google-auth-sync');
		var btnSecuritySignout = document.getElementById('btn-security-signout');
		var detailAuthBadge = document.getElementById('detail-auth-badge');
		var detailPlanEmail = document.getElementById('detail-plan-email');
		var detailCoinsVal = document.getElementById('detail-coins-val');
		var hubWalletCoinsPill = document.getElementById('hub-wallet-coins-pill');
		var detailWalletBalanceNum = document.getElementById('detail-wallet-balance-num');
		var uidPill = document.getElementById('detail-account-uid-pill');

		var defaultAvatar = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 36 36' fill='none'><circle cx='18' cy='18' r='18' fill='%231f232b'/><circle cx='18' cy='13.5' r='5.5' fill='%2394a3b8'/><path d='M8 29.5c0-5.5 4.5-9 10-9s10 3.5 10 9' fill='%2394a3b8'/></svg>";

		var coins = localStorage.getItem('shorttv_user_coins') || localStorage.getItem('short_user_coins') || '100';
		if (detailCoinsVal) detailCoinsVal.textContent = 'Balance: ' + coins + ' Coins';
		if (hubWalletCoinsPill) hubWalletCoinsPill.textContent = coins + ' Coins';
		if (detailWalletBalanceNum) detailWalletBalanceNum.textContent = coins;

		// If user is not provided by Firebase (e.g. localhost 408), preserve existing active login
		if (!user || (!user.uid && !user.email)) {
			var savedEmail = localStorage.getItem('short_user_email');
			var isSavedAuth = (localStorage.getItem('short_is_logged_in') === '1') || (savedEmail && savedEmail !== 'guest@short.local');
			if (isSavedAuth && savedEmail) {
				user = {
					email: savedEmail,
					displayName: localStorage.getItem('short_user_display_name') || (savedEmail.split('@')[0] || 'Member'),
					uid: localStorage.getItem('short_user_uid') || 'local_' + savedEmail,
					photoURL: localStorage.getItem('short_user_photo') || localStorage.getItem('shorttv_user_avatar') || ''
				};
			}
		}

		if (user && (user.uid || user.email)) {
			var displayName = user.displayName || (user.email ? user.email.split('@')[0] : 'ShortTV Member');
			var email = user.email || '';
			var uid = user.uid || '';
			var photo = user.photoURL || '';

			localStorage.setItem('short_is_logged_in', '1');
			if (email) localStorage.setItem('short_user_email', email);
			if (displayName) localStorage.setItem('short_user_display_name', displayName);
			if (uid) localStorage.setItem('short_user_uid', uid);
			if (photo) {
				localStorage.setItem('short_user_photo', photo);
				localStorage.setItem('shorttv_user_avatar', photo);
			}

			if (hubAvatar) hubAvatar.src = photo || defaultAvatar;
			if (detailAvatar) detailAvatar.src = photo || defaultAvatar;
			var mobNavAvatar = document.getElementById('mobile-nav-account-avatar') || document.querySelector('.mobile-nav-avatar-img');
			if (mobNavAvatar && photo) mobNavAvatar.src = photo;

			if (hubAccountSub) hubAccountSub.textContent = displayName + ' • ' + email;
			if (hubSigninBtn) hubSigninBtn.style.display = 'none';

			if (detailName) detailName.textContent = displayName;
			if (detailInputName) detailInputName.value = displayName;
			if (detailInputEmail) detailInputEmail.value = email;
			if (detailInputUid) detailInputUid.value = uid;
			if (detailAuthBadge) {
				detailAuthBadge.textContent = 'Verified User';
				detailAuthBadge.style.background = 'rgba(34, 197, 94, 0.15)';
				detailAuthBadge.style.color = '#4ade80';
			}
			if (uidPill) uidPill.textContent = 'UID: ' + uid.substring(0, 14) + '...';
			if (socialGoogleSub) socialGoogleSub.textContent = email;
			if (btnGoogleAuth) {
				btnGoogleAuth.className = 'btn-conn-toggle is-connected';
				btnGoogleAuth.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>Connected</span>';
			}
			if (btnSecuritySignout) {
				btnSecuritySignout.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg> <span>Sign Out Everywhere</span>';
			}
			// Check VIP subscription in RTDB & localStorage
			var localSub = null;
			try {
				localSub = JSON.parse(localStorage.getItem('short_subscription') || 'null');
			} catch(e){}

			if (localSub && (localSub.status === 'active' || localSub.active === true) && localSub.plan_id && localSub.plan_id !== 'free' && localSub.plan_id !== 'free_tier') {
				applyActiveSubSettings(localSub);
			} else {
				applyActiveSubSettings(null);
			}

			if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) {
				// Live Coins Sync
				firebase.database().ref('users/' + uid + '/coins').on('value', function(snap) {
					var val = snap.val();
					if (val !== null && val !== undefined) {
						var coinsNum = parseInt(val, 10);
						localStorage.setItem('shorttv_user_coins', String(coinsNum));
						if (detailCoinsVal) detailCoinsVal.textContent = 'Balance: ' + coinsNum + ' Coins';
						if (hubWalletCoinsPill) hubWalletCoinsPill.textContent = coinsNum + ' Coins';
						if (detailWalletBalanceNum) detailWalletBalanceNum.textContent = coinsNum;
						if (typeof window.syncHeaderCoins === 'function') window.syncHeaderCoins();
					}
				});

				firebase.database().ref('users/' + uid + '/subscription').once('value', function(snap) {
					var sub = snap.val();
					if (sub && (sub.status === 'active' || sub.active === true) && sub.plan_id && sub.plan_id !== 'free' && sub.plan_id !== 'free_tier') {
						applyActiveSubSettings(sub);
					} else {
						applyActiveSubSettings(null);
					}
				});
			}
		} else {
			// Guest / Signed Out State
			applyActiveSubSettings(null);
			localStorage.removeItem('short_is_logged_in');
			localStorage.removeItem('short_user_email');
			localStorage.removeItem('short_user_display_name');
			localStorage.removeItem('short_user_uid');
			localStorage.removeItem('short_user_photo');
			localStorage.removeItem('shorttv_user_avatar');

			if (hubAvatar) hubAvatar.src = defaultAvatar;
			if (detailAvatar) detailAvatar.src = defaultAvatar;
			var mobNavAvatarG = document.getElementById('mobile-nav-account-avatar') || document.querySelector('.mobile-nav-avatar-img');
			if (mobNavAvatarG) mobNavAvatarG.src = defaultAvatar;

			if (hubAccountSub) hubAccountSub.textContent = 'Guest Viewer • Not signed in';
			if (hubSigninBtn) hubSigninBtn.style.display = 'none';

			if (detailName) detailName.textContent = 'Guest Viewer';
			if (detailInputName) detailInputName.value = 'Guest Viewer';
			if (detailInputEmail) detailInputEmail.value = 'Not signed in';
			var guestUid = 'guest_session_' + Date.now();
			if (detailInputUid) detailInputUid.value = guestUid;
			if (detailAuthBadge) {
				detailAuthBadge.textContent = 'Guest Mode';
				detailAuthBadge.style.background = 'rgba(148, 163, 184, 0.15)';
				detailAuthBadge.style.color = '#94a3b8';
			}
			if (uidPill) uidPill.textContent = 'UID: Guest Session';
			if (socialGoogleSub) socialGoogleSub.textContent = 'Not connected';
			if (btnGoogleAuth) {
				btnGoogleAuth.className = 'btn-conn-toggle is-action btn-trigger-google-auth';
				btnGoogleAuth.innerHTML = '<span>Sign In</span>';
			}
			if (btnSecuritySignout) {
				btnSecuritySignout.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.9C6.2 7.4 8.9 5 12 5z"/><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/><path fill="#FBBC05" d="M5.3 14.7c-.2-.7-.4-1.5-.4-2.4s.2-1.7.4-2.4L1.6 7c-.8 1.6-1.3 3.4-1.3 5.3 0 1.9.5 3.7 1.3 5.3l3.7-2.9z"/><path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.4-6.7-5.3L1.6 16C3.5 19.8 7.4 23 12 23z"/></svg> <span>Sign In with Google</span>';
			}
			if (detailPlanEmail) detailPlanEmail.textContent = 'Free Guest Access';
		}

		// Update 4-Stat Strip (Saved, Liked, Rated, Comments)
		updateUserStats(user);

		// Render Dynamic Transaction History
		renderAccountTransactions(user);
	}

	function updateUserStats(user) {
		var uid = (user && user.uid) ? user.uid : (localStorage.getItem('short_user_uid') || '');
		var email = (user && user.email) ? user.email : (localStorage.getItem('short_user_email') || '');
		var displayName = (user && user.displayName) ? user.displayName : (localStorage.getItem('short_user_display_name') || '');

		var statWatchlist = document.getElementById('detail-stat-watchlist');
		var statLikes     = document.getElementById('detail-stat-likes');
		var statRatings   = document.getElementById('detail-stat-ratings');
		var statComments  = document.getElementById('detail-stat-comments');

		// 1. Calculate Local Stats
		var localSavedCount = 0;
		var localLikedCount = 0;
		var localRatedCount = 0;
		var localCommentCount = 0;

		try {
			// Scan localStorage for ratings
			var ratedSet = {};
			var userRatingsMap = JSON.parse(localStorage.getItem('shorttv_user_ratings') || localStorage.getItem('short_ratings') || '{}');
			Object.keys(userRatingsMap).forEach(function(k){ if (userRatingsMap[k]) ratedSet[k] = true; });
			for (var i = 0; i < localStorage.length; i++) {
				var lk = localStorage.key(i);
				if (lk && (lk.startsWith('shorttv_rating_') || lk.startsWith('short_rating_') || lk.startsWith('stv_rating_') || lk.startsWith('rating_'))) {
					var rVal = localStorage.getItem(lk);
					if (rVal && parseInt(rVal, 10) > 0) {
						ratedSet[lk.replace(/^(shorttv_rating_|short_rating_|stv_rating_|rating_)/, '')] = true;
					}
				}
			}
			localRatedCount = Object.keys(ratedSet).length;

			// Scan localStorage for likes
			var likedSet = {};
			var userLikesMap = JSON.parse(localStorage.getItem('shorttv_user_likes') || localStorage.getItem('short_likes') || '{}');
			Object.keys(userLikesMap).forEach(function(k){ if (userLikesMap[k]) likedSet[k] = true; });
			for (var j = 0; j < localStorage.length; j++) {
				var lk2 = localStorage.key(j);
				if (lk2 && (lk2.startsWith('stv_liked_') || lk2.startsWith('short_liked_') || lk2.startsWith('shorttv_liked_') || lk2.startsWith('liked_'))) {
					if (localStorage.getItem(lk2) === '1' || localStorage.getItem(lk2) === 'true') {
						likedSet[lk2.replace(/^(stv_liked_|short_liked_|shorttv_liked_|liked_)/, '')] = true;
					}
				}
			}
			localLikedCount = Object.keys(likedSet).length;

			// Scan localStorage for saved / watchlist
			var savedSet = {};
			var scopedKeys = ['short_watchlist_' + uid, 'short_my_list_' + uid, 'short_watchlist', 'short_my_list', 'stv_watchlist_guest', 'shorttv_my_list', 'short_saved'];
			scopedKeys.forEach(function(sk){
				try {
					var parsed = JSON.parse(localStorage.getItem(sk) || '{}');
					if (Array.isArray(parsed)) {
						parsed.forEach(function(item){ if (item && (item.id || item.post_id)) savedSet[item.id || item.post_id] = true; });
					} else if (parsed && typeof parsed === 'object') {
						Object.keys(parsed).forEach(function(k){ if (parsed[k]) savedSet[k] = true; });
					}
				} catch(e){}
			});
			localSavedCount = Object.keys(savedSet).length;

			// Scan localStorage for comments
			var myComments = JSON.parse(localStorage.getItem('shorttv_user_comments') || localStorage.getItem('short_user_comments') || '[]');
			if (Array.isArray(myComments)) {
				localCommentCount = myComments.length;
			}
		} catch(e) {
			console.warn('Local stats scan error:', e);
		}

		// Initial display with local fallback / existing values
		if (statWatchlist) statWatchlist.textContent = Math.max(parseInt(statWatchlist.textContent, 10) || 0, localSavedCount);
		if (statLikes)     statLikes.textContent = Math.max(parseInt(statLikes.textContent, 10) || 0, localLikedCount);
		if (statRatings)   statRatings.textContent = Math.max(parseInt(statRatings.textContent, 10) || 0, localRatedCount);
		if (statComments)  statComments.textContent = Math.max(parseInt(statComments.textContent, 10) || 0, localCommentCount);

		// 2. Fetch WordPress API User Stats (WordPress comments & ratings)
		if (email || uid) {
			fetch('<?php echo esc_url( rest_url( 'short/v1/user-stats' ) ); ?>?email=' + encodeURIComponent(email) + '&uid=' + encodeURIComponent(uid))
				.then(function(res){ return res.json(); })
				.then(function(data){
					if (data && typeof data.comments === 'number') {
						if (statComments) statComments.textContent = Math.max(parseInt(statComments.textContent, 10) || 0, data.comments, localCommentCount);
					}
				})
				.catch(function(){});
		}

		// 3. Fetch RTDB / Firestore Live Data for logged-in user
		if (uid && typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length) {
			if (firebase.database) {
				var rdb = firebase.database();
				rdb.ref('users/' + uid + '/watchlist').once('value', function(snap) {
					var val = snap.val();
					if (val) {
						var count = Object.keys(val).length;
						if (statWatchlist) statWatchlist.textContent = Math.max(parseInt(statWatchlist.textContent, 10) || 0, count, localSavedCount);
					}
				});
				rdb.ref('users/' + uid + '/likes').once('value', function(snap) {
					var val = snap.val();
					if (val) {
						var count = Object.keys(val).filter(function(k){ return !!val[k]; }).length;
						if (statLikes) statLikes.textContent = Math.max(parseInt(statLikes.textContent, 10) || 0, count, localLikedCount);
					}
				});
				rdb.ref('users/' + uid + '/ratings').once('value', function(snap) {
					var val = snap.val();
					if (val) {
						var count = Object.keys(val).length;
						if (statRatings) statRatings.textContent = Math.max(parseInt(statRatings.textContent, 10) || 0, count, localRatedCount);
					}
				});
				rdb.ref('users/' + uid + '/comments').once('value', function(snap) {
					var val = snap.val();
					if (val) {
						var count = Object.keys(val).length;
						if (statComments) statComments.textContent = Math.max(parseInt(statComments.textContent, 10) || 0, count, localCommentCount);
					}
				});

				// Also check global comments node in RTDB
				rdb.ref('comments').once('value', function(snap) {
					var allComms = snap.val();
					if (allComms && typeof allComms === 'object') {
						var foundCount = 0;
						Object.keys(allComms).forEach(function(sId) {
							var dramaGroup = allComms[sId];
							if (dramaGroup && typeof dramaGroup === 'object') {
								Object.keys(dramaGroup).forEach(function(cId) {
									var item = dramaGroup[cId];
									if (item && (item.uid === uid || item.userId === uid || (email && (item.author_email === email || item.email === email)) || (displayName && item.userName === displayName))) {
										foundCount++;
									}
								});
							}
						});
						if (foundCount > 0 && statComments) {
							statComments.textContent = Math.max(parseInt(statComments.textContent, 10) || 0, foundCount, localCommentCount);
						}
					}
				});
			}

			if (firebase.firestore) {
				var fdb = firebase.firestore();
				fdb.collection('users').doc(uid).collection('my_list').get().then(function(s){
					if (s && s.size > 0 && statWatchlist) statWatchlist.textContent = Math.max(parseInt(statWatchlist.textContent, 10) || 0, s.size, localSavedCount);
				}).catch(function(){});
				fdb.collection('users').doc(uid).collection('likes').get().then(function(s){
					if (s && s.size > 0 && statLikes) statLikes.textContent = Math.max(parseInt(statLikes.textContent, 10) || 0, s.size, localLikedCount);
				}).catch(function(){});
				fdb.collection('users').doc(uid).collection('ratings').get().then(function(s){
					if (s && s.size > 0 && statRatings) statRatings.textContent = Math.max(parseInt(statRatings.textContent, 10) || 0, s.size, localRatedCount);
				}).catch(function(){});
				fdb.collection('users').doc(uid).collection('comments').get().then(function(s){
					if (s && s.size > 0 && statComments) statComments.textContent = Math.max(parseInt(statComments.textContent, 10) || 0, s.size, localCommentCount);
				}).catch(function(){});
			}
		}
	}

	function applyActiveSubSettings(sub) {
		var hubBillingSub = document.getElementById('hub-billing-sub');
		var detailPlanBadge = document.getElementById('detail-plan-badge');
		var detailPlanName = document.getElementById('detail-plan-name');
		var detailPlanPrice = document.getElementById('detail-plan-price');
		var detailPlanPeriod = document.getElementById('detail-plan-period') || document.querySelector('.billing-price-period');
		var detailPlanPerks = document.getElementById('detail-plan-perks');
		var cancelSubBtn = document.getElementById('detail-btn-cancel-sub');

		var isVipActive = sub && (sub.status === 'active' || sub.active === true) && sub.plan_id && sub.plan_id !== 'free' && sub.plan_id !== 'free_tier';

		if (isVipActive) {
			var planTitle = sub.plan_name || (sub.plan_id ? sub.plan_id.toUpperCase() + ' VIP' : 'VIP Pass');
			var tierName = (sub.tier || sub.plan_id || 'VIP').toUpperCase();
			var planId = (sub.plan_id || '').toLowerCase();
			var planNameLower = (sub.plan_name || '').toLowerCase();

			var planPrice = sub.price;
			var planPeriod = sub.period;

			if (!planPrice || planPrice === '$0.00' || planPrice === '$0') {
				if (planId.indexOf('week') !== -1 || planNameLower.indexOf('week') !== -1 || planId === 'basic') {
					planPrice = '$4.99';
					planPeriod = '/ weekly';
				} else if (planId.indexOf('year') !== -1 || planId.indexOf('annu') !== -1 || planNameLower.indexOf('year') !== -1 || planNameLower.indexOf('annu') !== -1 || planId === 'premium') {
					planPrice = '$49.99';
					planPeriod = '/ yearly';
				} else {
					planPrice = '$14.99';
					planPeriod = '/ monthly';
				}
			}

			if (hubBillingSub) hubBillingSub.textContent = planTitle + ' (Active) • ' + tierName;
			if (detailPlanBadge) detailPlanBadge.textContent = tierName;
			if (detailPlanName) detailPlanName.textContent = planTitle;
			if (detailPlanPrice) detailPlanPrice.textContent = planPrice;
			if (detailPlanPeriod) detailPlanPeriod.textContent = planPeriod;
			if (detailPlanPerks) detailPlanPerks.textContent = (sub.quality || '1080p Full HD') + ' • 100% Ad-Free • All Dramas Unlocked';
			if (cancelSubBtn) {
				cancelSubBtn.classList.add('is-visible');
				cancelSubBtn.style.setProperty('display', 'inline-flex', 'important');
				cancelSubBtn.onclick = async function() {
					if (window.confirm('Are you sure you want to cancel your ' + planTitle + ' subscription?')) {
						localStorage.removeItem('short_subscription');
						localStorage.removeItem('short_sub_tier');
						localStorage.removeItem('short_is_vip');
						document.cookie = 'short_sub_tier=; path=/; max-age=0; SameSite=Lax';
						if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser && firebase.database) {
							var u = firebase.auth().currentUser;
							firebase.database().ref('users/' + u.uid + '/subscription').remove();
						}
						applyActiveSubSettings(null);
						alert('Your subscription has been cancelled.');
					}
				};
			}
		} else {
			if (hubBillingSub) hubBillingSub.textContent = 'Free Member';
			if (detailPlanBadge) detailPlanBadge.textContent = 'Free Tier';
			if (detailPlanName) detailPlanName.textContent = 'Free Member';
			if (detailPlanPrice) detailPlanPrice.textContent = '$0.00';
			if (detailPlanPeriod) detailPlanPeriod.textContent = '/ free';
			if (detailPlanPerks) detailPlanPerks.textContent = 'Standard Drama Access • Ad-supported Streaming • Free Starter Coins';
			if (cancelSubBtn) {
				cancelSubBtn.classList.remove('is-visible');
				cancelSubBtn.style.setProperty('display', 'none', 'important');
				cancelSubBtn.onclick = null;
			}
		}
	}

	// Sign in & Sign out handlers
	async function doSignOut() {
		try {
			if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
				await firebase.auth().signOut();
			}
		} catch(e) {
			console.warn('Firebase signout:', e);
		}
		// Clear all authentication & profile tokens
		localStorage.removeItem('short_is_logged_in');
		localStorage.removeItem('short_user_email');
		localStorage.removeItem('short_user_display_name');
		localStorage.removeItem('short_user_uid');
		localStorage.removeItem('short_user_photo');
		localStorage.removeItem('shorttv_user_avatar');
		localStorage.removeItem('short_active_avatar');
		localStorage.removeItem('short_active_profile');
		localStorage.removeItem('short_active_profile_id');
		localStorage.removeItem('short_active_profile_name');
		localStorage.removeItem('short_subscription');
		localStorage.removeItem('short_sub_tier');
		document.cookie = 'short_sub_tier=; path=/; max-age=0; SameSite=Lax';

		syncSettingsState(null);
		window.location.href = '<?php echo esc_url( home_url( '/' ) ); ?>';
	}

	async function triggerGoogleSignIn() {
		if (typeof firebase === 'undefined' || !firebase.auth) {
			alert('Firebase authentication initializing, please try again in a moment...');
			return;
		}
		try {
			var provider = new firebase.auth.GoogleAuthProvider();
			provider.setCustomParameters({ prompt: 'select_account' });
			var result = await firebase.auth().signInWithPopup(provider);
			if (result && result.user) {
				syncSettingsState(result.user);
				window.location.reload();
			}
		} catch(err) {
			if (err.code !== 'auth/popup-closed-by-user') {
				alert('Sign-in error: ' + err.message);
			}
		}
	}


	var btnHubSignin = document.getElementById('btn-hub-signin');
	if (btnHubSignin) btnHubSignin.addEventListener('click', triggerGoogleSignIn);

	var btnSecSignout = document.getElementById('btn-security-signout');
	if (btnSecSignout) {
		btnSecSignout.addEventListener('click', function() {
			var isLoggedIn = (localStorage.getItem('short_is_logged_in') === '1') || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser);
			if (isLoggedIn) {
				doSignOut();
			} else {
				triggerGoogleSignIn();
			}
		});
	}

	var btnGoogleAuth = document.getElementById('btn-google-auth-sync');
	if (btnGoogleAuth) {
		btnGoogleAuth.addEventListener('click', function() {
			var isLoggedIn = (localStorage.getItem('short_is_logged_in') === '1') || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser);
			if (!isLoggedIn) {
				triggerGoogleSignIn();
			} else {
				alert('Account is already connected with Google.');
			}
		});
	}

	document.addEventListener('click', function(e) {
		var t = e.target.closest('.btn-trigger-google-auth');
		if (t && t !== btnGoogleAuth) {
			triggerGoogleSignIn();
		}
	});

	// Copy UID
	var btnCopyUid = document.getElementById('btn-copy-account-uid');
	var btnCopyUidInline = document.getElementById('btn-copy-account-uid-inline');
	
	function copyUidAction(btnEl) {
		var uidInput = document.getElementById('detail-input-uid');
		var val = uidInput ? uidInput.value : '';
		if (val) {
			navigator.clipboard.writeText(val).then(function() {
				if (btnEl) {
					var orig = btnEl.textContent;
					btnEl.textContent = '✓ Copied';
					setTimeout(function(){ btnEl.textContent = orig; }, 1500);
				}
			});
		}
	}

	if (btnCopyUid) {
		btnCopyUid.addEventListener('click', function() {
			copyUidAction(btnCopyUid);
		});
	}
	if (btnCopyUidInline) {
		btnCopyUidInline.addEventListener('click', function() {
			copyUidAction(btnCopyUidInline);
		});
	}

	// Clear Playback Cache
	var btnClearCache = document.getElementById('btn-clear-playback-cache');
	if (btnClearCache) {
		btnClearCache.addEventListener('click', function() {
			var count = 0;
			for (var i = localStorage.length - 1; i >= 0; i--) {
				var key = localStorage.key(i);
				if (key && (key.startsWith('shorttv_video_') || key.startsWith('stv_prog_') || key.startsWith('short_cache_'))) {
					localStorage.removeItem(key);
					count++;
				}
			}
			alert('✓ Cache Cleared: ' + count + ' items removed from local memory.');
		});
	}

	// Reset Watch History
	var btnResetHistory = document.getElementById('btn-reset-watch-history');
	if (btnResetHistory) {
		btnResetHistory.addEventListener('click', function() {
			if (!confirm('Are you sure you want to clear your local continue watching history?')) return;
			for (var i = localStorage.length - 1; i >= 0; i--) {
				var key = localStorage.key(i);
				if (key && (key.startsWith('short_history_') || key === 'short_watch_history' || key === 'stv_history')) {
					localStorage.removeItem(key);
				}
			}
			alert('✓ Watch history reset.');
		});
	}

	// Photo URL Prompt
	var btnChangePhoto = document.getElementById('btn-detail-change-photo');
	if (btnChangePhoto) {
		btnChangePhoto.addEventListener('click', function() {
			var cur = localStorage.getItem('short_user_photo') || '';
			var url = prompt('Enter custom profile picture URL:', cur);
			if (url !== null) {
				url = url.trim();
				if (url) {
					localStorage.setItem('short_user_photo', url);
					localStorage.setItem('shorttv_user_avatar', url);
					var dAv = document.getElementById('detail-account-avatar');
					var hAv = document.getElementById('settings-hub-avatar');
					if (dAv) dAv.src = url;
					if (hAv) hAv.src = url;
				}
			}
		});
	}

	// Save Profile Details
	var btnSaveProfile = document.getElementById('btn-save-account-info');
	var toastSave = document.getElementById('account-info-saved-toast');
	if (btnSaveProfile) {
		btnSaveProfile.addEventListener('click', function() {
			var nameInput = document.getElementById('detail-input-name');
			if (nameInput && nameInput.value.trim()) {
				var newName = nameInput.value.trim();
				localStorage.setItem('short_user_display_name', newName);
				var dName = document.getElementById('detail-display-name');
				var hSub = document.getElementById('hub-account-sub');
				if (dName) dName.textContent = newName;
				if (hSub) hSub.textContent = newName + ' • ' + (localStorage.getItem('short_user_email') || 'Guest');
				if (toastSave) {
					toastSave.style.opacity = '1';
					setTimeout(function(){ toastSave.style.opacity = '0'; }, 2000);
				}
			}
		});
	}

	// Quick Cards Jump Links & Transaction View Buttons
	document.addEventListener('click', function(e) {
		var jumpBtn = e.target.closest('[data-jump-view], .btn-open-transactions-view');
		if (jumpBtn) {
			e.preventDefault();
			var targetView = jumpBtn.getAttribute('data-jump-view') || 'transactions';
			if (typeof window.switchSettingsView === 'function') {
				window.switchSettingsView(targetView);
			} else if (typeof switchSettingsView === 'function') {
				switchSettingsView(targetView);
			}
		}
	});

	// Load dynamic user transactions from Firebase / LocalStorage
	function renderAccountTransactions(user) {
		var txListEl = document.getElementById('account-recent-tx-list');
		var tableBody = document.getElementById('saas-tx-table-body');
		var mobileListEl = document.getElementById('saas-mobile-tx-list');
		if (!txListEl && !tableBody && !mobileListEl) return;

		var currentUid = (user && user.uid) ? user.uid : localStorage.getItem('short_user_uid');

		function showTxReceiptModal(item) {
			var modal = document.getElementById('modal-transaction-receipt');
			if (!modal) return;

			var dateStr = item.ts ? new Date(item.ts).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Recent';
			var amount = parseInt(item.amount || 0, 10);
			var isVip = item.type === 'vip' || (item.reason && item.reason.toLowerCase().indexOf('vip') !== -1 && !amount);
			var isCredit = isVip || amount >= 0;

			var currentEmail = localStorage.getItem('short_user_email') || (user && user.email) || 'Registered Customer';
			var orderCode = item.order_id || ('TX-' + Math.abs(item.ts || Date.now()).toString().slice(-6));
			var reasonText = item.reason || 'Lemon Squeezy Order';

			// Extract dollar price if present in reason string e.g. "Starter Pack (300 Coins) ($1.99)"
			var priceMatch = reasonText.match(/\(\$([0-9.]+)\)/);
			var priceStr = priceMatch ? ('$' + priceMatch[1]) : (isVip ? '$9.99' : (amount ? '$' + (Math.abs(amount) * 0.01).toFixed(2) : '$0.00'));
			var cleanedItemName = reasonText.replace(/Lemon Squeezy\s*[•-]\s*/i, '').trim();

			// Salutation & Header Elements
			var salutationEl = document.getElementById('rcpt-customer-salutation');
			if (salutationEl) salutationEl.textContent = currentEmail;

			var summaryDateEl = document.getElementById('rcpt-summary-date');
			if (summaryDateEl) summaryDateEl.textContent = dateStr;

			var summaryModeEl = document.getElementById('rcpt-summary-mode');
			if (summaryModeEl) {
				summaryModeEl.textContent = (reasonText.toLowerCase().indexOf('lemon') !== -1) ? 'Lemon Squeezy (Card / PayPal)' : ((reasonText.toLowerCase().indexOf('reward') !== -1) ? 'System Free Credit' : 'Digital Gateway');
			}

			var summaryRefEl = document.getElementById('rcpt-summary-ref');
			if (summaryRefEl) summaryRefEl.textContent = orderCode;

			// Table Elements
			var tableItemEl = document.getElementById('rcpt-table-item');
			if (tableItemEl) tableItemEl.textContent = cleanedItemName;

			var tableCoinsEl = document.getElementById('rcpt-table-coins');
			if (tableCoinsEl) {
				tableCoinsEl.textContent = isVip ? '👑 VIP PASS' : (amount ? ((amount > 0 ? '+' : '') + amount + ' Coins') : 'Completed');
				tableCoinsEl.style.color = isVip ? '#c084fc' : (isCredit ? '#4ade80' : '#f87171');
			}

			var tablePriceEl = document.getElementById('rcpt-table-price');
			if (tablePriceEl) tablePriceEl.textContent = priceStr;

			var tableStatusCell = document.getElementById('rcpt-table-status-cell');
			if (tableStatusCell) {
				var statusText = item.status || 'Completed';
				var isSuccess = statusText.toLowerCase() === 'completed';
				var bg = isSuccess ? '#dcfce7' : '#fee2e2';
				var border = isSuccess ? '#86efac' : '#fca5a5';
				var color = isSuccess ? '#15803d' : '#b91c1c';
				tableStatusCell.innerHTML = '<svg width="92" height="24" viewBox="0 0 92 24" style="display:inline-block; vertical-align:middle;">' +
					'<rect width="92" height="24" rx="12" fill="' + bg + '" stroke="' + border + '" stroke-width="1"/>' +
					'<text x="46" y="16" text-anchor="middle" font-size="11.5" font-weight="800" fill="' + color + '" font-family="-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif">' + statusText + '</text>' +
				'</svg>';
			}

			modal.style.display = 'flex';
		}

		function printReceiptDirectly() {
			var printableContent = document.getElementById('tx-receipt-printable-area');
			if (!printableContent) return;

			var printFrame = document.getElementById('tx-receipt-print-frame');
			if (!printFrame) {
				printFrame = document.createElement('iframe');
				printFrame.id = 'tx-receipt-print-frame';
				printFrame.style.position = 'fixed';
				printFrame.style.right = '0';
				printFrame.style.bottom = '0';
				printFrame.style.width = '0';
				printFrame.style.height = '0';
				printFrame.style.border = '0';
				printFrame.style.visibility = 'hidden';
				document.body.appendChild(printFrame);
			}

			var frameDoc = printFrame.contentWindow || printFrame.contentDocument;
			if (frameDoc.document) frameDoc = frameDoc.document;

			var html = '<!DOCTYPE html>' +
				'<html><head><meta charset="utf-8"><title>ShortTV - Official Receipt</title>' +
				'<style>' +
				'@page { margin: 12mm 15mm; size: letter portrait; }' +
				'* { box-sizing: border-box; }' +
				'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; margin: 0; padding: 0; color: #0f172a !important; background: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
				'.rcpt-doc-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 22px; padding-bottom: 18px; border-bottom: 2px solid #e2e8f0; }' +
				'.rcpt-doc-company-info { text-align: right; font-size: 11.5px; color: #334155 !important; line-height: 1.5; border-right: 3.5px solid #d97706; padding-right: 12px; }' +
				'.rcpt-text-primary { font-weight: 800; color: #0f172a !important; font-size: 13px; }' +
				'.rcpt-text-sub { color: #475569 !important; }' +
				'#rcpt-support-domain { color: #0284c7 !important; font-weight: 700; }' +
				'.rcpt-salutation-block { margin-bottom: 18px; }' +
				'.rcpt-text-title { font-size: 14px; font-weight: 800; color: #0f172a !important; margin-bottom: 4px; }' +
				'#rcpt-customer-salutation { color: #0284c7 !important; font-weight: 700; }' +
				'.rcpt-summary-heading { margin-bottom: 14px; border-left: 3.5px solid #d97706; padding-left: 10px; }' +
				'.rcpt-summary-heading h4 { margin: 0; font-size: 14px; font-weight: 900; color: #d97706 !important; letter-spacing: 0.8px; text-transform: uppercase; }' +
				'.rcpt-summary-row { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 6px; font-size: 12px; }' +
				'.rcpt-label { color: #64748b !important; font-weight: 700; }' +
				'.rcpt-data { color: #0f172a !important; font-weight: 700; }' +
				'#rcpt-summary-ref { color: #0284c7 !important; font-weight: 800; font-family: monospace; }' +
				'.rcpt-table-wrap { border-radius: 8px; overflow: hidden; border: 1.5px solid #cbd5e1; margin-bottom: 18px; }' +
				'table { width: 100%; border-collapse: collapse; text-align: left; font-size: 12.5px; }' +
				'thead tr { background: #f59e0b !important; color: #ffffff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
				'th { padding: 10px 14px; font-weight: 800; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px; border-right: 1px solid rgba(255,255,255,0.3); color: #ffffff !important; }' +
				'th:last-child { border-right: none; }' +
				'tbody tr { background: #f8fafc !important; }' +
				'td { padding: 12px 14px; border-right: 1px solid #e2e8f0; color: #0f172a !important; font-weight: 600; }' +
				'td:last-child { border-right: none; }' +
				'#rcpt-table-coins { color: #15803d !important; font-weight: 800; }' +
				'#rcpt-table-status { display: inline-block; font-size: 11px; font-weight: 800; padding: 2px 10px 4px 10px; line-height: 14px; border-radius: 999px; background: #dcfce7 !important; color: #15803d !important; border: 1px solid #86efac; box-sizing: border-box; vertical-align: middle; text-align: center; -webkit-print-color-adjust: exact; print-color-adjust: exact; }' +
				'.rcpt-disclaimer-box { margin-bottom: 16px; font-size: 11.5px; color: #475569 !important; line-height: 1.5; }' +
				'.rcpt-disclaimer-box a { color: #d97706 !important; text-decoration: none; font-weight: 700; }' +
				'.rcpt-doc-footer { padding-top: 14px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; font-size: 10.5px; color: #64748b !important; }' +
				'</style></head><body>' +
				printableContent.innerHTML +
				'</body></html>';

			frameDoc.open();
			frameDoc.write(html);
			frameDoc.close();

			setTimeout(function() {
				printFrame.contentWindow.focus();
				printFrame.contentWindow.print();
			}, 350);
		}

		function downloadReceiptAsImage() {
			var printableContent = document.getElementById('tx-receipt-printable-area');
			if (!printableContent) return;

			var btn = document.getElementById('btn-download-tx-receipt-img');
			var originalBtnHtml = btn ? btn.innerHTML : '';
			if (btn) {
				btn.innerHTML = '<span>Generating Image...</span>';
				btn.style.pointerEvents = 'none';
				btn.style.opacity = '0.8';
			}

			function doCapture() {
				var orderRefEl = document.getElementById('rcpt-summary-ref');
				var orderRef = orderRefEl ? orderRefEl.innerText.trim().replace(/[^a-zA-Z0-9_-]/g, '') : 'receipt';
				var fileName = 'ShortTV-Receipt-' + (orderRef || 'invoice') + '.png';

				window.html2canvas(printableContent, {
					scale: 2,
					useCORS: true,
					allowTaint: true,
					backgroundColor: '#13161f',
					logging: false
				}).then(function(canvas) {
					var link = document.createElement('a');
					link.download = fileName;
					link.href = canvas.toDataURL('image/png');
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				}).catch(function(err) {
					console.error('Failed to generate receipt image:', err);
					alert('Unable to generate receipt image. Please use Print / Save PDF.');
				}).finally(function() {
					if (btn) {
						btn.innerHTML = originalBtnHtml;
						btn.style.pointerEvents = 'auto';
						btn.style.opacity = '1';
					}
				});
			}

			if (window.html2canvas) {
				doCapture();
			} else {
				var script = document.createElement('script');
				script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
				script.onload = function() {
					doCapture();
				};
				script.onerror = function() {
					if (btn) {
						btn.innerHTML = originalBtnHtml;
						btn.style.pointerEvents = 'auto';
						btn.style.opacity = '1';
					}
					alert('Failed to load image library. Please use Print / Save PDF.');
				};
				document.head.appendChild(script);
			}
		}

		// Receipt Modal Close Handlers
		var modalReceipt = document.getElementById('modal-transaction-receipt');
		var btnCloseRcpt = document.getElementById('btn-close-tx-receipt');
		var btnCloseRcpt2 = document.getElementById('btn-close-tx-receipt-secondary');
		var btnPrintRcpt = document.getElementById('btn-print-tx-receipt');
		var btnDownloadImgRcpt = document.getElementById('btn-download-tx-receipt-img');

		if (btnCloseRcpt) btnCloseRcpt.onclick = function() { if (modalReceipt) modalReceipt.style.display = 'none'; };
		if (btnCloseRcpt2) btnCloseRcpt2.onclick = function() { if (modalReceipt) modalReceipt.style.display = 'none'; };
		if (modalReceipt) {
			modalReceipt.onclick = function(e) {
				if (e.target === modalReceipt) modalReceipt.style.display = 'none';
			};
		}
		if (btnPrintRcpt) {
			btnPrintRcpt.onclick = function() {
				printReceiptDirectly();
			};
		}
		if (btnDownloadImgRcpt) {
			btnDownloadImgRcpt.onclick = function() {
				downloadReceiptAsImage();
			};
		}

		function isPurchaseItem(item) {
			if (!item) return false;
			var reason = (item.reason || '').toLowerCase();
			var type = (item.type || '').toLowerCase();
			var isVip = type === 'vip' || reason.indexOf('vip') !== -1;
			var isReward = reason.indexOf('reward') !== -1 || reason.indexOf('checkin') !== -1 || reason.indexOf('check-in') !== -1 || reason.indexOf('bonus') !== -1 || reason.indexOf('welcome') !== -1 || reason.indexOf('ad') !== -1;
			var isUnlock = reason.indexOf('unlock') !== -1 || reason.indexOf('episode') !== -1 || (item.amount && item.amount < 0);
			if (isReward || isUnlock) return false;
			if (isVip || type === 'coins' || reason.indexOf('pack') !== -1 || reason.indexOf('top-up') !== -1 || reason.indexOf('topup') !== -1 || reason.indexOf('starter') !== -1 || reason.indexOf('lemon') !== -1 || reason.indexOf('$') !== -1) {
				return true;
			}
			return false;
		}

		function formatCompactDate(ts) {
			if (!ts) return 'Recent';
			var d = new Date(ts);
			var now = new Date();
			var isToday = d.toDateString() === now.toDateString();
			var yesterday = new Date(now);
			yesterday.setDate(yesterday.getDate() - 1);
			var isYesterday = d.toDateString() === yesterday.toDateString();
			var timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
			if (isToday) return 'Today · ' + timeStr;
			if (isYesterday) return 'Yesterday · ' + timeStr;
			return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' · ' + timeStr;
		}

		function formatCleanTitle(reasonText, type) {
			if (!reasonText) return 'Transaction';
			var t = reasonText.replace(/Lemon Squeezy\s*[•-]\s*/i, '').replace(/\(\$[0-9.]+\)/g, '').trim();
			if (/check-?in/i.test(t)) {
				var dayMatch = t.match(/day\s*(\d+)/i);
				return dayMatch ? ('Daily Check-in (Day ' + dayMatch[1] + ')') : 'Daily Check-in';
			}
			if (/welcome\s*bonus/i.test(t)) return 'Welcome Bonus';
			if (/vip\s*pass|vip\s*membership/i.test(t)) return 'VIP Pass';
			if (/unlock/i.test(t)) return t.length > 24 ? (t.substring(0, 22) + '…') : t;
			if (/starter/i.test(t)) return 'Starter Coin Pack';
			if (/popular/i.test(t)) return 'Popular Coin Pack';
			if (/mega/i.test(t)) return 'Mega Coin Pack';
			if (t.length > 24) return t.substring(0, 22) + '…';
			return t;
		}

		function buildTxHtml(item, index, isLast) {
			var amount = parseInt(item.amount || 0, 10);
			var isVip = item.type === 'vip' || (item.reason && item.reason.toLowerCase().indexOf('vip') !== -1 && !amount);
			var isCredit = isVip || amount >= 0;
			var reasonText = item.reason || 'Transaction';
			var isPurchase = isPurchaseItem(item);

			var dateFormatted = formatCompactDate(item.ts);
			var cleanTitle = formatCleanTitle(reasonText, item.type);

			// Icons & Branding
			var isReward = /check-?in|reward|bonus|welcome/i.test(reasonText);
			var avatarBg = isVip ? 'rgba(168,85,247,0.14)' : (isReward ? 'rgba(245,158,11,0.14)' : (isPurchase ? 'rgba(34,197,94,0.14)' : 'rgba(239,68,68,0.14)'));
			var avatarColor = isVip ? '#c084fc' : (isReward ? '#fbbf24' : (isPurchase ? '#4ade80' : '#f87171'));

			var avatarSvg = isVip
				? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'
				: (isReward
					? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>'
					: (isPurchase
						? '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a2.5 2.5 0 0 0-5 0c0 2 5 2 5 5a2.5 2.5 0 0 1-5 0"/></svg>'
						: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>'));

			// Price & Subtitle
			var priceMatch = reasonText.match(/\(\$([0-9.]+)\)/);
			var subLabel = priceMatch ? ('$' + priceMatch[1]) : (isVip ? '$9.99' : (isPurchase ? ('$' + (Math.abs(amount) * 0.01).toFixed(2)) : (isReward ? 'Free Reward' : 'Spent')));
			var amountText = isVip ? '👑 VIP PASS' : (amount ? ((amount > 0 ? '+' : '') + amount + ' Coins') : 'Completed');
			var amountColor = isVip ? '#c084fc' : (isCredit ? '#4ade80' : '#f87171');

			var receiptTag = isPurchase ? '<span style="font-size:10px; font-weight:700; color:#38bdf8; background:rgba(56,189,248,0.12); border:1px solid rgba(56,189,248,0.22); padding:1px 5px; border-radius:4px; margin-left:4px; flex-shrink:0;">Receipt 📄</span>' : '';

			var borderStyle = isLast ? 'border-bottom:none;' : 'border-bottom:1px solid rgba(255,255,255,0.05);';

			return '<div class="account-tx-row' + (isPurchase ? ' has-receipt' : '') + '" ' + (isPurchase ? ('data-tx-index="' + index + '"') : '') + ' style="display:flex; align-items:center; justify-content:space-between; padding:11px 14px; ' + borderStyle + ' transition:background 0.15s ease;' + (isPurchase ? ' cursor:pointer;' : '') + '">' +
				'<div style="display:flex; align-items:center; gap:11px; min-width:0; flex:1 1 auto; margin-right:10px;">' +
					'<div style="width:32px; height:32px; min-width:32px; border-radius:8px; background:' + avatarBg + '; color:' + avatarColor + '; display:flex; align-items:center; justify-content:center; flex-shrink:0;">' +
						avatarSvg +
					'</div>' +
					'<div style="min-width:0; flex:1 1 auto; overflow:hidden;">' +
						'<div style="font-size:13px; font-weight:700; color:#ffffff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:flex; align-items:center; gap:4px;" title="' + reasonText + '">' +
							'<span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + cleanTitle + '</span>' + receiptTag +
						'</div>' +
						'<div style="font-size:11px; color:#64748b; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + dateFormatted + '</div>' +
					'</div>' +
				'</div>' +
				'<div style="text-align:right; flex-shrink:0;">' +
					'<div style="font-size:13.5px; font-weight:800; color:' + amountColor + '; letter-spacing:-0.2px;">' + amountText + '</div>' +
					'<div style="font-size:10.5px; color:#64748b; margin-top:1px;">' + subLabel + '</div>' +
				'</div>' +
			'</div>';
		}

		function buildSaasTableRow(item, index) {
			var dateObj = item.ts ? new Date(item.ts) : new Date();
			var dateDateStr = dateObj.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
			var dateTimeStr = dateObj.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
			
			var amount = parseInt(item.amount || 0, 10);
			var isVip = item.type === 'vip' || (item.reason && item.reason.toLowerCase().indexOf('vip') !== -1 && !amount);
			var isCredit = isVip || amount >= 0;
			var reasonText = item.reason || 'Transaction';
			var isPurchase = isPurchaseItem(item);

			// Icons & Branding
			var isLemon = isPurchase;
			var isReward = reasonText.toLowerCase().indexOf('checkin') !== -1 || reasonText.toLowerCase().indexOf('check-in') !== -1 || reasonText.toLowerCase().indexOf('reward') !== -1 || reasonText.toLowerCase().indexOf('bonus') !== -1 || reasonText.toLowerCase().indexOf('welcome') !== -1;

			var avatarBg = isVip ? 'rgba(168,85,247,0.15)' : (isReward ? 'rgba(245,158,11,0.15)' : (isLemon ? 'rgba(234,179,8,0.15)' : 'rgba(239,68,68,0.15)'));
			var avatarColor = isVip ? '#c084fc' : (isReward ? '#fbbf24' : (isLemon ? '#eab308' : '#f87171'));
			
			var avatarSvg = isVip
				? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'
				: (isReward
					? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>'
					: (isLemon
						? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a2.5 2.5 0 0 0-5 0c0 2 5 2 5 5a2.5 2.5 0 0 1-5 0"/></svg>'
						: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>'));

			var subCategory = isVip ? 'VIP Membership' : (isReward ? 'Daily Bonus' : (isLemon ? 'Payment Gateway' : 'Episode Unlock'));
			var cleanedName = reasonText.replace(/Lemon Squeezy\s*[•-]\s*/i, '').trim();

			var orderCode = item.order_id || ('TX-' + Math.abs(((item.ts || Date.now()) % 89999) + 10000));
			var orderBadge = '<span style="font-family:monospace; font-size:11.5px; font-weight:700; color:#38bdf8; background:rgba(56,189,248,0.1); border:1px solid rgba(56,189,248,0.25); padding:3px 8px; border-radius:6px; white-space:nowrap; display:inline-block;">' + orderCode + '</span>';

			// Amount & Price subtext
			var priceMatch = reasonText.match(/\(\$([0-9.]+)\)/);
			var priceStr = priceMatch ? ('$' + priceMatch[1]) : (isVip ? '$9.99' : (isPurchase ? ('$' + (Math.abs(amount) * 0.01).toFixed(2)) : 'Free'));
			var amountText = isVip ? '👑 VIP PASS' : (amount ? ((amount > 0 ? '+' : '') + amount + ' Coins') : 'Completed');
			var amountColor = isVip ? '#c084fc' : (isCredit ? '#4ade80' : '#f87171');

			// Status SVG Pill
			var statusText = item.status || 'Completed';
			var isSuccess = statusText.toLowerCase() === 'completed' || statusText.toLowerCase() === 'success';
			var bg = isSuccess ? '#dcfce7' : '#fee2e2';
			var border = isSuccess ? '#86efac' : '#fca5a5';
			var color = isSuccess ? '#15803d' : '#b91c1c';
			var statusSvg = '<svg width="86" height="22" viewBox="0 0 86 22" style="display:inline-block; vertical-align:middle; flex-shrink:0;">' +
				'<rect width="86" height="22" rx="11" fill="' + bg + '" stroke="' + border + '" stroke-width="1"/>' +
				'<text x="43" y="15" text-anchor="middle" font-size="11" font-weight="800" fill="' + color + '" font-family="-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif">' + statusText + '</text>' +
			'</svg>';

			var actionBtn = isPurchase ? ('<button type="button" class="saas-btn-details" data-tx-index="' + index + '">Receipt</button>') : '<span style="font-size:11.5px; color:#64748b; font-weight:600;">—</span>';
			var rowClass = 'saas-row' + (isPurchase ? ' has-receipt' : '');

			return '<tr class="' + rowClass + '" ' + (isPurchase ? ('data-tx-index="' + index + '"') : '') + ' style="' + (isPurchase ? 'cursor:pointer;' : 'cursor:default;') + '">' +
				'<td style="padding:14px 18px; vertical-align:middle; max-width:240px; width:30%;">' +
					'<div style="display:flex; align-items:center; gap:12px; min-width:0;">' +
						'<div style="width:38px; height:38px; border-radius:10px; background:' + avatarBg + '; color:' + avatarColor + '; display:flex; align-items:center; justify-content:center; flex-shrink:0;">' +
							avatarSvg +
						'</div>' +
						'<div style="min-width:0; flex:1; overflow:hidden;">' +
							'<div style="font-size:13.5px; font-weight:700; color:#ffffff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + cleanedName + '">' + cleanedName + '</div>' +
							'<div style="font-size:11.5px; color:#94a3b8; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + subCategory + '</div>' +
						'</div>' +
					'</div>' +
				'</td>' +
				'<td style="padding:14px 16px; vertical-align:middle; white-space:nowrap; width:18%;">' +
					'<div style="font-size:13px; font-weight:700; color:#ffffff; white-space:nowrap;">' + dateDateStr + '</div>' +
					'<div style="font-size:11.5px; color:#94a3b8; margin-top:2px; white-space:nowrap;">at ' + dateTimeStr + '</div>' +
				'</td>' +
				'<td style="padding:14px 16px; vertical-align:middle; white-space:nowrap; width:15%;">' +
					orderBadge +
				'</td>' +
				'<td style="padding:14px 16px; vertical-align:middle; text-align:right; white-space:nowrap; width:15%;">' +
					'<div style="font-size:14px; font-weight:800; color:' + amountColor + '; white-space:nowrap;">' + amountText + '</div>' +
					'<div style="font-size:11.5px; color:#94a3b8; margin-top:2px; white-space:nowrap;">' + priceStr + '</div>' +
				'</td>' +
				'<td style="padding:14px 16px; vertical-align:middle; text-align:center; white-space:nowrap; width:12%;">' +
					statusSvg +
				'</td>' +
				'<td style="padding:14px 18px; vertical-align:middle; text-align:right; white-space:nowrap; width:10%;">' +
					actionBtn +
				'</td>' +
			'</tr>';
		}

		function buildSaasMobileCard(item, index) {
			var dateObj = item.ts ? new Date(item.ts) : new Date();
			var dateDateStr = dateObj.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
			var dateTimeStr = dateObj.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
			
			var amount = parseInt(item.amount || 0, 10);
			var isVip = item.type === 'vip' || (item.reason && item.reason.toLowerCase().indexOf('vip') !== -1 && !amount);
			var isCredit = isVip || amount >= 0;
			var reasonText = item.reason || 'Transaction';
			var isPurchase = isPurchaseItem(item);

			// Icons & Branding
			var isLemon = isPurchase;
			var isReward = reasonText.toLowerCase().indexOf('checkin') !== -1 || reasonText.toLowerCase().indexOf('check-in') !== -1 || reasonText.toLowerCase().indexOf('reward') !== -1 || reasonText.toLowerCase().indexOf('bonus') !== -1 || reasonText.toLowerCase().indexOf('welcome') !== -1;

			var avatarBg = isVip ? 'rgba(168,85,247,0.15)' : (isReward ? 'rgba(245,158,11,0.15)' : (isLemon ? 'rgba(234,179,8,0.15)' : 'rgba(239,68,68,0.15)'));
			var avatarColor = isVip ? '#c084fc' : (isReward ? '#fbbf24' : (isLemon ? '#eab308' : '#f87171'));
			
			var avatarSvg = isVip
				? '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>'
				: (isReward
					? '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>'
					: (isLemon
						? '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a2.5 2.5 0 0 0-5 0c0 2 5 2 5 5a2.5 2.5 0 0 1-5 0"/></svg>'
						: '<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>'));

			var subCategory = isVip ? 'VIP Membership' : (isReward ? 'Daily Bonus' : (isLemon ? 'Payment Gateway' : 'Episode Unlock'));
			var cleanedName = reasonText.replace(/Lemon Squeezy\s*[•-]\s*/i, '').trim();

			var orderCode = item.order_id || ('TX-' + Math.abs(((item.ts || Date.now()) % 89999) + 10000));
			var orderBadge = '<span style="font-family:monospace; font-size:11px; font-weight:700; color:#38bdf8; background:rgba(56,189,248,0.1); border:1px solid rgba(56,189,248,0.25); padding:2px 6px; border-radius:5px;">' + orderCode + '</span>';

			var amountText = isVip ? '👑 VIP PASS' : (amount ? ((amount > 0 ? '+' : '') + amount + ' Coins') : 'Completed');
			var amountColor = isVip ? '#c084fc' : (isCredit ? '#4ade80' : '#f87171');

			var statusText = item.status || 'Completed';
			var isSuccess = statusText.toLowerCase() === 'completed' || statusText.toLowerCase() === 'success';
			var bg = isSuccess ? '#dcfce7' : '#fee2e2';
			var border = isSuccess ? '#86efac' : '#fca5a5';
			var color = isSuccess ? '#15803d' : '#b91c1c';
			var statusSvg = '<svg width="78" height="20" viewBox="0 0 78 20" style="display:inline-block; vertical-align:middle;">' +
				'<rect width="78" height="20" rx="10" fill="' + bg + '" stroke="' + border + '" stroke-width="1"/>' +
				'<text x="39" y="14" text-anchor="middle" font-size="10.5" font-weight="800" fill="' + color + '" font-family="-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif">' + statusText + '</text>' +
			'</svg>';

			var actionBtn = isPurchase ? ('<button type="button" class="saas-mob-btn-details saas-btn-details" data-tx-index="' + index + '">Receipt</button>') : '';
			var cardClass = 'saas-mobile-card' + (isPurchase ? ' has-receipt' : '');

			return '<div class="' + cardClass + '" ' + (isPurchase ? ('data-tx-index="' + index + '"') : '') + ' style="' + (isPurchase ? 'cursor:pointer;' : 'cursor:default;') + '">' +
				'<div class="saas-mob-card-top">' +
					'<div class="saas-mob-avatar" style="background:' + avatarBg + '; color:' + avatarColor + ';">' + avatarSvg + '</div>' +
					'<div class="saas-mob-info">' +
						'<div class="saas-mob-title">' + cleanedName + '</div>' +
						'<div class="saas-mob-sub">' + subCategory + ' &bull; ' + dateDateStr + ' ' + dateTimeStr + '</div>' +
					'</div>' +
					'<div class="saas-mob-amount" style="color:' + amountColor + ';">' + amountText + '</div>' +
				'</div>' +
				'<div class="saas-mob-card-bottom">' +
					'<div style="display:flex; align-items:center; gap:8px;">' + orderBadge + statusSvg + '</div>' +
					actionBtn +
				'</div>' +
			'</div>';
		}

		var globalEntries = [];

		function applySaasFilters() {
			var searchEl = document.getElementById('saas-tx-search-input');
			var typeEl = document.getElementById('saas-filter-type');
			var dateEl = document.getElementById('saas-filter-date');
			var statusEl = document.getElementById('saas-filter-status');
			var tableBody = document.getElementById('saas-tx-table-body');
			var mobileListEl = document.getElementById('saas-mobile-tx-list');
			var emptyState = document.getElementById('saas-tx-empty-state');

			var q = searchEl ? searchEl.value.trim().toLowerCase() : '';
			var filterType = typeEl ? typeEl.value : 'all';
			var filterDate = dateEl ? dateEl.value : 'all';
			var filterStatus = statusEl ? statusEl.value : 'all';

			var now = Date.now();

			var filtered = globalEntries.filter(function(item) {
				var reason = (item.reason || '').toLowerCase();
				var orderId = (item.order_id || '').toLowerCase();
				var status = (item.status || 'Completed').toLowerCase();
				var amount = parseInt(item.amount || 0, 10);
				var isVip = item.type === 'vip' || reason.indexOf('vip') !== -1;
				var isCredit = isVip || amount >= 0;
				var isReward = reason.indexOf('reward') !== -1 || reason.indexOf('checkin') !== -1 || reason.indexOf('check-in') !== -1 || reason.indexOf('bonus') !== -1;
				var isTopup = isCredit && !isVip && !isReward;
				var isUnlock = !isCredit && !isVip;

				// Search query
				if (q) {
					var matchQ = reason.indexOf(q) !== -1 || orderId.indexOf(q) !== -1 || status.indexOf(q) !== -1;
					if (!matchQ) return false;
				}

				// Type
				if (filterType === 'topup' && !isTopup) return false;
				if (filterType === 'vip' && !isVip) return false;
				if (filterType === 'reward' && !isReward) return false;
				if (filterType === 'unlock' && !isUnlock) return false;

				// Status
				if (filterStatus === 'completed' && status !== 'completed' && status !== 'success') return false;
				if (filterStatus === 'pending' && status !== 'pending') return false;

				// Date
				if (filterDate !== 'all' && item.ts) {
					var days = parseInt(filterDate, 10);
					var diffDays = (now - item.ts) / (1000 * 60 * 60 * 24);
					if (diffDays > days) return false;
				}

				return true;
			});

			if (!filtered.length) {
				if (tableBody) tableBody.innerHTML = '';
				if (mobileListEl) mobileListEl.innerHTML = '';
				if (emptyState) emptyState.style.display = 'block';
			} else {
				if (emptyState) emptyState.style.display = 'none';
				var tableRowsHtml = '';
				var mobileCardsHtml = '';
				filtered.forEach(function(item) {
					var originalIdx = globalEntries.indexOf(item);
					var idx = originalIdx !== -1 ? originalIdx : 0;
					tableRowsHtml += buildSaasTableRow(item, idx);
					mobileCardsHtml += buildSaasMobileCard(item, idx);
				});
				if (tableBody) tableBody.innerHTML = tableRowsHtml;
				if (mobileListEl) mobileListEl.innerHTML = mobileCardsHtml;
			}
		}

		function deduplicateTransactions(list) {
			if (!Array.isArray(list)) return [];
			var seen = new Set();
			return list.filter(function(item) {
				if (!item) return false;
				var key;
				if (item.order_id && typeof item.order_id === 'string' && item.order_id.length > 3) {
					key = 'order_' + item.order_id;
				} else {
					var roundedTs = Math.floor((item.ts || 0) / 10000);
					key = (item.reason || '') + '_' + (item.amount || 0) + '_' + (item.type || '') + '_' + roundedTs;
				}
				if (seen.has(key)) return false;
				seen.add(key);
				return true;
			});
		}

		function renderList(entries) {
			entries = deduplicateTransactions(entries || []);
			globalEntries = entries;
			var emptyHtml = '<div class="account-tx-row" style="display:flex; align-items:center; justify-content:space-between; padding:11px 14px; border-bottom:none;">' +
				'<div style="display:flex; align-items:center; gap:11px; min-width:0;">' +
					'<div style="width:32px; height:32px; border-radius:8px; background:rgba(234,179,8,0.14); color:#fbbf24; display:flex; align-items:center; justify-content:center; flex-shrink:0;">' +
						'<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M15 9.5a2.5 2.5 0 0 0-5 0c0 2 5 2 5 5a2.5 2.5 0 0 1-5 0"/></svg>' +
					'</div>' +
					'<div style="min-width:0;">' +
						'<div style="font-size:13px; font-weight:700; color:#ffffff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">Welcome Bonus</div>' +
						'<div style="font-size:11px; color:#64748b; margin-top:2px;">Account Creation · Completed</div>' +
					'</div>' +
				'</div>' +
				'<div style="text-align:right; flex-shrink:0;">' +
					'<div style="font-size:13.5px; font-weight:800; color:#4ade80;">+100 Coins</div>' +
					'<div style="font-size:10.5px; color:#64748b; margin-top:1px;">Free Credit</div>' +
				'</div>' +
			'</div>';

			// Render Preview on Main Hub (up to 4 items)
			if (txListEl) {
				if (!entries || !entries.length) {
					txListEl.innerHTML = emptyHtml;
				} else {
					var previewHtml = '';
					var previewSlice = entries.slice(0, 4);
					previewSlice.forEach(function(item, idx) {
						var isLast = idx === previewSlice.length - 1;
						previewHtml += buildTxHtml(item, idx, isLast);
					});
					txListEl.innerHTML = previewHtml;
				}
			}

			// Render Full SaaS Fintech Table & Mobile Cards
			applySaasFilters();
		}

		// Bind Search & Custom Dropdowns
		var saasSearchInput = document.getElementById('saas-tx-search-input');
		if (saasSearchInput) {
			saasSearchInput.addEventListener('input', applySaasFilters);
		}

		// Custom Dropdown Interactions
		document.querySelectorAll('.saas-custom-dropdown').forEach(function(dropdown) {
			var trigger = dropdown.querySelector('.saas-dropdown-trigger');
			var input = dropdown.querySelector('input[type="hidden"]');
			var label = dropdown.querySelector('.saas-dropdown-label');
			var options = dropdown.querySelectorAll('.saas-dropdown-option');

			if (trigger) {
				trigger.addEventListener('click', function(e) {
					e.stopPropagation();
					var isOpen = dropdown.classList.contains('is-open');
					document.querySelectorAll('.saas-custom-dropdown.is-open').forEach(function(d) {
						if (d !== dropdown) d.classList.remove('is-open');
					});
					dropdown.classList.toggle('is-open', !isOpen);
				});
			}

			options.forEach(function(opt) {
				opt.addEventListener('click', function(e) {
					e.stopPropagation();
					var val = this.getAttribute('data-value');
					var txt = this.textContent.trim();
					if (input) input.value = val;
					if (label) label.textContent = txt;
					options.forEach(function(o) { o.classList.remove('is-selected'); });
					this.classList.add('is-selected');
					dropdown.classList.remove('is-open');
					applySaasFilters();
				});
			});
		});

		// Close dropdowns on outside click
		document.addEventListener('click', function(e) {
			document.querySelectorAll('.saas-custom-dropdown.is-open').forEach(function(d) {
				d.classList.remove('is-open');
			});

			// Delegated click listener for transaction details & receipts
			var target = e.target.closest('.has-receipt, .saas-btn-details, .account-tx-receipt-btn');
			if (target) {
				var idx = parseInt(target.getAttribute('data-tx-index'), 10);
				if (!isNaN(idx) && globalEntries[idx]) {
					showTxReceiptModal(globalEntries[idx]);
				}
			}
		});

		// Initial load from localStorage with fallback
		var localTx = [];
		try {
			localTx = JSON.parse(localStorage.getItem('short_local_coin_history') || '[]');
			if (!localTx.length && currentUid) {
				localTx = JSON.parse(localStorage.getItem('stv_coin_history_' + currentUid) || '[]');
			}
		} catch(e){}
		if (!localTx || !localTx.length) {
			localTx = [{
				amount: 100,
				reason: 'Welcome Bonus Credits',
				status: 'Completed',
				ts: Date.now(),
				order_id: 'WB-' + (currentUid ? currentUid.slice(0,6) : '89214')
			}];
		}
		renderList(localTx);

		// Live sync from Firebase RTDB (with REST fallback)
		var RTDB_URL = (typeof window.shortFirebaseSettings !== 'undefined' && window.shortFirebaseSettings.databaseURL)
			? window.shortFirebaseSettings.databaseURL
			: 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';

		var targetUid = (user && user.uid) ? user.uid : localStorage.getItem('short_user_uid');

		if (targetUid) {
			if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) {
				firebase.database().ref('users/' + targetUid + '/coinHistory').limitToLast(50).on('value', function(snapshot) {
					var data = snapshot.val();
					if (data) {
						var entries = Object.keys(data).map(function(k) { return data[k]; }).reverse();
						try {
							localStorage.setItem('short_local_coin_history', JSON.stringify(entries));
							localStorage.setItem('stv_coin_history_' + targetUid, JSON.stringify(entries));
						} catch(e) {}
						renderList(entries);
					}
				});
			} else if (RTDB_URL) {
				fetch(RTDB_URL + '/users/' + targetUid + '/coinHistory.json?orderBy="$key"&limitToLast=50')
					.then(function(res) { return res.json(); })
					.then(function(data) {
						if (data && typeof data === 'object') {
							var entries = Object.keys(data).map(function(k) { return data[k]; }).reverse();
							try {
								localStorage.setItem('short_local_coin_history', JSON.stringify(entries));
								localStorage.setItem('stv_coin_history_' + targetUid, JSON.stringify(entries));
							} catch(e) {}
							renderList(entries);
						}
					})
					.catch(function(err) {
						console.warn('[Account REST coinHistory fetch error]', err);
					});
			}
		}
	}

	// ─────────────────────────────────────────────────────────────
	// REWARDS & DAILY COINS ENGINE
	// ─────────────────────────────────────────────────────────────
	var rewardedUrls      = <?php echo $ad_url_json; ?>;
	var rawVideoAdUrl     = <?php echo wp_json_encode( $rewarded_video_url ); ?>;
	var configCountdown   = <?php echo (int) $ad_countdown_s; ?> || 15;
	var configRewardCoins = <?php echo (int) $ad_reward_coins; ?> || 30;
	var defaultAdVideos   = [
		'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4',
		'https://vjs.zencdn.net/v/oceans.mp4',
		'https://raw.githubusercontent.com/bower-media-samples/big-buck-bunny-480p-30s/master/video.mp4'
	];
	var defaultTestVideo  = defaultAdVideos[0];

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

	function getFirebaseRtdbUrl() {
		return (typeof window.shortFirebaseSettings !== 'undefined' && window.shortFirebaseSettings.databaseURL)
			? window.shortFirebaseSettings.databaseURL
			: ((typeof window.SHORT_CONFIG !== 'undefined' && window.SHORT_CONFIG.firebase && window.SHORT_CONFIG.firebase.databaseURL)
				? window.SHORT_CONFIG.firebase.databaseURL
				: 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app');
	}

	function getRewardCoins() {
		return parseInt(localStorage.getItem('shorttv_user_coins') || localStorage.getItem('short_user_coins') || '100', 10);
	}

	function updateRewardCoinsDisplay(val) {
		var c = (typeof val === 'number' && !isNaN(val)) ? val : getRewardCoins();
		var el = document.getElementById('reward-user-coins-display');
		if (el) el.textContent = c;
		var detailCoins = document.getElementById('detail-coins-val');
		if (detailCoins) detailCoins.textContent = 'Balance: ' + c + ' Coins';
		var hubWalletPill = document.getElementById('hub-wallet-coins-pill');
		if (hubWalletPill) hubWalletPill.textContent = c + ' Coins';
		var hubWalletPreview = document.getElementById('hub-wallet-coins-preview');
		if (hubWalletPreview) hubWalletPreview.textContent = c;
		var detailWalletBal = document.getElementById('detail-wallet-balance-num');
		if (detailWalletBal) detailWalletBal.textContent = c;
		if (typeof window.syncHeaderCoins === 'function') {
			window.syncHeaderCoins();
		}
	}

	function addRewardCoins(amount, reason) {
		var uid = localStorage.getItem('short_user_uid');
		var cur = getRewardCoins() + amount;
		localStorage.setItem('shorttv_user_coins', String(cur));
		localStorage.setItem('short_user_coins', String(cur));
		updateRewardCoinsDisplay(cur);

		var txItem = {
			amount: amount,
			reason: reason || 'Reward Bonus',
			type: 'reward',
			status: 'Completed',
			order_id: 'RW-' + Math.floor(Math.random() * 89999 + 10000),
			ts: Date.now()
		};

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

		// Sync to RTDB (Atomic Transaction)
		var rtdbRef = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) ? firebase.database() : null;
		if (rtdbRef && uid) {
			try {
				rtdbRef.ref('users/' + uid + '/coins').transaction(function(curr) {
					return (parseInt(curr, 10) || 0) + amount;
				}, function(err, committed, snap) {
					if (committed && snap) {
						var authVal = parseInt(snap.val(), 10);
						if (!isNaN(authVal)) {
							localStorage.setItem('shorttv_user_coins', String(authVal));
							localStorage.setItem('short_user_coins', String(authVal));
							updateRewardCoinsDisplay(authVal);
						}
					}
				});
				if (typeof window.recordShortTransaction !== 'function') {
					rtdbRef.ref('users/' + uid + '/coinHistory').push(txItem);
				}
			} catch(e) {
				console.warn('[Account RTDB Coin Error]', e);
			}
		} else if (uid) {
			var RTDB_URL = getFirebaseRtdbUrl();
			if (RTDB_URL) {
				fetch(RTDB_URL + '/users/' + uid + '/coins.json')
					.then(function(res) { return res.json(); })
					.then(function(cloudCoins) {
						var base = (typeof cloudCoins === 'number') ? cloudCoins : cur;
						var fin = (typeof cloudCoins === 'number') ? (base + amount) : cur;
						localStorage.setItem('shorttv_user_coins', String(fin));
						localStorage.setItem('short_user_coins', String(fin));
						updateRewardCoinsDisplay(fin);
						return fetch(RTDB_URL + '/users/' + uid + '/coins.json', {
							method: 'PUT',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify(fin)
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

		window.dispatchEvent(new CustomEvent('short_transaction_updated'));
	}

	// Rewarded Video Ad Modal Engine
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

	function resolveVideoUrl(targetUrl, callback) {
		if (!targetUrl || typeof targetUrl !== 'string' || !targetUrl.trim()) {
			callback(defaultTestVideo);
			return;
		}
		targetUrl = targetUrl.trim();
		if (/\.(mp4|webm|m3u8)(\?|$)/i.test(targetUrl)) {
			callback(targetUrl);
			return;
		}
		if (targetUrl.indexOf('pubads.g.doubleclick.net') !== -1 || targetUrl.indexOf('googleads') !== -1 || targetUrl.indexOf('omg10.com') !== -1) {
			callback(defaultTestVideo);
			return;
		}
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

		addRewardCoins(currentAdReward, 'Watched Rewarded Video Ad (+' + currentAdReward + ' Coins)');

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
		btnContinueWatch.addEventListener('click', resumeAdPlayback);
	}
	if (btnConfirmExit) {
		btnConfirmExit.addEventListener('click', function(){
			closeRewardVideoModal(true);
		});
	}
	if (closeVideoBtn) {
		closeVideoBtn.addEventListener('click', function(e){
			e.preventDefault();
			closeRewardVideoModal(false);
		});
	}
	if (exitBackdrop) {
		exitBackdrop.addEventListener('click', function(){
			resumeAdPlayback();
		});
	}

	// Check-in & Activities UI Init
	function initRewardsView() {
		updateRewardCoinsDisplay();
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

		var todayNode = document.getElementById('checkin-today-node');
		var checkinStatusText = document.getElementById('checkin-status-text');

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

		if (todayNode) {
			todayNode.addEventListener('click', function(){
				if (lastCheckinDate === todayStr) {
					showRewardToast('Already checked in today!');
					return;
				}
				var uid = localStorage.getItem('short_user_uid');
				var rtdbRef = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) ? firebase.database() : null;

				if (rtdbRef && uid) {
					todayNode.style.pointerEvents = 'none';
					rtdbRef.ref('users/' + uid + '/rewardCheckin').transaction(function(curr) {
						if (curr && curr.lastDate === todayStr) {
							// Already claimed elsewhere! Abort transaction.
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
							console.warn('[Account Checkin RTDB Error]', error);
							lastCheckinDate = todayStr;
							streakCount++;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							localStorage.setItem('shorttv_reward_streak', String(streakCount));
							if (streakEl) streakEl.textContent = streakCount;
							updateCheckinUI();
							addRewardCoins(150, 'Daily Check-in Day ' + streakCount);
							showRewardToast('+150 Coins Claimed!');
						} else if (!committed) {
							lastCheckinDate = todayStr;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							updateCheckinUI();
							showRewardToast('Already checked in on another device today!');
						} else {
							var val = snapshot ? snapshot.val() : null;
							streakCount = (val && val.streak) ? parseInt(val.streak, 10) : (streakCount + 1);
							lastCheckinDate = todayStr;
							localStorage.setItem('shorttv_reward_checkin_date', todayStr);
							localStorage.setItem('shorttv_reward_streak', String(streakCount));
							if (streakEl) streakEl.textContent = streakCount;
							updateCheckinUI();
							addRewardCoins(150, 'Daily Check-in Day ' + streakCount);
							showRewardToast('+150 Coins Claimed!');
						}
					});
				} else if (uid) {
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

							addRewardCoins(150, 'Daily Check-in Day ' + streakCount);
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
							addRewardCoins(150, 'Daily Check-in Day ' + streakCount);
							showRewardToast('+150 Coins Claimed!');
						});
				} else {
					lastCheckinDate = todayStr;
					streakCount++;
					localStorage.setItem('shorttv_reward_checkin_date', todayStr);
					localStorage.setItem('shorttv_reward_streak', String(streakCount));
					if (streakEl) streakEl.textContent = streakCount;
					updateCheckinUI();
					addRewardCoins(150, 'Daily Check-in Day ' + streakCount);
					showRewardToast('+150 Coins Claimed!');
				}
			});
		}

		// Watch Time Milestones
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
			var uid = localStorage.getItem('short_user_uid');
			var rtdbRef = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) ? firebase.database() : null;
			if (rtdbRef && uid) {
				rtdbRef.ref('users/' + uid + '/rewardTasks/' + todayStr + '/claimedMilestones').set(arr);
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

		renderWatchMilestones();

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
					addRewardCoins(unclaimedReadyCoins, 'Watch Milestone (' + unclaimedReadyMins.join('m, ') + 'm)');
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
					addRewardCoins(rewardCoins, 'Watch Milestone ' + targetMins + 'm');
					renderWatchMilestones();
					showRewardToast('+' + rewardCoins + ' Coins Claimed!');
				} else {
					var remain = targetMins - watchMins;
					showRewardToast('Watch ' + remain + 'm more for +' + rewardCoins + ' coins');
				}
			});
		});

		// Share Task
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
				var uid = localStorage.getItem('short_user_uid');
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
					var rtdbRef = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) ? firebase.database() : null;
					if (rtdbRef && uid) {
						rtdbRef.ref('users/' + uid + '/rewardTasks/' + todayStr + '/shared').set(true);
					}
					addRewardCoins(50, 'Shared with Friends Reward');
					showRewardToast('+50 Coins Awarded!');
				}
			});
		}

		// Watch Ads Task
		var btnWatchAd = document.getElementById('btn-watch-ad-task');
		if (btnWatchAd) {
			btnWatchAd.addEventListener('click', function(){
				if (adsCount >= 10) {
					showRewardToast('Daily limit reached (10/10)');
					return;
				}
				var uid = localStorage.getItem('short_user_uid');

				playRewardedVideo(30, function(){
					adsCount++;
					localStorage.setItem('shorttv_reward_ads_count_' + todayStr, String(adsCount));
					updateAdUI();

					var rtdbRef = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) ? firebase.database() : null;
					if (rtdbRef && uid) {
						rtdbRef.ref('users/' + uid + '/rewardTasks/' + todayStr + '/adsCount').set(adsCount);
					}
				});
			});
		}

		// Rules Modal
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

		// Attach RTDB listeners for rewards tab in account page
		var accUid = localStorage.getItem('short_user_uid');
		var rtdbAccount = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.database) ? firebase.database() : null;
		if (rtdbAccount && accUid) {
			rtdbAccount.ref('users/' + accUid + '/rewardCheckin').on('value', function(snap) {
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
			rtdbAccount.ref('users/' + accUid + '/coins').on('value', function(snap) {
				var v = snap.val();
				if (v !== null && v !== undefined) {
					var cVal = parseInt(v, 10);
					if (!isNaN(cVal)) {
						localStorage.setItem('shorttv_user_coins', String(cVal));
						localStorage.setItem('short_user_coins', String(cVal));
						updateRewardCoinsDisplay(cVal);
					}
				}
			});
		}
	}

	// Initialize Display Modes & Theme Contrast / Accents
	function initAppearanceControls() {
		// 1. Display Modes (Cinematic Dark, OLED Black, Midnight Navy)
		var modeCards = document.querySelectorAll('.display-mode-card');
		var savedMode = localStorage.getItem('short_display_mode') || 'cinematic-dark';

		function setDisplayMode(mode) {
			modeCards.forEach(function(card) {
				if (card.getAttribute('data-mode') === mode) {
					card.classList.add('is-active');
				} else {
					card.classList.remove('is-active');
				}
			});
			document.documentElement.setAttribute('data-display-mode', mode);
			localStorage.setItem('short_display_mode', mode);

			// Dynamic background adjustments for page wrapper & meta theme-color
			var pageWrap = document.querySelector('.short-settings-page-wrapper');
			var metaTheme = document.querySelector('meta[name="theme-color"]');
			var targetBg = '#121212';
			var targetRgb = '18, 18, 18';
			if (mode === 'oled-black') {
				targetBg = '#000000';
				targetRgb = '0, 0, 0';
			} else if (mode === 'midnight-navy') {
				targetBg = '#0b101b';
				targetRgb = '11, 16, 27';
			}

			document.documentElement.style.setProperty('--bg-app', targetBg);
			document.documentElement.style.setProperty('--bg-app-rgb', targetRgb);
			document.documentElement.style.setProperty('--bg-header', targetBg);
			document.documentElement.style.setProperty('--bg-nav-bottom', targetBg);
			document.documentElement.style.setProperty('--bg-nav-top', targetBg);
			document.documentElement.style.setProperty('--bg-footer', targetBg);
			document.documentElement.style.setProperty('--bg-primary', targetBg);
			document.documentElement.style.setProperty('--bg-body', targetBg);

			if (pageWrap) {
				pageWrap.style.setProperty('background', targetBg, 'important');
				pageWrap.style.setProperty('background-color', targetBg, 'important');
			}
			document.querySelectorAll('.settings-top-navbar, .reward-mobile-top-navbar, .short-mobile-bottom-nav, .mobile-bottom-nav, .settings-view-pane, .short-header, .site-header, .reel-header').forEach(function(el) {
				el.style.setProperty('background', targetBg, 'important');
				el.style.setProperty('background-color', targetBg, 'important');
			});
			if (metaTheme) {
				metaTheme.setAttribute('content', targetBg);
			}
		}

		setDisplayMode(savedMode);

		modeCards.forEach(function(card) {
			card.addEventListener('click', function() {
				var m = card.getAttribute('data-mode');
				if (m) setDisplayMode(m);
			});
		});

		// 2. Theme Accent & Contrast Colors
		var colorSwatches = document.querySelectorAll('.color-swatch-btn');
		var customPicker = document.getElementById('theme-custom-picker');
		var hexInput = document.getElementById('theme-hex-input');
		var savedColor = localStorage.getItem('short_theme_color') || '#E50914';

		function applyThemeColor(hex) {
			if (!hex || !/^#[0-9A-Fa-f]{6}$/i.test(hex)) return;

			document.documentElement.style.setProperty('--accent', hex);
			document.documentElement.style.setProperty('--theme-accent', hex);
			document.documentElement.style.setProperty('--btn-primary-bg', hex);
			document.documentElement.style.setProperty('--player-accent', hex);
			document.documentElement.style.setProperty('--brand-primary', hex);

			var cleanHex = hex.replace('#', '');
			var r = parseInt(cleanHex.substring(0, 2), 16);
			var g = parseInt(cleanHex.substring(2, 4), 16);
			var b = parseInt(cleanHex.substring(4, 6), 16);
			document.documentElement.style.setProperty('--theme-accent-rgb', r + ', ' + g + ', ' + b);
			document.documentElement.style.setProperty('--accent-rgb', r + ', ' + g + ', ' + b);
			document.documentElement.style.setProperty('--theme-accent-glow', 'rgba(' + r + ', ' + g + ', ' + b + ', 0.45)');

			localStorage.setItem('short_theme_color', hex);

			// Update swatches active state
			colorSwatches.forEach(function(btn) {
				var c = btn.getAttribute('data-color');
				if (c && c.toLowerCase() === hex.toLowerCase()) {
					btn.classList.add('is-active');
				} else {
					btn.classList.remove('is-active');
				}
			});

			// Update picker inputs
			if (customPicker) customPicker.value = hex;
			if (hexInput) hexInput.value = hex.toUpperCase();

			// Mini player preview elements
			var prevProg = document.getElementById('mini-preview-progress');
			var prevKnob = document.getElementById('mini-preview-knob');
			var prevBadge = document.getElementById('mini-preview-badge');
			var prevBtn = document.getElementById('mini-preview-button');
			if (prevProg) prevProg.style.background = hex;
			if (prevKnob) prevKnob.style.boxShadow = '0 0 6px ' + hex;
			if (prevBadge) {
				prevBadge.style.background = hex;
				prevBadge.style.color = '#ffffff';
			}
			if (prevBtn) {
				prevBtn.style.background = 'rgba(' + r + ',' + g + ',' + b + ', 0.2)';
				prevBtn.style.color = hex;
			}
		}

		applyThemeColor(savedColor);

		colorSwatches.forEach(function(btn) {
			btn.addEventListener('click', function() {
				var c = btn.getAttribute('data-color');
				if (c) applyThemeColor(c);
			});
		});

		if (customPicker) {
			customPicker.addEventListener('input', function() {
				applyThemeColor(customPicker.value);
			});
		}

		if (hexInput) {
			hexInput.addEventListener('input', function() {
				var val = hexInput.value.trim();
				if (!val.startsWith('#')) val = '#' + val;
				if (/^#[0-9A-Fa-f]{6}$/i.test(val)) {
					applyThemeColor(val);
				}
			});
		}
	}

	initRewardsView();
	initAppearanceControls();

	window.addEventListener('short_transaction_updated', function() {
		var user = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) ? firebase.auth().currentUser : null;
		renderAccountTransactions(user);
	});

	// Initial auth bootstrap — run immediately (before DOMContentLoaded)
	// so hub footer buttons are correct before any other script can interfere.
	(function immediateSync() {
		var localLoggedIn = (localStorage.getItem('short_is_logged_in') === '1') && !!localStorage.getItem('short_user_email');
		if (localLoggedIn) {
			syncSettingsState({
				displayName: localStorage.getItem('short_user_display_name') || '',
				email:       localStorage.getItem('short_user_email') || '',
				uid:         localStorage.getItem('short_user_uid') || '',
				photoURL:    localStorage.getItem('short_user_photo') || ''
			});
		} else {
			syncSettingsState(null);
		}
	})();

	// Once Firebase resolves, override with the authoritative server state.
	document.addEventListener('DOMContentLoaded', function() {
		if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
			firebase.auth().onAuthStateChanged(function(user) {
				syncSettingsState(user);
			});
		}
	});

})();
</script>

<?php
get_footer();
