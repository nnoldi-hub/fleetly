import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fleetly_mobile/features/vehicles/data/models/vehicle_model.dart';
import 'package:fleetly_mobile/features/vehicles/data/vehicles_repository.dart';

/// Vehicles list state
class VehiclesState {
  final List<Vehicle> vehicles;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int totalPages;
  final bool hasMore;
  final String? searchQuery;
  final String? statusFilter;
  final String? typeFilter;

  const VehiclesState({
    this.vehicles = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.totalPages = 1,
    this.hasMore = false,
    this.searchQuery,
    this.statusFilter,
    this.typeFilter,
  });

  VehiclesState copyWith({
    List<Vehicle>? vehicles,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? totalPages,
    bool? hasMore,
    String? searchQuery,
    String? statusFilter,
    String? typeFilter,
  }) {
    return VehiclesState(
      vehicles: vehicles ?? this.vehicles,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      totalPages: totalPages ?? this.totalPages,
      hasMore: hasMore ?? this.hasMore,
      searchQuery: searchQuery ?? this.searchQuery,
      statusFilter: statusFilter ?? this.statusFilter,
      typeFilter: typeFilter ?? this.typeFilter,
    );
  }
}

/// Vehicles list provider
final vehiclesProvider = StateNotifierProvider<VehiclesNotifier, VehiclesState>((ref) {
  return VehiclesNotifier(ref.watch(vehiclesRepositoryProvider));
});

/// Vehicles list notifier
class VehiclesNotifier extends StateNotifier<VehiclesState> {
  final VehiclesRepository _repository;

  VehiclesNotifier(this._repository) : super(const VehiclesState());

  /// Load initial vehicles
  Future<void> loadVehicles({
    String? search,
    String? status,
    String? type,
  }) async {
    state = state.copyWith(
      isLoading: true,
      error: null,
      searchQuery: search,
      statusFilter: status,
      typeFilter: type,
    );

    final result = await _repository.getVehicles(
      page: 1,
      search: search,
      status: status,
      type: type,
    );

    result.fold(
      (failure) {
        state = state.copyWith(
          isLoading: false,
          error: failure.message,
        );
      },
      (response) {
        state = state.copyWith(
          vehicles: response.vehicles,
          isLoading: false,
          currentPage: response.page,
          totalPages: response.totalPages,
          hasMore: response.hasMore,
        );
      },
    );
  }

  /// Load more vehicles (pagination)
  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);

    final result = await _repository.getVehicles(
      page: state.currentPage + 1,
      search: state.searchQuery,
      status: state.statusFilter,
      type: state.typeFilter,
    );

    result.fold(
      (failure) {
        state = state.copyWith(
          isLoadingMore: false,
          error: failure.message,
        );
      },
      (response) {
        state = state.copyWith(
          vehicles: [...state.vehicles, ...response.vehicles],
          isLoadingMore: false,
          currentPage: response.page,
          totalPages: response.totalPages,
          hasMore: response.hasMore,
        );
      },
    );
  }

  /// Refresh vehicles list
  Future<void> refresh() async {
    await loadVehicles(
      search: state.searchQuery,
      status: state.statusFilter,
      type: state.typeFilter,
    );
  }

  /// Search vehicles
  Future<void> search(String query) async {
    await loadVehicles(
      search: query.isEmpty ? null : query,
      status: state.statusFilter,
      type: state.typeFilter,
    );
  }

  /// Filter by status
  Future<void> filterByStatus(String? status) async {
    await loadVehicles(
      search: state.searchQuery,
      status: status,
      type: state.typeFilter,
    );
  }

  /// Filter by type
  Future<void> filterByType(String? type) async {
    await loadVehicles(
      search: state.searchQuery,
      status: state.statusFilter,
      type: type,
    );
  }

  /// Clear all filters
  Future<void> clearFilters() async {
    await loadVehicles();
  }

  /// Delete vehicle from list
  void removeVehicle(int id) {
    state = state.copyWith(
      vehicles: state.vehicles.where((v) => v.id != id).toList(),
    );
  }

  /// Update vehicle in list
  void updateVehicleInList(Vehicle vehicle) {
    state = state.copyWith(
      vehicles: state.vehicles.map((v) => v.id == vehicle.id ? vehicle : v).toList(),
    );
  }

  /// Add vehicle to list
  void addVehicle(Vehicle vehicle) {
    state = state.copyWith(
      vehicles: [vehicle, ...state.vehicles],
    );
  }
}

