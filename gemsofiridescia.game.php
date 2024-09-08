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

class GemsOfIridescia extends Table
{
    public function __construct()
    {
        parent::__construct();

        $this->initGameStateLabels([]);

        $this->tiles = $this->getNew("module.common.deck");
        $this->tiles->init("tile");

        $this->explorers = $this->getNew("module.common.deck");
        $this->explorers->init("explorer");

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
    public function actPlayCard(int $card_id): void
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state.
        $this->checkAction("actPlayCard");

        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Add your game logic to play a card here.
        $card_name = $this->card_types[$card_id]['card_name'];

        // Notify all players about the card played.
        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
            "card_name" => $card_name,
            "card_id" => $card_id,
            "i18n" => ['card_name'],
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("playCard");
    }

    public function actPass(): void
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state.
        $this->checkAction("actPass");

        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("pass");
    }

    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return string[]
     * @see ./states.inc.php
     */

    public function argRevealTiles()
    {
        $player_id = (int) $this->getActivePlayerId();

        return [
            "revealableTiles" => $this->revealableTiles($player_id)
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

    public function getTileBoard(): array
    {
        $tileBoard = $this->getCollectionFromDB("SELECT card_id id, card_type type, card_location location, card_location_arg location_arg 
        FROM tile WHERE card_location<>'hand'");

        return $tileBoard;
    }

    public function adjacentTiles(int $player_id): array
    {
        $adjacentTiles = [];

        $explorer = $this->getExplorerByPlayerId($player_id);

        if ($explorer["location"] === "scene") {
            return $this->getCollectionFromDB($this->deckSelectQuery . "FROM tile WHERE card_location='1' AND card_location_arg<=6");
        }

        $explorerRegion = $explorer["type"];
        $explorerHex = $explorer["type_arg"];

        $leftHex = $explorerHex - 1;
        $rightHex = $explorerHex + 1;
        $topLeftHex = $explorerHex <= 6 ? $explorerHex + 6 : $explorerHex - 7;
        $topRightHex =  $explorerHex <= 6 ? $explorerHex + 7 : $explorerHex - 6;

        if ($explorerHex === 1) {
            $leftHex = null;
        }

        if ($explorerHex === 6) {
            $rightHex = null;
        }

        if ($explorerRegion === 5) {
            $topLeftHex = null;
            $topRightHex = null;
        }

        if ($explorerHex === 7) {
            $leftHex = null;
            $topLeftHex = null;
        }

        if ($explorerHex === 13) {
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

            $location = $explorerRegion * 2;
            if ($hex <= 6) {
                $location--;
            }

            $tileCard = $this->getObjectFromDB($this->deckSelectQuery . "FROM tile WHERE card_location='$location' AND card_location_arg=$hex");

            $tileCard_id = $tileCard["id"];
            $adjacentTiles[$tileCard_id] = $tileCard;
        }

        return $adjacentTiles;
    }

    public function revealableTiles(int $player_id): array
    {
        $revealableTiles = [];

        $adjacentTiles =  $this->adjacentTiles($player_id);
        $revealedTiles = $this->globals->get("revealedTiles", []);

        foreach ($adjacentTiles as $card_id => $tileCard) {
            if (!key_exists($card_id, $revealedTiles)) {
                $tileRow = $tileCard["location"];
                $revealableTiles[$tileRow][] = $tileCard;
            }
        }

        return $revealableTiles;
    }

    public function getExplorers(): array
    {
        $explorers = $this->getCollectionFromDB($this->deckSelectQuery . " 
        FROM explorer WHERE card_location<>'box'");

        return $explorers;
    }

    public function getExplorerByPlayerId(int $player_id): array
    {
        $explorer = $this->getObjectFromDB("SELECT card_id, card_type type, card_type_arg type_arg, card_location location, card_location_arg location_arg 
        FROM explorer WHERE card_type_arg='$player_id'");

        return $explorer;
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
        $result["tileBoard"] = $this->getTileBoard();
        $result["playerBoards"] = $this->globals->get("playerBoards");
        $result["explorers"] = $this->getExplorers();

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

        $this->explorers->createCards($explorers, "deck");

        $explorers = $this->explorers->getCardsInLocation("deck");
        $playerBoards = [];
        foreach ($explorers as $card_id => $explorer) {
            foreach ($players as $player_id => $player) {
                $player_color = $this->getPlayerColorById($player_id);

                if ($player_color === $explorer["type"]) {
                    $playerBoards[$player_id] = $explorer["type_arg"];

                    $this->explorers->moveCard($card_id, "scene");
                    $this->DbQuery("UPDATE explorer SET card_type_arg=$player_id WHERE card_id='$card_id'");
                }
            }
        }

        $this->globals->set("playerBoards", $playerBoards);
        $this->explorers->moveAllCardsInLocation("deck", "box");

        $this->globals->set("playerBoards", $playerBoards);
        $tiles = [];
        foreach ($this->tiles_info as $tile_id => $tile_info) {
            $region_id = $tile_info["region"];

            $tiles[] = ["type" => $region_id, "type_arg" => $tile_id, "nbr" => 1];
        }

        $this->tiles->createCards($tiles, "deck");

        foreach ($this->regions_info as $region_id => $region) {
            $tilesOfregion = $this->tiles->getCardsOfTypeInLocation($region_id, null, "deck");
            $k_tilesOfregion = array_keys($tilesOfregion);

            $temporaryLocation = strval($region["name"]);
            $this->tiles->moveCards($k_tilesOfregion, $temporaryLocation);
            $this->tiles->shuffle($temporaryLocation);

            $hex = 1;
            for ($i = 1; $i <= count($tilesOfregion); $i++) {
                $finalLocation = $region_id * 2;

                if ($hex <= 6) {
                    $finalLocation--;
                }

                $this->tiles->pickCardForLocation($temporaryLocation, $finalLocation, $hex);
                $hex++;
            }
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
