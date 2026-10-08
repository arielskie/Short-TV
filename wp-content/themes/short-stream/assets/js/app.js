/**
 * Short Stream - Master Frontend Engine
 * Version: 1.0.0
 */

(function () {
  'use strict';

  window.SHORT = window.SHORT || {};
  window.SHORT_CONFIG = window.SHORT_CONFIG || window.SHORT_CONFIG || {};
  const config = window.SHORT_CONFIG;
  let db = null;
  let auth = null;
  let currentUser = null;
  
  // Auto-migrate legacy localStorage keys (old prefix → new prefix)
  try {
    ['active_avatar', 'active_profile_id', 'custom_profiles', 'is_kid', 'subscription', 'watch_history', 'continue_watching'].forEach(function(k) {
      var legacyKey = 'short_legacy_migrate_' + k;
      if (!localStorage.getItem(legacyKey)) {
        var oldKeys = ['short_' + k];
        oldKeys.forEach(function(ok) {
          var v = localStorage.getItem(ok);
          if (v && !localStorage.getItem('short_' + k)) {
            localStorage.setItem('short_' + k, v);
          }
        });
        localStorage.setItem(legacyKey, '1');
      }
    });
  } catch(e) {}

  let activeProfileId = localStorage.getItem('short_active_profile_id') || 'profile_1';
  let myLocalList = new Set();

  /**
   * Premium Netflix-style Custom Modal Popup for Alerts and Prompts
   */
  function shortCustomAlert(options) {
    return new Promise(function(resolve) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      const title = options.title || 'Notification';
      const message = options.message || '';
      const buttonText = options.buttonText || 'OK';
      const iconType = options.type || 'info'; // 'success', 'info', 'warning', 'error'

      let iconSvg = '';
      let accentCol = 'var(--theme-accent, #E50914)';
      let bgAccent = 'rgba(var(--theme-accent-rgb, 229, 9, 20), 0.15)';

      if (iconType === 'success') {
        accentCol = '#46d369';
        bgAccent = 'rgba(70, 211, 105, 0.15)';
        iconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="${accentCol}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>`;
      } else if (iconType === 'error') {
        accentCol = '#e50914';
        bgAccent = 'rgba(229, 9, 20, 0.15)';
        iconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="${accentCol}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;
      } else if (iconType === 'email') {
        accentCol = 'var(--theme-accent, #E50914)';
        bgAccent = 'rgba(var(--theme-accent-rgb, 229, 9, 20), 0.15)';
        iconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="${accentCol}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>`;
      } else {
        iconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="${accentCol}" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>`;
      }

      const overlay = document.createElement('div');
      overlay.className = 'short-custom-popup-overlay';
      overlay.innerHTML = `
        <div class="short-custom-popup-box">
          <button type="button" class="short-custom-popup-close" aria-label="Close">&times;</button>
          <div class="short-custom-popup-icon" style="background:${bgAccent}; border-color:${accentCol};">
            ${iconSvg}
          </div>
          <h3 class="short-custom-popup-title">${title}</h3>
          <p class="short-custom-popup-msg">${message}</p>
          <div class="short-custom-popup-actions">
            <button type="button" class="short-custom-popup-btn-confirm">${buttonText}</button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);

      // Trigger enter animation
      requestAnimationFrame(function() {
        overlay.classList.add('is-visible');
        const btn = overlay.querySelector('.short-custom-popup-btn-confirm');
        if (btn) btn.focus();
      });

      function closePopup() {
        overlay.classList.remove('is-visible');
        setTimeout(function() {
          if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
          resolve(true);
        }, 250);
      }

      overlay.querySelector('.short-custom-popup-btn-confirm').addEventListener('click', closePopup);
      overlay.querySelector('.short-custom-popup-close').addEventListener('click', closePopup);
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closePopup();
      });
      overlay.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Enter') {
          e.preventDefault();
          closePopup();
        }
      });
    });
  }

  function shortCustomPrompt(options) {
    return new Promise(function(resolve) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      const title = options.title || 'Enter Information';
      const message = options.message || '';
      const placeholder = options.placeholder || '';
      const defaultValue = options.defaultValue || '';
      const inputType = options.inputType || 'text';
      const confirmText = options.confirmText || 'Submit';
      const cancelText = options.cancelText || 'Cancel';
      const iconType = options.type || 'email';

      let promptIconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--theme-accent, #E50914)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>`;
      if (iconType === 'lock') {
        promptIconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--theme-accent, #E50914)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>`;
      }

      const overlay = document.createElement('div');
      overlay.className = 'short-custom-popup-overlay';
      overlay.innerHTML = `
        <div class="short-custom-popup-box">
          <button type="button" class="short-custom-popup-close" aria-label="Close">&times;</button>
          <div class="short-custom-popup-icon" style="background:rgba(var(--theme-accent-rgb, 229, 9, 20), 0.15); border-color:var(--theme-accent, #E50914);">
            ${promptIconSvg}
          </div>
          <h3 class="short-custom-popup-title">${title}</h3>
          <p class="short-custom-popup-msg">${message}</p>
          <div class="short-custom-popup-input-wrap">
            <input type="${inputType}" class="short-custom-popup-input" placeholder="${placeholder}" value="${defaultValue}">
          </div>
          <div class="short-custom-popup-actions" style="gap:10px;">
            <button type="button" class="short-custom-popup-btn-confirm" style="flex:1;">${confirmText}</button>
            <button type="button" class="short-custom-popup-btn-cancel" style="flex:1;">${cancelText}</button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);

      const input = overlay.querySelector('.short-custom-popup-input');

      requestAnimationFrame(function() {
        overlay.classList.add('is-visible');
        if (input) {
          input.focus();
          input.select();
        }
      });

      function closePopup(val) {
        overlay.classList.remove('is-visible');
        setTimeout(function() {
          if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
          resolve(val);
        }, 250);
      }

      overlay.querySelector('.short-custom-popup-btn-confirm').addEventListener('click', function() {
        closePopup(input ? input.value : '');
      });
      overlay.querySelector('.short-custom-popup-btn-cancel').addEventListener('click', function() {
        closePopup(null);
      });
      overlay.querySelector('.short-custom-popup-close').addEventListener('click', function() {
        closePopup(null);
      });
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closePopup(null);
      });
      overlay.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          closePopup(null);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          closePopup(input ? input.value : '');
        }
      });
    });
  }

  function shortCustomConfirm(options) {
    return new Promise(function(resolve) {
      if (typeof options === 'string') {
        options = { message: options };
      }
      const title = options.title || 'Confirm Action';
      const message = options.message || '';
      const confirmText = options.confirmText || 'Confirm';
      const cancelText = options.cancelText || 'Cancel';
      const isSub = options.type === 'sub_required';

      let iconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;
      let iconBg = 'rgba(239, 68, 68, 0.15)';
      let iconBorder = '#ef4444';
      let btnClass = 'is-danger';

      if (isSub) {
        iconSvg = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e50914" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>`;
        iconBg = 'rgba(229, 9, 20, 0.15)';
        iconBorder = '#e50914';
        btnClass = '';
      }

      const overlay = document.createElement('div');
      overlay.className = 'short-custom-popup-overlay' + (isSub ? ' short-sub-required-popup' : '');
      overlay.innerHTML = `
        <div class="short-custom-popup-box">
          <div class="short-custom-popup-topbar">
            <button type="button" class="btn-popup-back" aria-label="Back">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
              <span>Back</span>
            </button>
          </div>
          <div class="short-custom-popup-icon" style="background:${iconBg}; border-color:${iconBorder};">
            ${iconSvg}
          </div>
          <h3 class="short-custom-popup-title">${title}</h3>
          <p class="short-custom-popup-msg">${message}</p>
          <div class="short-custom-popup-actions">
            <button type="button" class="short-custom-popup-btn-confirm ${btnClass}">${confirmText}</button>
            <button type="button" class="short-custom-popup-btn-cancel">${cancelText}</button>
          </div>
        </div>
      `;

      document.body.appendChild(overlay);

      requestAnimationFrame(function() {
        overlay.classList.add('is-visible');
        const confirmBtn = overlay.querySelector('.short-custom-popup-btn-confirm');
        if (confirmBtn) confirmBtn.focus();
      });

      function closePopup(result) {
        overlay.classList.remove('is-visible');
        setTimeout(function() {
          if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
          resolve(result);
        }, 250);
      }

      overlay.querySelector('.short-custom-popup-btn-confirm').addEventListener('click', function() {
        closePopup(true);
      });
      overlay.querySelector('.short-custom-popup-btn-cancel').addEventListener('click', function() {
        closePopup(false);
      });
      const backBtn = overlay.querySelector('.btn-popup-back');
      if (backBtn) {
        backBtn.addEventListener('click', function() {
          closePopup(false);
        });
      }
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closePopup(false);
      });
      overlay.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          e.preventDefault();
          closePopup(false);
        }
      });
    });
  }

  window.shortCustomAlert = shortCustomAlert;
  window.shortCustomConfirm = shortCustomConfirm;
  window.shortCustomPrompt = shortCustomPrompt;

  function getSubscription() {
    // Demo / Guest mode check: VIP memberships are only valid for authenticated accounts
    let isRealUser = false;
    if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser && firebase.auth().currentUser.email) {
      isRealUser = true;
    }
    const localEmail = localStorage.getItem('short_user_email');
    if (localEmail && localEmail !== 'guest@short.local' && localStorage.getItem('short_is_logged_in') === '1') {
      isRealUser = true;
    }

    if (!isRealUser) {
      // Purge any stale demo subscription if user is in guest / demo mode
      if (localStorage.getItem('short_subscription')) {
        localStorage.removeItem('short_subscription');
        localStorage.removeItem('short_sub_tier');
      }
      return null;
    }

    try {
      const stored = localStorage.getItem('short_subscription');
      if (stored) {
        const parsed = JSON.parse(stored);
        if (parsed && (parsed.status === 'active' || parsed.plan_id)) {
          return parsed;
        }
      }
    } catch (e) {}

    try {
      const cookieMatch = document.cookie.match(/short_sub_tier=([^;]+)/);
      if (cookieMatch && ['basic', 'standard', 'premium', 'active'].indexOf(cookieMatch[1].toLowerCase()) !== -1) {
        return { plan_id: cookieMatch[1].toLowerCase(), status: 'active', plan_name: cookieMatch[1].toUpperCase() };
      }
    } catch (e) {}

    return null;
  }

  function hasActiveSubscription() {
    const paywallConfig = config.subscription && ('enable_paywall' in config.subscription) ? config.subscription.enable_paywall : true;
    if (!paywallConfig) {
      return true;
    }
    const sub = getSubscription();
    return !!(sub && (sub.status === 'active' || sub.plan_id));
  }

  function showSubscriptionRequiredModal(title, message, ctaText) {
    const heading = title || 'Subscription Required';
    const text = message || 'A streaming subscription is required to access this feature. Subscribe now to unlock unlimited streaming and build your personal watchlist.';
    const cta = ctaText || 'View Plans & Pricing';

    return shortCustomConfirm({
      title: heading,
      message: text,
      confirmText: cta,
      cancelText: 'Maybe Later',
      type: 'sub_required'
    }).then(function(confirmed) {
      if (confirmed) {
        window.location.href = (config.site_url || '') + '/subscription/';
      }
      return confirmed;
    });
  }

  window.hasActiveSubscription = hasActiveSubscription;
  window.getSubscription = getSubscription;
  window.showSubscriptionRequiredModal = showSubscriptionRequiredModal;

  window.SHORT.customAlert = shortCustomAlert;
  window.SHORT.customPrompt = shortCustomPrompt;
  window.SHORT.customConfirm = shortCustomConfirm;
  window.SHORT.hasActiveSubscription = hasActiveSubscription;
  window.SHORT.getSubscription = getSubscription;
  window.SHORT.showSubscriptionRequiredModal = showSubscriptionRequiredModal;

  function initFirebase() {
    if (typeof firebase !== 'undefined' && config.firebase && config.firebase.apiKey) {
      try {
        if (!config.firebase.databaseURL) {
          config.firebase.databaseURL = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
        }
        if (!firebase.apps.length) {
          firebase.initializeApp(config.firebase);
        }
        auth = firebase.auth();
        // Only init Firestore if the SDK is loaded (we use RTDB now)
        if (firebase.firestore) {
          db = firebase.firestore();
        }

        auth.onAuthStateChanged(function (user) {
          currentUser = user;
          handleAuthStateChange(user);
        });
      } catch (err) {
        console.warn('[Short] Firebase init failed:', err);
      }
    }
  }

  function handleAuthStateChange(user) {
    const guestView = document.getElementById('auth-guest-view');
    const userView = document.getElementById('auth-user-view');
    const authLinks = document.querySelectorAll('.auth-required, .auth-required-inline');
    const guestLinks = document.querySelectorAll('.guest-only');
    const avatarImg = document.getElementById('header-current-avatar');
    const currentPath = window.location.pathname;

    const isAuthPage = currentPath.includes('/login') || currentPath.includes('/register') || currentPath.includes('/forgot-password');
    const isProfilePage = currentPath.includes('/profile');
    const isSubscriptionPage = currentPath.includes('/subscription') || currentPath.includes('/plans');

    if (user) {
      localStorage.setItem('short_is_logged_in', '1');
      if (user.email) localStorage.setItem('short_user_email', user.email);
      if (user.displayName) localStorage.setItem('short_user_display_name', user.displayName);
      if (user.uid) localStorage.setItem('short_user_uid', user.uid);
      const userPhoto = user.photoURL || (user.providerData && user.providerData[0] && user.providerData[0].photoURL) || '';
      if (userPhoto) {
        localStorage.setItem('short_user_photo', userPhoto);
        localStorage.setItem('short_active_avatar', userPhoto);
        localStorage.setItem('shorttv_user_avatar', userPhoto);
      }
      if (typeof initAccountSync === 'function') initAccountSync();

      document.documentElement.classList.remove('is-guest-state');
      if (guestView) guestView.style.display = 'none';
      if (userView) userView.style.display = 'block';
      document.body.classList.remove('guest-landing-active');
      const mobileBottomNav = document.querySelector('.short-mobile-bottom-nav');
      if (mobileBottomNav) {
        mobileBottomNav.style.display = '';
      }
      const mainHeader = document.getElementById('short-header');
      if (mainHeader) mainHeader.style.display = '';

      // Update header profile dropdown with real user email & name
      const headerDropdownName = document.getElementById('header-dropdown-name');
      const headerDropdownEmail = document.getElementById('header-dropdown-email');
      const headerLogoutBtn = document.getElementById('short-btn-logout');
      const headerLoginNav = document.getElementById('short-btn-login-nav');
      if (headerDropdownName) headerDropdownName.textContent = user.displayName || (user.email ? user.email.split('@')[0] : 'ShortTV Member');
      if (headerDropdownEmail) headerDropdownEmail.textContent = user.email || '';
      if (headerLogoutBtn) headerLogoutBtn.style.display = 'flex';
      if (headerLoginNav) headerLoginNav.style.display = 'none';

      authLinks.forEach(function (el) { el.style.display = ''; });
      guestLinks.forEach(function (el) { el.style.display = 'none'; });
      const defaultIcon = document.getElementById('header-default-user-icon');
      if (avatarImg) {
        const photo = user.photoURL || (user.providerData && user.providerData[0] && user.providerData[0].photoURL) || '';
        if (photo && !photo.includes('avatar-') && !photo.includes('avatar_') && !photo.startsWith('data:image/svg')) {
          avatarImg.src = photo;
          avatarImg.classList.add('has-custom-avatar');
          if (defaultIcon) defaultIcon.style.display = 'none';
        } else {
          avatarImg.src = '';
          avatarImg.classList.remove('has-custom-avatar');
          if (defaultIcon) defaultIcon.style.display = 'block';
        }
        const mobNavAvatar = document.getElementById('mobile-nav-account-avatar');
        if (mobNavAvatar && photo) {
          mobNavAvatar.src = photo;
        }
      }

      // If logged in but on the login/register page, send to homepage
      if (isAuthPage) {
        const urlParams = new URLSearchParams(window.location.search);
        const red = urlParams.get('redirect_to') || urlParams.get('redirect');
        if (red && red.startsWith('/') && !red.startsWith('//') && !red.includes('/profile')) {
          window.location.href = red;
        } else {
          window.location.href = (config.site_url || '') + '/';
        }
        return;
      }

      // Synchronize Coins from Firebase RTDB for logged-in user
      if (typeof firebase !== 'undefined' && firebase.database) {
        firebase.database().ref('users/' + user.uid + '/coins').on('value', function(snap) {
          const v = snap.val();
          if (v !== null && v !== undefined) {
            const coinsVal = parseInt(v, 10);
            localStorage.setItem('shorttv_user_coins', String(coinsVal));
            if (typeof window.syncHeaderCoins === 'function') window.syncHeaderCoins();
          }
        });
      }

      // Synchronize VIP Pass from Firebase RTDB
      if (typeof firebase !== 'undefined' && firebase.database) {
        firebase.database().ref('users/' + user.uid + '/subscription').once('value', function(snap) {
          const sub = snap.val();
          if (sub && sub.status === 'active') {
            localStorage.setItem('short_subscription', JSON.stringify(sub));
            localStorage.setItem('short_sub_tier', sub.plan_id || 'standard');
            document.cookie = 'short_sub_tier=' + encodeURIComponent(sub.plan_id || 'standard') + '; path=/; max-age=2592000; SameSite=Lax';
            if (typeof initProfileMembershipCard === 'function') initProfileMembershipCard();
            if (typeof window.syncHeaderVipBadge === 'function') window.syncHeaderVipBadge();
          }
        });
      }
      if (typeof window.syncHeaderVipBadge === 'function') window.syncHeaderVipBadge();
      if (typeof window.syncHeaderCoins === 'function') window.syncHeaderCoins();

      initProfileSync(user.uid);
      initMyListSync(user.uid);
      initLikesSync(user.uid);
      initContinueWatchingSync(user.uid);
    } else {
      // Guest / Demo Mode: Not signed in automatically
      localStorage.removeItem('short_user_email');
      localStorage.removeItem('short_user_display_name');
      localStorage.removeItem('short_user_uid');
      localStorage.removeItem('short_user_photo');
      localStorage.removeItem('shorttv_user_avatar');
      localStorage.removeItem('short_active_avatar');
      localStorage.removeItem('short_active_profile');
      localStorage.removeItem('short_active_profile_id');
      localStorage.removeItem('short_active_profile_name');
      localStorage.removeItem('short_is_logged_in');
      localStorage.removeItem('short_subscription');
      localStorage.removeItem('short_sub_tier');
      localStorage.setItem('shorttv_user_coins', '100'); // Reset to default guest coins
      if (typeof cwUnsubscribe === 'function') {
        cwUnsubscribe();
        cwUnsubscribe = null;
      }
      if (typeof window.syncHeaderCoins === 'function') window.syncHeaderCoins();
      if (typeof window.syncHeaderVipBadge === 'function') window.syncHeaderVipBadge();
      if (typeof window.syncMobileNavAvatar === 'function') window.syncMobileNavAvatar();
      if (typeof window.syncContinueWatchingRow === 'function') window.syncContinueWatchingRow();
      renderContinueWatching();
      document.documentElement.classList.add('is-guest-state');

      // Update header profile dropdown to show Guest state and Login link
      const headerDropdownName = document.getElementById('header-dropdown-name');
      const headerDropdownEmail = document.getElementById('header-dropdown-email');
      const headerLogoutBtn = document.getElementById('short-btn-logout');
      const headerLoginNav = document.getElementById('short-btn-login-nav');
      if (headerDropdownName) headerDropdownName.textContent = 'Guest Viewer';
      if (headerDropdownEmail) headerDropdownEmail.textContent = 'Demo Mode (Not signed in)';
      if (headerLogoutBtn) headerLogoutBtn.style.display = 'none';
      if (headerLoginNav) headerLoginNav.style.display = 'flex';

      if (avatarImg) {
        avatarImg.src = '';
        avatarImg.classList.remove('has-custom-avatar');
      }
      const defaultIcon = document.getElementById('header-default-user-icon');
      if (defaultIcon) defaultIcon.style.display = 'block';

      const mainHeader = document.getElementById('short-header');
      const mobileBottomNav = document.querySelector('.short-mobile-bottom-nav');
      if (mainHeader) mainHeader.style.display = '';
      if (mobileBottomNav) mobileBottomNav.style.display = '';
      if (guestView) guestView.style.display = 'block';
      if (userView) userView.style.display = 'none';

      authLinks.forEach(function (el) { el.style.display = 'none'; });
      guestLinks.forEach(function (el) { el.style.display = ''; });

      if (typeof initAccountSync === 'function') initAccountSync();
      if (typeof initProfileMembershipCard === 'function') initProfileMembershipCard();
    }
  }

  function resolveAvatarUrl(url) {
    const defaultAvatar = (config.site_url || '') + '/wp-content/themes/short-stream/assets/images/avatar-1.svg';
    if (!url || typeof url !== 'string') return defaultAvatar;

    let clean = url.trim();

    // If it references an avatar or theme asset in wp-content, normalize to current domain/config.site_url
    if (clean.includes('/wp-content/')) {
      const idx = clean.indexOf('/wp-content/');
      clean = clean.substring(idx); // strip http://localhost:..., http://192.168..., etc.
      // Normalize legacy theme folder name
      clean = clean.replace('/themes/short-stream/', '/themes/short-stream/');
      return (config.site_url || '') + clean;
    }

    // Relative asset path like assets/images/avatar-1.svg
    if (clean.includes('assets/images/avatar-')) {
      const idx = clean.indexOf('assets/images/avatar-');
      return (config.site_url || '') + '/wp-content/themes/short-stream/' + clean.substring(idx);
    }

    return clean;
  }

  // Migrate any stale avatar URLs in localStorage on page load
  try {
    const storedAvatar = localStorage.getItem('short_active_avatar');
    if (storedAvatar) {
      localStorage.setItem('short_active_avatar', resolveAvatarUrl(storedAvatar));
    }
    const storedProfiles = localStorage.getItem('short_custom_profiles');
    if (storedProfiles) {
      let parsed = JSON.parse(storedProfiles);
      if (Array.isArray(parsed)) {
        parsed = parsed.map(function (p) {
          return Object.assign({}, p, { avatar: resolveAvatarUrl(p.avatar) });
        });
        localStorage.setItem('short_custom_profiles', JSON.stringify(parsed));
      }
    }
  } catch (e) {}

  let currentProfilesList = [];

  function getMaxProfilesForCurrentPlan() {
    const sub = getSubscription();
    if (!sub || sub.status !== 'active') {
      return 1; // Free Guest Pass: strictly 1 profile
    }
    const planId = (sub.plan_id || '').toLowerCase();
    if (planId === 'basic') {
      return 2; // Basic: 1 profile + Kid (max 2)
    } else if (planId === 'standard') {
      return 3; // Standard: 2 profiles + Kid (max 3)
    } else if (planId === 'premium') {
      return 6; // Premium 4K: 5 profiles + Kid (max 6)
    }
    return 1;
  }

  function sanitizeProfilesList(profiles, maxAllowed) {
    if (!Array.isArray(profiles)) return [];
    const base = config.site_url || '';
    const otherAvatars = [
      base + '/wp-content/themes/short-stream/assets/images/avatar-2.svg',
      base + '/wp-content/themes/short-stream/assets/images/avatar-3.svg',
      base + '/wp-content/themes/short-stream/assets/images/avatar-5.svg',
      base + '/wp-content/themes/short-stream/assets/images/avatar-6.svg',
      base + '/wp-content/themes/short-stream/assets/images/avatar-7.svg',
      base + '/wp-content/themes/short-stream/assets/images/avatar-8.svg',
    ];

    let foundSystemKids = false;
    let fallbackIdx = 0;

    const cleaned = profiles.map(function (p) {
      const copy = Object.assign({}, p);
      const isStriped = (copy.avatar || '').includes('avatar-4.svg');

      if (copy.id === 'profile_kids') {
        foundSystemKids = true;
        copy.isKid = true;
        copy.avatar = base + '/wp-content/themes/short-stream/assets/images/avatar-4.svg';
      } else {
        // Any other profile must NOT use the system striped kids icon
        if (isStriped) {
          copy.avatar = otherAvatars[fallbackIdx % otherAvatars.length];
          fallbackIdx++;
        }
      }
      return copy;
    });

    if (maxAllowed && cleaned.length > maxAllowed) {
      return cleaned.slice(0, maxAllowed);
    }
    return cleaned;
  }

  function getDefaultProfiles() {
    const base = config.site_url || '';
    const maxAllowed = getMaxProfilesForCurrentPlan();

    try {
      const local = localStorage.getItem('short_custom_profiles');
      if (local) {
        let parsed = JSON.parse(local);
        if (Array.isArray(parsed) && parsed.length > 0) {
          const sanitized = sanitizeProfilesList(parsed, maxAllowed);
          localStorage.setItem('short_custom_profiles', JSON.stringify(sanitized));
          return sanitized;
        }
      }
    } catch (e) {}

    // Default clean profile for fresh users / guest pass
    const fresh = [
      { id: 'profile_1', name: 'Profile 1', avatar: base + '/wp-content/themes/short-stream/assets/images/avatar-1.svg', isKid: false }
    ];
    if (maxAllowed >= 3) {
      fresh.push({ id: 'profile_2', name: 'Profile 2', avatar: base + '/wp-content/themes/short-stream/assets/images/avatar-2.svg', isKid: false });
    }
    if (maxAllowed >= 2) {
      fresh.push({ id: 'profile_kids', name: 'Kids', avatar: base + '/wp-content/themes/short-stream/assets/images/avatar-4.svg', isKid: true });
    }
    return fresh.slice(0, maxAllowed);
  }

  function initProfileSync(uid) {
    if (!db) return;
    const profilesRef = db.collection('users').doc(uid).collection('profiles');

    profilesRef.onSnapshot(function (snapshot) {
      if (snapshot.empty) {
        const initialProfiles = getDefaultProfiles();
        const batch = db.batch();
        initialProfiles.forEach(function (p) {
          batch.set(profilesRef.doc(p.id), Object.assign({}, p, {
            createdAt: firebase.firestore.FieldValue.serverTimestamp()
          }));
        });
        batch.commit().then(function() {
          currentProfilesList = initialProfiles;
          setActiveProfile('profile_1', initialProfiles[0].avatar);
        }).catch(console.warn);
      } else {
        const profiles = [];
        snapshot.forEach(function (doc) { 
          const data = doc.data();
          profiles.push(Object.assign({ id: doc.id }, data, { avatar: resolveAvatarUrl(data.avatar) })); 
        });
        currentProfilesList = profiles;
        renderProfileSwitcher(profiles);
      }
    });

    // Also sync user subscription from Firestore
    db.collection('users').doc(uid).get().then(function (doc) {
      if (doc.exists && doc.data().subscription) {
        localStorage.setItem('short_subscription', JSON.stringify(doc.data().subscription));
      }
    }).catch(console.warn);
  }

  function updateHeaderKidsButtonUI(isKid) {
    const kidsBtn = document.getElementById('header-kids-btn');
    const kidsText = document.getElementById('header-kids-btn-text');
    if (!kidsBtn) return;

    if (isKid) {
      kidsBtn.classList.add('is-kids-active');
      kidsBtn.style.display = 'none';
      if (kidsText) kidsText.textContent = '';
      kidsBtn.setAttribute('title', '');
    } else {
      kidsBtn.classList.remove('is-kids-active');
      kidsBtn.style.display = '';
      if (kidsText) kidsText.textContent = 'Kids';
      kidsBtn.setAttribute('title', 'Switch to Kids Profile');
    }
  }

  function setActiveProfile(profileId, profileAvatar) {
    activeProfileId = profileId;
    localStorage.setItem('short_active_profile_id', profileId);
    const resolvedAvatar = resolveAvatarUrl(profileAvatar);
    if (resolvedAvatar) {
      localStorage.setItem('short_active_avatar', resolvedAvatar);
    }
    const headerAvatar = document.getElementById('header-current-avatar');
    if (headerAvatar && resolvedAvatar) {
      headerAvatar.src = resolvedAvatar;
    }
    const mobNavAvatar = document.getElementById('mobile-nav-account-avatar');
    if (mobNavAvatar && resolvedAvatar) {
      mobNavAvatar.src = resolvedAvatar;
    }

    const proList = currentProfilesList.length ? currentProfilesList : getDefaultProfiles();
    const target = proList.find(function(p) { return p.id === profileId; });
    const isKid = target ? !!target.isKid : false;
    document.cookie = 'short_is_kid=' + (isKid ? '1' : '0') + '; path=/; max-age=31536000';
    updateHeaderKidsButtonUI(isKid);

    if (target) {
      localStorage.setItem('short_active_profile_name', target.name || '');
      localStorage.setItem('short_active_profile', JSON.stringify(target));
    }
    if (typeof initAccountSync === 'function') {
      initAccountSync();
    }

    // Refresh My List cache & UI for the new active profile
    if (typeof getLocalMyList === 'function') {
      myListCache = getLocalMyList();
      myLocalList.clear();
      Object.keys(myListCache).forEach(function (id) { myLocalList.add(String(id)); });
      updateMyListButtonsUI();
      renderMyListPage();
    }

    // Refresh Continue Watching & History for the new active profile
    if (typeof renderContinueWatching === 'function') {
      renderContinueWatching();
    }
    if (typeof renderHistoryPage === 'function') {
      renderHistoryPage();
    }

    if (currentUser) {
      initMyListSync(currentUser.uid);
      initContinueWatchingSync(currentUser.uid);
    }
  }

  function renderProfileSwitcher(profiles) {
    const proList = profiles && profiles.length ? profiles : getDefaultProfiles();
    currentProfilesList = proList;

    // Update the main header trigger avatar with the currently selected profile
    const activeProfile = proList.find(function(p) { return p.id === activeProfileId; }) || proList[0];
    const headerAvatar = document.getElementById('header-current-avatar');
    if (activeProfile) {
      const resolved = resolveAvatarUrl(activeProfile.avatar);
      if (headerAvatar && resolved) {
        headerAvatar.src = resolved;
      }
      const mobNavAvatar = document.getElementById('mobile-nav-account-avatar');
      if (mobNavAvatar && resolved) {
        mobNavAvatar.src = resolved;
      }
      if (resolved) {
        localStorage.setItem('short_active_avatar', resolved);
      }
    }

    renderWhosWatchingGrid(proList);
  }

  function isKidProfileActive() {
    if (document.cookie.indexOf('short_is_kid=1') !== -1) return true;
    const proList = currentProfilesList && currentProfilesList.length ? currentProfilesList : getDefaultProfiles();
    const active = proList.find(function(p) { return p.id === activeProfileId; });
    return active ? !!active.isKid : false;
  }

  function isItemKidsFriendly(item) {
    if (!item) return false;
    if (item.adult) return false;

    const title = (item.title || item.name || '').toLowerCase();
    const prohibitedWords = [
      'the nun', 'saw x', 'saw vi', 'saw vii', 'saw 3d', 'terrifier', 'conjuring',
      'annabelle', 'insidious', 'sinister', 'chucky', 'child\'s play', 'evil dead',
      'hellraiser', 'human centipede', 'hostel', 'final destination', 'texas chainsaw', 'exorcist'
    ];
    for (let i = 0; i < prohibitedWords.length; i++) {
      if (title.indexOf(prohibitedWords[i]) !== -1 && title.indexOf('flying nun') === -1) {
        return false;
      }
    }

    const genreIds = [];
    if (item.genre_ids && Array.isArray(item.genre_ids)) {
      item.genre_ids.forEach(function(g) { genreIds.push(Number(g)); });
    }
    if (item.genres && Array.isArray(item.genres)) {
      item.genres.forEach(function(g) { if (g.id) genreIds.push(Number(g.id)); });
    }

    // Prohibited: 27 (Horror), 80 (Crime), 10752 (War Movie), 10768 (War & Politics TV)
    if (genreIds.includes(27) || genreIds.includes(80) || genreIds.includes(10752) || genreIds.includes(10768)) {
      return false;
    }

    // Allowed Kids: 16 (Animation), 10751 (Family), 10762 (Kids TV)
    if (genreIds.includes(16) || genreIds.includes(10751) || genreIds.includes(10762)) {
      return true;
    }

    // General family-safe: 35 (Comedy), 12 (Adventure), 14 (Fantasy), 10402 (Music) without 53 (Thriller) / 9648 (Mystery)
    const hasSafe = genreIds.includes(35) || genreIds.includes(12) || genreIds.includes(14) || genreIds.includes(10402);
    if (hasSafe && !genreIds.includes(53) && !genreIds.includes(9648)) {
      return true;
    }

    return false;
  }

  const ALL_AVATARS = [
    '/wp-content/themes/short-stream/assets/images/avatar-1.svg',
    '/wp-content/themes/short-stream/assets/images/avatar-2.svg',
    '/wp-content/themes/short-stream/assets/images/avatar-3.svg',
    '/wp-content/themes/short-stream/assets/images/avatar-4.svg',
    '/wp-content/themes/short-stream/assets/images/avatar-5.svg',
    '/wp-content/themes/short-stream/assets/images/avatar-6.svg',
    '/wp-content/themes/short-stream/assets/images/avatar-7.svg',
    '/wp-content/themes/short-stream/assets/images/avatar-8.svg',
  ];

  function renderWhosWatchingGrid(profiles) {
    const grid = document.getElementById('whos-watching-grid');
    if (!grid) return;

    grid.innerHTML = '';
    const maxAllowed = getMaxProfilesForCurrentPlan();
    let proList = profiles && profiles.length ? profiles : getDefaultProfiles();

    // Enforce plan limit
    if (proList.length > maxAllowed) {
      proList = proList.slice(0, maxAllowed);
      currentProfilesList = proList;
      localStorage.setItem('short_custom_profiles', JSON.stringify(proList));
    }
    currentProfilesList = proList;

    proList.forEach(function (p) {
      const card = document.createElement('div');
      card.className = 'profile-card-item' + (p.isKid ? ' is-kid-profile' : '');
      const hasPin = p.pin && p.pin.trim().length > 0;
      const isStripedKidsAvatar = (p.avatar || '').includes('avatar-4.svg');
      const showCornerKidsBadge = p.isKid && !isStripedKidsAvatar;

      const resolvedImg = resolveAvatarUrl(p.avatar);
      const fallbackImg = (config.site_url || '') + '/wp-content/themes/short-stream/assets/images/avatar-1.svg';

      card.innerHTML = `
        <div class="profile-avatar-wrap">
          <img src="${resolvedImg}" alt="${p.name}" onerror="this.onerror=null;this.src='${fallbackImg}';">
          ${showCornerKidsBadge ? '<span class="profile-kid-corner-badge">kids</span>' : ''}
          <div class="profile-edit-overlay">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
          </div>
        </div>
        <div class="profile-name">${p.name}</div>
        ${hasPin ? '<div class="profile-lock-badge" title="Profile Locked"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#808080" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></div>' : ''}
      `;

      card.addEventListener('click', function (e) {
        e.preventDefault();
        const isManageMode = grid.classList.contains('manage-mode');
        if (isManageMode) {
          openEditProfileModal(p, proList);
        } else {
          // If profile has PIN lock, request PIN before entering
          if (hasPin) {
            promptUnlockPin(p);
          } else {
            selectAndEnterProfile(p);
          }
        }
      });

      grid.appendChild(card);
    });

    // Add Profile Button Card (Max 6 across all plans: 5 user + 1 kid)
    if (proList.length < 6) {
      const isLocked = proList.length >= maxAllowed;
      const addCard = document.createElement('button');
      addCard.className = 'btn-add-profile-card' + (isLocked ? ' is-locked' : '');
      addCard.setAttribute('type', 'button');
      
      if (isLocked) {
        addCard.innerHTML = `
          <div class="add-profile-icon-wrap" title="Upgrade Plan to Add Profile">
            <svg class="icon-lock-premium" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="11" width="16" height="11" rx="3" ry="3"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              <circle cx="12" cy="16" r="1.2" fill="currentColor"></circle>
              <path d="M12 17.2v1.8"></path>
            </svg>
          </div>
          <div class="profile-name">Add Profile</div>
          <span class="profile-plan-lock-badge">UPGRADE</span>
        `;
        addCard.addEventListener('click', function () {
          openProfileLimitModal();
        });
      } else {
        addCard.innerHTML = `
          <div class="add-profile-icon-wrap">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
          </div>
          <div class="profile-name">Add Profile</div>
        `;
        addCard.addEventListener('click', function () {
          openAddProfileModal(proList);
        });
      }
      grid.appendChild(addCard);
    }
  }

  function showProfileTransitionLoader(avatarUrl, callback) {
    let loader = document.getElementById('netflix-profile-loader');
    if (!loader) {
      loader = document.createElement('div');
      loader.id = 'netflix-profile-loader';
      loader.className = 'netflix-profile-loader-overlay';
      loader.innerHTML = `
        <div class="netflix-spinner-wrapper">
          <img class="netflix-spinner-avatar" id="netflix-spinner-avatar-img" src="" alt="Profile">
          <div class="netflix-spinner-ring"></div>
        </div>
      `;
      document.body.appendChild(loader);
    }
    const avatarImg = document.getElementById('netflix-spinner-avatar-img');
    if (avatarImg) {
      avatarImg.src = resolveAvatarUrl(avatarUrl);
    }

    loader.classList.add('show');
    setTimeout(function() {
      if (typeof callback === 'function') callback();
    }, 650);
  }

  function selectAndEnterProfile(p) {
    setActiveProfile(p.id, p.avatar);
    showProfileTransitionLoader(p.avatar, function() {
      window.location.href = config.site_url || window.location.origin || '/';
    });
  }

  function promptUnlockPin(profile) {
    const modal = document.getElementById('modal-unlock-pin');
    const boxes = modal ? modal.querySelectorAll('.pin-digit-box') : null;
    const errorEl = document.getElementById('unlock-pin-error');
    const closeBtn = document.getElementById('btn-close-pin-modal');

    if (!modal || !boxes || !boxes.length) return;

    if (errorEl) {
      errorEl.style.display = 'none';
      errorEl.textContent = 'Your PIN must be 4 numbers.';
    }

    boxes.forEach(function(b) {
      b.value = '';
      b.classList.remove('error');
    });

    modal.style.display = 'flex';
    setTimeout(function() { boxes[0].focus(); }, 60);

    function checkPinEntered() {
      let pin = '';
      boxes.forEach(function(b) { pin += b.value; });
      if (pin.length === 4) {
        if (pin === profile.pin) {
          modal.style.display = 'none';
          selectAndEnterProfile(profile);
        } else {
          if (errorEl) {
            errorEl.textContent = 'Incorrect PIN. Please try again.';
            errorEl.style.display = 'block';
          }
          boxes.forEach(function(b) {
            b.classList.add('error');
            b.value = '';
          });
          setTimeout(function() { boxes[0].focus(); }, 100);
        }
      }
    }

    boxes.forEach(function(input, idx) {
      input.oninput = function() {
        // Strip non-digit
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value.length >= 1) {
          input.value = input.value.slice(-1);
          input.classList.remove('error');
          if (errorEl) errorEl.style.display = 'none';
          if (idx < 3) {
            boxes[idx + 1].focus();
          }
          checkPinEntered();
        }
      };

      input.onkeydown = function(e) {
        if (e.key === 'Backspace' && !input.value && idx > 0) {
          boxes[idx - 1].focus();
        }
      };
    });

    // Forgot PIN handler (Netflix-style instant recovery using account verification)
    const forgotPinBtn = document.getElementById('btn-forgot-pin-help');
    if (forgotPinBtn) {
      forgotPinBtn.onclick = async function (e) {
        e.preventDefault();
        const userEmail = (currentUser && currentUser.email) ? currentUser.email : (localStorage.getItem('short_user_email') || '');

        // If user is authenticated with Firebase via password or Google
        if (currentUser && auth) {
          // Detect provider: google.com vs password
          const isGoogleUser = (currentUser.providerData || []).some(function(p) {
            return p.providerId === 'google.com';
          });

          let authSuccess = false;

          if (isGoogleUser) {
            // User signed in with Google (no password). Prompt them to confirm with Google login
            const confirmGoogle = await shortCustomAlert({
              title: 'Verify with Google',
              message: 'Since you signed in using Google (<strong>' + (userEmail || 'Google Account') + '</strong>), please confirm your Google identity to view or reset this profile PIN.',
              buttonText: 'Verify with Google',
              type: 'info'
            });

            if (!confirmGoogle) return;

            try {
              const googleProvider = new firebase.auth.GoogleAuthProvider();
              googleProvider.setCustomParameters({ prompt: 'select_account' });
              await currentUser.reauthenticateWithPopup(googleProvider);
              authSuccess = true;
            } catch (gErr) {
              console.warn('[Short] Google re-authentication failed:', gErr);
              if (gErr && (gErr.code === 'auth/popup-closed-by-user' || gErr.code === 'auth/cancelled-popup-request')) {
                return; // User simply closed the popup
              }
              await shortCustomAlert({
                title: 'Google Verification Failed',
                message: 'Unable to verify your Google identity. Please try again.',
                type: 'error',
                buttonText: 'OK'
              });
              return;
            }

          } else {
            // Email/Password account: Ask for account password
            const enteredPassword = await shortCustomPrompt({
              title: 'Verify Account Password',
              message: 'To protect your profiles, enter your <strong>' + (userEmail || 'account') + '</strong> password to view or reset this profile PIN:',
              placeholder: 'Account Password',
              inputType: 'password',
              type: 'lock',
              confirmText: 'Verify Password',
              cancelText: 'Cancel'
            });

            if (!enteredPassword) return;

            try {
              const credential = firebase.auth.EmailAuthProvider.credential(userEmail, enteredPassword);
              await currentUser.reauthenticateWithCredential(credential);
              authSuccess = true;
            } catch (authErr) {
              console.warn('[Short] Password verification failed:', authErr);
              let errMsg = 'Incorrect account password. Please try again.';
              if (authErr && authErr.code === 'auth/wrong-password') {
                errMsg = 'The password you entered is incorrect.';
              } else if (authErr && authErr.code === 'auth/too-many-requests') {
                errMsg = 'Too many failed attempts. Please try again in a few moments.';
              }

              await shortCustomAlert({
                title: 'Verification Failed',
                message: errMsg,
                type: 'error',
                buttonText: 'Try Again'
              });
              return;
            }
          }

          if (authSuccess) {
            // Identity verified: User has proven ownership of account!
            const action = await shortCustomPrompt({
              title: 'Profile PIN: ' + (profile.pin || 'None'),
              message: 'Your current 4-digit PIN is <strong>' + (profile.pin || 'Not set') + '</strong>.<br><br>Enter a <strong>new 4-digit PIN</strong> below, or click "Keep & Enter" to unlock with the current PIN:',
              placeholder: 'New 4-digit PIN (or leave blank)',
              defaultValue: '',
              inputType: 'number',
              type: 'lock',
              confirmText: 'Update PIN',
              cancelText: 'Keep & Enter'
            });

            if (action && /^[0-9]{4}$/.test(action.trim())) {
              // User specified a new 4-digit PIN
              const newPin = action.trim();
              profile.pin = newPin;
              
              // Persist to profiles list & database
              const maxAllowed = getMaxProfilesForCurrentPlan();
              const currentList = currentProfilesList.length ? currentProfilesList : getDefaultProfiles();
              const matched = currentList.find(function(p) { return p.id === profile.id; });
              if (matched) matched.pin = newPin;
              saveUpdatedProfiles(currentList);

              await shortCustomAlert({
                title: 'PIN Updated Successfully',
                message: 'Your new PIN is <strong>' + newPin + '</strong>. You will now be signed into <strong>' + profile.name + '</strong>.',
                type: 'success',
                buttonText: 'Continue to Profile'
              });
            }

            // Unlock and enter profile immediately
            modal.style.display = 'none';
            selectAndEnterProfile(profile);
          }

        } else {
          // Fallback if not authenticated via Firebase: prompt password/email
          await shortCustomAlert({
            title: 'Account Authentication Required',
            message: 'Please log into your account to manage or recover profile PINs.',
            type: 'error',
            buttonText: 'OK'
          });
        }
      };
    }

    if (closeBtn) {
      closeBtn.onclick = function() {
        modal.style.display = 'none';
      };
    }
  }

  let selectedModalAvatar = '';

  function updateModalKidBadgePreview() {
    const kidsInput = document.getElementById('new-profile-kids');
    const badge = document.getElementById('preview-kid-badge');
    if (!badge || !kidsInput) return;
    const isKid = kidsInput.checked;
    const isStriped = (selectedModalAvatar || '').includes('avatar-4.svg');
    badge.style.display = (isKid && !isStriped) ? 'block' : 'none';
  }

  function buildAvatarPickerGrid(currentAvatar, isDefaultKidsProfile) {
    const pickerGrid = document.getElementById('avatar-picker-grid');
    if (!pickerGrid) return;
    pickerGrid.innerHTML = '';
    const base = config.site_url || '';

    // The special "kids" striped icon (avatar-4.svg) is the exclusive default icon
    // for the built-in system Kids profile and should not be picked for other profiles.
    const availableAvatars = ALL_AVATARS.filter(function(relPath) {
      if (relPath.includes('avatar-4.svg') && !isDefaultKidsProfile) {
        return false;
      }
      return true;
    });

    availableAvatars.forEach(function(relPath) {
      const fullUrl = base + relPath;
      const item = document.createElement('div');
      item.className = 'avatar-choice-item' + (fullUrl === currentAvatar || relPath === currentAvatar ? ' selected' : '');
      item.innerHTML = `<img src="${fullUrl}" alt="Avatar">`;
      item.addEventListener('click', function() {
        pickerGrid.querySelectorAll('.avatar-choice-item').forEach(function(el) { el.classList.remove('selected'); });
        item.classList.add('selected');
        selectedModalAvatar = fullUrl;
        const previewImg = document.getElementById('new-profile-avatar-preview');
        if (previewImg) previewImg.src = fullUrl;
        updateModalKidBadgePreview();
      });
      pickerGrid.appendChild(item);
    });
  }

  function openProfileLimitModal() {
    const modal = document.getElementById('modal-profile-limit-upgrade');
    const titleEl = document.getElementById('profile-limit-title');
    const descEl = document.getElementById('profile-limit-desc');
    const closeBtn = document.getElementById('btn-close-limit-modal');
    const cancelBtn = document.getElementById('btn-cancel-limit-modal');

    const sub = getSubscription();
    const planName = (sub && sub.status === 'active') ? (sub.plan_name || 'Current Plan') : 'Free Guest Pass';
    const maxAllowed = getMaxProfilesForCurrentPlan();

    if (titleEl) titleEl.textContent = 'Profile Limit Reached (' + planName + ')';
    if (descEl) {
      if (!sub || sub.status !== 'active') {
        descEl.textContent = 'Free Guest Pass is limited to 1 profile. Upgrade your subscription to create additional personalized profiles for your family.';
      } else {
        descEl.textContent = 'Your ' + planName + ' allows up to ' + maxAllowed + ' profile' + (maxAllowed > 1 ? 's' : '') + '. Upgrade to Premium to create up to 5 individual profiles plus a dedicated Kids profile.';
      }
    }

    if (modal) {
      modal.style.display = 'flex';
      const closeModal = function() { modal.style.display = 'none'; };
      if (closeBtn) closeBtn.onclick = closeModal;
      if (cancelBtn) cancelBtn.onclick = closeModal;
    }
  }

  function openAddProfileModal(proList) {
    const maxAllowed = getMaxProfilesForCurrentPlan();
    if (proList.length >= maxAllowed) {
      openProfileLimitModal();
      return;
    }

    const modal = document.getElementById('modal-add-profile');
    const form = document.getElementById('form-create-profile');
    const title = document.getElementById('profile-modal-title');
    const editingId = document.getElementById('editing-profile-id');
    const nameInput = document.getElementById('new-profile-name');
    const pinInput = document.getElementById('new-profile-pin');
    const kidsInput = document.getElementById('new-profile-kids');
    const deleteBtn = document.getElementById('btn-delete-profile');
    const previewImg = document.getElementById('new-profile-avatar-preview');
    const avatarSection = document.getElementById('avatar-selection-section');

    if (!modal) return;
    if (form) form.reset();
    if (title) title.textContent = 'Add Profile';
    if (editingId) editingId.value = '';
    if (deleteBtn) deleteBtn.style.display = 'none';
    if (avatarSection) avatarSection.style.display = 'block';

    const availableChoices = ALL_AVATARS.filter(function(a) { return !a.includes('avatar-4.svg'); });
    const defaultAv = (config.site_url || '') + availableChoices[(proList.length) % availableChoices.length];
    selectedModalAvatar = defaultAv;
    if (previewImg) previewImg.src = defaultAv;
    buildAvatarPickerGrid(defaultAv, false);
    updateModalKidBadgePreview();

    modal.style.display = 'flex';
  }

  function openEditProfileModal(profile, proList) {
    const modal = document.getElementById('modal-add-profile');
    const title = document.getElementById('profile-modal-title');
    const editingId = document.getElementById('editing-profile-id');
    const nameInput = document.getElementById('new-profile-name');
    const pinInput = document.getElementById('new-profile-pin');
    const kidsInput = document.getElementById('new-profile-kids');
    const deleteBtn = document.getElementById('btn-delete-profile');
    const previewImg = document.getElementById('new-profile-avatar-preview');
    const avatarSection = document.getElementById('avatar-selection-section');

    if (!modal) return;
    if (title) title.textContent = 'Edit ' + profile.name;
    if (editingId) editingId.value = profile.id;
    if (nameInput) nameInput.value = profile.name;
    if (pinInput) pinInput.value = profile.pin || '';
    if (kidsInput) kidsInput.checked = !!profile.isKid;
    if (avatarSection) avatarSection.style.display = 'block';

    const isSystemKids = profile.id === 'profile_kids';
    selectedModalAvatar = profile.avatar;
    if (previewImg) previewImg.src = resolveAvatarUrl(profile.avatar);
    buildAvatarPickerGrid(profile.avatar, isSystemKids);
    updateModalKidBadgePreview();

    if (deleteBtn) {
      deleteBtn.style.display = proList.length > 1 ? 'inline-block' : 'none';
      deleteBtn.onclick = async function() {
        const ok = await shortCustomConfirm({
          title: 'Delete Profile?',
          message: 'Are you sure you want to delete profile <strong>' + profile.name + '</strong>? This profile\'s watch history, continue watching, and personalized list will be permanently removed.',
          confirmText: 'Delete Profile',
          cancelText: 'Cancel'
        });
        if (!ok) return;

        // 1. Remove all profile-scoped localStorage items
        const targetId = profile.id;
        try {
          localStorage.removeItem('short_continue_watching_' + targetId);
          localStorage.removeItem('short_watch_history_' + targetId);
          localStorage.removeItem('short_my_list_' + targetId);
          localStorage.removeItem('short_likes_' + targetId);
          localStorage.removeItem('short_notifications_' + targetId);
        } catch (e) {}

        // 2. Remove profile and subcollections from Firestore
        if (currentUser && db) {
          try {
            const profileRef = db.collection('users').doc(currentUser.uid).collection('profiles').doc(targetId);
            
            // Delete subcollections in background
            const subcollections = ['continue_watching', 'watch_history', 'my_list', 'likes', 'notifications'];
            subcollections.forEach(function(subCol) {
              profileRef.collection(subCol).get().then(function(snap) {
                const batch = db.batch();
                snap.forEach(function(doc) {
                  batch.delete(doc.ref);
                });
                return batch.commit();
              }).catch(console.warn);
            });

            // Delete profile document
            profileRef.delete().catch(console.warn);
          } catch (e) {
            console.warn('[Profile Delete Error]', e);
          }
        }

        const updated = proList.filter(function(p) { return p.id !== profile.id; });
        currentProfilesList = updated;
        saveUpdatedProfiles(updated);
        
        // If current active profile was deleted, switch to the first remaining profile
        if (activeProfileId === targetId && updated.length > 0) {
          setActiveProfile(updated[0].id, updated[0].avatar);
        }

        renderWhosWatchingGrid(updated);
        modal.style.display = 'none';
      };
    }

    modal.style.display = 'flex';
  }

  function saveUpdatedProfiles(profiles) {
    currentProfilesList = profiles;
    localStorage.setItem('short_custom_profiles', JSON.stringify(profiles));
    if (currentUser && db) {
      const batch = db.batch();
      profiles.forEach(function (p) {
        const docRef = db.collection('users').doc(currentUser.uid).collection('profiles').doc(p.id);
        batch.set(docRef, p, { merge: true });
      });
      batch.commit().catch(console.warn);
    }
  }

  function initWhosWatchingEvents() {
    renderWhosWatchingGrid(getDefaultProfiles());

    // Toggle Manage Mode
    const manageBtn = document.getElementById('btn-toggle-manage-mode');
    const resetBtn = document.getElementById('btn-reset-profiles-demo');
    const grid = document.getElementById('whos-watching-grid');
    if (manageBtn && grid) {
      manageBtn.addEventListener('click', function () {
        grid.classList.toggle('manage-mode');
        const isActive = grid.classList.contains('manage-mode');
        manageBtn.classList.toggle('active', isActive);
        manageBtn.textContent = isActive ? 'Done' : 'Manage Profiles';
        if (resetBtn) {
          resetBtn.style.display = isActive ? 'inline-flex' : 'none';
        }
      });

      // Auto-activate Manage Mode if navigated from "Manage Profiles" (?manage=1)
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('manage') === '1') {
        grid.classList.add('manage-mode');
        manageBtn.classList.add('active');
        manageBtn.textContent = 'Done';
        if (resetBtn) resetBtn.style.display = 'inline-flex';
      }
    }

    if (resetBtn) {
      resetBtn.addEventListener('click', function() {
        if (confirm('Reset profiles back to default? This will clear out all extra demo profiles.')) {
          localStorage.removeItem('short_custom_profiles');
          const clean = getDefaultProfiles();
          currentProfilesList = clean;
          saveUpdatedProfiles(clean);
          setActiveProfile(clean[0].id, clean[0].avatar);
          renderWhosWatchingGrid(clean);
        }
      });
    }

    // Modal Add/Edit Profile
    const modal = document.getElementById('modal-add-profile');
    const closeBtn = document.getElementById('btn-close-profile-modal');
    const cancelBtn = document.getElementById('btn-cancel-add-profile');
    const form = document.getElementById('form-create-profile');
    const triggerAvatar = document.getElementById('trigger-avatar-picker');
    const avatarSection = document.getElementById('avatar-selection-section');
    const kidsInput = document.getElementById('new-profile-kids');

    if (kidsInput) {
      kidsInput.addEventListener('change', updateModalKidBadgePreview);
    }

    if (triggerAvatar && avatarSection) {
      triggerAvatar.addEventListener('click', function() {
        avatarSection.style.display = avatarSection.style.display === 'none' ? 'block' : 'none';
      });
    }

    function closeModal() {
      if (modal) modal.style.display = 'none';
      if (form) form.reset();
      updateModalKidBadgePreview();
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        const editingId = document.getElementById('editing-profile-id').value;
        const nameInput = document.getElementById('new-profile-name');
        const pinInput = document.getElementById('new-profile-pin');
        const isKidInput = document.getElementById('new-profile-kids');
        const name = nameInput ? nameInput.value.trim() : 'User';
        const pin = pinInput ? pinInput.value.trim() : '';
        const isKid = isKidInput ? isKidInput.checked : false;

        const maxAllowed = getMaxProfilesForCurrentPlan();
        const current = currentProfilesList.length ? currentProfilesList : getDefaultProfiles();

        if (editingId) {
          // Update existing profile
          const target = current.find(function(p) { return p.id === editingId; });
          if (target) {
            target.name = name;
            target.pin = pin;
            target.isKid = isKid;
            if (selectedModalAvatar) {
              target.avatar = selectedModalAvatar;
            }
          }
        } else {
          // Check limit before adding
          if (current.length >= maxAllowed) {
            closeModal();
            openProfileLimitModal();
            return;
          }

          // Add new profile (avatar-4.svg is strictly reserved for the built-in system Kids profile)
          let finalAvatar = selectedModalAvatar;
          if (!finalAvatar || finalAvatar.includes('avatar-4.svg')) {
            finalAvatar = (config.site_url || '') + '/wp-content/themes/short-stream/assets/images/avatar-2.svg';
          }

          const newP = {
            id: 'profile_' + Date.now(),
            name: name,
            pin: pin,
            avatar: finalAvatar,
            isKid: isKid,
            createdAt: new Date().toISOString()
          };
          current.push(newP);
        }

        const sanitizedList = sanitizeProfilesList(current, maxAllowed);
        saveUpdatedProfiles(sanitizedList);
        renderWhosWatchingGrid(sanitizedList);
        closeModal();
      });
    }
  }

  let myListCache = {};

  function getLocalMyList() {
    try {
      const stored = localStorage.getItem('short_my_list_' + activeProfileId);
      return stored ? JSON.parse(stored) : {};
    } catch (e) {
      return {};
    }
  }

  function saveLocalMyList(listObj) {
    try {
      localStorage.setItem('short_my_list_' + activeProfileId, JSON.stringify(listObj));
    } catch (e) {}
  }

  // Load initial list from localStorage
  myListCache = getLocalMyList();
  Object.keys(myListCache).forEach(function (id) {
    const item = myListCache[id];
    const title = (item && (item.title || item.name) ? item.title || item.name : '').trim().toLowerCase();
    if (!title || title === 'untitled' || title === 'untitled title' || title.startsWith('untitled')) {
      delete myListCache[id];
    } else {
      myLocalList.add(String(id));
    }
  });
  saveLocalMyList(myListCache);

  let myListUnsubscribe = null;
  function initMyListSync(uid) {
    if (typeof myListUnsubscribe === 'function') {
      myListUnsubscribe();
      myListUnsubscribe = null;
    }

    // Always render whatever is in localStorage immediately
    myListCache = getLocalMyList();
    myLocalList.clear();
    Object.keys(myListCache).forEach(function (id) {
      const item = myListCache[id];
      const title = (item && (item.title || item.name) ? item.title || item.name : '').trim().toLowerCase();
      if (!title || title === 'untitled' || title === 'untitled title' || title.startsWith('untitled')) {
        delete myListCache[id];
      } else {
        myLocalList.add(String(id));
      }
    });
    saveLocalMyList(myListCache);
    updateMyListButtonsUI();
    renderMyListPage();

    if (!uid) return;

    const rtdbUrl = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';

    // 1. RTDB SDK Realtime Listener
    if (typeof firebase !== 'undefined' && firebase.database) {
      try {
        firebase.database().ref('users/' + uid + '/watchlist').on('value', function(snapshot) {
          const data = snapshot.val() || {};
          myLocalList.clear();
          myListCache = {};
          Object.keys(data).forEach(function(id) {
            const item = data[id];
            if (item && (item.title || item.id)) {
              myLocalList.add(String(id));
              myListCache[id] = Object.assign({ id: id }, item);
            }
          });
          saveLocalMyList(myListCache);
          updateMyListButtonsUI();
          renderMyListPage();
        }, function(err) {
          console.warn('[My List RTDB Listener Error]', err);
        });
      } catch(e) {}
    }

    // 2. Direct REST fetch to populate immediately
    fetch(rtdbUrl + '/users/' + uid + '/watchlist.json')
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data && typeof data === 'object') {
          myLocalList.clear();
          myListCache = {};
          Object.keys(data).forEach(function(id) {
            const item = data[id];
            if (item && (item.title || item.id)) {
              myLocalList.add(String(id));
              myListCache[id] = Object.assign({ id: id }, item);
            }
          });
          saveLocalMyList(myListCache);
          updateMyListButtonsUI();
          renderMyListPage();
        }
      })
      .catch(function(err) {
        console.warn('[My List REST Error]', err);
      });
  }

  function updateMyListButtonsUI() {
    document.querySelectorAll('.btn-add-list, .btn-action-list, #hp-btn-list').forEach(function (btn) {
      const tmdbId = String(btn.getAttribute('data-id') || '');
      if (!tmdbId) return;
      if (myLocalList.has(tmdbId)) {
        btn.classList.add('active', 'in-list');
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        btn.setAttribute('title', 'Remove from My List');
      } else {
        btn.classList.remove('active', 'in-list');
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
        btn.setAttribute('title', 'Add to My List');
      }
    });
  }

  async function toggleMyList(itemData) {
    if (!itemData || !itemData.id) return;
    const id = String(itemData.id);
    const isPresent = myLocalList.has(id);
    const rtdbUrl = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
    const authUser = (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser) || currentUser;
    const uid = (authUser && authUser.uid) || localStorage.getItem('short_user_uid');

    if (isPresent) {
      myLocalList.delete(id);
      delete myListCache[id];
      saveLocalMyList(myListCache);

      if (uid) {
        try {
          if (typeof firebase !== 'undefined' && firebase.database) {
            firebase.database().ref('users/' + uid + '/watchlist/' + id).remove().catch(function(){});
            firebase.database().ref('users/' + uid + '/my_list/' + id).remove().catch(function(){});
          }
        } catch(e) {}
        fetch(rtdbUrl + '/users/' + uid + '/watchlist/' + id + '.json', {
          method: 'DELETE'
        }).catch(function(err){
          console.warn('[My List Delete error]', err);
        });
        fetch(rtdbUrl + '/users/' + uid + '/my_list/' + id + '.json', {
          method: 'DELETE'
        }).catch(function(){});
      }
    } else {
      let rawTitle = (itemData.title || itemData.name || '').trim();
      if (!rawTitle || rawTitle.toLowerCase() === 'untitled' || rawTitle.toLowerCase() === 'untitled title') {
        const heroTitle = document.querySelector('.hero-title');
        if (heroTitle && heroTitle.textContent.trim()) {
          rawTitle = heroTitle.textContent.trim();
        } else {
          rawTitle = 'Title #' + id;
        }
      }

      const newItem = {
        id: id,
        title: rawTitle,
        poster: itemData.poster || itemData.poster_path || '',
        overview: itemData.overview || itemData.synopsis || '',
        savedAt: Date.now()
      };

      myLocalList.add(id);
      myListCache[id] = newItem;
      saveLocalMyList(myListCache);

      if (uid) {
        try {
          if (typeof firebase !== 'undefined' && firebase.database) {
            firebase.database().ref('users/' + uid + '/watchlist/' + id).set(newItem).catch(function(){});
            firebase.database().ref('users/' + uid + '/my_list/' + id).set(newItem).catch(function(){});
          }
        } catch(e) {}
        fetch(rtdbUrl + '/users/' + uid + '/watchlist/' + id + '.json', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(newItem)
        }).catch(function(err){
          console.warn('[My List Save error]', err);
        });
        fetch(rtdbUrl + '/users/' + uid + '/my_list/' + id + '.json', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(newItem)
        }).catch(function(){});
      }
    }
    updateMyListButtonsUI();
    renderMyListPage();
  }

  // =========================================================================
  // USER LIKES MANAGEMENT & DATABASE SYNCHRONIZATION
  // =========================================================================
  let myLocalLikes = new Set();
  let myLikesCache = {};

  function getGuestUuid() {
    let uuid = localStorage.getItem('short_guest_uuid');
    if (!uuid) {
      uuid = 'guest_' + Math.random().toString(36).substring(2, 12) + '_' + Date.now().toString(36);
      try {
        localStorage.setItem('short_guest_uuid', uuid);
      } catch (e) {}
    }
    return uuid;
  }

  function getLikesStorageKey() {
    return 'short_user_likes_' + (activeProfileId || 'profile_1');
  }

  function getLocalLikes() {
    try {
      const stored = localStorage.getItem(getLikesStorageKey());
      return stored ? JSON.parse(stored) : {};
    } catch (e) {
      return {};
    }
  }

  function saveLocalLikes(likesObj) {
    try {
      localStorage.setItem(getLikesStorageKey(), JSON.stringify(likesObj || {}));
    } catch (e) {}
  }

  function showAppToast(message, isLiked) {
    let toast = document.getElementById('short-global-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'short-global-toast';
      toast.className = 'short-global-toast';
      document.body.appendChild(toast);
    }
    const icon = isLiked !== false 
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color:var(--accent, #00DF82);"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#94a3b8;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
    toast.innerHTML = '<div class="toast-content">' + icon + '<span>' + message + '</span></div>';
    toast.classList.add('show');
    clearTimeout(window._shortAppToastTimeout);
    window._shortAppToastTimeout = setTimeout(function () {
      toast.classList.remove('show');
    }, 2800);
  }

  // Load initial likes from localStorage
  myLikesCache = getLocalLikes();
  Object.keys(myLikesCache).forEach(function (id) {
    myLocalLikes.add(String(id));
  });

  function updateLikeButtonsUI() {
    document.querySelectorAll('.btn-like, .btn-action-round.btn-like, #hp-btn-like, #modal-btn-like').forEach(function (btn) {
      const tmdbId = String(btn.getAttribute('data-id') || '');
      if (!tmdbId) return;
      const isLiked = myLocalLikes.has(tmdbId);
      if (isLiked) {
        btn.classList.add('liked', 'active', 'active-liked');
        btn.setAttribute('title', 'Liked');
        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
      } else {
        btn.classList.remove('liked', 'active', 'active-liked');
        btn.setAttribute('title', 'I like this');
        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
      }
    });
  }

  function syncLikesFromBackend() {
    const guestUuid = getGuestUuid();
    const siteBase = (config.site_url ? new URL(config.site_url, window.location.origin).pathname.replace(/\/$/, '') : '');
    const restUrl = config.rest_url || (window.location.origin + siteBase + '/wp-json/short/v1');
    const apiUrl = restUrl + '/likes?guest_uuid=' + encodeURIComponent(guestUuid);

    fetch(apiUrl, {
      headers: { 'X-WP-Nonce': config.nonce || '' }
    })
    .then(function (res) { return res.json(); })
    .then(function (res) {
      if (res && res.success && Array.isArray(res.data)) {
        res.data.forEach(function (item) {
          if (item && item.id) {
            const idStr = String(item.id);
            myLocalLikes.add(idStr);
            myLikesCache[idStr] = item;
          }
        });
        saveLocalLikes(myLikesCache);
        updateLikeButtonsUI();
      }
    })
    .catch(function (err) {
      console.warn('[Short Likes] Backend sync error:', err);
    });
  }

  let likesUnsubscribe = null;
  function initLikesSync(uid) {
    if (typeof likesUnsubscribe === 'function') {
      likesUnsubscribe();
      likesUnsubscribe = null;
    }

    // Refresh from local cache
    myLikesCache = getLocalLikes();
    myLocalLikes.clear();
    Object.keys(myLikesCache).forEach(function (id) {
      myLocalLikes.add(String(id));
    });
    updateLikeButtonsUI();

    // Sync from WP Database
    syncLikesFromBackend();

    // Sync with Firestore if active
    if (!db || !uid) return;

    const profileId = activeProfileId || 'profile_1';
    const likesRef = db.collection('users').doc(uid).collection('profiles').doc(profileId).collection('likes');

    // Migrate local items to Firestore
    Object.values(myLikesCache || {}).forEach(function (item) {
      if (!item || !item.id) return;
      likesRef.doc(String(item.id)).get().then(function (doc) {
        if (!doc.exists) {
          likesRef.doc(String(item.id)).set(Object.assign({}, item, {
            liked_at: firebase.firestore.FieldValue.serverTimestamp()
          })).catch(function () {});
        }
      }).catch(function () {});
    });

    likesUnsubscribe = likesRef.onSnapshot(function (snapshot) {
      snapshot.forEach(function (doc) {
        const data = doc.data();
        const id = String(doc.id);
        myLocalLikes.add(id);
        myLikesCache[id] = Object.assign({ id: id }, data);
      });
      saveLocalLikes(myLikesCache);
      updateLikeButtonsUI();
    }, function (err) {
      console.warn('[Short Likes] Firestore sync error:', err);
    });
  }

  async function toggleLike(itemData) {
    if (!itemData || !itemData.id) return;
    const id = String(itemData.id);
    const isPresent = myLocalLikes.has(id);
    const willBeLiked = !isPresent;

    if (isPresent) {
      myLocalLikes.delete(id);
      delete myLikesCache[id];
      saveLocalLikes(myLikesCache);
      showAppToast('Removed from Liked Titles', false);
    } else {
      const rawTitle = (itemData.title || itemData.name || '').trim();
      const newItem = {
        id: id,
        tmdb_id: parseInt(id, 10),
        media_type: itemData.media_type || itemData.type || 'movie',
        title: rawTitle || 'Untitled',
        poster_path: itemData.poster_path || '',
        backdrop_path: itemData.backdrop_path || '',
        vote_average: parseFloat(itemData.vote_average || itemData.rating || 0),
        year: itemData.year || ''
      };
      myLocalLikes.add(id);
      myLikesCache[id] = newItem;
      saveLocalLikes(myLikesCache);
      showAppToast('Added to Liked Titles', true);
    }

    updateLikeButtonsUI();

    // 1. Sync to WordPress Database via REST API
    const guestUuid = getGuestUuid();
    const siteBase = (config.site_url ? new URL(config.site_url, window.location.origin).pathname.replace(/\/$/, '') : '');
    const restUrl = config.rest_url || (window.location.origin + siteBase + '/wp-json/short/v1');

    fetch(restUrl + '/likes/toggle', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce || ''
      },
      body: JSON.stringify({
        id: id,
        tmdb_id: parseInt(id, 10),
        media_type: itemData.media_type || itemData.type || 'movie',
        title: (itemData.title || itemData.name || '').trim(),
        poster_path: itemData.poster_path || '',
        backdrop_path: itemData.backdrop_path || '',
        vote_average: parseFloat(itemData.vote_average || itemData.rating || 0),
        year: itemData.year || '',
        guest_uuid: guestUuid
      })
    }).then(function(res) {
      return res.json();
    }).then(function(data) {
      if (data && data.success && data.total_likes !== undefined) {
        // Optionally update any like counts if rendered
      }
    }).catch(function (err) {
      console.warn('[Short Likes] WP REST sync error:', err);
    });

    // 2. Sync to Firebase Firestore
    if (currentUser && db) {
      try {
        const profileId = activeProfileId || 'profile_1';
        const likeDocRef = db.collection('users').doc(currentUser.uid).collection('profiles').doc(profileId).collection('likes').doc(id);
        if (willBeLiked) {
          await likeDocRef.set({
            id: id,
            tmdb_id: parseInt(id, 10),
            media_type: itemData.media_type || itemData.type || 'movie',
            title: (itemData.title || itemData.name || '').trim(),
            poster_path: itemData.poster_path || '',
            backdrop_path: itemData.backdrop_path || '',
            vote_average: parseFloat(itemData.vote_average || itemData.rating || 0),
            year: itemData.year || '',
            liked_at: firebase.firestore.FieldValue.serverTimestamp()
          });
        } else {
          await likeDocRef.delete();
        }
      } catch (e) {
        console.warn('[Short Likes] Firebase sync error:', e);
      }
    }
  }

  window.SHORT.toggleLike = toggleLike;
  window.SHORT.updateLikeButtonsUI = updateLikeButtonsUI;

  function renderMyListPage(currentFilter) {
    const grid = document.getElementById('my-list-cards-grid');
    if (!grid) return;

    const filter = currentFilter || (grid.getAttribute('data-active-filter') || 'all');
    grid.setAttribute('data-active-filter', filter);

    // Synchronize tab buttons active state
    const tabs = document.querySelectorAll('#my-list-filter-tabs .tab-btn, .page-filter-tabs .tab-btn');
    tabs.forEach(function (tab) {
      const tabFilter = tab.getAttribute('data-filter') || 'all';
      if (tabFilter === filter) {
        tab.classList.add('active');
      } else {
        tab.classList.remove('active');
      }
    });

    // Clean up any existing notice element
    const noticeEl = document.getElementById('my-list-signin-notice');
    if (noticeEl) {
      noticeEl.remove();
    }

    const items = Object.values(myListCache || {});
    let hasCleaned = false;
    const validItems = [];

    items.forEach(function (item) {
      if (!item || !item.id) {
        hasCleaned = true;
        return;
      }
      const title = (item.title || item.name || '').trim();
      const lower = title.toLowerCase();
      // Remove untitled corruption or empty items from before
      if (!title || lower === 'untitled' || lower === 'untitled title' || lower.startsWith('untitled')) {
        hasCleaned = true;
        delete myListCache[item.id];
        myLocalList.delete(String(item.id));
        if (currentUser && db) {
          db.collection('users').doc(currentUser.uid).collection('profiles').doc(activeProfileId || 'profile_1').collection('my_list').doc(String(item.id)).delete().catch(function(){});
        }
        return;
      }
      validItems.push(item);
    });

    if (hasCleaned) {
      saveLocalMyList(myListCache);
      updateMyListButtonsUI();
    }

    const filtered = validItems;

    if (!filtered.length) {
      grid.innerHTML = `
        <div class="my-list-empty-state" style="grid-column: 1 / -1; text-align: center; padding: 50px 20px; background: rgba(255,255,255,0.02); border-radius: 16px; border: 1px dashed rgba(255,255,255,0.1);">
          <div class="empty-icon-wrap" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255,45,85,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #ff2d55;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
          </div>
          <h2 class="empty-title" style="color: #fff; font-size: 20px; font-weight: 700; margin-bottom: 8px;">No Saved Dramas Yet</h2>
          <p class="empty-desc" style="color: #888; font-size: 14px; max-width: 420px; margin: 0 auto 20px; line-height: 1.5;">Explore trending short dramas on Short TV and tap the Save button to binge-watch them here anytime.</p>
          <a href="${config.site_url || '/'}" class="btn btn-play" style="display: inline-flex; align-items: center; gap: 8px; background: #ff2d55; color: #fff; padding: 10px 24px; border-radius: 20px; text-decoration: none; font-weight: 700; font-size: 14px;">
            <span>Explore Trending Dramas</span>
          </a>
        </div>
      `;
      return;
    }

    grid.innerHTML = '';
    filtered.forEach(function (item) {
      const card = document.createElement('div');
      const posterUrl = item.poster || item.poster_path || item.cover || item.backdrop_path || 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=600&auto=format&fit=crop&q=80';
      const watchUrl = (config.site_url || '') + '/watch/' + item.id + '/';
      const rating = item.vote_average ? parseFloat(item.vote_average).toFixed(1) : (item.rating || '4.8');

      card.className = 'short-drama-card';
      card.setAttribute('data-id', item.id);
      card.setAttribute('data-title', item.title || '');

      card.innerHTML = `
        <a href="${watchUrl}" class="short-drama-poster-wrap">
          <img src="${posterUrl}" alt="${item.title || 'Drama'}" class="short-drama-poster-img" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=600&auto=format&fit=crop&q=80';">
          <div class="short-drama-play-badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            <span>Play</span>
          </div>
          <button type="button" class="short-drama-overlay-btn btn-remove-from-list" data-id="${item.id}" title="Remove from saved list">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </a>
        <div class="short-drama-info-box">
          <a href="${watchUrl}" class="short-drama-title">${item.title || 'Untitled Drama'}</a>
          <div class="short-drama-sub">
            <span style="color: #ffc107;">★ ${rating}</span>
            <span>Short TV</span>
          </div>
        </div>
      `;

      const removeBtn = card.querySelector('.btn-remove-from-list');
      if (removeBtn) {
        removeBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          toggleMyList(item);
        });
      }

      grid.appendChild(card);
    });
  }

  function initMyListPageFilters() {
    document.addEventListener('click', function (e) {
      const tab = e.target.closest('#my-list-filter-tabs .tab-btn, .page-filter-tabs .tab-btn');
      if (!tab) return;
      e.preventDefault();
      const filter = tab.getAttribute('data-filter') || 'all';
      renderMyListPage(filter);
    });
  }

  function getCWStorageKey() {
    const uid = (currentUser && currentUser.uid) || localStorage.getItem('short_user_uid') || 'guest';
    return 'short_continue_watching_' + uid + '_' + (activeProfileId || 'profile_1');
  }

  function getHistoryStorageKey() {
    const uid = (currentUser && currentUser.uid) || localStorage.getItem('short_user_uid') || 'guest';
    return 'short_watch_history_' + uid + '_' + (activeProfileId || 'profile_1');
  }

  function getLocalContinueWatching() {
    try {
      const uid = (currentUser && currentUser.uid) || localStorage.getItem('short_user_uid');
      if (uid) {
        let raw = localStorage.getItem('short_continue_watching_' + uid + '_' + (activeProfileId || 'profile_1'));
        if (!raw) raw = localStorage.getItem('shorttv_history_' + uid);
        if (raw) {
          const parsed = JSON.parse(raw);
          if (parsed && typeof parsed === 'object') return parsed;
        }
        return {};
      } else {
        let raw = localStorage.getItem('short_guest_watch_history') || localStorage.getItem('short_continue_watching_guest_profile_1');
        if (raw) {
          const parsed = JSON.parse(raw);
          if (parsed && typeof parsed === 'object') return parsed;
        }
        return {};
      }
    } catch (e) {
      return {};
    }
  }

  function saveLocalContinueWatching(data) {
    try {
      const uid = (currentUser && currentUser.uid) || localStorage.getItem('short_user_uid');
      if (uid) {
        localStorage.setItem('short_continue_watching_' + uid + '_' + (activeProfileId || 'profile_1'), JSON.stringify(data || {}));
        localStorage.setItem('shorttv_history_' + uid, JSON.stringify(data || {}));
      } else {
        localStorage.setItem('short_continue_watching_guest_profile_1', JSON.stringify(data || {}));
        localStorage.setItem('short_guest_watch_history', JSON.stringify(data || {}));
      }
    } catch (e) {}
  }

  function getLocalWatchHistory() {
    try {
      const uid = (currentUser && currentUser.uid) || localStorage.getItem('short_user_uid');
      if (uid) {
        let raw = localStorage.getItem('short_watch_history_' + uid + '_' + (activeProfileId || 'profile_1'));
        if (!raw) raw = localStorage.getItem('shorttv_history_' + uid);
        return raw ? JSON.parse(raw) : {};
      } else {
        let raw = localStorage.getItem('short_watch_history_guest_profile_1') || localStorage.getItem('short_guest_watch_history');
        return raw ? JSON.parse(raw) : {};
      }
    } catch (e) {
      return {};
    }
  }

  function saveLocalWatchHistory(data) {
    try {
      const uid = (currentUser && currentUser.uid) || localStorage.getItem('short_user_uid');
      if (uid) {
        localStorage.setItem('short_watch_history_' + uid + '_' + (activeProfileId || 'profile_1'), JSON.stringify(data || {}));
        localStorage.setItem('shorttv_history_' + uid, JSON.stringify(data || {}));
      } else {
        localStorage.setItem('short_watch_history_guest_profile_1', JSON.stringify(data || {}));
        localStorage.setItem('short_guest_watch_history', JSON.stringify(data || {}));
      }
    } catch (e) {}
  }

  function normalizeMediaUrl(url, dramaId) {
    if (config && config.drama_map && dramaId && config.drama_map[String(dramaId)] && config.drama_map[String(dramaId)].poster) {
      return config.drama_map[String(dramaId)].poster;
    }
    if (!url || typeof url !== 'string') return '';
    url = url.trim();
    if (!url) return '';

    var siteUrl = (config && config.site_url) ? config.site_url : window.location.origin;

    var wpIdx = url.indexOf('/wp-content/');
    if (wpIdx !== -1) {
      return siteUrl.replace(/\/+$/, '') + url.substring(wpIdx);
    }

    if (url.startsWith('/')) {
      return siteUrl.replace(/\/+$/, '') + '/' + url.replace(/^\/+/, '');
    }

    try {
      var parsed = new URL(url, window.location.origin);
      if ((parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1' || parsed.hostname === '0.0.0.0') && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        return siteUrl.replace(/\/+$/, '') + parsed.pathname + parsed.search;
      }
    } catch (e) {}

    return url;
  }
  window.normalizeMediaUrl = normalizeMediaUrl;

  function renderContinueWatching(itemsMap) {
    const container = document.getElementById('carousel-continue-watching') || document.querySelector('.continue-watching-track, #carousel-continue-watching');
    const row = document.getElementById('row-continue-watching') || document.querySelector('.content-row-continue, #row-continue-watching');
    if (!container || !row) return;

    const dataMap = (typeof itemsMap !== 'undefined' && itemsMap !== null) ? itemsMap : getLocalContinueWatching();
    
    // Group / deduplicate by drama ID so each drama has exactly ONE card with latest episode
    const grouped = {};
    Object.values(dataMap).forEach(function (item) {
      var dramaId = String(item.id || item.post_id || item.series_id || item.tmdb_id || '');
      if (!dramaId) return;
      const existingTime = grouped[dramaId] ? (grouped[dramaId].updated_at || grouped[dramaId].timestamp || grouped[dramaId].watchedAt || 0) : -1;
      const itemTime = item.updated_at || item.timestamp || item.watchedAt || 0;
      if (!grouped[dramaId] || itemTime >= existingTime) {
        grouped[dramaId] = item;
      }
    });

    const items = Object.values(grouped).sort(function (a, b) {
      return (b.updated_at || b.timestamp || b.watchedAt || 0) - (a.updated_at || a.timestamp || a.watchedAt || 0);
    });

    if (!items.length) {
      row.style.display = 'none';
      container.innerHTML = '';
      return;
    }

    row.style.display = 'block';
    container.innerHTML = '';

    const isPortrait = (row.getAttribute('data-layout') === 'portrait') || row.classList.contains('portrait-row');

    items.forEach(function (data) {
      const dramaId = String(data.id || data.post_id || data.series_id || data.tmdb_id || '');
      const percent = Math.min(100, Math.max(5, data.percent || Math.round(((data.currentTime || 0) / (data.duration || 1)) * 100)));
      const rawPoster = data.poster || data.poster_path || data.backdrop || data.backdrop_path || '';
      const rawBackdrop = data.backdrop || data.backdrop_path || data.poster || data.poster_path || '';
      const chosenImg = normalizeMediaUrl(isPortrait ? rawPoster : rawBackdrop);
      const epNum = parseInt(data.episode || data.lastEpisode || 1, 10);
      const totalEps = parseInt(data.totalEpisodes || data.total_episodes || 1, 10);

      let watchUrl = data.watchUrl || data.watch_url || ((config.site_url || '') + '/watch/' + dramaId + '/?episode=' + epNum);

      const card = document.createElement('div');
      card.className = 'media-card ' + (isPortrait ? 'portrait-card' : 'landscape-card') + ' continue-watching-card';
      card.setAttribute('data-id', dramaId);
      card.setAttribute('data-title', data.title || 'Short Drama');

      const epBadge = (epNum > 0) ? `<span class="card-ep-badge">EP ${epNum}` + (totalEps > 1 ? ` / ${totalEps}` : '') + `</span>` : '';
      const wrapClass = isPortrait ? 'card-portrait-wrap' : 'card-backdrop-wrap';
      const imgClass = isPortrait ? 'card-portrait-img' : 'card-backdrop-img';

      card.innerHTML = `
        <a href="${watchUrl}" class="${wrapClass}">
          <img class="${imgClass}" src="${chosenImg}" alt="${data.title || 'Short Drama'}" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=600&auto=format&fit=crop&q=80';">
          <div class="card-overlay-gradient"></div>
          <div class="card-hover-action">
            <div class="hover-play-btn">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
            </div>
          </div>
          <div class="card-progress-bar-wrap">
            <div class="card-progress-bar-fill" style="width: ${percent}%;"></div>
          </div>
          <div class="card-title-overlay">
            <span class="card-title-text">${data.title || 'Short Drama'}</span>
            ${epBadge}
          </div>
          <button type="button" class="btn-remove-continue" data-id="${dramaId}" title="Remove from Continue Watching">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </a>
      `;

      const removeBtn = card.querySelector('.btn-remove-continue');
      if (removeBtn) {
        removeBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          removeFromContinueWatching(dramaId);
        });
      }

      container.appendChild(card);
    });

    setTimeout(function () {
      const arrowLeft = row.querySelector('.arrow-left, .slider-arrow-left');
      const arrowRight = row.querySelector('.arrow-right, .slider-arrow-right');
      if (arrowLeft) arrowLeft.classList.add('is-hidden');
      if (arrowRight) {
        if (container.scrollWidth > container.clientWidth + 10) {
          arrowRight.classList.remove('is-hidden');
        } else {
          arrowRight.classList.add('is-hidden');
        }
      }
    }, 50);
  }

  function removeFromContinueWatching(contentId) {
    if (!contentId) return;
    const strId = String(contentId);
    
    const cwList = getLocalContinueWatching();
    delete cwList[strId];
    saveLocalContinueWatching(cwList);

    if (currentUser && db) {
      const cwCol = db.collection('users').doc(currentUser.uid).collection('profiles').doc(activeProfileId || 'profile_1').collection('continue_watching');
      cwCol.doc(strId).delete().catch(function () {});
    }

    const rtdbBase = 'https://shorttv-fd9ef-default-rtdb.asia-southeast1.firebasedatabase.app';
    const effectiveUid = (currentUser && currentUser.uid) || localStorage.getItem('short_user_uid');
    if (effectiveUid) {
      fetch(rtdbBase + '/users/' + effectiveUid + '/history/' + strId + '.json', { method: 'DELETE' }).catch(function(){});
      if (typeof firebase !== 'undefined' && firebase.database) {
        try { firebase.database().ref('users/' + effectiveUid + '/history/' + strId).remove(); } catch(e) {}
      }
    }

    renderContinueWatching();
  }

  let cwUnsubscribe = null;
  function initContinueWatchingSync(uid) {
    if (typeof cwUnsubscribe === 'function') {
      cwUnsubscribe();
      cwUnsubscribe = null;
    }

    renderContinueWatching();

    if (!db || !uid) return;
    const profileId = activeProfileId || 'profile_1';
    const cwRef = db.collection('users').doc(uid).collection('profiles').doc(profileId).collection('continue_watching');

    cwUnsubscribe = cwRef.onSnapshot(function (snapshot) {
      const cloudMap = {};
      snapshot.forEach(function (doc) {
        const data = doc.data();
        cloudMap[doc.id] = Object.assign({ id: doc.id }, data);
      });

      saveLocalContinueWatching(cloudMap);
      renderContinueWatching(cloudMap);
      if (typeof window.syncContinueWatchingRow === 'function') {
        window.syncContinueWatchingRow();
      }
    }, function (err) {
      console.warn('[Continue Watching] Firestore snapshot error:', err);
      renderContinueWatching();
    });
  }

  function saveWatchProgress(data) {
    var rawId = data.id || data.series_id || data.post_id || data.tmdb_id;
    if (!data || !rawId) return;

    const cwKey = String(rawId);
    const duration = data.duration || 100;
    const currentTime = data.currentTime || 0;
    let percent = data.percent;
    if (typeof percent === 'undefined' || percent === null) {
      percent = Math.min(100, Math.max(5, Math.round((currentTime / (duration || 1)) * 100)));
    }

    const rawPoster = data.poster || data.poster_path || '';
    const normPoster = normalizeMediaUrl(rawPoster);

    const record = {
      id: cwKey,
      title: data.title || 'Short Drama',
      episode: data.episode || 1,
      lastEpisode: data.episode || 1,
      totalEpisodes: data.totalEpisodes || data.total_episodes || 1,
      poster: normPoster,
      currentTime: currentTime,
      duration: duration,
      percent: percent,
      watchedAt: Date.now()
    };

    // 1. Save locally to Continue Watching
    const cwList = getLocalContinueWatching();
    if (percent >= 95) {
      delete cwList[cwKey];
    } else {
      cwList[cwKey] = record;
    }
    saveLocalContinueWatching(cwList);

    // 2. Save locally to Watch History
    const histList = getLocalWatchHistory();
    histList[cwKey] = record;
    saveLocalWatchHistory(histList);

    // 3. Sync to Firebase Firestore & RTDB
    if (currentUser && db) {
      const payload = Object.assign({}, record, {
        timestamp: firebase.firestore.FieldValue.serverTimestamp()
      });

      const userProfileRef = db.collection('users').doc(currentUser.uid).collection('profiles').doc(activeProfileId || 'profile_1');

      if (percent >= 95) {
        userProfileRef.collection('continue_watching').doc(cwKey).delete().catch(function () {});
      } else {
        userProfileRef.collection('continue_watching').doc(cwKey).set(payload, { merge: true }).catch(function () {});
      }
      userProfileRef.collection('watch_history').doc(cwKey).set(payload, { merge: true }).catch(function () {});

      if (typeof firebase !== 'undefined' && firebase.database) {
        try {
          firebase.database().ref('users/' + currentUser.uid + '/history/' + cwKey).set(record);
        } catch(e) {}
      }
    }

    renderContinueWatching();
    renderHistoryPage();
  }

  function renderHistoryPage() {
    if (document.querySelector('.short-history-container')) return;
    const grid = document.getElementById('history-cards-grid');
    if (!grid) return;

    const dataMap = getLocalWatchHistory();
    const items = Object.values(dataMap).sort(function (a, b) {
      return (b.updated_at || b.timestamp || b.watchedAt || 0) - (a.updated_at || a.timestamp || a.watchedAt || 0);
    });

    if (!items.length) {
      grid.innerHTML = `
        <div class="my-list-empty-state">
          <div class="empty-icon-wrap">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
          <h2 class="empty-title">No Watch History Yet</h2>
          <p class="empty-desc">Short dramas you play will appear here so you can easily track what you have watched.</p>
          <a href="${config.site_url || '/'}" class="btn btn-play" style="margin-top: 14px;">
            <span>Browse Short Dramas</span>
          </a>
        </div>
      `;
      return;
    }

    grid.innerHTML = '';
    items.forEach(function (data) {
      const dramaId = String(data.id || data.post_id || data.tmdb_id || '');
      const percent = Math.min(100, Math.max(5, data.percent || Math.round(((data.currentTime || 0) / (data.duration || 1)) * 100)));
      const rawPoster = data.poster || data.poster_path || data.backdrop || data.backdrop_path || '';
      const imgUrl = normalizeMediaUrl(rawPoster);
      const epNum = parseInt(data.episode || data.lastEpisode || 1, 10);
      const totalEps = parseInt(data.totalEpisodes || data.total_episodes || 1, 10);

      let watchUrl = data.watchUrl || data.watch_url || ((config.site_url || '') + '/watch/' + dramaId + '/?episode=' + epNum);

      const card = document.createElement('div');
      card.className = 'media-card landscape-card history-grid-card';
      card.setAttribute('data-id', dramaId);

      const epBadge = (epNum > 0) ? `<span class="card-ep-badge">EP ${epNum}` + (totalEps > 1 ? ` / ${totalEps}` : '') + `</span>` : '';

      card.innerHTML = `
        <a href="${watchUrl}" class="card-backdrop-wrap">
          <img class="card-backdrop-img" src="${imgUrl}" alt="${data.title || 'Short Drama'}" loading="lazy" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1518199266791-5375a83190b7?w=600&auto=format&fit=crop&q=80';">
          <div class="card-overlay-gradient"></div>
          <div class="card-hover-action">
            <div class="hover-play-btn">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
            </div>
          </div>
          <div class="card-progress-bar-wrap">
            <div class="card-progress-bar-fill" style="width: ${percent}%;"></div>
          </div>
          <div class="card-title-overlay">
            <span class="card-title-text">${data.title || 'Short Drama'}</span>
            ${epBadge}
          </div>
        </a>
      `;

      grid.appendChild(card);
    });
  }

    // ─── initVideoPlayer() has been moved to short-player.js ───────
  // The native player module (short-player.js) now handles:
  //   - HLS.js adaptive streaming
  //   - Quality selector with subscription tier gating
  //   - Subtitle picker
  //   - Skip intro
  //   - Watch progress tracking (localStorage + Firestore)
  //   - Next episode auto-advance
  //   - Server switching
  //   - Paywall gate
  //   - Keyboard controls (Space, arrows, F, M)
  //
  // Expose saveWatchProgress globally so short-player.js can call it:
  window.saveWatchProgress = saveWatchProgress;

  function formatSeconds(sec) {
    const s = Math.floor(sec || 0);
    const m = Math.floor(s / 60);
    const hrs = Math.floor(m / 60);
    const remM = m % 60;
    const remS = s % 60;
    if (hrs > 0) {
      return hrs + ':' + (remM < 10 ? '0' : '') + remM + ':' + (remS < 10 ? '0' : '') + remS;
    }
    return remM + ':' + (remS < 10 ? '0' : '') + remS;
  }

  function initLiveSearch() {
    const searchInput = document.querySelector('#short-live-search-input, .live-search-input, .search-input');
    const searchWrapper = document.querySelector('.short-search-box, .header-search');
    const toggleBtn = document.querySelector('#search-toggle-btn, .search-toggle-btn');
    const closeBtn = document.querySelector('#mobile-search-close-btn, .mobile-search-close-btn');
    const resultsDropdown = document.querySelector('#short-search-dropdown, .short-search-dropdown, .search-results-dropdown');

    function openSearchModal() {
      if (searchWrapper) {
        searchWrapper.classList.add('search-modal-active', 'active');
        const header = document.querySelector('.short-header, #short-header, .reel-header');
        if (header) header.classList.add('search-active');
        if (window.innerWidth <= 768) {
          searchWrapper.classList.add('mobile-modal-active');
          document.body.classList.add('search-header-active');
        }
        if (resultsDropdown) {
          resultsDropdown.classList.remove('show');
          resultsDropdown.innerHTML = '';
        }
        if (searchInput) {
          setTimeout(function () {
            searchInput.focus();
          }, 80);
        }
      }
    }

    function closeSearchModal() {
      if (searchWrapper) {
        searchWrapper.classList.remove('search-modal-active', 'mobile-modal-active', 'active');
        const header = document.querySelector('.short-header, #short-header, .reel-header');
        if (header) header.classList.remove('search-active');
        document.body.classList.remove('search-header-active', 'search-modal-open', 'mobile-search-modal-open');
        if (resultsDropdown) {
          resultsDropdown.classList.remove('show');
          resultsDropdown.innerHTML = '';
        }
        if (searchInput) {
          searchInput.blur();
        }
      }
    }

    if (toggleBtn && searchWrapper) {
      toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (searchWrapper.classList.contains('search-modal-active') || searchWrapper.classList.contains('mobile-modal-active')) {
          const q = searchInput ? searchInput.value.trim() : '';
          if (q.length > 0) {
            window.location.href = (config.site_url || '') + '/search/?q=' + encodeURIComponent(q);
          } else {
            closeSearchModal();
          }
        } else {
          openSearchModal();
        }
      });
    }

    if (searchInput) {
      searchInput.addEventListener('focus', function () {
        openSearchModal();
      });
      searchInput.addEventListener('click', function (e) {
        e.stopPropagation();
        openSearchModal();
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeSearchModal();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeSearchModal();
      }
    });

    // Close when tapping/clicking anywhere outside the search bar/dropdown
    document.addEventListener('click', function (e) {
      if (searchWrapper && (searchWrapper.classList.contains('search-modal-active') || searchWrapper.classList.contains('mobile-modal-active'))) {
        if (!searchWrapper.contains(e.target)) {
          closeSearchModal();
        }
      }
    });

    if (!searchInput || !resultsDropdown) return;

    let debounceTimer = null;
    searchInput.addEventListener('input', function (e) {
      const q = e.target.value.trim();
      clearTimeout(debounceTimer);

      if (q.length < 1) {
        resultsDropdown.classList.remove('show');
        resultsDropdown.innerHTML = '';
        return;
      }

      debounceTimer = setTimeout(async function () {
        try {
          const isKidParam = isKidProfileActive() ? '&is_kids=1' : '';
          const res = await fetch(config.rest_url + '/search?query=' + encodeURIComponent(q) + isKidParam, {
            headers: { 'X-WP-Nonce': config.nonce },
          });
          const data = await res.json();
          let results = data.results || [];
          if (isKidProfileActive()) {
            results = results.filter(isItemKidsFriendly);
          }
          renderSearchResults(results, resultsDropdown, q);
        } catch (err) {
          console.warn('[Search] fetch error:', err);
        }
      }, 200);
    });

    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        const q = searchInput.value.trim();
        if (q.length > 0) {
          window.location.href = (config.site_url || '') + '/search/?q=' + encodeURIComponent(q);
        }
      }
    });

    document.addEventListener('click', function (e) {
      if (searchWrapper && !searchWrapper.contains(e.target)) {
        resultsDropdown.classList.remove('show');
      }
    });
  }

  function renderSearchResults(results, container, query) {
    if (isKidProfileActive() && Array.isArray(results)) {
      results = results.filter(isItemKidsFriendly);
    }

    if (!results || !results.length) {
      container.innerHTML = `
        <div class="search-empty-state">
          <p>No ${isKidProfileActive() ? 'kids/family ' : ''}dramas or titles found for "${query || ''}"</p>
        </div>
      `;
      container.classList.add('show');
      return;
    }

    container.innerHTML = '';
    results.slice(0, 10).forEach(function (item) {
      const el = document.createElement('a');
      el.className = 'search-item';
      const isTv = item.media_type === 'tv' || (!item.title && !!item.name);
      const isDrama = item.is_drama || item.media_type === 'drama' || item.watch_url;
      const type = isDrama ? 'drama' : (isTv ? 'tv' : 'movie');
      const title = item.title || item.name || 'Untitled';
      const dateStr = item.release_date || item.first_air_date || '';
      const year = dateStr.length >= 4 ? dateStr.substring(0, 4) : '';
      const rating = item.vote_average ? Number(item.vote_average).toFixed(1) : '';
      let posterPath = item.poster || item.poster_path || '';
      if (posterPath && !posterPath.startsWith('http')) {
        posterPath = 'https://image.tmdb.org/t/p/w154' + posterPath;
      }
      
      el.href = item.watch_url || (isDrama ? (config.site_url + '/watch/' + item.id) : (config.site_url + '/' + type + '/' + item.id + '/'));

      let thumbMarkup = '';
      if (posterPath) {
        thumbMarkup = `
          <div class="search-item-thumb-box">
            <img class="search-item-thumb-img" src="${posterPath}" alt="${title}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="search-item-fallback-icon" style="display:none;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
            </div>
          </div>
        `;
      } else {
        thumbMarkup = `
          <div class="search-item-thumb-box">
            <div class="search-item-fallback-icon" style="display:flex;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#777" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
            </div>
          </div>
        `;
      }

      const badgeLabel = item.badge || (!isDrama ? (isTv ? 'SERIES' : 'MOVIE') : '');
      const epsText = item.episodes_count ? `<span class="search-meta-item search-meta-eps">${item.episodes_count} Eps</span>` : '';
      const ratingText = (rating && parseFloat(rating) > 0) ? `<span class="search-meta-item search-meta-rating"><span style="color:#ffc107;">★</span>${rating}</span>` : '';
      const genreLabel = item.genre_text ? `<span class="search-meta-item search-meta-genre">${item.genre_text}</span>` : '';

      // Build dot-separated meta items
      const metaParts = [];
      if (badgeLabel) {
        metaParts.push(`<span class="search-badge-type">${badgeLabel}</span>`);
      }
      if (epsText) metaParts.push(epsText);
      if (ratingText) metaParts.push(ratingText);
      if (genreLabel) metaParts.push(genreLabel);

      el.innerHTML = `
        ${thumbMarkup}
        <div class="search-item-info">
          <div class="search-item-title" title="${title}">${title}</div>
          <div class="search-item-meta">
            ${metaParts.join('<span class="search-meta-dot">•</span>')}
          </div>
        </div>
      `;
      container.appendChild(el);
    });

    if (query) {
      const viewAll = document.createElement('a');
      viewAll.className = 'search-item-view-all';
      viewAll.href = (config.site_url || '') + '/search/?q=' + encodeURIComponent(query);
      viewAll.innerHTML = `<span>See all results for "<strong>${query}</strong>"</span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>`;
      container.appendChild(viewAll);
    }

    container.classList.add('show');
  }

  function initSearchPage() {
    const searchPageInput = document.getElementById('search-page-query-input');
    const resultsGrid = document.getElementById('search-page-results-grid');
    if (!searchPageInput || !resultsGrid) return;

    const urlParams = new URLSearchParams(window.location.search);
    const initialQuery = urlParams.get('q') || '';
    if (initialQuery) {
      searchPageInput.value = initialQuery;
      performSearchPageQuery(initialQuery);
    }

    let debounceTimer = null;
    searchPageInput.addEventListener('input', function (e) {
      const q = e.target.value.trim();
      clearTimeout(debounceTimer);
      if (q.length < 2) {
        resultsGrid.innerHTML = '';
        return;
      }
      debounceTimer = setTimeout(function () {
        performSearchPageQuery(q);
      }, 300);
    });

    async function performSearchPageQuery(q) {
      resultsGrid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding: 40px; color: #888;">Searching titles...</div>';
      try {
        const isKidParam = isKidProfileActive() ? '&is_kids=1' : '';
        const res = await fetch(config.rest_url + '/search?query=' + encodeURIComponent(q) + isKidParam, {
          headers: { 'X-WP-Nonce': config.nonce },
        });
        const data = await res.json();
        let items = data.results || [];
        if (isKidProfileActive()) {
          items = items.filter(isItemKidsFriendly);
        }
        if (!items.length) {
          resultsGrid.innerHTML = `<div style="grid-column: 1/-1; text-align:center; padding: 60px 20px; color: #888;">No ${isKidProfileActive() ? 'kids/family ' : ''}results found for "${q}".</div>`;
          return;
        }
        resultsGrid.innerHTML = '';
        items.forEach(function (item) {
          const title = item.title || item.name || 'Untitled';
          const isTv = item.media_type === 'tv' || (!item.title && !!item.name);
          const isDrama = item.is_drama || item.media_type === 'drama' || item.watch_url;
          const itemType = isDrama ? 'drama' : (isTv ? 'tv' : 'movie');
          const id = item.id;
          let poster = item.poster || item.poster_path || '';
          if (poster && !poster.startsWith('http')) {
            poster = 'https://image.tmdb.org/t/p/w500' + poster;
          }
          const rating = item.vote_average ? Number(item.vote_average).toFixed(1) : '';
          const dateStr = item.release_date || item.first_air_date || '';
          const year = dateStr.length >= 4 ? dateStr.substring(0, 4) : '';
          const linkUrl = item.watch_url || (isDrama ? (config.site_url + '/watch/' + id) : (config.site_url + '/' + itemType + '/' + id + '/'));

          const card = document.createElement('div');
          card.className = 'short-card-simple';
          card.innerHTML = `
            <a href="${linkUrl}" class="simple-card-link" aria-label="${title}">
              <div class="simple-poster-wrap">
                ${poster ? `<img src="${poster}" alt="${title}" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">` : ''}
                <div class="poster-placeholder" style="${poster ? 'display:none;' : 'display:flex;'}">
                  <span>${title}</span>
                </div>
                <div class="card-gradient-overlay"></div>
                <div class="card-badges-top">
                  <span class="badge-type">${isDrama ? 'DRAMA' : (isTv ? 'TV' : 'MOVIE')}</span>
                  ${rating ? `<span class="badge-rating">★ ${rating}</span>` : ''}
                </div>
                <div class="card-hover-action">
                  <div class="hover-play-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                  </div>
                </div>
              </div>
              <div class="simple-card-info">
                <div class="simple-card-title" title="${title}">${title}</div>
                <div class="simple-card-meta">
                  ${item.episodes_count ? `<span class="simple-eps">${item.episodes_count} Eps</span>` : (year ? `<span class="simple-year">${year}</span>` : '')}
                  <span class="simple-quality">${item.genre_text ? item.genre_text.split(',')[0] : 'HD'}</span>
                </div>
              </div>
            </a>
          `;
          resultsGrid.appendChild(card);
        });
      } catch (err) {
        console.warn('[Search Page] Error:', err);
        resultsGrid.innerHTML = `<div style="grid-column: 1/-1; text-align:center; padding: 40px; color: #E50914;">Failed to load results. Please try again.</div>`;
      }
    }
  }

  function initRowCarousels() {
    function updateRowArrows(container) {
      if (!container) return;
      const track = container.querySelector('.row-carousel, .carousel-track');
      if (!track) return;
      const arrowLeft = container.querySelector('.carousel-arrow.arrow-left, .slider-arrow-left');
      const arrowRight = container.querySelector('.carousel-arrow.arrow-right, .slider-arrow-right');

      const maxScroll = Math.max(0, track.scrollWidth - track.clientWidth);
      if (maxScroll <= 5) {
        if (arrowLeft) arrowLeft.classList.add('is-hidden');
        if (arrowRight) arrowRight.classList.add('is-hidden');
        return;
      }

      if (arrowLeft) {
        if (track.scrollLeft <= 10) {
          arrowLeft.classList.add('is-hidden');
        } else {
          arrowLeft.classList.remove('is-hidden');
        }
      }

      if (arrowRight) {
        if (track.scrollLeft >= maxScroll - 10) {
          arrowRight.classList.add('is-hidden');
        } else {
          arrowRight.classList.remove('is-hidden');
        }
      }
    }

    function refreshAllArrows() {
      document.querySelectorAll('.row-carousel-container, .carousel-container, .short-row, .content-row').forEach(function (row) {
        updateRowArrows(row);
      });
    }

    // Delegated click event for arrows
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.carousel-arrow, .slider-arrow');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();

      const container = btn.closest('.row-carousel-container, .carousel-container, .short-row, .content-row');
      if (!container) return;
      const track = container.querySelector('.row-carousel, .carousel-track');
      if (!track) return;

      const isLeft = btn.classList.contains('arrow-left') || btn.classList.contains('slider-arrow-left');
      const cards = Array.from(track.children).filter(function(el) {
        return el.classList.contains('media-card') || el.classList.contains('top10-card');
      });

      let targetScrollLeft = track.scrollLeft;

      if (cards.length > 0) {
        const trackRect = track.getBoundingClientRect();
        const currentScroll = track.scrollLeft;
        const visibleWidth = track.clientWidth;

        if (isLeft) {
          // Find the card that should align nicely to the left edge after scrolling left
          let foundTarget = false;
          for (let i = cards.length - 1; i >= 0; i--) {
            const cardLeft = cards[i].offsetLeft;
            if (cardLeft < currentScroll - 20) {
              targetScrollLeft = Math.max(0, cardLeft);
              foundTarget = true;
              break;
            }
          }
          if (!foundTarget) {
            targetScrollLeft = 0;
          }
        } else {
          // Find the first card that is partially or fully cut off on the right
          let foundTarget = false;
          for (let i = 0; i < cards.length; i++) {
            const cardLeft = cards[i].offsetLeft;
            if (cardLeft >= currentScroll + visibleWidth - 30) {
              targetScrollLeft = cardLeft;
              foundTarget = true;
              break;
            }
          }
          if (!foundTarget) {
            // If none found beyond visible width, find the next card past currentScroll
            for (let i = 0; i < cards.length; i++) {
              const cardLeft = cards[i].offsetLeft;
              if (cardLeft > currentScroll + 20) {
                targetScrollLeft = cardLeft;
                foundTarget = true;
                break;
              }
            }
          }
        }
      } else {
        const scrollAmount = Math.max(track.clientWidth * 0.85, 280);
        targetScrollLeft = isLeft ? (track.scrollLeft - scrollAmount) : (track.scrollLeft + scrollAmount);
      }

      track.scrollTo({
        left: Math.max(0, targetScrollLeft),
        behavior: 'smooth',
      });

      setTimeout(function () {
        updateRowArrows(container);
      }, 400);
    });

    // Delegated scroll event
    document.addEventListener('scroll', function (e) {
      if (e.target && (e.target.classList && (e.target.classList.contains('row-carousel') || e.target.classList.contains('carousel-track')))) {
        const container = e.target.closest('.row-carousel-container, .carousel-container, .short-row, .content-row');
        if (container) updateRowArrows(container);
      }
    }, true);

    // Refresh on hover / mouseenter
    document.addEventListener('mouseenter', function (e) {
      if (e.target && e.target.closest && (e.target.closest('.short-row') || e.target.closest('.row-carousel-container'))) {
        const container = e.target.closest('.row-carousel-container, .carousel-container, .short-row, .content-row');
        if (container) updateRowArrows(container);
      }
    }, true);

    window.addEventListener('resize', refreshAllArrows);
    setTimeout(refreshAllArrows, 400);
    setTimeout(refreshAllArrows, 1200);
  }

  function initDragToScroll() {
    const selector = '.cast-cards-track, .row-carousel, .carousel-track, .scroll-drag-enabled';
    const containers = document.querySelectorAll(selector);

    containers.forEach(function (slider) {
      if (slider.getAttribute('data-drag-initialized') === 'true') return;
      slider.setAttribute('data-drag-initialized', 'true');

      let isDown = false;
      let startX = 0;
      let scrollLeft = 0;
      let isDragged = false;
      let dragVelocity = 0;
      let lastX = 0;
      let momentumID = null;

      slider.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        if (e.target.closest('button, input, select, textarea, .custom-season-trigger, .hero-sound-toggle, .carousel-arrow, .slider-arrow, .btn-circle')) return;

        isDown = true;
        isDragged = false;
        slider.classList.add('is-dragging');
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
        lastX = e.pageX;
        dragVelocity = 0;
        cancelAnimationFrame(momentumID);
      });

      function stopDrag() {
        if (!isDown) return;
        isDown = false;
        slider.classList.remove('is-dragging');

        if (Math.abs(dragVelocity) > 2) {
          let momentum = dragVelocity * 10;
          function step() {
            if (Math.abs(momentum) < 0.5) return;
            slider.scrollLeft -= momentum;
            momentum *= 0.88;
            momentumID = requestAnimationFrame(step);
          }
          step();
        }

        setTimeout(function () {
          isDragged = false;
        }, 80);
      }

      window.addEventListener('mouseup', stopDrag);

      slider.addEventListener('mouseleave', function () {
        if (isDown) stopDrag();
      });

      slider.addEventListener('mousemove', function (e) {
        if (!isDown) return;
        e.preventDefault();
        const currentX = e.pageX;
        const x = currentX - slider.offsetLeft;
        const walk = (x - startX) * 1.35;
        
        dragVelocity = currentX - lastX;
        lastX = currentX;

        if (Math.abs(x - startX) > 5) {
          isDragged = true;
        }

        slider.scrollLeft = scrollLeft - walk;
      });

      // Prevent link or card click if user dragged the track
      slider.addEventListener('click', function (e) {
        if (isDragged) {
          e.preventDefault();
          e.stopPropagation();
          isDragged = false;
        }
      }, true);

      slider.addEventListener('dragstart', function (e) {
        e.preventDefault();
      });
    });
  }

  function initQuickModal() {
    const modal = document.querySelector('.short-modal-overlay');
    const closeBtn = document.querySelector('.modal-close-btn');

    if (!modal) return;
    if (closeBtn) {
      closeBtn.addEventListener('click', function () { modal.classList.remove('show'); });
    }
    modal.addEventListener('click', function (e) {
      if (e.target === modal) modal.classList.remove('show');
    });

    document.querySelectorAll('.btn-expand-modal').forEach(function (btn) {
      btn.addEventListener('click', async function (e) {
        e.preventDefault();
        e.stopPropagation();
        const id = btn.getAttribute('data-id');
        const type = btn.getAttribute('data-type') || 'movie';

        try {
          const res = await fetch(config.rest_url + '/' + type + '/' + id, {
            headers: { 'X-WP-Nonce': config.nonce },
          });
          const data = await res.json();
          renderModalContent(data, type);
          modal.classList.add('show');
        } catch (err) {
          console.warn('[Modal] Fetch error:', err);
        }
      });
    });
  }

  function setExpandableOverview(el, text, maxChars) {
    if (!el) return;
    const limit = maxChars || 220;
    const fullText = (text || '').trim();
    if (!fullText) {
      el.textContent = 'Experience this thrilling title streaming now on Short.';
      return;
    }

    if (fullText.length <= limit) {
      el.textContent = fullText;
      return;
    }

    // Find a word boundary near limit
    let truncated = fullText.slice(0, limit);
    const lastSpace = truncated.lastIndexOf(' ');
    if (lastSpace > 120) {
      truncated = truncated.slice(0, lastSpace);
    }

    el.innerHTML = '';

    const shortSpan = document.createElement('span');
    shortSpan.className = 'overview-text overview-text-short';
    shortSpan.textContent = truncated + '…';

    const fullSpan = document.createElement('span');
    fullSpan.className = 'overview-text overview-text-full';
    fullSpan.textContent = fullText;
    fullSpan.style.display = 'none';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-read-more-toggle';
    btn.setAttribute('aria-expanded', 'false');
    btn.innerHTML = `
      <span class="toggle-label-more">Read More</span>
      <span class="toggle-label-less" style="display:none;">Show Less</span>
      <svg class="toggle-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
    `;

    const labelMore = btn.querySelector('.toggle-label-more');
    const labelLess = btn.querySelector('.toggle-label-less');

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      const isExpanded = btn.getAttribute('aria-expanded') === 'true';
      if (isExpanded) {
        shortSpan.style.display = 'inline';
        fullSpan.style.display = 'none';
        labelMore.style.display = 'inline';
        labelLess.style.display = 'none';
        btn.setAttribute('aria-expanded', 'false');
        btn.classList.remove('is-expanded');
      } else {
        shortSpan.style.display = 'none';
        fullSpan.style.display = 'inline';
        labelMore.style.display = 'none';
        labelLess.style.display = 'inline';
        btn.setAttribute('aria-expanded', 'true');
        btn.classList.add('is-expanded');
      }
    });

    el.appendChild(shortSpan);
    el.appendChild(fullSpan);
    el.appendChild(document.createTextNode(' '));
    el.appendChild(btn);
  }

  function renderModalContent(item, type) {
    const titleEl = document.querySelector('.modal-title');
    const overviewEl = document.querySelector('.modal-overview');
    const backdropEl = document.querySelector('.modal-hero-img');
    const playBtn = document.querySelector('.modal-play-btn');
    const matchEl = document.getElementById('modal-match') || document.querySelector('.meta-match');

    if (titleEl) titleEl.textContent = item.title || item.name || '';
    if (overviewEl) setExpandableOverview(overviewEl, item.overview || '', 220);
    if (backdropEl) backdropEl.src = 'https://image.tmdb.org/t/p/w780' + (item.backdrop_path || item.poster_path);
    if (playBtn) playBtn.href = config.site_url + '/watch/' + item.id + '/';

    if (matchEl) {
      const rawRating = parseFloat(item.vote_average || item.rating);
      let score = 95;
      if (!isNaN(rawRating) && rawRating > 0) {
        score = Math.min(99, Math.max(60, Math.round(rawRating * 10)));
      } else {
        const idNum = parseInt(item.id, 10) || 42;
        score = 90 + (idNum % 9);
      }
      matchEl.textContent = score + '% Match';
    }
  }

  function formatAuthError(err) {
    if (!err) return 'An error occurred. Please try again.';
    const code = (err.code || '').toLowerCase();
    const rawMsg = err.message || '';
    const msg = rawMsg.toLowerCase();

    if (code.includes('email-already-in-use') || msg.includes('email-already-in-use') || msg.includes('already in use') || msg.includes('already registered') || msg.includes('already exists')) {
      return 'This email address is already in use. Please sign in or use a different email.';
    }
    if (code.includes('invalid-login-credentials') || code.includes('wrong-password') || code.includes('user-not-found') || msg.includes('invalid-login-credentials') || msg.includes('wrong-password') || msg.includes('user-not-found') || msg.includes('invalid credential')) {
      return 'Incorrect email or password. Please try again or reset your password.';
    }
    if (code.includes('invalid-email') || msg.includes('invalid-email') || msg.includes('badly formatted')) {
      return 'Please enter a valid email address.';
    }
    if (code.includes('weak-password') || msg.includes('weak-password') || msg.includes('at least 6 characters')) {
      return 'Password should be at least 6 characters.';
    }
    if (code.includes('user-disabled') || msg.includes('user-disabled')) {
      return 'This account has been disabled. Please contact support.';
    }
    if (code.includes('too-many-requests') || msg.includes('too-many-requests')) {
      return 'Too many failed attempts. Please wait a few minutes and try again.';
    }
    if (code.includes('network-request-failed') || msg.includes('network-request-failed')) {
      return 'Network connection error. Please check your internet connection.';
    }
    if (code.includes('popup-closed-by-user') || msg.includes('popup-closed-by-user')) {
      return 'Sign in was cancelled.';
    }

    // Strip "Firebase: Error (auth/...)" wrapper if present
    const cleanMsg = rawMsg.replace(/^Firebase:\s*(Error)?\s*(\(([^)]+)\))?:?\s*/i, '').replace(/^auth\//i, '').replace(/[-_]/g, ' ').replace(/\s*\([^)]*\)/g, '').trim();
    if (cleanMsg && cleanMsg.length > 2) {
      return cleanMsg.charAt(0).toUpperCase() + cleanMsg.slice(1) + '.';
    }

    return 'Something went wrong. Please check your information and try again.';
  }

  function initAuthForms() {
    const feedback = document.getElementById('auth-feedback-msg');
    function showFeedback(msg, isError) {
      if (feedback) {
        feedback.textContent = msg;
        feedback.style.color = isError ? '#ff5252' : '#46d369';
        feedback.style.display = 'block';
      } else {
        alert(msg);
      }
    }

    // Google Sign-in Button
    const googleBtn = document.getElementById('btn-google-signin');
    if (googleBtn) {
      googleBtn.addEventListener('click', async function (e) {
        e.preventDefault();
        if (!auth) {
          showFeedback('Firebase is not initialized. Please verify your Firebase API keys in WordPress settings.', true);
          return;
        }
        try {
          const provider = new firebase.auth.GoogleAuthProvider();
          provider.setCustomParameters({ prompt: 'select_account' });
          const result = await auth.signInWithPopup(provider);
          showFeedback('Signed in successfully! Redirecting...', false);
          setTimeout(function () {
            const urlParams = new URLSearchParams(window.location.search);
            const red = urlParams.get('redirect_to') || urlParams.get('redirect');
            if (red && red.startsWith('/') && !red.startsWith('//') && !red.includes('/profile')) {
              window.location.href = red;
            } else {
              window.location.href = (config.site_url || '') + '/';
            }
          }, 400);
        } catch (err) {
          console.error('[Google Auth Error]:', err);
          showFeedback(formatAuthError(err), true);
        }
      });
    }

    // Email/Password Login Form
    const loginForm = document.getElementById('short-login-form');
    if (loginForm) {
      loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const emailEl = document.getElementById('login-email');
        const passEl = document.getElementById('login-password');
        if (!emailEl || !passEl) return;
        const email = emailEl.value.trim();
        const password = passEl.value;
        if (!auth) {
          showFeedback('Firebase Auth is not initialized. Check API settings.', true);
          return;
        }
        try {
          await auth.signInWithEmailAndPassword(email, password);
          showFeedback('Signed in successfully! Redirecting...', false);
          setTimeout(function () { 
            const urlParams = new URLSearchParams(window.location.search);
            const red = urlParams.get('redirect_to') || urlParams.get('redirect');
            if (red && red.startsWith('/') && !red.startsWith('//') && !red.includes('/profile')) {
              window.location.href = red;
            } else {
              window.location.href = (config.site_url || '') + '/';
            }
          }, 400);
        } catch (err) {
          console.error('[Login Error]:', err);
          showFeedback(formatAuthError(err), true);
        }
      });
    }

    // Registration Form
    const regForm = document.getElementById('short-register-form');
    if (regForm) {
      regForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const emailEl = document.getElementById('reg-email');
        const passEl = document.getElementById('reg-password');
        if (!emailEl || !passEl) return;
        const email = emailEl.value.trim();
        const password = passEl.value;
        if (!auth) {
          showFeedback('Firebase Auth is not initialized. Check API settings.', true);
          return;
        }
        try {
          await auth.createUserWithEmailAndPassword(email, password);
          showFeedback('Account created successfully! Redirecting...', false);
          setTimeout(function () { 
            const urlParams = new URLSearchParams(window.location.search);
            const red = urlParams.get('redirect_to') || urlParams.get('redirect');
            if (red && red.startsWith('/') && !red.startsWith('//') && !red.includes('/profile')) {
              window.location.href = red;
            } else {
              window.location.href = (config.site_url || '') + '/';
            }
          }, 400);
        } catch (err) {
          console.error('[Register Error]:', err);
          showFeedback(formatAuthError(err), true);
        }
      });
    }

    // Forgot Password Form
    const forgotForm = document.getElementById('short-forgot-form');
    if (forgotForm) {
      forgotForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const emailEl = document.getElementById('forgot-email');
        if (!emailEl) return;
        const email = emailEl.value.trim();
        if (!auth) {
          showFeedback('Firebase Auth is not initialized.', true);
          return;
        }
        try {
          await auth.sendPasswordResetEmail(email);
          showFeedback('Password reset link sent to your email!', false);
        } catch (err) {
          console.error('[Reset Error]:', err);
          showFeedback(formatAuthError(err), true);
        }
      });
    }

    // Sign Out Button (supports both ID and class triggers)
    document.querySelectorAll('#short-btn-logout, .logout-btn').forEach(function(btn) {
      btn.addEventListener('click', async function (e) {
        e.preventDefault();
        if (auth) {
          try {
            await auth.signOut();
          } catch(err) {
            console.warn('[SignOut]', err);
          }
        }
        // Purge all user watch history, continue watching, watchlist and profile caches from localStorage
        var keysToRemove = [];
        for (var k in localStorage) {
          if (k.startsWith('short_continue_watching') || 
              k.startsWith('short_watch_history') || 
              k.startsWith('short_my_list') || 
              k.startsWith('short_watchlist_') || 
              k.startsWith('shorttv_history') || 
              k.startsWith('shorttv_watch_history') || 
              k.startsWith('short_likes') ||
              k === 'shorttv_watch_history' ||
              k === 'short_is_logged_in' ||
              k === 'short_user_email' ||
              k === 'short_user_display_name' ||
              k === 'short_user_uid' ||
              k === 'short_active_profile_id' ||
              k === 'short_active_avatar' ||
              k === 'short_subscription' ||
              k === 'short_sub_tier' ||
              k === 'shorttv_user_coins') {
            keysToRemove.push(k);
          }
        }
        keysToRemove.forEach(function(k) { localStorage.removeItem(k); });
        localStorage.setItem('shorttv_user_coins', '100');
        if (typeof window.syncHeaderCoins === 'function') window.syncHeaderCoins();

        document.documentElement.classList.add('is-guest-state');
        window.location.href = (config.site_url || '') + '/login/';
      });
    });
  }

  function initHeroSlider() {
    const slider = document.getElementById('hero-billboard-slider');
    if (!slider) return;

    const slides = slider.querySelectorAll('.hero-slide');
    const dots = slider.querySelectorAll('.hero-dot');
    const prevBtn = document.getElementById('hero-slider-prev');
    const nextBtn = document.getElementById('hero-slider-next');
    const soundBtn = document.getElementById('hero-slider-sound-btn');

    if (!slides.length) return;

    let currentIndex = 0;
    let timer = null;
    let heroTrailerTimeout = null;
    let heroIsMuted = true;
    const heroTrailerCache = {};

    function stopCurrentSlideVideo() {
      clearTimeout(heroTrailerTimeout);
      slides.forEach(function (s) {
        const wrap = s.querySelector('.hero-video-wrap');
        const frame = s.querySelector('.hero-video-frame');
        if (wrap) {
          wrap.classList.remove('active');
          wrap.style.display = 'none';
        }
        if (frame) frame.src = '';
      });
      if (soundBtn) soundBtn.style.display = 'none';
    }

    function playHeroTrailer(slide) {
      if (!slide) return;
      const id = slide.getAttribute('data-id');
      const type = slide.getAttribute('data-media-type') || 'movie';
      if (!id) return;
      const cacheKey = type + '_' + id;

      function startVideo(videoKey) {
        if (!videoKey || !slide.classList.contains('active')) return;
        const wrap = slide.querySelector('.hero-video-wrap');
        const frame = slide.querySelector('.hero-video-frame');
        if (!wrap || !frame) return;

        const embedUrl = 'https://www.youtube.com/embed/' + encodeURIComponent(videoKey) + '?autoplay=1&mute=1&controls=0&disablekb=1&iv_load_policy=3&fs=0&rel=0&modestbranding=1&playsinline=1&enablejsapi=1';

        frame.onload = function () {
          if (slide.classList.contains('active') && wrap) {
            wrap.classList.add('active');
          }
        };

        frame.src = embedUrl;
        wrap.style.display = 'block';
        heroIsMuted = true;

        if (soundBtn) {
          soundBtn.style.display = 'inline-flex';
          const iconOn = soundBtn.querySelector('.icon-sound-on');
          const iconOff = soundBtn.querySelector('.icon-sound-off');
          if (iconOn) iconOn.style.display = 'none';
          if (iconOff) iconOff.style.display = 'block';
        }

        setTimeout(function () {
          if (slide.classList.contains('active') && wrap) {
            wrap.classList.add('active');
          }
        }, 500);
      }

      if (heroTrailerCache[cacheKey] !== undefined) {
        if (heroTrailerCache[cacheKey]) {
          startVideo(heroTrailerCache[cacheKey]);
        }
        return;
      }

      if (!config.rest_url) return;
      fetch(config.rest_url + '/' + type + '/' + id, {
        headers: { 'X-WP-Nonce': config.nonce || '' }
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.videos || !Array.isArray(data.videos.results)) {
          heroTrailerCache[cacheKey] = null;
          return;
        }
        const ytVideos = data.videos.results.filter(function (v) {
          return v.site === 'YouTube' && v.key;
        });
        const trailer = ytVideos.find(function (v) { return v.type === 'Trailer'; }) ||
                        ytVideos.find(function (v) { return v.type === 'Teaser'; }) ||
                        ytVideos[0];
        if (trailer && trailer.key) {
          heroTrailerCache[cacheKey] = trailer.key;
          if (slide.classList.contains('active')) {
            startVideo(trailer.key);
          }
        } else {
          heroTrailerCache[cacheKey] = null;
        }
      })
      .catch(function () {
        heroTrailerCache[cacheKey] = null;
      });
    }

    function goToSlide(index) {
      if (index < 0) index = slides.length - 1;
      if (index >= slides.length) index = 0;
      currentIndex = index;

      stopCurrentSlideVideo();

      slides.forEach(function (s, i) {
        if (i === currentIndex) {
          s.classList.add('active');
        } else {
          s.classList.remove('active');
        }
      });

      dots.forEach(function (d, i) {
        if (i === currentIndex) {
          d.classList.add('active');
        } else {
          d.classList.remove('active');
        }
      });

      // Start trailer for the current active slide after 400ms pause
      const currentSlide = slides[currentIndex];
      heroTrailerTimeout = setTimeout(function () {
        playHeroTrailer(currentSlide);
      }, 400);
    }

    if (soundBtn) {
      soundBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const activeSlide = slides[currentIndex];
        if (!activeSlide) return;
        const frame = activeSlide.querySelector('.hero-video-frame');
        if (!frame || !frame.contentWindow) return;

        heroIsMuted = !heroIsMuted;
        const cmd = heroIsMuted ? 'mute' : 'unMute';
        frame.contentWindow.postMessage(JSON.stringify({
          event: 'command',
          func: cmd,
          args: []
        }), '*');

        const iconOn = soundBtn.querySelector('.icon-sound-on');
        const iconOff = soundBtn.querySelector('.icon-sound-off');
        if (iconOn && iconOff) {
          iconOn.style.display = heroIsMuted ? 'none' : 'block';
          iconOff.style.display = heroIsMuted ? 'block' : 'none';
        }
      });
    }

    function startAutoSlide() {
      stopAutoSlide();
      timer = setInterval(function () {
        goToSlide(currentIndex + 1);
      }, 8000);
    }

    function stopAutoSlide() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function (e) {
        e.preventDefault();
        goToSlide(currentIndex - 1);
        startAutoSlide();
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function (e) {
        e.preventDefault();
        goToSlide(currentIndex + 1);
        startAutoSlide();
      });
    }

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        const slideIdx = parseInt(dot.getAttribute('data-slide'), 10) || 0;
        goToSlide(slideIdx);
        startAutoSlide();
      });
    });

    slider.addEventListener('mouseenter', stopAutoSlide);
    slider.addEventListener('mouseleave', startAutoSlide);

    // Touch swipe support for mobile
    let touchStartX = 0;
    let touchStartY = 0;
    slider.addEventListener('touchstart', function (e) {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
    }, { passive: true });
    slider.addEventListener('touchend', function (e) {
      const dx = touchStartX - e.changedTouches[0].clientX;
      const dy = Math.abs(touchStartY - e.changedTouches[0].clientY);
      // Only register horizontal swipes (not scroll)
      if (Math.abs(dx) > 50 && Math.abs(dx) > dy) {
        goToSlide(dx > 0 ? currentIndex + 1 : currentIndex - 1);
        startAutoSlide();
      }
    }, { passive: true });

    // Initial first slide trailer trigger
    goToSlide(0);
    startAutoSlide();
  }

  function initHoverPreviewCards() {
    // Hover preview popup disabled — not used in ShortTV theme
    return;
    // Only enable on non-touch desktop screens
    if (window.matchMedia('(hover: none)').matches) return;

    let previewPortal = document.getElementById('short-hover-preview-portal');
    if (!previewPortal) {
      previewPortal = document.createElement('div');
      previewPortal.id = 'short-hover-preview-portal';
      previewPortal.className = 'short-hover-preview-portal';
      previewPortal.innerHTML = `
        <div class="hover-preview-card" id="hover-preview-card">
          <div class="hover-preview-media">
            <img class="hover-preview-img" id="hp-img" src="" alt="">
            <div class="hover-preview-video-wrap" id="hp-video-wrap" style="display:none;">
              <iframe id="hp-video-frame" src="" allow="autoplay; encrypted-media; picture-in-picture" frameborder="0"></iframe>
            </div>
            <span class="hover-preview-top10" id="hp-top10" style="display:none;">TOP 10</span>
            <div class="hover-preview-gradient"></div>
            <span class="hover-preview-title" id="hp-title"></span>
            <button class="hover-preview-sound-btn" id="hp-sound-btn" aria-label="Toggle sound" type="button" style="display:none;" title="Mute/Unmute Trailer">
              <svg class="icon-sound-on" style="display:none;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg>
              <svg class="icon-sound-off" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><line x1="23" y1="9" x2="17" y2="15"></line><line x1="17" y1="9" x2="23" y2="15"></line></svg>
            </button>
          </div>
          <div class="hover-preview-body">
            <div class="hover-preview-actions">
              <div class="actions-left">
                <a href="#" class="preview-btn-play" id="hp-btn-play" title="Play Now">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </a>
                <button class="preview-btn-action btn-add-list" id="hp-btn-list" title="Add to My List" type="button">
                  <svg class="icon-plus" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
                <button class="preview-btn-action btn-like" id="hp-btn-like" title="I like this" type="button">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                </button>
              </div>
              <div class="actions-right">
                <a href="#" class="preview-btn-action btn-expand-details" id="hp-btn-details" title="Episodes & Info">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </a>
              </div>
            </div>
            <div class="hover-preview-meta">
              <span class="preview-rating" id="hp-rating">★ 8.2</span>
              <span class="preview-age" id="hp-age">16+</span>
              <span class="preview-duration" id="hp-duration">25 Episodes</span>
              <span class="preview-badge-hd">HD</span>
            </div>
            <div class="hover-preview-genres" id="hp-genres">Exciting • Isekai • Superpowers</div>
            <div class="hover-preview-overview" id="hp-overview" style="display:none;"></div>
            <div class="hover-preview-footer">
              <a href="#" class="btn-hover-more-info" id="hp-more-info-btn">
                <span>More Info</span>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </a>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(previewPortal);
    }

    const previewCard = document.getElementById('hover-preview-card');
    const hpImg = document.getElementById('hp-img');
    const hpVideoWrap = document.getElementById('hp-video-wrap');
    const hpVideoFrame = document.getElementById('hp-video-frame');
    const hpTop10 = document.getElementById('hp-top10');
    const hpTitle = document.getElementById('hp-title');
    const hpBtnPlay = document.getElementById('hp-btn-play');
    const hpBtnList = document.getElementById('hp-btn-list');
    const hpBtnLike = document.getElementById('hp-btn-like');
    const hpBtnDetails = document.getElementById('hp-btn-details');
    const hpRating = document.getElementById('hp-rating');
    const hpAge = document.getElementById('hp-age');
    const hpDuration = document.getElementById('hp-duration');
    const hpGenres = document.getElementById('hp-genres');
    const hpOverview = document.getElementById('hp-overview');
    const hpMoreInfoBtn = document.getElementById('hp-more-info-btn');
    const hpSoundBtn = document.getElementById('hp-sound-btn');

    let hoverTimeout = null;
    let hideTimeout = null;
    let trailerTimeout = null;
    let currentCard = null;
    let isVideoMuted = true;
    let lastCardData = null;
    const trailerCache = {};

    if (hpSoundBtn) {
      hpSoundBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const onIcon = hpSoundBtn.querySelector('.icon-sound-on');
        const offIcon = hpSoundBtn.querySelector('.icon-sound-off');
        if (isVideoMuted) {
          isVideoMuted = false;
          if (onIcon) onIcon.style.display = 'block';
          if (offIcon) offIcon.style.display = 'none';
          if (hpVideoFrame && hpVideoFrame.contentWindow) {
            hpVideoFrame.contentWindow.postMessage('{"event":"command","func":"unMute","args":""}', '*');
          }
        } else {
          isVideoMuted = true;
          if (onIcon) onIcon.style.display = 'none';
          if (offIcon) offIcon.style.display = 'block';
          if (hpVideoFrame && hpVideoFrame.contentWindow) {
            hpVideoFrame.contentWindow.postMessage('{"event":"command","func":"mute","args":""}', '*');
          }
        }
      });
    }

    if (hpBtnLike) {
      hpBtnLike.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (lastCardData) {
          toggleLike(lastCardData);
        }
      });
    }

    let modalTrailerTimeout = null;
    let modalIsMuted = true;

    let modalLoadedEpisodesList = [];
    let modalCurrentEpisodePage = 1;
    const MODAL_EPISODES_PER_PAGE = 12;

    function renderModalEpisodesChunk(append) {
      const episodesGrid = document.getElementById('modal-episodes-list');
      const loadMoreWrap = document.getElementById('modal-episodes-loadmore-wrap');
      const loadMoreBtnText = document.getElementById('btn-load-more-episodes-text');
      if (!episodesGrid) return;

      if (!append) {
        episodesGrid.innerHTML = '';
      }

      const total = modalLoadedEpisodesList.length;
      if (total === 0) {
        episodesGrid.innerHTML = '<div class="no-episodes-found" style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #888;"><p>No episodes found.</p></div>';
        if (loadMoreWrap) loadMoreWrap.style.display = 'none';
        return;
      }

      const currentSeason = parseInt(episodesGrid.getAttribute('data-current-season') || '1', 10);
      const tvId = document.getElementById('modal-season-dropdown') ? document.getElementById('modal-season-dropdown').getAttribute('data-tv-id') : '';

      const startIndex = (modalCurrentEpisodePage - 1) * MODAL_EPISODES_PER_PAGE;
      const endIndex = Math.min(startIndex + MODAL_EPISODES_PER_PAGE, total);
      const chunk = modalLoadedEpisodesList.slice(startIndex, endIndex);

      let html = '';
      chunk.forEach(function (ep) {
        const epNum = ep.episode_number || 1;
        const epTitle = ep.name || ('Episode ' + epNum);
        const epDesc = ep.overview || '';
        const epStill = ep.still_path ? (config.tmdb_image_base ? config.tmdb_image_base + 'w300' + ep.still_path : 'https://image.tmdb.org/t/p/w300' + ep.still_path) : '';
        const watchUrl = (config.site_url || '') + '/watch/' + (tvId || '') + '/season-' + currentSeason + '/episode-' + epNum;

        html += '<a href="' + watchUrl + '" class="episode-card">';
        html += '  <div class="episode-thumb-wrap">';
        if (epStill) {
          html += '    <img src="' + epStill + '" alt="' + epTitle.replace(/"/g, '&quot;') + '" loading="lazy" onerror="this.onerror=null;this.src=\'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300&auto=format&fit=crop&q=80\';">';
        } else {
          html += '    <div class="episode-thumb-fallback">EP ' + epNum + '</div>';
        }
        html += '    <div class="episode-play-icon">▶</div>';
        html += '  </div>';
        html += '  <div class="episode-info">';
        html += '    <div class="episode-num-title"><strong>' + epNum + '.</strong> ' + epTitle + '</div>';
        html += '    <p class="episode-desc">' + epDesc + '</p>';
        html += '  </div>';
        html += '</a>';
      });

      if (append) {
        episodesGrid.insertAdjacentHTML('beforeend', html);
      } else {
        episodesGrid.innerHTML = html;
      }

      if (loadMoreWrap) {
        if (endIndex < total) {
          loadMoreWrap.style.display = 'block';
          const remaining = total - endIndex;
          if (loadMoreBtnText) {
            loadMoreBtnText.textContent = 'View More Episodes (' + remaining + ' remaining)';
          }
        } else {
          loadMoreWrap.style.display = 'none';
        }
      }
    }

    // Modal View More Episodes button listener
    const modalLoadMoreEpisodesBtn = document.getElementById('btn-load-more-episodes');
    if (modalLoadMoreEpisodesBtn) {
      modalLoadMoreEpisodesBtn.addEventListener('click', function (e) {
        e.preventDefault();
        modalCurrentEpisodePage++;
        renderModalEpisodesChunk(true);
      });
    }

    function loadModalEpisodes(tvId, seasonNum) {
      const episodesGrid = document.getElementById('modal-episodes-list');
      const loadMoreWrap = document.getElementById('modal-episodes-loadmore-wrap');
      if (!episodesGrid || !tvId) return;

      episodesGrid.classList.add('is-loading');
      if (loadMoreWrap) loadMoreWrap.style.display = 'none';

      const siteBase = (config.site_url ? new URL(config.site_url, window.location.origin).pathname.replace(/\/$/, '') : '');
      const restBase = config.rest_url || (window.location.origin + siteBase + '/wp-json/short/v1');

      fetch(restBase + '/tv/' + tvId + '/season/' + seasonNum, {
        headers: { 'X-WP-Nonce': config.nonce || '' }
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && Array.isArray(data.episodes)) {
          modalLoadedEpisodesList = data.episodes;
          modalCurrentEpisodePage = 1;
          renderModalEpisodesChunk(false);
        } else {
          modalLoadedEpisodesList = [];
          episodesGrid.innerHTML = '<div class="no-episodes-found" style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #888;"><p>No episodes found.</p></div>';
          if (loadMoreWrap) loadMoreWrap.style.display = 'none';
        }
      })
      .catch(function (err) {
        console.warn('[Modal] Error loading season episodes:', err);
        episodesGrid.innerHTML = '<div class="no-episodes-found" style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #888;"><p>Unable to load episodes.</p></div>';
        if (loadMoreWrap) loadMoreWrap.style.display = 'none';
      })
      .finally(function () {
        episodesGrid.classList.remove('is-loading');
      });
    }

    function initModalSeasonDropdown() {
      const dropdown = document.getElementById('modal-season-dropdown');
      const trigger = document.getElementById('modal-season-trigger');
      const menu = document.getElementById('modal-season-menu');
      const selectedText = document.getElementById('modal-season-selected-text');
      const episodesGrid = document.getElementById('modal-episodes-list');

      if (!dropdown || !trigger || !episodesGrid) return;

      // Toggle dropdown
      trigger.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (menu && menu.children.length === 0) return;
        const isOpen = dropdown.classList.toggle('open');
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      };

      // Close on outside click
      document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) {
          dropdown.classList.remove('open');
          trigger.setAttribute('aria-expanded', 'false');
        }
      });

      // Close on Escape
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && dropdown.classList.contains('open')) {
          dropdown.classList.remove('open');
          trigger.setAttribute('aria-expanded', 'false');
        }
      });

      if (!menu) return;

      // Option selection
      menu.onclick = function (e) {
        const option = e.target.closest('.custom-season-option');
        if (!option) return;

        const seasonNum = parseInt(option.getAttribute('data-season-number') || '1', 10);
        const label = option.getAttribute('data-label') || '';
        const currentSeason = parseInt(episodesGrid.getAttribute('data-current-season') || '1', 10);
        const tvId = dropdown.getAttribute('data-tv-id');

        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');

        if (seasonNum === currentSeason) return;

        menu.querySelectorAll('.custom-season-option').forEach(function (opt) {
          opt.classList.remove('selected');
          opt.setAttribute('aria-selected', 'false');
        });
        option.classList.add('selected');
        option.setAttribute('aria-selected', 'true');

        if (selectedText) {
          selectedText.textContent = label;
        }

        episodesGrid.setAttribute('data-current-season', seasonNum);
        loadModalEpisodes(tvId, seasonNum);
      };
    }



    function openDetailsModal(item) {
      const modal = document.getElementById('short-details-modal');
      if (!modal || !item) return;

      const heroImg = document.getElementById('modal-hero-img');
      const modalVideoWrap = document.getElementById('modal-hero-video-wrap');
      const modalVideoFrame = document.getElementById('modal-hero-video-frame');
      const modalSoundBtn = document.getElementById('modal-sound-btn');
      const modalTitle = document.getElementById('modal-title');
      const modalMatch = document.getElementById('modal-match');
      const btnPlay = document.getElementById('modal-btn-play');
      const btnList = document.getElementById('modal-btn-list');
      const modalYear = document.getElementById('modal-year');
      const modalDuration = document.getElementById('modal-duration');
      const modalAge = document.getElementById('modal-age');
      const modalOverview = document.getElementById('modal-overview');
      const modalCast = document.getElementById('modal-cast');
      const modalGenres = document.getElementById('modal-genres');
      const modalTags = document.getElementById('modal-tags');
      const moreGrid = document.getElementById('modal-more-grid');
      const modalEpisodesSection = document.getElementById('modal-episodes-section');
      const modalSeasonDropdown = document.getElementById('modal-season-dropdown');
      const modalSeasonMenu = document.getElementById('modal-season-menu');
      const modalSeasonSelectedText = document.getElementById('modal-season-selected-text');
      const modalEpisodesList = document.getElementById('modal-episodes-list');

      // Reset trailer video preview
      clearTimeout(modalTrailerTimeout);
      if (modalVideoWrap) {
        modalVideoWrap.classList.remove('active');
        modalVideoWrap.style.display = 'none';
      }
      if (modalVideoFrame) {
        modalVideoFrame.src = '';
      }
      if (modalSoundBtn) {
        modalSoundBtn.style.display = 'none';
      }

      if (heroImg) heroImg.src = item.backdrop || '';
      if (modalTitle) modalTitle.textContent = item.title || '';
      if (btnPlay) btnPlay.href = (config.site_url || '') + '/watch/' + item.id + '/?type=' + item.type;
      if (modalYear) modalYear.textContent = item.year || '2024';
      if (modalDuration) modalDuration.textContent = item.duration || (item.type === 'tv' ? '1 Season' : '1h 50m');
      if (modalAge) modalAge.textContent = item.age || '16+';
      if (modalOverview) setExpandableOverview(modalOverview, item.overview || 'Experience this thrilling title streaming now on Short.', 220);
      if (modalGenres) modalGenres.textContent = item.genres || 'Action, Thriller';
      if (modalTags) modalTags.textContent = 'Gripping, Suspenseful, Exciting';

      // Dynamic match calculation
      if (modalMatch) {
        const rawRating = parseFloat(item.rating);
        let score = 95;
        if (!isNaN(rawRating) && rawRating > 0) {
          score = Math.min(99, Math.max(60, Math.round(rawRating * 10)));
        } else {
          const idNum = parseInt(item.id, 10) || 42;
          score = 90 + (idNum % 9);
        }
        modalMatch.textContent = score + '% Match';
      }

      // Episodes / Seasons section reset
      if (item.type === 'tv') {
        if (modalEpisodesSection) modalEpisodesSection.style.display = 'block';
        if (modalSeasonDropdown) {
          modalSeasonDropdown.setAttribute('data-tv-id', item.id);
          modalSeasonDropdown.classList.remove('open');
        }
        if (modalSeasonMenu) modalSeasonMenu.innerHTML = '';
        if (modalSeasonSelectedText) modalSeasonSelectedText.textContent = 'Season 1';
        if (modalEpisodesList) {
          modalEpisodesList.setAttribute('data-current-season', '1');
          modalEpisodesList.innerHTML = '<div class="episodes-loading" style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: #888;">Loading episodes...</div>';
        }
      } else {
        if (modalEpisodesSection) modalEpisodesSection.style.display = 'none';
        if (modalEpisodesList) modalEpisodesList.innerHTML = '';
      }

      if (btnList) {
        btnList.setAttribute('data-id', item.id);
        btnList.setAttribute('data-type', item.type);
        btnList.setAttribute('data-title', item.title);
        btnList.setAttribute('data-backdrop', item.backdrop);
        btnList.setAttribute('data-rating', item.rating);
        if (myListCache && myListCache[item.id]) {
          btnList.classList.add('in-list', 'active');
          btnList.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        } else {
          btnList.classList.remove('in-list', 'active');
          btnList.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
        }
      }

      const modalBtnLike = document.getElementById('modal-btn-like');
      if (modalBtnLike) {
        modalBtnLike.setAttribute('data-id', item.id);
        modalBtnLike.setAttribute('data-type', item.type);
        modalBtnLike.setAttribute('data-title', item.title);
        modalBtnLike.setAttribute('data-backdrop', item.backdrop);
        modalBtnLike.setAttribute('data-poster', item.poster || item.backdrop);
        modalBtnLike.setAttribute('data-rating', item.rating);
        modalBtnLike.setAttribute('data-year', item.year);
        if (myLocalLikes && myLocalLikes.has(String(item.id))) {
          modalBtnLike.classList.add('liked', 'active', 'active-liked');
          modalBtnLike.setAttribute('title', 'Liked');
          modalBtnLike.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
        } else {
          modalBtnLike.classList.remove('liked', 'active', 'active-liked');
          modalBtnLike.setAttribute('title', 'I like this');
          modalBtnLike.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
        }
      }

      function startModalTrailer(videoKey) {
        if (!videoKey || !modalVideoFrame || !modalVideoWrap) return;
        const embedUrl = 'https://www.youtube.com/embed/' + encodeURIComponent(videoKey) + '?autoplay=1&mute=1&controls=0&disablekb=1&iv_load_policy=3&fs=0&rel=0&modestbranding=1&playsinline=1&enablejsapi=1';

        modalVideoFrame.onload = function () {
          if (modalVideoWrap) {
            modalVideoWrap.classList.add('active');
          }
        };

        modalVideoFrame.src = embedUrl;
        modalVideoWrap.style.display = 'block';
        modalIsMuted = true;
        if (modalSoundBtn) {
          modalSoundBtn.style.display = 'inline-flex';
          const iconOn = modalSoundBtn.querySelector('.icon-sound-on');
          const iconOff = modalSoundBtn.querySelector('.icon-sound-off');
          if (iconOn) iconOn.style.display = 'none';
          if (iconOff) iconOff.style.display = 'block';
        }
        setTimeout(function () {
          if (modalVideoWrap) {
            modalVideoWrap.classList.add('active');
          }
        }, 300);
      }

      // Fetch dynamic details if REST URL or fallback site base is available
      const siteBase = (config.site_url ? new URL(config.site_url, window.location.origin).pathname.replace(/\/$/, '') : '');
      const restBase = config.rest_url || (window.location.origin + siteBase + '/wp-json/short/v1');
      const itemType = (item.type === 'tv' || item.type === 'series') ? 'tv' : 'movie';
      const itemId = item.id;

      if (restBase && itemId) {
        fetch(restBase + '/' + itemType + '/' + itemId, {
          headers: { 'X-WP-Nonce': config.nonce || '' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data) return;
          if (modalMatch && data.vote_average) {
            const dynamicRating = parseFloat(data.vote_average);
            if (!isNaN(dynamicRating) && dynamicRating > 0) {
              const score = Math.min(99, Math.max(60, Math.round(dynamicRating * 10)));
              modalMatch.textContent = score + '% Match';
            }
          }
          if (data.overview && modalOverview) setExpandableOverview(modalOverview, data.overview, 220);
          if (data.credits && data.credits.cast && modalCast) {
            const castNames = data.credits.cast.slice(0, 5).map(function (c) { return c.name; }).join(', ');
            modalCast.textContent = castNames || 'Popular Ensemble Cast';
          }
          if (data.genres && Array.isArray(data.genres) && modalGenres) {
            modalGenres.textContent = data.genres.map(function (g) { return g.name; }).join(', ');
          }

          // Handle TV seasons and episode loading
          if (itemType === 'tv') {
            const validSeasons = (data.seasons && Array.isArray(data.seasons))
              ? data.seasons.filter(function (s) { return (s.season_number || 0) > 0 && (s.episode_count || 0) > 0; })
              : [];

            if (validSeasons.length === 0) {
              validSeasons.push({
                season_number: 1,
                name: 'Season 1',
                episode_count: data.number_of_episodes || 12
              });
            }

            const firstSeason = validSeasons[0];
            const firstSeasonNum = firstSeason ? firstSeason.season_number : 1;
            const firstSeasonName = firstSeason ? (firstSeason.name || ('Season ' + firstSeasonNum)) : 'Season 1';
            const firstSeasonCount = firstSeason ? (firstSeason.episode_count || 0) : 0;
            const firstSeasonLabel = firstSeasonName + ' (' + firstSeasonCount + ' Episodes)';

            if (modalSeasonSelectedText) {
              modalSeasonSelectedText.textContent = firstSeasonLabel;
            }
            if (modalEpisodesList) {
              modalEpisodesList.setAttribute('data-current-season', firstSeasonNum);
            }

            if (modalSeasonMenu) {
              modalSeasonMenu.innerHTML = '';
              if (validSeasons.length > 1) {
                if (modalSeasonDropdown) modalSeasonDropdown.style.display = 'inline-block';
                validSeasons.forEach(function (s, idx) {
                  const sNum = s.season_number;
                  const sName = s.name || ('Season ' + sNum);
                  const sCount = s.episode_count || 0;
                  const sLabel = sName + ' (' + sCount + ' Episodes)';
                  const isActive = (idx === 0);

                  const opt = document.createElement('div');
                  opt.className = 'custom-season-option' + (isActive ? ' selected' : '');
                  opt.setAttribute('role', 'option');
                  opt.setAttribute('aria-selected', isActive ? 'true' : 'false');
                  opt.setAttribute('data-season-number', sNum);
                  opt.setAttribute('data-label', sLabel);
                  opt.setAttribute('data-name', sName);
                  opt.setAttribute('data-count', sCount);
                  opt.innerHTML = `
                    <span class="season-opt-name">${sName}</span>
                    <span class="season-opt-count">${sCount} Episodes</span>
                    <svg class="season-opt-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                  `;
                  modalSeasonMenu.appendChild(opt);
                });
              } else {
                if (modalSeasonDropdown) modalSeasonDropdown.style.display = 'inline-block';
              }
            }

            loadModalEpisodes(itemId, firstSeasonNum);
          }

          // Autoplay background trailer video preview in modal hero
          if (data.videos && Array.isArray(data.videos.results)) {
            const ytVideos = data.videos.results.filter(function (v) {
              return v.site === 'YouTube' && v.key;
            });
            const trailer = ytVideos.find(function (v) { return v.type === 'Trailer'; }) ||
                            ytVideos.find(function (v) { return v.type === 'Teaser'; }) ||
                            ytVideos[0];
            if (trailer && trailer.key) {
              modalTrailerTimeout = setTimeout(function () {
                startModalTrailer(trailer.key);
              }, 400);
            }
          }

          // Use recommendations first, fall back to similar
          let simList = (data.recommendations && data.recommendations.results && data.recommendations.results.length > 0)
            ? data.recommendations.results
            : (data.similar && data.similar.results ? data.similar.results : []);

          if (simList.length > 0 && moreGrid) {
            moreGrid.innerHTML = '';
            simList.slice(0, 9).forEach(function (sim) {
              const simTitle = sim.title || sim.name || '';
              const simType = sim.title ? 'movie' : 'tv';
              const simBackdrop = sim.backdrop_path
                ? 'https://image.tmdb.org/t/p/w780' + sim.backdrop_path
                : (sim.poster_path ? 'https://image.tmdb.org/t/p/w500' + sim.poster_path : '');
              const simYear = (sim.release_date || sim.first_air_date || '').slice(0, 4);
              const simRating = sim.vote_average ? parseFloat(sim.vote_average).toFixed(1) : '';
              const simRuntime = sim.runtime ? (Math.floor(sim.runtime / 60) + 'h ' + (sim.runtime % 60) + 'm') : '';
              const simOverview = sim.overview ? sim.overview.slice(0, 120) + (sim.overview.length > 120 ? '…' : '') : '';
              const simUrl = (config.site_url || '') + '/' + simType + '/' + sim.id + '/';
              const simFallback = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=780&auto=format&fit=crop&q=80';

              const card = document.createElement('div');
              card.className = 'rec-card-item';
              card.innerHTML = `
                <a href="${simUrl}" class="rec-card-thumb">
                  <img src="${simBackdrop || simFallback}" alt="${simTitle}" loading="lazy" onerror="this.onerror=null;this.src='${simFallback}';">
                  ${simRuntime ? `<span class="rec-duration-badge">${simRuntime}</span>` : ''}
                  <div class="rec-play-overlay">
                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                  </div>
                </a>
                <div class="rec-card-body">
                  <div class="rec-card-meta-row">
                    <div class="rec-card-badges">
                      <span class="rec-age-badge">13+</span>
                      <span class="rec-hd-badge">HD</span>
                      ${simYear ? `<span class="rec-year">${simYear}</span>` : ''}
                    </div>
                    <button class="rec-add-btn btn-add-list"
                      data-id="${sim.id}" data-type="${simType}"
                      data-title="${simTitle.replace(/"/g, '&quot;')}"
                      data-backdrop="${simBackdrop}"
                      data-rating="${simRating}"
                      title="Add to My List">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                  </div>
                  ${simOverview ? `<p class="rec-card-desc">${simOverview}</p>` : ''}
                </div>
              `;
              moreGrid.appendChild(card);
            });
          } else if (moreGrid) {
            moreGrid.innerHTML = '<p style="color:#888;font-size:0.9rem;padding:20px 0;">No similar titles found.</p>';
          }
        })
        .catch(function (err) {
          console.warn('[Modal] Fetch details err:', err);
        });
      }

      window.SHORT.openDetailsModal = openDetailsModal;

      modal.style.display = 'flex';
      setTimeout(function () {
        modal.classList.add('show');
      }, 10);
      document.body.style.overflow = 'hidden';
    }

    // Modal Sound Button Click Handler
    const modalSoundBtn = document.getElementById('modal-sound-btn');
    if (modalSoundBtn) {
      modalSoundBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const modalVideoFrame = document.getElementById('modal-hero-video-frame');
        if (!modalVideoFrame || !modalVideoFrame.contentWindow) return;
        modalIsMuted = !modalIsMuted;
        const cmd = modalIsMuted ? 'mute' : 'unMute';
        modalVideoFrame.contentWindow.postMessage(JSON.stringify({
          event: 'command',
          func: cmd,
          args: []
        }), '*');
        const iconOn = modalSoundBtn.querySelector('.icon-sound-on');
        const iconOff = modalSoundBtn.querySelector('.icon-sound-off');
        if (iconOn && iconOff) {
          iconOn.style.display = modalIsMuted ? 'none' : 'block';
          iconOff.style.display = modalIsMuted ? 'block' : 'none';
        }
      });
    }

    function closeDetailsModal() {
      const modal = document.getElementById('short-details-modal');
      if (!modal) return;
      clearTimeout(modalTrailerTimeout);
      const modalVideoWrap = document.getElementById('modal-hero-video-wrap');
      const modalVideoFrame = document.getElementById('modal-hero-video-frame');
      const modalSoundBtn = document.getElementById('modal-sound-btn');
      if (modalVideoWrap) {
        modalVideoWrap.classList.remove('active');
        modalVideoWrap.style.display = 'none';
      }
      if (modalVideoFrame) {
        modalVideoFrame.src = '';
      }
      if (modalSoundBtn) {
        modalSoundBtn.style.display = 'none';
      }
      modal.classList.remove('show');
      setTimeout(function () {
        modal.style.display = 'none';
      }, 280);
      document.body.style.overflow = '';
    }

    // Modal Close Event Listeners
    const modalEl = document.getElementById('short-details-modal');
    const modalCloseBtn = document.getElementById('details-modal-close');
    const modalBackdrop = document.getElementById('details-modal-backdrop');

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeDetailsModal);
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeDetailsModal);

    if (modalEl) {
      modalEl.addEventListener('click', function (e) {
        if (!e.target.closest('.short-details-modal-container')) {
          closeDetailsModal();
        }
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDetailsModal();
    });

    initModalSeasonDropdown();

    if (hpBtnDetails) {
      hpBtnDetails.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        hidePreview();
        if (lastCardData) {
          openDetailsModal(lastCardData);
        }
      });
    }

    function showPreview(card) {
      if (!card) return;
      currentCard = card;

      const id = card.getAttribute('data-id');
      const type = card.getAttribute('data-type') || 'movie';
      const title = card.getAttribute('data-title') || '';
      const backdrop = card.getAttribute('data-backdrop') || '';
      const rating = card.getAttribute('data-rating') || '';
      const age = card.getAttribute('data-age') || '16+';
      const duration = card.getAttribute('data-duration') || (type === 'tv' ? '1 Season' : '1h 50m');
      const genres = card.getAttribute('data-genres') || 'Exciting • Blockbuster';
      const overview = card.getAttribute('data-overview') || '';
      const isTop10 = card.getAttribute('data-top10') === '1';

      const cardImgEl = card.querySelector('.card-backdrop-img, .top10-poster-img, img');
      const fallbackBackdrop = (cardImgEl && cardImgEl.src) ? cardImgEl.src : 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=780&auto=format&fit=crop&q=80';
      const actualBackdrop = backdrop || fallbackBackdrop;

      lastCardData = {
        id: id,
        type: type,
        title: title,
        backdrop: actualBackdrop,
        rating: rating,
        age: age,
        duration: duration,
        genres: genres,
        overview: overview,
        isTop10: isTop10
      };

      // Populate content
      hpImg.src = actualBackdrop;
      hpImg.alt = title;
      hpImg.onerror = function () {
        this.onerror = null;
        this.src = fallbackBackdrop;
      };
      hpTitle.textContent = title;
      hpTop10.style.display = isTop10 ? 'inline-block' : 'none';

      if (rating) {
        hpRating.textContent = '★ ' + rating;
        hpRating.style.display = 'inline-block';
      } else {
        hpRating.style.display = 'none';
      }

      hpAge.textContent = age;
      hpDuration.textContent = duration;
      hpGenres.textContent = genres;

      // Populate Overview Synopsis
      if (hpOverview) {
        if (overview && overview.trim()) {
          hpOverview.textContent = overview.trim();
          hpOverview.style.display = '-webkit-box';
        } else {
          hpOverview.textContent = '';
          hpOverview.style.display = 'none';
        }
      }

      // Populate More Info redirect button
      const detailsUrl = (config.site_url || '') + '/' + type + '/' + id + '/';
      if (hpMoreInfoBtn) {
        hpMoreInfoBtn.href = detailsUrl;
      }

      const watchUrl = (config.site_url || '') + '/watch/' + id + '/?type=' + type;
      hpBtnPlay.href = watchUrl;

      hpBtnList.setAttribute('data-id', id);
      hpBtnList.setAttribute('data-type', type);
      hpBtnList.setAttribute('data-title', title);
      hpBtnList.setAttribute('data-backdrop', actualBackdrop);
      hpBtnList.setAttribute('data-rating', rating);

      // Check if in My List
      if (myListCache && myListCache[id]) {
        hpBtnList.classList.add('in-list', 'active');
        hpBtnList.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
      } else {
        hpBtnList.classList.remove('in-list', 'active');
        hpBtnList.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
      }

      if (hpBtnLike) {
        hpBtnLike.setAttribute('data-id', id);
        hpBtnLike.setAttribute('data-type', type);
        hpBtnLike.setAttribute('data-title', title);
        hpBtnLike.setAttribute('data-backdrop', actualBackdrop);
        hpBtnLike.setAttribute('data-rating', rating);
        if (myLocalLikes && myLocalLikes.has(String(id))) {
          hpBtnLike.classList.add('liked', 'active', 'active-liked');
          hpBtnLike.setAttribute('title', 'Liked');
          hpBtnLike.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
        } else {
          hpBtnLike.classList.remove('liked', 'active', 'active-liked');
          hpBtnLike.setAttribute('title', 'I like this');
          hpBtnLike.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>';
        }
      }

      // Calculate position
      const targetEl = card.querySelector('.top10-poster-wrap, .card-backdrop-wrap') || card;
      const rect = targetEl.getBoundingClientRect();
      const targetWidth = Math.min(Math.max(rect.width * 1.36, 280), 360);
      const EDGE_GUARD = 62; // width of carousel nav arrow zone

      let targetLeft = rect.left - (targetWidth - rect.width) / 2;
      const targetTop = rect.top - 28;

      // Detect if card is near the LEFT edge → pin popup to left of card, not past the arrow zone
      const isNearLeft = rect.left < EDGE_GUARD + 10;
      // Detect if card is near the RIGHT edge → pin popup to right of card, not past the arrow zone
      const isNearRight = (rect.right > window.innerWidth - EDGE_GUARD - 10);

      if (isNearLeft) {
        targetLeft = Math.max(EDGE_GUARD, rect.left);
      } else if (isNearRight) {
        targetLeft = Math.min(rect.right - targetWidth, window.innerWidth - targetWidth - EDGE_GUARD);
      } else {
        // Center under card, clamped to safe zone
        targetLeft = Math.max(EDGE_GUARD, Math.min(targetLeft, window.innerWidth - targetWidth - EDGE_GUARD));
      }

      previewPortal.style.top = (targetTop + window.scrollY) + 'px';
      previewPortal.style.left = (targetLeft + window.scrollX) + 'px';
      previewPortal.style.width = targetWidth + 'px';

      // Reset trailer video preview
      clearTimeout(trailerTimeout);
      if (hpVideoWrap) {
        hpVideoWrap.classList.remove('active');
        hpVideoWrap.style.display = 'none';
      }
      if (hpVideoFrame) {
        hpVideoFrame.src = '';
      }
      if (hpSoundBtn) {
        hpSoundBtn.style.display = 'none';
        const onIcon = hpSoundBtn.querySelector('.icon-sound-on');
        const offIcon = hpSoundBtn.querySelector('.icon-sound-off');
        if (onIcon) onIcon.style.display = 'none';
        if (offIcon) offIcon.style.display = 'block';
      }
      isVideoMuted = true;

      // Autoplay trailer after 400ms of sustained hover
      trailerTimeout = setTimeout(function () {
        if (!previewPortal.classList.contains('show') || currentCard !== card) return;
        playCardTrailer(id, type);
      }, 400);

      previewPortal.classList.add('show');
    }

    function playCardTrailer(id, type) {
      if (!id) return;
      const cacheKey = type + '_' + id;

      function startTrailer(videoKey) {
        if (!videoKey || !previewPortal.classList.contains('show') || !hpVideoFrame || !hpVideoWrap) return;
        const embedUrl = 'https://www.youtube.com/embed/' + encodeURIComponent(videoKey) + '?autoplay=1&mute=1&controls=0&disablekb=1&iv_load_policy=3&fs=0&rel=0&modestbranding=1&playsinline=1&enablejsapi=1';

        hpVideoFrame.onload = function () {
          if (previewPortal.classList.contains('show') && hpVideoWrap) {
            hpVideoWrap.classList.add('active');
          }
        };

        hpVideoFrame.src = embedUrl;
        hpVideoWrap.style.display = 'block';
        if (hpSoundBtn) hpSoundBtn.style.display = 'flex';

        setTimeout(function () {
          if (previewPortal.classList.contains('show') && hpVideoWrap) {
            hpVideoWrap.classList.add('active');
          }
        }, 250);
      }

      if (trailerCache[cacheKey] !== undefined) {
        if (trailerCache[cacheKey]) {
          startTrailer(trailerCache[cacheKey]);
        }
        return;
      }

      if (!config.rest_url) return;
      fetch(config.rest_url + '/' + type + '/' + id, {
        headers: { 'X-WP-Nonce': config.nonce || '' }
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.videos || !Array.isArray(data.videos.results)) {
          trailerCache[cacheKey] = null;
          return;
        }
        const ytVideos = data.videos.results.filter(function (v) {
          return v.site === 'YouTube' && v.key;
        });
        const trailer = ytVideos.find(function (v) { return v.type === 'Trailer'; }) ||
                        ytVideos.find(function (v) { return v.type === 'Teaser'; }) ||
                        ytVideos[0];

        if (data.overview && hpOverview && (!hpOverview.textContent || !hpOverview.textContent.trim())) {
          hpOverview.textContent = data.overview.trim();
          hpOverview.style.display = '-webkit-box';
        }

        if (trailer && trailer.key) {
          trailerCache[cacheKey] = trailer.key;
          if (previewPortal.classList.contains('show')) {
            startTrailer(trailer.key);
          }
        } else {
          trailerCache[cacheKey] = null;
        }
      })
      .catch(function () {
        trailerCache[cacheKey] = null;
      });
    }

    function hidePreview() {
      clearTimeout(trailerTimeout);
      if (hpVideoWrap) {
        hpVideoWrap.classList.remove('active');
        hpVideoWrap.style.display = 'none';
      }
      if (hpVideoFrame) {
        hpVideoFrame.src = '';
      }
      if (hpSoundBtn) {
        hpSoundBtn.style.display = 'none';
      }
      previewPortal.classList.remove('show');
      currentCard = null;
    }

    // Attach delegated events for fast, reliable hover
    document.addEventListener('mouseover', function (e) {
      const card = e.target.closest('.media-card');
      if (card) {
        clearTimeout(hideTimeout);
        if (currentCard === card && previewPortal.classList.contains('show')) return;
        clearTimeout(hoverTimeout);
        hoverTimeout = setTimeout(function () {
          showPreview(card);
        }, 180);
      }
    });

    document.addEventListener('mouseout', function (e) {
      const card = e.target.closest('.media-card');
      if (card) {
        clearTimeout(hoverTimeout);
        hideTimeout = setTimeout(function () {
          if (!previewPortal.matches(':hover')) {
            hidePreview();
          }
        }, 180);
      }
    });

    previewPortal.addEventListener('mouseenter', function () {
      clearTimeout(hideTimeout);
    });

    previewPortal.addEventListener('mouseleave', function () {
      hideTimeout = setTimeout(function () {
        hidePreview();
      }, 150);
    });
  }

  function initGenreFilterAjax() {
    const gridContainer = document.getElementById('genre-grid-container');
    const pillsContainer = document.querySelector('.genre-pills');
    const loader = document.getElementById('genre-filter-loader');
    const cardsGrid = document.querySelector('.short-grid-cards');
    const pageTitleEl = document.querySelector('.page-main-title');
    const sentinel = document.getElementById('infinite-scroll-sentinel');
    const infiniteLoader = document.getElementById('infinite-scroll-loader');
    const loadMoreWrap = document.getElementById('load-more-btn-wrap');
    const loadMoreBtn = document.getElementById('btn-load-more');

    if (!cardsGrid) return;

    let currentReqController = null;
    let isLoadingMore = false;
    let currentType = gridContainer ? (gridContainer.getAttribute('data-current-type') || 'movie') : 'movie';
    let currentGenreId = gridContainer ? parseInt(gridContainer.getAttribute('data-current-genre') || '0', 10) : 0;
    let currentPage = gridContainer ? parseInt(gridContainer.getAttribute('data-current-page') || '1', 10) : 1;
    let hasMore = gridContainer ? (gridContainer.getAttribute('data-has-more') !== 'false') : true;

    function fetchGenreTitles(type, genreId, newUrl, pushHistory) {
      if (loader) loader.classList.add('show');
      if (infiniteLoader) infiniteLoader.style.display = 'none';
      if (loadMoreWrap) loadMoreWrap.style.display = 'none';

      if (currentReqController) {
        currentReqController.abort();
      }
      currentReqController = new AbortController();

      currentType = type;
      currentGenreId = genreId;
      currentPage = 1;
      hasMore = true;

      if (gridContainer) {
        gridContainer.setAttribute('data-current-type', type);
        gridContainer.setAttribute('data-current-genre', genreId);
        gridContainer.setAttribute('data-current-page', '1');
        gridContainer.setAttribute('data-has-more', 'true');
      }

      const ajaxUrl = config.ajax_url || '/wp-admin/admin-ajax.php';
      const params = new URLSearchParams({
        action: 'short_filter_genre',
        type: type,
        genre_id: genreId,
        page: 1
      });

      fetch(ajaxUrl + '?' + params.toString(), {
        signal: currentReqController.signal
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          if (res && res.success && res.data) {
            if (cardsGrid) {
              cardsGrid.innerHTML = res.data.html;
            }
            if (pageTitleEl && res.data.title) {
              pageTitleEl.textContent = res.data.title;
            }
            hasMore = !!res.data.has_more;
            if (gridContainer) {
              gridContainer.setAttribute('data-has-more', hasMore ? 'true' : 'false');
            }
            if (pushHistory && newUrl) {
              window.history.pushState({ type: type, genre_id: genreId }, '', newUrl);
            }
            // Update My List buttons on new cards
            updateMyListButtonsUI();
          }
        })
        .catch(function (err) {
          if (err.name !== 'AbortError') {
            console.warn('[Short] Genre filter request error:', err);
          }
        })
        .finally(function () {
          if (loader) loader.classList.remove('show');
        });
    }

    function loadMoreTitles() {
      if (isLoadingMore || !hasMore) return;

      isLoadingMore = true;
      if (infiniteLoader) infiniteLoader.style.display = 'flex';
      if (loadMoreWrap) loadMoreWrap.style.display = 'none';

      const nextPage = currentPage + 1;
      const ajaxUrl = config.ajax_url || '/wp-admin/admin-ajax.php';
      const params = new URLSearchParams({
        action: 'short_filter_genre',
        type: currentType,
        genre_id: currentGenreId,
        page: nextPage
      });

      fetch(ajaxUrl + '?' + params.toString())
        .then(function (res) { return res.json(); })
        .then(function (res) {
          if (res && res.success && res.data && res.data.html) {
            currentPage = nextPage;
            hasMore = !!res.data.has_more;
            if (gridContainer) {
              gridContainer.setAttribute('data-current-page', currentPage);
              gridContainer.setAttribute('data-has-more', hasMore ? 'true' : 'false');
            }
            cardsGrid.insertAdjacentHTML('beforeend', res.data.html);
            updateMyListButtonsUI();
          } else {
            hasMore = false;
            if (gridContainer) gridContainer.setAttribute('data-has-more', 'false');
          }
        })
        .catch(function (err) {
          console.warn('[Short] Load more error:', err);
        })
        .finally(function () {
          isLoadingMore = false;
          if (infiniteLoader) infiniteLoader.style.display = 'none';
        });
    }

    // Pill Navigation Clicks
    if (pillsContainer) {
      pillsContainer.addEventListener('click', function (e) {
        const pill = e.target.closest('.genre-pill');
        if (!pill) return;

        const type = pill.getAttribute('data-type') || 'movie';
        const genreId = parseInt(pill.getAttribute('data-genre-id') || '0', 10);
        const targetUrl = pill.getAttribute('href');

        e.preventDefault();

        // Toggle active state on pills
        pillsContainer.querySelectorAll('.genre-pill').forEach(function (p) {
          p.classList.remove('active');
        });
        pill.classList.add('active');

        fetchGenreTitles(type, genreId, targetUrl, true);
      });
    }

    // Support Browser Back / Forward buttons without full reload
    window.addEventListener('popstate', function (e) {
      const state = e.state;
      if (state && typeof state.genre_id !== 'undefined' && pillsContainer) {
        const targetPill = pillsContainer.querySelector('.genre-pill[data-genre-id="' + state.genre_id + '"]');
        if (targetPill) {
          pillsContainer.querySelectorAll('.genre-pill').forEach(function (p) {
            p.classList.remove('active');
          });
          targetPill.classList.add('active');
        }
        fetchGenreTitles(state.type || 'movie', state.genre_id, window.location.href, false);
      }
    });

    // Auto Infinite Scroll with IntersectionObserver
    if (sentinel && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) {
          loadMoreTitles();
        }
      }, { rootMargin: '400px 0px', threshold: 0.01 });

      observer.observe(sentinel);
    }

    // Scroll event fallback for continuous smooth loading
    let scrollThrottle = null;
    window.addEventListener('scroll', function () {
      if (!sentinel || isLoadingMore || !hasMore) return;
      if (scrollThrottle) return;
      scrollThrottle = setTimeout(function () {
        scrollThrottle = null;
        const rect = sentinel.getBoundingClientRect();
        if (rect.top <= window.innerHeight + 500) {
          loadMoreTitles();
        }
      }, 150);
    });

    // Manual Load More Button fallback click
    if (loadMoreBtn) {
      loadMoreBtn.addEventListener('click', function (e) {
        e.preventDefault();
        loadMoreTitles();
      });
    }
  }

  function initCustomSeasonDropdown() {
    const dropdown = document.getElementById('custom-season-dropdown');
    const trigger = document.getElementById('custom-season-trigger');
    const menu = document.getElementById('custom-season-menu');
    const selectedText = document.getElementById('custom-season-selected-text');
    const episodesGrid = document.getElementById('episodes-list-grid');

    if (!dropdown || !trigger || !episodesGrid) return;

    const tvId = dropdown.getAttribute('data-tv-id');
    let isFetching = false;

    // Toggle dropdown
    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      const isOpen = dropdown.classList.toggle('open');
      trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
      }
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && dropdown.classList.contains('open')) {
        dropdown.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.focus();
      }
    });

    if (!menu) return;

    // Option selection
    menu.addEventListener('click', function (e) {
      const option = e.target.closest('.custom-season-option');
      if (!option || isFetching) return;

      const seasonNum = parseInt(option.getAttribute('data-season-number') || '1', 10);
      const label = option.getAttribute('data-label') || '';
      const currentSeason = parseInt(episodesGrid.getAttribute('data-current-season') || '1', 10);

      // Close menu
      dropdown.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');

      if (seasonNum === currentSeason) return;

      // Update active option UI
      menu.querySelectorAll('.custom-season-option').forEach(function (opt) {
        opt.classList.remove('selected');
        opt.setAttribute('aria-selected', 'false');
      });
      option.classList.add('selected');
      option.setAttribute('aria-selected', 'true');

      if (selectedText) {
        selectedText.textContent = label;
      }

      episodesGrid.setAttribute('data-current-season', seasonNum);
      episodesGrid.classList.add('is-loading');
      isFetching = true;

      // Fetch episodes for selected season
      const ajaxUrl = config.ajax_url || '/wp-admin/admin-ajax.php';
      const params = new URLSearchParams({
        action: 'short_get_season_episodes',
        tv_id: tvId,
        season: seasonNum
      });

      fetch(ajaxUrl + '?' + params.toString())
        .then(function (res) { return res.json(); })
        .then(function (res) {
          if (res && res.success && res.data && res.data.html) {
            episodesGrid.innerHTML = res.data.html;
          } else if (config.rest_url) {
            // Fallback to REST API
            return fetch(config.rest_url + '/tv/' + tvId + '/season/' + seasonNum, {
              headers: { 'X-WP-Nonce': config.nonce || '' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data && Array.isArray(data.episodes)) {
                let html = '';
                data.episodes.forEach(function (ep) {
                  const epNum = ep.episode_number || 1;
                  const epTitle = ep.name || ('Episode ' + epNum);
                  const epDesc = ep.overview || '';
                  const epStill = ep.still_path ? (config.tmdb_image_base ? config.tmdb_image_base + 'w300' + ep.still_path : 'https://image.tmdb.org/t/p/w300' + ep.still_path) : '';
                  const watchUrl = (config.site_url || '') + '/watch/' + tvId + '/season-' + seasonNum + '/episode-' + epNum;

                  html += '<a href="' + watchUrl + '" class="episode-card">';
                  html += '  <div class="episode-thumb-wrap">';
                  if (epStill) {
                    html += '    <img src="' + epStill + '" alt="' + epTitle.replace(/"/g, '&quot;') + '" loading="lazy" onerror="this.onerror=null;this.src=\'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=300&auto=format&fit=crop&q=80\';">';
                  } else {
                    html += '    <div class="episode-thumb-fallback">EP ' + epNum + '</div>';
                  }
                  html += '    <div class="episode-play-icon">▶</div>';
                  html += '  </div>';
                  html += '  <div class="episode-info">';
                  html += '    <div class="episode-num-title"><strong>' + epNum + '.</strong> ' + epTitle + '</div>';
                  html += '    <p class="episode-desc">' + epDesc + '</p>';
                  html += '  </div>';
                  html += '</a>';
                });
                episodesGrid.innerHTML = html || '<div class="no-episodes-found" style="padding: 40px 20px; text-align: center; color: #888;"><p>No episodes found.</p></div>';
              }
            });
          }
        })
        .catch(function (err) {
          console.warn('[Short] Error loading season episodes:', err);
        })
        .finally(function () {
          episodesGrid.classList.remove('is-loading');
          isFetching = false;
        });
    });
  }

  function initHeaderKidsSwitcher() {
    const kidsBtn = document.getElementById('header-kids-btn');
    if (!kidsBtn) return;

    const proList = currentProfilesList.length ? currentProfilesList : getDefaultProfiles();
    const activeProfile = proList.find(function(p) { return p.id === activeProfileId; }) || proList[0];
    const isCurrentlyKid = activeProfile ? !!activeProfile.isKid : false;
    updateHeaderKidsButtonUI(isCurrentlyKid);

    kidsBtn.addEventListener('click', function (e) {
      e.preventDefault();
      const currentList = currentProfilesList.length ? currentProfilesList : getDefaultProfiles();
      const curr = currentList.find(function(p) { return p.id === activeProfileId; }) || currentList[0];

      if (curr && curr.isKid) {
        // Exit Kids: switch back to the primary adult profile
        const adultProfile = currentList.find(function(p) { return !p.isKid; }) || currentList[0];
        if (adultProfile.pin && adultProfile.pin.trim().length > 0) {
          promptUnlockPin(adultProfile);
        } else {
          selectAndEnterProfile(adultProfile);
        }
      } else {
        // Enter Kids Profile
        let kidProfile = currentList.find(function(p) { return p.isKid; });
        if (!kidProfile) {
          kidProfile = {
            id: 'profile_3',
            name: 'Kids',
            avatar: (config.site_url || '') + '/wp-content/themes/short-stream/assets/images/avatar-4.svg',
            isKid: true
          };
          currentList.push(kidProfile);
          saveUpdatedProfiles(currentList);
        }
        selectAndEnterProfile(kidProfile);
      }
    });
  }

  /* ==========================================================================
     SUBSCRIPTION & MEMBERSHIP ENGINE
     ========================================================================== */


  function saveSubscription(plan) {
    // Strictly forbid activating VIP on demo or guest accounts
    let user = null;
    if (typeof firebase !== 'undefined' && firebase.auth) {
      user = firebase.auth().currentUser;
    }
    const localEmail = localStorage.getItem('short_user_email');
    const isRealUser = (user && user.email) || (localEmail && localEmail !== 'guest@short.local' && localStorage.getItem('short_is_logged_in') === '1');

    if (!isRealUser) {
      console.warn('[ShortTV] VIP activation blocked: VIP Pass cannot be activated on a demo/guest account.');
      return null;
    }

    const planId = (plan.plan_id || 'standard').toLowerCase();
    let days = 30;
    let bonusCoins = 500;

    if (planId === 'basic') {
      days = 7;
      bonusCoins = 100;
    } else if (planId === 'standard') {
      days = 30;
      bonusCoins = 500;
    } else if (planId === 'premium') {
      days = 365;
      bonusCoins = 2000;
    }

    const subData = {
      plan_id: planId,
      tier: planId,
      plan_name: plan.plan_name || 'VIP Pass',
      quality: plan.quality || '1080p Ultra HD',
      price: plan.price || (config.subscription ? config.subscription.plan_standard_price || '14.99' : '14.99'),
      status: 'active',
      currency: config.subscription ? config.subscription.currency_symbol || '$' : '$',
      started_at: Date.now(),
      expires_at: Date.now() + days * 24 * 60 * 60 * 1000,
      expiresAt: Date.now() + days * 24 * 60 * 60 * 1000
    };

    localStorage.setItem('short_subscription', JSON.stringify(subData));
    localStorage.setItem('short_sub_tier', planId);
    document.cookie = 'short_sub_tier=' + encodeURIComponent(planId) + '; path=/; max-age=' + (days * 86400) + '; SameSite=Lax';

    // Award bonus coins
    let currentCoins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
    currentCoins += bonusCoins;
    localStorage.setItem('shorttv_user_coins', String(currentCoins));

    // Firebase RTDB sync
    if (typeof firebase !== 'undefined' && firebase.database && (currentUser || (firebase.auth && firebase.auth().currentUser))) {
      const user = currentUser || firebase.auth().currentUser;
      const rtdb = firebase.database();
      rtdb.ref('users/' + user.uid + '/subscription').set(subData);
      if (user.email) {
        rtdb.ref('users/' + user.uid + '/email').set(user.email);
        localStorage.setItem('short_user_email', user.email);
        localStorage.setItem('short_is_logged_in', '1');
      }
      rtdb.ref('users/' + user.uid + '/coins').set(currentCoins);
      rtdb.ref('users/' + user.uid + '/coinHistory').push({
        amount: bonusCoins,
        reason: subData.plan_name + ' Bonus Coins',
        ts: Date.now()
      });
    }

    return subData;
  }
  window.saveSubscription = saveSubscription;

  function cancelSubscription() {
    localStorage.removeItem('short_subscription');
    localStorage.removeItem('short_sub_tier');
    document.cookie = 'short_sub_tier=; path=/; max-age=0; SameSite=Lax';

    if (typeof firebase !== 'undefined' && firebase.database && (currentUser || (firebase.auth && firebase.auth().currentUser))) {
      const user = currentUser || firebase.auth().currentUser;
      firebase.database().ref('users/' + user.uid + '/subscription').remove();
    }
  }
  window.cancelSubscription = cancelSubscription;

  function initSubscriptionPage() {
    const grid = document.getElementById('subscription-plans-grid');
    if (!grid) return;

    const currentSub = getSubscription();
    const sym = config.subscription ? config.subscription.currency_symbol || '$' : '$';
    const proceedBtn = document.getElementById('btn-proceed-checkout');
    const modal = document.getElementById('modal-checkout-activation');
    const closeBtn = document.getElementById('btn-close-checkout-modal');
    const backBtn = document.getElementById('btn-back-checkout-modal');
    const changePlanBtn = document.getElementById('btn-modal-change-plan');
    const activateBtn = document.getElementById('btn-activate-subscription');
    const statusMsg = document.getElementById('checkout-status-msg');
    const cancelOptionWrap = document.getElementById('cancel-sub-page-option');
    const cancelPageBtn = document.getElementById('btn-cancel-sub-page');
    const cards = grid.querySelectorAll('.sub-plan-card');

    let currentPlanId = (currentSub && currentSub.status === 'active') ? (currentSub.plan_id || 'premium') : null;

    // Default selected plan (either the user's current plan or the featured premium plan)
    let selectedPlan = {
      plan_id: 'premium',
      plan_name: 'Premium 4K',
      quality: '4K Ultra HD + HDR',
      price: config.subscription ? config.subscription.plan_premium_price || '15.99' : '15.99'
    };

    function updateCardsUI() {
      cards.forEach(function (card) {
        const cardPlanId = card.getAttribute('data-plan-id');
        const cardPlanName = card.getAttribute('data-plan-name') || 'Plan';
        const cardBtn = card.querySelector('.btn-select-plan');
        const isCurrent = (currentPlanId && cardPlanId === currentPlanId);
        const isSel = (cardPlanId === selectedPlan.plan_id);

        // Remove old ribbons
        const oldRibbon = card.querySelector('.current-plan-ribbon');
        if (oldRibbon) oldRibbon.remove();

        if (isCurrent) {
          card.classList.add('is-current-plan');
          const ribbon = document.createElement('div');
          ribbon.className = 'current-plan-ribbon';
          ribbon.innerHTML = '<span>✓ CURRENT PLAN</span>';
          card.prepend(ribbon);
        } else {
          card.classList.remove('is-current-plan');
        }

        if (isSel) {
          card.classList.add('is-selected');
        } else {
          card.classList.remove('is-selected');
        }

        if (cardBtn) {
          if (isCurrent) {
            cardBtn.textContent = '✓ Current Plan';
            cardBtn.classList.add('btn-current-active');
          } else {
            cardBtn.textContent = isCurrent ? '✓ Current Plan' : ('Select ' + cardPlanName);
            cardBtn.classList.remove('btn-current-active');
          }
        }
      });

      // Update Proceed CTA button text
      if (proceedBtn) {
        if (currentPlanId && selectedPlan.plan_id === currentPlanId) {
          proceedBtn.innerHTML = '<span>✓ ' + selectedPlan.plan_name + ' • Watch</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        } else if (currentPlanId) {
          proceedBtn.innerHTML = '<span>Switch to ' + selectedPlan.plan_name + '</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        } else {
          proceedBtn.innerHTML = '<span>Continue</span> <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>';
        }
      }

      // Update modal values
      const nameEl = document.getElementById('checkout-selected-plan-name');
      const qualEl = document.getElementById('checkout-selected-plan-quality');
      const priceEl = document.getElementById('checkout-selected-plan-price');
      if (nameEl) nameEl.textContent = selectedPlan.plan_name;
      if (qualEl) qualEl.textContent = selectedPlan.quality;
      if (priceEl) priceEl.textContent = sym + selectedPlan.price;
    }

    // Initialize selection to current plan if user has one
    if (currentPlanId) {
      cards.forEach(function (card) {
        if (card.getAttribute('data-plan-id') === currentPlanId) {
          selectedPlan = {
            plan_id: currentPlanId,
            plan_name: card.getAttribute('data-plan-name') || 'Premium 4K',
            quality: card.getAttribute('data-quality') || '4K Ultra HD + HDR',
            price: card.getAttribute('data-price') || '15.99'
          };
        }
      });
    }

    updateCardsUI();

    // Cancel Plan option on subscription page
    if (cancelOptionWrap && cancelPageBtn) {
      if (currentPlanId && currentSub && currentSub.status === 'active' && !currentSub.is_default) {
        cancelOptionWrap.style.display = 'block';
        cancelPageBtn.onclick = async function (e) {
          e.preventDefault();
          const confirmed = await shortCustomConfirm({
            title: 'Cancel Membership?',
            message: 'Are you sure you want to cancel your ' + (currentSub.plan_name || 'streaming') + ' membership? You will be downgraded to the Free Guest Pass.',
            confirmText: 'Yes, Cancel Plan',
            cancelText: 'Keep Plan'
          });
          if (confirmed) {
            cancelSubscription();
            await shortCustomAlert({
              title: 'Membership Cancelled',
              message: 'Your membership has been cancelled successfully. You are now on the Free Guest Pass.',
              type: 'info'
            });
            window.location.reload();
          }
        };
      } else {
        cancelOptionWrap.style.display = 'none';
      }
    }

    // Card click / selection
    cards.forEach(function (card) {
      card.addEventListener('click', function () {
        selectedPlan = {
          plan_id: card.getAttribute('data-plan-id') || 'premium',
          plan_name: card.getAttribute('data-plan-name') || 'Premium 4K',
          quality: card.getAttribute('data-quality') || '4K Ultra HD + HDR',
          price: card.getAttribute('data-price') || '15.99'
        };
        updateCardsUI();
        const section = document.getElementById('checkout-activation-section');
        if (section) section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });
    });

    // Handle proceed or scroll to checkout section
    if (proceedBtn) {
      proceedBtn.addEventListener('click', function () {
        if (currentPlanId && selectedPlan.plan_id === currentPlanId) {
          const urlParams = new URLSearchParams(window.location.search);
          const redirectUrl = urlParams.get('redirect');
          window.location.href = redirectUrl || (config.site_url || '') + '/';
          return;
        }
        const section = document.getElementById('checkout-activation-section');
        if (section) section.scrollIntoView({ behavior: 'smooth' });
      });
    }

    if (activateBtn) {
      activateBtn.addEventListener('click', async function () {
        // Enforce: Guest / Demo accounts CANNOT activate or buy VIP
        let user = (typeof firebase !== 'undefined' && firebase.auth) ? firebase.auth().currentUser : null;
        const localEmail = localStorage.getItem('short_user_email');
        let isRealUser = (user && user.email) || (localEmail && localEmail !== 'guest@short.local' && localStorage.getItem('short_is_logged_in') === '1');

        if (!isRealUser) {
          // Trigger Google sign-in immediately
          if (typeof firebase !== 'undefined' && firebase.auth) {
            try {
              const provider = new firebase.auth.GoogleAuthProvider();
              provider.setCustomParameters({ prompt: 'select_account' });
              const res = await firebase.auth().signInWithPopup(provider);
              if (res && res.user && res.user.email) {
                user = res.user;
                isRealUser = true;
                if (typeof window.updateCheckoutUserUI === 'function') {
                  window.updateCheckoutUserUI(user);
                }
              } else {
                return;
              }
            } catch (err) {
              console.warn('[Checkout VIP Google Auth]', err);
              if (statusMsg) {
                statusMsg.style.display = 'block';
                statusMsg.innerHTML = '<span style="color:#ef4444; font-weight:700;">🔒 Google Sign-In Required: VIP memberships cannot be activated on demo or guest accounts.</span>';
              }
              return;
            }
          } else {
            alert('🔒 Google Sign-In Required: Demo or guest accounts cannot activate VIP passes. Please sign in with your Google account first.');
            return;
          }
        }

        // If custom Lemon Squeezy checkout URL is configured from WP Admin or localStorage, open the Lemon.js overlay
        const planId = (selectedPlan && selectedPlan.plan_id) || 'standard';
        const customLsUrl = (typeof window.SHORT_LS_CONFIG !== 'undefined' && window.SHORT_LS_CONFIG.vip_urls && window.SHORT_LS_CONFIG.vip_urls[planId]) || (typeof window.SHORT_LS_CONFIG !== 'undefined' && window.SHORT_LS_CONFIG.vip_urls && window.SHORT_LS_CONFIG.vip_urls.standard) || (typeof window.SHORT_LS_CONFIG !== 'undefined' && window.SHORT_LS_CONFIG.vip_checkout_url) || localStorage.getItem('shorttv_ls_vip_url');
        if (customLsUrl && typeof LemonSqueezy !== 'undefined' && LemonSqueezy.Url) {
          sessionStorage.setItem('short_pending_checkout', JSON.stringify({
            type: 'vip',
            plan_id: planId,
            plan_name: selectedPlan.plan_name || 'VIP Pass',
            price: selectedPlan.price
          }));
          LemonSqueezy.Url.Open(customLsUrl);
          return;
        }

        const btnText = activateBtn.querySelector('.btn-text');
        const btnSpinner = activateBtn.querySelector('.btn-spinner');

        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline-block';
        activateBtn.disabled = true;

        setTimeout(function () {
          saveSubscription(selectedPlan);

          if (statusMsg) {
            statusMsg.style.display = 'block';
            statusMsg.innerHTML = '<span style="color:#10b981; font-weight:700; font-size:1.05rem;">🎉 VIP Plan Updated to ' + selectedPlan.plan_name + ' (Lemon Squeezy Test Mode)!</span><br><span style="color:#a0a0a0; font-size:0.85rem;">Redirecting to your stream...</span>';
          }

          setTimeout(function () {
            const urlParams = new URLSearchParams(window.location.search);
            const redirectUrl = urlParams.get('redirect');
            if (redirectUrl) {
              window.location.href = redirectUrl;
            } else {
              window.location.href = (config.site_url || '') + '/';
            }
          }, 1200);
        }, 800);
      });
    }
  }

  function initProfileMembershipCard() {
    const card = document.getElementById('user-account-membership-card');
    const badgeEl = document.getElementById('account-plan-badge') || document.getElementById('profile-plan-badge');
    const nameEl = document.getElementById('account-plan-name') || document.getElementById('profile-plan-name');
    const statusEl = document.getElementById('account-plan-perks') || document.getElementById('profile-plan-status');
    const cancelBtn = document.getElementById('btn-cancel-subscription');

    if (!card && !badgeEl && !nameEl && !cancelBtn) return;

    const sub = getSubscription();
    if (sub && sub.status === 'active' && !sub.is_default) {
      if (badgeEl) {
        badgeEl.textContent = (sub.plan_name || 'PREMIUM 4K').toUpperCase();
        badgeEl.style.background = 'rgba(var(--theme-accent-rgb, 229, 9, 20), 0.15)';
        badgeEl.style.color = 'var(--theme-accent, #E50914)';
        badgeEl.style.borderColor = 'rgba(var(--theme-accent-rgb, 229, 9, 20), 0.3)';
      }
      if (nameEl) nameEl.textContent = (sub.plan_name || 'Premium 4K') + ' Plan';
      if (statusEl) statusEl.textContent = (sub.quality || '4K Ultra HD + HDR') + ', Unlimited Streaming, Ad-free';
      if (cancelBtn) {
        cancelBtn.style.display = 'inline-flex';
        cancelBtn.onclick = async function (e) {
          e.preventDefault();
          const confirmed = await shortCustomConfirm({
            title: 'Cancel Streaming Plan?',
            message: 'Are you sure you want to cancel your ' + (sub.plan_name || 'streaming') + ' membership? You will lose access to HD/4K streaming perks and revert to the Free Guest Pass.',
            confirmText: 'Yes, Cancel Plan',
            cancelText: 'Keep Plan'
          });
          if (confirmed) {
            cancelSubscription();
            await shortCustomAlert({
              title: 'Membership Cancelled',
              message: 'Your membership has been cancelled successfully. Your account is now on the Free Guest Pass.',
              type: 'info'
            });
            window.location.reload();
          }
        };
      }
    } else {
      if (badgeEl) {
        badgeEl.textContent = 'FREE PASS';
        badgeEl.style.background = 'rgba(255, 255, 255, 0.08)';
        badgeEl.style.color = '#94a3b8';
        badgeEl.style.borderColor = 'rgba(255, 255, 255, 0.15)';
      }
      if (nameEl) nameEl.textContent = 'Free Guest Pass';
      if (statusEl) statusEl.textContent = 'Standard definition (720p), 1 Active Screen, Ad-supported';
      if (cancelBtn) {
        cancelBtn.style.display = 'none';
      }
    }
  }

  // Second initVideoPlayer() removed — now handled by short-player.js module
  // (See comment at line ~1421 for details)

  document.addEventListener('DOMContentLoaded', function () {
    initFirebase();
    initAuthForms();
    initWhosWatchingEvents();
    initHeroSlider();
    initLiveSearch();
    initSearchPage();
    initHeaderKidsSwitcher();
    initRowCarousels();
    initDragToScroll();
    initHoverPreviewCards();
    initQuickModal();
    initSubscriptionPage();
    initProfileMembershipCard();
    // initVideoPlayer() removed — short-player.js auto-initializes on DOMContentLoaded
    initGenreFilterAjax();
    initCustomSeasonDropdown();
    initMyListPageFilters();
    // Render My List, Likes, Continue Watching, and History from localStorage immediately
    updateMyListButtonsUI();
    updateLikeButtonsUI();
    syncLikesFromBackend();
    renderMyListPage();
    renderContinueWatching();
    renderHistoryPage();
    initDragToScroll();

    document.addEventListener('click', function (e) {
      const likeBtn = e.target.closest('.btn-like, #modal-btn-like, #hp-btn-like');
      if (likeBtn) {
        e.preventDefault();
        e.stopPropagation();

        let title = likeBtn.getAttribute('data-title');
        let poster = likeBtn.getAttribute('data-poster');
        let backdrop = likeBtn.getAttribute('data-backdrop');
        let rating = likeBtn.getAttribute('data-rating');
        let year = likeBtn.getAttribute('data-year');
        let mediaType = likeBtn.getAttribute('data-type') || 'movie';
        let id = likeBtn.getAttribute('data-id');

        if (!title) {
          const container = likeBtn.closest('.hero-slide, .media-card, .slider-item, .hero-inner, .watch-hero-content, .details-modal-content, .netflix-preview-modal, #hover-preview-card');
          if (container) {
            const titleEl = container.querySelector('.hero-title, .card-title, .media-title, .modal-title, #hp-title, h1, h2, h3');
            if (titleEl && titleEl.textContent) title = titleEl.textContent.trim();
          }
        }

        toggleLike({
          id: id,
          title: title,
          media_type: mediaType,
          poster_path: poster,
          backdrop_path: backdrop,
          vote_average: rating,
          year: year
        });
        return;
      }

      const listBtn = e.target.closest('.btn-add-list, .btn-action-list');
      if (listBtn) {
        e.preventDefault();
        e.stopPropagation();

        let title = listBtn.getAttribute('data-title');
        let poster = listBtn.getAttribute('data-poster');
        let backdrop = listBtn.getAttribute('data-backdrop');
        let rating = listBtn.getAttribute('data-rating');
        let year = listBtn.getAttribute('data-year');
        let mediaType = listBtn.getAttribute('data-type') || 'movie';
        let id = listBtn.getAttribute('data-id');

        if (!title) {
          const container = listBtn.closest('.hero-slide, .media-card, .slider-item, .hero-inner, .watch-hero-content, .details-modal-content, .netflix-preview-modal, #hover-preview-card');
          if (container) {
            const titleEl = container.querySelector('.hero-title, .card-title, .media-title, .modal-title, #hp-title, h1, h2, h3');
            if (titleEl && titleEl.textContent) title = titleEl.textContent.trim();
          }
        }

        toggleMyList({
          id: id,
          title: title,
          media_type: mediaType,
          poster_path: poster,
          backdrop_path: backdrop,
          vote_average: rating,
          year: year
        });
      }
    });

    // ── Notifications System (Desktop Dropdown & Mobile Page) ──────────
    function initNotificationsSystem() {
      const notifBtn = document.querySelector('#notif-toggle-btn, .notif-btn');
      const notifDropdown = document.querySelector('#notif-dropdown-menu, .notif-dropdown');
      const notifBadge = document.getElementById('notif-unread-count');
      const dropdownContainer = document.getElementById('notif-items-container');
      const pageContainer = document.getElementById('notif-page-items-container');
      const markAllReadBtn = document.getElementById('notif-mark-all-read');
      const pageMarkAllReadBtn = document.getElementById('notif-page-mark-all-read');

      if (notifBtn) {
        notifBtn.addEventListener('click', function (e) {
          if (window.innerWidth <= 768) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = (config.site_url || '') + '/notification/';
            return;
          }
          if (notifDropdown) {
            e.stopPropagation();
            notifDropdown.classList.toggle('show');
          }
        });
        if (notifDropdown) {
          document.addEventListener('click', function () { notifDropdown.classList.remove('show'); });
        }
      }

      function getReadNotifIds() {
        try {
          return JSON.parse(localStorage.getItem('short_read_notif_ids') || '[]');
        } catch (e) {
          return [];
        }
      }

      function markNotifRead(id) {
        const readIds = getReadNotifIds();
        if (!readIds.includes(id)) {
          readIds.push(id);
          try {
            localStorage.setItem('short_read_notif_ids', JSON.stringify(readIds));
          } catch(e) {}
        }
        updateBadgeAndReadState();
      }

      function markAllRead() {
        if (!window._shortNotifications) return;
        const allIds = window._shortNotifications.map(function(n) { return n.id; });
        try {
          localStorage.setItem('short_read_notif_ids', JSON.stringify(allIds));
        } catch(e) {}
        updateBadgeAndReadState();
      }

      if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          markAllRead();
        });
      }

      if (pageMarkAllReadBtn) {
        pageMarkAllReadBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          markAllRead();
        });
      }

      function timeAgo(timestamp) {
        if (!timestamp) return 'Just now';
        const seconds = Math.floor((Date.now() / 1000) - timestamp);
        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        const days = Math.floor(hours / 24);
        return days + 'd ago';
      }

      function updateBadgeAndReadState() {
        const notifs = window._shortNotifications || [];
        const readIds = getReadNotifIds();
        const unread = notifs.filter(function(n) { return !readIds.includes(n.id); });
        const badgeCountText = unread.length > 9 ? '9+' : String(unread.length);

        // Update all notification badges (desktop header, mobile header, mobile bottom nav, settings hub)
        const allBadges = document.querySelectorAll('#notif-unread-count, .notif-badge, .mobile-nav-notif-badge, .settings-inline-notif-badge, #settings-hub-notif-unread-count, #dropdown-notif-unread-count');
        allBadges.forEach(function(badge) {
          if (unread.length > 0) {
            badge.textContent = badgeCountText;
            badge.style.display = 'inline-flex';
          } else {
            badge.style.display = 'none';
          }
        });

        // Update read/unread class in DOM
        document.querySelectorAll('.short-notif-item').forEach(function(item) {
          const id = item.getAttribute('data-notif-id');
          if (readIds.includes(id)) {
            item.classList.add('is-read');
            item.classList.remove('is-unread');
          } else {
            item.classList.add('is-unread');
            item.classList.remove('is-read');
          }
        });
      }

      function openNotificationModal(n) {
        const overlay = document.createElement('div');
        overlay.className = 'short-custom-popup-overlay short-notif-full-overlay';
        
        const badgeColor = n.badge_color || (n.type === 'important' ? '#dc2626' : '#0284c7');
        const tag = n.tag || (n.type === 'important' ? 'Important Update' : 'Announcement');
        const posterHtml = n.poster ? `<div class="short-notif-modal-poster-wrap"><img src="${n.poster}" alt="Poster" class="short-notif-modal-poster" onerror="this.parentElement.style.display='none';"></div>` : '';
        
        let actionBtnHtml = '';
        if (n.link && n.link !== '#' && n.link !== window.location.href && n.link !== window.location.origin + '/' && n.link !== config.site_url && n.link !== config.site_url + '/') {
          actionBtnHtml = `<a href="${n.link}" class="short-custom-popup-btn-confirm" style="display:flex; align-items:center; justify-content:center; text-decoration:none; gap:6px;">
            <span>Open Destination</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
          </a>`;
        }

        overlay.innerHTML = `
          <div class="short-custom-popup-box short-notif-modal-box">
            <button type="button" class="short-custom-popup-close short-notif-popup-close" aria-label="Close">&times;</button>
            <div class="short-notif-modal-header-tag">
              <span class="notif-item-badge" style="background:${badgeColor}; font-size:11px; padding:3px 9px;">${tag}</span>
              <span class="short-notif-modal-time">${timeAgo(n.created_at)}</span>
            </div>
            ${posterHtml}
            <h2 class="short-notif-modal-title">${n.title}</h2>
            <div class="short-notif-modal-body">${n.message.replace(/\n/g, '<br>')}</div>
            <div class="short-custom-popup-actions" style="margin-top:22px; gap:10px;">
              ${actionBtnHtml}
              <button type="button" class="short-custom-popup-btn-confirm short-notif-modal-close-btn" style="${actionBtnHtml ? 'background:rgba(255,255,255,0.12); color:#fff; border:1px solid rgba(255,255,255,0.2);' : ''}">Got It</button>
            </div>
          </div>
        `;

        document.body.appendChild(overlay);

        requestAnimationFrame(function() {
          overlay.classList.add('is-visible');
        });

        function closeModal() {
          overlay.classList.remove('is-visible');
          setTimeout(function() {
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
          }, 240);
        }

        overlay.querySelector('.short-notif-modal-close-btn').addEventListener('click', closeModal);
        overlay.querySelector('.short-custom-popup-close').addEventListener('click', closeModal);
        overlay.addEventListener('click', function(e) {
          if (e.target === overlay) closeModal();
        });
        overlay.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') closeModal();
        });
      }

      function renderNotifications(notifs) {
        window._shortNotifications = notifs;
        const readIds = getReadNotifIds();

        // 1. Render Desktop Dropdown
        if (dropdownContainer) {
          if (!notifs.length) {
            dropdownContainer.innerHTML = '<div class="notif-empty">No new notifications</div>';
          } else {
            dropdownContainer.innerHTML = notifs.map(function(n) {
              const isRead = readIds.includes(n.id);
              const tag = n.tag || (n.type === 'important' ? 'Important Update' : 'New Release');
              const badgeColor = n.badge_color || (n.type === 'important' ? '#dc2626' : '#0284c7');
              const posterHtml = n.poster 
                ? '<img src="' + n.poster + '" alt="Poster" class="notif-item-img" onerror="this.style.display=\'none\';">'
                : '<div class="notif-item-img notif-item-img-placeholder"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></div>';

              const hasCustomOrNoLink = !n.link || n.link === '#' || n.link === window.location.href || n.link === window.location.origin + '/' || n.link === config.site_url || n.link === config.site_url + '/';

              return '<a href="' + (hasCustomOrNoLink ? 'javascript:void(0);' : n.link) + '" class="notif-item short-notif-item ' + (isRead ? 'is-read' : 'is-unread') + '" data-notif-id="' + n.id + '"' + (hasCustomOrNoLink ? ' data-open-modal="1"' : '') + '>' +
                posterHtml +
                '<div class="notif-item-content">' +
                  '<div class="notif-item-tag-row">' +
                    '<span class="notif-item-badge" style="background:' + badgeColor + ';">' + tag + '</span>' +
                    '<span class="notif-item-time">' + timeAgo(n.created_at) + '</span>' +
                  '</div>' +
                  '<div class="notif-item-title">' + n.title + '</div>' +
                  '<div class="notif-item-text">' + n.message + '</div>' +
                '</div>' +
                '<div class="notif-unread-dot"></div>' +
              '</a>';
            }).join('');
          }
        }

        // 2. Render Dedicated Notification Page
        if (pageContainer) {
          if (!notifs.length) {
            const emptyEl = document.getElementById('notif-page-empty-state');
            if (emptyEl) emptyEl.style.display = 'flex';
          } else {
            pageContainer.innerHTML = notifs.map(function(n) {
              const isRead = readIds.includes(n.id);
              const tag = n.tag || (n.type === 'important' ? 'Important Update' : 'New Release');
              const badgeColor = n.badge_color || (n.type === 'important' ? '#dc2626' : '#0284c7');
              const posterHtml = n.poster 
                ? '<img src="' + n.poster + '" alt="Poster" class="notif-page-card-poster" onerror="this.style.display=\'none\';">'
                : '<div class="notif-page-card-poster notif-page-poster-placeholder"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg></div>';

              const hasCustomOrNoLink = !n.link || n.link === '#' || n.link === window.location.href || n.link === window.location.origin + '/' || n.link === config.site_url || n.link === config.site_url + '/';

              return '<a href="' + (hasCustomOrNoLink ? 'javascript:void(0);' : n.link) + '" class="notif-page-item short-notif-item ' + (isRead ? 'is-read' : 'is-unread') + '" data-notif-id="' + n.id + '"' + (hasCustomOrNoLink ? ' data-open-modal="1"' : '') + '>' +
                posterHtml +
                '<div class="notif-page-item-info">' +
                  '<div class="notif-page-tag-row">' +
                    '<span class="notif-item-badge" style="background:' + badgeColor + ';">' + tag + '</span>' +
                    '<span class="notif-item-time">' + timeAgo(n.created_at) + '</span>' +
                  '</div>' +
                  '<h3 class="notif-page-item-title">' + n.title + '</h3>' +
                  '<p class="notif-page-item-desc">' + n.message + '</p>' +
                '</div>' +
                '<div class="notif-unread-dot"></div>' +
              '</a>';
            }).join('');
          }
        }

        // Attach click to mark as read and open full popup modal if requested
        document.querySelectorAll('.short-notif-item').forEach(function(el) {
          el.addEventListener('click', function(e) {
            const id = this.getAttribute('data-notif-id');
            if (id) markNotifRead(id);

            if (this.getAttribute('data-open-modal') === '1') {
              e.preventDefault();
              const found = (window._shortNotifications || []).find(function(item) { return item.id === id; });
              if (found) {
                openNotificationModal(found);
              }
            }
          });
        });

        updateBadgeAndReadState();
      }

      // 1. Instant local render from localized PHP configuration or window object
      const initialData = (config.notifications && config.notifications.length) 
        ? config.notifications 
        : (window._shortInitialNotifications && window._shortInitialNotifications.length ? window._shortInitialNotifications : []);
      if (initialData && initialData.length) {
        renderNotifications(initialData);
      }

      // 2. Safe background sync from REST API endpoint
      let apiUrl = config.rest_url || '';
      if (apiUrl) {
        if (apiUrl.indexOf('?') !== -1) {
          apiUrl = apiUrl + '&rest_route=/short/v1/notifications';
        } else {
          apiUrl = apiUrl.replace(/\/$/, '') + '/notifications';
        }
        fetch(apiUrl, {
          headers: { 'X-WP-Nonce': config.nonce || '' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data && data.success && Array.isArray(data.notifications)) {
            renderNotifications(data.notifications);
          }
        })
        .catch(function(err) {
          console.warn('[Notifications] Fetch background sync note:', err);
        });
      }
    }
    initNotificationsSystem();

    const profileTrigger = document.querySelector('#profile-avatar-trigger, .profile-avatar-trigger, .profile-trigger');
    const profileDropdown = document.querySelector('#profile-dropdown-menu, .profile-dropdown-menu');
    if (profileTrigger && profileDropdown && typeof window.toggleProfileDropdown !== 'function') {
      profileTrigger.addEventListener('click', function (e) {
        e.stopPropagation();
        profileDropdown.classList.toggle('show');
      });
      document.addEventListener('click', function () { profileDropdown.classList.remove('show'); });
    }

    // Sync Navigation Active State with current URL path
    function syncNavActiveState() {
      let currentPath = window.location.pathname.toLowerCase().replace(/^\/+|\/+$/g, '');
      const siteBase = (config.site_url ? new URL(config.site_url).pathname : '').toLowerCase().replace(/^\/+|\/+$/g, '');
      if (siteBase && (currentPath === siteBase || currentPath.startsWith(siteBase + '/'))) {
        currentPath = currentPath.substring(siteBase.length).replace(/^\/+/, '');
      }
      currentPath = currentPath.replace(/^index\.php\/?/i, '');

      const hash = (window.location.hash || '').toLowerCase();
      let activeSection = '';

      if (hash === '#leaderboard' || currentPath === 'leaderboard' || currentPath.startsWith('leaderboard') || currentPath.startsWith('ranking')) {
        activeSection = 'leaderboard';
      } else if (hash === '#reward' || hash === '#rewards' || currentPath === 'reward' || currentPath.startsWith('reward')) {
        activeSection = 'rewards';
      } else if (currentPath === '' || currentPath === 'index.php') {
        activeSection = 'home';
      } else if (currentPath.startsWith('series') || currentPath.startsWith('shows') || currentPath.startsWith('tv-shows') || currentPath.startsWith('tv')) {
        activeSection = 'series';
      } else if (currentPath.startsWith('movies') || currentPath.startsWith('movie')) {
        activeSection = 'movies';
      } else if (currentPath.startsWith('anime')) {
        activeSection = 'anime';
      } else if (currentPath.startsWith('new-popular')) {
        activeSection = (hash === '#leaderboard') ? 'leaderboard' : 'new-popular';
      } else if (currentPath.startsWith('my-list')) {
        activeSection = 'my-list';
      } else if (currentPath.startsWith('history')) {
        activeSection = 'history';
      } else if (currentPath.startsWith('search')) {
        activeSection = 'search';
      } else if (currentPath.startsWith('profile') || currentPath.startsWith('account') || currentPath.startsWith('settings')) {
        activeSection = 'account';
      } else if (currentPath.startsWith('genre') || currentPath.startsWith('genres') || currentPath.startsWith('categories') || currentPath.startsWith('discover')) {
        activeSection = 'categories';
      }

      function isNavMatch(navKey, targetSection, el) {
        if (!targetSection) return false;
        var href = (el ? el.getAttribute('href') : '') || '';
        var hrefPath = '';
        try {
          if (href) {
            hrefPath = new URL(href, window.location.origin).pathname.toLowerCase().replace(/^\/+|\/+$/g, '');
            if (siteBase && (hrefPath === siteBase || hrefPath.startsWith(siteBase + '/'))) {
              hrefPath = hrefPath.substring(siteBase.length).replace(/^\/+/, '');
            }
          }
        } catch(e){}

        if (navKey) {
          navKey = navKey.toLowerCase();
          if (navKey === targetSection) return true;
          if (targetSection === 'home' && (navKey === 'home' || navKey === 'mobile_home')) return true;
          if ((targetSection === 'categories' || targetSection === 'genre' || targetSection === 'discover') && (navKey === 'categories' || navKey === 'genre' || navKey === 'genres' || navKey === 'mobile_discovery' || navKey === 'discovery' || navKey === 'discover')) return true;
          if ((targetSection === 'leaderboard' || targetSection === 'ranking') && (navKey === 'leaderboard' || navKey === 'ranking' || navKey === 'rankings' || navKey === 'mobile_leaderboard')) return true;
          if ((targetSection === 'rewards' || targetSection === 'reward') && (navKey === 'rewards' || navKey === 'reward' || navKey === 'mobile_rewards')) return true;
          if ((targetSection === 'account' || targetSection === 'profile') && (navKey === 'account' || navKey === 'profile' || navKey === 'settings' || navKey === 'mobile_account')) return true;
          if (targetSection === 'new-popular' && (navKey === 'new-popular' || navKey === 'popular' || navKey === 'mobile_popular')) return true;
        }

        if (hrefPath && currentPath) {
          if (hrefPath === currentPath) return true;
          if (targetSection === 'categories' && (hrefPath === 'genre' || hrefPath.startsWith('genre/') || hrefPath === 'discover' || hrefPath === 'categories')) return true;
          if (targetSection === 'leaderboard' && (hrefPath === 'leaderboard' || hrefPath.startsWith('leaderboard/') || hrefPath === 'ranking')) return true;
        }
        return false;
      }

      // Desktop Nav
      document.querySelectorAll('.short-nav-desktop .nav-item, .reel-nav-desktop .nav-item').forEach(function(el) {
        if (isNavMatch(el.getAttribute('data-nav'), activeSection, el)) {
          el.classList.add('active');
        } else {
          el.classList.remove('active');
        }
      });

      // Mobile Bottom Nav
      document.querySelectorAll('.short-mobile-bottom-nav .mobile-nav-item').forEach(function(el) {
        if (isNavMatch(el.getAttribute('data-nav'), activeSection, el)) {
          el.classList.add('active');
        } else {
          el.classList.remove('active');
        }
      });
    }
    syncNavActiveState();
    window.addEventListener('hashchange', syncNavActiveState);

    // Expandable details page overview
    document.querySelectorAll('.details-overview').forEach(function (el) {
      if (el && el.textContent) {
        setExpandableOverview(el, el.textContent.trim(), 240);
      }
    });

    // Netflix Guest Landing & Onboarding Handlers
    function initNetflixLandingInteractions() {
      // FAQ Accordion Toggle
      const faqItems = document.querySelectorAll('.netflix-faq-accordion .faq-item');
      faqItems.forEach(function(item) {
        const btn = item.querySelector('.faq-question-btn');
        if (btn) {
          btn.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            faqItems.forEach(function(other) {
              if (other !== item) {
                other.classList.remove('active');
                const otherBtn = other.querySelector('.faq-question-btn');
                if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
              }
            });
            if (isActive) {
              item.classList.remove('active');
              btn.setAttribute('aria-expanded', 'false');
            } else {
              item.classList.add('active');
              btn.setAttribute('aria-expanded', 'true');
            }
          });
        }
      });

      // Top 10 Trending Carousel Arrows
      const top10Track = document.getElementById('top10-track');
      const top10Prev = document.getElementById('top10-arrow-prev');
      const top10Next = document.getElementById('top10-arrow-next');

      if (top10Track && top10Prev && top10Next) {
        top10Prev.addEventListener('click', function() {
          top10Track.scrollBy({ left: -600, behavior: 'smooth' });
        });
        top10Next.addEventListener('click', function() {
          top10Track.scrollBy({ left: 600, behavior: 'smooth' });
        });
      }

      // Netflix Multi-Language (i18n) Dictionary & Switcher
      const I18N_DICT = {
        en: {
          sign_in: 'Sign In',
          hero_title: 'All the hits you keep hearing about',
          hero_price: 'Starts at ₱169. Cancel anytime.',
          hero_cta: 'Ready to watch? Enter your email to create or restart your membership.',
          email_placeholder: 'Email address',
          get_started: 'Get Started',
          trending_now: 'Trending Now',
          watch_now: 'Watch Now',
          reasons_title: 'More Reasons to Join',
          card1_title: 'Enjoy on your TV',
          card1_desc: 'Watch on Smart TVs, Playstation, Xbox, Chromecast, Apple TV, Blu-ray players, and more.',
          card2_title: 'Download your shows to watch offline',
          card2_desc: 'Save your favorites easily and always have something to watch.',
          card3_title: 'Watch everywhere',
          card3_desc: 'Stream unlimited movies and TV shows on your phone, tablet, laptop, and TV.',
          card4_title: 'Create profiles for kids',
          card4_desc: 'Send kids on adventures with their favorite characters in a space made just for them — free with your membership.',
          faq_title: 'Frequently Asked Questions',
          faq1_q: 'What is Short?',
          faq1_a1: 'Short is a streaming service that offers a wide variety of award-winning TV shows, movies, anime, documentaries, and more on thousands of internet-connected devices.',
          faq1_a2: 'You can watch as much as you want, whenever you want without a single commercial – all for one low monthly price. There\'s always something new to discover and new TV shows and movies are added every week!',
          faq2_q: 'How much does Short cost?',
          faq2_a1: 'Watch Short on your smartphone, tablet, Smart TV, laptop, or streaming device, all for one fixed monthly fee. Plans range from ₱169 to ₱549 a month. No extra costs, no contracts.',
          faq3_q: 'Where can I watch?',
          faq3_a1: 'Watch anywhere, anytime. Sign in with your Short account to watch instantly on the web from your personal computer or on any internet-connected device that offers the app, including smart TVs, smartphones, tablets, streaming media players and game consoles.',
          faq3_a2: 'You can also download your favorite shows with the iOS, Android, or Windows 10 app. Use downloads to watch while you\'re on the go and without an internet connection. Take Short with you anywhere.',
          faq4_q: 'How do I cancel?',
          faq4_a1: 'Short is flexible. There are no pesky contracts and no commitments. You can easily cancel your account online in two clicks. There are no cancellation fees – start or stop your account anytime.',
          faq5_q: 'What can I watch on Short?',
          faq5_a1: 'Short has an extensive library of feature films, documentaries, TV shows, anime, award-winning originals, and more. Watch as much as you want, anytime you want.',
          faq6_q: 'Is Short good for kids?',
          faq6_a1: 'The Short Kids experience is included in your membership to give parents control while kids enjoy family-friendly TV shows and movies in their own space.',
          faq6_a2: 'Kids profiles come with PIN-protected parental controls that let you restrict the maturity rating of content kids can watch and block specific titles you don’t want kids to see.'
        },
        fil: {
          sign_in: 'Mag-sign In',
          hero_title: 'Lahat ng trending na palabas na pinag-uusapan',
          hero_price: 'Nagsisimula sa ₱169. Kanselahin kahit kailan.',
          hero_cta: 'Handa nang manood? Ilagay ang iyong email para magsimula o i-restart ang membership.',
          email_placeholder: 'Email address',
          get_started: 'Magsimula',
          trending_now: 'Trending Ngayon',
          watch_now: 'Panoorin Ngayon',
          reasons_title: 'Iba pang Dahilan para Sumali',
          card1_title: 'I-enjoy sa iyong TV',
          card1_desc: 'Manood sa Smart TV, Playstation, Xbox, Chromecast, Apple TV, Blu-ray player, at iba pa.',
          card2_title: 'I-download ang mga palabas para panoorin offline',
          card2_desc: 'Madaling i-save ang iyong mga paborito para laging may mapapanood.',
          card3_title: 'Manood kahit saan',
          card3_desc: 'Mag-stream ng unli movies at TV shows sa iyong phone, tablet, laptop, at TV.',
          card4_title: 'Gumawa ng profiles para sa mga bata',
          card4_desc: 'Pasayahin ang mga bata kasama ang kanilang paboritong characters sa espasyong para sa kanila — libre sa iyong membership.',
          faq_title: 'Mga Madalas Itanong',
          faq1_q: 'Ano ang Short?',
          faq1_a1: 'Ang Short ay isang streaming service na nag-aalok ng sari-saring award-winning TV shows, movies, anime, documentaries, at marami pang iba sa libo-libong internet-connected devices.',
          faq1_a2: 'Maaari kang manood kahit gaano karami, kahit kailan nang walang commercial – lahat para sa isang mababang buwanang presyo. Laging may bagong matutuklasan linggo-linggo!',
          faq2_q: 'Magkano ang Short?',
          faq2_a1: 'Panoorin ang Short sa smartphone, tablet, Smart TV, laptop, o streaming device para sa isang fixed monthly fee. Ang mga plano ay mula ₱169 hanggang ₱549 kada buwan.',
          faq3_q: 'Saan ako puwedeng manood?',
          faq3_a1: 'Manood kahit saan, kahit kailan. Mag-sign in gamit ang iyong Short account para manood agad sa web mula sa computer o anumang device na may Short app.',
          faq3_a2: 'Maaari mo ring i-download ang mga paboritong palabas gamit ang app sa iOS, Android, o Windows 10 para manood offline habang nagbi-biyahe.',
          faq4_q: 'Paano ako magkakansela?',
          faq4_a1: 'Flexible ang Short. Walang kontrata at walang commitment. Madaling magkansela online sa dalawang click lang nang walang cancellation fee.',
          faq5_q: 'Ano ang mapapanood ko sa Short?',
          faq5_a1: 'May malawak na koleksyon ng pelikula, dokumentaryo, serye, anime, at award-winning originals ang Short.',
          faq6_q: 'Maganda ba ang Short para sa mga bata?',
          faq6_a1: 'Kasama ang Short Kids experience sa membership para magkaroon ng kontrol ang mga magulang habang nage-enjoy ang mga bata sa pambatang palabas.',
          faq6_a2: 'May PIN-protected parental controls ang Kids profiles para ma-restrict ang maturity rating ng mga palabas.'
        }
      };

      function switchLanguage(lang) {
        const dict = I18N_DICT[lang] || I18N_DICT.en;
        document.querySelectorAll('[data-i18n]').forEach(function(el) {
          const key = el.getAttribute('data-i18n');
          if (dict[key]) {
            el.textContent = dict[key];
          }
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(function(el) {
          const key = el.getAttribute('data-i18n-placeholder');
          if (dict[key]) {
            el.placeholder = dict[key];
          }
        });

        // Update all language dropdown labels & active states across page
        document.querySelectorAll('.lang-current-label').forEach(function(lbl) {
          lbl.textContent = lang === 'fil' ? 'Filipino' : 'English';
        });
        document.querySelectorAll('.lang-option').forEach(function(opt) {
          if (opt.getAttribute('data-value') === lang) {
            opt.classList.add('active');
          } else {
            opt.classList.remove('active');
          }
        });

        try {
          localStorage.setItem('short_language', lang);
        } catch(e) {}
      }

      // Initialize saved language or default to English
      const savedLang = (function() {
        try { return localStorage.getItem('short_language') || 'en'; } catch(e) { return 'en'; }
      })();

      if (savedLang !== 'en') {
        switchLanguage(savedLang);
      }

      // Language Dropdown Toggle & Selection
      const langDropdown = document.getElementById('netflix-lang-dropdown');
      const langTrigger = document.getElementById('netflix-lang-trigger');
      
      if (langDropdown && langTrigger) {
        langTrigger.addEventListener('click', function(e) {
          e.stopPropagation();
          const isOpen = langDropdown.classList.contains('open');
          // Close other open dropdowns if any
          document.querySelectorAll('.netflix-custom-lang-dropdown').forEach(d => d.classList.remove('open'));
          if (!isOpen) {
            langDropdown.classList.add('open');
            langTrigger.setAttribute('aria-expanded', 'true');
          } else {
            langDropdown.classList.remove('open');
            langTrigger.setAttribute('aria-expanded', 'false');
          }
        });

        langDropdown.querySelectorAll('.lang-option').forEach(function(opt) {
          opt.addEventListener('click', function(e) {
            e.stopPropagation();
            const val = opt.getAttribute('data-value');
            switchLanguage(val);
            langDropdown.classList.remove('open');
            langTrigger.setAttribute('aria-expanded', 'false');
          });
        });

        document.addEventListener('click', function() {
          langDropdown.classList.remove('open');
          langTrigger.setAttribute('aria-expanded', 'false');
        });
      }

      // Auto populate email input if email query param exists
      const urlParams = new URLSearchParams(window.location.search);
      const emailParam = urlParams.get('email');
      if (emailParam) {
        const loginEmail = document.getElementById('login-email');
        const regEmail = document.getElementById('reg-email');
        if (loginEmail && !loginEmail.value) loginEmail.value = emailParam;
        if (regEmail && !regEmail.value) regEmail.value = emailParam;
      }
    }
    initNetflixLandingInteractions();

    // ── Global Theme Accent Color Customizer ────────────────────────
    function initThemeColorCustomizer() {
      const STORAGE_KEY = 'short_theme_color';
      const DEFAULT_COLOR = '#E50914';

      function applyThemeColor(hexColor) {
        if (!hexColor || !hexColor.startsWith('#')) return;

        let cleanHex = hexColor.replace('#', '');
        if (cleanHex.length === 3) {
          cleanHex = cleanHex[0]+cleanHex[0] + cleanHex[1]+cleanHex[1] + cleanHex[2]+cleanHex[2];
        }
        if (cleanHex.length !== 6) return;

        const r = parseInt(cleanHex.substring(0, 2), 16);
        const g = parseInt(cleanHex.substring(2, 4), 16);
        const b = parseInt(cleanHex.substring(4, 6), 16);
        const rgbStr = `${r}, ${g}, ${b}`;

        document.documentElement.style.setProperty('--accent', hexColor);
        document.documentElement.style.setProperty('--theme-accent', hexColor);
        document.documentElement.style.setProperty('--btn-primary-bg', hexColor);
        document.documentElement.style.setProperty('--player-accent', hexColor);
        document.documentElement.style.setProperty('--theme-accent-rgb', rgbStr);
        document.documentElement.style.setProperty('--accent-rgb', rgbStr);
        document.documentElement.style.setProperty('--theme-accent-glow', `rgba(${rgbStr}, 0.45)`);

        try {
          localStorage.setItem(STORAGE_KEY, hexColor);
        } catch(e) {}

        // Update Account page live preview and active buttons if present
        const swatches = document.querySelectorAll('.color-swatch-btn');
        swatches.forEach(swatch => {
          const swatchColor = swatch.getAttribute('data-color');
          if (swatchColor && swatchColor.toLowerCase() === hexColor.toLowerCase()) {
            swatch.classList.add('is-active');
          } else {
            swatch.classList.remove('is-active');
          }
        });

        const customPicker = document.getElementById('theme-custom-picker');
        const hexInput = document.getElementById('theme-hex-input');
        if (customPicker) customPicker.value = hexColor;
        if (hexInput) hexInput.value = hexColor.toUpperCase();

        const previewProg = document.getElementById('mini-preview-progress');
        const previewKnob = document.getElementById('mini-preview-knob');
        const previewBadge = document.getElementById('mini-preview-badge');
        const previewBtn = document.getElementById('mini-preview-button');
        if (previewProg) previewProg.style.backgroundColor = hexColor;
        if (previewKnob) {
          previewKnob.style.borderColor = hexColor;
          previewKnob.style.boxShadow = `0 0 6px ${hexColor}`;
        }
        if (previewBadge) {
          previewBadge.style.color = hexColor;
          previewBadge.style.backgroundColor = `rgba(${rgbStr}, 0.2)`;
        }
        if (previewBtn) {
          previewBtn.style.backgroundColor = hexColor;
        }

        // Show toast briefly
        const toast = document.getElementById('theme-saved-toast');
        if (toast) {
          toast.classList.add('show');
          clearTimeout(toast._timeout);
          toast._timeout = setTimeout(() => {
            toast.classList.remove('show');
          }, 2000);
        }
      }
      window.applyShortThemeColor = applyThemeColor;

      // Initial apply on load
      let currentColor = DEFAULT_COLOR;
      try {
        currentColor = localStorage.getItem(STORAGE_KEY) || DEFAULT_COLOR;
      } catch(e) {}
      applyThemeColor(currentColor);

      // Bind swatch buttons
      const palette = document.getElementById('theme-color-palette');
      if (palette) {
        palette.addEventListener('click', (e) => {
          const btn = e.target.closest('.color-swatch-btn');
          if (btn) {
            const color = btn.getAttribute('data-color');
            if (color) applyThemeColor(color);
          }
        });
      }

      // Bind Native Picker
      const customPicker = document.getElementById('theme-custom-picker');
      if (customPicker) {
        customPicker.addEventListener('input', (e) => {
          applyThemeColor(e.target.value);
        });
      }

      // Bind Hex text input
      const hexInput = document.getElementById('theme-hex-input');
      if (hexInput) {
        hexInput.addEventListener('change', (e) => {
          let val = e.target.value.trim();
          if (!val.startsWith('#')) val = '#' + val;
          if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
            applyThemeColor(val);
          }
        });
      }

      // Bind Reset Button
      const resetBtn = document.getElementById('btn-reset-theme-color');
      if (resetBtn) {
        resetBtn.addEventListener('click', () => {
          applyThemeColor(DEFAULT_COLOR);
        });
      }
    }
    initThemeColorCustomizer();

    // ── Account Preferences & Custom Dropdown ─────────────────────────
    function initAccountPreferences() {
      const dropdownWrap = document.getElementById('account-audio-custom-dropdown');
      const dropdownBtn = document.getElementById('account-audio-dropdown-btn');
      const selectedText = document.getElementById('account-audio-selected-text');
      const hiddenInput = document.getElementById('setting-preferred-audio');
      const optionsMenu = document.getElementById('account-audio-menu');
      const autoplayToggle = document.getElementById('setting-autoplay-next');
      const autoSkipToggle = document.getElementById('setting-auto-skip');

      if (dropdownWrap && dropdownBtn && optionsMenu) {
        // Toggle dropdown open/close
        dropdownBtn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          const isOpen = dropdownWrap.classList.contains('is-open');
          if (isOpen) {
            dropdownWrap.classList.remove('is-open');
            dropdownBtn.setAttribute('aria-expanded', 'false');
          } else {
            dropdownWrap.classList.add('is-open');
            dropdownBtn.setAttribute('aria-expanded', 'true');
          }
        });

        // Option selection
        const options = optionsMenu.querySelectorAll('.account-dropdown-opt');
        options.forEach(function (opt) {
          opt.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const val = opt.getAttribute('data-value');
            const labelSpan = opt.querySelector('span');
            const label = labelSpan ? labelSpan.textContent : val;

            options.forEach(function (o) {
              o.classList.remove('is-selected');
              o.setAttribute('aria-selected', 'false');
            });
            opt.classList.add('is-selected');
            opt.setAttribute('aria-selected', 'true');

            if (selectedText) selectedText.textContent = label;
            if (hiddenInput) {
              hiddenInput.value = val;
              hiddenInput.dispatchEvent(new Event('change'));
            }

            try {
              localStorage.setItem('short_preferred_audio', val);
            } catch (err) {}

            dropdownWrap.classList.remove('is-open');
            dropdownBtn.setAttribute('aria-expanded', 'false');
          });
        });

        // Close on outside click
        document.addEventListener('click', function (e) {
          if (!dropdownWrap.contains(e.target)) {
            dropdownWrap.classList.remove('is-open');
            dropdownBtn.setAttribute('aria-expanded', 'false');
          }
        });

        // Close on escape key
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && dropdownWrap.classList.contains('is-open')) {
            dropdownWrap.classList.remove('is-open');
            dropdownBtn.setAttribute('aria-expanded', 'false');
          }
        });

        // Load saved preference
        try {
          const savedPref = localStorage.getItem('short_preferred_audio');
          if (savedPref) {
            const matchingOpt = optionsMenu.querySelector('.account-dropdown-opt[data-value="' + savedPref + '"]');
            if (matchingOpt) {
              options.forEach(function (o) {
                o.classList.remove('is-selected');
                o.setAttribute('aria-selected', 'false');
              });
              matchingOpt.classList.add('is-selected');
              matchingOpt.setAttribute('aria-selected', 'true');
              const labelSpan = matchingOpt.querySelector('span');
              if (selectedText && labelSpan) selectedText.textContent = labelSpan.textContent;
              if (hiddenInput) hiddenInput.value = savedPref;
            }
          }
        } catch (err) {}
      }

      // Sync Autoplay Next toggle
      if (autoplayToggle) {
        try {
          const savedAutoplay = localStorage.getItem('short_autoplay_next');
          if (savedAutoplay !== null) {
            autoplayToggle.checked = (savedAutoplay === 'true');
          }
          autoplayToggle.addEventListener('change', function () {
            localStorage.setItem('short_autoplay_next', autoplayToggle.checked);
          });
        } catch (err) {}
      }

      // Sync Auto Skip Intro toggle
      if (autoSkipToggle) {
        try {
          const savedAutoSkip = localStorage.getItem('short_auto_skip');
          if (savedAutoSkip !== null) {
            autoSkipToggle.checked = (savedAutoSkip === 'true');
          }
          autoSkipToggle.addEventListener('change', function () {
            localStorage.setItem('short_auto_skip', autoSkipToggle.checked);
          });
        } catch (err) {}
      }
    }
    initAccountPreferences();

    // ── Account & Active Profile Sync ────────────────────────────────
    function initAccountSync() {
      try {
        const storedEmail = (currentUser && currentUser.email) ? currentUser.email : (localStorage.getItem('short_user_email') || '');
        const activeProfileStr = localStorage.getItem('short_active_profile');
        const activeProfileName = localStorage.getItem('short_active_profile_name');
        const activeAvatar = localStorage.getItem('short_active_avatar');

        let profile = null;
        if (activeProfileStr) {
          try { profile = JSON.parse(activeProfileStr); } catch (e) {}
        }

        const viewEmail = document.getElementById('account-view-email');
        const viewName = document.getElementById('account-view-name');
        const viewAvatar = document.getElementById('account-view-avatar');

        if (viewEmail) {
          if (storedEmail) {
            viewEmail.textContent = storedEmail;
          } else {
            viewEmail.textContent = 'Not signed in (Demo / Guest Mode)';
          }
        }

        const nameToShow = (profile && profile.name) || activeProfileName || (currentUser && (currentUser.displayName || (currentUser.email ? currentUser.email.split('@')[0] : ''))) || (storedEmail ? storedEmail.split('@')[0] : '');
        if (viewName) {
          viewName.textContent = nameToShow || 'Guest Viewer';
        }

        const authTag = document.getElementById('account-auth-tag');
        const guestBanner = document.getElementById('account-guest-banner');
        const signinBtn = document.getElementById('account-signin-btn');
        const sinceEl = document.getElementById('account-since-note');

        if (storedEmail || (currentUser && currentUser.email)) {
          if (authTag) authTag.style.display = 'inline-block';
          if (guestBanner) guestBanner.style.display = 'none';
          if (signinBtn) signinBtn.style.display = 'none';
          if (sinceEl) sinceEl.textContent = 'Google Verified Account';
        } else {
          if (authTag) authTag.style.display = 'none';
          if (guestBanner) guestBanner.style.display = 'flex';
          if (signinBtn) signinBtn.style.display = 'inline-flex';
        }

        const avatarToShow = (profile && profile.avatar) || activeAvatar;
        if (viewAvatar && avatarToShow) {
          viewAvatar.src = resolveAvatarUrl(avatarToShow);
        }

        if (typeof window.syncHeaderAuthState === 'function') {
          window.syncHeaderAuthState();
        }

        // Bind Dedicated Sign Out button on Account page
        const signoutBtn = document.getElementById('account-signout-btn');
        if (signoutBtn && !signoutBtn.dataset.bound) {
          signoutBtn.dataset.bound = '1';
          signoutBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            if (auth) {
              try { await auth.signOut(); } catch (err) { console.warn('[SignOut]', err); }
            }
            localStorage.removeItem('short_is_logged_in');
            localStorage.removeItem('short_active_profile_id');
            localStorage.removeItem('short_active_avatar');
            localStorage.removeItem('short_active_profile');
            localStorage.removeItem('short_active_profile_name');
            localStorage.removeItem('short_user_email');
            localStorage.removeItem('short_user_display_name');
            localStorage.removeItem('short_user_uid');
            document.documentElement.classList.add('is-guest-state');
            window.location.href = (config.site_url || '') + '/login/';
          });
        }
      } catch (e) {
        console.warn('[AccountSync]', e);
      }
    }
    window.SHORT.initAccountSync = initAccountSync;
    initAccountSync();

    // ── Netflix-Style Mobile Splash Screen Controller ────────────────────
    function initMobileSplash() {
      const splashEl = document.getElementById('short-mobile-splash');
      if (!splashEl) return;

      // If splash is already active via header.php inline runner, guarantee fallback dismissal
      if (splashEl.style.display !== 'none') {
        const dismissSplash = function () {
          splashEl.classList.add('fade-out');
          setTimeout(function () {
            splashEl.style.display = 'none';
            document.body.classList.remove('splash-active');
          }, 450);
        };
        splashEl.addEventListener('click', dismissSplash, { once: true });
        splashEl.addEventListener('touchstart', dismissSplash, { passive: true, once: true });
        setTimeout(dismissSplash, 3500);
      }
    }
    initMobileSplash();

    // ── Header Scroll Blur & Dark Solid Background Controller ───────────
    function initHeaderScrollEffect() {
      const header = document.getElementById('short-header') || document.querySelector('.reel-header') || document.querySelector('.short-header') || document.querySelector('.site-header');
      if (!header) return;

      const handleScroll = function () {
        const top = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        if (top > 15) {
          if (!header.classList.contains('scrolled')) header.classList.add('scrolled');
          if (!document.body.classList.contains('scrolled')) document.body.classList.add('scrolled');
        } else {
          if (header.classList.contains('scrolled')) header.classList.remove('scrolled');
          if (document.body.classList.contains('scrolled')) document.body.classList.remove('scrolled');
        }
      };

      window.addEventListener('scroll', handleScroll, { passive: true });
      document.addEventListener('scroll', handleScroll, { passive: true });
      window.addEventListener('touchmove', handleScroll, { passive: true });
      handleScroll();
    }
    initHeaderScrollEffect();

  });
})();
