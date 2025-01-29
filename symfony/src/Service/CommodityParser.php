<?php

namespace App\Service;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Panther\Client;

/**
 * Сервис для парсинга JSON-LD (type="application/ld+json")
 */
class CommodityParser
{
    /**
     * Загружаем HTML по заданному URL и извлекаем данные о продукте из JSON-LD.
     */
    public function parse(string $url): array
    {
        // 1. Скачиваем HTML
        $html = $this->fetchHtml($url);

        // 2. Парсим теги <script type="application/ld+json">
        return $this->parseJsonLd($html);
    }

    /**
     * Скачиваем HTML через Guzzle
     */
    private function fetchHtml(string $url): string
    {
        $capabilities = array(
            "os"                       => "Windows",
            "os_version"               => "11",
            "browser"                  => "Chrome",
            "browser_version"          => "latest",
            "name"                     => "Test",
            "build"                    => "Build 1.0",
            "browserstack.debug"       => true,
            "browserstack.console"     => "info",
            "browserstack.networkLogs" => true,
            "disableCorsRestrictions"  => true,
            "wsLocalSupport"           => true,
            "geoLocation"              => "GB",
            "goog:chromeOptions"       => [
                "args" => [
                    '--disable-popup-blocking',
                    '--disable-application-cache',
                    '--disable-web-security',
                    '--start-maximized',
                    '--ignore-certificate-errors',
                    '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36',
                    '--window-size=1200,1100',
                ],
            ],
        );
        $caps = DesiredCapabilities::chrome();
        foreach ($capabilities as $key => $value) {
            $caps->setCapability($key, $value);
        }
        $client = Client::createSeleniumClient(
            'http://selenium:4444/wd/hub', // Адрес и порт Selenium
            $caps,
        );
        $client->request('GET', $url);

        // Дождёмся, пока JS выполнится и Cloudflare пропустит
        sleep(5); // или использовать явное ожидание загрузки/редиректа

        return $client->getPageSource();

    }

    /**
     * Ищем все теги <script type="application/ld+json">, декодируем JSON
     * и возвращаем массивы, где @type = "Product".
     */
    public function parseJsonLd(string $html): array
    {
        $crawler = new Crawler($html);
        $ldJsonBlocks = $crawler->filter('script[type="application/ld+json"]');

        $products = [];

        foreach ($ldJsonBlocks as $scriptTag) {
            $scriptContent = $scriptTag->nodeValue;
            $data = json_decode($scriptContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                continue; // некорректный JSON
            }

            // Бывает объект или массив объектов, нормализуем:
            $items = isset($data[0]) ? $data : [$data];

            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['@type'])) {
                    continue;
                }
                if ($item['@type'] === 'Product') {
                    $products[] = $this->extractProductData($item);
                }
            }
        }

        return $products;
    }

    /**
     * Извлекаем ключевые поля из структуры JSON-LD с @type="Product".
     * Меняйте по необходимости (доставайте дополнительные поля).
     */
    private function extractProductData(array $item): array
    {
        // Название, описание
        $name = $item['name'] ?? null;
        $description = $item['description'] ?? null;

        // Изображения могут быть массивом строк или массивом объектов.
        $image = null;
        if (!empty($item['image']) && is_array($item['image'])) {
            foreach ($item['image'] as $imgObj) {
                // Проверяем, что это массив с полями "url" и "name"
                if (is_array($imgObj) && isset($imgObj['url'], $imgObj['name'])) {
                    // Ищем "Main image" в "name"
                    if (str_contains($imgObj['name'], 'Main image')) {
                        $image = $imgObj['url'];
                        break; // прекращаем перебор
                    }
                }
            }
        }

        // Цена лежит в offers.price, но может быть чем угодно (напр. "6,999,-").
        // Нужно почистить.
        $rawPrice = $item['offers']['price'] ?? null;
        $price = $this->cleanPrice($rawPrice);

        // Собираем результат
        return [
            'name'        => $name,
            'description' => $description,
            'photo'       => $image,
            'price'       => $price
        ];
    }

    /**
     * Убираем лишние символы и превращаем в float.
     * Например, "6,999,-" -> 6999.0
     */
    private function cleanPrice(?string $priceString): ?float
    {
        if (!$priceString) {
            return null;
        }
        // Убираем всё, кроме цифр и точки
        $clean = preg_replace('/[^0-9\.]/', '', $priceString);

        return (float) $clean;
    }
}
