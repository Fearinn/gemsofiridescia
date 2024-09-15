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
      this.goiStocks = {
        gems: {},
        tiles: {},
        explorers: {},
      };
      this.goiCounters = {};
    },

    setup: function (gamedatas) {
      console.log("Starting game setup");

      this.goiGlobals.gemIds = {
        iridescia: 0,
        amethyst: 1,
        citrine: 2,
        emerald: 3,
        sapphire: 4,
        coin: 5,
      };

      this.goiGlobals.players = gamedatas.players;
      this.goiGlobals.player = gamedatas.players[this.player_id];
      this.goiGlobals.tileBoard = gamedatas.tileBoard;
      this.goiGlobals.playerBoards = gamedatas.playerBoards;
      this.goiGlobals.revealedTiles = gamedatas.revealedTiles;
      this.goiGlobals.explorers = gamedatas.explorers;
      this.goiGlobals.gems = gamedatas.gems;
      this.goiGlobals.selectedTile = null;

      for (const player_id in this.goiGlobals.players) {
        this.goiStocks[player_id] = {
          gems: {},
          tiles: {},
          explorers: {},
        };
      }

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

      this.goiManagers.gems = new CardManager(this, {
        getId: (card) => `gem-${card.id}-${card.type}`,
        selectedCardClass: "goi_gemSelected",
        setupDiv: (card, div) => {
          div.classList.add("goi_gem");
          div.style.position = "relative";

          const backgroundPosition = this.calcBackgroundPosition(card.type);
          div.style.backgroundPosition = backgroundPosition;
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {},
      });

      this.goiManagers.tiles = new CardManager(this, {
        getId: (card) => `goi_tile-${card.id}`,
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
        getId: (card) => `goi_explorer-${card.id}`,
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

      this.goiStocks.gems.rainbowOptions = new CardStock(
        this.goiManagers.gems,
        document.getElementById("goi_rainbowOptions")
      );

      this.goiStocks.gems.rainbowOptions.onSelectionChange = (
        selection,
        lastChange
      ) => {
        if (selection.length > 0) {
          this.goiGlobals.selectedGem = lastChange;
        } else {
          this.goiGlobals.selectedGem = null;
        }

        this.handleConfirmationButton();
      };

      this.goiStocks.gems.void = new VoidStock(
        this.goiManagers.gems,
        document.getElementById("player_boards"),
        {}
      );

      /* PLAYER PANELS */
      this.goiCounters.gems = {};
      for (const player_id in this.goiGlobals.players) {
        document.getElementById(
          `player_board_${player_id}`
        ).innerHTML += `<div id="goi_playerPanel:${player_id}" class="goi_playerPanel">
            <div id="goi_gemCounters:${player_id}" class="goi_gemCounters"></div>
          </div>`;

        this.goiCounters.gems[player_id] = {
          amethyst: new ebg.counter(),
          citrine: new ebg.counter(),
          emerald: new ebg.counter(),
          sapphire: new ebg.counter(),
          coin: new ebg.counter(),
        };

        const gemCounters = this.goiCounters.gems[player_id];

        let spritePosition = 1;
        for (const gem in gemCounters) {
          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);
          spritePosition++;

          document.getElementById(
            `goi_gemCounters:${player_id}`
          ).innerHTML += `<div class="goi_gemCounter">
                <div class="goi_gemIcon" style="background-position: ${backgroundPosition}"></div>
                <span id="goi_gemCounter:${player_id}-${gem}"></span>
              </div>`;
        }

        for (const gem in gemCounters) {
          const gemCounter = gemCounters[gem];
          gemCounter.create(`goi_gemCounter:${player_id}-${gem}`);
          gemCounter.setValue(this.goiGlobals.gems[player_id][gem]);
        }
      }

      /* BOARDS */
      for (let row = 1; row <= 9; row++) {
        /* tiles */
        const tileRow = `row-${row}`;

        this.goiStocks.tiles[tileRow] = new CardStock(
          this.goiManagers.tiles,
          document.getElementById(`goi_tileRow-${row}`),
          {}
        );

        this.goiStocks.tiles[tileRow].onSelectionChange = (
          selected,
          lastChange
        ) => {
          const stateName = this.getStateName();

          if (stateName === "revealTile" || stateName) {
            if (selected.length === 0) {
              this.goiGlobals.selectedTile = null;
            } else {
              this.goiGlobals.selectedTile = lastChange;
              this.unselectAllStocks(
                this.goiManagers.tiles,
                this.goiStocks.tiles[tileRow]
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

            this.goiStocks.tiles[tileRow].addCard(tileCard).then(() => {
              this.goiStocks.tiles[tileRow].setCardVisible(tileCard, false);

              const revealedTileCard =
                this.goiGlobals.revealedTiles[tileCard_id];
              if (revealedTileCard) {
                this.goiStocks.tiles[tileRow].flipCard(revealedTileCard);
              }
            });
          }
        }
      }

      this.goiStocks.explorers.grid = new CardStock(
        this.goiManagers.explorers,
        document.getElementById("goi_explorersGrid"),
        {}
      );

      for (const explorerCard_id in this.goiGlobals.explorers) {
        const explorerCard = this.goiGlobals.explorers[explorerCard_id];

        if (explorerCard["location"] === "board") {
          this.goiStocks.explorers.grid.addCard(
            explorerCard,
            {},
            {
              forceToElement: document.getElementById(
                `goi_tile-${explorerCard["location_arg"]}`
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
        ).innerHTML += `<div id="goi_playerBoard:${player_id}" class="goi_playerBoard" style="background-position: ${backgroundPosition}" data-player="${player_id}">
        <div id="goi_explorerScene:${player_id}" class="goi_explorerScene"></div>
          <div id="goi_cargo:${player_id}" class="goi_cargo">
            <div id="goi_cargoBox:${player_id}-1" class="goi_cargoBox" data-box=1></div> 
            <div id="goi_cargoBox:${player_id}-2" class="goi_cargoBox" data-box=2></div> 
            <div id="goi_cargoBox:${player_id}-3" class="goi_cargoBox" data-box=3></div> 
            <div id="goi_cargoBox:${player_id}-4" class="goi_cargoBox" data-box=4></div> 
            <div id="goi_cargoBox:${player_id}-5" class="goi_cargoBox" data-box=5></div> 
            <div id="goi_cargoBox:${player_id}-6" class="goi_cargoBox" data-box=6></div> 
            <div id="goi_cargoBox:${player_id}-7" class="goi_cargoBox" data-box=7></div> 
          </div>
        </div>`;

        this.goiStocks[player_id].gems.cargo = new CardStock(
          this.goiManagers.gems,
          document.getElementById(`goi_cargo:${player_id}`),
          {}
        );

        this.goiStocks[player_id].explorers.scene = new CardStock(
          this.goiManagers.explorers,
          document.getElementById(`goi_explorerScene:${player_id}`),
          {}
        );

        for (const card_id in this.goiGlobals.explorers) {
          const explorer = this.goiGlobals.explorers[card_id];

          if (
            explorer["location"] === "scene" &&
            explorer["type_arg"] == player_id
          ) {
            this.goiStocks[player_id].explorers.scene.addCard(explorer);
          }
        }

        const gems = this.goiGlobals.gems[player_id];

        let box = 1;
        for (const gem in gems) {
          if (gem === "coin") {
            continue;
          }

          const gemCount = gems[gem];

          for (let i = 1; i <= gemCount; i++) {
            this.goiStocks[player_id].gems.cargo.addCard(
              {
                id: Date.now() + i,
                type: gem,
                type_arg: this.goiGlobals.gemIds[gem],
              },
              {},
              {
                forceToElement: document.getElementById(
                  `goi_cargoBox:${player_id}-${box}`
                ),
              }
            );

            box++;
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
            const tileRow = `row-${row}`;
            const tileCards = revealableTiles[row];

            this.goiStocks.tiles[tileRow].setSelectableCards(tileCards);
          }
          return;
        }

        if (stateName === "moveExplorer") {
          const explorableTiles = args.args.explorableTiles;
          const revealsLimit = args.args.revealsLimit;

          if (revealsLimit < 2) {
            this.addActionButton(
              "goi_undoBtn",
              _("Change mind (reveal other tile)"),
              "actUndoSkipRevealTile",
              null,
              false,
              "gray"
            );
          }

          this.toggleRowsSelection();

          for (const row in explorableTiles) {
            const tileRow = `row-${row}`;
            const tileCards = explorableTiles[row];

            this.goiStocks.tiles[tileRow].setSelectableCards(tileCards);
          }
        }

        if (stateName === "rainbowTile") {
          for (const gem in this.goiGlobals.gems[this.player_id]) {
            this.goiStocks.gems.rainbowOptions.addCard({
              id: Date.now() + Math.random(),
              type: gem,
              type_arg: this.goiGlobals.gemIds[gem],
            });
          }

          const gemCards = this.goiStocks.gems.rainbowOptions.getCards();
          this.goiStocks.gems.rainbowOptions.setSelectionMode(
            "single",
            gemCards
          );
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

      if (stateName === "rainbowTile") {
        this.goiStocks.gems.rainbowOptions.removeAll();
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
      elementId = "goi_confirmationBtn"
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

      if (stateName === "rainbowTile") {
        const selectedGem = this.goiGlobals.selectedGem;

        if (selectedGem) {
          this.addActionButton(elementId, message, "actPickRainbowGem");
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
        const tileRow = `row-${row}`;
        this.goiStocks.tiles[tileRow].setSelectionMode(selection, []);
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

    actUndoSkipRevealTile: function () {
      this.performAction("actUndoSkipRevealTile");
    },

    actMoveExplorer: function () {
      this.performAction("actMoveExplorer", {
        tileCard_id: this.goiGlobals.selectedTile.id,
      });
    },

    actPickRainbowGem: function () {
      this.performAction("actPickRainbowGem", {
        gem_id: this.goiGlobals.selectedGem.type_arg,
      });
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    setupNotifications: function () {
      console.log("notifications subscriptions setup");
      dojo.subscribe("revealTile", this, "notif_revealTile");
      dojo.subscribe("moveExplorer", this, "notif_moveExplorer");
      dojo.subscribe("resetExplorer", this, "notif_resetExplorer");
      dojo.subscribe("incGem", this, "notif_incGem");
    },

    notif_revealTile: function (notif) {
      const tileCard = notif.args.tileCard;

      const tileRow = `row-${tileCard.location}`;
      this.goiStocks.tiles[tileRow].flipCard(tileCard);
    },

    notif_moveExplorer: function (notif) {
      const player_id = notif.args.player_id;
      const tileCard = notif.args.tileCard;
      const explorerCard = notif.args.explorerCard;

      this.goiStocks.explorers.grid.addCard(
        explorerCard,
        {},
        {
          forceToElement: document.getElementById(`goi_tile-${tileCard.id}`),
        }
      );
    },

    notif_resetExplorer: function (notif) {
      const player_id = notif.args.player_id;
      const explorerCard = notif.args.explorerCard;

      this.goiStocks[player_id].explorers.scene.addCard(explorerCard);
    },

    notif_incGem: function (notif) {
      const player_id = notif.args.player_id;
      const gem = notif.args.gem;
      const delta = notif.args.delta;
      const tileCard = notif.args.tileCard;

      this.goiCounters.gems[player_id][gem].incValue(delta);

      const box = this.goiStocks[player_id].gems.cargo.getCards().length + 1;

      this.goiStocks[player_id].gems.cargo.addCard(
        {
          id: Date.now() + Math.random(),
          type: gem,
          type_arg: this.goiGlobals.gemIds[gem],
        },
        {
          fromElement: tileCard
            ? document.getElementById(`goi_tile-${tileCard.id}`)
            : undefined,
        },
        {
          forceToElement: document.getElementById(
            `goi_cargoBox:${player_id}-${box}`
          ),
        }
      );
    },
  });
});
