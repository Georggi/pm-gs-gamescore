<?php
namespace GamesCore\Timers;

use GamesCore\BaseFiles\BaseMiniGame;
use Core\BaseFiles\BaseTask;
use GamesCore\Loader;

class GameWaitForPlayers extends BaseTask{
    /** @var BaseMiniGame */
    private $game;
    /** @var int */
    private $counter = 6000;
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
            if($this->counter % 120 === 0){
                $this->time = $this->counter / 120;
                $this->timeTag = "minute" . ($this->time > 1 ? "s" : "");
            }elseif($this->counter === 600 || $this->counter <= 200){
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