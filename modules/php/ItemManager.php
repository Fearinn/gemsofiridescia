<?php

declare(strict_types=1);

namespace Bga\Games\GemsOfIridescia;

class ItemManager {
    public function __construct(int $item_id, \Table $game)
    {
        $this->game = $game;

        $info = $game->items_info[$item_id];
        $this->id = $item_id;
        $this->tr_name = $info["tr_name"];
        $this->cost = $info["cost"];
    }
}