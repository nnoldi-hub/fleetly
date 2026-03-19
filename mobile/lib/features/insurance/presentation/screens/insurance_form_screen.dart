import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/insurance/data/models/insurance_model.dart';
import 'package:fleetly_mobile/features/insurance/presentation/providers/insurance_provider.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/providers/vehicles_provider.dart';

/// Insurance form screen for create/edit
class InsuranceFormScreen extends ConsumerStatefulWidget {
  final int? insuranceId;

  const InsuranceFormScreen({super.key, this.insuranceId});

  bool get isEditing => insuranceId != null;

  @override
  ConsumerState<InsuranceFormScreen> createState() => _InsuranceFormScreenState();
}

class _InsuranceFormScreenState extends ConsumerState<InsuranceFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _policyNumberController = TextEditingController();
  final _providerController = TextEditingController();
  final _premiumController = TextEditingController();
  final _coverageController = TextEditingController();
  final _notesController = TextEditingController();

  InsuranceType _selectedType = InsuranceType.rca;
  int? _selectedVehicleId;
  DateTime _startDate = DateTime.now();
  DateTime _endDate = DateTime.now().add(const Duration(days: 365));

  bool _isLoading = false;
  bool _isInitialized = false;

  @override
  void initState() {
    super.initState();
    // Vehicles will be loaded by the provider
  }

  @override
  void dispose() {
    _policyNumberController.dispose();
    _providerController.dispose();
    _premiumController.dispose();
    _coverageController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _initializeFromInsurance(Insurance insurance) {
    if (_isInitialized) return;
    _isInitialized = true;

    _selectedType = insurance.type;
    _policyNumberController.text = insurance.policyNumber ?? '';
    _providerController.text = insurance.provider ?? '';
    _premiumController.text = insurance.premium?.toString() ?? '';
    _coverageController.text = insurance.coverageAmount?.toString() ?? '';
    _notesController.text = insurance.notes ?? '';
    _selectedVehicleId = insurance.vehicle?.id;
    _startDate = insurance.startDate;
    _endDate = insurance.endDate;
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final vehiclesState = ref.watch(vehiclesProvider);

    // If editing, load the insurance data
    if (widget.isEditing) {
      final insuranceState = ref.watch(insuranceDetailProvider(widget.insuranceId!));
      if (insuranceState.insurance != null) {
        _initializeFromInsurance(insuranceState.insurance!);
      }
    }

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.isEditing ? 'Editează Asigurare' : 'Asigurare Nouă'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Vehicle selector
            _buildVehicleDropdown(vehiclesState),
            const SizedBox(height: 16),

            // Insurance type
            _buildTypeDropdown(),
            const SizedBox(height: 16),

            // Policy number
            TextFormField(
              controller: _policyNumberController,
              decoration: const InputDecoration(
                labelText: 'Număr Poliță',
                hintText: 'Ex: RCA-123456',
                prefixIcon: Icon(Icons.confirmation_number),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),

            // Provider
            TextFormField(
              controller: _providerController,
              decoration: const InputDecoration(
                labelText: 'Asigurător',
                hintText: 'Ex: Allianz, Omniasig, etc.',
                prefixIcon: Icon(Icons.business),
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),

            // Date pickers row
            Row(
              children: [
                Expanded(
                  child: _buildDateField(
                    label: 'Data Început',
                    value: _startDate,
                    onChanged: (date) {
                      setState(() {
                        _startDate = date;
                        if (_endDate.isBefore(_startDate)) {
                          _endDate = _startDate.add(const Duration(days: 365));
                        }
                      });
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _buildDateField(
                    label: 'Data Sfârșit',
                    value: _endDate,
                    onChanged: (date) => setState(() => _endDate = date),
                    firstDate: _startDate,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Premium
            TextFormField(
              controller: _premiumController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Primă Asigurare (RON)',
                hintText: '0.00',
                prefixIcon: Icon(Icons.payments),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value != null && value.isNotEmpty) {
                  if (double.tryParse(value) == null) {
                    return 'Introduceți o sumă validă';
                  }
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Coverage amount
            TextFormField(
              controller: _coverageController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(
                labelText: 'Sumă Asigurată (RON)',
                hintText: '0.00',
                prefixIcon: Icon(Icons.account_balance_wallet),
                border: OutlineInputBorder(),
              ),
              validator: (value) {
                if (value != null && value.isNotEmpty) {
                  if (double.tryParse(value) == null) {
                    return 'Introduceți o sumă validă';
                  }
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Notes
            TextFormField(
              controller: _notesController,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Observații',
                hintText: 'Note adiționale...',
                prefixIcon: Icon(Icons.notes),
                border: OutlineInputBorder(),
                alignLabelWithHint: true,
              ),
            ),
            const SizedBox(height: 24),

            // Submit button
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _submitForm,
                child: _isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(widget.isEditing ? 'Salvează' : 'Adaugă Asigurare'),
              ),
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildVehicleDropdown(VehiclesState vehiclesState) {
    if (vehiclesState.isLoading && vehiclesState.vehicles.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    return DropdownButtonFormField<int>(
      value: _selectedVehicleId,
      decoration: const InputDecoration(
        labelText: 'Vehicul *',
        prefixIcon: Icon(Icons.directions_car),
        border: OutlineInputBorder(),
      ),
      hint: const Text('Selectează vehicul'),
      items: vehiclesState.vehicles.map((vehicle) {
        return DropdownMenuItem<int>(
          value: vehicle.id,
          child: Text('${vehicle.plateNumber} - ${vehicle.brand} ${vehicle.model}'),
        );
      }).toList(),
      onChanged: (value) => setState(() => _selectedVehicleId = value),
      validator: (value) {
        if (value == null) return 'Selectați un vehicul';
        return null;
      },
    );
  }

  Widget _buildTypeDropdown() {
    return DropdownButtonFormField<InsuranceType>(
      value: _selectedType,
      decoration: const InputDecoration(
        labelText: 'Tip Asigurare *',
        prefixIcon: Icon(Icons.category),
        border: OutlineInputBorder(),
      ),
      items: InsuranceType.values.map((type) {
        return DropdownMenuItem<InsuranceType>(
          value: type,
          child: Text(type.label),
        );
      }).toList(),
      onChanged: (value) {
        if (value != null) {
          setState(() => _selectedType = value);
        }
      },
    );
  }

  Widget _buildDateField({
    required String label,
    required DateTime value,
    required ValueChanged<DateTime> onChanged,
    DateTime? firstDate,
  }) {
    final dateFormat = DateFormat('dd/MM/yyyy');

    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: value,
          firstDate: firstDate ?? DateTime(2020),
          lastDate: DateTime(2100),
        );
        if (picked != null) {
          onChanged(picked);
        }
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: const Icon(Icons.calendar_today),
          border: const OutlineInputBorder(),
        ),
        child: Text(dateFormat.format(value)),
      ),
    );
  }

  Future<void> _submitForm() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final data = {
      'vehicle_id': _selectedVehicleId,
      'insurance_type': _selectedType.value,
      'policy_number': _policyNumberController.text.trim(),
      'provider': _providerController.text.trim(),
      'start_date': _startDate.toIso8601String().split('T').first,
      'end_date': _endDate.toIso8601String().split('T').first,
      'premium': _premiumController.text.isNotEmpty
          ? double.tryParse(_premiumController.text)
          : null,
      'coverage_amount': _coverageController.text.isNotEmpty
          ? double.tryParse(_coverageController.text)
          : null,
      'notes': _notesController.text.trim(),
    };

    final repository = ref.read(insuranceRepositoryProvider);

    final result = widget.isEditing
        ? await repository.updateInsurance(widget.insuranceId!, data)
        : await repository.createInsurance(data);

    setState(() => _isLoading = false);

    result.fold(
      (error) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Eroare: $error'),
            backgroundColor: Colors.red,
          ),
        );
      },
      (insurance) {
        // Refresh the list
        ref.read(insuranceListProvider.notifier).refresh();

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(widget.isEditing
                ? 'Asigurare actualizată'
                : 'Asigurare adăugată'),
            backgroundColor: Colors.green,
          ),
        );

        if (context.mounted) {
          context.pop();
        }
      },
    );
  }
}
