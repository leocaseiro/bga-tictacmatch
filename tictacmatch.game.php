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
  * tictacmatch.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );
require_once('modules/php/Helpers/Collection.php');
require_once('modules/php/Helpers/DB_Manager.php');
require_once('modules/php/Helpers/QueryBuilder.php');
require_once('modules/php/Core/UserPreferences.php');

class tictacmatch extends Table
{
	// Team pairing constants  (ripped off from Coinche)
	const TEAM_1_3 = 1;
	const TEAM_1_2 = 2;
	const TEAM_1_4 = 3;
	const TEAM_RANDOM = 4;
    const PLAYER_TEAMS = 'playerTeams';

    const TEAM_EVEN = 'evens_team';
    const TEAM_ODD = 'odds_team';
    const TEAM_O = 0;
    const TEAM_X = 10;
    const TEAM_X_STRING = 'X';
    const TEAM_O_STRING = 'O';

    const HAS_WINNER = 'has_winner';
    const WINNER_MATCHES = 'winner_matches';

    const WIPE_CARDS_FROM = 'wipe_cards_from';
    const DOUBLE_PLAY_PLAYER = 'double_play_player';
    const DOUBLE_PLAY_CARDS = 'double_play_cards';

    // 0, 1, 2,
    // 3, 4, 5,
    // 6, 7, 8,
    const MATCHES = [
        // horizontally
        1 => [0, 1, 2],
        2 => [3, 4, 5],
        3 => [6, 7, 8],
        // vertically
        4 => [0, 3, 6],
        5 => [1, 4, 7],
        6 => [2, 5, 8],
        // diagonally
        7 => [0, 4, 8],
        // anti-diagonally
        8 => [2, 4, 6],
    ];

    public static $instance = null;

	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::$instance = $this;

