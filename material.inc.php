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

$this->dice_info = [
    "mining" => clienttranslate("Mining"),
    "stone" => clienttranslate("Stone"),
    "gem" => clienttranslate("Gem")
];


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

$this->gemsIds_info = [
    "amethyst" => 1,
    "citrine" => 2,
    "emerald" => 3,
    "sapphire" => 4
];

$this->gems_info = [
    1 => [
        "name" => "amethyst",
        "tr_name" => clienttranslate("Amethyst(s)")
    ],
    2 => [
        "name" => "citrine",
        "tr_name" => clienttranslate("Citrine(s)")
    ],
    3 => [
        "name" => "emerald",
        "tr_name" => clienttranslate("Emerald(s)")
    ],
    4 => [
        "name" => "sapphire",
        "tr_name" => clienttranslate("Sapphire(s)")
    ],
];

$this->regions_info = [
    1 => [
        "name" => "Desert",
        "tr_name" => clienttranslate("Desert")
    ],
    2 => [
        "name" => "Canyon",
        "tr_name" => clienttranslate("Canyon")
    ],
    3 => [
        "name" => "Forest",
        "tr_name" => clienttranslate("Forest")
    ],
    4 => [
        "name" => "Ruins",
        "tr_name" => clienttranslate("Ruins")
    ],
    5 => [
        "name" => "Castle",
        "tr_name" => clienttranslate("Castle")
    ],
];

$this->tileEffects_info = [
    1 => [
        "values" => [
            2 => 1,
            3 => 2,
            4 => 3,
        ],
    ],
    2 => [
        "values" => [
            2 => 1,
            3 => 2,
            4 => 3,
            5 => 5,
        ],
    ],
    3 => [
        "values" => [
            2 => 1,
            3 => 1,
            4 => 1,
        ],
    ],
];

$this->rows_info = [
    1 => [1, 2, 3, 4, 5, 6],
    2 => [7, 8, 9, 10, 11, 12, 13],
    3 => [14, 15, 16, 17, 18, 19],
    4 => [20, 21, 22, 23, 24, 25, 26],
    5 => [27, 28, 29, 30, 31, 32],
    6 => [33, 34, 35, 36, 37, 38, 39],
    7 => [40, 41, 42, 43, 44, 45],
    8 => [46, 47, 48, 49, 50, 51, 52],
    9 => [53, 54, 55, 56, 57, 58]
];

$this->columns_info = [
    1 => [7, 20, 33, 43],
    2 => [1, 14, 27, 40, 53],
    3 => [8, 21, 34, 47],
    4 => [2, 15, 28, 41, 54],
    5 => [9, 22, 35, 48],
    6 => [3, 16, 29, 42, 55],
    7 => [10, 23, 36, 49],
    8 => [4, 17, 30, 43, 56],
    9 => [11, 24, 37, 50],
    10 => [5, 18, 31, 44, 57],
    11 => [12, 25, 38, 51],
    12 => [6, 19, 32, 45, 58],
    13 => [13, 26, 39, 42],
];

