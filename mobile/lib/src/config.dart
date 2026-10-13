/// Build-time configuration.
///
/// The API base URL is injected at build time so the same code targets a
/// local server, staging or production:
///
///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000
///   flutter build appbundle --dart-define=API_BASE_URL=https://petpulse.atsys.co
library;

const String apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'https://petpulse.atsys.co',
);
