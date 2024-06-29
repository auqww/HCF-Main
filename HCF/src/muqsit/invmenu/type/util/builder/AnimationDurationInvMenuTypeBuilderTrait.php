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

trait AnimationDurationInvMenuTypeBuilderTrait {

	private int $animation_duration = 0;

	protected function getAnimationDuration() : int {
		return $this->animation_duration;
	}

	public function setAnimationDuration(int $animation_duration) : self {
		$this->animation_duration = $animation_duration;
		return $this;
	}
}
