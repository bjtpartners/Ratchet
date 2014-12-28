<?php
namespace Ratchet\SocketIO\Transports\Types\Polling;

/**
 * Created by PhpStorm.
 * User: SaphirAngel
 * Date: 22/12/2014
 * Time: 20:51
 */
class Polling
{
    private $messages = [];
    private $component = null;

    public function onMessage(ConnectionInterface $conn, RequestInterface $request)
    {
        if (strtoupper($request->getMethod()) == 'GET') {
            $conn->sendNextMessage();
        } else {
            $this->connection->onMessage($conn, $request->getBody());
        }
    }
}