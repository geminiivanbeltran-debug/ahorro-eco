<?php
/**
 * Configuración del prototipo Ahorro Eco.
 *
 * Todo lo que el diagnóstico (cuestionario de 30 usuarios) pueda ajustar vive
 * aquí: tarifas, umbrales y supuestos de las reglas. Así se corrigen sin tocar
 * el código del motor (ver Capítulo 8, "Diseño preliminar del prototipo").
 */
return [

    // ---------- Tarifas EMCALI, tarifa sencilla, nivel de tensión 1 (COP/kWh) ----------
    // Fuente: EMCALI (2026), septiembre de 2026. Verificar contra el tarifario vigente.
    'tarifas' => [
        'vigencia'          => 'septiembre de 2026',
        'subsistencia_kwh'  => 173,          // UPME (2004): consumo de subsistencia
        // sub   = precio dentro del consumo de subsistencia (solo estratos 1 a 3)
        // sobre = precio por encima de ese tope
        'residencial' => [
            1 => ['sub' => 426.17,  'sobre' => 915.52],   // VERIFICAR: el documento cita 426,17 a 532,72 para estratos 1 y 2
            2 => ['sub' => 532.72,  'sobre' => 915.52],   // VERIFICAR el precio sobre el tope para estratos 1 y 2
            3 => ['sub' => 778.19,  'sobre' => 915.52],
            4 => ['sub' => 915.52,  'sobre' => 915.52],
            5 => ['sub' => 1098.63, 'sobre' => 1098.63],
            6 => ['sub' => 1098.63, 'sobre' => 1098.63],
        ],
        'comercial' => ['sub' => 1098.63, 'sobre' => 1098.63],
    ],

    // ---------- Franjas del día ----------
    'franjas' => [
        'madrugada' => ['nombre' => 'Madrugada (0 a 6 h)',  'inicio' => 0],
        'manana'    => ['nombre' => 'Mañana (6 a 12 h)',    'inicio' => 6],
        'tarde'     => ['nombre' => 'Tarde (12 a 18 h)',    'inicio' => 12],
        'noche'     => ['nombre' => 'Noche (18 a 24 h)',    'inicio' => 18],
    ],
    // Franjas en que una microempresa suele estar abierta (ajustar con el diagnóstico).
    'franjas_atencion' => ['manana', 'tarde'],
    'franja_pico'      => 'noche',   // franja de mayor demanda (regla R02)

    // ---------- Catálogo de equipos ----------
    // potencia: W por unidad (valor típico, editable por el usuario)
    // factor:   fracción del tiempo "encendido" que el equipo consume a plena potencia
    //           (la nevera, por ejemplo, cicla el compresor)
    // standby:  W por unidad cuando queda conectado sin usarse
    // horas:    horas de uso diario sugeridas
    // perfil:   para quién aparece en la plantilla ('ambos', 'hogar', 'microempresa')
    'categorias' => [
        'nevera'       => ['nombre' => 'Nevera',                    'potencia' => 150,  'factor' => 0.35, 'standby' => 0,   'horas' => 24, 'perfil' => 'ambos'],
        'aire'         => ['nombre' => 'Aire acondicionado',        'potencia' => 1200, 'factor' => 0.70, 'standby' => 2,   'horas' => 6,  'perfil' => 'ambos'],
        'ventilador'   => ['nombre' => 'Ventilador',                'potencia' => 60,   'factor' => 1.0,  'standby' => 0,   'horas' => 8,  'perfil' => 'ambos'],
        'lavadora'     => ['nombre' => 'Lavadora',                  'potencia' => 500,  'factor' => 0.60, 'standby' => 1,   'horas' => 1,  'perfil' => 'hogar'],
        'televisor'    => ['nombre' => 'Televisor',                 'potencia' => 100,  'factor' => 1.0,  'standby' => 3,   'horas' => 5,  'perfil' => 'hogar'],
        'computador'   => ['nombre' => 'Computador',                'potencia' => 120,  'factor' => 1.0,  'standby' => 4,   'horas' => 8,  'perfil' => 'ambos'],
        'plancha'      => ['nombre' => 'Plancha',                   'potencia' => 1000, 'factor' => 0.60, 'standby' => 0,   'horas' => 1,  'perfil' => 'hogar'],
        'ducha'        => ['nombre' => 'Ducha o calentador eléctrico', 'potencia' => 4000, 'factor' => 1.0, 'standby' => 0, 'horas' => 0.5, 'perfil' => 'hogar'],
        'horno'        => ['nombre' => 'Horno o microondas',        'potencia' => 1200, 'factor' => 0.80, 'standby' => 2,   'horas' => 0.5, 'perfil' => 'ambos'],
        'iluminacion'  => ['nombre' => 'Iluminación',               'potencia' => 12,   'factor' => 1.0,  'standby' => 0,   'horas' => 6,  'perfil' => 'ambos'],
        'vitrina'      => ['nombre' => 'Vitrina o nevera comercial','potencia' => 350,  'factor' => 0.50, 'standby' => 0,   'horas' => 24, 'perfil' => 'microempresa'],
        'otro'         => ['nombre' => 'Otro equipo',               'potencia' => 100,  'factor' => 1.0,  'standby' => 0,   'horas' => 4,  'perfil' => 'ambos'],
    ],

    // Equipos que se cargan con el botón "Cargar equipos de ejemplo" (útil para la demo).
    'plantillas' => [
        'hogar' => [
            ['nombre' => 'Nevera',                'categoria' => 'nevera',      'potencia' => 150,  'cantidad' => 1, 'horas' => 24,  'franja' => 'noche', 'reposo' => 0],
            ['nombre' => 'Televisor sala',        'categoria' => 'televisor',   'potencia' => 100,  'cantidad' => 1, 'horas' => 5,   'franja' => 'noche', 'reposo' => 1],
            ['nombre' => 'Aire acondicionado',    'categoria' => 'aire',        'potencia' => 1200, 'cantidad' => 1, 'horas' => 9,   'franja' => 'noche', 'reposo' => 0],
            ['nombre' => 'Bombillos incandescentes','categoria' => 'iluminacion','potencia' => 60,  'cantidad' => 4, 'horas' => 5,   'franja' => 'noche', 'reposo' => 0],
            ['nombre' => 'Plancha',               'categoria' => 'plancha',     'potencia' => 1000, 'cantidad' => 1, 'horas' => 1.5, 'franja' => 'noche', 'reposo' => 0],
            ['nombre' => 'Ducha eléctrica',       'categoria' => 'ducha',       'potencia' => 4000, 'cantidad' => 1, 'horas' => 0.5, 'franja' => 'manana','reposo' => 0],
        ],
        'microempresa' => [
            ['nombre' => 'Vitrina refrigerada',   'categoria' => 'vitrina',     'potencia' => 350,  'cantidad' => 2, 'horas' => 24,  'franja' => 'tarde', 'reposo' => 0],
            ['nombre' => 'Nevera del local',      'categoria' => 'nevera',      'potencia' => 250,  'cantidad' => 1, 'horas' => 24,  'franja' => 'tarde', 'reposo' => 0],
            ['nombre' => 'Aire del local',        'categoria' => 'aire',        'potencia' => 1500, 'cantidad' => 1, 'horas' => 14,  'franja' => 'tarde', 'reposo' => 0],
            ['nombre' => 'Computadores',          'categoria' => 'computador',  'potencia' => 120,  'cantidad' => 3, 'horas' => 14,  'franja' => 'manana','reposo' => 1],
            ['nombre' => 'Iluminación del local', 'categoria' => 'iluminacion', 'potencia' => 18,   'cantidad' => 10,'horas' => 16,  'franja' => 'manana','reposo' => 0],
            ['nombre' => 'Horno de la cocina',    'categoria' => 'horno',       'potencia' => 2000, 'cantidad' => 1, 'horas' => 4,   'franja' => 'noche', 'reposo' => 0],
        ],
    ],

    // ---------- Sectores de microempresa (pregunta P2 del cuestionario) ----------
    'sectores' => [
        'comercio'              => 'Comercio',
        'alimentos'             => 'Alimentos',
        'servicios_personales'  => 'Servicios personales',
        'servicios_profesionales' => 'Servicios profesionales',
        'otro'                  => 'Otro',
    ],

    // ---------- Umbrales y supuestos del motor de reglas ----------
    // Son hipótesis de trabajo: se confirman o ajustan con el diagnóstico (Tabla 5).
    'umbrales' => [
        'alto_consumo_w'          => 1000,  // potencia total (W) que se considera "alto consumo"
        'aire_horas_max_hogar'    => 8,     // R03
        'aire_horas_max_micro'    => 10,    // R03
        'ahorro_termostato_pct'   => 0.10,  // R04: ahorro al subir el termostato / usar ventilador
        'led_w'                   => 9,     // R05: potencia de un bombillo LED equivalente
        'incandescente_w_min'     => 40,    // R05: desde cuántos W por bombillo se sugiere LED
        'horas_atencion'          => 12,    // R06, R10: horas diarias de atención de la microempresa
        'ducha_min_umbral'        => 20,    // R07: minutos de ducha desde los que se sugiere reducir
        'ducha_min_reducir'       => 10,    // R07: minutos que se sugiere recortar
        'plancha_horas_umbral'    => 1.0,   // R08
        'plancha_ahorro_horas'    => 0.25,  // R08: horas diarias que se evitan al planchar en una tanda
        'ahorro_vitrina_pct'      => 0.08,  // R09
        'nevera_w_alta'           => 200,   // R11: potencia por unidad desde la que se revisa la eficiencia
        'ahorro_reemplazo_pct'    => 0.20,  // R11: ahorro típico al reemplazar por un equipo eficiente
        'peso_equipo_dominante'   => 0.40,  // R13: participación en el consumo para "priorizar"
        'ahorro_dominante_pct'    => 0.10,  // R13
        'ahorro_reduccion_pct'    => 0.10,  // R14: reducción del consumo total que se propone
        'standby_horas_corte'     => 8,     // R01: horas nocturnas que se desconecta el equipo
    ],
];
