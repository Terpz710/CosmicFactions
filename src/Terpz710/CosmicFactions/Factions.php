<?php

declare(strict_types=1);

namespace Terpz710\CosmicFactions;

use pocketmine\plugin\PluginBase;

use Terpz710\CosmicFactions\FactionManager;
use Terpz710\CosmicFactions\Command\FactionCommand;

class Factions extends PluginBase {

    private $factionManager;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->factionManager = new FactionManager($this);

        $this->getServer()->getCommandMap()->register("Factions", new FactionCommand($this->factionManager));
    }

    public function getFactionManager(): FactionManager {
        return $this->factionManager;
    }
}
