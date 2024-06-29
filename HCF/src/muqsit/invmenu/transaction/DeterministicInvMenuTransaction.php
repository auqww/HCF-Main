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

namespace muqsit\invmenu\transaction;

use Closure;
use LogicException;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class DeterministicInvMenuTransaction implements InvMenuTransaction {

	public function __construct(
		private InvMenuTransaction       $inner,
		private InvMenuTransactionResult $result
	) {}

	public function continue() : InvMenuTransactionResult {
		throw new LogicException("Cannot change state of deterministic transactions");
	}

	public function discard() : InvMenuTransactionResult {
		throw new LogicException("Cannot change state of deterministic transactions");
	}

	public function then(?Closure $callback) : void {
		$this->result->then($callback);
	}

	public function getPlayer() : Player {
		return $this->inner->getPlayer();
	}

	public function getOut() : Item {
		return $this->inner->getOut();
	}

	public function getIn() : Item {
		return $this->inner->getIn();
	}

	public function getItemClicked() : Item {
		return $this->inner->getItemClicked();
	}

	public function getItemClickedWith() : Item {
		return $this->inner->getItemClickedWith();
	}

	public function getAction() : SlotChangeAction {
		return $this->inner->getAction();
	}

	public function getTransaction() : InventoryTransaction {
		return $this->inner->getTransaction();
	}
}
