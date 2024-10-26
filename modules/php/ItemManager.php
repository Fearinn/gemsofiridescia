<?php

declare(strict_types=1);

namespace Bga\Games\GemsOfIridescia;

class ItemManager
{
    public function __construct(int $item_id, \Table $game)
    {
        $this->game = $game;

        $info = $game->items_info[$item_id];
        $this->id = $item_id;
        $this->tr_name = $info["tr_name"];
        $this->cost = $info["cost"];
    }

    public function isUsable(int $player_id)
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
