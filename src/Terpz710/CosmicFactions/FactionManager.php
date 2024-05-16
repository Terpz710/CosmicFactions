<?php

declare(strict_types=1);

namespace Terpz710\CosmicFactions;

use pocketmine\player\Player;

class FactionManager {

    private $factions = [];
    private $pendingInvitations = [];
    private $plugin;
    private $dataFile;

    public function __construct($plugin) {
        $this->plugin = $plugin;
        $this->dataFile = $this->plugin->getDataFolder() . "Factions.json";
        $this->loadFactions();
    }

    public function createFaction(Player $player, string $name): bool {
        if (isset($this->factions[$name])) {
            return false;
        }

        $this->factions[$name] = [
            'name' => $name,
            'leader' => $player->getName(),
            'members' => [$player->getName()],
            'claims' => [],
            'power' => 0,
            'balance' => 0.0
        ];
        $this->saveFactions();
        return true;
    }

    public function joinFaction(Player $player, string $name): bool {
        if (!isset($this->factions[$name])) {
            return false;
        }

        $this->factions[$name]['members'][] = $player->getName();
        $this->saveFactions();
        return true;
    }

    public function leaveFaction(Player $player): bool {
        foreach ($this->factions as $name => $faction) {
            if (in_array($player->getName(), $faction['members'])) {
                $this->factions[$name]['members'] = array_filter(
                    $this->factions[$name]['members'],
                    function ($member) use ($player) {
                        return $member !== $player->getName();
                    }
                );

                if ($faction['leader'] === $player->getName()) {
                    unset($this->factions[$name]);
                }

                $this->saveFactions();
                return true;
            }
        }

        return false;
    }

    public function getFaction(Player $player): ?string {
        foreach ($this->factions as $name => $faction) {
            if (in_array($player->getName(), $faction['members'])) {
                return $name;
            }
        }
        return null;
    }

    public function getFactionMembers(string $name): array {
        return $this->factions[$name]['members'] ?? [];
    }

    public function isFactionLeader(Player $player, string $name): bool {
        return isset($this->factions[$name]) && $this->factions[$name]['leader'] === $player->getName();
    }

    public function getFactionLeader(string $name): ?string {
        return $this->factions[$name]['leader'] ?? null;
    }


    public function getFactions(): array {
        return $this->factions;
    }

    public function factionExists(string $name): bool {
        return isset($this->factions[$name]);
    }

    public function setFactionData(string $name, array $data): bool {
        if (isset($this->factions[$name])) {
            $this->factions[$name] = $data;
            $this->saveFactions();
            return true;
        }
        return false;
    }

    public function disbandFaction(string $name): bool {
        if (isset($this->factions[$name])) {
            unset($this->factions[$name]);
            $this->saveFactions();
            return true;
        }
        return false;
    }

    public function claimLand(string $factionName, int $claimedArea, Player $player): void {
        if (isset($this->factions[$factionName])) {
            $pricePerBlock = 1;
            $totalPrice = $claimedArea * $pricePerBlock;
        
            if ($this->reduceFactionBalance($factionName, $totalPrice)) {
                $this->factions[$factionName]['claims'][] = $claimedArea;
                $this->saveFactions();
            } else {
                $player->sendMessage("You don't have enough money to claim this land.");
            }
        }
    }

    public function sendInvitation(string $factionName, Player $invitee): bool {
        $inviteeName = $invitee->getName();
        if (!isset($this->pendingInvitations[$inviteeName])) {
            $this->pendingInvitations[$inviteeName] = $factionName;
            return true;
        }
        return false;
    }

    public function getPendingInvitation(Player $player): ?string {
        $playerName = $player->getName();
        if (isset($this->pendingInvitations[$playerName])) {
            $factionName = $this->pendingInvitations[$playerName];
            unset($this->pendingInvitations[$playerName]);
            return $factionName;
        }
        return null;
    }

    // Power management
    public function getFactionPower(string $name): int {
        return $this->factions[$name]['power'] ?? 0;
    }

    public function setFactionPower(string $name, int $power): bool {
        if (isset($this->factions[$name])) {
            $this->factions[$name]['power'] = $power;
            $this->saveFactions();
            return true;
        }
        return false;
    }

    public function addFactionPower(string $name, int $amount): bool {
        if (isset($this->factions[$name])) {
            $this->factions[$name]['power'] += $amount;
            $this->saveFactions();
            return true;
        }
        return false;
    }

    public function reduceFactionPower(string $name, int $amount): bool {
        if (isset($this->factions[$name])) {
            $this->factions[$name]['power'] -= $amount;
            $this->saveFactions();
            return true;
        }
        return false;
    }

    private function updateFactionPower(string $factionName): void {
        if (isset($this->factions[$factionName])) {
            $claimedAreas = $this->factions[$factionName]['claims'] ?? [];
            $powerPerBlock = 10;
            $totalPower = 0;
            foreach ($claimedAreas as $claimedArea) {
                $totalPower += $claimedArea * $powerPerBlock;
            }
            $this->factions[$factionName]['power'] = $totalPower;
        }
    }

    // Balance management
    public function getFactionBalance(string $name): float {
        return $this->factions[$name]['balance'] ?? 0.0;
    }

    public function setFactionBalance(string $name, float $balance): bool {
        if (isset($this->factions[$name])) {
            $this->factions[$name]['balance'] = $balance;
            $this->saveFactions();
            return true;
        }
        return false;
    }

    public function addFactionBalance(string $name, float $amount): bool {
        if (isset($this->factions[$name])) {
            $this->factions[$name]['balance'] += $amount;
            $this->saveFactions();
            return true;
        }
        return false;
    }

    public function reduceFactionBalance(string $name, float $amount): bool {
        if (isset($this->factions[$name])) {
            $this->factions[$name]['balance'] -= $amount;
            $this->saveFactions();
            return true;
        }
        return false;
    }

    private function loadFactions(): void {
        if (file_exists($this->dataFile)) {
            $this->factions = json_decode(file_get_contents($this->dataFile), true) ?? [];
        }
    }

    private function saveFactions(): void {
        file_put_contents($this->dataFile, json_encode($this->factions, JSON_PRETTY_PRINT));
    }
}
