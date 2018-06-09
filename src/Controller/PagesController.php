<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PagesController extends AbstractController
{
    public function login()
    {
        return $this->render('pages/login.html.twig', [
            'controller_name' => 'PagesController',
        ]);
    }
}
