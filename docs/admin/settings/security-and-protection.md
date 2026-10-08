# 🛡️ Security & DevTools Protection Settings

Manage your platform's built-in client anti-inspection guards, shortcut locks, and domain licensing.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Hub → Settings → Security & Protection** tab.

---

## 🔒 Your Active Protections

Your platform has **two core security layers**:

### 1. 🛡️ Anti-Inspection & Right-Click Guard (Under *Security & Protection*)
When enabled (`Allow Right-Click & Inspect Element` is unchecked):
* 🚫 **Right-Click Context Menu Block:** Prevents visitors from right-clicking on videos, posters, or UI elements.
* 🚫 **DevTools Hotkey Locks:** Blocks `F12`, `Ctrl+Shift+I` (Inspect), `Ctrl+Shift+J` (Console), `Ctrl+Shift+C` (Element Picker), and Mac `Cmd+Opt+I/J/C`.
* 🚫 **View Source Lock:** Blocks `Ctrl+U` and `Cmd+Opt+U` (View Source).
* 🚫 **Save Page Lock:** Blocks `Ctrl+S` and `Cmd+S` to prevent downloading offline page/media dumps.

### 2. 🔐 Domain Lock Protection (Under *Theme License & Domain Lock*)
* Restricts your theme and video engine execution strictly to your registered production domain.

---

## 💡 Developer Tip
* Keep **"Allow Right-Click & Inspect Element"** checked while developing locally on `localhost` so you can use browser Developer Tools. Uncheck it when launching live to protect your content!
