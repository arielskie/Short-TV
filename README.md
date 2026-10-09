# ShortTV — Short-Drama Streaming Platform for WordPress

<p align="center">
  <img src="assets/preview.png" alt="ShortTV Banner" width="100%" style="border-radius: 12px; box-shadow: 0 16px 48px rgba(0,0,0,0.7); margin-bottom: 20px;">
</p>

<p align="center">
  <a href="https://wordpress.org"><img src="https://img.shields.io/badge/WordPress-6.0%2B-21759B.svg?style=for-the-badge&logo=wordpress&logoColor=white" alt="WordPress"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg?style=for-the-badge&logo=php&logoColor=white" alt="PHP"></a>
  <a href="https://firebase.google.com"><img src="https://img.shields.io/badge/Firebase-Auth%20%26%20Firestore-FFCA28.svg?style=for-the-badge&logo=firebase&logoColor=black" alt="Firebase"></a>
  <a href="https://developers.cloudflare.com/r2/"><img src="https://img.shields.io/badge/Cloudflare%20R2-Zero%20Egress-F38020.svg?style=for-the-badge&logo=cloudflare&logoColor=white" alt="Cloudflare R2"></a>
  <a href="https://web.dev/progressive-web-apps/"><img src="https://img.shields.io/badge/PWA-Installable%20App-5A0FC8.svg?style=for-the-badge&logo=pwa&logoColor=white" alt="PWA"></a>
</p>

<p align="center">
  <a href="https://arielskie.github.io/Short-TV/">
    <img src="https://img.shields.io/badge/🚀%20LIVE%20DEMO-EXPLORE%20SHORTTV%20ONLINE-00df82?style=for-the-badge&logo=googlechrome&logoColor=white&labelColor=090d16" alt="Live Demo Button" height="38">
  </a>
  <a href="https://arielskie.github.io/Short-TV/docs/admin-simulator.html">
    <img src="https://img.shields.io/badge/⚙️%20ADMIN%20PANEL-TEST%20SIMULATOR-2271b1?style=for-the-badge&logo=wordpress&logoColor=white&labelColor=1e293b" alt="Admin Simulator Button" height="38">
  </a>
</p>

> **ShortTV** is a state-of-the-art, mobile-first **vertical micro-drama streaming platform** engineered specifically for WordPress. Inspired by global industry leaders (*ShortTV, ReelShort, DramaBox, GoodShort*), it is optimized for viral short-drama episodes, bite-sized vertical series, web-novel adaptations, and monetization through coins and VIP subscriptions.

---

## 🎮 Live Interactive Product Demos

Experience the frontend vertical player and backend settings panel live in your browser:

