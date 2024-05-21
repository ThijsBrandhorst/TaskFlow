<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use DivisionByZeroError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

class TaskController extends AbstractController
{

    #[Route(path: '/', name: 'app_task_index', methods: [Request::METHOD_GET])]
    public function index(TaskRepository $repository): Response
    {
        $tasks = $repository->findAll();
        try {
            $percentDone = 100 / count($tasks) * count($this->filterCompleteTasks($tasks));
        } catch (DivisionByZeroError $error) {
            $percentDone = 0;
        }
        return $this->render('task/index.html.twig', [
            'tasks' => $this->sortTasks($tasks),
            'percentDone' => round($percentDone,0),
        ]);
    }

    #[Route(path: '/task/complete/{id}', name: 'app_task_complete', requirements: ['id' => '\d+'], methods: [Request::METHOD_POST])]
    public function complete(int $id, EntityManagerInterface $manager): Response
    {
        $task = $manager->getRepository(Task::class)->find($id);
        $task->setCompleted(!$task->isCompleted());
        $manager->flush();
        return $this->redirectToRoute('app_task_index');
    }

    #remove
    #[Route('/task/delete/{id}', name: 'app_task_delete', methods: ['POST'])]
    public function deleteTask(Task $task, EntityManagerInterface $entityManager): Response
    {
        // Check if the task exists
        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        // Remove the task
        $entityManager->remove($task);
        $entityManager->flush();

        // Redirect back to the index page or any other page as needed
        return $this->redirectToRoute('app_task_index');
    }

    # form
    #[Route('/task/create', name: 'app_task_create', methods: ['POST']), ]
    public function createTask(Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = new Task();
        // Retrieve the task body from the request
        $taskBody = $request->request->get('task_body');

        // Set the task body to the Task entity
        $task->setBody($taskBody);
        $task->setCompleted(false);

        $entityManager->persist($task);
        $entityManager->flush();

        return $this->redirectToRoute('app_task_index');
    }

    #[Route('/task/edit/{id}', name: 'app_task_edit', methods: ['POST'])]
    public function editTask(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($id);
        if (!$task) {
            throw $this->createNotFoundException('Task not found');
        }

        $newBody = $request->request->get('new_body');
        $task->setBody($newBody);
        $entityManager->flush();

        return $this->redirectToRoute('app_task_index');
    }

    private function sortTasks(array $tasks): array
    {
        $sortedTasks = [];
        foreach ($tasks as $task) {
            if ($task->isCompleted()) {
                $sortedTasks[] = $task;
                continue;
            }
            array_unshift($sortedTasks, $task);
        }
        return $sortedTasks;
    }

    private function filterCompleteTasks(array $tasks): array
    {
        $filteredTasks = [];
        foreach ($tasks as $task) {
            if ($task->isCompleted()) {
                $filteredTasks[] = $task;
            }
        }
        return $filteredTasks;
    }

}