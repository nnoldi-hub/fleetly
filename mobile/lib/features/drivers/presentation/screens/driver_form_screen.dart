import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/drivers/data/models/driver_model.dart';
import 'package:fleetly_mobile/features/drivers/presentation/providers/drivers_provider.dart';

class DriverFormScreen extends ConsumerStatefulWidget {
  final int? driverId;

  const DriverFormScreen({
    super.key,
    this.driverId,
  });

  @override
  ConsumerState<DriverFormScreen> createState() => _DriverFormScreenState();
}

class _DriverFormScreenState extends ConsumerState<DriverFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _dateFormat = DateFormat('dd.MM.yyyy');

  late TextEditingController _firstNameController;
  late TextEditingController _lastNameController;
  late TextEditingController _emailController;
  late TextEditingController _phoneController;
  late TextEditingController _cnpController;
  late TextEditingController _addressController;
  late TextEditingController _licenseNumberController;
  late TextEditingController _licenseCategoriesController;

  DateTime? _licenseExpiry;
  DateTime? _medicalExpiry;
  DateTime? _hireDate;
  String _status = 'active';

  bool _isLoading = false;
  Driver? _existingDriver;

  bool get isEditing => widget.driverId != null;

  @override
  void initState() {
    super.initState();
    _initControllers();
    if (isEditing) {
      _loadDriver();
    }
  }

  void _initControllers() {
    _firstNameController = TextEditingController();
    _lastNameController = TextEditingController();
    _emailController = TextEditingController();
    _phoneController = TextEditingController();
    _cnpController = TextEditingController();
    _addressController = TextEditingController();
    _licenseNumberController = TextEditingController();
    _licenseCategoriesController = TextEditingController();
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _cnpController.dispose();
    _addressController.dispose();
    _licenseNumberController.dispose();
    _licenseCategoriesController.dispose();
    super.dispose();
  }

  Future<void> _loadDriver() async {
    setState(() => _isLoading = true);
    await ref.read(driverDetailProvider.notifier).loadDriver(widget.driverId!);
    final state = ref.read(driverDetailProvider);
    if (state.driver != null) {
      _populateForm(state.driver!);
    }
    setState(() => _isLoading = false);
  }

  void _populateForm(Driver driver) {
    _existingDriver = driver;
    _firstNameController.text = driver.firstName;
    _lastNameController.text = driver.lastName;
    _emailController.text = driver.email ?? '';
    _phoneController.text = driver.phone ?? '';
    _cnpController.text = driver.cnp ?? '';
    _addressController.text = driver.address ?? '';
    _licenseNumberController.text = driver.licenseNumber ?? '';
    _licenseCategoriesController.text = driver.licenseCategories ?? '';
    _licenseExpiry = driver.licenseExpiry;
    _medicalExpiry = driver.medicalExpiry;
    _hireDate = driver.hireDate;
    _status = driver.status ?? 'active';
    setState(() {});
  }

  Future<void> _selectDate(String field) async {
    DateTime initialDate;
    DateTime firstDate;
    DateTime lastDate;

    switch (field) {
      case 'license_expiry':
        initialDate = _licenseExpiry ?? DateTime.now().add(const Duration(days: 365));
        firstDate = DateTime.now();
        lastDate = DateTime.now().add(const Duration(days: 365 * 20));
        break;
      case 'medical_expiry':
        initialDate = _medicalExpiry ?? DateTime.now().add(const Duration(days: 365));
        firstDate = DateTime.now();
        lastDate = DateTime.now().add(const Duration(days: 365 * 10));
        break;
      case 'hire_date':
        initialDate = _hireDate ?? DateTime.now();
        firstDate = DateTime(2000);
        lastDate = DateTime.now();
        break;
      default:
        return;
    }

    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: firstDate,
      lastDate: lastDate,
    );

    if (picked != null) {
      setState(() {
        switch (field) {
          case 'license_expiry':
            _licenseExpiry = picked;
            break;
          case 'medical_expiry':
            _medicalExpiry = picked;
            break;
          case 'hire_date':
            _hireDate = picked;
            break;
        }
      });
    }
  }

  Future<void> _saveDriver() async {
    if (!_formKey.currentState!.validate()) return;

    final data = {
      'first_name': _firstNameController.text.trim(),
      'last_name': _lastNameController.text.trim(),
      'email': _emailController.text.trim().isEmpty ? null : _emailController.text.trim(),
      'phone': _phoneController.text.trim().isEmpty ? null : _phoneController.text.trim(),
      'cnp': _cnpController.text.trim().isEmpty ? null : _cnpController.text.trim(),
      'address': _addressController.text.trim().isEmpty ? null : _addressController.text.trim(),
      'license_number': _licenseNumberController.text.trim().isEmpty ? null : _licenseNumberController.text.trim(),
      'license_categories': _licenseCategoriesController.text.trim().isEmpty ? null : _licenseCategoriesController.text.trim(),
      'license_expiry': _licenseExpiry?.toIso8601String().split('T').first,
      'medical_expiry': _medicalExpiry?.toIso8601String().split('T').first,
      'hire_date': _hireDate?.toIso8601String().split('T').first,
      'status': _status,
    };

    final formNotifier = ref.read(driverFormProvider.notifier);
    bool success;

    if (isEditing) {
      success = await formNotifier.updateDriver(widget.driverId!, data);
    } else {
      success = await formNotifier.createDriver(data);
    }

    if (success && mounted) {
      final savedDriver = ref.read(driverFormProvider).savedDriver;
      if (savedDriver != null) {
        if (isEditing) {
          ref.read(driversProvider.notifier).updateDriver(savedDriver);
        } else {
          ref.read(driversProvider.notifier).addDriver(savedDriver);
        }
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isEditing ? 'Șofer actualizat cu succes' : 'Șofer adăugat cu succes'),
        ),
      );
      context.pop();
    } else if (mounted) {
      final error = ref.read(driverFormProvider).error;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error ?? 'Eroare la salvarea șoferului'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final formState = ref.watch(driverFormProvider);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(isEditing ? 'Editează Șofer' : 'Adaugă Șofer'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Personal info section
                  _SectionHeader(
                    title: 'Informații Personale',
                    icon: Icons.person,
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _firstNameController,
                          decoration: const InputDecoration(
                            labelText: 'Prenume *',
                            prefixIcon: Icon(Icons.person_outline),
                          ),
                          textCapitalization: TextCapitalization.words,
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Prenumele este obligatoriu';
                            }
                            return null;
                          },
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          controller: _lastNameController,
                          decoration: const InputDecoration(
                            labelText: 'Nume *',
                            prefixIcon: Icon(Icons.person_outline),
                          ),
                          textCapitalization: TextCapitalization.words,
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) {
                              return 'Numele este obligatoriu';
                            }
                            return null;
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _cnpController,
                    decoration: const InputDecoration(
                      labelText: 'CNP',
                      prefixIcon: Icon(Icons.badge),
                    ),
                    keyboardType: TextInputType.number,
                    maxLength: 13,
                  ),
                  const SizedBox(height: 8),
                  _DateField(
                    label: 'Data angajării',
                    value: _hireDate,
                    dateFormat: _dateFormat,
                    onTap: () => _selectDate('hire_date'),
                    onClear: () => setState(() => _hireDate = null),
                    icon: Icons.work,
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: _status,
                    decoration: const InputDecoration(
                      labelText: 'Status',
                      prefixIcon: Icon(Icons.toggle_on),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'active', child: Text('Activ')),
                      DropdownMenuItem(value: 'inactive', child: Text('Inactiv')),
                      DropdownMenuItem(value: 'on_leave', child: Text('În concediu')),
                      DropdownMenuItem(value: 'suspended', child: Text('Suspendat')),
                    ],
                    onChanged: (value) {
                      if (value != null) setState(() => _status = value);
                    },
                  ),
                  const SizedBox(height: 24),

                  // Contact section
                  _SectionHeader(
                    title: 'Contact',
                    icon: Icons.contact_phone,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _phoneController,
                    decoration: const InputDecoration(
                      labelText: 'Telefon',
                      prefixIcon: Icon(Icons.phone),
                    ),
                    keyboardType: TextInputType.phone,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _emailController,
                    decoration: const InputDecoration(
                      labelText: 'Email',
                      prefixIcon: Icon(Icons.email),
                    ),
                    keyboardType: TextInputType.emailAddress,
                    validator: (value) {
                      if (value != null && value.isNotEmpty) {
                        final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
                        if (!emailRegex.hasMatch(value)) {
                          return 'Email invalid';
                        }
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _addressController,
                    decoration: const InputDecoration(
                      labelText: 'Adresă',
                      prefixIcon: Icon(Icons.location_on),
                    ),
                    maxLines: 2,
                  ),
                  const SizedBox(height: 24),

                  // License section
                  _SectionHeader(
                    title: 'Permis de Conducere',
                    icon: Icons.credit_card,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _licenseNumberController,
                    decoration: const InputDecoration(
                      labelText: 'Număr permis',
                      prefixIcon: Icon(Icons.numbers),
                    ),
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _licenseCategoriesController,
                    decoration: const InputDecoration(
                      labelText: 'Categorii (ex: B, C, CE)',
                      prefixIcon: Icon(Icons.category),
                    ),
                    textCapitalization: TextCapitalization.characters,
                  ),
                  const SizedBox(height: 16),
                  _DateField(
                    label: 'Expirare permis',
                    value: _licenseExpiry,
                    dateFormat: _dateFormat,
                    onTap: () => _selectDate('license_expiry'),
                    onClear: () => setState(() => _licenseExpiry = null),
                    icon: Icons.event,
                  ),
                  const SizedBox(height: 16),
                  _DateField(
                    label: 'Expirare fișă medicală',
                    value: _medicalExpiry,
                    dateFormat: _dateFormat,
                    onTap: () => _selectDate('medical_expiry'),
                    onClear: () => setState(() => _medicalExpiry = null),
                    icon: Icons.medical_services,
                  ),
                  const SizedBox(height: 32),

                  // Submit button
                  SizedBox(
                    height: 50,
                    child: ElevatedButton(
                      onPressed: formState.isSubmitting ? null : _saveDriver,
                      child: formState.isSubmitting
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Text(isEditing ? 'Salvează Modificările' : 'Adaugă Șofer'),
                    ),
                  ),
                  const SizedBox(height: 16),
                ],
              ),
            ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  final IconData icon;

  const _SectionHeader({
    required this.title,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      children: [
        Icon(icon, size: 20, color: theme.colorScheme.primary),
        const SizedBox(width: 8),
        Text(
          title,
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: theme.colorScheme.primary,
          ),
        ),
      ],
    );
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final DateTime? value;
  final DateFormat dateFormat;
  final VoidCallback onTap;
  final VoidCallback onClear;
  final IconData icon;

  const _DateField({
    required this.label,
    required this.value,
    required this.dateFormat,
    required this.onTap,
    required this.onClear,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon),
          suffixIcon: value != null
              ? IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: onClear,
                )
              : const Icon(Icons.calendar_today),
        ),
        child: Text(
          value != null ? dateFormat.format(value!) : 'Selectează data',
          style: TextStyle(
            color: value != null
                ? Theme.of(context).colorScheme.onSurface
                : Theme.of(context).hintColor,
          ),
        ),
      ),
    );
  }
}
