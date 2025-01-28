<?php

namespace App\Controller;

use App\Dto\CommodityDto;
use App\Entity\Commodity;
use App\Service\CommodityParser;
use App\Service\ImageDownloader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CommodityController extends AbstractController
{
    #[Route('/commodity/{id}', name: 'app_commodity', methods: 'GET')]
    public function getCommodity(string $id, EntityManagerInterface $em): JsonResponse
    {
        // Получаем данные из сущности
        $commodityData = $em->getRepository(Commodity::class)->find($id);
        return $this->json($commodityData);
    }

    #[Route('/commodity', name: 'app_commodities', methods: 'GET')]
    public function getCommodities(EntityManagerInterface $em): JsonResponse
    {
        // Получаем данные из сущности
        $commodityData = $em->getRepository(Commodity::class)->findAll();
        return $this->json($commodityData, 200);
    }

    #[Route('/commodity', name: 'create_commodity', methods: 'POST')]
    public function createCommodity(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
        CommodityParser $parser,
        ImageDownloader $imageDownloader,
    ): JsonResponse {
        // 1. Считываем входные данные
        $dto = new CommodityDto();
        $url = json_decode($request->getContent(), true)['url'];
        $dto->url = $url;

        // 2. Валидируем DTO
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            // Преобразуем ошибки
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsArray], 400);
        }

        try {
            // 3. Парсим данные из внешнего источника
            $parsedData = $parser->parse($dto->url)[0];

            if (empty($parsedData)) {
                return new JsonResponse(['error' => 'No product data found'], 404);
            }

            // 4. Скачиваем «Main image» (если есть)
            $localImageFilename = null;
            if (!empty($parsedData['photo'])) {
                $localImageFilename = $imageDownloader->downloadImage($parsedData['photo']);
            }

            // 5. Создаём новую сущность Commodity
            $commodity = new Commodity();

            // Из парсинга:
            $commodity->setName($parsedData['name'] ?? 'Unknown product');
            $commodity->setPrice($parsedData['price'] ?? null);
            $commodity->setDescription($parsedData['description'] ?? '');

            // В поле photo сохраняем локальное имя
            if ($localImageFilename) {
                // Храним что-то вроде 'img_xxx.jpg', без "uploads/".
                $commodity->setPhoto($localImageFilename);
            }

            // 6. Сохраняем сущность в БД
            try {
                $em->persist($commodity);
                $em->flush();
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                return new JsonResponse(['error' => 'A commodity with same name already exists'], 400);
            }

        } catch (\Exception $e) {
            // В случае любой ошибки парсинга или получения HTML
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }

        // 7. Возвращаем ответ
        return new JsonResponse([
            'id'     => $commodity->getId(),
            'name'   => $commodity->getName(),
            'price'  => $commodity->getPrice(),
            'description'  => $commodity->getDescription(),
            'photo' => $commodity->getPhoto(),
        ], 201);
    }

    #[Route('/commodity/{id}', name: 'update_commodity', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // 1. Ищем товар по ID
        $commodity = $em->getRepository(Commodity::class)->find($id);

        if (!$commodity) {
            return new JsonResponse(['error' => 'Commodity not found'], 404);
        }

        // 2. Извлекаем данные из запроса
        // {
        //   "name": "New name",
        //   "price": 99.99,
        //   "photo": "some_photo.jpg",
        //   "description": "Updated description"
        // }
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        // 3. Обновляем поля (проверяем, что они существуют в $data)
        if (array_key_exists('name', $data)) {
            $commodity->setName($data['name']);
        }
        if (array_key_exists('price', $data)) {
            if (!is_numeric($data['price'])) {
                return new JsonResponse(['error' => 'Price must be a numeric value.'], 400);
            }
            $commodity->setPrice((float) $data['price']);
        }
        if (array_key_exists('photo', $data)) {
            $commodity->setPhoto($data['photo']);
        }
        if (array_key_exists('description', $data)) {
            $commodity->setDescription($data['description']);
        }

        // 4. Сохраняем изменения
        try {
            $em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return new JsonResponse(['error' => 'Changes rejected. A commodity with same name already exists'], 400);
        }

        // 5. Возвращаем результат (новые данные)
        return new JsonResponse([
            'id'          => $commodity->getId(),
            'name'        => $commodity->getName(),
            'price'       => $commodity->getPrice(),
            'photo'       => $commodity->getPhoto(),
            'description' => $commodity->getDescription(),
        ],
        200);
    }

    #[Route('/commodity/{id}', name: 'delete_commodity', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        // 1. Ищем товар по ID
        $commodity = $em->getRepository(Commodity::class)->find($id);

        if (!$commodity) {
            return new JsonResponse(['error' => 'Commodity not found'], 404);
        }

        // 2. Удаляем файл
        $fileName = $commodity->getPhoto();
        if ($fileName) {
            // Полный путь
            $filePath = $this->getParameter('uploads_directory') . '/' . $fileName;

            if (file_exists($filePath)) {
                // Удаляем
                unlink($filePath);
            }
        }

        // 3. Удаляем сущность из БД
        $em->remove($commodity);
        $em->flush();

        return new JsonResponse(['status' => 'deleted'], 200);
    }
}
