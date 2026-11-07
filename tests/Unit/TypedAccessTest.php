<?php

use Stilling\SNBTParser\Exceptions\SNBTException;
use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\Exceptions\SNBTParseException;
use Stilling\SNBTParser\Exceptions\SNBTTagException;
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\ByteTag;
use Stilling\SNBTParser\Tag\CompoundTag;
use Stilling\SNBTParser\Tag\IntArrayTag;
use Stilling\SNBTParser\Tag\ListTag;

test("parses a compound root", function () {
	expect(SNBTParser::parseCompound("{ a: 1b }"))->toBeInstanceOf(CompoundTag::class)
		->and(fn () => SNBTParser::parseCompound("[1, 2]"))->toThrow(SNBTParseException::class, "Expected a compound at the root, found ListTag at position 0")
		->and(fn () => SNBTParser::parseCompound("5b"))->toThrow(SNBTParseException::class);
});

test("reads typed values from a compound", function () {
	$tag = SNBTParser::parseCompound('{
		name: "Steve",
		slot: 3b,
		air: 300s,
		xp: 42,
		score: 9999999999l,
		health: 20.0f,
		x: -78.5d,
		onGround: 1b,
		flying: 0b,
		sneaking: true,
		pos: [1.0d, 2.0d],
		nested: { a: 1 },
		UUID: [I; 110787060, 1156138790, -1514210135, 238594805]
	}');

	expect($tag->getString("name"))->toBe("Steve")
		->and($tag->getInt("slot"))->toBe(3)
		->and($tag->getInt("air"))->toBe(300)
		->and($tag->getInt("xp"))->toBe(42)
		->and($tag->getInt("score"))->toBe(9999999999)
		->and($tag->getFloat("health"))->toBe(20.0)
		->and($tag->getFloat("x"))->toBe(-78.5)
		->and($tag->getBool("onGround"))->toBeTrue()
		->and($tag->getBool("flying"))->toBeFalse()
		->and($tag->getBool("sneaking"))->toBeTrue()
		->and($tag->getList("pos"))->toBeInstanceOf(ListTag::class)
		->and($tag->getCompound("nested")->getInt("a"))->toBe(1)
		->and($tag->getAs("slot", ByteTag::class)->value)->toBe(3)
		->and($tag->getAs("UUID", IntArrayTag::class)->toUuid())->toBe("069a79f4-44e9-4726-a5be-fca90e38aaf5")
		->and($tag->getUuid("UUID"))->toBe("069a79f4-44e9-4726-a5be-fca90e38aaf5");
});

test("compound getters throw on a missing key or a wrong type", function () {
	$tag = SNBTParser::parseCompound('{ name: "Steve", xp: 42, health: 20.0f }');

	expect(fn () => $tag->getString("missing"))->toThrow(SNBTTagException::class, 'No tag at key "missing".')
		->and(fn () => $tag->getInt("name"))->toThrow(SNBTTagException::class, 'Expected IntegerTag at key "name", found StringTag.')
		->and(fn () => $tag->getFloat("xp"))->toThrow(SNBTTagException::class)
		->and(fn () => $tag->getInt("health"))->toThrow(SNBTTagException::class)
		->and(fn () => $tag->getBool("xp"))->toThrow(SNBTTagException::class, 'Expected BooleanTag or ByteTag at key "xp", found IntTag.')
		->and(fn () => $tag->getCompound("name"))->toThrow(SNBTTagException::class)
		->and(SNBTTagException::missing("x"))->toBeInstanceOf(SNBTException::class);
});

test("getUuid() throws on a missing key, a wrong type or a wrong length", function () {
	$tag = SNBTParser::parseCompound('{ name: "Steve", short: [I; 1, 2, 3], longs: [L; 1l, 2l, 3l, 4l] }');

	expect(fn () => $tag->getUuid("missing"))->toThrow(SNBTTagException::class, 'No tag at key "missing".')
		->and(fn () => $tag->getUuid("name"))->toThrow(SNBTTagException::class, 'Expected IntArrayTag at key "name", found StringTag.')
		->and(fn () => $tag->getUuid("longs"))->toThrow(SNBTTagException::class)
		->and(fn () => $tag->getUuid("short"))->toThrow(SNBTInvalidArgumentException::class);
});

test("reads a UUID from a list", function () {
	$list = SNBTParser::parseTyped('[[I; 110787060, 1156138790, -1514210135, 238594805], "x"]');

	if (!$list instanceof ListTag) {
		throw new RuntimeException("Expected a ListTag.");
	}

	expect($list->getUuid(0))->toBe("069a79f4-44e9-4726-a5be-fca90e38aaf5")
		->and(fn () => $list->getUuid(1))->toThrow(SNBTTagException::class, "Expected IntArrayTag at index 1, found StringTag.");
});

test("fromUuid() builds the int array for a UUID", function () {
	$tag = IntArrayTag::fromUuid("069a79f4-44e9-4726-a5be-fca90e38aaf5");

	expect($tag->values)->toBe([110787060, 1156138790, -1514210135, 238594805])
		->and($tag->toUuid())->toBe("069a79f4-44e9-4726-a5be-fca90e38aaf5")
		->and(IntArrayTag::fromUuid("069A79F444E94726A5BEFCA90E38AAF5")->values)->toBe($tag->values)
		->and(fn () => IntArrayTag::fromUuid("not-a-uuid"))->toThrow(SNBTInvalidArgumentException::class);
});

test("fromUuid() writes a UUID into a compound and a list", function () {
	$player = SNBTParser::parseCompound('{ name: "Steve" }')
		->with("UUID", IntArrayTag::fromUuid("069a79f4-44e9-4726-a5be-fca90e38aaf5"));
	$list = (new ListTag([]))->withAppended(IntArrayTag::fromUuid("069a79f4-44e9-4726-a5be-fca90e38aaf5"));

	expect($player->getUuid("UUID"))->toBe("069a79f4-44e9-4726-a5be-fca90e38aaf5")
		->and($player->toSnbt())->toBe('{name:"Steve",UUID:[I;110787060,1156138790,-1514210135,238594805]}')
		->and($list->getUuid(0))->toBe("069a79f4-44e9-4726-a5be-fca90e38aaf5");
});

test("reads typed values from a list", function () {
	$list = SNBTParser::parseTyped('[1.5d, "x", 7s, 1b, { a: 1 }, [2]]');
	expect($list)->toBeInstanceOf(ListTag::class);

	if (!$list instanceof ListTag) {
		return;
	}

	expect($list->getFloat(0))->toBe(1.5)
		->and($list->getString(1))->toBe("x")
		->and($list->getInt(2))->toBe(7)
		->and($list->getBool(3))->toBeTrue()
		->and($list->getCompound(4)->getInt("a"))->toBe(1)
		->and($list->getList(5)->getInt(0))->toBe(2)
		->and(fn () => $list->getString(6))->toThrow(SNBTTagException::class, "No tag at index 6.")
		->and(fn () => $list->getInt(1))->toThrow(SNBTTagException::class, "Expected IntegerTag at index 1, found StringTag.");
});
