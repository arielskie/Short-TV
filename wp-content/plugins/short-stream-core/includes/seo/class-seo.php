<?php
namespace SHORT\Core\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SEO {
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'render_seo_tags' ), 1 );
	}

	public static function render_seo_tags() {
		global $post;

		$post_id = 0;
		if ( is_singular( \SHORT\Core\CPT\Video_CPT::POST_TYPE ) && $post ) {
			$post_id = $post->ID;
		} elseif ( get_query_var( 'short_id' ) ) {
			$post_id = absint( get_query_var( 'short_id' ) );
		}

		if ( ! $post_id ) {
			return;
		}

		$schema   = \SHORT\Core\CPT\Video_CPT::get_series_schema( $post_id );
		$title    = esc_attr( $schema['title'] ?? get_the_title( $post_id ) );
		$overview = esc_attr( wp_trim_words( $schema['overview'] ?? get_post_field( 'post_excerpt', $post_id ), 30 ) );
		$poster   = ! empty( $schema['poster'] ) ? $schema['poster'] : ( get_the_post_thumbnail_url( $post_id, 'full' ) ?: '' );
		$rating   = ! empty( $schema['rating'] ) ? number_format( (float) $schema['rating'], 1 ) : '5.0';
		$current_url = esc_url( get_permalink( $post_id ) ?: home_url( '/watch/' . $post_id ) );

		echo "\n<!-- ShortTV OpenGraph & Twitter Meta Tags -->\n";
		echo '<meta property="og:title" content="' . $title . ' - Short Stream" />' . "\n";
		echo '<meta property="og:description" content="' . $overview . '" />' . "\n";
		echo '<meta property="og:type" content="video.tv_show" />' . "\n";
		echo '<meta property="og:url" content="' . $current_url . '" />' . "\n";
		if ( $poster ) {
			echo '<meta property="og:image" content="' . esc_url( $poster ) . '" />' . "\n";
		}
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . $title . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . $overview . '" />' . "\n";
		if ( $poster ) {
			echo '<meta name="twitter:image" content="' . esc_url( $poster ) . '" />' . "\n";
		}

		$json_ld = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'TVSeries',
			'name'        => $title,
			'description' => $overview,
			'image'       => $poster,
		);
		if ( $rating ) {
			$json_ld['aggregateRating'] = array(
				'@type'       => 'AggregateRating',
				'ratingValue' => $rating,
				'bestRating'  => '5.0',
				'ratingCount' => 50,
			);
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $json_ld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
	}
}
