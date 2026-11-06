import 'package:flutter/material.dart';

import '../widgets/common.dart';
import 'login_screen.dart';
import 'register_screen.dart';

class WelcomeScreen extends StatelessWidget {
  const WelcomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Scaffold(
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) => SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: BoxConstraints(minHeight: constraints.maxHeight - 48),
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 440),
                  child: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                    Center(child: Image.asset('assets/images/logo.webp', height: 150, semanticLabel: 'Logotipo de PetPulse')),
                    const SizedBox(height: 24),
                    const Text(
                      'Consulta el estado de tu canino o felino',
                      style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, height: 1.15, letterSpacing: -0.5),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Descubre lo que tu mascota necesita: cómo sigue, los partes médicos '
                      'y los medicamentos que le aplica la clínica.',
                      style: TextStyle(fontSize: 17, height: 1.5, color: scheme.onSurfaceVariant),
                    ),
                    const SizedBox(height: 32),
                    FilledButton(
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const RegisterScreen())),
                      child: const Text('Crear cuenta'),
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton(
                      onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen())),
                      child: const Text('Ya tengo cuenta'),
                    ),
                    const SizedBox(height: 24),
                    Text(
                      'Para ver a tu mascota necesitas el código que te entrega tu clínica.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 14, color: scheme.onSurfaceVariant),
                    ),
                    const SizedBox(height: 8),
                    const Center(child: Wordmark(size: 28)),
                  ]),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
