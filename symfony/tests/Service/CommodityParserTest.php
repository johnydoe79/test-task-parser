<?php

namespace App\Tests\Service;

use App\Service\CommodityParser;
use PHPUnit\Framework\TestCase;

class CommodityParserTest extends TestCase
{
    public function testParserUnit(): void
    {
        // 1. Создаём экземпляр «боевого» CommodityParser
        $parser = new CommodityParser();

        // 2. Указываем пример html кода, содержащего информацию о товаре со страницы Alza:
        $html = <<<HTML
<script type="application/ld+json">
{
    "@context":"https://schema.org",
    "@type":"Product",
    "name":"MORA Shallow Baking Sheet Black",
    "image":[{
        "@type":"ImageObject",
        "url":"https://image.alza.cz/products/MORAPL23/MORAPL23.jpg",
        "name":"MORA Shallow Baking Sheet Black - Baking Sheet - Main image"
    }],
    "description":"Baking Sheet – shallow, made of enamelled Steel, non-stick coating, 15 × 424 × 360mm (H × W × D) ",
    "sku":"MORAPL23",
    "mpn":"685997",
    "brand":
    {
        "@type":"Brand",
        "name":"MORA"
    },
    "aggregateRating":
    {
        "@type":"AggregateRating",
        "ratingValue":4.8,
        "reviewCount":13
    },
    "offers":
    {
        "@type":"Offer",
        "url":"https://www.alza.cz/mora-shallow-baking-sheet-black-d5158983.htm",
        "priceCurrency":"CZK",
        "price":"390,-",
        "itemCondition":"https://schema.org/NewCondition",
        "availability":"https://schema.org/InStock"
    }
}
</script>
HTML;
        // 3. Вызываем непосредственно парсер
        $result = $parser->parseJsonLd($html);

        // 4. Проверяем, что что-то вернулось
        $this->assertNotEmpty($result, 'Ожидаем, что на странице есть хотя бы один Product в JSON-LD');

        // 5. Можно проверить отдельные поля
        // Допустим, берем первый элемент
        $first = $result[0];

        // Проверяем, что name не пустое
        $this->assertNotEmpty($first['name'] ?? null, 'У товара должно быть имя');

        // Проверяем, что price не пустое
        $this->assertNotEmpty($first['price'] ?? null, 'У товара нет цены');

        // Проверяем, что name не пустое
        $this->assertNotEmpty($first['photo'] ?? null, 'У товара нет фото');

        // Проверяем, что name не пустое
        $this->assertNotEmpty($first['description'] ?? null, 'У товара нет описания');

    }
}
