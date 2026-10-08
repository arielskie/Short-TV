<?php
/**
 * Template Name: Subscription & Plans
 *
 * @package Short_Stream
 */

$sub_settings = get_option( 'short_subscription_settings', array() );

$current_user_plan_id = '';
if ( class_exists( 'SHORT_Subscription' ) && method_exists( 'SHORT_Subscription', 'get_user_plan' ) && is_user_logged_in() ) {
	$plan_info = SHORT_Subscription::get_user_plan( get_current_user_id() );
	if ( ! empty( $plan_info['name'] ) && 'Free Member' !== $plan_info['name'] ) {
		$pname = strtolower( $plan_info['name'] );
		if ( strpos( $pname, 'week' ) !== false || ( ! empty( $plan_info['plan_id'] ) && 'basic' === $plan_info['plan_id'] ) ) {
			$current_user_plan_id = 'basic';
		} elseif ( strpos( $pname, 'month' ) !== false || ( ! empty( $plan_info['plan_id'] ) && 'standard' === $plan_info['plan_id'] ) ) {
			$current_user_plan_id = 'standard';
		} elseif ( strpos( $pname, 'annual' ) !== false || strpos( $pname, 'year' ) !== false || ( ! empty( $plan_info['plan_id'] ) && 'premium' === $plan_info['plan_id'] ) ) {
			$current_user_plan_id = 'premium';
		}
	}
}

get_header();

$sym            = ! empty( $sub_settings['currency_symbol'] ) ? $sub_settings['currency_symbol'] : '$';
$basic_price    = ! empty( $sub_settings['plan_basic_price'] ) ? $sub_settings['plan_basic_price'] : '4.99';
$standard_price = ! empty( $sub_settings['plan_standard_price'] ) ? $sub_settings['plan_standard_price'] : '14.99';
$premium_price  = ! empty( $sub_settings['plan_premium_price'] ) ? $sub_settings['plan_premium_price'] : '49.99';
?>

