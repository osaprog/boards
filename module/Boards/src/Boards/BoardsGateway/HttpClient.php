<?php
namespace Boards\BoardsGateway;

class HttpClient{
    
    const SERVICE_URL = 'https://api.boards.ie/';
    const API_KEY = 'bcde9b99d2f7cec38906383831388eb1';
    
    function sendGetApiRequest($query) {
                $method = 'GET';
                $params = array(
                    '_key' => self::API_KEY
                );
                // In case we use POST or POST Request.
                $headers = array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                );
                $curl = curl_init();
                $service_url = self::SERVICE_URL . $query;
                $service_url .= '?' . http_build_query($params);
                curl_setopt($curl, CURLOPT_URL, $service_url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

                $response = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($code == 200) {
                    $response = json_decode($response, true);
                } else {
                    echo 'error ' . $code;
                    return false;
                }
                return $response;
            }
}