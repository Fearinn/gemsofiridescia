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
        "name" => "amethyst",
        "tr_label" => clienttranslate("Amethyst")
    ],
    2 => [
        "name" => "citrine",
        "tr_label" => clienttranslate("Citrine")
    ],
    3 => [
        "name" => "emerald",
        "tr_label" => clienttranslate("Emerald")
    ],
    4 => [
        "name" => "sapphire",
        "tr_label" => clienttranslate("Sapphire")
    ],
];

$this->regions_info = [
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
        "region" => 1,
        "gem" => 0,
        "effect" => 0,
    ],

    2 => [
        "region" => 1,
        "gem" => 1,
        "effect" => 0,
    ],
    3 => [
        "region" => 1,
        "gem" => 1,
        "effect" => 0,
    ],
    4 => [
        "region" => 1,
        "gem" => 1,
        "effect" => 0,
    ],
    5 => [
        "region" => 1,
        "gem" => 2,
        "effect" => 0,
    ],
    6 => [
        "region" => 1,
        "gem" => 2,
        "effect" => 0,
    ],
    7 => [
        "region" => 1,
        "gem" => 2,
        "effect" => 0,
    ],
    8 => [
        "region" => 1,
        "gem" => 3,
        "effect" => 0,
    ],
    9 => [
        "region" => 1,
        "gem" => 3,
        "effect" => 0,
    ],
    10 => [
        "region" => 1,
        "gem" => 3,
        "effect" => 0,
    ],
    11 => [
        "region" => 1,
        "gem" => 4,
        "effect" => 0,
    ],
    12 => [
        "region" => 1,
        "gem" => 4,
        "effect" => 0,
    ],
    13 => [
        "region" => 1,
        "gem" => 4,
        "effect" => 0,
    ],

    14 => [
        "region" => 2,
        "gem" => 0,
        "effect" => 0
    ],
    15 => [
        "region" => 2,
        "gem" => 1,
        "effect" => 1
    ],
    16 => [
        "region" => 2,
        "gem" => 1,
        "effect" => 2
    ],
    17 => [
        "region" => 2,
        "gem" => 1,
        "effect" => 3
    ],
    18 => [
        "region" => 2,
        "gem" => 2,
        "effect" => 1
    ],
    19 => [
        "region" => 2,
        "gem" => 2,
        "effect" => 2
    ],
    20 => [
        "region" => 2,
        "gem" => 2,
        "effect" => 3
    ],
    21 => [
        "region" => 2,
        "gem" => 3,
        "effect" => 1
    ],
    22 => [
        "region" => 2,
        "gem" => 3,
        "effect" => 2
    ],
    23 => [
        "region" => 2,
        "gem" => 3,
        "effect" => 3
    ],
    24 => [
        "region" => 2,
        "gem" => 4,
        "effect" => 1
    ],
    25 => [
        "region" => 2,
        "gem" => 4,
        "effect" => 2
    ],
    26 => [
        "region" => 2,
        "gem" => 4,
        "effect" => 3
    ],
    27 => [
        "region" => 3,
        "gem" => 0,
        "effect" => 0
    ],
    28 => [
        "region" => 3,
        "gem" => 1,
        "effect" => 1
    ],
    29 => [
        "region" => 3,
        "gem" => 1,
        "effect" => 2
    ],
    30 => [
        "region" => 3,
        "gem" => 1,
        "effect" => 3
    ],
    31 => [
        "region" => 3,
        "gem" => 2,
        "effect" => 1
    ],
    32 => [
        "region" => 3,
        "gem" => 2,
        "effect" => 2
    ],
    33 => [
        "region" => 3,
        "gem" => 2,
        "effect" => 3
    ],
    34 => [
        "region" => 3,
        "gem" => 3,
        "effect" => 1
    ],
    35 => [
        "region" => 3,
        "gem" => 3,
        "effect" => 2
    ],
    36 => [
        "region" => 3,
        "gem" => 3,
        "effect" => 3
    ],
    37 => [
        "region" => 3,
        "gem" => 4,
        "effect" => 1
    ],
    38 => [
        "region" => 3,
        "gem" => 4,
        "effect" => 2
    ],
    39 => [
        "region" => 3,
        "gem" => 4,
        "effect" => 3
    ],
    40 => [
        "region" => 4,
        "gem" => 0,
        "effect" => 0
    ],
    41 => [
        "region" => 4,
        "gem" => 1,
        "effect" => 1
    ],
    42 => [
        "region" => 4,
        "gem" => 1,
        "effect" => 2
    ],
    43 => [
        "region" => 4,
        "gem" => 1,
        "effect" => 3
    ],
    44 => [
        "region" => 4,
        "gem" => 2,
        "effect" => 1
    ],
    45 => [
        "region" => 4,
        "gem" => 2,
        "effect" => 2
    ],
    46 => [
        "region" => 4,
        "gem" => 2,
        "effect" => 3
    ],
    47 => [
        "region" => 4,
        "gem" => 3,
        "effect" => 1
    ],
    48 => [
        "region" => 4,
        "gem" => 3,
        "effect" => 2
    ],
    49 => [
        "region" => 4,
        "gem" => 3,
        "effect" => 3
    ],
    50 => [
        "region" => 4,
        "gem" => 4,
        "effect" => 1
    ],
    51 => [
        "region" => 4,
        "gem" => 4,
        "effect" => 2
    ],
    52 => [
        "region" => 4,
        "gem" => 4,
        "effect" => 3
    ],
    53 => [
        "region" => 5,
        "gem" => 0,
        "effect" => 2,
    ],
    54 => [
        "region" => 5,
        "gem" => 1,
        "effect" => 2,
    ],
    55 => [
        "region" => 5,
        "gem" => 2,
        "effect" => 2,
    ],
    56 => [
        "region" => 5,
        "gem" => 3,
        "effect" => 2,
    ],
    57 => [
        "region" => 5,
        "gem" => 4,
        "effect" => 2,
    ],
    58 => [
        "region" => 5,
        "gem" => 10,
        "effect" => 2,
    ]
];
