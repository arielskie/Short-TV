# 🔥 Firebase Configuration & Realtime Database

Configure authentication, real-time multi-profile synchronization, watch history, bookmarking, and FCM push notifications.

---

## 📍 Where to Find
Go to **WordPress Admin → ShortTV Hub → Settings → Firebase Configuration** tab.

---

## 🛠️ Required Configuration Fields

```
Firebase Project Settings
 ├── Web App SDK Snippet (apiKey, authDomain, projectId, storageBucket, messagingSenderId, appId)
 ├── Realtime Database URL (Asia / US cluster)
 ├── Firebase Service Account JSON (For FCM HTTP v1 Live Push)
 └── Web Push VAPID Key (Public Key for browser permission tokens)
```

| Field Name | Source in Firebase Console | Purpose |
|---|---|---|
| **API Key (`apiKey`)** | Project Settings → General → Your apps → SDK setup | Client-side Firebase SDK authentication. |
| **Auth Domain (`authDomain`)** | Project Settings → General → Your apps | Handles Google / Email sign-in popups and redirects. |
| **Project ID (`projectId`)** | Project Settings → General | Identifies your Firebase project. |
| **Storage Bucket (`storageBucket`)** | Project Settings → General | Optional storage bucket. |
| **Messaging Sender ID** | Project Settings → Cloud Messaging | FCM Sender ID for push notification messaging. |
| **App ID (`appId`)** | Project Settings → General → Your apps | Unique Web App ID. |
| **Firebase Service Account JSON** | Project Settings → Service accounts → Generate new private key | Modern **HTTP v1 API** credential for sending push alerts directly from WordPress backend. |
| **Web Push VAPID Key** | Project Settings → Cloud Messaging → Web configuration | Public VAPID key used by client browsers to generate push tokens. |

---

## 📋 Step-by-Step Setup Guide
1. Open [Firebase Console](https://console.firebase.google.com/) and select or create your project.
2. Enable **Authentication** (Google Sign-In + Email/Password).
3. Enable **Realtime Database** (Set rules to allow read/write for authenticated users).
4. In **Project Settings → Service Accounts**, click **Generate new private key** and copy the full JSON file contents into the **Firebase Service Account JSON** field.
5. In **Project Settings → Cloud Messaging → Web configuration**, copy the **Web Push certificates (Key pair)** into **Web Push VAPID Key**.
6. Click **Save Firebase Configuration**.
