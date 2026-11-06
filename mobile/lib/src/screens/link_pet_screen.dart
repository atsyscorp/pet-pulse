import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../link_code.dart';
import '../models.dart';
import '../session.dart';
import '../widgets/common.dart';
import 'scan_screen.dart';

/// Redeem the clinic's code, typed or scanned. Pops with the linked [Pet].
class LinkPetScreen extends StatefulWidget {
  const LinkPetScreen({super.key, this.scanner});

  /// Overridable in tests (no camera there).
  final Future<String?> Function(BuildContext context)? scanner;

  @override
  State<LinkPetScreen> createState() => _LinkPetScreenState();
}

class _LinkPetScreenState extends State<LinkPetScreen> {
  final _code = TextEditingController();
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _scan() async {
    final scanner = widget.scanner ?? (ctx) => Navigator.of(ctx).push<String>(MaterialPageRoute(builder: (_) => const ScanScreen()));
    final code = await scanner(context);
    if (code == null || !mounted) return;
    _code.text = LinkCode.display(code);
    await _submit();
  }

  Future<void> _submit() async {
    if (!LinkCode.isComplete(_code.text)) {
      setState(() => _error = 'El código tiene 8 caracteres, por ejemplo ABCD-2345.');
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    final session = context.read<Session>();
    try {
      final pet = await session.api.linkPet(LinkCode.normalize(_code.text));
      if (mounted) Navigator.of(context).pop(pet);
    } on UnauthorizedException {
      await session.expire();
    } on ValidationException catch (e) {
      setState(() => _error = e.first('code') ?? e.message);
    } catch (e) {
      setState(() => _error = errorText(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(title: const Text('Vincular mascota')),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 440),
            child: ListView(padding: const EdgeInsets.all(24), children: [
              Text(
                'Tu clínica te entrega un código de un solo uso, en papel o como QR, para cada mascota. Vence a los 7 días.',
                style: TextStyle(fontSize: 16, height: 1.5, color: scheme.onSurfaceVariant),
              ),
              const SizedBox(height: 24),
              FilledButton.icon(onPressed: _busy ? null : _scan, icon: const Icon(Icons.qr_code_scanner), label: const Text('Escanear QR')),
              const SizedBox(height: 24),
              Row(children: [
                const Expanded(child: Divider()),
                Padding(padding: const EdgeInsets.symmetric(horizontal: 12), child: Text('o escribe el código', style: TextStyle(color: scheme.onSurfaceVariant))),
                const Expanded(child: Divider()),
              ]),
              const SizedBox(height: 24),
              if (_error != null) ...[ErrorBanner(_error!), const SizedBox(height: 16)],
              TextField(
                controller: _code,
                textAlign: TextAlign.center,
                textCapitalization: TextCapitalization.characters,
                autocorrect: false,
                enableSuggestions: false,
                maxLength: 9,
                inputFormatters: [_LinkCodeFormatter()],
                style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, letterSpacing: 4, fontFeatures: [FontFeature.tabularFigures()]),
                decoration: const InputDecoration(hintText: 'ABCD-2345', counterText: ''),
                onSubmitted: (_) => _busy ? null : _submit(),
              ),
              const SizedBox(height: 16),
              OutlinedButton(
                onPressed: _busy ? null : _submit,
                child: _busy ? const SizedBox.square(dimension: 22, child: CircularProgressIndicator(strokeWidth: 2.5)) : const Text('Vincular'),
              ),
            ]),
          ),
        ),
      ),
    );
  }
}

/// Uppercases and inserts the dash as the owner types: "abcd2" → "ABCD-2".
class _LinkCodeFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    final raw = LinkCode.normalize(newValue.text);
    final clipped = raw.length > LinkCode.length ? raw.substring(0, LinkCode.length) : raw;
    final text = LinkCode.display(clipped);
    return TextEditingValue(text: text, selection: TextSelection.collapsed(offset: text.length));
  }
}
