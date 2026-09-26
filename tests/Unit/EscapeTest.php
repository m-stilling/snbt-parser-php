<?php

use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\SNBTParser;

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
		->and(SNBTParser::parse('"Hé€"'))->toBe("Hé€")
		->and(SNBTParser::parse('"\U0001F600"'))->toBe("😀")
		->and(SNBTParser::parse('"😀"'))->toBe("😀")
		->and(SNBTParser::parse('{"A": 1}'))->toBe([ "A" => 1 ]);
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
