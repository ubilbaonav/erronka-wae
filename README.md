# Txurdinaga Ikastetxea

Aplicación web para la gestión de accesos, usuarios, vehículos y plazas de
parking del centro educativo Txurdinaga.

## Estructura general

| Zona de la web | Ubicación | Función |
|---|---|---|
| Página pública y login | [`index.html`](./index.html) | Presentación del centro, acceso al login, información general y chatbot público. |
| Dashboard autenticado | [`paginas/dashboard.php`](./paginas/dashboard.php) | Panel principal adaptado al rol del usuario. |
| Dashboard alternativo | [`paginas/dashboard.html`](./paginas/dashboard.html) | Redirección automática al dashboard PHP. |
| Parking público | [`paginas/parking.php`](./paginas/parking.php) | Consulta de las plazas del parking público. |
| Cierre de sesión | [`auth/logout.php`](./auth/logout.php) | Destruye la sesión y vuelve al login. |
| Estilos | [`assets/styles.css`](./assets/styles.css) | Diseño visual, tablas, formularios, responsive y tema del chatbot. |
| JavaScript común | [`assets/app.js`](./assets/app.js) | Logout, mensajes de login y aceptación de cookies. |
| Icono | [`assets/IMG/icon.png`](./assets/IMG/icon.png) | Favicon de la aplicación. |

## Requisitos de la rúbrica y dónde están implementados

### 1. Tecnologías y funcionamiento general

- **HTML:** estructura de la página pública y formularios en
  [`index.html`](./index.html).
- **PHP:** autenticación, sesiones, consultas y renderizado del dashboard en
  [`auth/login.php`](./auth/login.php) y [`paginas/dashboard.php`](./paginas/dashboard.php).
- **CSS:** diseño completo en [`assets/styles.css`](./assets/styles.css).
- **JavaScript:** comportamiento del cliente en [`assets/app.js`](./assets/app.js).
- **Base de datos MySQL/MariaDB:** conexión y selección de usuario de base de
  datos según el rol en [`config/conexioa.php`](./config/conexioa.php).
- **Chatbot n8n:** se carga en [`index.html`](./index.html) y
  [`paginas/dashboard.php`](./paginas/dashboard.php). Su apariencia se
  personaliza desde [`assets/styles.css`](./assets/styles.css).

### 2. Página de inicio

En [`index.html`](./index.html) se encuentran:

- Cabecera con la identidad de Txurdinaga.
- Botón para iniciar sesión.
- Presentación del centro.
- Resumen visual del estado del centro.
- Tarjetas de acceso seguro, información y comunidad.
- Modal de login.
- Enlace al parking público.
- Chatbot.
- Banner de consentimiento de cookies.

### 3. Login, sesiones y control de acceso

El formulario de login está en [`index.html`](./index.html) y se procesa en
[`auth/login.php`](./auth/login.php).

El login:

- Valida email y contraseña.
- Consulta el usuario con una sentencia preparada.
- Comprueba que el usuario esté activo.
- Regenera el ID de sesión para evitar session fixation.
- Guarda en sesión el ID, nombre, email y rol.
- Redirige a [`paginas/dashboard.php`](./paginas/dashboard.php).

El dashboard exige una sesión activa. Si no existe, redirige al login.
El cierre de sesión está en [`auth/logout.php`](./auth/logout.php).

### 4. Roles de usuario

Los roles se convierten en valores internos en
[`auth/login.php`](./auth/login.php):

| Rol en la base de datos | Rol interno | Funcionalidad principal |
|---|---|---|
| `Ikaslea` | `estudiante` | Dashboard, parking permitido y gestión de sus coches. |
| `Irakaslea` | `profesor` | Dashboard, gestión de sus coches y consulta de todos los alumnos. |
| `Admin` | `administrador` | Gestión completa de usuarios y vehículos, además del parking. |
| `Bisitaria` | `visita` | Acceso limitado al dashboard y parking público. |

