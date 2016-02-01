<?php
namespace GamesCore;

use Core\InternalAPI\CoreInstance;
use GamesCore\BaseFiles\BaseMiniGame;
use GamesCore\BaseFiles\MiniGameProject;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;

class Loader extends CoreInstance{

    public function onLoad(){
        $this->getCore()->setNotHub();
    }

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
    }

    public function onDisable(){
        $this->forceGamesClose(true);
        parent::onDisable();
    }

    /** .----------------. .----------------. .----------------.
     * | .--------------. | .--------------. | .--------------. |
     * | |      __      | | |   ______     | | |     _____    | |
     * | |     /  \     | | |  |_   __ \   | | |    |_   _|   | |
     * | |    / /\ \    | | |    | |__) |  | | |      | |     | |
     * | |   / ____ \   | | |    |  ___/   | | |      | |     | |
     * | | _/ /    \ \_ | | |   _| |_      | | |     _| |_    | |
     * | ||____|  |____|| | |  |_____|     | | |    |_____|   | |
     * | |              | | |              | | |              | |
     * | '--------------' | '--------------' | '--------------' |
     *  '----------------' '----------------' '----------------'
     */

    /**
     * @return string
     */
    public function updaterName(){
        return "GamesCore";
    }

    /** @var Sign[]|null */
    private $gameSigns = null;

    public function registerAllAvailableSigns(){
        foreach($this->getServer()->getDefaultLevel()->getChunks() as $chunk){
            if($chunk->isLoaded()){
                $this->registerSigns($chunk);
            }
        }
    }

    /**
     * @param FullChunk $chunk
     */
    public function registerSigns(FullChunk $chunk){
        $signs = [];
        foreach($chunk->getTiles() as $tile){
            if($tile->getLevel()->getId() !== $this->getServer()->getDefaultLevel()->getId()){
                continue;
            }
            if($tile instanceof Sign && strtolower($tile->getText()[0]) === "[match]"){
                $signs[] = $tile;
            }
        }
        if($this->gameSigns === null){
            if(count($signs) > 0){
                $this->gameSigns = $signs;
                echo "\n\n" . count($signs) . " were registered!\n\n";
            }
        }else{
            array_merge($this->gameSigns, $signs);
        }
        if(is_array($this->gameSigns)){
            array_unique($this->gameSigns);
        }
    }

    /**
     * @return null|Sign[]
     */
    public function getRegisteredSigns(){
        if($this->gameSigns === null){
            $this->registerAllAvailableSigns();
        }
        return $this->gameSigns;
    }

    /** @var MiniGameProject */
    private $miniGame;

    /**
     * @param MiniGameProject $miniGame
     */
    public function registerMiniGame(MiniGameProject $miniGame){
        $this->miniGame = $miniGame;
        // Import custom spawn...
        #$miniGame->saveResource("world.zip", true);
        #$this->unzip($miniGame->getDataFolder() . "world.zip", $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . "world" . DIRECTORY_SEPARATOR);
    }

    /**
     * @return MiniGameProject
     */
    public function getMiniGame(){
        return $this->miniGame;
    }

    /** @var bool  */
    private $gamesStarted = false;

    public function initGames(){
        if(!$this->gamesStarted && $this->getRegisteredSigns() !== null){
            foreach($this->getRegisteredSigns() as $sign){
                foreach(scandir($dir = $this->getMiniGame()->getDataFolder() . "worlds" . DIRECTORY_SEPARATOR) as $resource){
                    if(substr($resource, -4) === ".zip"){
                        $level = $this->generateNewLevel(str_replace(".zip", "", $resource), $dir . $resource);
                        $this->addNewGame($this->getMiniGame()->generateMiniGame($this, $level, $sign));
                        var_dump("Created arena: " . $level->getName());
                    }
                }
            }
            $this->gamesStarted = true;
        }
    }

    /** @var array */
    private $games = [];

    private $gamesBySign = [];

    /**
     * @param BaseMiniGame $game
     */
    public function addNewGame(BaseMiniGame $game){
        $this->getServer()->getPluginManager()->registerEvents($game, $this);
        $this->games[$game->getLevel()->getId()] = $game;
        $this->gamesBySign[$game->getSign()->getId()] =& $this->games[$game->getLevel()->getId()]; // Passed by reference :3
        $color = count($game->getAllPlayers()) >= $game->getMaxPlayers() ? TextFormat::GOLD . TextFormat::ITALIC : TextFormat::GREEN . TextFormat::BOLD;
        $game->getSign()->setText($color . "[Join] ", $color . count($game->getLevel()->getPlayers()) . TextFormat::RESET . TextFormat::AQUA . " / " . $color . $game->getMaxPlayers(), TextFormat::LIGHT_PURPLE . $game->getLevel()->getName());
    }

    /**
     * @param BaseMiniGame $base
     * @return BaseMiniGame
     */
    public function generateBasedGame(BaseMiniGame $base){
        $game = $this->getMiniGame()->generateMiniGame($this,
            $this->generateNewLevel($name = preg_replace("/[^0-9]+/", "", $base->getLevel()->getName()), $resource = $base->getPlugin()->getResource($name . ".zip")),
            $base->getSign()
        );
        fclose($resource);
        return $game;
    }

    /**
     * @param BaseMiniGame $game
     */
    public function removeGame(BaseMiniGame $game){
        if(isset($this->games[$game->getLevel()->getId()])){
            unset($this->games[$game->getLevel()->getId()]);
            unset($this->gamesBySign[$game->getSign()->getId()]);
            $dir = $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $game->getLevel()->getFolderName() . DIRECTORY_SEPARATOR;
            $this->getServer()->unloadLevel($game->getLevel(), false);
            $this->getCore()->recursiveDirectoryCleaner($dir, true);
        }
    }

    /**
     * @param bool $shutdown
     */
    private function forceGamesClose($shutdown = false){
        foreach($this->games as $id => $game){
            /** @var BaseMiniGame $game */
            $game->endGame(true, $shutdown);
            $game->getSign()->setText("[match]");
        }
        $this->getMiniGame()->setEnabled(false);
    }

    /**
     * @param Level $level
     * @return bool|BaseMiniGame
     */
    public function getGame(Level $level){
        if(!isset($this->games[$level->getId()])){
            return false;
        }
        return $this->games[$level->getId()];
    }

    /**
     * @param Sign $sign
     * @return bool|BaseMiniGame
     */
    public function getGameBySign(Sign $sign){
        if(isset($this->gamesBySign[$sign->getId()])){
            return $this->gamesBySign[$sign->getId()];
        }
        return false;
    }

    /**
     * @param string $name
     * @param string $zipDir
     * @return Level
     */
    public function generateNewLevel($name, $zipDir){
        $dir = $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR;
        $count = [];
        foreach(scandir($dir) as $world){
            if(($world !== "." && $world !== "..") && strpos($world, $name) !== false){
                $count[] = preg_replace("/[^0-9]+/", "", $world);
            }
        }
        $newName = $name . "-" . (count($count) > 0 ? max($count) + 1 : 1);
        mkdir($dir = $dir . $newName . DIRECTORY_SEPARATOR);
        $this->getCore()->unzip($zipDir, $dir);
        $this->getServer()->loadLevel($newName);
        return $this->getServer()->getLevelByName($newName);
    }
}