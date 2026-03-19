import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/insurance/data/insurance_repository.dart';
import 'package:fleetly_mobile/features/insurance/data/models/insurance_model.dart';

/// Insurance repository provider
final insuranceRepositoryProvider = Provider<InsuranceRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return InsuranceRepository(apiClient);
});

/// Insurance list state
class InsuranceListState {
  final List<Insurance> insurances;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int lastPage;
  final int total;
  final String? filterType;
  final String? filterStatus;
  final int? filterVehicleId;

  InsuranceListState({
    this.insurances = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.lastPage = 1,
    this.total = 0,
    this.filterType,
    this.filterStatus,
    this.filterVehicleId,
  });

  bool get hasMore => currentPage < lastPage;

  InsuranceListState copyWith({
    List<Insurance>? insurances,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? lastPage,
    int? total,
    String? filterType,
    String? filterStatus,
    int? filterVehicleId,
    bool clearFilters = false,
  }) {
    return InsuranceListState(
      insurances: insurances ?? this.insurances,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      total: total ?? this.total,
      filterType: clearFilters ? null : (filterType ?? this.filterType),
      filterStatus: clearFilters ? null : (filterStatus ?? this.filterStatus),
      filterVehicleId: clearFilters ? null : (filterVehicleId ?? this.filterVehicleId),
    );
  }
}

/// Insurance list notifier
class InsuranceListNotifier extends StateNotifier<InsuranceListState> {
  final InsuranceRepository _repository;

  InsuranceListNotifier(this._repository) : super(InsuranceListState()) {
    loadInsurances();
  }

  Future<void> loadInsurances({bool refresh = false}) async {
    if (state.isLoading) return;

    state = state.copyWith(
      isLoading: true,
      error: null,
      currentPage: refresh ? 1 : state.currentPage,
    );

    final result = await _repository.getInsurances(
      page: refresh ? 1 : state.currentPage,
      type: state.filterType,
      status: state.filterStatus,
      vehicleId: state.filterVehicleId,
    );

    result.fold(
      (error) => state = state.copyWith(isLoading: false, error: error),
      (response) => state = state.copyWith(
        isLoading: false,
        insurances: response.data,
        currentPage: response.currentPage,
        lastPage: response.lastPage,
        total: response.total,
      ),
    );
  }

  Future<void> loadMore() async {
    if (!state.hasMore || state.isLoadingMore || state.isLoading) return;

    state = state.copyWith(isLoadingMore: true);

    final result = await _repository.getInsurances(
      page: state.currentPage + 1,
      type: state.filterType,
      status: state.filterStatus,
      vehicleId: state.filterVehicleId,
    );

    result.fold(
      (error) => state = state.copyWith(isLoadingMore: false, error: error),
      (response) => state = state.copyWith(
        isLoadingMore: false,
        insurances: [...state.insurances, ...response.data],
        currentPage: response.currentPage,
        lastPage: response.lastPage,
        total: response.total,
      ),
    );
  }

  void setFilter({String? type, String? status, int? vehicleId}) {
    state = state.copyWith(
      filterType: type,
      filterStatus: status,
      filterVehicleId: vehicleId,
    );
    loadInsurances(refresh: true);
  }

  void clearFilters() {
    state = state.copyWith(clearFilters: true);
    loadInsurances(refresh: true);
  }

  Future<void> refresh() => loadInsurances(refresh: true);

  Future<bool> deleteInsurance(int id) async {
    final result = await _repository.deleteInsurance(id);
    return result.fold(
      (error) => false,
      (_) {
        state = state.copyWith(
          insurances: state.insurances.where((i) => i.id != id).toList(),
          total: state.total - 1,
        );
        return true;
      },
    );
  }
}

/// Insurance list provider
final insuranceListProvider =
    StateNotifierProvider<InsuranceListNotifier, InsuranceListState>((ref) {
  final repository = ref.watch(insuranceRepositoryProvider);
  return InsuranceListNotifier(repository);
});

/// Insurance detail state
class InsuranceDetailState {
  final Insurance? insurance;
  final bool isLoading;
  final String? error;

  InsuranceDetailState({
    this.insurance,
    this.isLoading = false,
    this.error,
  });

  InsuranceDetailState copyWith({
    Insurance? insurance,
    bool? isLoading,
    String? error,
  }) {
    return InsuranceDetailState(
      insurance: insurance ?? this.insurance,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

/// Insurance detail notifier
class InsuranceDetailNotifier extends StateNotifier<InsuranceDetailState> {
  final InsuranceRepository _repository;
  final int id;

  InsuranceDetailNotifier(this._repository, this.id)
      : super(InsuranceDetailState()) {
    loadInsurance();
  }

  Future<void> loadInsurance() async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _repository.getInsurance(id);
    result.fold(
      (error) => state = state.copyWith(isLoading: false, error: error),
      (insurance) =>
          state = state.copyWith(isLoading: false, insurance: insurance),
    );
  }

  Future<void> refresh() => loadInsurance();
}

/// Insurance detail provider family
final insuranceDetailProvider = StateNotifierProvider.family<
    InsuranceDetailNotifier, InsuranceDetailState, int>((ref, id) {
  final repository = ref.watch(insuranceRepositoryProvider);
  return InsuranceDetailNotifier(repository, id);
});

/// Expiring insurances state
class ExpiringInsurancesState {
  final List<ExpiringInsurance> insurances;
  final bool isLoading;
  final String? error;

  ExpiringInsurancesState({
    this.insurances = const [],
    this.isLoading = false,
    this.error,
  });
}

/// Expiring insurances provider
final expiringInsurancesProvider =
    FutureProvider.family<List<ExpiringInsurance>, int>((ref, days) async {
  final repository = ref.watch(insuranceRepositoryProvider);
  final result = await repository.getExpiringInsurances(days: days);
  return result.fold(
    (error) => throw Exception(error),
    (data) => data,
  );
});
