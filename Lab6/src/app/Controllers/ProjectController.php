<?php
// app/Controllers/ProjectController.php — контроллер проектов: список и просмотр проекта с его задачами.
// Напишите его САМОСТОЯТЕЛЬНО по образцу TaskController (методы index и show устроены так же).

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ProjectRepository;
use App\Models\TaskRepository;

final class ProjectController
{
    // GET /projects
    public function index(): void
    {
        $projects = (new ProjectRepository())->all();

        view('projects/index', [
            'title'    => 'Проекты',
            'projects' => $projects,
        ]);
    }

    // GET /projects/2
    public function show(string $id): void
    {
        $project = (new ProjectRepository())->find((int) $id);

        if (!$project) {
            notFound();
        }

        $tasks = (new TaskRepository())->byProject($project['id']);

        view('projects/show', [
            'title' => $project['title'],
            'project' => $project,
            'tasks'   => $tasks,
        ]);
    }
}
