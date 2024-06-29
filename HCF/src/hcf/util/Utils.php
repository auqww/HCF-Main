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

namespace hcf\util;

use hcf\kit\Kit;
use hcf\kit\KitFactory;
use hcf\session\SessionFactory;
use InvalidArgumentException;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\inventory\Inventory;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use RuntimeException;
use function array_map;
use function array_values;
use function count;
use function explode;
use function floor;
use function gmdate;
use function preg_match;
use function strlen;
use function strtotime;
use function substr;
use function time;
use function trim;

final class Utils {

	public static function vectorToString(Vector3 $vector3, string $separator = '-') : string {
		return $vector3->getX() . $separator . $vector3->getY() . $separator . $vector3->getZ();
	}

	public static function positionToString(Position $position) : string {
		[$world, $x, $y, $z] = [$position->getWorld()->getFolderName(), $position->getFloorX(), $position->getFloorY(), $position->getFloorZ()];
		return $world . ':' . $x . ':' . $y . ':' . $z;
	}

	public static function stringToVector(string $data, string $separator = '-') : Vector3 {
		$data = explode($separator, $data);

		if (count($data) !== 3) {
			throw new RuntimeException('Data exceed.');
		}
		return new Vector3((float) $data[0], (float) $data[1], (float) $data[2]);
	}

	public static function stringToPosition(string $data) : Position {
		$data = explode(':', $data);

		if (!Server::getInstance()->getInstance()->getWorldManager()->isWorldGenerated($data[0])) {
			throw new RuntimeException('World isn\'t generated');
		}

		if (!Server::getInstance()->getInstance()->getWorldManager()->isWorldLoaded($data[0])) {
			Server::getInstance()->getWorldManager()->loadWorld($data[0]);
		}
		return new Position((float) $data[1], (float) $data[2], (float) $data[3], Server::getInstance()->getWorldManager()->getWorldByName($data[0]));
	}

	public static function timeFormat(int $time) : string {
		if ($time < 60) {
			return $time . 's';
		} elseif ($time >= 3600) {
			return gmdate('H:i:s', $time);
		} else {
			return gmdate('i:s', $time);
		}
	}

	public static function minecraftRomanNumerals(int $number) : string {
		static $romanNumerals = [
			1 => "I", 2 => "II", 3 => "III", 4 => "IV", 5 => "V",
			6 => "VI", 7 => "VII", 8 => "VII", 9 => "IX", 10 => "X"
		];
		return $romanNumerals[$number] ?? ((string) $number);
	}

	public static function stringToTime(string $duration) : int {
		$time_units = ['y' => 'year', 'M' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'm' => 'minute'];
		$regex = '/^([0-9]+y)?([0-9]+M)?([0-9]+w)?([0-9]+d)?([0-9]+h)?([0-9]+m)?$/';
		$matches = [];
		$is_matching = preg_match($regex, $duration, $matches);

		if (!$is_matching) {
			throw new InvalidArgumentException('Invalid duration. Please put numbers and letters');
		}
		$time = '';

		foreach ($matches as $index => $match) {
			if ($index === 0 || strlen($match) === 0) {
				continue;
			}
			$n = substr($match, 0, -1);
			$unit = $time_units[substr($match, -1)];
			$time .= "$n $unit ";
		}
		$time = trim($time);

		return $time === '' ? time() : strtotime($time);
	}

	public static function date(int $time) : string {
		$remaining = $time;
		$s = $remaining % 60;

		$m = null;
		$h = null;
		$d = null;

		if ($remaining >= 60) {
			$m = floor(($remaining % 3600) / 60);

			if ($remaining >= 3600) {
				$h = floor(($remaining % 86400) / 3600);

				if ($remaining >= 3600 * 24) {
					$d = floor($remaining / 86400);
				}
			}
		}
		return ($m !== null ? ($h !== null ? ($d !== null ? "$d days " : "") . "$h hours " : "") . "$m minutes " : "") . "$s seconds";
	}

	public static function createKitMenu(Player $player) : void {
		$session = SessionFactory::get($player);

		if ($session === null) {
			return;
		}
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

		foreach (KitFactory::getAll() as $kit) {
			if ($kit->getInventorySlot() === null) {
				continue;
			}
			$item = clone $kit->getItemDecorative();
			$item->setCustomName(TextFormat::colorize('&r' . $kit->getName()));
			$lore = ['&r', '&r&6Cooldown: &f' . ($kit->getCountdown() === null ? 'Unlimited' : self::date($kit->getCountdown()))];

			if ($session->getTimer($kit->getName() . '_kit') !== null) {
				$lore[] = '&r&6Available in: &f' . self::date($session->getTimer($kit->getName() . '_kit')->getTime());
			}
			$lore[] = '&r';
			$lore[] = '&r&7smooth.tebex.io';
			$item->setLore(array_map(fn(string $text) => TextFormat::colorize($text), $lore));
			$item->getNamedTag()->setString('kit_name', $kit->getName());

			$menu->getInventory()->setItem($kit->getInventorySlot(), $item);
		}

		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$player = $transaction->getPlayer();
			$item = $transaction->getItemClicked();

			if ($item->getNamedTag()->getTag('kit_name') !== null) {
				$kit = KitFactory::get($item->getNamedTag()->getString('kit_name'));
				$kit?->giveTo($player);
			}
			return $transaction->discard();
		});

		$menu->send($player, TextFormat::colorize('&dGreek Kits'));
	}

	public static function createKitOrganizationEditor(Player $player) : void {
		$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
		$contents = array_map(function (Kit $kit) {
			$item = clone $kit->getItemDecorative();
			$item->getNamedTag()->setString('kit_name', $kit->getName());
			return $item;
		}, array_values(KitFactory::getAll()));
		$menu->getInventory()->setContents($contents);
		$menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
			$item = $transaction->getItemClickedWith();

			if (!$item->isNull() && $item->getNamedTag()->getTag('kit_name') === null) {
				return $transaction->discard();
			}
			return $transaction->continue();
		});
		$menu->setInventoryCloseListener(function (Player $player, Inventory $inventory) : void {
			$contents = $inventory->getContents();

			foreach ($contents as $slot => $item) {
				if ($item->getNamedTag()->getTag('kit_name') === null) {
					continue;
				}
				$kit = KitFactory::get($item->getNamedTag()->getString('kit_name'));

				if ($kit === null) {
					continue;
				}
				$kit->setInventorySlot($slot);
			}
			$player->sendMessage(TextFormat::colorize('&aYou have been edited kit organization'));
		});
		$menu->send($player, TextFormat::colorize('&dEdit Kit Organization'));
	}
}
