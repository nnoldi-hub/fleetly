/// Document model
class Document {
  final int id;
  final String name;
  final String type;
  final String? description;
  final String? filePath;
  final String? fileUrl;
  final int? vehicleId;
  final String? vehiclePlate;
  final int? driverId;
  final String? driverName;
  final DateTime? issueDate;
  final DateTime? expiryDate;
  final String? status;
  final String? notes;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Document({
    required this.id,
    required this.name,
    required this.type,
    this.description,
    this.filePath,
    this.fileUrl,
    this.vehicleId,
    this.vehiclePlate,
    this.driverId,
    this.driverName,
    this.issueDate,
    this.expiryDate,
    this.status,
    this.notes,
    this.createdAt,
    this.updatedAt,
  });

  factory Document.fromJson(Map<String, dynamic> json) {
    return Document(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      name: json['name'] ?? json['title'] ?? '',
      type: json['type'] ?? json['document_type'] ?? 'other',
      description: json['description'],
      filePath: json['file_path'] ?? json['filePath'],
      fileUrl: json['file_url'] ?? json['fileUrl'],
      vehicleId: json['vehicle_id'] != null
          ? (json['vehicle_id'] is String ? int.tryParse(json['vehicle_id']) : json['vehicle_id'])
          : null,
      vehiclePlate: json['vehicle_plate'] ?? json['vehiclePlate'],
      driverId: json['driver_id'] != null
          ? (json['driver_id'] is String ? int.tryParse(json['driver_id']) : json['driver_id'])
          : null,
      driverName: json['driver_name'] ?? json['driverName'],
      issueDate: json['issue_date'] != null
          ? DateTime.tryParse(json['issue_date'])
          : null,
      expiryDate: json['expiry_date'] != null
          ? DateTime.tryParse(json['expiry_date'])
          : null,
      status: json['status'] ?? 'active',
      notes: json['notes'],
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'type': type,
      'description': description,
      'file_path': filePath,
      'vehicle_id': vehicleId,
      'driver_id': driverId,
      'issue_date': issueDate?.toIso8601String().split('T').first,
      'expiry_date': expiryDate?.toIso8601String().split('T').first,
      'status': status,
      'notes': notes,
    };
  }

  /// Check if document is expired
  bool get isExpired {
    if (expiryDate == null) return false;
    return expiryDate!.isBefore(DateTime.now());
  }

  /// Check if document expires soon (within 30 days)
  bool get expiresSoon {
    if (expiryDate == null) return false;
    final thirtyDaysFromNow = DateTime.now().add(const Duration(days: 30));
    return expiryDate!.isBefore(thirtyDaysFromNow) && !isExpired;
  }

  /// Check if document has any alerts
  bool get hasAlerts => isExpired || expiresSoon;

  /// Get document type label in Romanian
  String get typeLabel {
    switch (type.toLowerCase()) {
      case 'itp':
        return 'ITP';
      case 'rca':
        return 'RCA';
      case 'casco':
        return 'CASCO';
      case 'licence':
      case 'license':
        return 'Licență';
      case 'registration':
        return 'Carte identitate';
      case 'inspection':
        return 'Inspecție';
      case 'insurance':
        return 'Asigurare';
      case 'contract':
        return 'Contract';
      case 'permit':
        return 'Autorizație';
      case 'medical':
        return 'Fișă medicală';
      case 'other':
      default:
        return 'Altele';
    }
  }

  /// Get status label
  String get statusLabel {
    switch (status?.toLowerCase()) {
      case 'active':
        return 'Activ';
      case 'expired':
        return 'Expirat';
      case 'pending':
        return 'În așteptare';
      case 'archived':
        return 'Arhivat';
      default:
        return status ?? 'Necunoscut';
    }
  }

  /// Get days until expiry (negative if expired)
  int? get daysUntilExpiry {
    if (expiryDate == null) return null;
    return expiryDate!.difference(DateTime.now()).inDays;
  }

  /// Get owner description
  String get ownerDescription {
    if (vehiclePlate != null && driverName != null) {
      return '$vehiclePlate • $driverName';
    }
    if (vehiclePlate != null) return vehiclePlate!;
    if (driverName != null) return driverName!;
    return 'Fără asociere';
  }

  Document copyWith({
    int? id,
    String? name,
    String? type,
    String? description,
    String? filePath,
    String? fileUrl,
    int? vehicleId,
    String? vehiclePlate,
    int? driverId,
    String? driverName,
    DateTime? issueDate,
    DateTime? expiryDate,
    String? status,
    String? notes,
  }) {
    return Document(
      id: id ?? this.id,
      name: name ?? this.name,
      type: type ?? this.type,
      description: description ?? this.description,
      filePath: filePath ?? this.filePath,
      fileUrl: fileUrl ?? this.fileUrl,
      vehicleId: vehicleId ?? this.vehicleId,
      vehiclePlate: vehiclePlate ?? this.vehiclePlate,
      driverId: driverId ?? this.driverId,
      driverName: driverName ?? this.driverName,
      issueDate: issueDate ?? this.issueDate,
      expiryDate: expiryDate ?? this.expiryDate,
      status: status ?? this.status,
      notes: notes ?? this.notes,
    );
  }
}

/// Paginated documents response
class DocumentsResponse {
  final List<Document> documents;
  final int total;
  final int page;
  final int perPage;
  final int totalPages;

  DocumentsResponse({
    required this.documents,
    required this.total,
    required this.page,
    required this.perPage,
    required this.totalPages,
  });

  factory DocumentsResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>? ?? [];
    final pagination = json['pagination'] as Map<String, dynamic>? ?? {};

    return DocumentsResponse(
      documents: data.map((d) => Document.fromJson(d as Map<String, dynamic>)).toList(),
      total: pagination['total'] ?? data.length,
      page: pagination['page'] ?? 1,
      perPage: pagination['per_page'] ?? pagination['perPage'] ?? 10,
      totalPages: pagination['total_pages'] ?? pagination['totalPages'] ?? 1,
    );
  }

  bool get hasMore => page < totalPages;
}

/// Document type enum for filtering
enum DocumentType {
  all,
  itp,
  rca,
  casco,
  license,
  registration,
  insurance,
  contract,
  permit,
  medical,
  other;

  String get label {
    switch (this) {
      case DocumentType.all:
        return 'Toate';
      case DocumentType.itp:
        return 'ITP';
      case DocumentType.rca:
        return 'RCA';
      case DocumentType.casco:
        return 'CASCO';
      case DocumentType.license:
        return 'Licență';
      case DocumentType.registration:
        return 'Carte identitate';
      case DocumentType.insurance:
        return 'Asigurare';
      case DocumentType.contract:
        return 'Contract';
      case DocumentType.permit:
        return 'Autorizație';
      case DocumentType.medical:
        return 'Fișă medicală';
      case DocumentType.other:
        return 'Altele';
    }
  }

  String get value {
    if (this == DocumentType.all) return '';
    return name;
  }
}
