/// Vehicle model
class Vehicle {
  final int id;
  final String plateNumber;
  final String brand;
  final String model;
  final int? year;
  final String? vin;
  final String? type;
  final String? fuelType;
  final int? currentMileage;
  final String? status;
  final String? color;
  final int? driverId;
  final String? driverName;
  final DateTime? insuranceExpiry;
  final DateTime? itpExpiry;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Vehicle({
    required this.id,
    required this.plateNumber,
    required this.brand,
    required this.model,
    this.year,
    this.vin,
    this.type,
    this.fuelType,
    this.currentMileage,
    this.status,
    this.color,
    this.driverId,
    this.driverName,
    this.insuranceExpiry,
    this.itpExpiry,
    this.createdAt,
    this.updatedAt,
  });

  factory Vehicle.fromJson(Map<String, dynamic> json) {
    return Vehicle(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      plateNumber: json['plate_number'] ?? json['plateNumber'] ?? '',
      brand: json['brand'] ?? '',
      model: json['model'] ?? '',
      year: json['year'] != null ? (json['year'] is String ? int.tryParse(json['year']) : json['year']) : null,
      vin: json['vin'],
      type: json['type'],
      fuelType: json['fuel_type'] ?? json['fuelType'],
      currentMileage: json['current_mileage'] != null 
          ? (json['current_mileage'] is String ? int.tryParse(json['current_mileage']) : json['current_mileage'])
          : null,
      status: json['status'] ?? 'active',
      color: json['color'],
      driverId: json['driver_id'] != null 
          ? (json['driver_id'] is String ? int.tryParse(json['driver_id']) : json['driver_id'])
          : null,
      driverName: json['driver_name'] ?? json['driverName'],
      insuranceExpiry: json['insurance_expiry'] != null 
          ? DateTime.tryParse(json['insurance_expiry']) 
          : null,
      itpExpiry: json['itp_expiry'] != null 
          ? DateTime.tryParse(json['itp_expiry']) 
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
      'plate_number': plateNumber,
      'brand': brand,
      'model': model,
      'year': year,
      'vin': vin,
      'type': type,
      'fuel_type': fuelType,
      'current_mileage': currentMileage,
      'status': status,
      'color': color,
      'driver_id': driverId,
    };
  }

  /// Get display name (brand model)
  String get displayName => '$brand $model';

  /// Get full display name with plate
  String get fullDisplayName => '$plateNumber - $brand $model';

  /// Check if vehicle has alerts
  bool get hasAlerts {
    final now = DateTime.now();
    if (insuranceExpiry != null && insuranceExpiry!.isBefore(now.add(const Duration(days: 30)))) {
      return true;
    }
    if (itpExpiry != null && itpExpiry!.isBefore(now.add(const Duration(days: 30)))) {
      return true;
    }
    return false;
  }

  /// Get status color
  String get statusLabel {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'Activ';
      case 'inactive':
        return 'Inactiv';
      case 'maintenance':
        return 'În service';
      case 'sold':
        return 'Vândut';
      default:
        return status ?? 'Necunoscut';
    }
  }

  /// Get fuel type label
  String get fuelTypeLabel {
    switch (fuelType?.toLowerCase()) {
      case 'diesel':
        return 'Diesel';
      case 'gasoline':
      case 'petrol':
        return 'Benzină';
      case 'electric':
        return 'Electric';
      case 'hybrid':
        return 'Hibrid';
      case 'lpg':
        return 'GPL';
      default:
        return fuelType ?? 'Necunoscut';
    }
  }

  /// Get vehicle type label
  String get typeLabel {
    switch (type?.toLowerCase()) {
      case 'car':
        return 'Autoturism';
      case 'van':
        return 'Dubă';
      case 'truck':
        return 'Camion';
      case 'motorcycle':
        return 'Motocicletă';
      case 'bus':
        return 'Autobuz';
      default:
        return type ?? 'Altul';
    }
  }

  Vehicle copyWith({
    int? id,
    String? plateNumber,
    String? brand,
    String? model,
    int? year,
    String? vin,
    String? type,
    String? fuelType,
    int? currentMileage,
    String? status,
    String? color,
    int? driverId,
    String? driverName,
    DateTime? insuranceExpiry,
    DateTime? itpExpiry,
  }) {
    return Vehicle(
      id: id ?? this.id,
      plateNumber: plateNumber ?? this.plateNumber,
      brand: brand ?? this.brand,
      model: model ?? this.model,
      year: year ?? this.year,
      vin: vin ?? this.vin,
      type: type ?? this.type,
      fuelType: fuelType ?? this.fuelType,
      currentMileage: currentMileage ?? this.currentMileage,
      status: status ?? this.status,
      color: color ?? this.color,
      driverId: driverId ?? this.driverId,
      driverName: driverName ?? this.driverName,
      insuranceExpiry: insuranceExpiry ?? this.insuranceExpiry,
      itpExpiry: itpExpiry ?? this.itpExpiry,
    );
  }
}

/// Paginated vehicles response
class VehiclesResponse {
  final List<Vehicle> vehicles;
  final int total;
  final int page;
  final int perPage;
  final int totalPages;

  VehiclesResponse({
    required this.vehicles,
    required this.total,
    required this.page,
    required this.perPage,
    required this.totalPages,
  });

  factory VehiclesResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>? ?? [];
    final pagination = json['pagination'] as Map<String, dynamic>? ?? {};
    
    return VehiclesResponse(
      vehicles: data.map((v) => Vehicle.fromJson(v as Map<String, dynamic>)).toList(),
      total: pagination['total'] ?? data.length,
      page: pagination['page'] ?? 1,
      perPage: pagination['per_page'] ?? pagination['perPage'] ?? 10,
      totalPages: pagination['total_pages'] ?? pagination['totalPages'] ?? 1,
    );
  }

  bool get hasMore => page < totalPages;
}
