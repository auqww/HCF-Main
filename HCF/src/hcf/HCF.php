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

use hcf\claim\ClaimFactory;
use hcf\claim\ClaimHandler;
use hcf\command\AutoFeedCommand;
use hcf\command\BalanceCommand;
use hcf\command\ClearLagCommand;
use hcf\command\CraftCommand;
use hcf\command\EnderChestCommand;
use hcf\command\FeedCommand;
use hcf\command\FixCommand;
use hcf\command\FloatingTextCommand;
use hcf\command\LogoutCommand;
use hcf\command\NearCommand;
use hcf\command\PayCommand;
use hcf\command\PingCommand;
use hcf\command\PlayersCommand;
use hcf\command\PvPCommand;
use hcf\command\RenameCommand;
use hcf\command\StatsCommand;
use hcf\command\TeammateLocationCommand;
use hcf\elevator\ElevatorHandler;
use hcf\enchantment\command\CustomEnchantCommand;
use hcf\enchantment\EnchantmentFactory;
use hcf\enchantment\EnchantmentHandler;
use hcf\entity\CustomTextEntity;
use hcf\entity\DisconnectEntity;
use hcf\entity\EnderPearlEntity;
use hcf\entity\SplashPotionEntity;
use hcf\faction\command\FactionCommand;
use hcf\faction\FactionFactory;
use hcf\item\EnderPearl;
use hcf\item\SplashPotion;
use hcf\kit\class\ClassFactory;
use hcf\kit\command\GKitCommand;
use hcf\kit\command\KitCommand;
use hcf\kit\KitFactory;
use hcf\kit\KitHandler;
use hcf\session\SessionFactory;
use hcf\timer\command\TimerCommand;
use hcf\timer\TimerFactory;
use hcf\timer\TimerHandler;
use hcf\util\ClearLag;
use hcf\util\inventory\CraftingInventory;
use hcf\util\inventory\InventoryIds;
use JsonException;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\data\bedrock\PotionTypeIdMap;
use pocketmine\data\bedrock\PotionTypeIds;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\projectile\SplashPotion as SplashPotionAlias;
use pocketmine\item\ItemFactory;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function time;

final class HCF extends PluginBase {
	use SingletonTrait;

	private static bool $development = false;
	private static int $time;

	public static function isUnderDevelopment() : bool {
		return self::$development;
	}

	public static function getTotalTime() : int {
		return self::$time;
	}

	protected function onLoad() : void {
		self::setInstance($this);

		self::$time = time();
	}

	protected function onEnable() : void {
		$this->saveDefaultConfig();

		$this->getServer()->getNetwork()->setName(TextFormat::colorize($this->getConfig()->get('motd-text', '')));

		$this->registerHandlers();
		$this->registerCommands();
		$this->registerInventories();
		$this->registerEntities();
		$this->registerItems();

		ClaimFactory::loadAll();
		EnchantmentFactory::loadAll();
		KitFactory::loadAll();
		ClassFactory::loadAll();
		FactionFactory::loadAll();
		SessionFactory::loadAll();
		TimerFactory::loadAll();

		TimerFactory::task();
		FactionFactory::task();
		SessionFactory::task();

		ClearLag::getInstance()->task();
	}

	private function registerHandlers() : void {
		$this->getServer()->getPluginManager()->registerEvents(new ElevatorHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new EnchantmentHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new TimerHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new ClaimHandler(), $this);
		$this->getServer()->getPluginManager()->registerEvents(new KitHandler(), $this);
	}

	private function registerCommands() : void {
		$this->getServer()->getCommandMap()->registerAll('HCF', [
			// Default command
			new AutoFeedCommand(),
			new BalanceCommand(),
			new ClearLagCommand(),
			new CraftCommand(),
			new EnderChestCommand(),
			new FeedCommand(),
			new FixCommand(),
			new FloatingTextCommand(),
			new LogoutCommand(),
			new NearCommand(),
			new PayCommand(),
			new PingCommand(),
			new PlayersCommand(),
			new PvPCommand(),
			new RenameCommand(),
			new StatsCommand(),
			new TeammateLocationCommand(),
			// Custom Enchant
			new CustomEnchantCommand(),
			// Kit
			new GKitCommand(),
			new KitCommand(),
			// Faction
			new FactionCommand(),
			// Timer
			new TimerCommand()
		]);
	}

	private function registerInventories() : void {
		if (!InvMenuHandler::isRegistered()) {
			InvMenuHandler::register($this);
		}
		InvMenuHandler::getTypeRegistry()->register(InventoryIds::CRAFTING_INVENTORY, new CraftingInventory());
	}

	private function registerEntities() : void {
		EntityFactory::getInstance()->register(CustomTextEntity::class, function (World $world, CompoundTag $nbt) : CustomTextEntity {
			return new CustomTextEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
		}, ['CustomTextEntity']);
		EntityFactory::getInstance()->register(DisconnectEntity::class, function (World $world, CompoundTag $nbt) : DisconnectEntity {
			$entity = new DisconnectEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
			$entity->flagForDespawn();

			return $entity;
		}, ['DisconnectEntity']);
		EntityFactory::getInstance()->register(EnderPearlEntity::class, function (World $world, CompoundTag $nbt) : EnderPearlEntity {
			return new EnderPearlEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['ThrownEnderpearl', 'minecraft:ender_pearl'], EntityLegacyIds::ENDER_PEARL);
		EntityFactory::getInstance()->register(SplashPotionEntity::class, function(World $world, CompoundTag $nbt) : SplashPotionEntity {
			$potionType = PotionTypeIdMap::getInstance()->fromId($nbt->getShort(SplashPotionAlias::TAG_POTION_ID, PotionTypeIds::WATER));

			if($potionType === null){
				throw new SavedDataLoadingException('No such potion type');
			}
			return new SplashPotionEntity(EntityDataHelper::parseLocation($nbt, $world), null, $potionType, $nbt);
		}, ['ThrownPotion', 'minecraft:potion', 'thrownpotion'], EntityLegacyIds::SPLASH_POTION);
	}

	private function registerItems() : void {
		ItemFactory::getInstance()->register(new EnderPearl(), true);

		foreach (PotionType::getAll() as $type) {
			ItemFactory::getInstance()->register(new SplashPotion($type), true);
		}
	}

	/**
	 * @throws JsonException
	 */
	protected function onDisable() : void {
		TimerFactory::saveAll();

		FactionFactory::saveAll();
		KitFactory::saveAll();
		SessionFactory::saveAll();
	}
}
