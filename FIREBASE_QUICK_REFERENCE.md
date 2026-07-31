# 🔥 Firebase Setup - Quick Reference Card

## 📱 App Details

```
Project ID:    marketingpro-ee5cc
Package Name:  com.bulksender.marketingagent
App Name:      BulkSender
```

---

## 🎯 Firebase Console Steps (In Order)

### 1️⃣ Add Flutter App
```
Firebase Console → marketingpro-ee5cc
  ↓
[+ Add app] button
  ↓
Select Flutter icon 🔷
  ↓
Enter package name: com.bulksender.marketingagent
  ↓
[Register app]
```

### 2️⃣ Download Config File
```
[Download google-services.json]
  ↓
Save to: android/app/google-services.json
```

### 3️⃣ Copy Configuration Values
```
From Firebase Console, copy:
- API Key
- App ID  
- Messaging Sender ID
- Project ID
- Storage Bucket
```

### 4️⃣ Enable Google Sign-In
```
Authentication → Sign-in method
  ↓
Google provider
  ↓
[Enable] toggle ON
  ↓
Select support email
  ↓
[Save]
```

### 5️⃣ Add SHA-1 Fingerprint
```
Get SHA-1:
  cd android
  ./gradlew signingReport
  
Add to Firebase:
  Project Settings → Your apps → Android
  ↓
  SHA certificate fingerprints
  ↓
  [Add fingerprint]
  ↓
  Paste SHA-1
  ↓
  [Save]
```

---

## 💻 Code Updates Required

### File 1: `lib/firebase_options.dart`
```dart
static const FirebaseOptions android = FirebaseOptions(
  apiKey: 'PASTE_YOUR_API_KEY_HERE',
  appId: 'PASTE_YOUR_APP_ID_HERE',
  messagingSenderId: 'PASTE_SENDER_ID_HERE',
  projectId: 'marketingpro-ee5cc',
  storageBucket: 'marketingpro-ee5cc.appspot.com',
);
```

### File 2: `android/build.gradle`
```gradle
buildscript {
    dependencies {
        classpath 'com.android.tools.build:gradle:7.3.0'
        classpath "org.jetbrains.kotlin:kotlin-gradle-plugin:$kotlin_version"
        classpath 'com.google.gms:google-services:4.4.0'  // ADD THIS
    }
}
```

### File 3: `android/app/build.gradle`
```gradle
// At the very bottom of file, add:
apply plugin: 'com.google.gms.google-services'
```

### File 4: `android/app/google-services.json`
```
Download from Firebase Console
Place in: android/app/google-services.json
```

---

## 🔧 Terminal Commands

```bash
# 1. Get dependencies
flutter pub get

# 2. Get SHA-1 for Google Sign-In
cd android
./gradlew signingReport
cd ..

# 3. Clean build
flutter clean
flutter pub get

# 4. Build APK
flutter build apk --release

# 5. Install on device
flutter install -d RMX3085
```

---

## ✅ Verification Checklist

### Before Building:
- [ ] google-services.json in android/app/
- [ ] firebase_options.dart updated with real values
- [ ] android/build.gradle has google-services plugin
- [ ] android/app/build.gradle applies plugin
- [ ] Package name matches everywhere

### In Firebase Console:
- [ ] Flutter app registered
- [ ] Google Sign-In enabled
- [ ] SHA-1 fingerprint added
- [ ] Support email selected

### After Building:
- [ ] App installs without errors
- [ ] Firebase initialized (check logs)
- [ ] Google Sign-In works
- [ ] User appears in Firebase Console → Authentication

---

## 🐛 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| "Default FirebaseApp not initialized" | Update firebase_options.dart with real values |
| Google Sign-In fails | Add SHA-1 to Firebase Console |
| "google-services.json not found" | Download and place in android/app/ |
| Build errors | Run: flutter clean && flutter pub get |
| Package name mismatch | Check AndroidManifest.xml matches Firebase |

---

## 📋 Files to Update Summary

```
✅ lib/firebase_options.dart          (Update values)
✅ android/app/google-services.json   (Download & paste)
✅ android/build.gradle                (Add plugin)
✅ android/app/build.gradle            (Apply plugin)
✅ AndroidManifest.xml                 (Package name added ✓)
```

---

## 🎯 Current Status

```
✅ Firebase Core added
✅ Firebase Auth added
✅ Google Sign-In integrated
✅ Package name set: com.bulksender.marketingagent
✅ Main.dart updated with Firebase.initializeApp()
⏳ Waiting for Firebase Console configuration
⏳ Waiting for google-services.json
⏳ Waiting for build.gradle updates
```

---

## 🔗 Quick Links

- **Firebase Console:** https://console.firebase.google.com/project/marketingpro-ee5cc
- **Authentication:** https://console.firebase.google.com/project/marketingpro-ee5cc/authentication
- **Project Settings:** https://console.firebase.google.com/project/marketingpro-ee5cc/settings/general

---

## 💡 Remember

1. **Package Name:** `com.bulksender.marketingagent`
2. **Project ID:** `marketingpro-ee5cc`
3. **SHA-1 Required:** For Google Sign-In to work
4. **Test on Real Device:** Emulator may have issues

---

**Print this card and keep it handy while setting up! 📄**
