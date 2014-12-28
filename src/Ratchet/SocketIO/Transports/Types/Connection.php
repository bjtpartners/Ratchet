<?php
namespace Ratchet\SocketIO\Transports\Types\Connection;

use Ratchet\AbstractConnectionDecorator;

/**
 * Created by PhpStorm.
 * User: SaphirAngel
 * Date: 23/12/2014
 * Time: 19:40
 */
class Connection extends AbstractConnectionDecorator
{
    public $messages;

    /**
     * Send data to the connection
     * @param  string $data
     * @return \Ratchet\ConnectionInterface
     */
    function send($data)
    {
        $this->messages[] = $data;
    }

    function sendNextMessage()
    {
        $this->getConnection()->send(array_shift($this->messages));
    }

    /**
     * Close the connection
     */
    function close()
    {
    }
} 