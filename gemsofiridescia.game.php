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

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

use \Bga\GameFramework\Actions\Types\IntParam;
use Bga\GameFramework\Actions\Types\JsonParam;

class GemsOfIridescia extends Table
{
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([]);

        $this->tile_cards = $this->getNew("module.common.deck");
        $this->tile_cards->init("tile");

        $this->explorer_cards = $this->getNew("module.common.deck");
        $this->explorer_cards->init("explorer");

        $this->relic_cards = $this->getNew("module.common.deck");
        $this->relic_cards->init("relic");

        $this->deckSelectQuery = "SELECT card_id id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg ";
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

        if (!array_key_exists($tileCard_id, $revealableTiles)) {
            throw new BgaVisibleSystemException("You can't reveal this tile now: actRevealTile, $tileCard_id");
        }

        $revealedTiles = $this->globals->get("revealedTiles", []);
        $revealedTiles[$tileCard_id] = $tileCard;
        $this->globals->set("revealedTiles", $revealedTiles);

        $region_id = $tileCard["type"];

        $this->notifyAllPlayers(
            "revealTile",
            clienttranslate('${player_name} reveals a tile from the ${region_label} region'),
            [
                "i18n" => ["region_label"],
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "region_label" => $this->regions_info[$region_id]["tr_label"],
                "tileCard" => $tileCard,
            ]
        );

        $this->globals->inc("revealsLimit", 1);

