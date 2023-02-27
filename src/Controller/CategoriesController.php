<?php

namespace App\Controller;

use App\Entity\Categories;
use App\Repository\TricksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/categories', name: 'categories_')]
class CategoriesController extends AbstractController
{
    #[Route('/{slug}', name: 'list')]
    public function list(
        Categories $category, 
        TricksRepository $tricksRepository,
        Request $request
        ): Response
    {
        // recup de la page dans l'url
        $page = $request->query->getInt('page', 1);

        //je veux recup la liste de tous les produits de la catégorie demandée et paginée

        $tricks = $tricksRepository->findTricksPaginated($page, $category->getSlug(), 10); // dernier paramètre = nombre de produit par page
        return $this->render('categories/list.html.twig', compact('category', 'tricks'));
    }
}
