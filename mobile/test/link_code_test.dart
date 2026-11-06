import 'package:flutter_test/flutter_test.dart';
import 'package:petpulse_owner/src/link_code.dart';

void main() {
  test('normalizes typed and scanned codes the same way the server does', () {
    expect(LinkCode.normalize('abcd-2345'), 'ABCD2345');
    expect(LinkCode.normalize(' ABCD 2345 '), 'ABCD2345');
    expect(LinkCode.normalize('PETPULSE:ABCD2345'), 'ABCD2345');
    expect(LinkCode.display('abcd2345'), 'ABCD-2345');
    expect(LinkCode.isComplete('ABCD-234'), isFalse);
    expect(LinkCode.isComplete('abcd-2345'), isTrue);
  });

  test('only PetPulse QRs are accepted from the scanner', () {
    expect(LinkCode.fromQr('PETPULSE:ABCD2345'), 'ABCD2345');
    expect(LinkCode.fromQr('https://example.com'), isNull);
    expect(LinkCode.fromQr('ABCD2345'), isNull);
    expect(LinkCode.fromQr('PETPULSE:ABC'), isNull);
    expect(LinkCode.fromQr(null), isNull);
  });
}
