import 'package:fleetly_mobile/features/fuel/presentation/providers/fuel_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

/// Fuel detail screen
class FuelDetailScreen extends ConsumerWidget {
  final int fuelId;
  final DateFormat _dateFormat = DateFormat('dd.MM.yyyy');

  FuelDetailScreen({
    super.key,
    required this.fuelId,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final fuelAsync = ref.watch(fuelDetailProvider(fuelId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalii alimentare'),
        actions: [
          fuelAsync.whenOrNull(
            data: (fuel) => PopupMenuButton<String>(
              itemBuilder: (context) => [
                const PopupMenuItem(
                  value: 'edit',
                  child: Row(
                    children: [
                      Icon(Icons.edit),
                      SizedBox(width: 8),
                      Text('Editează'),
                    ],
                  ),
                ),
                const PopupMenuItem(
                  value: 'delete',
                  child: Row(
                    children: [
                      Icon(Icons.delete, color: Colors.red),
                      SizedBox(width: 8),
                      Text('Șterge', style: TextStyle(color: Colors.red)),
                    ],
                  ),
                ),
              ],
              onSelected: (value) {
                if (value == 'edit') {
                  context.push('/fuel/$fuelId/edit');
                } else if (value == 'delete') {
                  _showDeleteDialog(context, ref);
                }
              },
            ),
          ) ?? const SizedBox(),
        ],
      ),
      body: fuelAsync.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline, size: 64, color: Colors.grey.shade400),
              const SizedBox(height: 16),
              Text('Eroare: $error'),
              const SizedBox(height: 8),
              ElevatedButton(
                onPressed: () => ref.refresh(fuelDetailProvider(fuelId)),
                child: const Text('Reîncearcă'),
              ),
            ],
          ),
        ),
        data: (fuel) => SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Main info card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      // Fuel type badge
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: _getFuelColor(fuel.fuelType).withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.local_gas_station,
                                  color: _getFuelColor(fuel.fuelType),
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  fuel.fuelTypeLabel,
                                  style: TextStyle(
                                    color: _getFuelColor(fuel.fuelType),
                                    fontWeight: FontWeight.w600,
                                    fontSize: 16,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                      // Quantity
                      Text(
                        fuel.formattedQuantity,
                        style: const TextStyle(
                          fontSize: 36,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        fuel.formattedPricePerUnit,
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 16,
                        ),
                      ),
                      const SizedBox(height: 16),
                      // Total cost
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 24,
                          vertical: 12,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFF2563EB).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          fuel.formattedTotalCost,
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF2563EB),
                          ),
                        ),
                      ),
                      if (fuel.fullTank) ...[
                        const SizedBox(height: 16),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 6,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.green.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.check_circle, color: Colors.green, size: 18),
                              SizedBox(width: 6),
                              Text(
                                'Rezervor plin',
                                style: TextStyle(
                                  color: Colors.green,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Vehicle & Driver card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.directions_car, color: Colors.grey.shade500),
                          const SizedBox(width: 8),
                          const Text(
                            'Vehicul & Șofer',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      if (fuel.vehiclePlate != null)
                        _DetailRow(
                          icon: Icons.directions_car,
                          label: 'Vehicul',
                          value: fuel.vehiclePlate!,
                        ),
                      if (fuel.driverName != null)
                        _DetailRow(
                          icon: Icons.person,
                          label: 'Șofer',
                          value: fuel.driverName!,
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Details card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.info_outline, color: Colors.grey.shade500),
                          const SizedBox(width: 8),
                          const Text(
                            'Detalii',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _DetailRow(
                        icon: Icons.calendar_today,
                        label: 'Data',
                        value: _dateFormat.format(fuel.date),
                      ),
                      if (fuel.mileage != null)
                        _DetailRow(
                          icon: Icons.speed,
                          label: 'Kilometraj',
                          value: '${fuel.mileage} km',
                        ),
                      if (fuel.station != null)
                        _DetailRow(
                          icon: Icons.place,
                          label: 'Stație',
                          value: fuel.station!,
                        ),
                      if (fuel.receiptNumber != null)
                        _DetailRow(
                          icon: Icons.receipt,
                          label: 'Nr. bon',
                          value: fuel.receiptNumber!,
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Notes card
              if (fuel.notes != null && fuel.notes!.isNotEmpty) ...[
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.notes, color: Colors.grey.shade500),
                            const SizedBox(width: 8),
                            const Text(
                              'Note',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          fuel.notes!,
                          style: const TextStyle(fontSize: 15),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Color _getFuelColor(String fuelType) {
    switch (fuelType.toLowerCase()) {
      case 'diesel':
        return Colors.amber.shade700;
      case 'gasoline':
      case 'petrol':
        return Colors.red;
      case 'lpg':
        return Colors.blue;
      case 'cng':
        return Colors.teal;
      case 'electric':
        return Colors.green;
      default:
        return Colors.grey;
    }
  }

  void _showDeleteDialog(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Șterge alimentare'),
        content: const Text('Sigur doriți să ștergeți această alimentare?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            onPressed: () async {
              Navigator.pop(context);
              final success = await ref
                  .read(fuelListProvider.notifier)
                  .deleteFuelRecord(fuelId);
              if (context.mounted) {
                if (success) {
                  context.pop();
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Alimentare ștearsă'),
                      backgroundColor: Colors.green,
                    ),
                  );
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Eroare la ștergere'),
                      backgroundColor: Colors.red,
                    ),
                  );
                }
              }
            },
            child: const Text('Șterge'),
          ),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Icon(icon, size: 20, color: Colors.grey.shade500),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              label,
              style: TextStyle(color: Colors.grey.shade600),
            ),
          ),
          Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}
