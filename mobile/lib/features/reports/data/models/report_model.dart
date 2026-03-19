import 'package:equatable/equatable.dart';

/// Fleet Overview Data for pie chart
class FleetOverviewData extends Equatable {
  final List<String> labels;
  final List<double> values;

  const FleetOverviewData({
    required this.labels,
    required this.values,
  });

  factory FleetOverviewData.fromJson(Map<String, dynamic> json) {
    final labelsList = json['labels'] as List? ?? [];
    final valuesList = json['values'] as List? ?? [];
    return FleetOverviewData(
      labels: labelsList.map((e) => e.toString()).toList(),
      values: valuesList.map((e) => (e as num).toDouble()).toList(),
    );
  }

  double get total => values.fold(0.0, (sum, val) => sum + val);

  @override
  List<Object?> get props => [labels, values];
}

/// Cost Data for monthly bar chart
class CostData extends Equatable {
  final List<String> labels;
  final List<double> fuel;
  final List<double> maintenance;
  final List<double> other;

  const CostData({
    required this.labels,
    required this.fuel,
    required this.maintenance,
    required this.other,
  });

  factory CostData.fromJson(Map<String, dynamic> json) {
    final labelsList = json['labels'] as List? ?? [];
    final fuelList = json['fuel'] as List? ?? [];
    final maintList = json['maintenance'] as List? ?? [];
    final otherList = json['other'] as List? ?? [];
    return CostData(
      labels: labelsList.map((e) => e.toString()).toList(),
      fuel: fuelList.map((e) => (e as num).toDouble()).toList(),
      maintenance: maintList.map((e) => (e as num).toDouble()).toList(),
      other: otherList.map((e) => (e as num).toDouble()).toList(),
    );
  }

  double get totalFuel => fuel.fold(0.0, (sum, val) => sum + val);
  double get totalMaintenance => maintenance.fold(0.0, (sum, val) => sum + val);
  double get totalOther => other.fold(0.0, (sum, val) => sum + val);
  double get grandTotal => totalFuel + totalMaintenance + totalOther;

  @override
  List<Object?> get props => [labels, fuel, maintenance, other];
}

/// Maintenance Data for planned vs completed chart
class MaintenanceStatsData extends Equatable {
  final List<String> labels;
  final List<int> planned;
  final List<int> completed;

  const MaintenanceStatsData({
    required this.labels,
    required this.planned,
    required this.completed,
  });

  factory MaintenanceStatsData.fromJson(Map<String, dynamic> json) {
    final labelsList = json['labels'] as List? ?? [];
    final plannedList = json['planned'] as List? ?? [];
    final completedList = json['completed'] as List? ?? [];
    return MaintenanceStatsData(
      labels: labelsList.map((e) => e.toString()).toList(),
      planned: plannedList.map((e) => (e as num).toInt()).toList(),
      completed: completedList.map((e) => (e as num).toInt()).toList(),
    );
  }

  int get totalPlanned => planned.fold(0, (sum, val) => sum + val);
  int get totalCompleted => completed.fold(0, (sum, val) => sum + val);

  @override
  List<Object?> get props => [labels, planned, completed];
}

/// Vehicle fuel consumption series
class VehicleConsumptionSeries extends Equatable {
  final String name;
  final List<double> consumption;

  const VehicleConsumptionSeries({
    required this.name,
    required this.consumption,
  });

  factory VehicleConsumptionSeries.fromJson(Map<String, dynamic> json) {
    final consumptionList = json['consumption'] as List? ?? [];
    return VehicleConsumptionSeries(
      name: json['name'] ?? '',
      consumption: consumptionList.map((e) => (e as num).toDouble()).toList(),
    );
  }

  double get averageConsumption {
    if (consumption.isEmpty) return 0;
    final nonZero = consumption.where((c) => c > 0).toList();
    if (nonZero.isEmpty) return 0;
    return nonZero.reduce((a, b) => a + b) / nonZero.length;
  }

  @override
  List<Object?> get props => [name, consumption];
}

/// Fuel consumption data
class FuelConsumptionData extends Equatable {
  final List<String> labels;
  final List<VehicleConsumptionSeries> vehicles;

  const FuelConsumptionData({
    required this.labels,
    required this.vehicles,
  });

  factory FuelConsumptionData.fromJson(Map<String, dynamic> json) {
    final labelsList = json['labels'] as List? ?? [];
    final vehiclesList = json['vehicles'] as List? ?? [];
    return FuelConsumptionData(
      labels: labelsList.map((e) => e.toString()).toList(),
      vehicles: vehiclesList
          .map((e) => VehicleConsumptionSeries.fromJson(e))
          .toList(),
    );
  }

  @override
  List<Object?> get props => [labels, vehicles];
}

/// Report type enum
enum ReportType {
  fleetOverview('fleet-overview', 'Prezentare Flotă'),
  costs('costs', 'Costuri'),
  maintenance('maintenance', 'Mentenanță'),
  fuel('fuel', 'Combustibil');

  final String value;
  final String label;
  const ReportType(this.value, this.label);
}

/// All reports combined
class ReportsData extends Equatable {
  final FleetOverviewData? fleetOverview;
  final CostData? costData;
  final MaintenanceStatsData? maintenanceData;
  final FuelConsumptionData? fuelData;

  const ReportsData({
    this.fleetOverview,
    this.costData,
    this.maintenanceData,
    this.fuelData,
  });

  @override
  List<Object?> get props => [fleetOverview, costData, maintenanceData, fuelData];
}
