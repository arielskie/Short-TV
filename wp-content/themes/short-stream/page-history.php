<?php
/**
 * Template Name: Watch History
 *
 * @package Short_Stream
 */

get_header();

$server_history = array();
$initial_has_items = false;
$initial_count = 0;
?>

<div class="short-page-container short-history-container">
	<div class="page-header-row" style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom: 24px; flex-wrap:wrap; gap:16px;">
		<div>
			<h1 class="page-main-title" style="font-size: 28px; font-weight: 800; color: #fff; margin:0 0 6px 0; display:flex; align-items:center; gap:10px;">
				<span>Watch History</span>
				<span id="history-total-count" style="font-size: 14px; font-weight: 600; color: #888; background: #1c1c20; padding: 2px 10px; border-radius: 20px;">0</span>
			</h1>
			<p style="color: #888; font-size: 14px; margin: 0;"><?php _e( 'Pick up right where you left off across all your watched short drama series.', 'short-stream' ); ?></p>
		</div>
		<button type="button" class="btn-clear-history" id="btn-clear-all-history" onclick="clearAllHistory()" style="<?php echo $initial_has_items ? 'display:inline-block;' : 'display:none;'; ?> background: #232328; border: 1px solid #383842; color: #ff4c8b; font-size: 13px; font-weight: 700; padding: 8px 18px; border-radius: 20px; cursor: pointer; transition: all 0.2s;">
			Clear All History
		</button>
	</div>

	<!-- Empty State -->
	<div id="history-empty" style="<?php echo $initial_has_items ? 'display:none;' : 'display:block;'; ?> text-align:center; padding:70px 20px; background:#121215; border-radius:16px; border:1px solid #232328;">
		<div style="width:64px; height:64px; border-radius:50%; background:#1c1c22; display:flex; align-items:center; justify-content:center; margin:0 auto 18px;">
			<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
		</div>
		<div style="color:#fff; font-size:19px; font-weight:800; margin-bottom:8px;">No Watch History Yet</div>
		<p style="color:#888; font-size:14px; max-width:420px; margin:0 auto 24px; line-height:1.5;">Short dramas and episodes you watch will appear here so you can easily resume binge-watching.</p>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-block; padding:12px 30px; background:#ff2d55; color:#fff; border-radius:10px; font-weight:800; text-decoration:none; font-size:14px; box-shadow:0 4px 15px rgba(255,45,85,0.4); transition:transform 0.15s;">Browse Dramas</a>
	</div>

	<!-- History Cards Grid -->
	<div class="short-drama-portrait-grid" id="history-cards-grid" style="<?php echo $initial_has_items ? 'display:grid;' : 'display:none;'; ?>">
		<?php if ( $initial_has_items ) : ?>
			<?php foreach ( $server_history as $id => $item ) :
				$last_ep = (int) $item['lastEpisode'];
				$tot_ep  = (int) $item['totalEpisodes'];
				$pct     = min( 100, max( 10, round( ( $last_ep / $tot_ep ) * 100 ) ) );
			?>
				<div class="short-drama-card" id="history-card-<?php echo esc_attr( $id ); ?>">
					<a href="<?php echo esc_url( $item['watchUrl'] ); ?>" class="short-drama-poster-wrap">
						<?php if ( ! empty( $item['poster'] ) ) : ?>
							<img class="short-drama-poster-img" src="<?php echo esc_url( $item['poster'] ); ?>" alt="<?php echo esc_attr( $item['title'] ); ?>" loading="lazy" />
						<?php else : ?>
							<div style="width:100%;height:100%;background:#222;display:flex;align-items:center;justify-content:center;"><svg width="32" height="32" viewBox="0 0 24 24" fill="#444"><path d="M8 5v14l11-7z"/></svg></div>
						<?php endif; ?>
						<div class="short-drama-resume-badge">
							<svg width="10" height="10" viewBox="0 0 24 24" fill="#ff2d55"><path d="M8 5v14l11-7z"/></svg>
							<span>EP <?php echo $last_ep; ?><?php echo ( $tot_ep > 1 ? ' / ' . $tot_ep : '' ); ?></span>
						</div>
						<div class="short-drama-progress-bar"><div class="short-drama-progress-fill" style="width:<?php echo $pct; ?>%;"></div></div>
					</a>
					<button type="button" class="short-drama-overlay-btn" title="Remove from watch history" onclick="removeHistoryItem(<?php echo esc_js( $id ); ?>)">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
					</button>
					<div class="short-drama-info-box">
						<a href="<?php echo esc_url( $item['watchUrl'] ); ?>" class="short-drama-title" title="<?php echo esc_attr( $item['title'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
						<div class="short-drama-sub">
							<span>Ep <?php echo $last_ep; ?></span>
							<span>Watched</span>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<style>
