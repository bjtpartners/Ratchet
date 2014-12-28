<?php
namespace Ratchet\Server;

use Ratchet\ConnectionInterface;
use React\Socket\ConnectionInterface as ReactConn;
use React\EventLoop\LoopInterface;

/**
 * {@inheritdoc}
 */
class IoConnection implements ConnectionInterface
{
    /**
     * @var \React\Socket\ConnectionInterface
     */
    protected $conn;

    /**
     * @var \React\LibEventLoop\LibEventLoop
     */
    public $loop;

    /**
     * @param \React\Socket\ConnectionInterface $conn
     */
    public function __construct(ReactConn $conn, LoopInterface $loop = null)
    {
        $this->conn = $conn;
        $this->loop = $loop;
    }

    /**
     * {@inheritdoc}
     */
    public function send($data)
    {
        $this->conn->write($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->conn->end();
    }
}
