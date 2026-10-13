import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:petpulse_owner/main.dart';
import 'package:petpulse_owner/src/api/api_client.dart';
import 'package:petpulse_owner/src/screens/link_pet_screen.dart';
import 'package:petpulse_owner/src/session.dart';

import 'fake_api.dart';

void main() {
  setUpAll(() => initializeDateFormatting('es'));

  Future<(FakeOwnerApi, Session)> boot(WidgetTester tester, {String? storedToken, bool linked = false}) async {
    tester.view.physicalSize = const Size(1080, 2340);
    tester.view.devicePixelRatio = 3;
    addTearDown(tester.view.reset);

    final server = FakeOwnerApi()..linked = linked;
    if (storedToken != null) server.issuedToken = storedToken;
    final session = Session(api: ApiClient(baseUrl: 'https://petpulse.test', httpClient: server.client), store: MemoryTokenStore(storedToken));
    await tester.pumpWidget(PetPulseApp(session: session));
    await session.restore();
    await tester.pumpAndSettle();
    return (server, session);
  }

  testWidgets('signs in, links a pet with the clinic code and reads its status', (tester) async {
    final (server, session) = await boot(tester);

    expect(find.text('Siga la hospitalización de su mascota'), findsOneWidget);

    await tester.tap(find.text('Ya tengo cuenta'));
    await tester.pumpAndSettle();
    await tester.enterText(find.widgetWithText(TextField, 'Correo electrónico'), 'maria@example.com');
    await tester.enterText(find.widgetWithText(TextField, 'Contraseña'), 'secreta123');
    await tester.tap(find.text('Entrar'));
    await tester.pumpAndSettle();

    expect(session.status, SessionStatus.signedIn);
    expect(find.text('Aún no tiene mascotas vinculadas'), findsOneWidget);

    // Link with a lowercase, dashless code: the formatter fixes it.
    await tester.tap(find.text('Vincular mascota'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField), 'abcd2345');
    await tester.pump();
    expect(tester.widget<TextField>(find.byType(TextField)).controller!.text, 'ABCD-2345');
    await tester.tap(find.text('Vincular'));
    await tester.pumpAndSettle();

    // Lands on the pet: status tab shows what the clinic chose to share.
    expect(find.text('Rocky'), findsWidgets);
    expect(find.text('Hospitalizado · Estable'), findsOneWidget);
    expect(find.text('Estable'), findsOneWidget);
    expect(find.text('32,4 kg'), findsOneWidget);

    await tester.tap(find.text('Partes médicos'));
    await tester.pumpAndSettle();
    expect(find.text('Hoy comió bien y está más activo.'), findsOneWidget);
    expect(find.textContaining('Dra. Restrepo · Veterinario'), findsOneWidget);

    await tester.tap(find.text('Medicación'));
    await tester.pumpAndSettle();
    expect(find.text('Ceftriaxona 1 g'), findsOneWidget);
    expect(find.text('Intravenosa'), findsOneWidget);
    expect(server.validCodes, isEmpty);
  });

  testWidgets('a wrong code shows the server message and stays on the screen', (tester) async {
    await boot(tester, storedToken: '9|stored');

    await tester.tap(find.text('Vincular mascota'));
    await tester.pumpAndSettle();
    await tester.enterText(find.byType(TextField), 'ZZZZ9999');
    await tester.tap(find.text('Vincular'));
    await tester.pumpAndSettle();

    expect(find.text('El código no es válido o ya venció. Pida uno nuevo en su clínica.'), findsOneWidget);
    expect(find.byType(LinkPetScreen), findsOneWidget);
  });

  testWidgets('a stored token is reused at launch, and a 401 sends the owner back to the welcome screen', (tester) async {
    final (server, session) = await boot(tester, storedToken: '9|stored', linked: true);

    expect(find.text('Hola, María'), findsOneWidget);
    expect(find.text('Rocky'), findsOneWidget);

    // Clinic revokes the link while the app is open; token also expires.
    server.issuedToken = 'rotated';
    await tester.tap(find.text('Rocky'));
    await tester.pumpAndSettle();
    await tester.fling(find.byType(ListView).first, const Offset(0, 400), 1000);
    await tester.pumpAndSettle();

    expect(session.status, SessionStatus.signedOut);
    expect(find.text('Siga la hospitalización de su mascota'), findsOneWidget);
  });

  testWidgets('registration requires consent to the data policy', (tester) async {
    await boot(tester);

    await tester.tap(find.text('Crear cuenta'));
    await tester.pumpAndSettle();
    await tester.enterText(find.widgetWithText(TextField, 'Nombre completo'), 'María López');
    await tester.enterText(find.widgetWithText(TextField, 'Correo electrónico'), 'maria@example.com');
    await tester.enterText(find.widgetWithText(TextField, 'Contraseña'), 'secreta123');
    await tester.ensureVisible(find.widgetWithText(FilledButton, 'Crear cuenta'));
    await tester.tap(find.widgetWithText(FilledButton, 'Crear cuenta'));
    await tester.pumpAndSettle();

    expect(find.text('Debe aceptar la política de tratamiento de datos personales.'), findsOneWidget);

    await tester.tap(find.byType(CheckboxListTile));
    await tester.tap(find.widgetWithText(FilledButton, 'Crear cuenta'));
    await tester.pumpAndSettle();
    expect(find.text('Aún no tiene mascotas vinculadas'), findsOneWidget);
  });
}
