import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/core/theme/app_theme.dart';
import 'package:fleetly_mobile/features/vehicles/data/models/vehicle_model.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/providers/vehicles_provider.dart';

class VehicleDetailScreen extends ConsumerWidget {
  final int vehicleId;

  const VehicleDetailScreen({
    super.key,
    required this.vehicleId,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(vehicleDetailProvider(vehicleId));

    return Scaffold(
      appBar: AppBar(
        title: Text(state.vehicle?.plateNumber ?? 'Vehicul'),
        actions: [
          if (state.vehicle != null) ...[
            IconButton(
              icon: const Icon(Icons.edit_outlined),
              onPressed: () => context.push('/vehicles/${vehicleId}/edit'),
            ),
            PopupMenuButton<String>(
              onSelected: (value) {
                if (value == 'delete') {
                  _showDeleteDialog(context, ref);
                } else if (value == 'mileage') {
                  _showMileageDialog(context, ref, state.vehicle!);
                }
              },
              itemBuilder: (context) => [
                const PopupMenuItem(
                  value: 'mileage',
                  child: ListTile(
                    leading: Icon(Icons.speed),
                    title: Text('Actualizează km'),
                    contentPadding: EdgeInsets.zero,
                  ),
                ),
                const PopupMenuItem(
                  value: 'delete',
                  child: ListTile(
                    leading: Icon(Icons.delete_outline, color: AppTheme.errorColor),
                    title: Text('Șterge', style: TextStyle(color: AppTheme.errorColor)),
                    contentPadding: EdgeInsets.zero,
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
      body: _buildBody(context, ref, state),
    );
  }

  Widget _buildBody(BuildContext context, WidgetRef ref, VehicleDetailState state) {
    if (state.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(state.error!, style: TextStyle(color: Colors.grey.shade600)),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () => ref.read(vehicleDetailProvider(vehicleId).notifier).loadVehicle(),
              icon: const Icon(Icons.refresh),
              label: const Text('Reîncearcă'),
            ),
          ],
        ),
      );
    }

    final vehicle = state.vehicle;
    if (vehicle == null) {
      return const Center(child: Text('Vehicul negăsit'));
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(vehicleDetailProvider(vehicleId).notifier).loadVehicle(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header card with main info
            _buildHeaderCard(context, vehicle),
            const SizedBox(height: 16),

            // Status and alerts section
            if (vehicle.hasAlerts)
              _buildAlertsCard(context, vehicle),
            
            // Quick actions
            _buildQuickActions(context, vehicle),
            const SizedBox(height: 16),

            // Details section
            _buildDetailsSection(context, vehicle),
            const SizedBox(height: 16),

            // Documents section
            _buildDocumentsSection(context, vehicle),
            const SizedBox(height: 16),

            // Driver section
            if (vehicle.driverName != null)
              _buildDriverSection(context, vehicle),
          ],
        ),
      ),
    );
  }

  Widget _buildHeaderCard(BuildContext context, Vehicle vehicle) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            // Vehicle icon
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                color: AppTheme.primaryColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Icon(
                _getVehicleIcon(vehicle.type),
                color: AppTheme.primaryColor,
                size: 40,
              ),
            ),
            const SizedBox(height: 16),

            // Plate number
            Text(
              vehicle.plateNumber,
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),

            // Brand and model
            Text(
              vehicle.displayName,
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey.shade600,
              ),
            ),
            if (vehicle.year != null) ...[
              const SizedBox(height: 2),
              Text(
                '${vehicle.year}',
                style: TextStyle(color: Colors.grey.shade500),
              ),
            ],
            const SizedBox(height: 16),

