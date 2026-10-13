import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:petpulse_owner/src/api/api_client.dart';
import 'package:petpulse_owner/src/api/api_exception.dart';

import 'fake_api.dart';

void main() {
  late FakeOwnerApi server;
  late ApiClient api;

  setUp(() {
    server = FakeOwnerApi();
    api = ApiClient(baseUrl: 'https://petpulse.test/', httpClient: server.client);
  });

  test('login returns the token and the owner, and later calls send it', () async {
    final result = await api.login(email: 'maria@example.com', password: 'secreta123', deviceName: 'test');
    expect(result.token, '1|token');
    expect(result.owner.name, 'María López');

    api.token = result.token;
    await api.me();
    expect(server.requests.last.url.toString(), 'https://petpulse.test/api/v1/owner/me');
    expect(server.requests.last.headers['Authorization'], 'Bearer 1|token');
    expect(server.requests.last.headers['Accept'], 'application/json');
  });

  test('422 becomes a ValidationException with field messages', () async {
    expect(
      () => api.login(email: 'maria@example.com', password: 'mala', deviceName: 'test'),
      throwsA(isA<ValidationException>().having((e) => e.first('email'), 'email', 'Correo o contraseña incorrectos.')),
    );
  });

  test('401 becomes UnauthorizedException', () async {
    expect(() => api.pets(), throwsA(isA<UnauthorizedException>()));
  });

  test('network failures and HTML error pages become friendly errors', () async {
    final offline = ApiClient(baseUrl: 'https://x.test', httpClient: MockClient((_) => throw http.ClientException('down')));
    expect(() => offline.pets(), throwsA(isA<NetworkException>()));

    final html = ApiClient(baseUrl: 'https://x.test', httpClient: MockClient((_) async => http.Response('<html>502</html>', 502)));
    expect(() => html.pets(), throwsA(isA<ServerException>()));
  });

  test('parses pets, pages of updates and medications', () async {
    api.token = (await api.login(email: 'maria@example.com', password: 'secreta123', deviceName: 't')).token;
    final pet = await api.linkPet('ABCD2345');
    expect(pet.name, 'Rocky');
    expect(pet.isHospitalized, isTrue);
    expect(pet.stay!.ownerStatusLabel, 'Estable');
    expect(pet.stay!.weightKg, 32.4);

    final updates = await api.updates(12);
    expect(updates.items.single.body, 'Hoy comió bien y está más activo.');
    expect(updates.hasMore, isFalse);

    final meds = await api.medications(12);
    expect(meds.items.single.routeLabel, 'Intravenosa');
  });
}
