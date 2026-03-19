import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../../core/network/api_client.dart';
import '../../data/notification_repository.dart';
import '../../data/models/notification_model.dart';

/// Repository provider
final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(ref.watch(apiClientProvider));
});

/// Notifications list state
class NotificationsState {
  final List<AppNotification> notifications;
  final bool isLoading;
  final String? error;
  final int currentPage;
  final int lastPage;
  final int total;
  final bool unreadOnly;

  const NotificationsState({
    this.notifications = const [],
    this.isLoading = false,
    this.error,
    this.currentPage = 1,
    this.lastPage = 1,
    this.total = 0,
    this.unreadOnly = false,
  });

  bool get canLoadMore => currentPage < lastPage;
  int get unreadCount => notifications.where((n) => !n.isRead).length;

  NotificationsState copyWith({
    List<AppNotification>? notifications,
    bool? isLoading,
    String? error,
    int? currentPage,
    int? lastPage,
    int? total,
    bool? unreadOnly,
  }) {
    return NotificationsState(
      notifications: notifications ?? this.notifications,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      currentPage: currentPage ?? this.currentPage,
      lastPage: lastPage ?? this.lastPage,
      total: total ?? this.total,
      unreadOnly: unreadOnly ?? this.unreadOnly,
    );
  }
}

/// Notifications list notifier
class NotificationsNotifier extends StateNotifier<NotificationsState> {
  final NotificationRepository _repository;

  NotificationsNotifier(this._repository) : super(const NotificationsState());

  /// Load notifications (first page)
  Future<void> load({bool? unreadOnly}) async {
    state = state.copyWith(
      isLoading: true,
      error: null,
      unreadOnly: unreadOnly ?? state.unreadOnly,
    );

    final result = await _repository.getNotifications(
      page: 1,
      unreadOnly: unreadOnly ?? state.unreadOnly,
    );

    result.fold(
      (error) => state = state.copyWith(isLoading: false, error: error),
      (response) => state = state.copyWith(
        notifications: response.data,
        isLoading: false,
        currentPage: response.currentPage,
        lastPage: response.lastPage,
        total: response.total,
      ),
    );
  }

  /// Load more notifications
  Future<void> loadMore() async {
    if (!state.canLoadMore || state.isLoading) return;

    state = state.copyWith(isLoading: true);

    final result = await _repository.getNotifications(
      page: state.currentPage + 1,
      unreadOnly: state.unreadOnly,
    );

    result.fold(
      (error) => state = state.copyWith(isLoading: false, error: error),
      (response) => state = state.copyWith(
        notifications: [...state.notifications, ...response.data],
        isLoading: false,
        currentPage: response.currentPage,
        lastPage: response.lastPage,
        total: response.total,
      ),
    );
  }

  /// Toggle unread filter
  Future<void> toggleUnreadOnly() async {
    await load(unreadOnly: !state.unreadOnly);
  }

  /// Mark a notification as read
  Future<bool> markAsRead(int id) async {
    final result = await _repository.markAsRead(id);

    return result.fold(
      (error) => false,
      (success) {
        // Update local state
        final updated = state.notifications.map((n) {
          if (n.id == id) {
            return AppNotification(
              id: n.id,
              title: n.title,
              message: n.message,
              type: n.type,
              priority: n.priority,
              data: n.data,
              isRead: true,
              readAt: DateTime.now(),
              createdAt: n.createdAt,
            );
          }
          return n;
        }).toList();

        state = state.copyWith(notifications: updated);
        return true;
      },
    );
  }

  /// Mark all notifications as read
  Future<bool> markAllAsRead() async {
    final result = await _repository.markAllAsRead();

    return result.fold(
      (error) => false,
      (success) {
        // Update local state
        final updated = state.notifications.map((n) {
          return AppNotification(
            id: n.id,
            title: n.title,
            message: n.message,
            type: n.type,
            priority: n.priority,
            data: n.data,
            isRead: true,
            readAt: DateTime.now(),
            createdAt: n.createdAt,
          );
        }).toList();

        state = state.copyWith(notifications: updated);
        return true;
      },
    );
  }

  /// Delete a notification
  Future<bool> deleteNotification(int id) async {
    final result = await _repository.deleteNotification(id);

    return result.fold(
      (error) => false,
      (success) {
        // Remove from local state
        final updated = state.notifications.where((n) => n.id != id).toList();
        state = state.copyWith(
          notifications: updated,
          total: state.total - 1,
        );
        return true;
      },
    );
  }
}

/// Notifications provider
final notificationsProvider =
    StateNotifierProvider<NotificationsNotifier, NotificationsState>((ref) {
  return NotificationsNotifier(ref.watch(notificationRepositoryProvider));
});

/// Unread count state
class UnreadCountState {
  final int count;
  final bool isLoading;
  final String? error;

  const UnreadCountState({
    this.count = 0,
    this.isLoading = false,
    this.error,
  });

  UnreadCountState copyWith({
    int? count,
    bool? isLoading,
    String? error,
  }) {
    return UnreadCountState(
      count: count ?? this.count,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

/// Unread count notifier
class UnreadCountNotifier extends StateNotifier<UnreadCountState> {
  final NotificationRepository _repository;

  UnreadCountNotifier(this._repository) : super(const UnreadCountState());

  /// Load unread count
  Future<void> load() async {
    state = state.copyWith(isLoading: true, error: null);

    final result = await _repository.getUnreadCount();

    result.fold(
      (error) => state = state.copyWith(isLoading: false, error: error),
      (count) => state = state.copyWith(count: count, isLoading: false),
    );
  }

  /// Decrement count (after marking as read)
  void decrement() {
    if (state.count > 0) {
      state = state.copyWith(count: state.count - 1);
    }
  }

  /// Reset count to 0 (after marking all as read)
  void reset() {
    state = state.copyWith(count: 0);
  }
}

/// Unread count provider
final unreadCountProvider =
    StateNotifierProvider<UnreadCountNotifier, UnreadCountState>((ref) {
  return UnreadCountNotifier(ref.watch(notificationRepositoryProvider));
});
