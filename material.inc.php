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

$this->cards = [
  0 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('blue'),
    'value' => 'X',
    'nbr' => 6
  ],
  1 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('blue'),
    'value' => 'O',
    'nbr' => 6
  ],
  2 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('green'),
    'value' => 'X',
    'nbr' => 6
  ],
  3 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('green'),
    'value' => 'O',
    'nbr' => 6
  ],
  4 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('red'),
    'value' => 'X',
    'nbr' => 6
  ],
  5 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('red'),
    'value' => 'O',
    'nbr' => 6
  ],
  6 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('yellow'),
    'value' => 'X',
    'nbr' => 6
  ],
  7 => [
    'type' => clienttranslate('symbol'),
    'color' => clienttranslate('yellow'),
    'value' => 'O',
    'nbr' => 6
  ],
  8 => [
    'type' => clienttranslate('action'),
    'value' => clienttranslate('double play card'),
    'nbr' => 4
  ],
  9 => [
    'type' => clienttranslate('action'),
    'value' => clienttranslate('flip card'),
    'nbr' => 4
  ],
  10 => [
    'type' => clienttranslate('action'),
    'value' => clienttranslate('wipe out card'),
    'nbr' => 4
  ],
]
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
