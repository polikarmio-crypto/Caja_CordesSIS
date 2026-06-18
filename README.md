# Caja de Salud CORDES — Sistema de Información Hospitalaria

Sistema web de gestión clínica y administrativa para la Caja de Salud CORDES (La Paz, Bolivia).  
Desarrollado con arquitectura **MVC en PHP puro** y base de datos **PostgreSQL**.

---

## Requisitos previos

Antes de correr el sistema, asegúrate de tener instalado:

| Herramienta | Versión mínima | Verificar con |
|---|---|---|
| **PHP** | 8.1+ | `php -v` |
| **PostgreSQL** | 14+ | `psql --version` |
| **Extensión PDO-PostgreSQL** | — | `php -m \| findstr pdo_pgsql` |
| **Git** | cualquier | `git --version` |

> El sistema **NO** usa Apache ni MySQL. Solo necesitas PHP y PostgreSQL.

---

## Instalación paso a paso

### 1. Clonar o copiar el proyecto

```bash
# Si usas Git:
git clone <url-del-repo> Caja_CordesSIS
cd Caja_CordesSIS

# O simplemente copia la carpeta del proyecto a donde quieras
```

---

### 2. Configurar la base de datos

#### 2.1 Crear la base de datos en PostgreSQL

Abre **pgAdmin** o una terminal con `psql` y ejecuta:

```sql
-- Como superusuario (postgres):
CREATE DATABASE caja_cordes
    WITH ENCODING='UTF8'
    LC_COLLATE='es_BO.UTF-8'
    LC_CTYPE='es_BO.UTF-8'
    TEMPLATE=template0;
```

Si el locale `es_BO.UTF-8` no está disponible en tu sistema, usa:
```sql
CREATE DATABASE caja_cordes WITH ENCODING='UTF8';
```

---

#### 2.2 Cargar el esquema y los datos (archivo principal)

La carpeta `database/` contiene los siguientes archivos SQL:

| Archivo | Descripción |
|---|---|
| `caja_cordes_20260617_2024.sql` | Dump completo: esquema + todos los datos de prueba |
| `security.sql` | Politicas de seguridad Row Level Security (RLS) |
| `update_schema.sql` | Actualizaciones de esquema (ejecutar despues del dump si existe) |

**Paso 1 — Cargar el dump principal (esquema + datos):**

Desde PowerShell, ajusta la ruta de `psql.exe` según donde tengas PostgreSQL instalado:

```powershell
# PostgreSQL instalado en E:\PostgreSQL\ (ajusta si es diferente)
$env:PGPASSWORD = "TU_CONTRASENA_AQUI"
& "E:\PostgreSQL\bin\psql.exe" `
    -U postgres `
    -h 127.0.0.1 `
    -d caja_cordes `
    -f "f:\Pablo Medina\Caja_CordesSIS\database\caja_cordes_20260617_2024.sql"
```

Si `psql` esta en el PATH del sistema, simplifica a:
```bash
psql -U postgres -h 127.0.0.1 -d caja_cordes -f database/caja_cordes_20260617_2024.sql
```

> Si ves errores de tipo `already exists`, son normales cuando el esquema ya fue cargado. Los datos se siguen insertando correctamente.

---

**Paso 2 — Aplicar actualizaciones de esquema (si corresponde):**

```powershell
$env:PGPASSWORD = "TU_CONTRASENA_AQUI"
& "E:\PostgreSQL\bin\psql.exe" `
    -U postgres `
    -h 127.0.0.1 `
    -d caja_cordes `
    -f "f:\Pablo Medina\Caja_CordesSIS\database\update_schema.sql"
```

---

**Paso 3 — Aplicar politicas de seguridad RLS:**

```powershell
$env:PGPASSWORD = "TU_CONTRASENA_AQUI"
& "E:\PostgreSQL\bin\psql.exe" `
    -U postgres `
    -h 127.0.0.1 `
    -d caja_cordes `
    -f "f:\Pablo Medina\Caja_CordesSIS\database\security.sql"
```

> **Orden correcto:** dump principal → update_schema → security. Siempre en ese orden.

---

### 3. Configurar las variables de entorno

En la raíz del proyecto existe el archivo **`.env`**. Edítalo con tus datos:

