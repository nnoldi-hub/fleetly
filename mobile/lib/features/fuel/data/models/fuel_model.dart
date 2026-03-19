/// Fuel record model
class FuelRecord {
  final int id;
  final int vehicleId;
  final String? vehiclePlate;
  final int? driverId;
  final String? driverName;
  final DateTime date;
  final double quantity;
  final double pricePerUnit;
  final double totalCost;
  final String fuelType;
  final int? mileage;
  final String? station;
  final String? receiptNumber;
  final bool fullTank;
  final String? notes;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  FuelRecord({
    required this.id,
    required this.vehicleId,
    this.vehiclePlate,
    this.driverId,
    this.driverName,
    required this.date,
    required this.quantity,
    required this.pricePerUnit,
    required this.totalCost,
    required this.fuelType,
    this.mileage,
    this.station,
    this.receiptNumber,
    this.fullTank = false,
    this.notes,
    this.createdAt,
    this.updatedAt,
  });

  factory FuelRecord.fromJson(Map<String, dynamic> json) {
    return FuelRecord(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      vehicleId: json['vehicle_id'] is String 
          ? int.parse(json['vehicle_id']) 
          : json['vehicle_id'] ?? 0,
      vehiclePlate: json['vehicle_plate'] ?? json['vehiclePlate'],
      driverId: json['driver_id'] != null 
          ? (json['driver_id'] is String ? int.tryParse(json['driver_id']) : json['driver_id'])
          : null,
      driverName: json['driver_name'] ?? json['driverName'],
      date: DateTime.tryParse(json['date'] ?? json['fill_date'] ?? '') ?? DateTime.now(),
      quantity: _parseDouble(json['quantity'] ?? json['liters']),
      pricePerUnit: _parseDouble(json['price_per_unit'] ?? json['pricePerUnit'] ?? json['price_per_liter']),
      totalCost: _parseDouble(json['total_cost'] ?? json['totalCost'] ?? json['total']),
      fuelType: json['fuel_type'] ?? json['fuelType'] ?? 'diesel',
      mileage: json['mileage'] != null 
          ? (json['mileage'] is String ? int.tryParse(json['mileage']) : json['mileage'])
          : null,
      station: json['station'] ?? json['gas_station'],
      receiptNumber: json['receipt_number'] ?? json['receiptNumber'],
      fullTank: json['full_tank'] == true || json['full_tank'] == 1 || json['fullTank'] == true,
      notes: json['notes'],
      createdAt: json['created_at'] != null 
          ? DateTime.tryParse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null 
          ? DateTime.tryParse(json['updated_at'])
          : null,
    );
  }

  static double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is double) return value;
    if (value is int) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'vehicle_id': vehicleId,
      'driver_id': driverId,
      'date': date.toIso8601String().split('T').first,
      'quantity': quantity,
      'price_per_unit': pricePerUnit,
      'total_cost': totalCost,
      'fuel_type': fuelType,
      'mileage': mileage,
      'station': station,
      'receipt_number': receiptNumber,
      'full_tank': fullTank,
      'notes': notes,
    };
  }

  /// Get fuel type label in Romanian
  String get fuelTypeLabel {
    switch (fuelType.toLowerCase()) {
      case 'diesel':
        return 'Motorină';
      case 'gasoline':
      case 'petrol':
        return 'Benzină';
      case 'lpg':
        return 'GPL';
      case 'cng':
        return 'GNC';
      case 'electric':
        return 'Electric';
      case 'hybrid':
        return 'Hibrid';
      default:
        return fuelType;
    }
  }

  /// Get formatted quantity
  String get formattedQuantity => '${quantity.toStringAsFixed(2)} L';

  /// Get formatted price per unit
  String get formattedPricePerUnit => '${pricePerUnit.toStringAsFixed(2)} RON/L';

  /// Get formatted total cost
  String get formattedTotalCost => '${totalCost.toStringAsFixed(2)} RON';

  /// Calculate consumption if mileage available
  double? get consumption {
    // This would need previous record to calculate
    return null;
  }

  FuelRecord copyWith({
    int? id,
    int? vehicleId,
    String? vehiclePlate,
    int? driverId,
    String? driverName,
    DateTime? date,
    double? quantity,
    double? pricePerUnit,
    double? totalCost,
    String? fuelType,
    int? mileage,
    String? station,
    String? receiptNumber,
    bool? fullTank,
    String? notes,
  }) {
    return FuelRecord(
      id: id ?? this.id,
      vehicleId: vehicleId ?? this.vehicleId,
      vehiclePlate: vehiclePlate ?? this.vehiclePlate,
      driverId: driverId ?? this.driverId,
      driverName: driverName ?? this.driverName,
      date: date ?? this.date,
      quantity: quantity ?? this.quantity,
      pricePerUnit: pricePerUnit ?? this.pricePerUnit,
      totalCost: totalCost ?? this.totalCost,
      fuelType: fuelType ?? this.fuelType,
      mileage: mileage ?? this.mileage,
      station: station ?? this.station,
      receiptNumber: receiptNumber ?? this.receiptNumber,
      fullTank: fullTank ?? this.fullTank,
      notes: notes ?? this.notes,
    );
  }
}

