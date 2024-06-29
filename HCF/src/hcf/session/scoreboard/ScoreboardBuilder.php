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

namespace hcf\session\scoreboard;

use betterkoths\koth\KothFactory;
use hcf\claim\Claim;
use hcf\claim\ClaimHandler;
use hcf\faction\FactionFactory;
use hcf\session\Session;
use hcf\session\timer\Timer;
use hcf\timer\TimerFactory;
use hcf\util\Utils;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_merge;
use function count;
use function strtolower;

final class ScoreboardBuilder {

	public function __construct(
		private Session $session,
		private string  $title = '',
		private array   $lines = [],
		private bool    $spawned = true
	) {}

	public function update() : void {
		$session = $this->session;
		$player = $session->getPlayer();

		if ($player === null || !$player->isOnline()) {
			return;
		}
		$lines = [
			'&r'
		];
		$faction = $session->getFaction();
		$globalTimers = array_filter(TimerFactory::getAll(), fn(\hcf\timer\Timer $tt) => $tt->isEnabled());
		$currentClaim = $session->getCurrentClaim();
		$claim = !ClaimHandler::getAlignedBB()->isVectorInside($player->getPosition()) ? '&cWilderness' : '&cWarzone';

		if ($currentClaim !== null) {
			if ($currentClaim->equals($session->getCurrentClaim())) {
				$claim = '&a' . $currentClaim->getDefaultName();
			} else {
				$claim = match ($currentClaim->getType()) {
					Claim::FACTION => '&c' . $currentClaim->getDefaultName(),
					Claim::SPAWN => '&a' . $currentClaim->getDefaultName(),
					Claim::ROAD => '&6' . $currentClaim->getDefaultName(),
					Claim::KOTH => '&9KoTH ' . $currentClaim->getDefaultName()
				};
			}
		}
        $lines[] = ' &l&gClaim: ' . $claim;

		if (count($globalTimers) !== 0) {
			foreach ($globalTimers as $globalTimer) {
				$lines[] = ' ' . $globalTimer->getFormat() . ' ' . Utils::timeFormat($globalTimer->getProgress());
			}
		}
		$timers = array_filter($session->getTimers(), fn(Timer $timer) => !$timer->isExpired() && $timer->isVisible());

		foreach ($timers as $timer) {
			$lines[] = ' ' . $timer->getFormat() . ' &c' . Utils::timeFormat($timer->getTime());
		}

		if ($this->session->getKitClass() !== null) {
			$kitClass = $this->session->getKitClass();

			if ($kitClass->hasEnergy() && $session->getEnergy(strtolower($kitClass->getName()) . '_energy') !== null) {
				$energy = $session->getEnergy(strtolower($kitClass->getName()) . '_energy');
				$lines[] = ' ' . $energy->getFormat() . ' &c' . $energy->getValue();
			}
		}

		if (KothFactory::getKothActive() !== null) {
			$kothActive = KothFactory::getKothActive();
			$lines[] = ' &l&2' . $kothActive->getName() . '&r&7: &c' . Utils::timeFormat($kothActive->getCurrentTime());
		}

		if ($faction !== null) {
			$focusFaction = $faction->getFocusFaction();
			$rallyMember = $faction->getRallyMember();

			if ($focusFaction !== null) {
				if (FactionFactory::get($focusFaction->getName()) === null) {
					$faction->setFocusFaction(null);
					return;
				}

				if (count($lines) > 1) {
					$lines[] = '&l';
				}
				$lines = array_merge($lines, [
					' &l&gTeam&r&7: &e' . $focusFaction->getName(),
					' &l&gHQ&r&7: &e' . ($focusFaction->getHome() !== null ? Utils::vectorToString($focusFaction->getHome()->asVector3(), ', ') : 'Has no home'),
					' &l&gDTR&r&7: &e' . $focusFaction->getDeathsUntilRaidable(),
					' &l&gOnline&r&7: &e' . count($focusFaction->getOnlineMembers())
				]);
			}

			if ($rallyMember !== null) {
				if (count($lines) > 1) {
					$lines[] = '&r&l';
				}
				$lines = array_merge($lines, [
					' &l&gRally&r&7: &e' . $rallyMember->getSession()->getName(),
					' &l&gXYZ&r&7: &e' . Utils::vectorToString($rallyMember->getLastPosition(), ', ')
				]);
			}
		}
		$lines[] = '&r&l&r';

		if (count($lines) === 6) {
			if ($this->spawned) {
				$this->despawn();
			}
			return;
		}

		if ($this->spawned) {
			$this->clear();
		} else {
			$this->spawn();
		}

		foreach ($lines as $content) {
			$this->addLine(TextFormat::colorize($content));
		}
	}

	public function despawn() : void {
		$pk = RemoveObjectivePacket::create(
			$this->session->getPlayer()?->getName()
		);
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($pk);
		$this->spawned = false;
	}

	public function clear() : void {
		$packet = new SetScorePacket();
		$packet->entries = $this->lines;
		$packet->type = SetScorePacket::TYPE_REMOVE;
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
		$this->lines = [];
	}

	public function spawn() : void {
		$packet = SetDisplayObjectivePacket::create(
			SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR,
			$this->session->getPlayer()?->getName(),
			TextFormat::colorize($this->title),
			'dummy',
			SetDisplayObjectivePacket::SORT_ORDER_ASCENDING
		);
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
		$this->spawned = true;
	}

	public function addLine(string $line, ?int $id = null) : void {
		$id = $id ?? count($this->lines);

		$entry = new ScorePacketEntry();
		$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

		if (isset($this->lines[$id])) {
			$pk = new SetScorePacket();
			$pk->entries[] = $this->lines[$id];
			$pk->type = SetScorePacket::TYPE_REMOVE;
			$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($pk);
			unset($this->lines[$id]);
		}
		$entry->scoreboardId = $id;
		$entry->objectiveName = $this->session->getPlayer()?->getName();
		$entry->score = $id;
		$entry->actorUniqueId = $this->session->getPlayer()?->getId();
		$entry->customName = $line;
		$this->lines[$id] = $entry;

		$packet = new SetScorePacket();
		$packet->entries[] = $entry;
		$packet->type = SetScorePacket::TYPE_CHANGE;
		$this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
	}
}
