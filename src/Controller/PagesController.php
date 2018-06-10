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

    public function login(){
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
            $session->set('user_id', $user->getId());

            if( $user->getPost() == "Admin" ){
                $session->set('user_permission', 1);
                return $this->redirectToRoute('employees');
            }else{
                $session->set('user_permission', 0);
                return $this->redirectToRoute('employees', array('tmp' => 'true'));
            }
        } else {
            return $this->redirectToRoute('login', array('error' => 'wrong_username_or_password'));
        }
    }

    public function employees(){
        return $this->render('pages/employees.html.twig', [
            'controller_name' => 'PagesController',
        ]);
    }
}
