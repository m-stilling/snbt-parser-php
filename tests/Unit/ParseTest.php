<?php

use Stilling\SNBTParser\SNBTParser;

test("boolean", function () {
	expect(SNBTParser::parse("true"))->toEqual(true)
		->and(SNBTParser::parse("false"))->toEqual(false);
});

test("byte", function () {
	foreach ([ 0, 42, 127, -5, -128 ] as $number) {
		expect(SNBTParser::parse("{$number}b"))->toEqual($number)
			->and(SNBTParser::parse("{$number}B"))->toEqual($number);
	}
});

test("short", function () {
	foreach ([ 0, 42, 32_767, -5, -32_768 ] as $number) {
		expect(SNBTParser::parse("{$number}s"))->toEqual($number)
			->and(SNBTParser::parse("{$number}S"))->toEqual($number);
	}
});

test("int", function () {
	foreach ([ 0, 42, 2_147_483_647, -5, -2_147_483_648 ] as $number) {
		expect(SNBTParser::parse("{$number}i"))->toEqual($number)
			->and(SNBTParser::parse("{$number}I"))->toEqual($number)
			->and(SNBTParser::parse("$number"))->toEqual($number);
	}
});

test("long", function () {
	expect(SNBTParser::parse("9223372036854775807l"))->toEqual(9_223_372_036_854_775_807)
		->and(SNBTParser::parse("9223372036854775807L"))->toEqual(9_223_372_036_854_775_807)
		->and(SNBTParser::parse("-9223372036854775808l"))->toEqual(-9_223_372_036_854_775_808)
		->and(SNBTParser::parse("-9223372036854775808L"))->toEqual(-9_223_372_036_854_775_808);

	foreach ([ 0, 42, -5 ] as $number) {
		expect(SNBTParser::parse($number . "l"))->toEqual($number)
			->and(SNBTParser::parse($number . "L"))->toEqual($number);
	}
});

test("float", function () {
	foreach ([0.0, 3.4, -3.4, 123.456, -987.654] as $number) {
		expect(SNBTParser::parse($number . "f"))->toEqual($number)
			->and(SNBTParser::parse($number . "F"))->toEqual($number);
	}
});

test("double", function () {
	foreach ([0.0, 1.79, -1.79, 123456.789, -987654.321] as $number) {
		expect(SNBTParser::parse($number . "d"))->toEqual($number)
			->and(SNBTParser::parse($number . "D"))->toEqual($number)
			->and(SNBTParser::parse($number))->toEqual($number);
	}
});

test("string", function () {
	expect(SNBTParser::parse("'single'"))->toEqual("single")
		->and(SNBTParser::parse('"double"'))->toEqual("double");
});

test("string with spaces", function () {
	expect(SNBTParser::parse('"hello world"'))->toEqual("hello world")
		->and(SNBTParser::parse("'hello world'"))->toEqual("hello world")
		->and(SNBTParser::parse('"a b c"'))->toEqual("a b c")
		->and(SNBTParser::parse('"  leading and trailing  "'))->toEqual("  leading and trailing  ")
		->and(SNBTParser::parse('{ msg: "a b c" }'))->toEqual([ "msg" => "a b c" ])
		->and(SNBTParser::parse('[ "a b", "c d" ]'))->toEqual([ "a b", "c d" ]);
});

test("string with quotes", function () {
	// A single-quoted string may contain unescaped double quotes, and vice versa.
	$nestedList = <<<'SNBT'
		[ 'a "b"', "c 'd'" ]
		SNBT;

	expect(SNBTParser::parse('\'he said "hi"\''))->toEqual('he said "hi"')
		->and(SNBTParser::parse("\"it's fine\""))->toEqual("it's fine")
		// Escaped copies of the encapsulating quote are resolved to a literal quote.
		->and(SNBTParser::parse('"he said \\"hi\\""'))->toEqual('he said "hi"')
		->and(SNBTParser::parse("'it\\'s fine'"))->toEqual("it's fine")
		// Embedded quotes survive when nested in compounds and lists.
		->and(SNBTParser::parse('{ msg: \'say "hi"\' }'))->toEqual([ "msg" => 'say "hi"' ])
		->and(SNBTParser::parse($nestedList))->toEqual([ 'a "b"', "c 'd'" ]);
});

test("string ending in backslash", function () {
	// A trailing escaped backslash must not be mistaken for an escaped closing quote.
	expect(SNBTParser::parse('"a\\\\"'))->toEqual('a\\')
		->and(SNBTParser::parse("'a\\\\'"))->toEqual('a\\')
		->and(SNBTParser::parse('"a\\\\\\\\"'))->toEqual('a\\\\')
		// An escaped quote followed by the real closing quote still resolves correctly.
		->and(SNBTParser::parse('"a\\\\\\""'))->toEqual('a\\"')
		->and(SNBTParser::parse('{ path: "C:\\\\" }'))->toEqual([ "path" => 'C:\\' ]);
});