```env
DB_HOST=127.0.0.1
DB_NAME=caja_cordes
DB_USER=postgres
DB_PASS=TU_CONTRASENA_AQUI
```

> **Nunca subas el `.env` a Git.** Ya está en el `.gitignore`.

---

### 4. Verificar la extensión PDO PostgreSQL en PHP

Busca tu archivo `php.ini` y asegúrate que esta línea **no** tenga punto y coma (`;`) al inicio:

```ini
extension=pdo_pgsql
```

En XAMPP el archivo `php.ini` suele estar en:
```
F:\Pablo Medina\xampp\php\php.ini
```

Para verificar que la extensión está activa, ejecuta en PowerShell:
```powershell
& "F:\Pablo Medina\xampp\php\php.exe" -m | findstr pdo_pgsql
```
Debe mostrar `pdo_pgsql` en la lista. Si no aparece, habilita la línea en `php.ini` y reinicia el servidor.

---

### 5. Correr el servidor de desarrollo

Desde la raíz del proyecto, abre una terminal (PowerShell) y ejecuta:

```powershell
& "F:\Pablo Medina\xampp\php\php.exe" -S 127.0.0.1:8000 "f:\Pablo Medina\Caja_CordesSIS\public\router.php"
```

> **Importante**: el comando debe apuntar a `public/router.php`, NO a `public/index.php`.  
> El `router.php` sirve los archivos estáticos (CSS, JS, imágenes) correctamente.

Si PHP está en el PATH del sistema, puedes simplificar a:

```bash
php -S 127.0.0.1:8000 public/router.php
```

---

### 6. Abrir el sistema en el navegador

Una vez que el servidor esté corriendo, abre:

```
http://127.0.0.1:8000
```

Deberías ver la pantalla de **inicio de sesión** de Caja Cordes.

---

## Usuarios de prueba

| Rol | Email | Contraseña |
|---|---|---|
| Administrativo | `admin@cajacordes.com` | *(ver BD)* |
| Médico | `medico@cajacordes.com` | *(ver BD)* |
| Paciente | `paciente@cajacordes.com` | *(ver BD)* |

> Las contraseñas están cifradas con `password_hash()` (bcrypt). Para cambiarlas,  
> usa el formulario de "Recuperar contraseña" o actualízalas directamente en la BD.

---

## Estructura del proyecto

```
Caja_CordesSIS/
├── .env                    <- Variables de entorno (DB, etc.)
├── config/
│   └── Database.php        <- Conexión PDO a PostgreSQL
├── controllers/            <- Lógica de cada módulo
│   ├── AuthController.php
│   ├── CitaController.php
│   ├── DashboardController.php
│   └── ...
├── core/
│   ├── Router.php          <- Enrutador MVC
│   ├── Helpers.php         <- Funciones auxiliares (log_activity, etc.)
│   └── structures/         <- Estructuras de datos personalizadas
├── database/
│   ├── caja_cordes_20260617_2024.sql  <- Dump completo (esquema + datos)
│   ├── security.sql                   <- Politicas de seguridad RLS
│   └── update_schema.sql              <- Actualizaciones de esquema
├── models/                 <- Clases de acceso a datos
│   ├── Cita.php
│   ├── Paciente.php
│   └── ...
├── public/
│   ├── index.php           <- Bootstrap de la aplicación
│   ├── router.php          <- Router para servidor de desarrollo PHP
│   ├── css/                <- Estilos (style.css, theme.css)
│   ├── js/                 <- Scripts (theme.js, etc.)
│   └── uploads/            <- Archivos subidos por usuarios
└── views/
    ├── layouts/
    │   ├── header.php      <- Cabecera HTML
    │   ├── sidebar.php     <- Menú lateral
    │   └── footer.php      <- Pie de página
    ├── auth/               <- Login, 2FA, recuperar contraseña
    ├── citas/              <- Módulo de citas médicas
    ├── pacientes/          <- Módulo de pacientes
    ├── dashboard/          <- Mi Portal (estadísticas)
    ├── farmacia/           <- Módulo de farmacia
    ├── historia_clinica/   <- Expediente clínico
    ├── hospitalizacion/    <- Control de camas
    ├── laboratorio/        <- Resultados de laboratorio
    ├── facturacion/        <- Facturación
    ├── insumo/             <- Inventario de insumos
    ├── sucursal/           <- Gestión de sucursales
    └── backups/            <- Gestión de backups de la BD
```

