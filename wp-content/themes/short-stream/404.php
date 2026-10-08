<?php
get_header();
?>
<div class="short-error-view">
	<h1>404</h1>
	<h2><?php _e( 'Lost your way in the stream?', 'short-stream' ); ?></h2>
	<p><?php _e( 'Sorry, we can&#8217;t find that page. You will find lots to explore on the home page.', 'short-stream' ); ?></p>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn-stream-login"><?php _e( 'Short Home', 'short-stream' ); ?></a>
</div>
<?php
get_footer();