- 📱 **[Launch Live App & Player Demo (https://arielskie.github.io/Short-TV/)](https://arielskie.github.io/Short-TV/)** — Browse mini-series, swipe reels, test double-tap heart animations, 1080p VIP streaming, and coin paywalls.
- ⚙️ **[Launch WordPress Admin Simulator](https://arielskie.github.io/Short-TV/docs/admin-simulator.html)** — Test the real-time Netflix-style splash screen customizer, Cloudflare R2 / Gumlet DRM connection testers, and monetization rules.

---

## 📑 Complete Documentation Suite

| Document | Audience | Highlights |
|---|---|---|
| [**Interactive Admin Simulator**](docs/admin-simulator.html) | Potential Buyers & Clients | **Live browser-based simulation** of the WordPress Admin panel, live Netflix phone splash preview simulator, storage credentials, and monetization dials. |
| [**USER_GUIDE.md**](docs/USER_GUIDE.md) | Viewers & Members | Inside video player, resolution quality tiers (1080p/720p VIP), gesture controls, coin unlocks, and PWA installation. |
| [**ADMIN_GUIDE.md**](docs/ADMIN_GUIDE.md) | Content Managers & Editors | ShortTV Drama Studio, R2 bulk uploader, folder hierarchy routing, and visual section builders. |
| [**CONFIGURATION.md**](docs/CONFIGURATION.md) | System Architects | Firebase Auth & Firestore setup, Cloudflare R2 / Gumlet CDN, payment webhooks. |
| [**INSTALLATION.md**](docs/INSTALLATION.md) | Administrators & Developers | Server requirements, theme/plugin installation, page route mapping. |
| [**FAQ_TROUBLESHOOTING.md**](docs/FAQ_TROUBLESHOOTING.md) | Support & DevOps | CORS fixes, PHP GD configuration, cross-device sync diagnostics. |
| [**CHANGELOG.md**](docs/CHANGELOG.md) | Everyone | Version release history, architectural updates, and feature notes. |
| [**LICENSE.md**](docs/LICENSE.md) | Commercial | Commercial software licensing terms and conditions. |

---

## 🎬 Inside Video: ReelPlayer Features & Monetization

```mermaid
flowchart TD
    Player["📱 ShortTV 9:16 Vertical ReelPlayer"]
    
    subgraph Stream_Engine ["⚡ Multi-Resolution Adaptive Streaming"]
        Auto["Auto (Recommended - HLS Adaptive)"]
        HD1080["1080p Full HD (👑 VIP Gated)"]
        HD720["720p HD (👑 VIP Gated)"]
        SD480["480p SD (Free / Standard)"]
        SD360["360p Data Saver (Low Bandwidth)"]
    end

    subgraph Access_Control ["👑 VIP Pass & Episode Access Control"]
        VIP_Pass["⭐ VIP Pass Button (Unlimited All-Access)"]
        Coins_Unlock["🪙 Coin Pay-Per-Episode (10-20 Coins/Ep)"]
        Free_Teasers["🆓 Free Teasers (First N Episodes Free)"]
        Auto_Unlock["🔄 Auto-Unlock Next Episode Toggle"]
    end

    subgraph User_Controls ["🎛️ Interactive Controls & Gestures"]
        Gestures["Swipe UP / DOWN Episode Switcher"]
        Speed_Control["Speed Switcher: 0.75x, 1.0x, 1.25x, 1.5x, 2.0x"]
        Heart_Burst["Double-Tap Screen Like with Heart FX"]
        Bookmark_Share["Save to My List & Web Share API"]
        Drama_Rating["Interactive 5-Star Drama Rating Widget"]
    end

    Player --> Stream_Engine
    Player --> Access_Control
    Player --> User_Controls
```

### 1. Video Quality Selector
- **Auto (Recommended)**: Dynamically adjusts HLS video resolution to viewer bandwidth.
- **1080p (Full HD) & 720p (HD)**: High-definition tiers gated behind active **VIP membership** (`👑 VIP` badge). Non-VIP viewers are presented with the VIP Pass upgrade sheet.
- **480p (SD) & 360p (Data Saver)**: Free-access resolutions optimized for mobile data networks.

### 2. VIP Pass & Episode Unlocking
- **⭐ VIP Pass**: Instant unrestricted access to all episodes, 1080p Full HD streaming, zero ads, and VIP badge status.
- **Coin Paywall**: Granular per-episode access rules with optional automatic next-episode unlocking.
- **Visual Status Chips**: Active playing frequency equalizer (`|||`), free episodes, and golden crown VIP badges.

---

## 🏗️ System Architecture

```mermaid
flowchart TD
    Client["📱 Frontend Client (PWA / Mobile / Desktop Web)"]
    
    subgraph WP_Backend ["WordPress Backend Engine"]
        CPT["Drama Studio & Episode Builder"]
        SectionBuilder["Visual Section & Navigation Builder"]
        Paywall["Monetization & Coin Economy"]
        PWA_Engine["PWA Manifest & Service Worker"]
    end
    
    subgraph Cloud_Services ["Cloud & Video Infrastructure"]
        Firebase["🔥 Google Firebase (Auth, Firestore, Cloud Sync)"]
        CDN["☁️ Video Storage & CDN (Cloudflare R2 / Gumlet / Cloudinary)"]
        Payments["💳 Payment Gateway (Lemon Squeezy / Webhooks)"]
        Ads["📢 Rewarded Video Ad Networks"]
    end
    
    Client <-->|Live Sync & Multi-Profile| Firebase
    Client <-->|Adaptive HLS / MP4 Stream| CDN
    Client <-->|API Queries & UI Hydration| WP_Backend
    WP_Backend <-->|VIP Subscription Webhooks| Payments
    Client <-->|Coin Rewards Verification| Ads
```

---

## 🌟 Comprehensive Page & Layout Showcase

| Page View | Core Components | Interactive Features |
|---|---|---|
| **🏠 Homepage** | Cinematic Hero Billboard, Genre Quick Pills, Continue Watching Shelf, Curated Carousels | 1-click play, bookmark toggle, live progress bar, floating PWA install & Back-to-Top |
| **🔍 Discover** | Category & Trope Explorer, Dynamic Grid Layout | Filter by release date, popularity, trope tags, with instant hover preview |
| **🎁 Rewards** | 7-Day Check-in Streak, Watch Time Milestones, Video Ads | Progressive daily coin bonuses, red envelope tiered gifts, rewarded video player |
| **🏆 Ranking** | Podium Top 3 & Top 10 Chart, Dynamic Genre Filters | Live heat score engine measuring views, likes, shares, and watch time |
| **👤 Account** | Profile Header, Coins Wallet, SaaS Transaction Ledger | Google One-Tap auth, multi-profile switcher, filterable ledger with deduplication |
| **📱 Watch Player** | Fullscreen 9:16 Vertical Video Feed, Episode Drawer | Quality selector (1080p/720p VIP), swipe UP/DOWN, double-tap like FX, speed toggle (0.75x-2.0x), automated VIP paywall |

---

## ⚡ Core Feature Matrix

| Feature | Description | Benefit |
|---|---|---|
| **ReelPlayer Engine** | Multi-quality vertical streaming with 1080p/720p VIP tiers, prebuffering, and gestures. | Maximize mobile session length and binge-watching. |
| **Cross-Device Sync** | Real-time synchronization via Google Firebase (Firestore & RTDB). | Viewers pick up exactly where they left off on any device. |
| **Dual Monetization** | Pay-per-episode coins + recurring VIP memberships. | Flexible revenue channels maximizing viewer conversions. |
| **Gamification Engine** | Daily login streaks, watch time milestones (red envelopes), and rewarded video ads. | 3x user retention and ad revenue generation. |
| **PWA App Experience** | Native-like desktop and mobile app with custom dynamic icons. | Zero app-store fees, high install rates, and offline shell. |
| **Multi-Profile System** | Up to 5 independent sub-profiles per user account. | Shared household accounts with personal history tracking. |
| **Ledger Guards** | Real-time deduplication filters preventing double-reward exploits. | Guaranteed financial data integrity and analytics accuracy. |

---

## 🛠️ Admin Builder & Studio Capabilities

| Admin Studio | Key Capabilities |
|---|---|
| **🎬 ShortTV Drama Studio** | Dedicated studio for vertical series, multi-cloud storage selector (Cloudflare R2, Gumlet, Cloudinary), auto-subfolder routing, batch R2 uploader, and auto-sort by episode number. |
| **📱 Episode Stream Pipeline** | 1-click batch paywall rules (*First 5 Free, next Coins* $\rightarrow$ *Apply to All*), sequential auto-renumbering (1 to N), and format switcher (Direct MP4 / Adaptive HLS). |
| **🏠 Visual Section Builder** | Desktop & mobile homepage rail manager with drag-and-drop reordering, layout styles (Hero Billboard, Vertical 9:16 Poster, Square Tile), and content source filters. |
| **📱 Mobile Header Studio** | Live interactive smartphone preview with 1-click toggles (Logo, Search, Coins, My List, History, Genre line) and auto-synced WordPress genre taxonomies. |
| **💳 Monetization Manager** | Configurable coin packages, bonus coin tiers, VIP subscriptions (Weekly/Monthly/Yearly), and webhook payment gateways. |

---

## 🚀 Quick Start in 4 Steps

1. **Upload Theme & Core Plugin to WordPress**:
   - `short-stream-core.zip` $\rightarrow$ **Plugins → Add New**
   - `short-stream.zip` $\rightarrow$ **Appearance → Themes**
2. **Configure Permalinks**:
   - Navigate to **Settings → Permalinks** and choose **Post name**.
3. **Enter Firebase & Video CDN Credentials**:
   - Open **ShortTV Settings** and paste your Firebase Web App and Cloudflare R2 / CDN keys.
4. **Upload Brand Site Icon**:
   - Go to **Appearance → Customize → Site Identity → Site Icon** to automatically generate all PWA and favicon assets.

---

## 📄 License
ShortTV is proprietary software released under the commercial theme license. All rights reserved.
