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
                'description' => totranslate('Capturing all 4 knights doubles the Scopa points.'),
            ],
            SCP_VARIANT_NAPOLA => [
                'name' => totranslate('Napola'),
                'tmdisplay' => totranslate('Variant: Napola'),
                'description' => totranslate('Capturing Ace, 2 and 3 of coins is worth 3 points. Also capturing the 4 of coins is worth 4 points. Also capturing the 5 of coins is worth 5 points. And it goes on.'),
            ],
            SCP_VARIANT_SCOPONE => [
                'name' => totranslate('Scopone'),
                'tmdisplay' => totranslate('Variant: Scopone'),
                'description' => totranslate('Played in 2 teams of 2. Starts with 4 cards on table and 9 in player\'s hands.'),
            ],
            SCP_VARIANT_SCOPONE_SCIENTIFICO => [
                'name' => totranslate('Scopone Scientifico'),
                'tmdisplay' => totranslate('Variant: Scopone Scientifico'),
                'description' => totranslate('Played in 2 teams of 2. Starts with no card on table and 10 in player\'s hands.'),
            ],
            SCP_VARIANT_SCOPA_DI_QUINDICI => [
                'name' => totranslate('Scopa di Quindici'),
                'tmdisplay' => totranslate('Variant: Scopa di Quindici'),
                'description' => totranslate('Capturing cards is possible only if the sum of cards equals 15. Examples: 7 captures 8, King takes 5 or Ace + 4.'),
            ],
            SCP_VARIANT_SCOPONE_DE_TRENTE => [
                'name' => totranslate('Scopone de Trente'),
                'tmdisplay' => totranslate('Variant: Scopone de Trente'),
                'description' => totranslate('Played in 2 teams of 2 and with target of 21 points. Ace, 2 and 3 of coins are worth 1, 2 and 3 extra points. Capturing all three means immediate victory.'),
            ],/*
            SCP_VARIANT_ASSO_PIGLIA_TUTTO => [
                'name' => totranslate('Acopa d\'Assi / Asso piglia tutto'),
                'tmdisplay' => totranslate('Variant: Acopa d\'Assi / Asso piglia tutto'),
                'description' => totranslate('Ace captures all cards on table (and it counts as a scopa).'),
            ],*/
            SCP_VARIANT_RE_BELLO => [
                'name' => totranslate('Re bello'),
                'tmdisplay' => totranslate('Variant: Re bello'),
                'description' => totranslate('The king of coins is worth an extra point.'),
            ],/*
            SCP_VARIANT_SCOPA_A_PERDERE => [
                'name' => totranslate('Scopa a perdere'),
                'tmdisplay' => totranslate('Variant: Scopa a perdere'),
                'description' => totranslate('The goal is to mark as little points as possible. First to reach the target (normally 21) loses.'),
            ],
            SCP_VARIANT_SCOPA_FRAC => [
                'name' => totranslate('Scopa Frac'),
                'tmdisplay' => totranslate('Variant: Scopa Frac'),
                'description' => totranslate('Aces, Jacks, Knights and Kings are each worth 1 point. This is the only way to mark points. In case of equality, the winner is who captured the King of coins. If a player can capture 1 or multiple cards, he can choose to capture multiple cards.'),
            ],*/
        ],
        'default' => SCP_VARIANT_SCOPA,
        'startcondition' => [
            SCP_VARIANT_SCOPONE => [
                [
                    'type' => 'otheroption',
                    'id' => SCP_TEAM_PLAY,
                    'value' => SCP_TEAM_PLAY_YES,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
                [
                    'type' => 'minplayers',
                    'value' => 4,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
                [
                    'type' => 'maxplayers',
                    'value' => 4,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
            ],
            SCP_VARIANT_SCOPONE_SCIENTIFICO => [
                [
                    'type' => 'otheroption',
                    'id' => SCP_TEAM_PLAY,
                    'value' => SCP_TEAM_PLAY_YES,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
                [
                    'type' => 'minplayers',
                    'value' => 4,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
                [
                    'type' => 'maxplayers',
                    'value' => 4,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
            ],
            SCP_VARIANT_SCOPONE_DE_TRENTE => [
                [
                    'type' => 'otheroption',
                    'id' => SCP_TEAM_PLAY,
                    'value' => SCP_TEAM_PLAY_YES,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
                [
                    'type' => 'otheroption',
                    'id' => SCP_OPTION_POINTS_TO_WIN,
                    'value' => SCP_OPTION_POINTS_TO_WIN_21,
                    'message' => totranslate('This variant is played with a target of 21 points.'),
                ],
                [
                    'type' => 'minplayers',
                    'value' => 4,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
                [
                    'type' => 'maxplayers',
                    'value' => 4,
                    'message' => totranslate('This variant is played only in 2 teams of 2.'),
                ],
            ],
        ],
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
        ],
        'notdisplayedmessage' => totranslate('Playing in teams is possible only with 4 or 6 players.'),
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
