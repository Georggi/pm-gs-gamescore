<?php
namespace GamesCore;

use Core\InternalAPI\SuperPlayer;
use pocketmine\network\SourceInterface;

class GamesPlayer extends SuperPlayer{
    public function __construct(SourceInterface $interface, $clientID, $ip, $port){
        parent::__construct($interface, $clientID, $ip, $port);
    }

    /**
     * @return GamesSession
     */
    public function getSession(){
        return parent::getSession();
    }
}