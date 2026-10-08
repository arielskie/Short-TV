<?php
/**
 * Template Name: My List / Saved Dramas
 *
 * @package Short_Stream
 */

get_header();
?>

<div class="short-page-container short-my-dramas-container" style="max-width: 1440px; margin: 0 auto; padding: 100px 28px 60px;">
	<div class="page-header-row" style="margin-bottom: 24px;">
		<div>
			<h1 class="page-main-title" style="font-size: 28px; font-weight: 800; color: #fff; margin-bottom: 6px;"><?php _e( 'Saved Short Dramas', 'short-stream' ); ?></h1>
			<p style="color: #888; font-size: 14px; margin: 0;"><?php _e( 'Your saved vertical short drama series for binge-watching anytime.', 'short-stream' ); ?></p>
		</div>
	</div>

	<!-- Loading state (strictly the only state visible at start) -->
	<div id="my-list-loading" class="my-list-state-box" style="display:block;text-align:center;padding:70px 20px;color:#aaa;font-size:14px;">
		<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ff2d55" stroke-width="2.5" style="animation:spin 0.9s linear infinite;margin-bottom:14px;display:block;margin-left:auto;margin-right:auto;"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="12"/><path d="M12 6v6l4 2"/></svg>
		<span>Loading your saved dramas…</span>
	</div>

	<!-- Sign-in prompt (shown strictly when not logged in and no local saves) -->
	<div id="my-list-signin-prompt" class="my-list-state-box" style="display:none;text-align:center;padding:60px 20px;">
		<div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,0.05); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px; color: #888;">
			<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
		</div>
		<div style="color:#fff;font-size:18px;font-weight:700;margin-bottom:8px;">Sign in to view your saved list</div>
		<div style="color:#888;font-size:13px;max-width:380px;margin:0 auto 22px;line-height:1.5;">Sync your saved short dramas across all your devices and pick up right where you left off.</div>
		<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" style="display:inline-block;padding:12px 30px;background:#ff2d55;color:#fff;border-radius:10px;font-weight:700;text-decoration:none;font-size:14px;transition:opacity 0.2s;">Sign In</a>
	</div>

	<!-- Empty state (shown strictly after check completes and confirms 0 saved dramas) -->
	<div id="my-list-empty" class="my-list-state-box" style="display:none;text-align:center;padding:60px 20px;">
		<div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255,45,85,0.08); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 18px; color: #ff2d55;">
			<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
		</div>
		<div style="color:#fff;font-size:18px;font-weight:700;margin-bottom:8px;">No Saved Dramas Yet</div>
		<div style="color:#888;font-size:13px;max-width:380px;margin:0 auto 22px;line-height:1.5;">Tap the bookmark icon on any drama to save it here for quick binge-watching anytime.</div>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-block;padding:12px 30px;background:#ff2d55;color:#fff;border-radius:10px;font-weight:700;text-decoration:none;font-size:14px;transition:opacity 0.2s;">Explore Dramas</a>
	</div>

	<!-- Cards Grid -->
	<div class="short-drama-portrait-grid" id="my-list-cards-grid" style="display:none;">
	</div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
