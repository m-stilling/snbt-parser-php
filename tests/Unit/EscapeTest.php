<?php

use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\StringTag;

test("decodes the character escapes", function () {
	expect(SNBTParser::parse('"a\bb"'))->toBe("a\x08b")
		->and(SNBTParser::parse('"a\fb"'))->toBe("a\fb")
		->and(SNBTParser::parse('"a\sb"'))->toBe("a b")
		->and(SNBTParser::parse('"a\nb\rc\td"'))->toBe("a\nb\rc\td")
		->and(SNBTParser::parse('"a\\\\b"'))->toBe("a\\b");
});

test("escapes either quote in either kind of string", function () {
	expect(SNBTParser::parse('"it\'s \"x\""'))->toBe("it's \"x\"")
		->and(SNBTParser::parse("'it\\'s \\\"x\\\"'"))->toBe("it's \"x\"");
});

test("decodes code point escapes to UTF-8", function () {
	expect(SNBTParser::parse('"\x41\xe9"'))->toBe("Aé")
		->and(SNBTParser::parse('"\u0048\u00E9\u20AC"'))->toBe("Hé€")
		->and(SNBTParser::parse('"\U0001F600"'))->toBe("😀")
		->and(SNBTParser::parse('"\uD83D\uDE00"'))->toBe("😀")
		->and(SNBTParser::parse('{"\u0041": 1}'))->toBe([ "A" => 1 ]);
});

test("keeps an escaped backslash before u as literal text", function () {
	expect(SNBTParser::parse('"\\\\u0041"'))->toBe("\\u0041");
});

if (class_exists(IntlChar::class)) {
	test("decodes named escapes", function () {
		expect(SNBTParser::parse('"\N{Snowman}"'))->toBe("☃")
			->and(SNBTParser::parse('"\N{LATIN SMALL LETTER E WITH ACUTE}"'))->toBe("é")
			->and(fn () => SNBTParser::parse('"\N{NOT A REAL CHARACTER NAME}"'))->toThrow(SNBTParseException::class, "Unknown character name")
			->and(fn () => SNBTParser::parse('"\N{Snowman"'))->toThrow(SNBTParseException::class)
			->and(fn () => SNBTParser::parse('"\NSnowman"'))->toThrow(SNBTParseException::class);
	});
}

test("rejects malformed escapes", function () {
	foreach ([ '"\x4"', '"\x4g"', '"\u12"', '"\U0000004"', '"\U00110000"', '"\uD83D"', '"\uDE00"', '"\uD83Dx"', '"\q"', '"\\' ] as $snbt) {
		expect(fn () => SNBTParser::parse($snbt))->toThrow(SNBTParseException::class);
	}
});

test("reads the strings that a 1.21.4 server writes", function () {
	// `data get storage` output from a 1.21.4 server: control characters are
	// written raw, and only the backslash and the quote are escaped.
	expect(SNBTParser::parse("\"a\nb\""))->toBe("a\nb")
		->and(SNBTParser::parse("\"a\rb\""))->toBe("a\rb")
		->and(SNBTParser::parse("\"a\tb\""))->toBe("a\tb")
		->and(SNBTParser::parse("\"a\x01b\""))->toBe("a\x01b")
		->and(SNBTParser::parse("'a\\\\b\"c\\'d'"))->toBe("a\\b\"c'd");
});

test("1.21.4 rejects the escapes that 1.21.5 added, so toSnbt() does not write them", function () {
	// 1.21.4 answered `Invalid escape sequence` for \n, \t and \x01, and accepted
	// raw control characters.
	expect((new StringTag("a\nb\tc\rd\x01e"))->toSnbt())->toBe("\"a\nb\tc\rd\x01e\"");
});

test("reads the strings that a 1.21.5 server writes", function () {
	// `data get storage` output from a 1.21.5 server for values set with raw
	// control characters, and for a value holding a backslash and both quotes.
	// A 26.3 server wrote the same output.
	expect(SNBTParser::parse('"a\nb"'))->toBe("a\nb")
		->and(SNBTParser::parse('"a\rb"'))->toBe("a\rb")
		->and(SNBTParser::parse('"a\tb"'))->toBe("a\tb")
		->and(SNBTParser::parse('"a\x01b"'))->toBe("a\x01b")
		->and(SNBTParser::parse("'a\\\\b\"c\\'d'"))->toBe("a\\b\"c'd");
});
