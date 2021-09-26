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
$this->colors = [
    1 => ['name' => clienttranslate('coin'),
        'nametr' => self::_('coin'), ],
    2 => ['name' => clienttranslate('cup'),
        'nametr' => self::_('cup'), ],
    3 => ['name' => clienttranslate('sword'),
        'nametr' => self::_('sword'), ],
    4 => ['name' => clienttranslate('club'),
        'nametr' => self::_('club'), ],
];

$this->values_label = [
    1 => clienttranslate('A'),
    2 => '2',
    3 => '3',
    4 => '4',
    5 => '5',
    6 => '6',
    7 => '7',
    8 => clienttranslate('J'),
    9 => clienttranslate('Knight'),
    10 => clienttranslate('K'),
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
