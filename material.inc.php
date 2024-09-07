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
 * material.inc.php
 *
 * GemsOfIridescia game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */
$this->explorers_info = [
    1 => [
        "name" => "Kito",
        "color" => "ff0000"
    ],
    2 => [
        "name" => "Ooven",
        "color" => "0000ff"
    ],
    3 => [
        "name" => "Moja",
        "color" => "008000"
    ],
    4 => [
        "name" => "Rhom",
        "color" => "ffa500"
    ],
];

$this->gems_info = [
    1 => [
        "name" => "Amethyst",
        "tr_label" => clienttranslate("Amethyst")
    ],
    2 => [
        "name" => "Citrine",
        "tr_label" => clienttranslate("Citrine")
    ],
    3 => [
        "name" => "Emerald",
        "tr_label" => clienttranslate("Emerald")
    ],
    4 => [
        "name" => "Sapphire",
        "tr_label" => clienttranslate("Sapphire")
    ],
];

$this->terrains_info = [
    1 => [
        "name" => "Desert",
        "tr_label" => clienttranslate("Desert")
    ],
    2 => [
        "name" => "Canyon",
        "tr_label" => clienttranslate("Canyon")
    ],
    3 => [
        "name" => "Florest",
        "tr_label" => clienttranslate("Florest")
    ],
    4 => [
        "name" => "Ruins",
        "tr_label" => clienttranslate("Ruins")
    ],
    5 => [
        "name" => "Castle",
        "tr_label" => clienttranslate("Castle")
    ],
];

$this->tileEffects_info = [
    1 => [
        "label" => clienttranslate("coin(s)"),
        "values" => [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => null
        ],
    ],
    2 => [
        "label" => clienttranslate("Royalt point(s)"),
        "values" => [
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 5
        ],
    ],
    3 => [
        "label" => clienttranslate("Stone die"),
        "values" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ],
    ],
];

