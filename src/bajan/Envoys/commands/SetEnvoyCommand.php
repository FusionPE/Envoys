<?php

declare(strict_types=1);

namespace bajan\Envoys\commands;

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

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender->hasPermission("envoy.set")) {
            if ($this->plugin->setEnvoy($sender)) {
                $sender->sendMessage(TF::GREEN . "Envoy set!");
            } else {
                $sender->sendMessage(TF::RED . "Failed to set the envoy.");
            }
        } else {
            $sender->sendMessage(TF::RED . "You do not have the required permission");
        }
        return true;
    }
}