---

## Módulos disponibles

| Módulo | Ruta URL | Roles con acceso |
|---|---|---|
| Mi Portal / Dashboard | `/dashboard` | Todos |
| Pacientes | `/pacientes` | Administrativo, Médico |
| Citas Médicas | `/citas` | Todos |
| Horarios Médicos | `/horarios` | Administrativo |
| Historia Clínica | `/pacientes/{id}/historia` | Médico, Administrativo |
| Hospitalización | `/hospitalizacion` | Médico, Administrativo |
| Laboratorio | `/laboratorio` | Laboratorista, Médico |
| Farmacia | `/farmacia` | Farmacéutico, Administrativo |
| Facturación | `/facturacion` | Administrativo |
| Insumos | `/insumo` | Administrativo |
| Sucursales | `/sucursal` | Administrativo |
| Médicos | `/medicos` | Administrativo |
| Ausencias Médicas | `/ausencias` | Administrativo |
| Reportes | `/reportes/citas` | Administrativo, Directivo |
| Perfil | `/perfil` | Todos |
| Backups BD | `/backups` | Administrativo, Directivo |

---

## Backup de la base de datos

### Crear un backup nuevo

```powershell
# pg_dump en este equipo está en E:\PostgreSQL\bin\
$env:PGPASSWORD = "TU_CONTRASENA_AQUI"
$fecha = Get-Date -Format 'yyyyMMdd_HHmm'
& "E:\PostgreSQL\bin\pg_dump.exe" `
    -U postgres `
    -h 127.0.0.1 `
    -d caja_cordes `
    -f "f:\Pablo Medina\Caja_CordesSIS\database\caja_cordes_$fecha.sql"
```

Una vez creado el backup nuevo, **elimina el anterior** para no acumular archivos:

```powershell
# Reemplaza el nombre del archivo viejo:
Remove-Item "f:\Pablo Medina\Caja_CordesSIS\database\caja_cordes_FECHA_ANTERIOR.sql" -Force
```

### Restaurar desde un backup

```powershell
# Restaurar desde SQL plano:
$env:PGPASSWORD = "TU_CONTRASENA_AQUI"
& "E:\PostgreSQL\bin\psql.exe" `
    -U postgres -h 127.0.0.1 -d caja_cordes `
    -f "f:\Pablo Medina\Caja_CordesSIS\database\caja_cordes_20260617_2024.sql"
```

---

## Solución de problemas comunes

### Error: "No se puede conectar a la base de datos"
- Verifica que PostgreSQL esté corriendo: `Get-Service postgresql*`
- Verifica las credenciales en `.env`
- Asegúrate de que el puerto 5432 esté abierto: `netstat -an | findstr 5432`

### Error: "extension=pdo_pgsql not found"
- Abre `php.ini` y habilita: `extension=pdo_pgsql` (quita el `;` del inicio)
- Reinicia el servidor PHP

### Las páginas CSS/JS no cargan
- Asegúrate de usar `router.php` y NO `index.php` al lanzar el servidor
- Comando correcto: `php -S 127.0.0.1:8000 public/router.php`

### Error 404 en rutas como `/citas`, `/dashboard`
- El sistema usa rutas limpias manejadas por el Router MVC
- Si usas Apache: asegúrate de que `mod_rewrite` esté habilitado y el `.htaccess` en `/public/` sea leído
- Con el servidor PHP built-in (`php -S`): todo funciona automáticamente

### El 2FA no envía correo
- El sistema usa `mail()` de PHP. Verifica que tu servidor SMTP esté configurado en `php.ini`
- En desarrollo puedes revisar los tokens directamente en la tabla `usuarios` de la BD

### Error al cargar el SQL: "role does not exist" o "permission denied"
- Conéctate como superusuario `postgres` al ejecutar los scripts
- Si el error es en `security.sql`, ejecuta primero el dump principal y luego `security.sql`

---

## Licencia

Sistema desarrollado para uso interno de la **Caja de Salud CORDES** — La Paz, Bolivia.  
Proyecto académico — Ingeniería de Sistemas, 2026.