test("string with special characters", function () {
	// Real control characters and backslashes must be escaped to stay valid JSON.
	expect(SNBTParser::parse("\"tab\there\""))->toEqual("tab\there")
		->and(SNBTParser::parse("\"line1\nline2\""))->toEqual("line1\nline2")
		->and(SNBTParser::parse('"back\\\\slash"'))->toEqual('back\\slash')
		->and(SNBTParser::parse('"url: https://example.com/path"'))->toEqual("url: https://example.com/path")
		->and(SNBTParser::parse('"café"'))->toEqual("café");
});

test("list", function () {
	expect(SNBTParser::parse("[]"))->toEqual([]);
});

test("list with compound", function () {
	expect(SNBTParser::parse("[ { test: true } ]"))->toEqual([ [ "test" => true ] ]);
});

test("compound", function () {
	expect(SNBTParser::parse("{ test: 1 }"))->toEqual(["test" => 1])
		->and(SNBTParser::parse('{ "test": 1 }'))->toEqual(["test" => 1]);
});

test("compound with extra key characters", function () {
	// Unquoted keys may contain dots, dashes and plus signs.
	expect(SNBTParser::parse("{ some.key: 1 }"))->toEqual([ "some.key" => 1 ])
		->and(SNBTParser::parse("{ a-b: 2 }"))->toEqual([ "a-b" => 2 ])
		->and(SNBTParser::parse("{ c+d: 3 }"))->toEqual([ "c+d" => 3 ])
		->and(SNBTParser::parse("{ a.b-c+d_e: 4 }"))->toEqual([ "a.b-c+d_e" => 4 ]);
});

test("tab and newline between tokens", function () {
	expect(SNBTParser::parse("{\ta:\t1\t}"))->toEqual([ "a" => 1 ])
		->and(SNBTParser::parse("[\n1,\n2\n]"))->toEqual([ 1, 2 ])
		// Whitespace before a string value is skipped, but spaces inside it are kept.
		->and(SNBTParser::parse("{\n\tmsg:\n\t\"hi there\"\n}"))->toEqual([ "msg" => "hi there" ])
		->and(SNBTParser::parse("[I;\n\t1,\n\t2\n]"))->toEqual([ 1, 2 ]);
});

test("pretty printed compound", function () {
	$snbt = <<<'SNBT'
	{
		name: "John Doe",
		age: 30,
		ids: [I; 1, 2, 3],
		nested: {
			active: true,
			tags: [ "a b", "c d" ]
		}
	}
	SNBT;

	expect(SNBTParser::parse($snbt))->toEqual([
		"name" => "John Doe",
		"age" => 30,
		"ids" => [ 1, 2, 3 ],
		"nested" => [
			"active" => true,
			"tags" => [ "a b", "c d" ],
		],
	]);
});