$this->hexes_info = [
    1 => ["row" => 1, "column" => 2],
    2 => ["row" => 1, "column" => 4],
    3 => ["row" => 1, "column" => 6],
    4 => ["row" => 1, "column" => 8],
    5 => ["row" => 1, "column" => 10],
    6 => ["row" => 1, "column" => 12],
    7 => ["row" => 2, "column" => 1],
    8 => ["row" => 2, "column" => 3],
    9 => ["row" => 2, "column" => 5],
    10 => ["row" => 2, "column" => 7],
    11 => ["row" => 2, "column" => 9],
    12 => ["row" => 2, "column" => 11],
    13 => ["row" => 2, "column" => 13],
    14 => ["row" => 3, "column" => 2],
    15 => ["row" => 3, "column" => 4],
    16 => ["row" => 3, "column" => 6],
    17 => ["row" => 3, "column" => 8],
    18 => ["row" => 3, "column" => 10],
    19 => ["row" => 3, "column" => 12],
    20 => ["row" => 4, "column" => 1],
    21 => ["row" => 4, "column" => 3],
    22 => ["row" => 4, "column" => 5],
    23 => ["row" => 4, "column" => 7],
    24 => ["row" => 4, "column" => 9],
    25 => ["row" => 4, "column" => 11],
    26 => ["row" => 4, "column" => 13],
    27 => ["row" => 5, "column" => 2],
    28 => ["row" => 5, "column" => 4],
    29 => ["row" => 5, "column" => 6],
    30 => ["row" => 5, "column" => 8],
    31 => ["row" => 5, "column" => 10],
    32 => ["row" => 5, "column" => 12],
    33 => ["row" => 6, "column" => 1],
    34 => ["row" => 6, "column" => 3],
    35 => ["row" => 6, "column" => 5],
    36 => ["row" => 6, "column" => 7],
    37 => ["row" => 6, "column" => 9],
    38 => ["row" => 6, "column" => 11],
    39 => ["row" => 6, "column" => 13],
    40 => ["row" => 7, "column" => 2],
    41 => ["row" => 7, "column" => 4],
    42 => ["row" => 7, "column" => 6],
    43 => ["row" => 7, "column" => 8],
    44 => ["row" => 7, "column" => 10],
    45 => ["row" => 7, "column" => 12],
    46 => ["row" => 8, "column" => 3],
    47 => ["row" => 8, "column" => 5],
    48 => ["row" => 8, "column" => 7],
    49 => ["row" => 8, "column" => 9],
    50 => ["row" => 8, "column" => 11],
    51 => ["row" => 8, "column" => 13],
    52 => ["row" => 9, "column" => 1],
    53 => ["row" => 9, "column" => 2],
    54 => ["row" => 9, "column" => 4],
    55 => ["row" => 9, "column" => 6],
    56 => ["row" => 9, "column" => 8],
    57 => ["row" => 9, "column" => 10],
    58 => ["row" => 9, "column" => 12],
];

$this->tiles_info = [
    1 => [
        "region" => 1,
        "gem" => 1,
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
        "gem" => 2,
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
        "gem" => 3,
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
        "gem" => 4,
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
        "gem" => 0,
        "effect" => 0,
    ],
    14 => [
        "region" => 2,
        "gem" => 1,
        "effect" => 1
    ],
    15 => [
        "region" => 2,
        "gem" => 1,
        "effect" => 2
    ],
    16 => [
        "region" => 2,
        "gem" => 1,
        "effect" => 3
    ],
    17 => [
        "region" => 2,
        "gem" => 2,
        "effect" => 1
    ],
    18 => [
        "region" => 2,
        "gem" => 2,
        "effect" => 2
    ],
    19 => [
        "region" => 2,
        "gem" => 2,
        "effect" => 3
    ],
    20 => [
        "region" => 2,
        "gem" => 3,
        "effect" => 1
    ],
    21 => [
        "region" => 2,
        "gem" => 3,
        "effect" => 2
    ],
    22 => [
        "region" => 2,
        "gem" => 3,
        "effect" => 3
    ],
    23 => [
        "region" => 2,
        "gem" => 4,
        "effect" => 1
    ],
    24 => [
        "region" => 2,
        "gem" => 4,
        "effect" => 2
    ],
    25 => [
        "region" => 2,
        "gem" => 4,
        "effect" => 3
    ],
    26 => [
        "region" => 2,
        "gem" => 0,
        "effect" => 0
    ],
    27 => [
        "region" => 3,
        "gem" => 1,
        "effect" => 1
    ],
    28 => [
        "region" => 3,
        "gem" => 1,
        "effect" => 2
    ],
    29 => [
        "region" => 3,
        "gem" => 1,
        "effect" => 3
    ],
    30 => [
        "region" => 3,
        "gem" => 2,
        "effect" => 1
    ],
    31 => [
        "region" => 3,
        "gem" => 2,
        "effect" => 2
    ],
    32 => [
        "region" => 3,
        "gem" => 2,
        "effect" => 3
    ],
    33 => [
        "region" => 3,
        "gem" => 3,
        "effect" => 1
    ],
    34 => [
        "region" => 3,
        "gem" => 3,
        "effect" => 2
    ],
    35 => [
        "region" => 3,
        "gem" => 3,
        "effect" => 3
    ],
    36 => [
        "region" => 3,
        "gem" => 4,
        "effect" => 1
    ],
    37 => [
        "region" => 3,
        "gem" => 4,
        "effect" => 2
    ],
    38 => [
        "region" => 3,
        "gem" => 4,
        "effect" => 3
    ],
    39 => [
        "region" => 3,
        "gem" => 0,
        "effect" => 0
    ],
    40 => [
        "region" => 4,
        "gem" => 1,
        "effect" => 1
    ],
    41 => [
        "region" => 4,
        "gem" => 1,
        "effect" => 2
    ],
    42 => [
        "region" => 4,
        "gem" => 1,
        "effect" => 3
    ],
    43 => [
        "region" => 4,
        "gem" => 2,
        "effect" => 1
    ],
    44 => [
        "region" => 4,
        "gem" => 2,
        "effect" => 2
    ],
    45 => [
        "region" => 4,
        "gem" => 2,
        "effect" => 3
    ],
    46 => [
        "region" => 4,
        "gem" => 3,
        "effect" => 1
    ],
    47 => [
        "region" => 4,
        "gem" => 3,
        "effect" => 2
    ],
    48 => [
        "region" => 4,
        "gem" => 3,
        "effect" => 3
    ],
    49 => [
        "region" => 4,
        "gem" => 4,
        "effect" => 1
    ],
    50 => [
        "region" => 4,
        "gem" => 4,
        "effect" => 2
    ],
    51 => [
        "region" => 4,
        "gem" => 4,
        "effect" => 3
    ],
    52 => [
        "region" => 4,
        "gem" => 0,
        "effect" => 0
    ],
    53 => [
        "region" => 5,
        "gem" => 1,
        "effect" => 2,
    ],
    54 => [
        "region" => 5,
        "gem" => 2,
        "effect" => 2,
    ],
    55 => [
        "region" => 5,
        "gem" => 3,
        "effect" => 2,
    ],
    56 => [
        "region" => 5,
        "gem" => 4,
        "effect" => 2,
    ],
    57 => [
        "region" => 5,
        "gem" => 10,
        "effect" => 2,
    ],
    58 => [
        "region" => 5,
        "gem" => 0,
        "effect" => 2,
    ],
];

