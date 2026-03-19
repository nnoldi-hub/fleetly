import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/fuel/data/models/fuel_model.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Fuel repository provider
final fuelRepositoryProvider = Provider<FuelRepository>((ref) {
  return FuelRepository(ref.read(apiClientProvider));
});

/// Fuel repository for API operations
class FuelRepository {
  final ApiClient _apiClient;

  FuelRepository(this._apiClient);

  /// Get all fuel records with pagination
  Future<FuelResponse> getFuelRecords({
    int page = 1,
    int perPage = 20,
    String? fuelType,
    int? vehicleId,
    int? driverId,
    String? search,
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page.toString(),
      'per_page': perPage.toString(),
    };

    if (fuelType != null && fuelType.isNotEmpty) {
      queryParams['fuel_type'] = fuelType;
    }
    if (vehicleId != null) {
      queryParams['vehicle_id'] = vehicleId.toString();
    }
    if (driverId != null) {
      queryParams['driver_id'] = driverId.toString();
    }
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }
    if (startDate != null) {
      queryParams['start_date'] = startDate.toIso8601String().split('T').first;
    }
    if (endDate != null) {
      queryParams['end_date'] = endDate.toIso8601String().split('T').first;
    }

    final response = await _apiClient.get(
      '/fuel',
      queryParameters: queryParams,
    );

    return FuelResponse.fromJson(response.data);
  }

  /// Get fuel record by ID
  Future<FuelRecord> getFuelById(int id) async {
    final response = await _apiClient.get('/fuel/$id');
    final data = response.data['data'] ?? response.data;
    return FuelRecord.fromJson(data);
  }

  /// Get fuel records for a specific vehicle
  Future<FuelResponse> getFuelByVehicle(
    int vehicleId, {
    int page = 1,
    int perPage = 20,
  }) async {
    return getFuelRecords(
      page: page,
      perPage: perPage,
      vehicleId: vehicleId,
    );
  }

  /// Create new fuel record
  Future<FuelRecord> createFuelRecord(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/fuel', data: data);
    final responseData = response.data['data'] ?? response.data;
    return FuelRecord.fromJson(responseData);
  }

  /// Update fuel record
  Future<FuelRecord> updateFuelRecord(int id, Map<String, dynamic> data) async {
    final response = await _apiClient.put('/fuel/$id', data: data);
    final responseData = response.data['data'] ?? response.data;
    return FuelRecord.fromJson(responseData);
  }

  /// Delete fuel record
  Future<bool> deleteFuelRecord(int id) async {
    final response = await _apiClient.delete('/fuel/$id');
    return response.statusCode == 200 || response.statusCode == 204;
  }

  /// Get fuel statistics
  Future<FuelStats> getFuelStats({
    int? vehicleId,
    String? period,
    DateTime? startDate,
    DateTime? endDate,
  }) async {
    final queryParams = <String, dynamic>{};
    if (vehicleId != null) {
      queryParams['vehicle_id'] = vehicleId.toString();
    }
    if (period != null) {
      queryParams['period'] = period;
    }
    if (startDate != null) {
      queryParams['start_date'] = startDate.toIso8601String().split('T').first;
    }
    if (endDate != null) {
      queryParams['end_date'] = endDate.toIso8601String().split('T').first;
    }

    final response = await _apiClient.get(
      '/fuel/stats',
      queryParameters: queryParams,
    );

    return FuelStats.fromJson(response.data['data'] ?? response.data);
  }

  /// Get recent fuel prices (average)
  Future<Map<String, double>> getRecentPrices() async {
    final response = await _apiClient.get('/fuel/prices');
    final data = response.data['data'] ?? response.data;
    return Map<String, double>.from(
      data.map((key, value) => MapEntry(key, _parseDouble(value))),
    );
  }
}

/// Parse double from various types
double _parseDouble(dynamic value) {
  if (value == null) return 0.0;
  if (value is double) return value;
  if (value is int) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0.0;
  return 0.0;
}
