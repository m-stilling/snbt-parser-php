<?php

use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\BooleanTag;
use Stilling\SNBTParser\Tag\ByteArrayTag;
use Stilling\SNBTParser\Tag\ByteTag;
use Stilling\SNBTParser\Tag\CompoundTag;
use Stilling\SNBTParser\Tag\DoubleTag;
use Stilling\SNBTParser\Tag\FloatTag;
use Stilling\SNBTParser\Tag\IntArrayTag;
use Stilling\SNBTParser\Tag\IntTag;
use Stilling\SNBTParser\Tag\ListTag;
use Stilling\SNBTParser\Tag\LongArrayTag;
use Stilling\SNBTParser\Tag\LongTag;
use Stilling\SNBTParser\SNBTFormat;
use Stilling\SNBTParser\Tag\ShortTag;
use Stilling\SNBTParser\Tag\StringTag;

test("preserves the distinct numeric types", function () {
	expect(SNBTParser::parseTyped("5b"))->toBeInstanceOf(ByteTag::class)
		->and(SNBTParser::parseTyped("5s"))->toBeInstanceOf(ShortTag::class)
		->and(SNBTParser::parseTyped("5i"))->toBeInstanceOf(IntTag::class)
		->and(SNBTParser::parseTyped("5"))->toBeInstanceOf(IntTag::class)
		->and(SNBTParser::parseTyped("5l"))->toBeInstanceOf(LongTag::class)
		->and(SNBTParser::parseTyped("5f"))->toBeInstanceOf(FloatTag::class)
		->and(SNBTParser::parseTyped("5d"))->toBeInstanceOf(DoubleTag::class)
		->and(SNBTParser::parseTyped("5.0"))->toBeInstanceOf(DoubleTag::class);
});

test("exposes the underlying value via the tag property", function () {
	$byte = SNBTParser::parseTyped("5b");
	expect($byte)->toBeInstanceOf(ByteTag::class);

	if ($byte instanceof ByteTag) {
		expect($byte->value)->toBe(5);
	}

	$array = SNBTParser::parseTyped("[I;1,2,3]");
	expect($array)->toBeInstanceOf(IntArrayTag::class);

	if ($array instanceof IntArrayTag) {
		expect($array->values)->toBe([ 1, 2, 3 ]);
	}
});

test("collapses to native values via toPhp", function () {
	expect(SNBTParser::parseTyped("5b")->toPhp())->toBe(5)
		->and(SNBTParser::parseTyped("5l")->toPhp())->toBe(5)
		->and(SNBTParser::parseTyped("0.5f")->toPhp())->toBe(0.5)
		->and(SNBTParser::parseTyped("true")->toPhp())->toBeTrue()
		->and(SNBTParser::parseTyped('"hi"')->toPhp())->toBe("hi");
});

test("parses booleans and strings", function () {
	expect(SNBTParser::parseTyped("true"))->toBeInstanceOf(BooleanTag::class)
		->and(SNBTParser::parseTyped("false"))->toBeInstanceOf(BooleanTag::class)
		->and(SNBTParser::parseTyped('"quoted"'))->toBeInstanceOf(StringTag::class)
		// An unquoted, non-numeric, non-boolean literal is a string.
		->and(SNBTParser::parseTyped("stone"))->toBeInstanceOf(StringTag::class)
		->and(SNBTParser::parseTyped("stone")->toPhp())->toBe("stone");
});

test("parses unquoted string values", function () {
	expect(SNBTParser::parse("{ id: stone }"))->toBe([ "id" => "stone" ]);
});

test("does not treat 0b/1b as booleans", function () {
	// Only the true/false keywords are booleans; bytes stay bytes (strict int).
	expect(SNBTParser::parseTyped("1b"))->toBeInstanceOf(ByteTag::class)
		->and(SNBTParser::parseTyped("0b"))->toBeInstanceOf(ByteTag::class)
		->and(SNBTParser::parse("1b"))->toBe(1)
		->and(SNBTParser::parse("0b"))->toBe(0);
});

test("preserves the distinct array types", function () {
	expect(SNBTParser::parseTyped("[B;1b,2b]"))->toBeInstanceOf(ByteArrayTag::class)
		->and(SNBTParser::parseTyped("[B;1b,2b]")->toPhp())->toBe([ 1, 2 ])
		->and(SNBTParser::parseTyped("[I;1,2,3]"))->toBeInstanceOf(IntArrayTag::class)
		->and(SNBTParser::parseTyped("[L;1l,2l]"))->toBeInstanceOf(LongArrayTag::class)
		// A bracket without a type prefix is a list, not a typed array.
		->and(SNBTParser::parseTyped("[1,2]"))->toBeInstanceOf(ListTag::class);
});

test("navigates compounds and lists", function () {
	$tag = SNBTParser::parseTyped('{ a: 1b, b: [ "x", "y" ] }');
	expect($tag)->toBeInstanceOf(CompoundTag::class);

	if (!$tag instanceof CompoundTag) {
		return;
	}

	expect($tag->has("a"))->toBeTrue()
		->and($tag->has("missing"))->toBeFalse()
		->and($tag->get("a"))->toBeInstanceOf(ByteTag::class)
		->and($tag->get("missing"))->toBeNull();

	$list = $tag->get("b");
	expect($list)->toBeInstanceOf(ListTag::class);

	if ($list instanceof ListTag) {
		expect($list->items)->toHaveCount(2);
	}
});

