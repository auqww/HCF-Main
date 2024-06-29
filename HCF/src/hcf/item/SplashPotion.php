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

namespace hcf\item;

use hcf\entity\SplashPotionEntity;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\PotionType;
use pocketmine\player\Player;

final class SplashPotion extends \pocketmine\item\SplashPotion {

	public function __construct(
		private PotionType $type
	) {
		parent::__construct(new ItemIdentifier(ItemIds::SPLASH_POTION, PotionTypeIdMap::getInstance()->toId($this->type)), $this->type->getDisplayName() . ' Splash Potion', $this->type);
	}

	protected function createEntity(Location $location, Player $thrower) : Throwable {
		return new SplashPotionEntity($location, $thrower, $this->type);
	}
}
