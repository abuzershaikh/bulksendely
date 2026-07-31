import 'dart:async';

import 'package:autoreply/core/network/api_client.dart';
import 'package:autoreply/features/subscription/models/app_user_subscription.dart';
import 'package:autoreply/features/subscription/models/subscription_plan.dart';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/foundation.dart';

class SubscriptionAccessException implements Exception {
  final String message;

  const SubscriptionAccessException(this.message);

  @override
  String toString() => message;
}

class SubscriptionService {
  SubscriptionService._internal();

  static final SubscriptionService instance = SubscriptionService._internal();
  static const String usersCollection = 'users';
  static const String subscriptionCustomersCollection =
      'subscription_customers';
  static const Duration _trialDuration = Duration(days: 1);

  final FirebaseAuth _auth = FirebaseAuth.instance;
  final FirebaseFirestore _firestore = FirebaseFirestore.instance;
  final ValueNotifier<AppUserSubscription?> currentUserNotifier =
      ValueNotifier<AppUserSubscription?>(null);

  StreamSubscription<DocumentSnapshot<Map<String, dynamic>>>? _userSub;

  AppUserSubscription? get currentUser => currentUserNotifier.value;
  bool get isPremiumUser => currentUser?.isPremium ?? false;

  DateTime? _resolvePanelActivationTime(
    Map<String, dynamic>? panelData,
    Map<String, dynamic>? existing,
  ) {
    DateTime? parseDate(dynamic value) {
      if (value is Timestamp) {
        return value.toDate();
      }
      if (value is String && value.isNotEmpty) {
        return DateTime.tryParse(value)?.toLocal();
      }
      return null;
    }

    return parseDate(panelData?['activatedAt']) ??
        parseDate(panelData?['premiumActivatedAt']) ??
        parseDate(existing?['premiumActivatedAt']);
  }

  Future<void> initialize() async {
    final user = _auth.currentUser;
    if (user == null) {
      await clearLocalState();
      return;
    }

    await syncUserProfile(
      uid: user.uid,
      email: user.email ?? '',
      name: user.displayName ?? '',
      photoUrl: user.photoURL ?? '',
    );
    _listenToUser(user.uid);
  }

  Future<void> clearLocalState() async {
    await _userSub?.cancel();
    _userSub = null;
    currentUserNotifier.value = null;
  }

  Future<void> syncUserProfile({
    required String uid,
    required String email,
    required String name,
    required String photoUrl,
  }) async {
    final panelData = await _fetchPanelSubscriptionByEmail(email);
    final doc = _userDoc(uid);
    final snapshot = await doc.get();
    final existing = snapshot.data();
    final emailTrialData = await _fetchTrialStateByEmail(
      email,
      excludeUid: uid,
    );

    final resolvedPlanValue =
        panelData?['plan']?.toString() ?? existing?['plan']?.toString();
    final resolvedStatusValue =
        panelData?['subscriptionStatus']?.toString() ??
        existing?['subscriptionStatus']?.toString();
    final plan = subscriptionPlanFromString(resolvedPlanValue);
    final status = subscriptionStatusFromString(resolvedStatusValue);
    final premiumActivatedAt = _resolvePanelActivationTime(panelData, existing);

    final model = AppUserSubscription(
      uid: uid,
      email: email,
      name: name,
      photoUrl: photoUrl,
      plan: plan,
      status:
          plan == SubscriptionPlan.free && status == SubscriptionStatus.inactive
          ? SubscriptionStatus.active
          : status,
      totalMessagesSent: (existing?['totalMessagesSent'] as num?)?.toInt() ?? 0,
      freeMessagesUsed: (existing?['freeMessagesUsed'] as num?)?.toInt() ?? 0,
      subscriptionExpiresAt:
          panelData?['expiresAt']?.toString() ??
          existing?['subscriptionExpiresAt']?.toString() ??
          '',
      premiumActivatedAt: premiumActivatedAt,
      trialStartedAt:
          _resolveDate(existing?['trialStartedAt']) ??
          _resolveDate(emailTrialData?['trialStartedAt']),
      trialEndsAt:
          _resolveDate(existing?['trialEndsAt']) ??
          _resolveDate(emailTrialData?['trialEndsAt']),
      trialOver:
          existing?['trialOver'] == true || emailTrialData?['trialOver'] == true,
      trialStatusMessage:
          (existing?['trialStatusMessage']?.toString() ?? '').isNotEmpty
          ? existing!['trialStatusMessage'].toString()
          : emailTrialData?['trialStatusMessage']?.toString() ?? '',
      currentSessionId: existing?['currentSessionId']?.toString() ?? '',
      sessionCount: (existing?['sessionCount'] as num?)?.toInt() ?? 0,
      waziperAccessToken: existing?['waziperAccessToken']?.toString() ?? '',
      waziperTeamId: existing?['waziperTeamId']?.toString() ?? '',
      waziperUserId: existing?['waziperUserId']?.toString() ?? '',
      waziperUsername: existing?['waziperUsername']?.toString() ?? '',
      waziperProvisionedAt: _resolveDate(existing?['waziperProvisionedAt']),
      createdAt: null,
      updatedAt: null,
    );

    final trialApplied = _applyTrialPolicy(model);
    await doc.set(trialApplied.toFirestore(), SetOptions(merge: true));
    currentUserNotifier.value = trialApplied;
  }