<div class="short-subscription-page">
	
	<!-- Clean Top Navbar: < Subscription - ZERO BORDER -->
	<div class="sub-top-navbar">
		<div class="sub-top-navbar-inner">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="sub-nav-back" title="<?php esc_attr_e( 'Back', 'short-stream' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
			</a>
			<h2 class="sub-top-nav-title"><?php _e( 'Subscription', 'short-stream' ); ?></h2>
			<div class="sub-nav-spacer"></div>
		</div>
	</div>

	<div class="sub-page-container">
		
		<!-- Subscription Header -->
		<div class="sub-header-section" style="margin-bottom: 24px;">
			<span class="sub-step-badge"><?php _e( 'SHORT TV VIP PASS', 'short-stream' ); ?></span>
			<h1 class="sub-title"><?php _e( 'Choose the drama pass that’s right for you', 'short-stream' ); ?></h1>
		</div>

		<!-- TAB SWITCHER NAV (VIP vs COINS) -->
		<div class="sub-tab-switcher-container" style="display: flex; justify-content: center; align-items: center; width: 100%; margin: 16px auto 36px auto; padding: 0; clear: both; position: relative; z-index: 5; overflow: visible !important;">
			<div class="sub-tab-pill-box" style="display: inline-flex; align-items: center; background: #161820; border: none; border-radius: 999px; padding: 4px; gap: 4px; box-shadow: none; max-width: 100%; box-sizing: border-box; overflow: visible !important;">
				<button type="button" class="sub-tab-btn active" id="tab-btn-vip" onclick="switchPricingTab('vip')" style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 8px 18px; height: 36px; border-radius: 999px; font-size: 13px; font-weight: 800; color: #fff; background: var(--theme-accent, #ff2d55); border: none; cursor: pointer; white-space: nowrap; transition: all 0.2s ease; box-sizing: border-box;">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="flex-shrink:0;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
					<span style="white-space: nowrap;"><?php _e( 'VIP Subscription', 'short-stream' ); ?></span>
				</button>
				<button type="button" class="sub-tab-btn" id="tab-btn-coins" onclick="switchPricingTab('coins')" style="display: inline-flex; align-items: center; justify-content: center; gap: 5px; padding: 8px 18px; height: 36px; border-radius: 999px; font-size: 13px; font-weight: 700; color: #94a3b8; background: transparent; border: none; cursor: pointer; white-space: nowrap; transition: all 0.2s ease; box-sizing: border-box;">
					<span style="font-size: 14px; flex-shrink: 0;">💰</span>
					<span style="white-space: nowrap;"><?php _e( 'Top Up Coins', 'short-stream' ); ?></span>
				</button>
			</div>
		</div>

		<!-- TAB PANEL 1: VIP PASSES -->
		<div class="sub-tab-panel" id="tab-panel-vip" style="display: block; width: 100%; clear: both; position: relative; overflow: visible;">

			<!-- ============================================================== -->
			<!-- A. DESKTOP VIEW: ALL 3 CARDS SIDE-BY-SIDE (EXPANDED & SLEEK) -->
			<!-- ============================================================== -->
			<div class="vip-desktop-cards-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; align-items: stretch; margin: 0 auto 36px auto; width: 100%; max-width: 1160px; box-sizing: border-box; overflow: visible;">
				
				<!-- Card 1: Weekly VIP -->
				<div class="sub-plan-card sub-desktop-plan-card<?php echo 'basic' === $current_user_plan_id ? ' is-current-plan' : ''; ?>" id="desktop-plan-card-basic" data-plan-id="basic" onclick="selectDesktopVipPlan('basic')">
					<div>
						<div class="plan-card-header" style="margin-bottom: 8px; display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start; gap: 4px; width: 100%; text-align: left;">
							<div style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:8px;">
								<span class="plan-badge-sub" style="display: inline-block; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; letter-spacing: 0.5px; margin: 0; align-self: flex-start; background: rgba(255,255,255,0.08); color: #94a3b8;"><?php _e( 'FLEXIBLE', 'short-stream' ); ?></span>
								<span class="plan-badge-current" id="plan-badge-current-basic" style="<?php echo 'basic' === $current_user_plan_id ? 'display:inline-flex;' : 'display:none;'; ?> align-items:center; gap:4px; font-size:10px; font-weight:900; padding:3px 9px; border-radius:999px; background:#10b981; color:#ffffff; letter-spacing:0.5px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <?php _e( 'CURRENT PLAN', 'short-stream' ); ?></span>
							</div>
							<h3 class="plan-name" style="font-size: 21px; font-weight: 800; margin: 2px 0 0; color: #ffffff; line-height: 1.2; display: block; width: 100%; text-align: left;"><?php _e( 'Weekly VIP', 'short-stream' ); ?></h3>
							<p class="plan-subtitle" style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.35; display: block; width: 100%; text-align: left;"><?php _e( '7 Days Pass • Flexible weekly access', 'short-stream' ); ?></p>
						</div>

						<div class="plan-price-box" style="margin: 10px 0 16px 0; display: flex; align-items: baseline; gap: 2px;">
							<span class="plan-currency" style="font-size: 18px; font-weight: 800; color: #fff;"><?php echo esc_html( $sym ); ?></span>
							<span class="plan-amount" style="font-size: 36px; font-weight: 900; color: #fff; line-height: 1;"><?php echo esc_html( $basic_price ); ?></span>
							<span class="plan-period" style="font-size: 13px; font-weight: 700; color: #94a3b8; margin-left: 2px;">/week</span>
						</div>

						<ul class="plan-features-list" style="list-style: none; padding: 0; margin: 0 0 18px 0; display: flex; flex-direction: column; gap: 10px; width: 100%; text-align: left;">
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( 'Unlock All Short Drama Episodes', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '1080p Full HD Video Quality', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '100% Ad-Free Fast Streaming', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '+100 Bonus Drama Coins', 'short-stream' ); ?></span>
							</li>
						</ul>
					</div>

					<button type="button" class="btn-select-plan" id="desktop-plan-btn-basic" onclick="executeVipCheckoutPlan('basic', event)">
						<?php echo 'basic' === $current_user_plan_id ? __( 'Current Active Plan', 'short-stream' ) : __( 'Choose Weekly Plan', 'short-stream' ); ?>
					</button>
				</div>

				<!-- Card 2: Monthly VIP (Most Popular) -->
				<div class="sub-plan-card sub-desktop-plan-card is-popular is-selected<?php echo 'standard' === $current_user_plan_id ? ' is-current-plan' : ''; ?>" id="desktop-plan-card-standard" data-plan-id="standard" onclick="selectDesktopVipPlan('standard')">
					<div>
						<div class="plan-card-header" style="margin-bottom: 8px; display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start; gap: 4px; width: 100%; text-align: left;">
							<div style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:8px;">
								<span class="plan-badge-popular" style="display: inline-block; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; letter-spacing: 0.5px; margin: 0; align-self: flex-start; background: var(--theme-accent, #ff2d55); color: #fff;"><?php _e( 'MOST POPULAR', 'short-stream' ); ?></span>
								<span class="plan-badge-current" id="plan-badge-current-standard" style="<?php echo 'standard' === $current_user_plan_id ? 'display:inline-flex;' : 'display:none;'; ?> align-items:center; gap:4px; font-size:10px; font-weight:900; padding:3px 9px; border-radius:999px; background:#10b981; color:#ffffff; letter-spacing:0.5px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <?php _e( 'CURRENT PLAN', 'short-stream' ); ?></span>
							</div>
							<h3 class="plan-name" style="font-size: 21px; font-weight: 800; margin: 2px 0 0; color: #ffffff; line-height: 1.2; display: block; width: 100%; text-align: left;"><?php _e( 'Monthly VIP', 'short-stream' ); ?></h3>
							<p class="plan-subtitle" style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.35; display: block; width: 100%; text-align: left;"><?php _e( '30 Days Pass • Best for binge watchers', 'short-stream' ); ?></p>
						</div>

						<div class="plan-price-box" style="margin: 10px 0 16px 0; display: flex; align-items: baseline; gap: 2px;">
							<span class="plan-currency" style="font-size: 18px; font-weight: 800; color: #fff;"><?php echo esc_html( $sym ); ?></span>
							<span class="plan-amount" style="font-size: 36px; font-weight: 900; color: #fff; line-height: 1;"><?php echo esc_html( $standard_price ); ?></span>
							<span class="plan-period" style="font-size: 13px; font-weight: 700; color: #94a3b8; margin-left: 2px;">/month</span>
						</div>

						<ul class="plan-features-list" style="list-style: none; padding: 0; margin: 0 0 18px 0; display: flex; flex-direction: column; gap: 10px; width: 100%; text-align: left;">
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( 'All Dramas & Series Unlocked', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '1080p Ultra HD Video Quality', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '100% Ad-Free Fast Streaming', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '+500 Bonus Drama Coins', 'short-stream' ); ?></span>
							</li>
						</ul>
					</div>

					<button type="button" class="btn-select-plan" id="desktop-plan-btn-standard" onclick="executeVipCheckoutPlan('standard', event)">
						<?php echo 'standard' === $current_user_plan_id ? __( 'Current Active Plan', 'short-stream' ) : __( 'Choose Monthly Plan', 'short-stream' ); ?>
					</button>
				</div>

				<!-- Card 3: Annual VIP (Best Value) -->
				<div class="sub-plan-card sub-desktop-plan-card<?php echo 'premium' === $current_user_plan_id ? ' is-current-plan' : ''; ?>" id="desktop-plan-card-premium" data-plan-id="premium" onclick="selectDesktopVipPlan('premium')">
					<div>
						<div class="plan-card-header" style="margin-bottom: 8px; display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start; gap: 4px; width: 100%; text-align: left;">
							<div style="display:flex; align-items:center; justify-content:space-between; width:100%; gap:8px;">
								<span class="plan-badge-sub" style="display: inline-block; font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 6px; letter-spacing: 0.5px; margin: 0; align-self: flex-start; background:#10b981; color:#fff;"><?php _e( 'SAVE 60%', 'short-stream' ); ?></span>
								<span class="plan-badge-current" id="plan-badge-current-premium" style="<?php echo 'premium' === $current_user_plan_id ? 'display:inline-flex;' : 'display:none;'; ?> align-items:center; gap:4px; font-size:10px; font-weight:900; padding:3px 9px; border-radius:999px; background:#10b981; color:#ffffff; letter-spacing:0.5px;"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"></polyline></svg> <?php _e( 'CURRENT PLAN', 'short-stream' ); ?></span>
							</div>
							<h3 class="plan-name" style="font-size: 21px; font-weight: 800; margin: 2px 0 0; color: #ffffff; line-height: 1.2; display: block; width: 100%; text-align: left;"><?php _e( 'Annual VIP', 'short-stream' ); ?></h3>
							<p class="plan-subtitle" style="font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.35; display: block; width: 100%; text-align: left;"><?php _e( '365 Days Pass • Unlimited all year', 'short-stream' ); ?></p>
						</div>

						<div class="plan-price-box" style="margin: 10px 0 16px 0; display: flex; align-items: baseline; gap: 2px;">
							<span class="plan-currency" style="font-size: 18px; font-weight: 800; color: #fff;"><?php echo esc_html( $sym ); ?></span>
							<span class="plan-amount" style="font-size: 36px; font-weight: 900; color: #fff; line-height: 1;"><?php echo esc_html( $premium_price ); ?></span>
							<span class="plan-period" style="font-size: 13px; font-weight: 700; color: #94a3b8; margin-left: 2px;">/year</span>
						</div>

						<ul class="plan-features-list" style="list-style: none; padding: 0; margin: 0 0 18px 0; display: flex; flex-direction: column; gap: 10px; width: 100%; text-align: left;">
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( 'All 365 Days & Releases Unlocked', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '1080p Ultra HD + HDR Quality', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( 'Priority Ultra Fast CDN • Ad-Free', 'short-stream' ); ?></span>
							</li>
							<li class="plan-feature-row" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; text-align: left; gap: 9px; font-size: 12.5px; color: #cbd5e1; line-height: 1.35; width: 100%;">
								<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" style="flex-shrink:0; margin:0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
								<span style="text-align: left; line-height: 1.35;"><?php _e( '+2,000 Bonus Drama Coins', 'short-stream' ); ?></span>
							</li>
						</ul>
					</div>

					<button type="button" class="btn-select-plan" id="desktop-plan-btn-premium" onclick="executeVipCheckoutPlan('premium', event)">
						<?php echo 'premium' === $current_user_plan_id ? __( 'Current Active Plan', 'short-stream' ) : __( 'Choose Annual Plan', 'short-stream' ); ?>
					</button>
				</div>
			</div>

			<!-- ============================================================== -->
			<!-- B. MOBILE VIEW: SLEEK TAB SWITCHER (NO CARD BG, NO BORDER, SPACIOUS) -->
			<!-- ============================================================== -->
			<div class="vip-mobile-tabbed-view">
				<div class="vip-mobile-tab-wrapper" id="vip-tabbed-card" data-plan-id="standard" data-plan-name="Monthly VIP" data-price="<?php echo esc_attr( $standard_price ); ?>">
					
					<!-- Main Title with Inline Star Badge & Subtitle -->
					<h2 class="vip-direct-title" style="display: flex; align-items: center; justify-content: flex-start; gap: 8px;">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="flex-shrink: 0;">
							<circle cx="12" cy="12" r="11" fill="#2563eb" />
							<path d="M12 6L14.09 10.26L18.7 10.93L15.35 14.2L16.14 18.79L12 16.61L7.86 18.79L8.65 14.2L5.3 10.93L9.91 10.26L12 6Z" fill="#ffffff"/>
						</svg>
						<span><?php _e( 'Start VIP Membership', 'short-stream' ); ?></span>
					</h2>
					<p class="vip-direct-sub"><?php _e( 'To complete the sign up process and unlock all short drama series, please proceed with your pass.', 'short-stream' ); ?></p>

					<!-- 3. Clean Spacious Mobile Switcher Section -->
					<div class="vip-mobile-inner-section">
						
						<!-- Pill Switcher Tabs -->
						<div class="vip-plan-pills-bar">
							<button type="button" class="vip-plan-pill-tab" id="vip-tab-btn-basic" onclick="switchVipPlanTab('basic')">
								<span><?php _e( 'Weekly', 'short-stream' ); ?></span>
							</button>
							<button type="button" class="vip-plan-pill-tab is-active" id="vip-tab-btn-standard" onclick="switchVipPlanTab('standard')">
								<span><?php _e( 'Monthly', 'short-stream' ); ?></span>
							</button>
							<button type="button" class="vip-plan-pill-tab" id="vip-tab-btn-premium" onclick="switchVipPlanTab('premium')">
								<span><?php _e( 'Annual', 'short-stream' ); ?></span>
							</button>
						</div>

						<!-- Large Price Display -->
						<div class="vip-direct-price-row">
							<span class="vip-direct-currency"><?php echo esc_html( $sym ); ?></span>
							<span class="vip-direct-amount" id="vip-tabbed-price-amount"><?php echo esc_html( $standard_price ); ?></span>
							<span class="vip-direct-period" id="vip-tabbed-price-period">/month</span>
						</div>

						<!-- 2-Column Checkmark Grid -->
						<div class="vip-perks-grid-2col">
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text" id="vip-check-episodes"><?php _e( 'All Dramas', 'short-stream' ); ?></span>
							</div>
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text" id="vip-check-quality"><?php _e( '1080p Ultra HD', 'short-stream' ); ?></span>
							</div>
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text" id="vip-check-adfree"><?php _e( '100% Ad-Free', 'short-stream' ); ?></span>
							</div>
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text" id="vip-check-coins"><?php _e( 'Bonus Coins', 'short-stream' ); ?></span>
							</div>
						</div>

					</div>

					<!-- 4. Trust Cards -->
					<div class="vip-trust-cards-stack">
						<div class="vip-trust-card trust-card-indigo">
							<div class="vip-trust-icon-wrap indigo-icon">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
							</div>
							<div class="vip-trust-info">
								<strong><?php _e( 'Instant VIP Activation', 'short-stream' ); ?></strong>
								<p><?php _e( 'Instant access to all short drama series granted immediately.', 'short-stream' ); ?></p>
							</div>
						</div>

						<div class="vip-trust-card trust-card-green">
							<div class="vip-trust-icon-wrap green-icon">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
							</div>
							<div class="vip-trust-info">
								<strong><?php _e( '100% Account Protected', 'short-stream' ); ?></strong>
								<p><?php _e( 'Pass safely binds to your Google account. Cancel anytime.', 'short-stream' ); ?></p>
							</div>
						</div>
					</div>

					<!-- 5. Primary Mobile CTA Button -->
					<button type="button" class="btn-proceed-payment-pill" id="btn-activate-subscription" onclick="executeVipCheckoutPlan(currentActiveVipTab)">
						<span class="btn-text"><?php _e( 'Proceed to payment →', 'short-stream' ); ?></span>
						<span class="btn-spinner" style="display:none;">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin-icon"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"></path></svg>
						</span>
					</button>

					<div id="checkout-status-msg" style="display:none; margin-top:10px; text-align:center;"></div>

				</div>
			</div>

			<!-- Google Auth Status Bar (Unified for desktop & mobile) -->
			<div class="vip-auth-state-wrap" style="max-width: 540px; margin: 24px auto 0 auto;">
				<!-- State A: Signed In with Google -->
				<div id="checkout-user-logged-in" style="display:none; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; background:rgba(34,197,94,0.07); border:1px solid rgba(34,197,94,0.22); border-radius:12px; padding:10px 14px;">
					<div style="display:flex; align-items:center; gap:10px;">
						<div style="width:28px; height:28px; border-radius:50%; background:#22c55e; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:12px;" id="checkout-user-avatar">✓</div>
						<div>
							<div style="color:#22c55e; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">✓ Account Linked</div>
							<div style="color:#fff; font-size:13px; font-weight:700;" id="checkout-user-email">user@gmail.com</div>
						</div>
					</div>
					<span style="font-size:11px; color:#94a3b8;">VIP binds to this account</span>
				</div>

				<!-- State B: Guest / Sign In Prompt -->
				<div id="checkout-user-guest" style="display:flex; flex-direction:column; gap:8px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 14px; text-align:center;">
					<div style="display:flex; align-items:center; justify-content:center; gap:8px;">
						<span style="font-size:16px;">🔒</span>
						<span style="color:#f59e0b; font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">Google Sign-In Recommended</span>
					</div>
					<p style="color:#94a3b8; font-size:12px; margin:0; line-height:1.4;">Sign in with Google to permanently sync and link your VIP subscription.</p>
					<div>
						<button type="button" class="btn-account-cta" id="btn-checkout-google-signin" style="background:#ffffff; color:#111; padding:8px 16px; border-radius:8px; font-weight:700; font-size:12.5px; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer; margin-top:2px;">
							<svg width="15" height="15" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.9C6.2 7.4 8.9 5 12 5z"/><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/><path fill="#FBBC05" d="M5.3 14.7c-.2-.7-.4-1.5-.4-2.4s.2-1.7.4-2.4L1.6 7c-.8 1.6-1.3 3.4-1.3 5.3 0 1.9.5 3.7 1.3 5.3l3.7-2.9z"/><path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.4-6.7-5.3L1.6 16C3.5 19.8 7.4 23 12 23z"/></svg>
							<span>Sign In with Google</span>
						</button>
					</div>
				</div>

				<!-- Cancel Option if active -->
				<div class="cancel-plan-option-wrap" id="cancel-sub-page-option" style="display:none; text-align:center; margin-top:14px;">
					<button type="button" class="btn-cancel-plan-link" id="btn-cancel-sub-page" onclick="cancelCurrentVipPlan()" style="background:transparent; border:none; color:#ef4444; font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline;">
						<span><?php _e( 'Cancel Current Plan', 'short-stream' ); ?></span>
					</button>
				</div>
			</div>

		</div>
		<!-- END TAB PANEL 1 (VIP) -->

		<!-- TAB PANEL 2: TOP UP COINS -->
		<div class="sub-tab-panel" id="tab-panel-coins" style="display: none;">
			
			<!-- A. DESKTOP VIEW: 3 CARDS -->
			<div class="coins-desktop-cards-grid sub-coin-packs-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; max-width: 940px; margin: 0 auto;">
				<!-- Pack 1: Starter Pack ($1.99 -> 300 Coins) -->
				<div class="stv-coin-pack-card" id="coin-card-starter" data-pack-id="starter" onclick="selectCoinCard('starter')">
					<div class="stv-coin-pack-icon">💰</div>
					<div class="stv-coin-pack-title">300 Coins</div>
					<div class="stv-coin-pack-sub" style="color: #94a3b8;">Unlock ~20 Episodes</div>
					<div class="stv-coin-pack-price" style="color: var(--theme-accent, #ff2d55);"><?php echo esc_html( $sym ); ?>1.99</div>
					<button type="button" class="btn-select-coin-pack" id="coin-btn-starter" onclick="executeCoinRecharge('starter', event)">
						<?php _e( 'Get 300 Coins', 'short-stream' ); ?>
					</button>
				</div>

				<!-- Pack 2: Popular Value Pack ($4.99 -> 1,200 Coins + 200 Bonus) -->
				<div class="stv-coin-pack-card is-popular is-selected-coin" id="coin-card-popular" data-pack-id="popular" onclick="selectCoinCard('popular')">
					<div class="stv-coin-pack-badge" style="background: linear-gradient(135deg, #ff9800, #f57c00); color: #fff;">MOST POPULAR • +200 BONUS</div>
					<div class="stv-coin-pack-icon">💰</div>
					<div class="stv-coin-pack-title">1,200 Coins</div>
					<div class="stv-coin-pack-sub" style="color: #ffb74d; font-weight: 600;">Unlock ~80 Episodes (Full Drama)</div>
					<div class="stv-coin-pack-price" style="color: #ff9800;"><?php echo esc_html( $sym ); ?>4.99</div>
					<button type="button" class="btn-select-coin-pack btn-coin-popular" id="coin-btn-popular" onclick="executeCoinRecharge('popular', event)">
						<?php _e( 'Get 1,200 Coins', 'short-stream' ); ?>
					</button>
				</div>

				<!-- Pack 3: Super Binge Pack ($9.99 -> 3,000 Coins + 600 Bonus) -->
				<div class="stv-coin-pack-card" id="coin-card-super" data-pack-id="super" onclick="selectCoinCard('super')">
					<div class="stv-coin-pack-badge" style="background: #10b981; color: #fff;">BEST VALUE • +600 BONUS</div>
					<div class="stv-coin-pack-icon">💰</div>
					<div class="stv-coin-pack-title">3,000 Coins</div>
					<div class="stv-coin-pack-sub" style="color: #34d399; font-weight: 600;">Unlock ~200 Episodes (2–3 Dramas)</div>
					<div class="stv-coin-pack-price" style="color: #10b981;"><?php echo esc_html( $sym ); ?>9.99</div>
					<button type="button" class="btn-select-coin-pack" id="coin-btn-super" onclick="executeCoinRecharge('super', event)">
						<?php _e( 'Get 3,000 Coins', 'short-stream' ); ?>
					</button>
				</div>
			</div>

			<!-- B. MOBILE VIEW: SLEEK TAB SWITCHER (LIKE VIP MEMBERSHIP) -->
			<div class="coins-mobile-tabbed-view">
				<div class="vip-mobile-tab-wrapper" id="coins-tabbed-card" data-pack-id="popular">
					<!-- Title with Inline Coin Emoji -->
					<h2 class="vip-direct-title" style="display: flex; align-items: center; justify-content: flex-start; gap: 8px;">
						<span style="font-size: 22px; line-height: 1;">💰</span>
						<span><?php _e( 'Top Up Drama Coins', 'short-stream' ); ?></span>
					</h2>
					<p class="vip-direct-sub"><?php _e( 'Unlock individual episodes as you watch with coins. Permanent unlocks & instant wallet recharge.', 'short-stream' ); ?></p>

					<!-- Mobile Switcher Section -->
					<div class="vip-mobile-inner-section">
						<!-- Pill Switcher Tabs -->
						<div class="vip-plan-pills-bar">
							<button type="button" class="vip-plan-pill-tab" id="coin-tab-btn-starter" onclick="switchCoinPlanTab('starter')">
								<span>300 Coins</span>
							</button>
							<button type="button" class="vip-plan-pill-tab is-active" id="coin-tab-btn-popular" onclick="switchCoinPlanTab('popular')">
								<span>1,200 Coins</span>
							</button>
							<button type="button" class="vip-plan-pill-tab" id="coin-tab-btn-super" onclick="switchCoinPlanTab('super')">
								<span>3,000 Coins</span>
							</button>
						</div>

						<!-- Large Price Display -->
						<div class="vip-direct-price-row">
							<span class="vip-direct-currency"><?php echo esc_html( $sym ); ?></span>
							<span class="vip-direct-amount" id="coin-tabbed-price-amount">4.99</span>
							<span class="vip-direct-period" id="coin-tabbed-price-period">/ 1,200 coins</span>
						</div>

						<!-- 2-Column Perks Grid -->
						<div class="vip-perks-grid-2col">
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text" id="coin-check-episodes"><?php _e( '~80 Episodes (Full Drama)', 'short-stream' ); ?></span>
							</div>
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text"><?php _e( 'Instant Recharge', 'short-stream' ); ?></span>
							</div>
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text" id="coin-check-bonus"><?php _e( '+200 Bonus Coins', 'short-stream' ); ?></span>
							</div>
							<div class="vip-perk-item">
								<span class="vip-perk-green-check">✓</span>
								<span class="vip-perk-text"><?php _e( 'Permanent Unlocks', 'short-stream' ); ?></span>
							</div>
						</div>
					</div>

					<!-- Mobile Direct CTA Button -->
					<button type="button" class="btn-proceed-payment-pill" id="btn-recharge-coins-mobile" onclick="executeCoinRecharge(currentActiveCoinTab, event)" style="background: linear-gradient(135deg, #ff9800, #ff5722); box-shadow: none;">
						<span class="btn-text" id="coin-mobile-cta-text"><?php _e( 'Recharge 1,200 Coins Now →', 'short-stream' ); ?></span>
					</button>

				</div>
			</div>

		</div>
		<!-- END TAB PANEL 2 (COINS) -->

	</div>
</div>

<script>
window.SHORT_LS_CONFIG = {
	enabled: <?php echo ( ! empty( $sub_settings['enable_lemonsqueezy'] ) || ( 'lemonsqueezy' === ( $sub_settings['payment_mode'] ?? '' ) ) ) ? 'true' : 'false'; ?>,
	mode: '<?php echo esc_js( $sub_settings['lemonsqueezy_mode'] ?? 'test' ); ?>',
	store_name: '<?php echo esc_js( $sub_settings['lemonsqueezy_store_name'] ?? 'Ayeng Store' ); ?>',
	vip_urls: {
		basic: '<?php echo esc_js( $sub_settings['lemonsqueezy_vip_weekly_url'] ?? '' ); ?>',
		standard: '<?php echo esc_js( $sub_settings['lemonsqueezy_vip_monthly_url'] ?? ( $sub_settings['lemonsqueezy_vip_checkout_url'] ?? '' ) ); ?>',
		premium: '<?php echo esc_js( $sub_settings['lemonsqueezy_vip_annual_url'] ?? '' ); ?>'
	},
	coins_urls: {
		starter: '<?php echo esc_js( $sub_settings['lemonsqueezy_coins_starter_url'] ?? '' ); ?>',
		popular: '<?php echo esc_js( $sub_settings['lemonsqueezy_coins_value_url'] ?? ( $sub_settings['lemonsqueezy_coins_checkout_url'] ?? '' ) ); ?>',
		super: '<?php echo esc_js( $sub_settings['lemonsqueezy_coins_super_url'] ?? '' ); ?>'
	}
};

var vipPlansMap = {
	basic: {
		plan_id: 'basic',
		plan_name: 'Weekly VIP',
		price: '<?php echo esc_js( $basic_price ); ?>',
		period: '/week',
		subtitle: '<?php esc_attr_e( "7 Days Pass • Flexible weekly access", "short-stream" ); ?>',
		quality: '1080p Full HD',
		episodes: 'All Dramas',
		adfree: '100% Ad-Free',
		coins: '+100 Bonus Coins'
	},
	standard: {
		plan_id: 'standard',
		plan_name: 'Monthly VIP',
		price: '<?php echo esc_js( $standard_price ); ?>',
		period: '/month',
		subtitle: '<?php esc_attr_e( "30 Days Pass • Most Popular for binge watchers", "short-stream" ); ?>',
		quality: '1080p Ultra HD',
		episodes: 'All Dramas',
		adfree: '100% Ad-Free',
		coins: '+500 Bonus Coins'
	},
	premium: {
		plan_id: 'premium',
		plan_name: 'Annual VIP',
		price: '<?php echo esc_js( $premium_price ); ?>',
		period: '/year',
		subtitle: '<?php esc_attr_e( "365 Days Pass • Best Value (Save 60%)", "short-stream" ); ?>',
		quality: '1080p Ultra HD',
		episodes: 'All Dramas',
		adfree: '100% Ad-Free',
		coins: '+2,000 Coins'
	}
};

var currentActiveVipTab = 'standard';

function selectDesktopVipPlan(planKey) {
	if (!vipPlansMap[planKey]) return;
	currentActiveVipTab = planKey;
	var data = vipPlansMap[planKey];

	// Update desktop cards selection
	['basic', 'standard', 'premium'].forEach(function(k) {
		var card = document.getElementById('desktop-plan-card-' + k);
		var btn  = document.getElementById('desktop-plan-btn-' + k);
		if (card) {
			if (k === planKey) {
				card.classList.add('is-selected');
			} else {
				card.classList.remove('is-selected');
			}
		}
		if (btn) {
			if (k === planKey) {
				btn.classList.add('btn-popular-gradient');
			} else {
				btn.classList.remove('btn-popular-gradient');
			}
		}
	});

	// Update mobile tab buttons
	['basic', 'standard', 'premium'].forEach(function(k) {
		var tabBtn = document.getElementById('vip-tab-btn-' + k);
		if (tabBtn) {
			if (k === planKey) {
				tabBtn.classList.add('is-active');
			} else {
				tabBtn.classList.remove('is-active');
			}
		}
	});

	// Update mobile view display
	var cardEl = document.getElementById('vip-tabbed-card');
	var amtEl  = document.getElementById('vip-tabbed-price-amount');
	var perEl  = document.getElementById('vip-tabbed-price-period');
	var epEl   = document.getElementById('vip-check-episodes');
	var adEl   = document.getElementById('vip-check-adfree');
	var qlEl   = document.getElementById('vip-check-quality');
	var cnEl   = document.getElementById('vip-check-coins');

	if (cardEl) {
		cardEl.setAttribute('data-plan-id', data.plan_id);
		cardEl.setAttribute('data-plan-name', data.plan_name);
		cardEl.setAttribute('data-price', data.price);
	}

	if (amtEl) amtEl.textContent = data.price;
	if (perEl) perEl.textContent = data.period;
	if (epEl)  epEl.textContent = data.episodes;
	if (adEl)  adEl.textContent = data.adfree;
	if (qlEl)  qlEl.textContent = data.quality;
	if (cnEl)  cnEl.textContent = data.coins;
}

function switchVipPlanTab(planKey) {
	selectDesktopVipPlan(planKey);
}

function executeVipCheckoutPlan(planKey, event) {
	if (event && event.stopPropagation) {
		event.stopPropagation();
	}
	if (!planKey) planKey = currentActiveVipTab;
	selectDesktopVipPlan(planKey);
	var user = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) ? firebase.auth().currentUser : null;
	var rawLocal = localStorage.getItem('short_user_email');
	var isLocalAuth = (localStorage.getItem('short_is_logged_in') === '1') || (rawLocal && rawLocal !== 'guest@short.local');

	if (!user && !isLocalAuth) {
		if (typeof shortCustomAlert === 'function') {
			shortCustomAlert({
				title: 'Sign In Required',
				message: 'Please sign in with your Google account first to link and activate your VIP subscription.',
				type: 'warning'
			});
		} else {
			alert('Please sign in with your Google account first to link and activate your VIP subscription.');
		}
		if (typeof firebase !== 'undefined' && firebase.auth) {
			var provider = new firebase.auth.GoogleAuthProvider();
			provider.setCustomParameters({ prompt: 'select_account' });
			firebase.auth().signInWithPopup(provider).then(function(res) {
				if (res && res.user && res.user.email) {
					window.location.reload();
				}
			}).catch(function(err){});
		}
		return;
	}

	var plan = vipPlansMap[planKey] || vipPlansMap.standard;
	var userEmail = (user && user.email) ? user.email : (rawLocal || 'user@short.local');
	var uid = (user && user.uid) ? user.uid : (localStorage.getItem('short_user_uid') || 'guest_' + Math.random().toString(36).substring(2, 9));

	// Check Lemon Squeezy custom URL
	var customVipUrl = (window.SHORT_LS_CONFIG && window.SHORT_LS_CONFIG.vip_urls && window.SHORT_LS_CONFIG.vip_urls[planKey]) || (window.SHORT_LS_CONFIG && window.SHORT_LS_CONFIG.vip_urls && window.SHORT_LS_CONFIG.vip_urls.standard) || localStorage.getItem('shorttv_ls_vip_url');
	if (customVipUrl) {
		sessionStorage.setItem('short_pending_checkout', JSON.stringify({
			type: 'vip',
			plan_id: plan.plan_id,
			plan_name: plan.plan_name,
			price: plan.price
		}));

		// Attach prefill parameters to checkout URL
		var targetUrl = customVipUrl;
		try {
			var urlObj = new URL(customVipUrl, window.location.origin);
			if (userEmail && userEmail !== 'user@short.local' && userEmail !== 'guest@short.local') {
				urlObj.searchParams.set('checkout[email]', userEmail);
			}
			urlObj.searchParams.set('checkout[custom][user_id]', uid);
			urlObj.searchParams.set('checkout[custom][plan_id]', plan.plan_id);
			urlObj.searchParams.set('checkout[custom][type]', 'vip');
			targetUrl = urlObj.toString();
		} catch(e){}

		if (typeof LemonSqueezy !== 'undefined' && LemonSqueezy.Url) {
			LemonSqueezy.Url.Open(targetUrl);
			return;
		} else {
			window.open(targetUrl, '_blank');
			return;
		}
	}

	// Instant Activation Flow (Sandbox / Fallback)
	var btn = document.getElementById('btn-activate-subscription');
	if (btn) {
		var btnText = btn.querySelector('.btn-text');
		var btnSpinner = btn.querySelector('.btn-spinner');
		if (btnText) btnText.style.display = 'none';
		if (btnSpinner) btnSpinner.style.display = 'inline-block';
		btn.style.pointerEvents = 'none';
	}

	setTimeout(function() {
		var now = Date.now();
		var days = (planKey === 'basic') ? 7 : (planKey === 'premium' ? 365 : 30);
		var expiresAt = now + (days * 24 * 60 * 60 * 1000);
		var bonusCoins = (planKey === 'basic') ? 100 : (planKey === 'premium' ? 2000 : 500);

		localStorage.setItem('short_is_vip', '1');
		localStorage.setItem('short_vip_plan', plan.plan_id);
		localStorage.setItem('short_vip_expires', expiresAt.toString());

		var curCoins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
		curCoins += bonusCoins;
		localStorage.setItem('shorttv_user_coins', curCoins.toString());

		// Record VIP Transaction
		if (typeof window.recordShortTransaction === 'function') {
			window.recordShortTransaction({
				amount: bonusCoins,
				reason: plan.plan_name + ' Activation (' + days + ' Days VIP + ' + bonusCoins + ' Bonus Coins)',
				type: 'vip',
				plan_id: plan.plan_id,
				status: 'Completed',
				order_id: 'TX-' + Math.floor(Math.random()*899999+100000),
				ts: Date.now()
			});
		}

		if (user && firebase.database) {
			var uId = user.uid;
			firebase.database().ref('users/' + uId).update({
				is_vip: true,
				vip_plan: plan.plan_id,
				vip_expires: expiresAt,
				coins: curCoins
			});
			firebase.database().ref('users/' + uId + '/subscription').set({
				plan_id: plan.plan_id,
				plan_name: plan.plan_name,
				quality: (planKey === 'premium') ? '1080p Ultra HD + HDR' : '1080p Ultra HD',
				tier: 'VIP',
				status: 'active',
				activated_at: now,
				expires_at: expiresAt
			});
		}

		var statusEl = document.getElementById('checkout-status-msg');
		if (statusEl) {
			statusEl.style.display = 'block';
			statusEl.innerHTML = '<div style="background:rgba(34,197,94,0.15); border:1px solid #22c55e; border-radius:10px; padding:10px; color:#22c55e; font-weight:800; font-size:13.5px;">🎉 VIP Pass Activated! Enjoy unlimited short drama streaming.</div>';
		}

		if (btn) {
			var btnText = btn.querySelector('.btn-text');
			var btnSpinner = btn.querySelector('.btn-spinner');
			if (btnText) {
				btnText.style.display = 'inline';
				btnText.textContent = '✓ VIP Pass Active';
			}
			if (btnSpinner) btnSpinner.style.display = 'none';
			btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
		}

		var cancelOpt = document.getElementById('cancel-sub-page-option');
		if (cancelOpt) cancelOpt.style.display = 'block';

		if (typeof shortCustomAlert === 'function') {
			shortCustomAlert({
				title: 'VIP Pass Activated! 🎉',
				message: 'Your ' + plan.plan_name + ' is now active for ' + userEmail + ' (' + days + ' days unlocked + ' + bonusCoins + ' bonus coins).',
				type: 'success',
				buttonText: 'Start Watching'
			}).then(function() {
				window.location.href = '<?php echo esc_url( home_url( '/' ) ); ?>';
			});
		} else {
			alert('🎉 Success! Your ' + plan.plan_name + ' is now active for ' + userEmail + ' (' + days + ' days unlocked + ' + bonusCoins + ' bonus coins).');
			window.location.href = '<?php echo esc_url( home_url( '/' ) ); ?>';
		}
	}, 500);
}

