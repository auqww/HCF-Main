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

namespace hcf\claim;

use hcf\faction\FactionFactory;
use hcf\session\SessionFactory;
use hcf\timer\TimerFactory;
use pocketmine\block\tile\Sign;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;
use function array_filter;
use function array_values;

final class ClaimHandler implements Listener {

	private static AxisAlignedBB $alignedBB;
	private int $border = 2000;

	public function __construct() {
		self::$alignedBB = new AxisAlignedBB(-300, World::Y_MIN, -300, 301, World::Y_MAX, 301);
	}

    public static function getAlignedBB(): AxisAlignedBB {
        return self::$alignedBB;
    }

	public function handleBreak(BlockBreakEvent $event) : void {
		$this->breakAndPlaceEvent($event);
	}

	private function breakAndPlaceEvent(BlockPlaceEvent|BlockBreakEvent $event) : void {
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$position = $block->getPosition();
		$item = $player->getInventory()->getItemInHand();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}

		if ($item->getNamedTag()->getTag('claim_tool') !== null) {
			$event->cancel();
			return;
		}
		$claim = self::insideClaim($block->getPosition());

		if ($claim === null) {
			if ((!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) && self::$alignedBB->isVectorInside($position)) {
				$event->cancel();
			}
			return;
		}

		if ((!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) && $claim->getType() !== Claim::FACTION) {
			$event->cancel();
			return;
		}
		$faction = $session->getFaction();

		if ($faction === null) {
			$event->cancel();
			return;
		}

