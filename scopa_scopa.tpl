{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- scopa implementation : © Jacques de Metz <demetz.jacques@gmail.com>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->
<div id="scp_board">

    <div class="whiteblock scp_notice" id="scp_notice">
        {VARIANT_MESSAGE}<br />
        {NAPOLA_MESSAGE}<br />
        <i>{HIDE_MESSAGE}</i><br />
        <i>{OPTIONS_MESSAGE}</i>
    </div>

    <!-- BEGIN seat -->
    <div class="whiteblock scp_player_seat scp_player_seat_{seat_position}" style="{style}" id="scp_player_seat_{player_id}">
        <h3>{player_name}</h3>
        <div id="scp_player_seat_{player_id}_cards" class="scp_deck"></div>
    </div>
    <!-- END seat -->


    <div id="scp_deck" class="whiteblock">
        <h3>
            {DECK}
        </h3>
        <div id="scp_deckcard" class="scp_deck">
        </div>
    </div>

    <div id="scp_cardplayed" class="whiteblock">
        <h3>
            {CARD_PLAYED}
        </h3>
        <div id="scp_cardplayedcard" class="scp_card">
        </div>
    </div>

    <div id="scp_table" class="whiteblock">
        <h3>
            {CARDS_ON_TABLE}
        </h3>
        <div id="scp_tablehandcards">
        </div>
    </div>

    <!-- BEGIN seat_bottom -->
    <div class="whiteblock scp_player_seat scp_player_seat_{seat_position}" style="{style}" id="scp_player_seat_{player_id}">
        <h3>{player_name}</h3>
        <div id="scp_player_seat_{player_id}_cards" class="scp_deck"></div>
    </div>
    <!-- END seat_bottom -->

    <div id="scp_capturechoice" class="whiteblock">
        <h3>
            {CHOOSE_CAPTURE}
        </h3>
        <div id="scp_capturechoiceholder">
        </div>
    </div>

    <div id="scp_myhand" class="whiteblock">
        <h3>{MY_HAND}</h3>
        <div id="scp_myhandcards">
        </div>
    </div>

</div>


<script type="text/javascript">
    var jstpl_card = '<div class="scp_card ${deck_class}" style="background-position:-${x}px -${y}px" id="card_${card_id}" data-card="${card_id}"><div class="scp_card_label ${added_classes}">${card_label}</div></div>';

    var jstpl_capturegroup = '<div class="scp_capturegroup" id="capturegroup_${id}" data-cards="${cards_ids}"></div>';

    var jstpl_player_board = '<div class="scp_deck" id="cp_deck_${player_id1}">${card_count}</div><div class="scp_scopa_points scp_deck" id="scp_scopa_points_${player_id2}">${scopa_in_round}</div><div class="scp_team" id="cp_team_${player_id3}">${ally_name}</div>';

    var jstpl_card_label = '<div class="scp_card_label ${added_classes}">${card_label}</div>';
</script>


{OVERALL_GAME_FOOTER}