function cancelCurrentVipPlan() {
	var proceedCancel = function() {
		localStorage.removeItem('short_is_vip');
		localStorage.removeItem('short_vip_plan');
		localStorage.removeItem('short_vip_expires');
		var user = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) ? firebase.auth().currentUser : null;
		if (user && firebase.database) {
			firebase.database().ref('users/' + user.uid).update({
				is_vip: false,
				vip_plan: null,
				vip_expires: null
			});
		}
		if (typeof shortCustomAlert === 'function') {
			shortCustomAlert({
				title: 'Subscription Cancelled',
				message: 'Your VIP subscription has been cancelled.',
				type: 'info'
			}).then(function() {
				window.location.reload();
			});
		} else {
			alert('Your VIP subscription has been cancelled.');
			window.location.reload();
		}
	};

	if (typeof shortCustomConfirm === 'function') {
		shortCustomConfirm({
			title: 'Cancel Subscription',
			message: 'Are you sure you want to cancel your active VIP subscription?',
			confirmText: 'Yes, Cancel',
			cancelText: 'Keep Plan',
			type: 'sub_required'
		}).then(function(confirmed) {
			if (confirmed) proceedCancel();
		});
	} else if (confirm('Are you sure you want to cancel your active VIP subscription?')) {
		proceedCancel();
	}
}

