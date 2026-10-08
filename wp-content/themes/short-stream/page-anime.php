<?php
/**
 * Anime Page Template
 *
 * @package Short_Stream
 */

get_header();

use SHORT\Core\TMDB\API as TMDB_API;

$trending_anime = class_exists( 'SHORT\Core\TMDB\API' ) ? TMDB_API::discover( 'tv', array( 'with_genres' => '16', 'with_original_language' => 'ja', 'sort_by' => 'popularity.desc' ), 1 ) : array();
$hero_items     = ( ! is_wp_error( $trending_anime ) && ! empty( $trending_anime['results'] ) ) ? array_slice( $trending_anime['results'], 0, 5 ) : array();

$db_sections = get_option( 'short_anime_sections', array() );
if ( empty( $db_sections ) && class_exists( 'SHORT\\Core\\Admin\\Dashboard' ) ) {
	$db_sections = \SHORT\Core\Admin\Dashboard::get_default_sections( 'anime' );
}

$curated_sections = ( ! empty( $db_sections ) && is_array( $db_sections ) ) ? array_values( array_filter( $db_sections, function( $s ) {
	return ! empty( $s['enabled'] );
} ) ) : array();
?>

<div class="short-homepage-wrapper">
	<!-- Multi-Slide Hero Billboard Slider -->
	<?php 
	if ( function_exists( 'short_render_hero_billboard' ) && ! empty( $hero_items ) ) {
		short_render_hero_billboard( $hero_items, 'IN ANIME TODAY' );
	}
	?>

	<!-- Rows Area -->
	<div class="short-content-rows">
		<!-- Dynamic Configured Carousel Rows -->
		<?php
		if ( ! empty( $curated_sections ) && function_exists( 'short_render_carousel_row' ) ) {
			foreach ( $curated_sections as $sec ) {
				$sec_type = $sec['type'] ?? '';
				if ( in_array( $sec_type, array( 'continue_watching', 'my_list' ), true ) ) {
					continue;
				}
				$items = function_exists( 'short_fetch_section_items' ) ? short_fetch_section_items( $sec, 'tv' ) : array();
				if ( ! empty( $items ) ) {
					short_render_carousel_row( $sec['title'], $items, $sec['layout'] ?? 'landscape' );
				}
			}
		}
		?>
	</div>
</div>

<?php
get_footer();
