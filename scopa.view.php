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

class view_scopa_scopa extends game_view
{
    public function getGameName()
    {
        return 'scopa';
    }

    public function build_page($viewArgs)
    {
        $this->tpl['CARDS_ON_TABLE'] = self::_('Cards on table');
        $this->tpl['MY_HAND'] = self::_('My hand');
        $this->tpl['CARD_PLAYED'] = self::_('Card played');
        $this->tpl['DECK'] = self::_('Deck');
        $this->tpl['CHOOSE_CAPTURE'] = self::_('Choose what to capture');



        $players = $this->game->loadPlayersBasicInfosWithTeam();
        $seats = ['left', 'top_left', 'right', 'top_right', 'bottom_right'];
        $this->page->begin_block("scopa_scopa", "seat");
        $this->page->begin_block("scopa_scopa", "seat_bottom_right");
        foreach ($seats as $seat) {
            $seat_tpl = $seat == 'bottom_right'?'seat_bottom_right':'seat';
            $player = array_filter($players, function ($v) use ($seat) {
                return isset($v['seat_position']) && $v['seat_position'] == $seat;
            });

            if (count($player) == 1) {
                $player = array_pop($player);
                $this->page->insert_block($seat_tpl, [
                    'seat_position' => $seat,
                    'style' => 'color: #'.$player['player_color'],
                    'player_id' => $player['player_id'],
                    'player_name' => $player['player_name'],
                ]);
            } else {
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
