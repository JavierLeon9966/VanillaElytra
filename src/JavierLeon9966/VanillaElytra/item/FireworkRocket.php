<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra\item;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;

use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;

class FireworkRocket extends Fireworks{

	public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult{
		if(!$player->isGliding()){
			return ItemUseResult::NONE();
		}

		$this->pop();

		$location = $player->getLocation();
		$entity = new FireworksRocket($location, $this);
		$entity->getNetworkProperties()->setLong(EntityMetadataProperties::MINECART_HAS_DISPLAY, $player->getId());
		$entity->setOwningEntity($player);
		$entity->spawnToAll();

		return ItemUseResult::SUCCESS();
	}
}