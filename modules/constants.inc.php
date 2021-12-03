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

if (!defined('SCP_VARIANT_SCOPA'))
{
    define('SCP_OPTION_POINTS_TO_WIN', 100);
    define('SCP_OPTION_POINTS_TO_WIN_11', 1);
    define('SCP_OPTION_POINTS_TO_WIN_16', 2);
    define('SCP_OPTION_POINTS_TO_WIN_21', 3);
    define('SCP_OPTION_POINTS_TO_WIN_31', 4);
    define('SCP_OPTION_POINTS_TO_WIN_51', 5);

    define('SCP_OPTION_MAX_CAPTURE', 101);
    define('SCP_OPTION_MAX_CAPTURE_2', 1);
    define('SCP_OPTION_MAX_CAPTURE_ANY', 2);

    define('SCP_VARIANT', 102);
    define('SCP_VARIANT_SCOPA', 0);
    define('SCP_VARIANT_IL_PONINO', 1);
    define('SCP_VARIANT_NAPOLA', 2);
    define('SCP_VARIANT_SCOPONE', 3);
    define('SCP_VARIANT_SCOPONE_SCIENTIFICO', 4);
    define('SCP_VARIANT_SCOPA_DI_QUINDICI', 5);
    define('SCP_VARIANT_SCOPONE_DE_TRENTE', 6);
    define('SCP_VARIANT_ASSO_PIGLIA_TUTTO', 7);
    define('SCP_VARIANT_RE_BELLO', 8);
    define('SCP_VARIANT_SCOPA_A_PERDERE', 9);
    define('SCP_VARIANT_SCOPA_FRAC', 10);
    define('SCP_VARIANT_ASSO_PIGLIA_TUTTO_TRADITIONAL', 11);
    define('SCP_VARIANT_ESCOBA', 12);
    define('SCP_VARIANT_CIRULLA', 13);

    define('SCP_VARIANT_NAPOLA_ENABLED', 104);
    define('SCP_VARIANT_NAPOLA_ENABLED_YES', 1);
    define('SCP_VARIANT_NAPOLA_ENABLED_NO', 2);

    define('SCP_TEAM_PLAY', 103);
    define('SCP_TEAM_PLAY_YES', 1);
    define('SCP_TEAM_PLAY_NO', 2);



    define('SCP_PREF_DISPLAY_LABELS', 100);
    define('SCP_PREF_DISPLAY_LABELS_YES', 1);
    define('SCP_PREF_DISPLAY_LABELS_NO', 2);

    define('SCP_PREF_CARD_DECK', 101);
    define('SCP_PREF_CARD_DECK_NAPOLITAN', 1);
    define('SCP_PREF_CARD_DECK_STANDARD', 2);
    define('SCP_PREF_CARD_DECK_BERGAMASCHE', 3);
    define('SCP_PREF_CARD_DECK_BRESCIANE', 4);
    define('SCP_PREF_CARD_DECK_PIACENTINE', 5);


    define('SCP_PREF_AUTO_PLAY', 102);
    define('SCP_PREF_AUTO_PLAY_YES', 1);
    define('SCP_PREF_AUTO_PLAY_NO', 2);

    define('SCP_PREF_DISPLAY_PLAYERS', 103);
    define('SCP_PREF_DISPLAY_PLAYERS_YES', 1);
    define('SCP_PREF_DISPLAY_PLAYERS_NO', 2);


    define('SCP_GLOBAL_CIRULLA_JOKER_VALUE', 10);
}