/// Paginated fuel response
class FuelResponse {
  final List<FuelRecord> records;
  final int total;
  final int page;
  final int perPage;
  final int totalPages;

  FuelResponse({
    required this.records,
    required this.total,
    required this.page,
    required this.perPage,
    required this.totalPages,
  });

  factory FuelResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>? ?? [];
    final pagination = json['pagination'] as Map<String, dynamic>? ?? {};

    return FuelResponse(
      records: data.map((d) => FuelRecord.fromJson(d as Map<String, dynamic>)).toList(),
      total: pagination['total'] ?? data.length,
      page: pagination['page'] ?? 1,
      perPage: pagination['per_page'] ?? pagination['perPage'] ?? 10,
      totalPages: pagination['total_pages'] ?? pagination['totalPages'] ?? 1,
    );
  }

  bool get hasMore => page < totalPages;
}

/// Fuel type enum
enum FuelType {
  all,
  diesel,
  gasoline,
  lpg,
  cng,
  electric;

  String get label {
    switch (this) {
      case FuelType.all:
        return 'Toate';
      case FuelType.diesel:
        return 'Motorină';
      case FuelType.gasoline:
        return 'Benzină';
      case FuelType.lpg:
        return 'GPL';
      case FuelType.cng:
        return 'GNC';
      case FuelType.electric:
        return 'Electric';
    }
  }

  String get value {
    if (this == FuelType.all) return '';
    return name;
  }
}

/// Fuel statistics model
class FuelStats {
  final double totalQuantity;
  final double totalCost;
  final double avgPricePerUnit;
  final double avgConsumption;
  final int totalRecords;

  FuelStats({
    required this.totalQuantity,
    required this.totalCost,
    required this.avgPricePerUnit,
    required this.avgConsumption,
    required this.totalRecords,
  });

  factory FuelStats.fromJson(Map<String, dynamic> json) {
    return FuelStats(
      totalQuantity: FuelRecord._parseDouble(json['total_quantity'] ?? json['totalQuantity']),
      totalCost: FuelRecord._parseDouble(json['total_cost'] ?? json['totalCost']),
      avgPricePerUnit: FuelRecord._parseDouble(json['avg_price'] ?? json['avgPrice']),
      avgConsumption: FuelRecord._parseDouble(json['avg_consumption'] ?? json['avgConsumption']),
      totalRecords: json['total_records'] ?? json['totalRecords'] ?? 0,
    );
  }

  String get formattedTotalQuantity => '${totalQuantity.toStringAsFixed(2)} L';
  String get formattedTotalCost => '${totalCost.toStringAsFixed(2)} RON';
  String get formattedAvgPrice => '${avgPricePerUnit.toStringAsFixed(2)} RON/L';
  String get formattedAvgConsumption => '${avgConsumption.toStringAsFixed(1)} L/100km';
}
