# Firebase Setup Guide

## ✅ Current Status

Firebase Core has been added to the project with placeholder configuration.

### 📦 Dependencies Added:
```yaml
firebase_core: ^3.8.1
firebase_auth: ^5.3.4
```

### 📁 Files Created:
- `lib/firebase_options.dart` - Firebase configuration file

### 🔧 Main.dart Updated:
```dart
await Firebase.initializeApp(
  options: DefaultFirebaseOptions.currentPlatform,
);
```

## 🚀 Complete Firebase Setup Steps

### Step 1: Get Firebase Configuration

1. **Go to Firebase Console:**
   - Visit: https://console.firebase.google.com/
   - Select project: `marketingpro-ee5cc`

2. **For Android App:**
   - Click "Add app" → Android
   - Package name: `com.example.autoreply` (check AndroidManifest.xml)
   - Download `google-services.json`
   - Place in: `android/app/google-services.json`

3. **Get Configuration Values:**
   - Go to Project Settings → General
   - Scroll to "Your apps"
   - Click on Android app
   - Copy these values:
     - API Key
     - App ID
     - Messaging Sender ID
     - Project ID
     - Storage Bucket

### Step 2: Update firebase_options.dart

Replace placeholder values in `lib/firebase_options.dart`:

```dart
static const FirebaseOptions android = FirebaseOptions(
  apiKey: 'YOUR_ACTUAL_API_KEY',
  appId: 'YOUR_ACTUAL_APP_ID',
  messagingSenderId: 'YOUR_MESSAGING_SENDER_ID',
  projectId: 'marketingpro-ee5cc',
  storageBucket: 'marketingpro-ee5cc.appspot.com',
);
```

### Step 3: Update Android Configuration

1. **Add google-services plugin to `android/build.gradle`:**
```gradle
buildscript {
    dependencies {
        classpath 'com.google.gms:google-services:4.4.0'
    }
}
```

2. **Apply plugin in `android/app/build.gradle`:**
```gradle
apply plugin: 'com.google.gms.google-services'
```

### Step 4: Enable Google Sign-In in Firebase

1. Go to Firebase Console
2. Authentication → Sign-in method
3. Enable "Google" provider
4. Add your SHA-1 certificate fingerprint

**Get SHA-1:**
```bash
cd android
./gradlew signingReport
```

### Step 5: Test Firebase Connection

Run the app and check logs for:
```
✅ Firebase initialized successfully
```

## 🔐 Google Sign-In with Firebase

### Update GoogleAuthService to use Firebase Auth:

```dart
import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';

class GoogleAuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final GoogleSignIn _googleSignIn = GoogleSignIn();

  Future<User?> signInWithGoogle() async {
    final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
    
    if (googleUser == null) return null;
    
    final GoogleSignInAuthentication googleAuth = 
        await googleUser.authentication;
    
    final credential = GoogleAuthProvider.credential(
      accessToken: googleAuth.accessToken,
      idToken: googleAuth.idToken,
    );
    
    final UserCredential userCredential = 
        await _auth.signInWithCredential(credential);
    
    return userCredential.user;
  }
  
  Future<void> signOut() async {
    await _auth.signOut();
    await _googleSignIn.signOut();
  }
}
```

## 📱 Current Implementation

### Without Firebase (Current):
- ✅ Google Sign-In working
- ✅ Local data storage (SharedPreferences)
- ✅ Auto-login functionality
- ❌ No backend authentication
- ❌ No user data sync

### With Firebase (After Setup):
- ✅ Google Sign-In with Firebase Auth
- ✅ Backend authentication
- ✅ User data sync across devices
- ✅ Secure token management
- ✅ Cloud Firestore integration (optional)

## 🎯 Next Steps

1. **Get actual Firebase config values**
2. **Update firebase_options.dart**
3. **Add google-services.json**
4. **Update build.gradle files**
5. **Enable Google Sign-In in Firebase Console**
6. **Add SHA-1 fingerprint**
7. **Test Firebase connection**
8. **Update GoogleAuthService to use Firebase Auth**

## 📝 Notes

- Current setup uses placeholder values
- App will work with Google Sign-In (without Firebase backend)
- For full Firebase integration, complete all setup steps
- Firebase provides better security and data sync

## 🐛 Troubleshooting

### Firebase initialization failed:
- Check firebase_options.dart has correct values
- Verify google-services.json is in correct location
- Check internet connection

### Google Sign-In not working:
- Add SHA-1 fingerprint to Firebase Console
- Enable Google provider in Authentication
- Check package name matches

### Build errors:
- Run `flutter clean`
- Run `flutter pub get`
- Rebuild the app

## 🔗 Useful Links

- Firebase Console: https://console.firebase.google.com/
- FlutterFire Documentation: https://firebase.flutter.dev/
- Firebase Auth: https://firebase.flutter.dev/docs/auth/overview
- Google Sign-In: https://pub.dev/packages/google_sign_in

## ✨ Summary

Firebase Core has been added with placeholder configuration. To complete setup:
1. Get actual config from Firebase Console
2. Update firebase_options.dart
3. Add google-services.json
4. Update build.gradle files
5. Test and verify

Current app works with Google Sign-In using local storage. Firebase integration will add backend authentication and cloud sync capabilities.
