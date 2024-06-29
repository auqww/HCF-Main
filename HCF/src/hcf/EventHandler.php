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

namespace hcf;

use hcf\claim\Claim;
use hcf\entity\EnderPearlEntity;
use hcf\faction\event\FactionAddPointEvent;
use hcf\faction\event\FactionRemovePointEvent;
use hcf\session\Session;
use hcf\session\SessionFactory;
use hcf\timer\TimerFactory;
use pocketmine\block\FenceGate;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Armor;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ReflectionClass;
use function min;
use function time;

final class EventHandler implements Listener {

	private array $lastHit = [];

	public function handleDecay(LeavesDecayEvent $event) : void {
		$event->cancel();
	}

	public function handleDamage(EntityDamageEvent $event) : void {
		$cause = $event->getCause();
		$player = $event->getEntity();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if (TimerFactory::get('EOTW') !== null && TimerFactory::get('EOTW')->isEnabled()) {
			return;
		}

		if (TimerFactory::get('SOTW') !== null && TimerFactory::get('SOTW')->isEnabled()) {
			$event->cancel();
			return;
		}

		if ($session->getTimer('starting_timer') !== null || ($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK || $cause === EntityDamageEvent::CAUSE_PROJECTILE) && $session->getTimer('pvp_timer') !== null) {
			$event->cancel();
			return;
		}
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
			$event->cancel();
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

			if ($target->getTimer('starting_timer') !== null || $target->getTimer('pvp_timer') !== null) {
				$event->cancel();
				return;
			}
			$currentClaim = $target->getCurrentClaim();

			if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
				$event->cancel();
				return;
			}

			if ($target->getFaction() !== null && $target->getFaction()->equals($session->getFaction())) {
				$event->cancel();
				return;
			}
			$session->addTimer('spawn_tag', '&l&cSpawn Tag&r&7:', 30);
			$target->addTimer('spawn_tag', '&l&cSpawn Tag&r&7:', 30);

