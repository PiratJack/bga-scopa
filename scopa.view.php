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
      }
  }
