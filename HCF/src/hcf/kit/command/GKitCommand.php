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

namespace hcf\kit\command;

use hcf\form\kit\KitForm;
use hcf\util\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class GKitCommand extends Command {

	public function __construct() {
		parent::__construct('gkit', 'Command for view all kits');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (!$sender instanceof Player) {
			return;
		}
		/*$form = new KitForm($sender);
		$sender->sendForm($form);*/
		Utils::createKitMenu($sender);
	}
}
