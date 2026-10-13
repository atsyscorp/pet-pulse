/// Errors the UI knows how to explain. Every message is ready to show.
sealed class ApiException implements Exception {
  const ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}

/// 401: the token is missing, expired or was revoked. Sign in again.
final class UnauthorizedException extends ApiException {
  const UnauthorizedException()
      : super('Tu sesión venció. Inicia sesión de nuevo.');
}

/// 404: the pet does not exist or this owner is no longer linked to it.
final class NotFoundException extends ApiException {
  const NotFoundException()
      : super('No encontramos esta mascota. Puede que la clínica haya retirado el acceso.');
}

/// 422: field-level validation errors from the server.
final class ValidationException extends ApiException {
  const ValidationException(super.message, this.errors);

  final Map<String, List<String>> errors;

  String? first(String field) => errors[field]?.firstOrNull;
}

/// No connection, DNS failure, timeout.
final class NetworkException extends ApiException {
  const NetworkException()
      : super('Sin conexión con la clínica. Revisa tu internet e intenta de nuevo.');
}

/// Anything else (429, 5xx, malformed response).
final class ServerException extends ApiException {
  const ServerException([
    super.message = 'La clínica no respondió como esperábamos. Intenta más tarde.',
  ]);
}
