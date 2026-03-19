import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/insurance/data/models/insurance_model.dart';
import 'package:fleetly_mobile/features/insurance/presentation/providers/insurance_provider.dart';

/// Insurance detail screen
class InsuranceDetailScreen extends ConsumerWidget {
  final int insuranceId;

  const InsuranceDetailScreen({super.key, required this.insuranceId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(insuranceDetailProvider(insuranceId));
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalii Asigurare'),
        actions: [
          if (state.insurance != null) ...[
            IconButton(
              icon: const Icon(Icons.edit),
              onPressed: () => context.push('/insurance/$insuranceId/edit'),
            ),
            IconButton(
              icon: const Icon(Icons.delete),
              onPressed: () => _confirmDelete(context, ref),
            ),
          ],
        ],
      ),
      body: _buildBody(context, ref, state, theme),
    );
  }

  Widget _buildBody(
    BuildContext context,
    WidgetRef ref,
    InsuranceDetailState state,
    ThemeData theme,
  ) {
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
            Text('Eroare: ${state.error}'),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () =>
                  ref.read(insuranceDetailProvider(insuranceId).notifier).refresh(),
              child: const Text('Reîncearcă'),
            ),
          ],
        ),
      );
    }

    final insurance = state.insurance;
    if (insurance == null) {
      return const Center(child: Text('Asigurare negăsită'));
    }

    final dateFormat = DateFormat('dd/MM/yyyy');

    return RefreshIndicator(
      onRefresh: () =>
          ref.read(insuranceDetailProvider(insuranceId).notifier).refresh(),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header card
            _buildHeaderCard(insurance, theme, dateFormat),
            const SizedBox(height: 16),

            // Status & Expiry
            _buildStatusCard(insurance, theme),
            const SizedBox(height: 16),

            // Vehicle info
            if (insurance.vehicle != null)
              _buildVehicleCard(insurance.vehicle!, theme, context),
            const SizedBox(height: 16),

            // Financial info
            _buildFinancialCard(insurance, theme),

            // Notes
            if (insurance.notes != null && insurance.notes!.isNotEmpty) ...[
              const SizedBox(height: 16),
              _buildNotesCard(insurance.notes!, theme),
            ],

            // Dates
            const SizedBox(height: 16),
            _buildDatesCard(insurance, theme, dateFormat),

            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildHeaderCard(
    Insurance insurance,
    ThemeData theme,
    DateFormat dateFormat,
  ) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Color(insurance.status.color).withAlpha(26),
                shape: BoxShape.circle,
              ),
              child: Icon(
                _getTypeIcon(insurance.type),
                size: 48,
                color: Color(insurance.status.color),
              ),
            ),
            const SizedBox(height: 16),
            Text(
              insurance.typeLabel,
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            if (insurance.policyNumber != null) ...[
              const SizedBox(height: 4),
              Text(
                'Poliță: ${insurance.policyNumber}',
                style: theme.textTheme.bodyLarge?.copyWith(
                  color: Colors.grey.shade600,
                ),
              ),
            ],
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: Color(insurance.status.color).withAlpha(26),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                insurance.statusLabel,
                style: TextStyle(
                  fontWeight: FontWeight.w600,
                  color: Color(insurance.status.color),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusCard(Insurance insurance, ThemeData theme) {
    final daysText = insurance.isExpired
        ? 'Expirată de ${-insurance.daysUntilExpiry} zile'
        : '${insurance.daysUntilExpiry} zile rămase';

    final progressValue = insurance.isExpired
        ? 1.0
        : (30 - insurance.daysUntilExpiry.clamp(0, 30)) / 30;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  insurance.isExpired ? Icons.error : Icons.schedule,
                  color: Color(insurance.status.color),
                ),
                const SizedBox(width: 8),
                Text(
                  'Status Valabilitate',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        daysText,
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Color(insurance.status.color),
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Data expirare: ${DateFormat('dd MMMM yyyy', 'ro').format(insurance.endDate)}',
                        style: theme.textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (!insurance.isExpired && insurance.daysUntilExpiry <= 30) ...[
              const SizedBox(height: 16),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: progressValue,
                  backgroundColor: Colors.grey.shade200,
                  valueColor: AlwaysStoppedAnimation<Color>(
                    Color(insurance.status.color),
                  ),
                  minHeight: 8,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildVehicleCard(
    InsuranceVehicle vehicle,
    ThemeData theme,
    BuildContext context,
  ) {
    return Card(
      child: InkWell(
        onTap: () => context.push('/vehicles/${vehicle.id}'),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary.withAlpha(26),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  Icons.directions_car,
                  color: theme.colorScheme.primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Vehicul Asigurat',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: Colors.grey.shade600,
                      ),
                    ),
                    Text(
                      vehicle.registrationNumber,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if (vehicle.name != null)
                      Text(
                        vehicle.name!,
                        style: theme.textTheme.bodySmall,
                      ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFinancialCard(Insurance insurance, ThemeData theme) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.payments, color: Colors.green),
                const SizedBox(width: 8),
                Text(
                  'Informații Financiare',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _InfoTile(
                    label: 'Primă Asigurare',
                    value: insurance.premium != null
                        ? '${insurance.premium!.toStringAsFixed(2)} RON'
                        : 'Nespecificat',
                    icon: Icons.price_change_outlined,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _InfoTile(
                    label: 'Sumă Asigurată',
                    value: insurance.coverageAmount != null
                        ? '${insurance.coverageAmount!.toStringAsFixed(0)} RON'
                        : 'Nespecificat',
                    icon: Icons.account_balance_wallet_outlined,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            _InfoTile(
              label: 'Asigurător',
              value: insurance.provider ?? 'Nespecificat',
              icon: Icons.business,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNotesCard(String notes, ThemeData theme) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.notes, color: Colors.blue),
                const SizedBox(width: 8),
                Text(
                  'Observații',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(notes),
          ],
        ),
      ),
    );
  }

  Widget _buildDatesCard(
    Insurance insurance,
    ThemeData theme,
    DateFormat dateFormat,
  ) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.calendar_month, color: Colors.purple),
                const SizedBox(width: 8),
                Text(
                  'Perioada Asigurare',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: _InfoTile(
                    label: 'Data Început',
                    value: dateFormat.format(insurance.startDate),
                    icon: Icons.play_arrow,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _InfoTile(
                    label: 'Data Sfârșit',
                    value: dateFormat.format(insurance.endDate),
                    icon: Icons.stop,
                  ),
                ),
              ],
            ),
            if (insurance.createdAt != null) ...[
              const Divider(height: 24),
              Text(
                'Adăugat: ${DateFormat('dd/MM/yyyy HH:mm').format(insurance.createdAt!)}',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: Colors.grey.shade500,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  IconData _getTypeIcon(InsuranceType type) {
    switch (type) {
      case InsuranceType.rca:
        return Icons.security;
      case InsuranceType.casco:
        return Icons.verified_user;
      case InsuranceType.cmr:
        return Icons.local_shipping;
      case InsuranceType.other:
        return Icons.description;
    }
  }

  void _confirmDelete(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Șterge asigurare'),
        content: const Text(
          'Sigur doriți să ștergeți această asigurare? Acțiunea este ireversibilă.',
        ),
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
                  .read(insuranceListProvider.notifier)
                  .deleteInsurance(insuranceId);
              if (success && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Asigurare ștearsă'),
                    backgroundColor: Colors.green,
                  ),
                );
                context.pop();
              }
            },
            child: const Text('Șterge'),
          ),
        ],
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;

  const _InfoTile({
    required this.label,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: Colors.grey.shade500),
            const SizedBox(width: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}
