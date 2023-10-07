<?php
namespace bajan\Envoys;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\plugin\Plugin;

class EnvoyTask extends Task {

    private $plugin;

    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick) {
        $this->plugin->runEnvoyEvent();
    }
}
