<?php

namespace App\Support;

/**
 * Traducciones EN→ES para los datos que llegan de la API de F1.
 *
 * Pensado para aplicarlo al sincronizar (ServicioApiF1) y al hacer un
 * "backfill" de los datos ya guardados en la base de datos.
 */
class Traducciones
{
    /** Nacionalidades (Ergast / Jolpica usan gentilicios en inglés) */
    public const NACIONALIDADES = [
        'American'      => 'Estadounidense',
        'Argentine'     => 'Argentino',
        'Argentinian'   => 'Argentino',
        'Australian'    => 'Australiano',
        'Austrian'      => 'Austriaco',
        'Belgian'       => 'Belga',
        'Brazilian'     => 'Brasileño',
        'British'       => 'Británico',
        'Bulgarian'     => 'Búlgaro',
        'Canadian'      => 'Canadiense',
        'Chinese'       => 'Chino',
        'Colombian'     => 'Colombiano',
        'Czech'         => 'Checo',
        'Danish'        => 'Danés',
        'Dutch'         => 'Neerlandés',
        'East German'   => 'Alemán',
        'Finnish'       => 'Finlandés',
        'French'        => 'Francés',
        'German'        => 'Alemán',
        'Hungarian'     => 'Húngaro',
        'Indian'        => 'Indio',
        'Indonesian'    => 'Indonesio',
        'Irish'         => 'Irlandés',
        'Italian'       => 'Italiano',
        'Japanese'      => 'Japonés',
        'Liechtensteiner' => 'Liechtensteiniano',
        'Malaysian'     => 'Malasio',
        'Mexican'       => 'Mexicano',
        'Monegasque'    => 'Monegasco',
        'New Zealander' => 'Neozelandés',
        'Polish'        => 'Polaco',
        'Portuguese'    => 'Portugués',
        'Rhodesian'     => 'Rodesiano',
        'Russian'       => 'Ruso',
        'South African' => 'Sudafricano',
        'Spanish'       => 'Español',
        'Swedish'       => 'Sueco',
        'Swiss'         => 'Suizo',
        'Thai'          => 'Tailandés',
        'Uruguayan'     => 'Uruguayo',
        'Venezuelan'    => 'Venezolano',
    ];

    /**
     * Status del piloto en una carrera (race_results.status).
     * IMPORTANTE: ServicioPuntuacion usa estos valores para detectar
     * abandonos/descalificaciones, así que las claves "técnicas" se
     * mantienen estables (Abandono / Descalificado / No salió).
     */
    public const STATUS_RESULTADO = [
        'Finished'        => 'Finalizó',
        'Lapped'          => 'Doblado',
        'Retired'         => 'Abandono',
        'DNF'             => 'Abandono',
        'Did not start'   => 'No salió',
        'DNS'             => 'No salió',
        'Disqualified'    => 'Descalificado',
        'Mechanical'      => 'Mecánico',
        'Engine'          => 'Motor',
        'Gearbox'         => 'Caja de cambios',
        'Transmission'    => 'Transmisión',
        'Hydraulics'      => 'Hidráulica',
        'Electrical'      => 'Eléctrico',
        'Accident'        => 'Accidente',
        'Collision'       => 'Colisión',
        'Spun off'        => 'Trompo',
        'Brakes'          => 'Frenos',
        'Suspension'      => 'Suspensión',
        'Overheating'     => 'Sobrecalentamiento',
        'Out of fuel'     => 'Sin combustible',
        'Oil leak'        => 'Fuga de aceite',
        'Tyre'            => 'Neumático',
        'Wheel'           => 'Rueda',
        'Driver Seat'     => 'Asiento',
        'Power Unit'      => 'Unidad de potencia',
        'ERS'             => 'ERS',
        'Battery'         => 'Batería',
        'Withdrew'        => 'Retirado',
        'Not classified'  => 'No clasificado',
        'Excluded'        => 'Excluido',
        'Unknown'         => 'Desconocido',
    ];

    public static function nacionalidad(?string $valor): ?string
    {
        if ($valor === null || $valor === '') return $valor;
        return self::NACIONALIDADES[$valor] ?? $valor;
    }

    public static function statusResultado(?string $valor): ?string
    {
        if ($valor === null || $valor === '') return $valor;
        return self::STATUS_RESULTADO[$valor] ?? $valor;
    }

    /** Considera abandono cualquiera de estos status (en español) */
    public const STATUS_ABANDONO = [
        'Abandono', 'Accidente', 'Mecánico', 'Colisión', 'Motor',
        'Caja de cambios', 'Transmisión', 'Hidráulica', 'Eléctrico',
        'Frenos', 'Suspensión', 'Sobrecalentamiento', 'Sin combustible',
        'Fuga de aceite', 'Neumático', 'Rueda', 'Trompo',
        'Unidad de potencia', 'ERS', 'Batería',
    ];
}
