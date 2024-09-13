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
      this.goiCounters = {};
    },

    setup: function (gamedatas) {
      console.log("Starting game setup");

      this.goiGlobals.players = gamedatas.players;
      this.goiGlobals.player = gamedatas.players[this.player_id];
      this.goiGlobals.tileBoard = gamedatas.tileBoard;
      this.goiGlobals.playerBoards = gamedatas.playerBoards;
      this.goiGlobals.revealedTiles = gamedatas.revealedTiles;
      this.goiGlobals.explorers = gamedatas.explorers;
      this.goiGlobals.gems = gamedatas.gems;
      this.goiGlobals.selectedTile = null;

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
          div.classList.add("goi_tileSide");

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
          div.classList.add("goi_tileSide");

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

      /* PLAYER PANELS */
      this.goiCounters.gems = {};
      for (const player_id in this.goiGlobals.players) {
        document.getElementById(
          `player_board_${player_id}`
        ).innerHTML += `<div id="goi_playerPanel:${player_id}" class="goi_playerPanel">
            <div id="goi_gemCounters:${player_id}" class="goi_gemCounters">
              <div class="goi_gemCounter">
                <div class="goi_gemIcon" data-gem="amethyst"></div>
                <span id="goi_gemCounter:${player_id}-amethyst">0</span>
              </div>
              <div class="goi_gemCounter">
                <div class="goi_gemIcon" data-gem="citrine"></div>
                <span id="goi_gemCounter:${player_id}-citrine">0</span>
              </div>
              <div class="goi_gemCounter">
                <div class="goi_gemIcon" data-gem="emerald"></div>
                <span id="goi_gemCounter:${player_id}-emerald">0</span>
              </div>
              <div class="goi_gemCounter">
                <div class="goi_gemIcon" data-gem="sapphire"></div>
                <span id="goi_gemCounter:${player_id}-sapphire">0</span>
              </div>
            </div>
          </div>`;

        this.goiCounters.gems[player_id] = {
          amethyst: new ebg.counter(),
          citrine: new ebg.counter(),
          emerald: new ebg.counter(),
          sapphire: new ebg.counter(),
        };

        const gemCounters = this.goiCounters.gems[player_id];

        for (const gem in gemCounters) {
          const gemCounter = gemCounters[gem];
          gemCounter.create(`goi_gemCounter:${player_id}-${gem}`);
          gemCounter.setValue(this.goiGlobals.gems[player_id][gem]);
        }
      }

      /* BOARDS */
      for (let row = 1; row <= 9; row++) {
        /* tiles */
        const tileRow = `tileRow-${row}`;

        this.goiStocks[tileRow] = new CardStock(
          this.goiManagers.tiles,
          document.getElementById(`goi_tileRow-${row}`),
          {}
        );

        this.goiStocks[tileRow].onSelectionChange = (selected, lastChange) => {
          const stateName = this.getStateName();

          if (stateName === "revealTile" || stateName) {
            if (selected.length === 0) {
              this.goiGlobals.selectedTile = null;
            } else {
              this.goiGlobals.selectedTile = lastChange;
              this.unselectAllStocks(
                this.goiManagers.tiles,
                this.goiStocks[tileRow]
              );
            }

            this.handleConfirmationButton();
          }
        };

        const tileBoard = this.goiGlobals.tileBoard;
        for (const tileCard_id in tileBoard) {
          const tileCard = tileBoard[tileCard_id];

          if (tileCard.location == row) {
            delete tileBoard[tileCard_id];

            this.goiStocks[tileRow].addCard(tileCard).then(() => {
              this.goiStocks[tileRow].setCardVisible(tileCard, false);

              const revealedTileCard =
                this.goiGlobals.revealedTiles[tileCard_id];
              if (revealedTileCard) {
                this.goiStocks[tileRow].flipCard(revealedTileCard);
              }
            });
          }
        }
      }

      this.goiStocks.explorersGrid = new CardStock(
        this.goiManagers.explorers,
        document.getElementById("goi_explorersGrid"),
        {}
      );

      for (const explorerCard_id in this.goiGlobals.explorers) {
        const explorerCard = this.goiGlobals.explorers[explorerCard_id];

        if (explorerCard["location"] === "board") {
          this.goiStocks.explorersGrid.addCard(
            explorerCard,
            {},
            {
              forceToElement: document.getElementById(
                `tile-${explorerCard["location_arg"]}`
              ),
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
        if (stateName === "revealTile") {
          const revealableTiles = args.args.revealableTiles;
          const revealsLimit = args.args.revealsLimit;
          const skippable = args.args.skippable;

          if (revealsLimit === 1) {
            this.gamedatas.gamestate.description = _(
              "${actplayer} may reveal another tile"
            );
            this.gamedatas.gamestate.descriptionmyturn = _(
              "${you} may reveal another tile"
            );
            this.updatePageTitle();
          }

          if (skippable) {
            this.addActionButton(
              "goi_skipBtn",
              _("Skip"),
              "actSkipRevealTile",
              null,
              false,
              "red"
            );
          }

          this.toggleRowsSelection();

          for (const row in revealableTiles) {
            const tileRow = `tileRow-${row}`;
            const tileCards = revealableTiles[row];

            this.goiStocks[tileRow].setSelectableCards(tileCards);
          }
          return;
        }

        if (stateName === "moveExplorer") {
          const explorableTiles = args.args.explorableTiles;

          this.toggleRowsSelection();

          for (const row in explorableTiles) {
            const tileRow = `tileRow-${row}`;
            const tileCards = explorableTiles[row];

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

      if (stateName === "revealTile") {
        this.toggleRowsSelection("none");
      }

      if (stateName === "moveExplorer") {
        this.toggleRowsSelection("none");
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
    getStateName: function () {
      return this.gamedatas.gamestate.name;
    },

    handleConfirmationButton: function (
      message = _("Confirm selection"),
      elementId = "goiConfirmationBtn"
    ) {
      document.getElementById(elementId)?.remove();
      const stateName = this.getStateName();

      if (stateName === "revealTile") {
        const selectedTile = this.goiGlobals.selectedTile;
        if (selectedTile) {
          this.addActionButton(elementId, message, "actRevealTile");
          return;
        }
      }

      if (stateName === "moveExplorer") {
        const selectedTile = this.goiGlobals.selectedTile;
        if (selectedTile) {
          this.addActionButton(elementId, message, "actMoveExplorer");
          return;
        }
      }
    },

    calcBackgroundPosition: function (spritePosition) {
      return -spritePosition * 100 + "% 0%";
    },

    unselectAllStocks: function (manager, exception) {
      manager.stocks.forEach((stock) => {
        if (exception?.element.id === stock.element.id) {
          return;
        }

        stock.unselectAll(true);
      });
    },

    toggleRowsSelection: function (selection = "single") {
      for (let row = 1; row <= 9; row++) {
        const tileRow = `tileRow-${row}`;
        this.goiStocks[tileRow].setSelectionMode(selection, []);
      }
    },

    getTileRow: function (region, hex) {
      let row = 1 + 2 * (Number(region) - 1);

      if (Number(hex) >= 7) {
        row++;
      }

      return row;
    },

    ///////////////////////////////////////////////////
    //// Player's action

    performAction: function (action, args = {}, options = {}) {
      this.bgaPerformAction(action, args, options);
    },

    actRevealTile: function () {
      this.performAction("actRevealTile", {
        tileCard_id: this.goiGlobals.selectedTile.id,
      });
    },

    actSkipRevealTile: function () {
      this.performAction("actSkipRevealTile");
    },

    actMoveExplorer: function () {
      this.performAction("actMoveExplorer", {
        tileCard_id: this.goiGlobals.selectedTile.id,
      });
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    setupNotifications: function () {
      console.log("notifications subscriptions setup");
      dojo.subscribe("revealTile", this, "notif_revealTile");
      dojo.subscribe("moveExplorer", this, "notif_moveExplorer");
      dojo.subscribe("incGem", this, "notif_incGem");
    },

    notif_revealTile: function (notif) {
      const tileCard = notif.args.tileCard;

      const tileRow = `tileRow-${tileCard.location}`;
      this.goiStocks[tileRow].flipCard(tileCard);
    },

    notif_moveExplorer: function (notif) {
      const tileCard = notif.args.tileCard;
      const explorerCard = notif.args.explorerCard;

      this.goiStocks.explorersGrid.addCard(
        explorerCard,
        {},
        {
          forceToElement: document.getElementById(`tile-${tileCard.id}`),
        }
      );
    },

    notif_incGem: function (notif) {
      const player_id = notif.args.player_id;
      const gem = notif.args.gem;
      const delta = notif.args.delta;

      this.goiCounters.gems[player_id][gem].incValue(delta);
    },
  });
});
