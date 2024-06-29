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

trait FixedInvMenuTypeBuilderTrait {

	private ?int $size = null;

	protected function getSize() : int {
		return $this->size ?? throw new LogicException("No size was provided");
	}

	public function setSize(int $size) : self {
		$this->size = $size;
		return $this;
	}
}