La navegación y el contenido se muestran condicionalmente en
[`paginas/dashboard.php`](./paginas/dashboard.php).

### 5. Consulta de alumnos para el profesor

La consulta de alumnos está implementada en la función `getStudents()` de
[`config/erabiltzaileak.php`](./config/erabiltzaileak.php).

En [`paginas/dashboard.php`](./paginas/dashboard.php), únicamente el rol
`profesor` puede ver la sección **Ikasleak**, que contiene una tabla con:

- ID.
- NAN.
- Nombre y apellidos.
- Email.
- Estado activo o inactivo.

El acceso aparece en el menú lateral mediante el ancla `#alumnos`.

### 6. Gestión de vehículos por parte de los usuarios

La sección **Nire cochea** aparece en el dashboard para los usuarios
autenticados y está ubicada en [`paginas/dashboard.php`](./paginas/dashboard.php).

Permite guardar:

- Matrícula.
- Marca.
- Modelo.

Las operaciones de consulta y alta están en
[`config/ibilgailuak.php`](./config/ibilgailuak.php), mediante:

- `getVehiclesByUserId()`.
- `addVehicle()`.

El vehículo se asocia al ID del usuario almacenado en la sesión, por lo que
cada usuario consulta sus propios coches.

### 7. CRUD completo de vehículos para el administrador

La sección de administración de vehículos está en
[`paginas/dashboard.php`](./paginas/dashboard.php), dentro del bloque
`#ibilgailuen-kudeaketa`.

El administrador puede:

- **Crear:** botón `Gehitu cochea`.
- **Leer:** tabla global con matrícula, propietario, marca y modelo.
- **Actualizar:** botón `Editatu`.
- **Eliminar:** botón `Ezabatu` con confirmación.

La lógica está centralizada en
[`config/ibilgailuak.php`](./config/ibilgailuak.php):

- `getAllVehicles()`.
- `addVehicle()`.
- `updateVehicle()`.
- `deleteVehicle()`.

La tabla `ibilgailuak` utiliza `matrikula` como clave primaria y
`erabiltzaile_id` para relacionar cada coche con su propietario.

### 8. Gestión de usuarios por el administrador

El CRUD de usuarios está en [`paginas/dashboard.php`](./paginas/dashboard.php)
y utiliza las funciones de [`config/erabiltzaileak.php`](./config/erabiltzaileak.php):

- `getUsers()`: lista de usuarios.
- `addUser()`: creación.
- `getUserById()`: carga para edición.
- `updateUser()`: actualización.
- `deleteUser()`: eliminación.

El administrador puede modificar nombre, apellidos, NAN, email, rol y
restablecer la contraseña predeterminada.

### 9. Parking

La lectura de plazas está en
[`config/parking.php`](./config/parking.php), mediante `getParkingZones()`.

El dashboard permite mostrar las zonas según el rol:

- Parking público.
- Parking de alumnos.
- Parking de profesores.

El resumen de plazas libres, ocupadas y porcentaje disponible se calcula con
`parkingSummary()`, también en [`config/parking.php`](./config/parking.php).

La respuesta JSON del parking está disponible en
[`api/parking-data.php`](./api/parking-data.php).

### 10. Chatbot

El chatbot se inicializa con `createChat()` en:

- [`index.html`](./index.html).
- [`paginas/dashboard.php`](./paginas/dashboard.php).

El webhook configurado es el endpoint local de n8n. El estilo visual se
encuentra en [`assets/styles.css`](./assets/styles.css), donde se han
personalizado:

- Colores corporativos.
- Cabecera azul.
- Mensajes del usuario y del bot.
- Botón flotante.
- Campo de escritura.
- Bordes, sombras y responsive.

### 11. Cookies

El banner se muestra en las páginas principales y el consentimiento se procesa
en [`api/cookie-consent.php`](./api/cookie-consent.php).

