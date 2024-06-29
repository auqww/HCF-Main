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

namespace muqsit\invmenu\type\util\builder;

use muqsit\invmenu\type\graphic\network\InvMenuGraphicNetworkTranslator;
use muqsit\invmenu\type\graphic\network\MultiInvMenuGraphicNetworkTranslator;
use muqsit\invmenu\type\graphic\network\WindowTypeInvMenuGraphicNetworkTranslator;
use function array_key_first;
use function count;

trait GraphicNetworkTranslatableInvMenuTypeBuilderTrait {

	/** @var InvMenuGraphicNetworkTranslator[] */
	private array $graphic_network_translators = [];

	public function setNetworkWindowType(int $window_type) : self {
		$this->addGraphicNetworkTranslator(new WindowTypeInvMenuGraphicNetworkTranslator($window_type));
		return $this;
	}

	public function addGraphicNetworkTranslator(InvMenuGraphicNetworkTranslator $translator) : self {
		$this->graphic_network_translators[] = $translator;
		return $this;
	}

	protected function getGraphicNetworkTranslator() : ?InvMenuGraphicNetworkTranslator {
		if (count($this->graphic_network_translators) === 0) {
			return null;
		}

		if (count($this->graphic_network_translators) === 1) {
			return $this->graphic_network_translators[array_key_first($this->graphic_network_translators)];
		}

		return new MultiInvMenuGraphicNetworkTranslator($this->graphic_network_translators);
	}
}
