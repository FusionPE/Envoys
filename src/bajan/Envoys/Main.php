<?php

declare(strict_types=1);

namespace bajan\Envoys;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\Tile;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use bajan\Envoys\commands\SetEnvoyCommand;

class Main extends PluginBase implements Listener {

    // minutes
    public $spawntime = 60;

    /** @var Config */
    private $items;

    /** @var Config */
    private $envoys;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveResource("config.yml");
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->spawntime = $config->get("envoy_interval", 60);
        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $this->spawntime * 60 * 20);
        @mkdir($this->getDataFolder());
        $this->saveResource("Items.yml");
        $this->items = new Config($this->getDataFolder() . "Items.yml", Config::YAML);
        $this->envoys = new Config($this->getDataFolder() . "Envoys.yml", Config::YAML);
        $this->getServer()->getCommandMap()->register("setenvoy", new SetEnvoyCommand($this));
    }
    
    public function getItemsConfig(): Config {
        return $this->items;
    }
    
    public function getEnvoysConfig(): Config {
        return $this->envoys;
    }
    
    public function runEnvoyEvent(): void {
        $countdown = 10; // Countdown in seconds before chest spawns

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->sendMessage(TF::AQUA . "WORLD EVENT");
            $player->sendMessage(TF::GREEN . "Envoys are being spawned in the warzone! Spawning in $countdown seconds...");
        }

        $this->getScheduler()->scheduleDelayedTask(new EnvoyTask($this), $countdown * 20);

        $envoyData = $this->getEnvoysConfig()->getAll();
        foreach ($envoyData as $envoy) {
            $coords = explode(":", $envoy["coords"]);
            $world = $this->getServer()->getWorldManager()->getWorldByName($envoy["world"]);
            $tile = $world->getTile(new Vector3(intval($coords[0]), intval($coords[1]), intval($coords[2])));
            $i = rand(3, 5);

            while ($i > 0) {
                $itemsList = $this->getItemsConfig()->get("Items");

                if (is_array($itemsList)) {
                    foreach ($itemsList as $itemString) {
                        $itemObj = StringToItemParser::getInstance()->parse($itemString);

                        if ($itemObj instanceof \pocketmine\item\Item) {
                            if ($tile instanceof \pocketmine\block\tile\Chest) {
                                $chest = $tile;
                                $chest->getInventory()->addItem($itemObj);
                            }
                        }
                    }
                }

                $i--;
            }
        }
    }
}
