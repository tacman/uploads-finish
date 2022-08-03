<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleFormType;
use App\Repository\ArticleRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleAdminController extends BaseController
{
    /**
     * @ IsGranted("ROLE_ADMIN_ARTICLE")
     */
    #[Route(path: '/admin/article/upload', name: 'admin_article_upload')]
    public function upload(EntityManagerInterface $em, Request $request, UploaderHelper $uploaderHelper)
    {
        $dir = '/home/tac/Pictures/oberon';
        $finder = new Finder();
        foreach ($finder->in($dir)->files() as $file) {
            $uploaderHelper->uploadArticleImage(new File($file->getRealPath()), $file->getFilename());
            dd($file);
        }
        $article = (new Article())
            ->setAuthor($this->getUser());

    }

        /**
     * @IsGranted("ROLE_ADMIN_ARTICLE")
     */
    #[Route(path: '/admin/article/new', name: 'admin_article_new')]
    public function new(EntityManagerInterface $em, Request $request, UploaderHelper $uploaderHelper)
    {
        $article = (new Article())
            ->setAuthor($this->getUser())
            ;
        $form = $this->createForm(ArticleFormType::class, $article);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Article $article */
            $article = $form->getData();
//            $article->setSlug(Urlizer::urlize($article->getTitle())); // @todo: this should be automatic.

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();

            if ($uploadedFile) {
                $newFilename = $uploaderHelper->uploadArticleImage($uploadedFile, $article->getImageFilename());
                $article->setImageFilename($newFilename);
            }

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article Created! Knowledge is power!');

            return $this->redirectToRoute('admin_article_list');
        }
        return $this->render('article_admin/new.html.twig', [
            'articleForm' => $form->createView()
        ]);
    }

    /**
     * @IsGranted("MANAGE", subject="article")
     */
    #[Route(path: '/admin/article/{id}/edit', name: 'admin_article_edit')]
    public function edit(Article $article, Request $request, EntityManagerInterface $em, UploaderHelper $uploaderHelper)
    {
        $form = $this->createForm(ArticleFormType::class, $article, [
            'include_published_at' => true
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form['imageFile']->getData();
            if ($uploadedFile) {
                $newFilename = $uploaderHelper->uploadArticleImage($uploadedFile, $article->getImageFilename());
                $article->setImageFilename($newFilename);
            }

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'Article Updated! Inaccuracies squashed!');

            return $this->redirectToRoute('admin_article_edit', [
                'id' => $article->getId(),
            ]);
        }
        return $this->render('article_admin/edit.html.twig', [
            'articleForm' => $form->createView(),
            'article' => $article,
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     */
    #[Route(path: '/admin/article/location-select', name: 'admin_article_location_select')]
    public function getSpecificLocationSelect(Request $request)
    {
        // a custom security check
        if (!$this->isGranted('ROLE_ADMIN_ARTICLE') && $this->getUser()->getArticles()->isEmpty()) {
            throw $this->createAccessDeniedException();
        }
        $article = new Article();
        $article->setLocation($request->query->get('location'));
        $form = $this->createForm(ArticleFormType::class, $article);
        // no field? Return an empty response
        if (!$form->has('specificLocationName')) {
            return new Response(null, 204);
        }
        return $this->render('article_admin/_specific_location_name.html.twig', [
            'articleForm' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN_ARTICLE")
     */
    #[Route(path: '/admin/article', name: 'admin_article_list')]
    public function list(ArticleRepository $articleRepo)
    {
        $articles = $articleRepo->findAll();
        return $this->render('article_admin/list.html.twig', [
            'articles' => $articles,
        ]);
    }
}
