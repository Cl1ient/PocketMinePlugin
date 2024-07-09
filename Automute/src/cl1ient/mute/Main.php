<?php
namespace cl1ient\mute;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class Main extends PluginBase implements Listener {

    private $mutedPlayers = [];
    private $motInterdits = ["connard", "fdp"]; // Variable pour définir les mots interdits
    private $autoMuteEnabled = true; // Variable pour suivre l'état de l'auto mute

    public function onEnable(): void {
        $this->getLogger()->notice("Plugin activé");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(): void {
        $this->getLogger()->notice("Plugin désactivé");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        $commandName = $command->getName();
        if ($commandName === "automute") {
            if ($this->autoMuteEnabled) {
                $this->autoMuteEnabled = false;
                $sender->sendMessage(TextFormat::RED . "Auto mute désactivé.");
            } else {
                $this->autoMuteEnabled = true;
                $sender->sendMessage(TextFormat::GREEN . "Auto mute activé.");
            }
            return true;
        }
        return false;
    }

    public function onPlayerChat(PlayerChatEvent $event) {
        if (!$this->autoMuteEnabled) {
            return;
        }

        $player = $event->getPlayer();
        $message = $event->getMessage();


        if (isset($this->mutedPlayers[$player->getName()])) {
            $event->cancel();
            $player->sendMessage(TextFormat::RED . "Vous êtes mute. Veuillez attendre la fin de votre mute.");
            return;
        }

        foreach ($this->motInterdits as $mot) {
            if (stripos($message, $mot) !== false) {
                $this->mutePlayer($player);
                $event->cancel();
                $player->sendMessage(TextFormat::RED . "Vous avez été mute pendant 30 secondes pour avoir utilisé un mot interdit.");
                return;
            }
        }
    }

    private function mutePlayer(Player $player) {
        $this->mutedPlayers[$player->getName()] = time() + 30;
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player): void {
            unset($this->mutedPlayers[$player->getName()]);
            if ($player->isOnline()) {
                $player->sendMessage(TextFormat::GREEN . "Votre mute est terminé. Faites attention à vos paroles à l'avenir.");
            }
        }), 30 * 20);
    }
}
