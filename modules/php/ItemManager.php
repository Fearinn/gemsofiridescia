<?php

declare(strict_types=1);

namespace Bga\Games\GemsOfIridescia;

use Bga\GameFramework\Actions\Types\IntArrayParam;
use \Bga\GameFramework\Actions\Types\IntParam;
use \Bga\GameFramework\Actions\Types\JsonParam;
use Bga\GameFramework\Actions\Types\StringParam;

class ItemManager
{
    public function __construct(int $itemCard_id, \Table $game)
    {
        $this->game = $game;

        $itemCard = $this->game->item_cards->getCard($itemCard_id);
        $item_id = (int) $itemCard["type_arg"];

        $this->card = $itemCard;
        $this->card_id = $itemCard_id;

        $info = $game->items_info[$item_id];

        $this->id = $item_id;
        $this->tr_name = $info["tr_name"];
        $this->cost = $info["cost"];
    }

    public function checkLocation(string $location, int $location_arg = null): bool
    {
        $confirmLocation = true;

        $card_location_arg = (int) $this->card["location_arg"];

        if ($this->card["location"] !== $location || ($location_arg && $location_arg !== $card_location_arg)) {
            $confirmLocation = false;
        }

        return $confirmLocation;
    }

    public function isBuyable(int $player_id): bool
    {
        if ($this->id === 2) {
            $hasBook = $this->game->item_cards->countCardsInLocation("book", $player_id) > 0;

            if ($hasBook) {
                return false;
            }
        }

        if ($this->id === 4) {
            $underElixirEffect = $this->game->globals->get(EPIC_ELIXIR) || $this->game->globals->get(EPIC_ELIXIR_TURN);

            if ($underElixirEffect) {
                return false;
            }
        }

        $hasEnoughCoins = $this->game->getCoins($player_id) >= $this->cost;
        $hasSameItem = !!$this->game->getCollectionFromDB("SELECT card_id from item 
        WHERE card_type_arg=$this->id AND card_location='hand' AND card_location_arg=$player_id");

        return !$this->game->globals->get(HAS_BOUGHT_ITEM) && $this->checkLocation("market") && $hasEnoughCoins && !$hasSameItem;
    }

    public function buy(int $player_id): void
    {
        if (!$this->isBuyable($player_id)) {
            throw new \BgaVisibleSystemException("You can't buy this item now: actBuyItem, $this->card_id");
        }

        $this->game->decCoin($this->cost, $player_id);

        $this->game->notifyAllPlayers(
            "buyItem",
            clienttranslate('${player_name} buys the ${item_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerOrRhomNameById($player_id),
                "itemCard" => $this->card,
                "item_name" => $this->tr_name,
                "i18n" => ["item_name"],
                "preserve" => ["item_id"],
                "item_id" => $this->id,
            ]
        );

        $this->game->item_cards->moveCard($this->card_id, "hand", $player_id);
        $this->game->globals->set(HAS_BOUGHT_ITEM, true);

        $this->game->replaceItem();
    }

    public function isUsable(int $player_id): bool
    {
        $state_id = (int) $this->game->gamestate->state_id();

        if (!$this->checkLocation("hand", $player_id)) {
            return false;
        }

        if ($state_id !== 4) {
            if ($this->id === 4) {
                return $this->canUseEpicElixir($player_id);
            }

            $preRevealStates = [2, 20, 22];

            if (in_array($state_id, $preRevealStates)) {
                if ($this->id === 10) {
                    $hasSwapableOpponent = $this->game->castlePlayersCount() < $this->game->getPlayersNumberNoZombie() - 1;
                    $explorerCard = $this->game->getExplorerByPlayerId($player_id);

                    return $this->game->globals->get(REVEALS_LIMIT) === 0 && $hasSwapableOpponent
                        && $explorerCard["location"] === "board";
                }

                if ($this->id === 11) {
                    $catapultableTiles = $this->game->catapultableTiles($player_id);
                    $canCatapult = !!$catapultableTiles["tiles"] || !!$catapultableTiles["empty"];
                    return $this->game->globals->get(REVEALS_LIMIT) === 0 && $canCatapult;
                }
            }

            $wellModifiers = [5, 6, 7];

            if ($state_id === 40 && in_array($this->id, $wellModifiers)) {
                return true;
            }

            return false;
        }

        if ($this->id === 1) {
            return $this->game->getTotalGemsCount($player_id) >= 2;
        }

        if ($this->id === 2) {
            $bookableRelics = $this->game->bookableRelics();
            return count($bookableRelics) > 0;
        }

        if ($this->id === 3) {
            return !$this->game->globals->get(MARVELOUS_CART) && $this->game->getCoins($player_id) >= 3;
        }

        if ($this->id === 4) {
            return $this->canUseEpicElixir($player_id);
        }

        if ($this->id === 5) {
            return true;
        }

        if ($this->id === 6) {
            return true;
        }

        if ($this->id === 7) {
            return true;
        }

        if ($this->id === 8) {
            return $this->game->getTotalGemsCount($player_id) > 0;
        }

        if ($this->id === 9) {
            $prosperousTiles = $this->game->prosperousTiles($player_id);
            return !$this->game->globals->get(PROSPEROUS_PICKAXE) && $this->game->getCoins($player_id) >= 3 && !!$prosperousTiles;
        }

        if ($this->id === 12) {
            return true;
        }

        return false;
    }

    public function use(int $player_id, #[JsonParam(alphanum: false)] array $args): bool
    {
        if (!$this->isUsable($player_id)) {
            throw new \BgaUserException($this->game->_("You can't use this item now"));
        }

        $eventKey = "message";

        $possiblyCancellable = [3, 4, 9, 12];

        if (in_array($this->id, $possiblyCancellable)) {
            $eventKey = "activateItem";
            $this->game->item_cards->moveCard($this->card_id, "active", $player_id);
        }

        $this->game->notifyAllPlayers(
            $eventKey,
            clienttranslate('${player_name} uses the ${item_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerOrRhomNameById($player_id),
                "itemCard" => $this->card,
                "item_name" => $this->tr_name,
                "i18n" => ["item_name"],
                "preserve" => ["item_id"],
                "item_id" => $this->id
            ]
        );

        if (!in_array($this->id, $possiblyCancellable)) {
            $this->discard();
        }

        if ($this->id === 1) {
            $oldGemCards_ids = (array) $args["oldGemCards_ids"];
            $newGem_id = (int) $args["newGem_id"];

            $this->cauldronOfFortune($oldGemCards_ids, $newGem_id, $player_id);
        }

        if ($this->id === 2) {
            $relic_id = (int) $args["relic_id"];
            $this->regalReferenceBook($relic_id, $player_id);
        }

        if ($this->id === 3) {
            $this->marvelousCart();
        }

        if ($this->id === 4) {
            $this->epicElixir();
        }

        if ($this->id === 5) {
            $dice = (array) $args["dice"];
            $duringWell = $this->duringWell();
            return $this->luckyLibation($dice, $player_id, $duringWell);
        }

        if ($this->id === 6) {
            $die_id = (string) $args["die_id"];
            $dieType = (string) $args["dieType"];
            $delta = (int) $args["delta"];
            $duringWell = $this->duringWell();

            if (abs($delta) !== 1) {
                throw new \BgaVisibleSystemException("Invalid delta for Jolty Jackhammer: $delta");
            }

            return $this->joltyJackhammer($delta, $die_id, $dieType, $player_id, false, $duringWell);
        }

        if ($this->id === 7) {
            $die_id = (string) $args["die_id"];
            $dieType = (string) $args["dieType"];
            $delta = (int) $args["delta"];
            $duringWell = $this->duringWell();

            return $this->joltyJackhammer($delta, $die_id, $dieType, $player_id, true, $duringWell);
        }

        if ($this->id === 8) {
            $gemCard_id = (int) $args["gemCard_id"];
            return $this->axeOfAwesomeness($gemCard_id, $player_id);
        }

        if ($this->id === 9) {
            $tileCard_id = (int) $args["tileCard_id"];
            $rainbowGem = (int) $args["rainbowGem"];
            $this->prosperousPickaxe($tileCard_id, $player_id, $rainbowGem);
        }

        if ($this->id === 10) {
            $opponent_id = (int) $args["opponent_id"];
            $this->swappingStones($player_id, $opponent_id);
        }

        if ($this->id === 11) {
            $tileCard_id = (int) $args["tileCard_id"];
            $this->cleverCatapult($tileCard_id, $player_id);
        }

        if ($this->id === 12) {
            $this->wishingWell($player_id);
        }

        return true;
    }

    public function cauldronOfFortune(#[IntArrayParam(min: 1, max: 144)] array $oldGemCards_ids, #[IntParam(min: 1, max: 4)] int $newGem_id, int $player_id): void
    {
        if (count($oldGemCards_ids) !== 2) {
            throw new \BgaVisibleSystemException("You must select exactly 2 Gems: Cauldron of Fortune");
        }

        foreach ($oldGemCards_ids as $gemCard_id) {
            $gemCard =  $this->game->gem_cards->getCard($gemCard_id);
            $this->game->checkCardLocation($gemCard, "hand", $player_id);

            $gem_id = (int) $gemCard["type_arg"];
            $this->game->decGem($gem_id, [$gemCard], $player_id, false, true);
        }

        $this->game->incGem(1, $newGem_id, $player_id);
    }

    public function regalReferenceBook(#[IntParam(1, 24)] int $relic_id, int $player_id): void
    {
        $deckSelectQuery = $this->game->deckSelectQuery;
        $queryResult = $this->game->getCollectionFromDB("$deckSelectQuery from relic WHERE card_type_arg=$relic_id");
        $relicCard = array_shift($queryResult);

        if ($relicCard["location"] !== "deck" && $relicCard["location"] !== "market") {
            throw new \BgaVisibleSystemException("You can't use the Regal Reference Book with this Relic: $relic_id");
        }

        $this->game->relic_cards->shuffle("deck");
        $relicsDeckTop = $this->game->getRelicsDeck(true);

        $relicCard_id = (int) $relicCard["id"];
        $this->game->item_cards->moveCard($this->card_id, "book", $player_id);
        $this->game->relic_cards->moveCard($relicCard_id, "book", $player_id);

        $this->game->notifyAllPlayers(
            "regalReferenceBook",
            clienttranslate('${player_name} reserves the ${relic_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerOrRhomNameById($player_id),
                "relic_name" => $this->game->relics_info[$relic_id]["tr_name"],
                "relicCard" => $relicCard,
                "relicsDeckCount" => $this->game->relic_cards->countCardsInLocation("deck"),
                "relicsDeckTop" => $relicsDeckTop,
                "itemCard" => $this->card,
                "preserve" => ["relicCard"],
            ]
        );

        if ($relicCard["location"] === "market") {
            $this->game->replaceRelic();
        }
    }

    public function marvelousCart(): void
    {
        $this->game->globals->set(MARVELOUS_CART, true);
    }

    public function epicElixir(): void
    {
        $this->game->globals->set(EPIC_ELIXIR, true);
    }

    public function axeOfAwesomeness(#[IntParam(min: 1, max: 144)] int $gemCard_id, int $player_id): bool
    {
        $gemCard =  $this->game->gem_cards->getCard($gemCard_id);
        $this->game->checkCardLocation($gemCard, "hand", $player_id);

        $gem_id = (int) $gemCard["type_arg"];

        $this->game->notifyAllPlayers(
            "axeOfAwesomeness",
            clienttranslate('${player_name} splits 1 ${gem_label} into 2'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerOrRhomNameById($player_id),
                "gem_label" => $this->game->gems_info[$gem_id]["tr_name"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );

        return $this->game->incGem(1, $gem_id, $player_id, null, false, true);
    }

    public function luckyLibation(
        #[JsonParam(alphanum: false)] array $dice,
        int $player_id,
        bool $duringWell,
    ): bool {
        $minedGemsCount = 0;
        $lostGemsCount = 0;
        $diceType = $this->getDiceType($dice);

        foreach ($dice as $die) {
            $die_id = $die["id"];

            if ($diceType === "gem") {
                $gem_id = $die_id;
                $gemName = $this->game->gems_info[$gem_id]["name"];

                $oldFace = $this->game->globals->get("$gemName:MarketValue");
                $newFace = $this->game->rollDie($die_id, $player_id, "gem", false);
                $delta = $newFace - $oldFace;

                $this->game->updateMarketValue($delta, $gem_id, true);
                $this->game->notifyAllPlayers(
                    "syncDieRolls",
                    "",
                    []
                );

                continue;
            }

            $rerollableDice = $this->game->globals->get(REROLLABLE_DICE, []);

            if (!array_key_exists($die_id, $rerollableDice)) {
                throw new \BgaVisibleSystemException("You can't reroll this die: Lucky Libation, $die_id");
            }

            $die = $rerollableDice[$die_id];
            $dieType = $die["type"];
            $oldFace = $die["face"];

            $newFace = (int) $this->game->rollDie($die_id, $player_id, $dieType, false);
            $delta = $newFace - $oldFace;

            $tileCard = $this->game->currentTile($player_id);
            $gem_id = (int) $this->game->currentTile($player_id, true);
            $gemName = $this->game->gems_info[$gem_id]["name"];
            $gemMarketValue = (int) $this->game->globals->get("$gemName:MarketValue");

            if ($oldFace < $gemMarketValue && $newFace >= $gemMarketValue) {
                $minedGemsCount++;
            }

            if ($oldFace >= $gemMarketValue && $newFace < $gemMarketValue) {
                $lostGemsCount++;
            }

            if ($duringWell) {
                $this->updateWishingWell($newFace);
            }
        }

        if ($diceType === "gem") {
            if ($duringWell) {
                $this->game->gamestate->nextState("pickWellGem");
            } else {
                $this->game->globals->set(REROLLABLE_DICE, []);
            }
            return true;
        }

        $gemsDelta = $minedGemsCount - $lostGemsCount;
        if ($this->game->globals->get(MARVELOUS_CART)) {
            $gemsDelta *= 2;
        }

        if ($gemsDelta === 0) {
            return true;
        }

        $fullCargo = false;

        if ($gemsDelta < 0) {
            $this->game->discardGems($player_id, null, $gem_id, abs($gemsDelta));
            $this->handleProsperousGem($gemsDelta, $player_id);
        }

        if ($gemsDelta > 0) {
            $fullCargo = !$this->game->incGem($gemsDelta, $gem_id, $player_id, $tileCard, true) ||
                !$this->handleProsperousGem($gemsDelta, $player_id);
        }

        return !$fullCargo;
    }

    public function joltyJackhammer(
        #[IntParam(min: -2, max: 2)] int $delta,
        #[StringParam(alphanum_dash: true)] string $die_id,
        #[StringParam(enum: ["gem", "stone", "mining"])] string $dieType,
        int $player_id,
        bool $isDynamite,
        bool $duringWell,
    ): bool {
        $itemName = $isDynamite ? "Dazzling Dynamite" : "Jolty Jackhammer";

        if ($delta === 0) {
            throw new \BgaVisibleSystemException("Invalid delta for $itemName: 0");
        }

        if ($dieType === "gem") {
            $gem_id = (int) $die_id;

            if ($isDynamite) {
                $this->game->notifyAllPlayers(
                    "dynamiteSFX",
                    "",
                    [],
                );
            }

            $newFace = $this->game->updateMarketValue($delta, $gem_id);
            $oldFace = $newFace - $delta;

            if ($duringWell) {
                $this->game->gamestate->nextState("pickWellGem");
            } else {
                $this->game->globals->set(REROLLABLE_DICE, []);
            }
            return true;
        }

        $rerollableDice = $this->game->globals->get(REROLLABLE_DICE, []);

        if (!array_key_exists($die_id, $rerollableDice)) {
            throw new \BgaVisibleSystemException("You didn't roll this die: $itemName, $die_id");
        }

        $die = $rerollableDice[$die_id];
        $dieType = $die["type"];

        $tileCard = $this->game->currentTile($player_id);
        $gem_id = (int) $this->game->currentTile($player_id, true);
        $gemName = $this->game->gems_info[$gem_id]["name"];
        $gemMarketValue = (int) $this->game->globals->get("$gemName:MarketValue");

        $oldFace = $die["face"];
        $newFace = $oldFace + $delta;

        if ($newFace < 1) {
            $newFace += 6;
        }

        if ($newFace > 6) {
            $newFace -= 6;
        }

        if ($isDynamite) {
            $this->game->notifyAllPlayers(
                "dynamiteSFX",
                "",
                [],
            );
        }

        $this->game->notifyAllPlayers(
            "joltyJackhammer",
            clienttranslate('${player_name} modifies a ${type_label} Die from ${oldFace} to ${face}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerOrRhomNameById($player_id),
                "type_label" => $this->game->dice_info[$dieType],
                "die_id" => $die_id,
                "oldFace" => $oldFace,
                "face" => $newFace,
                "type" => $dieType,
                "i18n" => ["type_label"],
            ]
        );

        $die = ["id" => $die_id, "type" => $dieType, "face" => $newFace];
        $this->game->updateRolledDice($die);

        if ($duringWell) {
            $this->updateWishingWell($newFace);
            $this->game->gamestate->nextState("pickWellGem");
            return true;
        }

        $fullCargo = false;
        $delta = $this->game->globals->get(MARVELOUS_CART) ? 2 : 1;

        if ($oldFace < $gemMarketValue && $newFace >= $gemMarketValue) {
            $fullCargo = !$this->game->incGem($delta, $gem_id, $player_id, $tileCard, true) ||
                !$this->handleProsperousGem($delta, $player_id);
        }

        if ($oldFace >= $gemMarketValue && $newFace < $gemMarketValue) {
            $this->game->discardGems($player_id, null, $gem_id, $delta);
            $this->handleProsperousGem(-$delta, $player_id);
        }

        return !$fullCargo;
    }

    public function prosperousPickaxe(#[IntParam(min: 1, max: 58)] int $tileCard_id, int $player_id, ?int $rainbowGem = null): void
    {
        $prosperousTiles = $this->game->prosperousTiles($player_id, true);

        if (!array_key_exists($tileCard_id, $prosperousTiles)) {
            throw new \BgaVisibleSystemException("You can't pick this tile for the Prosperous Pickaxe: $tileCard_id");
        }

        $tileCard = $this->game->tile_cards->getCard($tileCard_id);
        $tile_id = (int) $tileCard["type_arg"];
        $gem_id = (int) $this->game->tiles_info[$tile_id]["gem"];

        if ($gem_id % 10 === 0) {
            $gem_id = $rainbowGem;
        }

        $tileCard["gem"] = $gem_id;
        $this->game->globals->set(PROSPEROUS_PICKAXE, $tileCard);

        $this->game->notifyAllPlayers(
            "prosperousPickaxe",
            clienttranslate('${player_name} picks a ${tile} (hex ${hex}) for the ${item_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerNameById($player_id),
                "item_id" => $this->id,
                "item_name" => clienttranslate("Prosperous Pickaxe"),
                "hex" => $tileCard["location_arg"],
                "tileCard" => $tileCard,
                "preserve" => ["tileCard", "item_id"],
                "i18n" => ["tile", "item_name"],
                "tile" => clienttranslate("tile"),
            ],
        );
    }

    public function swappingStones(int $player_id, int $opponent_id): void
    {
        $this->game->checkPlayer($opponent_id);

        if ($player_id === $opponent_id) {
            throw new \BgaVisibleSystemException("You can't select yourself for Swapping Stones");
        }

        $currentExplorerCard = $this->game->getExplorerByPlayerId($player_id);
        $currentExplorerCard_id = (int) $currentExplorerCard["id"];

        $opponentExplorerCard = $this->game->getExplorerByPlayerId($opponent_id);
        $opponentExplorerCard_id = (int) $opponentExplorerCard["id"];

        $currentHex = (int) $currentExplorerCard["location_arg"];
        $opponentHex = (int) $opponentExplorerCard["location_arg"];

        $this->game->explorer_cards->moveCard($currentExplorerCard_id, "board", $opponentHex);
        $this->game->explorer_cards->moveCard($opponentExplorerCard_id, "board", $currentHex);

        $currentExplorerCard = $this->game->getExplorerByPlayerId($player_id);
        $opponentExplorerCard = $this->game->getExplorerByPlayerId($opponent_id);

        $this->game->notifyAllPlayers(
            "swappingStones",
            clienttranslate('${player_name} swaps location with ${player_name2}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerOrRhomNameById($player_id),
                "player_id" => $opponent_id,
                "player_name2" => $this->game->getPlayerOrRhomNameById($opponent_id),
                "currentExplorerCard" => $currentExplorerCard,
                "opponentExplorerCard" => $opponentExplorerCard,
                "currentHex" => $currentHex,
                "opponentHex" => $opponentHex,
            ]
        );
    }

    public function cleverCatapult(#[IntParam(min: -58, max: 58)] int $tileCard_id, int $player_id): void
    {
        $revealedTiles = $this->game->globals->get(REVEALED_TILES, []);

        if ($tileCard_id < 0) {
            $hex = abs($tileCard_id);
            $explorerCard = $this->game->getExplorerByPlayerId($player_id);
            $this->game->explorer_cards->moveCard($explorerCard["id"], "board", $hex);

            $this->game->notifyAllPlayers(
                "cleverCatapult",
                clienttranslate('${player_name} jumps to an empty tile space (hex ${hex})'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->game->getPlayerNameById($player_id),
                    "hex" => $hex,
                    "explorerCard" => $explorerCard,
                ]
            );

            return;
        }

        if (!array_key_exists($tileCard_id, $revealedTiles)) {
            $this->game->actRevealTile(null, $tileCard_id, true, true);
        }

        $this->game->actMoveExplorer(null, $tileCard_id, true);
    }

    public function wishingWell(int $player_id): void
    {
        $die_1 = (int) $this->game->rollDie("1-$player_id", $player_id, "mining");
        $die_2 = (int) $this->game->rollDie("2-$player_id", $player_id, "mining");

        $max = (int) max([$die_1, $die_2]);
        $min = (int) min([$die_1, $die_2]);

        $this->game->globals->set(WISHING_WELL, ["card_id" => $this->card_id, "max" => $max, "min" => $min]);
    }

    public function wishingWell2(#[IntParam(min: 1, max: 4)] int $gem_id, int $player_id): bool
    {
        $marketValue = $this->game->getMarketValues($gem_id);
        $registeredWell = $this->game->globals->get(WISHING_WELL);

        if ($registeredWell === null) {
            throw new \BgaVisibleSystemException("You didn't use the Wishing Well");
        }

        $maxValue = $registeredWell["max"];

        if ($maxValue < $marketValue) {
            throw new \BgaVisibleSystemException("You can't gain this gem from the Wishing Well: $gem_id, $marketValue, $maxValue");
        }

        $this->disable();
        $this->discard();

        return $this->game->incGem(1, $gem_id, $player_id);
    }

    public function isCancellable($player_id): bool
    {
        if (!$this->checkLocation("active", $player_id)) {
            return false;
        }

        $state_id = (int) $this->game->gamestate->state_id();

        if ($state_id !== 4) {
            if ($this->id === 4) {
                return $this->game->globals->get(EPIC_ELIXIR);
            }

            return false;
        }

        if ($this->id === 3) {
            return !$this->game->globals->get(HAS_MINED) && $this->game->globals->get(MARVELOUS_CART);
        }

        if ($this->id === 4) {
            return $this->game->globals->get(EPIC_ELIXIR);
        }

        if ($this->id === 9) {
            return !$this->game->globals->get(HAS_MINED) && $this->game->globals->get(PROSPEROUS_PICKAXE);
        }

        return false;
    }

    public function undo($player_id): void
    {
        if (!$this->isCancellable($player_id)) {
            throw new \BgaVisibleSystemException("You can't cancel this Item now: actUndoItem: $this->card_id");
        }

        $this->game->notifyAllPlayers(
            "cancelItem",
            clienttranslate('${player_name} cancels the ${item_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerOrRhomNameById($player_id),
                "itemCard" => $this->card,
                "item_name" => $this->tr_name,
                "i18n" => ["item_name"],
                "preserve" => ["item_id"],
                "item_id" => $this->id,
            ]
        );

        $this->game->item_cards->moveCard($this->card_id, "hand", $player_id);

        if ($this->id === 3) {
            $this->game->globals->set(MARVELOUS_CART, false);
        }

        if ($this->id === 4) {
            $this->game->globals->set(EPIC_ELIXIR, false);
        }

        if ($this->id === 9) {
            $this->game->globals->set(PROSPEROUS_PICKAXE, null);
        }
    }

    public function discard(): void
    {
        $this->game->notifyAllPlayers(
            "discardItem",
            "",
            [
                "itemCard" => $this->card
            ]
        );

        $this->game->item_cards->moveCard($this->card_id, "discard");
    }

    public function disable(): void
    {
        if ($this->id === 3) {
            $this->game->globals->set(MARVELOUS_CART, false);
        }

        if ($this->id === 4) {
            $this->game->globals->set(EPIC_ELIXIR, false);
        }

        if ($this->id === 9) {
            $this->game->globals->set(PROSPEROUS_PICKAXE, null);
        }

        if ($this->id === 12) {
            $this->game->globals->set(REROLLABLE_DICE, []);
            $this->game->globals->set(WISHING_WELL, null);
        }
    }

    public function handleProsperousGem(int $delta, int $player_id): bool
    {
        $tileCard = $this->game->globals->get(PROSPEROUS_PICKAXE);

        if (!$tileCard) {
            return true;
        }

        $gem_id = (int) $tileCard["gem"];

        if ($delta < 0) {
            $this->game->discardGems($player_id, null, $gem_id, abs($delta));
            return true;
        }

        return $this->game->incGem($delta, $gem_id, $player_id, $tileCard, true);
    }

    public function canUseEpicElixir(int $player_id): bool
    {
        $isLastPlayer = $this->game->castlePlayersCount() === $this->game->getPlayersNumberNoZombie() - 1;

        $canOnlyMoveToCastle = true;
        if ($isLastPlayer) {
            $revealableTiles = $this->game->revealableTiles($player_id);
            $explorableTiles = $this->game->explorableTiles($player_id);

            $adjacentTiles = array_merge($revealableTiles, $explorableTiles);

            foreach ($adjacentTiles as $tileCard) {
                $region_id = (int) $tileCard["type"];

                if ($region_id !== 5) {
                    $canOnlyMoveToCastle = false;
                    break;
                }
            }
        }

        $isLastTurn = $isLastPlayer && $canOnlyMoveToCastle;
        return !$this->game->globals->get(EPIC_ELIXIR) && !$isLastTurn;
    }

    public function getDiceType(array $dice): string
    {
        $diceType = null;
        foreach ($dice as $die) {
            $dieType = $die["type"];
            if ($diceType === null) {
                $diceType = $dieType;
                continue;
            }

            if ($diceType === "gem" && $diceType !== $dieType) {
                throw new \BgaVisibleSystemException("You can't roll Gem dice and Mining Dice simultaneously: Lucky Libation, $dieType, $diceType");
            }
        }

        return $diceType;
    }

    public function updateWishingWell(int $newFace): array
    {
        $registeredWell = $this->game->globals->get(WISHING_WELL);

        $registeredMax = (int) $registeredWell["max"];
        $registeredMin = (int) $registeredWell["min"];

        if ($newFace > $registeredMax) {
            $registeredWell["max"] = $newFace;
        }

        if ($newFace < $registeredMax) {
            if ($registeredMax > $registeredMin) {
                $registeredWell["max"] = $newFace;
            }

            if ($registeredMax === $registeredMin) {
                $registeredWell["min"] = $newFace;
            }
        }

        $this->game->globals->set(WISHING_WELL, $registeredWell);
        return $registeredWell;
    }

    public function duringWell(): bool {
        return (int) $this->game->gamestate->state_id() === (int) ST_PICK_WELL_GEM;
    }
}
