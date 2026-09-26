<?php

use Stilling\SNBTParser\Exceptions\SNBTInvalidArgumentException;
use Stilling\SNBTParser\Exceptions\SNBTTagException;
use Stilling\SNBTParser\SNBTParser;
use Stilling\SNBTParser\Tag\BooleanTag;
use Stilling\SNBTParser\Tag\ByteTag;
use Stilling\SNBTParser\Tag\CompoundTag;
use Stilling\SNBTParser\Tag\DoubleTag;
use Stilling\SNBTParser\Tag\IntTag;
use Stilling\SNBTParser\Tag\ListTag;
use Stilling\SNBTParser\Tag\LongTag;
use Stilling\SNBTParser\Tag\StringTag;
use Stilling\SNBTParser\Tag\Tag;

test("builds tags from native values", function () {
	expect(Tag::fromPhp(true))->toBeInstanceOf(BooleanTag::class)
		->and(Tag::fromPhp(5))->toBeInstanceOf(IntTag::class)
		->and(Tag::fromPhp(2_147_483_647))->toBeInstanceOf(IntTag::class)
		->and(Tag::fromPhp(-2_147_483_648))->toBeInstanceOf(IntTag::class)
		->and(Tag::fromPhp(2_147_483_648))->toBeInstanceOf(LongTag::class)
		->and(Tag::fromPhp(-2_147_483_649))->toBeInstanceOf(LongTag::class)
		->and(Tag::fromPhp(1.5))->toBeInstanceOf(DoubleTag::class)
		->and(Tag::fromPhp("x"))->toBeInstanceOf(StringTag::class)
		->and(Tag::fromPhp([]))->toBeInstanceOf(ListTag::class)
		->and(Tag::fromPhp([ 1, 2 ]))->toBeInstanceOf(ListTag::class)
		->and(Tag::fromPhp([ "a" => 1 ]))->toBeInstanceOf(CompoundTag::class);
});

test("builds a nested tree that mixes native values and tags", function () {
	$tag = Tag::fromPhp([
		"id" => "minecraft:chest",
		"Items" => [
			[ "Slot" => new ByteTag(0), "id" => "minecraft:lead", "count" => 1 ],
		],
		"Pos" => [ 1.0, 2.5, -3.0 ],
		"Invulnerable" => false,
	]);

	expect($tag->toSnbt())->toBe('{id:"minecraft:chest",Items:[{Slot:0b,id:"minecraft:lead",count:1}],Pos:[1.0d,2.5d,-3.0d],Invulnerable:false}');
});

test("rejects values without a tag type", function () {
	expect(fn () => Tag::fromPhp(null))->toThrow(SNBTInvalidArgumentException::class, "Cannot convert a value of type null to a tag.")
		->and(fn () => Tag::fromPhp(new stdClass()))->toThrow(SNBTInvalidArgumentException::class)
		->and(fn () => Tag::fromPhp([ "a" => null ]))->toThrow(SNBTInvalidArgumentException::class);
});

test("changes a compound without touching the original", function () {
	$original = SNBTParser::parseCompound("{a:1b,b:2b}");

	$replaced = $original->with("a", new ByteTag(9));
	$added = $original->with("c", new StringTag("x"));
	$removed = $original->without("a");

	expect($original->toSnbt())->toBe("{a:1b,b:2b}")
		->and($replaced->toSnbt())->toBe("{a:9b,b:2b}")
		->and($added->toSnbt())->toBe('{a:1b,b:2b,c:"x"}')
		->and($removed->toSnbt())->toBe("{b:2b}")
		->and($original->without("missing")->toSnbt())->toBe("{a:1b,b:2b}");
});

test("changes a list without touching the original", function () {
	$original = new ListTag([ new IntTag(1), new IntTag(2), new IntTag(3) ]);

	expect($original->with(1, new IntTag(9))->toSnbt())->toBe("[1,9,3]")
		->and($original->withAppended(new IntTag(4))->toSnbt())->toBe("[1,2,3,4]")
		->and($original->without(0)->toSnbt())->toBe("[2,3]")
		->and(array_keys($original->without(0)->items))->toBe([ 0, 1 ])
		->and($original->without(5)->toSnbt())->toBe("[1,2,3]")
		->and($original->toSnbt())->toBe("[1,2,3]")
		->and(fn () => $original->with(3, new IntTag(4)))->toThrow(SNBTTagException::class, "No tag at index 3.");
});
