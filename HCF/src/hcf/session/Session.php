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

namespace hcf\session;

use hcf\claim\Claim;
use hcf\disconnect\DisconnectFactory;
use hcf\faction\Faction;
use hcf\faction\FactionFactory;
use hcf\faction\member\FactionMember;
use hcf\kit\class\KitClass;
use hcf\session\data\EconomyData;
use hcf\session\data\PlayerData;
use hcf\session\energy\EnergyTrait;
use hcf\session\handler\HandlerTrait;
use hcf\session\scoreboard\ScoreboardBuilder;
use hcf\session\scoreboard\ScoreboardTrait;
use hcf\session\timer\TimerTrait;
use hcf\timer\TimerFactory;
use JetBrains\PhpStorm\ArrayShape;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_map;
use function count;
use function intval;

final class Session {
	use PlayerData;
	use EconomyData;

	use HandlerTrait;
	use ScoreboardTrait;

	use EnergyTrait;
	use TimerTrait;

	public function __construct(
		private string $xuid,
		private string $rawUuid,
		private string $name,
		private bool $firstConnection = true,
		private ?Claim $currentClaim = null,
		private ?KitClass $kitClass = null,
		private ?Faction $faction = null
	) {
		$this->setScoreboard(new ScoreboardBuilder($this, '&l&9HCF &r&7| &l&9Mc'));

		if ($this->firstConnection) {
			$this->addTimer('starting_timer', '&l&aStarting Timer&r&7:', 60 * 60);
		}
	}

	public function getXuid() : string {
		return $this->xuid;
	}

	public function getRawUuid() : string {
		return $this->rawUuid;
	}

	public function getCurrentClaim() : ?Claim {
		return $this->currentClaim;
	}

	public function getKitClass() : ?KitClass {
		return $this->kitClass;
	}

	public function isOnline() : bool {
		return $this->getPlayer() !== null;
	}

	public function getPlayer() : ?Player {
		return Server::getInstance()->getPlayerByRawUUID($this->rawUuid);
	}

	public function setRawUuid(string $rawUuid) : void {
		$this->rawUuid = $rawUuid;
	}

	public function setCurrentClaim(?Claim $claim) : void {
		$this->currentClaim = $claim;
	}

	public function setKitClass(?KitClass $kitClass) : void {
		$this->kitClass = $kitClass;
	}

	public function setFaction(?Faction $faction) : void {
		$this->faction = $faction;
	}

	public function update() : void {
		$faction = $this->getFaction();
		$player = $this->getPlayer();

		$this->scoreboard->update();
		$this->updateTimers();
		$this->updateEnergies();

		if ($faction !== null && $this->isOnline()) {
			$members = array_filter($player->getViewers(), function (Player $target) use ($faction) : bool {
				$session = SessionFactory::get($target);
				return $faction->equals($session?->getFaction());
			});

			if (count($members) === 0) {
				return;
			}
			$metadata = clone $player->getNetworkProperties();
			$metadata->setString(EntityMetadataProperties::NAMETAG, TextFormat::colorize('&a' . $player->getName()));

			if ($player->getEffects()->has(VanillaEffects::INVISIBILITY())) {
				$metadata->setGenericFlag(EntityMetadataFlags::INVISIBLE, false);
				$metadata->setGenericFlag(EntityMetadataFlags::CAN_SHOW_NAMETAG, true);
			}
			$player->getNetworkSession()->getEntityEventBroadcaster()->syncActorData(array_map(fn(Player $target) => $target->getNetworkSession(), $members), $player, $metadata->getAll());
		}
	}

	public function join() : void {
		$player = $this->getPlayer();
		$faction = $this->getFaction();

		if ($player === null) {
			return;
		}
		$this->scoreboard->spawn();

		if (DisconnectFactory::get($this) !== null) {
			DisconnectFactory::get($this)->join();
		}

		$pk = GameRulesChangedPacket::create(['showCoordinates' => new BoolGameRule(true, false)]);
		$player->getNetworkSession()->sendDataPacket($pk);
		$player->setNameTag(TextFormat::colorize('&c' . $player->getName()));

		if ($faction !== null) {
			$player->setScoreTag(TextFormat::colorize('&6[&c' . $faction->getName() . ' &c' . $faction->getDeathsUntilRaidable() . '&6]'));
		}
	}

	public function getFaction() : ?Faction {
		return $this->faction;
	}

	public function getName() : string {
		return $this->name;
	}

	public function quit() : void {
		$player = $this->getPlayer();

		if ($player !== null) {
			$this->getClaimCreatorHandler()?->finish($player);
			$currentClaim = $this->getCurrentClaim();

			if (!$this->hasLogout() && ($currentClaim === null || $currentClaim->getType() !== Claim::SPAWN)) {
				if (TimerFactory::get('SOTW') !== null && !TimerFactory::get('SOTW')->isEnabled()) {
					if (!$player->hasPermission('god.permission') || !$player->getGamemode()->equals(GameMode::CREATIVE())) {
						DisconnectFactory::create($this, $player->getLocation(), $player->getArmorInventory()->getContents(), $player->getInventory()->getContents());
					}
				}
			}
		}
		$this->stopClaimCreatorHandler();
		$this->stopKitHandler();

		$this->logout = false;
		$this->firstConnection = false;
	}

	public static function deserializeData(string $xuid, array $data) : Session {
		$session = new Session($xuid, '', $data['name'], false);
		$session->setKills((int) $data['kills']);
		$session->setKillStreak((int) $data['kill-streak']);
		$session->setBestKillStreak((int) $data['best-kill-streak']);
		$session->setDeaths((int) $data['deaths']);

		if ($data['faction'] !== null) {
			$faction = FactionFactory::get($data['faction']);

			if ($faction !== null) {
				$session->setFaction($faction);
				$faction->addMember($session, intval($data['faction-rank'] ?? FactionMember::RANK_MEMBER));
			}
		}

		foreach ($data['timers'] as $name => $timer) {
			$session->addTimer($name, $timer['format'], (int) $timer['time'], (bool) $timer['paused'], (bool) $timer['visible']);
		}
		return $session;
	}

	#[ArrayShape(['name' => "string", 'balance' => "int", 'kills' => "int", 'deaths' => "int", 'kill-streak' => "int", 'best-kill-streak' => "int", 'faction' => "null|string", 'timers' => "array"])] public function serializeData() : array {
		$faction = $this->faction;
		$data = ['name' => $this->name, 'balance' => $this->balance, 'kills' => $this->kills, 'deaths' => $this->deaths, 'kill-streak' => $this->killStreak, 'best-kill-streak' => $this->bestKillStreak, 'faction' => $faction?->getName(), 'timers' => []];

		if ($faction !== null) {
			$data['faction-rank'] = $faction->getMember($this)?->getRank();
		}

		foreach ($this->getTimers() as $timerName => $timer) {
			$data['timers'][$timerName] = ['format' => $timer->getDefaultFormat(), 'time' => $timer->getTime(), 'paused' => $timer->isPaused(), 'visible' => $timer->isVisible()];
		}
		return $data;
	}
}