            // Status badge
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: _getStatusColor(vehicle.status).withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                vehicle.statusLabel,
                style: TextStyle(
                  color: _getStatusColor(vehicle.status),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Mileage
            if (vehicle.currentMileage != null)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.speed, color: Colors.grey.shade600, size: 20),
                  const SizedBox(width: 8),
                  Text(
                    '${_formatNumber(vehicle.currentMileage!)} km',
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildAlertsCard(BuildContext context, Vehicle vehicle) {
    final alerts = <Widget>[];
    final now = DateTime.now();
    final dateFormat = DateFormat('dd.MM.yyyy');

    if (vehicle.insuranceExpiry != null && 
        vehicle.insuranceExpiry!.isBefore(now.add(const Duration(days: 30)))) {
      final isExpired = vehicle.insuranceExpiry!.isBefore(now);
      alerts.add(
        _AlertItem(
          icon: Icons.shield_outlined,
          title: isExpired ? 'Asigurare expirată' : 'Asigurare expiră curând',
          subtitle: 'Data expirare: ${dateFormat.format(vehicle.insuranceExpiry!)}',
          isError: isExpired,
        ),
      );
    }

    if (vehicle.itpExpiry != null && 
        vehicle.itpExpiry!.isBefore(now.add(const Duration(days: 30)))) {
      final isExpired = vehicle.itpExpiry!.isBefore(now);
      alerts.add(
        _AlertItem(
          icon: Icons.verified_outlined,
          title: isExpired ? 'ITP expirat' : 'ITP expiră curând',
          subtitle: 'Data expirare: ${dateFormat.format(vehicle.itpExpiry!)}',
          isError: isExpired,
        ),
      );
    }

    if (alerts.isEmpty) return const SizedBox.shrink();

    return Card(
      color: AppTheme.warningColor.withValues(alpha: 0.1),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.warning_amber_rounded, color: AppTheme.warningColor),
                const SizedBox(width: 8),
                Text(
                  'Alerte',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.orange.shade800,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ...alerts,
          ],
        ),
      ),
    );
  }

  Widget _buildQuickActions(BuildContext context, Vehicle vehicle) {
    return Row(
      children: [
        Expanded(
          child: _QuickActionButton(
            icon: Icons.local_gas_station,
            label: 'Alimentare',
            onTap: () {
              // TODO: Navigate to add fuel
            },
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _QuickActionButton(
            icon: Icons.build,
            label: 'Service',
            onTap: () {
              // TODO: Navigate to add maintenance
            },
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _QuickActionButton(
            icon: Icons.description,
            label: 'Documente',
            onTap: () {
              // TODO: Navigate to documents
            },
          ),
        ),
      ],
    );
  }

  Widget _buildDetailsSection(BuildContext context, Vehicle vehicle) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Detalii tehnice',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            _DetailRow(label: 'Tip vehicul', value: vehicle.typeLabel),
            if (vehicle.fuelType != null)
              _DetailRow(label: 'Combustibil', value: vehicle.fuelTypeLabel),
            if (vehicle.vin != null)
              _DetailRow(label: 'VIN', value: vehicle.vin!),
            if (vehicle.color != null)
              _DetailRow(label: 'Culoare', value: vehicle.color!),
          ],
        ),
      ),
    );
  }

  Widget _buildDocumentsSection(BuildContext context, Vehicle vehicle) {
    final dateFormat = DateFormat('dd.MM.yyyy');
    
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Documente',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                TextButton(
                  onPressed: () {
                    // TODO: View all documents
                  },
                  child: const Text('Vezi toate'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (vehicle.insuranceExpiry != null)
              _DocumentItem(
                icon: Icons.shield_outlined,
                title: 'Asigurare RCA',
                expiry: vehicle.insuranceExpiry!,
                dateFormat: dateFormat,
              ),
            if (vehicle.itpExpiry != null)
              _DocumentItem(
                icon: Icons.verified_outlined,
                title: 'ITP',
                expiry: vehicle.itpExpiry!,
                dateFormat: dateFormat,
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildDriverSection(BuildContext context, Vehicle vehicle) {
    return Card(
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: AppTheme.primaryColor.withValues(alpha: 0.1),
          child: const Icon(Icons.person, color: AppTheme.primaryColor),
        ),
        title: const Text('Șofer asignat'),
        subtitle: Text(vehicle.driverName!),
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          if (vehicle.driverId != null) {
            context.push('/drivers/${vehicle.driverId}');
          }
        },
      ),
    );
  }

  void _showDeleteDialog(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Șterge vehicul'),
        content: const Text('Ești sigur că vrei să ștergi acest vehicul? Această acțiune nu poate fi anulată.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              final success = await ref.read(vehicleFormProvider.notifier).deleteVehicle(vehicleId);
              if (success && context.mounted) {
                ref.read(vehiclesProvider.notifier).removeVehicle(vehicleId);
                context.pop();
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Vehicul șters cu succes')),
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: AppTheme.errorColor),
            child: const Text('Șterge'),
          ),
        ],
      ),
    );
  }

  void _showMileageDialog(BuildContext context, WidgetRef ref, Vehicle vehicle) {
    final controller = TextEditingController(
      text: vehicle.currentMileage?.toString() ?? '',
    );

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Actualizează kilometraj'),
        content: TextField(
          controller: controller,
          keyboardType: TextInputType.number,
          decoration: const InputDecoration(
            labelText: 'Kilometraj actual',
            suffix: Text('km'),
          ),
          autofocus: true,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
          ElevatedButton(
            onPressed: () async {
              final mileage = int.tryParse(controller.text);
              if (mileage != null && mileage > 0) {
                Navigator.pop(context);
                final success = await ref
                    .read(vehicleDetailProvider(vehicleId).notifier)
                    .updateMileage(mileage);
                if (success && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Kilometraj actualizat')),
                  );
                }
              }
            },
            child: const Text('Salvează'),
          ),
        ],
      ),
    );
  }

  Color _getStatusColor(String? status) {
    switch (status?.toLowerCase()) {
      case 'active':
        return AppTheme.successColor;
      case 'inactive':
        return Colors.grey;
      case 'maintenance':
        return AppTheme.warningColor;
      default:
        return Colors.grey;
    }
  }

  IconData _getVehicleIcon(String? type) {
    switch (type?.toLowerCase()) {
      case 'car':
        return Icons.directions_car;
      case 'van':
        return Icons.airport_shuttle;
      case 'truck':
        return Icons.local_shipping;
      default:
        return Icons.directions_car;
    }
  }

  String _formatNumber(int number) {
    return number.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]}.',
    );
  }
}

