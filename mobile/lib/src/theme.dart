import 'package:flutter/material.dart';

/// PetPulse design tokens (DESIGN.md): sober tonal surfaces, night-indigo
/// action in light, pulse-cyan action in dark, Outfit throughout, 48 px targets.
abstract final class PetPulseColors {
  static const page = Color(0xFFF1F5F9);
  static const surface = Color(0xFFFFFFFF);
  static const sunken = Color(0xFFE8EEF5);
  static const line = Color(0xFFD5DDE8);
  static const edge = Color(0xFF738398);
  static const ink = Color(0xFF0F172A);
  static const muted = Color(0xFF475569);
  static const accent = Color(0xFF120C36);
  static const trace = Color(0xFF0E7490);
  static const late = Color(0xFFB91C1C);

  static const pageDark = Color(0xFF120C36);
  static const surfaceDark = Color(0xFF1B1450);
  static const sunkenDark = Color(0xFF0D0928);
  static const lineDark = Color(0xFF342B6E);
  static const edgeDark = Color(0xFF7A74B4);
  static const inkDark = Color(0xFFF8FAFC);
  static const mutedDark = Color(0xFFB9B4DC);
  static const accentDark = Color(0xFF22E5F5);
  static const lateDark = Color(0xFFFCA5A5);
}

ThemeData petPulseTheme(Brightness brightness) {
  final dark = brightness == Brightness.dark;
  final scheme = ColorScheme(
    brightness: brightness,
    primary: dark ? PetPulseColors.accentDark : PetPulseColors.accent,
    onPrimary: dark ? PetPulseColors.accent : Colors.white,
    secondary: dark ? PetPulseColors.accentDark : PetPulseColors.trace,
    onSecondary: dark ? PetPulseColors.accent : Colors.white,
    error: dark ? PetPulseColors.lateDark : PetPulseColors.late,
    onError: dark ? PetPulseColors.accent : Colors.white,
    surface: dark ? PetPulseColors.surfaceDark : PetPulseColors.surface,
    onSurface: dark ? PetPulseColors.inkDark : PetPulseColors.ink,
    onSurfaceVariant: dark ? PetPulseColors.mutedDark : PetPulseColors.muted,
    surfaceContainerHighest: dark ? PetPulseColors.sunkenDark : PetPulseColors.sunken,
    outline: dark ? PetPulseColors.edgeDark : PetPulseColors.edge,
    outlineVariant: dark ? PetPulseColors.lineDark : PetPulseColors.line,
  );

  final base = ThemeData(useMaterial3: true, colorScheme: scheme, fontFamily: 'Outfit');
  final radius12 = BorderRadius.circular(12);

  return base.copyWith(
    scaffoldBackgroundColor: dark ? PetPulseColors.pageDark : PetPulseColors.page,
    appBarTheme: AppBarTheme(
      backgroundColor: dark ? PetPulseColors.pageDark : PetPulseColors.page,
      foregroundColor: scheme.onSurface,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: false,
      titleTextStyle: TextStyle(fontFamily: 'Outfit', fontSize: 22, fontWeight: FontWeight.w800, color: scheme.onSurface),
    ),
    cardTheme: CardThemeData(
      color: scheme.surface,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24), side: BorderSide(color: scheme.outlineVariant)),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size.fromHeight(52),
        shape: RoundedRectangleBorder(borderRadius: radius12),
        textStyle: const TextStyle(fontFamily: 'Outfit', fontSize: 16, fontWeight: FontWeight.w700),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size.fromHeight(52),
        foregroundColor: scheme.onSurface,
        side: BorderSide(color: scheme.outlineVariant),
        shape: RoundedRectangleBorder(borderRadius: radius12),
        textStyle: const TextStyle(fontFamily: 'Outfit', fontSize: 16, fontWeight: FontWeight.w700),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: scheme.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(borderRadius: radius12, borderSide: BorderSide(color: scheme.outline)),
      enabledBorder: OutlineInputBorder(borderRadius: radius12, borderSide: BorderSide(color: scheme.outline)),
      focusedBorder: OutlineInputBorder(borderRadius: radius12, borderSide: BorderSide(color: scheme.onSurface, width: 2)),
    ),
    tabBarTheme: TabBarThemeData(
      labelColor: scheme.onSurface,
      unselectedLabelColor: scheme.onSurfaceVariant,
      indicatorColor: scheme.onSurface,
      labelStyle: const TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w700, fontSize: 15),
      unselectedLabelStyle: const TextStyle(fontFamily: 'Outfit', fontWeight: FontWeight.w600, fontSize: 15),
      // Three tabs must fit a 360 px phone without truncating "Partes médicos".
      labelPadding: const EdgeInsets.symmetric(horizontal: 6),
      tabAlignment: TabAlignment.fill,
    ),
    dividerTheme: DividerThemeData(color: scheme.outlineVariant, space: 1),
  );
}
