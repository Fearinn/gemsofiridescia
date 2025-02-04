<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * GemsOfIridescia implementation : © Matheus Gomes matheusgomesforwork@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gemsofiridescia.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */

declare(strict_types=1);

namespace Bga\Games\GemsOfIridescia;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

use \Bga\GameFramework\Actions\Types\IntParam;
use \Bga\GameFramework\Actions\Types\JsonParam;
use \Bga\GameFramework\Actions\CheckAction;

const ST_PICK_WELL_GEM = 40;
const ST_TRANSFER_GEM = 31;

const PLAYER_BOARDS = "playerBoards";
const REVEALS_LIMIT = "revealsLimit";
const HAS_MOVED_EXPLORER = "hasMovedExplorer";
const SOLD_GEM = "soldGem";
const HAS_MINED = "hasMined";
const REVEALED_TILES = "revealedTiles";
const RAINBOW_GEM = "activeGem";
const PLAYER_STONE_DICE = "playerStoneDice";
const ACTIVE_STONE_DICE = "activeStoneDice";
const ROLLED_DICE = "rolledDice";
const REROLLABLE_DICE = "rerollableDice";
const ANCHOR_STATE = "anchorState";
const HAS_EXPANDED_TILES = "hasExpandedTiles";
const CURRENT_TILE = "currentTile";
const HAS_BOUGHT_ITEM = "hasBoughtItem";
const ACTION_AFTER_SELL = "actionAfterSell";

const MARVELOUS_CART = "marvelousCart";
const EPIC_ELIXIR = "epicElixir";
const EPIC_ELIXIR_TURN = "epicElixirTurn";
const SWAPPING_STONES = "swappingStones";
const PROSPEROUS_PICKAXE = "prosperousPickaxe";
const WISHING_WELL = "wishingWell";

const REAL_TURN = "realTurn";
const RHOM_FIRST_TURN = "rhomFirstTurn";
const RHOM_MULTIPLIER = "rhomMultiplier";
const RHOM_STATS = "rhomStats";
const RHOM_POSSIBLE_RAINBOW = "rhomPossibleRainbow";
const RHOM_SKIP = "rhomSkip";

class Game extends \Table
{
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([
            "startingCatapult" => 100,
        ]);

        $this->tile_cards = $this->getNew("module.common.deck");
        $this->tile_cards->init("tile");

        $this->explorer_cards = $this->getNew("module.common.deck");
        $this->explorer_cards->init("explorer");

        $this->gem_cards = $this->getNew("module.common.deck");
        $this->gem_cards->init("gem");

        $this->relic_cards = $this->getNew("module.common.deck");
        $this->relic_cards->init("relic");

        $this->objective_cards = $this->getNew("module.common.deck");
        $this->objective_cards->init("objective");

        $this->item_cards = $this->getNew("module.common.deck");
        $this->item_cards->init("item");
        $this->item_cards->autoreshuffle = true;
        $this->item_cards->autoreshuffle_trigger = ["obj" => $this, "method" => "reshuffleItemsDeck"];

        $this->rhom_cards = $this->getNew("module.common.deck");
        $this->rhom_cards->init("rhom");
        $this->rhom_cards->autoreshuffle = true;
        $this->rhom_cards->autoreshuffle_trigger = ["obj" => $this, "method", "reshuffleRhomDeck"];

        $this->deckSelectQuery = "SELECT card_id id, card_type type, card_type_arg type_arg, 
        card_location location, card_location_arg location_arg ";

