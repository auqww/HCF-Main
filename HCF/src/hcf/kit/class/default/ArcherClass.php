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

namespace hcf\kit\class\default;

use hcf\HCF;
use hcf\kit\class\default\_trait\CooldownTrait;
use hcf\kit\class\KitClass;
use hcf\session\SessionFactory;
use hcf\util\Utils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use function intval;
use function time;

final class ArcherClass extends KitClass {
	use CooldownTrait;

	/**
	 * @param TaskHandler[] $tasks
	 */
	public function __construct(
		private array $archerMark = [],
		private array $sugar = [],
		private array $feather = [],
		private array $tasks = []
	) {
		parent::__construct('Archer', false, [VanillaItems::LEATHER_CAP(), VanillaItems::LEATHER_TUNIC(), VanillaItems::LEATHER_PANTS(), VanillaItems::LEATHER_BOOTS()], [new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 2), new EffectInstance(VanillaEffects::RESISTANCE(), Limits::INT32_MAX, 1), new EffectInstance(VanillaEffects::FIRE_RESISTANCE(), Limits::INT32_MAX)]);
	}

	public function handleDamage(EntityDamageEvent $event) : void {
		$player = $event->getEntity();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if ($event instanceof EntityDamageByChildEntityEvent) {
			$child = $event->getChild();
			$killer = $event->getDamager();

			if (!$child instanceof Arrow || !$killer instanceof Player) {
				return;
			}
			$target = SessionFactory::get($killer);

			if ($target === null) {
				return;
			}

			if (!$target->getKitClass() instanceof ArcherClass) {
				return;
			}

			if ($session->getKitClass() instanceof ArcherClass) {
				$killer->sendMessage(TextFormat::colorize('&cYou can\'t archer tag someone who has the same as you.'));
				return;
			}
			$killer->sendMessage(TextFormat::colorize('&e[&9Archer Range&e(&c' . intval($player->getPosition()->distance($killer->getPosition())) . '&e)] &6Marked player for 10 seconds.'));
			$player->sendMessage(TextFormat::colorize('&c&lMarked! &r&eAn archer has shot you and marked you (+15% damage) for 10 seconds.'));

			$player->setNameTag(TextFormat::colorize('&e' . $player->getName()));
			$session->addTimer('archer_mark', '&l&6Archer Mark&r&7:', 5);

			if (isset($this->tasks[$player->getXuid()])) {
				$this->tasks[$player->getXuid()]?->cancel();
			}
			$this->tasks[$player->getXuid()] = HCF::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) : void {
				if ($player->isOnline()) {
					$player->setNameTag(TextFormat::colorize('&c' . $player->getName()));
				}
			}), 20 * 10);
			return;
		}

		if (isset($this->archerMark[$player->getXuid()]) && $this->archerMark[$player->getXuid()] > time()) {
			$baseDamage = $event->getBaseDamage();
			$event->setBaseDamage($baseDamage + 4.5);
		}
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof ArcherClass) {
			return;
		}

		if ($this->getCooldown($player) !== 0) {
			$player->sendMessage(TextFormat::colorize('&cYou have global cooldown. &7(' . Utils::timeFormat($this->getCooldown($player)) . ')'));
			return;
		}

		if ($item->getId() === ItemIds::SUGAR) {
			if (isset($this->sugar[$player->getXuid()]) && $this->sugar[$player->getXuid()] > time()) {
				$player->sendMessage(TextFormat::colorize('&cYou have Speed cooldown, &7' . Utils::timeFormat($this->sugar[$player->getXuid()] - time())));
				return;
			}
			$this->addCooldown($player, 5);
			$this->sugar[$player->getXuid()] = time() + 35;

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have Speed IV for 5 seconds'));
		} elseif ($item->getId() === ItemIds::FEATHER) {
			if (isset($this->feather[$player->getXuid()]) && $this->feather[$player->getXuid()] > time()) {
				$player->sendMessage(TextFormat::colorize('&cYou have Jump cooldown, &7' , Utils::timeFormat($this->feather[$player->getXuid()] - time())));
				return;
			}
			$this->addCooldown($player, 5);
			$this->feather[$player->getXuid()] = time() + 40;

			$item->pop();
			$player->getInventory()->setItemInHand($item);

			$player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have Jump IV for 5 seconds'));
		}
	}
}
