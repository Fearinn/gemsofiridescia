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
  g_gamethemeurl + "modules/js/bga-help.js",
  g_gamethemeurl + "modules/js/bga-zoom.js",
  g_gamethemeurl + "modules/js/bga-cards.js",
  g_gamethemeurl + "modules/js/bga-dice.js",
  g_gamethemeurl + "modules/js/diceTypes.js",
], function (dojo, declare) {
  return declare("bgagame.gemsofiridescia", ebg.core.gamegui, {
    constructor: function () {
      console.log("gemsofiridescia constructor");

      this._registeredCustomTooltips = {};
      this._attachedTooltips = {};

      this.goi = {};
      this.goi.info = {};
      this.goi.globals = {};
      this.goi.managers = {};
      this.goi.selections = {};
      this.goi.counters = {};

      this.goi.stocks = {
        gems: {},
        tiles: {},
        explorers: {},
        dice: {},
        relics: {},
        objectives: {},
        items: {},
        scoringMarkers: {},
        rhom: {},
      };
    },

    setup: function (gamedatas) {
      console.log("Starting game setup");

      this.goi.version = gamedatas.version;

      const gameArea = document.getElementById("goi_gameArea");

      this.goi.managers.zoom = new ZoomManager({
        element: gameArea,
        localStorageZoomKey: "gemsofiridescia-zoom",
        zoomControls: {
          color: "black",
        },
        zoomLevels: [0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1, 1.1, 1.2],
        defaultZoom: 0.3,
        smooth: true,
        onZoomChange: () => {
          const width = gameArea.offsetWidth;
          const scrollWidth = gameArea.scrollWidth;

          if (scrollWidth > width) {
            gameArea.style.justifyContent = "flex-start";
            return;
          }

          gameArea.style.justifyContent = "center";
        },
      });

      const width = gameArea.offsetWidth;
      const scrollWidth = gameArea.scrollWidth;
      if (scrollWidth > width) {
        gameArea.style.justifyContent = "flex-start";
      } else {
        gameArea.style.justifyContent = "center";
      }

      this.goi.info.tiles = gamedatas.tilesInfo;
      this.goi.info.relics = gamedatas.relicsInfo;
      this.goi.info.objectives = gamedatas.objectivesInfo;
      this.goi.info.items = gamedatas.itemsInfo;

      this.goi.info.gems = {
        tooltips: {
          0: _("Iridia Stone"),
          1: _("Amethyst (purple)"),
          2: _("Citrine (yellow)"),
          3: _("Emerald (green)"),
          4: _("Sapphire (blue)"),
        },
        ids: {
          iridescia: 0,
          amethyst: 1,
          citrine: 2,
          emerald: 3,
          sapphire: 4,
          coin: 5,
        },
      };

      this.goi.info.defaultSelections = {
        tile: null,
        emptyTile: null,
        gem: null,
        gems: [],
        die: null,
        dice: [],
        opponent: null,
        relic: null,
        item: null,
        objective: null,
        delta: null,
      };

      this.goi.selections = this.goi.info.defaultSelections;

      this.goi.bot = gamedatas.bot;
      this.goi.globals.isSolo = gamedatas.isSolo;
      this.goi.globals.realPlayer = gamedatas.realPlayer;
      this.goi.globals.rhomDeck = gamedatas.rhomDeck;
      this.goi.globals.rhomDeckTop = gamedatas.rhomDeckTop;
      this.goi.globals.rhomDiscard = gamedatas.rhomDiscard;
      this.goi.globals.barricadeTiles = gamedatas.barricadeTiles;

      this.goi.globals.players = gamedatas.playersNoZombie;
      this.goi.globals.player = gamedatas.players[this.player_id];
      this.goi.globals.tilesBoard = gamedatas.tilesBoard;
      this.goi.globals.playerBoards = gamedatas.playerBoards;
      this.goi.globals.revealedTiles = gamedatas.revealedTiles;
      this.goi.globals.collectedTiles = gamedatas.collectedTiles;
      this.goi.globals.iridiaStoneOwner = gamedatas.iridiaStoneOwner;
      this.goi.globals.royaltyTokens = gamedatas.royaltyTokens;
      this.goi.globals.explorers = gamedatas.explorers;
      this.goi.globals.coins = gamedatas.coins;
      this.goi.globals.gems = gamedatas.gems;
      this.goi.globals.gemsCounts = gamedatas.gemsCounts;
      this.goi.globals.availableCargos = [];
      this.goi.globals.marketValues = gamedatas.marketValues;
      this.goi.globals.publicStoneDiceCount = gamedatas.publicStoneDiceCount;
      this.goi.globals.privateStoneDiceCount = gamedatas.privateStoneDiceCount;
      this.goi.globals.activeStoneDiceCount = gamedatas.activeStoneDiceCount;
      this.goi.globals.rolledDice = gamedatas.rolledDice;
      this.goi.globals.relicsDeck = gamedatas.relicsDeck;
      this.goi.globals.relicsDeckTop = gamedatas.relicsDeckTop;
      this.goi.globals.relicsMarket = gamedatas.relicsMarket;
      this.goi.globals.restoredRelics = gamedatas.restoredRelics;
      this.goi.globals.itemsDeck = gamedatas.itemsDeck;
      this.goi.globals.itemsMarket = gamedatas.itemsMarket;
      this.goi.globals.itemsDiscard = gamedatas.itemsDiscard;
      this.goi.globals.boughtItems = gamedatas.boughtItems;
      this.goi.globals.activeItems = gamedatas.activeItems;
      this.goi.globals.cancellableItems = gamedatas.cancellableItems;
      this.goi.globals.objectives = gamedatas.objectives;
      this.goi.globals.books = gamedatas.books;

      for (const player_id in this.goi.globals.players) {
        this.goi.stocks[player_id] = {
          gems: {},
          tiles: {},
          royaltyTokens: {},
          explorers: {},
          dice: {},
          relics: {},
          objectives: {},
          items: {},
          scoringMarkers: {},
        };
      }

      this.goi.managers.help = new HelpManager(this, {
        buttons: [
          new BgaHelpExpandableButton({
            title: _("Player Aid"),
            expandedWidth: "300px",
            expandedHeight: "409px",
            foldedHtml: `<span class="goi_helpFolded">?</span>`,
            unfoldedHtml: `<div id="goi_aidContainer"></div>`,
          }),
        ],
      });

      this.goi.managers.aid = new CardManager(this, {
        getId: (card) => `aid-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_playerAid");
          div.style.position = "relative";

          const backgroundPosition = this.calcBackgroundPosition(
            this.goi.globals.playerBoards[this.player_id] - 1 || 0
          );
          div.style.backgroundPosition = backgroundPosition;

          const coinPosition = this.calcCoinPosition(3);
          const sentence3a = this.format_string_recursive(
            _("Spend ${coin_icon} to Mine Gems. (∞)"),
            {
              coin_icon: `<i class="goi_coinIcon"><span class="goi_iconValue" style="left: ${coinPosition}">3</span></i>`,
            }
          );

          const cardTitle = document.createElement("h4");
          cardTitle.textContent = _("Player Aid");
          cardTitle.classList.add("goi_cardTitle");
          cardTitle.style.fontSize = "28px";

          div.appendChild(cardTitle);
          this.addCardTitle(cardTitle, 28, 409);

          const aidContent = `
            <div class="goi_aidBlock">
              <h5 class="goi_aidSubtitle">${_("Main Actions")}</h5>
              <div class="goi_aidFlaggedSentence"><i class="goi_greenFlag"></i><span>1 ${_(
                "Reveal up to 2 adjacent tiles."
              )}</span></div>
              <span>2 ${_("Move your explorer to an adjacent tile.")}</span>
            </div>
            <div class="goi_aidBlock">
              <h5 class="goi_aidSubtitle">${_(
                "Optional Actions (in any order)"
              )}</h5>
              <span> 3a ${sentence3a}</span>
              <span>3b ${_("Purchase an Item Card. (Once)")}</span>
              <span>3c ${_("Play Item Card(s). (∞)")}</span>
              <span>3d ${_("Sell Gem(s) of one color. (Once)")}</span>
            </div>
            <div class="goi_aidBlock">
              <h5 class="goi_aidSubtitle">${_("End of Turn")}</h5>
              <span>4 ${_("Restore Relic(s). (Optional)")}</span>
              <span>5 ${_("Collect hex tile.")}</span>
              <span>6 ${_("Adjust Market die.")}</span>
            </div>
          `;

          const aidContentElement = document.createElement("div");
          aidContentElement.innerHTML = aidContent;
          aidContentElement.style.boxSizing = "border-box";
          aidContentElement.style.padding = "0 4px";
          aidContentElement.style.fontFamily = "'rooney-web', serif";
          aidContentElement.style.fontSize = "16px";
          aidContentElement.classList.add("goi_cardContent");

          div.appendChild(aidContentElement);
          this.addAidContent(div, aidContentElement, 16);

          div.style.backgroundPosition = backgroundPosition;
          div.classList.add("goi_playerAid", "goi_tooltip", "goi_card");
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {},
      });

      this.goi.stocks.aid = new CardStock(
        this.goi.managers.aid,
        document.getElementById("goi_aidContainer")
      );

      this.goi.stocks.aid.addCard({ id: this.player_id });

      this.goi.managers.dice = new DiceManager(this, {
        selectedDieClass: "goi_selectedDie",
        perspective: 0,
        dieTypes: {
          gem: new GemDie(),
          stone: new StoneDie(),
          mining: new MiningDie(),
        },
      });

      this.goi.managers.gems = new CardManager(this, {
        getId: (card) => `gem-${card.id}`,
        selectedCardClass: "goi_selectedGem",
        setupDiv: (card, div) => {
          div.classList.add("goi_gem");
          div.style.position = "relative";

          const gem_id = Number(card.type_arg);

          const backgroundPosition = this.calcBackgroundPosition(gem_id);
          div.style.backgroundPosition = backgroundPosition;

          const tooltip = this.goi.info.gems.tooltips[gem_id];
          this.addTooltip(div.id, tooltip, "");
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {},
      });

      this.goi.managers.tiles = new CardManager(this, {
        cardHeight: 130,
        cardWidth: 112.5,
        getId: (card) => `tile-${card.id}`,
        selectedCardClass: "goi_selectedTile",
        setupDiv: (card, div) => {
          div.classList.add("goi_tile");
          div.style.position = "absolute";

          if (card.location === "barricade") {
            const barricadeElement = document.createElement("div");
            barricadeElement.classList.add("goi_barricade");
            div.appendChild(barricadeElement);
          }

          if (card.id < 0) {
            div.classList.add("goi_emptyTile");
          }
        },
        setupFrontDiv: (card, div) => {
          if (card.id < 0 || !card.type_arg) {
            return;
          }

          const backgroundCode = card.type;
          const background = `url(${g_gamethemeurl}/img/tiles-${backgroundCode}.png)`;

          const backgroundPosition = this.calcBackgroundPosition(
            card.type_arg - 13 * (card.type - 1) - 1
          );

          div.style.backgroundImage = background;
          div.style.backgroundPosition = backgroundPosition;

          const tooltip = this.createTileTooltip(card);

          this.addTooltipHtml(div.id, tooltip);
        },
        setupBackDiv: (card, div) => {
          if (card.id < 0) {
            return;
          }

          const background = `url(${g_gamethemeurl}/img/tilesBacks.png)`;
          const backgroundPosition = this.calcBackgroundPosition(card.type - 1);

          div.style.backgroundImage = background;
          div.style.backgroundPosition = backgroundPosition;

          if (card.location === "board") {
            this.addTooltip(
              div.id,
              this.format_string_recursive(_("Hex: ${log_hex}"), {
                log_hex: card.location_arg,
              }),
              ""
            );
          }
        },
      });

      this.goi.managers.royaltyTokens = new CardManager(this, {
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

      this.goi.managers.explorers = new CardManager(this, {
        getId: (card) => `explorer-${card.id}`,
        selectedCardClass: "goi_selectedCard",
        setupDiv: (card, div) => {
          div.classList.add("goi_explorer");
          div.style.position = "relative";

          const player_id = Number(card.type_arg);
          const playerName = this.goi.globals.players[player_id]?.name;
          this.addTooltip(div.id, playerName, "");

          if (this.goi.globals.isSolo && player_id == this.goi.bot.id) {
            div.classList.add("goi_rhomExplorer");
            return;
          }

          const spritePosition = this.goi.globals.playerBoards[player_id] - 1;
          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);

          div.style.backgroundPosition = backgroundPosition;
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {},
      });

      this.goi.managers.relics = new CardManager(this, {
        cardHeight: 230,
        cardWidth: 168.75,
        selectedCardClass: "goi_selectedCard",
        getId: (card) => `relic-${card.id}`,
        setupDiv: (card, div) => {
          if (card.type == -99) {
            div.classList.add("goi_tooltip");
            div.style.visibility = "hidden";
          }

          div.classList.add("goi_card");
          div.classList.add("goi_relic");
          div.style.position = "relative";
        },
        setupFrontDiv: (card, div) => {
          const relic_id = Number(card.type_arg);

          if (!relic_id || card.id === "fake") {
            div.style.backgroundImage = `url(${g_gamethemeurl}img/relicsBacks.jpg)`;
            const backgroundPosition = this.calcBackgroundPosition(card.type);

            div.style.backgroundPosition = backgroundPosition;
            return;
          }

          const backgroundCode = Math.ceil(relic_id / 12);
          let background = `url(${g_gamethemeurl}img/relics-${backgroundCode}.jpg)`;

          if (card.type == -99) {
            background = background.replace("img/", "img/tooltips/");
          }

          const spritePosition =
            backgroundCode === 1 ? relic_id - 1 : relic_id - 13;

          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);

          div.style.backgroundImage = background;
          div.style.backgroundPosition = backgroundPosition;

          const relicName = this.goi.info.relics[relic_id].tr_name;

          const cardTitle = document.createElement("h4");
          cardTitle.textContent = _(relicName);
          cardTitle.classList.add("goi_cardTitle");

          if (div.childElementCount === 0) {
            const initialFont = card.type == -99 ? 24 : 16;
            const cardHeight = card.type == -99 ? 409 : 230;

            cardTitle.style.fontSize = `${initialFont}px`;
            div.appendChild(cardTitle);
            this.addCardTitle(cardTitle, initialFont, cardHeight);
          }

          new dijit.Tooltip({
            connectId: [div.id],
            getContent: (matchedNode) => {
              return this.createRelicTooltip(relic_id);
            },
          });
        },
        setupBackDiv: (card, div) => {
          div.style.backgroundImage = `url(${g_gamethemeurl}img/relicsBacks.jpg)`;
          const backgroundPosition = this.calcBackgroundPosition(card.type);

          div.style.backgroundPosition = backgroundPosition;
        },
      });

      this.goi.managers.objectives = new CardManager(this, {
        cardHeight: 230,
        cardWidth: 168.75,
        selectedCardClass: "goi_selectedCard",
        getId: (card) => `objective-${card.id}`,
        setupDiv: (card, div) => {
          if (card.type == -99) {
            div.style.visibility = "hidden";
            div.classList.add("goi_tooltip");
          }

          div.classList.add("goi_card");
          div.classList.add("goi_objective");
          div.style.position = "relative";
        },
        setupFrontDiv: (card, div) => {
          const objective_id = Number(card.type_arg);

          if (!objective_id) {
            return;
          }

          const objectiveInfo = this.goi.info.objectives[objective_id];
          const objectiveName = objectiveInfo.tr_name;

          const cardTitle = document.createElement("h4");
          cardTitle.textContent = _(objectiveName);
          cardTitle.classList.add("goi_cardTitle");

          if (div.childElementCount === 0) {
            const initialFont = card.type == -99 ? 28 : undefined;
            const cardHeight = card.type == -99 ? 409 : undefined;

            cardTitle.style.fontSize = `${initialFont}px`;
            div.appendChild(cardTitle);
            this.addCardTitle(cardTitle, initialFont, cardHeight);
          }

          if (div.childElementCount === 1) {
            const initialFont = card.type == -99 ? 28 : undefined;
            const cardHeight = card.type == -99 ? 409 : undefined;
            this.addObjectiveContent(
              objective_id,
              div,
              null,
              initialFont,
              cardHeight
            );
          }

          const backgroundCode = objective_id <= 7 ? 1 : 2;
          let background = `url(${g_gamethemeurl}img/objectives-${backgroundCode}.jpg)`;

          if (card.type == -99) {
            background = background.replace("img/", "img/tooltips/");
          }

          let spritePosition = objective_id - 8 * (backgroundCode - 1);

          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);

          div.style.background = background;
          div.style.backgroundPosition = backgroundPosition;

          new dijit.Tooltip({
            connectId: [div.id],
            getContent: (matchedNode) => {
              return this.createObjectiveTooltip(objective_id);
            },
          });
        },
        setupBackDiv: (card, div) => {
          const background = `url(${g_gamethemeurl}img/objectives-1.jpg)`;
          const backgroundPosition = this.calcBackgroundPosition(0);

          div.style.background = background;
          div.style.backgroundPosition = backgroundPosition;
        },
      });

      this.goi.managers.items = new CardManager(this, {
        cardHeight: 230,
        cardWidth: 168.75,
        selectedCardClass: "goi_selectedCard",
        getId: (card) => `item-${card.id}`,
        setupDiv: (card, div) => {
          if (card.type == -99) {
            div.style.visibility = "hidden";
            div.classList.add("goi_tooltip");
          }

          div.classList.add("goi_card");
          div.classList.add("goi_item");
          div.style.position = "relative";
        },
        setupFrontDiv: (card, div) => {
          const item_id = Number(card.type_arg);

          if (!item_id) {
            return;
          }

          const itemInfo = this.goi.info.items[item_id];
          const itemName = itemInfo.tr_name;

          const cardTitle = document.createElement("h4");
          cardTitle.textContent = _(itemName);
          cardTitle.classList.add("goi_cardTitle");

          if (div.childElementCount === 0) {
            const initialFont = card.type == -99 ? 28 : undefined;
            const cardHeight = card.type == -99 ? 409 : undefined;

            cardTitle.style.fontSize = `${initialFont}px`;
            div.appendChild(cardTitle);
            this.addCardTitle(cardTitle, initialFont, cardHeight);
          }

          if (div.childElementCount === 1) {
            const fontSize = card.type == -99 ? 28 : undefined;
            const cardHeight = card.type == -99 ? 409 : undefined;
            this.addItemContent(item_id, div, null, fontSize, cardHeight);
          }

          const backgroundPosition = this.calcBackgroundPosition(item_id);
          div.style.backgroundPosition = backgroundPosition;

          new dijit.Tooltip({
            connectId: [div.id],
            getContent: (matchedNode) => {
              return this.createItemTooltip(item_id);
            },
          });
        },
        setupBackDiv: (card, div) => {},
      });

      this.goi.managers.scoringMarkers = new CardManager(this, {
        cardHeight: 40,
        cardWidth: 40,
        getId: (card) => `scoringMarker-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_scoringMarker");
          div.style.backgroundColor = card.color;

          if (card.score == 100) {
            const scoreElement = document.createElement("span");
            scoreElement.classList.add("goi_scoreHundred");
            scoreElement.textContent = 100;
            div.appendChild(scoreElement);
          }
        },
        setupFrontDiv: (card, div) => {},
        setupBackDiv: (card, div) => {
          div.style.backgroundColor = card.color;
        },
      });

      this.goi.managers.rhom = new CardManager(this, {
        cardHeight: 230,
        cardWidth: 168.75,
        getId: (card) => `rhom-${card.id}`,
        setupDiv: (card, div) => {
          div.classList.add("goi_card");
          div.classList.add("goi_rhom");
          div.style.position = "relative";
        },
        setupFrontDiv: (card, div) => {
          const rhom_id = Number(card.type_arg);

          const backgroundPosition = this.calcBackgroundPosition(rhom_id);
          div.style.backgroundPosition = backgroundPosition;

          const tooltip = this.createRhomTooltip(rhom_id);
          this.addTooltipHtml(div.id, tooltip);
        },
        setupBackDiv: (card, div) => {
          const spritePosition = card.type == "left" ? 0 : 17;
          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);
          div.style.backgroundPosition = backgroundPosition;
        },
      });

      const scoringTrack = document.getElementById("goi_scoringTrack");

      let slotsIds = [];
      for (let slotId = 1; slotId <= 100; slotId++) {
        slotsIds.push(slotId);
      }

      this.goi.stocks.scoringMarkers.track = new SlotStock(
        this.goi.managers.scoringMarkers,
        scoringTrack,
        {
          slotsIds,
          mapCardToSlot: (card) => {
            return Number(card.score);
          },
        }
      );

      scoringTrack.childNodes.forEach((slot) => {
        slot.style.position = "absolute";
        slot.style.height = "40px";
        slot.style.width = "40px";

        const slotId = Number(slot.dataset.slotId);

        if (slotId <= 34) {
          slot.style.bottom = `${2.5 + slotId * 2.5}%`;
          slot.style.left = slotId % 2 === 0 ? "6.5%" : "4.25%";
          return;
        }

        if (slotId > 34 && slotId <= 74) {
          slot.style.left = `${6.3 + (slotId - 34) * 2.125}%`;
          slot.style.top = slotId % 2 === 0 ? "8.4%" : "5.9%";
          return;
        }

        if (slotId >= 75) {
          slot.style.top = `${11 + (slotId - 75) * 2.5}%`;
          slot.style.right = slotId % 2 === 0 ? "5.5%" : "3.25%";
          return;
        }
      });

      this.goi.stocks.gems.rainbowOptions = new CardStock(
        this.goi.managers.gems,
        document.getElementById("goi_rainbowOptions")
      );

      this.goi.stocks.gems.rainbowOptions.onSelectionChange = (
        selection,
        lastChange
      ) => {
        if (selection.length > 0) {
          this.goi.selections.gem = lastChange;
        } else {
          this.goi.selections.gem = null;
        }

        this.handleSelection();
      };

      this.goi.stocks.gems.void = new VoidStock(
        this.goi.managers.gems,
        document.getElementById("goi_void")
      );

      /* PLAYER PANELS */
      for (const player_id in this.goi.globals.players) {
        if (this.goi.globals.isSolo && player_id == this.goi.bot.id) {
          continue;
        }

        this.getPlayerPanelElement(
          player_id
        ).innerHTML += `<div id="goi_playerPanel:${player_id}" class="goi_playerPanel">
            <div id="goi_gemCounters:${player_id}" class="goi_gemCounters"></div>
          </div>`;

        this.goi.counters[player_id] = {
          gems: {
            amethyst: new ebg.counter(),
            citrine: new ebg.counter(),
            emerald: new ebg.counter(),
            sapphire: new ebg.counter(),
          },
        };

        const gemCounters = this.goi.counters[player_id].gems;

        let spritePosition = 1;
        for (const gemName in gemCounters) {
          const backgroundPosition =
            this.calcBackgroundPosition(spritePosition);
          spritePosition++;

          document.getElementById(
            `goi_gemCounters:${player_id}`
          ).innerHTML += `<div class="goi_gemCounter">
                <div class="goi_gemIcon" style="background-position: ${backgroundPosition}"></div>
                <span id="goi_gemCounter:${player_id}-${gemName}" class="goi_counterValue"></span>
              </div>`;
        }

        const coins = this.goi.globals.coins[player_id];
        const positionLeft = this.calcCoinPosition(coins);

        document.getElementById(
          `goi_gemCounters:${player_id}`
        ).innerHTML += `<div class="goi_gemCounter">
        <div class="goi_gemIcon goi_coinIcon"> 
          <span id="goi_coinCounter:${player_id}" class="goi_iconValue" style="left: ${positionLeft}"></span>
        </div>
      </div>`;

        for (const gemName in gemCounters) {
          const gemCounter = gemCounters[gemName];
          gemCounter.create(`goi_gemCounter:${player_id}-${gemName}`);
          gemCounter.setValue(this.goi.globals.gemsCounts[player_id][gemName]);
        }

        this.goi.counters[player_id].coins = new ebg.counter();
        this.goi.counters[player_id].coins.create(
          `goi_coinCounter:${player_id}`
        );
        this.goi.counters[player_id].coins.setValue(coins);
      }

      /* BOARDS */

      /* tiles */
      this.goi.stocks.tiles.void = new VoidStock(
        this.goi.managers.tiles,
        document.getElementById("goi_void")
      );

      this.goi.stocks.tiles.board = new CardStock(
        this.goi.managers.tiles,
        document.getElementById("goi_board"),
        {}
      );

      this.goi.stocks.tiles.board.onSelectionChange = (
        selection,
        lastChange
      ) => {
        this.goi.stocks[this.player_id].items.hand.unselectAll(true);
        document.getElementById("goi_confirmItem_btn")?.remove();

        if (selection.length === 0) {
          this.goi.selections.tile = null;
        } else {
          this.goi.selections.tile = lastChange;
        }

        this.handleSelection();
      };

      const tilesBoard = this.goi.globals.tilesBoard;
      for (const tileCard_id in tilesBoard) {
        const tileCard = tilesBoard[tileCard_id];

        this.goi.stocks.tiles.board
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
            this.goi.stocks.tiles.board.setCardVisible(tileCard, false);

            const revealedTileCard =
              this.goi.globals.revealedTiles[tileCard_id];
            if (revealedTileCard) {
              this.goi.stocks.tiles.board.flipCard(revealedTileCard);
            }
          });
      }

      this.goi.stocks.tiles.empty = new CardStock(
        this.goi.managers.tiles,
        document.getElementById("goi_board"),
        {}
      );

      this.goi.stocks.tiles.empty.onSelectionChange = (
        selection,
        lastChange
      ) => {
        this.goi.stocks[this.player_id].items.hand.unselectAll(true);
        document.getElementById("goi_confirmItem_btn")?.remove();

        this.goi.stocks.tiles.board.unselectAll(true);
        document.getElementById("goi_confirm_btn")?.remove();

        const stateName = this.getStatWithRhomeName();

        if (stateName === "client_pickEmptyTile") {
          if (selection.length === 0) {
            this.goi.selections.emptyTile = null;
          } else {
            this.goi.selections.emptyTile = lastChange;
          }
        } else {
          if (selection.length === 0) {
            this.goi.selections.tile = null;
          } else {
            this.goi.selections.tile = lastChange;
          }
        }

        this.handleSelection();
      };

      if (this.goi.globals.isSolo) {
        this.goi.stocks.tiles.barricade = new CardStock(
          this.goi.managers.tiles,
          document.getElementById("goi_board"),
          {}
        );

        const barricadeTiles = this.goi.globals.barricadeTiles;
        for (const tileCard_id in barricadeTiles) {
          const tileCard = barricadeTiles[tileCard_id];
          this.goi.stocks.tiles.barricade.addCard(
            tileCard,
            {},
            {
              forceToElement: document.getElementById(
                `goi_tileContainer-${tileCard.location_arg}`
              ),
            }
          );
          this.goi.stocks.tiles.barricade.setCardVisible(
            tileCard,
            !!tileCard.type_arg
          );
        }
      }

      /* EXPLORERS */

      this.goi.stocks.explorers.board = new CardStock(
        this.goi.managers.explorers,
        document.getElementById("goi_explorersBoard"),
        {}
      );

      this.goi.stocks.explorers.board.onSelectionChange = (
        selection,
        lastChange
      ) => {
        if (selection.length > 0) {
          this.goi.selections.opponent = Number(lastChange.type_arg);
        } else {
          this.goi.selections.opponent = null;
        }

        this.handleSelection();
      };

      for (const explorerCard_id in this.goi.globals.explorers) {
        const explorerCard = this.goi.globals.explorers[explorerCard_id];
        const hex = explorerCard.location_arg;

        if (explorerCard["location"] === "board") {
          this.goi.stocks.explorers.board.addCard(
            explorerCard,
            {},
            {
              forceToElement: document.getElementById(
                `goi_tileContainer-${hex}`
              ),
            }
          );
        }
      }

      this.goi.stocks.dice.market = new DiceStock(
        this.goi.managers.dice,
        document.getElementById("goi_gemDice"),
        {
          sort: (die, otherDie) => {
            return die.id - otherDie.id;
          },
        }
      );

      this.goi.stocks.dice.market.onSelectionChange = (
        selection,
        lastChange
      ) => {
        const stateName = this.getStatWithRhomeName();

        this.goi.stocks[this.player_id].dice.scene.setSelectionMode("none");
        this.goi.stocks[this.player_id].dice.scene.setSelectionMode("multiple");

        if (stateName === "client_luckyLibation") {
          this.goi.selections.dice = selection;
        } else if (selection.length > 0) {
          this.goi.selections.die = lastChange;
        } else {
          this.goi.selections.die = null;
        }

        this.handleSelection();
      };

      for (const gemName in this.goi.globals.marketValues) {
        const gem_id = this.goi.info.gems.ids[gemName];
        const value = this.goi.globals.marketValues[gemName];

        this.goi.stocks.dice.market.addDie({
          id: gem_id,
          face: value,
          type: "gem",
        });
      }

      this.goi.stocks.dice.stone = new DiceStock(
        this.goi.managers.dice,
        document.getElementById("goi_stoneDice"),
        {}
      );

      for (
        let die = 4;
        die > 4 - this.goi.globals.publicStoneDiceCount;
        die--
      ) {
        this.goi.stocks.dice.stone.addDie({
          id: die,
          type: "stone",
          face: 6,
        });
      }

      for (const player_id in this.goi.globals.players) {
        const spritePosition = this.goi.globals.playerBoards[player_id] - 1;
        const backgroundPosition = this.calcBackgroundPosition(spritePosition);

        const player = this.goi.globals.players[player_id];
        const playerName =
          this.player_id == player_id
            ? `${_("You")} (${player.name})`
            : player.name;
        const playerColor = player.color;
        const order = this.player_id == player_id ? -1 : 0;

        document.getElementById("goi_playerZones").innerHTML += `
        <div id="goi_playerZoneContainer:${player_id}" class="goi_playerZoneContainer whiteblock" style="border-color: #${playerColor}; order: ${order};">
          <h3 id="goi_playerZoneTitle:${player_id}" class="goi_zoneTitle" style="color: #${playerColor};">${playerName}</h3>
          <div id="goi_playerZone:${player_id}" class="goi_playerZone">
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
                <div id="goi_scoringHundred:${player_id}" class="goi_scoringHundred"></div>
                <div id="goi_iridiaStone:${player_id}" class="goi_iridiaStone"></div>
            </div>
            <div id="goi_playerHand:${player_id}" class="goi_playerHand">
              <div id="goi_book:${player_id}" class="goi_book"></div>
              <div id="goi_items:${player_id}" class="goi_items"></div>
              <div id="goi_objectives:${player_id}" class="goi_objectives"></div>
              <div id="goi_victoryPiles:${player_id}" class="goi_victoryPiles">
                <div id="goi_relicsPile:${player_id}" class="goi_relicsPile"></div>
                <div id="goi_tilesPile:${player_id}" class="goi_tilesPile"></div>
                <div id="goi_royaltyToken:${player_id}"></div> 
              </div>
            </div>
          </div>
        </div>`;
      }

      let currentStoneDie_id = 1;
      for (const player_id in this.goi.globals.players) {
        const player = this.goi.globals.players[player_id];
        const player_color = player.color;

        this.goi.stocks[player_id].dice.scene = new DiceStock(
          this.goi.managers.dice,
          document.getElementById(`goi_sceneDice:${player_id}`),
          {
            sort: (die, otherDie) => {
              if (die.type !== otherDie.type) {
                if (die.type === "mining") {
                  return 1;
                }

                return 0;
              }

              const die_id = String(die.id);
              const otherDie_id = String(otherDie.id);

              if (die_id > otherDie_id) {
                return 1;
              }

              if (die_id < otherDie_id) {
                return -1;
              }

              return 0;
            },
          }
        );

        this.goi.stocks[player_id].dice.scene.onSelectionChange = (
          selection,
          lastChange
        ) => {
          this.goi.stocks.dice.market.setSelectionMode("none");

          const stateName = this.getStatWithRhomeName();
          if (stateName === "client_luckyLibation") {
            this.goi.stocks.dice.market.setSelectionMode("multiple");
          } else {
            this.goi.stocks.dice.market.setSelectionMode("single");
          }

          if (stateName === "client_luckyLibation") {
            this.goi.selections.dice = selection;
          } else if (selection.length > 0) {
            this.goi.selections.die = lastChange;
          } else {
            this.goi.selections.die = null;
          }

          this.handleSelection();
        };

        const rolledDice = this.goi.globals.rolledDice;

        const mininigDie1_id = `1-${player_id}`;
        const mininigDie2_id = `2-${player_id}`;

        const dice = [
          {
            id: mininigDie1_id,
            face: rolledDice[mininigDie1_id]?.face || 6,
            type: "mining",
            color: player_color,
          },
          {
            id: mininigDie2_id,
            face: rolledDice[mininigDie2_id]?.face || 6,
            type: "mining",
            color: player_color,
          },
        ];

        const privateStoneDiceCount =
          this.goi.globals.privateStoneDiceCount[player_id];

        for (
          let die_id = currentStoneDie_id;
          die_id <= privateStoneDiceCount + currentStoneDie_id - 1;
          die_id++
        ) {
          const active = die_id <= this.goi.globals.activeStoneDiceCount;
          const face = rolledDice[die_id]?.face;

          dice.push({
            id: die_id,
            type: "stone",
            face: face || 6,
            active: active,
          });
        }
        currentStoneDie_id += dice.length - 2;

        this.goi.stocks[player_id].dice.scene.addDice(dice);

        this.goi.stocks[player_id].gems.cargo = new CardStock(
          this.goi.managers.gems,
          document.getElementById(`goi_cargo:${player_id}`)
        );
        this.goi.stocks[player_id].gems.cargo.onSelectionChange = (
          selection,
          lastChange
        ) => {
          const stateName = this.getStatWithRhomeName();

          if (stateName === "client_sellGems") {
            if (selection.length > 0) {
              if (selection[0].type !== lastChange.type) {
                this.goi.stocks[player_id].gems.cargo.unselectAll(true);
                this.goi.stocks[player_id].gems.cargo.selectCard(
                  lastChange,
                  true
                );
                this.goi.selections.gems = [lastChange];
              } else {
                this.goi.selections.gems = selection;
              }
            } else {
              this.goi.selections.gems = selection;
            }

            this.handleSelection();
            return;
          }

          if (stateName === "transferGem") {
            const excedentGems = this.goi.globals.excedentGems;
            if (selection.length > excedentGems) {
              this.showMessage(
                this.format_string(
                  _("You can't select more than ${excedentGems} Gem(s)"),
                  { excedentGems }
                ),
                "error"
              );
              this.goi.stocks[player_id].gems.cargo.unselectCard(
                lastChange,
                true
              );
              return;
            }
            this.goi.selections.gems = selection;
            this.handleSelection();
            return;
          }

          if (stateName === "client_cauldronOfFortune") {
            if (selection.length > 2) {
              this.showMessage(_("You can't select more than 2 Gems"), "error");
              this.goi.stocks[player_id].gems.cargo.unselectCard(
                lastChange,
                true
              );
              return;
            }

            this.goi.selections.gems = selection;
            this.handleSelection();
          }

          if (stateName === "client_axeOfAwesomeness") {
            if (selection.length > 0) {
              this.goi.selections.gem = lastChange;
            } else {
              this.goi.selections.gems = null;
            }
            this.handleSelection();
          }
        };

        this.goi.stocks[player_id].explorers.scene = new CardStock(
          this.goi.managers.explorers,
          document.getElementById(`goi_sceneExplorer:${player_id}`),
          {}
        );

        for (const card_id in this.goi.globals.explorers) {
          const explorerCard = this.goi.globals.explorers[card_id];

          if (
            explorerCard["location"] === "scene" &&
            explorerCard["type_arg"] == player_id
          ) {
            this.goi.stocks[player_id].explorers.scene.addCard(explorerCard);
          }
        }

        const gemCards = this.goi.globals.gems[player_id];

        for (const gemCard_id in gemCards) {
          const gemCard = gemCards[gemCard_id];
          this.addGemToCargo(gemCard, player_id);
        }

        /*  OBJECTIVES */

        if (player_id != this.player_id) {
          document
            .getElementById(`goi_objectives:${player_id}`)
            .classList.add("goi_opponentObjectives");
        }

        this.goi.stocks[player_id].objectives.hand = new AllVisibleDeck(
          this.goi.managers.objectives,
          document.getElementById(`goi_objectives:${player_id}`),
          { horizontalShift: "0px", verticalShift: "24px" }
        );

        this.goi.stocks[player_id].objectives.hand.onSelectionChange = (
          selection,
          lastChange
        ) => {
          if (selection.length > 0) {
            this.goi.selections.objective = lastChange;
          } else {
            this.goi.selections.objective = null;
          }

          this.handleSelection();
        };

        const objectives = this.goi.globals.objectives[player_id];
        for (const objectiveCard_id in objectives) {
          const objectiveCard = objectives[objectiveCard_id];
          this.goi.stocks[player_id].objectives.hand.addCard(objectiveCard);

          if (player_id != this.player_id) {
            this.goi.stocks[player_id].objectives.hand.setCardVisible(
              objectiveCard,
              false
            );
          }
        }

        /* ITEMS */

        this.goi.stocks[player_id].items.hand = new AllVisibleDeck(
          this.goi.managers.items,
          document.getElementById(`goi_items:${player_id}`),
          { horizontalShift: "0px", verticalShift: "24px" }
        );

        this.goi.stocks[player_id].items.hand.onSelectionChange = (
          selection,
          lastChange
        ) => {
          this.goi.stocks.tiles.board.unselectAll(true);
          document.getElementById("goi_confirmation_btn")?.remove();

          if (selection.length > 0) {
            this.goi.selections.item = lastChange;
          } else {
            this.goi.selections.item = null;
          }

          this.handleItemSelection();
        };

        const boughtItems = this.goi.globals.boughtItems[player_id];
        for (const itemCard_id in boughtItems) {
          const itemCard = boughtItems[itemCard_id];
          this.goi.stocks[player_id].items.hand.addCard(itemCard);
        }

        this.goi.stocks[player_id].items.book = new CardStock(
          this.goi.managers.items,
          document.getElementById(`goi_book:${player_id}`)
        );

        const bookItem = this.goi.globals.books[player_id].item;
        if (bookItem) {
          this.goi.stocks[player_id].items.book.addCard(bookItem);
        }

        /* VICTORY PILE */
        this.goi.stocks[player_id].tiles.victoryPile = new CardStock(
          this.goi.managers.tiles,
          document.getElementById(`goi_tilesPile:${player_id}`),
          {
            sort: (tile, otherTile) => {
              if (this.getGameUserPreference(103) == 1) {
                const tileInfo = this.goi.info.tiles[tile.type_arg];
                const otherTileInfo = this.goi.info.tiles[otherTile.type_arg];

                if (tileInfo != otherTileInfo["gem"]) {
                  return tileInfo["gem"] - otherTileInfo["gem"];
                }
              }

              return tile.type_arg - otherTile.type_arg;
            },
          }
        );

        this.goi.stocks[player_id].tiles.victoryPile.onSelectionChange = (
          selection,
          lastChange
        ) => {
          if (selection.length > 0) {
            this.goi.selections.tile = lastChange;
          } else {
            this.goi.selections.tile = null;
          }

          this.handleSelection();
        };

        const collectedTiles = this.goi.globals.collectedTiles[player_id];
        for (const tileCard_id in collectedTiles) {
          const tileCard = collectedTiles[tileCard_id];
          this.goi.stocks[player_id].tiles.victoryPile.addCard(tileCard);
        }

        /* ROYALTY TOKENS */

        this.goi.stocks[player_id].gems.iridiaStone = new CardStock(
          this.goi.managers.gems,
          document.getElementById(`goi_iridiaStone:${player_id}`)
        );

        if (player_id == this.goi.globals.iridiaStoneOwner) {
          this.goi.stocks[player_id].gems.iridiaStone.addCard({
            id: "iridia",
            type: "iridia",
            type_arg: 0,
          });
        }

        this.goi.stocks[player_id].royaltyTokens.victoryPile = new CardStock(
          this.goi.managers.royaltyTokens,
          document.getElementById(`goi_royaltyToken:${player_id}`)
        );

        const royaltyToken = this.goi.globals.royaltyTokens[player_id];

        if (royaltyToken) {
          this.goi.stocks[player_id].royaltyTokens.victoryPile.addCard({
            id: royaltyToken.id,
            type: royaltyToken.name,
            type_arg: royaltyToken.id,
          });
        }

        this.goi.stocks[player_id].relics.victoryPile = new CardStock(
          this.goi.managers.relics,
          document.getElementById(`goi_relicsPile:${player_id}`),
          {
            sort: (relic, otherRelic) => {
              const relicInfo = this.goi.info.relics[relic.type_arg];
              const relicType = relicInfo["type"];

              const otherRelicInfo = this.goi.info.relics[otherRelic.type_arg];
              const otherRelicType = otherRelicInfo["type"];

              const leadGem = relicInfo["leadGem"];
              const otherLeadGem = relicInfo["leadGem"];

              if (this.getGameUserPreference(104) == 1) {
                if (leadGem != otherLeadGem) {
                  return leadGem - otherLeadGem;
                }
              }

              return relicType - otherRelicType;
            },
          }
        );

        const restoredRelics = this.goi.globals.restoredRelics[player_id];
        for (const relicCard_id in restoredRelics) {
          const relicCard = restoredRelics[relicCard_id];
          this.goi.stocks[player_id].relics.victoryPile.addCard(relicCard);
        }

        this.goi.stocks[player_id].relics.book = new CardStock(
          this.goi.managers.relics,
          document.getElementById(`goi_book:${player_id}`)
        );

        this.goi.stocks[player_id].relics.book.onSelectionChange = (
          selection,
          lastChange
        ) => {
          this.goi.stocks.relics.market.unselectAll(true);

          if (selection.length > 0) {
            this.goi.selections.relic = lastChange;
          } else {
            this.goi.selections.relic = null;
          }

          this.handleSelection();
        };

        const bookRelic = this.goi.globals.books[player_id].relic;
        if (bookRelic) {
          {
            this.goi.stocks[player_id].relics.book.addCard(bookRelic);
          }
        }

        /* SCORING MARKERS */
        this.goi.stocks[player_id].scoringMarkers.hundred = new CardStock(
          this.goi.managers.scoringMarkers,
          document.getElementById(`goi_scoringHundred:${player_id}`)
        );

        const score = player.score;

        if (score >= 100) {
          const completeScoringMarker = {
            id: `${player_id}-100`,
            color: `#${player_color}`,
            score: 100,
          };

          const scoringMarker = {
            id: player_id,
            color: `#${player_color}`,
            score: score - 100,
          };

          if (score === 100) {
            this.goi.stocks.scoringMarkers.track.removeCard(scoringMarker);
          } else {
            this.goi.stocks.scoringMarkers.track.addCard(scoringMarker);
          }

          this.goi.stocks[player_id].scoringMarkers.hundred.addCard(
            completeScoringMarker
          );
        } else if (score > 0) {
          const scoringMarker = {
            id: player_id,
            color: `#${player_color}`,
            score,
          };
          this.goi.stocks.scoringMarkers.track.addCard(scoringMarker);
        }
      }

      /* RELICS */
      this.goi.stocks.relics.tooltips = new CardStock(
        this.goi.managers.relics,
        document.getElementById("goi_tooltips")
      );

      for (const relic_id in this.goi.info.relics) {
        this.goi.stocks.relics.tooltips.addCard({
          id: `tooltip-${relic_id}`,
          type: -99,
          type_arg: relic_id,
        });
      }

      this.goi.stocks.relics.deck = new Deck(
        this.goi.managers.relics,
        document.getElementById("goi_relicsDeck"),
        {
          counter: {
            id: "goi_relicsDeckCounter",
            position: "top",
            extraClasses: "goi_deckCounter",
            hideWhenEmpty: true,
          },
        }
      );

      const relicsDeck = this.goi.globals.relicsDeck;
      for (const relicCard_id in relicsDeck) {
        const relicCard = relicsDeck[relicCard_id];

        this.goi.stocks.relics.deck.addCard(relicCard);
        this.goi.stocks.relics.deck.setCardVisible(relicCard, false);
      }

      const relicsDeckTop = this.goi.globals.relicsDeckTop;

      if (relicsDeckTop) {
        this.goi.stocks.relics.deck.addCard(relicsDeckTop);
        this.goi.stocks.relics.deck.setCardVisible(relicsDeckTop, false);
      }

      this.goi.stocks.relics.market = new CardStock(
        this.goi.managers.relics,
        document.getElementById("goi_relicsMarket"),
        {
          sort: (a, b) => {
            return Number(b.location_arg) - Number(a.location_arg);
          },
        }
      );

      this.goi.stocks.relics.market.onSelectionChange = (
        selection,
        lastChange
      ) => {
        this.goi.stocks[this.player_id].relics.book.unselectAll(true);

        if (selection.length > 0) {
          this.goi.selections.relic = lastChange;
        } else {
          this.goi.selections.relic = null;
        }

        this.handleSelection();
      };

      const relicsMarket = this.goi.globals.relicsMarket;
      for (const relicCard_id in relicsMarket) {
        const relicCard = relicsMarket[relicCard_id];

        this.goi.stocks.relics.market.addCard(relicCard);
      }

      /* OBECTIVES */

      this.goi.stocks.objectives.tooltips = new CardStock(
        this.goi.managers.objectives,
        document.getElementById("goi_tooltips")
      );

      for (const objective_id in this.goi.info.objectives) {
        this.goi.stocks.objectives.tooltips.addCard({
          id: `tooltip-${objective_id}`,
          type: -99,
          type_arg: objective_id,
        });
      }

      this.goi.stocks.objectives.void = new VoidStock(
        this.goi.managers.objectives,
        document.getElementById("goi_void")
      );

      /* ITEMS */
      document.getElementById("goi_activeItemsTitle").textContent = _("Active");

      document.getElementById("goi_itemsDiscardTitle").textContent =
        _("Discard");

      this.goi.stocks.items.tooltips = new CardStock(
        this.goi.managers.items,
        document.getElementById("goi_tooltips")
      );

      for (const item_id in this.goi.info.items) {
        this.goi.stocks.items.tooltips.addCard({
          id: `tooltip-${item_id}`,
          type: -99,
          type_arg: item_id,
        });
      }

      this.goi.stocks.items.deck = new Deck(
        this.goi.managers.items,
        document.getElementById("goi_itemsDeck"),
        {
          counter: {
            id: "goi_itemsDeckCounter",
            position: "top",
            extraClasses: "goi_deckCounter",
          },
        }
      );

      const itemsDeck = this.goi.globals.itemsDeck;
      for (const itemCard_id in itemsDeck) {
        const itemCard = itemsDeck[itemCard_id];

        this.goi.stocks.items.deck.addCard(itemCard);
        this.goi.stocks.items.deck.setCardVisible(itemCard, false);
      }

      if (this.goi.globals.isSolo) {
        document.getElementById("goi_merchant").classList.add("goi_solo");
      }

      this.goi.stocks.items.market = new CardStock(
        this.goi.managers.items,
        document.getElementById("goi_itemsMarket"),
        {
          sort: (a, b) => {
            return Number(b.location_arg) - Number(a.location_arg);
          },
        }
      );

      this.goi.stocks.items.market.onSelectionChange = (
        selection,
        lastChange
      ) => {
        if (selection.length > 0) {
          this.goi.selections.item = lastChange;
        } else {
          this.goi.selections.item = null;
        }

        this.handleSelection();
      };

      const itemsMarket = this.goi.globals.itemsMarket;
      for (const itemCard_id in itemsMarket) {
        const itemCard = itemsMarket[itemCard_id];

        this.goi.stocks.items.market.addCard(itemCard);
      }

      this.goi.stocks.items.active = new AllVisibleDeck(
        this.goi.managers.items,
        document.getElementById(`goi_activeItems`),
        { horizontalShift: "0px", verticalShift: "24px" }
      );

      this.goi.stocks.items.active.onSelectionChange = (
        selection,
        lastChange
      ) => {
        if (selection.length > 0) {
          this.goi.selections.item = lastChange;
        } else {
          this.goi.selections.item = null;
        }

        this.handleItemSelection();
      };

      const activeItems = this.goi.globals.activeItems;
      for (const itemCard_id in activeItems) {
        const itemCard = activeItems[itemCard_id];
        this.goi.stocks.items.active.addCard(itemCard);
      }

      this.goi.stocks.items.discard = new CardStock(
        this.goi.managers.items,
        document.getElementById(`goi_itemsDiscard`)
      );

      const itemsDiscard = this.goi.globals.itemsDiscard;
      for (const itemCard_id in itemsDiscard) {
        const itemCard = itemsDiscard[itemCard_id];
        this.goi.stocks.items.discard.addCard(itemCard);
      }

      if (this.goi.globals.isSolo) {
        const realPlayer = this.goi.globals.realPlayer;
        const bot = this.goi.bot;

        let xclone = document.getElementById(
          `overall_player_board_${realPlayer.id}`
        ).outerHTML;
        xclone = xclone.replaceAll(realPlayer.id, bot.id);
        xclone = xclone.replaceAll(realPlayer.name, bot.name);
        xclone = xclone.replaceAll(realPlayer.color, bot.color);

        xclone = xclone.replaceAll("<a", "<span");
        xclone = xclone.replaceAll("</a>", "</span>");
        xclone = xclone.replaceAll("bga-flag", "");

        document
          .getElementById("player_boards")
          .insertAdjacentHTML("beforeend", xclone);

        document.getElementById(
          `avatar_${bot.id}`
        ).src = `${g_gamethemeurl}/img/solo/rhomAvatar.jpg`;

        document.getElementById(`player_score_${bot.id}`).textContent =
          bot.score;
        document
          .getElementById(`goi_gemCounters:${bot.id}`)
          .querySelector(".goi_coinIcon")
          .remove();

        this.goi.counters[bot.id] = {
          gems: {
            amethyst: new ebg.counter(),
            citrine: new ebg.counter(),
            emerald: new ebg.counter(),
            sapphire: new ebg.counter(),
          },
        };

        const gemCounters = this.goi.counters[bot.id].gems;

        for (const gemName in gemCounters) {
          const gemCounter = gemCounters[gemName];
          gemCounter.create(`goi_gemCounter:${bot.id}-${gemName}`);
          gemCounter.setValue(this.goi.globals.gemsCounts[bot.id][gemName]);
        }

        /* RHOM ZONE */
        const rhomZoneElement = document.getElementById(
          `goi_playerZone:${bot.id}`
        );

        const rhomDeckElement = document.createElement("div");
        rhomDeckElement.id = "goi_rhomDeck";
        rhomDeckElement.classList.add("goi_rhomDeck");
        rhomZoneElement.prepend(rhomDeckElement);

        this.goi.stocks.rhom.deck = new Deck(
          this.goi.managers.rhom,
          rhomDeckElement,
          {
            counter: {
              id: "goi_rhomDeckCounter",
              position: "top",
              extraClasses: "goi_deckCounter",
            },
          }
        );

        const rhomDeck = this.goi.globals.rhomDeck;
        for (const rhomCard_id in rhomDeck) {
          const rhomCard = rhomDeck[rhomCard_id];
          this.goi.stocks.rhom.deck.addCard(rhomCard);
          this.goi.stocks.rhom.deck.setCardVisible(rhomCard, false);
        }

        const rhomDeckTop = this.goi.globals.rhomDeckTop;
        this.goi.stocks.rhom.deck.addCard(rhomDeckTop);
        this.goi.stocks.rhom.deck.setCardVisible(rhomDeckTop, false);

        const rhomDiscardElement = document.createElement("div");
        rhomDiscardElement.id = "goi_rhomDiscard";
        rhomDiscardElement.classList.add("goi_rhomDiscard");
        rhomZoneElement.prepend(rhomDiscardElement);

        this.goi.stocks.rhom.discard = new CardStock(
          this.goi.managers.rhom,
          rhomDiscardElement,
          {
            sort: (a, b) => {
              return b.location_arg - a.location_arg;
            },
          }
        );

        const rhomDiscard = this.goi.globals.rhomDiscard;
        for (const rhomCard_id in rhomDiscard) {
          const rhomCard = rhomDiscard[rhomCard_id];
          this.goi.stocks.rhom.discard.addCard(rhomCard);
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
        const activeEpicElixir = this.goi.stocks.items.active
          .getCards()
          .filter((itemCard) => {
            return itemCard.type_arg == 4;
          });

        this.goi.globals.cancellableItems = activeEpicElixir;
        this.goi.stocks.items.active.setSelectionMode(
          "single",
          activeEpicElixir
        );

        const usableEpicElixir = this.goi.stocks[this.player_id].items.hand
          .getCards()
          .filter((itemCard) => {
            return itemCard.type_arg == 4;
          });

        this.goi.stocks[this.player_id].items.hand.setSelectionMode(
          "single",
          usableEpicElixir
        );

        if (!stateName.includes("client_")) {
          this.goi.selections = this.goi.info.defaultSelections;
        }

        if (
          stateName.includes("client_") &&
          stateName !== "client_regalReferenceBook"
        ) {
          this.addActionButton(
            "goi_cancel_btn",
            _("Cancel"),
            () => {
              this.restoreServerGameState();
            },
            null,
            false,
            "red"
          );
        }

        if (stateName === "revealTile") {
          const revealableTiles = args.args.revealableTiles;
          const expandedRevealableTiles = args.args.expandedRevealableTiles;
          const revealsLimit = args.args.revealsLimit;
          const skippable = args.args.skippable;
          const usableItems = args.args.usableItems;

          if (!skippable) {
            this.gamedatas.gamestate.descriptionmyturn = _(
              "${you} must reveal a tile"
            );
            this.updatePageTitle();
          }

          if (revealsLimit == 1) {
            this.gamedatas.gamestate.descriptionmyturn = _(
              "${you} may reveal another tile"
            );
            this.updatePageTitle();
          }

          if (
            usableItems.length > 0 &&
            usableItems.length !== usableEpicElixir.length
          ) {
            let description = _(
              "${you} may reveal a tile or use an Item with the ${green_flag}"
            );

            this.gamedatas.gamestate.descriptionmyturn =
              this.format_string_recursive(description, {
                you: _("${you}"),
                green_flag: _("green flag"),
                item_name: _("Epic Elixir"),
                item_id: 4,
              });
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

          this.goi.stocks.tiles.board.setSelectionMode(
            "single",
            revealableTiles.length > 0
              ? revealableTiles
              : expandedRevealableTiles
          );

          this.goi.stocks[this.player_id].items.hand.setSelectionMode(
            "single",
            usableItems
          );

          return;
        }

        if (stateName === "discardCollectedTile") {
          const usableItems = args.args.usableItems;
          const singleCollectedTile = args.args.singleCollectedTile;

          if (usableItems.length > 0) {
            this.gamedatas.gamestate.descriptionmyturn =
              this.format_string_recursive(
                _(
                  "${you} have no legal moves and must discard one tile from your Victory Pile or use an Item with the ${green_flag}"
                ),
                {
                  you: _("${you}"),
                  green_flag: _("green flag"),
                }
              );
            this.updatePageTitle();
          } else if (singleCollectedTile) {
            this.goi.selections.tile = singleCollectedTile;

            this.setClientState("client_pickEmptyTile", {
              descriptionmyturn:
                "${you} must pick an empty tile to move your Explorer to",
            });

            document.getElementById("goi_cancel_btn").remove();
            return;
          }

          this.goi.stocks[this.player_id].items.hand.setSelectionMode(
            "single",
            usableItems
          );

          this.goi.stocks[this.player_id].tiles.victoryPile.setSelectionMode(
            "single"
          );
        }

        if (stateName === "client_pickEmptyTile") {
          const emptyHexes = args.args.emptyHexes;

          emptyHexes.forEach((emptyHex) => {
            this.goi.stocks.tiles.empty.addCard(
              { id: -emptyHex },
              {},
              {
                forceToElement: document.getElementById(
                  `goi_tileContainer-${emptyHex}`
                ),
              }
            );
          });

          this.goi.stocks.tiles.empty.setSelectionMode("single");
        }

        if (stateName === "discardTile") {
          const discardableTiles = this.goi.stocks.tiles.board
            .getCards()
            .filter((tileCard) => {
              return tileCard.type != 5;
            });

          this.goi.stocks.tiles.board.setSelectionMode(
            "single",
            discardableTiles
          );
        }

        if (stateName === "moveExplorer") {
          const explorableTiles = args.args.explorableTiles;
          const revealsLimit = args.args.revealsLimit;
          const revealableTiles = args.args.revealableTiles;

          if (revealsLimit < 2 && revealableTiles.length > 0) {
            this.addActionButton(
              "goi_undo_btn",
              _("Reveal another tile"),
              "actUndoSkipRevealTile",
              null,
              false,
              "gray"
            );
          }

          this.goi.stocks.tiles.board.setSelectionMode(
            "single",
            explorableTiles
          );

          return;
        }

        if (stateName === "confirmAutoMove") {
          const usableItems = args.args.usableItems;
          const mustReveal = args.args.mustReveal;

          if (usableItems.length > 0 && !mustReveal) {
            this.gamedatas.gamestate.descriptionmyturn =
              this.format_string_recursive(
                _(
                  "${you} have a single possible move. Proceed onto the available tile or use an Item with the ${green_flag}"
                ),
                {
                  you: _("${you}"),
                  green_flag: _("green flag"),
                }
              );
            this.updatePageTitle();
          }

          if (mustReveal) {
            let description = this.format_string_recursive(
              _("${you} must reveal the only available adjacent tile. Proceed"),
              { you: _("${you}") }
            );

            if (usableItems.length > 0) {
              description = this.format_string_recursive(
                _(
                  "${you} must reveal the only available adjacent tile or use an Item with the ${green_flag}"
                ),
                {
                  you: _("${you}"),
                  green_flag: _("green flag"),
                }
              );
            }

            this.gamedatas.gamestate.descriptionmyturn = description;
            this.updatePageTitle();
          }

          this.goi.stocks[this.player_id].items.hand.setSelectionMode(
            "single",
            usableItems
          );

          this.addActionButton(
            "goi_confirm_btn",
            _("Proceed"),
            "actConfirmAutoMove",
          );
        }

        if (stateName === "rainbowTile") {
          this.generateRainbowOptions(() => {
            this.actPickRainbowGem();
          });
          return;
        }

        if (stateName === "optionalActions") {
          const canMine = args.args.canMine;
          const activeStoneDiceCount = args.args.activeStoneDiceCount;
          const activableStoneDiceCount = args.args.activableStoneDiceCount;
          const canSellGems = args.args.canSellGems;
          const canSellMoreGems = args.args.canSellMoreGems;
          const soldGem = args.args.soldGem;
          const canBuyItem = args.args.canBuyItem;
          const buyableItems = args.args.buyableItems;
          const canUseItem = args.args.canUseItem;
          const usableItems = args.args.usableItems;
          const cancellableItems = args.args.cancellableItems;

          this.goi.globals.cancellableItems = cancellableItems;
          this.goi.stocks.items.active.setSelectionMode(
            "single",
            cancellableItems
          );
          this.goi.stocks[this.player_id].items.hand.setSelectionMode("none");

          this.addActionButton(
            "goi_skip_btn",
            _("Skip"),
            "actSkipOptionalActions",
            null,
            false,
            "red"
          );

          this.addActionButton("goi_mine_btn", _("Mine"), () => {
            if (activableStoneDiceCount === 0) {
              this.goi.selections.dice = [];
              this.actMine();
            } else {
              this.setClientState("client_mine", {
                descriptionmyturn: _(
                  "${you} must pick how many Stone Dice you'd like to roll. Currently active: ${activeStoneDiceCount}"
                ),
                client_args: { activableStoneDiceCount, activeStoneDiceCount },
              });
            }
          });

          if (!canMine) {
            document.getElementById("goi_mine_btn").classList.add("disabled");
          }

          const sellGemsText = canSellMoreGems
            ? _("Sell more Gem(s)")
            : _("Sell Gem(s)");

          this.addActionButton("goi_sellGems_btn", sellGemsText, () => {
            this.setClientState("client_sellGems", {
              descriptionmyturn: _(
                "${you} must select Gem(s) to sell (all from the same type)"
              ),
              client_args: { soldGem: canSellMoreGems ? soldGem : null },
            });
          });

          if (!canSellGems && !canSellMoreGems) {
            document
              .getElementById("goi_sellGems_btn")
              .classList.add("disabled");
          }

          this.addActionButton("goi_buyItem_btn", _("Buy an Item"), () => {
            this.setClientState("client_buyItem", {
              descriptionmyturn: _("${you} must select an Item to buy"),
              client_args: { buyableItems },
            });
          });

          if (!canBuyItem) {
            document
              .getElementById("goi_buyItem_btn")
              .classList.add("disabled");
          }

          this.addActionButton("goi_useItem_btn", _("Use an Item"), () => {
            this.setClientState("client_useItem", {
              descriptionmyturn: _("${you} may select an Item to use"),
              client_args: { usableItems },
            });
          });

          if (!canUseItem) {
            document
              .getElementById("goi_useItem_btn")
              .classList.add("disabled");
          }

          return;
        }

        if (stateName === "client_sellGems") {
          const soldGem = args.client_args.soldGem;
          let selectableGems =
            this.goi.stocks[this.player_id].gems.cargo.getCards();

          if (soldGem) {
            selectableGems = selectableGems.filter((gemCard) => {
              return soldGem == gemCard.type_arg;
            });
          }

          this.goi.stocks[this.player_id].gems.cargo.setSelectionMode(
            "multiple",
            selectableGems
          );
        }

        if (stateName === "client_mine") {
          const activableStoneDiceCount =
            args.client_args.activableStoneDiceCount;

          for (let option = 0; option <= activableStoneDiceCount; option++) {
            const stoneDice =
              this.goi.stocks[this.player_id].dice.scene.getDice();

            this.addActionButton(`goi_mineOption_btn:${option}`, option, () => {
              this.goi.selections.dice = stoneDice
                .filter((die) => {
                  return die.type === "stone";
                })
                .slice(0, option);

              this.actMine();
            });
          }
        }

        if (stateName === "client_buyItem") {
          const buyableItems = args.client_args.buyableItems;
          this.goi.stocks.items.market.setSelectionMode("single", buyableItems);
        }

        if (stateName === "client_useItem") {
          const usableItems = args.client_args.usableItems;

          this.goi.stocks[this.player_id].items.hand.setSelectionMode(
            "single",
            usableItems
          );
        }

        if (stateName === "client_cauldronOfFortune") {
          this.goi.stocks[this.player_id].gems.cargo.setSelectionMode(
            "multiple"
          );
        }

        if (stateName === "client_cauldronOfFortune2") {
          this.generateRainbowOptions(() => {
            this.actUseItem();
          });
        }

        if (stateName === "client_regalReferenceBook") {
          const bookableRelics = args.args.bookableRelics.map((card) => {
            card.id = `book-${card.type_arg}`;
            return card;
          });

          const relicsMarket = bookableRelics.filter((card) => {
            return card.location === "market";
          });

          const relicsDeck = bookableRelics.filter((card) => {
            return card.location === "deck";
          });

          this.generateBookDialog(relicsDeck, relicsMarket);
        }

        if (stateName === "client_luckyLibation") {
          const rerollableDice = [];
          for (const die_id in args.args.rerollableDice) {
            const die = args.args.rerollableDice[die_id];
            rerollableDice.push(die);
          }

          this.goi.stocks.dice.market.setSelectionMode("multiple");
          this.goi.stocks[this.player_id].dice.scene.setSelectionMode(
            "multiple",
            rerollableDice
          );
        }

        if (stateName === "client_joltyJackhammer") {
          const rerollableDice = [];
          for (const die_id in args.args.rerollableDice) {
            const die = args.args.rerollableDice[die_id];
            rerollableDice.push(die);
          }

          console.log(
            rerollableDice,
            this.goi.stocks[this.player_id].dice.scene.getDice(),
            "rerollable"
          );

          this.goi.stocks.dice.market.setSelectionMode("single");
          this.goi.stocks[this.player_id].dice.scene.setSelectionMode(
            "single",
            rerollableDice
          );
        }

        if (stateName === "client_dazzlingDynamite") {
          const rerollableDice = [];
          for (const die_id in args.args.rerollableDice) {
            const die = args.args.rerollableDice[die_id];
            rerollableDice.push(die);
          }

          this.goi.stocks.dice.market.setSelectionMode("single");
          this.goi.stocks[this.player_id].dice.scene.setSelectionMode(
            "single",
            rerollableDice
          );
        }

        if (stateName === "client_axeOfAwesomeness") {
          this.goi.stocks[this.player_id].gems.cargo.setSelectionMode(
            "multiple"
          );
        }

        if (stateName === "client_prosperousPickaxe") {
          const prosperousTiles = args.args.prosperousTiles;

          this.goi.stocks.tiles.board.setSelectionMode(
            "single",
            prosperousTiles
          );
        }

        if (stateName === "client_prosperousPickaxe2") {
          this.generateRainbowOptions(() => {
            this.actUseItem();
          });
        }

        if (stateName === "client_swappingStones") {
          const selectableExplorers = this.goi.stocks.explorers.board
            .getCards()
            .filter((explorerCard) => {
              return explorerCard.type_arg != this.player_id;
            });

          this.goi.stocks.explorers.board.setSelectionMode(
            "single",
            selectableExplorers
          );
        }

        if (stateName === "pickWellGem") {
          const usableItems = args.args.usableItems;
          const pickableGems = args.args.pickableGems;

          if (usableItems.length > usableEpicElixir.length) {
            this.gamedatas.gamestate.descriptionmyturn = _(
              "${you} must select a Gem for the Wishing Well or use an Item"
            );
            this.updatePageTitle();
          }

          this.generateRainbowOptions(() => {
            this.actPickWellGem();
          }, pickableGems);

          this.goi.stocks[this.player_id].items.hand.setSelectionMode(
            "single",
            usableItems
          );
        }

        if (stateName === "client_cleverCatapult") {
          const catapultableTiles = args.args.catapultableTiles;
          const catapultableEmpty = catapultableTiles.empty;

          this.goi.stocks.tiles.board.setSelectionMode(
            "single",
            catapultableTiles.tiles
          );

          for (const emptyHex in catapultableEmpty) {
            this.goi.stocks.tiles.empty.addCard(
              { id: -emptyHex },
              {},
              {
                forceToElement: document.getElementById(
                  `goi_tileContainer-${emptyHex}`
                ),
              }
            );
          }

          this.goi.stocks.tiles.empty.setSelectionMode("single");
        }

        if (stateName === "transferGem") {
          const excedentGems = args.args.excedentGems;
          const availableCargos = args.args.availableCargos;

          this.goi.globals.excedentGems = excedentGems;
          this.goi.globals.availableCargos = availableCargos;

          if (availableCargos.length === 0) {
            this.gamedatas.gamestate.descriptionmyturn =
              this.format_string_recursive(
                _(
                  "The cargos of all players are full. ${you} must pick up to ${excedentGems} Gem(s) to discard"
                ),
                {
                  excedentGems: excedentGems,
                  you: _("${you}"),
                }
              );
            this.updatePageTitle();
          }

          this.goi.stocks[this.player_id].gems.cargo.setSelectionMode(
            "multiple"
          );
        }

        if (stateName === "client_transferGem") {
          const selectedGems = args.client_args.selectedGems;
          const availableCargos = args.client_args.availableCargos;

          this.goi.selections.gems = selectedGems;

          for (const player_id in this.goi.globals.players) {
            const zoneElement = document.getElementById(
              `goi_playerZoneContainer:${player_id}`
            );

            if (!availableCargos.includes(Number(player_id))) {
              zoneElement.classList.add("goi_unselectablePlayerZone");
              continue;
            }

            zoneElement.classList.add("goi_selectablePlayerZone");

            zoneElement.onclick = () => {
              document
                .querySelectorAll(".goi_selectedPlayerZone")
                .forEach((element) => {
                  if (element.id === zoneElement.id) {
                    return;
                  }
                  element.classList.remove("goi_selectedPlayerZone");
                });

              zoneElement.classList.toggle("goi_selectedPlayerZone");

              if (zoneElement.classList.contains("goi_selectedPlayerZone")) {
                this.goi.selections.opponent = player_id;
              } else {
                this.goi.selections.opponent = null;
              }

              this.handleSelection();
            };
          }
        }

        if (stateName === "restoreRelic") {
          const restorableRelics = args.args.restorableRelics;
          const canRestoreBook = args.args.canRestoreBook;

          this.addActionButton(
            "goi_undo_btn",
            _("Perform another optional action"),
            "actUndoSkipOptionalActions",
            null,
            false,
            "gray"
          );

          this.addActionButton(
            "goi_skip_btn",
            _("Skip"),
            "actSkipRestoreRelic",
            null,
            false,
            "red"
          );

          this.goi.stocks.relics.market.setSelectionMode("single");
          this.goi.stocks.relics.market.setSelectableCards(restorableRelics);

          if (canRestoreBook) {
            this.goi.stocks[this.player_id].relics.book.setSelectionMode(
              "single"
            );
          }
        }

        if (stateName === "discardObjective") {
          this.goi.stocks[this.player_id].objectives.hand.setSelectionMode(
            "single"
          );
        }

        if (stateName === "startSolo") {
          this.addActionButton("goi_startSolo_btn", _("Start"), "actStartSolo");
        }

        if (stateName === "pickRainbowForRhom") {
          const pickableGems = args.args.pickableGems;

          this.generateRainbowOptions(() => {
            this.actPickRainbowForRhom();
          }, pickableGems);
        }

        if (stateName === "discardTileForRhom") {
          const bot = this.goi.bot;
          this.goi.stocks[bot.id].tiles.victoryPile.setSelectionMode("single");
        }

        return;
      }

      if (stateName === "transferGem") {
        const excedentGems = args.args.excedentGems;
        const availableCargos = args.args.availableCargos;

        if (availableCargos.length === 0) {
          this.gamedatas.gamestate.descriptionmyturn =
            this.format_string_recursive(
              _(
                "The cargos of all players are full. ${actplayer} must pick up to ${excedentGems} Gem(s) to discard"
              ),
              {
                actplayer: _("${actplayer}"),
                excedentGems: excedentGems,
              }
            );
          this.updatePageTitle();
        }
        return;
      }
    },

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      if (!this.goi.globals.player) {
        return;
      }

      this.goi.stocks[this.player_id].items.hand.setSelectionMode("none");

      if (stateName === "revealTile") {
        this.goi.stocks.tiles.board.setSelectionMode("none");
      }

      if (stateName === "discardCollectedTile") {
        this.goi.stocks[this.player_id].items.hand.setSelectionMode("none");
        this.goi.stocks[this.player_id].tiles.victoryPile.setSelectionMode(
          "none"
        );
      }

      if (stateName === "client_pickEmptyTile") {
        this.goi.stocks.tiles.empty.setSelectionMode("none");
        this.goi.stocks.tiles.empty.removeAll();
      }

      if (stateName === "discardTile") {
        this.goi.stocks.tiles.board.setSelectionMode("none");
      }

      if (stateName === "rainbowTile") {
        this.goi.stocks.gems.rainbowOptions.removeAll();
      }

      if (stateName === "moveExplorer") {
        this.goi.stocks.tiles.board.setSelectionMode("none");
      }

      if (stateName === "discardObjective") {
        this.goi.stocks[this.player_id].objectives.hand.setSelectionMode(
          "none"
        );
      }

      if (stateName === "optionalActions") {
        this.goi.stocks.items.active.setSelectionMode("none");
      }

      if (stateName === "client_sellGems") {
        this.goi.stocks[this.player_id].gems.cargo.setSelectionMode("none");
      }

      if (stateName === "client_buyItem") {
        this.goi.stocks.items.market.setSelectionMode("none");
      }

      if (stateName === "client_useItem") {
        this.goi.stocks[this.player_id].items.hand.setSelectionMode("none");
      }

      if (stateName === "client_cauldronOfFortune") {
        this.goi.stocks[this.player_id].gems.cargo.setSelectionMode("none");
      }

      if (stateName === "client_cauldronOfFortune2") {
        this.goi.stocks.gems.rainbowOptions.setSelectionMode("none");
        this.goi.stocks.gems.rainbowOptions.removeAll();
      }

      if (stateName === "client_luckyLibation") {
        this.goi.stocks.dice.market.setSelectionMode("none");
        this.goi.stocks[this.player_id].dice.scene.setSelectionMode("none");
      }

      if (stateName === "client_joltyJackhammer") {
        this.goi.stocks.dice.market.setSelectionMode("none");
        this.goi.stocks[this.player_id].dice.scene.setSelectionMode("none");
      }

      if (stateName === "client_dazzlingDynamite") {
        this.goi.stocks.dice.market.setSelectionMode("none");
        this.goi.stocks[this.player_id].dice.scene.setSelectionMode("none");
      }

      if (stateName === "client_axeOfAwesomeness") {
        this.goi.stocks[this.player_id].gems.cargo.setSelectionMode("none");
      }

      if (stateName === "client_prosperousPickaxe") {
        this.goi.stocks.tiles.board.setSelectionMode("none");
      }

      if (stateName === "client_cleverCatapult") {
        this.goi.stocks.tiles.board.setSelectionMode("none");
        this.goi.stocks.tiles.empty.setSelectionMode("none");
        this.goi.stocks.tiles.empty.removeAll();
      }

      if (stateName === "client_swappingStones") {
        this.goi.stocks.explorers.board.setSelectionMode("none");
      }

      if (stateName === "transferGem") {
        this.goi.stocks[this.player_id].gems.cargo.setSelectionMode("none");
      }

      if (stateName === "client_transferGem") {
        this.goi.stocks[this.player_id].gems.cargo.setSelectionMode("none");
        this.goi.selections.gems = [];
        this.goi.selections.opponent = null;

        for (const player_id in this.goi.globals.players) {
          const zoneElement = document.getElementById(
            `goi_playerZoneContainer:${player_id}`
          );
          zoneElement.classList.remove("goi_unselectablePlayerZone");
          zoneElement.classList.remove("goi_selectablePlayerZone");
          zoneElement.classList.remove("goi_selectedPlayerZone");
          zoneElement.onclick = undefined;
        }
      }

      if (stateName === "restoreRelic") {
        this.goi.stocks.relics.market.setSelectionMode("none");
        this.goi.stocks[this.player_id].relics.book.setSelectionMode("none");
      }

      if (stateName === "discardTileForRhom") {
        const bot = this.goi.bot;
        this.goi.stocks[bot.id].tiles.victoryPile.setSelectionMode("none");
      }
    },

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName, args);

      this.attachRegisteredTooltips();
    },

    ///////////////////////////////////////////////////
    //// Utility methods
    getStatWithRhomeName: function () {
      return this.gamedatas.gamestate.name;
    },

    playSound: function (sound, duration = null) {
      if (this.getGameUserPreference(102) == 0) {
        return;
      }

      this.disableNextMoveSound();
      playSound(`gemsofiridescia_${sound}`);

      if (duration) {
        setTimeout(() => {
          stopSound(`gemsofiridescia_${sound}`);
        }, duration);
      }
    },

    calcBackgroundPosition: function (spritePosition) {
      return -spritePosition * 100 + "% 0%";
    },

    calcCoinPosition: function (coins) {
      let positionLeft = coins >= 10 ? "23%" : "32%";

      if (coins >= 20) {
        positionLeft = "17%";
      }

      if (coins === 11 || coins === 4) {
        positionLeft = "30%";
      }

      if (coins === 1) {
        positionLeft = "36%";
      }

      return positionLeft;
    },

    generateRainbowOptions: function (callback, pickableGems) {
      if (!pickableGems) {
        pickableGems = this.goi.globals.gemsCounts[this.player_id];
      }

      for (const gemName in pickableGems) {
        const gem_id = this.goi.info.gems.ids[gemName];
        const buttonId = `goi_rainbow-${gem_id}_btn`;

        const backgroundPosition = this.calcBackgroundPosition(gem_id);

        this.addActionButton(
          buttonId,
          `<div class="goi_gem card" style="background-position: ${backgroundPosition}"></div>`,
          () => {
            this.goi.selections.gem = gem_id;
            callback();
          },
          null,
          false,
          "gray"
        );

        document.getElementById(buttonId).style.border = "none";
      }
    },

    generateItemButton: function (item_id, elementId, isCancellable) {
      const itemName = this.goi.info.items[item_id].tr_name;

      if (!isCancellable) {
        const message = this.format_string(_("Use ${item_name}"), {
          item_name: _(itemName),
        });

        this.addActionButton(elementId, message, "onUseItem");
        return;
      }

      const message = this.format_string(_("Cancel ${item_name}"), {
        item_name: _(itemName),
      });

      this.addActionButton(
        elementId,
        message,
        "actUndoItem",
        null,
        false,
        "gray"
      );
    },

    generateBookDialog: async function (relicsDeck, relicsMarket) {
      this.goi.dialog = new ebg.popindialog();
      this.goi.dialog.create("goi_bookDialog");
      this.goi.dialog.setTitle(_("Regal Reference Book"));
      this.goi.dialog.setMaxWidth(740);
      this.goi.dialog.replaceCloseCallback((event) => {
        this.goi.dialog.destroy();
        this.restoreServerGameState();
      });

      const buttonId = "goi_confirm_btn";

      const html = `<div id="goi_content_dlg" class="goi_content_dlg">
      <div id="goi_buttonContainer_dlg" class="goi_buttonContainer_dlg"></div>
        <div id="goi_marketContainer_dlg">
            <h4>${_("Row")}</h4>
            <div id="goi_relicsMarket_dlg" class="goi_relicsMarket"></div>
        </div>
        <div id="goi_deckContainer_dlg">
            <h4>${_("Deck")}</h4>
            <div id="goi_relicsDeck_dlg" class="goi_relicsMarket"></div>
        </div>
      </div>`;

      this.goi.dialog.setContent(html);
      this.goi.dialog.show();

      const dialogElement = document.getElementById("popin_goi_bookDialog");
      dialogElement.classList.add("goi_dialog");

      const selectionChange = (selection, lastChange) => {
        document
          .getElementById(buttonId)
          .classList.toggle("disabled", selection.length === 0);

        if (selection.length > 0) {
          this.goi.selections.relic = lastChange;
        } else {
          this.goi.selections.relic = null;
        }
      };

      this.goi.stocks.relics.market_dlg = new CardStock(
        this.goi.managers.relics,
        document.getElementById("goi_relicsMarket_dlg"),
        {}
      );

      this.goi.stocks.relics.market_dlg.onSelectionChange = (
        selection,
        lastChange
      ) => {
        this.goi.stocks.relics.deck_dlg.unselectAll(true);
        selectionChange(selection, lastChange);
      };

      this.goi.stocks.relics.market_dlg.addCards(relicsMarket);
      this.goi.stocks.relics.market_dlg.setSelectionMode("single");

      this.goi.stocks.relics.deck_dlg = new CardStock(
        this.goi.managers.relics,
        document.getElementById("goi_relicsDeck_dlg"),
        {}
      );

      this.goi.stocks.relics.deck_dlg.onSelectionChange = (
        selection,
        lastChange
      ) => {
        this.goi.stocks.relics.market_dlg.unselectAll(true);
        selectionChange(selection, lastChange);
      };

      this.goi.stocks.relics.deck_dlg.addCards(relicsDeck);
      this.goi.stocks.relics.deck_dlg.setSelectionMode("single");

      this.addActionButton(
        buttonId,
        _("Confirm selection"),
        () => {
          this.goi.dialog.destroy();
          this.goi.stocks.relics.market_dlg.remove();
          this.goi.stocks.relics.deck_dlg.remove();

          this.actUseItem();
        },
        "goi_buttonContainer_dlg",
        false
      );

      document.getElementById(buttonId).classList.add("disabled");
    },

    findFreeBox: function (player_id) {
      const occupiedBoxes = [];

      this.goi.stocks[player_id].gems.cargo.getCards().forEach((gemCard) => {
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

      this.goi.stocks[player_id].gems.cargo.addCard(
        gemCard,
        {
          fromElement: originElement || document.getElementById("goi_void"),
        },
        {
          forceToElement: destinationElement,
        }
      );
    },

    handleSelection: function (
      elementId = "goi_confirmation_btn",
      message = _("Confirm selection")
    ) {
      document.getElementById(elementId)?.remove();
      const stateName = this.getStatWithRhomeName();

      if (stateName === "revealTile") {
        if (this.goi.selections.tile) {
          this.addActionButton(elementId, message, "actRevealTile");
        }
      }

      if (stateName === "discardCollectedTile") {
        if (this.goi.selections.tile) {
          this.addActionButton(elementId, message, () => {
            this.setClientState("client_pickEmptyTile", {
              descriptionmyturn:
                "${you} must pick an empty tile to move your Explorer to",
            });
          });
          return;
        }
      }

      if (stateName === "discardTileForRhom") {
        if (this.goi.selections.tile) {
          this.addActionButton(elementId, message, "actDiscardTileForRhom");
        }
      }

      if (stateName === "client_pickEmptyTile") {
        if (this.goi.selections.tile) {
          this.addActionButton(elementId, message, () => {
            this.actDiscardCollectedTile();
          });
        }
      }

      if (stateName === "discardTile") {
        if (this.goi.selections.tile) {
          this.addActionButton(elementId, message, "actDiscardTile");
          return;
        }
      }

      if (stateName === "rainbowTile") {
        const selectedGem = this.goi.selections.gem;

        if (selectedGem) {
          this.addActionButton(elementId, message, "actPickRainbowGem");
          return;
        }
      }

      if (stateName === "moveExplorer") {
        if (this.goi.selections.tile) {
          this.addActionButton(elementId, message, "actMoveExplorer");
          return;
        }
      }

      if (stateName === "client_sellGems") {
        this.addActionButton(elementId, message, "actSellGems");
        return;
      }

      if (stateName === "client_buyItem") {
        this.addActionButton(elementId, message, "actBuyItem");
        return;
      }

      if (stateName === "client_cauldronOfFortune") {
        if (this.goi.selections.gems.length === 2) {
          this.addActionButton(elementId, message, () => {
            this.setClientState("client_cauldronOfFortune2", {
              descriptionmyturn: _(
                "${you} may pick the type of the Gem collected in the trade"
              ),
            });
          });
        }
        return;
      }

      if (stateName === "client_cauldronOfFortune2") {
        if (this.goi.selections.gem) {
          this.addActionButton(elementId, message, "actUseItem");
        }
        return;
      }

      if (stateName === "client_luckyLibation") {
        if (this.goi.selections.dice.length > 0) {
          this.addActionButton(elementId, message, "actUseItem");
        }
      }

      if (stateName === "client_cleverCatapult") {
        if (this.goi.selections.tile) {
          this.addActionButton(elementId, message, "actUseItem");
        }
        return;
      }

      if (stateName === "client_swappingStones") {
        if (this.goi.selections.opponent) {
          this.addActionButton(elementId, message, "actUseItem");
        }
        return;
      }

      if (stateName === "client_joltyJackhammer") {
        document.getElementById("goi_-1_btn")?.remove();
        document.getElementById("goi_+1_btn")?.remove();

        if (this.goi.selections.die) {
          this.addActionButton("goi_-1_btn", "-1", () => {
            this.goi.selections.delta = -1;
            this.actUseItem();
          });

          this.addActionButton("goi_+1_btn", "+1", () => {
            this.goi.selections.delta = +1;
            this.actUseItem();
          });
        }

        return;
      }

      if (stateName === "client_dazzlingDynamite") {
        document.getElementById("goi_-1_btn")?.remove();
        document.getElementById("goi_+1_btn")?.remove();

        document.getElementById("goi_-2_btn")?.remove();
        document.getElementById("goi_+2_btn")?.remove();

        if (this.goi.selections.die) {
          this.addActionButton("goi_-1_btn", "-1", () => {
            this.goi.selections.delta = -1;
            this.actUseItem();
          });

          this.addActionButton("goi_+1_btn", "+1", () => {
            this.goi.selections.delta = 1;
            this.actUseItem();
          });

          this.addActionButton("goi_-2_btn", "-2", () => {
            this.goi.selections.delta = -2;
            this.actUseItem();
          });

          this.addActionButton("goi_+2_btn", "+2", () => {
            this.goi.selections.delta = 2;
            this.actUseItem();
          });
        }

        return;
      }

      if (stateName === "client_axeOfAwesomeness") {
        if (this.goi.selections.gem) {
          this.addActionButton(elementId, message, "actUseItem");
        }
        return;
      }

      if (stateName === "client_prosperousPickaxe") {
        const selectedTile = this.goi.selections.tile;
        if (selectedTile) {
          const tile_id = selectedTile.type_arg;
          const gem_id = this.goi.info.tiles[tile_id].gem;

          this.addActionButton(elementId, message, () => {
            if (gem_id == 0 || gem_id == 10) {
              this.setClientState("client_prosperousPickaxe2", {
                descriptionmyturn: _(
                  "${you} must pick the Gem to mine from the Rainbow"
                ),
              });
              return;
            }

            this.actUseItem();
          });
        }
      }

      if (stateName === "transferGem") {
        if (this.goi.selections.gems.length > 0) {
          this.addActionButton(elementId, message, () => {
            const availableCargos = this.goi.globals.availableCargos;

            if (availableCargos.length === 0) {
              this.actTransferGem();
              return;
            }

            const selectedGems = this.goi.selections.gems;

            const client_availableCargos = availableCargos.filter(
              (player_id) => {
                const totalGemsCount =
                  this.goi.stocks[player_id].gems.cargo.getCards().length;

                return totalGemsCount + selectedGems.length <= 7;
              }
            );

            this.setClientState("client_transferGem", {
              descriptionmyturn:
                "${you} must pick an opponent to transfer the selected Gem to",
              client_args: {
                selectedGems,
                availableCargos: client_availableCargos,
              },
            });
          });
        }
      }

      if (stateName === "client_transferGem") {
        if (
          this.goi.selections.gems.length > 0 &&
          this.goi.selections.opponent
        ) {
          this.addActionButton(elementId, message, "actTransferGem");
          return;
        }
      }

      if (stateName === "restoreRelic") {
        const selectedRelic = this.goi.selections.relic;

        if (selectedRelic) {
          this.addActionButton(elementId, message, "actRestoreRelic");
          return;
        }
      }

      if (stateName === "discardObjective") {
        const selectedObjective = this.goi.selections.objective;

        if (selectedObjective) {
          this.addActionButton(elementId, message, "actDiscardObjective");
          return;
        }
      }
    },

    handleItemSelection: function () {
      const selectedItem = this.goi.selections.item;

      const elementId = "goi_confirmItem_btn";
      document.getElementById(elementId)?.remove();

      if (selectedItem) {
        const item_id = Number(selectedItem.type_arg);
        const itemCard_id = Number(selectedItem.id);

        const isCancellable = this.goi.globals.cancellableItems.some(
          (itemCard) => {
            return itemCard.id == itemCard_id;
          }
        );

        this.generateItemButton(item_id, elementId, isCancellable);
      }
    },

    addCardTitle: function (titleElement, initialFont = 16, cardHeight = 230) {
      const titleHeight = titleElement.offsetHeight;
      const maxHeight = cardHeight * 0.09;

      if (titleHeight > maxHeight) {
        const fontSize = initialFont * 0.98;
        titleElement.style.fontSize = `${fontSize}px`;

        requestAnimationFrame(() => {
          this.addCardTitle(titleElement, fontSize, cardHeight);
        }, 0);
      }
    },

    addItemContent: function (
      item_id,
      cardElement,
      contentElement,
      initialFont = 14,
      cardHeight = 230
    ) {
      if (!contentElement) {
        const itemInfo = this.goi.info.items[item_id];
        const itemContent = itemInfo.content;

        contentElement = document.createElement("span");
        contentElement.textContent = _(itemContent);
        contentElement.classList.add("goi_cardContent");

        contentElement.style.fontFamily = "'rooney-web', serif";
        contentElement.style.fontSize = `${initialFont}px`;
        cardElement.appendChild(contentElement);
      }

      const contentHeight = contentElement.offsetHeight;
      const maxHeight = cardHeight * 0.14;

      if (contentHeight > maxHeight) {
        const fontSize = initialFont * 0.98;
        contentElement.style.fontSize = `${fontSize}px`;

        requestAnimationFrame(() => {
          this.addItemContent(
            item_id,
            cardElement,
            contentElement,
            fontSize,
            cardHeight
          );
        }, 0);
      }
    },

    addObjectiveContent: function (
      objective_id,
      cardElement,
      contentElement,
      initialFont = 12,
      cardHeight = 230
    ) {
      if (!contentElement) {
        const objectiveInfo = this.goi.info.objectives[objective_id];
        const objectiveContent = objectiveInfo.content;

        contentElement = document.createElement("span");
        contentElement.textContent = _(objectiveContent);
        contentElement.classList.add("goi_cardContent");

        contentElement.style.fontFamily = `"rooney-web", serif`;
        contentElement.style.fontSize = `${initialFont}px`;
        cardElement.appendChild(contentElement);
      }

      const contentHeight = contentElement.offsetHeight;
      const maxHeight = cardHeight * 0.12;

      if (contentHeight > maxHeight) {
        const fontSize = initialFont * 0.98;
        contentElement.style.fontSize = `${fontSize}px`;

        requestAnimationFrame(() => {
          contentElement.style.transform = "translateY(-50%)";
          this.addObjectiveContent(
            objective_id,
            cardElement,
            contentElement,
            fontSize,
            cardHeight
          );
        }, 0);
      }
    },

    addAidContent: function (div, contentElement, initialFont = 16) {
      const contentHeight = contentElement.offsetHeight;
      const maxHeight = 409 * 0.6;

      if (contentHeight > maxHeight) {
        const fontSize = initialFont * 0.98;
        contentElement.style.fontSize = `${fontSize}px`;

        requestAnimationFrame(() => {
          this.addAidContent(div, contentElement, fontSize);
        }, 0);
      }
    },

    ///////////////////////////////////////////////////
    //// Player's action

    performAction: function (action, args = {}, options = {}) {
      args.clientVersion = this.goi.version;
      this.bgaPerformAction(action, args, options);
    },

    actRevealTile: function () {
      this.performAction("actRevealTile", {
        tileCard_id: this.goi.selections.tile.id,
      });
    },

    actSkipRevealTile: function () {
      this.performAction("actSkipRevealTile");
    },

    actUndoSkipRevealTile: function () {
      this.performAction("actUndoSkipRevealTile");
    },

    actDiscardCollectedTile: function () {
      this.performAction("actDiscardCollectedTile", {
        tileCard_id: this.goi.selections.tile.id,
        emptyHex: Math.abs(this.goi.selections.emptyTile.id),
      });
    },

    actDiscardTile: function () {
      this.performAction("actDiscardTile", {
        tileCard_id: this.goi.selections.tile.id,
      });
    },

    actMoveExplorer: function () {
      this.performAction("actMoveExplorer", {
        tileCard_id: this.goi.selections.tile.id,
      });
    },

    actConfirmAutoMove: function () {
      this.performAction("actConfirmAutoMove");
    },

    actPickRainbowGem: function () {
      this.performAction("actPickRainbowGem", {
        gem_id: this.goi.selections.gem,
      });
    },

    actMine: function () {
      this.performAction("actMine", {
        stoneDice: JSON.stringify(this.goi.selections.dice),
      });
    },

    actSellGems: function () {
      const selectedGems = this.goi.selections.gems;
      this.performAction("actSellGems", {
        gem_id: selectedGems[0].type_arg,
        selectedGems: JSON.stringify(selectedGems),
      });
    },

    actBuyItem: function () {
      this.performAction("actBuyItem", {
        itemCard_id: this.goi.selections.item.id,
      });
    },

    onUseItem: function () {
      const item_id = Number(this.goi.selections.item.type_arg);

      const instantaneousItems = [3, 4, 12];

      if (instantaneousItems.includes(item_id)) {
        this.actUseItem();
      }

      if (item_id === 1) {
        this.setClientState("client_cauldronOfFortune", {
          descriptionmyturn: _(
            "${you} must select any 2 Gems in your cargo to trade for another Gem"
          ),
        });
      }

      if (item_id === 2) {
        this.setClientState("client_regalReferenceBook", {
          descriptionmyturn: _("${you} must select a Relic to reserve for you"),
        });
      }

      if (item_id === 5) {
        this.setClientState("client_luckyLibation", {
          descriptionmyturn: _(
            "${you} must select any dice from a mining atempt or a Gem Market Die to re-roll"
          ),
        });
      }

      if (item_id === 6) {
        this.setClientState("client_joltyJackhammer", {
          descriptionmyturn: _("${you} must select a die to modify its value"),
        });
      }

      if (item_id === 7) {
        this.setClientState("client_dazzlingDynamite", {
          descriptionmyturn: _("${you} must select a die to modify its value"),
        });
      }

      if (item_id === 8) {
        this.setClientState("client_axeOfAwesomeness", {
          descriptionmyturn: _(
            "${you} must select any Gem in your cargo to split into 2 Gems"
          ),
        });
      }

      if (item_id === 9) {
        this.setClientState("client_prosperousPickaxe", {
          descriptionmyturn: _(
            "${you} must select a tile to collect Gems from when mining during this turn"
          ),
        });
      }

      if (item_id === 10) {
        this.setClientState("client_swappingStones", {
          descriptionmyturn: _(
            "${you} must select an opponent explorer to swap location with"
          ),
        });
      }

      if (item_id === 11) {
        this.setClientState("client_cleverCatapult", {
          descriptionmyturn: _("${you} must select a tile to jump to"),
        });
      }
    },

    actUseItem: function () {
      const selectedItem = this.goi.selections.item;
      const item_id = Number(selectedItem.type_arg);

      if (item_id === 4) {
        this.performAction(
          "actUseEpicElixir",
          {
            itemCard_id: selectedItem.id,
          },
          { checkAction: false }
        );
        return;
      }

      let args = {};

      if (item_id === 1) {
        const oldGemCards_ids = this.goi.selections.gems.map((gemCard) => {
          return Number(gemCard.id);
        });

        args = {
          oldGemCards_ids: oldGemCards_ids,
          newGem_id: this.goi.selections.gem,
        };
      }

      if (item_id === 2) {
        args = {
          relic_id: this.goi.selections.relic.type_arg,
        };
      }

      if (item_id === 5) {
        args = {
          diceType: this.goi.selections.dice[0].type,
          dice: this.goi.selections.dice,
        };
      }

      if (item_id === 6) {
        args = {
          die_id: this.goi.selections.die.id,
          dieType: this.goi.selections.die.type,
          delta: this.goi.selections.delta,
        };
      }

      if (item_id === 7) {
        args = {
          die_id: this.goi.selections.die.id,
          dieType: this.goi.selections.die.type,
          delta: this.goi.selections.delta,
        };
      }

      if (item_id === 8) {
        args = {
          gemCard_id: this.goi.selections.gem.id,
        };
      }

      if (item_id === 9) {
        args = {
          tileCard_id: this.goi.selections.tile.id,
          rainbowGem: this.goi.selections.gem,
        };
      }

      if (item_id === 10) {
        args = {
          opponent_id: this.goi.selections.opponent,
        };
      }

      if (item_id === 11) {
        args = {
          tileCard_id: this.goi.selections.tile.id,
        };
      }

      if (item_id === 12) {
        args = {};
      }

      this.performAction("actUseItem", {
        itemCard_id: selectedItem.id,
        args: JSON.stringify(args),
      });
    },

    actUndoItem: function () {
      const selectedItem = this.goi.selections.item;
      const item_id = Number(selectedItem.type_arg);

      if (item_id === 4) {
        this.performAction(
          "actUndoItem",
          {
            itemCard_id: selectedItem.id,
          },
          { checkAction: false }
        );
        return;
      }

      this.performAction("actUndoItem", {
        itemCard_id: selectedItem.id,
      });
    },

    actPickWellGem: function () {
      this.bgaPerformAction("actPickWellGem", {
        gem_id: this.goi.selections.gem,
      });
    },

    actTransferGem: function () {
      this.performAction("actTransferGem", {
        gemCards: JSON.stringify(this.goi.selections.gems),
        opponent_id: this.goi.selections.opponent,
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
        relicCard_id: this.goi.selections.relic.id,
      });
    },

    actSkipRestoreRelic: function () {
      this.performAction("actSkipRestoreRelic");
    },

    actDiscardObjective: function () {
      this.performAction("actDiscardObjective", {
        objectiveCard_id: this.goi.selections.objective.id,
      });
    },

    actStartSolo: function () {
      this.performAction("actStartSolo");
    },

    actPickRainbowForRhom: function () {
      this.performAction("actPickRainbowForRhom", {
        gem_id: this.goi.selections.gem,
      });
    },

    actDiscardTileForRhom: function () {
      this.performAction("actDiscardTileForRhom", {
        tileCard_id: this.goi.selections.tile.id,
      });
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    setupNotifications: function () {
      console.log("notifications subscriptions setup");
      const notifications = [
        { event: "revealTile" },
        { event: "discardCollectedTile" },
        { event: "discardTile" },
        { event: "moveExplorer" },
        { event: "resetExplorer" },
        { event: "incRoyaltyPoints", duration: 1000 },
        { event: "obtainStoneDie" },
        { event: "activateStoneDie" },
        { event: "resetStoneDice" },
        { event: "rollDie", duration: 100 },
        { event: "joltyJackhammer" },
        { event: "dynamiteSFX" },
        { event: "syncDieRolls", duration: 1000 },
        { event: "incCoin" },
        { event: "incGem" },
        { event: "decGem" },
        { event: "transferGem" },
        { event: "discardGems" },
        { event: "obtainIridiaStone" },
        { event: "obtainRoyaltyToken" },
        { event: "restoreRelic" },
        { event: "replaceRelic", duration: 1000 },
        { event: "reshuffleItemsDeck", duration: 5000 },
        { event: "buyItem" },
        { event: "replaceItem", duration: 1000 },
        { event: "activateItem" },
        { event: "cancelItem" },
        { event: "discardItem", duration: 100 },
        { event: "regalReferenceBook", duration: 1000 },
        { event: "swappingStones", duration: 1000 },
        { event: "cleverCatapult" },
        { event: "collectTile" },
        { event: "updateMarketValue" },
        {
          event: "discardObjective",
          ignoreCurrentPlayer: true,
        },
        {
          event: "discardObjective_priv",
        },
        {
          event: "revealObjective",
          duration: 1000,
          ignoreCurrentPlayer: true,
        },
        { event: "completeObjective", duration: 1000 },
        { event: "zombieQuit" },
        { event: "rhomDrawCard" },
        { event: "reshuffleRhomDeck", duration: 1000 },
        { event: "rhomBarricade" },
      ];

      notifications.forEach((notif) => {
        const event = notif.event;
        let duration = notif.duration;
        const ignoreCurrentPlayer = notif.ignoreCurrentPlayer;

        dojo.subscribe(event, this, `notif_${event}`);

        if (ignoreCurrentPlayer) {
          this.notifqueue.setIgnoreNotificationCheck(event, (notif) => {
            return notif.args.player_id == this.player_id;
          });
        }

        if (duration === 0) {
          return;
        }

        if (duration === undefined) {
          duration = 500;
        }

        this.notifqueue.setSynchronous(event, duration);
      });
    },

    notif_revealTile: function (notif) {
      const tileCard = notif.args.tileCard;
      this.goi.stocks.tiles.board.flipCard(tileCard);

      this.playSound("flip");
    },

    notif_discardCollectedTile: function (notif) {
      const tileCard = notif.args.tileCard;

      this.goi.stocks.tiles.void.addCard(tileCard);
    },

    notif_discardTile: function (notif) {
      const tileCard = notif.args.tileCard;

      this.goi.stocks.tiles.void.addCard(tileCard);
    },

    notif_moveExplorer: function (notif) {
      const explorerCard = notif.args.explorerCard;
      const hex = notif.args.hex;

      this.goi.stocks.explorers.board.removeCard(explorerCard);

      this.goi.stocks.explorers.board.addCard(
        explorerCard,
        {
          fromElement: document.getElementById(
            `goi_tileContainer-${explorerCard.location_arg}`
          ),
        },
        {
          forceToElement: document.getElementById(`goi_tileContainer-${hex}`),
        }
      );
    },

    notif_resetExplorer: function (notif) {
      const player_id = notif.args.player_id;
      const explorerCard = notif.args.explorerCard;

      this.goi.stocks[player_id].explorers.scene.addCard(explorerCard);
    },

    notif_incGem: function (notif) {
      const player_id = notif.args.player_id;
      const delta = notif.args.delta;
      const gemName = notif.args.gemName;
      const gemCards = notif.args.gemCards;
      const tileCard = notif.args.tileCard;

      this.goi.counters[player_id].gems[gemName].incValue(delta);

      for (const gemCard_id in gemCards) {
        const gemCard = gemCards[gemCard_id];
        const hex = tileCard?.location_arg;

        this.addGemToCargo(
          gemCard,
          player_id,
          hex ? document.getElementById(`goi_tileContainer-${hex}`) : undefined
        );
      }

      this.playSound("gems");
    },

    notif_decGem: function (notif) {
      const player_id = notif.args.player_id;
      const gemName = notif.args.gemName;
      const delta = notif.args.delta;
      const gemCards = notif.args.gemCards;

      this.goi.counters[player_id].gems[gemName].incValue(-delta);

      for (const gemCard_id in gemCards) {
        const gemCard = gemCards[gemCard_id];
        this.goi.stocks.gems.void.addCard(gemCard);
      }
    },

    notif_transferGem: function (notif) {
      const player_id = notif.args.player_id;
      const opponent_id = notif.args.player_id2;
      const transferredGemCards = notif.args.gemCards;
      const gemName = notif.args.gemName;
      const delta = notif.args.delta;

      transferredGemCards.forEach((transferredGemCard) => {
        this.addGemToCargo(transferredGemCard, opponent_id);
      });

      const newGemCards = this.goi.stocks[player_id].gems.cargo
        .getCards()
        .filter((gemCard) => {
          return !gemCard.box;
        })
        .slice(0, delta);

      if (newGemCards.length > 0) {
        this.goi.stocks[player_id].gems.cargo.removeCards(newGemCards);

        newGemCards.forEach((newGemCard) => {
          this.addGemToCargo(
            newGemCard,
            player_id,
            document.getElementById(`goi_cargoExcedent:${player_id}`)
          );
        });
      }

      this.goi.counters[player_id].gems[gemName].incValue(-delta);
      this.goi.counters[opponent_id].gems[gemName].incValue(delta);
    },

    notif_discardGems: function (notif) {
      const player_id = notif.args.player_id;
      const delta = notif.args.delta;

      let newGemCards = this.goi.stocks[player_id].gems.cargo
        .getCards()
        .filter((gemCard) => {
          return !gemCard.box;
        })
        .slice(0, delta);

      if (newGemCards.length > 0) {
        this.goi.stocks[player_id].gems.cargo.removeCards(newGemCards);

        newGemCards.forEach((newGemCard) => {
          this.addGemToCargo(
            newGemCard,
            player_id,
            document.getElementById(`goi_cargoExcedent:${player_id}`)
          );
        });
      }
    },

    notif_obtainIridiaStone: function (notif) {
      const player_id = notif.args.player_id;

      this.goi.stocks[player_id].gems.iridiaStone.addCard({
        id: "iridia",
        type: "iridia",
        type_arg: 0,
      });

      this.playSound("iridia", 5000);
    },

    notif_obtainRoyaltyToken: function (notif) {
      const player_id = notif.args.player_id;
      const tokenName = notif.args.tokenName;
      const token_id = notif.args.token_id;

      this.goi.stocks[player_id].royaltyTokens.victoryPile.addCard({
        id: token_id,
        type: tokenName,
        type_arg: token_id,
      });
    },

    notif_incCoin: function (notif) {
      const player_id = notif.args.player_id;
      const delta = notif.args.delta;

      this.goi.counters[player_id].coins.incValue(delta);

      const coins = this.goi.counters[player_id].coins.getValue();
      const positionLeft = this.calcCoinPosition(coins);

      this.goi.counters[player_id].coins.span.style.left = positionLeft;

      if (delta > 0) {
        this.playSound("coins", 2000);
      }
    },

    notif_incRoyaltyPoints: function (notif) {
      const player_id = notif.args.player_id;
      const points = notif.args.points;

      let score = 0;
      const bot = this.goi.bot;
      if (this.goi.globals.isSolo && player_id == bot.id) {
        const scoreElement = document.getElementById(`player_score_${bot.id}`);
        const prevPoints = Number(scoreElement.innerText);
        document.getElementById(`player_score_${bot.id}`).innerText =
          points + prevPoints;

        score = points + prevPoints;
      } else {
        this.scoreCtrl[player_id].incValue(points);
        score = this.scoreCtrl[player_id].getValue();
      }

      const player_color = `#${this.goi.globals.players[player_id].color}`;

      if (score >= 100) {
        const scoringMarker = {
          id: player_id,
          color: player_color,
          score: score - 100,
        };

        const completeScoringMarker = {
          id: `${player_id}-100`,
          color: player_color,
          score: 100,
        };

        if (score === 100) {
          this.goi.stocks.scoringMarkers.track.removeCard(scoringMarker);
        } else {
          this.goi.stocks.scoringMarkers.track.addCard(scoringMarker);
        }

        this.goi.stocks[player_id].scoringMarkers.hundred.addCard(
          completeScoringMarker
        );

        return;
      }

      const scoringMarker = {
        id: player_id,
        color: player_color,
        score,
      };

      this.goi.stocks.scoringMarkers.track.addCard(scoringMarker);
    },

    notif_obtainStoneDie: function (notif) {
      const player_id = notif.args.player_id;
      const die_id = notif.args.die_id;

      this.goi.stocks[player_id].dice.scene.addDie({
        id: die_id,
        type: "stone",
      });
    },

    notif_activateStoneDie: function (notif) {
      const player_id = notif.args.player_id;
      const die_id = notif.args.die_id;

      const die = {
        id: die_id,
        type: "stone",
        active: true,
        face: this.goi.globals.rolledDice[die_id]?.face,
      };

      this.goi.stocks[player_id].dice.scene.removeDie(die);
      this.goi.stocks[player_id].dice.scene.addDie(die);
    },

    notif_resetStoneDice: function (notif) {
      const player_id = notif.args.player_id;

      const activeDice = this.goi.stocks[player_id].dice.scene
        .getDice()
        .filter((die) => {
          return !!die.active;
        })
        .map((die) => {
          die.active = false;
          return die;
        });

      this.goi.stocks.dice.stone.addDice(activeDice);
      this.goi.stocks.dice.stone.removeDice(activeDice);
      this.goi.stocks.dice.stone.addDice(activeDice);
    },

    notif_rollDie: function (notif) {
      const player_id = notif.args.player_id;
      const die_id = notif.args.die_id;
      const face = notif.args.face;
      const type = notif.args.type;

      const die = {
        id: die_id,
        face: face,
        type: type,
      };

      if (type === "gem") {
        this.goi.stocks.dice.market
          .getDieElement(die)
          .classList.remove("goi_selectedDie");
        this.goi.stocks.dice.market.rollDie(die);
      } else {
        this.goi.stocks[player_id].dice.scene
          .getDieElement(die)
          .classList.remove("goi_selectedDie");
        this.goi.stocks[player_id].dice.scene.rollDie(die);
      }

      this.goi.globals.rolledDice[die_id] = die;

      this.playSound("dice");
    },

    notif_joltyJackhammer: function (notif) {
      const player_id = notif.args.player_id;
      const die_id = notif.args.die_id;
      const face = notif.args.face;
      const type = notif.args.type;

      const die = {
        id: die_id,
        face: face,
        type: type,
        color: this.goi.globals.players[player_id].color,
      };

      this.goi.stocks[player_id].dice.scene.removeDie(die);
      this.goi.stocks[player_id].dice.scene.addDie(die);

      if (type === "stone") {
        this.notif_activateStoneDie(notif);
      }
    },

    notif_dynamiteSFX: function (notif) {
      this.playSound("explosion");
    },

    notif_syncDieRolls: function (notif) {},

    notif_restoreRelic: function (notif) {
      const player_id = notif.args.player_id;
      const relicCard = notif.args.relicCard;

      this.goi.stocks[player_id].relics.victoryPile.addCard(relicCard);
    },

    notif_replaceRelic: function (notif) {
      const relicCard = notif.args.relicCard;
      const relicsDeckTop = notif.args.relicsDeckTop;
      const relicsDeckCount = notif.args.relicsDeckCount;

      this.goi.stocks.relics.market.addCard(relicCard, {
        fromStock: this.goi.stocks.relics.deck,
      });

      this.goi.stocks.relics.deck.removeCard({ id: "fake" });
      this.goi.stocks.relics.deck.setCardNumber(relicsDeckCount, relicsDeckTop);

      if (relicsDeckTop) {
        this.goi.stocks.relics.deck.setCardVisible(relicsDeckTop, false);
      }
    },

    notif_reshuffleItemsDeck: function (notif) {
      const itemsMarket = notif.args.itemsMarket;

      const previousMarket = this.goi.stocks.items.market.getCards();
      const discard = this.goi.stocks.items.discard.getCards();

      const reshuffledItems = previousMarket.concat(discard);

      this.goi.stocks.items.deck
        .addCards(reshuffledItems, {
          fromStock: this.goi.stocks.items.market,
        })
        .then(() => {
          return this.goi.stocks.items.deck.shuffle();
        })
        .then(() => {
          for (const itemCard_id in itemsMarket) {
            const itemCard = itemsMarket[itemCard_id];
            this.goi.stocks.items.market.addCard(itemCard, {
              fromStock: this.goi.stocks.items.deck,
            });
          }
        });
    },

    notif_buyItem: function (notif) {
      const player_id = notif.args.player_id;
      const itemCard = notif.args.itemCard;

      this.goi.stocks[player_id].items.hand.addCard(itemCard);
    },

    notif_replaceItem: function (notif) {
      const itemCard = notif.args.itemCard;

      this.goi.stocks.items.market.addCard(itemCard, {
        fromStock: this.goi.stocks.items.deck,
      });
    },

    notif_activateItem: function (notif) {
      const itemCard = notif.args.itemCard;
      this.goi.stocks.items.active.addCard(itemCard);
    },

    notif_cancelItem: function (notif) {
      const player_id = notif.args.player_id;
      const itemCard = notif.args.itemCard;

      this.goi.stocks[player_id].items.hand.addCard(itemCard);
    },

    notif_discardItem: function (notif) {
      const itemCard = notif.args.itemCard;
      this.goi.stocks.items.discard.addCard(itemCard);
    },

    notif_regalReferenceBook: function (notif) {
      const player_id = notif.args.player_id;
      const relicCard = notif.args.relicCard;
      const itemCard = notif.args.itemCard;
      const relicsDeckCount = notif.args.relicsDeckCount;
      const relicsDeckTop = notif.args.relicsDeckTop;

      this.goi.stocks[player_id].items.book.addCard(itemCard);
      this.goi.stocks[player_id].relics.book.addCard(relicCard, {
        fromStock:
          relicCard.location === "deck"
            ? this.goi.stocks.relics.deck
            : undefined,
      });

      this.goi.stocks.relics.deck.shuffle({ animatedCardsMax: 5 });

      this.goi.stocks.relics.deck.setCardNumber(relicsDeckCount, relicsDeckTop);

      if (relicsDeckTop) {
        this.goi.stocks.relics.deck.setCardVisible(relicsDeckTop, false);
      }
    },

    notif_swappingStones: function (notif) {
      const currentExplorerCard = notif.args.currentExplorerCard;
      const opponentExplorerCard = notif.args.opponentExplorerCard;
      const currentHex = notif.args.currentHex;
      const opponentHex = notif.args.opponentHex;

      this.goi.stocks.explorers.board.removeCard(currentExplorerCard);
      this.goi.stocks.explorers.board.removeCard(opponentExplorerCard);

      this.goi.stocks.explorers.board.addCard(
        currentExplorerCard,
        {
          fromElement: document.getElementById(
            `goi_tileContainer-${currentHex}`
          ),
        },
        {
          forceToElement: document.getElementById(
            `goi_tileContainer-${opponentHex}`
          ),
        }
      );

      this.goi.stocks.explorers.board.addCard(
        opponentExplorerCard,
        {
          fromElement: document.getElementById(
            `goi_tileContainer-${opponentHex}`
          ),
        },
        {
          forceToElement: document.getElementById(
            `goi_tileContainer-${currentHex}`
          ),
        }
      );

      this.playSound("swapping", 2500);
    },

    notif_cleverCatapult: function (notif) {
      const hex = notif.args.hex;
      const explorerCard = notif.args.explorerCard;

      this.goi.stocks.explorers.board.removeCard(explorerCard);
      this.goi.stocks.explorers.board.addCard(
        explorerCard,
        {
          fromElement: document.getElementById(
            `goi_tileContainer-${explorerCard.location_arg}`
          ),
        },
        {
          forceToElement: document.getElementById(`goi_tileContainer-${hex}`),
        }
      );
    },

    notif_collectTile: function (notif) {
      const player_id = notif.args.player_id;
      const tileCard = notif.args.tileCard;

      this.goi.stocks[player_id].tiles.victoryPile.addCard(tileCard);
    },

    notif_updateMarketValue: function (notif) {
      const marketValue = notif.args.marketValue;
      const gem_id = notif.args.gem_id;

      const die = this.goi.stocks.dice.market.getDice().find((die) => {
        return gem_id == die.id;
      });

      this.goi.stocks.dice.market.removeDie(die);

      die.face = marketValue;
      this.goi.stocks.dice.market.addDie(die);
    },

    notif_discardObjective: function (notif) {
      const objectiveCard = notif.args.objectiveCard;

      this.goi.stocks.objectives.void.addCard(objectiveCard);
    },

    notif_discardObjective_priv: function (notif) {
      const objectiveCard = notif.args.objectiveCard;

      this.goi.stocks.objectives.void.addCard(objectiveCard);
    },

    notif_revealObjective: function (notif) {
      const player_id = notif.args.player_id;
      const objectiveCard = notif.args.objectiveCard;

      this.goi.stocks[player_id].objectives.hand.flipCard(objectiveCard);
    },

    notif_completeObjective: function (notif) {
      const player_id = notif.args.player_id;
      const objectiveCard = notif.args.objectiveCard;
      const points = notif.args.points;

      const objectiveElement =
        this.goi.stocks[player_id].objectives.hand.getCardElement(
          objectiveCard
        );

      const player_color = this.goi.globals.players[player_id].color;

      this.displayScoring(objectiveElement.id, player_color, points);
    },

    notif_zombieQuit: function (notif) {
      const player_id = notif.args.player_id;
      const explorerCard = notif.args.explorerCard;

      const itemCards = this.goi.stocks[player_id].items.hand.getCards();
      this.goi.stocks.items.discard.addCards(itemCards);
      const bookCard = this.goi.stocks[player_id].items.book.getCards();
      this.goi.stocks.items.discard.addCards(bookCard);

      const stoneDice = this.goi.stocks[player_id].dice.scene
        .getDice()
        .filter((die) => {
          return die.type === "stone";
        });
      this.goi.stocks.dice.stone.addDice(stoneDice);

      this.goi.stocks.scoringMarkers.track.removeCard({ id: player_id });
      this.goi.stocks[player_id].explorers.scene.addCard(explorerCard);
      this.goi.stocks[player_id].explorers.scene.remove();
      this.goi.stocks[player_id].relics.victoryPile.remove();
      this.goi.stocks[player_id].relics.book.remove();
      this.goi.stocks[player_id].gems.cargo.remove();
      this.goi.stocks[player_id].royaltyTokens.victoryPile.remove();
      this.goi.stocks[player_id].objectives.hand.remove();
      this.goi.stocks[player_id] = undefined;

      this.goi.counters[player_id].coins.toValue(0);
      for (const counterKey in this.goi.counters[player_id].gems) {
        this.goi.counters[player_id].gems[counterKey].toValue(0);
      }

      document.getElementById(`goi_playerZoneContainer:${player_id}`).remove();
    },

    /* SOLO */

    notif_rhomDrawCard: function (notif) {
      const rhomCard = notif.args.rhomCard;
      const rhomDeckCount = notif.args.rhomDeckCount;
      const rhomDeckTop = notif.args.rhomDeckTop;

      this.goi.stocks.rhom.discard.addCard(rhomCard, {
        fromStock: this.goi.stocks.rhom.deck,
      });

      this.goi.stocks.rhom.deck.setCardNumber(rhomDeckCount, rhomDeckTop);
      this.goi.stocks.rhom.deck.setCardVisible(rhomDeckTop, false);
    },

    notif_reshuffleRhomDeck: function (notif) {
      const rhomDeckCount = notif.args.rhomDeckCount;
      const rhomDeckTop = notif.args.rhomDeckTop;

      const rhomDiscard = this.goi.stocks.rhom.discard.getCards();
      this.goi.stocks.rhom.deck.addCards(rhomDiscard);

      this.goi.stocks.rhom.deck.shuffle();
      this.goi.stocks.rhom.deck.setCardNumber(rhomDeckCount, rhomDeckTop);
      this.goi.stocks.rhom.deck.setCardVisible(rhomDeckTop, false);
    },

    notif_rhomBarricade: function (notif) {
      const tileCard = notif.args.tileCard;

      this.goi.stocks.tiles.board.removeCard(tileCard);
      this.goi.stocks.tiles.barricade.addCard(
        tileCard,
        {},
        {
          forceToElement: document.getElementById(
            `goi_tileContainer-${tileCard.location_arg}`
          ),
        }
      );
      this.goi.stocks.tiles.barricade.setCardVisible(
        tileCard,
        !!tileCard.type_arg
      );
    },

    /* LOGS MANIPULATION */

    createTileTooltip: function (tileCard) {
      const tile_id = Number(tileCard.type_arg);
      const region_id = Number(tileCard.type);
      const hex = Number(tileCard.location_arg);

      const background = `url(${g_gamethemeurl}/img/tooltips/tiles-${region_id}.png)`;

      const backgroundPosition = this.calcBackgroundPosition(
        tile_id - 13 * (region_id - 1) - 1
      );

      const hexText = this.format_string_recursive(_("Hex: ${log_hex}"), {
        log_hex: hex,
      });

      const hexElement =
        tileCard.location === "board"
          ? `<span style="font-size: 16px">${hexText}</span></span>`
          : "";

      const tooltip = `<div>
      <div class="goi_tooltip goi_tile" style="background-image: ${background}; background-position: ${backgroundPosition}"></div>
      ${hexElement}
      </div>`;

      return tooltip;
    },

    createRelicTooltip: function (relic_id) {
      const realCard = document
        .getElementById("goi_gameArea")
        .querySelector(`#relic-tooltip-${relic_id}`);

      const clone = realCard.cloneNode(true);
      clone.style.visibility = "visible";
      return clone.outerHTML;
    },

    createItemTooltip: function (item_id) {
      const realCard = document
        .getElementById("goi_gameArea")
        .querySelector(`#item-tooltip-${item_id}`);

      const clone = realCard.cloneNode(true);
      clone.style.visibility = "visible";
      return clone.outerHTML;
    },

    createObjectiveTooltip: function (objective_id) {
      const realCard = document
        .getElementById("goi_gameArea")
        .querySelector(`#objective-tooltip-${objective_id}`);

      const clone = realCard.cloneNode(true);
      clone.style.visibility = "visible";
      return clone.outerHTML;
    },

    createRhomTooltip: function (rhom_id) {
      const backgroundPosition = this.calcBackgroundPosition(rhom_id);
      return `<div class="goi_rhom goi_tooltip goi_card" style="background-position: ${backgroundPosition}"></div>`;
    },

    addCustomTooltip: function (container, html) {
      this.addTooltipHtml(container, html, 1000);
    },

    setLoader: function (image_progress, logs_progress) {
      this.inherited(arguments); // required, this is "super()" call, do not remove
      if (!this.isLoadingLogsComplete && logs_progress >= 100) {
        this.isLoadingLogsComplete = true; // this is to prevent from calling this more then once
        this.onLoadingLogsComplete();
      }
    },

    onLoadingLogsComplete: function () {
      console.log("Loading logs complete");

      this.attachRegisteredTooltips();
    },

    registerCustomTooltip: function (html, id) {
      this._registeredCustomTooltips[id] = html;
      return id;
    },

    attachRegisteredTooltips: function () {
      console.log("Attaching toolips");

      for (const id in this._registeredCustomTooltips) {
        let tooltip = this._registeredCustomTooltips[id];

        if (tooltip.match("goi_item")) {
          new dijit.Tooltip({
            connectId: [id],
            getContent: (matchedNode) => {
              const item_id = id.split("-")[1];
              return this.createItemTooltip(item_id);
            },
          });
        } else if (tooltip.match("goi_objective")) {
          new dijit.Tooltip({
            connectId: [id],
            getContent: (matchedNode) => {
              const objective_id = id.split("-")[1];
              return this.createObjectiveTooltip(objective_id);
            },
          });
        } else if (tooltip.match("goi_relic")) {
          new dijit.Tooltip({
            connectId: [id],
            getContent: (matchedNode) => {
              return this.createRelicTooltip(relic_id);
            },
          });
        } else {
          this.addCustomTooltip(id, tooltip);
          this._attachedTooltips[id] = tooltip;
        }
      }

      this._registeredCustomTooltips = {};
    },

    // @Override
    format_string_recursive: function (log, args) {
      try {
        if (log && args && !args.processed) {
          args.processed = true;

          if (this.goi.globals.isSolo) {
            const bot_id = this.goi.bot.id;
            const botColor = this.goi.bot.color;
            const botName = this.goi.bot.name;

            if (args.rhom) {
              args.rhom = `<span style="font-weight: bold; color: #${botColor}">${botName}</span>`;
            }

            if (args.player_name && args.player_id == bot_id) {
              args.player_name = `<!--PNS--><span class="playername" style="color: #${botColor}">${botName}</span><!--PNE-->`;
            }

            if (
              args.player_name2 &&
              args.player_name2.includes(`>${botName}</`)
            ) {
              args.player_name2 = `<!--PNS--><span class="playername" style="color: #${botColor}">${botName}</span><!--PNE-->`;
            }

            if (args.card && args.rhomCard) {
              const rhomCard = args.rhomCard;

              const rhom_id = Number(rhomCard.type_arg);
              const uid = `${Date.now()}${rhom_id}`;
              const elementId = `goi_rhomLog:${uid}`;

              args.card = `<span id="${elementId}" style="font-weight: bold;">${_(
                args.card
              )}</span>`;

              const tooltip = this.createRhomTooltip(rhom_id);
              this.registerCustomTooltip(tooltip, elementId);
            }
          }

          if (args.tile && args.tileCard) {
            const tileCard = args.tileCard;

            const tile_id = Number(tileCard.type_arg);
            const uid = `${Date.now()}${tile_id}`;
            const elementId = `goi_tileLog:${uid}`;

            args.tile = `<span id="${elementId}" style="font-weight: bold;">${_(
              args.tile
            )}</span>`;

            const tooltip = this.createTileTooltip(tileCard);
            this.registerCustomTooltip(tooltip, elementId);
          }

          if (args.log_hex) {
            args.log_hex = `<span style="font-weight: bold;">${args.log_hex}</span>`;
          }

          if (args.relicCard && args.relic_name) {
            const relicCard = args.relicCard;

            const relic_id = Number(relicCard.type_arg);
            const uid = `${Date.now()}${relic_id}`;
            const elementId = `goi_relicLog:${uid}`;

            args.relic_name = `<span id="${elementId}" style="font-weight: bold;">${_(
              args.relic_name
            )}</span>`;

            const tooltip = this.createRelicTooltip(relic_id);
            this.registerCustomTooltip(tooltip, elementId);
          }

          if (args.item_id && args.item_name) {
            const item_id = Number(args.item_id);
            const uid = `${Date.now()}-${item_id}`;
            const elementId = `goi_itemLog:${uid}`;

            args.item_name = `<span id="${elementId}" style="font-weight: bold;">${_(
              args.item_name
            )}</span>`;

            const tooltip = this.createItemTooltip(item_id);

            this.registerCustomTooltip(tooltip, elementId);
          }

          if (args.objectiveCard && args.objective_name) {
            const objectiveCard = args.objectiveCard;

            const objective_id = Number(objectiveCard.type_arg);
            const uid = `${Date.now()}-${objective_id}`;
            const elementId = `goi_objectiveLog:${uid}`;

            args.objective_name = `<span id="${elementId}" style="font-weight: bold;">${_(
              args.objective_name
            )}</span>`;

            const tooltip = this.createObjectiveTooltip(objective_id);
            this.registerCustomTooltip(tooltip, elementId);
          }
        }

        if (this.getGameUserPreference(101) == 1) {
          if (args.green_flag) {
            args.green_flag = `<span class="textalign"><span class="goi_greenFlag textalign_inner"></span></span>`;
          }

          if (args.gem_label) {
            const gem_id = args.gem_id;
            const backgroundPosition = this.calcBackgroundPosition(gem_id);
            args.gem_label = `<span class="goi_gemIcon goi_log" style="background-position: ${backgroundPosition};"></span>`;
          }

          if (args.coin) {
            const coins = Math.abs(args.delta_log);
            const positionLeft = this.calcCoinPosition(coins);

            args.coin = `<span class="goi_logMarker">
              <span class="goi_iconValue" style="left: ${positionLeft}">${coins}</span>
            </span>`;

            args.delta_log = "";
          }

          if (args.points_log) {
            const spritePosition = args.finalScoring ? 2 : 1;
            const backgroundPosition =
              this.calcBackgroundPosition(spritePosition);

            let positionLeft =
              args.points_log >= 10 && args.points_log !== 11 ? "19%" : "28%";

            if (args.points_log === 1) {
              positionLeft = "35%";
            }

            args.points_log = `<span class="goi_logMarker" style="background-position: ${backgroundPosition};">
              <span class="goi_iconValue goi_scoring" style="left: ${positionLeft};">${args.points_log}</span>
            </span>`;
          }
        }
      } catch (e) {
        console.error(log, args, "Exception thrown", e.stack);
      }

      return this.inherited(arguments);
    },
  });
});
