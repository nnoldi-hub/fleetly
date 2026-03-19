import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/drivers/presentation/providers/drivers_provider.dart';

class DriverDetailScreen extends ConsumerStatefulWidget {
  final int driverId;

  const DriverDetailScreen({
    super.key,
    required this.driverId,
  });

  @override
  ConsumerState<DriverDetailScreen> createState() => _DriverDetailScreenState();
}

class _DriverDetailScreenState extends ConsumerState<DriverDetailScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(driverDetailProvider.notifier).loadDriver(widget.driverId);
    });
  }

  Future<void> _confirmDelete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmare ștergere'),
        content: const Text('Sigur dorești să ștergi acest șofer? Această acțiune nu poate fi anulată.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Anulează'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Șterge'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final success = await ref.read(driverDetailProvider.notifier).deleteDriver(widget.driverId);
      if (success && mounted) {
        ref.read(driversProvider.notifier).removeDriver(widget.driverId);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Șofer șters cu succes')),
        );
        context.pop();
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Eroare la ștergerea șoferului'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(driverDetailProvider);
    final theme = Theme.of(context);
    final dateFormat = DateFormat('dd.MM.yyyy');

    return Scaffold(
      appBar: AppBar(
        title: Text(state.driver?.fullName ?? 'Detalii Șofer'),
        actions: [
          if (state.driver != null) ...[
            IconButton(
              icon: const Icon(Icons.edit),
              onPressed: () => context.push('/drivers/${widget.driverId}/edit'),
              tooltip: 'Editează',
            ),
            PopupMenuButton<String>(
              onSelected: (value) {
                if (value == 'delete') {
                  _confirmDelete();
                }
              },
              itemBuilder: (context) => [
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
            ),
          ],
        ],
      ),
      body: _buildBody(state, theme, dateFormat),
    );
  }

  Widget _buildBody(DriverDetailState state, ThemeData theme, DateFormat dateFormat) {
    if (state.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.error_outline,
              size: 64,
              color: theme.colorScheme.error,
            ),
            const SizedBox(height: 16),
            Text(
              'Eroare la încărcarea șoferului',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              state.error!,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () => ref.read(driverDetailProvider.notifier).loadDriver(widget.driverId),
              icon: const Icon(Icons.refresh),
              label: const Text('Reîncearcă'),
            ),
          ],
        ),
      );
    }

    final driver = state.driver;
    if (driver == null) {
      return const Center(child: Text('Șofer negăsit'));
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(driverDetailProvider.notifier).loadDriver(widget.driverId),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header card with avatar
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 40,
                      backgroundColor: theme.colorScheme.primaryContainer,
                      child: Text(
                        driver.initials,
                        style: theme.textTheme.headlineMedium?.copyWith(
                          color: theme.colorScheme.onPrimaryContainer,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                    const SizedBox(width: 20),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            driver.fullName,
                            style: theme.textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 4),
                          _StatusBadge(status: driver.status),
                          if (driver.vehiclePlate != null) ...[
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Icon(
                                  Icons.directions_car,
                                  size: 16,
                                  color: theme.colorScheme.primary,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  driver.vehiclePlate!,
                                  style: theme.textTheme.bodyMedium?.copyWith(
                                    color: theme.colorScheme.primary,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Alerts section
            if (driver.hasAlerts) ...[
              _AlertsSection(driver: state.driver!),
              const SizedBox(height: 16),
            ],

            // Quick actions
            _QuickActionsSection(
              driver: driver,
              onCallPhone: driver.phone != null ? () => _makePhoneCall(driver.phone!) : null,
              onSendEmail: driver.email != null ? () => _sendEmail(driver.email!) : null,
            ),
            const SizedBox(height: 16),

            // Contact info
            _SectionCard(
              title: 'Informații Contact',
              icon: Icons.contact_phone,
              children: [
                if (driver.phone != null)
                  _InfoRow(icon: Icons.phone, label: 'Telefon', value: driver.phone!),
                if (driver.email != null)
                  _InfoRow(icon: Icons.email, label: 'Email', value: driver.email!),
                if (driver.address != null)
                  _InfoRow(icon: Icons.location_on, label: 'Adresă', value: driver.address!),
              ],
            ),
            const SizedBox(height: 16),

            // Personal info
            _SectionCard(
              title: 'Date Personale',
              icon: Icons.person,
              children: [
                if (driver.cnp != null)
                  _InfoRow(icon: Icons.badge, label: 'CNP', value: driver.cnp!),
                if (driver.hireDate != null)
                  _InfoRow(
                    icon: Icons.work,
                    label: 'Data angajării',
                    value: dateFormat.format(driver.hireDate!),
                  ),
              ],
            ),
            const SizedBox(height: 16),

            // License info
            _SectionCard(
              title: 'Permis de Conducere',
              icon: Icons.credit_card,
              children: [
                if (driver.licenseNumber != null)
                  _InfoRow(
                    icon: Icons.numbers,
                    label: 'Număr permis',
                    value: driver.licenseNumber!,
                  ),
                if (driver.licenseCategories != null)
                  _InfoRow(
                    icon: Icons.category,
                    label: 'Categorii',
                    value: driver.licenseCategories!,
                  ),
                if (driver.licenseExpiry != null)
                  _InfoRow(
                    icon: Icons.event,
                    label: 'Expirare permis',
                    value: dateFormat.format(driver.licenseExpiry!),
                    isAlert: driver.isLicenseExpired ||
                        driver.licenseExpiry!.isBefore(DateTime.now().add(const Duration(days: 30))),
                  ),
                if (driver.medicalExpiry != null)
                  _InfoRow(
                    icon: Icons.medical_services,
                    label: 'Expirare fișă medicală',
                    value: dateFormat.format(driver.medicalExpiry!),
                    isAlert: driver.isMedicalExpired ||
                        driver.medicalExpiry!.isBefore(DateTime.now().add(const Duration(days: 30))),
                  ),
              ],
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  void _makePhoneCall(String phone) {
    // TODO: Implement phone call
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Apel: $phone')),
    );
  }

  void _sendEmail(String email) {
    // TODO: Implement email
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('Email: $email')),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String? status;

  const _StatusBadge({this.status});

  @override
  Widget build(BuildContext context) {
    Color backgroundColor;
    Color textColor;
    String label;

    switch (status?.toLowerCase()) {
      case 'active':
        backgroundColor = Colors.green.withValues(alpha: 0.1);
        textColor = Colors.green;
        label = 'Activ';
        break;
      case 'inactive':
        backgroundColor = Colors.grey.withValues(alpha: 0.1);
        textColor = Colors.grey;
        label = 'Inactiv';
        break;
      case 'on_leave':
        backgroundColor = Colors.orange.withValues(alpha: 0.1);
        textColor = Colors.orange;
        label = 'În concediu';
        break;
      case 'suspended':
        backgroundColor = Colors.red.withValues(alpha: 0.1);
        textColor = Colors.red;
        label = 'Suspendat';
        break;
      default:
        backgroundColor = Colors.grey.withValues(alpha: 0.1);
        textColor = Colors.grey;
        label = status ?? 'Necunoscut';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: textColor,
        ),
      ),
    );
  }
}

class _AlertsSection extends StatelessWidget {
  final dynamic driver;

  const _AlertsSection({required this.driver});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final alerts = <Widget>[];

    if (driver.isLicenseExpired) {
      alerts.add(_AlertItem(
        icon: Icons.credit_card,
        title: 'Permis expirat',
        color: Colors.red,
      ));
    } else if (driver.licenseExpiry != null &&
        driver.licenseExpiry.isBefore(DateTime.now().add(const Duration(days: 30)))) {
      alerts.add(_AlertItem(
        icon: Icons.credit_card,
        title: 'Permis expiră în curând',
        color: Colors.orange,
      ));
    }

    if (driver.isMedicalExpired) {
      alerts.add(_AlertItem(
        icon: Icons.medical_services,
        title: 'Fișă medicală expirată',
        color: Colors.red,
      ));
    } else if (driver.medicalExpiry != null &&
        driver.medicalExpiry.isBefore(DateTime.now().add(const Duration(days: 30)))) {
      alerts.add(_AlertItem(
        icon: Icons.medical_services,
        title: 'Fișă medicală expiră în curând',
        color: Colors.orange,
      ));
    }

    if (alerts.isEmpty) return const SizedBox.shrink();

    return Card(
      color: Colors.orange.withValues(alpha: 0.1),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 20),
                const SizedBox(width: 8),
                Text(
                  'Alerte',
                  style: theme.textTheme.titleSmall?.copyWith(
                    color: Colors.orange,
                    fontWeight: FontWeight.w600,
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
}

class _AlertItem extends StatelessWidget {
  final IconData icon;
  final String title;
  final Color color;

  const _AlertItem({
    required this.icon,
    required this.title,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 8),
          Text(
            title,
            style: TextStyle(
              fontSize: 14,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionsSection extends StatelessWidget {
  final dynamic driver;
  final VoidCallback? onCallPhone;
  final VoidCallback? onSendEmail;

  const _QuickActionsSection({
    required this.driver,
    this.onCallPhone,
    this.onSendEmail,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        if (onCallPhone != null)
          Expanded(
            child: _ActionButton(
              icon: Icons.phone,
              label: 'Apelează',
              onTap: onCallPhone!,
              color: Colors.green,
            ),
          ),
        if (onCallPhone != null && onSendEmail != null) const SizedBox(width: 12),
        if (onSendEmail != null)
          Expanded(
            child: _ActionButton(
              icon: Icons.email,
              label: 'Email',
              onTap: onSendEmail!,
              color: Colors.blue,
            ),
          ),
      ],
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final Color color;

  const _ActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
    required this.color,
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
              Icon(icon, color: color, size: 28),
              const SizedBox(height: 4),
              Text(
                label,
                style: TextStyle(
                  color: color,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final List<Widget> children;

  const _SectionCard({
    required this.title,
    required this.icon,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    if (children.isEmpty) return const SizedBox.shrink();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, size: 20, color: theme.colorScheme.primary),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: theme.textTheme.titleSmall?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            ...children,
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final bool isAlert;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
    this.isAlert = false,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final valueColor = isAlert ? Colors.orange : theme.colorScheme.onSurface;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            icon,
            size: 18,
            color: isAlert ? Colors.orange : theme.colorScheme.onSurfaceVariant,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: valueColor,
                    fontWeight: isAlert ? FontWeight.w600 : null,
                  ),
                ),
              ],
            ),
          ),
          if (isAlert)
            Icon(
              Icons.warning_amber_rounded,
              size: 18,
              color: Colors.orange,
            ),
        ],
      ),
    );
  }
}
