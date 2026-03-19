import 'package:dartz/dartz.dart' hide State;
import 'package:fleetly_mobile/features/fuel/data/fuel_repository.dart';
import 'package:fleetly_mobile/features/fuel/data/models/fuel_model.dart';
import 'package:fleetly_mobile/features/fuel/presentation/providers/fuel_provider.dart';
import 'package:fleetly_mobile/features/vehicles/data/vehicles_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

/// Fuel form screen - Add/Edit
class FuelFormScreen extends ConsumerStatefulWidget {
  final int? fuelId;

  const FuelFormScreen({
    super.key,
    this.fuelId,
  });

  @override
  ConsumerState<FuelFormScreen> createState() => _FuelFormScreenState();
}

class _FuelFormScreenState extends ConsumerState<FuelFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _dateFormat = DateFormat('dd.MM.yyyy');

  bool _isLoading = false;
  bool _isInitialized = false;

  // Form controllers
  final _quantityController = TextEditingController();
  final _pricePerUnitController = TextEditingController();
  final _totalCostController = TextEditingController();
  final _mileageController = TextEditingController();
  final _stationController = TextEditingController();
  final _receiptNumberController = TextEditingController();
  final _notesController = TextEditingController();

  // Form values
  int? _vehicleId;
  String _fuelType = 'diesel';
  DateTime _date = DateTime.now();
  bool _fullTank = false;
  bool _autoCalculate = true;

  // Vehicles list
  List<Map<String, dynamic>> _vehicles = [];
  bool _vehiclesLoading = true;

  bool get isEdit => widget.fuelId != null;

  @override
  void initState() {
    super.initState();
    _loadVehicles();
    _quantityController.addListener(_calculateTotal);
    _pricePerUnitController.addListener(_calculateTotal);
  }

  void _calculateTotal() {
    if (!_autoCalculate) return;
    final quantity = double.tryParse(_quantityController.text) ?? 0;
    final pricePerUnit = double.tryParse(_pricePerUnitController.text) ?? 0;
    if (quantity > 0 && pricePerUnit > 0) {
      final total = quantity * pricePerUnit;
      _totalCostController.text = total.toStringAsFixed(2);
    }
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
              'fuelType': v.fuelType,
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

  void _initializeWithFuel(FuelRecord fuel) {
    if (_isInitialized) return;
    _isInitialized = true;

    _vehicleId = fuel.vehicleId;
    _fuelType = fuel.fuelType;
    _date = fuel.date;
    _fullTank = fuel.fullTank;
    _quantityController.text = fuel.quantity.toString();
    _pricePerUnitController.text = fuel.pricePerUnit.toString();
    _totalCostController.text = fuel.totalCost.toString();
    _mileageController.text = fuel.mileage?.toString() ?? '';
    _stationController.text = fuel.station ?? '';
    _receiptNumberController.text = fuel.receiptNumber ?? '';
    _notesController.text = fuel.notes ?? '';
  }

  @override
  void dispose() {
    _quantityController.dispose();
    _pricePerUnitController.dispose();
    _totalCostController.dispose();
    _mileageController.dispose();
    _stationController.dispose();
    _receiptNumberController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(2000),
      lastDate: DateTime.now(),
    );

    if (picked != null) {
      setState(() {
        _date = picked;
      });
    }
  }

  void _onVehicleChanged(int? vehicleId) {
    setState(() {
      _vehicleId = vehicleId;
      // Auto-set fuel type based on vehicle
      if (vehicleId != null) {
        final vehicle = _vehicles.firstWhere(
          (v) => v['id'] == vehicleId,
          orElse: () => {},
        );
        if (vehicle.isNotEmpty && vehicle['fuelType'] != null) {
          _fuelType = vehicle['fuelType'];
        }
      }
    });
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
        'fuel_type': _fuelType,
        'date': _date.toIso8601String().split('T').first,
        'quantity': double.parse(_quantityController.text),
        'price_per_unit': double.parse(_pricePerUnitController.text),
        'total_cost': double.parse(_totalCostController.text),
        'full_tank': _fullTank,
        'mileage': _mileageController.text.isEmpty 
            ? null 
            : int.parse(_mileageController.text),
        'station': _stationController.text.isEmpty 
            ? null 
            : _stationController.text,
        'receipt_number': _receiptNumberController.text.isEmpty 
            ? null 
            : _receiptNumberController.text,
        'notes': _notesController.text.isEmpty 
            ? null 
            : _notesController.text,
      };

      // Remove null values
      data.removeWhere((key, value) => value == null);

      final repository = ref.read(fuelRepositoryProvider);

      if (isEdit) {
        await repository.updateFuelRecord(widget.fuelId!, data);
      } else {
        await repository.createFuelRecord(data);
      }

      // Refresh list
      ref.read(fuelListProvider.notifier).loadFuel(refresh: true);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isEdit 
                ? 'Alimentare actualizată' 
                : 'Alimentare adăugată'),
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
      final fuelAsync = ref.watch(fuelDetailProvider(widget.fuelId!));
      return fuelAsync.when(
        loading: () => Scaffold(
          appBar: AppBar(title: const Text('Editare alimentare')),
          body: const Center(child: CircularProgressIndicator()),
        ),
        error: (e, _) => Scaffold(
          appBar: AppBar(title: const Text('Editare alimentare')),
          body: Center(child: Text('Eroare: $e')),
        ),
        data: (fuel) {
          _initializeWithFuel(fuel);
          return _buildForm();
        },
      );
    }

    return _buildForm();
  }

  Widget _buildForm() {
    return Scaffold(
      appBar: AppBar(
        title: Text(isEdit ? 'Editare alimentare' : 'Adaugă alimentare'),
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
                            onChanged: _onVehicleChanged,
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

            // Fuel type & Date
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Tip combustibil *',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      value: _fuelType,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'diesel', child: Text('Motorină')),
                        DropdownMenuItem(value: 'gasoline', child: Text('Benzină')),
                        DropdownMenuItem(value: 'lpg', child: Text('GPL')),
                        DropdownMenuItem(value: 'cng', child: Text('GNC')),
                        DropdownMenuItem(value: 'electric', child: Text('Electric')),
                      ],
                      onChanged: (value) {
                        setState(() => _fuelType = value!);
                      },
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Data alimentării *',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: () => _selectDate(context),
                      child: InputDecorator(
                        decoration: const InputDecoration(
                          border: OutlineInputBorder(),
                          suffixIcon: Icon(Icons.calendar_today),
                        ),
                        child: Text(_dateFormat.format(_date)),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Quantity & Prices
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Cantitate și preț',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _quantityController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Cantitate *',
                        suffixText: 'L',
                      ),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Introduceți cantitatea';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Valoare invalidă';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _pricePerUnitController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Preț/litru *',
                        suffixText: 'RON/L',
                      ),
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      validator: (value) {
                        if (value == null || value.isEmpty) {
                          return 'Introduceți prețul';
                        }
                        if (double.tryParse(value) == null) {
                          return 'Valoare invalidă';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _totalCostController,
                            decoration: const InputDecoration(
                              border: OutlineInputBorder(),
                              labelText: 'Total *',
                              suffixText: 'RON',
                            ),
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                            validator: (value) {
                              if (value == null || value.isEmpty) {
                                return 'Total necesar';
                              }
                              if (double.tryParse(value) == null) {
                                return 'Valoare invalidă';
                              }
                              return null;
                            },
                            onChanged: (_) {
                              _autoCalculate = false;
                            },
                          ),
                        ),
                        const SizedBox(width: 8),
                        IconButton(
                          onPressed: () {
                            setState(() {
                              _autoCalculate = true;
                              _calculateTotal();
                            });
                          },
                          icon: Icon(
                            Icons.calculate,
                            color: _autoCalculate ? Colors.blue : Colors.grey,
                          ),
                          tooltip: 'Calculează automat',
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    SwitchListTile(
                      title: const Text('Rezervor plin'),
                      value: _fullTank,
                      onChanged: (value) {
                        setState(() => _fullTank = value);
                      },
                      contentPadding: EdgeInsets.zero,
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Additional info
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Informații suplimentare',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _mileageController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Kilometraj',
                        suffixText: 'km',
                      ),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _stationController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Stație/Benzinărie',
                        prefixIcon: Icon(Icons.place),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _receiptNumberController,
                      decoration: const InputDecoration(
                        border: OutlineInputBorder(),
                        labelText: 'Număr bon/factură',
                        prefixIcon: Icon(Icons.receipt),
                      ),
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
