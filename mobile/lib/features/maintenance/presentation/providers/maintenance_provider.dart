import 'package:fleetly_mobile/features/maintenance/data/maintenance_repository.dart';
import 'package:fleetly_mobile/features/maintenance/data/models/maintenance_model.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Maintenance list state
class MaintenanceListState {
  final List<Maintenance> records;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int totalPages;
  final bool hasMore;
  final MaintenanceType filterType;
  final int? filterVehicleId;
  final String searchQuery;

  const MaintenanceListState({
    this.records = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.totalPages = 1,
    this.hasMore = false,
    this.filterType = MaintenanceType.all,
    this.filterVehicleId,
    this.searchQuery = '',
  });

  MaintenanceListState copyWith({
    List<Maintenance>? records,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? totalPages,
    bool? hasMore,
    MaintenanceType? filterType,
    int? filterVehicleId,
    String? searchQuery,
  }) {
    return MaintenanceListState(
      records: records ?? this.records,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      totalPages: totalPages ?? this.totalPages,
      hasMore: hasMore ?? this.hasMore,
      filterType: filterType ?? this.filterType,
      filterVehicleId: filterVehicleId ?? this.filterVehicleId,
      searchQuery: searchQuery ?? this.searchQuery,
    );
  }
}

/// Maintenance list notifier
class MaintenanceListNotifier extends StateNotifier<MaintenanceListState> {
  final MaintenanceRepository _repository;

  MaintenanceListNotifier(this._repository) : super(const MaintenanceListState());

  /// Load maintenance records
  Future<void> loadMaintenance({bool refresh = false}) async {
    if (state.isLoading && !refresh) return;

    state = state.copyWith(
      isLoading: true,
      error: null,
      currentPage: refresh ? 1 : state.currentPage,
    );

    try {
      final response = await _repository.getMaintenanceRecords(
        page: 1,
        type: state.filterType.value.isEmpty ? null : state.filterType.value,
        vehicleId: state.filterVehicleId,
        search: state.searchQuery.isEmpty ? null : state.searchQuery,
      );

      state = state.copyWith(
        records: response.records,
        isLoading: false,
        currentPage: response.page,
        totalPages: response.totalPages,
        hasMore: response.hasMore,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
      );
    }
  }

  /// Load more records (pagination)
  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);

    try {
      final response = await _repository.getMaintenanceRecords(
        page: state.currentPage + 1,
        type: state.filterType.value.isEmpty ? null : state.filterType.value,
        vehicleId: state.filterVehicleId,
        search: state.searchQuery.isEmpty ? null : state.searchQuery,
      );

      state = state.copyWith(
        records: [...state.records, ...response.records],
        isLoadingMore: false,
        currentPage: response.page,
        totalPages: response.totalPages,
        hasMore: response.hasMore,
      );
    } catch (e) {
      state = state.copyWith(
        isLoadingMore: false,
        error: e.toString(),
      );
    }
  }

  /// Set filter by type
  void setFilterType(MaintenanceType type) {
    if (state.filterType == type) return;
    state = state.copyWith(filterType: type);
    loadMaintenance(refresh: true);
  }

  /// Set filter by vehicle
  void setFilterVehicle(int? vehicleId) {
    if (state.filterVehicleId == vehicleId) return;
    state = state.copyWith(filterVehicleId: vehicleId);
    loadMaintenance(refresh: true);
  }

  /// Search maintenance records
  void search(String query) {
    state = state.copyWith(searchQuery: query);
    loadMaintenance(refresh: true);
  }

  /// Clear all filters
  void clearFilters() {
    state = state.copyWith(
      filterType: MaintenanceType.all,
      filterVehicleId: null,
      searchQuery: '',
    );
    loadMaintenance(refresh: true);
  }

  /// Delete maintenance record
  Future<bool> deleteMaintenance(int id) async {
    try {
      final success = await _repository.deleteMaintenance(id);
      if (success) {
        state = state.copyWith(
          records: state.records.where((m) => m.id != id).toList(),
        );
      }
      return success;
    } catch (e) {
      return false;
    }
  }
}

/// Maintenance list provider
final maintenanceListProvider =
    StateNotifierProvider<MaintenanceListNotifier, MaintenanceListState>((ref) {
  final repository = ref.read(maintenanceRepositoryProvider);
  return MaintenanceListNotifier(repository);
});

/// Single maintenance provider
final maintenanceDetailProvider =
    FutureProvider.family<Maintenance, int>((ref, id) async {
  final repository = ref.read(maintenanceRepositoryProvider);
  return repository.getMaintenanceById(id);
});

/// Upcoming maintenance provider
final upcomingMaintenanceProvider =
    FutureProvider<List<Maintenance>>((ref) async {
  final repository = ref.read(maintenanceRepositoryProvider);
  return repository.getUpcomingMaintenance(days: 30);
});

/// Maintenance stats provider
final maintenanceStatsProvider =
    FutureProvider.family<Map<String, dynamic>, int?>((ref, vehicleId) async {
  final repository = ref.read(maintenanceRepositoryProvider);
  return repository.getMaintenanceStats(vehicleId: vehicleId);
});
