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

                this.dontPreloadImage('cards_standard.jpg');
                this.dontPreloadImage('cards_italian.jpg');
                this.dontPreloadImage('cards_bergamasche.jpg');
                this.dontPreloadImage('cards_bresciane.jpg');
                this.dontPreloadImage('cards_piacentine.jpg');

                this.deckCardClasses = ['scp_napolitan_deck', 'scp_standard_deck', 'scp_bergamasche_deck', 'scp_bresciane_deck', 'scp_piacentine_deck'];

                dojo.connect(window, 'resize', () => this.resizeBoard());
            },

            // Initial setup
            // Define player boards, player & table "hands", notifications and the capture zone
            setup: function(gamedatas) {
                // Setting up player boards
                for (var playerId in gamedatas.players) {
                    var player = gamedatas.players[playerId];
                    var player_board_div = $('player_board_' + playerId);

                    if (typeof gamedatas.players[playerId].team_id == 'undefined')
                        var ally_name = '';
                    else {
                        var ally_id = gamedatas.players[playerId].ally;
                        var ally_name = gamedatas.players[ally_id].name;
                    }

                    // Set up player board
                    if (player.cards_captured) {
                        dojo.place(this.format_block('jstpl_player_board', {
                            player_id1: playerId,
                            /* Some kind of bug happens if you use multiple times the same variable in the template */
                            player_id2: playerId,
                            player_id3: playerId,
                            player_id4: playerId,
                            card_count: gamedatas.players_hand[playerId],
                            ally_name: ally_name,
                            scopa_in_round: player.scopa_in_round,
                            cards_captured_count: player.cards_captured,
                        }), player_board_div);
                    } else {
                        dojo.place(this.format_block('jstpl_player_board', {
                            player_id1: playerId,
                            /* Some kind of bug happens if you use multiple times the same variable in the template */
                            player_id2: playerId,
                            player_id3: playerId,
                            player_id4: playerId,
                            card_count: gamedatas.players_hand[playerId],
                            ally_name: ally_name,
                            scopa_in_round: player.scopa_in_round,
                            cards_captured_count: 0,
                        }), player_board_div);
                    }

                    this.addTooltip('cp_deck_' + playerId, _('Cards left in hand'), '')
                    this.addTooltip('scp_scopa_points_' + playerId, _('Scopa points marked during this round'), '')
                    this.addTooltip('scp_cards_captured_' + playerId, _('Cards captured during this round'), '')

                    // Team game
                    if (typeof gamedatas.players[playerId].team_id != 'undefined') {
                        var tooltipText = dojo.string.substitute(_("This player plays with ${ally_name}"), {
                            ally_name: ally_name,
                        });
                        this.addTooltip('cp_team_' + playerId, tooltipText, '')
                    }
                }

                $('scp_deckcard').innerHTML = gamedatas.players_hand.deck;


                /************* Setup Stock for Player & Table hands ****************/
                this.playerCards = new ebg.stock();
                this.tableCards = new ebg.stock();

                this.resizeBoard();

                var stockHands = [this.playerCards, this.tableCards];

                for (i in stockHands) {
                    var stockHand = stockHands[i];

                    stockHand.item_margin = 10;
                    stockHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
                    stockHand.centerItems = true;
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
                this.playerCards.create(this, $('scp_myhandcards'), this.cardwidth, this.cardheight);
                // Hand contents
                this.updateCardsInHand(gamedatas.hand);
                // Allow to play!
                dojo.connect(this.playerCards, 'onChangeSelection', this, 'onSelectCard');

                // Final setup of Table hand
                // Setup card UI location
                this.tableCards.create(this, $('scp_tablehandcards'), this.cardwidth, this.cardheight);
                // Table contents
                this.updateCardsOnTable(gamedatas.table);

                /************* Misc ****************/

                // User preferences
                this.setupUserPreferences();

                // Setup game notifications to handle
                this.setupNotifications();

                // Initialize captures & hide capture choices
                this.cardCaptures = [];
                var fadeCapture = dojo.fadeOut({
                    node: 'scp_capturechoice',
                    duration: 0
                });
                dojo.connect(fadeCapture, 'onEnd', (node) => {
                    dojo.query(node).addClass('scp_hidden');
                });
                fadeCapture.play();
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // Display or hide capture options upon player turn
            onEnteringState: function(stateName, args) {
                switch (stateName) {
                    case 'playerTurn':
                        // Can player select a card?
                        if (this.isCurrentPlayerActive()) {
                            this.cardCaptures = args.args._private.cardCaptures;
                            this.displayCaptureCards();
                            this.playerCards.setSelectionMode(1);
                        } else {
                            this.hideCaptureTooltip();
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

            // Display buttons for Cirulla
            onUpdateActionButtons: function(stateName, args) {
                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'cirullaDeclare':
                            this.addActionButton('cirullaDeclare_button', _('Declare Cirulla combination'), () => {
                                this.onCirullaDeclare(args._private.jokerValues);
                            });
                            this.addActionButton('cirullaPass_button', _('Pass'), 'onCirullaPass');
                            break;
                    }
                }
            },

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
                    'value': _(this.gamedatas.values_label[card.value]),
                    'suit': _(this.gamedatas.colors[card.color].name),
                });

                var tooltipHideClass = dojo.query("#preference_control_100")[0].value == "2" ? 'scp_hidden' : '';
                dojo.place(this.format_block('jstpl_card_label', {
                    card_label: text,
                    added_classes: tooltipHideClass
                }), card_div.id);

                // Apply user display preferences
                dojo.query(card_div).addClass(this.getCardDeckClass());
            },

            // Displays a card in a given element
            renderCard: function(card, position) {
                var text = dojo.string.substitute(_("${value} of ${suit}"), {
                    'value': _(this.gamedatas.values_label[card.type_arg]),
                    'suit': _(this.gamedatas.colors[card.type].name),
                });
                var tooltipHideClass = dojo.query("#preference_control_100")[0].value == "2" ? 'scp_hidden' : '';
                return dojo.place(
                    this.format_block(
                        'jstpl_card', {
                            card_id: card.id,
                            x: this.cardwidth * (parseInt(card.type_arg) - 1),
                            y: this.cardheight * (parseInt(card.type) - 1),
                            deck_class: this.getCardDeckClass(),
                            card_label: text,
                            added_classes: tooltipHideClass
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
                    var source = 'scp_myhandcards';
                else if (this.isPlayerSeatDisplayed())
                    var source = 'scp_player_seat_' + playerId + '_cards';
                else
                    var source = 'overall_player_board_' + playerId;

                // Define where the card stays during capture
                // this.player_id == playerId is meant for auto-play
                if (!this.isPlayerSeatDisplayed())
                    var waiting_zone = 'scp_cardplayedcard';
                else
                    var waiting_zone = source;

                var cardPlayed = this.renderCard(card, source);
                var cardCreate = this.slideToObject(cardPlayed, waiting_zone, 750 / this.animationSpeed);


                if (this.isCurrentPlayerActive() || this.player_id == playerId)
                    dojo.connect(cardCreate, 'onBegin', () => this.playerCards.removeFromStockById(card.id));

                animations.push(cardCreate);


                // Card played towards table
                if (captures == undefined) {
                    var cardToTable = this.slideToObject(cardPlayed, 'scp_tablehandcards', 750 / this.animationSpeed);
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
                        var cardCapturedDiv = this.renderCard(cardCaptured, 'scp_tablehandcards_item_' + cardCaptured.id);
                        var cardCapturedAnim = this.slideToObject(cardCapturedDiv, waiting_zone, 750 / this.animationSpeed);
                        dojo.connect(cardCapturedAnim, 'onEnd', dojo.destroy);
                        dojo.connect(cardCapturedAnim, 'beforeBegin', (node) => this.tableCards.removeFromStockById(node.dataset.card));
                        animations.push(cardCapturedAnim);
                    }

                    // Card collected by player
                    var cardCapture = this.slideToObject(cardPlayed, source, 750 / this.animationSpeed);
                    dojo.connect(cardCapture, 'onEnd', dojo.destroy);
                    animations.push(cardCapture);
                }
                dojo.fx.chain(animations).play();
            },

            // Resizes the board and cards based on the screen size
            resizeBoard: function() {
                var players_visible = dojo.query("#preference_control_103")[0].value == "1" ? 'yes' : 'no';

                var card_sizes = {
                    's': {
                        'yes': [68, 111], // Players visible
                        'no': [68, 111],
                    },
                    'm': {
                        'yes': [68, 111],
                        'no': [100, 165],
                    },
                    'l': {
                        'yes': [100, 165],
                        'no': [132, 219],
                    },
                };

                // Card size
                this.cardwidth = 68;
                this.cardheight = 111;

                if (window.matchMedia("(max-width: 1350px)").matches)
                    var size = 's';
                else if (window.matchMedia("(min-width: 1350px) and (max-width: 1900px)").matches)
                    var size = 'm';
                else if (window.matchMedia("(min-width: 1900px)").matches)
                    var size = 'l';


                var size_to_use = card_sizes[size][players_visible];

                // Card size
                this.cardwidth = size_to_use[0];
                this.cardheight = size_to_use[1];


                var stockHands = [this.playerCards, this.tableCards];

                for (i in stockHands) {
                    var stockHand = stockHands[i];

                    stockHand.item_height = this.cardheight;
                    stockHand.item_width = this.cardwidth;

                    stockHand.updateDisplay();;
                }

                if (window.matchMedia("(max-width: 1030px)").matches) {
                    dojo.place('scp_deck', 'scp_table', 'after');
                    dojo.place('scp_cardplayed', 'scp_deck', 'after');
                } else {
                    dojo.place('scp_deck', 'scp_table', 'before');
                    dojo.place('scp_cardplayed', 'scp_deck', 'after');
                }
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
                        'scp_capturechoiceholder'
                    );
                    // Allow interactivity
                    dojo.connect(captureGroup, 'onclick', this, 'onSelectCapture');

                    // Display cards in the group
                    for (i in capture.cards)
                        this.renderCard(capture.cards[i], captureGroup);

                }
                var fadeCapture = dojo.fadeIn({
                    node: 'scp_capturechoice',
                    duration: 0
                });
                dojo.connect(fadeCapture, 'beforeBegin', (node) => {
                    dojo.query(node).removeClass('scp_hidden');
                });
                fadeCapture.play();
            },

            // Hides the block to select a capture option
            hideCaptureOptions: function() {
                dojo.query('.scp_capturegroup').forEach(dojo.destroy);
                var fadeCapture = dojo.fadeOut({
                    node: 'scp_capturechoice',
                    duration: 0
                });
                dojo.connect(fadeCapture, 'onEnd', (node) => {
                    dojo.query(node).addClass('scp_hidden');
                });
                fadeCapture.play();
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
            hideCaptureTooltip: function() {
                // Note: we can't remove tooltips, so it's just set to nothing
                this.addTooltipToClass('scp_canCapture', '', '');
                dojo.query('.scp_canCapture').removeClass('scp_canCapture');
            },

            ///////////////////////////////////////////////////
            //// Update UI from notifications

            // Update card counts
            updateCardsCount: function(counts) {
                for (type in counts)
                    if (type == 'deck')
                        $('scp_deckcard').innerHTML = counts[type];
                    else if (type == 'capture') {
                    for (playerId in counts[type])
                        $('scp_cards_captured_' + playerId).innerHTML = counts[type][playerId];
                } else if (type == 'hand') {
                    for (playerId in counts[type])
                        $('cp_deck_' + playerId).innerHTML = counts[type][playerId];
                }
                // Backwards compatibility: type = player ID
                else
                    $('cp_deck_' + type).innerHTML = counts[type];
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
                    this.hideCaptureTooltip();
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

                    this.hideCaptureTooltip();
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
                    this.hideCaptureTooltip();
                    this.ajaxcall('/scopa/scopa/playCard.html', {
                        cardId: cardId,
                        cardsCaptured: '',
                        lock: true
                    }, this, function(result) {}, function(is_error) {});

                    this.playerCards.unselectAll();
                    this.hideCaptureOptions();
                }
            },

            // Player declares his Cirulla combination
            onCirullaDeclare: function(jokerValues) {
                if (this.checkAction('cirullaDeclare', true) == false)
                    return;

                if (jokerValues.length == 0) {
                    this.ajaxcall('/scopa/scopa/cirullaDeclare.html', {
                        lock: true
                    }, this, function(result) {}, function(is_error) {});
                } else if (jokerValues.length == 1) {
                    this.ajaxcall('/scopa/scopa/cirullaDeclare.html', {
                        lock: true,
                        jokerValue: jokerValues[0]
                    }, this, function(result) {}, function(is_error) {});
                } else {
                    this.multipleChoiceDialog(
                        _('What should be the value of the 7 of cups?'), jokerValues,
                        dojo.hitch(this, function(choice) {
                            var jokerValue = jokerValues[choice];
                            this.ajaxcall('/scopa/scopa/cirullaDeclare.html', {
                                lock: true,
                                jokerValue: jokerValue
                            }, this, function(result) {});
                        }));
                }
            },

            // Player does not his Cirulla combination
            onCirullaPass: function(evt) {
                if (this.checkAction('cirullaPass', true) == false)
                    return;

                this.ajaxcall('/scopa/scopa/cirullaPass.html', {
                    lock: true
                }, this, function(result) {}, function(is_error) {});
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
                    dojo.query('#preference_control_' + prefId)[0].value = prefValue;
                    dojo.query('#preference_fontrol_' + prefId)[0].value = prefValue;
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

                        // Display opponents
                    case 103:
                        // Yes, display them
                        if (prefValue == 1) {
                            dojo.query('body').removeClass('scp_players_hidden'); /* Ensure styles are applied everywhere */
                            dojo.query('body').addClass('scp_players_visible');
                            dojo.query('#scp_board').removeClass('scp_players_hidden'); /* For querying */
                            dojo.query('#scp_board').addClass('scp_players_visible');
                        }
                        // No, hide them
                        else {
                            dojo.query('body').removeClass('scp_players_visible');
                            dojo.query('body').addClass('scp_players_hidden');
                            dojo.query('#scp_board').removeClass('scp_players_visible');
                            dojo.query('#scp_board').addClass('scp_players_hidden');
                        }
                        this.resizeBoard();
                        break;


                        // Display information banner
                    case 104:
                        // Yes, display it
                        if (prefValue == 1) {
                            dojo.query('body').removeClass('scp_notice_hidden'); /* Ensure styles are applied everywhere */
                            dojo.query('body').addClass('scp_notice_visible');
                        }
                        // No, hide it
                        else {
                            dojo.query('body').removeClass('scp_notice_visible');
                            dojo.query('body').addClass('scp_notice_hidden');
                        }
                        this.resizeBoard();
                        break;

                        // Animation speed
                    case 105:
                        this.animationSpeed = prefValue;
                        break;
                }

                // Preferences that need to be sent to server (only if needed in PHP code)
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
                var val = parseInt($('preference_control_101').value);
                if (val > 0 && val <= this.deckCardClasses.length) {
                    return this.deckCardClasses[val - 1];
                } else {
                    return 'scp_italian_deck';
                }
            },

            isPlayerSeatDisplayed: function() {
                if (window.matchMedia("(max-width: 1030px)").matches)
                    return false;

                return dojo.hasClass('scp_board', 'scp_players_visible');
            },


            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            // Notification setup
            setupNotifications: function() {
                dojo.subscribe('cardPlayedToTable', this, 'notif_cardPlayedToTable');
                this.notifqueue.setSynchronous('cardPlayedToTable', 2500 / this.animationSpeed);

                dojo.subscribe('cardPlayedAndCapture', this, 'notif_cardPlayedAndCapture');
                this.notifqueue.setSynchronous('cardPlayedAndCapture', 4500 / this.animationSpeed);

                dojo.subscribe('cardsCount', this, 'notif_cardsCount');

                dojo.subscribe('cardsInHand', this, 'notif_cardsInHand');

                dojo.subscribe('cardsOnTable', this, 'notif_cardsOnTable');

                dojo.subscribe('playerScores', this, 'notif_playerScores');

                dojo.subscribe('playerCapturesTable', this, 'notif_playerCapturesTable');
                this.notifqueue.setSynchronous('playerCapturesTable', 2000 / this.animationSpeed);
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
                    var target = 'scp_myhandcards';
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
                    var scoreData = notif.args.score[playerId];
                    this.scoreCtrl[playerId].toValue(scoreData.player_score);
                    $('scp_scopa_points_' + playerId).innerHTML = scoreData.scopa_in_round;
                }
            },
        });
    });