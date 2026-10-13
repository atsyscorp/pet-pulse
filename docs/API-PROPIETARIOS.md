# API para la app de propietarios (v1)

Base: `https://SU-DOMINIO/api/v1/owner`. Todas las respuestas son JSON en español.
Envíe siempre `Accept: application/json`.

## Autenticación

La app obtiene un **token** al registrarse o iniciar sesión y lo envía en cada
petición:

```
Authorization: Bearer <token>
```

Los tokens vencen a los 90 días (`SANCTUM_TOKEN_EXPIRATION`, en minutos). Solo
sirven para esta API: una sesión del personal de la clínica no tiene acceso, y un
token de propietario no tiene acceso a la aplicación clínica.

| Método | Ruta | Cuerpo | Respuesta |
|---|---|---|---|
| POST | `/register` | `name`, `email`, `phone`?, `password` (≥ 8, letras y números), `accept_terms` (true), `device_name` | 201 `{ token, token_type, expires_at, owner }` |
| POST | `/login` | `email`, `password`, `device_name` | 200 `{ token, token_type, expires_at, owner }` |
| POST | `/logout` | — | 200; invalida el token usado |
| GET | `/me` | — | `{ data: { id, name, email, phone } }` |

`accept_terms` registra el consentimiento de tratamiento de datos (Ley 1581 de 2012).
Límites: 5 registros y 10 intentos de inicio de sesión por minuto e IP.

## Vincular una mascota

La clínica genera un **código de un solo uso** (válido 7 días) desde la ficha del
paciente; se muestra como `ABCD-2345` y como QR con el contenido
`PETPULSE:ABCD2345`. Generar otro código anula el anterior.

| Método | Ruta | Cuerpo | Respuesta |
|---|---|---|---|
| POST | `/pets/link` | `code` (acepta `ABCD-2345`, `abcd2345` o `PETPULSE:ABCD2345`) | 201 `{ data: Pet }` · 422 si no es válido |

Un código desconocido, vencido, usado o anulado devuelve el mismo mensaje (no se
distingue a propósito). Límite: 10 intentos por hora.

## Mascotas

| Método | Ruta | Respuesta |
|---|---|---|
| GET | `/pets` | `{ data: Pet[] }`, solo las mascotas vinculadas y no revocadas |
| GET | `/pets/{id}` | `{ data: Pet }` · 404 si no está vinculada |
| GET | `/pets/{id}/updates` | Partes médicos, más recientes primero, paginados de a 20 |
| GET | `/pets/{id}/medications` | Dosis **ya administradas**, más recientes primero, paginadas de a 30 |

### Pet

```json
{
  "id": 12,
  "name": "Rocky",
  "species": "Canino",
  "breed": "Pastor Alemán",
  "sex": "Macho",
  "birth_date": "2020-03-15",
  "age": "6 años",
  "microchip": "900182000123456",
  "clinic": { "id": 1, "name": "Clínica Veterinaria Los Andes" },
  "hospitalization": {
    "id": 40,
    "active": true,
    "status": "hospitalized",
    "status_label": "Hospitalizado",
    "owner_status": "stable",
    "owner_status_label": "Estable",
    "owner_status_updated_at": "2026-10-13T09:20:00-05:00",
    "admitted_at": "2026-10-12T20:19:00+00:00",
    "discharged_at": null,
    "weight_kg": 32.4,
    "discharge_notes": null
  }
}
```

`hospitalization` es la estancia más reciente (abierta o cerrada) o `null`.
`owner_status` es el estado que la clínica decidió mostrar (`stable`,
`observation`, `delicate`, `critical`) o `null` si no lo ha fijado.
`discharge_notes` solo se envía cuando la estancia está cerrada.

**Nunca se envían** al propietario: el nivel de triage interno, la cama, el
documento o teléfono registrados en la clínica, las dosis exactas, el inventario
ni las notas internas del kárdex.

### Parte médico

```json
{ "id": 3, "hospitalization_id": 40, "body": "Hoy comió bien.", "author": "Dra. Camila Restrepo", "author_role": "Veterinario", "created_at": "2026-10-13T14:05:00+00:00" }
```

### Medicación administrada

```json
{ "id": 881, "drug": "Ceftriaxona 1 g", "active_ingredient": "Ceftriaxona", "route": "IV", "route_label": "Intravenosa", "administered_at": "2026-10-13T13:02:11+00:00" }
```

## Errores

| Código | Significado |
|---|---|
| 401 | Falta el token, o venció. La app debe volver a iniciar sesión. |
| 403 | El token no es de propietario. |
| 404 | La mascota no existe o el propietario no está vinculado a ella. |
| 422 | Validación: `{ message, errors: { campo: [mensaje] } }`. |
| 429 | Demasiados intentos; reintentar más tarde. |