test("large compound", function () {
	$snbt = <<<SNBT
		{seenCredits: 0b, DeathTime: 0s, foodTickTimer: 0, recipeBook: {recipes: [], isGuiOpen: 0b, toBeDisplayed: [], isSmokerGuiOpen: 0b, isFurnaceFilteringCraftable: 0b, isFurnaceGuiOpen: 0b, isBlastingFurnaceFilteringCraftable: 0b, isBlastingFurnaceGuiOpen: 0b, isFilteringCraftable: 0b, isSmokerFilteringCraftable: 0b}, XpTotal: 0, OnGround: 1b, AbsorptionAmount: 0.0f, spawn_extra_particles_on_fall: 0b, playerGameType: 1, Invulnerable: 0b, SelectedItemSlot: 0, Brain: {memories: {}}, Dimension: "minecraft:overworld", abilities: {walkSpeed: 0.1f, instabuild: 1b, flying: 0b, flySpeed: 0.05f, mayfly: 1b, invulnerable: 1b, mayBuild: 1b}, Score: 0, Rotation: [0.0f, 0.0f], HurtByTimestamp: 0, attributes: [{base: 0.10000000149011612d, id: "minecraft:movement_speed"}, {base: 3.0d, id: "minecraft:entity_interaction_range"}, {base: 4.5d, id: "minecraft:block_interaction_range"}, {base: 0.6000000238418579d, id: "minecraft:step_height"}], foodSaturationLevel: 0.0f, fall_distance: 0.0d, SelectedItem: {id: "minecraft:lead", count: 1}, Air: 300s, warden_spawn_tracker: {ticks_since_last_warning: 10639, cooldown_ticks: 0, warning_level: 0}, XpSeed: 174870856, EnderItems: [], UUID: [I;110787060,1156138790,-1514210135,238594805], XpLevel: 0, Inventory: [{count: 1, Slot: 0b, id: "minecraft:lead"}], foodLevel: 19, Motion: [0.0d, -0.0784000015258789d, 0.0d], DataVersion: 4325, SleepTimer: 0s, XpP: 0.0f, current_impulse_context_reset_grace_time: 0, equipment: {}, Pos: [-78.5d, 65.0d, -19.5d], Health: 20.0f, HurtTime: 0s, FallFlying: 0b, Fire: 0s, ignore_fall_damage_from_current_explosion: 0b, PortalCooldown: 0, foodExhaustionLevel: 2.2999997f}
	SNBT;


	expect(SNBTParser::parse($snbt))->toEqual([
		"seenCredits" => 0,
		"DeathTime" => 0,
		"foodTickTimer" => 0,
		"recipeBook" => [
			"recipes" => [],
			"isGuiOpen" => 0,
			"toBeDisplayed" => [],
			"isSmokerGuiOpen" => 0,
			"isFurnaceFilteringCraftable" => 0,
			"isFurnaceGuiOpen" => 0,
			"isBlastingFurnaceFilteringCraftable" => 0,
			"isBlastingFurnaceGuiOpen" => 0,
			"isFilteringCraftable" => 0,
			"isSmokerFilteringCraftable" => 0,
		],
		"XpTotal" => 0,
		"OnGround" => 1,
		"AbsorptionAmount" => 0,
		"spawn_extra_particles_on_fall" => 0,
		"playerGameType" => 1,
		"Invulnerable" => 0,
		"SelectedItemSlot" => 0,
		"Brain" => [ "memories" => [] ],
		"Dimension" => "minecraft:overworld",
		"abilities" => [
			"walkSpeed" => 0.1,
			"instabuild" => 1,
			"flying" => 0,
			"flySpeed" => 0.05,
			"mayfly" => 1,
			"invulnerable" => 1,
			"mayBuild" => 1,
		],
		"Score" => 0,
		"Rotation" => [ 0, 0 ],
		"HurtByTimestamp" => 0,
		"attributes" => [
			[
				"base" => 0.10000000149012, // 0.10000000149011612 gets rounded to 0.10000000149012
				"id" => "minecraft:movement_speed",
			],
			[
				"base" => 3,
				"id" => "minecraft:entity_interaction_range",
			],
			[
				"base" => 4.5,
				"id" => "minecraft:block_interaction_range",
			],
			[
				"base" => 0.60000002384186, // 0.6000000238418579 gets rounded to 0.60000002384186
				"id" => "minecraft:step_height",
			],
		],
		"foodSaturationLevel" => 0,
		"fall_distance" => 0,
		"SelectedItem" => [
			"id" => "minecraft:lead",
			"count" => 1,
		],
		"Air" => 300,
		"warden_spawn_tracker" => [
			"ticks_since_last_warning" => 10639,
			"cooldown_ticks" => 0,
			"warning_level" => 0,
		],
		"XpSeed" => 174870856,
		"EnderItems" => [],
		"UUID" => [
			110787060,
			1156138790,
			-1514210135,
			238594805,
		],
		"XpLevel" => 0,
		"Inventory" => [ [ "count" => 1, "Slot" => 0, "id" => "minecraft:lead" ] ],
		"foodLevel" => 19,
		"Motion" => [
			0,
			-0.078400001525879, // -0.0784000015258789 gets rounded to -0.078400001525879
			0,
		],
		"DataVersion" => 4325,
		"SleepTimer" => 0,
		"XpP" => 0,
		"current_impulse_context_reset_grace_time" => 0,
		"equipment" => [],
		"Pos" => [ -78.5, 65, -19.5 ],
		"Health" => 20,
		"HurtTime" => 0,
		"FallFlying" => 0,
		"Fire" => 0,
		"ignore_fall_damage_from_current_explosion" => 0,
		"PortalCooldown" => 0,
		"foodExhaustionLevel" => 2.2999997,
	]);
});

test("byte array", function () {
	expect(SNBTParser::parse("[B;1b,2b,3b]"))->toEqual([ 1, 2, 3 ]);
});

test("int array", function () {
	expect(SNBTParser::parse("[I;1,2,3]"))->toEqual([1, 2, 3])
		->and(SNBTParser::parse("[I;1b,2s,3i] "))->toEqual([1, 2, 3]);
});

test("long array", function () {
	expect(SNBTParser::parse("[L;1l,2l,3l]"))->toEqual([1, 2, 3])
		->and(SNBTParser::parse("[L;1b,2s,3i,4l] "))->toEqual([1, 2, 3, 4]);
});

test("empty typed array", function () {
	expect(SNBTParser::parse("[B;]"))->toEqual([])
		->and(SNBTParser::parse("[I;]"))->toEqual([])
		->and(SNBTParser::parse("[L;]"))->toEqual([])
		->and(SNBTParser::parse("[I; ]"))->toEqual([])
		->and(SNBTParser::parse("{ ids: [I;] }"))->toEqual([ "ids" => [] ]);
});
