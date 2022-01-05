<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra\item;

use BlockHorizons\Fireworks\entity\FireworksRocket;
use BlockHorizons\Fireworks\item\Fireworks;

use JavierLeon9966\VanillaElytra\VanillaElytra;

use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\{CancelTaskException, ClosureTask};
use pocketmine\utils\AssumptionFailedError;

class FireworkRocket extends Fireworks{

	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult{
		if(!$player->isGliding()){
			return ItemUseResult::NONE();
		}

		$this->pop();

		$location = $player->getLocation();
		$entity = new FireworksRocket($location, $this);
		$entity->spawnToAll();

		$duration = 20 * $this->getFlightDuration();
		$plugin = $player->getServer()->getPluginManager()->getPlugin('VanillaElytra');
		if(!$plugin instanceof VanillaElytra){
			throw new AssumptionFailedError;
		}

		//TODO: Change this to vanilla boosting
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function() use($duration, $entity, $player): void{
			static $ticks = 0;
			if(!$player->isOnline() or ++$ticks >= $duration){
				throw new CancelTaskException;
			}elseif($player->isGliding()){
				$directionVector = $player->getDirectionVector();
				$motionBefore = $player->getMotion();
				$newMotion = $motionBefore->addVector($directionVector
				    ->multiply(1.5)
				    ->subtractVector($motionBefore)
				    ->multiply(0.5)
				    ->addVector($directionVector->multiply(0.1))
				);
				$player->setMotion($newMotion);
			}
			$entity->teleport($player->getPosition());
			$entity->setMotion($player->getMotion());
		}), 1);

		return ItemUseResult::SUCCESS();
	}
}