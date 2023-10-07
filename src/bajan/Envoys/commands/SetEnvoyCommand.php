<?php

declare(strict_types=1);

namespace bajan\Envoys\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use bajan\Envoys\Main;

class SetEnvoyCommand extends Command {

    /** @var Main */
    private $plugin;

    public function __construct(Main $plugin) {
        parent::__construct("setenvoy", "Set an envoy");
        $this->plugin = $plugin;
        $this->setPermission("envoys.cmd");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used by players.");
            return true;
        }

        $success = $this->plugin->handleSetEnvoy($sender);
        if ($success) {
            $sender->sendMessage(TF::GREEN . "Envoy set!");
        } else {
            $sender->sendMessage(TF::RED . "Error setting envoy.");
        }
        return true;
    }
}
