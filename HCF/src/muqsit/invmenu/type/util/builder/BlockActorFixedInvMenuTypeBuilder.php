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

use LogicException;
use muqsit\invmenu\type\BlockActorFixedInvMenuType;
use muqsit\invmenu\type\graphic\network\BlockInvMenuGraphicNetworkTranslator;

final class BlockActorFixedInvMenuTypeBuilder implements InvMenuTypeBuilder {
	use BlockInvMenuTypeBuilderTrait;
	use FixedInvMenuTypeBuilderTrait;
	use GraphicNetworkTranslatableInvMenuTypeBuilderTrait;
	use AnimationDurationInvMenuTypeBuilderTrait;

	private ?string $block_actor_id = null;

	public function __construct() {
		$this->addGraphicNetworkTranslator(BlockInvMenuGraphicNetworkTranslator::instance());
	}

	public function setBlockActorId(string $block_actor_id) : self {
		$this->block_actor_id = $block_actor_id;
		return $this;
	}

	public function build() : BlockActorFixedInvMenuType {
		return new BlockActorFixedInvMenuType($this->getBlock(), $this->getSize(), $this->getBlockActorId(), $this->getGraphicNetworkTranslator(), $this->getAnimationDuration());
	}

	private function getBlockActorId() : string {
		return $this->block_actor_id ?? throw new LogicException("No block actor ID was specified");
	}
}
