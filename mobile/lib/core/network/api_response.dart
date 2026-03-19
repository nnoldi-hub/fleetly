/// Generic API response wrapper
class ApiResponse<T> {
  final bool success;
  final String? message;
  final T? data;
  final ApiError? error;
  final ApiPagination? pagination;

  ApiResponse({
    required this.success,
    this.message,
    this.data,
    this.error,
    this.pagination,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic)? fromJsonT,
  ) {
    return ApiResponse<T>(
      success: json['success'] ?? false,
      message: json['message'],
      data: json['data'] != null && fromJsonT != null
          ? fromJsonT(json['data'])
          : json['data'],
      error: json['error'] != null ? ApiError.fromJson(json['error']) : null,
      pagination: json['meta']?['pagination'] != null
          ? ApiPagination.fromJson(json['meta']['pagination'])
          : null,
    );
  }
}

/// API error details
class ApiError {
  final int? code;
  final String? message;
  final Map<String, dynamic>? details;

  ApiError({this.code, this.message, this.details});

  factory ApiError.fromJson(Map<String, dynamic> json) {
    return ApiError(
      code: json['code'],
      message: json['message'],
      details: json['details'],
    );
  }
}

/// Pagination metadata
class ApiPagination {
  final int currentPage;
  final int perPage;
  final int total;
  final int totalPages;
  final bool hasMore;

  ApiPagination({
    required this.currentPage,
    required this.perPage,
    required this.total,
    required this.totalPages,
    required this.hasMore,
  });

  factory ApiPagination.fromJson(Map<String, dynamic> json) {
    return ApiPagination(
      currentPage: json['current_page'] ?? 1,
      perPage: json['per_page'] ?? 20,
      total: json['total'] ?? 0,
      totalPages: json['total_pages'] ?? 0,
      hasMore: json['has_more'] ?? false,
    );
  }
}
