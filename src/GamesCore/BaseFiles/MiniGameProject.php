<?php
namespace GamesCore\BaseFiles;

use Core\InternalAPI\CoreInstance;
use GamesCore\Loader;
use pocketmine\block\Air;
use pocketmine\level\format\FullChunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;

abstract class MiniGameProject extends CoreInstance{
    /** @var Loader */
    private $core;

    public function onLoad(){
        $this->getCore()->registerMiniGame($this);

        if(is_dir($dir = $this->getDataFolder() . "worlds" . DIRECTORY_SEPARATOR)){
            $this->getCore()->getCore()->recursiveDirectoryCleaner($dir, true);
        }
        $this->saveResource("worlds.zip", true);
        if(file_exists($zip = $this->getDataFolder() . "worlds.zip")){
            $this->getCore()->getCore()->unzip($zip, $this->getDataFolder());
        }
        var_dump($this->updaterName());
    }

    public function onDisable(){
        if(is_dir($dir = $this->getDataFolder() . "worlds" . DIRECTORY_SEPARATOR)){
            $this->getCore()->getCore()->recursiveDirectoryCleaner($dir, true);
        }
        parent::onDisable();
    }

    /**
     * @return Loader
     */
    public function getCore(){
        if($this->core === null){
            $this->core = $this->getServer()->getPluginManager()->getPlugin("GamesCore");
        }
        return $this->core;
    }

    /**
     * @param Loader $gamesCore
     * @param Level $level
     * @param Sign $sign
     * @return BaseMiniGame
     */
    public abstract function generateMiniGame(Loader $gamesCore, Level $level, Sign $sign);

    /** @var array */
    private $spawnPoints = [];

    /**
     * @param Level $level
     * @return Sign[]
     */
    public final function getWorldSpawnPoints(Level $level){
        if(!isset($this->spawnPoints[$level->getId()]) || count($this->spawnPoints[$level->getId()]) < 2 || !isset($this->spawnPoints[$level->getId()]["spawn"]) || count($this->spawnPoints[$level->getId()]["spawn"]) < 1){
            $this->spawnPoints[$level->getId()] = [];
            $tiles = $level->getTiles();
            if(!empty($tiles)){
                foreach($tiles as $tile){
                    if($tile instanceof Sign){
                        if($tile->getText()[0] === "[spawn]"){
                            $pos = $tile->floor();
                            $this->spawnPoints[$level->getId()]["spawn"][] = $pos;
                            if($tile->getText()[1] === "respawn"){
                                $this->spawnPoints[$level->getId()]["respawn"][] = $pos; // Just to be sure that we can use a single position as both "Spawn" and "Re-Spawn"
                            }
                            $level->setBlock($tile, new Air(), true, false);
                        }
                    }
                }
            } else { //FOR TESTING, IN GAMES SHOULD NEVER BE USED(Or no?)
                $Spawn = $level->getSpawnLocation();
                echo $level->getId(). "\n";
                $this->spawnPoints[$level->getId()]["spawn"][] = new Vector3($Spawn->getX(), $Spawn->getY(), $Spawn->getZ());
                $this->spawnPoints[$level->getId()]["respawn"][] = new Vector3($Spawn->getX(), $Spawn->getY(), $Spawn->getZ());
            }
        }
        return $this->spawnPoints[$level->getId()]["spawn"];
    }

    /**
     * @param FullChunk $chunk
     */
    public function registerChunkSigns(FullChunk $chunk){
        foreach($chunk->getTiles() as $tile){
            if($tile instanceof Sign){
                $cTile = clone $tile;
                if($tile->getText()[0] === "[spawn]"){
                    $this->spawnPoints[$tile->getLevel()->getId()]["spawn"][] = $cTile;
                }
                if($tile->getText()[1] === "respawn"){
                    $this->spawnPoints[$tile->getLevel()->getId()]["respawn"][] = $cTile; // Just to be sure that we can use a single position as both "Spawn" and "Re-Spawn"
                }
                $tile->getLevel()->setBlock($tile, new Air(), true, false);
            }
        }
    }

    /**
     * @param Level $level
     * @return Sign[]
     */
    public final function getWorldRespawnPoints(Level $level){
        if(!isset($this->spawnPoints[$level->getId()]) || count($this->spawnPoints[$level->getId()]) < 2){
            $this->spawnPoints[$level->getId()] = [];
            foreach($level->getTiles() as $tile){
                if($tile instanceof Sign){
                    if($tile->getText()[0] === "[respawn]"){
                        $this->spawnPoints[$level->getId()]["respawn"][] = clone $tile;
                        $level->setBlock($tile, new Air(), true, false);
                    }
                }
            }
        }
        return $this->spawnPoints[$level->getId()]["respawn"];
    }
}