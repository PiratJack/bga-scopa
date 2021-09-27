<?php

/**
 * ------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * scopa implementation : © Jacques de Metz <demetz.jacques@gmail.com>.
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */
require_once APP_GAMEMODULE_PATH.'module/table/table.game.php';

class scopa extends Table
{
    public function __construct()
    {
        parent::__construct();

        self::initGameStateLabels(
            [
                'target_score' => 100,
                'max_capture_cards' => 101, ]
        );

        $this->target_score_mapping = [
            1 => 11,
            2 => 16,
            3 => 21,
            4 => 31,
        ];

        $this->cards = self::getNew('module.common.deck');
        $this->cards->init('card');
    }

    // Compute and return the current game progression.
    public function getGameProgression()
    {
        $max_score = self::getUniqueValueFromDb('SELECT MAX( player_score ) FROM player');
        $cards_captured = self::getUniqueValueFromDb('SELECT count(card_id) FROM card WHERE card_location = "capture"');
        $target_score = $this->target_score_mapping[$this->getGameStateValue('target_score')];

        return min(100, ($max_score + 4 * $cards_captured / 40) / $target_score * 100);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    // Determine which cards can capture or be played
    // Output is an array with:
    // player's card_id => [dummy index => [cards => list of cards, total => total points, size => number of cards]]
    // If no capture is possible, $output[$card_id] doesn't exist
    public function getCardCaptures($active_player_id)
    {
        // Cards in player hand
        $hand = $this->cards->getCardsInLocation('hand', $active_player_id);

        // Cards played on the table
        $table = $this->cards->getCardsInLocation('table');

        // Find all combinations of the table's cards
        $combinations = [];
        $this->combineCards($combinations, $table);

        // Depending on option, reduce combinations to 2 cards max
        if ('1' == $this->getGameStateValue('max_capture_cards')) {
            $combinations = array_filter(
                $combinations,
                function ($value) {
                    return $value['size'] <= 2;
                }
            );
        }

        // Remove combinations > 10 points (can't be captured)
        $combinations = array_filter(
            $combinations,
            function ($value) {
                return $value['total'] <= 10;
            }
        );

        // Keep only combinations possibles for the player
        $possible_actions = [];

        foreach ($hand as $card_id => $card) {
            $possible_actions[$card_id] = array_filter(
                $combinations,
                function ($value) use ($card) {
                    return $value['total'] == $card['type_arg'];
                }
            );
        }

        // Keep only the combination that has the smallest number of cards
        foreach ($possible_actions as $card_id => $combinations) {
            // Remove cards that have no combination possible
            if (0 == count($combinations)) {
                unset($possible_actions[$card_id]);

                continue;
            }
            $min_cards = min(
                array_map(
                    function ($val) {
                        return $val['size'];
                    },
                    $combinations
                )
            );
            // array_values forces a re-indexing, which means Javascript will see it as an array and not an object
            $possible_actions[$card_id] = array_values(
                array_filter(
                    $combinations,
                    function ($value) use ($min_cards) {
                        return $value['size'] == $min_cards;
                    }
                )
            );
        }

        return $possible_actions;
    }

    // Determines all possible combinations of cards
    public function combineCards(&$combinations, $cards_left, $cards_included = [])
    {
        // cards_left's length will change over time; so we need to store it
        $nb_cards = count($cards_left);
        foreach ($cards_left as $card_id => $card_taken) {
            unset($cards_left[$card_id]);

            $combination = [];
            $combination['cards'] = array_merge($cards_included, [$card_id => $card_taken]);
            $combination['total'] = array_reduce(
                $combination['cards'],
                function ($total, $card) {
                    return $total + $card['type_arg'];
                }
            );
            $combination['size'] = count($combination['cards']);
            $combinations[] = $combination;

            if (count($cards_left) > 0) {
                $this->combineCards($combinations, $cards_left, $combination['cards']);
            }
        }

        return $combinations;
    }

    // Sends counts of data in player's hands and deck
    public function notif_cardsCount()
    {
        // Cards in each player's hand
        $sql = 'SELECT IF(card_location="deck","deck",card_location_arg) location, count(card_id)
                FROM card where card_location in ("hand", "deck")
                GROUP BY location ';
        $data = self::getCollectionFromDb($sql, true);

        $sql = 'SELECT player_id id FROM player ';
        $players = self::getCollectionFromDb($sql, true);
        foreach ($players as $player_id => $temp) {
            if (!array_key_exists($player_id, $data)) {
                $data[$player_id] = 0;
            }
        }

        if (!array_key_exists('deck', $data)) {
            $data['deck'] = 0;
        }

        self::notifyAllPlayers(
            'cardsCount',
            '',
            [
                'count' => $data,
            ]
        );
    }

    // Sends the cards that are in a player's hand
    public function notif_cardsInHand($player_id = 0)
    {
        if (0 == $player_id) {
            $player_id = self::getCurrentPlayerId();
        }

        // Cards in player hand
        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        self::notifyPlayer($player_id, 'cardsInHand', '', ['cards' => $cards]);
    }

    // Sends the cards that are on the table
    public function notif_cardsOnTable()
    {
        self::notifyAllPlayers('cardsOnTable', '', ['cards' => $this->cards->getCardsInLocation('table')]);
    }

    // Updates player's scores
    public function notif_playerScores()
    {
        $sql = 'SELECT player_id, player_score FROM player';
        $data = self::getCollectionFromDb($sql, true);
        self::notifyAllPlayers('playerScores', '', ['score' => $data]);
    }

    // A player wins: update score, statistics & send notification
    public function playerWin($player_id, $win_type, &$scoring_table = null)
    {
        $this->incStat(1, $win_type, $player_id);
        $this->DbQuery('UPDATE player SET player_score=player_score+1 WHERE player_id="'.$player_id.'"');

        if ('scopa_number' == $win_type) {
            $this->DbQuery('UPDATE player SET scopa_in_round=scopa_in_round+1 WHERE player_id="'.$player_id.'"');
        }

        $win_types = [
            'scopa_number' => clienttranslate('${player_name} captures all cards and wins a point. Scopa!'),
            'sette_bello' => clienttranslate('${player_name} captured the 7 of coins and wins a point.'),
            'cards_captured' => clienttranslate('${player_name} captured the most cards and wins a point.'),
            'coins_captured' => clienttranslate('${player_name} captured the most coin cards and wins a point.'),
            'prime_score' => clienttranslate('${player_name} scores the highest prime and wins a point.'),
        ];
        self::notifyAllPlayers(
            'message',
            $win_types[$win_type],
            [
                'player_name' => self::getPlayerNameById($player_id),
            ]
        );

        if (!is_null($scoring_table)) {
            ++$scoring_table['added_points'][$player_id];
            ++$scoring_table['final_score'][$player_id];
        }

        // Update scores
        $this->notif_playerScores();
    }

    // There is a tie => notify everyone
    public function playerTie($tie_type)
    {
        // Ties are not possible for sette bello or scopa
        $tie_types = [
            'cards_captured' => clienttranslate('Multiple players captured the most cards. No point won!'),
            'coins_captured' => clienttranslate('Multiple players captured the most coins. No point won!'),
            'prime_score' => clienttranslate('Multiple players have the highest prime score. No point won!'),
        ];
        self::notifyAllPlayers('message', $tie_types[$tie_type], []);
    }

    // Gets and returns a random element in the list (used for Zombie)
    public function getRandomElement($array)
    {
        $index_list = array_keys($array);
        $rand_number = rand(0, count($index_list) - 1);

        return $array[$index_list[$rand_number]];
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    ////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in scopa.action.php)
    */

    public function playCard($card_id_ajax, $cards_captured_ajax)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('playCard');

        $player_id = self::getActivePlayerId();

        $cards = [];
        $cards['hand'] = $this->cards->getCardsInLocation('hand', $player_id);
        $cards['table'] = $this->cards->getCardsInLocation('table');

        // Check if received card exists and is in player's hand
        if ($card_id_ajax < 0 || !array_key_exists($card_id_ajax, $cards['hand'])) {
            throw new BgaUserException(self::_('This card is not in your hand'));
        }

        $card_played = $this->cards->getCard($card_id_ajax);
        if (!isset($card_played)) {
            throw new BgaVisibleSystemException(self::_('This card is nowhere to be found'));
        }

        // Check if received cards captured makes sense (= no negative value, ...)
        foreach ($cards_captured_ajax as $card) {
            if ($card < 0 || !array_key_exists($card, $cards['table'])) {
                throw new BgaUserException(self::_('Those cards are not on the table'));
            }
        }

        // Determine whether play or capture
        $possible_captures = $this->getCardCaptures($player_id);

        $capture = [];
        if (!array_key_exists($card_played['id'], $possible_captures)) {
            $this->cards->moveCard($card_played['id'], 'table');

            self::notifyAllPlayers(
                'cardPlayedToTable',
                clienttranslate('${player_name} plays ${value_label} of ${color_label} to the table'),
                [
                    'i18n' => ['value_label', 'color_label'],
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card_played['id'],
                    'value' => $card_played['type_arg'],
                    'value_label' => $this->values_label[$card_played['type_arg']],
                    'color' => $card_played['type'],
                    'color_label' => $this->colors[$card_played['type']]['name'],
                ]
            );
        } else {
            // Single capture possible
            if (1 == count($possible_captures[$card_played['id']])) {
                $capture = $possible_captures[$card_played['id']][0]['cards'];
            }

            // Multiple combinations possible ==> get the right one
            else {
                sort($cards_captured_ajax);
                foreach ($possible_captures[$card_played['id']] as $possible_capture) {
                    $possible_capture_cards_ids = array_map(
                        function ($v) {
                            return $v['id'];
                        },
                        $possible_capture['cards']
                    );
                    sort($possible_capture_cards_ids);
                    if ($possible_capture_cards_ids == $cards_captured_ajax) {
                        $capture = $possible_capture['cards'];

                        break;
                    }
                }
            }

            // Move cards to "capture" pile for that player
            $this->cards->moveCard($card_played['id'], 'capture', $player_id);
            foreach ($capture as $card_captured) {
                $this->cards->moveCard($card_captured['id'], 'capture', $player_id);
            }

            // Store who captured last
            $sql = 'UPDATE player SET has_last_captured = IF(player_id = '.$player_id.', TRUE, FALSE)';
            self::DbQuery($sql);

            // Notify about capture
            self::notifyAllPlayers(
                'cardPlayedAndCapture',
                clienttranslate('${player_name} plays ${value_label} of ${color_label} and captures ${nb_capture} card(s)'),
                [
                    'i18n' => ['value_label', 'color_label'],
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'card_id' => $card_played['id'],
                    'capture' => $capture,
                    'nb_capture' => count($capture),
                    'value' => $card_played['type_arg'],
                    'value_label' => $this->values_label[$card_played['type_arg']],
                    'color' => $card_played['type'],
                    'color_label' => $this->colors[$card_played['type']]['name'],
                ]
            );

            // Check if Scopa happened
            if (0 == $this->cards->countCardInLocation('table')) {
                // If the player is last and it's the last round, no scopa is possible
                if (0 != $this->cards->countCardInLocation('deck') || 0 != $this->cards->countCardInLocation('hand')) {
                    $this->playerWin($player_id, 'scopa_number');
                }
            }
        }

        //Update card counts
        $this->notif_cardsCount();

        $this->gamestate->nextState('playCard');
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    // Determine which cards can capture
    public function argPlayerTurn()
    {
        return [
            'cardCaptures' => self::getCardCaptures(self::getActivePlayerId()),
        ];
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    // Deal new cards to the table from the deck
    public function stDealStart()
    {
        // Move all cards to deck
        $this->cards->moveAllCardsInLocation(null, 'deck');
        $this->cards->shuffle('deck');

        // Deal 4 cards to the table (without re-shuffling)
        $cards = $this->cards->pickCardsForLocation(4, 'deck', 'table', 0, true);

        // If there are 3 or 4 kings, then reshuffle as needed
        $kings = array_filter(
            $cards,
            function ($card) {
                return 10 == $card['type_arg'];
            }
        );
        while (count($kings) >= 3) {
            $this->cards->moveAllCardsInLocation(null, 'deck');
            $cards = $this->cards->pickCardsForLocation(4, 'deck', 'table', 0, true);
            $kings = array_filter(
                $cards,
                function ($card) {
                    return 10 == $card['type_arg'];
                }
            );
        }

        // Reset the counter of Scopa of the round
        $sql = 'UPDATE player SET scopa_in_round = 0';
        self::DbQuery($sql);

        // Notify all players about the card's table
        self::notifyAllPlayers(
            'tableCards',
            '',
            [
                'cards' => $cards,
            ]
        );

        // Next, deal cards to each player
        $this->gamestate->nextState();
    }

    // Deal new cards to each player from the deck
    public function stHandStart()
    {
        // Deal 3 cards to each player (without re-shuffling)
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $cards = $this->cards->pickCardsForLocation(3, 'deck', 'hand', $player_id, true);
            $this->notif_cardsInHand($player_id);
        }
        $this->notif_cardsCount();

        // Next, first player plays
        $this->gamestate->nextState();
    }

    // Determines whether to distribute new cards or not
    public function stNextPlayer()
    {
        if (0 == $this->cards->countCardInLocation('hand')) {
            $this->gamestate->nextState('handEnd');
        } else {
            $player_id = self::activeNextPlayer();
            self::giveExtraTime($player_id);
            $this->gamestate->nextState('playerTurn');
        }
    }

    // Deals new cards if possible, otherwise go to endDeck
    public function stHandEnd()
    {
        // If cards are left in the deck, distribute them
        if (0 != $this->cards->countCardInLocation('deck')) {
            $this->activeNextPlayer();
            $this->gamestate->nextState('handStart');
        } else {
            $this->gamestate->nextState('deckEnd');
        }
    }

    // Calculates each player's score based on captured cards
    public function stDeckEnd()
    {
        // Sends a pause to process any pending event / animation
        self::notifyAllPlayers('pause', '', []);

        // Give the remaining cards to the player who captured last
        $sql = 'SELECT player_id FROM player WHERE has_last_captured = TRUE';
        $player_last_capture = self::getCollectionFromDB($sql);
        if (1 == count($player_last_capture)) {
            $player_last_capture = array_pop($player_last_capture)['player_id'];
            $this->cards->moveAllCardsInLocation('table', 'capture', null, $player_last_capture);

            self::notifyAllPlayers(
                'playerCapturesTable',
                clienttranslate('${player_name} captures all remaining cards'),
                [
                    'player_id' => $player_last_capture,
                    'player_name' => self::getPlayerNameById($player_last_capture),
                ]
            );
        }

        // Scoring table initialization
        $score_table = [];
        $scoring_rows = [
            'previous_score' => clienttranslate('Previous score'),
            'scopa_number' => clienttranslate('Scopa'),
            'sette_bello' => clienttranslate('7 of coins (sette bello)'),
            'cards_captured' => clienttranslate('Cards captured'),
            'coins_captured' => clienttranslate('Coins captured'),
            'prime_score' => clienttranslate('Prime (primiera)'),
            'added_points' => clienttranslate('Points won this round'),
            'final_score' => clienttranslate('Final score'),
        ];
        $players = self::loadPlayersBasicInfos();
        foreach ($scoring_rows as $code => $label) {
            $score_table[$code] = array_fill_keys(array_keys($players), 0);
        }

        // Scoring for Scopa
        // Scopa score is from DB directly
        $sql = 'SELECT player_id, player_score, scopa_in_round FROM player';
        $values = self::getCollectionFromDb($sql);

        // Scopa points have already been added to the score, so remove them
        $score_table['previous_score'] = array_map(
            function ($v) {
                return $v['player_score'] - $v['scopa_in_round'];
            },
            $values
        );
        // Add scopa points to the score
        $score_table['scopa_number'] = array_map(
            function ($v) {
                return $v['scopa_in_round'];
            },
            $values
        );
        $score_table['added_points'] = $score_table['scopa_number'];

        // Scoring for Sette Bello
        // Who has the 7 of coins (sette bello)?
        $card = $this->cards->getCardsOfType(1, 7);
        $sette_bello = array_pop($card);
        if ('capture' != $sette_bello['location']) {
            throw new BgaVisibleSystemException(self::_('Invalid state: 7 of coins in unexpected place'));
        }
        $this->playerWin($sette_bello['location_arg'], 'sette_bello', $score_table);
        $score_table['sette_bello'][$sette_bello['location_arg']] = 1;

        // Scoring for # cards, # coins and Prime
        // Who captured the most cards?
        $sql = 'SELECT IF(card_location="deck", "deck", card_location_arg) player, count(card_id) nb_cards
                            FROM card
                            WHERE card_location = "capture"
                            GROUP BY card_location, player
                            ORDER BY nb_cards DESC';
        $score_table['cards_captured'] = self::getCollectionFromDb($sql, true);

        // Who has the most coin cards?
        $sql = 'SELECT IF(card_location="deck", "deck", card_location_arg) player, count(card_id) nb_cards
                            FROM card
                            WHERE card_location = "capture" AND card_type = 1
                            GROUP BY card_location, player
                            ORDER BY nb_cards DESC';
        $score_table['coins_captured'] = self::getCollectionFromDb($sql, true);

        // Calculate Prime score
        $players_prime = array_fill_keys(array_keys($this->loadPlayersBasicInfos()), ['1' => '', '2' => '', '3' => '', '4' => '']);
        $point_per_card = $this->prime_points;
        foreach ($players_prime as $player_id => $colors) {
            $cards = $this->cards->getCardsInLocation('capture', $player_id);
            foreach ($colors as $color_id => $temp) {
                $cards_of_color = array_filter(
                    $cards,
                    function ($card) use ($color_id) {
                        return $card['type'] == $color_id;
                    }
                );

                $prime_points = array_map(
                    function ($card) use ($point_per_card) {
                            return $point_per_card[$card['type_arg']];
                        },
                    $cards_of_color
                );
                if (!empty($prime_points)) {
                    $players_prime[$player_id][$color_id] = max($prime_points);
                } else {
                    $players_prime[$player_id][$color_id] = 0;
                }
            }
            $players_prime[$player_id] = array_sum($players_prime[$player_id]);
        }
        $score_table['prime_score'] = $players_prime;

        // Get the winners in each category (except scopa & sette bello, already counted)
        $categories = ['cards_captured', 'coins_captured', 'prime_score'];
        foreach ($categories as $category) {
            $high_score = max($score_table[$category]);
            $winners = array_filter(
                $score_table[$category],
                function ($val) use ($high_score) {
                    return $val == $high_score;
                }
            );
            if (1 == count($winners)) {
                // We have a unique winner!
                $this->playerWin(array_keys($winners)[0], $category, $score_table);
            } else {
                $this->playerTie($category);
            }
        }

        // Calculate final score
        foreach ($score_table['final_score'] as $player => $score) {
            $score_table['final_score'][$player] = $score_table['previous_score'][$player] + $score_table['added_points'][$player];
        }

        // Format scoring table for display
        // Format the scoring table
        $score_table_display = [];
        $players = self::loadPlayersBasicInfos();
        ksort($players);
        $nb_players = count($players);
        // First line: player names
        $header = [''];
        foreach ($players as $player_id => $player) {
            $header[] = [
                'str' => '${player_name}',
                'args' => ['player_name' => $player['player_name']],
                'type' => 'header',
            ];
        }
        $score_table_display[] = $header;
        // This is used to default all player's scores to 0 for each row (otherwise they're not displayed)
        $player_to_zero = array_fill_keys(array_keys($players), 0);
        foreach ($scoring_rows as $code => $label) {
            $score = $score_table[$code];
            ksort($score);
            $score_table_display[] = [0 => $label] + $score + $player_to_zero;
        }
        $this->notifyAllPlayers(
            'tableWindow',
            '',
            [
                'id' => 'finalScoring',
                'title' => clienttranslate('Score'),
                'table' => $score_table_display,
                'closing' => clienttranslate('Close'),
            ]
        );

        self::notifyAllPlayers('pause', '', []);

        // Should we go to next round or not?
        // Is the game over or not?
        $target_score = $this->target_score_mapping[$this->getGameStateValue('target_score')];
        $sql = 'SELECT player_id, player_score
                            FROM player
                            WHERE player_score >= '.$target_score;
        $player_score = self::getCollectionFromDb($sql, true);
        if (0 == count($player_score)) {
            // No player has enough, start new round
            $this->gamestate->nextState('dealStart');
            self::notifyAllPlayers('message', clienttranslate('No player has enough points. The game continues!'), []);

            return;
        }
        // Otherwise, find which player has most points
        $highest_score = max($player_score);
        $winners = array_filter(
            $player_score,
            function ($val) use ($highest_score) {
                return $val == $highest_score;
            }
        );
        if (1 != count($winners)) {
            // At least 2 players with the same score, start a new round
            self::notifyAllPlayers('message', clienttranslate('The 2 top players have the same score. The game continues!'), []);
            $this->gamestate->nextState('dealStart');
        } else {
            // We have a winner!
            $this->gamestate->nextState('gameEnd');
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    // Zombie turn: Plays a card at random
    public function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ('activeplayer' === $state['type']) {
            // Choose which card to play
            $cards = $this->cards->getCardsInLocation('hand', $active_player);
            $card = $this->getRandomElement($cards);

            $captures = $this->getCardCaptures($active_player);
            // Can't capture, just play
            if (!array_key_exists($card['id'], $captures)) {
                $capture = [];
            } else {
                // Single capture possible: just do it!
                if (1 == count($captures[$card['id']])) {
                    $capture_cards = reset($captures[$card['id']])['cards'];
                }
                // Multiple captures possible: Choose one
                else {
                    $capture_cards = $this->getRandomElement($captures[$card['id']])['cards'];
                }
                $capture = array_map(
                    function ($v) {
                        return $v['id'];
                    },
                    $capture_cards
                );
            }

            $this->playCard($card['id'], $capture);

            return;
        }

        throw new feException('Zombie mode not supported at this game state: '.$statename);
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    public function upgradeTableDb($from_version)
    {
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return 'scopa';
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        $sql = 'DELETE FROM player WHERE 1 ';
        self::DbQuery($sql);

        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on 'player' table in the database (dbmodel.sql), you can initialize it there.
        $sql = 'INSERT INTO player (player_id, player_score, player_color, player_canal, player_name, player_avatar) VALUES ';
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = '("'.$player_id.'",0,"'.$color.'","'.$player['player_canal'].'","'.addslashes($player['player_name']).'","'.addslashes($player['player_avatar']).'")';
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        // Init global values with their initial values

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat('player', 'scopa_number', 0);
        self::initStat('player', 'sette_bello', 0);
        self::initStat('player', 'cards_captured', 0);
        self::initStat('player', 'coins_captured', 0);
        self::initStat('player', 'prime_score', 0);

        // Prepare card deck
        $cards = [];
        foreach ($this->colors as $color_id => $color) { // coin, cup, sword, club
            foreach ($this->values_label as $value_id => $value) { //  1, 2, 3, 4, ... K
                $cards[] = ['type' => $color_id, 'type_arg' => $value_id, 'nbr' => 1];
            }
        }

        $this->cards->createCards($cards, 'deck');

        // Activate first player
        $this->activeNextPlayer();
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = [];

        $current_player_id = self::getCurrentPlayerId();

        // Get information about players
        $sql = 'SELECT player_id id, player_score score FROM player ';
        $result['players'] = self::getCollectionFromDb($sql);

        // Cards in player hand
        $result['hand'] = $this->cards->getCardsInLocation('hand', $current_player_id);

        // Cards played on the table
        $result['table'] = $this->cards->getCardsInLocation('table');

        // Cards in each player's hand
        $this->notif_cardsCount();

        // If the player has no card, then it won't exist in the result ==> add 0
        $result['players_hand'] = [];
        $sql = 'SELECT IF(card_location="deck","deck",card_location_arg) location, count(card_id)
                FROM card where card_location in ("hand", "deck")
                GROUP BY location ';
        $result['players_hand'] = self::getCollectionFromDb($sql, true);
        foreach ($result['players'] as $player_id => $player) {
            if (!array_key_exists($player_id, $result['players_hand'])) {
                $result['players_hand'][$player_id] = 0;
            }
        }

        // Same for the deck
        if (!array_key_exists('deck', $result['players_hand'])) {
            $result['players_hand']['deck'] = 0;
        }

        return $result;
    }
}
