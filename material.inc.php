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
        "name" => "septor",
        "tr_name" => clienttranslate("Septor"),
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
            3 => 1,
            4 => 0
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
            1 => 1,
            2 => 2,
            3 => 0,
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
            3 => 1,
            4 => 0
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
        "points" => 9,
        "cost" => [
            1 => 2,
            2 => 0,
            3 => 1,
            4 => 1
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
        "points" => 5,
        "cost" => [
            1 => 2,
            2 => 1,
            3 => 0,
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
            2 => 0,
            3 => 1,
            4 => 1
        ]
    ],
    18 => [
        "name" => "Citrine Plate",
        "tr_name" => clienttranslate("Citrine Plate"),
        "leadGem" => 2,
        "type" => 3,
        "points" => 6,
        "cost" => [
            1 => 0,
            2 => 1,
            3 => 1,
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
            3 => 1,
            4 => 0
        ]
    ],
    20 => [
        "name" => "Emerald Tablet",
        "tr_name" => clienttranslate("Emerald Tablet"),
        "leadGem" => 3,
        "type" => 3,
        "points" => 9,
        "cost" => [
            1 => 0,
            2 => 1,
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
        "content" => clienttranslate("Trade any two gems in your cargo hold for one gem of your choice from the supply."),
        "cost" => 4,
    ],
    2 => [
        "name" => "Regal Reference Book",
        "tr_name" => clienttranslate("Regal Reference Book"),
        "content" => clienttranslate("Search the relic deck for a card of your choice or take one from relic row. Gain that relic card and place this card on top of it. Only you can restore this relic."),
        "cost" => 2,
    ],
    3 => [
        "name" => "Marvelous Mine Cart",
        "tr_name" => clienttranslate("Marvelous Mine Cart"),
        "content" => clienttranslate("Gain 2x gems when mining this turn (when rolling your mining dice)."),
        "cost" => 4,
    ],
    4 => [
        "name" => "Epic Elixir",
        "tr_name" => clienttranslate("Epic Elixir"),
        "content" => clienttranslate("Take an additional turn."),
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
        "content" => clienttranslate("Modify any one die by +/-1. This can either be a die that you've rolled or a gem market dice."),
        "cost" => 2,
    ],
    7 => [
        "name" => "Dazzling Dynamite",
        "tr_name" => clienttranslate("Dazzling Dynamite"),
        "content" => clienttranslate("Modify any one die by up to +/-2. This can either be a die that you've rolled or a gem market dice."),
        "cost" => 3,
    ],
    8 => [
        "name" => "Axe of Awesomeness",
        "tr_name" => clienttranslate("Axe of Awesomeness"),
        "content" => clienttranslate("Split one gem in your cargo hold into two gems of the same type."),
        "cost" => 3,
    ],
    9 => [
        "name" => "Prosperous Pickaxe",
        "tr_name" => clienttranslate("Prosperous Pickaxe"),
        "content" => clienttranslate("For every gem gained from mining on one tile this turn, gain a gem from a revealed adjacent tile."),
        "cost" => 6,
    ],
    10 => [
        "name" => "Swapping Stones",
        "tr_name" => clienttranslate("Swapping Stones"),
        "content" => clienttranslate("Swap location with any player. (Must be played at the beginning of your turn, prior to revealing tiles.)"),
        "cost" => 3,
    ],
    11 => [
        "name" => "Clever Catapult",
        "tr_name" => clienttranslate("Clever Catapult"),
        "content" => clienttranslate("Jump over one adjacent tile space onto an unoccupied tile space. If the tile is not yet revealed, reveal it after landing. (Must be played at the start of your turn, prior to revealing tiles.)"),
        "cost" => 3,
    ]
];

$this->weathervane_info = [
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
        "weathervane" => "left",
    ],
    6 => [
        "directions" => [
            "left" => 2,
            "topLeft" => 3,
            "topRight" => 4,
            "right" => 1,
        ],
        "effect" => 2,
        "weathervane" => "left",
    ],
    7 => [
        "directions" => [
            "left" => 3,
            "topLeft" => 4,
            "topRight" => 1,
            "right" => 2,
        ],
        "effect" => 2,
        "weathervane" => "right",
    ],
    8 => [
        "directions" => [
            "left" => 4,
            "topLeft" => 1,
            "topRight" => 2,
            "right" => 3,
        ],
        "effect" => 2,
        "weathervane" => "right",
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
        "weathervane" => "left",
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
        "weathervane" => "right",
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
