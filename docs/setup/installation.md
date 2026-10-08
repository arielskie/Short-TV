# 🚀 Platform Installation & Server Requirements

Comprehensive guide to installing WordPress, activating the ShortTV theme, installing required core plugins, configuring permalinks, and provisioning required platform pages.

---

## 💻 Server & Environment Requirements

| Requirement | Minimum Specification | Recommended Specification |
|---|---|---|
| **WordPress** | 6.0+ | 6.4+ |
| **PHP** | 7.4+ | 8.1+ / 8.2+ |
| **PHP Extensions** | `curl`, `json`, `mbstring`, `openssl`, `zip`, `fileinfo` | `gd` or `imagick` active |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ | MySQL 8.0+ / MariaDB 10.6+ |
| **Web Server** | Apache with `mod_rewrite` / Nginx | Nginx + PHP-FPM with HTTP/2 |
| **SSL / HTTPS** | **Required** | Valid SSL (Required for PWA & Firebase Auth) |
| **Permalinks** | Post name (`/%postname%/`) | Post name |

---

## 📦 Package Distribution Structure

```
shorttv-package/
├── short-stream.zip          # Active WordPress Theme
├── short-stream-core.zip     # Core Plugin Engine (Required)
├── assets/                   # Logos, Mockups & Icon Assets
└── docs/                     # Complete Platform Documentation
    ├── admin/                # Admin Guides (Dramas, Presets, Settings)
    ├── setup/                # Technical Setup & Firebase Guides
    └── user/                 # End-User Experience & Features
```

---

## 🚀 Step-by-Step Installation Flowchart

```mermaid
flowchart TD
    Start["🚀 Begin Installation"] --> P1["1. Install short-stream-core.zip in Plugins"]
    P1 --> P2["2. Install short-stream.zip in Appearance -> Themes"]
    P2 --> P3["3. Configure Permalinks to 'Post name'"]
    P3 --> P4["4. Verify Auto-Created Platform Pages"]
    P4 --> P5["5. Enter Firebase, Video CDN & License Keys"]
    P5 --> Done["✨ ShortTV Platform Live!"]
```

---

## 🛠️ Step-by-Step Instructions

### Step 1: Install & Activate the Core Plugin
1. In your WordPress Admin Dashboard, navigate to **Plugins → Add New → Upload Plugin**.
2. Select `short-stream-core.zip` from your computer and click **Install Now**.
3. Click **Activate Plugin**.
4. A new **ShortTV Hub** admin suite will appear in your sidebar.

### Step 2: Install & Activate the Theme
1. Go to **Appearance → Themes → Add New → Upload Theme**.
2. Select `short-stream.zip` and click **Install Now**.
3. Click **Activate**.

### Step 3: Configure Permalinks
1. Go to **Settings → Permalinks**.
2. Under **Common Settings**, select **Post name** (`/%postname%/`).
3. Click **Save Changes** (this registers all custom taxonomy and reel player routes).

### Step 4: Required Platform Pages
Upon activation, the theme auto-provisions or maps the core pages below:

| Page Title | Required URL Slug | Template / Purpose |
|---|---|---|
| **Watch** | `watch` | Fullscreen Vertical Reel Player Feed |
| **Auth** | `auth` | Google One-Tap & Mobile Sign-in |
| **Account** | `account` | User Profile, Wallet & Multi-Profiles |
| **History** | `history` | Watch Progress & Continue Watching |
| **My List** | `my-list` | Private Bookmarks & Watchlist |
| **Discover** | `genre` | Category & Trope Hub |
| **Search** | `search` | Instant Debounced Search |
| **Rewards** | `reward` | Daily Streak, Watch Time Milestones & Ads |
| **Leaderboard** | `leaderboard` | Top 10 Ranking & Heat Score Charts |
| **Subscription**| `subscription` | VIP Membership Paywall Checkout |

---

## 🔄 Upgrading Existing Installations

1. Backup your WordPress database and `wp-content/uploads/` directory.
2. In **Plugins → Add New → Upload Plugin**, upload the latest `short-stream-core.zip` and click **Replace current with uploaded**.
3. In **Appearance → Themes → Add New → Upload Theme**, upload the latest `short-stream.zip` and click **Replace current with uploaded**.
4. Go to **Settings → Permalinks** and click **Save Changes**.
