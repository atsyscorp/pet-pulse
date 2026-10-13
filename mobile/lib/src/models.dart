/// Data returned by the PetPulse owner API (see docs/API-PROPIETARIOS.md).
library;

class Owner {
  const Owner({required this.id, required this.name, required this.email, this.phone});

  factory Owner.fromJson(Map<String, dynamic> json) => Owner(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String,
        phone: json['phone'] as String?,
      );

  final int id;
  final String name;
  final String email;
  final String? phone;
}

class Stay {
  const Stay({
    required this.id,
    required this.active,
    required this.statusLabel,
    required this.admittedAt,
    this.ownerStatus,
    this.ownerStatusLabel,
    this.ownerStatusUpdatedAt,
    this.dischargedAt,
    this.weightKg,
    this.dischargeNotes,
  });

  factory Stay.fromJson(Map<String, dynamic> json) => Stay(
        id: json['id'] as int,
        active: json['active'] as bool,
        statusLabel: json['status_label'] as String,
        ownerStatus: json['owner_status'] as String?,
        ownerStatusLabel: json['owner_status_label'] as String?,
        ownerStatusUpdatedAt: _date(json['owner_status_updated_at']),
        admittedAt: _date(json['admitted_at'])!,
        dischargedAt: _date(json['discharged_at']),
        weightKg: (json['weight_kg'] as num?)?.toDouble(),
        dischargeNotes: json['discharge_notes'] as String?,
      );

  final int id;
  final bool active;
  final String statusLabel;
  final String? ownerStatus;
  final String? ownerStatusLabel;
  final DateTime? ownerStatusUpdatedAt;
  final DateTime admittedAt;
  final DateTime? dischargedAt;
  final double? weightKg;
  final String? dischargeNotes;
}

class Pet {
  const Pet({
    required this.id,
    required this.name,
    required this.species,
    required this.clinicName,
    this.breed,
    this.sex,
    this.birthDate,
    this.age,
    this.microchip,
    this.stay,
  });

  factory Pet.fromJson(Map<String, dynamic> json) => Pet(
        id: json['id'] as int,
        name: json['name'] as String,
        species: json['species'] as String,
        breed: json['breed'] as String?,
        sex: json['sex'] as String?,
        birthDate: _date(json['birth_date']),
        age: json['age'] as String?,
        microchip: json['microchip'] as String?,
        clinicName: (json['clinic'] as Map<String, dynamic>)['name'] as String? ?? '',
        stay: json['hospitalization'] == null
            ? null
            : Stay.fromJson(json['hospitalization'] as Map<String, dynamic>),
      );

  final int id;
  final String name;
  final String species;
  final String? breed;
  final String? sex;
  final DateTime? birthDate;
  final String? age;
  final String? microchip;
  final String clinicName;
  final Stay? stay;

  bool get isHospitalized => stay?.active ?? false;

  String get description => [species, if (breed != null) breed, if (age != null) age].join(' · ');
}

class OwnerUpdate {
  const OwnerUpdate({required this.id, required this.body, required this.createdAt, this.author, this.authorRole});

  factory OwnerUpdate.fromJson(Map<String, dynamic> json) => OwnerUpdate(
        id: json['id'] as int,
        body: json['body'] as String,
        author: json['author'] as String?,
        authorRole: json['author_role'] as String?,
        createdAt: _date(json['created_at'])!,
      );

  final int id;
  final String body;
  final String? author;
  final String? authorRole;
  final DateTime createdAt;
}

class Medication {
  const Medication({required this.id, required this.drug, required this.routeLabel, required this.administeredAt, this.activeIngredient});

  factory Medication.fromJson(Map<String, dynamic> json) => Medication(
        id: json['id'] as int,
        drug: json['drug'] as String? ?? 'Medicamento',
        activeIngredient: json['active_ingredient'] as String?,
        routeLabel: json['route_label'] as String,
        administeredAt: _date(json['administered_at'])!,
      );

  final int id;
  final String drug;
  final String? activeIngredient;
  final String routeLabel;
  final DateTime administeredAt;
}

/// One page of a Laravel paginated collection.
class PageOf<T> {
  const PageOf({required this.items, required this.currentPage, required this.lastPage});

  factory PageOf.fromJson(Map<String, dynamic> json, T Function(Map<String, dynamic>) item) {
    final meta = json['meta'] as Map<String, dynamic>? ?? const {};
    return PageOf(
      items: (json['data'] as List).cast<Map<String, dynamic>>().map(item).toList(),
      currentPage: meta['current_page'] as int? ?? 1,
      lastPage: meta['last_page'] as int? ?? 1,
    );
  }

  final List<T> items;
  final int currentPage;
  final int lastPage;

  bool get hasMore => currentPage < lastPage;
}

DateTime? _date(Object? value) => value == null ? null : DateTime.parse(value as String).toLocal();
