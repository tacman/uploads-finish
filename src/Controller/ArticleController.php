<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\SlackClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ArticleController extends AbstractController
{
    public function __construct(
        /**
         * Currently unused: just showing a controller with a constructor!
         */
        private readonly bool $isDebug
    )
    {
    }

    #[Route(path: '/', name: 'app_homepage')]
    public function homepage(ArticleRepository $repository)
    {
        $articles = $repository->findAllPublishedOrderedByNewest();
        return $this->render('article/homepage.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route(path: '/news/{slug}', name: 'article_show')]
    public function show(Article $article)
    {
        if ($article->getSlug() === 'khaaaaaan') {
//            $slack->sendMessage('Kahn', 'Ah, Kirk, my old friend...');
        }
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route(path: '/news/{slug}/heart', name: 'article_toggle_heart', methods: ['POST'])]
    public function toggleArticleHeart(Article $article, LoggerInterface $logger, EntityManagerInterface $em)
    {
        $article->incrementHeartCount();
        $em->flush();
        $logger->info('Article is being hearted!');
        return new JsonResponse(['hearts' => $article->getHeartCount()]);
    }
}
