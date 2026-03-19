import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fleetly_mobile/features/auth/data/auth_repository.dart';
import 'package:fleetly_mobile/features/auth/data/models/user_model.dart';

/// Auth state
enum AuthStatus {
  initial,
  loading,
  authenticated,
  unauthenticated,
  error,
}

class AuthState {
  final AuthStatus status;
  final User? user;
  final String? errorMessage;

  const AuthState({
    this.status = AuthStatus.initial,
    this.user,
    this.errorMessage,
  });

  AuthState copyWith({
    AuthStatus? status,
    User? user,
    String? errorMessage,
  }) {
    return AuthState(
      status: status ?? this.status,
      user: user ?? this.user,
      errorMessage: errorMessage,
    );
  }

  bool get isAuthenticated => status == AuthStatus.authenticated;
  bool get isLoading => status == AuthStatus.loading;
}

/// Auth notifier provider
final authNotifierProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.watch(authRepositoryProvider));
});

/// Auth state notifier
class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _authRepository;

  AuthNotifier(this._authRepository) : super(const AuthState()) {
    checkAuthStatus();
  }

  /// Check if user is already logged in
  Future<void> checkAuthStatus() async {
    state = state.copyWith(status: AuthStatus.loading);
    
    final isLoggedIn = await _authRepository.isLoggedIn();
    
    if (isLoggedIn) {
      // Try to get cached user first
      final cachedUser = await _authRepository.getCachedUser();
      if (cachedUser != null) {
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: cachedUser,
        );
        
        // Refresh user data from API in background
        _refreshUserData();
      } else {
        // No cached user, fetch from API
        final result = await _authRepository.getCurrentUser();
        result.fold(
          (failure) {
            state = state.copyWith(
              status: AuthStatus.unauthenticated,
              errorMessage: failure.message,
            );
          },
          (user) {
            state = state.copyWith(
              status: AuthStatus.authenticated,
              user: user,
            );
          },
        );
      }
    } else {
      state = state.copyWith(status: AuthStatus.unauthenticated);
    }
  }

  /// Refresh user data from API
  Future<void> _refreshUserData() async {
    final result = await _authRepository.getCurrentUser();
    result.fold(
      (failure) {
        // If refresh fails with 401, logout
        if (failure.code == 401) {
          logout();
        }
      },
      (user) {
        if (mounted) {
          state = state.copyWith(user: user);
        }
      },
    );
  }

  /// Login
  Future<bool> login({
    required String username,
    required String password,
  }) async {
    state = state.copyWith(status: AuthStatus.loading, errorMessage: null);
    
    final result = await _authRepository.login(
      username: username,
      password: password,
    );
    
    return result.fold(
      (failure) {
        state = state.copyWith(
          status: AuthStatus.error,
          errorMessage: failure.message,
        );
        return false;
      },
      (loginResponse) {
        state = state.copyWith(
          status: AuthStatus.authenticated,
          user: loginResponse.user,
          errorMessage: null,
        );
        return true;
      },
    );
  }

  /// Logout
  Future<void> logout() async {
    state = state.copyWith(status: AuthStatus.loading);
    await _authRepository.logout();
    state = const AuthState(status: AuthStatus.unauthenticated);
  }

  /// Clear error
  void clearError() {
    state = state.copyWith(errorMessage: null);
  }
}
