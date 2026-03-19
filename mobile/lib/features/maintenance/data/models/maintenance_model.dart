/// Maintenance record model
class Maintenance {
  final int id;
  final int vehicleId;
  final String? vehiclePlate;
  final String type;
  final String? description;
  final DateTime date;
  final int? mileage;
  final double? cost;
  final String? serviceProvider;
  final String? invoiceNumber;
  final DateTime? nextServiceDate;
  final int? nextServiceMileage;
  final String? status;
  final String? notes;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Maintenance({
    required this.id,
    required this.vehicleId,
    this.vehiclePlate,
    required this.type,
    this.description,
    required this.date,
    this.mileage,
    this.cost,
    this.serviceProvider,
    this.invoiceNumber,
    this.nextServiceDate,
    this.nextServiceMileage,
    this.status,
    this.notes,
    this.createdAt,
    this.updatedAt,
  });

  factory Maintenance.fromJson(Map<String, dynamic> json) {
    return Maintenance(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      vehicleId: json['vehicle_id'] is String 
          ? int.parse(json['vehicle_id']) 
          : json['vehicle_id'] ?? 0,
      vehiclePlate: json['vehicle_plate'] ?? json['vehiclePlate'],
      type: json['type'] ?? json['maintenance_type'] ?? 'other',
      description: json['description'],
      date: DateTime.tryParse(json['date'] ?? json['service_date'] ?? '') ?? DateTime.now(),
      mileage: json['mileage'] != null 
          ? (json['mileage'] is String ? int.tryParse(json['mileage']) : json['mileage'])
          : null,
      cost: json['cost'] != null 
          ? (json['cost'] is String ? double.tryParse(json['cost']) : json['cost']?.toDouble())
          : null,
      serviceProvider: json['service_provider'] ?? json['serviceProvider'],
      invoiceNumber: json['invoice_number'] ?? json['invoiceNumber'],
      nextServiceDate: json['next_service_date'] != null 
          ? DateTime.tryParse(json['next_service_date'])
          : null,
      nextServiceMileage: json['next_service_mileage'] != null
          ? (json['next_service_mileage'] is String 
              ? int.tryParse(json['next_service_mileage']) 
              : json['next_service_mileage'])
          : null,
      status: json['status'] ?? 'completed',
      notes: json['notes'],
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
      'vehicle_id': vehicleId,
      'type': type,
      'description': description,
      'date': date.toIso8601String().split('T').first,
      'mileage': mileage,
      'cost': cost,
      'service_provider': serviceProvider,
      'invoice_number': invoiceNumber,
      'next_service_date': nextServiceDate?.toIso8601String().split('T').first,
      'next_service_mileage': nextServiceMileage,
      'status': status,
      'notes': notes,
    };
  }

  /// Get maintenance type label in Romanian
  String get typeLabel {
    switch (type.toLowerCase()) {
      case 'oil_change':
        return 'Schimb ulei';
      case 'tire_change':
        return 'Schimb anvelope';
      case 'brake_service':
        return 'Frâne';
      case 'engine':
        return 'Motor';
      case 'transmission':
        return 'Transmisie';
      case 'electrical':
        return 'Sistem electric';
      case 'suspension':
        return 'Suspensie';
      case 'air_conditioning':
        return 'Aer condiționat';
      case 'inspection':
        return 'Inspecție';
      case 'repair':
        return 'Reparație';
      case 'scheduled':
        return 'Revizie programată';
      case 'other':
      default:
        return 'Altele';
    }
  }

  /// Get status label
  String get statusLabel {
    switch (status?.toLowerCase()) {
      case 'scheduled':
        return 'Programat';
      case 'in_progress':
        return 'În lucru';
      case 'completed':
        return 'Finalizat';
      case 'cancelled':
        return 'Anulat';
      default:
        return status ?? 'Necunoscut';
    }
  }

  /// Check if next service is due soon
  bool get isServiceDueSoon {
    if (nextServiceDate != null) {
      final daysUntil = nextServiceDate!.difference(DateTime.now()).inDays;
      return daysUntil <= 30 && daysUntil >= 0;
    }
    return false;
  }

