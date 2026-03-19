import 'package:dartz/dartz.dart' hide State;
import 'package:fleetly_mobile/features/maintenance/data/maintenance_repository.dart';
import 'package:fleetly_mobile/features/maintenance/data/models/maintenance_model.dart';
import 'package:fleetly_mobile/features/maintenance/presentation/providers/maintenance_provider.dart';
import 'package:fleetly_mobile/features/vehicles/data/vehicles_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

/// Maintenance form screen - Add/Edit
class MaintenanceFormScreen extends ConsumerStatefulWidget {
  final int? maintenanceId;

  const MaintenanceFormScreen({
    super.key,
    this.maintenanceId,
  });

  @override
  ConsumerState<MaintenanceFormScreen> createState() => _MaintenanceFormScreenState();
}

class _MaintenanceFormScreenState extends ConsumerState<MaintenanceFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _dateFormat = DateFormat('dd.MM.yyyy');

  bool _isLoading = false;
  bool _isInitialized = false;

  // Form controllers
  final _descriptionController = TextEditingController();
  final _mileageController = TextEditingController();
  final _costController = TextEditingController();
  final _serviceProviderController = TextEditingController();
  final _invoiceNumberController = TextEditingController();
  final _nextMileageController = TextEditingController();
  final _notesController = TextEditingController();

  // Form values
  int? _vehicleId;
  String _type = 'scheduled';
  DateTime _date = DateTime.now();
  DateTime? _nextServiceDate;
  String _status = 'completed';

  // Vehicles list
  List<Map<String, dynamic>> _vehicles = [];
  bool _vehiclesLoading = true;

  bool get isEdit => widget.maintenanceId != null;

  @override
  void initState() {
    super.initState();
    _loadVehicles();
  }

  Future<void> _loadVehicles() async {
    try {
      final repository = ref.read(vehiclesRepositoryProvider);
      final result = await repository.getVehicles(perPage: 100);
      result.fold(
        (failure) {
          setState(() {
            _vehiclesLoading = false;
          });
        },
        (response) {
          setState(() {
            _vehicles = response.vehicles.map((v) => {
              'id': v.id,
              'plate': v.plateNumber,
            }).toList();
            _vehiclesLoading = false;
          });
        },
      );
    } catch (e) {
      setState(() {
        _vehiclesLoading = false;
      });
    }
  }

  void _initializeWithMaintenance(Maintenance maintenance) {
    if (_isInitialized) return;
    _isInitialized = true;

    _vehicleId = maintenance.vehicleId;
    _type = maintenance.type;
    _date = maintenance.date;
    _descriptionController.text = maintenance.description ?? '';
    _mileageController.text = maintenance.mileage?.toString() ?? '';
    _costController.text = maintenance.cost?.toString() ?? '';
    _serviceProviderController.text = maintenance.serviceProvider ?? '';
    _invoiceNumberController.text = maintenance.invoiceNumber ?? '';
    _nextServiceDate = maintenance.nextServiceDate;
    _nextMileageController.text = maintenance.nextServiceMileage?.toString() ?? '';
    _status = maintenance.status ?? 'completed';
    _notesController.text = maintenance.notes ?? '';
  }

  @override
  void dispose() {
    _descriptionController.dispose();
    _mileageController.dispose();
    _costController.dispose();
    _serviceProviderController.dispose();
    _invoiceNumberController.dispose();
    _nextMileageController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context, bool isNextService) async {
    final initialDate = isNextService 
        ? (_nextServiceDate ?? DateTime.now().add(const Duration(days: 90)))
        : _date;

    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: isNextService ? DateTime.now() : DateTime(2000),
      lastDate: DateTime(2100),
    );

    if (picked != null) {
      setState(() {
        if (isNextService) {
          _nextServiceDate = picked;
        } else {
          _date = picked;
        }
      });
    }
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    if (_vehicleId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Selectați un vehicul'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final data = {
        'vehicle_id': _vehicleId,
        'type': _type,
        'description': _descriptionController.text.isEmpty 
            ? null 
            : _descriptionController.text,
        'date': _date.toIso8601String().split('T').first,
        'mileage': _mileageController.text.isEmpty 
            ? null 
            : int.parse(_mileageController.text),
        'cost': _costController.text.isEmpty 
            ? null 
            : double.parse(_costController.text),
        'service_provider': _serviceProviderController.text.isEmpty 
            ? null 
            : _serviceProviderController.text,
        'invoice_number': _invoiceNumberController.text.isEmpty 
            ? null 
            : _invoiceNumberController.text,
        'next_service_date': _nextServiceDate?.toIso8601String().split('T').first,
        'next_service_mileage': _nextMileageController.text.isEmpty 
            ? null 
            : int.parse(_nextMileageController.text),
        'status': _status,
        'notes': _notesController.text.isEmpty 
            ? null 
            : _notesController.text,
      };

      // Remove null values
      data.removeWhere((key, value) => value == null);

      final repository = ref.read(maintenanceRepositoryProvider);

      if (isEdit) {
        await repository.updateMaintenance(widget.maintenanceId!, data);
      } else {
        await repository.createMaintenance(data);
      }

      // Refresh list
      ref.read(maintenanceListProvider.notifier).loadMaintenance(refresh: true);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isEdit 
                ? 'Înregistrare actualizată' 
                : 'Înregistrare adăugată'),
            backgroundColor: Colors.green,
          ),
        );
        context.pop();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Eroare: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    // Load existing data for edit
    if (isEdit && !_isInitialized) {
      final maintenanceAsync = ref.watch(maintenanceDetailProvider(widget.maintenanceId!));
      return maintenanceAsync.when(
        loading: () => Scaffold(
          appBar: AppBar(title: const Text('Editare mentenanță')),
          body: const Center(child: CircularProgressIndicator()),
        ),
        error: (e, _) => Scaffold(
          appBar: AppBar(title: const Text('Editare mentenanță')),
          body: Center(child: Text('Eroare: $e')),
        ),
        data: (maintenance) {
          _initializeWithMaintenance(maintenance);
          return _buildForm();
        },
      );
    }

    return _buildForm();
  }

  Widget _buildForm() {
    return Scaffold(
      appBar: AppBar(
        title: Text(isEdit ? 'Editare mentenanță' : 'Adaugă mentenanță'),
        actions: [
          if (_isLoading)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: SizedBox(
                  width: 20,
                  height: 20,
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
            )
          else
            TextButton(
              onPressed: _save,
              child: const Text('Salvează'),
            ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Vehicle selector
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Vehicul *',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _vehiclesLoading
                        ? const Center(child: CircularProgressIndicator())
                        : DropdownButtonFormField<int>(
                            value: _vehicleId,
                            decoration: const InputDecoration(
                              border: OutlineInputBorder(),
                              hintText: 'Selectați vehiculul',
                            ),
                            items: _vehicles.map((v) {
                              return DropdownMenuItem<int>(
                                value: v['id'] as int,
                                child: Text(v['plate'] as String),
                              );
                            }).toList(),
                            onChanged: (value) {
                              setState(() => _vehicleId = value);
                            },
                            validator: (value) {
                              if (value == null) {
                                return 'Selectați un vehicul';
                              }
                              return null;
                            },
                          ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Type & Status
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Tip lucrare *',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: _type,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'scheduled', child: Text('Revizie programată')),
                        DropdownMenuItem(value: 'oil_change', child: Text('Schimb ulei')),
                        DropdownMenuItem(value: 'tire_change', child: Text('Schimb anvelope')),
                        DropdownMenuItem(value: 'brake_service', child: Text('Frâne')),
                        DropdownMenuItem(value: 'engine', child: Text('Motor')),
                        DropdownMenuItem(value: 'transmission', child: Text('Transmisie')),
                        DropdownMenuItem(value: 'electrical', child: Text('Sistem electric')),
                        DropdownMenuItem(value: 'suspension', child: Text('Suspensie')),
                        DropdownMenuItem(value: 'air_conditioning', child: Text('Aer condiționat')),
                        DropdownMenuItem(value: 'inspection', child: Text('Inspecție')),
                        DropdownMenuItem(value: 'repair', child: Text('Reparație')),
                        DropdownMenuItem(value: 'other', child: Text('Altele')),
                      ],
                      onChanged: (value) {
                        setState(() => _type = value!);
                      },
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Status',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: _status,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'scheduled', child: Text('Programat')),
                        DropdownMenuItem(value: 'in_progress', child: Text('În lucru')),
                        DropdownMenuItem(value: 'completed', child: Text('Finalizat')),
                        DropdownMenuItem(value: 'cancelled', child: Text('Anulat')),
                      ],
                      onChanged: (value) {
                        setState(() => _status = value!);
                      },
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Date & Mileage
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Data și kilometraj',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: () => _selectDate(context, false),
                      child: InputDecorator(
                        decoration: const InputDecoration(
                          border: OutlineInputBorder(),
                          labelText: 'Data service *',
                          suffixIcon: Icon(Icons.calendar_today),
                        ),
                        child: Text(_dateFormat.format(_date)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _mileageController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Kilometraj',
                        suffixText: 'km',
                      ),
                      keyboardType: TextInputType.number,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Description
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Descriere',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _descriptionController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Descriere lucrare',
                        alignLabelWithHint: true,
                      ),
                      maxLines: 3,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Cost & Provider
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Cost și furnizor',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _costController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Cost',
                        suffixText: 'RON',
                      ),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _serviceProviderController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Furnizor service',
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _invoiceNumberController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Număr factură',
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Next service
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Următorul service',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: () => _selectDate(context, true),
                      child: InputDecorator(
                        decoration: InputDecoration(
                          border: const OutlineInputBorder(),
                          labelText: 'Data următorului service',
                          suffixIcon: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (_nextServiceDate != null)
                                IconButton(
                                  icon: const Icon(Icons.clear),
                                  onPressed: () {
                                    setState(() => _nextServiceDate = null);
                                  },
                                ),
                              const Icon(Icons.calendar_today),
                            ],
                          ),
                        ),
                        child: Text(
                          _nextServiceDate != null
                              ? _dateFormat.format(_nextServiceDate!)
                              : 'Selectați data',
                          style: TextStyle(
                            color: _nextServiceDate != null ? null : Colors.grey,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _nextMileageController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Kilometraj următorul service',
                        suffixText: 'km',
                      ),
                      keyboardType: TextInputType.number,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Notes
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Note',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _notesController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Note adiționale',
                        alignLabelWithHint: true,
                      ),
                      maxLines: 3,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 32),

            // Save button
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _save,
                child: _isLoading
                    ? const CircularProgressIndicator()
                    : Text(isEdit ? 'Actualizează' : 'Salvează'),
              ),
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }
}
