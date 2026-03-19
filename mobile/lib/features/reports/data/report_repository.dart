import 'package:dartz/dartz.dart';
import '../../../../core/network/api_client.dart';
import 'models/report_model.dart';

class ReportRepository {
  final ApiClient _apiClient;

  ReportRepository(this._apiClient);

  /// Get fleet overview data (pie chart)
  Future<Either<String, FleetOverviewData>> getFleetOverview() async {
    try {
      final response = await _apiClient.get('/reports/fleet-overview-data');
      return Right(FleetOverviewData.fromJson(response.data));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Get cost data (monthly stacked bar chart)
  Future<Either<String, CostData>> getCostData() async {
    try {
      final response = await _apiClient.get('/reports/cost-data');
      return Right(CostData.fromJson(response.data));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Get maintenance stats data (planned vs completed)
  Future<Either<String, MaintenanceStatsData>> getMaintenanceData() async {
    try {
      final response = await _apiClient.get('/reports/maintenance-data');
      return Right(MaintenanceStatsData.fromJson(response.data));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Get fuel consumption data
  Future<Either<String, FuelConsumptionData>> getFuelConsumptionData() async {
    try {
      final response = await _apiClient.get('/reports/fuel-consumption-data');
      return Right(FuelConsumptionData.fromJson(response.data));
    } catch (e) {
      return Left(e.toString());
    }
  }

  /// Load all reports data
  Future<Either<String, ReportsData>> getAllReports() async {
    try {
      // Load all data in parallel
      final results = await Future.wait([
        getFleetOverview(),
        getCostData(),
        getMaintenanceData(),
        getFuelConsumptionData(),
      ]);

      FleetOverviewData? fleetOverview;
      CostData? costData;
      MaintenanceStatsData? maintenanceData;
      FuelConsumptionData? fuelData;

      results[0].fold((l) => null, (r) => fleetOverview = r as FleetOverviewData);
      results[1].fold((l) => null, (r) => costData = r as CostData);
      results[2].fold((l) => null, (r) => maintenanceData = r as MaintenanceStatsData);
      results[3].fold((l) => null, (r) => fuelData = r as FuelConsumptionData);

      return Right(ReportsData(
        fleetOverview: fleetOverview,
        costData: costData,
        maintenanceData: maintenanceData,
        fuelData: fuelData,
      ));
    } catch (e) {
      return Left(e.toString());
    }
  }
}
