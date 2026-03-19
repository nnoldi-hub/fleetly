import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:fleetly_mobile/core/theme/app_theme.dart';
import 'package:fleetly_mobile/features/vehicles/data/models/vehicle_model.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/providers/vehicles_provider.dart';

class VehiclesListScreen extends ConsumerStatefulWidget {
  const VehiclesListScreen({super.key});

  @override
  ConsumerState<VehiclesListScreen> createState() => _VehiclesListScreenState();
}

class _VehiclesListScreenState extends ConsumerState<VehiclesListScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  String? _selectedStatus;
  String? _selectedType;

  @override
  void initState() {
    super.initState();
    // Load vehicles on init
    Future.microtask(() {
      ref.read(vehiclesProvider.notifier).loadVehicles();
    });

    // Add scroll listener for pagination
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= 
        _scrollController.position.maxScrollExtent - 200) {
      ref.read(vehiclesProvider.notifier).loadMore();
    }
  }

  void _onSearch(String query) {
    ref.read(vehiclesProvider.notifier).search(query);
  }

  void _showFilters() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => _FiltersBottomSheet(
        selectedStatus: _selectedStatus,
        selectedType: _selectedType,
        onApply: (status, type) {
          setState(() {
            _selectedStatus = status;
            _selectedType = type;
          });
          ref.read(vehiclesProvider.notifier).loadVehicles(
            search: _searchController.text.isEmpty ? null : _searchController.text,
            status: status,
            type: type,
          );
          Navigator.pop(context);
        },
        onClear: () {
          setState(() {
            _selectedStatus = null;
            _selectedType = null;
          });
          ref.read(vehiclesProvider.notifier).clearFilters();
          Navigator.pop(context);
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(vehiclesProvider);
    final hasFilters = _selectedStatus != null || _selectedType != null;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Vehicule'),
        actions: [
          Stack(
            children: [
              IconButton(
                icon: const Icon(Icons.filter_list),
                onPressed: _showFilters,
              ),
              if (hasFilters)
                Positioned(
                  right: 8,
                  top: 8,
                  child: Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppTheme.primaryColor,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
      body: Column(
        children: [
          // Search bar
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Caută vehicul...',
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _searchController.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear),
                        onPressed: () {
                          _searchController.clear();
                          _onSearch('');
                        },
                      )
                    : null,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                filled: true,
                fillColor: Colors.grey.shade100,
              ),
              onChanged: _onSearch,
            ),
          ),

          // Active filters chips
          if (hasFilters)
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(
                children: [
                  if (_selectedStatus != null)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: Chip(
                        label: Text(_getStatusLabel(_selectedStatus!)),
                        deleteIcon: const Icon(Icons.close, size: 16),
                        onDeleted: () {
                          setState(() => _selectedStatus = null);
                          ref.read(vehiclesProvider.notifier).filterByStatus(null);
                        },
                      ),
                    ),
                  if (_selectedType != null)
                    Chip(
                      label: Text(_getTypeLabel(_selectedType!)),
                      deleteIcon: const Icon(Icons.close, size: 16),
                      onDeleted: () {
                        setState(() => _selectedType = null);
                        ref.read(vehiclesProvider.notifier).filterByType(null);
                      },
                    ),
                ],
              ),
            ),

          // Content
          Expanded(
            child: _buildContent(state),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          context.push('/vehicles/add');
        },
        child: const Icon(Icons.add),
      ),
    );
  }

  Widget _buildContent(VehiclesState state) {
    if (state.isLoading && state.vehicles.isEmpty) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (state.error != null && state.vehicles.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.error_outline,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            Text(
              state.error!,
              style: TextStyle(color: Colors.grey.shade600),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: () => ref.read(vehiclesProvider.notifier).refresh(),
              icon: const Icon(Icons.refresh),
              label: const Text('Reîncearcă'),
            ),
          ],
        ),
      );
    }

    if (state.vehicles.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.directions_car_outlined,
              size: 64,
              color: Colors.grey.shade400,
            ),
            const SizedBox(height: 16),
            Text(
              'Nu există vehicule',
              style: TextStyle(
                fontSize: 18,
                color: Colors.grey.shade600,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'Adaugă primul vehicul din flotă',
              style: TextStyle(color: Colors.grey.shade500),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(vehiclesProvider.notifier).refresh(),
      child: ListView.builder(
        controller: _scrollController,
        padding: const EdgeInsets.only(bottom: 80),
        itemCount: state.vehicles.length + (state.isLoadingMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index == state.vehicles.length) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: CircularProgressIndicator(),
              ),
            );
          }

          final vehicle = state.vehicles[index];
          return _VehicleListItem(
            vehicle: vehicle,
            onTap: () => context.push('/vehicles/${vehicle.id}'),
          );
        },
      ),
    );
  }

  String _getStatusLabel(String status) {
    switch (status.toLowerCase()) {
      case 'active':
        return 'Activ';
      case 'inactive':
        return 'Inactiv';
      case 'maintenance':
        return 'În service';
      default:
        return status;
    }
  }

  String _getTypeLabel(String type) {
    switch (type.toLowerCase()) {
      case 'car':
        return 'Autoturism';
      case 'van':
        return 'Dubă';
      case 'truck':
        return 'Camion';
      default:
        return type;
    }
  }
}

