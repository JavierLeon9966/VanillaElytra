<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra;

use BlockHorizons\Fireworks\Loader as Fireworks;

use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\{Item, ItemFactory};
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\plugin\PluginBase;

use JavierLeon9966\VanillaElytra\item\{Elytra, FireworkRocket};

final class VanillaElytra extends PluginBase implements Listener{

	public function onLoad(): void{
		ItemFactory::registerItem(new Elytra, true);
		Item::addCreativeItem(Item::get(Item::ELYTRA));
	}

	public function onEnable(): void{
		if(class_exists(Fireworks::class)){
			ItemFactory::registerItem(new FireworkRocket, true);
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	/**
	 * @priority MONITOR
	 * @ignoreCancelled
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event): void{
		$player = $event->getPlayer();
		if($event->getPacket() instanceof PlayerActionPacket){
			switch($event->getPacket()->action){
				case PlayerActionPacket::ACTION_START_GLIDE:
					if($player->getGenericFlag(Entity::DATA_FLAG_GLIDING)) return;
					$player->setGenericFlag(Entity::DATA_FLAG_GLIDING, true);
					$player->eyeHeight /= 3;
					$player->height /= 3;
					break;
				case PlayerActionPacket::ACTION_STOP_GLIDE:
					if(!$player->getGenericFlag(Entity::DATA_FLAG_GLIDING)) return;
					$player->setGenericFlag(Entity::DATA_FLAG_GLIDING, false);
					$player->eyeHeight *= 3;
					$player->height *= 3;
					break;
				default:
					return;
			}
			$player->getDataPropertyManager()->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, $player->height);
			$player->setScale($player->getScale()); //Update bounding box server-side
		}
	}

	/**
	 * @priority MONITOR
	 * @ignoreCancelled
	 */
	public function onPlayerMove(PlayerMoveEvent $event): void{
		$player = $event->getPlayer();
		$elytra = $player->getArmorInventory()->getChestplate();
		if($elytra instanceof Elytra and $player->isSurvival() and $player->getGenericFlag(Entity::DATA_FLAG_GLIDING)){
			if($this->getServer()->getTick() % 20 === 0 and $elytra->applyDamage(1)){
				$player->getArmorInventory()->setChestplate($elytra);
			}
			if($player->pitch >= -59 and $player->pitch <= 38){
				$player->resetFallDistance();
			}
		}
	}
}