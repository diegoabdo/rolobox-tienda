<?php

return [

    /*
    | Zona horaria del negocio. La base de datos guarda todo en UTC; esta zona se usa
    | para mostrar fechas y calcular qué días de entrega se pueden elegir.
    */
    'timezone' => env('ROLOBOX_TIMEZONE', 'America/Guatemala'),

    'currency' => 'GTQ',

    'contact' => [
        'whatsapp' => env('ROLOBOX_WHATSAPP', ''),
        'email' => env('ROLOBOX_CONTACT_EMAIL', ''),
    ],

    /*
    | Cada pedido guarda la versión de términos y privacidad que aceptó el cliente.
    | Cambia este valor cada vez que publiques textos nuevos.
    */
    'legal_version' => env('ROLOBOX_LEGAL_VERSION', '2026-11-01-v1'),

    // Razón social que aparece en términos y privacidad.
    'legal_entity' => env('ROLOBOX_LEGAL_ENTITY', ''),

    // Mientras sea true, las páginas legales muestran el aviso de borrador. Ponlo en false
    // solo cuando los textos estén revisados.
    'legal_draft' => (bool) env('ROLOBOX_LEGAL_DRAFT', true),

    'delivery' => [
        // Días mínimos entre el pedido y la entrega local.
        'min_lead_days' => (int) env('ROLOBOX_MIN_LEAD_DAYS', 1),
        // Cuántos días hacia adelante se pueden elegir.
        'max_days_ahead' => 14,
        // Días sin reparto (0 = domingo … 6 = sábado).
        'closed_weekdays' => [0],
        // Franjas horarias del reparto local.
        'slots' => [
            '09-11' => '9:00 a 11:00',
            '11-13' => '11:00 a 13:00',
            '15-17' => '15:00 a 17:00',
        ],
    ],

    'cart' => [
        'max_lines' => 20,
        'max_quantity_per_line' => 20,
    ],

    'checkout' => [
        // Minutos que el link de pago queda vigente.
        'payment_ttl_minutes' => 60,
        // Las transferencias pueden tardar más en acreditarse.
        'transfer_ttl_minutes' => 1440,
    ],

    'payments' => [
        'driver' => env('PAYMENTS_DRIVER', 'fake'),

        // Comisión del procesador por cobro con tarjeta. Solo se usa para sugerir precios en el panel.
        'card_fee' => [
            'percent' => 4.5,
            'fixed_cents' => 200,
        ],

        // Cobro con tarjeta en persona (POS de Recurrente): sin cargo fijo.
        'pos_fee' => [
            'percent' => 4.5,
            'fixed_cents' => 0,
        ],

        'recurrente' => [
            'base_url' => env('RECURRENTE_BASE_URL', 'https://app.recurrente.com/api'),
            'secret_key' => env('RECURRENTE_SECRET_KEY'),
            'webhook_secret' => env('RECURRENTE_WEBHOOK_SECRET'),
            // Tolerancia de reloj para la firma del webhook, en segundos.
            'webhook_tolerance' => 300,
        ],
    ],
];
