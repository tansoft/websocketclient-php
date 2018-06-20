<?php

namespace WebSocket;

/**
 * @desc
 * @package   WebSocket
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright MIT
 */

class ClientEx extends Client {
    public function tryconnect() {
        $this->connect();
        return $this->is_connected;
    }
    public function getsocket() {
        return $this->socket;
    }
    public function reset() {
        $this->is_connected = false;
    }
}