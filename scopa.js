/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * scopa implementation : © Jacques de Metz <demetz.jacques@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

define([
        'dojo', 'dojo/_base/declare',
        'ebg/core/gamegui',
        'ebg/counter',
        'ebg/stock'
    ],
    function(dojo, declare) {
        return declare('bgagame.scopa', ebg.core.gamegui, {
            constructor: function() {
                // Initially, player hand is empty
                this.playerCards = null;
                this.tableCards = null;
                this.playedCard = null;
                // Card size
                this.cardwidth = 68;
                this.cardheight = 111;

                if (window.matchMedia("(min-width: 1500px)").matches) {
                    this.cardwidth = 132;
                    this.cardheight = 219;
                }
                this.dontPreloadImage('cards_standard.jpg');
                this.dontPreloadImage('cards_italian.jpg');

                this.deckCardClasses = ['scp_standard_deck', 'scp_italian_deck'];
            },

            // Initial setup
            // Define player boards, player & table "hands", notifications and the capture zone
            setup: function(gamedatas) {
                // Setting up player boards
                for (var playerId in gamedatas.players) {
                    var player = gamedatas.players[playerId];

                    if (gamedatas.players[player.id].team_id == "0")
                        gamedatas.players[player.id].team_id = '';

                    // Setting up players boards
                    var player_board_div = $('player_board_' + playerId);
                    dojo.place(this.format_block('jstpl_player_board', {
                        player_id: player.id,
                        card_count: gamedatas.players_hand[player.id],
                        team_id: gamedatas.players[player.id].team_id,
                    }), player_board_div);

                    var tooltipText = dojo.string.substitute(_("This player belongs to team ${team}"), {
                        'team': gamedatas.players[player.id].team_id,
                    });
                    this.addTooltip('cp_team_' + player.id, tooltipText, '')
                }
                $('deckcard').innerHTML = gamedatas.players_hand.deck;


                /************* Setup Stock for Player & Table hands ****************/
                this.playerCards = new ebg.stock();
                this.tableCards = new ebg.stock();

                var stockHands = [this.playerCards, this.tableCards];

                for (i in stockHands) {
                    var stockHand = stockHands[i];

                    stockHand.item_margin = 10;
                    stockHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
                    stockHand.autowidth = true;
                    // Do not allow any selection now, even for player. It'll be done by playerTurn state
                    stockHand.setSelectionMode(0);

                    // Card images
                    stockHand.image_items_per_row = 10;
                    for (var color = 1; color <= 4; color++) {
                        for (var value = 1; value <= 10; value++) {
                            // Build card type id
                            var cardFaceId = this.getCardFacePosition(color, value);
                            stockHand.addItemType(cardFaceId, cardFaceId, '', cardFaceId);
                        }
                    }
                }

                // Final setup of Player hand
                // Setup card UI location
                this.playerCards.create(this, $('myhandcards'), this.cardwidth, this.cardheight);
                // Hand contents
                this.updateCardsInHand(gamedatas.hand);
                // Allow to play!
                dojo.connect(this.playerCards, 'onChangeSelection', this, 'onSelectCard');

                // Final setup of Table hand
                // Setup card UI location
                this.tableCards.create(this, $('tablehandcards'), this.cardwidth, this.cardheight);
                // Table contents
                this.updateCardsOnTable(gamedatas.table);

                /************* Misc ****************/

                // User preferences
                this.setupUserPreferences();

                // Setup game notifications to handle
                this.setupNotifications();

                // Initialize array for captures & hide capture choices
                this.cardCaptures = [];
                dojo.fadeOut({
                    node: 'capturechoice',
                    duration: 0
                }).play();
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // Display or hide capture options upon player turn
            onEnteringState: function(stateName, args) {
                switch (stateName) {
                    case 'playerTurn':
                        // Can player select a card?
                        if (this.isCurrentPlayerActive()) {
                            this.cardCaptures = args.args.cardCaptures;
                            this.displayCaptureCards();
                            this.playerCards.setSelectionMode(1);
                        } else {
                            this.hideCaptureCards();
                            this.playerCards.setSelectionMode(0);
                        }
                        this.hideCaptureOptions();
                        break;
                }
            },

            // Hide capture options
            onLeavingState: function(stateName) {
                switch (stateName) {
                    case 'playerTurn':
                        this.playerCards.setSelectionMode(0); // Disable selection
                        this.hideCaptureOptions();
                        break;
                }
            },

            // Not used
            onUpdateActionButtons: function(stateName, args) {},

            ///////////////////////////////////////////////////
            //// Utility methods

            // Converts the card's color+value to a position in the cards.png file
            getCardFacePosition: function(color, value) {
                return (parseInt(color) - 1) * 10 + (parseInt(value) - 1);
            },

            // Converts the card's position in the cards.png file to a color+value
            getCardColorAndValue: function(cardFaceId) {
                return {
                    'color': Math.floor(cardFaceId / 10) + 1,
                    'value': cardFaceId % 10 + 1,
                }
            },

            ///////////////////////////////////////////////////
            //// Card display & manipulation

            // Display cards from Stock properly (with card name, user prefs, ...)
            setupNewCard: function(card_div, card_type_id, card_id) {
                var card = this.getCardColorAndValue(card_type_id);
                var text = dojo.string.substitute(_("${value} of ${suit}"), {
                    'value': this.gamedatas.values_label[card.value],
                    'suit': this.gamedatas.colors[card.color].name,
                });

                var tooltipHideClass = dojo.query("#preference_control_100")[0].value == "2" ? 'scp_hidden' : '';
                dojo.place(this.format_block('jstpl_card_label', {
                    'card_label': text,
                    added_classes: tooltipHideClass
                }), card_div.id);

                // Apply user display preferences
                dojo.query(card_div).addClass(this.getCardDeckClass());
            },

            // Displays a card in a given element
            renderCard: function(card, position) {
                return dojo.place(
                    this.format_block(
                        'jstpl_card', {
                            card_id: card.id,
                            x: this.cardwidth * (parseInt(card.type_arg) - 1),
                            y: this.cardheight * (parseInt(card.type) - 1),
                            deck_class: this.getCardDeckClass(),
                        }
                    ),
                    position
                );
            },

            // Formats some data about the card
            getCardFromNotif: function(args) {
                return {
                    id: args.card_id,
                    type: args.color,
                    type_arg: args.value,
                    face_id: this.getCardFacePosition(args.color, args.value)
                }
            },

            // Displays card movements (from hand to "card played", then with table cards)
            playCard: function(card, playerId, captures) {
                var animations = [];

                // Define where the card is initially
                // this.player_id == playerId is meant for auto-play
                if (this.isCurrentPlayerActive() || this.player_id == playerId)
                    var source = 'myhandcards';
                else
                    var source = 'overall_player_board_' + playerId;

                var cardPlayed = this.renderCard(card, source);
                var cardCreate = this.slideToObject(cardPlayed, 'cardplayedcard', 750);

                if (this.isCurrentPlayerActive() || this.player_id == playerId)
                    dojo.connect(cardCreate, 'onBegin', () => this.playerCards.removeFromStockById(card.id));

                animations.push(cardCreate);


                // Card played towards table
                if (captures == undefined) {
                    var cardToTable = this.slideToObject(cardPlayed, 'tablehandcards', 750);
                    dojo.connect(cardToTable, 'onEnd', (node) => {
                        dojo.destroy(node);
                        this.tableCards.addToStockWithId(card.face_id, card.id);
                    });
                    animations.push(cardToTable);
                }
                // Card captures
                else {
                    // Remove cards from table
                    for (i in captures) {
                        var cardCaptured = captures[i];
                        var cardCapturedDiv = this.renderCard(cardCaptured, 'tablehandcards_item_' + cardCaptured.id);
                        var cardCapturedAnim = this.slideToObject(cardCapturedDiv, 'cardplayedcard', 750);
                        dojo.connect(cardCapturedAnim, 'onEnd', dojo.destroy);
                        dojo.connect(cardCapturedAnim, 'beforeBegin', (node) => this.tableCards.removeFromStockById(node.dataset.card));
                        animations.push(cardCapturedAnim);
                    }

                    // Card collected by player
                    var cardCapture = this.slideToObject(cardPlayed, source, 750);
                    dojo.connect(cardCapture, 'onEnd', dojo.destroy);
                    animations.push(cardCapture);
                }
                dojo.fx.chain(animations).play();
            },

            ///////////////////////////////////////////////////
            //// Capture zone handling

            // Renders & displays the block to select a capture option
            displayCaptureOptions: function(captures) {
                for (captureId in captures) {
                    var capture = captures[captureId];

                    // Get all cards's IDs (will be used as payload)
                    var cardsIds = capture.cards.map(x => x.id).join(',');

                    // Create the group of cards
                    var captureGroup = dojo.place(
                        this.format_block(
                            'jstpl_capturegroup', {
                                id: captureId,
                                cards_ids: cardsIds
                            }
                        ),
                        'capturechoiceholder'
                    );
                    // Allow interactivity
                    dojo.connect(captureGroup, 'onclick', this, 'onSelectCapture');

                    // Display cards in the group
                    for (i in capture.cards)
                        this.renderCard(capture.cards[i], captureGroup);

                }
                dojo.fadeIn({
                    node: 'capturechoice',
                    duration: 0
                }).play();
            },

            // Hides the block to select a capture option
            hideCaptureOptions: function() {
                dojo.query('.scp_capturegroup').forEach(dojo.destroy);
                dojo.fadeOut({
                    node: 'capturechoice',
                    duration: 0
                }).play();
            },

            // Displays / hides tooltip & dotted border based on which cards can capture
            displayCaptureCards: function() {
                //Adds a tooltip & a class to all cards that can capture
                var myHand = this.playerCards.getAllItems();
                for (i in myHand) {
                    card = myHand[i];
                    if (card.id in this.cardCaptures) {
                        dojo.addClass(this.playerCards.getItemDivId(card.id), 'scp_canCapture');
                    } else {
                        dojo.removeClass(this.playerCards.getItemDivId(card.id), 'scp_canCapture');
                    }
                }
                this.addTooltipToClass('scp_canCapture', '', _('This card can capture cards from the table'));
            },

            // Hides tooltip & dotted border based on which cards can capture
            hideCaptureCards: function() {
                // First, remove all past actions
                // Note: we can't remove tooltips, so it's just set to nothing
                this.addTooltipToClass('scp_canCapture', '', '');
                dojo.query('.scp_canCapture').removeClass('scp_canCapture');
            },

            ///////////////////////////////////////////////////
            //// Update UI from notifications

            // Update card counts
            updateCardsCount: function(counts) {
                for (i in counts)
                    if (i == 'deck')
                        $('deckcard').innerHTML = counts[i];
                    else
                        $('cp_deck_' + i).innerHTML = counts[i];
            },

            // Update cards in hand
            updateCardsInHand: function(cards) {
                for (var i in cards) {
                    var card = cards[i];
                    var color = card.type;
                    var value = card.type_arg;
                    this.playerCards.addToStockWithId(this.getCardFacePosition(color, value), card.id);
                }
            },

            // Update cards on table
            updateCardsOnTable: function(cards) {
                for (var i in cards) {
                    var card = cards[i];
                    var color = card.type;
                    var value = card.type_arg;
                    this.tableCards.addToStockWithId(this.getCardFacePosition(color, value), card.id);
                }
            },

            ///////////////////////////////////////////////////
            //// Player's action

            // Player chooses a card to play
            onSelectCard: function(evt) {
                var items = this.playerCards.getSelectedItems();
                // Player unselected a card or is not allowed to play => Hide capture options
                if (items.length == 0 || this.checkAction('playCard', true) == false) {
                    this.hideCaptureOptions();
                    return;
                }

                var cardId = items[0].id;

                // Card can capture 2 different combinations
                if (cardId in this.cardCaptures && this.cardCaptures[cardId].length > 1) {
                    this.displayCaptureOptions(this.cardCaptures[cardId]);
                }
                // Card can't capture, just sent it to server
                else {
                    this.ajaxcall('/scopa/scopa/playCard.html', {
                        cardId: cardId,
                        cardsCaptured: '',
                        lock: true
                    }, this, function(result) {}, function(is_error) {});
                    this.playerCards.unselectAll();
                    this.hideCaptureOptions();
                }
            },

            // Player chooses what to capture
            onSelectCapture: function(evt) {
                var items = this.playerCards.getSelectedItems();

                if (items.length == 0)
                    return;

                if (this.checkAction('playCard', true) == false)
                    return;

                var cardId = items[0].id;

                // Card can capture different combinations, so we need to send the chosen one
                if (cardId in this.cardCaptures && this.cardCaptures[cardId].length > 1) {
                    var capture = evt.currentTarget.dataset.cards;

                    this.ajaxcall('/scopa/scopa/playCard.html', {
                        cardId: cardId,
                        cardsCaptured: capture,
                        lock: true
                    }, this, function(result) {}, function(is_error) {});
                    this.playerCards.unselectAll();
                    this.hideCaptureOptions();
                }
                // Card can't capture, just sent it to server
                // This shouldn't happen (as the capture groups won't be displayed)
                else {
                    this.ajaxcall('/scopa/scopa/playCard.html', {
                        cardId: cardId,
                        cardsCaptured: '',
                        lock: true
                    }, this, function(result) {}, function(is_error) {});

                    this.playerCards.unselectAll();
                    this.hideCaptureOptions();
                }
            },


            ///////////////////////////////////////////////////
            //// User preferences

            // Defines handlers when user changes values
            setupUserPreferences: function() {
                // Extract the ID and value from the UI control
                var _this = this;

                function onchange(e) {
                    var match = e.target.id.match(/^preference_[cf]ontrol_(\d+)$/);
                    if (!match) {
                        return;
                    }
                    var prefId = +match[1];
                    var prefValue = +e.target.value;
                    _this.prefs[prefId].value = prefValue;
                    _this.onPreferenceChange(prefId, prefValue);
                }

                // Call onPreferenceChange() when any value changes
                dojo.query(".preference_control").connect("onchange", onchange);

                // Call onPreferenceChange() now to initialize the setup
                dojo.forEach(
                    dojo.query("#ingame_menu_content .preference_control"),
                    function(el) {
                        onchange({
                            target: el
                        });
                    }
                );
            },

            onPreferenceChange: function(prefId, prefValue) {
                // Preferences that change display
                switch (prefId) {
                    // Display labels on cards?
                    case 100:
                        // Yes, display them
                        if (prefValue == 1)
                            dojo.query('.scp_card_label').removeClass('scp_hidden')
                        // No, hide them
                        else
                            dojo.query('.scp_card_label').addClass('scp_hidden')
                        break;

                        // Card deck theme
                    case 101:
                        // Remove all existing classes
                        for (i in this.deckCardClasses) {
                            var deckCardClass = this.deckCardClasses[i];
                            dojo.query('.' + deckCardClass).removeClass(deckCardClass);
                        }
                        // Add the new ones
                        dojo.query('.scp_card').addClass(this.getCardDeckClass());
                        dojo.query('.stockitem').addClass(this.getCardDeckClass());

                        /* The below should work, but it doesn't. So I use classes instead
                        var stockHands = [this.playerCards, this.tableCards];
                        var imagePath = this.getCardDeckType();
                        for (var i in stockHands) {
                            var stockHand = stockHands[i];
                            for (var c in stockHand.item_type) {
                                stockHand.item_type[c].image = imagePath;
                            }

                            stockHand.updateDisplay();
                        }*/
                        break;
                }

                // Preferences that need to be sent to server (only if read-write)
                if (!(this.isSpectator || g_archive_mode || typeof g_replayFrom != 'undefined')) {
                    if ([102].includes(prefId)) {
                        this.ajaxcall("/scopa/scopa/setUserPref.html", {
                            pref_id: prefId,
                            pref_value: prefValue,
                        }, () => {});
                    }
                }
            },

            /*getCardDeckType: function() {
                var val = $('preference_control_101').value;
                if (val=== "1") {
                    return g_gamethemeurl + 'img/cards_italian.jpg';
                } else if (val ==="2") {
                    return g_gamethemeurl + 'img/cards_standard.jpg';
                } else {

                }
            },*/

            getCardDeckClass: function() {
                var val = $('preference_control_101').value;
                if (val === "1") {
                    return 'scp_italian_deck';
                } else if (val === "2") {
                    return 'scp_standard_deck';
                } else {
                    return 'scp_italian_deck';
                }
            },


            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            // Notification setup
            setupNotifications: function() {
                dojo.subscribe('cardPlayedToTable', this, 'notif_cardPlayedToTable');
                this.notifqueue.setSynchronous('cardPlayedToTable', 2500);

                dojo.subscribe('cardPlayedAndCapture', this, 'notif_cardPlayedAndCapture');
                this.notifqueue.setSynchronous('cardPlayedAndCapture', 4500);

                dojo.subscribe('cardsCount', this, 'notif_cardsCount');

                dojo.subscribe('cardsInHand', this, 'notif_cardsInHand');

                dojo.subscribe('cardsOnTable', this, 'notif_cardsOnTable');

                dojo.subscribe('playerScores', this, 'notif_playerScores');

                dojo.subscribe('playerCapturesTable', this, 'notif_playerCapturesTable');
                this.notifqueue.setSynchronous('playerCapturesTable', 2000);
            },

            // A player played a card towards the table
            notif_cardPlayedToTable: function(notif) {
                var card = this.getCardFromNotif(notif.args)
                this.playCard(card, notif.args.player_id);
            },

            // A player played and captures one or multiple cards
            notif_cardPlayedAndCapture: function(notif) {
                var card = this.getCardFromNotif(notif.args)
                this.playCard(card, notif.args.player_id, notif.args.capture);
            },

            // At end of deck, a player captures all cards
            notif_playerCapturesTable: function(notif) {
                if (notif.args.player_id == this.playerId)
                    var target = "myhandcards";
                else
                    var target = 'overall_player_board_' + notif.args.player_id;

                this.tableCards.removeAllTo(target);
            },

            // Notify about count of cards in deck and player's hands
            notif_cardsCount: function(notif) {
                this.updateCardsCount(notif.args.count);
            },

            // Notify about cards on the table
            notif_cardsOnTable: function(notif) {
                this.updateCardsOnTable(notif.args.cards);
            },

            // Notify about cards in ative player's hand
            notif_cardsInHand: function(notif) {
                this.updateCardsInHand(notif.args.cards);
            },

            // Notify about scores
            notif_playerScores: function(notif) {
                for (var playerId in notif.args.score) {
                    var newScore = notif.args.score[playerId];
                    this.scoreCtrl[playerId].toValue(newScore);
                }
            },
        });
    });