function switchPricingTab(tab) {
	var vipPanel = document.getElementById('tab-panel-vip');
	var coinsPanel = document.getElementById('tab-panel-coins');
	var vipBtn = document.getElementById('tab-btn-vip');
	var coinsBtn = document.getElementById('tab-btn-coins');
	var accent = getComputedStyle(document.documentElement).getPropertyValue('--theme-accent').trim() || '#ff2d55';

	if (tab === 'coins') {
		if (vipPanel) vipPanel.style.display = 'none';
		if (coinsPanel) coinsPanel.style.display = 'block';
		if (coinsBtn) {
			coinsBtn.style.background = '#f59e0b';
			coinsBtn.style.color = '#fff';
			coinsBtn.classList.add('active');
		}
		if (vipBtn) {
			vipBtn.style.background = 'transparent';
			vipBtn.style.color = '#94a3b8';
			vipBtn.classList.remove('active');
		}
	} else {
		if (vipPanel) vipPanel.style.display = 'block';
		if (coinsPanel) coinsPanel.style.display = 'none';
		if (vipBtn) {
			vipBtn.style.background = accent;
			vipBtn.style.color = '#fff';
			vipBtn.classList.add('active');
		}
		if (coinsBtn) {
			coinsBtn.style.background = 'transparent';
			coinsBtn.style.color = '#94a3b8';
			coinsBtn.classList.remove('active');
		}
	}
}

