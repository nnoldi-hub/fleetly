import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/insurance/data/models/insurance_model.dart';
import 'package:fleetly_mobile/features/insurance/presentation/providers/insurance_provider.dart';

/// Insurance list screen
class InsuranceListScreen extends ConsumerStatefulWidget {
  const InsuranceListScreen({super.key});

  @override
  ConsumerState<InsuranceListScreen> createState() => _InsuranceListScreenState();
}

class _InsuranceListScreenState extends ConsumerState<InsuranceListScreen> {
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent - 200) {
      ref.read(insuranceListProvider.notifier).loadMore();
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(insuranceListProvider);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Asigurări'),
        actions: [
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: () => _showFilterDialog(context, ref, state),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.read(insuranceListProvider.notifier).refresh(),
        child: _buildBody(state, theme),
      ),
      floatingActionButton: FloatingActionButton(
        heroTag: 'insurance_fab',
        onPressed: () => context.push('/insurance/new'),
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildBody(InsuranceListState state, ThemeData theme) {
    if (state.isLoading && state.insurances.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.error != null && state.insurances.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.error_outline, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              'Eroare la încărcare',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              state.error!,
              style: theme.textTheme.bodySmall,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => ref.read(insuranceListProvider.notifier).refresh(),
              child: const Text('Reîncearcă'),
            ),
          ],
        ),
      );
    }

    if (state.insurances.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.verified_user_outlined, size: 64, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              'Nicio asigurare',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              'Adaugă prima asigurare',
              style: theme.textTheme.bodySmall,
            ),
          ],
        ),
      );
    }

    return Column(
      children: [
        // Filter chips
        if (state.filterType != null ||
            state.filterStatus != null ||
            state.filterVehicleId != null)
          _buildFilterChips(state),

        // Stats bar
        _buildStatsBar(state, theme),

        // List
        Expanded(
          child: ListView.builder(
            controller: _scrollController,
            padding: const EdgeInsets.all(16),
            itemCount: state.insurances.length + (state.isLoadingMore ? 1 : 0),
            itemBuilder: (context, index) {
              if (index == state.insurances.length) {
                return const Center(
                  child: Padding(
                    padding: EdgeInsets.all(16),
                    child: CircularProgressIndicator(),
                  ),
                );
              }
              return _InsuranceCard(insurance: state.insurances[index]);
            },
          ),
        ),
      ],
    );
  }

  Widget _buildFilterChips(InsuranceListState state) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Wrap(
        spacing: 8,
        children: [
          if (state.filterType != null)
            Chip(
              label: Text(InsuranceType.fromString(state.filterType).label),
              onDeleted: () {
                ref.read(insuranceListProvider.notifier).setFilter(
                      type: null,
                      status: state.filterStatus,
                      vehicleId: state.filterVehicleId,
                    );
              },
            ),
          if (state.filterStatus != null)
            Chip(
              label: Text(InsuranceStatus.fromString(state.filterStatus).label),
              onDeleted: () {
                ref.read(insuranceListProvider.notifier).setFilter(
                      type: state.filterType,
                      status: null,
                      vehicleId: state.filterVehicleId,
                    );
              },
            ),
          TextButton(
            onPressed: () =>
                ref.read(insuranceListProvider.notifier).clearFilters(),
            child: const Text('Șterge filtrele'),
          ),
        ],
      ),
    );
  }

  Widget _buildStatsBar(InsuranceListState state, ThemeData theme) {
    final active = state.insurances.where((i) => i.status == InsuranceStatus.active).length;
    final expiring = state.insurances.where((i) => i.status == InsuranceStatus.expiringSoon).length;
    final expired = state.insurances.where((i) => i.status == InsuranceStatus.expired).length;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceContainerHighest.withAlpha(51),
        border: Border(
          bottom: BorderSide(color: theme.dividerColor),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _StatItem(label: 'Total', value: state.total.toString(), color: Colors.blue),
          _StatItem(label: 'Active', value: active.toString(), color: Colors.green),
          _StatItem(label: 'Expiră', value: expiring.toString(), color: Colors.orange),
          _StatItem(label: 'Expirate', value: expired.toString(), color: Colors.red),
        ],
      ),
    );
  }

  void _showFilterDialog(BuildContext context, WidgetRef ref, InsuranceListState state) {
    String? selectedType = state.filterType;
    String? selectedStatus = state.filterStatus;

    showDialog(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Filtrează asigurări'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<String?>(
                decoration: const InputDecoration(
                  labelText: 'Tip asigurare',
                  border: OutlineInputBorder(),
                ),
                value: selectedType,
                items: [
                  const DropdownMenuItem(value: null, child: Text('Toate')),
                  ...InsuranceType.values.map((t) => DropdownMenuItem(
                        value: t.value,
                        child: Text(t.label),
                      )),
                ],
                onChanged: (value) => setState(() => selectedType = value),
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String?>(
                decoration: const InputDecoration(
                  labelText: 'Status',
                  border: OutlineInputBorder(),
                ),
                value: selectedStatus,
                items: [
                  const DropdownMenuItem(value: null, child: Text('Toate')),
                  ...InsuranceStatus.values.map((s) => DropdownMenuItem(
                        value: s.value,
                        child: Text(s.label),
                      )),
                ],
                onChanged: (value) => setState(() => selectedStatus = value),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Anulează'),
            ),
            ElevatedButton(
              onPressed: () {
                ref.read(insuranceListProvider.notifier).setFilter(
                      type: selectedType,
                      status: selectedStatus,
                    );
                Navigator.pop(context);
              },
              child: const Text('Aplică'),
            ),
          ],
        ),
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _StatItem({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: Colors.grey.shade600,
          ),
        ),
      ],
    );
  }
}

