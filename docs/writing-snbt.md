# Writing SNBT

`toSnbt()` writes a tag as SNBT. The output keeps the NBT type of each tag.

```php
public function toSnbt(SNBTFormat $format = SNBTFormat::Compact, bool $escapeControlCharacters = false): string
```

- [Formats](#formats)
- [Keys](#keys)
- [Numbers](#numbers)
- [Strings](#strings)
- [Minecraft versions](#minecraft-versions)

## Formats

`SNBTFormat` controls the layout:

```php
use Stilling\SNBTParser\SNBTFormat;
use Stilling\SNBTParser\SNBTParser;

$tag = SNBTParser::parseTyped('{name: "Steve", pos: [1.0d, 2.0d], nested: {a: 1b}}');

$tag->toSnbt();                    // {name:"Steve",pos:[1.0d,2.0d],nested:{a:1b}}
$tag->toSnbt(SNBTFormat::Spaced);  // { name: "Steve", pos: [ 1.0d, 2.0d ], nested: { a: 1b } }
$tag->toSnbt(SNBTFormat::Pretty);
```

`SNBTFormat::Pretty` writes compounds and lists on more than one line, with four spaces for each level. It writes typed arrays on one line:

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

The parser reads the output of all three formats back to the same tree.

## Keys

`toSnbt()` writes a key without quotes when the key contains only `A-Z`, `a-z`, `0-9`, `_`, `-`, `.` and `+`. Examples: `{0:1b}`, `{-x:1b}` and `{true:1b}`. It puts all other keys in double quotes.

## Numbers

`toSnbt()` writes every number in signed decimal form with its type suffix, for example `-16b` and `1.5f`. It does not write hexadecimal, binary, `_` separators or the `u` and `s` prefixes.

## Strings

By default, `toSnbt()` puts strings in double quotes and escapes only `\` and `"`. It writes all other characters unchanged. This includes line breaks and other control characters:

```php
use Stilling\SNBTParser\Tag\StringTag;

(new StringTag("a\nb"))->toSnbt();

// returns ->
"a
b"
```

A `.mcfunction` file needs each command on one line. For output on one line, set `escapeControlCharacters`:

```php
(new StringTag("a\nb"))->toSnbt(escapeControlCharacters: true);
// "a\nb"

$tag->toSnbt(SNBTFormat::Spaced, escapeControlCharacters: true);
```

With `escapeControlCharacters`, `toSnbt()` writes these escapes in strings and in quoted keys:

| Character | Output |
| --- | --- |
| line feed | `\n` |
| carriage return | `\r` |
| tab | `\t` |
| other control characters, U+0000 to U+001F and U+007F | `\x` with 2 hex digits, for example `\x01` |

`SNBTFormat::Pretty` still writes compounds and lists on more than one line. Use `Compact` or `Spaced` for output on one line.

## Minecraft versions

`toSnbt()` does not check what a Minecraft version accepts. It writes the tree as it is, and the server reports input that it does not accept.

| Output | Minecraft 1.21.4 and earlier | Minecraft 1.21.5 and later |
| --- | --- | --- |
| strings with only `\` and `"` escaped (the default) | reads them | reads them |
| keys without quotes, such as `{0:1b}` | reads it | reads it |
| `escapeControlCharacters` escapes | rejects them | reads them |
| a list with items of different types | rejects it | reads it |
| an empty key, such as `{"":1b}` | rejects it | rejects it |
