# ShortTV — Troubleshooting & Frequently Asked Questions

This document provides resolutions for common hosting, video streaming, cross-device sync, and PWA installation scenarios.

---

## ❓ Frequently Asked Questions

### 1. Which video streaming formats are supported?
ShortTV supports:
- **Direct MP4 Files** (Vertical 9:16 or 16:9 widescreen).
- **HLS Adaptive Streams (`.m3u8`)** delivered via Cloudflare R2, Gumlet Video DRM, or Cloudinary.
- **Third-Party Video Hosts** (Cloudinary, Gumlet, Vimeo, YouTube).

### 2. How does real-time cross-device sync work?
ShortTV utilizes Google Firebase (Firestore and Realtime Database) coupled with intelligent local caching:
- When a viewer watches Episode 3 at 01:25 on mobile, the timestamp is synced to their user profile in Firestore.
- Opening the website on a PC or tablet instantly reflects the updated progress on the **Continue Watching** shelf.

### 3. Can I lock specific episodes behind a VIP paywall?
Yes. You can configure free preview thresholds per drama (e.g. *Episodes 1–5 Free, Episode 6+ VIP/Coins*) or set global defaults in **ShortTV → Settings → Monetization**.

---

## 🔧 Troubleshooting Common Technical Issues

### ⚠️ Issue 1: "There has been an error cropping your image" in WordPress Customizer
- **Root Cause**: The PHP `gd` or `imagick` image processing extension is disabled in your `php.ini`.
- **Resolution**:
  1. Open your server's `php.ini` file (in XAMPP: `C:\xampp\php\php.ini`).
  2. Locate `;extension=gd` and remove the semicolon to enable it:
     ```ini
     extension=gd
     ```
  3. Restart Apache.
  4. *Quick Workaround*: In the Customizer crop dialog, click **"Skip cropping"** to bypass server-side cropping.

---

### ⚠️ Issue 2: Video fails to load or browser console shows CORS error
- **Root Cause**: Your video CDN bucket (e.g. Cloudflare R2) lacks Cross-Origin Resource Sharing headers.
- **Resolution**: Add this CORS rule to your storage bucket settings:
  ```json
  [
    {
      "AllowedOrigins": ["*"],
      "AllowedMethods": ["GET", "HEAD"],
      "AllowedHeaders": ["*"],
      "MaxAgeSeconds": 86400
    }
  ]
  ```

---

### ⚠️ Issue 3: PWA install prompt displays old logo or old app name
- **Root Cause**: Chromium-based browsers aggressively cache Progressive Web App manifests on `localhost` or custom domains.
- **Resolution**:
  1. Open Chrome DevTools (`F12`).
  2. Navigate to **Application → Storage**.
  3. Click **"Clear site data"**.
  4. Press `Ctrl + F5` to reload.

---

### ⚠️ Issue 4: 404 Error when opening `/watch`, `/genre`, or `/account`
- **Root Cause**: WordPress rewrite rules require flushing after theme activation.
- **Resolution**:
  1. In WordPress Admin, navigate to **Settings → Permalinks**.
  2. Confirm **Post name** (`/%postname%/`) is selected and click **Save Changes**.
  3. Ensure pages with slugs `watch`, `genre`, `account`, `reward`, `leaderboard` exist under **Pages → All Pages**.
