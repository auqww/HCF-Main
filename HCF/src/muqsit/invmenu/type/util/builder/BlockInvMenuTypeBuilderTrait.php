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

namespace muqsit\invmenu\type\util\builder;

use LogicException;
use pocketmine\block\Block;

trait BlockInvMenuTypeBuilderTrait {

	private ?Block $block = null;

	protected function getBlock() : Block {
		return $this->block ?? throw new LogicException("No block was provided");
	}

	public function setBlock(Block $block) : self {
		$this->block = $block;
		return $this;
	}
}
