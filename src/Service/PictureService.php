<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PictureService
{
    private $params;

    public function __construct(ParameterBagInterface $params) //ParameterBagInterface va chercher les infos qui sont dans mes paramètres dans services.yaml
    {
        $this->params = $params;
    }

    public function add(UploadedFile $picture, ?string $folder = '', ?int $width = 250, ?int $height = 250) // retailler les images par défaut à 250 par 250
    {
        // On donne un nouveau nom à l'image et on le stock en webp pour l'optimisation de l'espace 
        $fichier = md5(uniqid(rand(), true)) . '.webp';

        // On récupère les infos de l'image largeur/hauteur
        $picture_infos = getimagesize($picture);
        // dd($picture_infos);

        if($picture_infos === false){ //Si on n'accède pas aux infos de l'image

            throw new Exception('Format d\'image incorrect');
        }

        // On vérifie le format de l'image 
        // switch dans picture_infos dans le tableau mime
        switch($picture_infos['mime']){
            case 'image/png':
                $picture_source = imagecreatefrompng($picture); 
                break;
            case 'image/jpeg':
                $picture_source = imagecreatefromjpeg($picture);
                break;
            case 'image/webp':
                $picture_source = imagecreatefromwebp($picture);
                break;
            default:
                throw new Exception('Format d\'image incorrect'); // Si on ne trouve pas un de ces formats
        }

        // On recadre l'image
        // On récupère les dimensions
        $imageWidth = $picture_infos[0];
        $imageHeight = $picture_infos[1];

        // On vérifie l'orientation de l'image
        switch ($imageWidth <=> $imageHeight){ // <=> spaceship : triple comparaison < ou = ou > resultant -1 ou 0 ou 1
            case -1: // portrait : largeur inférieur à la hauteur
                $squareSize = $imageWidth;
                $src_x = 0; // x pleine largeur
                $src_y = ($imageHeight - $squareSize) / 2; // et ici on déscend à la moitié de la hauteur pour enlever le haut et le bas 
            break;
            case 0: // carré : largeur égale à la hauteur
                $squareSize = $imageWidth;
                $src_x = 0;
                $src_y = 0;
            break;
            case 1: // paysage 
                $squareSize = $imageHeight;
                $src_x = ($imageWidth - $squareSize) / 2; // on divise la largeur par 2
                $src_y = 0;
            break;
        }

        // On crée une nouvelle image "vierge"
        // $width, $height hauteur et largeur demandé par notre utilisateur
        $resized_picture = imagecreatetruecolor($width, $height); 
        //imagecopyresampled() copie une zone rectangulaire de l'image src_im vers l'image dst_im. Durant la copie, la zone est rééchantillonnée de manière à conserver la clarté de l'image durant une réduction. prendra une forme rectangulaire src_image d'une largeur de src_width et d'une hauteur src_height à la position (src_x,src_y) et le placera dans une zone rectangulaire dst_image d'une largeur de dst_width et d'une hauteur de dst_height à la position (dst_x,dst_y). 
        imagecopyresampled($resized_picture, $picture_source, 0, 0, $src_x, $src_y, $width, $height, $squareSize, $squareSize); 

        $path = $this->params->get('images_directory') . $folder;

        // On crée le dossier de destination s'il n'existe pas
        if(!file_exists($path . '/mini/')){
            mkdir($path . '/mini/', 0755, true);
        }

        // On stocke l'image recadrée
        //imagewebp Affiche ou sauvegarde une version WebP de l'image fournie. 
        imagewebp($resized_picture, $path . '/mini/' . $width . 'x' . $height . '-' . $fichier); 

        //on déplace l'image dans le bon dossier
        $picture->move($path . '/', $fichier);

        return $fichier;
    }

    // Suppression d'une image 
    // 

    public function delete(string $fichier, ?string $folder = '', ?int $width = 250, ?int $height = 250)
    {
        if($fichier !== 'default.webp'){ // ne pas supprimer mon fichier par défaut
            $success = false;
            $path = $this->params->get('images_directory') . $folder;

            $mini = $path . '/mini/' . $width . 'x' . $height . '-' . $fichier;

            if(file_exists($mini)){
                unlink($mini);
                $success = true;
            }

            $original = $path . '/' . $fichier;

            if(file_exists($original)){
                unlink($original);
                $success = true;
            }
            return $success;
        }
        return false;
    }
}