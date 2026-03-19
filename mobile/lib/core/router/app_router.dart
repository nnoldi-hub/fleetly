import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:fleetly_mobile/core/constants/app_constants.dart';
import 'package:fleetly_mobile/features/auth/presentation/providers/auth_provider.dart';
import 'package:fleetly_mobile/features/auth/presentation/screens/login_screen.dart';
import 'package:fleetly_mobile/features/dashboard/presentation/screens/dashboard_screen.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/screens/vehicles_list_screen.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/screens/vehicle_detail_screen.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/screens/vehicle_form_screen.dart';
import 'package:fleetly_mobile/features/vehicles/presentation/providers/vehicles_provider.dart';
import 'package:fleetly_mobile/features/drivers/presentation/screens/drivers_list_screen.dart';
import 'package:fleetly_mobile/features/drivers/presentation/screens/driver_detail_screen.dart';
import 'package:fleetly_mobile/features/drivers/presentation/screens/driver_form_screen.dart';
import 'package:fleetly_mobile/features/documents/presentation/screens/documents_list_screen.dart';
import 'package:fleetly_mobile/features/documents/presentation/screens/document_detail_screen.dart';
import 'package:fleetly_mobile/features/documents/presentation/screens/document_form_screen.dart';
import 'package:fleetly_mobile/features/maintenance/presentation/screens/maintenance_list_screen.dart';
import 'package:fleetly_mobile/features/maintenance/presentation/screens/maintenance_detail_screen.dart';
import 'package:fleetly_mobile/features/maintenance/presentation/screens/maintenance_form_screen.dart';
import 'package:fleetly_mobile/features/fuel/presentation/screens/fuel_list_screen.dart';
import 'package:fleetly_mobile/features/fuel/presentation/screens/fuel_detail_screen.dart';
import 'package:fleetly_mobile/features/fuel/presentation/screens/fuel_form_screen.dart';
import 'package:fleetly_mobile/features/insurance/presentation/screens/insurance_list_screen.dart';
import 'package:fleetly_mobile/features/insurance/presentation/screens/insurance_detail_screen.dart';
import 'package:fleetly_mobile/features/insurance/presentation/screens/insurance_form_screen.dart';
import 'package:fleetly_mobile/features/notifications/presentation/screens/notifications_screen.dart';
import 'package:fleetly_mobile/features/reports/presentation/screens/reports_screen.dart';
import 'package:fleetly_mobile/features/settings/presentation/screens/settings_screen.dart';

