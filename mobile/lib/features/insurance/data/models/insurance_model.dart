import 'package:equatable/equatable.dart';

/// Insurance type enum
enum InsuranceType {
  rca('rca', 'RCA'),
  casco('casco', 'CASCO'),
  cmr('cmr', 'CMR'),
  other('other', 'Altele');

  final String value;
  final String label;
  const InsuranceType(this.value, this.label);

  static InsuranceType fromString(String? value) {
    return InsuranceType.values.firstWhere(
      (e) => e.value == value,
      orElse: () => InsuranceType.other,
    );
  }
}

/// Insurance status enum
enum InsuranceStatus {
  active('active', 'Activă', 0xFF4CAF50),
  expiringSoon('expiring_soon', 'Expiră curând', 0xFFFF9800),
  expired('expired', 'Expirată', 0xFFF44336);

  final String value;
  final String label;
  final int color;
  const InsuranceStatus(this.value, this.label, this.color);

  static InsuranceStatus fromString(String? value) {
    return InsuranceStatus.values.firstWhere(
      (e) => e.value == value,
      orElse: () => InsuranceStatus.active,
    );
  }
}

/// Insurance vehicle info
class InsuranceVehicle extends Equatable {
  final int id;
  final String registrationNumber;
  final String? name;

  const InsuranceVehicle({
    required this.id,
    required this.registrationNumber,
    this.name,
  });

  factory InsuranceVehicle.fromJson(Map<String, dynamic> json) {
    return InsuranceVehicle(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      registrationNumber: json['registration_number'] ?? '',
      name: json['name'],
    );
  }

  @override
  List<Object?> get props => [id, registrationNumber, name];
}

/// Insurance model
class Insurance extends Equatable {
  final int id;
  final InsuranceType type;
  final String? policyNumber;
  final String? provider;
  final DateTime startDate;
  final DateTime endDate;
  final bool isExpired;
  final int daysUntilExpiry;
  final InsuranceStatus status;
  final double? premium;
  final double? coverageAmount;
  final String? notes;
  final InsuranceVehicle? vehicle;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const Insurance({
    required this.id,
    required this.type,
    this.policyNumber,
    this.provider,
    required this.startDate,
    required this.endDate,
    required this.isExpired,
    required this.daysUntilExpiry,
    required this.status,
    this.premium,
    this.coverageAmount,
    this.notes,
    this.vehicle,
    this.createdAt,
    this.updatedAt,
  });

  factory Insurance.fromJson(Map<String, dynamic> json) {
    return Insurance(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      type: InsuranceType.fromString(json['type']),
      policyNumber: json['policy_number'],
      provider: json['provider'],
      startDate: DateTime.parse(json['start_date']),
      endDate: DateTime.parse(json['end_date']),
      isExpired: json['is_expired'] ?? false,
      daysUntilExpiry: json['days_until_expiry'] ?? 0,
      status: InsuranceStatus.fromString(json['status']),
      premium: _parseDouble(json['premium']),
      coverageAmount: _parseDouble(json['coverage_amount']),
      notes: json['notes'],
      vehicle: json['vehicle'] != null
          ? InsuranceVehicle.fromJson(json['vehicle'])
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'vehicle_id': vehicle?.id,
      'insurance_type': type.value,
      'policy_number': policyNumber,
      'provider': provider,
      'start_date': startDate.toIso8601String().split('T').first,
      'end_date': endDate.toIso8601String().split('T').first,
      'premium': premium,
      'coverage_amount': coverageAmount,
      'notes': notes,
    };
  }

  String get typeLabel => type.label;
  String get statusLabel => status.label;

  String get vehicleDisplay {
    if (vehicle == null) return 'N/A';
    if (vehicle!.name != null && vehicle!.name!.isNotEmpty) {
      return '${vehicle!.registrationNumber} - ${vehicle!.name}';
    }
    return vehicle!.registrationNumber;
  }

  @override
  List<Object?> get props => [
        id,
        type,
        policyNumber,
        provider,
        startDate,
        endDate,
        isExpired,
        daysUntilExpiry,
        status,
        premium,
        coverageAmount,
        notes,
        vehicle,
      ];
}

/// Insurance list response
class InsuranceResponse {
  final List<Insurance> data;
  final int currentPage;
  final int lastPage;
  final int total;

  InsuranceResponse({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory InsuranceResponse.fromJson(Map<String, dynamic> json) {
    final dataList = json['data'] as List? ?? [];
    return InsuranceResponse(
      data: dataList.map((e) => Insurance.fromJson(e)).toList(),
      currentPage: json['pagination']?['current_page'] ?? 1,
      lastPage: json['pagination']?['last_page'] ?? 1,
      total: json['pagination']?['total'] ?? 0,
    );
  }
}

/// Expiring insurance item
class ExpiringInsurance {
  final int id;
  final InsuranceType type;
  final String? policyNumber;
  final String? provider;
  final DateTime expiryDate;
  final int daysUntilExpiry;
  final String priority;
  final InsuranceVehicle vehicle;

  ExpiringInsurance({
    required this.id,
    required this.type,
    this.policyNumber,
    this.provider,
    required this.expiryDate,
    required this.daysUntilExpiry,
    required this.priority,
    required this.vehicle,
  });

  factory ExpiringInsurance.fromJson(Map<String, dynamic> json) {
    return ExpiringInsurance(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      type: InsuranceType.fromString(json['type']),
      policyNumber: json['policy_number'],
      provider: json['provider'],
      expiryDate: DateTime.parse(json['expiry_date']),
      daysUntilExpiry: json['days_until_expiry'] ?? 0,
      priority: json['priority'] ?? 'low',
      vehicle: InsuranceVehicle.fromJson(json['vehicle']),
    );
  }
}

double? _parseDouble(dynamic value) {
  if (value == null) return null;
  if (value is double) return value;
  if (value is int) return value.toDouble();
  if (value is String) return double.tryParse(value);
  return null;
}
