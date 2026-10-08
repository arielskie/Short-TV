# 🔔 Notification Center & FCM Live Push Alerts

Broadcast system announcements, new drama episode releases, promotional discounts, and live push notifications to subscriber devices.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Hub → Settings → Notifications** tab.

---

## 📡 Features Overview

```
Broadcast Engine
 ├── FCM HTTP v1 API Push (Direct to Windows/Mac/Android notification shades)
 ├── In-App Toast Alerts (Real-time popups while users browse)
 ├── Header Bell Notification Center (Unread count badge + preview dropdown)
 └── Dedicated /notification/ Feed (Full chronological alert history)
```

---

## 🛠️ Broadcasting a Notification

| Form Field | Description | Example |
|---|---|---|
| **Notification Type / Priority** | Categorization and styling for the notification. | `Important Update (Red Alert)`, `New Episode Release`, `Coins & Rewards`. |
| **Title / Headline** | Main headline displayed in push notifications and in-app feeds. | `🔥 Stranger Things: Season Finale is Streaming!` |
| **Message / Details** | Short summary or description text for the alert. | `Episode 10 is now available in 4K Ultra HD. Watch now!` |
| **Badge Tag Label** | Custom pill tag shown in notification cards. | `Season Finale`, `Free VIP`, `Bonus`. |
| **Destination URL / Link** | URL opened when the viewer taps or clicks the notification. | `http://localhost/wordpress/watch/315/` |
| **Send Live FCM Push Alert** | Check to trigger live device push notifications to all registered subscribers. | `Checked` |

---

## ⚡ Auto-Seed Smart Releases
Click the **Auto-Seed Smart Releases** button to instantly generate high-converting default notifications for:
* Welcome aboard onboarding message
* Daily Coin bonus reminders
* Trending series spotlight
