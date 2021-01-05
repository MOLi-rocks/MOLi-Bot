<?php

namespace MOLiBot\Services;

use Exception;
use MOLiBot\Repositories\PublishedNcnuRssRepository;
use MOLiBot\DataSources\NcnuRss as RssDataSource;
use MOLiBot\DataSources\NcnuStaffContact as StaffContactDataSource;

class NcnuService
{
    private $publishedNcnuRssRepository;
    private $rssDataSource;
    private $staffContactSource;

    /**
     * NcnuService constructor.
     * @param PublishedNcnuRssRepository $publishedNcnuRssRepository
     * @param RssDataSource $rssDataSource
     * @param StaffContactDataSource $staffContactDataSource
     */
    public function __construct(
        PublishedNcnuRssRepository $publishedNcnuRssRepository,
        RssDataSource $rssDataSource,
        StaffContactDataSource $staffContactDataSource
    )
    {
        $this->publishedNcnuRssRepository = $publishedNcnuRssRepository;
        $this->rssDataSource = $rssDataSource;
        $this->staffContactSource = $staffContactDataSource;
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRss()
    {
        return $this->rssDataSource->getContent();
    }

    /**
     * @param $guid string
     * @return bool
     */
    public function checkRssPublished($guid)
    {
        return $this->publishedNcnuRssRepository->checkRssPublished($guid);
    }

    /**
     * @param $guid string
     * @return \Illuminate\Database\Eloquent\Model|PublishedNcnuRssRepository
     */
    public function storePublishedRss($guid)
    {
        return $this->publishedNcnuRssRepository->storePublishedRss($guid);
    }

    /**
     * @param $keyword
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getStaffContact($keyword = null)
    {
        try {
            $contents = $this->staffContactSource->getContent($keyword);

            $result = [];

            foreach ($contents as $content_item) {
                $tmpArray = [];

                $items = explode(",\"", $content_item);

                foreach ($items as $item) {
                    array_push($tmpArray, trim($item, "\"\r\n "));
                }

                array_push($result, $tmpArray);
            }

            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
}