        self::initGameStateLabels( array(
            self::PLAYER_TEAMS => 100,
            self::TEAM_ODD => 11,
            self::TEAM_EVEN => 12,
            self::HAS_WINNER => 13,
            self::WIPE_CARDS_FROM => 14,
            self::DOUBLE_PLAY_PLAYER => 15,
            self::DOUBLE_PLAY_CARDS => 16,
            self::WINNER_MATCHES => 17,
        ) );
        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
	}

    public static function get()
    {
        return self::$instance;
    }

    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "tictacmatch";
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
            switch (self::getGameStateValue(self::PLAYER_TEAMS)) {
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
        self::initStat( 'table', 'turns_number', 0 );
        self::initStat( 'player', 'turns_number', 0 );
        self::initStat( 'table', 'cards_played', 0 );
        self::initStat( 'player', 'cards_played', 0 );
        self::initStat( 'table', 'symbol_cards_on_empty_space', 0 );
        self::initStat( 'player', 'symbol_cards_on_empty_space', 0 );
        self::initStat( 'table', 'blue_cards', 0 );
        self::initStat( 'player', 'blue_cards', 0 );
        self::initStat( 'table', 'green_cards', 0 );
        self::initStat( 'player', 'green_cards', 0 );
        self::initStat( 'table', 'red_cards', 0 );
        self::initStat( 'player', 'red_cards', 0 );
        self::initStat( 'table', 'yellow_cards', 0 );
        self::initStat( 'player', 'yellow_cards', 0 );
        self::initStat( 'table', 'x_cards', 0 );
        self::initStat( 'player', 'x_cards', 0 );
        self::initStat( 'table', 'o_cards', 0 );
        self::initStat( 'player', 'o_cards', 0 );
        self::initStat( 'table', 'symbol_cards_value_replaced', 0 );
        self::initStat( 'player', 'symbol_cards_value_replaced', 0 );
        self::initStat( 'table', 'symbol_cards_color_replaced', 0 );
        self::initStat( 'player', 'symbol_cards_color_replaced', 0 );
        self::initStat( 'table', 'flip_cards_played', 0 );
        self::initStat( 'player', 'flip_cards_played', 0 );
        self::initStat( 'table', 'wipe_out_cards_played', 0 );
        self::initStat( 'player', 'wipe_out_cards_played', 0 );
        self::initStat( 'table', 'double_play_cards_played', 0 );
        self::initStat( 'player', 'double_play_cards_played', 0 );
        self::initStat( 'table', 'reshuffle_draw_pile', 0 );
        self::initStat( 'player', 'wiped_out_cards_player', 0 );

        // setup the initial game situation here
        self::setGameStateInitialValue( self::HAS_WINNER, false );
        self::setGameStateInitialValue( self::DOUBLE_PLAY_PLAYER, 0 );
        self::setGameStateInitialValue( self::DOUBLE_PLAY_CARDS, 3 );

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

        // User Preferences (from tisaac boilerplate)
        UserPreferences::setupNewGame($players, $options);

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
        // Add teams for each player on gamedatas
        $this->addExtraPropsToPlayers($result['players']);

        // User Preferences
        $result['prefs'] = UserPreferences::getUiData($current_player_id);

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
        // There isn't a proper way to detect progression of TIC TAC MATCH, so we disabled
        // updateGameProgression => false
        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////

    function addExtraPropsToPlayers(&$players) {
        foreach ($players as $playerIndex => $player) {
            $even = $this->getTeamValue(self::getGameStateValue(self::TEAM_EVEN));
            $odd = $this->getTeamValue(self::getGameStateValue(self::TEAM_ODD));
            $player['player_team'] = (int) $player['player_no'] % 2 == 0 ? $even : $odd;
            $player['nCards'] = $this->cards->countCardInLocation('hand', $playerIndex);
            $players[$playerIndex] = $player;
        }
    }

    function addExtraCardPropertiesFromMaterial(&$card) {
        if (!isset($card)) {
            return;
        }

        $materialCard = $this->ttm_cards[$card['type_arg']];

        $card['class'] = $materialCard['class'];
        $card['color'] = $materialCard['color'];
        if (isset($materialCard['colorLabel'])) {
            $card['colorLabel'] = $materialCard['colorLabel'];
        }
        $card['label'] = $materialCard['label'];
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

    function wipeCards($player_id) {
        $player_name = self::getActivePlayerName();
        $player_from = self::getPlayerNameById($player_id);
        $this->setGameStateValue(self::WIPE_CARDS_FROM, $player_id);
        $cards = $this->cards->getCardsInLocation('hand', $player_id);
        $this->populateCardProperties($cards);
        $this->cards->moveAllCardsInLocation('hand', 'discardpile', $player_id, 0);

        // Notify all players about the wiped out
        self::notifyAllPlayers( "wipedOut", clienttranslate( '${player_name} wiped cards from ${player_from}' ), array(
            'player_name' => $player_name,
            'player_from' => $player_from,
            'player_id' => $player_id,
            'cards' => $cards,
            'totalcardsondiscardpile' => $this->cards->countCardInLocation('discardpile'),
        ));

        // Notify player about the new 4 cards
        for ($i = 0; $i < 4; $i++) {
            // Draw a new card to player
            $newCard = $this->cards->pickCard('deck', $player_id);
            $this->addExtraCardPropertiesFromMaterial($newCard);
            $newCard_name = $newCard['label'];
            self::notifyPlayer( $player_id, "drawSelfCard", clienttranslate( 'You draw ${card_name}' ), array(
                'card_name' => $newCard_name,
                'card' => $newCard
            ));

            $totalcardsondeck = $this->cards->countCardInLocation('deck');
            if ($totalcardsondeck == 0) {
                $this->reShuffleDeck();
            }
        }

        self::incStat(1, 'wiped_out_cards_player', $player_id);
    }

    function checkWinner() {
        $matches = self::MATCHES;

        $boardgrid = [];
        foreach ($matches as $winner_matches => $row) {
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
                    self::setGameStateValue(self::WINNER_MATCHES, $winner_matches);
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

    function getWinners($winner) {
        $players = self::loadPlayersBasicInfos();
        $winner_value = $winner == self::TEAM_X_STRING ? self::TEAM_X : self::TEAM_O;
        $winners_no = self::getGameStateValue(self::TEAM_EVEN) == $winner_value ? [0, 2] : [1, 3];
        $winners = array();
        foreach ($players as $player) {
            if (in_array($player['player_no'], $winners_no)) {
                $this->setWinnerScore($player['player_id']);
                array_push($winners, $player);
            }
        }

        return $winners;
    }

    function moveCardsToDeck($from_location, $from_js_id, $cell = false) {
        $totalOfCards = $this->cards->countCardInLocation($from_location);

        if (!$cell || $totalOfCards > 1) {
            $cardOnTop = $this->cards->getCardOnTop($from_location);

            // move all cards from cell to deck
            $this->cards->moveAllCardsInLocation($from_location, 'deck');

            if ($cell) {
                // revert card from the top to stay on cell
                $this->cards->moveCard($cardOnTop['id'], $from_location);
            }

            // Notify all players about the card played
            self::notifyAllPlayers( "moveCardsToDeck", "", array(
                'from' => $from_js_id,
                'to' => 'js-deck',
                'totalOfCards' => $totalOfCards,
            ));
        }
    }

    function reShuffleDeck() {
        // grab cards for each cell, except from the top card
        for ($i = 0; $i < 9; $i++) {
            $cell = 'cell-' . $i;
            $this->moveCardsToDeck($cell, 'js-board-cell--'. $i, true);
        }
        // grab cards from discard pile
        $this->moveCardsToDeck('discardpile', 'js-discard-pile-card');

        // shuffle new deck
        $this->cards->shuffle('deck');
        self::incStat(1, 'reshuffle_draw_pile');

        // Notify all players about the new draw pile
        self::notifyAllPlayers( "reShuffleDeck", clienttranslate( 'Draw pile was empty, making new draw pile from discarded cards and covered cards' ), array(
            'totalcardsondeck' => $this->cards->countCardInLocation('deck'),
            'totalcardsondiscardpile' => $this->cards->countCardInLocation('discardpile')
        ));
    }

    function setStatsForCardPlayedOnCell($card, $playingGridCard, $player_id) {
        self::incStat(1, 'cards_played');
        self::incStat(1, 'cards_played', $player_id);

        // blue_cards, green_cards, red_cards, yellow_cards
        self::incStat(1, $card['color'] . '_cards');
        self::incStat(1, $card['color'] . '_cards', $player_id);

        // x_cards, o_cards
        self::incStat(1, strtolower($card['value']) . '_cards');
        self::incStat(1, strtolower($card['value']) . '_cards', $player_id);

        if (!$playingGridCard) {
            self::incStat(1, 'symbol_cards_on_empty_space');
            self::incStat(1, 'symbol_cards_on_empty_space', $player_id);
        }

        if ($playingGridCard) {
            if ($card['value'] != $playingGridCard['value']) {
                self::incStat(1, 'symbol_cards_value_replaced');
                self::incStat(1, 'symbol_cards_value_replaced', $player_id);
            }

            if ($card['color'] != $playingGridCard['color']) {
                self::incStat(1, 'symbol_cards_color_replaced');
                self::incStat(1, 'symbol_cards_color_replaced', $player_id);
            }
        }
    }

    function setStatsForActionCardPlayed($card, $player_id) {
        self::incStat(1, 'cards_played');
        self::incStat(1, 'cards_played', $player_id);

        // flip_cards_played, wipe_out_cards_played, double_play_cards_played
        self::incStat(1, $card['value'] . 's_played');
        self::incStat(1, $card['value'] . 's_played', $player_id);
    }

    protected function isPlayerZombie($player_id) {
        $players = self::loadPlayersBasicInfos();
        if (! isset($players[$player_id]))
            throw new BgaSystemException("Player $player_id is not playing here");

        return ($players[$player_id]['player_zombie'] == 1);
    }

    function notifyScores()
    {
        $symbol_winner = $this->checkWinner();
        $winners = $this->getWinners($symbol_winner);
        $winner_matches = self::getGameStateValue(self::WINNER_MATCHES);
        $message = NULL;

        $props = array(
            'player_name' => $winners[0]['player_name'],
            'symbol'=> $symbol_winner == 'X' ? 'X' : '0',
            'winner_matches' => self::MATCHES[$winner_matches]
        );

        if (count($winners) == 2) {
            $message = clienttranslate('Players ${player_name} and ${player_name2} are the winners with ${symbol}!');
            $props['player_name2'] = $winners[1]['player_name'];
        } else {
            $message = clienttranslate('Player ${player_name} is the winner with ${symbol}!');
        }

        self::notifyAllPlayers( "endScore", $message, $props);
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in tictacmatch.action.php)
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

        if ($card['type'] !== 'symbol') {
            throw new BgaUserException(self::_('You need to select a symbol card to play at the table!'));
            return;
        }

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
        $card_name = $card['label'];
        $this->setStatsForCardPlayedOnCell($card, $playingGridCard, $player_id);

        $players = self::loadPlayersBasicInfos();
        $this->addExtraPropsToPlayers($players);

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card' => $card,
            'cell_location' => $cell_location,
            'players' => $players,
            )
        );

        if ($this->checkWinner()) {
            self::setGameStateValue(self::HAS_WINNER, true);
            $this->gamestate->nextState('nextPlayer');
            return;
        }

        // Only draw if is not part of double play
        if (self::getGameStateValue(self::DOUBLE_PLAY_PLAYER) == 0) {
            // Draw a new card to player
            $newCard = $this->cards->pickCard('deck', $player_id);
            $this->addExtraCardPropertiesFromMaterial($newCard);
            $newCard_name = $newCard['label'];

            // Update player data
            $this->addExtraPropsToPlayers($players);

            // Notify all players about the drawing card
            $totalcardsondeck = $this->cards->countCardInLocation('deck');
            self::notifyAllPlayers( "drawCard", clienttranslate( '${player_name} draw a card' ), array(
                'player_name' => self::getActivePlayerName(),
                'totalcardsondeck' => $totalcardsondeck,
                'players' => $players,
            ));

            // Notify player about the new card
            self::notifyPlayer( $player_id, "drawSelfCard", clienttranslate( 'You draw ${card_name}' ), array(
                'card_name' => $newCard_name,
                'card' => $newCard,
                'players' => $players,
            ));
        }

        $totalcardsondeck = $this->cards->countCardInLocation('deck');
        if ($totalcardsondeck == 0) {
            $this->reShuffleDeck();
        }

        $this->gamestate->nextState('nextPlayer');
    }

    function playAction( $card_id, $playerChosen )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playAction' );

        $player_id = self::getActivePlayerId();

        $card = $this->cards->getCard($card_id);
        $this->addExtraCardPropertiesFromMaterial($card);
        $action = [
            'name' => $card['value']
        ];
        $is_double_play_turn = self::getGameStateValue(self::DOUBLE_PLAY_PLAYER);
        $do_action = false;

        // Do Action
        switch ($card['value']) {
            // Flip card
            case 'flip_card':
                $this->toggleTeam();
                $action['teams'] = [
                    'even' => $this->getTeamValue(self::getGameStateValue(self::TEAM_EVEN)),
                    'odd' => $this->getTeamValue(self::getGameStateValue(self::TEAM_ODD))
                ];

                break;

            case 'double_play_card':
                if ($is_double_play_turn) {
                    throw new BgaUserException(self::_('You are not allowerd to play another Double Play card during a Double Play turn, please choose another card!'));
                    return;
                }
                self::setGameStateValue(self::DOUBLE_PLAY_PLAYER, self::getActivePlayerId());
                $do_action = 'action_2plus';
                break;

            case 'wipe_out_card':
                if ($is_double_play_turn) {
                    throw new BgaUserException(self::_('You are not allowerd to play a Wipe Out card during a Double Play turn, please choose another card!'));
                    return;
                }
                if ($this->isPlayerZombie($playerChosen)) {
                    throw new BgaUserException(self::_('The player you are trying to wipe cards is a zombie, please choose another player!'));
                    return;
                }
                $do_action = 'action_wipe_out';
                break;
        }

        $this->setStatsForActionCardPlayed($card, $player_id);

        $this->cards->insertCardOnExtremePosition($card_id, 'discardpile', true);
        $card_name = $card['label'];

        $players = self::loadPlayersBasicInfos();
        $this->addExtraPropsToPlayers($players);

        // Notify all players about the card played
        self::notifyAllPlayers( "actionPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card' => $card,
            'action' => $action,
            'players' => $players,
        ) );

        if ($this->checkWinner()) {
            self::setGameStateValue(self::HAS_WINNER, true);
            $this->gamestate->nextState('nextPlayer');
            return;
        }

        // Only draw if is not part of double play
        if (self::getGameStateValue(self::DOUBLE_PLAY_PLAYER) == 0) {
            // Draw a new card to player
            $newCard = $this->cards->pickCard('deck', $player_id);
            $this->addExtraCardPropertiesFromMaterial($newCard);
            $newCard_name = $newCard['label'];

            // Update player data
            $this->addExtraPropsToPlayers($players);

            // Notify all players about the drawing card
            $totalcardsondeck = $this->cards->countCardInLocation('deck');
            self::notifyAllPlayers( "drawCard", clienttranslate( '${player_name} draw a card' ), array(
                'player_name' => self::getActivePlayerName(),
                'totalcardsondeck' => $totalcardsondeck,
                'totalcardsondiscardpile' => $this->cards->countCardInLocation('discardpile'),
                'players' => $players,
            ));

            // Notify player about the new card
            self::notifyPlayer( $player_id, "drawSelfCard", clienttranslate( 'You draw ${card_name}' ), array(
                'card_name' => $newCard_name,
                'card' => $newCard,
                'players' => $players,
            ));
        }

        $totalcardsondeck = $this->cards->countCardInLocation('deck');
        if ($totalcardsondeck == 0) {
            $this->reShuffleDeck();
        }

        if ($do_action == 'action_wipe_out') {
            $this->wipeCards($playerChosen);
        }

        $this->gamestate->nextState('nextPlayer');
    }

    function skipAction( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'skipAction' );

        $is_double_play_turn = self::getGameStateValue(self::DOUBLE_PLAY_PLAYER);

        if (!$is_double_play_turn) {
            throw new BgaSystemException("You are not allowed to skip action at the moment.");
            return;
        }

        $card = $this->cards->getCard($card_id);
        $this->addExtraCardPropertiesFromMaterial($card);

        if ($card['type'] === 'symbol') {
            throw new BgaSystemException("You are not allowed to skip symbol cards");
            return;
        }

        if ($card['value'] === 'flip_card') {
            throw new BgaSystemException("You are not allowed to skip this card");
            return;
        }

        $players = self::loadPlayersBasicInfos();
        $this->addExtraPropsToPlayers($players);

        // Discard card
        $this->cards->insertCardOnExtremePosition($card_id, 'discardpile', true);

        // Notify all players about the card discarded
        self::notifyAllPlayers( "actionPlayed", clienttranslate( '${player_name} discards ${card_name}' ), array(
            'player_id' => self::getActivePlayerId(),
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card['label'],
            'card' => $card,
            'action' => ['name' => $card['value']],
            'players' => $players,
        ) );

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
        // End of the game!
        if (self::getGameStateValue(self::HAS_WINNER)) {
            $this->notifyScores();
            $this->gamestate->nextState('endScore');
            return;
        }

        // Wipe Cards out
        if ($player_id = self::getGameStateValue(self::WIPE_CARDS_FROM)) {
            self::setGameStateValue(self::WIPE_CARDS_FROM, 0);
            $this->gamestate->changeActivePlayer( $player_id );
            $this->gamestate->nextState('playerTurn');
            return;
        }

        // Double Play
        if ($player_id = self::getGameStateValue(self::DOUBLE_PLAY_PLAYER)) {
            $players = self::loadPlayersBasicInfos();
            $this->addExtraPropsToPlayers($players);

            $cards = self::getGameStateValue(self::DOUBLE_PLAY_CARDS);
            if ($cards == 3) {
                self::setGameStateValue(self::DOUBLE_PLAY_CARDS, 2);
                $this->gamestate->nextState('playerTurn');
                return;
            } elseif ($cards == 2) {
                self::setGameStateValue(self::DOUBLE_PLAY_CARDS, 1);
                $this->gamestate->nextState('playerTurn');
                return;
            } elseif ($cards == 1) {
                // reset
                self::setGameStateValue(self::DOUBLE_PLAY_PLAYER, 0);
                self::setGameStateValue(self::DOUBLE_PLAY_CARDS, 3);
                // Notify player about the new 4 cards
                for ($i = 0; $i < 3; $i++) {
                    // Draw a new card to player
                    $newCard = $this->cards->pickCard('deck', $player_id);
                    $this->addExtraCardPropertiesFromMaterial($newCard);
                    // Update players data
                    $this->addExtraPropsToPlayers($players);
                    $newCard_name = $newCard['label'];
                    self::notifyPlayer( $player_id, "drawSelfCard", clienttranslate( 'You draw ${card_name}' ), array(
                        'card_name' => $newCard_name,
                        'card' => $newCard,
                        'players' => $players
                    ));

                    $totalcardsondeck = $this->cards->countCardInLocation('deck');
                    if ($totalcardsondeck == 0) {
                        $this->reShuffleDeck();
                    }
                }
                // Update players data
                $this->addExtraPropsToPlayers($players);

                // Notify all players about the drawing card
                $totalcardsondeck = $this->cards->countCardInLocation('deck');
                self::notifyAllPlayers( "drawCard", clienttranslate( '${player_name} draw 3 cards' ), array(
                    'player_name' => self::getActivePlayerName(),
                    'totalcardsondeck' => $totalcardsondeck,
                    'players' => $players
                ));
            }
        }

        $player_id = $this->activeNextPlayer();
        self::giveExtraTime($player_id);
        $this->gamestate->nextState('playerTurn');
    }

    function stEndScore()
    {
            $this->gamestate->nextState('endGame');
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
        // Ripped-off idea from Bandido
    	// If the player just left, we put back their cards in the deck. Else we do nothing more.
         if ($this->cards->countCardInLocation("hand", $active_player) != 0) {
            $this->cards->moveAllCardsInLocation("hand", "deck", $active_player);
            $this->notifyAllPlayers(
                "playerLeft",
                clienttranslate('A player left. Their hand has been sent back to the deck.'),
                array()
            );

            self::notifyPlayer($active_player, "changeHand", "", array('newHand' => array()));
        }

        $this->gamestate->nextState("zombiePass");
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
