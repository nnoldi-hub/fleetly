import 'package:dartz/dartz.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/insurance/data/models/insurance_model.dart';

/// Insurance repository
class InsuranceRepository {
  final ApiClient _apiClient;

  InsuranceRepository(this._apiClient);

  /// Get paginated insurance list
  Future<Either<String, InsuranceResponse>> getInsurances({
    int page = 1,
    int perPage = 20,
    int? vehicleId,
    String? type,
    String? status,
  }) async {
    try {
      final params = <String, dynamic>{
        'page': page,
        'per_page': perPage,
      };

      if (vehicleId != null) params['vehicle_id'] = vehicleId;
      if (type != null && type.isNotEmpty) params['type'] = type;
      if (status != null && status.isNotEmpty) params['status'] = status;

      final response = await _apiClient.get('/insurance', queryParameters: params);
      return Right(InsuranceResponse.fromJson(response.data));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Get insurance by ID
  Future<Either<String, Insurance>> getInsurance(int id) async {
    try {
      final response = await _apiClient.get('/insurance/$id');
      return Right(Insurance.fromJson(response.data['data']));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Create insurance
  Future<Either<String, Insurance>> createInsurance(Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.post('/insurance', data: data);
      return Right(Insurance.fromJson(response.data['data']));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Update insurance
  Future<Either<String, Insurance>> updateInsurance(int id, Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.put('/insurance/$id', data: data);
      return Right(Insurance.fromJson(response.data['data']));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Delete insurance
  Future<Either<String, void>> deleteInsurance(int id) async {
    try {
      await _apiClient.delete('/insurance/$id');
      return const Right(null);
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Get expiring insurances
  Future<Either<String, List<ExpiringInsurance>>> getExpiringInsurances({
    int days = 30,
  }) async {
    try {
      final response = await _apiClient.get(
        '/insurance/expiring',
        queryParameters: {'days': days},
      );
      final records = response.data['data']['records'] as List? ?? [];
      return Right(records.map((e) => ExpiringInsurance.fromJson(e)).toList());
    } catch (e) {
      return Left(e.toString());
    }
  }
}
