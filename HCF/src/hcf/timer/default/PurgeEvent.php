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

namespace hcf\timer\default;

use hcf\timer\Timer;

final class PurgeEvent extends Timer {

	public function __construct() {
		parent::__construct('Purge', 'Use timer to purge event', '&r&l&cEvent Latam ends in &r&7:', 60 * 60);
	}
}
