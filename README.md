# SNBT Parser

[![tests](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/m-stilling/snbt-parser-php/actions/workflows/tests.yml) [![Packagist Version](https://img.shields.io/packagist/v/stilling/snbt-parser)](https://packagist.org/packages/stilling/snbt-parser)

Turn [Minecraft SNBT](https://minecraft.wiki/w/NBT_format#SNBT_format) data into the corresponding PHP data types. 

```
composer require stilling/snbt-parser
```

The package requires PHP 8.3 or later.

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

`parse()` turns an empty compound `{}` and an empty list `[]` into the same empty PHP array. Use `parseTyped()` to tell them apart.

## Preserving NBT types

`parse()` is lossy by design - it can't tell a `byte` from an `int`. When the distinction matters, or you want to edit and re-serialize SNBT, use `parseTyped()`, which returns a tree of typed tags instead:

```php
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\ByteTag;

$tag = SNBTParser::parseCompound('{ Slot: 3b, id: "minecraft:shield" }');

$tag->get("Slot") instanceof ByteTag; // true
$tag->get("id")->toPhp();             // "minecraft:shield"

$tag->toPhp();                        // [ "Slot" => 3, "id" => "minecraft:shield" ]
$tag->toSnbt();                       // '{Slot:3b,id:"minecraft:shield"}'
```

Every value becomes a `Tag` subclass under `Stilling\SNBTParser\Tag`: `ByteTag`, `ShortTag`, `IntTag`, `LongTag`, `FloatTag`, `DoubleTag`, `BooleanTag`, `StringTag`, `ByteArrayTag`, `IntArrayTag`, `LongArrayTag`, `ListTag` and `CompoundTag`. Each one exposes:

- `toPhp()` - the native PHP value (the same thing `parse()` returns)
- `toSnbt()` - the value re-serialized back to SNBT, preserving its type

`CompoundTag` additionally provides `get(string $key): ?Tag` and `has(string $key): bool`, and `ListTag` provides `get(int $index): ?Tag`. The container tags are `Countable` and iterable (`count($tag)`, `foreach ($tag as $key => $value)`), and expose their contents as readonly `entries` / `items` / `values` properties.

Iterating a `CompoundTag` yields string keys. PHP stores a numeric-string key such as `"0"` as an `int` key, so `entries` and `toPhp()` can hold `int` keys.

`parseTyped()` returns `Tag`. Use `parseCompound()` when the root must be a compound, for example the output of `data get`. It returns `CompoundTag`, and it throws `SNBTParseException` when the root is a different tag.

### Reading typed values

`CompoundTag` and `ListTag` have typed getters. They take a key (compound) or an index (list). Each getter throws `SNBTTagException` when the entry is missing or holds a different tag type.

| Getter | Returns | Accepts |
| --- | --- | --- |
| `getString()` | `string` | `StringTag` |
| `getInt()` | `int` | `ByteTag`, `ShortTag`, `IntTag`, `LongTag` |
| `getFloat()` | `float` | `FloatTag`, `DoubleTag` |
| `getBool()` | `bool` | `BooleanTag`, and `ByteTag` (non-zero is `true`) |
| `getCompound()` | `CompoundTag` | `CompoundTag` |
| `getList()` | `ListTag` | `ListTag` |
| `getAs($key, $class)` | an instance of `$class` | `$class` and its subclasses |

```php
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\IntArrayTag;

$player = SNBTParser::parseCompound('{Health: 20.0f, OnGround: 1b, Pos: [-78.5d, 65.0d, -19.5d], abilities: {flying: 0b}, UUID: [I; 110787060, 1156138790, -1514210135, 238594805]}');

$player->getFloat("Health");                          // 20.0
$player->getBool("OnGround");                         // true
$player->getList("Pos")->getFloat(1);                 // 65.0
$player->getCompound("abilities")->getBool("flying"); // false
$player->getAs("UUID", IntArrayTag::class)->toUuid(); // "069a79f4-44e9-4726-a5be-fca90e38aaf5"

$player->getInt("Health"); // throws SNBTTagException: Expected IntegerTag at key "Health", found FloatTag.
```

The getters have PHPStan generics, so static analysis knows the return type of each call.

### Building and changing tags

`Tag::fromPhp()` builds a tag tree from native PHP values:

| PHP value | Tag |
| --- | --- |
| `bool` | `BooleanTag` |
| `int` | `IntTag`, or `LongTag` outside the 32-bit range |
| `float` | `DoubleTag` |
| `string` | `StringTag` |
| list array | `ListTag` |
| other array | `CompoundTag` |
| `Tag` | the same tag |

An empty array becomes an empty `ListTag`. Use `new CompoundTag([])` for an empty compound. Other values, such as `null` and objects, cause `SNBTInvalidArgumentException`. To get a different NBT type, put a tag in the array:

```php
use Stilling\SNBTParser\Tag\ByteTag;
use Stilling\SNBTParser\Tag\Tag;

Tag::fromPhp(["Slot" => new ByteTag(0), "id" => "minecraft:lead", "count" => 1])->toSnbt();
// '{Slot:0b,id:"minecraft:lead",count:1}'
```

Tags are immutable. These methods return a changed copy:

- `CompoundTag::with(string $key, Tag $tag)` - sets a key. An existing key keeps its position. A new key goes last.
- `CompoundTag::without(string $key)` - removes a key. A missing key is not an error.
- `ListTag::with(int $index, Tag $tag)` - replaces an item. It throws `SNBTTagException` when the index is out of range.
- `ListTag::withAppended(Tag $tag)` - adds an item at the end.
- `ListTag::without(int $index)` - removes an item. The later items move down by one.

```php
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\ByteTag;

$item = SNBTParser::parseCompound('{Slot: 3b, id: "minecraft:shield", count: 1}');

$item->with("Slot", new ByteTag(4))->without("count")->toSnbt();
// '{Slot:4b,id:"minecraft:shield"}'
```

### Formatting the output

`toSnbt()` accepts an `SNBTFormat` to control its layout. It defaults to `Compact`:

```php
use Stilling\SNBTParser\SNBTFormat;
use Stilling\SNBTParser\SNBTParser;

$tag = SNBTParser::parseTyped('{name: "Steve", pos: [1.0d, 2.0d], nested: {a: 1b}}');

$tag->toSnbt();                      // {name:"Steve",pos:[1.0d,2.0d],nested:{a:1b}}
$tag->toSnbt(SNBTFormat::Spaced);    // { name: "Steve", pos: [ 1.0d, 2.0d ], nested: { a: 1b } }
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

Minecraft stores UUIDs as four-integer arrays, e.g. `UUID: [I; 110787060, 1156138790, -1514210135, 238594805]`. Four helpers convert between that form and the canonical string. `uuidToInts()` and `uuidToSnbt()` accept the hyphenated form or 32 bare hex digits, in either case. `intsToUuid()` and `toUuid()` throw `SNBTInvalidArgumentException` unless the array holds exactly four integers. `uuidToInts()` and `uuidToSnbt()` throw it for a string that is not a UUID. `SNBTInvalidArgumentException` extends `InvalidArgumentException`.

```php
use Stilling\SNBTParser\SNBTFormat;
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\IntArrayTag;

SNBTParser::intsToUuid([110787060, 1156138790, -1514210135, 238594805]);
// "069a79f4-44e9-4726-a5be-fca90e38aaf5"

SNBTParser::parseCompound('{UUID: [I; 110787060, 1156138790, -1514210135, 238594805]}')->getAs("UUID", IntArrayTag::class)->toUuid();
// "069a79f4-44e9-4726-a5be-fca90e38aaf5"

SNBTParser::uuidToInts("069a79f4-44e9-4726-a5be-fca90e38aaf5");
// [110787060, 1156138790, -1514210135, 238594805]

SNBTParser::uuidToSnbt("069a79f4-44e9-4726-a5be-fca90e38aaf5");
// "[I;110787060,1156138790,-1514210135,238594805]"

SNBTParser::uuidToSnbt("069a79f4-44e9-4726-a5be-fca90e38aaf5", SNBTFormat::Spaced);
// "[I; 110787060, 1156138790, -1514210135, 238594805]"
```

## Supported syntax

The parser reads the SNBT that Minecraft writes, for example the output of `data get`, and the SNBT syntax that Java Edition 1.21.5 added for commands and data packs:

- compounds, lists, and the typed arrays `[B;...]`, `[I;...]` and `[L;...]`
- lists with items of different types, for example `[1, "a", {b: 2}]`
- numbers with the suffixes `b`, `s`, `i`, `l`, `f` and `d`, in either case, and numbers without a suffix (`1` is an int, `1.0` and `1e3` are doubles)
- the number forms from Minecraft 1.21.5, described in [Numbers](#numbers)
- one trailing comma after the last entry of a compound or the last item of a list, for example `{a: 1,}` and `[1, 2,]`. Typed arrays do not accept a trailing comma.
- `true` and `false`
- strings in double or single quotes, with the escapes described in [Strings](#strings)
- unquoted strings and keys, made of `A-Z`, `a-z`, `0-9`, `_`, `-`, `.` and `+`
- the operations `bool(...)` and `uuid(...)`, described in [Operations](#operations)

Input that the parser read before 1.21.5 support was added gives the same result, with one exception: an unquoted value that is now a valid number, for example `0x1F`, `0b101`, `1_000` or `5ub`, is now a number and not a string. Minecraft writes every string value in quotes, so its output is not affected. Keys are never numbers, so `{0x1F: 1}` has the key `"0x1F"`.

The parser does not read the list index syntax `[0: a, 1: b]`.

### Numbers

The parser reads the number forms that Minecraft 1.21.5 added:

| Form | Example | Result |
| --- | --- | --- |
| hexadecimal | `0xCAFE` | `IntTag` 51966 |
| binary | `0b101` | `IntTag` 5 |
| `_` between digits | `1_000`, `1_2.3_4f` | `IntTag` 1000, `FloatTag` 12.34 |
| no whole part or no fraction | `.5`, `1.` | `DoubleTag` 0.5, `DoubleTag` 1.0 |
| unsigned suffix | `240ub` | `ByteTag` -16 |
| signed suffix | `5sb` | `ByteTag` 5 |

The signedness prefix `u` or `s` goes before the type suffix: `ub`, `us`, `ui`, `ul`, `sb`, `ss`, `si` and `sl`. Without it, decimal numbers are signed, and hexadecimal and binary numbers are unsigned. An unsigned number must fit the unsigned range of its type, for example 0 to 255 for a byte. The tag holds the signed value with the same bits, so `240ub`, `0xF0ub` and `-16b` give the same `ByteTag`.

`b` is a hexadecimal digit. `0x11b` is the int 283. Write a hexadecimal byte with a signedness prefix: `0x11ub` or `0x11sb`.

In a typed array, an element without a suffix has the type of the array. An element can have a smaller type than the array: `[I; 1b, 2s, 3]` is valid.

The parser also reads the older forms. A number can start with `0`, and `007b` is the byte 7. A literal that is not a valid number, for example `0x` or `1_`, is an unquoted string.

`toSnbt()` writes every number in signed decimal form, which all versions can read.

### Strings

A quoted string can contain these escapes:

| Escape | Result |
| --- | --- |
| `\\` | `\` |
| `\"` and `\'` | the quote, in either kind of string |
| `\b`, `\f`, `\n`, `\r`, `\s`, `\t` | backspace, form feed, line feed, carriage return, space, tab |
| `\x41` | the code point with 2 hex digits |
| `A` | the code point with 4 hex digits |
| `\U0001F600` | the code point with 8 hex digits |
| `\N{Snowman}` | the Unicode character with that name |

The parser writes each code point to the result as UTF-8. A `\u` high surrogate followed by a `\u` low surrogate gives one character, for example `😀` gives 😀. An unpaired surrogate causes `SNBTParseException`.

`\N{...}` needs the `intl` extension. Without it, `\N{...}` causes `SNBTParseException`.

`toSnbt()` writes only the escapes `\\` and `\"`. It writes control characters such as a line break unchanged, because Minecraft versions before 1.21.5 do not read `\n` but do read a raw line break. A string with a line break therefore spans more than one line of output, so it does not fit on one line of a `.mcfunction` file.

### Operations

An operation is a name directly followed by arguments in parentheses. The parser evaluates it and returns the result as a tag:

| Operation | Argument | Result |
| --- | --- | --- |
| `bool(x)` | a boolean or a number | `BooleanTag`: a boolean stays the same, and a number is `true` unless it is zero |
| `uuid(s)` | a string that holds a UUID | `IntArrayTag` with the four integers that Minecraft stores |

```php
use Stilling\SNBTParser\SNBTParser;

SNBTParser::parse('{Invulnerable: bool(1), UUID: uuid("069a79f4-44e9-4726-a5be-fca90e38aaf5")}');
// ["Invulnerable" => true, "UUID" => [110787060, 1156138790, -1514210135, 238594805]]
```

Any other name, a wrong number of arguments, or an argument of the wrong type causes `SNBTParseException`. `toSnbt()` writes the result, not the operation: `bool(1)` becomes `true`.

## Errors

Every exception from this package implements `Stilling\SNBTParser\Exceptions\SNBTException`. Catch that interface to catch all of them.

| Exception | Extends | Thrown when |
| --- | --- | --- |
| `SNBTParseException` | `RuntimeException` | the input is not valid SNBT, or `parseCompound()` finds a different root tag |
| `SNBTTagException` | `UnexpectedValueException` | a typed getter finds no entry or an entry of a different tag type, or `ListTag::with()` gets an index out of range |
| `SNBTInvalidArgumentException` | `InvalidArgumentException` | a UUID helper, `Tag::fromPhp()` or a tag constructor gets a value that it cannot use |

The parser throws `SNBTParseException` when the input is not valid SNBT. This includes these numbers:

- an integer outside the range of its type, for example `300b` or `2147483648`
- a decimal with an integer suffix, for example `1.5b`
- a floating-point number that overflows, for example `1e400`

The tag constructors also check the range. `new ByteTag(300)` and `new IntArrayTag([2147483648])` throw `SNBTInvalidArgumentException`. The integer tags give their range as the constants `MIN` and `MAX`, for example `ByteTag::MIN` and `ByteTag::MAX`. `FloatTag` and `DoubleTag` do not accept `INF` or `NAN`.

`SNBTParseException` gives the location of the error:

- `position` - the byte offset in the input, starting at 0
- `lineNumber` - the line, starting at 1
- `columnNumber` - the byte offset in that line, starting at 1

```php
use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\SNBTParser;

try {
    SNBTParser::parse("{\n    a: 1,\n    b: @\n}");
} catch (SNBTParseException $e) {
    $e->getMessage();  // 'Unexpected character at position 19 near "@\n}".'
    $e->position;      // 19
    $e->lineNumber;    // 3
    $e->columnNumber;  // 8
}
```
