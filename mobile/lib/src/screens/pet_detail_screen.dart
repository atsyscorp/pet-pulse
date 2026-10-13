import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../format.dart';
import '../models.dart';
import '../session.dart';
import '../widgets/common.dart';

/// One pet: status, written updates from the clinic, and medication given.
class PetDetailScreen extends StatefulWidget {
  const PetDetailScreen({super.key, required this.pet});

  final Pet pet;

  @override
  State<PetDetailScreen> createState() => _PetDetailScreenState();
}

class _PetDetailScreenState extends State<PetDetailScreen> {
  late Pet _pet = widget.pet;

  Future<void> _refreshPet() async {
    final session = context.read<Session>();
    try {
      final pet = await session.api.pet(_pet.id);
      if (mounted) setState(() => _pet = pet);
    } on UnauthorizedException {
      await session.expire();
    } on NotFoundException {
      if (mounted) Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Scaffold(
        appBar: AppBar(
          title: Text(_pet.name),
          bottom: const TabBar(tabs: [Tab(text: 'Estado'), Tab(text: 'Partes médicos'), Tab(text: 'Medicación')]),
        ),
        body: TabBarView(children: [
          _StatusTab(pet: _pet, onRefresh: _refreshPet),
          _PagedList<OwnerUpdate>(
            key: const PageStorageKey('updates'),
            fetch: (page) => context.read<Session>().api.updates(_pet.id, page: page),
            emptyTitle: 'Aún no hay partes médicos',
            emptyBody: 'Cuando el equipo de la clínica escriba sobre ${_pet.name}, lo verá aquí.',
            emptyIcon: Icons.chat_bubble_outline,
            itemBuilder: (context, u) => _UpdateTile(update: u),
          ),
          _PagedList<Medication>(
            key: const PageStorageKey('meds'),
            fetch: (page) => context.read<Session>().api.medications(_pet.id, page: page),
            emptyTitle: 'Sin medicamentos aplicados',
            emptyBody: 'Aquí aparecerá cada medicamento que la clínica le aplique a ${_pet.name}, con la hora.',
            emptyIcon: Icons.medication_outlined,
            itemBuilder: (context, m) => _MedicationTile(medication: m),
            dayOf: (m) => m.administeredAt,
          ),
        ]),
      ),
    );
  }
}

class _StatusTab extends StatelessWidget {
  const _StatusTab({required this.pet, required this.onRefresh});

  final Pet pet;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final stay = pet.stay;

    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(padding: const EdgeInsets.all(16), children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              if (stay == null) ...[
                const Text('Sin hospitalizaciones', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800)),
                const SizedBox(height: 6),
                Text('${pet.name} no ha estado hospitalizado en ${pet.clinicName}.', style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 16)),
              ] else if (stay.active) ...[
                StayChip(pet: pet),
                const SizedBox(height: 16),
                Text(
                  stay.ownerStatusLabel ?? 'Hospitalizado',
                  style: const TextStyle(fontSize: 34, fontWeight: FontWeight.w800, letterSpacing: -0.5),
                ),
                if (stay.ownerStatusUpdatedAt != null)
                  Text('Actualizado ${Fmt.relative(stay.ownerStatusUpdatedAt!)}', style: TextStyle(color: scheme.onSurfaceVariant)),
                const SizedBox(height: 16),
                const Divider(),
                _Fact('Hospitalizado desde', Fmt.dateTime(stay.admittedAt)),
                if (stay.weightKg != null) _Fact('Peso al ingreso', Fmt.weight(stay.weightKg!)),
                _Fact('Clínica', pet.clinicName),
              ] else ...[
                StayChip(pet: pet),
                const SizedBox(height: 16),
                Text(stay.statusLabel, style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w800)),
                const SizedBox(height: 16),
                const Divider(),
                _Fact('Ingreso', Fmt.dateTime(stay.admittedAt)),
                if (stay.dischargedAt != null) _Fact('Salida', Fmt.dateTime(stay.dischargedAt!)),
                if (stay.dischargeNotes != null && stay.dischargeNotes!.isNotEmpty) ...[
                  const SizedBox(height: 12),
                  Text('Indicaciones de la clínica', style: TextStyle(fontWeight: FontWeight.w700, color: scheme.onSurfaceVariant)),
                  const SizedBox(height: 4),
                  Text(stay.dischargeNotes!, style: const TextStyle(fontSize: 16, height: 1.5)),
                ],
              ],
            ]),
          ),
        ),
        const SizedBox(height: 16),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('Ficha', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
              const SizedBox(height: 8),
              _Fact('Especie', pet.species),
              if (pet.breed != null) _Fact('Raza', pet.breed!),
              if (pet.sex != null) _Fact('Sexo', pet.sex!),
              if (pet.birthDate != null) _Fact('Nacimiento', '${Fmt.date(pet.birthDate!)}${pet.age != null ? ' (${pet.age})' : ''}'),
              if (pet.microchip != null) _Fact('Microchip', pet.microchip!),
            ]),
          ),
        ),
        const SizedBox(height: 16),
        Text(
          'La información la publica ${pet.clinicName}. Ante cualquier duda, comuníquese con la clínica.',
          style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13),
          textAlign: TextAlign.center,
        ),
      ]),
    );
  }
}

