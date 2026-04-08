<?php
/**
 * RUGAL - Breed Data
 * Healthy weight ranges for common breeds
 * v2.0 - Datos completos y promedios para planes personalizados
 */

function obtenerConfiguracionRazas() {
    return [
        // RAZAS PEQUEÑAS
        'bulldog frances' => [
            'peso_min' => 8,
            'peso_max' => 14,
            'peso_promedio' => 11,
            'tamano' => 'pequeno',
            'energia' => 'baja',
            'esperanza_vida' => 12,
            'recomendacion' => 'Cuidado con el calor y exceso de ejercicio. Vigilancia de problemas respiratorios.'
        ],
        'chihuahua' => [
            'peso_min' => 1.5,
            'peso_max' => 3,
            'peso_promedio' => 2.2,
            'tamano' => 'pequeno',
            'energia' => 'media',
            'esperanza_vida' => 15,
            'recomendacion' => 'Cuidado con climas fríos. Dental sensitivo requiere limpieza regular.'
        ],
        'pug' => [
            'peso_min' => 6,
            'peso_max' => 8,
            'peso_promedio' => 7,
            'tamano' => 'pequeno',
            'energia' => 'baja',
            'esperanza_vida' => 13,
            'recomendacion' => 'Limpieza de pliegues faciales diaria. Vigilancia respiratoria.'
        ],
        'shih tzu' => [
            'peso_min' => 4,
            'peso_max' => 7,
            'peso_promedio' => 5.5,
            'tamano' => 'pequeno',
            'energia' => 'media',
            'esperanza_vida' => 13,
            'recomendacion' => 'Higiene ocular y peluquería regular. Cuidado con infecciones de oído.'
        ],
        'schnauzer' => [
            'peso_min' => 5,
            'peso_max' => 9,
            'peso_promedio' => 7,
            'tamano' => 'pequeno',
            'energia' => 'media',
            'esperanza_vida' => 14,
            'recomendacion' => 'Sensibles a grasas en la dieta. Pueden tener predisposición a cálculos urinarios.'
        ],
        'beagle' => [
            'peso_min' => 9,
            'peso_max' => 11,
            'peso_promedio' => 10,
            'tamano' => 'pequeno',
            'energia' => 'alta',
            'esperanza_vida' => 13,
            'recomendacion' => 'Tienden a seguir rastros, cuidado con escapes. Control de peso importante.'
        ],
        'poodle' => [
            'peso_min' => 2,
            'peso_max' => 32,
            'peso_promedio' => 15,
            'tamano' => 'pequeno',
            'energia' => 'alta',
            'esperanza_vida' => 14,
            'recomendacion' => 'Muy inteligentes, requieren retos cognitivos. Sensibles a problemas de oído.'
        ],
        
        // RAZAS MEDIANAS
        'cocker spaniel' => [
            'peso_min' => 12,
            'peso_max' => 15,
            'peso_promedio' => 13.5,
            'tamano' => 'mediano',
            'energia' => 'alta',
            'esperanza_vida' => 13,
            'recomendacion' => 'Ejercicio diario importante. Cuidado con las infecciones de oído.'
        ],
        'bulldog ingles' => [
            'peso_min' => 18,
            'peso_max' => 25,
            'peso_promedio' => 21,
            'tamano' => 'mediano',
            'energia' => 'baja',
            'esperanza_vida' => 9,
            'recomendacion' => 'Control de peso estricto para proteger articulaciones. Vigilancia respiratoria.'
        ],
        'spaniel cocker' => [
            'peso_min' => 12,
            'peso_max' => 16,
            'peso_promedio' => 14,
            'tamano' => 'mediano',
            'energia' => 'alta',
            'esperanza_vida' => 13,
            'recomendacion' => 'Activos y amigables. Cepillado regular y control de infecciones de oído.'
        ],
        'border collie' => [
            'peso_min' => 12,
            'peso_max' => 19,
            'peso_promedio' => 15.5,
            'tamano' => 'mediano',
            'energia' => 'muy_alta',
            'esperanza_vida' => 13,
            'recomendacion' => 'Raza ultra inteligente. Necesita estimulación mental y física intensa diariamente.'
        ],
        'dálmata' => [
            'peso_min' => 15,
            'peso_max' => 32,
            'peso_promedio' => 23,
            'tamano' => 'mediano',
            'energia' => 'alta',
            'esperanza_vida' => 12,
            'recomendacion' => 'Energía muy alta. Vigilancia de sordera congénita y predisposición a cálculos urinarios.'
        ],

        // RAZAS COMUNES (PITBULL / AMERICAN BULLY)
        'pitbull' => [
            'peso_min' => 13,
            'peso_max' => 35,
            'peso_promedio' => 24,
            'tamano' => 'mediano',
            'energia' => 'muy_alta',
            'esperanza_vida' => 12,
            'recomendacion' => 'Raza con alta energía y musculatura. Necesita ejercicio diario y estimulación mental. Control de peso para evitar problemas articulares.'
        ],
        'american bully' => [
            'peso_min' => 20,
            'peso_max' => 50,
            'peso_promedio' => 32,
            'tamano' => 'mediano-grande',
            'energia' => 'alta',
            'esperanza_vida' => 11,
            'recomendacion' => 'Musculosos y activos; requieren ejercicio regular y control de peso. Supervisar socialización y entrenamiento.'
        ],
        
        // RAZAS GRANDES
        'labrador retriever' => [
            'peso_min' => 25,
            'peso_max' => 36,
            'peso_promedio' => 30,
            'tamano' => 'grande',
            'energia' => 'alta',
            'esperanza_vida' => 11,
            'recomendacion' => 'Necesita ejercicio diario intenso y estimulación mental. Control de displasia de cadera.'
        ],
        'golden retriever' => [
            'peso_min' => 25,
            'peso_max' => 34,
            'peso_promedio' => 30,
            'tamano' => 'grande',
            'energia' => 'alta',
            'esperanza_vida' => 11,
            'recomendacion' => 'Cepillado frecuente y natación si es posible. Vigilancia de problemas cardíacos.'
        ],
        'pastor aleman' => [
            'peso_min' => 22,
            'peso_max' => 40,
            'peso_promedio' => 31,
            'tamano' => 'grande',
            'energia' => 'muy_alta',
            'esperanza_vida' => 11,
            'recomendacion' => 'Vigilancia de cadera y socialización temprana. Estimulación mental constante.'
        ],
        'rottweiler' => [
            'peso_min' => 35,
            'peso_max' => 60,
            'peso_promedio' => 47,
            'tamano' => 'grande',
            'energia' => 'media',
            'esperanza_vida' => 10,
            'recomendacion' => 'Necesita socialización temprana. Control de displasia y problemas cardíacos.'
        ],
        'doberman' => [
            'peso_min' => 27,
            'peso_max' => 45,
            'peso_promedio' => 36,
            'tamano' => 'grande',
            'energia' => 'muy_alta',
            'esperanza_vida' => 11,
            'recomendacion' => 'Muy leal y protector. Requiere entrenamiento firme y ejercicio regular.'
        ],
        'gran danes' => [
            'peso_min' => 46,
            'peso_max' => 90,
            'peso_promedio' => 68,
            'tamano' => 'gigante',
            'energia' => 'baja',
            'esperanza_vida' => 8,
            'recomendacion' => 'Raza grande con esperanza vida corta. Vigilancia cardíaca y articular importante.'
        ],
        'san bernardo' => [
            'peso_min' => 65,
            'peso_max' => 120,
            'peso_promedio' => 90,
            'tamano' => 'gigante',
            'energia' => 'media',
            'esperanza_vida' => 9,
            'recomendacion' => 'Sensibles al calor. Cepillado regular y control de displasia de cadera.'
        ],
        'husky siberiano' => [
            'peso_min' => 15,
            'peso_max' => 28,
            'peso_promedio' => 21,
            'tamano' => 'mediano-grande',
            'energia' => 'muy_alta',
            'esperanza_vida' => 12,
            'recomendacion' => 'Energía extremadamente alta. Escape artists, requieren cercos altos y ejercicio intenso.'
        ],
        
        // DEFAULT
        'mestizo' => [
            'peso_min' => 5,
            'peso_max' => 30,
            'peso_promedio' => 17.5,
            'tamano' => 'mediano',
            'energia' => 'media',
            'esperanza_vida' => 12,
            'recomendacion' => 'Plan de mantenimiento equilibrado. Cada mestizo es único, ajustar según comportamiento.'
        ]
    ];
}

function obtenerDatosRaza($razaNombre) {
    $razas_data = obtenerConfiguracionRazas();
    $razaNombre = strtolower(trim($razaNombre));
    
    foreach ($razas_data as $key => $data) {
        if (strpos($razaNombre, $key) !== false) {
            return $data;
        }
    }
    
    // Default for unknown breed
    return [
        'peso_min' => 5,
        'peso_max' => 30,
        'peso_promedio' => 17.5,
        'tamano' => 'mediano',
        'energia' => 'media',
        'esperanza_vida' => 12,
        'recomendacion' => 'Mantener chequeos veterinarios regulares.'
    ];
}

/**
 * Función para obtener lista de razas disponibles
 */
function obtenerListaRazas() {
    return array_keys(obtenerConfiguracionRazas());
}
