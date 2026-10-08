# 🗂️ Content Blocks & Homepage Section Builder

The **Homepage Section Builder** (`WordPress Admin → ShortTV Hub → Content Blocks`) is a powerful, dynamic visual shelf and layout management studio. It gives administrators 100% control over how short dramas, carousels, hero billboards, and mobile navigation bars are structured across desktop and mobile devices.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Hub → Content Blocks** (or `admin.php?page=short-homepage`).

---

## 🧭 The 3 Division Layout Selector

At the top of the Section Builder, you can switch between three distinct layout contexts:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  [ 🖥️ Desktop Header ]    [ 📱 Mobile Header ]    [ 📱 Mobile Bottom Menu ]  │
└─────────────────────────────────────────────────────────────────────────────┘
```

| Division | Target Audience | Primary Function |
|---|---|---|
| **🖥️ Desktop Header** | Desktop & Tablet Browsers | Manages desktop top navigation tabs (`Home`, `Categories`, `New & Popular`, `Leaderboard`, `My List`) and the specific content rows rendered beneath each tab. |
| **📱 Mobile Header** | Mobile Web & PWA Viewers | Customizes the ultra-compact 48px sticky mobile top bar, live toggle switches, search triggers, coin wallet, and genre pills. |
| **📱 Mobile Bottom Menu** | Mobile Devices (PWA/App View) | Manages the floating 5-button bottom dock navigation (`Home`, `Discovery/Shorts`, `Rewards`, `Leaderboard`, `Account`). |

---

## 📱 Mobile Header Quick-Controls Studio

When selecting **📱 Mobile Header**, an instant visual toggle matrix appears allowing you to show or hide individual header components:

| Toggle Switch | Setting Key | Description |
|---|---|---|
| **Logo** | `show_logo` | Displays brand logo or site icon on the left edge. |
| **Search Bar** | `show_search` | Shows the floating search bar or expandable search trigger icon. |
| **Language Switcher** | `show_lang` | Displays the multi-language selector dropdown / modal trigger. |
| **Coins / Wallet** | `show_coins` | Shows the user's live Coin balance badge with direct link to the Recharge Store. |
| **My List** | `show_mylist` | Quick bookmark icon to access saved favorite dramas. |
| **Watch History** | `show_history` | Quick clock icon to open recently played episodes and continue watching. |
| **Genre Labels** | `show_genre_labels` | Horizontal scrolling mood/genre pill bar beneath the search row. |

> [!TIP]
> Changes made to the Mobile Header toggles can be saved immediately via the **Save Mobile Header Settings** button, or reverted using **Reset to Defaults**.

---

## 🎨 Tabs & Icons Studio (Desktop & Mobile Bottom Dock)

Clicking the **`⚙️ Manage Tabs & Icons`** button opens an interactive tab manager to reorder, add, or customize tabs with 26+ custom SVG streaming icons.

### Supported Dashicons & Custom SVG Streaming Icons
- 🏠 **Home** (`dashicons-admin-home`)
- 📂 **Categories** (`dashicons-category` / `dashicons-list-view`)
- ⭐ **Star / Favorites** (`dashicons-star-filled`)
- 🔥 **Trending / Flame** (`dashicons-flame`)
- 🔖 **Bookmark / My List** (`dashicons-bookmark`)
- 🏆 **Awards / Trophy** (`dashicons-awards`)
- 🥇 **Medal / Rank** (`dashicons-medal`)
- 🎬 **Short TV Player** (`dashicons-video-alt3`)
- 📱 **Discovery / Shorts** (`dashicons-smartphone`)
- 📺 **TV / Mini-Series** (`dashicons-desktop`)
- 👑 **VIP / Crown** (`dashicons-admin-users`)
- 💰 **Rewards / Coins** (`dashicons-money-alt`)
- 💕 **Romance & Moods** (`dashicons-heart`)

### Tab Configuration Fields
1. **Icon Picker**: Live preview dropdown with SVG streaming icons.
2. **Tab Title**: Human-readable label (e.g. *New & Popular*, *Leaderboard*, *Discovery*).
3. **Target Slug**: Unique URL anchor/query slug (e.g. `home`, `categories`, `new-popular`, `leaderboard`, `my-list`, `mobile_discovery`).
4. **Actions**: Add new tab (`+`), remove tab (`🗑️`), or drag to reorder.

---

## 📊 Section Configuration Table & Shelf Builder

The main table lists every content row rendered on the active tab page in exact display order from top to bottom.

```
┌───┬──────────────────────────┬──────────────────────────┬────────────────────────┬───────────┬─────────┬─────────┐
│ # │ Section Title            │ Card Layout Style        │ Content Source         │ Override  │ Limit   │ Enabled │
├───┼──────────────────────────┼──────────────────────────┼────────────────────────┼───────────┼─────────┼─────────┤
│ 1 │ Hero Slider Showcase     │ 🎬 Hero Slider           │ Hero Slider (Featured) │ —         │ 5       │  [x]    │
│ 2 │ Most Popular 🌍          │ 🔟 TOP 10 (Giant Ranks)  │ Most Viewed (views)    │ —         │ 10      │  [x]    │
│ 3 │ Short VIP 👑             │ 📱 Vertical 9:16 Poster  │ VIP Only (vip)         │ —         │ 10      │  [x]    │
│ 4 │ Top Rated 🏆             │ 🏆 Leaderboard Podium    │ Top Rated (rating)     │ —         │ 10      │  [x]    │
│ 5 │ New Releases 🚀          │ 📱 Vertical 9:16 Poster  │ New Releases (latest)  │ —         │ 10      │  [x]    │
│ 6 │ Binge-Ready 🔥           │ 🖥️ Landscape 16:9 Banner │ Binge-Ready (episodes) │ —         │ 10      │  [x]    │
│ 7 │ Romance & Passion 💕     │ 📱 Vertical 9:16 Poster  │ genre/romance          │ —         │ 10      │  [x]    │
└───┴──────────────────────────┴──────────────────────────┴────────────────────────┴───────────┴─────────┴─────────┘
```

---

## 🎭 1. Card Layout Styles

Each row can render dramas in one of **5 specialized streaming layout styles**:

| Style Key | Display Name | Visual Appearance & Best Use Case |
|---|---|---|
| `hero` | **🎬 Hero Slider (Cinematic Billboard)** | Massive full-width auto-sliding carousel at the top of the page. Displays high-resolution backdrop art, animated title logo, tags, episode badge, and quick **Play Now / Add to List** buttons. |
| `portrait` | **📱 Vertical 9:16 Poster (Standard)** | Classic 9:16 vertical short-form drama cards with subtle hover zoom, rating badge (⭐ 9.8), total episode count badge, and title caption below. Ideal for genre shelves. |
| `top10` | **🔟 TOP 10 (Giant Rank Numbers)** | Netflix/TikTok style row with giant stylized gradient rank numbers (`1` to `10`) placed behind or overlapping each drama poster card. Ideal for *Most Popular* and *Daily Viral*. |
| `leaderboard` | **🏆 Leaderboard (Podium & Ranking Board)** | Top 3 Podium layout (🥇 Gold #1, 🥈 Silver #2, 🥉 Bronze #3) followed by structured rank cards with heat meters and total view counts. |
| `landscape` | **🖥️ Landscape 16:9 Banner** | Wide 16:9 cinematic widescreen cards showing key scene stills or trailer thumbnails with title overlay and progress indicators. |

---

## 🔍 2. Content Sources & Query Algorithms

The **Content Source** dropdown defines the automated database query or filter used to populate the shelf:

### A. 🔥 Sorting & Ranking Algorithms
- **Hero Slider (Top Featured)**: Retrieves dramas explicitly flagged as *Featured Hero* or highest trending score.
- **Leaderboard & Champions**: Ranks titles by comprehensive engagement score (views + coin unlocks + ratings).
- **Most Viewed (`views`)**: Orders dramas descending by total stream view counts.
- **Top Rated (`rating`)**: Orders dramas descending by user rating score (e.g. 9.9, 9.5).
- **New Releases (`latest`)**: Orders by publishing timestamp (`post_date DESC`).
- **Binge-Ready (`episodes`)**: Prioritizes completed short series with high episode counts (e.g. 50+ episodes).
- **VIP Only (`vip`)**: Displays exclusive VIP paywall-gated dramas.

### B. 🎭 Genre & Tag Filters
- `genre/romance` — Romance, Billionaire Love, CEO Affairs
- `genre/fantasy` — Fantasy, Supernatural, Time Travel
- `genre/vampire` — Vampire, Werewolf, Alpha
- `genre/ceo` — Wealthy CEO, Hidden Billionaire, Corporate
- `genre/revenge` — Sweet Revenge, Reborn, Underdog Comeback
- `genre/urban` — Urban Drama, Modern City, Slice of Life
- `genre/historical` — Historical, Martial Arts, Palace Intrigue
- `genre/custom` — Custom user-defined category slug

### C. ▶️ Real-time Personalization
- `continue_watching` — Automatically fetches the logged-in viewer's recent watch history with remaining timestamp progress. Hidden automatically if the user has no history.

---

## ⚡ 3. Endpoint Override & Advanced Customization

- **Endpoint Override**: Allows developers and administrators to specify a custom REST API endpoint, preset identifier, or external JSON source (e.g., `preset://trending_asia` or `api/v1/curated-feed`).
- **Max Items Limit**: Set numeric item limits per shelf (from **5** up to **50** cards). Default is **10**.
- **Enabled Checkbox**: Toggle shelves on or off instantly without deleting the row configuration.

