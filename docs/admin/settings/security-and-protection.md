# 🛡️ Security & Content Protection Settings

Manage your platform's built-in client anti-inspection guards, shortcut locks, and developer exemptions. All settings use uniform intuitive toggles: **Checked = Enabled**, **Unchecked = Disabled**.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Hub → Settings → Security & Protection** tab.

---

## 🔒 Protection Modules

| Setting | Behavior When Checked (Enabled) | Behavior When Unchecked (Disabled) |
|---|---|---|
| **Right-Click Protection** | Blocks the browser context menu on posters, videos, and UI. | Visitors can right-click freely. |
| **Text Selection Protection** | Prevents dragging and highlighting text across the site (`user-select: none`). | Visitors can select and highlight text. |
| **Copy & Cut Protection** | Disables `Ctrl+C`, `Ctrl+X`, `Ctrl+U` (view source), and `Ctrl+S` (save page). | Keyboard copy and save shortcuts function normally. |
| **Developer Tools Protection** | Blocks `F12`, `Ctrl+Shift+I`, `Ctrl+Shift+J`, `Ctrl+Shift+C`, and Mac `Cmd+Opt+I/J/C`. | Browser Developer Tools inspection is allowed. |
| **Screenshot & Print Shield** | Blanks page print/PDF generation (`Ctrl+P`) and clears clipboard on PrintScreen key. | Standard printing is allowed. |
| **Anti-Leak Watermark** | Renders a subtle semi-transparent floating IP/Username watermark across the vertical video player to deter recording. | Watermark is hidden. |
| **Admin / Editor Exemption** | Logged-in WordPress administrators and editors bypass all locks automatically for testing and managing content. | Protections apply globally to all users. |

---

## 🔑 Developer Bypass & Local Network Support
In addition to the admin settings, developers can bypass restrictions during staging and development:
* **Localhost & Private LAN Networks**: Localhost and local private subnets (`192.168.x.x`, `10.x.x.x`, `172.16.0.0/12`) automatically bypass licensing locks.
* **Developer Master Key**: Append `?dev_key=...` with the configured master key to bypass licensing restrictions on remote staging domains.
* **Non-Intrusive Engine**: Content protection guards work silently without intrusive screen blackouts or blur overlays, preserving a seamless user experience.
