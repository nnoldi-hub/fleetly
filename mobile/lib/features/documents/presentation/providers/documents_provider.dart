import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fleetly_mobile/core/network/api_client.dart';
import 'package:fleetly_mobile/features/documents/data/models/document_model.dart';
import 'package:fleetly_mobile/features/documents/data/documents_repository.dart';

/// Documents repository provider
final documentsRepositoryProvider = Provider<DocumentsRepository>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return DocumentsRepository(apiClient);
});

/// Documents list state
class DocumentsState {
  final List<Document> documents;
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final int currentPage;
  final int totalPages;
  final bool hasMore;
  final String searchQuery;
  final String typeFilter;
  final String statusFilter;

  const DocumentsState({
    this.documents = const [],
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.currentPage = 1,
    this.totalPages = 1,
    this.hasMore = false,
    this.searchQuery = '',
    this.typeFilter = '',
    this.statusFilter = 'all',
  });

  DocumentsState copyWith({
    List<Document>? documents,
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    int? currentPage,
    int? totalPages,
    bool? hasMore,
    String? searchQuery,
    String? typeFilter,
    String? statusFilter,
  }) {
    return DocumentsState(
      documents: documents ?? this.documents,
      isLoading: isLoading ?? this.isLoading,
      isLoadingMore: isLoadingMore ?? this.isLoadingMore,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      totalPages: totalPages ?? this.totalPages,
      hasMore: hasMore ?? this.hasMore,
      searchQuery: searchQuery ?? this.searchQuery,
      typeFilter: typeFilter ?? this.typeFilter,
      statusFilter: statusFilter ?? this.statusFilter,
    );
  }
}

/// Documents list notifier
class DocumentsNotifier extends StateNotifier<DocumentsState> {
  final DocumentsRepository _repository;

  DocumentsNotifier(this._repository) : super(const DocumentsState());

  /// Load documents (first page)
  Future<void> loadDocuments({
    String? search,
    String? type,
    String? status,
  }) async {
    state = state.copyWith(
      isLoading: true,
      error: null,
      searchQuery: search ?? state.searchQuery,
      typeFilter: type ?? state.typeFilter,
      statusFilter: status ?? state.statusFilter,
    );

    final result = await _repository.getDocuments(
      page: 1,
      search: search ?? state.searchQuery,
      type: type ?? state.typeFilter,
      status: status ?? state.statusFilter,
    );

    result.fold(
      (failure) => state = state.copyWith(
        isLoading: false,
        error: failure.message,
      ),
      (response) => state = state.copyWith(
        isLoading: false,
        documents: response.documents,
        currentPage: response.page,
        totalPages: response.totalPages,
        hasMore: response.hasMore,
      ),
    );
  }

  /// Load more documents (next page)
  Future<void> loadMore() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);

    final result = await _repository.getDocuments(
      page: state.currentPage + 1,
      search: state.searchQuery,
      type: state.typeFilter,
      status: state.statusFilter,
    );

    result.fold(
      (failure) => state = state.copyWith(
        isLoadingMore: false,
        error: failure.message,
      ),
      (response) => state = state.copyWith(
        isLoadingMore: false,
        documents: [...state.documents, ...response.documents],
        currentPage: response.page,
        totalPages: response.totalPages,
        hasMore: response.hasMore,
      ),
    );
  }

  /// Refresh documents
  Future<void> refresh() async {
    await loadDocuments();
  }

  /// Search documents
  Future<void> search(String query) async {
    await loadDocuments(search: query);
  }

  /// Filter by type
  Future<void> filterByType(String type) async {
    await loadDocuments(type: type);
  }

  /// Filter by status
  Future<void> filterByStatus(String status) async {
    await loadDocuments(status: status);
  }

  /// Clear filters
  Future<void> clearFilters() async {
    await loadDocuments(search: '', type: '', status: 'all');
  }

  /// Delete document from local state
  void removeDocument(int id) {
    state = state.copyWith(
      documents: state.documents.where((d) => d.id != id).toList(),
    );
  }

  /// Update document in local state
  void updateDocument(Document document) {
    state = state.copyWith(
      documents: state.documents.map((d) => d.id == document.id ? document : d).toList(),
    );
  }

  /// Add document to local state
  void addDocument(Document document) {
    state = state.copyWith(
      documents: [document, ...state.documents],
    );
  }
}

/// Documents state provider
final documentsProvider = StateNotifierProvider<DocumentsNotifier, DocumentsState>((ref) {
  final repository = ref.watch(documentsRepositoryProvider);
  return DocumentsNotifier(repository);
});

