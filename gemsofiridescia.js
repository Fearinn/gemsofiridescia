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
  g_gamethemeurl + "modules/bga-help.js",
  g_gamethemeurl + "modules/bga-zoom.js",
  g_gamethemeurl + "modules/bga-cards.js",
  g_gamethemeurl + "modules/bga-dice.js",
  g_gamethemeurl + "modules/diceTypes.js",
], function (dojo, declare) {
  return declare("bgagame.gemsofiridescia", ebg.core.gamegui, {
    constructor: function () {
      console.log("gemsofiridescia constructor");

      this.goi_info = {};
      this.goi_globals = {};
      this.goi_managers = {};
      this.goi_selections = {};
      this.goi_counters = {};

      this.goi_stocks = {
        gems: {},
        tiles: {},
        explorers: {},
        dice: {},
        relics: {},
      };
    },

    setup: function (gamedatas) {
      console.log("Starting game setup");

      this.goi_info.relics = gamedatas.relicsInfo;

      this.goi_info.gemIds = {
        iridescia: 0,
        amethyst: 1,
        citrine: 2,
        emerald: 3,
        sapphire: 4,
        coin: 5,
      };

      this.goi_globals.players = gamedatas.players;
      this.goi_globals.player = gamedatas.players[this.player_id];
      this.goi_globals.tilesBoard = gamedatas.tilesBoard;
      this.goi_globals.playerBoards = gamedatas.playerBoards;
      this.goi_globals.revealedTiles = gamedatas.revealedTiles;
      this.goi_globals.collectedTiles = gamedatas.collectedTiles;
      this.goi_globals.iridiaStoneOwner = gamedatas.iridiaStoneOwner;
      this.goi_globals.royaltyTokens = gamedatas.royaltyTokens;
      this.goi_globals.explorers = gamedatas.explorers;
      this.goi_globals.coins = gamedatas.coins;
      this.goi_globals.gems = gamedatas.gems;
      this.goi_globals.gemsCounts = gamedatas.gemsCounts;
      this.goi_globals.availableCargos = [];
      this.goi_globals.marketValues = gamedatas.marketValues;
      this.goi_globals.publicStoneDiceCount = gamedatas.publicStoneDiceCount;
      this.goi_globals.privateStoneDiceCount = gamedatas.privateStoneDiceCount;
      this.goi_globals.activeStoneDiceCount = gamedatas.activeStoneDiceCount;
      this.goi_globals.stoneDiceFaces = {};
      this.goi_globals.relicsDeck = gamedatas.relicsDeck;
      this.goi_globals.relicsDeckTop = gamedatas.relicsDeckTop;
      this.goi_globals.relicsMarket = gamedatas.relicsMarket;
      this.goi_globals.restoredRelics = gamedatas.restoredRelics;

      this.goi_info.defaultSelections = {
        tile: null,
        gem: null,
        gems: [],
        diceCount: 0,
        opponent: null,
      };

      this.goi_selections = this.goi_info.defaultSelections;

      for (const player_id in this.goi_globals.players) {
        this.goi_stocks[player_id] = {
          gems: {},
          tiles: {},
          royaltyTokens: {},
          explorers: {},
          dice: {},
          relics: {},
        };
      }

      const aidBackgroundPosition = this.calcBackgroundPosition(
        this.goi_globals.playerBoards[this.player_id] - 1
      );

      const sentence3a = this.format_string(
        _("Spend 3 ${coin_icon} to Mine Gems. (∞)"),
        {
          coin_icon: `<span class="goi_coinIcon"></span>`,
        }
      );

      const aidContent = `
      <div id="goi_helpCardContent" class="goi_cardContent" style="--maxHeight: 180px;"> 
        <div>
          <span class="goi_helpCardSubtitle">${_("Main Actions")}</span>
          <span>1 ${_("Reveal up to 2 adjacent tiles.")}</span>
          <span>2 ${_("Move your explorer on to an adjacent tile.")}</span>
        </div>
        <div>
          <span class="goi_helpCardSubtitle">${_("Optional Actions")}</span>
          <span> 3a ${sentence3a}</span>
          <span>3b ${_("Purchase an Item Card. (Once)")}</span>
          <span>3c ${_("Play Item Card(s). (∞)")}</span>
          <span>3d ${_("Sell Gems. (Once)")}</span>
        </div>
        <div>
          <span class="goi_helpCardSubtitle">${_("End of Turn")}</span>
          <span>4 ${_("Restore Relic(s). (Optional)")}</span>
          <span>5 ${_("Collect hex tile.")}</span>
          <span>6 ${_("Adjust Market die.")}</span>
        </div>
      </div>
      `;

      this.goi_managers.help = new HelpManager(this, {
        buttons: [
          new BgaHelpExpandableButton({
            title: _("Player aid"),
            expandedHeight: "273px",
            foldedHtml: `<span class="goi_playerAid-folded">?</span>`,
            unfoldedHtml: `<div id="goi_helpCard" class="goi_helpCard bga-card" style="background-position: ${aidBackgroundPosition}">
              <span class="goi_cardTitle">${_("Player Aid")}</span>
              ${aidContent}
            </div>`,
          }),
        ],
      });

      this.goi_managers.zoom = new ZoomManager({
        element: document.getElementById("goi_gameArea"),
        localStorageZoomKey: "gemsofiridescia-zoom",
        zoomControls: {
          color: "black",
        },
        zoomLevels: [0.125, 0.2, 0.25, 0.375, 0.5, 0.625, 0.75],
      });

      this.goi_managers.dice = new DiceManager(this, {
        selectedDieClass: "goi_selectedDie",
        perspective: 0,
        dieTypes: {
          gem: new GemDie(),
          stone: new StoneDie(),
          mining: new MiningDie(),
        },
      });

      this.goi_managers.gems = new CardManager(this, {
        getId: (card) => `gem-${card.id}`,
        selectedCardClass: "goi_selectedGem",
        setupDiv: (card, div) => {
          div.classList.add("goi_gem");
          div.style.position = "relative";

          const backgroundPosition = this.calcBackgroundPosition(card.type_arg);
          div.style.backgroundPosition = backgroundPosition;
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {},
      });

      this.goi_managers.tiles = new CardManager(this, {
        cardHeight: 230,
        cardWidth: 200,
        getId: (card) => `tile-${card.id}`,
        selectedCardClass: "goi_selectedTile",
        setupDiv: (card, div) => {
          div.classList.add("goi_tile");
          div.style.position = "absolute";
        },
        setupFrontDiv: (card, div) => {
          const backgroundCode = card.type;
          const background = `url(${g_gamethemeurl}/img/tiles-${backgroundCode}.png)`;

          const backgroundPosition = this.calcBackgroundPosition(
            card.type_arg - 13 * (card.type - 1) - 1
          );

          div.style.backgroundImage = background;
          div.style.backgroundPosition = backgroundPosition;
        },
        setupBackDiv: (card, div) => {
          const background = `url(${g_gamethemeurl}/img/tilesBacks.png)`;
          const backgroundPosition = this.calcBackgroundPosition(card.type - 1);

          div.style.backgroundImage = background;
          div.style.backgroundPosition = backgroundPosition;
        },
      });

      this.goi_managers.royaltyTokens = new CardManager(this, {
        getId: (card) => `royaltyToken-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_royaltyToken");
          div.style.position = "relative";

          const backgroundPosition = this.calcBackgroundPosition(
            card.type_arg - 1
          );

          div.style.backgroundPosition = backgroundPosition;
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {},
      });

      this.goi_managers.explorers = new CardManager(this, {
        getId: (card) => `explorer-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_explorer");
          div.style.position = "relative";

          const spritePosition =
            this.goi_globals.playerBoards[card.type_arg] - 1;
          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);

          div.style.backgroundPosition = backgroundPosition;
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {},
      });

      this.goi_managers.relics = new CardManager(this, {
        cardHeight: 409,
        cardWidth: 300,
        selectedCardClass: "goi_selectedCard",
        getId: (card) => `relic-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_card");
          div.classList.add("goi_relic");
          div.style.position = "relative";
        },
        setupFrontDiv: (card, div) => {
          if (!card.type_arg || card.id === "fake") {
            div.style.backgroundImage = `url(${g_gamethemeurl}img/relicsBacks.png)`;
            const backgroundPosition = this.calcBackgroundPosition(card.type);

            div.style.backgroundPosition = backgroundPosition;
            return;
          }

          const backgroundCode = Math.ceil(card.type_arg / 12);
          const background = `url(${g_gamethemeurl}img/relics-${backgroundCode}.png)`;

          const spritePosition =
            backgroundCode === 1 ? card.type_arg - 1 : card.type_arg - 13;

          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);

          div.style.backgroundImage = background;
          div.style.backgroundPosition = backgroundPosition;

          const relicName = this.goi_info.relics[card.type_arg].tr_name;

          const cardTitle = document.createElement("span");
          cardTitle.textContent = _(relicName);
          cardTitle.classList.add("goi_cardTitle");

          if (div.childElementCount === 0) {
            div.appendChild(cardTitle);
          }
        },
        setupBackDiv: (card, div) => {
          div.style.backgroundImage = `url(${g_gamethemeurl}img/relicsBacks.png)`;
          const backgroundPosition = this.calcBackgroundPosition(card.type);

          div.style.backgroundPosition = backgroundPosition;
        },
      });

      this.goi_stocks.gems.rainbowOptions = new CardStock(
        this.goi_managers.gems,
        document.getElementById("goi_rainbowOptions")
      );

      this.goi_stocks.gems.rainbowOptions.onSelectionChange = (
        selection,
        lastChange
      ) => {
        if (selection.length > 0) {
          this.goi_selections.gem = lastChange;
        } else {
          this.goi_selections.gem = null;
        }

        this.handleConfirmationButton();
      };

      this.goi_stocks.gems.void = new VoidStock(
        this.goi_managers.gems,
        document.getElementById("goi_gemVoid"),
        {}
      );

      /* PLAYER PANELS */
      for (const player_id in this.goi_globals.players) {
        this.goi_counters[player_id] = {};

        document.getElementById(
          `player_board_${player_id}`
        ).innerHTML += `<div id="goi_playerPanel:${player_id}" class="goi_playerPanel">
            <div id="goi_gemCounters:${player_id}" class="goi_gemCounters"></div>
          </div>`;

        this.goi_counters[player_id].gems = {
          amethyst: new ebg.counter(),
          citrine: new ebg.counter(),
          emerald: new ebg.counter(),
          sapphire: new ebg.counter(),
        };

        const gemCounters = this.goi_counters[player_id].gems;

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

        document.getElementById(
          `goi_gemCounters:${player_id}`
        ).innerHTML += `<div class="goi_gemCounter">
        <div class="goi_gemIcon" style="background-position: -500%"></div>
        <span id="goi_coinCounter:${player_id}"></span>
      </div>`;

        for (const gem in gemCounters) {
          const gemCounter = gemCounters[gem];
          gemCounter.create(`goi_gemCounter:${player_id}-${gem}`);
          gemCounter.setValue(this.goi_globals.gemsCounts[player_id][gem]);
        }

        this.goi_counters[player_id].coins = new ebg.counter();
        this.goi_counters[player_id].coins.create(
          `goi_coinCounter:${player_id}`
        );
        this.goi_counters[player_id].coins.setValue(
          this.goi_globals.coins[player_id]
        );
      }

      /* BOARDS */

      /* tiles */
      this.goi_stocks.tiles.board = new CardStock(
        this.goi_managers.tiles,
        document.getElementById("goi_board"),
        {}
      );

      this.goi_stocks.tiles.board.onSelectionChange = (
        selected,
        lastChange
      ) => {
        const stateName = this.getStateName();

        if (stateName === "revealTile" || stateName === "moveExplorer") {
          if (selected.length === 0) {
            this.goi_selections.tile = null;
          } else {
            this.goi_selections.tile = lastChange;
          }

          this.handleConfirmationButton();
        }
      };

      const tilesBoard = this.goi_globals.tilesBoard;
      for (const tileCard_id in tilesBoard) {
        const tileCard = tilesBoard[tileCard_id];

        this.goi_stocks.tiles.board
          .addCard(
            tileCard,
            {},
            {
              forceToElement: document.getElementById(
                `goi_tileContainer-${tileCard.location_arg}`
              ),
            }
          )
          .then(() => {
            this.goi_stocks.tiles.board.setCardVisible(tileCard, false);

            const revealedTileCard =
              this.goi_globals.revealedTiles[tileCard_id];
            if (revealedTileCard) {
              this.goi_stocks.tiles.board.flipCard(revealedTileCard);
            }
          });
      }

      this.goi_stocks.explorers.board = new CardStock(
        this.goi_managers.explorers,
        document.getElementById("goi_explorersBoard"),
        {}
      );

      for (const explorerCard_id in this.goi_globals.explorers) {
        const explorerCard = this.goi_globals.explorers[explorerCard_id];
        const tileHex = explorerCard.location_arg;

        if (explorerCard["location"] === "board") {
          this.goi_stocks.explorers.board.addCard(
            explorerCard,
            {},
            {
              forceToElement: document.getElementById(
                `goi_tileContainer-${tileHex}`
              ),
            }
          );
        }
      }

      this.goi_stocks.dice.market = new DiceStock(
        this.goi_managers.dice,
        document.getElementById("goi_gemDice"),
        {}
      );

      for (const gemName in this.goi_globals.marketValues) {
        const value = this.goi_globals.marketValues[gemName];

        this.goi_stocks.dice.market.addDie({
          id: gemName,
          face: value,
          type: "gem",
        });
      }

      this.goi_stocks.dice.stone = new DiceStock(
        this.goi_managers.dice,
        document.getElementById("goi_stoneDice"),
        {}
      );

      for (
        let die = 4;
        die > 4 - this.goi_globals.publicStoneDiceCount;
        die--
      ) {
        this.goi_stocks.dice.stone.addDie({
          id: die,
          type: "stone",
          face: 6,
        });
      }

      for (const player_id in this.goi_globals.players) {
        const spritePosition = this.goi_globals.playerBoards[player_id] - 1;
        const backgroundPosition = this.calcBackgroundPosition(spritePosition);

        document.getElementById("goi_playerBoards").innerHTML += `
        <div id="goi_playerBoardContainer:${player_id}" class="goi_playerBoardContainer whiteblock">
          <div id="goi_playerHand:${player_id}" class="goi_playerHand">
            <div id="goi_victoryPile:${player_id}" class="goi_victoryPile">
                <div id="goi_iridiaStone:${player_id}" class="goi_royaltyTokenContainer"></div> 
                <div id="goi_royaltyToken:${player_id}" class="goi_royaltyTokenContainer"></div> 
                <div id="goi_tilesPile:${player_id}" class="goi_tilesPile"></div>
                <div id="goi_relicsPile:${player_id}" class="goi_relicsPile"></div>
            </div>
          </div>
          <div id="goi_playerBoard:${player_id}" class="goi_playerBoard" style="background-position: ${backgroundPosition}" data-player="${player_id}">
            <div id="goi_scene:${player_id}" class="goi_scene">
              <div id="goi_sceneExplorer:${player_id}" class="goi_sceneExplorer"></div>
              <div id="goi_sceneDice:${player_id}" class="goi_sceneDice"></div>
            </div>
              <div id="goi_cargo:${player_id}" class="goi_cargo">
                <div id="goi_cargoExcedent:${player_id}" class="goi_cargoExcedent whiteblock"></div> 
                <div id="goi_cargoBox:${player_id}-1" class="goi_cargoBox" data-box=1></div> 
                <div id="goi_cargoBox:${player_id}-2" class="goi_cargoBox" data-box=2></div> 
                <div id="goi_cargoBox:${player_id}-3" class="goi_cargoBox" data-box=3></div> 
                <div id="goi_cargoBox:${player_id}-4" class="goi_cargoBox" data-box=4></div> 
                <div id="goi_cargoBox:${player_id}-5" class="goi_cargoBox" data-box=5></div> 
                <div id="goi_cargoBox:${player_id}-6" class="goi_cargoBox" data-box=6></div> 
                <div id="goi_cargoBox:${player_id}-7" class="goi_cargoBox" data-box=7></div> 
              </div>
          </div>
        </div>`;
      }

      for (const player_id in this.goi_globals.players) {
        const player_color = this.goi_globals.players[player_id].color;

        this.goi_stocks[player_id].dice.scene = new DiceStock(
          this.goi_managers.dice,
          document.getElementById(`goi_sceneDice:${player_id}`)
        );

        this.goi_stocks[player_id].dice.scene.onSelectionChange = (
          selection,
          lastChange
        ) => {
          const selectedDiceCount = selection.length;
          this.goi_selections.diceCount = selectedDiceCount;

          const message =
            selectedDiceCount === 0
              ? _("Mine")
              : this.format_string(_("Mine (activate ${count} Stone dice)"), {
                  count: selectedDiceCount,
                });

          this.handleConfirmationButton("goi_mine_btn", message);
        };

        const dice = [
          {
            id: `1:${player_id}`,
            face: 6,
            type: "mining",
            color: player_color,
          },
          {
            id: `2:${player_id}`,
            face: 6,
            type: "mining",
            color: player_color,
          },
        ];

        const privateStoneDiceCount =
          this.goi_globals.privateStoneDiceCount[player_id];

        for (let die_id = 1; die_id <= privateStoneDiceCount; die_id++) {
          const active = die_id <= this.goi_globals.activeStoneDiceCount;

          dice.push({ id: die_id, type: "stone", face: 6, active: active });
        }
        this.goi_stocks[player_id].dice.scene.addDice(dice);

        this.goi_stocks[player_id].gems.cargo = new CardStock(
          this.goi_managers.gems,
          document.getElementById(`goi_cargo:${player_id}`)
        );
        this.goi_stocks[player_id].gems.cargo.onSelectionChange = (
          selection,
          lastChange
        ) => {
          const stateName = this.getStateName();

          if (stateName === "optionalActions") {
            if (selection.length > 0) {
              if (selection[0].type === lastChange.type) {
                this.goi_selections.gems.push(lastChange);
              } else {
                this.goi_stocks[player_id].gems.cargo.unselectAll(true);
                this.goi_stocks[player_id].gems.cargo.selectCard(
                  lastChange,
                  true
                );
                this.goi_selections.gems = [lastChange];
              }
            } else {
              this.goi_selections.gems = [];
            }

            this.handleConfirmationButton(
              "goi_sellGems_btn",
              _("Sell selected Gem(s)")
            );
            return;
          }

          if (stateName === "transferGem") {
            this.goi_selections.gem = lastChange;
            this.handleConfirmationButton();
            return;
          }
        };

        this.goi_stocks[player_id].explorers.scene = new CardStock(
          this.goi_managers.explorers,
          document.getElementById(`goi_sceneExplorer:${player_id}`),
          {}
        );
        for (const card_id in this.goi_globals.explorers) {
          const explorerCard = this.goi_globals.explorers[card_id];

          if (
            explorerCard["location"] === "scene" &&
            explorerCard["type_arg"] == player_id
          ) {
            this.goi_stocks[player_id].explorers.scene.addCard(explorerCard);
          }
        }

        const gemCards = this.goi_globals.gems[player_id];

        for (const gemCard_id in gemCards) {
          const gemCard = gemCards[gemCard_id];
          this.addGemToCargo(gemCard, player_id);
        }

        /* VICTORY PILE */
        this.goi_stocks[player_id].tiles.victoryPile = new AllVisibleDeck(
          this.goi_managers.tiles,
          document.getElementById(`goi_tilesPile:${player_id}`),
          {
            horizontalShift: "0px",
            verticalShift: "48px",
          }
        );

        const collectedTiles = this.goi_globals.collectedTiles[player_id];
        for (const tileCard_id in collectedTiles) {
          const tileCard = collectedTiles[tileCard_id];
          this.goi_stocks[player_id].tiles.victoryPile.addCard(tileCard);
        }

        this.goi_stocks[player_id].gems.iridiaStone = new CardStock(
          this.goi_managers.gems,
          document.getElementById(`goi_iridiaStone:${player_id}`)
        );

        if (player_id == this.goi_globals.iridiaStoneOwner) {
          this.goi_stocks[player_id].gems.iridiaStone.addCard({
            id: "iridia",
            type: "iridia",
            type_arg: 0,
          });
        }

        this.goi_stocks[player_id].royaltyTokens.victoryPile = new CardStock(
          this.goi_managers.royaltyTokens,
          document.getElementById(`goi_royaltyToken:${player_id}`)
        );

        const royaltyToken = this.goi_globals.royaltyTokens[player_id];

        if (royaltyToken) {
          this.goi_stocks[player_id].royaltyTokens.victoryPile.addCard({
            id: royaltyToken.id,
            type: royaltyToken.name,
            type_arg: royaltyToken.id,
          });
        }

        this.goi_stocks[player_id].relics.victoryPile = new AllVisibleDeck(
          this.goi_managers.relics,
          document.getElementById(`goi_relicsPile:${player_id}`),
          { horizontalShift: "0px", verticalShift: "48px" }
        );

        const restoredRelics = this.goi_globals.restoredRelics[player_id];
        for (const relicCard_id in restoredRelics) {
          const relicCard = restoredRelics[relicCard_id];
          this.goi_stocks[player_id].relics.victoryPile.addCard(relicCard);
        }
      }

      /* RELICS */
      this.goi_stocks.relics.deck = new Deck(
        this.goi_managers.relics,
        document.getElementById("goi_relicsDeck"),
        {
          counter: {
            id: "relicsDeckCounter",
            position: "top",
            extraClasses: "goi_deckCounter",
          },
        }
      );

      const relicsDeck = this.goi_globals.relicsDeck;
      for (const relicCard_id in relicsDeck) {
        const relicCard = relicsDeck[relicCard_id];

        this.goi_stocks.relics.deck.addCard(relicCard);
        this.goi_stocks.relics.deck.setCardVisible(relicCard, false);
      }

      const relicsDeckTop = this.goi_globals.relicsDeckTop;
      this.goi_stocks.relics.deck.addCard(relicsDeckTop);
      this.goi_stocks.relics.deck.setCardVisible(relicsDeckTop, false);

      this.goi_stocks.relics.market = new CardStock(
        this.goi_managers.relics,
        document.getElementById("goi_relicsMarket"),
        {}
      );

      this.goi_stocks.relics.market.onSelectionChange = (
        selection,
        lastChange
      ) => {
        if (selection.length > 0) {
          this.goi_globals.selectedRelic = lastChange;
        } else {
          this.goi_globals.selectedRelic = null;
        }

        this.handleConfirmationButton();
      };

      const relicsMarket = this.goi_globals.relicsMarket;
      for (const relicCard_id in relicsMarket) {
        const relicCard = relicsMarket[relicCard_id];

        this.goi_stocks.relics.market.addCard(relicCard);
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

          if (!skippable) {
            this.gamedatas.gamestate.descriptionmyturn = _(
              "${you} must reveal a tile"
            );
            this.updatePageTitle();
          }

          if (revealsLimit === 1) {
            this.gamedatas.gamestate.descriptionmyturn = _(
              "${you} may reveal another tile"
            );
            this.updatePageTitle();
          }

          if (skippable) {
            this.addActionButton(
              "goi_skip_btn",
              _("Skip"),
              "actSkipRevealTile",
              null,
              false,
              "red"
            );
          }

          this.goi_stocks.tiles.board.setSelectionMode(
            "single",
            revealableTiles
          );

          return;
        }

        if (stateName === "moveExplorer") {
          const explorableTiles = args.args.explorableTiles;
          const revealsLimit = args.args.revealsLimit;
          const revealableTiles = args.args.revealableTiles;

          if (revealsLimit < 2 && revealableTiles.length > 0) {
            this.addActionButton(
              "goi_undo_btn",
              _("Change mind (reveal another tile)"),
              "actUndoSkipRevealTile",
              null,
              false,
              "gray"
            );
          }

          this.goi_stocks.tiles.board.setSelectionMode("single");
          this.goi_stocks.tiles.board.setSelectableCards(explorableTiles);

          return;
        }

        if (stateName === "rainbowTile") {
          for (const gemName in this.goi_globals.gemsCounts[this.player_id]) {
            this.goi_stocks.gems.rainbowOptions.addCard({
              id: `rainbow-${gemName}`,
              type: gemName,
              type_arg: this.goi_info.gemIds[gemName],
            });
          }

          const gemCards = this.goi_stocks.gems.rainbowOptions.getCards();
          this.goi_stocks.gems.rainbowOptions.setSelectionMode(
            "single",
            gemCards
          );

          return;
        }

        if (stateName === "optionalActions") {
          const can_mine = args.args.can_mine;
          const can_sellGems = args.args.can_sellGems;

          this.addActionButton(
            "goi_skip_btn",
            _("Skip"),
            "actSkipOptionalActions",
            null,
            false,
            "red"
          );

          if (can_mine) {
            this.addActionButton("goi_mine_btn", _("Mine"), "actMine");

            const selectableDice = this.goi_stocks[this.player_id].dice.scene
              .getDice()
              .filter((die) => {
                return die.type === "stone" && !die.active;
              });

            if (selectableDice.length > 0) {
              this.goi_stocks[this.player_id].dice.scene.setSelectionMode(
                "multiple",
                selectableDice
              );
            }
          }

          if (can_sellGems) {
            this.goi_stocks[this.player_id].gems.cargo.setSelectionMode(
              "multiple",
              this.goi_stocks[this.player_id].gems.cargo.getCards()
            );
          }

          return;
        }

        if (stateName === "transferGem") {
          const availableCargos = args.args.availableCargos;
          console.log(availableCargos, "cargos");
          this.goi_globals.availableCargos = availableCargos;

          if (availableCargos.length === 0) {
            this.gamedatas.gamestate.descriptionmyturn = _(
              "The cargos of all players are full. ${you} must pick a Gem to discard"
            );
            this.updatePageTitle();
          }

          this.goi_stocks[this.player_id].gems.cargo.setSelectionMode("single");
        }

        if (stateName === "client_transferGem") {
          const selectedGem = args.client_args.selectedGem;

          this.addActionButton(
            "goi_changeMind_btn",
            _("Change mind (pick other Gem)"),
            () => {
              this.restoreServerGameState();
            },
            null,
            false,
            "gray"
          );

          this.goi_selections.gem = selectedGem;
          const gemElement =
            this.goi_stocks[this.player_id].gems.cargo.getCardElement(
              selectedGem
            );
          gemElement.classList.add("goi_selectedGem");

          for (const player_id in this.goi_globals.players) {
            if (player_id == this.player_id) {
              continue;
            }

            const playerBoardElement = document.getElementById(
              `goi_playerBoard:${player_id}`
            );

            playerBoardElement.classList.add("goi_selectablePlayerBoard");

            playerBoardElement.onclick = () => {
              playerBoardElement.classList.toggle("goi_selectedPlayerBoard");

              if (
                playerBoardElement.classList.contains("goi_selectedPlayerBoard")
              ) {
                this.goi_selections.opponent = player_id;
              } else {
                this.goi_selections.opponent = null;
              }

              this.handleConfirmationButton();
            };
          }
        }

        if (stateName === "restoreRelic") {
          const restorableRelics = args.args.restorableRelics;

          this.addActionButton(
            "goi_undo_btn",
            _("Change mind (perform another optional action)"),
            "actUndoSkipOptionalActions",
            null,
            false,
            "gray"
          );

          this.addActionButton(
            "goi_skip_btn",
            _("Skip and finish turn"),
            () => {
              this.confirmationDialog(
                _(
                  "Your turn will be finished. You won't be able to change your mind."
                ),
                () => {
                  this.actSkipRestoreRelic();
                }
              );
              return;
            },
            null,
            false,
            "red"
          );

          this.goi_stocks.relics.market.setSelectionMode("single");
          this.goi_stocks.relics.market.setSelectableCards(restorableRelics);
        }
        return;
      }

      if (stateName === "revealTile") {
        const revealsLimit = args.args.revealsLimit;
        const skippable = args.args.skippable;

        if (revealsLimit < 2) {
          this.gamedatas.gamestate.descriptionmyturn = _(
            "${you} may reveal another tile"
          );
          this.updatePageTitle();
        }

        if (skippable) {
          this.gamedatas.gamestate.description = _(
            "${actplayer} must reveal a tile"
          );
          this.updatePageTitle();
        }
      }

      if (stateName === "transferGem") {
        const availableCargos = args.args.availableCargos;
        this.goi_globals.availableCargos = availableCargos;

        if (availableCargos.length === 0) {
          this.gamedatas.gamestate.description = _(
            "The cargos of all players are full. ${actplayer} must pick a Gem to discard"
          );
          this.updatePageTitle();
        }
      }

      if (stateName === "betweenTurns") {
        this.goi_stocks[this.player_id].dice.scene.getDice().forEach((die) => {
          if (!die.active) {
            return;
          }

          die.active = false;
          die.face = this.goi_globals.stoneDiceFaces[die.id];
          this.goi_stocks[this.player_id].dice.scene.removeDie(die);
          this.goi_stocks[this.player_id].dice.scene.addDie(die);
        });
      }
    },

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      if (stateName === "revealTile") {
        this.goi_stocks.tiles.board.setSelectionMode("none");
      }

      if (stateName === "moveExplorer") {
        this.goi_stocks.tiles.board.setSelectionMode("none");
      }

      if (stateName === "rainbowTile") {
        this.goi_stocks.gems.rainbowOptions.removeAll();
      }

      if (stateName === "optionalActions") {
        this.goi_stocks[this.player_id].gems.cargo.setSelectionMode("none");
        this.goi_stocks[this.player_id].dice.scene.setSelectionMode("none");
      }

      if (stateName === "transferGem") {
        this.goi_globals.availableCargos = [];
        this.goi_stocks[this.player_id].gems.cargo.setSelectionMode("none");
      }

      if (stateName === "client_transferGem") {
        const selectedGem = this.goi_selections.gem;

        const gemElement =
          this.goi_stocks[this.player_id].gems.cargo.getCardElement(
            selectedGem
          );
        gemElement.classList.remove("goi_selectedGem");

        for (const player_id in this.goi_globals.players) {
          const playerBoardElement = document.getElementById(
            `goi_playerBoard:${player_id}`
          );
          playerBoardElement.classList.remove("goi_selectablePlayerBoard");
          playerBoardElement.classList.remove("goi_selectedPlayerBoard");
          playerBoardElement.onclick = undefined;
        }
      }

      if (stateName === "restoreRelic") {
        this.goi_stocks.relics.market.setSelectionMode("none");
      }

      this.goi_selections = this.goi_info.defaultSelections;
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
      elementId = "goi_confirmation_btn",
      message = _("Confirm selection")
    ) {
      document.getElementById(elementId)?.remove();
      const stateName = this.getStateName();

      if (stateName === "revealTile") {
        const selectedTile = this.goi_selections.tile;
        if (selectedTile) {
          this.addActionButton(elementId, message, "actRevealTile");
          return;
        }
      }

      if (stateName === "moveExplorer") {
        const selectedTile = this.goi_selections.tile;
        if (selectedTile) {
          this.addActionButton(elementId, message, "actMoveExplorer");
          return;
        }
      }

      if (stateName === "rainbowTile") {
        const selectedGem = this.goi_selections.gem;

        if (selectedGem) {
          this.addActionButton(elementId, message, "actPickRainbowGem");
          return;
        }
      }

      if (stateName === "optionalActions") {
        if (
          elementId === "goi_sellGems_btn" &&
          this.goi_selections.gems.length > 0
        ) {
          this.addActionButton(elementId, message, "actSellGems");
          return;
        }

        if (elementId === "goi_mine_btn") {
          this.addActionButton(elementId, message, "actMine");
        }
      }

      if (stateName === "transferGem") {
        if (this.goi_selections.gem) {
          this.addActionButton(elementId, message, () => {
            const availableCargos = this.goi_globals.availableCargos;
            if (availableCargos.length === 0) {
              this.actTransferGem();
              return;
            }

            this.setClientState("client_transferGem", {
              descriptionmyturn:
                "${you} must pick an opponent to transfer the selected Gem to",
              client_args: { selectedGem: this.goi_selections.gem },
            });
          });
        }
      }

      if (stateName === "client_transferGem") {
        if (this.goi_selections.gem && this.goi_selections.opponent) {
          this.addActionButton(elementId, message, "actTransferGem");
        }
      }

      if (stateName === "restoreRelic") {
        const selectedRelic = this.goi_globals.selectedRelic;

        if (selectedRelic) {
          this.addActionButton(elementId, message, "actRestoreRelic");
          return;
        }
      }
    },

    calcBackgroundPosition: function (spritePosition) {
      return -spritePosition * 100 + "% 0%";
    },

    findFreeBox: function (player_id) {
      const occupiedBoxes = [];

      this.goi_stocks[player_id].gems.cargo.getCards().forEach((gemCard) => {
        occupiedBoxes.push(gemCard.box);
      });

      for (let box = 1; box <= 7; box++) {
        if (!occupiedBoxes.includes(box)) {
          return box;
        }
      }
    },

    addGemToCargo: function (gemCard, player_id, originElement) {
      const box = this.findFreeBox(player_id);
      gemCard.box = box;

      const destinationElement = box
        ? document.getElementById(`goi_cargoBox:${player_id}-${box}`)
        : document.getElementById(`goi_cargoExcedent:${player_id}`);

      this.goi_stocks[player_id].gems.cargo.addCard(
        gemCard,
        {
          fromElement: originElement,
        },
        {
          forceToElement: destinationElement,
        }
      );
    },

    ///////////////////////////////////////////////////
    //// Player's action

    performAction: function (action, args = {}, options = {}) {
      this.bgaPerformAction(action, args, options);
    },

    actRevealTile: function () {
      this.performAction("actRevealTile", {
        tileCard_id: this.goi_selections.tile.id,
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
        tileCard_id: this.goi_selections.tile.id,
      });
    },

    actPickRainbowGem: function () {
      this.performAction("actPickRainbowGem", {
        gem_id: this.goi_selections.gem.type_arg,
      });
    },

    actMine: function () {
      this.performAction("actMine", {
        newStoneDiceCount: this.goi_selections.diceCount,
      });
    },

    actSellGems: function () {
      const selectedGems = this.goi_selections.gems;
      this.performAction("actSellGems", {
        gem_id: selectedGems[0].type_arg,
        selectedGems: JSON.stringify(selectedGems),
      });
    },

    actTransferGem: function () {
      const selectedGem = this.goi_selections.gem;
      this.performAction("actTransferGem", {
        gem_id: selectedGem.type_arg,
        gemCard: JSON.stringify(selectedGem),
        opponent_id: this.goi_selections.opponent,
      });
    },

    actSkipOptionalActions: function () {
      this.performAction("actSkipOptionalActions");
    },

    actUndoSkipOptionalActions: function () {
      this.performAction("actUndoSkipOptionalActions");
    },

    actRestoreRelic: function () {
      this.performAction("actRestoreRelic", {
        relicCard_id: this.goi_globals.selectedRelic.id,
      });
    },

    actSkipRestoreRelic: function () {
      this.performAction("actSkipRestoreRelic");
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    setupNotifications: function () {
      console.log("notifications subscriptions setup");
      const notifications = [
        { event: "revealTile", duration: 500 },
        { event: "moveExplorer", duration: 500 },
        { event: "resetExplorer", duration: 500 },
        { event: "incRoyaltyPoints", duration: 500 },
        { event: "obtainStoneDie", duration: 500 },
        { event: "activateStoneDie", duration: 500 },
        { event: "rollDie", duration: 0 },
        { event: "syncDieRolls", duration: 1000 },
        { event: "incCoin", duration: 500 },
        { event: "incGem", duration: 500 },
        { event: "decGem", duration: 500 },
        { event: "transferGem", duration: 500 },
        { event: "obtainIridiaStone", duration: 500 },
        { event: "obtainRoyaltyToken", duration: 500 },
        { event: "restoreRelic", duration: 500 },
        { event: "replaceRelic", duration: 1000 },
        { event: "collectTile", duration: 500 },
        { event: "updateMarketValue", duration: 500 },
      ];

      notifications.forEach((notif) => {
        const event = notif.event;
        const duration = notif.duration;

        dojo.subscribe(event, this, `notif_${event}`);

        if (duration > 0) {
          this.notifqueue.setSynchronous(event, duration);
        }
      });
    },

    notif_revealTile: function (notif) {
      const tileCard = notif.args.tileCard;

      this.goi_stocks.tiles.board.flipCard(tileCard);
    },

    notif_moveExplorer: function (notif) {
      const tileCard = notif.args.tileCard;
      const explorerCard = notif.args.explorerCard;

      this.goi_stocks.explorers.board.removeCard(explorerCard);
      this.goi_stocks.explorers.board.addCard(
        explorerCard,
        {
          fromElement: document.getElementById(
            `goi_tileContainer-${explorerCard.location_arg}`
          ),
        },
        {
          forceToElement: document.getElementById(
            `goi_tileContainer-${tileCard.location_arg}`
          ),
        }
      );
    },

    notif_resetExplorer: function (notif) {
      const player_id = notif.args.player_id;
      const explorerCard = notif.args.explorerCard;

      this.goi_stocks[player_id].explorers.scene.addCard(explorerCard);
    },

    notif_incGem: function (notif) {
      const player_id = notif.args.player_id;
      const delta = notif.args.delta;
      const gemName = notif.args.gemName;
      const gemCards = notif.args.gemCards;
      const tileCard = notif.args.tileCard;

      this.goi_counters[player_id].gems[gemName].incValue(delta);

      for (const gemCard_id in gemCards) {
        const gemCard = gemCards[gemCard_id];
        const hex = tileCard?.location_arg;

        this.addGemToCargo(
          gemCard,
          player_id,
          hex ? document.getElementById(`goi_tileContainer-${hex}`) : undefined
        );
      }
    },

    notif_decGem: function (notif) {
      const player_id = notif.args.player_id;
      const gemName = notif.args.gemName;
      const delta = notif.args.delta;
      const gem_id = notif.args.gem_id;
      let gemCards = notif.args.gemCards;

      this.goi_counters[player_id].gems[gemName].incValue(-delta);

      if (!gemCards) {
        gemCards = this.goi_stocks[player_id].gems.cargo
          .getCards()
          .filter((gemCard) => {
            return gemCard.type_arg == gem_id;
          });

        gemCards = gemCards.slice(-delta);
      }

      for (const gemCard_id in gemCards) {
        const gemCard = gemCards[gemCard_id];
        this.goi_stocks.gems.void.addCard(gemCard);
      }
    },

    notif_transferGem: function (notif) {
      const player_id = notif.args.player_id;
      const opponent_id = notif.args.player_id2;
      const transferredGemCard = notif.args.gemCard;
      const gemName = notif.args.gemName;

      this.addGemToCargo(transferredGemCard, opponent_id);

      const newGemCard = this.goi_stocks[player_id].gems.cargo
        .getCards()
        .filter((gemCard) => {
          return !gemCard.box;
        })[0];

      if (newGemCard) {
        this.goi_stocks[player_id].gems.cargo.removeCard(newGemCard);
        this.addGemToCargo(
          newGemCard,
          player_id,
          document.getElementById(`goi_cargoExcedent:${player_id}`)
        );
      }

      this.goi_counters[player_id].gems[gemName].incValue(-1);
      this.goi_counters[player_id].gems[gemName].incValue(1);
    },

    notif_obtainIridiaStone: function (notif) {
      const player_id = notif.args.player_id;

      this.goi_stocks[player_id].gems.iridiaStone.addCard({
        id: "iridia",
        type: "iridia",
        type_arg: 0,
      });
    },

    notif_obtainRoyaltyToken: function (notif) {
      const player_id = notif.args.player_id;
      const tokenName = notif.args.tokenName;
      const token_id = notif.args.token_id;

      this.goi_stocks[player_id].royaltyTokens.victoryPile.addCard({
        id: token_id,
        type: tokenName,
        type_arg: token_id,
      });
    },

    notif_incCoin: function (notif) {
      const player_id = notif.args.player_id;
      const delta = notif.args.delta;

      this.goi_counters[player_id].coins.incValue(delta);
    },

    notif_incRoyaltyPoints: function (notif) {
      const player_id = notif.args.player_id;
      const delta = notif.args.delta;

      this.scoreCtrl[player_id].incValue(delta);
    },

    notif_obtainStoneDie: function (notif) {
      const player_id = notif.args.player_id;
      const die_id = notif.args.die_id;

      this.goi_stocks[player_id].dice.scene.addDie({
        id: die_id,
        type: "stone",
      });
    },

    notif_activateStoneDie: function (notif) {
      const player_id = notif.args.player_id;
      const die_id = notif.args.die_id;

      this.goi_stocks[player_id].dice.scene.removeDie({
        id: die_id,
        type: "stone",
      });

      this.goi_stocks[player_id].dice.scene.addDie({
        id: die_id,
        type: "stone",
        active: true,
        face: this.goi_globals.stoneDiceFaces[die_id],
      });
    },

    notif_rollDie: function (notif) {
      const player_id = notif.args.player_id;
      const die_id = notif.args.die_id;
      const face = notif.args.face;
      const type = notif.args.type;

      this.goi_stocks[player_id].dice.scene.unselectAll();

      this.goi_stocks[player_id].dice.scene.rollDie({
        id: die_id,
        face: face,
        type: type,
      });

      this.goi_globals.stoneDiceFaces[die_id] = face;
    },

    notif_syncDieRolls: function (notif) {},

    notif_restoreRelic: function (notif) {
      const player_id = notif.args.player_id;
      const relicCard = notif.args.relicCard;

      this.goi_stocks[player_id].relics.victoryPile.addCard(relicCard);
    },

    notif_replaceRelic: function (notif) {
      const relicCard = notif.args.relicCard;
      const relicsDeckTop = notif.args.relicsDeckTop;

      this.goi_stocks.relics.market.addCard(relicCard, {
        fromElement: document.getElementById("goi_relicsDeck"),
      });

      this.goi_stocks.relics.deck.removeCard({ id: "fake" });
      this.goi_stocks.relics.deck.addCard(relicsDeckTop);
    },

    notif_collectTile: function (notif) {
      const player_id = notif.args.player_id;
      const tileCard = notif.args.tileCard;

      console.log(tileCard, "tileCard");

      this.goi_stocks[player_id].tiles.victoryPile.addCard(tileCard);
    },

    notif_updateMarketValue: function (notif) {
      const marketValue = notif.args.marketValue;
      const gem_id = notif.args.gem_id;
      const gemName = notif.args.gemName;

      const die = this.goi_stocks.dice.market.getDice().find((die) => {
        return gemName === die.id;
      });

      this.goi_stocks.dice.market.removeDie(die);

      die.face = marketValue;
      this.goi_stocks.dice.market.addDie(die);
    },
  });
});
