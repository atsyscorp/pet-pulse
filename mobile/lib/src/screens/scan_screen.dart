import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../link_code.dart';

/// Camera view that pops with the first PetPulse link code it sees.
class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  final _controller = MobileScannerController(formats: const [BarcodeFormat.qrCode]);
  bool _done = false;
  bool _foreignQr = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _onDetect(BarcodeCapture capture) {
    if (_done) return;
    for (final barcode in capture.barcodes) {
      final code = LinkCode.fromQr(barcode.rawValue);
      if (code != null) {
        _done = true;
        Navigator.of(context).pop(code);
        return;
      }
    }
    if (!_foreignQr) setState(() => _foreignQr = true);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        title: const Text('Escanear QR'),
        backgroundColor: Colors.black,
        foregroundColor: Colors.white,
        titleTextStyle: const TextStyle(fontFamily: 'Outfit', fontSize: 20, fontWeight: FontWeight.w700, color: Colors.white),
      ),
      body: Stack(fit: StackFit.expand, children: [
        MobileScanner(
          controller: _controller,
          onDetect: _onDetect,
          errorBuilder: (context, error) => Center(
            child: Padding(
              padding: const EdgeInsets.all(32),
              child: Text(
                error.errorCode == MobileScannerErrorCode.permissionDenied
                    ? 'Sin permiso para usar la cámara. Actívalo en los ajustes del teléfono o escribe el código.'
                    : 'No se pudo abrir la cámara. Escribe el código a mano.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.white, fontSize: 16),
              ),
            ),
          ),
        ),
        Center(
          child: Container(
            width: 240,
            height: 240,
            decoration: BoxDecoration(border: Border.all(color: Colors.white, width: 3), borderRadius: BorderRadius.circular(24)),
          ),
        ),
        Positioned(
          left: 24,
          right: 24,
          bottom: 48,
          child: Text(
            _foreignQr ? 'Ese QR no es de PetPulse. Apunta al QR que te dio la clínica.' : 'Apunta al QR que te entregó la clínica.',
            textAlign: TextAlign.center,
            style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w600),
          ),
        ),
      ]),
    );
  }
}
