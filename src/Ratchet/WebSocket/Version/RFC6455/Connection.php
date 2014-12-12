<?php
namespace Ratchet\WebSocket\Version\RFC6455;

use Ratchet\AbstractConnectionDecorator;
use Ratchet\WebSocket\Version\DataInterface;
use React\Promise\Deferred;
use React\EventLoop\LibEventLoop;

/**
 * {@inheritdoc}
 * @property \StdClass $WebSocket
 */
class Connection extends AbstractConnectionDecorator
{
    /**
     * {@inheritdoc}
     */
    public function send($msg)
    {
        if (!$this->WebSocket->closing) {
            if (!($msg instanceof DataInterface)) {
                $msg = new Frame($msg);
            }

            $this->getConnection()->send($msg->getContents());
        }

        return $this;
    }


    /**
     * Send a ping frame to the connection and return a promise
     *
     * @param LibEventLoop $loop The loop for timeout managment
     * @param $timeout Timeout before the connection is considered as dead
     * @param null $uniqId A id for identify the ping request (call uniqId function if null)
     * @return mixed A promise
     */
    public function ping(LibEventLoop $loop, $timeout, $uniqId = null)
    {
        $connection = &$this;
        if (is_null($uniqId))
            $uniqId = uniqid('', true);
        if (!isset($this->WebSocket->pingH))
            $this->WebSocket->pingH = [];
        $this->WebSocket->pingH[$uniqId] = new \StdClass;
        $this->WebSocket->pingH[$uniqId]->deferredPong = new \React\Promise\Deferred();
        $loop->addTimer(0.01, function () use (&$connection, $uniqId, $loop, $timeout) {
            $connection->WebSocket->pingH[$uniqId]->pongTimer = $loop->addTimer($timeout, function () use (&$connection, $uniqId) {
                $deferred = $connection->WebSocket->pingH[$uniqId];
                unset($connection->WebSocket->pingH[$uniqId]);
                $deferred->deferredPong->reject('no response');
            });
            $connection->WebSocket->pingH[$uniqId]->lastPingTimestamp = (new \DateTime())->getTimestamp();
            $frame = new Frame($uniqId, true, Frame::OP_PING);
            $connection->send($frame);
        });
        return $this->WebSocket->pingH[$uniqId]->deferredPong->promise();
    }

    /**
     *
     * Check if the connection is up by a periodic send of ping request to the connection.
     * Call close if the ping fail.
     *
     * @param LibEventLoop $loop
     * @param int $interval Duration between two sending of ping request
     * @param float $timeout Timeout before the connection is considered as dead
     */
    public function keepAlive(LibEventLoop $loop, $interval = 5, $timeout = 0.5)
    {
        $conn = $this;
        $loop->addPeriodicTimer($interval, function ($timer) use ($loop, $conn, $timeout) {
            $conn->ping($loop, $timeout, 'keepAlive')->then(
                function ($timestamp) {
                },
                function () use ($conn, $timer) {
                    $timer->cancel();
                    unset($conn->WebSocket->pingH['keepAlive']);
                    $conn->close();
                });
        });
    }


    /**
     * {@inheritdoc}
     */
    public function close($code = 1000)
    {
        if ($this->WebSocket->closing) {
            return;
        }

        if ($code instanceof DataInterface) {
            $this->send($code);
        } else {
            $this->send(new Frame(pack('n', $code), true, Frame::OP_CLOSE));
        }

        $this->getConnection()->close();

        $this->WebSocket->closing = true;
    }
}
