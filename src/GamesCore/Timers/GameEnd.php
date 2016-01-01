<?php
namespace GamesCore\Timers;

use GamesCore\BaseFiles\BaseMiniGame;
use Core\BaseFiles\BaseTask;
use GamesCore\Loader;

class GameEnd extends BaseTask{
    /** @var BaseMiniGame */
    private $game;
    /** @var int */
    private $counter;
    /** @var string */
    private $endMessage;
    /** @var int */
    private $time;
    /** @var string */
    private $timeTag;

    public function __construct(Loader $core, BaseMiniGame $game, $time, $endMessage){
        parent::__construct($core);
        $this->game = $game;
        $this->counter = $time * 20; // Counter set to "Ticks"
        $this->endMessage = $endMessage;
    }

    public function onRun($currentTick){
        if($this->counter < 20){
            if($this->game->getRoundsNumber() > 1){
                $this->game->onNextRound();
            }elseif($this->game->hasSuddenDead()){
                $this->game->onSuddenDead();
            }else{
                $this->game->endGame();
            }
        }
        if($this->counter % 120 === 0){
            $this->time = $this->counter / 120;
            $this->timeTag = "minute" . ($this->time === 1 ? "" : "s");
        }elseif($this->counter === 600 || $this->counter <= 200){
            $this->time = $this->counter / 20;
            $this->timeTag = "second" . ($this->time === 1 ? "" : "s");
        }
        foreach($this->game->getAllPlayers() as $p){
            $p->sendTip("%games.timer", [$this->time, "%games.timer." . $this->timeTag]);
        }
        $this->counter--;
    }
}