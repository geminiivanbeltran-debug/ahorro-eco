# 🌱 Ahorro Eco — Plataforma Web e IoT para la Gestión Energética Residencial

**Ahorro Eco** es una plataforma web e IoT diseñada para monitorear, controlar y optimizar el consumo de energía eléctrica en hogares en tiempo real. El sistema integra dispositivos inteligentes mediante la API de **Tuya IoT Platform**, persistencia de datos históricos en **MySQL** y analítica financiera personalizada según el valor del kilovatio-hora ($COP/kWh$) configurado por cada usuario.

## 🚀 Características Principales

* **Control IoT en Tiempo Real:** Encendido y apagado remoto de tomas inteligentes simples y multicanal, lectura instantánea de potencia activa ($W$), voltaje ($V$), consumo por hora ($kWh/h$) y costo ($COP/h$), junto con la vinculación de nuevos dispositivos.
* **Monitoreo y Trazabilidad:** Proyección energética y financiera por **Día, Semana, Mes y Año**, desglose por equipo conectado y visualización gráfica del historial de potencia almacenado en la base de datos.
* **Recomendaciones Inteligentes:** Detección de cargas activas, cálculo del potencial de ahorro mensual y sugerencias para reducir el consumo innecesario o en modo de espera (*standby*).
* **Simulador de Consumo y Huella de Carbono:** Modelado interactivo de electrodomésticos según horas de uso diario para estimar el consumo mensual ($kWh$), el costo proyectado y las emisiones de $CO_2$.
* **Centro de Educación Energética:** Guías prácticas sobre eficiencia energética, etiquetado **RETIQ** y noticias ambientales actualizadas.
* **Gestión de Usuarios y Validación:** Registro con ubicación geográfica, verificación de seguridad, notificaciones por correo electrónico, personalización de tarifa $kWh$ e instrumento de encuesta de experiencia de usuario.

## 🛠️ Tecnologías Utilizadas

* **Backend:** PHP (`mysqli`, `cURL`, firma `HMAC-SHA256`).
* **Base de Datos:** MySQL / MariaDB.
* **Frontend:** HTML5, CSS3 (diseño responsivo) y JavaScript.
* **Integraciones:** Tuya Cloud OpenAPI, Google reCAPTCHA y PHPMailer (SMTP).

## 📂 Estructura General del Proyecto

```text
ahorro-eco/
├── PHPMailer/                  # Librería para envío de correos transaccionales
├── nc_assets/                  # Recursos estáticos adicionales
├── uploads/                    # Archivos multimedia cargados en la plataforma
├── base_de_datos.sql           # Script de estructura de la base de datos MySQL
├── index.php                   # Vista principal de acceso
├── login.php                   # Procesamiento de inicio de sesión
├── logout.php                  # Cierre de sesión
├── home.php                    # Panel principal de navegación
├── monitoreo.php               # Analítica proyectada e historial de consumo
├── recomendaciones.php         # Cálculo dinámico de ahorro energético
├── simulacion.php              # Calculadora de consumo y huella de carbono
├── educacion.php               # Guías técnicas y noticias ambientales
├── perfil.php                  # Configuración de usuario y tarifa kWh
├── conexion.example.php        # Plantilla de conexión a MySQL
├── iot.example.php             # Plantilla del módulo de control IoT
├── tuya_api.example.php        # Plantilla de comunicación con Tuya Cloud
├── registro.example.php        # Plantilla de registro de usuarios
├── encuesta.example.php        # Plantilla de encuesta de satisfacción
└── README.md                   # Documentación del repositorio
```

## ⚙️ Instalación y Despliegue

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/ahorro-eco.git
cd ahorro-eco
```

### 2. Importar la base de datos

1. Crea una base de datos en **MySQL / phpMyAdmin** (cotejamiento `utf8mb4_unicode_ci`).
2. Importa el archivo `base_de_datos.sql` incluido en el repositorio para generar automáticamente todas las tablas necesarias.

### 3. Configurar las plantillas `.example.php`

Duplica y renombra los archivos de ejemplo en la raíz del proyecto completando tus propias credenciales de servidor, base de datos, correo SMTP y API de Tuya:

| Archivo Plantilla | Renombrar a | Configuración Requerida |
| :--- | :--- | :--- |
| `conexion.example.php` | `conexion.php` | Host, usuario, contraseña y nombre de la base de datos MySQL. |
| `iot.example.php` | `iot.php` | Credenciales de Tuya IoT Platform y `Device ID` de los dispositivos. |
| `tuya_api.example.php` | `tuya_api.php` | `Client ID` y `Secret` de Tuya Cloud. |
| `registro.example.php` | `registro.php` | Llaves de Google reCAPTCHA y credenciales de correo SMTP. |
| `encuesta.example.php` | `encuesta.php` | Credenciales de correo SMTP para el envío de respuestas. |

### 4. Ejecución

Ubica la carpeta del proyecto en tu servidor web con soporte PHP (Apache/Nginx, XAMPP, Laragon o hosting cPanel) y accede desde tu navegador a `index.php`.