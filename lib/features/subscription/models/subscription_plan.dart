enum SubscriptionPlan { free, monthly, yearly, lifetime }

enum SubscriptionStatus { inactive, active, expired }

extension SubscriptionPlanX on SubscriptionPlan {
  String get firestoreValue {
    switch (this) {
      case SubscriptionPlan.free:
        return 'free';
      case SubscriptionPlan.monthly:
        return 'monthly';
      case SubscriptionPlan.yearly:
        return 'yearly';
      case SubscriptionPlan.lifetime:
        return 'lifetime';
    }
  }

  String get label {
    switch (this) {
      case SubscriptionPlan.free:
        return 'Free';
      case SubscriptionPlan.monthly:
        return 'Monthly';
      case SubscriptionPlan.yearly:
        return 'Yearly';
      case SubscriptionPlan.lifetime:
        return 'Lifetime';
    }
  }
}

extension SubscriptionStatusX on SubscriptionStatus {
  String get firestoreValue {
    switch (this) {
      case SubscriptionStatus.inactive:
        return 'inactive';
      case SubscriptionStatus.active:
        return 'active';
      case SubscriptionStatus.expired:
        return 'expired';
    }
  }
}

SubscriptionPlan subscriptionPlanFromString(String? value) {
  switch ((value ?? '').trim().toLowerCase()) {
    case 'monthly':
    case 'quarterly':
      return SubscriptionPlan.monthly;
    case 'yearly':
      return SubscriptionPlan.yearly;
    case 'lifetime':
      return SubscriptionPlan.lifetime;
    default:
      return SubscriptionPlan.free;
  }
}

SubscriptionStatus subscriptionStatusFromString(String? value) {
  switch ((value ?? '').trim().toLowerCase()) {
    case 'active':
      return SubscriptionStatus.active;
    case 'expired':
      return SubscriptionStatus.expired;
    default:
      return SubscriptionStatus.inactive;
  }
}
