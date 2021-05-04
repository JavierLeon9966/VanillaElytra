<?php

declare(strict_types = 1);

namespace JavierLeon9966\VanillaElytra\item;

use pocketmine\item\Durable;

class Elytra extends Durable{

	public function getMaxDurability(): int{
		return 433;
	}

	public function getMaxStackSize(): int{
		return 1;
	}
}