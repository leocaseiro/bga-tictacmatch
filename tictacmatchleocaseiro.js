/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * tictacmatchleocaseiro implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * tictacmatchleocaseiro.js
 *
 * tictacmatchleocaseiro user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.tictacmatchleocaseiro", ebg.core.gamegui, {
        constructor: function(){
            console.log('tictacmatchleocaseiro constructor');

            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.playerHand = null;
            this.selectedCardId = null;
        },

        /*
            setup:

            This method must set up the game user interface according to current game situation specified
            in parameters.

            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)

            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

        setup: function( gamedatas )
        {
            const self = this;
            console.log( "Starting game setup" );

            this.playerHand = this.gamedatas.hand;
            this.boardGrid = this.gamedatas.boardgrid;
            this.topDiscardPile = this.gamedatas.discardpiletopcard;

            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
            }

            // Deal cards to current player hand
            Object.entries(this.playerHand).forEach(([id, card]) => {
                const domId = `js-card-${id}`;
                const cardDiv = this.format_block('jstpl_card', {
                    ID: domId,
                    DATAID: id,
                    CLASS: card.class,
                    COLOR: card.color,
                    CARDVALUE: card.value,
                });

                dojo.place(cardDiv, 'js-hand__cards');
                this.addTooltip(domId, _(`${card.value} ${card.color}`), _('Click to select card'));

                dojo.connect($(domId), 'onclick', this, this.onHandCardClick);
            });

            // Add event listeners to Grid cells
            Array.from(document.querySelectorAll('[id^="js-board-cell--"]')).forEach(function(el) {
                self.addTooltip(el.id, _('Card cell'), _('Click to place card'));
                dojo.connect($(el.id), 'onclick', self, self.onGridCardClick);
            });

            // Show cards on Grid
            Object.entries(this.boardGrid).forEach(([id, card], i) => {
                if (!card) {
                    return;
                }

                const domId = `js-board-cell--${i}`;
                const cardDiv = this.format_block('jstpl_card', {
                    ID: domId,
                    DATAID: id,
                    CLASS: card.class,
                    COLOR: card.color,
                    CARDVALUE: card.value,
                });
                this.removeTooltip(domId);

                dojo.place(cardDiv, domId, 'replace');
                this.addTooltip(domId, _(`${card.value} ${card.color}`), _(`Click to place a ${card.value} card or a ${card.color} card`));
                dojo.setAttr($(domId), 'data-cell', i);
                dojo.connect($(domId), 'onclick', self, self.onGridCardClick);
            });

            // Show number of cards on deck
            const deckTotal = `${this.gamedatas.totalcardsondeck}x`;
            const deckBadge = document.getElementById('js-deck-badge');
            deckBadge.setAttribute('title', deckTotal);
            deckBadge.innerText = deckTotal;

            // Show card on Discard Pile
            if (this.topDiscardPile) {
                const domId = 'js-discard-pile-card';
                const discardPileCardDiv = this.format_block('jstpl_card', {
                    ID: domId,
                    DATAID: this.topDiscardPile.id,
                    CLASS: this.topDiscardPile.class,
                    COLOR: this.topDiscardPile.color,
                    CARDVALUE: this.topDiscardPile.value,
                });

                dojo.place(discardPileCardDiv, domId, 'replace');
                this.addTooltip(domId, _('Discard Pile'), _('Click to discard card'));
            }

            // Show number of cards on Discard Pile
            const discardTotal = `${this.gamedatas.totalcardsondiscardpile}x`;
            const discardPileBadge = document.getElementById('js-discard-pile-badge');
            discardPileBadge.setAttribute('title', discardTotal);
            discardPileBadge.innerText = discardTotal;

            // Show player team card
            const teamCardClass = `card--team_${this.getTeamValue().toLowerCase()}`;
            dojo.replaceClass('js-team-card', teamCardClass, 'card--empty');

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },


        ///////////////////////////////////////////////////
        //// Game & client states

        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );

            switch( stateName )
            {

            /* Example:

            case 'myGameState':

                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );

                break;
           */


            case 'dummmy':
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );

            switch( stateName )
            {

            /* Example:

            case 'myGameState':

                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );

                break;
           */


            case 'dummmy':
                break;
            }
        },

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );

            if( this.isCurrentPlayerActive() )
            {
                switch( stateName )
                {
/*
                 Example:

                 case 'myGameState':

                    // Add 3 action buttons in the action status bar:

                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' );
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' );
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' );
                    break;
*/
                }
            }
        },

        ///////////////////////////////////////////////////
        //// Utility methods

        /*

            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.

        */
       getTeamValue: function() {
            const playerObj = this.gamedatas.players[this.player_id];
            return Number(playerObj.player_no) % 2 === 0 ? this.gamedatas.teams.even : this.gamedatas.teams.odd;
       },


        ///////////////////////////////////////////////////
        //// Player's action



        /*

            Here, you are defining methods to handle player's action (ex: results of mouse click on
            game objects).

            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server

        */

        onHandCardClick: function (e) {
            if (!this.checkAction('playCard', true)) {
                return;
            }
            this.selectCard = e.target.dataset;

            if (this.selectCard.color === 'action') {
                this.ajaxcall(
                    '/tictacmatchleocaseiro/tictacmatchleocaseiro/playAction.html',
                    {
                        lock: true,
                        cardId: this.selectCard.id,
                    },
                    this,
                    function( result ) {
                        // Do some stuff after a successful call
                        // NB : usually not needed as changes must be handled by notifications
                        // You should NOT modify the interface in a callback or it will most likely break the framework replays (or make it inaccurate)
                        // You should NOT make another ajaxcall in a callback in order not to create race conditions
                    }
                );
            }
        },

        onGridCardClick: function (e) {
            if (!this.checkAction('playCard', true)) {
                return;
            }

            if (!this.selectCard) {
                this.showMessage(_('Please select a card from your hand!'), 'error');
            }

            const cell = e.target.dataset;

            // empty cell or same color and not same value
            if (cell.id) {
                if (cell.color === this.selectCard.color && cell.value === this.selectCard.value) {
                    this.showMessage(_('You are not allowed to place the same card, only a card at the same color or same value!'), 'error');
                    return;
                }

                if (cell.color !== this.selectCard.color && cell.value !== this.selectCard.value) {
                    this.showMessage(_('You need to select a card at the same color or same value!'), 'error');
                    return;
                }
            }

            this.ajaxcall(
                '/tictacmatchleocaseiro/tictacmatchleocaseiro/playCard.html',
                {
                    lock: true,
                    cellLocation: cell.cell,
                    cardId: this.selectCard.id,
                },
                this,
                function( result ) {
                    // Do some stuff after a successful call
                    // NB : usually not needed as changes must be handled by notifications
                    // You should NOT modify the interface in a callback or it will most likely break the framework replays (or make it inaccurate)
                    // You should NOT make another ajaxcall in a callback in order not to create race conditions
                }
            );
        },

        /* Example:

        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );

            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/tictacmatchleocaseiro/tictacmatchleocaseiro/myAction.html", {
                                                                    lock: true,
                                                                    myArgument1: arg1,
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 },
                         this, function( result ) {

                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)

                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );
        },

        */


        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your tictacmatchleocaseiro.game.php file.

        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // TODO: here, associate your game notifications with local methods

            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //
        },

        // TODO: from this point and below, you can write your game notifications handling methods

        /*
        Example:

        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );

            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call

            // TODO: play the card in the user interface.
        },

        */
   });
});