/// Single vehicle detail state
class VehicleDetailState {
  final Vehicle? vehicle;
  final bool isLoading;
  final String? error;

  const VehicleDetailState({
    this.vehicle,
    this.isLoading = false,
    this.error,
  });

  VehicleDetailState copyWith({
    Vehicle? vehicle,
    bool? isLoading,
    String? error,
  }) {
    return VehicleDetailState(
      vehicle: vehicle ?? this.vehicle,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

/// Vehicle detail provider family (for individual vehicle)
final vehicleDetailProvider = StateNotifierProvider.family<VehicleDetailNotifier, VehicleDetailState, int>((ref, id) {
  return VehicleDetailNotifier(ref.watch(vehiclesRepositoryProvider), id);
});

/// Vehicle detail notifier
class VehicleDetailNotifier extends StateNotifier<VehicleDetailState> {
  final VehiclesRepository _repository;
  final int vehicleId;

  VehicleDetailNotifier(this._repository, this.vehicleId) : super(const VehicleDetailState()) {
    loadVehicle();
  }

  /// Load vehicle details
  Future<void> loadVehicle() async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _repository.getVehicle(vehicleId);

    result.fold(
      (failure) {
        state = state.copyWith(
          isLoading: false,
          error: failure.message,
        );
      },
      (vehicle) {
        state = state.copyWith(
          vehicle: vehicle,
          isLoading: false,
        );
      },
    );
  }

  /// Update mileage
  Future<bool> updateMileage(int mileage) async {
    final result = await _repository.updateMileage(vehicleId, mileage);

    return result.fold(
      (failure) => false,
      (vehicle) {
        state = state.copyWith(vehicle: vehicle);
        return true;
      },
    );
  }
}

/// Vehicle form state for create/edit
class VehicleFormState {
  final bool isSubmitting;
  final String? error;
  final Vehicle? savedVehicle;

  const VehicleFormState({
    this.isSubmitting = false,
    this.error,
    this.savedVehicle,
  });

  VehicleFormState copyWith({
    bool? isSubmitting,
    String? error,
    Vehicle? savedVehicle,
  }) {
    return VehicleFormState(
      isSubmitting: isSubmitting ?? this.isSubmitting,
      error: error,
      savedVehicle: savedVehicle,
    );
  }
}

/// Vehicle form provider
final vehicleFormProvider = StateNotifierProvider.autoDispose<VehicleFormNotifier, VehicleFormState>((ref) {
  return VehicleFormNotifier(ref.watch(vehiclesRepositoryProvider));
});

/// Vehicle form notifier
class VehicleFormNotifier extends StateNotifier<VehicleFormState> {
  final VehiclesRepository _repository;

  VehicleFormNotifier(this._repository) : super(const VehicleFormState());

  /// Create new vehicle
  Future<bool> createVehicle(Map<String, dynamic> data) async {
    state = state.copyWith(isSubmitting: true, error: null);

    final result = await _repository.createVehicle(data);

    return result.fold(
      (failure) {
        state = state.copyWith(
          isSubmitting: false,
          error: failure.message,
        );
        return false;
      },
      (vehicle) {
        state = state.copyWith(
          isSubmitting: false,
          savedVehicle: vehicle,
        );
        return true;
      },
    );
  }

  /// Update existing vehicle
  Future<bool> updateVehicle(int id, Map<String, dynamic> data) async {
    state = state.copyWith(isSubmitting: true, error: null);

    final result = await _repository.updateVehicle(id, data);

    return result.fold(
      (failure) {
        state = state.copyWith(
          isSubmitting: false,
          error: failure.message,
        );
        return false;
      },
      (vehicle) {
        state = state.copyWith(
          isSubmitting: false,
          savedVehicle: vehicle,
        );
        return true;
      },
    );
  }

  /// Delete vehicle
  Future<bool> deleteVehicle(int id) async {
    state = state.copyWith(isSubmitting: true, error: null);

    final result = await _repository.deleteVehicle(id);

    return result.fold(
      (failure) {
        state = state.copyWith(
          isSubmitting: false,
          error: failure.message,
        );
        return false;
      },
      (success) {
        state = state.copyWith(isSubmitting: false);
        return true;
      },
    );
  }

  /// Clear error
  void clearError() {
    state = state.copyWith(error: null);
  }
}
