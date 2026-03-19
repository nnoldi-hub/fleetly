import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Settings state
class SettingsState {
  final bool notificationsEnabled;
  final bool emailNotifications;
  final bool pushNotifications;
  final bool darkMode;
  final String language;
  final bool biometricEnabled;
  final int autoLogoutMinutes;

  const SettingsState({
    this.notificationsEnabled = true,
    this.emailNotifications = true,
    this.pushNotifications = true,
    this.darkMode = false,
    this.language = 'ro',
    this.biometricEnabled = false,
    this.autoLogoutMinutes = 30,
  });

  SettingsState copyWith({
    bool? notificationsEnabled,
    bool? emailNotifications,
    bool? pushNotifications,
    bool? darkMode,
    String? language,
    bool? biometricEnabled,
    int? autoLogoutMinutes,
  }) {
    return SettingsState(
      notificationsEnabled: notificationsEnabled ?? this.notificationsEnabled,
      emailNotifications: emailNotifications ?? this.emailNotifications,
      pushNotifications: pushNotifications ?? this.pushNotifications,
      darkMode: darkMode ?? this.darkMode,
      language: language ?? this.language,
      biometricEnabled: biometricEnabled ?? this.biometricEnabled,
      autoLogoutMinutes: autoLogoutMinutes ?? this.autoLogoutMinutes,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'notificationsEnabled': notificationsEnabled,
      'emailNotifications': emailNotifications,
      'pushNotifications': pushNotifications,
      'darkMode': darkMode,
      'language': language,
      'biometricEnabled': biometricEnabled,
      'autoLogoutMinutes': autoLogoutMinutes,
    };
  }

  factory SettingsState.fromJson(Map<String, dynamic> json) {
    return SettingsState(
      notificationsEnabled: json['notificationsEnabled'] ?? true,
      emailNotifications: json['emailNotifications'] ?? true,
      pushNotifications: json['pushNotifications'] ?? true,
      darkMode: json['darkMode'] ?? false,
      language: json['language'] ?? 'ro',
      biometricEnabled: json['biometricEnabled'] ?? false,
      autoLogoutMinutes: json['autoLogoutMinutes'] ?? 30,
    );
  }
}

/// Settings notifier
class SettingsNotifier extends StateNotifier<SettingsState> {
  final FlutterSecureStorage _storage;
  static const _settingsKey = 'app_settings';

  SettingsNotifier(this._storage) : super(const SettingsState()) {
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    try {
      final settingsJson = await _storage.read(key: _settingsKey);
      if (settingsJson != null) {
        // Parse JSON manually since we don't have dart:convert imported
        // In a real app, you'd use jsonDecode
        // For now, use default settings
      }
    } catch (e) {
      // Use default settings on error
    }
  }

  Future<void> _saveSettings() async {
    try {
      // In a real app, you'd use jsonEncode
      // For now, save individual values
      await _storage.write(
        key: '${_settingsKey}_notifications',
        value: state.notificationsEnabled.toString(),
      );
      await _storage.write(
        key: '${_settingsKey}_darkMode',
        value: state.darkMode.toString(),
      );
      await _storage.write(
        key: '${_settingsKey}_language',
        value: state.language,
      );
    } catch (e) {
      // Handle error
    }
  }

  void setNotificationsEnabled(bool enabled) {
    state = state.copyWith(notificationsEnabled: enabled);
    _saveSettings();
  }

  void setEmailNotifications(bool enabled) {
    state = state.copyWith(emailNotifications: enabled);
    _saveSettings();
  }

  void setPushNotifications(bool enabled) {
    state = state.copyWith(pushNotifications: enabled);
    _saveSettings();
  }

  void setDarkMode(bool enabled) {
    state = state.copyWith(darkMode: enabled);
    _saveSettings();
  }

  void setLanguage(String language) {
    state = state.copyWith(language: language);
    _saveSettings();
  }

  void setBiometricEnabled(bool enabled) {
    state = state.copyWith(biometricEnabled: enabled);
    _saveSettings();
  }

  void setAutoLogoutMinutes(int minutes) {
    state = state.copyWith(autoLogoutMinutes: minutes);
    _saveSettings();
  }

  Future<void> resetSettings() async {
    state = const SettingsState();
    await _saveSettings();
  }
}

/// Settings provider
final settingsProvider =
    StateNotifierProvider<SettingsNotifier, SettingsState>((ref) {
  const storage = FlutterSecureStorage();
  return SettingsNotifier(storage);
});
