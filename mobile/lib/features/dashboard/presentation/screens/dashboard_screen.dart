import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:fleetly_mobile/core/constants/app_constants.dart';
import 'package:fleetly_mobile/core/theme/app_theme.dart';
import 'package:fleetly_mobile/features/auth/presentation/providers/auth_provider.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authNotifierProvider);
    final user = authState.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {
              context.push(AppConstants.notificationsRoute);
            },
          ),
          PopupMenuButton<String>(
            icon: CircleAvatar(
              backgroundColor: AppTheme.primaryColor,
              radius: 16,
              child: Text(
                user?.initials ?? 'U',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            onSelected: (value) async {
              if (value == 'logout') {
                await ref.read(authNotifierProvider.notifier).logout();
              } else if (value == 'profile') {
                context.push(AppConstants.settingsRoute);
              } else if (value == 'settings') {
                context.push(AppConstants.settingsRoute);
              }
            },
            itemBuilder: (context) => <PopupMenuEntry<String>>[
              const PopupMenuItem<String>(
                value: 'profile',
                child: ListTile(
                  leading: Icon(Icons.person_outline),
                  title: Text('Profil'),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
              const PopupMenuItem<String>(
                value: 'settings',
                child: ListTile(
                  leading: Icon(Icons.settings_outlined),
                  title: Text('Setări'),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
              const PopupMenuDivider(),
              const PopupMenuItem<String>(
                value: 'logout',
                child: ListTile(
                  leading: Icon(Icons.logout, color: AppTheme.errorColor),
                  title: Text('Deconectare', style: TextStyle(color: AppTheme.errorColor)),
                  contentPadding: EdgeInsets.zero,
                ),
              ),
            ],
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          // TODO: Refresh dashboard data
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Welcome message
              Text(
                'Bună ziua, ${user?.fullName ?? 'Utilizator'}!',
                style: Theme.of(context).textTheme.headlineSmall,
              ),
              const SizedBox(height: 4),
              Text(
                user?.companyName ?? 'Fleet Management',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 24),

              // Stats cards
              GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 1.4,
                children: [
                  _StatCard(
                    icon: Icons.directions_car,
                    title: 'Vehicule',
                    value: '0',
                    color: AppTheme.primaryColor,
                    onTap: () => context.push(AppConstants.vehiclesRoute),
                  ),
                  _StatCard(
                    icon: Icons.person,
                    title: 'Șoferi',
                    value: '0',
                    color: AppTheme.secondaryColor,
                    onTap: () => context.push(AppConstants.driversRoute),
                  ),
                  _StatCard(
                    icon: Icons.description,
                    title: 'Documente',
                    value: '0',
                    subtitle: '0 expiră curând',
                    color: AppTheme.warningColor,
                    onTap: () => context.push(AppConstants.documentsRoute),
                  ),
                  _StatCard(
                    icon: Icons.build,
                    title: 'Mentenanță',
                    value: '0',
                    subtitle: 'programate',
                    color: AppTheme.infoColor,
                    onTap: () => context.push(AppConstants.maintenanceRoute),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // Quick actions
              Text(
                'Acțiuni rapide',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _QuickActionButton(
                      icon: Icons.add_circle_outline,
                      label: 'Adaugă vehicul',
                      onTap: () {
                        context.push('${AppConstants.vehiclesRoute}/add');
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionButton(
                      icon: Icons.local_gas_station,
                      label: 'Alimentare',
                      onTap: () {
                        context.push('${AppConstants.fuelRoute}/add');
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionButton(
                      icon: Icons.build_circle_outlined,
                      label: 'Service',
                      onTap: () {
                        context.push('${AppConstants.maintenanceRoute}/new');
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),

              // Alerts section
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Alerte',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  TextButton(
                    onPressed: () {
                      context.push(AppConstants.notificationsRoute);
                    },
                    child: const Text('Vezi toate'),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      Icon(
                        Icons.check_circle_outline,
                        size: 48,
                        color: AppTheme.successColor.withValues(alpha: 0.5),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Nu există alerte active',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 0,
        onTap: (index) {
          switch (index) {
            case 0:
              // Already on dashboard
              break;
            case 1:
              context.push(AppConstants.vehiclesRoute);
              break;
            case 2:
              context.push(AppConstants.driversRoute);
              break;
            case 3:
              showModalBottomSheet(
                context: context,
                builder: (context) => _MoreMenuSheet(),
              );
              break;
          }
        },
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.dashboard_outlined),
            activeIcon: Icon(Icons.dashboard),
            label: 'Dashboard',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.directions_car_outlined),
            activeIcon: Icon(Icons.directions_car),
            label: 'Vehicule',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person_outline),
            activeIcon: Icon(Icons.person),
            label: 'Șoferi',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.more_horiz),
            activeIcon: Icon(Icons.more_horiz),
            label: 'Mai mult',
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String value;
  final String? subtitle;
  final Color color;
  final VoidCallback? onTap;

  const _StatCard({
    required this.icon,
    required this.title,
    required this.value,
    this.subtitle,
    required this.color,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(icon, color: color, size: 20),
                  ),
                  Text(
                    value,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  if (subtitle != null)
                    Text(
                      subtitle!,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QuickActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;

  const _QuickActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          border: Border.all(color: AppTheme.borderColor),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Icon(icon, color: AppTheme.primaryColor),
            const SizedBox(height: 4),
            Text(
              label,
              style: Theme.of(context).textTheme.bodySmall,
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _MoreMenuSheet extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.description_outlined),
              title: const Text('Documente'),
              onTap: () {
                Navigator.pop(context);
                context.push(AppConstants.documentsRoute);
              },
            ),
            ListTile(
              leading: const Icon(Icons.build_outlined),
              title: const Text('Mentenanță'),
              onTap: () {
                Navigator.pop(context);
                context.push(AppConstants.maintenanceRoute);
              },
            ),
            ListTile(
              leading: const Icon(Icons.local_gas_station_outlined),
              title: const Text('Combustibil'),
              onTap: () {
                Navigator.pop(context);
                context.push(AppConstants.fuelRoute);
              },
            ),
            ListTile(
              leading: const Icon(Icons.security_outlined),
              title: const Text('Asigurări'),
              onTap: () {
                Navigator.pop(context);
                context.push(AppConstants.insuranceRoute);
              },
            ),
            ListTile(
              leading: const Icon(Icons.bar_chart_outlined),
              title: const Text('Rapoarte'),
              onTap: () {
                Navigator.pop(context);
                context.push(AppConstants.reportsRoute);
              },
            ),
            ListTile(
              leading: const Icon(Icons.settings_outlined),
              title: const Text('Setări'),
              onTap: () {
                Navigator.pop(context);
                context.push(AppConstants.settingsRoute);
              },
            ),
          ],
        ),
      ),
    );
  }
}
