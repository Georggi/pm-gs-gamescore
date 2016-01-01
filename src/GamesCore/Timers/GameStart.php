<?php
namespace GamesCore\Timers;

use GamesCore\BaseFiles\BaseMiniGame;
use Core\BaseFiles\BaseTask;
use GamesCore\Loader;

class GameStart extends BaseTask{
    /** @var BaseMiniGame */
    private $game;
    /** @var int */
    private $counter = 600;
    /** @var int */
    private $time;
    /** @var string */
    private $timeTag;

    public function __construct(Loader $core, BaseMiniGame $game){
        parent::__construct($core);
        $this->game = $game;
    }

    public function onRun($currentTick){
        if($this->counter < 1){
            $this->game->startGame();
        }else{
            if($this->counter % 20 === 0){
                $this->time = $this->counter / 20;
                $this->timeTag = "second" . ($this->time > 1 ? "s" : "");
            }
            foreach($this->game->getAllPlayers() as $p){
                $p->sendTip("%games.start", [$this->time, "%games.time." . $this->timeTag]);
            }
            $this->counter--;
        }
    }
}