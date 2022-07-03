<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * tictacmatchleocaseiro implementation : © Leo Caseiro <leo@leocaseiro.com>
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
    'type' => 'symbol',
    'type_arg' => 0,
    'nbr' => 6,
    'class' => 'blue_x',
    'color' => 'blue',
    'value' => 'X',
    'label' => clienttranslate('Blue X'),
    'colorLabel' => clienttranslate('Blue')
  ],
  1 => [
    'type' => 'symbol',
    'type_arg' => 1,
    'nbr' => 6,
    'class' => 'blue_o',
    'color' => 'blue',
    'value' => 'O',
    'label' => clienttranslate('Blue 0'),
    'colorLabel' => clienttranslate('Blue')
  ],
  2 => [
    'type' => 'symbol',
    'type_arg' => 2,
    'nbr' => 6,
    'class' => 'green_x',
    'color' => 'green',
    'value' => 'X',
    'label' => clienttranslate('Green X'),
    'colorLabel' => clienttranslate('Green')
  ],
  3 => [
    'type' => 'symbol',
    'type_arg' => 3,
    'nbr' => 6,
    'class' => 'green_o',
    'color' => 'green',
    'value' => 'O',
    'label' => clienttranslate('Green 0'),
    'colorLabel' => clienttranslate('Green')
  ],
  4 => [
    'type' => 'symbol',
    'type_arg' => 4,
    'nbr' => 6,
    'class' => 'red_x',
    'color' => 'red',
    'value' => 'X',
    'label' => clienttranslate('Red X'),
    'colorLabel' => clienttranslate('Red')
  ],
  5 => [
    'type' => 'symbol',
    'type_arg' => 5,
    'nbr' => 6,
    'class' => 'red_o',
    'color' => 'red',
    'value' => 'O',
    'label' => clienttranslate('Red 0'),
    'colorLabel' => clienttranslate('Red')
  ],
  6 => [
    'type' => 'symbol',
    'type_arg' => 6,
    'nbr' => 6,
    'class' => 'yellow_x',
    'color' => 'yellow',
    'value' => 'X',
    'label' => clienttranslate('Yellow X'),
    'colorLabel' => clienttranslate('Yellow')
  ],
  7 => [
    'type' => 'symbol',
    'type_arg' => 7,
    'nbr' => 6,
    'class' => 'yellow_o',
    'color' => 'yellow',
    'value' => 'O',
    'label' => clienttranslate('Yellow 0'),
    'colorLabel' => clienttranslate('Yellow')
  ],
  8 => [
    'type' => 'action',
    'type_arg' => 8,
    'nbr' => 4,
    'class' => 'action_2plus',
    'color' => 'action',
    'value' => 'double_play_card',
    'label' => clienttranslate('Double Play Card')
  ],
  9 => [
    'type' => 'action',
    'type_arg' => 9,
    'nbr' => 4,
    'class' => 'action_flip',
    'color' => 'action',
    'value' => 'flip_card',
    'label' => clienttranslate('Flip Card')
  ],
  10 => [
    'type' => 'action',
    'type_arg' => 10,
    'nbr' => 4,
    'class' => 'action_wipe_out',
    'color' => 'action',
    'value' => 'wipe_out_card',
    'label' => clienttranslate('Wipe Out Card')
  ],
];
