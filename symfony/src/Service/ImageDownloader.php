<?php

namespace App\Service;

use GuzzleHttp\Client;

class ImageDownloader
{
    private string $targetDir;

    public function __construct(string $targetDir)
    {
        // $targetDir — это путь до каталога, где будут храниться загруженные файлы.
        // Например, `public/uploads`
        $this->targetDir = rtrim($targetDir, '/');
    }

    /**
     * Скачиваем файл с указанного URL и сохраняем локально в $targetDir.
     * Возвращаем имя (или путь) сохранённого файла.
     */
    public function downloadImage(string $url): string
    {
        $client = new Client([
            'headers' => [
                'User-Agent' => 'PostmanRuntime/7.42.0',
                'Accept' => '*/*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Connection' => 'keep-alive',
            ],
            'cookies' => true,
            'allow_redirects' => true,
        ]);
        $response = $client->get($url);

        // Получаем байты картинки
        $content = $response->getBody()->getContents();


        // Генерируем уникальное имя
        // (Можно приделать проверку MIME-типа, расширение и т.п.)
        $filename = 'img_' . uniqid() . '.jpg';

        // Полный путь
        $fullPath = $this->targetDir . '/' . $filename;

        // Сохраняем в файл
        file_put_contents($fullPath, $content);

        return $filename;
    }
}
