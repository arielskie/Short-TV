# ShortTV — Platform Version & Changelog

All notable changes, architectural updates, and enhancements to the ShortTV platform are documented in this file.

---

## 🏷️ Release History

| Version | Release Date | Major Architectural Milestones |
|---|---|---|
| **v2.5.0** | 2026-10-07 | PWA Site Icon Engine, Borderless Floating Controls & Deduplication Guards |
| **v2.4.0** | 2026-10-06 | SaaS Transaction Ledger Suite & Multi-Profile Cloud Sync |
| **v2.3.0** | 2026-10-05 | 7-Day Check-in Streaks, Watch Time Red Envelopes & Rewarded Video Ads |
| **v2.0.0** | 2026-10-01 | TikTok-Style Vertical Reels Swiper Engine & Hero Billboard |
| **v1.0.0** | Initial | Core WordPress Theme & Firebase Cross-Device Sync Architecture |

---

### [2.5.0] — 2026-10-07
#### Added
- **Dynamic PWA Site Icon Engine**: Direct synchronization between WordPress Customizer Site Icon (`get_site_icon_url`) and the PWA manifest generator.
- **Cache-Busting Manifest Endpoint**: Added version hashes (`?v=313`) and `no-cache` HTTP headers preventing stale PWA metadata.
- **Floating Controls Interaction**: Re-architected `#reel-btn-download-app` with borderless glassmorphic design and native `window.shortPromptPwaInstall()` trigger.

#### Fixed
- **Ad Reward Deduplication**: Guarded duplicate push transactions in `page-account.php`, `page-reward.php`, and `page-watch.php`.
- **Genre Route Suppression**: Restored full header & navigation rendering on taxonomy archives and genre exploration pages.

---

### [2.4.0] — 2026-10-06
#### Added
- **SaaS Transaction Filter Suite**: Categorized ledger filtering (*All, Coins, VIP, Video Ads*) with dynamic client-side filtering and real-time balance calculations.
- **Multi-Profile Cloud Architecture**: Integrated `short_active_profile_id` into Firestore and LocalStorage sync channels.

---

### [2.3.0] — 2026-10-05
#### Added
- **Gamification Rewards Hub**: 7-Day progressive check-in calendar (+100 to +300 coins) with streak multiplier bonuses.
- **Daily Watch Time Milestones**: Automated tiered coin rewards (5m, 10m, 20m, 30m, 45m) with interactive red envelope cards.
- **Rewarded Video Ad Player**: 30-second sponsored video clips awarding wallet credits.

---

### [2.0.0] — 2026-10-01
#### Added
- **TikTok-Style Vertical Swiper**: 9:16 vertical video player with touch gestures, episode drawers, speed controls, and double-tap heart animations.
- **Cinematic Hero Billboard**: Dynamic backdrop lighting and top drama spotlight.
- **Cross-Device Firebase Synchronization**: Live resume progress on `Continue Watching` shelf.

---

### [1.0.0] — Initial Release
- **Core Architecture**: Custom Post Type `shorttv_drama` and episode manager.
- **Authentication**: Google One-Tap & Firebase Auth integration.
- **Video Delivery**: Cloudflare R2 & Gumlet CDN support.
