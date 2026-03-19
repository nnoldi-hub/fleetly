import 'package:dartz/dartz.dart';
import 'package:fleetly_mobile/core/errors/failures.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/drivers/data/models/driver_model.dart';

/// Drivers repository for API operations
class DriversRepository {
  final ApiClient _apiClient;

  DriversRepository(this._apiClient);

  /// Get paginated list of drivers
  Future<Either<Failure, DriversResponse>> getDrivers({
    int page = 1,
    int perPage = 10,
    String? search,
    String? status,
    String? sortBy,
    String? sortOrder,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page.toString(),
        'per_page': perPage.toString(),
      };

      if (search != null && search.isNotEmpty) {
        queryParams['search'] = search;
      }
      if (status != null && status.isNotEmpty && status != 'all') {
        queryParams['status'] = status;
      }
      if (sortBy != null) {
        queryParams['sort_by'] = sortBy;
      }
      if (sortOrder != null) {
        queryParams['sort_order'] = sortOrder;
      }

      final response = await _apiClient.get('/drivers', queryParameters: queryParams);
      
      if (response.data['success'] == true) {
        final driversResponse = DriversResponse.fromJson(response.data);
        return Right(driversResponse);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch drivers',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get single driver by ID
  Future<Either<Failure, Driver>> getDriver(int id) async {
    try {
      final response = await _apiClient.get('/drivers/$id');
      
      if (response.data['success'] == true) {
        final driver = Driver.fromJson(response.data['data']);
        return Right(driver);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch driver',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Create new driver
  Future<Either<Failure, Driver>> createDriver(Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.post('/drivers', data: data);
      
      if (response.data['success'] == true) {
        final driver = Driver.fromJson(response.data['data']);
        return Right(driver);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to create driver',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Update driver
  Future<Either<Failure, Driver>> updateDriver(int id, Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.put('/drivers/$id', data: data);
      
      if (response.data['success'] == true) {
        final driver = Driver.fromJson(response.data['data']);
        return Right(driver);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to update driver',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Delete driver
  Future<Either<Failure, bool>> deleteDriver(int id) async {
    try {
      final response = await _apiClient.delete('/drivers/$id');
      
      if (response.data['success'] == true) {
        return const Right(true);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to delete driver',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Assign vehicle to driver
  Future<Either<Failure, Driver>> assignVehicle(int driverId, int vehicleId) async {
    try {
      final response = await _apiClient.post('/drivers/$driverId/assign-vehicle', data: {
        'vehicle_id': vehicleId,
      });
      
      if (response.data['success'] == true) {
        final driver = Driver.fromJson(response.data['data']);
        return Right(driver);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to assign vehicle',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Unassign vehicle from driver
  Future<Either<Failure, Driver>> unassignVehicle(int driverId) async {
    try {
      final response = await _apiClient.post('/drivers/$driverId/unassign-vehicle');
      
      if (response.data['success'] == true) {
        final driver = Driver.fromJson(response.data['data']);
        return Right(driver);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to unassign vehicle',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get available drivers (without assigned vehicles)
  Future<Either<Failure, List<Driver>>> getAvailableDrivers() async {
    try {
      final response = await _apiClient.get('/drivers', queryParameters: {
        'available': 'true',
        'per_page': '100',
      });
      
      if (response.data['success'] == true) {
        final data = response.data['data'] as List<dynamic>;
        final drivers = data.map((d) => Driver.fromJson(d as Map<String, dynamic>)).toList();
        return Right(drivers);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch available drivers',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get drivers with expiring documents
  Future<Either<Failure, List<Driver>>> getDriversWithExpiringDocs({int daysAhead = 30}) async {
    try {
      final response = await _apiClient.get('/drivers/expiring', queryParameters: {
        'days': daysAhead.toString(),
      });
      
      if (response.data['success'] == true) {
        final data = response.data['data'] as List<dynamic>;
        final drivers = data.map((d) => Driver.fromJson(d as Map<String, dynamic>)).toList();
        return Right(drivers);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch drivers with expiring docs',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }
}
