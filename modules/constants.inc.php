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
}
