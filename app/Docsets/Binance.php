<?php

namespace App\Docsets;

use Godbout\DashDocsetBuilder\Docsets\BaseDocset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class Binance extends BaseDocset
{
    public const CODE = 'binance';
    public const NAME = 'Binance';
    public const URL = 'binance-docs.github.io';
    public const INDEX = 'apidocs/futures/en/index.html';
    public const PLAYGROUND = '';
    public const ICON_16 = '../../icon.png';
    public const ICON_32 = '../../icon@2x.png';
    public const EXTERNAL_DOMAINS = [];


    public function grab(): bool
    {
        $toIgnore = implode('|', [
            '/cn/',
            '/delivery/',
            '/spot/',
            '/voptions/',
        ]);

        system(
            "echo; wget binance-docs.github.io/apidocs/futures/en/ \
                --mirror \
                --trust-server-names \
                --reject-regex='{$toIgnore}' \
                --page-requisites \
                --adjust-extension \
                --convert-links \
                --span-hosts \
                --domains={$this->externalDomains()} \
                --directory-prefix=storage/{$this->downloadedDirectory()} \
                -e robots=off \
                --quiet \
                --show-progress",
            $result
        );

        return $result === 0;
    }

    public function entries(string $file): Collection
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        $entries = collect();

        //

        return $entries;
    }

    public function format(string $file): string
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        //

        return $crawler->saveHTML();
    }
}