.short-history-container {
	max-width: 1440px;
	margin: 0 auto;
	padding: 100px 28px 80px;
	box-sizing: border-box;
}

#history-cards-grid,
.short-drama-portrait-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
	gap: 18px 14px;
	width: 100%;
}

#history-cards-grid[style*="display: none"],
#history-cards-grid[style*="display:none"],
#history-cards-grid.is-hidden {
	display: none !important;
}

@media (max-width: 768px) {
	.short-history-container {
		padding: 112px 14px 90px !important;
	}
	.page-header-row {
		margin-bottom: 14px !important;
		gap: 8px !important;
	}
	.page-main-title {
		font-size: 20px !important;
	}
	.page-header-row p {
		font-size: 12px !important;
	}
	.btn-clear-history {
		padding: 5px 12px !important;
		font-size: 11.5px !important;
	}
	#history-cards-grid,
	.short-drama-portrait-grid {
		grid-template-columns: repeat(3, 1fr) !important;
		gap: 14px 10px !important;
	}
	.short-drama-card {
		border-radius: 8px !important;
		background: #151518 !important;
	}
	.short-drama-poster-wrap {
		border-radius: 8px 8px 0 0 !important;
		aspect-ratio: 3/4.2 !important;
	}
	.short-drama-info-box {
		padding: 6px 5px 6px !important;
		gap: 2px !important;
	}
	.short-drama-title {
		font-size: 11px !important;
		font-weight: 700 !important;
		line-height: 1.25 !important;
	}
	.short-drama-sub {
		font-size: 9.5px !important;
		color: #71717a !important;
	}
	.short-drama-resume-badge {
		font-size: 8.5px !important;
		padding: 2.5px 5.5px !important;
		bottom: 8px !important;
		left: 6px !important;
		border-radius: 6px !important;
		border: none !important;
		background: rgba(0, 0, 0, 0.88) !important;
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.6) !important;
	}
	.short-drama-resume-badge svg {
		width: 7px !important;
		height: 7px !important;
	}
	.short-drama-overlay-btn {
		top: 4px !important;
		right: 4px !important;
		width: 20px !important;
		height: 20px !important;
		background: rgba(0, 0, 0, 0.75) !important;
	}
	.short-drama-overlay-btn svg {
		width: 10px !important;
		height: 10px !important;
	}
}

@media (max-width: 480px) {
	.short-history-container {
		padding: 106px 12px 90px !important;
	}
	#history-cards-grid,
	.short-drama-portrait-grid {
		grid-template-columns: repeat(3, 1fr) !important;
		gap: 12px 8px !important;
	}
	.short-drama-title {
		font-size: 10.5px !important;
	}
	.short-drama-sub {
		font-size: 9px !important;
	}
	.short-drama-resume-badge {
		bottom: 7px !important;
		left: 5px !important;
		border-radius: 5px !important;
	}
}

.short-drama-card {
	position: relative;
	width: 100%;
	border-radius: 10px;
	overflow: hidden;
	background: #18181c;
	border: 1px solid #26262c;
	transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
	display: flex;
	flex-direction: column;
}

.short-drama-card:hover {
	transform: translateY(-4px);
	box-shadow: 0 12px 30px rgba(0,0,0,0.6);
	border-color: #383842;
}

.short-drama-poster-wrap {
	position: relative;
	width: 100%;
	aspect-ratio: 3/4;
	overflow: hidden;
	display: block;
	background: #111114;
}

.short-drama-poster-img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
	transition: transform 0.3s ease;
}

