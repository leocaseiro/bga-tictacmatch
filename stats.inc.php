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
 * stats.inc.php
 *
 * tictacmatch game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.

    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")

    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean

    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.

    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress

    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players

*/

$common_stats = array(
    "turns_number" => array(
        "id"=> 10,
        "name" => clienttranslate("Number of turns"),
        "type" => "int"
    ),
    "cards_played" => array(
        "id"=> 11,
        "name" => clienttranslate("Number of cards played"),
        "type" => "int"
    ),
    "symbol_cards_on_empty_space" => array(
        "id"=> 12,
        "name" => clienttranslate("Number of symbol cards placed on empty spaces"),
        "type" => "int"
    ),
    "blue_cards" => array(
        "id"=> 13,
        "name" => clienttranslate("Number of blue cards played"),
        "type" => "int"
    ),
    "green_cards" => array(
        "id"=> 14,
        "name" => clienttranslate("Number of green cards played"),
        "type" => "int"
    ),
    "red_cards" => array(
        "id"=> 15,
        "name" => clienttranslate("Number of red cards played"),
        "type" => "int"
    ),
    "yellow_cards" => array(
        "id"=> 16,
        "name" => clienttranslate("Number of yellow cards played"),
        "type" => "int"
    ),
    "x_cards" => array(
        "id"=> 17,
        "name" => clienttranslate("Number of 'X' cards played"),
        "type" => "int"
    ),
    "o_cards" => array(
        "id"=> 18,
        "name" => clienttranslate("Number of '0' cards played"),
        "type" => "int"
    ),
    "symbol_cards_value_replaced" => array(
        "id"=> 19,
        "name" => clienttranslate("Number of symbol cards replaced with different value"),
        "type" => "int"
    ),
    "symbol_cards_color_replaced" => array(
        "id"=> 20,
        "name" => clienttranslate("Number of symbol cards replaced with different color"),
        "type" => "int"
    ),
    "flip_cards_played" => array(
        "id"=> 21,
        "name" => clienttranslate("Number of Flip cards played"),
        "type" => "int"
    ),
    "wipe_out_cards_played" => array(
        "id"=> 22,
        "name" => clienttranslate("Number of Wipe Out cards played"),
        "type" => "int"
    ),
    "double_play_cards_played" => array(
        "id"=> 23,
        "name" => clienttranslate("Number of Double Play cards played"),
        "type" => "int"
    ),
);

$stats_type = array(
    // Statistics global to table
    "table" => $common_stats + array(
        "reshuffle_draw_pile" => array(
            "id"=> 24,
            "name" => clienttranslate("Number of times that draw pile was reshuffled"),
            "type" => "int"
        ),
    ),

    // Statistics existing for each player
    "player" => $common_stats + array(
        "wiped_out_cards_player" => array(
            "id"=> 25,
            "name" => clienttranslate("Number of times player had cards wiped out"),
            "type" => "int"
        ),
    ),

    "value_labels" => array(
		24 => array(
			0 => clienttranslate("None"),
        ),
    )

);
