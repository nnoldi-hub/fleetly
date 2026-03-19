import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fleetly_mobile/features/documents/data/models/document_model.dart';
import 'package:fleetly_mobile/features/documents/presentation/providers/documents_provider.dart';

class DocumentsListScreen extends ConsumerStatefulWidget {
  const DocumentsListScreen({super.key});

  @override
  ConsumerState<DocumentsListScreen> createState() => _DocumentsListScreenState();
}

class _DocumentsListScreenState extends ConsumerState<DocumentsListScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  DocumentType _selectedType = DocumentType.all;
  String _selectedStatus = 'all';

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(documentsProvider.notifier).loadDocuments();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      ref.read(documentsProvider.notifier).loadMore();
    }
  }

  void _onSearch(String query) {
    ref.read(documentsProvider.notifier).search(query);
  }

  void _onTypeFilter(DocumentType type) {
    setState(() => _selectedType = type);
    ref.read(documentsProvider.notifier).filterByType(type.value);
  }

  void _onStatusFilter(String status) {
    setState(() => _selectedStatus = status);
    ref.read(documentsProvider.notifier).filterByStatus(status);
  }

  void _clearFilters() {
    _searchController.clear();
    setState(() {
      _selectedType = DocumentType.all;
      _selectedStatus = 'all';
    });
    ref.read(documentsProvider.notifier).clearFilters();
  }

  void _showFilterSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => _FilterBottomSheet(
        selectedType: _selectedType,
        selectedStatus: _selectedStatus,
        onTypeChanged: _onTypeFilter,
        onStatusChanged: _onStatusFilter,
        onClear: () {
          Navigator.pop(context);
          _clearFilters();
        },
        onApply: () => Navigator.pop(context),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(documentsProvider);
    final theme = Theme.of(context);
    final hasFilters = _selectedType != DocumentType.all || 
                       _selectedStatus != 'all' || 
                       state.searchQuery.isNotEmpty;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Documente'),
        actions: [
          IconButton(
            icon: Badge(
              isLabelVisible: hasFilters,
              child: const Icon(Icons.filter_list),
            ),
            onPressed: _showFilterSheet,
            tooltip: 'Filtre',
          ),
          if (hasFilters)
            IconButton(
              icon: const Icon(Icons.clear_all),
              onPressed: _clearFilters,
              tooltip: 'Șterge filtrele',
            ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/documents/add'),
        child: const Icon(Icons.add),
      ),
      body: Column(
        children: [
          // Search bar
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Caută documente...',
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
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              onChanged: _onSearch,
            ),
          ),

          // Content
          Expanded(
            child: _buildContent(state, theme),
          ),
        ],
      ),
    );
  }

  Widget _buildContent(DocumentsState state, ThemeData theme) {
    if (state.isLoading && state.documents.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.error != null && state.documents.isEmpty) {
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
              'Eroare la încărcarea documentelor',
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
              onPressed: () => ref.read(documentsProvider.notifier).loadDocuments(),
              icon: const Icon(Icons.refresh),
              label: const Text('Reîncearcă'),
            ),
          ],
        ),
      );
    }

    if (state.documents.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.folder_off_outlined,
              size: 64,
              color: theme.colorScheme.onSurfaceVariant,
            ),
            const SizedBox(height: 16),
            Text(
              state.searchQuery.isNotEmpty || _selectedType != DocumentType.all
                  ? 'Nu s-au găsit documente'
                  : 'Nu există documente',
              style: theme.textTheme.titleMedium,
            ),
            if (state.searchQuery.isEmpty && _selectedType == DocumentType.all) ...[
              const SizedBox(height: 8),
              Text(
                'Adaugă primul document',
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => context.push('/documents/add'),
                icon: const Icon(Icons.add),
                label: const Text('Adaugă Document'),
              ),
            ],
            if (state.searchQuery.isNotEmpty || _selectedType != DocumentType.all) ...[
              const SizedBox(height: 24),
              TextButton.icon(
                onPressed: _clearFilters,
                icon: const Icon(Icons.clear_all),
                label: const Text('Șterge filtrele'),
              ),
            ],
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(documentsProvider.notifier).refresh(),
      child: ListView.builder(
        controller: _scrollController,
        padding: const EdgeInsets.all(16),
        itemCount: state.documents.length + (state.isLoadingMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index == state.documents.length) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: CircularProgressIndicator(),
              ),
            );
          }

          final document = state.documents[index];
          return _DocumentCard(
            document: document,
            onTap: () => context.push('/documents/${document.id}'),
          );
        },
      ),
    );
  }
}

class _FilterBottomSheet extends StatelessWidget {
  final DocumentType selectedType;
  final String selectedStatus;
  final Function(DocumentType) onTypeChanged;
  final Function(String) onStatusChanged;
  final VoidCallback onClear;
  final VoidCallback onApply;

