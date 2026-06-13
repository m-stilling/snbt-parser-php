# SNBT Parser

[![tests](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml) [![Packagist Version](https://img.shields.io/packagist/v/stilling/snbt-parser)](https://packagist.org/packages/stilling/snbt-parser)

Turn [Minecraft SNBT](https://minecraft.wiki/w/NBT_format#SNBT_format) data into the corresponding PHP data types. 

```
composer require stilling/snbt-parser
```

> [!TIP]
> Need to fetch this data from a server first? [`stilling/minecraft-rcon`](https://packagist.org/packages/stilling/minecraft-rcon) is a lightweight Minecraft RCON client that handles multi-packet responses - run commands like `data get ...` and feed the output straight into this parser.

> [!NOTE]
> Under the hood this package transposes the SNBT to JSON and decodes it with `json_decode()`. Numeric values keep their full precision, but the NBT type suffixes are not retained - every integer type (`b`/`s`/`i`/`l`) becomes a PHP `int` and every floating-point type (`f`/`d`) becomes a PHP `float`. A potential v2 may parse SNBT directly to preserve type information and skip the JSON round-trip.

Here's an example parsing the SNBT data of a chest using the following command: `data get block -40 73 -11`

```php
use Stilling\SNBTParser\SNBTParser;

SNBTParser::parse('{z: -11, x: -40, id: "minecraft:chest", y: 73, Items: [{count: 1, Slot: 0b, id: "minecraft:golden_horse_armor"}, {count: 1, Slot: 1b, id: "minecraft:saddle"}, {count: 1, Slot: 2b, components: {"minecraft:repair_cost": 1, "minecraft:enchantments": {"minecraft:luck_of_the_sea": 2, "minecraft:lure": 2, "minecraft:unbreaking": 3}, "minecraft:damage": 10}, id: "minecraft:fishing_rod"}, {count: 1, Slot: 3b, id: "minecraft:shield"}]}')

// returns ->

[
    "z" => -11,
    "x" => -40,
    "id" => "minecraft:chest",
    "y" => 73,
    "Items" => [
        [
            "count" => 1,
            "Slot" => 0,
            "id" => "minecraft:golden_horse_armor",
        ],
        [
            "count" => 1,
            "Slot" => 1,
            "id" => "minecraft:saddle",
        ],
        [
            "count" => 1,
            "Slot" => 2,
            "components" => [
                "minecraft:repair_cost" => 1,
                "minecraft:enchantments" => [
                    "minecraft:luck_of_the_sea" => 2,
                    "minecraft:lure" => 2,
                    "minecraft:unbreaking" => 3,
                ],
                "minecraft:damage" => 10,
            ],
            "id" => "minecraft:fishing_rod",
        ],
        [
            "count" => 1,
            "Slot" => 3,
            "id" => "minecraft:shield",
        ],
    ],
]
```
