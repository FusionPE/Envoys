<?php

declare(strict_types=1);

namespace bajan\Envoys;

use pocketmine\block\VanillaBlocks;
use pocketmine\block\tile\TileFactory;
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
    $envoyData = $this->envoys->getAll();

    foreach ($envoyData as $data => $world) {
        $data = explode(":", $data);
        $worldManager = $this->getServer()->getWorldManager();
        $targetWorld = $worldManager->getWorldByName($world);

        if ($targetWorld === null) {
            continue;
        }

        $x = intval($data[0]);
        $y = intval($data[1]);
        $z = intval($data[2]);

        $chunk = $targetWorld->getChunkAtPosition(new Vector3($x, $y, $z));

        if (!$chunk->isGenerated()) {
            $chunk->generate(true);
        }

        $tile = TileFactory::createTile(Tile::CHEST, $targetWorld, CompoundTag::create()->setInt(Tile::TAG_X, $x)->setInt(Tile::TAG_Y, $y)->setInt(Tile::TAG_Z, $z));

        if ($tile instanceof \pocketmine\block\tile\Chest) {
            $i = rand(3, 5);

            while ($i > 0) {
                $itemsList = $this->items->get("Items");

                if (is_array($itemsList)) {
                    foreach ($itemsList as $itemString) {
                        $itemObj = StringToItemParser::getInstance()->parse($itemString);

                        if ($itemObj instanceof \pocketmine\item\Item) {
                            $tile->getInventory()->addItem($itemObj);
                            }
                        }
                    }

                $i--;
                }
            }
        }
    }
}
