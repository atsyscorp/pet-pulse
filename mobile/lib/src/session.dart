import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'api/api_client.dart';
import 'api/api_exception.dart';
import 'models.dart';

/// Where the token lives. Keychain / Keystore in the app; memory in tests.
abstract interface class TokenStore {
  Future<String?> read();
  Future<void> write(String token);
  Future<void> delete();
}

class SecureTokenStore implements TokenStore {
  const SecureTokenStore();

  static const _key = 'petpulse_owner_token';
  static const _storage = FlutterSecureStorage();

  @override
  Future<String?> read() => _storage.read(key: _key);

  @override
  Future<void> write(String token) => _storage.write(key: _key, value: token);

  @override
  Future<void> delete() => _storage.delete(key: _key);
}

class MemoryTokenStore implements TokenStore {
  MemoryTokenStore([this._token]);

  String? _token;

  @override
  Future<String?> read() async => _token;

  @override
  Future<void> write(String token) async => _token = token;

  @override
  Future<void> delete() async => _token = null;
}

enum SessionStatus { restoring, signedOut, signedIn }

/// Signed-in state of the app. Screens listen to it; when any request comes
/// back 401 they call [expire] and the app returns to the welcome screen.
class Session extends ChangeNotifier {
  Session({required this.api, required this.store, this.deviceName = 'App PetPulse'});

  final ApiClient api;
  final TokenStore store;
  final String deviceName;

  SessionStatus status = SessionStatus.restoring;
  Owner? owner;

  /// On launch: reuse a stored token if the server still accepts it.
  Future<void> restore() async {
    final token = await store.read();
    if (token == null) return _signedOut();

    api.token = token;
    try {
      owner = await api.me();
      status = SessionStatus.signedIn;
      notifyListeners();
    } on UnauthorizedException {
      await store.delete();
      _signedOut();
    } on ApiException {
      // Offline at launch: keep the token and let the user in; screens retry.
      status = SessionStatus.signedIn;
      notifyListeners();
    }
  }

  Future<void> login(String email, String password) async {
    final result = await api.login(email: email.trim(), password: password, deviceName: deviceName);
    await _signedIn(result.token, result.owner);
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
    required bool acceptTerms,
    String? phone,
  }) async {
    final result = await api.register(
      name: name.trim(),
      email: email.trim(),
      phone: phone?.trim(),
      password: password,
      acceptTerms: acceptTerms,
      deviceName: deviceName,
    );
    await _signedIn(result.token, result.owner);
  }

  Future<void> logout() async {
    try {
      await api.logout();
    } on ApiException {
      // Already invalid or offline: forget it locally anyway.
    }
    await expire();
  }

  /// The server rejected the token (expired, revoked, logged out elsewhere).
  Future<void> expire() async {
    await store.delete();
    _signedOut();
  }

  Future<void> _signedIn(String token, Owner who) async {
    await store.write(token);
    api.token = token;
    owner = who;
    status = SessionStatus.signedIn;
    notifyListeners();
  }

  void _signedOut() {
    api.token = null;
    owner = null;
    status = SessionStatus.signedOut;
    notifyListeners();
  }
}
