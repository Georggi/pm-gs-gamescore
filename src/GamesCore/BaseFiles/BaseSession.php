<?php
namespace GamesCore\BaseFiles;

use Core\BaseFiles\BaseSession as CoreSession;
use Core\InternalAPI\SuperPlayer;

class BaseSession extends CoreSession{
    /** @var BaseMiniGame */
    private $game;

    public function __construct(SuperPlayer $player, BaseMiniGame $game){
        parent::__construct($player);
        $this->game = $game;
    }

    /**
     * @return SuperPlayer
     */
    public final function getPlayer(){
        return parent::getPlayer();
    }

    /**
     * @return BaseMiniGame
     */
    public function getGame(){
        return $this->game;
    }

    /** @var bool */
    private $isActive;

    /**
     * @return bool
     */
    public final function isActive(){
        return $this->isActive;
    }

    /**
     * @param bool $bool
     */
    public final function setActive($bool){
        $this->isActive = $bool;
    }

    /**
     * @param bool $spectate
     */
    public function deactivatePlayer($spectate = true){
        $this->setActive(false);
        if($spectate){
            $this->getPlayer()->setGamemode(SuperPlayer::SPECTATOR);
        }
        // TODO: Inventory switch
    }

    public function onGameEnd(){
        $this->getPlayer()->teleport($this->getPlayer()->getServer()->getDefaultLevel()->getSpawnLocation(), 0, 0);
    }
}