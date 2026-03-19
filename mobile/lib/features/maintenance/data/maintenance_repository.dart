import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/maintenance/data/models/maintenance_model.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Maintenance repository provider
final maintenanceRepositoryProvider = Provider<MaintenanceRepository>((ref) {
  return MaintenanceRepository(ref.read(apiClientProvider));
});

/// Maintenance repository for API operations
class MaintenanceRepository {
  final ApiClient _apiClient;

  MaintenanceRepository(this._apiClient);

  /// Get all maintenance records with pagination
  Future<MaintenanceResponse> getMaintenanceRecords({
    int page = 1,
    int perPage = 20,
    String? type,
    int? vehicleId,
    String? status,
    String? search,
  }) async {
    final queryParams = <String, dynamic>{
      'page': page.toString(),
      'per_page': perPage.toString(),
    };

    if (type != null && type.isNotEmpty) {
      queryParams['type'] = type;
    }
    if (vehicleId != null) {
      queryParams['vehicle_id'] = vehicleId.toString();
    }
    if (status != null && status.isNotEmpty) {
      queryParams['status'] = status;
    }
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }

    final response = await _apiClient.get(
      '/maintenance',
      queryParameters: queryParams,
    );

    return MaintenanceResponse.fromJson(response.data);
  }

  /// Get maintenance record by ID
  Future<Maintenance> getMaintenanceById(int id) async {
    final response = await _apiClient.get('/maintenance/$id');
    final data = response.data['data'] ?? response.data;
    return Maintenance.fromJson(data);
  }

  /// Get maintenance records for a specific vehicle
  Future<MaintenanceResponse> getMaintenanceByVehicle(
    int vehicleId, {
    int page = 1,
    int perPage = 20,
    String? type,
  }) async {
    return getMaintenanceRecords(
      page: page,
      perPage: perPage,
      vehicleId: vehicleId,
      type: type,
    );
  }

  /// Create new maintenance record
  Future<Maintenance> createMaintenance(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/maintenance', data: data);
    final responseData = response.data['data'] ?? response.data;
    return Maintenance.fromJson(responseData);
  }

  /// Update maintenance record
  Future<Maintenance> updateMaintenance(int id, Map<String, dynamic> data) async {
    final response = await _apiClient.put('/maintenance/$id', data: data);
    final responseData = response.data['data'] ?? response.data;
    return Maintenance.fromJson(responseData);
  }

  /// Delete maintenance record
  Future<bool> deleteMaintenance(int id) async {
    final response = await _apiClient.delete('/maintenance/$id');
    return response.statusCode == 200 || response.statusCode == 204;
  }

  /// Get upcoming maintenance (scheduled)
  Future<List<Maintenance>> getUpcomingMaintenance({int days = 30}) async {
    final response = await _apiClient.get(
      '/maintenance/upcoming',
      queryParameters: {'days': days.toString()},
    );

    final data = response.data['data'] as List<dynamic>? ?? [];
    return data.map((d) => Maintenance.fromJson(d as Map<String, dynamic>)).toList();
  }

  /// Get maintenance statistics
  Future<Map<String, dynamic>> getMaintenanceStats({
    int? vehicleId,
    String? period,
  }) async {
    final queryParams = <String, dynamic>{};
    if (vehicleId != null) {
      queryParams['vehicle_id'] = vehicleId.toString();
    }
    if (period != null) {
      queryParams['period'] = period;
    }

    final response = await _apiClient.get(
      '/maintenance/stats',
      queryParameters: queryParams,
    );

    return response.data['data'] ?? response.data;
  }
}