  const _FilterBottomSheet({
    required this.selectedType,
    required this.selectedStatus,
    required this.onTypeChanged,
    required this.onStatusChanged,
    required this.onClear,
    required this.onApply,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Filtre',
                style: theme.textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              TextButton(
                onPressed: onClear,
                child: const Text('Resetează'),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Text(
            'Tip document',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: DocumentType.values.map((type) {
              return FilterChip(
                label: Text(type.label),
                selected: selectedType == type,
                onSelected: (_) => onTypeChanged(type),
              );
            }).toList(),
          ),
          const SizedBox(height: 20),
          Text(
            'Status',
            style: theme.textTheme.titleSmall?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              FilterChip(
                label: const Text('Toate'),
                selected: selectedStatus == 'all',
                onSelected: (_) => onStatusChanged('all'),
              ),
              FilterChip(
                label: const Text('Active'),
                selected: selectedStatus == 'active',
                onSelected: (_) => onStatusChanged('active'),
              ),
              FilterChip(
                label: const Text('Expirate'),
                selected: selectedStatus == 'expired',
                onSelected: (_) => onStatusChanged('expired'),
                selectedColor: Colors.red.withValues(alpha: 0.2),
              ),
              FilterChip(
                label: const Text('Expiră în curând'),
                selected: selectedStatus == 'expiring',
                onSelected: (_) => onStatusChanged('expiring'),
                selectedColor: Colors.orange.withValues(alpha: 0.2),
              ),
            ],
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: onApply,
              child: const Text('Aplică'),
            ),
          ),
          const SizedBox(height: 8),
        ],
      ),
    );
  }
}

class _DocumentCard extends StatelessWidget {
  final Document document;
  final VoidCallback onTap;

  const _DocumentCard({
    required this.document,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final dateFormat = DateFormat('dd.MM.yyyy');

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Icon
              _DocumentIcon(type: document.type, hasAlert: document.hasAlerts),
              const SizedBox(width: 16),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            document.name,
                            style: theme.textTheme.titleMedium?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        _TypeBadge(type: document.type),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      document.ownerDescription,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                    if (document.expiryDate != null) ...[
                      const SizedBox(height: 8),
                      _ExpiryInfo(
                        expiryDate: document.expiryDate!,
                        dateFormat: dateFormat,
                        isExpired: document.isExpired,
                        expiresSoon: document.expiresSoon,
                      ),
                    ],
                  ],
                ),
              ),
              // Chevron
              const SizedBox(width: 8),
              Icon(
                Icons.chevron_right,
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DocumentIcon extends StatelessWidget {
  final String type;
  final bool hasAlert;

  const _DocumentIcon({
    required this.type,
    required this.hasAlert,
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

    if (hasAlert) {
      backgroundColor = Colors.orange.withValues(alpha: 0.1);
    }

    return Stack(
      children: [
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: backgroundColor,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(
            icon,
            color: hasAlert ? Colors.orange : theme.colorScheme.primary,
          ),
        ),
        if (hasAlert)
          Positioned(
            right: -2,
            top: -2,
            child: Container(
              width: 16,
              height: 16,
              decoration: BoxDecoration(
                color: Colors.orange,
                shape: BoxShape.circle,
                border: Border.all(color: theme.colorScheme.surface, width: 2),
              ),
              child: const Icon(
                Icons.priority_high,
                size: 10,
                color: Colors.white,
              ),
            ),
          ),
      ],
    );
  }
}

class _TypeBadge extends StatelessWidget {
  final String type;

  const _TypeBadge({required this.type});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    String label;

    switch (type.toLowerCase()) {
      case 'itp':
        label = 'ITP';
        break;
      case 'rca':
        label = 'RCA';
        break;
      case 'casco':
        label = 'CASCO';
        break;
      default:
        label = type.toUpperCase();
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: theme.colorScheme.primaryContainer,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        label,
        style: theme.textTheme.labelSmall?.copyWith(
          color: theme.colorScheme.onPrimaryContainer,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _ExpiryInfo extends StatelessWidget {
  final DateTime expiryDate;
  final DateFormat dateFormat;
  final bool isExpired;
  final bool expiresSoon;

  const _ExpiryInfo({
    required this.expiryDate,
    required this.dateFormat,
    required this.isExpired,
    required this.expiresSoon,
  });

  @override
  Widget build(BuildContext context) {
    Color color;
    IconData icon;
    String text;

    if (isExpired) {
      color = Colors.red;
      icon = Icons.error;
      text = 'Expirat: ${dateFormat.format(expiryDate)}';
    } else if (expiresSoon) {
      color = Colors.orange;
      icon = Icons.warning;
      final days = expiryDate.difference(DateTime.now()).inDays;
      text = 'Expiră în $days zile';
    } else {
      color = Colors.green;
      icon = Icons.check_circle;
      text = 'Valid până: ${dateFormat.format(expiryDate)}';
    }

    return Row(
      children: [
        Icon(icon, size: 14, color: color),
        const SizedBox(width: 4),
        Text(
          text,
          style: TextStyle(
            fontSize: 12,
            color: color,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}