// Check URL param ?tab=coins to auto open coins tab
if (window.location.search.indexOf('tab=coins') !== -1 || window.location.hash === '#coins') {
	switchPricingTab('coins');
}

document.addEventListener('DOMContentLoaded', function() {
	var isVip = localStorage.getItem('short_is_vip') === '1';
	if (isVip) {
		var cancelOpt = document.getElementById('cancel-sub-page-option');
		if (cancelOpt) cancelOpt.style.display = 'block';
	}

	function getActiveUser(fbUser) {
		if (fbUser && fbUser.email) return fbUser;
		var localEmail = localStorage.getItem('short_user_email');
		var isLocalAuth = (localStorage.getItem('short_is_logged_in') === '1') || (localEmail && localEmail !== 'guest@short.local');
		if (isLocalAuth && localEmail) {
			return {
				email: localEmail,
				displayName: localStorage.getItem('short_user_display_name') || 'Member',
				photoURL: localStorage.getItem('short_user_photo') || localStorage.getItem('shorttv_user_avatar') || ''
			};
		}
		return null;
	}

	function updateCheckoutUserUI(rawUser) {
		var user = getActiveUser(rawUser);
		var loggedInBox = document.getElementById('checkout-user-logged-in');
		var guestBox = document.getElementById('checkout-user-guest');
		var emailEl = document.getElementById('checkout-user-email');
		var avatarEl = document.getElementById('checkout-user-avatar');

		if (user && user.email) {
			if (loggedInBox) loggedInBox.style.display = 'flex';
			if (guestBox) guestBox.style.display = 'none';
			if (emailEl) emailEl.textContent = user.email;
			if (avatarEl) {
				if (user.photoURL) {
					avatarEl.innerHTML = '<img src="' + user.photoURL + '" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">';
				} else {
					avatarEl.textContent = (user.displayName || user.email || 'U').charAt(0).toUpperCase();
				}
			}
		} else {
			if (loggedInBox) loggedInBox.style.display = 'none';
			if (guestBox) guestBox.style.display = 'flex';
		}
	}
	window.updateCheckoutUserUI = updateCheckoutUserUI;

	function updateSubscribedPlanBadges(vipPlanId) {
		var activeKey = '';
		var savedPlan = vipPlanId || localStorage.getItem('short_vip_plan') || localStorage.getItem('short_user_plan') || '';
		if (savedPlan) {
			var norm = String(savedPlan).toLowerCase();
			if (norm.indexOf('week') !== -1 || norm === 'basic') activeKey = 'basic';
			else if (norm.indexOf('month') !== -1 || norm === 'standard') activeKey = 'standard';
			else if (norm.indexOf('annual') !== -1 || norm.indexOf('year') !== -1 || norm === 'premium') activeKey = 'premium';
		}

		if (activeKey) {
			['basic', 'standard', 'premium'].forEach(function(k) {
				var card = document.getElementById('desktop-plan-card-' + k);
				var badge = document.getElementById('plan-badge-current-' + k);
				var btn = document.getElementById('desktop-plan-btn-' + k);
				if (k === activeKey) {
					if (card) card.classList.add('is-current-plan');
					if (badge) badge.style.display = 'inline-flex';
					if (btn) btn.textContent = '<?php echo esc_js( __( 'Current Active Plan', 'short-stream' ) ); ?>';
				} else {
					if (card) card.classList.remove('is-current-plan');
					if (badge) badge.style.display = 'none';
				}
			});
		}
	}
	window.updateSubscribedPlanBadges = updateSubscribedPlanBadges;

	// Initial render
	updateCheckoutUserUI(getActiveUser(null));
	updateSubscribedPlanBadges();

	if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
		firebase.auth().onAuthStateChanged(function(user) {
			updateCheckoutUserUI(user);
			if (user && firebase.database) {
				firebase.database().ref('users/' + user.uid + '/coins').on('value', function(snap) {
					var v = snap.val();
					if (v !== null && v !== undefined) {
						var val = parseInt(v, 10);
						localStorage.setItem('shorttv_user_coins', String(val));
					}
				});
				firebase.database().ref('users/' + user.uid + '/is_vip').on('value', function(snap) {
					if (snap.val() === true) {
						localStorage.setItem('short_is_vip', '1');
						var cancelOpt = document.getElementById('cancel-sub-page-option');
						if (cancelOpt) cancelOpt.style.display = 'block';
					}
				});
				firebase.database().ref('users/' + user.uid + '/vip_plan').on('value', function(snap) {
					var vp = snap.val();
					if (vp) {
						localStorage.setItem('short_vip_plan', vp);
						updateSubscribedPlanBadges(vp);
					}
				});
			}
		});
	}

	// 1-Click Google Sign-In inside checkout modal
	var googleCheckoutBtn = document.getElementById('btn-checkout-google-signin');
	if (googleCheckoutBtn) {
		googleCheckoutBtn.addEventListener('click', async function(e) {
			e.preventDefault();
			triggerGoogleAuth();
		});
	}

	async function triggerGoogleAuth() {
		if (typeof firebase === 'undefined' || !firebase.auth) {
			if (typeof shortCustomAlert === 'function') {
				shortCustomAlert({
					title: 'Please Wait',
					message: 'Firebase authentication is initializing. Please try again in a moment.',
					type: 'info'
				});
			} else {
				alert('Firebase authentication is initializing. Please try again.');
			}
			return;
		}
		try {
			var provider = new firebase.auth.GoogleAuthProvider();
			provider.setCustomParameters({ prompt: 'select_account' });
			var res = await firebase.auth().signInWithPopup(provider);
			if (res && res.user) {
				updateCheckoutUserUI(res.user);
			}
		} catch(err) {
			console.warn('[Checkout Google Signin]', err);
			if (err.code !== 'auth/popup-closed-by-user') {
				if (typeof shortCustomAlert === 'function') {
					shortCustomAlert({
						title: 'Sign-In Failed',
						message: err.message || 'Please try again.',
						type: 'error'
					});
				} else {
					alert('Sign-in failed: ' + (err.message || 'Please try again.'));
				}
			}
		}
	}
});

