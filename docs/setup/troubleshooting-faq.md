# ❓ FAQ & Troubleshooting Guide

Solutions for common installation, streaming playback, push notification, and permissions questions.

---

## ❓ Push Notifications & FCM

### Q: Why does it show "0 Push Subscribers" in the admin dashboard?
**A:** Subscribers are real browser/mobile devices that have granted notification permissions. 
1. Push notifications **cannot** register in **Incognito/Private** mode (Chrome disables Service Worker push in Incognito for privacy).
2. Open the site in a normal tab, click **Allow** on the bottom-left prompt banner, and approve the browser prompt.
3. Refresh the Admin page to see your updated subscriber count.

### Q: Which credentials do I need for push notifications?
**A:** 
1. **Firebase Service Account JSON** (from Firebase Console → Project Settings → Service Accounts → Generate new private key).
2. **Web Push VAPID Key** (from Firebase Console → Project Settings → Cloud Messaging → Web configuration).

---

## ❓ Video Playback & Streaming

### Q: What video format should I upload?
**A:** ShortTV natively supports:
* Vertical MP4 (`1080x1920` or `720x1280` px) with H.264 video codec and AAC audio.
* HLS (`.m3u8`) streaming playlists for adaptive bitrate playback.

### Q: Why is my video not playing on mobile?
**A:** Ensure your storage provider (e.g. Cloudflare R2 / S3) has **CORS** enabled for your domain:
```json
[
  {
    "AllowedOrigins": ["*"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "MaxAgeSeconds": 3600
  }
]
```
