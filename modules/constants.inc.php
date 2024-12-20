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

if (!defined('SCP_VARIANT_SCOPA')) {
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
    define('SCP_VARIANT_ESCOBA_NO_PRIME', 14);

    define('SCP_VARIANT_NAPOLA_ENABLED', 104);
    define('SCP_VARIANT_NAPOLA_ENABLED_YES', 1);
    define('SCP_VARIANT_NAPOLA_ENABLED_NO', 2);

    define('SCP_TEAM_PLAY', 103);
    define('SCP_TEAM_PLAY_YES', 1);
    define('SCP_TEAM_PLAY_NO', 2);

    define('SCP_WHO_CAPTURES_REMAINING', 105);
    define('SCP_WHO_CAPTURES_REMAINING_CAPTURER', 1);
    define('SCP_WHO_CAPTURES_REMAINING_DEALER', 2);

    define('SCP_MULTIPLE_CAPTURES', 106);
    define('SCP_MULTIPLE_CAPTURES_ALLOW_LOWEST', 1);
    define('SCP_MULTIPLE_CAPTURES_ALLOW_ALL', 2);
    define('SCP_MULTIPLE_CAPTURES_ALLOW_ALL_EXCEPT_SINGLE', 3);

    define('SCP_TEAM_COMPOSITION', 107);
    define('SCP_TEAM_COMPOSITION_RANDOM', 1);
    define('SCP_TEAM_COMPOSITION_1_2', 12);
    define('SCP_TEAM_COMPOSITION_1_3', 13);
    define('SCP_TEAM_COMPOSITION_1_4', 14);
    define('SCP_TEAM_COMPOSITION_12_34_56', 1234);
    define('SCP_TEAM_COMPOSITION_12_35_46', 1235);
    define('SCP_TEAM_COMPOSITION_12_36_45', 1236);
    define('SCP_TEAM_COMPOSITION_13_24_56', 1324);
    define('SCP_TEAM_COMPOSITION_13_25_46', 1325);
    define('SCP_TEAM_COMPOSITION_13_26_45', 1326);
    define('SCP_TEAM_COMPOSITION_14_23_56', 1423);
    define('SCP_TEAM_COMPOSITION_14_25_36', 1425);
    define('SCP_TEAM_COMPOSITION_14_26_35', 1426);
    define('SCP_TEAM_COMPOSITION_15_23_46', 1523);
    define('SCP_TEAM_COMPOSITION_15_24_36', 1524);
    define('SCP_TEAM_COMPOSITION_15_26_34', 1526);


    define('SCP_PREF_DISPLAY_LABELS', 100);
    define('SCP_PREF_DISPLAY_LABELS_YES', 1);
    define('SCP_PREF_DISPLAY_LABELS_NO', 2);

    define('SCP_PREF_CARD_DECK', 101);
    define('SCP_PREF_CARD_DECK_NAPOLITAN', 1);
    define('SCP_PREF_CARD_DECK_STANDARD', 2);
    define('SCP_PREF_CARD_DECK_BERGAMASCHE', 3);
    define('SCP_PREF_CARD_DECK_BRESCIANE', 4);
    define('SCP_PREF_CARD_DECK_PIACENTINE', 5);
    define('SCP_PREF_CARD_DECK_SPANISH', 6);

    define('SCP_PREF_AUTO_PLAY', 102);
    define('SCP_PREF_AUTO_PLAY_YES', 1);
    define('SCP_PREF_AUTO_PLAY_NO', 2);

    define('SCP_PREF_DISPLAY_PLAYERS', 103);
    define('SCP_PREF_DISPLAY_PLAYERS_YES', 1);
    define('SCP_PREF_DISPLAY_PLAYERS_NO', 2);

    define('SCP_PREF_DISPLAY_NOTICE', 104);
    define('SCP_PREF_DISPLAY_NOTICE_YES', 1);
    define('SCP_PREF_DISPLAY_NOTICE_NO', 2);

    define('SCP_PREF_ANIMATION_SPEED', 105);
    define('SCP_PREF_ANIMATION_SPEED_1', 1);
    define('SCP_PREF_ANIMATION_SPEED_2', 2);
    define('SCP_PREF_ANIMATION_SPEED_25', 25);

    define('SCP_PREF_DISPLAY_ORDER', 106);
    define('SCP_PREF_DISPLAY_ORDER_BY_SUIT', 1);
    define('SCP_PREF_DISPLAY_ORDER_BY_NUMBER', 2);


    define('SCP_GLOBAL_CIRULLA_JOKER_VALUE', 10);
}