  /// Check if next service is overdue
  bool get isServiceOverdue {
    if (nextServiceDate != null) {
      return nextServiceDate!.isBefore(DateTime.now());
    }
    return false;
  }

  /// Get formatted cost
  String get formattedCost {
    if (cost == null) return '-';
    return '${cost!.toStringAsFixed(2)} RON';
  }

  Maintenance copyWith({
    int? id,
    int? vehicleId,
    String? vehiclePlate,
    String? type,
    String? description,
    DateTime? date,
    int? mileage,
    double? cost,
    String? serviceProvider,
    String? invoiceNumber,
    DateTime? nextServiceDate,
    int? nextServiceMileage,
    String? status,
    String? notes,
  }) {
    return Maintenance(
      id: id ?? this.id,
      vehicleId: vehicleId ?? this.vehicleId,
      vehiclePlate: vehiclePlate ?? this.vehiclePlate,
      type: type ?? this.type,
      description: description ?? this.description,
      date: date ?? this.date,
      mileage: mileage ?? this.mileage,
      cost: cost ?? this.cost,
      serviceProvider: serviceProvider ?? this.serviceProvider,
      invoiceNumber: invoiceNumber ?? this.invoiceNumber,
      nextServiceDate: nextServiceDate ?? this.nextServiceDate,
      nextServiceMileage: nextServiceMileage ?? this.nextServiceMileage,
      status: status ?? this.status,
      notes: notes ?? this.notes,
    );
  }
}

/// Paginated maintenance response
class MaintenanceResponse {
  final List<Maintenance> records;
  final int total;
  final int page;
  final int perPage;
  final int totalPages;

  MaintenanceResponse({
    required this.records,
    required this.total,
    required this.page,
    required this.perPage,
    required this.totalPages,
  });

  factory MaintenanceResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>? ?? [];
    final pagination = json['pagination'] as Map<String, dynamic>? ?? {};

    return MaintenanceResponse(
      records: data.map((d) => Maintenance.fromJson(d as Map<String, dynamic>)).toList(),
      total: pagination['total'] ?? data.length,
      page: pagination['page'] ?? 1,
      perPage: pagination['per_page'] ?? pagination['perPage'] ?? 10,
      totalPages: pagination['total_pages'] ?? pagination['totalPages'] ?? 1,
    );
  }

  bool get hasMore => page < totalPages;
}

/// Maintenance type enum
enum MaintenanceType {
  all,
  oilChange,
  tireChange,
  brakeService,
  engine,
  transmission,
  electrical,
  suspension,
  airConditioning,
  inspection,
  repair,
  scheduled,
  other;

  String get label {
    switch (this) {
      case MaintenanceType.all:
        return 'Toate';
      case MaintenanceType.oilChange:
        return 'Schimb ulei';
      case MaintenanceType.tireChange:
        return 'Schimb anvelope';
      case MaintenanceType.brakeService:
        return 'Frâne';
      case MaintenanceType.engine:
        return 'Motor';
      case MaintenanceType.transmission:
        return 'Transmisie';
      case MaintenanceType.electrical:
        return 'Sistem electric';
      case MaintenanceType.suspension:
        return 'Suspensie';
      case MaintenanceType.airConditioning:
        return 'Aer condiționat';
      case MaintenanceType.inspection:
        return 'Inspecție';
      case MaintenanceType.repair:
        return 'Reparație';
      case MaintenanceType.scheduled:
        return 'Revizie programată';
      case MaintenanceType.other:
        return 'Altele';
    }
  }

  String get value {
    if (this == MaintenanceType.all) return '';
    switch (this) {
      case MaintenanceType.oilChange:
        return 'oil_change';
      case MaintenanceType.tireChange:
        return 'tire_change';
      case MaintenanceType.brakeService:
        return 'brake_service';
      case MaintenanceType.airConditioning:
        return 'air_conditioning';
      default:
        return name;
    }
  }
}