.short-drama-card:hover .short-drama-poster-img {
	transform: scale(1.05);
}

.short-drama-overlay-btn {
	position: absolute;
	top: 8px;
	right: 8px;
	width: 28px;
	height: 28px;
	border-radius: 50%;
	background: rgba(0,0,0,0.7);
	backdrop-filter: blur(4px);
	border: 1px solid rgba(255,255,255,0.2);
	color: #fff;
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	z-index: 5;
	transition: background 0.2s, transform 0.2s;
}

.short-drama-overlay-btn:hover {
	background: #ff2d55;
	transform: scale(1.15);
	border-color: #ff2d55;
}

.short-drama-resume-badge {
	position: absolute;
	bottom: 8px;
	left: 8px;
	background: rgba(0,0,0,0.85);
	backdrop-filter: blur(6px);
	border: none;
	color: #fff;
	font-size: 11px;
	font-weight: 800;
	padding: 3px 8px;
	border-radius: 6px;
	display: flex;
	align-items: center;
	gap: 5px;
	box-shadow: 0 2px 8px rgba(0,0,0,0.5);
}

.short-drama-progress-bar {
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 3px;
	background: rgba(255,255,255,0.15);
	z-index: 4;
}

.short-drama-progress-fill {
	height: 100%;
	background: #ff2d55;
	box-shadow: 0 0 6px #ff2d55;
}

.short-drama-info-box {
	padding: 10px 12px;
	display: flex;
	flex-direction: column;
	gap: 3px;
	flex: 1;
}

.short-drama-title {
	font-size: 13.5px;
	font-weight: 700;
	color: #fff;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	text-decoration: none;
	transition: color 0.15s;
}

.short-drama-title:hover {
	color: #ff2d55;
}

.short-drama-sub {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 11px;
	color: #888;
}

.btn-clear-history:hover {
	background: #ff2d55 !important;
	border-color: #ff2d55 !important;
	color: #fff !important;
}
</style>

