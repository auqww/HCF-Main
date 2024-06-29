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

namespace muqsit\invmenu\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryListener;
use pocketmine\item\Item;

final class SharedInventorySynchronizer implements InventoryListener {

	public function __construct(
		private Inventory $inventory
	) {}

	public function getSynchronizingInventory() : Inventory {
		return $this->inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void {
		$this->inventory->setContents($inventory->getContents());
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void {
		$this->inventory->setItem($slot, $inventory->getItem($slot));
	}
}
