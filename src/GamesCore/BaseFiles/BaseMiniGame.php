<?php
namespace GamesCore\BaseFiles;

use GamesCore\GamesPlayer;
use GamesCore\Loader;
use GamesCore\Timers\GameEnd;
use GamesCore\Timers\GameStart;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\level\Level;
use pocketmine\scheduler\TaskHandler;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

abstract class BaseMiniGame{
    /** @var Loader */
    private $core;
    /** @var MiniGameProject */
    private $plugin;
    /** @var Level */
    private $level;
    /** @var null|Sign */
    private $arenaSign = null;
    /** @var int */
    private $maxPlayers;
    /** @var int */
    private $minPlayers;
    /** @var int */
    private $gameTime;
    /** @var int */
    private $currentRound = 1;
    /** @var int */
    private $rounds;
    /** @var bool */
    private $suddenDead;
    /** @var string */
    private $endMessage;
    /** @var bool */
    private $hasStarted = false;
    /** @var null|TaskHandler */
    private $task = null;

    public function __construct(Loader $core, MiniGameProject $plugin, Level $level, Sign $arenaSign, $maxPlayers, $minPlayers, $gameTimeInMinutes, $rounds = 1, $suddenDead, $gameEndMessage = "to end this game"){
        $this->core = $core;
        $this->plugin = $plugin;
        $this->level = $level;
        $this->arenaSign = $arenaSign;
        $this->maxPlayers = $maxPlayers;
        $this->minPlayers = $minPlayers;
        $this->gameTime = $gameTimeInMinutes * 60;
        $this->rounds = $rounds;
        $this->suddenDead = $suddenDead;
        $this->endMessage = $gameEndMessage;
    }

    /**
     * @return Loader
     */
    public final function getCore(){
        return $this->core;
    }

    /**
     * Not final so mini-games can add a special "Return Tag" to it's own plugin API ;)
     *
     * @return MiniGameProject
     */
    public function getPlugin(){
        return $this->plugin;
    }

    /**
     * @return null|Sign
     */
    public final function getSign(){
        return $this->arenaSign;
    }

    /**
     * @return int
     */
    public final function getMaxPlayers(){
        return $this->maxPlayers;
    }

    /**
     * @return int
     */
    public final function getMinPlayers(){
        return $this->minPlayers;
    }

    /**
     * @return int
     */
    public final function getGameTime(){
        return $this->gameTime;
    }

    /**
     * @return null|TaskHandler
     */
    public final function getCountdownTask(){
        return $this->task;
    }

    /**
     * @return int
     */
    public final function getRoundsNumber(){
        return $this->rounds;
    }

    /**
     * @return int
     */
    public final function getCurrentRound(){
        return $this->currentRound;
    }

    /**
     * @return bool
     */
    public final function hasSuddenDead(){
        return $this->suddenDead;
    }

    public final function startGame(){
        if(!$this->hasStarted()){
            if($this->getCountdownTask() !== null){
                $this->getCore()->getServer()->getScheduler()->cancelTask($this->getCountdownTask()->getTaskId());
            }
            $this->addSpawnPoints($this->getPlugin()->getWorldSpawnPoints($this->getLevel()));
            $this->addRespawnPoints($this->getPlugin()->getWorldSpawnPoints($this->getLevel()));
            $this->getSign()->setText(($color = TextFormat::RED . TextFormat::ITALIC) . "[Running]", "", TextFormat::LIGHT_PURPLE . preg_replace("/[0-9]+/", "", $this->getLevel()->getName()));
            $this->onGameStart();
            $this->hasStarted = true;
            $this->getCore()->getServer()->getScheduler()->scheduleRepeatingTask(new GameEnd($this->getCore(), $this, $this->gameTime, $this->endMessage), 1); // Executed each tick...
        }
    }

    public abstract function onGameStart();

    //////////////////////////////////////////////////////////////////
    //  The following functions are called with the "GameEnd" task  //
    //////////////////////////////////////////////////////////////////