/// Single document detail state
class DocumentDetailState {
  final Document? document;
  final bool isLoading;
  final String? error;

  const DocumentDetailState({
    this.document,
    this.isLoading = false,
    this.error,
  });

  DocumentDetailState copyWith({
    Document? document,
    bool? isLoading,
    String? error,
  }) {
    return DocumentDetailState(
      document: document ?? this.document,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

/// Document detail notifier
class DocumentDetailNotifier extends StateNotifier<DocumentDetailState> {
  final DocumentsRepository _repository;

  DocumentDetailNotifier(this._repository) : super(const DocumentDetailState());

  /// Load document details
  Future<void> loadDocument(int id) async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _repository.getDocument(id);

    result.fold(
      (failure) => state = state.copyWith(
        isLoading: false,
        error: failure.message,
      ),
      (document) => state = state.copyWith(
        isLoading: false,
        document: document,
      ),
    );
  }

  /// Delete document
  Future<bool> deleteDocument(int id) async {
    final result = await _repository.deleteDocument(id);
    return result.fold(
      (failure) => false,
      (success) => true,
    );
  }

  /// Reset state
  void reset() {
    state = const DocumentDetailState();
  }
}

/// Document detail provider
final documentDetailProvider = StateNotifierProvider<DocumentDetailNotifier, DocumentDetailState>((ref) {
  final repository = ref.watch(documentsRepositoryProvider);
  return DocumentDetailNotifier(repository);
});

/// Document form state
class DocumentFormState {
  final bool isSubmitting;
  final String? error;
  final Document? savedDocument;

  const DocumentFormState({
    this.isSubmitting = false,
    this.error,
    this.savedDocument,
  });

  DocumentFormState copyWith({
    bool? isSubmitting,
    String? error,
    Document? savedDocument,
  }) {
    return DocumentFormState(
      isSubmitting: isSubmitting ?? this.isSubmitting,
      error: error,
      savedDocument: savedDocument ?? this.savedDocument,
    );
  }
}

/// Document form notifier
class DocumentFormNotifier extends StateNotifier<DocumentFormState> {
  final DocumentsRepository _repository;

  DocumentFormNotifier(this._repository) : super(const DocumentFormState());

  /// Create new document
  Future<bool> createDocument(Map<String, dynamic> data) async {
    state = state.copyWith(isSubmitting: true, error: null);

    final result = await _repository.createDocument(data);

    return result.fold(
      (failure) {
        state = state.copyWith(
          isSubmitting: false,
          error: failure.message,
        );
        return false;
      },
      (document) {
        state = state.copyWith(
          isSubmitting: false,
          savedDocument: document,
        );
        return true;
      },
    );
  }

  /// Update document
  Future<bool> updateDocument(int id, Map<String, dynamic> data) async {
    state = state.copyWith(isSubmitting: true, error: null);

    final result = await _repository.updateDocument(id, data);

    return result.fold(
      (failure) {
        state = state.copyWith(
          isSubmitting: false,
          error: failure.message,
        );
        return false;
      },
      (document) {
        state = state.copyWith(
          isSubmitting: false,
          savedDocument: document,
        );
        return true;
      },
    );
  }

  /// Reset form state
  void reset() {
    state = const DocumentFormState();
  }
}

/// Document form provider
final documentFormProvider = StateNotifierProvider<DocumentFormNotifier, DocumentFormState>((ref) {
  final repository = ref.watch(documentsRepositoryProvider);
  return DocumentFormNotifier(repository);
});

/// Expiring documents provider
final expiringDocumentsProvider = FutureProvider.family<List<Document>, int>((ref, daysAhead) async {
  final repository = ref.watch(documentsRepositoryProvider);
  final result = await repository.getExpiringDocuments(daysAhead: daysAhead);
  return result.fold(
    (failure) => throw Exception(failure.message),
    (documents) => documents,
  );
});

/// Vehicle documents provider
final vehicleDocumentsProvider = FutureProvider.family<List<Document>, int>((ref, vehicleId) async {
  final repository = ref.watch(documentsRepositoryProvider);
  final result = await repository.getVehicleDocuments(vehicleId);
  return result.fold(
    (failure) => throw Exception(failure.message),
    (documents) => documents,
  );
});

/// Driver documents provider
final driverDocumentsProvider = FutureProvider.family<List<Document>, int>((ref, driverId) async {
  final repository = ref.watch(documentsRepositoryProvider);
  final result = await repository.getDriverDocuments(driverId);
  return result.fold(
    (failure) => throw Exception(failure.message),
    (documents) => documents,
  );
});
