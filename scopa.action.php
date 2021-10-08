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

    /**
     * Player changes display preferences
     */
    public function setUserPref()
    {
        self::setAjaxMode();

        $preferences = [
            'display_card_labels'=> AT_posint,
            'card_deck'=> AT_alphanum,
            ];

        // Retrieve arguments
        $user_preferences = [];
        foreach ($preferences as $pref_id => $format) {
            $val = self::getArg($pref_id, $format);
            if ($val != '') {
                $user_preferences[$pref_id] = $val;
            }
        }

        // Filter values for card deck
        if (array_key_exists('card_deck', $user_preferences)) {
            if (!in_array($user_preferences['card_deck'], ['standard', 'italian'])) {
                $user_preferences['card_deck'] = 'italian';
            }
        }

        $this->game->setUserPref($user_preferences);

        self::ajaxResponse();
    }
}
