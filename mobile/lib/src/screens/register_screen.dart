import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../session.dart';
import '../widgets/common.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _accept = false;
  bool _busy = false;
  bool _obscure = true;
  String? _error;
  Map<String, List<String>> _fieldErrors = const {};

  @override
  void dispose() {
    for (final c in [_name, _email, _phone, _password]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _busy = true;
      _error = null;
      _fieldErrors = const {};
    });
    try {
      await context.read<Session>().register(
            name: _name.text,
            email: _email.text,
            phone: _phone.text,
            password: _password.text,
            acceptTerms: _accept,
          );
      if (mounted) Navigator.of(context).popUntil((route) => route.isFirst);
    } on ValidationException catch (e) {
      setState(() => _fieldErrors = e.errors);
    } catch (e) {
      setState(() => _error = errorText(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final termsError = _fieldErrors['accept_terms']?.first;

    return Scaffold(
      appBar: AppBar(title: const Text('Crear cuenta')),
      body: SafeArea(
        child: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 440),
            child: AutofillGroup(
              child: ListView(padding: const EdgeInsets.all(24), children: [
                if (_error != null) ...[ErrorBanner(_error!), const SizedBox(height: 16)],
                TextField(
                  controller: _name,
                  textCapitalization: TextCapitalization.words,
                  autofillHints: const [AutofillHints.name],
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(labelText: 'Nombre completo', errorText: _fieldErrors['name']?.first),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _email,
                  keyboardType: TextInputType.emailAddress,
                  autofillHints: const [AutofillHints.email],
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(labelText: 'Correo electrónico', errorText: _fieldErrors['email']?.first),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _phone,
                  keyboardType: TextInputType.phone,
                  autofillHints: const [AutofillHints.telephoneNumber],
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(labelText: 'Celular (opcional)', errorText: _fieldErrors['phone']?.first),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _password,
                  obscureText: _obscure,
                  autofillHints: const [AutofillHints.newPassword],
                  decoration: InputDecoration(
                    labelText: 'Contraseña',
                    helperText: 'Mínimo 8 caracteres, con letras y números.',
                    errorText: _fieldErrors['password']?.first,
                    errorMaxLines: 3,
                    suffixIcon: IconButton(
                      tooltip: _obscure ? 'Mostrar contraseña' : 'Ocultar contraseña',
                      icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                      onPressed: () => setState(() => _obscure = !_obscure),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Material(
                  color: scheme.surface,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                    side: BorderSide(color: termsError != null ? scheme.error : scheme.outline),
                  ),
                  child: CheckboxListTile(
                    value: _accept,
                    onChanged: (v) => setState(() => _accept = v ?? false),
                    controlAffinity: ListTileControlAffinity.leading,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    title: const Text(
                      'Autorizo el tratamiento de mis datos personales para usar PetPulse, conforme a la Ley 1581 de 2012.',
                      style: TextStyle(fontSize: 14, height: 1.4),
                    ),
                  ),
                ),
                if (termsError != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8, left: 12),
                    child: Text(termsError, style: TextStyle(color: scheme.error, fontSize: 13)),
                  ),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: _busy ? null : _submit,
                  child: _busy ? const SizedBox.square(dimension: 22, child: CircularProgressIndicator(strokeWidth: 2.5)) : const Text('Crear cuenta'),
                ),
              ]),
            ),
          ),
        ),
      ),
    );
  }
}
