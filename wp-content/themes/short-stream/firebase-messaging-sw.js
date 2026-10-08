/**
 * Short Stream - Firebase Cloud Messaging Service Worker
 */
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.23.0/firebase-messaging-compat.js');

// Import the main PWA service worker with offline caching & push handling
importScripts('./assets/pwa/service-worker.js');

// Listen for background FCM messages
self.addEventListener('push', function(event) {
    if (event.data) {
        try {
            var payload = event.data.json();
            var notif = payload.notification || payload;
            var title = notif.title || '🎬 ShortTV Drama Alert';
            var options = {
                body: notif.body || notif.message || 'New episodes and releases ready to watch!',
                icon: notif.icon || 'https://i.postimg.cc/cH3CM5h4/image.png',
                image: notif.image || undefined,
                badge: 'https://i.postimg.cc/cH3CM5h4/image.png',
                data: {
                    url: (payload.data && payload.data.link) || notif.click_action || '/'
                },
                vibrate: [200, 100, 200]
            };
            event.waitUntil(self.registration.showNotification(title, options));
        } catch(e) {}
    }
});
