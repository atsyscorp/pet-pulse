import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';

import 'src/api/api_client.dart';
import 'src/config.dart';
import 'src/screens/pets_screen.dart';
import 'src/screens/welcome_screen.dart';
import 'src/session.dart';
import 'src/theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('es');

  final session = Session(api: ApiClient(baseUrl: apiBaseUrl), store: const SecureTokenStore());
  runApp(PetPulseApp(session: session));
  await session.restore();
}

class PetPulseApp extends StatelessWidget {
  const PetPulseApp({super.key, required this.session});

  final Session session;

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider.value(
      value: session,
      child: MaterialApp(
        title: 'PetPulse',
        debugShowCheckedModeBanner: false,
        theme: petPulseTheme(Brightness.light),
        darkTheme: petPulseTheme(Brightness.dark),
        locale: const Locale('es'),
        supportedLocales: const [Locale('es')],
        localizationsDelegates: GlobalMaterialLocalizations.delegates,
        home: const _Root(),
      ),
    );
  }
}

/// Switches between the signed-out and signed-in worlds. Rebuilding with a
/// new key drops every route, so a sign-out (or an expired token) never leaves
/// a pet screen behind.
class _Root extends StatelessWidget {
  const _Root();

  @override
  Widget build(BuildContext context) {
    final status = context.select<Session, SessionStatus>((s) => s.status);

    return switch (status) {
      SessionStatus.restoring => const Scaffold(body: Center(child: CircularProgressIndicator())),
      SessionStatus.signedOut => const _Area(key: ValueKey('out'), child: WelcomeScreen()),
      SessionStatus.signedIn => const _Area(key: ValueKey('in'), child: PetsScreen()),
    };
  }
}

class _Area extends StatelessWidget {
  const _Area({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) => Navigator(onGenerateRoute: (_) => MaterialPageRoute(builder: (_) => child));
}
