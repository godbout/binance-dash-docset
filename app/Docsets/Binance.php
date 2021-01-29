<?php

namespace App\Docsets;

use Godbout\DashDocsetBuilder\Docsets\BaseDocset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class Binance extends BaseDocset
{
    public const CODE = 'binance';
    public const NAME = 'Binance';
    public const URL = 'binance-docs.github.io';
    public const INDEX = 'apidocs/futures/en/index.html';
    public const PLAYGROUND = '';
    public const ICON_16 = '../../icons/icon.png';
    public const ICON_32 = '../../icons/icon@2x.png';
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
        $entries = $entries->union($this->guideEntries($crawler, $file));
        $entries = $entries->union($this->sectionEntries($crawler, $file));

        return $entries;
    }

    protected function guideEntries(HtmlPageCrawler $crawler, string $file)
    {
        $entries = collect();

        $crawler->filter('#toc a.toc-h1')->each(function (HtmlPageCrawler $node) use ($entries) {
            $entries->push([
                'name' => trim($node->text()),
                'type' => 'Guide',
                'path' => $this->url() . '/apidocs/futures/en/' . $node->attr('href')
            ]);
        });

        return $entries;
    }

    protected function sectionEntries(HtmlPageCrawler $crawler, string $file)
    {
        $entries = collect();

        $crawler->filter('h2')->each(function (HtmlPageCrawler $node) use ($entries, $file) {
            $entries->push([
                'name' => trim($node->text()),
                'type' => 'Section',
                'path' => Str::after($file . '#' . $node->getAttribute('id'), $this->innerDirectory()),
            ]);
        });

        return $entries;
    }

    public function format(string $file): string
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        $this->removeHeader($crawler);
        $this->removeLeftSidebar($crawler);

        $this->insertDashTableOfContents($crawler);

        return $crawler->saveHTML();
    }

    protected function removeHeader(HtmlPageCrawler $crawler)
    {
        $crawler->filter('body > header')->remove();
    }

    protected function removeLeftSidebar(HtmlPageCrawler $crawler)
    {
        $crawler->filter('.toc-wrapper')->remove();
    }

    protected function insertDashTableOfContents(HtmlPageCrawler $crawler)
    {
        $crawler->filter('h1#change-log')
            ->before('<a name="//apple_ref/cpp/Section/Top" class="dashAnchor"></a>');

        $crawler->filter('h2, h3, h4')->each(static function (HtmlPageCrawler $node) {
            $node->before(
                '<a id="' . Str::slug($node->text()) . '" name="//apple_ref/cpp/Section/' . rawurlencode($node->text()) . '" class="dashAnchor"></a>'
            );
        });
    }
}
