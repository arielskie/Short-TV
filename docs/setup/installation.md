# 🚀 Platform Installation & Server Requirements

Complete guide to installing WordPress, activating the ShortTV theme, installing required core plugins, and verifying server prerequisites.

---

## 🖥️ Server Prerequisites

| Component | Minimum | Recommended |
|---|---|---|
| **PHP Version** | `7.4` | `8.1` or `8.2` |
| **MySQL / MariaDB** | `5.7` / `10.3` | `8.0` / `10.6` |
| **PHP Extensions** | `openssl`, `curl`, `json`, `mbstring`, `fileinfo` | All enabled |
| **Web Server** | Apache (mod_rewrite) or Nginx | Apache / Nginx with HTTPS |
| **Upload Max Filesize** | `64M` | `256M+` (For large poster/video assets) |

---

## 📦 Step-by-Step Installation

### 1. Upload Theme & Core Plugin
* In **WordPress Admin → Appearance → Themes**, click **Add New → Upload Theme** and upload `short-stream.zip`.
* In **Plugins → Add New → Upload Plugin**, upload `short-stream-core.zip` and click **Activate**.

### 2. Auto-Page Creation
Upon theme activation, ShortTV automatically configures the necessary core WordPress pages:
* `/watch/` (Vertical Reel Player)
* `/short-tv/` (Main ShortTV Directory)
* `/new-popular/` (Trending & Top Ranked)
* `/my-list/` (User Bookmarks)
* `/history/` (User Watch History)
* `/notification/` (Notifications Center)
* `/account/` (Account & Profile Hub)
* `/subscription/` (Pricing & VIP Plans)

### 3. Permalinks Configuration
* Navigate to **WordPress Admin → Settings → Permalinks**.
* Select **Post name** (`/%postname%/`) and click **Save Changes**.
