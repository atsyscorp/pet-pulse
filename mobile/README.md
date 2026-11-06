# PetPulse para propietarios (app Flutter)

App móvil para los **propietarios de mascotas**. Con el código o QR que entrega la
clínica, el propietario vincula a su mascota y ve:

- **Estado**: si está hospitalizada, desde cuándo y el estado que la clínica decide
  mostrar (Estable, En observación, Delicado, Crítico); al salir, la indicación de alta.
- **Partes médicos**: las notas que el equipo publica desde la ficha del paciente.
- **Medicación**: cada medicamento ya aplicado, con la vía y la hora (sin dosis ni inventario).
- **Ficha**: especie, raza, sexo, edad y microchip.

La app consume la API del servidor PetPulse (`/api/v1/owner`), documentada en
[`docs/API-PROPIETARIOS.md`](../docs/API-PROPIETARIOS.md). Toda la información la controla la
clínica: el propietario solo ve lo que se publicó para él, y la clínica puede retirar el
acceso en cualquier momento.

## Estructura

| Ruta | Contenido |
|---|---|
| `lib/main.dart` | Arranque, tema, idioma español y cambio entre sesión iniciada / cerrada |
| `lib/src/api/` | Cliente HTTP tipado y errores con mensajes listos para mostrar |
| `lib/src/session.dart` | Sesión: token guardado en Keychain (iOS) / Keystore (Android) |
| `lib/src/screens/` | Bienvenida, ingreso, registro, mis mascotas, vincular (código o QR), detalle |
| `lib/src/theme.dart` | Colores y tipografía del sistema de diseño (`DESIGN.md`) |
| `assets/` | Logotipo, icono y tipografía Outfit (licencia OFL incluida) |
| `test/` | Pruebas del cliente, del código de vinculación y del flujo completo de la app |

## Desarrollo

Requisitos: **Flutter 3.47** (Dart 3.13). Para Android, Android Studio con el SDK; para
iOS, un Mac con Xcode.

```bash
cd mobile
flutter pub get
flutter test                       # 11 pruebas
flutter analyze

# Contra un servidor local (emulador Android: 10.0.2.2 es el computador anfitrión)
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000
```

Sin `API_BASE_URL`, la app apunta a producción: `https://petpulse.atsys.co`.

Para probar el flujo completo en local:

1. En el servidor, `php artisan migrate --seed` y entre a `/dev/login/2` (veterinaria).
2. Abra la ficha de un paciente → **App del propietario** → *Generar código de vinculación*.
3. En la app: *Crear cuenta* → *Vincular mascota* → escriba el código o escanee el QR.

## Publicar en Google Play

1. Cree la llave de carga (una sola vez; **guárdela con copia de seguridad**, sin ella no se
   pueden publicar actualizaciones):
   ```bash
   keytool -genkey -v -keystore ~/petpulse-upload.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
   ```
2. Cree `mobile/android/key.properties` (está en `.gitignore`, nunca se sube al repositorio):
   ```properties
   storePassword=...
   keyPassword=...
   keyAlias=upload
   storeFile=/ruta/a/petpulse-upload.jks
   ```
3. Compile el paquete para la tienda:
   ```bash
   flutter build appbundle --release --dart-define=API_BASE_URL=https://petpulse.atsys.co
   ```
   Suba `build/app/outputs/bundle/release/app-release.aab` en Google Play Console.

Identificador de la app: **`co.atsys.petpulse`**. Es permanente una vez publicada; si
prefiere otro, cámbielo en `android/app/build.gradle.kts` antes de la primera subida.

## Publicar en App Store

Requiere un Mac con Xcode y la cuenta de Apple Developer de la empresa.

1. `open ios/Runner.xcworkspace` → *Runner* → *Signing & Capabilities*: elija el equipo
   (Team). Identificador: **`co.atsys.petpulse`**.
2. ```bash
   flutter build ipa --release --dart-define=API_BASE_URL=https://petpulse.atsys.co
   ```
3. Suba el `.ipa` con Transporter o desde Xcode (*Product → Archive*).

## Lo que piden las tiendas

- **Política de privacidad** pública (URL). La app trata datos personales del propietario
  (nombre, correo, celular) y de su mascota; debe citar la Ley 1581 de 2012.
- **Eliminación de cuenta**: Apple y Google exigen que el usuario pueda solicitar borrar su
  cuenta. Está en la lista de pendientes (ver abajo).
- **Uso de la cámara**: ya declarado (solo para escanear el QR de la clínica).

## Integración continua

`.github/workflows/mobile.yml` ejecuta análisis, pruebas y compila el APK de Android en
cada cambio dentro de `mobile/`. El APK queda como artefacto del flujo (firmado con
llaves de depuración, solo para pruebas internas).

## Pendientes para siguientes versiones

- Notificaciones push (requiere un proyecto de Firebase de la empresa).
- Recuperar contraseña por correo (requiere configurar el envío de correos del servidor).
- Eliminar la cuenta desde la app.
- Citas, vacunas e historia clínica, cuando esos módulos existan en PetPulse.
