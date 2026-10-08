<?php
/**
 * Template Name: Notifications Page
 *
 * Dedicated mobile & desktop Notifications Center for Short.
 * URL: /notifications/ or /notification/
 *
 * @package Short_Stream
 */

get_header();
?>

<div class="short-page-container short-notifications-page">
	<style>
		.short-notifications-page {
			max-width: 900px;
			margin: 0 auto;
			padding: 95px 20px 100px 20px;
			box-sizing: border-box;
			min-height: 80vh;
		}
		@media (max-width: 768px) {
			.short-notifications-page {
				padding: 120px 14px 100px 14px !important;
				margin-top: 0 !important;
			}
			.notif-page-header {
				margin-bottom: 18px !important;
			}
			.notif-page-header .page-main-title {
				font-size: 22px !important;
			}
		}
		.notif-page-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 14px;
			margin-bottom: 24px;
		}
		.notif-page-title-wrap {
			flex: 1;
			min-width: 200px;
		}
		.notif-page-header .page-main-title {
			font-size: 26px;
			font-weight: 800;
			color: #ffffff;
			margin: 0 0 6px 0;
			line-height: 1.2;
		}
		.notif-page-subtitle {
			font-size: 13.5px;
			color: #94a3b8;
			margin: 0;
		}
		.notif-page-actions {
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.notif-page-mark-read {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.15);
			color: #cbd5e1;
			font-size: 12.5px;
			font-weight: 700;
			padding: 8px 14px;
			border-radius: 20px;
			cursor: pointer;
			transition: all 0.2s ease;
		}
		.notif-page-mark-read:hover {
			background: rgba(255, 255, 255, 0.16);
			color: #ffffff;
			border-color: rgba(255, 255, 255, 0.3);
		}
		.notifications-page-card {
			width: 100%;
			box-sizing: border-box;
		}
		.notif-page-list {
			display: flex;
			flex-direction: column;
			gap: 14px;
		}
		.notif-page-item {
			display: flex;
			align-items: flex-start;
			gap: 16px;
			background: #18181f;
			border: 1px solid rgba(255, 255, 255, 0.08);
			border-radius: 14px;
			padding: 16px 18px;
			text-decoration: none;
			color: inherit;
			position: relative;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.notif-page-item:hover {
			background: #20202a;
			border-color: rgba(255, 255, 255, 0.18);
			transform: translateY(-2px);
			box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
		}
		.notif-page-item.is-unread {
			border-left: 4px solid var(--theme-accent, #ff2d55);
			background: #1c1c24;
		}
		.notif-page-card-poster {
			width: 56px;
			height: 78px;
			border-radius: 8px;
			object-fit: cover;
			background: #0f1015;
			flex-shrink: 0;
		}
		.notif-page-poster-placeholder {
			display: flex;
			align-items: center;
			justify-content: center;
			color: var(--theme-accent, #ff2d55);
			background: rgba(255, 255, 255, 0.04);
		}
		.notif-page-item-info {
			flex: 1;
			min-width: 0;
		}
		.notif-page-tag-row {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 6px;
			flex-wrap: wrap;
		}
		.notif-item-badge {
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.4px;
			padding: 3px 8px;
			border-radius: 5px;
			color: #ffffff;
		}
		.notif-item-time {
			font-size: 11.5px;
			color: #64748b;
			font-weight: 600;
		}
		.notif-page-item-title {
			font-size: 15px;
			font-weight: 700;
			color: #ffffff;
			margin: 0 0 4px 0;
			line-height: 1.3;
		}
		.notif-page-item-desc {
			font-size: 13px;
			color: #94a3b8;
			margin: 0;
			line-height: 1.45;
		}
		.notif-unread-dot {
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: var(--theme-accent, #ff2d55);
			flex-shrink: 0;
			margin-top: 4px;
			box-shadow: 0 0 8px var(--theme-accent, #ff2d55);
		}
		.notif-page-item.is-read .notif-unread-dot {
			display: none;
		}
		.notif-page-empty {
			display: none;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			text-align: center;
			padding: 60px 20px;
			background: #16161c;
			border: 1px dashed rgba(255, 255, 255, 0.12);
			border-radius: 16px;
		}
		.notif-empty-icon-wrap {
			width: 68px;
			height: 68px;
			border-radius: 50%;
			background: rgba(255, 255, 255, 0.05);
			color: #64748b;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-bottom: 16px;
		}
		.notif-page-empty h3 {
			color: #ffffff;
			font-size: 17px;
			font-weight: 700;
			margin: 0 0 6px 0;
		}
		.notif-page-empty p {
			color: #64748b;
			font-size: 13px;
			max-width: 360px;
			margin: 0;
			line-height: 1.5;
		}
<?php
$notifications = class_exists( 'SHORT\Core\Admin\Dashboard' )
	? \SHORT\Core\Admin\Dashboard::get_notifications()
	: get_option( 'short_system_notifications', array() );

if ( ! is_array( $notifications ) ) {
	$notifications = array();
}
?>
	</style>

	<div class="page-header-row notif-page-header">
		<div class="notif-page-title-wrap">
			<h1 class="page-main-title"><?php _e( 'Notifications', 'short-stream' ); ?></h1>
			<p class="notif-page-subtitle"><?php _e( 'Stay updated with new releases, trending hits, and personalized alerts.', 'short-stream' ); ?></p>
		</div>
		<div class="notif-page-actions">
			<button type="button" class="btn-link-action notif-page-mark-read" id="notif-page-mark-all-read">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"></polyline></svg>
				<span><?php _e( 'Mark all read', 'short-stream' ); ?></span>
			</button>
		</div>
	</div>

	<div class="notifications-page-card">
		<div class="notif-page-list" id="notif-page-items-container">
			<?php if ( ! empty( $notifications ) ) : ?>
				<?php foreach ( $notifications as $n ) : 
					$id          = esc_attr( $n['id'] ?? '' );
					$tag         = esc_html( $n['tag'] ?? ( ( $n['type'] ?? '' ) === 'important' ? 'Important Update' : 'New Release' ) );
					$badge_color = esc_attr( $n['badge_color'] ?? ( ( $n['type'] ?? '' ) === 'important' ? '#dc2626' : '#0284c7' ) );
					$title       = esc_html( $n['title'] ?? '' );
					$message     = esc_html( $n['message'] ?? '' );
					$link        = ! empty( $n['link'] ) ? esc_url( $n['link'] ) : 'javascript:void(0);';
					$poster      = ! empty( $n['poster'] ) ? esc_url( $n['poster'] ) : '';
					$created_at  = intval( $n['created_at'] ?? time() );
					$time_str    = human_time_diff( $created_at, time() ) . ' ' . __( 'ago', 'short-stream' );
					$is_modal    = ( empty( $n['link'] ) || '#' === $n['link'] || home_url( '/' ) === $n['link'] );
				?>
					<a href="<?php echo $is_modal ? 'javascript:void(0);' : $link; ?>" class="notif-page-item short-notif-item is-unread" data-notif-id="<?php echo $id; ?>" <?php echo $is_modal ? 'data-open-modal="1"' : ''; ?>>
						<?php if ( ! empty( $poster ) ) : ?>
							<img src="<?php echo $poster; ?>" alt="<?php echo $title; ?>" class="notif-page-card-poster" onerror="this.style.display='none';">
						<?php else : ?>
							<div class="notif-page-card-poster notif-page-poster-placeholder">
								<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
							</div>
						<?php endif; ?>
						<div class="notif-page-item-info">
							<div class="notif-page-tag-row">
								<span class="notif-item-badge" style="background:<?php echo $badge_color; ?>;"><?php echo $tag; ?></span>
								<span class="notif-item-time"><?php echo esc_html( $time_str ); ?></span>
							</div>
							<h3 class="notif-page-item-title"><?php echo $title; ?></h3>
							<p class="notif-page-item-desc"><?php echo $message; ?></p>
						</div>
						<div class="notif-unread-dot"></div>
					</a>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="notif-page-empty" id="notif-page-empty-state" style="display:flex;">
					<div class="notif-empty-icon-wrap">
						<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
					</div>
					<h3><?php _e( 'No new notifications', 'short-stream' ); ?></h3>
					<p><?php _e( 'We will notify you here as soon as new movies, episodes, and updates arrive.', 'short-stream' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<script>
window._shortInitialNotifications = <?php echo wp_json_encode( $notifications ); ?>;
</script>

<?php
get_footer();
