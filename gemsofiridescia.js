/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GemsOfIridescia implementation : © Matheus Gomes matheusgomesforwork@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
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
      this.goiGlobals.tileBoard = gamedatas.tileBoard;
      this.goiGlobals.playerBoards = gamedatas.playerBoards;
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
        selectedCardClass: "goi_tileSelected",
        setupDiv: (card, div) => {
          div.classList.add("goi_tile");
          div.style.position = "relative";

          div.style.order = card.location_arg;
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

          const spritePosition =
            this.goiGlobals.playerBoards[card.type_arg] - 1;
          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);

          div.style.backgroundPosition = backgroundPosition;
        },
        setupFrontDiv: (card, div) => {},
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

        const tileBoard = this.goiGlobals.tileBoard;

        for (const card_id in tileBoard) {
          const card = tileBoard[card_id];
          const hex = card.location_arg;

          if (this.getTileRow(card.type, hex) === row) {
            delete tileBoard[card_id];

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
        const spritePosition = this.goiGlobals.playerBoards[player_id] - 1;
        const backgroundPosition = this.calcBackgroundPosition(spritePosition);

        document.getElementById(
          "goi_playerBoards"
        ).innerHTML += `<div id="goi_playerBoard-${player_id}" class="goi_playerBoard" style="background-position: ${backgroundPosition}" data-player="${player_id}">
        <div id="goi_explorerScene-${player_id}" class="goi_explorerScene"></div>
        </div>`;

        const explorerScene = `explorerScene-${player_id}`;
        this.goiStocks[explorerScene] = new CardStock(
          this.goiManagers.explorers,
          document.getElementById(`goi_explorerScene-${player_id}`),
          {}
        );

        for (const card_id in this.goiGlobals.explorers) {
          const explorer = this.goiGlobals.explorers[card_id];

          if (
            explorer["location"] === "scene" &&
            explorer["type_arg"] == player_id
          ) {
            this.goiStocks[explorerScene].addCard(explorer);
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

      if (this.isCurrentPlayerActive()) {
        if (stateName === "revealTiles") {
          const revealableTiles = args.args.revealableTiles;

          this.makeAllRowsSelectable();

          for (const row in revealableTiles) {
            const tileRow = `tileRow-${row}`;
            const tileCards = revealableTiles[row];

            this.goiStocks[tileRow].setSelectableCards(tileCards);
          }
        }
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
      }
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    calcBackgroundPosition: function (spritePosition) {
      return -spritePosition * 100 + "% 0%";
    },

    getTileRow: function (region, hex) {
      let row = 1 + 2 * (Number(region) - 1);

      if (Number(hex) >= 7) {
        row++;
      }

      return row;
    },

    makeAllRowsSelectable: function () {
      for (let row = 1; row <= 9; row++) {
        this.goiStocks[`tileRow-${row}`].setSelectionMode("multiple", []);
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
