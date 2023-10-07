<?php

namespace klaus\Envoys;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as TF;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\block\tile\Tile;

class Main extends PluginBase implements Listener{

	//minutes
	public $spawntime = 5;

	public function onEnable():void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new EnvoyTask($this), $this->spawntime*60*20);
		@mkdir($this->getDataFolder());
		$this->saveResource("Config.yml");
		$this->saveResource("Envoys.yml");
		$this->saveResource("Items.yml");
		$this->cfg = new Config($this->getDataFolder()."Config.yml",Config::YAML);
		$this->envoys = new Config($this->getDataFolder()."Envoys.yml",Config::YAML);
		$this->items = new Config($this->getDataFolder()."Items.yml",Config::YAML);
	}

	public function runEnvoyEvent(){
		foreach($this->getServer()->getOnlinePlayers() as $players){
			$players->sendMessage(TF::AQUA."WORLD EVENT");
			$players->sendMessage(TF::GREEN."Envoys are being spawned in the warzone!");
		}
		foreach($this->envoys as $data => $world){
			$data = explode(":",$data);
			$tile = $this->getServer()->getWorldManager()->getWorldByName($world)->getTile(new Vector3(intval($data[0]), intval($data[1]), intval($data[2])));
			$i = rand(3,5);
			while($i > 0){
				$item = $this->items[array_rand($this->items)];
				$item = explode(":",$item);
				$tile->getInventory()->addItem(Item::get($item[0],$item[1],$item[2]));
				$i--;
			}
		}
	}

	public function setEnvoy(Player $sender){
		$this->envoys->set($sender->x.":".$sender->y.":".$sender->z, $sender->getLevel()->getName());
		$this->envoys->save();
		$items = $this->items->get("Items");
		$item = $items[array_rand($items)];
		$values = explode(":", $item);
		$world = $sender->getWorld();
		$world->setBlock($sender->getPosition()->asVector3(), Block::get(54));
		$nbt = new CompoundTag(" ", [
			new ListTag("Items", []),
			new StringTag("id", Tile::CHEST),
			new IntTag("x", $sender->x),
			new IntTag("y", $sender->y),
			new IntTag("z", $sender->z)
		]);
		$chest = Tile::createTile("Chest", $sender->getLevel(), $nbt);
		$level->addTile($chest);
		$inv = $chest->getInventory();
		$inv->addItem(Item::get($values[0], $values[1]));
		$sender->sendMessage(TF::GREEN."Envoy set!");
		return true;
	}

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
		switch($cmd){
			case "setenvoy":
				if(!$sender->hasPermission("envoy.set")) {$sender->sendMessage(TF::RED."You do not have the required permission"); return false;}
				$this->setEnvoy($sender);
				return true;
		}
	}
}
