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

require_once("modules/constants.inc.php");


$game_options = [
    100 => [
        'name' => totranslate('Points to win'),
        'values' => [
            1 => ['name' => totranslate('11 points'), 'tmdisplay' => totranslate('11 points to win')],
            2 => ['name' => totranslate('16 points'), 'tmdisplay' => totranslate('16 points to win')],
            3 => ['name' => totranslate('21 points'), 'tmdisplay' => totranslate('21 points to win')],
            4 => ['name' => totranslate('31 points'), 'tmdisplay' => totranslate('31 points to win')],
        ],
        'default' => 1,
    ],
    101 => [
        'name' => totranslate('Max number of cards captured'),
        'values' => [
            1 => ['name' => totranslate('2 cards'), 'tmdisplay' => 'Max of 2 cards captured'],
            2 => ['name' => totranslate('No limit'), 'tmdisplay' => 'No limit on capture'],
        ],
        'default' => 2,
    ],
    102 => [
        'name' => totranslate('Variant'),
        'values' => [
            SCP_VARIANT_SCOPA => [
                'name' => totranslate('Standard Scopa'),
                'tmdisplay' => totranslate('Standard Scopa'),
                'description' => totranslate('Standard game of Scopa'),
            ],
            SCP_VARIANT_IL_PONINO => [
                'name' => totranslate('Il Ponino'),
                'tmdisplay' => totranslate('Variant: Il Ponino'),
                'description' => totranslate('Capturing all 4 knights doubles the Scopa points'),
            ],
        ],
        'default' => SCP_VARIANT_SCOPA,
    ],
];
