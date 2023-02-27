<?php

namespace App\Controller;

use App\Entity\Tricks;
use App\Entity\Images;
use App\Form\TricksFormType;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/figures', name: 'figures_')]
class TricksController extends AbstractController
{



    #[Route('/ajout', name: 'add')]
    public function add(
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        PictureService $pictureService
        ): Response
    {
        
        $trick = new Tricks(); // nvelle figure

        //création formulaire 
        $form = $this->createForm(TricksFormType::class, $trick);
        // envoi de la requete du form
        $form->handleRequest($request);
        // verifie si form  soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {

            // On récupère les images du formulaire
            $images = $form->get('images')->getData();
            
            foreach($images as $image){
                // On définit le dossier de destination
                $folder = 'tricks';

                // On appelle le service d'ajout
                $fichier = $pictureService->add($image, $folder, 300, 300);

                $img = new Images();
                $img->setName($fichier);
                $trick->addImage($img);
            }
            //création du slug 
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);

            //stockage des infos
            $em->persist($trick);
            $em->flush();
            //message succes
            $this->addFlash('success', 'Figure ajoutée avec succès');
            //redirection
            return $this->redirectToRoute('app_main');
        }

        return $this->render('user/figures/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/modification/{id}', name: 'edit')]
    public function edit(
        Tricks $trick,
        Request $request, 
        EntityManagerInterface $em, 
        SluggerInterface $slugger,
        PictureService $pictureService
    ): Response
    {
        //creation formulaire 
        $form = $this->createForm(TricksFormType::class, $trick);
        // envoi de la requete du form
        $form->handleRequest($request);
        // verifie si form  soumis et valide
        if($form->isSubmitted() && $form->isValid())
        {

            // On récupère les images
            $images = $form->get('images')->getData();

            foreach($images as $image){
                // On définit le dossier de destination
                $folder = 'tricks';

                // On appelle le service d'ajout
                $fichier = $pictureService->add($image, $folder, 300, 300);

                $img = new Images();
                $img->setName($fichier);
                $trick->addImage($img);
            }
            //création du slug 
            $slug = $slugger->slug($trick->getName());
            $trick->setSlug($slug);

            //stockage des infos
            $em->persist($trick);
            $em->flush();

            //message succes
            $this->addFlash('success', 'Produit modifié avec succès');
            //redirection
            return $this->redirectToRoute('app_main');
        }

        return $this->render('user/figures/edit.html.twig', [
            'form' => $form->createView(),
            'trick' => $trick
        ]);
    }

    #[Route('/suppression/{id}', name: 'delete')]
    public function delete(Tricks $trick): Response
    {

        return $this->render('user/figures/delete.html.twig', [
            'controller_name' => 'suppression_figure',
        ]);
    }

    #[Route('/suppression/image/{id}', name: 'delete_image', methods: ['DELETE'])]
    public function deleteImage(Images $image, Request $request, EntityManagerInterface $em, PictureService $pictureService): JsonResponse
    {
        // On récupère le contenu de la requête
        $data = json_decode($request->getContent(), true);

        if($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])){
            // Le token csrf est valide
            // On récupère le nom de l'image
            $nom = $image->getName();

            if($pictureService->delete($nom, 'tricks', 300, 300)){
                // On supprime l'image de la base de données
                $em->remove($image);
                $em->flush();

                return new JsonResponse(['success' => true], 200);
            }
            // La suppression a échoué
            return new JsonResponse(['error' => 'Erreur de suppression'], 400);
        }

        return new JsonResponse(['error' => 'Token invalide'], 400);
    }

    #[Route('/{slug}', name: 'details')]
    public function details(Tricks $trick): Response
    {
        return $this->render('tricks/details.html.twig', compact('trick'));
    }

    
}