$this->royaltyTokens_info = [
    1 => [
        "name" => "banner",
        "tr_name" => clienttranslate("Banner"),
        "points" => 3
    ],
    2 => [
        "name" => "scepter",
        "tr_name" => clienttranslate("Scepter"),
        "points" => 5,
    ],
    3 => [
        "name" => "throne",
        "tr_name" => clienttranslate("Throne"),
        "points" => 7,
    ]
];

$this->relics_info = [
    1 => [
        "name" => "Amethyst Ring",
        "tr_name" => clienttranslate("Amethyst Ring"),
        "leadGem" => 1,
        "type" => 1,
        "points" => 5,
        "cost" => [
            1 => 2,
            2 => 1,
            3 => 0,
            4 => 0
        ]
    ],
    2 => [
        "name" => "Citrine Amulet",
        "tr_name" => clienttranslate("Citrine Amulet"),
        "leadGem" => 2,
        "type" => 1,
        "points" => 5,
        "cost" => [
            1 => 0,
            2 => 2,
            3 => 0,
            4 => 1
        ]
    ],
    3 => [
        "name" => "Emerald Brooch",
        "tr_name" => clienttranslate("Emerald Brooch"),
        "leadGem" => 3,
        "type" => 1,
        "points" => 5,
        "cost" => [
            1 => 0,
            2 => 0,
            3 => 2,
            4 => 1
        ]
    ],
    4 => [
        "name" => "Sapphire Chatelaine",
        "tr_name" => clienttranslate("Sapphire Chatelaine"),
        "leadGem" => 4,
        "type" => 1,
        "points" => 5,
        "cost" => [
            1 => 1,
            2 => 0,
            3 => 0,
            4 => 2
        ]
    ],
    5 => [
        "name" => "Amethyst Bracelet",
        "tr_name" => clienttranslate("Amethyst Bracelet"),
        "leadGem" => 1,
        "type" => 1,
        "points" => 6,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 0,
            4 => 1
        ]
    ],
    6 => [
        "name" => "Emerald Jewelry Box",
        "tr_name" => clienttranslate("Emerald Jewelry Box"),
        "leadGem" => 3,
        "type" => 1,
        "points" => 6,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 0
        ]
    ],
    7 => [
        "name" => "Sapphire Pendant",
        "tr_name" => clienttranslate("Sapphire Pendant"),
        "leadGem" => 4,
        "type" => 1,
        "points" => 6,
        "cost" => [
            1 => 1,
            2 => 0,
            3 => 1,
            4 => 1
        ]
    ],
    8 => [
        "name" => "Citrine Earrings",
        "tr_name" => clienttranslate("Citrine Earrings"),
        "leadGem" => 2,
        "type" => 1,
        "points" => 6,
        "cost" => [
            1 => 0,
            2 => 1,
            3 => 1,
            4 => 1
        ]
    ],
    9 => [
        "name" => "Emerald Goggles",
        "tr_name" => clienttranslate("Emerald Goggles"),
        "leadGem" => 3,
        "type" => 2,
        "points" => 5,
        "cost" => [
            1 => 1,
            2 => 0,
            3 => 2,
            4 => 0
        ]
    ],
    10 => [
        "name" => "Sapphire Spyglass",
        "tr_name" => clienttranslate("Sapphire Spyglass"),
        "leadGem" => 4,
        "type" => 2,
        "points" => 5,
        "cost" => [
            1 => 0,
            2 => 1,
            3 => 0,
            4 => 2
        ]
    ],
    11 => [
        "name" => "Citrine Miner's Hat",
        "tr_name" => clienttranslate("Citrine Miner's Hat"),
        "leadGem" => 2,
        "type" => 2,
        "points" => 5,
        "cost" => [
            1 => 0,
            2 => 2,
            3 => 1,
            4 => 0
        ]
    ],
    12 => [
        "name" => "Amethyst Compass",
        "tr_name" => clienttranslate("Amethyst Compass"),
        "leadGem" => 1,
        "type" => 2,
        "points" => 5,
        "cost" => [
            1 => 2,
            2 => 0,
            3 => 0,
            4 => 1
        ]
    ],
    13 => [
        "name" => "Sapphire Droid",
        "tr_name" => clienttranslate("Sapphire Droid"),
        "leadGem" => 4,
        "type" => 2,
        "points" => 7,
        "cost" => [
            1 => 0,
            2 => 2,
            3 => 0,
            4 => 2
        ]
    ],
    14 => [
        "name" => "Amethyst Power Core",
        "tr_name" => clienttranslate("Amethyst Power Core"),
        "leadGem" => 1,
        "type" => 2,
        "points" => 7,
        "cost" => [
            1 => 2,
            2 => 0,
            3 => 2,
            4 => 0
        ]
    ],
    15 => [
        "name" => "Sapphire Sun Dial",
        "tr_name" => clienttranslate("Sapphire Sun Dial"),
        "leadGem" => 4,
        "type" => 3,
        "points" => 5,
        "cost" => [
            1 => 0,
            2 => 0,
            3 => 1,
            4 => 2
        ]
    ],
    16 => [
        "name" => "Amethyst Vase",
        "tr_name" => clienttranslate("Amethyst Vase"),
        "leadGem" => 1,
        "type" => 3,
        "points" => 6,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 0
        ]
    ],
    17 => [
        "name" => "Emerald Lantern",
        "tr_name" => clienttranslate("Emerald Lantern"),
        "leadGem" => 3,
        "type" => 3,
        "points" => 6,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 0
        ]
    ],
    18 => [
        "name" => "Citrine Plate",
        "tr_name" => clienttranslate("Citrine Plate"),
        "leadGem" => 2,
        "type" => 3,
        "points" => 6,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 0,
            4 => 1
        ]
    ],
    19 => [
        "name" => "Citrine Talisman",
        "tr_name" => clienttranslate("Citrine Talisman"),
        "leadGem" => 2,
        "type" => 3,
        "points" => 9,
        "cost" => [
            1 => 1,
            2 => 2,
            3 => 0,
            4 => 1
        ]
    ],
    20 => [
        "name" => "Emerald Tablet",
        "tr_name" => clienttranslate("Emerald Tablet"),
        "leadGem" => 3,
        "type" => 3,
        "points" => 9,
        "cost" => [
            1 => 1,
            2 => 0,
            3 => 2,
            4 => 1
        ]
    ],
    21 => [
        "name" => "Iridia Chalice",
        "tr_name" => clienttranslate("Iridia Chalice"),
        "leadGem" => 0,
        "type" => 0,
        "points" => 10,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ]
    ],
    22 => [
        "name" => "Iridia Crown",
        "tr_name" => clienttranslate("Iridia Crown"),
        "leadGem" => 0,
        "type" => 0,
        "points" => 10,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ]
    ],
    23 => [
        "name" => "Iridia Tiara",
        "tr_name" => clienttranslate("Iridia Tiara"),
        "leadGem" => 0,
        "type" => 0,
        "points" => 10,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ]
    ],
    24 => [
        "name" => "The Book of Iridescia",
        "tr_name" => clienttranslate("The Book of Iridescia"),
        "leadGem" => 0,
        "type" => 0,
        "points" => 10,
        "cost" => [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 1
        ]
    ]
];

