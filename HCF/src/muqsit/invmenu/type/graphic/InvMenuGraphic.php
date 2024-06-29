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

use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

interface InvMenuGraphic {

	public function send(Player $player, ?string $name) : void;

	public function sendInventory(Player $player, Inventory $inventory) : bool;

	public function remove(Player $player) : void;

	public function getNetworkTranslator() : ?InvMenuGraphicNetworkTranslator;

	/**
	 * Returns a rough duration (in milliseconds) the client
	 * takes to animate the inventory opening and closing.
	 */
	public function getAnimationDuration() : int;
}
