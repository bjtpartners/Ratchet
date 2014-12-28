<?php

/**
 * Created by PhpStorm.
 * User: SaphirAngel
 * Date: 22/12/2014
 * Time: 14:41
 */
class WebSocket
{

    public function handshake(RequestInterface $request)
    {
        if (true !== $this->_verifier->verifyAll($request)) {
            return new Response(400);
        }

        return new Response(101, array(
            'Upgrade' => 'websocket'
        , 'Connection' => 'Upgrade'
        , 'Sec-WebSocket-Accept' => $this->sign((string)$request->getHeader('Sec-WebSocket-Key'))
        ));
    }
}