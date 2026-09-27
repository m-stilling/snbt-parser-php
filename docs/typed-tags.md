# Typed tags

`parseTyped()` and `parseCompound()` return a tree of tags. A tag keeps its NBT type, so a `byte` stays a `ByteTag` and does not become a plain `int`.

- [Parsing](#parsing)
- [Tag classes](#tag-classes)
- [Reading typed values](#reading-typed-values)
- [Building tags](#building-tags)
- [Changing tags](#changing-tags)
- [UUIDs](#uuids)

## Parsing

| Method | Returns |
| --- | --- |
| `SNBTParser::parse()` | native PHP values |
| `SNBTParser::parseTyped()` | `Tag` |
| `SNBTParser::parseCompound()` | `CompoundTag`. It throws `SNBTParseException` when the root is a different tag. Use it for the output of `data get`. |

```php
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\ByteTag;

$tag = SNBTParser::parseCompound('{Slot: 3b, id: "minecraft:shield"}');

$tag->get("Slot") instanceof ByteTag; // true
$tag->get("id")->toPhp();             // "minecraft:shield"
$tag->toPhp();                        // ["Slot" => 3, "id" => "minecraft:shield"]
$tag->toSnbt();                       // '{Slot:3b,id:"minecraft:shield"}'
```

`parse()` gives an empty PHP array for both an empty compound `{}` and an empty list `[]`. `parseTyped()` gives a `CompoundTag` and a `ListTag`.

## Tag classes

All classes are in the `Stilling\SNBTParser\Tag` namespace and extend `Tag`:

| Kind | Classes |
| --- | --- |
| integers | `ByteTag`, `ShortTag`, `IntTag`, `LongTag` |
| floating-point numbers | `FloatTag`, `DoubleTag` |
| other values | `BooleanTag`, `StringTag` |
| typed arrays | `ByteArrayTag`, `IntArrayTag`, `LongArrayTag` |
| containers | `ListTag`, `CompoundTag` |

Every tag has these methods:

- `toPhp()` - the native PHP value. This is the same value that `parse()` returns.
- `toSnbt()` - the tag as SNBT, with its NBT types. Refer to [Writing SNBT](writing-snbt.md).

`CompoundTag`, `ListTag` and the typed arrays are `Countable` and iterable:

| Class | Contents | Read one entry |
| --- | --- | --- |
| `CompoundTag` | `entries` | `get(string $key): ?Tag`, `has(string $key): bool` |
| `ListTag` | `items` | `get(int $index): ?Tag` |
| `ByteArrayTag`, `IntArrayTag`, `LongArrayTag` | `values` | - |

```php
count($tag);

foreach ($tag as $key => $value) {
    // ...
}
```

A `CompoundTag` iteration gives string keys. `entries` and `toPhp()` can have `int` keys, because PHP stores a numeric-string key such as `"0"` as an `int`.

## Reading typed values

`CompoundTag` and `ListTag` have typed getters. A getter takes a key on a compound and an index on a list. It throws `SNBTTagException` when the entry is missing or has a different tag type.

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

$player = SNBTParser::parseCompound('{Health: 20.0f, OnGround: 1b, Pos: [-78.5d, 65.0d, -19.5d], UUID: [I; 110787060, 1156138790, -1514210135, 238594805]}');

$player->getFloat("Health");                          // 20.0
$player->getBool("OnGround");                         // true
$player->getList("Pos")->getFloat(1);                 // 65.0
$player->getAs("UUID", IntArrayTag::class)->toUuid(); // "069a79f4-44e9-4726-a5be-fca90e38aaf5"

$player->getInt("Health"); // throws SNBTTagException: Expected IntegerTag at key "Health", found FloatTag.
```

The getters have PHPStan generics. PHPStan knows the return type of each call.

## Building tags

`Tag::fromPhp()` makes a tag tree from native PHP values:

| PHP value | Tag |
| --- | --- |
| `bool` | `BooleanTag` |
| `int` | `IntTag`, or `LongTag` outside the 32-bit range |
| `float` | `DoubleTag` |
| `string` | `StringTag` |
| list array | `ListTag` |
| other array | `CompoundTag` |
| `Tag` | the same tag |

To get a different NBT type, put a tag in the array:

```php
use Stilling\SNBTParser\Tag\ByteTag;
use Stilling\SNBTParser\Tag\Tag;

Tag::fromPhp(["Slot" => new ByteTag(0), "id" => "minecraft:lead", "count" => 1])->toSnbt();
// '{Slot:0b,id:"minecraft:lead",count:1}'
```

Details:

- An empty array becomes an empty `ListTag`. Use `new CompoundTag([])` for an empty compound.
- Other values, such as `null` and objects, cause `SNBTInvalidArgumentException`.
- The tag constructors check the range of the value. Refer to [Errors](errors.md#invalid-values).

## Changing tags

Tags are immutable. These methods return a changed copy:

| Method | Result |
| --- | --- |
| `CompoundTag::with(string $key, Tag $tag)` | Sets a key. An existing key keeps its position. A new key goes last. |
| `CompoundTag::without(string $key)` | Removes a key. A missing key is not an error. |
| `ListTag::with(int $index, Tag $tag)` | Replaces an item. It throws `SNBTTagException` when the index is out of range. |
| `ListTag::withAppended(Tag $tag)` | Adds an item at the end. |
| `ListTag::without(int $index)` | Removes an item. The later items move down by one. |

```php
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\ByteTag;

$item = SNBTParser::parseCompound('{Slot: 3b, id: "minecraft:shield", count: 1}');

$item->with("Slot", new ByteTag(4))->without("count")->toSnbt();
// '{Slot:4b,id:"minecraft:shield"}'
```

## UUIDs

Minecraft stores a UUID as an array of four integers, for example `UUID: [I; 110787060, 1156138790, -1514210135, 238594805]`. These methods convert between that array and the UUID string:

```php
use Stilling\SNBTParser\SNBTFormat;
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\IntArrayTag;

SNBTParser::intsToUuid([110787060, 1156138790, -1514210135, 238594805]);
// "069a79f4-44e9-4726-a5be-fca90e38aaf5"

$tag->getAs("UUID", IntArrayTag::class)->toUuid();
// "069a79f4-44e9-4726-a5be-fca90e38aaf5"

SNBTParser::uuidToInts("069a79f4-44e9-4726-a5be-fca90e38aaf5");
// [110787060, 1156138790, -1514210135, 238594805]

SNBTParser::uuidToSnbt("069a79f4-44e9-4726-a5be-fca90e38aaf5");
// "[I;110787060,1156138790,-1514210135,238594805]"

SNBTParser::uuidToSnbt("069a79f4-44e9-4726-a5be-fca90e38aaf5", SNBTFormat::Spaced);
// "[I; 110787060, 1156138790, -1514210135, 238594805]"
```

Details:

- `uuidToInts()` and `uuidToSnbt()` accept the hyphenated form or 32 hex digits, in uppercase or lowercase.
- These methods throw `SNBTInvalidArgumentException` when the input is not correct: `intsToUuid()` and `toUuid()` for an array that does not hold four integers, and `uuidToInts()` and `uuidToSnbt()` for a string that is not a UUID.
- The SNBT operation `uuid("...")` also gives an `IntArrayTag`. Refer to [Operations](syntax.md#operations).