test("decodes escape sequences", function () {
	expect(SNBTParser::parse('"line1\nline2"'))->toBe("line1\nline2")
		->and(SNBTParser::parse('"tab\tend"'))->toBe("tab\tend")
		->and(SNBTParser::parse('"quote\"here"'))->toBe('quote"here');
});

test("rejects invalid escape sequences", function () {
	expect(fn () => SNBTParser::parse('"bad\xescape"'))
		->toThrow(\Stilling\SNBTParser\Exceptions\SNBTParseException::class);
});

test("serializes tags back to snbt", function () {
	expect(SNBTParser::parseTyped("5b")->toSnbt())->toBe("5b")
		->and(SNBTParser::parseTyped("5")->toSnbt())->toBe("5")
		->and(SNBTParser::parseTyped("5l")->toSnbt())->toBe("5l")
		->and(SNBTParser::parseTyped("true")->toSnbt())->toBe("true")
		->and(SNBTParser::parseTyped('"a\"b"')->toSnbt())->toBe('"a\"b"')
		// Array element suffixes are lowercase, matching Minecraft.
		->and(SNBTParser::parseTyped("[B;1b,2b]")->toSnbt())->toBe("[B;1b,2b]")
		->and(SNBTParser::parseTyped("[L;1l,2l]")->toSnbt())->toBe("[L;1l,2l]")
		->and(SNBTParser::parseTyped("[I;1,2]")->toSnbt())->toBe("[I;1,2]");
});

test("keeps the decimal point on whole floats and doubles", function () {
	expect(SNBTParser::parseTyped("20.0f")->toSnbt())->toBe("20.0f")
		->and(SNBTParser::parseTyped("1f")->toSnbt())->toBe("1.0f")
		->and(SNBTParser::parseTyped("65.0d")->toSnbt())->toBe("65.0d")
		->and(SNBTParser::parseTyped("-19.5d")->toSnbt())->toBe("-19.5d");
});

test("formats output as compact, spaced or pretty", function () {
	$tag = SNBTParser::parseTyped('{a:1b,ids:[I;1,2],nested:{c:true}}');

	expect($tag->toSnbt())->toBe('{a:1b,ids:[I;1,2],nested:{c:true}}')
		->and($tag->toSnbt(SNBTFormat::Compact))->toBe('{a:1b,ids:[I;1,2],nested:{c:true}}')
		->and($tag->toSnbt(SNBTFormat::Spaced))->toBe('{ a: 1b, ids: [I; 1, 2], nested: { c: true } }')
		->and($tag->toSnbt(SNBTFormat::Pretty))->toBe(
			"{\n    a: 1b,\n    ids: [I; 1, 2],\n    nested: {\n        c: true\n    }\n}"
		);
});

test("spaced format pads compounds and lists but not typed arrays", function () {
	$tag = SNBTParser::parseTyped('{a:[1,2],b:[I;1,2],c:{}}');

	expect($tag->toSnbt(SNBTFormat::Spaced))->toBe('{ a: [ 1, 2 ], b: [I; 1, 2], c: {} }');
});

test("empty containers stay inline in every format", function () {
	foreach ([SNBTFormat::Compact, SNBTFormat::Spaced, SNBTFormat::Pretty] as $format) {
		expect(SNBTParser::parseTyped("{}")->toSnbt($format))->toBe("{}")
			->and(SNBTParser::parseTyped("[]")->toSnbt($format))->toBe("[]")
			->and(SNBTParser::parseTyped("[I;]")->toSnbt($format))->toBe("[I;]");
	}
});

test("every format re-parses to the same tree", function () {
	$tag = SNBTParser::parseTyped('{a:1b,b:[1,2],c:{d:"x y"},e:[I;1,2,3]}');

	foreach ([SNBTFormat::Compact, SNBTFormat::Spaced, SNBTFormat::Pretty] as $format) {
		expect(SNBTParser::parseTyped($tag->toSnbt($format))->toPhp())->toEqual($tag->toPhp());
	}
});

test("escapes control characters when serializing", function () {
	$bs = chr(92);

	// A real newline/tab in the value must be written back escaped, not raw.
	expect((new StringTag("a\nb"))->toSnbt())->toBe('"a' . $bs . 'nb"')
		->and((new StringTag("x\ty"))->toSnbt())->toBe('"x' . $bs . 'ty"')
		// ...and the escaped form round-trips to the original bytes.
		->and(SNBTParser::parseTyped((new StringTag("a\nb"))->toSnbt())->toPhp())->toBe("a\nb");
});

test("round-trips double precision through serialization", function () {
	expect(SNBTParser::parseTyped("0.10000000149011612d")->toSnbt())->toBe("0.10000000149011612d");
});

test("serialization is stable across a re-parse", function () {
	$cases = [
		"5b",
		"0.5f",
		"false",
		'"hello world"',
		"[B;1b,2b]",
		"[I;1,2,3]",
		"[L;1l]",
		'{ a: 1b, b: "x y", c: [ 1, 2 ], d: { nested: true } }',
	];

	foreach ($cases as $snbt) {
		$once = SNBTParser::parseTyped($snbt)->toSnbt();
		$twice = SNBTParser::parseTyped($once)->toSnbt();

		expect($twice)->toBe($once);
	}
});
