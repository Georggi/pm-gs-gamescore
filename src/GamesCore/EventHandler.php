<?php
namespace GamesCore;

use Core\InternalAPI\Events\SuperPlayerCreateEvent;
use pocketmine\block\SignPost;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\level\ChunkUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class EventHandler implements Listener{
    private $plugin;

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerCreationEvent $event
     *
     * @priority HIGH
     */
    public function onPlayerCreate(PlayerCreationEvent $event){
        $event->setPlayerClass(GamesPlayer::class);
    }

    /**
     * @param SuperPlayerCreateEvent $event
     *
     * @priority HIGHEST
     */
    public function onCoreSessionCreate(SuperPlayerCreateEvent $event){
        $event->setCore($this->plugin);
        $event->setSessionClass(GamesSession::class);
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        $event->setJoinMessage("");
        $this->plugin->initGames();
    }

    /**
     * @param ChunkLoadEvent $event
     *
     * @ignoreCancelled true
     */
    public function onChunkLoad(ChunkLoadEvent $event){
        if($event->getLevel()->getId() === $this->plugin->getServer()->getDefaultLevel()->getId()){
            $this->plugin->registerSigns($event->getChunk());
        }else{
            if(($game = $this->plugin->getGame($event->getLevel())) !== false){
                $game->getPlugin()->registerChunkSigns($event->getChunk());
            }
        }
    }

    public function onChunkUnload(ChunkUnloadEvent $event){
        foreach($event->getChunk()->getTiles() as $tile){
            if($tile instanceof Sign && (
                strtolower($tile->getText()[0] === TextFormat::GOLD . TextFormat::ITALIC . "[Join]") ||
                strtolower($tile->getText()[0] === TextFormat::GREEN . TextFormat::BOLD . "[Join]")) &&
            $this->plugin->getServer) {

            }
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event){
        /** @var GamesPlayer $player */
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if($player->getSession()->isInGame()){
            $player->getSession()->getGame()->onPlayerInteract($event);
        }elseif($event->getItem()->getId() === Item::DYE && ($event->getItem()->getDamage() === 10 || $event->getItem()->getDamage() === 8)){
            $this->plugin->getCore()->switchMagicClock($player, $event->getItem());
        }elseif($block instanceof SignPost){
            /** @var Sign $sign */
            $sign = $block->getLevel()->getTile($block);
            if(!($game = $this->plugin->getGameBySign($sign))){
                $player->sendMessage("%games.notfound");
            }else{
                $game->addPlayer($player);
            }
        }
    }

    /**
     * @param PlayerMoveEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerMove(PlayerMoveEvent $event){
        /** @var GamesPlayer $player */
        $player = $event->getPlayer();
        if($player->getSession()->isInGame()){
            $player->getSession()->getGame()->onPlayerMove($event);
        }
    }

    /**
     * @param EntityMotionEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onEntityMotion(EntityMotionEvent $event){
        $player = $event->getEntity();
        if($player instanceof GamesPlayer){
            if($player->getSession()->isInGame()){
                $player->getSession()->getGame()->onPlayerMotionChange($event);
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onEntityDamage(EntityDamageEvent $event){
        $player = $event->getEntity();
        if($player instanceof GamesPlayer){
            if($player->getSession()->isInGame()){
                $player->getSession()->getGame()->onEntityDamage($event);
            }
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onItemConsume(PlayerItemConsumeEvent $event){
        /** @var GamesPlayer $player */
        $player = $event->getPlayer();
        if($player->getSession()->isInGame()){
            $player->getSession()->getGame()->onItemConsume($event);
        }
    }

    /**
     * @param PlayerDeathEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerDeath(PlayerDeathEvent $event){
        /** @var GamesPlayer $player */
        $player = $event->getEntity();
        if($player->getSession()->isInGame()){
            $player->getSession()->getGame()->onPlayerDeath($event);
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event){
        /** @var GamesPlayer $player */
        $player = $event->getPlayer();
        if($player->getSession()->isInGame()){
            $player->getSession()->getGame()->onPlayerRespawn($event);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
        /** @var GamesPlayer $player */
        $player = $event->getPlayer();
        if($player->getSession()->isInGame()){
            $player->getSession()->getGame()->onPlayerQuit($event);
        }
    }

    /**
     * @param PlayerToggleSneakEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerSneak(PlayerToggleSneakEvent $event){
        /** @var GamesPlayer $player */
        $player = $event->getPlayer();
        if($player->getSession()->isInGame()){
            $player->getSession()->getGame()->onPlayerSneak($event);
        }
    }
}