class _Fact extends StatelessWidget {
  const _Fact(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(width: 150, child: Text(label, style: TextStyle(color: scheme.onSurfaceVariant))),
        Expanded(child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600))),
      ]),
    );
  }
}

class _UpdateTile extends StatelessWidget {
  const _UpdateTile({required this.update});

  final OwnerUpdate update;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(update.body, style: const TextStyle(fontSize: 17, height: 1.5)),
          const SizedBox(height: 10),
          Text(
            [if (update.author != null) update.author!, if (update.authorRole != null) update.authorRole!, Fmt.dateTime(update.createdAt)].join(' · '),
            style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13),
          ),
        ]),
      ),
    );
  }
}

class _MedicationTile extends StatelessWidget {
  const _MedicationTile({required this.medication});

  final Medication medication;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        leading: Text(Fmt.time(medication.administeredAt), style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, fontFeatures: [FontFeature.tabularFigures()])),
        title: Text(medication.drug, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Text(medication.routeLabel, style: TextStyle(color: scheme.onSurfaceVariant)),
        trailing: Icon(Icons.check_circle_outline, color: scheme.onSurfaceVariant, semanticLabel: 'Aplicado'),
      ),
    );
  }
}

/// Paginated, pull-to-refresh list with "load more" and optional day headings.
class _PagedList<T> extends StatefulWidget {
  const _PagedList({
    super.key,
    required this.fetch,
    required this.itemBuilder,
    required this.emptyTitle,
    required this.emptyBody,
    required this.emptyIcon,
    this.dayOf,
  });

  final Future<PageOf<T>> Function(int page) fetch;
  final Widget Function(BuildContext context, T item) itemBuilder;
  final String emptyTitle;
  final String emptyBody;
  final IconData emptyIcon;
  final DateTime Function(T item)? dayOf;

  @override
  State<_PagedList<T>> createState() => _PagedListState<T>();
}

class _PagedListState<T> extends State<_PagedList<T>> with AutomaticKeepAliveClientMixin {
  final List<T> _items = [];
  int _page = 0;
  bool _hasMore = true;
  bool _loading = false;
  Object? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _loadMore();
  }

  Future<void> _refresh() async {
    setState(() {
      _items.clear();
      _page = 0;
      _hasMore = true;
      _error = null;
    });
    await _loadMore();
  }

  Future<void> _loadMore() async {
    if (_loading || !_hasMore) return;
    setState(() => _loading = true);
    try {
      final page = await widget.fetch(_page + 1);
      if (!mounted) return;
      setState(() {
        _items.addAll(page.items);
        _page = page.currentPage;
        _hasMore = page.hasMore;
        _error = null;
      });
    } on UnauthorizedException {
      if (mounted) await context.read<Session>().expire();
    } catch (e) {
      if (mounted) setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    if (_items.isEmpty) {
      final Widget child;
      if (_loading) {
        child = const Center(child: CircularProgressIndicator());
      } else if (_error != null) {
        child = MessageView(icon: Icons.wifi_off, title: 'No se pudo cargar', body: errorText(_error!), action: OutlinedButton(onPressed: _refresh, child: const Text('Reintentar')));
      } else {
        child = MessageView(icon: widget.emptyIcon, title: widget.emptyTitle, body: widget.emptyBody);
      }
      return RefreshIndicator(
        onRefresh: _refresh,
        child: LayoutBuilder(
          builder: (context, c) => SingleChildScrollView(physics: const AlwaysScrollableScrollPhysics(), child: SizedBox(height: c.maxHeight, child: child)),
        ),
      );
    }

    final rows = <Widget>[];
    String? lastDay;
    for (final item in _items) {
      final day = widget.dayOf?.call(item);
      if (day != null) {
        final heading = Fmt.dayHeading(day);
        if (heading != lastDay) {
          rows.add(Padding(
            padding: EdgeInsets.fromLTRB(4, rows.isEmpty ? 0 : 12, 4, 4),
            child: Text(heading, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
          ));
          lastDay = heading;
        }
      }
      rows.add(Padding(padding: const EdgeInsets.only(bottom: 10), child: widget.itemBuilder(context, item)));
    }
    if (_hasMore) {
      rows.add(Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: _loading ? const Center(child: CircularProgressIndicator()) : OutlinedButton(onPressed: _loadMore, child: const Text('Ver más')),
      ));
    }

    return RefreshIndicator(onRefresh: _refresh, child: ListView(padding: const EdgeInsets.all(16), children: rows));
  }
}
