import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api_exception.dart';
import '../models.dart';
import '../session.dart';
import '../widgets/common.dart';
import 'link_pet_screen.dart';
import 'pet_detail_screen.dart';

/// Home: the owner's linked pets.
class PetsScreen extends StatefulWidget {
  const PetsScreen({super.key});

  @override
  State<PetsScreen> createState() => _PetsScreenState();
}

class _PetsScreenState extends State<PetsScreen> {
  List<Pet>? _pets;
  Object? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final session = context.read<Session>();
    try {
      final pets = await session.api.pets();
      if (mounted) {
        setState(() {
          _pets = pets;
          _error = null;
        });
      }
    } on UnauthorizedException {
      await session.expire();
    } catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  Future<void> _link() async {
    final pet = await Navigator.of(context).push<Pet>(MaterialPageRoute(builder: (_) => const LinkPetScreen()));
    if (pet == null || !mounted) return;
    await _load();
    if (!mounted) return;
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => PetDetailScreen(pet: pet)));
  }

  Future<void> _confirmLogout() async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cerrar sesión'),
        content: const Text('Tendrás que volver a ingresar tu correo y contraseña.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancelar')),
          TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('Cerrar sesión')),
        ],
      ),
    );
    if (ok == true && mounted) await context.read<Session>().logout();
  }

  @override
  Widget build(BuildContext context) {
    final owner = context.watch<Session>().owner;

    return Scaffold(
      appBar: AppBar(
        title: const Wordmark(size: 32),
        actions: [
          IconButton(tooltip: 'Cerrar sesión', icon: const Icon(Icons.logout), onPressed: _confirmLogout),
          const SizedBox(width: 4),
        ],
      ),
      floatingActionButton: _pets?.isNotEmpty ?? false
          ? FloatingActionButton.extended(onPressed: _link, icon: const Icon(Icons.qr_code_scanner), label: const Text('Vincular mascota'))
          : null,
      body: RefreshIndicator(onRefresh: _load, child: _body(owner)),
    );
  }

  Widget _body(Owner? owner) {
    if (_error != null && _pets == null) {
      return _scrollable(MessageView(
        icon: Icons.wifi_off,
        title: 'No pudimos cargar tus mascotas',
        body: errorText(_error!),
        action: OutlinedButton(onPressed: _load, child: const Text('Reintentar')),
      ));
    }
    final pets = _pets;
    if (pets == null) return const Center(child: CircularProgressIndicator());
    if (pets.isEmpty) {
      return _scrollable(MessageView(
        icon: Icons.pets,
        title: 'Aún no tienes mascotas vinculadas',
        body: 'Pide en tu clínica veterinaria el código o QR de PetPulse para tu mascota y vincúlala aquí.',
        action: FilledButton.icon(onPressed: _link, icon: const Icon(Icons.qr_code_scanner), label: const Text('Vincular mascota')),
      ));
    }

    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 96),
      itemCount: pets.length + 1,
      separatorBuilder: (_, _) => const SizedBox(height: 12),
      itemBuilder: (context, i) {
        if (i == 0) {
          return Padding(
            padding: const EdgeInsets.fromLTRB(4, 4, 4, 4),
            child: Text(
              owner == null ? 'Mis mascotas' : 'Hola, ${owner.name.split(' ').first}',
              style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800, letterSpacing: -0.3),
            ),
          );
        }
        return _PetCard(pet: pets[i - 1], onChanged: _load);
      },
    );
  }

  Widget _scrollable(Widget child) => LayoutBuilder(
        builder: (context, c) => SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: SizedBox(height: c.maxHeight, child: child),
        ),
      );
}

class _PetCard extends StatelessWidget {
  const _PetCard({required this.pet, required this.onChanged});

  final Pet pet;
  final VoidCallback onChanged;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () async {
          await Navigator.of(context).push(MaterialPageRoute(builder: (_) => PetDetailScreen(pet: pet)));
          onChanged();
        },
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Row(children: [
            CircleAvatar(
              radius: 28,
              backgroundColor: scheme.surfaceContainerHighest,
              child: Text(pet.name.characters.first.toUpperCase(), style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: scheme.onSurface)),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(pet.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                const SizedBox(height: 2),
                Text(pet.description, style: TextStyle(color: scheme.onSurfaceVariant)),
                Text(pet.clinicName, style: TextStyle(color: scheme.onSurfaceVariant, fontSize: 13)),
                const SizedBox(height: 10),
                StayChip(pet: pet),
              ]),
            ),
            Icon(Icons.chevron_right, color: scheme.onSurfaceVariant),
          ]),
        ),
      ),
    );
  }
}
