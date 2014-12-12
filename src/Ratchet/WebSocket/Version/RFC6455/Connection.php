<?php
namespace Ratchet\WebSocket\Version\RFC6455;

use Ratchet\AbstractConnectionDecorator;
use Ratchet\Wamp\Exception;
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
    public function ping($timeout, $uniqId = null)
    {
        if (is_null($this->loop))
            throw new \UnexpectedValueException('No loop event in server');

        if (is_null($uniqId))
            $uniqId = uniqid('', true);
        if (!isset($this->WebSocket->pingH))
            $this->WebSocket->pingH = [];

        $this->WebSocket->pingH[$uniqId] = new \StdClass;
        $this->WebSocket->pingH[$uniqId]->deferredPong = new \React\Promise\Deferred();

        $this->loop->addTimer(0.01, function () use ($uniqId, $timeout) {
            $this->WebSocket->pingH[$uniqId]->pongTimer = $this->loop->addTimer($timeout, function () use ($uniqId) {
                $deferred = $this->WebSocket->pingH[$uniqId];
                unset($this->WebSocket->pingH[$uniqId]);
                $deferred->deferredPong->reject('no response');
            });
            $this->WebSocket->pingH[$uniqId]->lastPingTimestamp = (new \DateTime())->getTimestamp();
            $frame = new Frame($uniqId, true, Frame::OP_PING);
            $this->send($frame);
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
    public function keepAlive($interval = 5, $timeout = 0.5)
    {
        $this->loop->addPeriodicTimer($interval, function ($timer) use ($timeout) {
            $this->ping($timeout, 'keepAlive')->then(
                function ($timestamp) {
                },
                function () use ($timer) {
                    $timer->cancel();
                    unset($this->WebSocket->pingH['keepAlive']);
                    $this->close();
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
