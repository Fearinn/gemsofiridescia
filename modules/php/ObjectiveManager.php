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

            $currentTiles = $this->game->getStatWithRhom("$gem_id:GemTiles", $current_player_id);
            $rainbowTiles = $this->game->getStatWithRhom("rainbow:Tiles", $current_player_id);

            $maxTiles = 0;
            foreach ($players as $player_id => $player) {
                if ($player_id === $current_player_id) {
                    continue;
                }

                $tilesCount = $this->game->getStatWithRhom("$gem_id:GemTiles", $player_id);

                if ($tilesCount >= $maxTiles) {
                    $maxTiles = $tilesCount;
                }
            }

            $playerTiles = $currentTiles + $rainbowTiles;

            return $playerTiles > 0 && $playerTiles >= $maxTiles;
        }

        if ($this->id >= 7 && $this->id <= 10) {
            $gem_id = $this->variable;

            $currentRelics = $relicsCount = $this->game->getStatWithRhom("$gem_id:GemRelics", $current_player_id);
            $iridiaRelics = $this->game->getStatWithRhom("iridia:Relics", $current_player_id);

            $maxRelics = 0;
            foreach ($players as $player_id => $player) {
                if ($player_id === $current_player_id) {
                    continue;
                }

                $relicsCount = $this->game->getStatWithRhom("$gem_id:GemRelics", $player_id);

                if ($relicsCount >= $maxRelics) {
                    $maxRelics = $relicsCount;
                }
            }

            $playerRelics = $currentRelics + $iridiaRelics;
            return $playerRelics > 0 && $playerRelics >= $maxRelics;
        }

        if ($this->id >= 12 && $this->id <= 14) {
            $relicType = (int) $this->variable;

            $currentRelics = $relicsCount = $this->game->getStatWithRhom("$relicType:TypeRelics", $current_player_id);
            $iridiaRelics = $this->game->getStatWithRhom("iridia:Relics", $current_player_id);

            $maxRelics = 0;
            foreach ($players as $player_id => $player) {
                if ($player_id === $current_player_id) {
                    continue;
                }

                $relicsCount = (int) $this->game->getStatWithRhom("$relicType:TypeRelics", $player_id);

                if ($relicsCount >= $maxRelics) {
                    $maxRelics = $relicsCount;
                }
            }

            if ($maxRelics === 0) {
                return false;
            }

            $relicsForSets = $this->game->globals->get("relicsForSets:$current_player_id");
            $iridia = (int) $relicsForSets["iridia"];
            $jewelry = (int) $relicsForSets[1];
            $lore = (int) $relicsForSets[2];
            $tech = (int) $relicsForSets[3];

            if (
                $currentRelics > 0 && $currentRelics >= $maxRelics
            ) {
                return true;
            }

            $neededWild = $maxRelics - $currentRelics;

            if ($currentRelics === 0) {
                $neededWild = 1;
            }

            if ($neededWild > $iridia) {
                return false;
            }

            $wildIridia = $iridia - $neededWild;
            $maxRelicsPoints = $this->game->calcMaxRelicsPoints($tech, $lore, $jewelry, $iridia);

            $maxWithObjective = $this->points;
            if ($relicType === 1) {
                $maxWithObjective += $this->game->calcMaxRelicsPoints($tech, $lore, $jewelry + $neededWild, $wildIridia);
            }

            if ($relicType === 2) {
                $maxWithObjective += $this->game->calcMaxRelicsPoints($tech, $lore + $neededWild, $jewelry, $wildIridia);
            }

            if ($relicType === 3) {
                $maxWithObjective += $this->game->calcMaxRelicsPoints($tech + $neededWild, $lore, $jewelry, $wildIridia);
            }

            if ($maxRelicsPoints >= $maxWithObjective) {
                return false;
            }

            $relicsForSets = $this->game->globals->get("relicsForSets:$current_player_id");

            $relicsForSets[$relicType] = $currentRelics + $neededWild;
            $relicsForSets["iridia"] = $wildIridia;
            $this->game->globals->set("relicsForSets:$current_player_id", $relicsForSets);

            return true;
        }

        if ($this->id === 6) {
            $gemsRelics = [
                "amethyst" => $this->game->getStatWithRhom("1:GemRelics", $current_player_id),
                "citrine" => $this->game->getStatWithRhom("2:GemRelics", $current_player_id),
                "emerald" => $this->game->getStatWithRhom("3:GemRelics", $current_player_id),
                "sapphire" => $this->game->getStatWithRhom("4:GemRelics", $current_player_id),
            ];

            $iridiaRelics = $this->game->getStatWithRhom("iridia:Relics", $current_player_id);

            $differentGems = 0;
            foreach ($gemsRelics as $relicsCount) {
                if ($relicsCount > 0) {
                    $differentGems++;
                };
            }

            return ($differentGems + $iridiaRelics) >= 4;
        }

        if ($this->id === 11) {
            return $this->game->relic_cards->countCardsInLocation("hand", $current_player_id) >= 5;
        }

        if ($this->id === 15) {
            $relicsForSets = $this->game->globals->get("relicsForSets:$current_player_id");

            $iridia = (int) $relicsForSets["iridia"];
            $jewelry = (int) $relicsForSets[1];
            $lore = (int) $relicsForSets[2];
            $tech = (int) $relicsForSets[3];

            $wildIridia = $iridia;
            $wildJewelry = $jewelry;
            $wildTech = $tech;
            $wildLore = $lore;

            if ($jewelry === 0) {
                if ($wildIridia === 0) {
                    return false;
                }

                $wildIridia--;
                $wildJewelry++;
            }

            if ($tech === 0) {
                if ($wildIridia === 0) {
                    return false;
                }

                $wildIridia--;
                $wildTech++;
            }

            if ($lore === 0) {
                if ($wildIridia === 0) {
                    return false;
                }

                $wildIridia--;
                $wildLore++;
            }

            $maxRelicsPoints = $this->game->calcMaxRelicsPoints($tech, $lore, $jewelry, $iridia);
            $maxWithObjective = $this->game->calcMaxRelicsPoints($wildTech, $wildLore, $wildJewelry, $wildIridia) + 7;

            if ($maxRelicsPoints > $maxWithObjective) {
                return false;
            }

            $relicsForSets = [1 => $wildJewelry, 3 => $wildTech, 2 => $wildLore, "iridia" => $wildIridia];
            $this->game->globals->set("relicsForSets:$current_player_id", $relicsForSets);

            return true;
        }
    }
}
