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
            SCP_OPTION_POINTS_TO_WIN_51 => ['name' => totranslate('51 points (mostly for Cirulla)'), 'tmdisplay' => totranslate('51 points to win')],
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
                'name' => totranslate('Scopone classico'),
                'tmdisplay' => totranslate('Variant: Scopone classico'),
                'description' => totranslate('Played in 2 teams of 2. Starts with 4 cards on table and 9 in player\'s hands.'),
            ],
            SCP_VARIANT_SCOPONE_SCIENTIFICO => [
                'name' => totranslate('Scopone Scientifico / Scopone a 10'),
                'tmdisplay' => totranslate('Variant: Scopone Scientifico / Scopone a 10'),
                'description' => totranslate('Played in 2 teams of 2. Starts with no card on table and 10 in player\'s hands.'),
            ],
            SCP_VARIANT_SCOPA_DI_QUINDICI => [
                'name' => totranslate('Scopa a Quindici'),
                'tmdisplay' => totranslate('Variant: Scopa a Quindici'),
                'description' => totranslate('Capturing cards is possible only if the sum of cards equals 15. Examples: 7 captures 8, King takes 5 or Ace + 4.'),
            ],
            SCP_VARIANT_SCOPONE_DE_TRENTE => [
                'name' => totranslate('Scopone de Trente'),
                'tmdisplay' => totranslate('Variant: Scopone de Trente'),
                'description' => totranslate('Played in 2 teams of 2 and with target of 21 points. Ace, 2 and 3 of coins are worth 1, 2 and 3 extra points. Capturing all three means immediate victory.'),
            ],
            SCP_VARIANT_ASSO_PIGLIA_TUTTO => [
                'name' => totranslate('Scopa d\'Assi / Asso piglia tutto (simplified)'),
                'tmdisplay' => totranslate('Variant: Scopa d\'Assi / Asso piglia tutto (simplified)'),
                'description' => totranslate('Ace captures all cards on table (and it counts as a scopa).'),
            ],
            SCP_VARIANT_ASSO_PIGLIA_TUTTO_TRADITIONAL => [
                'name' => totranslate('Scopa d\'Assi / Asso piglia tutto (traditional)'),
                'tmdisplay' => totranslate('Variant: Scopa d\'Assi / Asso piglia tutto (traditional)'),
                'description' => totranslate('Ace captures all cards on table (and it counts as a scopa). If there\'s an ace on the table or if you\'re the first player, the ace only captures other aces.'),
            ],
            SCP_VARIANT_RE_BELLO => [
                'name' => totranslate('Re bello'),
                'tmdisplay' => totranslate('Variant: Re bello'),
                'description' => totranslate('The king of coins is worth an extra point.'),
            ],
            SCP_VARIANT_SCOPA_A_PERDERE => [
                'name' => totranslate('Scopa a perdere'),
                'tmdisplay' => totranslate('Variant: Scopa a perdere'),
                'description' => totranslate('The goal is to mark as little points as possible. First to reach the target (normally 21) loses.'),
            ],
            SCP_VARIANT_SCOPA_FRAC => [
                'name' => totranslate('Scopa Frac'),
                'tmdisplay' => totranslate('Variant: Scopa Frac'),
                'description' => totranslate('Aces, Jacks, Knights and Kings are each worth 1 point. This is the only way to mark points. In case of equality, the winner is who captured the King of coins. If a player can capture 1 or multiple cards, he can choose to capture multiple cards.'),
            ],
            SCP_VARIANT_ESCOBA => [
                'name' => totranslate('Escoba'),
                'tmdisplay' => totranslate('Variant: Escoba'),
                'description' => totranslate('Capturing cards is possible only if the sum of cards equals 15. Extra point for capturing most sevens and all of the sevens.'),
            ],
            SCP_VARIANT_ESCOBA_NO_PRIME => [
                'name' => totranslate('Escoba without Prime'),
                'tmdisplay' => totranslate('Variant: Escoba without Prime points'),
                'description' => totranslate('Capturing cards is possible only if the sum of cards equals 15. Extra point for capturing most sevens and all of the sevens. Prime points are not counted'),
            ],
            SCP_VARIANT_CIRULLA => [
                'name' => totranslate('Cirulla'),
                'tmdisplay' => totranslate('Variant: Cirulla'),
                'description' => totranslate('This variant is a combination of several others, plus additional specific rules. Please refer to the game help for the full rules. Usually played in 51 points'),
            ],
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
            SCP_VARIANT_SCOPA_FRAC => [
                [
                    'type' => 'otheroption',
                    'id' => SCP_OPTION_MAX_CAPTURE,
                    'value' => SCP_OPTION_MAX_CAPTURE_ANY,
                    'message' => totranslate('In this variant, there is no limit to the number of cards to capture.'),
                ],
            ],
        ],
    ],
    SCP_VARIANT_NAPOLA_ENABLED => [
        'name' => totranslate('Napola variant'),
        'values' => [
            SCP_VARIANT_NAPOLA_ENABLED_YES => [
                'name' => totranslate('Enabled'),
                'tmdisplay' => totranslate('Napola variant enabled'),
                'description' => totranslate('Capturing Ace, 2 and 3 of coins is worth 3 points. Also capturing the 4 of coins is worth 4 points. Also capturing the 5 of coins is worth 5 points. And it goes on.'),
            ],
            SCP_VARIANT_NAPOLA_ENABLED_NO => [
                'name' => totranslate('Disabled'),
            ],
        ],
        'default' => SCP_VARIANT_NAPOLA_ENABLED_NO,
        'displaycondition' => [
            [
                'type' => 'otheroptionisnot',
                'id' => SCP_VARIANT,
                'value' => SCP_VARIANT_NAPOLA,
            ],
        ],
        'notdisplayedmessage' => totranslate('No need to enable Napola twice')
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
                'value' => [4, 6],
            ],
        ],
        'notdisplayedmessage' => totranslate('Playing in teams is possible only with 4 or 6 players.'),
        'default' => SCP_TEAM_PLAY_NO,
    ],
    SCP_TEAM_COMPOSITION => [
        'name' => totranslate('Team determination'),
        'values' => [
            SCP_TEAM_COMPOSITION_RANDOM => [
                'name' => totranslate('Random')
            ],
            SCP_TEAM_COMPOSITION_1_2 => [
                'name' => totranslate('By lobby display order: 1st/2nd versus 3rd/4th (4 players only)')
            ],
            SCP_TEAM_COMPOSITION_1_3 => [
                'name' => totranslate('By lobby display order: 1st/3rd versus 2nd/4th (4 players only)')
            ],
            SCP_TEAM_COMPOSITION_1_4 => [
                'name' => totranslate('By lobby display order: 1st/4th versus 2nd/3rd (4 players only)')
            ],
            SCP_TEAM_COMPOSITION_12_34_56 => [ 'name' => totranslate('By lobby display order: 1st/2nd vs 3rd/4th vs 5th/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_12_35_46 => [ 'name' => totranslate('By lobby display order: 1st/2nd vs 3rd/5th vs 4th/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_12_36_45 => [ 'name' => totranslate('By lobby display order: 1st/2nd vs 3rd/6th vs 4th/5th (6 players only)') ],
            SCP_TEAM_COMPOSITION_13_24_56 => [ 'name' => totranslate('By lobby display order: 1st/3rd vs 2nd/4th vs 5th/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_13_25_46 => [ 'name' => totranslate('By lobby display order: 1st/3rd vs 2nd/5th vs 4th/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_13_26_45 => [ 'name' => totranslate('By lobby display order: 1st/3rd vs 2nd/6th vs 4th/5th (6 players only)') ],
            SCP_TEAM_COMPOSITION_14_23_56 => [ 'name' => totranslate('By lobby display order: 1st/4th vs 2nd/3rd vs 5th/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_14_25_36 => [ 'name' => totranslate('By lobby display order: 1st/4th vs 2nd/5th vs 3rd/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_14_26_35 => [ 'name' => totranslate('By lobby display order: 1st/4th vs 2nd/6th vs 3rd/5th (6 players only)') ],
            SCP_TEAM_COMPOSITION_15_23_46 => [ 'name' => totranslate('By lobby display order: 1st/5th vs 2nd/3rd vs 4th/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_15_24_36 => [ 'name' => totranslate('By lobby display order: 1st/5th vs 2nd/4th vs 3rd/6th (6 players only)') ],
            SCP_TEAM_COMPOSITION_15_26_34 => [ 'name' => totranslate('By lobby display order: 1st/5th vs 2nd/6th vs 3rd/4th (6 players only)') ],
        ],
        'default' => SCP_TEAM_COMPOSITION_RANDOM,
        'displaycondition' => [
            [
                'type' => 'otheroption',
                'id' => SCP_TEAM_PLAY,
                'value' => [SCP_TEAM_PLAY_YES],
            ],
        ],
        'notdisplayedmessage' => totranslate('Team composition is only available when playing in teams'),
        'startcondition' => [
            SCP_TEAM_COMPOSITION_1_2 => [
                [
                    'type' => 'minplayers', 'value' => 4, 'message' => 'The selected team setting is available for 4 players only.', 'gamestartonly' => true
                ],
                [
                    'type' => 'maxplayers', 'value' => 4, 'message' => 'The selected team setting is available for 4 players only.', 'gamestartonly' => true
                ],
            ],
            SCP_TEAM_COMPOSITION_1_3 => [
                [
                    'type' => 'minplayers', 'value' => 4, 'message' => 'The selected team setting is available for 4 players only.', 'gamestartonly' => true
                ],
                [
                    'type' => 'maxplayers', 'value' => 4, 'message' => 'The selected team setting is available for 4 players only.', 'gamestartonly' => true
                ],
            ],
            SCP_TEAM_COMPOSITION_1_4 => [
                [
                    'type' => 'minplayers', 'value' => 4, 'message' => 'The selected team setting is available for 4 players only.', 'gamestartonly' => true
                ],
                [
                    'type' => 'maxplayers', 'value' => 4, 'message' => 'The selected team setting is available for 4 players only.', 'gamestartonly' => true
                ],
            ],
            SCP_TEAM_COMPOSITION_12_34_56 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_12_35_46 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_12_36_45 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_13_24_56 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_13_25_46 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_13_26_45 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_14_23_56 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_14_25_36 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_14_26_35 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_15_23_46 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_15_24_36 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
            SCP_TEAM_COMPOSITION_15_26_34 => [ ['type' => 'minplayers', 'value' => 6, 'message' => 'The selected team setting is available for 6 players only.', 'gamestartonly' => true] ],
        ],
    ],
    SCP_WHO_CAPTURES_REMAINING => [
        'name' => totranslate('Which player should capture the remaining cards?'),
        'values' => [
            SCP_WHO_CAPTURES_REMAINING_CAPTURER => [
                'name' => totranslate('The last player to capture a card'),
            ],
            SCP_WHO_CAPTURES_REMAINING_DEALER => [
                'name' => totranslate('The dealer (who plays last)'),
            ],
        ],
        'default' => SCP_WHO_CAPTURES_REMAINING_CAPTURER,
    ],
    SCP_MULTIPLE_CAPTURES => [
        'name' => totranslate('If multiple combinations of cards can be captured:'),
        'values' => [
            SCP_MULTIPLE_CAPTURES_ALLOW_LOWEST => [
                'name' => totranslate('Allow only the lowest number of cards to be captured'),
            ],
            SCP_MULTIPLE_CAPTURES_ALLOW_ALL_EXCEPT_SINGLE => [
                'name' => totranslate('Allow any combination to be captured except if a single card matches'),
                'description' => totranslate('If a single card matches, capture it. Otherwise, any combination can be captured.'),
            ],
            SCP_MULTIPLE_CAPTURES_ALLOW_ALL => [
                'name' => totranslate('Allow any combination to be captured'),
            ],
        ],
        'displaycondition' => [
            [
                'type' => 'otheroptionisnot',
                'id' => SCP_VARIANT,
                'value' => [SCP_VARIANT_SCOPA_FRAC, SCP_VARIANT_CIRULLA],
            ],
        ],
        'notdisplayedmessage' => totranslate('Frac and Cirulla allow any combination to be captured'),
        'default' => SCP_MULTIPLE_CAPTURES_ALLOW_LOWEST,
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
            SCP_PREF_CARD_DECK_NAPOLITAN => [ 'name' => totranslate('Naepolitan') ],
            SCP_PREF_CARD_DECK_STANDARD => [ 'name' => totranslate('Standard') ],
            SCP_PREF_CARD_DECK_BERGAMASCHE => [ 'name' => totranslate('Bergamasche') ],
            SCP_PREF_CARD_DECK_BRESCIANE => [ 'name' => totranslate('Bresciane') ],
            SCP_PREF_CARD_DECK_PIACENTINE => [ 'name' => totranslate('Piacentine') ],
            SCP_PREF_CARD_DECK_SPANISH => [ 'name' => totranslate('Spanish') ],
        ],
        'default' => SCP_PREF_CARD_DECK_NAPOLITAN
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
    SCP_PREF_DISPLAY_PLAYERS => [
        'name' => totranslate('Display other players?'),
        'needReload' => false, // Handled through JS directly
        'values' => [
            SCP_PREF_DISPLAY_PLAYERS_YES => [ 'name' => totranslate('Yes') ],
            SCP_PREF_DISPLAY_PLAYERS_NO => [ 'name' => totranslate('No') ]
        ],
        'default' => SCP_PREF_DISPLAY_PLAYERS_NO
    ],
    SCP_PREF_DISPLAY_NOTICE => [
        'name' => totranslate('Display information banner at the top?'),
        'needReload' => false, // Handled through JS directly
        'values' => [
            SCP_PREF_DISPLAY_NOTICE_YES => [ 'name' => totranslate('Yes') ],
            SCP_PREF_DISPLAY_NOTICE_NO => [ 'name' => totranslate('No') ]
        ],
        'default' => SCP_PREF_DISPLAY_NOTICE_YES
    ],
    SCP_PREF_ANIMATION_SPEED => [
        'name' => totranslate('Animation speed'),
        'needReload' => false, // Handled through JS directly
        'values' => [
            SCP_PREF_ANIMATION_SPEED_1 => [ 'name' => totranslate('Slow') ],
            SCP_PREF_ANIMATION_SPEED_2 => [ 'name' => totranslate('Fast') ],
            SCP_PREF_ANIMATION_SPEED_25 => [ 'name' => totranslate('Lightning fast') ]
        ],
        'default' => SCP_PREF_ANIMATION_SPEED_1
    ],
    SCP_PREF_DISPLAY_ORDER => [
        'name' => totranslate('Order of cards'),
        'needReload' => false, // Handled through JS directly
        'values' => [
            SCP_PREF_DISPLAY_ORDER_BY_SUIT => [ 'name' => totranslate('By suit') ],
            SCP_PREF_DISPLAY_ORDER_BY_NUMBER => [ 'name' => totranslate('By number') ],
        ],
        'default' => SCP_PREF_DISPLAY_ORDER_BY_SUIT
    ],
];