$this->objectives_info = [
    1 => [
        "name" => "Glimmering Gold",
        "tr_name" => clienttranslate("Glimmering Gold"),
        "content" => clienttranslate("Finish the game with the most coins"),
        "points" => 7,
        "variable" => null,
    ],
    2 => [
        "name" => "Amethyst Aficionado",
        "tr_name" => clienttranslate("Amethyst Aficionado"),
        "content" => clienttranslate("Collect the most Amethyst tiles"),
        "points" => 5,
        "variable" => 1,
    ],
    3 => [
        "name" => "Citrine Crazy",
        "tr_name" => clienttranslate("Citrine Crazy"),
        "content" => clienttranslate("Collect the most Citrine tiles"),
        "points" => 5,
        "variable" => 2,
    ],
    4 => [
        "name" => "Emerald Enthusiast",
        "tr_name" => clienttranslate("Emerald Enthusiast"),
        "content" => clienttranslate("Collect the most Emerald tiles"),
        "points" => 5,
        "variable" => 3,
    ],
    5 => [
        "name" => "Sapphire Savant",
        "tr_name" => clienttranslate("Sapphire Savant"),
        "content" => clienttranslate("Collect the most Sapphire tiles"),
        "points" => 5,
        "variable" => 4,
    ],
    6 => [
        "name" => "Regal Rainbow",
        "tr_name" => clienttranslate("Regal Rainbow"),
        "content" => clienttranslate("Restore 1 Relic from each Gem type"),
        "points" => 7,
        "variable" => null,
    ],
    7 => [
        "name" => "All About Amethyst",
        "tr_name" => clienttranslate("All About Amethyst"),
        "content" => clienttranslate("Restore the most Amethyst Relics"),
        "points" => 7,
        "variable" => 1,
    ],
    8 => [
        "name" => "Classy Citrine",
        "tr_name" => clienttranslate("Classy Citrine"),
        "content" => clienttranslate("Restore the most Citrine Relics"),
        "points" => 7,
        "variable" => 2,
    ],
    9 => [
        "name" => "Emerald Euphoria",
        "tr_name" => clienttranslate("Emerald Euphoria"),
        "content" => clienttranslate("Restore the most Emerald Relics"),
        "points" => 7,
        "variable" => 3,
    ],
    10 => [
        "name" => "Sapphire Saturation",
        "tr_name" => clienttranslate("Sapphire Saturation"),
        "content" => clienttranslate("Restore the most Sapphire Relics"),
        "points" => 7,
        "variable" => 4,
    ],
    11 => [
        "name" => "Remarkable Restorer",
        "tr_name" => clienttranslate("Remarkable Restorer"),
        "content" => clienttranslate("Restore 5 or more Relics"),
        "points" => 7,
        "variable" => null,
    ],
    12 => [
        "name" => "Jazzy Jeweler",
        "tr_name" => clienttranslate("Jazzy Jeweler"),
        "content" => clienttranslate("Restore the most Jewelry Relics"),
        "points" => 5,
        "variable" => 1,
    ],
    13 => [
        "name" => "Lots-O-Lore",
        "tr_name" => clienttranslate("Lots-O-Lore"),
        "content" => clienttranslate("Restore the most Lore Relics"),
        "points" => 7,
        "variable" => 2,
    ],
    14 => [
        "name" => "Tantalizing Tech",
        "tr_name" => clienttranslate("Tantalizing Tech"),
        "content" => clienttranslate("Restore the most Tech Relics"),
        "points" => 7,
        "variable" => 3
    ],
    15 => [
        "name" => "Master Miner",
        "tr_name" => clienttranslate("Master Miner"),
        "content" => clienttranslate("Restore 1 Relic from each of the 3 categories"),
        "points" => 7,
        "variable" => null,
    ]
];

