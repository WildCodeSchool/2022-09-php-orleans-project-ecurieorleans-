<?php

namespace App\Controller;

use App\Model\EventManager;

class EventAdminController extends AbstractController
{
    private const AUTHORIZED_EXTENSIONS = ['image/jpg', 'image/jpeg', 'image/webp', 'image/png', 'image/gif'];
    private const MAX_FILE_SIZE = 200000;
    public const UPLOADS_DIR_LOCATION =  './assets/uploads/';
    public function index(): string
    {
        $errors =  [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $events = array_map('trim', $_POST);
            $errorsForm = $this->errorsForm($events);
            $errorsFiles = $this->errorsFile();
            $errors = array_merge($errorsFiles, $errorsForm);
            if (empty($errors)) {
                if (empty($_FILES['eventImage']['name'])) {
                    $uniqueFiles = null;
                } else {
                    $uniqueFiles = uniqid() . $_FILES['eventImage']['name'];
                }
                move_uploaded_file($_FILES['eventImage']['name'], self::UPLOADS_DIR_LOCATION);
                $addCard = new EventManager();
                $addCard->addCard($events, $uniqueFiles);
                return $this->twig->render('AdminEvent/AddAdminEvent.html.twig');
            }
            return $this->twig->render(
                'AdminEvent/AddAdminEvent.html.twig',
                ['errors' => $errors, 'events' => $events]
            );
        }
        return $this->twig->render('AdminEvent/AddAdminEvent.html.twig');
    }

    public function errorsForm(array $event)
    {
        $errors = [];
        $sport =  [];
        foreach ($this->sections as $section) {
            $sport[] = $section['id'];
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($event['raceName'])) {
                $errors[] = "Le titre de course est obligatoire .";
            }
            if (empty($event['date'])) {
                $errors[] = "La date est obligatoire .";
            }
            $maxLengthRaceName = 255;
            if (strlen($event['raceName']) > $maxLengthRaceName) {
                $errors[] = "Le titre de la course est trop long max " . $maxLengthRaceName . ".";
            }
            $event['sportSelect'] = (int) $event['sportSelect'];
            if (!in_array($event['sportSelect'], $sport)) {
                $errors[] = "Le type de course selectionné n'existe pas .";
            }
            return $errors;
        }
    }

    public function errorsFile()
    {
        $errors = [];
        $extensions = [];
        $nameFile = $_FILES['eventImage']['tmp_name'];
        $extension = pathinfo($_FILES['eventImage']['name'], PATHINFO_EXTENSION);
        foreach (self::AUTHORIZED_EXTENSIONS as $extension) {
            $extension = substr($extension, 6);
            $extensions[] = $extension . ' ';
        }

        if (!empty($nameFile)) {
            $mime = mime_content_type($nameFile);
        } else {
            $mime = null;
        }

        if (!empty($nameFile)) {
            if ($_FILES['eventImage']['error'] != 0) {
                $errors[] = 'Problème de chargement de l\'image.';
            }
            if ((!in_array($mime, self::AUTHORIZED_EXTENSIONS))) {
                $errors[] = 'Veuillez sélectionner une image de type ' . implode(',', $extensions) . ' !';
            }
        }

        if (file_exists($nameFile) && filesize($nameFile) > self::MAX_FILE_SIZE) {
            $errors[] = "Votre fichier doit faire moins de 200ko !";
        }
        return $errors;
    }
}
