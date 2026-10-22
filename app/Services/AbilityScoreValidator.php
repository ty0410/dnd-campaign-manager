<?php

namespace App\Services;

class AbilityScoreValidator
{
    private const ABILITIES = [
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
    ];

    /**
     * Coste de cada puntuación en el método de compra de puntos.
     * Las puntuaciones de 1 a 8 no consumen puntos.
     */
    private const POINT_COSTS = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
        6 => 0,
        7 => 0,
        8 => 0,
        9 => 1,
        10 => 2,
        11 => 3,
        12 => 4,
        13 => 5,
        14 => 7,
        15 => 9,
    ];

    private const MAX_POINT_BUY_POINTS = 27;

    /**
     * Devuelve los errores de las reglas de asignación de características.
     */
    public function validate(array $data): array
    {
        $errors = [];
        $method = $data['ability_score_method'] ?? null;
        $scores = [];

        // Si no se ha elegido un método, permitimos fichas incompletas
        // para mantener la compatibilidad con personajes existentes.
        foreach (self::ABILITIES as $ability) {
            $score = $data[$ability] ?? null;

            if ($score === null || $score === '') {
                if ($method !== null) {
                    $errors[$ability][] =
                        'Debes indicar esta característica al elegir un método.';
                }

                continue;
            }

            // Rechazar valores que no sean números enteros.
            if (
                !is_numeric($score)
                || filter_var($score, FILTER_VALIDATE_INT) === false
            ) {
                $errors[$ability][] =
                    'La característica debe ser un número entero.';

                continue;
            }

            $score = (int) $score;
            $scores[$ability] = $score;

            // Método manual: puntuaciones de 1 a 20.
            if ($method === 'manual' && ($score < 1 || $score > 20)) {
                $errors[$ability][] =
                    'En el método manual, la puntuación debe estar entre 1 y 20.';
            }

            // Array estándar: valores editables de 1 a 20.
            // Ya no se exige conservar los seis valores originales.
            if (
                $method === 'standard_array'
                && ($score < 1 || $score > 20)
            ) {
                $errors[$ability][] =
                    'En el array estándar, la puntuación debe estar entre 1 y 20.';
            }

            // Compra de puntos: puntuaciones de 1 a 15.
            if (
                $method === 'point_buy'
                && ($score < 1 || $score > 15)
            ) {
                $errors[$ability][] =
                    'En la compra de puntos, la puntuación debe estar entre 1 y 15.';
            }
        }

        if ($method === null) {
            return $errors;
        }

        // Rechazar métodos desconocidos.
        if (!in_array(
            $method,
            ['manual', 'standard_array', 'point_buy'],
            true
        )) {
            $errors['ability_score_method'][] =
                'El método de asignación de características no es válido.';

            return $errors;
        }

        // Comprobar el coste de compra cuando las seis características
        // se han introducido y son válidas para este método.
        if ($method === 'point_buy' && count($scores) === 6) {
            $totalCost = 0;
            $allScoresValid = true;

            foreach ($scores as $score) {
                if ($score < 1 || $score > 15) {
                    $allScoresValid = false;
                    break;
                }

                $totalCost += self::POINT_COSTS[$score];
            }

            if (
                $allScoresValid
                && $totalCost > self::MAX_POINT_BUY_POINTS
            ) {
                $errors['ability_score_method'][] =
                    "La compra de puntos cuesta {$totalCost} puntos; "
                    . 'el máximo permitido es '
                    . self::MAX_POINT_BUY_POINTS
                    . '.';
            }
        }

        return $errors;
    }
}