        // EXPERIMENTAL FLAG TO PREVENT DEADLOCKS
        $this->bSelectGlobalsForUpdate = true;
    }

    /**
     * Player action, example content.
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action defined into `gemsofiridescia.action.php`.
     *
     * @throws \BgaSystemException
     * @see action_gemsofiridescia::actMyAction
     */

    public function actRevealTile(?int $clientVersion, #[IntParam(min: 1, max: 58)] int $tileCard_id, bool $force = false, bool $skipTransition = false): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        if (!$force) {
            $revealableTiles = $this->revealableTiles($player_id, true);

            if (!array_key_exists($tileCard_id, $revealableTiles)) {
                throw new \BgaVisibleSystemException("You can't reveal this tile now: actRevealTile, $tileCard_id");
            }
        }

        $this->revealTile($tileCard_id, $player_id);

        if (!$skipTransition) {
            $this->gamestate->nextState("repeat");
        }
    }

    public function actConfirmAutoMove(?int $clientVersion): void
    {
        $tileCard_id = $this->globals->get(CURRENT_TILE);
        $this->globals->set(CURRENT_TILE, null);

        $revealedTiles = $this->globals->get(REVEALED_TILES);

        if (!array_key_exists($tileCard_id, $revealedTiles)) {
            $this->actRevealTile($clientVersion, $tileCard_id, false, true);
            $this->gamestate->nextState("moveExplorer");
            return;
        }

        $this->actMoveExplorer(null, $tileCard_id);
    }

    public function actSkipRevealTile(?int $clientVersion): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $explorableTiles = $this->explorableTiles($player_id);
        if (!$explorableTiles) {
            throw new \BgaVisibleSystemException("You must reveal a tile now: actSkipRevealTile");
        }

        $this->gamestate->nextState("skip");
    }

    public function actUndoSkipRevealTile(?int $clientVersion): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $revealsLimit = (int) $this->globals->get(REVEALS_LIMIT);
        $revealableTiles = $this->revealableTiles($player_id);

        if ($revealsLimit === 2 || !$revealableTiles) {
            throw new \BgaVisibleSystemException("You can't reveal other tile now: actUndoSkipRevealTile");
        }

        $this->gamestate->nextState("back");
    }

    public function actDiscardCollectedTile(?int $clientVersion, #[IntParam(min: 1, max: 58)] int $tileCard_id, #[IntParam(min: 1, max: 58)] int $emptyHex): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $emptyHexes = $this->emptyHexes($player_id);

        if (!in_array($emptyHex, $emptyHexes)) {
            throw new \BgaVisibleSystemException("You can't move your explorer to this empty tile now: actDiscardCollectedTile, $emptyHex");
        }

        $this->discardCollectedTile($tileCard_id, $player_id);
        $this->moveToEmptyHex($emptyHex, $player_id);

        $this->globals->set(HAS_EXPANDED_TILES, true);
        $this->gamestate->nextState("revealTile");
    }

    public function actDiscardTile(?int $clientVersion, #[IntParam(min: 1, max: 58)] int $tileCard_id): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $region_id = (int) $tileCard["type"];

        if ($region_id === 5) {
            throw new \BgaVisibleSystemException("You can't discard tiles from the Castle region");
        }

        $this->checkCardLocation($tileCard, "board");

        $this->tile_cards->moveCard($tileCard_id, "discard");

        $hex = (int) $tileCard["location_arg"];

        $this->notifyAllPlayers(
            "discardTile",
            clienttranslate('${player_name} discards a ${tile} from the board (hex ${log_hex})'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "log_hex" => $hex,
                "hex" => $hex,
                "tileCard" => $tileCard,
                "preserve" => ["tileCard"],
                "i18n" => ["tile"],
                "tile" => clienttranslate("tile"),
            ]
        );

        $this->gamestate->nextState("betweenTurns");
    }

    public function actMoveExplorer(?int $clientVersion, #[IntParam(min: 1, max: 58)] int $tileCard_id, bool $force = false): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        if (!$force) {
            $explorableTiles = $this->explorableTiles($player_id, true);

            if (!array_key_exists($tileCard_id, $explorableTiles)) {
                throw new \BgaVisibleSystemException("You can't move your explorer to this tile now: actMoveExplorer, $tileCard_id");
            }
        }

        $this->moveExplorer($tileCard_id, $player_id);
    }

    public function actPickRainbowGem(?int $clientVersion, #[IntParam(min: 1, max: 4)] int $gem_id): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $tileCard = $this->currentTile($player_id);

        $this->globals->set(RAINBOW_GEM, $gem_id);

        if (
            !$this->incGem(1, $gem_id, $player_id, $tileCard)
        ) {
            $anchorState_id = (int) $this->gamestate->state_id();
            $this->globals->set(ANCHOR_STATE, $anchorState_id);
            $this->gamestate->jumpToState(ST_TRANSFER_GEM);
            return;
        }

        $this->gamestate->nextState("optionalActions");
    }

    public function actDiscardObjective(?int $clientVersion, #[IntParam(min: 1, max: 15)] int $objectiveCard_id): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $objectiveCard = $this->objective_cards->getCard($objectiveCard_id);

        $this->checkCardLocation($objectiveCard, "hand", $player_id);

        $this->objective_cards->moveCard($objectiveCard_id, "discard");

        $objective_id = (int) $objectiveCard["type_arg"];
        $objective_info = $this->objectives_info[$objective_id];

        $this->notifyAllPlayers(
            "discardObjective",
            clienttranslate('${player_name} discards a Secret Objective'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "objectiveCard" => $this->hideCard($objectiveCard)
            ]
        );

        $this->notifyPlayer(
            $player_id,
            "discardObjective_priv",
            clienttranslate('You discard the ${objective_name} objective'),
            [
                "player_id" => $player_id,
                "objective_name" => $objective_info["tr_name"],
                "objectiveCard" => $objectiveCard,
                "i18n" => ["objectiveName"],
                "preserve" => ["objectiveCard"]
            ]
        );

        $tileCard = $this->currentTile($player_id);
        $this->resolveTileEffect($tileCard, $player_id);
    }

    public function actMine(?int $clientVersion, #[JsonParam(alphanum: true)] array $stoneDice): void
    {
        $this->actionAfterSell();

        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $this->globals->set(HAS_MINED, true);
        $this->globals->set(ROLLED_DICE, []);
        $this->globals->set(REROLLABLE_DICE, []);

        $playerDice = $this->globals->get(PLAYER_STONE_DICE)[$player_id];
        if (array_diff($stoneDice, $playerDice)) {
            throw new \BgaVisibleSystemException("You don't own all the selected Dice");
        }

        $stoneDiceCount = count($stoneDice);
        $activeStoneDice = $this->globals->get(ACTIVE_STONE_DICE);
        $activeStoneDiceCount = count($activeStoneDice);

        if ($stoneDiceCount > $activeStoneDiceCount) {
            $this->globals->set(ACTIVE_STONE_DICE, $stoneDice);
        }

        $playerDice = $this->globals->get(PLAYER_STONE_DICE)[$player_id];
        $playerDiceCount = count($playerDice);

        if ($stoneDiceCount > $stoneDiceCount) {
            throw new \BgaVisibleSystemException("Not enough Stone Dice: actMine, $stoneDiceCount, $playerDiceCount");
        }

        $this->decCoin(3, $player_id, true);

        $tileCard = $this->currentTile($player_id);
        $tile_id = (int) $tileCard["type_arg"];
        $gem_id = (int) $this->tiles_info[$tile_id]["gem"];

        if ($gem_id % 10 === 0) {
            $gem_id = $this->globals->get(RAINBOW_GEM);
        }

        $miningDice = [
            ["id" => "1-$player_id", "type" => "mining"],
            ["id" => "2-$player_id", "type" => "mining"],
        ];

        $stoneDice = array_map(
            function ($die_id) {
                return ["id" => $die_id, "type" => "stone"];
            },
            $stoneDice
        );

        $dice = array_merge($miningDice, $stoneDice);

        $minedGemsCount = $this->mine($gem_id, $dice, $player_id);

        $this->incStatWithRhom(1, "miningAttempts", $player_id);

        if ($minedGemsCount === 0) {
            $this->notifyAllPlayers(
                "failToMine",
                clienttranslate('${player_name} fails to mine his tile'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                ]
            );

            $this->incStatWithRhom(1, "failedMiningAttempts", $player_id);
        } else {
            if ($this->globals->get(MARVELOUS_CART)) {
                $minedGemsCount *= 2;
            }

            $fullCargo = false;
            if (!$this->incGem($minedGemsCount, $gem_id, $player_id, $tileCard, true)) {
                $fullCargo = true;
            };

            $tileCard = $this->globals->get(PROSPEROUS_PICKAXE);

            if ($tileCard) {
                $tile_id = (int) $tileCard["type_arg"];
                $gem_id = (int) $tileCard["gem"];

                if (!$this->incGem($minedGemsCount, $gem_id, $player_id, $tileCard, true)) {
                    $fullCargo = true;
                }
            }

            if ($fullCargo) {
                $anchorState_id = (int) $this->gamestate->state_id();
                $this->globals->set(ANCHOR_STATE, $anchorState_id);
                $this->gamestate->jumpToState(ST_TRANSFER_GEM);
                return;
            }
        }

        $this->gamestate->nextState("repeat");
    }

    public function actSellGems(?int $clientVersion, #[JsonParam(alphanum: false)] array $selectedGems): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $gem_id = null;
        $soldGem = (int) $this->globals->get(SOLD_GEM);

        if ($soldGem) {
            $gem_id = $soldGem;
        }

        $gemCards = [];
        foreach ($selectedGems as $gemCard) {
            $gemCard_id = $gemCard["id"];

            $gemCard = $this->gem_cards->getCard($gemCard_id);
            $gemCards[$gemCard_id] = $gemCard;

            if ($gem_id === null) {
                $gem_id = (int) $gemCard["type_arg"];
                continue;
            }

            if ($gem_id !== (int) $gemCard["type_arg"]) {
                throw new \BgaVisibleSystemException("You must sell gems of the same type: actSellGems, $gem_id");
            }
        }

        $delta = count($gemCards);

        $this->sellGems($delta, $gem_id, $gemCards, $player_id);
        $this->globals->set(SOLD_GEM, $gem_id);

        $this->gamestate->nextState("repeat");
    }

    public function actBuyItem(?int $clientVersion, #[IntParam(min: 1, max: 36)] int $itemCard_id)
    {
        $this->actionAfterSell();

        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $item = new ItemManager($itemCard_id, $this);

        $item->buy($player_id);
        $this->incStatWithRhom(1, "itemsPurchased", $player_id);

        $this->gamestate->nextState("repeat");
    }

    public function actUseItem(?int $clientVersion, #[IntParam(min: 1, max: 36)] int $itemCard_id, #[JsonParam(alphanum: false)] array $args): void
    {
        $this->actionAfterSell();

        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $item = new ItemManager($itemCard_id, $this);

        if (!$item->use($player_id, $args)) {
            $anchorState_id = (int) $this->gamestate->state_id();
            $this->globals->set(ANCHOR_STATE, $anchorState_id);
            $this->gamestate->jumpToState(ST_TRANSFER_GEM);
            return;
        };

        if ($item->id === 12) {
            $this->gamestate->nextState("pickWellGem");
            return;
        }

        $duringWell = (int) $this->gamestate->state_id() === 40;
        if ($duringWell) {
            return;
        }

        if ($item->id !== 11) {
            $this->gamestate->nextState("repeat");
        }
    }

    public function actUndoItem(?int $clientVersion, #[IntParam(min: 1, max: 36)] int $itemCard_id): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $item = new ItemManager($itemCard_id, $this);

        $item->undo($player_id);

        $this->gamestate->nextState("repeat");
    }

    #[CheckAction(false)]
    public function actUseEpicElixir(?int $clientVersion, #[IntParam(min: 1, max: 36)] int $itemCard_id): void
    {
        $this->checkVersion($clientVersion);
        $current_player_id = (int) $this->getCurrentPlayerId();
        $player_id = (int) $this->getActivePlayerId();

        if ($current_player_id !== $player_id) {
            throw new \BgaVisibleSystemException("You're not the active player: actUseEpicElixir");
        }

        $item = new ItemManager($itemCard_id, $this);
        $item->use($player_id, []);

        $state_id = $this->gamestate->state_id();
        $this->gamestate->jumpToState($state_id);
    }

    #[CheckAction(false)]
    public function actUndoEpicElixir(?int $clientVersion, #[IntParam(min: 1, max: 36)] int $itemCard_id): void
    {
        $this->checkVersion($clientVersion);
        $current_player_id = (int) $this->getActivePlayerId();
        $player_id = (int) $this->getActivePlayerId();

        if ($current_player_id !== $player_id) {
            throw new \BgaVisibleSystemException("You're not the active player: actUndoEpicElixir");
        }

        $item = new ItemManager($itemCard_id, $this);
        $item->undo($player_id);

        $state_id = $this->gamestate->state_id();
        $this->gamestate->jumpToState($state_id);
    }

    public function actPickWellGem(?int $clientVersion, #[IntParam(min: 1, max: 4)] int $gem_id)
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $registeredWell = $this->globals->get(WISHING_WELL);

        if ($registeredWell === null) {
            throw new \BgaVisibleSystemException("The Wishing Well was not used");
        }

        $itemCard_id = (int) $registeredWell["card_id"];
        $item = new ItemManager($itemCard_id, $this);

        if (!$item->wishingWell2($gem_id, $player_id)) {
            $anchorState_id = (int) $this->gamestate->state_id();
            $this->globals->set(ANCHOR_STATE, $anchorState_id);
            $this->gamestate->jumpToState(ST_TRANSFER_GEM);
            return;
        };

        $this->gamestate->nextState("optionalActions");
    }

    public function actTransferGem(?int $clientVersion, #[JsonParam(alphanum: false)] array $gemCards, ?int $opponent_id): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $excedentGems = $this->getTotalGemsCount($player_id) - 7;
        $transferredGemsCount = count($gemCards);

        if ($transferredGemsCount > $excedentGems) {
            throw new \BgaVisibleSystemException("You can't transfer more gems than your excedent");
        }

        $availableCargos = $this->availableCargos($player_id, $transferredGemsCount);

        if ($opponent_id) {
            $this->checkPlayer($opponent_id);

            if (!in_array($opponent_id, $availableCargos)) {
                throw new \BgaVisibleSystemException("You can't transfer that many gems to this player now: actTransferGem, $opponent_id, $transferredGemsCount");
            }
        }

        $gemCardsByType = [];
        foreach ($gemCards as $gemCard) {
            $gemCard_id = (int) $gemCard["id"];
            $gemCard = $this->gem_cards->getCard($gemCard_id);

            $this->checkCardLocation($gemCard, "hand", $player_id);

            $gem_id = (int) $gemCard["type_arg"];

            if (!array_key_exists($gem_id, $gemCardsByType)) {
                $gemCardsByType[$gem_id] = [];
            }

            $gemCardsByType[$gem_id][] = $gemCard;
        }

        foreach ($gemCardsByType as $gemTypeCards) {
            if (!$availableCargos) {
                $this->discardGems($player_id, $gemTypeCards, null);
            } else {
                $this->transferGems($gemTypeCards, $opponent_id, $player_id);
            }
        }

        $this->gamestate->nextState("repeat");
    }

    public function actSkipOptionalActions(?int $clientVersion): void
    {
        $this->checkVersion($clientVersion);
        $this->gamestate->nextState("skip");
    }

    public function actUndoSkipOptionalActions(?int $clientVersion): void
    {
        $this->checkVersion($clientVersion);
        $this->gamestate->nextState("back");
    }

    public function actRestoreRelic(?int $clientVersion, #[IntParam(min: 1, max: 24)] int $relicCard_id): void
    {
        $this->checkVersion($clientVersion);
        $player_id = (int) $this->getActivePlayerId();

        $restorableRelics = $this->restorableRelics($player_id, true);
        $relicCard = $this->relic_cards->getCard($relicCard_id);

        if (!array_key_exists($relicCard_id, $restorableRelics) && !$this->checkCardLocation($relicCard, "book", $player_id)) {
            throw new \BgaVisibleSystemException("You can't restore this Relic now: actRestoreRelic, $relicCard_id");
        }

        $this->restoreRelic($relicCard_id, $player_id);

        $this->gamestate->nextState("repeat");
    }

    public function actSkipRestoreRelic(?int $clientVersion): void
    {
        $this->checkVersion($clientVersion);
        $this->gamestate->nextState("skip");
    }

    /* SOLO ACTIONS */

    public function actStartSolo(): void
    {
        $this->gamestate->nextState("rhomFirstTurn");
    }

    public function actPickRainbowForRhom(?int $clientVersion, #[IntParam(min: 1, max: 4)] int $gem_id): void
    {
        $this->checkVersion($clientVersion);

        $pickableGems = $this->globals->get(RHOM_POSSIBLE_RAINBOW, []);

        if (!in_array($gem_id, $pickableGems)) {
            throw new \BgaVisibleSystemException("You can't pick this gem for the Rhom: $gem_id");
        }

        $this->globals->set(RAINBOW_GEM, $gem_id);
        $this->globals->set(RHOM_SKIP, 2);
        $this->globals->set(RHOM_POSSIBLE_RAINBOW, null);

        if ($this->globals->get(RHOM_FIRST_TURN, true)) {
            $this->stRhomFirstTurn();
            return;
        }

        $this->gamestate->nextState("rhomTurn");
    }

    public function actDiscardTileForRhom(?int $clientVersion, #[IntParam(min: 1, max: 58)] int $tileCard_id): void
    {
        $this->checkVersion($clientVersion);

        $this->discardCollectedTile($tileCard_id, 1);
        $this->rhomMoveToEmptyHex();

        $this->gamestate->nextState("rhomTurn");
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return string[]
     * @see ./states.inc.php
     */

    public function argConfirmAutoMove(): array
    {
        $player_id = (int) $this->getActivePlayerId();
        $usableItems = $this->usableItems($player_id);

        $tileCard_id = $this->globals->get(CURRENT_TILE);
        $revealedTiles = $this->globals->get(REVEALED_TILES);
        $mustReveal = !array_key_exists($tileCard_id, $revealedTiles);

        $catapultCard_id = $this->getUniqueValueFromDB("SELECT card_id FROM item WHERE card_location='hand' AND card_location_arg=$player_id AND card_type_arg=11 LIMIT 1");
        $catapultableTiles = ["empty" => [], "tiles" => []];
        if ($catapultCard_id) {
            $catapultableTiles = $this->catapultableTiles($player_id);
        }

        return [
            "usableItems" => $usableItems,
            "mustReveal" => $mustReveal,
            "catapultableTiles" => $catapultableTiles,
        ];
    }

    public function argRevealTile(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $revealableTiles = $this->revealableTiles($player_id);
        $revealsLimit = (int) $this->globals->get(REVEALS_LIMIT);
        $explorableTiles = $this->explorableTiles($player_id);

        $catapultCard_id = $this->getUniqueValueFromDB("SELECT card_id FROM item WHERE card_location='hand' AND card_location_arg=$player_id AND card_type_arg=11 LIMIT 1");
        $catapultableTiles = ["empty" => [], "tiles" => []];
        if ($catapultCard_id) {
            $catapultableTiles = $this->catapultableTiles($player_id);
        }

        $noRevealableTile = !$revealableTiles;

        $singleRevealableTile = (!$explorableTiles && count($revealableTiles) === 1);

        $hasReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle from player WHERE player_id=$player_id");
        $skippable = !!$explorableTiles;

        $usableItems = $this->usableItems($player_id);
        $cancellableItems = $this->cancellableItems($player_id);

        $mustDiscardCollectedTile = $revealsLimit < 2 && !$revealableTiles && !$explorableTiles;

        $auto = ($singleRevealableTile) && !$usableItems && !$cancellableItems;

        return [
            "auto" => $auto,
            "revealableTiles" => $revealableTiles,
            "mustDiscardCollectedTile" => $mustDiscardCollectedTile,
            "catapultableTiles" => $catapultableTiles,
            "revealsLimit" => $revealsLimit,
            "skippable" => $skippable,
            "hasReachedCastle" => $hasReachedCastle,
            "usableItems" => $usableItems,
            "cancellableItems" => $cancellableItems,
            "_no_notify" => $mustDiscardCollectedTile || $noRevealableTile || $auto
                || $revealsLimit === 2 || $hasReachedCastle,
        ];
    }

    public function stRevealTile(): void
    {
        $isSolo = $this->isSolo();
        $rhomFirstTurn = $isSolo && $this->globals->get(RHOM_FIRST_TURN, true);

        if ($rhomFirstTurn) {
            $this->gamestate->nextState("startSolo");
            return;
        }

        $args = $this->argRevealTile();

        if ($args["_no_notify"]) {
            if ($args["hasReachedCastle"]) {
                if ($isSolo) {
                    $rhomReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle FROM robot WHERE id=1");
                    if ($rhomReachedCastle) {
                        $this->gamestate->nextState("finalScoring");
                        return;
                    }
                }

                $this->gamestate->nextState("discardTile");
                return;
            }

            if ($args["mustDiscardCollectedTile"]) {
                $this->gamestate->nextState("discardCollectedTile");
                return;
            }

            if ($args["auto"]) {
                $revealableTiles = (array) $args["revealableTiles"];
                $tileCard = reset($revealableTiles);
                $tileCard_id = (int) $tileCard["id"];

                $this->globals->set(CURRENT_TILE, $tileCard_id);
                $this->gamestate->nextState("confirmAutoMove");
                return;
            }

            $this->gamestate->nextState("moveExplorer");
        }
    }

    public function argDiscardCollectedTile(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $collectedTiles = $this->getCollectedTiles($player_id);
        $usableItems = $this->usableItems($player_id);

        $catapultCard_id = $this->getUniqueValueFromDB("SELECT card_id FROM item WHERE card_location='hand' AND card_location_arg=$player_id AND card_type_arg=11 LIMIT 1");
        $catapultableTiles = ["empty" => [], "tiles" => []];
        if ($catapultCard_id) {
            $catapultableTiles = $this->catapultableTiles($player_id);
        }

        $emptyHexes = $this->emptyHexes($player_id);

        $singleCollectedTile = count($collectedTiles) === 1 ? reset($collectedTiles) : null;
        $auto = $singleCollectedTile && !$usableItems && count($emptyHexes) === 1;

        return [
            "auto" => $auto,
            "collectedTiles" => $collectedTiles,
            "singleCollectedTile" => $singleCollectedTile,
            "usableItems" => $usableItems,
            "catapultableTiles" => $catapultableTiles,
            "emptyHexes" => $emptyHexes,
            "_no_notify" => $auto
        ];
    }

    public function stDiscardCollectedTile(): void
    {
        $args = $this->argDiscardCollectedTile();

        if ($args["_no_notify"]) {
            $collectedTiles = $args["collectedTiles"];
            $emptyHexes = $args["emptyHexes"];

            $tileCard = reset($collectedTiles);
            $tileCard_id = (int) $tileCard["id"];

            $emptyHex = (int) reset($emptyHexes);

            $this->actDiscardCollectedTile(null, $tileCard_id, $emptyHex);
            return;
        }
    }

    public function argMoveExplorer(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $explorableTiles = $this->explorableTiles($player_id);
        $revealableTiles = $this->revealableTiles($player_id);

        $revealsLimit = $this->globals->get(REVEALS_LIMIT);

        $singleExplorableTile = count($explorableTiles) === 1 && ($revealsLimit === 2 || !$revealableTiles);

        return [
            "auto" => $singleExplorableTile,
            "explorableTiles" => $explorableTiles,
            "revealableTiles" => $revealableTiles,
            "revealsLimit" => $revealsLimit,
            "_no_notify" => !!$this->globals->get(HAS_MOVED_EXPLORER) || $singleExplorableTile
        ];
    }

    public function stMoveExplorer(): void
    {
        $args = $this->argMoveExplorer();

        if ($args["_no_notify"]) {
            if ($args["auto"]) {
                $explorableTiles = $args["explorableTiles"];
                $tileCard = reset($explorableTiles);
                $tileCard_id = (int) $tileCard["id"];

                $this->globals->set(CURRENT_TILE, $tileCard_id);
                $this->gamestate->nextState("confirmAutoMove");
                return;
            }

            $this->gamestate->nextState("optionalActions");
        }
    }

    public function argOptionalActions(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $canMine = $this->hasEnoughCoins(3, $player_id);
        $canSellGems = $this->getTotalGemsCount($player_id) > 0 && !$this->globals->get(SOLD_GEM);

        $soldGem = $this->globals->get(SOLD_GEM);
        $canSellMoreGems = false;

        if (!$canSellGems && $soldGem) {
            $gem_id = $soldGem;
            $gemCount = $this->getGemsCounts($player_id, true)[$gem_id];
            $hasPerformedOtherAction = $this->globals->get(ACTION_AFTER_SELL);

            $canSellMoreGems = $gem_id && $gemCount > 0 && !$hasPerformedOtherAction;
        }

        $buyableItems = $this->buyableItems($player_id);
        $canBuyItem = !!$buyableItems;

        $usableItems = $this->usableItems($player_id);
        $canUseItem = !!$usableItems;

        $activeDice = $this->globals->get(ACTIVE_STONE_DICE);
        $activeDiceCount = count($activeDice);
        $playerStoneDice = $this->globals->get(PLAYER_STONE_DICE)[$player_id];
        $activableDiceCount = count($playerStoneDice);

        $explorableTiles = $this->explorableTiles($player_id);
        $prosperousTiles = $this->prosperousTiles($player_id);

        $bookableRelics = $this->bookableRelics();

        return [
            "canMine" => $canMine,
            "activeStoneDiceCount" => $activeDiceCount,
            "activableStoneDiceCount" => $activableDiceCount,
            "explorableTiles" => $explorableTiles,
            "prosperousTiles" => $prosperousTiles,
            "canSellGems" => $canSellGems,
            "canSellMoreGems" => $canSellMoreGems,
            "soldGem" => $soldGem,
            "canBuyItem" => $canBuyItem,
            "buyableItems" => $buyableItems,
            "canUseItem" => $canUseItem,
            "usableItems" => $usableItems,
            "cancellableItems" => $this->cancellableItems($player_id),
            "rerollableDice" => $this->globals->get(REROLLABLE_DICE, []),
            "bookableRelics" => $bookableRelics,
            "_no_notify" => !$canMine && !$canSellGems && !$canSellMoreGems && !$canBuyItem && !$canUseItem,
        ];
    }

    public function stOptionalActions(): void
    {
        $args = $this->argOptionalActions();

        if ($args["_no_notify"]) {
            $this->gamestate->nextState("restoreRelic");
        }
    }

    public function argPickWellGem(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $marketValues = $this->getMarketValues(null);

        $registeredWell = $this->globals->get(WISHING_WELL);
        $itemCard_id = (int) $registeredWell["card_id"];
        $maxValue = (int) $registeredWell["max"];
        $pickableGems = [];

        foreach ($marketValues as $gemName => $marketValue) {
            if ($marketValue <= $maxValue) {
                $pickableGems[$gemName] = $marketValue;
            }
        }

        $usableItems = $this->usableItems($player_id);
        $rerollableDice = $this->globals->get(REROLLABLE_DICE, []);
        $auto = !$usableItems && count($pickableGems) === 1;

        $singlePickableGem = null;
        if ($auto) {
            $k_pickableGems = array_keys($pickableGems);
            $gemName = reset($k_pickableGems);
            $singlePickableGem = $this->gemsIds_info[$gemName];
        }

        return [
            "itemCard_id" => $itemCard_id,
            "pickableGems" => $pickableGems,
            "singlePickableGem" => $singlePickableGem,
            "failed" => !$pickableGems,
            "usableItems" => $usableItems,
            "rerollableDice" => $rerollableDice,
            "auto" => $auto,
            "no_notify" => $auto || !$pickableGems,
        ];
    }

    public function stPickWellGem(): void
    {
        $player_id = (int) $this->getActivePlayerId();
        $args = $this->argPickWellGem();

        if ($args["no_notify"]) {
            if ($args["auto"]) {
                $gem_id = (int) $args["singlePickableGem"];
                $this->actPickWellGem(null, $gem_id);
                return;
            }

            if ($args["failed"]) {
                $this->notifyAllPlayers(
                    "failedWell",
                    clienttranslate('The ${item_name} of ${player_name} fails'),
                    [
                        "player_name" => $this->getPlayerNameById($player_id),
                        "item_name" => clienttranslate("Wishing Well"),
                        "i18n" => ["item_name"],
                        "preserve" => ["item_id"],
                        "item_id" => 12,
                    ]
                );

                $itemCard_id = $args["itemCard_id"];
                $item = new ItemManager($itemCard_id, $this);
                $item->disable();
                $item->discard();

                $this->gamestate->nextState("fail");
            }
        }
    }

    public function argTransferGem(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $excedentGems = $this->getTotalGemsCount($player_id) - 7;
        $availableCargos = $this->availableCargos($player_id, 1);

        return [
            "excedentGems" => $excedentGems,
            "availableCargos" => $availableCargos,
            "_no_notify" => $excedentGems <= 0,
        ];
    }

    public function stTransferGem(): void
    {
        $args = $this->argTransferGem();

        if ($args["_no_notify"]) {
            $anchorState_id = (int) $this->globals->get(ANCHOR_STATE);

            if ($anchorState_id !== 2) {
                $anchorState_id = 4;
            }

            $this->gamestate->jumpToState($anchorState_id);
            return;
        }

        $player_id = (int) $this->getActivePlayerId();

        $availableCargos = $args["availableCargos"];
        $gemsCounts = $this->getGemsCounts($player_id);

        $typesOfGems = 0;
        foreach ($gemsCounts as $gemName => $gemCount) {
            if ($gemCount > 0) {
                $typesOfGems++;
            }
        }

        if ($typesOfGems === 1) {
            $gemCard = $this->getObjectFromDB("$this->deckSelectQuery FROM gem 
            WHERE card_location='hand' AND card_location_arg=$player_id LIMIT 1");

            if (!$availableCargos) {
                $this->discardGems($player_id, [$gemCard], null);
                $this->gamestate->nextState("repeat");
                return;
            }

            if (count($availableCargos) === 1) {
                $opponent_id = reset($availableCargos);

                $this->transferGems([$gemCard], $opponent_id, $player_id);
                $this->gamestate->nextState("repeat");
            }
        }
    }

    public function argRestoreRelic(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $restorableRelics = $this->restorableRelics($player_id);
        $canRestoreBook = $this->canRestoreBook($player_id);

        return [
            "restorableRelics" => $restorableRelics,
            "canRestoreBook" => $canRestoreBook,
            "_no_notify" => !$restorableRelics && !$canRestoreBook,
        ];
    }

    public function stRestoreRelic(): void
    {
        $args = $this->argRestoreRelic();

        if ($args["_no_notify"]) {
            $this->gamestate->nextState("betweenTurns");
        }
    }

    public function stBetweenTurns(): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $this->resetStoneDice($player_id);
        $this->collectTile($player_id);
        $this->resetExplorer($player_id);

        $this->globals->set(REVEALS_LIMIT, 0);
        $this->globals->set(HAS_MOVED_EXPLORER, false);
        $this->globals->set(HAS_MINED, false);
        $this->globals->set(HAS_EXPANDED_TILES, false);
        $this->globals->set(HAS_BOUGHT_ITEM, false);
        $this->globals->set(ACTION_AFTER_SELL, false);
        $this->globals->set(SOLD_GEM, null);
        $this->globals->set(ACTIVE_STONE_DICE, []);
        $this->globals->set(ROLLED_DICE, []);
        $this->globals->set(REROLLABLE_DICE, []);
        $this->globals->set(RAINBOW_GEM, null);
        $this->globals->set(ANCHOR_STATE, null);

        $castlePlayersCount = $this->castlePlayersCount();
        $playersNumber = $this->getPlayersNumberNoZombie();

        if ($castlePlayersCount === $playersNumber) {
            $this->gamestate->nextState("finalScoring");
            return;
        }

        $this->giveExtraTime($player_id);

        $epicElixir = $this->globals->get(EPIC_ELIXIR, false);
        $this->disableItems();

        $isSolo = $this->isSolo();
        $isRealTurn = $this->globals->get(REAL_TURN);

        if ($epicElixir) {
            $this->globals->set(EPIC_ELIXIR_TURN, true);

            $this->notifyAllPlayers(
                "epicElixir",
                clienttranslate('${player_name} starts a new turn (${item_name})'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "item_name" => clienttranslate("Epic Elixir"),
                    "i18n" => ["item_name"],
                    "preserve" => ["item_id"],
                    "item_id" => 4,
                ]
            );
        } else {
            $this->globals->set(EPIC_ELIXIR_TURN, false);

            $this->notifyAllPlayers(
                "passTurn",
                clienttranslate('${player_name} ends ${his} turn'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "his" => $this->pronoun($player_id),
                    "i18n" => ["his"],
                ]
            );

            if ($isSolo && $isRealTurn) {
                $this->globals->set(REAL_TURN, false);
                $this->gamestate->nextState("rhomTurn");
                return;
            }

            $this->activeNextPlayer();
        }

        $this->gamestate->nextState("nextTurn");
    }

    public function stFinalScoring(): void
    {
        $this->calcFinalScoring();
        $this->gamestate->nextState("gameEnd");
    }

    /* SOLO MODE */

    public function stRhomFirstTurn(): void
    {
        $rhomSkip = $this->globals->get(RHOM_SKIP, 0);

        if ($rhomSkip === 0) {
            $hex_1 = $this->rollDie("1-1", 1, "mining");
            $hex_2 = $this->rollDie("2-1", 1, "mining");

            $weathervaneDirection = $this->weathervaneDirection();

            if ($weathervaneDirection === "right") {
                if ($hex_1 === 1 || $hex_1 === 6) {
                    $hex_1 = 2;
                }

                if ($hex_2 === 1 || $hex_2 === 6) {
                    $hex_2 = 2;
                }
            }

            if ($weathervaneDirection === "left") {
                if ($hex_1 === 1 || $hex_1 === 6) {
                    $hex_1 = 5;
                }

                if ($hex_2 === 1 || $hex_2 === 6) {
                    $hex_2 = 5;
                }
            }

            if ($hex_1 === $hex_2) {
                if ($weathervaneDirection === "right") {
                    $hex_2++;

                    if ($hex_2 === 6) {
                        $hex_2 = 2;
                    }
                }

                if ($weathervaneDirection === "left") {
                    $hex_2--;

                    if ($hex_2 === 1) {
                        $hex_2 = 5;
                    }
                }
            }

            $this->notifyAllPlayers(
                "syncDieRolls",
                "",
                []
            );

            $tileCard_id = (int) $this->getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location='board' AND card_location_arg=$hex_1");
            $this->revealTile($tileCard_id, 1);

            $tileCard_id = (int) $this->getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location='board' AND card_location_arg=$hex_2");
            $this->revealTile($tileCard_id, 1);
        }

        $revealedTiles = $this->globals->get(REVEALED_TILES);
        $mostDemandingTiles = $this->mostDemandingTiles($revealedTiles);

        $tileCard = reset($mostDemandingTiles);
        $tileCard_id = (int) $tileCard["id"];

        if ($rhomSkip <= 1) {
            $this->moveExplorer($tileCard_id, 1);
        }

        if ($this->globals->get(RHOM_POSSIBLE_RAINBOW)) {
            return;
        }

        $this->collectTile(1);

        $this->globals->set(RHOM_FIRST_TURN, false);
        $this->globals->set(RHOM_SKIP, 0);
        $this->globals->set(REAL_TURN, true);
        $this->gamestate->nextState("realTurn");
    }

    public function stRhomTurn(): void
    {
        $hasReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle FROM robot WHERE id=1");
        if ($hasReachedCastle) {
            $this->rhomBarricade();
            $this->globals->set(REAL_TURN, true);
            $this->gamestate->nextState("realTurn");
            return;
        }

        $rhomSkip = $this->globals->get(RHOM_SKIP, 0);

        if ($rhomSkip === 0) {
            if (!$this->rhomRevealTiles()) {
                return;
            }
        }

        if ($rhomSkip <= 1) {
            $explorableTiles = $this->explorableTiles(1);

            if (!$explorableTiles) {
                $this->gamestate->nextState("discardTileForRhom");
                return;
            }

            $mostDemandingTiles = $this->mostDemandingTiles($explorableTiles);

            $tileCard = reset($mostDemandingTiles);
            $tileCard_id = (int) $tileCard["id"];

            $this->moveExplorer($tileCard_id, 1);
        }

        if ($this->globals->get(RHOM_POSSIBLE_RAINBOW)) {
            return;
        }

        $this->rhomRestoreRelic();
        $this->collectTile(1);
        $this->resetExplorer(1);

        $this->globals->set(RHOM_SKIP, 0);
        $this->globals->set(REAL_TURN, true);
        $this->globals->set(RHOM_MULTIPLIER, 1);
        $this->gamestate->nextState("realTurn");
    }

    public function argRhomTurn(): array
    {
        return [
            "rhom" => "Rhom"
        ];
    }

    public function argPickRainbowForRhom(): array
    {
        $pickableGems = $this->globals->get(RHOM_POSSIBLE_RAINBOW);
        asort($pickableGems);

        return [
            "rhom" => "Rhom",
            "pickableGems" => $pickableGems,
        ];
    }

    public function argDiscardTileForRhom(): array
    {
        $collectedTiles = $this->getCollectedTiles(1);
        $auto = count($collectedTiles) === 1;

        return [
            "rhom" => "Rhom",
            "collectedTiles" => $collectedTiles,
            "auto" => $auto,
            "no_notify" => $auto,
        ];
    }

    public function stDiscardTileForRhom(): void
    {
        $args = $this->argDiscardTileForRhom();

        if ($args["no_notify"]) {
            if ($args["auto"]) {
                $collectedTiles = $args["collectedTiles"];

                $tileCard = reset($collectedTiles);
                $tileCard_id = (int) $tileCard["id"];

                $this->actDiscardTileForRhom(null, $tileCard_id);
                return;
            }
        }
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression()
    {
        $players = $this->loadPlayersNoZombie();

        $progression = 0;

        foreach ($players as $player_id => $player) {
            $explorerCard = $this->getExplorerByPlayerId($player_id);

            if ($explorerCard["location"] === "scene") {
                $hasReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle from player WHERE player_id=$player_id");

                if ($hasReachedCastle) {
                    $progression += 1;
                }

                continue;
            }

            $hex = (int) $explorerCard["location_arg"];
            $tileRow = $this->hexes_info[$hex]["row"];

            $progression += $tileRow / 9;
        }

        return round($progression / count($players) * 100);
    }

    /*   Utility functions */

    public function checkVersion(?int $clientVersion): void
    {
        if ($clientVersion === null) {
            return;
        }

        $serverVersion = (int) $this->gamestate->table_globals[300];
        if ($clientVersion != $serverVersion) {
            throw new \BgaVisibleSystemException($this->_("A new version of this game is now available. Please reload the page (F5)."));
        }
    }

    public function isSolo(): bool
    {
        return $this->getPlayersNumber() === 1;
    }

    public function isZombie(int $player_id): bool
    {
        return !!$this->getUniqueValueFromDB("SELECT player_zombie from player WHERE player_id=$player_id");
    }

    public function loadPlayersNoZombie(): array
    {
        $players = $this->getCollectionFromDB("SELECT player_id id, player_name name, player_color color, player_score score FROM player WHERE player_zombie=0");

        if ($this->isSolo()) {
            $players[1] = $this->getObjectFromDB("SELECT * FROM robot WHERE id=1");
        }

        return $players;
    }

    public function getPlayersNumberNoZombie(): int
    {
        if ($this->isSolo()) {
            return 2;
        }

        $playersNoZombie = $this->loadPlayersNoZombie();
        return count($playersNoZombie);
    }

    public function getPlayerOrRhomNameById(int $player_id): string
    {
        if ($player_id === 1) {
            return "Rhom";
        }

        return $this->getPlayerNameById($player_id);
    }

    public function checkPlayer($player_id): void
    {
        if ($this->isSolo() && $player_id === 1) {
            return;
        }

        $players = $this->loadPlayersNoZombie();

        if ($player_id && !array_key_exists($player_id, $players)) {
            throw new \BgaVisibleSystemException("This player is not in the table, $player_id");
        }
    }

    public function rollDie(int | string $die_id, int $player_id, string $type, bool $rerollable = true): int
    {
        $face = bga_rand(1, 6);

        $this->incStatWithRhom(1, "$face:Rolled", $player_id);

        $this->notifyAllPlayers(
            'rollDie',
            clienttranslate('${player_name} rolls a ${face} with a ${type_label} die'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "die_id" => $die_id,
                "face" => $face,
                "type" => $type,
                "type_label" => $this->dice_info[$type],
                "i18n" => ["type_label"]
            ]
        );

        $die = ["id" => $die_id, "type" => $type, "face" => $face];

        $this->updateRolledDice($die);
        $this->updateRerollableDice($die, !$rerollable);

        return $face;
    }

    public function updateRolledDice(array $die)
    {
        $die_id = $die["id"];

        $rolledDice = $this->globals->get(ROLLED_DICE, []);
        $rolledDice[$die_id] = $die;

        $this->globals->set(ROLLED_DICE, $rolledDice);
    }

    public function updateRerollableDice(array $die, bool $remove = false)
    {
        $die_id = $die["id"];

        $rerollableDice = $this->globals->get(REROLLABLE_DICE, []);
        $rerollableDice[$die_id] = $die;

        if ($remove) {
            unset($rerollableDice[$die_id]);
        }

        $this->globals->set(REROLLABLE_DICE, $rerollableDice);
    }

    public function checkCardLocation(array $card, string | int $location, int $location_arg = null): bool
    {
        if ($card["location"] != $location || ($location_arg && $card["location_arg"] != $location_arg)) {
            throw new \BgaVisibleSystemException("Unexpected card location: $location, $location_arg");
        }

        return true;
    }

    public function hideCard(array $card, bool $hideOrder = false, string | int $fakeId = null): array
    {
        if ($fakeId) {
            $card["id"] = $fakeId;
        }

        if ($hideOrder) {
            $card["location_arg"] = null;
        }

        $card["type_arg"] = null;
        return $card;
    }

    public function hideCards(array $cards, bool $hideOrder = false, bool $fakeIds = false): array
    {
        $hiddenCards = [];
        $fakeId = -count($cards);
        foreach ($cards as $card_id => $card) {
            $fakeId = $fakeIds ? $fakeId : null;
            $hiddenCards[$card_id] = $this->hideCard($card, $hideOrder, $fakeId);

            $fakeId++;
        }

        return $hiddenCards;
    }

    public function getExplorers(): array
    {
        $explorerCards = $this->getCollectionFromDB("$this->deckSelectQuery FROM explorer WHERE card_location!='box'");

        return $explorerCards;
    }

    public function getExplorerByPlayerId(int $player_id): array
    {
        $queryResult = $this->getCollectionFromDB("$this->deckSelectQuery FROM explorer WHERE card_type_arg=$player_id AND card_location<>'box'");
        $explorerCard = reset($queryResult);

        return $explorerCard;
    }

    public function resetExplorer(int $player_id): void
    {
        if ($player_id === 1) {
            $hasReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle from robot WHERE id=1");
        } else {
            $hasReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle from player WHERE player_id=$player_id");
        }

        if ($hasReachedCastle) {
            $explorerCard = $this->getExplorerByPlayerId($player_id);
            $explorerCard_id = (int) $explorerCard["id"];
            $this->explorer_cards->moveCard($explorerCard_id, "scene");

            $this->notifyAllPlayers(
                "resetExplorer",
                "",
                [
                    "player_id" => $player_id,
                    "explorerCard" => $explorerCard,
                ]
            );
        }
    }

    public function getTilesBoard(): array
    {
        $tilesBoard = $this->getCollectionFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board'");

        return $this->hideCards($tilesBoard);
    }

    public function adjacentTiles(int $player_id, int $hex = null, bool $onlyHexes = false, bool $onlyUnoccupied = true, bool $withDirections = false): array
    {
        $adjacentTiles = [];

        if (!$hex) {
            $explorerCard = $this->getExplorerByPlayerId($player_id);

            if ($explorerCard["location"] === "scene") {
                if ($onlyHexes) {
                    if ($this->getPlayersNumber() === 2) {
                        return [2, 3, 4, 5];
                    }

                    return [1, 2, 3, 4, 5, 6];
                }

                $queryResult = $this->getCollectionFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board' AND card_location_arg<=6");
                return $queryResult;
            }

            $hex = (int) $explorerCard["location_arg"];
        }

        $tileRow = $this->hexes_info[$hex]["row"];

        $leftHex = $hex - 1;
        $rightHex = $hex + 1;
        $topLeftHex = $hex + 6;
        $topRightHex = $hex + 7;

        $leftEdges = [1, 7, 14, 20, 27, 33, 40, 46, 53];
        $rightEdges = [6, 13, 19, 26, 32, 39, 45, 52, 58];

        $shiftEdges = $this->getPlayersNumber() === 2 && !$onlyHexes;
        if ($shiftEdges) {
            $leftEdges = [2, 8, 15, 21, 28, 34, 41, 47, 53];
            $rightEdges = [5, 12, 18, 25, 31, 38, 44, 51, 58];
        }

        $excludeTop = $tileRow % 2 === 0 && ($tileRow < 8 || !$shiftEdges);

        if (in_array($hex, $leftEdges)) {
            $leftHex = null;

            if ($excludeTop) {
                $topLeftHex = null;
            }
        };

        if (in_array($hex, $rightEdges)) {
            $rightHex = null;

            if ($excludeTop) {
                $topRightHex = null;
            }
        }

        $adjacentHexes = [
            "left" => $leftHex,
            "topLeft" => $topLeftHex,
            "topRight" => $topRightHex,
            "right" => $rightHex,
        ];

        if ($onlyHexes) {
            $hexes = [];
            foreach ($adjacentHexes as $hex) {
                if ($hex === null || !in_array($hex, range(1, 58))) {
                    continue;
                }

                if (!$onlyUnoccupied) {
                    $hexes[] = $hex;
                    continue;
                }

                $isOcuppied = !!$this->getUniqueValueFromDB("SELECT card_id FROM explorer WHERE card_location='board' AND card_location_arg=$hex");
                $isBarricaded = !!$this->getUniqueValueFromDB("SELECT card_id FROM tile WHERE card_location='barricade' AND card_location_arg=$hex");

                if (!$isOcuppied && !$isBarricaded) {
                    $hexes[] = $hex;
                }
            }
            return $hexes;
        }

        foreach ($adjacentHexes as $direction => $hex) {
            if ($hex === null) {
                continue;
            }

            $tileCard = $this->getObjectFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board' AND card_location_arg=$hex");

            if ($tileCard) {
                $tileCard_id = (int) $tileCard["id"];

                if ($withDirections) {
                    $adjacentTiles[$direction] = $tileCard;
                    continue;
                }

                $adjacentTiles[$tileCard_id] = $tileCard;
            }
        }

        return $adjacentTiles;
    }

    public function prosperousTiles(int $player_id, bool $associative = false): array
    {
        $explorableTiles = $this->explorableTiles($player_id, $associative);
        $explorerCard = $this->getExplorerByPlayerId($player_id);

        $hex = (int) $explorerCard["location_arg"];
        $leftBack = $hex - 7;
        $rightBack = $hex - 6;
        $hexesBehind = [
            $leftBack,
            $rightBack,
        ];

        $revealedTiles = $this->globals->get(REVEALED_TILES);
        $tilesBehind = [];
        foreach ($hexesBehind as $hex) {
            $tileCard = $this->getObjectFromDB("$this->deckSelectQuery from tile WHERE card_location='board' AND card_location_arg=$hex");

            if ($tileCard) {
                $tileCard_id = (int) $tileCard["id"];

                if (array_key_exists($tileCard_id, $revealedTiles)) {
                    if ($associative) {
                        $tilesBehind[$tileCard_id] = $tileCard;
                        continue;
                    }

                    $tilesBehind[] = $tileCard;
                }
            }
        }

        $prosperousTiles = [];
        if ($associative) {
            $prosperousTiles = $explorableTiles + $tilesBehind;
        } else {
            $prosperousTiles = array_merge($explorableTiles, $tilesBehind);
        }

        return $prosperousTiles;
    }

    public function revealableTiles(int $player_id, bool $associative = false): array
    {
        $revealableTiles = [];

        $adjacentTiles =  $this->adjacentTiles($player_id);
        $revealedTiles = $this->globals->get(REVEALED_TILES, []);

        foreach ($adjacentTiles as $tileCard_id => $tileCard) {
            if (!array_key_exists($tileCard_id, $revealedTiles)) {
                if ($associative) {
                    $revealableTiles[$tileCard_id] = $tileCard;
                    continue;
                }

                $revealableTiles[] = $tileCard;
            }
        }

        return $this->hideCards($revealableTiles);
    }

    public function explorableTiles(int $player_id, bool $associative = false): array
    {
        $explorableTiles = [];

        $adjacentTiles = $this->adjacentTiles($player_id);
        $revealedTiles = $this->globals->get(REVEALED_TILES, []);

        foreach ($adjacentTiles as $tileCard_id => $tileCard) {
            if (array_key_exists($tileCard_id, $revealedTiles)) {
                if ($associative) {
                    $explorableTiles[$tileCard_id] = $tileCard;
                    continue;
                }

                $explorableTiles[] = $tileCard;
            }
        }

        return $explorableTiles;
    }

    public function discardCollectedTile(int $tileCard_id, int $player_id): void
    {
        $tileCard = $this->tile_cards->getCard($tileCard_id);
        $this->checkCardLocation($tileCard, "hand", $player_id);
        $this->tile_cards->moveCard($tileCard_id, "discard");

        $this->notifyAllPlayers(
            "discardCollectedTile",
            clienttranslate('${player_name} discards a collected ${tile} to unblock ${his} moves'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "his" => $this->pronoun($player_id),
                "i18n" => ["his, tile"],
                "preserve" => ["tileCard"],
                "tileCard" => $tileCard,
                "tile" => clienttranslate("tile"),
            ]
        );
    }

    public function closestEmpty(int $player_id, array $adjacentHexes): array
    {
        $emptyWithAdjacent = [];

        if (!$adjacentHexes) {
            return [];
        }

        foreach ($adjacentHexes as $hex) {
            $adjacentTiles = $this->adjacentTiles($player_id, $hex);

            if ($adjacentTiles) {
                $emptyWithAdjacent[] = $hex;
            }
        }

        if (!$emptyWithAdjacent) {
            $newHexes = [];
            foreach ($adjacentHexes as $hex) {
                $newHexes = array_merge($newHexes, $this->adjacentTiles($player_id, $hex, true));
            }

            $newHexes = array_unique($newHexes);
            return $this->closestEmpty($player_id, $newHexes);
        }

        return $emptyWithAdjacent;
    }

    public function emptyHexes(int $player_id): array
    {
        $adjacentHexes = $this->adjacentTiles($player_id, null, true);
        return $this->closestEmpty($player_id, $adjacentHexes);
    }

    public function moveToEmptyHex(int $emptyHex, int $player_id)
    {
        $explorerCard = $this->getExplorerByPlayerId($player_id);
        $explorerCard_id = (int) $explorerCard["id"];
        $this->explorer_cards->moveCard($explorerCard_id, "board", $emptyHex);

        $this->notifyAllPlayers(
            "moveExplorer",
            clienttranslate('${player_name} moves ${his} explorer onto an empty tile (hex ${log_hex})'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "his" => $this->pronoun($player_id),
                "log_hex" => $emptyHex,
                "hex" => $emptyHex,
                "explorerCard" => $explorerCard,
                "i18n" => ["his"],
            ]
        );
    }

    public function expandedCatapultableHexes(int $player_id, array $adjacentHexes): array
    {
        $expandedCatapultableHexes = [];

        foreach ($adjacentHexes as $hex) {
            $expandedHexes = $this->adjacentTiles($player_id, $hex, true);
            $expandedCatapultableHexes = array_merge($expandedCatapultableHexes, $expandedHexes);
        }

        return $expandedCatapultableHexes;
    }

    public function catapultableTiles(int $player_id, bool $associative = false): array
    {
        $catapultableTiles = [];
        $catapultableEmpty = [];

        $adjacentHexes = $this->adjacentTiles($player_id, null, true, false);
        $expandedCatapultableHexes = $this->expandedCatapultableHexes($player_id, $adjacentHexes, true);

        foreach ($expandedCatapultableHexes as $hex) {
            $tileCard = $this->getObjectFromDB("$this->deckSelectQuery from tile 
            WHERE card_location='board' AND card_location_arg=$hex");

            if ($tileCard) {
                $tileCard_id = (int) $tileCard["id"];

                $revealableTiles = $this->revealableTiles($player_id, true);
                $explorableTiles = $this->explorableTiles($player_id, true);

                if (array_key_exists($tileCard_id, $revealableTiles) || array_key_exists($tileCard_id, $explorableTiles)) {
                    continue;
                }

                if ($associative) {
                    $catapultableTiles[$tileCard_id] = $tileCard;
                    continue;
                }

                $catapultableTiles[] = $tileCard;
                continue;
            }

            if (array_key_exists($hex, $adjacentHexes)) {
                continue;
            }

            $catapultableEmpty[$hex] = $hex;
        }

        return ["tiles" => $catapultableTiles, "empty" => array_unique($catapultableEmpty)];
    }

    public function revealTile(int $tileCard_id, int $player_id): void
    {
        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $revealedTiles = $this->globals->get(REVEALED_TILES, []);
        $revealedTiles[$tileCard_id] = $tileCard;
        $this->globals->set(REVEALED_TILES, $revealedTiles);

        $hex = (int) $tileCard["location_arg"];

        $this->notifyAllPlayers(
            "revealTile",
            clienttranslate('${player_name} reveals a ${tile} (hex ${log_hex})'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "log_hex" => $hex,
                "hex" => $hex,
                "tileCard" => $tileCard,
                "preserve" => ["tileCard"],
                "i18n" => ["tile"],
                "tile" => clienttranslate("tile"),
            ]
        );

        if ($player_id !== 1) {
            $this->globals->inc(REVEALS_LIMIT, 1);
        }
    }

    public function moveExplorer($tileCard_id, $player_id)
    {
        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $hex = (int) $tileCard["location_arg"];

        $explorerCard = $this->getExplorerByPlayerId($player_id);
        $explorerCard_id = (int) $explorerCard["id"];
        $this->explorer_cards->moveCard($explorerCard_id, "board", $hex);

        $this->notifyAllPlayers(
            "moveExplorer",
            clienttranslate('${player_name} moves ${his} explorer onto a new ${tile} (hex ${log_hex}) '),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "log_hex" => $hex,
                "hex" => $hex,
                "tileCard" => $tileCard,
                "explorerCard" => $explorerCard,
                "his" => $this->pronoun($player_id),
                "i18n" => ["his"],
                "preserve" => ["tileCard"],
                "tile" => clienttranslate("tile"),
            ]
        );

        if ($player_id !== 1) {
            $this->globals->set(HAS_MOVED_EXPLORER, true);
        }

        $this->resolveTileEffect($tileCard, $player_id);
    }

    public function resolveTileEffect(array $tileCard, int $player_id): void
    {
        if ($player_id === 1) {
            $this->rhomResolveTileEffect($tileCard);
            return;
        }

        $tile_id = (int) $tileCard["type_arg"];
        $region_id = (int) $tileCard["type"];

        $tile_info = $this->tiles_info[$tile_id];
        $tileEffect_id = (int) $tile_info["effect"];
        $gem_id = (int) $tile_info["gem"];

        $hasReachedForest = !!$this->getUniqueValueFromDB("SELECT forest from player WHERE player_id=$player_id");

        if ($region_id >= 3 && !$hasReachedForest) {
            $this->reachForest($player_id, $tileCard);
            return;
        }

        if ($tileEffect_id) {
            $tileEffect = $this->tileEffects_info[$tileEffect_id];
            $effectValue = $tileEffect["values"][$region_id];

            if ($tileEffect_id === 1) {
                $this->incCoin($effectValue, $player_id);
            }

            if ($tileEffect_id === 2) {
                $this->incRoyaltyPoints($effectValue, $player_id);
                $this->incStatWithRhom($effectValue, "tilesPoints", $player_id);
            }

            if ($tileEffect_id === 3) {
                $this->obtainStoneDie($player_id);
            }
        }

        if ($gem_id % 10 === 0) {
            if ($gem_id === 10) {
                $this->obtainIridiaStone($player_id);
            }

            $this->gamestate->nextState("rainbowTile");
            return;
        }

        if (!$this->incGem(1, $gem_id, $player_id, $tileCard)) {
            $anchorState_id = (int) $this->gamestate->state_id();
            $this->globals->set(ANCHOR_STATE, $anchorState_id);
            $this->gamestate->jumpToState(ST_TRANSFER_GEM);
            return;
        };

        $this->gamestate->nextState("optionalActions");
    }

    public function currentTile(int $player_id, bool $onlyGem = false): array | int
    {
        $explorerCard = $this->getExplorerByPlayerId($player_id);

        $hex = (int) $explorerCard["location_arg"];

        $tileCard = $this->getObjectFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board' AND card_location_arg=$hex");

        if ($onlyGem) {
            $tile_id = (int) $tileCard["type_arg"];
            $gem_id = $this->tiles_info[$tile_id]["gem"];

            if ($gem_id % 10 === 0) {
                $gem_id = $this->globals->get(RAINBOW_GEM);
            }

            return $gem_id;
        }

        return $tileCard;
    }

    public function collectTile(int $player_id): void
    {
        $explorerCard = $this->getExplorerByPlayerId($player_id);

        if ($explorerCard["location"] === "scene") {
            return;
        }

        $tileCard = $this->currentTile($player_id);
        $tileCard_id = (int) $tileCard["id"];

        $this->tile_cards->moveCard($tileCard_id, "hand", $player_id);

        $tile_id = (int) $tileCard["type_arg"];
        $tile_info = $this->tiles_info[$tile_id];
        $gem_id = (int) $tile_info["gem"];
        $region_id = (int) $tile_info["region"];

        $statName = $gem_id % 10 === 0 ? "rainbow:Tiles" : "$gem_id:GemTiles";
        $this->incStatWithRhom(1, $statName, $player_id);
        $this->incStatWithRhom(1, "tilesCollected", $player_id);

        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $this->notifyAllPlayers(
            "collectTile",
            "",
            [
                "player_id" => $player_id,
                "tileCard" => $tileCard,
            ]
        );

        if ($gem_id !== 0 && $gem_id !== 10) {
            $this->updateMarketValue(1, $gem_id);
        }

        if ($region_id === 5) {
            $this->reachCastle($player_id);
        }
    }

    public function reachForest(int $player_id): void
    {
        $this->DbQuery("UPDATE player SET forest=1 WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "reachForest",
            clienttranslate('${player_name} reaches the Forest region'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
            ]
        );

        $this->gamestate->nextState("discardObjective");
    }

    public function getIridiaStoneOwner(): int | null
    {
        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            if ($player_id === 1) {
                $foundIridia = !!$this->getUniqueValueFromDB("SELECT iridia_stone FROM robot WHERE id=1");
            } else {
                $foundIridia = !!$this->getUniqueValueFromDB("SELECT iridia_stone FROM player WHERE player_id=$player_id");
            }

            if ($foundIridia) {
                return $player_id;
            }
        }

        return null;
    }

    public function obtainIridiaStone(int $player_id): void
    {
        if ($this->getIridiaStoneOwner()) {
            throw new \BgaVisibleSystemException("The Iridia Stone has already been found: obtainIridiaStone");
        }

        if ($player_id === 1) {
            $this->DbQuery("UPDATE robot SET iridia_stone=1, score_aux=score_aux+1000 WHERE id=1");
        } else {
            $this->DbQuery("UPDATE player SET iridia_stone=1, player_score_aux=player_score_aux+1000 WHERE player_id=$player_id");
        }

        $this->notifyAllPlayers(
            "obtainIridiaStone",
            clienttranslate('${player_name} finds the Iridia Stone'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id)
            ]
        );

        $this->setStatWithRhom(10, "iridiaPoints", $player_id);
        $this->incRoyaltyPoints(10, $player_id);
    }

    public function getRoyaltyTokens(?int $player_id): array
    {
        if ($player_id) {
            foreach ($this->royaltyTokens_info as $token_id => $token_info) {
                $tokenName = $token_info["name"];
                $hasObtainedToken = !!$this->getUniqueValueFromDB("SELECT $tokenName FROM player WHERE player_id=$player_id");

                if ($hasObtainedToken) {
                    $token_info["id"] = $token_id;
                    return $token_info;
                }
            }
        }

        $royaltyTokens = [];

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $royaltyTokens[$player_id] = null;

            foreach ($this->royaltyTokens_info as $token_id => $token_info) {
                $tokenName = $token_info["name"];

                if ($player_id === 1) {
                    $hasObtainedToken = !!$this->getUniqueValueFromDB("SELECT $tokenName FROM robot WHERE id=1");
                } else {
                    $hasObtainedToken = !!$this->getUniqueValueFromDB("SELECT $tokenName FROM player WHERE player_id=$player_id");
                }

                if ($hasObtainedToken) {
                    $token_info["id"] = $token_id;
                    $royaltyTokens[$player_id] = $token_info;
                    break;
                }
            }
        }

        return $royaltyTokens;
    }

    public function reachCastle(int $player_id): void
    {
        if ($player_id === 1) {
            $this->DbQuery("UPDATE robot SET castle=1 WHERE id=1");
        } else {
            $this->DbQuery("UPDATE player SET castle=1 WHERE player_id=$player_id");
        }

        $this->notifyAllPlayers(
            "reacheCastle",
            clienttranslate('${player_name} reaches the Castle row'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id)
            ]
        );

        $castlePlayersCount = (int) $this->castlePlayersCount();
        $playersNumber = (int) $this->getPlayersNumber();

        $isSolo = $this->isSolo();
        if ($isSolo) {
            $playersNumber = 2;
        }

        if ($castlePlayersCount === $playersNumber) {
            return;
        }

        if ($castlePlayersCount === 1) {
            $score_aux = 100;
            $token_id = 3;

            if ($playersNumber <= 2) {
                $score_aux = 10;
                $token_id = 2;
            }
        }

        if ($castlePlayersCount === 2) {
            $score_aux = 10;
            $token_id = 2;

            if ($playersNumber === 3) {
                $score_aux = 1;
                $token_id = 1;
            }
        }

        if ($castlePlayersCount === 3) {
            $score_aux = 1;
            $token_id = 1;
        }

        $token_info = $this->royaltyTokens_info[$token_id];
        $tokenName = $token_info["name"];
        $tokenLabel = $token_info["tr_name"];
        $tokenPoints = (int) $token_info["points"];

        $this->notifyAllPlayers(
            "obtainRoyaltyToken",
            clienttranslate('${player_name} obtains the ${token_label}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "token_id" => $token_id,
                "token_label" => $tokenLabel,
                "tokenName" => $tokenName,
                "i18n" => ["token_label"]
            ]
        );

        if ($player_id === 1) {
            $this->DbQuery("UPDATE robot SET $tokenName=1, score_aux=score_aux+$score_aux WHERE id=1");
        } else {
            $this->DbQuery("UPDATE player SET $tokenName=1, player_score_aux=player_score_aux+$score_aux WHERE player_id=$player_id");
        }

        $this->setStatWithRhom($tokenPoints, "tokenPoints", $player_id);
        $this->incRoyaltyPoints($tokenPoints, $player_id);
    }

    public function castlePlayersCount(): int
    {
        $castlePlayers = $this->getCollectionFromDB("SELECT player_id FROM player WHERE castle=1");
        $castlePlayersCount = count($castlePlayers);

        if ($this->isSolo()) {
            $castleRhom = !!$this->getUniqueValueFromDB("SELECT castle from robot WHERE id=1");
            if ($castleRhom) {
                $castlePlayersCount++;
            }
        }

        return $castlePlayersCount;
    }

    public function getCollectedTiles(?int $player_id): array
    {
        if ($player_id) {
            return $this->tile_cards->getCardsInLocation("hand", $player_id);
        }

        $collectedTiles = [];

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $collectedTiles[$player_id] = $this->tile_cards->getCardsInLocation("hand", $player_id);
        }

        return $collectedTiles;
    }

    public function getCoins(?int $player_id): int | array
    {
        $sql = "SELECT coin FROM player WHERE player_id=";
        if ($player_id) {
            return (int) $this->getUniqueValueFromDB("$sql$player_id");
        }

        $coins = [];

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $coins[$player_id] = (int) $this->getUniqueValueFromDB("$sql$player_id");
        }

        return $coins;
    }

    public function getGems(?int $player_id): array
    {
        if ($player_id) {
            return $this->gem_cards->getCardsInLocation("hand", $player_id);
        }

        $gems = [];

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $gems[$player_id] = $this->gem_cards->getCardsInLocation("hand", $player_id);
        }

        return $gems;
    }

    public function getGemsCounts(?int $player_id, bool $useId = false): array
    {
        $gemsCounts = [];

        if ($player_id) {
            foreach ($this->gems_info as $gem_id => $gem_info) {
                $gemName = $gem_info["name"];
                $handGems = $this->gem_cards->getCardsOfTypeInLocation($gemName, null, "hand", $player_id);

                if ($useId) {
                    $gemsCounts[$gem_id] = count($handGems);
                    continue;
                }

                $gemsCounts[$gemName] = count($handGems);
            }

            return $gemsCounts;
        }

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $gemsCounts[$player_id] = [];

            foreach ($this->gems_info as $gem_info) {
                $gemName = $gem_info["name"];
                $handGems = $this->gem_cards->getCardsOfTypeInLocation($gemName, null, "hand", $player_id);
                $gemsCounts[$player_id][$gemName] = count($handGems);
            }
        }

        return $gemsCounts;
    }

    public function getTotalGemsCount(int $player_id): int
    {
        $gems = $this->getGemsCounts($player_id);

        $totalGemsCount = 0;
        foreach ($gems as $gemCount) {
            $totalGemsCount += $gemCount;
        }

        return $totalGemsCount;
    }

    public function mine(int $gem_id, array $dice, int $player_id): int
    {
        $gemName = $this->gems_info[$gem_id]["name"];
        $gemMarketValue = $this->globals->get("$gemName:MarketValue");

        $minedGemsCount = 0;

        foreach ($dice as $die) {
            $die_id = $die["id"];
            $dieType = $die["type"];

            $roll = $this->rollDie($die_id, $player_id, $dieType);

            if ($roll >= $gemMarketValue) {
                $minedGemsCount++;
            }
        }

        $this->notifyAllPlayers(
            "syncDieRolls",
            "",
            []
        );

        foreach ($dice as $die) {
            if ($die["type"] !== "stone") {
                continue;
            }

            $die_id = (int) $die["id"];

            $this->notifyAllPlayers(
                "activateStoneDie",
                "",
                [
                    "player_id" => $player_id,
                    "die_id" => $die_id
                ]
            );
        }

        return $minedGemsCount;
    }

    public function availableCargos(int $current_player_id = null, int $excess = 1): array
    {
        if ($this->isSolo()) {
            if ($this->getTotalGemsCount(1) + $excess <= 7) {
                return [1];
            }
            return [];
        }

        $players = $this->loadPlayersNoZombie();

        $availableCargos = [];
        foreach ($players as $player_id => $player) {
            if ($this->getTotalGemsCount($player_id) + $excess <= 7 && $player_id !== $current_player_id) {
                $availableCargos[] = $player_id;
            }
        }

        return $availableCargos;
    }

    public function transferGems(array $gemCards, int $opponent_id, int $player_id): void
    {
        $gemCardsByType = [];

        foreach ($gemCards as $gemCard) {
            $gemCard_id = (int) $gemCard["id"];
            $gemCard = $this->gem_cards->getCard($gemCard_id);
            $this->checkCardLocation($gemCard, "hand", $player_id);

            $this->gem_cards->moveCard($gemCard_id, "hand", $opponent_id);

            $gem_id = (int) $gemCard["type_arg"];

            if (!array_key_exists($gem_id, $gemCardsByType)) {
                $gemCardsByType[$gem_id] = [];
            }

            $gemCardsByType[$gem_id][] = $gemCard;
        }

        foreach ($gemCardsByType as $gem_id => $gemCards) {
            $delta = count($gemCards);

            $gem_info = $this->gems_info[$gem_id];
            $gemName = $gem_info["name"];

            $this->notifyAllPlayers(
                "transferGem",
                clienttranslate('${player_name} gives away ${delta} ${gem_label} to ${player_name2}'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "player_name2" => $this->getPlayerOrRhomNameById($opponent_id),
                    "player_id2" => $opponent_id,
                    "delta" => $delta,
                    "gem_label" => $gem_info["tr_name"],
                    "gemName" => $gemName,
                    "gemCards" => $gemCards,
                    "i18n" => ["gem_label"],
                    "preserve" => ["gem_id"],
                    "gem_id" => $gem_id,
                ]
            );
        }
    }

    public function discardGems(int $player_id, ?array $gemCards, ?int $gem_id, ?int $delta = 1): void
    {
        if (!$gemCards) {
            if (!$gem_id) {
                throw new \BgaVisibleSystemException("One of the args 'gemCards' and 'gem_id' is mandatory: discardGem");
            }

            $gemCards = $this->getCollectionFromDB("$this->deckSelectQuery from gem 
            WHERE card_location='hand' AND card_location_arg=$player_id AND card_type_arg=$gem_id LIMIT $delta");
        }

        if (!$gem_id) {
            $gemCard = reset($gemCards);
            $gem_id = (int) $gemCard["type_arg"];
        }

        $this->decGem($gem_id, $gemCards, $player_id);

        $this->notifyAllPlayers(
            "discardGems",
            clienttranslate('${player_name} returns ${delta} ${gem_label} to the supply'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "delta" => count($gemCards),
                "gemCards" => $gemCards,
                "gem_label" => $this->gems_info[$gem_id]["tr_name"],
                "i18n" => ["gem_label"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );
    }

    public function incGem(int $delta, int $gem_id, int $player_id, array $tileCard = null, bool $mine = false, bool $silent = false): bool
    {
        $gemName = $this->gems_info[$gem_id]["name"];

        $gemCards = $this->gem_cards->pickCardsForLocation($delta, $gemName, "hand", $player_id);

        $message = $mine ? clienttranslate('${player_name} mines ${delta} ${gem_label}') :
            clienttranslate('${player_name} collects ${delta} ${gem_label}');

        if ($silent) {
            $message = "";
        }

        $this->notifyAllPlayers(
            "incGem",
            $message,
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "delta" => $delta,
                "gem_label" => $this->gems_info[$gem_id]["tr_name"],
                "gemName" => $gemName,
                "gemCards" => $gemCards,
                "tileCard" => $tileCard,
                "i18n" => ["gem_label"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );

        if ($this->getTotalGemsCount($player_id) > 7) {
            return false;
        }

        return true;
    }

    public function decGem(int $gem_id, array $gemCards, int $player_id, bool $sell = false, bool $trade = false): void
    {
        $delta = count($gemCards);

        if ($delta <= 0) {
            throw new \BgaVisibleSystemException("The delta must be positive: decGem, $delta");
        }

        if (count($gemCards) < $delta) {
            throw new \BgaVisibleSystemException("Not enough gems: decGem, $delta");
        }

        $gemName = $this->gems_info[$gem_id]["name"];

        foreach ($gemCards as $gemCard) {
            $gemCard_id = (int) $gemCard["id"];
            $gemCard = $this->gem_cards->getCard($gemCard_id);

            $this->checkCardLocation($gemCard, "hand", $player_id);

            $this->gem_cards->insertCardOnExtremePosition($gemCard_id, $gemName, false);
        }

        $message = "";

        if ($sell) {
            $message = clienttranslate('${player_name} sells ${delta} ${gem_label}');
        } else if ($trade) {
            $message = clienttranslate('${player_name} trades ${delta} ${gem_label}');
        }

        $this->notifyAllPlayers(
            "decGem",
            $message,
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "delta" => $delta,
                "gemName" => $gemName,
                "gemCards" => $gemCards,
                "gem_label" => $this->gems_info[$gem_id]["tr_name"],
                "i18n" => ["gem_label"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );
    }


    public function sellGems(int $delta, int $gem_id, array $gemCards, int $player_id): void
    {
        $gemName = $this->gems_info[$gem_id]["name"];

        $this->decGem(
            $gem_id,
            $gemCards,
            $player_id,
            true
        );

        $marketValue = $this->globals->get("$gemName:MarketValue");
        $earnedCoins = $marketValue * $delta;

        $this->incCoin($earnedCoins, $player_id);
    }

    public function actionAfterSell(): void
    {
        if ($this->globals->get(SOLD_GEM) !== null) {
            $this->globals->set(ACTION_AFTER_SELL, true);
        }
    }

    public function updateMarketValue(int $delta, int $gem_id, bool $silent = false): int
    {
        $gem_info = $this->gems_info[$gem_id];
        $gemName = $gem_info["name"];

        $marketValueCode = "$gemName:MarketValue";
        $marketValue = $this->globals->inc($marketValueCode, $delta);

        if ($marketValue > 6) {
            $marketValue = $this->globals->inc($marketValueCode, -6);

            $this->notifyAllPlayers(
                'crashMarket',
                clienttranslate('The market crashes for ${gem_label}'),
                [
                    "gem_label" => $gem_info["tr_name"],
                    "i18n" => ["gem_label"],
                    "preserve" => ["gem_id"],
                    "gem_id" => $gem_id,
                ]
            );
        }

        if ($marketValue < 1) {
            $marketValue = $this->globals->inc($marketValueCode, 6);
        }

        $notifEvent = $silent ? "message" : "updateMarketValue";
        $this->notifyAllPlayers(
            $notifEvent,
            clienttranslate('The market value of ${gem_label} is ${marketValue} now'),
            [
                "marketValue" => $marketValue,
                "gem_label" => $gem_info["tr_name"],
                "i18n" => ["gem_label"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );

        return $marketValue;
    }

    public function getMarketValues(?int $gem_id): array | int
    {
        if ($gem_id) {
            $gemName = $this->gems_info[$gem_id]["name"];
            return $this->globals->get("$gemName:MarketValue");
        };

        foreach ($this->gems_info as $gem_id => $gem_info) {
            $gemName = $gem_info["name"];
            $marketValues[$gemName] = $this->globals->get("$gemName:MarketValue");
        }

        return $marketValues;
    }

    public function hasEnoughCoins(int $delta, int $player_id)
    {
        $currentCoins = $this->getUniqueValueFromDB("SELECT coin from player WHERE player_id=$player_id");

        return $currentCoins >= $delta;
    }

    public function incCoin(int $delta, int $player_id): void
    {
        $this->dbQuery("UPDATE player SET coin=coin+$delta WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "incCoin",
            clienttranslate('${player_name} obtains ${delta_log} ${coin}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "delta" => $delta,
                "delta_log" => $delta,
                "coin" => clienttranslate("coin(s)"),
                "i18n" => ["coin"],
            ]
        );

        $this->incStatWithRhom($delta, "coinsObtained", $player_id);
    }

    public function decCoin(int $delta, int $player_id): void
    {
        if ($delta <= 0) {
            throw new \BgaVisibleSystemException("The delta must be positive: decCoin, $delta");
        }

        if (!$this->hasEnoughCoins($delta, $player_id)) {
            throw new \BgaVisibleSystemException("You don't have enough coins: decCoin, $delta");
        }

        $this->dbQuery("UPDATE player SET coin=coin-$delta WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "incCoin",
            clienttranslate('${player_name} spends ${delta_log} ${coin}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "delta" => -$delta,
                "delta_log" => $delta,
                "coin" => clienttranslate("coin(s)"),
                "i18n" => ["coin"],
                "preserve" => ["delta_log"],
            ]
        );
    }

    public function incRoyaltyPoints(int $delta, int $player_id, bool $silent = false): void
    {
        if ($delta === 0) {
            return;
        }

        $this->dbQuery("UPDATE player SET player_score=player_score+$delta WHERE player_id=$player_id");

        if ($player_id === 1) {
            $this->dbQuery("UPDATE robot SET score=score+$delta WHERE id=1");
        }

        $this->notifyAllPlayers(
            "incRoyaltyPoints",
            $silent ? "" : clienttranslate('${player_name} scores ${points_log} point(s)'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "points" => $delta,
                "points_log" => $delta,
                "preserve" => ["points_log"],
            ]
        );
    }

    public function getPublicStoneDice(): array
    {
        $playerDice = $this->globals->get(PLAYER_STONE_DICE);
        $allPlayerDice = [];

        foreach ($playerDice as $dice) {
            $allPlayerDice = array_merge($allPlayerDice, $dice);
        }

        $publicDice = array_values(array_diff([1, 2, 3, 4], $allPlayerDice));
        return $publicDice;
    }

    public function obtainStoneDie(int $player_id): bool
    {
        $publicStoneDice = $this->getPublicStoneDice();

        if (!$publicStoneDice) {
            return false;
        }

        $die_id = reset($publicStoneDice);

        $stoneDice = $this->globals->get(PLAYER_STONE_DICE);
        $stoneDice[$player_id][] = $die_id;
        $this->globals->set(PLAYER_STONE_DICE, $stoneDice);

        $this->notifyAllPlayers(
            "obtainStoneDie",
            clienttranslate('${player_name} obtains a Stone die'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "die_id" => $die_id,
            ]
        );

        return true;
    }

    public function resetStoneDice(int $player_id): void
    {
        $activeDice = $this->globals->get(ACTIVE_STONE_DICE);
        $playerDice = $this->globals->get(PLAYER_STONE_DICE);

        if (!$activeDice) {
            return;
        }

        $playerDice[$player_id] = array_values(array_diff($playerDice[$player_id], $activeDice));
        $this->globals->set(PLAYER_STONE_DICE, $playerDice);
        $this->globals->set(ACTIVE_STONE_DICE, []);

        $this->notifyAllPlayers(
            "resetStoneDice",
            "",
            [
                "player_id" => $player_id,
                "resetDice" => $activeDice,
            ]
        );
    }

    public function getRelicsDeck(bool $onlyTop = false): ?array
    {
        $relicsDeckTop = $this->relic_cards->getCardOnTop("deck");

        if ($relicsDeckTop === null) {
            return null;
        }

        $relicsDeckTop_id = $relicsDeckTop["id"];

        if ($onlyTop) {
            return $this->hideCard($relicsDeckTop, true, "fake");
        }

        $relicsDeck = $this->relic_cards->getCardsInLocation("deck");
        unset($relicsDeck[$relicsDeckTop_id]);

        return $this->hideCards($relicsDeck, true, true);
    }

    public function getRelicsMarket(): array
    {
        $relicsMarket = $this->relic_cards->getCardsInLocation("market");

        uasort($relicsMarket, function ($relicCard, $otherRelicCard) {
            return (int) $otherRelicCard["location_arg"] <=> (int) $relicCard["location_arg"];
        });

        return $relicsMarket;
    }

    public function getRestoredRelics(?int $player_id): array
    {
        if ($player_id) {
            return $this->relic_cards->getCardsInLocation("hand", $player_id);
        }

        $relicCards = [];

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $relicCards[$player_id] = $this->relic_cards->getCardsInLocation("hand", $player_id);
        }

        return $relicCards;
    }

    function canPayRelicCost(int $relic_id, int $player_id): bool
    {
        $canPayRelicCost = true;

        $relicCost = $this->relics_info[$relic_id]["cost"];
        $playerGems = $this->getGemsCounts($player_id);

        foreach ($playerGems as $gemName => $gemCount) {
            $gem_id = $this->gemsIds_info[$gemName];
            if ($gemCount < $relicCost[$gem_id]) {
                $canPayRelicCost = false;
                break;
            }
        }

        return $canPayRelicCost;
    }

    function restorableRelics(int $player_id, bool $associative = false): array
    {
        $relicCards = $this->relic_cards->getCardsInLocation("market");

        $restorableRelics = [];
        foreach ($relicCards as $relicCard_id => $relicCard) {
            $relic_id = (int) $relicCard["type_arg"];
            $canPayRelicCost =  $this->canPayRelicCost($relic_id, $player_id);

            if ($canPayRelicCost) {
                if ($associative) {
                    $restorableRelics[$relicCard_id] = $relicCard;
                    continue;
                }

                $restorableRelics[] = $relicCard;
            }
        }

        if ($associative) {
            uasort($restorableRelics, function ($relicCard, $otherRelicCard) {
                return (int) $otherRelicCard["location_arg"] <=> $relicCard["location_arg"];
            });
        } else {
            usort($restorableRelics, function ($relicCard, $otherRelicCard) {
                return (int) $otherRelicCard["location_arg"] <=> $relicCard["location_arg"];
            });
        }

        return $restorableRelics;
    }

    public function getBooks(?int $player_id = null): array
    {
        $books = [];

        if ($player_id) {
            $bookItem = $this->getObjectFromDB("$this->deckSelectQuery FROM item WHERE card_location='book' AND card_location_arg=$player_id");
            $bookRelic = $this->getObjectFromDB("$this->deckSelectQuery FROM relic WHERE card_location='book' AND card_location_arg=$player_id");
            return ["item" => $bookItem, "relic" => $bookRelic];
        }

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $bookItem = $this->getObjectFromDB("$this->deckSelectQuery FROM item WHERE card_location='book' AND card_location_arg=$player_id");
            $bookRelic = $this->getObjectFromDB("$this->deckSelectQuery FROM relic WHERE card_location='book' AND card_location_arg=$player_id");
            $books[$player_id] = ["item" => $bookItem, "relic" => $bookRelic];
        }

        return $books;
    }

    public function canRestoreBook(int $player_id): bool
    {
        $bookRelic = $this->getBooks($player_id)["relic"];

        if (!$bookRelic) {
            return false;
        }

        $relic_id = (int) $bookRelic["type_arg"];

        return $this->canPayRelicCost($relic_id, $player_id);
    }

    public function restoreRelic(int $relicCard_id, int $player_id): void
    {
        $relicCard = $this->relic_cards->getCard($relicCard_id);
        $relic_id = (int) $relicCard["type_arg"];

        $relic_info = $this->relics_info[$relic_id];
        $relicCost = $relic_info["cost"];
        $relicPoints = (int) $relic_info["points"];
        $relicType = (int) $relic_info["type"];
        $leadGem = (int) $relic_info["leadGem"];

        $statName = $leadGem === 0 ? "iridia:Relics" : "$leadGem:GemRelics";
        $this->incStatWithRhom(1, $statName, $player_id);

        if ($relicType !== 0) {
            $this->incStatWithRhom(1, "$relicType:TypeRelics", $player_id);
        }

        foreach ($relicCost as $gem_id => $gemCost) {
            if ($gemCost === 0) {
                continue;
            }

            $gemName = $this->gems_info[$gem_id]["name"];

            $gemCards = $this->gem_cards->getCardsOfTypeInLocation($gemName, null, "hand", $player_id);
            $gemCards = array_slice($gemCards, 0, $gemCost, true);

            $this->decGem($gem_id, $gemCards, $player_id);
        }

        $this->relic_cards->moveCard($relicCard_id, "hand", $player_id);
        $this->notifyAllPlayers(
            "restoreRelic",
            clienttranslate('${player_name} restores the ${relic_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "relic_name" => $relic_info["tr_name"],
                "relicCard" => $relicCard,
                "i18n" => ["relic_name"],
                "preserve" => ["relicCard"],
            ]
        );

        $this->incRoyaltyPoints($relicPoints, $player_id);
        $this->incStatWithRhom($relicPoints, "relicsPoints", $player_id);

        if ($relicCard["location"] === "book") {
            $itemCard = $this->getBooks($player_id)["item"];
            $itemCard_id = (int) $itemCard["id"];
            $item = new ItemManager($itemCard_id, $this);

            $item->discard();
            return;
        }

        $this->replaceRelic();
    }

    public function replaceRelic(): void
    {
        $relicCard = $this->relic_cards->getCardOnTop("deck");

        if (!$relicCard) {
            return;
        }

        $relicCard_id = (int) $relicCard["id"];
        $this->relic_cards->insertCardOnExtremePosition($relicCard_id, "market", false);
        $relicCard = $this->relic_cards->getCard($relicCard_id);
        $relic_id = $relicCard["type_arg"];
        $relic_info = $this->relics_info[$relic_id];

        $relicsDeckTop = $this->getRelicsDeck(true);

        $this->notifyAllPlayers(
            "replaceRelic",
            clienttranslate('A new Relic is revealed: the ${relic_name}'),
            [
                "relic_name" => $relic_info["tr_name"],
                "relicsDeckCount" => $this->relic_cards->countCardsInLocation("deck"),
                "relicsDeckTop" => $relicsDeckTop,
                "relicCard" => $relicCard,
                "i18n" => ["relic_name"],
                "preserve" => ["relicCard"],
            ]
        );
    }

    public function bookableRelics(bool $associative = false): array
    {
        $sql = "SELECT card_type type, card_type_arg type_arg, card_location location FROM relic WHERE card_location!='hand' AND card_location!='book' ORDER BY card_type_arg";
        if ($associative) {
            return $this->getCollectionFromDb($sql);
        }

        return  $this->getObjectListFromDB($sql);
    }

    public function getItemsDeck(): array
    {
        $itemsDeck = $this->item_cards->getCardsInLocation("deck");

        return $this->hideCards($itemsDeck, true, true);
    }

    public function getItemsMarket(): array
    {
        $itemsMarket = $this->item_cards->getCardsInLocation("market");
        uasort($itemsMarket, function ($itemCard, $otherItemCard) {
            return (int) $otherItemCard["location_arg"] <=> (int) $itemCard["location_arg"];
        });

        return $itemsMarket;
    }

    public function checkItemsMarket(): bool
    {
        $itemsMarket = $this->getItemsMarket();

        $itemsCounts = [];
        $pairsCount = 0;

        foreach ($itemsMarket as $itemCard_id => $itemCard) {
            $item_id = (int) $itemCard["type_arg"];

            if (!array_key_exists($item_id, $itemsCounts)) {
                $itemsCounts[$item_id] = 0;
            }

            $itemsCounts[$item_id]++;

            if ($itemsCounts[$item_id] === 2) {
                $pairsCount++;
            }
        }

        $trio = max($itemsCounts) >= 3;
        $pairs = $pairsCount >= 2;

        $isValid = !$trio && !$pairs;

        return $isValid;
    }

    public function reshuffleItemsDeck(bool $setup = false, array $players = null)
    {
        $this->item_cards->moveAllCardsInLocation("market", "deck");
        $this->item_cards->moveAllCardsInLocation("discard", "deck");

        if ($players === null) {
            $players = $this->loadPlayersBasicInfos();
        }

        $itemsMarketCount = count($players) === 1 ? 6 : 5;

        $this->item_cards->shuffle("deck");
        $this->item_cards->pickCardsForLocation($itemsMarketCount, "deck", "market");

        if (!$this->checkItemsMarket()) {
            $this->reshuffleItemsDeck($setup);
            return;
        };

        $itemsDeck = $this->getItemsDeck();
        $itemsMarket = $this->getItemsMarket();

        if ($setup) {
            return;
        }

        $this->notifyAllPlayers(
            "reshuffleItemsDeck",
            clienttranslate('All Items in the Market and in the discard are reshuffled into the deck for a Market refresh'),
            [
                "itemsDeck" => $itemsDeck,
                "itemsMarket" => $itemsMarket,
            ]
        );
    }

    public function getBoughtItems(?int $player_id): array
    {
        $boughtItems = [];

        if ($player_id) {
            return $this->item_cards->getCardsInLocation("hand", $player_id);
        }

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $boughtItems[$player_id] = $this->item_cards->getCardsInLocation("hand", $player_id);
        }

        return $boughtItems;
    }

    public function getActiveItems(): array
    {
        $activeItems = $this->item_cards->getCardsInLocation("active");
        return $activeItems;
    }

    public function getItemsDiscard(): array
    {
        $itemsDiscard = $this->item_cards->getCardsInLocation("discard");
        return $itemsDiscard;
    }

    public function buyableItems(int $player_id, bool $associative = false): array
    {
        $buyableItems = [];
        $marketItems = $this->item_cards->getCardsInLocation("market");

        foreach ($marketItems as $itemCard_id => $itemCard) {
            $item = new ItemManager($itemCard_id, $this);

            if ($item->isBuyable($player_id)) {
                if ($associative) {
                    $buyableItems[$itemCard_id] = $itemCard;
                    continue;
                }

                $buyableItems[] = $itemCard;
            }
        }

        return $buyableItems;
    }

    public function usableItems(int $player_id, bool $associative = false): array
    {
        $usableItems = [];
        $boughtItems = $this->item_cards->getCardsInLocation("hand", $player_id);

        foreach ($boughtItems as $itemCard_id => $itemCard) {
            $item = new ItemManager($itemCard_id, $this);

            if ($item->isUsable($player_id)) {
                if ($associative) {
                    $usableItems[$itemCard_id] = $itemCard;
                    continue;
                }

                $usableItems[] = $itemCard;
            }
        }

        return $usableItems;
    }

    public function cancellableItems(int $player_id, bool $associative = false): array
    {
        $cancellableItems = [];
        $activeItems = $this->getActiveItems();

        foreach ($activeItems as $itemCard_id => $itemCard) {
            $item = new ItemManager($itemCard_id, $this);

            if ($item->isCancellable($player_id)) {
                if ($associative) {
                    $cancellableItems[$itemCard_id] = $itemCard;
                    continue;
                }

                $cancellableItems[] = $itemCard;
            }
        }

        return $cancellableItems;
    }

    public function replaceItem(): void
    {
        $itemCard = $this->item_cards->getCardOnTop("deck");

        if (!$itemCard) {
            $this->reshuffleItemsDeck();
            return;
        }

        $itemCard_id = (int) $itemCard["id"];
        $this->item_cards->insertCardOnExtremePosition($itemCard_id, "market", false);
        $itemCard = $this->item_cards->getCard($itemCard_id);

        $item = new ItemManager($itemCard_id, $this);

        $this->notifyAllPlayers(
            "replaceItem",
            clienttranslate('A new Item is revealed: the ${item_name}'),
            [
                "item_name" => $item->tr_name,
                "itemCard" => $item->card,
                "i18n" => ["item_name"],
                "preserve" => ["item_id"],
                "item_id" => $item->id,
            ]
        );

        if (!$this->checkItemsMarket()) {
            $this->reshuffleItemsDeck();
        };
    }

    function disableItems()
    {
        $activeItems = $this->getActiveItems();

        if (!$activeItems) {
            return;
        }

        foreach ($activeItems as $itemCard_id => $itemCard) {
            $item = new ItemManager($itemCard_id, $this);
            $item->disable();
            $item->discard();
        }
    }

    public function getObjectives(int $current_player_id, bool $unique = false): array
    {
        if ($unique) {
            return $this->objective_cards->getCardsInLocation("hand", $current_player_id);
        }

        $objectiveCards = [];

        $players = $this->loadPlayersNoZombie();
        foreach ($players as $player_id => $player) {
            $cards = $this->objective_cards->getCardsInLocation("hand", $player_id);
            $objectiveCards[$player_id] = $player_id === $current_player_id ? $cards : $this->hideCards($cards);
        }

        return $objectiveCards;
    }

    public function computeGemsPoints(int $player_id): int
    {
        $totalGemsCount = $this->getTotalGemsCount($player_id);

        $gemsPoints = (int) ($totalGemsCount - ($totalGemsCount % 2)) / 2;

        $this->incRoyaltyPoints($gemsPoints, $player_id, true);

        if ($gemsPoints > 0) {
            $this->notifyAllPlayers(
                "computeGemsPoints",
                clienttranslate('${player_name} scores ${points_log} point(s) from gem sets'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "points" => $gemsPoints,
                    "preserve" => ["points_log", "finalScoring"],
                    "points_log" => $gemsPoints,
                    "finalScoring" => true,
                ]
            );
        }

        return $gemsPoints;
    }

    function tilesDp($gemsCounts, &$memo)
    {
        $key = implode(',', $gemsCounts);
        if (isset($memo[$key])) {
            return $memo[$key];
        }

        $maxPoints = 0;

        // Try to create sets of 7 (12 points)
        for ($gem_id = 1; $gem_id <= 4; $gem_id++) {
            if ($gemsCounts[$gem_id] + $gemsCounts[0] >= 7) { // Enough gems including wilds
                $usedWild = max(0, 7 - $gemsCounts[$gem_id]);
                $newGemsCounts = $gemsCounts; // Copy the original array
                $newGemsCounts[0] -= $usedWild; // Use wilds
                $newGemsCounts[$gem_id] -= (7 - $usedWild); // Use regular gems
                if ($newGemsCounts[$gem_id] >= 0 && $newGemsCounts[0] >= 0) {
                    $maxPoints = max($maxPoints, 12 + $this->tilesDp($newGemsCounts, $memo));
                }
            }
        }

        // Try to create sets of 5 (7 points)
        for ($gem_id = 1; $gem_id <= 4; $gem_id++) {
            if ($gemsCounts[$gem_id] + $gemsCounts[0] >= 5) {
                $usedWild = max(0, 5 - $gemsCounts[$gem_id]);
                $newGemsCounts = $gemsCounts; // Copy the original array
                $newGemsCounts[0] -= $usedWild; // Use wilds
                $newGemsCounts[$gem_id] -= (5 - $usedWild); // Use regular gems
                if ($newGemsCounts[$gem_id] >= 0 && $newGemsCounts[0] >= 0) {
                    $maxPoints = max($maxPoints, 7 + $this->tilesDp($newGemsCounts, $memo));
                }
            }
        }

        // Try to create sets of 3 (3 points)
        for ($gem_id = 1; $gem_id <= 4; $gem_id++) {
            if ($gemsCounts[$gem_id] + $gemsCounts[0] >= 3) {
                $usedWild = max(0, 3 - $gemsCounts[$gem_id]);
                $newGemsCounts = $gemsCounts; // Copy the original array
                $newGemsCounts[0] -= $usedWild; // Use wilds
                $newGemsCounts[$gem_id] -= (3 - $usedWild); // Use regular gems
                if ($newGemsCounts[$gem_id] >= 0 && $newGemsCounts[0] >= 0) {
                    $maxPoints = max($maxPoints, 3 + $this->tilesDp($newGemsCounts, $memo));
                }
            }
        }

        $memo[$key] = $maxPoints;
        return $maxPoints;
    }

    public function calcMaxTilesPoints(array $gemsCounts): int
    {
        $memo = [];
        $totalPoints = $this->tilesDp($gemsCounts, $memo);

        return $totalPoints;
    }

    public function computeTilesPoints(int $player_id): int
    {
        $tilesCountsByGem = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
        ];

        $tileCards = $this->tile_cards->getCardsInLocation("hand", $player_id);
        foreach ($tileCards as $tileCard) {
            $tile_id = (int) $tileCard["type_arg"];
            $gem_id = (int) $this->tiles_info[$tile_id]["gem"];

            if ($gem_id === 10) {
                $gem_id = 0;
            }

            $tilesCountsByGem[$gem_id]++;
        }

        $tilesPoints = $this->calcMaxTilesPoints($tilesCountsByGem);

        if ($tilesPoints) {
            $this->notifyAllPlayers(
                "computeTilesPoints",
                clienttranslate('${player_name} scores ${points_log} points from tile sets'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "points" => $tilesPoints,
                    "preserve" => ["points_log", "finalScoring"],
                    "points_log" => $tilesPoints,
                    "finalScoring" => true,
                ],
            );
        }

        $this->incRoyaltyPoints($tilesPoints, $player_id, true);

        return $tilesPoints;
    }

    function relicsDp($tech, $lore, $jewelry, $iridia, &$memo)
    {
        // If we've already computed this state, return the memoized result
        if (isset($memo[$tech][$lore][$jewelry][$iridia])) {
            return $memo[$tech][$lore][$jewelry][$iridia];
        }

        // Base case: If no relics are left, no points can be scored
        if ($tech == 0 && $lore == 0 && $jewelry == 0 && $iridia == 0) {
            return 0;
        }

        $maxPoints = 0;

        // Try to form lore-lore-lore (9 points)
        if ($lore >= 3) {
            $maxPoints = max($maxPoints, 9 + $this->relicsDp($tech, $lore - 3, $jewelry, $iridia, $memo));
        } elseif ($lore >= 2 && $iridia >= 1) {
            $maxPoints = max($maxPoints, 9 + $this->relicsDp($tech, $lore - 2, $jewelry, $iridia - 1, $memo));
        } elseif ($lore >= 1 && $iridia >= 2) {
            $maxPoints = max($maxPoints, 9 + $this->relicsDp($tech, $lore - 1, $jewelry, $iridia - 2, $memo));
        } elseif ($iridia >= 3) {
            $maxPoints = max($maxPoints, 9 + $this->relicsDp($tech, $lore, $jewelry, $iridia - 3, $memo));
        }

        // Try to form tech-tech-tech (7 points)
        if ($tech >= 3) {
            $maxPoints = max($maxPoints, 7 + $this->relicsDp($tech - 3, $lore, $jewelry, $iridia, $memo));
        } elseif ($tech >= 2 && $iridia >= 1) {
            $maxPoints = max($maxPoints, 7 + $this->relicsDp($tech - 2, $lore, $jewelry, $iridia - 1, $memo));
        } elseif ($tech >= 1 && $iridia >= 2) {
            $maxPoints = max($maxPoints, 7 + $this->relicsDp($tech - 1, $lore, $jewelry, $iridia - 2, $memo));
        } elseif ($iridia >= 3) {
            $maxPoints = max($maxPoints, 7 + $this->relicsDp($tech, $lore, $jewelry, $iridia - 3, $memo));
        }

        // Try to form jewelry-jewelry-jewelry (5 points)
        if ($jewelry >= 3) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore, $jewelry - 3, $iridia, $memo));
        } elseif ($jewelry >= 2 && $iridia >= 1) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore, $jewelry - 2, $iridia - 1, $memo));
        } elseif ($jewelry >= 1 && $iridia >= 2) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore, $jewelry - 1, $iridia - 2, $memo));
        } elseif ($iridia >= 3) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore, $jewelry, $iridia - 3, $memo));
        }

        // Try to form tech-lore-jewelry (5 points)
        if ($tech >= 1 && $lore >= 1 && $jewelry >= 1) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech - 1, $lore - 1, $jewelry - 1, $iridia, $memo));
        } elseif ($tech >= 1 && $lore >= 1 && $iridia >= 1) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech - 1, $lore - 1, $jewelry, $iridia - 1, $memo));
        } elseif ($tech >= 1 && $jewelry >= 1 && $iridia >= 1) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech - 1, $lore, $jewelry - 1, $iridia - 1, $memo));
        } elseif ($lore >= 1 && $jewelry >= 1 && $iridia >= 1) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore - 1, $jewelry - 1, $iridia - 1, $memo));
        } elseif ($tech >= 1 && $iridia >= 2) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech - 1, $lore, $jewelry, $iridia - 2, $memo));
        } elseif ($lore >= 1 && $iridia >= 2) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore - 1, $jewelry, $iridia - 2, $memo));
        } elseif ($jewelry >= 1 && $iridia >= 2) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore, $jewelry - 1, $iridia - 2, $memo));
        } elseif ($iridia >= 3) {
            $maxPoints = max($maxPoints, 5 + $this->relicsDp($tech, $lore, $jewelry, $iridia - 3, $memo));
        }

        // Memoize the result before returning it
        $memo[$tech][$lore][$jewelry][$iridia] = $maxPoints;

        return $maxPoints;
    }

    public function calcMaxRelicsPoints(int $tech, int $lore, int $jewelry, int $iridia): int
    {
        $memo = [];

        return $this->relicsDp($tech, $lore, $jewelry, $iridia, $memo);
    }

    public function computeRelicsPoints(int $player_id): int
    {
        $relicsForSets = $this->globals->get("relicsForSets:$player_id");

        $iridia = (int) $relicsForSets["iridia"];
        $jewelry = (int) $relicsForSets[1];
        $lore = (int) $relicsForSets[2];
        $tech = (int) $relicsForSets[3];

        $relicsPoints = $this->calcMaxRelicsPoints($tech, $lore, $jewelry, $iridia);

        if ($relicsPoints > 0) {
            $this->notifyAllPlayers(
                "computeRelicsPoints",
                clienttranslate('${player_name} scores ${points_log} points from relic sets'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "points" => $relicsPoints,
                    "preserve" => ["points_log", "finalScoring"],
                    "points_log" => $relicsPoints,
                    "finalScoring" => true,
                ],
            );
        }

        $this->incRoyaltyPoints($relicsPoints, $player_id, true);

        return $relicsPoints;
    }

    public function computeObjectivePoints(int $player_id): int
    {
        if ($player_id === 1) {
            return 0;
        }

        $handObjectives = $this->objective_cards->getCardsInLocation("hand", $player_id);
        $objectiveCard = reset($handObjectives);
        $objective_id = (int) $objectiveCard["type_arg"];

        $objective = new ObjectiveManager($objective_id, $this);
        $objectiveCompleted = $objective->checkCondition($player_id);

        $this->notifyAllPlayers(
            "revealObjective",
            clienttranslate('${player_name} reveals ${objective_name} as his Secret Objective'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerOrRhomNameById($player_id),
                "objective_name" => $objective->tr_name,
                "objectiveCard" => $objectiveCard,
                "i18n" => ["objective_name"],
                "preserve" => ["objectiveCard"]
            ]
        );

        if ($objectiveCompleted) {
            $this->incRoyaltyPoints($objective->points, $player_id, true);

            $this->notifyAllPlayers(
                "completeObjective",
                clienttranslate('${player_name} completes the ${objective_name} objective and scores ${points_log} points'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "objective_name" => $objective->tr_name,
                    "objectiveCard" => $objectiveCard,
                    "points" => $objective->points,
                    "i18n" => ["objective_name"],
                    "preserve" => ["objectiveCard", "points_log", "finalScoring"],
                    "points_log" => $objective->points,
                    "finalScoring" => true,
                ]
            );

            return $objective->points;
        }

        return 0;
    }

    public function calcFinalScoring(): void
    {
        $players = $this->loadPlayersNoZombie();

        $tableNames = [];
        foreach ($players as $player_id => $player) {
            $relicsForSets = [
                1 => (int) $this->getStatWithRhom("1:TypeRelics", $player_id),
                2 => (int) $this->getStatWithRhom("3:TypeRelics", $player_id),
                3 => (int) $this->getStatWithRhom("2:TypeRelics", $player_id),
                "iridia" => (int) $this->getStatWithRhom("iridia:Relics", $player_id),
            ];
            $this->globals->set("relicsForSets:$player_id", $relicsForSets);

            $objectivePoints = $this->computeObjectivePoints($player_id);
            $gemsPoints = $this->computeGemsPoints($player_id);
            $bonusTilesPoints = $this->computeTilesPoints($player_id);
            $bonusRelicsPoints = $this->computeRelicsPoints($player_id);

            $this->setStatWithRhom($gemsPoints, "gemsPoints", $player_id);
            $this->incStatWithRhom($bonusTilesPoints, "tilesPoints", $player_id);
            $this->incStatWithRhom($bonusRelicsPoints, "relicsPoints", $player_id);
            $this->setStatWithRhom($objectivePoints, "objectivePoints", $player_id);

            $tableNames[] = [
                "str" => '${player_name}',
                "args" => ["player_id" => $player_id, "player_name" => $this->getPlayerOrRhomNameById($player_id)],
                "type" => "header"
            ];

            $tilesPoints = $this->getStatWithRhom("tilesPoints", $player_id);
            $relicsPoints = $this->getStatWithRhom("relicsPoints", $player_id);
            $tokenPoints = $this->getStatWithRhom("tokenPoints", $player_id);
            $iridiaPoints = $this->getStatWithRhom("iridiaPoints", $player_id);

            $rhomPoints = 0;
            if ($this->isSolo() && $player_id === 1) {
                $rhomPoints = $this->getStatWithRhom("rhomPoints", $player_id);
            }

            $totalPoints = $gemsPoints + $tilesPoints + $relicsPoints + $objectivePoints + $tokenPoints + $iridiaPoints + $rhomPoints;

            $tableGems[] = $gemsPoints;
            $tableTiles[] = $tilesPoints;
            $tableRelics[] = $relicsPoints;
            $tableObjective[] = $objectivePoints;
            $tableToken[] = $tokenPoints;
            $tableIridia[] = $iridiaPoints;
            $tableOther[] = $rhomPoints;
            $tableTotal[] = $totalPoints;
        }

        $table = [
            [["str" => clienttranslate("From:"), "args" => [], "type" => "header"], ...$tableNames],
            [clienttranslate("Gems"), ...$tableGems],
            [clienttranslate("Tiles"), ...$tableTiles],
            [clienttranslate("Relics"), ...$tableRelics],
            [clienttranslate("Objective"), ...$tableObjective],
            [clienttranslate("Royalty Token"), ...$tableToken],
            [clienttranslate("Iridia Stone"), ...$tableIridia],
            [clienttranslate("Other"), ...$tableOther],
            [clienttranslate("Total"), ...$tableTotal],
        ];

        $this->notifyAllPlayers(
            "tableWindow",
            "",
            [
                "id" => "finalScoring",
                "title" => clienttranslate("Final scoring"),
                "table" => $table,
                "closing" => clienttranslate("Close"),
            ]
        );

        if ($this->isSolo()) {
            $rhomScore = (int) $this->getUniqueValueFromDB("SELECT score FROM robot WHERE id=1");
            $player_id = (int) $this->getActivePlayerId();
            $playerScore = $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id=$player_id");

            if ($rhomScore > $playerScore) {
                $this->DbQuery("UPDATE player SET player_score=0 WHERE player_id=$player_id");
                return;
            }

            if ($rhomScore === $playerScore) {
                $rhomScoreAux = (int) $this->getUniqueValueFromDB("SELECT score_aux FROM robot WHERE id=1");
                if ($rhomScoreAux > 0) {
                    $this->DbQuery("UPDATE player SET player_score=0 WHERE player_id=$player_id");
                }
            }
        }
    }

    /* SOLO UTILITY */
    public function pronoun(int $player_id)
    {
        return $player_id === 1 ? clienttranslate("its") : clienttranslate("his");
    }

    public function getStatWithRhom(string $statName, int $player_id = null): int
    {
        if ($this->isSolo() && $player_id === 1) {
            $rhomStats = $this->globals->get(RHOM_STATS, []);

            if (!array_key_exists($statName, $rhomStats)) {
                return 0;
            }

            return $rhomStats[$statName];
        }

        return (int) $this->getStat($statName, $player_id);
    }

    public function incStatWithRhom(int $delta, string $statName, int $player_id = null): void
    {
        if ($this->isSolo() && $player_id === 1) {
            $rhomStats = $this->globals->get(RHOM_STATS, []);

            if (!array_key_exists($statName, $rhomStats)) {
                $rhomStats[$statName] = 0;
            }

            $rhomStats[$statName] += $delta;
            $this->globals->set(RHOM_STATS, $rhomStats);
            return;
        }

        $this->incStat($delta, $statName, $player_id);
    }

    public function setStatWithRhom(int $value, string $statName, int $player_id = null): void
    {
        if ($this->isSolo() && $player_id === 1) {
            $rhomStats = $this->globals->get(RHOM_STATS, []);
            $rhomStats[$statName] = $value;
            $this->globals->set(RHOM_STATS, $rhomStats);
            return;
        }

        $this->setStat($value, $statName, $player_id);
    }

    public function getRhomDeck(bool $onlyTop = false): array
    {
        $rhomDeckTop = $this->rhom_cards->getCardOnTop("deck");
        if ($onlyTop) {
            return $this->hideCard($rhomDeckTop);
        }

        $rhomDeck = $this->rhom_cards->getCardsInLocation("deck");
        $rhomDeckTop_id = (int) $rhomDeckTop["id"];
        unset($rhomDeck[$rhomDeckTop_id]);

        return $this->hideCards($rhomDeck, false, true);
    }

    public function reshuffleRhomDeck(): void
    {
        $this->rhom_cards->moveAllCardsInLocation("discard", "deck");
        $this->rhom_cards->shuffle("deck");

        $this->notifyAllPlayers(
            "reshuffleRhomDeck",
            clienttranslate('The Rhom&apos;s deck is reshuffled'),
            [
                "rhomDeckCount" => $this->rhom_cards->countCardsInLocation("deck"),
                "rhomDeckTop" => $this->getRhomDeck(true),
            ]
        );
    }

    public function getRhomDiscard(): array
    {
        return $this->rhom_cards->getCardsInLocation("discard");
    }

    public function weathervaneDirection(): string
    {
        $rhomDeckTop = $this->rhom_cards->getCardOnTop("deck");
        return $rhomDeckTop["type"];
    }

    public function rhomDrawCard(): array
    {
        $rhomDeckCount =  $this->rhom_cards->countCardsInLocation("deck");
        $rhomCard = $this->rhom_cards->pickCardForLocation("deck", "discard", $rhomDeckCount);

        $rhomDeckTop = $this->getRhomDeck(true);
        $rhomDeckCount = $this->rhom_cards->countCardsInLocation("deck");

        $this->notifyAllPlayers(
            "rhomDrawCard",
            clienttranslate('${player_name} draws a ${card} from its deck'),
            [
                "player_id" => 1,
                "player_name" => $this->getPlayerOrRhomNameById(1),
                "card" => clienttranslate("card"),
                "rhomDeckTop" => $rhomDeckTop,
                "rhomDeckCount" => $rhomDeckCount,
                "i18n" => ["card"],
                "preserve" => ["rhomCard"],
                "rhomCard" => $rhomCard,
            ]
        );

        $rhom_id = $rhomCard["type_arg"];
        $effect = (int) $this->rhom_info[$rhom_id]["effect"];

        if ($effect === 1) {
            $position = (int) $this->rollDie("1-1", 1, "mining");
            $itemsMarket = $this->getItemsMarket();

            $itemsMarket = array_values($itemsMarket);

            $itemCard = $itemsMarket[$position - 1];
            $itemCard_id = (int) $itemCard["id"];

            $item = new ItemManager($itemCard_id, $this);
            $item->discard();

            $points = $item->cost;
            $this->incRoyaltyPoints($points, 1, true);
            $this->incStatWithRhom($points, "rhomPoints", 1);

            $this->notifyAllPlayers(
                "rhomDiscardItem",
                clienttranslate('${player_name} discards the ${item_name} and scores ${points_log} points'),
                [
                    "player_id" => 1,
                    "player_name" => $this->getPlayerOrRhomNameById(1),
                    "item_name" => $item->tr_name,
                    "points_log" => $points,
                    "points" => $points,
                    "i18n" => ["item_name"],
                    "preserve" => ["item_id"],
                    "item_id" => $item->id,
                ]
            );

            $this->replaceItem();
        }

        if ($effect === 2 || $effect === 3) {
            $this->globals->set(RHOM_MULTIPLIER, $effect);
        }

        if ($effect === 4) {
            $this->rhomBarricade();
        }

        return $rhomCard;
    }

    public function getBarricadeTiles(): array
    {
        $barricadeTiles = $this->tile_cards->getCardsInLocation("barricade");

        $revealedTiles = $this->globals->get(REVEALED_TILES);
        foreach ($barricadeTiles as $tileCard_id => $tileCard) {
            if (!array_key_exists($tileCard_id, $revealedTiles)) {
                $barricadeTiles[$tileCard_id] = $this->hideCard($tileCard);
            }
        }

        return $barricadeTiles;
    }

    public function barricadeHexes(int $row): ?array
    {
        $hexesInRow = $this->rows_info[$row];
        $s_hexesInRow = implode(",", $hexesInRow);

        $isEmpty = !$this->getCollectionFromDB("SELECT card_id FROM tile WHERE card_location='board' AND card_location_arg IN ($s_hexesInRow)");

        if ($isEmpty) {
            if ($row === 8) {
                return null;
            }

            $row++;
            return $this->barricadeHexes($row);
        }

        $weathervaneDirection = $this->weathervaneDirection();
        if ($weathervaneDirection === "right") {
            array_reverse($hexesInRow);
        }

        return $hexesInRow;
    }

    public function rhomBarricade(): void
    {
        $player_id = (int) $this->getActivePlayerId();
        $explorerCard = $this->getExplorerByPlayerId($player_id);

        $hex = (int) $explorerCard["location_arg"];

        if ($explorerCard["location"] === "scene") {
            $row = 1;
        } else {
            $row = $this->hexes_info[$hex]["row"] + 1;
        }

        if ($row === 9) {
            return;
        }

        $hexesInRow = $this->barricadeHexes($row);

        if ($hexesInRow === null) {
            return;
        }

        $shift = (int) $this->rollDie("1-1", 1, "mining") - 1;
        $this->placeBarricade($shift, $hexesInRow);
    }

    public function placeBarricade(int $shift, array $hexesInRow): void
    {
        if (!array_key_exists($shift, $hexesInRow)) {
            $shift = 0;
        }

        $hex = $hexesInRow[$shift];
        $tileCard = $this->getObjectFromDB("$this->deckSelectQuery from tile WHERE card_location='board' AND card_location_arg=$hex");

        if ($tileCard === null) {
            $shift++;
            $this->placeBarricade($shift, $hexesInRow);
            return;
        }

        $tileCard_id = (int) $tileCard["id"];
        $this->tile_cards->moveCard($tileCard_id, "barricade", $hex);

        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $revealedTiles = $this->globals->get(REVEALED_TILES);
        if (!array_key_exists($tileCard_id, $revealedTiles)) {
            $tileCard = $this->hideCard($tileCard);
        }

        $this->notifyAllPlayers(
            "rhomBarricade",
            clienttranslate('${player_name} places a barricade (hex ${log_hex})'),
            [
                "player_id" => 1,
                "player_name" => $this->getPlayerOrRhomNameById(1),
                "log_hex" => $hex,
                "hex" => $hex,
                "tileCard" => $tileCard,
            ]
        );
    }

    public function gemsDemand(): array
    {
        $gemsDemand = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0
        ];

        $relicsMarket = $this->getRelicsMarket();
        foreach ($relicsMarket as $relicCard) {
            $relic_id = (int) $relicCard["type_arg"];
            $cost = $this->relics_info[$relic_id]["cost"];

            foreach ($cost as $gem_id => $demand) {
                $gemsDemand[$gem_id] += $demand;
            }
        }

        arsort($gemsDemand);
        return $gemsDemand;
    }

    public function rhomDiscardGems(int $newGem): void
    {
        $excessGems = $this->getTotalGemsCount(1) - 7;
        $gemsCounts = $this->getGemsCounts(1, true);

        $hasGem = $gemsCounts[$newGem] > $excessGems;

        if ($hasGem) {
            $marketValue = $this->getMarketValues($newGem);
            $points = $marketValue * $excessGems;

            $this->discardGems(1, null, $newGem, $excessGems);
            $this->incRoyaltyPoints($points, 1, true);
            $this->incStatWithRhom($points, "rhomPoints", 1);

            $this->notifyAllPlayers(
                "message",
                clienttranslate('${player_name} scores ${points_log} point(s) from the discarded gem(s)'),
                [
                    "player_id" => 1,
                    "player_name" => $this->getPlayerOrRhomNameById(1),
                    "preserve" => ["points_log"],
                    "points_log" => $points,
                ]
            );

            return;
        }

        arsort($gemsCounts);

        foreach ($gemsCounts as $gem_id => $gemCount) {
            if ($gemCount === 0 || $gem_id === $newGem) {
                continue;
            }

            $discardedCount = $excessGems - $gemCount;

            if ($discardedCount <= 0) {
                $discardedCount = $excessGems;
            }

            $marketValue = $this->getMarketValues($gem_id);
            $points = $marketValue * $discardedCount;

            $this->discardGems(1, null, $gem_id, $discardedCount);
            $this->incRoyaltyPoints($points, 1);
            $this->incStatWithRhom($points, "gemsPoints", 1);

            $excessGems -= $discardedCount;

            if ($excessGems === 0) {
                break;
            }
        }
    }

    public function compareHexesByWeathervane(int $hex, int $otherHex): int
    {
        $column = $this->hexes_info[$hex]["column"];
        $otherColumn = $this->hexes_info[$otherHex]["column"];

        $weathervaneDirection = $this->weathervaneDirection();

        if ($weathervaneDirection === "left") {
            return $column <=> $otherColumn;
        }

        return $otherColumn <=> $column;
    }

    public function mostDemandingTiles(array $tileCards): array
    {
        $includesIridia = false;
        $gemsDemand = $this->gemsDemand();

        $gemsCounts = $this->getGemsCounts(1, true);
        $hasAllGems = true;
        $onlyRainbow = true;

        foreach ($tileCards as $tileCard) {
            $tile_id = (int) $tileCard["type_arg"];
            $gem_id = (int) $this->tiles_info[$tile_id]["gem"];

            if ($gem_id === 10) {
                $includesIridia = true;
            }

            if ($gem_id % 10 !== 0 && $gemsCounts[$gem_id] === 0) {
                $hasAllGems = false;
            }

            if ($gem_id !== 0) {
                $onlyRainbow = false;
            }
        }

        $mostDemandingTiles = array_filter($tileCards, function ($tileCard) use ($onlyRainbow, $includesIridia, $gemsCounts, $hasAllGems) {
            $tile_id = (int) $tileCard["type_arg"];
            $gem_id = (int) $this->tiles_info[$tile_id]["gem"];

            if ($includesIridia) {
                return $gem_id === 10;
            }

            if (!$onlyRainbow && $gem_id === 0) {
                return false;
            }

            if ($hasAllGems) {
                return true;
            }

            return $gemsCounts[$gem_id] === 0;
        });

        if (count($mostDemandingTiles) > 1) {
            uasort($mostDemandingTiles, function ($tileCard, $otherTileCard) use ($onlyRainbow, $gemsDemand) {
                $tile_id = (int) $tileCard["type_arg"];
                $gem_id = (int) $this->tiles_info[$tile_id]["gem"];

                if (!$onlyRainbow) {
                    $demand = $gemsDemand[$gem_id];

                    $otherTile_id = (int) $otherTileCard["type_arg"];
                    $otherGem_id = (int) $this->tiles_info[$otherTile_id]["gem"];
                    $otherDemand = $gemsDemand[$otherGem_id];

                    if ($demand !== $otherDemand) {
                        return $otherDemand <=> $demand;
                    }
                }

                $hex = (int) $tileCard["location_arg"];
                $otherHex = (int) $otherTileCard["location_arg"];

                return $this->compareHexesByWeathervane($hex, $otherHex);
            });
        }

        return $mostDemandingTiles;
    }

    public function rhomRevealableTiles(): array
    {
        $revealableTiles = [];

        $revealedTiles = $this->globals->get(REVEALED_TILES);
        $adjacentTiles = $this->adjacentTiles(1, null, false, true, true);

        foreach ($adjacentTiles as $direction => $tileCard) {
            if ($tileCard === null) {
                continue;
            }

            $tileCard_id = (int) $tileCard["id"];

            if (!array_key_exists($tileCard_id, $revealedTiles)) {
                $revealableTiles[$direction] = $tileCard;
            }
        }

        return $revealableTiles;
    }

    public function rhomRevealTiles(): bool
    {
        $revealableTiles = $this->rhomRevealableTiles();
        $explorableTiles = $this->explorableTiles(1);

        if (!$revealableTiles && !$explorableTiles) {
            $this->gamestate->nextState("discardTileForRhom");
            return false;
        }

        $rhomCard = $this->rhomDrawCard();
        $rhom_id = (int) $rhomCard["type_arg"];

        $directions = $this->rhom_info[$rhom_id]["directions"];
        asort($directions);

        $revealsLimit = 0;
        foreach ($directions as $direction => $order) {
            if (!array_key_exists($direction, $revealableTiles)) {
                continue;
            }

            $tileCard = $revealableTiles[$direction];
            $tileCard_id = (int) $tileCard["id"];

            $this->revealTile($tileCard_id, 1);
            $revealsLimit++;

            if ($revealsLimit === 2) {
                break;
            }
        }

        return true;
    }

    public function rhomResolveTileEffect(array $tileCard): void
    {
        $tile_id = (int) $tileCard["type_arg"];
        $region_id = (int) $tileCard["type"];

        $tile_info = $this->tiles_info[$tile_id];
        $gem_id = $tile_info["gem"];
        $tileEffect_id = $tile_info["effect"];

        $rainbowGem = $this->globals->get(RAINBOW_GEM);

        if ($gem_id % 10 === 0) {
            if ($rainbowGem) {
                $gem_id = $rainbowGem;
            } else {
                if ($gem_id === 10) {
                    $this->obtainIridiaStone(1);
                }

                $gemsDemand = $this->gemsDemand();

                $maxDemand = max($gemsDemand);
                $mostDemandingGems = [];

                foreach ($gemsDemand as $gem_id => $demand) {
                    if ($demand === $maxDemand) {
                        $gemName = $this->gems_info[$gem_id]["name"];
                        $mostDemandingGems[$gemName] = $gem_id;
                    }
                }

                if (count($mostDemandingGems) === 1) {
                    $gem_id = reset($mostDemandingGems);
                } else {
                    $this->globals->set(RHOM_POSSIBLE_RAINBOW, $mostDemandingGems);
                    $this->gamestate->nextState("pickRainbowForRhom");
                    return;
                }
            }
        }

        $rhomMultiplier = $this->globals->get(RHOM_MULTIPLIER, 1);
        $fullCargo = !$this->incGem($rhomMultiplier, $gem_id, 1, $tileCard);

        if ($fullCargo) {
            $this->rhomDiscardGems($gem_id);
        }

        if ($tileEffect_id) {
            $tileEffect = $this->tileEffects_info[$tileEffect_id];
            $points = $tileEffect["values"][$region_id];

            if ($tileEffect_id === 3) {
                $publicStoneDiceCount = $this->getPublicStoneDice();
                $player_id = (int) $this->getActivePlayerId();

                if ($publicStoneDiceCount === 0) {
                    $this->notifyAllPlayers(
                        "message",
                        clienttranslate('${player_name2} does not roll a die because all Stone dice are with ${player_name}'),
                        [
                            "player_id" => $player_id,
                            "player_name" => $this->getPlayerOrRhomNameById($player_id),
                            "player_id2" => 1,
                            "player_name2" => $this->getPlayerOrRhomNameById(1),
                        ]
                    );
                    return;
                }
                $points = $this->rollDie("1-1", 1, "mining");
            }

            $this->incRoyaltyPoints($points, 1);
            $this->incStatWithRhom($points, "tilesPoints", 1);
        }
    }

    public function rhomMoveToEmptyHex(): void
    {
        $emptyHexes = $this->emptyHexes(1);
        usort($emptyHexes, function (int $hex, int $otherHex) {
            return $this->compareHexesByWeathervane($hex, $otherHex);
        });

        $emptyHex = reset($emptyHexes);
        $this->moveToEmptyHex($emptyHex, 1);
    }

    public function rhomRestoreRelic(): void
    {
        $restorableRelics = $this->restorableRelics(1, true);

        if (!$restorableRelics) {
            return;
        }

        $weathervaneDirection = $this->weathervaneDirection();

        if ($weathervaneDirection === "right") {
            $restorableRelics = array_reverse($restorableRelics, true);
        }

        uasort($restorableRelics, function ($relicCard, $otherRelicCard) {
            $relic_id = (int) $relicCard["type_arg"];
            $points = (int) $this->relics_info[$relic_id]["points"];

            $otherRelic_id = (int) $otherRelicCard["type_arg"];
            $otherPoints = (int) $this->relics_info[$otherRelic_id]["points"];

            return $otherPoints <=> $points;
        });

        $relicCard = reset($restorableRelics);
        $relicCard_id = (int) $relicCard["id"];

        $this->restoreRelic($relicCard_id, 1);
        $this->rhomRestoreRelic();
    }

    /*  DEBUG */

    public function debug_calcFinalScoring(): void
    {
        $this->calcFinalScoring();
    }

    public function debug_rollDie(int $player_id): void
    {
        $this->rollDie(1, $player_id, "stone");
    }

    public function debug_obtainStoneDie(int $player_id): void
    {
        $this->obtainStoneDie($player_id);
    }

    public function debug_resetStoneDice(int $player_id): void
    {
        $this->resetStoneDice($player_id);
    }

    public function debug_stat(int $player_id): void
    {
        $stat = $this->getStatWithRhom("miningAttempts", $player_id);
        throw new \BgaUserException($stat);
    }

    public function debug_incGem(int $delta, int $gem_id, int $player_id): void
    {
        $this->incGem($delta, $gem_id, $player_id);
    }

    public function debug_fillCargo(int $player_id): void
    {
        $totalGemsCount = $this->getTotalGemsCount($player_id);
        $this->incGem(7 - $totalGemsCount, 2, $player_id);
    }

    public function debug_overflowCargo(int $player_id): void
    {
        $this->incGem(4, 4, $player_id);
        $this->incGem(4, 3, $player_id);

        $anchorState_id = (int) $this->gamestate->state_id();
        $this->globals->set(ANCHOR_STATE, $anchorState_id);
        $this->gamestate->jumpToState(ST_TRANSFER_GEM);
    }

    public function debug_setMarketValue(int $value, int $gem_id): void
    {
        $gemName = $this->gems_info[$gem_id]["name"];
        $this->globals->set("$gemName:MarketValue", $value);
    }

    public function debug_moveExplorer(int $hex, int $player_id): void
    {
        $this->DbQuery("UPDATE explorer SET card_location='board', card_location_arg=$hex WHERE card_location<>'box'AND card_type_arg=$player_id");
    }

    public function debug_removeTiles(): void
    {
        $this->DbQuery("UPDATE tile SET card_location='box', card_location_arg=0 
        WHERE card_location='board' AND card_location_arg IN (47, 48, 49, 50, 51)");
    }

    public function debug_revealTiles(): void
    {
        $tileCards =  $this->getCollectionFromDB("$this->deckSelectQuery from tile WHERE card_type=5");
        $this->globals->set(REVEALED_TILES, $tileCards);
    }

    public function debug_reshuffleRelicsDeck(): void
    {
        $this->relic_cards->moveAllCardsInLocation(null, "deck");
        $this->relic_cards->shuffle("deck");
        $this->relic_cards->pickCardsForLocation(5, "deck", "market");
    }

    public function debug_giveItem(int $item_id, int $player_id): void
    {
        $this->DbQuery("UPDATE item SET card_location='hand', card_location_arg=$player_id WHERE card_location='deck' AND card_type_arg=$item_id LIMIT 1");
    }

    public function debug_reshuffleItemsDeck(): void
    {
        $this->reshuffleItemsDeck();
    }

    public function debug_giveObjective(int $objective_id, int $player_id): void
    {
        $this->objective_cards->moveAllCardsInLocation("hand", "discard", $player_id);
        $this->DbQuery("UPDATE objective SET card_location='hand', card_location_arg=$player_id WHERE card_type_arg=$objective_id LIMIT 1");
    }

    public function debug_zombieQuit(int $player_id): void
    {
        $this->explorer_cards->moveAllCardsInLocation("board", "scene", null, $player_id);
        $this->gem_cards->moveAllCardsInLocation("hand", "discard", $player_id);
        $this->tile_cards->moveAllCardsInLocation("hand", "discard", $player_id);
        $this->objective_cards->moveAllCardsInLocation("hand", "discard", $player_id);

        $this->relic_cards->moveAllCardsInLocation("hand", "discard", $player_id);
        $this->relic_cards->moveAllCardsInLocation("book", "discard", $player_id);

        $this->item_cards->moveAllCardsInLocation("hand", "discard", $player_id);
        $this->item_cards->moveAllCardsInLocation("book", "discard", $player_id);
        $this->globals->set(EPIC_ELIXIR, false);

        $stoneDice = $this->globals->get(PLAYER_STONE_DICE);
        $stoneDice[$player_id] = [];
        $this->globals->set(PLAYER_STONE_DICE, $stoneDice);
        $this->globals->set(ACTIVE_STONE_DICE, []);

        $this->DbQuery("UPDATE player SET coin=0 WHERE player_id=$player_id");

        $zombies = $this->globals->get("zombies", []);

        if (!in_array($player_id, $zombies)) {
            $zombies[$player_id] = $player_id;
            $this->globals->set("zombies", $zombies);

            $this->notifyAllPlayers(
                "zombieQuit",
                clienttranslate('${player_name} quits the game. All his dice, tiles, gems, relics and items are discarded'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerOrRhomNameById($player_id),
                    "explorerCard" => $this->getExplorerByPlayerId($player_id),
                ]
            );
        }

        if ($this->getActivePlayerId() == $player_id) {
            $this->gamestate->jumpToState(6);
        }
    }

    public function debug_reshuffleRhomDeck(): void
    {
        $this->reshuffleRhomDeck();
    }

    public function debug_rhomBarricade(): void
    {
        $this->rhomBarricade();
    }

    public function debug_barricadeTile(int $hex): void
    {
        $this->DbQuery("UPDATE tile SET card_location='barricade' WHERE card_location='board' AND card_location_arg=$hex");
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version)
    {
        if ($from_version <= 2412041803) {
            $scepterColumn = $this->getUniqueValueFromDB("SHOW COLUMNS FROM player LIKE 'scepter'");
            if (empty($scepterColumn)) {
                $this->applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_player ADD scepter TINYINT UNSIGNED NOT NULL DEFAULT 0");
            }
        }

        if ($from_version <= 2501091302) {
            $stoneDieColumn =  $this->getUniqueValueFromDB("SHOW COLUMNS FROM player LIKE 'stone_die'");
            if (empty($stoneDieColumn)) {
                return;
            }

            $players = $this->loadPlayersBasicInfos();
            $playersDice = [];
            $dice = [1, 2, 3, 4];
            foreach ($players as $player_id => $player) {
                $playerDiceCount = (int) $this->getUniqueValueFromDB("SELECT stone_die FROM player WHERE player_id=$player_id");
                $playerDice = array_slice($dice, 0, $playerDiceCount);
                $playersDice[$player_id] = $playerDice;
            }
            $this->globals->set(PLAYER_STONE_DICE, $playersDice);
            $this->globals->set(ACTIVE_STONE_DICE, []);
        }

        if ($from_version <= 2501132214) {
            if ($this->isSolo()) {
                $rhomScepterColumn = $this->getUniqueValueFromDB("SHOW COLUMNS FROM robot LIKE 'scepter'");
                if (empty($rhomScepterColumn)) {
                    $this->applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_robot ADD scepter TINYINT UNSIGNED NOT NULL DEFAULT 0");
                    $this->applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_robot ADD banner TINYINT UNSIGNED NOT NULL DEFAULT 0");
                    $this->applyDbUpgradeToAllDB("ALTER TABLE DBPREFIX_robot ADD throne TINYINT UNSIGNED NOT NULL DEFAULT 0");
                }
            }
        }
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas()
    {
        $result = [];
        $result["version"] = (int) $this->gamestate->table_globals[300];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.

        $result["players"] = $this->getCollectionFromDb("SELECT player_id, player_score score FROM player");
        $playersNoZombie = $this->loadPlayersNoZombie();
        $result["playersNoZombie"] = $playersNoZombie;
        $result["tilesBoard"] = $this->getTilesBoard();
        $result["playerBoards"] = $this->globals->get(PLAYER_BOARDS);
        $result["tilesInfo"] = $this->tiles_info;
        $result["revealedTiles"] = $this->globals->get(REVEALED_TILES, []);
        $result["collectedTiles"] = $this->getCollectedTiles(null);
        $result["iridiaStoneOwner"] = $this->getIridiaStoneOwner();
        $result["royaltyTokens"] = $this->getRoyaltyTokens(null);
        $result["explorers"] = $this->getExplorers();
        $result["coins"] = $this->getCoins(null);
        $result["gems"] = $this->getGems(null);
        $result["gemsCounts"] = $this->getGemsCounts(null);
        $result["marketValues"] = $this->getMarketValues(null);
        $result["publicStoneDice"] = $this->getPublicStoneDice();
        $result["playerStoneDice"] = $this->globals->get(PLAYER_STONE_DICE);
        $result["activeStoneDice"] = $this->globals->get(ACTIVE_STONE_DICE, []);
        $result["rolledDice"] = $this->globals->get(ROLLED_DICE, []);
        $result["relicsInfo"] = $this->relics_info;
        $result["relicsDeck"] = $this->getRelicsDeck();
        $result["relicsDeckTop"] = $this->getRelicsDeck(true);
        $result["relicsMarket"] = $this->getRelicsMarket();
        $result["restoredRelics"] = $this->getRestoredRelics(null);
        $result["itemsInfo"] = $this->items_info;
        $result["itemsDeck"] = $this->getItemsDeck();
        $result["itemsMarket"] = $this->getItemsMarket();
        $result["boughtItems"] = $this->getBoughtItems(null);
        $result["activeItems"] = $this->getActiveItems();
        $result["itemsDiscard"] = $this->getItemsDiscard();
        $result["objectivesInfo"] = $this->objectives_info;
        $result["objectives"] = $this->getObjectives($current_player_id);
        $result["books"] = $this->getBooks();

        $isSolo = $this->isSolo();
        $result["isSolo"] = $isSolo;

        if ($isSolo) {
            $result["realPlayer"] = array_shift($playersNoZombie);
            $result["bot"] = $this->getObjectFromDB("SELECT * FROM robot WHERE id=1");
            $result["rhomDeck"] = $this->getRhomDeck();
            $result["rhomDeckTop"] = $this->getRhomDeck(true);
            $result["rhomDiscard"] = $this->getRhomDiscard();
            $result["barricadeTiles"] = $this->getBarricadeTiles();
        }

        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName()
    {
        return "gemsofiridescia";
    }

    protected function setupNewGame($players, $options = [])
    {
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $colors = $gameinfos["player_colors"];
        if (count($players) === 1) {
            $this->DbQuery("INSERT INTO robot () VALUES ()");
        }

        $this->reattributeColorsBasedOnPreferences($players, $colors);
        $this->reloadPlayersBasicInfos();

        $players = $this->loadPlayersBasicInfos();

        foreach ($players as $player_id => $player) {
            for ($face = 1; $face <= 6; $face++) {
                $this->initStat("player", "$face:Rolled", 0, $player_id);
            }

            $this->initStat("player", "coinsObtained", 0, $player_id);
            $this->initStat("player", "miningAttempts", 0, $player_id);
            $this->initStat("player", "failedMiningAttempts", 0, $player_id);
            $this->initStat("player", "itemsPurchased", 0, $player_id);

            $this->initStat("player", "gemsPoints", 0, $player_id);
            $this->initStat("player", "tilesPoints", 0, $player_id);
            $this->initStat("player", "relicsPoints", 0, $player_id);
            $this->initStat("player", "objectivePoints", 0, $player_id);
            $this->initStat("player", "tokenPoints", 0, $player_id);
            $this->initStat("player", "iridiaPoints", 0, $player_id);

            $this->initStat("player", "tilesCollected", 0, $player_id);
            $this->initStat("player", "1:GemTiles", 0, $player_id);
            $this->initStat("player", "2:GemTiles", 0, $player_id);
            $this->initStat("player", "3:GemTiles", 0, $player_id);
            $this->initStat("player", "4:GemTiles", 0, $player_id);
            $this->initStat("player", "rainbow:Tiles", 0, $player_id);

            $this->initStat("player", "1:GemRelics", 0, $player_id);
            $this->initStat("player", "2:GemRelics", 0, $player_id);
            $this->initStat("player", "3:GemRelics", 0, $player_id);
            $this->initStat("player", "4:GemRelics", 0, $player_id);

            $this->initStat("player", "1:TypeRelics", 0, $player_id);
            $this->initStat("player", "2:TypeRelics", 0, $player_id);
            $this->initStat("player", "3:TypeRelics", 0, $player_id);
            $this->initStat("player", "iridia:Relics", 0, $player_id);
        }

        $isSolo = count($players) === 1;
        $bot_color = $this->getUniqueValueFromDB("SELECT color FROM robot WHERE id=1");

        $explorerCards = [];
        foreach ($this->explorers_info as $explorer_id => $explorer) {
            $explorerCards[] = ["type" => $explorer["color"], "type_arg" => $explorer_id, "nbr" => 1];
        }

        if ($isSolo) {
            $explorerCards[] = ["type" => $bot_color, "type_arg" => 0, "nbr" => 1];
        }

        $this->explorer_cards->createCards($explorerCards, "deck");

        $explorerCards = $this->explorer_cards->getCardsInLocation("deck");
        $playerBoards = [];

        foreach ($players as $player_id => $player) {
            foreach ($explorerCards as $explorerCard_id => $explorerCard) {
                $player_color = $this->getPlayerColorById($player_id);

                if ($player_color === $explorerCard["type"]) {
                    $playerBoards[$player_id] = (int) $explorerCard["type_arg"];

                    $this->explorer_cards->moveCard($explorerCard_id, "scene", $player_id);
                    $this->DbQuery("UPDATE explorer SET card_type_arg=$player_id WHERE card_id=$explorerCard_id");
                }
            }
        }

        if (count($players) === 1) {
            $explorerCard = $this->getObjectFromDB("$this->deckSelectQuery FROM explorer WHERE card_type='$bot_color'");
            $playerBoards[1] = 4;

            $explorerCard_id = (int) $explorerCard["id"];
            $this->explorer_cards->moveCard($explorerCard_id, "scene", 1);
            $this->DbQuery("UPDATE explorer SET card_type_arg=1 WHERE card_id=$explorerCard_id");

            $rhomCards = [];
            foreach ($this->rhom_info as $rhom_id => $rhom_info) {
                $direction = $rhom_info["weathervane"];
                $rhomCards[] = ["type" => $direction, "type_arg" => $rhom_id, "nbr" => 1];
            }

            $this->rhom_cards->createCards($rhomCards, "deck");
            $this->rhom_cards->shuffle("deck");
        }

        $this->globals->set(PLAYER_BOARDS, $playerBoards);
        $this->explorer_cards->moveAllCardsInLocation("deck", "box");

        $tileCards = [];
        foreach ($this->tiles_info as $tile_id => $tile_info) {
            $region_id = $tile_info["region"];

            $tileCards[] = ["type" => $region_id, "type_arg" => $tile_id, "nbr" => 1];
        }

        $this->tile_cards->createCards($tileCards, "deck");

        $hex = 1;
        foreach ($this->regions_info as $region_id => $region) {
            $tilesOfregion = $this->tile_cards->getCardsOfTypeInLocation($region_id, null, "deck");
            $k_tilesOfregion = array_keys($tilesOfregion);

            $temporaryLocation = (string) $region["name"];
            $this->tile_cards->moveCards($k_tilesOfregion, $temporaryLocation);
            $this->tile_cards->shuffle($temporaryLocation);

            for ($i = 1; $i <= count($tilesOfregion); $i++) {
                $this->tile_cards->pickCardForLocation($temporaryLocation, "board", $hex);
                $hex++;
            }
        }

        if (count($players) <= 2) {
            $this->DbQuery("UPDATE tile SET card_location='box', card_location_arg=0 
            WHERE card_location='board' AND 
            card_location_arg IN (1, 6, 7, 13, 14, 19, 20, 26, 27, 32, 33, 39, 40, 45, 46, 52)");
        }

        $gemCards = [];
        foreach ($this->gems_info as $gem_id => $gem_info) {
            $gemName = $gem_info["name"];
            $gemCards[] = ["type" => $gemName, "type_arg" => $gem_id, "nbr" => count($players) * 7 + 8];
        }
        $this->gem_cards->createCards($gemCards, "deck");

        foreach ($this->gems_info as $gem_id => $gem_info) {
            $gemName = $gem_info["name"];
            $this->DbQuery("UPDATE gem SET card_location='$gemName' WHERE card_type_arg=$gem_id");
            $this->gem_cards->shuffle($gemName);
        }

        $relicCards = [];
        foreach ($this->relics_info as $relic_id => $relic_info) {
            $relicCards[] = ["type" => $relic_info["leadGem"], "type_arg" => $relic_id, "nbr" => 1];
        }
        $this->relic_cards->createCards($relicCards, "deck");
        $this->relic_cards->shuffle("deck");
        $this->relic_cards->pickCardsForLocation(5, "deck", "market");

        $objectiveCards = [];
        foreach ($this->objectives_info as $objective_id => $objective_info) {
            $objectiveCards[] = ["type" => $objective_info["points"], "type_arg" => $objective_id, "nbr" => 1];
        }
        $this->objective_cards->createCards($objectiveCards, "deck");
        $this->objective_cards->shuffle("deck");

        foreach ($players as $player_id => $player) {
            $this->objective_cards->pickCardsForLocation(2, "deck", "hand", $player_id);
        }

        $itemCards = [];
        foreach ($this->items_info as $item_id => $item_info) {
            if (count($players) <= 2 && ($item_id === 10 || $item_id === 11)) {
                continue;
            }

            $itemCards[] = ["type" => $item_info["cost"], "type_arg" => $item_id, "nbr" => 3];
        }
        $this->item_cards->createCards($itemCards, "deck");

        if (count($players) === 4 && $this->getGameStateValue("startingCatapult") == 1) {
            $first_player_id = (int) $this->getNextPlayerTable()[0];
            $last_player_id = $this->getPlayerBefore($first_player_id);

            $this->DbQuery("UPDATE item SET card_location='hand', card_location_arg=$last_player_id WHERE card_location='deck' AND card_type_arg=$item_id LIMIT 1");
        }

        $itemsMarketCount = count($players) === 1 ? 6 : 5;
        $this->item_cards->shuffle("deck");
        $this->item_cards->pickCardsForLocation($itemsMarketCount, "deck", "market");

        if (
            !$this->checkItemsMarket()
        ) {
            $this->reshuffleItemsDeck(true, $players);
        };

        $stoneDice = [];
        foreach ($players as $player_id => $player) {
            $stoneDice[$player_id] = [];
        }

        $this->globals->set(PLAYER_STONE_DICE, $stoneDice);
        $this->globals->set(ACTIVE_STONE_DICE, []);
        $this->globals->set(REVEALS_LIMIT, 0);

        foreach ($this->gems_info as $gem_info) {
            $gemName = $gem_info["name"];
            $marketValueCode = "$gemName:MarketValue";

            $this->globals->set($marketValueCode, 2);
        }
        $this->activeNextPlayer();
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $player_id): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            $this->explorer_cards->moveAllCardsInLocation("board", "scene", null, $player_id);
            $this->gem_cards->moveAllCardsInLocation("hand", "discard", $player_id);
            $this->tile_cards->moveAllCardsInLocation("hand", "discard", $player_id);
            $this->objective_cards->moveAllCardsInLocation("hand", "discard", $player_id);

            $this->relic_cards->moveAllCardsInLocation("hand", "discard", $player_id);
            $this->relic_cards->moveAllCardsInLocation("book", "discard", $player_id);

            $this->item_cards->moveAllCardsInLocation("hand", "discard", $player_id);
            $this->item_cards->moveAllCardsInLocation("book", "discard", $player_id);
            $this->globals->set(EPIC_ELIXIR, false);

            $this->DbQuery("UPDATE player SET coin=0 WHERE player_id=$player_id");

            $zombies = $this->globals->get("zombies", []);

            if (!in_array($player_id, $zombies)) {
                $zombies[$player_id] = $player_id;
                $this->globals->set("zombies", $zombies);

                $this->notifyAllPlayers(
                    "zombieQuit",
                    "",
                    [
                        "player_id" => $player_id,
                        "player_name" => $this->getPlayerOrRhomNameById($player_id),
                        "explorerCard" => $this->getExplorerByPlayerId($player_id),
                    ]
                );
            }

            $this->gamestate->jumpToState(6);
            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($player_id, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }

    public function loadBugReportSQL(int $reportId, array $studioPlayers): void
    {
        $prodPlayers = $this->getObjectListFromDb("SELECT `player_id` FROM `player`", true);
        $prodCount = count($prodPlayers);
        $studioCount = count($studioPlayers);
        if ($prodCount != $studioCount) {
            throw new \BgaVisibleSystemException("Incorrect player count (bug report has $prodCount players, studio table has $studioCount players)");
        }

        // SQL specific to your game
        // For example, reset the current state if it's already game over
        $sql = [
            "UPDATE `global` SET `global_value` = 10 WHERE `global_id` = 1 AND `global_value` = 99"
        ];
        foreach ($prodPlayers as $index => $prodId) {
            $studioId = $studioPlayers[$index];
            // SQL common to all games
            $sql[] = "UPDATE `player` SET `player_id` = $studioId WHERE `player_id` = $prodId";
            $sql[] = "UPDATE `global` SET `global_value` = $studioId WHERE `global_value` = $prodId";
            $sql[] = "UPDATE `stats` SET `stats_player_id` = $studioId WHERE `stats_player_id` = $prodId";

            // SQL specific to your game
            $sql[] = "UPDATE `tile` SET `card_location_arg` = $studioId WHERE `card_location_arg` = $prodId";
            $sql[] = "UPDATE `item` SET `card_location_arg` = $studioId WHERE `card_location_arg` = $prodId";
            $sql[] = "UPDATE `relic` SET `card_location_arg` = $studioId WHERE `card_location_arg` = $prodId";
            $sql[] = "UPDATE `gem` SET `card_location_arg` = $studioId WHERE `card_location_arg` = $prodId";
            $sql[] = "UPDATE `explorer` SET `card_type_arg` = $studioId WHERE `card_type_arg` = $prodId";
            // $sql[] = "UPDATE `my_table` SET `my_column` = REPLACE(`my_column`, $prodId, $studioId)";
        }
        foreach ($sql as $q) {
            $this->DbQuery($q);
        }
        $this->reloadPlayersBasicInfos();
    }
}
