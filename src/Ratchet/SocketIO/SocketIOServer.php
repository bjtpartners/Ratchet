<?php
/**
 * Created by PhpStorm.
 * User: SaphirAngel
 * Date: 22/12/2014
 * Time: 14:15
 */

namespace Ratchet\SocketIO;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\Response;
use Ratchet\WebSocket\Version;


class SocketIOServer implements HttpServerInterface
{

    private $component;

    public function __construct(MessageComponentInterface $component)
    {
        $this->component = $component;

    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->transport->onClose($conn);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        // TODO: Implement onError() method.
    }

    /**
     * @param \Ratchet\ConnectionInterface $conn
     * @param \Guzzle\Http\Message\RequestInterface $request null is default because PHP won't let me overload; don't pass null!!!
     * @throws \UnexpectedValueException if a RequestInterface is not passed
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {

        if (null === $request) {
            throw new \UnexpectedValueException('$request can not be null');
        }

        /*
                $conn->WebSocket = new \StdClass;
                $conn->WebSocket->request = $request;
                $conn->WebSocket->established = false;
                $conn->WebSocket->closing = false;
        */


        if (null == ($sid = $conn->WebSocket->request->getQuery()->get('sid'))) {
            $conn->send(json_encode($this->getHandshake()));
        } else {
            $this->upgradeTransport($conn);
            $this->transport->onMessage($conn, $request);
        }
    }

    public function getHandshake()
    {
        return [
            'sid' => uniqid('', true),
            'upgrades' => ['polling', 'websocket'],
            'pingTimeout' => 30
        ];
    }

    public function upgradeTransport(ConnectionInterface $conn)
    {
        $conn->transport = new Polling($this->component);
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $this->transport->onMessage($from, $msg);
    }

    function attemptUpgrade(ConnectionInterface $conn, $data = '')
    {

    }

}