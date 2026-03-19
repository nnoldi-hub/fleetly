import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/drivers/data/models/driver_model.dart';
import 'package:fleetly_mobile/features/drivers/data/drivers_repository.dart';

/// Drivers repository provider
final driversRepositoryProvider = Provider<DriversRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return DriversRepository(apiClient);
});

/// Drivers list state
class DriversState {
  final List<Driver> drivers;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int totalPages;
  final bool hasMore;
  final String searchQuery;
  final String statusFilter;

  const DriversState({
    this.drivers = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.totalPages = 1,
    this.hasMore = false,
    this.searchQuery = '',
    this.statusFilter = 'all',
  });

  DriversState copyWith({
    List<Driver>? drivers,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? totalPages,
    bool? hasMore,
    String? searchQuery,
    String? statusFilter,
  }) {
    return DriversState(
      drivers: drivers ?? this.drivers,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      totalPages: totalPages ?? this.totalPages,
      hasMore: hasMore ?? this.hasMore,
      searchQuery: searchQuery ?? this.searchQuery,
      statusFilter: statusFilter ?? this.statusFilter,
    );
  }
}

/// Drivers list notifier
class DriversNotifier extends StateNotifier<DriversState> {
  final DriversRepository _repository;

  DriversNotifier(this._repository) : super(const DriversState());

  /// Load drivers (first page)
  Future<void> loadDrivers({String? search, String? status}) async {
    state = state.copyWith(
      isLoading: true,
      error: null,
      searchQuery: search ?? state.searchQuery,
      statusFilter: status ?? state.statusFilter,
    );

    final result = await _repository.getDrivers(
      page: 1,
      search: search ?? state.searchQuery,
      status: status ?? state.statusFilter,
    );

    result.fold(
      (failure) => state = state.copyWith(
        isLoading: false,
        error: failure.message,
      ),
      (response) => state = state.copyWith(
        isLoading: false,
        drivers: response.drivers,
        currentPage: response.page,
        totalPages: response.totalPages,
        hasMore: response.hasMore,
      ),
    );
  }

  /// Load more drivers (next page)
  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);

    final result = await _repository.getDrivers(
      page: state.currentPage + 1,
      search: state.searchQuery,
      status: state.statusFilter,
    );

    result.fold(
      (failure) => state = state.copyWith(
        isLoadingMore: false,
        error: failure.message,
      ),
      (response) => state = state.copyWith(
        isLoadingMore: false,
        drivers: [...state.drivers, ...response.drivers],
        currentPage: response.page,
        totalPages: response.totalPages,
        hasMore: response.hasMore,
      ),
    );
  }

  /// Refresh drivers
  Future<void> refresh() async {
    await loadDrivers();
  }

  /// Search drivers
  Future<void> search(String query) async {
    await loadDrivers(search: query);
  }

  /// Filter by status
  Future<void> filterByStatus(String status) async {
    await loadDrivers(status: status);
  }

  /// Clear filters
  Future<void> clearFilters() async {
    await loadDrivers(search: '', status: 'all');
  }

  /// Delete driver from local state
  void removeDriver(int id) {
    state = state.copyWith(
      drivers: state.drivers.where((d) => d.id != id).toList(),
    );
  }

  /// Update driver in local state
  void updateDriver(Driver driver) {
    state = state.copyWith(
      drivers: state.drivers.map((d) => d.id == driver.id ? driver : d).toList(),
    );
  }

  /// Add driver to local state
  void addDriver(Driver driver) {
    state = state.copyWith(
      drivers: [driver, ...state.drivers],
    );
  }
}

/// Drivers state provider
final driversProvider = StateNotifierProvider<DriversNotifier, DriversState>((ref) {
  final repository = ref.watch(driversRepositoryProvider);
  return DriversNotifier(repository);
});

/// Single driver detail state
class DriverDetailState {
  final Driver? driver;
  final bool isLoading;
  final String? error;

  const DriverDetailState({
    this.driver,
    this.isLoading = false,
    this.error,
  });

