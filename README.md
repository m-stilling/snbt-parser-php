# SNBT Parser

[![tests](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml) [![Packagist Version](https://img.shields.io/packagist/v/stilling/snbt-parser)](https://packagist.org/packages/stilling/snbt-parser)

Turn [Minecraft SNBT](https://minecraft.wiki/w/NBT_format#SNBT_format) data into the corresponding PHP data types. 

```
composer require stilling/snbt-parser
```

> [!TIP]
> Need to fetch this data from a server first? [`stilling/minecraft-rcon`](https://packagist.org/packages/stilling/minecraft-rcon) is a lightweight Minecraft RCON client that handles multi-packet responses - run commands like `data get ...` and feed the output straight into this parser.

> [!NOTE]
> `parse()` returns native PHP types, collapsing the NBT type suffixes the way you usually want them - every integer type (`b`/`s`/`i`/`l`) becomes a PHP `int` and every floating-point type (`f`/`d`) becomes a PHP `float`. When you need to keep the exact NBT types - or re-serialize back to SNBT - use [`parseTyped()`](#preserving-nbt-types) instead.

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

## Preserving NBT types

`parse()` is lossy by design - it can't tell a `byte` from an `int`. When the distinction matters, or you want to edit and re-serialize SNBT, use `parseTyped()`, which returns a tree of typed tags instead:

```php
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\ByteTag;

$tag = SNBTParser::parseTyped('{ Slot: 3b, id: "minecraft:shield" }');

$tag->get("Slot") instanceof ByteTag; // true
$tag->get("id")->toPhp();             // "minecraft:shield"

$tag->toPhp();                        // [ "Slot" => 3, "id" => "minecraft:shield" ]
$tag->toSnbt();                       // '{Slot:3b,id:"minecraft:shield"}'
```

Every value becomes a `Tag` subclass under `Stilling\SNBTParser\Tag`: `ByteTag`, `ShortTag`, `IntTag`, `LongTag`, `FloatTag`, `DoubleTag`, `BooleanTag`, `StringTag`, `ByteArrayTag`, `IntArrayTag`, `LongArrayTag`, `ListTag` and `CompoundTag`. Each one exposes:

- `toPhp()` - the native PHP value (the same thing `parse()` returns)
- `toSnbt()` - the value re-serialized back to SNBT, preserving its type

`CompoundTag` additionally provides `get(string $key): ?Tag` and `has(string $key): bool`, and the container tags expose their contents as readonly `entries` / `items` / `values` properties.

### Formatting the output

`toSnbt()` accepts an `SNBTFormat` to control its layout. It defaults to `Compact`:

```php
use Stilling\SNBTParser\SNBTFormat;
use Stilling\SNBTParser\SNBTParser;

$tag = SNBTParser::parseTyped('{name: "Steve", pos: [1.0d, 2.0d], nested: {a: 1b}}');

$tag->toSnbt();                      // {name:"Steve",pos:[1.0d,2.0d],nested:{a:1b}}
$tag->toSnbt(SNBTFormat::Spaced);    // {name: "Steve", pos: [1.0d, 2.0d], nested: {a: 1b}}
$tag->toSnbt(SNBTFormat::Pretty);
```

`SNBTFormat::Pretty` indents compounds and lists across lines (four spaces per level), while keeping typed number arrays on a single line:

```
{
    name: "Steve",
    pos: [
        1.0d,
        2.0d
    ],
    nested: {
        a: 1b
    }
}
```

All three formats produce valid SNBT that parses back to the same tree.

## Converting UUIDs

Minecraft stores UUIDs as four-integer arrays (e.g. `UUID: [I; 110787060, 1156138790, -1514210135, 238594805]`). Once parsed, pass that array to `intsToUuid()` to get the canonical string form:

```php
use Stilling\SNBTParser\SNBTParser;

SNBTParser::intsToUuid([110787060, 1156138790, -1514210135, 238594805]);

// returns -> "069a79f4-44e9-4726-a5be-fca90e38aaf5"
```
