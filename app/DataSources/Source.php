<?php

namespace MOLiBot\DataSources;

use GuzzleHttp\Client as GuzzleHttpClient;

abstract class Source implements SourceInterface
{
    protected $httpClient;

    /**
     * Source constructor.
     */
    public function __construct()
    {
        $this->httpClient = new GuzzleHttpClient([
            'headers' => [
                'User-Agent'      => 'MOLiBot',
                'Accept-Encoding' => 'gzip',
                'Accept'          => 'application/json',
                'cache-control'   => 'no-cache'
            ],
            'timeout' => 10
        ]);
    }

    /**
     * @return array
     */
    public function getContent() : array
    {
        return [];
    }

    /**
     * @param GuzzleHttpClient $newClient
     */
    public function newHttpClient(GuzzleHttpClient $newClient) : void
    {
        $this->httpClient = $newClient;
    }
}