class _AlertItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final bool isError;

  const _AlertItem({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.isError = false,
  });

  @override
  Widget build(BuildContext context) {
    final color = isError ? AppTheme.errorColor : AppTheme.warningColor;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontWeight: FontWeight.w500,
                    color: color,
                  ),
                ),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey.shade600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _QuickActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 16),
          child: Column(
            children: [
              Icon(icon, color: AppTheme.primaryColor),
              const SizedBox(height: 8),
              Text(
                label,
                style: const TextStyle(fontSize: 12),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(color: Colors.grey.shade600),
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

class _DocumentItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final DateTime expiry;
  final DateFormat dateFormat;

  const _DocumentItem({
    required this.icon,
    required this.title,
    required this.expiry,
    required this.dateFormat,
  });

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final isExpired = expiry.isBefore(now);
    final isExpiringSoon = !isExpired && expiry.isBefore(now.add(const Duration(days: 30)));
    
    Color statusColor = AppTheme.successColor;
    if (isExpired) {
      statusColor = AppTheme.errorColor;
    } else if (isExpiringSoon) {
      statusColor = AppTheme.warningColor;
    }

    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: statusColor.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, color: statusColor, size: 20),
      ),
      title: Text(title),
      subtitle: Text(
        'Expiră: ${dateFormat.format(expiry)}',
        style: TextStyle(
          color: statusColor,
          fontSize: 12,
        ),
      ),
      trailing: Icon(
        Icons.chevron_right,
        color: Colors.grey.shade400,
      ),
    );
  }
}
