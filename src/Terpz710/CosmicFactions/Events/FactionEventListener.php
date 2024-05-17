<?php

declare(strict_types=1);

namespace Terpz710\CosmicFactions\Events;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use Terpz710\CosmicFactions\FactionManager;

class FactionEventListener implements Listener {

    private $factionManager;

    public function __construct(FactionManager $factionManager) {
        $this->factionManager = $factionManager;
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            if ($entity->getHealth() - $event->getFinalDamage() <= 0) {
                if ($event instanceof EntityDamageByEntityEvent) {
                    $damager = $event->getDamager();
                    if ($damager instanceof Player) {
                        $this->handlePlayerKill($entity, $damager);
                    } else {
                        $this->handlePlayerDeath($entity);
                    }
                } else {
                    $this->handlePlayerDeath($entity);
                }
            }
        }
    }

    private function handlePlayerKill(Player $victim, Player $killer): void {
        $victimFaction = $this->factionManager->getFaction($victim);
        $killerFaction = $this->factionManager->getFaction($killer);

        if ($victimFaction !== null) {
            $victimPowerLoss = 1; //victim
            $this->factionManager->reduceFactionPower($victimFaction, $victimPowerLoss);
            $victim->sendMessage("You have lost $victimPowerLoss power due to being killed by {$killer->getName()}!");
        }

        if ($killerFaction !== null) {
            $killerPowerGain = 1; //killer
            $this->factionManager->addFactionPower($killerFaction, $killerPowerGain);
            $killer->sendMessage("You have gained $killerPowerGain power for killing {$victim->getName()}!");
        }
    }

    private function handlePlayerDeath(Player $victim): void {
        $victimFaction = $this->factionManager->getFaction($victim);

        if ($victimFaction !== null) {
            $victimPowerLoss = 1; //default
            $this->factionManager->reduceFactionPower($victimFaction, $victimPowerLoss);
            $victim->sendMessage("You have lost $victimPowerLoss power!");
        }
    }
}
