# 🌱 Ahorro Eco — Plataforma Web e IoT para la Gestión Energética Residencial

**Ahorro Eco** es una aplicación web e IoT diseñada para monitorear, controlar y optimizar el consumo de energía eléctrica en hogares colombianos en tiempo real. El sistema integra dispositivos inteligentes mediante la API oficial de **Tuya IoT Platform** (firma criptográfica `HMAC-SHA256`), persistencia de datos históricos en **MySQL** y analítica financiera personalizada según el valor exacto del kilovatio-hora ($COP/kWh$) de cada usuario.

---

## 🚀 Características Principales (Nivel de Madurez TRL5)

* **Control IoT en Tiempo Real (`iot.php`):** Encendido y apagado remoto de tomas inteligentes simples y multicanal (`switch_1`, `switch_2`, etc.), lectura instantánea de potencia activa ($W$), voltaje ($V$), consumo por hora ($kWh/h$) y costo en pesos colombianos ($COP/h$), además de vinculación y validación dinámica de nuevos dispositivos Tuya Cloud.
* **Monitoreo y Trazabilidad (`monitoreo.php`):** Proyección financiera y energética por **Día, Semana, Mes y Año**, desglose individual por equipo sincronizado y visualización gráfica del historial de potencia almacenado en MySQL.
* **Recomendaciones Inteligentes (`recomendaciones.php`):** Detección automática de cargas activas, cálculo del potencial de ahorro mensual en pesos y alertas preventivas contra el consumo fantasma (*standby*).
* **Simulador de Consumo y Huella de Carbono (`simulacion.php`):** Modelado interactivo de electrodomésticos por horas de uso diario, calculando consumo mensual ($kWh$), costo estimado ($COP$) y emisiones equivalentes de $CO_2$ ($kg$).
* **Centro de Educación Energética (`educacion.php`):** Guías técnicas sobre eficiencia energética, etiquetado **RETIQ** y noticias ambientales sincronizadas mediante RSS.
* **Gestión de Usuarios y Validación (`registro.php`, `perfil.php`, `encuesta.php`):** Registro con selección dinámica de departamentos y municipios de Colombia, validación **Google reCAPTCHA**, envío de credenciales vía **PHPMailer (SMTP)**, personalización de tarifa $kWh$ e instrumento de encuesta para validación funcional con usuarios.

---

## 🛠️ Stack Tecnológico

* **Backend:** PHP 8.x (`mysqli` con consultas preparadas, `cURL`, firma `HMAC-SHA256`).
* **Base de Datos:** MySQL / MariaDB (`ahorvonr_1`).
* **Frontend:** HTML5, CSS3 (Diseño Responsivo Móvil/Escritorio), JavaScript Vanilla y Chart.js.
* **Servicios Externos:** Tuya Cloud OpenAPI (`Western America Data Center`), Google reCAPTCHA v2 y PHPMailer (SMTP).

---

## 📂 Estructura del Repositorio

```text
ahorro-eco/
├── landing.php                 # Página de aterrizaje con vista previa interactiva
├── index.php                   # Inicio de sesión de usuarios
├── home.php                    # Panel principal de navegación
├── monitoreo.php               # Analítica proyectada e historial de potencia MySQL
├── recomendaciones.php         # Cálculo dinámico de potencial de ahorro
├── simulacion.php              # Calculadora de consumo y huella de carbono
├── educacion.php               # Guías RETIQ y noticias ambientales
├── perfil.php                  # Configuración de datos y tarifa COP/kWh
├── logout.php                  # Cierre seguro de sesión
├── conexion.example.php        # Plantilla de conexión a MySQL
├── iot.example.php             # Plantilla del módulo IoT (sin credenciales)
├── tuya_api.example.php        # Plantilla de puente de consulta Tuya Cloud
├── registro.example.php        # Plantilla de registro con reCAPTCHA y SMTP
├── encuesta.example.php        # Plantilla de encuesta con envío SMTP
├── ahorvonr_1.sql              # Estructura y respaldo de la base de datos MySQL
├── PHPMailer/                  # Librería para envío de correos transaccionales
└── README.md                   # Documentación del proyecto
```

---

## ⚙️ Instalación y Configuración Local / Hosting

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/ahorro-eco.git
cd ahorro-eco
```

### 2. Importar la base de datos MySQL
1. Crea una base de datos llamada `ahorvonr_1` (con cotejamiento `utf8mb4_unicode_ci`) en **phpMyAdmin** o desde consola MySQL.
2. Importa el archivo `ahorvonr_1.sql` incluido en este repositorio para generar las tablas (`usuarios`, `dispositivos_iot`, `dispositivos_usuario`, `historial_consumo_iot`, etc.).

### 3. Configurar las credenciales a partir de los archivos `.example.php`
Por seguridad, las llaves privadas no están incluidas en el repositorio. Duplica y renombra los siguientes archivos completando tus propias credenciales:

| Archivo Plantilla | Renombrar a | Parámetros a Configurar |
| :--- | :--- | :--- |
| `conexion.example.php` | `conexion.php` | `$host`, `$user`, `$pass`, `$db` de tu servidor MySQL. |
| `iot.example.php` | `iot.php` | `$client_id`, `$secret` de Tuya IoT Platform y tus `Device ID`. |
| `tuya_api.example.php` | `tuya_api.php` | `$client_id` y `$secret` de Tuya Cloud. |
| `registro.example.php` | `registro.php` | `Site Key` y `Secret Key` de Google reCAPTCHA + credenciales SMTP. |
| `encuesta.example.php` | `encuesta.php` | Correo y contraseña de aplicación SMTP en PHPMailer. |

### 4. Despliegue
Sube la carpeta completa a tu servidor web Apache/Nginx con PHP activo (como XAMPP, Laragon o cPanel `public_html`) y accede desde el navegador a `landing.php` o `index.php`.

---

## 🔒 Buenas Prácticas de Seguridad

* Los archivos con credenciales reales (`conexion.php`, `iot.php`, `tuya_api.php`, `registro.php`, `encuesta.php`) están excluidos mediante `.gitignore` para evitar la exposición de llaves de API y contraseñas en el control de versiones.
* Las contraseñas de usuario se almacenan cifradas mediante `password_hash(..., PASSWORD_DEFAULT)`.

---

## 👥 Equipo Desarrollador

Proyecto desarrollado como prototipo funcional validado en entorno relevante (**TRL5**) para la gestión eficiente del consumo eléctrico residencial.
