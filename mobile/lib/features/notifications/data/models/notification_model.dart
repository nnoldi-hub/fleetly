import 'package:equatable/equatable.dart';

/// Notification type enum
enum NotificationType {
  alert('alert', 'Alertă'),
  info('info', 'Informare'),
  warning('warning', 'Avertisment'),
  reminder('reminder', 'Reminder'),
  system('system', 'Sistem');

  final String value;
  final String label;
  const NotificationType(this.value, this.label);

  static NotificationType fromString(String? value) {
    return NotificationType.values.firstWhere(
      (e) => e.value == value,
      orElse: () => NotificationType.info,
    );
  }
}

/// Notification priority enum
enum NotificationPriority {
  low('low', 'Scăzută', 0xFF9E9E9E),
  normal('normal', 'Normală', 0xFF2196F3),
  high('high', 'Ridicată', 0xFFFF9800),
  urgent('urgent', 'Urgentă', 0xFFF44336);

  final String value;
  final String label;
  final int color;
  const NotificationPriority(this.value, this.label, this.color);

  static NotificationPriority fromString(String? value) {
    return NotificationPriority.values.firstWhere(
      (e) => e.value == value,
      orElse: () => NotificationPriority.normal,
    );
  }
}

/// Notification model
class AppNotification extends Equatable {
  final int id;
  final String title;
  final String message;
  final NotificationType type;
  final NotificationPriority priority;
  final Map<String, dynamic>? data;
  final bool isRead;
  final DateTime? readAt;
  final DateTime createdAt;

  const AppNotification({
    required this.id,
    required this.title,
    required this.message,
    required this.type,
    required this.priority,
    this.data,
    required this.isRead,
    this.readAt,
    required this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      title: json['title'] ?? '',
      message: json['message'] ?? '',
      type: NotificationType.fromString(json['type']),
      priority: NotificationPriority.fromString(json['priority']),
      data: json['data'] as Map<String, dynamic>?,
      isRead: json['is_read'] ?? false,
      readAt: json['read_at'] != null ? DateTime.tryParse(json['read_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  String get typeLabel => type.label;
  String get priorityLabel => priority.label;

  String get timeAgo {
    final now = DateTime.now();
    final diff = now.difference(createdAt);

    if (diff.inMinutes < 1) {
      return 'Acum';
    } else if (diff.inMinutes < 60) {
      return 'Acum ${diff.inMinutes} min';
    } else if (diff.inHours < 24) {
      return 'Acum ${diff.inHours} ore';
    } else if (diff.inDays < 7) {
      return 'Acum ${diff.inDays} zile';
    } else {
      return '${createdAt.day}/${createdAt.month}/${createdAt.year}';
    }
  }

  @override
  List<Object?> get props => [
        id,
        title,
        message,
        type,
        priority,
        data,
        isRead,
        readAt,
        createdAt,
      ];
}

/// Notifications list response
class NotificationsResponse {
  final List<AppNotification> data;
  final int currentPage;
  final int lastPage;
  final int total;

  NotificationsResponse({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory NotificationsResponse.fromJson(Map<String, dynamic> json) {
    final dataList = json['data'] as List? ?? [];
    return NotificationsResponse(
      data: dataList.map((e) => AppNotification.fromJson(e)).toList(),
      currentPage: json['pagination']?['current_page'] ?? 1,
      lastPage: json['pagination']?['last_page'] ?? 1,
      total: json['pagination']?['total'] ?? 0,
    );
  }
}
