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

namespace muqsit\invmenu\session\network;

use Closure;

final class NetworkStackLatencyEntry {

	public int $timestamp;
	public int $network_timestamp;
	public Closure $then;
	public float $sent_at = 0.0;

	public function __construct(int $timestamp, Closure $then, ?int $network_timestamp = null) {
		$this->timestamp = $timestamp;
		$this->then = $then;
		$this->network_timestamp = $network_timestamp ?? $timestamp;
	}
}
