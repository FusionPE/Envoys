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
        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $this->spawntime * 60 * 20);
        @mkdir($this->getDataFolder());
        $this->saveResource("Items.yml");
        $this->items = new Config($this->getDataFolder() . "Items.yml", Config::YAML);
        $this->envoys = new Config($this->getDataFolder() . "Envoys.yml", Config::YAML);
        $this->getServer()->getCommandMap()->register("setenvoy", new SetEnvoyCommand($this));
    }

    public function runEnvoyEvent(): void {
        foreach ($this->getServer()->getOnlinePlayers() as $players) {
            $players->sendMessage(TF::AQUA . "WORLD EVENT");
            $players->sendMessage(TF::GREEN . "Envoys are being spawned in the warzone!");
        }

        $envoyData = $this->envoys->getAll();
        foreach ($envoyData as $data => $world) {
            $data = explode(":", $data);
            $tile = $this->getServer()->getWorldManager()->getWorldByName($world)->getTile(new Vector3(intval($data[0]), intval($data[1]), intval($data[2])));
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

    public function setEnvoy(Player $sender) {
        $position = $sender->getPosition();
        $coords = floor($position->x) . ":" . floor($position->y) . ":" . floor($position->z);
        $worldName = $sender->getWorld()->getFolderName();

        $envoyData = $this->envoys->getAll();
        $envoyData[$coords] = $worldName;
        $this->envoys->setAll($envoyData);
        $this->envoys->save();

        $itemsList = $this->items->get("Items");

        if (is_array($itemsList)) {
            $itemString = $itemsList[array_rand($itemsList)];
            $itemObj = StringToItemParser::getInstance()->parse($itemString);

            if ($itemObj instanceof \pocketmine\item\Item) {
                $world = $sender->getWorld();
                $nbt = CompoundTag::create()
                ->setTag("Items", new ListTag([]))
                ->setString("id", "Chest")
                ->setInt("x", floor($position->x))
                ->setInt("y", floor($position->y))
                ->setInt("z", floor($position->z));
                $chest = new \pocketmine\block\tile\Chest($world, $nbt);
                $world->setBlock($position->asVector3(), $chest);
                $nbt = CompoundTag::create()
                    ->setTag("Items", new ListTag([]))
                    ->setString("id", "Chest")
                    ->setInt("x", floor($position->x))
                    ->setInt("y", floor($position->y))
                    ->setInt("z", floor($position->z));
                $chest = new \pocketmine\block\tile\Chest($sender->getWorld(), $nbt);
                $world->addTile($chest);
                $inv = $chest->getRealInventory();
                $inv->addItem($itemObj);
                $sender->sendMessage(TF::GREEN . "Envoy set at $coords in world $worldName!");
                return true;
            }
        }
        return false;
    }
}