    /**
     * This function will allow games to make things while jumping players to a new round...
     * Well, if there are more than 1 round xD
     * If not, the "onSuddenDead" function will be called (See function's comment)
     */
    public function onNextRound(){}

    /**
     * How will this work?
     * Lets take an example of a game that needs a winner, like Survival Games...
     * When "onSuddenDead" is called, you can teleport all the players to an arena where they will fight until just 1 person is alive,
     * you can detect it with an event, and after it, call the "endGame" function to end normally :3
     */
    public function onSuddenDead(){}

    /**
     * @param bool $force
     * @param bool $shutDown
     */
    public final function endGame($force = false, $shutDown = false){
        if(!$force){
            $this->onGameEnd();
        }
        foreach($this->getAllSessions() as $p){
            if($shutDown){
                $p->getPlayer()->kick();
            }else{
                $p->onGameEnd();
            }
        }
        $this->getCore()->removeGame($this);
        if(!$shutDown){
            $this->getCore()->addNewGame($this->getCore()->generateBasedGame($this));
        }
    }

    //////////////////////////////////////////////////////////////////

    public abstract function onGameEnd();

    /**
     * @return bool
     */
    public final function hasStarted(){
        return $this->hasStarted;
    }

    /**   _____              _
     *   / ____|            (_)
     *  | (___   ___ ___ ___ _  ___  _ __  ___
     *   \___ \ / _ / __/ __| |/ _ \| '_ \/ __|
     *   ____) |  __\__ \__ | | (_) | | | \__ \
     *  |_____/ \___|___|___|_|\___/|_| |_|___/
     */

    private $sessions = [];

    /**
     * @param GamesPlayer $player
     * @return BaseSession
     */
    public abstract function generateSession(GamesPlayer $player);

    /**
     * @param GamesPlayer $player
     * @return bool|BaseSession
     */
    public function getSession(GamesPlayer $player){
        if(!isset($this->sessions[$spl = spl_object_hash($player)])){
            if(isset($this->players[$spl])){
                $this->sessions[$spl] = $this->generateSession($player);
            }else{
                return false;
            }
        }
        return $this->sessions[$spl];
    }

    /**
     * @return BaseSession[]
     */
    public function getAllSessions(){
        return array_values($this->sessions);
    }

    /**  _____  _
     *  |  __ \| |
     *  | |__) | | __ _ _   _  ___ _ __ ___
     *  |  ___/| |/ _` | | | |/ _ | '__/ __|
     *  | |    | | (_| | |_| |  __| |  \__ \
     *  |_|    |_|\__,_|\__, |\___|_|  |___/
     *                   __/ |
     *                  |___/
     */

    /** @var GamesPlayer[] */
    private $players = [];

    /**
     * @param GamesPlayer $player
     * @return bool
     */
    public final function addPlayer(GamesPlayer $player){
        // TODO: 'Waiting for players' task

        if($this->hasStarted() || count($this->getAllPlayers()) + 1 > $this->getMaxPlayers()){
            $player->sendMessage("%games.full");
            return false;
        }
        $player->teleport($this->getLevel()->getSpawnLocation());
        $player->sendMessage("%games.join");

        $spl = spl_object_hash($player);
        $this->players[$spl] = $player;
        $this->sessions[$spl] = $this->generateSession($player);;

        if(count($this->getAllPlayers()) === $this->getMinPlayers()){
            $this->task = $this->getCore()->getServer()->getScheduler()->scheduleRepeatingTask(new GameStart($this->getCore(), $this), 1); // Executed each tick...;
        }elseif(count($this->getAllPlayers()) === $this->getMaxPlayers() && !$this->hasStarted()){ // Just to be sure... double check if the game haven't started xD
            $this->startGame();
        }
        foreach($this->getAllPlayers() as $p){
            $p->showPlayer($player);
            $player->showPlayer($p);
        }
        $this->onPlayerJoin($player);
        return true;
    }

    /**
     * This function should teleport the player to the arena, set custom things, etc...
     *
     * @param GamesPlayer $player
     */
    public abstract function onPlayerJoin(GamesPlayer $player);

