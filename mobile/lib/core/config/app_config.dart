/// App configuration for different environments
class AppConfig {
  static const String appName = 'Fleetly';
  static const String appVersion = '1.0.0';
  
  // API Configuration
  static const String devApiUrl = 'http://localhost/fleet-management/api/v1';
  static const String prodApiUrl = 'https://fleetly.ro/api/v1';
  
  // Current environment
  static const bool isProduction = true;
  
  static String get apiBaseUrl => isProduction ? prodApiUrl : devApiUrl;
  
  // Timeouts
  static const int connectionTimeout = 30000; // 30 seconds
  static const int receiveTimeout = 30000;
  
  // Storage Keys
  static const String accessTokenKey = 'access_token';
  static const String refreshTokenKey = 'refresh_token';
  static const String userDataKey = 'user_data';
  
  // Pagination
  static const int defaultPageSize = 20;
}
