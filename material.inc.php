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

$this->colors = [
    1 => ['name' => clienttranslate('coin')],
    2 => ['name' => clienttranslate('cup')],
    3 => ['name' => clienttranslate('sword')],
    4 => ['name' => clienttranslate('club')],
];

$this->values_label = [
    1 => clienttranslate('Ace (1)'),
    2 => '2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => clienttranslate('Jack (8)'),
    9 => clienttranslate('Knight (9)'),
    10 => clienttranslate('King (10)'),
];

// How many "prime" point each card represents
$this->prime_points = [
    1 => 16,
    2 => 12,
    3 => 13,
    4 => 14,
    5 => 15,
    6 => 18,
    7 => 21,
    8 => 10,
    9 => 10,
    10 => 10,
];

// Defines where players are seated
// Structure: count_total_players => [ player_order => position]
// player_order being 0 for active player, 1 for next player, ...
// Reminder: Scopa is played counter-clockwise
$this->seat_positions = [
    6 => [
        1 => 'bottom_right',
        2 => 'right',
        3 => 'top_right',
        4 => 'top_left',
        5 => 'left',
    ],
    4 => [
        1 => 'bottom_right',
        2 => 'top_right',
        3 => 'top_left',
    ],
    3 => [
        1 => 'top_right',
        2 => 'top_left',
    ],
    2 => [
        1 => 'top_left',
    ],
];
