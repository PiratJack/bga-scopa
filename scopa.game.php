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

class scopa extends Table {
    public function __construct() {
        parent::__construct();

        self::initGameStateLabels(
            [
                'target_score' => SCP_OPTION_POINTS_TO_WIN,
                'max_capture_cards' => SCP_OPTION_MAX_CAPTURE,
                'game_variant' => SCP_VARIANT,
                'napola_variant' => SCP_VARIANT_NAPOLA_ENABLED,
                'team_play' => SCP_TEAM_PLAY,
                'who_captures_remaining' => SCP_WHO_CAPTURES_REMAINING,
                'multiple_captures' => SCP_MULTIPLE_CAPTURES,
                'team_composition' => SCP_TEAM_COMPOSITION,

                'cirulla_joker_value' => SCP_GLOBAL_CIRULLA_JOKER_VALUE,
            ]
        );

        $this->target_score_mapping = [
            SCP_OPTION_POINTS_TO_WIN_11 => 11,
            SCP_OPTION_POINTS_TO_WIN_16 => 16,
            SCP_OPTION_POINTS_TO_WIN_21 => 21,
            SCP_OPTION_POINTS_TO_WIN_31 => 31,
            SCP_OPTION_POINTS_TO_WIN_51 => 51,
        ];

        $this->cards = self::getNew('module.common.deck');
        $this->cards->init('card');
    }

