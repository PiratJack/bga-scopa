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

    <div id="tablehand" class="scp_cardholder whiteblock">
        <h3>
            {CARDS_ON_TABLE}
        </h3>
        <div id="tablehandcards">
        </div>
    </div>

    <div id="interactions">
        <div id="cardplayed" class="scp_cardholder whiteblock">
            <h3>
                {CARD_PLAYED}
            </h3>
            <div id="cardplayedcard" class="scp_card">
            </div>
        </div>

        <div id="deck" class="scp_cardholder whiteblock">
            <h3>
                {DECK}
            </h3>
            <div id="deckcard" class="scp_deck">
            </div>
        </div>
    </div>

    <div id="capturechoice" class="scp_cardholder whiteblock">
        <h3>
            {CHOOSE_CAPTURE}
        </h3>
        <div class="capturechoiceholder" id="capturechoiceholder">
        </div>
    </div>

    <div id="myhand" class="scp_cardholder whiteblock">
        <h3>{MY_HAND}</h3>
        <div id="myhandcards">
        </div>
    </div>

</div>


<script type="text/javascript">
    var jstpl_card = '<div class="scp_card ${deck_class}" style="background-position:-${x}px -${y}px" id="card_${card_id}" data-card="${card_id}"></div>';

    var jstpl_capturegroup = '<div class="scp_capturegroup" id="capturegroup_${id}" data-cards="${cards_ids}"></div>';

    var jstpl_player_board = '<div class="scp_deck" id="cp_deck_${player_id}">${card_count}</div><div class="scp_team" id="cp_team_${player_id}">${team_id}</div>';

    var jstpl_card_label = '<div class="scp_card_label ${added_classes}">${card_label}</div>';
</script>


{OVERALL_GAME_FOOTER}