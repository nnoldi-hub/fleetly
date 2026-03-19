// Basic Flutter widget test for Fleetly Mobile App
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fleetly_mobile/main.dart';

void main() {
  testWidgets('FleetlyApp should build without errors', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(
      const ProviderScope(
        child: FleetlyApp(),
      ),
    );

    // Verify app title exists
    expect(find.text('Fleetly'), findsWidgets);
  });
}
