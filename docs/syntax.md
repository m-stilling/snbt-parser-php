# Supported syntax

The parser reads the SNBT that Minecraft writes, for example the output of `data get`. It also reads the SNBT syntax that Java Edition 1.21.5 added for commands and data packs.

- [Overview](#overview)
- [Numbers](#numbers)
- [Strings](#strings)
- [Operations](#operations)

## Overview

| Syntax | Example |
| --- | --- |
| compounds | `{a: 1, b: "x"}` |
| lists | `[1, 2, 3]` |
| lists with items of different types | `[1, "a", {b: 2}]` |
| typed arrays | `[B; 1b, 2b]`, `[I; 1, 2]`, `[L; 1l, 2l]` |
| numbers | `1b`, `2s`, `3`, `4l`, `1.5f`, `2.5d`. Refer to [Numbers](#numbers). |
| booleans | `true`, `false` |
| quoted strings | `"a"`, `'a'`. Refer to [Strings](#strings). |
| unquoted strings and keys | `{id: stone, -x: 1.5}`. Refer to the details below. |
| one trailing comma in a compound or list | `{a: 1,}`, `[1, 2,]` |
| operations | `bool(1)`, `uuid("...")`. Refer to [Operations](#operations). |

Details:

- An unquoted string or key contains only `A-Z`, `a-z`, `0-9`, `_`, `-`, `.` and `+`.
- An unquoted value that is a valid number is a number, for example `0x1F`, `0b101`, `1_000` and `5ub`. A key is never a number, so `{0x1F: 1}` has the key `"0x1F"`.
- Typed arrays do not accept a trailing comma.
- The parser does not read the list index syntax `[0: a, 1: b]`.
- The parser does not check what a Minecraft version accepts. Refer to [Minecraft versions](writing-snbt.md#minecraft-versions).

## Numbers

A number has one of the type suffixes `b`, `s`, `i`, `l`, `f` and `d`, in uppercase or lowercase. A number without a suffix is an int (`1`) or a double (`1.0`, `1e3`).

The parser also reads the number forms that Minecraft 1.21.5 added:

| Form | Example | Result |
| --- | --- | --- |
| hexadecimal | `0xCAFE` | `IntTag` 51966 |
| binary | `0b101` | `IntTag` 5 |
| `_` between digits | `1_000`, `1_2.3_4f` | `IntTag` 1000, `FloatTag` 12.34 |
| no whole part or no fraction | `.5`, `1.` | `DoubleTag` 0.5, `DoubleTag` 1.0 |
| unsigned suffix | `240ub` | `ByteTag` -16 |
| signed suffix | `5sb` | `ByteTag` 5 |

Details:

- The signedness prefix `u` or `s` goes before the type suffix: `ub`, `us`, `ui`, `ul`, `sb`, `ss`, `si` and `sl`.
- Without a prefix, decimal numbers are signed, and hexadecimal and binary numbers are unsigned.
- An unsigned number must fit the unsigned range of its type, for example 0 to 255 for a byte. The tag holds the signed value with the same bits, so `240ub`, `0xF0ub` and `-16b` give the same `ByteTag`.
- `b` is a hexadecimal digit, so `0x11b` is the int 283. Write a hexadecimal byte with a prefix: `0x11ub` or `0x11sb`.
- In a typed array, an element without a suffix has the type of the array. An element can have a smaller type than the array: `[I; 1b, 2s, 3]` is valid.
- A number can start with `0`. `007b` is the byte 7.
- A literal that is not a valid number, for example `0x` or `1_`, is an unquoted string.
- A number outside the range of its type causes `SNBTParseException`. Refer to [Errors](errors.md#invalid-numbers).

## Strings

A string is in double quotes or single quotes. A quoted string can contain these escapes:

| Escape | Result |
| --- | --- |
| `\\` | `\` |
| `\"` and `\'` | the quote, in both kinds of string |
| `\b`, `\f`, `\n`, `\r`, `\s`, `\t` | backspace, form feed, line feed, carriage return, space, tab |
| `\x41` | the code point with 2 hex digits |
| `A` | the code point with 4 hex digits |
| `\U0001F600` | the code point with 8 hex digits |
| `\N{Snowman}` | the Unicode character with that name |

Details:

- The parser writes each code point to the result as UTF-8.
- A `\u` high surrogate followed by a `\u` low surrogate gives one character, for example `😀` gives 😀. An unpaired surrogate causes `SNBTParseException`.
- `\N{...}` needs the `intl` extension. Without it, `\N{...}` causes `SNBTParseException`.

## Operations

An operation is a name directly followed by arguments in parentheses. The parser evaluates it and gives the result as a tag:

| Operation | Argument | Result |
| --- | --- | --- |
| `bool(x)` | a boolean or a number | `BooleanTag`. A boolean stays the same. A number is `true` unless it is zero. |
| `uuid(s)` | a string that holds a UUID | `IntArrayTag` with the four integers that Minecraft stores |

```php
use Stilling\SNBTParser\SNBTParser;

SNBTParser::parse('{Invulnerable: bool(1), UUID: uuid("069a79f4-44e9-4726-a5be-fca90e38aaf5")}');
// ["Invulnerable" => true, "UUID" => [110787060, 1156138790, -1514210135, 238594805]]
```

Details:

- Any other name, a wrong number of arguments, or an argument of the wrong type causes `SNBTParseException`.
- `toSnbt()` writes the result, not the operation. `bool(1)` becomes `true`.
