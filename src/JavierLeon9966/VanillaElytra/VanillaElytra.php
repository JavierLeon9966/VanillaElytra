<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra;

use BlockHorizons\Fireworks\Loader as Fireworks;

use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerMoveEvent, PlayerToggleGlideEvent, PlayerQuitEvent};
use pocketmine\inventory\{ArmorInventory, CreativeInventory};
use pocketmine\item\{ArmorTypeInfo, ItemIdentifier, ItemFactory, ItemTypeIds, StringToItemParser};
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\{ClosureTask, TaskHandler};

use JavierLeon9966\VanillaElytra\item\{Elytra, FireworkRocket};

final class VanillaElytra extends PluginBase implements Listener{

	public const MINIMUM_PITCH = -59; // According to vanilla
	public const MAXIMUM_PITCH = 38;

	/**
	 * @var TaskHandler[]
	 * @phpstan-var array<string, TaskHandler>
	 */
	private array $glidingTicker = [];

	public function onEnable(): void{
		$itemFactory = ItemFactory::getInstance();
		$creativeInventory = CreativeInventory::getInstance();
		$stringToItemParser = StringToItemParser::getInstance();

		$elytra = new Elytra(new ItemIdentifier(ItemTypeIds::FIRST_UNUSED_ITEM_ID), 'Elytra', new ArmorTypeInfo(0, 433, ArmorInventory::SLOT_CHEST));
		$itemFactory->register($elytra, true);
		$creativeInventory->add($elytra);
		$stringToItemParser->register('elytra', static fn() => clone $elytra);
		if(class_exists(Fireworks::class)){
			$firework = new FireworkRocket(new ItemIdentifier(ItemIds::FIREWORKS, 0), 'Firework Rocket');
			$itemFactory->register($firework, true);
			$creativeInventory->add($firework);
			$stringToItemParser->register('firework_rocket', static fn() => clone $firework);
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @priority MONITOR
	 */
	public function onPlayerMove(PlayerMoveEvent $event): void{
		$player = $event->getPlayer();
		if(!$player->isGliding()){
			return;
		}

		$location = $player->getLocation();
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