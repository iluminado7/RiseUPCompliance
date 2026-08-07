<?php

return [

    /*
    | Clave maestra de datos personales — hex de 64 caracteres.
    |
    | Deliberadamente separada de APP_KEY: esa cifra cookies y sesiones, y
    | rotarla desloguea a todos. Los datos personales tienen otro ciclo de
    | vida y otra política de rotación; mezclarlas hace imposible rotar una
    | sin la otra.
    */
    'clave_maestra' => env('ENCRYPTION_KEY'),

    /*
    | ID de la clave activa. Cada registro cifrado guarda con cuál se cifró,
    | para poder descifrar lo viejo después de una rotación. Formato: key-vN
    */
    'clave_activa_id' => env('ACTIVE_KEY_ID', 'key-v1'),

    /*
    | Claves retiradas, para descifrar registros anteriores a una rotación.
    */
    'claves_anteriores' => [
        'key-v1' => env('ENCRYPTION_KEY_V1'),
        'key-v2' => env('ENCRYPTION_KEY_V2'),
    ],

    /*
    | Clave del HMAC con que se hashean las IPs.
    |
    | El sistema original usaba hash('sha256', $ip) sin clave. El espacio
    | IPv4 completo son 4.300 millones de valores: precalcular la tabla
    | entera es cuestión de minutos, así que ese hash era reversible.
    |
    | Con HMAC y una clave que vive solo en el entorno, un dump de la base
    | ya no alcanza para recuperar la IP. Importa sobre todo en
    | tracking_sessions, que relaciona una IP con una denuncia.
    */
    'clave_hash_ip' => env('IP_HASH_KEY'),

];
