/**
 * Short Stream - Service Worker
 */
const CACHE_NAME = 'short-cache-v5';

// Install: Activate immediately without blocking on network pre-caching
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate: Clean up old cache versions and take immediate control of clients
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((name) => {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch: Network-first with runtime caching for static and get requests
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    // Only handle and cache http and https schemes (prevents chrome-extension, moz-extension, blob, data errors)
    if (!event.request.url.startsWith('http://') && !event.request.url.startsWith('https://')) {
        return;
    }

    const url = new URL(event.request.url);

    // Bypass caching for WP admin, login, REST APIs, video streaming, ads, and preview
    if (
        url.pathname.includes('/wp-admin/') ||
        url.pathname.includes('/wp-login.php') ||
        url.pathname.includes('/wp-json/') ||
        url.pathname.includes('/watch/') ||
        url.pathname.endsWith('.mp4') ||
        url.pathname.endsWith('.webm') ||
        url.pathname.endsWith('.m3u8') ||
        url.pathname.endsWith('.ts') ||
        url.hostname.includes('googleapis.com') ||
        url.hostname.includes('doubleclick.net') ||
        url.search.includes('preview=')
    ) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((networkResponse) => {
                if (
                    networkResponse &&
                    networkResponse.status === 200 &&
                    networkResponse.type === 'basic'
                ) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone).catch(() => {});
                    }).catch(() => {});
                }
                return networkResponse;
            })
            .catch(async () => {
                const cached = await caches.match(event.request);
                if (cached) return cached;
                return new Response('', { status: 408, statusText: 'Request timed out or offline' });
            })
    );
});

// ═══════════════════════════════════════════════════════════════════════════
// PUSH & STATUS BAR LIVE NOTIFICATIONS (FCM / Web Push)
// ═══════════════════════════════════════════════════════════════════════════
self.addEventListener('push', function(event) {
    var data = {};
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data = { title: 'ShortTV Alert', message: event.data.text() };
        }
    }

    var notifPayload = data.notification || data;
    var title = notifPayload.title || data.title || '🎬 ShortTV Drama Alert';
    var body = notifPayload.body || notifPayload.message || data.message || 'New episodes and releases are ready to watch!';
    var icon = notifPayload.icon || data.poster || 'https://i.postimg.cc/cH3CM5h4/image.png';
    var image = notifPayload.image || data.poster || undefined;
    var targetUrl = data.link || data.url || (data.data && data.data.url) || (notifPayload.click_action) || '/';

    var options = {
        body: body,
        icon: icon,
        badge: icon,
        image: image,
        data: {
            url: targetUrl
        },
        vibrate: [200, 100, 200, 100, 200],
        tag: data.tag || ('shorttv-push-' + Date.now()),
        renotify: true,
        requireInteraction: false
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    var targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(windowClients) {
            for (var i = 0; i < windowClients.length; i++) {
                var client = windowClients[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
