import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

/// In-memory stand-in for /api/v1/owner with the same JSON shapes as Laravel.
class FakeOwnerApi {
  final Set<String> validCodes = {'ABCD2345'};
  bool linked = false;
  bool revoked = false;
  String? issuedToken;
  final List<http.Request> requests = [];

  static const _owner = {'id': 1, 'name': 'María López', 'email': 'maria@example.com', 'phone': null};

  static Map<String, dynamic> rocky({bool active = true, String? ownerStatus = 'stable', String? ownerStatusLabel = 'Estable'}) => {
        'id': 12,
        'name': 'Rocky',
        'species': 'Canino',
        'breed': 'Pastor Alemán',
        'sex': 'Macho',
        'birth_date': '2020-03-15',
        'age': '6 años',
        'microchip': '900182000123456',
        'clinic': {'id': 1, 'name': 'Clínica Los Andes'},
        'hospitalization': {
          'id': 40,
          'active': active,
          'status': active ? 'hospitalized' : 'closed',
          'status_label': active ? 'Hospitalizado' : 'Alta médica',
          'owner_status': ownerStatus,
          'owner_status_label': ownerStatusLabel,
          'owner_status_updated_at': '2026-10-13T14:20:00+00:00',
          'admitted_at': '2026-10-12T20:19:00+00:00',
          'discharged_at': active ? null : '2026-10-14T15:00:00+00:00',
          'weight_kg': 32.4,
          'discharge_notes': active ? null : 'Control en 5 días.',
        },
      };

  MockClient get client => MockClient((request) async {
        requests.add(request);
        final path = request.url.path.replaceFirst('/api/v1/owner', '');
        final auth = request.headers['Authorization'];
        final body = request.body.isEmpty ? <String, dynamic>{} : jsonDecode(request.body) as Map<String, dynamic>;

        if (path == '/login') {
          if (body['password'] != 'secreta123') {
            return _json(422, {'message': 'Correo o contraseña incorrectos.', 'errors': {'email': ['Correo o contraseña incorrectos.']}});
          }
          issuedToken = '1|token';
          return _json(200, {'token': issuedToken, 'token_type': 'Bearer', 'expires_at': null, 'owner': _owner});
        }
        if (path == '/register') {
          if (body['accept_terms'] != true) {
            return _json(422, {'message': 'Revise los datos.', 'errors': {'accept_terms': ['Debe aceptar la política de tratamiento de datos personales.']}});
          }
          issuedToken = '2|token';
          return _json(201, {'token': issuedToken, 'token_type': 'Bearer', 'expires_at': null, 'owner': _owner});
        }

        if (auth == null || auth != 'Bearer $issuedToken') return _json(401, {'message': 'Unauthenticated.'});

        return switch (path) {
          '/me' => _json(200, {'data': _owner}),
          '/logout' => _json(200, {'message': 'Sesión cerrada.'}),
          '/pets' => _json(200, {'data': linked && !revoked ? [rocky()] : []}),
          '/pets/link' => validCodes.remove(body['code'])
              ? (() {
                  linked = true;
                  return _json(201, {'data': rocky()});
                })()
              : _json(422, {'message': 'El código no es válido o ya venció. Pida uno nuevo en su clínica.', 'errors': {'code': ['El código no es válido o ya venció. Pida uno nuevo en su clínica.']}}),
          '/pets/12' => linked && !revoked ? _json(200, {'data': rocky()}) : _json(404, {'message': 'Not found'}),
          '/pets/12/updates' => _json(200, {
              'data': [
                {'id': 2, 'hospitalization_id': 40, 'body': 'Hoy comió bien y está más activo.', 'author': 'Dra. Restrepo', 'author_role': 'Veterinario', 'created_at': '2026-10-13T15:00:00+00:00'},
              ],
              'meta': {'current_page': 1, 'last_page': 1},
            }),
          '/pets/12/medications' => _json(200, {
              'data': [
                {'id': 9, 'drug': 'Ceftriaxona 1 g', 'active_ingredient': 'Ceftriaxona', 'route': 'IV', 'route_label': 'Intravenosa', 'administered_at': '2026-10-13T13:02:00+00:00'},
              ],
              'meta': {'current_page': 1, 'last_page': 1},
            }),
          _ => _json(404, {'message': 'Not found'}),
        };
      });

  static http.Response _json(int status, Object body) =>
      http.Response(jsonEncode(body), status, headers: {'content-type': 'application/json; charset=utf-8'});
}
