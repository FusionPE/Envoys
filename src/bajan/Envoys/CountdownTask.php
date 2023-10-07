<?php

namespace bajan\Envoys;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

class CountdownTask extends Task {
    private $plugin;
    private $player;
    private $count;
    private $message;

    public function __construct(Main $plugin, Player $player, int $count, string $message) {
        $this->plugin = $plugin;
        $this->player = $player;
        $this->count = $count;
        $this->message = $message;
    }

    public function onRun(): void {
        if ($this->count <= 0) {
            $this->plugin->runEnvoyEvent($this->player);
        } else {
            $this->player->sendMessage(TF::AQUA . "Envoy: " . $this->count);
            $this->count--;

            $this->plugin->getScheduler()->scheduleDelayedTask(new CountdownTask($this->plugin, $this->player, $this->count, $this->message), 20);
        }
    }
}

