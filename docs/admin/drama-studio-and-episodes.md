# 🎬 ShortTV Drama Studio & Episode Builder Guide

The **ShortTV Drama Studio** is a purpose-built workspace engineered for fast publishing of episodic vertical reels, batch video uploads, automatic metadata generation, and granular paywall access rules.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Dramas → Add New Drama** (or click Edit on an existing drama).

---

## 🛠️ Studio Workspace Overview

```
Drama Studio Architecture
 ├── 1. Top Action Bar (Draft / Published status, Live Watch Preview, Publish button)
 ├── 2. Cloud Storage & R2 Folder Manager (Direct bucket uploading & folder creation)
 ├── 3. Batch Tools (Bulk Drag-and-Drop Video Uploader, Auto-Sort by Ep #)
 ├── 4. Key-Art Drop Zone (9:16 Vertical Poster, Horizontal Backdrop, Title Logo)
 ├── 5. Drama Metadata (Title, Synopsis, Genres, Language, Release Year)
 └── 6. Episode Stream Pipeline (Per-episode Video URLs, Free/Coin status, HLS/MP4 toggle)
```

---

## 📋 Step-by-Step Drama Publishing Workflow

### Step 1: Select Storage Provider & Folder
* Select your active storage provider (**Cloudflare R2**, **Gumlet**, or **Cloudinary**).
* Check **Auto Subfolder** so uploads are automatically organized into `short/[Drama-Title]/`.

### Step 2: Bulk Upload Episodes
* Click **Bulk Upload Videos (R2)** and drop all your video files (`ep1.mp4`, `ep2.mp4`, etc.).
* Click **Auto-Sort by Ep #** to sequentially order all uploaded clips from Episode 1 to N.

### Step 3: Configure Episode Paywall Rules
* Use the quick rule selector: `First 5 Free, next Coins` (or customize).
* Click **Apply to All** to instantly lock episode 6 onwards behind coin/VIP access.

### Step 4: Add Artwork & Details
* Upload a high-resolution 9:16 vertical poster image (`1080x1920` or `720x1280` px).
* Enter the drama storyline synopsis and genres (*Billionaire, Romance, Urban, Fantasy, Revenge*).

### Step 5: Publish
* Click the blue **Publish Drama** button in the top-right corner. The drama is immediately live on your homepage and discover feeds!
