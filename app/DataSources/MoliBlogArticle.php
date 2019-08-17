<?php

namespace MOLiBot\DataSources;

use MOLiBot\Exceptions\DataSourceRetriveException;
use Exception;

class MoliBlogArticle extends Source
{
    private $url;
    private $page = 1;

    /**
     * MoliKktix constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->url = config('moli.blog.url') . '/ghost/api/v0.1/posts/' .
            '?client_id=' . config('moli.blog.client_id') .
            '&client_secret=' . config('moli.blog.client_secret') .
            '&include=author,tags';
    }

    /**
     * @param $page
     * @return void
     */
    public function setPage($page)
    {
        if (!empty($page)) {
            $this->page = $page;
        }
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException|DataSourceRetriveException
     */
    public function getContent() : array
    {
        try {
            $response = $this->httpClient->request('GET', $this->url . '&page=' . $this->page);

            $fileContents = json_decode($response->getBody()->getContents(), true);

            return $fileContents;
        } catch (Exception $e) {
            throw new DataSourceRetriveException($e->getMessage(), $e->getCode());
        }
    }
}