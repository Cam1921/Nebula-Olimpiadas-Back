Nebula-Olimpiadas — Backend

Repositorio para el desarrollo Back-end del sistema para Olimpiadas. Proyecto grupal para Taller de Ingeniería de Software.

🧰 Tecnologías

Framework: Laravel 11

Lenguaje: PHP 8.2/8.3

Auth: Laravel Sanctum (Bearer tokens)

Base de datos: PostgreSQL (Supabase)

Gestor de dependencias: Composer

✅ Requisitos previos

PHP 8.2 o 8.3 (Non Thread Safe, x64 recomendado en Windows)

Composer

Git

PostgreSQL en Supabase (un proyecto DEV compartido para el equipo)

Extensiones PHP requeridas (php.ini)
extension=curl
extension=mbstring
extension=openssl
extension=pgsql
extension=pdo_pgsql
extension=fileinfo


Si aparece el warning Module "openssl" is already loaded, significa que la extensión está duplicada. Deja solo una línea extension=openssl.

🗂 Estructura del proyecto (resumen)
app/
  Http/Controllers/AuthController.php
  Models/User.php
config/
  cors.php
  sanctum.php
database/
  migrations/
  seeders/
    AdminUserSeeder.php
    DatabaseSeeder.php
routes/
  api.php
.env           (local, no se versiona)

🚀 Instalación (desarrollo local)
1) Clonar el repositorio
git clone https://github.com/<tu-usuario>/Nebula-Olimpiadas-Back.git
cd Nebula-Olimpiadas-Back

2) Instalar dependencias
composer install

3) Configurar variables de entorno

Copia el ejemplo y edita tus credenciales:

cp .env.example .env
php artisan key:generate


Edita .env con Supabase DEV (ejemplo):

APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db.xxxxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=<<PASSWORD_DEV>>
DB_SSLMODE=require

CACHE_STORE=file
SESSION_DRIVER=file


⚠️ Si existe DATABASE_URL en .env, coméntalo para que no sobreescriba las variables DB_*.

4) Limpiar config y migrar
php artisan config:clear
php artisan migrate

5) Semillas (usuario admin)
php artisan db:seed --class="Database\\Seeders\\AdminUserSeeder"
# Admin: admin@nebula.com  /  Admin12345!

6) Levantar el servidor
php artisan serve --host=0.0.0.0 --port=8000
# http://localhost:8000

🔐 Autenticación (Sanctum, Bearer)

Rutas principales (API):

POST /api/login → devuelve { token, user }

POST /api/logout → requiere header Authorization: Bearer <TOKEN>

GET /api/me → pendiente menor (ver abajo)

GET /api/health → ping { ok: true }

Pruebas rápidas

Login

POST http://localhost:8000/api/login
Headers:
  Content-Type: application/json
Body (JSON):
{
  "email": "admin@nebula.com",
  "password": "Admin12345!"
}


🌐 CORS (SPA en Vite)

config/cors.php debe permitir el front local:

return [
  'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['http://localhost:5173','http://127.0.0.1:5173'],
  'allowed_headers' => ['*'],
  'supports_credentials' => false, // Bearer tokens (no cookies)
];


Luego:

php artisan config:clear

🧪 Comandos útiles
php artisan --version
php artisan route:list
php artisan migrate
php artisan migrate:fresh --seed     # ⚠️ NO usar en BD compartida sin avisar
php artisan tinker
php artisan optimize:clear
composer dump-autoload

🧩 Problemas comunes (FAQ)

1) “could not translate host name”
El DB_HOST no debe incluir @. Debe ser db.xxxxxx.supabase.co.
Agrega DB_SSLMODE=require.

2) Cambié .env y no aplica
php artisan config:clear

3) 401 en login
Cuerpo JSON vacío o credenciales incorrectas. Asegúrate de enviar:

{ "email":"admin@nebula.com","password":"Admin12345!" }

4) personal_access_tokens duplicada
Quitá la migración duplicada más nueva y vuelve a migrar.

👥 Trabajo en equipo (BD compartida)

Este proyecto usa una sola base Supabase DEV compartida para todo el equipo.

Reglas:

Toda modificación de esquema va en migraciones nuevas (no edites migraciones ya aplicadas).

Seeders idempotentes (updateOrCreate), nada destructivo.

Prohibido migrate:fresh o db:wipe en horario de trabajo sin aviso a todo el equipo.

Un responsable revisa PRs con migraciones antes de mergear a develop.

Flujo:

Crea rama de feature desde develop.

Implementa cambios + migraciones.

PR → revisión → merge a develop.

Post-merge: git pull && php artisan migrate.

.env.example del repo apunta a Supabase DEV; el DB_PASSWORD se comparte por canal seguro (no en el repo).

Para producción se recomienda otro proyecto Supabase separado.

🏷️ Ramas

develop (rama por defecto durante el curso)

feature/* → PR a develop

Protege develop en GitHub (revisiones, no push directo).

📄 Licencia / Autoría

Equipo Nebula Soft S.R.L. — Proyecto académico.

Licencia: (definir: MIT / privativa / etc.)