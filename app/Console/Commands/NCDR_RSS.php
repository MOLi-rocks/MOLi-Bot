<?php

namespace MOLiBot\Console\Commands;

use Illuminate\Console\Command;

use Exception;
use MOLiBot\Services\NcdrService;
use MOLiBot\Services\TelegramService;

class NCDR_RSS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ncdr:rss-check {--init}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check New RSS Feed From NCDR';

    /**
     * @var NcdrService
     */
    private $ncdrService;

    /**
     * @var telegramService
     */
    private $telegramService;

    /** @var \Illuminate\Support\Collection NCDR_to_BOTChannel_list */
    private $NCDR_to_BOTChannel_list;

    /** @var \Illuminate\Support\Collection NCDR_should_mute */
    private $NCDR_should_mute;
    
    /**
     * Create a new command instance.
     *
     * @param NcdrService $ncdrService
     * @param TelegramService $telegramService
     * 
     * @return void
     */
    public function __construct(NcdrService $ncdrService,
                                TelegramService $telegramService)
    {
        parent::__construct();
        
        $this->ncdrService = $ncdrService;
        $this->telegramService = $telegramService;

        // 哪些類別的 NCDR 訊息要推到 MOLi 廣播頻道
        $this->NCDR_to_BOTChannel_list = collect([
            '地震',
            '土石流',
            '河川高水位',
            '降雨',
            '停班停課',
            '道路封閉',
            '雷雨',
            '颱風'
        ]);

        // 哪些類別的 NCDR 訊息要靜音
        $this->NCDR_should_mute = collect([
            '土石流'
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws
     */
    public function handle()
    {
        try {
            $contents = $this->ncdrService->getRss();

            $items = $contents['entry'];

            $nowListId = [];

            foreach ($items as $item) {
                $itemId = $item['id'];

                array_push($nowListId, $itemId);

                if (!$this->ncdrService->checkRssPublished($itemId)) {
                    if ($this->option('init')) {
                        $chatId = config('telegram-channel.test');
                    } else {
                        $chatId = config('telegram-channel.weather');
                    }

                    $category = $item['category']['@term'];

                    if ($this->NCDR_to_BOTChannel_list->contains($category)) {
                        $this->telegramService->sendMessage(
                            $chatId,
                            trim($item['summary']['#text']) . PHP_EOL . '#' . $category,
                            null,
                            true
                        );
                    }

                    $this->ncdrService->storePublishedRss($itemId, $category);

                    sleep(5);
                }
            }

            $this->ncdrService->deletePublishedRecordWithExcludeId($nowListId);

            $this->info('Mission Complete!');
            return 0;
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
