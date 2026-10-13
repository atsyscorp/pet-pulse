import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models.dart';
import 'api_exception.dart';

/// Thin, typed client for /api/v1/owner. All HTTP and error mapping lives here
/// so screens only deal with models and [ApiException]s.
class ApiClient {
  ApiClient({required String baseUrl, http.Client? httpClient})
      : _base = Uri.parse(baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl),
        _http = httpClient ?? http.Client();

  final Uri _base;
  final http.Client _http;

  static const Duration _timeout = Duration(seconds: 20);

  /// Bearer token of the signed-in owner, or null.
  String? token;

  // ───────────── Auth ─────────────

  Future<({String token, Owner owner})> register({
    required String name,
    required String email,
    required String password,
    required bool acceptTerms,
    required String deviceName,
    String? phone,
  }) async {
    final json = await _send('POST', '/register', body: {
      'name': name,
      'email': email,
      if (phone != null && phone.isNotEmpty) 'phone': phone,
      'password': password,
      'accept_terms': acceptTerms,
      'device_name': deviceName,
    });
    return (token: json['token'] as String, owner: Owner.fromJson(json['owner'] as Map<String, dynamic>));
  }

  Future<({String token, Owner owner})> login({required String email, required String password, required String deviceName}) async {
    final json = await _send('POST', '/login', body: {'email': email, 'password': password, 'device_name': deviceName});
    return (token: json['token'] as String, owner: Owner.fromJson(json['owner'] as Map<String, dynamic>));
  }

  Future<void> logout() => _send('POST', '/logout');

  Future<Owner> me() async => Owner.fromJson((await _send('GET', '/me'))['data'] as Map<String, dynamic>);

  // ───────────── Pets ─────────────

  Future<List<Pet>> pets() async {
    final json = await _send('GET', '/pets');
    return (json['data'] as List).cast<Map<String, dynamic>>().map(Pet.fromJson).toList();
  }

  Future<Pet> pet(int id) async => Pet.fromJson((await _send('GET', '/pets/$id'))['data'] as Map<String, dynamic>);

  Future<Pet> linkPet(String code) async =>
      Pet.fromJson((await _send('POST', '/pets/link', body: {'code': code}))['data'] as Map<String, dynamic>);

  Future<PageOf<OwnerUpdate>> updates(int petId, {int page = 1}) async =>
      PageOf.fromJson(await _send('GET', '/pets/$petId/updates?page=$page'), OwnerUpdate.fromJson);

  Future<PageOf<Medication>> medications(int petId, {int page = 1}) async =>
      PageOf.fromJson(await _send('GET', '/pets/$petId/medications?page=$page'), Medication.fromJson);

  // ───────────── Transport ─────────────

  Future<Map<String, dynamic>> _send(String method, String path, {Map<String, dynamic>? body}) async {
    final request = http.Request(method, Uri.parse('$_base/api/v1/owner$path'))
      ..headers.addAll({
        'Accept': 'application/json',
        if (body != null) 'Content-Type': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      });
    if (body != null) request.body = jsonEncode(body);

    final http.Response response;
    try {
      response = await http.Response.fromStream(await _http.send(request).timeout(_timeout));
    } on TimeoutException {
      throw const NetworkException();
    } on http.ClientException {
      throw const NetworkException();
    }

    final decoded = _decode(response);

    switch (response.statusCode) {
      case >= 200 && < 300:
        return decoded;
      case 401:
        throw const UnauthorizedException();
      case 404:
        throw const NotFoundException();
      case 422:
        final errors = (decoded['errors'] as Map<String, dynamic>? ?? const {})
            .map((field, messages) => MapEntry(field, (messages as List).cast<String>()));
        throw ValidationException(decoded['message'] as String? ?? 'Revisa los datos.', errors);
      case 429:
        throw const ServerException('Demasiados intentos. Espera un momento e intenta de nuevo.');
      default:
        throw const ServerException();
    }
  }

  Map<String, dynamic> _decode(http.Response response) {
    if (response.body.isEmpty) return const {};
    try {
      final value = jsonDecode(utf8.decode(response.bodyBytes));
      return value is Map<String, dynamic> ? value : const {};
    } on FormatException {
      // An HTML error page (proxy, maintenance mode) instead of JSON.
      if (response.statusCode >= 200 && response.statusCode < 300) throw const ServerException();
      return const {};
    }
  }
}
