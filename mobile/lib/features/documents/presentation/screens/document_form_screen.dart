import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/documents/data/models/document_model.dart';
import 'package:fleetly_mobile/features/documents/presentation/providers/documents_provider.dart';

class DocumentFormScreen extends ConsumerStatefulWidget {
  final int? documentId;

  const DocumentFormScreen({
    super.key,
    this.documentId,
  });

  @override
  ConsumerState<DocumentFormScreen> createState() => _DocumentFormScreenState();
}

class _DocumentFormScreenState extends ConsumerState<DocumentFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _dateFormat = DateFormat('dd.MM.yyyy');

  late TextEditingController _nameController;
  late TextEditingController _descriptionController;
  late TextEditingController _notesController;

  String _type = 'other';
  String _status = 'active';
  DateTime? _issueDate;
  DateTime? _expiryDate;
  int? _vehicleId;
  int? _driverId;

  bool _isLoading = false;
  Document? _existingDocument;

  bool get isEditing => widget.documentId != null;

  final List<Map<String, String>> _documentTypes = [
    {'value': 'itp', 'label': 'ITP'},
    {'value': 'rca', 'label': 'RCA'},
    {'value': 'casco', 'label': 'CASCO'},
    {'value': 'license', 'label': 'Licență'},
    {'value': 'registration', 'label': 'Carte identitate'},
    {'value': 'insurance', 'label': 'Asigurare'},
    {'value': 'contract', 'label': 'Contract'},
    {'value': 'permit', 'label': 'Autorizație'},
    {'value': 'medical', 'label': 'Fișă medicală'},
    {'value': 'other', 'label': 'Altele'},
  ];

  @override
  void initState() {
    super.initState();
    _initControllers();
    if (isEditing) {
      _loadDocument();
    }
  }

  void _initControllers() {
    _nameController = TextEditingController();
    _descriptionController = TextEditingController();
    _notesController = TextEditingController();
  }

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _loadDocument() async {
    setState(() => _isLoading = true);
    await ref.read(documentDetailProvider.notifier).loadDocument(widget.documentId!);
    final state = ref.read(documentDetailProvider);
    if (state.document != null) {
      _populateForm(state.document!);
    }
    setState(() => _isLoading = false);
  }

  void _populateForm(Document document) {
    _existingDocument = document;
    _nameController.text = document.name;
    _descriptionController.text = document.description ?? '';
    _notesController.text = document.notes ?? '';
    _type = document.type;
    _status = document.status ?? 'active';
    _issueDate = document.issueDate;
    _expiryDate = document.expiryDate;
    _vehicleId = document.vehicleId;
    _driverId = document.driverId;
    setState(() {});
  }

  Future<void> _selectDate(String field) async {
    DateTime initialDate;
    DateTime firstDate;
    DateTime lastDate;

    switch (field) {
      case 'issue_date':
        initialDate = _issueDate ?? DateTime.now();
        firstDate = DateTime(2000);
        lastDate = DateTime.now();
        break;
      case 'expiry_date':
        initialDate = _expiryDate ?? DateTime.now().add(const Duration(days: 365));
        firstDate = DateTime.now().subtract(const Duration(days: 365));
        lastDate = DateTime.now().add(const Duration(days: 365 * 20));
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
          case 'issue_date':
            _issueDate = picked;
            break;
          case 'expiry_date':
            _expiryDate = picked;
            break;
        }
      });
    }
  }

  Future<void> _saveDocument() async {
    if (!_formKey.currentState!.validate()) return;

    final data = {
      'name': _nameController.text.trim(),
      'type': _type,
      'description': _descriptionController.text.trim().isEmpty 
          ? null 
          : _descriptionController.text.trim(),
      'notes': _notesController.text.trim().isEmpty 
          ? null 
          : _notesController.text.trim(),
      'status': _status,
      'issue_date': _issueDate?.toIso8601String().split('T').first,
      'expiry_date': _expiryDate?.toIso8601String().split('T').first,
      'vehicle_id': _vehicleId,
      'driver_id': _driverId,
    };

    final formNotifier = ref.read(documentFormProvider.notifier);
    bool success;

    if (isEditing) {
      success = await formNotifier.updateDocument(widget.documentId!, data);
    } else {
      success = await formNotifier.createDocument(data);
    }

    if (success && mounted) {
      final savedDocument = ref.read(documentFormProvider).savedDocument;
      if (savedDocument != null) {
        if (isEditing) {
          ref.read(documentsProvider.notifier).updateDocument(savedDocument);
        } else {
          ref.read(documentsProvider.notifier).addDocument(savedDocument);
        }
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isEditing ? 'Document actualizat cu succes' : 'Document adăugat cu succes'),
        ),
      );
      context.pop();
    } else if (mounted) {
      final error = ref.read(documentFormProvider).error;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error ?? 'Eroare la salvarea documentului'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final formState = ref.watch(documentFormProvider);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(isEditing ? 'Editează Document' : 'Adaugă Document'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  // Basic info section
                  _SectionHeader(
                    title: 'Informații Document',
                    icon: Icons.description,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _nameController,
                    decoration: const InputDecoration(
                      labelText: 'Nume document *',
                      prefixIcon: Icon(Icons.title),
                      hintText: 'ex: ITP 2024',
                    ),
                    textCapitalization: TextCapitalization.sentences,
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Numele documentului este obligatoriu';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    value: _type,
                    decoration: const InputDecoration(
                      labelText: 'Tip document *',
                      prefixIcon: Icon(Icons.category),
                    ),
                    items: _documentTypes.map((type) {
                      return DropdownMenuItem(
                        value: type['value'],
                        child: Text(type['label']!),
                      );
                    }).toList(),
                    onChanged: (value) {
                      if (value != null) setState(() => _type = value);
                    },
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _descriptionController,
                    decoration: const InputDecoration(
                      labelText: 'Descriere',
                      prefixIcon: Icon(Icons.notes),
                    ),
                    maxLines: 2,
                  ),
                  const SizedBox(height: 24),

                  // Dates section
                  _SectionHeader(
                    title: 'Valabilitate',
                    icon: Icons.calendar_today,
                  ),
                  const SizedBox(height: 12),
                  _DateField(
                    label: 'Data emiterii',
                    value: _issueDate,
                    dateFormat: _dateFormat,
                    onTap: () => _selectDate('issue_date'),
                    onClear: () => setState(() => _issueDate = null),
                    icon: Icons.event,
                  ),
                  const SizedBox(height: 16),
                  _DateField(
                    label: 'Data expirării',
                    value: _expiryDate,
                    dateFormat: _dateFormat,
                    onTap: () => _selectDate('expiry_date'),
                    onClear: () => setState(() => _expiryDate = null),
                    icon: Icons.event_busy,
                  ),
                  const SizedBox(height: 24),

                  // Status section
                  _SectionHeader(
                    title: 'Status',
                    icon: Icons.toggle_on,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _status,
                    decoration: const InputDecoration(
                      labelText: 'Status document',
                      prefixIcon: Icon(Icons.flag),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'active', child: Text('Activ')),
                      DropdownMenuItem(value: 'pending', child: Text('În așteptare')),
                      DropdownMenuItem(value: 'expired', child: Text('Expirat')),
                      DropdownMenuItem(value: 'archived', child: Text('Arhivat')),
                    ],
                    onChanged: (value) {
                      if (value != null) setState(() => _status = value);
                    },
                  ),
                  const SizedBox(height: 24),

                  // Notes section
                  _SectionHeader(
                    title: 'Note Adiționale',
                    icon: Icons.note,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _notesController,
                    decoration: const InputDecoration(
                      labelText: 'Note',
                      prefixIcon: Icon(Icons.note_add),
                      hintText: 'Informații suplimentare...',
                    ),
                    maxLines: 3,
                  ),
                  const SizedBox(height: 32),

                  // Submit button
                  SizedBox(
                    height: 50,
                    child: ElevatedButton(
                      onPressed: formState.isSubmitting ? null : _saveDocument,
                      child: formState.isSubmitting
                          ? const SizedBox(
                              width: 24,
                              height: 24,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : Text(isEditing ? 'Salvează Modificările' : 'Adaugă Document'),
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
