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
use Bga\GameFramework\Actions\Types\JsonParam;
use BgaUserException;
use BgaVisibleSystemException;

const PLAYER_BOARDS = "playerBoards";
const REVEALS_LIMIT = "revealsLimit";
const HAS_MOVED_EXPLORER = "hasMovedExplorer";
const HAS_SOLD_GEMS = "hasSoldGems";
const REVEALED_TILES = "revealedTiles";
const RAINBOW_GEM = "activeGem";
const ACTIVE_STONE_DICE_COUNT = "activeStoneDice";
const PUBLIC_STONE_DICE_COUNT = "publicStoneDiceCount";
const ANCHOR_STATE = "anchorState";
const HAS_EXPANDED_TILES = "hasExpandedTiles";
const CURRENT_TILE = "currentTile";

class Game extends \Table
{
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([]);

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

        $this->rhom_cards = $this->getNew("module.common.deck");
        $this->rhom_cards->init("rhom");

        $this->barrier_cards = $this->getNew("module.common.deck");
        $this->barrier_cards->init("barrier");

        $this->deckSelectQuery = "SELECT card_id id, card_type type, card_type_arg type_arg, 
        card_location location, card_location_arg location_arg ";
    }

    /**
     * Player action, example content.
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action defined into `gemsofiridescia.action.php`.
     *
     * @throws BgaSystemException
     * @see action_gemsofiridescia::actMyAction
     */

    public function actRevealTile(#[IntParam(min: 1, max: 58)] int $tileCard_id): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $revealableTiles = $this->revealableTiles($player_id, true);

        if (!$revealableTiles) {
            $revealableTiles = $this->expandedRevealableTiles($player_id, true);
        }

        if (!array_key_exists($tileCard_id, $revealableTiles)) {
            throw new \BgaVisibleSystemException("You can't reveal this tile now: actRevealTile, $tileCard_id");
        }

        $revealedTiles = $this->globals->get(REVEALED_TILES, []);
        $revealedTiles[$tileCard_id] = $tileCard;
        $this->globals->set(REVEALED_TILES, $revealedTiles);

        $this->notifyAllPlayers(
            "revealTile",
            clienttranslate('${player_name} reveals a ${tile} (hex ${hex})'),
            [
                "i18n" => ["region_label"],
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "hex" => $tileCard["location_arg"],
                "tileCard" => $tileCard,
                "preserve" => ["tileCard"],
                "i18n" => ["tile"],
                "tile" => clienttranslate("tile"),
            ]
        );

        $this->globals->inc(REVEALS_LIMIT, 1);

        $this->gamestate->nextState("repeat");
    }

    public function actSkipRevealTile()
    {
        $player_id = (int) $this->getActivePlayerId();

        $explorableTiles = $this->explorableTiles($player_id);
        if (!$explorableTiles) {
            throw new \BgaVisibleSystemException("You must reveal a tile now: actSkipRevealTile");
        }

        $this->gamestate->nextState("skip");
    }

    public function actUndoSkipRevealTile()
    {

        $player_id = (int) $this->getActivePlayerId();

        $revealsLimit = (int) $this->globals->get(REVEALS_LIMIT);
        $revealableTiles = $this->revealableTiles($player_id);

        if ($revealsLimit === 2 || !$revealableTiles) {
            throw new \BgaVisibleSystemException("You can't reveal other tile now: actUndoSkipRevealTile");
        }

        $this->gamestate->nextState("back");
    }

    public function actDiscardCollectedTile(#[IntParam(min: 1, max: 58)] int $tileCard_id): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $this->checkCardLocation($tileCard, "hand", $player_id);

        $this->tile_cards->moveCard($tileCard_id, "discard");

        $this->notifyAllPlayers(
            "discardCollectedTile",
            clienttranslate('${player_name} discards a collected ${tile} to unblock his moves'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "tileCard" => $tileCard,
                "preserve" => ["tileCard"],
                "18n" => ["tile"],
                "tile" => clienttranslate("tile"),
            ]
        );

        $this->globals->set(HAS_EXPANDED_TILES, true);
        $this->gamestate->nextState("revealTile");
    }

    public function actDiscardTile(#[IntParam(min: 1, max: 58)] int $tileCard_id): void
    {
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
            clienttranslate('${player_name} discards a ${tile} from the board (hex ${hex}) '),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "hex" => $hex,
                "tileCard" => $tileCard,
                "preserve" => ["tileCard"],
                "i18n" => ["tile"],
                "tile" => clienttranslate("tile"),
            ]
        );

        $this->gamestate->nextState("betweenTurns");
    }

    public function actMoveExplorer(#[IntParam(min: 1, max: 58)] int $tileCard_id): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $explorableTiles = $this->explorableTiles($player_id, true);

        if (!$explorableTiles) {
            $explorableTiles = $this->expandedExplorableTiles($player_id, true);
        }

        if (!array_key_exists($tileCard_id, $explorableTiles)) {
            throw new \BgaVisibleSystemException("You can't move your explorer to this tile now: actMoveExplorer, $tileCard_id");
        }

        $explorerCard = $this->getExplorerByPlayerId($player_id);

        $this->explorer_cards->moveCard($explorerCard["id"], "board", $tileCard["location_arg"]);

        $this->notifyAllPlayers(
            "moveExplorer",
            clienttranslate('${player_name} moves his explorer to a new ${tile} (hex ${hex}) '),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "hex" => $tileCard["location_arg"],
                "tileCard" => $tileCard,
                "explorerCard" => $explorerCard,
                "i18n" => ["region_label"],
                "preserve" => ["tileCard"],
                "tile" => clienttranslate("tile"),
            ]
        );

        $this->globals->set(HAS_MOVED_EXPLORER, true);

        $this->resolveTileEffect($tileCard, $player_id);
    }

    public function actPickRainbowGem(#[IntParam(min: 1, max: 4)] int $gem_id): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $explorer = $this->getExplorerByPlayerId($player_id);
        $hex = (int) $explorer["location_arg"];
        $tileCard = $this->getObjectFromDB("$this->deckSelectQuery FROM tile WHERE card_location_arg=$hex");

        $this->globals->set(RAINBOW_GEM, $gem_id);

        if (
            !$this->incGem(1, $gem_id, $player_id, $tileCard)
        ) {
            return;
        }

        $this->gamestate->nextState("optionalActions");
    }

    public function actDiscardObjective(#[IntParam(min: 1, max: 15)] int $objectiveCard_id): void
    {
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
                "player_name" => $this->getPlayerNameById($player_id)
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

        $tileCard = $this->globals->get(CURRENT_TILE);

        $this->resolveTileEffect($tileCard, $player_id);
    }

    public function actMine(#[JsonParam(alphanum: false)] array $stoneDice): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $stoneDiceCount = count($stoneDice);

        $activeStoneDiceCount = $this->globals->get(ACTIVE_STONE_DICE_COUNT);

        if ($stoneDiceCount > $activeStoneDiceCount) {
            $this->globals->set(ACTIVE_STONE_DICE_COUNT, $stoneDiceCount);
        }

        $privateStoneDiceCount = $this->getPrivateStoneDiceCount($player_id);

        if ($stoneDiceCount > $privateStoneDiceCount) {
            throw new \BgaVisibleSystemException("Not enough Stone Dice: actMine, $stoneDiceCount, $privateStoneDiceCount");
        }

        $this->decCoin(3, $player_id, true);

        $explorer = $this->getExplorerByPlayerId($player_id);
        $hex = (int) $explorer["location_arg"];

        $tileCard = $this->getObjectFromDB("$this->deckSelectQuery FROM tile WHERE card_location_arg=$hex");
        $tile_id = (int) $tileCard["type_arg"];

        $gem_id = (int) $this->tiles_info[$tile_id]["gem"];

        if ($gem_id !== 0 && $gem_id !== 10) {
            $gemName = $this->gems_info[$gem_id]["name"];
            $gemMarketValue = $this->globals->get("$gemName:MarketValue");
        } else {
            $gem_id = $this->globals->get(RAINBOW_GEM);
            $gemName = $this->gems_info[$gem_id]["name"];
            $gemMarketValue = $this->globals->get("$gemName:MarketValue");
        }

        $roll1 = $this->rollDie("1:$player_id", $player_id, "mining");
        $roll2 = $this->rollDie("2:$player_id", $player_id, "mining");

        $minedGems = 0;

        if ($roll1 >= $gemMarketValue) {
            $minedGems++;
        }

        if ($roll2 >= $gemMarketValue) {
            $minedGems++;
        }

        foreach ($stoneDice as $die) {
            $die_id = (int) $die["id"];
            $roll = $this->rollDie($die_id, $player_id, "stone");

            if ($roll >= $gemMarketValue) {
                $minedGems++;
            }
        }

        $this->notifyAllPlayers(
            "syncDieRolls",
            "",
            []
        );

        foreach ($stoneDice as $die) {
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

        if ($minedGems === 0) {
            $this->notifyAllPlayers(
                "failToMine",
                clienttranslate('${player_name} fails to mine his tile'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerNameById($player_id)
                ]
            );
        } else {
            if (!$this->incGem($minedGems, $gem_id, $player_id, $tileCard, true)) {
                return;
            };
        }

        $this->gamestate->nextState("repeat");
    }

    public function actSellGems(#[IntParam(min: 1, max: 4)] int $gem_id, #[JsonParam(alphanum: false)] array $selectedGems): void
    {
        $player_id = (int) $this->getActivePlayerId();

        if ($this->globals->get(HAS_SOLD_GEMS)) {
            throw new \BgaVisibleSystemException("You can only sell gems once per turn: actSellGems");
        }

        $gemCards = [];
        foreach ($selectedGems as $gemCard) {
            $gemCard_id = $gemCard["id"];

            $gemCard = $this->gem_cards->getCard($gemCard_id);
            $gemCards[$gemCard_id] = $gemCard;

            if ($gem_id !== (int) $gemCard["type_arg"]) {
                throw new \BgaVisibleSystemException("You must sell gems of the same type: actSellGems, $gem_id");
            }
        }

        $this->sellGem(count($gemCards), $gem_id, $gemCards, $player_id);
        $this->globals->set(HAS_SOLD_GEMS, true);

        $this->gamestate->nextState("repeat");
    }

    public function actTransferGem(#[JsonParam(alphanum: false)] array $gemCard, ?int $opponent_id): void
    {
        $players = $this->loadPlayersBasicInfos();

        if ($opponent_id && !array_key_exists($opponent_id, $players)) {
            throw new \BgaVisibleSystemException("This player is not in the table: actTransferGem, $opponent_id");
        }

        $player_id = (int) $this->getActivePlayerId();

        $gemCard_id = (int) $gemCard["id"];
        $gemCard = $this->gem_cards->getCard($gemCard_id);

        $this->checkCardLocation($gemCard, "hand", $player_id);

        $availableCargos = $this->availableCargos($player_id);

        if (!$availableCargos) {
            $this->discardGem($gemCard, $player_id);
        } else {
            $this->transferGem($gemCard, $opponent_id, $player_id);
        }

        $this->gamestate->nextState("repeat");
    }

    public function actSkipOptionalActions(): void
    {
        $this->gamestate->nextState("skip");
    }

    public function actUndoSkipOptionalActions(): void
    {
        $this->gamestate->nextState("back");
    }

    public function actRestoreRelic(#[IntParam(min: 1, max: 24)] int $relicCard_id): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $restorableRelics = $this->restorableRelics($player_id, true);

        if (!array_key_exists($relicCard_id, $restorableRelics)) {
            throw new \BgaVisibleSystemException("You can't restore this Relic now: actRestoreRelic, $relicCard_id");
        }

        $this->restoreRelic($relicCard_id, $player_id);

        $this->gamestate->nextState("repeat");
    }

    public function actSkipRestoreRelic(): void
    {
        $this->gamestate->nextState("skip");
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return string[]
     * @see ./states.inc.php
     */

    public function argRevealTile(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $revealableTiles = $this->revealableTiles($player_id);
        $revealsLimit = (int) $this->globals->get(REVEALS_LIMIT);
        $explorableTiles = $this->explorableTiles($player_id);

        $hasExpandedTiles = $this->globals->get(HAS_EXPANDED_TILES, false);

        $expandedRevealableTiles = [];
        if ($hasExpandedTiles) {
            $expandedRevealableTiles = $this->expandedRevealableTiles($player_id);
        }

        $mustDiscardCollectedTile = $revealsLimit < 2 && !$hasExpandedTiles && !$revealableTiles && !$explorableTiles;

        $noRevealableTile = $hasExpandedTiles && !$expandedRevealableTiles;

        $hasReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle from player WHERE player_id=$player_id");

        return [
            "revealableTiles" => $revealableTiles,
            "expandedRevealableTiles" => $expandedRevealableTiles,
            "mustDiscardCollectedTile" => $mustDiscardCollectedTile,
            "revealsLimit" => $revealsLimit,
            "skippable" => !!$explorableTiles,
            "hasReachedCastle" => $hasReachedCastle,
            "_no_notify" => $mustDiscardCollectedTile || $noRevealableTile || $revealsLimit === 2 || $hasReachedCastle,
        ];
    }

    public function stRevealTile(): void
    {
        $args = $this->argRevealTile();

        if ($args["_no_notify"]) {
            if ($args["hasReachedCastle"]) {
                $this->gamestate->nextState("discardTile");
                return;
            }

            if ($args["mustDiscardCollectedTile"]) {
                $this->gamestate->nextState("discardCollectedTile");
                return;
            }

            $this->gamestate->nextState("moveExplorer");
        }
    }

    public function argDiscardCollectedTile(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $collectedTiles = $this->getCollectedTiles($player_id);

        $auto = count($collectedTiles) === 1;

        return [
            "collectedTiles" => $collectedTiles,
            "_no_notify" => $auto
        ];
    }

    public function stDiscardCollectedTile(): void
    {
        $args = $this->argDiscardCollectedTile();

        $collectedTiles = $args["collectedTiles"];

        if ($args["_no_notify"]) {
            $tileCard = array_shift($collectedTiles);
            $tileCard_id = (int) $tileCard["id"];

            $this->actDiscardCollectedTile($tileCard_id);
            return;
        }
    }

    public function argMoveExplorer(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $explorableTiles = $this->explorableTiles($player_id);
        $revealableTiles = $this->revealableTiles($player_id);
        $revealsLimit = $this->globals->get(REVEALS_LIMIT);

        if (!$explorableTiles) {
            $explorableTiles = $this->expandedExplorableTiles($player_id);
            $revealableTiles = $this->expandedRevealableTiles($player_id);
        }

        return [
            "explorableTiles" => $explorableTiles,
            "revealableTiles" => $revealableTiles,
            "revealsLimit" => $revealsLimit,
            "_no_notify" => !!$this->globals->get(HAS_MOVED_EXPLORER)
        ];
    }

    public function stMoveExplorer(): void
    {
        $args = $this->argMoveExplorer();

        if ($args["_no_notify"]) {
            $this->gamestate->nextState("optionalActions");
        }
    }

    public function argOptionalActions(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $canMine = $this->hasEnoughCoins(3, $player_id);
        $canSellGems = $this->getTotalGemsCount($player_id) > 0 && !$this->globals->get(HAS_SOLD_GEMS);

        $activeStoneDiceCount = $this->globals->get(ACTIVE_STONE_DICE_COUNT);
        $activableStoneDiceCount = $this->getPrivateStoneDiceCount($player_id);

        return [
            "canMine" => $canMine,
            "activeStoneDiceCount" => $activeStoneDiceCount,
            "activableStoneDiceCount" => $activableStoneDiceCount,
            "canSellGems" => $canSellGems,
            "_no_notify" => !$canMine && !$canSellGems,
        ];
    }

    public function stOptionalActions(): void
    {
        $args = $this->argOptionalActions();

        if ($args["_no_notify"]) {
            $this->gamestate->nextState("restoreRelic");
        }
    }

    public function argTransferGem(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        return [
            "availableCargos" => $this->availableCargos($player_id),
            "_no_notify" => $this->getTotalGemsCount($player_id) <= 7,
        ];
    }

    public function stTransferGem(): void
    {
        $args = $this->argTransferGem();

        if ($args["_no_notify"]) {
            $anchorState_id = $this->globals->get(ANCHOR_STATE);

            if ($anchorState_id === 30 || $anchorState_id === 32) {
                $anchorState_id === 4;
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
                $this->discardGem($gemCard, $player_id);
                return;
            }

            if (count($availableCargos) === 1) {
                $opponent_id = array_shift($availableCargos);

                $this->transferGem($gemCard, $opponent_id, $player_id);
                $this->gamestate->nextState("repeat");
            }
        }
    }

    public function argRestoreRelic(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $restorableRelics = $this->restorableRelics($player_id);

        return [
            "restorableRelics" => $restorableRelics,
            "_no_notify" => !$restorableRelics
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

        $hasReachedCastle = !!$this->getUniqueValueFromDB("SELECT castle from player WHERE player_id=$player_id");

        if (!$hasReachedCastle) {
            $this->resetStoneDice($player_id);
            $this->collectTile($player_id);
        }

        $this->globals->set(REVEALS_LIMIT, 0);
        $this->globals->set(HAS_SOLD_GEMS, false);
        $this->globals->set(HAS_MOVED_EXPLORER, false);
        $this->globals->set(HAS_EXPANDED_TILES, false);
        $this->globals->set(ACTIVE_STONE_DICE_COUNT, 0);
        $this->globals->set(RAINBOW_GEM, null);
        $this->globals->set(ANCHOR_STATE, null);
        $this->globals->set(CURRENT_TILE, null);

        $castlePlayers = $this->getCollectionFromDB("SELECT player_id FROM player WHERE castle=1");

        if ($castlePlayers && count($castlePlayers) === $this->getPlayersNumber()) {
            $this->gamestate->nextState("finalScoring");
            return;
        }

        $this->notifyAllPlayers(
            "passTurn",
            clienttranslate('${player_name} passes'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id)
            ]
        );

        $this->giveExtraTime($player_id);
        $this->activeNextPlayer();

        $this->gamestate->nextState("nextTurn");
    }

    public function stFinalScoring(): void
    {
        $this->calcFinalScoring();
        $this->gamestate->nextState("gameEnd");
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
        $players = $this->loadPlayersBasicInfos();

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

            $tileRow = ceil(($hex + 1) / 7);

            $progression += $tileRow / 9;
        }

        return round($progression / count($players) * 100);
    }

    /*   Utility functions */

    public function rollDie(int | string $die_id, int $player_id, string $type): int
    {
        $face = bga_rand(1, 6);

        $this->notifyAllPlayers(
            'rollDie',
            clienttranslate('${player_name} rolls a ${face} with a ${type_label} die'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "die_id" => $die_id,
                "face" => $face,
                "type" => $type,
                "type_label" => $this->dice_info[$type],
                "i18n" => ["type_label"]
            ]
        );

        return $face;
    }

    public function checkCardLocation(array $card, string | int $location, int $location_arg = null)
    {
        if ($card["location"] != $location || ($location_arg && $card["location_arg"] != $location_arg)) {
            throw new \BgaVisibleSystemException("Unexpected card location: $location, $location_arg");
        }
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
        $explorers = $this->getCollectionFromDB($this->deckSelectQuery . " 
        FROM explorer WHERE card_location<>'box'");

        return $explorers;
    }

    public function getExplorerByPlayerId(int $player_id): array
    {
        $explorer = $this->getObjectFromDB($this->deckSelectQuery . "FROM explorer WHERE card_type_arg='$player_id'");

        return $explorer;
    }

    public function getTilesBoard(): array
    {
        $tilesBoard = $this->getCollectionFromDB("SELECT card_id id, card_type type, card_location location, card_location_arg location_arg 
        FROM tile WHERE card_location='board'");

        return $this->hideCards($tilesBoard);
    }

    public function adjacentTiles(int $player_id, ?int $tileHex = null): array
    {
        $adjacentTiles = [];

        if (!$tileHex) {
            $explorerCard = $this->getExplorerByPlayerId($player_id);

            if ($explorerCard["location"] === "scene") {
                return $this->getCollectionFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board' AND card_location_arg<=6");
            }

            $tileHex = $explorerCard["location_arg"];
        }

        $tileRow = ceil(($tileHex + 1) / 7);

        $leftHex = $tileHex - 1;
        $rightHex = $tileHex + 1;
        $topLeftHex = $tileHex + 6;
        $topRightHex = $tileHex + 7;

        $leftEdges = [1, 7, 14, 20, 27, 33, 40, 46, 53];
        $rightEdges = [6, 13, 19, 26, 32, 39, 45, 52, 58];

        if ($this->getPlayersNumber() === 2) {
            $leftEdges = [2, 8, 15, 21, 28, 34, 41, 46, 53];
            $rightEdges = [5, 12, 18, 25, 31, 38, 44, 51, 58];
        }

        if (in_array($tileHex, $leftEdges)) {
            $leftHex = null;

            if ($tileRow % 2 === 0) {
                $topLeftHex = null;
            }
        };

        if (in_array($tileHex, $rightEdges)) {
            $rightHex = null;

            if ($tileRow % 2 === 0) {
                $topRightHex = null;
            }
        }

        $adjacentHexes = [
            $leftHex,
            $rightHex,
            $topLeftHex,
            $topRightHex
        ];

        foreach ($adjacentHexes as $hex) {
            if ($hex === null) {
                continue;
            }

            $tileCard = $this->getObjectFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board' AND card_location_arg=$hex");

            if ($tileCard) {
                $tileCard_id = $tileCard["id"];
                $adjacentTiles[$tileCard_id] = $tileCard;
            }
        }

        return $adjacentTiles;
    }

    public function revealableTiles(int $player_id, bool $associative = false): array
    {
        $revealableTiles = [];

        $adjacentTiles =  $this->adjacentTiles($player_id);
        $revealedTiles = $this->globals->get(REVEALED_TILES, []);

        foreach ($adjacentTiles as $tileCard_id => $tileCard) {
            if (!key_exists($tileCard_id, $revealedTiles)) {
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
            if (key_exists($tileCard_id, $revealedTiles)) {
                if ($associative) {
                    $explorableTiles[$tileCard_id] = $tileCard;
                    continue;
                }

                $explorableTiles[] = $tileCard;
            }
        }

        return $explorableTiles;
    }

    public function expandedAdjacentTiles(int $player_id, array $adjacentTiles): array
    {
        $expandedAdjacentTiles = [];

        foreach ($adjacentTiles as $tileCard) {
            $tileHex = (int) $tileCard["location_arg"];

            $expandedTiles = $this->adjacentTiles($player_id, $tileHex);

            if (!$expandedTiles) {
                $expandedAdjacentTiles = $this->expandedAdjacentTiles($player_id, $expandedTiles);
                return $expandedAdjacentTiles;
            }

            foreach ($this->adjacentTiles($player_id, $tileHex) as $expandedTileCard_id => $expandedTileCard) {
                $expandedAdjacentTiles[$expandedTileCard_id] = $expandedTileCard;
            }
        }

        return $expandedAdjacentTiles;
    }

    public function expandedRevealableTiles(int $player_id, bool $associative = false): array
    {
        $expandedRevealableTiles = [];
        $adjacentTiles = $this->adjacentTiles($player_id);

        $expandedAdjacentTiles = $this->expandedAdjacentTiles($player_id, $adjacentTiles);
        $revealedTiles = $this->globals->get(REVEALED_TILES);

        foreach ($expandedAdjacentTiles as $tileCard_id => $tileCard) {
            if (key_exists($tileCard_id, $revealedTiles)) {
                continue;
            }

            if ($associative) {
                $expandedRevealableTiles[$tileCard_id] = $tileCard;
                continue;
            }

            $expandedRevealableTiles[] = $tileCard;
        }

        return $expandedRevealableTiles;
    }

    public function expandedExplorableTiles(int $player_id, bool $associative = false): array
    {
        $expandedExplorableTiles = [];
        $adjacentTiles = $this->adjacentTiles($player_id);

        $expandedAdjacentTiles = $this->expandedAdjacentTiles($player_id, $adjacentTiles);
        $revealedTiles = $this->globals->get(REVEALED_TILES);

        foreach ($expandedAdjacentTiles as $tileCard_id => $tileCard) {
            if (!key_exists($tileCard_id, $revealedTiles)) {
                continue;
            }

            if ($associative) {
                $expandedExplorableTiles[$tileCard_id] = $tileCard;
                continue;
            }

            $expandedExplorableTiles[] = $tileCard;
        }

        return $expandedExplorableTiles;
    }

    public function resolveTileEffect(array $tileCard, int $player_id): void
    {
        $tile_id = (int) $tileCard["type_arg"];
        $region_id = (int) $tileCard["type"];

        $tileInfo = $this->tiles_info[$tile_id];
        $gem_id = (int) $tileInfo["gem"];

        $hasReachedForest = !!$this->getUniqueValueFromDB("SELECT forest from player WHERE player_id=$player_id");

        if ($region_id === 3 && !$hasReachedForest) {
            $this->globals->set(CURRENT_TILE, $tileCard);
            $this->reachForest($player_id, $tileCard);
            return;
        }

        if ($gem_id === 0) {
            $this->gamestate->nextState("rainbowTile");
            return;
        }

        $tileEffect_id = (int) $tileInfo["effect"];

        if ($tileEffect_id) {
            $tileEffect = $this->tileEffects_info[$tileEffect_id];
            $effectValue = $tileEffect["values"][$region_id];

            if ($tileEffect_id === 1) {
                $this->incCoin($effectValue, $player_id);
            }

            if ($tileEffect_id === 2) {
                $this->incRoyaltyPoints($effectValue, $player_id);
            }

            if ($tileEffect_id === 3) {
                $this->obtainStoneDie($player_id);
            }
        }

        if ($gem_id === 10) {
            $this->obtainIridiaStone($player_id);
            $this->gamestate->nextState("rainbowTile");
            return;
        }

        if (!$this->incGem(1, $gem_id, $player_id, $tileCard)) {
            return;
        };

        $this->gamestate->nextState("optionalActions");
    }

    public function collectTile(int $player_id): bool
    {
        $explorerCard = $this->getExplorerByPlayerId($player_id);
        $hex = (int) $explorerCard["location_arg"];

        $tileCard = $this->getObjectFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board' 
        AND card_location_arg=$hex");

        $tileCard_id = (int) $tileCard["id"];

        $this->tile_cards->moveCard($tileCard_id, "hand", $player_id);

        $tile_id = (int) $tileCard["type_arg"];
        $tile_info = $this->tiles_info[$tile_id];
        $gem_id = (int) $tile_info["gem"];
        $region_id = (int) $tile_info["region"];

        $statName = $gem_id === 0 || $gem_id === 10 ? "rainbow:Tiles" : "$gem_id:GemTiles";
        $this->incStat(1, $statName, $player_id);

        $this->notifyAllPlayers(
            "collectTile",
            "",
            [
                "player_id" => $player_id,
                "tileCard" => $tileCard,
            ]
        );

        if ($gem_id !== 0 && $gem_id !== 10) {
            $this->updateMarketValue($gem_id);
        }

        if ($region_id === 5) {
            $this->reachCastle($player_id);
        }

        return true;
    }

    public function reachForest(int $player_id): void
    {
        $this->DbQuery("UPDATE player SET forest=1 WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "reachForest",
            clienttranslate('${player_name} reaches the Forest region'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
            ]
        );


        $this->gamestate->nextState("discardObjective");
    }

    public function getIridiaStoneOwner(): int | null
    {
        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $foundIridia = !!$this->getUniqueValueFromDB("SELECT iridia_stone FROM player WHERE player_id=$player_id");
            if ($foundIridia) {
                return $player_id;
            }
        }

        return null;
    }

    public function obtainIridiaStone(int $player_id): void
    {
        if ($this->getIridiaStoneOwner()) {
            throw new \BgaVisibleSystemException("The Iridia Stone has already been found: collectIridiaStone");
        }

        $this->DbQuery("UPDATE player SET iridia_stone=1, player_score_aux=1000 WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "obtainIridiaStone",
            clienttranslate('${player_name} finds the Iridia Stone'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id)
            ]
        );

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

        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $royaltyTokens[$player_id] = null;

            foreach ($this->royaltyTokens_info as $token_id => $token_info) {
                $tokenName = $token_info["name"];
                $hasObtainedToken = !!$this->getUniqueValueFromDB("SELECT $tokenName FROM player WHERE player_id=$player_id");

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
        $this->DbQuery("UPDATE player SET castle=1 WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "reacheCastle",
            clienttranslate('${player_name} reachs the Castle row'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id)
            ]
        );

        $this->notifyAllPlayers(
            "resetExplorer",
            "",
            [
                "player_id" => $player_id,
                "explorerCard" => $this->getExplorerByPlayerId($player_id)
            ]
        );

        $castlePlayers = $this->getCollectionFromDB("SELECT player_id FROM player WHERE castle=1");
        $castlePlayersCount = count($castlePlayers);

        if ($castlePlayersCount === 1) {
            $score_aux = 100;
            $token_id = 3;
        }

        if ($castlePlayersCount === 2) {
            $score_aux = 10;
            $token_id = 2;
        }

        if ($castlePlayersCount === 3) {
            $score_aux = 1;
            $token_id = 1;
        }

        if ($castlePlayersCount >= 4) {
            return;
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
                "player_name" => $this->getPlayerNameById($player_id),
                "token_id" => $token_id,
                "token_label" => $tokenLabel,
                "tokenName" => $tokenName,
                "i18n" => ["token_label"]
            ]
        );

        $this->DbQuery("UPDATE player SET $tokenName=1, player_score_aux=$score_aux WHERE player_id=$player_id");
        $this->incRoyaltyPoints($tokenPoints, $player_id);
    }

    public function getCollectedTiles(?int $player_id): array
    {
        if ($player_id) {
            return $this->tile_cards->getCardsInLocation("hand", $player_id);
        }

        $collectedTiles = [];

        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $collectedTiles[$player_id] = $this->tile_cards->getCardsInLocation("hand", $player_id);
        }

        return $collectedTiles;
    }

    public function getCoins(?int $player_id): int | array
    {
        $sql = "SELECT coin FROM player WHERE player_id=";
        if ($player_id) {
            return $this->getUniqueValueFromDB("$sql$player_id");
        }

        $coins = [];

        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $coins[$player_id] = $this->getUniqueValueFromDB("$sql$player_id");
        }

        return $coins;
    }

    public function getGems(?int $player_id): array
    {
        if ($player_id) {
            return $this->gem_cards->getCardsInLocation("hand", $player_id);
        }

        $gems = [];

        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $gems[$player_id] = $this->gem_cards->getCardsInLocation("hand", $player_id);
        }

        return $gems;
    }

    public function getGemsCounts(?int $player_id): array
    {
        $gemsCounts = [];

        if ($player_id) {
            foreach ($this->gems_info as $gem_info) {
                $gemName = $gem_info["name"];
                $handGems = $this->gem_cards->getCardsOfTypeInLocation($gemName, null, "hand", $player_id);
                $gemsCounts[$gemName] = count($handGems);
            }

            return $gemsCounts;
        }

        $players = $this->loadPlayersBasicInfos();
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

    public function availableCargos(int $excludedPlayer_id = null): array
    {
        $players = $this->loadPlayersBasicInfos();

        $availableCargos = [];
        foreach ($players as $player_id => $player) {
            if ($this->getTotalGemsCount($player_id) <= 7 && $player_id !== $excludedPlayer_id) {
                $availableCargos[] = $player_id;
            }
        }

        return $availableCargos;
    }

    public function transferGem(array $gemCard, int $opponent_id, int $player_id, $auto = false): void
    {
        $gem_id = (int) $gemCard["type_arg"];
        $gemCard_id = (int) $gemCard["id"];
        $gem_info = $this->gems_info[$gem_id];

        $this->gem_cards->moveCard($gemCard_id, "hand", $opponent_id);

        $this->notifyAllPlayers(
            "transferGem",
            clienttranslate('${player_name} gives away 1 ${gem_label} to ${player_name2}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "player_id2" => $opponent_id,
                "player_name2" => $this->getPlayerNameById($opponent_id),
                "gem_label" => $gem_info["tr_name"],
                "gemName" => $gem_info["name"],
                "gemCard" => $gemCard,
                "18n" => ["gem_label"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );
    }

    public function discardGem(array $gemCard, int $player_id): void
    {
        $gem_id = (int) $gemCard["type_arg"];

        $this->notifyAllPlayers(
            "discardGem",
            clienttranslate('${player_name} returns 1 ${gem_label} to the supply'),
            [
                "player_name" => $this->getPlayerNameById($player_id),
                "gem_label" => $this->gems_info[$gem_id]["tr_name"],
                "i18n" => ["gem_label"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );

        $this->decGem(1, $gem_id, [$gemCard], $player_id);
    }

    public function incGem(int $delta, int $gem_id, int $player_id, array $tileCard = null, bool $mine = false): bool
    {
        $gemName = $this->gems_info[$gem_id]["name"];

        $gemCards = $this->gem_cards->pickCardsForLocation($delta, $gemName, "hand", $player_id);

        $message = $mine ? clienttranslate('${player_name} mines ${delta} ${gem_label}') :
            clienttranslate('${player_name} collects ${delta} ${gem_label}');

        $this->notifyAllPlayers(
            "incGem",
            $message,
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
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
            $anchorState_id = $this->gamestate->state_id();
            $this->globals->set(ANCHOR_STATE, $anchorState_id);
            $this->gamestate->jumpToState(31);
            return false;
        }

        return true;
    }

    public function decGem(int $delta, int $gem_id, array $gemCards, int $player_id, bool $sell = false): void
    {
        if ($delta <= 0) {
            throw new \BgaVisibleSystemException("The delta must be positive: decGem, $delta");
        }

        if (count($gemCards) < $delta) {
            throw new \BgaVisibleSystemException("Not enough gems: decGem, $delta");
        }

        $gemName = $this->gems_info[$gem_id]["name"];

        foreach ($gemCards as $gemCard_id => $gemCard) {
            $this->checkCardLocation($gemCard, "hand", $player_id);

            $this->gem_cards->insertCardOnExtremePosition($gemCard_id, $gemName, false);
        }

        $this->notifyAllPlayers(
            "decGem",
            $sell ? clienttranslate('${player_name} sells ${delta} ${gem_label}') : "",
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
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

    public function sellGem(int $delta, int $gem_id, array $gemCards, int $player_id): void
    {
        $gemName = $this->gems_info[$gem_id]["name"];

        $this->decGem(
            $delta,
            $gem_id,
            $gemCards,
            $player_id,
            true
        );

        $marketValue = $this->globals->get("$gemName:MarketValue");
        $earnedCoins = $marketValue * $delta;

        $this->incCoin($earnedCoins, $player_id);
    }

    public function updateMarketValue(int $gem_id): void
    {
        $gem_info = $this->gems_info[$gem_id];
        $gemName = $gem_info["name"];

        $marketValueCode = "$gemName:MarketValue";
        $marketValue = $this->globals->get($marketValueCode);

        if ($marketValue === 6) {
            $this->globals->set($marketValueCode, 1);
            $marketValue = 1;
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
        } else {
            $marketValue = $this->globals->inc($marketValueCode, 1);
        }

        $this->notifyAllPlayers(
            "updateMarketValue",
            clienttranslate('The market value of ${gem_label} is ${marketValue} now'),
            [
                "marketValue" => $marketValue,
                "gem_label" => $gem_info["tr_name"],
                "i18n" => ["gem_label"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );
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
            clienttranslate('${player_name} obtains ${delta} ${coin}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "delta" => $delta,
                "coin" => clienttranslate("coin(s)"),
                "i18n" => ["coin"],
            ]
        );
    }

    public function decCoin(int $delta, int $player_id): void
    {
        if (!$this->hasEnoughCoins($delta, $player_id)) {
            throw new \BgaVisibleSystemException("You don't have enough coins: decCoin, $delta");
        }

        $this->dbQuery("UPDATE player SET coin=coin-$delta WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "incCoin",
            clienttranslate('${player_name} spends ${abs_delta} ${coin}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "abs_delta" => $delta,
                "delta" => -$delta,
                "coin" => clienttranslate("coin(s)"),
                "i18n" => ["coin"]
            ]
        );
    }

    public function incRoyaltyPoints(int $delta, int $player_id, bool $silent = false): void
    {
        $this->dbQuery("UPDATE player SET player_score=player_score+$delta WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "incRoyaltyPoints",
            $silent ? "" : clienttranslate('${player_name} scores ${delta} Royalty Point(s)'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "delta" => $delta
            ]
        );
    }

    public function getPrivateStoneDiceCount(?int $player_id): int | array
    {
        $sql = "SELECT stone_die FROM player WHERE player_id=";
        if ($player_id) {
            return (int) $this->getUniqueValueFromDB("$sql$player_id");
        }

        $privateStoneDiceCount = [];

        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $privateStoneDiceCount[$player_id] = (int) $this->getUniqueValueFromDB("$sql$player_id");
        }

        return $privateStoneDiceCount;
    }

    public function obtainStoneDie(int $player_id): bool
    {
        if ($this->globals->get(PUBLIC_STONE_DICE_COUNT) === 0) {
            return false;
        }

        $this->dbQuery("UPDATE player SET stone_die=stone_die+1 WHERE player_id=$player_id");
        $publicStoneDiceCount = $this->globals->inc(PUBLIC_STONE_DICE_COUNT, -1);

        $this->notifyAllPlayers(
            "obtainStoneDie",
            clienttranslate('${player_name} obtains a Stone Die'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "die_id" => 4 - $publicStoneDiceCount
            ]
        );

        return true;
    }

    public function resetStoneDice(int $player_id): void
    {
        $activeStoneDiceCount = $this->globals->get(ACTIVE_STONE_DICE_COUNT);

        $this->dbQuery("UPDATE player SET stone_die=stone_die-$activeStoneDiceCount WHERE player_id=$player_id");
        $this->globals->inc(PUBLIC_STONE_DICE_COUNT, $activeStoneDiceCount);

        $this->notifyAllPlayers(
            "resetStoneDice",
            "",
            [
                "player_id" => $player_id,
            ]
        );
    }

    public function getRelicsDeck(bool $onlyTop = false): array
    {
        $relicsDeckTop = $this->relic_cards->getCardOnTop("deck");
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
        return $this->relic_cards->getCardsInLocation("market");
    }

    public function getRestoredRelics(?int $player_id): array
    {
        if ($player_id) {
            return $this->relic_cards->getCardsInLocation("hand", $player_id);
        }

        $relicCards = [];

        $players = $this->loadPlayersBasicInfos();
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

        return $restorableRelics;
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
        $this->incStat(1, $statName, $player_id);

        if ($relicType !== 4) {
            $this->incStat(1, "$relicType:TypeRelics", $player_id);
        }

        foreach ($relicCost as $gem_id => $gemCost) {
            if ($gemCost === 0) {
                continue;
            }

            $gemName = $this->gems_info[$gem_id]["name"];

            $gemCards = $this->gem_cards->getCardsOfTypeInLocation($gemName, null, "hand", $player_id);
            $gemCards = array_slice($gemCards, 0, $gemCost, true);

            $this->decGem($gemCost, $gem_id, $gemCards, $player_id);
        }

        $this->relic_cards->moveCard($relicCard_id, "hand", $player_id);
        $this->notifyAllPlayers(
            "restoreRelic",
            clienttranslate('${player_name} restores the ${relic_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "relic_name" => $relic_info["tr_name"],
                "relicCard" => $relicCard,
                "i18n" => ["relic_name"],
                "preserve" => ["relicCard"],
            ]
        );

        $this->incRoyaltyPoints($relicPoints, $player_id);

        $this->replaceRelic();
    }

    public function replaceRelic(): void
    {
        $relicCard = $this->relic_cards->pickCardForLocation("deck", "market");
        $relic_id = $relicCard["type_arg"];

        $relicsDeckTop = $this->relic_cards->getCardOnTop("deck");
        $relic_info = $this->relics_info[$relic_id];

        $this->notifyAllPlayers(
            "replaceRelic",
            clienttranslate('A new Relic is revealed: the ${relic_name}'),
            [
                "relic_name" => $relic_info["tr_name"],
                "relicsDeckTop" => $this->hideCard($relicsDeckTop, true, "fake"),
                "relicCard" => $relicCard,
                "i18n" => ["relic_name"],
                "preserve" => ["relicCard"],
            ]
        );
    }

    public function getItemsDeck(): array
    {
        $itemsDeck = $this->item_cards->getCardsInLocation("deck");

        return $this->hideCards($itemsDeck, true, true);
    }

    public function getItemsMarket(): array
    {
        $itemsMarket = $this->item_cards->getCardsInLocation("market");

        return $itemsMarket;
    }

    public function checkItemsMarket(): bool
    {
        $itemsMarket = $this->getItemsMarket();

        $itemsCounts = [];
        $pairsCount = 0;

        foreach ($itemsMarket as $itemCard_id => $itemCard) {
            $item_id = (int) $itemCard["type_arg"];

            if (!key_exists($item_id, $itemsCounts)) {
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

    public function reshuffleItemsDeck(bool $setup = false)
    {
        $this->item_cards->moveAllCardsInLocation(null, "deck");
        $this->item_cards->shuffle("deck");
        $this->item_cards->pickCardsForLocation(5, "deck", "market");

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
            clienttranslate('The merchant&apos;s market is reshuffled'),
            [
                "itemsDeck" => $itemsDeck,
                "itemsMarket" => $itemsMarket,
            ]
        );
    }

    public function getObjectives(int $current_player_id, bool $unique = false): array
    {
        if ($unique) {
            return $this->objective_cards->getCardsInLocation("hand", $current_player_id);
        }

        $objectiveCards = [];

        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $cards = $this->objective_cards->getCardsInLocation("hand", $player_id);
            $objectiveCards[$player_id] = $player_id === $current_player_id ? $cards : $this->hideCards($cards);
        }

        return $objectiveCards;
    }

    public function computeGemsPoints(int $player_id): void
    {
        $totalGemsCount = $this->getTotalGemsCount($player_id);

        $gemsPoints = (int) ($totalGemsCount - ($totalGemsCount % 2)) / 2;

        $this->incRoyaltyPoints($gemsPoints, $player_id, true);

        if ($gemsPoints > 0) {
            $this->notifyAllPlayers(
                "computeGemsPoints",
                clienttranslate('${player_name} scores ${points} points from Gems'),
                [
                    "player_name" => $this->getPlayerNameById($player_id),
                    "points" => $gemsPoints,
                ]
            );
        }
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

    public function computeTilesPoints(int $player_id): void
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
            $gem_id = $this->tiles_info[$tile_id]["gem"];

            $tilesCountsByGem[$gem_id]++;
        }

        $tilesPoints = $this->calcMaxTilesPoints($tilesCountsByGem);

        if ($tilesPoints) {
            $this->notifyAllPlayers(
                "computeTilesPoints",
                clienttranslate('${player_name} scores ${points} from tiles'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerNameById($player_id),
                    "points" => $tilesPoints
                ],
            );
        }

        $this->incRoyaltyPoints($tilesPoints, $player_id, true);
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

    public function calcMaxRelicsPoints($tech, $lore, $jewelry, $iridia): int
    {
        $memo = [];

        return $this->relicsDp($tech, $lore, $jewelry, $iridia, $memo);
    }

    public function computeRelicsPoints(int $player_id): void
    {
        $relicsCountsByType = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0
        ];

        $relicCards = $this->relic_cards->getCardsInLocation("hand", $player_id);
        foreach ($relicCards as $relicCard) {
            $relic_id = (int) $relicCard["type_arg"];
            $type_id = (int) $this->relics_info[$relic_id]["type"];
            $relicsCountsByType[$type_id]++;
        }

        $iridia = $relicsCountsByType[0];
        $jewelry = $relicsCountsByType[1];
        $lore = $relicsCountsByType[2];
        $tech = $relicsCountsByType[3];

        $relicsPoints = $this->calcMaxRelicsPoints($tech, $lore, $jewelry, $iridia);

        if ($relicsPoints > 0) {
            $this->notifyAllPlayers(
                "computeRelicsPoints",
                clienttranslate('${player_name} scores ${points} from relics'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerNameById($player_id),
                    "points" => $relicsPoints
                ],
            );
        }

        $this->incRoyaltyPoints($relicsPoints, $player_id, true);
    }

    public function computeObjectivePoints(int $player_id): void
    {
        $handObjectives = $this->objective_cards->getCardsInLocation("hand", $player_id);
        $objectiveCard = array_shift($handObjectives);
        $objective_id = (int) $objectiveCard["type_arg"];

        $objective = new ObjectiveManager($objective_id, $this);
        $objectiveCompleted = $objective->checkCondition($player_id);

        $this->notifyAllPlayers(
            "revealObjective",
            clienttranslate('${player_name} reveals ${objective_name} as his Secret Objective'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
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
                clienttranslate('${player_name} completes the ${objective_name} objective and scores ${points} points'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerNameById($player_id),
                    "objective_name" => $objective->tr_name,
                    "objectiveCard" => $objectiveCard,
                    "points" => $objective->points,
                    "i18n" => ["objective_name"],
                    "preserve" => ["objectiveCard"]
                ]
            );
        }
    }

    public function calcFinalScoring(): void
    {
        $players = $this->loadPlayersBasicInfos();

        foreach ($players as $player_id => $player) {
            $this->computeGemsPoints($player_id);
            $this->computeTilesPoints($player_id);
            $this->computeRelicsPoints($player_id);
            $this->computeObjectivePoints($player_id);
        }
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
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
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

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb("SELECT player_id, player_score score FROM player");
        $result["tilesBoard"] = $this->getTilesBoard();
        $result["playerBoards"] = $this->globals->get(PLAYER_BOARDS);
        $result["revealedTiles"] = $this->globals->get(REVEALED_TILES, []);
        $result["collectedTiles"] = $this->getCollectedTiles(null);
        $result["iridiaStoneOwner"] = $this->getIridiaStoneOwner();
        $result["royaltyTokens"] = $this->getRoyaltyTokens(null);
        $result["explorers"] = $this->getExplorers();
        $result["coins"] = $this->getCoins(null);
        $result["gems"] = $this->getGems(null);
        $result["gemsCounts"] = $this->getGemsCounts(null);
        $result["marketValues"] = $this->getMarketValues(null);
        $result["publicStoneDiceCount"] = $this->globals->get(PUBLIC_STONE_DICE_COUNT);
        $result["privateStoneDiceCount"] = $this->getPrivateStoneDiceCount(null);
        $result["activeStoneDiceCount"] = $this->globals->get(ACTIVE_STONE_DICE_COUNT);
        $result["relicsInfo"] = $this->relics_info;
        $result["relicsDeck"] = $this->getRelicsDeck();
        $result["relicsDeckTop"] = $this->getRelicsDeck(true);
        $result["relicsMarket"] = $this->getRelicsMarket();
        $result["restoredRelics"] = $this->getRestoredRelics(null);
        $result["itemsInfo"] = $this->items_info;
        $result["itemsDeck"] = $this->getItemsDeck();
        $result["itemsMarket"] = $this->getItemsMarket();
        $result["objectivesInfo"] = $this->objectives_info;
        $result["objectives"] = $this->getObjectives($current_player_id);

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

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        $players = $this->loadPlayersBasicInfos();

        foreach ($players as $player_id => $player) {
            $this->initStat("player", "1:GemTiles", 0, $player_id);
            $this->initStat("player", "2:GemTiles", 0, $player_id);
            $this->initStat("player", "3:GemTiles", 0, $player_id);
            $this->initStat("player", "4:GemTiles", 0, $player_id);

            $this->initStat("player", "1:GemRelics", 0, $player_id);
            $this->initStat("player", "2:GemRelics", 0, $player_id);
            $this->initStat("player", "3:GemRelics", 0, $player_id);
            $this->initStat("player", "4:GemRelics", 0, $player_id);

            $this->initStat("player", "1:TypeRelics", 0, $player_id);
            $this->initStat("player", "2:TypeRelics", 0, $player_id);
            $this->initStat("player", "3:TypeRelics", 0, $player_id);
        }

        $explorers = [];
        foreach ($this->explorers_info as $explorer_id => $explorer) {
            $explorers[] = ["type" => $explorer["color"], "type_arg" => $explorer_id, "nbr" => 1];
        }

        $this->explorer_cards->createCards($explorers, "deck");

        $explorers = $this->explorer_cards->getCardsInLocation("deck");
        $playerBoards = [];
        foreach ($explorers as $card_id => $explorer) {
            foreach ($players as $player_id => $player) {
                $player_color = $this->getPlayerColorById($player_id);

                if ($player_color === $explorer["type"]) {
                    $playerBoards[$player_id] = $explorer["type_arg"];

                    $this->explorer_cards->moveCard($card_id, "scene", $player_id);
                    $this->DbQuery("UPDATE explorer SET card_type_arg=$player_id WHERE card_id='$card_id'");
                }
            }
        }

        $this->globals->set(PLAYER_BOARDS, $playerBoards);
        $this->explorer_cards->moveAllCardsInLocation("deck", "box");

        $tiles = [];
        foreach ($this->tiles_info as $tile_id => $tile_info) {
            $region_id = $tile_info["region"];

            $tiles[] = ["type" => $region_id, "type_arg" => $tile_id, "nbr" => 1];
        }

        $this->tile_cards->createCards($tiles, "deck");

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

        if (count($players) === 2) {
            $this->DbQuery("UPDATE tile SET card_location='box', card_location_arg=0 
            WHERE card_location='board' AND 
            card_location_arg IN (1, 6, 7, 13, 14, 19, 20, 26, 27, 32, 33, 39, 40, 45, 46, 52)");
        }

        $gemCards = [];
        foreach ($this->gems_info as $gem_id => $gem_info) {
            $gemName = $gem_info["name"];
            $gemCards[] = ["type" => $gemName, "type_arg" => $gem_id, "nbr" => count($players) * 7];
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
            if (count($players) === 2 && ($item_id === 10 || $item_id === 11)) {
                continue;
            }

            $itemCards[] = ["type" => $item_info["cost"], "type_arg" => $item_id, "nbr" => 3];
        }
        $this->item_cards->createCards($itemCards, "deck");

        $this->item_cards->shuffle("deck");

        $this->item_cards->pickCardsForLocation(5, "deck", "market");

        if (
            !$this->checkItemsMarket()
        ) {
            $this->reshuffleItemsDeck(true);
        };

        $this->globals->set(REVEALS_LIMIT, 0);
        $this->globals->set(PUBLIC_STONE_DICE_COUNT, 4);
        $this->globals->set(ACTIVE_STONE_DICE_COUNT, 0);

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
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default: {
                        $this->gamestate->nextState("zombiePass");
                        break;
                    }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
