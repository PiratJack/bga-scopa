<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * scopa implementation : © Jacques de Metz <demetz.jacques@gmail.com>.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */
$stats_type = [
    // Statistics global to table
    'table' => [
    ],

    // Statistics existing for each player
    'player' => [
        'scopa_number' => ['id' => 10,
            'name' => totranslate('Number of sweeps done (scopa)'),
            'type' => 'int', ],
        'sette_bello' => ['id' => 11,
            'name' => totranslate('Wins with 7 of coins (sette bello)'),
            'type' => 'int', ],
        'cards_captured' => ['id' => 12,
            'name' => totranslate('Wins with max cards captured'),
            'type' => 'int', ],
        'coins_captured' => ['id' => 13,
            'name' => totranslate('Wins with max coin cards captured'),
            'type' => 'int', ],
        'prime_score' => ['id' => 14,
            'name' => totranslate('Wins with prime'),
            'type' => 'int', ],
    ],
];
