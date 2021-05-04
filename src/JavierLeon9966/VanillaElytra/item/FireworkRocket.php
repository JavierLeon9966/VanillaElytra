<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra\item;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;

use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\{EntityLink, EntityMetadataFlags, EntityMetadataProperties};
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class FireworkRocket extends Fireworks{

	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult{
		if($player->getArmorInventory()->getChestplate() instanceof Elytra and $player->getNetworkProperties()->getGenericFlag(EntityMetadataFlags::GLIDING)){
			$this->pop();

			$entity = new FireworksRocket($player->getLocation(), $this);

			$entity->getNetworkProperties()->setVector3(EntityMetadataProperties::RIDER_SEAT_POSITION, new Vector3(0, $player->getEyeHeight(), 0));
			$entity->setInvisible();
			$entity->spawnToAll();

			$pk = new SetActorLinkPacket;
			$pk->link = new EntityLink($player->getId(), $entity->getId(), EntityLink::TYPE_PASSENGER, true, true);
			$player->getWorld()->broadcastPacketToViewers($player->getPosition(), $pk);

			$duration = 20 * $this->getFlightDuration();
			$task = $player->getServer()->getPluginManager()->getPlugin('VanillaElytra')->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function() use($duration, $player, &$task): void{
				static $ticks = 0;
				if(++$ticks >= $duration or !$player->isOnline()){
					$task->cancel();
				}elseif($player->getArmorInventory()->getChestplate() instanceof Elytra and $player->getNetworkProperties()->getGenericFlag(EntityMetadataFlags::GLIDING)){
					$player->setMotion($player->getDirectionVector()->multiply(1.8));
				}
			}), 1);

			return ItemUseResult::SUCCESS();
		}
		return ItemUseResult::FAIL();
	}
}