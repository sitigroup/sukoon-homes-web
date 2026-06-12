class TrustVerificationProfile {
  TrustVerificationProfile({
    required this.customerId,
    required this.trustScore,
    this.manualAdjustment = 0,
    this.publicVisible = true,
    this.lastCalculatedAt,
    this.badges = const [],
    this.verificationBadges = const [],
    this.ownerTrustBadge = false,
    this.tenantTrustBadge = false,
  });

  factory TrustVerificationProfile.fromMap(Map<String, dynamic>? map) {
    if (map == null || map.isEmpty) {
      return TrustVerificationProfile(customerId: 0, trustScore: 0);
    }
    final badgesRaw = map['badges'] as List? ?? [];
    final verificationRaw = map['verification_badges'] as List? ?? [];
    return TrustVerificationProfile(
      customerId: int.tryParse('${map['customer_id'] ?? 0}') ?? 0,
      trustScore: int.tryParse('${map['trust_score'] ?? 0}') ?? 0,
      manualAdjustment:
          int.tryParse('${map['manual_adjustment'] ?? 0}') ?? 0,
      publicVisible: map['public_visible'] == true ||
          map['public_visible'] == 1 ||
          map['public_visible'] == '1',
      lastCalculatedAt: map['last_calculated_at']?.toString(),
      badges: badgesRaw
          .map(
            (e) => TrustVerificationBadge.fromMap(
              e as Map<String, dynamic>? ?? {},
            ),
          )
          .toList(),
      verificationBadges: verificationRaw
          .map(
            (e) => TrustVerificationIssuedBadge.fromMap(
              e as Map<String, dynamic>? ?? {},
            ),
          )
          .toList(),
      ownerTrustBadge: map['owner_trust_badge'] == true ||
          map['owner_trust_badge'] == 1,
      tenantTrustBadge: map['tenant_trust_badge'] == true ||
          map['tenant_trust_badge'] == 1,
    );
  }

  final int customerId;
  final int trustScore;
  final int manualAdjustment;
  final bool publicVisible;
  final String? lastCalculatedAt;
  final List<TrustVerificationBadge> badges;
  final List<TrustVerificationIssuedBadge> verificationBadges;
  final bool ownerTrustBadge;
  final bool tenantTrustBadge;

  bool get hasPublicScore => trustScore > 0;
}

class TrustVerificationBadge {
  TrustVerificationBadge({
    this.slug,
    this.label,
    this.iconUrl,
    this.description,
  });

  factory TrustVerificationBadge.fromMap(Map<String, dynamic> map) {
    return TrustVerificationBadge(
      slug: map['slug']?.toString(),
      label: map['label']?.toString() ?? map['name']?.toString(),
      iconUrl: map['icon_url']?.toString() ?? map['icon']?.toString(),
      description: map['description']?.toString(),
    );
  }

  final String? slug;
  final String? label;
  final String? iconUrl;
  final String? description;
}

class TrustVerificationIssuedBadge {
  TrustVerificationIssuedBadge({
    this.id,
    this.badgeType,
    this.status,
    this.label,
    this.issuedAt,
    this.expiresAt,
    this.canDownload = false,
  });

  factory TrustVerificationIssuedBadge.fromMap(Map<String, dynamic> map) {
    return TrustVerificationIssuedBadge(
      id: int.tryParse('${map['id'] ?? ''}'),
      badgeType: map['badge_type']?.toString() ?? map['type']?.toString(),
      status: map['status']?.toString(),
      label: map['label']?.toString() ?? map['badge_label']?.toString(),
      issuedAt: map['issued_at']?.toString(),
      expiresAt: map['expires_at']?.toString(),
      canDownload: map['can_download'] == true || map['can_download'] == 1,
    );
  }

  final int? id;
  final String? badgeType;
  final String? status;
  final String? label;
  final String? issuedAt;
  final String? expiresAt;
  final bool canDownload;
}

class TrustVerificationCity {
  TrustVerificationCity({required this.slug, required this.label});