    /**
     * @param GamesPlayer $player
     */
    public final function removePlayer(GamesPlayer $player){
        $spl = spl_object_hash($player);
        if(isset($this->players[$spl])){
            unset($this->players[$spl]);
        }
        if(isset($this->sessions[$spl])){
            unset($this->sessions[$spl]);
        }
    }

    /**
     * @return GamesPlayer[]
     */
    public function getAllPlayers(){
        return array_values($this->players);
    }

    /**
     * @param string $message
     */
    public final function broadcastMessage($message){
        foreach($this->getAllPlayers() as $p){
            $p->sendMessage($message);
        }
    }

    /**  _                    _
     *  | |                  | |
     *  | |     _____   _____| |
     *  | |    / _ \ \ / / _ | |
     *  | |___|  __/\ V |  __| |
     *  |______\___| \_/ \___|_|
     */

    /**
     * @return Level
     */
    public final function getLevel(){
        return $this->level;
    }

    /** @var array */
    private $spawnPoints = [];

    /**
     * @return Sign[]
     */
    public final function getSpawnPoints(){
        return $this->spawnPoints;
    }

    /** @var int */
    private $nextSpawnPoint = 0;

    /**
     * @param bool $unset
     * @return null|Sign
     */
    public final function getNextSpawnPoint($unset = false){
        if(isset($this->getSpawnPoints()[$this->nextSpawnPoint])){
            $sp = $this->getSpawnPoints()[$this->nextSpawnPoint];
            if($unset){
                unset($this->getSpawnPoints()[$this->nextSpawnPoint]);
            }
            $this->nextSpawnPoint++;
            return $sp;
        }
        return null;
    }

    /**
     * @return Sign
     */
    public final function getRandomSpawnPoint(){
        return $this->spawnPoints[array_rand($this->spawnPoints)];
    }

    /**
     * @param Sign[] $spawnPoints
     */
    public final function addSpawnPoints(array $spawnPoints){
        $this->spawnPoints = $spawnPoints;
    }

    /** @var Sign[] */
    private $respawnPoints = [];

    /**
     * @return Sign[]
     */
    public final function getRespawnPoints(){
        return $this->respawnPoints;
    }

    /** @var int */
    private $nextRespawnPoint = 0;

    /**
     * @param bool $unset
     * @return null|Sign
     */
    public final function getNextRespawnPoint($unset = false){
        if(isset($this->getRespawnPoints()[$this->nextRespawnPoint])){
            $sp = $this->getRespawnPoints()[$this->nextRespawnPoint];
            if($unset){
                unset($this->getRespawnPoints()[$this->nextRespawnPoint]);
            }
            $this->nextRespawnPoint++;
            return $sp;
        }
        return null;
    }

    /**
     * @return Sign
     */
    public final function getRandomRespawnPoint(){
        return $this->respawnPoints[array_rand($this->respawnPoints)];
    }

    /**
     * @param Sign[] $respawnPoints
     */
    public final function addRespawnPoints(array $respawnPoints){
        $this->respawnPoints = $respawnPoints;
    }

    /**  ______               _
     *  |  ____|             | |
     *  | |____   _____ _ __ | |_ ___
     *  |  __\ \ / / _ | '_ \| __/ __|
     *  | |___\ V |  __| | | | |_\__ \
     *  |______\_/ \___|_| |_|\__|___/
     */

    /**
     * @param PlayerInteractEvent $event
     */
    public function  onPlayerInteract(PlayerInteractEvent $event){}

    /**
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event){}

    /**
     * @param EntityMotionEvent $event
     */
    public function onPlayerMotionChange(EntityMotionEvent $event){}

    /**
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event){}

    /**
     * @param PlayerItemConsumeEvent $event
     */
    public function onItemConsume(PlayerItemConsumeEvent $event){}

    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event){}

    /**
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event){}

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event){}

    /**
     * @param PlayerToggleSneakEvent $event
     */
    public function onPlayerSneak(PlayerToggleSneakEvent $event){}
}