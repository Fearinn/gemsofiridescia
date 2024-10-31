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

        if ($this->card["location"] !== $location || ($location_arg && $location_arg !== $this->card["location_arg"])) {
            $confirmLocation = false;
        }

        return $confirmLocation;
    }

    public function isBuyable(int $player_id): bool
    {
        return $this->checkLocation("market") && $this->game->getCoins($player_id) >= $this->cost;
    }

    public function buy(int $player_id): void
    {
        if (!$this->isBuyable($player_id)) {
            throw new \BgaVisibleSystemException("You can't buy this item now: actBuyItem, $this->card_id");
        }

        $this->game->decCoin($this->cost, $player_id);
        $this->game->item_cards->moveCard($this->card_id, "hand", $player_id);

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

        $this->game->replaceItem();
    }

    public function isUsable(int $player_id): bool
    {
        if ($this->id === 1) {
            return $this->game->getTotalGemsCount($player_id) >= 2;
        }

        if ($this->id === 3) {
            return $this->game->getCoins($player_id) >= 3;
        }

        if ($this->id === 8) {
            return $this->game->getTotalGemsCount($player_id) >= 1;
        }

        if ($this->id === 9) {
            return $this->game->getCoins($player_id) >= 3;
        }

        if ($this->id === 11) {
            return !!$this->game->expandedRevealableTiles($player_id) || !!$this->game->expandedExplorableTiles($player_id);
        }

        return true;
    }
}