  Future<AppUserSubscription?> syncPlanFromPanel() async {
    final user = _auth.currentUser;
    if (user == null) {
      return null;
    }

    await syncUserProfile(
      uid: user.uid,
      email: user.email ?? '',
      name: user.displayName ?? '',
      photoUrl: user.photoURL ?? '',
    );
    return currentUserNotifier.value;
  }

  Future<String> ensureWaziperAccessToken() async {
    final current = currentUser;
    if (current != null &&
        current.waziperAccessToken.isNotEmpty &&
        await _isWaziperAccessTokenValid(current.waziperAccessToken)) {
      return current.waziperAccessToken;
    }

    final user = _requireUser();
    final snapshot = await _userDoc(user.uid).get();
    final existing = snapshot.data();
    final existingToken = existing?['waziperAccessToken']?.toString() ?? '';
    if (existingToken.isNotEmpty &&
        await _isWaziperAccessTokenValid(existingToken)) {
      final model = AppUserSubscription.fromFirestore(existing!);
      currentUserNotifier.value = model;
      return existingToken;
    }

    final provisioned = await _provisionWaziperWorkspace(
      uid: user.uid,
      email: user.email ?? '',
      name: user.displayName ?? '',
    );
    return provisioned['access_token']?.toString() ?? '';
  }

  Future<bool> _isWaziperAccessTokenValid(String token) async {
    final trimmed = token.trim();
    if (trimmed.isEmpty) {
      return false;
    }

    try {
      final response = await ApiClient.get('api/instances', {
        'access_token': trimmed,
      });
      if (response['status']?.toString() == 'success') {
        return true;
      }

      final message = response['message']?.toString().toLowerCase() ?? '';
      if (message.contains('invalid access_token') ||
          message.contains('authentication failed') ||
          message.contains('access token does not exist') ||
          message.contains('does not exist') ||
          message.contains('invalidated')) {
        return false;
      }
    } catch (_) {
      return false;
    }

    return false;
  }

  Future<void> updatePlan({
    required SubscriptionPlan plan,
    required SubscriptionStatus status,
    String? expiresAt,
  }) async {
    final user = _requireUser();
    await _userDoc(user.uid).set({
      'plan': plan.firestoreValue,
      'subscriptionStatus': status.firestoreValue,
      if (expiresAt != null) 'subscriptionExpiresAt': expiresAt,
      'premiumActivatedAt': FieldValue.serverTimestamp(),
      'updatedAt': FieldValue.serverTimestamp(),
    }, SetOptions(merge: true));
  }

  Future<void> updateWhatsappSession({required String sessionId}) async {
    final user = _requireUser();
    final data = currentUser;
    final shouldCountNewSession =
        data == null || data.currentSessionId != sessionId;

    await _userDoc(user.uid).set({
      'currentSessionId': sessionId,
      'sessionCount': shouldCountNewSession
          ? FieldValue.increment(1)
          : data.sessionCount,
      'updatedAt': FieldValue.serverTimestamp(),
    }, SetOptions(merge: true));
  }

