<?php

declare(strict_types=1);

namespace Terpz710\CosmicFactions\Command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use Terpz710\CosmicFactions\FactionManager;

class FactionCommand extends Command {

    private $factionManager;
    private $pos1 = [];
    private $pos2 = [];

    public function __construct(FactionManager $factionManager) {
        parent::__construct("f", "Factions");
        $this->setPermission("factions.cmd");
        $this->factionManager = $factionManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used by players.");
            return false;
        }

        if (empty($args)) {
            $sender->sendMessage("Usage: /f help");
            return false;
        }

        $subCommand = array_shift($args);

        switch ($subCommand) {
            case 'create':
                $this->handleCreateCommand($sender, $args);
                break;

            case 'leave':
                $this->handleLeaveCommand($sender);
                break;

            case 'disband':
                $this->handleDisbandCommand($sender, $args);
                break;

            case 'members':
                $this->handleMembersCommand($sender, $args);
                break;

            case 'claim':
                $this->handleClaimCommand($sender, $args);
                break;

            case 'promote':
                $this->handlePromoteCommand($sender, $args);
                break;

            case 'powertop':
                $this->handlePowerTopCommand($sender, $args);
                break;

            case 'moneytop':
                $this->handleMoneyTopCommand($sender, $args);
                break;

            case 'help':
                $this->handleHelpCommand($sender, $args);
                break;

            case 'status':
                $this->handleStatusCommand($sender, $args);
                break;

            case 'seestatus':
                $this->handleSeeStatusCommand($sender, $args);
                break;

            default:
                $sender->sendMessage("The subcommand §c{$subCommand}§f doesnt exist... do §e/f help§f to get started!");
                break;
        }
        return true;
    }

    private function handleHelpCommand(Player $player, array $args): void {
        $player->sendMessage("-----------------§e§lFactions Commands§r§f-----------------");
        $player->sendMessage("/f create <name> - Create a new faction");
        $player->sendMessage("/f leave - Leave your current faction");
        $player->sendMessage("/f disband - Disband your faction");
        $player->sendMessage("/f members - Show a list of members and leaders in your faction");
        $player->sendMessage("/f promote <player> - Promote a member to leader (leader only)");
        $player->sendMessage("/f powertop - Show the top factions by power");
        $player->sendMessage("/f moneytop - Show the top factions by money balance");
        $player->sendMessage("/f status - Show the your faction status");
        $player->sendMessage("/f seestatus - Shows the selected faction status");
        $player->sendMessage("/f claim pos1|pos2 - Claim certain amount of land");
        $player->sendMessage("-----------------------------------------------------------");
        
    }

    private function handleCreateCommand(Player $player, array $args): void {
        if (count($args) !== 1) {
            $player->sendMessage("Usage: /f create <name>");
            return;
        }

        $name = $args[0];

        $existingFaction = $this->factionManager->getFaction($player);
        if ($existingFaction !== null) {
            $player->sendMessage("You are already in a faction. You cannot create a new one.");
            return;
        }

        $success = $this->factionManager->createFaction($player, $name);

        if ($success) {
            $player->sendMessage("Faction '$name' created successfully.");
        } else {
            $player->sendMessage("Faction '$name' already exists.");
        }
    }

    private function handleLeaveCommand(Player $player): void {
        $factionName = $this->factionManager->getFaction($player);

        if ($factionName !== null) {
            if ($this->factionManager->isFactionLeader($player, $factionName)) {
                $player->sendMessage("You cannot leave the faction as a leader. Disband the faction instead.");
                return;
            }

            $success = $this->factionManager->leaveFaction($player);

            if ($success) {
                $player->sendMessage("You have left your faction.");
            } else {
                $player->sendMessage("An error occurred while leaving the faction.");
            }
        } else {
            $player->sendMessage("You are not in a faction.");
        }
    }

    private function handleDisbandCommand(Player $player, array $args): void {
        $factionName = $this->factionManager->getFaction($player);

        if ($factionName === null) {
            $player->sendMessage("You are not in a faction.");
            return;
        }

        if (!$this->factionManager->isFactionLeader($player, $factionName)) {
            $player->sendMessage("Only the leader can disband the faction.");
            return;
        }

        $success = $this->factionManager->disbandFaction($factionName);

        if ($success) {
            $player->sendMessage("Faction '$factionName' disbanded successfully.");
        } else {
            $player->sendMessage("An error occurred while disbanded the faction.");
        }
    }

    private function handleMembersCommand(Player $player, array $args): void {
        $factionName = $this->factionManager->getFaction($player);

        if ($factionName !== null) {
            $members = $this->factionManager->getFactionMembers($factionName);
            $leader = $this->factionManager->getFactions()[$factionName]['leader'];

            $player->sendMessage("Faction Members:");
            $player->sendMessage("- Leader: $leader");
            foreach ($members as $member) {
                $player->sendMessage("- $member");
            }
        } else {
            $player->sendMessage("You are not in a faction.");
        }
    }

    private function handlePromoteCommand(Player $player, array $args): void {
        $factionName = $this->factionManager->getFaction($player);

        if ($factionName !== null) {
            $leader = $this->factionManager->getFactions()[$factionName]['leader'];

            if ($leader === $player->getName()) {
                $newLeader = array_shift($args);
                if ($newLeader !== null) {
                    $members = $this->factionManager->getFactionMembers($factionName);
                    if (in_array($newLeader, $members)) {
                        $this->factionManager->getFactions()[$factionName]['leader'] = $newLeader;
                        $this->factionManager->saveFactions();
                        $player->sendMessage("You have promoted $newLeader to be the leader of the faction.");
                    } else {
                        $player->sendMessage("$newLeader is not a member of your faction.");
                    }
                } else {
                    $player->sendMessage("Usage: /f promote <player>");
                }
            } else {
                $player->sendMessage("Only the leader can promote members.");
            }
        } else {
            $player->sendMessage("You are not in a faction.");
        }
    }

    private function handlePowerTopCommand(Player $player, array $args): void {
        $factions = $this->factionManager->getFactions();

        usort($factions, function ($a, $b) {
            return $b['power'] <=> $a['power'];
        });

        $player->sendMessage("Top Factions by Power:");
        $count = 0;
        foreach ($factions as $name => $faction) {
            $count++;
            $player->sendMessage("$count. " . $faction['name'] . " - Power: {$faction['power']}");
            if ($count >= 10) {
                break;
            }
        }
    }

    private function handleMoneyTopCommand(Player $player, array $args): void {
        $factions = $this->factionManager->getFactions();

        usort($factions, function ($a, $b) {
            return $b['balance'] <=> $a['balance'];
        });

        $player->sendMessage("Top Factions by Money Balance:");
        $count = 0;
        foreach ($factions as $name => $faction) {
            $count++;
            $player->sendMessage("$count. " . $faction['name'] . " - Balance: {$faction['balance']}");
            if ($count >= 10) {
                break;
            }
        }
    }

    private function handleStatusCommand(Player $player, array $args): void {
        $factionName = $this->factionManager->getFaction($player);

        if ($factionName === null) {
            $player->sendMessage("You are not in a faction.");
            return;
        }

        $leader = $this->factionManager->getFactionLeader($factionName);
        $members = $this->factionManager->getFactionMembers($factionName);
        $power = $this->factionManager->getFactionPower($factionName);
        $balance = $this->factionManager->getFactionBalance($factionName);

        $position = ($leader === $player->getName()) ? 'Leader' : 'Member';

        $player->sendMessage("Faction: $factionName");
        $player->sendMessage("Position: $position");
        $player->sendMessage("Power: $power");
        $player->sendMessage("Balance: $balance");
    }

    private function handleSeeStatusCommand(Player $player, array $args): void {
        if (count($args) !== 1) {
            $player->sendMessage("Usage: /f seestatus <factionName>");
            return;
        }

        $factionName = $args[0];

        if (!$this->factionManager->factionExists($factionName)) {
            $player->sendMessage("Faction '$factionName' does not exist.");
            return;
        }

        $leader = $this->factionManager->getFactionLeader($factionName);
        $members = $this->factionManager->getFactionMembers($factionName);
        $power = $this->factionManager->getFactionPower($factionName);
        $balance = $this->factionManager->getFactionBalance($factionName);

        $player->sendMessage("Faction: $factionName");
        $player->sendMessage("Leader: $leader");
        $player->sendMessage("Members:");
        foreach ($members as $member) {
            $player->sendMessage("- $member");
        }
        $player->sendMessage("Power: $power");
        $player->sendMessage("Balance: $balance");
    }

    private function handleClaimCommand(Player $player, array $args): void {
        if (empty($args)) {
            $player->sendMessage("Usage: /f claim <pos1|pos2>");
            return;
        }

        $claimType = array_shift($args);

        switch ($claimType) {
            case 'pos1':
                $this->pos1[$player->getName()] = $player->getPosition();
                $player->sendMessage("Position 1 set for claiming.");
                break;
            case 'pos2':
                $this->pos2[$player->getName()] = $player->getPosition();
                $player->sendMessage("Position 2 set for claiming.");
                break;
            default:
                $player->sendMessage("Usage: /f claim <pos1|pos2>");
                return;
        }

        if (isset($this->pos1[$player->getName()]) && isset($this->pos2[$player->getName()])) {
            $pos1 = $this->pos1[$player->getName()];
            $pos2 = $this->pos2[$player->getName()];

            $x1 = min($pos1->getX(), $pos2->getX());
            $x2 = max($pos1->getX(), $pos2->getX());
            $z1 = min($pos1->getZ(), $pos2->getZ());
            $z2 = max($pos1->getZ(), $pos2->getZ());
            $claimedArea = ($x2 - $x1 + 1) * ($z2 - $z1 + 1);

            $factionName = $this->factionManager->getFaction($player);
            if ($factionName !== null) {
                $this->factionManager->claimLand($factionName, $claimedArea);
                $player->sendMessage("Claimed area: $claimedArea blocks. Faction land data updated.");
            } else {
                $player->sendMessage("You are not in a faction.");
            }
            unset($this->pos1[$player->getName()]);
            unset($this->pos2[$player->getName()]);
    }
}


}
