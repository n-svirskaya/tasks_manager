<?php

namespace ManagerBundle\Controller;

use Doctrine\ODM\MongoDB\DocumentManager;
use ManagerBundle\Form\TaskType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use ManagerBundle\Document\Task;
use Symfony\Component\HttpFoundation\Request;

class MainController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        $tasks = $this->get('doctrine_mongodb')
            ->getRepository('ManagerBundle:Task')
            ->findAll();

        return $this->render('ManagerBundle:Main:index.html.twig', array('tasks' => $tasks));
    }

    /**
     * @Route("/create", name="create")
     */
    public function createAction(Request $request)
    {
        /** @var DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        $taskId = $request->get('id');
        $createdAt = new \DateTime('now');
        if ($taskId) {
            $task = $dm->getRepository('ManagerBundle:Task')->find($taskId);
            $msg = 'Ваша задача успешно обновлена!';
        } else {
            $task = new Task();
            $msg = 'Ваша задача успешно добавлена!';
            $task->setCreatedAt($createdAt->getTimestamp());
        }

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $task->setUpdatedAt($createdAt->getTimestamp());
            $dm->persist($task);
            $dm->flush($task);

            $this->get('session')->getFlashBag()->add('notice', $msg);

            return $this->redirect($this->generateUrl('homepage'));
        }

        return $this->render('ManagerBundle:Main:edit.html.twig', array('form' => $form->createView()));
    }

    /**
     * @Route("/delete", name="delete")
     */
    public function deleteAction(Request $request)
    {
        /** @var DocumentManager $dm */
        $dm = $this->get('doctrine_mongodb')->getManager();
        $taskId = $request->get('id');
        if ($taskId) {
            $task = $dm->getRepository('ManagerBundle:Task')->find($taskId);
            if (is_object($task)) {
                $dm->remove($task);
                $dm->flush($task);
                $this->get('session')->getFlashBag()->add('notice', 'Задача удалена успешно!');
            } else {
                $this->get('session')->getFlashBag()->add('error', 'Такой задачи не существует!');
            }
        } else {
            $this->get('session')->getFlashBag()->add('error', 'Произошла ошибка!');
        }

        return $this->redirect($this->generateUrl('homepage'));
    }
}
