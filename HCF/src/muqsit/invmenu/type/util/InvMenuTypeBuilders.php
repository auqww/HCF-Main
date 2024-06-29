<?php

/*
 * A PocketMine-MP plugin that implements Hard Core Factions.
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 *
 * @author JkqzDev
 */

declare(strict_types=1);

namespace muqsit\invmenu\type\util;

use muqsit\invmenu\type\util\builder\BlockActorFixedInvMenuTypeBuilder;
use muqsit\invmenu\type\util\builder\BlockFixedInvMenuTypeBuilder;
use muqsit\invmenu\type\util\builder\DoublePairableBlockActorFixedInvMenuTypeBuilder;

final class InvMenuTypeBuilders {

	public static function BLOCK_ACTOR_FIXED() : BlockActorFixedInvMenuTypeBuilder {
		return new BlockActorFixedInvMenuTypeBuilder();
	}

	public static function BLOCK_FIXED() : BlockFixedInvMenuTypeBuilder {
		return new BlockFixedInvMenuTypeBuilder();
	}

	public static function DOUBLE_PAIRABLE_BLOCK_ACTOR_FIXED() : DoublePairableBlockActorFixedInvMenuTypeBuilder {
		return new DoublePairableBlockActorFixedInvMenuTypeBuilder();
	}
}
