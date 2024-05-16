<?php

declare(strict_types=1);

namespace Terpz710\CosmicFactions\Events;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use Terpz710\CosmicFactions\FactionManager;

class FactionEventListener implements Listener {

    private $factionManager;

    public function __construct(FactionManager $factionManager) {
        $this->factionManager = $factionManager;
    }

    public function onEntityDamage(EntityDamageByEntityEvent $event): void {
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if ($entity instanceof Player && $damager instanceof Player) {
            if ($entity->getHealth() - $event->getFinalDamage() <= 0) {
                $this->handlePlayerKill($entity, $damager);
            }
        }
    }

    private function handlePlayerKill(Player $victim, Player $killer): void {
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
