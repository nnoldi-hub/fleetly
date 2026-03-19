import 'package:dartz/dartz.dart';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fleetly_mobile/core/errors/failures.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/vehicles/data/models/vehicle_model.dart';

/// Vehicles Repository Provider
final vehiclesRepositoryProvider = Provider<VehiclesRepository>((ref) {
  return VehiclesRepository(dio: ref.watch(dioProvider));
});

/// Vehicles Repository
class VehiclesRepository {
  final Dio dio;

  VehiclesRepository({required this.dio});

  /// Get paginated list of vehicles
  Future<Either<Failure, VehiclesResponse>> getVehicles({
    int page = 1,
    int perPage = 20,
    String? search,
    String? status,
    String? type,
    String? fuelType,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'per_page': perPage,
      };

      if (search != null && search.isNotEmpty) {
        queryParams['search'] = search;
      }
      if (status != null && status.isNotEmpty) {
        queryParams['status'] = status;
      }
      if (type != null && type.isNotEmpty) {
        queryParams['type'] = type;
      }
      if (fuelType != null && fuelType.isNotEmpty) {
        queryParams['fuel_type'] = fuelType;
      }

      final response = await dio.get(
        '/vehicles',
        queryParameters: queryParams,
      );

      if (response.statusCode == 200) {
        final data = response.data;
        if (data['success'] == true) {
          return Right(VehiclesResponse.fromJson(data));
        }
        return Left(ServerFailure(message: data['message'] ?? 'Eroare la încărcarea vehiculelor'));
      }

      return const Left(ServerFailure(message: 'Eroare la încărcarea vehiculelor'));
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        return const Left(NetworkFailure(message: 'Conexiune timeout'));
      }
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(message: 'Sesiune expirată'));
      }
      return Left(ServerFailure(message: e.message ?? 'Eroare de rețea'));
    } catch (e) {
      return Left(ServerFailure(message: 'Eroare: $e'));
    }
  }

  /// Get single vehicle by ID
  Future<Either<Failure, Vehicle>> getVehicle(int id) async {
    try {
      final response = await dio.get('/vehicles/$id');

      if (response.statusCode == 200) {
        final data = response.data;
        if (data['success'] == true && data['data'] != null) {
          return Right(Vehicle.fromJson(data['data']));
        }
        return Left(ServerFailure(message: data['message'] ?? 'Vehicul negăsit'));
      }

      return const Left(ServerFailure(message: 'Eroare la încărcarea vehiculului'));
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) {
        return const Left(ServerFailure(message: 'Vehicul negăsit'));
      }
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(message: 'Sesiune expirată'));
      }
      return Left(ServerFailure(message: e.message ?? 'Eroare de rețea'));
    } catch (e) {
      return Left(ServerFailure(message: 'Eroare: $e'));
    }
  }

  /// Create new vehicle
  Future<Either<Failure, Vehicle>> createVehicle(Map<String, dynamic> data) async {
    try {
      final response = await dio.post('/vehicles', data: data);

      if (response.statusCode == 201 || response.statusCode == 200) {
        final responseData = response.data;
        if (responseData['success'] == true && responseData['data'] != null) {
          return Right(Vehicle.fromJson(responseData['data']));
        }
        return Left(ServerFailure(message: responseData['message'] ?? 'Eroare la crearea vehiculului'));
      }

      return const Left(ServerFailure(message: 'Eroare la crearea vehiculului'));
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        final errors = e.response?.data['errors'];
        if (errors != null && errors is Map) {
          final firstError = errors.values.first;
          return Left(ValidationFailure(message: firstError is List ? firstError.first : firstError.toString()));
        }
        return Left(ValidationFailure(message: e.response?.data['message'] ?? 'Date invalide'));
      }
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(message: 'Sesiune expirată'));
      }
      return Left(ServerFailure(message: e.message ?? 'Eroare de rețea'));
    } catch (e) {
      return Left(ServerFailure(message: 'Eroare: $e'));
    }
  }

  /// Update existing vehicle
  Future<Either<Failure, Vehicle>> updateVehicle(int id, Map<String, dynamic> data) async {
    try {
      final response = await dio.put('/vehicles/$id', data: data);

      if (response.statusCode == 200) {
        final responseData = response.data;
        if (responseData['success'] == true && responseData['data'] != null) {
          return Right(Vehicle.fromJson(responseData['data']));
        }
        return Left(ServerFailure(message: responseData['message'] ?? 'Eroare la actualizarea vehiculului'));
      }

      return const Left(ServerFailure(message: 'Eroare la actualizarea vehiculului'));
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        final errors = e.response?.data['errors'];
        if (errors != null && errors is Map) {
          final firstError = errors.values.first;
          return Left(ValidationFailure(message: firstError is List ? firstError.first : firstError.toString()));
        }
        return Left(ValidationFailure(message: e.response?.data['message'] ?? 'Date invalide'));
      }
      if (e.response?.statusCode == 404) {
        return const Left(ServerFailure(message: 'Vehicul negăsit'));
      }
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(message: 'Sesiune expirată'));
      }
      return Left(ServerFailure(message: e.message ?? 'Eroare de rețea'));
    } catch (e) {
      return Left(ServerFailure(message: 'Eroare: $e'));
    }
  }

  /// Delete vehicle
  Future<Either<Failure, bool>> deleteVehicle(int id) async {
    try {
      final response = await dio.delete('/vehicles/$id');

      if (response.statusCode == 200) {
        final responseData = response.data;
        if (responseData['success'] == true) {
          return const Right(true);
        }
        return Left(ServerFailure(message: responseData['message'] ?? 'Eroare la ștergerea vehiculului'));
      }

      return const Left(ServerFailure(message: 'Eroare la ștergerea vehiculului'));
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) {
        return const Left(ServerFailure(message: 'Vehicul negăsit'));
      }
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(message: 'Sesiune expirată'));
      }
      return Left(ServerFailure(message: e.message ?? 'Eroare de rețea'));
    } catch (e) {
      return Left(ServerFailure(message: 'Eroare: $e'));
    }
  }

  /// Update vehicle mileage
  Future<Either<Failure, Vehicle>> updateMileage(int id, int mileage) async {
    try {
      final response = await dio.post('/vehicles/$id/mileage', data: {
        'mileage': mileage,
      });

      if (response.statusCode == 200) {
        final responseData = response.data;
        if (responseData['success'] == true && responseData['data'] != null) {
          return Right(Vehicle.fromJson(responseData['data']));
        }
        return Left(ServerFailure(message: responseData['message'] ?? 'Eroare la actualizarea kilometrajului'));
      }

      return const Left(ServerFailure(message: 'Eroare la actualizarea kilometrajului'));
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(message: 'Sesiune expirată'));
      }
      return Left(ServerFailure(message: e.message ?? 'Eroare de rețea'));
    } catch (e) {
      return Left(ServerFailure(message: 'Eroare: $e'));
    }
  }
}