$this->items_info = [
    1 => [
        "name" => "Cauldron of Fortune",
        "tr_name" => clienttranslate("Cauldron of Fortune"),
        "content" => clienttranslate("Trade two gems in your cargo hold for one gem of your choice from the supply."),
        "details" => [
            clienttranslate("The gems you trade in can be any combination and do not need to be the same type."),
        ],
        "cost" => 4,
    ],
    2 => [
        "name" => "Regal Reference Book",
        "tr_name" => clienttranslate("Regal Reference Book"),
        "content" => clienttranslate("Choose a card from the Relics deck or from the row of Relics and reserve it."),
        "details" => [
            clienttranslate("The Regal Reference Book card is placed on top of the chosen card."),
            clienttranslate("Only you can restore this relic."),
            clienttranslate("Although reserved, it must be restored to earn points; however, there is no penalty for not restoring it."),
        ],
        "cost" => 2,
    ],
    3 => [
        "name" => "Marvelous Mine Cart",
        "tr_name" => clienttranslate("Marvelous Mine Cart"),
        "content" => clienttranslate("Gain 2x gems when mining this turn."),
        "details" => [
            clienttranslate("Do not double the initial gem gained from stepping on the tile."),
            clienttranslate("Active for the entire turn."),
            clienttranslate("Must be declared prior to rolling your Mining dice."),
        ],
        "cost" => 4,
    ],
    4 => [
        "name" => "Epic Elixir",
        "tr_name" => clienttranslate("Epic Elixir"),
        "content" => clienttranslate("Take an additional turn."),
        "details" => [
            clienttranslate("Declare its use before the next player starts their turn."),
            clienttranslate("Upon playing, start a full turn sequence."),
            clienttranslate("You cannot purchase another Epic Elixir card on a turn started by using one."),
            clienttranslate("You may cancel it if the additional turn hasn't started yet."),
        ],
        "cost" => 6,
    ],
    5 => [
        "name" => "Lucky Libation",
        "tr_name" => clienttranslate("Lucky Libation"),
        "content" => clienttranslate("Re-roll any of your dice or roll up to 4 gem market dice."),
        "cost" => 2,
    ],
    6 => [
        "name" => "Jolty Jackhammer",
        "tr_name" => clienttranslate("Jolty Jackhammer"),
        "content" => clienttranslate("Modify any die by +/-1."),
        "details" => [
            clienttranslate("This can be either a die that you've rolled during a mining attempt or one of the Gem Market dice."),
            clienttranslate("Dice values can change from six to one and vice-versa."),
        ],
        "cost" => 2,
    ],
    7 => [
        "name" => "Dazzling Dynamite",
        "tr_name" => clienttranslate("Dazzling Dynamite"),
        "content" => clienttranslate("Modify any die by up to +/-2."),
        "details" => [
            clienttranslate("This can be either a die that you've rolled during a mining attempt or one of the Gem Market dice."),
            clienttranslate("Dice values can change from six to one and vice-versa."),
        ],
        "cost" => 3,
    ],
    8 => [
        "name" => "Axe of Awesomeness",
        "tr_name" => clienttranslate("Axe of Awesomeness"),
        "content" => clienttranslate("Split one gem in your cargo hold into two gems of that same type."),
        "details" => [],
        "cost" => 3,
    ],
    9 => [
        "name" => "Prosperous Pickaxe",
        "tr_name" => clienttranslate("Prosperous Pickaxe"),
        "content" => clienttranslate("For every gem gained from mining this turn, gain a gem from a revealed adjacent tile."),
        "details" => [
            clienttranslate("You still need to spend the required coins per mining attempt when using this item."),
            clienttranslate("You must pick the same adjacent tile for all attempts, which limits you to gaining two gem types."),
            clienttranslate("Must be declared prior to rolling your Mining dice."),
        ],
        "cost" => 6,
    ],
    10 => [
        "name" => "Swapping Stones",
        "tr_name" => clienttranslate("Swapping Stones"),
        "content" => clienttranslate("Swap location with any player."),
        "details" => [
            clienttranslate("After swapping location, play a turn as normal starting with Step One (Reveal Tiles)."),
            clienttranslate("Must be played at the beginning of your turn, prior to revealing tiles."),
        ],
        "cost" => 3,
    ],
    11 => [
        "name" => "Clever Catapult",
        "tr_name" => clienttranslate("Clever Catapult"),
        "content" => clienttranslate("Jump over one adjacent tile space onto an unoccupied tile space."),
        "details" => [
            clienttranslate("If you land on a revealed tile, continue your turn as usual. If it's unrevealed, reveal it, collect the gem, and proceed."),
            clienttranslate("Must be played at the beginning of your turn, prior to revealing tiles."),
        ],       
        "cost" => 3,
    ],
    12 => [
        "name" => "Wishing Well",
        "tr_name" => clienttranslate("Wishing Well"),
        "content" => clienttranslate("Roll your mining dice. Gain one gem with market value equal to or lower than one of your dice."),
        "details" => [
            clienttranslate("This is not a mining action and cannot be combined with the Marvelous Mine Cart or Prosperous Pickaxe."),
        ],
        "cost" => 5,
    ]
];

