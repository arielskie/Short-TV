<?php
get_header();
?>

<?php
$initial_query = sanitize_text_field( $_GET['q'] ?? '' );
?>
<div class="short-page-container">
	<div class="search-page-header">
		<h1 class="page-main-title"><?php esc_html_e( 'Explore & Search', 'short-stream' ); ?></h1>
		<div class="search-page-input-wrap">
			<svg class="search-page-input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
			<input type="text" id="search-page-query-input" class="search-page-input" placeholder="<?php esc_attr_e( 'Search dramas, mini-series, genres...', 'short-stream' ); ?>" value="<?php echo esc_attr( $initial_query ); ?>" autocomplete="off">
		</div>
	</div>

	<div class="short-grid-cards" id="search-page-results-grid"></div>
</div>

<?php
get_footer();

