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
$game_options = [
    100 => [
        'name' => totranslate('Points to win'),
        'values' => [
            1 => ['name' => totranslate('11 points')],
            2 => ['name' => totranslate('16 points')],
            3 => ['name' => totranslate('21 points')],
            4 => ['name' => totranslate('31 points')],
        ],
        'default' => 1,
    ],
    101 => [
        'name' => totranslate('Max number of cards captured'),
        'values' => [
            1 => ['name' => totranslate('2 cards')],
            2 => ['name' => totranslate('No limit')],
        ],
        'default' => 2,
    ],
];
