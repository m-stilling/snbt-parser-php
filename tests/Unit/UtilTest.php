<?php

use Stilling\SNBTParser\Exceptions\SNBTException;
use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\SNBTFormat;
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\IntArrayTag;

test("ints to uuid", function () {
	$ints = [
		110787060,
		1156138790,
		-1514210135,
		238594805,
	];

	expect(SNBTParser::intsToUuid($ints))->toEqual("069a79f4-44e9-4726-a5be-fca90e38aaf5")
		->and(fn () => SNBTParser::intsToUuid([]))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => SNBTParser::intsToUuid([ 1, 2, 3 ]))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => SNBTParser::intsToUuid([ 1, 2, 3, "4" ]))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => SNBTParser::intsToUuid([ ...$ints, 1 ]))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => SNBTParser::intsToUuid([ 1, 2, 3, 2_147_483_648 ]))->toThrow(SNBTInvalidArgumentException::class);
});

test("uuid to ints", function () {
	$ints = [
		110787060,
		1156138790,
		-1514210135,
		238594805,
	];

	expect(SNBTParser::uuidToInts("069a79f4-44e9-4726-a5be-fca90e38aaf5"))->toEqual($ints)
		->and(SNBTParser::uuidToInts("069A79F4-44E9-4726-A5BE-FCA90E38AAF5"))->toEqual($ints)
		->and(SNBTParser::uuidToInts("069a79f444e94726a5befca90e38aaf5"))->toEqual($ints)
		->and(SNBTParser::uuidToInts("00000000-0000-0000-0000-000000000000"))->toEqual([ 0, 0, 0, 0 ])
		->and(SNBTParser::uuidToInts("ffffffff-ffffffff-ffff-ffff-ffffffff"))->toEqual([ -1, -1, -1, -1 ])
		->and(SNBTParser::uuidToInts("80000000-0000-0000-7fff-ffff00000000"))->toEqual([ -2147483648, 0, 2147483647, 0 ])
		->and(fn () => SNBTParser::uuidToInts(""))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => SNBTParser::uuidToInts("not-a-uuid"))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => SNBTParser::uuidToInts("069a79f4-44e9-4726-a5be-fca90e38aaf"))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => SNBTParser::uuidToInts("069a79f4-44e9-4726-a5be-fca90e38aafg"))->toThrow(SNBTInvalidArgumentException::class);
});

test("uuid round trip", function () {
	$uuid = "069a79f4-44e9-4726-a5be-fca90e38aaf5";
	$ints = [ 110787060, 1156138790, -1514210135, 238594805 ];

	expect(SNBTParser::intsToUuid(SNBTParser::uuidToInts($uuid)))->toEqual($uuid)
		->and(SNBTParser::uuidToInts(SNBTParser::intsToUuid($ints)))->toEqual($ints);
});

test("uuid to snbt", function () {
	$uuid = "069a79f4-44e9-4726-a5be-fca90e38aaf5";

	expect(SNBTParser::uuidToSnbt($uuid))->toEqual("[I;110787060,1156138790,-1514210135,238594805]")
		->and(SNBTParser::uuidToSnbt($uuid, SNBTFormat::Spaced))->toEqual("[I; 110787060, 1156138790, -1514210135, 238594805]")
		->and(SNBTParser::uuidToSnbt($uuid, SNBTFormat::Pretty))->toEqual("[I; 110787060, 1156138790, -1514210135, 238594805]")
		->and(SNBTParser::parse(SNBTParser::uuidToSnbt($uuid)))->toEqual(SNBTParser::uuidToInts($uuid))
		->and(fn () => SNBTParser::uuidToSnbt("not-a-uuid"))->toThrow(SNBTInvalidArgumentException::class);
});

test("int array tag to uuid", function () {
	$tag = new IntArrayTag([ 110787060, 1156138790, -1514210135, 238594805 ]);

	expect($tag->toUuid())->toEqual("069a79f4-44e9-4726-a5be-fca90e38aaf5")
		->and(fn () => (new IntArrayTag([]))->toUuid())->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => (new IntArrayTag([ 1, 2, 3 ]))->toUuid())->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => (new IntArrayTag([ 1, 2, 3, 4, 5 ]))->toUuid())->toThrow(SNBTInvalidArgumentException::class);
});

test("invalid arguments are package exceptions", function () {
	expect(new SNBTInvalidArgumentException())->toBeInstanceOf(SNBTException::class)
		->and(new SNBTInvalidArgumentException())->toBeInstanceOf(InvalidArgumentException::class);
});
