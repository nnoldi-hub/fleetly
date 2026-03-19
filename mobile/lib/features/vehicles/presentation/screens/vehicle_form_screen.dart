import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:fleetly_mobile/features/vehicles/data/models/vehicle_model.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/providers/vehicles_provider.dart';

class VehicleFormScreen extends ConsumerStatefulWidget {
  final Vehicle? vehicle; // null for create, non-null for edit

  const VehicleFormScreen({
    super.key,
    this.vehicle,
  });

  @override
  ConsumerState<VehicleFormScreen> createState() => _VehicleFormScreenState();
}

class _VehicleFormScreenState extends ConsumerState<VehicleFormScreen> {
  final _formKey = GlobalKey<FormState>();
  
  late final TextEditingController _plateNumberController;
  late final TextEditingController _brandController;
  late final TextEditingController _modelController;
  late final TextEditingController _yearController;
  late final TextEditingController _vinController;
  late final TextEditingController _mileageController;
  late final TextEditingController _colorController;
  
  String? _selectedType;
  String? _selectedFuelType;
  String? _selectedStatus;

  bool get isEditing => widget.vehicle != null;

  @override
  void initState() {
    super.initState();
    final v = widget.vehicle;
    
    _plateNumberController = TextEditingController(text: v?.plateNumber ?? '');
    _brandController = TextEditingController(text: v?.brand ?? '');
    _modelController = TextEditingController(text: v?.model ?? '');
    _yearController = TextEditingController(text: v?.year?.toString() ?? '');
    _vinController = TextEditingController(text: v?.vin ?? '');
    _mileageController = TextEditingController(text: v?.currentMileage?.toString() ?? '');
    _colorController = TextEditingController(text: v?.color ?? '');
    
    _selectedType = v?.type ?? 'car';
    _selectedFuelType = v?.fuelType ?? 'diesel';
    _selectedStatus = v?.status ?? 'active';
  }

