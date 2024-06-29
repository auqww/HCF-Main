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

use hcf\HCF;
use hcf\timer\Timer;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class StartOfTheWorld extends Timer {

	public function __construct() {
		parent::__construct('SOTW', 'Use timer to Start Of The World', '&l&aEvent SOTW ends in &r&r:', 3 * 60 * 60);
	}

	public function setEnabled(bool $enabled) : void {
		parent::setEnabled($enabled);

		if ($enabled) {
			HCF::getInstance()->getServer()->getCommandMap()->register('HCF', new class extends Command {

				public function __construct() {
					parent::__construct('spawn', 'Use command to teleport to spawn.');
				}

				public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
					if (!$sender instanceof Player) {
						return;
					}
					$sender->teleport($sender->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
				}
			});
		} else {
			$command = HCF::getInstance()->getServer()->getCommandMap()->getCommand('spawn');

			if ($command !== null) {
				HCF::getInstance()->getServer()->getCommandMap()->unregister($command);
			}
		}

		foreach (HCF::getInstance()->getServer()->getOnlinePlayers() as $player) {
			$player->getNetworkSession()->syncAvailableCommands();
		}
	}
}
