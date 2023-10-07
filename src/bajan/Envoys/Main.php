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

    public function runEnvoyEvent(int $countdownTime): void {
        $countdown = $countdownTime * 60; // Convert minutes to seconds

        $countdowns = [1200, 900, 600, 300, 60, 30, 10]; // Countdowns in seconds
        $messages = [
            "$countdownTime mins",
            "15 mins",
            "10 mins",
            "5 mins",
            "1 min",
            "30 sec",
            "10 sec"
        ];

        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->sendMessage(TF::AQUA . "WORLD EVENT");
            $player->sendMessage(TF::GREEN . "Envoys are being spawned in the warzone!");

            foreach ($countdowns as $index => $count) {
                if ($count <= $countdown) {
                    $this->getScheduler()->scheduleDelayedTask(new CountdownTask($this, $player, $count, $messages[$index]), 20);
                }
            }
        }

        $envoyData = $this->getEnvoysConfig()->getAll();

        foreach ($envoyData as $envoy) {
            $coords = explode(":", $envoy["coords"]);
            $worldName = $envoy["world"];
            $world = $this->getServer()->getWorldManager()->getWorldByName($worldName);
            $tile = $world->getTile(new Vector3(intval($coords[0]), intval($coords[1]), intval($coords[2])));

            if ($countdown <= 0) {
                if ($tile instanceof \pocketmine\block\tile\Chest) {
                    $itemsList = $this->getItemsConfig()->get("Items");
                    if (is_array($itemsList)) {
                        foreach ($itemsList as $itemString) {
                            $itemObj = StringToItemParser::getInstance()->parse($itemString);
                            if ($itemObj instanceof \pocketmine\item\Item) {
                                $chest = $tile;
                                $chest->getInventory()->addItem($itemObj);
                            }
                        }
                    }
                }
            }
        }
    }
}
