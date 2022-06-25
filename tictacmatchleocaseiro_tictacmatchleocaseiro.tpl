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
            <div id="js-deck" class="card card--back"></div>
            <span id="js-deck-badge" class="card-badge" title="20x">20x</span>
        </div>

        <div class="ttm-board-grid">
            <!-- BEGIN boardgrid -->
            <div id="js-board-cell--{i}" data-cell={i} class="card card--empty"></div>
            <!-- END boardgrid -->
        </div>

        <div class="ttm-discard-pile whiteblock">
            <h3 class="ttm-title"><span class="translate">Discard</span></h3>
            <div id="js-discard-pile-card" class="card card--empty"></div>
            <span id="js-discard-pile-badge" class="card-badge" title="4x">4x</span>
        </div>
    </div>

    <div class="ttm-hand">
        <div class="ttm-hand__team whiteblock">
            <h3 class="ttm-title"><span class="translate">Team</span></h3>
            <div id="js-team-card" class="card card--empty"></div>
        </div>
        <div class="ttm-hand__cards-wrapper whiteblock">
            <h3 class="ttm-title">Hand</h3>
            <div id="js-hand__cards" class="ttm-hand__cards">

            </div>
        </div>
        <div class="ttm-hand__placeholder"></div>
    </div>
</div>

<script type="text/javascript">

// Javascript HTML templates

var jstpl_card='<div id="${ID}" data-id="${DATAID}" class="card card--${CLASS}" data-value="${CARDVALUE}" data-color="${COLOR}"></div>';
</script>

{OVERALL_GAME_FOOTER}
