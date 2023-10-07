<?php

declare(strict_types=1);

namespace bajan\Envoys\commands;

use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;

class SetEnvoyCommand extends Command {

    /** @var \bajan\Envoys\Main */
    private $plugin;

    public function __construct(\bajan\Envoys\Main $plugin) {
        parent::__construct("setenvoy", "Set an envoy");
        $this->plugin = $plugin;
        $this->setPermission("envoys.cmd");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
    if ($command->getName() === "setconvoy" && $sender instanceof Player) {
        $coords = floor($sender->x) . ":" . floor($sender->y) . ":" . floor($sender->z) . ":" . $sender->getWorld()->getFolderName();

        $envoyData = $this->envoys->getAll();
        $envoyData["envoy" . count($envoyData) + 1] = $coords;
        $this->envoys->setAll($envoyData);
        $this->envoys->save();

        $sender->sendMessage(TF::GREEN . "Envoy set at $coords!");
        return true;
        }
    return false;
    }

}
