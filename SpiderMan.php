<?php
/**
 * @name SpiderMan
 * @author alvin0319
 * @main alvin0319\SpiderMan
 * @version 1.0.0
 * @api 4.0.0
 */
namespace alvin0319;

/**
 * 이 플러그인은 EconomyAPI 플러그인이 사용되므로 EconomyAPI 플러그인이 없을시 작동되지 않습니다.
 */
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\PluginCommand;

class SpiderMan extends PluginBase implements Listener {
    public function onEnable() : void{
        //$this->getServer()->getPluginManager()->registerEvent($this, $this);
        @mkdir ($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, [
            "money" => "100000",
            "plugin-prefix" => "§b§l[ §f벽타기 §b] §f",
            "climb-time" => "60",
            "cmd" => "climb"
        ]);
        $this->db = $this->config->getAll();
        $this->money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $this->cmd = new PluginCommand($this->db["cmd"], $this);
        $this->cmd->setDescription("A ClimbCommand");
        $this->getServer()->getCommandMap()->register ($this->db["cmd"], $this->cmd);
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if ($command->getName() === $this->db["cmd"]) {
            if (! isset ($args[0])) {
                $sender->sendMessage($this->db["plugin-prefix"] . "/" . $this->db["cmd"] . " 실행");
                return true;
            }
            if ($args[0] === "실행") {
                if (! $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")) {
                    $sender->sendMessage($this->db["plugin-prefix"] . "EconomyAPI 플러그인이 없습니다");
                    return true;
                }
                if ($this->money->myMoney($sender) < $this->db["money"]) {
                    $sender->sendMessage($this->db["plugin-prefix"] . "돈이 부족합니다. 필요한 금액: " . $this->db["money"] . "원");
                    return true;
                }
                $this->money->reduceMoney($sender, $this->db["money"]);
                $sender->setCanClimbWalls(true);
                $sender->sendMessage($this->db["plugin-prefix"] . "벽타기가 활성화되었습니다");
                $this->getScheduler()->scheduleDelayedTask(new class ($this, $sender) extends Task {
                   private $owner, $player;
                   
                   public function __construct(SpiderMan $owner, Player $player) {
                       $this->owner = $owner;
                       $this->player = $player;
                   }
                   public function onRun(int $currentTick) {
                       $this->player->setCanClimbWalls(false);
                       $this->player->sendMessage ($this->owner->db["plugin-prefix"] . "벽타기 시간이 끝났습니다");
                   }
                }, $this->db["climb-time"] * 20);
            }
        }
        return true;
    }
}