  factory TrustVerificationCity.fromMap(Map<String, dynamic> map) {
    return TrustVerificationCity(
      slug: map['slug']?.toString() ?? map['city_slug']?.toString() ?? '',
      label: map['label']?.toString() ??
          map['name']?.toString() ??
          map['slug']?.toString() ??
          '',
    );
  }

  final String slug;
  final String label;
}

class TrustVerificationPackage {
  TrustVerificationPackage({
    required this.id,
    required this.name,
    required this.orderType,
    required this.citySlug,
    this.price,
    this.description,
    this.features = const [],
  });

  factory TrustVerificationPackage.fromMap(Map<String, dynamic> map) {
    final featuresRaw = map['features'] as List? ?? [];
    return TrustVerificationPackage(
      id: int.tryParse('${map['id'] ?? ''}') ?? 0,
      name: map['name']?.toString() ?? 'Verification package',
      orderType: map['order_type']?.toString() ?? map['type']?.toString() ?? '',
      citySlug: map['city_slug']?.toString() ?? '',
      price: double.tryParse('${map['price'] ?? map['amount'] ?? 0}'),
      description: map['description']?.toString(),
      features: featuresRaw.map((e) => e.toString()).toList(),
    );
  }

  final int id;
  final String name;
  final String orderType;
  final String citySlug;
  final double? price;
  final String? description;
  final List<String> features;
}

class TrustVerificationOrderDetail extends TrustVerificationOrderSummary {
  TrustVerificationOrderDetail({
    super.id,
    super.orderType,
    super.status,
    super.paymentStatus,
    super.packageName,
    super.createdAt,
    super.canDownloadReport,
    this.amount,
    this.orderNumber,
    this.citySlug,
    this.subjectName,
    this.subjectPhone,
    this.timeline = const [],
  });

  factory TrustVerificationOrderDetail.fromMap(Map<String, dynamic> map) {
    final package = map['package'] as Map<String, dynamic>?;
    final subject = map['subject'] as Map<String, dynamic>?;
    final timelineRaw = map['timeline'] as List? ?? map['status_timeline'] as List? ?? [];
    return TrustVerificationOrderDetail(
      id: int.tryParse('${map['id'] ?? ''}'),
      orderType: map['order_type']?.toString(),
      status: map['status']?.toString(),
      paymentStatus: map['payment_status']?.toString(),
      packageName: package?['name']?.toString(),
      createdAt: map['created_at']?.toString(),
      canDownloadReport: map['can_download_report'] == true ||
          map['can_download_report'] == 1,
      amount: double.tryParse('${map['amount'] ?? 0}'),
      orderNumber: map['order_number']?.toString(),
      citySlug: map['city_slug']?.toString(),
      subjectName: subject?['full_name']?.toString(),
      subjectPhone: subject?['phone']?.toString(),
      timeline: timelineRaw
          .map((e) => e is Map ? Map<String, dynamic>.from(e) : <String, dynamic>{})
          .toList(),
    );
  }

  final double? amount;
  final String? orderNumber;
  final String? citySlug;
  final String? subjectName;
  final String? subjectPhone;
  final List<Map<String, dynamic>> timeline;

  bool get needsPayment =>
      paymentStatus != 'paid' && paymentStatus != 'completed';
}

class TrustVerificationOrderSummary {
  TrustVerificationOrderSummary({
    this.id,
    this.orderType,
    this.status,
    this.paymentStatus,
    this.packageName,
    this.createdAt,
    this.canDownloadReport = false,
  });

  factory TrustVerificationOrderSummary.fromMap(Map<String, dynamic> map) {
    final package = map['package'] as Map<String, dynamic>?;
    return TrustVerificationOrderSummary(
      id: int.tryParse('${map['id'] ?? ''}'),
      orderType: map['order_type']?.toString(),
      status: map['status']?.toString(),
      paymentStatus: map['payment_status']?.toString(),
      packageName: package?['name']?.toString(),
      createdAt: map['created_at']?.toString(),
      canDownloadReport: map['can_download_report'] == true ||
          map['can_download_report'] == 1,
    );
  }

  final int? id;
  final String? orderType;
  final String? status;
  final String? paymentStatus;
  final String? packageName;
  final String? createdAt;
  final bool canDownloadReport;
}
