import 'dart:convert';
import 'package:dartz/dartz.dart';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:fleetly_mobile/core/config/app_config.dart';
import 'package:fleetly_mobile/core/errors/failures.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/auth/data/models/user_model.dart';

/// Auth Repository Provider
final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository(
    dio: ref.watch(dioProvider),
    secureStorage: ref.watch(secureStorageProvider),
  );
});

/// Auth Repository
class AuthRepository {
  final Dio dio;
  final FlutterSecureStorage secureStorage;

  AuthRepository({
    required this.dio,
    required this.secureStorage,
  });

  /// Login with username and password
  Future<Either<Failure, LoginResponse>> login({
    required String username,
    required String password,
  }) async {
    try {
      final response = await dio.post(
        '/auth/login',
        data: {
          'username': username,
          'password': password,
        },
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final loginResponse = LoginResponse.fromJson(response.data['data']);
        
        // Save tokens
        await secureStorage.write(
          key: AppConfig.accessTokenKey,
          value: loginResponse.tokens.accessToken,
        );
        await secureStorage.write(
          key: AppConfig.refreshTokenKey,
          value: loginResponse.tokens.refreshToken,
        );
        
        // Save user data
        await secureStorage.write(
          key: AppConfig.userDataKey,
          value: jsonEncode(loginResponse.user.toJson()),
        );

        return Right(loginResponse);
      }

      return Left(AuthFailure(
        message: response.data['message'] ?? 'Eroare la autentificare',
        code: response.statusCode,
      ));
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(
          message: 'Credențiale invalide',
          code: 401,
        ));
      }
      if (e.type == DioExceptionType.connectionTimeout ||
          e.type == DioExceptionType.receiveTimeout) {
        return const Left(NetworkFailure());
      }
      return Left(ServerFailure(
        message: e.response?.data?['message'] ?? 'Eroare server',
        code: e.response?.statusCode,
      ));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  /// Get current user from API
  Future<Either<Failure, User>> getCurrentUser() async {
    try {
      final response = await dio.get('/auth/me');

      if (response.statusCode == 200 && response.data['success'] == true) {
        final user = User.fromJson(response.data['data']);
        
        // Update cached user data
        await secureStorage.write(
          key: AppConfig.userDataKey,
          value: jsonEncode(user.toJson()),
        );
        
        return Right(user);
      }

      return Left(AuthFailure(
        message: response.data['message'] ?? 'Eroare la obținerea datelor',
      ));
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        return const Left(AuthFailure(
          message: 'Sesiune expirată',
          code: 401,
        ));
      }
      return Left(ServerFailure(
        message: e.response?.data?['message'] ?? 'Eroare server',
      ));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }

  /// Get cached user data
  Future<User?> getCachedUser() async {
    try {
      final userData = await secureStorage.read(key: AppConfig.userDataKey);
      if (userData != null) {
        return User.fromJson(jsonDecode(userData));
      }
    } catch (_) {}
    return null;
  }

  /// Check if user is logged in
  Future<bool> isLoggedIn() async {
    final token = await secureStorage.read(key: AppConfig.accessTokenKey);
    return token != null && token.isNotEmpty;
  }

  /// Logout
  Future<void> logout() async {
    try {
      await dio.post('/auth/logout');
    } catch (_) {
      // Ignore errors on logout
    }
    
    // Clear stored data
    await secureStorage.delete(key: AppConfig.accessTokenKey);
    await secureStorage.delete(key: AppConfig.refreshTokenKey);
    await secureStorage.delete(key: AppConfig.userDataKey);
  }

  /// Forgot password
  Future<Either<Failure, String>> forgotPassword(String email) async {
    try {
      final response = await dio.post(
        '/auth/forgot-password',
        data: {'email': email},
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        return Right(response.data['message'] ?? 'Email trimis');
      }

      return Left(AuthFailure(
        message: response.data['message'] ?? 'Eroare',
      ));
    } on DioException catch (e) {
      return Left(ServerFailure(
        message: e.response?.data?['message'] ?? 'Eroare server',
      ));
    } catch (e) {
      return Left(UnknownFailure(message: e.toString()));
    }
  }
}
