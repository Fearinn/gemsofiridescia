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
 * states.inc.php
 *
 * GemsOfIridescia game states description
 *
 */


$machinestates = [

    // The initial state. Please do not modify.

    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => 2]
    ),

    // Note: ID=2 => your first state

    2 => [
        "name" => "revealTile",
        "description" => clienttranslate('${actplayer} may reveal a tile'),
        "descriptionmyturn" => clienttranslate('${you} may reveal a tile'),
        "type" => "activeplayer",
        "args" => "argRevealTile",
        "action" => "stRevealTile",
        "possibleactions" => ["actRevealTile", "actSkipRevealTile"],
        "transitions" => ["revealTileAgain" => 2, "moveExplorer" => 3]
    ],

    3 => [
        "name" => "moveExplorer",
        "description" => clienttranslate('${actplayer} must move his explorer to a revealed tile'),
        "descriptionmyturn" => clienttranslate('${you} must move your explorer to a revealed tile'),
        "type" => "activeplayer",
        "args" => "argMoveExplorer",
        "action" => "stMoveExplorer",
        "possibleactions" => ["actMoveExplorer"],
        "transitions" => ["mine" => 31]
    ],

    31 => [
        "name" => "mine",
        "description" => clienttranslate('${actplayer} may spend 3 coins to mine this tile'),
        "descriptionmyturn" => clienttranslate('${you} may spend 3 coins to mine this tile'),
        "type" => "activeplayer",
        "possibleactions" => ["actMine", "actSkipMine"],
        "transitions" => ["" => 4]
    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],
];
