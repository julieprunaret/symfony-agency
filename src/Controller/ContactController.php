<?php

namespace App\Controller;

use DateTimeImmutable;
use App\Entity\Contact;
use App\Form\ContactType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, ManagerRegistry $doctrine, MailerInterface $mailer): Response
    {
        //documentation : https://symfony.com/doc/5.4/mailer.html#twig-html-css
        //
        //ETAPE 1 : on crée le contact
        //
        $contact = new Contact;//on crée le contact
        $contact->setSentAt(new DateTimeImmutable());//on met la date par défaut du jour

        //
        //ETAPE 2 : on crée le formulaire
        //
        $formContact = $this->createForm(ContactType::class, $contact);

        //
        //ETAPE 3 : on vérifi la soumission du formulaire
        //
        $formContact->handleRequest($request);
        if($formContact->isSubmitted() && $formContact->isValid())
        {
            //ETAPE 3.1 : on enregistre dans la bdd
            $em = $doctrine->getManager();
            $em->persist($contact);
            $em->flush();
            //ETAPE 3.2 : on envoit l'email
            $email = (new TemplatedEmail())
                    ->from(($contact->getEmail()))//on récupère l'email de la personne (issu du form)
                    ->to('votreContact@societe.com')
                    ->subject('Formulaire contact!')
                    ->htmlTemplate('emails/contact.html.twig')//on va cheercher le tempate dans le dossier emails
                    ->context([
                        'contact' => $contact
                    ]);
            $mailer->send($email);

            $this->addFlash('success_email', 'Votre email a bien été envoyé');
        }


        return $this->render('contact/index.html.twig', [
            'formContact' => $formContact->createView()
        ]);
    }
}
