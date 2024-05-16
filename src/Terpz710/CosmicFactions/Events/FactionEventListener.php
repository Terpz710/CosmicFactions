<?php

declare(strict_types=1);

namespace Terpz710\CosmicFactions\Events;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerKillEvent;

use Terpz710\CosmicFactions\FactionManager;

class FactionEventListener implements Listener {

    private $factionManager;

    public function __construct(FactionManager $factionManager) {
        $this->factionManager = $factionManager;
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $victim = $event->getPlayer();
        $killer = $victim->getLastDamageCause()->getDamager();
        
        if ($killer instanceof Player) {
            $victimFaction = $this->factionManager->getFaction($victim);
            $killerFaction = $this->factionManager->getFaction($killer);

            if ($victimFaction !== null && $killerFaction !== null) {
                $victimPower = $this->factionManager->getFactionPower($victimFaction);
                $killerPower = $this->factionManager->getFactionPower($killerFaction);
                
                $this->factionManager->reduceFactionPower($victimFaction, $victimPower);
                $this->factionManager->addFactionPower($killerFaction, $victimPower);
            }
        }
    }

    public function onPlayerKill(PlayerKillEvent $event): void {
        $killer = $event->getPlayer();
        $victim = $event->getEntity();

        $killerFaction = $this->factionManager->getFaction($killer);
        $victimFaction = $this->factionManager->getFaction($victim);

        if ($killerFaction !== null && $victimFaction !== null) {
            $killerPower = $this->factionManager->getFactionPower($killerFaction);
            $victimPower = $this->factionManager->getFactionPower($victimFaction);
            
            $this->factionManager->reduceFactionPower($killerFaction, $killerPower);
            $this->factionManager->addFactionPower($victimFaction, $killerPower);
        }
    }
}