  Future<void> clearWhatsappSession() async {
    final user = _requireUser();
    await _userDoc(user.uid).set({
      'currentSessionId': '',
      'sessionCount': 0,
      'updatedAt': FieldValue.serverTimestamp(),
    }, SetOptions(merge: true));
  }

  Future<void> ensureDirectMessageAccess(int requestedCount) async {
    // Premium removed — all users have unlimited access.
    return;
  }

  Future<void> ensurePremiumAccess({
    String message = 'This is a premium feature.',
  }) async {
    // Premium removed — all users have full access.
    return;
  }

  Future<void> recordSuccessfulSend(int successfulCount) async {
    if (successfulCount <= 0) {
      return;
    }

    final user = _requireUser();
    final data = _normalizeUnrestrictedAccess(await _ensureLoadedUser());
    final update = <String, dynamic>{
      'totalMessagesSent': FieldValue.increment(successfulCount),
      'freeMessagesUsed': 0,
      'freeMessagesLeft': AppUserSubscription.freeMessageLimit,
      'trialOver': false,
      'trialStatusMessage': '',
      'plan': SubscriptionPlan.lifetime.firestoreValue,
      'subscriptionStatus': SubscriptionStatus.active.firestoreValue,
      'updatedAt': FieldValue.serverTimestamp(),
    };

    await _userDoc(user.uid).set(update, SetOptions(merge: true));
    currentUserNotifier.value = _normalizeUnrestrictedAccess(
      data.copyWith(
        totalMessagesSent: data.totalMessagesSent + successfulCount,
        freeMessagesUsed: 0,
        updatedAt: DateTime.now(),
      ),
    );
  }

  Future<AppUserSubscription> _ensureLoadedUser() async {
    final current = currentUser;
    if (current != null) {
      return current;
    }

    final user = _requireUser();
    final snapshot = await _userDoc(user.uid).get();
    final data = snapshot.data();
    if (data == null) {
      await syncUserProfile(
        uid: user.uid,
        email: user.email ?? '',
        name: user.displayName ?? '',
        photoUrl: user.photoURL ?? '',
      );
      return currentUserNotifier.value!;
    }

    final model = _applyTrialPolicy(AppUserSubscription.fromFirestore(data));
    final trialPayload = <String, dynamic>{
      'trialOver': model.trialOver,
      'trialStatusMessage': model.trialStatusMessage,
      'updatedAt': FieldValue.serverTimestamp(),
    };
    if (model.trialStartedAt != null) {
      trialPayload['trialStartedAt'] = Timestamp.fromDate(model.trialStartedAt!);
    }
    if (model.trialEndsAt != null) {
      trialPayload['trialEndsAt'] = Timestamp.fromDate(model.trialEndsAt!);
    }
    await _userDoc(user.uid).set(trialPayload, SetOptions(merge: true));
    currentUserNotifier.value = _normalizeUnrestrictedAccess(model);
    _listenToUser(user.uid);
    return _normalizeUnrestrictedAccess(model);
  }