var currentActiveCoinTab = 'popular';
var coinPlansMap = {
	starter: {
		pack_id: 'starter',
		pack_name: 'Starter Pack (300 Coins)',
		amount: 300,
		price: '1.99',
		period: '/ 300 coins',
		episodes: '<?php esc_attr_e( "~20 Episodes", "short-stream" ); ?>',
		bonus: '<?php esc_attr_e( "+0 Bonus Coins", "short-stream" ); ?>',
		ctaText: '<?php esc_attr_e( "Recharge 300 Coins Now →", "short-stream" ); ?>'
	},
	popular: {
		pack_id: 'popular',
		pack_name: 'Value Pack (1,200 Coins)',
		amount: 1200,
		price: '4.99',
		period: '/ 1,200 coins',
		episodes: '<?php esc_attr_e( "~80 Episodes (Full Drama)", "short-stream" ); ?>',
		bonus: '<?php esc_attr_e( "+200 Bonus Coins", "short-stream" ); ?>',
		ctaText: '<?php esc_attr_e( "Recharge 1,200 Coins Now →", "short-stream" ); ?>'
	},
	super: {
		pack_id: 'super',
		pack_name: 'Super Binge (3,000 Coins)',
		amount: 3000,
		price: '9.99',
		period: '/ 3,000 coins',
		episodes: '<?php esc_attr_e( "~200 Episodes (2–3 Dramas)", "short-stream" ); ?>',
		bonus: '<?php esc_attr_e( "+600 Bonus Coins", "short-stream" ); ?>',
		ctaText: '<?php esc_attr_e( "Recharge 3,000 Coins Now →", "short-stream" ); ?>'
	}
};

var coinPacksMap = {
	starter: {
		pack_id: 'starter',
		pack_name: 'Starter Pack (300 Coins)',
		amount: 300,
		price: '<?php echo esc_js( $sym ); ?>1.99'
	},
	popular: {
		pack_id: 'popular',
		pack_name: 'Value Pack (1,200 Coins)',
		amount: 1200,
		price: '<?php echo esc_js( $sym ); ?>4.99'
	},
	super: {
		pack_id: 'super',
		pack_name: 'Super Binge (3,000 Coins)',
		amount: 3000,
		price: '<?php echo esc_js( $sym ); ?>9.99'
	}
};

function switchCoinPlanTab(packKey) {
	if (!coinPlansMap[packKey]) return;
	currentActiveCoinTab = packKey;
	var data = coinPlansMap[packKey];

	// Update mobile pill buttons
	['starter', 'popular', 'super'].forEach(function(k) {
		var tabBtn = document.getElementById('coin-tab-btn-' + k);
		if (tabBtn) {
			if (k === packKey) {
				tabBtn.classList.add('is-active');
				tabBtn.style.background = 'linear-gradient(135deg, #ff9800, #f57c00)';
				tabBtn.style.color = '#ffffff';
			} else {
				tabBtn.classList.remove('is-active');
				tabBtn.style.background = 'transparent';
				tabBtn.style.color = '#94a3b8';
			}
		}
	});

	// Update mobile view elements
	var cardEl = document.getElementById('coins-tabbed-card');
	var amtEl  = document.getElementById('coin-tabbed-price-amount');
	var perEl  = document.getElementById('coin-tabbed-price-period');
	var epEl   = document.getElementById('coin-check-episodes');
	var bnEl   = document.getElementById('coin-check-bonus');
	var ctaEl  = document.getElementById('coin-mobile-cta-text');

	if (cardEl) cardEl.setAttribute('data-pack-id', data.pack_id);
	if (amtEl) amtEl.textContent = data.price;
	if (perEl) perEl.textContent = data.period;
	if (epEl)  epEl.textContent = data.episodes;
	if (bnEl)  bnEl.textContent = data.bonus;
	if (ctaEl) ctaEl.textContent = data.ctaText;
}

function selectCoinCard(packId) {
	['starter', 'popular', 'super'].forEach(function(k) {
		var c = document.getElementById('coin-card-' + k);
		var b = document.getElementById('coin-btn-' + k);
		if (c) {
			if (k === packId) {
				c.classList.add('is-selected-coin');
				c.style.background = '#201e28';
			} else {
				c.classList.remove('is-selected-coin');
				c.style.background = '#181a20';
			}
		}
		if (b) {
			if (k === packId) {
				b.classList.add('btn-coin-popular');
			} else {
				b.classList.remove('btn-coin-popular');
			}
		}
	});
}

function executeCoinRecharge(packId, event) {
	if (event && event.stopPropagation) {
		event.stopPropagation();
	}
	if (!packId || typeof packId !== 'string') packId = currentActiveCoinTab || 'popular';
	var pack = coinPacksMap[packId] || coinPacksMap.popular;

	var user = (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) ? firebase.auth().currentUser : null;
	var rawLocal = localStorage.getItem('short_user_email');
	var isLocalAuth = (localStorage.getItem('short_is_logged_in') === '1') || (rawLocal && rawLocal !== 'guest@short.local');

	if (!user && !isLocalAuth) {
		if (typeof shortCustomAlert === 'function') {
			shortCustomAlert({
				title: 'Sign In Required',
				message: 'Please sign in with your Google account first so your coins are safely linked to your wallet.',
				type: 'warning'
			});
		} else {
			alert('Please sign in with your Google account first so your coins are safely linked to your wallet.');
		}
		if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
			var provider = new firebase.auth.GoogleAuthProvider();
			provider.setCustomParameters({ prompt: 'select_account' });
			firebase.auth().signInWithPopup(provider).then(function(res) {
				if (res && res.user && res.user.email) {
					window.location.reload();
				}
			}).catch(function(err){});
		}
		return;
	}

	var userEmail = (user && user.email) ? user.email : (rawLocal || 'user@short.local');
	var uid = (user && user.uid) ? user.uid : (localStorage.getItem('short_user_uid') || 'guest_' + Math.random().toString(36).substring(2, 9));

	var customCoinsUrl = (window.SHORT_LS_CONFIG && window.SHORT_LS_CONFIG.coins_urls && window.SHORT_LS_CONFIG.coins_urls[packId]) || (window.SHORT_LS_CONFIG && window.SHORT_LS_CONFIG.coins_urls && window.SHORT_LS_CONFIG.coins_urls.popular) || localStorage.getItem('shorttv_ls_coins_url');
	if (customCoinsUrl) {
		sessionStorage.setItem('short_pending_checkout', JSON.stringify({
			type: 'coins',
			amount: pack.amount,
			pack_name: pack.pack_name,
			price: pack.price,
			pack_id: pack.pack_id
		}));

		var targetUrl = customCoinsUrl;
		try {
			var urlObj = new URL(customCoinsUrl, window.location.origin);
			if (userEmail && userEmail !== 'user@short.local' && userEmail !== 'guest@short.local') {
				urlObj.searchParams.set('checkout[email]', userEmail);
			}
			urlObj.searchParams.set('checkout[custom][user_id]', uid);
			urlObj.searchParams.set('checkout[custom][pack_id]', pack.pack_id);
			urlObj.searchParams.set('checkout[custom][type]', 'coins');
			targetUrl = urlObj.toString();
		} catch(e){}

		if (typeof LemonSqueezy !== 'undefined' && LemonSqueezy.Url) {
			LemonSqueezy.Url.Open(targetUrl);
			return;
		} else {
			window.open(targetUrl, '_blank');
			return;
		}
	}

	var doInstantRecharge = function() {
		var cur = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
		cur += pack.amount;
		localStorage.setItem('shorttv_user_coins', cur.toString());

		if (typeof window.recordShortTransaction === 'function') {
			window.recordShortTransaction({
				amount: pack.amount,
				reason: 'Coin Pack Top-Up (' + pack.pack_name + ' - ' + pack.price + ')',
				type: 'coins',
				plan_id: pack.pack_id,
				status: 'Completed',
				order_id: 'COIN-' + Math.floor(Math.random()*899999+100000),
				ts: Date.now()
			});
		}

		if (user && firebase.database) {
			var dbUid = user.uid;
			var db = firebase.database();
			db.ref('users/' + dbUid + '/coins').set(cur);
			db.ref('users/' + dbUid + '/coinHistory').push({
				amount: pack.amount,
				reason: 'Coin Pack Top-Up (' + pack.pack_name + ' - ' + pack.price + ')',
				ts: Date.now()
			});
		}

		if (typeof shortCustomAlert === 'function') {
			shortCustomAlert({
				title: 'Coins Added! 💰',
				message: pack.amount + ' coins added to your wallet. Balance: ' + cur + ' Coins.',
				type: 'success',
				buttonText: 'Done'
			});
		} else {
			alert('🎉 Success! ' + pack.amount + ' coins added to your wallet. Balance: ' + cur + ' Coins.');
		}
	};

	if (typeof shortCustomConfirm === 'function') {
		shortCustomConfirm({
			title: 'Top Up Coins',
			message: 'Top up ' + pack.pack_name + ' for ' + pack.price + ' (Instant Mode)?',
			confirmText: 'Top Up Now',
			cancelText: 'Cancel'
		}).then(function(confirmed) {
			if (confirmed) doInstantRecharge();
		});
	} else if (confirm('Top up ' + pack.pack_name + ' for ' + pack.price + ' (Instant Mode)?')) {
		doInstantRecharge();
	}
}
</script>