        $this->gamestate->nextState("repeat");
    }

    public function actSkipRevealTile()
    {
        $player_id = (int) $this->getActivePlayerId();

        $explorableTiles = $this->explorableTiles($player_id);
        if (!$explorableTiles) {
            throw new BgaVisibleSystemException("You must reveal a tile now: actSkipRevealTile");
        }

        $this->gamestate->nextState("skip");
    }

    public function actUndoSkipRevealTile()
    {
        $revealsLimit = (int) $this->globals->get("revealsLimit");

        if ($revealsLimit === 2) {
            throw new BgaVisibleSystemException("You can't reveal other tile now: actUndoSkipRevealTile");
        }

        $this->gamestate->nextState("back");
    }

    public function actMoveExplorer(#[IntParam(min: 1, max: 58)] int $tileCard_id): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $tileCard = $this->tile_cards->getCard($tileCard_id);

        $explorableTiles = $this->explorableTiles($player_id, true);

        if (!array_key_exists($tileCard_id, $explorableTiles)) {
            throw new BgaVisibleSystemException("You can't move your explorer to this tile now: actMoveExplorer, $tileCard_id");
        }

        $region_id = (int) $tileCard["type"];

        $explorerCard = $this->getExplorerByPlayerId($player_id);

        $this->explorer_cards->moveCard($explorerCard["id"], "board", $tileCard["location_arg"]);

        $this->notifyAllPlayers(
            "moveExplorer",
            clienttranslate('${player_name} moves his explorer to a tile from the ${region_label} region'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "region_label" => $this->regions_info[$region_id]["tr_label"],
                "tileCard" => $tileCard,
                "explorerCard" => $explorerCard,
                "i18n" => ["region_label"],
            ]
        );

        $this->resolveTileEffect($tileCard, $player_id);
    }

    public function actPickRainbowGem(#[IntParam(min: 1, max: 4)] int $gem_id): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $this->incGem(1, $gem_id, $player_id);

        $this->gamestate->nextState("mine");
    }

    public function actMine(#[IntParam(min: 0, max: 4)] int $stoneDiceCount): void
    {
        $player_id = (int) $this->getActivePlayerId();

        $privateStoneDiceCount = $this->globals->get("privateStoneDiceCount")[$player_id];

        if ($stoneDiceCount > $privateStoneDiceCount) {
            throw new BgaVisibleSystemException("Not enough Stone Dice: actMine, $stoneDiceCount, $privateStoneDiceCount");
        }

        $this->decCoin(3, $player_id, true);

        $explorer = $this->getExplorerByPlayerId($player_id);
        $hex = (int) $explorer["location_arg"];

        $tileCard = $this->getObjectFromDB("$this->deckSelectQuery FROM tile WHERE card_location_arg=$hex");
        $tile_id = (int) $tileCard["type_arg"];

        $gem_id = (int) $this->tiles_info[$tile_id]["gem"];
        $gemName = $this->gems_info[$gem_id]["name"];
        $gemMarketValue = $this->globals->get("$gemName:MarketValue");

        $roll1 = $this->rollDie("1:$player_id", $player_id, "mining");
        $roll2 = $this->rollDie("2:$player_id", $player_id, "mining");

        $mined = 0;

        if ($roll1 >= $gemMarketValue) {
            $mined++;
        }

        if ($roll2 >= $gemMarketValue) {
            $mined++;
        }

        $this->globals->inc("activeStoneDice", $stoneDiceCount);

        if ($stoneDiceCount > 0) {
            $this->notifyAllPlayers(
                "informActiveStoneDice",
                clienttranslate('${player_name} has ${count} active Stone dice'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerNameById($player_id),
                    "count" => $this->globals->get("activeStoneDice"),
                ]
            );
        }

        for ($die_id = $stoneDiceCount; $die_id > 0; $die_id--) {
            $this->notifyAllPlayers(
                "activateStoneDie",
                "",
                [
                    "player_id" => $player_id,
                    "die_id" => $die_id
                ]
            );

            $roll = $this->rollDie($die_id, $player_id, "stone");

            if ($roll >= $gemMarketValue) {
                $mined++;
            }
        }

        if ($mined === 0) {
            $this->notifyAllPlayers(
                "failToMine",
                clienttranslate('${player_name} fails to mine his tile'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->getPlayerNameById($player_id)
                ]
            );
        } else {
            $this->incGem($mined, $gem_id, $player_id, $tileCard, true);
        }

        $this->gamestate->nextState("repeat");
    }

    public function actSellGems(#[IntParam(min: 1, max: 4)] int $gem_id, #[JsonParam(alphanum: false)] array $selectedGems): void
    {
        $player_id = (int) $this->getActivePlayerId();

        if ($this->globals->get("hasSoldGems")) {
            throw new BgaVisibleSystemException("You can only sell gems once per turn: actSellGems");
        }

        $soldGems = [];
        foreach ($selectedGems as $gemCard) {
            if ($gem_id !== (int) $gemCard["type_arg"]) {
                throw new BgaVisibleSystemException("You must sell gems of the same type: actSellGems, $gem_id");
            }

            $soldGems[] = $gemCard;
        }

        $this->sellGem(count($soldGems), $gem_id, $soldGems, $player_id);
        $this->globals->set("hasSoldGems", true);

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
            throw new BgaVisibleSystemException("You can't restore this Relic now: actRestoreRelic, $relicCard_id");
        }

        $this->restoreRelic($relicCard_id, $player_id);

        $this->gamestate->nextState("repeat");
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
        $revealsLimit = (int) $this->globals->get("revealsLimit");
        $explorableTiles = $this->explorableTiles($player_id);

        return [
            "revealableTiles" => $this->revealableTiles($player_id),
            "revealsLimit" => $revealsLimit,
            "skippable" => !!$explorableTiles,
            "_no_notify" => !$revealableTiles || $revealsLimit >= 2,
        ];
    }

    public function stRevealTile(): void
    {
        $args = $this->argRevealTile();

        if ($args["_no_notify"]) {
            $this->gamestate->nextState("moveExplorer");
        }
    }

    public function argMoveExplorer(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $explorableTiles = $this->explorableTiles($player_id);
        $revealsLimit = $this->globals->get("revealsLimit");

        return [
            "explorableTiles" => $explorableTiles,
            "revealsLimit" => $revealsLimit,
            "_no_notify" => !$explorableTiles
        ];
    }

    public function stMoveExplorer(): void
    {
        $args = $this->argMoveExplorer();

        if ($args["_no_notify"]) {
            $this->gamestate->nextState("mine");
        }
    }

    public function argOptionalActions(): array
    {
        $player_id = (int) $this->getActivePlayerId();

        $can_mine = $this->hasEnoughCoins(3, $player_id);
        $can_sellGems = $this->getTotalGemCount($player_id) > 0 && !$this->globals->get("hasSoldGems");

        return [
            "can_mine" => $can_mine,
            "can_sellGems" => $can_sellGems,
            "_no_notify" => !$can_mine && !$can_sellGems,
        ];
    }

    public function stOptionalActions(): void
    {
        $args = $this->argOptionalActions();

        if ($args["_no_notify"]) {
            $this->gamestate->nextState("restoreRelic");
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
        // TODO: compute and return the game progression

        return 0;
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

    public function hideCard(array $card, string | int $fakeId = null): array
    {
        if ($fakeId) {
            $card["id"] = $fakeId;
        }
        $card["type_arg"] = null;
        return $card;
    }

    public function hideCards(array $cards, bool $fakeIds = false): array
    {
        $hiddenCards = [];
        $fakeId = -count($cards);
        foreach ($cards as $card_id => $card) {
            $fakeId = $fakeIds ? $fakeId : null;
            $hiddenCards[$card_id] = $this->hideCard($card, $fakeId);

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

    public function getTileBoard(): array
    {
        $tilesBoard = $this->getCollectionFromDB("SELECT card_id id, card_type type, card_location location, card_location_arg location_arg 
        FROM tile WHERE card_location='board'");

        return $this->hideCards($tilesBoard);
    }

    public function adjacentTiles(int $player_id): array
    {
        $adjacentTiles = [];

        $explorer = $this->getExplorerByPlayerId($player_id);

        if ($explorer["location"] === "scene") {
            return $this->getCollectionFromDB("$this->deckSelectQuery FROM tile WHERE card_location='board' AND card_location_arg<=6");
        }

        $tileHex = $explorer["location_arg"];

        $leftHex = $tileHex - 1;
        $rightHex = $tileHex + 1;
        $topLeftHex = $tileHex + 5;
        $topRightHex = $tileHex + 6;

        $tilesRow = ceil(($tileHex + 1) / 7);

        if ($tilesRow % 2 === 0) {
            $topLeftHex++;
            $topRightHex++;
        }

        $leftEdges = [1, 7, 14, 20, 27, 33, 40, 46, 53];
        $rightEdges = [6, 13, 19, 26, 32, 39, 45, 52];

        if (in_array($tileHex, $leftEdges)) {
            $leftHex = null;
            $topLeftHex = null;
        };

        if (in_array($tileHex, $rightEdges)) {
            $rightHex = null;
            $topRightHex = null;
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
        $revealedTiles = $this->globals->get("revealedTiles", []);

        foreach ($adjacentTiles as $card_id => $tileCard) {
            if (!key_exists($card_id, $revealedTiles)) {
                if ($associative) {
                    $revealableTiles[$card_id] = $tileCard;
                    continue;
                }

                $revealableTiles[] = $tileCard;
            }
        }

        return $this->hideCards($revealableTiles);
    }

    public function occupiedTiles(): array
    {
        $occupiedTiles = [];

        $explorers = $this->getExplorers();
        foreach ($explorers as $card_id => $explorerCard) {
            $explorerTile = $explorerCard["location_arg"];

            if ($explorerCard["location"] === "board") {
                $tileCard = $this->getObjectFromDB($this->deckSelectQuery . "from tile 
                WHERE card_location='board' AND card_location_arg=$explorerTile");

                if ($tileCard) {
                    $tileCard_id = $tileCard["id"];
                    $occupiedTiles[$tileCard_id] = $tileCard;
                }
            }
        }

        return $occupiedTiles;
    }

    public function explorableTiles(int $player_id, bool $associative = false): array
    {
        $explorableTiles = [];

        $adjacentTiles = $this->adjacentTiles($player_id);
        $revealedTiles = $this->globals->get("revealedTiles", []);
        $occupiedTiles = (array) $this->occupiedTiles();

        foreach ($adjacentTiles as $card_id => $tileCard) {
            if (key_exists($card_id, $revealedTiles) && !key_exists($card_id, $occupiedTiles)) {
                if ($associative) {
                    $explorableTiles[$card_id] = $tileCard;
                    continue;
                }

                $explorableTiles[] = $tileCard;
            }
        }

        return $this->hideCards($explorableTiles);
    }

    public function resolveTileEffect(array $tileCard, int $player_id): void
    {
        $tile_id = (int) $tileCard["type_arg"];
        $region_id = (int) $tileCard["type"];

        $tileInfo = $this->tiles_info[$tile_id];
        $gem_id = (int) $tileInfo["gem"];

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

        $this->incGem(1, $gem_id, $player_id, $tileCard);

        $this->gamestate->nextState("mine");
    }

    public function getGems(?int $player_id): array
    {
        $sql = "SELECT amethyst, citrine, emerald, sapphire, coin FROM player WHERE player_id=";
        if ($player_id) {
            return $this->getObjectFromDB("$sql$player_id");
        }

        $gems = [];

        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $gems[$player_id] = $this->getObjectFromDB("$sql$player_id");
        }

        return $gems;
    }

    public function getTotalGemCount(int $player_id): int
    {
        $gems = $this->getGems($player_id);

        $totalGemCount = 0;
        foreach ($gems as $gemCount) {
            $totalGemCount += $gemCount;
        }

        return $totalGemCount;
    }

    public function incGem(int $delta, int $gem_id, int $player_id, array $tileCard = null, bool $mine = false): void
    {
        $gemName = $this->gems_info[$gem_id]["name"];

        $this->DbQuery("UPDATE player SET $gemName=$gemName+$delta WHERE player_id=$player_id");

        $message = $mine ? clienttranslate('${player_name} mines ${delta} ${gem_label}') : clienttranslate('${player_name} collects ${delta} ${gem_label}');

        $this->notifyAllPlayers(
            "incGem",
            $message,
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "delta" => $delta,
                "gem" => $gemName,
                "tileCard" => $tileCard,
                "gem_label" => $this->gems_info[$gem_id]["tr_label"],
                "i18n" => ["gem_label"]
            ]
        );
    }

    public function decGem(int $delta, int $gem_id, int $player_id, array $gemCards = null, bool $sell = false): void
    {
        if ($delta <= 0) {
            return;
        }

        $gemName = $this->gems_info[$gem_id]["name"];

        $gemCount = $this->getUniqueValueFromDB("SELECT $gemName FROM player WHERE player_id=$player_id");

        if ($gemCount < $delta) {
            throw new BgaVisibleSystemException("Not enough gems: decGem, $gemName, $delta, $gemCount");
        }

        $this->notifyAllPlayers(
            "decGem",
            $sell ? clienttranslate('${player_name} sells ${delta} ${gem_label}') : "",
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "delta" => $delta,
                "gem_id" => $gem_id,
                "gemName" => $gemName,
                "gemCards" => $gemCards,
                "gem_label" => $this->gems_info[$gem_id]["tr_label"],
                "i18n" => ["gem_label"]
            ]
        );

        $this->DbQuery("UPDATE player SET $gemName=$gemName-$delta WHERE player_id=$player_id");
    }

    public function sellGem(int $delta, int $gem_id, array $gemCards, int $player_id)
    {
        $gemName = $this->gems_info[$gem_id]["name"];

        $this->decGem(
            $delta,
            $gem_id,
            $player_id,
            $gemCards,
            true
        );

        $marketValue = $this->globals->get("$gemName:MarketValue");
        $earnedCoins = $marketValue * $delta;

        $this->incCoin($earnedCoins, $player_id);
    }

    public function updateMarket(int $gem_id): void
    {
        $gemName = $this->gems_info[$gem_id]["name"];

        $marketValueCode = "$gemName:MarketValue";
        $marketValue = $this->globals->get($marketValueCode);

        if ($marketValue === 6) {
            $this->globals->set($marketValueCode, 1);
            return;
        }

        $this->globals->inc($marketValueCode, 1);
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
            clienttranslate('${player_name} obtains ${delta} coin(s)'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "delta" => $delta
            ]
        );
    }

    public function decCoin(int $delta, int $player_id): void
    {
        if (!$this->hasEnoughCoins($delta, $player_id)) {
            throw new BgaVisibleSystemException("You don't have enough coins: decCoin, $delta");
        }

        $this->dbQuery("UPDATE player SET coin=coin-$delta WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "incCoin",
            clienttranslate('${player_name} spends ${abs_delta} coin(s)'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "abs_delta" => $delta,
                "delta" => -$delta
            ]
        );
    }

    public function incRoyaltyPoints(int $delta, int $player_id): void
    {
        $this->dbQuery("UPDATE player SET player_score=player_score+$delta WHERE player_id=$player_id");

        $this->notifyAllPlayers(
            "incRoyaltyPoints",
            clienttranslate('${player_name} obtains ${delta} Royalty Point(s)'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "delta" => $delta
            ]
        );
    }

    public function obtainStoneDie(int $player_id): bool
    {
        if ($this->globals->get("publicStoneDiceCount") === 0) {
            return false;
        }


        $this->dbQuery("UPDATE player SET stone_die=stone_die+1 WHERE player_id=$player_id");
        $this->globals->inc("publicStoneDiceCount", -1);

        $privateStoneDiceCount = $this->globals->get("privateStoneDiceCount");
        $privateStoneDiceCount[$player_id]++;
        $this->globals->set("privateStoneDiceCount", $privateStoneDiceCount);

        $this->notifyAllPlayers(
            "obtainStoneDie",
            clienttranslate('${player_name} obtains a Stone Die'),
            [
                "player_id" => $player_id,
                "player_name" => $this->getPlayerNameById($player_id),
                "die_id" => 4 - $this->globals->get("publicStoneDiceCount")
            ]
        );

        return true;
    }

    function getRelicsDeck(bool $market = false): array
    {
        if ($market) {
            return $this->relic_cards->getCardsInLocation("market");
        }

        $relicsDeck = $this->relic_cards->getCardsInLocation("deck");
        return $this->hideCards($relicsDeck, true);
    }

    function getRelicsByPlayer(?int $player_id): array
    {
        if ($player_id) {
            return $this->relic_cards->getCardsInLocation("hand", $player_id);
        }

        $players = $this->loadPlayersBasicInfos();
        $relicCards = [];

        foreach ($players as $player_id => $player) {
            $relicCards[$player_id] = $this->relic_cards->getCardsInLocation("hand", $player_id);
        }
    }

    function canPayRelicCost(int $relic_id, int $player_id): bool
    {
        $canPayRelicCost = true;

        $relicCost = $this->relics_info[$relic_id]["cost"];
        $playerGems = $this->getGems($player_id);

        foreach ($playerGems as $gemName => $gemCount) {
            if ($gemName === "coin") {
                continue;
            }

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

    function restoreRelic(int $relicCard_id, int $player_id): void
    {
        $relicCard = $this->relic_cards->getCard($relicCard_id);
        $relic_id = (int) $relicCard["type_arg"];

        $relic_info = $this->relics_info[$relic_id];
        $relicCost = $relic_info["cost"];
        $relicPoints = $relic_info["points"];

        foreach ($relicCost as $gem_id => $gemCost) {
                $this->decGem($gemCost, $gem_id, $player_id);
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
                "i18n" => ["relic_name"]
            ]
        );

        $this->incRoyaltyPoints($relicPoints, $player_id);
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
        $result["tilesBoard"] = $this->getTileBoard();
        $result["playerBoards"] = $this->globals->get("playerBoards");
        $result["revealedTiles"] = $this->globals->get("revealedTiles", []);
        $result["explorers"] = $this->getExplorers();
        $result["gems"] = $this->getGems(null);
        $result["marketValues"] = $this->getMarketValues(null);
        $result["publicStoneDiceCount"] = $this->globals->get("publicStoneDiceCount");
        $result["privateStoneDiceCount"] = $this->globals->get("privateStoneDiceCount");
        $result["relicsInfo"] = $this->relics_info;
        $result["relicsDeck"] = $this->getRelicsDeck(false);
        $result["relicsMarket"] = $this->getRelicsDeck(true);

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

        $this->globals->set("playerBoards", $playerBoards);
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

            if (count($players) === 2) {
                $this->DbQuery("UPDATE tile SET card_location='box', card_location_arg=0 
                WHERE card_location='board' AND 
                card_location_arg IN (1, 6, 7, 13, 14, 19, 20, 26, 27, 32, 33, 39, 40, 45, 46, 52, 53, 58)");
            }
        }

        $relicCards = [];
        foreach ($this->relics_info as $relic_id => $relic_info) {
            $relicCards[] = ["type" => $relic_info["leadGem"], "type_arg" => $relic_id, "nbr" => 1];
        }
        $this->relic_cards->createCards($relicCards, "deck");
        $this->relic_cards->shuffle("deck");
        $this->relic_cards->pickCardsForLocation(5, "deck", "market");

        $this->globals->set("revealsLimit", 0);
        $this->globals->set("publicStoneDiceCount", 4);
        $this->globals->set("activeStoneDice", 0);

        $privateStoneDiceCount = [];
        foreach ($players as $player_id => $player) {
            $privateStoneDiceCount[$player_id] = 0;
            $this->globals->set("privateStoneDiceCount", $privateStoneDiceCount);
        }

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

        throw new feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
