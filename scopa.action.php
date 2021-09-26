<?php
/**
 * ------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * scopa implementation : © Jacques de Metz <demetz.jacques@gmail.com>.
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 */
class action_scopa extends APP_GameAction
{
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = 'common_notifwindow';
            $this->viewArgs['table'] = self::getArg('table', AT_posint, true);
        } else {
            $this->view = 'scopa_scopa';
            self::trace('Complete reinitialization of board game');
        }
    }

    /**
     * Player chooses a card (+ a capture option if relevant).
     */
    public function playCard()
    {
        self::setAjaxMode();

        // Retrieve arguments
        $card_id = self::getArg('cardId', AT_posint, true);
        $cards_captured = self::getArg('cardsCaptured', AT_numberlist, true);

        $cards_captured = '' == $cards_captured ? [] : explode(',', $cards_captured);

        $this->game->playCard($card_id, $cards_captured);

        self::ajaxResponse();
    }
}
