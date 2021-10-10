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
if (!defined('STATE_DEAL_START')) {
    define('STATE_DEAL_START', 20);
    define('STATE_HAND_START', 30);
    define('STATE_PLAYER_TURN', 40);
    define('STATE_NEXT_PLAYER', 50);
    define('STATE_AUTO_PLAYER', 55);
    define('STATE_HAND_END', 60);
    define('STATE_DECK_END', 70);
}

$machinestates = [
    // The initial state. Please do not modify.
    1 => [
        'name' => 'gameSetup',
        'description' => '',
        'type' => 'manager',
        'action' => 'stGameSetup',
        'transitions' => ['' => STATE_DEAL_START],
    ],

    /// New deal
    STATE_DEAL_START => [
        'name' => 'dealStart',
        'description' => '',
        'type' => 'game',
        'action' => 'stDealStart',
        'updateGameProgression' => true,
        'transitions' => ['' => STATE_HAND_START],
    ],

    // No card in hand ==> new hand
    STATE_HAND_START => [
        'name' => 'handStart',
        'description' => '',
        'type' => 'game',
        'action' => 'stHandStart',
        'updateGameProgression' => true,
        'transitions' => ['' => STATE_PLAYER_TURN],
    ],

    // Player turn
    STATE_PLAYER_TURN => [
        'name' => 'playerTurn',
        'description' => clienttranslate('${actplayer} must play a card'),
        'descriptionmyturn' => clienttranslate('${you} must play a card'),
        'type' => 'activeplayer',
        'args' => 'argPlayerTurn',
        'possibleactions' => ['playCard'],
        'transitions' => ['playCard' => STATE_NEXT_PLAYER],
    ],

    // Switch to next player
    STATE_NEXT_PLAYER => [
        'name' => 'nextPlayer',
        'description' => '',
        'type' => 'game',
        'action' => 'stNextPlayer',
        'updateGameProgression' => true,
        'possibleactions' => ['playerTurn', 'autoPlayerTurn', 'handEnd'],
        'transitions' => ['playerTurn' => STATE_PLAYER_TURN, 'autoPlayerTurn' => STATE_AUTO_PLAYER, 'handEnd' => STATE_HAND_END],
    ],

    // Switch to next player
    STATE_AUTO_PLAYER => [
        'name' => 'autoPlayer',
        'description' => '',
        'type' => 'game',
        'action' => 'stAutoPlayer',
        'updateGameProgression' => true,
        'possibleactions' => ['playerTurn', 'playCard'],
        'transitions' => ['playerTurn' => STATE_PLAYER_TURN, 'playCard' => STATE_NEXT_PLAYER],
    ],

    // No card left in hand: deal new cards if there are cards in the deck
    STATE_HAND_END => [
        'name' => 'handEnd',
        'description' => '',
        'type' => 'game',
        'action' => 'stHandEnd',
        'updateGameProgression' => true,
        'possibleactions' => ['handStart', 'deckEnd'],
        'transitions' => ['handStart' => STATE_HAND_START, 'deckEnd' => STATE_DECK_END],
    ],

    // No card left to deal: count points & go to next round if needed
    STATE_DECK_END => [
        'name' => 'deckEnd',
        'description' => '',
        'type' => 'game',
        'action' => 'stDeckEnd',
        'transitions' => ['dealStart' => STATE_DEAL_START, 'gameEnd' => 99],
    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => [
        'name' => 'gameEnd',
        'description' => clienttranslate('End of game'),
        'type' => 'manager',
        'action' => 'stGameEnd',
        'args' => 'argGameEnd',
    ],
];
