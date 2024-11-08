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
            return $this->game->item_cards->countCardsInLocation("book", $player_id) === 0;
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
                "player_name" => $this->game->getPlayerNameById($player_id),
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
                return !$this->game->globals->get(EPIC_ELIXIR);
            }

            if ($state_id === 2 || $state_id === 20) {
                if ($this->id === 10) {
                    $hasSwapableOpponent = $this->game->castlePlayersCount() < $this->game->getPlayersNumber() - 1;
                    $explorerCard = $this->game->getExplorerByPlayerId($player_id);

                    return $hasSwapableOpponent && $explorerCard["location"] === "board";
                }

                if ($this->id === 11) {
                    $catapultableTiles = $this->game->catapultableTiles($player_id);
                    $canCatapult = !!$catapultableTiles["tiles"] || !!$catapultableTiles["empty"];
                    return $this->game->globals->get(REVEALS_LIMIT) === 0 && $canCatapult;
                }
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
            return !$this->game->globals->get(EPIC_ELIXIR);
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
            $explorableTiles = $this->game->explorableTiles($player_id);
            return !$this->game->globals->get(PROSPEROUS_PICKAXE) && $this->game->getCoins($player_id) >= 3 && !!$explorableTiles;
        }

        return false;
    }

    public function use(int $player_id, #[JsonParam(alphanum: false)] array $args): bool
    {
        if (!$this->isUsable($player_id)) {
            throw new \BgaVisibleSystemException("You can't use this Item now: actUseItem, $this->card_id");
        }

        $eventKey = "message";

        $possiblyCancellable = [3, 4, 9];

        if (in_array($this->id, $possiblyCancellable)) {
            $eventKey = "activateItem";
        }

        $this->game->notifyAllPlayers(
            $eventKey,
            clienttranslate('${player_name} uses the ${item_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerNameById($player_id),
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
            $die_id = (string) $args["die_id"];
            $dieType = (string) $args["dieType"];
            $gemDice = (array) $args["gemDice"];
            $this->luckyLibation($die_id, $dieType, $gemDice, $player_id);
        }

        if ($this->id === 6) {
            $die_id = (string) $args["die_id"];
            $dieType = (string) $args["dieType"];
            $dieModif = (string) $args["dieModif"];
            $this->joltyJackhammer(1, $dieModif, $die_id, $dieType, $player_id);
        }

        if ($this->id === 7) {
            $die_id = (string) $args["die_id"];
            $dieType = (string) $args["dieType"];
            $dieModif = (string) $args["dieModif"];
            $this->joltyJackhammer(2, $dieModif, $die_id, $dieType, $player_id);
        }

        if ($this->id === 8) {
            $gemCard_id = (int) $args["gemCard_id"];
            return $this->axeOfAwesomeness($gemCard_id, $player_id);
        }

        if ($this->id === 9) {
            $tileCard_id = (int) $args["tileCard_id"];
            $this->prosperousPickaxe($tileCard_id, $player_id);
        }

        if ($this->id === 10) {
            $opponent_id = (int) $args["opponent_id"];
            $this->swappingStones($player_id, $opponent_id);
        }

        if ($this->id === 11) {
            $tileCard_id = (int) $args["tileCard_id"];
            $this->cleverCatapult($tileCard_id, $player_id);
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
            $this->game->decGem(1, $gem_id, [$gemCard_id => $gemCard], $player_id, false, true);
        }

        $this->game->incGem(1, $newGem_id, $player_id);
    }

    public function regalReferenceBook(#[IntParam(1, 24)] int $relic_id, int $player_id): void
    {
        $deckSelectQuery = $this->game->deckSelectQuery;
        $queryResult = $this->game->getCollectionFromDB("$deckSelectQuery from relic WHERE card_type_arg=$relic_id");
        $relicCard = array_shift($queryResult);

        if ($relicCard["location"] === "hand" || $relicCard["location"] === "book") {
            throw new \BgaVisibleSystemException("You can't use the Regal Reference Book with this Relic: $relic_id");
        }

        $relicCard_id = (int) $relicCard["id"];

        $this->game->notifyAllPlayers(
            "regalReferenceBook",
            clienttranslate('${player_name} reserves the ${relic_name}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerNameById($player_id),
                "relic_name" => $this->game->relics_info[$relic_id]["tr_name"],
                "relicCard" => $relicCard,
                "itemCard" => $this->card,
                "preserve" => ["relicCard"],
            ]
        );

        $this->game->item_cards->moveCard($this->card_id, "book", $player_id);
        $this->game->relic_cards->moveCard($relicCard_id, "book", $player_id);
        $this->game->replaceRelic();
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
                "player_name" => $this->game->getPlayerNameById($player_id),
                "gem_label" => $this->game->gems_info[$gem_id]["tr_name"],
                "preserve" => ["gem_id"],
                "gem_id" => $gem_id,
            ]
        );

        return $this->game->incGem(1, $gem_id, $player_id, null, false, true);
    }

    public function luckyLibation(
        #[StringParam(alphanum_dash: true)] ?string $die_id,
        #[StringParam(alphanum: true)] string $dieType,
        #[JsonParam(alphanum: false)] ?array $gemDice,
        int $player_id
    ): bool {
        if ($dieType === "gem") {
            foreach ($gemDice as $die) {
                $die_id = $die["id"];
                $dieType = $die["type"];

                $gem_id = $die_id;
                $gemName = $this->game->gems_info[$gem_id]["name"];

                $oldFace = $this->game->globals->get("$gemName:MarketValue");
                $newFace = $this->game->rollDie($die_id, $player_id, "gem");
                $delta = $newFace - $oldFace;

                $this->game->updateMarketValue($delta, $gem_id, true);
            }

            $this->game->notifyAllPlayers(
                "syncDieRolls",
                "",
                []
            );

            return true;
        }

        $rolledDice = $this->game->globals->get(ROLLED_DICE, []);
        $die = $rolledDice[$die_id];
        $dieType = $die["type"];
        $oldFace = $die["face"];

        if (!array_key_exists($die_id, $rolledDice)) {
            throw new \BgaVisibleSystemException("You didn't roll this die: Lucky Libation, $die_id");
        }

        $newFace = (int) $this->game->rollDie($die_id, $player_id, $dieType);
        $delta = $newFace - $oldFace;

        $tileCard = $this->game->currentTile($player_id);
        $gem_id = (int) $this->game->currentTile($player_id, true);
        $gemName = $this->game->gems_info[$gem_id]["name"];
        $gemMarketValue = (int) $this->game->globals->get("$gemName:MarketValue");

        if ($newFace < 1) {
            $newFace += 6;
        }

        if ($newFace > 6) {
            $newFace -= 6;
        }

        $rolledDice =  $this->game->globals->get(ROLLED_DICE, []);
        $rolledDice[$die_id] = ["id" => $die_id, "type" => $dieType, "face" => $newFace];
        $this->game->globals->set(ROLLED_DICE, $rolledDice);

        if ($oldFace < $gemMarketValue) {
            if ($newFace >= $gemMarketValue) {
                return $this->game->incGem(1, $gem_id, $player_id, $tileCard, true);
            }
        }

        if ($newFace < $gemMarketValue) {
            $this->game->discardGem($player_id, null, $gem_id);
        }

        return true;
    }

    public function joltyJackhammer(
        #[IntParam(min: 1, max: 2)] int $delta,
        #[StringParam(enum: ["negative", "positive"])] string $dieModif,
        #[StringParam(alphanum_dash: true)] string $die_id,
        #[StringParam(enum: ["gem", "stone", "mining"])] string $dieType,
        int $player_id
    ): bool {
        $delta = $dieModif === "positive" ? $delta : -$delta;

        if ($dieType === "gem") {
            $gem_id = (int) $die_id;

            $newFace = $this->game->updateMarketValue($delta, $gem_id);
            $oldFace = $newFace - $delta;

            return true;
        }

        $rolledDice = $this->game->globals->get(ROLLED_DICE, []);
        $die = $rolledDice[$die_id];
        $dieType = $die["type"];

        if (!array_key_exists($die_id, $rolledDice)) {
            throw new \BgaVisibleSystemException("You didn't roll this die: Jolty Jackhammer / Dazzling Dynamite, $die_id");
        }

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

        $this->game->notifyAllPlayers(
            "joltyJackhammer",
            clienttranslate('${player_name} modifies a ${type_label} Die from ${oldFace} to ${face}'),
            [
                "player_id" => $player_id,
                "player_name" => $this->game->getPlayerNameById($player_id),
                "type_label" => $this->game->dice_info[$dieType],
                "die_id" => $die_id,
                "oldFace" => $oldFace,
                "face" => $newFace,
                "type" => $dieType,
                "i18n" => ["type_label"],
            ]
        );

        $rolledDice =  $this->game->globals->get(ROLLED_DICE, []);
        $rolledDice[$die_id] = ["id" => $die_id, "type" => $dieType, "face" => $newFace];
        $this->game->globals->set(ROLLED_DICE, $rolledDice);

        if ($oldFace < $gemMarketValue) {
            if ($newFace >= $gemMarketValue) {
                return $this->game->incGem(1, $gem_id, $player_id, $tileCard, true);
            }
        }

        if ($newFace < $gemMarketValue) {
            $this->game->discardGem($player_id, null, $gem_id);
        }

        return true;
    }

    public function prosperousPickaxe(#[IntParam(min: 1, max: 58)] int $tileCard_id, int $player_id): void
    {
        $explorableTiles = $this->game->explorableTiles($player_id, true);
        if (!array_key_exists($tileCard_id, $explorableTiles)) {
            throw new \BgaVisibleSystemException("You can't pick this tile for the Prosperous Pickaxe: $tileCard_id");
        }

        $tileCard = $this->game->tile_cards->getCard($tileCard_id);
        $this->game->globals->set(PROSPEROUS_PICKAXE, $tileCard);
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
                "player_name" => $this->game->getPlayerNameById($player_id),
                "player_id" => $opponent_id,
                "player_name2" => $this->game->getPlayerNameById($opponent_id),
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
            $tileHex = abs($tileCard_id);
            $explorerCard = $this->game->getExplorerByPlayerId($player_id);
            $this->game->explorer_cards->moveCard($explorerCard["id"], "board", $tileHex);

            $this->game->notifyAllPlayers(
                "cleverCatapult",
                clienttranslate('${player_name} jumps to an empty tile space (hex ${hex})'),
                [
                    "player_id" => $player_id,
                    "player_name" => $this->game->getPlayerNameById($player_id),
                    "hex" => abs($tileCard_id),
                    "explorerCard" => $explorerCard,
                ]
            );

            return;
        }

        if (!array_key_exists($tileCard_id, $revealedTiles)) {
            $this->game->actRevealTile($tileCard_id, true, true);
        }

        $this->game->actMoveExplorer($tileCard_id, true);
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
                "player_name" => $this->game->getPlayerNameById($player_id),
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
    }
}
