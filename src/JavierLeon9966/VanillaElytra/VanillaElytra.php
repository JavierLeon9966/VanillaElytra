<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra;

use BlockHorizons\Fireworks\Loader as Fireworks;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\{ItemIds, ItemFactory};
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmime\network\mcpe\protocol\types\entity\{EntityMetadataFlags, EntityMetadataProperties};
use pocketmine\plugin\PluginBase;

use JavierLeon9966\VanillaElytra\item\{Elytra, FireworkRocket};

final class VanillaElytra extends PluginBase implements Listener{

	public function onLoad(): void{
		$itemFactory = ItemFactory::getInstance();
		$itemFactory->register(new Elytra(new ItemIdentifier(ItemIds::ELYTRA, 0), 'Elytra'), true);
		CreativeInventory::getInstance()->add($itemFactory->get(ItemIds::ELYTRA));
	}

	public function onEnable(): void{
		if(class_exists(Fireworks::class)){
			$itemFactory = ItemFactory::getInstance();
			$itemFactory->register(new FireworkRocket(new ItemIdentifier(ItemIds::FIREWORKS, 0), 'Firework Rocket'), true);
			CreativeInventory::getInstance()->add($itemFactory->get(ItemIds::FIREWORKS));
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	/**
	 * @priority MONITOR
	 * @ignoreCancelled
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event): void{
		$packet = $event->getPacket();
		if($packet instanceof PlayerActionPacket){
			$player = $event->getOrigin()->getPlayer();
			$networkProperties = $player->getNetworkProperties();
			$sizeInfo = $player->getInitialSizeInfo()->scale($player->getScale());
			switch($packet->action){
				case PlayerActionPacket::ACTION_START_GLIDE:
					if($networkProperties->getGenericFlag(EntityMetadataFlags::GLIDING)) return;
					$networkProperties->setGenericFlag(EntityMetadataFlags::GLIDING, true);

					$player->size = new EntitySizeInfo($sizeInfo->getWidth(), $sizeInfo->getHeight() / 3, $sizeInfo->getEyeHeight() / 3);
					break;
				case PlayerActionPacket::ACTION_STOP_GLIDE:
					if(!$networkProperties->getGenericFlag(EntityMetadataFlags::GLIDING)) return;
					$networkProperties->setGenericFlag(EntityMetadataFlags::GLIDING, false);

					$player->size = new EntitySizeInfo($sizeInfo->getWidth(), $sizeInfo->getHeight() * 3, $sizeInfo->getEyeHeight() * 3);
					break;
				default:
					return;
			}

			$networkProperties->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, $player->size->getHeight());
		}
	}

	/**
	 * @priority MONITOR
	 * @ignoreCancelled
	 */
	public function onPlayerMove(PlayerMoveEvent $event): void{
		$player = $event->getPlayer();
		$elytra = $player->getArmorInventory()->getChestplate();
		if($elytra instanceof Elytra and $player->isSurvival() and $player->getNetworkProperties()->getGenericFlag(EntityMetadataFlags::GLIDING)){
			if($this->getServer()->getTick() % 20 === 0 and $elytra->applyDamage(1)){
				$player->getArmorInventory()->setChestplate($elytra);
			}

			$location = $player->getLocation();
			if($location->pitch >= -59 and $location->pitch <= 38){
				$player->resetFallDistance();
			}
		}
	}
}