  @override
  void dispose() {
    _plateNumberController.dispose();
    _brandController.dispose();
    _modelController.dispose();
    _yearController.dispose();
    _vinController.dispose();
    _mileageController.dispose();
    _colorController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final data = {
      'plate_number': _plateNumberController.text.trim().toUpperCase(),
      'brand': _brandController.text.trim(),
      'model': _modelController.text.trim(),
      'type': _selectedType,
      'fuel_type': _selectedFuelType,
      'status': _selectedStatus,
    };

    if (_yearController.text.isNotEmpty) {
      data['year'] = _yearController.text.trim();
    }
    if (_vinController.text.isNotEmpty) {
      data['vin'] = _vinController.text.trim().toUpperCase();
    }
    if (_mileageController.text.isNotEmpty) {
      data['current_mileage'] = _mileageController.text.trim();
    }
    if (_colorController.text.isNotEmpty) {
      data['color'] = _colorController.text.trim();
    }

    bool success;
    if (isEditing) {
      success = await ref.read(vehicleFormProvider.notifier).updateVehicle(widget.vehicle!.id, data);
    } else {
      success = await ref.read(vehicleFormProvider.notifier).createVehicle(data);
    }

    if (success && mounted) {
      final savedVehicle = ref.read(vehicleFormProvider).savedVehicle;
      if (savedVehicle != null) {
        if (isEditing) {
          ref.read(vehiclesProvider.notifier).updateVehicleInList(savedVehicle);
        } else {
          ref.read(vehiclesProvider.notifier).addVehicle(savedVehicle);
        }
      }
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isEditing ? 'Vehicul actualizat' : 'Vehicul adăugat'),
        ),
      );
      context.pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final formState = ref.watch(vehicleFormProvider);

    // Show error if any
    ref.listen<VehicleFormState>(vehicleFormProvider, (prev, next) {
      if (next.error != null && prev?.error != next.error) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(next.error!),
            backgroundColor: Colors.red,
          ),
        );
      }
    });

    return Scaffold(
      appBar: AppBar(
        title: Text(isEditing ? 'Editare vehicul' : 'Vehicul nou'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Plate number
            TextFormField(
              controller: _plateNumberController,
              decoration: const InputDecoration(
                labelText: 'Număr înmatriculare *',
                hintText: 'Ex: B 123 ABC',
                prefixIcon: Icon(Icons.confirmation_number),
              ),
              textCapitalization: TextCapitalization.characters,
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'Numărul de înmatriculare este obligatoriu';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Brand and model in one row
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _brandController,
                    decoration: const InputDecoration(
                      labelText: 'Marca *',
                      hintText: 'Ex: Volkswagen',
                    ),
                    textCapitalization: TextCapitalization.words,
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Marca este obligatorie';
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextFormField(
                    controller: _modelController,
                    decoration: const InputDecoration(
                      labelText: 'Model *',
                      hintText: 'Ex: Golf',
                    ),
                    textCapitalization: TextCapitalization.words,
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Modelul este obligatoriu';
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Year and mileage
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    controller: _yearController,
                    decoration: const InputDecoration(
                      labelText: 'An fabricație',
                      hintText: 'Ex: 2020',
                    ),
                    keyboardType: TextInputType.number,
                    validator: (value) {
                      if (value != null && value.isNotEmpty) {
                        final year = int.tryParse(value);
                        if (year == null || year < 1900 || year > DateTime.now().year + 1) {
                          return 'An invalid';
                        }
                      }
                      return null;
                    },
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: TextFormField(
                    controller: _mileageController,
                    decoration: const InputDecoration(
                      labelText: 'Kilometraj',
                      hintText: 'Ex: 50000',
                      suffix: Text('km'),
                    ),
                    keyboardType: TextInputType.number,
                    validator: (value) {
                      if (value != null && value.isNotEmpty) {
                        final mileage = int.tryParse(value);
                        if (mileage == null || mileage < 0) {
                          return 'Kilometraj invalid';
                        }
                      }
                      return null;
                    },
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Vehicle type
            DropdownButtonFormField<String>(
              value: _selectedType,
              decoration: const InputDecoration(
                labelText: 'Tip vehicul',
                prefixIcon: Icon(Icons.directions_car),
              ),
              items: const [
                DropdownMenuItem(value: 'car', child: Text('Autoturism')),
                DropdownMenuItem(value: 'van', child: Text('Dubă')),
                DropdownMenuItem(value: 'truck', child: Text('Camion')),
                DropdownMenuItem(value: 'motorcycle', child: Text('Motocicletă')),
                DropdownMenuItem(value: 'bus', child: Text('Autobuz')),
              ],
              onChanged: (value) {
                setState(() => _selectedType = value);
              },
            ),
            const SizedBox(height: 16),

            // Fuel type
            DropdownButtonFormField<String>(
              value: _selectedFuelType,
              decoration: const InputDecoration(
                labelText: 'Combustibil',
                prefixIcon: Icon(Icons.local_gas_station),
              ),
              items: const [
                DropdownMenuItem(value: 'diesel', child: Text('Diesel')),
                DropdownMenuItem(value: 'gasoline', child: Text('Benzină')),
                DropdownMenuItem(value: 'electric', child: Text('Electric')),
                DropdownMenuItem(value: 'hybrid', child: Text('Hibrid')),
                DropdownMenuItem(value: 'lpg', child: Text('GPL')),
              ],
              onChanged: (value) {
                setState(() => _selectedFuelType = value);
              },
            ),
            const SizedBox(height: 16),

            // Status
            DropdownButtonFormField<String>(
              value: _selectedStatus,
              decoration: const InputDecoration(
                labelText: 'Status',
                prefixIcon: Icon(Icons.info_outline),
              ),
              items: const [
                DropdownMenuItem(value: 'active', child: Text('Activ')),
                DropdownMenuItem(value: 'inactive', child: Text('Inactiv')),
                DropdownMenuItem(value: 'maintenance', child: Text('În service')),
                DropdownMenuItem(value: 'sold', child: Text('Vândut')),
              ],
              onChanged: (value) {
                setState(() => _selectedStatus = value);
              },
            ),
            const SizedBox(height: 16),

            // VIN
            TextFormField(
              controller: _vinController,
              decoration: const InputDecoration(
                labelText: 'Serie șasiu (VIN)',
                hintText: 'Ex: WVWZZZ3CZWE123456',
              ),
              textCapitalization: TextCapitalization.characters,
              validator: (value) {
                if (value != null && value.isNotEmpty) {
                  if (value.length != 17) {
                    return 'VIN-ul trebuie să aibă 17 caractere';
                  }
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Color
            TextFormField(
              controller: _colorController,
              decoration: const InputDecoration(
                labelText: 'Culoare',
                hintText: 'Ex: Alb',
                prefixIcon: Icon(Icons.palette),
              ),
              textCapitalization: TextCapitalization.words,
            ),
            const SizedBox(height: 32),

            // Submit button
            SizedBox(
              height: 50,
              child: ElevatedButton(
                onPressed: formState.isSubmitting ? null : _submit,
                child: formState.isSubmitting
                    ? const SizedBox(
                        height: 24,
                        width: 24,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Text(isEditing ? 'Salvează modificările' : 'Adaugă vehicul'),
              ),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }
}
