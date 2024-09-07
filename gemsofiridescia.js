/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GemsOfIridescia implementation : © Matheus Gomes matheusgomesforwork@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.tilesBoardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gemsofiridescia.js
 *
 * GemsOfIridescia user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
  "dojo",
  "dojo/_base/declare",
  "ebg/core/gamegui",
  "ebg/counter",
  g_gamethemeurl + "modules/bga-zoom.js",
  g_gamethemeurl + "modules/bga-cards.js",
], function (dojo, declare) {
  return declare("bgagame.gemsofiridescia", ebg.core.gamegui, {
    constructor: function () {
      console.log("gemsofiridescia constructor");

      this.goiGlobals = {};
      this.goiManagers = {};
      this.goiStocks = {};
    },

    setup: function (gamedatas) {
      console.log("Starting game setup");

      this.goiGlobals.players = gamedatas.players;
      this.goiGlobals.tilesBoard = gamedatas.tilesBoard;
      this.goiGlobals.explorers = gamedatas.explorers;

      this.goiManagers.zoom = new ZoomManager({
        element: document.getElementById("goi_gameArea"),
        localStorageZoomKey: "gemsofiridescia-zoom",
        zoomControls: {
          color: "white",
        },
        autoZoom: {
          expectedWidth: 740,
        },
      });

      this.goiManagers.tiles = new CardManager(this, {
        getId: (card) => `tile-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_tile");
          div.style.position = "relative";
        },
        setupFrontDiv: (card, div) => {
          let backgroundPosition = this.calcBackgroundPosition(
            (Number(card.type) - 1) * 14
          );

          if (card.type_arg) {
            backgroundPosition = this.calcBackgroundPosition(
              Number(card.type_arg) + (Number(card.type) - 1)
            );
          }

          div.style.backgroundPosition = backgroundPosition;
        },
        setupBackDiv: (card, div) => {
          const backgroundPosition = this.calcBackgroundPosition(
            (Number(card.type) - 1) * 14
          );
          div.style.backgroundPosition = backgroundPosition;
        },
      });

      this.goiManagers.explorers = new CardManager(this, {
        getId: (card) => `explorer-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_explorer");
          div.style.position = "relative";
        },
        setupFrontDiv: (card, div) => {
          let backgroundPosition = this.calcBackgroundPosition(
            Number(card.type) - 1
          );

          div.style.backgroundPosition = backgroundPosition;
        },
        setupBackDiv: (card, div) => {},
      });

      /* create board */

      for (let row = 1; row <= 9; row++) {
        /* tiles */
        const tileRow = `tileRow-${row}`;
        this.goiStocks[tileRow] = new CardStock(
          this.goiManagers.tiles,
          document.getElementById(`goi_tileRow-${row}`),
          {}
        );

        const tilesBoard = this.goiGlobals.tilesBoard;

        for (const card_id in tilesBoard) {
          const card = tilesBoard[card_id];
          const hex = card.location_arg;

          if (this.getTileRow(card.type, hex) === row) {
            delete tilesBoard[card_id];

            this.goiStocks[tileRow].addCard(card).then(() => {
              this.goiStocks[tileRow].setCardVisible(card, false);
            });
          }
        }
      }

      this.goiStocks.explorersGrid = new CardStock(
        this.goiManagers.explorers,
        document.getElementById("goi_explorersGrid"),
        {}
      );

      for (const card_id in this.goiGlobals.explorers) {
        const explorer = this.goiGlobals.explorers[card_id];

        if (explorer["location"] === "board") {
          const tile = explorer["location_arg"];

          this.goiStocks.explorersGrid.addCard(
            explorer,
            {},
            {
              forceToElement: document.getElementById(`tile-${tile}`),
            }
          );
        }
      }

      for (const player_id in this.goiGlobals.players) {
        document.getElementById(
          "goi_playerBoards"
        ).innerHTML += `<div id=goi_playerBoard-${player_id} class="goi_playerBoard">
        <div id="goi_explorerScene-${player_id} class="goi_explorerScene"></div>
        </div>`;

        const explorerCargo = `explorerCargo-${player_id}`;
        this.goiStocks[explorerCargo] = new CardStock(
          this.goiManagers.explorers,
          document.getElementById("goi_explorerScene"),
          {}
        );

        for (const card_id in this.goiGlobals.explorers) {
          const explorer = this.goiGlobals.explorers[card_id];
          if (
            explorer["location"] === "scene" &&
            explorer["type_arg"] === player_id
          ) {
            this.goiStocks[explorerCargo].addCard(explorer);
          }
        }
      }

      this.setupNotifications();

      console.log("Ending game setup");
    },

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    onEnteringState: function (stateName, args) {
      console.log("Entering state: " + stateName, args);

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */

        case "dummmy":
          break;
      }
    },

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */

        case "dummmy":
          break;
      }
    },

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName, args);

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case "playerTurn":
            const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn

            // Add test action buttons in the action status bar, simulating a card click:
            playableCardsIds.forEach((cardId) =>
              this.addActionButton(
                `actPlayCard${cardId}-btn`,
                _("Play card with id ${card_id}").replace("${card_id}", cardId),
                () => this.onCardClick(cardId)
              )
            );

            this.addActionButton(
              "actPass-btn",
              _("Pass"),
              () => this.bgaPerformAction("actPass"),
              null,
              null,
              "gray"
            );
            break;
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    calcBackgroundPosition: function (spritePos) {
      return -spritePos * 100 + "% 0%";
    },

    getTileRow: function (terrain, hex) {
      let row = 1 + 2 * (Number(terrain) - 1);

      if (Number(hex) >= 7) {
        row++;
      }

      return row;
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

    // Example:

    onCardClick: function (card_id) {
      console.log("onCardClick", card_id);

      this.bgaPerformAction("actPlayCard", {
        card_id,
      }).then(() => {
        // What to do after the server call if it succeeded
        // (most of the time, nothing, as the game will react to notifs / change of state instead)
      });
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your gemsofiridescia.game.php file.
        
        */
    setupNotifications: function () {
      console.log("notifications subscriptions setup");

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