  DriverDetailState copyWith({
    Driver? driver,
    bool? isLoading,
    String? error,
  }) {
    return DriverDetailState(
      driver: driver ?? this.driver,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

/// Driver detail notifier
class DriverDetailNotifier extends StateNotifier<DriverDetailState> {
  final DriversRepository _repository;

  DriverDetailNotifier(this._repository) : super(const DriverDetailState());

  /// Load driver details
  Future<void> loadDriver(int id) async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _repository.getDriver(id);

    result.fold(
      (failure) => state = state.copyWith(
        isLoading: false,
        error: failure.message,
      ),
      (driver) => state = state.copyWith(
        isLoading: false,
        driver: driver,
      ),
    );
  }

  /// Delete driver
  Future<bool> deleteDriver(int id) async {
    final result = await _repository.deleteDriver(id);
    return result.fold(
      (failure) => false,
      (success) => true,
    );
  }

  /// Assign vehicle to driver
  Future<bool> assignVehicle(int driverId, int vehicleId) async {
    final result = await _repository.assignVehicle(driverId, vehicleId);
    return result.fold(
      (failure) => false,
      (driver) {
        state = state.copyWith(driver: driver);
        return true;
      },
    );
  }

  /// Unassign vehicle from driver
  Future<bool> unassignVehicle(int driverId) async {
    final result = await _repository.unassignVehicle(driverId);
    return result.fold(
      (failure) => false,
      (driver) {
        state = state.copyWith(driver: driver);
        return true;
      },
    );
  }

  /// Reset state
  void reset() {
    state = const DriverDetailState();
  }
}

/// Driver detail provider
final driverDetailProvider = StateNotifierProvider<DriverDetailNotifier, DriverDetailState>((ref) {
  final repository = ref.watch(driversRepositoryProvider);
  return DriverDetailNotifier(repository);
});

/// Driver form state
class DriverFormState {
  final bool isSubmitting;
  final String? error;
  final Driver? savedDriver;

  const DriverFormState({
    this.isSubmitting = false,
    this.error,
    this.savedDriver,
  });

  DriverFormState copyWith({
    bool? isSubmitting,
    String? error,
    Driver? savedDriver,
  }) {
    return DriverFormState(
      isSubmitting: isSubmitting ?? this.isSubmitting,
      error: error,
      savedDriver: savedDriver ?? this.savedDriver,
    );
  }
}

/// Driver form notifier
class DriverFormNotifier extends StateNotifier<DriverFormState> {
  final DriversRepository _repository;

  DriverFormNotifier(this._repository) : super(const DriverFormState());

  /// Create new driver
  Future<bool> createDriver(Map<String, dynamic> data) async {
    state = state.copyWith(isSubmitting: true, error: null);

    final result = await _repository.createDriver(data);

    return result.fold(
      (failure) {
        state = state.copyWith(
          isSubmitting: false,
          error: failure.message,
        );
        return false;
      },
      (driver) {
        state = state.copyWith(
          isSubmitting: false,
          savedDriver: driver,
        );
        return true;
      },
    );
  }

  /// Update driver
  Future<bool> updateDriver(int id, Map<String, dynamic> data) async {
    state = state.copyWith(isSubmitting: true, error: null);

    final result = await _repository.updateDriver(id, data);

    return result.fold(
      (failure) {
        state = state.copyWith(
          isSubmitting: false,
          error: failure.message,
        );
        return false;
      },
      (driver) {
        state = state.copyWith(
          isSubmitting: false,
          savedDriver: driver,
        );
        return true;
      },
    );
  }

  /// Reset form state
  void reset() {
    state = const DriverFormState();
  }
}

/// Driver form provider
final driverFormProvider = StateNotifierProvider<DriverFormNotifier, DriverFormState>((ref) {
  final repository = ref.watch(driversRepositoryProvider);
  return DriverFormNotifier(repository);
});

/// Available drivers provider (for vehicle assignment)
final availableDriversProvider = FutureProvider<List<Driver>>((ref) async {
  final repository = ref.watch(driversRepositoryProvider);
  final result = await repository.getAvailableDrivers();
  return result.fold(
    (failure) => throw Exception(failure.message),
    (drivers) => drivers,
  );
});
