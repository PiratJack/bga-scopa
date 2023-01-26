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
require_once APP_BASE_PATH.'view/common/game.view.php';

class view_scopa_scopa extends game_view {
    public function getGameName() {
        return 'scopa';
    }

    public function build_page($viewArgs) {
        // Various words on board
        $this->tpl['CARDS_ON_TABLE'] = self::_('Cards on table');
        $this->tpl['MY_HAND'] = self::_('My hand');
        $this->tpl['CARD_PLAYED'] = self::_('Card played');
        $this->tpl['DECK'] = self::_('Deck');
        $this->tpl['CHOOSE_CAPTURE'] = self::_('Choose what to capture');

        // Notice at the top of the board
        $game_options = $this->game->getTableOptions();
        $variant = $this->game->getGameStateValue('game_variant');
        $variant_data = $game_options[SCP_VARIANT]['values'][$variant];
        $this->tpl['VARIANT_MESSAGE'] = str_replace('${variant_name}', $variant_data['name'], self::_('You are playing a game of ${variant_name}. '));
        if ($variant != SCP_VARIANT_SCOPA)
        {
            $this->tpl['VARIANT_MESSAGE'] .= str_replace('${variant_rules}', $variant_data['description'], self::_('Special rules: ${variant_rules}'));
        }

        // Napola notice at top of the board
        $napola_value = $this->game->getGameStateValue('napola_variant');
        if ($napola_value == SCP_VARIANT_NAPOLA_ENABLED_YES)
        {
            $napola_infos = $game_options[SCP_VARIANT_NAPOLA_ENABLED]['values'][$napola_value];
            $this->tpl['NAPOLA_MESSAGE'] = str_replace('${rules}', $napola_infos['description'], self::_('The Napola variant is enabled. ${rules}'));
        }
        else
        {
            $this->tpl['NAPOLA_MESSAGE'] = '';
        }

        // Final notice at top of the board
        $this->tpl['HIDE_MESSAGE'] = self::_('Hide this message in the options (bottom of screen or top right hamburger menu)');
        $this->tpl['OPTIONS_MESSAGE'] = self::_('You may change card types or hide their labels at the same place.');

        // Player seats & names
        $players = $this->game->loadPlayersBasicInfosWithTeam();
        $seats = ['left', 'top_left', 'right', 'top_right', 'bottom_right', 'bottom_left'];
        $this->page->begin_block("scopa_scopa", "seat");
        $this->page->begin_block("scopa_scopa", "seat_bottom");
        foreach ($seats as $seat)
        {
            $seat_tpl = in_array($seat, ['bottom_right', 'bottom_left'])?'seat_bottom':'seat';
            $player = array_filter($players, function ($v) use ($seat) {
                return isset($v['seat_position']) && $v['seat_position'] == $seat;
            });

            if (count($player) == 1)
            {
                $player = array_pop($player);
                $this->page->insert_block($seat_tpl, [
                    'seat_position' => $seat,
                    'style' => 'color: #'.$player['player_color'],
                    'player_id' => $player['player_id'],
                    'player_name' => $player['player_name'],
                ]);
            }
            else
            {
                $this->page->insert_block($seat_tpl, [
                    'seat_position' => $seat,
                    'style' => 'visibility: hidden;',
                    'player_id' => '',
                    'player_name' => '',
                ]);
            }
        }
    }
}
