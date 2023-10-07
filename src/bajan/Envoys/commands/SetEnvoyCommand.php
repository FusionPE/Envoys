<?php

declare(strict_types=1);

namespace bajan\Envoys\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;
use pocketmine\math\Vector3;
use pocketmine\block\tile\Chest;
use pocketmine\item\StringToItemParser;
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
        $coords = new Vector3(floor($position->x), floor($position->y), floor($position->z));
        $worldName = $sender->getWorld()->getFolderName();

        $envoyData = $this->plugin->getEnvoysConfig()->getAll();
        $envoyData[] = [
            "coords" => $coords,
            "world" => $worldName
        ];
        $this->plugin->getEnvoysConfig()->setAll($envoyData);
        $this->plugin->getEnvoysConfig()->save();

        $itemsList = $this->plugin->getItemsConfig()->get("Items");

        if (is_array($itemsList)) {
            $itemString = $itemsList[array_rand($itemsList)];
            $itemObj = StringToItemParser::getInstance()->parse($itemString);

            if ($itemObj instanceof Item) {
                $world = $sender->getWorld();
                $nbt = $world->getTileFactory()->createBaseNBT($coords);
                $chest = Chest::createTile($world, $nbt);
                $world->addTile($chest);
                $inv = $chest->getInventory();
                $inv->addItem($itemObj);

                $sender->sendMessage(TF::GREEN . "Envoy set at $coords in world $worldName!");
            }
        }

        return true;
    }
}
