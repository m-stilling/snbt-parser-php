<?php

use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\BooleanTag;
use Stilling\SNBTParser\Tag\ByteArrayTag;
use Stilling\SNBTParser\Tag\ByteTag;
use Stilling\SNBTParser\Tag\DoubleTag;
use Stilling\SNBTParser\Tag\FloatTag;
use Stilling\SNBTParser\Tag\IntArrayTag;
use Stilling\SNBTParser\Tag\IntTag;
use Stilling\SNBTParser\Tag\LongTag;
use Stilling\SNBTParser\Tag\ShortTag;
use Stilling\SNBTParser\Tag\StringTag;

/**
 * @param class-string $class
 */
function expectTag(string $snbt, string $class, mixed $value): void {
	$tag = SNBTParser::parseTyped($snbt);

	expect($tag)->toBeInstanceOf($class)
		->and($tag->toPhp())->toBe($value);
}

test("parses hexadecimal integers", function () {
	expectTag("0xbad", IntTag::class, 0xBAD);
	expectTag("0xCAFE", IntTag::class, 0xCAFE);
	expectTag("0XcaFe", IntTag::class, 0xCAFE);
	expectTag("0xAB_CD", IntTag::class, 0xABCD);
	expectTag("+0x10", IntTag::class, 16);
	// Hexadecimal defaults to unsigned, so the full 32-bit range fits an int.
	expectTag("0xFFFFFFFF", IntTag::class, -1);
	expectTag("0x80000000", IntTag::class, -2_147_483_648);
	// `b` is a hex digit, so a trailing b is part of the number.
	expectTag("0x11b", IntTag::class, 0x11B);
	expectTag("0x11ub", ByteTag::class, 17);
	expectTag("0xFFub", ByteTag::class, -1);
	expectTag("0x7Fsb", ByteTag::class, 127);
	expectTag("0xFFFFs", ShortTag::class, -1);
	expectTag("0xFFFFFFFFFFFFFFFFl", LongTag::class, -1);
	expectTag("0x8000000000000000L", LongTag::class, PHP_INT_MIN);
	expectTag("-0x1si", IntTag::class, -1);
	expectTag("-0x80000000si", IntTag::class, -2_147_483_648);
});

test("parses binary integers", function () {
	expectTag("0b101", IntTag::class, 5);
	expectTag("0B101", IntTag::class, 5);
	expectTag("0b10_01", IntTag::class, 9);
	expectTag("0b1s", ShortTag::class, 1);
	// Binary defaults to unsigned.
	expectTag("0b11111111b", ByteTag::class, -1);
	expectTag("0b11111111ub", ByteTag::class, -1);
	expectTag("-0b1sb", ByteTag::class, -1);
});

test("parses decimal integers with underscores and signedness", function () {
	expectTag("1_000", IntTag::class, 1000);
	expectTag("1__000l", LongTag::class, 1000);
	expectTag("5sb", ByteTag::class, 5);
	expectTag("5ss", ShortTag::class, 5);
	expectTag("5S", ShortTag::class, 5);
	expectTag("5SI", IntTag::class, 5);
	expectTag("240ub", ByteTag::class, -16);
	expectTag("255UB", ByteTag::class, -1);
	expectTag("65535us", ShortTag::class, -1);
	expectTag("4294967295ui", IntTag::class, -1);
	expectTag("18446744073709551615ul", LongTag::class, -1);
	expectTag("9223372036854775808ul", LongTag::class, PHP_INT_MIN);
	expectTag("-0ub", ByteTag::class, 0);
});

test("rejects integers outside the signed or unsigned range", function () {
	$cases = [
		"240sb", "256ub", "-1ub", "65536us", "4294967296ui", "18446744073709551616ul",
		"0x100ub", "0x80sb", "0x1_0000_0000", "0x1_0000_0000_0000_0000l", "-0x1", "0b1_0000_0000ub",
	];

	foreach ($cases as $snbt) {
		expect(fn () => SNBTParser::parse($snbt))->toThrow(SNBTParseException::class, "out of range");
	}

	expect(fn () => SNBTParser::parse("256ub"))->toThrow(SNBTParseException::class, 'Integer "256ub" is out of range (0 to 255)')
		->and(fn () => SNBTParser::parse("18446744073709551616ul"))->toThrow(SNBTParseException::class, "(0 to 18446744073709551615)");
});

test("parses the extended floating-point forms", function () {
	expectTag(".1", DoubleTag::class, 0.1);
	expectTag("1.", DoubleTag::class, 1.0);
	expectTag("-.5f", FloatTag::class, -0.5);
	expectTag("1.2e3", DoubleTag::class, 1200.0);
	expectTag("1.2E+3", DoubleTag::class, 1200.0);
	expectTag("12000e-1", DoubleTag::class, 1200.0);
	expectTag("1_2.3_4__5f", FloatTag::class, 12.345);
	expectTag("1_2e3_4", DoubleTag::class, 12e34);
	expectTag("1.e3d", DoubleTag::class, 1000.0);
});

test("keeps literals that are not numbers as unquoted strings", function () {
	foreach ([ "0x", "0xg", "0x_1", "1_", "_1", "1__", "0b2", "5u", "5su", "5ubb", "1.2.3", "0x1.5", "1e", "." ] as $snbt) {
		expectTag($snbt, StringTag::class, $snbt);
	}
});