		if ((!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) && $claim->getDefaultName() !== $faction->getName()) {
			$targetFaction = FactionFactory::get($claim->getDefaultName());

			if ($targetFaction !== null && !$targetFaction->isRaidable()) {
				$event->cancel();
				$player->sendMessage(TextFormat::colorize('&cYou cannot break blocks in &e' . $targetFaction->getName() . '\'s &cterritory'));
			}
		}
	}

	public static function insideClaim(Position $position) : ?Claim {
		$claim = array_values(array_filter(ClaimFactory::getAll(), fn(Claim $claim) => $claim->inside($position)));
		return $claim[0] ?? null;
	}

	public function handlePlace(BlockPlaceEvent $event) : void {
		$this->breakAndPlaceEvent($event);
	}

	public function handleTeleport(EntityTeleportEvent $event) : void {
		$player = $event->getEntity();
		$to = $event->getTo();

		if (!$player instanceof Player) {
			return;
		}
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$claim = self::insideClaim($to);

		if ($claim === null) {
			return;
		}

		if ($session->getTimer('spawn_tag') !== null && $claim->getType() === Claim::SPAWN) {
			$event->cancel();
			$player->sendMessage(TextFormat::colorize('&cYou have spawn tag. You cannot teleport to this position.'));
			return;
		}

		if ($session->getTimer('pvp_timer') !== null && $claim->getType() === Claim::FACTION && $claim->getDefaultName() !== $session->getFaction()?->getName()) {
			$event->cancel();
			$player->sendMessage(TextFormat::colorize('&cYou have pvp timer. You cannot teleport to this position.'));
		}
	}

	/**
	 * @handleCancelled true
	 */
	public function handleChat(PlayerChatEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$session->getClaimCreatorHandler()?->handleChat($event);
	}

	public function handleInteract(PlayerInteractEvent $event) : void {
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$position = $block->getPosition();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$tile = $position->getWorld()->getTile($position);

		if ($tile instanceof Sign) {
			return;
		}
		$purgeEvent = TimerFactory::get('Purge');

		if ($purgeEvent !== null && $purgeEvent->isEnabled()) {
			return;
		}

		if ((!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) && self::$alignedBB->isVectorInside($position)) {
			$event->cancel();
			return;
		}
		$claim = self::insideClaim($block->getPosition());

		if ((!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) && $claim !== null && $claim->getType() !== Claim::FACTION) {
			$event->cancel();
			return;
		}
		$claimCreatorHandler = $session->getClaimCreatorHandler();

		if ($claimCreatorHandler !== null) {
			$claimCreatorHandler->handleInteract($event);
			return;
		}
		$faction = $session->getFaction();

		if ($claim !== null && $faction === null) {
			$event->cancel();
			return;
		}

		if ((!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) && $faction !== null && $claim !== null && $claim->getDefaultName() !== $faction->getName()) {
			$targetFaction = FactionFactory::get($claim->getDefaultName());

			if ($targetFaction !== null && !$targetFaction->isRaidable()) {
				$event->cancel();
				$player->sendMessage(TextFormat::colorize('&cYou cannot break blocks in &e' . $targetFaction->getName() . '\'s &cterritory'));
			}
		}
	}

	public function handleDropItem(PlayerDropItemEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$session->getClaimCreatorHandler()?->handleDropItem($event);
	}

	public function handleMove(PlayerMoveEvent $event) : void {
		$player = $event->getPlayer();
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$position = $player->getPosition();

		if ($position->getX() > $this->border || $position->getFloorX() < -$this->border || $position->getFloorZ() > $this->border || $position->getFloorZ() < -$this->border) {
			$event->cancel();
			return;
		}
		$currentClaim = $session->getCurrentClaim();
		$insideClaim = self::insideClaim($player->getPosition());

		if ($insideClaim === null) {
			if ($currentClaim !== null) {
				if ($currentClaim->getType() === Claim::SPAWN) {
					$pvp_timer = $session->getTimer('pvp_timer');

					if ($pvp_timer !== null && $pvp_timer->isPaused()) {
						$pvp_timer->setPaused(false);
					}
				}
				$player->sendMessage(TextFormat::colorize('&eNow leaving: ' . $currentClaim->getName($session->getFaction()?->getName())));
				$player->sendMessage(TextFormat::colorize('&eNow entering: &c' . (!self::$alignedBB->isVectorInside($player->getPosition()) ? 'Wilderness' : 'Warzone') . ' &e(&cDeathban&e)'));
				$session->setCurrentClaim(null);
			}
			return;
		}

		if ($insideClaim->equals($currentClaim)) {
			return;
		}
		$pvp_timer = $session->getTimer('pvp_timer');
		$starting_timer = $session->getTimer('starting_timer');
		$spawn_tag = $session->getTimer('spawn_tag');

		if ($currentClaim !== null) {
			if ($currentClaim->getType() === Claim::SPAWN) {
				if ($pvp_timer !== null && $pvp_timer->isPaused()) {
					$pvp_timer->setPaused(false);
				}
			}
			$leavingMessage = TextFormat::colorize('&eNow leaving: ' . $currentClaim->getName($session->getFaction()?->getName()));
		} else {
			$leavingMessage = TextFormat::colorize('&eNow leaving: &c' . (!self::$alignedBB->isVectorInside($player->getPosition()) ? 'Wilderness' : 'Warzone') . ' &e(&cDeathban&e)');
		}

		if ($insideClaim->getType() === Claim::SPAWN) {
			if ($spawn_tag !== null) {
				$event->cancel();
				return;
			}

			if ($pvp_timer !== null && !$pvp_timer->isPaused()) {
				$pvp_timer->setPaused(true);
			}
		} elseif ($insideClaim->getType() === Claim::FACTION) {
			if ($pvp_timer !== null) {
				$event->cancel();
				return;
			}

			if ($starting_timer !== null && $insideClaim->getDefaultName() !== $session->getFaction()?->getName()) {
				$event->cancel();
				return;
			}
		}
		$player->sendMessage($leavingMessage);
		$player->sendMessage(TextFormat::colorize('&eNow entering: ' . $insideClaim->getName($session->getFaction()?->getName())));
		$session->setCurrentClaim($insideClaim);
	}
}
