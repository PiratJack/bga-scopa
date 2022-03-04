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
$gameinfos = [
    // Name of the game in English (will serve as the basis for translation)
    'game_name' => 'Scopa',

    // Unknown creators
    'designer' => '',
    'artist' => '',

    // Year of FIRST publication of this game. Can be negative.
    'year' => 1600,

    // Game in public domain
    'publisher' => '',
    'publisher_website' => '',
    'publisher_bgg_id' => 171,

    'bgg_id' => 15889,

    // Can be played for 2, 3, 4 or 6 players, no preference
    'players' => [2, 3, 4, 6],
    'suggest_player_number' => null,
    'not_recommend_player_number' => null,

    // Estimated game duration, in minutes
    'estimated_duration' => 30,

    // Time in second add to a player when "giveExtraTime" is called
    'fast_additional_time' => 30,
    'medium_additional_time' => 40,
    'slow_additional_time' => 50,

    'tie_breaker_description' => '',

    'losers_not_ranked' => true,

    'solo_mode_ranked' => false,

    'is_beta' => 1,
    'is_coop' => 0,

    // Language is irrelevant for the game
    'language_dependency' => false,

    'complexity' => 2,
    'luck' => 3,
    'strategy' => 4,
    'diplomacy' => 1,

    // Colors attributed to players
    'player_colors' => ['ff0000', '008000', '0000ff', 'ffa500', '773300'],
    'favorite_colors_support' => true,

    // Change player order for rematch
    'disable_player_order_swap_on_rematch' => false,

    // Game interface width range (pixels)
    'game_interface_width' => [
        'min' => 320,
        'max' => null,
    ],

    // Game presentation
    'presentation' => [
        totranslate('Scopa is a traditional Italian game of cards.'),
        totranslate('Its exact origin is lost to History, but the game goes on, with several variants.'),
        totranslate('Taking turns, each player tries to capture cards. Extra points are awarded for the Sette bello (7 of coins) & other coin cards. Performing a Scopa (sweeping all cards) during the game is awarded by an extra point.'),
    ],

    // Games categories
    'tags' => [2, 23, 30, 200],

    //////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)

    // simple : A plays, B plays, C plays, A plays, B plays, ...
    // circuit : A plays and choose the next player C, C plays and choose the next player D, ...
    // complex : A+B+C plays and says that the next player is A+B
    'is_sandbox' => false,
    'turnControl' => 'simple',

    ////////
];
