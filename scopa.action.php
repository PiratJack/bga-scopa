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
class action_scopa extends APP_GameAction {
    public function __default() {
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
    public function playCard() {
        self::setAjaxMode();

        // Retrieve arguments
        $card_id = self::getArg('cardId', AT_posint, true);
        $cards_captured = self::getArg('cardsCaptured', AT_numberlist, true);

        $cards_captured = '' == $cards_captured ? [] : explode(',', $cards_captured);

        $this->game->playCard($card_id, $cards_captured);

        self::ajaxResponse();
    }

    /**
     * Player declare a Cirulla combination
     */
    public function cirullaDeclare() {
        self::setAjaxMode();

        // Retrieve arguments
        $joker_value = self::getArg('jokerValue', AT_posint, false, 0);

        $this->game->act_cirullaDeclare($joker_value);

        self::ajaxResponse();
    }

    /**
     * Player declare a Cirulla combination
     */
    public function cirullaPass() {
        self::setAjaxMode();

        $this->game->act_cirullaPass();

        self::ajaxResponse();
    }

    /**
     * Player changes display preferences
     */
    public function setUserPref() {
        self::setAjaxMode();

        // Retrieve arguments
        $pref_id = self::getArg('pref_id', AT_posint);
        $pref_value = self::getArg('pref_value', AT_posint);

        // Check the value is correct
        switch ($pref_id) {
            case SCP_PREF_AUTO_PLAY:
                if (!in_array($pref_value, [SCP_PREF_AUTO_PLAY_YES,SCP_PREF_AUTO_PLAY_NO])) {
                    throw new BgaUserException(str_replace('${pref_id}', $pref_id, self::_('Wrong value for user preference ${pref_id}')));
                }
                break;
        }

        $this->game->setUserPref($pref_id, $pref_value);

        self::ajaxResponse();
    }
}
