<?php

declare(strict_types=1);

namespace Bga\Games\GemsOfIridescia;

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

            if ($state_id === 2) {
                if ($this->id === 10) {
                    $hasSwapableOpponent = $this->game->castlePlayersCount() < $this->game->getPlayersNumber() - 1;
                    $explorerCard = $this->game->getExplorerByPlayerId($player_id);

                    return !$this->game->globals->get(SWAPPING_STONES) && $hasSwapableOpponent && $explorerCard["location"] === "board";
                }

                if ($this->id === 11) {
                    $canExpandTiles = (!!$this->game->expandedRevealableTiles($player_id) || !!$this->game->expandedExplorableTiles($player_id));
                    return $this->game->globals->get(REVEALS_LIMIT) === 0 && $canExpandTiles;
                }
            }

            return false;
        }

        if ($this->id === 1) {
            return $this->game->getTotalGemsCount($player_id) >= 2;
        }

        if ($this->id === 3) {
            return !$this->game->globals->get(MARVELOUS_CART) && $this->game->getCoins($player_id) >= 3;
        }

        if ($this->id === 4) {
            return !$this->game->globals->get(EPIC_ELIXIR);
        }

        // if ($this->id === 8) {
        //     return $this->game->getTotalGemsCount($player_id) >= 1;
        // }

        // if ($this->id === 9) {
        //     return $this->game->getCoins($player_id) >= 3;
        // }

        return false;
    }

    public function use(int $player_id, array $args)
    {
        if (!$this->isUsable($player_id)) {
            throw new \BgaVisibleSystemException("You can't use this Item now: actUseItem, $this->card_id");
        }

        $this->game->notifyAllPlayers(
            "useItem",
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

        $this->game->item_cards->moveCard($this->card_id, "used", $player_id);

        if ($this->id === 1) {
            $oldGemCards_ids = $args["oldGemCards_ids"];
            $newGem_id = (int) $args["newGem_id"];

            $this->cauldronOfFortune($oldGemCards_ids, $newGem_id, $player_id);
        }

        if ($this->id === 3) {
            $this->marvelousCart();
        }

        if ($this->id === 4) {
            $this->epicElixir();
        }

        if ($this->id === 10) {
            $opponent_id = (int) $args["opponent_id"];
            $this->game->checkPlayer($opponent_id);

            $this->swappingStones($player_id, $opponent_id);
        }
    }

    public function cauldronOfFortune(array $oldGemCards_ids, int $newGem_id, int $player_id)
    {
        if (!in_array($newGem_id, range(1, 4))) {
            throw new \BgaVisibleSystemException("This Gem doesn't exist: Cauldron of Fortune, $newGem_id'");
        }

        if (count($oldGemCards_ids) !== 2) {
            throw new \BgaVisibleSystemException("You must select exactly 2 Gems: Cauldron of Fortune");
        }

        foreach ($oldGemCards_ids as $gemCard_id) {
            $gemCard =  $this->game->gem_cards->getCard($gemCard_id);
            $this->game->checkCardLocation($gemCard, "hand");

            $gem_id = (int) $gemCard["type_arg"];
            $this->game->decGem(1, $gem_id, [$gemCard], $player_id, false, true);
        }

        $this->game->incGem(1, $newGem_id, $player_id);
    }

    public function marvelousCart()
    {
        $this->game->globals->set(MARVELOUS_CART, true);
    }

    public function epicElixir()
    {
        $this->game->globals->set(EPIC_ELIXIR, true);
    }

    public function swappingStones(int $player_id, int $opponent_id)
    {
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

        $this->game->globals->set(SWAPPING_STONES, true);
    }

    public function isUndoable($player_id): bool
    {
        if (!$this->checkLocation("used", $player_id)) {
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

        return false;
    }

    public function undo($player_id): void
    {
        if (!$this->isUndoable($player_id)) {
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
    }

    public function discard()
    {
        if ($this->id === 4) {
            $this->game->globals->set(EPIC_ELIXIR);
        }

        if ($this->id === 10) {
            $this->game->globals->set(SWAPPING_STONES);
        }

        $this->game->item_cards->moveCard($this->card_id, "discard");
    }
}
