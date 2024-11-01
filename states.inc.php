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
        "possibleactions" => ["actRevealTile", "actSkipRevealTile", "actUseItem", "actUndoItem"],
        "transitions" => [
            "repeat" => 2,
            "discardCollectedTile" => 20,
            "discardTile" => 21,
            "skip" => 3,
            "moveExplorer" => 3
        ]
    ],

    20 => [
        "name" => "discardCollectedTile",
        "description" => clienttranslate('${actplayer} has no free adjacent tiles and must discard one from his Victory Pile'),
        "descriptionmyturn" => clienttranslate('${you} have no free adjacent tiles and must discard one from your Victory Pile'),
        "type" => "activeplayer",
        "args" => "argDiscardCollectedTile",
        "action" => "stDiscardCollectedTile",
        "possibleactions" => ["actDiscardCollectedTile"],
        "transitions" => ["revealTile" => 2]
    ],

    21 => [
        "name" => "discardTile",
        "description" => clienttranslate('${actplayer} must discard a tile from the board'),
        "descriptionmyturn" => clienttranslate('${you} must discard a tile from the board'),
        "type" => "activeplayer",
        "possibleactions" => ["actDiscardTile"],
        "transitions" => ["betweenTurns" => 6],
    ],

    3 => [
        "name" => "moveExplorer",
        "description" => clienttranslate('${actplayer} must move his explorer to a revealed tile'),
        "descriptionmyturn" => clienttranslate('${you} must move your explorer to a revealed tile'),
        "type" => "activeplayer",
        "args" => "argMoveExplorer",
        "action" => "stMoveExplorer",
        "possibleactions" => ["actMoveExplorer", "actUndoSkipRevealTile"],
        "transitions" => ["back" => 2, "rainbowTile" => 30, "discardObjective" => 32, "optionalActions" => 4]
    ],

    30 => [
        "name" => "rainbowTile",
        "description" => clienttranslate('${actplayer} must pick a Gem to collect from the Rainbow'),
        "descriptionmyturn" => clienttranslate('${you} must pick a Gem to collect from the Rainbow'),
        "type" => "activeplayer",
        "possibleactions" => ["actPickRainbowGem"],
        "transitions" => ["optionalActions" => 4]
    ],

    31 => [
        "name" => "transferGem",
        "description" => clienttranslate('The cargo of ${actplayer} is full. ${actplayer} must pick a Gem to give away to other player'),
        "descriptionmyturn" => clienttranslate('Your cargo is full. ${you} must pick a Gem to give away to other player'),
        "type" => "activeplayer",
        "args" => "argTransferGem",
        "action" => "stTransferGem",
        "possibleactions" => ["actTransferGem", "actDiscardGem"],
        "transitions" => ["repeat" => 31],
    ],

    32 => [
        "name" => "discardObjective",
        "description" => clienttranslate('${actplayer} must discard a Secret Objective'),
        "descriptionmyturn" => clienttranslate('${you} must discard a Secret Objective'),
        "type" => "activeplayer",
        "possibleactions" => ["actDiscardObjective"],
        "transitions" => ["optionalActions" => 4]
    ],

    4 => [
        "name" => "optionalActions",
        "description" => clienttranslate('${actplayer} may perform any available optional actions, in any order'),
        "descriptionmyturn" => clienttranslate('${you} may perform any available optional actions, in any order'),
        "type" => "activeplayer",
        "args" => "argOptionalActions",
        "action" => "stOptionalActions",
        "possibleactions" => ["actMine", "actSellGems", "actSkipOptionalActions", "actBuyItem", "actUseItem", "actUndoItem"],
        "transitions" => ["repeat" => 4, "skip" => 5, "restoreRelic" => 5]
    ],

    5 => [
        "name" => "restoreRelic",
        "description" => clienttranslate('${actplayer} may restore a Relic'),
        "descriptionmyturn" => clienttranslate('${you} may restore a Relic'),
        "type" => "activeplayer",
        "args" => "argRestoreRelic",
        "action" => "stRestoreRelic",
        "possibleactions" => ["actRestoreRelic", "actSkipRestoreRelic", "actUndoSkipOptionalActions"],
        "transitions" => ["back" => 4, "repeat" => 5, "skip" => 6, "betweenTurns" => 6]
    ],

    6 => [
        "name" => "betweenTurns",
        "description" => clienttranslate("Ending turn..."),
        "type" => "game",
        "action" => "stBetweenTurns",
        "transitions" => ["nextTurn" => 2, "finalScoring" => 7],
        "updateGameProgression" => true,
    ],

    7 => [
        "name" => "finalScoring",
        "description" => clienttranslate("Computing final scoring..."),
        "type" => "game",
        "action" => "stFinalScoring",
        "transitions" => ["gameEnd" => 99]
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
