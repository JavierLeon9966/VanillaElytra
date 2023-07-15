<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;

use JavierLeon9966\VanillaElytra\item\ExtraVanillaItems;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerItemUseEvent, PlayerMoveEvent, PlayerToggleGlideEvent, PlayerQuitEvent};
use pocketmine\inventory\{ArmorInventory, CreativeInventory};
use pocketmine\item\{ArmorTypeInfo, ItemIdentifier, ItemTypeIds, StringToItemParser};
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\{ClosureTask, TaskHandler};
use pocketmine\world\format\io\GlobalItemDataHandlers;

use JavierLeon9966\VanillaElytra\item\Elytra;

final class VanillaElytra extends PluginBase implements Listener{

	public const MINIMUM_PITCH = -59; // According to vanilla
	public const MAXIMUM_PITCH = 38;

	/**
	 * @var TaskHandler[]
	 * @phpstan-var array<string, TaskHandler>
	 */
	private array $glidingTicker = [];

	public function onEnable(): void{
		$itemDeserializer = GlobalItemDataHandlers::getDeserializer();
		$itemSerializer = GlobalItemDataHandlers::getSerializer();
		$creativeInventory = CreativeInventory::getInstance();
		$stringToItemParser = StringToItemParser::getInstance();

		$elytra = ExtraVanillaItems::ELYTRA();
		$itemDeserializer->map(ItemTypeNames::ELYTRA, static fn() => clone $elytra);
		$itemSerializer->map($elytra, static fn() => new SavedItemData(ItemTypeNames::ELYTRA));
		$creativeInventory->add($elytra);
		$stringToItemParser->register('elytra', static fn() => clone $elytra);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!class_exists(Fireworks::class)){
			return;
		}
		$this->getServer()->getPluginManager()->registerEvent(PlayerItemUseEvent::class, static function(PlayerItemUseEvent $event): void{
			$player = $event->getPlayer();
			$inventory = $player->getInventory();
			$item = $inventory->getItemInHand();
			if(!$item instanceof Fireworks){
				return;
			}
			if(!$player->isGliding()){
				return;
			}

			$item->pop();

			$location = $player->getLocation();
			$entity = new FireworksRocket($location, $item);
			$entity->getNetworkProperties()->setLong(EntityMetadataProperties::MINECART_HAS_DISPLAY, $player->getId());
			$entity->setOwningEntity($player);
			$entity->spawnToAll();

			$inventory->setItemInHand($item);
		}, EventPriority::MONITOR, $this);
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerMove(PlayerMoveEvent $event): void{
		$player = $event->getPlayer();
		if(!$player->isGliding()){
			return;
		}

		if($player->isOnGround()){
			$player->toggleGlide(false);
		}
		$location = $event->getFrom();
		if($location->pitch >= self::MINIMUM_PITCH and $location->pitch <= self::MAXIMUM_PITCH){
			$player->resetFallDistance();
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerToggleGlide(PlayerToggleGlideEvent $event): void{
		$player = $event->getPlayer();
		$rawUUID = $player->getUniqueId()->getBytes();
		if($event->isGliding()){
			$armorInventory = $player->getArmorInventory();
			$this->glidingTicker[$rawUUID] = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function() use($armorInventory, $player): void{
				if($player->hasFiniteResources() and ($elytra = $armorInventory->getChestplate()) instanceof Elytra and $elytra->applyDamage(1)){
					$armorInventory->setChestplate($elytra);
				}
			}), 20);
		}else{
			($this->glidingTicker[$rawUUID] ?? null)?->cancel();
			unset($this->glidingTicker[$rawUUID]);
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event): void{
		$rawUUID = $event->getPlayer()->getUniqueId()->getBytes();
		($this->glidingTicker[$rawUUID] ?? null)?->cancel();
		unset($this->glidingTicker[$rawUUID]);
	}
}