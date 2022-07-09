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
 * tictacmatch.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in tictacmatch_tictacmatch.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

  require_once( APP_BASE_PATH."view/common/game.view.php" );

  class view_tictacmatch_tictacmatch extends game_view
  {
    function getGameName() {
        return "tictacmatch";
    }
  	function build_page( $viewArgs )
  	{
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $this->page->begin_block( "tictacmatch_tictacmatch", "boardgrid" );
        for ($i = 0; $i < 9; $i++) {
          $this->page->insert_block("boardgrid", array('i' => $i));
        }

        $this->tpl['DECK'] = self::_("Deck");
        $this->tpl['DISCARD'] = self::_("Discard");
        $this->tpl['TEAM'] = self::_("Team");
        $this->tpl['MY_HAND'] = self::_("My Hand");

        /*********** Do not change anything below this line  ************/
  	}
  }
