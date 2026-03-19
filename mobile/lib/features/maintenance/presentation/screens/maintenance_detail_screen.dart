import 'package:fleetly_mobile/features/maintenance/presentation/providers/maintenance_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

/// Maintenance detail screen
class MaintenanceDetailScreen extends ConsumerWidget {
  final int maintenanceId;
  final DateFormat _dateFormat = DateFormat('dd.MM.yyyy');

  MaintenanceDetailScreen({
    super.key,
    required this.maintenanceId,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final maintenanceAsync = ref.watch(maintenanceDetailProvider(maintenanceId));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalii mentenanță'),
        actions: [
          maintenanceAsync.whenOrNull(
            data: (maintenance) => PopupMenuButton<String>(
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
                  context.push('/maintenance/$maintenanceId/edit');
                } else if (value == 'delete') {
                  _showDeleteDialog(context, ref);
                }
              },
            ),
          ) ?? const SizedBox(),
        ],
      ),
      body: maintenanceAsync.when(
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
                onPressed: () => ref.refresh(maintenanceDetailProvider(maintenanceId)),
                child: const Text('Reîncearcă'),
              ),
            ],
          ),
        ),
        data: (maintenance) => SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Status & Type Card
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                maintenance.typeLabel,
                                style: const TextStyle(
                                  fontSize: 22,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                _dateFormat.format(maintenance.date),
                                style: TextStyle(
                                  color: Colors.grey.shade600,
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                          _StatusChip(status: maintenance.status ?? 'unknown'),
                        ],
                      ),
                      if (maintenance.vehiclePlate != null) ...[
                        const SizedBox(height: 16),
                        Row(
                          children: [
                            Icon(Icons.directions_car, color: Colors.grey.shade500),
                            const SizedBox(width: 8),
                            Text(
                              'Vehicul: ${maintenance.vehiclePlate}',
                              style: const TextStyle(fontSize: 16),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Description card
              if (maintenance.description != null) ...[
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.description, color: Colors.grey.shade500),
                            const SizedBox(width: 8),
                            const Text(
                              'Descriere',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          maintenance.description!,
                          style: const TextStyle(fontSize: 15),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
              ],

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
                        label: 'Data service',
                        value: _dateFormat.format(maintenance.date),
                      ),
                      if (maintenance.mileage != null)
                        _DetailRow(
                          icon: Icons.speed,
                          label: 'Kilometraj',
                          value: '${maintenance.mileage} km',
                        ),
                      if (maintenance.cost != null)
                        _DetailRow(
                          icon: Icons.payments,
                          label: 'Cost',
                          value: maintenance.formattedCost,
                          valueStyle: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                          ),
                        ),
                      if (maintenance.serviceProvider != null)
                        _DetailRow(
                          icon: Icons.store,
                          label: 'Furnizor',
                          value: maintenance.serviceProvider!,
                        ),
                      if (maintenance.invoiceNumber != null)
                        _DetailRow(
                          icon: Icons.receipt,
                          label: 'Nr. factură',
                          value: maintenance.invoiceNumber!,
                        ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Next service card
              if (maintenance.nextServiceDate != null ||
                  maintenance.nextServiceMileage != null) ...[
                Card(
                  color: maintenance.isServiceOverdue
                      ? Colors.red.shade50
                      : maintenance.isServiceDueSoon
                          ? Colors.orange.shade50
                          : null,
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(
                              maintenance.isServiceOverdue
                                  ? Icons.warning_amber
                                  : Icons.update,
                              color: maintenance.isServiceOverdue
                                  ? Colors.red
                                  : maintenance.isServiceDueSoon
                                      ? Colors.orange
                                      : Colors.grey.shade500,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              'Următorul service',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: maintenance.isServiceOverdue
                                    ? Colors.red
                                    : null,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        if (maintenance.nextServiceDate != null)
                          _DetailRow(
                            icon: Icons.event,
                            label: 'Dată',
                            value: _dateFormat.format(maintenance.nextServiceDate!),
                            valueStyle: TextStyle(
                              color: maintenance.isServiceOverdue
                                  ? Colors.red
                                  : maintenance.isServiceDueSoon
                                      ? Colors.orange
                                      : null,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        if (maintenance.nextServiceMileage != null)
                          _DetailRow(
                            icon: Icons.speed,
                            label: 'Kilometraj',
                            value: '${maintenance.nextServiceMileage} km',
                          ),
                        if (maintenance.isServiceOverdue) ...[
                          const SizedBox(height: 8),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.red.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Row(
                              children: [
                                Icon(Icons.warning, color: Colors.red, size: 20),
                                SizedBox(width: 8),
                                Text(
                                  'Service-ul este depășit!',
                                  style: TextStyle(
                                    color: Colors.red,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ] else if (maintenance.isServiceDueSoon) ...[
                          const SizedBox(height: 8),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: Colors.orange.withValues(alpha: 0.15),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.schedule, color: Colors.orange, size: 20),
                                const SizedBox(width: 8),
                                Text(
                                  'Service în ${maintenance.nextServiceDate!.difference(DateTime.now()).inDays} zile',
                                  style: const TextStyle(
                                    color: Colors.orange,
                                    fontWeight: FontWeight.bold,
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
              ],

              // Notes card
              if (maintenance.notes != null && maintenance.notes!.isNotEmpty) ...[
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
                          maintenance.notes!,
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

  void _showDeleteDialog(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Șterge înregistrare'),
        content: const Text('Sigur doriți să ștergeți această înregistrare de mentenanță?'),
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
                  .read(maintenanceListProvider.notifier)
                  .deleteMaintenance(maintenanceId);
              if (context.mounted) {
                if (success) {
                  context.pop();
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text('Înregistrare ștearsă'),
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

class _StatusChip extends StatelessWidget {
  final String status;

  const _StatusChip({required this.status});

  @override
  Widget build(BuildContext context) {
    Color color;
    String label;

    switch (status.toLowerCase()) {
      case 'completed':
        color = Colors.green;
        label = 'Finalizat';
        break;
      case 'in_progress':
        color = Colors.orange;
        label = 'În lucru';
        break;
      case 'scheduled':
        color = Colors.blue;
        label = 'Programat';
        break;
      case 'cancelled':
        color = Colors.grey;
        label = 'Anulat';
        break;
      default:
        color = Colors.grey;
        label = status;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final TextStyle? valueStyle;

  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
    this.valueStyle,
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
            style: valueStyle ?? const TextStyle(fontWeight: FontWeight.w500),
          ),
        ],
      ),
    );
  }
}
