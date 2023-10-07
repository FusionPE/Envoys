<?php

declare(strict_types=1);

namespace bajan\Envoys;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\block\Block;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat as TF;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\WorldManager;
use pocketmine\item\StringToItemParser;

class Main extends PluginBase implements Listener {

    // minutes
    public $spawntime = 60;

    /** @var Config */
    private $envoys;

    /** @var Config */
    private $items;

    public function onEnable(): void {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $this->spawntime * 60 * 20);
        @mkdir($this->getDataFolder());
        $this->saveResource("Envoys.yml");
        $this->saveResource("Items.yml");
        $this->envoys = new Config($this->getDataFolder() . "Envoys.yml", Config::YAML);
        $this->items = new Config($this->getDataFolder() . "Items.yml", Config::YAML);
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
        $this->envoys->set(floor($position->x) . ":" . floor($position->y) . ":" . floor($position->z), $sender->getWorld()->getFolderName());
        $this->envoys->save();
        $itemsList = $this->items->get("Items");

        if (is_array($itemsList)) {
            $itemString = $itemsList[array_rand($itemsList)];
            $itemObj = StringToItemParser::getInstance()->parse($itemString);

            if ($itemObj instanceof \pocketmine\item\Item) {
                $world = $sender->getWorld();
                $world->setBlock($position->asVector3(), Block::getId(BlockIds::CHEST));
                $nbt = CompoundTag::create()
                    ->setTag(new ListTag("Items", []))
                    ->setString("id", "Chest")
                    ->setInt("x", floor($position->x))
                    ->setInt("y", floor($position->y))
                    ->setInt("z", floor($position->z));
                $chest = Tile::createTile("Chest", $sender->getWorld(), $nbt);
                $world->addTile($chest);
                $inv = $chest->getInventory();
                $inv->addItem($itemObj);
                $sender->sendMessage(TF::GREEN . "Envoy set!");
                return true;
            }
        }
        return false;
    }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {
        switch ($cmd) {
            case "setenvoy":
                if (!$sender->hasPermission("envoy.set")) {
                    $sender->sendMessage(TF::RED . "You do not have the required permission");
                    return false;
                }
                $this->setEnvoy($sender);
                return true;
        }
        return false;
    }
}
