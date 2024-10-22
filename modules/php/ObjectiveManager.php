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
            $maxCoins = 0;

            foreach ($players as $player_id => $player) {
                $coins = (int) $this->game->getUniqueValueFromDB("SELECT coin from player WHERE player_id=$player_id");
                if ($coins > $maxCoins || ($coins === $maxCoins && $player_id === $current_player_id)) {
                    $maxCoins = $coins;
                    $leadPlayer = $player_id;
                }
            }

            return $leadPlayer === $current_player_id;
        }

        if ($this->id >= 2 && $this->id <= 5) {
            $gem_id = $this->variable;

            $currentTiles = $this->game->getStat("$gem_id:GemTiles", $current_player_id);
            $rainbowTiles = $this->game->getStat("rainbow:Tiles", $current_player_id);

            $maxTiles = 0;
            foreach ($players as $player_id => $player) {
                if ($player_id === $current_player_id) {
                    continue;
                }

                $tilesCount = $this->game->getStat("$gem_id:GemTiles", $player_id);

                if ($tilesCount >= $maxTiles) {
                    $maxTiles = $tilesCount;
                }
            }

            $playerTiles = $currentTiles + $rainbowTiles;

            return $playerTiles > 0 && $playerTiles >= $maxTiles;
        }

        if ($this->id >= 7 && $this->id <= 10) {
            $gem_id = $this->variable;

            $currentRelics = $relicsCount = $this->game->getStat("$gem_id:GemRelics", $current_player_id);
            $iridiaRelics = $this->game->getStat("iridia:Relics", $current_player_id);

            $maxRelics = 0;
            foreach ($players as $player_id => $player) {
                if ($player_id === $current_player_id) {
                    continue;
                }

                $relicsCount = $this->game->getStat("$gem_id:GemRelics", $player_id);

                if ($relicsCount >= $maxRelics) {
                    $maxRelics = $relicsCount;
                }
            }

            $playerRelics = $currentRelics + $iridiaRelics;

            return $playerRelics > 0 && $playerRelics >= $maxRelics;
        }

        if ($this->id >= 12 && $this->id <= 14) {
            $relicType = $this->variable;

            $currentRelics = $relicsCount = $this->game->getStat("$relicType:TypeRelics", $current_player_id);
            $iridiaRelics = $this->game->getStat("iridia:Relics", $current_player_id);

            $maxRelics = 0;
            foreach ($players as $player_id => $player) {
                if ($player_id === $current_player_id) {
                    continue;
                }

                $relicsCount = $this->game->getStat("$relicType:TypeRelics", $player_id);

                if ($relicsCount >= $maxRelics) {
                    $maxRelics = $relicsCount;
                }
            }

            $playerRelics = $currentRelics + $iridiaRelics;

            return  $playerRelics > 0 && $playerRelics >= $maxRelics;
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
