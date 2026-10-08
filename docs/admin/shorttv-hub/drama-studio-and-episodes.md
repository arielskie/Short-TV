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

### Step 1: ✍️ Enter Drama Title (CRITICAL FIRST STEP)
* **Always type your Drama Title first** (e.g., *Guardian of the Forbidden Flame*).
> [!IMPORTANT]
> **Why Title First?**
> The cloud uploader uses your Drama Title to automatically name and create your cloud storage folder (e.g. `short/Guardian of the Forbidden Flame/`). If you upload before setting a title, the video files will be uploaded outside into the root bucket directory without a folder name.

---

### Step 2: ☁️ Select Storage Provider & Verify Folder
* Select your active storage provider (**Cloudflare R2**, **Gumlet**, or **Cloudinary**).
* Ensure **Auto Subfolder** is checked so it targets `short/[Drama-Title]/`.

---

### Step 3: 🚀 Bulk Upload & Auto-Sort Episodes
* Click **Bulk Upload Videos (R2)** and drop all your video files (`ep1.mp4`, `ep2.mp4`, etc.).
* Click **Auto-Sort by Ep #** to sequentially order all uploaded clips from Episode 1 to N.

---

### Step 4: 💎 Configure Episode Paywall Rules
* Use the quick rule selector: `First 5 Free, next Coins` (or customize per episode).
* Click **Apply to All** to instantly lock episode 6 onwards behind coin/VIP access.

---

### Step 5: 🖼️ Add 9:16 Artwork & Storyline Synopsis
* Upload a high-resolution 9:16 vertical poster image (`1080x1920` or `720x1280` px).
* Enter the drama storyline synopsis and select genres (*Billionaire, Romance, Urban, Fantasy, Revenge*).

---

### Step 6: 🚀 Publish Drama
* Click the blue **Publish Drama** button in the top-right corner. The drama is immediately live on your homepage and discover feeds!

