import 'package:fleetly_mobile/features/fuel/data/fuel_repository.dart';
import 'package:fleetly_mobile/features/fuel/data/models/fuel_model.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Fuel list state
class FuelListState {
  final List<FuelRecord> records;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int totalPages;
  final bool hasMore;
  final FuelType filterType;
  final int? filterVehicleId;
  final String searchQuery;
  final DateTime? startDate;
  final DateTime? endDate;

  const FuelListState({
    this.records = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.totalPages = 1,
    this.hasMore = false,
    this.filterType = FuelType.all,
    this.filterVehicleId,
    this.searchQuery = '',
    this.startDate,
    this.endDate,
  });

  FuelListState copyWith({
    List<FuelRecord>? records,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? totalPages,
    bool? hasMore,
    FuelType? filterType,
    int? filterVehicleId,
    String? searchQuery,
    DateTime? startDate,
    DateTime? endDate,
  }) {
    return FuelListState(
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
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
    );
  }
}

/// Fuel list notifier
class FuelListNotifier extends StateNotifier<FuelListState> {
  final FuelRepository _repository;

  FuelListNotifier(this._repository) : super(const FuelListState());

  /// Load fuel records
  Future<void> loadFuel({bool refresh = false}) async {
    if (state.isLoading && !refresh) return;

    state = state.copyWith(
      isLoading: true,
      error: null,
      currentPage: refresh ? 1 : state.currentPage,
    );

    try {
      final response = await _repository.getFuelRecords(
        page: 1,
        fuelType: state.filterType.value.isEmpty ? null : state.filterType.value,
        vehicleId: state.filterVehicleId,
        search: state.searchQuery.isEmpty ? null : state.searchQuery,
        startDate: state.startDate,
        endDate: state.endDate,
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
      final response = await _repository.getFuelRecords(
        page: state.currentPage + 1,
        fuelType: state.filterType.value.isEmpty ? null : state.filterType.value,
        vehicleId: state.filterVehicleId,
        search: state.searchQuery.isEmpty ? null : state.searchQuery,
        startDate: state.startDate,
        endDate: state.endDate,
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

  /// Set filter by fuel type
  void setFilterType(FuelType type) {
    if (state.filterType == type) return;
    state = state.copyWith(filterType: type);
    loadFuel(refresh: true);
  }

  /// Set filter by vehicle
  void setFilterVehicle(int? vehicleId) {
    if (state.filterVehicleId == vehicleId) return;
    state = state.copyWith(filterVehicleId: vehicleId);
    loadFuel(refresh: true);
  }

  /// Set date range filter
  void setDateRange(DateTime? start, DateTime? end) {
    state = state.copyWith(startDate: start, endDate: end);
    loadFuel(refresh: true);
  }

  /// Search fuel records
  void search(String query) {
    state = state.copyWith(searchQuery: query);
    loadFuel(refresh: true);
  }

  /// Clear all filters
  void clearFilters() {
    state = state.copyWith(
      filterType: FuelType.all,
      filterVehicleId: null,
      searchQuery: '',
      startDate: null,
      endDate: null,
    );
    loadFuel(refresh: true);
  }

  /// Delete fuel record
  Future<bool> deleteFuelRecord(int id) async {
    try {
      final success = await _repository.deleteFuelRecord(id);
      if (success) {
        state = state.copyWith(
          records: state.records.where((r) => r.id != id).toList(),
        );
      }
      return success;
    } catch (e) {
      return false;
    }
  }
}

/// Fuel list provider
final fuelListProvider =
    StateNotifierProvider<FuelListNotifier, FuelListState>((ref) {
  final repository = ref.read(fuelRepositoryProvider);
  return FuelListNotifier(repository);
});

/// Single fuel record provider
final fuelDetailProvider =
    FutureProvider.family<FuelRecord, int>((ref, id) async {
  final repository = ref.read(fuelRepositoryProvider);
  return repository.getFuelById(id);
});

/// Fuel stats provider
final fuelStatsProvider =
    FutureProvider.family<FuelStats, int?>((ref, vehicleId) async {
  final repository = ref.read(fuelRepositoryProvider);
  return repository.getFuelStats(vehicleId: vehicleId);
});

/// Fuel stats by period provider
final fuelStatsByPeriodProvider =
    FutureProvider.family<FuelStats, ({int? vehicleId, String period})>((ref, params) async {
  final repository = ref.read(fuelRepositoryProvider);
  return repository.getFuelStats(vehicleId: params.vehicleId, period: params.period);
});
