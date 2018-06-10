<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

use App\Entity\Employee;

class PagesController extends AbstractController
{
    private function getUserById($id){
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $user = $repository->find($id);
        return $user;
    }

    private function checkIfLoggedIn(SessionInterface $session){
        if( $session->get('user_id', -1) == -1 ) return false; else return true;
    }

    public function login(SessionInterface $session){
        if( $this->checkIfLoggedIn($session) ) return $this->redirectToRoute('schedule');

        return $this->render('pages/login.html.twig', [
            'controller_name' => 'PagesController',
        ]);
    }

    public function login_action(SessionInterface $session){
        $request = Request::createFromGlobals();
        $login_username = $request->query->get('login_username');
        $login_password = $request->query->get('login_password');
        //die($login_username." ".$login_password);
    
        $repository = $this->getDoctrine()->getRepository(Employee::class);

        $user = $repository->findOneBy(
            ['email' => $login_username]
        );

        if( !$user ) return $this->redirectToRoute('login', array('error' => 'wrong_username_or_password'));

        if( $user->getPassword() == $login_password  ){
            $session->set('user_id', ($user->getId()) );

            if( $user->getPost() == "Admin" ){
                $session->set('user_permission', 1);
            }else{
                $session->set('user_permission', 0);
            }

            return $this->redirectToRoute('schedule');
        } else {
            return $this->redirectToRoute('login', array('error' => 'wrong_username_or_password'));
        }
    }

    public function logout_action(SessionInterface $session){
        $session->set('user_permission', -1);
        $session->set('user_id', -1);
        return $this->redirectToRoute('login');
    }

    public function employees(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');

        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $users = $repository->findAll();

        return $this->render('pages/employees.html.twig', [
            'controller_name' => 'PagesController',
            'users' => $users,
        ]);
    }

    public function add_employee_action(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');
        
        $request = Request::createFromGlobals();
        $entityManager = $this->getDoctrine()->getManager();

        $newUser = new Employee();
        $newUser->setFirstName( $request->query->get('first_name') );
        $newUser->setLastName( $request->query->get('last_name') );
        $newUser->setPost( $request->query->get('post') );
        $newUser->setPassword( $request->query->get('password') );
        $newUser->setEmail( $request->query->get('email') );

        $defaultSchedule = "";
        for($i = 0; $i<24*7; $i++) $defaultSchedule = $defaultSchedule."0";
        $newUser->setSchedulePattern( $defaultSchedule );

        $entityManager->persist($newUser);
        $entityManager->flush();

        return $this->redirectToRoute('employees');
    }

    public function delete_employee_action(SessionInterface $session, $id){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');

        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $toBeDeleted = $repository->find($id);

        if( !$toBeDeleted ) return $this->redirectToRoute('employees');

        $entityManager->remove($toBeDeleted);
        $entityManager->flush();
        
        return $this->redirectToRoute('employees');
    }

    public function edit_employee_action(SessionInterface $session, $id){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');
        if( $session->get('user_permission', -1) != 1 ) return $this->redirectToRoute('schedule');

        $request = Request::createFromGlobals();
        
        $entityManager = $this->getDoctrine()->getManager();
        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $toBeEdited = $repository->find($id);

        if( !$toBeEdited ) return $this->redirectToRoute('employees');

        $toBeEdited->setFirstName( $request->query->get('first_name') );
        $toBeEdited->setLastName( $request->query->get('last_name') );
        $toBeEdited->setPost( $request->query->get('post') );
        $toBeEdited->setPassword( $request->query->get('password') );
        $toBeEdited->setEmail( $request->query->get('email') );

        $entityManager->flush();
        
        return $this->redirectToRoute('employees');
    }

    public function schedule(SessionInterface $session){
        if( !($this->checkIfLoggedIn($session)) ) return $this->redirectToRoute('login');

        $repository = $this->getDoctrine()->getRepository(Employee::class);
        $users = $repository->findAll();

        return $this->render('pages/schedule.html.twig', [
            'controller_name' => 'PagesController',
            'users' => $users,
        ]);
    }

}
