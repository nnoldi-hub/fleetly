import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/network/api_client.dart';
import '../../data/report_repository.dart';
import '../../data/models/report_model.dart';

/// Repository provider
final reportRepositoryProvider = Provider<ReportRepository>((ref) {
  return ReportRepository(ref.watch(apiClientProvider));
});

/// Reports state
class ReportsState {
  final ReportsData? data;
  final bool isLoading;
  final String? error;
  final ReportType selectedReport;

  const ReportsState({
    this.data,
    this.isLoading = false,
    this.error,
    this.selectedReport = ReportType.fleetOverview,
  });

  ReportsState copyWith({
    ReportsData? data,
    bool? isLoading,
    String? error,
    ReportType? selectedReport,
  }) {
    return ReportsState(
      data: data ?? this.data,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      selectedReport: selectedReport ?? this.selectedReport,
    );
  }
}

/// Reports notifier
class ReportsNotifier extends StateNotifier<ReportsState> {
  final ReportRepository _repository;

  ReportsNotifier(this._repository) : super(const ReportsState());

  /// Load all reports data
  Future<void> load() async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _repository.getAllReports();

    result.fold(
      (error) => state = state.copyWith(isLoading: false, error: error),
      (data) => state = state.copyWith(data: data, isLoading: false),
    );
  }

  /// Change selected report type
  void selectReport(ReportType type) {
    state = state.copyWith(selectedReport: type);
  }
}

/// Reports provider
final reportsProvider =
    StateNotifierProvider<ReportsNotifier, ReportsState>((ref) {
  return ReportsNotifier(ref.watch(reportRepositoryProvider));
});

/// Individual report providers for granular loading

/// Fleet overview provider
final fleetOverviewProvider =
    FutureProvider.autoDispose<FleetOverviewData?>((ref) async {
  final repository = ref.watch(reportRepositoryProvider);
  final result = await repository.getFleetOverview();
  return result.fold((l) => null, (r) => r);
});

/// Cost data provider
final costDataProvider = FutureProvider.autoDispose<CostData?>((ref) async {
  final repository = ref.watch(reportRepositoryProvider);
  final result = await repository.getCostData();
  return result.fold((l) => null, (r) => r);
});

/// Maintenance stats provider
final maintenanceStatsProvider =
    FutureProvider.autoDispose<MaintenanceStatsData?>((ref) async {
  final repository = ref.watch(reportRepositoryProvider);
  final result = await repository.getMaintenanceData();
  return result.fold((l) => null, (r) => r);
});

/// Fuel consumption provider
final fuelConsumptionProvider =
    FutureProvider.autoDispose<FuelConsumptionData?>((ref) async {
  final repository = ref.watch(reportRepositoryProvider);
  final result = await repository.getFuelConsumptionData();
  return result.fold((l) => null, (r) => r);
});
