<?php

namespace App\Controller;

use App\Entity\Tricks;
use App\Repository\CategoriesRepository;
use App\Repository\TricksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(CategoriesRepository $categoriesRepository, TricksRepository $tricks): Response
    {
        
        return $this->render('main/index.html.twig', [
            'categories' => $categoriesRepository->findall(),
            'tricks' => $tricks->findAll()
        ]);
    }


}
