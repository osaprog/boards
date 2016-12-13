<?php

namespace Boards\BoardsGateway;

interface BoardsGatewayMapperInterface {

    /**
     * @return array
     * @throws BoardsGatewayMapperException
     */
    public function fetch($query);
}
