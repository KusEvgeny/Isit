<?php
// app/Controllers/Api/CategoryApiController.php — REST API ресурса «категории» (/api/categories).

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\CategoryRepository;

final class CategoryApiController
{
    private CategoryRepository $categories;

    public function __construct()
    {
        $this->categories = new CategoryRepository();
    }

    // GET /api/categories — список
    public function index(): void
    {
        json_response($this->categories->all());
    }

    // GET /api/categories/2 — одна запись
    public function show(string $id): void
    {
        $category = $this->categories->find((int) $id);
        if ($category === null) {
            json_error('Категория не найдена', 404);
        }
        json_response($category);
    }

    // POST /api/categories — создание
    public function store(): void
    {
        $data = json_input();
        $errors = $this->validate($data);
        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $category = $this->categories->add([
            'name' => trim($data['name'])
        ]);
        header('Location: /api/categories/' . $category['id']);
        json_response($category, 201);
    }

    // PUT/PATCH /api/categories/2 — изменение
    public function update(string $id): void
    {
        $category = $this->categories->find((int) $id);
        if ($category === null) {
            json_error('Категория не найдена', 404);
        }

        $data = array_merge($category, json_input());
        $errors = $this->validate($data);
        if ($errors) {
            json_error('Ошибка проверки данных', 422, $errors);
        }

        $updated = $this->categories->update($category['id'], [
            'name' => trim($data['name'])
        ]);
        json_response($updated);
    }

    // DELETE /api/categories/2 — удаление
    public function destroy(string $id): void
    {
        if (!$this->categories->delete((int) $id)) {
            json_error('Категория не найдена', 404);
        }
        no_content();
    }

    // Проверка данных
    private function validate(array $data): array
    {
        $errors = [];
        if (!is_string($data['name'] ?? null) || mb_strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Название категории — не короче 2 символов';
        }
        return $errors;
    }
}