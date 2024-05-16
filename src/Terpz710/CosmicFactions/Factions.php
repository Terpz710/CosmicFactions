<?php

declare(strict_types=1);

namespace Terpz710\CosmicFactions;

use pocketmine\plugin\PluginBase;

use Terpz710\CosmicFactions\FactionManager;
use Terpz710\CosmicFactions\Command\FactionCommand;
use Terpz710\CosmicFactions\Events\FactionEventListener;

class Factions extends PluginBase {

    private $factionManager;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->factionManager = new FactionManager($this);

        $this->getServer()->getCommandMap()->register("Factions", new FactionCommand($this, $this->factionManager));

        $this->getServer()->getPluginManager()->registerEvents(new FactionEventListener($this->factionManager), $this);
    }

    public function getFactionManager(): FactionManager {
        return $this->factionManager;
    }
}
