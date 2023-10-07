<?php
namespace bajan\Envoys;

use pocketmine\scheduler\Task;
use pocketmine\Server;

class EnvoyTask extends Task {

  public function __construct(Main $plugin) {
		$this->plugin = $plugin;
	}

  public function onRun() {
		$this->plugin->runEnvoyEvent();
		return true;
  }

}