<style>
/* Suppress site header & genre bar completely on Subscription Page */
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

/* Base Desktop Styles */
.short-subscription-page {
	padding: 112px 0 60px 0 !important;
	box-sizing: border-box !important;
	min-height: 100vh !important;
	width: 100% !important;
	background: #121212 !important;
}

.sub-top-navbar {
	position: fixed !important;
	top: 0 !important;
	left: 0 !important;
	right: 0 !important;
	height: 66px !important;
	background: rgba(18, 18, 18, 0.94) !important;
	backdrop-filter: blur(16px) !important;
	-webkit-backdrop-filter: blur(16px) !important;
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	padding: 0 28px !important;
	z-index: 900 !important;
	border: none !important;
	border-bottom: none !important;
	box-shadow: none !important;
	outline: none !important;
	box-sizing: border-box !important;
}

.sub-top-navbar-inner {
	max-width: 1160px !important;
	width: 100% !important;
	margin: 0 auto !important;
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
}

.sub-nav-back {
	color: #fff !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	width: 40px !important;
	height: 40px !important;
	border-radius: 50% !important;
	text-decoration: none !important;
	background: transparent !important;
	border: none !important;
	box-shadow: none !important;
	outline: none !important;
	transition: color 0.2s ease, transform 0.2s ease !important;
	cursor: pointer !important;
}

.sub-nav-back:hover {
	background: transparent !important;
	color: var(--theme-accent, #ff2d55) !important;
	transform: translateX(-3px) !important;
}

.sub-nav-back svg {
	width: 26px !important;
	height: 26px !important;
}

.sub-top-nav-title {
	color: #fff !important;
	font-size: 22px !important;
	font-weight: 800 !important;
	margin: 0 !important;
	letter-spacing: -0.3px !important;
	text-align: center !important;
	flex: 1 !important;
}

.sub-nav-spacer {
	width: 40px !important;
	height: 40px !important;
	flex-shrink: 0 !important;
}

.sub-page-container {
	max-width: 960px !important;
	margin: 0 auto !important;
	width: 100% !important;
	box-sizing: border-box !important;
	padding: 0 16px !important;
}

.sub-header-section {
	margin-top: 32px !important;
	margin-bottom: 28px !important;
	padding: 0 !important;
	text-align: center !important;
}

/* ============================================================== */
/* DESKTOP 3-CARD GRID (EXPANDED & SPACIOUS) */
/* ============================================================== */
.vip-desktop-cards-grid {
	display: grid !important;
	grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
	gap: 24px !important;
	align-items: stretch !important;
	margin: 0 auto 36px auto !important;
	width: 100% !important;
	max-width: 1160px !important;
	box-sizing: border-box !important;
	padding: 0 !important;
	overflow: visible !important;
}

.sub-plan-card {
	position: relative !important;
	background: #181924 !important;
	border: 1px solid rgba(255, 255, 255, 0.06) !important;
	outline: none !important;
	border-radius: 20px !important;
	padding: 30px 26px 24px 26px !important;
	display: flex !important;
	flex-direction: column !important;
	justify-content: space-between !important;
	box-sizing: border-box !important;
	transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
	color: #fff !important;
	box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3) !important;
	overflow: visible !important;
	cursor: pointer !important;
}

.sub-plan-card:hover {
	transform: translateY(-4px) !important;
	border-color: rgba(255, 255, 255, 0.16) !important;
	box-shadow: 0 8px 30px rgba(0, 0, 0, 0.45) !important;
}

.sub-plan-card.is-selected {
	border: 1px solid rgba(var(--theme-accent-rgb, 229, 9, 20), 0.4) !important;
	outline: none !important;
	background: #201e28 !important;
	box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5) !important;
}

.sub-plan-card.is-current-plan {
	border: 1.5px solid #10b981 !important;
	box-shadow: 0 0 24px rgba(16, 185, 129, 0.2) !important;
}

.plan-card-header {
	margin-bottom: 8px !important;
	display: flex !important;
	flex-direction: column !important;
	align-items: flex-start !important;
	justify-content: flex-start !important;
	gap: 4px !important;
	width: 100% !important;
	text-align: left !important;
}

.plan-badge-sub {
	display: inline-block !important;
	background: rgba(255,255,255,0.08) !important;
	color: #94a3b8 !important;
	font-size: 10px !important;
	font-weight: 800 !important;
	padding: 3px 8px !important;
	border-radius: 6px !important;
	letter-spacing: 0.5px !important;
	margin: 0 !important;
	align-self: flex-start !important;
}

