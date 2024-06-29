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

namespace hcf\elevator;

use pocketmine\block\tile\Sign;
use pocketmine\block\utils\SignText;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use function strtolower;

final class ElevatorHandler implements Listener {

	public function handleChange(SignChangeEvent $event) : void {
		$text = $event->getNewText();

		if (strtolower($text->getLine(0)) === '[elevator]') {
			if (strtolower($text->getLine(1)) === 'up') {
				$event->setNewText(new SignText([TextFormat::colorize('&e[Elevator]'), TextFormat::colorize('&7up')]));
			} elseif (strtolower($text->getLine(1)) === 'down') {
				$event->setNewText(new SignText([TextFormat::colorize('&e[Elevator]'), TextFormat::colorize('&7down')]));
			}
		}
	}

	public function handleInteract(PlayerInteractEvent $event) : void {
		$action = $event->getAction();
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$tile = $player->getWorld()->getTile($block->getPosition());

		if (!$tile instanceof Sign) {
			return;
		}
		$lines = $tile->getText()->getLines();

		if ($action !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			return;
		}

		if ($lines[0] !== TextFormat::colorize('&e[Elevator]')) {
			return;
		}

		if ($lines[1] === TextFormat::colorize('&7up')) {
			$this->upTeleport($player, $block->getPosition());
		} elseif ($lines[1] === TextFormat::colorize('&7down')) {
			$this->downTeleport($player, $block->getPosition());
		}
	}

	private function upTeleport(Player $player, Position $position) : void {
		for ($y = $position->getFloorY() + 1; $y <= World::Y_MAX; $y++) {
			if (
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y, $position->getFloorZ())->getCollisionBoxes()) &&
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y + 1, $position->getFloorZ())->getCollisionBoxes()) &&
				!empty($position->getWorld()->getBlockAt($position->getFloorX(), $y - 1, $position->getFloorZ())->getCollisionBoxes())
			) {
				$pos = new Vector3($position->getFloorX() + 0.5, $y, $position->getFloorZ() + 0.5);
				$player->teleport($pos, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
				break;
			}
		}
	}

	private function downTeleport(Player $player, Position $position) : void {
		for ($y = $position->getFloorY() - 1; $y >= World::Y_MIN; $y--) {
			if (
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y, $position->getFloorZ())->getCollisionBoxes()) &&
				empty($position->getWorld()->getBlockAt($position->getFloorX(), $y + 1, $position->getFloorZ())->getCollisionBoxes()) &&
				!empty($position->getWorld()->getBlockAt($position->getFloorX(), $y - 1, $position->getFloorZ())->getCollisionBoxes())
			) {
				$pos = new Vector3($position->getFloorX() + 0.5, $y, $position->getFloorZ() + 0.5);
				$player->teleport($pos, $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
				break;
			}
		}
	}
}
