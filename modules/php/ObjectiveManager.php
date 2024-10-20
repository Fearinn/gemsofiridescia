<?php

declare(strict_types=1);

namespace Bga\Games\GemsOfIridescia;

class ObjectiveManager
{
    public function __construct(int $objective_id, \Table $game)
    {
        $this->game = $game;

        $info = $game->objectives_info[$objective_id];
        $this->id = $objective_id;
        $this->tr_name = $info["tr_name"];
        $this->content = $info["content"];
        $this->points = $info["points"];
        $this->variable = $info["variable"];
    }

    public function checkCondition(int $current_player_id): bool
    {
        $players = $this->game->loadPlayersBasicInfos();
        $leadPlayer = null;

        if ($this->id === 1) {
            $max_coins = 0;

            foreach ($players as $player_id => $player) {
                $coins = (int) $this->game->getUniqueValueFromDB("SELECT coin from player WHERE player_id=$player_id");
                if ($coins > $max_coins || ($coins === $max_coins && $player_id === $current_player_id)) {
                    $max_coins = $coins;
                    $leadPlayer = $player_id;
                }
            }

            return $leadPlayer === $current_player_id;
        }

        if ($this->id >= 2 && $this->id <= 5) {
            $gem_id = $this->variable;
            $max_tiles = 0;

            foreach ($players as $player_id => $player) {
                $tilesCount = $this->game->getStat("$gem_id:GemTiles", $player_id);

                if ($tilesCount > $max_tiles || ($tilesCount === $max_tiles && $player_id === $current_player_id)) {
                    $max_tiles = $tilesCount;
                    $leadPlayer = $player_id;
                }
            }

            return $leadPlayer === $current_player_id;
        }

        if ($this->id >= 7 && $this->id <= 10) {
            $gem_id = $this->variable;
            $max_relics = 0;

            foreach ($players as $player_id => $player) {
                $relicsCount = $this->game->getStat("$gem_id:GemRelics", $player_id);

                if ($relicsCount > $max_relics || ($relicsCount === $max_relics && $player_id === $current_player_id)) {
                    $max_relics = $relicsCount;
                    $leadPlayer = $player_id;
                }
            }

            return $leadPlayer === $current_player_id;
        }

        if ($this->id >= 12 && $this->id <= 14) {
            $relicType = $this->variable;
            $max_relics = 0;

            foreach ($players as $player_id => $player) {
                $relicsCount = $this->game->getStat("$relicType:TypeRelics", $player_id);

                if ($relicsCount > $max_relics || ($relicsCount === $max_relics && $player_id === $current_player_id)) {
                    $max_relics = $relicsCount;
                    $leadPlayer = $player_id;
                }
            }

            return $leadPlayer === $current_player_id;
        }

        if ($this->id === 6) {
            $gemsRelics = [
                "amethyst" => $this->game->getStat("1:GemRelics"),
                "citrine" => $this->game->getStat("2:GemRelics"),
                "emerald" => $this->game->getStat("3:GemRelics"),
                "sapphire" => $this->game->getStat("4:GemRelics"),
            ];

            $iridiaRelics = $this->game->getStat("iridiaRelics");

            $differentGems = 0;
            foreach ($gemsRelics as $relicsCount) {
                if ($relicsCount > 0) {
                    $differentGems++;
                };
            }

            return ($differentGems + $iridiaRelics) >= 4;
        }

        if ($this->id === 11) {
            return $this->relic_cards->countCardsInLocation("hand", $current_player_id) >= 5;
        }

        if ($this->id === 15) {
            $typesRelics = [
                "jewelry" => $this->game->getStat("1:TypeRelics"),
                "lore" => $this->game->getStat("2:TypeRelics"),
                "tech" => $this->game->getStat("3:TypeRelics"),
            ];

            $iridiaRelics = $this->game->getStat("iridiaRelics");

            $differentTypes = 0;
            foreach ($typesRelics as $relicsCount) {
                if ($relicsCount > 0) {
                    $differentTypes++;
                };
            }

            return ($differentTypes + $iridiaRelics) >= 3;
        }
    }
}
