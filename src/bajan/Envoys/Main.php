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

class Main extends PluginBase implements Listener {

    /** @var Config */
    private $envoys;

    /** @var Config */
    private $items;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        // Read the interval from the Envoys.yml file
        $envoysFile = $this->getDataFolder() . "Envoys.yml";
        $config = new Config($envoysFile, Config::YAML);
        $spawntime = (int)$config->get("interval", 60);

        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $spawntime * 60 * 20);
        
        @mkdir($this->getDataFolder());

        $this->envoys = new Config($envoysFile, Config::YAML);

        $this->saveResource("Items.yml");
        $this->items = new Config($this->getDataFolder() . "Items.yml", Config::YAML);
    }

    public function runEnvoyEvent(): void {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->sendMessage(TF::AQUA . "WORLD EVENT");
            $player->sendMessage(TF::GREEN . "Envoys are being spawned in the warzone!");
        }

        $envoyData = $this->envoys->getAll();
        foreach ($envoyData as $data => $world) {
            $data = explode(":", $data);
            $worldManager = $this->getServer()->getWorldManager();
            $targetWorld = $worldManager->getWorldByName($world);

            if ($targetWorld === null) {
                continue;
            }

            $tile = $targetWorld->getTile(new Vector3(intval($data[0]), intval($data[1]), intval($data[2])));

            if ($tile === null) {
                continue;
            }

            $i = rand(3, 5);

            while ($i > 0) {
                $itemsList = $this->items->get("Items");

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
