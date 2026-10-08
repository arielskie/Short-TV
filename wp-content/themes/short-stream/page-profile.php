<?php
/**
 * Template Name: Short TV User Dashboard / Profile
 *
 * @package Short_Stream
 */

get_header();
?>

<style>
/* ═══ Unified Sleek Modern Profile Dashboard ═══ */
.stv-dash {
	max-width: 880px;
	margin: 0 auto;
	padding: 110px 16px 80px;
	min-height: 80vh;
}

.stv-profile-surface {
	background: #13131a;
	border: 1px solid rgba(255, 255, 255, 0.08);
	border-radius: 24px;
	padding: 32px 36px;
	box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
	position: relative;
	overflow: hidden;
}

.stv-profile-surface::before {
	content: '';
	position: absolute;
	top: -80px;
	right: -80px;
	width: 260px;
	height: 260px;
	background: radial-gradient(circle, rgba(255, 45, 85, 0.12) 0%, transparent 70%);
	pointer-events: none;
}

/* ─── Profile Header ─── */
.stv-profile-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 20px;
	flex-wrap: wrap;
}

.stv-user-info-wrap {
	display: flex;
	align-items: center;
	gap: 20px;
	flex: 1;
	min-width: 260px;
}

.stv-av-wrap {
	position: relative;
	flex-shrink: 0;
}

.stv-av {
	width: 76px;
	height: 76px;
	border-radius: 50%;
	object-fit: cover;
	border: 2.5px solid #ff2d55;
	box-shadow: 0 4px 18px rgba(255, 45, 85, 0.35);
	display: block;
	background: #1c1c24;
	cursor: pointer;
	transition: transform 0.2s, box-shadow 0.2s;
}

.stv-av:hover {
	transform: scale(1.04);
	box-shadow: 0 6px 24px rgba(255, 45, 85, 0.5);
}

.stv-user-meta {
	flex: 1;
	min-width: 0;
}

.stv-name-row {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-bottom: 4px;
	flex-wrap: wrap;
}

.stv-user-name {
	font-size: 22px;
	font-weight: 800;
	color: #ffffff;
	letter-spacing: -0.3px;
	line-height: 1.2;
}

