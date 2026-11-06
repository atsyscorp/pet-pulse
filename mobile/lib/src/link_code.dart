/// Link codes handed out by the clinic: "ABCD-2345" on paper, "PETPULSE:ABCD2345" in the QR.
/// Mirrors App\Services\OwnerApp\LinkCode on the server.
abstract final class LinkCode {
  static const int length = 8;
  static const String qrPrefix = 'PETPULSE:';

  /// "abcd 2345", "ABCD-2345" and "PETPULSE:ABCD2345" all become "ABCD2345".
  static String normalize(String input) {
    var value = input.trim().toUpperCase();
    if (value.startsWith(qrPrefix)) value = value.substring(qrPrefix.length);
    return value.replaceAll(RegExp('[^A-Z0-9]'), '');
  }

  static bool isComplete(String input) => normalize(input).length == length;

  /// Code from a scanned QR, or null if the QR is not a PetPulse code.
  static String? fromQr(String? raw) {
    if (raw == null || !raw.trim().toUpperCase().startsWith(qrPrefix)) return null;
    final code = normalize(raw);
    return code.length == length ? code : null;
  }

  static String display(String input) {
    final code = normalize(input);
    return code.length <= 4 ? code : '${code.substring(0, 4)}-${code.substring(4)}';
  }
}
