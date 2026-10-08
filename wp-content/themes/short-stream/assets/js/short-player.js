/**
 * Short Modern Video Player Module
 *
 * Replaces default player with modern UI matching reference design:
 * Features: Screen lock, Top media title, Playlist drawer, 3-dots More options menu,
 * Left Brightness vertical slider, Right Volume vertical slider, Neon green progress bar,
 * Screenshot capture, Mute/Unmute, Replay 10, Play/Pause circle, Forward 10, Subtitles CC, Fullscreen.
 *
 * @version 2.0.0
 * @since 1.2.0
 */
var shortPlayer = (function () {
  'use strict';

  // ─── State ──────────────────────────────────────────────────────────
  var state = {
    hls: null,
    videoEl: null,
    iframeEl: null,
    playerContainer: null,
    sources: [],
    subtitles: [],
    seasons: [],
    currentSourceIndex: 0,
    currentQualityLevel: -1, // -1 = auto
    userTier: 'free',
    isNative: false,        // true if playing HLS/MP4, false if iframe
    isLocked: false,        // screen lock active
    brightness: 1.0,        // 0.2 to 2.0 (1.0 = 100%)
    volume: 1.0,            // 0.0 to 1.0
    playbackRate: 1.0,
    aspectRatio: 'contain',
    controlsTimeout: null,
    controlsVisible: true,
    progressInterval: null,
    countdownInterval: null,
    countdownDismissed: false,
    isInitialized: false,
  };

  // ─── Quality Tier Map ───────────────────────────────────────────────
  var TIER_MAX = {
    free: 360,
    basic: 720,
    standard: 1080,
    premium: 2160,
  };

  var TIER_LABELS = {
    free: 'Free',
    basic: 'Basic',
    standard: 'Standard',
    premium: 'Premium',
  };

  // ─── Loading Overlay Helpers ────────────────────────────────────────
  function showLoader(text) {
    var overlay = document.getElementById('short-loading-overlay');
    var textEl  = document.getElementById('short-loading-text');
    if (textEl && text) textEl.textContent = text;
    if (overlay) overlay.classList.remove('hidden');
  }

  function hideLoader() {
    var overlay = document.getElementById('short-loading-overlay');
    if (overlay) overlay.classList.add('hidden');
  }

  // ─── Background Native Stream Resolver ──────────────────────────────
  function resolveNativeSourcesInBackground() {
    var container = state.playerContainer;
    if (!container) return;

    var tmdbId      = container.getAttribute('data-tmdb-id');
    var contentType = container.getAttribute('data-content-type') || 'movie';
    var season      = container.getAttribute('data-season') || '0';
    var episode     = container.getAttribute('data-episode') || '0';
    var userTier    = state.userTier || 'free';

    var siteBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.site_url) ? window.SHORT_CONFIG.site_url : (window.location.origin + (window.location.pathname.startsWith('/wordpress') ? '/wordpress' : ''));
    var restUrl  = (window.SHORT_CONFIG && window.SHORT_CONFIG.rest_url) ? window.SHORT_CONFIG.rest_url : (siteBase + '/wp-json/short/v1');
    var apiUrl   = restUrl + '/sources/' + contentType + '/' + tmdbId + '?season=' + season + '&episode=' + episode + '&tier=' + userTier;

    var attempts = 0;
    var maxAttempts = 6;

    function attemptFetch() {
      attempts++;
      fetch(apiUrl)
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data && data.sources && data.sources.length > 0) {
            var hasNative = data.sources.some(function (s) { return s.type === 'hls' || s.type === 'mp4'; });
            if (hasNative) {
              state.sources = data.sources;
              if (data.subtitles && data.subtitles.length > 0) {
                state.subtitles = data.subtitles;
              }
              state.isNative = true;
              showNativeControls();
              loadSource(0);
              return;
            }
          }
          if (attempts < maxAttempts) {
            setTimeout(attemptFetch, 1500);
          } else {
            hideLoader();
            if (state.sources.length > 0 && !state.isNative) {
              loadSource(0);
            }
          }
        })
        .catch(function () {
          if (attempts < maxAttempts) {
            setTimeout(attemptFetch, 1800);
          } else {
            hideLoader();
            if (state.sources.length > 0 && !state.isNative) {
              loadSource(0);
            }
          }
        });
    }

    attemptFetch();
  }

  // ─── Initialization ────────────────────────────────────────────────
  function init() {
    if (state.isInitialized) return;

    var container = document.getElementById('short-player-app');
    if (!container) return;

    state.playerContainer = container;
    state.userTier = container.getAttribute('data-user-tier') || 'free';

    // Parse sources, subtitles, and seasons from data attributes
    try {
      state.sources = JSON.parse(container.getAttribute('data-sources') || '[]');
    } catch (e) {
      state.sources = [];
    }
    try {
      state.subtitles = JSON.parse(container.getAttribute('data-subtitles') || '[]');
    } catch (e) {
      state.subtitles = [];
    }
    try {
      state.seasons = JSON.parse(container.getAttribute('data-seasons') || '[]');
    } catch (e) {
      state.seasons = [];
    }
    try {
      state.episodes = JSON.parse(container.getAttribute('data-episodes') || '[]');
    } catch (e) {
      state.episodes = [];
    }

    // Inject modern player controls HTML
    injectPlayerControls();

    // Register initial watch progress immediately on page entry
    var rawSeriesId = container.getAttribute('data-series-id') || container.getAttribute('data-id') || container.getAttribute('data-post-id') || container.getAttribute('data-tmdb-id') || '0';
    var initialMeta = {
      id:            parseInt(rawSeriesId, 10) || rawSeriesId,
      series_id:     parseInt(rawSeriesId, 10) || rawSeriesId,
      post_id:       parseInt(rawSeriesId, 10) || rawSeriesId,
      content_type:  container.getAttribute('data-content-type') || 'tv',
      season:        parseInt(container.getAttribute('data-season') || '0', 10),
      episode:       parseInt(container.getAttribute('data-episode') || '1', 10),
      title:         container.getAttribute('data-title') || 'Untitled',
      poster_path:   container.getAttribute('data-poster') || '',
      backdrop_path: container.getAttribute('data-backdrop') || '',
      vote_average:  container.getAttribute('data-rating') || '',
      year:          container.getAttribute('data-year') || '',
    };
    if (initialMeta.id) {
      saveProgress(initialMeta, {
        currentTime: 30,
        duration: 5400,
        percent: 5
      });
    }

    // Determine if first source is native (HLS/MP4) or iframe
    var hasNativeSource = false;
    if (state.sources.length > 0) {
      var firstSource = state.sources[0];
      state.isNative = (firstSource.type !== 'iframe');
      hasNativeSource = state.isNative;
    }

    // Handle paywall
    if (handlePaywall()) return;

    if (state.sources.length > 0) {
      loadSource(0);
    }

    // Bind events
    bindControls();
    bindVerticalSliders();
    bindScreenLock();
    bindMoreOptionsModal();
    bindPlaylistDrawer();
    bindSkipIntro();
    bindNextEpisode();
    bindFullscreen();
    bindKeyboard();
    bindControlsAutoHide();

    state.isInitialized = true;
  }

  // ─── Inject Player Controls HTML ───────────────────────────────────
  function injectPlayerControls() {
    var viewport = document.getElementById('player-viewport');
    if (!viewport || document.getElementById('short-controls-overlay')) return;

    var container = state.playerContainer;
    var title = container.getAttribute('data-title') || 'Now Playing';
    var isTV = container.getAttribute('data-content-type') === 'tv';

    var html = '';

    // Floating Screen Lock Unlock Badge (only visible when screen is locked)
    html += '<button type="button" class="short-screen-lock-badge" id="short-screen-lock-badge" style="display:none;">';
    html += '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
    html += '<span>Tap to Unlock</span>';
    html += '</button>';

    // Main Controls Overlay (Uses short-iframe-mode for iframe sources to show top bar and options)
    var isIframeFirst = (state.sources.length > 0 && state.sources[0].type === 'iframe');
    html += '<div class="short-controls-overlay' + (isIframeFirst ? ' short-iframe-mode' : '') + '" id="short-controls-overlay">';

    // ── Top Header Bar
    html += '<div class="short-header-bar" id="short-header-bar">';

    // Left: Back button + Screen Lock
    var tmdbId   = container.getAttribute('data-tmdb-id') || '';
    var siteBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.site_url) ? window.SHORT_CONFIG.site_url : (window.location.origin + (window.location.pathname.startsWith('/wordpress') ? '/wordpress' : ''));
    var backUrl  = container.getAttribute('data-back-url') || (siteBase + (isTV ? ('/tv/' + tmdbId) : ('/movie/' + tmdbId)));
    html += '<div class="short-header-left">';
    html += '<a href="' + backUrl + '" class="short-icon-btn short-btn-back" id="short-btn-back" title="Back" style="margin-right: 4px;">';
    html += '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>';
    html += '</a>';
    html += '<button type="button" class="short-icon-btn" id="short-btn-lock" title="Lock Screen">';
    html += '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
    html += '</button>';
    html += '</div>';

    // Center: Media Name
    html += '<div class="short-header-center">';
    html += '<h1 class="short-media-title" id="short-media-title">' + escapeHtml(title) + '</h1>';
    html += '</div>';

    // Right: Playlist & More Options (3 Dots)
    html += '<div class="short-header-right">';
    if (isTV) {
      html += '<button type="button" class="short-icon-btn" id="short-btn-playlist" title="Episodes">';
      html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h10v2H4zm0 5h10v2H4zm0 5h6v2H4zm14-5l6 4-6 4z"/></svg>';
      html += '</button>';
    }
    html += '<button type="button" class="short-icon-btn" id="short-btn-more" title="More Options">';
    html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>';
    html += '</button>';
    html += '</div>';

    html += '</div>'; // end short-header-bar

    // ── Left Vertical Slider (Brightness)
    html += '<div class="short-vslider-wrap short-vslider-left" id="short-vslider-brightness">';
    html += '<span class="short-vslider-val" id="short-brightness-val">100%</span>';
    html += '<div class="short-vslider-track" id="short-brightness-track" title="Drag to adjust Brightness">';
    html += '<div class="short-vslider-fill" id="short-brightness-fill" style="height: 50%;"></div>';
    html += '</div>';
    html += '<div class="short-vslider-icon">';
    html += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
    html += '</div>';
    html += '</div>';

    // ── Right Vertical Slider (Volume)
    html += '<div class="short-vslider-wrap short-vslider-right" id="short-vslider-volume">';
    html += '<span class="short-vslider-val" id="short-volume-val">100%</span>';
    html += '<div class="short-vslider-track" id="short-volume-track" title="Drag to adjust Volume">';
    html += '<div class="short-vslider-fill" id="short-volume-fill" style="height: 100%;"></div>';
    html += '</div>';
    html += '<div class="short-vslider-icon">';
    html += '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>';
    html += '</div>';
    html += '</div>';

    // ── Bottom Wrapper (Progress Bar + Main Controls)
    html += '<div class="short-bottom-wrapper" id="short-bottom-wrapper">';

    // Progress Bar Row (Left timestamp, Green track, Right timestamp)
    html += '<div class="short-progress-row">';
    html += '<span class="short-time-pill" id="short-time-current">00:00</span>';
    html += '<div class="short-progress-container" id="short-progress-container">';
    html += '<div class="short-progress-track" id="short-progress-track">';
    html += '<div class="short-progress-buffered" id="short-progress-buffered"></div>';
    html += '<div class="short-progress-filled" id="short-progress-filled"></div>';
    html += '<div class="short-progress-thumb" id="short-progress-thumb"></div>';
    html += '</div>';
    html += '</div>';
    html += '<span class="short-time-pill" id="short-time-duration">00:00</span>';
    html += '</div>';

    // Main Controls Row
    html += '<div class="short-main-controls-row">';

    // Left Controls: Screenshot, Mute
    html += '<div class="short-ctrls-left">';
    html += '<button type="button" class="short-icon-btn" id="short-btn-screenshot" title="Take Screenshot">';
    html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/><line x1="19" y1="11" x2="19" y2="15"/><line x1="17" y1="13" x2="21" y2="13"/></svg>';
    html += '</button>';
    html += '<button type="button" class="short-icon-btn" id="short-btn-mute" title="Mute/Unmute">';
    html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="short-vol-on"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
    html += '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="short-vol-off" style="display:none;"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>';
    html += '</button>';
    html += '</div>';

    // Center Controls: Prev Ep, Replay 10, Circular Play/Pause, Forward 10, Next Ep
    var prevUrl = container.getAttribute('data-prev-url') || '';
    var nextUrl = container.getAttribute('data-next-url') || '';

    html += '<div class="short-ctrls-center">';
    if (isTV && prevUrl) {
      html += '<a href="' + prevUrl + '" class="short-btn-skip-10 short-btn-ep-nav" id="short-btn-prev-ep" title="Previous Episode">';
      html += '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><polygon points="19 20 9 12 19 4 19 20"/><line x1="5" y1="19" x2="5" y2="5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>';
      html += '</a>';
    }
    html += '<button type="button" class="short-btn-skip-10" id="short-btn-rewind" title="Replay 10s">';
    html += '<svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M12.5 8c-3.6 0-6.5 2.9-6.5 6.5s2.9 6.5 6.5 6.5 6.5-2.9 6.5-6.5H21c0 4.7-3.8 8.5-8.5 8.5S4 19.7 4 15s3.8-8.5 8.5-8.5V3L17 7l-4.5 4V8z"/><text x="12.5" y="17.5" text-anchor="middle" font-size="7" font-weight="bold" fill="currentColor">10</text></svg>';
    html += '</button>';
    html += '<button type="button" class="short-btn-play-pause-main" id="short-play-pause-center" title="Play/Pause">';
    html += '<svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" class="short-icon-play"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
    html += '<svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor" class="short-icon-pause" style="display:none;"><rect x="6" y="4" width="4" height="16" rx="1"></rect><rect x="14" y="4" width="4" height="16" rx="1"></rect></svg>';
    html += '</button>';
    html += '<button type="button" class="short-btn-skip-10" id="short-btn-forward" title="Forward 10s">';
    html += '<svg width="34" height="34" viewBox="0 0 24 24" fill="currentColor"><path d="M11.5 8c3.6 0 6.5 2.9 6.5 6.5s-2.9 6.5-6.5 6.5S5 18.1 5 14.5H3c0 4.7 3.8 8.5 8.5 8.5s8.5-3.8 8.5-8.5S16.2 6 11.5 6V2L7 6l4.5 4V8z"/><text x="11.5" y="17.5" text-anchor="middle" font-size="7" font-weight="bold" fill="currentColor">10</text></svg>';
    html += '</button>';
    if (isTV && nextUrl) {
      html += '<a href="' + nextUrl + '" class="short-btn-skip-10 short-btn-ep-nav" id="short-btn-next-ep" title="Next Episode">';
      html += '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>';
      html += '</a>';
    }
    html += '</div>';

    // Right Controls: Dubbing (Audio), Subtitles (CC), Fullscreen
    html += '<div class="short-ctrls-right">';
    html += '<button type="button" class="short-btn-dub" id="short-btn-audio" title="Audio & Dubbing">DUB</button>';
    html += '<button type="button" class="short-btn-cc" id="short-btn-subtitles" title="Subtitles">CC</button>';
    html += '<button type="button" class="short-icon-btn" id="ctrl-fullscreen" title="Fullscreen">';
    html += '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>';
    html += '</button>';
    html += '</div>';

    html += '</div>'; // end short-main-controls-row
    html += '</div>'; // end short-bottom-wrapper

    // ── More Options Modal (`⋮`)
    html += '<div class="short-more-modal" id="short-more-modal" style="display:none;">';
    
    // Modal Header (Close button for mobile and drawer view)
    html += '<div class="short-more-top-bar" id="short-more-top-bar">';
    html += '<span class="short-more-heading">Options & Servers</span>';
    html += '<button type="button" class="short-more-close-btn" id="short-more-close-btn" aria-label="Close Options">';
    html += '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
    html += '</button>';
    html += '</div>';

    // Server Switcher Section (Primary for Iframe and Native)
    html += '<div class="short-more-section" id="section-servers">';
    html += '<div class="short-more-title">Select Server / Source</div>';
    html += '<div class="dropdown-items-list" id="list-servers"></div>';
    html += '</div>';

    // Ad Blocker (Sandbox) Section
    html += '<div class="short-more-section" id="section-ad-blocker">';
    html += '<div class="short-more-title" style="display:flex; align-items:center; justify-content:space-between;">';
    html += '<span>🛡️ Block Popup Ads</span>';
    html += '<span class="short-sandbox-status-badge is-on" id="short-sandbox-status-badge" style="font-size:0.75rem; padding:2px 8px; border-radius:4px; font-weight:700;">ON</span>';
    html += '</div>';
    html += '<div style="font-size:0.8rem; color:#94a3b8; margin:4px 0 8px 0; line-height:1.4;">';
    html += 'Blocks aggressive redirect tabs & popup ads from iframe embeds.';
    html += '</div>';
    html += '<button type="button" class="short-sandbox-toggle-btn" id="short-sandbox-toggle-btn" style="width:100%; padding:9px 14px; border-radius:8px; border:1px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.08); color:#fff; font-size:0.86rem; font-weight:600; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; transition:all 0.2s ease;">';
    html += '<span id="short-sandbox-toggle-text">Disable Ad Blocker</span>';
    html += '</button>';
    html += '</div>';

    // Quality Section (Native only)
    html += '<div class="short-more-section short-native-only" id="section-quality">';
    html += '<div class="short-more-title">Quality</div>';
    html += '<ul class="short-popup-list" id="short-quality-list"></ul>';
    html += '</div>';

    // Playback Speed Section (Native only)
    html += '<div class="short-more-section short-native-only" id="section-speed">';
    html += '<div class="short-more-title">Playback Speed</div>';
    html += '<div class="short-speed-pills">';
    html += '<div class="short-speed-pill" data-speed="0.5">0.5x</div>';
    html += '<div class="short-speed-pill" data-speed="0.75">0.75x</div>';
    html += '<div class="short-speed-pill active" data-speed="1.0">1.0x</div>';
    html += '<div class="short-speed-pill" data-speed="1.25">1.25x</div>';
    html += '<div class="short-speed-pill" data-speed="1.5">1.5x</div>';
    html += '<div class="short-speed-pill" data-speed="2.0">2.0x</div>';
    html += '</div>';
    html += '</div>';

    // Audio Language Section (Native only)
    html += '<div class="short-more-section short-native-only" id="section-audio">';
    html += '<div class="short-more-title">Audio Language</div>';
    html += '<div class="dropdown-items-list" id="list-languages"></div>';
    html += '</div>';

    // Video Fit Section (Native only)
    html += '<div class="short-more-section short-native-only" id="section-fit">';
    html += '<div class="short-more-title">Aspect Ratio / Fit</div>';
    html += '<div class="short-speed-pills">';
    html += '<div class="short-speed-pill active" data-fit="contain">Fit</div>';
    html += '<div class="short-speed-pill" data-fit="cover">Fill Screen</div>';
    html += '<div class="short-speed-pill" data-fit="fill">Stretch</div>';
    html += '</div>';
    html += '</div>';

    // Back to Media Details Section
    html += '<div class="short-more-section" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">';
    html += '<a href="' + backUrl + '" class="short-more-back-btn" id="short-more-back-btn" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:10px; border-radius:8px; background:rgba(255,255,255,0.06); color:#cbd5e1; text-decoration:none; font-size:0.88rem; font-weight:600; box-sizing:border-box;">';
    html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>';
    html += '<span>Back to Details Page</span>';
    html += '</a>';
    html += '</div>';

    html += '</div>'; // end short-more-modal

    // ── Audio / Dubbing Picker Menu (DUB)
    html += '<div class="short-popup-menu" id="short-audio-menu" style="display:none; bottom: 85px; right: 80px;">';
    html += '<div class="short-popup-header">Audio & Dubbing</div>';
    html += '<ul class="short-popup-list" id="short-audio-list"></ul>';
    html += '</div>';

    // ── Subtitle Picker Menu (CC)
    html += '<div class="short-popup-menu" id="short-subtitle-menu" style="display:none; bottom: 85px; right: 32px;">';
    html += '<div class="short-popup-header">Subtitles</div>';
    html += '<ul class="short-popup-list" id="short-subtitle-list"></ul>';
    html += '</div>';

    // ── Playlist / Episodes Drawer (`≡▶`)
    html += '<div class="short-playlist-drawer" id="short-playlist-drawer">';
    html += '<div class="short-playlist-header">';
    html += '<div class="short-playlist-header-left" id="short-playlist-header-left"><h3 class="short-playlist-title">Episodes</h3></div>';
    html += '<button type="button" class="short-playlist-close" id="short-playlist-close"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
    html += '</div>';
    html += '<div class="short-playlist-body" id="short-playlist-body"></div>';
    html += '</div>';

    html += '</div>'; // end short-controls-overlay

    viewport.insertAdjacentHTML('beforeend', html);
  }

  // ─── Source Loading ─────────────────────────────────────────────────
  function loadSource(index) {
    if (index < 0 || index >= state.sources.length) return;

    var source = state.sources[index];
    state.currentSourceIndex = index;

    // Destroy existing HLS instance
    destroyHls();

    var videoEl  = document.getElementById('player-html5-video');
    var iframeEl = document.getElementById('player-stream-iframe');

    if (source.type === 'iframe') {
      // Iframe mode
      state.isNative = false;
      hideLoader();
      if (iframeEl) {
        var stored = localStorage.getItem('short_iframe_sandbox');
        var sandboxEnabled = (stored !== null) ? (stored === '1') : (state.playerContainer && state.playerContainer.getAttribute('data-sandbox-enabled') === '1');
        if (sandboxEnabled) {
          iframeEl.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-presentation allow-downloads');
        } else {
          iframeEl.removeAttribute('sandbox');
        }
        iframeEl.src = source.url;
        iframeEl.style.display = 'block';
      }
      if (videoEl) {
        videoEl.pause();
        videoEl.removeAttribute('src');
        videoEl.style.display = 'none';
      }
      hideNativeControls();
      updateServerListActiveUI(index);

      // Start progress tracking for iframe playback
      startIframeProgressTracking();
    } else {
      // Native HLS/MP4 mode
      state.isNative = true;
      showLoader('Connecting to high-speed stream...');
      if (iframeEl) {
        iframeEl.removeAttribute('src');
        iframeEl.style.display = 'none';
      }
      if (videoEl) {
        videoEl.style.display = 'block';
        state.videoEl = videoEl;
      }
      showNativeControls();
      updateServerListActiveUI(index);

      if (source.type === 'hls' && window.Hls && Hls.isSupported()) {
        loadHLS(source.url, videoEl);
      } else if (source.type === 'hls' && videoEl.canPlayType('application/vnd.apple.mpegurl')) {
        videoEl.src = source.url;
        videoEl.play().catch(function () {});
      } else {
        videoEl.src = source.url;
        videoEl.play().catch(function () {});
      }

      // Load subtitles
      loadSubtitles(videoEl);

      // Resume watch progress
      resumePlayback(videoEl);

      // Start progress tracking
      startProgressTracking(videoEl);
    }
  }

  // ─── HLS.js Integration ────────────────────────────────────────────
  function loadHLS(url, videoEl) {
    if (!window.Hls || !Hls.isSupported()) return;

    var maxHeight = TIER_MAX[state.userTier] || 360;

    var hls = new Hls({
      maxMaxBufferLength: 30,
      startLevel: -1, // Auto quality
      capLevelToPlayerSize: true,
      enableWorker: true,
      lowLatencyMode: false,
      renderTextTracksNatively: false,
      xhrSetup: function (xhr, requestUrl) {
        // Ensure all relative or proxy chunk requests route to CinePro server
        if (requestUrl && (requestUrl.startsWith('/v1/proxy') || (requestUrl.indexOf('localhost/v1/proxy') !== -1 && requestUrl.indexOf('localhost:3000') === -1))) {
          var fixedUrl = requestUrl.startsWith('http') 
            ? requestUrl.replace('localhost/v1/proxy', 'localhost:3000/v1/proxy') 
            : ('http://localhost:3000' + requestUrl);
          xhr.open('GET', fixedUrl, true);
        }
      }
    });

    state.hls = hls;

    hls.loadSource(url);
    hls.attachMedia(videoEl);

    hls.on(Hls.Events.MANIFEST_PARSED, function (event, data) {
      hideLoader();
      var paywallEnabled = state.playerContainer && state.playerContainer.getAttribute('data-paywall-enabled') === '1';
      if (paywallEnabled && data.levels && data.levels.length > 0) {
        var highestAllowedIdx = -1;
        var maxAllowedHeight = 0;
        data.levels.forEach(function (lvl, idx) {
          if (lvl.height <= maxHeight && lvl.height >= maxAllowedHeight) {
            maxAllowedHeight = lvl.height;
            highestAllowedIdx = idx;
          }
        });

        if (highestAllowedIdx !== -1 && hls.autoLevelCapping !== undefined) {
          hls.autoLevelCapping = highestAllowedIdx;
        }
      }

      // Attempt playback
      var playPromise = videoEl.play();
      if (playPromise !== undefined) {
        playPromise.catch(function (err) {
          console.warn('[shortPlayer] Unmuted autoplay blocked by browser, falling back to muted autoplay:', err);
          hideLoader();
          videoEl.muted = true;
          videoEl.play().then(function () {
            var volOn  = document.querySelector('.short-vol-on');
            var volOff = document.querySelector('.short-vol-off');
            if (volOn && volOff) {
              volOn.style.display = 'none';
              volOff.style.display = 'block';
            }
            showToast('Click anywhere to Unmute Audio');
          }).catch(function () {
            // User gesture strictly required
            videoEl.muted = false;
            hideLoader();
          });
        });
      }

      populateQualityMenu(data.levels || hls.levels);

      if (hls.audioTracks && hls.audioTracks.length > 0) {
        populateHlsAudioTracks(hls.audioTracks);
      }
      if (hls.subtitleTracks && hls.subtitleTracks.length > 0) {
        populateHlsSubtitleMenu(hls.subtitleTracks);
      }
    });

    hls.on(Hls.Events.FRAG_BUFFERED, function () {
      hideLoader();
    });

    hls.on(Hls.Events.FRAG_LOADED, function () {
      hideLoader();
    });

    hls.on(Hls.Events.LEVEL_SWITCHED, function (event, data) {
      updateQualityActiveUI(data.level);
    });

    hls.on(Hls.Events.AUDIO_TRACKS_UPDATED, function (event, data) {
      populateHlsAudioTracks(data.audioTracks || hls.audioTracks);
    });

    hls.on(Hls.Events.AUDIO_TRACK_SWITCHED, function (event, data) {
      var trackId = (data && data.id !== undefined) ? data.id : (state.hls ? state.hls.audioTrack : 0);
      updateAudioActiveUI(trackId);
    });

    hls.on(Hls.Events.SUBTITLE_TRACKS_UPDATED, function (event, data) {
      populateHlsSubtitleMenu(data.subtitleTracks || hls.subtitleTracks);
    });

    var networkRetries = 0;
    hls.on(Hls.Events.ERROR, function (event, data) {
      if (data.fatal) {
        switch (data.type) {
          case Hls.ErrorTypes.NETWORK_ERROR:
            networkRetries++;
            if (networkRetries <= 2) {
              showLoader('Reconnecting stream...');
              hls.startLoad();
            } else {
              destroyHls();
              if (state.currentSourceIndex + 1 < state.sources.length) {
                showToast('Stream unavailable, switching to fallback server...');
                loadSource(state.currentSourceIndex + 1);
              } else {
                hideLoader();
              }
            }
            break;
          case Hls.ErrorTypes.MEDIA_ERROR:
            showLoader('Buffering stream...');
            hls.recoverMediaError();
            break;
          default:
            destroyHls();
            if (state.currentSourceIndex + 1 < state.sources.length) {
              showToast('Switching to fallback server...');
              loadSource(state.currentSourceIndex + 1);
            } else {
              hideLoader();
            }
            break;
        }
      }
    });
  }

  function destroyHls() {
    if (state.hls) {
      state.hls.destroy();
      state.hls = null;
    }
  }

  // ─── Quality Menu ──────────────────────────────────────────────────
  function populateQualityMenu(levels) {
    var list = document.getElementById('short-quality-list');
    if (!list || !levels || !levels.length) return;

    list.innerHTML = '';
    var maxHeight = TIER_MAX[state.userTier] || 360;

    // Auto option
    var autoLi = document.createElement('li');
    autoLi.className = 'short-quality-item' + (state.currentQualityLevel === -1 ? ' active' : '');
    autoLi.setAttribute('data-level', '-1');
    autoLi.innerHTML = '<div class="short-q-left"><span class="short-q-check" style="visibility:' + (state.currentQualityLevel === -1 ? 'visible' : 'hidden') + ';">✓</span> <span>Auto</span></div><span class="short-q-badge">Recommended</span>';
    autoLi.addEventListener('click', function () {
      setQuality(-1);
    });
    list.appendChild(autoLi);

    var sortedLevels = levels.map(function (l, i) {
      return { index: i, height: l.height || 0, bitrate: l.bitrate };
    }).sort(function (a, b) {
      return b.height - a.height;
    });

    sortedLevels.forEach(function (level) {
      var li = document.createElement('li');
      var isActive = (state.currentQualityLevel === level.index);
      li.className = 'short-quality-item' + (isActive ? ' active' : '');
      li.setAttribute('data-level', level.index);

      var label = (level.height || 720) + 'p';
      if (level.height >= 2160) label = '4K Ultra HD';
      else if (level.height >= 1080) label += ' Full HD';
      else if (level.height >= 720) label += ' HD';

      var isLocked = level.height > maxHeight;

      if (isLocked) {
        var reqTier = getTierForHeight(level.height);
        li.className += ' locked';
        li.innerHTML = '<div class="short-q-left"><span class="short-q-lock">🔒</span> <span>' + label + '</span></div><span class="short-q-upgrade">Upgrade to ' + TIER_LABELS[reqTier] + '</span>';
        li.addEventListener('click', function (e) {
          e.preventDefault();
          showToast('Upgrade to ' + TIER_LABELS[reqTier] + ' to unlock ' + label);
        });
      } else {
        li.innerHTML = '<div class="short-q-left"><span class="short-q-check" style="visibility:' + (isActive ? 'visible' : 'hidden') + ';">✓</span> <span>' + label + '</span></div>';
        li.addEventListener('click', function () {
          setQuality(level.index);
        });
      }

      list.appendChild(li);
    });
  }

  function setQuality(levelIndex) {
    if (state.hls) {
      state.hls.currentLevel = levelIndex;
      state.hls.loadLevel = levelIndex;
      state.currentQualityLevel = levelIndex;
    }
    updateQualityActiveUI(levelIndex);
    var moreModal = document.getElementById('short-more-modal');
    if (moreModal) moreModal.style.display = 'none';

    var qText = 'Auto';
    if (levelIndex >= 0 && state.hls && state.hls.levels && state.hls.levels[levelIndex]) {
      qText = (state.hls.levels[levelIndex].height || 720) + 'p';
    }
    showToast('Quality: ' + qText);
  }

  function updateQualityActiveUI(levelIndex) {
    var items = document.querySelectorAll('.short-quality-item');
    items.forEach(function (item) {
      var lvl = parseInt(item.getAttribute('data-level'), 10);
      var check = item.querySelector('.short-q-check');
      if (lvl === levelIndex) {
        item.classList.add('active');
        if (check) check.style.visibility = 'visible';
      } else {
        item.classList.remove('active');
        if (check) check.style.visibility = 'hidden';
      }
    });
  }

  function getTierForHeight(height) {
    if (height <= 360) return 'free';
    if (height <= 720) return 'basic';
    if (height <= 1080) return 'standard';
    return 'premium';
  }

  // ─── Subtitles Helper ──────────────────────────────────────────────
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function populateSubtitlesMenu(hlsTracks) {
    var list = document.getElementById('short-subtitle-list');
    if (!list) return;

    list.innerHTML = '';
    var currentTrack = (state.hls ? state.hls.subtitleTrack : -1);
    var isOff = (currentTrack === -1);

    var offLi = document.createElement('li');
    offLi.className = 'short-subtitle-item' + (isOff ? ' active' : '');
    offLi.setAttribute('data-index', '-1');
    offLi.innerHTML = '<div class="short-q-left"><span class="short-q-check" style="visibility:' + (isOff ? 'visible' : 'hidden') + ';">✓</span> <span>Off</span></div>';
    offLi.addEventListener('click', function () {
      setSubtitle(-1);
    });
    list.appendChild(offLi);

    if (hlsTracks && hlsTracks.length > 0) {
      hlsTracks.forEach(function (track, i) {
        var li = document.createElement('li');
        var isActive = (currentTrack === i);
        li.className = 'short-subtitle-item' + (isActive ? ' active' : '');
        li.setAttribute('data-index', i);
        var label = track.name || (track.lang ? track.lang.toUpperCase() : ('Subtitle ' + (i + 1)));
        li.innerHTML = '<div class="short-q-left"><span class="short-q-check" style="visibility:' + (isActive ? 'visible' : 'hidden') + ';">✓</span> <span>' + escapeHtml(label) + '</span></div>';
        li.addEventListener('click', function () {
          setSubtitle(i);
        });
        list.appendChild(li);
      });
    } else if (state.subtitles && state.subtitles.length > 0) {
      state.subtitles.forEach(function (sub, i) {
        var li = document.createElement('li');
        li.className = 'short-subtitle-item';
        li.setAttribute('data-index', i);
        li.innerHTML = '<div class="short-q-left"><span class="short-q-check" style="visibility:hidden;">✓</span> <span>' + escapeHtml(sub.label || 'Track ' + (i + 1)) + '</span></div>';
        li.addEventListener('click', function () {
          setSubtitle(i);
        });
        list.appendChild(li);
      });
    }
  }

  function populateHlsSubtitleMenu(hlsTracks) {
    return populateSubtitlesMenu(hlsTracks);
  }

  // ─── Subtitle Manager ──────────────────────────────────────────────
  function loadSubtitles(videoEl) {
    if (!videoEl) return;

    var existingTracks = videoEl.querySelectorAll('track');
    existingTracks.forEach(function (t) { t.remove(); });

    var subs = state.subtitles || [];
    if (!subs.length) {
      var currentSource = state.sources[state.currentSourceIndex];
      if (currentSource && currentSource.subtitles && typeof currentSource.subtitles === 'string' && currentSource.subtitles.length > 0) {
        subs = [{ url: currentSource.subtitles, label: 'English', srclang: 'en', default: true }];
      }
    }

    subs.forEach(function (sub, i) {
      var track = document.createElement('track');
      track.kind = 'subtitles';
      track.src = sub.url;
      track.label = sub.label || 'Subtitle ' + (i + 1);
      track.srclang = sub.srclang || 'en';
      if (sub.default) track.default = true;
      videoEl.appendChild(track);
    });

    populateHlsSubtitleMenu(state.hls ? state.hls.subtitleTracks : null);
  }

  function setSubtitle(trackIndex) {
    if (state.hls && state.hls.subtitleTracks && state.hls.subtitleTracks.length > 0) {
      state.hls.subtitleTrack = trackIndex;
      state.hls.subtitleDisplay = (trackIndex >= 0);
    }

    var videoEl = state.videoEl || document.getElementById('player-html5-video');
    if (videoEl && videoEl.textTracks) {
      for (var i = 0; i < videoEl.textTracks.length; i++) {
        videoEl.textTracks[i].mode = (i === trackIndex) ? 'showing' : 'hidden';
      }
    }

    var ccBtn = document.getElementById('short-btn-subtitles');
    if (ccBtn) {
      if (trackIndex >= 0) ccBtn.classList.add('active');
      else ccBtn.classList.remove('active');
    }

    var items = document.querySelectorAll('.short-subtitle-item');
    var chosenLabel = 'Off';
    items.forEach(function (item) {
      var idx = parseInt(item.getAttribute('data-index'), 10);
      var check = item.querySelector('.short-q-check');
      if (idx === trackIndex) {
        item.classList.add('active');
        if (check) check.style.visibility = 'visible';
        chosenLabel = item.textContent.replace('✓', '').trim();
      } else {
        item.classList.remove('active');
        if (check) check.style.visibility = 'hidden';
      }
    });

    var subMenu = document.getElementById('short-subtitle-menu');
    if (subMenu) subMenu.style.display = 'none';
    var subSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="7" y1="15" x2="7.01" y2="15"/><line x1="11" y1="15" x2="13" y2="15"/><line x1="7" y1="11" x2="17" y2="11"/></svg>';
    showToast(subSvg + (trackIndex >= 0 ? ' <span>Subtitles: ' + escapeHtml(chosenLabel) + '</span>' : ' <span>Subtitles: Off</span>'));
  }

  // ─── Audio Language Helper ─────────────────────────────────────────
  function getLanguageFlag(lang) {
    if (!lang) return '🌐';
    var l = lang.toLowerCase();
    if (l.indexOf('en') === 0 || l.indexOf('eng') === 0) return '🇺🇸';
    if (l.indexOf('es') === 0 || l.indexOf('spa') === 0) return '🇪🇸';
    if (l.indexOf('fr') === 0 || l.indexOf('fre') === 0 || l.indexOf('fra') === 0) return '🇫🇷';
    if (l.indexOf('it') === 0 || l.indexOf('ita') === 0) return '🇮🇹';
    if (l.indexOf('de') === 0 || l.indexOf('ger') === 0 || l.indexOf('deu') === 0) return '🇩🇪';
    if (l.indexOf('ja') === 0 || l.indexOf('jpn') === 0) return '🇯🇵';
    if (l.indexOf('ko') === 0 || l.indexOf('kor') === 0) return '🇰🇷';
    if (l.indexOf('zh') === 0 || l.indexOf('chi') === 0 || l.indexOf('zho') === 0) return '🇨🇳';
    if (l.indexOf('pt') === 0 || l.indexOf('por') === 0) return '🇵🇹';
    if (l.indexOf('ru') === 0 || l.indexOf('rus') === 0) return '🇷🇺';
    if (l.indexOf('uk') === 0 || l.indexOf('ukr') === 0) return '🇺🇦';
    if (l.indexOf('el') === 0 || l.indexOf('gre') === 0 || l.indexOf('ell') === 0) return '🇬🇷';
    return '🌐';
  }

  function populateHlsAudioTracks(audioTracks) {
    var langList   = document.getElementById('list-languages');
    var audioPopup = document.getElementById('short-audio-list');
    var dubBtn     = document.getElementById('short-btn-audio');

    if (!audioTracks || !audioTracks.length) {
      if (dubBtn) dubBtn.style.display = 'none';
      return;
    }

    if (dubBtn) dubBtn.style.display = 'inline-flex';
    if (langList) langList.innerHTML = '';
    if (audioPopup) audioPopup.innerHTML = '';

    var currentAudio = 0;
    if (state.hls && typeof state.hls.audioTrack === 'number' && state.hls.audioTrack >= 0) {
      currentAudio = state.hls.audioTrack;
    } else if (audioTracks && audioTracks.length) {
      var defIdx = -1;
      audioTracks.forEach(function (t, i) {
        if (t.default) defIdx = i;
      });
      currentAudio = (defIdx >= 0) ? defIdx : 0;
    }

    audioTracks.forEach(function (track, idx) {
      var isActive = (idx === currentAudio);
      var name = track.name || (track.lang ? track.lang.toUpperCase() : ('Audio ' + (idx + 1)));
      var flag = getLanguageFlag(track.lang || track.name);
      var sub = track.lang ? (track.lang.toUpperCase() + ' • Stereo / 5.1') : 'Stereo / 5.1';

      // Item for dedicated Audio popup menu (DUB button)
      if (audioPopup) {
        var li = document.createElement('li');
        li.className = 'short-quality-item' + (isActive ? ' active' : '');
        li.setAttribute('data-audio-track', idx);
        li.innerHTML = '<div class="short-q-left"><span class="short-q-check" style="visibility:' + (isActive ? 'visible' : 'hidden') + ';">✓</span> <span>' + escapeHtml(name) + '</span></div><span class="short-q-badge">' + escapeHtml((track.lang || 'EN').toUpperCase()) + '</span>';
        li.addEventListener('click', function (e) {
          e.stopPropagation();
          setAudioTrack(idx, name);
          var audioMenu = document.getElementById('short-audio-menu');
          if (audioMenu) audioMenu.style.display = 'none';
        });
        audioPopup.appendChild(li);
      }

      // Item for More modal
      if (langList) {
        var item = document.createElement('div');
        item.className = 'custom-dropdown-item' + (isActive ? ' active' : '');
        item.setAttribute('data-audio-track', idx);
        item.innerHTML = '<div class="item-left"><span class="item-icon">' + flag + '</span><div class="item-details"><span class="item-title">' + escapeHtml(name) + '</span><span class="item-subtitle">' + escapeHtml(sub) + '</span></div></div><span class="item-check" style="visibility:' + (isActive ? 'visible' : 'hidden') + ';">✓</span>';

        item.addEventListener('click', function (e) {
          e.stopPropagation();
          setAudioTrack(idx, name);
        });

        langList.appendChild(item);
      }
    });

    if (currentAudio >= 0 && dubBtn) {
      dubBtn.classList.add('active');
    }
  }

  function updateAudioActiveUI(trackIndex) {
    var popupItems = document.querySelectorAll('#short-audio-list .short-quality-item');
    popupItems.forEach(function (item) {
      var idx = parseInt(item.getAttribute('data-audio-track'), 10);
      var chk = item.querySelector('.short-q-check');
      if (idx === trackIndex) {
        item.classList.add('active');
        if (chk) chk.style.visibility = 'visible';
      } else {
        item.classList.remove('active');
        if (chk) chk.style.visibility = 'hidden';
      }
    });

    var langItems = document.querySelectorAll('#list-languages .custom-dropdown-item');
    langItems.forEach(function (item) {
      var idx = parseInt(item.getAttribute('data-audio-track'), 10);
      var chk = item.querySelector('.item-check');
      if (idx === trackIndex) {
        item.classList.add('active');
        if (chk) chk.style.visibility = 'visible';
      } else {
        item.classList.remove('active');
        if (chk) chk.style.visibility = 'hidden';
      }
    });
  }

  function updateServerListActiveUI(serverIndex) {
    var serverList = document.getElementById('list-servers');
    if (!serverList) return;
    var items = serverList.querySelectorAll('.custom-dropdown-item');
    items.forEach(function (item, idx) {
      if (idx === serverIndex) {
        item.classList.add('active');
      } else {
        item.classList.remove('active');
      }
    });
  }

  function setAudioTrack(trackIndex, name) {
    if (state.hls) {
      state.hls.audioTrack = trackIndex;
    }

    var dubBtn = document.getElementById('short-btn-audio');
    if (dubBtn) {
      dubBtn.classList.add('active');
    }

    updateAudioActiveUI(trackIndex);

    var moreModal = document.getElementById('short-more-modal');
    if (moreModal) moreModal.style.display = 'none';

    var audioMenu = document.getElementById('short-audio-menu');
    if (audioMenu) audioMenu.style.display = 'none';

    var audSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
    showToast(audSvg + ' <span>Audio: ' + escapeHtml(name) + '</span>');
  }

  // ─── Vertical Sliders (Brightness on Left, Volume on Right) ───────
  function bindVerticalSliders() {
    var brightWrap  = document.getElementById('short-vslider-brightness');
    var brightTrack = document.getElementById('short-brightness-track');
    var brightFill  = document.getElementById('short-brightness-fill');
    var brightVal   = document.getElementById('short-brightness-val');

    var volWrap     = document.getElementById('short-vslider-volume');
    var volTrack    = document.getElementById('short-volume-track');
    var volFill     = document.getElementById('short-volume-fill');
    var volVal      = document.getElementById('short-volume-val');

    var isBrightDragging = false;
    var isVolDragging    = false;

    // Brightness adjustment function
    function setBrightnessFromPos(clientY) {
      if (!brightTrack) return;
      var rect = brightTrack.getBoundingClientRect();
      var pct = Math.max(0, Math.min(1, (rect.bottom - clientY) / rect.height));
      // Map 0..1 to 0.2..2.0 brightness
      var bVal = 0.2 + (pct * 1.8);
      state.brightness = bVal;

      var videoEl = state.videoEl || document.getElementById('player-html5-video');
      if (videoEl) {
        videoEl.style.filter = 'brightness(' + bVal.toFixed(2) + ')';
      }

      var displayPct = Math.round(pct * 100);
      if (brightFill) brightFill.style.height = (pct * 100) + '%';
      if (brightVal) brightVal.textContent = displayPct + '%';
    }

    if (brightTrack) {
      brightTrack.addEventListener('mousedown', function (e) {
        e.stopPropagation();
        isBrightDragging = true;
        setBrightnessFromPos(e.clientY);
      });
      brightTrack.addEventListener('click', function (e) {
        e.stopPropagation();
        setBrightnessFromPos(e.clientY);
      });
    }

    // Volume adjustment function
    function setVolumeFromPos(clientY) {
      if (!volTrack) return;
      var rect = volTrack.getBoundingClientRect();
      var pct = Math.max(0, Math.min(1, (rect.bottom - clientY) / rect.height));
      state.volume = pct;

      var videoEl = state.videoEl || document.getElementById('player-html5-video');
      if (videoEl) {
        videoEl.volume = pct;
        videoEl.muted = (pct === 0);
      }

      var displayPct = Math.round(pct * 100);
      if (volFill) volFill.style.height = (pct * 100) + '%';
      if (volVal) volVal.textContent = displayPct + '%';

      var volOn  = document.querySelector('.short-vol-on');
      var volOff = document.querySelector('.short-vol-off');
      if (volOn && volOff) {
        volOn.style.display = (pct === 0) ? 'none' : 'block';
        volOff.style.display = (pct === 0) ? 'block' : 'none';
      }
    }

    if (volTrack) {
      volTrack.addEventListener('mousedown', function (e) {
        e.stopPropagation();
        isVolDragging = true;
        setVolumeFromPos(e.clientY);
      });
      volTrack.addEventListener('click', function (e) {
        e.stopPropagation();
        setVolumeFromPos(e.clientY);
      });
    }

    document.addEventListener('mousemove', function (e) {
      if (isBrightDragging) {
        setBrightnessFromPos(e.clientY);
      } else if (isVolDragging) {
        setVolumeFromPos(e.clientY);
      }
    });

    document.addEventListener('mouseup', function () {
      isBrightDragging = false;
      isVolDragging = false;
    });
  }

  // ─── Screen Lock Toggle ────────────────────────────────────────────
  function bindScreenLock() {
    var lockBtn   = document.getElementById('short-btn-lock');
    var unlockBtn = document.getElementById('short-screen-lock-badge');
    var overlay   = document.getElementById('short-controls-overlay');
    var topBar    = document.getElementById('player-top-bar');

    if (lockBtn) {
      lockBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        state.isLocked = true;
        if (overlay) overlay.style.display = 'none';
        if (topBar) topBar.style.display = 'none';
        if (unlockBtn) unlockBtn.style.display = 'inline-flex';
        var lockSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
        showToast(lockSvg + ' <span>Screen Locked</span>');
      });
    }

    if (unlockBtn) {
      unlockBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        state.isLocked = false;
        if (overlay) overlay.style.display = 'flex';
        if (topBar) topBar.style.display = 'flex';
        if (unlockBtn) unlockBtn.style.display = 'none';
        var unlockSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>';
        showToast(unlockSvg + ' <span>Screen Unlocked</span>');
      });
    }
  }

  // ─── More Options Modal (`⋮`) ─────────────────────────────────────
  function bindMoreOptionsModal() {
    var moreBtn   = document.getElementById('short-btn-more');
    var moreModal = document.getElementById('short-more-modal');
    if (!moreBtn || !moreModal) return;

    function openMoreModal() {
      moreModal.style.display = 'block';
      setTimeout(function () {
        moreModal.classList.add('is-open');
      }, 10);
    }

    function closeMoreModal() {
      moreModal.classList.remove('is-open');
      setTimeout(function () {
        if (!moreModal.classList.contains('is-open')) {
          moreModal.style.display = 'none';
        }
      }, 250);
    }

    moreBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var subMenu = document.getElementById('short-subtitle-menu');
      var drawer  = document.getElementById('short-playlist-drawer');
      if (subMenu) subMenu.style.display = 'none';
      if (drawer)  drawer.classList.remove('is-open');

      var isOpen = moreModal.classList.contains('is-open') || moreModal.style.display !== 'none';
      if (isOpen) {
        closeMoreModal();
      } else {
        openMoreModal();
      }
    });

    var modalCloseBtn = moreModal.querySelector('#short-more-close-btn');
    if (modalCloseBtn) {
      modalCloseBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        closeMoreModal();
      });
    }

    // Speed pills
    var speedPills = moreModal.querySelectorAll('.short-speed-pill[data-speed]');
    speedPills.forEach(function (pill) {
      pill.addEventListener('click', function (e) {
        e.stopPropagation();
        var speed = parseFloat(pill.getAttribute('data-speed') || '1.0');
        state.playbackRate = speed;
        var videoEl = state.videoEl || document.getElementById('player-html5-video');
        if (videoEl) videoEl.playbackRate = speed;

        speedPills.forEach(function (p) { p.classList.remove('active'); });
        pill.classList.add('active');
        var speedSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="13 19 22 12 13 5 13 19"/><polygon points="2 19 11 12 2 5 2 19"/></svg>';
        showToast(speedSvg + ' <span>Speed: ' + speed + 'x</span>');
      });
    });

    // Aspect Ratio / Fit pills
    var fitPills = moreModal.querySelectorAll('.short-speed-pill[data-fit]');
    fitPills.forEach(function (pill) {
      pill.addEventListener('click', function (e) {
        e.stopPropagation();
        var fit = pill.getAttribute('data-fit') || 'contain';
        state.aspectRatio = fit;
        var videoEl = state.videoEl || document.getElementById('player-html5-video');
        if (videoEl) videoEl.style.objectFit = fit;

        fitPills.forEach(function (p) { p.classList.remove('active'); });
        pill.classList.add('active');
        var fitSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
        showToast(fitSvg + ' <span>Fit: ' + fit.toUpperCase() + '</span>');
      });
    });

    // Populate server items in More modal
    var serverList = moreModal.querySelector('#list-servers');
    if (serverList) {
      serverList.innerHTML = '';
      state.sources.forEach(function (src, idx) {
        var item = document.createElement('div');
        var isActive = (idx === state.currentSourceIndex);
        item.className = 'custom-dropdown-item' + (isActive ? ' active' : '');
        item.innerHTML = '<div class="item-left"><span class="item-icon">' + (src.type === 'hls' ? '⚡' : '🎬') + '</span><div class="item-details"><span class="item-title">' + escapeHtml(src.name || ('Server ' + (idx + 1))) + '</span><span class="item-subtitle">' + escapeHtml((src.type || 'IFRAME').toUpperCase()) + '</span></div></div><span class="item-badge">' + escapeHtml(src.resolution || '1080p') + '</span>';

        item.addEventListener('click', function (e) {
          e.stopPropagation();
          loadSource(idx);
          serverList.querySelectorAll('.custom-dropdown-item').forEach(function (el) { el.classList.remove('active'); });
          item.classList.add('active');
          closeMoreModal();
          var srvSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>';
          showToast(srvSvg + ' <span>Switched to ' + escapeHtml(src.name || ('Server ' + (idx + 1))) + '</span>');
        });
        serverList.appendChild(item);
      });
    }

    // Sandbox / Ad Blocker Toggle Handler
    var sandboxBtn   = moreModal.querySelector('#short-sandbox-toggle-btn');
    var sandboxBadge = moreModal.querySelector('#short-sandbox-status-badge');
    var sandboxText  = moreModal.querySelector('#short-sandbox-toggle-text');

    function refreshSandboxUI(isOn) {
      if (sandboxBadge) {
        sandboxBadge.textContent = isOn ? 'ON' : 'OFF';
        sandboxBadge.className = 'short-sandbox-status-badge ' + (isOn ? 'is-on' : 'is-off');
      }
      if (sandboxText) {
        sandboxText.textContent = isOn ? 'Disable Ad Blocker' : 'Enable Ad Blocker';
      }
    }

    var storedSandbox = localStorage.getItem('short_iframe_sandbox');
    var isSandboxActive = (storedSandbox !== null) ? (storedSandbox === '1') : (state.playerContainer && state.playerContainer.getAttribute('data-sandbox-enabled') === '1');
    refreshSandboxUI(isSandboxActive);

    if (sandboxBtn) {
      sandboxBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var current = (localStorage.getItem('short_iframe_sandbox') !== null) 
          ? (localStorage.getItem('short_iframe_sandbox') === '1') 
          : (state.playerContainer && state.playerContainer.getAttribute('data-sandbox-enabled') === '1');
        var next = !current;
        localStorage.setItem('short_iframe_sandbox', next ? '1' : '0');
        refreshSandboxUI(next);

        var iframeEl = document.getElementById('player-stream-iframe');
        if (iframeEl) {
          if (next) {
            iframeEl.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-presentation allow-downloads');
            showToast('🛡️ Ad Blocker (Sandbox) Enabled - Popups blocked');
          } else {
            iframeEl.removeAttribute('sandbox');
            showToast('⚠️ Ad Blocker Disabled - Full iframe access');
          }
          // Refresh iframe
          if (iframeEl.src) {
            var s = iframeEl.src;
            iframeEl.src = '';
            setTimeout(function () { iframeEl.src = s; }, 60);
          }
        }
      });
    }

    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (moreModal && !moreModal.contains(e.target) && e.target !== moreBtn) {
        closeMoreModal();
      }
    });
  }

  // ─── Playlist / Episodes Drawer (`≡▶`) ─────────────────────────────
  function bindPlaylistDrawer() {
    var playlistBtn    = document.getElementById('short-btn-playlist');
    var drawer         = document.getElementById('short-playlist-drawer');
    var closeBtn       = document.getElementById('short-playlist-close');
    var body           = document.getElementById('short-playlist-body');

    if (!playlistBtn || !drawer) return;

    playlistBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var subMenu   = document.getElementById('short-subtitle-menu');
      var moreModal = document.getElementById('short-more-modal');
      if (subMenu)   subMenu.style.display = 'none';
      if (moreModal) moreModal.style.display = 'none';

      populatePlaylistDrawer();
      drawer.classList.add('is-open');
    });

    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        drawer.classList.remove('is-open');
      });
    }

    document.addEventListener('click', function (e) {
      if (drawer.classList.contains('is-open') && !drawer.contains(e.target) && e.target !== playlistBtn) {
        drawer.classList.remove('is-open');
      }
    });

    function populatePlaylistDrawer(seasonToLoad) {
      if (!body) return;

      var container       = state.playerContainer;
      var tmdbId          = container.getAttribute('data-tmdb-id') || '0';
      var playingSeason   = parseInt(container.getAttribute('data-season') || '1', 10);
      var playingEp       = parseInt(container.getAttribute('data-episode') || '1', 10);
      var viewSeason      = seasonToLoad !== undefined ? seasonToLoad : playingSeason;
      var isPlayingSeason = (viewSeason === playingSeason);
      var fallbackThumb   = container.getAttribute('data-backdrop') || container.getAttribute('data-poster') || '';

      var seasons = state.seasons || [];
      var headerLeft = document.getElementById('short-playlist-header-left');
      if (headerLeft) {
        var activeSeasonObj = seasons.find(function (s) { return s.season_number === viewSeason; });
        var activeSeasonLabel = activeSeasonObj ? (activeSeasonObj.name || ('Season ' + viewSeason)) : ('Season ' + viewSeason);

        if (seasons.length > 1) {
          var selHtml = '<div class="short-season-dropdown" id="short-season-dropdown">';
          selHtml += '<button type="button" class="short-season-trigger" id="short-season-trigger">';
          selHtml += '<span class="short-season-label" id="short-season-label">' + escapeHtml(activeSeasonLabel) + '</span>';
          selHtml += '<svg class="short-season-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>';
          selHtml += '</button>';

          selHtml += '<div class="short-season-menu" id="short-season-menu" style="display:none;">';
          seasons.forEach(function (s) {
            var sNum = s.season_number;
            if (sNum > 0) {
              var isAct = (sNum === viewSeason);
              selHtml += '<div class="short-season-item' + (isAct ? ' active' : '') + '" data-season-num="' + sNum + '">';
              selHtml += '<span>' + escapeHtml(s.name || ('Season ' + sNum)) + '</span>';
              if (isAct) selHtml += '<span class="short-season-check">✓</span>';
              selHtml += '</div>';
            }
          });
          selHtml += '</div>'; // end short-season-menu
          selHtml += '</div>'; // end short-season-dropdown
          headerLeft.innerHTML = selHtml;

          var dropdownWrap = document.getElementById('short-season-dropdown');
          var triggerBtn   = document.getElementById('short-season-trigger');
          var menuEl       = document.getElementById('short-season-menu');

          if (triggerBtn && menuEl) {
            triggerBtn.addEventListener('click', function (e) {
              e.stopPropagation();
              var isOpen = menuEl.style.display !== 'none';
              menuEl.style.display = isOpen ? 'none' : 'block';
              if (dropdownWrap) {
                if (isOpen) dropdownWrap.classList.remove('is-open');
                else dropdownWrap.classList.add('is-open');
              }
            });

            menuEl.querySelectorAll('.short-season-item').forEach(function (item) {
              item.addEventListener('click', function (e) {
                e.stopPropagation();
                var newSeason = parseInt(item.getAttribute('data-season-num'), 10);
                menuEl.style.display = 'none';
                if (dropdownWrap) dropdownWrap.classList.remove('is-open');
                populatePlaylistDrawer(newSeason);
              });
            });

            // Close on outside click
            document.addEventListener('click', function (e) {
              if (dropdownWrap && !dropdownWrap.contains(e.target)) {
                menuEl.style.display = 'none';
                dropdownWrap.classList.remove('is-open');
              }
            });
          }
        } else {
          headerLeft.innerHTML = '<h3 class="short-playlist-title">Episodes</h3>';
        }
      }

      function renderEpisodeCards(episodesList) {
        body.innerHTML = '';
        if (!episodesList || !episodesList.length) {
          body.innerHTML = '<div style="color:#94a3b8; padding:30px 20px; text-align:center; font-size:0.9rem;">No episodes found</div>';
          return;
        }

        var siteBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.site_url) ? window.SHORT_CONFIG.site_url : (window.location.origin + (window.location.pathname.startsWith('/wordpress') ? '/wordpress' : ''));

        episodesList.forEach(function (epData, idx) {
          var epNum = epData.episode_number || (idx + 1);
          var isCurrent = isPlayingSeason && (epNum === playingEp);
          var epTitle = epData.name || ('Episode ' + epNum);
          var epThumb = epData.still_path ? ('https://image.tmdb.org/t/p/w300' + epData.still_path) : fallbackThumb;
          var epRuntime = epData.runtime ? (epData.runtime + 'm') : '';
          var epOverview = epData.overview || '';

          var item = document.createElement('a');
          item.className = 'short-ep-item' + (isCurrent ? ' active' : '');
          item.href = siteBase + '/watch/' + tmdbId + '/season-' + viewSeason + '/episode-' + epNum + '/';

          var thumbHtml = '<div class="short-ep-thumb-wrap">' +
            (epThumb ? '<img class="short-ep-thumb-img" src="' + epThumb + '" alt="' + escapeHtml(epTitle) + '" loading="lazy" />' : '<div class="short-ep-thumb-placeholder"></div>') +
            '<div class="short-ep-thumb-play"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></div>' +
            (isCurrent ? '<span class="short-ep-badge-live">NOW PLAYING</span>' : '') +
            '</div>';

          var detailsHtml = '<div class="short-ep-info-col">' +
            '<div class="short-ep-top-line">' +
            '<span class="short-ep-title">' + epNum + '. ' + escapeHtml(epTitle) + '</span>' +
            (epRuntime ? '<span class="short-ep-duration">' + epRuntime + '</span>' : '') +
            '</div>' +
            (epOverview ? '<p class="short-ep-desc">' + escapeHtml(epOverview) + '</p>' : '') +
            '</div>';

          item.innerHTML = thumbHtml + detailsHtml;
          body.appendChild(item);
        });

        // Auto-scroll active into view
        var activeItem = body.querySelector('.short-ep-item.active');
        if (activeItem) {
          setTimeout(function () {
            activeItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }, 100);
        }
      }

      // Check if we have preloaded data for the playing season
      if (isPlayingSeason && state.episodes && state.episodes.length > 0) {
        renderEpisodeCards(state.episodes);
      } else {
        // Fetch season from REST API or fallback to basic count
        body.innerHTML = '<div style="color:#94a3b8; padding:30px 20px; text-align:center; font-size:0.9rem;">Loading episodes...</div>';
        
        var siteBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.site_url) ? window.SHORT_CONFIG.site_url : (window.location.origin + (window.location.pathname.startsWith('/wordpress') ? '/wordpress' : ''));
        var restUrl = (window.SHORT_CONFIG && window.SHORT_CONFIG.rest_url) ? (window.SHORT_CONFIG.rest_url + '/tv/' + tmdbId + '/season/' + viewSeason) : (siteBase + '/wp-json/short/v1/tv/' + tmdbId + '/season/' + viewSeason);

        fetch(restUrl)
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data && data.episodes && data.episodes.length) {
              renderEpisodeCards(data.episodes);
            } else {
              fallbackRender();
            }
          })
          .catch(function () {
            fallbackRender();
          });
      }

      function fallbackRender() {
        var currSeasonObj = seasons.find(function (s) { return (s.season_number || 0) === viewSeason; }) || seasons[0] || { episode_count: 10 };
        var epCount = currSeasonObj.episode_count || 10;
        var fakeList = [];
        for (var i = 1; i <= epCount; i++) {
          fakeList.push({ episode_number: i, name: 'Episode ' + i });
        }
        renderEpisodeCards(fakeList);
      }
    }
  }

  // ─── Playback Controls ─────────────────────────────────────────────
  function bindControls() {
    var videoEl = document.getElementById('player-html5-video');
    if (!videoEl) return;
    state.videoEl = videoEl;

    // Back Button (always navigate directly to the show/movie details page)
    var backBtn = document.getElementById('short-btn-back');
    if (backBtn) {
      backBtn.addEventListener('click', function (e) {
        var targetUrl = backBtn.getAttribute('href');
        if (targetUrl && targetUrl !== '#' && targetUrl !== 'javascript:void(0);') {
          e.preventDefault();
          window.location.href = targetUrl;
        }
      });
    }

    // Play/Pause center button
    var playPause = document.getElementById('short-play-pause-center');
    if (playPause) {
      playPause.addEventListener('click', function (e) {
        e.stopPropagation();
        if (videoEl.paused) videoEl.play().catch(function () {});
        else videoEl.pause();
      });
    }

    // Click video to toggle play/pause & unmute if muted autoplay
    videoEl.addEventListener('click', function () {
      if (state.isLocked) return;
      if (videoEl.muted) {
        videoEl.muted = false;
        var volOn  = document.querySelector('.short-vol-on');
        var volOff = document.querySelector('.short-vol-off');
        if (volOn && volOff) {
          volOn.style.display = 'block';
          volOff.style.display = 'none';
        }
        showToast('Audio Unmuted');
      }
      if (videoEl.paused) videoEl.play().catch(function () {});
      else videoEl.pause();
    });

    // Update play/pause icon
    videoEl.addEventListener('play', function () {
      var iconPlay  = document.querySelector('.short-icon-play');
      var iconPause = document.querySelector('.short-icon-pause');
      if (iconPlay) iconPlay.style.display = 'none';
      if (iconPause) iconPause.style.display = 'block';
    });

    videoEl.addEventListener('pause', function () {
      hideLoader();
      var iconPlay  = document.querySelector('.short-icon-play');
      var iconPause = document.querySelector('.short-icon-pause');
      if (iconPlay) iconPlay.style.display = 'block';
      if (iconPause) iconPause.style.display = 'none';
    });

    // Loading overlay click dismiss & play
    var loadingOverlay = document.getElementById('short-loading-overlay');
    if (loadingOverlay) {
      loadingOverlay.style.pointerEvents = 'auto';
      loadingOverlay.addEventListener('click', function () {
        hideLoader();
        if (videoEl.paused) videoEl.play().catch(function () {});
      });
    }

    // Loading & Buffering indicators
    videoEl.addEventListener('waiting', function () {
      if (!videoEl.paused && videoEl.readyState < 3 && videoEl.currentTime > 0) {
        showLoader('Buffering stream...');
      }
    });
    videoEl.addEventListener('playing', function () {
      hideLoader();
    });
    videoEl.addEventListener('canplay', function () {
      hideLoader();
    });
    videoEl.addEventListener('canplaythrough', function () {
      hideLoader();
    });
    videoEl.addEventListener('seeking', function () {
      showLoader('Loading...');
    });
    videoEl.addEventListener('seeked', function () {
      hideLoader();
    });

    // Rewind / Forward 10s
    var rewindBtn = document.getElementById('short-btn-rewind');
    if (rewindBtn) {
      rewindBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        videoEl.currentTime = Math.max(0, videoEl.currentTime - 10);
        var rewSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="11 19 2 12 11 5 11 19"/><polygon points="22 19 13 12 22 5 22 19"/></svg>';
        showToast(rewSvg + ' <span>-10s</span>');
      });
    }

    var forwardBtn = document.getElementById('short-btn-forward');
    if (forwardBtn) {
      forwardBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        videoEl.currentTime = Math.min(videoEl.duration || 0, videoEl.currentTime + 10);
        var fwdSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="13 19 22 12 13 5 13 19"/><polygon points="2 19 11 12 2 5 2 19"/></svg>';
        showToast(fwdSvg + ' <span>+10s</span>');
      });
    }

    // Time update → progress bar + timestamps
    videoEl.addEventListener('timeupdate', function () {
      if (videoEl.currentTime > 0) {
        hideLoader();
      }
      if (!videoEl.duration) return;
      var pct = (videoEl.currentTime / videoEl.duration) * 100;

      var filled = document.getElementById('short-progress-filled');
      var thumb  = document.getElementById('short-progress-thumb');
      if (filled) filled.style.width = pct + '%';
      if (thumb)  thumb.style.left = pct + '%';

      var timeCur = document.getElementById('short-time-current');
      var timeDur = document.getElementById('short-time-duration');
      if (timeCur) timeCur.textContent = formatTime(videoEl.currentTime);
      if (timeDur) timeDur.textContent = formatTime(videoEl.duration);
    });

    // Buffered progress
    videoEl.addEventListener('progress', function () {
      if (!videoEl.duration || !videoEl.buffered.length) return;
      var bufferedEnd = videoEl.buffered.end(videoEl.buffered.length - 1);
      var pct = (bufferedEnd / videoEl.duration) * 100;
      var bufferedBar = document.getElementById('short-progress-buffered');
      if (bufferedBar) bufferedBar.style.width = pct + '%';
    });

    // Progress bar seeking (click + drag)
    var progressContainer = document.getElementById('short-progress-container') || document.querySelector('.short-progress-container');
    var progressTrack     = document.getElementById('short-progress-track');
    var isSeeking = false;

    function seekAt(e) {
      var track = document.getElementById('short-progress-track') || document.querySelector('.short-progress-container');
      if (!track || !videoEl || !videoEl.duration) return;
      var rect = track.getBoundingClientRect();
      var clientX = (e.touches && e.touches.length) ? e.touches[0].clientX : e.clientX;
      var offsetX = Math.max(0, Math.min(clientX - rect.left, rect.width));
      var pct = offsetX / (rect.width || 1);
      videoEl.currentTime = pct * videoEl.duration;
      
      var filled = document.getElementById('short-progress-filled');
      var thumb  = document.getElementById('short-progress-thumb');
      if (filled) filled.style.width = (pct * 100) + '%';
      if (thumb)  thumb.style.left = (pct * 100) + '%';
    }

    if (progressContainer) {
      progressContainer.addEventListener('mousedown', function (e) {
        e.stopPropagation();
        isSeeking = true;
        seekAt(e);
      });
      progressContainer.addEventListener('click', function (e) {
        e.stopPropagation();
        seekAt(e);
      });
    }
    if (progressTrack) {
      progressTrack.addEventListener('click', function (e) {
        e.stopPropagation();
        seekAt(e);
      });
    }

    document.addEventListener('mousemove', function (e) {
      if (isSeeking) seekAt(e);
    });
    document.addEventListener('mouseup', function () {
      if (isSeeking) isSeeking = false;
    });

    // Mute / Unmute Button
    var muteBtn = document.getElementById('short-btn-mute');
    if (muteBtn) {
      muteBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        videoEl.muted = !videoEl.muted;
        var volOn  = document.querySelector('.short-vol-on');
        var volOff = document.querySelector('.short-vol-off');
        if (volOn && volOff) {
          volOn.style.display = videoEl.muted ? 'none' : 'block';
          volOff.style.display = videoEl.muted ? 'block' : 'none';
        }
        var volFill = document.getElementById('short-volume-fill');
        var volVal  = document.getElementById('short-volume-val');
        if (volFill) volFill.style.height = (videoEl.muted ? '0%' : (videoEl.volume * 100) + '%');
        if (volVal)  volVal.textContent = (videoEl.muted ? '0%' : Math.round(videoEl.volume * 100) + '%');
        
        var muteSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>';
        var unmuteSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
        showToast(videoEl.muted ? (muteSvg + ' <span>Muted</span>') : (unmuteSvg + ' <span>Unmuted</span>'));
      });
    }

    // Screenshot Capture Button
    var screenshotBtn = document.getElementById('short-btn-screenshot');
    if (screenshotBtn) {
      screenshotBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        takeScreenshot(videoEl);
      });
    }

    // Audio & Dubbing Button (DUB)
    var audioBtn  = document.getElementById('short-btn-audio');
    var audioMenu = document.getElementById('short-audio-menu');
    if (audioBtn && audioMenu) {
      audioBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var moreModal = document.getElementById('short-more-modal');
        var drawer    = document.getElementById('short-playlist-drawer');
        var subMenu   = document.getElementById('short-subtitle-menu');
        if (moreModal) moreModal.style.display = 'none';
        if (drawer)    drawer.classList.remove('is-open');
        if (subMenu)   subMenu.style.display = 'none';

        var isOpen = audioMenu.style.display !== 'none';
        audioMenu.style.display = isOpen ? 'none' : 'block';
      });
    }

    // Subtitle Button (CC)
    var subtitleBtn  = document.getElementById('short-btn-subtitles');
    var subtitleMenu = document.getElementById('short-subtitle-menu');
    if (subtitleBtn && subtitleMenu) {
      subtitleBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var moreModal = document.getElementById('short-more-modal');
        var drawer    = document.getElementById('short-playlist-drawer');
        var audMenu   = document.getElementById('short-audio-menu');
        if (moreModal) moreModal.style.display = 'none';
        if (drawer)    drawer.classList.remove('is-open');
        if (audMenu)   audMenu.style.display = 'none';

        var isOpen = subtitleMenu.style.display !== 'none';
        subtitleMenu.style.display = isOpen ? 'none' : 'block';
      });
    }

    // Close menus on background click
    document.addEventListener('click', function (e) {
      if (subtitleMenu && !subtitleMenu.contains(e.target) && e.target !== subtitleBtn) {
        subtitleMenu.style.display = 'none';
      }
      if (audioMenu && !audioMenu.contains(e.target) && e.target !== audioBtn) {
        audioMenu.style.display = 'none';
      }
    });
  }

  // ─── Screenshot Function ───────────────────────────────────────────
  function takeScreenshot(videoEl) {
    if (!videoEl || !videoEl.videoWidth || !videoEl.videoHeight) {
      var warnSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
      showToast(warnSvg + ' <span>Video frame not ready</span>');
      return;
    }

    try {
      var canvas = document.createElement('canvas');
      canvas.width = videoEl.videoWidth;
      canvas.height = videoEl.videoHeight;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

      var container = state.playerContainer;
      var title = (container.getAttribute('data-title') || 'screenshot').replace(/[^a-z0-9_-]/gi, '_');
      var time = Math.floor(videoEl.currentTime || 0);

      var dataUrl = canvas.toDataURL('image/png');
      var link = document.createElement('a');
      link.download = title + '_' + time + 's.png';
      link.href = dataUrl;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      var camSvg = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>';
      showToast(camSvg + ' <span>Screenshot saved!</span>');
    } catch (err) {
      console.warn('[shortPlayer] Screenshot failed (likely cross-origin):', err);
      var camSvg2 = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>';
      showToast(camSvg2 + ' <span>Screenshot saved</span>');
    }
  }

  // ─── Skip Intro ────────────────────────────────────────────────────
  function bindSkipIntro() {
    var skipBtn = document.getElementById('btn-player-skip-intro');
    var videoEl = document.getElementById('player-html5-video');
    if (!skipBtn) return;

    skipBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var skipSec = parseInt(skipBtn.getAttribute('data-skip-seconds') || '85', 10);
      if (videoEl && state.isNative) {
        videoEl.currentTime = (videoEl.currentTime || 0) + skipSec;
        showToast('<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; vertical-align:middle; margin-right:4px;"><polygon points="13 19 22 12 13 5 13 19"/><polygon points="2 19 11 12 2 5 2 19"/></svg> Skipped ' + skipSec + 's Intro');
      }
      skipBtn.style.display = 'none';
    });

    if (videoEl) {
      videoEl.addEventListener('timeupdate', function () {
        if (!state.isNative || state.isLocked) return;
        if (videoEl.currentTime > 5 && videoEl.currentTime < 90) {
          skipBtn.style.display = 'inline-flex';
        } else {
          skipBtn.style.display = 'none';
        }
      });
    }
  }

  // ─── Next Episode Countdown ────────────────────────────────────────
  function bindNextEpisode() {
    var nextOverlay    = document.getElementById('player-next-ep-overlay');
    var closeBtn       = document.getElementById('btn-close-next-ep');
    var cancelBtn      = document.getElementById('btn-cancel-next-ep');
    var videoEl        = document.getElementById('player-html5-video');
    var countdownDigits = document.getElementById('next-ep-countdown-digits');
    var progressFill   = document.getElementById('next-ep-progress-fill');

    if (!nextOverlay) return;

    var nextUrl = nextOverlay.getAttribute('data-next-url');
    if (!nextUrl) return;

    function triggerCountdown() {
      if (state.countdownDismissed || !nextUrl || state.isLocked) return;
      if (nextOverlay.style.display === 'block') return;

      nextOverlay.style.display = 'block';
      var remaining = 5;
      if (countdownDigits) countdownDigits.textContent = remaining;
      if (progressFill) {
        progressFill.style.transition = 'none';
        progressFill.style.width = '0%';
        setTimeout(function () {
          progressFill.style.transition = 'width 5s linear';
          progressFill.style.width = '100%';
        }, 50);
      }

      if (state.countdownInterval) clearInterval(state.countdownInterval);
      state.countdownInterval = setInterval(function () {
        remaining -= 1;
        if (countdownDigits) countdownDigits.textContent = Math.max(0, remaining);
        if (remaining <= 0) {
          clearInterval(state.countdownInterval);
          window.location.href = nextUrl;
        }
      }, 1000);
    }

    function dismissCountdown() {
      state.countdownDismissed = true;
      if (state.countdownInterval) clearInterval(state.countdownInterval);
      if (nextOverlay) nextOverlay.style.display = 'none';
    }

    if (closeBtn)  closeBtn.addEventListener('click', dismissCountdown);
    if (cancelBtn) cancelBtn.addEventListener('click', dismissCountdown);

    if (videoEl) {
      videoEl.addEventListener('ended', triggerCountdown);
      videoEl.addEventListener('timeupdate', function () {
        if (videoEl.duration > 30 && (videoEl.duration - videoEl.currentTime) <= 12) {
          triggerCountdown();
        }
      });
    }
  }

  // ─── Fullscreen ────────────────────────────────────────────────────
  function bindFullscreen() {
    var fsBtn = document.getElementById('ctrl-fullscreen');
    if (!fsBtn) return;

    fsBtn.addEventListener('click', function () {
      var el = state.playerContainer || document.getElementById('short-player-app');
      if (!document.fullscreenElement) {
        if (el.requestFullscreen) el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
      } else {
        if (document.exitFullscreen) document.exitFullscreen();
      }
    });
  }

  // ─── Keyboard Controls ─────────────────────────────────────────────
  function bindKeyboard() {
    document.addEventListener('keydown', function (e) {
      var videoEl = state.videoEl || document.getElementById('player-html5-video');
      if (!videoEl || !state.isNative || state.isLocked) return;

      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

      switch (e.key) {
        case ' ':
        case 'k':
          e.preventDefault();
          if (videoEl.paused) videoEl.play().catch(function () {});
          else videoEl.pause();
          break;
        case 'ArrowLeft':
          e.preventDefault();
          videoEl.currentTime = Math.max(0, videoEl.currentTime - 10);
          var rewSvgK = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="11 19 2 12 11 5 11 19"/><polygon points="22 19 13 12 22 5 22 19"/></svg>';
          showToast(rewSvgK + ' <span>-10s</span>');
          break;
        case 'ArrowRight':
          e.preventDefault();
          videoEl.currentTime = Math.min(videoEl.duration || 0, videoEl.currentTime + 10);
          var fwdSvgK = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="13 19 22 12 13 5 13 19"/><polygon points="2 19 11 12 2 5 2 19"/></svg>';
          showToast(fwdSvgK + ' <span>+10s</span>');
          break;
        case 'ArrowUp':
          e.preventDefault();
          videoEl.volume = Math.min(1, videoEl.volume + 0.1);
          break;
        case 'ArrowDown':
          e.preventDefault();
          videoEl.volume = Math.max(0, videoEl.volume - 0.1);
          break;
        case 'f':
        case 'F':
          e.preventDefault();
          var el = state.playerContainer;
          if (!document.fullscreenElement) {
            if (el && el.requestFullscreen) el.requestFullscreen();
          } else {
            document.exitFullscreen();
          }
          break;
        case 'm':
        case 'M':
          e.preventDefault();
          videoEl.muted = !videoEl.muted;
          var volOn  = document.querySelector('.short-vol-on');
          var volOff = document.querySelector('.short-vol-off');
          if (volOn && volOff) {
            volOn.style.display = videoEl.muted ? 'none' : 'block';
            volOff.style.display = videoEl.muted ? 'block' : 'none';
          }
          var muteSvgK = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" fill="currentColor"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>';
          var unmuteSvgK = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14" fill="none" stroke="currentColor" stroke-width="2"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07" fill="none" stroke="currentColor" stroke-width="2"/></svg>';
          showToast(videoEl.muted ? (muteSvgK + ' <span>Muted</span>') : (unmuteSvgK + ' <span>Unmuted</span>'));
          break;
      }
    });
  }

  // ─── Controls Auto-Hide ────────────────────────────────────────────
  function bindControlsAutoHide() {
    var container = state.playerContainer;
    if (!container) return;

    container.addEventListener('mousemove', function () {
      if (state.isLocked) return;
      showControls();
      clearTimeout(state.controlsTimeout);
      state.controlsTimeout = setTimeout(hideControls, 3500);
    });

    container.addEventListener('mouseleave', function () {
      if (state.isLocked) return;
      state.controlsTimeout = setTimeout(hideControls, 1500);
    });

    container.addEventListener('touchstart', function () {
      if (state.isLocked) return;
      if (state.controlsVisible) {
        hideControls();
      } else {
        showControls();
        clearTimeout(state.controlsTimeout);
        state.controlsTimeout = setTimeout(hideControls, 4000);
      }
    });
  }

  function showControls() {
    state.controlsVisible = true;
    var overlay = document.getElementById('short-controls-overlay');
    var topBar  = document.getElementById('player-top-bar');
    if (overlay) overlay.classList.remove('short-controls-hidden');
    if (topBar)  topBar.classList.remove('short-controls-hidden');
    if (state.playerContainer) state.playerContainer.style.cursor = 'default';
  }

  function hideControls() {
    if (state.isLocked) return;
    var moreModal = document.getElementById('short-more-modal');
    if (moreModal && moreModal.style.display !== 'none') return;
    var drawer = document.getElementById('short-playlist-drawer');
    if (drawer && drawer.classList.contains('is-open')) return;

    if (state.isNative) {
      var videoEl = state.videoEl || document.getElementById('player-html5-video');
      if (!videoEl || videoEl.paused) return;
    }

    state.controlsVisible = false;
    var overlay = document.getElementById('short-controls-overlay');
    var topBar  = document.getElementById('player-top-bar');
    if (overlay) overlay.classList.add('short-controls-hidden');
    if (topBar)  topBar.classList.add('short-controls-hidden');
    if (state.playerContainer) state.playerContainer.style.cursor = 'none';
  }

  function showNativeControls() {
    var overlay = document.getElementById('short-controls-overlay');
    if (overlay) {
      overlay.style.display = 'flex';
      overlay.classList.remove('short-iframe-mode');
    }
    var moreModal = document.getElementById('short-more-modal');
    if (moreModal) {
      moreModal.classList.remove('is-iframe-view');
    }
  }

  function hideNativeControls() {
    var overlay = document.getElementById('short-controls-overlay');
    if (overlay) {
      overlay.style.display = 'flex';
      overlay.classList.add('short-iframe-mode');
    }
    var moreModal = document.getElementById('short-more-modal');
    if (moreModal) {
      moreModal.classList.add('is-iframe-view');
    }
  }

  // ─── Paywall ───────────────────────────────────────────────────────
  function handlePaywall() {
    var container   = state.playerContainer;
    var isPaywallOn = container.getAttribute('data-paywall-enabled') === '1';

    if (!isPaywallOn) return false;

    if (container.getAttribute('data-is-admin') === '1') {
      return false;
    }

    var hasSub = false;

    if (typeof window.hasActiveSubscription === 'function') {
      try {
        hasSub = window.hasActiveSubscription();
      } catch (e) {}
    }

    if (!hasSub) {
      try {
        var stored = JSON.parse(localStorage.getItem('short_subscription') || 'null');
        if (stored && (stored.status === 'active' || stored.plan_id || stored.plan_name)) {
          hasSub = true;
        }
      } catch (e) {}
    }

    if (!hasSub) {
      var cookieMatch = document.cookie.match(/short_sub_tier=([^;]+)/);
      if (cookieMatch && ['basic', 'standard', 'premium', 'active'].indexOf(cookieMatch[1].toLowerCase()) !== -1) {
        hasSub = true;
      }
    }

    if (!hasSub && state.userTier && ['basic', 'standard', 'premium'].indexOf(state.userTier.toLowerCase()) !== -1) {
      hasSub = true;
    }

    if (hasSub) {
      var overlay = document.getElementById('player-paywall-overlay');
      if (overlay) overlay.style.display = 'none';
      return false;
    }

    var paywallOverlay = document.getElementById('player-paywall-overlay');
    var iframeEl       = document.getElementById('player-stream-iframe');
    var videoEl        = document.getElementById('player-html5-video');

    if (paywallOverlay) paywallOverlay.style.display = 'flex';
    if (iframeEl) iframeEl.style.display = 'none';
    if (videoEl) {
      videoEl.pause();
      videoEl.style.display = 'none';
    }

    var trailerBtn = document.getElementById('btn-play-trailer-preview');
    if (trailerBtn) {
      trailerBtn.addEventListener('click', function () {
        var trailerKey = trailerBtn.getAttribute('data-trailer-key');
        if (trailerKey && iframeEl) {
          iframeEl.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(trailerKey) + '?autoplay=1&rel=0';
          iframeEl.style.display = 'block';
          if (paywallOverlay) paywallOverlay.style.display = 'none';
        }
      });
    }

    return true;
  }

  // ─── Watch Progress Tracking ───────────────────────────────────────
  function startProgressTracking(videoEl) {
    if (!videoEl) return;
    if (state.iframeProgressInterval) {
      clearInterval(state.iframeProgressInterval);
      state.iframeProgressInterval = null;
    }

    var container = state.playerContainer;
    var meta = {
      tmdb_id:       parseInt(container.getAttribute('data-tmdb-id') || '0', 10),
      content_type:  container.getAttribute('data-content-type') || 'movie',
      season:        parseInt(container.getAttribute('data-season') || '0', 10),
      episode:       parseInt(container.getAttribute('data-episode') || '0', 10),
      title:         container.getAttribute('data-title') || 'Untitled',
      poster_path:   container.getAttribute('data-poster') || '',
      backdrop_path: container.getAttribute('data-backdrop') || '',
      vote_average:  container.getAttribute('data-rating') || '',
      year:          container.getAttribute('data-year') || '',
    };

    if (state.progressInterval) clearInterval(state.progressInterval);
    state.progressInterval = setInterval(function () {
      if (!videoEl.paused && videoEl.duration) {
        saveProgress(meta, videoEl);
      }
    }, 10000);

    videoEl.addEventListener('pause', function () {
      if (videoEl.duration) saveProgress(meta, videoEl);
    });

    videoEl.addEventListener('ended', function () {
      if (videoEl.duration) {
        saveProgress(meta, videoEl, true);
      }
    });
  }

  function startIframeProgressTracking() {
    if (state.progressInterval) {
      clearInterval(state.progressInterval);
      state.progressInterval = null;
    }
    if (state.iframeProgressInterval) {
      clearInterval(state.iframeProgressInterval);
      state.iframeProgressInterval = null;
    }

    var container = state.playerContainer;
    if (!container) return;

    var rawSeriesId = container.getAttribute('data-series-id') || container.getAttribute('data-id') || container.getAttribute('data-post-id') || container.getAttribute('data-tmdb-id') || '0';
    var meta = {
      id:            parseInt(rawSeriesId, 10) || rawSeriesId,
      series_id:     parseInt(rawSeriesId, 10) || rawSeriesId,
      post_id:       parseInt(rawSeriesId, 10) || rawSeriesId,
      content_type:  container.getAttribute('data-content-type') || 'tv',
      season:        parseInt(container.getAttribute('data-season') || '0', 10),
      episode:       parseInt(container.getAttribute('data-episode') || '1', 10),
      title:         container.getAttribute('data-title') || 'Untitled',
      poster_path:   container.getAttribute('data-poster') || '',
      backdrop_path: container.getAttribute('data-backdrop') || '',
      vote_average:  container.getAttribute('data-rating') || '',
      year:          container.getAttribute('data-year') || '',
    };

    var seriesId = String(meta.id || meta.series_id || meta.post_id || rawSeriesId);
    if (!seriesId || seriesId === '0') return;

    // Retrieve previous progress if exists
    var prevCurrentTime = 30;
    var duration = 5400; // default 90 min
    try {
      var isLoggedIn = localStorage.getItem('short_is_logged_in') === '1';
      var uid = isLoggedIn ? localStorage.getItem('short_user_uid') : null;
      var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';
      var scopedKey = uid ? ('short_continue_watching_' + uid + '_' + activeProfile) : ('short_continue_watching_guest_' + activeProfile);
      var fallbackKey = uid ? ('shorttv_history_' + uid) : 'short_guest_watch_history';
      var storage = JSON.parse(localStorage.getItem(scopedKey) || localStorage.getItem(fallbackKey) || '{}');
      var prev = storage[seriesId];
      if (prev && prev.currentTime) {
        prevCurrentTime = Math.max(30, prev.currentTime);
        if (prev.duration) duration = prev.duration;
      }
    } catch (e) {}

    // Immediate initial registration so title appears immediately in Continue Watching
    saveProgress(meta, {
      currentTime: prevCurrentTime,
      duration: duration,
      percent: Math.min(95, Math.max(5, Math.round((prevCurrentTime / duration) * 100)))
    });

    var trackedSeconds = prevCurrentTime;
    state.iframeProgressInterval = setInterval(function () {
      trackedSeconds += 15;
      saveProgress(meta, {
        currentTime: trackedSeconds,
        duration: duration,
        percent: Math.min(95, Math.max(5, Math.round((trackedSeconds / duration) * 100)))
      });
    }, 15000);
  }

  function saveProgress(meta, videoOrObj, isComplete) {
    var curTime = 0;
    var durTime = 3600;
    var pct = 0;

    if (videoOrObj && typeof videoOrObj.currentTime !== 'undefined' && typeof videoOrObj.duration !== 'undefined') {
      curTime = isComplete ? Math.floor(videoOrObj.duration) : Math.floor(videoOrObj.currentTime);
      durTime = Math.floor(videoOrObj.duration) || 3600;
      pct = isComplete ? 100 : (videoOrObj.percent !== undefined ? videoOrObj.percent : Math.round((curTime / durTime) * 100));
    }

    var progressData = Object.assign({}, meta, {
      currentTime: curTime,
      duration: durTime,
      percent: Math.min(100, Math.max(5, pct)),
    });

    if (typeof window.saveWatchProgress === 'function') {
      window.saveWatchProgress(progressData);
    }

    try {
      var isLoggedIn = localStorage.getItem('short_is_logged_in') === '1';
      var uid = isLoggedIn ? localStorage.getItem('short_user_uid') : null;
      var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';
      var scopedKey = uid ? ('short_continue_watching_' + uid + '_' + activeProfile) : ('short_continue_watching_guest_' + activeProfile);
      var histKey = uid ? ('shorttv_history_' + uid) : 'short_guest_watch_history';
      var seriesId = String(meta.id || meta.series_id || meta.post_id || meta.tmdb_id);

      [scopedKey, histKey].forEach(function(storageKey) {
        if (!storageKey) return;
        var storage = JSON.parse(localStorage.getItem(storageKey) || '{}');
        Object.keys(storage).forEach(function (k) {
          if (k.startsWith(seriesId + '_s')) {
            delete storage[k];
          }
        });

        var rawPoster = meta.poster || meta.poster_path || '';
        var normPoster = typeof window.normalizeMediaUrl === 'function' ? window.normalizeMediaUrl(rawPoster) : rawPoster;

        storage[seriesId] = {
          id: seriesId,
          title: meta.title || 'Short Drama',
          poster: normPoster,
          episode: meta.episode || 1,
          lastEpisode: meta.episode || 1,
          currentTime: progressData.currentTime || 0,
          duration: progressData.duration || 3600,
          percent: progressData.percent || 5,
          watchedAt: Date.now()
        };
        localStorage.setItem(storageKey, JSON.stringify(storage));
      });
    } catch (e) {}
  }

  function resumePlayback(videoEl) {
    if (!videoEl) return;

    var container = state.playerContainer;
    var seriesId  = container.getAttribute('data-series-id') || container.getAttribute('data-id') || container.getAttribute('data-post-id') || container.getAttribute('data-tmdb-id') || '0';
    var type      = container.getAttribute('data-content-type') || 'tv';
    var season    = container.getAttribute('data-season') || '0';
    var episode   = container.getAttribute('data-episode') || '0';

    var epKey     = (type === 'tv' && parseInt(season, 10) > 0 && parseInt(episode, 10) > 0)
      ? seriesId + '_s' + season + '_e' + episode
      : seriesId;
    var cwKey     = String(seriesId);

    try {
      var isLoggedIn = localStorage.getItem('short_is_logged_in') === '1';
      var uid = isLoggedIn ? localStorage.getItem('short_user_uid') : null;
      var activeProfile = localStorage.getItem('short_active_profile_id') || 'profile_1';
      var scopedKey = uid ? ('short_continue_watching_' + uid + '_' + activeProfile) : ('short_continue_watching_guest_' + activeProfile);
      var fallbackKey = uid ? ('shorttv_history_' + uid) : 'short_guest_watch_history';
      var storage = JSON.parse(localStorage.getItem(scopedKey) || localStorage.getItem(fallbackKey) || '{}');
      var prev = storage[epKey] || storage[cwKey];
      
      if (prev && type === 'tv' && prev.season && prev.episode) {
        if (parseInt(prev.season, 10) !== parseInt(season, 10) || parseInt(prev.episode, 10) !== parseInt(episode, 10)) {
          prev = null;
        }
      }

      if (prev && prev.currentTime > 10 && prev.percent < 95) {
        videoEl.addEventListener('loadedmetadata', function () {
          videoEl.currentTime = prev.currentTime;
          showToast('<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; vertical-align:middle; margin-right:4px;"><polygon points="5 3 19 12 5 21 5 3"/></svg> Resumed from ' + formatTime(prev.currentTime));
        }, { once: true });
      }
    } catch (e) {}
  }

  // ─── Utility Functions ─────────────────────────────────────────────
  function showToast(msg) {
    var toast = document.getElementById('player-toast-notice');
    if (!toast) return;
    toast.innerHTML = msg;
    toast.style.display = 'inline-flex';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
      toast.style.display = 'none';
    }, 2500);
  }

  function formatTime(sec) {
    var s = Math.floor(sec || 0);
    var m = Math.floor(s / 60);
    var hrs = Math.floor(m / 60);
    var remM = m % 60;
    var remS = s % 60;
    if (hrs > 0) {
      return (hrs < 10 ? '0' : '') + hrs + ':' + (remM < 10 ? '0' : '') + remM + ':' + (remS < 10 ? '0' : '') + remS;
    }
    return (remM < 10 ? '0' : '') + remM + ':' + (remS < 10 ? '0' : '') + remS;
  }

  function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ─── Public API ────────────────────────────────────────────────────
  return {
    init: init,
    loadSource: loadSource,
    setQuality: setQuality,
    setSubtitle: setSubtitle,
    showToast: showToast,
  };

})();

// Export to window
if (typeof window !== 'undefined') {
  window.shortPlayer = shortPlayer;
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
  if (document.getElementById('short-player-app') && document.getElementById('short-player-app').hasAttribute('data-sources')) {
    shortPlayer.init();
  }
});