.stv-badge {
	font-size: 10px;
	font-weight: 800;
	padding: 2.5px 9px;
	border-radius: 8px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	background: linear-gradient(135deg, #4caf50, #00897b);
	color: #fff;
	display: inline-flex;
	align-items: center;
}

.stv-user-email {
	color: #94a3b8;
	font-size: 13px;
	font-weight: 500;
	margin-bottom: 8px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.stv-uid-row {
	display: flex;
	align-items: center;
	gap: 8px;
	flex-wrap: wrap;
}

.stv-uid-badge {
	font-size: 11px;
	color: #64748b;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
	background: rgba(255, 255, 255, 0.04);
	border: 1px solid rgba(255, 255, 255, 0.06);
	padding: 3px 10px;
	border-radius: 8px;
}

.stv-copy-btn {
	background: none;
	border: none;
	color: #ff2d55;
	font-size: 11.5px;
	font-weight: 700;
	cursor: pointer;
	padding: 0;
	transition: opacity 0.2s;
}

.stv-copy-btn:hover {
	opacity: 0.8;
}

/* ─── Profile Actions ─── */
.stv-profile-actions {
	display: flex;
	align-items: center;
	gap: 10px;
	flex-shrink: 0;
}

.stv-btn-action {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	padding: 9px 18px;
	border-radius: 12px;
	font-size: 13px;
	font-weight: 700;
	text-decoration: none;
	cursor: pointer;
	transition: all 0.2s;
	white-space: nowrap;
}

.stv-btn-settings {
	background: rgba(255, 255, 255, 0.06);
	border: 1px solid rgba(255, 255, 255, 0.12);
	color: #e2e8f0;
}

.stv-btn-settings:hover {
	background: rgba(255, 255, 255, 0.12);
	color: #ffffff;
	border-color: rgba(255, 255, 255, 0.25);
}

.stv-btn-signout {
	background: rgba(239, 68, 68, 0.1);
	border: 1px solid rgba(239, 68, 68, 0.25);
	color: #f87171;
}

.stv-btn-signout:hover {
	background: rgba(239, 68, 68, 0.2);
	color: #ff8585;
	border-color: rgba(239, 68, 68, 0.4);
}

.stv-btn-google-login {
	background: #ffffff;
	color: #0f172a;
	border: none;
	box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
}

.stv-btn-google-login:hover {
	background: #f1f5f9;
}

/* ─── Seamless Horizontal Stats Strip ─── */
.stv-stats-strip {
	display: flex;
	align-items: center;
	justify-content: space-around;
	background: rgba(255, 255, 255, 0.025);
	border: 1px solid rgba(255, 255, 255, 0.05);
	border-radius: 16px;
	margin: 28px 0;
	padding: 16px 20px;
}

.stv-stat-col {
	flex: 1;
	text-align: center;
	position: relative;
}

.stv-stat-col:not(:last-child)::after {
	content: '';
	position: absolute;
	right: 0;
	top: 15%;
	height: 70%;
	width: 1px;
	background: rgba(255, 255, 255, 0.06);
}

.stv-stat-val {
	font-size: 24px;
	font-weight: 800;
	color: #ffffff;
	line-height: 1.1;
	margin-bottom: 4px;
}

.stv-stat-name {
	font-size: 11px;
	font-weight: 700;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 0.6px;
}

/* ─── Integrated 2-Column Wallet & VIP Row ─── */
.stv-integrated-panels {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16px;
}

.stv-panel-box {
	background: rgba(255, 255, 255, 0.025);
	border: 1px solid rgba(255, 255, 255, 0.06);
	border-radius: 18px;
	padding: 22px;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	gap: 18px;
}

.stv-panel-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.stv-panel-tag {
	font-size: 11.5px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	display: inline-flex;
	align-items: center;
	gap: 6px;
}

.stv-panel-tag.coins { color: #fbbf24; }
.stv-panel-tag.vip { color: #c084fc; }

.stv-panel-main {
	margin-top: 4px;
}

.stv-panel-big {
	font-size: 26px;
	font-weight: 900;
	color: #ffffff;
	line-height: 1.2;
}

.stv-panel-sub {
	color: #8892b0;
	font-size: 12px;
	margin-top: 4px;
	line-height: 1.4;
}

.stv-panel-actions {
	display: flex;
	align-items: center;
	gap: 10px;
	flex-wrap: wrap;
}

.stv-btn-claim {
	background: rgba(251, 191, 36, 0.1);
	border: 1px solid rgba(251, 191, 36, 0.3);
	color: #fbbf24;
	font-size: 12.5px;
	font-weight: 700;
	padding: 8px 16px;
	border-radius: 12px;
	cursor: pointer;
	transition: all 0.2s;
}

.stv-btn-claim:hover:not(:disabled) {
	background: rgba(251, 191, 36, 0.2);
}

.stv-btn-claim:disabled {
	opacity: 0.5;
	cursor: default;
}

.stv-btn-topup-gradient {
	background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
	color: #ffffff;
	font-size: 12.5px;
	font-weight: 800;
	padding: 8px 18px;
	border-radius: 12px;
	text-decoration: none;
	display: inline-flex;
	align-items: center;
	gap: 6px;
	border: none;
	box-shadow: 0 4px 14px rgba(234, 88, 12, 0.35);
	transition: all 0.2s;
}

.stv-btn-topup-gradient:hover {
	transform: translateY(-1px);
	box-shadow: 0 6px 18px rgba(234, 88, 12, 0.5);
	color: #ffffff;
}

.stv-btn-vip-gradient {
	background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
	color: #ffffff;
	font-size: 12.5px;
	font-weight: 800;
	padding: 8px 20px;
	border-radius: 12px;
	text-decoration: none;
	display: inline-flex;
	align-items: center;
	gap: 6px;
	border: none;
	box-shadow: 0 4px 14px rgba(168, 85, 247, 0.35);
	transition: all 0.2s;
}

.stv-btn-vip-gradient:hover {
	transform: translateY(-1px);
	box-shadow: 0 6px 18px rgba(168, 85, 247, 0.5);
	color: #ffffff;
}

/* Guest Banner */
.stv-guest-bar {
	background: linear-gradient(135deg, rgba(255,45,85,0.08), rgba(251,191,36,0.05));
	border: 1px solid rgba(255,45,85,0.2);
	border-radius: 14px;
	padding: 14px 18px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 14px;
	margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
	.stv-dash {
		padding: 95px 12px 60px;
	}
	.stv-profile-surface {
		padding: 22px 18px;
		border-radius: 18px;
	}
	.stv-integrated-panels {
		grid-template-columns: 1fr;
		gap: 12px;
	}
	.stv-user-info-wrap {
		gap: 14px;
	}
	.stv-av {
		width: 60px;
		height: 60px;
	}
	.stv-user-name {
		font-size: 18px;
	}
	.stv-stats-strip {
		margin: 20px 0;
		padding: 12px 10px;
	}
	.stv-stat-val {
		font-size: 18px;
	}
	.stv-stat-name {
		font-size: 10px;
	}
}
</style>

<div class="stv-dash">
	<div class="stv-profile-surface">

		<!-- ═══ USER HEADER ROW ═══ -->
		<div class="stv-profile-header">
			<div class="stv-user-info-wrap">
				<div class="stv-av-wrap">
					<img id="pf-avatar" class="stv-av" referrerpolicy="no-referrer"
						src="data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 36 36' fill='none'><circle cx='18' cy='18' r='18' fill='%23222228'/><circle cx='18' cy='13.5' r='5.5' fill='%23cbd5e1'/><path d='M8 29.5c0-5.5 4.5-9 10-9s10 3.5 10 9' fill='%23cbd5e1'/></svg>"
						alt="Profile Photo" onclick="window.promptChangePhoto()" title="Click to change photo" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 36 36\' fill=\'none\'><circle cx=\'18\' cy=\'18\' r=\'18\' fill=\'%23222228\'/><circle cx=\'18\' cy=\'13.5\' r=\'5.5\' fill=\'%23cbd5e1\'/><path d=\'M8 29.5c0-5.5 4.5-9 10-9s10 3.5 10 9\' fill=\'%23cbd5e1\'/></svg>';" />
				</div>

				<div class="stv-user-meta">
					<div class="stv-name-row">
						<span id="pf-name" class="stv-user-name">Guest Viewer</span>
						<span id="pf-badge" class="stv-badge" style="background:#4b5563;">GUEST</span>
					</div>
					<div id="pf-email" class="stv-user-email">Sign in with Google to sync your coins and VIP pass.</div>
					<div class="stv-uid-row">
						<span id="pf-uid" class="stv-uid-badge">Session: Guest</span>
						<button onclick="copyUid()" style="display:none;" id="pf-copy-btn" class="stv-copy-btn">Copy UID</button>
					</div>
				</div>
			</div>

			<div class="stv-profile-actions" id="pf-auth-action">
				<!-- Guest Mode -->
				<button type="button" onclick="signInWithGoogle()" id="pf-google-signin-btn" class="stv-btn-action stv-btn-google-login">
					<svg width="15" height="15" viewBox="0 0 24 24"><path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.7l3.1-3.1C17.3 1.8 14.8 1 12 1 7.4 1 3.5 3.6 1.6 7.4l3.7 2.9C6.2 7.4 8.9 5 12 5z"/><path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/><path fill="#FBBC05" d="M5.3 14.7c-.2-.7-.4-1.5-.4-2.4s.2-1.7.4-2.4L1.6 7c-.8 1.6-1.3 3.4-1.3 5.3 0 1.9.5 3.7 1.3 5.3l3.7-2.9z"/><path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3.1 0-5.8-2.4-6.7-5.3L1.6 16C3.5 19.8 7.4 23 12 23z"/></svg>
					<span>Sign In with Google</span>
				</button>
				<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" id="pf-login-link-btn" class="stv-btn-action stv-btn-settings">
					Sign In / Register
				</a>

				<!-- Signed-In Mode -->
				<a href="<?php echo esc_url( home_url( '/account/' ) ); ?>" id="pf-settings-btn" class="stv-btn-action stv-btn-settings" style="display:none;">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
					<span>Settings</span>
				</a>
				<button type="button" onclick="handleSignOut()" id="pf-signout-btn-hero" class="stv-btn-action stv-btn-signout" style="display:none;">
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
					<span>Sign Out</span>
				</button>
			</div>
		</div>

		<!-- ═══ SEAMLESS HORIZONTAL STATS STRIP ═══ -->
		<div class="stv-stats-strip">
			<div class="stv-stat-col">
				<div class="stv-stat-val" id="stat-watchlist">0</div>
				<div class="stv-stat-name">Saved</div>
			</div>
			<div class="stv-stat-col">
				<div class="stv-stat-val" id="stat-likes">0</div>
				<div class="stv-stat-name">Liked</div>
			</div>
			<div class="stv-stat-col">
				<div class="stv-stat-val" id="stat-ratings">0</div>
				<div class="stv-stat-name">Rated</div>
			</div>
			<div class="stv-stat-col">
				<div class="stv-stat-val" id="stat-comments">0</div>
				<div class="stv-stat-name">Comments</div>
			</div>
		</div>

		<!-- ═══ INTEGRATED WALLET & VIP ROW ═══ -->
		<div class="stv-integrated-panels">
			<!-- Drama Coins Wallet -->
			<div class="stv-panel-box">
				<div>
					<div class="stv-panel-head">
						<span class="stv-panel-tag coins">💰 Drama Coins Wallet</span>
					</div>
					<div class="stv-panel-main">
						<div class="stv-panel-big"><span id="pf-coins">100</span> <span style="font-size:18px;color:#fbbf24;">💰</span></div>
						<div class="stv-panel-sub" id="pf-coins-sub">Earn coins by daily check-in or top up to unlock episodes</div>
					</div>
				</div>
				<div class="stv-panel-actions">
					<button onclick="claimDailyCheckin()" id="pf-checkin-btn" class="stv-btn-claim">Daily +20 💰</button>
					<a href="<?php echo esc_url( home_url( '/subscription/?tab=coins' ) ); ?>" class="stv-btn-topup-gradient">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="#fff"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
						<span>+ Top Up Coins</span>
					</a>
				</div>
			</div>

			<!-- VIP Membership Pass -->
			<div class="stv-panel-box" id="pf-sub-card">
				<div>
					<div class="stv-panel-head">
						<span class="stv-panel-tag vip">⭐ ShortTV VIP Pass</span>
					</div>
					<div class="stv-panel-main">
						<div class="stv-panel-big" id="pf-sub-tier" style="font-size:20px;">Free Viewer</div>
						<div class="stv-panel-sub" id="pf-sub-expires">Watch for free or unlock all episodes with VIP Pass</div>
					</div>
				</div>
				<div class="stv-panel-actions">
					<a href="<?php echo esc_url( home_url( '/subscription/' ) ); ?>" id="pf-sub-btn" class="stv-btn-vip-gradient">
						<span>Manage Plan</span>
					</a>
				</div>
			</div>
		</div>

	</div><!-- /.stv-profile-surface -->
</div><!-- /.stv-dash -->

<script>
(function() {
'use strict';

var RTDB_BASE = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
var WATCH_URL = '<?php echo esc_js( home_url('/watch/') ); ?>';
var rtdb = null;
var currentUser = null;

/* ── Helpers ── */
function fmt(ts) {
	if (!ts) return '';
	var d = new Date(ts);
	return d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
}
function fmtTime(ts) {
	if (!ts) return '';
	var d = new Date(ts);
	return d.toLocaleDateString('en-US', { month:'short', day:'numeric' }) + ' ' + d.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });
}
function esc(s) {
	return String(s||'').replace(/[&<>"']/g, function(c) {
		return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
	});
}
function stopLoad(id) {
	var el = document.getElementById('load-' + id);
	if (el) el.style.display = 'none';
}
function empty(id) {
	stopLoad(id);
	var el = document.getElementById('empty-' + id);
	if (el) el.style.display = 'block';
}
function setCount(id, n) {
	var el = document.getElementById('cnt-' + id);
	if (el) el.textContent = n;
	var st = document.getElementById('stat-' + id);
	if (st) st.textContent = n;
}

/* ── Section toggle ── */
window.toggleSection = function(bodyId, headEl) {
	var body = document.getElementById(bodyId);
	if (!body) return;
	var collapsed = body.classList.toggle('collapsed');
	var chev = headEl.querySelector('.stv-chevron');
	if (chev) chev.classList.toggle('open', !collapsed);
};

/* ── Watchlist ── */
function loadWatchlist(uid) {
	if (!uid) {
		loadLocalWatchlist();
		return;
	}
	if (rtdb) {
		rtdb.ref('users/' + uid + '/watchlist').once('value', function(snap) {
			var val = snap.val();
			if (!val || !Object.keys(val).length) {
				loadLocalWatchlist();
			} else {
				renderWatchlist(val, false);
			}
		});
	} else {
		fetch(RTDB_BASE + '/users/' + uid + '/watchlist.json')
			.then(r => r.json())
			.then(function(val) {
				if (!val) loadLocalWatchlist();
				else renderWatchlist(val, false);
			})
			.catch(loadLocalWatchlist);
	}
}

function loadLocalWatchlist() {
	var profId = localStorage.getItem('short_active_profile_id') || 'profile_1';
	var raw = localStorage.getItem('short_my_list_' + profId) || localStorage.getItem('short_my_list') || localStorage.getItem('stv_watchlist_guest');
	var data = {};
	try { data = JSON.parse(raw) || {}; } catch(e) {}
	renderWatchlist(data, true);
}

function renderWatchlist(data, isLocal) {
	stopLoad('watchlist');
	var grid = document.getElementById('watchlist-grid');
	if (!data || !Object.keys(data).length) {
		setCount('watchlist', 0);
		empty('watchlist');
		var emp = document.getElementById('empty-watchlist');
		if (emp) {
			emp.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="#555"><path fill-rule="evenodd" d="M6 0h12a3 3 0 0 1 3 3v19.86a.72.72 0 0 1-1.14.66L12 18.78l-7.86 4.74A.72.72 0 0 1 3 22.86V3a3 3 0 0 1 3-3zm6 3.84l1.74 3.84 4.08.72-2.94 2.88.66 4.32L12 13.68l-3.54 1.92.66-4.32-2.94-2.88 4.08-.72z"/></svg>No saved dramas yet<br><span style="font-size:11.5px;color:#777;">(Tap "+ My List" while watching any drama to add it here)</span>';
		}
		return;
	}
	var keys = Object.keys(data).sort((a,b) => ((data[b]||{}).savedAt||(data[b]||{}).added_at||0) - ((data[a]||{}).savedAt||(data[a]||{}).added_at||0));
	setCount('watchlist', keys.length);

	var banner = isLocal ? '<div style="grid-column:1/-1; background:rgba(255,152,0,0.08); border:1px solid rgba(255,152,0,0.25); border-radius:10px; padding:8px 14px; font-size:12px; color:#ff9800; display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;"><span>💾 Saved locally on this browser</span><button type="button" onclick="signInWithGoogle()" style="background:#fff; color:#111; border:none; padding:4px 10px; border-radius:6px; font-weight:700; font-size:11.5px; cursor:pointer;">Sync to Google</button></div>' : '';

	grid.innerHTML = banner + keys.map(function(id) {
		var item = data[id] || {};
		var link = WATCH_URL + '?id=' + id;
		var poster = item.poster || item.poster_path || '';
		return '<a href="'+link+'" class="stv-drama-thumb" title="'+esc(item.title||item.name||'')+'">' +
			(poster ? '<img src="'+esc(poster)+'" alt="'+esc(item.title||item.name||'')+'" loading="lazy" />' :
				'<div style="width:100%;height:100%;background:#222;display:flex;align-items:center;justify-content:center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="#555"><path d="M8 5v14l11-7z"/></svg></div>') +
			'<div class="stv-drama-thumb-title">'+esc(item.title||item.name||'Drama')+'</div>' +
			'</a>';
	}).join('');
}

/* ── Likes ── */
function loadLikes(uid) {
	if (!uid) {
		loadLocalLikes();
		return;
	}
	if (rtdb) {
		rtdb.ref('users/' + uid + '/likes').once('value', function(snap) {
			var val = snap.val();
			if (!val || !Object.keys(val).length) loadLocalLikes();
			else renderLikes(val, false);
		});
	} else {
		fetch(RTDB_BASE + '/users/' + uid + '/likes.json')
			.then(r => r.json())
			.then(function(val) {
				if (!val) loadLocalLikes();
				else renderLikes(val, false);
			})
			.catch(loadLocalLikes);
	}
}

function loadLocalLikes() {
	var data = {};
	try {
		for (var i = 0; i < localStorage.length; i++) {
			var k = localStorage.key(i);
			if (k && k.indexOf('stv_liked_') === 0) {
				var sid = k.replace('stv_liked_', '');
				if (sid) data[sid] = true;
			}
		}
		var profId = localStorage.getItem('short_active_profile_id') || 'profile_1';
		var raw = localStorage.getItem('short_user_likes_' + profId) || localStorage.getItem('short_likes') || localStorage.getItem('stv_likes');
		if (raw) Object.assign(data, JSON.parse(raw) || {});
	} catch(e) {}
	renderLikes(data, true);
}

function renderLikes(data, isLocal) {
	stopLoad('likes');
	var grid = document.getElementById('likes-grid');
	if (!data || !Object.keys(data).length) {
		setCount('likes', 0);
		empty('likes');
		var emp = document.getElementById('empty-likes');
		if (emp) {
			emp.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="#555"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>No liked dramas yet<br><span style="font-size:11.5px;color:#777;">(Tap "❤️ Like" while watching any drama to add it here)</span>';
		}
		return;
	}
	var keys = Object.keys(data).filter(k => data[k]);
	setCount('likes', keys.length);
	grid.innerHTML = keys.map(function(id) {
		var item = typeof data[id] === 'object' ? data[id] : {};
		var link = WATCH_URL + '?id=' + id;
		var poster = item.poster || item.poster_path || '';
		return '<a href="'+link+'" class="stv-drama-thumb" title="Series #'+id+'">' +
			(poster ? '<img src="'+esc(poster)+'" alt="Drama #'+id+'" loading="lazy" />' :
			'<div style="width:100%;height:100%;background:#1a1a24;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:4px;">' +
			'<svg width="22" height="22" viewBox="0 0 24 24" fill="#ff2d55"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>' +
			'</div>') +
			'<div class="stv-drama-thumb-title">'+(item.title ? esc(item.title) : 'Drama #'+id)+'</div>' +
			'</a>';
	}).join('');
}

/* ── Ratings ── */
function getLocalRatingsMap() {
	var rated = {};
	try {
		for (var i = 0; i < localStorage.length; i++) {
			var k = localStorage.key(i);
			if (k && k.indexOf('shorttv_rating_') === 0) {
				var sid = k.replace('shorttv_rating_', '');
				var val = parseInt(localStorage.getItem(k), 10);
				if (sid && val > 0) rated[sid] = val;
			}
		}
	} catch(e) {}
	return rated;
}

function loadRatings(uid) {
	var localRatings = getLocalRatingsMap();
	if (!uid) {
		renderRatings(localRatings);
		return;
	}
	if (rtdb) {
		rtdb.ref('users/' + uid + '/ratings').once('value', function(snap) {
			var val = snap.val() || {};
			renderRatings(Object.assign({}, localRatings, val));
		});
	} else {
		fetch(RTDB_BASE + '/users/' + uid + '/ratings.json')
			.then(r => r.json())
			.then(val => renderRatings(Object.assign({}, localRatings, val || {})))
			.catch(() => renderRatings(localRatings));
	}
}

function renderRatings(data) {
	stopLoad('ratings');
	var list = document.getElementById('ratings-list');
	if (!data || !Object.keys(data).length) { setCount('ratings', 0); empty('ratings'); return; }
	var keys = Object.keys(data);
	setCount('ratings', keys.length);
	list.innerHTML = keys.map(function(seriesId) {
		var rating = parseInt(data[seriesId], 10) || 5;
		var stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);
		return '<li class="stv-activity-item">' +
			'<div class="stv-activity-dot" style="background:rgba(255,193,7,0.1);color:#ffc107;font-size:16px;">★</div>' +
			'<div class="stv-activity-body">' +
				'<div class="stv-activity-title"><a href="'+WATCH_URL+'?id='+seriesId+'" style="color:#fff;text-decoration:none;">Drama #'+seriesId+'</a></div>' +
				'<div class="stv-activity-sub" style="color:#ffc107;letter-spacing:1px;">'+stars+'</div>' +
			'</div>' +
			'<div class="stv-activity-meta">'+rating+'/5</div>' +
			'</li>';
	}).join('');
}

/* ── Comments ── */
function loadComments(uid) {
	if (!uid) {
		setCount('comments', 0);
		empty('comments');
		return;
	}
	if (rtdb) {
		rtdb.ref('users/' + uid + '/comments').limitToLast(50).once('value', function(snap) {
			renderUserComments(snap.val());
		});
	} else {
		fetch(RTDB_BASE + '/users/' + uid + '/comments.json?orderBy="$key"&limitToLast=50')
			.then(r => r.json()).then(renderUserComments).catch(() => empty('comments'));
	}
}
function renderUserComments(data) {
	stopLoad('comments');
	var list = document.getElementById('comments-list');
	if (!data) {
		fetchCommentsGlobal(currentUser && currentUser.uid);
		return;
	}
	var items = [];
	Object.keys(data).forEach(function(seriesId) {
		var group = data[seriesId];
		if (group && typeof group === 'object') {
			Object.keys(group).forEach(function(k) {
				var c = group[k];
				if (c) items.push({ seriesId: seriesId, text: c.text, ts: c.createdAt });
			});
		}
	});
	items.sort((a,b) => (b.ts||0)-(a.ts||0));
	if (!items.length) { setCount('comments', 0); empty('comments'); return; }
	setCount('comments', items.length);
	list.innerHTML = items.slice(0,30).map(function(c) {
		return '<li class="stv-activity-item">' +
			'<div class="stv-activity-dot" style="background:rgba(33,150,243,0.1);color:#42a5f5;">💬</div>' +
			'<div class="stv-activity-body">' +
				'<div class="stv-activity-title"><a href="'+WATCH_URL+'?id='+c.seriesId+'" style="color:#fff;text-decoration:none;">Series #'+c.seriesId+'</a></div>' +
				'<div class="stv-activity-sub">'+esc(c.text||'')+'</div>' +
			'</div>' +
			'<div class="stv-activity-meta">'+fmtTime(c.ts)+'</div>' +
			'</li>';
	}).join('');
}

function fetchCommentsGlobal(uid) {
	if (!uid) { setCount('comments', 0); empty('comments'); return; }
	if (rtdb) {
		rtdb.ref('comments').once('value', function(snap) {
			var data = snap.val() || {};
			var items = [];
			Object.keys(data).forEach(function(seriesId) {
				var group = data[seriesId];
				if (!group) return;
				Object.keys(group).forEach(function(k) {
					var c = group[k];
					if (c && c.uid === uid) {
						items.push({ seriesId: seriesId, text: c.text, ts: c.createdAt });
					}
				});
			});
			items.sort((a,b)=>(b.ts||0)-(a.ts||0));
			if (!items.length) { setCount('comments', 0); empty('comments'); return; }
			setCount('comments', items.length);
			var list = document.getElementById('comments-list');
			list.innerHTML = items.slice(0,30).map(function(c) {
				return '<li class="stv-activity-item">' +
					'<div class="stv-activity-dot" style="background:rgba(33,150,243,0.1);color:#42a5f5;">💬</div>' +
					'<div class="stv-activity-body">' +
						'<div class="stv-activity-title"><a href="'+WATCH_URL+'?id='+c.seriesId+'" style="color:#fff;text-decoration:none;">Series #'+c.seriesId+'</a></div>' +
						'<div class="stv-activity-sub">'+esc(c.text||'')+'</div>' +
					'</div>' +
					'<div class="stv-activity-meta">'+fmtTime(c.ts)+'</div>' +
					'</li>';
			});
		});
	} else {
		setCount('comments', 0);
		empty('comments');
	}
}

/* ── Coin Transactions ── */
function loadTransactions(uid) {
	if (!uid) {
		renderLocalTransactions('guest');
		return;
	}
	if (rtdb) {
		rtdb.ref('users/' + uid + '/coinHistory').limitToLast(50).once('value', function(snap) {
			renderTransactions(snap.val());
		});
	} else {
		fetch(RTDB_BASE + '/users/' + uid + '/coinHistory.json?orderBy="$key"&limitToLast=50')
			.then(r => r.json()).then(renderTransactions).catch(() => {
				renderLocalTransactions(uid);
			});
	}
}
function renderLocalTransactions(uid) {
	var local = [];
	try { local = JSON.parse(localStorage.getItem('stv_coin_history_' + uid) || '[]'); } catch(e) {}
	renderTransactions(local.length ? local.reduce((o,t,i)=>{o[i]=t;return o;},{}) : null);
}
function renderTransactions(data) {
	stopLoad('tx');
	var list = document.getElementById('tx-list');
	if (!data) {
		var uid = currentUser && currentUser.uid || 'guest';
		var local = [];
		try { local = JSON.parse(localStorage.getItem('stv_coin_history_' + uid) || '[]'); } catch(e) {}
		if (local.length) {
			data = local.reduce((o,t,i)=>{o[i]=t;return o;},{});
		} else {
			empty('tx');
			return;
		}
	}
	var items = typeof data === 'object' && !Array.isArray(data)
		? Object.values(data).filter(Boolean)
		: (Array.isArray(data) ? data : []);
	items.sort((a,b)=>(b.ts||0)-(a.ts||0));
	if (!items.length) { empty('tx'); return; }
	setCount('tx', items.length);
	list.innerHTML = items.slice(0,50).map(function(t) {
		var earn = (t.amount > 0);
		return '<li class="stv-activity-item">' +
			'<div class="stv-activity-dot" style="background:'+(earn?'rgba(76,175,80,0.1)':'rgba(255,82,82,0.1)')+';font-size:14px;">'+(earn?'＋':'－')+'</div>' +
			'<div class="stv-activity-body">' +
				'<div class="stv-activity-title">'+esc(t.reason||'Coin transaction')+'</div>' +
				'<div class="stv-activity-sub">'+fmtTime(t.ts)+'</div>' +
			'</div>' +
			'<div class="stv-activity-meta '+(earn?'tx-earn':'tx-spend')+'">'+(earn?'+':'')+t.amount+' 🪙</div>' +
			'</li>';
	}).join('');
}

/* ── Coin balance ── */
function loadCoins(uid) {
	if (rtdb && uid) {
		rtdb.ref('users/' + uid + '/coins').on('value', function(snap) {
			var v = snap.val();
			if (v !== null && v !== undefined) {
				var el = document.getElementById('pf-coins');
				if (el) el.textContent = parseInt(v, 10);
				localStorage.setItem('shorttv_user_coins', String(v));
			}
		});
	} else {
		var coins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
		var el = document.getElementById('pf-coins');
		if (el) el.textContent = coins;
	}
}

/* ── Subscription ── */
function loadSubscription(uid) {
	if (rtdb && uid) {
		rtdb.ref('users/' + uid + '/subscription').once('value', function(snap) {
			var sub = snap.val();
			if (sub && sub.status === 'active') {
				applySubUI(sub, true);
				return;
			}
			checkLocalSubscription();
		});
	} else {
		checkLocalSubscription();
	}
}

function checkLocalSubscription() {
	if (!currentUser) {
		// Demo / Guest mode: strictly Free Viewer. Demo cannot hold VIP.
		if (localStorage.getItem('short_subscription')) {
			localStorage.removeItem('short_subscription');
			localStorage.removeItem('short_sub_tier');
		}
	} else {
		try {
			var sub = JSON.parse(localStorage.getItem('short_subscription') || '{}');
			if (sub && sub.status === 'active') {
				applySubUI(sub, true);
				return;
			}
		} catch(e) {}
	}

	var tierEl = document.getElementById('pf-sub-tier');
	var expEl = document.getElementById('pf-sub-expires');
	var btnEl = document.getElementById('pf-sub-btn');
	var badge = document.getElementById('pf-badge');
	if (tierEl) tierEl.textContent = 'Free Viewer';
	if (expEl) expEl.textContent = 'Watch for free or unlock episodes with coins';
	if (btnEl) {
		btnEl.textContent = 'Upgrade to VIP';
		btnEl.href = '<?php echo esc_js( home_url('/subscription/') ); ?>';
		btnEl.onclick = null;
		btnEl.style.background = 'linear-gradient(135deg, #7c4dff, #ff2d55)';
	}
	if (!currentUser && badge) {
		badge.textContent = 'GUEST';
		badge.className = 'stv-badge';
		badge.style.background = '#4b5563';
	}
}

function applySubUI(sub, isLinked) {
	var tierEl = document.getElementById('pf-sub-tier');
	var expEl = document.getElementById('pf-sub-expires');
	var btnEl = document.getElementById('pf-sub-btn');
	var badge = document.getElementById('pf-badge');
	var tierName = sub.plan_name || (sub.plan_id ? sub.plan_id.toUpperCase() + ' VIP' : 'VIP Pass');

	if (tierEl) tierEl.textContent = tierName + (isLinked ? ' (Active)' : ' (Guest Session)');
	if (expEl) expEl.textContent = isLinked
		? (sub.expires_at || sub.expiresAt ? 'Expires: ' + fmt(sub.expires_at || sub.expiresAt) + ' • All Dramas Unlocked' : 'Active • All Dramas Unlocked')
		: '⚠️ Temporary browser pass. Sign in with Google to bind to your Gmail!';
	if (badge) {
		badge.textContent = 'VIP';
		badge.className = 'stv-badge vip';
		badge.style.background = 'linear-gradient(135deg, #4caf50, #00897b)';
	}
	if (btnEl) {
		if (isLinked) {
			btnEl.textContent = 'Manage Plan';
			btnEl.href = '<?php echo esc_js( home_url('/subscription/') ); ?>';
			btnEl.onclick = null;
		} else {
			btnEl.textContent = 'Bind to Google';
			btnEl.href = '#';
			btnEl.onclick = function(e) { e.preventDefault(); signInWithGoogle(); };
		}
	}
}

/* ── Auth State Change ── */
function onUserLoggedIn(user) {
	currentUser = user;
	var nameEl = document.getElementById('pf-name');
	var emailEl = document.getElementById('pf-email');
	var uidEl = document.getElementById('pf-uid');
	var badgeEl = document.getElementById('pf-badge');
	var googleBtn = document.getElementById('pf-google-signin-btn');
	var loginLink = document.getElementById('pf-login-link-btn');
	var settingsBtn = document.getElementById('pf-settings-btn');
	var signoutBtnHero = document.getElementById('pf-signout-btn-hero');
	var signoutBtn = document.getElementById('pf-signout-btn');
	var guestBar = document.getElementById('pf-guest-bar');
	var copyBtn = document.getElementById('pf-copy-btn');

	var email = user.email || localStorage.getItem('short_user_email') || '';
	var displayName = user.displayName || localStorage.getItem('short_user_display_name') || (email ? email.split('@')[0] : 'ShortTV Member');
	var uid = user.uid || localStorage.getItem('short_user_uid') || '';

	if (nameEl) nameEl.textContent = displayName;
	if (emailEl) emailEl.textContent = email || 'Signed In';
	if (badgeEl) {
		badgeEl.textContent = '✓ Verified';
		badgeEl.className = 'stv-badge';
		badgeEl.style.background = '#22c55e';
	}
	if (uidEl) uidEl.textContent = uid ? 'UID: ' + uid.substring(0, 14) + '…' : 'Verified Member';
	if (copyBtn) copyBtn.style.display = uid ? 'inline-block' : 'none';

	if (googleBtn) googleBtn.style.display = 'none';
	if (loginLink) loginLink.style.display = 'none';
	if (settingsBtn) settingsBtn.style.display = 'inline-flex';
	if (signoutBtnHero) signoutBtnHero.style.display = 'inline-flex';
	if (signoutBtn) signoutBtn.style.display = 'inline-flex';
	if (guestBar) guestBar.style.display = 'none';

	// Store user state in localStorage
	localStorage.setItem('short_is_logged_in', '1');
	if (email) localStorage.setItem('short_user_email', email);
	if (displayName) localStorage.setItem('short_user_display_name', displayName);
	if (uid) localStorage.setItem('short_user_uid', uid);

	// Init real profile avatar photo (ignore cartoon smileys)
	var avEl = document.getElementById('pf-avatar');
	var photo = user.photoURL || '';
	if (!photo && user.providerData && user.providerData.length) {
		for (var i = 0; i < user.providerData.length; i++) {
			if (user.providerData[i] && user.providerData[i].photoURL) {
				photo = user.providerData[i].photoURL;
				break;
			}
		}
	}
	if (!photo) {
		photo = localStorage.getItem('short_user_photo') || localStorage.getItem('short_active_avatar') || localStorage.getItem('shorttv_user_avatar') || '';
	}

	if (photo && (photo.includes('avatar-') || photo.includes('avatar_') || (photo.includes('/images/avatar') && photo.endsWith('.svg')))) {
		photo = '';
		localStorage.removeItem('shorttv_user_avatar');
		localStorage.removeItem('short_active_avatar');
	}

	if (photo) {
		localStorage.setItem('short_user_photo', photo);
		localStorage.setItem('short_active_avatar', photo);
		localStorage.setItem('shorttv_user_avatar', photo);
	}

	var fallbackSvg = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" fill="none"><circle cx="18" cy="18" r="18" fill="%23222228"/><circle cx="18" cy="13.5" r="5.5" fill="%23cbd5e1"/><path d="M8 29.5c0-5.5 4.5-9 10-9s10 3.5 10 9" fill="%23cbd5e1"/></svg>';
	var finalPhoto = photo || fallbackSvg;

	if (avEl) {
		avEl.src = finalPhoto;
	}
	var hdrAv = document.getElementById('header-current-avatar');
	if (hdrAv && finalPhoto) {
		hdrAv.src = finalPhoto;
	}

	// Load user data
	loadCoins(uid);
	loadRatings(uid);
	loadComments(uid);
	loadSubscription(uid);
}

function onGuestMode() {
	currentUser = null;
	var nameEl = document.getElementById('pf-name');
	var emailEl = document.getElementById('pf-email');
	var uidEl = document.getElementById('pf-uid');
	var badgeEl = document.getElementById('pf-badge');
	var googleBtn = document.getElementById('pf-google-signin-btn');
	var loginLink = document.getElementById('pf-login-link-btn');
	var settingsBtn = document.getElementById('pf-settings-btn');
	var signoutBtnHero = document.getElementById('pf-signout-btn-hero');
	var signoutBtn = document.getElementById('pf-signout-btn');
	var guestBar = document.getElementById('pf-guest-bar');
	var copyBtn = document.getElementById('pf-copy-btn');

	if (nameEl) nameEl.textContent = 'Guest Viewer';
	if (emailEl) emailEl.textContent = 'Sign in with Google to sync your coins, watch progress, and VIP pass permanently.';
	if (badgeEl) { badgeEl.textContent = 'GUEST'; badgeEl.className = 'stv-badge'; badgeEl.style.background = '#4b5563'; }
	if (uidEl) uidEl.textContent = 'Session: Guest (This Device)';
	if (copyBtn) copyBtn.style.display = 'none';

	if (googleBtn) googleBtn.style.display = 'inline-flex';
	if (loginLink) loginLink.style.display = 'inline-flex';
	if (settingsBtn) settingsBtn.style.display = 'none';
	if (signoutBtnHero) signoutBtnHero.style.display = 'none';
	if (signoutBtn) signoutBtn.style.display = 'none';
	if (guestBar) guestBar.style.display = 'flex';

	// Load local coins
	loadCoins(null);

	// Load local ratings & comments
	loadRatings(null);
	setCount('comments', 0);

	// Check subscription
	loadSubscription(null);
}

/* ── Init Firebase ── */
document.addEventListener('DOMContentLoaded', function() {
	// Clear any old smiley avatar stored locally
	var rawAv = localStorage.getItem('shorttv_user_avatar') || localStorage.getItem('short_active_avatar') || '';
	if (rawAv && (rawAv.includes('avatar-') || rawAv.includes('avatar_') || (rawAv.includes('/images/avatar') && rawAv.endsWith('.svg')))) {
		localStorage.removeItem('shorttv_user_avatar');
		localStorage.removeItem('short_active_avatar');
	}

	var checkedDate = localStorage.getItem('shorttv_last_checkin');
	var today = new Date().toDateString();
	if (checkedDate === today) {
		var btn = document.getElementById('pf-checkin-btn');
		if (btn) { btn.textContent = '✓ Claimed'; btn.disabled = true; btn.style.opacity = '0.5'; }
	}

	// Immediate hydration from localStorage if logged in
	var isLoggedIn = (localStorage.getItem('short_is_logged_in') === '1') || !!localStorage.getItem('short_user_email');
	var cachedEmail = localStorage.getItem('short_user_email') || '';
	var cachedName = localStorage.getItem('short_user_display_name') || (cachedEmail ? cachedEmail.split('@')[0] : '');
	var cachedUid = localStorage.getItem('short_user_uid') || '';
	var cachedPhoto = localStorage.getItem('short_user_photo') || localStorage.getItem('short_active_avatar') || localStorage.getItem('shorttv_user_avatar') || '';

	if (cachedPhoto && (cachedPhoto.includes('avatar-') || cachedPhoto.includes('avatar_') || (cachedPhoto.includes('/images/avatar') && cachedPhoto.endsWith('.svg')))) {
		cachedPhoto = '';
	}

	if (isLoggedIn && (cachedEmail || cachedName)) {
		onUserLoggedIn({
			displayName: cachedName,
			email: cachedEmail,
			uid: cachedUid || ('user_' + (cachedEmail || 'default').replace(/[^a-zA-Z0-9]/g, '_')),
			photoURL: cachedPhoto
		});
	} else {
		onGuestMode();
	}

	if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
		firebase.auth().onAuthStateChanged(function(user) {
			if (firebase.database && !rtdb) {
				rtdb = firebase.database();
			}
			if (user) {
				onUserLoggedIn(user);
			} else {
				var hasAuth = (localStorage.getItem('short_is_logged_in') === '1') || !!localStorage.getItem('short_user_email');
				if (!hasAuth) {
					onGuestMode();
				}
			}
		});
	}
});

/* ── Daily Check-in ── */
window.claimDailyCheckin = function() {
	var checkedDate = localStorage.getItem('shorttv_last_checkin');
	var today = new Date().toDateString();
	if (checkedDate === today) {
		alert('Already claimed today\'s coins! Come back tomorrow.');
		return;
	}
	var cur = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
	cur += 20;
	localStorage.setItem('shorttv_user_coins', String(cur));
	localStorage.setItem('shorttv_last_checkin', today);

	if (rtdb && currentUser) {
		rtdb.ref('users/' + currentUser.uid + '/coins').set(cur);
		var tx = { amount: 20, reason: 'Daily Check-in Bonus', ts: Date.now() };
		rtdb.ref('users/' + currentUser.uid + '/coinHistory').push(tx);
	}

	var el = document.getElementById('pf-coins');
	if (el) el.textContent = cur;
	var btn = document.getElementById('pf-checkin-btn');
	if (btn) { btn.textContent = '✓ Claimed!'; btn.disabled = true; btn.style.opacity = '0.5'; }
	alert('🎉 +20 Coins! New balance: ' + cur + ' 🪙');
};

/* ── 1-Click Google Sign-In ── */
window.signInWithGoogle = async function() {
	if (typeof firebase === 'undefined' || !firebase.auth) {
		alert('Firebase authentication is initializing. Please try again.');
		return;
	}
	try {
		var provider = new firebase.auth.GoogleAuthProvider();
		provider.setCustomParameters({ prompt: 'select_account' });
		var result = await firebase.auth().signInWithPopup(provider);
		if (result && result.user) {
			onUserLoggedIn(result.user);
		}
	} catch(err) {
		console.warn('[Profile Google Auth Error]', err);
		if (err.code !== 'auth/popup-closed-by-user') {
			alert('Sign-in failed: ' + (err.message || 'Please try again.'));
		}
	}
};

/* ── Utilities ── */
window.copyUid = function() {
	if (!currentUser || !currentUser.uid) {
		alert('Sign in to view and copy your permanent account ID!');
		return;
	}
	navigator.clipboard.writeText(currentUser.uid).then(function() {
		var btn = document.getElementById('pf-copy-btn');
		if (btn) { btn.textContent = '✓ Copied'; setTimeout(function(){ btn.textContent = 'Copy'; }, 1500); }
	}).catch(function(){});
};

window.handleSignOut = function() {
	if (!confirm('Sign out of your account?')) return;
	if (typeof firebase !== 'undefined' && firebase.apps && firebase.apps.length && firebase.auth) {
		firebase.auth().signOut().catch(function(){});
	}
	localStorage.removeItem('short_is_logged_in');
	localStorage.removeItem('short_user_email');
	localStorage.removeItem('short_user_display_name');
	localStorage.removeItem('short_user_uid');
	localStorage.removeItem('short_user_photo');
	localStorage.removeItem('shorttv_user_avatar');
	localStorage.removeItem('short_active_avatar');
	window.location.reload();
};

window.promptChangePhoto = function() {
	var current = localStorage.getItem('short_user_photo') || (currentUser && currentUser.photoURL) || '';
	var newUrl = prompt('Enter image URL for your profile photo (or leave empty for default):', current.startsWith('data:') ? '' : current);
	if (newUrl !== null) {
		newUrl = newUrl.trim();
		if (newUrl) {
			localStorage.setItem('short_user_photo', newUrl);
			localStorage.setItem('short_active_avatar', newUrl);
			localStorage.setItem('shorttv_user_avatar', newUrl);
			var avEl = document.getElementById('pf-avatar');
			if (avEl) avEl.src = newUrl;
			var hdrAv = document.getElementById('header-current-avatar');
			if (hdrAv) hdrAv.src = newUrl;
		} else {
			localStorage.removeItem('short_user_photo');
			localStorage.removeItem('shorttv_user_avatar');
			localStorage.removeItem('short_active_avatar');
			var fallback = (currentUser && currentUser.photoURL) ? currentUser.photoURL : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" fill="none"><circle cx="18" cy="18" r="18" fill="%23222228"/><circle cx="18" cy="13.5" r="5.5" fill="%23cbd5e1"/><path d="M8 29.5c0-5.5 4.5-9 10-9s10 3.5 10 9" fill="%23cbd5e1"/></svg>';
			var avEl = document.getElementById('pf-avatar');
			if (avEl) avEl.src = fallback;
			var hdrAv = document.getElementById('header-current-avatar');
			if (hdrAv) hdrAv.src = fallback;
		}
	}
};

})();
</script>

<?php get_footer(); ?>
