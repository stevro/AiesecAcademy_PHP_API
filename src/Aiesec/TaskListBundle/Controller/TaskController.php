<?php

/*
 *  Developed by Stefan Matei - stev.matei@gmail.com
 */

namespace Aiesec\TaskListBundle\Controller;

use Aiesec\TaskListBundle\Entity\Task;
use Aiesec\TaskListBundle\Form\TaskType;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\View\RouteRedirectView;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\HttpCacheBundle\Configuration\InvalidatePath;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Hateoas\Representation\PaginatedRepresentation;
use Hateoas\Representation\CollectionRepresentation;

/**
 * Description of TaskController
 *
 * @author stefan
 */
class TaskController extends FOSRestController
{

    /**
     * List all tasks.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing tasks.")
     * @Annotations\QueryParam(name="limit", requirements="\d+", default="5", description="How many tasks to return.")
     *
     * @Annotations\View(templateVar="tasks", serializerEnableMaxDepthChecks=true)
     *
     * @param Request               $request      the request object
     * @param ParamFetcherInterface $paramFetcher param fetcher service
     *
     * @return array
     */
    public function getTasksAction(Request $request, ParamFetcherInterface $paramFetcher)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $offset = $paramFetcher->get('offset');
        $offset = null == $offset ? 0 : $offset + 1;
        $limit = $paramFetcher->get('limit');

        $tasks = $entityManager->getRepository('AiesecTaskListBundle:Task')->findBy(array(), array('deadline' => 'ASC'), $limit, $offset);

        return $tasks;
    }

    /**
     * Get a single task.
     *
     * @ApiDoc(
     *   output = "Aiesec\TaskListBundle\Entity\Task",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     404 = "Returned when the task is not found"
     *   }
     * )
     *
     * @Annotations\View(templateVar="task", serializerEnableMaxDepthChecks=true)
     *
     * @Cache(expires="+2 days", public = true, smaxage=30, maxage=15)
     *
     * @param Request $request the request object
     * @param int     $id      the task id
     *
     * @return array
     *
     * @throws NotFoundHttpException when task not exist
     */
    public function getTaskAction(Request $request, $id)
    {
        $task = $this->getTask($id);

//        $response = new \Symfony\Component\HttpFoundation\Response();
//        $response->setLastModified($task->getUpdatedAt());
//        if ($response->isNotModified($request)) {
//            return $response;
//        }
//        dump('Not cached');

        $view = new View($task);

        return $view;
    }

    /**
     * Presents the form to use to create a new task.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes = {
     *     200 = "Returned when successful"
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @return FormTypeInterface
     */
    public function newTaskAction()
    {
        return $this->createForm(new TaskType());
    }

    /**
     * Creates a new task from the submitted data.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "Aiesec\TaskListBundle\Form\TaskType",
     *   statusCodes = {
     *     200 = "Returned when successful",
     *     400 = "Returned when the form has errors"
     *   }
     * )
     *
     * @Annotations\View(
     *   template = "AiesecTaskListBundle:Task:newTask.html.twig",
     *   statusCode = Codes::HTTP_BAD_REQUEST
     * )
     *
     * @InvalidatePath("/tasks")
     *
     * @param Request $request the request object
     *
     * @return FormTypeInterface|RouteRedirectView
     */
    public function postTasksAction(Request $request)
    {
        $task = new Task();
        $form = $this->createForm(new TaskType(), $task);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->routeRedirectView('get_task', array('id' => $task->getId()));
        }

        return array(
            'form' => $form
        );
    }

    /**
     * Presents the form to use to update an existing task.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     200 = "Returned when successful",
     *     404 = "Returned when the task is not found"
     *   }
     * )
     *
     * @Annotations\View()
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return FormTypeInterface
     *
     * @throws NotFoundHttpException when note not exist
     */
    public function editTaskAction(Request $request, $id)
    {
        $task = $this->getTask($id);

        $form = $this->createForm(new TaskType(), $task, array('method' => 'PUT'));

        return $form;
    }

    /**
     * Update existing task from the submitted data or create a new task at a specific location.
     *
     * @ApiDoc(
     *   resource = true,
     *   input = "Aiesec\TaskListBundle\Form\TaskType",
     *   statusCodes = {
     *     201 = "Returned when a new resource is created",
     *     204 = "Returned when successful",
     *     400 = "Returned when the form has errors"
     *   }
     * )
     *
     * @Annotations\View(
     *   template="AiesecTaskListBundle:Task:editTask.html.twig",
     *   templateVar="form"
     * )
     *
     * @InvalidatePath("/tasks")
     *
     * @param Request $request the request object
     * @param int     $id      the note id
     *
     * @return FormTypeInterface|RouteRedirectView
     *
     * @throws NotFoundHttpException when task not exist
     */
    public function putTaskAction(Request $request, $id)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $task = $entityManager->getRepository('AiesecTaskListBundle:Task')->find($id);
        if (false === $task) {
            $task = new Task();

            $statusCode = Codes::HTTP_CREATED;
        } else {
            $statusCode = Codes::HTTP_NO_CONTENT;
        }

        $form = $this->createForm(new TaskType(), $task, array('method' => 'PUT'));

        $form->handleRequest($request);
        if ($form->isValid()) {

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->routeRedirectView('get_task', array('id' => $task->getId()), $statusCode);
        }

        $view = View::create($form, 400);
        return $view;
    }

    /**
     * Removes a task.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     204="Returned when successful"
     *   }
     * )
     *
     * @InvalidatePath("/tasks")
     *
     * @param Request $request the request object
     * @param int     $id      the task id
     *
     * @return RouteRedirectView
     */
    public function deleteTaskAction(Request $request, $id)
    {
        $task = $this->getTask($id);

        $entityManager = $this->getDoctrine()->getManager();

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->routeRedirectView('get_tasks', array(), Codes::HTTP_NO_CONTENT);
    }

    /**
     * Marks as done a task.
     *
     * @ApiDoc(
     *   resource = true,
     *   statusCodes={
     *     204="Returned when successful"
     *   }
     * )
     *
     * @InvalidatePath("/tasks")
     *
     * @param Request $request the request object
     * @param int     $id      the task id
     *
     * @return RouteRedirectView
     */
    public function putTasksDoneAction(Request $request, $id)
    {
        $task = $this->getTask($id);
        $task->setStatus(Task::DONE);

        $this->getDoctrine()->getManager()->flush();

        return $this->routeRedirectView('get_tasks', array(), Codes::HTTP_NO_CONTENT);
    }

    /**
     *
     * @param integer $id
     * @return Task
     * @throws NotFoundHttpException
     */
    protected function getTask($id)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $task = $entityManager->getRepository('AiesecTaskListBundle:Task')->find($id);
        if (false === $task) {
            throw $this->createNotFoundException("Task does not exist.");
        }

        return $task;
    }

}