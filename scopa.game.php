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
                'target_score' => SCP_OPTION_POINTS_TO_WIN,
                'max_capture_cards' => SCP_OPTION_MAX_CAPTURE,
                'game_variant' => SCP_VARIANT,
                'team_play' => SCP_TEAM_PLAY,
            ]
        );

        $this->target_score_mapping = [
            SCP_OPTION_POINTS_TO_WIN_11 => 11,
            SCP_OPTION_POINTS_TO_WIN_16 => 16,
            SCP_OPTION_POINTS_TO_WIN_21 => 21,
            SCP_OPTION_POINTS_TO_WIN_31 => 31,
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
        if (SCP_OPTION_MAX_CAPTURE_2 == $this->getGameStateValue('max_capture_cards')) {
            $combinations = array_filter(
                $combinations,
                function ($value) {
                    return $value['size'] <= 2;
                }
            );
        }


        // Keep only combinations possibles for the player
        $possible_actions = [];

        // SCP_VARIANT_ASSO_PIGLIA_TUTTO: Remove this condition (Ace captures all)
        // In Scopa di Quindici, capture is possible if the sum of cards is 15
        if ($this->getGameStateValue('game_variant') == SCP_VARIANT_SCOPA_DI_QUINDICI) {
            foreach ($hand as $card_id => $card) {
                $possible_actions[$card_id] = array_filter(
                    $combinations,
                    function ($value) use ($card) {
                        return $value['total'] + $card['type_arg'] == 15;
                    }
                );
            }
        } else {
            // Remove combinations > 10 points (can't be captured)
            $combinations = array_filter(
                $combinations,
                function ($value) {
                    return $value['total'] <= 10;
                }
            );


            foreach ($hand as $card_id => $card) {
                $possible_actions[$card_id] = array_filter(
                    $combinations,
                    function ($value) use ($card) {
                        // SCP_VARIANT_ASSO_PIGLIA_TUTTO: Adapt this condition (Ace captures all)
                        return $value['total'] == $card['type_arg'];
                    }
                );
            }
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

    // Formats cards for display purpose
    public function cardsToDisplay($cards)
    {
        $cards_by_type = [];
        foreach ($cards as $card) {
            if (!array_key_exists($card['type'], $cards_by_type)) {
                $cards_by_type[$card['type']] = [];
            }
            $cards_by_type[$card['type']][] = $card['type_arg'];
        }
        ksort($cards_by_type);

        $cards_display = [];
        foreach ($cards_by_type as $type => $cards_of_type) {
            $cards_display[$type] = $this->colors[$type]['name'].' :';

            foreach ($cards_of_type as $card_value) {
                $cards_display[$type] .= '&nbsp;' . $this->values_label[$card_value];
            }
        }

        return implode('<br />', $cards_display);
    }

    // Gets and returns a random element in the list (used for Zombie)
    public function getRandomElement($array)
    {
        $index_list = array_keys($array);
        $rand_number = rand(0, count($index_list) - 1);

        return $array[$index_list[$rand_number]];
    }

    // Returns whether the active player has auto-play on
    public function getPlayerAutoPlay($player_id)
    {
        return $this->getUserPreference($player_id, SCP_PREF_AUTO_PLAY);
    }

    // Returns whether the active player has auto-play on
    public function getUserPreference($player_id, $pref_id)
    {
        if (!isset($this->user_preferences)) {
            $sql = 'SELECT player_id, pref_id, pref_value FROM user_preferences';

            $this->user_preferences = self::getDoubleKeyCollectionFromDB($sql, true);
        }

        $player_id = $this->getActivePlayerId();
        if (array_key_exists($player_id, $this->user_preferences)) {
            if (array_key_exists(SCP_PREF_AUTO_PLAY, $this->user_preferences[$player_id])) {
                return $this->user_preferences[$player_id][SCP_PREF_AUTO_PLAY];
            }
        }

        $game_preferences = $this->getTablePreferences();
        return $game_preferences[$pref_id]['default'];
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Teams-related functions
    ////////////

    // Returns whether playing by teams is enabled
    private function isTeamPlay()
    {
        return ($this->getGameStateValue('team_play') == SCP_TEAM_PLAY_YES);
    }

    // Return the name of a team or player based on its ID and type
    private function getScorerNameById($scorer_id, $type)
    {
        if ($type == 'player') {
            return self::getPlayerNameById($scorer_id);
        } else {
            $this->loadTeamsBasicInfos();
            return $this->teams[$scorer_id]['team_name'];
        }
    }

    // Returns data for all teams
    private function loadTeamsBasicInfos($players = [])
    {
        if ($players == []) {
            $players = $this->loadPlayersBasicInfosWithTeam();
        }

        if (!isset($this->teams)) {
            $this->teams = [];
            for ($team_id = 1; $team_id < (count($players)/2 + 1); $team_id++) {
                $team_players = array_filter($players, function ($v) use ($team_id) {
                    return $v['team_id'] == $team_id;
                });

                $team_players_id = array_keys($team_players);

                $team_name = clienttranslate('Team ${player1} and ${player2}');
                $team_name = str_replace('${player1}', $team_players[$team_players_id[0]]['player_name'], $team_name);
                $team_name = str_replace('${player2}', $team_players[$team_players_id[1]]['player_name'], $team_name);

                $this->teams[$team_id] = [
                    'team_id' => $team_id,
                    'team_name' => $team_name,
                    'players' => array_keys($team_players),
                ];
            }
        }
        return $this->teams;
    }

    // Returns the ID of the player's team
    private function getPlayerTeam($player_id)
    {
        $players = $this->loadPlayersBasicInfosWithTeam();

        return $players[$player_id]['team_id'];
    }

    // Does the same as loadPlayerBasicInfos, with the team_id added
    private function loadPlayersBasicInfosWithTeam()
    {
        $players = self::loadPlayersBasicInfos();
        if ($this->isTeamPlay()) {
            $sql = 'SELECT player_id, player_score, team_id FROM player';
            $data = self::getCollectionFromDB($sql);
            $this->players_to_team = array_map(function ($v) {
                return $v['team_id'];
            }, $data);
            $players_scores = array_map(function ($v) {
                return $v['player_score'];
            }, $data);

            $teams = array_fill_keys(array_unique($this->players_to_team), []);
            foreach ($teams as $team_id => $temp) {
                $teams[$team_id]['players'] = array_keys(array_filter($this->players_to_team, function ($v) use ($team_id) {
                    return $v == $team_id;
                }));
            }

            foreach ($players as $player_id => $player) {
                $players[$player_id]['score'] = $players_scores[$player_id];
                $players[$player_id]['team_id'] = $this->players_to_team[$player_id];
                $team_players = $teams[$players[$player_id]['team_id']]['players'];
                $ally = array_filter($team_players, function ($v) use ($player_id) {
                    return $v != $player_id;
                });
                $players[$player_id]['ally'] = array_pop($ally);
            }
        } else {
            $sql = 'SELECT player_id, player_score FROM player';
            $data = self::getCollectionFromDB($sql, true);
            foreach ($data as $player_id => $score) {
                $players[$player_id]['score'] = $score;
            }
        }
        return $players;
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Notification functions
    ////////////
    // Note: some notifications are sent outside of the below ones


    // Sends counts of data in player's hands and deck
    private function notif_cardsCount()
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
    private function notif_cardsInHand($player_id = 0)
    {
        if (0 == $player_id) {
            $player_id = self::getCurrentPlayerId();
        }

        // Cards in player hand
        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        self::notifyPlayer(
            $player_id,
            'cardsInHand',
            clienttranslate('Your cards are: <br />${cards_display}'),
            [
                'cards' => $cards,
                'cards_display' => $this->cardsToDisplay($cards)
            ]
        );
    }

    // Sends the cards that are on the table
    private function notif_cardsOnTable()
    {
        $cards = $this->cards->getCardsInLocation('table');
        self::notifyAllPlayers(
            'cardsOnTable',
            clienttranslate('Cards dealt to the table: <br />${cards_display}'),
            [
                'cards' => $cards,
                'cards_display' => $this->cardsToDisplay($cards)
            ]
        );
    }

    // Updates player's scores
    private function notif_playerScores()
    {
        $sql = 'SELECT player_id, player_score FROM player';
        $data = self::getCollectionFromDb($sql, true);
        self::notifyAllPlayers('playerScores', '', ['score' => $data]);
    }

    // A player wins: update score, statistics & send notification
    private function playerWin($scorer_id, $win_type, &$scoring_table = null)
    {
        // Define who scored - team or player + their name (for notifications purpose)
        $scorer_type = $this->isTeamPlay() ? 'team' : 'player';
        $scorer_name = self::getScorerNameById($scorer_id, $scorer_type);

        if (!$this->isTeamPlay()) {
            $this->incStat(1, $win_type, $scorer_id);
        }

        $this->DbQuery('UPDATE player SET player_score=player_score+1 WHERE '.$scorer_type.'_id = "'.$scorer_id.'"');

        if ('scopa_number' == $win_type) {
            $this->DbQuery('UPDATE player SET scopa_in_round=scopa_in_round+1 WHERE '.$scorer_type.'_id="'.$scorer_id.'"');
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
                'player_name' => $scorer_name, // called "player_name" so that JS colors the names
            ]
        );

        if (!is_null($scoring_table)) {
            ++$scoring_table['added_points'][$scorer_id];
            ++$scoring_table['final_score'][$scorer_id];
        }

        // Update scores
        $this->notif_playerScores();
        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);
    }

    // A player wins points for variants: update DB & notify, but no statistic
    private function playerWinsVariantPoints($scorer_id, $win_type, $nb_points, &$scoring_table, $card_captured = '')
    {
        // Define who scored - team or player + their name (for notifications purpose)
        $scorer_type = $this->isTeamPlay() ? 'team' : 'player';
        $scorer_name = self::getScorerNameById($scorer_id, $scorer_type);

        $this->DbQuery('UPDATE player SET player_score=player_score+'.$nb_points.' WHERE '.$scorer_type.'_id = "'.$scorer_id.'"');

        $win_types = [
            'il_ponino' => clienttranslate('${player_name} captured all knights and marks ${nb_points} points.'),
            'napola' => clienttranslate('${player_name} captured a series of coin cards and marks ${nb_points} points.'),
            're_bello' => clienttranslate('${player_name} captured the king of coins and marks a point.'),
            'scopone_de_trente' => clienttranslate('${player_name} captured ${card_captured} of coins and marks ${nb_points} points.'),
            'scopone_de_trente_all' => clienttranslate('${player_name} captured Ace, 2 and 3 of coins - Victory!'),
        ];
        self::notifyAllPlayers(
            'message',
            $win_types[$win_type],
            [
                'player_name' => $scorer_name,  // called "player_name" so that JS colors the names
                'nb_points' => $nb_points,
                'card_captured' => $card_captured,
            ]
        );

        $scoring_table['variant'][$scorer_id] += $nb_points;
        $scoring_table['added_points'][$scorer_id] += $nb_points;
        $scoring_table['final_score'][$scorer_id] += $nb_points;

        // Update scores
        $this->notif_playerScores();
        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);
    }

    // There is a tie => notify everyone
    private function playerTie($tie_type)
    {
        // Ties are not possible for sette bello or scopa
        if ($this->isTeamPlay()) {
            $tie_types = [
                'cards_captured' => clienttranslate('Multiple teams captured the most cards. No point won!'),
                'coins_captured' => clienttranslate('Multiple teams captured the most coins. No point won!'),
                'prime_score' => clienttranslate('Multiple teams have the highest prime score. No point won!'),
                'il_ponino' => clienttranslate('No team captured the 4 knights. No point won!'),
            ];
        } else {
            $tie_types = [
                'cards_captured' => clienttranslate('Multiple players captured the most cards. No point won!'),
                'coins_captured' => clienttranslate('Multiple players captured the most coins. No point won!'),
                'prime_score' => clienttranslate('Multiple players have the highest prime score. No point won!'),
                'il_ponino' => clienttranslate('Nobody captured the 4 knights. No point won!'),
            ];
        }
        self::notifyAllPlayers('message', $tie_types[$tie_type], []);
        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);
    }

    // Display player score table
    private function notif_finalScore($score_table, $scoring_rows)
    {
        // Format the scoring table
        $score_table_display = [];
        if ($this->isTeamPlay()) {
            $scorers = $this->loadTeamsBasicInfos();
        } else {
            $scorers = self::loadPlayersBasicInfos();
        }
        ksort($scorers);

        // First line: names
        $header = [''];
        foreach ($scorers as $scorer_id => $scorer) {
            $header[] = [
                'str' => '${player_name}', // called "player_name" so that JS colors the names
                'args' => ['player_name' => isset($scorer['player_name']) ? $scorer['player_name'] : $scorer['team_name']],
                'type' => 'header',
            ];
        }
        $score_table_display[] = $header;

        // This is used to default all scores to 0 for each row (otherwise they're not displayed)
        $scorer_to_zero = array_fill_keys(array_keys($scorers), 0);
        foreach ($scoring_rows as $code => $label) {
            $score = $score_table[$code];
            ksort($score);
            $score_table_display[] = [0 => $label] + $score + $scorer_to_zero;
        }

        // Finally, send notification
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

        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);
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
        // Check if auto-play or not - if not, check player is allowed
        $state = $this->gamestate->state();
        if ($state['name'] == 'playerTurn') {
            self::checkAction('playCard');
        }

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
                clienttranslate('${player_name} plays ${value_label} of ${color_label} and captures ${nb_capture} card(s):<br />${cards_display}'),
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
                    'cards_display' => $this->cardsToDisplay($capture),
                ]
            );

            // Check if Scopa happened
            if (0 == $this->cards->countCardInLocation('table')) {
                // If the player is last and it's the last round, no scopa is possible
                if (0 != $this->cards->countCardInLocation('deck') || 0 != $this->cards->countCardInLocation('hand')) {
                    if ($this->isTeamPlay()) {
                        $this->playerWin($this->getPlayerTeam($player_id), 'scopa_number');
                    } else {
                        $this->playerWin($player_id, 'scopa_number');
                    }
                }
            }
        }

        //Update card counts
        $this->notif_cardsCount();

        $this->gamestate->nextState('playCard');
    }

    // Change of user preferences
    public function setUserPref($pref_id, $pref_value)
    {
        $player_id = self::getCurrentPlayerId();

        $sql = 'REPLACE INTO user_preferences (player_id, pref_id, pref_value)';
        $sql .= ' VALUES ('.$player_id.', '.$pref_id.', '.$pref_value.')';
        self::DbQuery($sql);
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

        // In Scopone scientifico, no cards are dealt on the table
        if (!in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_SCOPONE_SCIENTIFICO])) {
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
        }

        // Notify all players about the card's table
        $this->notif_cardsOnTable();

        // Reset the counter of Scopa of the round
        $sql = 'UPDATE player SET scopa_in_round = 0';
        self::DbQuery($sql);

        // Next, deal cards to each player
        $this->gamestate->nextState();
    }

    // Deal new cards to each player from the deck
    public function stHandStart()
    {
        // Deal cards to each player (without re-shuffling)
        $cards_in_hand = [
            SCP_VARIANT_SCOPA => 3,
            SCP_VARIANT_IL_PONINO => 3,
            SCP_VARIANT_NAPOLA => 3,
            SCP_VARIANT_SCOPONE => 9,
            SCP_VARIANT_SCOPONE_SCIENTIFICO => 10,
            SCP_VARIANT_SCOPA_DI_QUINDICI => 3,
            SCP_VARIANT_SCOPONE_DE_TRENTE => 9,
            SCP_VARIANT_ASSO_PIGLIA_TUTTO => 3,
            SCP_VARIANT_RE_BELLO => 3,
            SCP_VARIANT_SCOPA_A_PERDERE => 3,
            SCP_VARIANT_SCOPA_FRAC => 3,
        ][$this->getGameStateValue('game_variant')];
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $cards = $this->cards->pickCardsForLocation($cards_in_hand, 'deck', 'hand', $player_id, true);
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
            $next_player_id = self::activeNextPlayer();
            self::giveExtraTime($next_player_id);
            if ($this->getPlayerAutoPlay($next_player_id) == SCP_PREF_AUTO_PLAY_YES) {
                $this->gamestate->nextState('autoPlayerTurn');
            } else {
                $this->gamestate->nextState('playerTurn');
            }
        }
    }

    // Plays automatically the last card in hand
    public function stAutoPlayer()
    {
        $player_id = self::getActivePlayerId();

        // Make sure the player indeed auto-plays
        if ($this->getPlayerAutoPlay($player_id) != SCP_PREF_AUTO_PLAY_YES) {
            $this->gamestate->nextState('playerTurn');
        }


        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        // Multiple cards ==> Manual choice
        if (count($cards) != 1) {
            $this->gamestate->nextState('playerTurn');
            return;
        }

        $card = array_pop($cards);
        $possible_captures = self::getCardCaptures($player_id);
        // Capturing or not capturing, that is the question
        if (!array_key_exists($card['id'], $possible_captures)) {
            $this->playCard($card['id'], []);
        } elseif (count($possible_captures[$card['id']]) == 1) {
            $capture = array_pop($possible_captures[$card['id']]);
            $cards_capture = array_map(function ($v) {
                return $v['id'];
            }, $capture['cards']);
            $this->playCard($card['id'], $cards_capture);
        }
        // Multiple captures possible ==> Manual choice
        else {
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
        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);

        // Give the remaining cards to the player who captured last
        $sql = 'SELECT player_id FROM player WHERE has_last_captured = TRUE';
        $player_last_capture = self::getCollectionFromDB($sql);
        if (1 == count($player_last_capture)) {
            $player_last_capture = array_pop($player_last_capture)['player_id'];
            $cards = $this->cards->getCardsInLocation('table');
            $this->cards->moveAllCardsInLocation('table', 'capture', null, $player_last_capture);

            self::notifyAllPlayers(
                'playerCapturesTable',
                clienttranslate('${player_name} captures all remaining cards:<br />${cards_display}'),
                [
                    'player_id' => $player_last_capture,
                    'player_name' => self::getPlayerNameById($player_last_capture),
                    'cards_display' => $this->cardsToDisplay($cards),
                ]
            );
        }

        $cards = $this->cards->getCardsInLocation('capture');
        $players = $this->loadPlayersBasicInfosWithTeam();
        $scorers = $players;

        if ($this->isTeamPlay()) {
            $scorers = $this->loadTeamsBasicInfos();

            foreach ($cards as $card_id => $card) {
                $cards[$card_id]['location_arg'] = $players[$card['location_arg']]['team_id'];
            }
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
            'variant' => '',
            'added_points' => clienttranslate('Points won this round'),
            'final_score' => clienttranslate('Final score'),
        ];
        foreach ($scoring_rows as $code => $label) {
            $score_table[$code] = array_fill_keys(array_keys($scorers), 0);
        }

        // For regular scopa, remove the "variant" row
        if (in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_SCOPA, SCP_VARIANT_SCOPONE, SCP_VARIANT_SCOPONE_SCIENTIFICO, SCP_VARIANT_SCOPA_DI_QUINDICI, SCP_VARIANT_ASSO_PIGLIA_TUTTO])) {
            unset($scoring_rows['variant']);
        } else {
            $game_options = $this->getTableOptions();
            $variant_id = $this->getGameStateValue('game_variant');
            $scoring_rows['variant'] = $game_options[SCP_VARIANT]['values'][$variant_id]['name'];
        }

        // Scoring for regular Scopa game
        $this->scoreScopa($cards, $score_table);
        $this->scoreSetteBello($cards, $score_table);
        $this->scoreCardsCaptured($cards, $score_table);
        $this->scoreCoinsCaptured($cards, $score_table);
        $this->scorePrime($cards, $score_table);

        // Score for variants (at least some)
        switch ($this->getGameStateValue('game_variant')) {
            case SCP_VARIANT_IL_PONINO:
                $this->scoreIlPonino($cards, $score_table);
                break;

            case SCP_VARIANT_NAPOLA:
                $this->scoreNapola($cards, $score_table);
                break;

            case SCP_VARIANT_SCOPONE_DE_TRENTE:
                $this->scoreScoponeDeTrente($cards, $score_table);
                break;

            case SCP_VARIANT_RE_BELLO:
                $this->scoreReBello($cards, $score_table);
                break;

            case SCP_VARIANT_SCOPA_FRAC:
                $this->scoreScopaFrac($cards, $score_table);
                break;
        }

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
        $this->notif_finalScore($score_table, $scoring_rows);

        // Should we go to next round or not?
        // Is the game over or not?
        $target_score = $this->target_score_mapping[$this->getGameStateValue('target_score')];
        if ($this->isTeamPlay()) {
            $sql = 'SELECT DISTINCT team_id, player_score
                                FROM player
                                WHERE player_score >= '.$target_score;
        } else {
            $sql = 'SELECT player_id, player_score
                                FROM player
                                WHERE player_score >= '.$target_score;
        }
        $player_score = self::getCollectionFromDb($sql, true);
        if (0 == count($player_score)) {
            // No player has enough, start new round
            $this->gamestate->nextState('dealStart');
            if ($this->isTeamPlay()) {
                self::notifyAllPlayers('message', clienttranslate('No team has enough points. The game continues!'), []);
            } else {
                self::notifyAllPlayers('message', clienttranslate('No player has enough points. The game continues!'), []);
            }

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
            if ($this->isTeamPlay()) {
                self::notifyAllPlayers('message', clienttranslate('The top teams have the same score. The game continues!'), []);
            } else {
                self::notifyAllPlayers('message', clienttranslate('The top players have the same score. The game continues!'), []);
            }
            $this->gamestate->nextState('dealStart');
        } else {
            // We have a winner!
            $this->gamestate->nextState('gameEnd');
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Scoring functions
    ////////////

    // Scores scopa points (= who sweeped the table)
    private function scoreScopa($cards, &$score_table)
    {
        // Scopa score is from DB directly
        if ($this->isTeamPlay()) {
            $sql = 'SELECT DISTINCT team_id, player_score score, scopa_in_round FROM player';
        } else {
            $sql = 'SELECT player_id, player_score score, scopa_in_round FROM player';
        }
        $values = self::getCollectionFromDb($sql);

        // Scopa points have already been added to the score, so remove them
        $score_table['previous_score'] = array_map(
            function ($v) {
                return $v['score'] - $v['scopa_in_round'];
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
    }

    // Scores Sette bello points (= who has the 7 of coins)
    private function scoreSetteBello($cards, &$score_table)
    {
        $card = array_filter($cards, function ($v) {
            return $v['type'] == 1 && $v['type_arg'] == 7;
        });
        $sette_bello = array_pop($card);
        if ('capture' != $sette_bello['location']) {
            throw new BgaVisibleSystemException(self::_('Invalid state: 7 of coins in unexpected place'));
        }
        $this->playerWin($sette_bello['location_arg'], 'sette_bello', $score_table);
        $score_table['sette_bello'][$sette_bello['location_arg']] = 1;
    }

    // Scores Cards captures (= most cards captured)
    private function scoreCardsCaptured($cards, &$score_table)
    {
        $cardsCaptured = array_fill_keys(array_unique(array_map(function ($v) {
            return $v['location_arg'];
        }, $cards)), 0);
        foreach ($cardsCaptured as $player_id => $temp) {
            $cardsCaptured[$player_id] = count(array_filter($cards, function ($v) use ($player_id) {
                return $v['location_arg'] == $player_id;
            }));
        }

        $score_table['cards_captured'] = $cardsCaptured;
    }

    // Scores Coinds captures (= most coins captured)
    private function scoreCoinsCaptured($cards, &$score_table)
    {
        $coinsCaptured = array_fill_keys(array_unique(array_map(function ($v) {
            return $v['location_arg'];
        }, $cards)), 0);
        foreach ($coinsCaptured as $player_id => $temp) {
            $coinsCaptured[$player_id] = count(array_filter($cards, function ($v) use ($player_id) {
                return $v['location_arg'] == $player_id && $v['type'] == 1;
            }));
        }

        $score_table['coins_captured'] = $coinsCaptured;
    }

    // Scores Prime points (= complex calculation)
    private function scorePrime($cards, &$score_table)
    {
        if ($this->isTeamPlay()) {
            $scorers = $this->loadTeamsBasicInfos();
        } else {
            $scorers = $this->loadPlayersBasicInfos();
        }
        $scorers_prime = array_fill_keys(array_keys($scorers), ['1' => '', '2' => '', '3' => '', '4' => '']);
        $point_per_card = $this->prime_points;
        foreach ($scorers_prime as $scorer_id => $colors) {
            $scorer_cards = array_filter($cards, function ($v) use ($scorer_id) {
                return $v['location_arg'] == $scorer_id;
            });
            foreach ($colors as $color_id => $temp) {
                $cards_of_color = array_filter(
                    $scorer_cards,
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
                    $scorers_prime[$scorer_id][$color_id] = max($prime_points);
                } else {
                    $scorers_prime[$scorer_id][$color_id] = 0;
                }
            }
            $scorers_prime[$scorer_id] = array_sum($scorers_prime[$scorer_id]);
        }
        $score_table['prime_score'] = $scorers_prime;
    }

    // Scores Il ponino points (= doubles Scopa points if captured all knights)
    private function scoreIlPonino($cards, &$score_table)
    {
        $knights = array_filter($cards, function ($v) {
            return $v['type_arg'] == 9;
        });
        $playerWithAll = array_pop($knights)['location_arg'];
        foreach ($knights as $knight) {
            if ($knight['location_arg'] != $playerWithAll) {
                $this->playerTie('il_ponino');
                return;
            }
        }

        $this->playerWinsVariantPoints($playerWithAll, 'il_ponino', $score_table['scopa_number'][$playerWithAll], $score_table);
    }

    // Scores Napola points (= A+2+3 of coin is worth 3, A+2+3+4 worth 4, ...)
    private function scoreNapola($cards, &$score_table)
    {
        $players = array_unique(array_map(function ($v) {
            return $v['location_arg'];
        }, $cards));
        foreach ($players as $player_id) {
            $coinsCaptured = array_filter($cards, function ($v) use ($player_id) {
                return $v['location_arg'] == $player_id && $v['type'] == 1;
            });

            $coinsCaptured = array_values(array_map(function ($v) {
                return (int)$v['type_arg'];
            }, $coinsCaptured));

            $max_coin_captured = 0;
            while (in_array($max_coin_captured+1, $coinsCaptured)) {
                $max_coin_captured++;
            }

            if ($max_coin_captured > 0) {
                break;
            }
        }

        if ($max_coin_captured > 2) {
            $this->playerWinsVariantPoints($player_id, 'napola', $max_coin_captured, $score_table);
        }
    }

    // Scores Scopone de Trente points (= A, 2, 3 of coins are worth 1 each, having all coins = victory)
    private function scoreScoponeDeTrente($cards, &$score_table)
    {
        $coin_cards = array_values(array_filter($cards, function ($card) {
            return $card['type'] == 1 && in_array($card['type_arg'], [1, 2, 3]);
        }));
        uasort($coin_cards, function ($a, $b) {
            return ($a['type_arg'] < $b['type_arg']) ? -1 : 1;
        });

        $teamWithAll = $coin_cards[0]['location_arg'];
        foreach ($coin_cards as $card) {
            if ($card['location_arg'] != $teamWithAll) {
                $teamWithAll = 0;
            }
            // Each of A, 2 and 3 of coin is worth 1, 2 or 3 points
            $this->playerWinsVariantPoints($card['location_arg'], 'scopone_de_trente', (int)$card['type_arg'], $score_table, $this->values_label[$card['type_arg']]);
        }
        // The team with all 3 wins
        if ($teamWithAll != 0) {
            $this->playerWinsVariantPoints($teamWithAll, 'scopone_de_trente_all', 100, $score_table);
        }
    }

    // Scores Re bello points (= K of coins is worth 1 point)
    private function scoreReBello($cards, &$score_table)
    {
        // SCP_VARIANT_RE_BELLO: Code scoring function
        $re_bello = array_filter($cards, function ($card) {
            return $card['type'] == 1 && $card['type_arg'] == 10;
        });
        $player_id = array_pop($re_bello)['location_arg'];

        $this->playerWinsVariantPoints($player_id, 're_bello', 1, $score_table);
    }

    // Scores Scopa Frac points (= J, Kn, K, A worth 1 each. If equality at 8, K of coin wins)
    private function scoreScopaFrac($cards, &$score_table)
    {
        //SCP_VARIANT_SCOPA_FRAC: Code scoring function

        //$this->playerWinsVariantPoints($playerWithAll, 'scopa_frac', $nb_points, $score_table);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    // Zombie turn: Plays a card at random
    public function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($this->isTeamPlay()) {
            throw new BgaVisibleSystemException(self::_('Zombie mode not supported in team plays, as it would disadvantage one of the teams'));
        }

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

        throw new BgaVisibleSystemException(self::_('Zombie mode not supported at this game state: ').$statename);
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
        // Added display preferences
        if ($from_version <= 2110081254) {
            $sql = 'ALTER TABLE DBPREFIX_player ADD `card_deck` VARCHAR(20) NOT NULL DEFAULT "italian",
                     ADD `display_card_labels` BOOLEAN NOT NULL DEFAULT 1';

            self::applyDbUpgradeToAllDB($sql);
        }

        // Moved preferences to BGA's framework & an actual table (if needed in PHP)
        if ($from_version <= 2110091744) {
            $sql = 'ALTER TABLE DBPREFIX_player DROP `card_deck`,
                     DROP `display_card_labels`';

            self::applyDbUpgradeToAllDB($sql);
            $sql = 'CREATE TABLE IF NOT EXISTS `user_preferences` (
                      `player_id` int(10) NOT NULL,
                      `pref_id` int(10) NOT NULL,
                      `pref_value` int(10) NOT NULL,
                      PRIMARY KEY (`player_id`, `pref_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

            self::applyDbUpgradeToAllDB($sql);
        }

        // Added possibility to play in teams
        if ($from_version <= 2110111902) {
            $sql = 'ALTER TABLE DBPREFIX_player ADD `team_id` INT NOT NULL';

            self::applyDbUpgradeToAllDB($sql);
        }
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

        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        $sql = 'INSERT INTO player (player_id, player_score, player_color, player_canal, player_name, player_avatar) VALUES ';
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = '("'.$player_id.'",0,"'.$color.'","'.$player['player_canal'].'","'.addslashes($player['player_name']).'","'.addslashes($player['player_avatar']).'")';
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);

        // Assign players to teams
        if ($this->isTeamPlay()) {
            $team_id = 0;
            $teams = [0 => [], 1 => []];
            if (count($player) == 6) {
                $teams[2] = [];
            }

            $player_order = $this->getNextPlayerTable();
            $player_pointer = $player_order[0];
            $i = 0;
            while ($i != count($players)) {
                $teams[$team_id][] = $player_pointer;
                $team_id ++;
                $team_id %= (count($players) / 2);

                $player_pointer = $player_order[$player_pointer];
                $i++;
            }

            foreach ($teams as $team_id => $team_players) {
                // The "+1" is needed so that team_id != 0
                // Having it equal to 0 is confusing for players + it makes some JS piece fail
                $sql = 'UPDATE player SET team_id = '.$team_id.'+1 WHERE player_id IN ('.implode(', ', $team_players).')';
                self::DbQuery($sql);
            }
        }


        self::reloadPlayersBasicInfos();

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
        $result['players'] = $this->loadPlayersBasicInfosWithTeam();

        // Material info (used for displaying card labels)
        $result['colors'] = $this->colors;
        $result['values_label'] = $this->values_label;

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
