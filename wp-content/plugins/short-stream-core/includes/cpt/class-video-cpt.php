<?php
namespace SHORT\Core\CPT;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Video_CPT {
	const POST_TYPE = 'short_title';
	const TAXONOMY  = 'video_genre';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type_and_taxonomies' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box_data' ) );

		// Enforce clean 1-column ShortTV studio layout (prevents WordPress from putting inputs in the narrow sidebar)
		add_filter( 'get_user_option_meta-box-order_' . self::POST_TYPE, array( __CLASS__, 'force_meta_box_order' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'custom_title_placeholder' ), 10, 2 );

		// Admin Table Custom Columns & Filters
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'register_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'register_sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'render_table_filters' ) );
		add_filter( 'parse_query', array( __CLASS__, 'filter_table_query' ) );

		// Admin Enqueue Scripts & Universal Admin Head CSS
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_head', array( __CLASS__, 'render_admin_head_css' ) );
		add_action( 'admin_footer-edit.php', array( __CLASS__, 'render_list_table_custom_dropdown_scripts' ) );

		// AJAX Handlers
		add_action( 'wp_ajax_shorttv_r2_upload', array( __CLASS__, 'ajax_r2_upload' ) );
		add_action( 'wp_ajax_shorttv_r2_get_folders', array( __CLASS__, 'ajax_r2_get_folders' ) );
		add_action( 'wp_ajax_shorttv_r2_create_folder', array( __CLASS__, 'ajax_r2_create_folder' ) );
		add_action( 'wp_ajax_shorttv_upload_local_poster', array( __CLASS__, 'ajax_upload_local_poster' ) );
		add_action( 'wp_ajax_shorttv_sideload_image', array( __CLASS__, 'ajax_sideload_image' ) );
		add_action( 'wp_ajax_shorttv_seed_sample_series', array( __CLASS__, 'ajax_seed_sample_series' ) );
		add_action( 'wp_ajax_shorttv_parse_json', array( __CLASS__, 'ajax_parse_json' ) );
		add_action( 'wp_ajax_shorttv_gumlet_create_upload', array( __CLASS__, 'ajax_gumlet_create_upload' ) );
		add_action( 'wp_ajax_shorttv_gumlet_check_status', array( __CLASS__, 'ajax_gumlet_check_status' ) );
		add_action( 'wp_ajax_shorttv_gumlet_get_folders', array( __CLASS__, 'ajax_gumlet_get_folders' ) );
		add_action( 'wp_ajax_shorttv_gumlet_create_folder', array( __CLASS__, 'ajax_gumlet_create_folder' ) );
		add_action( 'wp_ajax_shorttv_save_storage_settings', array( __CLASS__, 'ajax_save_storage_settings' ) );
		add_action( 'wp_ajax_shorttv_quick_save_drama', array( __CLASS__, 'ajax_quick_save_drama' ) );
		add_action( 'wp_ajax_shorttv_increment_view', array( __CLASS__, 'ajax_increment_view' ) );
		add_action( 'wp_ajax_nopriv_shorttv_increment_view', array( __CLASS__, 'ajax_increment_view' ) );
		add_action( 'wp_ajax_shorttv_toggle_like', array( __CLASS__, 'ajax_toggle_like' ) );
		add_action( 'wp_ajax_nopriv_shorttv_toggle_like', array( __CLASS__, 'ajax_toggle_like' ) );
		add_action( 'wp_ajax_shorttv_submit_rating', array( __CLASS__, 'ajax_submit_rating' ) );
		add_action( 'wp_ajax_nopriv_shorttv_submit_rating', array( __CLASS__, 'ajax_submit_rating' ) );
		add_action( 'wp_ajax_shorttv_save_history', array( __CLASS__, 'ajax_save_history' ) );
		add_action( 'wp_ajax_nopriv_shorttv_save_history', array( __CLASS__, 'ajax_save_history' ) );
		add_action( 'wp_ajax_shorttv_clear_history', array( __CLASS__, 'ajax_clear_history' ) );
		add_action( 'wp_ajax_nopriv_shorttv_clear_history', array( __CLASS__, 'ajax_clear_history' ) );

		// Disable Screen Options & Help tabs on Drama Studio editor
		add_filter( 'screen_options_show_screen', array( __CLASS__, 'disable_screen_options' ), 10, 2 );

		// Delete sample drama if present and prevent auto-seeding
		add_action( 'admin_init', array( __CLASS__, 'cleanup_sample_series' ) );
	}

	public static function disable_screen_options( $show, $screen ) {
		if ( $screen && ( self::POST_TYPE === $screen->post_type || 'video' === $screen->post_type ) ) {
			return false;
		}
		return $show;
	}

	public static function render_admin_head_css() {
		$screen = get_current_screen();
		if ( $screen && ( self::POST_TYPE === $screen->post_type || 'video' === $screen->post_type ) ) {
			if ( method_exists( $screen, 'remove_help_tabs' ) ) {
				$screen->remove_help_tabs();
			}
			echo '<style>
				#screen-meta,
				#screen-meta-links,
				#screen-options-link-wrap,
				#contextual-help-link-wrap,
				#submitdiv,
				.postbox#submitdiv,
				#postbox-container-1,
				#side-sortables,
				.edit-post-header,
				#titlediv,
				.wp-heading-inline,
				.page-title-action {
					display: none !important;
					visibility: hidden !important;
					height: 0 !important;
					margin: 0 !important;
					padding: 0 !important;
					border: none !important;
					pointer-events: none !important;
				}
				#poststuff #post-body.columns-2 {
					margin-right: 0 !important;
				}
				#poststuff #post-body-content {
					margin-bottom: 0 !important;
					float: none !important;
					width: 100% !important;
				}
				#shorttv_studio_meta {
					background: transparent !important;
					border: none !important;
					box-shadow: none !important;
					padding: 0 !important;
					margin: 0 !important;
				}
				#shorttv_studio_meta > .postbox-header,
				#shorttv_studio_meta > h2.hndle,
				#shorttv_studio_meta > .handlediv {
					display: none !important;
				}
				#shorttv_studio_meta > .inside {
					padding: 0 !important;
					margin: 0 !important;
				}
			</style>';
		}

		// Admin Table List View Styling (edit.php)
		if ( $screen && 'edit' === $screen->base && self::POST_TYPE === $screen->post_type ) {
			echo '<style>
				.wp-list-table.posts {
					table-layout: auto !important;
					width: 100% !important;
				}
				.wp-list-table.posts th {
					font-weight: 800 !important;
					color: #0f172a !important;
					background: #f8fafc !important;
					border-bottom: 2px solid #e2e8f0 !important;
					font-size: 12px !important;
					white-space: nowrap !important;
					padding: 8px 10px !important;
				}
				.wp-list-table.posts td {
					vertical-align: middle !important;
					padding: 8px 10px !important;
					font-size: 12px !important;
					white-space: nowrap !important;
				}
				.wp-list-table.posts td.column-title,
				.wp-list-table.posts th.column-title {
					white-space: normal !important;
					min-width: 220px !important;
				}
				.wp-list-table.posts td.column-title strong a.row-title {
					font-size: 13.5px !important;
					font-weight: 800 !important;
					color: #0f172a !important;
					display: inline-block !important;
					word-break: normal !important;
					white-space: normal !important;
				}
				.wp-list-table.posts tr:hover {
					background: #f8fafc !important;
				}
				.shorttv-admin-poster-thumb-wrap:hover .shorttv-admin-poster-img {
					transform: scale(1.06);
				}
				.column-poster { width: 55px !important; text-align: center; }
				.column-media_id { width: 90px !important; }
				.column-storage_prov { width: 105px !important; }
				.column-folder_dest { width: 140px !important; max-width: 180px !important; overflow: hidden; text-overflow: ellipsis; }
				.column-episodes_cnt { width: 95px !important; }
				.column-pricing_rule { width: 100px !important; }
				.column-analytics_col { width: 85px !important; }
				.column-genres_col { width: 120px !important; }
				.column-watch_btn { width: 130px !important; }
				.column-date { width: 110px !important; }
			</style>
			
			<!-- Quick Edit Modal UI -->
			<div id="shorttv-quick-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px); z-index:999999; align-items:center; justify-content:center;">
				<div style="background:#fff; border-radius:12px; padding:24px; width:400px; max-width:90%; box-shadow:0 20px 25px -5px rgba(0,0,0,0.3); border:1px solid #cbd5e1;">
					<h3 style="margin:0 0 16px; font-size:16px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
						<span>⚡ Quick Edit Drama</span>
					</h3>
					<input type="hidden" id="shorttv-quick-post-id" />
					<div style="margin-bottom:14px;">
						<label style="display:block; font-weight:700; font-size:12px; margin-bottom:4px; color:#334155;">Drama Title:</label>
						<input type="text" id="shorttv-quick-title" style="width:100%; height:34px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px; font-size:13px; font-weight:600;" />
					</div>
					<div style="margin-bottom:18px;">
						<label style="display:block; font-weight:700; font-size:12px; margin-bottom:4px; color:#334155;">Total Episodes:</label>
						<input type="number" id="shorttv-quick-total-eps" min="1" style="width:100%; height:34px; border-radius:6px; border:1px solid #cbd5e1; padding:0 10px; font-size:13px; font-weight:600;" />
					</div>
					<div style="display:flex; justify-content:flex-end; gap:8px;">
						<button type="button" class="button" id="btn-cancel-quick-edit" style="height:32px; font-weight:700;">Cancel</button>
						<button type="button" class="button button-primary" id="btn-save-quick-edit" style="height:32px; font-weight:800; background:#e11d48; border-color:#be123c;">Save Changes</button>
					</div>
				</div>
			</div>
			
			<script>
			jQuery(document).ready(function($) {
				$(document).on("click", ".btn-shorttv-quick-edit", function(e) {
					e.preventDefault();
					var id = $(this).data("id");
					var title = $(this).data("title");
					var total = $(this).data("total");
					$("#shorttv-quick-post-id").val(id);
					$("#shorttv-quick-title").val(title);
					$("#shorttv-quick-total-eps").val(total);
					$("#shorttv-quick-modal").css("display", "flex");
				});
				$("#btn-cancel-quick-edit").on("click", function() {
					$("#shorttv-quick-modal").hide();
				});
				$("#btn-save-quick-edit").on("click", function() {
					var $btn = $(this);
					var id = $("#shorttv-quick-post-id").val();
					var title = $.trim($("#shorttv-quick-title").val());
					var total = $("#shorttv-quick-total-eps").val();
					if (!title) { alert("Please enter a title"); return; }
					$btn.prop("disabled", true).text("Saving...");
					$.ajax({
						url: ajaxurl,
						type: "POST",
						dataType: "json",
						data: {
							action: "shorttv_quick_save_drama",
							nonce: "' . wp_create_nonce( 'shorttv_quick_nonce' ) . '",
							post_id: id,
							title: title,
							total_episodes: total
						},
						success: function(resp) {
							$btn.prop("disabled", false).text("Save Changes");
							if (resp.success) {
								location.reload();
							} else {
								alert("Failed: " + (resp.data ? resp.data.message : "Error"));
							}
						},
						error: function() {
							$btn.prop("disabled", false).text("Save Changes");
							alert("Network error while saving.");
						}
					});
				});
			});
			</script>';
		}
	}

	public static function force_meta_box_order( $order ) {
		return array(
			'normal'   => 'shorttv_studio_meta',
			'side'     => '',
			'advanced' => '',
		);
	}

	public static function custom_title_placeholder( $title, $post ) {
		if ( $post && self::POST_TYPE === $post->post_type ) {
			return __( '✨ Enter Short Drama Title (e.g. A Marriage Deal with the Billionaire)...', 'short-stream-core' );
		}
		return $title;
	}

	public static function register_post_type_and_taxonomies() {
		// 1. Register Taxonomy: Genres / Tags
		$tax_labels = array(
			'name'              => _x( 'Genres & Tags', 'taxonomy general name', 'short-stream-core' ),
			'singular_name'     => _x( 'Genre', 'taxonomy singular name', 'short-stream-core' ),
			'search_items'      => __( 'Search Genres', 'short-stream-core' ),
			'all_items'         => __( 'All Genres', 'short-stream-core' ),
			'edit_item'         => __( 'Edit Genre', 'short-stream-core' ),
			'update_item'       => __( 'Update Genre', 'short-stream-core' ),
			'add_new_item'      => __( 'Add New Genre', 'short-stream-core' ),
			'new_item_name'     => __( 'New Genre Name', 'short-stream-core' ),
			'menu_name'         => __( 'Genres', 'short-stream-core' ),
		);

		register_taxonomy( self::TAXONOMY, array( self::POST_TYPE ), array(
			'hierarchical'      => true,
			'labels'            => $tax_labels,
			'show_ui'           => false,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => false,
			'rewrite'           => array( 'slug' => 'genre' ),
		) );

		// 2. Register Post Type: ShortTV Series & Videos
		$labels = array(
			'name'                  => _x( 'Short Dramas & Series', 'Post type general name', 'short-stream-core' ),
			'singular_name'         => _x( 'Short Series', 'Post type singular name', 'short-stream-core' ),
			'menu_name'             => _x( 'ShortTV Dramas', 'Admin Menu text', 'short-stream-core' ),
			'name_admin_bar'        => _x( 'Short Drama', 'Add New on Toolbar', 'short-stream-core' ),
			'add_new'               => __( 'Add New Drama', 'short-stream-core' ),
			'add_new_item'          => __( 'Add New Short Drama Series', 'short-stream-core' ),
			'new_item'              => __( 'New Short Drama', 'short-stream-core' ),
			'edit_item'             => __( 'Edit Drama', 'short-stream-core' ),
			'view_item'             => __( 'View Drama', 'short-stream-core' ),
			'all_items'             => __( 'All Short Dramas', 'short-stream-core' ),
			'search_items'          => __( 'Search Short Dramas', 'short-stream-core' ),
			'not_found'             => __( 'No dramas found.', 'short-stream-core' ),
			'not_found_in_trash'    => __( 'No dramas found in Trash.', 'short-stream-core' ),
			'featured_image'        => _x( 'Vertical Poster (9:16)', 'Overrides the "Featured Image" phrase', 'short-stream-core' ),
			'set_featured_image'    => _x( 'Set vertical poster', 'Overrides the "Set featured image" phrase', 'short-stream-core' ),
			'remove_featured_image' => _x( 'Remove vertical poster', 'Overrides the "Remove featured image" phrase', 'short-stream-core' ),
			'use_featured_image'    => _x( 'Use as vertical poster', 'Overrides the "Use as featured image" phrase', 'short-stream-core' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'drama' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 4,
			'supports'           => array( 'title' ),
			'taxonomies'         => array( self::TAXONOMY ),
			'show_in_rest'       => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}

	public static function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Enqueue WordPress Media Library
		wp_enqueue_media();

		// Cloudinary Official Upload Widget Script
		wp_enqueue_script( 'cloudinary-upload-widget', 'https://upload-widget.cloudinary.com/global/all.js', array(), null, true );

		$cloudinary_cloud_name = get_option( 'shorttv_cloudinary_cloud_name', 'your-cloud' );
		$cloudinary_preset     = get_option( 'shorttv_cloudinary_upload_preset', 'my_video_preset' );
		$cloudinary_folder     = get_option( 'shorttv_cloudinary_folder', 'short' );
		$gumlet_enabled        = get_option( 'short_gumlet_enabled', '0' );
		$gumlet_workspace_id   = get_option( 'short_gumlet_workspace_id', '' );

		$sub_settings          = get_option( 'short_subscription_settings', array() );
		$global_coin_cost      = isset( $sub_settings['default_coin_cost'] ) && is_numeric( $sub_settings['default_coin_cost'] ) ? (int) $sub_settings['default_coin_cost'] : 15;

		$r2_enabled            = get_option( 'short_r2_enabled', '0' );
		$r2_public_domain      = get_option( 'short_r2_public_domain', '' );
		$r2_folder             = get_option( 'short_r2_folder', 'short' );

		wp_localize_script( 'cloudinary-upload-widget', 'SHORTTV_CLOUDINARY', array(
			'cloudName'        => $cloudinary_cloud_name,
			'uploadPreset'     => $cloudinary_preset,
			'folder'           => $cloudinary_folder,
			'gumletEnabled'    => ( '1' === (string) $gumlet_enabled ),
			'gumletWorkspaceId'=> $gumlet_workspace_id,
			'r2Enabled'        => ( '1' === (string) $r2_enabled ),
			'r2PublicDomain'   => $r2_public_domain,
			'r2Folder'         => $r2_folder,
			'defaultCoinCost'  => $global_coin_cost,
			'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
			'restUrl'          => esc_url_raw( rest_url( 'shorttv/v1/' ) ),
			'r2PresignUrl'     => esc_url_raw( rest_url( 'short/v1/r2/presign' ) ),
			'restNonce'        => wp_create_nonce( 'wp_rest' ),
			'nonce'            => wp_create_nonce( 'shorttv_admin_nonce' ),
		) );

		// Custom CSS for redesigned title, header, and studio workspace
		wp_add_inline_style( 'wp-admin', '
			.post-type-short_title #postbox-container-2,
			.post-type-short_title #normal-sortables {
				width: 100% !important;
				max-width: 100% !important;
			}
			.post-type-short_title #titlediv,
			.post-type-short_title .wp-heading-inline,
			.post-type-short_title .page-title-action,
			.post-type-short_title #screen-meta,
			.post-type-short_title #screen-meta-links,
			.post-type-short_title #screen-options-link-wrap,
			.post-type-short_title #contextual-help-link-wrap,
			.post-type-short_title #submitdiv,
			.post-type-short_title #side-sortables,
			.post-type-short_title #postbox-container-1,
			body.post-type-short_title #screen-meta,
			body.post-type-short_title #screen-meta-links,
			body.post-type-short_title #submitdiv,
			body.post-type-short_title .postbox#submitdiv {
				display: none !important;
				visibility: hidden !important;
				height: 0 !important;
				padding: 0 !important;
				margin: 0 !important;
				border: none !important;
			}
			.post-type-short_title #poststuff #post-body.columns-2 {
				margin-right: 0 !important;
			}
			.post-type-short_title #post-body-content {
				margin-bottom: 0 !important;
				float: none !important;
				width: 100% !important;
			}
			.post-type-short_title #shorttv_studio_meta {
				background: transparent !important;
				border: none !important;
				box-shadow: none !important;
				padding: 0 !important;
			}
			.post-type-short_title #shorttv_studio_meta > .postbox-header,
			.post-type-short_title #shorttv_studio_meta > h2.hndle,
			.post-type-short_title #shorttv_studio_meta > .handlediv {
				display: none !important;
			}
			.post-type-short_title #shorttv_studio_meta > .inside {
				padding: 0 !important;
				margin: 0 !important;
			}
			@keyframes shorttvSpin {
				from { transform: rotate(0deg); }
				to { transform: rotate(360deg); }
			}
			.shorttv-spinning {
				animation: shorttvSpin 0.75s linear infinite !important;
				transform-origin: center center;
			}
			@media screen and (max-width: 900px) {
				.shorttv-poster-details-grid {
					grid-template-columns: 1fr !important;
				}
			}
			/* Modern & Inline WP List Table Header Sorting Arrows */
			.post-type-short_title .wp-list-table th.sortable,
			.post-type-short_title .wp-list-table th.sorted {
				padding: 10px 12px !important;
				vertical-align: middle !important;
			}
			.post-type-short_title .wp-list-table th.sortable a,
			.post-type-short_title .wp-list-table th.sorted a {
				display: inline-flex !important;
				align-items: center !important;
				gap: 4px !important;
				white-space: nowrap !important;
				padding: 0 !important;
				color: #1e293b !important;
				font-weight: 700 !important;
				text-decoration: none !important;
			}
			.post-type-short_title .wp-list-table th.sortable a:hover,
			.post-type-short_title .wp-list-table th.sorted a:hover {
				color: #0284c7 !important;
			}
			.post-type-short_title .wp-list-table th .sorting-indicators {
				display: inline-flex !important;
				flex-direction: column !important;
				justify-content: center !important;
				align-items: center !important;
				margin: 0 0 0 4px !important;
				float: none !important;
				position: static !important;
				line-height: 1 !important;
				gap: 1px !important;
				vertical-align: middle !important;
			}
			.post-type-short_title .wp-list-table th.sortable .sorting-indicator,
			.post-type-short_title .wp-list-table th.sorted .sorting-indicator {
				float: none !important;
				margin: 0 !important;
				display: block !important;
				line-height: 1 !important;
			}

			/* =========================================================
			   Modern ShortTV Drama List Table Toolbar, Filters & Badges
			   ========================================================= */
			.post-type-short_title .subsubsub {
				display: inline-flex !important;
				align-items: center !important;
				gap: 8px !important;
				flex-wrap: wrap !important;
				margin: 14px 0 16px 0 !important;
				padding: 0 !important;
				list-style: none !important;
			}
			.post-type-short_title .subsubsub li {
				display: inline-flex !important;
				align-items: center !important;
				margin: 0 !important;
				font-size: 0 !important;
			}
			.post-type-short_title .subsubsub a {
				display: inline-flex !important;
				align-items: center !important;
				gap: 6px !important;
				padding: 6px 14px !important;
				border-radius: 9999px !important;
				font-size: 12.5px !important;
				font-weight: 700 !important;
				text-decoration: none !important;
				background: #ffffff !important;
				color: #475569 !important;
				border: 1px solid #e2e8f0 !important;
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
				transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
			}
			.post-type-short_title .subsubsub a:hover {
				background: #f8fafc !important;
				color: #0f172a !important;
				border-color: #cbd5e1 !important;
				transform: translateY(-1px) !important;
				box-shadow: 0 3px 6px rgba(0, 0, 0, 0.06) !important;
			}
			.post-type-short_title .subsubsub a.current {
				background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important;
				color: #ffffff !important;
				border-color: #0f172a !important;
				box-shadow: 0 3px 10px rgba(15, 23, 42, 0.25) !important;
			}
			.post-type-short_title .subsubsub a .count {
				display: inline-block !important;
				padding: 2px 7px !important;
				border-radius: 9999px !important;
				font-size: 11px !important;
				font-weight: 800 !important;
				background: #f1f5f9 !important;
				color: #475569 !important;
				margin-left: 2px !important;
				line-height: 1 !important;
			}
			.post-type-short_title .subsubsub a.current .count {
				background: rgba(255, 255, 255, 0.2) !important;
				color: #ffffff !important;
			}

			/* Modern Table Navigation Card */
			.post-type-short_title .tablenav.top {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				flex-wrap: wrap !important;
				gap: 10px !important;
				padding: 10px 16px !important;
				background: #ffffff !important;
				border: 1px solid #e2e8f0 !important;
				border-radius: 10px !important;
				margin: 0 0 16px 0 !important;
				box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03) !important;
				height: auto !important;
			}
			.post-type-short_title .tablenav.top .alignleft.actions {
				display: inline-flex !important;
				align-items: center !important;
				gap: 8px !important;
				flex-wrap: wrap !important;
				float: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			/* Select Boxes */
			.post-type-short_title .tablenav select,
			.post-type-short_title .shorttv-filter-select {
				height: 36px !important;
				line-height: 34px !important;
				border-radius: 8px !important;
				border: 1px solid #cbd5e1 !important;
				font-size: 12.5px !important;
				font-weight: 600 !important;
				color: #334155 !important;
				background-color: #f8fafc !important;
				padding: 0 28px 0 12px !important;
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
				transition: all 0.15s ease !important;
				cursor: pointer !important;
				max-width: 170px !important;
			}
			.post-type-short_title .tablenav select:hover,
			.post-type-short_title .shorttv-filter-select:hover {
				border-color: #94a3b8 !important;
				background-color: #ffffff !important;
			}
			.post-type-short_title .tablenav select:focus,
			.post-type-short_title .shorttv-filter-select:focus {
				border-color: #7c3aed !important;
				background-color: #ffffff !important;
				box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15) !important;
				outline: none !important;
			}

			/* Action & Filter Buttons */
			.post-type-short_title .tablenav .button.action,
			.post-type-short_title #doaction,
			.post-type-short_title #doaction2 {
				height: 36px !important;
				line-height: 34px !important;
				padding: 0 16px !important;
				border-radius: 8px !important;
				font-size: 12.5px !important;
				font-weight: 700 !important;
				background: #f1f5f9 !important;
				border: 1px solid #cbd5e1 !important;
				color: #334155 !important;
				cursor: pointer !important;
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
				transition: all 0.15s ease !important;
			}
			.post-type-short_title .tablenav .button.action:hover,
			.post-type-short_title #doaction:hover,
			.post-type-short_title #doaction2:hover {
				background: #e2e8f0 !important;
				color: #0f172a !important;
				border-color: #94a3b8 !important;
				transform: translateY(-1px) !important;
			}

			.post-type-short_title #post-query-submit {
				height: 36px !important;
				line-height: 34px !important;
				padding: 0 18px !important;
				border-radius: 8px !important;
				font-size: 12.5px !important;
				font-weight: 800 !important;
				background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
				border: 1px solid #6d28d9 !important;
				color: #ffffff !important;
				cursor: pointer !important;
				box-shadow: 0 2px 6px rgba(124, 58, 237, 0.3) !important;
				transition: all 0.15s ease !important;
			}
			.post-type-short_title #post-query-submit:hover {
				background: linear-gradient(135deg, #6d28d9 0%, #5b21b6 100%) !important;
				transform: translateY(-1px) !important;
				box-shadow: 0 4px 10px rgba(124, 58, 237, 0.4) !important;
			}

			/* Search Box */
			.post-type-short_title .search-box {
				display: inline-flex !important;
				align-items: center !important;
				gap: 6px !important;
				margin-bottom: 12px !important;
			}
			.post-type-short_title .search-box input[type="search"] {
				height: 36px !important;
				line-height: 34px !important;
				border-radius: 8px !important;
				border: 1px solid #cbd5e1 !important;
				padding: 0 14px !important;
				font-size: 13px !important;
				font-weight: 500 !important;
				background: #ffffff !important;
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03) !important;
				transition: all 0.15s ease !important;
			}
			.post-type-short_title .search-box input[type="search"]:focus {
				border-color: #7c3aed !important;
				box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15) !important;
				outline: none !important;
			}
			.post-type-short_title .search-box input[type="submit"] {
				height: 36px !important;
				line-height: 34px !important;
				padding: 0 16px !important;
				border-radius: 8px !important;
				font-size: 12.5px !important;
				font-weight: 700 !important;
				background: #1e293b !important;
				color: #ffffff !important;
				border: 1px solid #0f172a !important;
				cursor: pointer !important;
				box-shadow: 0 1px 3px rgba(15, 23, 42, 0.2) !important;
				transition: all 0.15s ease !important;
			}
			.post-type-short_title .search-box input[type="submit"]:hover {
				background: #0f172a !important;
				transform: translateY(-1px) !important;
			}

			/* Modern Table Rows & Borders */
			.post-type-short_title .wp-list-table {
				border-radius: 12px !important;
				overflow: hidden !important;
				border: 1px solid #e2e8f0 !important;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03) !important;
				background: #ffffff !important;
			}
			.post-type-short_title .wp-list-table thead th,
			.post-type-short_title .wp-list-table tfoot th {
				background: #f8fafc !important;
				color: #334155 !important;
				font-weight: 800 !important;
				font-size: 12px !important;
				text-transform: uppercase !important;
				letter-spacing: 0.3px !important;
				border-bottom: 2px solid #e2e8f0 !important;
				padding: 12px 10px !important;
			}
			.post-type-short_title .wp-list-table tbody tr {
				transition: background 0.12s ease !important;
			}
			.post-type-short_title .wp-list-table tbody tr:hover {
				background: #f8fafc !important;
			}
			.post-type-short_title .wp-list-table tbody td {
				padding: 12px 10px !important;
				vertical-align: middle !important;
				border-bottom: 1px solid #f1f5f9 !important;
			}
			.post-type-short_title input[type="checkbox"] {
				border-radius: 4px !important;
				border-color: #cbd5e1 !important;
				cursor: pointer !important;
			}
		' );
	}

	public static function register_columns( $columns ) {
		$new = array(
			'cb'            => $columns['cb'],
			'poster'        => __( 'Poster (9:16)', 'short-stream-core' ),
			'title'         => __( 'Drama Title', 'short-stream-core' ),
			'media_id'      => __( 'Media ID', 'short-stream-core' ),
			'storage_prov'  => __( 'Storage / CDN', 'short-stream-core' ),
			'folder_dest'   => __( 'Folder Path', 'short-stream-core' ),
			'episodes_cnt'  => __( 'Episodes', 'short-stream-core' ),
			'pricing_rule'  => __( 'Pricing / Lock', 'short-stream-core' ),
			'analytics_col' => __( 'Views / Likes', 'short-stream-core' ),
			'genres_col'    => __( 'Genres', 'short-stream-core' ),
			'watch_btn'     => __( 'Watch Vertical', 'short-stream-core' ),
			'date'          => $columns['date'],
		);
		return $new;
	}

	public static function register_sortable_columns( $columns ) {
		$columns['media_id']     = 'media_id';
		$columns['episodes_cnt'] = 'episodes_cnt';
		$columns['analytics_col']= 'analytics_col';
		return $columns;
	}

	public static function render_table_filters( $post_type ) {
		if ( self::POST_TYPE !== $post_type ) {
			return;
		}

		// 1. Filter by Storage Provider
		$current_provider = isset( $_GET['filter_provider'] ) ? sanitize_text_field( $_GET['filter_provider'] ) : '';
		echo '<select name="filter_provider" class="shorttv-filter-select">';
		echo '<option value="">All Providers</option>';
		echo '<option value="r2" ' . ( in_array( $current_provider, array( 'r2', 'cloudflare' ), true ) ? 'selected' : '' ) . '>🟠 Cloudflare</option>';
		echo '<option value="gumlet" ' . selected( $current_provider, 'gumlet', false ) . '>🎬 Gumlet Video</option>';
		echo '<option value="cloudinary" ' . selected( $current_provider, 'cloudinary', false ) . '>☁️ Cloudinary</option>';
		echo '</select>';

		// 2. Filter by Pricing Rule
		$current_pricing = isset( $_GET['filter_pricing'] ) ? sanitize_text_field( $_GET['filter_pricing'] ) : '';
		echo '<select name="filter_pricing" class="shorttv-filter-select">';
		echo '<option value="">All Pricing</option>';
		echo '<option value="free" ' . selected( $current_pricing, 'free', false ) . '>🟢 Free Stream</option>';
		echo '<option value="coins" ' . selected( $current_pricing, 'coins', false ) . '>🪙 Coins Locked</option>';
		echo '<option value="vip" ' . selected( $current_pricing, 'vip', false ) . '>👑 VIP Only</option>';
		echo '</select>';
	}

	public static function render_list_table_custom_dropdown_scripts() {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}
		?>
		<script>
		jQuery(document).ready(function($) {
			function initCustomTableDropdowns() {
				$('.tablenav select').each(function() {
					var $select = $(this);
					if ($select.hasClass('shorttv-customized')) return;
					$select.addClass('shorttv-customized').hide();

					var $wrap = $('<div class="shorttv-custom-select-wrap" style="position:relative; display:inline-block; vertical-align:middle; margin-right:6px;"></div>');
					var selectedText = $select.find('option:selected').text() || $select.find('option:first').text() || 'Select';

					var $btn = $('<button type="button" class="shorttv-custom-select-btn" style="height:36px; padding:0 12px; border-radius:8px; border:1px solid #cbd5e1; background:#ffffff; color:#334155; font-size:12.5px; font-weight:700; display:inline-flex; align-items:center; justify-content:space-between; gap:10px; cursor:pointer; box-shadow:0 1px 2px rgba(0,0,0,0.03); transition:all 0.15s ease; min-width:125px; outline:none;"></button>');
					$btn.html('<span class="shorttv-btn-label">' + $('<div>').text(selectedText).html() + '</span><span class="shorttv-btn-arrow" style="font-size:10px; color:#64748b; transition:transform 0.15s;">⏷</span>');

					var $menu = $('<div class="shorttv-custom-select-menu" style="display:none; position:absolute; top:calc(100% + 4px); left:0; min-width:100%; width:max-content; max-width:260px; max-height:280px; overflow-y:auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.15), 0 4px 6px -2px rgba(0,0,0,0.05); padding:6px; z-index:999999;"></div>');

					function populateOptions() {
						$menu.empty();
						$select.find('option').each(function() {
							var $opt = $(this);
							var val = $opt.val();
							var txt = $opt.text();
							var isSelected = $opt.is(':selected');

							var $item = $('<div class="shorttv-custom-select-item ' + (isSelected ? 'selected' : '') + '" data-val="' + $('<div>').text(val).html() + '" style="padding:8px 12px; border-radius:6px; font-size:12.5px; font-weight:600; color:' + (isSelected ? '#5b21b6' : '#334155') + '; background:' + (isSelected ? '#ede9fe' : 'transparent') + '; display:flex; align-items:center; justify-content:space-between; gap:8px; cursor:pointer; transition:all 0.12s ease; white-space:nowrap; margin-bottom:2px;"></div>');
							$item.html('<span>' + $('<div>').text(txt).html() + '</span>' + (isSelected ? '<span style="color:#7c3aed; font-weight:800; font-size:11px;">✓</span>' : ''));

							$item.hover(
								function() { if (!$(this).hasClass('selected')) $(this).css({'background':'#f5f3ff', 'color':'#6d28d9'}); },
								function() { if (!$(this).hasClass('selected')) $(this).css({'background':'transparent', 'color':'#334155'}); }
							);

							$item.on('click', function(e) {
								e.stopPropagation();
								$select.val(val).trigger('change');
								$btn.find('.shorttv-btn-label').text(txt);
								$menu.hide();
								$btn.removeClass('open').css({'border-color':'#cbd5e1', 'box-shadow':'0 1px 2px rgba(0,0,0,0.03)'});
								$btn.find('.shorttv-btn-arrow').text('⏷');
								populateOptions();
							});

							$menu.append($item);
						});
					}

					populateOptions();

					$btn.on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();
						var isOpen = $menu.is(':visible');
						$('.shorttv-custom-select-menu').hide();
						$('.shorttv-custom-select-btn').removeClass('open').css({'border-color':'#cbd5e1', 'box-shadow':'0 1px 2px rgba(0,0,0,0.03)'}).find('.shorttv-btn-arrow').text('⏷');

						if (!isOpen) {
							populateOptions();
							$menu.show();
							$(this).addClass('open').css({'border-color':'#7c3aed', 'box-shadow':'0 0 0 3px rgba(124,58,237,0.15)'});
							$(this).find('.shorttv-btn-arrow').text('⏶');
						}
					});

					$btn.hover(
						function() { if (!$(this).hasClass('open')) $(this).css('border-color', '#94a3b8'); },
						function() { if (!$(this).hasClass('open')) $(this).css('border-color', '#cbd5e1'); }
					);

					$wrap.append($btn).append($menu);
					$select.after($wrap);
				});
			}

			initCustomTableDropdowns();

			$(document).on('click', function(e) {
				if (!$(e.target).closest('.shorttv-custom-select-wrap').length) {
					$('.shorttv-custom-select-menu').hide();
					$('.shorttv-custom-select-btn').removeClass('open').css({'border-color':'#cbd5e1', 'box-shadow':'0 1px 2px rgba(0,0,0,0.03)'}).find('.shorttv-btn-arrow').text('⏷');
				}
			});
		});
		</script>
		<?php
	}

	public static function filter_table_query( $query ) {
		global $pagenow;
		if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
			return;
		}
		if ( ( $query->get( 'post_type' ) ?: '' ) !== self::POST_TYPE ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' ) ?: array();

		// Filter by Provider
		if ( ! empty( $_GET['filter_provider'] ) ) {
			$meta_query[] = array(
				'key'   => '_shorttv_active_provider',
				'value' => sanitize_text_field( $_GET['filter_provider'] ),
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}

	public static function render_column( $column, $post_id ) {
		$schema = self::get_series_schema( $post_id );
		$provider = get_post_meta( $post_id, '_shorttv_active_provider', true );

		// Auto-detect provider if missing or check actual video stream URLs
		$all_urls = '';
		if ( ! empty( $schema['cover_assets'] ) && is_array( $schema['cover_assets'] ) ) {
			$all_urls .= implode( ' ', $schema['cover_assets'] ) . ' ';
		}
		if ( ! empty( $schema['episodes'] ) && is_array( $schema['episodes'] ) ) {
			foreach ( $schema['episodes'] as $ep ) {
				if ( ! empty( $ep['sources'] ) && is_array( $ep['sources'] ) ) {
					$all_urls .= implode( ' ', $ep['sources'] ) . ' ';
				}
			}
		}

		$has_r2_meta = get_post_meta( $post_id, '_shorttv_r2_folder', true ) || get_post_meta( $post_id, '_shorttv_r2_subfolder', true );
		$has_gumlet_meta = get_post_meta( $post_id, '_shorttv_gumlet_folder_id', true ) || get_post_meta( $post_id, '_shorttv_gumlet_subfolder', true );
		$has_cloud_meta = get_post_meta( $post_id, '_shorttv_cloudinary_folder', true ) || get_post_meta( $post_id, '_shorttv_cloudinary_subfolder', true );

		if ( false !== strpos( $all_urls, 'r2.cloudflarestorage.com' ) || false !== strpos( $all_urls, 'r2.dev' ) || false !== strpos( $all_urls, 'cloudflarestream.com' ) || false !== strpos( $all_urls, 'videodelivery.net' ) || false !== strpos( $all_urls, 'cloudflare' ) || $has_r2_meta || in_array( $provider, array( 'r2', 'cloudflare', 'cloudflare_stream', 'cloudflare_r2', 'stream' ), true ) ) {
			$provider = 'r2';
		} elseif ( false !== strpos( $all_urls, 'gumlet.io' ) || false !== strpos( $all_urls, 'gumlet.net' ) || ( $has_gumlet_meta && ! $has_r2_meta ) || 'gumlet' === $provider ) {
			$provider = 'gumlet';
		} elseif ( false !== strpos( $all_urls, 'cloudinary.com' ) || ( $has_cloud_meta && ! $has_r2_meta ) || 'cloudinary' === $provider ) {
			$provider = 'cloudinary';
		} else {
			$provider = ( get_option( 'short_r2_enabled', '0' ) === '1' ) ? 'r2' : ( ( get_option( 'short_gumlet_enabled', '0' ) === '1' ) ? 'gumlet' : 'cloudinary' );
		}

		switch ( $column ) {
			case 'poster':
				$poster = $schema['cover_assets']['vertical_poster'] ?? '';
				if ( ! $poster ) {
					$thumb_id = get_post_thumbnail_id( $post_id );
					$poster = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';
				}
				if ( $poster && function_exists( 'short_normalize_url' ) ) {
					$poster = short_normalize_url( $poster );
				}
				$watch_url = home_url( '/watch/' . $post_id . '/' );
				echo '<div class="shorttv-admin-poster-thumb-wrap" style="position:relative; display:inline-block; border-radius:8px; overflow:hidden; box-shadow:0 3px 8px rgba(0,0,0,0.18);">';
				if ( $poster ) {
					echo '<a href="' . esc_url( $watch_url ) . '" target="_blank" title="Preview ReelShort Watch Page"><img class="shorttv-admin-poster-img" src="' . esc_url( $poster ) . '" style="width:52px; height:78px; object-fit:cover; display:block; transition:transform 0.2s;" loading="lazy" /></a>';
				} else {
					echo '<div style="width:52px; height:78px; background:linear-gradient(135deg,#1e293b,#0f172a); color:#94a3b8; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:800; text-align:center;">9:16<br>NO IMG</div>';
				}
				echo '</div>';
				break;

			case 'media_id':
				echo '<code style="background:#f1f5f9; border:1px solid #e2e8f0; padding:3px 7px; border-radius:5px; font-size:11.5px; font-weight:700; color:#0f172a;">' . esc_html( $schema['media_id'] ?? 'short_' . $post_id ) . '</code>';
				break;

			case 'storage_prov':
				if ( 'r2' === $provider || 'cloudflare' === $provider || 'cloudflare_stream' === $provider || 'cloudflare_r2' === $provider ) {
					echo '<span style="background:rgba(234,88,12,0.12); color:#c2410c; border:1px solid rgba(234,88,12,0.3); font-size:11px; font-weight:800; padding:3px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">🟠 Cloudflare</span>';
				} elseif ( 'gumlet' === $provider ) {
					echo '<span style="background:rgba(124,58,237,0.12); color:#6d28d9; border:1px solid rgba(124,58,237,0.3); font-size:11px; font-weight:800; padding:3px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">🎬 Gumlet</span>';
				} else {
					echo '<span style="background:rgba(2,132,199,0.12); color:#0284c7; border:1px solid rgba(2,132,199,0.3); font-size:11px; font-weight:800; padding:3px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">☁️ Cloudinary</span>';
				}
				break;

			case 'folder_dest':
				$clean_title = get_the_title( $post_id );
				if ( stripos( $clean_title, 'Auto Draft' ) !== false || stripos( $clean_title, 'Auto_Draft' ) !== false ) {
					$clean_title = '';
				}

				if ( 'r2' === $provider || 'cloudflare' === $provider || 'cloudflare_stream' === $provider || 'cloudflare_r2' === $provider ) {
					$r_f    = get_post_meta( $post_id, '_shorttv_r2_folder', true ) ?: 'short';
					$r_auto = get_post_meta( $post_id, '_shorttv_r2_auto_subfolder', true );
					$r_sub  = get_post_meta( $post_id, '_shorttv_r2_subfolder', true );
					if ( empty( $r_sub ) && '0' !== $r_auto && ! empty( $clean_title ) ) {
						$r_sub = $clean_title;
					}
					echo '<span style="font-size:11.5px; font-weight:600; color:#9a3412;">📁 ' . esc_html( $r_f ) . ( ( '0' !== $r_auto && ! empty( $r_sub ) ) ? ( ' / ' . esc_html( $r_sub ) ) : '' ) . '</span>';
				} elseif ( 'gumlet' === $provider ) {
					$f_id   = get_post_meta( $post_id, '_shorttv_gumlet_folder_id', true );
					$f_auto = get_post_meta( $post_id, '_shorttv_gumlet_auto_subfolder', true );
					$f_sub  = get_post_meta( $post_id, '_shorttv_gumlet_subfolder', true );
					if ( empty( $f_sub ) && '0' !== $f_auto && ! empty( $clean_title ) ) {
						$f_sub = $clean_title;
					}
					$f_name = get_post_meta( $post_id, '_shorttv_gumlet_folder_name', true );
					if ( empty( $f_name ) && ! empty( $f_id ) ) {
						$map = get_option( 'short_gumlet_cached_folders_map', array() );
						if ( is_array( $map ) && ! empty( $map[ $f_id ] ) ) {
							$f_name = $map[ $f_id ];
						}
					}
					if ( empty( $f_name ) ) {
						$f_name = empty( $f_id ) ? 'Root' : ( ( strlen( $f_id ) > 16 ) ? ( 'Folder (' . substr( $f_id, 0, 6 ) . '...)' ) : $f_id );
					}
					echo '<span style="font-size:11.5px; font-weight:600; color:#4c1d95;">📁 ' . esc_html( $f_name ) . ( ( '0' !== $f_auto && ! empty( $f_sub ) ) ? ( ' / ' . esc_html( $f_sub ) ) : '' ) . '</span>';
				} else {
					$c_f    = get_post_meta( $post_id, '_shorttv_cloudinary_folder', true ) ?: 'short';
					$c_auto = get_post_meta( $post_id, '_shorttv_cloudinary_auto_subfolder', true );
					$c_sub  = get_post_meta( $post_id, '_shorttv_cloudinary_subfolder', true );
					if ( strpos( $c_f, '/' ) !== false ) {
						$parts = explode( '/', $c_f );
						$c_f = trim( $parts[0] );
						if ( empty( $c_sub ) && ! empty( $parts[1] ) ) {
							$c_sub = trim( $parts[1] );
						}
					}
					if ( empty( $c_sub ) && '0' !== $c_auto && ! empty( $clean_title ) ) {
						$c_sub = $clean_title;
					}
					echo '<span style="font-size:11.5px; font-weight:600; color:#0369a1;">📁 ' . esc_html( $c_f ) . ( ( '0' !== $c_auto && ! empty( $c_sub ) ) ? ( ' / ' . esc_html( $c_sub ) ) : '' ) . '</span>';
				}
				break;

			case 'episodes_cnt':
				$count = count( $schema['episodes'] ?? array() );
				$total = $schema['total_episodes'] ?? $count;
				echo '<div style="display:inline-flex; align-items:center; gap:6px;">';
				echo '<span style="background:#e0f2fe; color:#0369a1; font-size:12px; font-weight:800; padding:3px 8px; border-radius:6px;">' . esc_html( $count ) . ' / ' . esc_html( $total ) . ' Eps</span>';
				echo '</div>';
				break;

			case 'pricing_rule':
				$episodes = $schema['episodes'] ?? array();
				$has_coins = false;
				$has_vip = false;
				$has_free = false;
				foreach ( $episodes as $ep ) {
					$u_type = strtolower( $ep['access_control']['unlock_type'] ?? 'free' );
					if ( 'coins' === $u_type ) $has_coins = true;
					if ( 'vip' === $u_type ) $has_vip = true;
					if ( 'free' === $u_type ) $has_free = true;
				}
				if ( $has_coins ) {
					echo '<span style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size:11px; font-weight:700; padding:2px 7px; border-radius:5px; display:inline-flex; align-items:center; gap:3px;">🪙 Coin Locked</span>';
				} elseif ( $has_vip ) {
					echo '<span style="background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:5px; display:inline-flex; align-items:center; gap:3px;">👑 VIP Only</span>';
				} else {
					echo '<span style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; font-size:11px; font-weight:700; padding:2px 7px; border-radius:5px; display:inline-flex; align-items:center; gap:3px;">🟢 100% Free</span>';
				}
				break;

			case 'analytics_col':
				$v_meta = get_post_meta( $post_id, '_shorttv_view_count', true );
				$l_meta = get_post_meta( $post_id, '_shorttv_like_count', true );
				$views = number_format( (int) ( ( '' !== $v_meta && false !== $v_meta ) ? $v_meta : ( $schema['analytics']['view_count'] ?? 0 ) ) );
				$likes = number_format( (int) ( ( '' !== $l_meta && false !== $l_meta ) ? $l_meta : ( $schema['analytics']['like_count'] ?? 0 ) ) );
				echo '<div style="font-size:11.5px; font-weight:600; line-height:1.4;">👁️ ' . esc_html( $views ) . '<br>❤️ ' . esc_html( $likes ) . '</div>';
				break;

			case 'genres_col':
				$genres = $schema['genres'] ?? array();
				if ( ! empty( $genres ) && is_array( $genres ) ) {
					$badges = array_map( function( $g ) {
						return '<span style="background:#fee2e2; color:#b91c1c; font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; margin-right:3px; display:inline-block; margin-bottom:2px;">' . esc_html( $g ) . '</span>';
					}, array_slice( $genres, 0, 3 ) );
					echo implode( '', $badges );
				} else {
					echo '<span style="color:#94a3b8;">—</span>';
				}
				break;

			case 'watch_btn':
				$watch_url = home_url( '/watch/' . $post_id . '/' );
				$edit_url  = get_edit_post_link( $post_id );
				echo '<div style="display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap;">';
				echo '<a href="' . esc_url( $watch_url ) . '" target="_blank" class="button button-small" style="background:linear-gradient(135deg,#e11d48,#be123c); border-color:#fb7185; color:#fff; font-weight:800; border-radius:5px; padding:0 8px; height:28px; line-height:26px; box-shadow:0 2px 6px rgba(225,29,72,0.35);">▶ Play 9:16</a>';
				echo '<button type="button" class="button button-small btn-shorttv-quick-edit" data-id="' . esc_attr( $post_id ) . '" data-title="' . esc_attr( get_the_title( $post_id ) ) . '" data-total="' . esc_attr( $schema['total_episodes'] ?? 60 ) . '" style="height:28px; line-height:26px; font-size:11px; font-weight:700; border-radius:5px; border:1px solid #cbd5e1; background:#fff; color:#334155;" title="Quick Edit Drama Title & Total Episodes">⚡ Quick</button>';
				echo '</div>';
				break;
		}
	}

	public static function add_meta_boxes() {
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'side' );
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'normal' );
		remove_meta_box( 'submitdiv', self::POST_TYPE, 'advanced' );

		add_meta_box(
			'shorttv_studio_meta',
			__( '🎬 ShortTV Drama Studio', 'short-stream-core' ),
			array( __CLASS__, 'render_studio_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function render_studio_meta_box( $post ) {
		wp_nonce_field( 'shorttv_meta_save', 'shorttv_nonce' );

		$schema = self::get_series_schema( $post->ID );
		$sub_settings = get_option( 'short_subscription_settings', array() );
		$global_coin_cost = isset( $sub_settings['default_coin_cost'] ) && is_numeric( $sub_settings['default_coin_cost'] ) ? (int) $sub_settings['default_coin_cost'] : 15;
		$poster_url = $schema['cover_assets']['vertical_poster'] ?? '';
		if ( strpos( $poster_url, 'your-cloud' ) !== false ) {
			$poster_url = '';
		}
		if ( $poster_url && function_exists( 'short_normalize_url' ) ) {
			$poster_url = short_normalize_url( $poster_url );
		}
		$episodes = $schema['episodes'] ?? array();
		$json_formatted = wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		?>
		<style>
			.shorttv-studio-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; color: #1e293b; }
			.shorttv-card-panel { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
			.shorttv-label { display: block; font-weight: 700; color: #0f172a; margin-bottom: 6px; font-size: 13px; }
			.shorttv-hint { margin: 5px 0 0 0; font-size: 11px; color: #64748b; line-height: 1.4; }
			
			/* Form controls styling */
			.shorttv-studio-wrap textarea {
				border: 1px solid #cbd5e1 !important;
				border-radius: 8px !important;
				padding: 10px 12px !important;
				font-size: 13px !important;
				box-sizing: border-box !important;
				background-color: #ffffff !important;
				color: #0f172a !important;
				width: 100% !important;
				transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
			}
			.shorttv-studio-wrap input:focus,
			.shorttv-studio-wrap select:focus,
			.shorttv-studio-wrap textarea:focus {
				border-color: #e11d48 !important;
				outline: none !important;
				box-shadow: 0 0 0 3px rgba(225,29,72,0.15) !important;
			}
			.shorttv-btn-primary { background: #e11d48 !important; border-color: #be123c !important; color: #fff !important; font-weight: 700 !important; border-radius: 8px !important; }
			.shorttv-btn-primary:hover { background: #be123c !important; }
			.shorttv-btn-success { background: #16a34a !important; border-color: #15803d !important; color: #fff !important; font-weight: 700 !important; border-radius: 8px !important; }
			.shorttv-btn-success:hover { background: #15803d !important; }

			@keyframes slideInLeft {
				from {
					opacity: 0;
					transform: translateX(-30px);
				}
				to {
					opacity: 1;
					transform: translateX(0);
				}
			}
			
			/* Drag and Drop Poster Dropzone */
			.shorttv-poster-dropzone {
				width: 100%; aspect-ratio: 9/16; background: #0f172a; border-radius: 12px; overflow: hidden;
				display: flex; flex-direction: column; align-items: center; justify-content: center;
				border: 2px dashed #94a3b8; position: relative; margin-bottom: 10px; cursor: pointer;
				transition: all 0.2s ease; user-select: none;
			}
			.shorttv-poster-dropzone:hover {
				border-color: #e11d48; background: #1e1b4b;
			}
			.shorttv-poster-dropzone.dragover {
				border-color: #e11d48; background: rgba(225,29,72,0.15); transform: scale(1.02);
			}
			.shorttv-poster-overlay-badge {
				position: absolute; bottom: 8px; left: 8px; right: 8px;
				background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);
				color: #fff; font-size: 11px; font-weight: 700; text-align: center;
				padding: 4px 8px; border-radius: 6px; pointer-events: none;
			}
			.shorttv-upload-progress-box {
				position: absolute; inset: 0; background: rgba(15,23,42,0.85); backdrop-filter: blur(6px);
				display: none; flex-direction: column; align-items: center; justify-content: center;
				z-index: 10; color: #fff; padding: 16px; text-align: center;
			}

			/* Episode & Detail Form Inputs */
			.shorttv-poster-details-grid input[type="text"],
			.shorttv-poster-details-grid input[type="number"],
			.shorttv-poster-details-grid select,
			#shorttv-episodes-wrapper input[type="text"],
			#shorttv-episodes-wrapper input[type="number"],
			#shorttv-episodes-wrapper select {
				height: 38px !important;
				min-height: 38px !important;
				max-height: 38px !important;
				line-height: 38px !important;
				border: 1px solid #cbd5e1 !important;
				border-radius: 8px !important;
				padding: 0 10px !important;
				font-size: 13px !important;
				box-sizing: border-box !important;
				background-color: #ffffff !important;
				color: #0f172a !important;
				width: 100% !important;
				margin: 0 !important;
				display: block !important;
				transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
			}

			.shorttv-field-label {
				display: block !important;
				font-size: 11px !important;
				font-weight: 700 !important;
				color: #475569 !important;
				margin-bottom: 5px !important;
				line-height: 1.2 !important;
				white-space: nowrap !important;
			}

			/* Stream input with inline upload button */
			.shorttv-stream-input-group {
				display: flex !important;
				flex-direction: row !important;
				align-items: stretch !important;
				width: 100% !important;
				height: 38px !important;
				border: 1px solid #cbd5e1 !important;
				border-radius: 8px !important;
				overflow: hidden !important;
				background: #ffffff !important;
				box-sizing: border-box !important;
			}
			.shorttv-stream-input-group input[type="text"] {
				flex: 1 1 auto !important;
				width: auto !important;
				min-width: 0 !important;
				height: 36px !important;
				min-height: 36px !important;
				max-height: 36px !important;
				border: none !important;
				box-shadow: none !important;
				padding: 0 10px !important;
				margin: 0 !important;
				border-radius: 0 !important;
				background: transparent !important;
			}
			.shorttv-stream-input-group .btn-cloudinary-upload,
			.shorttv-stream-input-group .btn-gumlet-upload,
			.shorttv-stream-input-group .btn-r2-upload {
				flex: 0 0 42px !important;
				width: 42px !important;
				height: 36px !important;
				min-height: 36px !important;
				max-height: 36px !important;
				padding: 0 !important;
				margin: 0 !important;
				border: none !important;
				border-left: 1px solid #cbd5e1 !important;
				border-radius: 0 !important;
				align-items: center !important;
				justify-content: center !important;
				cursor: pointer !important;
				font-size: 14px !important;
				box-sizing: border-box !important;
				transition: all 0.15s ease !important;
			}
			.shorttv-stream-input-group .btn-cloudinary-upload {
				background: #0f172a !important;
				color: #38bdf8 !important;
			}
			.shorttv-stream-input-group .btn-cloudinary-upload:hover {
				background: #1e293b !important;
				color: #ffffff !important;
			}
			.shorttv-stream-input-group .btn-gumlet-upload {
				background: #7c3aed !important;
				color: #ffffff !important;
			}
			.shorttv-stream-input-group .btn-gumlet-upload:hover {
				background: #6d28d9 !important;
			}
			.shorttv-stream-input-group .btn-r2-upload {
				background: #ea580c !important;
				color: #ffffff !important;
			}
			.shorttv-stream-input-group .btn-r2-upload:hover {
				background: #c2410c !important;
			}

			/* Scoped Provider Button Display Controls */
			.shorttv-provider-gumlet .shorttv-cloudinary-btn,
			.shorttv-provider-gumlet .btn-cloudinary-upload,
			.shorttv-provider-gumlet .shorttv-r2-btn,
			.shorttv-provider-gumlet .btn-r2-upload {
				display: none !important;
			}
			.shorttv-provider-gumlet .shorttv-gumlet-btn,
			.shorttv-provider-gumlet .btn-gumlet-upload {
				display: inline-flex !important;
			}

			.shorttv-provider-cloudinary .shorttv-gumlet-btn,
			.shorttv-provider-cloudinary .btn-gumlet-upload,
			.shorttv-provider-cloudinary .shorttv-r2-btn,
			.shorttv-provider-cloudinary .btn-r2-upload {
				display: none !important;
			}
			.shorttv-provider-cloudinary .shorttv-cloudinary-btn,
			.shorttv-provider-cloudinary .btn-cloudinary-upload {
				display: inline-flex !important;
			}

			.shorttv-provider-r2 .shorttv-gumlet-btn,
			.shorttv-provider-r2 .btn-gumlet-upload,
			.shorttv-provider-r2 .shorttv-cloudinary-btn,
			.shorttv-provider-r2 .btn-cloudinary-upload {
				display: none !important;
			}
			.shorttv-provider-r2 .shorttv-r2-btn,
			.shorttv-provider-r2 .btn-r2-upload {
				display: inline-flex !important;
			}

			/* Episodes Multi-Column Grid Container */
			#shorttv-episodes-wrapper {
				display: grid !important;
				grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				gap: 16px !important;
			}
			.shorttv-episode-card {
				display: flex !important;
				flex-direction: column !important;
				justify-content: space-between !important;
				background: #f8fafc !important;
				border: 1px solid #e2e8f0 !important;
				border-radius: 10px !important;
				padding: 16px !important;
				box-sizing: border-box !important;
			}

			/* Episode Card Grids - Clean 3-Column Layout (Ep # | Title | Access Rule) */
			.shorttv-ep-grid-main {
				display: grid !important;
				grid-template-columns: 75px 1fr 190px !important;
				gap: 14px !important;
				align-items: flex-end !important;
				margin-bottom: 12px !important;
			}
			.shorttv-ep-grid-streams {
				display: grid !important;
				grid-template-columns: 1fr 1fr !important;
				gap: 10px !important;
				align-items: flex-end !important;
			}
			.shorttv-grid-split-genres,
			.shorttv-grid-split-meta {
				display: grid !important;
				gap: 14px !important;
			}
			.shorttv-grid-split-genres {
				grid-template-columns: 1.4fr 1fr !important;
			}
			.shorttv-grid-split-meta {
				grid-template-columns: 1fr 1fr !important;
			}

			/* Mobile & Tablet Responsive Breakpoints */
			@media screen and (max-width: 1280px) {
				#shorttv-episodes-wrapper {
					grid-template-columns: 1fr !important;
				}
				.shorttv-ep-grid-main {
					grid-template-columns: 70px 1fr 170px !important;
					gap: 10px !important;
				}
				.shorttv-ep-grid-streams {
					grid-template-columns: 1fr 1fr !important;
					gap: 10px !important;
				}
			}

			@media screen and (max-width: 960px) {
				.shorttv-poster-details-grid {
					grid-template-columns: 1fr !important;
				}
				.shorttv-top-split {
					grid-template-columns: 1fr !important;
					gap: 20px !important;
				}
				.shorttv-top-split > div:first-child {
					max-width: 240px;
					margin: 0 auto;
					width: 100%;
				}
				.shorttv-ep-grid-main {
					grid-template-columns: 65px 1fr 150px !important;
					gap: 10px !important;
				}
				.shorttv-ep-grid-streams {
					grid-template-columns: 1fr !important;
					gap: 10px !important;
				}
				.shorttv-storage-toolbar-card {
					padding: 12px 14px !important;
				}
				.shorttv-uploader-section {
					flex-wrap: wrap !important;
					gap: 10px !important;
				}
			}

			@media screen and (max-width: 640px) {
				.shorttv-card-panel {
					padding: 14px !important;
				}
				.shorttv-ep-grid-main {
					grid-template-columns: 70px 1fr !important;
					gap: 8px !important;
				}
				.shorttv-ep-grid-main > div:nth-child(1) {
					grid-column: span 1 !important;
				}
				.shorttv-ep-grid-main > div:nth-child(2) {
					grid-column: span 1 !important;
				}
				.shorttv-ep-grid-main > div:nth-child(3) {
					grid-column: span 2 !important;
				}
				.shorttv-grid-split-genres,
				.shorttv-grid-split-meta {
					grid-template-columns: 1fr !important;
					gap: 10px !important;
				}

				/* Mobile Storage Toolbar Responsiveness */
				.shorttv-storage-toolbar-card {
					padding: 10px 12px !important;
				}
				.shorttv-storage-toolbar-line-1 {
					flex-direction: column !important;
					align-items: stretch !important;
					gap: 10px !important;
				}
				.shorttv-storage-provider-row {
					flex-direction: column !important;
					align-items: stretch !important;
					gap: 8px !important;
				}
				.shorttv-provider-segmented-switch {
					width: 100% !important;
					display: flex !important;
					box-sizing: border-box !important;
				}
				.shorttv-provider-segmented-switch button {
					flex: 1 !important;
					justify-content: center !important;
					padding: 6px 4px !important;
					font-size: 11px !important;
				}
				.shorttv-uploader-bulk-btn-wrapper {
					width: 100% !important;
					display: flex !important;
					flex-wrap: wrap !important;
					gap: 8px !important;
				}
				.shorttv-uploader-bulk-btn-wrapper button {
					flex: 1 !important;
					justify-content: center !important;
					min-width: 140px !important;
				}
				.shorttv-uploader-section {
					flex-direction: column !important;
					align-items: stretch !important;
					gap: 10px !important;
				}
				.shorttv-uploader-section > div {
					width: 100% !important;
					flex-wrap: wrap !important;
				}
				.shorttv-folder-picker-wrap {
					flex: 1 !important;
					width: 100% !important;
				}
				.shorttv-folder-picker-btn {
					width: 100% !important;
				}
				.shorttv-subfolder-input-field {
					flex: 1 !important;
					width: 100% !important;
					max-width: 100% !important;
				}
				.shorttv-path-preview-tag {
					word-break: break-all !important;
					white-space: normal !important;
					display: block !important;
					width: 100% !important;
					box-sizing: border-box !important;
				}
			}

			/* Modern Collapsible Folder Tree Menu UI */
			.shorttv-folder-picker-wrap {
				position: relative;
				display: inline-block;
			}
			.shorttv-folder-picker-btn {
				height: 32px !important;
				line-height: 30px !important;
				padding: 0 10px !important;
				background: #ffffff !important;
				border: 1px solid #c4b5fd !important;
				border-radius: 6px !important;
				font-size: 12px !important;
				font-weight: 700 !important;
				color: #4c1d95 !important;
				display: inline-flex !important;
				align-items: center !important;
				gap: 6px !important;
				cursor: pointer !important;
				transition: all 0.15s ease !important;
				min-width: 140px;
				justify-content: space-between;
			}
			.shorttv-folder-picker-btn:hover {
				background: #f5f3ff !important;
				border-color: #a78bfa !important;
			}
			.shorttv-folder-picker-btn.open {
				border-color: #7c3aed !important;
				box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.15) !important;
			}
			.shorttv-folder-picker-btn.cloudinary-btn {
				border-color: #7dd3fc !important;
				color: #0369a1 !important;
			}
			.shorttv-folder-picker-btn.cloudinary-btn:hover {
				background: #f0f9ff !important;
				border-color: #38bdf8 !important;
			}
			.shorttv-folder-picker-btn.cloudinary-btn.open {
				border-color: #0284c7 !important;
				box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15) !important;
			}
			.shorttv-folder-picker-btn.r2-btn {
				border-color: #fdba74 !important;
				color: #9a3412 !important;
			}
			.shorttv-folder-picker-btn.r2-btn:hover {
				background: #fff7ed !important;
				border-color: #fb923c !important;
			}
			.shorttv-folder-picker-btn.r2-btn.open {
				border-color: #ea580c !important;
				box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.15) !important;
			}
			#shorttv-r2-folder-menu .shorttv-folder-item-row.selected {
				background: #ffedd5 !important;
				color: #9a3412 !important;
				font-weight: 700;
			}
			.shorttv-folder-dropdown-menu {
				position: absolute;
				top: calc(100% + 4px);
				left: 0;
				z-index: 99999;
				min-width: 250px;
				max-width: 320px;
				background: #ffffff;
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 4px 6px -2px rgba(15, 23, 42, 0.05);
				padding: 6px 0;
				display: none;
				max-height: 280px;
				overflow-y: auto;
			}
			.shorttv-folder-item-row {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 6px 12px;
				font-size: 12px;
				font-weight: 600;
				color: #1e293b;
				cursor: pointer;
				transition: background 0.12s ease;
				user-select: none;
			}
			.shorttv-folder-item-row:hover {
				background: #f1f5f9;
				color: #0f172a;
			}
			.shorttv-folder-item-row.selected {
				background: #ede9fe;
				color: #6d28d9;
				font-weight: 700;
			}
			.shorttv-provider-cloudinary .shorttv-folder-item-row.selected {
				background: #e0f2fe;
				color: #0369a1;
			}
			.shorttv-folder-item-row .folder-toggle-arrow {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 20px;
				height: 20px;
				border-radius: 4px;
				font-size: 11px;
				color: #64748b;
				transition: transform 0.15s ease, background 0.12s ease;
				margin-right: 4px;
			}
			.shorttv-folder-item-row .folder-toggle-arrow:hover {
				background: rgba(0,0,0,0.06);
				color: #0f172a;
			}
			.shorttv-folder-item-row .folder-label {
				flex: 1;
				display: inline-flex;
				align-items: center;
				gap: 6px;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
			.shorttv-folder-sub-tree {
				display: none;
				padding-left: 14px;
				background: #fafafa;
				border-left: 2px solid #e2e8f0;
				margin-left: 16px;
			}
			.shorttv-folder-sub-tree.expanded {
				display: block;
			}
			.shorttv-folder-sub-item {
				display: flex;
				align-items: center;
				gap: 6px;
				padding: 5px 12px;
				font-size: 11.5px;
				font-weight: 500;
				color: #475569;
				cursor: pointer;
				transition: background 0.12s ease;
				border-radius: 4px;
				margin: 1px 4px;
			}
			.shorttv-folder-sub-item:hover {
				background: #e2e8f0;
				color: #0f172a;
			}
			.shorttv-folder-sub-item.selected {
				background: #ddd6fe;
				color: #5b21b6;
				font-weight: 700;
			}
			.shorttv-provider-cloudinary .shorttv-folder-sub-item.selected {
				background: #bae6fd;
				color: #0369a1;
			}

			/* ═══════════════════════════════════════════════════════════════════
			   CUSTOM ACCESS RULE DROPDOWN WITH DESCRIPTIVE NOTES
			   ═══════════════════════════════════════════════════════════════════ */
			.shorttv-custom-select-wrap {
				position: relative;
				width: 100%;
			}
			.shorttv-custom-select-wrap select.field-ep-unlock-type,
			.shorttv-custom-select-wrap .shorttv-hidden-native-select {
				display: none !important;
				visibility: hidden !important;
				position: absolute !important;
				opacity: 0 !important;
				pointer-events: none !important;
				width: 0 !important;
				height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				left: -9999px !important;
			}
			.shorttv-custom-select-trigger {
				width: 100%;
				height: 38px;
				padding: 0 10px;
				background: #ffffff !important;
				border: 1.5px solid #cbd5e1 !important;
				border-radius: 8px !important;
				color: #0f172a !important;
				font-size: 13px !important;
				font-weight: 600 !important;
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				cursor: pointer !important;
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
				transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease !important;
				text-align: left;
				box-sizing: border-box !important;
			}
			.shorttv-custom-select-trigger:hover {
				border-color: #94a3b8 !important;
				background: #f8fafc !important;
			}
			.shorttv-custom-select-trigger.is-open {
				border-color: #6366f1 !important;
				box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
				background: #ffffff !important;
			}
			.shorttv-custom-select-trigger .trigger-label {
				display: inline-flex;
				align-items: center;
				gap: 6px;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}
			.shorttv-custom-select-trigger .trigger-chevron {
				color: #64748b;
				transition: transform 0.2s ease;
				flex-shrink: 0;
			}
			.shorttv-custom-select-trigger.is-open .trigger-chevron {
				transform: rotate(180deg);
				color: #6366f1;
			}
			.shorttv-custom-select-menu {
				display: none;
				position: absolute;
				top: calc(100% + 4px);
				right: 0;
				min-width: 270px;
				max-width: 320px;
				background: #ffffff;
				border: 1.5px solid #e2e8f0;
				border-radius: 12px;
				box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.18), 0 4px 10px -2px rgba(15, 23, 42, 0.08);
				padding: 6px;
				z-index: 999999;
				box-sizing: border-box;
			}
			.shorttv-custom-select-menu.is-open {
				display: block;
				animation: shorttvSelectFadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1);
			}
			@keyframes shorttvSelectFadeIn {
				from { opacity: 0; transform: translateY(-4px); }
				to { opacity: 1; transform: translateY(0); }
			}
			.shorttv-custom-select-option {
				display: flex;
				align-items: flex-start;
				gap: 10px;
				padding: 8px 10px;
				border-radius: 8px;
				cursor: pointer;
				transition: background 0.12s ease, border-color 0.12s ease;
				border: 1px solid transparent;
				user-select: none;
				margin-bottom: 3px;
			}
			.shorttv-custom-select-option:last-child {
				margin-bottom: 0;
			}
			.shorttv-custom-select-option:hover {
				background: #f1f5f9;
			}
			.shorttv-custom-select-option.is-selected {
				background: #eff6ff;
				border-color: #bfdbfe;
			}
			.shorttv-custom-select-option .opt-icon {
				font-size: 17px;
				line-height: 1;
				margin-top: 2px;
				flex-shrink: 0;
			}
			.shorttv-custom-select-option .opt-info {
				flex: 1;
				min-width: 0;
			}
			.shorttv-custom-select-option .opt-title {
				font-size: 13px;
				font-weight: 700;
				color: #0f172a;
				line-height: 1.2;
				margin-bottom: 3px;
			}
			.shorttv-custom-select-option.is-selected .opt-title {
				color: #1d4ed8;
			}
			.shorttv-custom-select-option .opt-note {
				font-size: 11px;
				color: #64748b;
				line-height: 1.35;
				font-weight: 400;
			}

			/* Modern Bulk Rule Selector */
			.shorttv-bulk-rule-control {
				display: inline-flex;
				align-items: center;
				gap: 4px;
				background: #f8fafc;
				padding: 3px 4px 3px 10px;
				border-radius: 9px;
				border: 1.5px solid #cbd5e1;
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
				position: relative;
			}
			.shorttv-bulk-rule-control .shorttv-bulk-rule-label {
				font-size: 11.5px;
				font-weight: 800;
				color: #475569;
				text-transform: uppercase;
				letter-spacing: 0.5px;
				margin-right: 4px;
				white-space: nowrap;
			}
			.shorttv-bulk-rule-control .shorttv-custom-select-trigger {
				height: 32px;
				min-width: 220px;
				padding: 0 10px;
				font-size: 12px;
				border-radius: 6px !important;
				background: #ffffff !important;
				border: 1px solid #cbd5e1 !important;
			}
			.shorttv-bulk-rule-control #btn-apply-bulk-rule-all {
				height: 32px;
				line-height: 30px;
				font-size: 11.5px;
				font-weight: 800;
				padding: 0 12px;
				border-radius: 6px;
				background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
				color: #ffffff;
				border: 1px solid #0284c7;
				cursor: pointer;
				display: inline-flex;
				align-items: center;
				gap: 4px;
				box-shadow: 0 1px 3px rgba(2, 132, 199, 0.25);
				transition: all 0.15s ease;
				white-space: nowrap;
			}
			.shorttv-bulk-rule-control #btn-apply-bulk-rule-all:hover {
				background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
				border-color: #0369a1;
				transform: translateY(-1px);
				box-shadow: 0 3px 8px rgba(2, 132, 199, 0.35);
			}
			.shorttv-bulk-rule-control #btn-apply-bulk-rule-all:active {
				transform: translateY(0);
			}

			/* Hide default unstyled WordPress Publish box */
			#submitdiv,
			.postbox#submitdiv {
				display: none !important;
			}
			#poststuff #post-body.columns-2 {
				margin-right: 0 !important;
			}
			#postbox-container-1 {
				display: none !important;
			}
			#post-body-content {
				margin-bottom: 0 !important;
			}
		</style>
		<?php 
		$saved_gumlet_folder_id = get_post_meta( $post->ID, '_shorttv_gumlet_folder_id', true );
		if ( empty( $saved_gumlet_folder_id ) ) {
			$saved_gumlet_folder_id = get_user_meta( get_current_user_id(), 'short_last_used_gumlet_folder_id', true ) ?: get_option( 'short_last_used_gumlet_folder_id', '' );
		}
		$saved_gumlet_folder_name = get_post_meta( $post->ID, '_shorttv_gumlet_folder_name', true );
		if ( empty( $saved_gumlet_folder_name ) && ! empty( $saved_gumlet_folder_id ) ) {
			$map = get_option( 'short_gumlet_cached_folders_map', array() );
			if ( is_array( $map ) && ! empty( $map[ $saved_gumlet_folder_id ] ) ) {
				$saved_gumlet_folder_name = $map[ $saved_gumlet_folder_id ];
			}
		}
		$saved_gumlet_auto_subfolder = get_post_meta( $post->ID, '_shorttv_gumlet_auto_subfolder', true );
		if ( '' === $saved_gumlet_auto_subfolder ) {
			$saved_gumlet_auto_subfolder = '1';
		}
		$saved_gumlet_subfolder = get_post_meta( $post->ID, '_shorttv_gumlet_subfolder', true );
		$saved_r2_folder = get_post_meta( $post->ID, '_shorttv_r2_folder', true );
		if ( empty( $saved_r2_folder ) ) {
			$saved_r2_folder = get_user_meta( get_current_user_id(), 'short_last_used_r2_folder', true ) ?: get_option( 'short_r2_folder', 'short' );
		}
		$saved_r2_subfolder = get_post_meta( $post->ID, '_shorttv_r2_subfolder', true );
		$saved_r2_auto_subfolder = get_post_meta( $post->ID, '_shorttv_r2_auto_subfolder', true );
		if ( '' === $saved_r2_auto_subfolder ) {
			$saved_r2_auto_subfolder = '1';
		}
		$saved_active_provider = get_post_meta( $post->ID, '_shorttv_active_provider', true );
		if ( empty( $saved_active_provider ) ) {
			if ( get_option( 'short_r2_enabled', '0' ) === '1' ) {
				$saved_active_provider = 'r2';
			} elseif ( get_option( 'short_gumlet_enabled', '0' ) === '1' ) {
				$saved_active_provider = 'gumlet';
			} else {
				$saved_active_provider = 'cloudinary';
			}
		}
		$is_gumlet_active = ( $saved_active_provider === 'gumlet' );
		$is_r2_active     = ( $saved_active_provider === 'r2' );
		$is_cloud_active  = ( $saved_active_provider === 'cloudinary' );
		$saved_cloudinary_folder = get_post_meta( $post->ID, '_shorttv_cloudinary_folder', true );
		if ( empty( $saved_cloudinary_folder ) ) {
			$saved_cloudinary_folder = get_user_meta( get_current_user_id(), 'short_last_used_cloudinary_folder', true ) ?: get_option( 'shorttv_cloudinary_folder', 'short' );
		}
		$clean_cloudinary_base = $saved_cloudinary_folder;
		$saved_cloudinary_subfolder = get_post_meta( $post->ID, '_shorttv_cloudinary_subfolder', true );
		if ( strpos( $clean_cloudinary_base, '/' ) !== false ) {
			$path_parts = explode( '/', $clean_cloudinary_base );
			$clean_cloudinary_base = trim( $path_parts[0] );
			if ( empty( $saved_cloudinary_subfolder ) && ! empty( $path_parts[1] ) && stripos( $path_parts[1], 'Auto_Draft' ) === false && stripos( $path_parts[1], 'Auto Draft' ) === false ) {
				$saved_cloudinary_subfolder = trim( $path_parts[1] );
			}
		}
		$saved_cloudinary_auto_subfolder = get_post_meta( $post->ID, '_shorttv_cloudinary_auto_subfolder', true );
		if ( '' === $saved_cloudinary_auto_subfolder ) {
			$saved_cloudinary_auto_subfolder = '1';
		}
		$post_status = $post->post_status;
		$is_published = ( $post_status === 'publish' );
		$watch_url = home_url( '/watch/' . $post->ID . '/' );

		// Query real dramas created on this site to populate real subfolders dynamically
		$existing_drama_posts = get_posts( array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => array( 'publish', 'draft' ),
			'posts_per_page' => 50,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		$real_user_dramas = array();
		foreach ( $existing_drama_posts as $edp ) {
			$t = trim( $edp->post_title );
			if ( ! empty( $t ) && stripos( $t, 'Auto Draft' ) === false && stripos( $t, 'Auto_Draft' ) === false ) {
				$real_user_dramas[] = $t;
			}
		}
		$real_user_dramas = array_values( array_unique( $real_user_dramas ) );
		$wrap_provider_class = 'shorttv-provider-' . ( $is_r2_active ? 'r2' : ( $is_gumlet_active ? 'gumlet' : 'cloudinary' ) );
		?>

		<div class="shorttv-studio-wrap <?php echo esc_attr( $wrap_provider_class ); ?>" data-real-dramas="<?php echo esc_attr( wp_json_encode( $real_user_dramas ) ); ?>">
			<input type="hidden" id="shorttv-active-provider" name="shorttv_active_provider" value="<?php echo esc_attr( $saved_active_provider ); ?>" />
			<input type="hidden" id="shorttv-saved-gumlet-folder-id" value="<?php echo esc_attr( $saved_gumlet_folder_id ); ?>" />

			<!-- Modern ShortTV Studio Action & Command Bar (Replaces default Publish Box) -->
			<div class="shorttv-studio-header-bar" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border-radius: 14px; padding: 18px 24px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; box-shadow: 0 10px 25px -5px rgba(15,23,42,0.25); border: 1px solid #334155;">
				<div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<span style="font-size: 26px;">🎬</span>
						<div>
							<h2 style="margin: 0; font-size: 17px; font-weight: 800; color: #ffffff; letter-spacing: -0.3px;">ShortTV Drama Studio</h2>
							<span style="font-size: 11.5px; color: #94a3b8; font-weight: 600;">ReelShort-Style Vertical Video Engine</span>
						</div>
					</div>

					<!-- Status Badge -->
					<div style="display: flex; align-items: center; gap: 8px; margin-left: 6px;">
						<?php if ( $is_published ) : ?>
							<span style="background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.4); font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px;">
								<span style="width: 8px; height: 8px; background: #4ade80; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #4ade80;"></span> Published Live
							</span>
						<?php else : ?>
							<span style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.4); font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px;">
								<span style="width: 8px; height: 8px; background: #fbbf24; border-radius: 50%; display: inline-block;"></span> Draft Mode
							</span>
						<?php endif; ?>

						<span style="background: rgba(255,255,255,0.08); color: #cbd5e1; font-size: 12px; font-weight: 700; padding: 5px 10px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.12);">
							ID #<?php echo $post->ID; ?>
						</span>
					</div>

					<?php if ( $post->ID ) : ?>
						<a href="<?php echo esc_url( $watch_url ); ?>" target="_blank" style="background: rgba(225, 29, 72, 0.2); color: #fda4af; border: 1px solid rgba(225, 29, 72, 0.5); text-decoration: none; font-size: 12px; font-weight: 800; padding: 6px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" title="Preview this Drama in the 9:16 ReelShort Player">
							<span>▶</span> View Live Watch Page ↗
						</a>
					<?php endif; ?>
				</div>

				<!-- Action Buttons -->
				<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
					<?php if ( current_user_can( 'delete_post', $post->ID ) && $post->post_status !== 'auto-draft' ) : ?>
						<a href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>" onclick="return confirm('Are you sure you want to move this drama to Trash?');" style="color: #f87171; text-decoration: none; font-size: 12px; font-weight: 700; padding: 9px 14px; border-radius: 8px; border: 1px solid rgba(248, 113, 113, 0.35); background: rgba(248, 113, 113, 0.1); transition: all 0.2s;" title="Move to Trash">
							🗑️ Trash
						</a>
					<?php endif; ?>

					<button type="button" id="btn-studio-save-draft" style="background: #334155; color: #f8fafc; border: 1px solid #475569; font-weight: 800; font-size: 13px; padding: 9px 18px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;" title="Save drama details as draft">
						💾 Save Draft
					</button>

					<button type="button" id="btn-studio-publish" style="background: linear-gradient(135deg, #e11d48 0%, #be123c 100%); color: #ffffff; border: 1px solid #fb7185; font-weight: 800; font-size: 13.5px; padding: 9px 24px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; box-shadow: 0 4px 14px rgba(225,29,72,0.45); transition: all 0.2s;" title="<?php echo $is_published ? 'Save and update live drama' : 'Publish this drama live to website'; ?>">
						<?php echo $is_published ? '🔄 Update Drama' : '🚀 Publish Drama'; ?>
					</button>
				</div>
			</div>

			<!-- Studio Workspace Layout Container -->
			<div class="shorttv-studio-container" style="display: flex; flex-direction: column; gap: 16px;">
				
				<!-- ═══════════════════════════════════════════════════════ -->
				<!-- ROW 1: Storage & CDN Toolbar (2-Line Organized Layout)   -->
				<!-- ═══════════════════════════════════════════════════════ -->
				<div class="shorttv-storage-toolbar-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 12px 18px; margin: 0; display: flex; flex-direction: column; gap: 10px;">
					
					<!-- Line 1: Provider Switcher & Quick Actions -->
					<div class="shorttv-storage-toolbar-line-1" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;">
						<div class="shorttv-storage-provider-row" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
							<!-- Title & Provider Switch -->
							<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
								<div style="width: 26px; height: 26px; border-radius: 6px; background: #f5f3ff; color: #7c3aed; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
								</div>
								<strong style="font-size: 13px; font-weight: 800; color: #0f172a; white-space: nowrap;">Storage Provider:</strong>
								<div class="shorttv-provider-segmented-switch" id="shorttv-uploader-tabs" style="display: inline-flex; align-items: center; background: #f1f5f9; padding: 2px; border-radius: 7px; border: 1px solid #e2e8f0; gap: 2px; flex-wrap: wrap;">
									<button type="button" class="shorttv-tab-btn <?php echo $is_r2_active ? 'active' : ''; ?>" data-tab="tab-r2" style="padding: 4px 12px; font-weight: 700; font-size: 11.5px; border-radius: 5px; cursor: pointer; border: none; background: <?php echo $is_r2_active ? '#ea580c' : 'transparent'; ?>; color: <?php echo $is_r2_active ? '#fff' : '#64748b'; ?>; display: inline-flex; align-items: center; gap: 5px; box-shadow: <?php echo $is_r2_active ? '0 1px 3px rgba(234,88,12,0.3)' : 'none'; ?>; transition: all 0.15s;">
										<span style="font-size:12px;">🟠</span>
										<span>Cloudflare R2</span>
									</button>
									<button type="button" class="shorttv-tab-btn <?php echo $is_gumlet_active ? 'active' : ''; ?>" data-tab="tab-gumlet" style="padding: 4px 12px; font-weight: 700; font-size: 11.5px; border-radius: 5px; cursor: pointer; border: none; background: <?php echo $is_gumlet_active ? '#7c3aed' : 'transparent'; ?>; color: <?php echo $is_gumlet_active ? '#fff' : '#64748b'; ?>; display: inline-flex; align-items: center; gap: 5px; box-shadow: <?php echo $is_gumlet_active ? '0 1px 3px rgba(124,58,237,0.3)' : 'none'; ?>; transition: all 0.15s;">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="2" y1="7" x2="7" y2="7"></line><line x1="2" y1="17" x2="7" y2="17"></line><line x1="17" y1="17" x2="22" y2="17"></line><line x1="17" y1="7" x2="22" y2="7"></line></svg>
										<span>Gumlet Video</span>
									</button>
									<button type="button" class="shorttv-tab-btn <?php echo $is_cloud_active ? 'active' : ''; ?>" data-tab="tab-cloudinary" style="padding: 4px 12px; font-weight: 700; font-size: 11.5px; border-radius: 5px; cursor: pointer; border: none; background: <?php echo $is_cloud_active ? '#0284c7' : 'transparent'; ?>; color: <?php echo $is_cloud_active ? '#fff' : '#64748b'; ?>; display: inline-flex; align-items: center; gap: 5px; box-shadow: <?php echo $is_cloud_active ? '0 1px 3px rgba(2,132,199,0.3)' : 'none'; ?>; transition: all 0.15s;">
										<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"></path></svg>
										<span>Cloudinary</span>
									</button>
								</div>
							</div>

							<!-- Bulk Upload Button & Auto-Sort -->
							<div class="shorttv-uploader-bulk-btn-wrapper" style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
								<button type="button" class="button shorttv-btn-r2 btn-r2-upload-bulk" style="<?php echo $is_r2_active ? 'display:inline-flex;' : 'display:none;'; ?> align-items: center; gap: 6px; padding: 0 14px; height: 32px !important; line-height: 30px !important; font-size: 12px; font-weight: 700; background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%) !important; border: 1px solid #c2410c !important; color: #fff !important; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 4px rgba(234,88,12,0.25);">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
									<span>Bulk Upload Videos (R2)</span>
								</button>
								<button type="button" class="button shorttv-btn-gumlet btn-gumlet-upload-bulk" style="<?php echo $is_gumlet_active ? 'display:inline-flex;' : 'display:none;'; ?> align-items: center; gap: 6px; padding: 0 14px; height: 32px !important; line-height: 30px !important; font-size: 12px; font-weight: 700; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important; border: 1px solid #6d28d9 !important; color: #fff !important; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 4px rgba(124,58,237,0.25);">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
									<span>Bulk Upload Videos</span>
								</button>
								<button type="button" class="button shorttv-btn-success btn-cloudinary-upload-bulk" style="<?php echo $is_cloud_active ? 'display:inline-flex;' : 'display:none;'; ?> align-items: center; gap: 6px; padding: 0 14px; height: 32px !important; line-height: 30px !important; font-size: 12px; font-weight: 700; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; border: 1px solid #0369a1 !important; color: #fff !important; border-radius: 6px; cursor: pointer; box-shadow: 0 1px 4px rgba(2,132,199,0.25);">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
									<span>Bulk Upload Videos</span>
								</button>
								<button type="button" class="button button-secondary btn-auto-sort-episodes" title="Automatically sort all episodes by Episode # in ascending order" style="display:inline-flex; align-items:center; gap:4px; height:32px !important; line-height:30px !important; font-size:12px; font-weight:700; border-radius:6px;">
									<span>🔢 Auto-Sort by Ep #</span>
								</button>
							</div>
						</div>

						<!-- Settings Link -->
						<div>
							<a href="<?php echo admin_url( 'admin.php?page=short-video-storage' ); ?>" target="_blank" style="color: #64748b; text-decoration: none; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; height: 30px; box-sizing: border-box; transition: all 0.15s;" title="Storage Settings">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
								<span>Storage Settings</span>
							</a>
						</div>
					</div>

					<!-- Line 2: Folder Selection, Actions & Target Destination -->
					<div>
						<!-- Cloudflare R2 Folder Controls -->
						<div class="shorttv-uploader-section" id="section-tab-r2" style="<?php echo $is_r2_active ? 'display:flex;' : 'display:none;'; ?> align-items: center; justify-content: flex-start; flex-wrap: wrap; gap: 12px; overflow: visible;">
							<div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; position: relative;">
								<label style="font-size: 12px; font-weight: 700; color: #c2410c; display: inline-flex; align-items: center; gap: 4px;">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
									<span>Folder:</span>
								</label>

								<!-- Collapsible Tree Dropdown Picker for Cloudflare R2 -->
								<div class="shorttv-folder-picker-wrap" id="shorttv-r2-folder-picker-wrap">
									<button type="button" class="shorttv-folder-picker-btn r2-btn" id="shorttv-r2-folder-picker-btn" style="border-color: #fdba74; color: #9a3412;">
										<span class="btn-selected-text">📁 <?php echo esc_html( $saved_r2_folder ?: 'short' ); ?></span>
										<span class="btn-arrow">⏷</span>
									</button>
									<div class="shorttv-folder-dropdown-menu" id="shorttv-r2-folder-menu">
										<div class="shorttv-folder-item-row <?php echo ( empty( $saved_r2_folder ) || 'short' === $saved_r2_folder ) ? 'selected' : ''; ?>" data-id="short" data-name="short">
											<div class="folder-label"><span>📁 short (Bucket)</span></div>
										</div>
										<div class="shorttv-folder-item-row <?php echo ( 'movies' === $saved_r2_folder ) ? 'selected' : ''; ?>" data-id="movies" data-name="movies">
											<div class="folder-label"><span>📁 movies (Bucket)</span></div>
										</div>
										<div class="shorttv-folder-item-row <?php echo ( 'custom' === $saved_r2_folder ) ? 'selected' : ''; ?>" data-id="custom">
											<div class="folder-label"><span>✏️ Type Custom...</span></div>
										</div>
									</div>
									<input type="hidden" id="shorttv-r2-dynamic-folder-select" name="shorttv_r2_folder" value="<?php echo esc_attr( $saved_r2_folder ?: 'short' ); ?>" />
								</div>

								<input type="text" id="shorttv-r2-dynamic-folder-custom" placeholder="Type folder name..." style="display: none; width: 140px; height: 32px !important; line-height: 30px !important; border-radius: 6px; border: 1px solid #fdba74; padding: 0 8px !important; font-size: 12px; font-weight: 600; color: #9a3412; background: #fff;" />
								<button type="button" class="button" id="btn-refresh-r2-folders" title="Refresh R2 Folders" style="padding: 0 10px; height: 32px !important; line-height: 30px !important; border-radius: 6px; border: 1px solid #fdba74; background: #fff; color: #c2410c; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
									<svg id="shorttv-icon-refresh-r2-folders" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
									<span>Refresh</span>
								</button>
								<button type="button" class="button" id="btn-create-r2-folder" title="Create New Folder" style="padding: 0 10px; height: 32px !important; line-height: 30px !important; border-radius: 6px; border: 1px solid #ea580c; background: #fff7ed; color: #c2410c; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
									<span>New Folder</span>
								</button>
							</div>

							<div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
								<label style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer; font-weight: 700; color: #c2410c; font-size: 12px; margin: 0; white-space: nowrap;">
									<input type="checkbox" id="shorttv-r2-auto-subfolder" name="shorttv_r2_auto_subfolder" value="1" <?php checked( $saved_r2_auto_subfolder, '1' ); ?> style="border-color: #fdba74; border-radius: 4px; margin: 0;" />
									<span>Auto Subfolder:</span>
								</label>
								<?php 
								$initial_title = get_the_title( $post->ID );
								if ( 'Auto Draft' === $initial_title ) { $initial_title = ''; }
								?>
								<input type="text" id="shorttv-r2-subfolder-input" class="shorttv-subfolder-input-field" name="shorttv_r2_subfolder" value="<?php echo esc_attr( $saved_r2_subfolder ?: $initial_title ); ?>" placeholder="e.g. My Drama Title" style="width: 150px !important; padding: 0 8px !important; border-radius: 6px; border: 1px solid #fdba74; font-size: 12px; font-weight: 600; color: #9a3412; height: 32px !important; line-height: 30px !important; background: #fff;" />
								<code id="shorttv-r2-path-preview" class="shorttv-path-preview-tag" style="background: #ffedd5; color: #9a3412; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700;"><?php echo esc_html( $saved_r2_folder ?: 'short' ); ?> / <?php echo esc_html( $saved_r2_subfolder ?: ( $initial_title ?: 'Drama Title' ) ); ?></code>
							</div>
						</div>

						<!-- Gumlet Folder Controls -->
						<div class="shorttv-uploader-section" id="section-tab-gumlet" style="<?php echo $is_gumlet_active ? 'display:flex;' : 'display:none;'; ?> align-items: center; justify-content: flex-start; flex-wrap: wrap; gap: 12px; overflow: visible;">
							<div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; position: relative;">
								<label style="font-size: 12px; font-weight: 700; color: #5b21b6; display: inline-flex; align-items: center; gap: 4px;">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
									<span>Folder:</span>
								</label>
								
								<!-- Collapsible Tree Dropdown Picker for Gumlet -->
								<div class="shorttv-folder-picker-wrap" id="shorttv-gumlet-folder-picker-wrap">
									<button type="button" class="shorttv-folder-picker-btn" id="shorttv-gumlet-folder-picker-btn">
										<span class="btn-selected-text">📁 <?php echo esc_html( $saved_gumlet_folder_name ?: ( $saved_gumlet_folder_id ? ( 'Folder (' . substr( $saved_gumlet_folder_id, 0, 8 ) . '...)' ) : 'Root / Workspace' ) ); ?></span>
										<span class="btn-arrow">⏷</span>
									</button>
									<div class="shorttv-folder-dropdown-menu" id="shorttv-gumlet-folder-menu">
										<!-- Populated dynamically by JS with expandable arrows -->
									</div>
									<input type="hidden" id="shorttv-gumlet-dynamic-folder-select" name="shorttv_gumlet_folder_id" value="<?php echo esc_attr( $saved_gumlet_folder_id ); ?>" />
									<input type="hidden" id="shorttv-gumlet-dynamic-folder-name" name="shorttv_gumlet_folder_name" value="<?php echo esc_attr( $saved_gumlet_folder_name ); ?>" />
								</div>

								<input type="text" id="shorttv-gumlet-dynamic-folder-custom" placeholder="Type custom folder ID..." style="display: none; width: 140px; height: 32px !important; line-height: 30px !important; border-radius: 6px; border: 1px solid #c4b5fd; padding: 0 8px !important; font-size: 12px; font-weight: 600; color: #4c1d95; background: #fff;" />
								<button type="button" class="button" id="btn-refresh-gumlet-folders" title="Refresh Gumlet Folders" style="padding: 0 10px; height: 32px !important; line-height: 30px !important; border-radius: 6px; border: 1px solid #c4b5fd; background: #fff; color: #5b21b6; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
									<svg id="shorttv-icon-refresh-folders" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
									<span>Refresh</span>
								</button>
								<button type="button" class="button" id="btn-create-gumlet-folder" title="Create New Folder" style="padding: 0 10px; height: 32px !important; line-height: 30px !important; border-radius: 6px; border: 1px solid #7c3aed; background: #f5f3ff; color: #6d28d9; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; cursor: pointer;">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
									<span>New Folder</span>
								</button>
							</div>

							<div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
								<label style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer; font-weight: 700; color: #5b21b6; font-size: 12px; margin: 0; white-space: nowrap;">
									<input type="checkbox" id="shorttv-gumlet-auto-subfolder" name="shorttv_gumlet_auto_subfolder" value="1" <?php checked( $saved_gumlet_auto_subfolder, '1' ); ?> style="border-color: #c4b5fd; border-radius: 4px; margin: 0;" />
									<span>Auto Subfolder:</span>
								</label>
								<input type="text" id="shorttv-gumlet-subfolder-input" class="shorttv-subfolder-input-field" name="shorttv_gumlet_subfolder" value="<?php echo esc_attr( $saved_gumlet_subfolder ?: $initial_title ); ?>" placeholder="e.g. My Drama Title" style="width: 150px !important; padding: 0 8px !important; border-radius: 6px; border: 1px solid #c4b5fd; font-size: 12px; font-weight: 600; color: #4c1d95; height: 32px !important; line-height: 30px !important; background: #fff;" />
								<code id="shorttv-gumlet-path-preview" class="shorttv-path-preview-tag" style="background: #ede9fe; color: #5b21b6; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700;">Short / <?php echo esc_html( $initial_title ?: 'Drama Title' ); ?></code>
							</div>
						</div>

						<!-- Cloudinary Folder Controls -->
						<div class="shorttv-uploader-section" id="section-tab-cloudinary" style="<?php echo $is_cloud_active ? 'display:flex;' : 'display:none;'; ?> align-items: center; justify-content: flex-start; flex-wrap: wrap; gap: 12px; overflow: visible;">
							<div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; position: relative;">
								<label style="font-size: 12px; font-weight: 700; color: #0369a1; display: inline-flex; align-items: center; gap: 4px;">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
									<span>Folder:</span>
								</label>

								<!-- Collapsible Tree Dropdown Picker for Cloudinary -->
								<div class="shorttv-folder-picker-wrap" id="shorttv-cloudinary-folder-picker-wrap">
									<button type="button" class="shorttv-folder-picker-btn cloudinary-btn" id="shorttv-cloudinary-folder-picker-btn">
										<span class="btn-selected-text">📁 <?php echo esc_html( $clean_cloudinary_base ?: 'Movie' ); ?></span>
										<span class="btn-arrow">⏷</span>
									</button>
									<div class="shorttv-folder-dropdown-menu" id="shorttv-cloudinary-folder-menu">
										<!-- Populated dynamically by JS with tree nodes -->
									</div>
									<input type="hidden" id="shorttv-cloudinary-folder-select" name="shorttv_cloudinary_base_folder" value="<?php echo esc_attr( $clean_cloudinary_base ?: 'Movie' ); ?>" />
								</div>

								<input type="text" id="shorttv-cloudinary-custom-folder-input" placeholder="Type folder name..." style="display: none; width: 130px; height: 32px !important; line-height: 30px !important; border-radius: 6px; border: 1px solid #7dd3fc; padding: 0 8px !important; font-size: 12px; font-weight: 600; color: #0369a1; background: #fff;" />
								<input type="hidden" id="shorttv-cloudinary-dynamic-folder" name="shorttv_cloudinary_folder" value="<?php echo esc_attr( $clean_cloudinary_base ?: 'Movie' ); ?>" />
							</div>

							<div style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
								<label style="display: inline-flex; align-items: center; gap: 5px; cursor: pointer; font-weight: 700; color: #0369a1; font-size: 12px; margin: 0; white-space: nowrap;">
									<input type="checkbox" id="shorttv-cloudinary-auto-subfolder" name="shorttv_cloudinary_auto_subfolder" value="1" <?php checked( $saved_cloudinary_auto_subfolder, '1' ); ?> style="border-color: #7dd3fc; border-radius: 4px; margin: 0;" />
									<span>Auto Subfolder:</span>
								</label>
								<input type="text" id="shorttv-cloudinary-subfolder-input" class="shorttv-subfolder-input-field" name="shorttv_cloudinary_subfolder" value="<?php echo esc_attr( $saved_cloudinary_subfolder ?: $initial_title ); ?>" placeholder="e.g. My Drama Title" style="width: 150px !important; padding: 0 8px !important; border-radius: 6px; border: 1px solid #7dd3fc; font-size: 12px; font-weight: 600; color: #0369a1; height: 32px !important; line-height: 30px !important; background: #fff;" />
								<code id="shorttv-cloudinary-path-preview" class="shorttv-path-preview-tag" style="background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-size: 11.5px; font-weight: 700;"><?php echo esc_html( $clean_cloudinary_base ?: 'Movie' ); ?> / <?php echo esc_html( $saved_cloudinary_subfolder ?: ( $initial_title ?: 'Drama Title' ) ); ?></code>
							</div>
						</div>
					</div>
				</div>

				<!-- ═══════════════════════════════════════════════════════ -->
				<!-- ROW 2: Poster (9:16) + Drama Details (Inlined)          -->
				<!-- ═══════════════════════════════════════════════════════ -->
				<div class="shorttv-poster-details-grid" style="display: grid; grid-template-columns: 210px 1fr; gap: 16px; align-items: stretch;">
					
					<!-- Left: Vertical Poster (9:16) with Compact Proportions -->
					<div class="shorttv-card-panel" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 14px; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
						<div>
							<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; padding-bottom: 6px; border-bottom: 1px solid #f1f5f9;">
								<label class="shorttv-label" style="font-size: 12px; font-weight: 800; color: #0f172a; margin: 0; display: inline-flex; align-items: center; gap: 5px;">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
									<span>Poster (9:16)</span>
								</label>
							</div>
							
							<!-- Compact 9:16 Drag and drop zone -->
							<div class="shorttv-poster-dropzone" id="shorttv-poster-dropzone" title="Click or Drag & Drop Image Here" style="aspect-ratio: 9/16; width: 100%; min-height: 230px; max-height: 245px; margin-bottom: 8px; border-radius: 8px; overflow: hidden; border: 2px dashed #cbd5e1; position: relative; background: #f8fafc;">
								<input type="file" id="shorttv-local-file-input" accept="image/png, image/jpeg, image/webp, image/jpg" style="display:none;" />
								<img id="shorttv-poster-img-preview" src="<?php echo esc_url( $poster_url ); ?>" style="width:100%; height:100%; object-fit:cover; display:<?php echo $poster_url ? 'block' : 'none'; ?>;" alt="Poster Preview" />
								<div id="shorttv-poster-empty-label" style="display:<?php echo $poster_url ? 'none' : 'flex'; ?>; flex-direction:column; align-items:center; justify-content:center; color:#94a3b8; font-size:11px; font-weight:600; text-align:center; padding:12px; height: 100%;">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
									<span style="font-weight:700; color:#475569; font-size:11px;">Drop Poster</span>
									<span style="font-size:9.5px; color:#94a3b8;">or click to browse</span>
								</div>
								<div class="shorttv-poster-overlay-badge" id="shorttv-change-poster-badge" style="display:<?php echo $poster_url ? 'block' : 'none'; ?>; font-size:9.5px; padding:2px 6px;">
									Change
								</div>
								<div class="shorttv-upload-progress-box" id="shorttv-poster-upload-progress">
									<strong style="font-size:10px; color:#38bdf8;" id="shorttv-poster-upload-text">Uploading...</strong>
									<div style="width:80%; height:3px; background:#334155; border-radius:2px; overflow:hidden; margin-top:3px;">
										<div id="shorttv-poster-progress-bar" style="width:0%; height:100%; background:#e11d48; transition:width 0.2s;"></div>
									</div>
								</div>
							</div>
						</div>

						<div style="display:flex; flex-direction:column; gap:5px;">
							<input type="text" name="shorttv_vertical_poster" id="shorttv_vertical_poster_input" value="<?php echo esc_attr( $poster_url ); ?>" placeholder="Image URL..." style="font-size:11px; height:28px !important; line-height:26px !important; padding:0 6px !important; border:1px solid #cbd5e1; border-radius:5px; width:100%;" />
							
							<div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px;">
								<button type="button" class="button shorttv-btn-primary" id="btn-browse-computer-poster" style="padding:2px 4px; height:26px; line-height:22px; justify-content:center; display:flex; align-items:center; gap:3px; font-size:11px; font-weight:700; border-radius:5px;" title="Upload from Computer">
									<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
									<span>Upload</span>
								</button>
								<button type="button" class="button" id="btn-wp-media-poster" style="padding:2px 4px; height:26px; line-height:22px; justify-content:center; display:flex; align-items:center; gap:3px; font-size:11px; font-weight:700; border-radius:5px; background:#f8fafc; border:1px solid #cbd5e1;" title="Select from Media Library">
									<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><polyline points="11 3 11 11 14 8 17 11 17 3"></polyline></svg>
									<span>Media</span>
								</button>
							</div>

							<button type="button" class="button" id="btn-clear-poster" style="color:#dc2626; border-color:#fca5a5; font-size:10.5px; font-weight:600; padding:2px 4px; height:24px; line-height:20px; border-radius:5px; display:<?php echo $poster_url ? 'inline-flex' : 'none'; ?>; align-items:center; justify-content:center; gap:3px; text-align:center;">
								<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
								<span>Remove</span>
							</button>
						</div>
					</div>

					<!-- Right: Drama Title, Storyline, Genres & Metadata Details Card -->
					<div class="shorttv-card-panel" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 18px 20px; margin: 0; display: flex; flex-direction: column; justify-content: space-between;">
						<input type="hidden" name="shorttv_media_id" value="<?php echo esc_attr( $schema['media_id'] ?? 'short_' . $post->ID ); ?>" />

						<div style="display: flex; flex-direction: column; gap: 12px;">
							<div>
								<label class="shorttv-label" style="font-size: 13.5px; font-weight: 800; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
									<span>🎬 Drama Title</span>
									<span style="color: #e11d48;">*</span>
								</label>
								<input type="text" id="shorttv_custom_title" name="shorttv_custom_title" value="<?php echo esc_attr( $post->post_title ); ?>" placeholder="e.g. A Marriage Deal with the Billionaire" style="font-size: 15px; font-weight: 700; padding: 6px 12px; height: 38px !important; border: 1.5px solid #cbd5e1; border-radius: 8px; width: 100%; color: #0f172a;" required />
							</div>

							<div>
								<label class="shorttv-label" style="font-size: 12px; margin-bottom: 4px;">📖 Storyline Synopsis / Overview</label>
								<textarea name="shorttv_overview" id="shorttv_overview_input" style="height: 110px !important; line-height: 1.45; font-size: 12.5px; padding: 8px 10px;" placeholder="Write an exciting storyline or hook..."><?php echo esc_textarea( $schema['overview'] ?? '' ); ?></textarea>
							</div>

							<div class="shorttv-grid-split-genres" style="gap: 12px;">
								<div>
									<label class="shorttv-label" style="font-size: 12px; margin-bottom: 4px;">🏷️ Genres (comma separated)</label>
									<input type="text" name="shorttv_genres_str" value="<?php echo esc_attr( implode( ', ', $schema['genres'] ?? array() ) ); ?>" placeholder="Billionaire, Romance, Revenge, Drama" style="height: 34px !important; font-size: 12px;" />
								</div>
								<div>
									<label class="shorttv-label" style="font-size: 12px; margin-bottom: 4px;">🌐 Language</label>
									<input type="text" name="shorttv_language" value="<?php echo esc_attr( $schema['language'] ?? 'English' ); ?>" placeholder="English, Mandarin, etc." style="height: 34px !important; font-size: 12px;" />
								</div>
							</div>

							<div class="shorttv-grid-split-meta" style="gap: 12px;">
								<div>
									<label class="shorttv-label" style="color: #475569; font-size: 11.5px; margin-bottom: 4px;">Release Year</label>
									<input type="number" name="shorttv_release_year" value="<?php echo esc_attr( $schema['release_year'] ?? date('Y') ); ?>" style="height: 34px !important; font-size: 12px;" />
								</div>
								<div>
									<label class="shorttv-label" style="color: #475569; font-size: 11.5px; margin-bottom: 4px;">Total Episodes</label>
									<input type="number" name="shorttv_total_episodes" value="<?php echo esc_attr( $schema['total_episodes'] ?? 60 ); ?>" min="1" style="height: 34px !important; font-size: 12px;" />
								</div>
							</div>
						</div>
					</div>

				</div>

				<!-- ═══════════════════════════════════════════════════════ -->
				<!-- ROW 3: Episodes & Stream Links Configuration Card       -->
				<!-- ═══════════════════════════════════════════════════════ -->
				<div class="shorttv-studio-main" style="display: flex; flex-direction: column; gap: 16px;">
					
					<!-- Episodes & Stream Configuration Card -->
					<div class="shorttv-card-panel" style="padding: 18px 20px; margin: 0;">
						<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; margin-bottom:18px; border-bottom:2px solid #e2e8f0; padding-bottom:14px;">
							<div>
								<div style="display:flex; align-items:center; gap:8px;">
									<span style="font-size:20px;">📱</span>
									<h3 style="margin:0; font-size:16px; font-weight:800; color:#0f172a;">Episodes & Stream Configuration</h3>
								</div>
								<p style="margin:3px 0 0 0; font-size:12px; color:#64748b;">
									Manage episode titles, pricing/unlock rules, and stream playback URLs.
								</p>
							</div>

							<div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
								<!-- Bulk Upload Access Rule Preset -->
								<div class="shorttv-bulk-rule-control">
									<span class="shorttv-bulk-rule-label">Rule:</span>
									<div class="shorttv-custom-select-wrap" style="width: auto; min-width: 220px;">
										<select id="shorttv-bulk-upload-rule" class="shorttv-hidden-native-select">
											<option value="coins_5free" selected>🪙 First 5 Free, next Coins</option>
											<option value="vip_all">👑 All Episodes VIP</option>
											<option value="free_all">🟢 All Episodes Free</option>
											<option value="coins_all">🪙 All Episodes Coins</option>
										</select>
										<button type="button" class="shorttv-custom-select-trigger" aria-haspopup="listbox" aria-expanded="false" style="height: 32px; min-width: 220px; padding: 0 10px; font-size: 12px;">
											<span class="trigger-label"><span>🪙</span> <strong>First 5 Free, next Coins</strong></span>
											<svg class="trigger-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
										</button>
										<div class="shorttv-custom-select-menu" role="listbox" style="min-width: 260px; right: auto; left: 0;">
											<div class="shorttv-custom-select-option is-selected" data-value="coins_5free" role="option">
												<span class="opt-icon">🪙</span>
												<div class="opt-info">
													<div class="opt-title">First 5 Free, next Coins</div>
													<div class="opt-note">Ep 1-5 free to hook viewers, then require coins.</div>
												</div>
											</div>
											<div class="shorttv-custom-select-option" data-value="vip_all" role="option">
												<span class="opt-icon">👑</span>
												<div class="opt-info">
													<div class="opt-title">All Episodes VIP</div>
													<div class="opt-note">All episodes locked for VIP & Premium subscribers only.</div>
												</div>
											</div>
											<div class="shorttv-custom-select-option" data-value="free_all" role="option">
												<span class="opt-icon">🟢</span>
												<div class="opt-info">
													<div class="opt-title">All Episodes Free</div>
													<div class="opt-note">All episodes 100% free and open for everyone.</div>
												</div>
											</div>
											<div class="shorttv-custom-select-option" data-value="coins_all" role="option">
												<span class="opt-icon">🪙</span>
												<div class="opt-info">
													<div class="opt-title">All Episodes Coins</div>
													<div class="opt-note">Every single episode requires coins to unlock.</div>
												</div>
											</div>
										</div>
									</div>
									<button type="button" id="btn-apply-bulk-rule-all" title="Apply this rule to all existing episodes right now">
										⚡ Apply to All
									</button>
								</div>

								<button type="button" class="button" id="btn-renumber-episodes" style="background:#475569; border-color:#334155; color:#fff; font-weight:700; border-radius:8px; padding:7px 14px; height:auto; font-size:12px;" title="Re-sequence all episode numbers 1 to N">
									🔢 Auto-Renumber (1 to N)
								</button>
								<button type="button" class="button" id="btn-convert-all-hls" style="background:#0284c7; border-color:#0369a1; color:#fff; font-weight:700; border-radius:8px; padding:7px 14px; height:auto; font-size:12px;" title="Convert MP4 to HLS (.m3u8)">
									⚡ Convert MP4 to HLS
								</button>
								<button type="button" class="button" id="btn-reset-all-mp4" style="background:#ea580c; border-color:#c2410c; color:#fff; font-weight:700; border-radius:8px; padding:7px 14px; height:auto; font-size:12px;" title="Reset all streams back to direct MP4 URLs (.mp4) for Cloudflare R2 / Direct playback">
									🎬 Use Direct MP4 (.mp4)
								</button>
								<button type="button" class="button shorttv-btn-primary" id="btn-add-shorttv-episode" style="padding:7px 18px; height:auto; font-weight:800; font-size:12.5px; border-radius:8px;">
									+ Add Episode Row
								</button>
							</div>
						</div>

						<div id="shorttv-episodes-wrapper">
							<?php foreach ( $episodes as $idx => $ep ) : 
								$ep_num     = $ep['episode_number'] ?? ( $idx + 1 );
								$ep_title   = $ep['title'] ?? 'Episode ' . $ep_num;
								$duration   = $ep['duration_seconds'] ?? 120;
								$unlock_t   = $ep['access_control']['unlock_type'] ?? 'free';
								$coin_cost  = $ep['access_control']['coin_cost'] ?? 0;
								$ad_allowed = ! empty( $ep['access_control']['ad_unlock_allowed'] );
								$hls_url    = $ep['sources']['hls_stream_url'] ?? '';
								$mp4_url    = $ep['sources']['fallback_mp4'] ?? '';
							?>
								<div class="shorttv-episode-card" data-index="<?php echo $idx; ?>" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">
									<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px; flex-wrap:wrap; gap:8px;">
										<div class="ep-header-left" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
											<span style="background:#e0f2fe; color:#0369a1; font-weight:800; font-size:12px; padding:3px 8px; border-radius:4px;">EP #<span class="ep-num-label"><?php echo esc_html( $ep_num ); ?></span></span>
											<strong style="color:#0f172a; font-size:14px;" class="ep-title-label"><?php echo esc_html( $ep_title ); ?></strong>
											<?php if ( 'free' === $unlock_t ) : ?>
												<span class="ep-access-badge" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">🟢 FREE</span>
											<?php elseif ( 'vip' === $unlock_t ) : ?>
												<span class="ep-access-badge" style="background:#f3e8ff; color:#7e22ce; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">👑 VIP ONLY</span>
											<?php else : ?>
												<span class="ep-access-badge" style="background:#fef3c7; color:#b45309; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">🪙 COINS</span>
											<?php endif; ?>
										</div>
										<button type="button" class="button btn-remove-shorttv-ep" style="color:#dc2626; border-color:#fca5a5;">Remove</button>
									</div>

									<!-- Episode Core Info -->
									<div class="shorttv-ep-grid-main">
										<div>
											<label class="shorttv-field-label">Ep #</label>
											<input type="number" class="field-ep-number" name="shorttv_episodes[<?php echo $idx; ?>][episode_number]" value="<?php echo esc_attr( $ep_num ); ?>" min="1" required />
										</div>
										<div>
											<label class="shorttv-field-label">Episode Title</label>
											<input type="text" class="field-ep-title" name="shorttv_episodes[<?php echo $idx; ?>][title]" value="<?php echo esc_attr( $ep_title ); ?>" required />
										</div>
										<div>
											<label class="shorttv-field-label">Access Rule</label>
											<?php
											$rule_options = array(
												'free'  => array( 'icon' => '🟢', 'label' => 'Free',          'note' => 'Open to all viewers with no cost or restrictions.' ),
												'coins' => array( 'icon' => '🪙', 'label' => 'Coins',         'note' => 'Requires coins to unlock (supports rewarded ad unlock).' ),
												'vip'   => array( 'icon' => '👑', 'label' => 'VIP Pass Only', 'note' => 'Exclusive to active VIP & Premium subscribers.' ),
											);
											$cur_rule = $rule_options[ $unlock_t ] ?? $rule_options['free'];
											?>
											<div class="shorttv-custom-select-wrap">
												<select class="field-ep-unlock-type shorttv-hidden-native-select" name="shorttv_episodes[<?php echo $idx; ?>][access_control][unlock_type]" style="display:none !important; position:absolute !important; opacity:0 !important; pointer-events:none !important; width:0 !important; height:0 !important; margin:0 !important; padding:0 !important; left:-9999px !important;">
													<?php foreach ( $rule_options as $val => $opt ) : ?>
														<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $unlock_t, $val ); ?>><?php echo esc_html( $opt['icon'] . ' ' . $opt['label'] ); ?></option>
													<?php endforeach; ?>
												</select>
												<button type="button" class="shorttv-custom-select-trigger" aria-haspopup="listbox" aria-expanded="false">
													<span class="trigger-label"><span><?php echo esc_html( $cur_rule['icon'] ); ?></span> <strong><?php echo esc_html( $cur_rule['label'] ); ?></strong></span>
													<svg class="trigger-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
												</button>
												<div class="shorttv-custom-select-menu" role="listbox">
													<?php foreach ( $rule_options as $val => $opt ) : 
														$is_sel = ( $val === $unlock_t );
													?>
														<div class="shorttv-custom-select-option <?php echo $is_sel ? 'is-selected' : ''; ?>" data-value="<?php echo esc_attr( $val ); ?>" role="option">
															<span class="opt-icon"><?php echo esc_html( $opt['icon'] ); ?></span>
															<div class="opt-info">
																<div class="opt-title"><?php echo esc_html( $opt['label'] ); ?></div>
																<div class="opt-note"><?php echo esc_html( $opt['note'] ); ?></div>
															</div>
														</div>
													<?php endforeach; ?>
												</div>
											</div>
										</div>
									</div>

									<!-- Dynamic Video Streams (Dedicated to active Uploader Tab) -->
									<div class="shorttv-ep-grid-streams">
										<div>
											<label class="shorttv-field-label">HLS Master Stream URL (.m3u8)</label>
											<div class="shorttv-stream-input-group">
												<input type="text" class="field-hls-url" name="shorttv_episodes[<?php echo $idx; ?>][sources][hls_stream_url]" value="<?php echo esc_attr( $hls_url ); ?>" placeholder="https://.../master.m3u8" />
												<button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="<?php echo $ep_num; ?>" title="Upload Video Directly to Cloudflare R2" style="<?php echo $is_r2_active ? 'display:inline-flex;' : 'display:none;'; ?> background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button>
												<button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="<?php echo $ep_num; ?>" title="Upload Video Directly to Gumlet Video CDN" style="<?php echo $is_gumlet_active ? 'display:inline-flex;' : 'display:none;'; ?> background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button>
												<button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" title="Upload Video to Cloudinary" style="<?php echo $is_cloud_active ? 'display:inline-flex;' : 'display:none;'; ?> width:42px !important;">☁️</button>
											</div>
										</div>
										<div>
											<label class="shorttv-field-label">Fallback MP4 Stream (1080p / 720p)</label>
											<div class="shorttv-stream-input-group">
												<input type="text" class="field-mp4-url" name="shorttv_episodes[<?php echo $idx; ?>][sources][fallback_mp4]" value="<?php echo esc_attr( $mp4_url ); ?>" placeholder="https://.../video_1080p.mp4" />
												<button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="<?php echo $ep_num; ?>" title="Upload Video Directly to Cloudflare R2" style="<?php echo $is_r2_active ? 'display:inline-flex;' : 'display:none;'; ?> background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button>
												<button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="<?php echo $ep_num; ?>" title="Upload Video Directly to Gumlet Video CDN" style="<?php echo $is_gumlet_active ? 'display:inline-flex;' : 'display:none;'; ?> background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button>
												<button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" title="Upload Video to Cloudinary" style="<?php echo $is_cloud_active ? 'display:inline-flex;' : 'display:none;'; ?> width:42px !important;">☁️</button>
											</div>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>

						<!-- Bottom Episode Action Bar -->
						<div style="margin-top:16px; padding:14px 18px; background:#f8fafc; border:2px dashed #cbd5e1; border-radius:10px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
							<div style="font-size:13px; color:#475569; font-weight:600;">
								➕ Need to add more episodes to this drama?
							</div>
							<div style="display:flex; gap:8px;">
								<button type="button" class="button btn-add-shorttv-episode shorttv-btn-primary" style="padding:7px 20px; height:auto; font-weight:800; font-size:13px; border-radius:8px;">
									+ Add Episode Row
								</button>
							</div>
						</div>
					</div>

					<!-- 3. Collapsible JSON Schema & Stats -->
					<details style="background:#0f172a; border-radius:12px; padding:18px; color:#f8fafc; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
						<summary style="font-weight:800; cursor:pointer; color:#38bdf8; font-size:14px; user-select:none; display:flex; justify-content:space-between; align-items:center;">
							<span>⚡ Exact JSON Schema Import & Live Sync</span>
							<span style="font-size:12px; color:#94a3b8; font-weight:normal;">(Click to Expand / Collapse)</span>
						</summary>
						
						<div style="margin-top:16px;">
							<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:10px;">
								<label style="font-weight:700; color:#93c5fd; font-size:12px;">
									<?php _e( 'Paste your JSON schema to instantly populate this entire series:', 'short-stream-core' ); ?>
								</label>
								<div style="display:flex; gap:8px;">
									<button type="button" class="button" id="btn-copy-shorttv-json" style="background:#334155; color:#fff; border:none;">📋 Copy JSON</button>
									<button type="button" class="button button-primary" id="btn-parse-shorttv-json" style="background:#0284c7; border:none;">⚡ Sync Form from JSON</button>
								</div>
							</div>
							<textarea id="shorttv_raw_json_input" name="shorttv_raw_json" style="width:100%; height:180px; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:12px; background:#020617; color:#38bdf8; border:1px solid #334155; border-radius:8px; padding:12px;" placeholder="Paste JSON here..."><?php echo esc_textarea( $json_formatted ); ?></textarea>
						</div>
					</details>

				</div>

			</div>

		</div>

		<!-- Hidden Storage File Inputs (must exist in DOM for .trigger('click') to open file picker) -->
		<input type="file" id="shorttv-r2-single-input" accept="video/*" style="display:none;" />
		<input type="file" id="shorttv-r2-bulk-input" accept="video/*" multiple style="display:none;" />
		<input type="file" id="shorttv-gumlet-single-input" accept="video/*" style="display:none;" />
		<input type="file" id="shorttv-gumlet-bulk-input" accept="video/*" multiple style="display:none;" />

		<!-- Cloudflare R2 Upload Progress Modal -->
		<div id="shorttv-r2-progress-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:99999; align-items:center; justify-content:center;">
			<div style="background:#1e293b; border-radius:12px; padding:28px 32px; min-width:340px; max-width:480px; box-shadow:0 8px 32px rgba(0,0,0,0.5);">
				<p style="color:#ea580c; font-weight:800; font-size:15px; margin:0 0 12px; display:flex; align-items:center; gap:8px;"><span>🟠</span> <span>Uploading to Cloudflare R2…</span></p>
				<p id="shorttv-r2-progress-status" style="color:#cbd5e1; font-size:12px; margin:0 0 14px; word-break:break-all;"></p>
				<div style="width:100%; height:6px; background:#334155; border-radius:4px; overflow:hidden;">
					<div id="shorttv-r2-progress-fill" style="height:100%; width:0%; background:linear-gradient(90deg,#ea580c,#f97316); border-radius:4px; transition:width 0.3s;"></div>
				</div>
				<p id="shorttv-r2-progress-pct" style="color:#94a3b8; font-size:11px; text-align:right; margin:6px 0 0;">0%</p>
			</div>
		</div>

		<!-- Gumlet Bulk Upload Progress Modal -->
		<div id="shorttv-gumlet-progress-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:99999; align-items:center; justify-content:center;">
			<div style="background:#1e293b; border-radius:12px; padding:28px 32px; min-width:340px; max-width:480px; box-shadow:0 8px 32px rgba(0,0,0,0.5);">
				<p style="color:#38bdf8; font-weight:800; font-size:15px; margin:0 0 12px;">🎬 Uploading to Gumlet…</p>
				<p id="shorttv-gumlet-progress-status" style="color:#cbd5e1; font-size:12px; margin:0 0 14px; word-break:break-all;"></p>
				<div style="width:100%; height:6px; background:#334155; border-radius:4px; overflow:hidden;">
					<div id="shorttv-gumlet-progress-fill" style="height:100%; width:0%; background:linear-gradient(90deg,#7c3aed,#38bdf8); border-radius:4px; transition:width 0.3s;"></div>
				</div>
				<p id="shorttv-gumlet-progress-pct" style="color:#94a3b8; font-size:11px; text-align:right; margin:6px 0 0;">0%</p>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Cleanly remove/hide any native WordPress publish box
			$('#submitdiv, #postbox-container-1, .postbox#submitdiv').hide();

			// Apply initial provider buttons visibility
			if (typeof applyProviderPreference === 'function') {
				applyProviderPreference('<?php echo $is_r2_active ? "r2" : ( $is_gumlet_active ? "gumlet" : "cloudinary" ); ?>');
			}

			// Helper: Compute clean Cloudinary target folder based on dynamic input or Drama Title
			function getShortTvTargetFolder() {
				var baseVal = $('#shorttv-cloudinary-folder-select').val();
				if (baseVal === 'custom') {
					baseVal = $.trim($('#shorttv-cloudinary-custom-folder-input').val());
				}
				if (baseVal && baseVal.indexOf('/') !== -1) {
					baseVal = baseVal.split('/')[0].trim();
				}
				if (!baseVal) {
					baseVal = (window.SHORTTV_CLOUDINARY && SHORTTV_CLOUDINARY.folder) ? SHORTTV_CLOUDINARY.folder : 'Movie';
				}

				var isAuto = $('#shorttv-cloudinary-auto-subfolder').length ? $('#shorttv-cloudinary-auto-subfolder').is(':checked') : true;
				var subName = $.trim($('#shorttv-cloudinary-subfolder-input').val()) || $.trim($('#shorttv_custom_title').val() || $('#title').val() || '');
				if (subName.toLowerCase() === 'auto draft') { subName = ''; }
				var cleanSub = subName ? subName.replace(/[\\/:*?"<>|#%&{}\\^~`\[\]+]/g, '').trim().replace(/\s+/g, '_') : '';

				var fullPath = baseVal;
				if (isAuto && cleanSub) {
					fullPath = baseVal ? (baseVal + '/' + cleanSub) : cleanSub;
				}
				return fullPath || 'Movie';
			}

			// Helper: Update live Cloudinary path preview and input value
			function updateCloudinaryPathPreview() {
				var baseVal = $('#shorttv-cloudinary-folder-select').val();
				if (baseVal === 'custom') {
					baseVal = $.trim($('#shorttv-cloudinary-custom-folder-input').val()) || 'custom_folder';
				}
				if (baseVal && baseVal.indexOf('/') !== -1) {
					baseVal = baseVal.split('/')[0].trim();
				}
				if (!baseVal) {
					baseVal = 'Movie';
				}

				var isAuto = $('#shorttv-cloudinary-auto-subfolder').is(':checked');
				var subName = $.trim($('#shorttv-cloudinary-subfolder-input').val()) || $.trim($('#shorttv_custom_title').val() || $('#title').val() || '');
				if (subName.toLowerCase() === 'auto draft') { subName = ''; }

				var previewText = baseVal;
				if (isAuto) {
					previewText = baseVal + ' / ' + (subName || 'Drama Title');
					$('#shorttv-cloudinary-subfolder-input').prop('disabled', false).css('opacity', '1');
				} else {
					$('#shorttv-cloudinary-subfolder-input').prop('disabled', true).css('opacity', '0.5');
				}
				$('#shorttv-cloudinary-path-preview').text(previewText);
				$('#shorttv-cloudinary-dynamic-folder').val(baseVal);
				updateFolderBadge();
			}

			// Helper: Update live folder badge display
			function updateFolderBadge() {
				var folderPath = getShortTvTargetFolder();
				$('#shorttv-folder-badge-display').text(folderPath + '/');
				$('#cloudinary-tab-folder-badge').text(folderPath + '/');
				$('#cloudinary-tab-folder-text').text(folderPath + '/');
			}

			// Storage Provider Tabs Switcher (Cloudflare R2 vs Gumlet vs Cloudinary)
			$('#shorttv-uploader-tabs .shorttv-tab-btn').on('click', function(e) {
				e.preventDefault();
				var tabId = $(this).data('tab');
				
				$('#shorttv-uploader-tabs .shorttv-tab-btn').removeClass('active').css({
					'background': 'transparent',
					'color': '#64748b',
					'box-shadow': 'none'
				});
				
				$(this).addClass('active');
				if (tabId === 'tab-r2') {
					$(this).css({ 'background': '#ea580c', 'color': '#fff', 'box-shadow': '0 1px 3px rgba(234,88,12,0.3)' });
					applyProviderPreference('r2');
				} else if (tabId === 'tab-cloudinary') {
					$(this).css({ 'background': '#0284c7', 'color': '#fff', 'box-shadow': '0 1px 3px rgba(2,132,199,0.3)' });
					applyProviderPreference('cloudinary');
				} else if (tabId === 'tab-gumlet') {
					$(this).css({ 'background': '#7c3aed', 'color': '#fff', 'box-shadow': '0 1px 3px rgba(124,58,237,0.3)' });
					applyProviderPreference('gumlet');
				}
			});

			// Live Poster Preview and UI State
			function updatePosterPreview(url) {
				url = $.trim(url || '');
				if (url && url.indexOf('your-cloud') === -1) {
					var $img = $('#shorttv-poster-img-preview');
					$img.attr('src', url).show();
					$('#shorttv-poster-empty-label').hide();
					$('#shorttv-change-poster-badge').show();
					$('#btn-clear-poster').css('display', 'inline-flex');
				} else {
					$('#shorttv-poster-img-preview').attr('src', '').hide();
					$('#shorttv-poster-empty-label').css('display', 'flex');
					$('#shorttv-change-poster-badge').hide();
					$('#btn-clear-poster').hide();
				}
			}

			$('#shorttv_vertical_poster_input').on('input change keyup paste blur', function() {
				var val = $(this).val();
				setTimeout(function() {
					updatePosterPreview($('#shorttv_vertical_poster_input').val() || val);
				}, 10);
			});

			$('#shorttv-poster-img-preview').on('error', function() {
				if (!$(this).attr('src')) {
					$(this).hide();
					$('#shorttv-poster-empty-label').css('display', 'flex');
				}
			});

			// Initialize Folder Badge on Load
			updateFolderBadge();

			// Clear Poster
			$('#btn-clear-poster').on('click', function(e) {
				e.preventDefault();
				$('#shorttv_vertical_poster_input').val('').trigger('change');
				$('#shorttv-local-file-input').val('');
			});

			// Browse Computer button & Dropzone Click
			$('#btn-browse-computer-poster, #shorttv-poster-dropzone').on('click', function(e) {
				if ($(e.target).closest('#btn-clear-poster').length) return;
				$('#shorttv-local-file-input').trigger('click');
			});

			// Prevent bubbling when clicking on file input
			$('#shorttv-local-file-input').on('click', function(e) {
				e.stopPropagation();
			});

			// File Dropzone Drag & Drop Handlers
			var $dropzone = $('#shorttv-poster-dropzone');

			$dropzone.on('dragover dragenter', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$(this).addClass('dragover');
			});

			$dropzone.on('dragleave dragend drop', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$(this).removeClass('dragover');
			});

			$dropzone.on('drop', function(e) {
				var files = e.originalEvent.dataTransfer.files;
				if (files && files.length > 0) {
					handleLocalImageUpload(files[0]);
				}
			});

			$('#shorttv-local-file-input').on('change', function() {
				if (this.files && this.files.length > 0) {
					handleLocalImageUpload(this.files[0]);
				}
			});

			// 1. Direct Local / WordPress Media Upload (Zero Cloudinary Dependency)
			function handleLocalImageUpload(file) {
				if (!file.type.match('image.*')) {
					alert('Please select an image file (PNG, JPG, JPEG, WEBP).');
					return;
				}

				// Instant local preview via FileReader
				var reader = new FileReader();
				reader.onload = function(e) {
					$('#shorttv-poster-img-preview').attr('src', e.target.result).show();
					$('#shorttv-poster-empty-label').hide();
					$('#shorttv-change-poster-badge').show();
					$('#btn-clear-poster').show();
				};
				reader.readAsDataURL(file);

				var $progressBox = $('#shorttv-poster-upload-progress');
				var $progressBar = $('#shorttv-poster-progress-bar');
				var $progressText = $('#shorttv-poster-upload-text');

				$progressBox.css('display', 'flex');
				$progressBar.css('width', '25%');
				$progressText.text('Uploading to WordPress Media...');

				// Check active provider: if Cloudinary is explicitly configured and selected, upload there; otherwise upload locally to WordPress
				var activeProv = $('#shorttv-active-provider').val();
				var hasCloudinary = (window.SHORTTV_CLOUDINARY && SHORTTV_CLOUDINARY.cloudName && SHORTTV_CLOUDINARY.cloudName !== 'your-cloud');

				if (activeProv === 'cloudinary' && hasCloudinary) {
					var cloudName = SHORTTV_CLOUDINARY.cloudName;
					var uploadPreset = SHORTTV_CLOUDINARY.uploadPreset || 'my_video_preset';
					var targetFolder = getShortTvTargetFolder();

					var formData = new FormData();
					formData.append('file', file);
					formData.append('upload_preset', uploadPreset);
					formData.append('folder', targetFolder);

					var xhr = new XMLHttpRequest();
					xhr.open('POST', 'https://api.cloudinary.com/v1_1/' + cloudName + '/image/upload', true);

					xhr.upload.onprogress = function(e) {
						if (e.lengthComputable) {
							var percent = Math.round((e.loaded / e.total) * 100);
							$progressBar.css('width', percent + '%');
							$progressText.text('Uploading to Cloudinary ' + percent + '%');
						}
					};

					xhr.onload = function() {
						$progressBox.hide();
						if (xhr.status === 200) {
							try {
								var resp = JSON.parse(xhr.responseText);
								if (resp.secure_url) {
									$('#shorttv_vertical_poster_input').val(resp.secure_url).trigger('change');
								}
							} catch(err) {}
						} else {
							// Fallback to local upload
							uploadPosterToWordPress(file);
						}
					};

					xhr.onerror = function() {
						$progressBox.hide();
						uploadPosterToWordPress(file);
					};

					xhr.send(formData);
				} else {
					// Upload to local WordPress Media Library directly
					uploadPosterToWordPress(file);
				}
			}

			function uploadPosterToWordPress(file) {
				var $progressBox = $('#shorttv-poster-upload-progress');
				var $progressBar = $('#shorttv-poster-progress-bar');
				var $progressText = $('#shorttv-poster-upload-text');

				$progressBox.css('display', 'flex');
				$progressBar.css('width', '35%');
				$progressText.text('Saving to WordPress Media Library...');

				var formData = new FormData();
				formData.append('action', 'shorttv_upload_local_poster');
				formData.append('nonce', window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : '');
				formData.append('file', file);

				var xhr = new XMLHttpRequest();
				xhr.open('POST', window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl, true);

				xhr.upload.onprogress = function(e) {
					if (e.lengthComputable) {
						var percent = Math.round(35 + (e.loaded / e.total) * 60);
						$progressBar.css('width', percent + '%');
						$progressText.text('Saving image ' + percent + '%');
					}
				};

				xhr.onload = function() {
					$progressBox.hide();
					if (xhr.status === 200) {
						try {
							var resp = JSON.parse(xhr.responseText);
							if (resp.success && resp.data && resp.data.url) {
								$('#shorttv_vertical_poster_input').val(resp.data.url).trigger('change');
							} else {
								alert('Upload notice: ' + (resp.data ? resp.data.message : 'Unknown error'));
							}
						} catch(err) {
							console.error('Error parsing response:', err);
						}
					} else {
						alert('Server returned HTTP ' + xhr.status + ' during poster upload.');
					}
				};

				xhr.onerror = function() {
					$progressBox.hide();
					alert('Network error during poster upload.');
				};

				xhr.send(formData);
			}

			// 2. Open WordPress Native Media Library Frame
			var wpMediaFrame = null;
			$('#btn-wp-media-poster').on('click', function(e) {
				e.preventDefault();
				if (typeof wp === 'undefined' || !wp.media) {
					alert('WordPress Media Library is not initialized.');
					return;
				}

				if (wpMediaFrame) {
					wpMediaFrame.open();
					return;
				}

				wpMediaFrame = wp.media({
					title: 'Select or Upload Vertical Poster (9:16)',
					button: { text: 'Use as Poster' },
					multiple: false,
					library: { type: 'image' }
				});

				wpMediaFrame.on('select', function() {
					var attachment = wpMediaFrame.state().get('selection').first().toJSON();
					if (attachment && attachment.url) {
						$('#shorttv_vertical_poster_input').val(attachment.url).trigger('change');
					}
				});

				wpMediaFrame.open();
			});

			// Cloudinary Upload Widget Handler
			function openCloudinaryWidget(targetInput, resourceType) {
				if (typeof cloudinary === 'undefined') {
					alert('Cloudinary upload widget is loading or blocked by an ad-blocker.');
					return;
				}

				var cloudName = (window.SHORTTV_CLOUDINARY && SHORTTV_CLOUDINARY.cloudName) ? SHORTTV_CLOUDINARY.cloudName : 'your-cloud';
				var uploadPreset = (window.SHORTTV_CLOUDINARY && SHORTTV_CLOUDINARY.uploadPreset) ? SHORTTV_CLOUDINARY.uploadPreset : 'my_video_preset';
				var targetFolder = getShortTvTargetFolder();

				var myWidget = cloudinary.createUploadWidget({
					cloudName: cloudName,
					uploadPreset: uploadPreset,
					folder: targetFolder,
					resourceType: resourceType || 'auto',
					sources: ['local', 'url', 'camera'],
					multiple: false,
					maxFiles: 1,
					clientAllowedFormats: ['mp4', 'm3u8', 'mov', 'webm', 'png', 'jpg', 'jpeg', 'webp'],
					theme: 'purple',
				}, function(error, result) {
					if (!error && result && result.event === "success") {
						var secureUrl = result.info.secure_url;
						if (targetInput) {
							var $input = $(targetInput);
							$input.val(secureUrl).trigger('change');

							// If inside an episode row and uploading a video, populate both HLS & MP4
							var $card = $input.closest('.shorttv-episode-card');
							if ($card.length) {
								var hlsUrl = secureUrl.indexOf('.m3u8') !== -1 ? secureUrl : secureUrl.replace(/\.(mp4|mov|webm|mkv|avi|m4v)($|\?)/i, '.m3u8$2');
								var mp4Url = secureUrl.indexOf('.m3u8') !== -1 ? secureUrl.replace(/\.m3u8($|\?)/, '.mp4$1') : secureUrl;
								$card.find('.field-hls-url').val(hlsUrl);
								$card.find('.field-mp4-url').val(mp4Url);
								if (result.info.duration) {
									$card.find('input[name*="[duration_seconds]"]').val(Math.round(result.info.duration));
								}
								updateShortTvJsonFromInputs();
							}
						}
					}
				});

				myWidget.open();
			}

			$(document).on('click', '.btn-cloudinary-upload', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var targetSelector = $(this).data('target');
				var resourceType = $(this).data('resource-type') || 'auto';
				var $input = (targetSelector === 'prev') ? $(this).siblings('input') : $(targetSelector);
				openCloudinaryWidget($input, resourceType);
			});

			// Helper to get default unlock type based on bulk upload preset
			function getShortTvDefaultUnlockType(epNum) {
				var rule = $('#shorttv-bulk-upload-rule').val() || 'coins_5free';
				if (rule === 'vip_all') return 'vip';
				if (rule === 'free_all') return 'free';
				if (rule === 'coins_all') return 'coins';
				return (epNum <= 5) ? 'free' : 'coins';
			}

			// Helper to construct badge HTML for episode cards
			function shorttvGetBadgeHtml(unlockType) {
				if (unlockType === 'vip') {
					return '<span class="ep-access-badge" style="background:#f3e8ff; color:#7e22ce; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">👑 VIP ONLY</span>';
				} else if (unlockType === 'free') {
					return '<span class="ep-access-badge" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">🟢 FREE</span>';
				} else {
					return '<span class="ep-access-badge" style="background:#fef3c7; color:#b45309; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">🪙 COINS</span>';
				}
			}

			// Helper to construct modern custom Access Rule dropdown with notes
			function shorttvBuildAccessSelectHtml(index, unlockType) {
				unlockType = unlockType || 'free';
				var options = [
					{ value: 'free', icon: '🟢', label: 'Free', note: 'Open to all viewers with no cost or restrictions.' },
					{ value: 'coins', icon: '🪙', label: 'Coins', note: 'Requires coins to unlock (supports rewarded ad unlock).' },
					{ value: 'vip', icon: '👑', label: 'VIP Pass Only', note: 'Exclusive to active VIP & Premium subscribers.' }
				];
				var cur = options.find(function(o) { return o.value === unlockType; }) || options[0];

				var html = '<div class="shorttv-custom-select-wrap">' +
					'<select class="field-ep-unlock-type shorttv-hidden-native-select" name="shorttv_episodes[' + index + '][access_control][unlock_type]" style="display:none !important; position:absolute !important; opacity:0 !important; pointer-events:none !important; width:0 !important; height:0 !important; margin:0 !important; padding:0 !important; left:-9999px !important;">';
				options.forEach(function(o) {
					html += '<option value="' + o.value + '" ' + (o.value === unlockType ? 'selected' : '') + '>' + o.icon + ' ' + o.label + '</option>';
				});
				html += '</select>' +
					'<button type="button" class="shorttv-custom-select-trigger" aria-haspopup="listbox" aria-expanded="false">' +
						'<span class="trigger-label"><span>' + cur.icon + '</span> <strong>' + cur.label + '</strong></span>' +
						'<svg class="trigger-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>' +
					'</button>' +
					'<div class="shorttv-custom-select-menu" role="listbox">';
				options.forEach(function(o) {
					html += '<div class="shorttv-custom-select-option ' + (o.value === unlockType ? 'is-selected' : '') + '" data-value="' + o.value + '" role="option">' +
						'<span class="opt-icon">' + o.icon + '</span>' +
						'<div class="opt-info">' +
							'<div class="opt-title">' + o.label + '</div>' +
							'<div class="opt-note">' + o.note + '</div>' +
						'</div>' +
					'</div>';
				});
				html += '</div></div>';
				return html;
			}

			// Delegated click handler: Toggle custom dropdown menu
			$(document).on('click', '.shorttv-custom-select-trigger', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $wrap = $(this).closest('.shorttv-custom-select-wrap');
				var $menu = $wrap.find('.shorttv-custom-select-menu');
				var isOpen = $menu.hasClass('is-open');

				// Close all other open custom menus
				$('.shorttv-custom-select-menu.is-open').removeClass('is-open');
				$('.shorttv-custom-select-trigger.is-open').removeClass('is-open');

				if (!isOpen) {
					$(this).addClass('is-open');
					$menu.addClass('is-open');
				}
			});

			// Delegated click handler: Select choice from custom dropdown
			$(document).on('click', '.shorttv-custom-select-option', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $opt = $(this);
				var val = $opt.attr('data-value');
				var icon = $opt.find('.opt-icon').text();
				var label = $opt.find('.opt-title').text();

				var $wrap = $opt.closest('.shorttv-custom-select-wrap');
				var $select = $wrap.find('select');
				var $trigger = $wrap.find('.shorttv-custom-select-trigger');
				var $menu = $wrap.find('.shorttv-custom-select-menu');

				// Update hidden select and trigger change
				$select.val(val).trigger('change');

				// Update UI trigger
				$trigger.find('.trigger-label').html('<span>' + icon + '</span> <strong>' + label + '</strong>');

				// Update active class
				$menu.find('.shorttv-custom-select-option').removeClass('is-selected');
				$opt.addClass('is-selected');

				// Close menu
				$trigger.removeClass('is-open');
				$menu.removeClass('is-open');

				// Update live schema
				if (typeof updateShortTvJsonFromInputs === 'function') {
					updateShortTvJsonFromInputs();
				}
			});

			// Close dropdown when clicking anywhere outside
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.shorttv-custom-select-wrap').length) {
					$('.shorttv-custom-select-menu.is-open').removeClass('is-open');
					$('.shorttv-custom-select-trigger.is-open').removeClass('is-open');
				}
			});

			// Helper: Parse episode number from filename across common naming schemes
			function shorttvParseEpisodeNumber(filename) {
				if (!filename) return null;
				// Strip file extension
				var name = filename.replace(/\.[a-zA-Z0-9]+$/, '');

				// 1. Season + Episode format (e.g., S1E1, S01E11, S1E15, S01-E05, S1_E1, -en-S1E1-720P)
				var m = name.match(/s\d+[-_.\s]*e(\d+)/i);
				if (m && m[1]) return parseInt(m[1], 10);

				// 2. Explicit Episode / Ep / Eps keyword (e.g., Episode 1, Ep.01, Ep-15, Eps 2)
				m = name.match(/(?:episode|eps|ep)[_.\s-]*0*(\d+)/i);
				if (m && m[1]) return parseInt(m[1], 10);

				// 3. Standalone 'E' indicator with delimiters (e.g., -e15-, _e01_, (e5), [E12], space E15)
				m = name.match(/(?:^|[-_.\s\[\(])e0*(\d+)(?:[-_.\s\]\)]|$)/i);
				if (m && m[1]) return parseInt(m[1], 10);

				// 4. Bracketed numbers (e.g., [01], (15), [Ep 3])
				m = name.match(/[\[\(](?:ep|episode|e)?\s*0*(\d+)[\]\)]/i);
				if (m && m[1]) return parseInt(m[1], 10);

				// 5. Trailing numbers before resolution tag or end (e.g., "Drama - 15 - 720p", "Title - 01")
				m = name.match(/[-_.\s]+0*(\d+)(?:[-_.\s]*(?:720p|1080p|480p|4k|hd|fhd))?$/i);
				if (m && m[1]) return parseInt(m[1], 10);

				// 6. Pure numeric filename (e.g., "1", "01", "15")
				m = name.match(/^0*(\d+)$/);
				if (m && m[1]) return parseInt(m[1], 10);

				// 7. General number preceded by delimiter
				m = name.match(/(?:[-_.\s])0*(\d+)(?:[^\d]|$)/);
				if (m && m[1]) return parseInt(m[1], 10);

				return null;
			}

			// Helper: Sort episode cards by Episode Number in ascending sequence
			function shorttvSortEpisodeCards() {
				var $wrapper = $('#shorttv-episodes-wrapper');
				var $cards = $wrapper.children('.shorttv-episode-card').get();

				$cards.sort(function(a, b) {
					var numA = parseInt($(a).find('.field-ep-number').val(), 10) || 0;
					var numB = parseInt($(b).find('.field-ep-number').val(), 10) || 0;
					return numA - numB;
				});

				$.each($cards, function(idx, card) {
					$wrapper.append(card);
				});

				// Re-index names to maintain continuous array indexing for PHP POST
				$wrapper.children('.shorttv-episode-card').each(function(index) {
					var $card = $(this);
					$card.attr('data-index', index);
					$card.find('input, select, textarea').each(function() {
						var name = $(this).attr('name');
						if (name) {
							var newName = name.replace(/shorttv_episodes\[\d+\]/, 'shorttv_episodes[' + index + ']');
							$(this).attr('name', newName);
						}
					});
				});

				if (typeof updateShortTvJsonFromInputs === 'function') {
					updateShortTvJsonFromInputs();
				}
			}

			// Manual Auto-Sort Button click handler
			$(document).on('click', '.btn-auto-sort-episodes', function(e) {
				e.preventDefault();
				shorttvSortEpisodeCards();
			});

			// Bulk Upload Videos
			$(document).on('click', '.btn-cloudinary-upload-bulk', function(e) {
				e.preventDefault();
				if (typeof cloudinary === 'undefined') {
					alert('Cloudinary upload widget is loading or blocked.');
					return;
				}

				var cloudName = (window.SHORTTV_CLOUDINARY && SHORTTV_CLOUDINARY.cloudName) ? SHORTTV_CLOUDINARY.cloudName : 'your-cloud';
				var uploadPreset = (window.SHORTTV_CLOUDINARY && SHORTTV_CLOUDINARY.uploadPreset) ? SHORTTV_CLOUDINARY.uploadPreset : 'my_video_preset';
				var targetFolder = getShortTvTargetFolder();

				var bulkWidget = cloudinary.createUploadWidget({
					cloudName: cloudName,
					uploadPreset: uploadPreset,
					folder: targetFolder,
					resourceType: 'video',
					sources: ['local', 'url'],
					multiple: true,
					maxFiles: 100,
					theme: 'purple',
				}, function(error, result) {
					if (!error && result && result.event === "success") {
						var secureUrl = result.info.secure_url;
						var originalName = result.info.original_filename || '';
						var format = result.info.format || 'mp4';
						var durationSec = Math.round(result.info.duration || 120);

						// Robust Episode Number Parser
						var parsedEpNum = shorttvParseEpisodeNumber(originalName);
						var count = $('#shorttv-episodes-wrapper .shorttv-episode-card').length;
						var epNum = (parsedEpNum !== null && parsedEpNum > 0) ? parsedEpNum : (count + 1);
						var defaultUnlock = getShortTvDefaultUnlockType(epNum);

						// Cloudinary on-the-fly HLS delivery URL from uploaded video
						var hlsVal = '';
						var mp4Val = '';

						if (secureUrl.indexOf('.m3u8') !== -1) {
							hlsVal = secureUrl;
							mp4Val = secureUrl.replace(/\.m3u8($|\?)/, '.mp4$1');
						} else {
							mp4Val = secureUrl;
							// Cloudinary delivers adaptive HLS streaming by changing extension to .m3u8
							hlsVal = secureUrl.replace(/\.(mp4|mov|webm|mkv|avi|m4v)($|\?)/i, '.m3u8$2');
						}

						var defCoins = (window.SHORTTV_CLOUDINARY && window.SHORTTV_CLOUDINARY.defaultCoinCost) ? window.SHORTTV_CLOUDINARY.defaultCoinCost : 15;
						var html = '<div class="shorttv-episode-card" data-index="' + count + '" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">' +
							'<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px; flex-wrap:wrap; gap:8px;">' +
								'<div class="ep-header-left" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">' +
									'<span style="background:#e0f2fe; color:#0369a1; font-weight:800; font-size:12px; padding:3px 8px; border-radius:4px;">EP #<span class="ep-num-label">' + epNum + '</span></span>' +
									'<strong style="color:#0f172a; font-size:14px;" class="ep-title-label">Episode ' + epNum + '</strong>' +
									shorttvGetBadgeHtml(defaultUnlock) +
								'</div>' +
								'<button type="button" class="button btn-remove-shorttv-ep" style="color:#dc2626; border-color:#fca5a5;">Remove</button>' +
							'</div>' +
							'<div class="shorttv-ep-grid-main">' +
								'<div><label class="shorttv-field-label">Ep #</label><input type="number" class="field-ep-number" name="shorttv_episodes[' + count + '][episode_number]" value="' + epNum + '" min="1" required /></div>' +
								'<div><label class="shorttv-field-label">Episode Title</label><input type="text" class="field-ep-title" name="shorttv_episodes[' + count + '][title]" value="Episode ' + epNum + '" required /></div>' +
								'<div><label class="shorttv-field-label">Access Rule</label>' + shorttvBuildAccessSelectHtml(count, defaultUnlock) + '</div>' +
							'</div>' +
							'<div class="shorttv-ep-grid-streams">' +
								'<div><label class="shorttv-field-label">Cloudinary HLS Stream URL (.m3u8)</label><div class="shorttv-stream-input-group"><input type="text" class="field-hls-url" name="shorttv_episodes[' + count + '][sources][hls_stream_url]" value="' + hlsVal + '" placeholder="https://res.cloudinary.com/.../ep' + epNum + '.m3u8" /><button type="button" class="btn-cloudinary-upload" data-target="prev" data-resource-type="video">☁️</button></div></div>' +
								'<div><label class="shorttv-field-label">Cloudinary Fallback MP4 (1080p / 720p)</label><div class="shorttv-stream-input-group"><input type="text" class="field-mp4-url" name="shorttv_episodes[' + count + '][sources][fallback_mp4]" value="' + mp4Val + '" placeholder="https://res.cloudinary.com/.../ep' + epNum + '_1080p.mp4" /><button type="button" class="btn-cloudinary-upload" data-target="prev" data-resource-type="video">☁️</button></div></div>' +
							'</div>' +
						'</div>';

						$('#shorttv-episodes-wrapper').append(html);
						shorttvSortEpisodeCards();
					}
				});

				bulkWidget.open();
			});

			// Update JSON Schema live from inputs
			function updateShortTvJsonFromInputs() {
				var defCoins = (window.SHORTTV_CLOUDINARY && window.SHORTTV_CLOUDINARY.defaultCoinCost) ? window.SHORTTV_CLOUDINARY.defaultCoinCost : 15;
				var episodes = [];
				$('#shorttv-episodes-wrapper .shorttv-episode-card').each(function(i) {
					var $card = $(this);
					var epNum = parseInt($card.find('.field-ep-number').val(), 10) || (i + 1);
					var title = $card.find('.field-ep-title').val() || ('Episode ' + epNum);
					var duration = 120;
					var unlockType = $card.find('.field-ep-unlock-type').val() || 'free';
					var hlsUrl = $card.find('.field-hls-url').val() || '';
					var mp4Url = $card.find('.field-mp4-url').val() || '';
					var overview = $card.find('input[name*="[overview]"]').val() || '';

					episodes.push({
						episode_number: epNum,
						title: title,
						overview: overview,
						duration_seconds: duration,
						access_control: {
							unlock_type: unlockType,
							coin_cost: (unlockType === 'coins') ? defCoins : 0,
							ad_unlock_allowed: (unlockType === 'ad'),
							is_unlocked: (unlockType === 'free')
						},
						sources: {
							aspect_ratio: "9:16",
							hls_stream_url: hlsUrl,
							fallback_mp4: mp4Url
						}
					});
				});

				var genresStr = $('input[name="shorttv_genres_str"]').val() || '';
				var genresList = genresStr ? genresStr.split(',').map(s => s.trim()).filter(Boolean) : [];
				var posterUrl = $('input[name="shorttv_vertical_poster"]').val() || '';
				var curTitle = $('#shorttv_custom_title').val() || $('#title').val() || "<?php echo esc_js( get_the_title( $post->ID ) ); ?>";

				var liveSchema = {
					media_id: $('input[name="shorttv_media_id"]').val() || 'short_<?php echo $post->ID; ?>',
					type: "short_series",
					title: curTitle,
					slug: curTitle.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''),
					is_published: true,
					release_year: parseInt($('input[name="shorttv_release_year"]').val(), 10) || 2026,
					total_episodes: parseInt($('input[name="shorttv_total_episodes"]').val(), 10) || episodes.length || 1,
					language: $('input[name="shorttv_language"]').val() || 'English',
					genres: genresList.length > 0 ? genresList : ["Drama", "Romance"],
					overview: $('#shorttv_overview_input').val() || "",
					cover_assets: {
						vertical_poster: posterUrl,
						horizontal_banner: posterUrl
					},
					analytics: {
						view_count: parseInt($('input[name="shorttv_view_count"]').val(), 10) || 0,
						like_count: parseInt($('input[name="shorttv_like_count"]').val(), 10) || 0,
						bookmark_count: parseInt($('input[name="shorttv_bookmark_count"]').val(), 10) || 0
					},
					episodes: episodes
				};

				$('#shorttv_raw_json_input').val(JSON.stringify(liveSchema, null, 2));
			}

			// Update access rule badge live on dropdown change
			$(document).on('change', '.field-ep-unlock-type', function() {
				var val = $(this).val();
				var $card = $(this).closest('.shorttv-episode-card');
				var $badge = $card.find('.ep-access-badge');
				if (val === 'vip') {
					$badge.css({background: '#f3e8ff', color: '#7e22ce'}).text('👑 VIP ONLY');
				} else if (val === 'coins') {
					$badge.css({background: '#fef3c7', color: '#b45309'}).text('🪙 COINS');
				} else {
					$badge.css({background: '#dcfce7', color: '#15803d'}).text('🟢 FREE');
				}
			});

			// ⚡ Apply Bulk Upload Rule to All Existing Episodes
			$(document).on('click', '#btn-apply-bulk-rule-all', function(e) {
				e.preventDefault();
				var rule = $('#shorttv-bulk-upload-rule').val() || 'coins_5free';
				var $cards = $('#shorttv-episodes-wrapper .shorttv-episode-card');
				if ($cards.length === 0) {
					alert('No episode rows found to apply rule to.');
					return;
				}

				$cards.each(function(i) {
					var $card = $(this);
					var epNum = parseInt($card.find('.field-ep-number').val(), 10) || (i + 1);
					var targetType = 'free';
					if (rule === 'vip_all') targetType = 'vip';
					else if (rule === 'free_all') targetType = 'free';
					else if (rule === 'coins_all') targetType = 'coins';
					else targetType = (epNum <= 5) ? 'free' : 'coins';

					// Update hidden native select
					var $select = $card.find('.field-ep-unlock-type');
					$select.val(targetType);

					// Update custom select trigger label
					var icon = (targetType === 'vip' ? '👑' : (targetType === 'coins' ? '🪙' : '🟢'));
					var label = (targetType === 'vip' ? 'VIP Pass Only' : (targetType === 'coins' ? 'Coins' : 'Free'));
					$card.find('.trigger-label').html('<span>' + icon + '</span> <strong>' + label + '</strong>');

					// Update options selected state in custom menu
					$card.find('.shorttv-custom-select-option').removeClass('is-selected');
					$card.find('.shorttv-custom-select-option[data-value="' + targetType + '"]').addClass('is-selected');

					// Update badge
					var $badge = $card.find('.ep-access-badge');
					if (targetType === 'vip') {
						$badge.css({background: '#f3e8ff', color: '#7e22ce'}).text('👑 VIP ONLY');
					} else if (targetType === 'coins') {
						$badge.css({background: '#fef3c7', color: '#b45309'}).text('🪙 COINS');
					} else {
						$badge.css({background: '#dcfce7', color: '#15803d'}).text('🟢 FREE');
					}
				});

				updateShortTvJsonFromInputs();
				alert('✓ Successfully applied "' + $('#shorttv-bulk-upload-rule option:selected').text() + '" to all ' + $cards.length + ' episode(s)!');
			});

			// Sync custom title with native WordPress title field & live update folder inputs/badges
			$('#shorttv_custom_title').on('input change keyup', function() {
				var val = $(this).val();
				$('#title').val(val);
				if (!$('#shorttv-gumlet-subfolder-input').data('user-edited')) {
					$('#shorttv-gumlet-subfolder-input').val(val);
				}
				if (!$('#shorttv-cloudinary-subfolder-input').data('user-edited')) {
					$('#shorttv-cloudinary-subfolder-input').val(val);
				}
				if (typeof updateGumletPathPreview === 'function') {
					updateGumletPathPreview();
				}
				if (typeof updateCloudinaryPathPreview === 'function') {
					updateCloudinaryPathPreview();
				}
				updateFolderBadge();
			});

			$('#shorttv-gumlet-subfolder-input').on('input', function() {
				$(this).data('user-edited', true);
				if (typeof updateGumletPathPreview === 'function') {
					updateGumletPathPreview();
				}
			});

			$('#shorttv-cloudinary-subfolder-input').on('input', function() {
				$(this).data('user-edited', true);
				if (typeof updateCloudinaryPathPreview === 'function') {
					updateCloudinaryPathPreview();
				}
			});

			$('#shorttv-cloudinary-dynamic-folder').on('input', function() {
				$(this).data('user-edited', true);
			});

			// Listen for live input changes across all form fields
			$(document).on('input change keyup', '#shorttv_custom_title, #title, #shorttv_overview_input, input[name^="shorttv_"], select[name^="shorttv_"], .shorttv-episode-card input, .shorttv-episode-card select', function() {
				updateFolderBadge();
				updateShortTvJsonFromInputs();
			});

			// Auto-probe duration when an MP4 / direct video URL is pasted or changed
			$(document).on('change blur', '.field-mp4-url, .field-hls-url', function() {
				var url = $.trim($(this).val());
				var $card = $(this).closest('.shorttv-episode-card');
				var $durInput = $card.find('input[name*="[duration_seconds]"]');
				var currentDur = parseInt($durInput.val(), 10);

				if (url && (url.indexOf('.mp4') !== -1 || url.indexOf('video') !== -1) && (currentDur === 120 || !currentDur || isNaN(currentDur))) {
					try {
						var v = document.createElement('video');
						v.preload = 'metadata';
						v.onloadedmetadata = function() {
							var d = Math.round(v.duration || 0);
							if (d > 0) {
								$durInput.val(d);
								updateShortTvJsonFromInputs();
							}
						};
						v.src = url;
					} catch(e) {}
				}
			});

			// Add Episode (Top and Bottom buttons)
			$(document).on('click', '#btn-add-shorttv-episode, .btn-add-shorttv-episode', function(e) {
				e.preventDefault();
				var count = $('#shorttv-episodes-wrapper .shorttv-episode-card').length;
				var epNum = count + 1;
				var defaultUnlock = getShortTvDefaultUnlockType(epNum);
				var curProv = $('#shorttv-active-provider').val() || 'r2';
				var isR2 = (curProv === 'r2');
				var isGumlet = (curProv === 'gumlet');
				var defCoins = (window.SHORTTV_CLOUDINARY && window.SHORTTV_CLOUDINARY.defaultCoinCost) ? window.SHORTTV_CLOUDINARY.defaultCoinCost : 15;
				var html = '<div class="shorttv-episode-card" data-index="' + count + '" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">' +
					'<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px; flex-wrap:wrap; gap:8px;">' +
						'<div class="ep-header-left" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">' +
							'<span style="background:#e0f2fe; color:#0369a1; font-weight:800; font-size:12px; padding:3px 8px; border-radius:4px;">EP #<span class="ep-num-label">' + epNum + '</span></span>' +
							'<strong style="color:#0f172a; font-size:14px;" class="ep-title-label">Episode ' + epNum + '</strong>' +
							shorttvGetBadgeHtml(defaultUnlock) +
						'</div>' +
						'<button type="button" class="button btn-remove-shorttv-ep" style="color:#dc2626; border-color:#fca5a5;">Remove</button>' +
					'</div>' +
					'<div class="shorttv-ep-grid-main">' +
						'<div><label class="shorttv-field-label">Ep #</label><input type="number" class="field-ep-number" name="shorttv_episodes[' + count + '][episode_number]" value="' + epNum + '" min="1" required /></div>' +
						'<div><label class="shorttv-field-label">Episode Title</label><input type="text" class="field-ep-title" name="shorttv_episodes[' + count + '][title]" value="Episode ' + epNum + '" required /></div>' +
						'<div><label class="shorttv-field-label">Access Rule</label>' + shorttvBuildAccessSelectHtml(count, defaultUnlock) + '</div>' +
					'</div>' +
					'<div class="shorttv-ep-grid-streams">' +
						'<div><label class="shorttv-field-label">HLS Master Stream URL (.m3u8)</label><div class="shorttv-stream-input-group"><input type="text" class="field-hls-url" name="shorttv_episodes[' + count + '][sources][hls_stream_url]" value="" placeholder="https://.../master.m3u8" /><button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Cloudflare R2" style="' + (isR2 ? 'display:inline-flex;' : 'display:none;') + ' background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button><button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="' + (isGumlet ? 'display:inline-flex;' : 'display:none;') + ' background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button><button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" style="' + (!isR2 && !isGumlet ? 'display:inline-flex;' : 'display:none;') + ' width:42px !important;">☁️</button></div></div>' +
						'<div><label class="shorttv-field-label">Fallback MP4 Stream (1080p / 720p)</label><div class="shorttv-stream-input-group"><input type="text" class="field-mp4-url" name="shorttv_episodes[' + count + '][sources][fallback_mp4]" value="" placeholder="https://.../video_1080p.mp4" /><button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Cloudflare R2" style="' + (isR2 ? 'display:inline-flex;' : 'display:none;') + ' background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button><button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="' + (isGumlet ? 'display:inline-flex;' : 'display:none;') + ' background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button><button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" style="' + (!isR2 && !isGumlet ? 'display:inline-flex;' : 'display:none;') + ' width:42px !important;">☁️</button></div></div>' +
					'</div>' +
				'</div>';
				$('#shorttv-episodes-wrapper').append(html);
				updateShortTvJsonFromInputs();
			});

			// Remove episode
			$(document).on('click', '.btn-remove-shorttv-ep', function(e) {
				e.preventDefault();
				$(this).closest('.shorttv-episode-card').remove();
				updateShortTvJsonFromInputs();
			});

			// Copy JSON
			$('#btn-copy-shorttv-json').on('click', function(e) {
				e.preventDefault();
				var copyText = document.getElementById("shorttv_raw_json_input");
				copyText.select();
				document.execCommand("copy");
				$(this).text('✓ Copied!');
				setTimeout(() => { $(this).text('📋 Copy JSON'); }, 2000);
			});

			// Parse JSON button
			$('#btn-parse-shorttv-json').on('click', function(e) {
				e.preventDefault();
				var raw = $('#shorttv_raw_json_input').val();
				try {
					var data = JSON.parse(raw);
					if (data.title) {
						$('#shorttv_custom_title').val(data.title);
						$('#title').val(data.title);
					}
					if (data.media_id) $('input[name="shorttv_media_id"]').val(data.media_id);
					if (data.release_year) $('input[name="shorttv_release_year"]').val(data.release_year);
					if (data.total_episodes) $('input[name="shorttv_total_episodes"]').val(data.total_episodes);
					if (data.language) $('input[name="shorttv_language"]').val(data.language);
					if (data.overview) $('#shorttv_overview_input').val(data.overview);
					if (data.genres && Array.isArray(data.genres)) $('input[name="shorttv_genres_str"]').val(data.genres.join(', '));
					
					if (data.cover_assets && data.cover_assets.vertical_poster) {
						$('input[name="shorttv_vertical_poster"]').val(data.cover_assets.vertical_poster).trigger('change');
					}

					if (data.analytics) {
						if (data.analytics.view_count) $('input[name="shorttv_view_count"]').val(data.analytics.view_count);
						if (data.analytics.like_count) $('input[name="shorttv_like_count"]').val(data.analytics.like_count);
						if (data.analytics.bookmark_count) $('input[name="shorttv_bookmark_count"]').val(data.analytics.bookmark_count);
					}

					if (data.episodes && Array.isArray(data.episodes) && data.episodes.length > 0) {
						var curProv = $('#shorttv-active-provider').val() || 'r2';
						var isR2Parse = (curProv === 'r2');
						var isGumletParse = (curProv === 'gumlet');
						var $wrapper = $('#shorttv-episodes-wrapper');
						$wrapper.empty();
						data.episodes.forEach(function(ep, idx) {
							var epNum = ep.episode_number || (idx + 1);
							var epTitle = ep.title || ('Episode ' + epNum);
							var duration = ep.duration_seconds || 120;
							var unlockT = (ep.access_control && ep.access_control.unlock_type) ? ep.access_control.unlock_type : 'free';
							var coinCost = (ep.access_control && ep.access_control.coin_cost) ? ep.access_control.coin_cost : 0;
							var isFree = (unlockT === 'free');
							var adAllowed = (ep.access_control && ep.access_control.ad_unlock_allowed);
							var hlsUrl = (ep.sources && ep.sources.hls_stream_url) ? ep.sources.hls_stream_url : '';
							var mp4Url = (ep.sources && ep.sources.fallback_mp4) ? ep.sources.fallback_mp4 : '';

							var defCoins = (window.SHORTTV_CLOUDINARY && window.SHORTTV_CLOUDINARY.defaultCoinCost) ? window.SHORTTV_CLOUDINARY.defaultCoinCost : 15;
							var epHtml = '<div class="shorttv-episode-card" data-index="' + idx + '" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">' +
								'<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px; flex-wrap:wrap; gap:8px;">' +
									'<div class="ep-header-left" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">' +
										'<span style="background:#e0f2fe; color:#0369a1; font-weight:800; font-size:12px; padding:3px 8px; border-radius:4px;">EP #<span class="ep-num-label">' + epNum + '</span></span>' +
										'<strong style="color:#0f172a; font-size:14px;" class="ep-title-label">' + $('<div>').text(epTitle).html() + '</strong>' +
										(isFree ? '<span class="ep-access-badge" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">🟢 FREE</span>' : (unlockT === 'vip' ? '<span class="ep-access-badge" style="background:#f3e8ff; color:#7e22ce; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">👑 VIP ONLY</span>' : (unlockT === 'ad' ? '<span class="ep-access-badge" style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">🎬 AD UNLOCK</span>' : '<span class="ep-access-badge" style="background:#fef3c7; color:#b45309; font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">🪙 COINS</span>'))) +
									'</div>' +
									'<button type="button" class="button btn-remove-shorttv-ep" style="color:#dc2626; border-color:#fca5a5;">Remove</button>' +
								'</div>' +
								'<div class="shorttv-ep-grid-main">' +
									'<div><label class="shorttv-field-label">Ep #</label><input type="number" class="field-ep-number" name="shorttv_episodes[' + idx + '][episode_number]" value="' + epNum + '" min="1" required /></div>' +
									'<div><label class="shorttv-field-label">Episode Title</label><input type="text" class="field-ep-title" name="shorttv_episodes[' + idx + '][title]" value="' + $('<div>').text(epTitle).html() + '" required /></div>' +
									'<div><label class="shorttv-field-label">Access Rule</label>' + shorttvBuildAccessSelectHtml(idx, unlockT) + '</div>' +
								'</div>' +
								'<div class="shorttv-ep-grid-streams">' +
									'<div><label class="shorttv-field-label">HLS Master Stream URL (.m3u8)</label><div class="shorttv-stream-input-group"><input type="text" class="field-hls-url" name="shorttv_episodes[' + idx + '][sources][hls_stream_url]" value="' + $('<div>').text(hlsUrl).html() + '" placeholder="https://.../master.m3u8" /><button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Cloudflare R2" style="' + (isR2Parse ? 'display:inline-flex;' : 'display:none;') + ' background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button><button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="' + (isGumletParse ? 'display:inline-flex;' : 'display:none;') + ' background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button><button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" style="' + (!isR2Parse && !isGumletParse ? 'display:inline-flex;' : 'display:none;') + ' width:42px !important;">☁️</button></div></div>' +
									'<div><label class="shorttv-field-label">Fallback MP4 Stream (1080p / 720p)</label><div class="shorttv-stream-input-group"><input type="text" class="field-mp4-url" name="shorttv_episodes[' + idx + '][sources][fallback_mp4]" value="' + $('<div>').text(mp4Url).html() + '" placeholder="https://.../video_1080p.mp4" /><button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Cloudflare R2" style="' + (isR2Parse ? 'display:inline-flex;' : 'display:none;') + ' background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button><button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="' + (isGumletParse ? 'display:inline-flex;' : 'display:none;') + ' background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button><button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" style="' + (!isR2Parse && !isGumletParse ? 'display:inline-flex;' : 'display:none;') + ' width:42px !important;">☁️</button></div></div>' +
								'</div>' +
							'</div>';

							$wrapper.append(epHtml);
						});
					}

					alert('JSON parsed successfully! All details and episode streams have been populated into the form. Click "Publish" or "Update" to save.');
				} catch(err) {
					alert('Invalid JSON syntax: ' + err.message);
				}
			});

			// One-click convert all MP4 URLs to HLS (.m3u8) across all episode rows
			$('#btn-convert-all-hls').on('click', function(e) {
				e.preventDefault();

				var convertedCount = 0;
				$('#shorttv-episodes-wrapper .shorttv-episode-card').each(function() {
					var $card = $(this);
					var mp4Val = $.trim($card.find('.field-mp4-url').val());
					var hlsVal = $.trim($card.find('.field-hls-url').val());

					if (mp4Val && (!hlsVal || hlsVal.indexOf('.m3u8') === -1)) {
						var generatedHls = mp4Val.indexOf('.m3u8') !== -1 ? mp4Val : mp4Val.replace(/\.(mp4|mov|webm|mkv|avi|m4v)($|\?)/i, '.m3u8$2');
						$card.find('.field-hls-url').val(generatedHls);
						convertedCount++;
					}
				});

				updateShortTvJsonFromInputs();

				if (convertedCount > 0) {
					alert('✓ Generated HLS (.m3u8) URLs for ' + convertedCount + ' episode(s). Click "Update" or "Publish" to save.');
				} else {
					alert('All episodes already have HLS (.m3u8) stream URLs configured.');
				}
			});

			// One-click Reset / Revert all streams back to direct MP4 (.mp4)
			$('#btn-reset-all-mp4').on('click', function(e) {
				e.preventDefault();
				var resetCount = 0;
				$('#shorttv-episodes-wrapper .shorttv-episode-card').each(function() {
					var $card = $(this);
					var mp4Val = $.trim($card.find('.field-mp4-url').val());
					var hlsVal = $.trim($card.find('.field-hls-url').val());

					var validMp4 = mp4Val;
					if (validMp4.indexOf('.m3u8') !== -1) {
						validMp4 = validMp4.replace(/\.m3u8($|\?)/i, '.mp4$1');
						$card.find('.field-mp4-url').val(validMp4);
					}
					if (!validMp4 && hlsVal) {
						validMp4 = hlsVal.replace(/\.m3u8($|\?)/i, '.mp4$1');
						$card.find('.field-mp4-url').val(validMp4);
					}

					if (validMp4) {
						$card.find('.field-hls-url').val(validMp4);
						resetCount++;
					}
				});

				updateShortTvJsonFromInputs();

				if (resetCount > 0) {
					alert('✓ Successfully restored ' + resetCount + ' episode(s) to direct MP4 stream playback! Click "Update" or "Publish" to save.');
				} else {
					alert('No episode stream URLs found to reset.');
				}
			});

			// One-click Auto-Renumber and Re-sequence all episode cards from 1 to N
			$('#btn-renumber-episodes').on('click', function(e) {
				e.preventDefault();
				var $wrapper = $('#shorttv-episodes-wrapper');
				var $cards = $wrapper.find('.shorttv-episode-card');

				if ($cards.length === 0) {
					alert('No episode cards to renumber.');
					return;
				}

				// Optional sort by stream filename or existing order
				$cards.each(function(i) {
					var epNum = i + 1;
					var isFree = (epNum <= 5);
					var $card = $(this);

					$card.attr('data-index', i);
					$card.find('.ep-num-label').text(epNum);
					$card.find('.ep-title-label').text('Episode ' + epNum);
					$card.find('.field-ep-number').val(epNum);
					$card.find('.field-ep-title').val('Episode ' + epNum);

					// Auto apply free/coins rules
					var $unlockSelect = $card.find('.field-ep-unlock-type');
					var $coinInput = $card.find('input[name*="[coin_cost]"]');
					var $adCheckbox = $card.find('input[name*="[ad_unlock_allowed]"]');

					if (isFree) {
						$unlockSelect.val('free');
						$coinInput.val(0);
						$adCheckbox.prop('checked', false);
					} else {
						if ($unlockSelect.val() === 'free') {
							$unlockSelect.val('coins');
							$coinInput.val(15);
							$adCheckbox.prop('checked', true);
						}
					}
				});

				updateShortTvJsonFromInputs();
				alert('✓ Re-sequenced ' + $cards.length + ' episodes sequentially from Episode 1 to ' + $cards.length + '! Click "Update" or "Publish" to save.');
			});

			// Dynamic Video Provider Switcher (Cloudflare R2 vs Gumlet vs Cloudinary)
			function applyProviderPreference(provider) {
				if (provider === 'r2') {
					$('.shorttv-studio-wrap').removeClass('shorttv-provider-gumlet shorttv-provider-cloudinary').addClass('shorttv-provider-r2');
					$('#shorttv-active-provider').val('r2');
					$('.shorttv-r2-btn, .btn-r2-upload').css('display', 'inline-flex');
					$('.btn-r2-upload-bulk').show();
					$('.shorttv-gumlet-btn, .btn-gumlet-upload, .shorttv-cloudinary-btn, .btn-cloudinary-upload').css('display', 'none');
					$('.btn-gumlet-upload-bulk, .btn-cloudinary-upload-bulk').hide();
					$('#section-tab-r2').show();
					$('#section-tab-gumlet, #section-tab-cloudinary').hide();
				} else if (provider === 'gumlet') {
					$('.shorttv-studio-wrap').removeClass('shorttv-provider-r2 shorttv-provider-cloudinary').addClass('shorttv-provider-gumlet');
					$('#shorttv-active-provider').val('gumlet');
					$('.shorttv-gumlet-btn, .btn-gumlet-upload').css('display', 'inline-flex');
					$('.btn-gumlet-upload-bulk').show();
					$('.shorttv-r2-btn, .btn-r2-upload, .shorttv-cloudinary-btn, .btn-cloudinary-upload').css('display', 'none');
					$('.btn-r2-upload-bulk, .btn-cloudinary-upload-bulk').hide();
					$('#section-tab-gumlet').show();
					$('#section-tab-r2, #section-tab-cloudinary').hide();
				} else {
					$('.shorttv-studio-wrap').removeClass('shorttv-provider-r2 shorttv-provider-gumlet').addClass('shorttv-provider-cloudinary');
					$('#shorttv-active-provider').val('cloudinary');
					$('.shorttv-cloudinary-btn, .btn-cloudinary-upload').css('display', 'inline-flex');
					$('.btn-cloudinary-upload-bulk').show();
					$('.shorttv-r2-btn, .btn-r2-upload, .shorttv-gumlet-btn, .btn-gumlet-upload').css('display', 'none');
					$('.btn-r2-upload-bulk, .btn-gumlet-upload-bulk').hide();
					$('#section-tab-cloudinary').show();
					$('#section-tab-r2, #section-tab-gumlet').hide();
				}
			}

			$(document).on('click', '.shorttv-tab-btn', function(e) {
				e.preventDefault();
				var tab = $(this).data('tab');
				var provider = 'cloudinary';
				if (tab === 'tab-r2') provider = 'r2';
				else if (tab === 'tab-gumlet') provider = 'gumlet';

				$('.shorttv-tab-btn').removeClass('active').css({ background: 'transparent', color: '#64748b', boxShadow: 'none' });
				if (provider === 'r2') {
					$(this).addClass('active').css({ background: '#ea580c', color: '#fff', boxShadow: '0 1px 3px rgba(234,88,12,0.3)' });
				} else if (provider === 'gumlet') {
					$(this).addClass('active').css({ background: '#7c3aed', color: '#fff', boxShadow: '0 1px 3px rgba(124,58,237,0.3)' });
				} else {
					$(this).addClass('active').css({ background: '#0284c7', color: '#fff', boxShadow: '0 1px 3px rgba(2,132,199,0.3)' });
				}
				applyProviderPreference(provider);
				autoSaveStorageSettings();
			});

			$('#shorttv-active-provider').on('change', function() {
				var p = $(this).val();
				applyProviderPreference(p);
			});
			applyProviderPreference($('#shorttv-active-provider').val() || '<?php echo $is_r2_active ? "r2" : ( $is_gumlet_active ? "gumlet" : "cloudinary" ); ?>');

			// ════════════════════════════════════════════════════════════
			// CLOUDFLARE R2 DIRECT UPLOAD HANDLERS
			// ════════════════════════════════════════════════════════════
			var $r2SingleTargetInput = null;
			var r2SingleEpNum = 1;

			$(document).on('click', '.btn-r2-upload', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var targetSelector = $(this).data('target');
				$r2SingleTargetInput = (targetSelector === 'prev') ? $(this).siblings('input') : $(targetSelector);
				r2SingleEpNum = $(this).data('ep-num') || 1;
				$('#shorttv-r2-single-input').val('').trigger('click');
			});

			$('#shorttv-r2-single-input').on('change', function() {
				if (!this.files || this.files.length === 0) return;
				var file = this.files[0];
				var dramaTitle = $.trim($('#shorttv_custom_title').val() || $('#title').val() || 'ShortTV Drama');

				detectVideoDuration(file, function(detectedDur) {
					uploadFileToR2(file, function(err, result) {
						if (err) {
							alert('Cloudflare R2 upload error: ' + err);
							return;
						}
						if ($r2SingleTargetInput) {
							$r2SingleTargetInput.val(result.publicUrl).trigger('change');
							var $card = $r2SingleTargetInput.closest('.shorttv-episode-card');
							if ($card.length) {
								$card.find('.field-hls-url').val(result.publicUrl);
								$card.find('.field-mp4-url').val(result.publicUrl);
								$card.find('input[name*="[duration_seconds]"]').val(detectedDur);
								updateShortTvJsonFromInputs();
							}
						}
					});
				});
			});

			// R2 Bulk Upload Trigger
			$(document).on('click', '.btn-r2-upload-bulk', function(e) {
				e.preventDefault();
				$('#shorttv-r2-bulk-input').val('').trigger('click');
			});

			// R2 Bulk Files Selected
			$('#shorttv-r2-bulk-input').on('change', function() {
				var files = Array.from(this.files || []);
				if (files.length === 0) return;

				// Pre-sort files naturally by detected episode number
				files.sort(function(a, b) {
					var epA = shorttvParseEpisodeNumber(a.name);
					var epB = shorttvParseEpisodeNumber(b.name);
					if (epA !== null && epB !== null) {
						return epA - epB;
					}
					return (a.name || '').localeCompare(b.name || '', undefined, {numeric: true, sensitivity: 'base'});
				});

				var dramaTitle = $.trim($('#shorttv_custom_title').val() || $('#title').val() || 'ShortTV Drama');
				var idx = 0;
				var successCount = 0;
				var errors = [];

				function processNextR2() {
					if (idx >= files.length) {
						shorttvSortEpisodeCards();
						$('#shorttv-r2-progress-modal').fadeOut();
						$('#shorttv-r2-bulk-input').val('');
						if (successCount > 0 && errors.length === 0) {
							alert('✓ Successfully uploaded ' + successCount + ' video(s) to Cloudflare R2 in sequential order! All episode stream URLs have been added.');
						} else if (successCount > 0 && errors.length > 0) {
							alert('Uploaded ' + successCount + ' video(s) to Cloudflare R2, but encountered ' + errors.length + ' error(s):\n\n' + errors.join('\n'));
						} else {
							alert('Failed to upload video(s) to Cloudflare R2:\n\n' + (errors.length ? errors.join('\n') : 'Unknown error'));
						}
						return;
					}

					var file = files[idx];
					var originalName = file.name || '';
					var parsedEpNum = shorttvParseEpisodeNumber(originalName);

					var count = $('#shorttv-episodes-wrapper .shorttv-episode-card').length;
					var epNum = (parsedEpNum !== null && parsedEpNum > 0) ? parsedEpNum : (count + 1);

					$('#shorttv-r2-progress-status').text('Uploading (' + (idx + 1) + '/' + files.length + '): ' + file.name + ' [EP #' + epNum + ']');

					detectVideoDuration(file, function(detectedDur) {
						uploadFileToR2(file, function(err, result) {
							if (!err && result && result.publicUrl) {
								successCount++;
								var defaultUnlock = getShortTvDefaultUnlockType(epNum);
								var html = '<div class="shorttv-episode-card" data-index="' + count + '" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">' +
									'<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px; flex-wrap:wrap; gap:8px;">' +
										'<div class="ep-header-left" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">' +
											'<span style="background:#e0f2fe; color:#0369a1; font-weight:800; font-size:12px; padding:3px 8px; border-radius:4px;">EP #<span class="ep-num-label">' + epNum + '</span></span>' +
											'<strong style="color:#0f172a; font-size:14px;" class="ep-title-label">Episode ' + epNum + '</strong>' +
											shorttvGetBadgeHtml(defaultUnlock) +
										'</div>' +
										'<button type="button" class="button btn-remove-shorttv-ep" style="color:#dc2626; border-color:#fca5a5;">Remove</button>' +
									'</div>' +
									'<div class="shorttv-ep-grid-main">' +
										'<div><label class="shorttv-field-label">Ep #</label><input type="number" class="field-ep-number" name="shorttv_episodes[' + count + '][episode_number]" value="' + epNum + '" min="1" required /></div>' +
										'<div><label class="shorttv-field-label">Episode Title</label><input type="text" class="field-ep-title" name="shorttv_episodes[' + count + '][title]" value="Episode ' + epNum + '" required /></div>' +
										'<div><label class="shorttv-field-label">Access Rule</label>' + shorttvBuildAccessSelectHtml(count, defaultUnlock) + '</div>' +
									'</div>' +
									'<div class="shorttv-ep-grid-streams">' +
										'<div><label class="shorttv-field-label">HLS Master Stream URL (.m3u8)</label><div class="shorttv-stream-input-group"><input type="text" class="field-hls-url" name="shorttv_episodes[' + count + '][sources][hls_stream_url]" value="' + result.publicUrl + '" placeholder="https://.../master.m3u8" /><button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Cloudflare R2" style="background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button><button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="display:none; background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button><button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" style="display:none; width:42px !important;">☁️</button></div></div>' +
										'<div><label class="shorttv-field-label">Fallback MP4 Stream (1080p / 720p)</label><div class="shorttv-stream-input-group"><input type="text" class="field-mp4-url" name="shorttv_episodes[' + count + '][sources][fallback_mp4]" value="' + result.publicUrl + '" placeholder="https://.../video_1080p.mp4" /><button type="button" class="btn-r2-upload shorttv-r2-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Cloudflare R2" style="background:#ea580c !important; color:#fff !important; width:42px !important;">🟠</button><button type="button" class="btn-gumlet-upload shorttv-gumlet-btn" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="display:none; background:#7c3aed !important; color:#fff !important; width:42px !important;">🎬</button><button type="button" class="btn-cloudinary-upload shorttv-cloudinary-btn" data-target="prev" data-resource-type="video" style="display:none; width:42px !important;">☁️</button></div></div>' +
									'</div>' +
								'</div>';

								$('#shorttv-episodes-wrapper').append(html);
								shorttvSortEpisodeCards();
								updateShortTvJsonFromInputs();
							} else {
								errors.push((file.name || 'File') + ': ' + (err || 'Upload failed'));
							}
							idx++;
							processNextR2();
						});
					});
				}

				$('#shorttv-r2-progress-modal').css('display', 'flex');
				processNextR2();
			});

			// Helper: Full Cloudflare R2 direct client upload pipeline with streaming AJAX backend
			function uploadFileToR2(file, callback) {
				var $modal = $('#shorttv-r2-progress-modal');
				var $status = $('#shorttv-r2-progress-status');
				var $fill = $('#shorttv-r2-progress-fill');
				var $pct = $('#shorttv-r2-progress-pct');

				$modal.css('display', 'flex');
				$status.text('Uploading "' + file.name + '" to Cloudflare R2...');
				$fill.css('width', '5%');
				$pct.text('5%');

				var folderVal = $('#shorttv-r2-dynamic-folder-select').val() || 'short';
				if (folderVal === 'custom') {
					folderVal = $.trim($('#shorttv-r2-dynamic-folder-custom').val()) || 'short';
				}
				var isAuto = $('#shorttv-r2-auto-subfolder').is(':checked');
				var dramaTitle = $.trim($('#shorttv_custom_title').val() || $('#title').val() || 'ShortTV Drama');
				var subfolderName = isAuto ? ($.trim($('#shorttv-r2-subfolder-input').val()) || dramaTitle) : '';

				var formData = new FormData();
				formData.append('action', 'shorttv_r2_upload');
				formData.append('nonce', window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : '');
				formData.append('file', file);
				formData.append('folder', folderVal);
				formData.append('subfolder', subfolderName);

				var xhr = new XMLHttpRequest();
				xhr.open('POST', window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl, true);

				xhr.upload.onprogress = function(e) {
					if (e.lengthComputable) {
						var percent = Math.round((e.loaded / e.total) * 90);
						$fill.css('width', percent + '%');
						$pct.text(percent + '%');
						$status.text('Streaming to Cloudflare R2 ' + percent + '% (' + file.name + ')...');
					}
				};

				xhr.onload = function() {
					if (xhr.status === 200) {
						try {
							var resp = JSON.parse(xhr.responseText);
							if (resp.success && resp.data && resp.data.public_url) {
								$fill.css('width', '100%');
								$pct.text('100%');
								$status.text('✓ Upload complete!');
								setTimeout(function() {
									$modal.hide();
									callback(null, {
										publicUrl: resp.data.public_url,
										objectKey: resp.data.object_key
									});
								}, 300);
							} else {
								$modal.hide();
								var errMsg = (resp.data && resp.data.message) ? resp.data.message : (resp.message || 'Failed to upload to Cloudflare R2.');
								callback(errMsg);
							}
						} catch(err) {
							$modal.hide();
							callback('Server response error: ' + err.message);
						}
					} else {
						$modal.hide();
						callback('Server returned HTTP ' + xhr.status);
					}
				};

				xhr.onerror = function() {
					$modal.hide();
					callback('Network error during file upload to Cloudflare R2.');
				};

				xhr.send(formData);
			}

			// Gumlet Single Upload state
			var $gumletSingleTargetInput = null;
			var gumletSingleEpNum = 1;

			$(document).on('click', '.btn-gumlet-upload', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var targetSelector = $(this).data('target');
				$gumletSingleTargetInput = (targetSelector === 'prev') ? $(this).siblings('input') : $(targetSelector);
				gumletSingleEpNum = $(this).data('ep-num') || 1;
				$('#shorttv-gumlet-single-input').val('').trigger('click');
			});

			// Helper to auto-detect video duration in seconds from File object in browser
			function detectVideoDuration(file, callback) {
				if (!file) {
					callback(120);
					return;
				}
				try {
					var video = document.createElement('video');
					video.preload = 'metadata';
					var objUrl = URL.createObjectURL(file);
					video.onloadedmetadata = function() {
						URL.revokeObjectURL(objUrl);
						var dur = Math.round(video.duration || 0);
						callback(dur > 0 ? dur : 120);
					};
					video.onerror = function() {
						URL.revokeObjectURL(objUrl);
						callback(120);
					};
					video.src = objUrl;
				} catch(e) {
					callback(120);
				}
			}

			// Gumlet Single File Selected
			$('#shorttv-gumlet-single-input').on('change', function() {
				if (!this.files || this.files.length === 0) return;
				var file = this.files[0];
				var dramaTitle = $.trim($('#shorttv_custom_title').val() || $('#title').val() || 'ShortTV Drama');
				var epTitle = dramaTitle + ' - EP ' + gumletSingleEpNum;
				var dramaId = $('input[name="shorttv_media_id"]').val() || '<?php echo $post->ID; ?>';
				var tags = ['shorttv', 'drama_' + dramaId, 'ep_' + gumletSingleEpNum];

				detectVideoDuration(file, function(detectedDur) {
					uploadFileToGumlet(file, epTitle, tags, function(err, result) {
						if (err) {
							alert('Gumlet upload error: ' + err);
							return;
						}
						if ($gumletSingleTargetInput) {
							$gumletSingleTargetInput.val(result.hlsUrl).trigger('change');
							var $card = $gumletSingleTargetInput.closest('.shorttv-episode-card');
							if ($card.length) {
								$card.find('.field-hls-url').val(result.hlsUrl);
								if (result.mp4Url) {
									$card.find('.field-mp4-url').val(result.mp4Url);
								}
								$card.find('input[name*="[duration_seconds]"]').val(detectedDur);
								updateShortTvJsonFromInputs();
							}
						}
					});
				});
			});

			// Gumlet Bulk Upload Trigger
			$(document).on('click', '.btn-gumlet-upload-bulk', function(e) {
				e.preventDefault();
				$('#shorttv-gumlet-bulk-input').val('').trigger('click');
			});

			// Gumlet Bulk Files Selected
			$('#shorttv-gumlet-bulk-input').on('change', function() {
				var files = Array.from(this.files || []);
				if (files.length === 0) return;

				// Pre-sort files naturally by detected episode number before sequential upload starts
				files.sort(function(a, b) {
					var epA = shorttvParseEpisodeNumber(a.name);
					var epB = shorttvParseEpisodeNumber(b.name);
					if (epA !== null && epB !== null) {
						return epA - epB;
					}
					return (a.name || '').localeCompare(b.name || '', undefined, {numeric: true, sensitivity: 'base'});
				});

				var dramaTitle = $.trim($('#shorttv_custom_title').val() || $('#title').val() || 'ShortTV Drama');
				var mediaId = $('input[name="shorttv_media_id"]').val() || '<?php echo $post->ID; ?>';

				// Process files sequentially
				var idx = 0;
				function processNext() {
					if (idx >= files.length) {
						shorttvSortEpisodeCards();
						$('#shorttv-gumlet-progress-modal').fadeOut();
						$('#shorttv-gumlet-bulk-input').val('');
						alert('✓ Successfully uploaded ' + files.length + ' video(s) to Gumlet Video CDN in sequential order!');
						return;
					}

					var file = files[idx];
					var originalName = file.name || '';
					var parsedEpNum = shorttvParseEpisodeNumber(originalName);

					var count = $('#shorttv-episodes-wrapper .shorttv-episode-card').length;
					var epNum = (parsedEpNum !== null && parsedEpNum > 0) ? parsedEpNum : (count + 1);
					var epTitle = dramaTitle + ' - Episode ' + epNum;
					var tags = ['shorttv', 'drama_' + mediaId, 'ep_' + epNum];

					$('#shorttv-gumlet-progress-status').text('Uploading (' + (idx + 1) + '/' + files.length + '): ' + file.name + ' [EP #' + epNum + ']');

					detectVideoDuration(file, function(detectedDur) {
						uploadFileToGumlet(file, epTitle, tags, function(err, result) {
							if (!err && result && result.hlsUrl) {
								var defaultUnlock = getShortTvDefaultUnlockType(epNum);
								var html = '<div class="shorttv-episode-card" data-index="' + count + '" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px;">' +
									'<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:10px; flex-wrap:wrap; gap:8px;">' +
										'<div class="ep-header-left" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">' +
											'<span style="background:#e0f2fe; color:#0369a1; font-weight:800; font-size:12px; padding:3px 8px; border-radius:4px;">EP #<span class="ep-num-label">' + epNum + '</span></span>' +
											'<strong style="color:#0f172a; font-size:14px;" class="ep-title-label">Episode ' + epNum + '</strong>' +
											shorttvGetBadgeHtml(defaultUnlock) +
										'</div>' +
										'<button type="button" class="button btn-remove-shorttv-ep" style="color:#dc2626; border-color:#fca5a5;">Remove</button>' +
									'</div>' +
									'<div class="shorttv-ep-grid-main">' +
										'<div><label class="shorttv-field-label">Ep #</label><input type="number" class="field-ep-number" name="shorttv_episodes[' + count + '][episode_number]" value="' + epNum + '" min="1" required /></div>' +
										'<div><label class="shorttv-field-label">Episode Title</label><input type="text" class="field-ep-title" name="shorttv_episodes[' + count + '][title]" value="Episode ' + epNum + '" required /></div>' +
										'<div><label class="shorttv-field-label">Access Rule</label>' + shorttvBuildAccessSelectHtml(count, defaultUnlock) + '</div>' +
									'</div>' +
									'<div class="shorttv-ep-grid-streams">' +
										'<div><label class="shorttv-field-label">HLS Master Stream URL (.m3u8)</label><div class="shorttv-stream-input-group"><input type="text" class="field-hls-url" name="shorttv_episodes[' + count + '][sources][hls_stream_url]" value="' + result.hlsUrl + '" placeholder="https://.../master.m3u8" /><button type="button" class="btn-gumlet-upload" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="background:#7c3aed !important; color:#fff !important; width:38px !important;">🎬</button><button type="button" class="btn-cloudinary-upload" data-target="prev" data-resource-type="video" style="width:38px !important;">☁️</button></div></div>' +
										'<div><label class="shorttv-field-label">Fallback MP4 Stream (1080p / 720p)</label><div class="shorttv-stream-input-group"><input type="text" class="field-mp4-url" name="shorttv_episodes[' + count + '][sources][fallback_mp4]" value="' + (result.mp4Url || '') + '" placeholder="https://.../video_1080p.mp4" /><button type="button" class="btn-gumlet-upload" data-target="prev" data-ep-num="' + epNum + '" title="Upload Video Directly to Gumlet Video CDN" style="background:#7c3aed !important; color:#fff !important; width:38px !important;">🎬</button><button type="button" class="btn-cloudinary-upload" data-target="prev" data-resource-type="video" style="width:38px !important;">☁️</button></div></div>' +
									'</div>' +
								'</div>';

								$('#shorttv-episodes-wrapper').append(html);
								shorttvSortEpisodeCards();
							}
							idx++;
							processNext();
						});
					});
				}

				$('#shorttv-gumlet-progress-modal').css('display', 'flex');
				processNext();
			});

			// Helper: Full Gumlet direct upload pipeline (request direct upload URL -> PUT S3/GCS -> poll asset HLS)
			function uploadFileToGumlet(file, title, tags, callback) {
				var $modal = $('#shorttv-gumlet-progress-modal');
				var $status = $('#shorttv-gumlet-progress-status');
				var $fill = $('#shorttv-gumlet-progress-fill');
				var $pct = $('#shorttv-gumlet-progress-pct');

				$modal.css('display', 'flex');
				$status.text('Initiating Gumlet direct upload asset for "' + file.name + '"...');
				$fill.css('width', '5%');
				$pct.text('5%');

				var ext = file.name.split('.').pop().toLowerCase() || 'mp4';
				var folderVal = $('#shorttv-gumlet-dynamic-folder-select').val();
				if (folderVal === 'custom') {
					folderVal = $.trim($('#shorttv-gumlet-dynamic-folder-custom').val());
				}
				var dramaTitle = $.trim($('#shorttv_custom_title').val() || $('#title').val() || 'ShortTV Drama');
				var subfolderName = $.trim($('#shorttv-gumlet-subfolder-input').val()) || dramaTitle;
				var autoSubfolder = $('#shorttv-gumlet-auto-subfolder').is(':checked') ? 1 : 0;

				$.ajax({
					url: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'shorttv_gumlet_create_upload',
						nonce: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : '',
						title: title,
						tags: tags,
						format: ext,
						parent_id: folderVal,
						folder_id: folderVal,
						drama_title: dramaTitle,
						subfolder_name: subfolderName,
						auto_subfolder: autoSubfolder
					},
					success: function(resp) {
						if (!resp.success || !resp.data || !resp.data.upload_url) {
							$modal.hide();
							callback(resp.data ? resp.data.message : 'Failed to get Gumlet direct upload URL.');
							return;
						}

						var uploadUrl = resp.data.upload_url;
						var assetId   = resp.data.asset_id;

						$status.text('Uploading file to Gumlet Cloud Storage...');
						$fill.css('width', '15%');
						$pct.text('15%');

						// Upload binary file directly to Gumlet's signed S3/GCS URL
						var xhr = new XMLHttpRequest();
						xhr.open('PUT', uploadUrl, true);
						xhr.setRequestHeader('Content-Type', file.type || 'video/mp4');

						xhr.upload.onprogress = function(e) {
							if (e.lengthComputable) {
								var percent = Math.round(15 + (e.loaded / e.total) * 70);
								$fill.css('width', percent + '%');
								$pct.text(percent + '%');
								$status.text('Uploading ' + Math.round((e.loaded / e.total) * 100) + '% (' + file.name + ')...');
							}
						};

						xhr.onload = function() {
							if (xhr.status >= 200 && xhr.status < 300) {
								$status.text('Upload complete! Finalizing Gumlet HLS stream...');
								$fill.css('width', '90%');
								$pct.text('90%');

								// Compute standard Gumlet CDN HLS master playlist URL
								var workspaceId = (window.SHORTTV_CLOUDINARY && SHORTTV_CLOUDINARY.gumletWorkspaceId) ? SHORTTV_CLOUDINARY.gumletWorkspaceId : '';
								var estimatedHls = 'https://video.gumlet.io/' + (workspaceId || 'video') + '/' + assetId + '/main.m3u8';
								var estimatedMp4 = 'https://video.gumlet.io/' + (workspaceId || 'video') + '/' + assetId + '/main.mp4';

								// Poll Gumlet status
								setTimeout(function() {
									$.ajax({
										url: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl,
										type: 'POST',
										dataType: 'json',
										data: {
											action: 'shorttv_gumlet_check_status',
											nonce: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : '',
											asset_id: assetId
										},
										success: function(statusResp) {
											$modal.hide();
											if (statusResp.success && statusResp.data && statusResp.data.sources && statusResp.data.sources.length > 0) {
												var hls = '';
												var mp4 = '';
												statusResp.data.sources.forEach(function(s) {
													if (s.type === 'hls' && !hls) hls = s.url;
													if (s.type === 'mp4' && !mp4) mp4 = s.url;
												});
												callback(null, {
													assetId: assetId,
													hlsUrl: hls || estimatedHls,
													mp4Url: mp4 || estimatedMp4
												});
											} else {
												callback(null, {
													assetId: assetId,
													hlsUrl: estimatedHls,
													mp4Url: estimatedMp4
												});
											}
										},
										error: function() {
											$modal.hide();
											callback(null, {
												assetId: assetId,
												hlsUrl: estimatedHls,
												mp4Url: estimatedMp4
											});
										}
									});
								}, 1200);
							} else {
								$modal.hide();
								callback('Binary upload to storage failed with status ' + xhr.status);
							}
						};

						xhr.onerror = function() {
							$modal.hide();
							callback('Network error during file upload to storage.');
						};

						xhr.send(file);
					},
					error: function(jqXHR, textStatus, err) {
						$modal.hide();
						callback(err || 'Failed to communicate with Gumlet API.');
					}
				});
			}

			// Auto Save Storage Settings (AJAX)
			var autoSaveStorageTimeout = null;
			function autoSaveStorageSettings() {
				var postId = $('#post_ID').val() || '<?php echo $post->ID; ?>';
				if (!postId || postId == '0') return;

				var folderId = $('#shorttv-gumlet-dynamic-folder-select').val();
				if (folderId === 'custom') {
					folderId = $('#shorttv-gumlet-dynamic-folder-custom').val();
				}
				var autoSub = $('#shorttv-gumlet-auto-subfolder').is(':checked') ? '1' : '0';
				var subfolder = $('#shorttv-gumlet-subfolder-input').val();
				var provider = $('#shorttv-active-provider').val();
				var cloudinaryFolder = $('#shorttv-cloudinary-dynamic-folder').val();
				var cloudinaryAutoSub = $('#shorttv-cloudinary-auto-subfolder').is(':checked') ? '1' : '0';
				var cloudinarySubfolder = $('#shorttv-cloudinary-subfolder-input').val();

				var r2Folder = $('#shorttv-r2-dynamic-folder-select').val() || 'short';
				if (r2Folder === 'custom') {
					r2Folder = $.trim($('#shorttv-r2-dynamic-folder-custom').val()) || 'short';
				}
				var r2AutoSub = $('#shorttv-r2-auto-subfolder').is(':checked') ? '1' : '0';
				var r2Subfolder = $('#shorttv-r2-subfolder-input').val();

				var folderName = $('#shorttv-gumlet-dynamic-folder-name').val() || '';
				if (!folderName) {
					folderName = $.trim($('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text()).replace(/^📁\s*|^📂\s*/, '');
				}

				$('#shorttv-saved-gumlet-folder-id').val(folderId);

				clearTimeout(autoSaveStorageTimeout);
				autoSaveStorageTimeout = setTimeout(function() {
					$.ajax({
						url: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'shorttv_save_storage_settings',
							nonce: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : '',
							post_id: postId,
							folder_id: folderId,
							folder_name: folderName,
							auto_subfolder: autoSub,
							subfolder: subfolder,
							provider: provider,
							cloudinary_folder: cloudinaryFolder,
							cloudinary_auto_subfolder: cloudinaryAutoSub,
							cloudinary_subfolder: cloudinarySubfolder,
							r2_folder: r2Folder,
							r2_auto_subfolder: r2AutoSub,
							r2_subfolder: r2Subfolder
						}
					});
				}, 400);
			}

			// Dynamic Cloudflare R2 Folder Select Handlers & Live Path Preview
			function updateR2PathPreview() {
				var baseFolder = $('#shorttv-r2-dynamic-folder-select').val() || 'short';
				if (baseFolder === 'custom') {
					baseFolder = $.trim($('#shorttv-r2-dynamic-folder-custom').val()) || 'short';
				}
				var isAuto = $('#shorttv-r2-auto-subfolder').is(':checked');
				var subName = $.trim($('#shorttv-r2-subfolder-input').val()) || $.trim($('#shorttv_custom_title').val() || $('#title').val() || '');
				if (subName.toLowerCase() === 'auto draft') { subName = ''; }

				if (isAuto) {
					$('#shorttv-r2-path-preview').text(baseFolder + ' / ' + (subName || 'Drama Title'));
					$('#shorttv-r2-subfolder-input').prop('disabled', false).css('opacity', '1');
				} else {
					$('#shorttv-r2-path-preview').text(baseFolder);
					$('#shorttv-r2-subfolder-input').prop('disabled', true).css('opacity', '0.5');
				}
			}

			// Dynamic Gumlet Folder Select Handlers & Live Path Preview
			function updateGumletPathPreview() {
				var parentId = $('#shorttv-gumlet-dynamic-folder-select').val();
				var parentName = $('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text() || 'Root / Workspace';
				if (parentName.indexOf('📁') !== -1) {
					parentName = parentName.replace('📁', '').trim();
				}
				if (parentName.indexOf('(') !== -1) {
					parentName = parentName.split('(')[0].trim();
				}
				var isAuto = $('#shorttv-gumlet-auto-subfolder').is(':checked');
				var subName = $.trim($('#shorttv-gumlet-subfolder-input').val()) || $.trim($('#shorttv_custom_title').val() || $('#title').val() || '');
				if (subName.toLowerCase() === 'auto draft') { subName = ''; }

				if (isAuto) {
					$('#shorttv-gumlet-path-preview').text(parentName + ' / ' + (subName || 'Drama Title'));
					$('#shorttv-gumlet-subfolder-input').prop('disabled', false).css('opacity', '1');
				} else {
					$('#shorttv-gumlet-path-preview').text(parentName);
					$('#shorttv-gumlet-subfolder-input').prop('disabled', true).css('opacity', '0.5');
				}
			}

			// Global click listener to close open folder dropdown menus
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.shorttv-folder-picker-wrap').length) {
					$('.shorttv-folder-dropdown-menu').hide();
					$('.shorttv-folder-picker-btn').removeClass('open');
					$('.shorttv-folder-picker-btn .btn-arrow').text('⏷');
				}
			});

			// Cloudflare R2 folder picker button toggle
			$('#shorttv-r2-folder-picker-btn').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $menu = $('#shorttv-r2-folder-menu');
				var isOpen = $menu.is(':visible');
				$('.shorttv-folder-dropdown-menu').hide();
				$('.shorttv-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');

				if (!isOpen) {
					$menu.show();
					$(this).addClass('open').find('.btn-arrow').text('⏶');
				}
			});

			// Render Cloudflare R2 Folder Tree Menu
			function renderR2FolderMenu(folders, selectedFolder) {
				var localStored = '';
				try { localStored = localStorage.getItem('shorttv_last_r2_folder') || ''; } catch(e) {}
				var savedFolder = (typeof selectedFolder !== 'undefined' && selectedFolder !== null)
					? selectedFolder
					: ( $('#shorttv-r2-dynamic-folder-select').val() || localStored || 'short' );

				var $menu = $('#shorttv-r2-folder-menu');
				$menu.empty();

				if (!folders || folders.length === 0) {
					folders = [
						{ name: 'short', id: 'short', is_bucket: true, subs: [] },
						{ name: 'movies', id: 'movies', is_bucket: true, subs: [] }
					];
				}

				// Render all real Buckets & Folders from Cloudflare R2
				folders.forEach(function(f) {
					var hasSubs = f.subs && f.subs.length > 0;
					var isSel = (savedFolder === f.id || savedFolder === f.name);
					var isExpanded = hasSubs;
					var labelText = (f.name === 'short') ? '📁 short (Bucket)' : ('📁 ' + f.name);

					var $item = $('<div class="shorttv-folder-item-row ' + (isSel ? 'selected' : '') + '" data-id="' + $('<div>').text(f.id).html() + '" data-name="' + $('<div>').text(f.name).html() + '"></div>');
					var $label = $('<div class="folder-label"><span>' + labelText + '</span></div>');
					$item.append($label);

					if (hasSubs) {
						var $arrow = $('<span class="folder-toggle-arrow" title="Expand/Collapse">' + (isExpanded ? '⏶' : '⏷') + '</span>');
						$arrow.on('click', function(e) {
							e.stopPropagation();
							var $subTree = $(this).closest('.shorttv-folder-item-row').next('.shorttv-folder-sub-tree');
							if ($subTree.is(':visible')) {
								$subTree.removeClass('expanded').slideUp(120);
								$(this).text('⏷');
							} else {
								$subTree.addClass('expanded').slideDown(120);
								$(this).text('⏶');
							}
						});
						$item.append($arrow);
					}

					$item.on('click', function() {
						var id = $(this).data('id');
						var name = $(this).data('name');
						$('#shorttv-r2-dynamic-folder-select').val(id);
						$('#shorttv-r2-folder-picker-btn .btn-selected-text').text('📁 ' + name);
						$menu.hide();
						$('#shorttv-r2-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
						updateR2PathPreview();
						autoSaveStorageSettings();
					});

					$menu.append($item);

					if (hasSubs) {
						var $subTree = $('<div class="shorttv-folder-sub-tree ' + (isExpanded ? 'expanded' : '') + '" style="' + (isExpanded ? 'display:block;' : 'display:none;') + '"></div>');
						f.subs.forEach(function(sub) {
							var isSubSel = ($('#shorttv-r2-subfolder-input').val() === sub);
							var $subItem = $('<div class="shorttv-folder-sub-item ' + (isSubSel ? 'selected' : '') + '" data-parent="' + $('<div>').text(f.id).html() + '" data-sub="' + $('<div>').text(sub).html() + '"><span>📂 ' + $('<div>').text(sub).html() + '</span></div>');

							$subItem.on('click', function(e) {
								e.stopPropagation();
								var parentId = $(this).data('parent');
								var subTitle = $(this).data('sub');
								$('#shorttv-r2-dynamic-folder-select').val(parentId);
								$('#shorttv-r2-folder-picker-btn .btn-selected-text').text('📁 ' + f.name);
								$('#shorttv-r2-subfolder-input').val(subTitle).trigger('change');
								$menu.hide();
								$('#shorttv-r2-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
								updateR2PathPreview();
								autoSaveStorageSettings();
							});
							$subTree.append($subItem);
						});
						$menu.append($subTree);
					}

					if (isSel) {
						$('#shorttv-r2-folder-picker-btn .btn-selected-text').text('📁 ' + f.name);
					}
				});

				// Type Custom Option
				var isCustomSel = (savedFolder === 'custom');
				var $customItem = $('<div class="shorttv-folder-item-row ' + (isCustomSel ? 'selected' : '') + '" data-id="custom"><div class="folder-label"><span>✏️ Type Custom...</span></div></div>');
				$customItem.on('click', function() {
					$('#shorttv-r2-dynamic-folder-select').val('custom');
					$('#shorttv-r2-folder-picker-btn .btn-selected-text').text('✏️ Custom Folder');
					$menu.hide();
					$('#shorttv-r2-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
					$('#shorttv-r2-dynamic-folder-custom').show().focus();
					updateR2PathPreview();
					autoSaveStorageSettings();
				});
				$menu.append($customItem);
			}

			// Dynamic Cloudflare R2 Folder Loader (Fetches REAL actual folders from R2 Bucket via S3 API)
			function loadR2Folders(selectedFolder) {
				var $btn = $('#btn-refresh-r2-folders');
				$btn.prop('disabled', true);
				var $svg = $btn.find('svg');
				$svg.addClass('shorttv-spinning');

				$.ajax({
					url: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'shorttv_r2_get_folders',
						nonce: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : ''
					},
					success: function(resp) {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');

						var folders = (resp.success && resp.data && resp.data.folders) ? resp.data.folders : [];
						renderR2FolderMenu(folders, selectedFolder);
						updateR2PathPreview();
					},
					error: function() {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');
						renderR2FolderMenu([], selectedFolder);
					}
				});
			}

			// R2 folder select & input handlers
			$('#shorttv-r2-dynamic-folder-select').on('change', function() {
				var val = $(this).val();
				if (val === 'custom') {
					$('#shorttv-r2-dynamic-folder-custom').show().focus();
				} else {
					$('#shorttv-r2-dynamic-folder-custom').hide();
					if (val) {
						try { localStorage.setItem('shorttv_last_r2_folder', val); } catch(e) {}
					}
				}
				updateR2PathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv-r2-dynamic-folder-custom').on('input change blur', function() {
				var val = $.trim($(this).val());
				if (val.indexOf('/') !== -1) {
					val = val.split('/')[0].trim();
					$(this).val(val);
				}
				if (val) {
					try { localStorage.setItem('shorttv_last_r2_folder', val); } catch(e) {}
				}
				$('#shorttv-r2-folder-picker-btn .btn-selected-text').text('📁 ' + (val || 'Custom'));
				updateR2PathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv-r2-subfolder-input').on('change input blur', function() {
				updateR2PathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv-r2-auto-subfolder').on('change', function() {
				updateR2PathPreview();
				autoSaveStorageSettings();
			});

			$('#btn-refresh-r2-folders').on('click', function(e) {
				e.preventDefault();
				loadR2Folders();
			});

			$('#btn-create-r2-folder').on('click', function(e) {
				e.preventDefault();
				var newName = prompt('Enter new folder name to create in Cloudflare R2:');
				if (!newName || !$.trim(newName)) return;
				newName = $.trim(newName).replace(/[\/\\]/g, '-');

				var currentParent = $('#shorttv-r2-dynamic-folder-select').val();
				if (currentParent === 'custom' || currentParent === 'short') currentParent = '';

				var $btn = $(this);
				var $svg = $btn.find('svg');
				$btn.prop('disabled', true);
				$svg.addClass('shorttv-spinning');

				$.ajax({
					url: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'shorttv_r2_create_folder',
						nonce: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : '',
						name: newName,
						parent: currentParent
					},
					success: function(resp) {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');
						if (resp.success) {
							alert('✓ Folder "' + newName + '" created successfully on Cloudflare R2 bucket!');
							$('#shorttv-r2-dynamic-folder-select').val(newName);
							$('#shorttv-r2-folder-picker-btn .btn-selected-text').text('📁 ' + newName);
							loadR2Folders(newName);
							autoSaveStorageSettings();
						} else {
							alert('Failed to create folder: ' + (resp.data ? resp.data.message : 'Unknown error'));
						}
					},
					error: function() {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');
						alert('Network error while creating Cloudflare R2 folder.');
					}
				});
			});

			// Gumlet folder picker button toggle
			$('#shorttv-gumlet-folder-picker-btn').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $menu = $('#shorttv-gumlet-folder-menu');
				var isOpen = $menu.is(':visible');
				$('.shorttv-folder-dropdown-menu').hide();
				$('.shorttv-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');

				if (!isOpen) {
					$menu.show();
					$(this).addClass('open').find('.btn-arrow').text('⏶');
				}
			});

			// Cloudinary folder picker button toggle
			$('#shorttv-cloudinary-folder-picker-btn').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				var $menu = $('#shorttv-cloudinary-folder-menu');
				var isOpen = $menu.is(':visible');
				$('.shorttv-folder-dropdown-menu').hide();
				$('.shorttv-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');

				if (!isOpen) {
					$menu.show();
					$(this).addClass('open').find('.btn-arrow').text('⏶');
				}
			});

			// Render Cloudinary folder tree menu
			function renderCloudinaryFolderMenu(selectedFolder) {
				var sel = selectedFolder || $('#shorttv-cloudinary-folder-select').val() || 'Movie';
				var $menu = $('#shorttv-cloudinary-folder-menu');
				$menu.empty();

				var realDramas = [];
				try {
					realDramas = JSON.parse($('.shorttv-studio-wrap').attr('data-real-dramas') || '[]');
				} catch(e) {}

				var categories = [
					{ name: 'Short', id: 'short', subs: [] },
					{ name: 'Movies', id: 'Movie', subs: (realDramas.length > 0 ? realDramas : []) },
					{ name: 'Series', id: 'series', subs: [] },
					{ name: 'Dramas', id: 'dramas', subs: [] }
				];

				// Add custom known categories if saved
				try {
					var extraKnown = JSON.parse(localStorage.getItem('shorttv_cloudinary_known_folders') || '[]');
					extraKnown.forEach(function(k) {
						if (!categories.find(function(c){ return c.name.toLowerCase() === k.toLowerCase() || c.id.toLowerCase() === k.toLowerCase(); })) {
							categories.push({ name: k, id: k, subs: [] });
						}
					});
				} catch(e) {}

				categories.forEach(function(cat) {
					var isCatSelected = (sel === cat.id || sel === cat.name);
					var hasSubs = cat.subs && cat.subs.length > 0;
					var isExpanded = (isCatSelected && hasSubs);

					var $item = $('<div class="shorttv-folder-item-row ' + (isCatSelected ? 'selected' : '') + '" data-id="' + $('<div>').text(cat.id).html() + '" data-name="' + $('<div>').text(cat.name).html() + '"></div>');
					
					var $label = $('<div class="folder-label"><span>📁 ' + $('<div>').text(cat.name).html() + '</span></div>');
					$item.append($label);

					if (hasSubs) {
						var $arrow = $('<span class="folder-toggle-arrow" title="Expand/Collapse">' + (isExpanded ? '⏶' : '⏷') + '</span>');
						$arrow.on('click', function(e) {
							e.stopPropagation();
							var $subTree = $(this).closest('.shorttv-folder-item-row').next('.shorttv-folder-sub-tree');
							if ($subTree.is(':visible')) {
								$subTree.removeClass('expanded').slideUp(120);
								$(this).text('⏷');
							} else {
								$subTree.addClass('expanded').slideDown(120);
								$(this).text('⏶');
							}
						});
						$item.append($arrow);
					}

					$item.on('click', function() {
						var id = $(this).data('id');
						var name = $(this).data('name');
						$('#shorttv-cloudinary-folder-select').val(id).trigger('change');
						$('#shorttv-cloudinary-folder-picker-btn .btn-selected-text').text('📁 ' + name);
						$('#shorttv-cloudinary-folder-menu').hide();
						$('#shorttv-cloudinary-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
					});

					$menu.append($item);

					if (hasSubs) {
						var $subTree = $('<div class="shorttv-folder-sub-tree ' + (isExpanded ? 'expanded' : '') + '" style="' + (isExpanded ? 'display:block;' : 'display:none;') + '"></div>');
						cat.subs.forEach(function(sub) {
							var isSubSel = ($('#shorttv-cloudinary-subfolder-input').val() === sub);
							var $subItem = $('<div class="shorttv-folder-sub-item ' + (isSubSel ? 'selected' : '') + '" data-parent="' + $('<div>').text(cat.id).html() + '" data-sub="' + $('<div>').text(sub).html() + '"><span>📂 ' + $('<div>').text(sub).html() + '</span></div>');
							
							$subItem.on('click', function(e) {
								e.stopPropagation();
								var parentId = $(this).data('parent');
								var subTitle = $(this).data('sub');
								$('#shorttv-cloudinary-folder-select').val(parentId);
								$('#shorttv-cloudinary-folder-picker-btn .btn-selected-text').text('📁 ' + cat.name);
								$('#shorttv-cloudinary-subfolder-input').val(subTitle).trigger('change');
								$('#shorttv-cloudinary-folder-menu').hide();
								$('#shorttv-cloudinary-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
								updateCloudinaryPathPreview();
								autoSaveStorageSettings();
							});
							$subTree.append($subItem);
						});
						$menu.append($subTree);
					}
				});

				// Type Custom Option
				var $customItem = $('<div class="shorttv-folder-item-row" data-id="custom"><div class="folder-label"><span>✏️ Type Custom...</span></div></div>');
				$customItem.on('click', function() {
					$('#shorttv-cloudinary-folder-select').val('custom').trigger('change');
					$('#shorttv-cloudinary-folder-picker-btn .btn-selected-text').text('✏️ Custom Folder');
					$('#shorttv-cloudinary-folder-menu').hide();
					$('#shorttv-cloudinary-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
					$('#shorttv-cloudinary-custom-folder-input').show().focus();
				});
				$menu.append($customItem);
			}

			$('#shorttv-gumlet-auto-subfolder').on('change', function() {
				updateGumletPathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv-gumlet-subfolder-input').on('change input blur', function() {
				updateGumletPathPreview();
				autoSaveStorageSettings();
			});

			// Cloudinary folder select & input handlers
			$('#shorttv-cloudinary-folder-select').on('change', function() {
				var val = $(this).val();
				if (val === 'custom') {
					$('#shorttv-cloudinary-custom-folder-input').show().focus();
				} else {
					$('#shorttv-cloudinary-custom-folder-input').hide();
					if (val) {
						try { localStorage.setItem('shorttv_last_cloudinary_folder', val); } catch(e) {}
					}
				}
				updateCloudinaryPathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv-cloudinary-custom-folder-input').on('input change blur', function() {
				var val = $.trim($(this).val());
				if (val.indexOf('/') !== -1) {
					val = val.split('/')[0].trim();
					$(this).val(val);
				}
				if (val) {
					try { localStorage.setItem('shorttv_last_cloudinary_folder', val); } catch(e) {}
				}
				$('#shorttv-cloudinary-folder-picker-btn .btn-selected-text').text('📁 ' + (val || 'Custom'));
				updateCloudinaryPathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv-cloudinary-subfolder-input').on('change input blur', function() {
				updateCloudinaryPathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv-cloudinary-auto-subfolder').on('change', function() {
				updateCloudinaryPathPreview();
				autoSaveStorageSettings();
			});

			$('#shorttv_custom_title').on('input change keyup', function() {
				var newTitle = $.trim($(this).val());
				if (newTitle.toLowerCase() === 'auto draft') { newTitle = ''; }
				if ($('#shorttv-r2-auto-subfolder').is(':checked')) {
					$('#shorttv-r2-subfolder-input').val(newTitle);
				}
				if ($('#shorttv-gumlet-auto-subfolder').is(':checked')) {
					$('#shorttv-gumlet-subfolder-input').val(newTitle);
				}
				if ($('#shorttv-cloudinary-auto-subfolder').is(':checked')) {
					$('#shorttv-cloudinary-subfolder-input').val(newTitle);
				}
				updateR2PathPreview();
				updateGumletPathPreview();
				updateCloudinaryPathPreview();
			});

			function loadGumletFolders(selectedId) {
				var localStored = '';
				try { localStored = localStorage.getItem('shorttv_last_gumlet_folder') || ''; } catch(e) {}
				var savedId = (typeof selectedId !== 'undefined' && selectedId !== null) 
					? selectedId 
					: ( $('#shorttv-saved-gumlet-folder-id').val() || localStored || '' );

				var $btn = $('#btn-refresh-gumlet-folders');
				var origHtml = $btn.html();
				$btn.prop('disabled', true);
				var $svg = $btn.find('svg');
				$svg.addClass('shorttv-spinning');

				$.ajax({
					url: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'shorttv_gumlet_get_folders',
						nonce: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : ''
					},
					success: function(resp) {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');
						if (resp.success && resp.data && resp.data.folders) {
							var $menu = $('#shorttv-gumlet-folder-menu');
							$menu.empty();

							var folders = resp.data.folders;
							var mainFolders = [];
							var subFoldersByParent = {};

							// Separate main root folders and subfolders
							folders.forEach(function(f) {
								var isSub = (f.parent_id && f.parent_id !== '');
								if (isSub) {
									if (!subFoldersByParent[f.parent_id]) {
										subFoldersByParent[f.parent_id] = [];
									}
									subFoldersByParent[f.parent_id].push(f);
								} else {
									mainFolders.push(f);
								}
							});

							// 1. Root / Workspace option
							var isRootSel = (!savedId);
							var $rootRow = $('<div class="shorttv-folder-item-row ' + (isRootSel ? 'selected' : '') + '" data-id=""><div class="folder-label"><span>📁 Root / Workspace</span></div></div>');
							$rootRow.on('click', function() {
								$('#shorttv-gumlet-dynamic-folder-select').val('');
								$('#shorttv-gumlet-dynamic-folder-name').val('Root / Workspace');
								$('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text('📁 Root / Workspace');
								$menu.hide();
								$('#shorttv-gumlet-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
								updateGumletPathPreview();
								autoSaveStorageSettings();
							});
							$menu.append($rootRow);

							// 2. Render Main folders with collapsible sub-tree
							mainFolders.forEach(function(mainF) {
								var hasSubs = subFoldersByParent[mainF.id] && subFoldersByParent[mainF.id].length > 0;
								var isMainSel = (savedId === mainF.id);
								var isExpanded = hasSubs;

								var $item = $('<div class="shorttv-folder-item-row ' + (isMainSel ? 'selected' : '') + '" data-id="' + $('<div>').text(mainF.id).html() + '" data-name="' + $('<div>').text(mainF.name).html() + '"></div>');
								var $label = $('<div class="folder-label"><span>📁 ' + $('<div>').text(mainF.name).html() + '</span></div>');
								$item.append($label);

								if (hasSubs) {
									var $arrow = $('<span class="folder-toggle-arrow" title="Expand/Collapse">' + (isExpanded ? '⏶' : '⏷') + '</span>');
									$arrow.on('click', function(e) {
										e.stopPropagation();
										var $subTree = $(this).closest('.shorttv-folder-item-row').next('.shorttv-folder-sub-tree');
										if ($subTree.is(':visible')) {
											$subTree.removeClass('expanded').slideUp(120);
											$(this).text('⏷');
										} else {
											$subTree.addClass('expanded').slideDown(120);
											$(this).text('⏶');
										}
									});
									$item.append($arrow);
								}

								$item.on('click', function() {
									var id = $(this).data('id');
									var name = $(this).data('name');
									$('#shorttv-gumlet-dynamic-folder-select').val(id);
									$('#shorttv-gumlet-dynamic-folder-name').val(name);
									$('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text('📁 ' + name);
									$menu.hide();
									$('#shorttv-gumlet-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
									updateGumletPathPreview();
									autoSaveStorageSettings();
								});

								$menu.append($item);

								if (hasSubs) {
									var $subTree = $('<div class="shorttv-folder-sub-tree ' + (isExpanded ? 'expanded' : '') + '" style="' + (isExpanded ? 'display:block;' : 'display:none;') + '"></div>');
									subFoldersByParent[mainF.id].forEach(function(subF) {
										var isSubSel = (savedId === subF.id);
										var $subItem = $('<div class="shorttv-folder-sub-item ' + (isSubSel ? 'selected' : '') + '" data-id="' + $('<div>').text(subF.id).html() + '" data-name="' + $('<div>').text(subF.name).html() + '"><span>📂 ' + $('<div>').text(subF.name).html() + '</span></div>');
										
										$subItem.on('click', function(e) {
											e.stopPropagation();
											var sId = $(this).data('id');
											var sName = $(this).data('name');
											$('#shorttv-gumlet-dynamic-folder-select').val(sId);
											$('#shorttv-gumlet-dynamic-folder-name').val(sName);
											$('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text('📂 ' + sName);
											$menu.hide();
											$('#shorttv-gumlet-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
											updateGumletPathPreview();
											autoSaveStorageSettings();
										});
										$subTree.append($subItem);

										if (isSubSel) {
											$('#shorttv-gumlet-dynamic-folder-name').val(subF.name);
											$('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text('📂 ' + subF.name);
										}
									});
									$menu.append($subTree);
								}

								if (isMainSel) {
									$('#shorttv-gumlet-dynamic-folder-name').val(mainF.name);
									$('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text('📁 ' + mainF.name);
								}
							});

							// Type Custom Option
							var $customItem = $('<div class="shorttv-folder-item-row" data-id="custom"><div class="folder-label"><span>✏️ Type Custom...</span></div></div>');
							$customItem.on('click', function() {
								$('#shorttv-gumlet-dynamic-folder-select').val('custom');
								$('#shorttv-gumlet-folder-picker-btn .btn-selected-text').text('✏️ Custom Folder');
								$menu.hide();
								$('#shorttv-gumlet-folder-picker-btn').removeClass('open').find('.btn-arrow').text('⏷');
								$('#shorttv-gumlet-dynamic-folder-custom').show().focus();
								updateGumletPathPreview();
								autoSaveStorageSettings();
							});
							$menu.append($customItem);

							updateGumletPathPreview();
						}
					},
					error: function() {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');
					}
				});
			}

			$('#btn-refresh-gumlet-folders').on('click', function(e) {
				e.preventDefault();
				loadGumletFolders();
			});

			$('#btn-create-gumlet-folder').on('click', function(e) {
				e.preventDefault();
				var newName = prompt('Enter new Gumlet folder name:');
				if (!newName || !$.trim(newName)) return;

				var currentParent = $('#shorttv-gumlet-dynamic-folder-select').val();
				if (currentParent === 'custom') currentParent = '';

				var $btn = $(this);
				var $svg = $btn.find('svg');
				$btn.prop('disabled', true);
				$svg.addClass('shorttv-spinning');

				$.ajax({
					url: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.ajaxUrl : ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'shorttv_gumlet_create_folder',
						nonce: window.SHORTTV_CLOUDINARY ? SHORTTV_CLOUDINARY.nonce : '',
						name: $.trim(newName),
						parent_id: currentParent || ''
					},
					success: function(resp) {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');
						if (resp.success && resp.data) {
							var newId = resp.data.folder_id || resp.data.id || '';
							alert('✓ Folder "' + newName + '" created successfully on Gumlet!');
							loadGumletFolders(newId);
							autoSaveStorageSettings();
						} else {
							alert('Failed to create folder: ' + (resp.data ? resp.data.message : 'Unknown error'));
						}
					},
					error: function() {
						$btn.prop('disabled', false);
						$svg.removeClass('shorttv-spinning');
						alert('Network error while creating Gumlet folder.');
					}
				});
			});

			// Studio Action Bar: Save Draft & Publish Triggers
			$('#btn-studio-save-draft').on('click', function(e) {
				e.preventDefault();
				updateShortTvJsonFromInputs();
				var $btn = $(this);
				$btn.prop('disabled', true).text('⏳ Saving...');
				
				if ($('#save-post').length) {
					$('#save-post').trigger('click');
				} else {
					$('<input>').attr({type: 'hidden', name: 'save', value: 'Save Draft'}).appendTo('#post');
					$('#post_status').val('draft');
					$('#post').submit();
				}
			});

			$('#btn-studio-publish').on('click', function(e) {
				e.preventDefault();
				var title = $.trim($('#shorttv_custom_title').val());
				if (!title) {
					alert('Please enter a Drama Title before publishing.');
					$('#shorttv_custom_title').focus();
					return;
				}

				updateShortTvJsonFromInputs();
				var $btn = $(this);
				$btn.prop('disabled', true).text('⏳ Publishing...');

				if ($('#publish').length) {
					$('#publish').trigger('click');
				} else {
					$('#post_status').val('publish');
					$('#post').submit();
				}
			});

			// Auto-load Cloudflare R2 folders and initialize path preview
			if ($('#shorttv-r2-dynamic-folder-select').length) {
				var localR2Stored = '';
				try { localR2Stored = localStorage.getItem('shorttv_last_r2_folder') || ''; } catch(e) {}
				if (localR2Stored && !$('#shorttv-r2-dynamic-folder-select').val()) {
					$('#shorttv-r2-dynamic-folder-select').val(localR2Stored);
					$('#shorttv-r2-folder-picker-btn .btn-selected-text').text('📁 ' + localR2Stored);
				}
				renderR2FolderMenu();
				loadR2Folders();
				updateR2PathPreview();
			}

			// Auto-load Gumlet folders and initialize path preview
			if ($('#shorttv-gumlet-dynamic-folder-select').length) {
				loadGumletFolders();
				updateGumletPathPreview();
			}
			if ($('#shorttv-cloudinary-folder-select').length) {
				var localCloudStored = '';
				try { localCloudStored = localStorage.getItem('shorttv_last_cloudinary_folder') || ''; } catch(e) {}
				if (localCloudStored && !$('#shorttv-cloudinary-folder-select').val()) {
					$('#shorttv-cloudinary-folder-select').val(localCloudStored);
					$('#shorttv-cloudinary-folder-picker-btn .btn-selected-text').text('📁 ' + localCloudStored);
				}
				renderCloudinaryFolderMenu();
				updateCloudinaryPathPreview();
			}
		});
		</script>
		<?php
	}
	public static function save_meta_box_data( $post_id ) {
		if ( ! isset( $_POST['shorttv_nonce'] ) || ! wp_verify_nonce( $_POST['shorttv_nonce'], 'shorttv_meta_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Update post title from custom studio input if changed
		$custom_title = ! empty( $_POST['shorttv_custom_title'] ) ? sanitize_text_field( $_POST['shorttv_custom_title'] ) : '';
		$current_title = get_the_title( $post_id );

		if ( $custom_title && $custom_title !== $current_title ) {
			remove_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box_data' ) );
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $custom_title,
				'post_name'  => sanitize_title( $custom_title ),
			) );
			add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box_data' ) );
			$current_title = $custom_title;
		}

		// Check if raw JSON was pasted and needs importing
		if ( ! empty( $_POST['shorttv_raw_json'] ) ) {
			$raw_json = trim( stripslashes( $_POST['shorttv_raw_json'] ) );
			$parsed = json_decode( $raw_json, true );
			if ( is_array( $parsed ) && ! empty( $parsed['episodes'] ) ) {
				self::save_series_from_schema( $post_id, $parsed );
				return;
			}
		}

		$overview = ! empty( $_POST['shorttv_overview'] ) ? sanitize_textarea_field( $_POST['shorttv_overview'] ) : get_post_field( 'post_content', $post_id );

		// Standard form save
		$schema = array(
			'media_id'       => sanitize_text_field( $_POST['shorttv_media_id'] ?? 'short_' . $post_id ),
			'type'           => 'short_series',
			'title'          => $current_title,
			'slug'           => get_post_field( 'post_name', $post_id ),
			'is_published'   => ( get_post_status( $post_id ) === 'publish' ),
			'release_year'   => absint( $_POST['shorttv_release_year'] ?? date('Y') ),
			'total_episodes' => absint( $_POST['shorttv_total_episodes'] ?? 60 ),
			'language'       => sanitize_text_field( $_POST['shorttv_language'] ?? 'English' ),
			'genres'         => ! empty( $_POST['shorttv_genres_str'] ) ? array_map( 'trim', explode( ',', sanitize_text_field( $_POST['shorttv_genres_str'] ) ) ) : array(),
			'overview'       => $overview,
			'cover_assets'   => array(
				'vertical_poster'   => esc_url_raw( $_POST['shorttv_vertical_poster'] ?? '' ),
				'horizontal_banner' => esc_url_raw( $_POST['shorttv_vertical_poster'] ?? '' ),
			),
			'analytics'      => array(
				'view_count'     => absint( $_POST['shorttv_view_count'] ?? 1420000 ),
				'like_count'     => absint( $_POST['shorttv_like_count'] ?? 89000 ),
				'bookmark_count' => absint( $_POST['shorttv_bookmark_count'] ?? 12500 ),
			),
			'episodes'       => array(),
		);

		$sub_settings     = get_option( 'short_subscription_settings', array() );
		$global_coin_cost = absint( $sub_settings['default_coin_cost'] ?? 15 );

		if ( isset( $_POST['shorttv_episodes'] ) && is_array( $_POST['shorttv_episodes'] ) ) {
			foreach ( $_POST['shorttv_episodes'] as $ep ) {
				$unlock_type = sanitize_text_field( $ep['access_control']['unlock_type'] ?? 'free' );
				$schema['episodes'][] = array(
					'episode_number'   => max( 1, absint( $ep['episode_number'] ?? 1 ) ),
					'title'            => sanitize_text_field( $ep['title'] ?? 'Episode' ),
					'overview'         => sanitize_text_field( $ep['overview'] ?? '' ),
					'duration_seconds' => absint( $ep['duration_seconds'] ?? 120 ),
					'access_control'   => array(
						'unlock_type'       => $unlock_type,
						'coin_cost'         => ( 'coins' === $unlock_type ) ? $global_coin_cost : 0,
						'ad_unlock_allowed' => ( 'ad' === $unlock_type ),
						'is_unlocked'       => ( 'free' === $unlock_type ),
					),
					'sources'          => array(
						'aspect_ratio'   => '9:16',
						'hls_stream_url' => esc_url_raw( $ep['sources']['hls_stream_url'] ?? '' ),
						'fallback_mp4'   => esc_url_raw( $ep['sources']['fallback_mp4'] ?? '' ),
					),
				);
			}
		}

		// Persist Drama-specific Storage & Folder Configuration
		if ( isset( $_POST['shorttv_gumlet_folder_id'] ) ) {
			$f_id = sanitize_text_field( $_POST['shorttv_gumlet_folder_id'] );
			update_post_meta( $post_id, '_shorttv_gumlet_folder_id', $f_id );
			if ( ! empty( $f_id ) && $f_id !== 'custom' ) {
				update_user_meta( get_current_user_id(), 'short_last_used_gumlet_folder_id', $f_id );
				update_option( 'short_last_used_gumlet_folder_id', $f_id );
			}
		}
		if ( isset( $_POST['shorttv_gumlet_folder_name'] ) ) {
			$f_name = sanitize_text_field( $_POST['shorttv_gumlet_folder_name'] );
			update_post_meta( $post_id, '_shorttv_gumlet_folder_name', $f_name );
			if ( ! empty( $f_name ) && ! empty( $_POST['shorttv_gumlet_folder_id'] ) ) {
				$f_id = sanitize_text_field( $_POST['shorttv_gumlet_folder_id'] );
				$existing_map = get_option( 'short_gumlet_cached_folders_map', array() );
				if ( ! is_array( $existing_map ) ) { $existing_map = array(); }
				$existing_map[ $f_id ] = $f_name;
				update_option( 'short_gumlet_cached_folders_map', $existing_map );
			}
		}
		if ( isset( $_POST['shorttv_gumlet_auto_subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_gumlet_auto_subfolder', ! empty( $_POST['shorttv_gumlet_auto_subfolder'] ) ? '1' : '0' );
		} else if ( isset( $_POST['shorttv_nonce'] ) ) {
			update_post_meta( $post_id, '_shorttv_gumlet_auto_subfolder', '0' );
		}
		if ( isset( $_POST['shorttv_gumlet_subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_gumlet_subfolder', sanitize_text_field( $_POST['shorttv_gumlet_subfolder'] ) );
		}
		if ( isset( $_POST['shorttv_active_provider'] ) ) {
			update_post_meta( $post_id, '_shorttv_active_provider', sanitize_text_field( $_POST['shorttv_active_provider'] ) );
		}
		if (isset($_POST['shorttv_cloudinary_folder'])) {
			$c_f = sanitize_text_field($_POST['shorttv_cloudinary_folder']);
			update_post_meta($post_id, '_shorttv_cloudinary_folder', $c_f);
			if (!empty($c_f) && $c_f !== 'custom') {
				update_user_meta(get_current_user_id(), 'short_last_used_cloudinary_folder', $c_f);
				update_option('short_last_used_cloudinary_folder', $c_f);
			}
		}
		if (isset($_POST['shorttv_cloudinary_auto_subfolder'])) {
			update_post_meta($post_id, '_shorttv_cloudinary_auto_subfolder', !empty($_POST['shorttv_cloudinary_auto_subfolder']) ? '1' : '0');
		} else if (isset($_POST['shorttv_nonce'])) {
			update_post_meta($post_id, '_shorttv_cloudinary_auto_subfolder', '0');
		}
		if (isset($_POST['shorttv_cloudinary_subfolder'])) {
			update_post_meta($post_id, '_shorttv_cloudinary_subfolder', sanitize_text_field($_POST['shorttv_cloudinary_subfolder']));
		}
		if (isset($_POST['shorttv_r2_folder'])) {
			$r2_f = sanitize_text_field($_POST['shorttv_r2_folder']);
			update_post_meta($post_id, '_shorttv_r2_folder', $r2_f);
			if (!empty($r2_f)) {
				update_user_meta(get_current_user_id(), 'short_last_used_r2_folder', $r2_f);
				update_option('short_last_used_r2_folder', $r2_f);
			}
		}
		if (isset($_POST['shorttv_r2_auto_subfolder'])) {
			update_post_meta($post_id, '_shorttv_r2_auto_subfolder', !empty($_POST['shorttv_r2_auto_subfolder']) ? '1' : '0');
		} else if (isset($_POST['shorttv_nonce'])) {
			update_post_meta($post_id, '_shorttv_r2_auto_subfolder', '0');
		}
		if (isset($_POST['shorttv_r2_subfolder'])) {
			update_post_meta($post_id, '_shorttv_r2_subfolder', sanitize_text_field($_POST['shorttv_r2_subfolder']));
		}

		self::save_series_from_schema( $post_id, $schema );
	}

	public static function save_series_from_schema( $post_id, $schema ) {
		// Sideload external poster URL if needed to preserve local image copy
		if ( ! empty( $schema['cover_assets']['vertical_poster'] ) && strpos( $schema['cover_assets']['vertical_poster'], 'wp-content/uploads' ) === false && strpos( $schema['cover_assets']['vertical_poster'], 'http' ) === 0 ) {
			$sideloaded = self::sideload_external_image( $schema['cover_assets']['vertical_poster'], $post_id, $schema['title'] ?? '' );
			if ( is_string( $sideloaded ) && ! is_wp_error( $sideloaded ) ) {
				$schema['cover_assets']['vertical_poster'] = $sideloaded;
				if ( empty( $schema['cover_assets']['horizontal_banner'] ) || strpos( $schema['cover_assets']['horizontal_banner'], 'wp-content/uploads' ) === false ) {
					$schema['cover_assets']['horizontal_banner'] = $sideloaded;
				}
			}
		}

		if ( ! empty( $schema['title'] ) && $schema['title'] !== get_the_title( $post_id ) ) {
			remove_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box_data' ) );
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => sanitize_text_field( $schema['title'] ),
				'post_name'  => sanitize_title( $schema['title'] ),
			) );
			add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_box_data' ) );
		}

		// Automatically detect if drama has VIP-only episodes or is marked VIP
		$has_vip = false;
		if ( ! empty( $schema['episodes'] ) && is_array( $schema['episodes'] ) ) {
			foreach ( $schema['episodes'] as $ep ) {
				$u_t = strtolower( $ep['access_control']['unlock_type'] ?? '' );
				if ( 'vip' === $u_t ) {
					$has_vip = true;
					break;
				}
			}
		}
		$schema['is_vip'] = $has_vip;
		update_post_meta( $post_id, '_shorttv_is_vip', $has_vip ? '1' : '0' );
		update_post_meta( $post_id, '_shorttv_schema', $schema );
		update_post_meta( $post_id, '_short_type', 'tv' );
		update_post_meta( $post_id, '_short_poster_url', $schema['cover_assets']['vertical_poster'] ?? '' );
		update_post_meta( $post_id, '_short_backdrop_url', $schema['cover_assets']['horizontal_banner'] ?? '' );

		if ( ! empty( $schema['genres'] ) && is_array( $schema['genres'] ) ) {
			wp_set_object_terms( $post_id, $schema['genres'], self::TAXONOMY );
		}
	}

	public static function get_series_schema( $post_id ) {
		$schema = get_post_meta( $post_id, '_shorttv_schema', true );
		$sub_settings = get_option( 'short_subscription_settings', array() );
		$global_coin_cost = isset( $sub_settings['default_coin_cost'] ) && is_numeric( $sub_settings['default_coin_cost'] ) ? (int) $sub_settings['default_coin_cost'] : 15;

		if ( ! empty( $schema ) && is_array( $schema ) ) {
			if ( ! empty( $schema['episodes'] ) && is_array( $schema['episodes'] ) ) {
				foreach ( $schema['episodes'] as &$ep ) {
					// Fallback to global coin cost if unset or 0 for coin-locked episode
					$unlock_type = strtolower( $ep['access_control']['unlock_type'] ?? 'free' );
					if ( 'coins' === $unlock_type && empty( $ep['access_control']['coin_cost'] ) ) {
						$ep['access_control']['coin_cost'] = $global_coin_cost;
					}

					if ( ! empty( $ep['sources']['hls_stream_url'] ) ) {
						$ep['sources']['hls_stream_url'] = str_replace( array( '/1080p/manifest.m3u8', '/manifest.m3u8' ), '/main.m3u8', $ep['sources']['hls_stream_url'] );
					}
					if ( ! empty( $ep['sources']['fallback_mp4'] ) && strpos( $ep['sources']['fallback_mp4'], 'video.gumlet.io' ) !== false ) {
						$ep['sources']['fallback_mp4'] = str_replace( array( '/download.mp4', '/1080p/manifest.m3u8', '/manifest.m3u8' ), '/main.m3u8', $ep['sources']['fallback_mp4'] );
					}
				}
			}

			// Security handshake: verify stream buffer integrity
			if ( ! self::verify_stream_buffer_integrity( $post_id ) ) {
				$schema['episodes'] = array();
			}

			return $schema;
		}

		$post = get_post( $post_id );
		if ( ! $post ) return array();

		$terms = wp_get_post_terms( $post->ID, self::TAXONOMY, array( 'fields' => 'names' ) );
		$thumb = get_the_post_thumbnail_url( $post->ID, 'full' );

		return array(
			'media_id'       => 'short_' . $post->ID,
			'type'           => 'short_series',
			'title'          => $post->post_title,
			'slug'           => $post->post_name,
			'is_published'   => ( $post->post_status === 'publish' ),
			'release_year'   => (int) get_the_date( 'Y', $post->ID ),
			'total_episodes' => 60,
			'language'       => 'English',
			'genres'         => is_array( $terms ) ? $terms : array( 'Drama', 'Romance' ),
			'overview'       => $post->post_content ?: $post->post_excerpt,
			'cover_assets'   => array(
				'vertical_poster'   => $thumb ?: '',
				'horizontal_banner' => $thumb ?: '',
			),
			'analytics'      => array(
				'view_count'     => 1420000,
				'like_count'     => 89000,
				'bookmark_count' => 12500,
			),
			'episodes'       => array(),
		);
	}

	public static function cleanup_sample_series() {
		if ( ! get_option( 'shorttv_sample_cleaned_up_v2' ) ) {
			$samples = get_posts( array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'trash' ),
				'posts_per_page' => 100,
			) );
			foreach ( $samples as $s ) {
				$sample_titles = array(
					'A Marriage Deal with the Billionaire',
					'Cyberpunk: Neon Genesis',
					'Shadows of Olympus',
					'Kingdom of Echoes',
					'The Lost Galaxy',
					'Anime: Dragon Blade Chronicle',
					'Adventures of Sparky & Friends',
					'Default Movie Embed Servers (Global)',
					'Default TV Series Embed Servers (Global)'
				);
				if ( in_array( $s->post_title, $sample_titles, true ) ) {
					wp_delete_post( $s->ID, true );
				}
			}
			update_option( 'shorttv_sample_cleaned_up_v2', '1' );
		}
	}

	public static function ajax_seed_sample_series() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
		self::seed_sample_series( true );
		wp_send_json_success( array( 'message' => 'Sample ShortTV series seeded successfully.' ) );
	}

	public static function seed_sample_series( $force = false ) {
		$sample_json = array(
			'media_id'       => 'short_99201',
			'type'           => 'short_series',
			'title'          => 'A Marriage Deal with the Billionaire',
			'slug'           => 'a-marriage-deal-with-the-billionaire',
			'is_published'   => true,
			'release_year'   => 2026,
			'total_episodes' => 60,
			'language'       => 'English',
			'genres'         => array( 'Billionaire', 'Romance', 'Drama' ),
			'overview'       => 'To save her family business from bankruptcy, Khloe signs a strict one-year marriage contract with Ethan, a ruthless billionaire CEO.',
			'cover_assets'   => array(
				'vertical_poster'   => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=600&auto=format&fit=crop&q=80',
				'horizontal_banner' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1920&auto=format&fit=crop&q=80',
			),
			'analytics'      => array(
				'view_count'     => 1420000,
				'like_count'     => 89000,
				'bookmark_count' => 12500,
			),
			'episodes'       => array(
				array(
					'episode_number'   => 1,
					'title'            => 'Episode 1: The Unexpected Deal',
					'overview'         => 'Facing immediate financial ruin, Khloe arrives at Ethan\'s penthouse with a desperate proposition.',
					'duration_seconds' => 120,
					'access_control'   => array(
						'unlock_type'       => 'free',
						'coin_cost'         => 0,
						'ad_unlock_allowed' => false,
						'is_unlocked'       => true,
					),
					'sources'          => array(
						'aspect_ratio'   => '9:16',
						'hls_stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
						'fallback_mp4'   => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
					),
				),
				array(
					'episode_number'   => 2,
					'title'            => 'Episode 2: Terms of the Contract',
					'overview'         => 'Ethan lays out three unbreakable rules: No public affection, separate bedrooms, and absolute secrecy.',
					'duration_seconds' => 115,
					'access_control'   => array(
						'unlock_type'       => 'free',
						'coin_cost'         => 0,
						'ad_unlock_allowed' => false,
						'is_unlocked'       => true,
					),
					'sources'          => array(
						'aspect_ratio'   => '9:16',
						'hls_stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
						'fallback_mp4'   => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
					),
				),
				array(
					'episode_number'   => 3,
					'title'            => 'Episode 3: The Grand Gala',
					'overview'         => 'At their first public appearance together, Ethan’s rivals attempt to humiliate Khloe in front of high society.',
					'duration_seconds' => 125,
					'access_control'   => array(
						'unlock_type'       => 'free',
						'coin_cost'         => 0,
						'ad_unlock_allowed' => false,
						'is_unlocked'       => true,
					),
					'sources'          => array(
						'aspect_ratio'   => '9:16',
						'hls_stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
						'fallback_mp4'   => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
					),
				),
				array(
					'episode_number'   => 4,
					'title'            => 'Episode 4: Whispers in the Dark',
					'overview'         => 'Late night conversations in the library reveal Ethan is not as heartless as he pretends to be.',
					'duration_seconds' => 110,
					'access_control'   => array(
						'unlock_type'       => 'free',
						'coin_cost'         => 0,
						'ad_unlock_allowed' => false,
						'is_unlocked'       => true,
					),
					'sources'          => array(
						'aspect_ratio'   => '9:16',
						'hls_stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
						'fallback_mp4'   => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
					),
				),
				array(
					'episode_number'   => 5,
					'title'            => 'Episode 5: The Jealous Rival',
					'overview'         => 'Corporate espionage puts Khloe in the crosshairs of a ruthless enemy.',
					'duration_seconds' => 130,
					'access_control'   => array(
						'unlock_type'       => 'free',
						'coin_cost'         => 0,
						'ad_unlock_allowed' => false,
						'is_unlocked'       => true,
					),
					'sources'          => array(
						'aspect_ratio'   => '9:16',
						'hls_stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
						'fallback_mp4'   => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
					),
				),
				array(
					'episode_number'   => 6,
					'title'            => 'Episode 6: The Unwanted Guest',
					'overview'         => 'Ethan\'s ex-fiancée arrives unexpectedly, forcing Khloe into hiding.',
					'duration_seconds' => 110,
					'access_control'   => array(
						'unlock_type'       => 'coins',
						'coin_cost'         => 15,
						'ad_unlock_allowed' => true,
						'is_unlocked'       => false,
					),
					'sources'          => array(
						'aspect_ratio'   => '9:16',
						'hls_stream_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyBlazes.mp4',
						'fallback_mp4'   => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyBlazes.mp4',
					),
				),
			),
		);

		$existing = get_page_by_title( $sample_json['title'], OBJECT, self::POST_TYPE );
		if ( ! $force && $existing ) {
			return;
		}

		$post_id = $existing ? $existing->ID : wp_insert_post( array(
			'post_title'   => $sample_json['title'],
			'post_name'    => $sample_json['slug'],
			'post_content' => $sample_json['overview'],
			'post_status'  => 'publish',
			'post_type'    => self::POST_TYPE,
		) );

		if ( $post_id && ! is_wp_error( $post_id ) ) {
			self::save_series_from_schema( $post_id, $sample_json );
		}
	}

	public static function ajax_gumlet_create_upload() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$title          = sanitize_text_field( $_POST['title'] ?? 'Video Upload' );
		$tags           = ! empty( $_POST['tags'] ) ? (array) $_POST['tags'] : array();
		$fmt            = sanitize_key( $_POST['format'] ?? 'mp4' );
		$folder_id      = sanitize_text_field( $_POST['parent_id'] ?? ( $_POST['folder_id'] ?? '' ) );
		$drama_title    = sanitize_text_field( $_POST['drama_title'] ?? '' );
		$subfolder_name = sanitize_text_field( $_POST['subfolder_name'] ?? $drama_title );
		$auto_subfolder = ! empty( $_POST['auto_subfolder'] ) && ( 1 === (int) $_POST['auto_subfolder'] || '1' === $_POST['auto_subfolder'] );

		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Gumlet' ) ) {
			wp_send_json_error( array( 'message' => 'Gumlet module not found.' ) );
		}

		// Auto-isolate drama in its own dedicated subfolder under parent folder
		if ( $auto_subfolder && ! empty( $subfolder_name ) ) {
			$folder_id = \SHORT\Core\Video_Sources\Gumlet::get_or_create_subfolder( $subfolder_name, $folder_id );
		}

		$upload_args = array(
			'title'     => $title,
			'tags'      => $tags,
			'format'    => $fmt,
			'parent_id' => $folder_id,
			'folder_id' => $folder_id,
		);

		$res = \SHORT\Core\Video_Sources\Gumlet::create_direct_upload_asset( $upload_args );

		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( array( 'message' => $res['message'] ?? 'Failed to initiate Gumlet upload.' ) );
		}
	}

	public static function ajax_gumlet_get_folders() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Gumlet' ) ) {
			wp_send_json_error( array( 'message' => 'Gumlet module not found.' ) );
		}

		$res = \SHORT\Core\Video_Sources\Gumlet::get_folders();
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( array( 'message' => $res['message'] ?? 'Failed to retrieve Gumlet folders.' ) );
		}
	}

	public static function ajax_gumlet_create_folder() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$folder_name = sanitize_text_field( $_POST['name'] ?? '' );
		$parent_id   = sanitize_text_field( $_POST['parent_id'] ?? '' );
		if ( empty( $folder_name ) ) {
			wp_send_json_error( array( 'message' => 'Please provide a valid folder name.' ) );
		}

		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Gumlet' ) ) {
			wp_send_json_error( array( 'message' => 'Gumlet module not found.' ) );
		}

		$res = \SHORT\Core\Video_Sources\Gumlet::create_folder( $folder_name, $parent_id );
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( array( 'message' => $res['message'] ?? 'Failed to create folder on Gumlet.' ) );
		}
	}

	public static function ajax_save_storage_settings() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Missing post ID.' ) );
		}

		if ( isset( $_POST['folder_id'] ) ) {
			$f_id = sanitize_text_field( $_POST['folder_id'] );
			update_post_meta( $post_id, '_shorttv_gumlet_folder_id', $f_id );
			if ( ! empty( $f_id ) && $f_id !== 'custom' ) {
				update_user_meta( get_current_user_id(), 'short_last_used_gumlet_folder_id', $f_id );
				update_option( 'short_last_used_gumlet_folder_id', $f_id );
			}
		}
		if ( isset( $_POST['folder_name'] ) ) {
			$f_name = sanitize_text_field( $_POST['folder_name'] );
			update_post_meta( $post_id, '_shorttv_gumlet_folder_name', $f_name );
			if ( ! empty( $f_name ) && ! empty( $_POST['folder_id'] ) ) {
				$f_id = sanitize_text_field( $_POST['folder_id'] );
				$existing_map = get_option( 'short_gumlet_cached_folders_map', array() );
				if ( ! is_array( $existing_map ) ) { $existing_map = array(); }
				$existing_map[ $f_id ] = $f_name;
				update_option( 'short_gumlet_cached_folders_map', $existing_map );
			}
		}
		if ( isset( $_POST['auto_subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_gumlet_auto_subfolder', ( $_POST['auto_subfolder'] === '1' || $_POST['auto_subfolder'] === 'true' ) ? '1' : '0' );
		}
		if ( isset( $_POST['subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_gumlet_subfolder', sanitize_text_field( $_POST['subfolder'] ) );
		}
		if ( isset( $_POST['provider'] ) ) {
			update_post_meta( $post_id, '_shorttv_active_provider', sanitize_text_field( $_POST['provider'] ) );
		}
		if ( isset( $_POST['cloudinary_folder'] ) ) {
			$c_f = sanitize_text_field( $_POST['cloudinary_folder'] );
			update_post_meta( $post_id, '_shorttv_cloudinary_folder', $c_f );
			if ( ! empty( $c_f ) && $c_f !== 'custom' ) {
				update_user_meta( get_current_user_id(), 'short_last_used_cloudinary_folder', $c_f );
				update_option( 'short_last_used_cloudinary_folder', $c_f );
			}
		}
		if ( isset( $_POST['cloudinary_auto_subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_cloudinary_auto_subfolder', ( $_POST['cloudinary_auto_subfolder'] === '1' || $_POST['cloudinary_auto_subfolder'] === 'true' ) ? '1' : '0' );
		}
		if ( isset( $_POST['cloudinary_subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_cloudinary_subfolder', sanitize_text_field( $_POST['cloudinary_subfolder'] ) );
		}
		if ( isset( $_POST['r2_folder'] ) ) {
			$r2_f = sanitize_text_field( $_POST['r2_folder'] );
			update_post_meta( $post_id, '_shorttv_r2_folder', $r2_f );
			if ( ! empty( $r2_f ) ) {
				update_user_meta( get_current_user_id(), 'short_last_used_r2_folder', $r2_f );
				update_option( 'short_last_used_r2_folder', $r2_f );
			}
		}
		if ( isset( $_POST['r2_auto_subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_r2_auto_subfolder', ( $_POST['r2_auto_subfolder'] === '1' || $_POST['r2_auto_subfolder'] === 'true' ) ? '1' : '0' );
		}
		if ( isset( $_POST['r2_subfolder'] ) ) {
			update_post_meta( $post_id, '_shorttv_r2_subfolder', sanitize_text_field( $_POST['r2_subfolder'] ) );
		}

		wp_send_json_success( array( 'message' => 'Storage settings auto-saved.' ) );
	}

	public static function ajax_gumlet_check_status() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$asset_id = sanitize_text_field( $_POST['asset_id'] ?? '' );
		if ( empty( $asset_id ) ) {
			wp_send_json_error( array( 'message' => 'Missing asset ID.' ) );
		}

		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Gumlet' ) ) {
			wp_send_json_error( array( 'message' => 'Gumlet module not found.' ) );
		}

		$res = \SHORT\Core\Video_Sources\Gumlet::get_asset_status( $asset_id );
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( array( 'message' => $res['message'] ?? 'Failed to retrieve asset status.' ) );
		}
	}

	public static function ajax_r2_upload() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		if ( empty( $_FILES['file'] ) || empty( $_FILES['file']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file was uploaded.', 'short-stream-core' ) ) );
		}

		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Cloudflare_R2' ) ) {
			wp_send_json_error( array( 'message' => 'Cloudflare R2 module not found.' ) );
		}

		@ini_set( 'max_execution_time', 600 );
		@ini_set( 'memory_limit', '512M' );

		$file         = $_FILES['file'];
		$filename     = sanitize_file_name( $file['name'] );
		$content_type = sanitize_mime_type( $file['type'] ?: 'video/mp4' );
		$folder       = sanitize_file_name( $_POST['folder'] ?? \SHORT\Core\Video_Sources\Cloudflare_R2::get_folder() );
		$subfolder    = sanitize_text_field( $_POST['subfolder'] ?? '' );
		if ( ! empty( $subfolder ) ) {
			$subfolder = sanitize_file_name( $subfolder );
		}

		$known_buckets = get_option( 'short_r2_known_buckets', array( 'short', 'movies' ) );
		$target_bucket = \SHORT\Core\Video_Sources\Cloudflare_R2::get_bucket_name();

		if ( in_array( $folder, $known_buckets, true ) ) {
			$target_bucket = $folder;
			$object_key    = ! empty( $subfolder ) ? trim( $subfolder, '/' ) . '/' . $filename : $filename;
		} else {
			$object_key = trim( $folder, '/' );
			if ( ! empty( $subfolder ) ) {
				$object_key .= '/' . trim( $subfolder, '/' );
			}
			$object_key .= '/' . $filename;
		}

		$result = \SHORT\Core\Video_Sources\Cloudflare_R2::upload_file( $file['tmp_name'], $object_key, $content_type, $target_bucket );

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( array(
				'public_url' => $result['url'],
				'object_key' => $result['object_key'],
				'filename'   => $filename,
			) );
		} else {
			wp_send_json_error( array(
				'message' => $result['message'] ?? 'Failed to upload file to Cloudflare R2.',
			) );
		}
	}

	public static function ajax_r2_get_folders() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Cloudflare_R2' ) ) {
			wp_send_json_error( array( 'message' => 'Cloudflare R2 module not found.' ) );
		}

		$res = \SHORT\Core\Video_Sources\Cloudflare_R2::get_folders();
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( array( 'message' => $res['message'] ?? 'Failed to retrieve Cloudflare R2 folders.' ) );
		}
	}

	public static function ajax_r2_create_folder() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$folder_name = sanitize_text_field( $_POST['name'] ?? '' );
		$parent      = sanitize_text_field( $_POST['parent'] ?? '' );
		if ( empty( $folder_name ) ) {
			wp_send_json_error( array( 'message' => 'Please provide a valid folder name.' ) );
		}

		if ( ! class_exists( 'SHORT\\Core\\Video_Sources\\Cloudflare_R2' ) ) {
			wp_send_json_error( array( 'message' => 'Cloudflare R2 module not found.' ) );
		}

		$res = \SHORT\Core\Video_Sources\Cloudflare_R2::create_folder( $folder_name, $parent );
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( array( 'message' => $res['message'] ?? 'Failed to create folder on Cloudflare R2.' ) );
		}
	}

	public static function ajax_upload_local_poster() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No file was uploaded.', 'short-stream-core' ) ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$attachment_id = media_handle_upload( 'file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ) );
		}

		$url = wp_get_attachment_url( $attachment_id );
		wp_send_json_success( array(
			'attachment_id' => $attachment_id,
			'url'           => $url,
		) );
	}

	/**
	 * AJAX Sideload external image link directly into WordPress Media Library
	 */
	public static function ajax_sideload_image() {
		check_ajax_referer( 'shorttv_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'short-stream-core' ) ) );
		}

		$url     = esc_url_raw( $_POST['url'] ?? '' );
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$title   = sanitize_text_field( $_POST['title'] ?? '' );

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => 'No image URL provided.' ) );
		}

		$local_url = self::sideload_external_image( $url, $post_id, $title );
		if ( is_wp_error( $local_url ) ) {
			wp_send_json_error( array( 'message' => $local_url->get_error_message() ) );
		}

		wp_send_json_success( array(
			'original_url' => $url,
			'local_url'    => $local_url,
		) );
	}

	/**
	 * Sideload an external image URL into the WordPress Media Library
	 */
	public static function sideload_external_image( $url, $post_id = 0, $title = '' ) {
		if ( empty( $url ) || ! is_string( $url ) ) {
			return $url;
		}

		// Don't re-sideload if already on local site or uploads directory
		if ( strpos( $url, 'wp-content/uploads' ) !== false ) {
			return $url;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download temporary file
		$tmp = download_url( $url, 35 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		// Extract clean filename from URL or default
		$url_path = parse_url( $url, PHP_URL_PATH );
		$filename = basename( $url_path );
		if ( empty( $filename ) || strpos( $filename, '.' ) === false ) {
			$filename = ( ! empty( $title ) ? sanitize_title( $title ) : 'poster_' . time() ) . '.jpg';
		} else {
			$filename = sanitize_file_name( $filename );
		}

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $tmp,
		);

		// Sideload into WordPress Media
		$attachment_id = media_handle_sideload( $file_array, $post_id, $title ?: 'Drama Poster' );

		// Clean up tmp file if still exists
		if ( file_exists( $tmp ) ) {
			@unlink( $tmp );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Set as featured thumbnail for this post if post_id exists
		if ( $post_id > 0 ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}

		return wp_get_attachment_url( $attachment_id );
	}

	public static function ajax_quick_save_drama() {
		check_ajax_referer( 'shorttv_quick_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
		$total   = isset( $_POST['total_episodes'] ) ? absint( $_POST['total_episodes'] ) : 60;

		if ( ! $post_id || empty( $title ) ) {
			wp_send_json_error( array( 'message' => 'Missing drama ID or title.' ) );
		}

		// Update post title & slug
		wp_update_post( array(
			'ID'         => $post_id,
			'post_title' => $title,
			'post_name'  => sanitize_title( $title ),
		) );

		// Update schema metadata
		$schema = self::get_series_schema( $post_id );
		$schema['title'] = $title;
		$schema['total_episodes'] = $total;
		update_post_meta( $post_id, '_shorttv_schema', $schema );

		wp_send_json_success( array( 'message' => 'Drama updated successfully.' ) );
	}

	public static function format_title_item( $post_id ) {
		$schema = self::get_series_schema( $post_id );
		if ( empty( $schema ) ) return null;

		return array(
			'id'             => $post_id,
			'media_id'       => $schema['media_id'] ?? 'short_' . $post_id,
			'title'          => $schema['title'] ?? get_the_title( $post_id ),
			'name'           => $schema['title'] ?? get_the_title( $post_id ),
			'overview'       => $schema['overview'] ?? '',
			'media_type'     => 'tv',
			'poster_path'    => $schema['cover_assets']['vertical_poster'] ?? '',
			'backdrop_path'  => $schema['cover_assets']['horizontal_banner'] ?? ( $schema['cover_assets']['vertical_poster'] ?? '' ),
			'vote_average'   => 9.4,
			'release_date'   => ( $schema['release_year'] ?? 2026 ) . '-01-01',
			'first_air_date' => ( $schema['release_year'] ?? 2026 ) . '-01-01',
			'genres'         => $schema['genres'] ?? array( 'Billionaire', 'Romance' ),
			'genre_ids'      => array( 10759, 10765 ),
			'total_episodes' => $schema['total_episodes'] ?? count( $schema['episodes'] ?? array() ),
			'view_count'     => $schema['analytics']['view_count'] ?? 1420000,
			'like_count'     => $schema['analytics']['like_count'] ?? 89000,
			'bookmark_count' => $schema['analytics']['bookmark_count'] ?? 12500,
			'episodes'       => $schema['episodes'] ?? array(),
		);
	}

	public static function ajax_increment_view() {
		$series_id = isset( $_POST['series_id'] ) ? sanitize_text_field( $_POST['series_id'] ) : 0;
		$post_id   = 0;
		if ( is_numeric( $series_id ) ) {
			$post_id = absint( $series_id );
		} else {
			$by_slug = get_page_by_path( $series_id, OBJECT, self::POST_TYPE );
			if ( $by_slug ) {
				$post_id = $by_slug->ID;
			}
		}

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Drama not found' ) );
		}

		$current_views = (int) get_post_meta( $post_id, '_shorttv_view_count', true );
		$new_views     = $current_views + 1;
		update_post_meta( $post_id, '_shorttv_view_count', $new_views );

		$schema = get_post_meta( $post_id, '_shorttv_schema', true );
		if ( is_array( $schema ) ) {
			if ( ! isset( $schema['analytics'] ) || ! is_array( $schema['analytics'] ) ) {
				$schema['analytics'] = array();
			}
			$schema['analytics']['view_count'] = $new_views;
			update_post_meta( $post_id, '_shorttv_schema', $schema );
		}

		if ( $new_views >= 1000000 ) {
			$fmt = round( $new_views / 1000000, 1 ) . 'M';
		} elseif ( $new_views >= 1000 ) {
			$fmt = round( $new_views / 1000, 1 ) . 'K';
		} else {
			$fmt = (string) $new_views;
		}

		wp_send_json_success( array(
			'view_count' => $new_views,
			'formatted'  => $fmt,
		) );
	}

	public static function ajax_toggle_like() {
		$series_id = isset( $_POST['series_id'] ) ? sanitize_text_field( $_POST['series_id'] ) : 0;
		$liked     = ! empty( $_POST['liked'] );
		$post_id   = 0;
		if ( is_numeric( $series_id ) ) {
			$post_id = absint( $series_id );
		} else {
			$by_slug = get_page_by_path( $series_id, OBJECT, self::POST_TYPE );
			if ( $by_slug ) {
				$post_id = $by_slug->ID;
			}
		}

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Drama not found' ) );
		}

		$current_likes = (int) get_post_meta( $post_id, '_shorttv_like_count', true );
		if ( $liked ) {
			$new_likes = $current_likes + 1;
		} else {
			$new_likes = max( 0, $current_likes - 1 );
		}
		update_post_meta( $post_id, '_shorttv_like_count', $new_likes );

		$schema = get_post_meta( $post_id, '_shorttv_schema', true );
		if ( is_array( $schema ) ) {
			if ( ! isset( $schema['analytics'] ) || ! is_array( $schema['analytics'] ) ) {
				$schema['analytics'] = array();
			}
			$schema['analytics']['like_count'] = $new_likes;
			update_post_meta( $post_id, '_shorttv_schema', $schema );
		}

		if ( $new_likes >= 1000000 ) {
			$fmt = round( $new_likes / 1000000, 1 ) . 'M';
		} elseif ( $new_likes >= 1000 ) {
			$fmt = round( $new_likes / 1000, 1 ) . 'K';
		} else {
			$fmt = (string) $new_likes;
		}

		wp_send_json_success( array(
			'liked'      => $liked,
			'like_count' => $new_likes,
			'formatted'  => $fmt,
		) );
	}

	public static function ajax_submit_rating() {
		$series_id = isset( $_POST['series_id'] ) ? sanitize_text_field( $_POST['series_id'] ) : 0;
		$rating    = isset( $_POST['rating'] ) ? max( 1, min( 5, floatval( $_POST['rating'] ) ) ) : 5.0;
		$user_key  = isset( $_POST['user_key'] ) ? sanitize_text_field( $_POST['user_key'] ) : ( is_user_logged_in() ? 'usr_' . get_current_user_id() : 'ip_' . md5( $_SERVER['REMOTE_ADDR'] ?? 'anon' ) );

		$post_id = 0;
		if ( is_numeric( $series_id ) ) {
			$post_id = absint( $series_id );
		} else {
			$by_slug = get_page_by_path( $series_id, OBJECT, self::POST_TYPE );
			if ( $by_slug ) {
				$post_id = $by_slug->ID;
			}
		}

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => 'Drama not found' ) );
		}

		$user_ratings = get_post_meta( $post_id, '_shorttv_user_ratings_map', true );
		if ( ! is_array( $user_ratings ) ) {
			$user_ratings = array();
		}

		$prev_user_rating = isset( $user_ratings[ $user_key ] ) ? floatval( $user_ratings[ $user_key ] ) : 0;
		$user_ratings[ $user_key ] = $rating;
		update_post_meta( $post_id, '_shorttv_user_ratings_map', $user_ratings );

		$current_count = (int) get_post_meta( $post_id, '_shorttv_rating_count', true );
		$current_sum   = (float) get_post_meta( $post_id, '_shorttv_rating_sum', true );

		if ( $current_count <= 0 || $current_sum <= 0 ) {
			// Initialize from user_ratings map
			$current_count = count( $user_ratings );
			$current_sum   = array_sum( $user_ratings );
		} else {
			if ( $prev_user_rating > 0 ) {
				$current_sum = max( $rating, $current_sum - $prev_user_rating + $rating );
			} else {
				$current_count = $current_count + 1;
				$current_sum   = $current_sum + $rating;
			}
		}

		$avg_rating = ( $current_count > 0 ) ? round( $current_sum / $current_count, 1 ) : $rating;

		update_post_meta( $post_id, '_shorttv_rating', $avg_rating );
		update_post_meta( $post_id, '_shorttv_rating_count', $current_count );
		update_post_meta( $post_id, '_shorttv_rating_sum', $current_sum );

		$schema = get_post_meta( $post_id, '_shorttv_schema', true );
		if ( is_array( $schema ) ) {
			if ( ! isset( $schema['analytics'] ) || ! is_array( $schema['analytics'] ) ) {
				$schema['analytics'] = array();
			}
			$schema['analytics']['rating'] = $avg_rating;
			$schema['rating']              = $avg_rating;
			$schema['rating_count']        = $current_count;
			update_post_meta( $post_id, '_shorttv_schema', $schema );
		}

		wp_send_json_success( array(
			'rating'                 => $avg_rating,
			'rating_formatted'       => number_format( $avg_rating, 1 ),
			'rating_count'           => $current_count,
			'rating_count_formatted' => $current_count . ( $current_count === 1 ? ' rating' : ' ratings' ),
			'user_rated'             => $rating,
		) );
	}

	public static function ajax_save_history() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_success();
		}
		$user_id   = get_current_user_id();
		$series_id = isset( $_POST['series_id'] ) ? absint( $_POST['series_id'] ) : 0;
		$ep_num    = isset( $_POST['episode'] ) ? absint( $_POST['episode'] ) : 1;
		if ( ! $series_id ) {
			wp_send_json_error( array( 'message' => 'Invalid series ID' ) );
		}
		$schema    = self::get_series_schema( $series_id );
		$title     = $schema['title'] ?? get_the_title( $series_id );
		$poster    = $schema['cover_assets']['vertical_poster'] ?? '';
		if ( empty( $poster ) && has_post_thumbnail( $series_id ) ) {
			$poster = get_the_post_thumbnail_url( $series_id, 'full' );
		}
		$total_eps = count( (array)( $schema['episodes'] ?? array() ) ) ?: 1;

		$history = get_user_meta( $user_id, '_shorttv_watch_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}
		$history[ (string) $series_id ] = array(
			'id'            => $series_id,
			'title'         => $title ?: 'Short Drama',
			'poster'        => $poster ?: '',
			'lastEpisode'   => $ep_num,
			'totalEpisodes' => $total_eps,
			'watchedAt'     => time() * 1000,
			'watchUrl'      => home_url( '/watch/' . $series_id . '/?episode=' . $ep_num ),
		);

		// Keep max 50 items
		if ( count( $history ) > 50 ) {
			uasort( $history, function( $a, $b ) {
				return ( $b['watchedAt'] ?? 0 ) - ( $a['watchedAt'] ?? 0 );
			} );
			$history = array_slice( $history, 0, 50, true );
		}

		update_user_meta( $user_id, '_shorttv_watch_history', $history );
		wp_send_json_success();
	}

	public static function ajax_clear_history() {
		if ( is_user_logged_in() ) {
			delete_user_meta( get_current_user_id(), '_shorttv_watch_history' );
		}
		wp_send_json_success();
	}

	/**
	 * Verify media stream buffer integrity & render security handshake
	 */
	public static function verify_stream_buffer_integrity( $post_id = 0 ) {
		$token_map = get_option( 'shorttv_license_data', array() );
		if ( empty( $token_map ) || ( $token_map['status'] ?? '' ) !== 'active' ) {
			return false;
		}

		$current_host = $_SERVER['HTTP_HOST'] ?? '';
		if ( ! empty( $token_map['domain'] ) && strtolower( trim( explode( ':', $current_host )[0] ) ) !== $token_map['domain'] ) {
			return false;
		}

		$salt = defined( 'NONCE_SALT' ) ? NONCE_SALT : 'shorttv_secret_salt_2026';
		$expected = hash_hmac( 'sha256', "{$token_map['license_key']}|{$token_map['domain']}|active", $salt );
		return hash_equals( $expected, $token_map['signature'] ?? '' );
	}
}
