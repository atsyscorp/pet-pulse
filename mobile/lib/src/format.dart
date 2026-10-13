import 'package:intl/intl.dart';

/// Dates in Latin American Spanish, in the device's local time.
abstract final class Fmt {
  static final _day = DateFormat("EEEE d 'de' MMMM", 'es');
  static final _dayShort = DateFormat("d MMM yyyy", 'es');
  static final _time = DateFormat('HH:mm', 'es');

  static String dateTime(DateTime value) => '${_dayShort.format(value)}, ${_time.format(value)}';

  static String date(DateTime value) => _dayShort.format(value);

  static String time(DateTime value) => _time.format(value);

  /// "hoy", "ayer" or "lunes 12 de octubre".
  static String dayHeading(DateTime value, {DateTime? now}) {
    final today = _dateOnly(now ?? DateTime.now());
    final day = _dateOnly(value);
    final diff = today.difference(day).inDays;
    if (diff == 0) return 'Hoy';
    if (diff == 1) return 'Ayer';
    final text = _day.format(value);
    return text[0].toUpperCase() + text.substring(1);
  }

  /// "hace 5 min", "hace 3 h", or the date.
  static String relative(DateTime value, {DateTime? now}) {
    final diff = (now ?? DateTime.now()).difference(value);
    if (diff.inMinutes < 1) return 'hace un momento';
    if (diff.inMinutes < 60) return 'hace ${diff.inMinutes} min';
    if (diff.inHours < 24) return 'hace ${diff.inHours} h';
    return dateTime(value);
  }

  static String weight(double kg) {
    final text = kg.toStringAsFixed(3).replaceFirst(RegExp(r'\.?0+$'), '');
    return '${text.replaceAll('.', ',')} kg';
  }
}

DateTime _dateOnly(DateTime d) => DateTime(d.year, d.month, d.day);
