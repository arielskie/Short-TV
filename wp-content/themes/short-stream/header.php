<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="theme-color" content="#121212">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<script>
	(function() {
		try {
			// Respect actual login state without auto-forcing demo user to be logged in
			if (localStorage.getItem('short_is_logged_in') === '1' || localStorage.getItem('short_user_email')) {
				document.documentElement.classList.remove('is-guest-state');
			} else {
				document.documentElement.classList.add('is-guest-state');
			}

			// Display Mode (Cinematic Dark, OLED Pitch Black, Midnight Navy)
			var savedMode = localStorage.getItem('short_display_mode') || 'cinematic-dark';
			document.documentElement.setAttribute('data-display-mode', savedMode);
			var targetModeBg = (savedMode === 'oled-black' || savedMode === 'oled') ? '#000000' : (savedMode === 'midnight-navy' || savedMode === 'navy' ? '#0b101b' : '#121212');
			var targetModeRgb = (savedMode === 'oled-black' || savedMode === 'oled') ? '0, 0, 0' : (savedMode === 'midnight-navy' || savedMode === 'navy' ? '11, 16, 27' : '18, 18, 18');
			document.documentElement.style.setProperty('--bg-app', targetModeBg);
			document.documentElement.style.setProperty('--bg-app-rgb', targetModeRgb);
			document.documentElement.style.setProperty('--bg-header', targetModeBg);
			document.documentElement.style.setProperty('--bg-nav-bottom', targetModeBg);
			document.documentElement.style.setProperty('--bg-nav-top', targetModeBg);
			document.documentElement.style.setProperty('--bg-footer', targetModeBg);
			document.documentElement.style.setProperty('--bg-primary', targetModeBg);
			document.documentElement.style.setProperty('--bg-body', targetModeBg);

			var savedColor = localStorage.getItem('short_theme_color') || '#E50914';
			if (savedColor) {
				document.documentElement.style.setProperty('--accent', savedColor);
				document.documentElement.style.setProperty('--theme-accent', savedColor);
				document.documentElement.style.setProperty('--btn-primary-bg', savedColor);
				document.documentElement.style.setProperty('--player-accent', savedColor);
				// Also parse rgb
				var hex = savedColor.replace('#', '');
				if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
				if (hex.length === 6) {
					var r = parseInt(hex.substring(0,2), 16);
					var g = parseInt(hex.substring(2,4), 16);
					var b = parseInt(hex.substring(4,6), 16);
					document.documentElement.style.setProperty('--theme-accent-rgb', r + ', ' + g + ', ' + b);
					document.documentElement.style.setProperty('--accent-rgb', r + ', ' + g + ', ' + b);
					document.documentElement.style.setProperty('--theme-accent-glow', 'rgba(' + r + ', ' + g + ', ' + b + ', 0.45)');
				}
			}
		} catch(e) {}

		<?php
		$security_settings = get_option('short_security_settings', array());
		if ( empty($security_settings['allow_right_click']) ) :
		?>
		// Anti-Inspection & Right Click Protection
		document.addEventListener('contextmenu', function(e) {
			e.preventDefault();
			return false;
		}, { capture: true });

		document.addEventListener('keydown', function(e) {
			// F12
			if (e.key === 'F12' || e.keyCode === 123) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
			// Ctrl+Shift+I / J / C / K (Inspect / Console / Element Picker)
			if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c' || e.key === 'K' || e.key === 'k' || e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67 || e.keyCode === 75)) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
			// Mac: Cmd+Option+I / J / C
			if (e.metaKey && e.altKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c' || e.keyCode === 73 || e.keyCode === 74 || e.keyCode === 67)) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
			// Ctrl+U / Cmd+Option+U (View Source)
			if ((e.ctrlKey && (e.key === 'u' || e.key === 'U' || e.keyCode === 85)) || (e.metaKey && e.altKey && (e.key === 'u' || e.key === 'U' || e.keyCode === 85))) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
			// Ctrl+S / Cmd+S (Save Page)
			if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S' || e.keyCode === 83)) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
		}, { capture: true });
		<?php endif; ?>
	})();
	</script>
	<!-- Global Dynamic Theme Accent CSS -->
	<style id="short-theme-dynamic-accent-style">
		:root {
			--theme-accent: #E50914;
			--theme-accent-rgb: 229, 9, 20;
			--theme-accent-glow: rgba(229, 9, 20, 0.45);
			--accent: var(--theme-accent);
			--accent-rgb: var(--theme-accent-rgb);
			--player-accent: var(--theme-accent);
			--btn-primary-bg: var(--theme-accent);
			--brand-primary: var(--theme-accent);
			--bg-app: #121212;
			--bg-app-rgb: 18, 18, 18;
			--bg-header: #121212;
			--bg-nav-bottom: #121212;
			--bg-nav-top: #121212;
			--bg-footer: #121212;
			--bg-primary: #121212;
			--bg-body: #121212;
		}

		/* Display Mode 1: Cinematic Dark (Default) */
		:root,
		[data-display-mode="cinematic-dark"],
		[data-display-mode="dark"] {
			--bg-app: #121212;
			--bg-app-rgb: 18, 18, 18;
			--bg-header: #121212;
			--bg-nav-bottom: #121212;
			--bg-nav-top: #121212;
			--bg-footer: #121212;
			--bg-primary: #121212;
			--bg-body: #121212;
		}

		/* Display Mode 2: OLED Pitch Black */
		[data-display-mode="oled-black"],
		[data-display-mode="oled"],
		[data-display-mode="black"] {
			--bg-app: #000000;
			--bg-app-rgb: 0, 0, 0;
			--bg-header: #000000;
			--bg-nav-bottom: #000000;
			--bg-nav-top: #000000;
			--bg-footer: #000000;
			--bg-primary: #000000;
			--bg-body: #000000;
		}

		/* Display Mode 3: Midnight Navy */
		[data-display-mode="midnight-navy"],
		[data-display-mode="navy"] {
			--bg-app: #0b101b;
			--bg-app-rgb: 11, 16, 27;
			--bg-header: #0b101b;
			--bg-nav-bottom: #0b101b;
			--bg-nav-top: #0b101b;
			--bg-footer: #0b101b;
			--bg-primary: #0b101b;
			--bg-body: #0b101b;
		}

		/* Hero Vignette & Seamless Background Overlays */
		.reel-hero-overlay-vertical {
			position: absolute !important;
			inset: 0 !important;
			pointer-events: none !important;
			background: linear-gradient(
				180deg,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.85) 0%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.2) 30%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.5) 65%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.92) 90%,
				var(--bg-app, #121212) 100%
			) !important;
		}

		.reel-hero-overlay-left {
			position: absolute !important;
			inset: 0 !important;
			pointer-events: none !important;
			background: linear-gradient(
				90deg,
				var(--bg-app, #121212) 0%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.95) 8%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.7) 25%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.15) 50%,
				transparent 70%
			) !important;
		}

		.reel-hero-overlay-right {
			position: absolute !important;
			inset: 0 !important;
			pointer-events: none !important;
			background: linear-gradient(
				270deg,
				var(--bg-app, #121212) 0%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.95) 8%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.65) 20%,
				transparent 50%
			) !important;
		}

		.hero-vignette-bottom {
			position: absolute !important;
			bottom: 0 !important;
			left: 0 !important;
			right: 0 !important;
			height: 320px !important;
			background: linear-gradient(
				180deg,
				transparent 0%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.6) 40%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.95) 85%,
				var(--bg-app, #121212) 100%
			) !important;
			pointer-events: none !important;
			z-index: 3 !important;
		}

		.details-hero-vignette {
			position: absolute !important;
			inset: 0 !important;
			background: linear-gradient(
				0deg,
				var(--bg-app, #121212) 0%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.7) 50%,
				rgba(var(--bg-app-rgb, 18, 18, 18), 0.9) 100%
			) !important;
			z-index: 1 !important;
		}

		/* Global Header, Footer, Body & Mobile Nav Unification (Seamless Background, Zero Border, Zero Shadow) */
		html,
		body,
		#page,
		.site,
		.short-main-content,
		.reel-home-page,
		.short-homepage-wrapper,
		.reel-genre-page,
		.short-leaderboard-page-wrapper,
		.short-account-page-wrapper,
		.short-settings-page-wrapper,
		.settings-view-pane,
		.short-reward-page-wrap,
		.short-header,
		.site-header,
		.reel-header,
		.short-header.scrolled,
		.site-header.scrolled,
		.reel-header.scrolled,
		.short-footer,
		.site-footer,
		.short-footer-container,
		.footer-bottom-bar,
		.short-mobile-bottom-nav,
		.mobile-bottom-nav,
		.settings-top-navbar,
		.reward-mobile-top-navbar,
		.short-mobile-header-bar,
		.short-mobile-genre-bar {
			background: var(--bg-app) !important;
			background-color: var(--bg-app) !important;
			border: none !important;
			border-top: none !important;
			border-bottom: none !important;
			border-left: none !important;
			border-right: none !important;
			box-shadow: none !important;
			-webkit-box-shadow: none !important;
			outline: none !important;
		}

		/* Transparent header only on home hero before scroll */
		body.home .reel-header:not(.scrolled),
		body.page-template-front-page .reel-header:not(.scrolled),
		body.home .short-header:not(.scrolled),
		body.page-template-front-page .short-header:not(.scrolled),
		.reel-header:not(.scrolled),
		.short-header:not(.scrolled) {
			background: linear-gradient(180deg, rgba(0, 0, 0, 0.85) 0%, transparent 100%) !important;
			background-color: transparent !important;
			border: none !important;
			box-shadow: none !important;
		}

		/* Solid background during scrolling (identical to body and footer via --bg-header) */
		.short-header.scrolled,
		.site-header.scrolled,
		.reel-header.scrolled,
		body.scrolled .reel-header,
		body.scrolled .short-header {
			background: var(--bg-header) !important;
			background-color: var(--bg-header) !important;
			backdrop-filter: none !important;
			-webkit-backdrop-filter: none !important;
			filter: none !important;
			box-shadow: none !important;
			-webkit-box-shadow: none !important;
			border: none !important;
			border-bottom: none !important;
		}

		.short-footer-container,
		.footer-bottom-bar {
			border: none !important;
			border-top: none !important;
			border-bottom: none !important;
			box-shadow: none !important;
		}

		/* Single-Row Header Icon / Text Buttons (Watchlist & My List) */
		.reel-header-mylist-btn,
		.reel-header-mhistory-btn {
			height: 32px !important;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
			padding: 4px 8px !important;
			background: transparent !important;
			border: none !important;
			color: rgba(255, 255, 255, 0.85) !important;
			text-decoration: none !important;
			cursor: pointer !important;
			transition: color 0.15s ease, opacity 0.15s ease !important;
			border-radius: 6px !important;
			box-sizing: border-box !important;
		}
		.reel-header-mylist-btn svg,
		.reel-header-mhistory-btn svg {
			width: 16px !important;
			height: 16px !important;
			stroke: currentColor !important;
			flex-shrink: 0 !important;
			transition: stroke 0.15s ease, transform 0.15s ease !important;
		}
		.reel-header-mylist-btn .reel-header-btn-text,
		.reel-header-mhistory-btn .reel-header-btn-text {
			font-size: 13.5px !important;
			font-weight: 600 !important;
			color: rgba(255, 255, 255, 0.85) !important;
			margin-left: 6px !important;
			white-space: nowrap !important;
			letter-spacing: -0.1px !important;
			transition: color 0.15s ease !important;
		}
		.reel-header-mylist-btn:hover,
		.reel-header-mhistory-btn:hover {
			color: var(--theme-accent, #ff2d55) !important;
			background: transparent !important;
			opacity: 1 !important;
		}
		.reel-header-mylist-btn:hover svg,
		.reel-header-mhistory-btn:hover svg {
			stroke: var(--theme-accent, #ff2d55) !important;
			transform: scale(1.08) !important;
		}
		.reel-header-mylist-btn:hover .reel-header-btn-text,
		.reel-header-mhistory-btn:hover .reel-header-btn-text {
			color: var(--theme-accent, #ff2d55) !important;
		}
		.short-mobile-genre-bar {
			display: none !important;
		}

		/* Global Profile Dropdown Menu */
		.profile-dropdown-menu {
			display: none !important;
			position: absolute !important;
			top: calc(100% + 8px) !important;
			right: 0 !important;
			width: 220px !important;
			background: #18181c !important;
			border: 1px solid #2e2e36 !important;
			border-radius: 12px !important;
			box-shadow: 0 14px 36px rgba(0, 0, 0, 0.85) !important;
			padding: 8px 0 !important;
			z-index: 999999 !important;
			backdrop-filter: blur(14px) !important;
			-webkit-backdrop-filter: blur(14px) !important;
		}
		.profile-dropdown-menu.show {
			display: block !important;
		}
		.short-profile-menu,
		.user-profile-widget {
			position: relative !important;
			display: inline-flex !important;
			align-items: center !important;
		}
		.profile-dropdown-userinfo {
			padding: 12px 16px !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
			margin-bottom: 6px !important;
		}
		.dropdown-item {
			display: flex !important;
			align-items: center !important;
			gap: 12px !important;
			width: 100% !important;
			text-align: left !important;
			padding: 9px 18px !important;
			font-size: 0.86rem !important;
			font-weight: 500 !important;
			color: #e5e5e5 !important;
			background: none !important;
			border: none !important;
			cursor: pointer !important;
			text-decoration: none !important;
			transition: background 0.15s ease, color 0.15s ease !important;
			box-sizing: border-box !important;
		}
		.dropdown-item:hover {
			background: rgba(255, 255, 255, 0.12) !important;
			color: #ffffff !important;
		}
		.dropdown-item.dropdown-item-accent {
			color: var(--theme-accent, #ff2d55) !important;
		}

		/* Auth Dropdown Buttons Toggle (Strict Mutual Exclusion) */
		html.is-guest-state #short-btn-logout,
		html.is-guest-state .profile-dropdown-menu .logout-btn {
			display: none !important;
		}
		html.is-guest-state #short-btn-login-nav,
		html.is-guest-state .profile-dropdown-menu #short-btn-login-nav {
			display: flex !important;
		}
		html:not(.is-guest-state) #short-btn-logout,
		html:not(.is-guest-state) .profile-dropdown-menu .logout-btn {
			display: flex !important;
		}
		html:not(.is-guest-state) #short-btn-login-nav,
		html:not(.is-guest-state) .profile-dropdown-menu #short-btn-login-nav {
			display: none !important;
		}

		/* Search Box Desktop Defaults (Capsule with divider and search icon) */
		.short-search-box {
			position: relative !important;
			display: inline-flex !important;
			align-items: center !important;
			background: rgba(255, 255, 255, 0.08) !important;
			border: 1px solid rgba(255, 255, 255, 0.12) !important;
			border-radius: 8px !important;
			padding: 0 12px 0 14px !important;
			height: 36px !important;
			width: 280px !important;
			min-width: 260px !important;
			box-sizing: border-box !important;
			transition: all 0.25s ease !important;
		}
		.short-search-box:hover,
		.short-search-box:focus-within {
			border-color: rgba(255, 255, 255, 0.28) !important;
			background: rgba(255, 255, 255, 0.12) !important;
			width: 340px !important;
		}
		.short-search-inner {
			display: inline-flex !important;
			align-items: center !important;
			background: transparent !important;
			border: none !important;
			box-shadow: none !important;
			padding: 0 !important;
			margin: 0 !important;
			width: 100% !important;
			height: 100% !important;
			flex: 1 !important;
		}
		.short-search-inner .live-search-input {
			background: transparent !important;
			border: none !important;
			outline: none !important;
			box-shadow: none !important;
			color: #ffffff !important;
			font-size: 13.5px !important;
			font-weight: 400 !important;
			padding: 0 !important;
			margin: 0 !important;
			flex: 1 !important;
			width: 100% !important;
			min-width: 0 !important;
		}
		.short-search-inner .live-search-input::placeholder {
			color: rgba(255, 255, 255, 0.5) !important;
			font-size: 13px !important;
		}
		.short-search-inner .search-input-divider {
			display: inline-block !important;
			width: 1px !important;
			height: 16px !important;
			background: rgba(255, 255, 255, 0.2) !important;
			margin: 0 10px !important;
			flex-shrink: 0 !important;
		}
		.short-search-inner .search-toggle-btn {
			background: transparent !important;
			border: none !important;
			color: rgba(255, 255, 255, 0.75) !important;
			padding: 0 !important;
			margin: 0 !important;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
			cursor: pointer !important;
			flex-shrink: 0 !important;
			transition: color 0.15s ease !important;
		}
		.short-search-inner .search-toggle-btn:hover {
			color: #ffffff !important;
		}
		.short-search-inner .search-toggle-btn svg {
			width: 16px !important;
			height: 16px !important;
			display: block !important;
		}
		.short-search-inner .mobile-search-close-btn {
			display: none !important;
			background: transparent !important;
			border: none !important;
			color: #94a3b8 !important;
			padding: 0 !important;
			cursor: pointer !important;
		}

		@media (min-width: 769px) {
			header#short-header,
			.short-header,
			.site-header,
			.reel-header {
				display: flex !important;
				flex-direction: row !important;
				align-items: center !important;
				justify-content: space-between !important;
				height: 64px !important;
				min-height: 64px !important;
				max-height: 64px !important;
				padding: 12px 28px !important;
				width: 100% !important;
				position: fixed !important;
				top: 0 !important;
				left: 0 !important;
				right: 0 !important;
				z-index: 9999 !important;
				box-sizing: border-box !important;
			}
			.short-header-container,
			.header-container {
				display: flex !important;
				flex-direction: row !important;
				align-items: center !important;
				justify-content: space-between !important;
				width: 100% !important;
				max-width: 100% !important;
				height: 100% !important;
				padding: 0 !important;
				margin: 0 !important;
				box-sizing: border-box !important;
			}
			.short-header-left {
				display: flex !important;
				flex-direction: row !important;
				align-items: center !important;
				gap: 24px !important;
				flex: 0 0 auto !important;
				height: 100% !important;
			}
			.short-header-right,
			.reel-header-right {
				display: flex !important;
				flex-direction: row !important;
				align-items: center !important;
				justify-content: flex-end !important;
				gap: 14px !important;
				margin-left: auto !important;
				flex: 0 0 auto !important;
				height: 100% !important;
			}
			.short-nav-desktop,
			.reel-nav-desktop {
				display: flex !important;
				flex-direction: row !important;
				align-items: center !important;
				gap: 8px !important;
			}
			.short-nav-desktop a,
			.reel-nav-desktop a {
				color: #94a3b8 !important;
				font-size: 14px !important;
				font-weight: 600 !important;
				text-decoration: none !important;
				padding: 6px 12px !important;
				border-radius: 8px !important;
				transition: all 0.2s ease !important;
				white-space: nowrap !important;
			}
			.short-nav-desktop a:hover,
			.short-nav-desktop a.active,
			.reel-nav-desktop a:hover,
			.reel-nav-desktop a.active {
				color: #ffffff !important;
				background: rgba(255, 255, 255, 0.08) !important;
			}
			.short-mobile-genre-bar,
			.short-mobile-bottom-nav {
				display: none !important;
			}
			.reel-header-mylist-btn,
			.reel-header-mhistory-btn {
				display: inline-flex !important;
				padding: 0 10px !important;
			}
			.reel-header-mylist-btn .reel-header-btn-text,
			.reel-header-mhistory-btn .reel-header-btn-text {
				display: inline-block !important;
			}
			.reel-nav-desktop a[data-nav="my-list"],
			.reel-nav-desktop a[data-nav="mylist"],
			.reel-nav-desktop a[href*="my-list"] {
				display: none !important;
			}
		}
		@media (max-width: 768px) {
			.reel-header-mylist-btn,
			.reel-header-mhistory-btn {
				width: 32px !important;
				min-width: 32px !important;
				padding: 0 !important;
			}
			.reel-header-mylist-btn .reel-header-btn-text,
			.reel-header-mhistory-btn .reel-header-btn-text {
				display: none !important;
			}
			.short-m-hide {
				display: none !important;
			}
		}

		@media (max-width: 768px) {
			/* 1. Stack the header content vertically and allow it to grow */
			header#short-header,
			.short-header,
			.site-header,
			.reel-header,
			header#short-header.scrolled,
			.short-header.scrolled,
			.site-header.scrolled,
			.reel-header.scrolled {
				height: auto !important;
				min-height: 48px !important;
				max-height: none !important;
				padding: 0 !important;
				margin: 0 !important;
				box-sizing: border-box !important;
				display: flex !important;
				flex-direction: column !important;
				align-items: stretch !important;
				backdrop-filter: none !important;
				-webkit-backdrop-filter: none !important;
				filter: none !important;
				box-shadow: none !important;
				-webkit-box-shadow: none !important;
				transition: background 0.25s ease, background-color 0.25s ease !important;
			}
			.short-header.scrolled,
			.site-header.scrolled,
			.reel-header.scrolled {
				background: var(--bg-header) !important;
				background-color: var(--bg-header) !important;
				padding: 0 !important;
			}
			/* 2. Ensure the top container stays at full width */
			.short-header-container,
			.header-container {
				height: 48px !important;
				min-height: 48px !important;
				max-height: 48px !important;
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				width: 100% !important;
				flex-shrink: 0 !important;
				padding: 0 16px !important;
				box-sizing: border-box !important;
				gap: 6px !important;
			}
			/* 3. Single-line horizontally scrollable genre bar (nowrap) */
			.short-mobile-genre-bar {
				width: 100% !important;
				display: flex !important;
				flex-direction: row !important;
				flex-wrap: nowrap !important;
				white-space: nowrap !important;
				overflow-x: auto !important;
				overflow-y: hidden !important;
				-webkit-overflow-scrolling: touch !important;
				scrollbar-width: none !important;
				padding: 4px 16px 10px 16px !important;
				gap: 8px !important;
				box-sizing: border-box !important;
			}
			.short-mobile-genre-bar::-webkit-scrollbar {
				display: none !important;
			}
			/* 4. Internal track with nowrap */
			.short-mobile-top-nav-track {
				display: flex !important;
				flex-direction: row !important;
				flex-wrap: nowrap !important;
				align-items: center !important;
				gap: 8px !important;
				width: max-content !important;
			}
			.short-mobile-genre-bar .mobile-top-nav-link {
				flex-shrink: 0 !important;
				display: inline-flex !important;
				align-items: center !important;
				justify-content: center !important;
				font-size: 13px !important;
				font-weight: 600 !important;
				color: #94a3b8 !important;
				background: transparent !important;
				border: none !important;
				box-shadow: none !important;
				backdrop-filter: none !important;
				-webkit-backdrop-filter: none !important;
				padding: 3px 6px !important;
				text-decoration: none !important;
				white-space: nowrap !important;
				transition: color 0.15s ease, font-weight 0.15s ease !important;
				line-height: 1.2 !important;
			}
			.short-mobile-genre-bar .mobile-top-nav-link.active,
			.short-mobile-genre-bar .mobile-top-nav-link:hover {
				color: #ffffff !important;
				font-weight: 800 !important;
				background: transparent !important;
				border: none !important;
				box-shadow: none !important;
			}
			.short-header-left,
			.short-header-right {
				height: 100% !important;
				display: flex !important;
				align-items: center !important;
			}
			.short-brand-logo,
			.reel-brand-logo,
			.brand-logo {
				display: inline-flex !important;
				align-items: center !important;
				height: 100% !important;
			}
			.reel-brand-logo-img,
			.short-brand-logo img,
			.brand-logo img {
				height: var(--short-logo-mobile-h, 28px) !important;
				max-height: 30px !important;
				width: var(--short-logo-mobile-w, auto) !important;
				max-width: 85px !important;
				object-fit: contain !important;
			}
			.reel-brand-logo-text {
				font-size: 14px !important;
			}
			.reel-lang-trigger {
				width: 26px !important;
				height: 26px !important;
				min-width: 26px !important;
				max-width: 26px !important;
			}

			/* Global Mobile Page Top Spacing: Prevent Header & Mobile Genre Bar from Cutting Off Page Content */
			.reel-hero-showcase {
				padding-top: calc(env(safe-area-inset-top, 0px) + 96px) !important;
			}
			.short-leaderboard-page-wrapper,
			.short-account-page-wrapper,
			.reel-genre-page,
			.short-reward-page-wrap,
			.short-page-container,
			.short-subscription-page,
			.short-homepage-wrapper {
				padding-top: calc(env(safe-area-inset-top, 0px) + 94px) !important;
			}
			body.admin-bar .reel-hero-showcase,
			body.admin-bar .short-leaderboard-page-wrapper,
			body.admin-bar .short-account-page-wrapper,
			body.admin-bar .reel-genre-page,
			body.admin-bar .short-reward-page-wrap,
			body.admin-bar .short-page-container,
			body.admin-bar .short-subscription-page,
			body.admin-bar .short-homepage-wrapper {
				padding-top: calc(env(safe-area-inset-top, 0px) + 138px) !important;
			}

			/* Universal Perfect 5-Item Mobile Bottom Navigation Layout & Centering */
			.short-mobile-bottom-nav {
				display: grid !important;
				grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
				align-items: center !important;
				justify-items: center !important;
				position: fixed !important;
				bottom: 0 !important;
				left: 0 !important;
				right: 0 !important;
				width: 100% !important;
				height: 58px !important;
				height: calc(58px + env(safe-area-inset-bottom, 0px)) !important;
				padding-bottom: env(safe-area-inset-bottom, 0px) !important;
				padding-left: 0 !important;
				padding-right: 0 !important;
				padding-top: 0 !important;
				margin: 0 !important;
				background: var(--bg-nav-bottom) !important;
				background-color: var(--bg-nav-bottom) !important;
				border: none !important;
				border-top: none !important;
				box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.7) !important;
				z-index: 9999 !important;
				box-sizing: border-box !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item {
				width: 100% !important;
				height: 100% !important;
				display: flex !important;
				flex-direction: column !important;
				align-items: center !important;
				justify-content: center !important;
				text-align: center !important;
				padding: 4px 1px !important;
				gap: 3px !important;
				color: #8e8e93 !important;
				text-decoration: none !important;
				box-sizing: border-box !important;
				transition: color 0.15s ease, transform 0.15s ease !important;
				-webkit-tap-highlight-color: transparent !important;
				overflow: hidden !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item span {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				font-size: 10px !important;
				font-weight: 600 !important;
				line-height: 1.1 !important;
				text-align: center !important;
				white-space: nowrap !important;
				overflow: hidden !important;
				text-overflow: ellipsis !important;
				letter-spacing: -0.15px !important;
				margin: 0 !important;
				padding: 0 1px !important;
				box-sizing: border-box !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item svg {
				width: 20px !important;
				height: 20px !important;
				stroke: currentColor !important;
				flex-shrink: 0 !important;
				display: block !important;
				margin: 0 auto !important;
				transition: stroke 0.15s ease, transform 0.15s ease !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item .mobile-nav-reward-icon-wrap,
			.short-mobile-bottom-nav .mobile-nav-item .mobile-nav-icon-wrap {
				width: 20px !important;
				height: 20px !important;
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
				margin: 0 auto !important;
				flex-shrink: 0 !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item .mobile-nav-avatar-wrap {
				width: 22px !important;
				height: 22px !important;
				min-width: 22px !important;
				min-height: 22px !important;
				border-radius: 50% !important;
				overflow: hidden !important;
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
				margin: 0 auto !important;
				flex-shrink: 0 !important;
				aspect-ratio: 1 / 1 !important;
				border: 1.5px solid rgba(255, 255, 255, 0.7) !important;
				box-sizing: border-box !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item .mobile-nav-avatar-img {
				width: 100% !important;
				height: 100% !important;
				min-width: 100% !important;
				min-height: 100% !important;
				border-radius: 50% !important;
				object-fit: cover !important;
				display: block !important;
				aspect-ratio: 1 / 1 !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item.active {
				color: var(--theme-accent, #e11d48) !important;
				font-weight: 800 !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item.active svg {
				stroke: var(--theme-accent, #e11d48) !important;
				transform: scale(1.06) !important;
			}
			.short-mobile-bottom-nav .mobile-nav-item:active {
				transform: scale(0.94) !important;
			}
		}

		/* Global Desktop Header Navigation Styling */
		.short-nav-desktop,
		.reel-nav-desktop {
			display: flex !important;
			flex-direction: row !important;
			align-items: center !important;
			flex-wrap: nowrap !important;
			gap: 8px !important;
			white-space: nowrap !important;
		}
		.short-nav-desktop .nav-item,
		.reel-nav-desktop .nav-item,
		.short-nav-desktop a.nav-item,
		.reel-nav-desktop a.nav-item {
			white-space: nowrap !important;
			flex-shrink: 0 !important;
			display: inline-flex !important;
			align-items: center !important;
			justify-content: center !important;
			text-decoration: none !important;
			line-height: 1 !important;
			font-size: 14px !important;
			font-weight: 600 !important;
			color: rgba(255, 255, 255, 0.75) !important;
			padding: 8px 14px !important;
			border-radius: 999px !important;
			transition: all 0.2s ease !important;
		}
		.short-nav-desktop .nav-item:hover,
		.reel-nav-desktop .nav-item:hover,
		.short-nav-desktop a.nav-item:hover,
		.reel-nav-desktop a.nav-item:hover {
			color: #ffffff !important;
			background: rgba(255, 255, 255, 0.1) !important;
		}
		.short-nav-desktop .nav-item.active,
		.reel-nav-desktop .nav-item.active,
		.short-nav-desktop a.nav-item.active,
		.reel-nav-desktop a.nav-item.active {
			color: #ffffff !important;
			background: rgba(255, 255, 255, 0.2) !important;
			font-weight: 800 !important;
		}

		/* Global Buttons & CTA backgrounds */
		.btn-primary,
		.btn-account-cta,
		.btn-change-plan,
		.btn-vip-purchase,
		.btn-recharge-submit,
		.hero-btn-play,
		.hero-play-btn,
		.sub-plan-btn.btn-popular,
		.vip-plan-card.is-popular .plan-btn,
		.short-btn-primary,
		.auth-submit-btn,
		#auth-submit-btn,
		.btn-watch-play,
		.play-btn-hero,
		.btn-checkout-recharge {
			background: var(--theme-accent) !important;
			border-color: var(--theme-accent) !important;
			color: #ffffff !important;
			box-shadow: 0 4px 18px rgba(var(--theme-accent-rgb), 0.4) !important;
		}

		/* Active Pills & Tabs */
		.sub-tab-btn.active,
		.tab-pill.is-active,
		.sub-tab-pill.active,
		.genre-pill.active,
		.episodes-filter-btn.active,
		.vip-tab-pill.is-active,
		.coin-tab-btn.active {
			background: var(--theme-accent) !important;
			border-color: var(--theme-accent) !important;
			color: #ffffff !important;
			box-shadow: 0 2px 12px rgba(var(--theme-accent-rgb), 0.35) !important;
		}

		/* Underline Text Tabs */
		.dt-tab.active {
			background: transparent !important;
			color: #ffffff !important;
			border-bottom-color: var(--theme-accent, #ff2d55) !important;
			box-shadow: none !important;
		}

		/* Active Swatches & Card Highlights */
		.color-swatch-btn.is-active {
			border-color: var(--theme-accent) !important;
			box-shadow: 0 0 0 1px var(--theme-accent), 0 4px 15px rgba(var(--theme-accent-rgb), 0.3) !important;
		}
		.vip-plan-card.is-popular,
		.coin-pack-card.is-popular,
		.coin-pack-card:hover {
			border-color: var(--theme-accent) !important;
		}

		/* Badges & Pills */
		.membership-pill,
		.badge-vip,
		.header-dropdown-vip-tag,
		.vip-badge,
		.vip-crown-badge,
		.plan-badge-popular {
			background: rgba(var(--theme-accent-rgb), 0.18) !important;
			color: var(--theme-accent) !important;
			border-color: rgba(var(--theme-accent-rgb), 0.4) !important;
		}

		/* Text Highlights */
		.text-accent,
		.accent-text,
		.plan-name-highlight,
		.price-currency,
		.plan-price-num,
		.highlight-price,
		.dropdown-item.is-accent,
		.vip-price-tag,
		.theme-accent-text {
			color: var(--theme-accent) !important;
		}

		/* Header Merged Coins & Top Up Pill */
		.header-coin-topup-merged,
		.header-coin-topup-merged .coin-val,
		.header-coin-topup-merged #header-user-coins-val {
			color: #ffffff !important;
			font-weight: 800 !important;
			font-size: 13px !important;
			display: inline-block !important;
			opacity: 1 !important;
			visibility: visible !important;
			text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25) !important;
		}

		/* Player Progress, Knobs & Loaders */
		.loader-progress-bar,
		#short-loader-bar,
		.mini-progress-fill,
		.art-progress-played,
		.plyr--video .plyr__control.plyr__tab-focus,
		.plyr__control--overlaid,
		.plyr__menu__container .plyr__control[role=menuitemradio][aria-checked=true]::before {
			background: var(--theme-accent) !important;
		}
		.plyr--full-ui input[type=range] {
			color: var(--theme-accent) !important;
		}
		.netflix-spinner-circle {
			border-top-color: var(--theme-accent) !important;
		}

		/* Active Indicator Dots & Borders */
		.slider-bullet.active,
		.carousel-bullet.active,
		.swiper-pagination-bullet-active {
			background: var(--theme-accent) !important;
			box-shadow: 0 0 8px var(--theme-accent) !important;
		}
	
				</style>
	<!-- Lemon Squeezy Official Overlay Checkout SDK -->
	<script src="https://app.lemonsqueezy.com/js/lemon.js" defer></script>
	<script>
	// Global Transaction Recorder Helper
	window.recordShortTransaction = function(txData) {
		if (!txData) return;
		var item = {
			amount: (txData.amount !== undefined) ? txData.amount : 0,
			reason: txData.reason || 'Transaction',
			ts: txData.ts || Date.now(),
			type: txData.type || (txData.amount >= 0 ? 'credit' : 'spend'),
			status: txData.status || 'Completed',
			order_id: txData.order_id || null,
			plan_id: txData.plan_id || null
		};

		// 1. Save to local coin history array
		try {
			var localHistory = JSON.parse(localStorage.getItem('short_local_coin_history') || '[]');
			if (!Array.isArray(localHistory)) localHistory = [];
			localHistory.unshift(item);
			if (localHistory.length > 50) localHistory = localHistory.slice(0, 50);
			localStorage.setItem('short_local_coin_history', JSON.stringify(localHistory));

			var uid = localStorage.getItem('short_user_uid');
			if (uid) {
				var stvHistory = JSON.parse(localStorage.getItem('stv_coin_history_' + uid) || '[]');
				if (!Array.isArray(stvHistory)) stvHistory = [];
				stvHistory.unshift(item);
				if (stvHistory.length > 50) stvHistory = stvHistory.slice(0, 50);
				localStorage.setItem('stv_coin_history_' + uid, JSON.stringify(stvHistory));
			}
		} catch(e) {
			console.warn('[Transaction Storage Error]', e);
		}

		// 2. Sync to Firebase if connected
		try {
			if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
				var user = firebase.auth().currentUser;
				if (user && firebase.database) {
					firebase.database().ref('users/' + user.uid + '/coinHistory').push(item);
				}
			}
		} catch(e) {
			console.warn('[Firebase Tx Error]', e);
		}

		// 3. Dispatch global live update event
		try {
			window.dispatchEvent(new CustomEvent('short_transaction_updated', { detail: item }));
		} catch(e) {}
	};

	// Global Custom Alert / Modal Helper
	window.shortCustomAlert = window.shortCustomAlert || function(options) {
		return new Promise(function(resolve) {
			if (typeof options === 'string') {
				options = { message: options };
			}
			var title = options.title || 'Notification';
			var message = options.message || '';
			var buttonText = options.buttonText || 'OK';
			var iconType = options.type || 'info';

			var iconSvg = '';
			var accentCol = '#ff2d55';
			var bgAccent = 'rgba(255, 45, 85, 0.15)';

			if (iconType === 'success') {
				accentCol = '#22c55e';
				bgAccent = 'rgba(34, 197, 94, 0.15)';
				iconSvg = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="' + accentCol + '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>';
			} else if (iconType === 'error') {
				accentCol = '#ef4444';
				bgAccent = 'rgba(239, 68, 68, 0.15)';
				iconSvg = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="' + accentCol + '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
			} else if (iconType === 'warning') {
				accentCol = '#f59e0b';
				bgAccent = 'rgba(245, 158, 11, 0.15)';
				iconSvg = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="' + accentCol + '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
			} else {
				iconSvg = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="' + accentCol + '" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
			}

			var overlay = document.createElement('div');
			overlay.className = 'short-custom-popup-overlay';
			overlay.innerHTML = 
				'<div class="short-custom-popup-box">' +
					'<button type="button" class="short-custom-popup-close" aria-label="Close">&times;</button>' +
					'<div class="short-custom-popup-icon" style="background:' + bgAccent + '; border-color:' + accentCol + ';">' +
						iconSvg +
					'</div>' +
					'<h3 class="short-custom-popup-title">' + title + '</h3>' +
					'<p class="short-custom-popup-msg">' + message + '</p>' +
					'<div class="short-custom-popup-actions">' +
						'<button type="button" class="short-custom-popup-btn-confirm">' + buttonText + '</button>' +
					'</div>' +
				'</div>';

			document.body.appendChild(overlay);

			requestAnimationFrame(function() {
				overlay.classList.add('is-visible');
				var btn = overlay.querySelector('.short-custom-popup-btn-confirm');
				if (btn) btn.focus();
			});

			function closePopup() {
				overlay.classList.remove('is-visible');
				setTimeout(function() {
					if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
					resolve(true);
				}, 250);
			}

			var confirmBtn = overlay.querySelector('.short-custom-popup-btn-confirm');
			var closeBtn = overlay.querySelector('.short-custom-popup-close');
			if (confirmBtn) confirmBtn.addEventListener('click', closePopup);
			if (closeBtn) closeBtn.addEventListener('click', closePopup);
			overlay.addEventListener('click', function(e) {
				if (e.target === overlay) closePopup();
			});
		});
	};

	window.addEventListener('DOMContentLoaded', function() {
		if (typeof window.createLemonSqueezy === 'function') {
			window.createLemonSqueezy();
		}
		if (typeof LemonSqueezy !== 'undefined' && LemonSqueezy.Setup) {
			LemonSqueezy.Setup({
				eventHandler: function(eventData) {
					if (eventData && eventData.event === 'Checkout.Success') {
						var pending = null;
						try {
							pending = JSON.parse(sessionStorage.getItem('short_pending_checkout') || '{}');
						} catch(e){}

						var orderId = (eventData.data && eventData.data.order && eventData.data.order.id) ? ('#' + eventData.data.order.id) : ('LS-' + Math.floor(Math.random()*899999+100000));

						// Check if the purchase was for Drama Coins
						if (pending && pending.type === 'coins' && pending.amount) {
							var addAmount = parseInt(pending.amount, 10) || 300;
							var packName = pending.pack_name || (addAmount + ' Drama Coins');
							var priceStr = pending.price || '';
							var curCoins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
							curCoins += addAmount;
							localStorage.setItem('shorttv_user_coins', String(curCoins));

							// Record real transaction
							window.recordShortTransaction({
								amount: addAmount,
								reason: 'Lemon Squeezy • ' + packName + (priceStr ? ' (' + priceStr + ')' : ''),
								type: 'credit',
								status: 'Completed',
								order_id: orderId,
								ts: Date.now()
							});

							// Update live UI coin balance
							var coinHeaderEl = document.getElementById('header-user-coins-val');
							if (coinHeaderEl) coinHeaderEl.textContent = curCoins;
							var subCoinEl = document.getElementById('sub-page-coin-balance');
							if (subCoinEl) subCoinEl.textContent = curCoins;

							// Sync coins to Firebase
							if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
								var uCoins = firebase.auth().currentUser;
								if (uCoins && firebase.database) {
									firebase.database().ref('users/' + uCoins.uid + '/coins').set(curCoins);
								}
							}

							sessionStorage.removeItem('short_pending_checkout');
							setTimeout(function() {
								window.shortCustomAlert({
									title: 'Payment Successful! 🎉',
									message: addAmount + ' Coins credited to your wallet (Order ' + orderId + '). Total Balance: ' + curCoins + ' Coins.',
									type: 'success',
									buttonText: 'View Wallet'
								}).then(function() {
									window.location.href = '<?php echo esc_url( home_url( '/account/#view=wallet-coins' ) ); ?>';
								});
							}, 600);
							return;
						}

						// Otherwise: VIP Subscription Purchase
						var planId = (pending && pending.plan_id) || 'standard';
						var planName = (pending && pending.plan_name) || 'VIP Pass';
						var bonusCoins = (planId === 'basic') ? 100 : (planId === 'premium' ? 2000 : 500);
						var days = (planId === 'basic') ? 7 : (planId === 'premium' ? 365 : 30);
						var expiresAt = Date.now() + (days * 24 * 60 * 60 * 1000);

						var vipPlan = {
							plan_id: planId,
							plan_name: planName,
							quality: (planId === 'premium') ? '1080p Ultra HD + HDR' : '1080p Ultra HD',
							tier: 'VIP',
							status: 'active',
							activated_at: Date.now(),
							expires_at: expiresAt
						};
						localStorage.setItem('short_subscription', JSON.stringify(vipPlan));
						localStorage.setItem('short_sub_tier', planId);
						localStorage.setItem('short_is_vip', '1');
						localStorage.setItem('short_vip_plan', planId);
						localStorage.setItem('short_vip_expires', expiresAt.toString());
						document.cookie = 'short_sub_tier=' + planId + '; path=/; max-age=2592000; SameSite=Lax';

						// Add VIP Bonus Coins
						var curCoinsVIP = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
						curCoinsVIP += bonusCoins;
						localStorage.setItem('shorttv_user_coins', String(curCoinsVIP));

						// Record real VIP transaction
						window.recordShortTransaction({
							amount: bonusCoins,
							reason: 'Lemon Squeezy • ' + planName + ' Pass Activation (' + days + ' Days VIP + ' + bonusCoins + ' Bonus Coins)',
							type: 'vip',
							plan_id: planId,
							status: 'Completed',
							order_id: orderId,
							ts: Date.now()
						});

						if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
							var u = firebase.auth().currentUser;
							if (u && firebase.database) {
								firebase.database().ref('users/' + u.uid + '/subscription').set(vipPlan);
								firebase.database().ref('users/' + u.uid).update({
									is_vip: true,
									vip_plan: planId,
									vip_expires: expiresAt,
									coins: curCoinsVIP
								});
							}
						}

						sessionStorage.removeItem('short_pending_checkout');
						setTimeout(function() {
							window.shortCustomAlert({
								title: 'VIP Pass Activated! 👑',
								message: 'Your ' + planName + ' is now active with ' + bonusCoins + ' bonus coins (Order ' + orderId + ').',
								type: 'success',
								buttonText: 'Start Watching'
							}).then(function() {
								window.location.href = '<?php echo esc_url( home_url( '/' ) ); ?>';
							});
						}, 600);
					}
				}
			});
		}
	});
	</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'short-dark-theme' ); ?>>
<?php wp_body_open(); ?>

<!-- Netflix Global Circular Loader & Progress Bar -->
<div id="short-page-loader" class="short-global-loader">
	<div class="loader-progress-bar" id="short-loader-bar"></div>
	<div class="loader-circle-wrapper" id="short-circle-wrapper">
		<div class="netflix-spinner-circle"></div>
	</div>
</div>
<script>
(function() {
	var loader = document.getElementById('short-page-loader');
	var bar = document.getElementById('short-loader-bar');
	if (bar) {
		bar.style.width = '35%';
	}
	document.addEventListener('DOMContentLoaded', function() {
		if (bar) bar.style.width = '75%';
	});
	window.addEventListener('load', function() {
		if (bar) bar.style.width = '100%';
		setTimeout(function() {
			if (loader) loader.classList.add('loaded');
		}, 300);
	});
	// Safety auto-dismiss
	setTimeout(function() {
		if (loader && !loader.classList.contains('loaded')) {
			if (bar) bar.style.width = '100%';
			setTimeout(function() { loader.classList.add('loaded'); }, 200);
		}
	}, 2500);

	// Global JS helpers for AJAX/delayed elements
	window.showGlobalLoader = function() {
		if (loader) {
			loader.classList.remove('loaded');
			if (bar) bar.style.width = '70%';
		}
	};
	window.hideGlobalLoader = function() {
		if (loader) {
			if (bar) bar.style.width = '100%';
			setTimeout(function() { loader.classList.add('loaded'); }, 200);
		}
	};
})();
</script>

<?php
// Dynamic Splash Brand Configuration from Admin Customizer
$brand_settings    = get_option( 'short_brand_settings', array() );
$splash_settings   = get_option( 'short_splash_settings', array() );
$splash_enabled    = $splash_settings['enabled'] ?? '1';
$splash_device     = $splash_settings['target_device'] ?? 'mobile_only';
$splash_frequency  = $splash_settings['frequency'] ?? 'session';
$splash_mode       = $splash_settings['mode'] ?? 'ribbon_letter';
$splash_logo_url   = $splash_settings['splash_logo_url'] ?? '';

if ( empty( $splash_logo_url ) ) {
	$splash_logo_url = ! empty( $brand_settings['site_logo'] ) ? $brand_settings['site_logo'] : ( ! empty( $brand_settings['logo'] ) ? $brand_settings['logo'] : '' );
}

$raw_site_name     = get_bloginfo( 'name' );
$splash_brand_name = ! empty( $splash_settings['splash_title'] ) 
	? $splash_settings['splash_title'] 
	: ( ! empty( $brand_settings['brand_name'] ) 
		? $brand_settings['brand_name'] 
		: ( ! empty( $brand_settings['site_title'] ) 
			? $brand_settings['site_title'] 
			: ( ( ! empty( $raw_site_name ) && 'My Blog' !== $raw_site_name && 'WordPress' !== $raw_site_name ) ? $raw_site_name : 'ShortTV' ) ) );

if ( empty( $splash_brand_name ) || 'My Blog' === $splash_brand_name || 'WordPress' === $splash_brand_name ) {
	$splash_brand_name = 'ShortTV';
}

$splash_first_letter = ! empty( $splash_settings['splash_letter'] ) ? $splash_settings['splash_letter'] : mb_strtoupper( mb_substr( trim( $splash_brand_name ), 0, 1 ) );
if ( empty( $splash_first_letter ) ) {
	$splash_first_letter = 'S';
}
$splash_rest_title   = mb_substr( trim( $splash_brand_name ), mb_strlen( $splash_first_letter ) );
$raw_site_desc       = get_bloginfo( 'description' );
$splash_tagline      = ! empty( $splash_settings['splash_tagline'] ) 
	? $splash_settings['splash_tagline'] 
	: ( ! empty( $brand_settings['brand_tagline'] ) 
		? $brand_settings['brand_tagline'] 
		: ( ! empty( $brand_settings['tagline'] ) 
			? $brand_settings['tagline'] 
			: ( ( ! empty( $raw_site_desc ) && 'Just another WordPress site' !== $raw_site_desc ) ? $raw_site_desc : __( 'Unlimited Short Dramas, Mini-Series, and More', 'short-stream' ) ) ) );

if ( empty( $splash_tagline ) || 'Just another WordPress site' === $splash_tagline ) {
	$splash_tagline = __( 'Unlimited Short Dramas, Mini-Series, and More', 'short-stream' );
}

$splash_bg                 = ! empty( $splash_settings['bg_color_custom'] ) ? $splash_settings['bg_color_custom'] : ( $splash_settings['bg_color'] ?? '#000000' );
$splash_accent_setting     = ! empty( $splash_settings['accent_color_custom'] ) ? $splash_settings['accent_color_custom'] : ( $splash_settings['accent_color'] ?? 'auto' );
$splash_is_auto            = ( 'auto' === $splash_accent_setting || empty( $splash_accent_setting ) );
$splash_accent             = $splash_is_auto ? 'var(--theme-accent, #ff2d55)' : $splash_accent_setting;
$splash_accent_first_letter = ! empty( $splash_settings['accent_first_letter'] ) && '1' === $splash_settings['accent_first_letter'];
$splash_speed              = $splash_settings['speed'] ?? 'normal';
$splash_show_skip          = $splash_settings['show_skip_hint'] ?? '1';
?>
<?php if ( '0' !== $splash_enabled && false !== $splash_enabled ) : ?>
<!-- Netflix Cinematic Mobile Splash Screen Animation -->
<div id="short-mobile-splash" class="short-splash-overlay splash-speed-<?php echo esc_attr( $splash_speed ); ?>" style="display:none; background-color:<?php echo esc_attr( $splash_bg ); ?>; --splash-accent: <?php echo esc_attr( $splash_accent ); ?>; <?php if ( ! $splash_is_auto ) : ?>--theme-accent: <?php echo esc_attr( $splash_accent ); ?>;<?php endif; ?>" aria-hidden="true">
	<div class="short-splash-container">
		<?php if ( 'custom_logo' === $splash_mode && ! empty( $splash_logo_url ) ) : ?>
			<!-- Custom Uploaded Logo Stage -->
			<div class="splash-custom-logo-stage" style="display:flex; align-items:center; justify-content:center; max-width:240px; margin:0 auto 16px;">
				<img src="<?php echo esc_url( $splash_logo_url ); ?>" alt="<?php echo esc_attr( $splash_brand_name ); ?>" class="splash-custom-logo-img" style="max-height:90px; max-width:220px; object-fit:contain; filter:drop-shadow(0 2px 8px rgba(0,0,0,0.5));">
			</div>
		<?php elseif ( 'ribbon_letter' === $splash_mode || empty( $splash_mode ) ) : ?>
			<!-- Dynamic Brand First Letter Cinematic Ribbon Stage (Netflix Style) -->
			<div class="netflix-ribbon-stage short-letter-stage" id="splash-ribbon-stage">
				<svg class="netflix-ribbon-svg short-letter-svg" viewBox="0 0 140 150" fill="none" xmlns="http://www.w3.org/2000/svg">
					<text x="50%" y="54%" dominant-baseline="central" text-anchor="middle" class="ribbon-letter-text" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif" font-weight="900" font-size="118" fill="<?php echo esc_attr( $splash_accent ); ?>" style="fill:<?php echo esc_attr( $splash_accent ); ?> !important; <?php echo ! $splash_is_auto ? 'filter:drop-shadow(0 0 8px ' . esc_attr( $splash_accent ) . '66);' : ''; ?>"><?php echo esc_html( $splash_first_letter ); ?></text>
				</svg>
				<!-- Subtle Energy Glow -->
				<div class="splash-energy-glow" style="<?php echo ! $splash_is_auto ? 'background:radial-gradient(circle, ' . esc_attr( $splash_accent ) . '33 0%, rgba(0,0,0,0) 70%) !important;' : ''; ?>"></div>
			</div>
		<?php endif; ?>

		<!-- Brand Logo Reveal Screen -->
		<div class="splash-brand-reveal <?php echo ( 'minimal_text' === $splash_mode ) ? 'splash-minimal-reveal' : ''; ?>" id="splash-brand-reveal">
			<div class="splash-brand-logo">
				<?php if ( $splash_accent_first_letter ) : ?>
					<span class="logo-accent" style="color:<?php echo esc_attr( $splash_accent ); ?>;"><?php echo esc_html( $splash_first_letter ); ?></span><?php echo esc_html( $splash_rest_title ); ?>
				<?php else : ?>
					<span class="logo-text-full" style="color:#ffffff;"><?php echo esc_html( $splash_brand_name ); ?></span>
				<?php endif; ?>
			</div>
			<div class="splash-brand-tagline"><?php echo esc_html( $splash_tagline ); ?></div>
		</div>
	</div>

	<!-- Soft Ambient Background Curtain -->
	<div class="splash-flash-curtain" id="splash-flash-curtain" style="<?php echo ! $splash_is_auto ? 'background:radial-gradient(circle, ' . esc_attr( $splash_accent ) . '1a 0%, rgba(0,0,0,0) 80%);' : ''; ?>"></div>
	
	<?php if ( '0' !== $splash_show_skip && false !== $splash_show_skip ) : ?>
	<!-- Tap to Skip Hint -->
	<div class="splash-skip-hint"><?php _e( 'Tap to skip', 'short-stream' ); ?></div>
	<?php endif; ?>
</div>
<script>
(function(){
	try {
		var s = document.getElementById('short-mobile-splash');
		if (!s) return;
		var p = new URLSearchParams(window.location.search);
		var force = p.has('splash') || p.get('preview') === 'splash';
		var targetDevice = <?php echo wp_json_encode( $splash_device ); ?>;
		var frequency = <?php echo wp_json_encode( $splash_frequency ); ?>;
		var speed = <?php echo wp_json_encode( $splash_speed ); ?>;
		var isMob = (window.innerWidth <= 768) || /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || window.navigator.standalone;
		var shouldTarget = (targetDevice === 'all_devices') || isMob;

		var hasSeen = false;
		if (frequency === 'session') {
			hasSeen = !!sessionStorage.getItem('short_splash_played');
		} else if (frequency === 'first_visit') {
			hasSeen = !!localStorage.getItem('short_splash_first_visit_played');
		} else if (frequency === 'always') {
			hasSeen = false;
		}

		if (force || (shouldTarget && !hasSeen)) {
			s.style.display = 'flex';
			document.body.classList.add('splash-active');

			var duration = 2600;
			if (speed === 'fast') duration = 1800;
			if (speed === 'slow') duration = 3400;

			var dismissed = false;
			var dismissSplash = function() {
				if (dismissed) return;
				dismissed = true;
				s.classList.add('fade-out');
				setTimeout(function() {
					s.style.display = 'none';
					document.body.classList.remove('splash-active');
					try {
						if (frequency === 'session') {
							sessionStorage.setItem('short_splash_played', '1');
						} else if (frequency === 'first_visit') {
							localStorage.setItem('short_splash_first_visit_played', '1');
						}
					} catch(e) {}
				}, 450);
			};

			// Tap to skip anytime
			s.addEventListener('click', dismissSplash, { once: true });
			s.addEventListener('touchstart', dismissSplash, { passive: true, once: true });

			// Auto dismiss after animation duration
			setTimeout(dismissSplash, duration);
		}
	} catch(e) {}
})();
</script>
<?php endif; ?>

<?php
$active_nav      = function_exists( 'short_get_current_nav_section' ) ? short_get_current_nav_section() : ( is_front_page() ? 'home' : '' );
$is_profile_mode = false;
$is_watch_mode   = (bool) get_query_var( 'short_watch' ) || ! empty( $GLOBALS['short_is_watch_page'] ) || is_singular( array( 'short_title', 'video', 'movie', 'tv' ) );
$is_auth_mode    = (bool) get_query_var( 'short_auth' );
$is_reward_mode           = is_page_template( 'page-reward.php' ) || is_page( 'reward' ) || is_page( 'rewards' ) || (bool) get_query_var( 'short_reward' );
$is_account_settings_mode = is_page_template( 'page-account.php' ) || is_page( 'account' ) || is_page( 'settings' ) || (bool) get_query_var( 'short_account' );
?>

<?php if ( ! $is_watch_mode && ! $is_auth_mode && ! $is_reward_mode && ! $is_account_settings_mode ) : ?>
<header id="short-header" class="short-header reel-header <?php echo $is_profile_mode ? 'short-header-profile-only' : ''; ?>">
	<div class="short-header-container">
		<div class="short-header-left">
			<?php
			$brand_settings = get_option( 'short_brand_settings', array() );
			$logo_url       = function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : 'https://i.postimg.cc/cH3CM5h4/image.png';
			$brand_name     = ! empty( $brand_settings['brand_name'] ) && 'My Blog' !== $brand_settings['brand_name'] ? $brand_settings['brand_name'] : ( get_bloginfo( 'name' ) !== 'My Blog' ? get_bloginfo( 'name' ) : 'ShortTV' );
			$display_mode   = $brand_settings['logo_display_mode'] ?? 'image_only';
			$logo_height        = ! empty( $brand_settings['logo_height'] ) ? (int) $brand_settings['logo_height'] : 42;
			if ( $logo_height < 15 || $logo_height > 150 ) $logo_height = 42;
			$logo_width         = ! empty( $brand_settings['logo_width'] ) ? (int) $brand_settings['logo_width'] : 0;
			$mobile_logo_height = ! empty( $brand_settings['mobile_logo_height'] ) ? (int) $brand_settings['mobile_logo_height'] : 36;
			if ( $mobile_logo_height < 15 || $mobile_logo_height > 120 ) $mobile_logo_height = 36;
			$mobile_logo_width  = ! empty( $brand_settings['mobile_logo_width'] ) ? (int) $brand_settings['mobile_logo_width'] : 0;

			$mobile_header_cfg  = get_option( 'short_mobile_header_config', array(
				'show_logo'    => 1,
				'show_search'  => 1,
				'show_lang'    => 1,
				'show_coins'   => 1,
				'show_mylist'  => 1,
				'show_history' => 0,
				'element_order' => array( 'logo', 'search', 'lang', 'coins', 'mylist', 'history' ),
			) );
			$show_m_logo    = ! isset( $mobile_header_cfg['show_logo'] ) || ! empty( $mobile_header_cfg['show_logo'] );
			$show_m_search  = ! isset( $mobile_header_cfg['show_search'] ) || ! empty( $mobile_header_cfg['show_search'] );
			$show_m_lang    = ! isset( $mobile_header_cfg['show_lang'] ) || ! empty( $mobile_header_cfg['show_lang'] );
			$show_m_coins   = ! isset( $mobile_header_cfg['show_coins'] ) || ! empty( $mobile_header_cfg['show_coins'] );
			$show_m_mylist  = ! empty( $mobile_header_cfg['show_mylist'] );
			$show_m_history = ! empty( $mobile_header_cfg['show_history'] );
			$show_m_genres  = ! isset( $mobile_header_cfg['show_genre_labels'] ) || ! empty( $mobile_header_cfg['show_genre_labels'] );

			$default_order = array( 'logo', 'search', 'lang', 'coins', 'mylist', 'history' );
			$element_order = ! empty( $mobile_header_cfg['element_order'] ) && is_array( $mobile_header_cfg['element_order'] ) ? $mobile_header_cfg['element_order'] : $default_order;
			$order_css = array();
			$order_idx = 1;
			foreach ( $element_order as $el_k ) {
				$order_css[ $el_k ] = $order_idx++;
			}
			?>
			<style>
				:root {
					--short-logo-h: <?php echo esc_attr( $logo_height ); ?>px;
					--short-logo-w: <?php echo $logo_width > 0 ? esc_attr( $logo_width ) . 'px' : 'auto'; ?>;
					--short-logo-mobile-h: <?php echo esc_attr( $mobile_logo_height ); ?>px;
					--short-logo-mobile-w: <?php echo $mobile_logo_width > 0 ? esc_attr( $mobile_logo_width ) . 'px' : 'auto'; ?>;
				}
				.reel-brand-logo-img,
				.short-brand-logo img,
				.brand-logo img {
					height: var(--short-logo-h) !important;
					width: var(--short-logo-w) !important;
					max-width: 280px !important;
					max-height: 80px !important;
					object-fit: contain !important;
					display: block !important;
				}
				@media (min-width: 769px) {
					.reel-header,
					.reel-header.scrolled,
					.short-header,
					.short-header.scrolled {
						padding: 12px 28px !important;
					}
				}
				@media (max-width: 768px) {
					.reel-header,
					.reel-header.scrolled,
					.short-header,
					.short-header.scrolled {
						padding: 0 !important;
					}
					.short-header-container,
					.header-container {
						display: flex !important;
						align-items: center !important;
						justify-content: space-between !important;
						padding: 0 10px !important;
						gap: 4px !important;
						width: 100% !important;
						box-sizing: border-box !important;
					}
					.reel-brand-logo-img,
					.short-brand-logo img,
					.brand-logo img {
						height: var(--short-logo-mobile-h, 28px) !important;
						width: var(--short-logo-mobile-w, auto) !important;
						max-height: 30px !important;
						max-width: 80px !important;
						object-fit: contain !important;
					}
					.short-header-left,
					.reel-header-left {
						order: <?php echo isset( $order_css['logo'] ) ? (int) $order_css['logo'] : 1; ?> !important;
						gap: 0 !important;
						flex-shrink: 0 !important;
						display: flex !important;
						align-items: center !important;
					}
					.short-header-right {
						flex: 1 1 auto !important;
						min-width: 0 !important;
						justify-content: flex-end !important;
						gap: 4px !important;
						display: flex !important;
						align-items: center !important;
					}
					.short-search-box {
						order: <?php echo isset( $order_css['search'] ) ? (int) $order_css['search'] : 2; ?> !important;
					}
					.reel-lang-dropdown-wrap {
						order: <?php echo isset( $order_css['lang'] ) ? (int) $order_css['lang'] : 3; ?> !important;
					}
					.header-coin-topup-merged {
						order: <?php echo isset( $order_css['coins'] ) ? (int) $order_css['coins'] : 4; ?> !important;
					}
					.reel-header-mylist-btn {
						order: <?php echo isset( $order_css['mylist'] ) ? (int) $order_css['mylist'] : 5; ?> !important;
					}
					.reel-header-mhistory-btn {
						order: <?php echo isset( $order_css['history'] ) ? (int) $order_css['history'] : 6; ?> !important;
					}
					.short-profile-menu {
						order: 99 !important;
					}
					.short-search-box,
					.short-search-box:not(.search-modal-active):not(.mobile-modal-active) {
						flex: 1 1 auto !important;
						width: auto !important;
						min-width: 0 !important;
						max-width: 140px !important;
						height: 25px !important;
						padding: 0 5px 0 7px !important;
						border-radius: 5px !important;
						background: rgba(255, 255, 255, 0.05) !important;
						border: 1px solid rgba(255, 255, 255, 0.01) !important;
						display: inline-flex !important;
						align-items: center !important;
						box-sizing: border-box !important;
						transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
					}
					.short-search-box:hover,
					.short-search-box:focus-within {
						max-width: 165px !important;
						border-color: rgba(255, 255, 255, 0.03) !important;
					}
					/* Full Header Replacement when Search is Active on Mobile */
					.short-header.search-active .short-header-left,
					.short-header.search-active .reel-header-left,
					.short-header.search-active .short-brand-logo,
					.short-header.search-active .reel-brand-logo,
					.short-header.search-active .reel-header-mhistory-btn,
					.short-header.search-active .reel-header-mylist-btn,
					.short-header.search-active .reel-lang-dropdown-wrap,
					.short-header.search-active .btn-reel-topup,
					.short-header.search-active .header-coin-topup-merged,
					.short-header.search-active .header-coin-wallet-pill,
					.short-header.search-active .profile-avatar-trigger,
					.short-header.search-active .header-profile-dropdown,
					.short-header.search-active .short-mobile-genre-bar,
					body.search-header-active .short-header-left,
					body.search-header-active .reel-header-left,
					body.search-header-active .reel-header-mhistory-btn,
					body.search-header-active .reel-header-mylist-btn,
					body.search-header-active .reel-lang-dropdown-wrap,
					body.search-header-active .btn-reel-topup,
					body.search-header-active .header-coin-topup-merged,
					body.search-header-active .header-coin-wallet-pill,
					body.search-header-active .profile-avatar-trigger,
					body.search-header-active .short-mobile-genre-bar {
						display: none !important;
						visibility: hidden !important;
						opacity: 0 !important;
						pointer-events: none !important;
					}

					.short-header.search-active,
					body.search-header-active .short-header,
					body.search-header-active .reel-header {
						height: auto !important;
						min-height: 62px !important;
						background: #0d0d12 !important;
						background-color: #0d0d12 !important;
						padding: 0 !important;
						overflow: visible !important;
						box-shadow: 0 4px 16px rgba(0, 0, 0, 0.7) !important;
						z-index: 99999 !important;
					}
					.short-header.search-active .short-header-container,
					body.search-header-active .short-header-container {
						height: auto !important;
						min-height: 62px !important;
						display: flex !important;
						align-items: center !important;
						padding: 12px 10px !important;
						box-sizing: border-box !important;
						position: relative !important;
						overflow: visible !important;
					}
					.short-header.search-active .short-header-right,
					body.search-header-active .short-header-right {
						display: flex !important;
						flex: 1 1 100% !important;
						width: 100% !important;
						min-width: 100% !important;
						max-width: 100% !important;
						margin: 0 !important;
						padding: 0 !important;
						align-items: center !important;
						position: relative !important;
						overflow: visible !important;
					}
					.short-search-box.search-modal-active,
					.short-search-box.mobile-modal-active,
					.short-header.search-active .short-search-box {
						position: relative !important;
						top: 0 !important;
						left: 0 !important;
						right: 0 !important;
						bottom: 0 !important;
						transform: none !important;
						width: 100% !important;
						max-width: 100% !important;
						min-width: 100% !important;
						height: 38px !important;
						max-height: 38px !important;
						min-height: 38px !important;
						margin: 0 !important;
						padding: 0 10px 0 8px !important;
						border-radius: 9px !important;
						background: #111116 !important;
						background-color: #111116 !important;
						backdrop-filter: none !important;
						-webkit-backdrop-filter: none !important;
						border: 1px solid rgba(255, 255, 255, 0.01) !important;
						display: flex !important;
						flex-direction: row !important;
						align-items: center !important;
						box-sizing: border-box !important;
						box-shadow: 0 2px 10px rgba(0, 0, 0, 0.6) !important;
						z-index: 99999 !important;
						cursor: default !important;
						overflow: visible !important;
					}
					.short-search-box.search-modal-active .short-search-inner,
					.short-search-box.mobile-modal-active .short-search-inner,
					.short-header.search-active .short-search-box .short-search-inner {
						position: relative !important;
						width: 100% !important;
						height: 100% !important;
						display: flex !important;
						align-items: center !important;
						margin: 0 !important;
						padding: 0 !important;
						gap: 6px !important;
					}
					.short-search-box .live-search-input,
					.short-search-box:not(.search-modal-active):not(.mobile-modal-active) .live-search-input {
						display: block !important;
						flex: 1 1 auto !important;
						min-width: 0 !important;
						width: 100% !important;
						font-size: 10.5px !important;
						line-height: normal !important;
						padding: 0 !important;
						margin: 0 !important;
					}
					.short-search-box.search-modal-active .live-search-input,
					.short-search-box.mobile-modal-active .live-search-input,
					.short-header.search-active .short-search-box .live-search-input {
						display: block !important;
						flex: 1 1 auto !important;
						min-width: 0 !important;
						width: 100% !important;
						height: 100% !important;
						font-size: 13px !important;
						font-weight: 500 !important;
						color: #ffffff !important;
						background: transparent !important;
						border: none !important;
						outline: none !important;
						line-height: normal !important;
						padding: 0 !important;
						margin: 0 !important;
						order: 1 !important;
					}
					.short-search-inner .live-search-input::placeholder {
						font-size: 10px !important;
						letter-spacing: -0.2px !important;
						color: rgba(255, 255, 255, 0.5) !important;
						white-space: nowrap !important;
					}
					.short-search-box.search-modal-active .live-search-input::placeholder,
					.short-search-box.mobile-modal-active .live-search-input::placeholder,
					.short-header.search-active .short-search-box .live-search-input::placeholder {
						font-size: 12.5px !important;
						color: rgba(255, 255, 255, 0.45) !important;
					}
					.short-search-inner .search-input-divider {
						display: inline-block !important;
						width: 1px !important;
						height: 9.5px !important;
						background: rgba(255, 255, 255, 0.08) !important;
						margin: 0 3px 0 4px !important;
						flex-shrink: 0 !important;
					}
					.short-search-box.search-modal-active .search-input-divider,
					.short-search-box.mobile-modal-active .search-input-divider,
					.short-header.search-active .short-search-box .search-input-divider {
						display: none !important;
					}
					.short-search-inner .search-toggle-btn {
						padding: 0 !important;
						margin: 0 !important;
						width: 18px !important;
						height: 18px !important;
						min-width: 18px !important;
						flex-shrink: 0 !important;
						display: inline-flex !important;
						align-items: center !important;
						justify-content: center !important;
					}
					.short-search-box.search-modal-active .search-toggle-btn,
					.short-search-box.mobile-modal-active .search-toggle-btn,
					.short-header.search-active .short-search-box .search-toggle-btn {
						order: 2 !important;
						position: static !important;
						width: 24px !important;
						height: 24px !important;
						min-width: 24px !important;
						display: inline-flex !important;
						align-items: center !important;
						justify-content: center !important;
						padding: 0 !important;
						margin: 0 !important;
						background: transparent !important;
						border: none !important;
						color: #ffffff !important;
						cursor: pointer !important;
						flex-shrink: 0 !important;
					}
					.short-search-inner .search-toggle-btn svg {
						width: 15px !important;
						height: 15px !important;
						max-width: 15px !important;
						max-height: 15px !important;
						display: block !important;
						opacity: 0.9 !important;
					}
					.short-search-box.search-modal-active .search-toggle-btn svg,
					.short-search-box.mobile-modal-active .search-toggle-btn svg,
					.short-header.search-active .short-search-box .search-toggle-btn svg {
						width: 15px !important;
						height: 15px !important;
						opacity: 0.85 !important;
					}
					.short-search-box .mobile-search-close-btn {
						display: none !important;
					}
					.short-search-box.search-modal-active .mobile-search-close-btn,
					.short-search-box.mobile-modal-active .mobile-search-close-btn,
					.short-header.search-active .short-search-box .mobile-search-close-btn {
						order: -1 !important;
						position: static !important;
						display: inline-flex !important;
						align-items: center !important;
						justify-content: center !important;
						width: 24px !important;
						height: 24px !important;
						min-width: 24px !important;
						background: transparent !important;
						border: none !important;
						color: #ffffff !important;
						padding: 0 !important;
						margin: 0 !important;
						cursor: pointer !important;
						flex-shrink: 0 !important;
						opacity: 0.85 !important;
						transition: opacity 0.15s ease !important;
					}
					.short-search-box.search-modal-active .mobile-search-close-btn:hover,
					.short-search-box.mobile-modal-active .mobile-search-close-btn:hover,
					.short-header.search-active .short-search-box .mobile-search-close-btn:hover {
						opacity: 1 !important;
					}
					.short-search-box.search-modal-active .mobile-search-close-btn svg,
					.short-search-box.mobile-modal-active .mobile-search-close-btn svg,
					.short-header.search-active .short-search-box .mobile-search-close-btn svg {
						width: 17px !important;
						height: 17px !important;
						stroke: #ffffff !important;
					}
					.short-search-box.search-modal-active .short-search-dropdown,
					.short-search-box.mobile-modal-active .short-search-dropdown,
					.short-header.search-active .short-search-box .short-search-dropdown {
						display: none !important;
						position: absolute !important;
						top: calc(100% + 10px) !important;
						left: 0 !important;
						right: 0 !important;
						width: 100% !important;
						max-width: 100% !important;
						max-height: calc(100dvh - 80px) !important;
						background: transparent !important;
						background-color: transparent !important;
						backdrop-filter: none !important;
						-webkit-backdrop-filter: none !important;
						border: none !important;
						border-radius: 0 !important;
						box-shadow: none !important;
						padding: 4px 0 30px 0 !important;
						margin: 0 !important;
						overflow-y: auto !important;
						z-index: 100000 !important;
					}
					.short-search-box.search-modal-active .short-search-dropdown.show,
					.short-search-box.mobile-modal-active .short-search-dropdown.show,
					.short-header.search-active .short-search-box .short-search-dropdown.show {
						display: flex !important;
						flex-direction: column !important;
						gap: 6px !important;
					}
					.short-search-box.search-modal-active .search-item,
					.short-search-box.mobile-modal-active .search-item,
					.short-header.search-active .short-search-box .search-item {
						padding: 8px 10px !important;
						background: #15151b !important;
						background-color: #15151b !important;
						border: 1px solid rgba(255, 255, 255, 0.05) !important;
						border-radius: 9px !important;
					}

					/* Page Dim Darkened Backdrop when Mobile Search is Active */
					body.search-header-active::after {
						content: "" !important;
						position: fixed !important;
						inset: 0 !important;
						top: 0 !important;
						background: rgba(10, 10, 14, 0.95) !important;
						backdrop-filter: none !important;
						-webkit-backdrop-filter: none !important;
						z-index: 99998 !important;
						pointer-events: auto !important;
					}
					.reel-header-icon-btn,
					.reel-lang-trigger {
						width: 26px !important;
						height: 26px !important;
						min-width: 26px !important;
						max-width: 26px !important;
						padding: 0 !important;
						background: transparent !important;
						border: none !important;
						box-shadow: none !important;
						color: #ffffff !important;
						flex-shrink: 0 !important;
						display: inline-flex !important;
						align-items: center !important;
						justify-content: center !important;
					}
					.reel-header-icon-btn svg,
					.reel-lang-trigger svg {
						width: 15px !important;
						height: 15px !important;
					}
					.reel-lang-trigger span,
					.reel-lang-trigger .chevron-icon {
						display: none !important;
					}
					.reel-header-history-btn {
						display: none !important;
					}
					.btn-reel-topup,
					.header-coin-topup-merged {
						height: 24px !important;
						min-height: 24px !important;
						max-height: 24px !important;
						padding: 0 8px !important;
						font-size: 11px !important;
						gap: 3px !important;
					}
					.header-coin-topup-merged .coin-icon {
						font-size: 11px !important;
					}
					.header-coin-topup-merged .coin-val,
					.header-coin-topup-merged #header-user-coins-val {
						font-size: 11px !important;
					}
				}

				/* Global Coins Pill Button */
				.btn-reel-topup,
				.header-coin-topup-merged,
				a.btn-reel-topup,
				a.header-coin-topup-merged {
					background: var(--theme-accent, #ff2d55) !important;
					background-color: var(--theme-accent, #ff2d55) !important;
					color: #ffffff !important;
					font-size: 12.5px !important;
					font-weight: 800 !important;
					height: 30px !important;
					min-height: 30px !important;
					max-height: 30px !important;
					box-sizing: border-box !important;
					padding: 0 12px !important;
					border-radius: 999px !important;
					text-decoration: none !important;
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: 6px !important;
					white-space: nowrap !important;
					flex-shrink: 0 !important;
					box-shadow: 0 3px 12px rgba(0, 0, 0, 0.3) !important;
					transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease, filter 0.2s ease !important;
					border: 1px solid rgba(255, 255, 255, 0.18) !important;
					position: relative !important;
					z-index: 3 !important;
					vertical-align: middle !important;
				}
				.btn-reel-topup:hover,
				.header-coin-topup-merged:hover,
				a.btn-reel-topup:hover,
				a.header-coin-topup-merged:hover,
				.btn-reel-topup:focus,
				.header-coin-topup-merged:focus {
					background: var(--theme-accent, #ff2d55) !important;
					background-color: var(--theme-accent, #ff2d55) !important;
					filter: brightness(1.12) !important;
					transform: translateY(-1px) scale(1.02) !important;
					box-shadow: 0 6px 18px rgba(0, 0, 0, 0.5), 0 0 14px rgba(255, 255, 255, 0.25) !important;
					color: #ffffff !important;
					z-index: 10 !important;
				}

				.reel-header,
				.short-header-container,
				.short-header-right,
				.reel-header-right,
				.short-profile-menu,
				.user-profile-widget {
					overflow: visible !important;
				}

				/* Header Desktop Icons Group */
				.reel-header-desktop-icons {
					display: inline-flex;
					align-items: center;
					gap: 12px;
					margin-right: 4px;
				}

				/* History Button with Text & Icon */
				.reel-header-history-btn {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					gap: 6px;
					color: #e2e8f0;
					background: rgba(255, 255, 255, 0.06);
					border: 1px solid rgba(255, 255, 255, 0.12);
					font-size: 13px;
					font-weight: 700;
					line-height: 1;
					cursor: pointer;
					padding: 7px 14px;
					border-radius: 20px;
					text-decoration: none !important;
					transition: all 0.2s;
					user-select: none;
					flex-shrink: 0;
					box-sizing: border-box;
				}
				.reel-header-history-btn svg {
					display: block;
					flex-shrink: 0;
				}
				.reel-header-history-btn span {
					display: inline-block;
					line-height: 1;
				}
				.reel-header-history-btn:hover {
					color: #ffffff !important;
					background: rgba(255, 255, 255, 0.14);
					border-color: rgba(255, 255, 255, 0.25);
				}

				/* Interactive Language Dropdown */
				.reel-lang-dropdown-wrap {
					position: relative;
					display: inline-block;
					flex-shrink: 0;
				}
				.reel-lang-trigger {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					gap: 6px;
					color: #e2e8f0;
					background: rgba(255, 255, 255, 0.06);
					border: 1px solid rgba(255, 255, 255, 0.12);
					font-size: 13px;
					font-weight: 700;
					line-height: 1;
					cursor: pointer;
					padding: 7px 14px;
					border-radius: 20px;
					transition: all 0.2s;
					user-select: none;
					flex-shrink: 0;
					box-sizing: border-box;
				}
				.reel-lang-trigger svg {
					display: block;
					flex-shrink: 0;
				}
				.reel-lang-trigger span {
					display: inline-block;
					line-height: 1;
				}
				.reel-lang-trigger:hover, .reel-lang-trigger.is-open {
					color: #ffffff;
					background: rgba(255, 255, 255, 0.14);
					border-color: rgba(255, 255, 255, 0.25);
				}
				.reel-lang-trigger svg.chevron-icon {
					transition: transform 0.2s;
				}
				.reel-lang-trigger.is-open svg.chevron-icon {
					transform: rotate(180deg);
				}
				.reel-lang-menu {
					position: absolute;
					top: calc(100% + 8px);
					right: 0;
					width: 190px;
					max-height: 320px;
					overflow-y: auto;
					background: #18181c;
					border: 1px solid #2e2e36;
					border-radius: 12px;
					box-shadow: 0 14px 36px rgba(0, 0, 0, 0.85);
					padding: 6px;
					display: none;
					z-index: 999999 !important;
					backdrop-filter: blur(14px);
				}
				.reel-lang-menu.is-open {
					display: block !important;
					animation: langFadeIn 0.15s ease;
				}
				@keyframes langFadeIn {
					from { opacity: 0; transform: translateY(-4px); }
					to { opacity: 1; transform: translateY(0); }
				}

				/* VIP & Profile Avatar Styles */
				.profile-avatar-trigger,
				.profile-avatar-trigger,
				.reel-avatar-trigger {
					position: relative;
					cursor: pointer;
					background: rgba(255, 255, 255, 0.08) !important;
					border: 1px solid rgba(255, 255, 255, 0.14) !important;
					padding: 0 !important;
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					border-radius: 50% !important;
					width: 32px !important;
					height: 32px !important;
					color: #ffffff !important;
					transition: all 0.2s ease !important;
					box-sizing: border-box !important;
				}
				.profile-avatar-trigger:hover,
				.reel-avatar-trigger:hover {
					background: rgba(255, 255, 255, 0.18) !important;
					border-color: rgba(255, 255, 255, 0.3) !important;
					color: #ffffff !important;
					transform: scale(1.05) !important;
				}
				.header-default-user-icon {
					width: 18px !important;
					height: 18px !important;
					stroke: currentColor !important;
					display: block !important;
					margin: auto !important;
				}
				.header-avatar-img,
				.reel-avatar-img {
					position: absolute !important;
					top: 0 !important;
					left: 0 !important;
					width: 100% !important;
					height: 100% !important;
					border-radius: 50% !important;
					background: transparent !important;
					object-fit: cover !important;
					display: none !important;
					border: none !important;
					margin: 0 !important;
					padding: 0 !important;
				}
				.header-avatar-img.has-custom-avatar {
					display: block !important;
				}
				.header-vip-badge {
					position: absolute;
					bottom: -3px;
					right: -5px;
					background: linear-gradient(135deg, #ff2d55 0%, #f59e0b 100%);
					color: #ffffff;
					font-size: 9px;
					font-weight: 900;
					letter-spacing: 0.4px;
					padding: 1px 5px;
					border-radius: 6px;
					display: inline-flex;
					align-items: center;
					gap: 2px;
					border: 1.5px solid #111317;
					box-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
					z-index: 2;
					pointer-events: none;
					line-height: 1.1;
				}
				.profile-avatar-trigger.has-active-vip .header-avatar-img {
					border: 2px solid #ff2d55 !important;
					box-shadow: 0 0 10px rgba(255, 45, 85, 0.55) !important;
				}

				/* Header Coin Pill Badge */
				.header-coin-wallet-pill {
					display: inline-flex;
					align-items: center;
					gap: 5px;
					background: rgba(255, 193, 7, 0.12);
					border: 1px solid rgba(255, 193, 7, 0.35);
					padding: 5px 12px;
					border-radius: 999px;
					text-decoration: none;
					color: #ffc107;
					font-size: 12.5px;
					font-weight: 800;
					letter-spacing: 0.2px;
					transition: all 0.2s ease;
					white-space: nowrap;
					flex-shrink: 0;
				}
				.header-coin-wallet-pill:hover {
					background: rgba(255, 193, 7, 0.22);
					border-color: #ffc107;
					color: #ffe082;
					transform: translateY(-1px);
				}
				@media (max-width: 600px) {
					.header-coin-wallet-pill {
						padding: 4px 8px;
						font-size: 11.5px;
					}
				}
				.reel-lang-item {
					display: flex;
					align-items: center;
					justify-content: space-between;
					padding: 8px 12px;
					border-radius: 8px;
					color: #cbd5e1;
					font-size: 13px;
					font-weight: 600;
					cursor: pointer;
					text-decoration: none;
					transition: background 0.15s, color 0.15s;
				}
				.reel-lang-item:hover {
					background: rgba(255, 45, 85, 0.15);
					color: #ff4c8b;
				}
				.reel-lang-item.is-active {
					color: #ff2d55;
					font-weight: 800;
					background: rgba(255, 45, 85, 0.12);
				}
				.reel-lang-item .check-icon {
					display: none;
				}
				.reel-lang-item.is-active .check-icon {
					display: inline-block;
				}
				@media (max-width: 768px) {
					.btn-reel-topup {
						padding: 5px 12px !important;
						font-size: 12px !important;
					}
					.reel-lang-trigger span, .reel-header-history-btn span {
						display: none;
					}
					.reel-lang-trigger, .reel-header-history-btn {
						padding: 6px 8px;
					}
				}

				/* Google Translate Complete Suppression */
				html, body {
					top: 0px !important;
					position: static !important;
					margin-top: 0px !important;
				}
				.goog-te-banner-frame,
				iframe.goog-te-banner-frame,
				.goog-te-banner-frame.skiptranslate,
				iframe.VIpgJd-ZVi9od-ORHb-OooU9b-fmcmS,
				.VIpgJd-ZVi9od-ORHb-OooU9b-fmcmS,
				.VIpgJd-ZVi9od-ORHb,
				.goog-te-banner,
				iframe[id^=":"],
				iframe[name*="goog"],
				iframe[id*="google"],
				.skiptranslate iframe,
				body > .skiptranslate:not(.reel-lang-dropdown-wrap),
				.VIpgJd-ZVi9od-aZ2wEe-wOHMyf,
				.VIpgJd-ZVi9od-aZ2wEe-OiiCO,
				.goog-te-gadget,
				#goog-gt-tt,
				.goog-te-balloon-frame,
				.goog-tooltip,
				.goog-tooltip:hover {
					display: none !important;
					visibility: hidden !important;
					height: 0 !important;
					width: 0 !important;
					opacity: 0 !important;
					pointer-events: none !important;
					z-index: -9999 !important;
				}
				.goog-text-highlight {
					background-color: transparent !important;
					background: none !important;
					border: none !important;
					box-shadow: none !important;
				}
				#google_translate_element,
				.goog-te-spinner-pos {
					display: none !important;
				}
			</style>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="short-brand-logo reel-brand-logo <?php echo ! $show_m_logo ? 'short-m-hide' : ''; ?>" aria-label="<?php echo esc_attr( $brand_name ?: 'ShortTV' ); ?>" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $brand_name ?: 'ShortTV' ); ?>" class="reel-brand-logo-img" style="border-radius:6px;" onerror="this.src='https://i.postimg.cc/cH3CM5h4/image.png';">
				<?php if ( 'image_text' === $display_mode ) : ?>
					<span class="reel-brand-logo-text" style="font-size:18px;font-weight:800;color:#fff;letter-spacing:-0.3px;"><?php echo esc_html( $brand_name ); ?></span>
				<?php endif; ?>
			</a>
			<?php if ( ! $is_profile_mode ) : ?>
			<nav class="short-nav-desktop reel-nav-desktop">
				<?php
				if ( function_exists( 'short_render_primary_nav' ) ) {
					short_render_primary_nav();
				}
				?>
			</nav>
			<?php endif; ?>
		</div>

		<?php if ( ! $is_profile_mode ) : ?>
		<div class="short-header-right reel-header-right">

			<!-- Search Bar / Icon (Capsule Search) -->
			<div class="short-search-box <?php echo ! $show_m_search ? 'short-m-hide' : ''; ?>">
				<div class="short-search-inner">
					<button class="mobile-search-close-btn" id="mobile-search-close-btn" aria-label="<?php esc_attr_e( 'Back', 'short-stream' ); ?>" type="button">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
					</button>
					<input type="text" id="short-live-search-input" class="live-search-input" placeholder="<?php esc_attr_e( 'Search for title', 'short-stream' ); ?>" autocomplete="off">
					<span class="search-input-divider"></span>
					<button class="search-toggle-btn reel-header-icon-btn" id="search-toggle-btn" aria-label="<?php esc_attr_e( 'Search', 'short-stream' ); ?>" type="button">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					</button>
				</div>
				<div id="short-search-dropdown" class="short-search-dropdown"></div>
			</div>

			<?php if ( $show_m_history ) : ?>
			<!-- Watchlist / Watch History Shortcut (Clock) -->
			<a href="<?php echo esc_url( home_url( '/history/' ) ); ?>" class="reel-header-mhistory-btn" id="header-mobile-history-btn" title="<?php esc_attr_e( 'Watchlist', 'short-stream' ); ?>" aria-label="<?php esc_attr_e( 'Watchlist', 'short-stream' ); ?>">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10"></circle>
					<polyline points="12 6 12 12 16 14"></polyline>
				</svg>
				<span class="reel-header-btn-text"><?php _e( 'Watchlist', 'short-stream' ); ?></span>
			</a>
			<?php endif; ?>

			<?php if ( $show_m_mylist ) : ?>
			<!-- Bookmark / My List Shortcut -->
			<a href="<?php echo esc_url( home_url( '/my-list/' ) ); ?>" class="reel-header-mylist-btn" id="header-mobile-mylist-btn" title="<?php esc_attr_e( 'My List', 'short-stream' ); ?>" aria-label="<?php esc_attr_e( 'My List', 'short-stream' ); ?>">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>
				</svg>
				<span class="reel-header-btn-text"><?php _e( 'My List', 'short-stream' ); ?></span>
			</a>
			<?php endif; ?>

			<!-- Interactive Language Dropdown -->
			<div class="reel-lang-dropdown-wrap <?php echo ! $show_m_lang ? 'short-m-hide' : ''; ?>" id="reel-lang-dropdown-wrap">
				<button type="button" class="reel-lang-trigger" id="reel-lang-trigger" onclick="window.toggleHeaderLang(event)" aria-haspopup="true" aria-expanded="false" title="Select Language">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
					<span id="current-lang-label">English</span>
					<svg class="chevron-icon" width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
				</button>
				<div class="reel-lang-menu" id="reel-lang-menu" role="menu">
					<div class="reel-lang-item is-active" onclick="window.selectHeaderLang('en', 'English', event)" data-lang="en" data-name="English">
						<span>English</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('es', 'Español', event)" data-lang="es" data-name="Español">
						<span>Español</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('fr', 'Français', event)" data-lang="fr" data-name="Français">
						<span>Français</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('de', 'Deutsch', event)" data-lang="de" data-name="Deutsch">
						<span>Deutsch</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('pt', 'Português', event)" data-lang="pt" data-name="Português">
						<span>Português</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('ja', '日本語', event)" data-lang="ja" data-name="日本語">
						<span>日本語</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('ko', '한국어', event)" data-lang="ko" data-name="한국어">
						<span>한국어</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('zh-tw', '繁體中文', event)" data-lang="zh-tw" data-name="繁體中文">
						<span>繁體中文</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('zh-cn', '简体中文', event)" data-lang="zh-cn" data-name="简体中文">
						<span>简体中文</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('id', 'Indonesia', event)" data-lang="id" data-name="Indonesia">
						<span>Bahasa Indonesia</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('tl', 'Tagalog', event)" data-lang="tl" data-name="Tagalog">
						<span>Tagalog / Filipino</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('vi', 'Tiếng Việt', event)" data-lang="vi" data-name="Tiếng Việt">
						<span>Tiếng Việt</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('th', 'ไทย', event)" data-lang="th" data-name="ไทย">
						<span>ภาษาไทย</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
					<div class="reel-lang-item" onclick="window.selectHeaderLang('ar', 'العربية', event)" data-lang="ar" data-name="العربية">
						<span>العربية</span>
						<svg class="check-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
					</div>
				</div>
			</div>

			<!-- Header Coins Wallet Pill -->
			<a href="<?php echo esc_url( home_url( '/subscription/?tab=coins' ) ); ?>" class="btn-reel-topup header-coin-topup-merged <?php echo ! $show_m_coins ? 'short-m-hide' : ''; ?>" id="header-coin-topup-merged" title="My Drama Coins — Click to Manage & Top Up">
				<span class="coin-icon" style="display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:inline-block; vertical-align:middle; flex-shrink:0;"><circle cx="12" cy="12" r="9.5" fill="#f59e0b" stroke="#fbbf24" stroke-width="1.6"/><circle cx="12" cy="12" r="6.8" stroke="#fef08a" stroke-width="1.2" fill="none"/><path d="M15.2 9.2 A3.8 3.8 0 1 0 15.2 14.8" fill="none" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round"/></svg>
				</span>
				<span class="coin-val" id="header-user-coins-val" style="font-weight: 800; font-size: 14px; color: #ffffff !important; display: inline-block !important;">100</span>
			</a>

			<!-- Profile / Auth Menu (ReelShort Avatar) -->
			<div class="short-profile-menu user-profile-widget" id="header-user-profile-menu">
				<div class="profile-avatar-trigger reel-avatar-trigger" id="profile-avatar-trigger" title="Profile & Account" onclick="window.toggleProfileDropdown(event)" aria-haspopup="true" aria-expanded="false">
					<svg class="header-default-user-icon" id="header-default-user-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
						<circle cx="12" cy="7" r="4"></circle>
					</svg>
					<img id="header-current-avatar" referrerpolicy="no-referrer" src="" alt="Profile" class="header-avatar-img reel-avatar-img" style="display:none;" onerror="this.style.display='none'; var s=document.getElementById('header-default-user-icon'); if(s) s.style.display='block';">
					<span class="header-vip-badge" id="header-vip-badge" style="display:none;" title="VIP Active">
						<svg width="9" height="9" viewBox="0 0 24 24" fill="#ffd700"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
						<span>VIP</span>
					</span>
				</div>

				<div class="profile-dropdown-menu" id="profile-dropdown-menu">
					<div class="profile-dropdown-userinfo" id="header-user-meta-box" style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 6px;">
						<div style="display:flex; align-items:center; gap:6px;">
							<div style="font-weight: 700; font-size: 13.5px; color: #fff;" id="header-dropdown-name">Guest Viewer</div>
							<span class="header-dropdown-vip-tag" id="header-dropdown-vip-tag" style="display:none; font-size:10px; font-weight:800; background:linear-gradient(135deg, #ff2d55, #f59e0b); color:#fff; padding:2px 6px; border-radius:4px; letter-spacing:0.5px;">VIP</span>
						</div>
						<div style="font-size: 11.5px; color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" id="header-dropdown-email">Not signed in</div>
					</div>
					<a href="<?php echo esc_url( home_url( '/my-list/' ) ); ?>" class="dropdown-item">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
						<span><?php _e( 'My List', 'short-stream' ); ?></span>
					</a>
					<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" class="dropdown-item dropdown-item-accent">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
						<span class="dropdown-item-accent-label"><?php _e( 'My Subscription', 'short-stream' ); ?></span>
					</a>
					<a href="<?php echo esc_url( home_url( '/notification/' ) ); ?>" class="dropdown-item" id="header-dropdown-notifications-btn">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
						<span style="flex:1;"><?php _e( 'Notifications', 'short-stream' ); ?></span>
						<span class="settings-inline-notif-badge" id="dropdown-notif-unread-count" style="display:none; position:static !important; background:var(--theme-accent, #ff2d55); color:#ffffff; font-size:10.5px; font-weight:800; padding:2px 7px; border-radius:999px; margin-left:auto !important; line-height:1;"></span>
					</a>
					<a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" class="dropdown-item">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
						<span><?php _e( 'Settings', 'short-stream' ); ?></span>
					</a>
					<div class="profile-menu-divider"></div>
					<button type="button" id="short-btn-logout" class="dropdown-item logout-btn" style="display:none;">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
						<span><?php _e( 'Sign Out of Short', 'short-stream' ); ?></span>
					</button>
					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" id="short-btn-login-nav" class="dropdown-item" style="color: var(--theme-accent, #ff2d55); font-weight: 700;">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
						<span><?php _e( 'Sign In / Register', 'short-stream' ); ?></span>
					</a>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php if ( ! $is_profile_mode && $show_m_genres ) : ?>
		<div class="short-mobile-genre-bar">
			<?php
			if ( function_exists( 'short_render_mobile_top_header_nav' ) ) {
				short_render_mobile_top_header_nav();
			}
			?>
		</div>
		<?php endif; ?>
</header>

<script>
// Real-time Header Scroll State Controller (Instantly toggles .scrolled on header and body)
(function() {
	function updateHeaderScrollState() {
		var header = document.getElementById('short-header') || document.querySelector('.reel-header') || document.querySelector('.short-header');
		if (!header) return;
		var scrollY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
		if (scrollY > 15) {
			if (!header.classList.contains('scrolled')) header.classList.add('scrolled');
			if (!document.body.classList.contains('scrolled')) document.body.classList.add('scrolled');
		} else {
			if (header.classList.contains('scrolled')) header.classList.remove('scrolled');
			if (document.body.classList.contains('scrolled')) document.body.classList.remove('scrolled');
		}
	}
	window.addEventListener('scroll', updateHeaderScrollState, { passive: true });
	document.addEventListener('scroll', updateHeaderScrollState, { passive: true });
	window.addEventListener('touchmove', updateHeaderScrollState, { passive: true });
	updateHeaderScrollState();
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', updateHeaderScrollState);
	}
})();

window.syncHeaderCoins = function() {
	var c = localStorage.getItem('shorttv_user_coins') || '100';
	var el = document.getElementById('header-user-coins-val');
	if (el) el.textContent = c;
};

window.syncMobileNavAvatar = function() {
	var wrap = document.getElementById('mobile-nav-account-icon-wrap');
	var headerAvatar = document.getElementById('header-current-avatar');
	var defaultIcon = document.getElementById('header-default-user-icon');
	var isLoggedIn = (localStorage.getItem('short_is_logged_in') === '1') && !!localStorage.getItem('short_user_email');
	var photo = localStorage.getItem('short_user_photo') || localStorage.getItem('shorttv_user_avatar') || localStorage.getItem('short_active_avatar') || '';
	
	if (isLoggedIn && photo && !photo.includes('avatar-') && !photo.includes('avatar_') && (photo.startsWith('http') || photo.startsWith('data:'))) {
		if (wrap) {
			wrap.innerHTML = '<img id="mobile-nav-account-avatar" src="' + photo + '" alt="Account" class="mobile-nav-avatar-img" referrerpolicy="no-referrer" style="width:24px;height:24px;min-width:24px;min-height:24px;border-radius:50%;object-fit:cover;display:block;border:none;outline:none;box-shadow:none;padding:0;margin:0;">';
		}
		if (headerAvatar) {
			headerAvatar.src = photo;
			headerAvatar.style.display = 'block';
			headerAvatar.classList.add('has-custom-avatar');
		}
		if (defaultIcon) defaultIcon.style.display = 'none';
	} else {
		if (wrap) {
			wrap.innerHTML = '<svg id="mobile-nav-account-default-svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
		}
		if (headerAvatar) {
			headerAvatar.src = '';
			headerAvatar.style.display = 'none';
			headerAvatar.classList.remove('has-custom-avatar');
		}
		if (defaultIcon) defaultIcon.style.display = 'block';
	}
};
document.addEventListener('DOMContentLoaded', window.syncMobileNavAvatar);

window.syncHeaderVipBadge = function() {
	var vipBadge = document.getElementById('header-vip-badge');
	var dropVip = document.getElementById('header-dropdown-vip-tag');
	var trigger = document.getElementById('profile-avatar-trigger');
	var subStr = localStorage.getItem('short_subscription');
	var isVip = false;
	if (subStr) {
		try {
			var sub = JSON.parse(subStr);
			if (sub && (sub.status === 'active' || sub.plan_id)) {
				isVip = true;
			}
		} catch(e) {}
	}
	var subTier = localStorage.getItem('short_sub_tier');
	if (subTier && subTier !== 'free') {
		isVip = true;
	}
	if (vipBadge) vipBadge.style.display = isVip ? 'inline-flex' : 'none';
	if (dropVip) dropVip.style.display = isVip ? 'inline-block' : 'none';
	if (trigger) {
		if (isVip) {
			trigger.classList.add('has-active-vip');
		} else {
			trigger.classList.remove('has-active-vip');
		}
	}
	if (typeof window.syncHeaderCoins === 'function') {
		window.syncHeaderCoins();
	}
};
window.toggleHeaderLang = function(e) {
	if (e) { e.preventDefault(); e.stopPropagation(); }
	var menu = document.getElementById('reel-lang-menu');
	var trigger = document.getElementById('reel-lang-trigger');
	if (!menu || !trigger) return;
	var isOpen = menu.classList.contains('is-open');
	if (isOpen) {
		menu.classList.remove('is-open');
		trigger.classList.remove('is-open');
		trigger.setAttribute('aria-expanded', 'false');
	} else {
		menu.classList.add('is-open');
		trigger.classList.add('is-open');
		trigger.setAttribute('aria-expanded', 'true');
	}
};

window.selectHeaderLang = function(code, name, e) {
	if (e) { e.preventDefault(); e.stopPropagation(); }
	var menu = document.getElementById('reel-lang-menu');
	var trigger = document.getElementById('reel-lang-trigger');
	var label = document.getElementById('current-lang-label');
	if (label) label.textContent = name;

	if (menu) {
		menu.querySelectorAll('.reel-lang-item').forEach(function(el) {
			if (el.getAttribute('data-lang').toLowerCase() === code.toLowerCase()) {
				el.classList.add('is-active');
			} else {
				el.classList.remove('is-active');
			}
		});
		menu.classList.remove('is-open');
	}
	if (trigger) {
		trigger.classList.remove('is-open');
		trigger.setAttribute('aria-expanded', 'false');
	}
	try {
		localStorage.setItem('shorttv_lang', code);
		localStorage.setItem('shorttv_lang_name', name);
		document.documentElement.lang = code;

		// Normalize Google Translate language code
		var gtCode = code;
		if (code === 'zh-cn') gtCode = 'zh-CN';
		if (code === 'zh-tw') gtCode = 'zh-TW';

		var domain = window.location.hostname;
		var langPath = (gtCode === 'en') ? '' : ('/auto/' + gtCode);

		if (gtCode === 'en') {
			document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
			document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; domain=' + domain + '; path=/;';
		} else {
			document.cookie = 'googtrans=' + langPath + '; path=/;';
			document.cookie = 'googtrans=' + langPath + '; domain=' + domain + '; path=/;';
		}

		// Trigger Google Translate live
		var teCombo = document.querySelector('.goog-te-combo');
		if (teCombo) {
			teCombo.value = gtCode;
			teCombo.dispatchEvent(new Event('change'));
		} else {
			window.location.reload();
		}
	} catch(err) {}
};

window.toggleProfileDropdown = function(e) {
	if (e) { e.preventDefault(); e.stopPropagation(); }
	var menu = document.getElementById('profile-dropdown-menu');
	var trigger = document.getElementById('profile-avatar-trigger');
	if (!menu) return;
	var isOpen = menu.classList.contains('show');
	if (isOpen) {
		menu.classList.remove('show');
		if (trigger) trigger.setAttribute('aria-expanded', 'false');
	} else {
		// Close language menu if open
		var langMenu = document.getElementById('reel-lang-menu');
		var langTrigger = document.getElementById('reel-lang-trigger');
		if (langMenu) langMenu.classList.remove('is-open');
		if (langTrigger) {
			langTrigger.classList.remove('is-open');
			langTrigger.setAttribute('aria-expanded', 'false');
		}

		menu.classList.add('show');
		if (trigger) trigger.setAttribute('aria-expanded', 'true');
	}
};

document.addEventListener('click', function(e) {
	// Language dropdown outside click
	var wrap = document.getElementById('reel-lang-dropdown-wrap');
	var menu = document.getElementById('reel-lang-menu');
	var trigger = document.getElementById('reel-lang-trigger');
	if (wrap && !wrap.contains(e.target) && menu) {
		menu.classList.remove('is-open');
		if (trigger) {
			trigger.classList.remove('is-open');
			trigger.setAttribute('aria-expanded', 'false');
		}
	}

	// Profile dropdown outside click
	var pWrap = document.getElementById('header-user-profile-menu');
	var pMenu = document.getElementById('profile-dropdown-menu');
	var pTrigger = document.getElementById('profile-avatar-trigger');
	if (pMenu && pMenu.classList.contains('show')) {
		if (pWrap && !pWrap.contains(e.target)) {
			pMenu.classList.remove('show');
			if (pTrigger) pTrigger.setAttribute('aria-expanded', 'false');
		}
	}
});

window.syncHeaderAuthState = function() {
	try {
		var storedEmail = localStorage.getItem('short_user_email') || '';
		var isLog = (localStorage.getItem('short_is_logged_in') === '1') && !!storedEmail && storedEmail !== 'guest@short.local';
		var storedName = localStorage.getItem('short_user_display_name') || localStorage.getItem('short_active_profile_name') || (storedEmail ? storedEmail.split('@')[0] : '');

		var nameEl = document.getElementById('header-dropdown-name');
		var emailEl = document.getElementById('header-dropdown-email');
		var logoutBtn = document.getElementById('short-btn-logout');
		var loginBtn = document.getElementById('short-btn-login-nav');

		if (isLog) {
			document.documentElement.classList.remove('is-guest-state');
			if (nameEl) nameEl.textContent = storedName || 'Member';
			if (emailEl) emailEl.textContent = storedEmail;
			if (logoutBtn) logoutBtn.style.setProperty('display', 'flex', 'important');
			if (loginBtn) loginBtn.style.setProperty('display', 'none', 'important');
		} else {
			document.documentElement.classList.add('is-guest-state');
			if (nameEl) nameEl.textContent = 'Guest Viewer';
			if (emailEl) emailEl.textContent = 'Not signed in';
			if (logoutBtn) logoutBtn.style.setProperty('display', 'none', 'important');
			if (loginBtn) loginBtn.style.setProperty('display', 'flex', 'important');
		}
	} catch(e) {}
};

// Restore saved language and VIP status on load
(function() {
	try {
		window.syncHeaderAuthState();
		if (typeof window.syncHeaderVipBadge === 'function') {
			window.syncHeaderVipBadge();
		}
		window.addEventListener('storage', function() {
			window.syncHeaderAuthState();
			if (typeof window.syncHeaderVipBadge === 'function') {
				window.syncHeaderVipBadge();
			}
		});
		document.addEventListener('DOMContentLoaded', function() {
			window.syncHeaderAuthState();
			if (typeof window.syncHeaderVipBadge === 'function') {
				window.syncHeaderVipBadge();
			}
		});
		var saved = localStorage.getItem('shorttv_lang');
		var savedName = localStorage.getItem('shorttv_lang_name');
		if (saved && savedName) {
			var label = document.getElementById('current-lang-label');
			if (label) label.textContent = savedName;
			var menu = document.getElementById('reel-lang-menu');
			if (menu) {
				menu.querySelectorAll('.reel-lang-item').forEach(function(el) {
					if (el.getAttribute('data-lang').toLowerCase() === saved.toLowerCase()) {
						el.classList.add('is-active');
					} else {
						el.classList.remove('is-active');
					}
				});
			}

			// Ensure translation cookie matches
			if (saved !== 'en') {
				var gtCode = saved;
				if (saved === 'zh-cn') gtCode = 'zh-CN';
				if (saved === 'zh-tw') gtCode = 'zh-TW';
				var langPath = '/auto/' + gtCode;
				var domain = window.location.hostname;
				document.cookie = 'googtrans=' + langPath + '; path=/;';
				document.cookie = 'googtrans=' + langPath + '; domain=' + domain + '; path=/;';
			}
		}
	} catch(e) {}
})();
</script>

<!-- Google Translate Hidden Element & Script -->
<div id="google_translate_element" style="display:none;"></div>
<script type="text/javascript">
function googleTranslateElementInit() {
	if (window.google && window.google.translate) {
		new window.google.translate.TranslateElement({
			pageLanguage: 'en',
			autoDisplay: false
		}, 'google_translate_element');
	}
}
(function() {
	var fixTop = function() {
		if (document.body && document.body.style.top && document.body.style.top !== '0px') {
			document.body.style.setProperty('top', '0px', 'important');
		}
		if (document.documentElement && document.documentElement.style.top && document.documentElement.style.top !== '0px') {
			document.documentElement.style.setProperty('top', '0px', 'important');
		}
		var frames = document.querySelectorAll('iframe.goog-te-banner-frame, iframe[id^=":"]');
		frames.forEach(function(f) {
			f.style.setProperty('display', 'none', 'important');
			f.style.setProperty('visibility', 'hidden', 'important');
			f.style.setProperty('height', '0', 'important');
		});
		var skips = document.querySelectorAll('body > .skiptranslate');
		skips.forEach(function(s) {
			if (!s.classList.contains('reel-lang-dropdown-wrap')) {
				s.style.setProperty('display', 'none', 'important');
			}
		});
	};
	var observer = new MutationObserver(fixTop);
	if (document.body) {
		observer.observe(document.body, { attributes: true, childList: true, subtree: true, attributeFilter: ['style', 'class'] });
	}
	window.addEventListener('DOMContentLoaded', fixTop);
	window.addEventListener('load', fixTop);
	setInterval(fixTop, 250);
})();
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" async defer></script>
<?php endif; ?>
<main id="short-main-content" class="short-main-content">
