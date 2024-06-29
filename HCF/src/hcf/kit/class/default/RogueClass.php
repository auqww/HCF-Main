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

use hcf\kit\class\default\_trait\CooldownTrait;
use hcf\kit\class\KitClass;
use hcf\session\SessionFactory;
use hcf\util\Utils;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\Limits;
use pocketmine\utils\TextFormat;
use function time;

final class RogueClass extends KitClass {
	use CooldownTrait;

	private array $cooldowns = [];

	private array $sugar = [];
	private array $feather = [];

	public function __construct() {
		parent::__construct('Rogue', false, [VanillaItems::CHAINMAIL_HELMET(), VanillaItems::CHAINMAIL_CHESTPLATE(), VanillaItems::CHAINMAIL_LEGGINGS(), VanillaItems::CHAINMAIL_BOOTS()], [new EffectInstance(VanillaEffects::SPEED(), Limits::INT32_MAX, 2), new EffectInstance(VanillaEffects::RESISTANCE(), Limits::INT32_MAX), new EffectInstance(VanillaEffects::JUMP_BOOST(), Limits::INT32_MAX, 3)]);
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$player = $event->getPlayer();
		$item = $event->getItem();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (!$session->getKitClass() instanceof self) {
			return;
		}

		if ($this->getCooldown($player) !== 0) {
			$player->sendMessage(TextFormat::colorize('&cYou have global cooldown. &7(' . Utils::timeFormat($this->getCooldown($player)) . ')'));
			return;
		}

		if ($item->getId() === ItemIds::SUGAR) {
			if (isset($this->sugar[$player->getXuid()]) && $this->sugar[$player->getXuid()] > time()) {
				$player->sendMessage(TextFormat::colorize('&cYou have Speed cooldown, &7' , Utils::timeFormat($this->sugar[$player->getXuid()] - time())));
				return;
			}
			$this->addCooldown($player, 5);
			$this->sugar[$player->getXuid()] = time() + 35;

			$player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have Speed IV for 5 seconds'));
		} elseif ($item->getId() === ItemIds::FEATHER) {
			if (isset($this->feather[$player->getXuid()]) && $this->feather[$player->getXuid()] > time()) {
				$player->sendMessage(TextFormat::colorize('&cYou have Jump cooldown, &7' , Utils::timeFormat($this->feather[$player->getXuid()] - time())));
				return;
			}
			$this->addCooldown($player, 5);
			$this->feather[$player->getXuid()] = time() + 40;

			$player->getEffects()->add(new EffectInstance(VanillaEffects::JUMP_BOOST(), 20 * 5, 3, false));
			$player->sendMessage(TextFormat::colorize('&aYou have Jump IV for 5 seconds'));
		}
	}

	public function handleDamage(EntityDamageEvent $event) : void {
		$entity = $event->getEntity();

		if (!$entity instanceof Player) {
			return;
		}
		$session = SessionFactory::get($entity);

		if ($session === null) {
			return;
		}

		if ($event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();

			if (!$damager instanceof Player) {
				return;
			}
			$target = SessionFactory::get($damager);

			if ($target === null) {
				return;
			}

			if (!$target->getKitClass() instanceof RogueClass) {
				return;
			}

			if ($damager->getInventory()->getItemInHand()->getId() !== ItemIds::GOLDEN_SWORD) {
				return;
			}

			if (!$this->isLookingBehind($damager, $entity)) {
				return;
			}

			if (isset($this->cooldowns[$damager->getName()]) && $this->cooldowns[$damager->getName()] > time()) {
				$damager->sendMessage(TextFormat::colorize('&cThis ability is in cooldown for ' . Utils::timeFormat($this->cooldowns[$damager->getName()] - time()) . ' seconds'));
				return;
			}
			$this->cooldowns[$damager->getName()] = time() + 15;

			$event->setModifier(0, EntityDamageEvent::MODIFIER_ARMOR);
			$event->setModifier(0, EntityDamageEvent::MODIFIER_STRENGTH);
			$event->setBaseDamage(8);

			$damager->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 10 * 20, 2));
			$damager->sendMessage(TextFormat::colorize('&cBackstabbed ' . $entity->getName() . ' dealing 4 HP damage'));
			$entity->sendMessage(TextFormat::colorize('&cYou\'re been backstabbed by ' . $damager->getName() . ' dealing 4 HP damage'));

			$damager->getInventory()->setItemInHand(VanillaItems::AIR());
		}
	}

	private function isLookingBehind(Entity $looker, Entity $viewing) : bool {
		return $viewing->getDirectionVector()->dot($looker->getDirectionVector()) > 0;
	}
}
