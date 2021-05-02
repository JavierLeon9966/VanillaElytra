<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra\item;

use BlockHorizons\Fireworks\item\Fireworks;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

use JavierLeon9966\VanillaElytra\VanillaElytra;

class FireworkRocket extends Fireworks{

	public function onClickAir(Player $player, Vector3 $directionVector): bool{
		if($player->getArmorInventory()->getChestplate() instanceof Elytra and $player->getGenericFlag(Entity::DATA_FLAG_GLIDING)){
			$this->pop();

			$nbt = Entity::createBaseNBT($player, new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);

			$entity = Entity::createEntity("FireworksRocket", $player->getLevel(), $nbt, $this);
			if(!$entity instanceof Entity){
				return false;
			}

			$entity->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, $player->getEyeHeight()));
			$entity->setInvisible();
			$entity->spawnToAll();

			$pk = new SetActorLinkPacket;
			$pk->link = new EntityLink($player->getId(), $entity->getId(), EntityLink::TYPE_PASSENGER, true, true);
			$player->getLevel()->broadcastPacketToViewers($player, $pk);

			$scheduler = $player->getServer()->getPluginManager()->getPlugin('VanillaElytra')->getScheduler();
			$task = $scheduler->scheduleRepeatingTask(new ClosureTask(static function() use($player): void{
				if($player->isOnline() and $player->getArmorInventory()->getChestplate() instanceof Elytra and $player->getGenericFlag(Entity::DATA_FLAG_GLIDING)){
					$player->setMotion($player->getDirectionVector()->multiply(1.8));
				}
			}), 1);
			$scheduler->scheduleDelayedTask(new ClosureTask(static function() use($task): void{
				$task->cancel();
			}), 20 * $this->getFlightDuration());

			return true;
		}
		return false;
	}
}