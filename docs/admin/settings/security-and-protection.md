# 🛡️ Security & DevTools Protection Settings

Protect your exclusive drama video assets, prevent unauthorized inspection, disable right-click theft, and safeguard API requests.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Hub → Settings → Security & Protection** tab.

---

## 🛠️ Security Protections & Features

| Security Module | Description | Recommended State |
|---|---|---|
| **Disable Right-Click Context Menu** | Prevents visitors from right-clicking on videos, posters, or UI elements to save media directly. | `Enabled` |
| **DevTools Key & Shortcut Guard** | Disables `F12`, `Ctrl+Shift+I`, `Ctrl+Shift+J`, `Ctrl+U` (View Source) on client browsers. | `Enabled` in Production |
| **Console Auto-Clear & Anti-Debug** | Clears JavaScript developer console output and detects debugger halts. | `Enabled` |
| **Video Source Obfuscation** | Obfuscates raw direct streaming URLs in DOM markup before player initialization. | `Enabled` |
| **Domain Lock Protection** | Restricts theme and player execution exclusively to your authorized production domain. | `Enabled` |
| **REST API Rate Limiting** | Throttles excessive spam requests to WordPress REST endpoints. | `Enabled` |

---

## 💡 Best Practices
* Keep DevTools Protection **disabled** on local development/staging (`localhost`) to make debugging easy, and **enable** it on your live production server.
