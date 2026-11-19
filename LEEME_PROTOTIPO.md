# Ahorro Eco – Prototipo funcional (TRL5)

Este documento resume qué se agregó a la demo con Tuya para llegar al prototipo descrito en el documento maestro.

## Puesta en marcha (una sola vez)

1. Ejecuta `migracion_prototipo.sql` sobre la base de datos existente (`ahorvonr_1`). **Solo una vez**: agrega columnas a `usuarios` y crea `equipos`, `lecturas` y `recomendaciones`. Las tablas del módulo IOT no se tocan.
2. Reemplaza en el servidor `registro.php` por una copia de `registro.example.php` con tus llaves de reCAPTCHA y SMTP. El `registro.php` anterior es vulnerable a inyección SQL y envía la clave por correo.
3. Mantén `conexion.php` como está (está en `.gitignore`).
4. Verifica las tarifas en `inc/config.php` (comentarios `VERIFICAR`) contra el tarifario vigente de EMCALI.
5. Fuerza HTTPS en el hosting (cPanel → «Forzar HTTPS»). Las cookies de sesión ya se marcan `Secure` cuando hay HTTPS.

## Requerimientos y dónde se cumplen

| Requerimiento | Archivos |
|---|---|
| RF-01 Registro e inicio de sesión con perfil hogar o microempresa | `registro.example.php`, `login.php`, `perfil.php` |
| RF-02 Registro de equipos (potencia, cantidad, horas) | `equipos.php` |
| RF-03 Simulador de sensores (endpoint PHP con intervalo y hora configurables) | `api_simulador.php`, `inc/Modelo.php` |
| RF-04 Consumo en pesos con tarifa EMCALI por estrato o sector | `consumo.php`, `inc/Tarifa.php`, `inc/config.php` |
| RF-05 Motor de reglas (15 reglas, explicables) | `inc/MotorReglas.php`, `recomendaciones.php` |
| RF-06 Calificación de recomendaciones (1 a 5) | `calificar.php`, indicador de pertinencia en `recomendaciones.php` |
| RNF-02 Respuesta del motor ≤ 2 s | se mide y se muestra en pantalla (≈ 2 ms en pruebas) |
| RNF-04 / Ley 1581 | consentimiento en el registro, sin dirección, `eliminar_cuenta.php` |
| RNF-05 Seguridad | consultas preparadas, `password_hash`, CSRF, sesión regenerada, errores no expuestos, validación real de fotos |
| RNF-06 PWA | `manifest.json`, `sw.js`, `offline.html`, `icons/` |

## Pruebas

- `php tests/prueba_motor.php`: tarifas, simulador y motor de reglas (sin base de datos).
- Pruebas de recorrido completo contra servidor y base reales: registro, login, equipos, simulación, recomendaciones, calificación, aislamiento entre usuarios, inyección SQL, CSRF, subida de archivos y eliminación de cuenta.

## Supuestos pendientes de confirmar con el diagnóstico

Todos los umbrales de las reglas (`umbrales` en `inc/config.php`) son hipótesis de trabajo; se ajustan sin tocar el código con los resultados de los 30 cuestionarios.
