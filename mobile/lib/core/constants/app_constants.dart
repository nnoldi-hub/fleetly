/// App-wide constants
class AppConstants {
  // Route names
  static const String splashRoute = '/';
  static const String loginRoute = '/login';
  static const String dashboardRoute = '/dashboard';
  static const String vehiclesRoute = '/vehicles';
  static const String vehicleDetailRoute = '/vehicles/:id';
  static const String driversRoute = '/drivers';
  static const String driverDetailRoute = '/drivers/:id';
  static const String documentsRoute = '/documents';
  static const String maintenanceRoute = '/maintenance';
  static const String fuelRoute = '/fuel';
  static const String insuranceRoute = '/insurance';
  static const String notificationsRoute = '/notifications';
  static const String reportsRoute = '/reports';
  static const String settingsRoute = '/settings';
  static const String profileRoute = '/profile';
  
  // Asset paths
  static const String imagesPath = 'assets/images';
  static const String iconsPath = 'assets/icons';
  
  // Date formats
  static const String dateFormat = 'dd/MM/yyyy';
  static const String dateTimeFormat = 'dd/MM/yyyy HH:mm';
  static const String apiDateFormat = 'yyyy-MM-dd';
  
  // Validation
  static const int minPasswordLength = 6;
  static const int maxPasswordLength = 50;
}
