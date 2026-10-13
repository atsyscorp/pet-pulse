import 'package:flutter/material.dart';

import '../api/api_exception.dart';
import '../models.dart';

/// Brand header: logo tile + wordmark ("Pet" in ink, "Pulse" in trace).
class Wordmark extends StatelessWidget {
  const Wordmark({super.key, this.size = 40});

  final double size;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Row(mainAxisSize: MainAxisSize.min, children: [
      ClipRRect(
        borderRadius: BorderRadius.circular(size * 0.3),
        child: Image.asset('assets/images/icon.png', width: size, height: size, semanticLabel: 'PetPulse'),
      ),
      const SizedBox(width: 10),
      Text.rich(
        TextSpan(children: [
          TextSpan(text: 'Pet', style: TextStyle(color: scheme.onSurface)),
          TextSpan(text: 'Pulse', style: TextStyle(color: scheme.secondary)),
        ]),
        style: TextStyle(fontSize: size * 0.55, fontWeight: FontWeight.w800, letterSpacing: -0.5),
      ),
    ]);
  }
}

/// Status of the stay as the owner sees it. Neutral styling on purpose: the
/// app reports, it does not alarm; the words carry the meaning.
class StayChip extends StatelessWidget {
  const StayChip({super.key, required this.pet});

  final Pet pet;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final stay = pet.stay;
    final (IconData icon, String text) = switch (stay) {
      null => (Icons.home_outlined, 'Sin hospitalizaciones'),
      Stay(active: true, ownerStatusLabel: final String label) => (Icons.monitor_heart_outlined, 'Hospitalizado · $label'),
      Stay(active: true) => (Icons.monitor_heart_outlined, 'Hospitalizado'),
      Stay(:final statusLabel) => (Icons.home_outlined, statusLabel),
    };
    final active = stay?.active ?? false;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: active ? scheme.primary : scheme.surfaceContainerHighest,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        Icon(icon, size: 16, color: active ? scheme.onPrimary : scheme.onSurfaceVariant),
        const SizedBox(width: 6),
        Flexible(
          child: Text(
            text,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: active ? scheme.onPrimary : scheme.onSurfaceVariant),
          ),
        ),
      ]),
    );
  }
}

/// Full-area message with an optional action (empty lists, errors).
class MessageView extends StatelessWidget {
  const MessageView({super.key, required this.icon, required this.title, this.body, this.action});

  final IconData icon;
  final String title;
  final String? body;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(icon, size: 48, color: scheme.onSurfaceVariant),
          const SizedBox(height: 16),
          Text(title, textAlign: TextAlign.center, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
          if (body != null) ...[
            const SizedBox(height: 8),
            Text(body!, textAlign: TextAlign.center, style: TextStyle(fontSize: 16, color: scheme.onSurfaceVariant, height: 1.5)),
          ],
          if (action != null) ...[const SizedBox(height: 24), action!],
        ]),
      ),
    );
  }
}

/// Error banner for forms: the server's message, never a stack trace.
class ErrorBanner extends StatelessWidget {
  const ErrorBanner(this.message, {super.key});

  final String message;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Semantics(
      liveRegion: true,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: scheme.error.withValues(alpha: 0.08),
          border: Border.all(color: scheme.error),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(Icons.error_outline, color: scheme.error, size: 20),
          const SizedBox(width: 10),
          Expanded(child: Text(message, style: TextStyle(color: scheme.error, fontWeight: FontWeight.w600))),
        ]),
      ),
    );
  }
}

String errorText(Object error) => error is ApiException ? error.message : 'Ocurrió un error inesperado. Intente de nuevo.';
