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

namespace hcf\session\data;

trait PlayerData {

	private int $kills = 0;
	private int $deaths = 0;
	private int $killStreak = 0;
	private int $bestKillStreak = 0;

	private bool $autoFeed = false;
	private bool $factionChat = false;
	private bool $logout = false;

	public function getKills() : int {
		return $this->kills;
	}

	public function getDeaths() : int {
		return $this->deaths;
	}

	public function getKillStreak() : int {
		return $this->killStreak;
	}

	public function getBestKillStreak() : int {
		return $this->bestKillStreak;
	}

	public function hasAutoFeed() : bool {
		return $this->autoFeed;
	}

	public function hasFactionChat() : bool {
		return $this->factionChat;
	}

	public function hasLogout() : bool {
		return $this->logout;
	}

	public function setKills(int $kills) : void {
		$this->kills = $kills;
	}

	public function setDeaths(int $deaths) : void {
		$this->deaths = $deaths;
	}

	public function setKillStreak(int $killStreak) : void {
		$this->killStreak = $killStreak;
	}

	public function setBestKillStreak(int $bestKillStreak) : void {
		$this->bestKillStreak = $bestKillStreak;
	}

	public function addKill() : void {
		$this->kills++;
	}

	public function addDeath() : void {
		$this->deaths++;
	}

	public function addKillstreak() : void {
		$this->killStreak++;
	}

	public function addBestKillstreak() : void {
		$this->bestKillStreak++;
	}

	public function setAutoFeed(bool $value = true) : void {
		$this->autoFeed = $value;
	}

	public function setFactionChat(bool $value = true) : void {
		$this->factionChat = $value;
	}

	public function setLogout(bool $value = true) : void {
		$this->logout = $value;
	}

	public function removeKillstreak() : void {
		$this->killStreak = 0;
	}
}
