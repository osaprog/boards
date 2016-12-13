<?php

namespace Boards\BoardsGateway;

abstract class BoardsGatewayMapper implements BoardsGatewayMapperInterface {

    public function fetch($query) {
        try {
            // insert logic to data
        } catch (Exception $e) {
            throw new BoardsGatewayMapperException($e->getMessage(), $e->getCode, $e);
        }
    }

}
