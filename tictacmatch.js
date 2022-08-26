/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * tictacmatch implementation : © Leo Caseiro <leo@leocaseiro.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * tictacmatch.js
 *
 * tictacmatch user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo",
    "dojo/_base/declare",
    g_gamethemeurl + 'modules/js/vendor/nouislider.min.js',
    "dojo/NodeList-traverse",
    "ebg/core/gamegui",
    "ebg/counter",
],
function (dojo, declare, noUiSlider) {
    return declare("bgagame.tictacmatch", ebg.core.gamegui, {
        constructor: function(){
            console.log('tictacmatch constructor');

            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.playerHand = null;
            this.selectedCard = {};
            this._cardScale = this.getConfig('tictacmatchCardScale', 45);
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
            this.cardsCounters = [];
            this.playerScoreCounters = [];
            this.players = this.gamedatas.players;
            this.teams = this.gamedatas.teams;


            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                /*
                    * Create player panel and counters
                */
                const matches_to_win = this.gamedatas.matches_to_win || 1;
                const playerPanel = this.format_block('jstpl_playerPanel', {
                    pId: player_id,
                    matches: matches_to_win
                });
                dojo.place(playerPanel, 'player_board_' + player_id);
                this.addTooltip('cards-in-hand-' + player_id, _('Total cards in hand'), '');
                this.addTooltip('js-panel-team-card-' + player_id, _('ID Card'), '');

                if (matches_to_win == 1) {
                    this.addTooltip('matches-player-' + player_id, _('Score'), '');
                } else {
                    this.addTooltip('matches-player-' + player_id, _('Score matches'), _(`First to win ${matches_to_win} matches`));
                }

                this.cardsCounters[player_id] = new ebg.counter();
                this.cardsCounters[player_id].create('js-cards-in-hands-counter-' + player_id);
                this.cardsCounters[player_id].setValue(player['nCards']);

                this.playerScoreCounters[player_id] = new ebg.counter();
                this.playerScoreCounters[player_id].create('player_score_' + player_id);
                this.playerScoreCounters[player_id].create('js-player-score-' + player_id);
                this.playerScoreCounters[player_id].setValue(player.score);
            }

            // Deal cards to current player hand
            Object.entries(this.playerHand).forEach(([id, card]) => {
                const domId = `js-card-${id}`;
                const cardDiv = self.getCardDiv(domId, id, card);
                dojo.place(cardDiv, 'js-hand__cards');
                this.addTooltip(domId, card.label, _('Click to select card'));
                dojo.connect($(domId), 'onclick', this, this.onHandCardClick);
            });

            // Add event listeners to Grid cells
            Array.from(document.querySelectorAll('[id^="js-board-cell--"]')).forEach(function(el) {
                self.addTooltip(el.id, _('Card space'), _('Click to place card'));
                dojo.connect($(el.id), 'onclick', self, self.onGridCardClick);
            });

            // Show cards on Grid
            Object.entries(this.boardGrid).forEach(([id, card], i) => {
                if (!card) {
                    return;
                }

                const domId = `js-board-cell--${i}`;
                const cardDiv = self.getCardDiv(domId, id, card);
                this.removeTooltip(domId);

                dojo.place(cardDiv, domId, 'replace');
                const symbol = card.value === 'X' ? 'X' : '0';
                this.addTooltip(domId, card.label, _(`Click to place a ${symbol} card or a ${card.colorLabel} card`));
                const $el = $(domId);
                if ($el) {
                    dojo.setAttr($el, 'data-cell', i);
                    dojo.connect($el, 'onclick', self, self.onGridCardClick);
                }
            });

            // Show number of cards on deck
            self.setNumberOfCardsOnBadge(self.gamedatas.totalcardsondeck, 'js-deck-badge');

            // Show card on Discard Pile
            if (this.topDiscardPile) {
                const domId = 'js-discard-pile-card';
                const discardPileCardDiv = self.getCardDiv(domId, this.topDiscardPile.id, this.topDiscardPile);
                dojo.place(discardPileCardDiv, domId, 'replace');
            }

            // Show number of cards on Discard Pile
            self.setNumberOfCardsOnBadge(self.gamedatas.totalcardsondiscardpile, 'js-discard-pile-badge');

            // Show player team card
            this.flipIDCard();

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            // User Preferences settings
            this.setupSettings();

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
                case 'playerTurn':
                    this.removeClassFromSelector('.card--selectable', 'card--selectable');

                    if (this.isCurrentPlayerActive()) {
                        const self = this;

                        self.addClassFromSelector('#js-hand__cards .card', 'card--selectable');
                        setTimeout(() => {
                            self.addClassFromSelector('#js-hand__cards .card', 'card--selectable');
                        }, 500); // make sure it works

                        setTimeout(() => {
                            self.addClassFromSelector('#js-hand__cards .card', 'card--selectable');
                        }, 1000); // make sure it works once again
                    } else {
                        this.removeClassFromSelector('#js-hand__cards .card', 'card--selectable');
                    }
                    break;
                case 'gameEnd':
                    this.removeClassFromSelector('.card--selectable', 'card--selectable');
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

        /*
        * Detect if spectator or replay
        */
        isReadOnly() {
            return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
        },

        randomInteger(min = 500, max = 1200) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        },

        getTeamValue: function() {
            return this.players[this.player_id]['player_team'];
        },

        setNumberOfCardsOnBadge: function(n, badgeId) {
            const deckTotal = `${n}x`;
            const deckBadge = document.getElementById(badgeId);
            setTimeout(() => {
                deckBadge.setAttribute('title', deckTotal);
                deckBadge.innerText = deckTotal;

                if (n == 0) {
                    const card = dojo.query(`#${badgeId}`).siblings('.card')[0];
                    if (card) {
                        dojo.setAttr(card, 'class', 'card card--empty');
                    }
                }
            }, 1000);
        },

        replaceCardAttributes: function(card, domId) {
            const el = $(domId);

            dojo.setAttr(el, 'class', `card card--${card.class}`);
            dojo.setAttr(el, 'data-color', card.color);
            dojo.setAttr(el, 'data-id', card.id);
            dojo.setAttr(el, 'data-value', card.value);
        },

        clearCardAttributes: function(domId) {
            const el = $(domId);

            dojo.setAttr(el, 'class', `card card--empty`);
            dojo.removeAttr(el, 'data-color');
            dojo.removeAttr(el, 'data-id');
            dojo.removeAttr(el, 'data-value');
        },

        replaceCardOnCell: function(cellLocation, card) {
            const domId = `js-board-cell--${cellLocation}`;
            const el = $(domId);

            this.replaceCardAttributes(card, domId);
            this.removeTooltip(domId);
            const symbol = card.value === 'X' ? 'X' : '0';
            this.addTooltip(domId, card.label, _(`Click to place a ${symbol} card or a ${card.colorLabel} card`));
        },

        replaceCardOnDiscardPile: function(card) {
            this.replaceCardAttributes(card, 'js-discard-pile-card');
            this.topDiscardPile = card;
        },

        // used on Tisaac slide
        isFastMode() {
            return this.instantaneousMode;
        },

        // Tisaac slide function
        slide(mobile, targetId, options = {}){
            let config = Object.assign({
              duration: 800,
              delay:0,
              destroy: false,
              attach: true,
              pos: null,
              className: 'moving',
              from: null,
              clearPos: true,
            }, options);

            const newParent = config.attach? targetId : $(mobile).parentNode;
            this.changeParent(mobile, 'game_play_area');
            if(config.from != null)
              this.placeOnObject(mobile, config.from);
            dojo.style(mobile, "zIndex", 5000);
            dojo.addClass(mobile, config.className);
            return new Promise((resolve, reject) => {
              const animation = config.pos == null? this.slideToObject(mobile, targetId, config.duration, config.delay)
                : this.slideToObjectPos(mobile, targetId, config.pos.x, config.pos.y, config.duration, config.delay);

              dojo.connect(animation, 'onEnd', () => {
                dojo.style(mobile, "zIndex", null);
                dojo.removeClass(mobile, config.className);
                this.changeParent(mobile, newParent);
                if(config.destroy)
                  dojo.destroy(mobile);
                if(config.clearPos)
                  dojo.style(mobile, { top:null, left:null, position:null });
                resolve();
              });
              animation.play();
            });
        },

        // used on Tisaac slide
        changeParent(mobile, new_parent, relation) {
            if (mobile === null) {
                console.error("attachToNewParent: mobile obj is null");
                return;
            }
            if (new_parent === null) {
                console.error("attachToNewParent: new_parent is null");
                return;
            }
            if (typeof mobile == "string") {
                mobile = $(mobile);
            }
            if (typeof new_parent == "string") {
                new_parent = $(new_parent);
            }
            if (typeof relation == "undefined") {
                relation = "last";
            }
            var src = dojo.position(mobile);
            dojo.style(mobile, "position", "absolute");
            dojo.place(mobile, new_parent, relation);
            var tgt = dojo.position(mobile);
            var box = dojo.marginBox(mobile);
            var cbox = dojo.contentBox(mobile);
            var left = box.l + src.x - tgt.x;
            var top = box.t + src.y - tgt.y;
            this.positionObjectDirectly(mobile, left, top);
            box.l += box.w - cbox.w;
            box.t += box.h - cbox.h;
            return box;
        },

        // used on Tisaac slide
        positionObjectDirectly(mobileObj, x, y) {
            // do not remove this "dead" code some-how it makes difference
            dojo.style(mobileObj, "left"); // bug? re-compute style
            // console.log("place " + x + "," + y);
            dojo.style(mobileObj, {
                left: x + "px",
                top: y + "px"
            });
            dojo.style(mobileObj, "left"); // bug? re-compute style
        },

        getCardDiv(domId, id, card) {
            const cardDiv = this.format_block('jstpl_card', {
                DOMID: domId,
                DATAID: id,
                CLASS: card.class,
                COLOR: card.color,
                CARDVALUE: card.value,
                TYPE: card.type,
            });
            return cardDiv;
        },

        getFlipCardClass(team) {
            teamCardClass = 'card-flip card-flip--flipped-' + team.toLowerCase();
            return teamCardClass;
        },

        flipIDCard() {
            let teamCardClass = 'hide'; // spectator
            if (!this.isSpectator) {
                teamCardClass = this.getFlipCardClass(this.getTeamValue());
            }
            dojo.setAttr('js-team-card', 'class', teamCardClass);

            for(let player_id in this.players ) {
                const team = this.players[player_id]['player_team'];
                teamCardClass = this.getFlipCardClass(team);
                dojo.setAttr('js-panel-team-card-' + player_id, 'class', teamCardClass);
            }
        },

        updatePlayerCardsInHand() {
            for(let player_id in this.players ) {
                const ncards = this.players[player_id]['nCards'];
                this.cardsCounters[player_id].toValue(ncards);
            }
        },

        removeClassFromSelector(sel = '.card', className = 'selected') {
            dojo.query(sel).forEach((el) => {
                dojo.removeClass(el, className);
            });
        },

        addClassFromSelector(sel = '.card', className = 'selected') {
            dojo.query(sel).forEach((el) => {
                dojo.addClass(el, className);
            });
        },

        updatePlayersGamedata(players) {
            for (let player_id in players ) {
                const player = players[player_id];
                this.players[player_id].nCards = player.nCards;
                this.players[player_id].player_team = player.player_team;
            }
        },

        showSkipAction() {
            const nCards = this.players[this.getCurrentPlayerId()].nCards;
            const card = this.selectedCard;

            if (nCards == 4) {
                return false;
            }

            if (card.type === 'symbol') {
                return false;
            }

            if (card.value === 'flip_card') {
                return false;
            }

            this.multipleChoiceDialog(
                _('As part of a Double Play turn, you are no allowed to use this action!'), [_('discard card')],
                () => {
                    this.ajaxcall( '/tictacmatch/tictacmatch/skipAction.html', {
                        lock: true,
                        cardId: this.selectedCard.id
                    }, this, () => {});
                }
            );

            return true;
        },

        moveMultipleCards: function (from, to, totalOfCards) {
            for(let i = 0; i <= totalOfCards; i++) {
                const cardId = `js-from--${from}--to--${to}--${i}`;
                const duration = this.randomInteger();
                const backCard = this.format_block('jstpl_back_card', { DOMID: cardId });
                dojo.place(backCard, from);
                this.slide(cardId, to, { clearPos: false, destroy: true, duration: duration });
            }
        },

        addCardToPlayerHand: function (card, players, duration = false) {
            const destinationSelector = 'js-hand__cards';
            let cardSelector = `js-card-${card.id}`;
            this.updatePlayersGamedata(players);
            this.updatePlayerCardsInHand();

            //create card on player board;
            const cardDiv = this.getCardDiv(cardSelector, card.id, card);
            dojo.place(cardDiv, 'js-deck');

            if (duration) {
                this.slide(cardSelector, destinationSelector, { duration: duration });
            } else {
                this.slide(cardSelector, destinationSelector);
            }
            this.addTooltip(cardSelector, card.label, _('Click to select card'));
            dojo.connect($(cardSelector), 'onclick', this, this.onHandCardClick);
            if (this.isCurrentPlayerActive()) {
                this.addClassFromSelector('#js-hand__cards .card', 'card--selectable');
            } else {
                this.removeClassFromSelector('#js-hand__cards .card', 'card--selectable');
            }
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

            this.selectedCard = e.target.dataset;

            this.removeClassFromSelector('.card--selected', 'card--selected');
            this.removeClassFromSelector('.ttm-board-grid .card--selectable', 'card--selectable');
            if (this.selectedCard.type === 'symbol') {
                this.addClassFromSelector('#' + e.target.id, 'card--selected');
                this.addClassFromSelector(`.ttm-board-grid .card[data-color="${this.selectedCard.color}"]`, 'card--selectable');
                this.addClassFromSelector(`.ttm-board-grid .card[data-value="${this.selectedCard.value}"]`, 'card--selectable');
                this.addClassFromSelector('.ttm-board-grid .card--empty', 'card--selectable');
                // remove if same value and color
                this.removeClassFromSelector(`.ttm-board-grid .card[data-color="${this.selectedCard.color}"].card[data-value="${this.selectedCard.value}"]`, 'card--selectable');
                return;
            }

            switch (this.selectedCard.value) {
                case 'wipe_out_card':
                    if (this.showSkipAction()) {
                        return;
                    }
                    const keys = Object.values(this.players)
                        .filter(player => player.id != this.getCurrentPlayerId())
                        .map(player => player.name);
                    this.multipleChoiceDialog(
                        _('Choose a player to wipe cards out:'), keys,
                        (choice) => {
                            const playerChosenName = keys[choice];
                            const playerChosen = Object.values(this.players)
                                .find(player => player.name == playerChosenName);
                            this.ajaxcall( '/tictacmatch/tictacmatch/playAction.html', {
                                lock: true,
                                cardId: this.selectedCard.id,
                                playerChosen: playerChosen.id
                            }, this, () => {});
                        }
                    );
                    break;
                case 'flip_card':
                case 'double_play_card':
                    if (this.showSkipAction()) {
                        break;
                    }
                default:
                    this.ajaxcall(
                        '/tictacmatch/tictacmatch/playAction.html',
                        {
                            lock: true,
                            cardId: this.selectedCard.id,
                        }, this, () => {}
                    );
                    break;
            }
        },

        onGridCardClick: function (e) {
            if (!this.checkAction('playCard', true)) {
                return;
            }

            if (!this.selectedCard) {
                this.showMessage(_('Please select a card from your hand!'), 'error');
                return;
            }

            if (this.selectedCard.color === 'action') {
                this.showMessage(_('Please select a symbol card!'), 'error');
                return;
            }

            const cell = e.target.dataset;

            // empty cell or same color and not same value
            if (cell.id) {
                if (cell.color === this.selectedCard.color && cell.value === this.selectedCard.value) {
                    this.showMessage(_('You are not allowed to place the same card, only a card at the same color or same value!'), 'error');
                    return;
                }

                if (cell.color !== this.selectedCard.color && cell.value !== this.selectedCard.value) {
                    this.showMessage(_('You need to select a card at the same color or same value!'), 'error');
                    return;
                }
            }

            this.ajaxcall(
                '/tictacmatch/tictacmatch/playCard.html',
                {
                    lock: true,
                    cellLocation: cell.cell,
                    cardId: this.selectedCard.id,
                },
                this, () => {}
            );
        },


        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:

            In this method, you associate each of your game notifications with your local method to handle it.

            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your tictacmatch.game.php file.

        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );

            // Example 1: standard notification handling
            dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            dojo.subscribe( 'actionPlayed', this, "notif_actionPlayed" );
            dojo.subscribe( 'drawCard', this, "notif_drawCard" );
            dojo.subscribe( 'drawSelfCard', this, "notif_drawSelfCard" );
            dojo.subscribe( 'drawSelfCards', this, "notif_drawSelfCards" );
            dojo.subscribe( 'moveCardsToDeck', this, "notif_moveCardsToDeck" );
            dojo.subscribe( 'reShuffleDeck', this, "notif_reShuffleDeck" );
            dojo.subscribe( 'wipedOut', this, "notif_wipedOut" );
            dojo.subscribe( 'newMatch', this, "notif_newMatch" );
            dojo.subscribe( 'endScore', this, "notif_endScore" );
            dojo.subscribe( 'matchScore', this, "notif_endScore" );

            this.notifqueue.setSynchronous("wipedOut", 500);
            this.notifqueue.setSynchronous("drawCard", 800);
            this.notifqueue.setSynchronous("drawSelfCard", 500);
            this.notifqueue.setSynchronous("drawSelfCards", 500);
            this.notifqueue.setSynchronous("moveCardsToDeck", 500);
            this.notifqueue.setSynchronous("reShuffleDeck", 800);
            this.notifqueue.setSynchronous("matchScore", 3000);
            this.notifqueue.setSynchronous("newMatch", 2000);
            this.notifqueue.setSynchronous("endScore", 5000);
        },

        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            const card = notif.args.card;
            const player_id = notif.args.player_id;
            const destinationSelector = `js-board-cell--${notif.args.cell_location}`;
            let cardSelector = `js-card-${card.id}`;
            const isCardAvailable = $(cardSelector);
            const players = notif.args.players;
            this.updatePlayersGamedata(players);
            this.updatePlayerCardsInHand();

            if (!isCardAvailable) {
                //create card on player board;
                const cardDiv = this.getCardDiv(cardSelector, card.id, card);
                dojo.place(cardDiv, `overall_player_board_${player_id}`);
            } else {
                // reset selectedCard
                this.selectedCard = {};
            }

            this.slide(cardSelector, destinationSelector).then(() => {
                this.replaceCardOnCell(notif.args.cell_location, card);
                dojo.destroy(cardSelector);
            });
        },

        notif_actionPlayed: function( notif )
        {
            console.log( 'notif_actionPlayed' );
            console.log( notif );
            const card = notif.args.card;
            const player_id = notif.args.player_id;
            const destinationSelector = 'js-discard-pile-card';
            let cardSelector = `js-card-${card.id}`;
            const isCardAvailable = $(cardSelector);
            const action = notif.args.action;
            const players = notif.args.players;
            this.updatePlayersGamedata(players);
            this.updatePlayerCardsInHand();

            if (!isCardAvailable) {
                //create card on player board;
                const cardDiv = this.getCardDiv(cardSelector, card.id, card);
                dojo.place(cardDiv, `overall_player_board_${player_id}`);
            } else {
                // reset selectedCard
                this.selectedCard = {};
            }

            this.slide(cardSelector, destinationSelector).then(() => {
                this.replaceCardOnDiscardPile(card);
                dojo.destroy(cardSelector);
            });

            switch (action.name) {
                case 'flip_card':
                    this.teams = action.teams;
                    this.flipIDCard();
                    break;
                default:
                    break;
            }
        },

        notif_drawSelfCard: function( notif )
        {
            console.log( 'notif_drawSelfCard' );
            console.log( notif );
            const card = notif.args.card;
            const players = notif.args.players;
            this.addCardToPlayerHand(card, players);
        },

        notif_drawSelfCards: function( notif )
        {
            console.log( 'notif_drawSelfCards' );
            console.log( notif );
            const cards = notif.args.cards;
            const players = notif.args.players;
            const self = this;
            cards.forEach((card) => {
                const duration = self.randomInteger()
                self.addCardToPlayerHand(card, players, duration);
            });

            // update teams, and player data
            this.updatePlayersGamedata(players);
            this.flipIDCard();
        },

        notif_drawCard: function( notif )
        {
            console.log( 'notif_drawCard' );
            console.log( notif );
            const players = notif.args.players;
            this.updatePlayersGamedata(players);
            this.updatePlayerCardsInHand();

            // Update number of cards on deck
            this.setNumberOfCardsOnBadge(notif.args.totalcardsondeck, 'js-deck-badge');
            if (notif.args.totalcardsondiscardpile) {
                this.setNumberOfCardsOnBadge(notif.args.totalcardsondiscardpile, 'js-discard-pile-badge');
            }

            if (this.isCurrentPlayerActive()) {
                this.addClassFromSelector('#js-hand__cards .card', 'card--selectable');
            } else {
                this.removeClassFromSelector('#js-hand__cards .card', 'card--selectable');
            }
        },

        notif_moveCardsToDeck: function( notif )
        {
            console.log( 'notif_moveCardsToDeck' );
            console.log( notif );
            const from = notif.args.from;
            const to = notif.args.to;
            const totalOfCards = notif.args.totalOfCards;

            this.moveMultipleCards(from, to, totalOfCards);
            this.updatePlayerCardsInHand();
        },

        notif_reShuffleDeck: function( notif )
        {
            console.log( 'notif_reShuffleDeck' );
            console.log( notif );

            // Update number of cards on deck
            this.setNumberOfCardsOnBadge(notif.args.totalcardsondeck, 'js-deck-badge');
            dojo.setAttr('js-deck', 'class', 'card card--back');
            if (notif.args.totalcardsondiscardpile) {
                this.setNumberOfCardsOnBadge(notif.args.totalcardsondiscardpile, 'js-discard-pile-badge');
                dojo.setAttr('js-deck', 'class', 'card card--back');
            }
            this.updatePlayerCardsInHand();
        },

        notif_wipedOut: function ( notif )
        {
            console.log( 'notif_wipedOut' );
            console.log( notif );
            const cards = notif.args.cards;
            const destinationSelector = 'js-discard-pile-card';
            const player_id = notif.args.player_id;

            Object.values(cards).forEach((card) => {
                const duration = this.randomInteger();
                let cardSelector = `js-card-${card.id}`;
                const isCardAvailable = $(cardSelector);
                // wipe all current cards
                if (!isCardAvailable) {
                    //create card on player board;
                    const cardDiv = this.getCardDiv(cardSelector, card.id, card);
                    dojo.place(cardDiv, `overall_player_board_${player_id}`);
                } else {
                    // reset selectedCard
                    this.selectedCard = {};
                }

                this.slide(cardSelector, destinationSelector, { duration: duration }).then(() => {
                    this.replaceCardOnDiscardPile(card);
                    dojo.destroy(cardSelector);
                });

                this.setNumberOfCardsOnBadge(notif.args.totalcardsondiscardpile, 'js-discard-pile-badge');
                if (this.isCurrentPlayerActive()) {
                    this.addClassFromSelector('#js-hand__cards .card', 'card--selectable');
                } else {
                    this.removeClassFromSelector('#js-hand__cards .card', 'card--selectable');
                }
            });
            this.updatePlayerCardsInHand();
        },

        notif_endScore: function ( notif )
        {
            console.log( 'notif_endScore' );
            console.log( notif );

            const winner_matches = notif.args.winner_matches;
            const players = notif.args.players;
            for (let player_id in players ) {
                const player = players[player_id];
                this.playerScoreCounters[player_id].toValue(player.score);
            }

            setTimeout(() => {
                for (let cell of winner_matches) {
                    this.addClassFromSelector('#js-board-cell--' + cell, 'card--selected');
                }
            }, 2000);
        },

        notif_newMatch: function ( notif )
        {
            console.log( 'notif_newMatch' );
            console.log( notif );
            const self = this;
            const totalCardsOnPreviousDiscardPile = notif.args.totalCardsOnPreviousDiscardPile;
            const totalcardsondeck = notif.args.totalcardsondeck;
            const totalcardsondiscardpile = notif.args.totalcardsondiscardpile;
            const initialCard = notif.args.initialCard;
            const discardCard = notif.args.discardCard;

            self.moveMultipleCards('js-discard-pile-card', 'js-deck', totalCardsOnPreviousDiscardPile);

            const currentPlayerCards = Array.from(document.querySelectorAll('#js-hand__cards [data-id]'));
            currentPlayerCards.forEach(card => {
                const cardSelector = card.id;
                const duration = self.randomInteger();

                self.slide(cardSelector, 'js-deck', { duration: duration }).then(() => {
                    dojo.destroy(cardSelector);
                });
            });

            const currentGridCards = Array.from(document.querySelectorAll('.ttm-board-grid [data-id]'));
            currentGridCards.forEach(card => {
                self.moveMultipleCards(card.id, 'js-deck', 1);

                // Add event listeners to Grid cells
                Array.from(document.querySelectorAll('[id^="js-board-cell--"]')).forEach(function(el) {
                    self.removeTooltip(card.id);
                    self.clearCardAttributes(card.id);

                    self.addTooltip(el.id, _('Card space'), _('Click to place card'));
                    dojo.connect($(el.id), 'onclick', self, self.onGridCardClick);
                });
            });
            self.setNumberOfCardsOnBadge(totalcardsondeck, 'js-deck-badge');

            // initialCard
            self.replaceCardOnCell(4, initialCard);

            // discardPile
            if (discardCard) {
                self.replaceCardOnDiscardPile(discardCard);
            } else {
                self.clearCardAttributes('js-discard-pile-card')
            }
            self.setNumberOfCardsOnBadge(totalcardsondiscardpile, 'js-discard-pile-badge');
        },

       /***********************************
       ************* Settings ************
       ***********************************/

      setupSettings() {
        dojo.place(
          this.format_string(jstpl_configPlayerBoard, {
            cardSize: _('Size of cards'),
          }),
          'player_boards',
          'first',
        );
        dojo.connect($('show-settings'), 'click', () => this.toggleSettings());
        this.addTooltip('show-settings', '', _('Display some settings about the game.'));

        this._cardScaleSlider = document.getElementById('layout-control-card-size');
        noUiSlider.create(this._cardScaleSlider, {
          start: [this._cardScale],
          step: 2   ,
          padding: 2,
          range: {
            min: [20],
            max: [70],
          },
        });
        this._cardScaleSlider.noUiSlider.on('slide', (arg) => this.setCardScale(parseInt(arg[0])));
        this.setCardScale(this._cardScale);
      },



    /* Helper to work with local storage */
    getConfig(value, v) {
        return localStorage.getItem(value) == null ? v : localStorage.getItem(value);
    },

      /**
       * Change the scale for playing cards
       */
      setCardScale(scale) {
        this._cardScale = scale;
        $('ebd-body').style.setProperty('--tictacmatchCardScale', scale / 100);
        localStorage.setItem('tictacmatchCardScale', scale);
      },

      /**
       * toggleSettings: open/close the settings inside the panel
       */
       toggleSettings(container = 'settings-controls-container') {
        dojo.toggleClass(container, 'settingsControlsHidden');

        // Hacking BGA framework
        if (dojo.hasClass('ebd-body', 'mobile_version')) {
          dojo.query('.player-board').forEach((elt) => {
            if (elt.style.height != 'auto') {
              dojo.style(elt, 'min-height', elt.style.height);
              elt.style.height = 'auto';
            }
          });
        }
      },
   });
});