/// Vehicle list item widget
class _VehicleListItem extends StatelessWidget {
  final Vehicle vehicle;
  final VoidCallback onTap;

  const _VehicleListItem({
    required this.vehicle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Vehicle icon
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: _getStatusColor(vehicle.status).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  _getVehicleIcon(vehicle.type),
                  color: _getStatusColor(vehicle.status),
                  size: 28,
                ),
              ),
              const SizedBox(width: 16),

              // Vehicle info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      vehicle.plateNumber,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      vehicle.displayName,
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 14,
                      ),
                    ),
                    if (vehicle.currentMileage != null) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(
                            Icons.speed,
                            size: 14,
                            color: Colors.grey.shade500,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '${_formatNumber(vehicle.currentMileage!)} km',
                            style: TextStyle(
                              color: Colors.grey.shade500,
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),

              // Status badge & arrow
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: _getStatusColor(vehicle.status).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      vehicle.statusLabel,
                      style: TextStyle(
                        color: _getStatusColor(vehicle.status),
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                  if (vehicle.hasAlerts) ...[
                    const SizedBox(height: 8),
                    const Icon(
                      Icons.warning_amber_rounded,
                      color: AppTheme.warningColor,
                      size: 20,
                    ),
                  ],
                ],
              ),
              const SizedBox(width: 8),
              Icon(
                Icons.chevron_right,
                color: Colors.grey.shade400,
              ),
            ],
          ),
        ),
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
      case 'sold':
        return Colors.red;
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
      case 'motorcycle':
        return Icons.two_wheeler;
      case 'bus':
        return Icons.directions_bus;
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

/// Filters bottom sheet
class _FiltersBottomSheet extends StatefulWidget {
  final String? selectedStatus;
  final String? selectedType;
  final Function(String?, String?) onApply;
  final VoidCallback onClear;

  const _FiltersBottomSheet({
    this.selectedStatus,
    this.selectedType,
    required this.onApply,
    required this.onClear,
  });

  @override
  State<_FiltersBottomSheet> createState() => _FiltersBottomSheetState();
}

class _FiltersBottomSheetState extends State<_FiltersBottomSheet> {
  late String? _status;
  late String? _type;

  @override
  void initState() {
    super.initState();
    _status = widget.selectedStatus;
    _type = widget.selectedType;
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Filtre',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
              TextButton(
                onPressed: widget.onClear,
                child: const Text('Resetează'),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Status filter
          const Text(
            'Status',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              _FilterChip(
                label: 'Activ',
                selected: _status == 'active',
                onSelected: (selected) {
                  setState(() => _status = selected ? 'active' : null);
                },
              ),
              _FilterChip(
                label: 'Inactiv',
                selected: _status == 'inactive',
                onSelected: (selected) {
                  setState(() => _status = selected ? 'inactive' : null);
                },
              ),
              _FilterChip(
                label: 'În service',
                selected: _status == 'maintenance',
                onSelected: (selected) {
                  setState(() => _status = selected ? 'maintenance' : null);
                },
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Type filter
          const Text(
            'Tip vehicul',
            style: TextStyle(fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              _FilterChip(
                label: 'Autoturism',
                selected: _type == 'car',
                onSelected: (selected) {
                  setState(() => _type = selected ? 'car' : null);
                },
              ),
              _FilterChip(
                label: 'Dubă',
                selected: _type == 'van',
                onSelected: (selected) {
                  setState(() => _type = selected ? 'van' : null);
                },
              ),
              _FilterChip(
                label: 'Camion',
                selected: _type == 'truck',
                onSelected: (selected) {
                  setState(() => _type = selected ? 'truck' : null);
                },
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Apply button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => widget.onApply(_status, _type),
              child: const Text('Aplică filtre'),
            ),
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final Function(bool) onSelected;

  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    return FilterChip(
      label: Text(label),
      selected: selected,
      onSelected: onSelected,
      selectedColor: AppTheme.primaryColor.withValues(alpha: 0.2),
      checkmarkColor: AppTheme.primaryColor,
    );
  }
}
