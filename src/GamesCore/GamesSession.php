<?php
namespace GamesCore;

use GamesCore\BaseFiles\BaseMiniGame;
use Core\BaseFiles\BaseSession as CoreSession;

class GamesSession extends CoreSession{
    /** @var BaseMiniGame|null */
    private $game = null;

    /**
     * @return bool
     */
    public function isInGame(){
        return $this->game !== null;
    }

    /**
     * @return BaseMiniGame|null
     */
    public function getGame(){
        return $this->game;
    }

    /**
     * @param BaseMiniGame $game
     */
    public function setGame(BaseMiniGame $game = null){
        $this->game = $game;
    }
}