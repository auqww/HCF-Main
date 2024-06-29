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

namespace muqsit\invmenu\type\graphic\network;

use muqsit\invmenu\session\InvMenuInfo;
use muqsit\invmenu\session\PlayerSession;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

final class MultiInvMenuGraphicNetworkTranslator implements InvMenuGraphicNetworkTranslator {

	/**
	 * @param InvMenuGraphicNetworkTranslator[] $translators
	 */
	public function __construct(
		private array $translators
	) {}

	public function translate(PlayerSession $session, InvMenuInfo $current, ContainerOpenPacket $packet) : void {
		foreach ($this->translators as $translator) {
			$translator->translate($session, $current, $packet);
		}
	}
}
