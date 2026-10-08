# 🔥 Complete Firebase & FCM HTTP v1 Setup Guide

Detailed instructions for setting up Google Firebase, enabling Realtime Database, obtaining Service Account JSON credentials, generating Web Push VAPID keys, and sending push notifications.

---

## 🛠️ Step 1: Create Your Firebase Project
1. Visit the [Google Firebase Console](https://console.firebase.google.com/).
2. Click **Add project**, name it (e.g. `shorttv-project`), and complete creation.

---

## 🔑 Step 2: Register Web App & Get Web SDK Config
1. On Project Overview, click the **Web icon (`</>`)** to add a Web App.
2. Register app name (e.g. `ShortTV Web`).
3. Copy the configuration object:
   * `apiKey`
   * `authDomain`
   * `projectId`
   * `storageBucket`
   * `messagingSenderId`
   * `appId`

---

## 📂 Step 3: Enable Authentication & Realtime Database
1. In the left sidebar under **Build**:
   * **Authentication:** Click *Get Started*, enable **Google** provider and **Email/Password**.
   * **Realtime Database:** Click *Create Database*, choose location (e.g. `asia-southeast1` or `us-central1`), and start in test/production mode with read/write access.

---

## 🔔 Step 4: Generate Service Account JSON & Web Push Key
1. Go to **Project Settings (⚙️) → Service Accounts** tab:
   * Click **Generate new private key** and confirm.
   * A `.json` credential file will download to your computer.
2. Go to **Project Settings → Cloud Messaging** tab:
   * Scroll down to **Web configuration**.
   * Under **Web Push certificates**, click **Generate key pair** to generate your public VAPID key (e.g. starting with `BPsh...`).

---

## ⚙️ Step 5: Paste Credentials in WordPress
1. In your **WordPress Admin**, go to **ShortTV Hub → Settings → Firebase Configuration**.
2. Fill in the Web SDK fields (`apiKey`, `authDomain`, `projectId`, etc.).
3. Open your downloaded `.json` file in a text editor, copy all text, and paste into **Firebase Service Account JSON**.
4. Paste your Web Push public key into **Web Push VAPID Key**.
5. Click **Save Firebase Configuration**.
6. Your Notification Center will display `✓ FCM HTTP v1 Active (Service Account)`.