#my-list-cards-grid {
	display: none;
	grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
	gap: 18px 14px;
	width: 100%;
}
#my-list-cards-grid.is-active-grid {
	display: grid !important;
}
@media (max-width: 768px) {
	.short-my-dramas-container {
		padding: 70px 10px 80px !important;
	}
	.page-header-row {
		margin-bottom: 14px !important;
	}
	.page-main-title {
		font-size: 20px !important;
		margin-bottom: 4px !important;
	}
	.page-header-row p {
		font-size: 12px !important;
	}
	#my-list-cards-grid {
		grid-template-columns: repeat(3, 1fr) !important;
		gap: 10px 6px !important;
	}
	.short-drama-card {
		border-radius: 7px !important;
		background: #141418 !important;
		border: 1px solid #202026 !important;
	}
	.short-drama-poster-wrap {
		aspect-ratio: 3/4.2 !important;
		border-radius: 6px 6px 0 0 !important;
	}
	.short-drama-play-badge {
		bottom: 4px !important;
		left: 4px !important;
		font-size: 8px !important;
		padding: 1px 4px !important;
		border-radius: 3px !important;
	}
	.short-drama-play-badge svg {
		width: 7px !important;
		height: 7px !important;
	}
	.short-drama-overlay-btn {
		width: 20px !important;
		height: 20px !important;
		top: 4px !important;
		right: 4px !important;
	}
	.short-drama-overlay-btn svg {
		width: 10px !important;
		height: 10px !important;
	}
	.short-drama-info-box {
		padding: 5px 5px 6px !important;
		gap: 2px !important;
	}
	.short-drama-title {
		font-size: 11px !important;
		font-weight: 700 !important;
		line-height: 1.25 !important;
	}
	.short-drama-sub {
		font-size: 9px !important;
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
	box-shadow: 0 10px 25px rgba(0,0,0,0.6);
	border-color: #383842;
}
.short-drama-poster-wrap {
	position: relative;
	width: 100%;
	aspect-ratio: 3/4;
	overflow: hidden;
	display: block;
	background: #121214;
}
.short-drama-poster-img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
	transition: transform 0.3s;
}
.short-drama-card:hover .short-drama-poster-img { transform: scale(1.04); }
.short-drama-overlay-btn {
	position: absolute;
	top: 8px; right: 8px;
	width: 26px; height: 26px;
	border-radius: 50%;
	background: rgba(0,0,0,0.65);
	backdrop-filter: blur(4px);
	border: 1px solid rgba(255,255,255,0.15);
	color: #fff;
	display: flex; align-items: center; justify-content: center;
	cursor: pointer;
	z-index: 5;
	transition: background 0.2s, transform 0.2s;
}
.short-drama-overlay-btn:hover { background: #ff2d55; transform: scale(1.1); }
.short-drama-play-badge {
	position: absolute;
	bottom: 8px; left: 8px;
	background: rgba(0,0,0,0.7);
	backdrop-filter: blur(4px);
	color: #fff; font-size: 10px; font-weight: 700;
	padding: 2px 7px; border-radius: 5px;
	display: flex; align-items: center; gap: 3px;
}
.short-drama-info-box {
	padding: 10px 12px;
	display: flex; flex-direction: column; gap: 3px;
}
.short-drama-title {
	font-size: 13.5px; font-weight: 700; color: #fff;
	white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
	text-decoration: none;
}
.short-drama-title:hover { color: #ff2d55; }
.short-drama-sub {
	display: flex; align-items: center; justify-content: space-between;
	font-size: 11px; color: #888;
}
</style>

<script>
(function() {
  window._myListHandledByTemplate = true;
  var rtdbBase = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
  var watchUrl = '<?php echo esc_url( home_url( '/watch/' ) ); ?>';

  function showState(state) {
    var loadingEl = document.getElementById('my-list-loading');
    var signinEl  = document.getElementById('my-list-signin-prompt');
    var emptyEl   = document.getElementById('my-list-empty');
    var gridEl    = document.getElementById('my-list-cards-grid');

    if (loadingEl) loadingEl.style.display = (state === 'loading') ? 'block' : 'none';
    if (signinEl)  signinEl.style.display  = (state === 'signin')  ? 'block' : 'none';
    if (emptyEl)   emptyEl.style.display   = (state === 'empty')   ? 'block' : 'none';
    if (gridEl) {
      if (state === 'grid') {
        gridEl.style.display = 'grid';
        gridEl.classList.add('is-active-grid');
      } else {
        gridEl.style.display = 'none';
        gridEl.classList.remove('is-active-grid');
      }
    }
  }

  function escHtml(s) {
    return String(s || '').replace(/[&<>"']/g, function(c) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }

  function buildCard(id, item) {
    var poster    = item.poster || item.poster_path || item.cover || item.backdrop_path || '';
    var title     = (item.title || item.name || 'Short Drama').trim();
    var watchLink = watchUrl + '?id=' + encodeURIComponent(id);
    var rating    = item.vote_average ? parseFloat(item.vote_average).toFixed(1) : (item.rating || '4.8');

    return '<div class="short-drama-card" id="mylist-card-' + id + '">' +
      '<a href="' + watchLink + '" class="short-drama-poster-wrap">' +
        (poster
          ? '<img class="short-drama-poster-img" src="' + escHtml(poster) + '" alt="' + escHtml(title) + '" loading="lazy" />'
          : '<div style="width:100%;height:100%;background:#222;display:flex;align-items:center;justify-content:center;">' +
              '<svg width="32" height="32" viewBox="0 0 24 24" fill="#444"><path d="M8 5v14l11-7z"/></svg>' +
            '</div>') +
        '<div class="short-drama-play-badge"><svg width="10" height="10" viewBox="0 0 24 24" fill="#fff"><path d="M8 5v14l11-7z"/></svg>Play</div>' +
      '</a>' +
      '<button class="short-drama-overlay-btn" title="Remove from list" onclick="removeItem(\'' + id + '\')">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>' +
      '</button>' +
      '<div class="short-drama-info-box">' +
        '<a href="' + watchLink + '" class="short-drama-title">' + escHtml(title) + '</a>' +
        '<div class="short-drama-sub"><span style="color:#ffc107;">★ ' + rating + '</span><span>Short TV</span></div>' +
      '</div>' +
    '</div>';
  }

  function renderList(data) {
    var grid = document.getElementById('my-list-cards-grid');
    if (!grid) return;

    if (!data) {
      grid.innerHTML = '';
      showState('empty');
      return;
    }

    var normalized = {};
    if (Array.isArray(data)) {
      data.forEach(function(item, idx) {
        if (item && typeof item === 'object' && (item.title || item.name || item.id)) {
          var id = String(item.id || idx);
          normalized[id] = item;
        }
      });
    } else if (typeof data === 'object') {
      Object.keys(data).forEach(function(k) {
        var item = data[k];
        if (item && typeof item === 'object' && (item.title || item.name || item.id)) {
          var id = String(item.id || k);
          normalized[id] = item;
        }
      });
    }

    var validKeys = Object.keys(normalized).filter(function(id) {
      var item = normalized[id];
      if (!item) return false;
      var title = (item.title || item.name || '').trim().toLowerCase();
      return title && title !== 'untitled' && title !== 'untitled title' && !title.startsWith('untitled');
    });

    if (validKeys.length === 0) {
      grid.innerHTML = '';
      showState('empty');
      return;
    }

    var keys = validKeys.sort(function(a, b) {
      var timeA = normalized[a].added_at || normalized[a].savedAt || 0;
      var timeB = normalized[b].added_at || normalized[b].savedAt || 0;
      return timeB - timeA;
    });

    var html = '';
    keys.forEach(function(id) {
      html += buildCard(id, normalized[id]);
    });

    grid.innerHTML = html;
    showState('grid');
  }

  window.removeItem = function(id) {
    var card = document.getElementById('mylist-card-' + id);
    if (card) card.style.opacity = '0.3';

    var uid = window._myListUid || localStorage.getItem('short_user_uid');

    // Remove from RTDB
    if (uid) {
      try {
        if (typeof firebase !== 'undefined' && firebase.database) {
          firebase.database().ref('users/' + uid + '/watchlist/' + id).remove().catch(function(){});
          firebase.database().ref('users/' + uid + '/my_list/' + id).remove().catch(function(){});
        }
      } catch(e) {}
      fetch(rtdbBase + '/users/' + uid + '/watchlist/' + id + '.json', { method: 'DELETE' }).catch(function(){});
      fetch(rtdbBase + '/users/' + uid + '/my_list/' + id + '.json', { method: 'DELETE' }).catch(function(){});
    }

    // Remove from localStorage
    try {
      for (var k in localStorage) {
        if (k.startsWith('short_my_list') || k.startsWith('short_watchlist') || k === 'stv_watchlist_guest' || k === 'short_guest_watchlist') {
          var local = JSON.parse(localStorage.getItem(k) || '{}');
          if (local && local[id]) {
            delete local[id];
            localStorage.setItem(k, JSON.stringify(local));
          }
        }
      }
    } catch(e) {}

    // Update buttons in app.js if present
    if (window.SHORT && typeof window.SHORT.toggleMyList === 'function' && window.SHORT.myLocalList) {
      window.SHORT.myLocalList.delete(String(id));
    }

    setTimeout(function() {
      if (card) card.remove();
      checkEmpty();
    }, 350);
  };

  function checkEmpty() {
    var grid = document.getElementById('my-list-cards-grid');
    if (grid && !grid.querySelector('.short-drama-card')) {
      showState('empty');
    }
  }

  function getAllLocalSaves() {
    var combined = {};
    try {
      for (var k in localStorage) {
        if (k.startsWith('short_my_list') || k.startsWith('short_watchlist') || k === 'stv_watchlist_guest' || k === 'short_guest_watchlist') {
          var local = JSON.parse(localStorage.getItem(k) || '{}');
          if (local && typeof local === 'object') {
            Object.assign(combined, local);
          }
        }
      }
    } catch(e) {}
    return combined;
  }

  function init() {
    showState('loading');

    // Immediate local cache render
    var cached = getAllLocalSaves();
    if (Object.keys(cached).length > 0) {
      renderList(cached);
    }

    var checked = false;

    function handleAuthUser(user) {
      if (checked) return;
      checked = true;

      var uid = (user && user.uid) || localStorage.getItem('short_user_uid');

      if (!uid) {
        var localData = getAllLocalSaves();
        if (Object.keys(localData).length > 0) {
          renderList(localData);
        } else {
          showState('empty');
        }
        return;
      }

      window._myListUid = uid;
      loadFromRtdbOrLocal(uid);
    }

    function loadFromRtdbOrLocal(uid) {
      // 1. RTDB SDK Realtime Sync
      try {
        if (typeof firebase !== 'undefined' && firebase.database) {
          firebase.database().ref('users/' + uid + '/watchlist').on('value', function(snapshot) {
            var data = snapshot.val();
            if (data && typeof data === 'object' && Object.keys(data).length > 0) {
              renderList(data);
            }
          });
        }
      } catch(e) {}

      // 2. Direct REST GET
      fetch(rtdbBase + '/users/' + uid + '/watchlist.json')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data && (Array.isArray(data) ? data.length > 0 : Object.keys(data).length > 0)) {
            renderList(data);
          } else {
            // Check fallback path my_list
            fetch(rtdbBase + '/users/' + uid + '/my_list.json')
              .then(function(r2) { return r2.json(); })
              .then(function(data2) {
                if (data2 && (Array.isArray(data2) ? data2.length > 0 : Object.keys(data2).length > 0)) {
                  renderList(data2);
                } else {
                  var localData = getAllLocalSaves();
                  if (Object.keys(localData).length > 0) {
                    renderList(localData);
                  } else {
                    showState('empty');
                  }
                }
              })
              .catch(function() {
                var localData = getAllLocalSaves();
                if (Object.keys(localData).length > 0) {
                  renderList(localData);
                } else {
                  showState('empty');
                }
              });
          }
        })
        .catch(function() {
          var localData = getAllLocalSaves();
          if (Object.keys(localData).length > 0) {
            renderList(localData);
          } else {
            showState('empty');
          }
        });
    }

    // Initialize Firebase Auth listener
    if (typeof firebase !== 'undefined') {
      var cfg = window.SHORT_CONFIG && window.SHORT_CONFIG.firebase;
      try {
        if (!firebase.apps.length) {
          firebase.initializeApp({
            apiKey: (cfg && cfg.apiKey) || 'AIzaSyDummyKeyForRTDB',
            databaseURL: rtdbBase,
            projectId: 'shorttv-fd9ef',
            authDomain: 'shorttv-fd9ef.firebaseapp.com'
          });
        }
      } catch(e) {}

      if (firebase.auth) {
        firebase.auth().onAuthStateChanged(function(user) {
          handleAuthUser(user);
        });
        setTimeout(function() {
          if (!checked) {
            handleAuthUser(firebase.auth().currentUser || (localStorage.getItem('short_user_uid') ? { uid: localStorage.getItem('short_user_uid') } : null));
          }
        }, 1200);
      } else {
        handleAuthUser(localStorage.getItem('short_user_uid') ? { uid: localStorage.getItem('short_user_uid') } : null);
      }
    } else {
      setTimeout(function() {
        if (typeof firebase !== 'undefined' && firebase.auth) {
          firebase.auth().onAuthStateChanged(function(user) {
            handleAuthUser(user);
          });
        } else {
          handleAuthUser(localStorage.getItem('short_user_uid') ? { uid: localStorage.getItem('short_user_uid') } : null);
        }
      }, 300);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>

<?php
get_footer();

