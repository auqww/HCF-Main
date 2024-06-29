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

namespace hcf\faction\command\subcommand;

use hcf\faction\command\FactionSubcommand;
use hcf\faction\FactionFactory;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function array_keys;
use function array_values;
use function arsort;
use function count;
use function in_array;

final class FactionTopCommand extends FactionSubcommand {

	public function __construct() {
		parent::__construct('top', 'Use command to top factions.');
	}

	public function execute(CommandSender $sender, array $args) : void {
		$factions = $this->getFactions();

		if (count($factions) === 0) {
			$sender->sendMessage(TextFormat::colorize('&cNo factions.'));
			return;
		}
		arsort($factions);

		$names = array_keys($factions);
		$points = array_values($factions);

		$sender->sendMessage(TextFormat::colorize('&l&gTOP 10 FACTIONS&r'));

		for ($i = 0; $i < 10; $i++) {
			if (isset($names[$i]) && isset($points[$i])) {
				$sender->sendMessage(TextFormat::colorize('&g' . ($i + 1) . '. &2' . $names[$i] . ' &7- &f' . $points[$i] . ' points'));
			}
		}
	}

	private function getFactions() : array {
		$factions = [];

		foreach (FactionFactory::getAll() as $faction) {
			if (in_array($faction->getName(), ['Spawn', 'Nether-Spawn', 'End-Spawn'], true)) {
				continue;
			}
			$factions[$faction->getName()] = $faction->getPoints();
		}
		return $factions;
	}
}
