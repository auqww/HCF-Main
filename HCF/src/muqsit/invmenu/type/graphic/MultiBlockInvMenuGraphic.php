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

namespace muqsit\invmenu\type\graphic;

use LogicException;
use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use function current;

final class MultiBlockInvMenuGraphic implements PositionedInvMenuGraphic {

	/**
	 * @param PositionedInvMenuGraphic[] $graphics
	 */
	public function __construct(
		private array $graphics
	) {}

	public function send(Player $player, ?string $name) : void {
		foreach ($this->graphics as $graphic) {
			$graphic->send($player, $name);
		}
	}

	public function sendInventory(Player $player, Inventory $inventory) : bool {
		return $this->first()->sendInventory($player, $inventory);
	}

	private function first() : PositionedInvMenuGraphic {
		$first = current($this->graphics);
		if ($first === false) {
			throw new LogicException("Tried sending inventory from a multi graphic consisting of zero entries");
		}

		return $first;
	}

	public function remove(Player $player) : void {
		foreach ($this->graphics as $graphic) {
			$graphic->remove($player);
		}
	}

	public function getNetworkTranslator() : ?InvMenuGraphicNetworkTranslator {
		return $this->first()->getNetworkTranslator();
	}

	public function getPosition() : Vector3 {
		return $this->first()->getPosition();
	}

	public function getAnimationDuration() : int {
		$max = 0;
		foreach ($this->graphics as $graphic) {
			$duration = $graphic->getAnimationDuration();
			if ($duration > $max) {
				$max = $duration;
			}
		}
		return $max;
	}
}
