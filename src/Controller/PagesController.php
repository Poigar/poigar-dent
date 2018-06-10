<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class PagesController extends AbstractController
{
    public function login()
    {
        return $this->render('pages/login.html.twig', [
            'controller_name' => 'PagesController',
        ]);
    }

    public function employees()
    {
        return $this->render('pages/employees.html.twig', [
            'controller_name' => 'PagesController',
        ]);
    }
}