.plan-badge-popular {
	display: inline-block !important;
	background: var(--theme-accent, #ff2d55) !important;
	color: #fff !important;
	font-size: 10px !important;
	font-weight: 800 !important;
	padding: 3px 8px !important;
	border-radius: 6px !important;
	letter-spacing: 0.5px !important;
	margin: 0 !important;
	align-self: flex-start !important;
}

.plan-badge-current {
	display: inline-flex !important;
	align-items: center !important;
	gap: 4px !important;
	background: #10b981 !important;
	color: #ffffff !important;
	font-size: 10px !important;
	font-weight: 900 !important;
	padding: 3px 9px !important;
	border-radius: 999px !important;
	letter-spacing: 0.5px !important;
	box-shadow: 0 2px 10px rgba(16, 185, 129, 0.4) !important;
}

.plan-name {
	font-size: 21px !important;
	font-weight: 800 !important;
	margin: 2px 0 0 !important;
	color: #fff !important;
	line-height: 1.2 !important;
	display: block !important;
	width: 100% !important;
	text-align: left !important;
}

.plan-subtitle {
	font-size: 12px !important;
	color: #94a3b8 !important;
	margin: 0 !important;
	line-height: 1.35 !important;
	display: block !important;
	width: 100% !important;
	text-align: left !important;
}

.plan-price-box {
	margin: 10px 0 16px 0 !important;
	display: flex !important;
	align-items: baseline !important;
	gap: 2px !important;
}

.plan-currency {
	font-size: 18px !important;
	font-weight: 800 !important;
	color: #fff !important;
}

.plan-amount {
	font-size: 36px !important;
	font-weight: 900 !important;
	color: #fff !important;
	line-height: 1 !important;
}

.plan-period {
	font-size: 13.5px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	margin-left: 2px !important;
}

.plan-features-list {
	list-style: none !important;
	padding: 0 !important;
	margin: 0 0 22px 0 !important;
	display: flex !important;
	flex-direction: column !important;
	gap: 11px !important;
	width: 100% !important;
	text-align: left !important;
}

.plan-feature-row {
	display: flex !important;
	flex-direction: row !important;
	align-items: center !important;
	justify-content: flex-start !important;
	text-align: left !important;
	gap: 9px !important;
	font-size: 13px !important;
	color: #cbd5e1 !important;
	line-height: 1.35 !important;
	width: 100% !important;
}

.plan-feature-row svg {
	width: 15px !important;
	height: 15px !important;
	min-width: 15px !important;
	max-width: 15px !important;
	flex-shrink: 0 !important;
	margin: 0 !important;
	stroke: #22c55e !important;
}

.plan-feature-row span {
	font-size: 13px !important;
	color: #cbd5e1 !important;
	line-height: 1.35 !important;
	text-align: left !important;
	white-space: normal !important;
	flex: 1 1 auto !important;
}

.btn-select-plan {
	width: 100% !important;
	height: 44px !important;
	border-radius: 999px !important;
	background: #2a2d37 !important;
	color: #fff !important;
	font-size: 14px !important;
	font-weight: 800 !important;
	border: none !important;
	outline: none !important;
	cursor: pointer !important;
	transition: all 0.2s ease !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
}

.btn-select-plan:hover {
	background: #333845 !important;
	color: #fff !important;
}

.sub-plan-card.is-selected .btn-select-plan,
.btn-popular-gradient {
	background: var(--theme-accent, #ff2d55) !important;
	color: #ffffff !important;
	border: none !important;
	box-shadow: 0 4px 14px var(--theme-accent-glow, rgba(255, 45, 85, 0.4)) !important;
}

.sub-plan-card.is-selected .btn-select-plan:hover,
.btn-popular-gradient:hover {
	filter: brightness(1.1) !important;
}

/* ============================================================== */
/* MOBILE TABBED VIEW (HIDDEN ON DESKTOP) */
/* ============================================================== */
.vip-mobile-tabbed-view,
.coins-mobile-tabbed-view {
	display: none !important;
}

/* ============================================================== */
/* SHARED / DIRECT STYLING FOR MOBILE TAB */
/* ============================================================== */
.vip-direct-title {
	color: #ffffff !important;
	font-size: 20px !important;
	font-weight: 800 !important;
	margin: 0 0 6px 0 !important;
	letter-spacing: -0.4px !important;
	line-height: 1.25 !important;
	display: flex !important;
	align-items: center !important;
	justify-content: flex-start !important;
	gap: 8px !important;
}

.vip-direct-sub {
	color: #94a3b8 !important;
	font-size: 13px !important;
	line-height: 1.45 !important;
	margin: 0 0 16px 0 !important;
}

.vip-plan-pills-bar {
	display: flex !important;
	align-items: center !important;
	justify-content: space-between !important;
	background: rgba(255, 255, 255, 0.06) !important;
	border: 1px solid rgba(255, 255, 255, 0.08) !important;
	border-radius: 999px !important;
	padding: 3px !important;
	gap: 3px !important;
	box-sizing: border-box !important;
	margin-bottom: 14px !important;
}

.vip-plan-pill-tab {
	flex: 1 1 0 !important;
	background: transparent !important;
	border: none !important;
	outline: none !important;
	border-radius: 999px !important;
	padding: 8px 6px !important;
	color: #94a3b8 !important;
	font-size: 13px !important;
	font-weight: 700 !important;
	cursor: pointer !important;
	transition: all 0.2s ease !important;
	text-align: center !important;
	white-space: nowrap !important;
}

.vip-plan-pill-tab.is-active {
	background: #2563eb !important;
	color: #ffffff !important;
	font-weight: 800 !important;
	box-shadow: 0 4px 12px rgba(37, 99, 235, 0.45) !important;
}

.vip-direct-price-row {
	display: flex !important;
	align-items: baseline !important;
	justify-content: flex-start !important;
	gap: 2px !important;
	line-height: 1 !important;
	margin: 12px 0 16px 0 !important;
}

.vip-direct-currency {
	font-size: 24px !important;
	font-weight: 800 !important;
	color: #ffffff !important;
	vertical-align: super !important;
}

.vip-direct-amount {
	font-size: 44px !important;
	font-weight: 900 !important;
	color: #ffffff !important;
	line-height: 1 !important;
	letter-spacing: -1.5px !important;
}

.vip-direct-period {
	font-size: 14px !important;
	font-weight: 700 !important;
	color: #94a3b8 !important;
	margin-left: 2px !important;
}

.vip-perks-grid-2col {
	display: grid !important;
	grid-template-columns: 1fr 1fr !important;
	gap: 12px 14px !important;
	margin-bottom: 18px !important;
}

.vip-perk-item {
	display: flex !important;
	align-items: center !important;
	gap: 8px !important;
	font-size: 13px !important;
	color: #e2e8f0 !important;
	font-weight: 600 !important;
}

.vip-perk-green-check {
	width: 20px !important;
	height: 20px !important;
	border-radius: 50% !important;
	background: #10b981 !important;
	color: #ffffff !important;
	display: inline-flex !important;
	align-items: center !important;
	justify-content: center !important;
	font-size: 11px !important;
	font-weight: 900 !important;
	flex-shrink: 0 !important;
}

.vip-trust-cards-stack {
	display: flex !important;
	flex-direction: column !important;
	gap: 10px !important;
	margin-bottom: 18px !important;
}

.vip-trust-card {
	border-radius: 12px !important;
	padding: 11px 12px !important;
	display: flex !important;
	align-items: center !important;
	gap: 12px !important;
	box-sizing: border-box !important;
}

.trust-card-indigo {
	background: rgba(99, 102, 241, 0.08) !important;
	border: 1px solid rgba(99, 102, 241, 0.22) !important;
}

.trust-card-green {
	background: rgba(16, 185, 129, 0.08) !important;
	border: 1px solid rgba(16, 185, 129, 0.22) !important;
}

.vip-trust-icon-wrap {
	width: 32px !important;
	height: 32px !important;
	border-radius: 8px !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	flex-shrink: 0 !important;
}

.indigo-icon {
	background: rgba(99, 102, 241, 0.16) !important;
}

.green-icon {
	background: rgba(16, 185, 129, 0.16) !important;
}

.vip-trust-info strong {
	display: block !important;
	color: #ffffff !important;
	font-size: 12.5px !important;
	font-weight: 700 !important;
	line-height: 1.2 !important;
	margin-bottom: 2px !important;
}

.vip-trust-info p {
	margin: 0 !important;
	color: #94a3b8 !important;
	font-size: 11px !important;
	line-height: 1.35 !important;
}

.btn-proceed-payment-pill {
	width: 100% !important;
	height: 50px !important;
	border-radius: 999px !important;
	background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 50%, #3b82f6 100%) !important;
	color: #ffffff !important;
	font-size: 15.5px !important;
	font-weight: 800 !important;
	border: none !important;
	outline: none !important;
	cursor: pointer !important;
	box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45) !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	box-sizing: border-box !important;
}

/* Coin Pack Cards Clean Layout */
.stv-coin-pack-card {
	position: relative !important;
	background: #181a20 !important;
	border: none !important;
	outline: none !important;
	border-radius: 16px !important;
	padding: 22px 18px 18px 18px !important;
	text-align: center !important;
	cursor: pointer !important;
	transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
	box-sizing: border-box !important;
	box-shadow: none !important;
}
.stv-coin-pack-card:hover {
	transform: translateY(-2px) !important;
	box-shadow: none !important;
}
.stv-coin-pack-card.is-popular {
	border: none !important;
	outline: none !important;
	background: #201e28 !important;
	box-shadow: none !important;
}
.stv-coin-pack-badge {
	position: absolute !important;
	top: -11px !important;
	left: 50% !important;
	transform: translateX(-50%) !important;
	white-space: nowrap !important;
	z-index: 5 !important;
	font-size: 10px !important;
	font-weight: 800 !important;
	padding: 3px 12px !important;
	border-radius: 10px !important;
	letter-spacing: 0.5px !important;
}
.stv-coin-pack-icon {
	font-size: 32px !important;
	margin-bottom: 6px !important;
	line-height: 1 !important;
}
.stv-coin-pack-title {
	color: #fff !important;
	font-size: 19px !important;
	font-weight: 800 !important;
	margin-bottom: 3px !important;
}
.stv-coin-pack-sub {
	font-size: 12px !important;
	margin-bottom: 12px !important;
}
.stv-coin-pack-price {
	font-size: 24px !important;
	font-weight: 900 !important;
	margin-bottom: 14px !important;
}
.btn-select-coin-pack {
	width: 100% !important;
	height: 38px !important;
	border-radius: 999px !important;
	background: #2a2d37 !important;
	color: #ffffff !important;
	font-size: 13px !important;
	font-weight: 800 !important;
	border: none !important;
	outline: none !important;
	cursor: pointer !important;
	transition: all 0.2s ease !important;
	display: flex !important;
	align-items: center !important;
	justify-content: center !important;
	box-shadow: none !important;
}
.btn-select-coin-pack:hover {
	background: #333845 !important;
	color: #ffffff !important;
}
.btn-coin-popular {
	background: linear-gradient(135deg, #ff9800, #ff5722) !important;
	color: #ffffff !important;
	font-weight: 800 !important;
}
.btn-coin-popular:hover {
	filter: brightness(1.1) !important;
}

/* ============================================================== */
/* MOBILE COMPACT ADJUSTMENTS (TAB VIEW, NO CARD BG/BORDER/PADDING) */
/* ============================================================== */
@media (max-width: 768px) {
	.short-subscription-page {
		padding: 68px 12px 30px 12px !important;
	}
	.sub-page-container {
		width: 100% !important;
		max-width: 100% !important;
		padding: 0 !important;
		box-sizing: border-box !important;
	}
	
	/* Hide desktop 3-card grid on mobile */
	.vip-desktop-cards-grid,
	.coins-desktop-cards-grid {
		display: none !important;
	}

	/* Show mobile tabbed view */
	.vip-mobile-tabbed-view,
	.coins-mobile-tabbed-view {
		display: block !important;
		width: 100% !important;
		max-width: 100% !important;
		margin: 0 !important;
		padding: 0 !important;
	}

	/* NO card background, NO card border, NO bulky padding on mobile */
	.vip-mobile-tab-wrapper {
		background: transparent !important;
		background-color: transparent !important;
		border: none !important;
		box-shadow: none !important;
		padding: 0 4px !important;
		width: 100% !important;
		box-sizing: border-box !important;
	}

	.vip-mobile-inner-section {
		background: transparent !important;
		border: none !important;
		padding: 0 !important;
		margin-bottom: 14px !important;
	}

	.sub-top-navbar {
		height: 56px !important;
		padding: 0 14px !important;
	}
	.sub-nav-back {
		width: 36px !important;
		height: 36px !important;
		background: transparent !important;
		border: none !important;
	}
	.sub-nav-back svg {
		width: 22px !important;
		height: 22px !important;
	}
	.sub-top-nav-title {
		font-size: 18px !important;
	}
	.sub-nav-spacer {
		width: 36px !important;
		height: 36px !important;
	}

	.sub-header-section {
		margin-top: 0 !important;
		margin-bottom: 14px !important;
		text-align: center !important;
	}
	.sub-step-badge {
		font-size: 10px !important;
		padding: 4px 10px !important;
		margin-bottom: 8px !important;
		display: inline-block !important;
	}
	.sub-title {
		font-size: 1.35rem !important;
		line-height: 1.25 !important;
		margin-bottom: 10px !important;
	}
	.sub-bullet-points {
		gap: 6px !important;
		font-size: 12px !important;
		padding: 0 !important;
		margin: 0 auto !important;
		max-width: 100% !important;
	}

	/* Tab Switcher (VIP / Coins) */
	.sub-tab-switcher-container {
		margin: 16px auto 32px auto !important;
	}
	.sub-tab-pill-box {
		width: 100% !important;
		max-width: 340px !important;
		padding: 4px !important;
		box-sizing: border-box !important;
		gap: 4px !important;
	}
	.sub-tab-btn {
		flex: 1 1 50% !important;
		justify-content: center !important;
		padding: 8px 10px !important;
		font-size: 12.5px !important;
		gap: 5px !important;
		white-space: nowrap !important;
		line-height: 1 !important;
	}

	/* Ensure desktop coin cards grid is completely hidden on mobile */
	.coins-desktop-cards-grid,
	.sub-coin-packs-grid {
		display: none !important;
	}
}
</style>

<?php
get_footer();
