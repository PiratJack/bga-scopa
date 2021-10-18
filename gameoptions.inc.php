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
    SCP_OPTION_POINTS_TO_WIN => [
        'name' => totranslate('Points to win'),
        'values' => [
            SCP_OPTION_POINTS_TO_WIN_11 => ['name' => totranslate('11 points'), 'tmdisplay' => totranslate('11 points to win')],
            SCP_OPTION_POINTS_TO_WIN_16 => ['name' => totranslate('16 points'), 'tmdisplay' => totranslate('16 points to win')],
            SCP_OPTION_POINTS_TO_WIN_21 => ['name' => totranslate('21 points'), 'tmdisplay' => totranslate('21 points to win')],
            SCP_OPTION_POINTS_TO_WIN_31 => ['name' => totranslate('31 points'), 'tmdisplay' => totranslate('31 points to win')],
        ],
        'default' => SCP_OPTION_POINTS_TO_WIN_11,
    ],
    SCP_OPTION_MAX_CAPTURE => [
        'name' => totranslate('Max number of cards captured'),
        'values' => [
            SCP_OPTION_MAX_CAPTURE_2 => ['name' => totranslate('2 cards'), 'tmdisplay' => 'Max of 2 cards captured'],
            SCP_OPTION_MAX_CAPTURE_ANY => ['name' => totranslate('No limit'), 'tmdisplay' => 'No limit on capture'],
        ],
        'default' => SCP_OPTION_MAX_CAPTURE_ANY,
    ],
    SCP_VARIANT => [
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
    SCP_TEAM_PLAY => [
        'name' => totranslate('Team play'),
        'values' => [
            SCP_TEAM_PLAY_YES => [
                'name' => totranslate('Play in teams'),
                'tmdisplay' => totranslate('Play in teams'),
            ],
            SCP_TEAM_PLAY_NO => [
                'name' => totranslate('Individual play'),
                'tmdisplay' => totranslate('Individual play'),
            ],
        ],
        'displaycondition' => [
            [
                'type' => 'minplayers',
                'value' => 4,
            ],
            // Hide Teams if ELO is enabled
            [
                'type' => 'otheroption',
                'id' => 201,
                'value' => 1,
            ]
        ],
        'notdisplayedmessage' => totranslate('Playing in teams is possible only with 4 or 6 players and with ELO off.'),
        'displayconditionoperand' => 'and',
        'default' => SCP_TEAM_PLAY_NO,
    ],
];


$game_preferences = [
    SCP_PREF_DISPLAY_LABELS => [
        'name' => totranslate('Display labels on cards?'),
        'needReload' => false, // Handled through JS directly
        'values' => [
            SCP_PREF_DISPLAY_LABELS_YES => [ 'name' => totranslate('Yes') ],
            SCP_PREF_DISPLAY_LABELS_NO => [ 'name' => totranslate('No') ]
        ],
        'default' => SCP_PREF_DISPLAY_LABELS_YES
    ],
    SCP_PREF_CARD_DECK => [
        'name' => totranslate('Card deck'),
        'needReload' => false, // Handled through JS directly
        'values' => [
            SCP_PREF_CARD_DECK_ITALIAN => [ 'name' => totranslate('Italian') ],
            SCP_PREF_CARD_DECK_STANDARD => [ 'name' => totranslate('Standard') ]
        ],
        'default' => SCP_PREF_CARD_DECK_ITALIAN
    ],
    SCP_PREF_AUTO_PLAY => [
        'name' => totranslate('Auto-play last card?'),
        'needReload' => false, // Doesn't generate any display change
        'values' => [
            SCP_PREF_AUTO_PLAY_YES => [ 'name' => totranslate('Yes') ],
            SCP_PREF_AUTO_PLAY_NO => [ 'name' => totranslate('No') ]
        ],
        'default' => SCP_PREF_AUTO_PLAY_NO
    ],
];
