import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/models/report_model.dart';
import '../providers/report_provider.dart';

class ReportsScreen extends ConsumerStatefulWidget {
  const ReportsScreen({super.key});

  @override
  ConsumerState<ReportsScreen> createState() => _ReportsScreenState();
}

class _ReportsScreenState extends ConsumerState<ReportsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(reportsProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(reportsProvider);
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Rapoarte'),
        actions: [
          IconButton(
            onPressed: () => ref.read(reportsProvider.notifier).load(),
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(reportsProvider.notifier).load(),
        child: _buildBody(state, colorScheme),
      ),
    );
  }

  Widget _buildBody(ReportsState state, ColorScheme colorScheme) {
    if (state.isLoading && state.data == null) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.error != null && state.data == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 48, color: colorScheme.error),
            const SizedBox(height: 16),
            Text(state.error!),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => ref.read(reportsProvider.notifier).load(),
              child: const Text('Reîncercați'),
            ),
          ],
        ),
      );
    }

    final data = state.data;
    if (data == null) {
      return const Center(
        child: Text('Nu există date disponibile'),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Report type selector
        _buildReportSelector(state.selectedReport),
        const SizedBox(height: 16),

        // Selected report content
        AnimatedSwitcher(
          duration: const Duration(milliseconds: 300),
          child: _buildReportContent(state.selectedReport, data),
        ),
      ],
    );
  }

  Widget _buildReportSelector(ReportType selected) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: ReportType.values.map((type) {
          final isSelected = type == selected;
          return Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(
              label: Text(type.label),
              selected: isSelected,
              onSelected: (_) {
                ref.read(reportsProvider.notifier).selectReport(type);
              },
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildReportContent(ReportType type, ReportsData data) {
    switch (type) {
      case ReportType.fleetOverview:
        return _FleetOverviewCard(
          key: const ValueKey('fleet'),
          data: data.fleetOverview,
        );
      case ReportType.costs:
        return _CostAnalysisCard(
          key: const ValueKey('costs'),
          data: data.costData,
        );
      case ReportType.maintenance:
        return _MaintenanceStatsCard(
          key: const ValueKey('maintenance'),
          data: data.maintenanceData,
        );
      case ReportType.fuel:
        return _FuelConsumptionCard(
          key: const ValueKey('fuel'),
          data: data.fuelData,
        );
    }
  }
}

/// Fleet Overview Card - Pie chart style
class _FleetOverviewCard extends StatelessWidget {
  final FleetOverviewData? data;

  const _FleetOverviewCard({super.key, this.data});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    if (data == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
            child: Text(
              'Nu există date pentru prezentarea flotei',
              style: TextStyle(color: colorScheme.outline),
            ),
          ),
        ),
      );
    }

    final colors = [
      Colors.green,
      Colors.orange,
      Colors.blue,
      Colors.purple,
    ];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.pie_chart, color: colorScheme.primary),
                const SizedBox(width: 8),
                Text(
                  'Distribuție Costuri Luna Curentă',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Simple pie chart representation
            Center(
              child: data!.total > 0
                  ? _SimplePieChart(labels: data!.labels, values: data!.values, colors: colors)
                  : const Text('Nu există costuri în această lună'),
            ),
            const SizedBox(height: 24),

            // Legend
            Wrap(
              spacing: 16,
              runSpacing: 8,
              children: List.generate(data!.labels.length, (i) {
                return _LegendItem(
                  color: colors[i % colors.length],
                  label: data!.labels[i],
                  value: data!.values[i],
                );
              }),
            ),

            const SizedBox(height: 16),
            // Total
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: colorScheme.primaryContainer.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Total',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    '${data!.total.toStringAsFixed(2)} RON',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: colorScheme.primary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Simple visual pie chart
class _SimplePieChart extends StatelessWidget {
  final List<String> labels;
  final List<double> values;
  final List<Color> colors;

  const _SimplePieChart({
    required this.labels,
    required this.values,
    required this.colors,
  });

  @override
  Widget build(BuildContext context) {
    final total = values.fold(0.0, (sum, val) => sum + val);
    if (total == 0) return const SizedBox.shrink();

    return SizedBox(
      height: 200,
      width: 200,
      child: CustomPaint(
        painter: _PieChartPainter(values: values, colors: colors, total: total),
      ),
    );
  }
}

class _PieChartPainter extends CustomPainter {
  final List<double> values;
  final List<Color> colors;
  final double total;

  _PieChartPainter({
    required this.values,
    required this.colors,
    required this.total,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;
    double startAngle = -90 * 3.14159 / 180; // Start from top

    for (int i = 0; i < values.length; i++) {
      final sweepAngle = (values[i] / total) * 2 * 3.14159;
      final paint = Paint()
        ..color = colors[i % colors.length]
        ..style = PaintingStyle.fill;

      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius),
        startAngle,
        sweepAngle,
        true,
        paint,
      );

      startAngle += sweepAngle;
    }

    // Draw center hole for donut effect
    final holePaint = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.fill;
    canvas.drawCircle(center, radius * 0.5, holePaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}

class _LegendItem extends StatelessWidget {
  final Color color;
  final String label;
  final double value;

  const _LegendItem({
    required this.color,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(3),
          ),
        ),
        const SizedBox(width: 6),
        Text(
          '$label: ${value.toStringAsFixed(0)} RON',
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    );
  }
}

/// Cost Analysis Card
class _CostAnalysisCard extends StatelessWidget {
  final CostData? data;

  const _CostAnalysisCard({super.key, this.data});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    if (data == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
            child: Text(
              'Nu există date pentru analiza costurilor',
              style: TextStyle(color: colorScheme.outline),
            ),
          ),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.bar_chart, color: colorScheme.primary),
                const SizedBox(width: 8),
                Text(
                  'Costuri - Ultimele 6 Luni',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Bar chart
            _SimpleBarChart(
              labels: data!.labels,
              series: [
                _BarSeries('Combustibil', Colors.green, data!.fuel),
                _BarSeries('Mentenanță', Colors.orange, data!.maintenance),
                _BarSeries('Altele', Colors.blue, data!.other),
              ],
            ),
            const SizedBox(height: 16),

            // Summary cards
            Row(
              children: [
                Expanded(
                  child: _SummaryCard(
                    label: 'Combustibil',
                    value: data!.totalFuel,
                    color: Colors.green,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _SummaryCard(
                    label: 'Mentenanță',
                    value: data!.totalMaintenance,
                    color: Colors.orange,
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: _SummaryCard(
                    label: 'Altele',
                    value: data!.totalOther,
                    color: Colors.blue,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: colorScheme.primaryContainer.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Total General'),
                  Text(
                    '${data!.grandTotal.toStringAsFixed(2)} RON',
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                      color: colorScheme.primary,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BarSeries {
  final String name;
  final Color color;
  final List<double> values;

  _BarSeries(this.name, this.color, this.values);
}

class _SimpleBarChart extends StatelessWidget {
  final List<String> labels;
  final List<_BarSeries> series;

  const _SimpleBarChart({
    required this.labels,
    required this.series,
  });

  @override
  Widget build(BuildContext context) {
    if (labels.isEmpty) return const SizedBox.shrink();

    // Find max value for scaling
    double maxValue = 0;
    for (var s in series) {
      for (var v in s.values) {
        if (v > maxValue) maxValue = v;
      }
    }
    if (maxValue == 0) maxValue = 1;

    return SizedBox(
      height: 180,
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: List.generate(labels.length, (i) {
          return Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                // Stacked bars
                ...series.map((s) {
                  final value = i < s.values.length ? s.values[i] : 0.0;
                  final height = (value / maxValue) * 120;
                  return Container(
                    height: height.clamp(0, 120),
                    margin: const EdgeInsets.symmetric(horizontal: 2),
                    decoration: BoxDecoration(
                      color: s.color,
                      borderRadius: const BorderRadius.vertical(
                        top: Radius.circular(3),
                      ),
                    ),
                  );
                }),
                const SizedBox(height: 8),
                // Label
                Text(
                  _formatMonth(labels[i]),
                  style: Theme.of(context).textTheme.labelSmall,
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          );
        }),
      ),
    );
  }

  String _formatMonth(String ym) {
    // Convert 2024-01 to Ian, Feb, etc.
    final parts = ym.split('-');
    if (parts.length != 2) return ym;
    final month = int.tryParse(parts[1]) ?? 1;
    const months = ['Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun',
                   'Iul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[month - 1];
  }
}

class _SummaryCard extends StatelessWidget {
  final String label;
  final double value;
  final Color color;

  const _SummaryCard({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(8),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Column(
        children: [
          Text(
            label,
            style: Theme.of(context).textTheme.labelSmall,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 4),
          Text(
            '${value.toStringAsFixed(0)}',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

/// Maintenance Stats Card
class _MaintenanceStatsCard extends StatelessWidget {
  final MaintenanceStatsData? data;

  const _MaintenanceStatsCard({super.key, this.data});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    if (data == null) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
            child: Text(
              'Nu există date pentru statisticile de mentenanță',
              style: TextStyle(color: colorScheme.outline),
            ),
          ),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.build, color: colorScheme.primary),
                const SizedBox(width: 8),
                Text(
                  'Mentenanță - Planificat vs Completat',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Stats summary
            Row(
              children: [
                Expanded(
                  child: _StatCard(
                    icon: Icons.schedule,
                    label: 'Planificate',
                    value: data!.totalPlanned,
                    color: Colors.blue,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _StatCard(
                    icon: Icons.check_circle,
                    label: 'Completate',
                    value: data!.totalCompleted,
                    color: Colors.green,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Monthly breakdown
            if (data!.labels.isNotEmpty) ...[
              Text(
                'Detalii Lunare',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              ...List.generate(data!.labels.length, (i) {
                return _MonthRow(
                  month: _formatMonth(data!.labels[i]),
                  planned: data!.planned[i],
                  completed: data!.completed[i],
                );
              }),
            ],
          ],
        ),
      ),
    );
  }

  String _formatMonth(String ym) {
    final parts = ym.split('-');
    if (parts.length != 2) return ym;
    final month = int.tryParse(parts[1]) ?? 1;
    final year = parts[0];
    const months = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie',
                   'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];
    return '${months[month - 1]} $year';
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final int value;
  final Color color;

  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 32),
          const SizedBox(height: 8),
          Text(
            value.toString(),
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
      ),
    );
  }
}

class _MonthRow extends StatelessWidget {
  final String month;
  final int planned;
  final int completed;

  const _MonthRow({
    required this.month,
    required this.planned,
    required this.completed,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(month, style: theme.textTheme.bodySmall),
          ),
          Expanded(
            child: Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: Colors.blue,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
                const SizedBox(width: 4),
                Text('$planned', style: theme.textTheme.bodySmall),
              ],
            ),
          ),
          Expanded(
            child: Row(
              children: [
                Container(
                  width: 8,
                  height: 8,
                  decoration: BoxDecoration(
                    color: Colors.green,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
                const SizedBox(width: 4),
                Text('$completed', style: theme.textTheme.bodySmall),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Fuel Consumption Card
class _FuelConsumptionCard extends StatelessWidget {
  final FuelConsumptionData? data;

  const _FuelConsumptionCard({super.key, this.data});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    if (data == null || data!.vehicles.isEmpty) {
      return Card(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Center(
            child: Text(
              'Nu există date pentru consumul de combustibil',
              style: TextStyle(color: colorScheme.outline),
            ),
          ),
        ),
      );
    }

    final colors = [Colors.blue, Colors.green, Colors.orange];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.local_gas_station, color: colorScheme.primary),
                const SizedBox(width: 8),
                Text(
                  'Consum Combustibil - Top 3 Vehicule',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Vehicle consumption cards
            ...List.generate(data!.vehicles.length, (i) {
              final vehicle = data!.vehicles[i];
              return _VehicleConsumptionRow(
                name: vehicle.name,
                consumption: vehicle.consumption,
                average: vehicle.averageConsumption,
                color: colors[i % colors.length],
                months: data!.labels,
              );
            }),
          ],
        ),
      ),
    );
  }
}

class _VehicleConsumptionRow extends StatelessWidget {
  final String name;
  final List<double> consumption;
  final double average;
  final Color color;
  final List<String> months;

  const _VehicleConsumptionRow({
    required this.name,
    required this.consumption,
    required this.average,
    required this.color,
    required this.months,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 4,
                height: 20,
                decoration: BoxDecoration(
                  color: color,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  name,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Text(
                  'Medie: ${average.toStringAsFixed(1)} L/100km',
                  style: theme.textTheme.labelSmall?.copyWith(
                    color: color,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Simple line representation
          Row(
            children: List.generate(consumption.length, (i) {
              return Expanded(
                child: Column(
                  children: [
                    Text(
                      consumption[i] > 0
                          ? '${consumption[i].toStringAsFixed(1)}'
                          : '-',
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: consumption[i] > 0 ? color : Colors.grey,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _formatMonth(months[i]),
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: Colors.grey,
                        fontSize: 10,
                      ),
                    ),
                  ],
                ),
              );
            }),
          ),
        ],
      ),
    );
  }

  String _formatMonth(String ym) {
    final parts = ym.split('-');
    if (parts.length != 2) return ym;
    final month = int.tryParse(parts[1]) ?? 1;
    const months = ['I', 'F', 'M', 'A', 'M', 'I', 'I', 'A', 'S', 'O', 'N', 'D'];
    return months[month - 1];
  }
}
