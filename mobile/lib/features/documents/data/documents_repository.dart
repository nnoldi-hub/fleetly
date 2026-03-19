import 'package:dartz/dartz.dart';
import 'package:fleetly_mobile/core/errors/failures.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/documents/data/models/document_model.dart';

/// Documents repository for API operations
class DocumentsRepository {
  final ApiClient _apiClient;

  DocumentsRepository(this._apiClient);

  /// Get paginated list of documents
  Future<Either<Failure, DocumentsResponse>> getDocuments({
    int page = 1,
    int perPage = 10,
    String? search,
    String? type,
    String? status,
    int? vehicleId,
    int? driverId,
    bool? expiringSoon,
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
      if (type != null && type.isNotEmpty) {
        queryParams['type'] = type;
      }
      if (status != null && status.isNotEmpty && status != 'all') {
        queryParams['status'] = status;
      }
      if (vehicleId != null) {
        queryParams['vehicle_id'] = vehicleId.toString();
      }
      if (driverId != null) {
        queryParams['driver_id'] = driverId.toString();
      }
      if (expiringSoon == true) {
        queryParams['expiring_soon'] = 'true';
      }
      if (sortBy != null) {
        queryParams['sort_by'] = sortBy;
      }
      if (sortOrder != null) {
        queryParams['sort_order'] = sortOrder;
      }

      final response = await _apiClient.get('/documents', queryParameters: queryParams);

      if (response.data['success'] == true) {
        final documentsResponse = DocumentsResponse.fromJson(response.data);
        return Right(documentsResponse);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch documents',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get single document by ID
  Future<Either<Failure, Document>> getDocument(int id) async {
    try {
      final response = await _apiClient.get('/documents/$id');

      if (response.data['success'] == true) {
        final document = Document.fromJson(response.data['data']);
        return Right(document);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch document',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Create new document
  Future<Either<Failure, Document>> createDocument(Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.post('/documents', data: data);

      if (response.data['success'] == true) {
        final document = Document.fromJson(response.data['data']);
        return Right(document);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to create document',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Update document
  Future<Either<Failure, Document>> updateDocument(int id, Map<String, dynamic> data) async {
    try {
      final response = await _apiClient.put('/documents/$id', data: data);

      if (response.data['success'] == true) {
        final document = Document.fromJson(response.data['data']);
        return Right(document);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to update document',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Delete document
  Future<Either<Failure, bool>> deleteDocument(int id) async {
    try {
      final response = await _apiClient.delete('/documents/$id');

      if (response.data['success'] == true) {
        return const Right(true);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to delete document',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get documents for a specific vehicle
  Future<Either<Failure, List<Document>>> getVehicleDocuments(int vehicleId) async {
    try {
      final response = await _apiClient.get('/vehicles/$vehicleId/documents');

      if (response.data['success'] == true) {
        final data = response.data['data'] as List<dynamic>;
        final documents = data.map((d) => Document.fromJson(d as Map<String, dynamic>)).toList();
        return Right(documents);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch vehicle documents',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get documents for a specific driver
  Future<Either<Failure, List<Document>>> getDriverDocuments(int driverId) async {
    try {
      final response = await _apiClient.get('/drivers/$driverId/documents');

      if (response.data['success'] == true) {
        final data = response.data['data'] as List<dynamic>;
        final documents = data.map((d) => Document.fromJson(d as Map<String, dynamic>)).toList();
        return Right(documents);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch driver documents',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get expiring documents
  Future<Either<Failure, List<Document>>> getExpiringDocuments({int daysAhead = 30}) async {
    try {
      final response = await _apiClient.get('/documents/expiring', queryParameters: {
        'days': daysAhead.toString(),
      });

      if (response.data['success'] == true) {
        final data = response.data['data'] as List<dynamic>;
        final documents = data.map((d) => Document.fromJson(d as Map<String, dynamic>)).toList();
        return Right(documents);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch expiring documents',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }

  /// Get expired documents
  Future<Either<Failure, List<Document>>> getExpiredDocuments() async {
    try {
      final response = await _apiClient.get('/documents/expired');

      if (response.data['success'] == true) {
        final data = response.data['data'] as List<dynamic>;
        final documents = data.map((d) => Document.fromJson(d as Map<String, dynamic>)).toList();
        return Right(documents);
      } else {
        return Left(ServerFailure(
          message: response.data['message'] ?? 'Failed to fetch expired documents',
        ));
      }
    } catch (e) {
      return Left(ServerFailure(message: e.toString()));
    }
  }
}
