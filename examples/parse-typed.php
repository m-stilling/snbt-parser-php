#!/usr/bin/env php
<?php

/*
 * Dump the typed tag tree produced by SNBTParser::parseTyped().
 *
 * Run it with:  php examples/parse-typed.php
 *
 * The sample below exercises every tag type so the var_dump shows the full
 * shape: each value is its own Tag subclass, and the int/short/byte/long (and
 * float/double) distinctions that parse() collapses are preserved here.
 */

require __DIR__ . "/../vendor/autoload.php";

use Stilling\SNBTParser\SNBTParser;

$snbt = <<<SNBT
{
    name: "Steve",
    onGround: true,
    selectedSlot: 0b,
    air: 300s,
    xpTotal: 174870856,
    score: 9999999999l,
    health: 20.0f,
    position: [-78.5d, 65.0d, -19.5d],
    UUID: [I; 110787060, 1156138790, -1514210135, 238594805],
    flags: [B; 1b, 0b, 1b],
    chunks: [L; 1l, 2l],
    inventory: [{Slot: 0b, id: "minecraft:lead", count: 1}]
}
SNBT;

echo "=== Input SNBT ===\n{$snbt}\n\n";

$tag = SNBTParser::parseTyped($snbt);

echo "=== parseTyped() — typed tag tree ===\n";
var_dump($tag);

echo "\n=== toPhp() — native PHP values ===\n";
print_r($tag->toPhp());

echo "\n=== toSnbt() — re-serialized, types preserved ===\n";
echo $tag->toSnbt() . "\n";