test("keeps reading the older number forms", function () {
	expectTag("0b", ByteTag::class, 0);
	expectTag("1b", ByteTag::class, 1);
	expectTag("007b", ByteTag::class, 7);
	expectTag("-0b", ByteTag::class, 0);
	expectTag("5", IntTag::class, 5);
	expectTag("5l", LongTag::class, 5);
	expectTag("1.5", DoubleTag::class, 1.5);
	expectTag("1e3", DoubleTag::class, 1000.0);
	expectTag("0.5f", FloatTag::class, 0.5);

	expect(SNBTParser::parse("[B;0b,1b]"))->toBe([ 0, 1 ])
		->and(SNBTParser::parse('{0b1: 1b, 0x1F: 2b}'))->toBe([ "0b1" => 1, "0x1F" => 2 ]);
});

test("typed array elements take the array type and accept the new forms", function () {
	expect(SNBTParser::parseTyped("[B;1,2]"))->toBeInstanceOf(ByteArrayTag::class)
		->and(SNBTParser::parse("[B;1,2]"))->toBe([ 1, 2 ])
		->and(SNBTParser::parse("[I;1b,2s,3]"))->toBe([ 1, 2, 3 ])
		->and(SNBTParser::parse("[B;0xFFub, 255ub, 0b1]"))->toBe([ -1, -1, 1 ])
		->and(SNBTParser::parse("[I;0xFFFFFFFF, 1_000]"))->toBe([ -1, 1000 ])
		->and(SNBTParser::parse("[L;0xFFFFFFFFFFFFFFFF]"))->toBe([ -1 ])
		->and(fn () => SNBTParser::parse("[B;300s]"))->toThrow(SNBTParseException::class, 'Integer "300s" is out of range (-128 to 127)')
		->and(fn () => SNBTParser::parse("[I;0xFFFFFFFFl]"))->toThrow(SNBTParseException::class, "out of range")
		->and(fn () => SNBTParser::parse("[I;.5]"))->toThrow(SNBTParseException::class, "Invalid I array element");
});

test("writes unsigned values back as signed decimal", function () {
	expect(SNBTParser::parseTyped("{a: 240ub, b: 0xFFFFFFFF, c: [B; 0xFFub]}")->toSnbt())
		->toBe("{a:-16b,b:-1,c:[B;-1b]}");
});

test("accepts one trailing comma in compounds and lists", function () {
	expect(SNBTParser::parse("{a: 1, b: 2,}"))->toBe([ "a" => 1, "b" => 2 ])
		->and(SNBTParser::parse("{ a: 1 , }"))->toBe([ "a" => 1 ])
		->and(SNBTParser::parse("[1, 2,]"))->toBe([ 1, 2 ])
		->and(SNBTParser::parse("[ [1,], {a: [],}, ]"))->toBe([ [ 1 ], [ "a" => [] ] ]);
});

test("rejects a comma without an element before it", function () {
	foreach ([ "{,}", "[,]", "{a: 1,,}", "[1,,]", "[1,,2]", "[I;1,]", "[B;1b,]" ] as $snbt) {
		expect(fn () => SNBTParser::parse($snbt))->toThrow(SNBTParseException::class);
	}
});

test("evaluates bool()", function () {
	expectTag("bool(true)", BooleanTag::class, true);
	expectTag("bool(false)", BooleanTag::class, false);
	expectTag("bool(1)", BooleanTag::class, true);
	expectTag("bool(0b)", BooleanTag::class, false);
	expectTag("bool( -5l )", BooleanTag::class, true);
	expectTag("bool(0.0)", BooleanTag::class, false);
	expectTag("bool(0.5f)", BooleanTag::class, true);

	expect(SNBTParser::parseTyped("{a: bool(1)}")->toSnbt())->toBe("{a:true}");
});

test("evaluates uuid()", function () {
	$ints = [ 110787060, 1156138790, -1514210135, 238594805 ];

	expectTag('uuid("069a79f4-44e9-4726-a5be-fca90e38aaf5")', IntArrayTag::class, $ints);
	expectTag("uuid('069A79F444E94726A5BEFCA90E38AAF5')", IntArrayTag::class, $ints);

	expect(SNBTParser::parse('{UUID: uuid("069a79f4-44e9-4726-a5be-fca90e38aaf5"), n: 1}'))->toBe([ "UUID" => $ints, "n" => 1 ]);
});

test("rejects invalid operations", function () {
	expect(fn () => SNBTParser::parse('bool("x")'))->toThrow(SNBTParseException::class, "bool() needs a number or a boolean")
		->and(fn () => SNBTParser::parse("bool()"))->toThrow(SNBTParseException::class, "bool() takes exactly one argument")
		->and(fn () => SNBTParser::parse("bool(1, 2)"))->toThrow(SNBTParseException::class, "bool() takes exactly one argument")
		->and(fn () => SNBTParser::parse("uuid(1)"))->toThrow(SNBTParseException::class, "uuid() needs a string")
		->and(fn () => SNBTParser::parse('uuid("nope")'))->toThrow(SNBTParseException::class, 'Invalid UUID "nope"')
		->and(fn () => SNBTParser::parse("{a: foo(1)}"))->toThrow(SNBTParseException::class, 'Unknown operation "foo" at position 4')
		->and(fn () => SNBTParser::parse("bool(1"))->toThrow(SNBTParseException::class)
		->and(fn () => SNBTParser::parse("bool (1)"))->toThrow(SNBTParseException::class);
});