---

## 🛠️ Step-by-Step Row Management

### How to Add a New Shelf
1. Scroll to the bottom of the table and click **`+ Add new row`**.
2. Enter the **Section Title** (e.g. `Billionaire Romances 💎`).
3. Select the desired **Card Layout Style** (`Vertical 9:16 Poster`, `TOP 10`, etc.).
4. Select the **Content Source** (`genre/ceo` or `genre/romance`).
5. Set the **Max Items** limit (e.g. `12`).
6. Ensure the **Enabled** box is checked.
7. Click **`💾 Save configuration`** at the top or bottom toolbar.

### How to Reorder Shelves
1. Click the **Move Up (▲)** or **Move Down (▼)** action buttons on any row to change its vertical position.
2. Click **`💾 Save configuration`** to write changes to the database.

### How to Reset to Factory Defaults
- Click **`🔄 Reset to defaults`** to restore the official ShortTV pre-configured layout (Hero Slider → Top 10 → VIP → Top Rated → New Releases → Genre Rows).

---

## 🔄 Quick Action Bar Summary

```
[ ➕ Add new row ]   [ 🔄 Reset to defaults ]   [ ⚡ Manage endpoint presets ]   [ 💾 Save configuration ]
```

- **`➕ Add new row`**: Appends an empty customizable row.
- **`🔄 Reset to defaults`**: Prompts confirmation and restores out-of-the-box layout.
- **`⚡ Manage endpoint presets`**: Direct shortcut to the **Endpoint Presets Studio** (`admin.php?page=short-presets`).
- **`💾 Save configuration`**: Triggers AJAX write and clears frontend transient cache.
