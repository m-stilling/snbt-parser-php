# Errors

Every exception from this package implements `Stilling\SNBTParser\Exceptions\SNBTException`. Catch that interface to catch all of them.

| Exception | Extends | Thrown when |
| --- | --- | --- |
| `SNBTParseException` | `RuntimeException` | the input is not valid SNBT, or `parseCompound()` finds a different root tag |
| `SNBTTagException` | `UnexpectedValueException` | a typed getter finds no entry or an entry of a different tag type, or `ListTag::with()` gets an index out of range |
| `SNBTInvalidArgumentException` | `InvalidArgumentException` | a UUID method, `Tag::fromPhp()` or a tag constructor gets a value that it cannot use |

## Error position

`SNBTParseException` gives the location of the error:

| Property | Value |
| --- | --- |
| `position` | the byte offset in the input, starting at 0 |
| `lineNumber` | the line, starting at 1 |
| `columnNumber` | the byte offset in that line, starting at 1 |

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

## Invalid numbers

The parser throws `SNBTParseException` for these numbers:

- an integer outside the range of its type, for example `300b` or `2147483648`
- a decimal with an integer suffix, for example `1.5b`
- a floating-point number that overflows, for example `1e400`

## Invalid values

The tag constructors throw `SNBTInvalidArgumentException` for a value that the tag cannot hold:

- An integer outside the range of the tag, for example `new ByteTag(300)` or `new IntArrayTag([2147483648])`. The integer tags give their range as the constants `MIN` and `MAX`, for example `ByteTag::MIN` and `ByteTag::MAX`.
- `INF` or `NAN` in `FloatTag` or `DoubleTag`.
