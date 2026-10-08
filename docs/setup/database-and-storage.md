# 🗄️ Database & Storage Architecture

Technical architecture guide on WordPress database tables, custom post types, metadata schemas, and cloud video bucket structures.

---

## 🏗️ Custom Post Types (CPT)

* **`short_title` (or `video`):** The primary drama series entity.
* **`short_episode`:** Child episode posts or structured serialized JSON meta attached to parent dramas.

---

## 🗃️ Core WordPress Options & Tables

| Option Key | Content |
|---|---|
| `short_brand_settings` | Logo URLs, brand display name, header dimensions. |
| `short_firebase_settings` | Firebase Web API keys, DB URL, Service Account JSON, VAPID key. |
| `short_player_settings` | Auto-play preferences, intro skips, tracking intervals. |
| `short_subscription_settings` | VIP pricing tiers, paywall toggles, coin conversion rates. |
| `short_security_settings` | DevTools guards, anti-theft flags, domain locks. |
| `short_fcm_device_tokens` | Array of registered browser/device push subscriber tokens. |
| `short_system_notifications` | Array of broadcast notifications and seeded alerts. |
