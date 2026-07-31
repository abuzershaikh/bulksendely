import 'dart:async';

import 'package:autoreply/features/subscription/services/subscription_service.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:shared_preferences/shared_preferences.dart';

class GoogleAuthService {
  static final GoogleAuthService _instance = GoogleAuthService._internal();
  factory GoogleAuthService() => _instance;
  GoogleAuthService._internal();

  final FirebaseAuth _firebaseAuth = FirebaseAuth.instance;
  final GoogleSignIn _googleSignIn = GoogleSignIn.instance;
  bool _googleInitialized = false;

  GoogleSignInAccount? _currentUser;

  GoogleSignInAccount? get currentUser => _currentUser;
  bool get isSignedIn =>
      _currentUser != null || _firebaseAuth.currentUser != null;

  Future<bool> initialize() async {
    try {
      final firebaseUser = _firebaseAuth.currentUser;
      if (firebaseUser == null) {
        return false;
      }

      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool('google_signed_in', true);
      await prefs.setString('user_email', firebaseUser.email ?? '');
      await prefs.setString('user_name', firebaseUser.displayName ?? '');
      await prefs.setString('user_photo', firebaseUser.photoURL ?? '');
      unawaited(_syncUserProfileSafely());
      return true;
    } catch (_) {
      return false;
    }
  }

  Future<GoogleSignInAccount?> signIn() async {
    await _ensureGoogleInitialized();
    if (!_googleSignIn.supportsAuthenticate()) {
      throw Exception('Google Sign-In is not supported on this platform.');
    }

    _currentUser = await _googleSignIn.authenticate();
    await _signInToFirebase(_currentUser!);
    await _saveLocalUser(_currentUser!);
    unawaited(_syncUserProfileSafely());
    return _currentUser;
  }

  Future<void> signOut() async {
    await _googleSignIn.signOut();
    await _firebaseAuth.signOut();
    _currentUser = null;
    await SubscriptionService.instance.clearLocalState();

    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('google_signed_in', false);
    await prefs.remove('user_email');
    await prefs.remove('user_name');
    await prefs.remove('user_photo');
  }

  Future<Map<String, String>> getUserInfo() async {
    final prefs = await SharedPreferences.getInstance();
    return {
      'email':
          prefs.getString('user_email') ??
          _firebaseAuth.currentUser?.email ??
          '',
      'name':
          prefs.getString('user_name') ??
          _firebaseAuth.currentUser?.displayName ??
          '',
      'photo':
          prefs.getString('user_photo') ??
          _firebaseAuth.currentUser?.photoURL ??
          '',
    };
  }

  Future<void> disconnect() async {
    await _googleSignIn.disconnect();
    await _firebaseAuth.signOut();
    _currentUser = null;
    await SubscriptionService.instance.clearLocalState();

    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }

  Future<void> _signInToFirebase(GoogleSignInAccount account) async {
    final googleAuth = account.authentication;
    final idToken = googleAuth.idToken;
    if (idToken == null || idToken.isEmpty) {
      throw Exception('Google ID token not received.');
    }

    final credential = GoogleAuthProvider.credential(idToken: idToken);
    await _firebaseAuth.signInWithCredential(credential);
  }

  Future<void> _ensureGoogleInitialized() async {
    if (_googleInitialized) {
      return;
    }
    await _googleSignIn.initialize();
    _googleInitialized = true;
  }

  Future<void> _saveLocalUser(GoogleSignInAccount account) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('google_signed_in', true);
    await prefs.setString('user_email', account.email);
    await prefs.setString('user_name', account.displayName ?? '');
    await prefs.setString('user_photo', account.photoUrl ?? '');
  }

  Future<void> _syncUserProfile() async {
    final user = _firebaseAuth.currentUser;
    if (user == null) {
      return;
    }

    await SubscriptionService.instance.syncUserProfile(
      uid: user.uid,
      email: user.email ?? _currentUser?.email ?? '',
      name: user.displayName ?? _currentUser?.displayName ?? '',
      photoUrl: user.photoURL ?? _currentUser?.photoUrl ?? '',
    );
    await SubscriptionService.instance.syncPlanFromPanel();
    await SubscriptionService.instance.ensureWaziperAccessToken();
    await SubscriptionService.instance.initialize();
  }

  Future<void> _syncUserProfileSafely() async {
    try {
      await _syncUserProfile();
    } catch (_) {
      // Keep auth flow responsive even if sync endpoints are slow/unavailable.
    }
  }
}
