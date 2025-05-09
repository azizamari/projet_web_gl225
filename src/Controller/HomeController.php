<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        // Get featured products (newest 8 products)
        $featuredProducts = $productRepository->findBy([], ['createdAt' => 'DESC'], 8);
        
        return $this->render('home/index.html.twig', [
            'featuredProducts' => $featuredProducts,
        ]);
    }
}