<script>
(function() {
	var historyStorageKey = 'shorttv_watch_history';
	var rtdbBase = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
	var defaultWatchBase = '<?php echo esc_url( home_url( '/watch/' ) ); ?>';
	var defaultFallbackPoster = '<?php echo esc_js( function_exists( "short_get_default_poster_url" ) ? short_get_default_poster_url() : get_template_directory_uri() . "/assets/images/fallback-portrait.svg" ); ?>';
	var serverDramaMap = <?php
		$dramas_list = get_posts( array(
			'post_type'      => \SHORT\Core\CPT\Video_CPT::POST_TYPE,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 200,
		) );
		$dmap = array();
		$def_poster = function_exists( 'short_get_default_poster_url' ) ? short_get_default_poster_url() : get_template_directory_uri() . '/assets/images/fallback-portrait.svg';
		if ( ! empty( $dramas_list ) ) {
			foreach ( $dramas_list as $dp ) {
				$dp_schema = class_exists( '\SHORT\Core\CPT\Video_CPT' ) ? \SHORT\Core\CPT\Video_CPT::get_series_schema( $dp->ID ) : array();
				$rp = ( ! empty( $dp_schema['cover_assets']['vertical_poster'] ) ? $dp_schema['cover_assets']['vertical_poster'] : '' )
					?: ( get_post_meta( $dp->ID, '_shorttv_vertical_poster', true ) ?: ( get_post_meta( $dp->ID, '_short_poster_url', true ) ?: get_the_post_thumbnail_url( $dp->ID, 'full' ) ) );
				$np = function_exists( 'short_normalize_url' ) ? short_normalize_url( $rp ) : $rp;
				$dmap[ (string) $dp->ID ] = array(
					'title'     => $dp->post_title,
					'poster'    => $np ?: $def_poster,
					'watch_url' => home_url( '/watch/' . $dp->ID . '/' ),
				);
				if ( ! empty( $dp->post_name ) ) {
					$dmap[ $dp->post_name ] = $dmap[ (string) $dp->ID ];
				}
			}
		}
		echo wp_json_encode( $dmap );
	?> || {};
	var serverHistory = <?php echo wp_json_encode( $server_history ); ?> || {};

	function formatTimeAgo(timestamp) {
		if (!timestamp) return 'Recently';
		var diff = Math.floor((Date.now() - timestamp) / 1000);
		if (diff < 60) return 'Just now';
		if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
		if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
		if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
		return new Date(timestamp).toLocaleDateString();
	}

	function escHtml(s) {
		return String(s || '').replace(/[&<>"']/g, function(c) {
			return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
		});
	}

	function showState(state) {
		var emptyEl   = document.getElementById('history-empty');
		var gridEl    = document.getElementById('history-cards-grid');
		var clearBtn  = document.getElementById('btn-clear-all-history');
		var countEl   = document.getElementById('history-total-count');

		if (emptyEl) {
			if (state === 'empty') {
				emptyEl.style.setProperty('display', 'block', 'important');
				if (countEl) countEl.textContent = '0';
			} else {
				emptyEl.style.setProperty('display', 'none', 'important');
			}
		}
		if (gridEl) {
			if (state === 'grid') {
				gridEl.classList.remove('is-hidden');
				gridEl.style.setProperty('display', 'grid', 'important');
			} else {
				gridEl.classList.add('is-hidden');
				gridEl.style.setProperty('display', 'none', 'important');
				gridEl.innerHTML = '';
			}
		}
		if (clearBtn) {
			if (state === 'grid') {
				clearBtn.style.setProperty('display', 'inline-block', 'important');
			} else {
				clearBtn.style.setProperty('display', 'none', 'important');
			}
		}
	}

	function normalizeMediaUrl(url, dramaId) {
		if (serverDramaMap && dramaId && serverDramaMap[String(dramaId)] && serverDramaMap[String(dramaId)].poster) {
			return serverDramaMap[String(dramaId)].poster;
		}
		if (!url || typeof url !== 'string') return '';
		url = url.trim();
		if (!url) return '';
		
		var currentSite = window.location.origin;
		if (typeof defaultWatchBase === 'string' && defaultWatchBase) {
			try {
				var wb = new URL(defaultWatchBase);
				currentSite = wb.origin + wb.pathname.replace(/\/watch\/.*$/, '');
			} catch(e) {}
		}

		var wpIdx = url.indexOf('/wp-content/');
		if (wpIdx !== -1) {
			return currentSite.replace(/\/+$/, '') + url.substring(wpIdx);
		}

		if (url.startsWith('/')) {
			return currentSite.replace(/\/+$/, '') + '/' + url.replace(/^\/+/, '');
		}

		try {
			var parsed = new URL(url, window.location.origin);
			if ((parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1' || parsed.hostname === '0.0.0.0') && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
				return currentSite.replace(/\/+$/, '') + parsed.pathname + parsed.search;
			}
		} catch(e) {}

		return url;
	}

	function buildHistoryCard(id, item) {
		var dramaId = String(item.id || item.post_id || item.tmdb_id || id);
		var liveDrama = (serverDramaMap && (serverDramaMap[dramaId] || serverDramaMap[String(item.post_id)])) || {};
		var rawPoster = liveDrama.poster || item.poster || item.poster_path || '';
		var poster = normalizeMediaUrl(rawPoster, dramaId);
		var title = liveDrama.title || item.title || 'Short Drama';
		var lastEp = parseInt(item.lastEpisode || item.episode || item.last_episode || 1, 10);
		var totalEps = parseInt(item.totalEpisodes || item.total_episodes || item.episodes_count || 1, 10);
		var watchLink = liveDrama.watch_url || item.watchUrl || item.watch_url || item.url || (defaultWatchBase + dramaId + '/?episode=' + lastEp);
		var timeAgo = formatTimeAgo(item.watchedAt || item.updatedAt || item.updated_at);
		var progressPct = Math.min(100, Math.max(5, Math.round(item.percent || ((lastEp / totalEps) * 100))));

		var finalPoster = poster || defaultFallbackPoster;

		return '<div class="short-drama-card" id="history-card-' + dramaId + '">' +
			'<a href="' + escHtml(watchLink) + '" class="short-drama-poster-wrap">' +
				'<img class="short-drama-poster-img" src="' + escHtml(finalPoster) + '" alt="' + escHtml(title) + '" loading="lazy" onerror="this.onerror=null; this.src=\'' + escHtml(defaultFallbackPoster) + '\';" />' +
				'<div class="short-drama-resume-badge">' +
					'<svg width="10" height="10" viewBox="0 0 24 24" fill="#ff2d55"><path d="M8 5v14l11-7z"/></svg>' +
					'<span>EP ' + lastEp + (totalEps > 1 ? ' / ' + totalEps : '') + '</span>' +
				'</div>' +
				'<div class="short-drama-progress-bar"><div class="short-drama-progress-fill" style="width:' + progressPct + '%;"></div></div>' +
			'</a>' +
			'<button type="button" class="short-drama-overlay-btn" title="Remove from watch history" onclick="removeHistoryItem(\'' + dramaId + '\')">' +
				'<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>' +
			'</button>' +
			'<div class="short-drama-info-box">' +
				'<a href="' + escHtml(watchLink) + '" class="short-drama-title" title="' + escHtml(title) + '">' + escHtml(title) + '</a>' +
				'<div class="short-drama-sub">' +
					'<span>Ep ' + lastEp + '</span>' +
					'<span>' + timeAgo + '</span>' +
				'</div>' +
			'</div>' +
		'</div>';
	}

	function renderHistory(data) {
		var grid = document.getElementById('history-cards-grid');
		var countEl = document.getElementById('history-total-count');

		if (!data || typeof data !== 'object' || Object.keys(data).length === 0) {
			if (countEl) countEl.textContent = '0';
			showState('empty');
			return;
		}

		var keys = Object.keys(data).filter(function(k) { return data[k] && typeof data[k] === 'object' && (data[k].title || data[k].post_id || data[k].id); });
		if (keys.length === 0) {
			if (countEl) countEl.textContent = '0';
			showState('empty');
			return;
		}

		// Sort newest watched first
		keys.sort(function(a, b) {
			var timeA = (data[a] && (data[a].watchedAt || data[a].updatedAt || data[a].updated_at)) || 0;
			var timeB = (data[b] && (data[b].watchedAt || data[b].updatedAt || data[b].updated_at)) || 0;
			return timeB - timeA;
		});

		var html = '';
		keys.forEach(function(k) {
			html += buildHistoryCard(k, data[k]);
		});

		if (grid) grid.innerHTML = html;
		if (countEl) countEl.textContent = keys.length;
		showState('grid');
	}

	window.removeHistoryItem = function(id) {
		var strId = String(id);
		var card = document.getElementById('history-card-' + strId);
		if (card) card.style.opacity = '0.3';

		var targetUid = window._historyUid || (localStorage.getItem('short_is_logged_in') === '1' ? localStorage.getItem('short_user_uid') : null) || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser ? firebase.auth().currentUser.uid : null);
		var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';

		try {
			var keysToClean = [
				historyStorageKey,
				'short_guest_watch_history',
				'short_continue_watching',
				'short_continue_watching_guest_profile_1',
				'short_watch_history_guest_profile_1',
				'short_continue_watching_' + activeProfile,
				'short_watch_history_' + activeProfile
			];
			if (targetUid) {
				keysToClean.push('shorttv_history_' + targetUid);
				keysToClean.push('short_continue_watching_' + targetUid + '_' + activeProfile);
				keysToClean.push('short_watch_history_' + targetUid + '_' + activeProfile);
			}
			keysToClean.forEach(function(k) {
				try {
					var raw = localStorage.getItem(k);
					if (raw) {
						var parsed = JSON.parse(raw);
						if (parsed && typeof parsed === 'object') {
							delete parsed[strId];
							localStorage.setItem(k, JSON.stringify(parsed));
						}
					}
				} catch(e) {}
			});
			delete serverHistory[strId];
		} catch(e) {}

		if (targetUid) {
			fetch(rtdbBase + '/users/' + targetUid + '/history/' + strId + '.json', { method: 'DELETE' }).catch(function(){});
			if (typeof firebase !== 'undefined' && firebase.database) {
				try { firebase.database().ref('users/' + targetUid + '/history/' + strId).remove(); } catch(e) {}
			}
			if (typeof firebase !== 'undefined' && firebase.firestore) {
				try {
					firebase.firestore().collection('users').doc(targetUid).collection('profiles').doc(activeProfile).collection('watch_history').doc(strId).delete().catch(function(){});
					firebase.firestore().collection('users').doc(targetUid).collection('profiles').doc(activeProfile).collection('continue_watching').doc(strId).delete().catch(function(){});
				} catch(e) {}
			}
		}

		setTimeout(function() {
			if (card) card.remove();
			var grid = document.getElementById('history-cards-grid');
			var remaining = grid ? grid.querySelectorAll('.short-drama-card').length : 0;
			var countEl = document.getElementById('history-total-count');
			if (countEl) countEl.textContent = remaining;
			if (remaining === 0) showState('empty');
		}, 300);
	};

	window.clearAllHistory = function() {
		if (!confirm('Are you sure you want to clear your entire watch history?')) return;

		var targetUid = window._historyUid || (localStorage.getItem('short_is_logged_in') === '1' ? localStorage.getItem('short_user_uid') : null) || (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser ? firebase.auth().currentUser.uid : null);
		var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';

		try {
			var keysToClear = [
				historyStorageKey,
				'short_guest_watch_history',
				'short_continue_watching',
				'short_continue_watching_guest_profile_1',
				'short_watch_history_guest_profile_1',
				'short_continue_watching_' + activeProfile,
				'short_watch_history_' + activeProfile
			];
			if (targetUid) {
				keysToClear.push('shorttv_history_' + targetUid);
				keysToClear.push('short_continue_watching_' + targetUid + '_' + activeProfile);
				keysToClear.push('short_watch_history_' + targetUid + '_' + activeProfile);
			}
			keysToClear.forEach(function(k) {
				localStorage.removeItem(k);
			});
		} catch(e) {}

		serverHistory = {};

		if (targetUid) {
			fetch(rtdbBase + '/users/' + targetUid + '/history.json', { method: 'DELETE' }).catch(function(){});
			if (typeof firebase !== 'undefined' && firebase.database) {
				try { firebase.database().ref('users/' + targetUid + '/history').remove(); } catch(e) {}
			}
			if (typeof firebase !== 'undefined' && firebase.firestore) {
				try {
					var userDoc = firebase.firestore().collection('users').doc(targetUid).collection('profiles').doc(activeProfile);
					userDoc.collection('watch_history').get().then(function(snap){ snap.forEach(function(doc){ doc.ref.delete(); }); }).catch(function(){});
					userDoc.collection('continue_watching').get().then(function(snap){ snap.forEach(function(doc){ doc.ref.delete(); }); }).catch(function(){});
				} catch(e) {}
			}
		}

		<?php if ( is_user_logged_in() ) : ?>
		try {
			fetch('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'action=shorttv_clear_history'
			}).catch(function() {});
		} catch(e) {}
		<?php endif; ?>

		var grid = document.getElementById('history-cards-grid');
		if (grid) {
			grid.innerHTML = '';
			grid.classList.add('is-hidden');
			grid.style.setProperty('display', 'none', 'important');
		}

		showState('empty');
		var countEl = document.getElementById('history-total-count');
		if (countEl) countEl.textContent = '0';
	};

	function loadHistory() {
		function getLocalData() {
			var localData = {};
			var isLoggedIn = localStorage.getItem('short_is_logged_in') === '1' || !!window._historyUid;
			var uid = window._historyUid || (isLoggedIn ? localStorage.getItem('short_user_uid') : null);
			var profileId = localStorage.getItem('short_active_profile_id') || 'profile_1';

			if (isLoggedIn && uid) {
				try {
					var rawUid = localStorage.getItem('shorttv_history_' + uid);
					if (rawUid) Object.assign(localData, JSON.parse(rawUid) || {});
				} catch(e) {}
				try {
					var rawProfile = localStorage.getItem('short_continue_watching_' + uid + '_' + profileId);
					if (rawProfile) Object.assign(localData, JSON.parse(rawProfile) || {});
				} catch(e) {}
				return localData;
			} else if (!isLoggedIn) {
				try {
					var raw = localStorage.getItem('short_guest_watch_history') || localStorage.getItem('short_continue_watching_guest_' + profileId);
					if (raw) Object.assign(localData, JSON.parse(raw) || {});
				} catch(e) {}
				return localData;
			}
			return {};
		}

		// 1. Immediately render local storage data to avoid white/empty flicker
		var isUserLoggedIn = localStorage.getItem('short_is_logged_in') === '1' || !!window._historyUid;
		var currentUid = window._historyUid || (isUserLoggedIn ? localStorage.getItem('short_user_uid') : null);
		var initialLocal = getLocalData();
		if (initialLocal && typeof initialLocal === 'object' && Object.keys(initialLocal).length > 0) {
			renderHistory(initialLocal);
		} else if (!isUserLoggedIn && serverHistory && typeof serverHistory === 'object' && Object.keys(serverHistory).length > 0) {
			renderHistory(serverHistory);
		} else {
			showState('empty');
		}

		if (currentUid) {
			loadUserCloudHistory(currentUid);
		}

		// 2. Listen to Firebase Auth for real-time cloud sync
		if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
			firebase.auth().onAuthStateChanged(function(user) {
				if (!user) {
					window._historyUid = null;
					var guestData = getLocalData();
					if (guestData && Object.keys(guestData).length > 0) {
						renderHistory(guestData);
					} else {
						showState('empty');
					}
					return;
				}

				window._historyUid = user.uid;
				loadUserCloudHistory(user.uid);
			});
		}

		function loadUserCloudHistory(uid) {
			if (!uid) return;
			var profileId = localStorage.getItem('short_active_profile_id') || 'profile_1';
			var rtdbData = {};
			var firestoreData = {};

			function combineAndRender() {
				var merged = Object.assign({}, rtdbData, firestoreData);
				if (Object.keys(merged).length > 0) {
					localStorage.setItem('shorttv_history_' + uid, JSON.stringify(merged));
					localStorage.setItem('short_continue_watching_' + uid + '_' + profileId, JSON.stringify(merged));
					renderHistory(merged);
				} else {
					localStorage.setItem('shorttv_history_' + uid, JSON.stringify({}));
					localStorage.setItem('short_continue_watching_' + uid + '_' + profileId, JSON.stringify({}));
					renderHistory({});
				}
			}

			// 1. Immediate direct REST Fetch from RTDB
			fetch(rtdbBase + '/users/' + uid + '/history.json')
				.then(function(r) { return r.json(); })
				.then(function(data) {
					if (data && typeof data === 'object') {
						rtdbData = Object.assign({}, data);
						combineAndRender();
					}
				})
				.catch(function(err) {
					console.warn('[History] REST fetch error:', err);
				});

			// 2. Realtime DB Listener if available
			if (typeof firebase !== 'undefined') {
				try {
					var dbInstance = null;
					if (firebase.database) {
						try { dbInstance = firebase.database(); } catch(e) {}
					}
					if (!dbInstance && firebase.app) {
						try { dbInstance = firebase.app().database(rtdbBase); } catch(e) {}
					}
					if (dbInstance) {
						window._historyRtdb = dbInstance;
						dbInstance.ref('users/' + uid + '/history').on('value', function(snap) {
							var val = snap.val();
							if (val && typeof val === 'object') {
								rtdbData = Object.assign({}, val);
								combineAndRender();
							}
						});
					}
				} catch(e) {}
			}

			// 3. Firestore Listener if available
			if (typeof firebase !== 'undefined' && firebase.firestore) {
				try {
					var userRef = firebase.firestore().collection('users').doc(uid).collection('profiles').doc(profileId);
					userRef.collection('continue_watching').onSnapshot(function(snap) {
						firestoreData = {};
						snap.forEach(function(doc) {
							if (doc.exists && doc.data()) firestoreData[doc.id] = doc.data();
						});
						if (Object.keys(firestoreData).length > 0) {
							combineAndRender();
						}
					}, function() {});
				} catch(e) {}
			}
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', loadHistory);
	} else {
		loadHistory();
	}
})();
</script>

<?php
get_footer();
