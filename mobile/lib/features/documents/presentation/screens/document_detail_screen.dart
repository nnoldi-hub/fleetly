import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/documents/presentation/providers/documents_provider.dart';

class DocumentDetailScreen extends ConsumerStatefulWidget {
  final int documentId;

  const DocumentDetailScreen({
    super.key,
    required this.documentId,
  });

  @override
  ConsumerState<DocumentDetailScreen> createState() => _DocumentDetailScreenState();
}

class _DocumentDetailScreenState extends ConsumerState<DocumentDetailScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(documentDetailProvider.notifier).loadDocument(widget.documentId);
    });
  }

  Future<void> _confirmDelete() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmare ștergere'),
        content: const Text('Sigur dorești să ștergi acest document? Această acțiune nu poate fi anulată.'),
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
      final success = await ref.read(documentDetailProvider.notifier).deleteDocument(widget.documentId);
      if (success && mounted) {
        ref.read(documentsProvider.notifier).removeDocument(widget.documentId);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Document șters cu succes')),
        );
        context.pop();
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Eroare la ștergerea documentului'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(documentDetailProvider);
    final theme = Theme.of(context);
    final dateFormat = DateFormat('dd.MM.yyyy');

    return Scaffold(
      appBar: AppBar(
        title: Text(state.document?.name ?? 'Detalii Document'),
        actions: [
          if (state.document != null) ...[
            IconButton(
              icon: const Icon(Icons.edit),
              onPressed: () => context.push('/documents/${widget.documentId}/edit'),
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

  Widget _buildBody(DocumentDetailState state, ThemeData theme, DateFormat dateFormat) {
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
              'Eroare la încărcarea documentului',
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
              onPressed: () => ref.read(documentDetailProvider.notifier).loadDocument(widget.documentId),
              icon: const Icon(Icons.refresh),
              label: const Text('Reîncearcă'),
            ),
          ],
        ),
      );
    }

    final document = state.document;
    if (document == null) {
      return const Center(child: Text('Document negăsit'));
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(documentDetailProvider.notifier).loadDocument(widget.documentId),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        _DocumentIcon(type: document.type, size: 56),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                document.name,
                                style: theme.textTheme.titleLarge?.copyWith(
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 4),
                              _TypeBadge(type: document.type, label: document.typeLabel),
                            ],
                          ),
                        ),
                      ],
                    ),
                    if (document.description != null) ...[
                      const SizedBox(height: 16),
                      Text(
                        document.description!,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Expiry alert
            if (document.hasAlerts) ...[
              _ExpiryAlert(document: document, dateFormat: dateFormat),
              const SizedBox(height: 16),
            ],

            // Dates section
            _SectionCard(
              title: 'Date Valabilitate',
              icon: Icons.calendar_today,
              children: [
                if (document.issueDate != null)
                  _InfoRow(
                    icon: Icons.event,
                    label: 'Data emiterii',
                    value: dateFormat.format(document.issueDate!),
                  ),
                if (document.expiryDate != null)
                  _InfoRow(
                    icon: Icons.event_busy,
                    label: 'Data expirării',
                    value: dateFormat.format(document.expiryDate!),
                    isAlert: document.hasAlerts,
                  ),
                if (document.daysUntilExpiry != null) ...[
                  const Divider(height: 24),
                  _ExpiryCountdown(daysUntilExpiry: document.daysUntilExpiry!),
                ],
              ],
            ),
            const SizedBox(height: 16),

            // Association section
            if (document.vehiclePlate != null || document.driverName != null)
              _SectionCard(
                title: 'Asociere',
                icon: Icons.link,
                children: [
                  if (document.vehiclePlate != null)
                    _InfoRow(
                      icon: Icons.directions_car,
                      label: 'Vehicul',
                      value: document.vehiclePlate!,
                      onTap: document.vehicleId != null
                          ? () => context.push('/vehicles/${document.vehicleId}')
                          : null,
                    ),
                  if (document.driverName != null)
                    _InfoRow(
                      icon: Icons.person,
                      label: 'Șofer',
                      value: document.driverName!,
                      onTap: document.driverId != null
                          ? () => context.push('/drivers/${document.driverId}')
                          : null,
                    ),
                ],
              ),
            if (document.vehiclePlate != null || document.driverName != null)
              const SizedBox(height: 16),

            // Notes section
            if (document.notes != null && document.notes!.isNotEmpty)
              _SectionCard(
                title: 'Note',
                icon: Icons.note,
                children: [
                  Text(
                    document.notes!,
                    style: theme.textTheme.bodyMedium,
                  ),
                ],
              ),
            if (document.notes != null && document.notes!.isNotEmpty)
              const SizedBox(height: 16),

            // Meta info
            _SectionCard(
              title: 'Informații',
              icon: Icons.info_outline,
              children: [
                _InfoRow(
                  icon: Icons.tag,
                  label: 'Status',
                  value: document.statusLabel,
                ),
                if (document.createdAt != null)
                  _InfoRow(
                    icon: Icons.add_circle_outline,
                    label: 'Creat',
                    value: dateFormat.format(document.createdAt!),
                  ),
                if (document.updatedAt != null)
                  _InfoRow(
                    icon: Icons.update,
                    label: 'Actualizat',
                    value: dateFormat.format(document.updatedAt!),
                  ),
              ],
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }
}

class _DocumentIcon extends StatelessWidget {
  final String type;
  final double size;

  const _DocumentIcon({
    required this.type,
    this.size = 48,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    Color backgroundColor;
    IconData icon;

    switch (type.toLowerCase()) {
      case 'itp':
        backgroundColor = Colors.blue.withValues(alpha: 0.1);
        icon = Icons.verified_user;
        break;
      case 'rca':
      case 'casco':
      case 'insurance':
        backgroundColor = Colors.green.withValues(alpha: 0.1);
        icon = Icons.shield;
        break;
      case 'license':
      case 'licence':
        backgroundColor = Colors.purple.withValues(alpha: 0.1);
        icon = Icons.credit_card;
        break;
      case 'medical':
        backgroundColor = Colors.red.withValues(alpha: 0.1);
        icon = Icons.medical_services;
        break;
      case 'contract':
        backgroundColor = Colors.orange.withValues(alpha: 0.1);
        icon = Icons.description;
        break;
      default:
        backgroundColor = theme.colorScheme.primaryContainer;
        icon = Icons.insert_drive_file;
    }

    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Icon(
        icon,
        size: size * 0.5,
        color: theme.colorScheme.primary,
      ),
    );
  }
}

class _TypeBadge extends StatelessWidget {
  final String type;
  final String label;

  const _TypeBadge({
    required this.type,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: theme.colorScheme.primaryContainer,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: theme.textTheme.labelMedium?.copyWith(
          color: theme.colorScheme.onPrimaryContainer,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _ExpiryAlert extends StatelessWidget {
  final dynamic document;
  final DateFormat dateFormat;

  const _ExpiryAlert({
    required this.document,
    required this.dateFormat,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isExpired = document.isExpired;
    final color = isExpired ? Colors.red : Colors.orange;

    return Card(
      color: color.withValues(alpha: 0.1),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Icon(
              isExpired ? Icons.error : Icons.warning,
              color: color,
              size: 28,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    isExpired ? 'Document Expirat' : 'Expiră în Curând',
                    style: theme.textTheme.titleSmall?.copyWith(
                      color: color,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    isExpired
                        ? 'Acest document a expirat la ${dateFormat.format(document.expiryDate)}'
                        : 'Acest document expiră pe ${dateFormat.format(document.expiryDate)}',
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: color.withValues(alpha: 0.8),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ExpiryCountdown extends StatelessWidget {
  final int daysUntilExpiry;

  const _ExpiryCountdown({required this.daysUntilExpiry});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    Color color;
    String label;
    IconData icon;

    if (daysUntilExpiry < 0) {
      color = Colors.red;
      label = 'Expirat de ${-daysUntilExpiry} zile';
      icon = Icons.error;
    } else if (daysUntilExpiry == 0) {
      color = Colors.red;
      label = 'Expiră astăzi!';
      icon = Icons.warning;
    } else if (daysUntilExpiry <= 30) {
      color = Colors.orange;
      label = 'Expiră în $daysUntilExpiry zile';
      icon = Icons.warning;
    } else {
      color = Colors.green;
      label = 'Valid încă $daysUntilExpiry zile';
      icon = Icons.check_circle;
    }

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 8),
          Text(
            label,
            style: theme.textTheme.titleSmall?.copyWith(
              color: color,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
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
  final VoidCallback? onTap;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
    this.isAlert = false,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final valueColor = isAlert ? Colors.orange : theme.colorScheme.onSurface;

    Widget content = Padding(
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
          if (onTap != null)
            Icon(
              Icons.chevron_right,
              color: theme.colorScheme.onSurfaceVariant,
            ),
          if (isAlert && onTap == null)
            Icon(
              Icons.warning_amber_rounded,
              size: 18,
              color: Colors.orange,
            ),
        ],
      ),
    );

    if (onTap != null) {
      return InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: content,
      );
    }

    return content;
  }
}
