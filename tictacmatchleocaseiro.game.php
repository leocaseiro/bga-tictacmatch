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
  * tictacmatchleocaseiro.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );
class tictacmatchleocaseiro extends Table
{
	// Team pairing constants  (ripped off from Coinche)
	const TEAM_1_3 = 1;
	const TEAM_1_2 = 2;
	const TEAM_1_4 = 3;
	const TEAM_RANDOM = 4;

    const TEAM_EVEN = 'evens_team';
    const TEAM_ODD = 'odds_team';
    const TEAM_O = 0;
    const TEAM_X = 10;
    const TEAM_X_STRING = 'X';
    const TEAM_O_STRING = 'O';

    const HAS_WINNER = 'has_winner';


	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array(
            'playerTeams' => 100,
            self::TEAM_ODD => 101,
            self::TEAM_EVEN => 102,
            self::HAS_WINNER => 103,
        ) );
        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
	}

    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "tictacmatchleocaseiro";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Team Setup (ripped off from Coinche)
        // Retrieve inital player order ([0 => playerId1, 1 => playerId2, ...])
		$playerInitialOrder = [];
		foreach ($players as $playerId => $player) {
			$playerInitialOrder[$player['player_table_order']] = $playerId;
		}
		ksort($playerInitialOrder);
		$playerInitialOrder = array_flip(array_values($playerInitialOrder));

		// Player order based on 'playerTeams' option
		if (count($players) === 4) {
            $playerOrder = [0, 1, 2, 3];
            switch (self::getGameStateValue('playerTeams')) {
                case self::TEAM_1_2:
                    $playerOrder = [0, 2, 1, 3];
                    break;
                case self::TEAM_1_4:
                    $playerOrder = [0, 1, 3, 2];
                    break;
                case self::TEAM_RANDOM:
                    shuffle($playerOrder);
                    break;
                default:
                case self::TEAM_1_3:
                    // Default order
                    break;
            }
        } else {
            // for 2 players, ignore this option, and just random
            $playerOrder = [0, 1];
            shuffle($playerOrder);
        }

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_no) VALUES ";
        $values = array();
        foreach ($players as $playerId => $player) {
			$color = array_shift($default_colors);
			$values[] =
				"('" .
				$playerId .
				"','$color','" .
				$player['player_canal'] .
				"','" .
				addslashes($player['player_name']) .
				"','" .
				addslashes($player['player_avatar']) .
				"','" .
				$playerOrder[$playerInitialOrder[$playerId]] .
				"')";
		}
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // setup the initial game situation here
        self::setGameStateInitialValue( self::HAS_WINNER, false );

        // Create cards
        $this->cards->createCards($this->ttm_cards, 'deck');

        // Shuffle deck
		$this->cards->shuffle('deck');

        // Deal 4 cards to each players
		$players = self::loadPlayersBasicInfos();
		foreach ($players as $playerId => $player) {
			$this->cards->pickCards(4, 'deck', $playerId);
		}

        // Setup grid with card on middle
        $initialCard = null;
        while ($initialCard == null) {
            $drewedCard = $this->cards->pickCardForLocation('deck', 'cell-4');
            if ($drewedCard['type'] === 'symbol') {
                $initialCard = $drewedCard;
            } else {
                // if action card, discard to draw another one.
                $this->cards->insertCardOnExtremePosition( $drewedCard['id'], 'discardpile', true);
            }
		}

        // Set symbol for players (first player has opposite side as the table on first draw)
        $this->addExtraCardPropertiesFromMaterial($initialCard);
        $this->addExtraCardPropertiesFromMaterial($initialCard);
        if ($initialCard['value'] === self::TEAM_X_STRING) {
            self::setGameStateInitialValue( self::TEAM_EVEN, self::TEAM_O );
            self::setGameStateInitialValue( self::TEAM_ODD, self::TEAM_X );
        } else {
            self::setGameStateInitialValue( self::TEAM_EVEN, self::TEAM_X );
            self::setGameStateInitialValue( self::TEAM_ODD, self::TEAM_O );
        }

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, player_no FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // Cards in player hand
        $result['hand'] = $this->cards->getPlayerHand($current_player_id);
        $this->populateCardProperties($result['hand']);

        $result['boardgrid'] = [];
        for ($i = 0; $i < 9; $i++) {
            $card = $this->cards->getCardOnTop('cell-' . $i);
            $this->addExtraCardPropertiesFromMaterial($card);
            $result['boardgrid'][$i] = $card;
        }

        // Cards on discardpile // TODO maybe only keep top card from stack only
        $result['discardpiletopcard'] = $this->cards->getCardOnTop('discardpile');
        $this->addExtraCardPropertiesFromMaterial($result['discardpiletopcard']);

        $result['totalcardsondiscardpile'] = $this->cards->countCardInLocation('discardpile');
        $result['totalcardsondeck'] = $this->cards->countCardInLocation('deck');

        // Get teams
        $result['teams'] = [
            'even' => $this->getTeamValue(self::getGameStateValue(self::TEAM_EVEN)),
            'odd' => $this->getTeamValue(self::getGameStateValue(self::TEAM_ODD))
        ];

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function addExtraCardPropertiesFromMaterial(&$card) {
        if (!isset($card)) {
            return;
        }

        $materialCard = $this->ttm_cards[$card['type_arg']];

        $card['class'] = $materialCard['class'];
        $card['color'] = $materialCard['color'];
        $card['value'] = $materialCard['value'];
    }

    function populateCardProperties(&$cards) {
        if (!$cards) {
            return;
        }

        foreach($cards as $card_id => $card) {
            if (isset($cards) && isset($cards[$card_id])) {
                $this->addExtraCardPropertiesFromMaterial($cards[$card_id]);
            } else {
                throw new BgaUserException(self::_('Card not found'));
            }
        }
    }

    function getTeamValue($stateId) {
        return $stateId == self::TEAM_X ? self::TEAM_X_STRING : self::TEAM_O_STRING;
    }

    function toggleTeam() {
        if (self::getGameStateValue(self::TEAM_EVEN) == self::TEAM_X) {
            self::setGameStateValue( self::TEAM_EVEN, self::TEAM_O );
            self::setGameStateValue( self::TEAM_ODD, self::TEAM_X );
        } else {
            self::setGameStateValue( self::TEAM_EVEN, self::TEAM_X );
            self::setGameStateValue( self::TEAM_ODD, self::TEAM_O );
        }
    }

    function checkWinner() {
        // 0, 1, 2,
        // 3, 4, 5,
        // 6, 7, 8,
        $matches = [
            // horizontally
            [0, 1, 2],
            [3, 4, 5],
            [6, 7, 8],
            // vertically
            [0, 3, 6],
            [1, 4, 7],
            [2, 5, 8],
            // diagonally
            [0, 4, 8],
            // anti-diagonally
            [2, 4, 6],
        ];

        $boardgrid = [];
        foreach ($matches as $row) {
            foreach ($row as $index) {
                if (!isset($boardgrid[$index]) || !is_null($boardgrid[$index])) {
                    $card = $this->cards->getCardOnTop('cell-' . $index);
                    $boardgrid[$index] = $card;
                }
            }
            if (
                !is_null($boardgrid[$row[0]]) && isset($boardgrid[$row[0]]['type_arg']) &&
                !is_null($boardgrid[$row[1]]) && isset($boardgrid[$row[1]]['type_arg']) &&
                !is_null($boardgrid[$row[2]]) && isset($boardgrid[$row[2]]['type_arg'])
            ) {
                if ($boardgrid[$row[0]]['type_arg'] == $boardgrid[$row[1]]['type_arg'] && $boardgrid[$row[1]]['type_arg'] == $boardgrid[$row[2]]['type_arg']) {
                    $card = $boardgrid[$row[0]];
                    $this->addExtraCardPropertiesFromMaterial($card);
                    return $card['value'];
                }
            }
        }


        return false;
    }

    function setWinnerScore($playerId) {
        if ($playerId) {
            $sql = "UPDATE player SET player_score = 1 WHERE player_id = '$playerId'";
            self::DbQuery($sql);
        }
    }

    function setWinners($winner) {
        $players = self::loadPlayersBasicInfos();
        $winners_no = self::getGameStateValue(self::TEAM_EVEN) == $winner ? [0, 2] : [1, 3];
        foreach ($players as $player) {
            if (in_array($player['player_no'], $winners_no)) {
                $this->setWinnerScore($player['player_id']);
            }
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in tictacmatchleocaseiro.action.php)
    */

    function playCard( $cell_location, $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' );

        $player_id = self::getActivePlayerId();

        $card_location = 'cell-' . $cell_location;
        $playingGridCard = $this->cards->getCardOnTop($card_location);
        $card = $this->cards->getCard($card_id);

        $this->addExtraCardPropertiesFromMaterial($card);


        if($playingGridCard) {
            $this->addExtraCardPropertiesFromMaterial($playingGridCard);

            if ($card['color'] === $playingGridCard['color'] && $card['value'] === $playingGridCard['value']) {
                throw new BgaUserException(self::_('You are not allowed to place the same card, only a card at the same color or same value!'));
                return;
            }

            if ($card['color'] !== $playingGridCard['color'] && $card['value'] !== $playingGridCard['value']) {
                throw new BgaUserException(self::_('You need to select a card at the same color or same value!'));
                return;
            }
        }

        $this->cards->insertCardOnExtremePosition($card_id, $card_location, true);
        $card_name = $card['value'] . ' ' . $card['color'];

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card' => $card,
            'cell_location' => $cell_location
            )
        );

        if ($winner = $this->checkWinner()) {
            $this->setWinners($winner);
            self::setGameStateValue(self::HAS_WINNER, true);
            $this->gamestate->nextState('nextPlayer');
            return;
        }

        // Draw a new card to player
        $newCard = $this->cards->pickCard('deck', $player_id);
        if ($newCard) {
            $this->addExtraCardPropertiesFromMaterial($newCard);
            $newCard_name = $newCard['value'] . ' ' . $newCard['color'];

            // Notify all players about the drawing card
            self::notifyAllPlayers( "drawCard", clienttranslate( '${player_name} draw a card' ), array(
                'player_name' => self::getActivePlayerName(),
                'totalcardsondeck' => $this->cards->countCardInLocation('deck'),
            ));

            // Notify player about the new card
            self::notifyPlayer( $player_id, "drawSelfCard", clienttranslate( 'You draw ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $newCard_name,
                'card' => $newCard
            ));
        }

        $this->gamestate->nextState('nextPlayer');
    }

    function playAction( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playAction' );

        $player_id = self::getActivePlayerId();

        $card = $this->cards->getCard($card_id);
        $this->addExtraCardPropertiesFromMaterial($card);
        $action = [
            'name' => $card['class']
        ];

        // Do Action
        switch ($card['class']) {
            // Flip card
            case 'action_flip':
                $this->toggleTeam();
                $action['teams'] = [
                    'even' => $this->getTeamValue(self::getGameStateValue(self::TEAM_EVEN)),
                    'odd' => $this->getTeamValue(self::getGameStateValue(self::TEAM_ODD))
                ];
                break;
            case 'action_2plus':
                break;
            case 'action_wipe_out':
                break;
        }

        $this->cards->insertCardOnExtremePosition($card_id, 'discardpile', true);
        $card_name = $card['value'];

        // Notify all players about the card played
        self::notifyAllPlayers( "actionPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card' => $card,
            'action' => $action,
        ) );

        if ($winner = $this->checkWinner()) {
            $this->setWinners($winner);
            self::setGameStateValue(self::HAS_WINNER, true);
            $this->gamestate->nextState('nextPlayer');
            return;
        }

        // Draw a new card to player
        $newCard = $this->cards->pickCard('deck', $player_id);
        if ($newCard) {
            $this->addExtraCardPropertiesFromMaterial($newCard);
            $newCard_name = $newCard['value'] . ' ' . $newCard['color'];
            // Notify all players about the drawing card
            self::notifyAllPlayers( "drawCard", clienttranslate( '${player_name} draw a card' ), array(
                'player_name' => self::getActivePlayerName(),
                'totalcardsondeck' => $this->cards->countCardInLocation('deck'),
                'totalcardsondiscardpile' => $this->cards->countCardInLocation('discardpile')
            ));

            // Notify player about the new card
            self::notifyPlayer( $player_id, "drawSelfCard", clienttranslate( 'You draw ${card_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'card_name' => $newCard_name,
                'card' => $newCard,
            ));
        }

        $this->gamestate->nextState('nextPlayer');
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stNextPlayer()
    {
        if (self::getGameStateValue(self::HAS_WINNER)) {
            $this->gamestate->nextState('endGame');
        } else {
            $this->activeNextPlayer();
            $this->gamestate->nextState('playerTurn');
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}
