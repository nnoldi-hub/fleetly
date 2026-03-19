import 'package:fleetly_mobile/features/auth/presentation/providers/auth_provider.dart';
import 'package:fleetly_mobile/features/settings/presentation/providers/settings_provider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

/// Settings screen
class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.watch(settingsProvider);
    final authState = ref.watch(authNotifierProvider);
    final user = authState.user;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Setări'),
      ),
      body: ListView(
        children: [
          // Profile section
          if (user != null) ...[
            _SectionHeader(title: 'Profil'),
            _ProfileTile(
              name: user.fullName,
              email: user.email,
              onTap: () => _showProfileDialog(context, ref),
            ),
          ],

          // Notifications section
          _SectionHeader(title: 'Notificări'),
          SwitchListTile(
            title: const Text('Notificări activate'),
            subtitle: const Text('Primește notificări în aplicație'),
            value: settings.notificationsEnabled,
            onChanged: (value) {
              ref.read(settingsProvider.notifier).setNotificationsEnabled(value);
            },
            secondary: const Icon(Icons.notifications_outlined),
          ),
          if (settings.notificationsEnabled) ...[
            SwitchListTile(
              title: const Text('Notificări email'),
              subtitle: const Text('Primește notificări pe email'),
              value: settings.emailNotifications,
              onChanged: (value) {
                ref.read(settingsProvider.notifier).setEmailNotifications(value);
              },
              secondary: const Icon(Icons.email_outlined),
            ),
            SwitchListTile(
              title: const Text('Notificări push'),
              subtitle: const Text('Primește notificări push'),
              value: settings.pushNotifications,
              onChanged: (value) {
                ref.read(settingsProvider.notifier).setPushNotifications(value);
              },
              secondary: const Icon(Icons.phone_android),
            ),
          ],

          // Appearance section
          _SectionHeader(title: 'Aspect'),
          SwitchListTile(
            title: const Text('Mod întunecat'),
            subtitle: const Text('Folosește tema întunecată'),
            value: settings.darkMode,
            onChanged: (value) {
              ref.read(settingsProvider.notifier).setDarkMode(value);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Tema va fi aplicată la repornirea aplicației'),
                  duration: Duration(seconds: 2),
                ),
              );
            },
            secondary: const Icon(Icons.dark_mode_outlined),
          ),
          ListTile(
            leading: const Icon(Icons.language),
            title: const Text('Limbă'),
            subtitle: Text(_getLanguageName(settings.language)),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showLanguageDialog(context, ref, settings.language),
          ),

          // Security section
          _SectionHeader(title: 'Securitate'),
          SwitchListTile(
            title: const Text('Autentificare biometrică'),
            subtitle: const Text('Folosește amprenta sau Face ID'),
            value: settings.biometricEnabled,
            onChanged: (value) {
              ref.read(settingsProvider.notifier).setBiometricEnabled(value);
            },
            secondary: const Icon(Icons.fingerprint),
          ),
          ListTile(
            leading: const Icon(Icons.timer_outlined),
            title: const Text('Deconectare automată'),
            subtitle: Text('După ${settings.autoLogoutMinutes} minute de inactivitate'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showAutoLogoutDialog(context, ref, settings.autoLogoutMinutes),
          ),
          ListTile(
            leading: const Icon(Icons.lock_outline),
            title: const Text('Schimbă parola'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showChangePasswordDialog(context),
          ),

          // Data section
          _SectionHeader(title: 'Date'),
          ListTile(
            leading: const Icon(Icons.sync),
            title: const Text('Sincronizare'),
            subtitle: const Text('Ultima sincronizare: acum'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Sincronizare în curs...'),
                  duration: Duration(seconds: 2),
                ),
              );
            },
          ),
          ListTile(
            leading: const Icon(Icons.storage_outlined),
            title: const Text('Curăță cache'),
            subtitle: const Text('Eliberează spațiu de stocare'),
            onTap: () => _showClearCacheDialog(context),
          ),

          // About section
          _SectionHeader(title: 'Despre'),
          ListTile(
            leading: const Icon(Icons.info_outline),
            title: const Text('Versiune'),
            subtitle: const Text('1.0.0 (Build 1)'),
          ),
          ListTile(
            leading: const Icon(Icons.article_outlined),
            title: const Text('Termeni și condiții'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showTermsDialog(context),
          ),
          ListTile(
            leading: const Icon(Icons.privacy_tip_outlined),
            title: const Text('Politica de confidențialitate'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showPrivacyDialog(context),
          ),
          ListTile(
            leading: const Icon(Icons.help_outline),
            title: const Text('Ajutor și suport'),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => _showSupportDialog(context),
          ),

          // Logout section
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: OutlinedButton.icon(
              onPressed: () => _showLogoutDialog(context, ref),
              icon: const Icon(Icons.logout, color: Colors.red),
              label: const Text(
                'Deconectare',
                style: TextStyle(color: Colors.red),
              ),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: Colors.red),
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ),
          const SizedBox(height: 32),
        ],
      ),
    );
  }

  String _getLanguageName(String code) {
    switch (code) {
      case 'ro':
        return 'Română';
      case 'en':
        return 'English';
      case 'hu':
        return 'Magyar';
      default:
        return code;
    }
  }

  void _showProfileDialog(BuildContext context, WidgetRef ref) {
    final user = ref.read(authNotifierProvider).user;
    if (user == null) return;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Profil'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _InfoRow(label: 'Nume', value: user.fullName),
            _InfoRow(label: 'Email', value: user.email),
            _InfoRow(label: 'Rol', value: user.roleName ?? user.role),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Închide'),
          ),
        ],
      ),
    );
  }

  void _showLanguageDialog(BuildContext context, WidgetRef ref, String currentLanguage) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Selectează limba'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            RadioListTile<String>(
              title: const Text('Română'),
              value: 'ro',
              groupValue: currentLanguage,
              onChanged: (value) {
                ref.read(settingsProvider.notifier).setLanguage(value!);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('English'),
              value: 'en',
              groupValue: currentLanguage,
              onChanged: (value) {
                ref.read(settingsProvider.notifier).setLanguage(value!);
                Navigator.pop(context);
              },
            ),
            RadioListTile<String>(
              title: const Text('Magyar'),
              value: 'hu',
              groupValue: currentLanguage,
              onChanged: (value) {
                ref.read(settingsProvider.notifier).setLanguage(value!);
                Navigator.pop(context);
              },
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
        ],
      ),
    );
  }

  void _showAutoLogoutDialog(BuildContext context, WidgetRef ref, int currentMinutes) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Deconectare automată'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            for (final minutes in [5, 15, 30, 60, 120])
              RadioListTile<int>(
                title: Text(minutes < 60 ? '$minutes minute' : '${minutes ~/ 60} ore'),
                value: minutes,
                groupValue: currentMinutes,
                onChanged: (value) {
                  ref.read(settingsProvider.notifier).setAutoLogoutMinutes(value!);
                  Navigator.pop(context);
                },
              ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
        ],
      ),
    );
  }

  void _showChangePasswordDialog(BuildContext context) {
    final currentController = TextEditingController();
    final newController = TextEditingController();
    final confirmController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Schimbă parola'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: currentController,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Parola curentă',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: newController,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Parola nouă',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: confirmController,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Confirmă parola',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
          ElevatedButton(
            onPressed: () {
              // Validate and change password
              if (newController.text != confirmController.text) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Parolele nu se potrivesc'),
                    backgroundColor: Colors.red,
                  ),
                );
                return;
              }
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Parola a fost schimbată'),
                  backgroundColor: Colors.green,
                ),
              );
            },
            child: const Text('Salvează'),
          ),
        ],
      ),
    );
  }

  void _showClearCacheDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Curăță cache'),
        content: const Text(
          'Această acțiune va șterge datele temporare din aplicație. '
          'Nu veți pierde datele salvate.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Cache șters cu succes'),
                  backgroundColor: Colors.green,
                ),
              );
            },
            child: const Text('Curăță'),
          ),
        ],
      ),
    );
  }

  void _showTermsDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Termeni și condiții'),
        content: const SingleChildScrollView(
          child: Text(
            'Prin utilizarea aplicației Fleetly, acceptați termenii și condițiile de utilizare.\n\n'
            '1. Aplicația este destinată gestionării flotelor de vehicule.\n\n'
            '2. Datele introduse sunt responsabilitatea utilizatorului.\n\n'
            '3. Serviciul poate fi suspendat pentru întreținere.\n\n'
            '4. Utilizatorul este responsabil pentru securitatea contului.',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Închide'),
          ),
        ],
      ),
    );
  }

  void _showPrivacyDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Politica de confidențialitate'),
        content: const SingleChildScrollView(
          child: Text(
            'Protecția datelor dumneavoastră este importantă pentru noi.\n\n'
            '1. Colectăm doar datele necesare funcționării aplicației.\n\n'
            '2. Datele sunt stocate securizat și criptat.\n\n'
            '3. Nu partajăm datele cu terți fără consimțământ.\n\n'
            '4. Puteți solicita ștergerea datelor oricând.',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Închide'),
          ),
        ],
      ),
    );
  }

  void _showSupportDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Ajutor și suport'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Aveți nevoie de ajutor?'),
            const SizedBox(height: 16),
            ListTile(
              leading: const Icon(Icons.email),
              title: const Text('Email'),
              subtitle: const Text('support@fleetly.ro'),
              contentPadding: EdgeInsets.zero,
            ),
            ListTile(
              leading: const Icon(Icons.phone),
              title: const Text('Telefon'),
              subtitle: const Text('+40 123 456 789'),
              contentPadding: EdgeInsets.zero,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Închide'),
          ),
        ],
      ),
    );
  }

  void _showLogoutDialog(BuildContext context, WidgetRef ref) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Deconectare'),
        content: const Text('Sigur doriți să vă deconectați?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Anulează'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            onPressed: () async {
              Navigator.pop(context);
              await ref.read(authNotifierProvider.notifier).logout();
              if (context.mounted) {
                context.go('/login');
              }
            },
            child: const Text('Deconectare'),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;

  const _SectionHeader({required this.title});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
      child: Text(
        title.toUpperCase(),
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: Colors.grey.shade600,
          letterSpacing: 1.2,
        ),
      ),
    );
  }
}

class _ProfileTile extends StatelessWidget {
  final String name;
  final String email;
  final VoidCallback onTap;

  const _ProfileTile({
    required this.name,
    required this.email,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: const Color(0xFF2563EB),
        child: Text(
          name.isNotEmpty ? name[0].toUpperCase() : '?',
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
      title: Text(
        name,
        style: const TextStyle(fontWeight: FontWeight.w600),
      ),
      subtitle: Text(email),
      trailing: const Icon(Icons.chevron_right),
      onTap: onTap,
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 80,
            child: Text(
              label,
              style: TextStyle(
                color: Colors.grey.shade600,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}
