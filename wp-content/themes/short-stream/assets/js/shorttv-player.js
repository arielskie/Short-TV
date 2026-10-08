/**
 * ReelShort / ShortTV Master 9:16 Vertical Video Engine (Pure Vanilla JS)
 * Supports HLS (.m3u8), MP4 fallback, 6-col grid episode switcher, active equalizer soundwaves, and paywalls.
 */

(function() {
  'use strict';

  window.SHORTTV = window.SHORTTV || {};

  class ShortTVPlayer {
    constructor(containerSelector, seriesData, initialEpisode = 1) {
      this.container = typeof containerSelector === 'string' ? document.querySelector(containerSelector) : containerSelector;
      if (!this.container) {
        this.container = document.body;
      }
      this.series = seriesData || {};
      this.currentEpisodeNum = parseInt(initialEpisode, 10) || 1;
      this.hls = null;
      this.video = this.container.querySelector('#shorttv-video');
      this.isScrubbing = false;
      this.userCoins = parseInt(localStorage.getItem('shorttv_user_coins') || '100', 10);
      this.unlockedEpisodes = JSON.parse(localStorage.getItem('shorttv_unlocked_' + (this.series.media_id || 'default')) || '[]');
      this.isVip = this.checkVip();

      this.init();
    }

    init() {
      this.bindEvents();
      this.renderEpisodesGrid();
      this.loadEpisode(this.currentEpisodeNum);
    }

    getEpisode(epNum) {
      const eps = this.series.episodes && this.series.episodes.length ? this.series.episodes : [];
      if (!eps.length) {
        return {
          episode_number: epNum || 1,
          title: 'Episode ' + (epNum || 1),
          overview: this.series.overview || '',
          duration_seconds: 120,
          access_control: { unlock_type: 'free', coin_cost: 0, is_unlocked: true },
          sources: {
            aspect_ratio: '9:16',
            hls_stream_url: '',
            fallback_mp4: ''
          }
        };
      }
      const found = eps.find(e => parseInt(e.episode_number, 10) === parseInt(epNum, 10));
      return found || eps[0];
    }

    checkVip() {
      const tier = localStorage.getItem('short_sub_tier') || (document.cookie.match(/short_sub_tier=([^;]+)/) ? decodeURIComponent(document.cookie.match(/short_sub_tier=([^;]+)/)[1]) : '');
      const validTiers = ['basic', 'standard', 'premium', 'weekly', 'monthly', 'annual', 'vip'];
      if (!validTiers.includes((tier || '').toLowerCase())) return false;

      // Verify expiration timestamp if present in subscription object
      try {
        const sub = JSON.parse(localStorage.getItem('short_subscription') || '{}');
        if (sub.expires_at && Date.now() > sub.expires_at) {
          localStorage.removeItem('short_sub_tier');
          localStorage.removeItem('short_subscription');
          return false;
        }
      } catch(e) {}
      return true;
    }

    getFirstLockedEpisodeNumber() {
      const eps = this.series.episodes && this.series.episodes.length ? this.series.episodes : [];
      for (let i = 0; i < eps.length; i++) {
        const ep = eps[i];
        const num = parseInt(ep.episode_number || (i + 1), 10);
        if (!this.isEpisodeUnlocked(ep, false)) {
          return num;
        }
      }
      return null;
    }

    isEpisodeUnlocked(ep, checkOrder = false) {
      if (this.checkVip()) return true;
      const epNum = parseInt(ep.episode_number, 10);
      const ac = ep.access_control || {};
      const unlockType = (ac.unlock_type || 'free').toLowerCase();

      // VIP episode: ONLY unlocked if user has active VIP! (Cannot be unlocked by coin purchases or ad unlocks)
      if (unlockType === 'vip') {
        return ac.is_unlocked === true;
      }

      // Check if unlocked locally in storage dynamically for coins/ads
      const storageKey = 'shorttv_unlocked_' + (this.series.media_id || 'default');
      let unlockedEps = [];
      try {
        unlockedEps = JSON.parse(localStorage.getItem(storageKey) || '[]');
      } catch(e) {}
      if (Array.isArray(unlockedEps) && unlockedEps.some(x => parseInt(x, 10) === epNum)) {
        return true;
      }

      // If the admin explicitly set coins/premium, always enforce it (no free grace period)
      if (unlockType === 'coins' || unlockType === 'premium') {
        return ac.is_unlocked === true;
      }

      // Free by admin setting
      if (unlockType === 'free' || ac.is_unlocked === true) return true;

      // Grace: first 10 episodes free if no explicit lock
      if (epNum <= 10) return true;

      return false;
    }

    loadEpisode(epNum) {
      const eps = this.series.episodes && this.series.episodes.length ? this.series.episodes : [];
      const ep = this.getEpisode(epNum);
      this.currentEpisodeNum = parseInt(ep.episode_number, 10);
      const totalUploaded = eps.length || 1;

      // 1. Update Right-Panel Details
      const seriesTitle = this.series.title || 'Drama';
      const epCrumb = document.getElementById('reel-crumb-ep');
      if (epCrumb) epCrumb.textContent = 'Episode ' + this.currentEpisodeNum;

      const mainTitle = document.getElementById('reel-watch-main-title');
      if (mainTitle) mainTitle.textContent = 'Episode ' + this.currentEpisodeNum + ' - ' + seriesTitle;

      const plotHead = document.getElementById('reel-plot-heading');
      if (plotHead) plotHead.textContent = 'Synopsis';
      
      const plotText = ep.overview || this.series.overview || 'Owen revealed he was taking part in the Mooncity selections in this thrilling drama episode.';
      const plotBody = document.getElementById('reel-plot-text');
      if (plotBody) {
        plotBody.textContent = plotText;
        if (typeof checkSynopsisOverflow === 'function') {
          setTimeout(checkSynopsisOverflow, 50);
        }
      }

      const rangeLabel = document.getElementById('reel-ep-range-label');
      if (rangeLabel) {
        rangeLabel.textContent = totalUploaded + (totalUploaded === 1 ? ' Episode' : ' Episodes');
      }

      // Update URL query state without page refresh
      try {
        const url = new URL(window.location.href);
        url.searchParams.set('episode', this.currentEpisodeNum);
        window.history.replaceState({ episode: this.currentEpisodeNum }, '', url.toString());
      } catch (e) {}

      // Update Top Bar Label
      const topEpLabel = document.getElementById('player-ep-top-label');
      if (topEpLabel) {
        topEpLabel.textContent = 'EP.' + this.currentEpisodeNum;
      }

      // 2. Update Grid Active States & Equalizer Animation
      const allBtns = document.querySelectorAll('.reel-ep-btn');
      allBtns.forEach(btn => {
        btn.classList.remove('is-active');
        const eq = btn.querySelector('.reel-ep-equalizer');
        if (eq) eq.remove();
      });

      const activeBtn = document.querySelector(`.reel-ep-btn[data-ep="${this.currentEpisodeNum}"]`) || document.querySelector('.reel-ep-btn');
      if (activeBtn) {
        activeBtn.classList.add('is-active');
        const eqDiv = document.createElement('div');
        eqDiv.className = 'reel-ep-equalizer';
        eqDiv.innerHTML = '<span></span><span></span><span></span>';
        activeBtn.appendChild(eqDiv);
      }

      // Update mobile bottom sheet chips if present
      const allChips = document.querySelectorAll('.ep-chip');
      allChips.forEach(chip => {
        chip.classList.remove('ep-chip-active');
        chip.style.borderColor = 'transparent';
        chip.style.color = '#aaa';
        const eq = chip.querySelector('.ep-chip-equalizer');
        if (eq) eq.remove();
      });
      const activeChip = document.querySelector(`.ep-chip[data-ep="${this.currentEpisodeNum}"]`);
      if (activeChip) {
        activeChip.classList.add('ep-chip-active');
        activeChip.style.borderColor = '#7c4dff';
        activeChip.style.color = '#fff';
        let eq = activeChip.querySelector('.ep-chip-equalizer');
        if (!eq) {
          eq = document.createElement('div');
          eq.className = 'ep-chip-equalizer';
          eq.style.cssText = 'position:absolute;bottom:4px;left:50%;transform:translateX(-50%);display:flex;gap:1.5px;align-items:flex-end;height:8px;';
          eq.innerHTML = '<div style="width:2px;height:4px;background:#7c4dff;border-radius:1px;"></div><div style="width:2px;height:7px;background:#7c4dff;border-radius:1px;"></div><div style="width:2px;height:5px;background:#7c4dff;border-radius:1px;"></div>';
          activeChip.appendChild(eq);
        }
      }

      // 3. Check Sequential Paywall
      const firstLockedNum = this.getFirstLockedEpisodeNumber();
      const isUnlocked = this.isEpisodeUnlocked(ep);
      const paywall = document.getElementById('shorttv-paywall-modal');

      // If user jumped ahead past earlier locked episodes, redirect them to the first locked episode!
      if (!isUnlocked && firstLockedNum !== null && firstLockedNum < this.currentEpisodeNum) {
        this.loadEpisode(firstLockedNum);
        return;
      }

      if (!isUnlocked) {
        if (this.video) {
          this.video.pause();
          this.video.removeAttribute('src');
          this.video.load();
        }
        if (this.hls) {
          this.hls.destroy();
          this.hls = null;
        }
        const cost = ep.access_control ? (ep.access_control.coin_cost || 15) : 15;
        const unlockType = ep.access_control ? (ep.access_control.unlock_type || 'coins').toLowerCase() : 'coins';
        if (typeof paywallOpen === 'function') {
          paywallOpen(cost, this.currentEpisodeNum, unlockType);
        } else {
          const costEl = document.getElementById('paywall-coin-cost');
          if (costEl) costEl.textContent = cost;
          const costInline = document.getElementById('paywall-cost-inline');
          if (costInline) costInline.textContent = cost;
        }
        if (paywall) {
          paywall.style.display = 'flex';
          paywall.classList.add('is-active');
        }
        const loader = document.getElementById('shorttv-video-loader');
        if (loader) loader.style.display = 'none';
        return;
      }

      if (paywall) {
        paywall.style.display = 'none';
        paywall.classList.remove('is-active');
      }

      // If episode is unlocked (e.g. VIP or previously purchased) but stream URL was stripped server-side, fetch it from the secure API
      if (isUnlocked && (!ep.sources || (!ep.sources.hls_stream_url && !ep.sources.fallback_mp4))) {
        const loader = document.getElementById('shorttv-video-loader');
        if (loader) loader.style.display = 'block';
        const restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
        const unlockUrl = restBase + (restBase.endsWith('/') ? '' : '/') + 'shorttv/v1/series/unlock-episode';
        const self = this;
        fetch(unlockUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            series_id: this.series.post_id || this.series.media_id || 197,
            episode_number: this.currentEpisodeNum,
            method: 'verify'
          })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data && data.sources) {
            ep.sources = data.sources;
            self.loadEpisode(self.currentEpisodeNum);
          } else {
            if (loader) loader.style.display = 'none';
          }
        })
        .catch(function(err) {
          console.warn('Fetch stream error:', err);
          if (loader) loader.style.display = 'none';
        });
        return;
      }

      // 4. Show Buffering Loader
      const loader = document.getElementById('shorttv-video-loader');
      const posterBg = document.getElementById('shorttv-poster-bg');
      if (loader) loader.style.display = 'block';
      if (posterBg) {
        posterBg.style.opacity = '1';
        posterBg.style.display = 'block';
      }

      // 5. Play Video Stream (Clean & Normalize URL)
      const normalizeUrl = function(url) {
        if (!url) return '';
        url = url.trim();
        // Cloudflare Stream Normalization
        if (url.indexOf('videodelivery.net') !== -1 || url.indexOf('cloudflarestream.com') !== -1) {
          const cfMatch = url.match(/(?:videodelivery\.net|cloudflarestream\.com)\/([a-zA-Z0-9_-]+)/);
          if (cfMatch && cfMatch[1] && url.indexOf('.m3u8') === -1 && url.indexOf('.mp4') === -1) {
            return 'https://videodelivery.net/' + cfMatch[1] + '/manifest/video.m3u8';
          }
        }
        // Gumlet Normalization
        if (url.indexOf('video.gumlet.io') !== -1) {
          return url.replace(/\/1080p\/manifest\.m3u8|\/manifest\.m3u8|\/download\.mp4/g, '/main.m3u8');
        }
        return url;
      };

      let hlsUrl = normalizeUrl(ep.sources && ep.sources.hls_stream_url ? ep.sources.hls_stream_url : '');
      let mp4Url = normalizeUrl(ep.sources && ep.sources.fallback_mp4 ? ep.sources.fallback_mp4 : hlsUrl);
      const streamUrl = hlsUrl || mp4Url;

      if (!streamUrl || !this.video) {
        if (loader) loader.style.display = 'none';
        return;
      }

      // Cleanup previous HLS instance
      if (this.hls) {
        this.hls.destroy();
        this.hls = null;
      }

      const self = this;
      const hidePoster = function() {
        const posterBg = document.getElementById('shorttv-poster-bg');
        const loader = document.getElementById('shorttv-video-loader');
        if (posterBg) { posterBg.style.opacity = '0'; }
        if (loader) loader.style.display = 'none';
      };

      const safePlay = function() {
        if (!self.video) return;
        self.video.volume = 1.0;
        const playPromise = self.video.play();
        if (playPromise !== undefined) {
          playPromise.then(function() {
            hidePoster();
            if (typeof updateVolumeUI === 'function') {
              updateVolumeUI(self.video.muted, self.video.volume);
            }
            setTimeout(function() {
              self.prefetchNextEpisode();
            }, 1800);
          }).catch(function(err) {
            // If browser blocks unmuted autoplay, retry with muted=true so video still plays, then show Unmute banner
            if (err.name === 'NotAllowedError') {
              self.video.muted = true;
              self.video.play().then(function() {
                hidePoster();
                if (typeof updateVolumeUI === 'function') {
                  updateVolumeUI(true, 1.0);
                }
                setTimeout(function() {
                  self.prefetchNextEpisode();
                }, 1800);
              }).catch(function(e) {
                console.warn('Playback error:', e);
              });
            } else if (err.name !== 'AbortError') {
              console.warn('Autoplay note:', err);
            }
          });
        }
      };

      // Also hide poster on 'playing' event (covers muted autoplay + user-initiated play)
      if (self.video) {
        self.video.addEventListener('playing', hidePoster, { once: true });
      }

      if (streamUrl.indexOf('.m3u8') !== -1 && window.Hls && Hls.isSupported()) {
        this.hls = new Hls({
          enableWorker: true,
          lowLatencyMode: true,
          startFragPrefetch: true,
          capLevelToPlayerSize: false,
          maxBufferLength: 12,
          maxMaxBufferLength: 24,
          maxBufferSize: 25 * 1000 * 1000,
          backBufferLength: 0,
          startPosition: 0,
          progressive: true
        });

        this.hls.loadSource(streamUrl);
        this.hls.attachMedia(this.video);

        this.hls.on(Hls.Events.MANIFEST_PARSED, function(event, data) {
          safePlay();
          if (typeof window.updateDynamicQualities === 'function') {
            window.updateDynamicQualities();
          }
        });

        this.hls.on(Hls.Events.LEVEL_LOADED, function(event, data) {
          if (typeof window.updateDynamicQualities === 'function') {
            window.updateDynamicQualities();
          }
        });

        this.hls.on(Hls.Events.LEVEL_SWITCHED, function(event, data) {
          if (typeof window.onHlsLevelSwitched === 'function') {
            window.onHlsLevelSwitched(data.level);
          }
        });

        this.hls.on(Hls.Events.ERROR, function(event, data) {
          if (data.fatal) {
            // Auto-fallback to direct MP4 stream if HLS fails (e.g. 404 on non-existent .m3u8)
            var fallback = (mp4Url && mp4Url !== streamUrl) ? mp4Url : (streamUrl.indexOf('.m3u8') !== -1 ? streamUrl.replace(/\.m3u8($|\?)/i, '.mp4$1') : '');
            if (fallback && fallback !== streamUrl) {
              console.warn('HLS stream failed (' + data.type + '), falling back to MP4 stream:', fallback);
              try { self.hls.destroy(); } catch(e) {}
              self.hls = null;
              self.video.src = fallback;
              self.video.load();
              safePlay();
              return;
            }

            switch (data.type) {
              case Hls.ErrorTypes.NETWORK_ERROR:
                self.hls.startLoad();
                break;
              case Hls.ErrorTypes.MEDIA_ERROR:
                self.hls.recoverMediaError();
                break;
              default:
                self.hls.destroy();
                break;
            }
          }
        });
      } else if (this.video.canPlayType('application/vnd.apple.mpegurl')) {
        // Native Safari HLS
        this.video.src = streamUrl;
        this.video.load();
        safePlay();

        this.video.addEventListener('error', function onSafariErr() {
          var fallback = (mp4Url && mp4Url !== streamUrl) ? mp4Url : (streamUrl.indexOf('.m3u8') !== -1 ? streamUrl.replace(/\.m3u8($|\?)/i, '.mp4$1') : '');
          if (fallback && fallback !== streamUrl) {
            console.warn('Safari HLS failed, falling back to MP4:', fallback);
            self.video.src = fallback;
            self.video.load();
            safePlay();
          }
        }, { once: true });
      } else {
        this.video.src = streamUrl;
        this.video.load();
        safePlay();
      }
    }

    nextEpisode() {
      const now = Date.now();
      if (this._lastNavTime && (now - this._lastNavTime < 350)) return;
      this._lastNavTime = now;

      const eps = this.series.episodes && this.series.episodes.length ? this.series.episodes : [];
      if (!eps.length) return;
      let currentIdx = eps.findIndex((e, idx) => {
        const num = parseInt(e.episode_number || (idx + 1), 10);
        return num === this.currentEpisodeNum;
      });
      if (currentIdx === -1) currentIdx = 0;
      if (currentIdx < eps.length - 1) {
        const nextEp = eps[currentIdx + 1];
        const nextNum = parseInt(nextEp.episode_number || (currentIdx + 2), 10);
        this.loadEpisode(nextNum);
      }
    }

    prevEpisode() {
      const now = Date.now();
      if (this._lastNavTime && (now - this._lastNavTime < 350)) return;
      this._lastNavTime = now;

      const eps = this.series.episodes && this.series.episodes.length ? this.series.episodes : [];
      if (!eps.length) return;
      let currentIdx = eps.findIndex((e, idx) => {
        const num = parseInt(e.episode_number || (idx + 1), 10);
        return num === this.currentEpisodeNum;
      });
      if (currentIdx > 0) {
        const prevEp = eps[currentIdx - 1];
        const prevNum = parseInt(prevEp.episode_number || currentIdx, 10);
        this.loadEpisode(prevNum);
      }
    }

    renderEpisodesGrid() {
      const eps = this.series.episodes && this.series.episodes.length ? this.series.episodes : [];
      if (!eps.length) return;

      // Update Desktop Grid Buttons
      const allBtns = document.querySelectorAll('.reel-ep-btn');
      allBtns.forEach((btn, idx) => {
        const ep = eps[idx] || this.getEpisode(btn.getAttribute('data-ep') || (idx + 1));
        const epNum = parseInt(ep.episode_number || (idx + 1), 10);
        const isUnlocked = this.isEpisodeUnlocked(ep);
        const isActive = (epNum === this.currentEpisodeNum);

        btn.classList.toggle('is-active', isActive);

        // Lock icon
        let lock = btn.querySelector('.reel-ep-lock-icon');
        if (!isUnlocked) {
          const isVipEp = (ep.access_control && (ep.access_control.unlock_type || '').toLowerCase() === 'vip');
          if (!lock) {
            lock = document.createElement('span');
            lock.className = 'reel-ep-lock-icon';
            lock.textContent = isVipEp ? '👑' : '🔒';
            btn.appendChild(lock);
          } else {
            lock.textContent = isVipEp ? '👑' : '🔒';
          }
        } else {
          if (lock) lock.remove();
        }

        // Equalizer
        let eq = btn.querySelector('.reel-ep-equalizer');
        if (isActive) {
          if (!eq) {
            eq = document.createElement('div');
            eq.className = 'reel-ep-equalizer';
            eq.innerHTML = '<span></span><span></span><span></span>';
            btn.appendChild(eq);
          }
        } else {
          if (eq) eq.remove();
        }
      });

      // Update Mobile Bottom Sheet Chips
      const allChips = document.querySelectorAll('.ep-chip');
      allChips.forEach((chip, idx) => {
        const ep = eps[idx] || this.getEpisode(chip.getAttribute('data-ep') || (idx + 1));
        const epNum = parseInt(ep.episode_number || (idx + 1), 10);
        const isUnlocked = this.isEpisodeUnlocked(ep);
        const isActive = (epNum === this.currentEpisodeNum);

        chip.classList.toggle('ep-chip-active', isActive);
        if (isActive) {
          chip.style.borderColor = '#7c4dff';
          chip.style.color = '#fff';
        } else {
          chip.style.borderColor = 'transparent';
          chip.style.color = '#aaa';
        }

        // Lock icon on chip
        let lock = chip.querySelector('.ep-chip-lock-icon');
        if (!isUnlocked) {
          const isVipEp = (ep.access_control && (ep.access_control.unlock_type || '').toLowerCase() === 'vip');
          if (!lock) {
            lock = document.createElement('span');
            lock.className = 'ep-chip-lock-icon';
            lock.style.cssText = 'font-size:9px;position:absolute;top:3px;right:3px;';
            lock.textContent = isVipEp ? '👑' : '🔒';
            chip.appendChild(lock);
          } else {
            lock.textContent = isVipEp ? '👑' : '🔒';
          }
        } else {
          if (lock) lock.remove();
        }

        // Equalizer on chip
        let eq = chip.querySelector('.ep-chip-equalizer');
        if (isActive) {
          if (!eq) {
            eq = document.createElement('div');
            eq.className = 'ep-chip-equalizer';
            eq.style.cssText = 'position:absolute;bottom:4px;left:50%;transform:translateX(-50%);display:flex;gap:1.5px;align-items:flex-end;height:8px;';
            eq.innerHTML = '<div style="width:2px;height:4px;background:#7c4dff;border-radius:1px;"></div><div style="width:2px;height:7px;background:#7c4dff;border-radius:1px;"></div><div style="width:2px;height:5px;background:#7c4dff;border-radius:1px;"></div>';
            chip.appendChild(eq);
          }
        } else {
          if (eq) eq.remove();
        }
      });
    }

    unlockCurrentEpisode(method) {
      const ep = this.getEpisode(this.currentEpisodeNum);
      const ac = ep ? (ep.access_control || {}) : {};
      const unlockType = (ac.unlock_type || 'free').toLowerCase();

      if (unlockType === 'vip' && method !== 'vip' && !this.checkVip()) {
        alert('This episode is VIP Exclusive and cannot be unlocked with ' + method + '. Please upgrade to VIP!');
        return;
      }

      const cost = ep.access_control ? (ep.access_control.coin_cost || 15) : 15;

      if (method === 'coins') {
        if (this.userCoins < cost) {
          alert('Insufficient coins! You have ' + this.userCoins + ' coins. Please top up.');
          return;
        }
        this.userCoins -= cost;
        localStorage.setItem('shorttv_user_coins', this.userCoins.toString());
      }

      const epNum = parseInt(this.currentEpisodeNum, 10);
      if (!this.unlockedEpisodes.some(x => parseInt(x, 10) === epNum)) {
        this.unlockedEpisodes.push(epNum);
      }
      const storageKey = 'shorttv_unlocked_' + (this.series.media_id || 'default');
      localStorage.setItem(storageKey, JSON.stringify(this.unlockedEpisodes));

      const paywall = document.getElementById('shorttv-paywall-modal');
      if (paywall) {
        paywall.style.display = 'none';
        paywall.classList.remove('is-active');
      }

      // Show instant loading feedback
      const loader = document.getElementById('shorttv-video-loader');
      if (loader) loader.style.display = 'block';

      const self = this;
      const restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
      const unlockUrl = restBase + (restBase.endsWith('/') ? '' : '/') + 'shorttv/v1/series/unlock-episode';

      fetch(unlockUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          series_id: this.series.post_id || this.series.media_id || 197,
          episode_number: epNum,
          method: method
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(data) {
        if (data && data.sources) {
          ep.sources = data.sources;
          if (ep.access_control) ep.access_control.is_unlocked = true;
          self.renderEpisodesGrid();
          self.loadEpisode(self.currentEpisodeNum);
        }
      })
      .catch(function(err) {
        console.warn('Unlock API error:', err);
        self.renderEpisodesGrid();
        self.loadEpisode(self.currentEpisodeNum);
      });
    }

    prefetchNextEpisode() {
      const eps = this.series.episodes && this.series.episodes.length ? this.series.episodes : [];
      if (!eps.length) return;
      let currentIdx = eps.findIndex((e, idx) => {
        const num = parseInt(e.episode_number || (idx + 1), 10);
        return num === this.currentEpisodeNum;
      });
      if (currentIdx === -1 || currentIdx >= eps.length - 1) return;

      const nextEp = eps[currentIdx + 1];
      const nextNum = parseInt(nextEp.episode_number || (currentIdx + 2), 10);
      const isNextUnlocked = this.isEpisodeUnlocked(nextEp);

      if (isNextUnlocked && (!nextEp.sources || (!nextEp.sources.hls_stream_url && !nextEp.sources.fallback_mp4))) {
        const restBase = (window.SHORT_CONFIG && window.SHORT_CONFIG.restUrl) || '/wordpress/wp-json/';
        const unlockUrl = restBase + (restBase.endsWith('/') ? '' : '/') + 'shorttv/v1/series/unlock-episode';
        fetch(unlockUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            series_id: this.series.post_id || this.series.media_id || 197,
            episode_number: nextNum,
            method: 'verify'
          })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data && data.sources) {
            nextEp.sources = data.sources;
          }
        })
        .catch(function() {});
      }
    }

    bindEvents() {
      const self = this;

      // Click Episode in Grid (Desktop .reel-ep-btn AND Mobile .ep-chip)
      document.addEventListener('click', function(e) {
        const btn = e.target.closest('.reel-ep-btn, .ep-chip');
        if (btn) {
          e.preventDefault();
          const ep = parseInt(btn.getAttribute('data-ep'), 10);
          if (!isNaN(ep)) {
            self.loadEpisode(ep);
            if (typeof hideDetails === 'function') {
              hideDetails();
            }
          }
        }
      });

      // Plot Synopsis More / Less Toggle
      const moreBtn = document.getElementById('reel-plot-more-btn');
      if (moreBtn) {
        moreBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const text = document.getElementById('reel-plot-text');
          if (text) {
            text.classList.toggle('is-expanded');
            moreBtn.textContent = text.classList.contains('is-expanded') ? 'Less' : 'More';
          }
        });
      }

      // Social Actions: Like
      const likeBtn = document.getElementById('reel-btn-like');
      if (likeBtn) {
        likeBtn.addEventListener('click', function(e) {
          e.preventDefault();
          likeBtn.classList.toggle('is-active');
          const cnt = document.getElementById('reel-like-count');
          if (cnt) {
            const liked = likeBtn.classList.contains('is-active');
            cnt.textContent = liked ? '6.4k' : '6.3k';
          }
        });
      }

      // Social Actions: Bookmark / My List
      const bookmarkBtn = document.getElementById('reel-btn-bookmark');
      if (bookmarkBtn) {
        bookmarkBtn.addEventListener('click', function(e) {
          e.preventDefault();
          bookmarkBtn.classList.toggle('is-active');
        });
      }

      // Video Progress & Time Update
      if (this.video) {
        const progressBar = document.getElementById('shorttv-progress-bar');
        const progressBuf = document.getElementById('shorttv-progress-buffered');
        const loader = document.getElementById('shorttv-video-loader');
        const posterBg = document.getElementById('shorttv-poster-bg');

        this.video.addEventListener('timeupdate', function() {
          if (this.duration && !self.isScrubbing && progressBar) {
            const pct = (this.currentTime / this.duration) * 100;
            progressBar.style.width = pct + '%';
          }
        });

        this.video.addEventListener('progress', function() {
          if (this.duration && this.buffered.length > 0 && progressBuf) {
            const bufferedEnd = this.buffered.end(this.buffered.length - 1);
            const pct = (bufferedEnd / this.duration) * 100;
            progressBuf.style.width = pct + '%';
          }
        });

        this.video.addEventListener('ended', function() {
          self.nextEpisode();
        });

        this.video.addEventListener('playing', function() {
          if (loader) loader.style.display = 'none';
          if (posterBg) posterBg.style.opacity = '0';
        });

        this.video.addEventListener('canplay', function() {
          if (loader) loader.style.display = 'none';
        });

        this.video.addEventListener('waiting', function() {
          if (loader) loader.style.display = 'block';
        });

        this.video.addEventListener('seeking', function() {
          if (loader) loader.style.display = 'block';
        });

        // Anti-download / anti-scraping protections
        this.video.setAttribute('controlsList', 'nodownload noplaybackrate nofullscreen');
        this.video.setAttribute('disablePictureInPicture', 'true');
        this.video.addEventListener('dragstart', function(e) { e.preventDefault(); return false; });

        // (Video click event disabled so background click does not toggle play/pause)

        // Mute / Unmute toggle
        const muteBtn = document.getElementById('shorttv-mute-btn');
        if (muteBtn) {
          muteBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            self.video.muted = !self.video.muted;
            document.getElementById('shorttv-icon-muted').style.display  = self.video.muted ? 'block' : 'none';
            document.getElementById('shorttv-icon-unmuted').style.display = self.video.muted ? 'none'  : 'block';
            muteBtn.title = self.video.muted ? 'Tap to unmute' : 'Tap to mute';
          });
        }
      }

      // Interactive Scrubber — works with both id="shorttv-progress-wrap" and class="stv-progress-wrap"
      const progressWrap = document.getElementById('shorttv-progress-wrap') || document.querySelector('.stv-progress-wrap');
      if (progressWrap) {
        const progressBar = document.getElementById('shorttv-progress-bar');
        const seekFromEvent = function(e) {
          const ep = self.getEpisode(self.currentEpisodeNum);
          if (!self.isEpisodeUnlocked(ep)) return;
          if (!self.video || !self.video.duration) return;
          const rect = progressWrap.getBoundingClientRect();
          const clientX = (e.touches && e.touches.length > 0) ? e.touches[0].clientX : e.clientX;
          if (clientX === undefined) return;
          const pos = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
          self.video.currentTime = pos * self.video.duration;
          if (progressBar) progressBar.style.width = (pos * 100) + '%';
        };

        progressWrap.addEventListener('mousedown', function(e) {
          const ep = self.getEpisode(self.currentEpisodeNum);
          if (!self.isEpisodeUnlocked(ep)) return;
          e.stopPropagation();
          self.isScrubbing = true;
          progressWrap.classList.add('is-scrubbing');
          seekFromEvent(e);
        });

        progressWrap.addEventListener('touchstart', function(e) {
          const ep = self.getEpisode(self.currentEpisodeNum);
          if (!self.isEpisodeUnlocked(ep)) return;
          e.stopPropagation();
          self.isScrubbing = true;
          progressWrap.classList.add('is-scrubbing');
          seekFromEvent(e);
        });

        document.addEventListener('mousemove', function(e) {
          if (self.isScrubbing) seekFromEvent(e);
        });

        document.addEventListener('touchmove', function(e) {
          if (self.isScrubbing) seekFromEvent(e);
        });

        document.addEventListener('mouseup', function() {
          if (self.isScrubbing) {
            self.isScrubbing = false;
            progressWrap.classList.remove('is-scrubbing');
          }
        });

        document.addEventListener('touchend', function() {
          if (self.isScrubbing) {
            self.isScrubbing = false;
            progressWrap.classList.remove('is-scrubbing');
          }
        });
      }
    }
  }

  window.ShortTVPlayer = ShortTVPlayer;
})();
