<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * tictacmatch implementation : © Leo Caseiro <leo@leocaseiro.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * tictacmatch game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in tictacmatch.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(
    100 => array(
        'name' => totranslate( 'Teams' ),
        'values' => array(
                1 => array( 'name' => totranslate( 'By table order (1rst/3rd versus 2nd/4th)' )),
                2 => array( 'name' => totranslate( 'By table order (1rst/2nd versus 3rd/4th)' )),
                3 => array( 'name' => totranslate( 'By table order (1rst/4th versus 2nd/3rd)' )),
                4 => array( 'name' => totranslate( 'At random' ) ),
        ),
        'default' => 4
    ),
);

$game_preferences = array();
