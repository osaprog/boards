<?php

namespace Boards\Model;

use Boards\BoardsGateway\BoardsGatewayMapper;
use Zend\Debug\Debug;
use Boards\BoardsGateway\HttpClient;

class BoardsTable extends BoardsGatewayMapper {

    public function fetch($query = 'forum') {
        $httpClient = new HttpClient();
        return $httpClient->sendGetApiRequest($query);
    }

}
