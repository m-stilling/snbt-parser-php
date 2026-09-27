# SNBT Parser

[![tests](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml) [![Packagist Version](https://img.shields.io/packagist/v/stilling/snbt-parser)](https://packagist.org/packages/stilling/snbt-parser)

Read [Minecraft SNBT](https://minecraft.wiki/w/NBT_format#SNBT_format) into PHP values or typed tags, change it, and write it back to SNBT.

```
composer require stilling/snbt-parser
```

The package requires PHP 8.3 or later.

## Quick start

### Read SNBT as PHP values

```php
use Stilling\SNBTParser\SNBTParser;

SNBTParser::parse('{id: "minecraft:shield", Slot: 3b, count: 1, Pos: [1.5d, 64.0d, -2.5d]}');
// ["id" => "minecraft:shield", "Slot" => 3, "count" => 1, "Pos" => [1.5, 64.0, -2.5]]
```

`parse()` returns native PHP values. Every integer type (`b`, `s`, `i`, `l`) becomes an `int`, and every floating-point type (`f`, `d`) becomes a `float`. To keep the NBT types, use `parseTyped()` or `parseCompound()`.

### Read typed values

`parseCompound()` returns a `CompoundTag`. Its getters return PHP values and check the tag type:

```php
use Stilling\SNBTParser\SNBTParser;

$player = SNBTParser::parseCompound('{Health: 20.0f, OnGround: 1b, Pos: [-78.5d, 65.0d, -19.5d], abilities: {flying: 0b}}');

$player->getFloat("Health");                          // 20.0
$player->getBool("OnGround");                         // true
$player->getList("Pos")->getFloat(1);                 // 65.0
$player->getCompound("abilities")->getBool("flying"); // false
```

### Change a tag and write SNBT

Tags are immutable. `with()` and `without()` return a changed copy. `toSnbt()` writes the tag with its NBT types:

```php
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\ByteTag;

$item = SNBTParser::parseCompound('{Slot: 3b, id: "minecraft:shield", count: 1}');

$item->with("Slot", new ByteTag(4))->without("count")->toSnbt();
// '{Slot:4b,id:"minecraft:shield"}'
```

## Documentation

- [Typed tags](https://github.com/m-stilling/snbt-parser-php/blob/main/docs/typed-tags.md) - tag classes, typed getters, building and changing tags, UUIDs
- [Writing SNBT](https://github.com/m-stilling/snbt-parser-php/blob/main/docs/writing-snbt.md) - output formats, and how `toSnbt()` writes keys, numbers and strings
- [Supported syntax](https://github.com/m-stilling/snbt-parser-php/blob/main/docs/syntax.md) - the SNBT that the parser reads: numbers, strings and operations
- [Errors](https://github.com/m-stilling/snbt-parser-php/blob/main/docs/errors.md) - exceptions and error positions

## See also

[`stilling/minecraft-rcon`](https://packagist.org/packages/stilling/minecraft-rcon) is a Minecraft RCON client that handles multi-packet responses. Use it to run commands such as `data get ...` on a server, and give the output to this parser.
