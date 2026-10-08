# ☁️ Video Storage & CDN Delivery

Connect cost-effective cloud object storage, streaming CDNs, and adaptive bitrate pipelines for zero-buffering playback worldwide.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Hub → Video Storage & CDN**.

---

## 🛠️ Supported Storage & CDN Providers

```
Storage Architecture
 ├── 1. Cloudflare R2 (Zero Egress Fees, Ultra-Fast Global Anycast CDN)
 ├── 2. Gumlet (Automated HLS Transcoding & DRM Optimization)
 ├── 3. Cloudinary (Cloud Video & Dynamic Transformations)
 └── 4. Custom Direct CDN / S3-Compatible Buckets (AWS S3, Wasabi, BunnyCDN)
```

---

## ⚙️ Cloudflare R2 Setup Guide
1. Create a Bucket in your [Cloudflare Dashboard](https://dash.cloudflare.com/) (e.g. `shorttv-videos`).
2. Navigate to **R2 → Manage R2 API Tokens** and generate an API token with **Admin Read & Write** permissions.
3. Enter your **Account ID**, **Access Key ID**, **Secret Access Key**, and **Public Custom Domain / Worker URL** in the ShortTV Storage settings.
4. Test connection to verify immediate upload capability.