/// Router provider
final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authNotifierProvider);
  
  return GoRouter(
    initialLocation: AppConstants.splashRoute,
    debugLogDiagnostics: true,
    redirect: (context, state) {
      final isLoggedIn = authState.isAuthenticated;
      final isLoggingIn = state.matchedLocation == AppConstants.loginRoute;
      final isInitial = authState.status == AuthStatus.initial || 
                        authState.status == AuthStatus.loading;
      
      // Still checking auth status
      if (isInitial) {
        return null; // Stay on current page
      }
      
      // Not logged in and not on login page - redirect to login
      if (!isLoggedIn && !isLoggingIn) {
        return AppConstants.loginRoute;
      }
      
      // Logged in and on login page - redirect to dashboard
      if (isLoggedIn && isLoggingIn) {
        return AppConstants.dashboardRoute;
      }
      
      // Logged in and on splash - redirect to dashboard
      if (isLoggedIn && state.matchedLocation == AppConstants.splashRoute) {
        return AppConstants.dashboardRoute;
      }
      
      return null;
    },
    routes: [
      // Splash / Initial
      GoRoute(
        path: AppConstants.splashRoute,
        builder: (context, state) => const _SplashScreen(),
      ),
      
      // Auth routes
      GoRoute(
        path: AppConstants.loginRoute,
        builder: (context, state) => const LoginScreen(),
      ),
      
      // Main app routes
      GoRoute(
        path: AppConstants.dashboardRoute,
        builder: (context, state) => const DashboardScreen(),
      ),
      
      // Vehicles
      GoRoute(
        path: AppConstants.vehiclesRoute,
        builder: (context, state) => const VehiclesListScreen(),
        routes: [
          GoRoute(
            path: 'add',
            builder: (context, state) => const VehicleFormScreen(),
          ),
          GoRoute(
            path: ':id',
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return VehicleDetailScreen(vehicleId: id);
            },
            routes: [
              GoRoute(
                path: 'edit',
                builder: (context, state) {
                  final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
                  // We get the vehicle from the provider
                  return Consumer(
                    builder: (context, ref, _) {
                      final vehicleState = ref.watch(vehicleDetailProvider(id));
                      return VehicleFormScreen(vehicle: vehicleState.vehicle);
                    },
                  );
                },
              ),
            ],
          ),
        ],
      ),
      
      // Drivers
      GoRoute(
        path: AppConstants.driversRoute,
        builder: (context, state) => const DriversListScreen(),
        routes: [
          GoRoute(
            path: 'add',
            builder: (context, state) => const DriverFormScreen(),
          ),
          GoRoute(
            path: ':id',
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return DriverDetailScreen(driverId: id);
            },
            routes: [
              GoRoute(
                path: 'edit',
                builder: (context, state) {
                  final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
                  return DriverFormScreen(driverId: id);
                },
              ),
            ],
          ),
        ],
      ),
      
      // Documents
      GoRoute(
        path: AppConstants.documentsRoute,
        builder: (context, state) => const DocumentsListScreen(),
        routes: [
          GoRoute(
            path: 'add',
            builder: (context, state) => const DocumentFormScreen(),
          ),
          GoRoute(
            path: ':id',
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return DocumentDetailScreen(documentId: id);
            },
            routes: [
              GoRoute(
                path: 'edit',
                builder: (context, state) {
                  final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
                  return DocumentFormScreen(documentId: id);
                },
              ),
            ],
          ),
        ],
      ),
      
      // Maintenance
      GoRoute(
        path: AppConstants.maintenanceRoute,
        builder: (context, state) => const MaintenanceListScreen(),
        routes: [
          GoRoute(
            path: 'new',
            builder: (context, state) => const MaintenanceFormScreen(),
          ),
          GoRoute(
            path: ':id',
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return MaintenanceDetailScreen(maintenanceId: id);
            },
            routes: [
              GoRoute(
                path: 'edit',
                builder: (context, state) {
                  final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
                  return MaintenanceFormScreen(maintenanceId: id);
                },
              ),
            ],
          ),
        ],
      ),
      
      // Fuel
      GoRoute(
        path: AppConstants.fuelRoute,
        builder: (context, state) => const FuelListScreen(),
        routes: [
          GoRoute(
            path: 'new',
            builder: (context, state) => const FuelFormScreen(),
          ),
          GoRoute(
            path: ':id',
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return FuelDetailScreen(fuelId: id);
            },
            routes: [
              GoRoute(
                path: 'edit',
                builder: (context, state) {
                  final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
                  return FuelFormScreen(fuelId: id);
                },
              ),
            ],
          ),
        ],
      ),
      
      // Insurance
      GoRoute(
        path: AppConstants.insuranceRoute,
        builder: (context, state) => const InsuranceListScreen(),
        routes: [
          GoRoute(
            path: 'new',
            builder: (context, state) => const InsuranceFormScreen(),
          ),
          GoRoute(
            path: ':id',
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return InsuranceDetailScreen(insuranceId: id);
            },
            routes: [
              GoRoute(
                path: 'edit',
                builder: (context, state) {
                  final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
                  return InsuranceFormScreen(insuranceId: id);
                },
              ),
            ],
          ),
        ],
      ),
      
      // Notifications
      GoRoute(
        path: AppConstants.notificationsRoute,
        builder: (context, state) => const NotificationsScreen(),
      ),
      
      // Reports
      GoRoute(
        path: AppConstants.reportsRoute,
        builder: (context, state) => const ReportsScreen(),
      ),
      
      // Settings
      GoRoute(
        path: AppConstants.settingsRoute,
        builder: (context, state) => const SettingsScreen(),
      ),
    ],
    errorBuilder: (context, state) => Scaffold(
      body: Center(
        child: Text('Pagină negăsită: ${state.error}'),
      ),
    ),
  );
});

/// Splash screen (shown while checking auth)
class _SplashScreen extends ConsumerWidget {
  const _SplashScreen();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return const Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.directions_car,
              size: 64,
              color: Color(0xFF2563EB),
            ),
            SizedBox(height: 16),
            Text(
              'Fleetly',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: Color(0xFF2563EB),
              ),
            ),
            SizedBox(height: 24),
            CircularProgressIndicator(),
          ],
        ),
      ),
    );
  }
}
