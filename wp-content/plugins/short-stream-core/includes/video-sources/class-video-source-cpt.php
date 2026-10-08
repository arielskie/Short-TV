<?php
namespace SHORT\Core\Video_Sources;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inactive Video Source CPT (Decommissioned in favor of ShortTV CPT & Cloudinary direct sources)
 */
class Video_Source_CPT {
	const POST_TYPE = 'short_video_source';

	public static function init() {
		// Fully disabled
	}

	public static function register_post_type() {
		// Fully disabled
	}

	public static function seed_default_sources( $force = false ) {
		// Fully disabled
	}
}
