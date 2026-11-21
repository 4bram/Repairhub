<?php

return [
    'campos' => [
        'Automóvil' => [
            ['clave' => 'placa', 'etiqueta' => 'Placa', 'requerido' => true],
            ['clave' => 'anio', 'etiqueta' => 'Año'],
            ['clave' => 'color', 'etiqueta' => 'Color'],
            ['clave' => 'kilometraje', 'etiqueta' => 'Kilometraje'],
            ['clave' => 'combustible', 'etiqueta' => 'Combustible'],
            ['clave' => 'transmision', 'etiqueta' => 'Transmisión'],
        ],
        'Motocicleta' => [
            ['clave' => 'placa', 'etiqueta' => 'Placa', 'requerido' => true],
            ['clave' => 'anio', 'etiqueta' => 'Año'],
            ['clave' => 'color', 'etiqueta' => 'Color'],
            ['clave' => 'kilometraje', 'etiqueta' => 'Kilometraje'],
            ['clave' => 'cilindrada', 'etiqueta' => 'Cilindrada (cc)'],
        ],
        'Laptop' => [
            ['clave' => 'procesador', 'etiqueta' => 'Procesador'],
            ['clave' => 'ram', 'etiqueta' => 'RAM'],
            ['clave' => 'almacenamiento', 'etiqueta' => 'Almacenamiento'],
            ['clave' => 'sistema_operativo', 'etiqueta' => 'Sistema operativo'],
        ],
        'Computadora de escritorio' => [
            ['clave' => 'procesador', 'etiqueta' => 'Procesador'],
            ['clave' => 'ram', 'etiqueta' => 'RAM'],
            ['clave' => 'almacenamiento', 'etiqueta' => 'Almacenamiento'],
            ['clave' => 'sistema_operativo', 'etiqueta' => 'Sistema operativo'],
        ],
        'Celular' => [
            ['clave' => 'imei', 'etiqueta' => 'IMEI'],
            ['clave' => 'almacenamiento', 'etiqueta' => 'Almacenamiento'],
            ['clave' => 'color', 'etiqueta' => 'Color'],
        ],
        'Tableta' => [
            ['clave' => 'imei', 'etiqueta' => 'IMEI'],
            ['clave' => 'almacenamiento', 'etiqueta' => 'Almacenamiento'],
        ],
        'Lavadora' => [
            ['clave' => 'capacidad_kg', 'etiqueta' => 'Capacidad (kg)'],
            ['clave' => 'tipo_carga', 'etiqueta' => 'Tipo de carga'],
        ],
        'Refrigerador' => [
            ['clave' => 'capacidad_litros', 'etiqueta' => 'Capacidad (litros)'],
        ],
    ],
];