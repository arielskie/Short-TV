<?php
namespace SHORT\Core\Admin;

use SHORT\Core\Video_Sources\Video_Source_CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menus' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'wp_ajax_short_flush_cache', array( __CLASS__, 'ajax_flush_cache' ) );
		add_action( 'wp_ajax_short_save_sections', array( __CLASS__, 'ajax_save_sections' ) );
		add_action( 'wp_ajax_short_save_genre_tabs', array( __CLASS__, 'ajax_save_genre_tabs' ) );
		add_action( 'wp_ajax_short_reset_sections', array( __CLASS__, 'ajax_reset_sections' ) );
		add_action( 'wp_ajax_short_save_presets', array( __CLASS__, 'ajax_save_presets' ) );
		add_action( 'wp_ajax_short_reset_presets', array( __CLASS__, 'ajax_reset_presets' ) );
		add_action( 'wp_ajax_short_test_endpoint', array( __CLASS__, 'ajax_test_endpoint' ) );
		add_action( 'wp_ajax_short_save_notification', array( __CLASS__, 'ajax_save_notification' ) );
		add_action( 'wp_ajax_short_delete_notification', array( __CLASS__, 'ajax_delete_notification' ) );
		add_action( 'wp_ajax_short_seed_smart_notifications', array( __CLASS__, 'ajax_seed_smart_notifications' ) );
		add_action( 'wp_ajax_short_upload_notif_image', array( __CLASS__, 'ajax_upload_notif_image' ) );
		add_action( 'wp_ajax_short_register_fcm_token', array( __CLASS__, 'ajax_register_fcm_token' ) );
		add_action( 'wp_ajax_nopriv_short_register_fcm_token', array( __CLASS__, 'ajax_register_fcm_token' ) );
		add_action( 'wp_ajax_short_restore_all_system_defaults', array( __CLASS__, 'ajax_restore_all_system_defaults' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_webhook_routes' ) );
		// Backward compatibility AJAX hooks
		add_action( 'wp_ajax_short_save_section_tab_titles', array( __CLASS__, 'ajax_save_section_tab_titles' ) );
		add_action( 'wp_ajax_short_save_section_tabs_config', array( __CLASS__, 'ajax_save_section_tabs_config' ) );
		add_action( 'wp_ajax_short_save_mobile_header_config', array( __CLASS__, 'ajax_save_mobile_header_config' ) );
		add_action( 'wp_ajax_short_reset_mobile_header_config', array( __CLASS__, 'ajax_reset_mobile_header_config' ) );
		add_action( 'admin_menu', array( __CLASS__, 'clean_duplicate_submenus' ), 999 );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'customize_wp_dashboard_widgets' ) );
		add_action( 'welcome_panel', array( __CLASS__, 'render_shorttv_welcome_panel' ) );
	}

	public static function customize_wp_dashboard_widgets() {
		global $wp_meta_boxes;

		// Clean generic WordPress clutter
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );

		// Add custom ShortTV Dashboard Widgets
		wp_add_dashboard_widget(
			'shorttv_analytics_widget',
			'🎬 ' . __( 'ShortTV Drama Analytics & Video CDNs', 'short-stream-core' ),
			array( __CLASS__, 'render_shorttv_analytics_widget' )
		);

		wp_add_dashboard_widget(
			'shorttv_quick_actions_widget',
			'⚡ ' . __( 'ShortTV Quick Management & Shortcuts', 'short-stream-core' ),
			array( __CLASS__, 'render_shorttv_quick_shortcuts_widget' )
		);
	}

	public static function render_shorttv_welcome_panel() {
		$brand_settings = get_option( 'short_brand_settings', array() );
		$hub_logo = ! empty( $brand_settings['custom_logo_url'] ) ? $brand_settings['custom_logo_url'] : ( function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : '' );
		$hub_brand_name = ! empty( $brand_settings['brand_name'] ) ? $brand_settings['brand_name'] : 'ShortTV';
		$cpt_counts = wp_count_posts( \SHORT\Core\CPT\Video_CPT::POST_TYPE );
		$total_dramas_published = (int) ( $cpt_counts->publish ?? 0 );
		$total_dramas_draft     = (int) ( $cpt_counts->draft ?? 0 );
		$total_dramas_all       = $total_dramas_published + $total_dramas_draft;
		?>
		<div style="background:linear-gradient(135deg, #090d16 0%, #0f172a 100%); color:#fff; border-radius:14px; padding:28px 32px; margin:16px 0 20px 0; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid rgba(255,255,255,0.1);">
			<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:20px;">
				<div style="display:flex; align-items:center; gap:16px;">
					<?php if ( $hub_logo ) : ?>
						<img src="<?php echo esc_url( $hub_logo ); ?>" alt="<?php echo esc_attr( $hub_brand_name ); ?>" style="height:48px; width:auto; max-width:140px; object-fit:contain; border-radius:8px;" />
					<?php endif; ?>
					<div>
						<h2 style="margin:0 0 6px 0; color:#fff; font-size:24px; font-weight:800; display:flex; align-items:center; gap:10px;">
							<span><?php echo esc_html( $hub_brand_name ); ?></span>
							<span style="background:#E50914; color:#fff; font-size:11px; font-weight:700; padding:3px 8px; border-radius:4px; text-transform:uppercase; letter-spacing:0.5px;"><?php _e( 'Streaming Hub', 'short-stream-core' ); ?></span>
						</h2>
						<p style="margin:0; color:#94a3b8; font-size:13.5px;">
							<?php printf( esc_html__( 'Manage %d vertical short drama series, video storage CDNs, mobile reels, and coins paywalls.', 'short-stream-core' ), $total_dramas_all ); ?>
						</p>
					</div>
				</div>

				<div style="display:flex; gap:10px; flex-wrap:wrap;">
					<a href="<?php echo admin_url( 'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="button button-primary" style="background:#E50914 !important; border-color:#E50914 !important; height:38px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; font-weight:700; font-size:13px; padding:0 16px;">
						<span>＋ <?php _e( 'Add Drama', 'short-stream-core' ); ?></span>
					</a>
					<a href="<?php echo admin_url( 'edit.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="button button-secondary" style="background:rgba(255,255,255,0.12) !important; color:#fff !important; border:1px solid rgba(255,255,255,0.2) !important; height:38px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; font-weight:600; font-size:13px;">
						<span>🎬 <?php _e( 'All Dramas', 'short-stream-core' ); ?></span>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-video-storage' ); ?>" class="button button-secondary" style="background:rgba(255,255,255,0.12) !important; color:#fff !important; border:1px solid rgba(255,255,255,0.2) !important; height:38px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; font-weight:600; font-size:13px;">
						<span>☁️ <?php _e( 'Video Storage & CDN', 'short-stream-core' ); ?></span>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings' ); ?>" class="button button-secondary" style="background:rgba(255,255,255,0.12) !important; color:#fff !important; border:1px solid rgba(255,255,255,0.2) !important; height:38px; border-radius:6px; display:inline-flex; align-items:center; gap:6px; font-weight:600; font-size:13px;">
						<span>⚙️ <?php _e( 'Settings', 'short-stream-core' ); ?></span>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	public static function render_shorttv_analytics_widget() {
		$cpt_counts = wp_count_posts( \SHORT\Core\CPT\Video_CPT::POST_TYPE );
		$total_dramas_published = (int) ( $cpt_counts->publish ?? 0 );
		$total_dramas_draft     = (int) ( $cpt_counts->draft ?? 0 );
		$total_dramas_all       = $total_dramas_published + $total_dramas_draft;
		$license_data = \SHORT\Core\Admin\License::get_license_data();
		$is_licensed = ( 'active' === $license_data['status'] );
		?>
		<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap:14px; margin-bottom:16px;">
			<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; text-align:center;">
				<span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;"><?php _e( 'Total Short Dramas', 'short-stream-core' ); ?></span>
				<div style="font-size:24px; font-weight:900; color:#0f172a; margin:4px 0;"><?php echo $total_dramas_all; ?></div>
				<span style="font-size:11px; color:#059669; font-weight:600;"><?php echo $total_dramas_published; ?> <?php _e( 'Published', 'short-stream-core' ); ?></span>
			</div>

			<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; text-align:center;">
				<span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;"><?php _e( 'Video Streaming CDN', 'short-stream-core' ); ?></span>
				<div style="font-size:18px; font-weight:900; color:#0284c7; margin:6px 0;">Gumlet &amp; Cloudinary</div>
				<span style="font-size:11px; color:#0284c7; font-weight:600;">⚡ <?php _e( 'HLS Adaptive Streams', 'short-stream-core' ); ?></span>
			</div>

			<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; text-align:center;">
				<span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;"><?php _e( 'License Status', 'short-stream-core' ); ?></span>
				<div style="font-size:18px; font-weight:900; color:<?php echo $is_licensed ? '#059669' : '#dc2626'; ?>; margin:6px 0;">
					<?php echo $is_licensed ? '🟢 ' . __( 'Active', 'short-stream-core' ) : '🔴 ' . __( 'Unlicensed', 'short-stream-core' ); ?>
				</div>
				<span style="font-size:11px; color:#64748b;"><?php _e( 'Strict Domain Lock', 'short-stream-core' ); ?></span>
			</div>
		</div>

		<div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; border-top:1px solid #f1f5f9;">
			<span style="font-size:12px; color:#64748b;"><?php _e( 'Fast 9:16 vertical TikTok/Reels style streaming experience.', 'short-stream-core' ); ?></span>
			<a href="<?php echo admin_url( 'admin.php?page=short-stream' ); ?>" style="font-size:12px; font-weight:700; color:#0284c7; text-decoration:none;">
				<?php _e( 'Open Full Control Hub →', 'short-stream-core' ); ?>
			</a>
		</div>
		<?php
	}

	public static function render_shorttv_quick_shortcuts_widget() {
		?>
		<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
			<a href="<?php echo admin_url( 'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="button button-secondary" style="height:38px; display:flex; align-items:center; justify-content:center; gap:6px; font-weight:600; text-align:center;">
				<span>➕ <?php _e( 'Upload New Series', 'short-stream-core' ); ?></span>
			</a>
			<a href="<?php echo admin_url( 'admin.php?page=short-homepage' ); ?>" class="button button-secondary" style="height:38px; display:flex; align-items:center; justify-content:center; gap:6px; font-weight:600; text-align:center;">
				<span>🎞️ <?php _e( 'Homepage Sections', 'short-stream-core' ); ?></span>
			</a>
			<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=branding' ); ?>" class="button button-secondary" style="height:38px; display:flex; align-items:center; justify-content:center; gap:6px; font-weight:600; text-align:center;">
				<span>🎨 <?php _e( 'Brand Logo & Theme', 'short-stream-core' ); ?></span>
			</a>
			<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=subscription' ); ?>" class="button button-secondary" style="height:38px; display:flex; align-items:center; justify-content:center; gap:6px; font-weight:600; text-align:center;">
				<span>💎 <?php _e( 'Paywall & Coins', 'short-stream-core' ); ?></span>
			</a>
		</div>
		<?php
	}

	public static function clean_duplicate_submenus() {
		global $submenu;
		if ( isset( $submenu['short-stream'] ) && is_array( $submenu['short-stream'] ) ) {
			// Remove the first auto-generated duplicate child item
			array_shift( $submenu['short-stream'] );
		}
	}

	public static function register_admin_menus() {
		// Register ShortTV Dashboard & Control Hub as primary menu
		add_menu_page(
			__( 'ShortTV Hub', 'short-stream-core' ),
			__( 'ShortTV Hub', 'short-stream-core' ),
			'manage_options',
			'short-stream',
			array( __CLASS__, 'render_dashboard' ),
			'dashicons-video-alt3',
			2
		);

		// 1. ShortTV Dramas & Videos
		add_submenu_page(
			'short-stream',
			__( 'ShortTV Dramas', 'short-stream-core' ),
			__( '🎬 ShortTV Dramas', 'short-stream-core' ),
			'manage_options',
			'edit.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE
		);

		add_submenu_page(
			'short-stream',
			__( 'Add New Drama', 'short-stream-core' ),
			__( '<span style="color:#00df82;font-weight:900;margin-right:6px;font-size:14px;">＋</span>Add New Drama', 'short-stream-core' ),
			'manage_options',
			'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE
		);

		add_submenu_page(
			'short-stream',
			__( 'Content Blocks', 'short-stream-core' ),
			__( '🎞️ Content Blocks', 'short-stream-core' ),
			'manage_options',
			'short-homepage',
			array( __CLASS__, 'render_section_builder' )
		);

		add_submenu_page(
			'short-stream',
			__( 'Endpoint Presets', 'short-stream-core' ),
			__( '⚡ Endpoint Presets', 'short-stream-core' ),
			'manage_options',
			'short-presets',
			array( __CLASS__, 'render_presets_manager' )
		);

		add_submenu_page(
			'short-stream',
			__( 'Video Storage & CDN', 'short-stream-core' ),
			__( '☁️ Video Storage & CDN', 'short-stream-core' ),
			'manage_options',
			'short-video-storage',
			array( __CLASS__, 'render_video_storage_settings' )
		);

		add_submenu_page(
			'short-stream',
			__( 'Settings', 'short-stream-core' ),
			__( '⚙️ Settings', 'short-stream-core' ),
			'manage_options',
			'short-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function register_settings() {
		register_setting( 'short_brand_group', 'short_brand_settings' );
		register_setting( 'short_nav_group', 'short_header_nav_items' );
		register_setting( 'short_nav_group', 'short_mobile_header_config' );
		register_setting( 'short_nav_group', 'short_bottom_nav_sections' );
		register_setting( 'short_firebase_group', 'short_firebase_settings' );
		register_setting( 'short_player_group', 'short_player_settings' );
		register_setting( 'short_security_group', 'short_security_settings' );
		register_setting( 'short_subscription_group', 'short_subscription_settings' );
		register_setting( 'short_ad_group', 'short_ad_settings' );
		register_setting( 'short_splash_group', 'short_splash_settings' );

		// Cloudinary Settings Group
		register_setting( 'shorttv_cloudinary_group', 'shorttv_cloudinary_cloud_name' );
		register_setting( 'shorttv_cloudinary_group', 'shorttv_cloudinary_upload_preset' );
		register_setting( 'shorttv_cloudinary_group', 'shorttv_cloudinary_folder' );

		// Gumlet Video CDN Settings Group
		register_setting( 'short_gumlet_group', 'short_gumlet_enabled' );
		register_setting( 'short_gumlet_group', 'short_gumlet_workspace_id' );
		register_setting( 'short_gumlet_group', 'short_gumlet_folder_id' );
		register_setting( 'short_gumlet_group', 'short_gumlet_api_secret' );
		register_setting( 'short_gumlet_group', 'short_gumlet_drm_enabled' );
		register_setting( 'short_gumlet_group', 'short_gumlet_cache_ttl' );

		// Cloudflare R2 Storage Settings Group
		register_setting( 'short_r2_group', 'short_r2_enabled' );
		register_setting( 'short_r2_group', 'short_r2_account_id' );
		register_setting( 'short_r2_group', 'short_r2_access_key_id' );
		register_setting( 'short_r2_group', 'short_r2_secret_access_key' );
		register_setting( 'short_r2_group', 'short_r2_bucket_name' );
		register_setting( 'short_r2_group', 'short_r2_public_domain' );
		register_setting( 'short_r2_group', 'short_r2_folder' );
	}
	public static function render_dashboard() {
		$firebase = ( get_option( 'short_firebase_settings', array() ) ?: get_option( 'short_firebase_settings', array() ) );
		$sources_count = wp_count_posts( Video_Source_CPT::POST_TYPE )->publish ?? 0;
		?>
		<div class="wrap short-dashboard-wrap" style="max-width:1280px; margin-top:20px;">
			<style>
				.short-dash-header {
					background: linear-gradient(135deg, #141414 0%, #1f1f1f 50%, #2b1113 100%);
					border-radius: 12px;
					padding: 28px 32px;
					color: #ffffff;
					display: flex;
					align-items: center;
					justify-content: space-between;
					flex-wrap: wrap;
					gap: 20px;
					box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
					margin-bottom: 26px;
					border: 1px solid rgba(255, 255, 255, 0.1);
				}
				.short-dash-title-group h1 {
					margin: 0 0 6px 0;
					font-size: 26px;
					font-weight: 800;
					color: #ffffff;
					display: flex;
					align-items: center;
					gap: 12px;
					letter-spacing: -0.5px;
				}
				.short-dash-title-group p {
					margin: 0;
					color: #94a3b8;
					font-size: 14px;
				}
				.short-badge-netflix {
					background: #E50914;
					color: #fff;
					font-size: 11px;
					font-weight: 700;
					padding: 3px 8px;
					border-radius: 4px;
					text-transform: uppercase;
					letter-spacing: 0.5px;
				}
				.short-dash-quick-links {
					display: flex;
					gap: 10px;
					flex-wrap: wrap;
				}
				.short-header-btn {
					display: inline-flex !important;
					align-items: center !important;
					gap: 8px !important;
					height: 38px !important;
					padding: 0 16px !important;
					border-radius: 6px !important;
					font-size: 13px !important;
					font-weight: 600 !important;
					text-decoration: none !important;
					transition: all 0.2s ease !important;
					cursor: pointer;
				}
				.short-header-btn-primary {
					background: #E50914 !important;
					color: #ffffff !important;
					border: 1px solid #E50914 !important;
				}
				.short-header-btn-primary:hover {
					background: #b80710 !important;
					border-color: #b80710 !important;
					transform: translateY(-1px);
				}
				.short-header-btn-secondary {
					background: rgba(255, 255, 255, 0.12) !important;
					color: #ffffff !important;
					border: 1px solid rgba(255, 255, 255, 0.2) !important;
					backdrop-filter: blur(8px);
				}
				.short-header-btn-secondary:hover {
					background: rgba(255, 255, 255, 0.22) !important;
					color: #ffffff !important;
					transform: translateY(-1px);
				}

				.short-cards-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
					gap: 22px;
					margin-bottom: 30px;
				}
				.short-status-card {
					background: #ffffff;
					border: 1px solid #e2e8f0;
					border-radius: 14px;
					padding: 24px 22px 20px 22px;
					display: flex;
					flex-direction: column;
					justify-content: space-between;
					min-height: 220px;
					box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02);
					transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s ease, border-color 0.2s ease;
					position: relative;
					overflow: hidden;
					box-sizing: border-box !important;
				}
				.short-status-card:hover {
					transform: translateY(-3px);
					box-shadow: 0 12px 28px rgba(0, 0, 0, 0.07);
					border-color: #cbd5e1;
				}
				.short-status-card::before {
					content: '';
					position: absolute;
					top: 0;
					left: 0;
					right: 0;
					height: 4px;
				}
				.card-accent-blue::before { background: linear-gradient(90deg, #0284c7, #38bdf8); }
				.card-accent-amber::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
				.card-accent-emerald::before { background: linear-gradient(90deg, #059669, #34d399); }
				.card-accent-purple::before { background: linear-gradient(90deg, #7c3aed, #a78bfa); }

				.card-top-row {
					display: flex;
					align-items: center;
					justify-content: space-between;
					margin-bottom: 16px;
				}
				.card-icon-bubble {
					width: 44px;
					height: 44px;
					border-radius: 12px;
					display: flex;
					align-items: center;
					justify-content: center;
					transition: transform 0.2s ease;
				}
				.short-status-card:hover .card-icon-bubble {
					transform: scale(1.06);
				}
				.bubble-blue { background: #e0f2fe; color: #0284c7; }
				.bubble-amber { background: #fef3c7; color: #d97706; }
				.bubble-emerald { background: #d1fae5; color: #059669; }
				.bubble-purple { background: #ede9fe; color: #7c3aed; }

				.card-pill-badge {
					display: inline-flex;
					align-items: center;
					gap: 6px;
					padding: 4px 10px;
					border-radius: 9999px;
					font-size: 11px;
					font-weight: 700;
					text-transform: uppercase;
					letter-spacing: 0.5px;
					line-height: 1;
				}
				.pill-success {
					background: #ecfdf5;
					color: #047857;
					border: 1px solid #a7f3d0;
				}
				.pill-danger {
					background: #fef2f2;
					color: #b91c1c;
					border: 1px solid #fecaca;
				}
				.pill-dot {
					width: 7px;
					height: 7px;
					border-radius: 50%;
					display: inline-block;
				}
				.pill-success .pill-dot { background: #10b981; box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25); }
				.pill-danger .pill-dot { background: #ef4444; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.25); }

				.card-body-content h3 {
					margin: 0 0 6px 0;
					font-size: 16px;
					font-weight: 700;
					color: #0f172a;
					letter-spacing: -0.2px;
				}
				.card-body-content p {
					margin: 0;
					font-size: 13px;
					color: #64748b;
					line-height: 1.5;
				}
				.card-stat-number {
					font-size: 28px;
					font-weight: 800;
					color: #0f172a;
					margin: 4px 0 2px 0;
					line-height: 1;
				}
				.card-stat-label {
					font-size: 12px;
					font-weight: 500;
					color: #64748b;
				}

				.card-footer-action {
					margin-top: 18px;
					padding-top: 14px;
					border-top: 1px solid #f1f5f9;
					display: flex;
					align-items: center;
					gap: 8px;
					width: 100%;
					box-sizing: border-box !important;
				}
				.card-btn {
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: 6px !important;
					height: 38px !important;
					padding: 0 12px !important;
					border-radius: 8px !important;
					font-size: 12px !important;
					font-weight: 600 !important;
					cursor: pointer;
					text-decoration: none !important;
					white-space: nowrap !important;
					transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
					flex: 1 1 50% !important;
					min-width: 0 !important;
					box-sizing: border-box !important;
					text-align: center !important;
				}
				.card-btn svg {
					flex-shrink: 0;
					width: 14px;
					height: 14px;
				}
				.card-btn span {
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
				}
				.card-btn:active {
					transform: scale(0.97) !important;
				}
				.card-btn-default {
					background: #ffffff !important;
					border: 1px solid #cbd5e1 !important;
					color: #334155 !important;
					box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
				}
				.card-btn-default:hover {
					background: #f8fafc !important;
					border-color: #94a3b8 !important;
					color: #0f172a !important;
					transform: translateY(-1px);
					box-shadow: 0 3px 6px rgba(0, 0, 0, 0.06) !important;
				}
				.card-btn-primary {
					background: #0284c7 !important;
					border: 1px solid #0284c7 !important;
					color: #ffffff !important;
					box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25) !important;
				}
				.card-btn-primary:hover {
					background: #0369a1 !important;
					border-color: #0369a1 !important;
					transform: translateY(-1px);
					box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35) !important;
				}
				.card-btn-emerald {
					background: #059669 !important;
					border: 1px solid #059669 !important;
					color: #ffffff !important;
					box-shadow: 0 2px 6px rgba(5, 150, 105, 0.25) !important;
				}
				.card-btn-emerald:hover {
					background: #047857 !important;
					border-color: #047857 !important;
					transform: translateY(-1px);
					box-shadow: 0 4px 10px rgba(5, 150, 105, 0.35) !important;
				}

				.short-hub-section {
					background: #ffffff;
					border: 1px solid #e2e8f0;
					border-radius: 12px;
					padding: 24px 28px;
					box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
				}
				.short-hub-heading {
					font-size: 18px;
					font-weight: 700;
					color: #1e293b;
					margin: 0 0 16px 0;
					display: flex;
					align-items: center;
					gap: 8px;
				}
				.short-hub-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
					gap: 16px;
				}
				.short-hub-card {
					border: 1px solid #f1f5f9;
					background: #f8fafc;
					border-radius: 10px;
					padding: 18px;
					display: flex;
					gap: 14px;
					text-decoration: none;
					color: inherit;
					transition: all 0.2s ease;
				}
				.short-hub-card:hover {
					background: #ffffff;
					border-color: #cbd5e1;
					box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
					transform: translateY(-2px);
				}
				.short-hub-icon {
					font-size: 28px;
					line-height: 1;
				}
				.short-hub-card h4 {
					margin: 0 0 4px 0;
					font-size: 15px;
					font-weight: 700;
					color: #0f172a;
				}
				.short-hub-card p {
					margin: 0;
					font-size: 12px;
					color: #64748b;
					line-height: 1.4;
				}
			</style>

			<!-- Header Banner -->
			<div class="short-dash-header">
				<div class="short-dash-title-group" style="display:flex; align-items:center; gap:16px;">
					<?php 
					$brand_settings = get_option( 'short_brand_settings', array() );
					$hub_logo = ! empty( $brand_settings['custom_logo_url'] ) ? $brand_settings['custom_logo_url'] : ( function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : '' );
					$hub_brand_name = ! empty( $brand_settings['brand_name'] ) ? $brand_settings['brand_name'] : 'ShortTV';
					if ( $hub_logo ) : ?>
						<img src="<?php echo esc_url( $hub_logo ); ?>" alt="<?php echo esc_attr( $hub_brand_name ); ?>" style="height:44px; width:auto; max-width:140px; object-fit:contain; border-radius:8px; display:block;" />
					<?php endif; ?>
					<div>
						<h1 style="margin:0 0 4px 0; display:flex; align-items:center; gap:8px;">
							<span><?php echo esc_html( $hub_brand_name ); ?></span>
							<span class="short-badge-netflix"><?php _e( 'Control Hub', 'short-stream-core' ); ?></span>
						</h1>
						<p style="margin:0;"><?php _e( 'Overview of vertical short drama series, Cloudinary & Gumlet CDN streaming, and monetization.', 'short-stream-core' ); ?></p>
					</div>
				</div>
				<div class="short-dash-quick-links">
					<a href="<?php echo admin_url( 'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="short-header-btn short-header-btn-primary">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
						<span><?php _e( 'Add Drama', 'short-stream-core' ); ?></span>
					</a>
					<a href="<?php echo admin_url( 'edit.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="short-header-btn short-header-btn-secondary">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
						<span><?php _e( 'All Dramas', 'short-stream-core' ); ?></span>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-video-storage' ); ?>" class="short-header-btn short-header-btn-secondary">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path></svg>
						<span><?php _e( 'Video Storage & CDN', 'short-stream-core' ); ?></span>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings' ); ?>" class="short-header-btn short-header-btn-secondary">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
						<span><?php _e( 'Settings', 'short-stream-core' ); ?></span>
					</a>
				</div>
			</div>

			<!-- Status & Service Cards Grid -->
			<div class="short-cards-grid">
				<?php 
				$cpt_counts = wp_count_posts( \SHORT\Core\CPT\Video_CPT::POST_TYPE );
				$total_dramas_published = (int) ( $cpt_counts->publish ?? 0 );
				$total_dramas_draft     = (int) ( $cpt_counts->draft ?? 0 );
				$total_dramas_all       = $total_dramas_published + $total_dramas_draft;
				?>

				<!-- ShortTV Dramas Card -->
				<div class="short-status-card card-accent-emerald">
					<div>
						<div class="card-top-row">
							<div class="card-icon-bubble bubble-emerald">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
							</div>
							<span class="card-pill-badge pill-success"><span class="pill-dot"></span> <?php _e( 'Active', 'short-stream-core' ); ?></span>
						</div>
						<div class="card-body-content">
							<h3><?php _e( 'ShortTV Dramas & Series', 'short-stream-core' ); ?></h3>
							<div class="card-stat-number"><?php echo $total_dramas_all; ?></div>
							<div class="card-stat-label"><?php printf( esc_html__( '%d Published, %d Drafts • Cloudflare R2, Gumlet & Cloudinary', 'short-stream-core' ), $total_dramas_published, $total_dramas_draft ); ?></div>
						</div>
					</div>
					<div class="card-footer-action">
						<a href="<?php echo admin_url( 'edit.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="card-btn card-btn-default">
							<span><?php _e( 'Manage Dramas', 'short-stream-core' ); ?></span>
						</a>
						<a href="<?php echo admin_url( 'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="card-btn card-btn-emerald">
							<span>➕ <?php _e( 'Add New Drama', 'short-stream-core' ); ?></span>
						</a>
					</div>
				</div>

				<!-- Firebase Card -->
				<div class="short-status-card card-accent-amber">
					<div>
						<div class="card-top-row">
							<div class="card-icon-bubble bubble-amber">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z"/></svg>
							</div>
							<?php if ( ! empty( $firebase['api_key'] ) && ! empty( $firebase['project_id'] ) ) : ?>
								<span class="card-pill-badge pill-success"><span class="pill-dot"></span> <?php _e( 'Configured', 'short-stream-core' ); ?></span>
							<?php else : ?>
								<span class="card-pill-badge pill-danger"><span class="pill-dot"></span> <?php _e( 'Missing Key', 'short-stream-core' ); ?></span>
							<?php endif; ?>
						</div>
						<div class="card-body-content">
							<h3><?php _e( 'Firebase Authentication', 'short-stream-core' ); ?></h3>
							<p><?php _e( 'Real-time multi-profile cloud sync for My List, watch progress, and PIN locks.', 'short-stream-core' ); ?></p>
						</div>
					</div>
					<div class="card-footer-action">
						<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=firebase' ); ?>" class="card-btn card-btn-default">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
							<span><?php _e( 'Configure Firebase', 'short-stream-core' ); ?></span>
						</a>
					</div>
				</div>

			</div>

			<!-- Quick Management Hub -->
			<div class="short-hub-section">
				<div class="short-hub-heading">
					<span class="dashicons dashicons-admin-tools" style="font-size:22px; width:22px; height:22px; color:#2271b1;"></span>
					<span><?php _e( 'ShortTV Management & Quick Actions', 'short-stream-core' ); ?></span>
				</div>
				<div class="short-hub-grid">
					<a href="<?php echo admin_url( 'edit.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="short-hub-card">
						<div class="short-hub-icon">🎬</div>
						<div>
							<h4><?php _e( 'ShortTV Dramas & Episodes', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Manage all vertical short dramas, arrange episode sequences, and manage story arcs.', 'short-stream-core' ); ?></p>
						</div>
					</a>
					<a href="<?php echo admin_url( 'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="short-hub-card">
						<div class="short-hub-icon">➕</div>
						<div>
							<h4><?php _e( 'Add New Drama', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Create a new vertical short drama series, upload 9:16 poster, and configure video streams.', 'short-stream-core' ); ?></p>
						</div>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-video-storage' ); ?>" class="short-hub-card">
						<div class="short-hub-icon">☁️</div>
						<div>
							<h4><?php _e( 'Video Storage & CDN', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Configure dedicated Cloudflare R2, Gumlet DRM, and Cloudinary video storage and streaming credentials.', 'short-stream-core' ); ?></p>
						</div>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=notifications' ); ?>" class="short-hub-card">
						<div class="short-hub-icon">🔔</div>
						<div>
							<h4><?php _e( 'Notification Manager', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Broadcast urgent alerts, episode release announcements, and trending drama updates.', 'short-stream-core' ); ?></p>
						</div>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=branding' ); ?>" class="short-hub-card">
						<div class="short-hub-icon">🎨</div>
						<div>
							<h4><?php _e( 'Branding & Logo Upload', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Upload custom PNG, SVG, or JPG logo to customize the frontend header and mobile brand.', 'short-stream-core' ); ?></p>
						</div>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=player' ); ?>" class="short-hub-card">
						<div class="short-hub-icon">📱</div>
						<div>
							<h4><?php _e( 'Vertical Player & UI Controls', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Configure vertical 9:16 player controls, auto-play behaviors, and stream preferences.', 'short-stream-core' ); ?></p>
						</div>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=subscription' ); ?>" class="short-hub-card">
						<div class="short-hub-icon">🪙</div>
						<div>
							<h4><?php _e( 'Subscription & Coins Paywall', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Manage coin unlocking costs per episode, VIP membership plans, and checkout methods.', 'short-stream-core' ); ?></p>
						</div>
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=ads' ); ?>" class="short-hub-card">
						<div class="short-hub-icon">💵</div>
						<div>
							<h4><?php _e( 'Advertising & Monetization', 'short-stream-core' ); ?></h4>
							<p><?php _e( 'Configure rewarded video ad networks, frequency caps, and bonus coin rewards.', 'short-stream-core' ); ?></p>
						</div>
					</a>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($){
			$('#short-flush-cache-btn').on('click', function(){
				var btn = $(this).prop('disabled', true).html('<span class="dashicons dashicons-update" style="font-size:14px; vertical-align:middle;"></span> <span>Flushing...</span>');
				$.post(ajaxurl, { action: 'short_flush_cache', nonce: '<?php echo wp_create_nonce("short_admin_nonce"); ?>' }, function(res){
					btn.prop('disabled', false).html('<span class="dashicons dashicons-image-rotate" style="font-size:14px; width:14px; height:14px;"></span> <span>Flush Cache</span>');
					$('#flush-cache-result').html('<span style="color:#059669; font-size:11px; font-weight:bold;">&#10003; ' + (res.data ? res.data.message : 'Flushed') + '</span>');
					setTimeout(function(){ $('#flush-cache-result').empty(); }, 3500);
				});
			});
		});
		</script>
		<?php
	}
	public static function render_homepage_builder() {
		self::render_section_builder( 'home' );
	}

	public static function render_series_builder() {
		self::render_section_builder( 'series' );
	}

	public static function render_movies_builder() {
		self::render_section_builder( 'movies' );
	}

	public static function render_section_builder( $default_target = 'home' ) {
		$target = isset( $_GET['target'] ) ? sanitize_key( $_GET['target'] ) : $default_target;
		$valid_targets = array( 'home', 'categories', 'new-popular', 'leaderboard', 'mobile_leaderboard', 'mobile_home', 'mobile_discovery', 'mobile_popular', 'mobile_account', 'bottom_nav', 'desktop_nav', 'series', 'movies', 'anime', 'kids', 'my-list', 'mobile_header' );
		$tabs_config = self::get_section_tabs_config();
		if ( ! empty( $tabs_config['desktop'] ) && is_array( $tabs_config['desktop'] ) ) {
			foreach ( $tabs_config['desktop'] as $dt ) {
				if ( ! empty( $dt['target'] ) ) $valid_targets[] = sanitize_key( $dt['target'] );
			}
		}
		if ( ! empty( $tabs_config['mobile_header'] ) && is_array( $tabs_config['mobile_header'] ) ) {
			foreach ( $tabs_config['mobile_header'] as $mht ) {
				if ( ! empty( $mht['target'] ) ) $valid_targets[] = sanitize_key( $mht['target'] );
			}
		}
		if ( ! empty( $tabs_config['mobile'] ) && is_array( $tabs_config['mobile'] ) ) {
			foreach ( $tabs_config['mobile'] as $mt ) {
				if ( ! empty( $mt['target'] ) ) $valid_targets[] = sanitize_key( $mt['target'] );
			}
		}
		if ( ! in_array( $target, $valid_targets, true ) ) {
			if ( isset( $_GET['page'] ) && 'short-categories' === $_GET['page'] ) {
				$target = 'categories';
			} elseif ( isset( $_GET['page'] ) && 'short-new-popular' === $_GET['page'] ) {
				$target = 'new-popular';
			} elseif ( isset( $_GET['page'] ) && 'short-leaderboard' === $_GET['page'] ) {
				$target = 'leaderboard';
			} elseif ( isset( $_GET['page'] ) && 'short-my-list' === $_GET['page'] ) {
				$target = 'my-list';
			} else {
				$target = 'home';
			}
		}

		if ( 'mobile_header' === $target ) {
			self::render_mobile_header_customizer();
			return;
		}

		if ( 'categories' === $target || 'mobile_discovery' === $target ) {
			// ── Genre Filter Tabs Manager ─────────────────────────────────────
			$genre_tabs_config = get_option( 'short_genre_filter_tabs', array() );
			$tabs_auto_mode    = ! empty( $genre_tabs_config['auto'] );
			$saved_tabs        = ! empty( $genre_tabs_config['tabs'] ) ? $genre_tabs_config['tabs'] : array();

			// Default tabs when nothing saved yet
			if ( empty( $saved_tabs ) ) {
				$saved_tabs = array(
					array( 'emoji' => '💕', 'label' => 'Romance',         'slug' => 'romance',  'enabled' => 1 ),
					array( 'emoji' => '✨', 'label' => 'Fantasy',         'slug' => 'fantasy',  'enabled' => 1 ),
					array( 'emoji' => '🧛', 'label' => 'Vampire',         'slug' => 'vampire',  'enabled' => 1 ),
					array( 'emoji' => '🐺', 'label' => 'Werewolf',        'slug' => 'werewolf', 'enabled' => 1 ),
					array( 'emoji' => '👑', 'label' => 'CEO / Billionaire','slug' => 'ceo',      'enabled' => 1 ),
					array( 'emoji' => '⚡', 'label' => 'Revenge',         'slug' => 'revenge',  'enabled' => 1 ),
					array( 'emoji' => '🏙️','label' => 'Urban',           'slug' => 'urban',    'enabled' => 1 ),
					array( 'emoji' => '🌆', 'label' => 'Modern',          'slug' => 'modern',   'enabled' => 1 ),
					array( 'emoji' => '🔍', 'label' => 'Suspense',        'slug' => 'suspense', 'enabled' => 1 ),
					array( 'emoji' => '💥', 'label' => 'Action',          'slug' => 'action',   'enabled' => 1 ),
					array( 'emoji' => '🎭', 'label' => 'Drama',           'slug' => 'drama',    'enabled' => 1 ),
				);
			}
			$nonce = wp_create_nonce( 'short_admin_nonce' );
			?>
			<div class="wrap short-builder-wrap">
				<style>
					.short-action-btn { display:inline-flex !important; align-items:center !important; justify-content:center !important; gap:6px !important; height:34px !important; line-height:1 !important; padding:0 14px !important; font-size:13px !important; font-weight:500 !important; cursor:pointer; }
					.short-action-btn .dashicons { font-size:16px !important; width:16px !important; height:16px !important; line-height:16px !important; margin:0 !important; display:inline-block !important; }
					.short-action-btn-primary { background:#2271b1 !important; color:#fff !important; border-color:#2271b1 !important; }
					.short-action-btn-primary:hover { background:#135e96 !important; color:#fff !important; }
					.short-btn-danger { color:#b32d2e !important; border-color:#d63638 !important; }
					.short-btn-danger:hover { background:#fcf0f1 !important; color:#a00 !important; }
					.nav-tab-wrapper .nav-tab { display:inline-flex !important; align-items:center !important; gap:6px !important; padding:7px 16px !important; font-size:13px !important; font-weight:600 !important; border-radius:6px !important; border:1px solid #cbd5e1 !important; background:#ffffff !important; color:#475569 !important; transition:all .15s ease !important; margin:0 !important; text-decoration:none !important; }
					.nav-tab-wrapper .nav-tab:hover { background:#f8fafc !important; color:#0f172a !important; border-color:#94a3b8 !important; }
					.nav-tab-wrapper .nav-tab .dashicons, .nav-tab-wrapper .nav-tab svg { font-size:16px !important; width:16px !important; height:16px !important; line-height:16px !important; margin:0 !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; color:inherit !important; }
					.nav-tab-wrapper .nav-tab.short-nav-tab-active, .nav-tab-wrapper .nav-tab.nav-tab-active { background:#2563eb !important; border-color:#2563eb !important; font-weight:700 !important; color:#ffffff !important; box-shadow:0 2px 5px rgba(37,99,235,0.28) !important; }
					.nav-tab-wrapper .nav-tab.short-nav-tab-active .dashicons, .nav-tab-wrapper .nav-tab.nav-tab-active .dashicons { color:#ffffff !important; }
					#genre-tabs-table td { vertical-align:middle !important; padding:8px 10px !important; }
					.genre-tab-emoji-input { width:52px !important; text-align:center !important; font-size:18px !important; }
					.genre-tab-label-input { width:160px !important; }
					.genre-tab-slug-input  { width:130px !important; font-family:monospace; font-size:12px; }
					.auto-mode-notice { background:#e8f5e9; border-left:4px solid #4caf50; padding:12px 16px; margin:16px 0; border-radius:4px; }
					.short-btn-icon-action { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border-radius:5px; border:1px solid #cbd5e1; background:#fff; color:#64748b; cursor:pointer; transition:all .15s; }
					.short-btn-icon-action:hover { background:#f8fafc; border-color:#94a3b8; }
					.short-btn-icon-action.btn-del-row:hover { background:#fef2f2; border-color:#fca5a5; color:#ef4444; }
					.short-btn-icon-action.btn-move-up:hover,.short-btn-icon-action.btn-move-down:hover { border-color:#93c5fd; color:#2563eb; background:#eff6ff; }
					.genre-row-actions { display:inline-flex; align-items:center; gap:5px; }
				</style>

				<h1>
					<span class="dashicons dashicons-category" style="font-size:30px;width:30px;height:30px;margin-right:8px;vertical-align:middle;"></span>
					<?php _e( 'Genre Filter Tabs Manager', 'short-stream-core' ); ?>
				</h1>
				<p class="description"><?php _e( 'Manage the genre filter pills displayed on the <strong>/genre/</strong> browse page. Set to <strong>Auto</strong> to show only genres that have actual posts, or <strong>Manual</strong> to control exactly which tabs appear, in what order, with custom names and emojis.', 'short-stream-core' ); ?></p>

				<?php 
				self::render_section_divisions_bar( $target ); 
				?>

				<!-- Auto / Manual Toggle -->
				<div style="background:#f8f9fa; border:1px solid #e2e8f0; border-radius:8px; padding:18px 20px; margin-bottom:16px; display:flex; align-items:flex-start; gap:18px; flex-wrap:wrap;">
					<div style="flex:1; min-width:260px;">
						<strong style="font-size:14px;"><?php _e('Tab Display Mode', 'short-stream-core'); ?></strong>
						<p style="margin:4px 0 0; color:#64748b; font-size:13px;"><?php _e('Auto: only genres with posts appear. Manual: you control the full list.', 'short-stream-core'); ?></p>
					</div>
					<div style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
						<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
							<input type="radio" name="genre_tab_mode" value="auto" <?php checked($tabs_auto_mode, true); ?> id="mode-auto" style="margin:0;">
							<span>🤖 <?php _e('Auto (Recommended)', 'short-stream-core'); ?></span>
						</label>
						<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;">
							<input type="radio" name="genre_tab_mode" value="manual" <?php checked($tabs_auto_mode, false); ?> id="mode-manual" style="margin:0;">
							<span>✏️ <?php _e('Manual', 'short-stream-core'); ?></span>
						</label>
					</div>
				</div>

				<!-- Auto Sort Options (only visible in Auto mode) -->
				<?php
				$auto_sort = $genre_tabs_config['auto_sort'] ?? 'count_desc';
				$sort_labels = array(
					'count_desc' => __('Most Posts First (↓)', 'short-stream-core'),
					'count_asc'  => __('Fewest Posts First (↑)', 'short-stream-core'),
					'alpha_asc'  => __('Alphabetical A → Z', 'short-stream-core'),
					'alpha_desc' => __('Alphabetical Z → A', 'short-stream-core'),
				);
				?>
				<div id="auto-sort-options" style="background:#f0f7ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px 18px; margin-bottom:16px; display:<?php echo $tabs_auto_mode ? 'flex' : 'none'; ?>; align-items:center; gap:16px; flex-wrap:wrap;">
					<label style="font-weight:600; font-size:13px; white-space:nowrap;">
						📊 <?php _e('Sort Auto Tabs By:', 'short-stream-core'); ?>
					</label>
					<select id="auto-sort-select" style="min-width:220px; height:34px; border-radius:5px; border:1px solid #93c5fd; padding:0 10px; font-size:13px;">
						<?php foreach ( $sort_labels as $skey => $slabel ) : ?>
							<option value="<?php echo esc_attr($skey); ?>" <?php selected($auto_sort, $skey); ?>><?php echo esc_html($slabel); ?></option>
						<?php endforeach; ?>
					</select>
					<span style="font-size:12px; color:#3b82f6;">
						<?php _e('Controls the order of tabs on the /genre/ page when Auto mode is on.', 'short-stream-core'); ?>
					</span>
				</div>

				<div id="auto-mode-notice" class="auto-mode-notice" style="<?php echo $tabs_auto_mode ? '' : 'display:none;'; ?>">
					🤖 <strong><?php _e('Auto Mode is ON.', 'short-stream-core'); ?></strong> <?php _e('The genre page automatically shows only genres that have at least 1 drama post, sorted by your chosen order above.', 'short-stream-core'); ?>
				</div>

				<!-- Tabs Table -->
				<div id="manual-tabs-section" style="<?php echo $tabs_auto_mode ? 'opacity:0.45; pointer-events:none;' : ''; ?>">
					<div style="margin-bottom:14px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
						<button type="button" class="button short-action-btn short-action-btn-primary" id="genre-add-tab-btn">
							<span class="dashicons dashicons-plus-alt2"></span>
							<span><?php _e('Add New Filter Tab', 'short-stream-core'); ?></span>
						</button>
						<span style="color:#64748b;font-size:13px;"><?php _e('Drag or use arrows to reorder. Edit emoji, name, and slug inline.', 'short-stream-core'); ?></span>
					</div>

					<table class="widefat fixed striped" id="genre-tabs-table">
						<thead>
							<tr>
								<th style="width:42px;text-align:center;">#</th>
								<th style="width:60px;text-align:center;"><?php _e('Emoji','short-stream-core'); ?></th>
								<th style="width:180px;"><?php _e('Tab Label','short-stream-core'); ?></th>
								<th style="width:150px;"><?php _e('Genre Slug','short-stream-core'); ?> <span style="font-weight:400;font-size:11px;color:#94a3b8;">(URL key)</span></th>
								<th style="width:75px;text-align:center;"><?php _e('Visible','short-stream-core'); ?></th>
								<th style="width:120px;text-align:center;"><?php _e('Actions','short-stream-core'); ?></th>
							</tr>
						</thead>
						<tbody id="genre-tabs-tbody">
							<?php foreach ( $saved_tabs as $ti => $tab ) : ?>
							<tr class="genre-tab-row" data-idx="<?php echo $ti; ?>">
								<td style="text-align:center;"><strong class="genre-row-num"><?php echo $ti + 1; ?></strong></td>
								<td style="text-align:center;"><input type="text" class="genre-tab-emoji-input" value="<?php echo esc_attr($tab['emoji'] ?? '🎬'); ?>" maxlength="4" placeholder="🎬" /></td>
								<td><input type="text" class="genre-tab-label-input widefat" value="<?php echo esc_attr($tab['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('Tab name e.g. Romance', 'short-stream-core'); ?>" /></td>
								<td><input type="text" class="genre-tab-slug-input widefat" value="<?php echo esc_attr($tab['slug'] ?? ''); ?>" placeholder="romance" /></td>
								<td style="text-align:center;"><input type="checkbox" class="genre-tab-enabled" <?php checked(!empty($tab['enabled'])); ?> /></td>
								<td style="text-align:center;">
									<div class="genre-row-actions">
										<button type="button" class="short-btn-icon-action btn-move-up" title="<?php esc_attr_e('Move Up','short-stream-core'); ?>"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg></button>
										<button type="button" class="short-btn-icon-action btn-move-down" title="<?php esc_attr_e('Move Down','short-stream-core'); ?>"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button>
										<button type="button" class="short-btn-icon-action btn-del-row" title="<?php esc_attr_e('Delete','short-stream-core'); ?>"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
									</div>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div><!-- /manual-tabs-section -->

				<!-- Save Bar -->
				<div style="margin-top:22px; display:flex; align-items:center; gap:14px; padding-top:18px; border-top:1px solid #e2e8f0;">
					<button type="button" class="button button-primary button-large" id="genre-tabs-save-btn" style="font-weight:600;">
						💾 <?php _e('Save Genre Filter Tabs', 'short-stream-core'); ?>
					</button>
					<span id="genre-tabs-save-status" style="font-weight:bold;"></span>
				</div>
			</div><!-- /wrap -->

			<script>
			jQuery(document).ready(function($){
				var nonce = '<?php echo $nonce; ?>';

				// Auto/Manual mode toggle
				$('input[name="genre_tab_mode"]').on('change', function(){
					var isAuto = $(this).val() === 'auto';
					$('#auto-mode-notice').toggle(isAuto);
					$('#auto-sort-options').toggle(isAuto);
					$('#manual-tabs-section').css({ opacity: isAuto ? 0.45 : 1, 'pointer-events': isAuto ? 'none' : '' });
				});

				// Reindex rows
				function reindexRows(){
					$('#genre-tabs-tbody tr.genre-tab-row').each(function(i){
						$(this).attr('data-idx', i);
						$(this).find('.genre-row-num').text(i + 1);
					});
				}

				// Move Up
				$(document).on('click', '.btn-move-up', function(){
					var row = $(this).closest('tr.genre-tab-row');
					var prev = row.prev('tr.genre-tab-row');
					if(prev.length){ row.insertBefore(prev); reindexRows(); }
				});

				// Move Down
				$(document).on('click', '.btn-move-down', function(){
					var row = $(this).closest('tr.genre-tab-row');
					var next = row.next('tr.genre-tab-row');
					if(next.length){ row.insertAfter(next); reindexRows(); }
				});

				// Delete row
				$(document).on('click', '.btn-del-row', function(){
					$(this).closest('tr.genre-tab-row').remove();
					reindexRows();
				});

				// Add new tab
				$('#genre-add-tab-btn').on('click', function(){
					var idx = $('#genre-tabs-tbody tr.genre-tab-row').length;
					var row = '<tr class="genre-tab-row" data-idx="'+idx+'">' +
						'<td style="text-align:center;"><strong class="genre-row-num">'+(idx+1)+'</strong></td>' +
						'<td style="text-align:center;"><input type="text" class="genre-tab-emoji-input" value="🎬" maxlength="4" /></td>' +
						'<td><input type="text" class="genre-tab-label-input widefat" value="" placeholder="Tab name" /></td>' +
						'<td><input type="text" class="genre-tab-slug-input widefat" value="" placeholder="genre-slug" /></td>' +
						'<td style="text-align:center;"><input type="checkbox" class="genre-tab-enabled" checked /></td>' +
						'<td style="text-align:center;"><div class="genre-row-actions">' +
						'<button type="button" class="short-btn-icon-action btn-move-up" title="Move Up"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg></button>' +
						'<button type="button" class="short-btn-icon-action btn-move-down" title="Move Down"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></button>' +
						'<button type="button" class="short-btn-icon-action btn-del-row" title="Delete"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>' +
						'</div></td></tr>';
					$('#genre-tabs-tbody').append(row);
					reindexRows();
					$('#genre-tabs-tbody tr.genre-tab-row').last().find('.genre-tab-label-input').focus();
				});

				// Save
				$('#genre-tabs-save-btn').on('click', function(){
					var mode     = $('input[name="genre_tab_mode"]:checked').val();
					var autoSort = $('#auto-sort-select').val();
					var tabs = [];
					$('#genre-tabs-tbody tr.genre-tab-row').each(function(){
						var emoji   = $(this).find('.genre-tab-emoji-input').val().trim() || '🎬';
						var label   = $(this).find('.genre-tab-label-input').val().trim();
						var slug    = $(this).find('.genre-tab-slug-input').val().trim().toLowerCase().replace(/\s+/g,'-');
						var enabled = $(this).find('.genre-tab-enabled').is(':checked') ? 1 : 0;
						if(label && slug){
							tabs.push({ emoji:emoji, label:label, slug:slug, enabled:enabled });
						}
					});
					$('#genre-tabs-save-status').html('<span style="color:#666;">Saving...</span>');
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: { action:'short_save_genre_tabs', nonce:nonce, mode:mode, auto_sort:autoSort, tabs:JSON.stringify(tabs) },
						success: function(res){
							if(res.success){
								$('#genre-tabs-save-status').html('<span style="color:green;">✔ Saved successfully!</span>');
								setTimeout(function(){ $('#genre-tabs-save-status').empty(); }, 3500);
							} else {
								$('#genre-tabs-save-status').html('<span style="color:red;">✘ Error: '+(res.data||'Unknown error')+'</span>');
							}
						},
						error: function(){
							$('#genre-tabs-save-status').html('<span style="color:red;">✘ Network error</span>');
						}
					});
				});
			});
			</script>
			<?php
			return; // Don't render the generic section builder for categories
			// ── end Genre Filter Tabs Manager ─────────────────────────────────
		}

		if ( 'leaderboard' === $target || 'mobile_leaderboard' === $target ) {
			$page_title  = __( 'Drama Leaderboard & Power Rankings Manager', 'short-stream-core' );
			$page_icon   = 'dashicons-awards';
			$description = __( 'The Leaderboard view is rendered as a dedicated standalone page (/leaderboard/) featuring the Top 3 Champions Podium Spotlight, Power Rankings Board (#4–#30), and live sort filters.', 'short-stream-core' );
			?>
			<div class="wrap short-builder-wrap">
				<h1>
					<span class="dashicons dashicons-awards" style="font-size:30px; width:30px; height:30px; margin-right:8px; vertical-align:middle; color:#f59e0b;"></span>
					<?php echo esc_html( $page_title ); ?>
				</h1>
				<p class="description"><?php echo esc_html( $description ); ?></p>

				<?php self::render_section_divisions_bar( $target ); ?>

				<div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:12px; padding:28px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
					<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid #f1f5f9;">
						<div style="display:flex; align-items:center; gap:14px;">
							<div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, #ffd700 0%, #f59e0b 100%); display:flex; align-items:center; justify-content:center; font-size:24px; box-shadow:0 4px 14px rgba(245,158,11,0.35);">
								🏆
							</div>
							<div>
								<h2 style="font-size:18px; font-weight:800; margin:0 0 4px 0; color:#0f172a;">Dedicated Leaderboard View Active</h2>
								<p style="margin:0; font-size:13px; color:#64748b;">No extra card rows or horizontal carousels are attached. The pure Leaderboard design is loaded directly.</p>
							</div>
						</div>
						<div style="display:flex; align-items:center; gap:10px;">
							<a href="<?php echo esc_url( home_url( '/leaderboard/' ) ); ?>" target="_blank" class="button button-primary button-large" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
								<span class="dashicons dashicons-external" style="font-size:16px; width:16px; height:16px; line-height:1;"></span>
								<span><?php _e( 'Open Live Leaderboard (/leaderboard/)', 'short-stream-core' ); ?></span>
							</a>
						</div>
					</div>

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
							<div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
								<span>🥇 Top 3 Champions Podium</span>
							</div>
							<p style="margin:0; font-size:12.5px; color:#64748b; line-height:1.5;">Gold (#1), Silver (#2), and Bronze (#3) podium cards featuring dynamic glow borders, posters, ratings, and instant play buttons.</p>
						</div>

						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
							<div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
								<span>📊 Power Rankings Board (#4 to #30)</span>
							</div>
							<p style="margin:0; font-size:12.5px; color:#64748b; line-height:1.5;">Clean numbered rank cards showing drama thumbnails, episodes count, real-time views, genre tags, and quick-action Play buttons.</p>
						</div>

						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
							<div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
								<span>⚡ Instant Filter Pills</span>
							</div>
							<p style="margin:0; font-size:12.5px; color:#64748b; line-height:1.5;">Visitors can toggle between <strong>🏆 All Champions</strong>, <strong>🔥 Most Viewed</strong>, <strong>⭐ Highest Rated</strong>, <strong>📺 Binge Leaders</strong>, and <strong>👑 VIP Exclusives</strong>.</p>
						</div>
					</div>
				</div>
			</div>
			<?php
			return; // Don't render the generic section row table for leaderboard
		} elseif ( 'my-list' === $target || 'mylist' === $target ) {
			$page_title  = __( 'User Bookmarks & My List Manager', 'short-stream-core' );
			$page_icon   = 'dashicons-list-view';
			$description = __( 'The My List view is rendered as a dedicated standalone page (/my-list/) powered by real-time client-side bookmarks and Firebase cloud sync.', 'short-stream-core' );
			?>
			<div class="wrap short-builder-wrap">
				<h1>
					<span class="dashicons dashicons-list-view" style="font-size:30px; width:30px; height:30px; margin-right:8px; vertical-align:middle; color:#3b82f6;"></span>
					<?php echo esc_html( $page_title ); ?>
				</h1>
				<p class="description"><?php echo esc_html( $description ); ?></p>

				<?php self::render_section_divisions_bar( $target ); ?>

				<div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:12px; padding:28px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
					<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px; margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid #f1f5f9;">
						<div style="display:flex; align-items:center; gap:14px;">
							<div style="width:48px; height:48px; border-radius:12px; background:linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); display:flex; align-items:center; justify-content:center; font-size:24px; box-shadow:0 4px 14px rgba(59,130,246,0.35);">
								🔖
							</div>
							<div>
								<h2 style="font-size:18px; font-weight:800; margin:0 0 4px 0; color:#0f172a;"><?php _e( 'Dedicated My List Page Active', 'short-stream-core' ); ?></h2>
								<p style="margin:0; font-size:13px; color:#64748b;"><?php _e( 'User bookmarks are stored per user profile and synced across devices in real time via Firebase and LocalStorage.', 'short-stream-core' ); ?></p>
							</div>
						</div>
						<div style="display:flex; align-items:center; gap:10px;">
							<a href="<?php echo esc_url( home_url( '/my-list/' ) ); ?>" target="_blank" class="button button-primary button-large" style="display:inline-flex; align-items:center; gap:6px; font-weight:700;">
								<span class="dashicons dashicons-external" style="font-size:16px; width:16px; height:16px; line-height:1;"></span>
								<span><?php _e( 'Open Live My List (/my-list/)', 'short-stream-core' ); ?></span>
							</a>
						</div>
					</div>

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px;">
						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
							<div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
								<span>☁️ Multi-Profile Cloud Sync</span>
							</div>
							<p style="margin:0; font-size:12.5px; color:#64748b; line-height:1.5;">When Firebase is active, user bookmarks automatically sync across smartphones, tablets, and desktop computers.</p>
						</div>

						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
							<div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
								<span>⚡ Instant Add / Remove</span>
							</div>
							<p style="margin:0; font-size:12.5px; color:#64748b; line-height:1.5;">Visitors can bookmark any drama series with 1 click from the hero billboard, carousel cards, or video player controls.</p>
						</div>

						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px;">
							<div style="font-weight:700; font-size:14px; color:#0f172a; margin-bottom:8px; display:flex; align-items:center; gap:6px;">
								<span>📱 Full Mobile Top Header Support</span>
							</div>
							<p style="margin:0; font-size:12.5px; color:#64748b; line-height:1.5;">Visitors on mobile devices can now access My List directly from the top horizontal navigation track.</p>
						</div>
					</div>
				</div>
			</div>
			<?php
			return; // Don't render the generic section row table for my-list
		} elseif ( 'new-popular' === $target ) {
			$opt_name     = 'short_new_popular_sections';
			$page_title   = __( 'New & Popular Section Builder', 'short-stream-core' );
			$page_icon    = 'dashicons-star-filled';
			$description  = __( 'Enable, disable, reorder, or customize dynamic trending, upcoming, and popular rows on the New & Popular page (/new-popular/).', 'short-stream-core' );
			$save_button  = __( 'Save New & Popular Configuration', 'short-stream-core' );
			$target_label = __( 'New & Popular', 'short-stream-core' );
		} elseif ( 'mobile_home' === $target ) {
			$opt_name     = 'short_mobile_home_sections';
			$page_title   = __( 'Mobile Home Section Builder', 'short-stream-core' );
			$page_icon    = 'dashicons-admin-home';
			$description  = __( 'Configure the drama feeds and carousel rows displayed on the mobile homepage view.', 'short-stream-core' );
			$save_button  = __( 'Save Mobile Home Configuration', 'short-stream-core' );
			$target_label = __( 'Mobile Home', 'short-stream-core' );
		} elseif ( 'mobile_discovery' === $target ) {
			$opt_name     = 'short_mobile_discovery_sections';
			$page_title   = __( 'Mobile Discovery (Short TV) Feed Builder', 'short-stream-core' );
			$page_icon    = 'dashicons-video-alt3';
			$description  = __( 'Configure the drama recommendation feeds and autoplay rows for the mobile Discovery & Short TV swipe player.', 'short-stream-core' );
			$save_button  = __( 'Save Mobile Discovery Configuration', 'short-stream-core' );
			$target_label = __( 'Mobile Discovery', 'short-stream-core' );
		} elseif ( 'mobile_popular' === $target ) {
			$opt_name     = 'short_mobile_popular_sections';
			$page_title   = __( 'Mobile Popular Section Builder', 'short-stream-core' );
			$page_icon    = 'dashicons-flame';
			$description  = __( 'Configure trending, popular, and top-ranked drama rows for the mobile Popular tab.', 'short-stream-core' );
			$save_button  = __( 'Save Mobile Popular Configuration', 'short-stream-core' );
			$target_label = __( 'Mobile Popular', 'short-stream-core' );
		} elseif ( 'mobile_account' === $target || 'bottom_nav' === $target ) {
			$opt_name     = 'short_bottom_nav_sections';
			$page_title   = __( 'Mobile Bottom Navigation & Account Builder', 'short-stream-core' );
			$page_icon    = 'dashicons-smartphone';
			$description  = __( 'Configure the bottom navigation bar and account shortcuts displayed on mobile screens. Default items: Home, Discovery, Popular, Account.', 'short-stream-core' );
			$save_button  = __( 'Save Mobile Navigation', 'short-stream-core' );
			$target_label = __( 'Mobile Nav', 'short-stream-core' );
		} elseif ( 'desktop_nav' === $target ) {
			$opt_name     = 'short_header_nav_items';
			$page_title   = __( 'Desktop Header Navigation Builder', 'short-stream-core' );
			$page_icon    = 'dashicons-desktop';
			$description  = __( 'Configure the top navigation menu displayed on desktop screens. Add pages like Home, Categories, New & Popular, My List, etc.', 'short-stream-core' );
			$save_button  = __( 'Save Desktop Navigation', 'short-stream-core' );
			$target_label = __( 'Desktop Nav', 'short-stream-core' );
		} else {
			$opt_name     = 'short_homepage_sections';
			$page_title   = __( 'Homepage Section Builder', 'short-stream-core' );
			$page_icon    = 'dashicons-admin-home';
			$description  = __( 'Enable, disable, reorder, or customize dynamic Short-style sections displayed on your streaming homepage (/).', 'short-stream-core' );
			$save_button  = __( 'Save Homepage Configuration', 'short-stream-core' );
			$target_label = __( 'Homepage', 'short-stream-core' );
		}

		$sections = get_option( $opt_name, array() );
		if ( empty( $sections ) ) {
			$sections = self::get_default_sections( $target );
		}
		// Strip any legacy 'my_list' rows so they never show in the homepage builder
		if ( is_array( $sections ) && 'home' === $target ) {
			$sections = array_values( array_filter( $sections, function( $s ) {
				$t = strtolower( trim( $s['type'] ?? '' ) );
				$id = strtolower( trim( $s['id'] ?? '' ) );
				$title = strtolower( trim( $s['title'] ?? '' ) );
				return ( $t !== 'my_list' && $id !== 'my_list' && $title !== 'my list' );
			} ) );
		}
		$presets = self::get_presets();
		$presets_map = array();
		foreach ( $presets as $p ) {
			$presets_map[ $p['id'] ] = array(
				'name'     => $p['name'],
				'endpoint' => $p['endpoint'],
			);
		}
		?>
		<div class="wrap short-builder-wrap">
			<style>
				.short-action-btn {
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: 6px !important;
					height: 34px !important;
					line-height: 1 !important;
					padding: 0 14px !important;
					font-size: 13px !important;
					font-weight: 500 !important;
					cursor: pointer;
				}
				.short-action-btn .dashicons {
					font-size: 16px !important;
					width: 16px !important;
					height: 16px !important;
					line-height: 16px !important;
					margin: 0 !important;
					display: inline-block !important;
				}
				.short-btn-danger {
					color: #b32d2e !important;
					border-color: #d63638 !important;
				}
				.short-btn-danger:hover {
					background: #fcf0f1 !important;
					color: #a00 !important;
				}
				.short-action-btn-primary {
					background: #2271b1 !important;
					color: #fff !important;
					border-color: #2271b1 !important;
				}
				.short-action-btn-primary:hover {
					background: #135e96 !important;
					color: #fff !important;
				}
				#short-sections-table td {
					vertical-align: middle !important;
				}
				.nav-tab-wrapper .nav-tab {
					display: inline-flex !important;
					align-items: center !important;
					gap: 6px !important;
					padding: 7px 16px !important;
					font-size: 13px !important;
					font-weight: 600 !important;
					border-radius: 6px !important;
					border: 1px solid #cbd5e1 !important;
					background: #ffffff !important;
					color: #475569 !important;
					transition: all .15s ease !important;
					margin: 0 !important;
					text-decoration: none !important;
				}
				.nav-tab-wrapper .nav-tab:hover {
					background: #f8fafc !important;
					color: #0f172a !important;
					border-color: #94a3b8 !important;
				}
				.nav-tab-wrapper .nav-tab .dashicons,
				.nav-tab-wrapper .nav-tab svg {
					font-size: 16px !important;
					width: 16px !important;
					height: 16px !important;
					line-height: 16px !important;
					margin: 0 !important;
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					color: inherit !important;
				}
				.nav-tab-wrapper .nav-tab.short-nav-tab-active,
				.nav-tab-wrapper .nav-tab.nav-tab-active {
					background: #2563eb !important;
					border-color: #2563eb !important;
					font-weight: 700 !important;
					color: #ffffff !important;
					box-shadow: 0 2px 5px rgba(37,99,235,0.28) !important;
				}
				.nav-tab-wrapper .nav-tab.short-nav-tab-active .dashicons,
				.nav-tab-wrapper .nav-tab.nav-tab-active .dashicons {
					color: #ffffff !important;
				}
			</style>

			<h1>
				<span class="dashicons <?php echo esc_attr( $page_icon ); ?>" style="font-size:30px; width:30px; height:30px; margin-right:8px; vertical-align:middle;"></span>
				<?php echo esc_html( $page_title ); ?>
			</h1>
			<p class="description"><?php echo esc_html( $description ); ?></p>

			<?php 
			self::render_section_divisions_bar( $target ); 
			?>

			<form id="short-sections-form">
				<input type="hidden" name="target" id="short_section_target" value="<?php echo esc_attr( $target ); ?>" />

				<div class="short-builder-toolbar">
					<button type="button" class="short-modern-btn short-modern-btn-primary" id="short-add-row-btn">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
						<span><?php _e( 'Add new row', 'short-stream-core' ); ?></span>
					</button>
					<button type="button" class="short-modern-btn short-modern-btn-danger" id="short-reset-sections-btn">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
						<span><?php _e( 'Reset to defaults', 'short-stream-core' ); ?></span>
					</button>
					<?php if ( 'bottom_nav' !== $target ) : ?>
					<a href="<?php echo admin_url('admin.php?page=short-presets'); ?>" class="short-modern-btn short-modern-btn-preset">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>
						<span><?php _e( 'Manage endpoint presets', 'short-stream-core' ); ?></span>
					</a>
					<?php endif; ?>
					<div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
						<span class="save-sections-status" style="font-weight:bold;"></span>
						<button type="submit" class="short-modern-btn short-modern-btn-primary">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
							<span><?php _e( 'Save configuration', 'short-stream-core' ); ?></span>
						</button>
					</div>
				</div>

				<style>
					/* Modern Row Builder Toolbar & Table Styles */
					.short-builder-toolbar {
						margin: 18px 0 16px 0;
						display: flex;
						gap: 10px;
						align-items: center;
						flex-wrap: wrap;
					}
					.short-modern-btn {
						display: inline-flex !important;
						align-items: center !important;
						justify-content: center !important;
						gap: 7px !important;
						height: 38px !important;
						padding: 0 16px !important;
						border-radius: 8px !important;
						font-size: 13px !important;
						font-weight: 600 !important;
						cursor: pointer !important;
						text-decoration: none !important;
						box-sizing: border-box !important;
						white-space: nowrap !important;
						transition: all 0.16s cubic-bezier(0.16, 1, 0.3, 1) !important;
						line-height: 1 !important;
					}
					.short-modern-btn svg {
						flex-shrink: 0;
					}
					.short-modern-btn-primary {
						background: #0284c7 !important;
						border: 1px solid #0284c7 !important;
						color: #ffffff !important;
						font-weight: 700 !important;
						box-shadow: 0 1px 2px rgba(2, 132, 199, 0.18) !important;
					}
					.short-modern-btn-primary:hover {
						background: #0369a1 !important;
						border-color: #0369a1 !important;
						color: #ffffff !important;
						transform: translateY(-1px);
						box-shadow: 0 3px 8px rgba(2, 132, 199, 0.28) !important;
					}
					.short-modern-btn-danger {
						background: #ffffff !important;
						border: 1px solid #fecaca !important;
						color: #dc2626 !important;
					}
					.short-modern-btn-danger:hover {
						background: #fef2f2 !important;
						border-color: #fca5a5 !important;
						color: #b91c1c !important;
						transform: translateY(-1px);
					}
					.short-modern-btn-preset {
						background: #ffffff !important;
						border: 1px solid #bae6fd !important;
						color: #0284c7 !important;
					}
					.short-modern-btn-preset:hover {
						background: #f0f9ff !important;
						border-color: #7dd3fc !important;
						color: #0369a1 !important;
						transform: translateY(-1px);
					}

					/* Modern Sections Table Card */
					.short-modern-table-card {
						background: transparent;
						border: none;
						border-radius: 0;
						overflow-x: auto !important;
						box-shadow: none;
						margin-top: 0;
						position: relative;
						width: 100%;
					}
					.short-modern-table {
						width: 100%;
						border-collapse: separate !important;
						border-spacing: 0 10px;
						background: transparent;
						table-layout: auto;
					}
					.short-modern-table thead th {
						background: transparent;
						color: #64748b;
						font-size: 11px;
						font-weight: 700;
						letter-spacing: 0.6px;
						text-transform: uppercase;
						padding: 4px 12px 0 12px;
						border-bottom: none;
						text-align: left;
						white-space: nowrap;
					}
					.short-modern-table thead tr th:first-child {
						border-top-left-radius: 0;
					}
					.short-modern-table thead tr th:last-child {
						border-top-right-radius: 0;
					}
					.short-modern-table tbody tr {
						border-bottom: none;
						transition: all 0.2s ease;
						box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
					}
					.short-modern-table tbody tr {
						position: relative;
						z-index: 1;
					}
					.short-modern-table tbody tr:hover {
						box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
					}
					.short-modern-table tbody tr.has-open-dropdown,
					.short-modern-table tbody tr:has(.short-custom-dropdown-wrap.is-open),
					.tab-config-row.has-open-dropdown,
					.tab-config-row:has(.short-custom-dropdown-wrap.is-open) {
						z-index: 999999 !important;
						position: relative !important;
					}
					.short-modern-table tbody td {
						padding: 12px 10px;
						vertical-align: middle;
						color: #1e293b;
						font-size: 13px;
						position: relative;
						background: #ffffff;
						border-top: 1px solid #e2e8f0;
						border-bottom: 1px solid #e2e8f0;
					}
					.short-modern-table tbody td:first-child {
						border-left: 1px solid #e2e8f0;
						border-top-left-radius: 10px;
						border-bottom-left-radius: 10px;
					}
					.short-modern-table tbody td:last-child {
						border-right: 1px solid #e2e8f0;
						border-top-right-radius: 10px;
						border-bottom-right-radius: 10px;
					}

					/* Modern Inputs & Dropdowns */
					.short-modern-input {
						height: 38px;
						border: 1px solid #e2e8f0 !important;
						border-radius: 8px !important;
						padding: 0 12px !important;
						font-size: 13.5px !important;
						color: #1e293b !important;
						background: #ffffff !important;
						width: 100% !important;
						box-sizing: border-box !important;
						transition: all 0.15s ease !important;
						box-shadow: none !important;
					}
					.short-modern-input:focus {
						border-color: #38bdf8 !important;
						outline: none !important;
						box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.22) !important;
					}
					.short-modern-num-input {
						height: 38px;
						border: 1px solid #e2e8f0 !important;
						border-radius: 8px !important;
						padding: 0 8px !important;
						font-size: 13.5px !important;
						color: #1e293b !important;
						text-align: center !important;
						width: 60px !important;
						box-sizing: border-box !important;
						transition: all 0.15s ease !important;
						box-shadow: none !important;
					}
					.short-modern-num-input:focus {
						border-color: #38bdf8 !important;
						outline: none !important;
						box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.22) !important;
					}

					/* Custom Floating Dropdown Menu (Matches media_1790900059976.png) */
					.short-custom-dropdown-wrap {
						position: relative;
						width: 100%;
						box-sizing: border-box;
					}
					.short-dropdown-trigger {
						height: 38px;
						width: 100%;
						border: 1px solid #e2e8f0;
						border-radius: 8px;
						padding: 0 12px;
						font-size: 13px;
						font-weight: 500;
						color: #1e293b;
						background: #ffffff;
						display: flex;
						align-items: center;
						justify-content: space-between;
						gap: 8px;
						cursor: pointer;
						box-sizing: border-box;
						transition: all 0.15s ease;
						text-align: left;
						user-select: none;
					}
					.short-dropdown-trigger:hover {
						border-color: #cbd5e1;
					}
					.short-custom-dropdown-wrap.is-open .short-dropdown-trigger,
					.short-dropdown-trigger:focus {
						border-color: #38bdf8 !important;
						outline: none !important;
						box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.22) !important;
					}
					.short-dropdown-label {
						overflow: hidden;
						text-overflow: ellipsis;
						white-space: nowrap;
						flex: 1;
					}
					.short-dropdown-arrow {
						display: inline-flex;
						align-items: center;
						justify-content: center;
						color: #64748b;
						transition: transform 0.2s ease;
						flex-shrink: 0;
					}
					.short-custom-dropdown-wrap.is-open .short-dropdown-arrow {
						transform: rotate(180deg);
					}
					.short-dropdown-menu {
						display: none;
						position: absolute;
						top: calc(100% + 4px);
						left: 0;
						width: 100%;
						min-width: 230px;
						max-height: 270px;
						overflow-y: auto;
						background: #ffffff;
						border: 1px solid #e2e8f0;
						border-radius: 12px;
						box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12), 0 4px 10px rgba(15, 23, 42, 0.04);
						padding: 6px;
						z-index: 999999;
						box-sizing: border-box;
					}
					.short-custom-dropdown-wrap.is-open .short-dropdown-menu {
						display: block;
						animation: shortDropdownPop 0.15s cubic-bezier(0.16, 1, 0.3, 1);
					}
					@keyframes shortDropdownPop {
						from { opacity: 0; transform: translateY(-4px); }
						to { opacity: 1; transform: translateY(0); }
					}
					.short-dropdown-menu::-webkit-scrollbar {
						width: 6px;
					}
					.short-dropdown-menu::-webkit-scrollbar-track {
						background: transparent;
					}
					.short-dropdown-menu::-webkit-scrollbar-thumb {
						background: #cbd5e1;
						border-radius: 9999px;
					}
					.short-dropdown-menu::-webkit-scrollbar-thumb:hover {
						background: #94a3b8;
					}
					.short-dropdown-group-header {
						font-size: 11px;
						font-weight: 800;
						color: #475569;
						letter-spacing: 0.5px;
						text-transform: uppercase;
						padding: 8px 10px 4px 10px;
						margin-top: 4px;
						border-top: 1px solid #f1f5f9;
					}
					.short-dropdown-group-header:first-child {
						border-top: none;
						margin-top: 0;
					}
					.short-dropdown-item {
						padding: 9px 12px;
						border-radius: 8px;
						font-size: 13px;
						font-weight: 500;
						color: #1e293b;
						cursor: pointer;
						line-height: 1.4;
						transition: all 0.12s ease;
						display: flex;
						align-items: center;
						gap: 8px;
					}
					.short-dropdown-item:hover {
						background: #f8fafc;
						color: #0f172a;
					}
					.short-dropdown-item.is-selected {
						background: #e0f2fe;
						color: #0284c7;
						font-weight: 700;
					}

					/* Prevent WordPress Footer from floating into table & Add Right Padding */
					#wpfooter {
						position: relative !important;
						margin-top: 50px !important;
						clear: both !important;
						display: block !important;
					}
					#wpcontent, #wpbody-content {
						padding-right: 10px !important;
					}
					#wpbody-content {
						padding-bottom: 60px !important;
						box-sizing: border-box !important;
						overflow: visible !important;
					}
					#wpfooter {
						display: none !important;
					}
					.wrap.short-builder-wrap {
						position: relative !important;
						clear: both !important;
						margin: 10px 0 30px 0 !important;
						padding: 0 !important;
						box-sizing: border-box !important;
						max-width: 100% !important;
					}
					.short-modern-table thead th:last-child,
					.short-modern-table tbody td:last-child {
						padding-right: 12px !important;
					}
					.short-modern-table thead th:first-child,
					.short-modern-table tbody td:first-child {
						padding-left: 20px !important;
					}
					.short-builder-toolbar {
						margin: 18px 0 16px 0;
						display: flex;
						gap: 10px;
						align-items: center;
						flex-wrap: wrap;
						padding: 0;
					}
					#short-sections-form {
						margin: 0;
						padding: 0;
					}
					.short-modern-table-card {
						margin-right: 0;
					}

					/* Custom Modern Checkbox (Clean SVG Checkmark, No WP Dashicon Clash) */
					.wp-core-ui input[type="checkbox"].short-modern-checkbox,
					input[type="checkbox"].short-modern-checkbox {
						-webkit-appearance: none !important;
						-moz-appearance: none !important;
						appearance: none !important;
						width: 20px !important;
						height: 20px !important;
						min-width: 20px !important;
						min-height: 20px !important;
						border: 1.5px solid #cbd5e1 !important;
						border-radius: 6px !important;
						background-color: #ffffff !important;
						cursor: pointer !important;
						display: inline-flex !important;
						align-items: center !important;
						justify-content: center !important;
						position: relative !important;
						margin: 0 auto !important;
						vertical-align: middle !important;
						transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
						box-shadow: none !important;
						outline: none !important;
						padding: 0 !important;
					}
					.wp-core-ui input[type="checkbox"].short-modern-checkbox:hover,
					input[type="checkbox"].short-modern-checkbox:hover {
						border-color: #94a3b8 !important;
					}
					.wp-core-ui input[type="checkbox"].short-modern-checkbox:focus,
					input[type="checkbox"].short-modern-checkbox:focus {
						border-color: #38bdf8 !important;
						box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.25) !important;
					}
					.wp-core-ui input[type="checkbox"].short-modern-checkbox::before,
					input[type="checkbox"].short-modern-checkbox::before,
					.wp-core-ui input[type="checkbox"].short-modern-checkbox:before,
					input[type="checkbox"].short-modern-checkbox:before,
					.wp-core-ui input[type="checkbox"].short-modern-checkbox::after,
					input[type="checkbox"].short-modern-checkbox::after,
					.wp-core-ui input[type="checkbox"].short-modern-checkbox:after,
					input[type="checkbox"].short-modern-checkbox:after {
						display: none !important;
						content: none !important;
					}
					.wp-core-ui input[type="checkbox"].short-modern-checkbox:checked,
					input[type="checkbox"].short-modern-checkbox:checked {
						background-color: #0284c7 !important;
						background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E") !important;
						background-repeat: no-repeat !important;
						background-position: center center !important;
						background-size: 13px 13px !important;
						border-color: #0284c7 !important;
						box-shadow: 0 1px 3px rgba(2, 132, 199, 0.25) !important;
					}

					/* Row Action Buttons */
					.short-row-actions {
						display: inline-flex;
						align-items: center;
						justify-content: center;
						gap: 6px;
					}
					.short-btn-icon-action {
						display: inline-flex;
						align-items: center;
						justify-content: center;
						width: 32px;
						height: 32px;
						padding: 0;
						border-radius: 8px;
						border: 1px solid #e2e8f0;
						background: #ffffff;
						color: #64748b;
						cursor: pointer;
						transition: all 0.15s ease;
						box-shadow: 0 1px 2px rgba(0,0,0,0.02);
					}
					.short-btn-icon-action svg {
						display: block;
						margin: auto;
						transition: transform 0.15s ease;
					}
					.short-btn-icon-action:hover {
						background: #f8fafc;
						border-color: #cbd5e1;
						color: #1e293b;
						transform: translateY(-1px);
						box-shadow: 0 2px 5px rgba(0,0,0,0.05);
					}
					.short-btn-icon-action:active {
						transform: translateY(0);
						box-shadow: none;
					}
					.short-btn-icon-action.btn-move-up:hover,
					.short-btn-icon-action.btn-move-down:hover {
						border-color: #93c5fd;
						color: #0284c7;
						background: #eff6ff;
					}
					.short-btn-icon-action.btn-del-row,
					.short-btn-icon-action.btn-del-preset {
						border-color: #e2e8f0;
						color: #94a3b8;
						background: #ffffff;
					}
					.short-btn-icon-action.btn-del-row:hover,
					.short-btn-icon-action.btn-del-preset:hover {
						background: #fef2f2;
						border-color: #fca5a5;
						color: #ef4444;
					}
				</style>

				<div class="short-modern-table-card">
				<?php if ( 'bottom_nav' === $target || 'desktop_nav' === $target ) : ?>
					<!-- Dedicated Navigation Menu Builder -->
					<table class="short-modern-table" id="short-sections-table">
						<thead>
							<tr>
								<th style="width:45px; text-align:center;">#</th>
								<th style="width:200px;"><?php _e( 'MENU ITEM LABEL', 'short-stream-core' ); ?></th>
								<th style="width:240px;"><?php _e( 'DESTINATION / PAGE', 'short-stream-core' ); ?></th>
								<th><?php _e( 'TARGET LINK / URL', 'short-stream-core' ); ?></th>
								<th style="width:75px; text-align:center;"><?php _e( 'ENABLED', 'short-stream-core' ); ?></th>
								<th style="width:125px; text-align:center;"><?php _e( 'ACTIONS', 'short-stream-core' ); ?></th>
							</tr>
						</thead>
						<tbody id="short-sortable-sections">
							<?php foreach ( $sections as $i => $sec ) : 
								$type = $sec['type'] ?? 'home';
								$url  = ! empty( $sec['endpoint'] ) ? $sec['endpoint'] : ( ! empty( $sec['url'] ) ? $sec['url'] : home_url( '/' . ( $type === 'home' ? '' : $type . '/' ) ) );
							?>
								<tr class="section-row" data-row-index="<?php echo $i; ?>">
									<td style="text-align:center; vertical-align:middle;"><strong class="row-num" style="color:#64748b; font-size:13.5px;"><?php echo $i + 1; ?></strong><input type="hidden" name="sections[<?php echo $i; ?>][id]" value="<?php echo esc_attr( $sec['id'] ?? $type ); ?>" /></td>
									<td><input type="text" name="sections[<?php echo $i; ?>][title]" value="<?php echo esc_attr( $sec['title'] ); ?>" class="short-modern-input sec-title-input" required /></td>
									<td>
										<select name="sections[<?php echo $i; ?>][type]" class="short-modern-select sec-nav-type-select">
											<option value="home" <?php selected( $type, 'home' ); ?>>Home (/)</option>
											<option value="shorttv" <?php selected( $type, 'shorttv' ); ?>>Short TV (/short-tv/)</option>
											<option value="categories" <?php selected( $type, 'categories' ); ?>>Categories (/genre/)</option>
											<option value="new-popular" <?php selected( $type, 'new-popular' ); ?>>New & Popular (/new-popular/)</option>
											<option value="leaderboard" <?php selected( $type, 'leaderboard' ); ?>>&#127942; Leaderboard)</option>
											<option value="my-list" <?php selected( $type, 'my-list' ); ?>>My List (/my-list/)</option>
											<option value="account" <?php selected( $type, 'account' ); ?>>Account (/account/)</option>
											<option value="custom" <?php selected( $type, 'custom' ); ?>>Custom Page / URL</option>
										</select>
									</td>
									<td>
										<input type="text" name="sections[<?php echo $i; ?>][endpoint]" value="<?php echo esc_attr( $url ); ?>" class="short-modern-input sec-nav-url-input" placeholder="<?php echo esc_url( home_url( '/' ) ); ?>" />
									</td>
									<td style="text-align:center; vertical-align:middle;"><input type="checkbox" name="sections[<?php echo $i; ?>][enabled]" value="1" class="short-modern-checkbox" <?php checked( ! empty( $sec['enabled'] ) ); ?> /></td>
									<td style="text-align:center; vertical-align:middle; white-space:nowrap;">
										<div class="short-row-actions">
											<button type="button" class="short-btn-icon-action btn-move-up" title="<?php esc_attr_e( 'Move Up', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
											</button>
											<button type="button" class="short-btn-icon-action btn-move-down" title="<?php esc_attr_e( 'Move Down', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
											</button>
											<button type="button" class="short-btn-icon-action btn-del-row" title="<?php esc_attr_e( 'Delete Row', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
											</button>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<table class="short-modern-table" id="short-sections-table">
						<thead>
							<tr>
								<th style="width:45px; text-align:center;">#</th>
								<th style="width:220px;"><?php _e( 'SECTION TITLE', 'short-stream-core' ); ?></th>
								<th style="width:210px;"><?php _e( 'CARD LAYOUT STYLE', 'short-stream-core' ); ?></th>
								<th style="width:230px;"><?php _e( 'CONTENT SOURCE', 'short-stream-core' ); ?></th>
								<th><?php _e( 'ENDPOINT OVERRIDE', 'short-stream-core' ); ?></th>
								<th style="width:85px; text-align:center;"><?php _e( 'MAX ITEMS', 'short-stream-core' ); ?></th>
								<th style="width:75px; text-align:center;"><?php _e( 'ENABLED', 'short-stream-core' ); ?></th>
								<th style="width:125px; text-align:center;"><?php _e( 'ACTIONS', 'short-stream-core' ); ?></th>
							</tr>
						</thead>
						<tbody id="short-sortable-sections">
							<?php foreach ( $sections as $i => $sec ) : 
								$sec = self::migrate_legacy_section( $sec );
								$type = $sec['type'] ?? 'views';
								$layout = $sec['layout'] ?? 'portrait';
							?>
								<tr class="section-row" data-row-index="<?php echo $i; ?>">
									<td style="text-align:center;"><strong class="row-num" style="color:#64748b; font-size:13.5px;"><?php echo $i + 1; ?></strong><input type="hidden" name="sections[<?php echo $i; ?>][id]" value="<?php echo esc_attr( $sec['id'] ?? ('sec_' . $i) ); ?>" /></td>
									<td><input type="text" name="sections[<?php echo $i; ?>][title]" value="<?php echo esc_attr( $sec['title'] ); ?>" class="short-modern-input sec-title-input" required /></td>
									<td>
										<select name="sections[<?php echo $i; ?>][layout]" class="short-modern-select sec-layout-select">
											<option value="hero" <?php selected( $layout, 'hero' ); ?>>&#127916; Hero Slider (Cinematic Billboard)</option>
											<option value="portrait" <?php selected( $layout, 'portrait' ); ?>>&#128241; Vertical 9:16 Poster (Standard)</option>
											<option value="top10" <?php selected( $layout, 'top10' ); ?>>&#128287; TOP 10 (Giant Rank Numbers)</option>
											<option value="leaderboard" <?php selected( $layout, 'leaderboard' ); ?>>&#127942; Leaderboard (Podium &amp; Ranking Board)</option>
											<option value="landscape" <?php selected( $layout, 'landscape' ); ?>>&#128421;&#65039; Landscape 16:9 Banner</option>
										</select>
									</td>
									<td>
										<select name="sections[<?php echo $i; ?>][type]" class="short-modern-select sec-type-select">
											<?php
											$sorting_presets = array();
											$genre_presets   = array();
											$custom_presets  = array();
											$std_sort_keys   = array( 'latest', 'views', 'rating', 'leaderboard', 'episodes', 'vip' );

											foreach ( $presets as $p ) {
												$ep = $p['endpoint'] ?? '';
												if ( 0 === strpos( $ep, 'genre/' ) ) {
													$genre_presets[] = $p;
												} elseif ( in_array( $ep, $std_sort_keys, true ) ) {
													$sorting_presets[] = $p;
												} else {
													$custom_presets[] = $p;
												}
											}
											?>
											<?php if ( ! empty( $sorting_presets ) ) : ?>
											<optgroup label="&#128293; Sorting &amp; Ranking">
												<?php foreach ( $sorting_presets as $p ) : 
													$opt_val = $p['endpoint'];
												?>
													<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $type === $opt_val || $type === $p['id'] ); ?>><?php echo wp_kses_post( $p['name'] ); ?></option>
												<?php endforeach; ?>
											</optgroup>
											<?php endif; ?>

											<?php if ( ! empty( $genre_presets ) ) : ?>
											<optgroup label="&#127917; Genre Filters">
												<?php foreach ( $genre_presets as $p ) : 
													$opt_val = $p['endpoint'];
												?>
													<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $type === $opt_val || $type === $p['id'] ); ?>><?php echo wp_kses_post( $p['name'] ); ?></option>
												<?php endforeach; ?>
												<option value="genre/custom" <?php selected( $type, 'genre/custom' ); ?>>🏷️ Custom Genre (by Slug)</option>
											</optgroup>
											<?php endif; ?>

											<?php if ( ! empty( $custom_presets ) ) : ?>
											<optgroup label="⚡ Custom &amp; External Presets">
												<?php foreach ( $custom_presets as $p ) : 
													$opt_val = ! empty( $p['endpoint'] ) ? $p['endpoint'] : $p['id'];
												?>
													<option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $type === $opt_val || $type === $p['id'] ); ?>><?php echo wp_kses_post( $p['name'] ); ?></option>
												<?php endforeach; ?>
											</optgroup>
											<?php endif; ?>
											<optgroup label="▶️ Real-time / History">
												<option value="continue_watching" <?php selected( $type, 'continue_watching' ); ?>>Continue Watching (User History)</option>
											</optgroup>
										</select>
									</td>
									<td>
										<?php $ep_placeholder = ( 'continue_watching' === $type ) ? 'Auto' : 'e.g. views, rating, latest'; ?>
										<input type="text" name="sections[<?php echo $i; ?>][endpoint]" value="<?php echo esc_attr( $sec['endpoint'] ); ?>" class="short-modern-input sec-endpoint-input" placeholder="<?php echo esc_attr( $ep_placeholder ); ?>" />
									</td>
									<td style="text-align:center;"><input type="number" name="sections[<?php echo $i; ?>][limit]" value="<?php echo esc_attr( $sec['limit'] ?? 20 ); ?>" class="short-modern-num-input" min="5" max="50" /></td>
									<td style="text-align:center; vertical-align:middle;"><input type="checkbox" name="sections[<?php echo $i; ?>][enabled]" value="1" class="short-modern-checkbox" <?php checked( ! empty( $sec['enabled'] ) ); ?> /></td>
									<td style="text-align:center; vertical-align:middle; white-space:nowrap;">
										<div class="short-row-actions">
											<button type="button" class="short-btn-icon-action btn-move-up" title="<?php esc_attr_e( 'Move Up', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
											</button>
											<button type="button" class="short-btn-icon-action btn-move-down" title="<?php esc_attr_e( 'Move Down', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
											</button>
											<button type="button" class="short-btn-icon-action btn-del-row" title="<?php esc_attr_e( 'Delete Row', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
											</button>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				</div>
				<p class="submit" style="margin-top:20px; display:none;">
					<button type="submit" class="short-modern-btn short-modern-btn-primary"><?php echo esc_html( $save_button ); ?></button>
					<span id="save-sections-status" style="margin-left:12px; font-weight:bold;"></span>
				</p>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($){
			var presetsMap = <?php echo json_encode( $presets_map ); ?>;
			var currentTarget = '<?php echo esc_js( $target ); ?>';

			function initCustomDropdowns(){
				$('.short-modern-select').each(function(){
					var $select = $(this);
					var $existingWrap = $select.next('.short-custom-dropdown-wrap');
					if($existingWrap.length){
						var selText = $select.find('option:selected').text();
						$existingWrap.find('.short-dropdown-label').text(selText);
						return;
					}
					$select.hide();

					var $wrap = $('<div class="short-custom-dropdown-wrap"></div>');
					var $trigger = $('<button type="button" class="short-dropdown-trigger"></button>');
					var $triggerText = $('<span class="short-dropdown-label"></span>');
					var $triggerIcon = $('<span class="short-dropdown-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg></span>');
					$trigger.append($triggerText).append($triggerIcon);

					var $menu = $('<div class="short-dropdown-menu"></div>');

					function updateTriggerLabel(){
						var selText = $select.find('option:selected').text();
						$triggerText.text(selText);
					}

					function populateItems(){
						$menu.empty();
						var currentVal = $select.val();

						$select.children().each(function(){
							if($(this).is('optgroup')){
								var groupLabel = $(this).attr('label');
								var $gHead = $('<div class="short-dropdown-group-header"></div>').text(groupLabel);
								$menu.append($gHead);

								$(this).find('option').each(function(){
									var val = $(this).val();
									var txt = $(this).text();
									var isSel = (val == currentVal);
									var $item = $('<div class="short-dropdown-item"></div>')
										.attr('data-value', val)
										.toggleClass('is-selected', isSel)
										.text(txt);
									$menu.append($item);
								});
							} else if($(this).is('option')){
								var val = $(this).val();
								var txt = $(this).text();
								var isSel = (val == currentVal);
								var $item = $('<div class="short-dropdown-item"></div>')
									.attr('data-value', val)
									.toggleClass('is-selected', isSel)
									.text(txt);
								$menu.append($item);
							}
						});
					}

					updateTriggerLabel();
					$wrap.append($trigger).append($menu);
					$select.after($wrap);

					$trigger.on('click', function(e){
						e.preventDefault();
						e.stopPropagation();
						var wasOpen = $wrap.hasClass('is-open');
						$('.short-custom-dropdown-wrap').removeClass('is-open');
						$('tr.section-row, .tab-config-row').removeClass('has-open-dropdown');
						if(!wasOpen){
							populateItems();
							$wrap.addClass('is-open');
							$wrap.closest('tr.section-row, .tab-config-row').addClass('has-open-dropdown');
						}
					});

					$menu.on('click', '.short-dropdown-item', function(e){
						e.preventDefault();
						e.stopPropagation();
						var chosenVal = $(this).data('value');
						$select.val(chosenVal).trigger('change');
						updateTriggerLabel();
						$wrap.removeClass('is-open');
						$('tr.section-row, .tab-config-row').removeClass('has-open-dropdown');
					});
				});
			}

			// Close dropdowns when clicking outside
			$(document).on('click', function(e){
				if(!$(e.target).closest('.short-custom-dropdown-wrap').length){
					$('.short-custom-dropdown-wrap').removeClass('is-open');
					$('tr.section-row, .tab-config-row').removeClass('has-open-dropdown');
				}
			});

			initCustomDropdowns();

			function reindexRows(){
				$('#short-sortable-sections tr.section-row').each(function(idx){
					$(this).attr('data-row-index', idx);
					$(this).find('.row-num').text(idx + 1);
					$(this).find('input, select').each(function(){
						var name = $(this).attr('name');
						if(name){
							var newName = name.replace(/^sections\[[^\]]+\]/, 'sections[' + idx + ']');
							$(this).attr('name', newName);
						}
					});
				});
				initCustomDropdowns();
			}

			// Move Row Up
			$(document).on('click', '.btn-move-up', function(e){
				e.preventDefault();
				e.stopPropagation();
				var row = $(this).closest('tr.section-row');
				var prev = row.prev('tr.section-row');
				if(prev.length){
					var rowType = row.find('.sec-type-select').val();
					var rowLayout = row.find('.sec-layout-select').val();
					var rowChecked = row.find('input[type="checkbox"]').is(':checked');

					var prevType = prev.find('.sec-type-select').val();
					var prevLayout = prev.find('.sec-layout-select').val();
					var prevChecked = prev.find('input[type="checkbox"]').is(':checked');

					row.insertBefore(prev);

					row.find('.sec-type-select').val(rowType);
					row.find('.sec-layout-select').val(rowLayout);
					row.find('input[type="checkbox"]').prop('checked', rowChecked);

					prev.find('.sec-type-select').val(prevType);
					prev.find('.sec-layout-select').val(prevLayout);
					prev.find('input[type="checkbox"]').prop('checked', prevChecked);

					reindexRows();
					initCustomDropdowns();

					row.css('background-color', '#e7f3ff');
					setTimeout(function(){
						row.css('transition', 'background-color 0.4s ease').css('background-color', '');
					}, 250);
				}
			});

			// Move Row Down
			$(document).on('click', '.btn-move-down', function(e){
				e.preventDefault();
				e.stopPropagation();
				var row = $(this).closest('tr.section-row');
				var next = row.next('tr.section-row');
				if(next.length){
					var rowType = row.find('.sec-type-select').val();
					var rowLayout = row.find('.sec-layout-select').val();
					var rowChecked = row.find('input[type="checkbox"]').is(':checked');

					var nextType = next.find('.sec-type-select').val();
					var nextLayout = next.find('.sec-layout-select').val();
					var nextChecked = next.find('input[type="checkbox"]').is(':checked');

					row.insertAfter(next);

					row.find('.sec-type-select').val(rowType);
					row.find('.sec-layout-select').val(rowLayout);
					row.find('input[type="checkbox"]').prop('checked', rowChecked);

					next.find('.sec-type-select').val(nextType);
					next.find('.sec-layout-select').val(nextLayout);
					next.find('input[type="checkbox"]').prop('checked', nextChecked);

					reindexRows();

					row.css('background-color', '#e7f3ff');
					setTimeout(function(){
						row.css('transition', 'background-color 0.4s ease').css('background-color', '');
					}, 250);
				}
			});

			// Delete Row
			$(document).on('click', '.btn-del-row', function(){
				if($('#short-sortable-sections tr').length <= 1){
					alert('You must keep at least one section.');
					return;
				}
				if(confirm('Are you sure you want to remove this section?')){
					$(this).closest('tr').remove();
					reindexRows();
				}
			});

			// Auto placeholder and endpoint fill on type change
			$(document).on('change', '.sec-type-select', function(){
				var row = $(this).closest('tr');
				var val = $(this).val();
				var epInput = row.find('.sec-endpoint-input');
				if(val === 'continue_watching'){
					epInput.val('').attr('placeholder', 'Auto');
				} else if(presetsMap && presetsMap[val]){
					epInput.val(presetsMap[val].endpoint);
					epInput.attr('placeholder', presetsMap[val].endpoint);
				} else {
					epInput.val(val);
					epInput.attr('placeholder', 'e.g. ' + val);
				}
			});

			// Auto link update for bottom navigation destination select
			var siteUrl = '<?php echo esc_url( home_url( '/' ) ); ?>';
			$(document).on('change', '.sec-nav-type-select', function(){
				var row = $(this).closest('tr');
				var val = $(this).val();
				var urlInput = row.find('.sec-nav-url-input');
				if (val === 'home') {
					urlInput.val(siteUrl);
				} else if (val === 'shorttv') {
					urlInput.val(siteUrl + 'short-tv/');
				} else if (val === 'categories') {
					urlInput.val(siteUrl + 'genre/');
				} else if (val === 'new-popular') {
					urlInput.val(siteUrl + 'new-popular/');
				} else if (val === 'leaderboard') {
					urlInput.val(siteUrl + 'new-popular/#leaderboard');
				} else if (val === 'my-list') {
					urlInput.val(siteUrl + 'my-list/');
				} else if (val === 'account') {
					urlInput.val(siteUrl + 'account/');
				} else if (val === 'custom') {
					if (!urlInput.val()) urlInput.val(siteUrl);
				}
			});

			// Add New Row
			$('#short-add-row-btn').on('click', function(){
				var count = $('#short-sortable-sections tr').length;

				if (currentTarget === 'bottom_nav' || currentTarget === 'desktop_nav') {
					var newNavRow = `
						<tr class="section-row" data-row-index="${count}">
							<td style="text-align:center; vertical-align:middle;"><strong class="row-num" style="color:#64748b; font-size:13.5px;">${count + 1}</strong><input type="hidden" name="sections[${count}][id]" value="nav_tab_${Date.now()}" /></td>
							<td><input type="text" name="sections[${count}][title]" value="New Tab" class="short-modern-input sec-title-input" required /></td>
							<td>
								<select name="sections[${count}][type]" class="short-modern-select sec-nav-type-select">
									<option value="home">Home (/)</option>
									<option value="shorttv">Short TV (/short-tv/)</option>
									<option value="categories">Categories (/genre/)</option>
									<option value="new-popular">New & Popular (/new-popular/)</option>
									<option value="leaderboard">&#127942; Leaderboard)</option>
									<option value="my-list">My List (/my-list/)</option>
									<option value="account">Account (/account/)</option>
									<option value="custom">Custom Page / URL</option>
								</select>
							</td>
							<td>
								<input type="text" name="sections[${count}][endpoint]" value="${siteUrl}" class="short-modern-input sec-nav-url-input" placeholder="${siteUrl}" />
							</td>
							<td style="text-align:center; vertical-align:middle;"><input type="checkbox" name="sections[${count}][enabled]" value="1" class="short-modern-checkbox" checked /></td>
							<td style="text-align:center; vertical-align:middle; white-space:nowrap;">
								<div class="short-row-actions">
									<button type="button" class="short-btn-icon-action btn-move-up" title="Move Up">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
									</button>
									<button type="button" class="short-btn-icon-action btn-move-down" title="Move Down">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
									</button>
									<button type="button" class="short-btn-icon-action btn-del-row" title="Delete Row">
										<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
									</button>
								</div>
							</td>
						</tr>
					`;
					$('#short-sortable-sections').append(newNavRow);
					reindexRows();
					return;
				}

				var sortOpts = '';
				var genreOpts = '';
				var customOpts = '';
				var stdSorts = ['latest', 'views', 'rating', 'leaderboard', 'episodes', 'vip'];

				$.each(presetsMap, function(pid, pdata){
					var ep = pdata.endpoint || '';
					var optVal = ep || pid;
					if (ep.indexOf('genre/') === 0) {
						genreOpts += `<option value="${optVal}">${pdata.name}</option>`;
					} else if (stdSorts.indexOf(ep) !== -1) {
						sortOpts += `<option value="${optVal}" ${optVal === defaultType ? 'selected' : ''}>${pdata.name}</option>`;
					} else {
						customOpts += `<option value="${optVal}">${pdata.name}</option>`;
					}
				});

				var defaultType = 'views';
				var defaultEp = 'views';
				var defaultLayout = 'portrait';

				var newRow = `
					<tr class="section-row" data-row-index="${count}">
						<td style="text-align:center; vertical-align:middle;"><strong class="row-num" style="color:#64748b; font-size:13.5px;">${count + 1}</strong><input type="hidden" name="sections[${count}][id]" value="custom_sec_${Date.now()}" /></td>
						<td><input type="text" name="sections[${count}][title]" value="New Drama Section" class="short-modern-input sec-title-input" required /></td>
						<td>
							<select name="sections[${count}][layout]" class="short-modern-select sec-layout-select">
								<option value="hero">&#127916; Hero Slider (Cinematic Billboard)</option>
								<option value="portrait" ${defaultLayout === 'portrait' ? 'selected' : ''}>&#128241; Vertical 9:16 Poster (Standard)</option>
								<option value="top10">&#128287; TOP 10 (Giant Rank Numbers)</option>
								<option value="leaderboard">&#127942; Leaderboard (Podium &amp; Ranking Board)</option>
								<option value="landscape">&#128421;&#65039; Landscape 16:9 Banner</option>
							</select>
						</td>
						<td>
							<select name="sections[${count}][type]" class="short-modern-select sec-type-select">
								${sortOpts ? `<optgroup label="&#128293; Sorting &amp; Ranking">${sortOpts}</optgroup>` : ''}
								${genreOpts ? `<optgroup label="&#127917; Genre Filters">${genreOpts}<option value="genre/custom">🏷️ Custom Genre (by Slug)</option></optgroup>` : ''}
								${customOpts ? `<optgroup label="⚡ Custom &amp; External Presets">${customOpts}</optgroup>` : ''}
								<optgroup label="▶️ Real-time / History">
									<option value="continue_watching">Continue Watching (User History)</option>
								</optgroup>
							</select>
						</td>
						<td><input type="text" name="sections[${count}][endpoint]" value="${defaultEp}" class="short-modern-input sec-endpoint-input" placeholder="e.g. views, rating, latest, genre/romance" /></td>
						<td><input type="number" name="sections[${count}][limit]" value="20" class="short-modern-num-input" min="5" max="50" /></td>
						<td style="text-align:center; vertical-align:middle;"><input type="checkbox" name="sections[${count}][enabled]" value="1" class="short-modern-checkbox" checked /></td>
						<td style="text-align:center; vertical-align:middle; white-space:nowrap;">
							<div class="short-row-actions">
								<button type="button" class="short-btn-icon-action btn-move-up" title="Move Up">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
								</button>
								<button type="button" class="short-btn-icon-action btn-move-down" title="Move Down">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
								</button>
								<button type="button" class="short-btn-icon-action btn-del-row" title="Delete Row">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
								</button>
							</div>
						</td>
					</tr>
				`;
				$('#short-sortable-sections').append(newRow);
				reindexRows();
			});

			// Reset Sections to Defaults
			$('#short-reset-sections-btn').on('click', function(){
				var target = $('#short_section_target').val() || 'home';
				var labelsMap = {
					'home': 'Homepage',
					'categories': 'Categories',
					'new-popular': 'New and Popular',
					'desktop_nav': 'Desktop Nav',
					'mobile_home': 'Mobile Home',
					'mobile_discovery': 'Mobile Discovery',
					'mobile_popular': 'Mobile Popular',
					'mobile_account': 'Mobile Account',
					'bottom_nav': 'Mobile Nav'
				};
				var targetLabel = labelsMap[target] || 'Current';
				if(confirm('Reset all ' + targetLabel + ' sections to the recommended Short defaults?')){
					var btn = $(this).prop('disabled', true);
					$.post(ajaxurl, { 
						action: 'short_reset_sections', 
						target: target,
						nonce: '<?php echo wp_create_nonce("short_admin_nonce"); ?>' 
					}, function(res){
						btn.prop('disabled', false);
						if(res.success){
							location.reload();
						} else {
							alert('Failed to reset: ' + (res.data || 'Unknown error'));
						}
					});
				}
			});

			// Save Form AJAX
			$('#short-sections-form').on('submit', function(e){
				e.preventDefault();
				var data = $(this).serialize() + '&action=short_save_sections&nonce=<?php echo wp_create_nonce("short_admin_nonce"); ?>';
				$('.save-sections-status, #save-sections-status').html('<span style="color:#666;">Saving changes...</span>');
				$.post(ajaxurl, data, function(res){
					if(res.success){
						$('.save-sections-status, #save-sections-status').html('<span style="color:green;">&#10003; Configuration saved successfully!</span>');
						setTimeout(function(){ $('.save-sections-status, #save-sections-status').empty(); }, 3500);
					} else {
						$('.save-sections-status, #save-sections-status').html('<span style="color:red;">&#10007; Error saving sections</span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	public static function render_presets() {
		self::render_presets_manager();
	}

	public static function render_presets_manager() {
		$presets = self::get_presets();
		?>
		<div class="wrap short-presets-wrap">
			<style>
				.short-presets-wrap {
					max-width: 100% !important;
					margin: 20px 20px 40px 2px;
				}
				.short-builder-toolbar {
					margin: 18px 0 16px 0;
					display: flex;
					gap: 10px;
					align-items: center;
					flex-wrap: wrap;
				}
				.short-modern-btn {
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: 7px !important;
					height: 38px !important;
					padding: 0 16px !important;
					border-radius: 8px !important;
					font-size: 13px !important;
					font-weight: 600 !important;
					cursor: pointer !important;
					text-decoration: none !important;
					box-sizing: border-box !important;
					white-space: nowrap !important;
					transition: all 0.16s cubic-bezier(0.16, 1, 0.3, 1) !important;
					line-height: 1 !important;
				}
				.short-modern-btn svg {
					flex-shrink: 0;
				}
				.short-modern-btn-primary {
					background: #0284c7 !important;
					border: 1px solid #0284c7 !important;
					color: #ffffff !important;
					font-weight: 700 !important;
					box-shadow: 0 1px 2px rgba(2, 132, 199, 0.18) !important;
				}
				.short-modern-btn-primary:hover {
					background: #0369a1 !important;
					border-color: #0369a1 !important;
					color: #ffffff !important;
					transform: translateY(-1px);
					box-shadow: 0 3px 8px rgba(2, 132, 199, 0.28) !important;
				}
				.short-modern-btn-danger {
					background: #ffffff !important;
					border: 1px solid #fecaca !important;
					color: #dc2626 !important;
				}
				.short-modern-btn-danger:hover {
					background: #fef2f2 !important;
					border-color: #fca5a5 !important;
					color: #b91c1c !important;
					transform: translateY(-1px);
				}
				.short-modern-btn-preset {
					background: #ffffff !important;
					border: 1px solid #bae6fd !important;
					color: #0284c7 !important;
				}
				.short-modern-btn-preset:hover {
					background: #f0f9ff !important;
					border-color: #7dd3fc !important;
					color: #0369a1 !important;
					transform: translateY(-1px);
				}
				.short-modern-table-card {
					background: transparent;
					border: none;
					border-radius: 0;
					overflow-x: auto !important;
					box-shadow: none;
					margin-top: 0;
					position: relative;
					width: 100%;
				}
				.short-modern-table {
					width: 100%;
					border-collapse: separate !important;
					border-spacing: 0 10px;
					background: transparent;
					table-layout: auto;
				}
				.short-modern-table thead th {
					background: transparent;
					color: #64748b;
					font-size: 11px;
					font-weight: 700;
					letter-spacing: 0.6px;
					text-transform: uppercase;
					padding: 4px 14px 0 14px;
					border-bottom: none;
					text-align: left;
					white-space: nowrap;
				}
				.short-modern-table tbody tr {
					background: #ffffff;
					box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
					border: 1px solid #e2e8f0;
					transition: all 0.2s ease;
				}
				.short-modern-table tbody tr:hover {
					box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
					border-color: #cbd5e1;
				}
				.short-modern-table tbody tr td {
					padding: 12px 14px;
					border-top: 1px solid #e2e8f0;
					border-bottom: 1px solid #e2e8f0;
					vertical-align: top;
					background: #ffffff;
				}
				.short-modern-table tbody tr td:first-child {
					border-left: 1px solid #e2e8f0;
					border-top-left-radius: 10px;
					border-bottom-left-radius: 10px;
				}
				.short-modern-table tbody tr td:last-child {
					border-right: 1px solid #e2e8f0;
					border-top-right-radius: 10px;
					border-bottom-right-radius: 10px;
				}
				.short-modern-table tbody tr td .preset-num {
					display: inline-block;
					line-height: 38px;
				}
				.short-modern-input {
					width: 100% !important;
					height: 38px !important;
					border-radius: 8px !important;
					border: 1px solid #cbd5e1 !important;
					padding: 0 12px !important;
					font-size: 13px !important;
					color: #1e293b !important;
					background: #ffffff !important;
					box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
					transition: all 0.15s ease !important;
					box-sizing: border-box !important;
				}
				.short-modern-input:focus {
					border-color: #0284c7 !important;
					box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
					outline: none !important;
				}
				.short-modern-test-btn {
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: 6px !important;
					height: 38px !important;
					padding: 0 14px !important;
					border-radius: 8px !important;
					font-size: 12.5px !important;
					font-weight: 600 !important;
					color: #0284c7 !important;
					background: #f0f9ff !important;
					border: 1px solid #bae6fd !important;
					cursor: pointer !important;
					transition: all 0.15s ease !important;
					line-height: 1 !important;
					white-space: nowrap !important;
					box-sizing: border-box !important;
				}
				.short-modern-test-btn:hover {
					background: #e0f2fe !important;
					border-color: #7dd3fc !important;
					color: #0369a1 !important;
					transform: translateY(-1px);
				}
				.short-modern-test-btn svg {
					flex-shrink: 0;
				}
				.short-row-actions {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					gap: 6px;
					height: 38px;
				}
				.short-btn-icon-action {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					width: 32px;
					height: 32px;
					padding: 0;
					border-radius: 8px;
					border: 1px solid #e2e8f0;
					background: #ffffff;
					color: #64748b;
					cursor: pointer;
					transition: all 0.15s ease;
					box-shadow: 0 1px 2px rgba(0,0,0,0.02);
				}
				.short-btn-icon-action:hover {
					background: #f8fafc;
					border-color: #cbd5e1;
					color: #1e293b;
					transform: translateY(-1px);
					box-shadow: 0 2px 5px rgba(0,0,0,0.05);
				}
				.short-btn-icon-action.btn-move-up-preset:hover,
				.short-btn-icon-action.btn-move-down-preset:hover {
					border-color: #93c5fd;
					color: #0284c7;
					background: #eff6ff;
				}
				.short-btn-icon-action.btn-del-preset:hover {
					background: #fef2f2;
					border-color: #fca5a5;
					color: #ef4444;
				}
				.short-test-badge {
					display: inline-flex;
					align-items: center;
					gap: 5px;
					padding: 4px 9px;
					border-radius: 6px;
					font-size: 11.5px;
					font-weight: 600;
					line-height: 1.3;
					margin-top: 6px;
				}
			</style>
			<h1><span class="dashicons dashicons-admin-settings" style="font-size:30px; width:30px; height:30px; margin-right:8px; vertical-align:middle;"></span> <?php _e( 'ShortTV Endpoint Presets Manager', 'short-stream-core' ); ?></h1>
			<p class="description"><?php _e( 'Manage, customize, and test the preset endpoints and feeds used in the Section Builder and homepage rows.', 'short-stream-core' ); ?></p>

			<form id="short-presets-form">
				<div class="short-builder-toolbar">
					<button type="button" class="short-modern-btn short-modern-btn-primary" id="short-add-preset-btn">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
						<span><?php _e( 'Add new row', 'short-stream-core' ); ?></span>
					</button>
					<button type="button" class="short-modern-btn short-modern-btn-danger" id="short-reset-presets-btn">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
						<span><?php _e( 'Reset to defaults', 'short-stream-core' ); ?></span>
					</button>
					<button type="button" class="short-modern-btn short-modern-btn-preset" id="short-open-generator-btn">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
						<span><?php _e( 'Endpoint Generator', 'short-stream-core' ); ?></span>
					</button>
					<div style="margin-left:auto; display:flex; align-items:center; gap:10px;">
						<span class="save-presets-status" style="font-weight:bold;"></span>
						<button type="submit" class="short-modern-btn short-modern-btn-primary">
							<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
							<span><?php _e( 'Save configuration', 'short-stream-core' ); ?></span>
						</button>
					</div>
				</div>

				<div class="short-modern-table-card">
					<table class="short-modern-table" id="short-presets-table">
						<thead>
							<tr>
								<th style="width:45px; text-align:center;">#</th>
								<th style="width:190px;"><?php _e( 'PRESET IDENTIFIER / KEY', 'short-stream-core' ); ?></th>
								<th style="width:260px;"><?php _e( 'DISPLAY NAME / LABEL', 'short-stream-core' ); ?></th>
								<th><?php _e( 'SHORTTV ENDPOINT / QUERY FEED', 'short-stream-core' ); ?></th>
								<th style="width:110px; text-align:center;"><?php _e( 'LIVE TEST', 'short-stream-core' ); ?></th>
								<th style="width:125px; text-align:center;"><?php _e( 'ACTIONS', 'short-stream-core' ); ?></th>
							</tr>
						</thead>
						<tbody id="short-presets-tbody">
							<?php foreach ( $presets as $i => $preset ) : ?>
								<tr class="preset-row" data-row-index="<?php echo $i; ?>">
									<td style="text-align:center;"><strong class="preset-num" style="color:#64748b; font-size:13.5px;"><?php echo $i + 1; ?></strong></td>
									<td><input type="text" name="presets[<?php echo $i; ?>][id]" value="<?php echo esc_attr( $preset['id'] ); ?>" class="short-modern-input code" required /></td>
									<td><input type="text" name="presets[<?php echo $i; ?>][name]" value="<?php echo esc_attr( $preset['name'] ); ?>" class="short-modern-input" required /></td>
									<td>
										<input type="text" name="presets[<?php echo $i; ?>][endpoint]" value="<?php echo esc_attr( $preset['endpoint'] ); ?>" class="short-modern-input code preset-endpoint-input" placeholder="e.g. views, rating, latest, genre/romance" required />
										<div class="preset-test-result"></div>
									</td>
									<td style="text-align:center;">
										<button type="button" class="short-modern-test-btn btn-test-preset">
											<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
											<span><?php _e( 'Test', 'short-stream-core' ); ?></span>
										</button>
									</td>
									<td style="text-align:center; vertical-align:middle; white-space:nowrap;">
										<div class="short-row-actions">
											<button type="button" class="short-btn-icon-action btn-move-up-preset" title="<?php esc_attr_e( 'Move Up', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
											</button>
											<button type="button" class="short-btn-icon-action btn-move-down-preset" title="<?php esc_attr_e( 'Move Down', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
											</button>
											<button type="button" class="short-btn-icon-action btn-del-preset" title="<?php esc_attr_e( 'Delete Preset', 'short-stream-core' ); ?>">
												<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
											</button>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<p class="submit" style="margin-top:20px; display:none;">
					<button type="submit" class="short-modern-btn short-modern-btn-primary"><?php _e( 'Save Endpoint Presets', 'short-stream-core' ); ?></button>
					<span id="save-presets-status" style="margin-left:12px; font-weight:bold;"></span>
				</p>
			</form>
		</div>

		<!-- ShortTV Feed & Filter Generator Modal -->
		<div id="short-generator-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.65); z-index:999999; align-items:center; justify-content:center;">
			<div style="background:#fff; width:720px; max-width:92vw; max-height:90vh; border-radius:8px; box-shadow:0 10px 30px rgba(0,0,0,0.3); display:flex; flex-direction:column; overflow:hidden;">
				<div style="padding:16px 22px; background:#111827; color:#fff; display:flex; align-items:center; justify-content:space-between;">
					<h2 style="margin:0; font-size:18px; color:#fff; display:flex; align-items:center; gap:8px;">
						<span class="dashicons dashicons-admin-generic" style="font-size:20px; width:20px; height:20px; color:#e11d48;"></span>
						<span>ShortTV Feed & Query Generator</span>
					</h2>
					<button type="button" id="short-close-gen-modal" style="background:transparent; border:none; color:#bbb; font-size:24px; cursor:pointer; line-height:1;">&times;</button>
				</div>
				<div style="padding:22px; overflow-y:auto; flex:1;">
					<p style="margin-top:0; color:#64748b; font-size:13px;">Select your desired ShortTV feed type, genre / trope, sorting metric, release year, or drama status to automatically generate an optimized ShortTV query endpoint.</p>
					
					<div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
						<div>
							<label style="display:block; font-weight:600; margin-bottom:4px;">1. Feed / Content Type</label>
							<select id="gen-type" style="width:100%; height:36px;">
								<option value="genre">Curated Drama Trope / Genre (genre/*)</option>
								<option value="views">🔥 Most Viewed (views)</option>
								<option value="rating">⭐ Highest Rated (rating)</option>
								<option value="latest">🚀 New Releases (latest)</option>
								<option value="episodes">&#128293; Binge-Ready (episodes)</option>
								<option value="hero">🎬 Hero Billboard Featured (views)</option>
							</select>
						</div>

						<div id="gen-genre-wrap">
							<label style="display:block; font-weight:600; margin-bottom:4px;">2. Drama Trope / Genre</label>
							<select id="gen-genre" style="width:100%; height:36px;">
								<option value="romance">💕 Romance & Passion</option>
								<option value="fantasy">✨ Fantasy & Supernatural</option>
								<option value="vampire">🧛 Vampire & Werewolf</option>
								<option value="ceo">👑 Billionaire & CEO</option>
								<option value="revenge">⚡ Revenge & Betrayal</option>
								<option value="urban">🏙️ Modern & Urban</option>
								<option value="historical">🏯 Historical & Period</option>
								<option value="thriller">🔪 Suspense & Mystery</option>
								<option value="family">👨‍👩‍👧 Family & Drama</option>
								<option value="comedy">😂 Comedy & Rom-Com</option>
								<option value="action">🔥 Action & Martial Arts</option>
							</select>
						</div>

						<div id="gen-sort-wrap">
							<label style="display:block; font-weight:600; margin-bottom:4px;">3. Sort Order / Priority</label>
							<select id="gen-sort" style="width:100%; height:36px;">
								<option value="views">Most Viewed (Popular)</option>
								<option value="rating">Highest Rated (Score)</option>
								<option value="latest">Newest First (Release Date)</option>
								<option value="episodes">Most Episodes (Binge-Worthy)</option>
							</select>
						</div>

						<div id="gen-year-wrap">
							<label style="display:block; font-weight:600; margin-bottom:4px;">4. Release Year</label>
							<select id="gen-year" style="width:100%; height:36px;">
								<option value="">All Release Years</option>
								<option value="2026">2026 Dramas</option>
								<option value="2025">2025 Dramas</option>
								<option value="2024">2024 Dramas</option>
								<option value="2023">2023 Dramas</option>
								<option value="2022">2022 Dramas</option>
								<option value="classic">Earlier Classics</option>
							</select>
						</div>

						<div id="gen-status-wrap">
							<label style="display:block; font-weight:600; margin-bottom:4px;">5. Airing Status / Quality</label>
							<select id="gen-status" style="width:100%; height:36px;">
								<option value="">Any Status / Rating</option>
								<option value="top_rated">Top Rated Only (Score ≥ 9.0)</option>
								<option value="binge_worthy">Binge-Worthy (20+ Episodes)</option>
								<option value="ongoing">Currently Airing (Ongoing)</option>
								<option value="completed">Completed Mini-Series</option>
							</select>
						</div>
					</div>

					<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:16px; margin-top:10px;">
						<div style="margin-bottom:12px;">
							<label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px; color:#1e293b;">Generated ShortTV Query / Endpoint:</label>
							<div style="display:flex; gap:8px;">
								<input type="text" id="gen-result-endpoint" class="widefat code" style="height:36px; font-weight:600; color:#0f172a; background:#fff; border:1px solid #cbd5e1;" readonly />
								<button type="button" class="button button-secondary" id="gen-btn-test" style="display:inline-flex; align-items:center; gap:4px; height:36px; padding:0 14px; font-weight:600;">
									<span class="dashicons dashicons-controls-play" style="font-size:14px; vertical-align:middle;"></span>
									<span>Live Test</span>
								</button>
							</div>
							<div id="gen-test-output" style="margin-top:6px;"></div>
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
							<div>
								<label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px;">Suggested Display Label:</label>
								<input type="text" id="gen-result-name" class="widefat" style="height:36px;" />
							</div>
							<div>
								<label style="display:block; font-weight:600; margin-bottom:4px; font-size:12px;">Suggested Identifier Key:</label>
								<input type="text" id="gen-result-id" class="widefat code" style="height:36px;" />
							</div>
						</div>
					</div>
				</div>

				<div style="padding:14px 22px; background:#f0f0f1; border-top:1px solid #dcdcde; display:flex; justify-content:flex-end; gap:10px; align-items:center;">
					<button type="button" class="button" id="short-cancel-gen-btn" style="height:38px; padding:0 18px; font-weight:600; font-size:13px; border-radius:6px;"><?php _e( 'Cancel', 'short-stream-core' ); ?></button>
					<button type="button" class="button button-primary button-large" id="gen-btn-insert" style="display:inline-flex; align-items:center; justify-content:center; gap:6px; height:38px; padding:0 18px; font-size:13px; font-weight:600; border-radius:6px;">
						<span class="dashicons dashicons-plus-alt2" style="font-size:16px; width:16px; height:16px; line-height:16px; display:inline-flex; align-items:center; justify-content:center; margin:0;"></span>
						<span><?php _e( 'Add to Presets Table', 'short-stream-core' ); ?></span>
					</button>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($){
			function reindexPresets(){
				$('#short-presets-tbody tr.preset-row').each(function(idx){
					$(this).attr('data-row-index', idx);
					$(this).find('.preset-num').text(idx + 1);
					$(this).find('input').each(function(){
						var name = $(this).attr('name');
						if(name){
							var newName = name.replace(/^presets\[[^\]]+\]/, 'presets[' + idx + ']');
							$(this).attr('name', newName);
						}
					});
				});
			}

			// Move Preset Up
			$(document).on('click', '.btn-move-up-preset', function(e){
				e.preventDefault();
				e.stopPropagation();
				var row = $(this).closest('tr.preset-row');
				var prev = row.prev('tr.preset-row');
				if(prev.length){
					row.insertBefore(prev);
					reindexPresets();
					row.css('background-color', '#e7f3ff');
					setTimeout(function(){
						row.css('transition', 'background-color 0.4s ease').css('background-color', '');
					}, 250);
				}
			});

			// Move Preset Down
			$(document).on('click', '.btn-move-down-preset', function(e){
				e.preventDefault();
				e.stopPropagation();
				var row = $(this).closest('tr.preset-row');
				var next = row.next('tr.preset-row');
				if(next.length){
					row.insertAfter(next);
					reindexPresets();
					row.css('background-color', '#e7f3ff');
					setTimeout(function(){
						row.css('transition', 'background-color 0.4s ease').css('background-color', '');
					}, 250);
				}
			});

			// ShortTV Feed & Endpoint Generator Logic
			function updateGenerator(){
				var type = $('#gen-type').val();
				var genre = $('#gen-genre').val();
				var sort = $('#gen-sort').val();
				var year = $('#gen-year').val();
				var status = $('#gen-status').val();

				var ep = '';
				var labelParts = [];
				var idParts = [];

				if (type === 'genre') {
					$('#gen-genre-wrap').show();
					ep = 'genre/' + genre;
					var genreText = $('#gen-genre option:selected').text().replace(/[^\w\s&]/gi, '').trim();
					labelParts.push(genreText);
					idParts.push('genre_' + genre);
				} else if (type === 'hero') {
					$('#gen-genre-wrap').hide();
					ep = 'views';
					labelParts.push('Hero Showcase Slider');
					idParts.push('hero_showcase');
				} else if (type === 'views') {
					$('#gen-genre-wrap').hide();
					ep = 'views';
					labelParts.push('Most Viewed Dramas');
					idParts.push('popular');
				} else if (type === 'rating') {
					$('#gen-genre-wrap').hide();
					ep = 'rating';
					labelParts.push('Top Rated Mini-Series');
					idParts.push('top_rated');
				} else if (type === 'latest') {
					$('#gen-genre-wrap').hide();
					ep = 'latest';
					labelParts.push('New Drama Releases');
					idParts.push('latest');
				} else if (type === 'episodes') {
					$('#gen-genre-wrap').hide();
					ep = 'episodes';
					labelParts.push('Binge-Ready Dramas');
					idParts.push('binge');
				}

				if (year) {
					labelParts.push('(' + year + ')');
					idParts.push(year);
				}

				if (status === 'top_rated' && type !== 'rating') {
					labelParts.unshift('Top Rated');
				} else if (status === 'binge_worthy' && type !== 'episodes') {
					labelParts.push('(Binge-Worthy)');
				} else if (status === 'ongoing') {
					labelParts.push('(Ongoing)');
				} else if (status === 'completed') {
					labelParts.push('(Completed)');
				}

				$('#gen-result-endpoint').val(ep);
				$('#gen-result-name').val(labelParts.join(' ') || 'ShortTV Drama Feed');
				$('#gen-result-id').val(idParts.join('_') || ('shorttv_' + Date.now()));
				$('#gen-test-output').empty();
			}

			// Modal Open / Close
			$('#short-open-generator-btn').on('click', function(){
				$('#short-generator-modal').css('display', 'flex');
				updateGenerator();
			});
			$('#short-close-gen-modal, #short-cancel-gen-btn').on('click', function(){
				$('#short-generator-modal').hide();
			});

			$('#gen-type, #gen-genre, #gen-sort, #gen-year, #gen-status').on('change', updateGenerator);

			// Live Test from Generator
			$('#gen-btn-test').on('click', function(){
				var ep = $('#gen-result-endpoint').val();
				var btn = $(this).prop('disabled', true);
				var out = $('#gen-test-output').html('<span style="color:#666; font-size:11px;">Testing live query...</span>');

				$.post(ajaxurl, { action: 'short_test_endpoint', endpoint: ep, nonce: '<?php echo wp_create_nonce("short_admin_nonce"); ?>' }, function(res){
					btn.prop('disabled', false);
					if(res.success){
						out.html(`<span class="short-test-badge" style="background:#e7f7ed; color:#186a3b;">&#10003; Live Connection Verified: ${res.data.count} items found (Sample: "${res.data.sample}")</span>`);
					} else {
						out.html(`<span class="short-test-badge" style="background:#fdedec; color:#922b21;">&#10007; ${res.data || 'Failed to fetch items'}</span>`);
					}
				});
			});

			// Insert Generated Preset
			$('#gen-btn-insert').on('click', function(){
				var id = $('#gen-result-id').val() || ('custom_' + Date.now());
				var name = $('#gen-result-name').val() || 'Custom Content Row';
				var ep = $('#gen-result-endpoint').val();

				var count = $('#short-presets-tbody tr').length;
				var newRow = `
					<tr class="preset-row" data-row-index="${count}" style="background:#f0f9ff;">
						<td style="text-align:center;"><strong class="preset-num" style="color:#64748b; font-size:13.5px;">${count + 1}</strong></td>
						<td><input type="text" name="presets[${count}][id]" value="${id}" class="short-modern-input code" required /></td>
						<td><input type="text" name="presets[${count}][name]" value="${name}" class="short-modern-input" required /></td>
						<td>
							<input type="text" name="presets[${count}][endpoint]" value="${ep}" class="short-modern-input code preset-endpoint-input" required />
							<div class="preset-test-result"></div>
						</td>
						<td style="text-align:center;">
							<button type="button" class="short-modern-test-btn btn-test-preset">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
								<span>Test</span>
							</button>
						</td>
						<td style="text-align:center; vertical-align:middle; white-space:nowrap;">
							<div class="short-row-actions">
								<button type="button" class="short-btn-icon-action btn-move-up-preset" title="Move Up">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
								</button>
								<button type="button" class="short-btn-icon-action btn-move-down-preset" title="Move Down">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
								</button>
								<button type="button" class="short-btn-icon-action btn-del-preset" title="Delete Preset">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
								</button>
							</div>
						</td>
					</tr>
				`;
				$('#short-presets-tbody').append(newRow);
				reindexPresets();
				$('#short-generator-modal').hide();

				// Scroll to and highlight new row
				var $newRowElem = $('#short-presets-tbody tr:last-child');
				$('html, body').animate({
					scrollTop: $newRowElem.offset().top - 120
				}, 350);
				setTimeout(function(){
					$newRowElem.css('transition', 'background-color 0.8s ease').css('background-color', '#ffffff');
				}, 600);
			});

			// Test Single Preset in Table
			$(document).on('click', '.btn-test-preset', function(){
				var row = $(this).closest('tr');
				var ep = row.find('.preset-endpoint-input').val();
				var resultDiv = row.find('.preset-test-result');
				var btn = $(this).prop('disabled', true).html('<span>Testing...</span>');

				$.post(ajaxurl, { action: 'short_test_endpoint', endpoint: ep, nonce: '<?php echo wp_create_nonce("short_admin_nonce"); ?>' }, function(res){
					btn.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> <span>Test</span>');
					if(res.success){
						resultDiv.html(`<span class="short-test-badge" style="background:#e7f7ed; color:#186a3b;">&#10003; ${res.data.count} items: "${res.data.sample}"</span>`);
					} else {
						resultDiv.html(`<span class="short-test-badge" style="background:#fdedec; color:#922b21;">&#10007; ${res.data || 'Error'}</span>`);
					}
				});
			});

			// Add Blank Preset
			$('#short-add-preset-btn').on('click', function(){
				var count = $('#short-presets-tbody tr').length;
				var newRow = `
					<tr class="preset-row" data-row-index="${count}">
						<td style="text-align:center;"><strong class="preset-num" style="color:#64748b; font-size:13.5px;">${count + 1}</strong></td>
						<td><input type="text" name="presets[${count}][id]" value="custom_${Date.now()}" class="short-modern-input code" required /></td>
						<td><input type="text" name="presets[${count}][name]" value="My Drama Feed" class="short-modern-input" required /></td>
						<td>
							<input type="text" name="presets[${count}][endpoint]" value="genre/romance" class="short-modern-input code preset-endpoint-input" placeholder="e.g. views, rating, latest, genre/romance" required />
							<div class="preset-test-result"></div>
						</td>
						<td style="text-align:center;">
							<button type="button" class="short-modern-test-btn btn-test-preset">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
								<span>Test</span>
							</button>
						</td>
						<td style="text-align:center; vertical-align:middle; white-space:nowrap;">
							<div class="short-row-actions">
								<button type="button" class="short-btn-icon-action btn-move-up-preset" title="Move Up">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
								</button>
								<button type="button" class="short-btn-icon-action btn-move-down-preset" title="Move Down">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
								</button>
								<button type="button" class="short-btn-icon-action btn-del-preset" title="Delete Preset">
									<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
								</button>
							</div>
						</td>
					</tr>
				`;
				$('#short-presets-tbody').append(newRow);
				reindexPresets();
			});

			// Delete Preset
			$(document).on('click', '.btn-del-preset', function(){
				if($('#short-presets-tbody tr').length <= 1){
					alert('You must keep at least one preset.');
					return;
				}
				if(confirm('Are you sure you want to delete this preset?')){
					$(this).closest('tr').remove();
					reindexPresets();
				}
			});

			// Reset Presets
			$('#short-reset-presets-btn').on('click', function(){
				if(confirm('Reset all endpoint presets to standard factory defaults?')){
					var btn = $(this).prop('disabled', true);
					$.post(ajaxurl, { action: 'short_reset_presets', nonce: '<?php echo wp_create_nonce("short_admin_nonce"); ?>' }, function(res){
						btn.prop('disabled', false);
						if(res.success){
							location.reload();
						} else {
							alert('Failed: ' + (res.data || 'Unknown error'));
						}
					});
				}
			});

			// Save Presets Form
			$('#short-presets-form').on('submit', function(e){
				e.preventDefault();
				var data = $(this).serialize() + '&action=short_save_presets&nonce=<?php echo wp_create_nonce("short_admin_nonce"); ?>';
				$('.save-presets-status, #save-presets-status').html('<span style="color:#666;">Saving presets...</span>');
				$.post(ajaxurl, data, function(res){
					if(res.success){
						$('.save-presets-status, #save-presets-status').html('<span style="color:green;">&#10003; Presets saved successfully!</span>');
						setTimeout(function(){ $('.save-presets-status, #save-presets-status').empty(); }, 3500);
					} else {
						$('.save-presets-status, #save-presets-status').html('<span style="color:red;">&#10007; Error saving presets</span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	public static function render_settings() {
		$tab = $_GET['tab'] ?? 'branding';
		if ( 'general' === $tab || 'cloudinary' === $tab || 'gumlet' === $tab ) {
			$tab = 'branding';
		}
		$brand    = ( get_option( 'short_brand_settings', array() ) ?: array() );
		$splash   = ( get_option( 'short_splash_settings', array() ) ?: array() );
		$firebase = ( get_option( 'short_firebase_settings', array() ) ?: get_option( 'short_firebase_settings', array() ) );
		$player   = ( get_option( 'short_player_settings', array() ) ?: get_option( 'short_player_settings', array() ) );
		$sub      = ( get_option( 'short_subscription_settings', array() ) ?: get_option( 'short_subscription_settings', array() ) );
		$ads      = ( get_option( 'short_ad_settings', array() ) ?: array() );
		
		// Enqueue media scripts for logo uploader
		wp_enqueue_media();
		?>
		<div class="wrap short-settings-wrap" style="width:100%; max-width:100%; margin-top:20px; box-sizing:border-box; padding-right:20px;">
			<style>
				.short-settings-wrap {
					width: 100% !important;
					max-width: 100% !important;
					box-sizing: border-box !important;
				}
				.short-settings-wrap .nav-tab-wrapper,
				.nav-tab-wrapper.short-settings-tabs {
					display: flex !important;
					flex-wrap: wrap !important;
					gap: 10px 12px !important;
					border-bottom: none !important;
					margin: 20px 0 26px 0 !important;
					padding: 0 !important;
				}
				.short-settings-wrap .nav-tab,
				.short-settings-tabs .nav-tab {
					display: inline-flex !important;
					align-items: center !important;
					gap: 8px !important;
					padding: 10px 18px !important;
					font-size: 13.5px !important;
					font-weight: 600 !important;
					border-radius: 8px !important;
					border: 1px solid #cbd5e1 !important;
					background: #ffffff !important;
					color: #334155 !important;
					margin: 0 !important;
					box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
					transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
					text-decoration: none !important;
					line-height: 1.4 !important;
				}
				.short-settings-wrap .nav-tab:hover,
				.short-settings-tabs .nav-tab:hover {
					background: #f8fafc !important;
					color: #0f172a !important;
					border-color: #94a3b8 !important;
					transform: translateY(-1px) !important;
					box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08) !important;
				}
				.short-settings-wrap .nav-tab.nav-tab-active,
				.short-settings-tabs .nav-tab.nav-tab-active {
					background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
					border-color: #0f172a !important;
					color: #ffffff !important;
					font-weight: 700 !important;
					transform: none !important;
					box-shadow: 0 4px 12px rgba(15, 23, 42, 0.22) !important;
				}

				/* Universal Modern Form UI Inputs */
				.short-settings-wrap input[type="text"],
				.short-settings-wrap input[type="password"],
				.short-settings-wrap input[type="url"],
				.short-settings-wrap input[type="email"],
				.short-settings-wrap input[type="number"],
				.short-settings-wrap select,
				.short-modern-form-input {
					border-radius: 8px !important;
					border: 1px solid #cbd5e1 !important;
					padding: 0 14px !important;
					font-size: 13.5px !important;
					color: #0f172a !important;
					background: #ffffff !important;
					height: 40px !important;
					box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
					transition: all 0.15s ease !important;
					box-sizing: border-box !important;
				}
				.short-settings-wrap input[type="text"]:focus,
				.short-settings-wrap input[type="password"]:focus,
				.short-settings-wrap input[type="url"]:focus,
				.short-settings-wrap input[type="email"]:focus,
				.short-settings-wrap input[type="number"]:focus,
				.short-settings-wrap select:focus,
				.short-modern-form-input:focus {
					border-color: #0284c7 !important;
					box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
					outline: none !important;
				}
				.short-settings-wrap textarea {
					border-radius: 8px !important;
					border: 1px solid #cbd5e1 !important;
					padding: 12px 14px !important;
					font-size: 13px !important;
					color: #0f172a !important;
					background: #ffffff !important;
					box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
					transition: all 0.15s ease !important;
					box-sizing: border-box !important;
				}
				.short-settings-wrap textarea:focus {
					border-color: #0284c7 !important;
					box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
					outline: none !important;
				}
				.short-modern-num-box {
					height: 38px !important;
					border-radius: 8px !important;
					border: 1px solid #cbd5e1 !important;
					padding: 0 12px !important;
					font-size: 13.5px !important;
					font-weight: 600 !important;
					color: #0f172a !important;
					background: #ffffff !important;
					box-sizing: border-box !important;
					transition: all 0.15s ease !important;
				}
				.short-modern-num-box:focus {
					border-color: #0284c7 !important;
					box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
					outline: none !important;
				}
				.short-mode-radio-card {
					display: flex !important;
					align-items: flex-start !important;
					gap: 12px !important;
					background: #ffffff !important;
					border: 1.5px solid #e2e8f0 !important;
					border-radius: 10px !important;
					padding: 14px 16px !important;
					cursor: pointer !important;
					transition: all 0.15s ease !important;
				}
				.short-mode-radio-card:hover {
					border-color: #93c5fd !important;
					background: #f8fafc !important;
				}
				.short-mode-radio-card.is-active {
					border-color: #0284c7 !important;
					background: #f0f9ff !important;
					box-shadow: 0 2px 6px rgba(2, 132, 199, 0.08) !important;
				}

				/* Modern Card and Table Polish */
				.short-settings-card {
					background: #ffffff !important;
					border: 1px solid #e2e8f0 !important;
					border-radius: 12px !important;
					padding: 24px 28px !important;
					margin-bottom: 24px !important;
					box-shadow: 0 2px 8px rgba(0,0,0,0.04) !important;
					width: 100% !important;
					box-sizing: border-box !important;
				}
				.short-settings-card-header {
					display: flex !important;
					align-items: center !important;
					justify-content: space-between !important;
					margin-bottom: 18px !important;
					border-bottom: 1px solid #f1f5f9 !important;
					padding-bottom: 14px !important;
					flex-wrap: wrap !important;
					gap: 12px !important;
				}
				.short-settings-card-header h3 {
					margin: 0 !important;
					font-size: 17px !important;
					font-weight: 800 !important;
					color: #0f172a !important;
					display: flex !important;
					align-items: center !important;
					gap: 8px !important;
				}
				.short-settings-card-header p {
					margin: 4px 0 0 !important;
					color: #64748b !important;
					font-size: 13px !important;
					line-height: 1.5 !important;
				}
				.short-settings-wrap table.form-table {
					margin-top: 0 !important;
					width: 100% !important;
				}
				.short-settings-wrap table.form-table th {
					font-size: 13.5px !important;
					font-weight: 700 !important;
					color: #1e293b !important;
					padding: 14px 16px 14px 0 !important;
					vertical-align: middle !important;
				}
				.short-settings-wrap table.form-table td {
					padding: 14px 0 !important;
					vertical-align: middle !important;
				}
				.short-settings-wrap .button-primary,
				.short-settings-wrap .submit .button-primary {
					background: #0284c7 !important;
					border-color: #0284c7 !important;
					border-radius: 8px !important;
					height: 40px !important;
					line-height: normal !important;
					padding: 0 24px !important;
					font-size: 13.5px !important;
					font-weight: 700 !important;
					box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25) !important;
					transition: all 0.15s ease !important;
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: 8px !important;
				}
				.short-settings-wrap .button-primary:hover,
				.short-settings-wrap .submit .button-primary:hover {
					background: #0369a1 !important;
					border-color: #0369a1 !important;
					transform: translateY(-1px) !important;
					box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35) !important;
				}
				/* Full-width: remove legacy max-width caps on sections & inputs */
				.short-settings-wrap form > div[style*="max-width"],
				.short-settings-wrap > div[style*="max-width"],
				.short-settings-wrap form div[style*="max-width"]:not(#splash-logo-preview-box),
				.short-settings-wrap input[style*="max-width"],
				.short-settings-wrap select[style*="max-width"] {
					max-width: none !important;
				}
				.short-settings-wrap > div[style*="max-width"] { width: 100% !important; }
				.short-settings-wrap .form-table input.regular-text,
				.short-settings-wrap .form-table input.large-text,
				.short-settings-wrap .form-table select {
					width: 100% !important;
					max-width: none !important;
				}
				.short-settings-wrap .form-table th { width: 240px !important; }
				.short-settings-wrap input[type="checkbox"] {
					width: 18px !important; height: 18px !important;
					border-radius: 5px !important; border: 1.5px solid #94a3b8 !important;
					margin: 0 8px 0 0 !important; vertical-align: middle !important;
				}
				.short-settings-wrap input[type="checkbox"]:checked { background: #0284c7 !important; border-color: #0284c7 !important; }
				.short-settings-wrap input[type="checkbox"]:checked::before { filter: brightness(0) invert(1); margin: -1px 0 0 -2px !important; }
				.short-settings-wrap .button:not(.button-primary) {
					border-radius: 8px !important; border-color: #cbd5e1 !important;
					height: 40px !important; min-height: 40px !important; line-height: normal !important; padding: 0 16px !important;
					display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important;
					font-size: 13px !important; font-weight: 600 !important;
				}
				.short-settings-wrap .button .dashicons {
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					width: 18px !important;
					height: 18px !important;
					font-size: 18px !important;
					line-height: 1 !important;
					margin: 0 !important;
					vertical-align: middle !important;
				}
				.short-settings-wrap .description { color: #64748b !important; font-size: 12.5px !important; }

				/* Modern Pill Tab Navigation */
				.short-settings-wrap .nav-tab-wrapper,
				.short-settings-tabs {
					border-bottom: none !important;
					padding: 0 !important;
					margin: 20px 0 26px 0 !important;
					display: flex !important;
					flex-wrap: wrap !important;
					gap: 10px !important;
				}
				.short-settings-wrap .nav-tab,
				.short-settings-tabs .nav-tab {
					background: #ffffff !important;
					border: 1.5px solid #e2e8f0 !important;
					border-radius: 10px !important;
					padding: 10px 18px !important;
					font-size: 13.5px !important;
					font-weight: 600 !important;
					color: #475569 !important;
					text-decoration: none !important;
					display: inline-flex !important;
					align-items: center !important;
					gap: 8px !important;
					transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
					box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
					margin: 0 !important;
					float: none !important;
					line-height: normal !important;
				}
				.short-settings-wrap .nav-tab:hover,
				.short-settings-tabs .nav-tab:hover {
					background: #f8fafc !important;
					color: #0f172a !important;
					border-color: #94a3b8 !important;
					transform: translateY(-1px) !important;
					box-shadow: 0 4px 10px rgba(0,0,0,0.06) !important;
				}
				.short-settings-wrap .nav-tab.nav-tab-active,
				.short-settings-tabs .nav-tab.nav-tab-active {
					background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
					color: #ffffff !important;
					border-color: #0284c7 !important;
					font-weight: 700 !important;
					box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35) !important;
					transform: translateY(-1px) !important;
				}
				.short-settings-wrap .nav-tab.nav-tab-active:hover,
				.short-settings-tabs .nav-tab.nav-tab-active:hover {
					background: linear-gradient(135deg, #0369a1 0%, #075985 100%) !important;
					color: #ffffff !important;
					border-color: #0369a1 !important;
				}
			</style>
			<h1 style="font-size:24px; font-weight:800; color:#0f172a; margin-bottom:4px;"><?php _e( 'Short Stream Settings', 'short-stream-core' ); ?></h1>
			<nav class="nav-tab-wrapper short-settings-tabs">
				<a href="?page=short-settings&tab=branding" class="nav-tab <?php echo ( 'branding' === $tab ) ? 'nav-tab-active' : ''; ?>">&#127912; <?php _e( 'Branding & Logo', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=splash" class="nav-tab <?php echo ( 'splash' === $tab ) ? 'nav-tab-active' : ''; ?>">&#127916; <?php _e( 'Splash Screen', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=protection" class="nav-tab <?php echo ( 'protection' === $tab ) ? 'nav-tab-active' : ''; ?>">&#128111;&#65039; <?php _e( 'Security & Protection', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=ads" class="nav-tab <?php echo ( 'ads' === $tab ) ? 'nav-tab-active' : ''; ?>">&#128181; <?php _e( 'Advertising & Monetization', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=firebase" class="nav-tab <?php echo ( 'firebase' === $tab ) ? 'nav-tab-active' : ''; ?>">&#128293; <?php _e( 'Firebase Configuration', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=player" class="nav-tab <?php echo ( 'player' === $tab ) ? 'nav-tab-active' : ''; ?>">&#9654;&#65039; <?php _e( 'Vertical Player & UI', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=subscription" class="nav-tab <?php echo ( 'subscription' === $tab ) ? 'nav-tab-active' : ''; ?>">&#128142; <?php _e( 'Subscription & Coins Paywall', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=notifications" class="nav-tab <?php echo ( 'notifications' === $tab ) ? 'nav-tab-active' : ''; ?>">&#128276; <?php _e( 'Notifications', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=backup" class="nav-tab <?php echo ( 'backup' === $tab ) ? 'nav-tab-active' : ''; ?>">&#128230; <?php _e( 'Backup & Migration', 'short-stream-core' ); ?></a>
				<a href="?page=short-settings&tab=license" class="nav-tab <?php echo ( 'license' === $tab ) ? 'nav-tab-active' : ''; ?>">&#128272; <?php _e( 'Theme License & Domain Lock', 'short-stream-core' ); ?></a>
			</nav>

			<?php if ( 'branding' === $tab ) : 
				$current_logo = ! empty( $brand['custom_logo_url'] ) ? $brand['custom_logo_url'] : ( function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : '' );
			?>
				<form method="post" action="options.php" id="short-branding-form" style="width:100%;">
					<?php settings_fields( 'short_brand_group' ); ?>
					
					<div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #e11d48; border-radius:12px; padding:26px 30px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04); width:100%; box-sizing:border-box;">
						<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #f1f5f9; padding-bottom:14px;">
							<div>
								<h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🖼️</span> <?php _e( 'Custom Website & App Logo', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13.5px;">
									<?php _e( 'Upload your brand PNG, SVG, or JPG logo. This logo automatically renders across the header, mobile navbar, splash screen, and Control Hub.', 'short-stream-core' ); ?>
								</p>
							</div>
						</div>

						<div style="display:grid; grid-template-columns: minmax(360px, 480px) 1fr; gap:36px; align-items:start;">
							<!-- Left Column: Logo Visual Preview & Uploader -->
							<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:22px;">
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:10px;"><?php _e( 'Current Logo Preview', 'short-stream-core' ); ?></label>
								<div id="short-logo-preview-box" style="display:flex; align-items:center; justify-content:center; padding:22px 28px; background:#0f172a; border-radius:10px; width:100%; min-height:90px; box-sizing:border-box; margin-bottom:16px; border:1px solid rgba(255,255,255,0.1); box-shadow:0 4px 14px rgba(0,0,0,0.15);">
									<img id="short-logo-preview-img" src="<?php echo esc_url( $current_logo ); ?>" alt="Site Logo" style="max-height:60px; max-width:100%; object-fit:contain; display:block;" />
								</div>
								
								<div style="margin-bottom:12px;">
									<input type="text" id="short-custom-logo-input" name="short_brand_settings[custom_logo_url]" value="<?php echo esc_attr( $brand['custom_logo_url'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="https://... or upload from Media Library" />
								</div>

								<div style="display:flex; gap:10px;">
									<button type="button" class="button button-primary" id="btn-upload-site-logo" style="display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 16px; font-weight:600; font-size:13px; border-radius:8px;">
										<span class="dashicons dashicons-upload" style="font-size:16px; width:16px; height:16px; line-height:16px;"></span>
										<span><?php _e( 'Upload Logo', 'short-stream-core' ); ?></span>
									</button>
									<button type="button" class="button button-secondary" id="btn-reset-site-logo" style="height:38px; padding:0 16px; font-weight:600; font-size:13px; border-radius:8px; <?php echo empty( $brand['custom_logo_url'] ) ? 'display:none;' : ''; ?>">
										<?php _e( 'Reset to Default', 'short-stream-core' ); ?>
									</button>
								</div>
								<p class="description" style="margin-top:12px; font-size:12px; color:#64748b; line-height:1.4;">
									<?php _e( 'Recommended format: <strong>Transparent SVG or PNG</strong> (Height: 32px – 64px).', 'short-stream-core' ); ?>
								</p>
							</div>

							<!-- Right Column: Display Modes, Brand Name & Dimensions -->
							<div>
								<div style="margin-bottom:22px;">
									<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:10px;"><?php _e( 'Logo Display Mode', 'short-stream-core' ); ?></label>
									<?php $display_mode = $brand['logo_display_mode'] ?? 'image_only'; ?>
									<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
										<label class="short-mode-radio-card <?php echo ( 'image_only' === $display_mode ) ? 'is-active' : ''; ?>">
											<input type="radio" name="short_brand_settings[logo_display_mode]" value="image_only" <?php checked( $display_mode, 'image_only' ); ?> style="margin-top:2px;" />
											<div>
												<strong style="font-size:13.5px; color:#0f172a; display:block;"><?php _e( 'Image Only', 'short-stream-core' ); ?></strong>
												<span style="display:block; margin-top:3px; font-size:12px; color:#64748b; line-height:1.4;"><?php _e( 'Hides text title next to logo on desktop & mobile.', 'short-stream-core' ); ?></span>
											</div>
										</label>
										<label class="short-mode-radio-card <?php echo ( 'image_text' === $display_mode ) ? 'is-active' : ''; ?>">
											<input type="radio" name="short_brand_settings[logo_display_mode]" value="image_text" <?php checked( $display_mode, 'image_text' ); ?> style="margin-top:2px;" />
											<div>
												<strong style="font-size:13.5px; color:#0f172a; display:block;"><?php _e( 'Image Icon + Text', 'short-stream-core' ); ?></strong>
												<span style="display:block; margin-top:3px; font-size:12px; color:#64748b; line-height:1.4;"><?php _e( 'Displays icon followed by site title on desktop.', 'short-stream-core' ); ?></span>
											</div>
										</label>
									</div>
								</div>

								<div style="margin-bottom:22px;">
									<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:8px;"><?php _e( 'Brand Display Name', 'short-stream-core' ); ?></label>
									<input type="text" name="short_brand_settings[brand_name]" value="<?php echo esc_attr( $brand['brand_name'] ?? get_bloginfo( 'name' ) ); ?>" class="short-modern-form-input" placeholder="ShortTV" />
									<p class="description" style="margin-top:6px; font-size:12px; color:#64748b;"><?php _e( 'Brand name used for SEO, tab titles, and when "Image Icon + Text" mode is active.', 'short-stream-core' ); ?></p>
								</div>

								<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px 20px;">
									<!-- Desktop Dimensions -->
									<div>
										<label style="font-weight:700; font-size:13px; color:#0f172a; display:flex; align-items:center; gap:6px; margin-bottom:10px;">
											<span>💻</span> <span><?php _e( 'Desktop Logo Dimensions', 'short-stream-core' ); ?></span>
										</label>
										<div style="display:flex; align-items:center; gap:14px;">
											<div style="flex:1;">
												<span style="font-size:11.5px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?php _e( 'Height (px)', 'short-stream-core' ); ?></span>
												<input type="number" name="short_brand_settings[logo_height]" value="<?php echo esc_attr( $brand['logo_height'] ?? '42' ); ?>" class="short-modern-num-box" style="width:100%;" min="15" max="150" step="1" />
											</div>
											<div style="flex:1;">
												<span style="font-size:11.5px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?php _e( 'Width (px)', 'short-stream-core' ); ?></span>
												<input type="number" name="short_brand_settings[logo_width]" value="<?php echo esc_attr( $brand['logo_width'] ?? '' ); ?>" class="short-modern-num-box" style="width:100%;" placeholder="Auto" min="20" max="400" step="1" />
											</div>
										</div>
									</div>

									<!-- Mobile Dimensions -->
									<div>
										<label style="font-weight:700; font-size:13px; color:#0f172a; display:flex; align-items:center; gap:6px; margin-bottom:10px;">
											<span>📱</span> <span><?php _e( 'Mobile Logo Dimensions', 'short-stream-core' ); ?></span>
										</label>
										<div style="display:flex; align-items:center; gap:14px;">
											<div style="flex:1;">
												<span style="font-size:11.5px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?php _e( 'Height (px)', 'short-stream-core' ); ?></span>
												<input type="number" name="short_brand_settings[mobile_logo_height]" value="<?php echo esc_attr( $brand['mobile_logo_height'] ?? '36' ); ?>" class="short-modern-num-box" style="width:100%;" min="15" max="100" step="1" />
											</div>
											<div style="flex:1;">
												<span style="font-size:11.5px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;"><?php _e( 'Width (px)', 'short-stream-core' ); ?></span>
												<input type="number" name="short_brand_settings[mobile_logo_width]" value="<?php echo esc_attr( $brand['mobile_logo_width'] ?? '' ); ?>" class="short-modern-num-box" style="width:100%;" placeholder="Auto" min="20" max="300" step="1" />
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Fallback Poster Asset Card -->
					<div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #8b5cf6; border-radius:12px; padding:26px 30px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04); width:100%; box-sizing:border-box;">
						<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #f1f5f9; padding-bottom:14px;">
							<div>
								<h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🎬</span> <?php _e( 'Default Fallback Drama Poster (9:16 Portrait)', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13.5px;">
									<?php _e( 'Global placeholder image used across Watch History, Continue Watching, Search, and grids whenever a drama poster is missing, failed to load, or broken.', 'short-stream-core' ); ?>
								</p>
							</div>
						</div>

						<div style="display:grid; grid-template-columns: 200px 1fr; gap:36px; align-items:start;">
							<!-- Left: 9:16 Visual Preview -->
							<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:18px; text-align:center;">
								<label style="font-weight:700; font-size:13px; color:#1e293b; display:block; margin-bottom:10px;"><?php _e( 'Fallback Preview (9:16)', 'short-stream-core' ); ?></label>
								<div id="short-fallback-preview-box" style="display:flex; align-items:center; justify-content:center; background:#12131a; border-radius:10px; width:135px; height:240px; margin:0 auto 14px auto; overflow:hidden; border:1px solid rgba(255,255,255,0.12); box-shadow:0 6px 18px rgba(0,0,0,0.25);">
									<?php 
									$current_fallback_poster = ! empty( $brand['fallback_poster_url'] ) ? $brand['fallback_poster_url'] : ( function_exists( 'short_get_default_poster_url' ) ? short_get_default_poster_url() : get_template_directory_uri() . '/assets/images/fallback-portrait.svg' ); 
									?>
									<img id="short-fallback-preview-img" src="<?php echo esc_url( $current_fallback_poster ); ?>" alt="Fallback Poster" style="width:100%; height:100%; object-fit:cover; display:block;" />
								</div>
							</div>

							<!-- Right: Input & Upload Button -->
							<div>
								<div style="margin-bottom:16px;">
									<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:8px;"><?php _e( 'Fallback Image URL or Media Library File', 'short-stream-core' ); ?></label>
									<input type="text" id="short-fallback-poster-input" name="short_brand_settings[fallback_poster_url]" value="<?php echo esc_attr( $brand['fallback_poster_url'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="https://... or choose from Media Library (leave empty for built-in SVG)" />
									<p class="description" style="margin-top:6px; font-size:12px; color:#64748b;"><?php _e( 'Default is a sleek 9:16 dark gradient with your centered ShortTV app icon. You can override it with your own high-res 9:16 portrait banner.', 'short-stream-core' ); ?></p>
								</div>

								<div style="display:flex; gap:10px;">
									<button type="button" class="button button-primary" id="btn-upload-fallback-poster" style="display:inline-flex; align-items:center; gap:6px; height:38px; padding:0 16px; font-weight:600; font-size:13px; border-radius:8px;">
										<span class="dashicons dashicons-upload" style="font-size:16px; width:16px; height:16px; line-height:16px;"></span>
										<span><?php _e( 'Upload Fallback Poster', 'short-stream-core' ); ?></span>
									</button>
									<button type="button" class="button button-secondary" id="btn-reset-fallback-poster" style="height:38px; padding:0 16px; font-weight:600; font-size:13px; border-radius:8px; <?php echo empty( $brand['fallback_poster_url'] ) ? 'display:none;' : ''; ?>">
										<?php _e( 'Reset to Built-in Default', 'short-stream-core' ); ?>
									</button>
								</div>
							</div>
						</div>
					</div>

					<div style="margin-top:20px;">
						<?php submit_button( __( 'Save Branding Settings', 'short-stream-core' ), 'primary button-large', 'submit', false, array( 'style' => 'height:40px; padding:0 24px; font-weight:700; font-size:14px; border-radius:8px; box-shadow:0 2px 6px rgba(2,132,199,0.25);' ) ); ?>
					</div>
				</form>

				<script>
				jQuery(document).ready(function($){
					var mediaFrame;
					$('#btn-upload-site-logo').on('click', function(e){
						e.preventDefault();
						if(mediaFrame){
							mediaFrame.open();
							return;
						}
						mediaFrame = wp.media({
							title: '<?php echo esc_js( __( 'Select or Upload Brand Logo', 'short-stream-core' ) ); ?>',
							button: { text: '<?php echo esc_js( __( 'Use as Site Logo', 'short-stream-core' ) ); ?>' },
							multiple: false,
							library: { type: 'image' }
						});
						mediaFrame.on('select', function(){
							var attachment = mediaFrame.state().get('selection').first().toJSON();
							$('#short-custom-logo-input').val(attachment.url);
							$('#short-logo-preview-img').attr('src', attachment.url);
							$('#btn-reset-site-logo').show();
						});
						mediaFrame.open();
					});

					$('#short-custom-logo-input').on('input change', function(){
						var val = $(this).val().trim();
						if(val){
							$('#short-logo-preview-img').attr('src', val);
							$('#btn-reset-site-logo').show();
						}
					});

					$('#btn-reset-site-logo').on('click', function(e){
						e.preventDefault();
						$('#short-custom-logo-input').val('');
						$('#short-logo-preview-img').attr('src', '<?php echo esc_js( esc_url( get_template_directory_uri() . '/assets/images/shorttv-logo.svg' ) ); ?>');
						$(this).hide();
					});

					// Fallback Poster Media Uploader
					var fallbackMediaFrame;
					$('#btn-upload-fallback-poster').on('click', function(e){
						e.preventDefault();
						if(fallbackMediaFrame){
							fallbackMediaFrame.open();
							return;
						}
						fallbackMediaFrame = wp.media({
							title: '<?php echo esc_js( __( 'Select or Upload Fallback Drama Poster (9:16)', 'short-stream-core' ) ); ?>',
							button: { text: '<?php echo esc_js( __( 'Use as Fallback Poster', 'short-stream-core' ) ); ?>' },
							multiple: false,
							library: { type: 'image' }
						});
						fallbackMediaFrame.on('select', function(){
							var attachment = fallbackMediaFrame.state().get('selection').first().toJSON();
							$('#short-fallback-poster-input').val(attachment.url);
							$('#short-fallback-preview-img').attr('src', attachment.url);
							$('#btn-reset-fallback-poster').show();
						});
						fallbackMediaFrame.open();
					});

					$('#short-fallback-poster-input').on('input change', function(){
						var val = $(this).val().trim();
						if(val){
							$('#short-fallback-preview-img').attr('src', val);
							$('#btn-reset-fallback-poster').show();
						}
					});

					$('#btn-reset-fallback-poster').on('click', function(e){
						e.preventDefault();
						$('#short-fallback-poster-input').val('');
						$('#short-fallback-preview-img').attr('src', '<?php echo esc_js( esc_url( get_template_directory_uri() . '/assets/images/fallback-portrait.svg' ) ); ?>');
						$(this).hide();
					});
				});
				</script>
			<?php elseif ( 'splash' === $tab ) : 
				$splash_enabled     = $splash['enabled'] ?? '1';
				$splash_device      = $splash['target_device'] ?? 'mobile_only';
				$splash_frequency   = $splash['frequency'] ?? 'session';
				$splash_mode        = $splash['mode'] ?? 'ribbon_letter';
				$splash_logo_url    = $splash['splash_logo_url'] ?? '';
				$splash_brand_name  = ! empty( $splash['splash_title'] ) ? $splash['splash_title'] : ( ! empty( $brand['brand_name'] ) ? $brand['brand_name'] : get_bloginfo( 'name' ) );
				if ( empty( $splash_brand_name ) ) $splash_brand_name = 'ShortTV';
				$splash_letter      = ! empty( $splash['splash_letter'] ) ? $splash['splash_letter'] : mb_strtoupper( mb_substr( trim( $splash_brand_name ), 0, 1 ) );
				$splash_tagline     = ! empty( $splash['splash_tagline'] ) ? $splash['splash_tagline'] : ( ! empty( $brand['brand_tagline'] ) ? $brand['brand_tagline'] : __( 'Unlimited Short Dramas, Mini-Series, and More', 'short-stream-core' ) );
				$splash_bg                  = $splash['bg_color'] ?? '#000000';
				$splash_accent              = ! empty( $splash['accent_color_custom'] ) ? $splash['accent_color_custom'] : ( $splash['accent_color'] ?? 'auto' );
				$splash_display_accent      = ( 'auto' === $splash_accent || empty( $splash_accent ) ) ? '#FF2D55' : $splash_accent;
				$splash_accent_first_letter = ! empty( $splash['accent_first_letter'] ) && '1' === $splash['accent_first_letter'];
				$splash_speed               = $splash['speed'] ?? 'normal';
				$splash_show_skip           = $splash['show_skip_hint'] ?? '1';
			?>
				<form method="post" action="options.php" id="short-splash-form" style="width:100%; box-sizing:border-box;">
					<?php settings_fields( 'short_splash_group' ); ?>

					<div style="display:flex; gap:24px; align-items:flex-start; flex-wrap:wrap; margin-top:20px; width:100%; box-sizing:border-box;">
						
						<!-- Left Column: Settings Cards -->
						<div style="flex:1 1 520px; min-width:300px; max-width:100%; box-sizing:border-box;">

							<!-- Card 1: Master Controls & Trigger Rules -->
							<div class="short-settings-card" style="border-left:4px solid #e11d48; margin-bottom:20px;">
								<div class="short-settings-card-header">
									<div>
										<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
											<span>🎬</span> <?php _e( 'App Splash Screen Master Controls', 'short-stream-core' ); ?>
										</h3>
										<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
											<?php _e( 'The Netflix-style cinematic splash screen welcomes users with an animated ribbon reveal, sound energy glow, and your custom brand identity upon app launch.', 'short-stream-core' ); ?>
										</p>
									</div>
								</div>

								<table class="form-table" style="margin-top:0;">
									<tr>
										<th style="width:180px;"><label><?php _e( 'Splash Screen Status', 'short-stream-core' ); ?></label></th>
										<td>
											<label style="font-weight:700; font-size:14px; cursor:pointer;">
												<input type="checkbox" name="short_splash_settings[enabled]" value="1" <?php checked( $splash_enabled, '1' ); ?> id="short-splash-enable-toggle" />
												<?php _e( 'Enable Animated Splash Screen', 'short-stream-core' ); ?>
											</label>
											<p class="description"><?php _e( 'Uncheck to completely bypass the splash screen.', 'short-stream-core' ); ?></p>
										</td>
									</tr>
									<tr>
										<th><label><?php _e( 'Target Viewports', 'short-stream-core' ); ?></label></th>
										<td>
											<fieldset>
												<label style="display:block; margin-bottom:8px;">
													<input type="radio" name="short_splash_settings[target_device]" value="mobile_only" <?php checked( $splash_device, 'mobile_only' ); ?> />
													<strong><?php _e( 'Mobile & Tablet Devices Only (Recommended)', 'short-stream-core' ); ?></strong>
													<span class="description" style="display:block; margin-left:22px; color:#64748b;"><?php _e( 'Launches on phones, PWA standalone apps, and tablets (screens &le; 768px). Desktop loads directly without splash delay.', 'short-stream-core' ); ?></span>
												</label>
												<label style="display:block;">
													<input type="radio" name="short_splash_settings[target_device]" value="all_devices" <?php checked( $splash_device, 'all_devices' ); ?> />
													<strong><?php _e( 'All Devices (Mobile, Tablets, and Desktop)', 'short-stream-core' ); ?></strong>
													<span class="description" style="display:block; margin-left:22px; color:#64748b;"><?php _e( 'Displays the splash screen to every visitor on any screen size.', 'short-stream-core' ); ?></span>
												</label>
											</fieldset>
										</td>
									</tr>
									<tr>
										<th><label><?php _e( 'Trigger Frequency', 'short-stream-core' ); ?></label></th>
										<td>
											<select name="short_splash_settings[frequency]" id="short-splash-freq-select" style="min-width:260px;">
												<option value="session" <?php selected( $splash_frequency, 'session' ); ?>><?php _e( 'Once per Browser Session (Default)', 'short-stream-core' ); ?></option>
												<option value="always" <?php selected( $splash_frequency, 'always' ); ?>><?php _e( 'Always on Every Page Launch', 'short-stream-core' ); ?></option>
												<option value="first_visit" <?php selected( $splash_frequency, 'first_visit' ); ?>><?php _e( 'First-Time Visitors Only (Once Ever)', 'short-stream-core' ); ?></option>
											</select>
											<p class="description"><?php _e( 'Controls how frequently visitors see the opening animation. You can also test anytime via URL: <code>?splash=1</code>.', 'short-stream-core' ); ?></p>
										</td>
									</tr>
								</table>
							</div>

							<!-- Card 2: Visual Presentation & Brand Customization -->
							<div class="short-settings-card" style="border-left:4px solid #3b82f6; margin-bottom:20px;">
								<div class="short-settings-card-header">
									<div>
										<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
											<span>🎨</span> <?php _e( 'Visual Style & Brand Presentation', 'short-stream-core' ); ?>
										</h3>
										<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
											<?php _e( 'Customize the animation type, custom logo image, monogram letter, brand typography, and slogans.', 'short-stream-core' ); ?>
										</p>
									</div>
								</div>

								<table class="form-table" style="margin-top:0;">
									<tr>
										<th style="width:180px;"><label><?php _e( 'Animation Presentation Mode', 'short-stream-core' ); ?></label></th>
										<td>
											<fieldset>
												<label style="display:block; margin-bottom:10px;">
													<input type="radio" name="short_splash_settings[mode]" value="ribbon_letter" <?php checked( $splash_mode, 'ribbon_letter' ); ?> class="splash-mode-radio" />
													<strong><?php _e( 'Cinematic Monogram Ribbon (Netflix Style — Recommended)', 'short-stream-core' ); ?></strong>
													<span class="description" style="display:block; margin-left:22px; color:#64748b;"><?php _e( 'Draws your brand initial with glowing light ribbon strokes, bursts into radial energy, and gracefully reveals your full logo & slogan.', 'short-stream-core' ); ?></span>
												</label>
												<label style="display:block; margin-bottom:10px;">
													<input type="radio" name="short_splash_settings[mode]" value="custom_logo" <?php checked( $splash_mode, 'custom_logo' ); ?> class="splash-mode-radio" />
													<strong><?php _e( 'Custom Splash Logo / App Icon', 'short-stream-core' ); ?></strong>
													<span class="description" style="display:block; margin-left:22px; color:#64748b;"><?php _e( 'Displays your custom uploaded brand logo or app icon with smooth glow zoom scaling and tagline fade-in.', 'short-stream-core' ); ?></span>
												</label>
												<label style="display:block;">
													<input type="radio" name="short_splash_settings[mode]" value="minimal_text" <?php checked( $splash_mode, 'minimal_text' ); ?> class="splash-mode-radio" />
													<strong><?php _e( 'Minimalist Brand Typography', 'short-stream-core' ); ?></strong>
													<span class="description" style="display:block; margin-left:22px; color:#64748b;"><?php _e( 'Clean, elegant centered typography reveal with glowing accent letter.', 'short-stream-core' ); ?></span>
												</label>
											</fieldset>
										</td>
									</tr>

									<!-- Monogram Letter Input -->
									<tr id="row-splash-letter" style="<?php echo ( 'custom_logo' === $splash_mode ) ? 'display:none;' : ''; ?>">
										<th><label><?php _e( 'Splash Monogram / Initial', 'short-stream-core' ); ?></label></th>
										<td>
											<input type="text" name="short_splash_settings[splash_letter]" id="input-splash-letter" value="<?php echo esc_attr( $splash_letter ); ?>" maxlength="4" style="width:80px; text-align:center; font-size:18px; font-weight:900; text-transform:uppercase;" />
											<p class="description"><?php _e( 'The 1–2 characters rendered in the glowing ribbon animation (defaults to first letter of brand name, e.g. "S").', 'short-stream-core' ); ?></p>
										</td>
									</tr>

									<!-- Custom Splash Logo Image -->
									<tr id="row-splash-logo" style="<?php echo ( 'custom_logo' !== $splash_mode ) ? 'display:none;' : ''; ?>">
										<th><label><?php _e( 'Custom Splash Logo Image', 'short-stream-core' ); ?></label></th>
										<td>
											<div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
												<input type="text" name="short_splash_settings[splash_logo_url]" id="input-splash-logo-url" value="<?php echo esc_attr( $splash_logo_url ); ?>" class="regular-text code" placeholder="https://... or upload from Media Library" />
												<button type="button" class="button button-primary" id="btn-upload-splash-logo" style="display:inline-flex; align-items:center; gap:5px;">
													<span class="dashicons dashicons-upload" style="font-size:15px; width:15px; height:15px; line-height:15px;"></span>
													<span><?php _e( 'Upload Logo', 'short-stream-core' ); ?></span>
												</button>
												<button type="button" class="button button-secondary" id="btn-clear-splash-logo" style="<?php echo empty( $splash_logo_url ) ? 'display:none;' : ''; ?>">
													<?php _e( 'Clear', 'short-stream-core' ); ?>
												</button>
											</div>
											<div id="splash-logo-preview-box" style="display:<?php echo empty( $splash_logo_url ) ? 'none' : 'inline-flex'; ?>; align-items:center; justify-content:center; padding:12px 18px; background:#0f172a; border-radius:8px; max-width:240px; border:1px solid rgba(255,255,255,0.1);">
												<img id="splash-logo-preview-img" src="<?php echo esc_url( $splash_logo_url ); ?>" alt="Splash Logo Preview" style="max-height:60px; max-width:180px; object-fit:contain;" />
											</div>
											<p class="description"><?php _e( 'Upload a high-resolution PNG or SVG logo image.', 'short-stream-core' ); ?></p>
										</td>
									</tr>

									<tr>
										<th><label><?php _e( 'Splash Brand Title', 'short-stream-core' ); ?></label></th>
										<td>
											<input type="text" name="short_splash_settings[splash_title]" id="input-splash-title" value="<?php echo esc_attr( $splash_brand_name ); ?>" class="regular-text" placeholder="ShortTV" />
											<p class="description"><?php _e( 'Title shown during the brand reveal screen.', 'short-stream-core' ); ?></p>
										</td>
									</tr>

									<tr>
										<th><label><?php _e( 'Splash Tagline / Slogan', 'short-stream-core' ); ?></label></th>
										<td>
											<input type="text" name="short_splash_settings[splash_tagline]" id="input-splash-tagline" value="<?php echo esc_attr( $splash_tagline ); ?>" class="large-text" placeholder="Unlimited Short Dramas, Mini-Series, and More" />
											<p class="description"><?php _e( 'Sub-headline slogan displayed below the brand name.', 'short-stream-core' ); ?></p>
										</td>
									</tr>
								</table>
							</div>

							<!-- Card 3: Colors, Theme & Timing -->
							<div class="short-settings-card" style="border-left:4px solid #10b981; margin-bottom:20px;">
								<div class="short-settings-card-header">
									<div>
										<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
											<span>🎨</span> <?php _e( 'Background, Accent Glow & Timing', 'short-stream-core' ); ?>
										</h3>
										<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
											<?php _e( 'Configure the background darkness, radiant ribbon glow color, and playback speed.', 'short-stream-core' ); ?>
										</p>
									</div>
								</div>

								<table class="form-table" style="margin-top:0;">
									<tr>
										<th style="width:180px;"><label><?php _e( 'Background Color', 'short-stream-core' ); ?></label></th>
										<td>
											<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[bg_color]" value="#000000" <?php checked( $splash_bg, '#000000' ); ?> class="splash-bg-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#000000; border:1px solid #94a3b8;"></span>
													<?php _e( 'OLED Pitch Black (#000000)', 'short-stream-core' ); ?>
												</label>
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[bg_color]" value="#121212" <?php checked( $splash_bg, '#121212' ); ?> class="splash-bg-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#121212; border:1px solid #94a3b8;"></span>
													<?php _e( 'Cinematic Dark (#121212)', 'short-stream-core' ); ?>
												</label>
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[bg_color]" value="#0b101b" <?php checked( $splash_bg, '#0b101b' ); ?> class="splash-bg-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#0b101b; border:1px solid #94a3b8;"></span>
													<?php _e( 'Midnight Navy (#0b101b)', 'short-stream-core' ); ?>
												</label>
											</div>
											<div style="display:flex; align-items:center; gap:8px;">
												<span style="font-size:12px; color:#64748b;"><?php _e( 'Custom Hex:', 'short-stream-core' ); ?></span>
												<input type="text" name="short_splash_settings[bg_color_custom]" id="input-splash-bg-custom" value="<?php echo esc_attr( $splash_bg ); ?>" style="width:110px;" placeholder="#000000" />
											</div>
										</td>
									</tr>

									<tr id="row-splash-accent" style="<?php echo ( 'custom_logo' === $splash_mode ) ? 'display:none;' : ''; ?>">
										<th><label><?php _e( 'Ribbon Glow & Accent Color', 'short-stream-core' ); ?></label></th>
										<td>
											<div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; background:#f1f5f9; padding:4px 10px; border-radius:6px; border:1px solid #cbd5e1; font-weight:600;">
													<input type="radio" name="short_splash_settings[accent_color]" value="auto" <?php checked( $splash_accent, 'auto' ); ?> class="splash-accent-preset" />
													<span>⚡ <?php _e( 'Auto (Active Theme Accent Color — Default)', 'short-stream-core' ); ?></span>
												</label>
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[accent_color]" value="#FFFFFF" <?php checked( $splash_accent, '#FFFFFF' ); ?> class="splash-accent-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#FFFFFF; border:1px solid #cbd5e1;"></span>
													<?php _e( 'Pure White (#FFFFFF)', 'short-stream-core' ); ?>
												</label>
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[accent_color]" value="#E50914" <?php checked( $splash_accent, '#E50914' ); ?> class="splash-accent-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#E50914;"></span>
													<?php _e( 'Netflix Red (#E50914)', 'short-stream-core' ); ?>
												</label>
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[accent_color]" value="#FF2D55" <?php checked( $splash_accent, '#FF2D55' ); ?> class="splash-accent-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#FF2D55;"></span>
													<?php _e( 'Neon Rose (#FF2D55)', 'short-stream-core' ); ?>
												</label>
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[accent_color]" value="#FFD700" <?php checked( $splash_accent, '#FFD700' ); ?> class="splash-accent-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#FFD700;"></span>
													<?php _e( 'Royal Gold (#FFD700)', 'short-stream-core' ); ?>
												</label>
												<label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
													<input type="radio" name="short_splash_settings[accent_color]" value="#00F0FF" <?php checked( $splash_accent, '#00F0FF' ); ?> class="splash-accent-preset" />
													<span style="display:inline-block; width:16px; height:16px; border-radius:50%; background:#00F0FF;"></span>
													<?php _e( 'Cyber Cyan (#00F0FF)', 'short-stream-core' ); ?>
												</label>
											</div>
											<div style="display:flex; align-items:center; gap:8px;">
												<span style="font-size:12px; color:#64748b;"><?php _e( 'Custom Hex:', 'short-stream-core' ); ?></span>
												<input type="text" name="short_splash_settings[accent_color_custom]" id="input-splash-accent-custom" value="<?php echo ( 'auto' !== $splash_accent ) ? esc_attr( $splash_accent ) : ''; ?>" style="width:110px;" placeholder="e.g. #FF2D55" />
												<span style="font-size:11px; color:#94a3b8;"><?php _e( '(Leave blank or choose Auto to match site theme)', 'short-stream-core' ); ?></span>
											</div>
										</td>
									</tr>

									<tr id="row-splash-accent-first-letter" style="<?php echo ( 'custom_logo' === $splash_mode ) ? 'display:none;' : ''; ?>">
										<th><label><?php _e( 'Title First Letter Accent', 'short-stream-core' ); ?></label></th>
										<td>
											<label style="font-weight:600; cursor:pointer;">
												<input type="checkbox" name="short_splash_settings[accent_first_letter]" id="input-splash-accent-first-letter" value="1" <?php checked( $splash_accent_first_letter, true ); ?> />
												<?php _e( 'Colorize the first letter in the brand title with accent color (leave unchecked for clean solid white title)', 'short-stream-core' ); ?>
											</label>
										</td>
									</tr>

									<tr>
										<th><label><?php _e( 'Animation Speed', 'short-stream-core' ); ?></label></th>
										<td>
											<select name="short_splash_settings[speed]" id="short-splash-speed-select" style="min-width:220px;">
												<option value="fast" <?php selected( $splash_speed, 'fast' ); ?>><?php _e( 'Fast (1.6 seconds)', 'short-stream-core' ); ?></option>
												<option value="normal" <?php selected( $splash_speed, 'normal' ); ?>><?php _e( 'Normal (2.4 seconds — Default)', 'short-stream-core' ); ?></option>
												<option value="cinematic" <?php selected( $splash_speed, 'cinematic' ); ?>><?php _e( 'Cinematic (3.2 seconds)', 'short-stream-core' ); ?></option>
											</select>
										</td>
									</tr>

									<tr>
										<th><label><?php _e( 'Skip Prompt Hint', 'short-stream-core' ); ?></label></th>
										<td>
											<label style="font-weight:600; cursor:pointer;">
												<input type="checkbox" name="short_splash_settings[show_skip_hint]" value="1" <?php checked( $splash_show_skip, '1' ); ?> />
												<?php _e( 'Show "Tap to skip" prompt at bottom of screen', 'short-stream-core' ); ?>
											</label>
										</td>
									</tr>
								</table>
							</div>

							<?php submit_button( __( 'Save Splash Screen Settings', 'short-stream-core' ) ); ?>
						</div>

						<!-- Right Column: Live Interactive Phone Mockup Preview -->
						<div style="flex:0 0 320px; position:sticky; top:52px; z-index:90; box-sizing:border-box; margin-bottom:20px;">
							<div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.06); text-align:center; box-sizing:border-box;">
								<h4 style="margin:0 0 6px; font-size:15px; color:#0f172a; display:flex; align-items:center; justify-content:center; gap:6px;">
									<span>📱</span> <?php _e( 'Live Splash Simulator', 'short-stream-core' ); ?>
								</h4>
								<p style="margin:0 0 14px; font-size:12px; color:#64748b;">
									<?php _e( 'Real-time preview of your mobile launch experience.', 'short-stream-core' ); ?>
								</p>

								<!-- Phone Device Shell -->
								<div id="sim-phone-shell" style="width:260px; height:480px; margin:0 auto; background:<?php echo esc_attr( $splash_bg ); ?>; border-radius:36px; border:10px solid #1e293b; box-shadow:0 16px 40px rgba(0,0,0,0.4); position:relative; overflow:hidden; display:flex; flex-direction:column; align-items:center; justify-content:center;">
									
									<!-- Phone Notch -->
									<div style="position:absolute; top:8px; left:50%; transform:translateX(-50%); width:70px; height:14px; background:#0f172a; border-radius:999px; z-index:10;"></div>

									<!-- Simulator Stage -->
									<div id="sim-stage" style="width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; position:relative; padding:20px; box-sizing:border-box;">
										
										<!-- Ribbon Letter Stage (Netflix Monogram Ribbon) -->
										<div id="sim-letter-stage" style="position:relative; width:90px; height:100px; display:<?php echo ( 'ribbon_letter' === $splash_mode || empty( $splash_mode ) ) ? 'flex' : 'none'; ?>; align-items:center; justify-content:center;">
											<span id="sim-monogram-text" style="font-size:74px; font-weight:900; color:<?php echo esc_attr( $splash_display_accent ); ?>; line-height:1; font-family:-apple-system,BlinkMacSystemFont,sans-serif; text-shadow:0 0 8px <?php echo esc_attr( $splash_display_accent ); ?>66;"><?php echo esc_html( $splash_letter ); ?></span>
										</div>

										<!-- Custom Logo Stage -->
										<div id="sim-logo-stage" style="position:relative; width:140px; height:80px; display:<?php echo ( 'custom_logo' === $splash_mode && ! empty( $splash_logo_url ) ) ? 'flex' : 'none'; ?>; align-items:center; justify-content:center;">
											<img id="sim-logo-img" src="<?php echo esc_url( $splash_logo_url ); ?>" alt="Logo" style="max-height:65px; max-width:130px; object-fit:contain;" />
										</div>

										<!-- Brand Reveal Stage -->
										<div id="sim-reveal-stage" style="margin-top:16px; text-align:center;">
											<div id="sim-brand-title" style="font-size:20px; font-weight:800; color:#ffffff; letter-spacing:-0.3px;">
												<span id="sim-title-accent" style="color:<?php echo esc_attr( $splash_display_accent ); ?>; display:<?php echo $splash_accent_first_letter ? 'inline' : 'none'; ?>;"><?php echo esc_html( $splash_letter ); ?></span><span id="sim-title-rest" style="display:<?php echo $splash_accent_first_letter ? 'inline' : 'none'; ?>;"><?php echo esc_html( mb_substr( $splash_brand_name, mb_strlen( $splash_letter ) ) ); ?></span><span id="sim-title-full" style="display:<?php echo ! $splash_accent_first_letter ? 'inline' : 'none'; ?>;"><?php echo esc_html( $splash_brand_name ); ?></span>
											</div>
											<div id="sim-brand-tagline" style="font-size:9.5px; color:#94a3b8; margin-top:4px; max-width:200px; line-height:1.3;">
												<?php echo esc_html( $splash_tagline ); ?>
											</div>
										</div>

										<!-- Skip Hint -->
										<div id="sim-skip-hint" style="position:absolute; bottom:20px; font-size:9px; color:rgba(255,255,255,0.4); text-transform:uppercase; letter-spacing:0.8px; display:<?php echo ( '1' === $splash_show_skip ) ? 'block' : 'none'; ?>;">
											<?php _e( 'Tap to skip', 'short-stream-core' ); ?>
										</div>
									</div>
								</div>

								<div style="margin-top:16px;">
									<button type="button" class="button button-primary button-large" id="btn-play-sim-animation" style="width:100%; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:700;">
										<span>▶</span> <?php _e( 'Preview Splash Animation', 'short-stream-core' ); ?>
									</button>
								</div>
							</div>
						</div>

					</div>
				</form>

				<script>
				jQuery(document).ready(function($){
					// Media Uploader for Splash Logo
					var splashMediaFrame;
					$('#btn-upload-splash-logo').on('click', function(e){
						e.preventDefault();
						if(splashMediaFrame){
							splashMediaFrame.open();
							return;
						}
						splashMediaFrame = wp.media({
							title: '<?php echo esc_js( __( 'Select or Upload Splash Logo', 'short-stream-core' ) ); ?>',
							button: { text: '<?php echo esc_js( __( 'Use as Splash Logo', 'short-stream-core' ) ); ?>' },
							multiple: false,
							library: { type: 'image' }
						});
						splashMediaFrame.on('select', function(){
							var attachment = splashMediaFrame.state().get('selection').first().toJSON();
							$('#input-splash-logo-url').val(attachment.url);
							$('#splash-logo-preview-img').attr('src', attachment.url);
							$('#splash-logo-preview-box').css('display', 'inline-flex');
							$('#btn-clear-splash-logo').show();
							$('#sim-logo-img').attr('src', attachment.url);
							if($('input.splash-mode-radio:checked').val() === 'custom_logo'){
								$('#sim-logo-stage').css('display', 'flex');
							}
						});
						splashMediaFrame.open();
					});

					$('#btn-clear-splash-logo').on('click', function(e){
						e.preventDefault();
						$('#input-splash-logo-url').val('');
						$('#splash-logo-preview-box').hide();
						$(this).hide();
						$('#sim-logo-stage').hide();
					});

					// Mode radio switcher
					$('.splash-mode-radio').on('change', function(){
						var mode = $(this).val();
						if(mode === 'custom_logo'){
							$('#row-splash-logo').show();
							$('#row-splash-letter').hide();
							$('#row-splash-accent').hide();
							$('#row-splash-accent-first-letter').hide();
							$('#sim-letter-stage').hide();
							if($('#input-splash-logo-url').val()){
								$('#sim-logo-stage').css('display', 'flex');
							} else {
								$('#sim-logo-stage').hide();
							}
							$('#sim-reveal-stage').css('margin-top', '12px');
						} else if(mode === 'minimal_text'){
							$('#row-splash-logo').hide();
							$('#row-splash-letter').show();
							$('#row-splash-accent').show();
							$('#row-splash-accent-first-letter').show();
							$('#sim-letter-stage').hide();
							$('#sim-logo-stage').hide();
							$('#sim-reveal-stage').css('margin-top', '0');
						} else {
							// ribbon_letter
							$('#row-splash-logo').hide();
							$('#row-splash-letter').show();
							$('#row-splash-accent').show();
							$('#row-splash-accent-first-letter').show();
							$('#sim-letter-stage').css('display', 'flex');
							$('#sim-logo-stage').hide();
							$('#sim-reveal-stage').css('margin-top', '16px');
						}
					});

					function updateSimTitle() {
						var t = $('#input-splash-title').val() || 'ShortTV';
						var l = $('#input-splash-letter').val().toUpperCase() || 'S';
						var isAccent = $('#input-splash-accent-first-letter').is(':checked');
						$('#sim-title-full').text(t);
						$('#sim-title-accent').text(l);
						$('#sim-title-rest').text(t.slice(l.length));
						if (isAccent) {
							$('#sim-title-accent').show();
							$('#sim-title-rest').show();
							$('#sim-title-full').hide();
						} else {
							$('#sim-title-accent').hide();
							$('#sim-title-rest').hide();
							$('#sim-title-full').show();
						}
					}

					$('#input-splash-letter').on('input', function(){
						var l = $(this).val().toUpperCase() || 'S';
						$('#sim-monogram-text').text(l);
						updateSimTitle();
					});

					$('#input-splash-title').on('input', updateSimTitle);
					$('#input-splash-accent-first-letter').on('change', updateSimTitle);

					$('#input-splash-tagline').on('input', function(){
						$('#sim-brand-tagline').text($(this).val());
					});

					// Background Color selector
					$('.splash-bg-preset').on('change', function(){
						var col = $(this).val();
						$('#input-splash-bg-custom').val(col);
						$('#sim-phone-shell').css('background', col);
					});
					$('#input-splash-bg-custom').on('input', function(){
						var col = $(this).val();
						$('#sim-phone-shell').css('background', col);
					});

					function getSimAccentColor() {
						var custom = $('#input-splash-accent-custom').val().trim();
						if (custom) return custom;
						var preset = $('.splash-accent-preset:checked').val();
						if (preset && preset !== 'auto') return preset;
						return '#FF2D55';
					}

					function applySimAccent() {
						var col = getSimAccentColor();
						$('#sim-monogram-text').css({ 'color': col, 'text-shadow': '0 0 8px ' + col + '66' });
						$('#sim-title-accent').css('color', col);
					}

					// Accent Color selector
					$('.splash-accent-preset').on('change', function(){
						var col = $(this).val();
						if(col === 'auto') {
							$('#input-splash-accent-custom').val('');
						} else {
							$('#input-splash-accent-custom').val(col);
						}
						applySimAccent();
					});
					$('#input-splash-accent-custom').on('input', function(){
						var col = $(this).val().trim();
						if (!col) {
							$('input.splash-accent-preset[value="auto"]').prop('checked', true);
						}
						applySimAccent();
					});

					// Animation Player Simulator with Dynamic Speed Scaling
					var simAnimTimeout1, simAnimTimeout2, simAnimTimeout3;
					function playSplashSimulator() {
						clearTimeout(simAnimTimeout1);
						clearTimeout(simAnimTimeout2);
						clearTimeout(simAnimTimeout3);

						var mode = $('input.splash-mode-radio:checked').val() || 'ribbon_letter';
						var speed = $('#short-splash-speed-select').val() || 'normal';
						var stage = $('#sim-stage');
						var letterStage = $('#sim-letter-stage');
						var revealStage = $('#sim-reveal-stage');
						var logoStage = $('#sim-logo-stage');
						var $btn = $('#btn-play-sim-animation');

						// Dynamic speed multiplier
						var speedMult = 1.0;
						var speedSec = '2.4s';
						if (speed === 'fast') {
							speedMult = 0.65;
							speedSec = '1.6s';
						} else if (speed === 'cinematic') {
							speedMult = 1.35;
							speedSec = '3.2s';
						}

						var origBtnText = '<span>▶</span> <?php echo esc_js( __( 'Preview Splash Animation', 'short-stream-core' ) ); ?>';
						$btn.html('<span>⚡</span> <?php echo esc_js( __( 'Simulating', 'short-stream-core' ) ); ?> (' + speedSec + ')...').prop('disabled', true);

						stage.css('opacity', '0');
						if (mode === 'ribbon_letter') {
							letterStage.css({ 'display': 'flex', 'opacity': '1', 'transform': 'scale(0.8)' });
							revealStage.css({ 'opacity': '0', 'transform': 'translateY(15px)' });
							logoStage.hide();
							stage.css('opacity', '1');

							// Phase 1: Ribbon Monogram zoom
							simAnimTimeout1 = setTimeout(function(){
								letterStage.css({ 'transition': 'transform ' + (0.6 * speedMult).toFixed(2) + 's cubic-bezier(0.34, 1.56, 0.64, 1), opacity ' + (0.4 * speedMult).toFixed(2) + 's ease', 'transform': 'scale(1.15)', 'opacity': '1' });
								
								// Phase 2: Reveal full brand
								simAnimTimeout2 = setTimeout(function(){
									letterStage.css({ 'transition': 'opacity ' + (0.3 * speedMult).toFixed(2) + 's ease, transform ' + (0.3 * speedMult).toFixed(2) + 's ease', 'opacity': '0', 'transform': 'scale(0.9)' });
									
									simAnimTimeout3 = setTimeout(function(){
										letterStage.hide();
										revealStage.css({ 'transition': 'opacity ' + (0.5 * speedMult).toFixed(2) + 's ease, transform ' + (0.5 * speedMult).toFixed(2) + 's ease', 'opacity': '1', 'transform': 'translateY(0)' });
										$btn.html(origBtnText).prop('disabled', false);
									}, Math.round(200 * speedMult));
								}, Math.round(700 * speedMult));
							}, 80);
						} else if (mode === 'minimal_text') {
							letterStage.hide();
							logoStage.hide();
							revealStage.css({ 'opacity': '0', 'transform': 'scale(0.92)' });
							stage.css('opacity', '1');
							simAnimTimeout1 = setTimeout(function(){
								revealStage.css({ 'transition': 'opacity ' + (0.6 * speedMult).toFixed(2) + 's ease, transform ' + (0.6 * speedMult).toFixed(2) + 's cubic-bezier(0.16, 1, 0.3, 1)', 'opacity': '1', 'transform': 'scale(1)' });
								setTimeout(function(){
									$btn.html(origBtnText).prop('disabled', false);
								}, Math.round(700 * speedMult));
							}, 100);
						} else {
							// custom_logo
							letterStage.hide();
							if($('#input-splash-logo-url').val()){
								logoStage.css({ 'display': 'flex', 'opacity': '0', 'transform': 'scale(0.8)' });
							}
							revealStage.css({ 'opacity': '0', 'transform': 'translateY(10px)' });
							stage.css('opacity', '1');
							simAnimTimeout1 = setTimeout(function(){
								logoStage.css({ 'transition': 'opacity ' + (0.5 * speedMult).toFixed(2) + 's ease, transform ' + (0.5 * speedMult).toFixed(2) + 's cubic-bezier(0.34, 1.56, 0.64, 1)', 'opacity': '1', 'transform': 'scale(1)' });
								simAnimTimeout2 = setTimeout(function(){
									revealStage.css({ 'transition': 'opacity ' + (0.4 * speedMult).toFixed(2) + 's ease, transform ' + (0.4 * speedMult).toFixed(2) + 's ease', 'opacity': '1', 'transform': 'translateY(0)' });
									setTimeout(function(){
										$btn.html(origBtnText).prop('disabled', false);
									}, Math.round(500 * speedMult));
								}, Math.round(400 * speedMult));
							}, 100);
						}
					}

					$('#btn-play-sim-animation').on('click', function(e){
						e.preventDefault();
						playSplashSimulator();
					});

					$('#short-splash-speed-select').on('change', function(){
						playSplashSimulator();
					});
				});
				</script>
			<?php elseif ( 'navigation' === $tab ) : 
				$header_items = get_option( 'short_header_nav_items', null );
				if ( null === $header_items || ! is_array( $header_items ) ) {
					$header_items = array(
						array( 'title' => 'Home', 'url' => '/', 'enabled' => 1 ),
						array( 'title' => 'Romance', 'url' => '/genre/romance/', 'enabled' => 1 ),
						array( 'title' => 'Fantasy', 'url' => '/genre/fantasy/', 'enabled' => 1 ),
						array( 'title' => 'Vampire', 'url' => '/genre/vampire/', 'enabled' => 1 ),
					);
				}

				$bottom_items = get_option( 'short_bottom_nav_sections', null );
				if ( null === $bottom_items || ! is_array( $bottom_items ) ) {
					$bottom_items = array(
						array( 'title' => 'Home', 'url' => '/', 'type' => 'home', 'enabled' => 1 ),
						array( 'title' => 'Latest', 'url' => '/short-tv/', 'type' => 'latest', 'enabled' => 1 ),
						array( 'title' => 'Popular', 'url' => '/new-popular/', 'type' => 'popular', 'enabled' => 1 ),
						array( 'title' => 'Account', 'url' => '/account/', 'type' => 'account', 'enabled' => 1 ),
					);
				}
			?>
				<form method="post" action="options.php" id="short-nav-form">
					<?php settings_fields( 'short_nav_group' ); ?>
					
					<!-- 1. Desktop Header Navigation -->
					<div class="short-settings-card" style="border-left:4px solid #e11d48; margin-top:20px;">
						<div class="short-settings-card-header">
							<div>
								<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🖥️</span> <?php _e( 'Desktop Header Navigation Links', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
									<?php _e( 'Customize top menu links next to the logo. Direct visitors to specific genres, custom pages, or categories.', 'short-stream-core' ); ?>
								</p>
							</div>
							<div style="display:flex; gap:8px;">
								<button type="button" class="button button-secondary" id="btn-preset-genres" style="font-size:12px;">
									⚡ <?php _e( 'Preset: Genres (Romance, Fantasy, Vampire)', 'short-stream-core' ); ?>
								</button>
								<button type="button" class="button button-secondary" id="btn-preset-reelshort" style="font-size:12px;">
									🎬 <?php _e( 'Preset: ReelShort (Categories, New & Popular, Brand)', 'short-stream-core' ); ?>
								</button>
							</div>
						</div>

						<table class="wp-list-table widefat fixed striped" id="table-header-nav" style="margin-top:10px;">
							<thead>
								<tr>
									<th style="width:35px; text-align:center;">#</th>
									<th style="width:200px;"><?php _e( 'Menu Title / Label', 'short-stream-core' ); ?></th>
									<th><?php _e( 'Destination URL / Genre Link', 'short-stream-core' ); ?></th>
									<th style="width:90px; text-align:center;"><?php _e( 'Visible', 'short-stream-core' ); ?></th>
									<th style="width:70px; text-align:center;"><?php _e( 'Action', 'short-stream-core' ); ?></th>
								</tr>
							</thead>
							<tbody id="header-nav-tbody">
								<?php foreach ( $header_items as $index => $item ) : ?>
								<tr class="header-nav-row">
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;" class="row-num"><?php echo $index + 1; ?></td>
									<td>
										<input type="text" name="short_header_nav_items[<?php echo $index; ?>][title]" value="<?php echo esc_attr( $item['title'] ?? '' ); ?>" class="regular-text" style="width:100%; font-weight:600;" placeholder="e.g. Romance" required />
									</td>
									<td>
										<input type="text" name="short_header_nav_items[<?php echo $index; ?>][url]" value="<?php echo esc_attr( $item['url'] ?? '' ); ?>" class="regular-text code" style="width:100%;" placeholder="e.g. /genre/romance/ or /new-popular/" required />
									</td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_header_nav_items[<?php echo $index; ?>][enabled]" value="0" />
										<input type="checkbox" name="short_header_nav_items[<?php echo $index; ?>][enabled]" value="1" <?php checked( ! empty( $item['enabled'] ) || ! isset( $item['enabled'] ) ); ?> />
									</td>
									<td style="text-align:center; vertical-align:middle;">
										<button type="button" class="button button-small button-link-delete btn-remove-row" style="color:#ef4444;" title="Remove">✕</button>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
							<button type="button" class="button button-primary" id="btn-add-header-item" style="display:inline-flex; align-items:center; gap:5px;">
								<span class="dashicons dashicons-plus-alt2" style="font-size:15px; width:15px; height:15px; line-height:15px;"></span>
								<span><?php _e( 'Add Header Nav Link', 'short-stream-core' ); ?></span>
							</button>
							<span style="font-size:12px; color:#64748b;"><?php _e( 'Tip: Use relative paths like <code>/genre/romance/</code> or full URLs.', 'short-stream-core' ); ?></span>
						</div>
					</div>

					<!-- 2. Mobile Top Header Bar Elements -->
					<?php
					$nav_mh_cfg = get_option( 'short_mobile_header_config', array(
						'show_logo'    => 1,
						'show_search'  => 1,
						'show_lang'    => 1,
						'show_coins'   => 1,
						'show_mylist'  => 1,
						'show_history' => 0,
					) );
					$n_show_logo    = ! isset( $nav_mh_cfg['show_logo'] ) || ! empty( $nav_mh_cfg['show_logo'] );
					$n_show_search  = ! isset( $nav_mh_cfg['show_search'] ) || ! empty( $nav_mh_cfg['show_search'] );
					$n_show_lang    = ! isset( $nav_mh_cfg['show_lang'] ) || ! empty( $nav_mh_cfg['show_lang'] );
					$n_show_coins   = ! isset( $nav_mh_cfg['show_coins'] ) || ! empty( $nav_mh_cfg['show_coins'] );
					$n_show_mylist  = ! empty( $nav_mh_cfg['show_mylist'] );
					$n_show_history = ! empty( $nav_mh_cfg['show_history'] );
					?>
					<div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #8b5cf6; border-radius:8px; padding:20px 24px; margin-top:24px; box-shadow:0 1px 4px rgba(0,0,0,0.05); max-width:980px;">
						<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
							<div>
								<h3 style="margin:0 0 4px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>📱</span> <?php _e( 'Mobile Top Header Bar Elements', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:0; color:#64748b; font-size:13px;">
									<?php _e( 'The compact 48px top bar on smartphones. Toggle which elements appear next to the brand logo.', 'short-stream-core' ); ?>
								</p>
							</div>
							<div>
								<a href="<?php echo admin_url( 'admin.php?page=short-homepage&target=mobile_header' ); ?>" class="button button-secondary" style="font-size:12px; color:#8b5cf6; border-color:#ddd6fe;">
									⚡ <?php _e( 'Open Visual Mobile Header Studio ↗', 'short-stream-core' ); ?>
								</a>
							</div>
						</div>

						<table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
							<thead>
								<tr>
									<th style="width:35px; text-align:center;">#</th>
									<th style="width:200px;"><?php _e( 'Header Element', 'short-stream-core' ); ?></th>
									<th><?php _e( 'Description & Destination', 'short-stream-core' ); ?></th>
									<th style="width:90px; text-align:center;"><?php _e( 'Visible', 'short-stream-core' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;">1</td>
									<td style="vertical-align:middle; font-weight:700;"><span class="dashicons dashicons-format-image" style="color:#8b5cf6; margin-right:4px;"></span> <?php _e( 'Brand Logo', 'short-stream-core' ); ?></td>
									<td style="vertical-align:middle; color:#64748b;"><?php _e( 'Displays brand logo image on the left. Size managed in Branding Settings.', 'short-stream-core' ); ?></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_mobile_header_config[show_logo]" value="0" />
										<input type="checkbox" name="short_mobile_header_config[show_logo]" value="1" <?php checked( $n_show_logo ); ?> />
									</td>
								</tr>
								<tr>
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;">2</td>
									<td style="vertical-align:middle; font-weight:700;"><span class="dashicons dashicons-search" style="color:#8b5cf6; margin-right:4px;"></span> <?php _e( 'Search Button', 'short-stream-core' ); ?></td>
									<td style="vertical-align:middle; color:#64748b;"><?php _e( 'Opens instant mobile search drawer overlay for dramas and episodes.', 'short-stream-core' ); ?></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_mobile_header_config[show_search]" value="0" />
										<input type="checkbox" name="short_mobile_header_config[show_search]" value="1" <?php checked( $n_show_search ); ?> />
									</td>
								</tr>
								<tr>
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;">3</td>
									<td style="vertical-align:middle; font-weight:700;"><span class="dashicons dashicons-admin-site-alt3" style="color:#8b5cf6; margin-right:4px;"></span> <?php _e( 'Language Selector', 'short-stream-core' ); ?></td>
									<td style="vertical-align:middle; color:#64748b;"><?php _e( 'Interactive globe dropdown for instantaneous multi-language switching.', 'short-stream-core' ); ?></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_mobile_header_config[show_lang]" value="0" />
										<input type="checkbox" name="short_mobile_header_config[show_lang]" value="1" <?php checked( $n_show_lang ); ?> />
									</td>
								</tr>
								<tr>
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;">4</td>
									<td style="vertical-align:middle; font-weight:700;"><span>💰</span> <?php _e( 'Coins Wallet Pill', 'short-stream-core' ); ?></td>
									<td style="vertical-align:middle; color:#64748b;"><?php _e( 'Live drama coins counter badge linking to /subscription/?tab=coins.', 'short-stream-core' ); ?></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_mobile_header_config[show_coins]" value="0" />
										<input type="checkbox" name="short_mobile_header_config[show_coins]" value="1" <?php checked( $n_show_coins ); ?> />
									</td>
								</tr>
								<tr style="background:#eff6ff;">
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;">5</td>
									<td style="vertical-align:middle; font-weight:700; color:#1e40af;"><span class="dashicons dashicons-bookmark" style="color:#2563eb; margin-right:4px;"></span> <?php _e( 'My List Bookmark', 'short-stream-core' ); ?></td>
									<td style="vertical-align:middle; color:#1e3a8a;"><?php _e( 'Direct bookmark icon button in mobile header linking to /my-list/.', 'short-stream-core' ); ?></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_mobile_header_config[show_mylist]" value="0" />
										<input type="checkbox" name="short_mobile_header_config[show_mylist]" value="1" <?php checked( $n_show_mylist ); ?> />
									</td>
								</tr>
								<tr>
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;">6</td>
									<td style="vertical-align:middle; font-weight:700;"><span class="dashicons dashicons-backup" style="color:#8b5cf6; margin-right:4px;"></span> <?php _e( 'Watch History', 'short-stream-core' ); ?></td>
									<td style="vertical-align:middle; color:#64748b;"><?php _e( 'Quick icon button linking directly to watched episodes history (/history/).', 'short-stream-core' ); ?></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_mobile_header_config[show_history]" value="0" />
										<input type="checkbox" name="short_mobile_header_config[show_history]" value="1" <?php checked( $n_show_history ); ?> />
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- 3. Mobile Bottom Navigation -->
					<div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #3b82f6; border-radius:8px; padding:20px 24px; margin-top:24px; box-shadow:0 1px 4px rgba(0,0,0,0.05); max-width:980px;">
						<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
							<div>
								<h3 style="margin:0 0 4px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>📱</span> <?php _e( 'Mobile Bottom Navigation Bar', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:0; color:#64748b; font-size:13px;">
									<?php _e( 'Customize tab labels, URLs, and icons displayed in the fixed mobile bottom bar (e.g. Home, Latest, Popular, Account).', 'short-stream-core' ); ?>
								</p>
							</div>
							<div style="display:flex; gap:8px;">
								<button type="button" class="button button-secondary" id="btn-preset-mobile-custom" style="font-size:12px;">
									⚡ <?php _e( 'Preset: Home, Latest, Popular, Account', 'short-stream-core' ); ?>
								</button>
								<button type="button" class="button button-secondary" id="btn-preset-mobile-default" style="font-size:12px;">
									📺 <?php _e( 'Preset: Home, Short TV, Popular, Account', 'short-stream-core' ); ?>
								</button>
							</div>
						</div>

						<table class="wp-list-table widefat fixed striped" id="table-bottom-nav" style="margin-top:10px;">
							<thead>
								<tr>
									<th style="width:35px; text-align:center;">#</th>
									<th style="width:160px;"><?php _e( 'Tab Label', 'short-stream-core' ); ?></th>
									<th style="width:170px;"><?php _e( 'Icon Type', 'short-stream-core' ); ?></th>
									<th><?php _e( 'Destination URL / Target', 'short-stream-core' ); ?></th>
									<th style="width:90px; text-align:center;"><?php _e( 'Visible', 'short-stream-core' ); ?></th>
									<th style="width:70px; text-align:center;"><?php _e( 'Action', 'short-stream-core' ); ?></th>
								</tr>
							</thead>
							<tbody id="bottom-nav-tbody">
								<?php 
								$icon_options = array(
									'home'          => '🏠 Home Icon',
									'latest'        => '⚡ Latest / Lightning Icon',
									'shorttv'       => '📺 TV / Screen Icon',
									'popular'       => '⭐ Popular / Star Icon',
									'new-popular'   => '⭐ Star Polygon Icon',
									'series'        => '🎬 Series Icon',
									'movies'        => '🎞️ Movies Icon',
									'search'        => '🔍 Search Icon',
									'notifications' => '🔔 Notifications Icon',
									'my-list'       => '➕ My List Icon',
									'account'       => '👤 Account / Avatar Icon',
								);
								foreach ( $bottom_items as $b_idx => $b_item ) : 
									$b_type = $b_item['type'] ?? 'home';
								?>
								<tr class="bottom-nav-row">
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;" class="b-row-num"><?php echo $b_idx + 1; ?></td>
									<td>
										<input type="text" name="short_bottom_nav_sections[<?php echo $b_idx; ?>][title]" value="<?php echo esc_attr( $b_item['title'] ?? '' ); ?>" class="regular-text" style="width:100%; font-weight:600;" placeholder="e.g. Latest" required />
									</td>
									<td>
										<select name="short_bottom_nav_sections[<?php echo $b_idx; ?>][type]" class="b-type-select" style="width:100%;">
											<?php foreach ( $icon_options as $opt_key => $opt_label ) : ?>
												<option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( $b_type, $opt_key ); ?>><?php echo esc_html( $opt_label ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td>
										<input type="text" name="short_bottom_nav_sections[<?php echo $b_idx; ?>][url]" value="<?php echo esc_attr( $b_item['url'] ?? '' ); ?>" class="regular-text code b-url-input" style="width:100%;" placeholder="e.g. /short-tv/ or /account/" required />
									</td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_bottom_nav_sections[<?php echo $b_idx; ?>][enabled]" value="0" />
										<input type="checkbox" name="short_bottom_nav_sections[<?php echo $b_idx; ?>][enabled]" value="1" <?php checked( ! empty( $b_item['enabled'] ) || ! isset( $b_item['enabled'] ) ); ?> />
									</td>
									<td style="text-align:center; vertical-align:middle;">
										<button type="button" class="button button-small button-link-delete btn-remove-b-row" style="color:#ef4444;" title="Remove">✕</button>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
							<button type="button" class="button button-primary" id="btn-add-bottom-item" style="display:inline-flex; align-items:center; gap:5px;">
								<span class="dashicons dashicons-plus-alt2" style="font-size:15px; width:15px; height:15px; line-height:15px;"></span>
								<span><?php _e( 'Add Mobile Bottom Nav Item', 'short-stream-core' ); ?></span>
							</button>
							<span style="font-size:12px; color:#64748b;"><?php _e( 'Account tab automatically shows user avatar when signed in.', 'short-stream-core' ); ?></span>
						</div>
					</div>

					<div style="margin-top:24px;">
						<?php submit_button( __( 'Save Navigation & Menus Settings', 'short-stream-core' ) ); ?>
					</div>
				</form>

				<script>
				jQuery(document).ready(function($){
					// Header Repeater Add
					$('#btn-add-header-item').on('click', function(e){
						e.preventDefault();
						var rowCount = $('#header-nav-tbody tr').length;
						var newRow = `
							<tr class="header-nav-row">
								<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;" class="row-num">${rowCount + 1}</td>
								<td><input type="text" name="short_header_nav_items[${rowCount}][title]" value="" class="regular-text" style="width:100%; font-weight:600;" placeholder="e.g. Vampire" required /></td>
								<td><input type="text" name="short_header_nav_items[${rowCount}][url]" value="" class="regular-text code" style="width:100%;" placeholder="e.g. /genre/vampire/" required /></td>
								<td style="text-align:center; vertical-align:middle;">
									<input type="hidden" name="short_header_nav_items[${rowCount}][enabled]" value="0" />
									<input type="checkbox" name="short_header_nav_items[${rowCount}][enabled]" value="1" checked />
								</td>
								<td style="text-align:center; vertical-align:middle;">
									<button type="button" class="button button-small button-link-delete btn-remove-row" style="color:#ef4444;" title="Remove">✕</button>
								</td>
							</tr>
						`;
						$('#header-nav-tbody').append(newRow);
					});

					// Header Repeater Remove
					$(document).on('click', '.btn-remove-row', function(e){
						e.preventDefault();
						if($('#header-nav-tbody tr').length > 1){
							$(this).closest('tr').remove();
							reindexHeaderRows();
						} else {
							alert('<?php echo esc_js( __( 'You must have at least one navigation item.', 'short-stream-core' ) ); ?>');
						}
					});

					function reindexHeaderRows(){
						$('#header-nav-tbody tr').each(function(idx){
							$(this).find('.row-num').text(idx + 1);
							$(this).find('input[name*="[title]"]').attr('name', `short_header_nav_items[${idx}][title]`);
							$(this).find('input[name*="[url]"]').attr('name', `short_header_nav_items[${idx}][url]`);
							$(this).find('input[type="hidden"][name*="[enabled]"]').attr('name', `short_header_nav_items[${idx}][enabled]`);
							$(this).find('input[type="checkbox"][name*="[enabled]"]').attr('name', `short_header_nav_items[${idx}][enabled]`);
						});
					}

					// Preset: Genres (Romance, Fantasy, Vampire)
					$('#btn-preset-genres').on('click', function(e){
						e.preventDefault();
						var items = [
							{ title: 'Home', url: '/' },
							{ title: 'Romance', url: '/genre/romance/' },
							{ title: 'Fantasy', url: '/genre/fantasy/' },
							{ title: 'Vampire', url: '/genre/vampire/' }
						];
						fillHeaderItems(items);
					});

					// Preset: ReelShort (Categories, New & Popular, Brand)
					$('#btn-preset-reelshort').on('click', function(e){
						e.preventDefault();
						var items = [
							{ title: 'Home', url: '/' },
							{ title: 'Categories', url: '/genre/' },
							{ title: 'New & Popular', url: '/new-popular/' },
							{ title: 'Brand', url: '/subscription/' }
						];
						fillHeaderItems(items);
					});

					function fillHeaderItems(items){
						$('#header-nav-tbody').empty();
						items.forEach(function(item, idx){
							var row = `
								<tr class="header-nav-row">
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;" class="row-num">${idx + 1}</td>
									<td><input type="text" name="short_header_nav_items[${idx}][title]" value="${item.title}" class="regular-text" style="width:100%; font-weight:600;" required /></td>
									<td><input type="text" name="short_header_nav_items[${idx}][url]" value="${item.url}" class="regular-text code" style="width:100%;" required /></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_header_nav_items[${idx}][enabled]" value="0" />
										<input type="checkbox" name="short_header_nav_items[${idx}][enabled]" value="1" checked />
									</td>
									<td style="text-align:center; vertical-align:middle;">
										<button type="button" class="button button-small button-link-delete btn-remove-row" style="color:#ef4444;" title="Remove">✕</button>
									</td>
								</tr>
							`;
							$('#header-nav-tbody').append(row);
						});
					}

					// Bottom Nav Repeater Add
					$('#btn-add-bottom-item').on('click', function(e){
						e.preventDefault();
						var bCount = $('#bottom-nav-tbody tr').length;
						var bRow = `
							<tr class="bottom-nav-row">
								<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;" class="b-row-num">${bCount + 1}</td>
								<td><input type="text" name="short_bottom_nav_sections[${bCount}][title]" value="" class="regular-text" style="width:100%; font-weight:600;" placeholder="e.g. Latest" required /></td>
								<td>
									<select name="short_bottom_nav_sections[${bCount}][type]" class="b-type-select" style="width:100%;">
										<option value="home">🏠 Home Icon</option>
										<option value="latest" selected>⚡ Latest / Lightning Icon</option>
										<option value="shorttv">📺 TV / Screen Icon</option>
										<option value="popular">⭐ Popular / Star Icon</option>
										<option value="new-popular">⭐ Star Polygon Icon</option>
										<option value="series">🎬 Series Icon</option>
										<option value="movies">🎞️ Movies Icon</option>
										<option value="search">🔍 Search Icon</option>
										<option value="notifications">🔔 Notifications Icon</option>
										<option value="my-list">➕ My List Icon</option>
										<option value="account">👤 Account / Avatar Icon</option>
									</select>
								</td>
								<td><input type="text" name="short_bottom_nav_sections[${bCount}][url]" value="/short-tv/" class="regular-text code b-url-input" style="width:100%;" required /></td>
								<td style="text-align:center; vertical-align:middle;">
									<input type="hidden" name="short_bottom_nav_sections[${bCount}][enabled]" value="0" />
									<input type="checkbox" name="short_bottom_nav_sections[${bCount}][enabled]" value="1" checked />
								</td>
								<td style="text-align:center; vertical-align:middle;">
									<button type="button" class="button button-small button-link-delete btn-remove-b-row" style="color:#ef4444;" title="Remove">✕</button>
								</td>
							</tr>
						`;
						$('#bottom-nav-tbody').append(bRow);
					});

					// Bottom Nav Repeater Remove
					$(document).on('click', '.btn-remove-b-row', function(e){
						e.preventDefault();
						if($('#bottom-nav-tbody tr').length > 1){
							$(this).closest('tr').remove();
							reindexBottomRows();
						} else {
							alert('<?php echo esc_js( __( 'You must have at least one mobile bottom nav item.', 'short-stream-core' ) ); ?>');
						}
					});

					function reindexBottomRows(){
						$('#bottom-nav-tbody tr').each(function(idx){
							$(this).find('.b-row-num').text(idx + 1);
							$(this).find('input[name*="[title]"]').attr('name', `short_bottom_nav_sections[${idx}][title]`);
							$(this).find('select[name*="[type]"]').attr('name', `short_bottom_nav_sections[${idx}][type]`);
							$(this).find('input[name*="[url]"]').attr('name', `short_bottom_nav_sections[${idx}][url]`);
							$(this).find('input[type="hidden"][name*="[enabled]"]').attr('name', `short_bottom_nav_sections[${idx}][enabled]`);
							$(this).find('input[type="checkbox"][name*="[enabled]"]').attr('name', `short_bottom_nav_sections[${idx}][enabled]`);
						});
					}

					// Preset: Mobile Custom (Home, Latest, Popular, Account)
					$('#btn-preset-mobile-custom').on('click', function(e){
						e.preventDefault();
						var items = [
							{ title: 'Home', type: 'home', url: '/' },
							{ title: 'Latest', type: 'latest', url: '/short-tv/' },
							{ title: 'Popular', type: 'popular', url: '/new-popular/' },
							{ title: 'Account', type: 'account', url: '/account/' }
						];
						fillBottomItems(items);
					});

					// Preset: Mobile Default (Home, Short TV, Popular, Account)
					$('#btn-preset-mobile-default').on('click', function(e){
						e.preventDefault();
						var items = [
							{ title: 'Home', type: 'home', url: '/' },
							{ title: 'Short TV', type: 'shorttv', url: '/short-tv/' },
							{ title: 'Popular', type: 'popular', url: '/new-popular/' },
							{ title: 'Account', type: 'account', url: '/account/' }
						];
						fillBottomItems(items);
					});

					function fillBottomItems(items){
						$('#bottom-nav-tbody').empty();
						items.forEach(function(item, idx){
							var row = `
								<tr class="bottom-nav-row">
									<td style="text-align:center; vertical-align:middle; color:#94a3b8; font-weight:bold;" class="b-row-num">${idx + 1}</td>
									<td><input type="text" name="short_bottom_nav_sections[${idx}][title]" value="${item.title}" class="regular-text" style="width:100%; font-weight:600;" required /></td>
									<td>
										<select name="short_bottom_nav_sections[${idx}][type]" class="b-type-select" style="width:100%;">
											<option value="home" ${item.type==='home'?'selected':''}>🏠 Home Icon</option>
											<option value="latest" ${item.type==='latest'?'selected':''}>⚡ Latest / Lightning Icon</option>
											<option value="shorttv" ${item.type==='shorttv'?'selected':''}>📺 TV / Screen Icon</option>
											<option value="popular" ${item.type==='popular'?'selected':''}>⭐ Popular / Star Icon</option>
											<option value="new-popular" ${item.type==='new-popular'?'selected':''}>⭐ Star Polygon Icon</option>
											<option value="series" ${item.type==='series'?'selected':''}>🎬 Series Icon</option>
											<option value="movies" ${item.type==='movies'?'selected':''}>🎞️ Movies Icon</option>
											<option value="search" ${item.type==='search'?'selected':''}>🔍 Search Icon</option>
											<option value="notifications" ${item.type==='notifications'?'selected':''}>🔔 Notifications Icon</option>
											<option value="my-list" ${item.type==='my-list'?'selected':''}>➕ My List Icon</option>
											<option value="account" ${item.type==='account'?'selected':''}>👤 Account / Avatar Icon</option>
										</select>
									</td>
									<td><input type="text" name="short_bottom_nav_sections[${idx}][url]" value="${item.url}" class="regular-text code b-url-input" style="width:100%;" required /></td>
									<td style="text-align:center; vertical-align:middle;">
										<input type="hidden" name="short_bottom_nav_sections[${idx}][enabled]" value="0" />
										<input type="checkbox" name="short_bottom_nav_sections[${idx}][enabled]" value="1" checked />
									</td>
									<td style="text-align:center; vertical-align:middle;">
										<button type="button" class="button button-small button-link-delete btn-remove-b-row" style="color:#ef4444;" title="Remove">✕</button>
									</td>
								</tr>
							`;
							$('#bottom-nav-tbody').append(row);
						});
					}
				});
				</script>
			<?php elseif ( 'firebase' === $tab ) : ?>
				<form method="post" action="options.php" style="width:100%;">
					<?php settings_fields( 'short_firebase_group' ); ?>
					<div class="short-settings-card" style="border-left:4px solid #f59e0b; margin-top:20px;">
						<div class="short-settings-card-header">
							<div>
								<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🔥</span> <?php _e( 'Firebase Authentication & Cloud Database Sync', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
									<?php _e( 'Configure your Firebase Web App credentials to sync user drama coins, watch history, bookmarks, and Google login.', 'short-stream-core' ); ?>
								</p>
							</div>
						</div>

						<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap:20px 28px; margin-top:10px;">
							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'Firebase API Key', 'short-stream-core' ); ?></label>
								<input type="text" name="short_firebase_settings[api_key]" value="<?php echo esc_attr( $firebase['api_key'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="AIzaSy..." />
								<p class="description" style="margin-top:4px;"><?php _e( 'Web API Key from Firebase Project Settings → General.', 'short-stream-core' ); ?></p>
							</div>

							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'Auth Domain', 'short-stream-core' ); ?></label>
								<input type="text" name="short_firebase_settings[auth_domain]" value="<?php echo esc_attr( $firebase['auth_domain'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="project-id.firebaseapp.com" />
								<p class="description" style="margin-top:4px;"><?php _e( 'Firebase authentication domain for web callbacks.', 'short-stream-core' ); ?></p>
							</div>

							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'Project ID', 'short-stream-core' ); ?></label>
								<input type="text" name="short_firebase_settings[project_id]" value="<?php echo esc_attr( $firebase['project_id'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="shorttv-project" />
								<p class="description" style="margin-top:4px;"><?php _e( 'Unique project identifier on Google Cloud Console.', 'short-stream-core' ); ?></p>
							</div>

							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'Storage Bucket', 'short-stream-core' ); ?></label>
								<input type="text" name="short_firebase_settings[storage_bucket]" value="<?php echo esc_attr( $firebase['storage_bucket'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="shorttv-project.firebasestorage.app" />
								<p class="description" style="margin-top:4px;"><?php _e( 'Default Cloud Storage bucket for user uploads and media.', 'short-stream-core' ); ?></p>
							</div>

							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'Messaging Sender ID', 'short-stream-core' ); ?></label>
								<input type="text" name="short_firebase_settings[messaging_sender_id]" value="<?php echo esc_attr( $firebase['messaging_sender_id'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="123456789012" />
								<p class="description" style="margin-top:4px;"><?php _e( 'FCM Sender ID for push notifications.', 'short-stream-core' ); ?></p>
							</div>

							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'App ID', 'short-stream-core' ); ?></label>
								<input type="text" name="short_firebase_settings[app_id]" value="<?php echo esc_attr( $firebase['app_id'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="1:123456789012:web:abcdef..." />
								<p class="description" style="margin-top:4px;"><?php _e( 'Unique Web App ID from Firebase SDK snippet.', 'short-stream-core' ); ?></p>
							</div>

							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'FCM Server Key (For Status Bar Live Push)', 'short-stream-core' ); ?></label>
								<input type="password" name="short_firebase_settings[fcm_server_key]" value="<?php echo esc_attr( $firebase['fcm_server_key'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="AAAA... or Firebase Cloud Messaging Server Key" />
								<p class="description" style="margin-top:4px;"><?php _e( 'Server Key from Firebase Console → Project Settings → Cloud Messaging (Used to send live push alerts to phones).', 'short-stream-core' ); ?></p>
							</div>

							<div>
								<label style="font-weight:700; font-size:13.5px; color:#1e293b; display:block; margin-bottom:6px;"><?php _e( 'Web Push VAPID Key (Public Key)', 'short-stream-core' ); ?></label>
								<input type="text" name="short_firebase_settings[fcm_vapid_key]" value="<?php echo esc_attr( $firebase['fcm_vapid_key'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="BEl..." />
								<p class="description" style="margin-top:4px;"><?php _e( 'Key pair from Firebase Cloud Messaging → Web configuration.', 'short-stream-core' ); ?></p>
							</div>
						</div>
					</div>
					<div style="margin-top:20px;">
						<?php submit_button( __( 'Save Firebase Configuration', 'short-stream-core' ) ); ?>
					</div>
				</form>
			<?php elseif ( 'protection' === $tab ) : 
				$security = get_option( 'short_security_settings', array() );
			?>
				<form method="post" action="options.php" style="width:100%;">
					<?php settings_fields( 'short_security_group' ); ?>
					
					<div class="short-settings-card" style="border-left:4px solid #f59e0b; margin-top:20px;">
						<div class="short-settings-card-header">
							<div>
								<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🛡️</span> <?php _e( 'Security & DevTools Protection', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
									<?php _e( 'Configure developer inspection and anti-tamper security protections across your video platform.', 'short-stream-core' ); ?>
								</p>
							</div>
						</div>

						<table class="form-table" style="margin-top:0;">
							<tr>
								<th style="width:240px;"><label><?php _e( 'Right-Click & DevTools Inspect', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center;">
										<input type="checkbox" name="short_security_settings[allow_right_click]" value="1" <?php checked( !empty($security['allow_right_click']) ); ?> />
										<span><?php _e( 'Allow Right-Click & Inspect Element', 'short-stream-core' ); ?></span>
									</label>
									<p class="description" style="margin-top:6px;"><?php _e( 'Disables the global anti-inspection script so you can right-click and use browser developer tools.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
					<div style="margin-top:20px;">
						<?php submit_button( __( 'Save Security Settings', 'short-stream-core' ) ); ?>
					</div>
				</form>
			<?php elseif ( 'player' === $tab ) : ?>
				<form method="post" action="options.php" style="width:100%;">
					<?php settings_fields( 'short_player_group' ); ?>
					<div class="short-settings-card" style="border-left:4px solid #0284c7; margin-top:20px;">
						<div class="short-settings-card-header">
							<div>
								<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>▶️</span> <?php _e( 'Vertical 9:16 Player & Playback Engine', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
									<?php _e( 'Fine-tune mobile vertical player gestures, auto-play behaviors, episode auto-advancing, and cloud progress tracking.', 'short-stream-core' ); ?>
								</p>
							</div>
						</div>

						<table class="form-table" style="margin-top:0;">
							<tr>
								<th style="width:260px;"><label><?php _e( 'Automatic Playback', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center;">
										<input type="checkbox" name="short_player_settings[auto_play]" value="1" <?php checked( !empty($player['auto_play']) ); ?> />
										<span><?php _e( 'Enable Auto Play on Load', 'short-stream-core' ); ?></span>
									</label>
									<p class="description" style="margin-top:4px;"><?php _e( 'Immediately plays video when entering episode watch screen.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Auto Next Episode', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center;">
										<input type="checkbox" name="short_player_settings[auto_next]" value="1" <?php checked( !empty($player['auto_next']) ); ?> />
										<span><?php _e( 'Auto-advance to Next Episode on Video Ended', 'short-stream-core' ); ?></span>
									</label>
									<p class="description" style="margin-top:4px;"><?php _e( 'Seamlessly swipes up to the next unlocked episode when current episode finishes.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Skip Intro Duration', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:8px;">
										<input type="number" name="short_player_settings[skip_intro_seconds]" value="<?php echo esc_attr($player['skip_intro_seconds'] ?? 85); ?>" class="short-modern-num-box" style="width:100px;" min="0" max="300" step="1" />
										<span style="font-weight:600; color:#475569; font-size:13.5px;"><?php _e( 'seconds', 'short-stream-core' ); ?></span>
									</div>
									<p class="description" style="margin-top:6px;"><?php _e( 'Duration skipped when user taps "Skip Intro" button in vertical player.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Firestore Progress Save Interval', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:8px;">
										<input type="number" name="short_player_settings[track_interval]" value="<?php echo esc_attr($player['track_interval'] ?? 15); ?>" class="short-modern-num-box" style="width:100px;" min="5" max="120" step="5" />
										<span style="font-weight:600; color:#475569; font-size:13.5px;"><?php _e( 'seconds', 'short-stream-core' ); ?></span>
									</div>
									<p class="description" style="margin-top:6px;"><?php _e( 'Frequency of background Firestore sync to persist exact resume timestamp to cloud user account.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
						</table>
					</div>
					<div style="margin-top:20px;">
						<?php submit_button( __( 'Save Player Settings', 'short-stream-core' ) ); ?>
					</div>
				</form>
			<?php elseif ( 'subscription' === $tab ) : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'short_subscription_group' ); ?>
					
					<!-- ══ SECTION 1: GLOBAL COINS PAYWALL SYSTEM ══ -->
					<div class="short-settings-card" style="border-left:4px solid #f59e0b; margin-top:20px;">
						<div class="short-settings-card-header">
							<div>
								<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🪙</span> <?php _e( 'Global Coins Paywall System (Per Episode)', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
									<?php _e( 'Control default coin costs across all episodes from one central place. If an individual episode does not have a custom coin cost set, it automatically inherits this global default.', 'short-stream-core' ); ?>
								</p>
							</div>
						</div>
						<table class="form-table" style="margin-top:0;">
							<tr>
								<th style="width:260px;"><label><?php _e( 'Default Coin Cost Per Episode', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:10px;">
										<input type="number" name="short_subscription_settings[default_coin_cost]" value="<?php echo esc_attr( $sub['default_coin_cost'] ?? '15' ); ?>" class="short-modern-num-box" style="width:110px;" min="1" step="1" />
										<span style="font-weight:600; color:#475569; font-size:13.5px;"><?php _e( 'Coins (e.g. 15). Used when episode access rule is set to "Coins".', 'short-stream-core' ); ?></span>
									</div>
									<p class="description" style="margin-top:6px; color:#64748b;">
										💡 <em><?php _e( 'You do NOT need to edit every episode manually. Setting this updates the unlock cost for all coin-locked episodes.', 'short-stream-core' ); ?></em>
									</p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'New User Free Starting Coins', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:10px;">
										<input type="number" name="short_subscription_settings[starting_coins]" value="<?php echo esc_attr( $sub['starting_coins'] ?? '100' ); ?>" class="short-modern-num-box" style="width:110px;" min="0" step="5" />
										<span style="font-weight:600; color:#475569; font-size:13.5px;"><?php _e( 'Free trial coins automatically credited to new visitors in their local wallet.', 'short-stream-core' ); ?></span>
									</div>
								</td>
							</tr>
						</table>
					</div>

					<!-- ══ SECTION 2: VIP / SUBSCRIPTION PASS PLANS ══ -->
					<div class="short-settings-card" style="border-left:4px solid #7c3aed; margin-top:24px;">
						<div class="short-settings-card-header">
							<div>
								<h3 style="margin:0; font-size:17px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>👑</span> <?php _e( 'VIP Subscription Membership Plans (Full Access)', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
									<?php _e( 'VIP Members get instant unlimited access to all VIP-exclusive and coin-locked episodes without spending coins.', 'short-stream-core' ); ?>
								</p>
							</div>
						</div>
						<table class="form-table" style="margin-top:0;">
							<tr>
								<th style="width:260px;"><label><?php _e( 'Require VIP / Paywall', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center;">
										<input type="checkbox" name="short_subscription_settings[enable_paywall]" value="1" <?php checked( ! empty( $sub['enable_paywall'] ) ); ?> />
										<span><?php _e( 'Enable Paywall on Watch Page', 'short-stream-core' ); ?></span>
									</label>
									<p class="description" style="margin-top:4px;"><?php _e( 'When enabled, locked episodes require either Coin Unlock or an Active VIP Pass to play.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Currency Symbol / Code', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" name="short_subscription_settings[currency_symbol]" value="<?php echo esc_attr( $sub['currency_symbol'] ?? '$' ); ?>" class="short-modern-num-box" style="width:100px; text-align:center;" placeholder="$" />
									<span class="description" style="margin-left:8px;"><?php _e( 'e.g. $, ₱, €, £, USD, PHP', 'short-stream-core' ); ?></span>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'VIP Monthly Pricing Tiers', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
										<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">
											<span style="font-weight:700; color:#0f172a; display:block; margin-bottom:6px; font-size:13.5px;">🥉 <?php _e( 'Basic VIP Plan', 'short-stream-core' ); ?></span>
											<input type="text" name="short_subscription_settings[plan_basic_price]" value="<?php echo esc_attr( $sub['plan_basic_price'] ?? '4.99' ); ?>" class="short-modern-form-input" placeholder="4.99" />
											<p class="description" style="margin-top:6px; font-size:12px;"><?php _e( '720p HD • 1 Device Screen', 'short-stream-core' ); ?></p>
										</div>
										<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">
											<span style="font-weight:700; color:#0f172a; display:block; margin-bottom:6px; font-size:13.5px;">🥈 <?php _e( 'Standard VIP Plan', 'short-stream-core' ); ?></span>
											<input type="text" name="short_subscription_settings[plan_standard_price]" value="<?php echo esc_attr( $sub['plan_standard_price'] ?? '14.99' ); ?>" class="short-modern-form-input" placeholder="14.99" />
											<p class="description" style="margin-top:6px; font-size:12px;"><?php _e( '1080p Full HD • 2 Screens', 'short-stream-core' ); ?></p>
										</div>
										<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">
											<span style="font-weight:700; color:#0f172a; display:block; margin-bottom:6px; font-size:13.5px;">👑 <?php _e( 'Premium VIP 4K Plan', 'short-stream-core' ); ?></span>
											<input type="text" name="short_subscription_settings[plan_premium_price]" value="<?php echo esc_attr( $sub['plan_premium_price'] ?? '49.99' ); ?>" class="short-modern-form-input" placeholder="49.99" />
											<p class="description" style="margin-top:6px; font-size:12px;"><?php _e( '4K HDR • 4 Screens • Spatial Audio', 'short-stream-core' ); ?></p>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Checkout / Payment Mode', 'short-stream-core' ); ?></label></th>
								<td>
									<select name="short_subscription_settings[payment_mode]" class="short-modern-form-input" style="max-width:400px;">
										<option value="lemonsqueezy" <?php selected( ( $sub['payment_mode'] ?? 'lemonsqueezy' ), 'lemonsqueezy' ); ?>><?php _e( '🍋 Lemon Squeezy (Cards, PayPal, Apple Pay, Google Pay)', 'short-stream-core' ); ?></option>
										<option value="mock" <?php selected( ( $sub['payment_mode'] ?? '' ), 'mock' ); ?>><?php _e( 'Free Instant Test Mode (1-Click Instant Activation)', 'short-stream-core' ); ?></option>
										<option value="stripe" <?php selected( ( $sub['payment_mode'] ?? '' ), 'stripe' ); ?>><?php _e( 'Credit / Debit Card (Stripe Checkout Ready)', 'short-stream-core' ); ?></option>
										<option value="paypal" <?php selected( ( $sub['payment_mode'] ?? '' ), 'paypal' ); ?>><?php _e( 'PayPal Express Checkout', 'short-stream-core' ); ?></option>
										<option value="manual" <?php selected( ( $sub['payment_mode'] ?? '' ), 'manual' ); ?>><?php _e( 'Manual Bank / GCash / E-Wallet Transfer', 'short-stream-core' ); ?></option>
									</select>
								</td>
							</tr>
						</table>
					</div>

					<!-- ══ SECTION 3: LEMON SQUEEZY PAYMENT GATEWAY ══ -->
					<div class="short-settings-card" style="border-left:4px solid #10b981; margin-top:24px;">
						<div class="short-settings-card-header">
							<div>
								<h3 style="margin:0; color:#0f172a; font-size:17px; font-weight:800; display:flex; align-items:center; gap:8px;">
									<span>🍋</span> <?php _e( 'Lemon Squeezy Payment Gateway (VIP & Coins)', 'short-stream-core' ); ?>
								</h3>
								<p style="margin:4px 0 0; color:#64748b; font-size:13px;">
									<?php _e( 'Connect your Lemon Squeezy store to accept payments for VIP Passes and Drama Coin packs with instant webhook fulfillment.', 'short-stream-core' ); ?>
								</p>
							</div>
							<span style="background:<?php echo ( ! empty( $sub['lemonsqueezy_mode'] ) && 'live' === $sub['lemonsqueezy_mode'] ) ? '#dcfce7; color:#15803d;' : '#fef3c7; color:#b45309;'; ?> font-size:11px; font-weight:800; padding:4px 12px; border-radius:999px; text-transform:uppercase; letter-spacing:0.5px;">
								<?php echo ( ! empty( $sub['lemonsqueezy_mode'] ) && 'live' === $sub['lemonsqueezy_mode'] ) ? 'LIVE PRODUCTION' : 'TEST MODE (SANDBOX ACTIVE)'; ?>
							</span>
						</div>

						<table class="form-table" style="margin-top:0;">
							<tr>
								<th style="width:260px;"><label><?php _e( 'Enable Lemon Squeezy', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="font-weight:700; font-size:14px; cursor:pointer; display:inline-flex; align-items:center;">
										<input type="checkbox" name="short_subscription_settings[enable_lemonsqueezy]" value="1" <?php checked( ! empty( $sub['enable_lemonsqueezy'] ) || ! isset( $sub['enable_lemonsqueezy'] ) ); ?> />
										<span><?php _e( 'Enable Lemon Squeezy Gateway for VIP & Coins Checkout', 'short-stream-core' ); ?></span>
									</label>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Gateway Environment', 'short-stream-core' ); ?></label></th>
								<td>
									<select name="short_subscription_settings[lemonsqueezy_mode]" class="short-modern-form-input" style="max-width:360px;">
										<option value="test" <?php selected( ( $sub['lemonsqueezy_mode'] ?? 'test' ), 'test' ); ?>><?php _e( '🧪 Test Mode (Sandbox / Demo Purchases)', 'short-stream-core' ); ?></option>
										<option value="live" <?php selected( ( $sub['lemonsqueezy_mode'] ?? '' ), 'live' ); ?>><?php _e( '🚀 Live Production Mode (Real Payments)', 'short-stream-core' ); ?></option>
									</select>
									<p class="description" style="margin-top:4px;"><?php _e( 'In Test Mode, you can make 100% free simulated purchases with test card numbers.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Store Name / Slug', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" name="short_subscription_settings[lemonsqueezy_store_name]" value="<?php echo esc_attr( $sub['lemonsqueezy_store_name'] ?? 'Ayeng Store' ); ?>" class="short-modern-form-input" placeholder="Ayeng Store" />
									<p class="description" style="margin-top:4px;"><?php _e( 'Your store name or slug as shown on your Lemon Squeezy dashboard.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Lemon Squeezy API Key', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="password" name="short_subscription_settings[lemonsqueezy_api_key]" value="<?php echo esc_attr( $sub['lemonsqueezy_api_key'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="eyJ0eXAiOiJKV1QiLCJhbGciOiJ..." />
									<p class="description" style="margin-top:4px;"><?php _e( 'Find this in your Lemon Squeezy dashboard under Settings → API Keys.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Webhook Callback URL', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:8px;">
										<input type="text" id="short-ls-callback-url" value="<?php echo esc_url( rest_url( 'short-stream/v1/lemonsqueezy-webhook' ) ); ?>" class="short-modern-form-input code" readonly style="background:#f8fafc; font-size:12px;" />
										<button type="button" class="button button-secondary" onclick="var copyText = document.getElementById('short-ls-callback-url'); copyText.select(); document.execCommand('copy'); this.innerText='✓ Copied!'; var b = this; setTimeout(function(){ b.innerText='Copy URL'; }, 1500);" style="height:40px; padding:0 18px; font-weight:600;">Copy URL</button>
									</div>
									<p class="description" style="margin-top:4px;"><?php _e( 'Copy & paste this into Lemon Squeezy Settings → Webhooks → Callback URL.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Webhook Signing Secret', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="password" name="short_subscription_settings[lemonsqueezy_webhook_secret]" value="<?php echo esc_attr( $sub['lemonsqueezy_webhook_secret'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="secret_..." />
									<p class="description" style="margin-top:4px;"><?php _e( 'Used to verify incoming Lemon Squeezy webhook signature (X-Signature).', 'short-stream-core' ); ?></p>
								</td>
							</tr>

							<!-- VIP Pass URLs -->
							<tr>
								<th><label><?php _e( 'VIP Pass Checkout Links', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:14px;">
										<div style="background:#faf5ff; border:1px solid #e9d5ff; border-radius:8px; padding:14px;">
											<strong style="color:#6b21a8; font-size:12.5px; display:block; margin-bottom:6px;">Weekly VIP ($4.99 / week)</strong>
											<input type="url" name="short_subscription_settings[lemonsqueezy_vip_weekly_url]" value="<?php echo esc_attr( $sub['lemonsqueezy_vip_weekly_url'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="https://store.lemonsqueezy.com/checkout/buy/..." style="font-size:12px;" />
										</div>
										<div style="background:#faf5ff; border:1px solid #e9d5ff; border-radius:8px; padding:14px;">
											<strong style="color:#6b21a8; font-size:12.5px; display:block; margin-bottom:6px;">Monthly VIP ($14.99 / month)</strong>
											<input type="url" name="short_subscription_settings[lemonsqueezy_vip_monthly_url]" value="<?php echo esc_attr( $sub['lemonsqueezy_vip_monthly_url'] ?? ( $sub['lemonsqueezy_vip_checkout_url'] ?? '' ) ); ?>" class="short-modern-form-input code" placeholder="https://store.lemonsqueezy.com/checkout/buy/..." style="font-size:12px;" />
										</div>
										<div style="background:#faf5ff; border:1px solid #e9d5ff; border-radius:8px; padding:14px;">
											<strong style="color:#6b21a8; font-size:12.5px; display:block; margin-bottom:6px;">Annual VIP ($49.99 / year)</strong>
											<input type="url" name="short_subscription_settings[lemonsqueezy_vip_annual_url]" value="<?php echo esc_attr( $sub['lemonsqueezy_vip_annual_url'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="https://store.lemonsqueezy.com/checkout/buy/..." style="font-size:12px;" />
										</div>
									</div>
								</td>
							</tr>

							<!-- Drama Coin Pack URLs -->
							<tr>
								<th><label><?php _e( 'Coin Pack Checkout Links', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:14px;">
										<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px;">
											<strong style="color:#92400e; font-size:12.5px; display:block; margin-bottom:6px;">Starter: 300 Coins ($1.99)</strong>
											<input type="url" name="short_subscription_settings[lemonsqueezy_coins_starter_url]" value="<?php echo esc_attr( $sub['lemonsqueezy_coins_starter_url'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="https://store.lemonsqueezy.com/checkout/buy/..." style="font-size:12px;" />
										</div>
										<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px;">
											<strong style="color:#92400e; font-size:12.5px; display:block; margin-bottom:6px;">Value: 1,200 + 200 ($4.99)</strong>
											<input type="url" name="short_subscription_settings[lemonsqueezy_coins_value_url]" value="<?php echo esc_attr( $sub['lemonsqueezy_coins_value_url'] ?? ( $sub['lemonsqueezy_coins_checkout_url'] ?? '' ) ); ?>" class="short-modern-form-input code" placeholder="https://store.lemonsqueezy.com/checkout/buy/..." style="font-size:12px;" />
										</div>
										<div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:14px;">
											<strong style="color:#92400e; font-size:12.5px; display:block; margin-bottom:6px;">Super: 3,000 + 600 ($9.99)</strong>
											<input type="url" name="short_subscription_settings[lemonsqueezy_coins_super_url]" value="<?php echo esc_attr( $sub['lemonsqueezy_coins_super_url'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="https://store.lemonsqueezy.com/checkout/buy/..." style="font-size:12px;" />
										</div>
									</div>
								</td>
							</tr>
						</table>

						<!-- Sandbox Testing Helper Box -->
						<div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:16px 20px; margin-top:20px;">
							<h4 style="margin:0 0 6px; color:#1e293b; font-size:13.5px; display:flex; align-items:center; gap:6px;">
								<span>🧪</span> <?php _e( 'Lemon Squeezy Sandbox Test Card Helper', 'short-stream-core' ); ?>
							</h4>
							<p style="margin:0 0 10px; color:#64748b; font-size:12.5px;">
								<?php _e( 'When <strong>Test Mode</strong> is active, use these credentials to simulate test purchases without spending real money:', 'short-stream-core' ); ?>
							</p>
							<div style="display:flex; flex-wrap:wrap; gap:20px; font-size:12.5px; color:#334155; font-family:monospace;">
								<span><strong>Card:</strong> 4242 4242 4242 4242</span>
								<span><strong>Expiry:</strong> 12/28 (Any future date)</span>
								<span><strong>CVC:</strong> 123</span>
								<span><strong>ZIP:</strong> 90210</span>
							</div>
						</div>
					</div>

					<div style="margin-top:20px;">
						<?php submit_button( __( 'Save Subscription Settings', 'short-stream-core' ) ); ?>
					</div>
				</form>
			<?php elseif ( 'ads' === $tab ) : 
				$ad_urls = array();
				if ( ! empty( $ads['rewarded_ad_urls'] ) && is_array( $ads['rewarded_ad_urls'] ) ) {
					$ad_urls = array_values( array_filter( array_map( 'trim', $ads['rewarded_ad_urls'] ) ) );
				}
				if ( empty( $ad_urls ) ) {
					$ad_urls = array_values( array_filter( array(
						$ads['rewarded_ad_url'] ?? '',
						$ads['rewarded_ad_url_2'] ?? '',
						$ads['rewarded_ad_url_3'] ?? ''
					) ) );
				}
				if ( empty( $ad_urls ) ) {
					$ad_urls = array( 'https://omg10.com/4/11932682' );
				}
				$cooldown_secs = ! empty( $ads['ad_click_cooldown'] ) ? (int) $ads['ad_click_cooldown'] : 2;
				$ad_mode       = $ads['ad_unlock_mode'] ?? 'clicks';
			?>
				<form method="post" action="options.php" id="short-ads-form" style="width:100%; margin-top:20px;">
					<?php settings_fields( 'short_ad_group' ); ?>
					
					<!-- ═══════════════════════════════════════════════════════════ -->
					<!-- CARD 1: STATIC DIRECT LINK ADS (MULTI-CLICK UNLOCK)       -->
					<!-- ═══════════════════════════════════════════════════════════ -->
					<div class="short-settings-card" style="border-left:4px solid #10b981; margin-bottom:24px;">
						<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
							<h3 style="margin:0; color:#0f172a; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
								<span>⚡</span> <?php _e( 'Static Direct Link Ads (Multi-Click Unlock)', 'short-stream-core' ); ?>
							</h3>
							<span style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:800; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:0.5px;">Active &amp; Recommended</span>
						</div>

						<!-- Buyer Guide / Info Box -->
						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#475569; line-height:1.6;">
							<strong style="color:#0f172a;">💡 <?php _e( 'How Static Multi-Click Ads Work:', 'short-stream-core' ); ?></strong><br>
							<?php _e( '• When a viewer reaches a locked episode, they click "Click Ad to Unlock" to open your Monetag / Adsterra Direct Links in a new tab.', 'short-stream-core' ); ?><br>
							<?php _e( '• Setting 3 Clicks gives you 3 paid ad impressions per unlock with zero coin inflation (no free coins given away).', 'short-stream-core' ); ?><br>
							<?php _e( '• Adding multiple links rotates each click through a different tag (Click 1 → Link #1, Click 2 → Link #2) so every click counts as a 100% unique paid CPM view!', 'short-stream-core' ); ?>
						</div>

						<table class="form-table short-modern-table" style="margin-top:0;">
							<tr>
								<th style="width:220px;"><label><?php _e( 'Enable Static Ad Unlock', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
										<input type="checkbox" name="short_ad_settings[enable_rewarded_ad]" value="1" <?php checked( ! isset( $ads['enable_rewarded_ad'] ) || ! empty( $ads['enable_rewarded_ad'] ) ); ?> style="width:18px; height:18px; border-radius:4px;" />
										<strong style="color:#0f172a; font-size:13.5px;"><?php _e( 'Show "Click Ad to Unlock" button on locked episode paywall', 'short-stream-core' ); ?></strong>
									</label>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Required Clicks', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:10px;">
										<input type="number" name="short_ad_settings[ad_required_clicks]" value="<?php echo esc_attr( $ads['ad_required_clicks'] ?? '3' ); ?>" class="short-modern-num-box" min="1" max="20" step="1" />
										<span style="font-size:12.5px; color:#64748b;"><?php _e( 'Number of clicks required to unlock episode (e.g. 3 or 4).', 'short-stream-core' ); ?></span>
									</div>
								</td>
							</tr>
							<tr>
								<th style="vertical-align:top; padding-top:14px;"><label><?php _e( 'Direct Links (Rotated)', 'short-stream-core' ); ?></label></th>
								<td>
									<div id="direct-links-repeater" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(420px, 1fr)); gap:10px; width:100%;">
										<?php foreach ( $ad_urls as $idx => $url_val ) : ?>
											<div class="ad-link-row" style="display:flex; align-items:center; gap:8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:6px 10px;">
												<span class="ad-link-badge" style="background:#e2e8f0; color:#334155; font-weight:700; font-size:11.5px; padding:4px 8px; border-radius:6px; min-width:56px; text-align:center; flex-shrink:0;">
													Click #<span class="ad-link-num"><?php echo $idx + 1; ?></span>
												</span>
												<input type="url" name="short_ad_settings[rewarded_ad_urls][]" value="<?php echo esc_attr( $url_val ); ?>" class="short-modern-form-input code" style="flex:1; height:34px; font-size:12px; margin:0;" placeholder="https://omg10.com/4/..." />
												<button type="button" class="button btn-remove-ad-link" onclick="removeAdLinkRow(this)" style="color:#ef4444; border-color:#fca5a5; height:32px; line-height:30px; padding:0 10px; font-size:12px;" title="Remove link">✕</button>
											</div>
										<?php endforeach; ?>
									</div>
									<div style="margin-top:12px;">
										<button type="button" class="button button-secondary" onclick="addAdLinkRow()" style="font-weight:600; font-size:12px; height:32px; border-radius:6px;">
											+ Add Another Direct Link
										</button>
									</div>
									<p style="margin-top:8px; font-size:12px; color:#64748b;">
										<?php _e( 'Click 1 opens Link #1, Click 2 opens Link #2, etc. Rotates through all added links.', 'short-stream-core' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Click Cooldown', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:10px;">
										<input type="number" name="short_ad_settings[ad_click_cooldown]" value="<?php echo esc_attr( $cooldown_secs ); ?>" class="short-modern-num-box" min="1" max="10" step="1" />
										<span style="font-size:12.5px; color:#64748b;"><?php _e( 'Seconds cooldown between clicks (prevents popup blockers & ensures impression registers).', 'short-stream-core' ); ?></span>
									</div>
								</td>
							</tr>
							<tr>
								<th style="vertical-align:top; padding-top:14px;"><label><?php _e( 'Daily Ad Unlock Limit', 'short-stream-core' ); ?></label></th>
								<td>
									<?php $limit_mode = $ads['ad_daily_limit_mode'] ?? 'auto_links'; ?>
									<fieldset style="display:flex; flex-direction:column; gap:10px; width:100%;">
										<label style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; cursor:pointer;">
											<div style="display:flex; align-items:center; gap:8px;">
												<input type="radio" name="short_ad_settings[ad_daily_limit_mode]" value="auto_links" <?php checked( $limit_mode, 'auto_links' ); ?> />
												<strong style="color:#0f172a;"><?php _e( '🎯 Auto-Limit by Total Links Added (Recommended)', 'short-stream-core' ); ?></strong>
											</div>
											<p style="margin:4px 0 0 24px; font-size:12px; color:#64748b; line-height:1.5;">
												<?php _e( 'Calculates: Total Links ÷ Required Clicks. E.g. 9 links with 3 clicks/ep allows exactly 3 free episodes per day. Once all links are used today, ad unlock is disabled until tomorrow!', 'short-stream-core' ); ?>
											</p>
										</label>
										<label style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; cursor:pointer;">
											<div style="display:flex; align-items:center; gap:8px;">
												<input type="radio" name="short_ad_settings[ad_daily_limit_mode]" value="custom" <?php checked( $limit_mode, 'custom' ); ?> />
												<strong style="color:#0f172a;"><?php _e( '🔢 Fixed Daily Limit:', 'short-stream-core' ); ?></strong>
												<input type="number" name="short_ad_settings[ad_custom_daily_limit]" value="<?php echo esc_attr( $ads['ad_custom_daily_limit'] ?? '5' ); ?>" class="short-modern-num-box" min="1" max="100" step="1" style="margin-left:6px;" />
												<span style="font-size:12px; color:#64748b;"><?php _e( 'Free episodes per user per day.', 'short-stream-core' ); ?></span>
											</div>
										</label>
										<label style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; cursor:pointer;">
											<div style="display:flex; align-items:center; gap:8px;">
												<input type="radio" name="short_ad_settings[ad_daily_limit_mode]" value="unlimited" <?php checked( $limit_mode, 'unlimited' ); ?> />
												<span style="color:#0f172a; font-weight:600;"><?php _e( '♾️ Unlimited (Loop links continuously with no daily limit)', 'short-stream-core' ); ?></span>
											</div>
										</label>
									</fieldset>
								</td>
							</tr>
						</table>
					</div>

					<!-- ═══════════════════════════════════════════════════════════ -->
					<!-- CARD 2: REWARDED VIDEO ADS (COUNTDOWN & COINS)             -->
					<!-- ═══════════════════════════════════════════════════════════ -->
					<div class="short-settings-card" style="border-left:4px solid #6366f1; margin-bottom:24px;">
						<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
							<h3 style="margin:0; color:#0f172a; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
								<span>🎬</span> <?php _e( 'Rewarded Video Ads (Watch to Earn Coins)', 'short-stream-core' ); ?>
							</h3>
							<span style="background:#e0e7ff; color:#4338ca; font-size:11px; font-weight:800; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:0.5px;">Video Stream &amp; Coins</span>
						</div>

						<!-- Buyer Guide / Info Box -->
						<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#475569; line-height:1.6;">
							<strong style="color:#0f172a;">💡 <?php _e( 'How Rewarded Video Ads Work:', 'short-stream-core' ); ?></strong><br>
							<?php _e( '• Viewers watch a video ad with a countdown timer (e.g. 15s) and receive bonus coins into their wallet upon completion.', 'short-stream-core' ); ?><br>
							<?php _e( '• Select "Watch Video Ad Mode" below if you are running video ads or sponsor overlays instead of static multi-click direct links.', 'short-stream-core' ); ?>
						</div>

						<table class="form-table short-modern-table" style="margin-top:0;">
							<tr>
								<th style="width:220px;"><label><?php _e( 'Active Unlock Mode', 'short-stream-core' ); ?></label></th>
								<td>
									<fieldset style="display:flex; flex-direction:column; gap:8px; width:100%;">
										<label style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; cursor:pointer;">
											<input type="radio" name="short_ad_settings[ad_unlock_mode]" value="clicks" <?php checked( $ad_mode, 'clicks' ); ?> />
											<strong style="color:#0f172a; margin-left:6px;"><?php _e( '⚡ Use Multi-Click Direct Link Mode (Card 1 above - Recommended for Static Links)', 'short-stream-core' ); ?></strong>
										</label>
										<label style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; cursor:pointer;">
											<input type="radio" name="short_ad_settings[ad_unlock_mode]" value="video_coins" <?php checked( $ad_mode, 'video_coins' ); ?> />
											<strong style="color:#0f172a; margin-left:6px;"><?php _e( '🎬 Use Video Ad Countdown &amp; Coin Reward Mode', 'short-stream-core' ); ?></strong>
										</label>
									</fieldset>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Video Ad Stream / VAST Tag URL', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" name="short_ad_settings[rewarded_video_url]" value="<?php echo esc_attr( $ads['rewarded_video_url'] ?? '' ); ?>" class="short-modern-form-input code" placeholder="https://pubads.g.doubleclick.net/... or https://example.com/ad.mp4" />
									<p style="margin:6px 0 0 0; font-size:12px; color:#64748b;">
										<?php _e( 'Paste your Google IMA VAST Tag URL (e.g. Single Inline Linear / Skippable Inline) or any direct video MP4 URL. If left blank, official Google IMA HTML5 test video will play.', 'short-stream-core' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Video Ad Countdown', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:10px;">
										<input type="number" name="short_ad_settings[ad_countdown_seconds]" value="<?php echo esc_attr( $ads['ad_countdown_seconds'] ?? '15' ); ?>" class="short-modern-num-box" min="3" max="120" step="1" />
										<span style="font-size:12.5px; color:#64748b;"><?php _e( 'Seconds timer required before granting bonus coins.', 'short-stream-core' ); ?></span>
									</div>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Reward Coins Granted', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:10px;">
										<input type="number" name="short_ad_settings[ad_reward_coins]" value="<?php echo esc_attr( $ads['ad_reward_coins'] ?? '5' ); ?>" class="short-modern-num-box" min="0" step="1" />
										<span style="font-size:12.5px; color:#64748b;"><?php _e( 'Coins added to viewer wallet upon completing video ad.', 'short-stream-core' ); ?></span>
									</div>
								</td>
							</tr>
						</table>
					</div>

					<script>
					function addAdLinkRow() {
						var repeater = document.getElementById('direct-links-repeater');
						if (!repeater) return;
						var count = repeater.querySelectorAll('.ad-link-row').length + 1;
						var row = document.createElement('div');
						row.className = 'ad-link-row';
						row.style.cssText = 'display:flex; align-items:center; gap:8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:6px 10px;';
						row.innerHTML = '<span class="ad-link-badge" style="background:#e2e8f0; color:#334155; font-weight:700; font-size:11.5px; padding:4px 8px; border-radius:6px; min-width:56px; text-align:center; flex-shrink:0;">Click #<span class="ad-link-num">' + count + '</span></span>' +
							'<input type="url" name="short_ad_settings[rewarded_ad_urls][]" value="" class="short-modern-form-input code" style="flex:1; height:34px; font-size:12px; margin:0;" placeholder="https://omg10.com/4/..." />' +
							'<button type="button" class="button btn-remove-ad-link" onclick="removeAdLinkRow(this)" style="color:#ef4444; border-color:#fca5a5; height:32px; line-height:30px; padding:0 10px; font-size:12px;" title="Remove link">✕</button>';
						repeater.appendChild(row);
					}

					function removeAdLinkRow(btn) {
						var repeater = document.getElementById('direct-links-repeater');
						if (!repeater) return;
						var rows = repeater.querySelectorAll('.ad-link-row');
						if (rows.length <= 1) {
							var input = rows[0].querySelector('input');
							if (input) input.value = '';
							return;
						}
						var row = btn.closest('.ad-link-row');
						if (row) row.remove();
						repeater.querySelectorAll('.ad-link-row').forEach(function(r, idx) {
							var num = r.querySelector('.ad-link-num');
							if (num) num.textContent = idx + 1;
						});
					}
					</script>

					<div style="margin-top:20px;">
						<?php submit_button( __( 'Save Advertising Settings', 'short-stream-core' ) ); ?>
					</div>
				</form>
			<?php elseif ( 'backup' === $tab ) : 
				$import_msg = '';
				$import_err = '';

				if ( isset( $_POST['short_do_import'] ) && check_admin_referer( 'short_migration_action', 'short_migration_nonce' ) ) {
					$raw_json = wp_unslash( $_POST['short_import_payload'] ?? '' );
					$decoded = json_decode( $raw_json, true );
					if ( is_array( $decoded ) && ! empty( $decoded ) ) {
						$opt_count = 0;
						$drama_count = 0;
						$site_url = site_url();

						// 1. Import options
						foreach ( $decoded as $opt_key => $opt_val ) {
							if ( 'dramas_library' === $opt_key ) continue;
							if ( 0 === strpos( $opt_key, 'short_' ) || 0 === strpos( $opt_key, 'shorttv_' ) ) {
								update_option( $opt_key, $opt_val );
								$opt_count++;
							}
						}

						// 2. Import Dramas Library
						if ( ! empty( $decoded['dramas_library'] ) && is_array( $decoded['dramas_library'] ) ) {
							update_option( 'shorttv_sample_cleaned_up_v2', '1' );
							foreach ( $decoded['dramas_library'] as $item ) {
								$title = trim( $item['title'] ?? '' );
								if ( empty( $title ) ) continue;
								$slug = $item['slug'] ?? sanitize_title( $title );

								// Replace local host with current live site host in poster URLs
								if ( ! empty( $item['cover_assets']['vertical_poster'] ) ) {
									$p = $item['cover_assets']['vertical_poster'];
									if ( false !== strpos( $p, '/wp-content/' ) ) {
										$item['cover_assets']['vertical_poster'] = $site_url . substr( $p, strpos( $p, '/wp-content/' ) );
									}
								}
								if ( ! empty( $item['cover_assets']['horizontal_banner'] ) ) {
									$b = $item['cover_assets']['horizontal_banner'];
									if ( false !== strpos( $b, '/wp-content/' ) ) {
										$item['cover_assets']['horizontal_banner'] = $site_url . substr( $b, strpos( $b, '/wp-content/' ) );
									}
								}

								// Check if post exists
								$existing = get_page_by_path( $slug, OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
								if ( ! $existing ) {
									$existing = get_page_by_title( $title, OBJECT, \SHORT\Core\CPT\Video_CPT::POST_TYPE );
								}

								if ( $existing ) {
									$post_id = $existing->ID;
									wp_update_post( array(
										'ID'          => $post_id,
										'post_status' => 'publish',
										'post_title'  => $title,
										'post_content'=> $item['overview'] ?? '',
									) );
								} else {
									$post_id = wp_insert_post( array(
										'post_title'   => $title,
										'post_name'    => $slug,
										'post_content' => $item['overview'] ?? '',
										'post_status'  => 'publish',
										'post_type'    => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
									) );
								}

								if ( $post_id && ! is_wp_error( $post_id ) ) {
									\SHORT\Core\CPT\Video_CPT::save_series_from_schema( $post_id, $item );
									
									// Set genres
									if ( ! empty( $item['genres'] ) && is_array( $item['genres'] ) ) {
										wp_set_object_terms( $post_id, $item['genres'], 'short_genre' );
									}
									
									// Postmeta shortcuts
									if ( ! empty( $item['cover_assets']['vertical_poster'] ) ) {
										update_post_meta( $post_id, '_shorttv_vertical_poster', $item['cover_assets']['vertical_poster'] );
									}
									if ( ! empty( $item['cover_assets']['horizontal_banner'] ) ) {
										update_post_meta( $post_id, '_shorttv_horizontal_banner', $item['cover_assets']['horizontal_banner'] );
									}
									if ( ! empty( $item['analytics']['view_count'] ) ) {
										update_post_meta( $post_id, '_shorttv_view_count', $item['analytics']['view_count'] );
									}
									if ( ! empty( $item['analytics']['like_count'] ) ) {
										update_post_meta( $post_id, '_shorttv_like_count', $item['analytics']['like_count'] );
									}
									if ( ! empty( $item['rating'] ) ) {
										update_post_meta( $post_id, '_shorttv_rating', $item['rating'] );
									}
									$drama_count++;
								}
							}
						}

						$import_msg = sprintf( __( '🎉 Successfully imported %d settings and %d short drama series! Your live homepage is now fully populated.', 'short-stream-core' ), $opt_count, $drama_count );
					} else {
						$import_err = __( '❌ Invalid JSON data. Please make sure you copied the complete exported JSON code.', 'short-stream-core' );
					}
				}

				$export_keys = array(
					'short_brand_settings',
					'short_firebase_settings',
					'short_player_settings',
					'short_subscription_settings',
					'short_security_settings',
					'short_ad_settings',
					'short_homepage_sections',
					'short_mobile_home_sections',
					'short_section_tabs_config',
					'short_header_nav_items',
					'short_bottom_nav_sections',
					'short_genre_filter_tabs',
					'short_r2_enabled',
					'short_r2_account_id',
					'short_r2_access_key_id',
					'short_r2_secret_access_key',
					'short_r2_bucket_name',
					'short_r2_public_domain',
					'short_r2_folder',
					'short_gumlet_enabled',
					'short_gumlet_api_secret',
					'short_gumlet_workspace_id',
					'short_gumlet_folder_id',
					'short_gumlet_drm_enabled',
					'shorttv_cloudinary_folder',
				);

				$export_data = array();
				foreach ( $export_keys as $k ) {
					$val = get_option( $k, null );
					if ( null !== $val ) {
						$export_data[ $k ] = $val;
					}
				}

				// Include full drama library in export
				$local_dramas_query = get_posts( array(
					'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
					'posts_per_page' => -1,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				) );
				$export_dramas = array();
				if ( ! empty( $local_dramas_query ) ) {
					foreach ( $local_dramas_query as $lp ) {
						if ( empty( $lp->post_title ) || 'Auto Draft' === $lp->post_title ) continue;
						$sc = \SHORT\Core\CPT\Video_CPT::get_series_schema( $lp->ID );
						$sc['title']       = $lp->post_title;
						$sc['slug']        = $lp->post_name;
						$sc['post_status'] = 'publish';
						$export_dramas[]   = $sc;
					}
				}
				$export_data['dramas_library'] = $export_dramas;

				$json_export = wp_json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			?>
				<div style="width:100%; margin-top:20px;">
					<?php if ( $import_msg ) : ?>
						<div class="notice notice-success is-dismissible" style="padding:14px 18px; border-left-color:#10b981; font-weight:600; border-radius:8px; margin-bottom:20px;">
							<p><?php echo esc_html( $import_msg ); ?></p>
						</div>
					<?php endif; ?>
					<?php if ( $import_err ) : ?>
						<div class="notice notice-error is-dismissible" style="padding:14px 18px; border-left-color:#ef4444; font-weight:600; border-radius:8px; margin-bottom:20px;">
							<p><?php echo esc_html( $import_err ); ?></p>
						</div>
					<?php endif; ?>

					<!-- Quick Migration Guide -->
					<div class="short-settings-card" style="border-left:4px solid #3b82f6; margin-bottom:24px;">
						<h3 style="margin:0 0 10px; font-size:17px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
							<span>🚀</span> <?php _e( '1-Click Local to Live Migration', 'short-stream-core' ); ?>
						</h3>
						<p style="margin:0; color:#475569; font-size:13.5px; line-height:1.7;">
							This 1-click migration package contains all <strong><?php echo count($export_dramas); ?> Short Dramas</strong>, all episodes, genres, posters, ratings, views, and all settings (Firebase, Branding, Content Blocks, Video CDNs, and Paywalls).<br>
							<strong>How to sync:</strong> Click <em>Copy Settings JSON</em> below on your Local demo, paste it into the <em>Import Settings</em> box on your live website (<code style="color:#0284c7; background:#e0f2fe; padding:2px 6px; border-radius:4px;">shorttv.ct.ws</code>), and click <strong>Import All Settings &amp; Dramas</strong>.
						</p>
					</div>

					<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(420px, 1fr)); gap:24px; width:100%;">
						<!-- Export Box -->
						<div class="short-settings-card" style="display:flex; flex-direction:column;">
							<h3 style="margin-top:0; margin-bottom:8px; font-size:16px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
								<span>📤</span> <?php _e( 'Export Settings (From Local)', 'short-stream-core' ); ?>
							</h3>
							<p style="margin:0 0 14px; font-size:12.5px; color:#64748b;">
								<?php _e( 'Includes all Firebase keys, Brand settings, Homepage Content Blocks, Video CDN credentials, and Paywall plans.', 'short-stream-core' ); ?>
							</p>
							<textarea id="short-export-textarea" readonly style="width:100%; height:280px; font-family:Consolas, Monaco, monospace; font-size:11.5px; background:#f8fafc; color:#334155; border:1px solid #cbd5e1; border-radius:8px; padding:12px; margin-bottom:14px; resize:vertical; box-sizing:border-box;"><?php echo esc_textarea( $json_export ); ?></textarea>
							<div style="display:flex; gap:10px; margin-top:auto;">
								<button type="button" id="btn-copy-export-json" class="button button-primary" style="flex:1; height:40px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; gap:8px; font-weight:700; font-size:13px; padding:0 18px;">
									<span class="dashicons dashicons-clipboard"></span>
									<span><?php _e( 'Copy Settings JSON', 'short-stream-core' ); ?></span>
								</button>
								<button type="button" id="btn-download-export-json" class="button button-secondary" style="height:40px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; gap:8px; font-weight:600; font-size:13px; padding:0 16px;">
									<span class="dashicons dashicons-download"></span>
									<span><?php _e( 'Download .json', 'short-stream-core' ); ?></span>
								</button>
							</div>
						</div>

						<!-- Import Box -->
						<div class="short-settings-card" style="display:flex; flex-direction:column;">
							<h3 style="margin-top:0; margin-bottom:8px; font-size:16px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
								<span>📥</span> <?php _e( 'Import Settings (To Live)', 'short-stream-core' ); ?>
							</h3>
							<p style="margin:0 0 14px; font-size:12.5px; color:#64748b;">
								<?php _e( 'Paste the exported JSON code here and click Import. Existing settings will be safely overwritten.', 'short-stream-core' ); ?>
							</p>
							<form method="post" action="?page=short-settings&tab=backup" style="display:flex; flex-direction:column; flex:1;">
								<?php wp_nonce_field( 'short_migration_action', 'short_migration_nonce' ); ?>
								<textarea name="short_import_payload" placeholder="Paste your exported settings JSON here..." required style="width:100%; height:280px; font-family:Consolas, Monaco, monospace; font-size:11.5px; background:#fff; color:#0f172a; border:1px solid #cbd5e1; border-radius:8px; padding:12px; margin-bottom:14px; resize:vertical; box-sizing:border-box;"></textarea>
								<button type="submit" name="short_do_import" value="1" class="button button-primary" style="height:40px; border-radius:8px; font-weight:700; font-size:13.5px; display:inline-flex; align-items:center; justify-content:center; gap:8px; margin-top:auto; width:100%;">
									<span class="dashicons dashicons-upload"></span>
									<span><?php _e( 'Import All Settings &amp; Dramas', 'short-stream-core' ); ?></span>
								</button>
							</form>
						</div>
					</div>
				</div>

				<script>
				jQuery(document).ready(function($){
					$('#btn-copy-export-json').on('click', function(){
						var textarea = document.getElementById('short-export-textarea');
						if(textarea){
							textarea.select();
							document.execCommand('copy');
							var btn = $(this);
							var orig = btn.html();
							btn.html('<span class="dashicons dashicons-yes"></span> Copied to Clipboard!');
							setTimeout(function(){ btn.html(orig); }, 2500);
						}
					});

					$('#btn-download-export-json').on('click', function(){
						var jsonStr = document.getElementById('short-export-textarea').value;
						var blob = new Blob([jsonStr], { type: 'application/json' });
						var url = URL.createObjectURL(blob);
						var a = document.createElement('a');
						a.href = url;
						a.download = 'short-tv-settings-' + (new Date().toISOString().slice(0,10)) + '.json';
						document.body.appendChild(a);
						a.click();
						document.body.removeChild(a);
						URL.revokeObjectURL(url);
					});
				});
				</script>
			<?php elseif ( 'notifications' === $tab ) : 
				$notifications = self::get_notifications();
			?>
				<style>
					.notif-tab-grid {
						display: grid;
						grid-template-columns: 380px 1fr;
						gap: 24px;
						width: 100%;
					}
					@media (max-width: 900px) {
						.notif-tab-grid {
							grid-template-columns: 1fr;
						}
					}
					.notif-card-box {
						background: #ffffff;
						border: 1px solid #e2e8f0;
						border-radius: 12px;
						padding: 24px;
						box-shadow: 0 2px 8px rgba(0,0,0,0.03);
					}
					.notif-card-box h3 {
						font-size: 16px;
						font-weight: 700;
						color: #0f172a;
						margin: 0 0 16px 0;
						padding-bottom: 12px;
						border-bottom: 1px solid #f1f5f9;
						display: flex;
						align-items: center;
						justify-content: space-between;
					}
					.notif-form-group {
						margin-bottom: 14px;
					}
					.notif-form-group label {
						display: block;
						font-size: 12.5px;
						font-weight: 600;
						color: #334155;
						margin-bottom: 5px;
					}
					.notif-form-group input[type="text"],
					.notif-form-group input[type="url"],
					.notif-form-group select,
					.notif-form-group textarea {
						width: 100%;
						border: 1px solid #cbd5e1;
						border-radius: 6px;
						padding: 8px 12px;
						font-size: 13px;
						box-sizing: border-box;
					}
					.notif-form-group input:focus,
					.notif-form-group textarea:focus,
					.notif-form-group select:focus {
						border-color: #E50914;
						box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.15);
						outline: none;
					}
					.notif-admin-list {
						display: flex;
						flex-direction: column;
						gap: 12px;
					}
					.notif-admin-item {
						display: flex;
						gap: 14px;
						align-items: flex-start;
						background: #f8fafc;
						border: 1px solid #e2e8f0;
						border-radius: 10px;
						padding: 14px 16px;
						position: relative;
						transition: all 0.2s ease;
					}
					.notif-admin-item:hover {
						background: #ffffff;
						border-color: #cbd5e1;
						box-shadow: 0 4px 12px rgba(0,0,0,0.04);
					}
					.notif-item-poster {
						width: 50px;
						height: 72px;
						border-radius: 6px;
						object-fit: cover;
						background: #0f172a;
						flex-shrink: 0;
					}
					.notif-item-body {
						flex: 1;
						min-width: 0;
					}
					.notif-item-top {
						display: flex;
						align-items: center;
						gap: 8px;
						margin-bottom: 4px;
						flex-wrap: wrap;
					}
					.notif-pill {
						font-size: 10px;
						font-weight: 700;
						text-transform: uppercase;
						padding: 2px 7px;
						border-radius: 4px;
						color: #ffffff;
					}
					.notif-item-title {
						font-size: 14px;
						font-weight: 700;
						color: #0f172a;
						margin: 0;
					}
					.notif-item-msg {
						font-size: 12.5px;
						color: #475569;
						margin: 4px 0 6px 0;
						line-height: 1.4;
					}
					.notif-item-meta {
						font-size: 11px;
						color: #94a3b8;
						display: flex;
						align-items: center;
						gap: 12px;
					}
					.notif-item-actions {
						display: flex;
						gap: 6px;
						align-self: center;
					}
					.btn-notif-edit {
						background: #e0f2fe;
						border: 1px solid #bae6fd;
						color: #0284c7;
						width: 32px;
						height: 32px;
						border-radius: 6px;
						cursor: pointer;
						display: inline-flex;
						align-items: center;
						justify-content: center;
						transition: all 0.2s ease;
					}
					.btn-notif-edit:hover {
						background: #0284c7;
						color: #ffffff;
					}
					.btn-notif-delete {
						background: #fee2e2;
						border: 1px solid #fecaca;
						color: #dc2626;
						width: 32px;
						height: 32px;
						border-radius: 6px;
						cursor: pointer;
						display: inline-flex;
						align-items: center;
						justify-content: center;
						transition: all 0.2s ease;
					}
					.btn-notif-delete:hover {
						background: #dc2626;
						color: #ffffff;
					}
					.notif-dropzone-box {
						border: 2px dashed #cbd5e1;
						border-radius: 8px;
						padding: 16px 14px;
						text-align: center;
						background: #f8fafc;
						transition: all 0.2s ease;
						position: relative;
					}
					.notif-dropzone-box.dragover {
						border-color: #E50914 !important;
						background: #fff1f2 !important;
					}
				</style>

				<?php
				$fcm_tokens = get_option( 'short_fcm_device_tokens', array() );
				$sub_count = is_array( $fcm_tokens ) ? count( array_unique( $fcm_tokens ) ) : 0;
				$firebase_cfg = get_option( 'short_firebase_settings', array() );
				$has_fcm_key = ! empty( $firebase_cfg['fcm_server_key'] );
				?>
				<div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #0284c7; border-radius:12px; padding:22px 28px; margin-bottom:24px; box-shadow:0 2px 8px rgba(0,0,0,0.03); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
					<div>
						<h3 style="margin:0 0 4px 0; font-size:18px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
							<span>🔔</span> <?php _e( 'Notification Center & Push Alerts', 'short-stream-core' ); ?>
							<?php if ( $has_fcm_key ) : ?>
								<span style="font-size:11px; font-weight:700; background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:12px; border:1px solid #bbf7d0;">✓ FCM Live Push Active</span>
							<?php else : ?>
								<span style="font-size:11px; font-weight:700; background:#fef3c7; color:#b45309; padding:2px 8px; border-radius:12px; border:1px solid #fde68a;">⚠️ FCM Server Key Needed in Firebase Tab</span>
							<?php endif; ?>
						</h3>
						<p style="margin:0; color:#64748b; font-size:13px;">
							<?php _e( 'Broadcast system announcements, important alerts, and auto-seed smart notifications for viewers.', 'short-stream-core' ); ?>
						</p>
					</div>
					<div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
						<span style="display:inline-flex; align-items:center; gap:6px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-size:12px; font-weight:700; padding:6px 12px; border-radius:6px;">
							<span>📱</span> <?php printf( __( '%d Push Subscriber(s)', 'short-stream-core' ), $sub_count ); ?>
						</span>
						<button type="button" class="button" id="btn-seed-smart-notifs" style="background:#0284c7; color:#fff; border-color:#0284c7; font-weight:600; padding:0 16px; height:36px; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
							<span class="dashicons dashicons-update" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span> 
							<span><?php _e( 'Auto-Seed Smart Releases', 'short-stream-core' ); ?></span>
						</button>
					</div>
				</div>

				<div class="notif-tab-grid">
					<!-- Create / Edit Form -->
					<div class="notif-card-box">
						<h3 id="notif-form-heading">
							<span><?php _e( 'Broadcast Notification', 'short-stream-core' ); ?></span>
							<span class="dashicons dashicons-megaphone" style="color:#E50914;"></span>
						</h3>
						<form id="short-create-notif-form">
							<input type="hidden" name="action" value="short_save_notification">
							<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'short_admin_nonce' ) ); ?>">
							<input type="hidden" name="notif_id" id="notif-id" value="">

							<div class="notif-form-group">
								<label for="notif-type"><?php _e( 'Notification Type / Priority', 'short-stream-core' ); ?></label>
								<select id="notif-type" name="notif_type">
									<option value="important"><?php _e( '🚨 Important Update (Red Alert)', 'short-stream-core' ); ?></option>
									<option value="new_release"><?php _e( '🎬 New Release / Premiere', 'short-stream-core' ); ?></option>
									<option value="new_season"><?php _e( '📺 New Season Alert', 'short-stream-core' ); ?></option>
									<option value="trending"><?php _e( '🔥 Trending Now', 'short-stream-core' ); ?></option>
									<option value="system"><?php _e( 'ℹ️ System Announcement', 'short-stream-core' ); ?></option>
								</select>
							</div>

							<div class="notif-form-group">
								<label for="notif-title"><?php _e( 'Title / Headline', 'short-stream-core' ); ?> *</label>
								<input type="text" id="notif-title" name="notif_title" placeholder="<?php esc_attr_e( 'e.g. Stranger Things Season 5 is Now Streaming!', 'short-stream-core' ); ?>" required>
							</div>

							<div class="notif-form-group">
								<label for="notif-message"><?php _e( 'Message / Details', 'short-stream-core' ); ?> *</label>
								<textarea id="notif-message" name="notif_message" rows="3" placeholder="<?php esc_attr_e( 'Enter notification description or release details...', 'short-stream-core' ); ?>" required></textarea>
							</div>

							<div class="notif-form-group">
								<label for="notif-tag"><?php _e( 'Badge Tag Label (Optional)', 'short-stream-core' ); ?></label>
								<input type="text" id="notif-tag" name="notif_tag" placeholder="<?php esc_attr_e( 'e.g. Important, Season Premiere, 4K HDR', 'short-stream-core' ); ?>">
							</div>

							<div class="notif-form-group">
								<label for="notif-link"><?php _e( 'Destination URL / Link', 'short-stream-core' ); ?></label>
								<input type="text" id="notif-link" name="notif_link" placeholder="<?php esc_attr_e( 'e.g. /watch/12345/ or /movies/', 'short-stream-core' ); ?>">
							</div>

							<div class="notif-form-group">
								<label><?php _e( 'Thumbnail / Poster Image (Drag & Drop or Select)', 'short-stream-core' ); ?></label>
								<input type="hidden" id="notif-poster" name="notif_poster" value="">

								<div id="notif-dropzone" class="notif-dropzone-box" style="box-sizing:border-box; width:100%; max-width:100%; overflow:hidden; padding:14px; text-align:center;">
									<div id="notif-dropzone-prompt">
										<span class="dashicons dashicons-cloud-upload" style="font-size:32px; width:32px; height:32px; color:#64748b; margin-bottom:4px; display:inline-block;"></span>
										<div style="font-size:13px; font-weight:700; color:#1e293b;"><?php _e( 'Drag & drop image here, or click to upload', 'short-stream-core' ); ?></div>
										<div style="font-size:11.5px; color:#94a3b8; margin-top:2px;"><?php _e( 'Uploads directly to WordPress Media Storage (JPG, PNG, WEBP)', 'short-stream-core' ); ?></div>
										<div style="margin-top:10px; display:flex; gap:8px; justify-content:center; align-items:center; flex-wrap:nowrap;">
											<button type="button" class="button button-secondary" id="btn-browse-notif-file" style="flex:1; font-weight:600; font-size:12px; height:32px; padding:0 8px; display:inline-flex; align-items:center; justify-content:center; gap:4px; white-space:nowrap; box-sizing:border-box;">
												<span class="dashicons dashicons-upload" style="font-size:15px; width:15px; height:15px; display:inline-flex; align-items:center; justify-content:center;"></span> 
												<span><?php _e( 'Choose Local File', 'short-stream-core' ); ?></span>
											</button>
											<button type="button" class="button button-secondary" id="btn-media-library-notif" style="flex:1; font-weight:600; font-size:12px; height:32px; padding:0 8px; display:inline-flex; align-items:center; justify-content:center; gap:4px; white-space:nowrap; box-sizing:border-box;">
												<span class="dashicons dashicons-admin-media" style="font-size:15px; width:15px; height:15px; display:inline-flex; align-items:center; justify-content:center;"></span> 
												<span><?php _e( 'Media Library', 'short-stream-core' ); ?></span>
											</button>
										</div>
									</div>

									<div id="notif-preview-wrap" style="display:none; align-items:center; justify-content:flex-start; gap:12px; box-sizing:border-box; width:100%; max-width:100%; background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; text-align:left;">
										<img id="notif-preview-img" src="" alt="Poster Preview" style="width:52px; height:70px; min-width:52px; max-width:52px; border-radius:6px; object-fit:cover; background:#0f172a; box-shadow:0 2px 6px rgba(0,0,0,0.12); flex-shrink:0; display:block;">
										<div style="flex:1; min-width:0; overflow:hidden;">
											<div style="font-size:12px; font-weight:700; color:#059669; display:flex; align-items:center; gap:4px;">
												<span>✓</span> <?php _e( 'Image Loaded', 'short-stream-core' ); ?>
											</div>
											<div id="notif-preview-url" style="font-size:11px; color:#64748b; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:100%; line-height:1.3;"></div>
											<button type="button" class="button button-link-delete button-small" id="btn-remove-notif-img" style="margin-top:5px; font-size:11.5px; padding:0; text-decoration:underline; color:#dc2626; cursor:pointer; background:none; border:none;">
												<?php _e( '✕ Remove Image', 'short-stream-core' ); ?>
											</button>
										</div>
									</div>

									<div id="notif-upload-progress" style="display:none; padding:12px 0; color:#0284c7; font-weight:700; font-size:12px;">
										<span class="dashicons dashicons-update" style="animation: rotation 1.2s infinite linear; vertical-align:middle;"></span> <?php _e( 'Uploading to WordPress storage...', 'short-stream-core' ); ?>
									</div>
								</div>

								<input type="file" id="notif-file-input" accept="image/*" style="display:none;">
								<div style="margin-top:6px;">
									<input type="text" id="notif-poster-manual" placeholder="<?php esc_attr_e( 'Or paste direct image URL...', 'short-stream-core' ); ?>" style="font-size:11.5px; padding:4px 8px; width:100%; box-sizing:border-box; color:#64748b; border:1px solid #e2e8f0; border-radius:4px;">
								</div>
							</div>

							<div class="notif-form-group" style="display:flex; align-items:center; gap:8px;">
								<input type="checkbox" id="notif-pinned" name="notif_pinned" value="1" checked>
								<label for="notif-pinned" style="margin:0; font-weight:normal;"><?php _e( 'Pin to top of Notification Center', 'short-stream-core' ); ?></label>
							</div>

							<div style="display:flex; gap:10px; margin-top:10px;">
								<button type="submit" id="btn-save-notif" class="button button-primary" style="flex:1; height:40px; background:#E50914 !important; border-color:#E50914 !important; color:#ffffff !important; font-weight:700; font-size:13px; border-radius:6px; cursor:pointer;">
									<span><?php _e( '🚀 Send Notification', 'short-stream-core' ); ?></span>
								</button>
								<button type="button" id="btn-cancel-edit-notif" class="button" style="display:none; height:40px; border-radius:6px; font-weight:600;">
									<?php _e( 'Cancel Edit', 'short-stream-core' ); ?>
								</button>
							</div>
							<div id="notif-form-status" style="margin-top:10px; font-size:12px; text-align:center;"></div>
						</form>
					</div>

					<!-- Notification Feed -->
					<div class="notif-card-box">
						<h3>
							<span><?php _e( 'Active Notifications', 'short-stream-core' ); ?> (<span id="notif-count-label"><?php echo count( $notifications ); ?></span>)</span>
							<span style="font-size:12px; color:#64748b; font-weight:normal;"><?php _e( 'Visible on desktop bell & mobile /notification/', 'short-stream-core' ); ?></span>
						</h3>

						<div class="notif-admin-list" id="notif-admin-list">
							<?php if ( empty( $notifications ) ) : ?>
								<p id="notif-empty-text" style="color:#64748b; font-size:13px; text-align:center; padding:30px 0;"><?php _e( 'No notifications found. Click "Auto-Seed Smart Releases" or send your first notification!', 'short-stream-core' ); ?></p>
							<?php else : ?>
								<?php foreach ( $notifications as $notif ) : 
									$type_colors = array(
										'important'   => '#dc2626',
										'new_release' => '#059669',
										'new_season'  => '#7c3aed',
										'trending'    => '#0284c7',
										'system'      => '#d97706',
									);
									$bg_color = $notif['badge_color'] ?? ( $type_colors[ $notif['type'] ?? 'system' ] ?? '#E50914' );
									$tag = ! empty( $notif['tag'] ) ? $notif['tag'] : ucfirst( str_replace( '_', ' ', $notif['type'] ?? 'Alert' ) );
								?>
									<div class="notif-admin-item" 
										data-id="<?php echo esc_attr( $notif['id'] ); ?>"
										data-type="<?php echo esc_attr( $notif['type'] ?? 'system' ); ?>"
										data-title="<?php echo esc_attr( $notif['title'] ?? '' ); ?>"
										data-message="<?php echo esc_attr( $notif['message'] ?? '' ); ?>"
										data-tag="<?php echo esc_attr( $notif['tag'] ?? '' ); ?>"
										data-link="<?php echo esc_attr( $notif['link'] ?? '' ); ?>"
										data-poster="<?php echo esc_attr( $notif['poster'] ?? '' ); ?>"
										data-pinned="<?php echo ! empty( $notif['pinned'] ) ? '1' : '0'; ?>">
										
										<?php if ( ! empty( $notif['poster'] ) ) : ?>
											<img src="<?php echo esc_url( $notif['poster'] ); ?>" alt="Poster" class="notif-item-poster" onerror="this.style.display='none';">
										<?php else : ?>
											<div class="notif-item-poster" style="display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px;">🔔</div>
										<?php endif; ?>
										<div class="notif-item-body">
											<div class="notif-item-top">
												<span class="notif-pill" style="background:<?php echo esc_attr( $bg_color ); ?>;"><?php echo esc_html( $tag ); ?></span>
												<?php if ( ! empty( $notif['pinned'] ) ) : ?>
													<span style="font-size:11px; color:#d97706; font-weight:700;">📌 <?php _e( 'Pinned', 'short-stream-core' ); ?></span>
												<?php endif; ?>
											</div>
											<h4 class="notif-item-title"><?php echo esc_html( $notif['title'] ); ?></h4>
											<p class="notif-item-msg"><?php echo esc_html( $notif['message'] ); ?></p>
											<div class="notif-item-meta">
												<span>📅 <?php echo esc_html( human_time_diff( $notif['created_at'] ?? time(), time() ) . ' ' . __( 'ago', 'short-stream-core' ) ); ?></span>
												<?php if ( ! empty( $notif['link'] ) ) : ?>
													<span>🔗 <a href="<?php echo esc_url( $notif['link'] ); ?>" target="_blank" style="color:#0284c7; text-decoration:none;"><?php echo esc_html( $notif['link'] ); ?></a></span>
												<?php endif; ?>
											</div>
										</div>
										<div class="notif-item-actions">
											<button type="button" class="btn-notif-edit" title="<?php esc_attr_e( 'Edit notification', 'short-stream-core' ); ?>">
												<span class="dashicons dashicons-edit" style="font-size:16px; width:16px; height:16px;"></span>
											</button>
											<button type="button" class="btn-notif-delete" data-id="<?php echo esc_attr( $notif['id'] ); ?>" title="<?php esc_attr_e( 'Delete notification', 'short-stream-core' ); ?>">
												<span class="dashicons dashicons-trash" style="font-size:16px; width:16px; height:16px;"></span>
											</button>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<script>
				jQuery(document).ready(function($){
					// Helper: Set Poster
					function setNotifPoster(url) {
						if (url) {
							$('#notif-poster').val(url);
							$('#notif-poster-manual').val(url);
							$('#notif-preview-img').attr('src', url);
							$('#notif-preview-url').text(url);
							$('#notif-dropzone-prompt').hide();
							$('#notif-preview-wrap').css('display', 'flex');
						} else {
							$('#notif-poster').val('');
							$('#notif-poster-manual').val('');
							$('#notif-preview-img').attr('src', '');
							$('#notif-preview-url').text('');
							$('#notif-preview-wrap').hide();
							$('#notif-dropzone-prompt').show();
						}
					}

					// Helper: Upload image to WP media storage
					function handleNotifFileUpload(file) {
						if (!file || !file.type.match(/^image\//i)) {
							alert('Please select a valid image file (JPG, PNG, WEBP).');
							return;
						}
						var formData = new FormData();
						formData.append('action', 'short_upload_notif_image');
						formData.append('nonce', '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>');
						formData.append('notif_image_file', file);

						$('#notif-dropzone-prompt, #notif-preview-wrap').hide();
						$('#notif-upload-progress').show();

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: formData,
							processData: false,
							contentType: false,
							success: function(res) {
								$('#notif-upload-progress').hide();
								if (res.success && res.data && res.data.url) {
									setNotifPoster(res.data.url);
								} else {
									$('#notif-dropzone-prompt').show();
									alert(res.data || 'Failed to upload image.');
								}
							},
							error: function() {
								$('#notif-upload-progress').hide();
								$('#notif-dropzone-prompt').show();
								alert('Server error while uploading image.');
							}
						});
					}

					// Browse Local File Button
					$('#btn-browse-notif-file').on('click', function(e){
						e.preventDefault();
						e.stopPropagation();
						$('#notif-file-input').trigger('click');
					});
					$('#notif-file-input').on('change', function(){
						if (this.files && this.files[0]) {
							handleNotifFileUpload(this.files[0]);
						}
					});

					// WP Media Library Modal Picker
					$('#btn-media-library-notif').on('click', function(e){
						e.preventDefault();
						e.stopPropagation();
						var frame = wp.media({
							title: '<?php echo esc_js( __( 'Select Notification Poster', 'short-stream-core' ) ); ?>',
							button: { text: '<?php echo esc_js( __( 'Use This Image', 'short-stream-core' ) ); ?>' },
							multiple: false,
							library: { type: 'image' }
						});
						frame.on('select', function(){
							var attachment = frame.state().get('selection').first().toJSON();
							setNotifPoster(attachment.url);
						});
						frame.open();
					});

					// Drag and Drop Zone
					var dropzone = $('#notif-dropzone');
					dropzone.on('dragover dragenter', function(e){
						e.preventDefault();
						e.stopPropagation();
						dropzone.addClass('dragover');
					});
					dropzone.on('dragleave dragend drop', function(e){
						e.preventDefault();
						e.stopPropagation();
						dropzone.removeClass('dragover');
					});
					dropzone.on('drop', function(e){
						var files = e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
						if (files && files.length > 0) {
							handleNotifFileUpload(files[0]);
						}
					});

					// Manual Image URL Paste Input
					$('#notif-poster-manual').on('input change', function(){
						var val = $(this).val().trim();
						if (val) {
							$('#notif-poster').val(val);
							$('#notif-preview-img').attr('src', val);
							$('#notif-preview-url').text(val);
							$('#notif-dropzone-prompt').hide();
							$('#notif-preview-wrap').css('display', 'flex');
						} else {
							setNotifPoster('');
						}
					});

					// Remove Image Button
					$('#btn-remove-notif-img').on('click', function(e){
						e.preventDefault();
						e.stopPropagation();
						setNotifPoster('');
					});

					// Edit Notification Click
					$(document).on('click', '.btn-notif-edit', function(){
						var item = $(this).closest('.notif-admin-item');
						var id = item.data('id');
						var type = item.data('type') || 'important';
						var title = item.data('title') || '';
						var message = item.data('message') || '';
						var tag = item.data('tag') || '';
						var link = item.data('link') || '';
						var poster = item.data('poster') || '';
						var pinned = item.data('pinned') == '1';

						$('#notif-id').val(id);
						$('#notif-type').val(type);
						$('#notif-title').val(title);
						$('#notif-message').val(message);
						$('#notif-tag').val(tag);
						$('#notif-link').val(link);
						$('#notif-pinned').prop('checked', pinned);
						setNotifPoster(poster);

						$('#notif-form-heading').html('<span>✏️ <?php echo esc_js( __( 'Edit Notification', 'short-stream-core' ) ); ?></span><span class="dashicons dashicons-edit" style="color:#0284c7;"></span>');
						$('#btn-save-notif').html('<span>💾 <?php echo esc_js( __( 'Update Notification', 'short-stream-core' ) ); ?></span>');
						$('#btn-cancel-edit-notif').show();
						$('#notif-form-status').empty();

						$('html, body').animate({
							scrollTop: $('#short-create-notif-form').offset().top - 80
						}, 300);
						$('#notif-title').focus();
					});

					// Cancel Edit Click
					$('#btn-cancel-edit-notif').on('click', function(){
						$('#short-create-notif-form')[0].reset();
						$('#notif-id').val('');
						setNotifPoster('');
						$('#notif-form-heading').html('<span><?php echo esc_js( __( 'Broadcast Notification', 'short-stream-core' ) ); ?></span><span class="dashicons dashicons-megaphone" style="color:#E50914;"></span>');
						$('#btn-save-notif').html('<span>🚀 <?php echo esc_js( __( 'Send Notification', 'short-stream-core' ) ); ?></span>');
						$('#btn-cancel-edit-notif').hide();
						$('#notif-form-status').empty();
					});

					// Create / Update Submit Handler
					$('#short-create-notif-form').on('submit', function(e){
						e.preventDefault();
						var form = $(this);
						var isEdit = !!$('#notif-id').val();
						var btn = $('#btn-save-notif').prop('disabled', true).text(isEdit ? 'Updating...' : 'Broadcasting...');
						$.post(ajaxurl, form.serialize(), function(res){
							btn.prop('disabled', false).html(isEdit ? '<span>💾 Update Notification</span>' : '<span>🚀 Send Notification</span>');
							if(res.success) {
								$('#notif-form-status').html('<span style="color:#059669; font-weight:bold;">&#10003; ' + (isEdit ? 'Notification updated successfully!' : 'Notification sent successfully!') + '</span>');
								setTimeout(function(){ window.location.reload(); }, 700);
							} else {
								$('#notif-form-status').html('<span style="color:#dc2626;">Error: ' + (res.data || 'Failed to save') + '</span>');
							}
						});
					});

					// Delete Notification
					$(document).on('click', '.btn-notif-delete', function(){
						if(!confirm('Are you sure you want to delete this notification?')) return;
						var notifId = $(this).data('id');
						var item = $(this).closest('.notif-admin-item');
						$.post(ajaxurl, {
							action: 'short_delete_notification',
							nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>',
							id: notifId
						}, function(res){
							if(res.success) {
								item.fadeOut(300, function(){ 
									$(this).remove(); 
									var count = $('.notif-admin-item').length;
									$('#notif-count-label').text(count);
								});
							} else {
								alert('Failed to delete notification.');
							}
						});
					});

					// Auto-Seed Smart Notifications
					$('#btn-seed-smart-notifs').on('click', function(){
						var btn = $(this).prop('disabled', true).html('<span class="dashicons dashicons-update" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span> <span>Seeding...</span>');
						$.post(ajaxurl, {
							action: 'short_seed_smart_notifications',
							nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>'
						}, function(res){
							if(res.success) {
								window.location.reload();
							} else {
								btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span> <span>Auto-Seed Smart Releases</span>');
								alert(res.data || 'Failed to auto-seed.');
							}
						});
					});
				});
				</script>
			<?php elseif ( 'license' === $tab ) : 
				$license_data = \SHORT\Core\Admin\License::get_license_data();
				$current_domain = \SHORT\Core\Admin\License::get_current_domain();
				$is_local = \SHORT\Core\Admin\License::is_local_environment();
				$is_active = ( 'active' === $license_data['status'] );
			?>
				<div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid <?php echo $is_active ? '#10b981' : '#f59e0b'; ?>; border-radius:12px; padding:26px 30px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04); width:100%; box-sizing:border-box;">
					<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #f1f5f9; padding-bottom:14px; flex-wrap:wrap; gap:12px;">
						<div>
							<h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
								<span>🔐</span> <?php _e( 'Theme License &amp; Domain Lock Protection', 'short-stream-core' ); ?>
							</h3>
							<p style="margin:4px 0 0; color:#64748b; font-size:13.5px;">
								<?php _e( 'Lock this ShortTV theme installation to your licensed domain. Supports Lemon Squeezy lifetime activation and local staging bypass.', 'short-stream-core' ); ?>
							</p>
						</div>
						<div>
							<?php if ( $is_active ) : ?>
								<span style="background:#d1fae5; color:#065f46; font-size:12px; font-weight:800; padding:6px 14px; border-radius:999px; display:inline-flex; align-items:center; gap:6px; border:1px solid #a7f3d0;">
									<span style="width:8px; height:8px; background:#10b981; border-radius:50%;"></span>
									<?php _e( '🟢 ACTIVE &amp; DOMAIN LOCKED', 'short-stream-core' ); ?>
								</span>
							<?php else : ?>
								<span style="background:#fef3c7; color:#92400e; font-size:12px; font-weight:800; padding:6px 14px; border-radius:999px; display:inline-flex; align-items:center; gap:6px; border:1px solid #fde68a;">
									<span style="width:8px; height:8px; background:#f59e0b; border-radius:50%;"></span>
									<?php _e( '🟡 UNLICENSED / TRIAL MODE', 'short-stream-core' ); ?>
								</span>
							<?php endif; ?>
						</div>
					</div>

					<!-- Current Domain Info Banner -->
					<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
						<div>
							<span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block;"><?php _e( 'Current Server Domain', 'short-stream-core' ); ?></span>
							<strong style="font-size:16px; color:#0f172a; font-family:Consolas, Monaco, monospace;"><?php echo esc_html( $current_domain ); ?></strong>
						</div>
						<div style="font-size:12.5px; color:#64748b;">
							<span style="background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:6px; font-weight:700; border:1px solid #bae6fd;">
								🔒 <?php _e( 'Strict Domain Lock Enforced', 'short-stream-core' ); ?>
							</span>
						</div>
					</div>

					<!-- License Key Form & Status Grid -->
					<div style="display:grid; grid-template-columns: minmax(360px, 1fr) minmax(320px, 420px); gap:28px; align-items:start;">
						
						<!-- Left: License Activation Form -->
						<div class="short-settings-card" style="margin-bottom:0;">
							<h4 style="margin:0 0 12px; font-size:15px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
								<span>🔑</span> <?php _e( 'Enter License Key', 'short-stream-core' ); ?>
							</h4>
							<p style="margin:0 0 16px; font-size:12.5px; color:#64748b;">
								<?php _e( 'Paste the license key received in your Lemon Squeezy receipt or invoice. Once activated, the theme is permanently bound to this domain.', 'short-stream-core' ); ?>
							</p>

							<form id="shorttv-license-form">
								<input type="hidden" name="action" value="shorttv_activate_license">
								<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'short_admin_nonce' ) ); ?>">

								<div style="margin-bottom:16px;">
									<label for="shorttv-license-key-input" style="display:block; font-weight:700; font-size:13px; color:#1e293b; margin-bottom:6px;"><?php _e( 'Lemon Squeezy License Key', 'short-stream-core' ); ?></label>
									<input type="text" id="shorttv-license-key-input" name="license_key" value="<?php echo esc_attr( $license_data['license_key'] ); ?>" class="regular-text" placeholder="e.g. SHORTTV-XXXX-XXXX-XXXX or Lemon Squeezy Key" style="width:100%; font-family:Consolas, Monaco, monospace; font-weight:bold; letter-spacing:1px; height:42px; font-size:14px;" required />
								</div>

								<div style="display:flex; gap:10px; align-items:center;">
									<button type="submit" id="btn-activate-license" class="button button-primary" style="height:40px; border-radius:8px; font-weight:700; font-size:13px; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:0 20px;">
										<span class="dashicons dashicons-yes-alt"></span>
										<span><?php echo $is_active ? __( 'Update / Re-verify License', 'short-stream-core' ) : __( 'Activate &amp; Lock Domain', 'short-stream-core' ); ?></span>
									</button>

									<?php if ( $is_active ) : ?>
										<button type="button" id="btn-deactivate-license" class="button button-secondary" style="height:40px; border-radius:8px; font-weight:600; font-size:13px; color:#dc2626; border-color:#fca5a5;">
											<span class="dashicons dashicons-dismiss"></span>
											<span><?php _e( 'Deactivate License', 'short-stream-core' ); ?></span>
										</button>
									<?php endif; ?>
								</div>
								<div id="license-form-status" style="margin-top:14px; font-size:13px;"></div>
							</form>
						</div>

						<!-- Right: License Details Box -->
						<div class="short-settings-card" style="background:#f8fafc; margin-bottom:0;">
							<h4 style="margin:0 0 14px; font-size:14.5px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">
								<span>📜</span> <?php _e( 'License Registration Details', 'short-stream-core' ); ?>
							</h4>

							<table style="width:100%; font-size:12.5px; line-height:2; color:#334155;">
								<tr>
									<td style="color:#64748b; width:130px; font-weight:600;"><?php _e( 'Product:', 'short-stream-core' ); ?></td>
									<td><strong style="color:#0f172a;"><?php echo esc_html( $license_data['product_name'] ); ?></strong></td>
								</tr>
								<tr>
									<td style="color:#64748b; font-weight:600;"><?php _e( 'Licensed Domain:', 'short-stream-core' ); ?></td>
									<td><code style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:bold; color:#0f172a;"><?php echo esc_html( $license_data['domain'] ?: $current_domain ); ?></code></td>
								</tr>
								<tr>
									<td style="color:#64748b; font-weight:600;"><?php _e( 'Validity:', 'short-stream-core' ); ?></td>
									<td><span style="color:#059669; font-weight:bold;">♾️ <?php echo esc_html( $license_data['expires_at'] ); ?></span></td>
								</tr>
								<tr>
									<td style="color:#64748b; font-weight:600;"><?php _e( 'Buyer / Licensee:', 'short-stream-core' ); ?></td>
									<td><?php echo esc_html( $license_data['customer_name'] ?: 'Not activated' ); ?></td>
								</tr>
								<?php if ( ! empty( $license_data['activated_at'] ) ) : ?>
								<tr>
									<td style="color:#64748b; font-weight:600;"><?php _e( 'Activated On:', 'short-stream-core' ); ?></td>
									<td><?php echo esc_html( $license_data['activated_at'] ); ?></td>
								</tr>
								<?php endif; ?>
							</table>

							<div style="margin-top:16px; padding-top:12px; border-top:1px dashed #cbd5e1; font-size:11.5px; color:#64748b; line-height:1.5;">
								💡 <em>Each single license can only be activated on 1 live production domain. Deactivate before moving to a new website.</em>
							</div>
						</div>

					</div>
				</div>

				<script>
				jQuery(document).ready(function($){
					$('#shorttv-license-form').on('submit', function(e){
						e.preventDefault();
						var form = $(this);
						var btn = $('#btn-activate-license').prop('disabled', true).html('<span>⏳ Contacting Lemon Squeezy...</span>');
						$('#license-form-status').html('');

						$.post(ajaxurl, form.serialize(), function(res){
							btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> <span>Activate &amp; Lock Domain</span>');
							if(res.success) {
								$('#license-form-status').html('<div style="color:#059669; font-weight:bold; background:#ecfdf5; border:1px solid #a7f3d0; padding:10px 14px; border-radius:6px;">' + res.data.message + '</div>');
								setTimeout(function(){ window.location.reload(); }, 1200);
							} else {
								$('#license-form-status').html('<div style="color:#dc2626; font-weight:bold; background:#fef2f2; border:1px solid #fecaca; padding:10px 14px; border-radius:6px;">❌ ' + (res.data || 'Failed to activate license.') + '</div>');
							}
						}).fail(function(){
							btn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> <span>Activate &amp; Lock Domain</span>');
							$('#license-form-status').html('<div style="color:#dc2626; font-weight:bold; background:#fef2f2; border:1px solid #fecaca; padding:10px 14px; border-radius:6px;">❌ Server connection error. Please try again.</div>');
						});
					});

					$('#btn-deactivate-license').on('click', function(e){
						e.preventDefault();
						if(!confirm('Are you sure you want to deactivate the license for this domain? This will unlock the license slot so you can use it on another domain.')) return;

						var btn = $(this).prop('disabled', true).text('Deactivating...');
						$.post(ajaxurl, {
							action: 'shorttv_deactivate_license',
							nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>'
						}, function(res){
							if(res.success) {
								alert(res.data.message);
								window.location.reload();
							} else {
								alert(res.data || 'Failed to deactivate license.');
								btn.prop('disabled', false).text('Deactivate License');
							}
						});
					});
				});
				</script>
			<?php elseif ( 'backup' === $tab ) : ?>
				<div style="background:#fff; border:1px solid #e2e8f0; border-left:4px solid #0284c7; border-radius:12px; padding:26px 30px; margin-top:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04); width:100%; box-sizing:border-box;">
					<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; border-bottom:1px solid #f1f5f9; padding-bottom:14px; flex-wrap:wrap; gap:12px;">
						<div>
							<h3 style="margin:0; font-size:18px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
								<span>📦</span> <?php _e( 'Configuration Reset &amp; 1-Click Default Setup', 'short-stream-core' ); ?>
							</h3>
							<p style="margin:4px 0 0; color:#64748b; font-size:13.5px;">
								<?php _e( 'Restore all headers, navigation tracks, row presets, and endpoints to clean out-of-the-box defaults anytime.', 'short-stream-core' ); ?>
							</p>
						</div>
					</div>

					<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; align-items:stretch;">
						<!-- Reset Defaults Card -->
						<div class="short-settings-card" style="margin-top:0; display:flex; flex-direction:column; justify-content:space-between;">
							<div>
								<h4 style="margin:0 0 8px; font-size:16px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🔄</span> <?php _e( 'Reset All Settings &amp; Endpoints to Defaults', 'short-stream-core' ); ?>
								</h4>
								<p style="margin:0 0 16px; font-size:13px; color:#64748b; line-height:1.6;">
									<?php _e( 'Restores all Header Pills, Desktop Navigation, Mobile Bottom Navbar, Section Tabs, and Row Endpoints (Hero, Trending, Most Viewed, Genres) to default values.', 'short-stream-core' ); ?>
								</p>
							</div>
							<div>
								<button type="button" id="btn-restore-all-defaults" class="button button-primary" style="background:#0284c7 !important; border-color:#0284c7 !important; height:42px; font-size:14px; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:8px; padding:0 22px; cursor:pointer;">
									<span class="dashicons dashicons-image-rotate"></span>
									<span><?php _e( '1-Click Reset to Default Settings', 'short-stream-core' ); ?></span>
								</button>
								<div id="reset-defaults-status" style="margin-top:12px; font-size:13px;"></div>
							</div>
						</div>

						<!-- Re-seed Sample Series Card -->
						<div class="short-settings-card" style="margin-top:0; display:flex; flex-direction:column; justify-content:space-between;">
							<div>
								<h4 style="margin:0 0 8px; font-size:16px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
									<span>🎬</span> <?php _e( 'Re-Seed Sample Short Dramas', 'short-stream-core' ); ?>
								</h4>
								<p style="margin:0 0 16px; font-size:13px; color:#64748b; line-height:1.6;">
									<?php _e( 'Populate your catalog with complete sample 9:16 short drama series, episode streams, and poster artwork for live testing.', 'short-stream-core' ); ?>
								</p>
							</div>
							<div>
								<button type="button" id="btn-seed-sample-dramas" class="button" style="background:#059669 !important; border-color:#059669 !important; color:#ffffff !important; height:42px; font-size:14px; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:8px; padding:0 22px; cursor:pointer;">
									<span class="dashicons dashicons-video-alt3"></span>
									<span><?php _e( 'Seed Sample Dramas', 'short-stream-core' ); ?></span>
								</button>
								<div id="seed-dramas-status" style="margin-top:12px; font-size:13px;"></div>
							</div>
						</div>
					</div>
				</div>

				<script>
				jQuery(document).ready(function($){
					$('#btn-restore-all-defaults').on('click', function(e){
						e.preventDefault();
						if(!confirm('Are you sure you want to reset all navigation tracks, homepage rows, and endpoints to clean defaults?')) return;

						var btn = $(this).prop('disabled', true).html('<span>⏳ Resetting settings...</span>');
						$('#reset-defaults-status').html('');

						$.post(ajaxurl, {
							action: 'short_restore_all_system_defaults',
							nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>'
						}, function(res){
							if(res.success) {
								$('#reset-defaults-status').html('<div style="color:#059669; font-weight:bold; background:#ecfdf5; border:1px solid #a7f3d0; padding:10px 14px; border-radius:6px;">🎉 All settings, headers, and endpoints restored to defaults!</div>');
								setTimeout(function(){ window.location.reload(); }, 1000);
							} else {
								btn.prop('disabled', false).html('<span class="dashicons dashicons-image-rotate"></span> <span>1-Click Reset to Default Settings</span>');
								$('#reset-defaults-status').html('<div style="color:#dc2626; font-weight:bold;">❌ ' + (res.data || 'Failed to reset.') + '</div>');
							}
						});
					});

					$('#btn-seed-sample-dramas').on('click', function(e){
						e.preventDefault();
						var btn = $(this).prop('disabled', true).html('<span>⏳ Seeding catalog...</span>');
						$('#seed-dramas-status').html('');

						$.post(ajaxurl, {
							action: 'shorttv_seed_sample_series',
							nonce: '<?php echo wp_create_nonce( "shorttv_admin_nonce" ); ?>'
						}, function(res){
							if(res.success) {
								$('#seed-dramas-status').html('<div style="color:#059669; font-weight:bold; background:#ecfdf5; border:1px solid #a7f3d0; padding:10px 14px; border-radius:6px;">🎉 Sample short dramas populated successfully!</div>');
								setTimeout(function(){ window.location.reload(); }, 1000);
							} else {
								btn.prop('disabled', false).html('<span class="dashicons dashicons-video-alt3"></span> <span>Seed Sample Dramas</span>');
								$('#seed-dramas-status').html('<div style="color:#dc2626; font-weight:bold;">❌ ' + (res.data || 'Failed to seed.') + '</div>');
							}
						});
					});
				});
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Dedicated Admin Page for Video Storage & CDN (Cloudinary & Gumlet).
	 */
	public static function render_video_storage_settings() {
		$tab = $_GET['tab'] ?? 'cloudinary';
		if ( 'general' === $tab ) {
			$tab = 'cloudinary';
		}
		?>
		<style>
			.short-settings-wrap {
				box-sizing: border-box !important;
				max-width: 100% !important;
				margin-right: 20px !important;
				padding-right: 10px !important;
			}
			/* Modern Pill Tab Navigation */
			.short-settings-wrap .nav-tab-wrapper {
				border-bottom: none !important;
				padding: 0 !important;
				margin: 20px 0 26px 0 !important;
				display: flex !important;
				flex-wrap: wrap !important;
				gap: 10px !important;
			}
			.short-settings-wrap .nav-tab {
				background: #ffffff !important;
				border: 1.5px solid #e2e8f0 !important;
				border-radius: 10px !important;
				padding: 10px 18px !important;
				font-size: 13.5px !important;
				font-weight: 600 !important;
				color: #475569 !important;
				text-decoration: none !important;
				display: inline-flex !important;
				align-items: center !important;
				gap: 8px !important;
				transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
				box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
				margin: 0 !important;
				float: none !important;
				line-height: normal !important;
			}
			.short-settings-wrap .nav-tab:hover {
				background: #f8fafc !important;
				color: #0f172a !important;
				border-color: #94a3b8 !important;
				transform: translateY(-1px) !important;
				box-shadow: 0 4px 10px rgba(0,0,0,0.06) !important;
			}
			.short-settings-wrap .nav-tab.nav-tab-active {
				background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
				color: #ffffff !important;
				border-color: #0284c7 !important;
				font-weight: 700 !important;
				box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35) !important;
				transform: translateY(-1px) !important;
			}
			.short-settings-wrap .nav-tab.nav-tab-active:hover {
				background: linear-gradient(135deg, #0369a1 0%, #075985 100%) !important;
				color: #ffffff !important;
				border-color: #0369a1 !important;
			}
			.short-settings-card {
				background: #ffffff;
				border: 1px solid #e2e8f0;
				border-radius: 12px;
				padding: 24px;
				margin-top: 20px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
				width: 100%;
				box-sizing: border-box;
			}
			.short-modern-form-input {
				width: 100%;
				max-width: 100% !important;
				border: 1px solid #cbd5e1 !important;
				border-radius: 8px !important;
				padding: 9px 14px !important;
				font-size: 13.5px !important;
				color: #0f172a !important;
				background: #ffffff !important;
				box-shadow: 0 1px 2px rgba(0,0,0,0.03) !important;
				transition: all 0.15s ease-in-out !important;
				box-sizing: border-box !important;
			}
			.short-modern-form-input:focus {
				border-color: #0284c7 !important;
				outline: none !important;
				box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
			}
			.short-modern-num-box {
				height: 38px !important;
				border-radius: 8px !important;
				border: 1px solid #cbd5e1 !important;
				padding: 0 12px !important;
				font-size: 13.5px !important;
				font-weight: 600 !important;
				color: #0f172a !important;
				background: #ffffff !important;
				box-sizing: border-box !important;
				transition: all 0.15s ease !important;
			}
			.short-modern-num-box:focus {
				border-color: #0284c7 !important;
				box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
				outline: none !important;
			}
			.short-modern-table {
				width: 100%;
				border-collapse: collapse;
			}
			.short-modern-table th {
				width: 240px;
				padding: 16px 16px 16px 0;
				vertical-align: top;
				font-weight: 600;
				color: #1e293b;
				font-size: 13.5px;
			}
			.short-modern-table td {
				padding: 14px 0;
			}
		</style>
		<div class="wrap short-settings-wrap">
			<h1 style="font-size:24px; font-weight:800; color:#0f172a; margin-bottom:4px;"><?php _e( '☁️ Video Storage & Streaming CDN', 'short-stream-core' ); ?></h1>
			<p class="description" style="margin-bottom:20px; font-size:14px; color:#64748b;">
				<?php _e( 'Configure your dedicated Cloudinary and Gumlet video storage providers. Uploads and streams are fully isolated per provider.', 'short-stream-core' ); ?>
			</p>
			<nav class="nav-tab-wrapper">
				<a href="?page=short-video-storage&tab=cloudinary" class="nav-tab <?php echo ( 'cloudinary' === $tab ) ? 'nav-tab-active' : ''; ?>">☁️ <?php _e( 'Cloudinary Settings', 'short-stream-core' ); ?></a>
				<a href="?page=short-video-storage&tab=gumlet" class="nav-tab <?php echo ( 'gumlet' === $tab ) ? 'nav-tab-active' : ''; ?>">🎬 <?php _e( 'Gumlet Video CDN', 'short-stream-core' ); ?></a>
				<a href="?page=short-video-storage&tab=r2" class="nav-tab <?php echo ( 'r2' === $tab ) ? 'nav-tab-active' : ''; ?>">📦 <?php _e( 'Cloudflare R2 Storage', 'short-stream-core' ); ?></a>
			</nav>

			<?php if ( 'cloudinary' === $tab ) : 
				$cloud_name    = get_option( 'shorttv_cloudinary_cloud_name', 'your-cloud' );
				$upload_preset = get_option( 'shorttv_cloudinary_upload_preset', 'my_video_preset' );
				$asset_folder  = get_option( 'shorttv_cloudinary_folder', 'short' );
			?>
				<div class="short-settings-card" style="border-left:4px solid #0284c7; margin-top:0;">
					<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
						<h3 style="margin:0; color:#0369a1; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
							<span>☁️</span> <?php _e( 'Cloudinary Storage Integration', 'short-stream-core' ); ?>
						</h3>
						<span style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:800; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:0.5px;">Cloud CDN</span>
					</div>
					<p style="margin:0 0 20px; font-size:13.5px; color:#475569; line-height:1.6;">
						<?php _e( 'Upload short drama episode videos and vertical posters directly to Cloudinary media storage with automatic adaptive streaming.', 'short-stream-core' ); ?>
					</p>

					<form method="post" action="options.php" style="width:100%;">
						<?php settings_fields( 'shorttv_cloudinary_group' ); ?>
						<table class="form-table short-modern-table" style="margin-top:0;">
							<tr>
								<th><label><?php _e( 'Cloudinary Cloud Name', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" name="shorttv_cloudinary_cloud_name" value="<?php echo esc_attr( $cloud_name ); ?>" class="short-modern-form-input" placeholder="e.g. dxyz12345" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Your Cloudinary Cloud Name (found on your Cloudinary dashboard).', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Upload Preset (Unsigned)', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" name="shorttv_cloudinary_upload_preset" value="<?php echo esc_attr( $upload_preset ); ?>" class="short-modern-form-input" placeholder="my_video_preset" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Your unsigned upload preset name from Cloudinary Settings → Upload → Upload Presets (e.g. <code>my_video_preset</code>).', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label><?php _e( 'Default Asset Folder', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" name="shorttv_cloudinary_folder" value="<?php echo esc_attr( $asset_folder ); ?>" class="short-modern-form-input" placeholder="short" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'The Cloudinary folder where videos and covers will be uploaded (Default: <code>short</code>).', 'short-stream-core' ); ?></p>
								</td>
							</tr>
						</table>
						<div style="margin-top:20px;">
							<?php submit_button( __( 'Save Cloudinary Settings', 'short-stream-core' ) ); ?>
						</div>
					</form>
				</div>

				<!-- Cloudinary Step-by-Step Setup Guide Box (At Bottom) -->
				<div class="short-settings-card" style="border-left:4px solid #0284c7; background:#f0f9ff; margin-top:24px;">
					<div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
						<span style="font-size:22px;">☁️</span><h3 style="margin:0; font-size:16px; color:#0369a1; font-weight:800;"><?php _e( 'Cloudinary Media Storage & CDN Integration Guide', 'short-stream-core' ); ?></h3>
					</div>
					<p style="margin:0 0 14px; font-size:13.5px; color:#0c4a6e; line-height:1.6;">
						<?php _e( 'Cloudinary allows fast, direct-from-browser uploading of 9:16 short drama episodes and posters with automatic video transcoding and CDN delivery.', 'short-stream-core' ); ?>
					</p>
					
					<div style="background:#ffffff; border:1px solid #bae6fd; border-radius:8px; padding:16px 20px; margin-top:12px;">
						<h4 style="margin:0 0 10px; color:#0369a1; font-size:13.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;"><?php _e( '⚡ How to Set Up Cloudinary (Step-by-Step):', 'short-stream-core' ); ?></h4>
						<ol style="margin:0 0 16px; padding-left:20px; font-size:13px; color:#334155; line-height:1.7;">
							<li><?php _e( 'Log in to your <a href="https://cloudinary.com/console" target="_blank" rel="noopener noreferrer" style="color:#0284c7; font-weight:700;">Cloudinary Dashboard</a>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'On the main dashboard, copy your <strong>Cloud Name</strong> and paste it into the <strong>Cloudinary Cloud Name</strong> field above.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Click the <strong>Settings (Gear Icon)</strong> at the bottom left → go to the <strong>Upload</strong> tab → scroll down to <strong>Upload presets</strong>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Click <strong>Add upload preset</strong>. Set <em>Signing Mode</em> to <strong>Unsigned</strong>, enter a preset name (e.g. <code>short_preset</code> or <code>my_video_preset</code>), and click <strong>Save</strong>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Copy the preset name into the <strong>Upload Preset (Unsigned)</strong> field above.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Set your Default Asset Folder to <code>short</code> (same folder structure as other providers), and click <strong>Save Cloudinary Settings</strong>.', 'short-stream-core' ); ?></li>
						</ol>
						<a href="https://cloudinary.com/console" target="_blank" rel="noopener noreferrer" class="button button-primary" style="background:#0284c7; border-color:#0369a1; height:36px; border-radius:6px; font-weight:600; display:inline-flex; align-items:center;">
							<?php _e( '→ Open Cloudinary Dashboard', 'short-stream-core' ); ?>
						</a>
					</div>
				</div>

			<?php elseif ( 'gumlet' === $tab ) :
				$gumlet_enabled      = get_option( 'short_gumlet_enabled', '0' );
				$gumlet_workspace_id = get_option( 'short_gumlet_workspace_id', '' );
				$gumlet_folder_id    = get_option( 'short_gumlet_folder_id', '' );
				$gumlet_api_secret   = get_option( 'short_gumlet_api_secret', '' );
				$gumlet_drm_enabled  = get_option( 'short_gumlet_drm_enabled', '1' );
				$gumlet_cache_ttl    = get_option( 'short_gumlet_cache_ttl', '60' );
			?>
				<div class="short-settings-card" style="border-left:4px solid #7c3aed; margin-top:0;">
					<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
						<h3 style="margin:0; color:#5b21b6; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
							<span>🎬</span> <?php _e( 'Gumlet Video CDN Integration', 'short-stream-core' ); ?>
						</h3>
						<span style="background:#ede9fe; color:#6d28d9; font-size:11px; font-weight:800; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:0.5px;">HLS &amp; DRM</span>
					</div>
					<p style="margin:0 0 20px; font-size:13.5px; color:#475569; line-height:1.6;">
						<?php _e( 'Upload and stream your short drama episodes directly using Gumlet Video CDN. Gumlet automatically generates adaptive HLS master streams (.m3u8) and MP4 fallbacks with ultra-fast CDN delivery.', 'short-stream-core' ); ?>
					</p>

					<form method="post" action="options.php" style="width:100%;">
						<?php settings_fields( 'short_gumlet_group' ); ?>
						<h4 style="margin:0 0 16px; font-size:15px; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:8px;"><?php _e( 'API Credentials', 'short-stream-core' ); ?></h4>
						<table class="form-table short-modern-table" style="margin-top:0;">
							<tr>
								<th><label for="short_gumlet_enabled"><?php _e( 'Enable Gumlet', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
										<input type="checkbox" id="short_gumlet_enabled" name="short_gumlet_enabled" value="1" <?php checked( '1', $gumlet_enabled ); ?> style="width:18px; height:18px; border-radius:4px;" />
										<strong style="color:#0f172a; font-size:13.5px;"><?php _e( 'Enable Gumlet Video CDN for Episode Uploads and Streaming', 'short-stream-core' ); ?></strong>
									</label>
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Allows single and bulk uploading of short drama episodes directly to Gumlet from the drama editor.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_gumlet_api_secret"><?php _e( 'API Secret Key', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="password" id="short_gumlet_api_secret" name="short_gumlet_api_secret" value="<?php echo esc_attr( $gumlet_api_secret ); ?>" class="short-modern-form-input" placeholder="gmt_live_xxxxxxxxxxxxxxxxxx" autocomplete="new-password" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Your Gumlet API Secret. Find it at <strong>Gumlet Dashboard → Settings → API Keys</strong>.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_gumlet_workspace_id"><?php _e( 'Workspace / Source ID', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" id="short_gumlet_workspace_id" name="short_gumlet_workspace_id" value="<?php echo esc_attr( $gumlet_workspace_id ); ?>" class="short-modern-form-input" placeholder="e.g. 6ab736423518a22dd8ac7f22" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Your main Gumlet Video Workspace ID (from dashboard URL: <code>?workspace=6ab736423518a22dd8ac7f22</code>).', 'short-stream-core' ); ?></p>
								</td>
							</tr>
						</table>

						<h4 style="margin:24px 0 16px; font-size:15px; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:8px;"><?php _e( 'Delivery Options', 'short-stream-core' ); ?></h4>
						<table class="form-table short-modern-table" style="margin-top:0;">
							<tr>
								<th><label for="short_gumlet_drm_enabled"><?php _e( 'Signed URL Protection (DRM)', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
										<input type="checkbox" id="short_gumlet_drm_enabled" name="short_gumlet_drm_enabled" value="1" <?php checked( '1', $gumlet_drm_enabled ); ?> style="width:18px; height:18px; border-radius:4px;" />
										<strong style="color:#0f172a; font-size:13.5px;"><?php _e( 'Generate HMAC-SHA256 signed URLs (1-hour expiry)', 'short-stream-core' ); ?></strong>
									</label>
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Protects Gumlet streams from hotlinking. Requires your Gumlet collection to have Signed URLs enabled. The API Secret is used as the signing key.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_gumlet_cache_ttl"><?php _e( 'Cache TTL (minutes)', 'short-stream-core' ); ?></label></th>
								<td>
									<div style="display:flex; align-items:center; gap:10px;">
										<input type="number" id="short_gumlet_cache_ttl" name="short_gumlet_cache_ttl" value="<?php echo esc_attr( $gumlet_cache_ttl ); ?>" class="short-modern-num-box" min="1" max="1440" />
										<span style="font-size:12.5px; color:#64748b;"><?php _e( 'How long to cache Gumlet playback URL responses server-side. Default: 60 minutes.', 'short-stream-core' ); ?></span>
									</div>
								</td>
							</tr>
						</table>

						<div style="margin-top:20px;">
							<?php submit_button( __( 'Save Gumlet Settings', 'short-stream-core' ) ); ?>
						</div>
					</form>
				</div>

				<div class="short-settings-card" style="margin-top:20px;">
					<h3 style="margin-top:0; margin-bottom:10px; font-size:15px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
						<span>🔌</span> <?php _e( 'Connection Test', 'short-stream-core' ); ?>
					</h3>
					<p style="margin:0 0 14px; font-size:13px; color:#64748b;">
						<?php _e( 'Test the live connection to Gumlet servers with your configured API Secret Key.', 'short-stream-core' ); ?>
					</p>
					<div style="display:flex; align-items:center; gap:12px;">
						<button type="button" class="button button-secondary" id="btn-test-gumlet" <?php echo empty( $gumlet_api_secret ) ? 'disabled' : ''; ?> style="height:36px; border-radius:6px; font-weight:600;">
							<?php _e( '🔌 Test Gumlet Connection', 'short-stream-core' ); ?>
						</button>
						<span id="gumlet-test-result" style="font-weight:600; font-size:13.5px;"></span>
					</div>
				</div>

				<script>
				(function() {
					var btn = document.getElementById('btn-test-gumlet');
					var result = document.getElementById('gumlet-test-result');
					if ( ! btn ) return;
					btn.addEventListener('click', function() {
						result.textContent = 'Testing…';
						result.style.color = '#666';
						btn.disabled = true;
						fetch('<?php echo esc_url( rest_url( 'short/v1/gumlet/test' ) ); ?>', {
							method: 'POST',
							headers: {
								'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
								'Content-Type': 'application/json'
							}
						})
						.then(function(r) { return r.json(); })
						.then(function(data) {
							if (data.success) {
								result.textContent = '✅ ' + data.message + (data.plan && data.plan !== 'N/A' ? ' — Plan: ' + data.plan : '');
								result.style.color = '#00a32a';
							} else {
								result.textContent = '❌ ' + (data.message || 'Connection failed');
								result.style.color = '#d63638';
							}
						})
						.catch(function(err) {
							result.textContent = '❌ Request failed: ' + err.message;
							result.style.color = '#d63638';
						})
						.finally(function() { btn.disabled = false; });
					});
				})();
				</script>

				<!-- Gumlet Step-by-Step Setup Guide Box (At Bottom) -->
				<div class="short-settings-card" style="border-left:4px solid #7c3aed; background:#f5f3ff; margin-top:24px;">
					<div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
						<span style="font-size:22px;">🎬</span><h3 style="margin:0; font-size:16px; color:#5b21b6; font-weight:800;"><?php _e( 'Gumlet Video CDN Integration Guide', 'short-stream-core' ); ?></h3>
					</div>
					<p style="margin:0 0 14px; font-size:13.5px; color:#3b0764; line-height:1.6;">
						<?php _e( 'Gumlet provides enterprise-grade video processing, HLS adaptive bitrate streaming (.m3u8), and secure signed URL tokens.', 'short-stream-core' ); ?>
					</p>
					
					<div style="background:#ffffff; border:1px solid #ddd6fe; border-radius:8px; padding:16px 20px; margin-top:12px;">
						<h4 style="margin:0 0 10px; color:#5b21b6; font-size:13.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;"><?php _e( '⚡ How to Set Up Gumlet (Step-by-Step):', 'short-stream-core' ); ?></h4>
						<ol style="margin:0 0 16px; padding-left:20px; font-size:13px; color:#334155; line-height:1.7;">
							<li><?php _e( 'Log in to your <a href="https://dash.gumlet.com" target="_blank" rel="noopener noreferrer" style="color:#7c3aed; font-weight:700;">Gumlet Dashboard</a>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Go to <strong>Settings → API Keys</strong> in the left sidebar, create or copy your <strong>API Key Secret</strong>, and paste it into the <strong>API Secret Key</strong> field above.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Go to your <strong>Video Sources / Collections</strong> page. Copy your <strong>Workspace / Collection ID</strong> from the browser URL (e.g. <code>?workspace=6ab7...</code>) and paste it into the <strong>Workspace ID</strong> field.', 'short-stream-core' ); ?></li>
							<li><?php _e( '(Optional) In your collection settings, enable <strong>Signed URLs</strong> for HMAC-SHA256 token DRM hotlink protection.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Check <strong>Enable Gumlet</strong> and click <strong>Save Gumlet Settings</strong>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Click the <strong>🔌 Test Gumlet Connection</strong> button above to confirm live connectivity!', 'short-stream-core' ); ?></li>
						</ol>
						<a href="https://dash.gumlet.com" target="_blank" rel="noopener noreferrer" class="button button-primary" style="background:#7c3aed; border-color:#6d28d9; height:36px; border-radius:6px; font-weight:600; display:inline-flex; align-items:center;">
							<?php _e( '→ Open Gumlet Dashboard', 'short-stream-core' ); ?>
						</a>
					</div>
				</div>

			<?php elseif ( 'r2' === $tab ) :
				$r2_enabled    = get_option( 'short_r2_enabled', '0' );
				$r2_account_id = get_option( 'short_r2_account_id', '' );
				$r2_access_key = get_option( 'short_r2_access_key_id', '' );
				$r2_secret_key = get_option( 'short_r2_secret_access_key', '' );
				$r2_bucket     = get_option( 'short_r2_bucket_name', '' );
				$r2_public_url = get_option( 'short_r2_public_domain', '' );
				$r2_folder     = get_option( 'short_r2_folder', 'short' );
			?>
				<div class="short-settings-card" style="border-left:4px solid #ea580c; margin-top:0;">
					<div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
						<h3 style="margin:0; color:#9a3412; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
							<span>📦</span> <?php _e( 'Cloudflare R2 Object Storage Integration', 'short-stream-core' ); ?>
						</h3>
						<span style="background:#ffedd5; color:#c2410c; font-size:11px; font-weight:800; padding:4px 10px; border-radius:999px; text-transform:uppercase; letter-spacing:0.5px;">Zero Egress CDN</span>
					</div>
					<p style="margin:0 0 20px; font-size:13.5px; color:#475569; line-height:1.6;">
						<?php _e( 'Cloudflare R2 is an ultra-fast, S3-compatible cloud storage with <strong>zero egress fees</strong>. Store drama episode video files (.mp4 / .m3u8), vertical posters, and media with high-speed global CDN delivery.', 'short-stream-core' ); ?>
					</p>

					<form method="post" action="options.php" style="width:100%;">
						<?php settings_fields( 'short_r2_group' ); ?>
						<h4 style="margin:0 0 16px; font-size:15px; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:8px;"><?php _e( 'R2 Credentials & Endpoint', 'short-stream-core' ); ?></h4>
						<table class="form-table short-modern-table" style="margin-top:0;">
							<tr>
								<th><label for="short_r2_enabled"><?php _e( 'Enable Cloudflare R2', 'short-stream-core' ); ?></label></th>
								<td>
									<label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
										<input type="checkbox" id="short_r2_enabled" name="short_r2_enabled" value="1" <?php checked( '1', $r2_enabled ); ?> style="width:18px; height:18px; border-radius:4px;" />
										<strong style="color:#0f172a; font-size:13.5px;"><?php _e( 'Enable Cloudflare R2 Storage for Episode Uploads and Video Streaming', 'short-stream-core' ); ?></strong>
									</label>
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Allows direct upload and high-speed CDN delivery of short drama episodes and video assets via Cloudflare R2.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_r2_account_id"><?php _e( 'Cloudflare Account ID', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" id="short_r2_account_id" name="short_r2_account_id" value="<?php echo esc_attr( $r2_account_id ); ?>" class="short-modern-form-input" placeholder="e.g. a1b2c3d4e5f67890abcdef1234567890" autocomplete="off" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Your 32-character Cloudflare Account ID (found on the R2 overview page).', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_r2_access_key_id"><?php _e( 'R2 Access Key ID', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" id="short_r2_access_key_id" name="short_r2_access_key_id" value="<?php echo esc_attr( $r2_access_key ); ?>" class="short-modern-form-input" placeholder="e.g. 7f8a9b0c1d2e3f4a5b6c7d8e" autocomplete="off" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'From Cloudflare R2 → Manage R2 API Tokens.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_r2_secret_access_key"><?php _e( 'R2 Secret Access Key', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="password" id="short_r2_secret_access_key" name="short_r2_secret_access_key" value="<?php echo esc_attr( $r2_secret_key ); ?>" class="short-modern-form-input" placeholder="e.g. 9a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b" autocomplete="new-password" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Your R2 API Token Secret Access Key.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_r2_bucket_name"><?php _e( 'R2 Bucket Name', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" id="short_r2_bucket_name" name="short_r2_bucket_name" value="<?php echo esc_attr( $r2_bucket ); ?>" class="short-modern-form-input" placeholder="e.g. short-videos" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'The exact name of your Cloudflare R2 bucket.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
						</table>

						<h4 style="margin:24px 0 16px; font-size:15px; font-weight:700; color:#0f172a; border-bottom:1px solid #f1f5f9; padding-bottom:8px;"><?php _e( 'Delivery & Folder Configuration', 'short-stream-core' ); ?></h4>
						<table class="form-table short-modern-table" style="margin-top:0;">
							<tr>
								<th><label for="short_r2_public_domain"><?php _e( 'Public CDN Domain / R2.dev URL', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="url" id="short_r2_public_domain" name="short_r2_public_domain" value="<?php echo esc_attr( $r2_public_url ); ?>" class="short-modern-form-input" placeholder="https://pub-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.r2.dev or https://media.yourdomain.com" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'Your public bucket URL from <strong>Bucket Settings → Public Access</strong> (either your <code>r2.dev</code> public subdomain or custom custom domain e.g. <code>https://media.yourdomain.com</code>). Videos will be streamed directly from this CDN domain.', 'short-stream-core' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="short_r2_folder"><?php _e( 'Default Asset Folder', 'short-stream-core' ); ?></label></th>
								<td>
									<input type="text" id="short_r2_folder" name="short_r2_folder" value="<?php echo esc_attr( $r2_folder ); ?>" class="short-modern-form-input" placeholder="short" />
									<p style="margin:6px 0 0; font-size:12px; color:#64748b;"><?php _e( 'The root folder where drama videos and assets will be stored (Default: <code>short</code>, same folder structure as other providers).', 'short-stream-core' ); ?></p>
								</td>
							</tr>
						</table>

						<div style="margin-top:20px;">
							<?php submit_button( __( 'Save Cloudflare R2 Settings', 'short-stream-core' ) ); ?>
						</div>
					</form>
				</div>

				<div class="short-settings-card" style="margin-top:20px;">
					<h3 style="margin-top:0; margin-bottom:10px; font-size:15px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
						<span>🔌</span> <?php _e( 'Connection Test', 'short-stream-core' ); ?>
					</h3>
					<p style="margin:0 0 14px; font-size:13px; color:#64748b;">
						<?php _e( 'Test the live connection to your Cloudflare R2 bucket with your configured credentials.', 'short-stream-core' ); ?>
					</p>
					<div style="display:flex; align-items:center; gap:12px;">
						<button type="button" class="button button-secondary" id="btn-test-r2" <?php echo ( empty( $r2_account_id ) || empty( $r2_access_key ) || empty( $r2_secret_key ) || empty( $r2_bucket ) ) ? 'disabled' : ''; ?> style="height:36px; border-radius:6px; font-weight:600;">
							<?php _e( '🔌 Test Cloudflare R2 Connection', 'short-stream-core' ); ?>
						</button>
						<span id="r2-test-result" style="font-weight:600; font-size:13.5px;"></span>
					</div>
				</div>

				<script>
				(function() {
					var btn = document.getElementById('btn-test-r2');
					var result = document.getElementById('r2-test-result');
					if ( ! btn ) return;
					btn.addEventListener('click', function() {
						result.textContent = 'Testing R2 bucket connection…';
						result.style.color = '#666';
						btn.disabled = true;
						fetch('<?php echo esc_url( rest_url( 'short/v1/r2/test' ) ); ?>', {
							method: 'POST',
							headers: {
								'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
								'Content-Type': 'application/json'
							}
						})
						.then(function(r) { return r.json(); })
						.then(function(data) {
							if (data.success) {
								result.textContent = '✅ ' + data.message;
								result.style.color = '#00a32a';
							} else {
								result.textContent = '❌ ' + (data.message || 'Connection failed');
								result.style.color = '#d63638';
							}
						})
						.catch(function(err) {
							result.textContent = '❌ Request failed: ' + err.message;
							result.style.color = '#d63638';
						})
						.finally(function() { btn.disabled = false; });
					});
				})();
				</script>

				<!-- Cloudflare R2 Step-by-Step Setup Guide Box (At Bottom) -->
				<div class="short-settings-card" style="border-left:4px solid #ea580c; background:#fff7ed; margin-top:24px;">
					<div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
						<span style="font-size:22px;">📦</span><h3 style="margin:0; font-size:16px; color:#9a3412; font-weight:800;"><?php _e( 'Cloudflare R2 Object Storage & CDN Integration Guide', 'short-stream-core' ); ?></h3>
					</div>
					<p style="margin:0 0 14px; font-size:13.5px; color:#431407; line-height:1.6;">
						<?php _e( 'Cloudflare R2 is an ultra-fast, S3-compatible cloud storage with <strong>zero egress fees</strong>. Store drama episode video files (.mp4 / .m3u8), vertical posters, and media with high-speed global CDN delivery.', 'short-stream-core' ); ?>
					</p>
					
					<div style="background:#ffffff; border:1px solid #fed7aa; border-radius:8px; padding:16px 20px; margin-top:12px;">
						<h4 style="margin:0 0 10px; color:#9a3412; font-size:13.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;"><?php _e( '⚡ How to Set Up Cloudflare R2 (Step-by-Step):', 'short-stream-core' ); ?></h4>
						<ol style="margin:0 0 16px; padding-left:20px; font-size:13px; color:#334155; line-height:1.7;">
							<li><?php _e( 'In your <a href="https://dash.cloudflare.com/" target="_blank" rel="noopener noreferrer" style="color:#ea580c; font-weight:700;">Cloudflare Dashboard</a> left sidebar, click <strong>Storage & databases → R2 Object Storage</strong>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Click <strong>Create Bucket</strong> and name it (e.g. <code>short-videos</code> or <code>short-dramas</code>).', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Return to the main <strong>R2 Object Storage</strong> page (click <em>R2 Object Storage</em> in the left menu or top breadcrumbs). Look on the <strong>right side / top right</strong> and click <strong>Manage R2 API Tokens</strong>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Click the blue <strong>Create API Token</strong> button. Under <em>Permissions</em>, select <strong>Object Read & Write</strong>, then click <strong>Create API Token</strong> at the bottom. Copy your <strong>Access Key ID</strong> and <strong>Secret Access Key</strong>.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Copy your <strong>Account ID</strong> (found on the right sidebar of the R2 overview page or your Cloudflare URL).', 'short-stream-core' ); ?></li>
							<li><?php _e( '<strong>Enable Public URL:</strong> Click on your bucket name (e.g. <code>short</code>) → Go to the <strong>Settings</strong> tab → Scroll down to <strong>Public Access</strong> → Under <strong>Public Development URL</strong>, click <strong>Enable</strong> (and confirm by clicking <em>Allow / Enable</em>). Copy the generated public URL starting with <code>https://pub-....r2.dev</code> and paste it into the <strong>Public CDN Domain / R2.dev URL</strong> field above.', 'short-stream-core' ); ?></li>
							<li><?php _e( 'Paste all credentials into the form above, check <strong>Enable Cloudflare R2</strong>, click <strong>Save Cloudflare R2 Settings</strong>, and then click <strong>🔌 Test Cloudflare R2 Connection</strong>!', 'short-stream-core' ); ?></li>
						</ol>
						<a href="https://dash.cloudflare.com/" target="_blank" rel="noopener noreferrer" class="button button-primary" style="background:#ea580c; border-color:#c2410c; height:36px; border-radius:6px; font-weight:600; display:inline-flex; align-items:center;">
							<?php _e( '→ Open Cloudflare Dashboard', 'short-stream-core' ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function ajax_flush_cache() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_short_%' OR option_name LIKE '_transient_timeout_short_%'" );
		wp_send_json_success( array( 'message' => __( 'Cache flushed successfully!', 'short-stream-core' ) ) );
	}

	public static function ajax_test_endpoint() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}
		$endpoint = sanitize_text_field( $_POST['endpoint'] ?? '' );
		if ( empty( $endpoint ) ) {
			wp_send_json_error( __( 'Endpoint URL cannot be empty.', 'short-stream-core' ) );
		}

		$clean_ep = strtolower( trim( $endpoint ) );
		$is_shorttv = strpos( $clean_ep, 'shorttv:' ) === 0 || in_array( $clean_ep, array( 'views', 'rating', 'latest', 'episodes', 'hero', 'vip', 'leaderboard', 'popular', 'top_rated' ), true ) || strpos( $clean_ep, 'genre/' ) === 0;

		if ( $is_shorttv ) {
			$args = array(
				'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 100,
			);

			$clean_key = str_replace( 'shorttv:', '', $clean_ep );
			$genre_filter = '';
			if ( strpos( $clean_key, 'genre/' ) === 0 ) {
				$genre_filter = sanitize_title( str_replace( 'genre/', '', $clean_key ) );
			}

			$posts = get_posts( $args );
			$matched = array();

			foreach ( $posts as $p ) {
				$schema = \SHORT\Core\CPT\Video_CPT::get_series_schema( $p->ID );
				$terms = wp_get_post_terms( $p->ID, 'video_genre', array( 'fields' => 'slugs' ) );
				$schema_genres = array_map( 'sanitize_title', (array)( $schema['genres'] ?? array() ) );
				$all_genres = array_unique( array_merge( (array) $terms, (array) $schema_genres ) );

				if ( ! empty( $genre_filter ) ) {
					$has_genre = false;
					// Map CEO to billionaire and vice-versa
					$target_genres = array( $genre_filter );
					if ( $genre_filter === 'ceo' ) $target_genres[] = 'billionaire';
					if ( $genre_filter === 'billionaire' ) $target_genres[] = 'ceo';
					if ( $genre_filter === 'vampire' ) $target_genres[] = 'werewolf';
					if ( $genre_filter === 'werewolf' ) $target_genres[] = 'vampire';

					foreach ( $all_genres as $g ) {
						foreach ( $target_genres as $tg ) {
							if ( $g === $tg || false !== strpos( $g, $tg ) || false !== strpos( $tg, $g ) ) {
								$has_genre = true;
								break 2;
							}
						}
					}
					if ( ! $has_genre ) continue;
				}

				$is_vip = ( get_post_meta( $p->ID, '_shorttv_is_vip', true ) === '1' );
				if ( ! $is_vip && ! empty( $schema['episodes'] ) ) {
					foreach ( (array) $schema['episodes'] as $ep ) {
						if ( ( $ep['access_control']['unlock_type'] ?? '' ) === 'vip' ) {
							$is_vip = true;
							break;
						}
					}
				}

				$views = (int) get_post_meta( $p->ID, '_shorttv_view_count', true );
				$rating = (float) ( get_post_meta( $p->ID, '_shorttv_rating', true ) ?: ( $schema['analytics']['rating'] ?? 9.5 ) );
				$likes = (int) ( get_post_meta( $p->ID, '_shorttv_like_count', true ) ?: ( $schema['analytics']['like_count'] ?? 0 ) );
				$ep_count = count( (array)( $schema['episodes'] ?? array() ) );

				$matched[] = array(
					'id'       => $p->ID,
					'title'    => $p->post_title,
					'views'    => $views,
					'rating'   => $rating,
					'likes'    => $likes,
					'ep_count' => $ep_count,
					'is_vip'   => $is_vip,
					'date'     => get_post_time( 'U', true, $p->ID ),
				);
			}

			if ( $clean_key === 'vip' ) {
				$matched = array_filter( $matched, function( $m ) {
					return ! empty( $m['is_vip'] );
				} );
			}

			if ( $clean_key === 'views' || $clean_key === 'popular' || $clean_key === 'hero' ) {
				usort( $matched, function( $a, $b ) {
					if ( $a['views'] === $b['views'] ) return $b['likes'] <=> $a['likes'];
					return $b['views'] <=> $a['views'];
				} );
			} elseif ( $clean_key === 'rating' || $clean_key === 'top_rated' || $clean_key === 'leaderboard' ) {
				usort( $matched, function( $a, $b ) {
					$score_a = ( $a['rating'] * 100 ) + ( $a['likes'] * 10 ) + $a['views'];
					$score_b = ( $b['rating'] * 100 ) + ( $b['likes'] * 10 ) + $b['views'];
					return $score_b <=> $score_a;
				} );
			} elseif ( $clean_key === 'episodes' || $clean_key === 'binge' ) {
				usort( $matched, function( $a, $b ) {
					return $b['ep_count'] <=> $a['ep_count'];
				} );
			} else {
				usort( $matched, function( $a, $b ) {
					return $b['date'] <=> $a['date'];
				} );
			}

			$count = count( $matched );
			$sample = ! empty( $matched[0] ) ? $matched[0]['title'] : ( ( $clean_key === 'vip' ) ? '0 VIP-exclusive dramas found (Set Episode Access Rule to 👑 VIP in Drama Studio)' : '0 ShortTV dramas found (Add dramas in "🎬 Add New Drama")' );

			wp_send_json_success( array( 'count' => $count, 'sample' => $sample ) );
		}

		wp_send_json_success( array( 'count' => 0, 'sample' => 'Custom Endpoint' ) );
	}

	public static function ajax_save_presets() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}
		if ( isset( $_POST['presets'] ) && is_array( $_POST['presets'] ) ) {
			$clean = array();
			foreach ( $_POST['presets'] as $p ) {
				$clean[] = array(
					'id'       => sanitize_key( $p['id'] ?? '' ),
					'name'     => sanitize_text_field( $p['name'] ?? '' ),
					'endpoint' => sanitize_text_field( $p['endpoint'] ?? '' ),
				);
			}
			update_option( 'short_endpoint_presets', $clean );
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	public static function ajax_reset_presets() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}
		$defaults = self::get_default_presets();
		update_option( 'short_endpoint_presets', $defaults );
		wp_send_json_success( array( 'presets' => $defaults ) );
	}

	public static function ajax_save_sections() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' ); 
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}
		$target = sanitize_key( $_POST['target'] ?? 'home' );
		$opt_map = array(
			'home'                => 'short_homepage_sections',
			'categories'          => 'short_categories_sections',
			'new-popular'         => 'short_new_popular_sections',
			'leaderboard'         => 'short_leaderboard_sections',
			'mobile_leaderboard'  => 'short_mobile_leaderboard_sections',
			'desktop_nav'         => 'short_header_nav_items',
			'mobile_home'         => 'short_mobile_home_sections',
			'mobile_discovery'    => 'short_mobile_discovery_sections',
			'mobile_popular'      => 'short_mobile_popular_sections',
			'mobile_account'      => 'short_bottom_nav_sections',
			'bottom_nav'          => 'short_bottom_nav_sections',
			'series'              => 'short_series_sections',
			'movies'              => 'short_movies_sections',
			'anime'               => 'short_anime_sections',
			'kids'                => 'short_kids_sections',
		);
		$opt_key = $opt_map[ $target ] ?? 'short_homepage_sections';

		if ( isset( $_POST['sections'] ) && is_array( $_POST['sections'] ) ) {
			$clean = array();
			foreach ( $_POST['sections'] as $s ) {
				$clean[] = array(
					'id'       => sanitize_text_field( $s['id'] ?? '' ),
					'title'    => sanitize_text_field( $s['title'] ?? '' ),
					'type'     => sanitize_text_field( $s['type'] ?? 'home' ),
					'layout'   => sanitize_text_field( $s['layout'] ?? 'landscape' ),
					'endpoint' => sanitize_text_field( $s['endpoint'] ?? '' ),
					'limit'    => absint( $s['limit'] ?? 20 ),
					'enabled'  => ! empty( $s['enabled'] ) ? 1 : 0,
				);
			}
			update_option( $opt_key, $clean );
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	public static function ajax_reset_sections() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}
		$target = sanitize_key( $_POST['target'] ?? 'home' );
		$opt_map = array(
			'home'                => 'short_homepage_sections',
			'categories'          => 'short_categories_sections',
			'new-popular'         => 'short_new_popular_sections',
			'leaderboard'         => 'short_leaderboard_sections',
			'mobile_leaderboard'  => 'short_mobile_leaderboard_sections',
			'desktop_nav'         => 'short_header_nav_items',
			'mobile_home'         => 'short_mobile_home_sections',
			'mobile_discovery'    => 'short_mobile_discovery_sections',
			'mobile_popular'      => 'short_mobile_popular_sections',
			'mobile_account'      => 'short_bottom_nav_sections',
			'bottom_nav'          => 'short_bottom_nav_sections',
			'series'              => 'short_series_sections',
			'movies'              => 'short_movies_sections',
			'anime'               => 'short_anime_sections',
			'kids'                => 'short_kids_sections',
		);
		$opt_key = $opt_map[ $target ] ?? 'short_homepage_sections';

		$defaults = self::get_default_sections( $target );
		update_option( $opt_key, $defaults );
		wp_send_json_success( array( 'sections' => $defaults ) );
	}

	/**
	 * Render Section Divisions Bar (Desktop & Mobile Pills + Dynamic Tab & Icon Studio)
	 */
	public static function render_section_divisions_bar( $target = 'home' ) {
		$tabs_config        = self::get_section_tabs_config();
		$desktop_tabs       = ! empty( $tabs_config['desktop'] ) && is_array( $tabs_config['desktop'] ) ? $tabs_config['desktop'] : array();
		$mobile_header_tabs = ! empty( $tabs_config['mobile_header'] ) && is_array( $tabs_config['mobile_header'] ) ? $tabs_config['mobile_header'] : $desktop_tabs;
		$mobile_tabs        = ! empty( $tabs_config['mobile'] ) && is_array( $tabs_config['mobile'] ) ? $tabs_config['mobile'] : array();
		$dashicons          = self::get_available_dashicons();

		// Active state detection
		$mobile_targets = array( 'mobile_home', 'mobile_discover', 'mobile_leaderboard', 'mobile_account', 'bottom_nav' );
		foreach ( $mobile_tabs as $mtab ) {
			if ( ! empty( $mtab['target'] ) ) {
				$mobile_targets[] = $mtab['target'];
			}
		}
		$is_mh_active      = ( 'mobile_header' === $target );
		$is_mb_active      = in_array( $target, $mobile_targets, true );
		$is_desktop_active = ! $is_mh_active && ! $is_mb_active;

		$first_desktop_target = ! empty( $desktop_tabs[0]['target'] ) ? $desktop_tabs[0]['target'] : 'home';
		$first_mobile_target  = ! empty( $mobile_tabs[0]['target'] ) ? $mobile_tabs[0]['target'] : 'mobile_home';

		$mobile_header_cfg = get_option( 'short_mobile_header_config', array(
			'show_logo'    => 1,
			'show_search'  => 1,
			'show_lang'    => 1,
			'show_coins'   => 1,
			'show_mylist'  => 1,
			'show_history' => 0,
		) );
		?>
		<!-- Section Division Tabs: Desktop & Mobile (Modern Style) -->
		<div class="short-modern-divisions-studio">
			<style>
				.dashicons-flame:before,
				.dashicons.dashicons-flame:before {
					content: "" !important;
					display: inline-block !important;
					width: 1em !important;
					height: 1em !important;
					background-color: currentColor !important;
					-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z'/%3E%3C/svg%3E") no-repeat center / contain !important;
					mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3z'/%3E%3C/svg%3E") no-repeat center / contain !important;
					vertical-align: middle !important;
				}
				.dashicons-bookmark:before,
				.dashicons.dashicons-bookmark:before {
					content: "" !important;
					display: inline-block !important;
					width: 1em !important;
					height: 1em !important;
					background-color: currentColor !important;
					-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23000'%3E%3Cpath d='M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z'/%3E%3C/svg%3E") no-repeat center / contain !important;
					mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23000'%3E%3Cpath d='M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z'/%3E%3C/svg%3E") no-repeat center / contain !important;
					vertical-align: middle !important;
				}
				.dashicons-awards:before,
				.dashicons.dashicons-awards:before {
					content: "" !important;
					display: inline-block !important;
					width: 1em !important;
					height: 1em !important;
					background-color: currentColor !important;
					-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23000'%3E%3Cpath d='M6 2h12v6a6 6 0 0 1-6 6 6 6 0 0 1-6-6V2zm14 2h2a2 2 0 0 1 2 2v1a4 4 0 0 1-4 4h0a6.002 6.002 0 0 1-2-1.25V4zm-16 0H2a2 2 0 0 0-2 2v1a4 4 0 0 0 4 4h0c.7-.55 1.38-1.2 2-1.25V4zM10 15.34A6.98 6.98 0 0 1 6 15v1a6 6 0 0 0 5 5.91V24H8v2h8v-2h-3v-2.09A6 6 0 0 0 18 16v-1a6.98 6.98 0 0 1-4 .34V20h-4v-4.66z'/%3E%3C/svg%3E") no-repeat center / contain !important;
					mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23000'%3E%3Cpath d='M6 2h12v6a6 6 0 0 1-6 6 6 6 0 0 1-6-6V2zm14 2h2a2 2 0 0 1 2 2v1a4 4 0 0 1-4 4h0a6.002 6.002 0 0 1-2-1.25V4zm-16 0H2a2 2 0 0 0-2 2v1a4 4 0 0 0 4 4h0c.7-.55 1.38-1.2 2-1.25V4zM10 15.34A6.98 6.98 0 0 1 6 15v1a6 6 0 0 0 5 5.91V24H8v2h8v-2h-3v-2.09A6 6 0 0 0 18 16v-1a6.98 6.98 0 0 1-4 .34V20h-4v-4.66z'/%3E%3C/svg%3E") no-repeat center / contain !important;
					vertical-align: middle !important;
				}
				.dashicons-medal:before,
				.dashicons.dashicons-medal:before {
					content: "" !important;
					display: inline-block !important;
					width: 1em !important;
					height: 1em !important;
					background-color: currentColor !important;
					-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='8' r='7'%3E%3C/circle%3E%3Cpolyline points='8.21 13.89 7 23 12 20 17 23 15.79 13.88'%3E%3C/polyline%3E%3C/svg%3E") no-repeat center / contain !important;
					mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='8' r='7'%3E%3C/circle%3E%3Cpolyline points='8.21 13.89 7 23 12 20 17 23 15.79 13.88'%3E%3C/polyline%3E%3C/svg%3E") no-repeat center / contain !important;
					vertical-align: middle !important;
				}
				.dashicons-list-view:before,
				.dashicons.dashicons-list-view:before {
					content: "" !important;
					display: inline-block !important;
					width: 1em !important;
					height: 1em !important;
					background-color: currentColor !important;
					-webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'/%3E%3C/svg%3E") no-repeat center / contain !important;
					mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'/%3E%3C/svg%3E") no-repeat center / contain !important;
					vertical-align: middle !important;
				}

				.short-modern-divisions-studio {
					margin: 20px 0 24px 0;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				}

				/* Top 3-Way Segmented Control Bar */
				.short-modern-segmented-control {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					gap: 6px;
					background: #f8fafc;
					border: 1px solid #e2e8f0;
					border-radius: 12px;
					padding: 6px;
					box-sizing: border-box;
				}
				.short-seg-btn {
					display: flex;
					align-items: center;
					justify-content: center;
					height: 40px;
					padding: 0 16px;
					border-radius: 9px;
					font-size: 13.5px;
					font-weight: 600;
					color: #64748b;
					text-decoration: none !important;
					background: transparent;
					border: none;
					cursor: pointer;
					transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
					box-sizing: border-box;
					user-select: none;
				}
				.short-seg-btn:hover {
					color: #0f172a;
					background: rgba(255, 255, 255, 0.65);
				}
				.short-seg-btn.is-active {
					background: #ffffff;
					color: #4f46e5;
					font-weight: 700;
					box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
				}

				/* Main Panel Card */
				.short-modern-panel-card {
					background: #ffffff;
					border: 1px solid #e2e8f0;
					border-radius: 14px;
					padding: 22px 24px;
					margin-top: 14px;
					box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
					box-sizing: border-box;
				}

				/* Panel Header */
				.short-panel-header {
					display: flex;
					align-items: center;
					justify-content: space-between;
					gap: 16px;
				}
				.short-panel-header-left {
					display: flex;
					align-items: center;
					gap: 14px;
				}
				.short-panel-icon-box {
					width: 44px;
					height: 44px;
					border-radius: 10px;
					display: flex;
					align-items: center;
					justify-content: center;
					flex-shrink: 0;
				}
				.short-panel-icon-box.theme-purple {
					background: #f5f3ff;
					border: 1px solid #ede9fe;
					color: #7c3aed;
				}
				.short-panel-icon-box.theme-blue {
					background: #eff6ff;
					border: 1px solid #dbeafe;
					color: #2563eb;
				}
				.short-panel-icon-box.theme-green {
					background: #ecfdf5;
					border: 1px solid #d1fae5;
					color: #059669;
				}
				.short-panel-icon-box .dashicons {
					font-size: 20px;
					width: 20px;
					height: 20px;
					line-height: 20px;
				}
				.short-panel-titles {
					display: flex;
					flex-direction: column;
					gap: 3px;
				}
				.short-panel-title {
					margin: 0;
					font-size: 16px;
					font-weight: 800;
					color: #0f172a;
					line-height: 1.2;
				}
				.short-panel-subtitle {
					margin: 0;
					font-size: 13px;
					color: #64748b;
					line-height: 1.3;
				}
				.short-panel-badge {
					font-size: 11px;
					font-weight: 800;
					letter-spacing: 0.6px;
					text-transform: uppercase;
					padding: 4px 10px;
					border-radius: 6px;
					line-height: 1;
				}
				.short-panel-badge.theme-purple {
					background: #fbfaff;
					color: #7c3aed;
					border: 1px solid #ede9fe;
				}
				.short-panel-badge.theme-blue {
					background: #f0f7ff;
					color: #2563eb;
					border: 1px solid #dbeafe;
				}
				.short-panel-badge.theme-green {
					background: #f0fdf4;
					color: #059669;
					border: 1px solid #d1fae5;
				}

				/* Actions / Pills Row */
				.short-panel-actions-row {
					display: flex;
					align-items: center;
					gap: 10px;
					flex-wrap: wrap;
					margin-top: 18px;
				}
				.short-panel-actions-row > .short-modern-action-btn {
					justify-content: center;
				}

				/* Action Pill Card with Switch (Mobile Header) */
				.short-action-pill-card {
					display: inline-flex;
					align-items: center;
					justify-content: space-between;
					gap: 10px;
					flex: 1;
					min-width: 130px;
					background: #ffffff;
					border: 1px solid #e2e8f0;
					border-radius: 10px;
					height: 44px;
					padding: 0 14px;
					box-sizing: border-box;
					transition: all 0.15s ease;
				}
				.short-action-pill-card:hover {
					border-color: #cbd5e1;
					box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
				}
				.short-action-pill-card .dashicons {
					font-size: 17px;
					width: 17px;
					height: 17px;
					display: flex;
					align-items: center;
					justify-content: center;
					color: #475569;
				}
				.short-action-pill-card .pill-label {
					font-size: 13.5px;
					font-weight: 600;
					color: #1e293b;
					white-space: nowrap;
					margin-right: auto;
				}

				/* Action Tab Card (Desktop & Bottom Nav tabs) */
				.short-action-tab-card {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					background: #ffffff;
					border: 1px solid #e2e8f0;
					border-radius: 10px;
					height: 44px;
					padding: 0 15px;
					font-size: 13.5px;
					font-weight: 600;
					color: #334155;
					text-decoration: none !important;
					cursor: pointer;
					transition: all 0.15s ease;
					box-sizing: border-box;
					white-space: nowrap;
				}
				.short-action-tab-card:hover {
					color: #0f172a;
					border-color: #cbd5e1;
					box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
					transform: translateY(-1px);
				}
				.short-action-tab-card .dashicons {
					font-size: 17px;
					width: 17px;
					height: 17px;
					display: flex;
					align-items: center;
					justify-content: center;
					color: #64748b;
				}
				.short-action-tab-card.is-active-tab-blue {
					border-color: #3b82f6;
					background: #eff6ff;
					color: #1d4ed8;
					font-weight: 700;
					box-shadow: 0 2px 8px rgba(37, 99, 235, 0.12);
				}
				.short-action-tab-card.is-active-tab-blue .dashicons {
					color: #2563eb;
				}
				.short-action-tab-card.is-active-tab-green {
					border-color: #10b981;
					background: #ecfdf5;
					color: #047857;
					font-weight: 700;
					box-shadow: 0 2px 8px rgba(16, 185, 129, 0.12);
				}
				.short-action-tab-card.is-active-tab-green .dashicons {
					color: #059669;
				}

				/* Sleek Toggle Switch (Matches media_1790898608673.png) */
				.short-modern-switch {
					position: relative;
					display: inline-block;
					width: 38px;
					height: 22px;
					cursor: pointer;
					margin: 0;
					flex-shrink: 0;
				}
				.short-modern-switch input {
					opacity: 0;
					width: 0;
					height: 0;
					margin: 0;
					position: absolute;
				}
				.short-switch-slider {
					position: absolute;
					top: 0; left: 0; right: 0; bottom: 0;
					background-color: #e2e8f0;
					border-radius: 9999px;
					transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
				}
				.short-switch-slider:before {
					position: absolute;
					content: "";
					height: 18px;
					width: 18px;
					left: 2px;
					bottom: 2px;
					background-color: #ffffff;
					border-radius: 50%;
					transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
					box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
				}
				.short-modern-switch input:checked + .short-switch-slider {
					background-color: #5c4ee5;
				}
				.short-modern-switch input:checked + .short-switch-slider:before {
					transform: translateX(16px);
				}

				/* Modern Action Button (Customize bar / Manage tabs) */
				.short-modern-action-btn {
					display: inline-flex;
					align-items: center;
					gap: 6px;
					height: 44px;
					padding: 0 16px;
					border-radius: 10px;
					font-weight: 700;
					font-size: 13px;
					text-decoration: none !important;
					cursor: pointer;
					transition: all 0.15s ease;
					box-sizing: border-box;
					white-space: nowrap;
					border: 1px solid transparent;
				}
				.short-modern-action-btn:hover {
					transform: translateY(-1px);
				}
				.short-modern-action-btn.theme-purple {
					background: #fbfaff;
					border-color: #e0e7ff;
					color: #6366f1;
				}
				.short-modern-action-btn.theme-purple:hover {
					background: #f5f3ff;
					border-color: #c7d2fe;
					color: #4f46e5;
					box-shadow: 0 2px 6px rgba(99, 102, 241, 0.12);
				}
				.short-modern-action-btn.theme-blue {
					background: #f8fbff;
					border-color: #dbeafe;
					color: #2563eb;
				}
				.short-modern-action-btn.theme-blue:hover {
					background: #eff6ff;
					border-color: #bfdbfe;
					color: #1d4ed8;
					box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
				}
				.short-modern-action-btn.theme-green {
					background: #f8fdfa;
					border-color: #d1fae5;
					color: #059669;
				}
				.short-modern-action-btn.theme-green:hover {
					background: #ecfdf5;
					border-color: #a7f3d0;
					color: #047857;
					box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12);
				}
				.short-modern-action-btn .dashicons {
					font-size: 16px;
					width: 16px;
					height: 16px;
					display: flex;
					align-items: center;
					justify-content: center;
				}

				/* Panel Footer */
				.short-panel-footer {
					display: flex;
					align-items: center;
					justify-content: space-between;
					margin-top: 20px;
					padding-top: 14px;
					border-top: 1px solid #f1f5f9;
				}
				.short-panel-footer-left {
					display: inline-flex;
					align-items: center;
					gap: 8px;
					font-size: 12.5px;
					color: #64748b;
				}
				.short-live-indicator-dot {
					width: 7px;
					height: 7px;
					border-radius: 50%;
					background: #10b981;
					display: inline-block;
					box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
					transition: all 0.2s ease;
				}
				.short-reset-defaults-btn {
					font-size: 12.5px;
					font-weight: 600;
					color: #64748b;
					text-decoration: none !important;
					cursor: pointer;
					background: none;
					border: none;
					padding: 0;
					transition: color 0.15s ease;
				}
				.short-reset-defaults-btn:hover {
					color: #0f172a;
					text-decoration: underline !important;
				}
			</style>

			<!-- Top 3-Way Segmented Control Bar -->
			<div class="short-modern-segmented-control">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=short-homepage&target=' . $first_desktop_target ) ); ?>" class="short-seg-btn <?php echo $is_desktop_active ? 'is-active' : ''; ?>">
					<?php _e( 'Desktop header', 'short-stream-core' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=short-homepage&target=mobile_header' ) ); ?>" class="short-seg-btn <?php echo $is_mh_active ? 'is-active' : ''; ?>">
					<?php _e( 'Mobile header', 'short-stream-core' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=short-homepage&target=' . $first_mobile_target ) ); ?>" class="short-seg-btn <?php echo $is_mb_active ? 'is-active' : ''; ?>">
					<?php _e( 'Mobile bottom menu', 'short-stream-core' ); ?>
				</a>
			</div>

			<!-- 1. PANEL: MOBILE HEADER (Matches media_1790898608673.png) -->
			<div id="panel-mobile-header" class="short-modern-panel-card" style="<?php echo $is_mh_active ? '' : 'display:none;'; ?>">
				<div class="short-panel-header">
					<div class="short-panel-header-left">
						<div class="short-panel-icon-box theme-purple">
							<span class="dashicons dashicons-smartphone"></span>
						</div>
						<div class="short-panel-titles">
							<h3 class="short-panel-title"><?php _e( 'Mobile header', 'short-stream-core' ); ?></h3>
							<p class="short-panel-subtitle"><?php _e( 'Quick-access actions for mobile visitors', 'short-stream-core' ); ?></p>
						</div>
					</div>
					<div class="short-panel-header-right">
						<span class="short-panel-badge theme-purple"><?php _e( 'MOBILE', 'short-stream-core' ); ?></span>
					</div>
				</div>

				<div class="short-panel-actions-row">
					<!-- Brand logo -->
					<div class="short-action-pill-card">
						<span class="dashicons dashicons-format-image"></span>
						<span class="pill-label"><?php _e( 'Brand logo', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_logo" <?php checked( ! empty( $mobile_header_cfg['show_logo'] ) ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>

					<!-- Search -->
					<div class="short-action-pill-card">
						<span class="dashicons dashicons-search"></span>
						<span class="pill-label"><?php _e( 'Search', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_search" <?php checked( ! empty( $mobile_header_cfg['show_search'] ) ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>

					<!-- Language -->
					<div class="short-action-pill-card">
						<span class="dashicons dashicons-admin-site-alt3"></span>
						<span class="pill-label"><?php _e( 'Language', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_lang" <?php checked( ! empty( $mobile_header_cfg['show_lang'] ) ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>

					<!-- Coins -->
					<div class="short-action-pill-card">
						<span class="dashicons dashicons-money-alt" style="color:#d97706;"></span>
						<span class="pill-label"><?php _e( 'Coins', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_coins" <?php checked( ! empty( $mobile_header_cfg['show_coins'] ) ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>

					<!-- My List -->
					<div class="short-action-pill-card">
						<span class="dashicons dashicons-bookmark" style="color:#2563eb;"></span>
						<span class="pill-label"><?php _e( 'My List', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_mylist" <?php checked( ! empty( $mobile_header_cfg['show_mylist'] ) ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>

					<!-- History -->
					<div class="short-action-pill-card">
						<span class="dashicons dashicons-backup" style="color:#8b5cf6;"></span>
						<span class="pill-label"><?php _e( 'History', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_history" <?php checked( ! empty( $mobile_header_cfg['show_history'] ) ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>
					<div class="short-action-pill-card">
						<span class="dashicons dashicons-tag" style="color:#8b5cf6;"></span>
						<span class="pill-label"><?php _e( 'Genre Tags', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_genre_labels" <?php checked( ! empty( $mobile_header_cfg['show_genre_labels'] ) ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>

					<!-- Customize bar Button -->
					<a href="<?php echo $is_mh_active ? '#short-mh-customizer-box' : esc_url( admin_url( 'admin.php?page=short-homepage&target=mobile_header' ) ); ?>" class="short-modern-action-btn theme-purple">
						<span class="dashicons dashicons-admin-generic"></span>
						<span><?php _e( 'Customize bar', 'short-stream-core' ); ?></span>
					</a>
				</div>

				<div class="short-panel-footer">
					<div class="short-panel-footer-left">
						<span class="short-live-indicator-dot"></span>
						<span class="short-mh-status-text"><?php _e( 'Changes are saved automatically', 'short-stream-core' ); ?></span>
					</div>
					<div class="short-panel-footer-right">
						<button type="button" class="short-reset-defaults-btn"><?php _e( 'Reset defaults', 'short-stream-core' ); ?></button>
					</div>
				</div>
			</div>

			<!-- 2. PANEL: DESKTOP HEADER -->
			<div id="panel-desktop" class="short-modern-panel-card" style="<?php echo $is_desktop_active ? '' : 'display:none;'; ?>">
				<div class="short-panel-header">
					<div class="short-panel-header-left">
						<div class="short-panel-icon-box theme-blue">
							<span class="dashicons dashicons-desktop"></span>
						</div>
						<div class="short-panel-titles">
							<h3 class="short-panel-title"><?php _e( 'Desktop header', 'short-stream-core' ); ?></h3>
							<p class="short-panel-subtitle"><?php _e( 'Navigation tabs and content blocks for desktop viewers', 'short-stream-core' ); ?></p>
						</div>
					</div>
					<div class="short-panel-header-right">
						<span class="short-panel-badge theme-blue"><?php _e( 'DESKTOP', 'short-stream-core' ); ?></span>
					</div>
				</div>

				<div class="short-panel-actions-row">
					<?php foreach ( $desktop_tabs as $dtab ) : 
						$d_target  = $dtab['target'] ?? 'home';
						$d_title   = $dtab['title'] ?? 'Tab';
						$d_icon    = ! empty( $dtab['icon'] ) ? $dtab['icon'] : ( ( false !== stripos( $d_title, 'leader' ) || false !== stripos( $d_target, 'leader' ) ) ? 'dashicons-awards' : 'dashicons-category' );
						$is_active = ( $target === $d_target );
					?>
						<a href="<?php echo admin_url( 'admin.php?page=short-homepage&target=' . esc_attr( $d_target ) ); ?>" 
						   class="short-action-tab-card <?php echo $is_active ? 'is-active-tab-blue' : ''; ?>">
							<span class="dashicons <?php echo esc_attr( $d_icon ); ?>"></span>
							<span><?php echo esc_html( $d_title ); ?></span>
						</a>
					<?php endforeach; ?>

					<button type="button" class="short-modern-action-btn theme-blue short-toggle-edit-titles-btn">
						<span class="dashicons dashicons-admin-generic"></span>
						<span><?php _e( 'Manage tabs & icons', 'short-stream-core' ); ?></span>
					</button>
				</div>

				<div class="short-panel-footer">
					<div class="short-panel-footer-left">
						<span class="short-live-indicator-dot"></span>
						<span><?php _e( 'Click any tab above to customize its content blocks', 'short-stream-core' ); ?></span>
					</div>
					<div class="short-panel-footer-right">
						<a href="<?php echo admin_url( 'admin.php?page=short-homepage&target=home' ); ?>" class="short-reset-defaults-btn"><?php _e( 'Reset to Home view', 'short-stream-core' ); ?></a>
					</div>
				</div>
			</div>

			<!-- 3. PANEL: MOBILE BOTTOM MENU -->
			<div id="panel-mobile-bottom" class="short-modern-panel-card" style="<?php echo $is_mb_active ? '' : 'display:none;'; ?>">
				<div class="short-panel-header">
					<div class="short-panel-header-left">
						<div class="short-panel-icon-box theme-green">
							<span class="dashicons dashicons-smartphone"></span>
						</div>
						<div class="short-panel-titles">
							<h3 class="short-panel-title"><?php _e( 'Mobile bottom menu', 'short-stream-core' ); ?></h3>
							<p class="short-panel-subtitle"><?php _e( 'Floating bottom navigation bar for handheld screens', 'short-stream-core' ); ?></p>
						</div>
					</div>
					<div class="short-panel-header-right">
						<span class="short-panel-badge theme-green"><?php _e( 'BOTTOM NAV', 'short-stream-core' ); ?></span>
					</div>
				</div>

				<div class="short-panel-actions-row">
					<?php foreach ( $mobile_tabs as $mtab ) : 
						$m_target  = $mtab['target'] ?? 'mobile_home';
						$m_title   = $mtab['title'] ?? 'Tab';
						$m_icon    = ! empty( $mtab['icon'] ) ? $mtab['icon'] : ( ( false !== stripos( $m_title, 'leader' ) || false !== stripos( $m_target, 'leader' ) ) ? 'dashicons-awards' : 'dashicons-admin-home' );
						$is_active = ( $target === $m_target || ( in_array( $m_target, array( 'mobile_account', 'bottom_nav' ) ) && in_array( $target, array( 'mobile_account', 'bottom_nav' ) ) ) );
					?>
						<a href="<?php echo admin_url( 'admin.php?page=short-homepage&target=' . esc_attr( $m_target ) ); ?>" 
						   class="short-action-tab-card <?php echo $is_active ? 'is-active-tab-green' : ''; ?>">
							<span class="dashicons <?php echo esc_attr( $m_icon ); ?>"></span>
							<span><?php echo esc_html( $m_title ); ?></span>
						</a>
					<?php endforeach; ?>

					<button type="button" class="short-modern-action-btn theme-green short-toggle-edit-titles-btn">
						<span class="dashicons dashicons-admin-generic"></span>
						<span><?php _e( 'Manage bottom tabs', 'short-stream-core' ); ?></span>
					</button>
				</div>

				<div class="short-panel-footer">
					<div class="short-panel-footer-left">
						<span class="short-live-indicator-dot"></span>
						<span><?php _e( 'Click any tab above to customize its content blocks', 'short-stream-core' ); ?></span>
					</div>
					<div class="short-panel-footer-right">
						<a href="<?php echo admin_url( 'admin.php?page=short-homepage&target=mobile_home' ); ?>" class="short-reset-defaults-btn"><?php _e( 'Reset to Mobile Home', 'short-stream-core' ); ?></a>
					</div>
				</div>
			</div>

			<script>
			jQuery(document).ready(function($){
				// 1. Live Mobile Header Auto-Save
				$('.short-live-mh-switch').on('change', function(){
					var dot = $('.short-live-indicator-dot');
					var statusText = $('.short-mh-status-text');
					dot.css({ background: '#eab308', boxShadow: '0 0 0 2px rgba(234, 179, 8, 0.25)' });
					statusText.text('<?php echo esc_js( __( 'Saving changes...', 'short-stream-core' ) ); ?>');

					var payload = {
						action: 'short_save_mobile_header_config',
						nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>',
						show_logo: $('.short-live-mh-switch[data-key="show_logo"]').is(':checked') ? 1 : 0,
						show_search: $('.short-live-mh-switch[data-key="show_search"]').is(':checked') ? 1 : 0,
						show_lang: $('.short-live-mh-switch[data-key="show_lang"]').is(':checked') ? 1 : 0,
						show_coins: $('.short-live-mh-switch[data-key="show_coins"]').is(':checked') ? 1 : 0,
						show_mylist: $('.short-live-mh-switch[data-key="show_mylist"]').is(':checked') ? 1 : 0,
						show_history: $('.short-live-mh-switch[data-key="show_history"]').is(':checked') ? 1 : 0,
						show_genre_labels: $('.short-live-mh-switch[data-key="show_genre_labels"]').is(':checked') ? 1 : 0
					};

					// Sync with bottom form if present
					$('#mh-input-logo').prop('checked', payload.show_logo == 1);
					$('#mh-input-search').prop('checked', payload.show_search == 1);
					$('#mh-input-lang').prop('checked', payload.show_lang == 1);
					$('#mh-input-coins').prop('checked', payload.show_coins == 1);
					$('#mh-input-mylist').prop('checked', payload.show_mylist == 1);
					$('#mh-input-history').prop('checked', payload.show_history == 1);
					$('#mh-input-genre-labels').prop('checked', payload.show_genre_labels == 1);

					$.post(ajaxurl, payload, function(res){
						if (res.success) {
							dot.css({ background: '#10b981', boxShadow: '0 0 0 2px rgba(16, 185, 129, 0.25)' });
							statusText.text('<?php echo esc_js( __( 'Changes are saved automatically', 'short-stream-core' ) ); ?>');
						} else {
							dot.css({ background: '#ef4444', boxShadow: '0 0 0 2px rgba(239, 68, 68, 0.25)' });
							statusText.text('<?php echo esc_js( __( 'Error saving changes', 'short-stream-core' ) ); ?>');
						}
					}).fail(function(){
						dot.css({ background: '#ef4444', boxShadow: '0 0 0 2px rgba(239, 68, 68, 0.25)' });
						statusText.text('<?php echo esc_js( __( 'Network error saving changes', 'short-stream-core' ) ); ?>');
					});
				});

				// 3. Reset Defaults via AJAX
				$('.short-reset-defaults-btn').on('click', function(e){
					e.preventDefault();
					if (!confirm('<?php echo esc_js( __( 'Reset mobile header items to default settings?', 'short-stream-core' ) ); ?>')) return;

					var dot = $('.short-live-indicator-dot');
					var statusText = $('.short-mh-status-text');
					dot.css({ background: '#eab308', boxShadow: '0 0 0 2px rgba(234, 179, 8, 0.25)' });
					statusText.text('<?php echo esc_js( __( 'Resetting to defaults...', 'short-stream-core' ) ); ?>');

					$.post(ajaxurl, {
						action: 'short_reset_mobile_header_config',
						nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>'
					}, function(res){
						if (res.success && res.data && res.data.config) {
							var cfg = res.data.config;
							$('.short-live-mh-switch[data-key="show_logo"]').prop('checked', cfg.show_logo == 1);
							$('.short-live-mh-switch[data-key="show_search"]').prop('checked', cfg.show_search == 1);
							$('.short-live-mh-switch[data-key="show_lang"]').prop('checked', cfg.show_lang == 1);
							$('.short-live-mh-switch[data-key="show_coins"]').prop('checked', cfg.show_coins == 1);
							$('.short-live-mh-switch[data-key="show_mylist"]').prop('checked', cfg.show_mylist == 1);
							$('.short-live-mh-switch[data-key="show_history"]').prop('checked', cfg.show_history == 1);
							$('.short-live-mh-switch[data-key="show_genre_labels"]').prop('checked', cfg.show_genre_labels == 1);

							// Sync with bottom form if present
							$('#mh-input-logo').prop('checked', cfg.show_logo == 1);
							$('#mh-input-search').prop('checked', cfg.show_search == 1);
							$('#mh-input-lang').prop('checked', cfg.show_lang == 1);
							$('#mh-input-coins').prop('checked', cfg.show_coins == 1);
							$('#mh-input-mylist').prop('checked', cfg.show_mylist == 1);
							$('#mh-input-history').prop('checked', cfg.show_history == 1);
							$('#mh-input-genre-labels').prop('checked', cfg.show_genre_labels == 1);

							dot.css({ background: '#10b981', boxShadow: '0 0 0 2px rgba(16, 185, 129, 0.25)' });
							statusText.text('<?php echo esc_js( __( 'Changes are saved automatically', 'short-stream-core' ) ); ?>');
						}
					});
				});
			});
			</script>
		</div>
			<!-- Expandable Tabs & Icons Studio Editor -->
			<style>
				.short-edit-tab-titles-panel {
					background: linear-gradient(145deg, #f8fafc, #f1f5f9) !important;
					border-color: #cbd5e1 !important;
					border-radius: 16px !important;
					box-shadow: 0 10px 30px rgba(15,23,42,0.04) !important;
					padding: 24px !important;
					margin-top: 16px !important;
				}
				.short-edit-tab-titles-panel > div[style*="display:grid"] {
					display: grid !important;
					grid-template-columns: 1fr 1fr !important;
					gap: 24px !important;
				}
				.short-edit-tab-titles-panel > div[style*="display:grid"] > div:nth-child(1) {
					grid-column: 1 / -1;
				}
				@media (max-width: 900px) {
					.short-edit-tab-titles-panel > div[style*="display:grid"] {
						grid-template-columns: 1fr !important;
					}
					.short-edit-tab-titles-panel > div[style*="display:grid"] > div:nth-child(1) {
						grid-column: 1 / -1;
					}
				}
				.short-edit-tab-titles-panel > div[style*="display:grid"] > div {
					border-radius: 14px !important;
					border: 1px solid #e2e8f0 !important;
					box-shadow: 0 4px 12px rgba(15,23,42,0.03) !important;
					padding: 20px !important;
				}
				#desktop-tabs-list {
					display: grid !important;
					grid-template-columns: 1fr 1fr !important;
					gap: 16px !important;
				}
				@media (max-width: 900px) {
					#desktop-tabs-list {
						grid-template-columns: 1fr !important;
					}
				}
				.short-edit-tab-titles-panel label:has(.short-quick-mh-toggle) {
					padding: 12px 16px !important;
					font-size: 13.5px !important;
					border-radius: 8px !important;
					margin-bottom: 2px !important;
				}
				.short-edit-tab-titles-panel .short-quick-mh-toggle {
					transform: scale(1.2);
					margin: 0 !important;
					cursor: pointer;
				}
				.tab-config-row {
					background: #ffffff !important;
					border: 1px solid #e2e8f0 !important;
					border-radius: 10px !important;
					transition: all 0.2s ease;
					box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
					padding: 10px 14px !important;
					position: relative;
					z-index: 1;
					display: grid !important;
					grid-template-columns: 32px 220px 1fr 1fr 30px !important;
				}
				.tab-config-row:has(.is-open) {
					z-index: 50 !important;
				}
				.tab-config-row:hover {
					border-color: #cbd5e1 !important;
					box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
					transform: translateY(-1px);
				}
				.btn-remove-tab-row {
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					padding: 0 !important;
					line-height: 1 !important;
					width: 30px !important;
					height: 30px !important;
					min-width: 30px !important;
					min-height: 30px !important;
					box-sizing: border-box !important;
				}
				.tab-config-row input[type="text"], .tab-config-row select {
					border: 1px solid transparent !important;
					background: #f8fafc !important;
					border-radius: 6px !important;
					box-shadow: none !important;
					transition: all 0.2s ease;
					font-weight: 500;
					color: #334155 !important;
				}
				.tab-config-row input[type="text"]:hover, .tab-config-row select:hover {
					background: #f1f5f9 !important;
				}
				.tab-config-row input[type="text"]:focus, .tab-config-row select:focus {
					background: #ffffff !important;
					border-color: #93c5fd !important;
					box-shadow: 0 0 0 3px rgba(59,130,246,0.1) !important;
				}
				.btn-add-desktop-tab, .btn-add-mobile-header-tab, .btn-add-mobile-tab {
					border-radius: 20px !important;
					height: 32px !important;
					padding: 0 16px !important;
					transition: all 0.2s ease !important;
				}
				.btn-add-desktop-tab:hover, .btn-add-mobile-header-tab:hover, .btn-add-mobile-tab:hover {
					background: #dbeafe !important;
					border-color: #60a5fa !important;
				}
			</style>
			<div class="short-edit-tab-titles-panel" style="display:none; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:18px 20px; margin-top:8px;">
				<div style="font-weight:800; font-size:14px; color:#0f172a; margin-bottom:14px; display:flex; align-items:center; gap:8px;">
					<span class="dashicons dashicons-admin-generic" style="color:#2563eb; font-size:20px; width:20px; height:20px;"></span>
					<span><?php _e( 'Tabs & Icons Studio — Add More Tabs & Customize Icons', 'short-stream-core' ); ?></span>
				</div>
				<p style="font-size:12px; color:#64748b; margin:-8px 0 16px 0;">
					<?php _e( 'Add unlimited tabs for Desktop and Mobile navigations, select streaming Dashicons with live visual previews, and configure their section target slugs and URL routes.', 'short-stream-core' ); ?>
				</p>

				<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(360px, 1fr)); gap:18px;">
					<!-- Desktop: Top Header Tabs Repeater -->
					<div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
						<div style="font-weight:700; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:#2563eb; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
							<span style="display:inline-flex; align-items:center; gap:6px;">
								<span class="dashicons dashicons-desktop"></span> <?php _e( 'Desktop: Top Header', 'short-stream-core' ); ?>
							</span>
							<button type="button" class="button button-small btn-add-desktop-tab" style="display:inline-flex !important; align-items:center !important; justify-content:center !important; gap:4px !important; font-weight:700 !important; color:#2563eb !important; border-color:#93c5fd !important; background:#eff6ff !important; height:28px !important; line-height:1 !important; padding:0 10px !important; cursor:pointer;">
								+ <?php _e( 'Add Desktop Tab', 'short-stream-core' ); ?>
							</button>
						</div>

						<div id="desktop-tabs-list" style="display:flex; flex-direction:column; gap:10px;">
							<?php foreach ( $desktop_tabs as $idx => $dtab ) : ?>
								<div class="tab-config-row" data-type="desktop" style="display:grid; grid-template-columns:32px 140px 1fr 1fr 28px; gap:8px; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 10px; border-radius:6px;">
									<div style="display:flex; align-items:center; justify-content:center;">
										<span class="tab-icon-preview dashicons <?php echo esc_attr( $dtab['icon'] ?? 'dashicons-category' ); ?>" style="font-size:20px; width:20px; height:20px; color:#2563eb;"></span>
									</div>
									<div>
										<select class="widefat tab-icon-select short-modern-select" style="font-size:12px; height:30px;">
											<?php foreach ( $dashicons as $d_cls => $d_name ) : ?>
												<option value="<?php echo esc_attr( $d_cls ); ?>" <?php selected( $dtab['icon'] ?? '', $d_cls ); ?>><?php echo esc_html( $d_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div>
										<input type="text" class="widefat tab-title-input" value="<?php echo esc_attr( $dtab['title'] ?? '' ); ?>" placeholder="Tab Title" style="font-size:12px; height:30px;" />
									</div>
									<div>
										<input type="text" class="widefat tab-target-input" value="<?php echo esc_attr( $dtab['target'] ?? '' ); ?>" placeholder="Slug (e.g. home)" style="font-size:11px; height:30px; font-family:monospace;" />
									</div>
									<div style="text-align:center;">
										<button type="button" class="button short-btn-icon-action btn-remove-tab-row" title="Remove" style="width:26px; height:26px; line-height:24px; color:#ef4444; border-color:#fca5a5;">✕</button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Mobile: Top Header Bar Elements -->
					<div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.03); display:flex; flex-direction:column;">
						<div style="font-weight:700; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:#8b5cf6; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
							<span style="display:inline-flex; align-items:center; gap:6px;">
								<span class="dashicons dashicons-smartphone"></span> <?php _e( 'Mobile: Top Header Bar', 'short-stream-core' ); ?>
							</span>
							<a href="<?php echo admin_url( 'admin.php?page=short-homepage&target=mobile_header' ); ?>" class="button button-small" style="color:#8b5cf6; border-color:#ddd6fe; background:#f5f3ff; font-weight:700;">
								<?php _e( 'Full Studio ↗', 'short-stream-core' ); ?>
							</a>
						</div>
						<p style="font-size:12px; color:#64748b; margin:0 0 12px 0;">
							<?php _e( 'Single compact 48px top bar on smartphones. Toggle which elements appear:', 'short-stream-core' ); ?>
						</p>

						<div style="display:flex; flex-direction:column; gap:8px;">
							<label style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:6px; font-weight:600; font-size:12.5px; cursor:pointer;">
								<span style="display:flex; align-items:center; gap:8px;">
									<span class="dashicons dashicons-format-image" style="color:#8b5cf6;"></span>
									<span><?php _e( 'Brand Logo', 'short-stream-core' ); ?></span>
								</span>
								<input type="checkbox" class="short-quick-mh-toggle" data-key="show_logo" <?php checked( ! empty( $mobile_header_cfg['show_logo'] ) ); ?> />
							</label>

							<label style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:6px; font-weight:600; font-size:12.5px; cursor:pointer;">
								<span style="display:flex; align-items:center; gap:8px;">
									<span class="dashicons dashicons-search" style="color:#8b5cf6;"></span>
									<span><?php _e( 'Search Icon Button', 'short-stream-core' ); ?></span>
								</span>
								<input type="checkbox" class="short-quick-mh-toggle" data-key="show_search" <?php checked( ! empty( $mobile_header_cfg['show_search'] ) ); ?> />
							</label>

							<label style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:6px; font-weight:600; font-size:12.5px; cursor:pointer;">
								<span style="display:flex; align-items:center; gap:8px;">
									<span class="dashicons dashicons-admin-site-alt3" style="color:#8b5cf6;"></span>
									<span><?php _e( 'Language Selector', 'short-stream-core' ); ?></span>
								</span>
								<input type="checkbox" class="short-quick-mh-toggle" data-key="show_lang" <?php checked( ! empty( $mobile_header_cfg['show_lang'] ) ); ?> />
							</label>

							<label style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:6px; font-weight:600; font-size:12.5px; cursor:pointer;">
								<span style="display:flex; align-items:center; gap:8px;">
									<span>💰</span>
									<span><?php _e( 'Coins Wallet Pill', 'short-stream-core' ); ?></span>
								</span>
								<input type="checkbox" class="short-quick-mh-toggle" data-key="show_coins" <?php checked( ! empty( $mobile_header_cfg['show_coins'] ) ); ?> />
							</label>

							<label style="display:flex; align-items:center; justify-content:space-between; background:#eff6ff; border:1px solid #bfdbfe; padding:8px 12px; border-radius:6px; font-weight:700; font-size:12.5px; cursor:pointer; color:#1e40af;">
								<span style="display:flex; align-items:center; gap:8px;">
									<span class="dashicons dashicons-bookmark" style="color:#2563eb;"></span>
									<span><?php _e( 'My List Bookmark', 'short-stream-core' ); ?></span>
								</span>
								<input type="checkbox" class="short-quick-mh-toggle" data-key="show_mylist" <?php checked( ! empty( $mobile_header_cfg['show_mylist'] ) ); ?> />
							</label>

							<label style="display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 12px; border-radius:6px; font-weight:600; font-size:12.5px; cursor:pointer;">
								<span style="display:flex; align-items:center; gap:8px;">
									<span class="dashicons dashicons-backup" style="color:#8b5cf6;"></span>
									<span><?php _e( 'Watch History', 'short-stream-core' ); ?></span>
								</span>
								<input type="checkbox" class="short-quick-mh-toggle" data-key="show_history" <?php checked( ! empty( $mobile_header_cfg['show_history'] ) ); ?> />
							</label>
						</div>
					</div>

					<!-- Mobile: Bottom Nav Tabs Repeater -->
					<div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
						<div style="font-weight:700; font-size:13px; text-transform:uppercase; letter-spacing:0.5px; color:#10b981; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between;">
							<span style="display:inline-flex; align-items:center; gap:6px;">
								<span class="dashicons dashicons-smartphone"></span> <?php _e( 'Mobile: Bottom Nav', 'short-stream-core' ); ?>
							</span>
							<button type="button" class="button button-small btn-add-mobile-tab" style="display:inline-flex !important; align-items:center !important; justify-content:center !important; gap:4px !important; font-weight:700 !important; color:#10b981 !important; border-color:#a7f3d0 !important; background:#ecfdf5 !important; height:28px !important; line-height:1 !important; padding:0 10px !important; cursor:pointer;">
								+ <?php _e( 'Add Bottom Nav Tab', 'short-stream-core' ); ?>
							</button>
						</div>

						<div id="mobile-tabs-list" style="display:flex; flex-direction:column; gap:10px;">
							<?php foreach ( $mobile_tabs as $idx => $mtab ) : ?>
								<div class="tab-config-row" data-type="mobile" style="display:grid; grid-template-columns:32px 140px 1fr 1fr 28px; gap:8px; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 10px; border-radius:6px;">
									<div style="display:flex; align-items:center; justify-content:center;">
										<span class="tab-icon-preview dashicons <?php echo esc_attr( $mtab['icon'] ?? 'dashicons-admin-home' ); ?>" style="font-size:20px; width:20px; height:20px; color:#10b981;"></span>
									</div>
									<div>
										<select class="widefat tab-icon-select short-modern-select" style="font-size:12px; height:30px;">
											<?php foreach ( $dashicons as $d_cls => $d_name ) : ?>
												<option value="<?php echo esc_attr( $d_cls ); ?>" <?php selected( $mtab['icon'] ?? '', $d_cls ); ?>><?php echo esc_html( $d_name ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div>
										<input type="text" class="widefat tab-title-input" value="<?php echo esc_attr( $mtab['title'] ?? '' ); ?>" placeholder="Tab Title" style="font-size:12px; height:30px;" />
									</div>
									<div>
										<input type="text" class="widefat tab-target-input" value="<?php echo esc_attr( $mtab['target'] ?? '' ); ?>" placeholder="Slug (e.g. mobile_home)" style="font-size:11px; height:30px; font-family:monospace;" />
									</div>
									<div style="text-align:center;">
										<button type="button" class="button short-btn-icon-action btn-remove-tab-row" title="Remove" style="width:26px; height:26px; line-height:24px; color:#ef4444; border-color:#fca5a5;">✕</button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div style="margin-top:16px; display:flex; align-items:center; gap:12px; justify-content:flex-end; border-top:1px solid #e2e8f0; padding-top:14px;">
					<span class="tab-titles-save-status" style="font-weight:600; font-size:12.5px;"></span>
					<button type="button" class="button short-cancel-edit-titles-btn" style="height:36px !important; line-height:34px !important; padding:0 14px !important;"><?php _e( 'Cancel', 'short-stream-core' ); ?></button>
					<button type="button" class="button button-primary short-save-tab-titles-btn" style="display:inline-flex !important; align-items:center !important; justify-content:center !important; gap:6px !important; font-weight:700 !important; font-size:13px !important; height:36px !important; line-height:1 !important; padding:0 18px !important; cursor:pointer;">
						<span class="dashicons dashicons-saved" style="font-size:17px !important; width:17px !important; height:17px !important; line-height:17px !important; display:inline-flex !important; align-items:center !important; justify-content:center !important; margin:0 !important; vertical-align:middle !important;"></span>
						<span style="display:inline-flex; align-items:center; line-height:1;"><?php _e( 'Save All Tabs & Icons', 'short-stream-core' ); ?></span>
					</button>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($){
			var availableDashicons = <?php echo json_encode( $dashicons ); ?>;

			// Toggle panel
			$(document).on('click', '.short-toggle-edit-titles-btn, .short-cancel-edit-titles-btn', function(e){
				e.preventDefault();
				$('.short-edit-tab-titles-panel').slideToggle(200);
			});

			var iconPresetMap = {
				'dashicons-admin-home': { title: 'Home', target: 'home' },
				'dashicons-category': { title: 'Categories', target: 'categories' },
				'dashicons-star-filled': { title: 'New and Popular', target: 'new-popular' },
				'dashicons-awards': { title: 'Leaderboard', target: 'leaderboard' },
				'dashicons-medal': { title: 'Leaderboard', target: 'leaderboard' },
				'dashicons-flame': { title: 'Trending', target: 'trending' },
				'dashicons-bookmark': { title: 'My List', target: 'my-list' },
				'dashicons-list-view': { title: 'My List', target: 'my-list' },
				'dashicons-playlist-video': { title: 'Short TV', target: 'shorttv' },
				'dashicons-video-alt3': { title: 'Discovery', target: 'shorttv' },
				'dashicons-admin-users': { title: 'Account', target: 'account' },
				'dashicons-clock': { title: 'History', target: 'history' }
			};

			// Icon dropdown change listener -> updates live preview & auto-fills title/target if default
			$(document).on('change', '.tab-icon-select', function(){
				var icon = $(this).val();
				var row = $(this).closest('.tab-config-row');
				var preview = row.find('.tab-icon-preview');
				preview.attr('class', 'tab-icon-preview dashicons ' + icon);

				var titleInput = row.find('.tab-title-input');
				var targetInput = row.find('.tab-target-input');
				var currentTitle = (titleInput.val() || '').trim();
				var currentTarget = (targetInput.val() || '').trim();

				if (iconPresetMap[icon] && (!currentTitle || currentTitle === 'New Tab' || currentTitle === 'New Mobile Tab' || currentTitle === 'Tab' || currentTitle === 'Home' || currentTarget.indexOf('tab_') === 0 || currentTarget.indexOf('mobile_') === 0)) {
					var preset = iconPresetMap[icon];
					titleInput.val(preset.title);
					if (row.data('type') === 'mobile') {
						targetInput.val('mobile_' + preset.target.replace('mobile_', ''));
					} else {
						targetInput.val(preset.target);
					}
				}
			});

			// Add Desktop Tab
			$(document).on('click', '.btn-add-desktop-tab', function(e){
				e.preventDefault();
				var optionsHtml = '';
				$.each(availableDashicons, function(cls, lbl){
					optionsHtml += '<option value="' + cls + '">' + lbl + '</option>';
				});
				var ts = Date.now();
				var newRow = `
				<div class="tab-config-row" data-type="desktop" style="display:grid; grid-template-columns:32px 140px 1fr 1fr 28px; gap:8px; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 10px; border-radius:6px;">
					<div style="display:flex; align-items:center; justify-content:center;">
						<span class="tab-icon-preview dashicons dashicons-category" style="font-size:20px; width:20px; height:20px; color:#2563eb;"></span>
					</div>
					<div>
						<select class="widefat tab-icon-select short-modern-select" style="font-size:12px; height:30px;">
							${optionsHtml}
						</select>
					</div>
					<div>
						<input type="text" class="widefat tab-title-input" value="New Tab" placeholder="Tab Title" style="font-size:12px; height:30px;" />
					</div>
					<div>
						<input type="text" class="widefat tab-target-input" value="tab_${ts}" placeholder="Slug" style="font-size:11px; height:30px; font-family:monospace;" />
					</div>
					<div style="text-align:center;">
						<button type="button" class="button short-btn-icon-action btn-remove-tab-row" title="Remove" style="width:26px; height:26px; line-height:24px; color:#ef4444; border-color:#fca5a5;">✕</button>
					</div>
				</div>`;
				$('#desktop-tabs-list').append(newRow); initCustomDropdowns();
			});

			// Add Mobile: Top Header Tab
			$(document).on('click', '.btn-add-mobile-header-tab', function(e){
				e.preventDefault();
				var optionsHtml = '';
				$.each(availableDashicons, function(cls, lbl){
					optionsHtml += '<option value="' + cls + '">' + lbl + '</option>';
				});
				var ts = Date.now();
				var newRow = `
				<div class="tab-config-row" data-type="mobile_header" style="display:grid; grid-template-columns:32px 140px 1fr 1fr 28px; gap:8px; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 10px; border-radius:6px;">
					<div style="display:flex; align-items:center; justify-content:center;">
						<span class="tab-icon-preview dashicons dashicons-category" style="font-size:20px; width:20px; height:20px; color:#8b5cf6;"></span>
					</div>
					<div>
						<select class="widefat tab-icon-select short-modern-select" style="font-size:12px; height:30px;">
							${optionsHtml}
						</select>
					</div>
					<div>
						<input type="text" class="widefat tab-title-input" value="New Tab" placeholder="Tab Title" style="font-size:12px; height:30px;" />
					</div>
					<div>
						<input type="text" class="widefat tab-target-input" value="tab_${ts}" placeholder="Slug" style="font-size:11px; height:30px; font-family:monospace;" />
					</div>
					<div style="text-align:center;">
						<button type="button" class="button short-btn-icon-action btn-remove-tab-row" title="Remove" style="width:26px; height:26px; line-height:24px; color:#ef4444; border-color:#fca5a5;">✕</button>
					</div>
				</div>`;
				$('#mobile-header-tabs-list').append(newRow); initCustomDropdowns();
			});

			// Add Mobile: Bottom Nav Tab
			$(document).on('click', '.btn-add-mobile-tab', function(e){
				e.preventDefault();
				var optionsHtml = '';
				$.each(availableDashicons, function(cls, lbl){
					optionsHtml += '<option value="' + cls + '">' + lbl + '</option>';
				});
				var ts = Date.now();
				var newRow = `
				<div class="tab-config-row" data-type="mobile" style="display:grid; grid-template-columns:32px 140px 1fr 1fr 28px; gap:8px; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; padding:8px 10px; border-radius:6px;">
					<div style="display:flex; align-items:center; justify-content:center;">
						<span class="tab-icon-preview dashicons dashicons-smartphone" style="font-size:20px; width:20px; height:20px; color:#10b981;"></span>
					</div>
					<div>
						<select class="widefat tab-icon-select short-modern-select" style="font-size:12px; height:30px;">
							${optionsHtml}
						</select>
					</div>
					<div>
						<input type="text" class="widefat tab-title-input" value="New Mobile Tab" placeholder="Tab Title" style="font-size:12px; height:30px;" />
					</div>
					<div>
						<input type="text" class="widefat tab-target-input" value="mobile_${ts}" placeholder="Slug" style="font-size:11px; height:30px; font-family:monospace;" />
					</div>
					<div style="text-align:center;">
						<button type="button" class="button short-btn-icon-action btn-remove-tab-row" title="Remove" style="width:26px; height:26px; line-height:24px; color:#ef4444; border-color:#fca5a5;">✕</button>
					</div>
				</div>`;
				$('#mobile-tabs-list').append(newRow); initCustomDropdowns();
			});

			// Remove Tab Row
			$(document).on('click', '.btn-remove-tab-row', function(e){
				e.preventDefault();
				var list = $(this).closest('#desktop-tabs-list, #mobile-header-tabs-list, #mobile-tabs-list');
				if(list.find('.tab-config-row').length <= 1){
					alert('You must keep at least one tab.');
					return;
				}
				$(this).closest('.tab-config-row').remove();
			});

			// Auto-suggest slug from tab title
			$(document).on('input', '.tab-title-input', function(){
				var row = $(this).closest('.tab-config-row');
				var targetInput = row.find('.tab-target-input');
				var currentTarget = targetInput.val();
				if(!currentTarget || currentTarget.indexOf('tab_') === 0 || (currentTarget.indexOf('mobile_') === 0 && currentTarget.length > 15)){
					var slug = $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
					if(row.data('type') === 'mobile' && slug && slug.indexOf('mobile_') !== 0){
						slug = 'mobile_' + slug;
					}
					if(slug){
						targetInput.val(slug);
					}
				}
			});

			// Save All Tabs & Icons
			$(document).on('click', '.short-save-tab-titles-btn', function(e){
				e.preventDefault();
				var btn = $(this).prop('disabled', true);
				var status = $('.tab-titles-save-status').html('<span style="color:#64748b;">Saving tabs & icons...</span>');
				var desktopItems = [];
				$('#desktop-tabs-list .tab-config-row').each(function(){
					var row = $(this);
					desktopItems.push({
						icon: row.find('.tab-icon-select').val() || 'dashicons-category',
						title: row.find('.tab-title-input').val() || 'Tab',
						target: row.find('.tab-target-input').val() || 'home'
					});
				});
				var mobileHeaderItems = [];
				$('#mobile-header-tabs-list .tab-config-row').each(function(){
					var row = $(this);
					mobileHeaderItems.push({
						icon: row.find('.tab-icon-select').val() || 'dashicons-category',
						title: row.find('.tab-title-input').val() || 'Tab',
						target: row.find('.tab-target-input').val() || 'home'
					});
				});
				var mobileItems = [];
				$('#mobile-tabs-list .tab-config-row').each(function(){
					var row = $(this);
					mobileItems.push({
						icon: row.find('.tab-icon-select').val() || 'dashicons-smartphone',
						title: row.find('.tab-title-input').val() || 'Mobile Tab',
						target: row.find('.tab-target-input').val() || 'mobile_home'
					});
				});

				$.post(ajaxurl, {
					action: 'short_save_section_tabs_config',
					nonce: '<?php echo wp_create_nonce("short_admin_nonce"); ?>',
					desktop: JSON.stringify(desktopItems),
					mobile_header: JSON.stringify(mobileHeaderItems),
					mobile: JSON.stringify(mobileItems)
				}, function(res){
					btn.prop('disabled', false);
					if(res.success){
						status.html('<span style="color:green;">✔ Tabs and icons saved! Reloading...</span>');
						setTimeout(function(){
							location.reload();
						}, 600);
					} else {
						status.html('<span style="color:red;">✘ Error saving tabs</span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Available Dashicons for Section Tabs
	 */
	public static function get_available_dashicons() {
		return array(
			'dashicons-admin-home'      => '🏠 Home',
			'dashicons-category'        => '📁 Categories / Genres',
			'dashicons-star-filled'     => '⭐ Star / New & Popular',
			'dashicons-flame'           => '🔥 Flame / Trending',
			'dashicons-bookmark'        => '🔖 Bookmark / Save',
			'dashicons-list-view'       => '📋 List View / My List',
			'dashicons-playlist-video'  => '🎞️ Playlist / Short TV',
			'dashicons-video-alt3'      => '🎬 Video / Discovery / Shorts',
			'dashicons-format-video'    => '📺 TV / Mini-Series',
			'dashicons-awards'          => '🏆 Leaderboard / Top Ranked',
			'dashicons-medal'           => '🥇 Leaderboard / Top 10',
			'dashicons-heart'           => '💖 Heart / Romance / Favorites',
			'dashicons-tickets-alt'     => '🎁 Gift / Rewards / Coins',
			'dashicons-gift'            => '🎁 Gift Box / Rewards',
			'dashicons-shield'          => '👑 Crown / Shield / Premium',
			'dashicons-superhero'       => '⚡ Lightning / Action',
			'dashicons-admin-users'     => '👤 User / Account',
			'dashicons-clock'           => '🕒 Clock / Watch History',
			'dashicons-visibility'      => '👁️ Views / Popular',
			'dashicons-search'          => '🔍 Search / Explore',
			'dashicons-tag'             => '🏷️ Tag / Custom Genre',
			'dashicons-external'        => '🚀 Rocket / Releases',
			'dashicons-smiley'          => '😊 Smiley / Anime / Comedy',
			'dashicons-book-alt'        => '📖 Story / Book / Novel',
			'dashicons-grid-view'       => '▦ Grid / Catalog',
			'dashicons-dashboard'       => '🧭 Compass / Studio',
		);
	}

	/**
	 * Get Section Tabs Full Configuration (Desktop & Mobile)
	 */
	public static function get_section_tabs_config() {
		$defaults = array(
			'desktop' => array(
				array( 'target' => 'home',        'title' => __( 'Home', 'short-stream-core' ),           'icon' => 'dashicons-admin-home',  'url' => '/' ),
				array( 'target' => 'categories',  'title' => __( 'Discover', 'short-stream-core' ),       'icon' => 'dashicons-category',    'url' => '/genre/' ),
				array( 'target' => 'new-popular', 'title' => __( 'New and Popular', 'short-stream-core' ),'icon' => 'dashicons-star-filled', 'url' => '/new-popular/' ),
				array( 'target' => 'leaderboard', 'title' => __( 'Leaderboard', 'short-stream-core' ),    'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
				array( 'target' => 'my-list',     'title' => __( 'My List', 'short-stream-core' ),        'icon' => 'dashicons-list-view',   'url' => '/my-list/' ),
			),
			'mobile_header' => array(
				array( 'target' => 'home',        'title' => __( 'Home', 'short-stream-core' ),           'icon' => 'dashicons-admin-home',  'url' => '/' ),
				array( 'target' => 'categories',  'title' => __( 'Discover', 'short-stream-core' ),       'icon' => 'dashicons-category',    'url' => '/genre/' ),
				array( 'target' => 'new-popular', 'title' => __( 'New and Popular', 'short-stream-core' ),'icon' => 'dashicons-star-filled', 'url' => '/new-popular/' ),
				array( 'target' => 'leaderboard', 'title' => __( 'Leaderboard', 'short-stream-core' ),    'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
				array( 'target' => 'my-list',     'title' => __( 'My List', 'short-stream-core' ),        'icon' => 'dashicons-list-view',   'url' => '/my-list/' ),
			),
			'mobile'  => array(
				array( 'target' => 'mobile_home',        'title' => __( 'Home', 'short-stream-core' ),        'icon' => 'dashicons-admin-home',  'url' => '/' ),
				array( 'target' => 'mobile_discovery',   'title' => __( 'Discover', 'short-stream-core' ),    'icon' => 'dashicons-video-alt3',  'url' => '/genre/' ),
				array( 'target' => 'mobile_rewards',     'title' => __( 'Rewards', 'short-stream-core' ),     'icon' => 'dashicons-tickets-alt', 'url' => '/reward/' ),
				array( 'target' => 'mobile_leaderboard', 'title' => __( 'Ranking', 'short-stream-core' ),     'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
				array( 'target' => 'mobile_account',     'title' => __( 'Account', 'short-stream-core' ),     'icon' => 'dashicons-admin-users', 'url' => '/account/' ),
			),
		);

		$saved = get_option( 'short_section_tabs_config', array() );
		if ( ! empty( $saved ) && is_array( $saved ) && ( ! empty( $saved['desktop'] ) || ! empty( $saved['mobile'] ) || ! empty( $saved['mobile_header'] ) ) ) {
			$desktop       = ! empty( $saved['desktop'] ) && is_array( $saved['desktop'] ) ? $saved['desktop'] : $defaults['desktop'];
			$mobile_header = ! empty( $saved['mobile_header'] ) && is_array( $saved['mobile_header'] ) ? $saved['mobile_header'] : $desktop;
			$mobile        = ! empty( $saved['mobile'] ) && is_array( $saved['mobile'] )   ? $saved['mobile']  : $defaults['mobile'];
			$has_rewards_tab = false;
			foreach ( $mobile as $m_chk ) {
				if ( in_array( $m_chk['target'] ?? '', array( 'mobile_rewards', 'rewards', 'reward' ), true ) ) {
					$has_rewards_tab = true;
					break;
				}
			}
			if ( ! $has_rewards_tab && count( $mobile ) >= 2 ) {
				array_splice( $mobile, 2, 0, array( array(
					'target' => 'mobile_rewards',
					'title'  => __( 'Rewards', 'short-stream-core' ),
					'icon'   => 'dashicons-tickets-alt',
					'url'    => '/reward/',
				) ) );
				$saved['mobile'] = $mobile;
				update_option( 'short_section_tabs_config', $saved );
			}
			foreach ( $desktop as &$dt ) {
				if ( empty( $dt['icon'] ) ) {
					$t_lower = strtolower( ( $dt['title'] ?? '' ) . ' ' . ( $dt['target'] ?? '' ) );
					if ( false !== strpos( $t_lower, 'leader' ) || false !== strpos( $t_lower, 'rank' ) || false !== strpos( $t_lower, 'top' ) ) {
						$dt['icon'] = 'dashicons-awards';
					} elseif ( false !== strpos( $t_lower, 'home' ) ) {
						$dt['icon'] = 'dashicons-admin-home';
					} elseif ( false !== strpos( $t_lower, 'cat' ) || false !== strpos( $t_lower, 'disc' ) || false !== strpos( $t_lower, 'genre' ) ) {
						$dt['icon'] = 'dashicons-category';
					} elseif ( false !== strpos( $t_lower, 'pop' ) || false !== strpos( $t_lower, 'star' ) || false !== strpos( $t_lower, 'new' ) ) {
						$dt['icon'] = 'dashicons-star-filled';
					} else {
						$dt['icon'] = 'dashicons-category';
					}
				}
			}
			unset( $dt );
			foreach ( $mobile_header as &$mht ) {
				if ( empty( $mht['icon'] ) ) {
					$t_lower = strtolower( ( $mht['title'] ?? '' ) . ' ' . ( $mht['target'] ?? '' ) );
					if ( false !== strpos( $t_lower, 'leader' ) || false !== strpos( $t_lower, 'rank' ) || false !== strpos( $t_lower, 'top' ) ) {
						$mht['icon'] = 'dashicons-awards';
					} elseif ( false !== strpos( $t_lower, 'home' ) ) {
						$mht['icon'] = 'dashicons-admin-home';
					} elseif ( false !== strpos( $t_lower, 'cat' ) || false !== strpos( $t_lower, 'disc' ) || false !== strpos( $t_lower, 'genre' ) ) {
						$mht['icon'] = 'dashicons-category';
					} elseif ( false !== strpos( $t_lower, 'pop' ) || false !== strpos( $t_lower, 'star' ) || false !== strpos( $t_lower, 'new' ) ) {
						$mht['icon'] = 'dashicons-star-filled';
					} else {
						$mht['icon'] = 'dashicons-category';
					}
				}
			}
			unset( $mht );
			foreach ( $mobile as &$mt ) {
				if ( empty( $mt['icon'] ) ) {
					$t_lower = strtolower( ( $mt['title'] ?? '' ) . ' ' . ( $mt['target'] ?? '' ) );
					if ( false !== strpos( $t_lower, 'leader' ) || false !== strpos( $t_lower, 'rank' ) ) {
						$mt['icon'] = 'dashicons-awards';
					} elseif ( false !== strpos( $t_lower, 'reward' ) || false !== strpos( $t_lower, 'gift' ) || false !== strpos( $t_lower, 'coin' ) ) {
						$mt['icon'] = 'dashicons-tickets-alt';
					} elseif ( false !== strpos( $t_lower, 'home' ) ) {
						$mt['icon'] = 'dashicons-admin-home';
					} elseif ( false !== strpos( $t_lower, 'disc' ) ) {
						$mt['icon'] = 'dashicons-video-alt3';
					} elseif ( false !== strpos( $t_lower, 'pop' ) || false !== strpos( $t_lower, 'flame' ) ) {
						$mt['icon'] = 'dashicons-flame';
					} else {
						$mt['icon'] = 'dashicons-smartphone';
					}
				}
			}
			unset( $mt );
			return array(
				'desktop'       => $desktop,
				'mobile_header' => $mobile_header,
				'mobile'        => $mobile,
			);
		}

		$old_titles = get_option( 'short_section_tab_titles', array() );
		if ( ! empty( $old_titles ) && is_array( $old_titles ) ) {
			if ( ! empty( $old_titles['desktop_home'] ) ) $defaults['desktop'][0]['title'] = $old_titles['desktop_home'];
			if ( ! empty( $old_titles['desktop_categories'] ) ) $defaults['desktop'][1]['title'] = $old_titles['desktop_categories'];
			if ( ! empty( $old_titles['desktop_new_popular'] ) ) $defaults['desktop'][2]['title'] = $old_titles['desktop_new_popular'];
			if ( ! empty( $old_titles['mobile_home'] ) ) $defaults['mobile'][0]['title'] = $old_titles['mobile_home'];
			if ( ! empty( $old_titles['mobile_discovery'] ) ) $defaults['mobile'][1]['title'] = $old_titles['mobile_discovery'];
			if ( ! empty( $old_titles['mobile_popular'] ) ) $defaults['mobile'][2]['title'] = $old_titles['mobile_popular'];
			if ( ! empty( $old_titles['mobile_account'] ) ) $defaults['mobile'][3]['title'] = $old_titles['mobile_account'];
		}

		return $defaults;
	}

	/**
	 * Get Custom Section Tab Titles (Compat)
	 */
	public static function get_section_tab_titles() {
		$config = self::get_section_tabs_config();
		$titles = array(
			'desktop_home'        => $config['desktop'][0]['title'] ?? 'Home',
			'desktop_categories'  => $config['desktop'][1]['title'] ?? 'Categories',
			'desktop_new_popular' => $config['desktop'][2]['title'] ?? 'New and Popular',
			'mobile_home'         => $config['mobile'][0]['title'] ?? 'Home',
			'mobile_discovery'    => $config['mobile'][1]['title'] ?? 'Discovery',
			'mobile_popular'      => $config['mobile'][2]['title'] ?? 'Popular',
			'mobile_account'      => $config['mobile'][3]['title'] ?? 'Account',
		);
		return $titles;
	}

	/**
	 * AJAX: Save Section Tab Titles (Compat)
	 */
	public static function ajax_save_section_tab_titles() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$titles = array(
			'desktop_home'        => sanitize_text_field( $_POST['desktop_home'] ?? 'Home' ),
			'desktop_categories'  => sanitize_text_field( $_POST['desktop_categories'] ?? 'Categories' ),
			'desktop_new_popular' => sanitize_text_field( $_POST['desktop_new_popular'] ?? 'New and Popular' ),
			'mobile_home'         => sanitize_text_field( $_POST['mobile_home'] ?? 'Home' ),
			'mobile_discovery'    => sanitize_text_field( $_POST['mobile_discovery'] ?? 'Discovery' ),
			'mobile_popular'      => sanitize_text_field( $_POST['mobile_popular'] ?? 'Popular' ),
			'mobile_account'      => sanitize_text_field( $_POST['mobile_account'] ?? 'Account' ),
		);

		update_option( 'short_section_tab_titles', $titles );
		wp_send_json_success( array(
			'message' => __( 'Tab titles updated successfully!', 'short-stream-core' ),
			'titles'  => $titles,
		) );
	}

	/**
	 * AJAX: Save Section Tabs Config (Dynamic Repeater with Icons & Slugs)
	 */
	public static function ajax_save_section_tabs_config() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$raw_desktop       = isset( $_POST['desktop'] ) ? json_decode( wp_unslash( $_POST['desktop'] ), true ) : array();
		$raw_mobile_header = isset( $_POST['mobile_header'] ) ? json_decode( wp_unslash( $_POST['mobile_header'] ), true ) : array();
		$raw_mobile        = isset( $_POST['mobile'] )  ? json_decode( wp_unslash( $_POST['mobile'] ), true )  : array();

		$clean_desktop = array();
		if ( is_array( $raw_desktop ) ) {
			foreach ( $raw_desktop as $item ) {
				$title = sanitize_text_field( $item['title'] ?? '' );
				if ( empty( $title ) ) continue;
				$target = sanitize_key( $item['target'] ?? sanitize_title( $title ) );
				if ( empty( $target ) ) $target = 'tab_' . wp_generate_password( 6, false, false );
				$icon = sanitize_html_class( $item['icon'] ?? 'dashicons-category' );
				$url  = sanitize_text_field( $item['url'] ?? '' );
				$clean_desktop[] = array(
					'target' => $target,
					'title'  => $title,
					'icon'   => $icon,
					'url'    => $url,
				);
			}
		}

		$clean_mobile_header = array();
		if ( is_array( $raw_mobile_header ) ) {
			foreach ( $raw_mobile_header as $item ) {
				$title = sanitize_text_field( $item['title'] ?? '' );
				if ( empty( $title ) ) continue;
				$target = sanitize_key( $item['target'] ?? sanitize_title( $title ) );
				if ( empty( $target ) ) $target = 'tab_' . wp_generate_password( 6, false, false );
				$icon = sanitize_html_class( $item['icon'] ?? 'dashicons-category' );
				$url  = sanitize_text_field( $item['url'] ?? '' );
				$clean_mobile_header[] = array(
					'target' => $target,
					'title'  => $title,
					'icon'   => $icon,
					'url'    => $url,
				);
			}
		}

		$clean_mobile = array();
		if ( is_array( $raw_mobile ) ) {
			foreach ( $raw_mobile as $item ) {
				$title = sanitize_text_field( $item['title'] ?? '' );
				if ( empty( $title ) ) continue;
				$target = sanitize_key( $item['target'] ?? sanitize_title( $title ) );
				if ( empty( $target ) ) $target = 'mobile_' . wp_generate_password( 6, false, false );
				$icon = sanitize_html_class( $item['icon'] ?? 'dashicons-category' );
				$url  = sanitize_text_field( $item['url'] ?? '' );
				$clean_mobile[] = array(
					'target' => $target,
					'title'  => $title,
					'icon'   => $icon,
					'url'    => $url,
				);
			}
		}

		if ( empty( $clean_desktop ) ) {
			$defaults = self::get_section_tabs_config();
			$clean_desktop = $defaults['desktop'];
		}
		if ( empty( $clean_mobile_header ) ) {
			$clean_mobile_header = $clean_desktop;
		}
		if ( empty( $clean_mobile ) ) {
			$defaults = self::get_section_tabs_config();
			$clean_mobile = $defaults['mobile'];
		}

		$config = array(
			'desktop'       => $clean_desktop,
			'mobile_header' => $clean_mobile_header,
			'mobile'        => $clean_mobile,
		);

		update_option( 'short_section_tabs_config', $config );

		// Update legacy title map
		$titles = array(
			'desktop_home'        => $clean_desktop[0]['title'] ?? 'Home',
			'desktop_categories'  => $clean_desktop[1]['title'] ?? 'Categories',
			'desktop_new_popular' => $clean_desktop[2]['title'] ?? 'New and Popular',
			'mobile_home'         => $clean_mobile[0]['title'] ?? 'Home',
			'mobile_discovery'    => $clean_mobile[1]['title'] ?? 'Discovery',
			'mobile_popular'      => $clean_mobile[2]['title'] ?? 'Popular',
			'mobile_account'      => $clean_mobile[3]['title'] ?? 'Account',
		);
		update_option( 'short_section_tab_titles', $titles );

		wp_send_json_success( array(
			'message' => __( 'Tabs and icons saved successfully!', 'short-stream-core' ),
			'config'  => $config,
		) );
	}

	/**
	 * AJAX: Save Mobile Top Header Configuration
	 */
	public static function ajax_save_mobile_header_config() {
		if ( ! check_ajax_referer( 'short_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$defaults = array(
			'show_logo'         => 1,
			'show_search'       => 1,
			'show_lang'         => 1,
			'show_coins'        => 1,
			'show_mylist'       => 1,
			'show_history'      => 0,
			'show_genre_labels' => 1,
			'element_order'     => array( 'logo', 'search', 'lang', 'coins', 'mylist', 'history' ),
		);

		$config = get_option( 'short_mobile_header_config', $defaults );
		if ( ! is_array( $config ) ) {
			$config = $defaults;
		}

		// Support saving drag-and-drop element order
		if ( isset( $_POST['element_order'] ) ) {
			$order_raw = $_POST['element_order'];
			if ( is_string( $order_raw ) ) {
				$decoded = json_decode( wp_unslash( $order_raw ), true );
				if ( is_array( $decoded ) ) {
					$order_raw = $decoded;
				} else {
					$order_raw = explode( ',', $order_raw );
				}
			}
			if ( is_array( $order_raw ) ) {
				$valid_keys  = array( 'logo', 'search', 'lang', 'coins', 'mylist', 'history' );
				$clean_order = array();
				foreach ( $order_raw as $item ) {
					$item = sanitize_key( $item );
					if ( in_array( $item, $valid_keys, true ) && ! in_array( $item, $clean_order, true ) ) {
						$clean_order[] = $item;
					}
				}
				foreach ( $valid_keys as $vk ) {
					if ( ! in_array( $vk, $clean_order, true ) ) {
						$clean_order[] = $vk;
					}
				}
				$config['element_order'] = $clean_order;
			}
		}

		// Support single key-value update from live AJAX switch
		if ( isset( $_POST['key'] ) ) {
			$key = sanitize_key( $_POST['key'] );
			$val = ! empty( $_POST['val'] ) ? 1 : 0;
			$config[ $key ] = $val;
		} else {
			foreach ( $defaults as $k => $def ) {
				if ( 'element_order' !== $k && isset( $_POST[ $k ] ) ) {
					$config[ $k ] = ! empty( $_POST[ $k ] ) ? 1 : 0;
				}
			}
		}

		update_option( 'short_mobile_header_config', $config );

		wp_send_json_success( array(
			'message' => __( 'Mobile top header configuration saved successfully!', 'short-stream-core' ),
			'config'  => $config,
		) );
	}

	/**
	 * AJAX: Reset Mobile Top Header Configuration to Defaults
	 */
	public static function ajax_reset_mobile_header_config() {
		if ( ! check_ajax_referer( 'short_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$defaults = array(
			'show_logo'         => 1,
			'show_search'       => 1,
			'show_lang'         => 1,
			'show_coins'        => 1,
			'show_mylist'       => 1,
			'show_history'      => 0,
			'show_genre_labels' => 1,
			'element_order'     => array( 'logo', 'search', 'lang', 'coins', 'mylist', 'history' ),
		);

		update_option( 'short_mobile_header_config', $defaults );

		wp_send_json_success( array(
			'message' => __( 'Mobile top header reset to defaults!', 'short-stream-core' ),
			'config'  => $defaults,
		) );
	}

	/**
	 * Render Mobile Top Header Customizer
	 * Allows configuring and reordering the compact 48px top bar on smartphones:
	 * Brand Logo, Search, Language, Coins wallet, My List bookmark, Watch History
	 */
	public static function render_mobile_header_customizer() {
		wp_enqueue_script( 'jquery-ui-sortable' );

		$brand_settings = get_option( 'short_brand_settings', array() );
		$logo_url       = function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : get_template_directory_uri() . '/assets/images/shorttv-logo.svg';
		$brand_name     = ! empty( $brand_settings['brand_name'] ) ? $brand_settings['brand_name'] : get_bloginfo( 'name' );
		$mobile_logo_h  = ! empty( $brand_settings['mobile_logo_height'] ) ? (int) $brand_settings['mobile_logo_height'] : 36;
		$mobile_logo_w  = ! empty( $brand_settings['mobile_logo_width'] ) ? (int) $brand_settings['mobile_logo_width'] : 0;

		$defaults = array(
			'show_logo'         => 1,
			'show_search'       => 1,
			'show_lang'         => 1,
			'show_coins'        => 1,
			'show_mylist'       => 1,
			'show_history'      => 0,
			'show_genre_labels' => 1,
			'element_order'     => array( 'logo', 'search', 'lang', 'coins', 'mylist', 'history' ),
		);

		$mobile_header_cfg = get_option( 'short_mobile_header_config', $defaults );
		if ( ! is_array( $mobile_header_cfg ) || empty( $mobile_header_cfg ) ) {
			$mobile_header_cfg = $defaults;
		}

		// Self-healing: if all settings are 0, restore sensible defaults
		$active_count = 0;
		foreach ( array( 'show_logo', 'show_search', 'show_lang', 'show_coins', 'show_mylist', 'show_history' ) as $k ) {
			if ( ! empty( $mobile_header_cfg[ $k ] ) ) {
				$active_count++;
			}
		}
		if ( 0 === $active_count ) {
			$mobile_header_cfg = $defaults;
			update_option( 'short_mobile_header_config', $defaults );
		}

		$show_logo         = ! empty( $mobile_header_cfg['show_logo'] );
		$show_search       = ! empty( $mobile_header_cfg['show_search'] );
		$show_lang         = ! empty( $mobile_header_cfg['show_lang'] );
		$show_coins        = ! empty( $mobile_header_cfg['show_coins'] );
		$show_mylist       = ! empty( $mobile_header_cfg['show_mylist'] );
		$show_history      = ! empty( $mobile_header_cfg['show_history'] );
		$show_genre_labels = ! empty( $mobile_header_cfg['show_genre_labels'] );

		$default_order = array( 'logo', 'search', 'lang', 'coins', 'mylist', 'history' );
		$element_order = ! empty( $mobile_header_cfg['element_order'] ) && is_array( $mobile_header_cfg['element_order'] ) ? $mobile_header_cfg['element_order'] : $default_order;
		foreach ( $default_order as $k ) {
			if ( ! in_array( $k, $element_order, true ) ) {
				$element_order[] = $k;
			}
		}

		// Metadata map for elements
		$elements_meta = array(
			'logo'    => array(
				'label'      => __( 'Brand Logo', 'short-stream-core' ),
				'icon'       => '<span class="dashicons dashicons-format-image" style="color:#8b5cf6;"></span>',
				'key'        => 'show_logo',
				'is_checked' => $show_logo,
				'bg'         => '#f8fafc',
				'border'     => '#e2e8f0',
				'text_color' => '#1e293b',
			),
			'search'  => array(
				'label'      => __( 'Search', 'short-stream-core' ),
				'icon'       => '<span class="dashicons dashicons-search" style="color:#8b5cf6;"></span>',
				'key'        => 'show_search',
				'is_checked' => $show_search,
				'bg'         => '#f8fafc',
				'border'     => '#e2e8f0',
				'text_color' => '#1e293b',
			),
			'lang'    => array(
				'label'      => __( 'Language', 'short-stream-core' ),
				'icon'       => '<span class="dashicons dashicons-admin-site-alt3" style="color:#8b5cf6;"></span>',
				'key'        => 'show_lang',
				'is_checked' => $show_lang,
				'bg'         => '#f8fafc',
				'border'     => '#e2e8f0',
				'text_color' => '#1e293b',
			),
			'coins'   => array(
				'label'      => __( 'Coins Wallet', 'short-stream-core' ),
				'icon'       => '<span>💰</span>',
				'key'        => 'show_coins',
				'is_checked' => $show_coins,
				'bg'         => '#fffbeb',
				'border'     => '#fde68a',
				'text_color' => '#92400e',
			),
			'mylist'  => array(
				'label'      => __( 'My List', 'short-stream-core' ),
				'icon'       => '<span class="dashicons dashicons-bookmark" style="color:#2563eb;"></span>',
				'key'        => 'show_mylist',
				'is_checked' => $show_mylist,
				'bg'         => '#eff6ff',
				'border'     => '#bfdbfe',
				'text_color' => '#1e40af',
			),
			'history' => array(
				'label'      => __( 'Watch History', 'short-stream-core' ),
				'icon'       => '<span class="dashicons dashicons-backup" style="color:#8b5cf6;"></span>',
				'key'        => 'show_history',
				'is_checked' => $show_history,
				'bg'         => '#f8fafc',
				'border'     => '#e2e8f0',
				'text_color' => '#1e293b',
			),
		);
		?>
		<div class="wrap short-builder-wrap" style="margin: 10px 0 30px 0 !important; padding: 0 !important; box-sizing: border-box !important; max-width: 100% !important;">
			<style>
				#wpcontent, #wpbody-content { padding-right: 10px !important; }
				#wpfooter { display: none !important; }
				.short-modern-divisions-studio {
					margin: 15px 0 20px 0;
					font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				}
				.short-modern-segmented-control {
					display: grid;
					grid-template-columns: repeat(3, 1fr);
					gap: 6px;
					background: #f8fafc;
					border: 1px solid #e2e8f0;
					border-radius: 12px;
					padding: 6px;
					box-sizing: border-box;
				}
				.short-seg-btn {
					display: flex;
					align-items: center;
					justify-content: center;
					height: 40px;
					padding: 0 16px;
					border-radius: 9px;
					font-size: 13.5px;
					font-weight: 600;
					color: #64748b;
					text-decoration: none !important;
					background: transparent;
					border: none;
					cursor: pointer;
					transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
					box-sizing: border-box;
					user-select: none;
				}
				.short-seg-btn:hover {
					color: #0f172a;
					background: rgba(255, 255, 255, 0.65);
				}
				.short-seg-btn.is-active {
					background: #ffffff;
					color: #4f46e5;
					font-weight: 700;
					box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
				}
				.short-sortable-pill {
					cursor: grab;
					user-select: none;
					transition: box-shadow 0.15s ease, transform 0.15s ease;
				}
				.short-sortable-pill:active {
					cursor: grabbing;
				}
				.short-sortable-pill.ui-sortable-helper {
					box-shadow: 0 12px 28px rgba(0,0,0,0.18) !important;
					transform: scale(1.04) rotate(-1deg);
					opacity: 0.95;
					z-index: 99999;
				}
				.short-sortable-placeholder {
					border: 2px dashed #8b5cf6 !important;
					background: rgba(139,92,246,0.06) !important;
					border-radius: 8px !important;
					min-width: 140px;
					height: 42px;
					visibility: visible !important;
				}
				.short-move-btn {
					background: none;
					border: 1px solid #cbd5e1;
					border-radius: 4px;
					padding: 1px 5px;
					cursor: pointer;
					font-size: 11px;
					color: #64748b;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					line-height: 1;
					transition: all 0.15s;
				}
				.short-move-btn:hover {
					background: #ede9fe;
					color: #6d28d9;
					border-color: #c4b5fd;
				}
			</style>

			<!-- Top 3-Way Segmented Control Bar (Uniform across all tabs) -->
			<div class="short-modern-divisions-studio">
				<div class="short-modern-segmented-control">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=short-homepage&target=home' ) ); ?>" class="short-seg-btn">
						<?php _e( 'Desktop header', 'short-stream-core' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=short-homepage&target=mobile_header' ) ); ?>" class="short-seg-btn is-active">
						<?php _e( 'Mobile header', 'short-stream-core' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=short-homepage&target=mobile_home' ) ); ?>" class="short-seg-btn">
						<?php _e( 'Mobile bottom menu', 'short-stream-core' ); ?>
					</a>
				</div>
			</div>

			<!-- Unified Mobile Header Studio Card -->
			<div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:12px; padding:28px; box-shadow:0 2px 8px rgba(0,0,0,0.04); box-sizing:border-box;">
				
				<!-- Studio Header -->
				<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px; margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid #f1f5f9;">
					<div style="display:flex; align-items:center; gap:14px;">
						<div style="width:44px; height:44px; border-radius:12px; background:linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); display:flex; align-items:center; justify-content:center; font-size:22px; color:#ffffff; box-shadow:0 4px 14px rgba(139,92,246,0.35);">
							📱
						</div>
						<div>
							<h2 style="font-size:17px; font-weight:800; margin:0 0 4px 0; color:#0f172a;"><?php _e( 'Mobile Header & Navigation Studio', 'short-stream-core' ); ?></h2>
							<p style="margin:0; font-size:13px; color:#64748b;"><?php _e( 'Drag elements to reorder their position. Toggle items on/off with live smartphone preview.', 'short-stream-core' ); ?></p>
						</div>
					</div>

					<div style="display:flex; align-items:center; gap:10px;">
						<button type="button" class="button button-secondary" id="btn-reset-mh-config" style="font-size:12px; font-weight:600; border-radius:6px;">
							<span class="dashicons dashicons-image-rotate" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span>
							<span><?php _e( 'Reset Order & Defaults', 'short-stream-core' ); ?></span>
						</button>
						<span id="mh-live-save-badge" style="font-size:12.5px; font-weight:700; color:#059669; background:#ecfdf5; border:1px solid #a7f3d0; padding:4px 12px; border-radius:999px; display:inline-flex; align-items:center; gap:5px;">
							<span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981;"></span>
							<span><?php _e( 'Auto-save active', 'short-stream-core' ); ?></span>
						</span>
					</div>
				</div>

				<!-- Live Interactive Smartphone Preview Mockup -->
				<div style="margin-bottom:26px;">
					<div style="font-weight:700; font-size:11.5px; text-transform:uppercase; letter-spacing:0.8px; color:#64748b; margin-bottom:10px; display:flex; align-items:center; gap:6px;">
						<span class="dashicons dashicons-visibility" style="font-size:16px; width:16px; height:16px;"></span>
						<span><?php _e( 'Live Mobile Top Header Bar Preview (Exact Screen View)', 'short-stream-core' ); ?></span>
					</div>
					
					<div style="max-width:420px; background:#0b0b0e; border:2px solid #2e2e38; border-radius:24px; padding:12px 14px 16px 14px; box-shadow:0 12px 32px rgba(0,0,0,0.3); margin:0 auto 12px auto;">
						<!-- Speaker notch -->
						<div style="width:60px; height:4px; background:#2e2e38; border-radius:2px; margin:0 auto 10px auto;"></div>
						
						<!-- Header Bar Mockup (48px) -->
						<div id="short-mh-preview-bar" style="height:48px; background:#121212; border-radius:10px 10px 0 0; display:flex; align-items:center; justify-content:space-between; padding:0 10px; box-sizing:border-box; border:1px solid rgba(255,255,255,0.08); border-bottom:none; gap:6px;">
							<?php
							// Render mockup elements in exact configured element_order
							foreach ( $element_order as $el_key ) {
								if ( 'logo' === $el_key ) : ?>
									<!-- Logo Preview -->
									<div id="mh-prev-logo" data-id="logo" style="display:<?php echo $show_logo ? 'flex' : 'none'; ?>; align-items:center; flex-shrink:0;">
										<img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="height:<?php echo esc_attr( $mobile_logo_h ); ?>px; max-height:30px; max-width:85px; object-fit:contain;" />
									</div>
								<?php elseif ( 'search' === $el_key ) : ?>
									<div id="mh-prev-search" data-id="search" style="display:<?php echo $show_search ? 'inline-flex' : 'none'; ?>; width:26px; height:26px; align-items:center; justify-content:center; color:#ffffff; flex-shrink:0;">
										<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
									</div>
								<?php elseif ( 'lang' === $el_key ) : ?>
									<div id="mh-prev-lang" data-id="lang" style="display:<?php echo $show_lang ? 'inline-flex' : 'none'; ?>; width:26px; height:26px; align-items:center; justify-content:center; color:#ffffff; flex-shrink:0;">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
									</div>
								<?php elseif ( 'coins' === $el_key ) : ?>
									<div id="mh-prev-coins" data-id="coins" style="display:<?php echo $show_coins ? 'inline-flex' : 'none'; ?>; align-items:center; gap:3px; background:linear-gradient(135deg, #e11d48, #be123c); color:#ffffff; font-size:11px; font-weight:800; padding:0 8px; height:24px; border-radius:999px; border:1px solid rgba(255,255,255,0.18); flex-shrink:0;">
										<span>💰</span><span>3785</span>
									</div>
								<?php elseif ( 'mylist' === $el_key ) : ?>
									<div id="mh-prev-mylist" data-id="mylist" style="display:<?php echo $show_mylist ? 'inline-flex' : 'none'; ?>; width:26px; height:26px; align-items:center; justify-content:center; color:#ffffff; flex-shrink:0;" title="My List">
										<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>
									</div>
								<?php elseif ( 'history' === $el_key ) : ?>
									<div id="mh-prev-history" data-id="history" style="display:<?php echo $show_history ? 'inline-flex' : 'none'; ?>; width:26px; height:26px; align-items:center; justify-content:center; color:#ffffff; flex-shrink:0;" title="Watch History">
										<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
									</div>
								<?php endif;
							}
							?>
						</div>

						<!-- Genre Labels Row Mockup (Dynamic from Taxonomy) -->
						<div id="mh-prev-genre-labels" style="display:<?php echo $show_genre_labels ? 'flex' : 'none'; ?>; align-items:center; gap:8px; overflow-x:hidden; padding:6px 12px 6px 12px; white-space:nowrap; background:#121212; border-radius:0 0 10px 10px; border:1px solid rgba(255,255,255,0.08); border-top:none;">
							<?php
							$prev_genres = get_terms( array( 'taxonomy' => 'video_genre', 'hide_empty' => false, 'number' => 8 ) );
							if ( ! empty( $prev_genres ) && ! is_wp_error( $prev_genres ) ) :
								foreach ( $prev_genres as $pg ) : ?>
									<span style="font-size:12px; font-weight:600; color:#94a3b8; padding:2px 4px;"><?php echo esc_html( $pg->name ); ?></span>
								<?php endforeach;
							else : ?>
								<span style="font-size:12px; font-weight:600; color:#94a3b8; padding:2px 4px;">Billionaire</span>
								<span style="font-size:12px; font-weight:600; color:#94a3b8; padding:2px 4px;">CEO</span>
								<span style="font-size:12px; font-weight:600; color:#94a3b8; padding:2px 4px;">Romance</span>
								<span style="font-size:12px; font-weight:600; color:#94a3b8; padding:2px 4px;">Vampire</span>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Drag & Drop Reorderable Header Elements Container -->
				<div style="margin-bottom:24px;">
					<div style="font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:0.8px; color:#64748b; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
						<div style="display:flex; align-items:center; gap:6px;">
							<span class="dashicons dashicons-move" style="font-size:16px; width:16px; height:16px; color:#8b5cf6;"></span>
							<span><?php _e( 'Header Elements Order & Visibility (Drag to Reorder & Live Toggles)', 'short-stream-core' ); ?></span>
						</div>
						<span style="font-size:11px; color:#8b5cf6; font-weight:700; background:#f5f3ff; border:1px solid #ddd6fe; padding:2px 8px; border-radius:999px;">
							✨ Drag cards or use &larr; &rarr; arrows to rearrange order
						</span>
					</div>

					<div id="short-mh-pills-container" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; padding:10px; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; min-height:58px;">
						<?php
						$order_pos = 1;
						foreach ( $element_order as $el_key ) :
							if ( ! isset( $elements_meta[ $el_key ] ) ) continue;
							$meta = $elements_meta[ $el_key ];
						?>
							<div class="short-action-pill-card short-sortable-pill" data-id="<?php echo esc_attr( $el_key ); ?>" style="display:inline-flex; align-items:center; gap:8px; background:<?php echo esc_attr( $meta['bg'] ); ?>; border:1px solid <?php echo esc_attr( $meta['border'] ); ?>; border-radius:8px; padding:7px 12px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
								<!-- Drag Handle -->
								<span class="short-drag-handle" style="cursor:grab; color:#94a3b8; font-size:14px; line-height:1; padding-right:2px;" title="<?php esc_attr_e( 'Drag to move', 'short-stream-core' ); ?>">⋮⋮</span>
								
								<!-- Position Badge -->
								<span class="short-order-badge" style="font-size:10px; font-weight:800; background:#e2e8f0; color:#475569; width:17px; height:17px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center;"><?php echo (int) $order_pos++; ?></span>

								<!-- Icon & Label -->
								<?php echo $meta['icon']; ?>
								<span style="font-size:13px; font-weight:700; color:<?php echo esc_attr( $meta['text_color'] ); ?>;"><?php echo esc_html( $meta['label'] ); ?></span>

								<!-- Left / Right Move Buttons -->
								<div style="display:inline-flex; gap:2px; margin:0 2px;">
									<button type="button" class="short-move-btn short-move-left" title="Move Left">&larr;</button>
									<button type="button" class="short-move-btn short-move-right" title="Move Right">&rarr;</button>
								</div>

								<!-- Live Toggle Switch -->
								<label class="short-modern-switch" style="position:relative; display:inline-block; width:32px; height:17px; margin:0 0 0 4px;">
									<input type="checkbox" class="short-live-mh-switch" data-key="<?php echo esc_attr( $meta['key'] ); ?>" <?php checked( $meta['is_checked'] ); ?> />
									<span class="short-switch-slider"></span>
								</label>
							</div>
						<?php endforeach; ?>
					</div>

					<!-- Separate Genre Labels Row Toggle -->
					<div style="margin-top:12px; display:inline-flex; align-items:center; gap:8px; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:8px; padding:8px 14px;">
						<span class="dashicons dashicons-tag" style="color:#7c3aed;"></span>
						<span style="font-size:13px; font-weight:700; color:#5b21b6;"><?php _e( 'Secondary Genre Tags Bar (Beneath Header)', 'short-stream-core' ); ?></span>
						<label class="short-modern-switch" style="position:relative; display:inline-block; width:34px; height:18px; margin:0 0 0 6px;">
							<input type="checkbox" class="short-live-mh-switch" data-key="show_genre_labels" <?php checked( $show_genre_labels ); ?> />
							<span class="short-switch-slider"></span>
						</label>
					</div>
				</div>

				<!-- Live Dynamic Genres List Studio Box -->
				<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px 20px;">
					<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:12px;">
						<div style="display:flex; align-items:center; gap:8px;">
							<span class="dashicons dashicons-tag" style="color:#7c3aed; font-size:20px; width:20px; height:20px;"></span>
							<span style="font-weight:800; font-size:14px; color:#0f172a;"><?php _e( 'Dynamic WordPress Genre Tags', 'short-stream-core' ); ?></span>
							<span style="font-size:11px; background:#ecfdf5; color:#059669; padding:2px 8px; border-radius:999px; font-weight:800; border:1px solid #a7f3d0;">⚡ Auto-Synced with Dramas</span>
						</div>
						<a href="<?php echo admin_url( 'edit-tags.php?taxonomy=video_genre&post_type=shorttv_video' ); ?>" target="_blank" class="button button-small" style="font-weight:700; color:#7c3aed; border-color:#c4b5fd; background:#ffffff;">
							<?php _e( '+ Add / Edit Genres &rarr;', 'short-stream-core' ); ?>
						</a>
					</div>
					<p style="font-size:12.5px; color:#64748b; margin:0 0 12px 0; line-height:1.5;">
						<?php _e( 'These genres are dynamically fetched from your live WordPress database and automatically display on the mobile genre line in real-time:', 'short-stream-core' ); ?>
					</p>

					<div style="display:flex; flex-wrap:wrap; gap:8px; max-height:160px; overflow-y:auto;">
						<?php
						$db_genres = get_terms( array( 'taxonomy' => 'video_genre', 'hide_empty' => false, 'orderby' => 'count', 'order' => 'DESC' ) );
						if ( ! empty( $db_genres ) && ! is_wp_error( $db_genres ) ) :
							foreach ( $db_genres as $dbg ) : ?>
								<span style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; font-weight:600; color:#334155; background:#ffffff; border:1px solid #cbd5e1; padding:5px 14px; border-radius:999px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
									<span>🏷️ <?php echo esc_html( $dbg->name ); ?></span>
								</span>
							<?php endforeach;
						else : ?>
							<span style="font-size:12px; color:#94a3b8;"><?php _e( 'No genres created yet. Add genres under ShortTV Dramas &rarr; Genres.', 'short-stream-core' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($){
			var saveNonce = '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>';

			function updateBadgesAndPreview() {
				var order = [];
				var previewBar = $('#short-mh-preview-bar');
				
				$('#short-mh-pills-container .short-sortable-pill').each(function(idx){
					var id = $(this).data('id');
					order.push(id);
					$(this).find('.short-order-badge').text(idx + 1);

					// Sync preview bar DOM order
					var prevEl = previewBar.find('[data-id="' + id + '"], #mh-prev-' + id);
					if (prevEl.length) {
						previewBar.append(prevEl);
					}
				});

				return order;
			}

			function saveOrder(order) {
				var badge = $('#mh-live-save-badge');
				badge.html('<span style="color:#d97706;">Saving order...</span>');

				$.post(ajaxurl, {
					action: 'short_save_mobile_header_config',
					nonce: saveNonce,
					element_order: order
				}, function(res){
					if(res.success){
						badge.html('<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;"></span> <span>Order saved</span>');
					} else {
						badge.html('<span style="color:#dc2626;">Error saving</span>');
					}
				}).fail(function(){
					badge.html('<span style="color:#dc2626;">Network error</span>');
				});
			}

			// Initialize jQuery UI Sortable
			if ($.fn.sortable) {
				$('#short-mh-pills-container').sortable({
					items: '.short-sortable-pill',
					handle: '.short-drag-handle',
					placeholder: 'short-sortable-placeholder',
					tolerance: 'pointer',
					opacity: 0.85,
					update: function() {
						var newOrder = updateBadgesAndPreview();
						saveOrder(newOrder);
					}
				});
			}

			// Move Left Button
			$(document).on('click', '.short-move-left', function(e){
				e.preventDefault();
				var card = $(this).closest('.short-sortable-pill');
				var prev = card.prev('.short-sortable-pill');
				if (prev.length) {
					card.insertBefore(prev);
					var newOrder = updateBadgesAndPreview();
					saveOrder(newOrder);
				}
			});

			// Move Right Button
			$(document).on('click', '.short-move-right', function(e){
				e.preventDefault();
				var card = $(this).closest('.short-sortable-pill');
				var next = card.next('.short-sortable-pill');
				if (next.length) {
					card.insertAfter(next);
					var newOrder = updateBadgesAndPreview();
					saveOrder(newOrder);
				}
			});

			// Instant Live Mockup Sync & Auto-Save for switches
			$(document).on('change', '.short-live-mh-switch', function(){
				var key = $(this).data('key');
				var isChecked = $(this).is(':checked');

				// Sync mockup immediately
				if(key === 'show_logo') $('#mh-prev-logo').css('display', isChecked ? 'flex' : 'none');
				if(key === 'show_search') $('#mh-prev-search').css('display', isChecked ? 'inline-flex' : 'none');
				if(key === 'show_lang') $('#mh-prev-lang').css('display', isChecked ? 'inline-flex' : 'none');
				if(key === 'show_coins') $('#mh-prev-coins').css('display', isChecked ? 'inline-flex' : 'none');
				if(key === 'show_mylist') $('#mh-prev-mylist').css('display', isChecked ? 'inline-flex' : 'none');
				if(key === 'show_history') $('#mh-prev-history').css('display', isChecked ? 'inline-flex' : 'none');
				if(key === 'show_genre_labels') $('#mh-prev-genre-labels').css('display', isChecked ? 'flex' : 'none');

				var badge = $('#mh-live-save-badge');
				badge.html('<span style="color:#d97706;">Saving...</span>');

				var payload = {
					action: 'short_save_mobile_header_config',
					nonce: saveNonce,
					key: key,
					val: isChecked ? 1 : 0
				};

				$.post(ajaxurl, payload, function(res){
					if(res.success){
						badge.html('<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#10b981;"></span> <span>Saved live</span>');
					} else {
						badge.html('<span style="color:#dc2626;">Error saving</span>');
					}
				}).fail(function(){
					badge.html('<span style="color:#dc2626;">Network error</span>');
				});
			});

			// Reset Defaults
			$('#btn-reset-mh-config').on('click', function(e){
				e.preventDefault();
				if(!confirm('Reset mobile top header order and icons to defaults?')) return;
				$.post(ajaxurl, {
					action: 'short_reset_mobile_header_config',
					nonce: saveNonce
				}, function(res){
					if(res.success){
						location.reload();
					}
				});
			});
		});
		</script>
		<?php
	}

	public static function ajax_save_genre_tabs() {
		if ( ! check_ajax_referer( 'short_admin_nonce', 'nonce', false ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$mode      = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'auto';
		$tabs_json = isset( $_POST['tabs'] ) ? wp_unslash( $_POST['tabs'] ) : '[]';
		$raw_tabs  = json_decode( $tabs_json, true );
		if ( ! is_array( $raw_tabs ) ) {
			$raw_tabs = array();
		}

		// Sanitize auto sort option
		$valid_sorts = array( 'count_desc', 'count_asc', 'alpha_asc', 'alpha_desc' );
		$auto_sort   = isset( $_POST['auto_sort'] ) ? sanitize_key( $_POST['auto_sort'] ) : 'count_desc';
		if ( ! in_array( $auto_sort, $valid_sorts, true ) ) {
			$auto_sort = 'count_desc';
		}

		$clean_tabs = array();
		foreach ( $raw_tabs as $tab ) {
			$slug = sanitize_title( $tab['slug'] ?? '' );
			$label = sanitize_text_field( $tab['label'] ?? '' );
			if ( ! $slug || ! $label ) continue;
			$clean_tabs[] = array(
				'emoji'   => sanitize_text_field( $tab['emoji'] ?? '🎬' ),
				'label'   => $label,
				'slug'    => $slug,
				'enabled' => ! empty( $tab['enabled'] ) ? 1 : 0,
			);
		}

		$config = array(
			'auto'      => ( 'auto' === $mode ) ? true : false,
			'auto_sort' => $auto_sort,
			'tabs'      => $clean_tabs,
		);
		update_option( 'short_genre_filter_tabs', $config );
		wp_send_json_success( array( 'saved' => count( $clean_tabs ), 'auto' => $config['auto'], 'sort' => $auto_sort ) );
	}

	public static function migrate_legacy_section( $sec ) {
		$type = $sec['type'] ?? '';
		if ( 0 !== strpos( $type, 'shorttv_' ) ) {
			return $sec; // Not a legacy section
		}
		$migration_map = array(
			'shorttv_hero'      => array( 'layout' => 'hero',     'type' => 'views' ),
			'shorttv_popular'   => array( 'layout' => 'portrait', 'type' => 'views' ),
			'shorttv_top_rated' => array( 'layout' => 'top10',    'type' => 'rating' ),
			'shorttv_latest'    => array( 'layout' => 'portrait', 'type' => 'latest' ),
			'shorttv_binge'     => array( 'layout' => 'portrait', 'type' => 'episodes' ),
			'shorttv_romance'   => array( 'layout' => 'portrait', 'type' => 'genre/romance' ),
			'shorttv_fantasy'   => array( 'layout' => 'portrait', 'type' => 'genre/fantasy' ),
			'shorttv_vampire'   => array( 'layout' => 'portrait', 'type' => 'genre/vampire' ),
			'shorttv_ceo'       => array( 'layout' => 'portrait', 'type' => 'genre/ceo' ),
			'shorttv_revenge'   => array( 'layout' => 'portrait', 'type' => 'genre/revenge' ),
			'shorttv_urban'     => array( 'layout' => 'portrait', 'type' => 'genre/urban' ),
			'shorttv_genre'     => array( 'layout' => 'portrait', 'type' => 'genre/custom' ),
			'shorttv_vip'       => array( 'layout' => 'portrait', 'type' => 'vip' ),
			'shorttv_leaderboard' => array( 'layout' => 'leaderboard', 'type' => 'rating' ),
		);
		if ( isset( $migration_map[ $type ] ) ) {
			$sec['layout'] = $migration_map[ $type ]['layout'];
			$sec['type']   = $migration_map[ $type ]['type'];
			if ( empty( $sec['endpoint'] ) ) {
				$sec['endpoint'] = $sec['type'];
			}
		}
		return $sec;
	}

	public static function get_default_presets() {
		return array(
			// ShortTV Native Presets
			array( 'id' => 'shorttv_hero', 'name' => '&#127916; ShortTV: Hero Slider (Top Featured)', 'endpoint' => 'views' ),
			array( 'id' => 'shorttv_leaderboard', 'name' => '&#127942; ShortTV: Leaderboard & Champions', 'endpoint' => 'rating' ),
			array( 'id' => 'shorttv_popular', 'name' => '&#128200; ShortTV: Most Viewed (Popular)', 'endpoint' => 'views' ),
			array( 'id' => 'shorttv_top_rated', 'name' => '&#11088; ShortTV: Top Rated (Ranked)', 'endpoint' => 'rating' ),
			array( 'id' => 'shorttv_latest', 'name' => '&#128640; ShortTV: New Releases (Latest)', 'endpoint' => 'latest' ),
			array( 'id' => 'shorttv_binge', 'name' => '&#128293; ShortTV: Binge-Ready (Most Episodes)', 'endpoint' => 'episodes' ),
			array( 'id' => 'shorttv_vip', 'name' => '&#128081; ShortTV: VIP Only Exclusives', 'endpoint' => 'vip' ),
			array( 'id' => 'shorttv_romance', 'name' => '&#128149; ShortTV: Romance & Passion', 'endpoint' => 'genre/romance' ),
			array( 'id' => 'shorttv_fantasy', 'name' => '&#10024; ShortTV: Fantasy & Supernatural', 'endpoint' => 'genre/fantasy' ),
			array( 'id' => 'shorttv_vampire', 'name' => '&#129415; ShortTV: Vampire & Werewolf', 'endpoint' => 'genre/vampire' ),
			array( 'id' => 'shorttv_ceo', 'name' => '&#128084; ShortTV: Billionaire & CEO', 'endpoint' => 'genre/ceo' ),
			array( 'id' => 'shorttv_revenge', 'name' => '&#128481;&#65039; ShortTV: Revenge & Drama', 'endpoint' => 'genre/revenge' ),
			array( 'id' => 'shorttv_urban', 'name' => '&#127980; ShortTV: Urban & Modern', 'endpoint' => 'genre/urban' ),
		);
	}

	public static function get_presets() {
		$presets = ( get_option( 'short_endpoint_presets', array() ) ?: get_option( 'short_endpoint_presets', array() ) );
		if ( empty( $presets ) ) {
			$presets = self::get_default_presets();
		}
		return $presets;
	}

	public static function get_default_sections( $target = 'home' ) {
		if ( 'categories' === $target ) {
			// Categories/Genre page uses its own standalone grid + dynamic filter tabs.
			// No section builder rows are needed here.
			return array();
		}

		if ( 'leaderboard' === $target || 'mobile_leaderboard' === $target ) {
			return array(
				array( 'id' => 'leaderboard_champions', 'title' => 'Top Ranked Champions 🏆', 'type' => 'rating', 'layout' => 'leaderboard', 'endpoint' => 'rating', 'limit' => 10, 'enabled' => 1 ),
				array( 'id' => 'top_10_today', 'title' => 'TOP 10 Trending Today 🥇', 'type' => 'views', 'layout' => 'top10', 'endpoint' => 'views', 'limit' => 10, 'enabled' => 1 ),
				array( 'id' => 'binge_worthy', 'title' => 'Binge-Ready Drama Leaders 📺', 'type' => 'episodes', 'layout' => 'portrait', 'endpoint' => 'episodes', 'limit' => 20, 'enabled' => 1 ),
				array( 'id' => 'new_hits', 'title' => 'Rising Star Dramas 🚀', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 20, 'enabled' => 1 ),
			);
		}

		if ( 'new-popular' === $target ) {
			return array(
				array( 'id' => 'top_10_today', 'title' => 'TOP &#128287; (Ranked 1-10)', 'type' => 'rating', 'layout' => 'top10', 'endpoint' => 'rating', 'limit' => 10, 'enabled' => 1 ),
				array( 'id' => 'trending_today', 'title' => 'Most Viewed Dramas 🔥', 'type' => 'views', 'layout' => 'portrait', 'endpoint' => 'views', 'limit' => 20, 'enabled' => 1 ),
				array( 'id' => 'new_releases', 'title' => 'New Releases &#128640;', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 20, 'enabled' => 1 ),
				array( 'id' => 'binge_ready', 'title' => 'Binge-Ready (Long Series) 📺', 'type' => 'episodes', 'layout' => 'portrait', 'endpoint' => 'episodes', 'limit' => 20, 'enabled' => 1 ),
			);
		}

		if ( 'desktop_nav' === $target ) {
			return array(
				array( 'id' => 'home', 'title' => 'Home', 'type' => 'home', 'layout' => 'landscape', 'endpoint' => home_url( '/' ), 'limit' => 1, 'enabled' => 1 ),
				array( 'id' => 'categories', 'title' => 'Categories', 'type' => 'categories', 'layout' => 'landscape', 'endpoint' => home_url( '/genre/' ), 'limit' => 1, 'enabled' => 1 ),
				array( 'id' => 'new-popular', 'title' => 'New & Popular', 'type' => 'new-popular', 'layout' => 'landscape', 'endpoint' => home_url( '/new-popular/' ), 'limit' => 1, 'enabled' => 1 ),
			);
		}

		if ( 'mobile_home' === $target ) {
			return array(
				array( 'id' => 'hero_showcase', 'title' => 'Hero Slider Showcase', 'type' => 'views', 'layout' => 'hero', 'endpoint' => 'views', 'limit' => 5, 'enabled' => 1 ),
				array( 'id' => 'reel_original', 'title' => 'Reel Original &#127916;', 'type' => 'views', 'layout' => 'portrait', 'endpoint' => 'views', 'limit' => 15, 'enabled' => 1 ),
				array( 'id' => 'top_10_today', 'title' => 'TOP &#128287; (Ranked 1-10)', 'type' => 'rating', 'layout' => 'top10', 'endpoint' => 'rating', 'limit' => 10, 'enabled' => 1 ),
				array( 'id' => 'new_releases', 'title' => 'New Releases &#128640;', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 15, 'enabled' => 1 ),
			);
		}

		if ( 'mobile_discovery' === $target ) {
			return array(
				array( 'id' => 'disc_popular', 'title' => 'Popular Short Dramas 🔥', 'type' => 'views', 'layout' => 'portrait', 'endpoint' => 'views', 'limit' => 20, 'enabled' => 1 ),
				array( 'id' => 'disc_top_rated', 'title' => 'Top Rated Short Plays ⭐', 'type' => 'rating', 'layout' => 'portrait', 'endpoint' => 'rating', 'limit' => 20, 'enabled' => 1 ),
				array( 'id' => 'disc_latest', 'title' => 'Fresh Releases 🚀', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 20, 'enabled' => 1 ),
			);
		}

		if ( 'mobile_popular' === $target ) {
			return self::get_default_sections( 'new-popular' );
		}

		if ( 'mobile_account' === $target || 'bottom_nav' === $target ) {
			return array(
				array( 'id' => 'home', 'title' => 'Home', 'type' => 'home', 'layout' => 'landscape', 'endpoint' => home_url( '/' ), 'limit' => 1, 'enabled' => 1 ),
				array( 'id' => 'discovery', 'title' => 'Discovery', 'type' => 'shorttv', 'layout' => 'landscape', 'endpoint' => home_url( '/short-tv/' ), 'limit' => 1, 'enabled' => 1 ),
				array( 'id' => 'popular', 'title' => 'Popular', 'type' => 'new-popular', 'layout' => 'landscape', 'endpoint' => home_url( '/new-popular/' ), 'limit' => 1, 'enabled' => 1 ),
				array( 'id' => 'account', 'title' => 'Account', 'type' => 'account', 'layout' => 'landscape', 'endpoint' => home_url( '/account/' ), 'limit' => 1, 'enabled' => 1 ),
			);
		}

		// Default Home Sections (ReelShort Standard Architecture)
		return array(
			array( 'id' => 'hero_showcase', 'title' => 'Hero Slider Showcase', 'type' => 'views', 'layout' => 'hero', 'endpoint' => 'views', 'limit' => 5, 'enabled' => 1 ),
			array( 'id' => 'reel_original', 'title' => 'Reel Original &#127916;', 'type' => 'views', 'layout' => 'portrait', 'endpoint' => 'views', 'limit' => 15, 'enabled' => 1 ),
			array( 'id' => 'top_10_today', 'title' => 'TOP &#128287; (Ranked 1-10)', 'type' => 'rating', 'layout' => 'top10', 'endpoint' => 'rating', 'limit' => 10, 'enabled' => 1 ),
			array( 'id' => 'new_releases', 'title' => 'New Releases &#128640;', 'type' => 'latest', 'layout' => 'portrait', 'endpoint' => 'latest', 'limit' => 15, 'enabled' => 1 ),
			array( 'id' => 'binge_ready', 'title' => 'Binge-Ready 🔥', 'type' => 'episodes', 'layout' => 'portrait', 'endpoint' => 'episodes', 'limit' => 15, 'enabled' => 1 ),
			array( 'id' => 'romance_dramas', 'title' => 'Romance & Passion &#128149;', 'type' => 'genre/romance', 'layout' => 'portrait', 'endpoint' => 'genre/romance', 'limit' => 15, 'enabled' => 1 ),
			array( 'id' => 'fantasy_dramas', 'title' => 'Fantasy & Supernatural &#10024;', 'type' => 'genre/fantasy', 'layout' => 'portrait', 'endpoint' => 'genre/fantasy', 'limit' => 15, 'enabled' => 1 ),
			array( 'id' => 'vampire_dramas', 'title' => 'Vampire & Werewolf &#129415;', 'type' => 'genre/vampire', 'layout' => 'portrait', 'endpoint' => 'genre/vampire', 'limit' => 15, 'enabled' => 1 ),
			array( 'id' => 'ceo_dramas', 'title' => 'Billionaire & CEO &#128084;', 'type' => 'genre/ceo', 'layout' => 'portrait', 'endpoint' => 'genre/ceo', 'limit' => 15, 'enabled' => 1 ),
		);
	}

	/**
	 * AJAX: 1-Click Restore All System Defaults (Headers, Endpoints, Rows, Tabs)
	 */
	public static function ajax_restore_all_system_defaults() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized permission.', 'short-stream-core' ) );
		}

		// 1. Reset Homepage Rows
		update_option( 'short_homepage_sections', self::get_default_sections( 'home' ) );
		update_option( 'short_mobile_home_sections', self::get_default_sections( 'mobile_home' ) );
		update_option( 'short_leaderboard_sections', self::get_default_sections( 'leaderboard' ) );
		update_option( 'short_new_popular_sections', self::get_default_sections( 'new-popular' ) );

		// 2. Reset Section Tabs Configuration
		update_option( 'short_section_tabs_config', array(
			'desktop' => array(
				array( 'target' => 'home',        'title' => __( 'Home', 'short-stream-core' ),           'icon' => 'dashicons-admin-home',  'url' => '/' ),
				array( 'target' => 'categories',  'title' => __( 'Discover', 'short-stream-core' ),       'icon' => 'dashicons-category',    'url' => '/genre/' ),
				array( 'target' => 'new-popular', 'title' => __( 'New and Popular', 'short-stream-core' ),'icon' => 'dashicons-star-filled', 'url' => '/new-popular/' ),
				array( 'target' => 'leaderboard', 'title' => __( 'Leaderboard', 'short-stream-core' ),    'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
				array( 'target' => 'my-list',     'title' => __( 'My List', 'short-stream-core' ),        'icon' => 'dashicons-list-view',   'url' => '/my-list/' ),
			),
			'mobile_header' => array(
				array( 'target' => 'home',        'title' => __( 'Home', 'short-stream-core' ),           'icon' => 'dashicons-admin-home',  'url' => '/' ),
				array( 'target' => 'categories',  'title' => __( 'Discover', 'short-stream-core' ),       'icon' => 'dashicons-category',    'url' => '/genre/' ),
				array( 'target' => 'new-popular', 'title' => __( 'New and Popular', 'short-stream-core' ),'icon' => 'dashicons-star-filled', 'url' => '/new-popular/' ),
				array( 'target' => 'leaderboard', 'title' => __( 'Leaderboard', 'short-stream-core' ),    'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
				array( 'target' => 'my-list',     'title' => __( 'My List', 'short-stream-core' ),        'icon' => 'dashicons-list-view',   'url' => '/my-list/' ),
			),
			'mobile'  => array(
				array( 'target' => 'mobile_home',        'title' => __( 'Home', 'short-stream-core' ),        'icon' => 'dashicons-admin-home',  'url' => '/' ),
				array( 'target' => 'mobile_discovery',   'title' => __( 'Discover', 'short-stream-core' ),    'icon' => 'dashicons-video-alt3',  'url' => '/genre/' ),
				array( 'target' => 'mobile_rewards',     'title' => __( 'Rewards', 'short-stream-core' ),     'icon' => 'dashicons-tickets-alt', 'url' => '/reward/' ),
				array( 'target' => 'mobile_leaderboard', 'title' => __( 'Ranking', 'short-stream-core' ),     'icon' => 'dashicons-awards',     'url' => '/leaderboard/' ),
				array( 'target' => 'mobile_account',     'title' => __( 'Account', 'short-stream-core' ),     'icon' => 'dashicons-admin-users', 'url' => '/account/' ),
			),
		) );

		// 3. Reset Brand & Security Defaults
		update_option( 'short_brand_settings', array(
			'brand_name'        => 'ShortTV',
			'custom_logo_url'   => 'https://i.postimg.cc/cH3CM5h4/image.png',
			'theme_color'       => '#E50914',
			'display_mode'      => 'cinematic-dark',
			'logo_display_mode' => 'image_only',
			'logo_display'      => 'image',
		) );

		update_option( 'short_security_settings', array(
			'allow_right_click' => '0',
			'allow_inspection'  => '0',
		) );

		// 4. Ensure Sample Series exist in DB
		\SHORT\Core\CPT\Video_CPT::seed_sample_series( true );
		flush_rewrite_rules( false );

		wp_send_json_success( array( 'message' => __( 'All settings, headers, and endpoints restored to clean defaults.', 'short-stream-core' ) ) );
	}

	public static function get_notifications() {
		$items = get_option( 'short_system_notifications', null );
		if ( null === $items ) {
			// Auto initialize with default smart notifications if empty
			$items = self::generate_smart_notifications();
			update_option( 'short_system_notifications', $items );
		}
		return is_array( $items ) ? $items : array();
	}

	public static function generate_smart_notifications() {
		$seeded = array(
			array(
				'id'          => 'seed_welcome',
				'title'       => 'Welcome to Short Stream!',
				'message'     => 'Explore thousands of viral short dramas, mini-series, and exclusive 4K Ultra HD originals.',
				'type'        => 'important',
				'tag'         => 'Important Update',
				'poster'      => '',
				'link'        => home_url( '/' ),
				'pinned'      => 1,
				'created_at'  => time(),
				'badge_color' => '#E50914',
			),
			array(
				'id'          => 'seed_rewards',
				'title'       => '🎁 Daily Bonus: Claim Your Free Coins!',
				'message'     => 'Your daily check-in reward is ready to claim. Complete tasks to unlock premium drama episodes for free.',
				'type'        => 'trending',
				'tag'         => 'Free Coins',
				'poster'      => get_template_directory_uri() . '/assets/images/floating-gift.png',
				'link'        => home_url( '/account/#view=rewards' ),
				'pinned'      => 0,
				'created_at'  => time() - ( 1 * 3600 * 3 ),
				'badge_color' => '#f59e0b',
			),
			array(
				'id'          => 'seed_release_billionaire',
				'title'       => '🎬 New Premiere: The Billionaire’s Secret Bride',
				'message'     => 'Episodes 1–50 are now streaming in 4K HDR! Experience the thrilling romance everyone is talking about.',
				'type'        => 'new_release',
				'tag'         => 'New Release',
				'poster'      => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=300&auto=format&fit=crop&q=80',
				'link'        => home_url( '/genre/' ),
				'pinned'      => 0,
				'created_at'  => time() - ( 1 * 3600 * 6 ),
				'badge_color' => '#059669',
			),
			array(
				'id'          => 'seed_trending_dragon',
				'title'       => '🔥 #1 on Top Chart: Hidden Heir of the Dragon Empire',
				'message'     => 'Over 500,000 viewers watched this week. Check out the top ranking drama series now!',
				'type'        => 'trending',
				'tag'         => '#1 Trending',
				'poster'      => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300&auto=format&fit=crop&q=80',
				'link'        => home_url( '/leaderboard/' ),
				'pinned'      => 0,
				'created_at'  => time() - ( 1 * 3600 * 12 ),
				'badge_color' => '#ff2d55',
			),
			array(
				'id'          => 'seed_season_ceo',
				'title'       => '📺 Complete Series Drop: CEO’s Revenge & Rebirth',
				'message'     => 'All 60 episodes have been released simultaneously. Binge-watch without waiting for new episodes.',
				'type'        => 'new_season',
				'tag'         => 'Full Series Drop',
				'poster'      => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=300&auto=format&fit=crop&q=80',
				'link'        => home_url( '/new-popular/' ),
				'pinned'      => 0,
				'created_at'  => time() - ( 1 * 3600 * 24 ),
				'badge_color' => '#7c3aed',
			),
			array(
				'id'          => 'seed_vip_pass',
				'title'       => '👑 ShortTV VIP Pass: 50% Off First Month',
				'message'     => 'Unlock unlimited drama access, ad-free streaming, and early release episodes on all your devices.',
				'type'        => 'system',
				'tag'         => 'Special Offer',
				'poster'      => '',
				'link'        => home_url( '/subscription/' ),
				'pinned'      => 0,
				'created_at'  => time() - ( 1 * 3600 * 48 ),
				'badge_color' => '#0284c7',
			),
		);

		// Pull top trending items from local ShortTV dramas if published posts exist
		$dramas = get_posts( array(
			'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( ! empty( $dramas ) ) {
			foreach ( $dramas as $idx => $drama ) {
				$schema = \SHORT\Core\CPT\Video_CPT::get_series_schema( $drama->ID );
				$poster = ! empty( $schema['poster'] ) ? $schema['poster'] : ( get_the_post_thumbnail_url( $drama->ID, 'medium' ) ?: '' );
				$seeded[] = array(
					'id'          => 'seed_drama_' . $drama->ID,
					'title'       => '🔥 Trending Drama: ' . $drama->post_title,
					'message'     => wp_trim_words( $schema['overview'] ?? $drama->post_excerpt, 16 ) ?: 'Watch the latest episodes now!',
					'type'        => 'trending',
					'tag'         => '#' . ( $idx + 1 ) . ' Trending Drama',
					'poster'      => $poster,
					'link'        => home_url( '/watch/' . $drama->ID . '/?episode=1' ),
					'pinned'      => 0,
					'created_at'  => time() - ( ( $idx + 1 ) * 3600 * 2 ),
					'badge_color' => '#ff2d55',
				);
			}
		}

		return $seeded;
	}

	public static function render_notifications_manager() {
		$notifications = self::get_notifications();
		?>
		<div class="wrap short-notifications-admin-wrap" style="max-width:1200px; margin-top:20px;">
			<style>
				.notif-admin-header {
					background: linear-gradient(135deg, #141414 0%, #1e1e1e 60%, #311116 100%);
					border-radius: 12px;
					padding: 26px 32px;
					color: #ffffff;
					display: flex;
					align-items: center;
					justify-content: space-between;
					flex-wrap: wrap;
					gap: 16px;
					margin-bottom: 24px;
					box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
				}
				.notif-admin-header h1 {
					color: #ffffff;
					font-size: 24px;
					font-weight: 800;
					margin: 0 0 6px 0;
					display: flex;
					align-items: center;
					gap: 10px;
				}
				.notif-admin-header p {
					margin: 0;
					color: #cbd5e1;
					font-size: 13.5px;
				}
				.notif-admin-grid {
					display: grid;
					grid-template-columns: 380px 1fr;
					gap: 24px;
				}
				@media (max-width: 900px) {
					.notif-admin-grid {
						grid-template-columns: 1fr;
					}
				}
				.notif-card-box {
					background: #ffffff;
					border: 1px solid #e2e8f0;
					border-radius: 12px;
					padding: 24px;
					box-shadow: 0 4px 16px rgba(0,0,0,0.03);
				}
				.notif-card-box h3 {
					font-size: 16px;
					font-weight: 700;
					color: #0f172a;
					margin: 0 0 16px 0;
					padding-bottom: 12px;
					border-bottom: 1px solid #f1f5f9;
					display: flex;
					align-items: center;
					justify-content: space-between;
				}
				.notif-form-group {
					margin-bottom: 14px;
				}
				.notif-form-group label {
					display: block;
					font-size: 12.5px;
					font-weight: 600;
					color: #334155;
					margin-bottom: 5px;
				}
				.notif-form-group input[type="text"],
				.notif-form-group input[type="url"],
				.notif-form-group select,
				.notif-form-group textarea {
					width: 100%;
					border: 1px solid #cbd5e1;
					border-radius: 6px;
					padding: 8px 12px;
					font-size: 13px;
					box-sizing: border-box;
				}
				.notif-form-group input:focus,
				.notif-form-group textarea:focus,
				.notif-form-group select:focus {
					border-color: #E50914;
					box-shadow: 0 0 0 2px rgba(229, 9, 20, 0.15);
					outline: none;
				}
				.notif-admin-list {
					display: flex;
					flex-direction: column;
					gap: 12px;
				}
				.notif-admin-item {
					display: flex;
					gap: 14px;
					align-items: flex-start;
					background: #f8fafc;
					border: 1px solid #e2e8f0;
					border-radius: 10px;
					padding: 14px 16px;
					position: relative;
					transition: all 0.2s ease;
				}
				.notif-admin-item:hover {
					background: #ffffff;
					border-color: #cbd5e1;
					box-shadow: 0 4px 12px rgba(0,0,0,0.04);
				}
				.notif-item-poster {
					width: 50px;
					height: 72px;
					border-radius: 6px;
					object-fit: cover;
					background: #0f172a;
					flex-shrink: 0;
				}
				.notif-item-body {
					flex: 1;
					min-width: 0;
				}
				.notif-item-top {
					display: flex;
					align-items: center;
					gap: 8px;
					margin-bottom: 4px;
					flex-wrap: wrap;
				}
				.notif-pill {
					font-size: 10px;
					font-weight: 700;
					text-transform: uppercase;
					padding: 2px 7px;
					border-radius: 4px;
					color: #ffffff;
				}
				.notif-item-title {
					font-size: 14px;
					font-weight: 700;
					color: #0f172a;
					margin: 0;
				}
				.notif-item-msg {
					font-size: 12.5px;
					color: #475569;
					margin: 4px 0 6px 0;
					line-height: 1.4;
				}
				.notif-item-meta {
					font-size: 11px;
					color: #94a3b8;
					display: flex;
					align-items: center;
					gap: 12px;
				}
				.notif-item-actions {
					display: flex;
					gap: 6px;
					align-self: center;
				}
				.btn-notif-delete {
					background: #fee2e2;
					border: 1px solid #fecaca;
					color: #dc2626;
					width: 32px;
					height: 32px;
					border-radius: 6px;
					cursor: pointer;
					display: inline-flex;
					align-items: center;
					justify-content: center;
					transition: all 0.2s ease;
				}
				.btn-notif-delete:hover {
					background: #dc2626;
					color: #ffffff;
				}
			</style>

			<div class="notif-admin-header">
				<div>
					<h1>
						<span class="dashicons dashicons-bell" style="font-size:26px; width:26px; height:26px;"></span>
						<span><?php _e( 'Notification Manager', 'short-stream-core' ); ?></span>
					</h1>
					<p><?php _e( 'Broadcast system announcements, important alerts, and auto-seed smart notifications for users.', 'short-stream-core' ); ?></p>
				</div>
				<div style="display:flex; gap:10px;">
					<button type="button" class="button" id="btn-seed-smart-notifs" style="background:#0284c7; color:#fff; border-color:#0284c7; font-weight:600; padding:0 16px; height:36px; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; gap:8px; line-height:1;">
						<span class="dashicons dashicons-update" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span> 
						<span style="display:inline-block; vertical-align:middle;"><?php _e( 'Auto-Seed Smart Releases', 'short-stream-core' ); ?></span>
					</button>
				</div>
			</div>

			<div class="notif-admin-grid">
				<!-- Create / Send Form -->
				<div class="notif-card-box">
					<h3>
						<span><?php _e( 'Broadcast Notification', 'short-stream-core' ); ?></span>
						<span class="dashicons dashicons-megaphone" style="color:#E50914;"></span>
					</h3>
					<form id="short-create-notif-form">
						<input type="hidden" name="action" value="short_save_notification">
						<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'short_admin_nonce' ) ); ?>">

						<div class="notif-form-group">
							<label for="notif-type"><?php _e( 'Notification Type / Priority', 'short-stream-core' ); ?></label>
							<select id="notif-type" name="notif_type">
								<option value="important"><?php _e( '🚨 Important Update (Red Alert)', 'short-stream-core' ); ?></option>
								<option value="new_release"><?php _e( '🎬 New Release / Premiere', 'short-stream-core' ); ?></option>
								<option value="new_season"><?php _e( '📺 New Season Alert', 'short-stream-core' ); ?></option>
								<option value="trending"><?php _e( '🔥 Trending Now', 'short-stream-core' ); ?></option>
								<option value="system"><?php _e( 'ℹ️ System Announcement', 'short-stream-core' ); ?></option>
							</select>
						</div>

						<div class="notif-form-group">
							<label for="notif-title"><?php _e( 'Title / Headline', 'short-stream-core' ); ?> *</label>
							<input type="text" id="notif-title" name="notif_title" placeholder="<?php esc_attr_e( 'e.g. Stranger Things Season 5 is Now Streaming!', 'short-stream-core' ); ?>" required>
						</div>

						<div class="notif-form-group">
							<label for="notif-message"><?php _e( 'Message / Details', 'short-stream-core' ); ?> *</label>
							<textarea id="notif-message" name="notif_message" rows="3" placeholder="<?php esc_attr_e( 'Enter notification description or release details...', 'short-stream-core' ); ?>" required></textarea>
						</div>

						<div class="notif-form-group">
							<label for="notif-tag"><?php _e( 'Badge Tag Label (Optional)', 'short-stream-core' ); ?></label>
							<input type="text" id="notif-tag" name="notif_tag" placeholder="<?php esc_attr_e( 'e.g. Important, Season Premiere, 4K HDR', 'short-stream-core' ); ?>">
						</div>

						<div class="notif-form-group">
							<label for="notif-link"><?php _e( 'Destination URL / Link', 'short-stream-core' ); ?></label>
							<input type="text" id="notif-link" name="notif_link" placeholder="<?php esc_attr_e( 'e.g. /watch/12345/ or /movies/', 'short-stream-core' ); ?>">
						</div>

						<div class="notif-form-group">
							<label for="notif-poster"><?php _e( 'Thumbnail / Poster Image URL (Optional)', 'short-stream-core' ); ?></label>
							<input type="url" id="notif-poster" name="notif_poster" placeholder="https://example.com/poster.jpg">
						</div>

						<div class="notif-form-group" style="display:flex; align-items:center; gap:8px;">
							<input type="checkbox" id="notif-pinned" name="notif_pinned" value="1" checked>
							<label for="notif-pinned" style="margin:0; font-weight:normal;"><?php _e( 'Pin to top of Notification Center', 'short-stream-core' ); ?></label>
						</div>

						<button type="submit" id="btn-save-notif" class="button" style="width:100%; height:40px; background:#E50914; border-color:#E50914; color:#ffffff; font-weight:700; font-size:13px; border-radius:6px; cursor:pointer;">
							<span><?php _e( '🚀 Send Notification', 'short-stream-core' ); ?></span>
						</button>
						<div id="notif-form-status" style="margin-top:10px; font-size:12px; text-align:center;"></div>
					</form>
				</div>

				<!-- Notification Feed -->
				<div class="notif-card-box">
					<h3>
						<span><?php _e( 'Active Notifications', 'short-stream-core' ); ?> (<span id="notif-count-label"><?php echo count( $notifications ); ?></span>)</span>
						<span style="font-size:12px; color:#64748b; font-weight:normal;"><?php _e( 'Visible on desktop bell & mobile /notification/', 'short-stream-core' ); ?></span>
					</h3>

					<div class="notif-admin-list" id="notif-admin-list">
						<?php if ( empty( $notifications ) ) : ?>
							<p id="notif-empty-text" style="color:#64748b; font-size:13px; text-align:center; padding:30px 0;"><?php _e( 'No notifications found. Click "Auto-Seed Smart Releases" or send your first notification!', 'short-stream-core' ); ?></p>
						<?php else : ?>
							<?php foreach ( $notifications as $notif ) : 
								$type_colors = array(
									'important'   => '#dc2626',
									'new_release' => '#059669',
									'new_season'  => '#7c3aed',
									'trending'    => '#0284c7',
									'system'      => '#d97706',
								);
								$bg_color = $notif['badge_color'] ?? ( $type_colors[ $notif['type'] ?? 'system' ] ?? '#E50914' );
								$tag = ! empty( $notif['tag'] ) ? $notif['tag'] : ucfirst( str_replace( '_', ' ', $notif['type'] ?? 'Alert' ) );
							?>
								<div class="notif-admin-item" data-id="<?php echo esc_attr( $notif['id'] ); ?>">
									<?php if ( ! empty( $notif['poster'] ) ) : ?>
										<img src="<?php echo esc_url( $notif['poster'] ); ?>" alt="Poster" class="notif-item-poster" onerror="this.style.display='none';">
									<?php else : ?>
										<div class="notif-item-poster" style="display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px;">🔔</div>
									<?php endif; ?>
									<div class="notif-item-body">
										<div class="notif-item-top">
											<span class="notif-pill" style="background:<?php echo esc_attr( $bg_color ); ?>;"><?php echo esc_html( $tag ); ?></span>
											<?php if ( ! empty( $notif['pinned'] ) ) : ?>
												<span style="font-size:11px; color:#d97706; font-weight:700;">📌 <?php _e( 'Pinned', 'short-stream-core' ); ?></span>
											<?php endif; ?>
										</div>
										<h4 class="notif-item-title"><?php echo esc_html( $notif['title'] ); ?></h4>
										<p class="notif-item-msg"><?php echo esc_html( $notif['message'] ); ?></p>
										<div class="notif-item-meta">
											<span>📅 <?php echo esc_html( human_time_diff( $notif['created_at'] ?? time(), time() ) . ' ' . __( 'ago', 'short-stream-core' ) ); ?></span>
											<?php if ( ! empty( $notif['link'] ) ) : ?>
												<span>🔗 <a href="<?php echo esc_url( $notif['link'] ); ?>" target="_blank" style="color:#0284c7; text-decoration:none;"><?php echo esc_html( $notif['link'] ); ?></a></span>
											<?php endif; ?>
										</div>
									</div>
									<div class="notif-item-actions">
										<button type="button" class="btn-notif-delete" data-id="<?php echo esc_attr( $notif['id'] ); ?>" title="<?php esc_attr_e( 'Delete notification', 'short-stream-core' ); ?>">
											<span class="dashicons dashicons-trash" style="font-size:16px; width:16px; height:16px;"></span>
										</button>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($){
			// Create Notification
			$('#short-create-notif-form').on('submit', function(e){
				e.preventDefault();
				var form = $(this);
				var btn = $('#btn-save-notif').prop('disabled', true).text('Broadcasting...');
				$.post(ajaxurl, form.serialize(), function(res){
					btn.prop('disabled', false).html('<span>🚀 Send Notification</span>');
					if(res.success) {
						$('#notif-form-status').html('<span style="color:#059669; font-weight:bold;">&#10003; Notification sent successfully!</span>');
						form[0].reset();
						setTimeout(function(){ window.location.reload(); }, 900);
					} else {
						$('#notif-form-status').html('<span style="color:#dc2626;">Error: ' + (res.data || 'Failed to save') + '</span>');
					}
				});
			});

			// Delete Notification
			$(document).on('click', '.btn-notif-delete', function(){
				if(!confirm('Are you sure you want to delete this notification?')) return;
				var notifId = $(this).data('id');
				var item = $(this).closest('.notif-admin-item');
				$.post(ajaxurl, {
					action: 'short_delete_notification',
					nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>',
					id: notifId
				}, function(res){
					if(res.success) {
						item.fadeOut(300, function(){ $(this).remove(); });
					} else {
						alert('Failed to delete notification.');
					}
				});
			});

			// Auto-Seed Smart Notifications
			$('#btn-seed-smart-notifs').on('click', function(){
				var btn = $(this).prop('disabled', true).html('<span class="dashicons dashicons-update" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span> <span>Seeding...</span>');
				$.post(ajaxurl, {
					action: 'short_seed_smart_notifications',
					nonce: '<?php echo wp_create_nonce( "short_admin_nonce" ); ?>'
				}, function(res){
					if(res.success) {
						window.location.reload();
					} else {
						btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span> <span>Auto-Seed Smart Releases</span>');
						alert(res.data || 'Failed to auto-seed.');
					}
				});
			});
		});
		</script>
		<?php
	}

	public static function ajax_upload_notif_image() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}

		if ( empty( $_FILES['notif_image_file'] ) ) {
			wp_send_json_error( __( 'No image file provided.', 'short-stream-core' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'notif_image_file', 0 );
		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( $attachment_id->get_error_message() );
		}

		$image_url = wp_get_attachment_url( $attachment_id );
		wp_send_json_success( array(
			'attachment_id' => $attachment_id,
			'url'           => $image_url,
		) );
	}

	public static function ajax_save_notification() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}

		$notif_id = sanitize_text_field( $_POST['notif_id'] ?? '' );
		$title    = sanitize_text_field( $_POST['notif_title'] ?? '' );
		$message  = sanitize_textarea_field( $_POST['notif_message'] ?? '' );
		$type     = sanitize_key( $_POST['notif_type'] ?? 'system' );
		$tag      = sanitize_text_field( $_POST['notif_tag'] ?? '' );
		$link     = esc_url_raw( $_POST['notif_link'] ?? '' );
		$poster   = esc_url_raw( $_POST['notif_poster'] ?? '' );
		$pinned   = ! empty( $_POST['notif_pinned'] ) ? 1 : 0;

		if ( empty( $title ) || empty( $message ) ) {
			wp_send_json_error( __( 'Title and Message are required.', 'short-stream-core' ) );
		}

		$type_colors = array(
			'important'   => '#dc2626',
			'new_release' => '#059669',
			'new_season'  => '#7c3aed',
			'trending'    => '#0284c7',
			'system'      => '#d97706',
		);

		$current = self::get_notifications();

		if ( ! empty( $notif_id ) ) {
			// Edit existing notification
			$found = false;
			foreach ( $current as &$item ) {
				if ( ( $item['id'] ?? '' ) === $notif_id ) {
					$item['title']       = $title;
					$item['message']     = $message;
					$item['type']        = $type;
					$item['tag']         = $tag ?: ( 'important' === $type ? 'Important Update' : ucfirst( str_replace( '_', ' ', $type ) ) );
					$item['poster']      = $poster;
					$item['link']        = $link ?: home_url( '/' );
					$item['pinned']      = $pinned;
					$item['badge_color'] = $type_colors[ $type ] ?? '#E50914';
					$found = true;
					$saved_notif = $item;
					break;
				}
			}

			if ( ! $found ) {
				wp_send_json_error( __( 'Notification to edit not found.', 'short-stream-core' ) );
			}
		} else {
			// Create new notification
			$saved_notif = array(
				'id'          => 'notif_' . time() . '_' . wp_rand( 100, 999 ),
				'title'       => $title,
				'message'     => $message,
				'type'        => $type,
				'tag'         => $tag ?: ( 'important' === $type ? 'Important Update' : ucfirst( str_replace( '_', ' ', $type ) ) ),
				'poster'      => $poster,
				'link'        => $link ?: home_url( '/' ),
				'pinned'      => $pinned,
				'created_at'  => time(),
				'badge_color' => $type_colors[ $type ] ?? '#E50914',
			);

			if ( $pinned ) {
				array_unshift( $current, $saved_notif );
			} else {
				$current[] = $saved_notif;
			}
		}

		update_option( 'short_system_notifications', $current );

		// Dispatch live push notification to phone status bars & browser notifications
		$push_sent = false;
		if ( ! empty( $_POST['notif_send_push'] ) || empty( $notif_id ) ) {
			$push_sent = self::send_fcm_push_notification( $title, $message, $link, $poster, $tag );
		}

		wp_send_json_success( array( 
			'is_edit'      => ! empty( $notif_id ), 
			'notification' => $saved_notif,
			'push_sent'    => $push_sent,
		) );
	}

	public static function ajax_register_fcm_token() {
		$token = sanitize_text_field( $_POST['token'] ?? '' );
		if ( empty( $token ) ) {
			wp_send_json_error( 'Invalid device token' );
		}

		$tokens = get_option( 'short_fcm_device_tokens', array() );
		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}

		if ( ! in_array( $token, $tokens, true ) ) {
			$tokens[] = $token;
			if ( count( $tokens ) > 10000 ) {
				$tokens = array_slice( $tokens, -10000 );
			}
			update_option( 'short_fcm_device_tokens', $tokens );
		}

		wp_send_json_success( array( 'registered' => true, 'total_subscribers' => count( $tokens ) ) );
	}

	public static function send_fcm_push_notification( $title, $message, $link = '', $poster = '', $tag = '' ) {
		$firebase = get_option( 'short_firebase_settings', array() );
		$server_key = trim( $firebase['fcm_server_key'] ?? '' );
		if ( empty( $server_key ) ) {
			return false;
		}

		$tokens = get_option( 'short_fcm_device_tokens', array() );
		if ( empty( $tokens ) || ! is_array( $tokens ) ) {
			return false;
		}

		$target_link = ! empty( $link ) ? $link : home_url( '/notification/' );
		$icon_url    = ! empty( $poster ) ? $poster : ( function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : 'https://i.postimg.cc/cH3CM5h4/image.png' );

		$body_payload = array(
			'registration_ids' => array_values( array_unique( $tokens ) ),
			'notification'     => array(
				'title'        => $title,
				'body'         => $message,
				'icon'         => $icon_url,
				'image'        => $poster ?: '',
				'click_action' => $target_link,
			),
			'data'             => array(
				'title'   => $title,
				'message' => $message,
				'poster'  => $poster ?: '',
				'link'    => $target_link,
				'tag'     => $tag ?: 'shorttv_alert',
			),
			'priority'         => 'high',
		);

		$response = wp_remote_post( 'https://fcm.googleapis.com/fcm/send', array(
			'headers' => array(
				'Authorization' => 'key=' . $server_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body_payload ),
			'timeout' => 15,
		) );

		return ! is_wp_error( $response );
	}

	public static function ajax_delete_notification() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}

		$id = sanitize_text_field( $_POST['id'] ?? '' );
		if ( empty( $id ) ) {
			wp_send_json_error( __( 'Invalid ID', 'short-stream-core' ) );
		}

		$current = self::get_notifications();
		$filtered = array_values( array_filter( $current, function( $n ) use ( $id ) {
			return ( $n['id'] ?? '' ) !== $id;
		} ) );

		update_option( 'short_system_notifications', $filtered );
		wp_send_json_success();
	}

	public static function ajax_seed_smart_notifications() {
		check_ajax_referer( 'short_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'short-stream-core' ) );
		}

		$seeded = self::generate_smart_notifications();
		$current = self::get_notifications();

		$existing_ids = array_column( $current, 'id' );
		$existing_titles = array_column( $current, 'title' );
		$added = 0;

		foreach ( $seeded as $s ) {
			if ( ! in_array( $s['title'], $existing_titles, true ) && ! in_array( $s['id'], $existing_ids, true ) ) {
				$current[] = $s;
				$added++;
			}
		}

		// If all default seeds already existed, update timestamps to fresh
		if ( 0 === $added && ! empty( $current ) ) {
			foreach ( $current as &$item ) {
				$item['created_at'] = time();
			}
		}

		update_option( 'short_system_notifications', $current );
		wp_send_json_success( array( 'added' => $added, 'total' => count( $current ) ) );
	}

	public static function render_cloudinary_uploader_hub() {
		$cloud_name   = get_option( 'shorttv_cloudinary_cloud_name', 'your-cloud' );
		$upload_preset = get_option( 'shorttv_cloudinary_upload_preset', 'my_video_preset' );
		$asset_folder  = get_option( 'shorttv_cloudinary_folder', 'short_dramas' );

		// Retrieve all existing drama series to display upload history
		$all_dramas = get_posts( array(
			'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => 100,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		?>
		<script src="https://upload-widget.cloudinary.com/global/all.js" type="text/javascript"></script>
		<style>
			.shorttv-hub-wrap { max-width: 1200px; margin: 20px auto 40px auto; padding: 0 12px; box-sizing: border-box; }
			.shorttv-hub-banner {
				display: flex;
				justify-content: space-between;
				align-items: center;
				flex-wrap: wrap;
				gap: 16px;
				margin-bottom: 20px;
				background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
				color: #fff;
				padding: 20px 24px;
				border-radius: 12px;
				box-shadow: 0 4px 15px rgba(0,0,0,0.15);
			}
			.shorttv-hub-banner h1 {
				color: #38bdf8;
				margin: 0 0 6px 0;
				font-size: 22px;
				font-weight: 800;
				display: flex;
				align-items: center;
				gap: 8px;
			}
			.shorttv-hub-grid {
				display: grid;
				grid-template-columns: 320px 1fr;
				gap: 20px;
				align-items: flex-start;
			}
			.shorttv-card {
				background: #ffffff;
				border: 1px solid #cbd5e1;
				border-radius: 12px;
				padding: 20px;
				box-shadow: 0 1px 4px rgba(0,0,0,0.05);
				box-sizing: border-box;
				overflow: hidden;
			}
			.shorttv-table-responsive {
				width: 100%;
				overflow-x: auto;
				-webkit-overflow-scrolling: touch;
				margin-bottom: 15px;
			}
			.shorttv-table-responsive table {
				min-width: 550px;
				width: 100%;
			}
			.shorttv-tab-btn {
				background: #f1f5f9;
				border: 1px solid #cbd5e1;
				padding: 8px 16px;
				border-radius: 6px;
				font-weight: 700;
				font-size: 13px;
				cursor: pointer;
				color: #475569;
				transition: all 0.2s;
			}
			.shorttv-tab-btn.active {
				background: #0284c7;
				color: #ffffff;
				border-color: #0284c7;
			}

			/* Mobile Responsiveness */
			@media (max-width: 860px) {
				.shorttv-hub-grid {
					grid-template-columns: 1fr;
				}
				.shorttv-hub-banner {
					flex-direction: column;
					align-items: flex-start;
					padding: 16px;
				}
				.shorttv-hub-banner h1 {
					font-size: 19px;
				}
				.shorttv-card {
					padding: 16px;
				}
			}
		</style>

		<div class="wrap shorttv-hub-wrap">
			<!-- Header Banner -->
			<div class="shorttv-hub-banner">
				<div>
					<h1>
						<span>☁️ Cloudinary Video Hub & JSON Importer</span>
					</h1>
					<p style="margin:0; color:#94a3b8; font-size:13px;">
						Upload 9:16 vertical videos, view uploaded series & Cloudinary files, and export/sync exact JSON schemas.
					</p>
				</div>
				<div style="display:flex; gap:10px; flex-wrap:wrap; width:100%; max-width:fit-content;">
					<a href="<?php echo admin_url( 'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE ); ?>" class="button button-primary" style="background:#e11d48; border-color:#be123c; font-weight:700;">
						➕ Add New Drama Series
					</a>
					<a href="<?php echo admin_url( 'admin.php?page=short-settings&tab=cloudinary' ); ?>" class="button" style="background:#334155; color:#fff; border:none;">
						⚙️ Cloudinary Settings
					</a>
				</div>
			</div>

			<!-- Main Content Grid -->
			<div class="shorttv-hub-grid">
				
				<!-- Left Card: Drama Series Setup & Upload -->
				<div class="shorttv-card">
					<h3 style="margin:0 0 12px 0; font-size:16px; font-weight:800; color:#0f172a;">1. Drama Information</h3>
					
					<div style="margin-bottom:12px;">
						<label style="font-size:12px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">Drama Series Title <span style="color:#ef4444;">*</span></label>
						<input type="text" id="hub-drama-title" placeholder="e.g. One Move God Mode" style="width:100%; height:38px; border-radius:6px; border:1px solid #cbd5e1; font-weight:600; padding:0 10px;" />
						<span style="font-size:11px; color:#0284c7; display:block; margin-top:3px;">
							📁 Cloudinary folder: <code id="hub-preview-folder" style="font-size:11px; background:#f0f9ff; color:#0369a1; padding:2px 4px;"><?php echo esc_html( $asset_folder ); ?>/One Move God Mode</code>
						</span>
					</div>

					<div style="margin-bottom:12px;">
						<label style="font-size:12px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">9:16 Vertical Poster</label>
						<div style="display:flex; gap:6px;">
							<input type="text" id="hub-poster-url" placeholder="https://res.cloudinary.com/.../poster.jpg" style="flex:1; height:34px; font-size:12px; border-radius:6px; border:1px solid #cbd5e1;" />
							<button type="button" class="button button-small" id="btn-upload-hub-poster" style="background:#0284c7; color:#fff; border:none; font-weight:700;">☁️ Upload</button>
						</div>
					</div>

					<div style="margin-bottom:14px;">
						<label style="font-size:12px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">Storyline / Overview</label>
						<textarea id="hub-drama-overview" placeholder="Brief synopsis of this short drama series..." style="width:100%; height:70px; font-size:12px; border-radius:6px; border:1px solid #cbd5e1; padding:8px; box-sizing:border-box;"></textarea>
					</div>

					<div style="margin-bottom:16px;">
						<label style="font-size:12px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">Genres (comma separated)</label>
						<input type="text" id="hub-drama-genres" value="Billionaire, Romance, Drama" style="width:100%; height:34px; border-radius:6px; border:1px solid #cbd5e1; font-size:12px;" />
					</div>

					<h3 style="margin:16px 0 10px 0; font-size:15px; font-weight:800; color:#0f172a; border-top:1px solid #f1f5f9; padding-top:14px;">2. Upload Episodes</h3>
					
					<button type="button" class="button button-primary button-hero" id="btn-open-cloudinary-hub-widget" style="width:100%; justify-content:center; display:flex; align-items:center; gap:8px; background:linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border:none; font-weight:800; padding:12px 16px; border-radius:8px; font-size:15px; box-sizing:border-box; margin-bottom:12px;">
						<span>☁️ Select Videos & Upload to Folder</span>
					</button>

					<!-- Quick Add from Cloudinary URL/Asset Name -->
					<div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px; margin-bottom:14px;">
						<label style="font-size:11px; font-weight:700; color:#1e293b; display:block; margin-bottom:4px;">
							🔗 Or Paste Cloudinary Video URL:
						</label>
						<div style="display:flex; gap:6px;">
							<input type="text" id="quick-add-video-url" placeholder="https://res.cloudinary.com/.../video.mp4" style="flex:1; height:32px; font-size:11px; border-radius:6px; border:1px solid #cbd5e1;" />
							<button type="button" class="button button-small" id="btn-quick-add-video" style="background:#0f172a; color:#fff; border:none; font-weight:700;">+ Add</button>
						</div>
					</div>

					<div style="border-top:1px solid #f1f5f9; padding-top:12px;">
						<label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">Active Cloud Name:</label>
						<code style="background:#f8fafc; display:block; padding:4px 6px; border-radius:4px; font-size:11px; margin-bottom:6px; word-break:break-all;"><?php echo esc_html( $cloud_name ); ?></code>

						<label style="font-size:11px; font-weight:700; color:#64748b; display:block; margin-bottom:4px;">Active Preset:</label>
						<code style="background:#f8fafc; display:block; padding:4px 6px; border-radius:4px; font-size:11px; word-break:break-all;"><?php echo esc_html( $upload_preset ); ?></code>
					</div>
				</div>

				<!-- Right Card: Tabs for Active Session Queue vs Uploaded Series / Files -->
				<div class="shorttv-card">
					<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:16px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
						<div style="display:flex; gap:8px;">
							<button type="button" class="shorttv-tab-btn active" id="tab-btn-queue">Current Uploads (<span id="queue-count">0</span>)</button>
							<button type="button" class="shorttv-tab-btn" id="tab-btn-history">All Uploaded Series (<?php echo count( $all_dramas ); ?>)</button>
						</div>
						<div style="display:flex; gap:8px;">
							<button type="button" class="button" id="btn-clear-queue" style="display:none; color:#dc2626;">Clear</button>
							<button type="button" class="button button-primary" id="btn-create-drama-from-queue" style="display:none; background:#16a34a; border:none; font-weight:700;">🚀 Create Drama Series</button>
						</div>
					</div>

					<!-- TAB 1: Current Session Uploads -->
					<div id="tab-content-queue">
						<div id="hub-upload-empty-state" style="padding:40px 16px; text-align:center; background:#f8fafc; border:2px dashed #cbd5e1; border-radius:8px; color:#64748b;">
							<div style="font-size:36px; margin-bottom:8px;">📱</div>
							<strong style="display:block; color:#1e293b; font-size:15px; margin-bottom:4px;">No videos uploaded in this session yet</strong>
							<span style="font-size:13px;">Click <strong>"Select Videos & Upload"</strong> or paste your Cloudinary video URL above.</span>
						</div>

						<div id="hub-upload-table-wrap" style="display:none;">
							<div class="shorttv-table-responsive">
								<table class="wp-list-table widefat fixed striped">
									<thead>
										<tr>
											<th style="width:65px;">Ep #</th>
											<th>Title / File</th>
											<th>MP4 Link</th>
											<th>HLS Stream (.m3u8)</th>
											<th style="width:70px;">Action</th>
										</tr>
									</thead>
									<tbody id="hub-upload-tbody"></tbody>
								</table>
							</div>
						</div>

						<div id="hub-json-output-wrap" style="display:none; margin-top:20px;">
							<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; flex-wrap:wrap; gap:8px;">
								<label style="font-weight:700; color:#0f172a; font-size:13px;">
									⚡ Generated ShortTV JSON Schema:
								</label>
								<button type="button" class="button button-small" id="btn-copy-hub-json" style="background:#0284c7; color:#fff; border:none;">📋 Copy JSON</button>
							</div>
							<textarea id="hub-generated-json" style="width:100%; height:160px; font-family:monospace; font-size:12px; background:#020617; color:#38bdf8; border-radius:6px; padding:10px; box-sizing:border-box;" readonly></textarea>
						</div>
					</div>

					<!-- TAB 2: Uploaded Series & File Catalog -->
					<div id="tab-content-history" style="display:none;">
						<?php if ( empty( $all_dramas ) ) : ?>
							<div style="padding:40px 16px; text-align:center; background:#f8fafc; border:2px dashed #cbd5e1; border-radius:8px; color:#64748b;">
								<div style="font-size:36px; margin-bottom:8px;">🎬</div>
								<strong style="display:block; color:#1e293b; font-size:15px; margin-bottom:4px;">No drama series created yet</strong>
								<span style="font-size:13px;">Upload your first drama series or click "Add New Drama Series" above.</span>
							</div>
						<?php else : ?>
							<div class="shorttv-table-responsive">
								<table class="wp-list-table widefat fixed striped">
									<thead>
										<tr>
											<th style="width:60px;">Poster</th>
											<th>Series Title</th>
											<th style="width:80px;">Episodes</th>
											<th>Cloudinary Files / JSON</th>
											<th style="width:140px;">Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $all_dramas as $drama ) :
											$schema = \SHORT\Core\CPT\Video_CPT::get_series_schema( $drama->ID );
											$ep_count = count( $schema['episodes'] ?? array() );
											$poster = $schema['cover_assets']['vertical_poster'] ?? '';
											$drama_json = wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
										?>
											<tr>
												<td>
													<?php if ( $poster ) : ?>
														<img src="<?php echo esc_url( $poster ); ?>" style="width:48px; height:68px; object-fit:cover; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.2);" />
													<?php else : ?>
														<div style="width:48px; height:68px; background:#cbd5e1; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:18px;">🎬</div>
													<?php endif; ?>
												</td>
												<td>
													<strong style="font-size:14px; color:#0f172a; display:block; margin-bottom:4px;"><?php echo esc_html( $drama->post_title ); ?></strong>
													<code style="font-size:11px; color:#64748b;"><?php echo esc_html( $schema['media_id'] ?? 'short_' . $drama->ID ); ?></code>
												</td>
												<td>
													<strong style="color:#2563eb;"><?php echo (int) $ep_count; ?> Eps</strong>
												</td>
												<td>
													<button type="button" class="button button-small btn-view-series-json" data-id="<?php echo esc_attr( $drama->ID ); ?>" style="background:#0f172a; color:#38bdf8; border:none; margin-bottom:4px;">
														📄 View & Copy JSON
													</button>
													<div style="font-size:11px; color:#64748b;">
														<?php if ( ! empty( $schema['episodes'][0]['sources']['fallback_mp4'] ) ) : ?>
															<a href="<?php echo esc_url( $schema['episodes'][0]['sources']['fallback_mp4'] ); ?>" target="_blank" style="color:#2563eb;">Ep 1 MP4 ↗</a>
														<?php endif; ?>
													</div>
													<textarea id="series-json-store-<?php echo esc_attr( $drama->ID ); ?>" style="display:none;"><?php echo esc_textarea( $drama_json ); ?></textarea>
												</td>
												<td>
													<div style="display:flex; flex-direction:column; gap:4px;">
														<a href="<?php echo admin_url( 'post.php?post=' . $drama->ID . '&action=edit' ); ?>" class="button button-small">✏️ Edit Series</a>
														<a href="<?php echo home_url( '/watch/' . $drama->ID . '/' ); ?>" target="_blank" class="button button-small button-primary" style="background:#e11d48; border-color:#be123c;">▶ Play 9:16</a>
													</div>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>

				</div>

			</div>
		</div>

		<!-- JSON Modal Viewer -->
		<div id="shorttv-json-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:99999; align-items:center; justify-content:center; padding:16px; box-sizing:border-box;">
			<div style="background:#0f172a; border-radius:12px; width:100%; max-width:650px; max-height:85vh; display:flex; flex-direction:column; padding:20px; box-sizing:border-box; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
					<h3 style="margin:0; color:#38bdf8; font-size:16px; font-weight:800;">📄 Exact ShortTV JSON Schema</h3>
					<button type="button" id="btn-close-json-modal" style="background:transparent; border:none; color:#94a3b8; font-size:20px; cursor:pointer;">&times;</button>
				</div>
				<textarea id="modal-json-content" style="flex:1; width:100%; min-height:300px; font-family:monospace; font-size:12px; background:#020617; color:#38bdf8; border:1px solid #334155; border-radius:6px; padding:12px; box-sizing:border-box;" readonly></textarea>
				<div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
					<button type="button" class="button" id="btn-modal-copy-json" style="background:#0284c7; color:#fff; border:none; font-weight:700;">📋 Copy Schema to Clipboard</button>
					<button type="button" class="button" id="btn-modal-close" style="background:#334155; color:#fff; border:none;">Close</button>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Persistent queue in localStorage
			var savedQueue = localStorage.getItem('shorttv_uploader_queue');
			var uploadedVideos = savedQueue ? JSON.parse(savedQueue) : [];

			var cloudName = '<?php echo esc_js( $cloud_name ); ?>';
			var uploadPreset = '<?php echo esc_js( $upload_preset ); ?>';
			var folder = '<?php echo esc_js( $asset_folder ); ?>';

			// Tab switching
			$('#tab-btn-queue').on('click', function() {
				$('.shorttv-tab-btn').removeClass('active');
				$(this).addClass('active');
				$('#tab-content-queue').show();
				$('#tab-content-history').hide();
			});

			$('#tab-btn-history').on('click', function() {
				$('.shorttv-tab-btn').removeClass('active');
				$(this).addClass('active');
				$('#tab-content-queue').hide();
				$('#tab-content-history').show();
			});

			// Modal JSON Viewer
			$(document).on('click', '.btn-view-series-json', function(e) {
				e.preventDefault();
				var id = $(this).data('id');
				var jsonText = $('#series-json-store-' + id).val();
				$('#modal-json-content').val(jsonText);
				$('#shorttv-json-modal').css('display', 'flex');
			});

			$('#btn-close-json-modal, #btn-modal-close').on('click', function() {
				$('#shorttv-json-modal').hide();
			});

			$('#btn-modal-copy-json').on('click', function() {
				var modalText = document.getElementById("modal-json-content");
				modalText.select();
				document.execCommand("copy");
				$(this).text('✓ Copied!');
				setTimeout(() => { $(this).text('📋 Copy Schema to Clipboard'); }, 2000);
			});

			function getDynamicTargetFolder() {
				var dramaTitle = $.trim($('#hub-drama-title').val());
				var cleanTitle = dramaTitle ? dramaTitle.replace(/[\\/:*?"<>|]/g, '').replace(/\s+/g, ' ') : '';
				return cleanTitle ? (folder + '/' + cleanTitle) : folder;
			}

			// Update folder preview as user types title
			$('#hub-drama-title').on('input keyup', function() {
				var targetF = getDynamicTargetFolder();
				$('#hub-preview-folder').text(targetF);
				generateHubJson();
			});

			$('#hub-poster-url, #hub-drama-overview, #hub-drama-genres').on('input keyup', function() {
				generateHubJson();
			});

			// Upload Poster to Dynamic Folder short/{Drama Title}
			$('#btn-upload-hub-poster').on('click', function(e) {
				e.preventDefault();
				var targetF = getDynamicTargetFolder();
				var posterWidget = cloudinary.createUploadWidget({
					cloudName: cloudName,
					uploadPreset: uploadPreset,
					folder: targetF,
					resourceType: 'image',
					multiple: false,
					clientAllowedFormats: ['png', 'jpg', 'jpeg', 'webp'],
					theme: 'purple',
				}, function(error, result) {
					if (!error && result && result.event === "success") {
						$('#hub-poster-url').val(result.info.secure_url);
						generateHubJson();
					}
				});
				posterWidget.open();
			});

			function addVideoItem(info) {
				var secureUrl = info.secure_url || info.url || '';
				var hlsUrl = secureUrl.replace(/\.[a-zA-Z0-9]+$/, '.m3u8');
				if (secureUrl.indexOf('/upload/') !== -1 && secureUrl.indexOf('/upload/sp_hd/') === -1) {
					hlsUrl = secureUrl.replace('/upload/', '/upload/sp_hd/').replace(/\.[a-zA-Z0-9]+$/, '.m3u8');
				}

				var epNum = uploadedVideos.length + 1;
				var filename = info.original_filename || info.public_id || ('Episode ' + epNum);
				if (filename.indexOf('/') !== -1) {
					filename = filename.split('/').pop();
				}

				uploadedVideos.push({
					episode_number: epNum,
					original_filename: filename,
					mp4_url: secureUrl,
					hls_url: hlsUrl,
					duration: Math.round(info.duration || 120),
				});

				localStorage.setItem('shorttv_uploader_queue', JSON.stringify(uploadedVideos));
				renderQueueTable();
			}

			// Quick Add Video URL
			$('#btn-quick-add-video').on('click', function(e) {
				e.preventDefault();
				var val = $.trim($('#quick-add-video-url').val());
				if (!val) {
					alert('Please enter a Cloudinary video URL.');
					return;
				}
				addVideoItem({
					secure_url: val,
					original_filename: val.split('/').pop().replace(/\.[a-zA-Z0-9]+$/, '')
				});
				$('#quick-add-video-url').val('');
			});

			// Open Cloudinary Video Widget with Dynamic Subfolder: short/{Drama Title}
			$('#btn-open-cloudinary-hub-widget').on('click', function(e) {
				e.preventDefault();
				var targetF = getDynamicTargetFolder();
				var dynamicVideoWidget = cloudinary.createUploadWidget({
					cloudName: cloudName,
					uploadPreset: uploadPreset,
					folder: targetF,
					resourceType: 'video',
					multiple: true,
					sources: ['local', 'url', 'camera'],
					clientAllowedFormats: ['mp4', 'mov', 'webm', 'm3u8'],
					theme: 'purple',
				}, function(error, result) {
					if (!error && result) {
						if (result.event === "success" && result.info) {
							addVideoItem(result.info);
						} else if (result.event === "queues-end") {
							renderQueueTable();
						}
					}
				});

				dynamicVideoWidget.open();
			});

			function generateHubJson() {
				var dramaTitle = $.trim($('#hub-drama-title').val()) || (uploadedVideos[0] ? uploadedVideos[0].original_filename.replace(/[_-]/g, ' ') : "My New Drama Series");
				var poster = $.trim($('#hub-poster-url').val()) || (uploadedVideos[0] ? uploadedVideos[0].mp4_url.replace(/\.[a-zA-Z0-9]+$/, '.jpg') : "");
				var overview = $.trim($('#hub-drama-overview').val()) || "A thrilling vertical short drama series.";
				var genresStr = $.trim($('#hub-drama-genres').val()) || "Billionaire, Romance, Drama";
				var genresArr = genresStr.split(',').map(s => s.trim()).filter(Boolean);

				var schemaEpisodes = uploadedVideos.map(function(item, idx) {
					var epNumber = idx + 1;
					var isFree = (epNumber <= 5);
					return {
						episode_number: epNumber,
						title: "Episode " + epNumber + ": " + (item.original_filename || "New Chapter"),
						overview: "Episode " + epNumber + " of " + dramaTitle,
						duration_seconds: item.duration || 120,
						access_control: {
							unlock_type: isFree ? "free" : "coins",
							coin_cost: isFree ? 0 : 15,
							ad_unlock_allowed: !isFree,
							is_unlocked: isFree
						},
						sources: {
							aspect_ratio: "9:16",
							hls_stream_url: item.hls_url,
							fallback_mp4: item.mp4_url
						}
					};
				});

				var fullJson = {
					media_id: "short_" + Math.floor(10000 + Math.random() * 90000),
					type: "short_series",
					title: dramaTitle,
					slug: dramaTitle.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
					is_published: true,
					release_year: new Date().getFullYear(),
					total_episodes: uploadedVideos.length || 1,
					language: "English",
					genres: genresArr,
					overview: overview,
					cover_assets: {
						vertical_poster: poster,
						horizontal_banner: poster
					},
					analytics: {
						view_count: 10000,
						like_count: 500,
						bookmark_count: 120
					},
					episodes: schemaEpisodes
				};

				$('#hub-generated-json').val(JSON.stringify(fullJson, null, 2));
			}

			function renderQueueTable() {
				if (uploadedVideos.length === 0) {
					$('#hub-upload-empty-state').show();
					$('#hub-upload-table-wrap, #hub-json-output-wrap, #btn-clear-queue, #btn-create-drama-from-queue').hide();
					$('#queue-count').text(0);
					return;
				}

				$('#hub-upload-empty-state').hide();
				$('#hub-upload-table-wrap, #hub-json-output-wrap, #btn-clear-queue, #btn-create-drama-from-queue').show();
				$('#queue-count').text(uploadedVideos.length);

				var $tbody = $('#hub-upload-tbody');
				$tbody.empty();

				uploadedVideos.forEach(function(item, idx) {
					var tr = '<tr>' +
						'<td><strong style="color:#0284c7;">EP #' + (idx + 1) + '</strong></td>' +
						'<td><strong>' + (item.original_filename || 'Episode ' + (idx + 1)) + '</strong></td>' +
						'<td><a href="' + item.mp4_url + '" target="_blank" style="font-size:12px; color:#2563eb;">View MP4 ↗</a></td>' +
						'<td><code style="font-size:11px; word-break:break-all;">' + item.hls_url + '</code></td>' +
						'<td><button type="button" class="button button-small btn-remove-row" data-idx="' + idx + '" style="color:#dc2626;">Delete</button></td>' +
					'</tr>';
					$tbody.append(tr);
				});

				generateHubJson();
			}

			// Render queue on load if items exist
			if (uploadedVideos.length > 0) {
				renderQueueTable();
			}

			$('#btn-copy-hub-json').on('click', function(e) {
				e.preventDefault();
				var copyText = document.getElementById("hub-generated-json");
				copyText.select();
				document.execCommand("copy");
				$(this).text('✓ Copied!');
				setTimeout(() => { $(this).text('📋 Copy JSON'); }, 2000);
			});

			$(document).on('click', '.btn-remove-row', function(e) {
				e.preventDefault();
				var idx = $(this).data('idx');
				uploadedVideos.splice(idx, 1);
				localStorage.setItem('shorttv_uploader_queue', JSON.stringify(uploadedVideos));
				renderQueueTable();
			});

			$('#btn-clear-queue').on('click', function(e) {
				e.preventDefault();
				uploadedVideos = [];
				localStorage.removeItem('shorttv_uploader_queue');
				renderQueueTable();
			});

			$('#btn-create-drama-from-queue').on('click', function(e) {
				e.preventDefault();
				generateHubJson();
				var jsonStr = $('#hub-generated-json').val();
				sessionStorage.setItem('shorttv_pending_import_json', jsonStr);
				window.location.href = '<?php echo admin_url( 'post-new.php?post_type=' . \SHORT\Core\CPT\Video_CPT::POST_TYPE . '&auto_import=1' ); ?>';
			});
		});
		</script>
		<?php
	}

	/**
	 * Register Lemon Squeezy REST API Webhook Endpoint
	 */
	public static function register_webhook_routes() {
		register_rest_route(
			'short-stream/v1',
			'/lemonsqueezy-webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_lemonsqueezy_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle incoming Lemon Squeezy Webhook events
	 */
	public static function handle_lemonsqueezy_webhook( \WP_REST_Request $request ) {
		$raw_payload  = $request->get_body();
		$sub_settings = get_option( 'short_subscription_settings', array() );
		$secret       = $sub_settings['lemonsqueezy_webhook_secret'] ?? '';

		// Verify HMAC signature if secret is configured
		if ( ! empty( $secret ) ) {
			$signature = $request->get_header( 'x-signature' );
			$computed  = hash_hmac( 'sha256', $raw_payload, $secret );
			if ( ! hash_equals( (string) $computed, (string) $signature ) ) {
				return new \WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
			}
		}

		$data = json_decode( $raw_payload, true );
		if ( empty( $data ) || empty( $data['meta']['event_name'] ) ) {
			return new \WP_REST_Response( array( 'error' => 'Invalid payload' ), 400 );
		}

		$event_name  = $data['meta']['event_name'];
		$custom_data = $data['meta']['custom_data'] ?? array();
		$attributes  = $data['data']['attributes'] ?? array();
		$user_email  = $attributes['user_email'] ?? ( $custom_data['user_email'] ?? '' );

		// Log last 20 events for inspection in WordPress
		$log = get_option( 'short_ls_webhook_log', array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		array_unshift( $log, array(
			'event'     => sanitize_text_field( $event_name ),
			'email'     => sanitize_email( $user_email ),
			'timestamp' => time(),
			'summary'   => sanitize_text_field( $attributes['first_order_item']['product_name'] ?? ( $attributes['product_name'] ?? 'Purchase' ) ),
		) );
		if ( count( $log ) > 20 ) {
			$log = array_slice( $log, 0, 20 );
		}
		update_option( 'short_ls_webhook_log', $log );

		return new \WP_REST_Response( array(
			'status' => 'success',
			'event'  => $event_name,
		), 200 );
	}
}