			$this->lastHit[$session->getXuid()] = [time() + 30, $target];
		}
	}

	public function handleItemPickup(EntityItemPickupEvent $event) : void {
		$entity = $event->getEntity();
		$origin = $event->getOrigin();

		if (!$entity instanceof Player) {
			return;
		}
		$session = SessionFactory::get($entity);

		if ($session === null) {
			return;
		}
		$owningEntity = $origin->getOwningEntity();

		if ($owningEntity === null || $owningEntity->getId() !== $entity->getId()) {
			if ($session->getTimer('starting_timer') !== null || $session->getTimer('pvp_timer') !== null) {
				$event->cancel();
			}
		}
	}

	/**
	 * @priority LOW
	 * @throws \ReflectionException
	 */
	public function handleHitBlock(ProjectileHitBlockEvent $event) : void {
		$entity = $event->getEntity();
		$rayTraceResult = $event->getRayTraceResult();
		$owningEntity = $entity->getOwningEntity();

		if (!$entity instanceof EnderPearlEntity || !$owningEntity instanceof Player) {
			return;
		}
		$insideBlock = $entity->getWorld()->getBlock($rayTraceResult->getHitVector());

		if (!EnderPearlEntity::canPassThrough($insideBlock, $event->getBlockHit())) {
			return;
		}

		if ($insideBlock instanceof FenceGate && $insideBlock->isOpen()) {
			return;
		}
		$reflectionClass = new ReflectionClass(EnderPearl::class);
		$method = $reflectionClass->getMethod('onHit');
		$method->setAccessible(true);
		$method->invoke($entity, $event);

		$entity->setOwningEntity(null);
	}

	public function handleChat(PlayerChatEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$kitHandler = $session->getKitHandler();

		if ($kitHandler !== null) {
			$kitHandler->handleChat($event);
			return;
		}
		$faction = $session->getFaction();

		if ($faction !== null && $session->hasFactionChat()) {
			$event->cancel();
			$faction->chat($player->getName(), $event->getMessage());
		}
	}

	public function handleDeath(PlayerDeathEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		/** @var Session|null $killer */
		$killer = null;

		if (isset($this->lastHit[$session->getXuid()])) {
			$data = $this->lastHit[$session->getXuid()];
			$time = (int) $data[0];

			if ($time > time()) {
				$killer = $data[1];

				$killer->addKill();
				$killer->addKillstreak();

				if ($killer->getKillStreak() > $killer->getBestKillStreak()) {
					$killer->addBestKillstreak();
				}
				$faction = $killer->getFaction();

				if ($faction !== null) {
					$ev = new FactionAddPointEvent($faction, 1);
					$ev->call();
					$faction->setPoints($faction->getPoints() + $ev->getPoints());
				}
			}
		}
		$session->removeTimer('spawn_tag');
		$session->addDeath();
		$session->removeKillstreak();
		$session->addTimer('pvp_timer', '&l&aPvP Timer&r&7:', 60 * 60);
		$faction = $session->getFaction();

		if ($faction !== null) {
			$points = $faction->getPoints();
			$deathsUntilRaidable = $faction->getDeathsUntilRaidable() - 1.0;
			$points--;

			if ($deathsUntilRaidable <= 0.00 && !$faction->isRaidable()) {
				$points -= 10;

				if ($killer !== null && $killer->getFaction() !== null) {
					$ev = new FactionAddPointEvent($killer->getFaction(), 3);
					$ev->call();

					$killer->getFaction()->setPoints($killer->getFaction()->getPoints() + $ev->getPoints());
					$killer->getFaction()->announce('&cThe faction &l' . $faction->getName() . '&r&c is now RAIDABLE!');
				}
			}
			$ev = new FactionRemovePointEvent($faction, $points);
			$ev->call();

			$faction->setDeathsUntilRaidable($deathsUntilRaidable);
			$faction->setPoints($ev->getPoints());

			if ($faction->isRaidable()) {
				$regenCooldown = $faction->getRegenCooldown() + 5 * 60;
				$faction->setRegenCooldown(min($regenCooldown, (int) HCF::getInstance()->getConfig()->get('faction.regen-cooldown', 1800)));
			} else {
				$faction->setRegenCooldown((int) HCF::getInstance()->getConfig()->get('faction.regen-cooldown', 1800));
			}

			foreach ($faction->getOnlineMembers() as $member) {
				$member->getSession()->getPlayer()?->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
			}
		}

		if ($killer === null) {
			$event->setDeathMessage(TextFormat::colorize('&c' . $player->getName() . '&4[' . $session->getKills() . '] &edied'));
		} else {
			$item = null;

			if ($killer->isOnline()) {
				$item = $killer->getPlayer()->getInventory()->getItemInHand();
			}
			$event->setDeathMessage(TextFormat::colorize('&c' . $player->getName() . '&4[' . $session->getKills() . '] &ewas slain by &c' . $killer->getName() . '&4[' . $killer->getKills() . ']' . ($item !== null ? ' &cusing ' . $item->getName() : '')));
		}
	}

	public function handleExhaust(PlayerExhaustEvent $event) : void {
		$player = $event->getPlayer();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$currentClaim = $session->getCurrentClaim();

		if ($currentClaim !== null && $currentClaim->getType() === Claim::SPAWN) {
			$event->cancel();

			if ($player->getHungerManager()->getFood() < $player->getHungerManager()->getMaxFood()) {
				$player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());
			}
			return;
		}

		if ($session->hasAutoFeed()) {
			$event->cancel();
		}
	}

	public function handleItemConsume(PlayerItemConsumeEvent $event) : void {
		$item = $event->getItem();
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if ($item->getId() === ItemIds::GOLDEN_APPLE) {
			if ($session->getTimer('golden_apple') !== null) {
				$event->cancel();
				return;
			}
			$session->addTimer('golden_apple', '&l&eApple&r&7:', 15);
		} elseif ($item->getId() === ItemIds::ENCHANTED_GOLDEN_APPLE) {
			if ($session->getTimer('golden_apple_enchanted') !== null) {
				$event->cancel();
				return;
			}
			$session->addTimer('golden_apple_enchanted', '&l&6GApple&r&7:', 60 * 60);
		}
	}

	public function handleItemUse(PlayerItemUseEvent $event) : void {
		$item = $event->getItem();
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if ($item instanceof Armor) {
			$event->cancel();
		} elseif ($item->getId() === ItemIds::ENDER_PEARL) {
			if ($session->getTimer('ender_pearl') !== null) {
				$event->cancel();
				return;
			}
			$session->addTimer('ender_pearl', '&l&6Ender Pearl&r&7:', 15);
		}
	}

	public function handleJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);
		$session?->join();

		$event->setJoinMessage(TextFormat::colorize('&7[&a+&7] &7' . $player->getName()));
	}

	public function handleLogin(PlayerLoginEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			SessionFactory::create($player);
		} else {
			if ($session->getRawUuid() !== $player->getUniqueId()->getBytes()) {
				$session->setRawUuid($player->getUniqueId()->getBytes());
			}
		}
	}

	public function handleQuit(PlayerQuitEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);
		$session?->quit();

		$event->setQuitMessage(TextFormat::colorize('&7[&c-&7] &7' . $player->getName()));
	}
}