  DateTime? _resolveDate(dynamic value) {
    if (value is Timestamp) {
      return value.toDate();
    }
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value)?.toLocal();
    }
    return null;
  }

  Future<Map<String, dynamic>> _provisionWaziperWorkspace({
    required String uid,
    required String email,
    required String name,
  }) async {
    if (email.trim().isEmpty) {
      throw const SubscriptionAccessException(
        'Email is required to prepare your WhatsApp workspace.',
      );
    }

    final response =
        await ApiClient.postToAdmin('admin_api/provision_waziper_user', {
          'api_key': ApiClient.adminApiKey,
          'uid': uid,
          'email': email.trim().toLowerCase(),
          'name': name.trim(),
        });

    if (response['status'] != 'success') {
      throw SubscriptionAccessException(
        response['message']?.toString() ??
            'Failed to prepare your WhatsApp workspace.',
      );
    }

    final data = Map<String, dynamic>.from(
      (response['data'] as Map?) ?? const {},
    );
    await _userDoc(uid).set({
      'waziperAccessToken': data['access_token']?.toString() ?? '',
      'waziperTeamId': data['team_id']?.toString() ?? '',
      'waziperUserId': data['waziper_user_id']?.toString() ?? '',
      'waziperUsername': data['username']?.toString() ?? '',
      'waziperProvisionedAt': FieldValue.serverTimestamp(),
      'updatedAt': FieldValue.serverTimestamp(),
    }, SetOptions(merge: true));

    final refreshed = await _userDoc(uid).get();
    final refreshedData = refreshed.data();
    if (refreshedData != null) {
      currentUserNotifier.value = _normalizeUnrestrictedAccess(
        AppUserSubscription.fromFirestore(refreshedData),
      );
    }

    return data;
  }

  DocumentReference<Map<String, dynamic>> _userDoc(String uid) {
    return _firestore.collection(usersCollection).doc(uid);
  }

  Future<Map<String, dynamic>?> _fetchPanelSubscriptionByEmail(
    String email,
  ) async {
    final normalizedEmail = email.trim().toLowerCase();
    if (normalizedEmail.isEmpty) {
      return null;
    }

    final query = await _firestore
        .collection(subscriptionCustomersCollection)
        .where('email', isEqualTo: normalizedEmail)
        .limit(1)
        .get();

    if (query.docs.isEmpty) {
      return null;
    }

    return query.docs.first.data();
  }

  Future<Map<String, dynamic>?> _fetchTrialStateByEmail(
    String email, {
    String? excludeUid,
  }) async {
    final normalizedEmail = email.trim().toLowerCase();
    if (normalizedEmail.isEmpty) {
      return null;
    }

    final query = await _firestore
        .collection(usersCollection)
        .where('email', isEqualTo: normalizedEmail)
        .get();

    if (query.docs.isEmpty) {
      return null;
    }

    Map<String, dynamic>? best;
    DateTime? bestStart;

    for (final doc in query.docs) {
      if (excludeUid != null && doc.id == excludeUid) {
        continue;
      }
      final data = doc.data();
      final start = _resolveDate(data['trialStartedAt']);
      if (best == null) {
        best = data;
        bestStart = start;
        continue;
      }

      final bestIsOver = best['trialOver'] == true;
      final currentIsOver = data['trialOver'] == true;
      if (currentIsOver && !bestIsOver) {
        best = data;
        bestStart = start;
        continue;
      }

      if (start != null && (bestStart == null || start.isBefore(bestStart))) {
        best = data;
        bestStart = start;
      }
    }

    return best;
  }

  User _requireUser() {
    final user = _auth.currentUser;
    if (user == null) {
      throw const SubscriptionAccessException('Please sign in first.');
    }
    return user;
  }

  AppUserSubscription _applyTrialPolicy(AppUserSubscription data) {
    final now = DateTime.now();
    final start = data.trialStartedAt ?? now;
    final end = data.trialEndsAt ?? start.add(_trialDuration);

    return _normalizeUnrestrictedAccess(
      data.copyWith(
        trialStartedAt: start,
        trialEndsAt: end,
      ),
    );
  }

  // ignore: unused_element
  bool _isTrialExpired(AppUserSubscription data) {
    // Premium removed — trial never expires.
    return false;
  }

  void _listenToUser(String uid) {
    _userSub?.cancel();
    _userSub = _userDoc(uid).snapshots().listen((snapshot) {
      final data = snapshot.data();
      if (data == null) {
        return;
      }
      currentUserNotifier.value = _normalizeUnrestrictedAccess(
        AppUserSubscription.fromFirestore(data),
      );
    });
  }

  AppUserSubscription _normalizeUnrestrictedAccess(AppUserSubscription data) {
    return data.copyWith(
      plan: SubscriptionPlan.lifetime,
      status: SubscriptionStatus.active,
      freeMessagesUsed: 0,
      trialOver: false,
      trialStatusMessage: '',
      premiumActivatedAt: data.premiumActivatedAt ?? DateTime.now(),
    );
  }
}
