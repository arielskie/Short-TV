<?php
get_header();
$auth_mode = get_query_var( 'short_auth' ) ?: 'login';
$prefilled_email = ! empty( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
?>

<style>
.netflix-auth-screen-layout {
  min-height: 100vh;
  background: #121212;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: 100%;
  box-sizing: border-box;
}
.netflix-auth-top-header {
  width: 100%;
  padding: 16px 40px;
  box-sizing: border-box;
}
.netflix-auth-header-inner {
  max-width: 1400px;
  margin: 0 auto;
}
.netflix-auth-main-body {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0 20px 24px;
  width: 100%;
  box-sizing: border-box;
}
.netflix-auth-modern-card {
  width: 100%;
  max-width: 440px !important;
  background: rgba(0, 0, 0, 0.78);
  border-radius: 6px;
  padding: 28px 36px 24px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.9);
  margin: 0 auto;
  box-sizing: border-box;
}
.auth-card-header {
  margin-bottom: 22px;
}
.netflix-auth-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 4px 0;
  line-height: 1.2;
}
.netflix-auth-subtitle {
  font-size: 0.88rem;
  color: #a3a3a3;
  margin: 4px 0 0 0;
}
.auth-feedback {
  display: none;
  background: #e87c03;
  color: #ffffff !important;
  padding: 10px 14px;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
  line-height: 1.35;
  margin-bottom: 14px;
  text-align: left;
}
.netflix-auth-modern-card .form-group {
  margin-bottom: 10px;
}
.netflix-auth-modern-card .form-group label {
  display: block;
  font-size: 0.8rem;
  color: #8c8c8c;
  margin-bottom: 4px;
}
.netflix-auth-modern-card .auth-input {
  width: 100%;
  height: 44px;
  background: #333333;
  border: 1px solid #444444;
  border-radius: 4px;
  color: #ffffff;
  padding: 0 14px;
  font-size: 0.95rem;
  box-sizing: border-box;
  outline: none;
}
.netflix-auth-modern-card .auth-input:focus {
  border-color: #ffffff;
  background: #444444;
}
.netflix-auth-modern-card .netflix-btn-red {
  width: 100%;
  height: 44px;
  background: #E50914 !important;
  color: #ffffff !important;
  font-size: 0.98rem !important;
  font-weight: 700 !important;
  border: none !important;
  border-radius: 4px !important;
  cursor: pointer;
  margin-top: 6px;
  transition: background 0.2s ease;
}
.netflix-auth-modern-card .netflix-btn-red:hover {
  background: #c11119 !important;
}
.netflix-auth-modern-card .auth-divider {
  display: flex;
  align-items: center;
  text-align: center;
  color: #737373;
  font-size: 0.8rem;
  margin: 12px 0;
}
.netflix-auth-modern-card .auth-divider::before,
.netflix-auth-modern-card .auth-divider::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}
.netflix-auth-modern-card .auth-divider span {
  padding: 0 10px;
}
.netflix-auth-modern-card .btn-google-auth {
  width: 100%;
  height: 40px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  color: #ffffff;
  font-size: 0.88rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.netflix-auth-modern-card .btn-google-auth:hover {
  background: rgba(255, 255, 255, 0.15);
}
.netflix-auth-help-row {
  margin-top: 10px;
  text-align: center;
}
.netflix-help-link {
  color: #a3a3a3;
  font-size: 0.82rem;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  text-decoration: none;
}
.netflix-help-link:hover {
  color: #ffffff;
}
.auth-footer-links {
  margin-top: 12px;
  text-align: center;
  font-size: 0.88rem;
}
.auth-footer-links a {
  color: #ffffff;
  font-weight: 600;
}
.netflix-recaptcha-notice {
  margin-top: 12px;
  font-size: 0.72rem;
  color: #737373;
  line-height: 1.35;
  text-align: center;
}
@media (max-width: 768px) {
  .netflix-auth-top-header {
    padding: 12px 16px;
  }
  .netflix-auth-modern-card {
    padding: 24px 16px;
    background: transparent;
    border: none;
    box-shadow: none;
  }
}
</style>

	<!-- Top Header Bar -->
	<header class="netflix-auth-top-header">
		<div class="netflix-auth-header-inner">
			<?php
			$auth_brand_settings = get_option( 'short_brand_settings', array() );
			$auth_logo_url       = function_exists( 'short_get_custom_logo_url' ) ? short_get_custom_logo_url() : 'https://i.postimg.cc/cH3CM5h4/image.png';
			$auth_brand_name     = ! empty( $auth_brand_settings['brand_name'] ) && 'My Blog' !== $auth_brand_settings['brand_name'] ? $auth_brand_settings['brand_name'] : ( get_bloginfo( 'name' ) !== 'My Blog' ? get_bloginfo( 'name' ) : 'ShortTV' );
			$auth_display_mode   = $auth_brand_settings['logo_display_mode'] ?? 'image_only';
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="short-brand-logo reel-brand-logo" aria-label="<?php echo esc_attr( $auth_brand_name ?: 'ShortTV' ); ?>" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;">
				<img src="<?php echo esc_url( $auth_logo_url ); ?>" alt="<?php echo esc_attr( $auth_brand_name ?: 'ShortTV' ); ?>" class="reel-brand-logo-img" style="height:38px; width:auto; max-height:48px; object-fit:contain; border-radius:6px;" onerror="this.src='https://i.postimg.cc/cH3CM5h4/image.png';">
				<?php if ( 'image_text' === $auth_display_mode ) : ?>
					<span class="reel-brand-logo-text" style="font-size:18px;font-weight:800;color:#fff;letter-spacing:-0.3px;"><?php echo esc_html( $auth_brand_name ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	</header>

	<!-- Centered Auth Card Container -->
	<div class="netflix-auth-main-body">
		<div class="netflix-auth-modern-card">
			<div class="auth-card-header">
				<h1 class="netflix-auth-title">
					<?php 
					if ( 'register' === $auth_mode ) {
						_e( 'Create Account', 'short-stream' );
					} elseif ( 'forgot' === $auth_mode ) {
						_e( 'Reset Password', 'short-stream' );
					} else {
						_e( 'Enter your info to sign in', 'short-stream' );
					}
					?>
				</h1>
				<?php if ( 'login' === $auth_mode ) : ?>
					<p class="netflix-auth-subtitle"><?php _e( 'Or get started with a new account.', 'short-stream' ); ?></p>
				<?php endif; ?>
				<div id="auth-feedback-msg" class="auth-feedback"></div>
			</div>

			<?php if ( 'register' === $auth_mode ) : ?>
				<form id="short-register-form" class="short-auth-form">
					<div class="form-group">
						<label><?php _e( 'Email Address', 'short-stream' ); ?></label>
						<input type="email" id="reg-email" value="<?php echo esc_attr( $prefilled_email ); ?>" required class="auth-input" placeholder="name@example.com">
					</div>
					<div class="form-group">
						<label><?php _e( 'Password', 'short-stream' ); ?></label>
						<input type="password" id="reg-password" required minlength="6" class="auth-input" placeholder="••••••••">
					</div>
					<button type="submit" class="btn-auth-submit netflix-btn-red" id="btn-register-submit"><?php _e( 'Get Started', 'short-stream' ); ?></button>
				</form>
				<div class="auth-switch-link" style="margin-top:16px; text-align:center; font-size:0.9rem;">
					<?php _e( 'Already have an account?', 'short-stream' ); ?> <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php _e( 'Sign In', 'short-stream' ); ?></a>
				</div>
			<?php elseif ( 'forgot' === $auth_mode ) : ?>
				<form id="short-forgot-form" class="short-auth-form">
					<div class="form-group">
						<label><?php _e( 'Enter your account email', 'short-stream' ); ?></label>
						<input type="email" id="forgot-email" value="<?php echo esc_attr( $prefilled_email ); ?>" required class="auth-input" placeholder="name@example.com">
					</div>
					<button type="submit" class="btn-auth-submit netflix-btn-red" id="btn-forgot-submit"><?php _e( 'Send Reset Link', 'short-stream' ); ?></button>
				</form>
				<div class="auth-switch-link" style="margin-top:16px; text-align:center; font-size:0.9rem;">
					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php _e( 'Back to Sign In', 'short-stream' ); ?></a>
				</div>
			<?php else : ?>
				<form id="short-login-form" class="short-auth-form">
					<div class="form-group">
						<label><?php _e( 'Email or mobile number', 'short-stream' ); ?></label>
						<input type="email" id="login-email" value="<?php echo esc_attr( $prefilled_email ); ?>" required class="auth-input" placeholder="Email or mobile number">
					</div>
					<div class="form-group" id="login-password-group">
						<label><?php _e( 'Password', 'short-stream' ); ?></label>
						<input type="password" id="login-password" required class="auth-input" placeholder="••••••••">
					</div>
					<button type="submit" class="btn-auth-submit netflix-btn-red" id="btn-login-submit"><?php _e( 'Continue', 'short-stream' ); ?></button>
				</form>

				<div class="auth-divider"><span><?php _e( 'OR', 'short-stream' ); ?></span></div>
				
				<button type="button" class="btn-google-auth" id="btn-google-signin">
					<svg width="18" height="18" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.9C6.2 7.4 8.9 5 12 5z"/><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/><path fill="#FBBC05" d="M5.3 14.7c-.2-.7-.4-1.5-.4-2.4s.2-1.7.4-2.4L1.6 7c-.8 1.6-1.3 3.4-1.3 5.3 0 1.9.5 3.7 1.3 5.3l3.7-2.9z"/><path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.4-6.7-5.3L1.6 16C3.5 19.8 7.4 23 12 23z"/></svg>
					<?php _e( 'Continue with Google', 'short-stream' ); ?>
				</button>

				<div class="netflix-auth-help-row">
					<a href="<?php echo esc_url( home_url( '/forgot-password/' ) ); ?>" class="netflix-help-link"><?php _e( 'Get Help', 'short-stream' ); ?> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg></a>
				</div>

				<div class="auth-footer-links">
					<a href="<?php echo esc_url( home_url( '/register/' ) . ( $prefilled_email ? '?email=' . urlencode( $prefilled_email ) : '' ) ); ?>"><?php _e( 'New to Short? Sign up now.', 'short-stream' ); ?></a>
				</div>
			<?php endif; ?>

			<div class="netflix-recaptcha-notice">
				<p><?php _e( 'This page is protected by Google reCAPTCHA to ensure you\'re not a bot.', 'short-stream' ); ?></p>
			</div>
		</div>
	</div>
</div>

<?php
get_footer();