Los nombres, duración y valores de la cookie están definidos en
[`config/constantes.php`](./config/constantes.php).

La comprobación de la cookie en el navegador está en
[`assets/app.js`](./assets/app.js).

### 12. Diseño responsive y accesibilidad

Las reglas responsive están al final de
[`assets/styles.css`](./assets/styles.css). Incluyen:

- Adaptación del dashboard a pantallas pequeñas.
- Ocultación del menú lateral en móvil.
- Tablas desplazables horizontalmente.
- Chatbot adaptado al ancho de la pantalla.
- Estados de foco visibles para navegación mediante teclado.
- Etiquetas y mensajes de error en formularios.

## Configuración local

El proyecto está preparado para ejecutarse con XAMPP:

1. Colocar el proyecto en `C:\xampp\htdocs\Web-erronka`.
2. Iniciar Apache y MySQL desde XAMPP.
3. Crear o importar la base de datos `txurdinaga_sarbideak`.
4. Comprobar los datos de conexión en
   [`config/constantes.php`](./config/constantes.php) y
   [`config/conexioa.php`](./config/conexioa.php).
5. Abrir:
   `http://localhost/Web-erronka/index.html`

La conexión utiliza cuentas de base de datos diferentes según el rol:

- `web_login`.
- `web_admin`.
- `web_irakaslea`.
- `web_ikaslea`.

## Tablas principales de base de datos

| Tabla | Uso |
|---|---|
| `erabiltzaileak` | Usuarios y sus datos personales. |
| `rolak` | Roles de la aplicación. |
| `ibilgailuak` | Matrícula, propietario, marca y modelo de los coches. |
| `parking_guneak` | Zonas de parking. |
| `parking_plazak` | Plazas y estado de ocupación. |
| `ibilgailuen_sarrerak` | Entradas y salidas de vehículos. |
| `pertsonen_sarrerak` | Entradas y salidas de personas. |
| `parking_intzidentziak` | Incidencias del parking. |
| `chatbot_kontsultak` | Consultas registradas del chatbot. |

## Validación realizada

Los archivos PHP principales se han comprobado con el verificador de sintaxis
de PHP de XAMPP. La conexión y las consultas principales de vehículos también
se han probado contra la base de datos local.

## Consulta de la sesión en el navegador

La sesión se puede inspeccionar desde las herramientas de desarrollador del
navegador:

1. Abrir la aplicación en `http://localhost/Web-erronka/index.html`.
2. Pulsar `F12` o `Ctrl + Shift + I`.
3. Abrir la pestaña **Application** / **Aplicación**.
4. En el menú lateral, entrar en **Storage → Cookies → http://localhost**.
5. Buscar la cookie `PHPSESSID`.

`PHPSESSID` es el identificador de la sesión PHP. Los datos del usuario no se
guardan directamente en el navegador: el ID, el nombre, el email y el rol se
guardan en el servidor mediante `$_SESSION['user']`.

La sesión se inicia y se utiliza en:

- [`auth/login.php`](./auth/login.php), donde se crea la sesión después de
  validar las credenciales.
- [`paginas/dashboard.php`](./paginas/dashboard.php), donde se comprueba que
  existe una sesión activa.
- [`auth/logout.php`](./auth/logout.php), donde se destruye la sesión.

La cookie `PHPSESSID` está configurada como `HttpOnly`, por lo que puede verse
en **Application → Cookies**, pero no puede leerse desde JavaScript mediante
`document.cookie`.

La cookie `cookies_accepted`, gestionada en
[`api/cookie-consent.php`](./api/cookie-consent.php), no es la sesión. Solo
guarda si el usuario ha aceptado el aviso de cookies.

Para comprobar temporalmente el contenido de la sesión desde PHP se puede
usar:

```php
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
```

Este código solo debe utilizarse durante la depuración y debe eliminarse
después, ya que muestra información privada.