$this->rhom_info = [
    1 => [
        "directions" => [
            "left" => 1,
            "topLeft" => 2,
            "topRight" => 3,
            "right" => 4,
        ],
        "effect" => 1,
        "weathervane" => "left",
    ],
    2 => [
        "directions" => [
            "left" => 2,
            "topLeft" => 3,
            "topRight" => 4,
            "right" => 1,
        ],
        "effect" => 1,
        "weathervane" => "left",
    ],
    3 => [
        "directions" => [
            "left" => 3,
            "topLeft" => 4,
            "topRight" => 1,
            "right" => 2,
        ],
        "effect" => 1,
        "weathervane" => "right",
    ],
    4 => [
        "directions" => [
            "left" => 4,
            "topLeft" => 1,
            "topRight" => 2,
            "right" => 3,
        ],
        "effect" => 1,
        "weathervane" => "right",
    ],
    5 => [
        "directions" => [
            "left" => 1,
            "topLeft" => 2,
            "topRight" => 3,
            "right" => 4,
        ],
        "effect" => 2,
        "weathervane" => "right",
    ],
    6 => [
        "directions" => [
            "left" => 2,
            "topLeft" => 3,
            "topRight" => 4,
            "right" => 1,
        ],
        "effect" => 2,
        "weathervane" => "right",
    ],
    7 => [
        "directions" => [
            "left" => 3,
            "topLeft" => 4,
            "topRight" => 1,
            "right" => 2,
        ],
        "effect" => 2,
        "weathervane" => "left",
    ],
    8 => [
        "directions" => [
            "left" => 4,
            "topLeft" => 1,
            "topRight" => 2,
            "right" => 3,
        ],
        "effect" => 2,
        "weathervane" => "left",
    ],
    9 => [
        "directions" => [
            "left" => 1,
            "topLeft" => 2,
            "topRight" => 3,
            "right" => 4,
        ],
        "effect" => 3,
        "weathervane" => "left",
    ],
    10 => [
        "directions" => [
            "left" => 2,
            "topLeft" => 3,
            "topRight" => 4,
            "right" => 1,
        ],
        "effect" => 3,
        "weathervane" => "right",
    ],
    11 => [
        "directions" => [
            "left" => 3,
            "topLeft" => 4,
            "topRight" => 1,
            "right" => 2,
        ],
        "effect" => 3,
        "weathervane" => "right",
    ],
    12 => [
        "directions" => [
            "left" => 4,
            "topLeft" => 1,
            "topRight" => 2,
            "right" => 3,
        ],
        "effect" => 3,
        "weathervane" => "left",
    ],
    13 => [
        "directions" => [
            "left" => 1,
            "topLeft" => 2,
            "topRight" => 3,
            "right" => 4,
        ],
        "effect" => 4,
        "weathervane" => "left",
    ],
    14 => [
        "directions" => [
            "left" => 2,
            "topLeft" => 3,
            "topRight" => 4,
            "right" => 1,
        ],
        "effect" => 4,
        "weathervane" => "left",
    ],
    15 => [
        "directions" => [
            "left" => 3,
            "topLeft" => 4,
            "topRight" => 1,
            "right" => 2,
        ],
        "effect" => 4,
        "weathervane" => "right",
    ],
    16 => [
        "directions" => [
            "left" => 4,
            "topLeft" => 1,
            "topRight" => 2,
            "right" => 3,
        ],
        "effect" => 4,
        "weathervane" => "right",
    ],
];