class _InsuranceCard extends ConsumerWidget {
  final Insurance insurance;

  const _InsuranceCard({required this.insurance});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final dateFormat = DateFormat('dd/MM/yyyy');

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () => context.push('/insurance/${insurance.id}'),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Color(insurance.status.color).withAlpha(26),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      _getTypeIcon(insurance.type),
                      color: Color(insurance.status.color),
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          insurance.typeLabel,
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        if (insurance.policyNumber != null)
                          Text(
                            'Poliță: ${insurance.policyNumber}',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: Colors.grey.shade600,
                            ),
                          ),
                      ],
                    ),
                  ),
                  _buildStatusBadge(insurance.status),
                ],
              ),
              const SizedBox(height: 12),
              const Divider(height: 1),
              const SizedBox(height: 12),

              // Details
              _DetailRow(
                icon: Icons.directions_car,
                label: 'Vehicul',
                value: insurance.vehicleDisplay,
              ),
              const SizedBox(height: 8),
              _DetailRow(
                icon: Icons.business,
                label: 'Asigurător',
                value: insurance.provider ?? 'Nespecificat',
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: _DetailRow(
                      icon: Icons.calendar_today,
                      label: 'Expiră',
                      value: dateFormat.format(insurance.endDate),
                    ),
                  ),
                  if (insurance.premium != null)
                    Expanded(
                      child: _DetailRow(
                        icon: Icons.payments,
                        label: 'Primă',
                        value: '${insurance.premium!.toStringAsFixed(2)} RON',
                      ),
                    ),
                ],
              ),

              // Days until expiry
              if (!insurance.isExpired && insurance.daysUntilExpiry <= 30)
                Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: insurance.daysUntilExpiry <= 7
                          ? Colors.red.shade50
                          : Colors.orange.shade50,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(
                          Icons.warning_amber_rounded,
                          size: 16,
                          color: insurance.daysUntilExpiry <= 7
                              ? Colors.red.shade700
                              : Colors.orange.shade700,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          'Expiră în ${insurance.daysUntilExpiry} zile',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w500,
                            color: insurance.daysUntilExpiry <= 7
                                ? Colors.red.shade700
                                : Colors.orange.shade700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
            ],
          ),
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

  Widget _buildStatusBadge(InsuranceStatus status) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Color(status.color).withAlpha(26),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        status.label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w500,
          color: Color(status.color),
        ),
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
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey.shade500),
        const SizedBox(width: 6),
        Text(
          '$label: ',
          style: TextStyle(
            fontSize: 13,
            color: Colors.grey.shade600,
          ),
        ),
        Flexible(
          child: Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
            ),
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }
}
