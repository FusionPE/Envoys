<?php

namespace bajan\Envoys\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\world\World;
use pocketmine\block\BlockFactory;
use pocketmine\block\tile\Chest;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\item\Item;
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

        $position = $sender->getPosition();
        $coords = floor($position->x) . ":" . floor($position->y) . ":" . floor($position->z);
        $worldName = $sender->getWorld()->getFolderName();

        $envoyData = $this->plugin->getEnvoysConfig()->getAll();
        $envoyData[] = [
            "coords" => $coords,
            "world" => $worldName,
        ];
        $this->plugin->getEnvoysConfig()->setAll($envoyData);
        $this->plugin->getEnvoysConfig()->save();

        $itemsList = $this->plugin->getItemsConfig()->get("Items");

        if (is_array($itemsList)) {
            $itemString = $itemsList[array_rand($itemsList)];
            $itemObj = Item::fromString($itemString);

            if ($itemObj instanceof Item) {
                $world = $sender->getWorld();
                $nbt = CompoundTag::create()
                    ->setTag("Items", new ListTag())
                    ->setString("id", "Chest")
                    ->setInt("x", floor($position->x))
                    ->setInt("y", floor($position->y))
                    ->setInt("z", floor($position->z));
                $chest = new Chest($world, $nbt);
                $world->setBlock($position->asVector3(), $chest);
                $nbt = CompoundTag::create()
                    ->setTag("Items", new ListTag())
                    ->setString("id", "Chest")
                    ->setInt("x", floor($position->x))
                    ->setInt("y", floor($position->y))
                    ->setInt("z", floor($position->z));
                $chest = new Chest($world, $nbt);
                $world->addTile($chest);
                $inv = $chest->getInventory();
                $inv->addItem($itemObj);

                $sender->sendMessage(TF::GREEN . "Envoy set at $coords in world $worldName!");
            } else {
                $sender->sendMessage(TF::RED . "Error setting envoy.");
            }
        } else {
            $sender->sendMessage(TF::RED . "Error setting envoy.");
        }

        return true;
    }
}