    // Compute and return the current game progression.
    public function getGameProgression() {
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
    public function getCardCaptures($active_player_id) {
        // Cards in player hand
        $hand = $this->cards->getCardsInLocation('hand', $active_player_id);

        // Cards played on the table
        $table = $this->cards->getCardsInLocation('table');
        $nb_table_cards = count($table);

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

        // In Scopa di Quindici & Escoba, capture is possible if the sum of cards is 15
        if (in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_SCOPA_DI_QUINDICI, SCP_VARIANT_ESCOBA, SCP_VARIANT_ESCOBA_NO_PRIME])) {
            foreach ($hand as $card_id => $card) {
                $possible_actions[$card_id] = array_filter(
                    $combinations,
                    function ($value) use ($card) {
                        return $value['total'] + $card['type_arg'] == 15;
                    }
                );
            }
        } // In Asso piglia tutto (simplified), Aces capture everything
        elseif ($this->getGameStateValue('game_variant') == SCP_VARIANT_ASSO_PIGLIA_TUTTO) {
            foreach ($hand as $card_id => $card) {
                $possible_actions[$card_id] = array_filter(
                    $combinations,
                    function ($value) use ($card, $nb_table_cards) {
                        return ($value['total'] == $card['type_arg']) || ($card['type_arg'] == 1 && $value['size'] == $nb_table_cards);
                    }
                );
            }
        }
        // In Asso piglia tutto (traditional), Aces capture everything with 2 conditions:
        // - There is no ace on table
        // - You're not the first player
        elseif ($this->getGameStateValue('game_variant') == SCP_VARIANT_ASSO_PIGLIA_TUTTO_TRADITIONAL) {
            $ace_on_table = count(array_filter($table, function ($card) {
                return $card['type_arg'] == 1;
            })) > 0;
            $first_player_first_round = (count($table) == 4) && (count($this->cards->getCardsInLocation('capture')) == 0) && (count($this->cards->getCardsInLocation('hand')) == $this->getPlayersNumber() * 3);

            $ace_captures_all = !$ace_on_table && !$first_player_first_round;

            foreach ($hand as $card_id => $card) {
                $possible_actions[$card_id] = array_filter(
                    $combinations,
                    function ($value) use ($card, $ace_captures_all, $nb_table_cards) {
                        return ($value['total'] == $card['type_arg']) || ($card['type_arg'] == 1 && $value['size'] == $nb_table_cards && $ace_captures_all);
                    }
                );
            }
        }
        // In Cirulla, capture is possible if either:
        // - The sum of cards on table equals the card played
        // - The sum of cards on the table + the card played is equal to 15
        // - Ace captures all cards (unless there's an ace on the table)
        // If multiple combinations are possible, any is possible (no need to capture the least number of cards)
        elseif ($this->getGameStateValue('game_variant') == SCP_VARIANT_CIRULLA) {
            $ace_on_table = count(array_filter($table, function ($card) {
                return $card['type_arg'] == 1;
            })) > 0;

            $ace_captures_all = !$ace_on_table;


            foreach ($hand as $card_id => $card) {
                $possible_actions[$card_id] = array_filter(
                    $combinations,
                    function ($value) use ($card, $ace_captures_all, $nb_table_cards) {
                        // Replace 7 of cup with joker value
                        if ($card['type'] == 2 && $card['type_arg'] == 7) {
                            $card['type_arg'] = self::getGameStateValue('cirulla_joker_value');
                        }

                        // Ace captures the entire table, unless there's an ace on table
                        if ($card['type_arg'] == 1 && $ace_captures_all) {
                            return $value['size'] == $nb_table_cards;
                        }

                        // Capture is possible if sum (table) = card or sum (hand+table) = 15
                        return ($value['total'] == $card['type_arg']) || ($value['total'] + $card['type_arg'] == 15);
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

            // Keep combinations if total cards = card in hand
            foreach ($hand as $card_id => $card) {
                $possible_actions[$card_id] = array_filter(
                    $combinations,
                    function ($value) use ($card) {
                        return $value['total'] == $card['type_arg'];
                    }
                );
            }
        }

        // Keep only the combination that has the smallest number of cards
        // In Scopa Frac & Cirulla, we can capture as many cards as wanted (not only the minimum number)
        if (in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_SCOPA_FRAC, SCP_VARIANT_CIRULLA])) {
            foreach ($possible_actions as $card_id => $combinations) {
                // Remove cards that have no combination possible
                if (0 == count($combinations)) {
                    unset($possible_actions[$card_id]);

                    continue;
                }
                // array_values forces a re-indexing, which means Javascript will see it as an array and not an object
                $possible_actions[$card_id] = array_values($possible_actions[$card_id]);
            }
            return $possible_actions;
        }

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

            $allow_all_captures = $this->getGameStateValue('multiple_captures') == SCP_MULTIPLE_CAPTURES_ALLOW_ALL;
            $allow_all_captures_except_single = $this->getGameStateValue('multiple_captures') == SCP_MULTIPLE_CAPTURES_ALLOW_ALL_EXCEPT_SINGLE;


            // array_values forces a re-indexing, which means Javascript will see it as an array and not an object

            // In Asso piglia tutto (simplified), Aces capture everything
            if (in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_ASSO_PIGLIA_TUTTO])) {
                $possible_actions[$card_id] = array_values(
                    array_filter(
                        $combinations,
                        function ($value) use ($min_cards, $hand, $table, $card_id, $allow_all_captures, $allow_all_captures_except_single) {
                            return ($hand[$card_id]['type_arg'] == 1) ? $value['size'] == count($table) : ($value['size'] == $min_cards || $allow_all_captures || ($min_cards != 1 && $allow_all_captures_except_single));
                        }
                    )
                );
            }
            // In Asso piglia tutto (traditionnal), Aces capture everything with 2 conditions:
            // - There is no ace on table
            // - You're not the first player
            elseif ($this->getGameStateValue('game_variant') == SCP_VARIANT_ASSO_PIGLIA_TUTTO_TRADITIONAL) {
                $possible_actions[$card_id] = array_values(
                    array_filter(
                        $combinations,
                        function ($value) use ($min_cards, $hand, $table, $card_id, $ace_captures_all, $allow_all_captures, $allow_all_captures_except_single) {
                            return ($hand[$card_id]['type_arg'] == 1 && $ace_captures_all) ? $value['size'] == count($table) : ($value['size'] == $min_cards || $allow_all_captures || ($min_cards != 1 && $allow_all_captures_except_single));
                        }
                    )
                );
            }
            // If all captures are allowed except if a single card matches
            elseif ($allow_all_captures_except_single) {
                // There is a single card matching
                if ($min_cards == 1) {
                    $possible_actions[$card_id] = array_values(
                        array_filter(
                            $combinations,
                            function ($value) use ($min_cards) {
                                return $value['size'] == $min_cards;
                            }
                        )
                    );
                }
                // There is no single card matching ==> can capture anything
                else {
                    $possible_actions[$card_id] = array_values($combinations);
                }
            }
            // If all captures are allowed, just apply array_values
            elseif ($allow_all_captures) {
                $possible_actions[$card_id] = array_values($combinations);
            }
            // In regular play, it's possible to capture only the minimum number of cards
            else {
                $possible_actions[$card_id] = array_values(
                    array_filter(
                        $combinations,
                        function ($value) use ($min_cards) {
                            return $value['size'] == $min_cards;
                        }
                    )
                );
            }
        }

        return $possible_actions;
    }

    // Determines all possible combinations of cards
    public function combineCards(&$combinations, $cards_left, $cards_included = []) {
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
    public function cardsToDisplay($cards) {
        $logs = [];
        $args = ['log' => '', 'args' => ['i18n' => []]];

        $cards_by_type = [];
        $card_in_color = 0;
        foreach ($cards as $card) {
            # Add suit/color (card type) to the list of arguments
            $arg_name = 'color_'.$card['type'];
            if (!array_key_exists($arg_name, $args['args'])) {
                $card_in_color = 0;

                $logs[$card['type']] = '${'.$arg_name.'} : ';
                $args['args'][$arg_name] = $this->colors[$card['type']]['name'];
                $args['args']['i18n'][] = $arg_name;
            }
            # Add the card itself
            $card_in_color += 1;
            $arg_name = 'card_'.$card['type'].'_'.$card['type_arg'];
            $logs[$card['type']] .= ' ${'.$arg_name.'} ';
            $args['args'][$arg_name] = $this->values_label[$card['type_arg']];
            $args['args']['i18n'][] = $arg_name;
        }
        $args['log'] = implode('<br />', $logs);

        return $args;
    }

    // Gets and returns a random element in the list (used for Zombie)
    public function getRandomElement($array) {
        $index_list = array_keys($array);
        $rand_number = rand(0, count($index_list) - 1);

        return $array[$index_list[$rand_number]];
    }

    // Returns whether the active player has auto-play on
    public function getPlayerAutoPlay($player_id) {
        return $this->getUserPreference($player_id, SCP_PREF_AUTO_PLAY);
    }

    // Returns whether the active player has auto-play on
    public function getUserPreference($player_id, $pref_id) {
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

    // Returns which Cirulla declaration is possible
    public function getCirullaCombinations($player_id) {
        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        $cirullaCombination = '';
        $joker_values = [];

        $seven_cups_in_hand = count(array_filter($cards, function ($card) {
            return $card['type'] == 2 && $card['type_arg'] == 7;
        })) == 1;


        // Sum of cards <= 10
        $sum_cards = $sum_cards = array_sum(array_map(function ($v) {
            return $v['type_arg'];
        }, $cards));
        if ($sum_cards < 10) {
            $cirullaCombination = 'cirulla_less_than_10';
        } elseif (($sum_cards - 7) < 10 && $seven_cups_in_hand) {
            if (($sum_cards - 7) == 9) {
                $joker_values = [1];
            } else {
                $joker_values = range(1, 9 - ($sum_cards - 7));
            }
            $cirullaCombination = 'cirulla_less_than_10';
        }

        // Three of a kind
        $other_cards_in_hand = array_filter($cards, function ($card) {
            return !($card['type'] == 2 && $card['type_arg'] == 7);
        });
        $card_values = array_values(array_unique(array_map(function ($v) {
            return $v['type_arg'];
        }, $other_cards_in_hand)));
        if (count($card_values) == 1) {
            if ($seven_cups_in_hand) {
                $joker_values = [$card_values[0]];
            }

            $cirullaCombination = 'cirulla_three_kind';
        }

        return [$cards, $cirullaCombination, $joker_values];
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Teams-related functions
    ////////////

    // Returns whether playing by teams is enabled
    private function isTeamPlay() {
        return ($this->getGameStateValue('team_play') == SCP_TEAM_PLAY_YES);
    }

    // Return the name of a team or player based on its ID and type
    private function getScorerNameById($scorer_id, $type) {
        if ($type == 'player') {
            return self::getPlayerNameById($scorer_id);
        } else {
            $this->loadTeamsBasicInfos();
            return $this->teams[$scorer_id]['team_name'];
        }
    }

    // Returns data for all teams
    private function loadTeamsBasicInfos($players = []) {
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
    private function getPlayerTeam($player_id) {
        $players = $this->loadPlayersBasicInfosWithTeam();

        return $players[$player_id]['team_id'];
    }

    // Does the same as loadPlayerBasicInfos, with the team_id added
    public function loadPlayersBasicInfosWithTeam($seats_needed = true) {
        $players = self::loadPlayersBasicInfos();
        if ($this->isTeamPlay()) {
            $sql = 'SELECT player_id, player_score, team_id, scopa_in_round FROM player';
            $data = self::getCollectionFromDB($sql);
            $this->players_to_team = array_map(function ($v) {
                return $v['team_id'];
            }, $data);

            $teams = array_fill_keys(array_unique($this->players_to_team), []);
            foreach ($teams as $team_id => $temp) {
                $teams[$team_id]['players'] = array_keys(array_filter($this->players_to_team, function ($v) use ($team_id) {
                    return $v == $team_id;
                }));
            }

            // Get # of cards captured
            $sql = 'SELECT card_location_arg player_id, count(card_id) nb_cards FROM card WHERE card_location="capture" GROUP BY player_id';
            $cards_captured = self::getCollectionFromDB($sql, true);
            $cards_captured += array_fill_keys(array_keys($players), 0);

            foreach ($players as $player_id => $player) {
                $players[$player_id]['score'] = $data[$player_id]['player_score'];
                $players[$player_id]['scopa_in_round'] = $data[$player_id]['scopa_in_round'];
                $players[$player_id]['team_id'] = $this->players_to_team[$player_id];
                $team_players = $teams[$players[$player_id]['team_id']]['players'];
                $ally = array_filter($team_players, function ($v) use ($player_id) {
                    return $v != $player_id;
                });
                $players[$player_id]['ally'] = array_pop($ally);
                $players[$player_id]['cards_captured'] = $cards_captured[$player_id] + $cards_captured[$players[$player_id]['ally']];
            }
        } else {
            $sql = 'SELECT player_id, player_score, scopa_in_round FROM player';
            $data = self::getCollectionFromDB($sql);

            // Get # of cards captured
            $sql = 'SELECT card_location_arg player_id, count(card_id) nb_cards FROM card WHERE card_location="capture" GROUP BY player_id';
            $cards_captured = self::getCollectionFromDB($sql, true);
            $cards_captured += array_fill_keys(array_keys($players), 0);

            foreach ($data as $player_id => $info) {
                $players[$player_id]['score'] = $info['player_score'];
                $players[$player_id]['scopa_in_round'] = $info['scopa_in_round'];
                $players[$player_id]['cards_captured'] = $cards_captured[$player_id];
            }
        }

        if ($seats_needed) {
            // Define player's seat position
            $player_order = $this->getNextPlayerTable();
            $current_player = $this->getCurrentPlayerId();
            if (!array_key_exists($current_player, $player_order)) {
                $current_player = $player_order[0];
                $players[$current_player]['seat_position'] = 'bottom_left';
            }

            $player_pointer = $player_order[$current_player];
            $order = 1;
            $seat_position = $this->seat_positions[count($players)];
            while ($current_player != $player_pointer && $order < count($players)) {
                $players[$player_pointer]['seat_position'] = $seat_position[$order];
                $order++;
                $player_pointer = $player_order[$player_pointer];
            }
        }

        return $players;
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Notification functions
    ////////////
    // Note: some notifications are sent outside of the below ones


    // Sends counts of data in player's hands and deck
    private function notif_cardsCount() {
        // Cards in each player's hand
        $sql = 'SELECT card_location, IF(card_location="deck","deck",card_location_arg) player, count(card_id) nb_cards
                FROM card where card_location in ("hand", "deck", "capture")
                GROUP BY card_location, player';
        $data = self::getDoubleKeyCollectionFromDB($sql, true);
        // Flatten the array a bit
        if (array_key_exists('deck', $data)) {
            $data['deck'] = $data['deck']['deck'];
        }

        $players = $this->loadPlayersBasicInfosWithTeam(false);
        $player_ids = array_keys($players);
        $data_zero = ['hand' => array_fill_keys($player_ids, 0), 'deck' => 0, 'capture' => array_fill_keys($player_ids, 0)];

        // For team play, merge the player's "scores"

        // Create any missing key (hand, capture or deck)
        $data += $data_zero;
        // Adds any missing player
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] += $data_zero[$key];
            }
        }

        if ($this->isTeamPlay()) {
            foreach ($data['capture'] as $player_id => $value) {
                $data['capture'][$player_id] = $players[$player_id]['cards_captured'];
            }
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
    private function notif_cardsInHand($player_id = 0) {
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
    private function notif_cardsOnTable() {
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
    private function notif_playerScores() {
        $sql = 'SELECT player_id, player_score, scopa_in_round FROM player';
        $data = self::getCollectionFromDb($sql);
        self::notifyAllPlayers('playerScores', '', ['score' => $data]);
    }

    // A player wins: update score, statistics & send notification
    private function playerWin($scorer_id, $win_type, &$scoring_table = null) {
        // Define who scored - team or player + their name (for notifications purpose)
        $scorer_type = $this->isTeamPlay() ? 'team' : 'player';
        $scorer_name = self::getScorerNameById($scorer_id, $scorer_type);

        if (!$this->isTeamPlay() && $win_type != 'sevens_captured') {
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
            'sevens_captured' => clienttranslate('${player_name} captured the most sevens and wins a point.'),
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
    private function playerWinsVariantPoints($scorer_id, $win_type, $nb_points, &$scoring_table, $card_captured = '') {
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
            'scopa_frac' => clienttranslate('${player_name} captured ${nb_points} valuable cards and marks ${nb_points} points'),
            'escoba_all_sevens' => clienttranslate('${player_name} captured all sevens and marks a point'),
            'cirulla_picolla' => clienttranslate('${player_name} captured a series of ${card_captured} cards of coins and marks ${nb_points} points.'),
            'cirulla_grande' => clienttranslate('${player_name} captured all face cards of coins and marks 5 point'),
            'cirulla_all' => clienttranslate('${player_name} captured all coins - Victory!'),
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

    // A player wins points for Napola: Since it may be counted separately, I have a separate function
    private function playerWinsNapolaPoints($scorer_id, $win_type, $nb_points, &$scoring_table) {
        // Enabled through the "regular" variant dropdown
        if ($this->getGameStateValue('game_variant') == SCP_VARIANT_NAPOLA) {
            $this->playerWinsVariantPoints($scorer_id, $win_type, $nb_points, $scoring_table);
        }
        // Enabled separately ==> need to move it to a separate row (otherwise, counted twice)
        else {
            $current_variant_scores = $scoring_table['variant'];
            $this->playerWinsVariantPoints($scorer_id, $win_type, $nb_points, $scoring_table);
            $scoring_table['napola'] = $scoring_table['variant'];
            $scoring_table['variant'] = $current_variant_scores;
        }
    }

    // A player wins points for Cirulla (at the start)
    private function playerWinsCirullaPoints($scorer_id, $win_type, $nb_points, $cards = [], $sum_cards = 0) {
        // Define who scored - team or player + their name (for notifications purpose)
        $scorer_type = $this->isTeamPlay() ? 'team' : 'player';
        $scorer_name = self::getScorerNameById($scorer_id, $scorer_type);


        $win_types = [
            'cirulla_dealer' => clienttranslate('The table cards sum to ${sum_cards}. ${player_name} marks ${nb_points} point(s) and captures them: <br />${cards_display}'),
            'cirulla_less_than_10' => clienttranslate('${player_name} has 3 cards summing less than 10 and marks 3 points: <br />${cards_display}'),
            'cirulla_three_kind' => clienttranslate('${player_name} has a three of a kind and marks 10 points: <br />${cards_display}'),
        ];
        self::notifyAllPlayers(
            'message',
            $win_types[$win_type],
            [
                'player_name' => $scorer_name,  // called "player_name" so that JS colors the names
                'nb_points' => $nb_points,
                'sum_cards' => $sum_cards,
                'cards_display' => $this->cardsToDisplay($cards)
            ]
        );

        if ($win_type == 'cirulla_dealer') {
            $this->DbQuery('UPDATE player SET scopa_in_round=scopa_in_round+'.$nb_points.' WHERE '.$scorer_type.'_id = "'.$scorer_id.'"');
        } else {
            $this->DbQuery('UPDATE player SET cirulla_points=cirulla_points+'.$nb_points.' WHERE '.$scorer_type.'_id = "'.$scorer_id.'"');
        }

        $this->DbQuery('UPDATE player SET player_score=player_score+'.$nb_points.' WHERE '.$scorer_type.'_id = "'.$scorer_id.'"');

        // Update scores
        $this->notif_playerScores();
        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);
    }

    // There is a tie => notify everyone
    private function playerTie($tie_type) {
        // Ties are not possible for sette bello or scopa
        if ($this->isTeamPlay()) {
            $tie_types = [
                'cards_captured' => clienttranslate('Multiple teams captured the most cards. No point won!'),
                'coins_captured' => clienttranslate('Multiple teams captured the most coins. No point won!'),
                'prime_score' => clienttranslate('Multiple teams have the highest prime score. No point won!'),
                'il_ponino' => clienttranslate('No team captured the 4 knights. No point won!'),
                'sevens_captured' => clienttranslate('Multiple teams captured the most sevens. No point won!'),
            ];
        } else {
            $tie_types = [
                'cards_captured' => clienttranslate('Multiple players captured the most cards. No point won!'),
                'coins_captured' => clienttranslate('Multiple players captured the most coins. No point won!'),
                'prime_score' => clienttranslate('Multiple players have the highest prime score. No point won!'),
                'il_ponino' => clienttranslate('Nobody captured the 4 knights. No point won!'),
                'sevens_captured' => clienttranslate('Multiple players captured the most sevens. No point won!'),
            ];
        }
        self::notifyAllPlayers('message', $tie_types[$tie_type], []);
        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);
    }

    // Display player score table
    private function notif_finalScore($score_table, $scoring_rows) {
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

    public function playCard($card_id_ajax, $cards_captured_ajax) {
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
            // Scopa Frac: no Scopa is possible
            if ($this->getGameStateValue('game_variant') != SCP_VARIANT_SCOPA_FRAC) {
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
        }

        //Update card counts
        $this->notif_cardsCount();

        $this->gamestate->nextState('playCard');
    }

    // Change of user preferences
    public function setUserPref($pref_id, $pref_value) {
        $player_id = self::getCurrentPlayerId();

        $sql = 'REPLACE INTO user_preferences (player_id, pref_id, pref_value)';
        $sql .= ' VALUES ('.$player_id.', '.$pref_id.', '.$pref_value.')';
        self::DbQuery($sql);
    }

    // Cirulla: do not declare the combination
    public function act_cirullaPass() {
        self::checkAction('cirullaPass');
        $player_id = self::getCurrentPlayerId();

        $this->gamestate->nextState('');
    }

    // Cirulla: Declare the combination
    public function act_cirullaDeclare($selected_joker_value) {
        self::checkAction('cirullaDeclare');
        $player_id = self::getCurrentPlayerId();

        [$cards, $cirullaCombination, $joker_values] = $this->getCirullaCombinations($player_id);

        if ($joker_values != []) {
            if (!in_array($selected_joker_value, $joker_values)) {
                throw new BgaUserException(self::_('This value is not possible.'));
            }
        }

        // Attribute points
        if ($cirullaCombination == 'cirulla_less_than_10') {
            $nb_points = 3;
        } elseif ($cirullaCombination == 'cirulla_three_kind') {
            $nb_points = 10;
        }
        if ($this->isTeamPlay()) {
            $this->playerWinsCirullaPoints($this->getPlayerTeam($player_id), $cirullaCombination, $nb_points, $cards);
        } else {
            $this->playerWinsCirullaPoints($player_id, $cirullaCombination, $nb_points, $cards);
        }

        if ($joker_values != []) {
            $this->setGameStateValue('cirulla_joker_value', $selected_joker_value);
            self::notifyAllPlayers(
                'message',
                clienttranslate('The 7 of cups is now a/an ${nb_points} for the purpose of capturing cards.'),
                [
                    'nb_points' => $this->values_label[$selected_joker_value],
                ]
            );
        }

        $this->gamestate->nextState('');
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    // Determine which cards can capture
    public function argPlayerTurn() {
        return [
            '_private' => [
                'active' => [
                    'cardCaptures' => self::getCardCaptures(self::getActivePlayerId())
                ]
            ]
        ];
    }

    // Determine which values can the 7 of cups take
    public function argCirullaDeclare() {
        $players = self::loadPlayersBasicInfos();
        $players_joker_values = [];
        foreach ($players as $player_id => $player) {
            [$cards, $cirullaCombination, $joker_values] = $this->getCirullaCombinations($player_id);

            if ($cirullaCombination != '') {
                $players_joker_values[$player_id]['jokerValues'] = $joker_values;
            }
        }

        return ['_private' => $players_joker_values];
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    // Deal new cards to the table from the deck
    public function stDealStart() {
        // Reset the counter of Scopa of the round
        $sql = 'UPDATE player SET scopa_in_round = 0';
        self::DbQuery($sql);
        $this->notif_playerScores();

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

        // Cirulla: dealer scores 1 or 2 points based on table hands
        if ($this->getGameStateValue('game_variant') == SCP_VARIANT_CIRULLA) {
            $dealer_id = $this->getPlayerBefore($this->getActivePlayerId());

            $seven_cups_on_table = count(array_filter($cards, function ($card) {
                return $card['type'] == 2 && $card['type_arg'] == 7;
            })) == 1;

            $sum_cards = array_sum(array_map(function ($v) {
                return $v['type_arg'];
            }, $cards));

            // If the sum is 15, the dealer captures all and wins a point
            // The 7 of cup is a joker and can take any value between 1 and 10
            // Therefore, if other cards sum up between 5 and 14, then it would win
            if ($sum_cards == 15 || ($seven_cups_on_table && ($sum_cards-7) >= 5 && ($sum_cards-7) <= 14)) {
                $this->playerWinsCirullaPoints($dealer_id, 'cirulla_dealer', 1, $cards, 15);
                $this->cards->moveAllCardsInLocation('table', 'capture', null, $dealer_id);
                self::notifyAllPlayers(
                    'playerCapturesTable',
                    '',
                    [
                        'player_id' => $dealer_id,
                    ]
                );
            }

            // Same goes with 30 points
            // If 7 of cup, then sum between 20 and 29 would work
            elseif ($sum_cards == 30 || ($seven_cups_on_table && ($sum_cards-7) >= 20 && ($sum_cards-7) <= 29)) {
                $this->playerWinsCirullaPoints($dealer_id, 'cirulla_dealer', 2, $cards, 30);
                $this->cards->moveAllCardsInLocation('table', 'capture', null, $dealer_id);
                self::notifyAllPlayers(
                    'playerCapturesTable',
                    '',
                    [
                        'player_id' => $dealer_id,
                    ]
                );
            }
        }

        // Next, deal cards to each player
        $this->gamestate->nextState();
    }

    // Deal new cards to each player from the deck
    public function stHandStart() {
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
            SCP_VARIANT_ASSO_PIGLIA_TUTTO_TRADITIONAL => 3,
            SCP_VARIANT_RE_BELLO => 3,
            SCP_VARIANT_SCOPA_A_PERDERE => 3,
            SCP_VARIANT_SCOPA_FRAC => 3,
            SCP_VARIANT_ESCOBA => 3,
            SCP_VARIANT_ESCOBA_NO_PRIME => 3,
            SCP_VARIANT_CIRULLA => 3,
        ][$this->getGameStateValue('game_variant')];
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $cards = $this->cards->pickCardsForLocation($cards_in_hand, 'deck', 'hand', $player_id, true);
            $this->notif_cardsInHand($player_id);
        }
        $this->notif_cardsCount();


        if ($this->getGameStateValue('game_variant') == SCP_VARIANT_CIRULLA) {
            $this->setGameStateValue('cirulla_joker_value', 7);
            $this->gamestate->nextState('cirullaDeclare');
        } else {
            $this->gamestate->nextState('playerTurn');
        }
    }

    // Determines whether some players can declare Cirulla combinations or not
    public function stCirullaDeclare() {
        $player_id = self::getActivePlayerId();
        [$cards, $cirullaCombination, $joker_values] = $this->getCirullaCombinations($player_id);

        if ($cirullaCombination == '') {
            $this->gamestate->nextState('');
        }
    }

    // Determines whether to distribute new cards or not
    public function stNextPlayer() {
        if (0 == $this->cards->countCardInLocation('hand')) {
            $this->gamestate->nextState('handEnd');
        } else {
            $next_player_id = self::activeNextPlayer();
            self::giveExtraTime($next_player_id);
            $next_player_cards = $this->cards->getCardsInLocation('hand', $next_player_id);

            if ($this->getGameStateValue('game_variant') == SCP_VARIANT_CIRULLA && count($next_player_cards) == 3) {
                $this->gamestate->nextState('cirullaDeclare');
            } elseif ($this->getPlayerAutoPlay($next_player_id) == SCP_PREF_AUTO_PLAY_YES) {
                $this->gamestate->nextState('autoPlayerTurn');
            } else {
                $this->gamestate->nextState('playerTurn');
            }
        }
    }

    // Plays automatically the last card in hand
    public function stAutoPlayer() {
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
    public function stHandEnd() {
        // If cards are left in the deck, distribute them
        if (0 != $this->cards->countCardInLocation('deck')) {
            $this->activeNextPlayer();
            $this->gamestate->nextState('handStart');
        } else {
            $this->gamestate->nextState('deckEnd');
        }
    }

    // Calculates each player's score based on captured cards
    public function stDeckEnd() {
        // Sends a pause to process any pending event / animation
        self::notifyAllPlayers('simplePause', '', ['time' => 2000]);

        // Give the remaining cards to one of the players (depending on option)
        if ($this->getGameStateValue('who_captures_remaining') == SCP_WHO_CAPTURES_REMAINING_CAPTURER) {
            $sql = 'SELECT player_id FROM player WHERE has_last_captured = TRUE';
            $player_last_capture = self::getCollectionFromDB($sql);
            if (1 == count($player_last_capture)) {
                $player_taking_cards = array_pop($player_last_capture)['player_id'];
            } else {
                throw new BgaVisibleSystemException(self::_('Database error - Multiple players captured last'));
            }
        } else {
            $player_taking_cards = $this->getActivePlayerId();
        }

        $cards = $this->cards->getCardsInLocation('table');
        if (count($cards) != 0) {
            $this->cards->moveAllCardsInLocation('table', 'capture', null, $player_taking_cards);

            self::notifyAllPlayers(
                'playerCapturesTable',
                clienttranslate('${player_name} captures all remaining cards:<br />${cards_display}'),
                [
                    'player_id' => $player_taking_cards,
                    'player_name' => self::getPlayerNameById($player_taking_cards),
                    'cards_display' => $this->cardsToDisplay($cards),
                ]
            );
        }

        // Notify about card counts
        $this->notif_cardsCount();

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
            'sevens_captured' => clienttranslate('Sevens captured'),
            'variant' => '',
            'napola' => clienttranslate('Napola'),
            'added_points' => clienttranslate('Points won this round'),
            'final_score' => clienttranslate('Final score'),
        ];
        foreach ($scoring_rows as $code => $label) {
            $score_table[$code] = array_fill_keys(array_keys($scorers), 0);
        }

        // For regular scopa, remove the "variant" row
        if (in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_SCOPA, SCP_VARIANT_SCOPONE, SCP_VARIANT_SCOPONE_SCIENTIFICO, SCP_VARIANT_SCOPA_DI_QUINDICI, SCP_VARIANT_ASSO_PIGLIA_TUTTO, SCP_VARIANT_ASSO_PIGLIA_TUTTO_TRADITIONAL])) {
            unset($scoring_rows['variant']);
        } else {
            $game_options = $this->getTableOptions();
            $variant_id = $this->getGameStateValue('game_variant');
            $scoring_rows['variant'] = $game_options[SCP_VARIANT]['values'][$variant_id]['name'];
        }
        // Hide the Napola row if that is not enabled separately
        if ($this->getGameStateValue('napola_variant') == SCP_VARIANT_NAPOLA_ENABLED_NO || $this->getGameStateValue('game_variant') == SCP_VARIANT_NAPOLA) {
            unset($scoring_rows['napola']);
        }

        // Hide the Escoba row if disabled
        if (!in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_ESCOBA, SCP_VARIANT_ESCOBA_NO_PRIME])) {
            unset($scoring_rows['sevens_captured']);
        }

        // Scoring for regular Scopa game
        $this->scoreScopa($cards, $score_table);
        // Scopa Frac: only points from variants are counted
        if ($this->getGameStateValue('game_variant') == SCP_VARIANT_SCOPA_FRAC) {
            unset($scoring_rows['scopa_number']);
            unset($scoring_rows['sette_bello']);
            unset($scoring_rows['cards_captured']);
            unset($scoring_rows['coins_captured']);
            unset($scoring_rows['prime_score']);
        } else {
            $this->scoreSetteBello($cards, $score_table);
            $this->scoreCardsCaptured($cards, $score_table);
            $this->scoreCoinsCaptured($cards, $score_table);
            if ($this->getGameStateValue('game_variant') == SCP_VARIANT_ESCOBA_NO_PRIME) {
                unset($scoring_rows['prime_score']);
            } else {
                $this->scorePrime($cards, $score_table);
            }
        }

        // Score for Napola
        if ($this->getGameStateValue('napola_variant') == SCP_VARIANT_NAPOLA_ENABLED_YES || $this->getGameStateValue('game_variant') == SCP_VARIANT_NAPOLA) {
            $this->scoreNapola($cards, $score_table);
        }

        // Score for variants
        switch ($this->getGameStateValue('game_variant')) {
            case SCP_VARIANT_IL_PONINO:
                $this->scoreIlPonino($cards, $score_table);
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

            case SCP_VARIANT_ESCOBA:
            case SCP_VARIANT_ESCOBA_NO_PRIME:
                $this->scoreEscoba($cards, $score_table);
                break;

            case SCP_VARIANT_CIRULLA:
                $this->scoreCirulla($cards, $score_table);
                break;
        }

        // Scopa frac: you can't win points that way
        if ($this->getGameStateValue('game_variant') != SCP_VARIANT_SCOPA_FRAC) {
            // Get the winners in each category (except scopa & sette bello, already counted)
            $categories = ['cards_captured', 'coins_captured', 'prime_score'];
            if (in_array($this->getGameStateValue('game_variant'), [SCP_VARIANT_ESCOBA, SCP_VARIANT_ESCOBA_NO_PRIME])) {
                $categories[] = 'sevens_captured';
            }
            if ($this->getGameStateValue('game_variant') == SCP_VARIANT_ESCOBA_NO_PRIME) {
                $categories = array_diff($categories, ['prime_score']);
            }
            foreach ($categories as $category) {
                // Array format is different than the others
                if ($category == 'prime_score') {
                    $scores = array_map(function ($el) {
                        return $el['args']['points'];
                    }, $score_table[$category]);
                    $high_score = max($scores);
                    $winners = array_filter(
                        $scores,
                        function ($val) use ($high_score) {
                            return $val == $high_score;
                        }
                    );
                } else {
                    $high_score = max($score_table[$category]);
                    $winners = array_filter(
                        $score_table[$category],
                        function ($val) use ($high_score) {
                            return $val == $high_score;
                        }
                    );
                }
                if (1 == count($winners)) {
                    // We have a unique winner!
                    $this->playerWin(array_keys($winners)[0], $category, $score_table);
                } else {
                    $this->playerTie($category);
                }
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
            // For Scopa a perdere, invert the scores so that winner has the highest one
            if ($this->getGameStateValue('game_variant') == SCP_VARIANT_SCOPA_A_PERDERE) {
                self::DbQuery('UPDATE player SET player_score = -player_score');
            }

            $this->gamestate->nextState('gameEnd');
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Scoring functions
    ////////////

    // Scores scopa points (= who sweeped the table)
    private function scoreScopa($cards, &$score_table) {
        // Scopa score is from DB directly
        if ($this->isTeamPlay()) {
            $sql = 'SELECT DISTINCT team_id, player_score score, scopa_in_round, cirulla_points FROM player';
        } else {
            $sql = 'SELECT player_id, player_score score, scopa_in_round, cirulla_points FROM player';
        }
        $values = self::getCollectionFromDb($sql);

        // Scopa points have already been added to the score, so remove them
        $score_table['previous_score'] = array_map(
            function ($v) {
                return $v['score'] - $v['scopa_in_round'] - $v['cirulla_points'];
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
    private function scoreSetteBello($cards, &$score_table) {
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
    private function scoreCardsCaptured($cards, &$score_table) {
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
    private function scoreCoinsCaptured($cards, &$score_table) {
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
    private function scorePrime($cards, &$score_table) {
        if ($this->isTeamPlay()) {
            $scorers = $this->loadTeamsBasicInfos();
        } else {
            $scorers = $this->loadPlayersBasicInfos();
        }
        $scorers_prime = array_fill_keys(array_keys($scorers), ['str' => '${points}', 'args' => ['prime_score_details' => '<br />','points' => 0]]);
        $point_per_card = $this->prime_points;
        foreach ($scorers_prime as $scorer_id => $colors) {
            $scorer_cards = array_filter($cards, function ($v) use ($scorer_id) {
                return $v['location_arg'] == $scorer_id;
            });
            foreach (['1', '2', '3', '4'] as $color_id) {
                $cards_of_color = array_filter(
                    $scorer_cards,
                    function ($card) use ($color_id) {
                        return $card['type'] == $color_id;
                    }
                );

                $prime_points = array_map(
                    function ($card) use ($point_per_card) {
                        return [
                            'card' => $card,
                            'points' => $point_per_card[$card['type_arg']]
                        ];
                    },
                    $cards_of_color
                );
                if (!empty($prime_points)) {
                    $prime_point_color = max(array_column($prime_points, 'points'));
                    $card_giving_point = array_filter($prime_points, function ($card) use ($prime_point_color) {
                        return $card['points'] == $prime_point_color;
                    });
                    $card = array_pop($card_giving_point)['card'];

                    // Prepare display of card
                    $args = [
                        'str' => clienttranslate('${value} of ${suit} : ${points} points<br />'),
                        'args' => [
                            'value' => $this->values_label[$card['type_arg']],
                            'suit' => $this->colors[$card['type']]['name'],
                            'points' => $prime_point_color,
                        ]
                    ];

                    $scorers_prime[$scorer_id]['args']['prime_score_details'] .= '${details_'.$color_id.'}';
                    $scorers_prime[$scorer_id]['args']['details_'.$color_id] = $args;
                    $scorers_prime[$scorer_id]['args']['points'] += $prime_point_color;
                }
            }
        }
        $score_table['prime_score'] = $scorers_prime;
    }

    // Scores Il ponino points (= doubles Scopa points if captured all knights)
    private function scoreIlPonino($cards, &$score_table) {
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
    private function scoreNapola($cards, &$score_table) {
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
            $this->playerWinsNapolaPoints($player_id, 'napola', $max_coin_captured, $score_table);
        }
    }

    // Scores Scopone de Trente points (= A, 2, 3 of coins are worth 1 each, having all coins = victory)
    private function scoreScoponeDeTrente($cards, &$score_table) {
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
    private function scoreReBello($cards, &$score_table) {
        $re_bello = array_filter($cards, function ($card) {
            return $card['type'] == 1 && $card['type_arg'] == 10;
        });
        $player_id = array_pop($re_bello)['location_arg'];

        $this->playerWinsVariantPoints($player_id, 're_bello', 1, $score_table);
    }

    // Scores Scopa Frac points (= J, Kn, K, A worth 1 each. If equality at 8, K of coin wins)
    private function scoreScopaFrac($cards, &$score_table) {
        $cardsWorthPoints = array_filter($cards, function ($card) {
            return in_array($card['type_arg'], [1, 8, 9, 10]);
        });

        $players = array_unique(array_map(function ($v) {
            return $v['location_arg'];
        }, $cardsWorthPoints));

        foreach ($players as $player_id) {
            $cardsCaptured = array_filter($cardsWorthPoints, function ($card) use ($player_id) {
                return $card['location_arg'] == $player_id;
            });
            $this->playerWinsVariantPoints($player_id, 'scopa_frac', count($cardsCaptured), $score_table);
        }
    }

    // Scores Escoba (= Capturing the most sevens is worth 1 point, capturing all of them is worth 2)
    private function scoreEscoba($cards, &$score_table) {
        $seven_captured = array_filter($cards, function ($card) {
            return $card['type_arg'] == 7;
        });

        $players = array_unique(array_map(function ($v) {
            return $v['location_arg'];
        }, $seven_captured));

        foreach ($players as $player_id) {
            $score_table['sevens_captured'][$player_id] = count(array_filter($seven_captured, function ($v) use ($player_id) {
                return $v['location_arg'] == $player_id;
            }));
            if ($score_table['sevens_captured'][$player_id] == 4) {
                $this->playerWinsVariantPoints($player_id, 'escoba_all_sevens', 1, $score_table);
            }
        }
    }

    // Scores Cirulla
    // Capturing all coins means an immediate win
    private function scoreCirulla($cards, &$score_table) {
        // Mark scores from the beginning of the hands
        if ($this->isTeamPlay()) {
            $sql = 'SELECT DISTINCT team_id, cirulla_points FROM player';
        } else {
            $sql = 'SELECT player_id, cirulla_points FROM player';
        }
        $values = self::getCollectionFromDb($sql, true);

        $score_table['variant'] = $values;

        foreach ($values as $player_id => $points) {
            $score_table['added_points'][$player_id] += $points;
            $score_table['final_score'][$player_id] += $points;
        }

        // Score other combinations
        $players = array_unique(array_map(function ($v) {
            return $v['location_arg'];
        }, $cards));

        // Score Picolla points: A+2+3 of coin is worth 3, A+2+3+4 is worth 4, A+2+3+4+5 is worth 5, A+2+3+4+5+6 is worth 6
        foreach ($players as $player_id) {
            $coinsCaptured = array_filter($cards, function ($v) use ($player_id) {
                return $v['location_arg'] == $player_id && $v['type'] == 1;
            });

            $coinsCaptured = array_values(array_map(function ($v) {
                return (int)$v['type_arg'];
            }, $coinsCaptured));


            // Capture all = immediate victory
            if (count($coinsCaptured) == 10) {
                $this->playerWinsVariantPoints($player_id, 'cirulla_all', 100, $score_table);
                return;
            }

            // Combinations starting with 1
            $max_coin_captured = 0;
            while (in_array($max_coin_captured+1, $coinsCaptured) && $max_coin_captured < 6) {
                $max_coin_captured++;
            }

            if ($max_coin_captured > 0) {
                break;
            }
        }

        if ($max_coin_captured > 2) {
            $this->playerWinsVariantPoints($player_id, 'cirulla_picolla', $max_coin_captured, $score_table, $max_coin_captured);
        }

        // Score la grande: J+K+Q of coin is worth 5
        foreach ($players as $player_id) {
            $coinsCaptured = array_filter($cards, function ($v) use ($player_id) {
                return $v['location_arg'] == $player_id && $v['type'] == 1;
            });

            $coinsCaptured = array_values(array_map(function ($v) {
                return (int)$v['type_arg'];
            }, $coinsCaptured));

            if (count(array_intersect([8, 9, 10], $coinsCaptured)) == 3) {
                $this->playerWinsVariantPoints($player_id, 'cirulla_grande', 5, $score_table);
                break;
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    // Zombie turn: Plays a card at random
    public function zombieTurn($state, $active_player) {
        $statename = $state['name'];

        if ($statename === 'playerTurn') {
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
        } else {
            $this->gamestate->nextState('');
            return;
        }

        throw new BgaVisibleSystemException(str_replace('${statename}', $statename, self::_('Zombie mode not supported at this game state: ${statename}')));
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

    public function upgradeTableDb($from_version) {
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
        // Added possibility to play in teams
        if ($from_version <= 2111141815) {
            $sql = 'ALTER TABLE DBPREFIX_player ADD `cirulla_points` INT NOT NULL';

            if (!self::getUniqueValueFromDB("SHOW COLUMNS FROM player LIKE 'cirulla_points'")) {
                self::applyDbUpgradeToAllDB($sql);
            }
        }
    }

    protected function getGameName() {
        // Used for translations and stuff. Please do not modify.
        return 'scopa';
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = []) {
        $sql = 'DELETE FROM player WHERE 1 ';
        self::DbQuery($sql);

        $gameinfos = self::getGameinfos();
        self::setGameStateInitialValue('cirulla_joker_value', 7);

        $default_colors = $gameinfos['player_colors'];

        // Create players
        $sql = 'INSERT INTO player (player_id, player_no, player_score, player_color, player_canal, player_name, player_avatar, team_id) VALUES ';
        $values = [];

        // Prepare team split
        $team_composition = $this->getGameStateValue('team_composition');
        $team_split = [
            SCP_TEAM_COMPOSITION_RANDOM => 0,
            SCP_TEAM_COMPOSITION_1_2 => [ 1 => 0, 2 => 0, 3 => 1, 4 => 1 ],
            SCP_TEAM_COMPOSITION_1_3 => [ 1 => 0, 3 => 0, 2 => 1, 4 => 1 ],
            SCP_TEAM_COMPOSITION_1_4 => [ 1 => 0, 4 => 0, 2 => 1, 3 => 1 ],
            SCP_TEAM_COMPOSITION_12_34_56 => [ 1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_12_35_46 => [ 1 => 0, 2 => 0, 3 => 1, 5 => 1, 4 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_12_36_45 => [ 1 => 0, 2 => 0, 3 => 1, 6 => 1, 4 => 2, 5 => 2],
            SCP_TEAM_COMPOSITION_13_24_56 => [ 1 => 0, 3 => 0, 2 => 1, 4 => 1, 5 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_13_25_46 => [ 1 => 0, 3 => 0, 2 => 1, 5 => 1, 4 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_13_26_45 => [ 1 => 0, 3 => 0, 2 => 1, 6 => 1, 4 => 2, 5 => 2],
            SCP_TEAM_COMPOSITION_14_23_56 => [ 1 => 0, 4 => 0, 2 => 1, 3 => 1, 5 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_14_25_36 => [ 1 => 0, 4 => 0, 2 => 1, 5 => 1, 3 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_14_26_35 => [ 1 => 0, 4 => 0, 2 => 1, 6 => 1, 3 => 2, 5 => 2],
            SCP_TEAM_COMPOSITION_15_23_46 => [ 1 => 0, 5 => 0, 2 => 1, 3 => 1, 4 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_15_24_36 => [ 1 => 0, 5 => 0, 2 => 1, 4 => 1, 3 => 2, 6 => 2],
            SCP_TEAM_COMPOSITION_15_26_34 => [ 1 => 0, 5 => 0, 2 => 1, 6 => 1, 3 => 2, 4 => 2],
        ][$team_composition];

        // I need to define a random dealer, since BGA won't do it before the player table is defined
        $random_dealer = bga_rand(1, count($players));

        $team_id = 0;
        $teams = [0 => [], 1 => []];
        if (count($players) == 6) {
            $teams[2] = [];
        }
        // What is the order as displayed in the lobby?
        // Undocumented column player_table_order ==> default is just based on the foreach order
        $players_order = [];
        foreach ($players as $player_id => $player) {
            if ($team_composition == SCP_TEAM_COMPOSITION_RANDOM) {
                $players_order[$player_id] = count($players_order) + 1;
            } elseif (array_key_exists('player_table_order', $player)) {
                $players_order[$player_id] = $player['player_table_order'];
            } else {
                $players_order[$player_id] = count($players_order) + 1;
            }
        }
        asort($players_order);

        foreach ($players_order as $player_id => $player_order) {
            $player = $players[$player_id];
            $color = array_shift($default_colors);

            switch ($team_composition) {
                case SCP_TEAM_COMPOSITION_RANDOM:
                    $teams[$team_id][] = $player_id;
                    $team_id ++;
                    $team_id %= (count($players) / 2);
                    // Keep player order from random BGA since we alternate teams
                    $player['player_no'] = $player_order - $random_dealer+1;
                    break;
                default:
                    $team_id = $team_split[$player_order];
                    $teams[$team_id][] = $player_id;
                    // Reorder players to ensure teams alternate
                    $new_order = $team_id+(count($teams[$team_id])-1)*count($teams);
                    $player['player_no'] = $new_order - $random_dealer+1;
            }
            if ($player['player_no'] < 0) {
                $player['player_no'] += count($players);
            }

            //$team_id+1 so that the first team is team 1 (easier for player + consistent with JS)
            $values[] = '("'.$player_id.'",'.$player['player_no'].',0,"'.$color.'","'.$player['player_canal'].'","'.addslashes($player['player_name']).'","'.addslashes($player['player_avatar']).'", '.($team_id+1).')';
        }
        $sql .= implode(', ', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);

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
    protected function getAllDatas() {
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