$this->tiles_info = [
    1 => [
        "terrain" => 1,
        "gem" => 0,
        "effect" => 0,
    ],

    2 => [
        "terrain" => 1,
        "gem" => 1,
        "effect" => 0,
    ],
    3 => [
        "terrain" => 1,
        "gem" => 1,
        "effect" => 0,
    ],
    4 => [
        "terrain" => 1,
        "gem" => 1,
        "effect" => 0,
    ],
    5 => [
        "terrain" => 1,
        "gem" => 2,
        "effect" => 0,
    ],
    6 => [
        "terrain" => 1,
        "gem" => 2,
        "effect" => 0,
    ],
    7 => [
        "terrain" => 1,
        "gem" => 2,
        "effect" => 0,
    ],
    8 => [
        "terrain" => 1,
        "gem" => 3,
        "effect" => 0,
    ],
    9 => [
        "terrain" => 1,
        "gem" => 3,
        "effect" => 0,
    ],
    10 => [
        "terrain" => 1,
        "gem" => 3,
        "effect" => 0,
    ],
    11 => [
        "terrain" => 1,
        "gem" => 4,
        "effect" => 0,
    ],
    12 => [
        "terrain" => 1,
        "gem" => 4,
        "effect" => 0,
    ],
    13 => [
        "terrain" => 1,
        "gem" => 4,
        "effect" => 0,
    ],

    14 => [
        "terrain" => 2,
        "gem" => 0,
        "effect" => 0
    ],
    15 => [
        "terrain" => 2,
        "gem" => 1,
        "effect" => 1
    ],
    16 => [
        "terrain" => 2,
        "gem" => 1,
        "effect" => 2
    ],
    17 => [
        "terrain" => 2,
        "gem" => 1,
        "effect" => 3
    ],
    18 => [
        "terrain" => 2,
        "gem" => 2,
        "effect" => 1
    ],
    19 => [
        "terrain" => 2,
        "gem" => 2,
        "effect" => 2
    ],
    20 => [
        "terrain" => 2,
        "gem" => 2,
        "effect" => 3
    ],
    21 => [
        "terrain" => 2,
        "gem" => 3,
        "effect" => 1
    ],
    22 => [
        "terrain" => 2,
        "gem" => 3,
        "effect" => 2
    ],
    23 => [
        "terrain" => 2,
        "gem" => 3,
        "effect" => 3
    ],
    24 => [
        "terrain" => 2,
        "gem" => 4,
        "effect" => 1
    ],
    25 => [
        "terrain" => 2,
        "gem" => 4,
        "effect" => 2
    ],
    26 => [
        "terrain" => 2,
        "gem" => 4,
        "effect" => 3
    ],
    27 => [
        "terrain" => 3,
        "gem" => 0,
        "effect" => 0
    ],
    28 => [
        "terrain" => 3,
        "gem" => 1,
        "effect" => 1
    ],
    29 => [
        "terrain" => 3,
        "gem" => 1,
        "effect" => 2
    ],
    30 => [
        "terrain" => 3,
        "gem" => 1,
        "effect" => 3
    ],
    31 => [
        "terrain" => 3,
        "gem" => 2,
        "effect" => 1
    ],
    32 => [
        "terrain" => 3,
        "gem" => 2,
        "effect" => 2
    ],
    33 => [
        "terrain" => 3,
        "gem" => 2,
        "effect" => 3
    ],
    34 => [
        "terrain" => 3,
        "gem" => 3,
        "effect" => 1
    ],
    35 => [
        "terrain" => 3,
        "gem" => 3,
        "effect" => 2
    ],
    36 => [
        "terrain" => 3,
        "gem" => 3,
        "effect" => 3
    ],
    37 => [
        "terrain" => 3,
        "gem" => 4,
        "effect" => 1
    ],
    38 => [
        "terrain" => 3,
        "gem" => 4,
        "effect" => 2
    ],
    39 => [
        "terrain" => 3,
        "gem" => 4,
        "effect" => 3
    ],
    40 => [
        "terrain" => 4,
        "gem" => 0,
        "effect" => 0
    ],
    41 => [
        "terrain" => 4,
        "gem" => 1,
        "effect" => 1
    ],
    42 => [
        "terrain" => 4,
        "gem" => 1,
        "effect" => 2
    ],
    43 => [
        "terrain" => 4,
        "gem" => 1,
        "effect" => 3
    ],
    44 => [
        "terrain" => 4,
        "gem" => 2,
        "effect" => 1
    ],
    45 => [
        "terrain" => 4,
        "gem" => 2,
        "effect" => 2
    ],
    46 => [
        "terrain" => 4,
        "gem" => 2,
        "effect" => 3
    ],
    47 => [
        "terrain" => 4,
        "gem" => 3,
        "effect" => 1
    ],
    48 => [
        "terrain" => 4,
        "gem" => 3,
        "effect" => 2
    ],
    49 => [
        "terrain" => 4,
        "gem" => 3,
        "effect" => 3
    ],
    50 => [
        "terrain" => 4,
        "gem" => 4,
        "effect" => 1
    ],
    51 => [
        "terrain" => 4,
        "gem" => 4,
        "effect" => 2
    ],
    52 => [
        "terrain" => 4,
        "gem" => 4,
        "effect" => 3
    ],
    53 => [
        "terrain" => 5,
        "gem" => 0,
        "effect" => 2,
    ],
    54 => [
        "terrain" => 5,
        "gem" => 1,
        "effect" => 2,
    ],
    55 => [
        "terrain" => 5,
        "gem" => 2,
        "effect" => 2,
    ],
    56 => [
        "terrain" => 5,
        "gem" => 3,
        "effect" => 2,
    ],
    57 => [
        "terrain" => 5,
        "gem" => 4,
        "effect" => 2,
    ],
    58 => [
        "terrain" => 5,
        "gem" => 10,
        "effect" => 2,
    ]
];
