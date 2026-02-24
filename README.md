# SNBT Parser

[![tests](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml) [![Packagist Version](https://img.shields.io/packagist/v/stilling/snbt-parser)](https://packagist.org/packages/stilling/snbt-parser)

Turn [Minecraft SNBT](https://minecraft.wiki/w/NBT_format#SNBT_format) data into the corresponding PHP data types. 

```
composer require stilling/snbt-parser
```

> [!NOTE]
> Technically, this package transposes the SNBT data to JSON and parses that using `json_decode()`, which may not be too performant. A potential v2 may parse SNBT directly.

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
