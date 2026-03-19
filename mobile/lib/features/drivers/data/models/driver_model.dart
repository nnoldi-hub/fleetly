/// Driver model
class Driver {
  final int id;
  final String firstName;
  final String lastName;
  final String? email;
  final String? phone;
  final String? cnp;
  final String? licenseNumber;
  final DateTime? licenseExpiry;
  final String? licenseCategories;
  final DateTime? medicalExpiry;
  final String? address;
  final String? status;
  final int? vehicleId;
  final String? vehiclePlate;
  final DateTime? hireDate;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Driver({
    required this.id,
    required this.firstName,
    required this.lastName,
    this.email,
    this.phone,
    this.cnp,
    this.licenseNumber,
    this.licenseExpiry,
    this.licenseCategories,
    this.medicalExpiry,
    this.address,
    this.status,
    this.vehicleId,
    this.vehiclePlate,
    this.hireDate,
    this.createdAt,
    this.updatedAt,
  });

  factory Driver.fromJson(Map<String, dynamic> json) {
    return Driver(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      firstName: json['first_name'] ?? json['firstName'] ?? '',
      lastName: json['last_name'] ?? json['lastName'] ?? '',
      email: json['email'],
      phone: json['phone'],
      cnp: json['cnp'],
      licenseNumber: json['license_number'] ?? json['licenseNumber'],
      licenseExpiry: json['license_expiry'] != null 
          ? DateTime.tryParse(json['license_expiry']) 
          : null,
      licenseCategories: json['license_categories'] ?? json['licenseCategories'],
      medicalExpiry: json['medical_expiry'] != null 
          ? DateTime.tryParse(json['medical_expiry']) 
          : null,
      address: json['address'],
      status: json['status'] ?? 'active',
      vehicleId: json['vehicle_id'] != null 
          ? (json['vehicle_id'] is String ? int.tryParse(json['vehicle_id']) : json['vehicle_id'])
          : null,
      vehiclePlate: json['vehicle_plate'] ?? json['vehiclePlate'],
      hireDate: json['hire_date'] != null 
          ? DateTime.tryParse(json['hire_date']) 
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
      'id': id,
      'first_name': firstName,
      'last_name': lastName,
      'email': email,
      'phone': phone,
      'cnp': cnp,
      'license_number': licenseNumber,
      'license_expiry': licenseExpiry?.toIso8601String().split('T').first,
      'license_categories': licenseCategories,
      'medical_expiry': medicalExpiry?.toIso8601String().split('T').first,
      'address': address,
      'status': status,
      'vehicle_id': vehicleId,
      'hire_date': hireDate?.toIso8601String().split('T').first,
    };
  }

  /// Get full name
  String get fullName => '$firstName $lastName';

  /// Get initials
  String get initials {
    final first = firstName.isNotEmpty ? firstName[0].toUpperCase() : '';
    final last = lastName.isNotEmpty ? lastName[0].toUpperCase() : '';
    return '$first$last';
  }

  /// Check if driver has alerts
  bool get hasAlerts {
    final now = DateTime.now();
    if (licenseExpiry != null && licenseExpiry!.isBefore(now.add(const Duration(days: 30)))) {
      return true;
    }
    if (medicalExpiry != null && medicalExpiry!.isBefore(now.add(const Duration(days: 30)))) {
      return true;
    }
    return false;
  }

  /// Get status label
  String get statusLabel {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'Activ';
      case 'inactive':
        return 'Inactiv';
      case 'on_leave':
        return 'În concediu';
      case 'suspended':
        return 'Suspendat';
      default:
        return status ?? 'Necunoscut';
    }
  }

  /// Check if license is expired
  bool get isLicenseExpired {
    if (licenseExpiry == null) return false;
    return licenseExpiry!.isBefore(DateTime.now());
  }

  /// Check if medical is expired
  bool get isMedicalExpired {
    if (medicalExpiry == null) return false;
    return medicalExpiry!.isBefore(DateTime.now());
  }

  Driver copyWith({
    int? id,
    String? firstName,
    String? lastName,
    String? email,
    String? phone,
    String? cnp,
    String? licenseNumber,
    DateTime? licenseExpiry,
    String? licenseCategories,
    DateTime? medicalExpiry,
    String? address,
    String? status,
    int? vehicleId,
    String? vehiclePlate,
    DateTime? hireDate,
  }) {
    return Driver(
      id: id ?? this.id,
      firstName: firstName ?? this.firstName,
      lastName: lastName ?? this.lastName,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      cnp: cnp ?? this.cnp,
      licenseNumber: licenseNumber ?? this.licenseNumber,
      licenseExpiry: licenseExpiry ?? this.licenseExpiry,
      licenseCategories: licenseCategories ?? this.licenseCategories,
      medicalExpiry: medicalExpiry ?? this.medicalExpiry,
      address: address ?? this.address,
      status: status ?? this.status,
      vehicleId: vehicleId ?? this.vehicleId,
      vehiclePlate: vehiclePlate ?? this.vehiclePlate,
      hireDate: hireDate ?? this.hireDate,
    );
  }
}

/// Paginated drivers response
class DriversResponse {
  final List<Driver> drivers;
  final int total;
  final int page;
  final int perPage;
  final int totalPages;

  DriversResponse({
    required this.drivers,
    required this.total,
    required this.page,
    required this.perPage,
    required this.totalPages,
  });

  factory DriversResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>? ?? [];
    final pagination = json['pagination'] as Map<String, dynamic>? ?? {};
    
    return DriversResponse(
      drivers: data.map((d) => Driver.fromJson(d as Map<String, dynamic>)).toList(),
      total: pagination['total'] ?? data.length,
      page: pagination['page'] ?? 1,
      perPage: pagination['per_page'] ?? pagination['perPage'] ?? 10,
      totalPages: pagination['total_pages'] ?? pagination['totalPages'] ?? 1,
    );
  }

  bool get hasMore => page < totalPages;
}
