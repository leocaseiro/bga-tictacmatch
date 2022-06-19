{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- tictacmatchleocaseiro implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    tictacmatchleocaseiro_tictacmatchleocaseiro.tpl

    This is the HTML template of your game.

    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.

    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format

    See your "view" PHP file to check how to set variables and control blocks

    Please REMOVE this comment before publishing your game on BGA
-->


<div class="ttm-game">
    <div class="ttm-table">
        <div class="ttm-deck whiteblock">
            <h3 class="ttm-title">Deck</h3>
            <div class="card card--back"></div>
            <span class="card-badge" title="20x">20x</span>
        </div>

        <div class="ttm-board-grid">
            <div class="card card--blue_o"></div>
            <div class="card card--empty"></div>
            <div class="card card--empty"></div>
            <div class="card card--empty"></div>
            <div class="card card--green_o"></div>
            <div class="card card--red_x"></div>
            <div class="card card--yellow_o"></div>
            <div class="card card--blue_x"></div>
            <div class="card card--empty"></div>
        </div>

        <div class="ttm-discard-pile whiteblock">
            <h3 class="ttm-title"><span class="translate">Discard</span></h3>
            <div class="card card--action_2plus"></div>
            <span class="card-badge" title="4x">4x</span>
        </div>
    </div>

    <div class="ttm-hand">
        <div class="ttm-hand__team whiteblock">
            <h3 class="ttm-title"><span class="translate">Team</span></h3>
            <div class="card card--team_x"></div>
        </div>
        <div class="ttm-hand__cards-wrapper whiteblock">
            <h3 class="ttm-title">Hand</h3>
            <div class="ttm-hand__cards">
                <div class="card card--blue_o"></div>
                <div class="card card--action_wipe_out"></div>
                <div class="card card--yellow_o"></div>
                <div class="card card--action_flip"></div>
            </div>
        </div>
        <div class="ttm-hand__placeholder"></div>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

</script>

{OVERALL_GAME_FOOTER}
