# 🎬 + Add New Drama — ShortTV Drama Studio

The **ShortTV Drama Studio** (`WordPress Admin → ShortTV Hub → + Add New Drama` or `post-new.php?post_type=short_title`) is the vertical video management console designed specifically for ReelShort, DramaBox, and TikTok-style episodic mini-series.

It provides direct integration with **Cloudflare R2**, **Gumlet Video**, and **Cloudinary**, allowing 1-click bulk episode uploading, automatic subfolder routing, live 9:16 poster previewing, flexible coin/VIP paywall rules, and JSON schema syncing.

---

## 📍 Where to Find
- Go to **WordPress Admin → ShortTV Hub → + Add New Drama**.
- Direct URL: `wp-admin/post-new.php?post_type=short_title`

---

## 🧭 Interface Overview & Visual Layout

The studio is organized into 3 high-efficiency functional rows:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│  🎬 ShortTV Drama Studio   [ 🟡 Draft Mode / Published ]  [ ID #327 ]  [ ▶ View Watch ]│
│                            [ 💾 Save Draft ]  [ 🚀 Publish Drama ]                     │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ 1. STORAGE & CDN TOOLBAR                                                               │
│    Provider: [ 🟠 Cloudflare R2 ] [ 🟣 Gumlet ] [ 🔵 Cloudinary ]                      │
│    [ ⬆️ Bulk Upload Videos ]   [ 🔢 Auto-Sort by Ep # ]   [ ⚙️ Storage Settings ]      │
│    Folder: [ 📁 short ▾ ] [ 🔄 Refresh ] [ ➕ New Folder ]  ☑ Auto Subfolder: [ Title ] │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ 2. POSTER (9:16) & DRAMA DETAILS                                                       │
│    ┌───────────────┐  ┌───────────────────────────────────────────────────────────┐    │
│    │               │  │  🎬 Drama Title *  (⚠️ Set Title First!)                   │    │
│    │  Drop Poster  │  │  📖 Storyline Synopsis / Overview                         │    │
│    │    (9:16)     │  │  🏷️ Genres (comma separated)    🌐 Language                │    │
│    │               │  │  📅 Release Year                🔢 Total Episodes          │    │
│    └───────────────┘  └───────────────────────────────────────────────────────────┘    │
├────────────────────────────────────────────────────────────────────────────────────────┤
│ 3. EPISODES & STREAM CONFIGURATION                                                     │
│    Rule Preset: [ 🪙 First 5 Free, next Coins ▾ ] [ ⚡ Apply to All ]                  │
│    [ 🔢 Auto-Renumber ]  [ ⚡ Convert to HLS ]  [ 🎬 Use Direct MP4 ]  [ ➕ Add Row ]   │
│    ─────────────────────────────────────────────────────────────────────────────────   │
│    [ Episodes List Table & Stream Links ]                                              │
│    ─────────────────────────────────────────────────────────────────────────────────   │
│    ▶ ⚡ Exact JSON Schema Import & Live Sync (Collapsible)                             │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 🚀 1. Top Action & Command Bar

The sticky header bar provides essential status indicators and publishing triggers:

| Element | ID / Selector | Function |
|---|---|---|
| **Status Badge** | `.shorttv-status-badge` | Displays whether the drama is currently in `Draft Mode` or `Published Live`. |
| **Drama ID** | `ID #327` | Unique numerical database ID assigned to the drama post. |
| **▶ View Live Watch Page** | Link `target="_blank"` | Opens the live 9:16 ReelShort web player for testing playback and coin unlock flows. |
| **💾 Save Draft** | `#btn-studio-save-draft` | Saves all metadata, posters, and episode links to the database without publishing to public feeds. |
| **🚀 Publish Drama** | `#btn-studio-publish` | Instantly publishes the drama to the public homepage, search feeds, and API endpoints. |

---

## ☁️ 2. Row 1: Storage & CDN Toolbar

ShortTV features native multi-cloud storage orchestration with zero coding required:

### A. Provider Switcher
Switch active storage destination tabs with 1 click:
- 🟠 **Cloudflare R2**: High-performance, zero egress fee S3-compatible cloud object storage.
- 🟣 **Gumlet Video**: Specialized HLS video transcoding with automatic DRM & multi-bitrate delivery.
- 🔵 **Cloudinary**: Global cloud media management with video transformations.

### B. Bulk Upload & Sorting Tools
- **`Bulk Upload Videos` Button**: Launches the multi-file selector. Drag and drop 50 to 100+ video files simultaneously (`.mp4`, `.mov`, `.mkv`, `.m3u8`).
- **`🔢 Auto-Sort by Ep #`**: Automatically sorts all episode rows in numerical ascending order based on filename (e.g. `ep01.mp4`, `ep02.mp4` $\rightarrow$ Episode 1, Episode 2).
- **`⚙️ Storage Settings`**: Shortcut to configure API keys and bucket credentials in `admin.php?page=short-video-storage`.

### C. Folder Tree Picker & Auto-Subfolder
- **Collapsible Tree Dropdown**: Visual folder browser displaying remote buckets and nested directories.
- **`🔄 Refresh`**: Fetches newly created folders directly from the cloud provider API.
- **`➕ New Folder`**: Creates a new folder on the remote cloud storage.
- **`☑ Auto Subfolder`**: Automatically creates a dedicated subfolder matching the drama title (e.g., `short / Drama Title`).

> [!IMPORTANT]
> **Step 1: Set Drama Title First!**
> Always type the **Drama Title** *before* clicking Bulk Upload. This guarantees the automatic subfolder generator organizes your video files into their own isolated folder in Cloudflare R2 / Gumlet.

---

## 🎨 3. Row 2: Poster (9:16) & Drama Metadata

The two-column grid provides dedicated spaces for vertical artwork and streaming metadata:

### Left Column: 9:16 Vertical Poster
- **Dropzone Area**: Drag and drop any image file (`.webp`, `.jpg`, `.png`).
- **Upload Progress Bar**: Visual loading animation during cloud upload.
- **Upload & Media Library**:
  - `Upload`: Uploads directly from local computer.
  - `Media`: Selects an existing image from WordPress Media Library.
  - `Image URL`: Allows pasting external CDN image URLs.

### Right Column: Drama Details & Metadata
- **🎬 Drama Title (*Required)**: The primary title displayed on posters, carousels, and search.
- **📖 Storyline Synopsis / Overview**: Captivating summary or drama hook.
- **🏷️ Genres (comma separated)**: Tag keywords (e.g. `Billionaire, Romance, CEO, Sweet Revenge`).
- **🌐 Language**: Audio and subtitle language (e.g. `English`, `Mandarin`, `Spanish`).
- **Release Year**: Year of release (default `2026`).
- **Total Episodes**: Total count of episodes in the series (e.g. `60` or `100`).

---

## ⚡ 4. Row 3: Episodes & Stream Configuration

### A. Bulk Access & Pricing Rule Presets

Quickly apply monetization and paywall rules across the entire drama with 1 click:

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│  Rule: [ 🪙 First 5 Free, next Coins ▾ ]   [ ⚡ Apply to All ]                         │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

| Rule Preset | Mode Key | Description & Strategy |
|---|---|---|
| **🪙 First 5 Free, next Coins** *(Recommended)* | `coins_5free` | Hook viewers with episodes 1 to 5 completely free, then require coins (e.g. 5-10 coins) for episode 6 onwards. |
| **👑 All Episodes VIP** | `vip_all` | Restricts every episode to active VIP / Monthly subscribers. |
| **🟢 All Episodes Free** | `free_all` | Opens all episodes 100% free with no coins or login required. |
| **🪙 All Episodes Coins** | `coins_all` | Every single episode requires coins to unlock. |

### B. Episode Stream URL Format Conversion
- **`⚡ Convert MP4 to HLS`**: Automatically swaps `.mp4` URLs to adaptive bitrate `.m3u8` HLS streaming endpoints.
- **`🎬 Use Direct MP4 (.mp4)`**: Switches all episode streams back to direct MP4 file URLs for Cloudflare R2 or direct CDN playback.
- **`🔢 Auto-Renumber (1 to N)`**: Cleans up and re-sequences episode numbers sequentially.

---

## 📋 5. Collapsible JSON Schema Import & Live Sync

For programmatic integrations, automated scrapers, and mass migrations, expand the **⚡ Exact JSON Schema Import & Live Sync** drawer:

```json
{
    "media_id": "short_327",
    "type": "short_series",
    "title": "A Marriage Deal with the Billionaire",
    "slug": "a-marriage-deal-with-the-billionaire",
    "is_published": true,
    "release_year": 2026,
    "total_episodes": 60,
    "language": "English",
    "genres": ["Billionaire", "Romance", "Revenge"],
    "overview": "When her family betrayed her, she married the most ruthless billionaire...",
    "cover_assets": {
        "vertical_poster": "https://cdn.example.com/posters/drama_327.webp",
        "horizontal_banner": "https://cdn.example.com/banners/drama_327.webp"
    },
    "analytics": {
        "view_count": 1420000,
        "like_count": 89000,
        "bookmark_count": 12500
    },
    "episodes": [
        {
            "episode_number": 1,
            "title": "Episode 1: The Unexpected Proposal",
            "stream_url": "https://cdn.example.com/episodes/ep1.mp4",
            "access_type": "free",
            "coins_required": 0
        },
        {
            "episode_number": 6,
            "title": "Episode 6: The Secret Unveiled",
            "stream_url": "https://cdn.example.com/episodes/ep6.mp4",
            "access_type": "coins",
            "coins_required": 10
        }
    ]
}
```

- **`📋 Copy JSON`**: Exports the entire drama structure to clipboard.
- **`⚡ Sync Form from JSON`**: Parses pasted JSON and instantly populates the title, synopsis, poster, genres, and all episode rows automatically.
