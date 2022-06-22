<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * tictacmatchleocaseiro implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * tictacmatchleocaseiro game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->ttm_cards = [
  0 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 0,
    'nbr' => 6,
    'class' => 'blue_x',
    'color' => clienttranslate('blue'),
    'value' => 'X'
  ],
  1 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 1,
    'nbr' => 6,
    'class' => 'blue_o',
    'color' => clienttranslate('blue'),
    'value' => 'O'
  ],
  2 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 2,
    'nbr' => 6,
    'class' => 'green_x',
    'color' => clienttranslate('green'),
    'value' => 'X'
  ],
  3 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 3,
    'nbr' => 6,
    'class' => 'green_o',
    'color' => clienttranslate('green'),
    'value' => 'O'
  ],
  4 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 4,
    'nbr' => 6,
    'class' => 'red_x',
    'color' => clienttranslate('red'),
    'value' => 'X'
  ],
  5 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 5,
    'nbr' => 6,
    'class' => 'red_o',
    'color' => clienttranslate('red'),
    'value' => 'O'
  ],
  6 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 6,
    'nbr' => 6,
    'class' => 'yellow_x',
    'color' => clienttranslate('yellow'),
    'value' => 'X'
  ],
  7 => [
    'type' => clienttranslate('symbol'),
    'type_arg' => 7,
    'nbr' => 6,
    'class' => 'yellow_o',
    'color' => clienttranslate('yellow'),
    'value' => 'O'
  ],
  8 => [
    'type' => clienttranslate('action'),
    'type_arg' => 8,
    'nbr' => 4,
    'class' => 'action_flip',
    'color' => clienttranslate('action'),
    'value' => clienttranslate('double play card')
  ],
  9 => [
    'type' => clienttranslate('action'),
    'type_arg' => 9,
    'nbr' => 4,
    'class' => 'action_2plus',
    'color' => clienttranslate('action'),
    'value' => clienttranslate('flip card')
  ],
  10 => [
    'type' => clienttranslate('action'),
    'type_arg' => 10,
    'nbr' => 4,
    'class' => 'action_wipe_out',
    'color' => clienttranslate('action'),
    'value' => clienttranslate('wipe out card')
  ],
];
/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);
$this->card = array(
   0 => array(
     'cardtype' => FRUIT,
     'subtype' => ACAI_BERRY,
     'count' => 6
   ),
*/
