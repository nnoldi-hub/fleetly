import 'package:dartz/dartz.dart';
import '../../../../core/network/api_client.dart';
import 'models/notification_model.dart';

class NotificationRepository {
  final ApiClient _apiClient;

  NotificationRepository(this._apiClient);

  /// Get paginated notifications
  Future<Either<String, NotificationsResponse>> getNotifications({
    int page = 1,
    int perPage = 20,
    bool? unreadOnly,
  }) async {
    try {
      final queryParams = <String, dynamic>{
        'page': page,
        'per_page': perPage,
      };

      if (unreadOnly == true) {
        queryParams['unread'] = 'true';
      }

      final response = await _apiClient.get(
        '/notifications',
        queryParameters: queryParams,
      );

      return Right(NotificationsResponse.fromJson(response.data));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Get unread notifications count
  Future<Either<String, int>> getUnreadCount() async {
    try {
      final response = await _apiClient.get('/notifications/unread-count');
      final count = response.data['count'] ?? 0;
      return Right(count is int ? count : int.tryParse(count.toString()) ?? 0);
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Mark a notification as read
  Future<Either<String, bool>> markAsRead(int id) async {
    try {
      await _apiClient.post('/notifications/$id/read');
      return const Right(true);
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Mark all notifications as read
  Future<Either<String, bool>> markAllAsRead() async {
    try {
      await _apiClient.post('/notifications/read-all');
      return const Right(true);
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Delete a notification
  Future<Either<String, bool>> deleteNotification(int id) async {
    try {
      await _apiClient.delete('/notifications/$id');
      return const Right(true);
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Register device for push notifications
  Future<Either<String, bool>> registerDevice({
    required String token,
    required String platform,
    String? deviceName,
  }) async {
    try {
      await _apiClient.post('/notifications/register-device', data: {
        'token': token,
        'platform': platform,
        if (deviceName != null) 'device_name': deviceName,
      });
      return const Right(true);
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Unregister device from push notifications
  Future<Either<String, bool>> unregisterDevice(String token) async {
    try {
      await _apiClient.post('/notifications/unregister-device', data: {
        'token': token,
      });
      return const Right(true);
    } catch (e) {
      return Left(e.toString());
    }
  }
}
