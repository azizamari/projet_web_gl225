<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    #[Route('/products', name: 'app_products')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }
    
    #[Route('/products/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
    
    #[Route('/admin/products', name: 'app_admin_products')]
    public function adminIndex(ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $products = $productRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('admin/product/index.html.twig', [
            'products' => $products,
        ]);
    }
    
    #[Route('/admin/products/new', name: 'app_admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    
                    $product->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading the image.');
                }
            }
            
            $entityManager->persist($product);
            $entityManager->flush();
            
            $this->addFlash('success', 'Product created successfully!');
            
            return $this->redirectToRoute('app_admin_products');
        }
        
        return $this->render('admin/product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
    
    #[Route('/admin/products/{id}/edit', name: 'app_admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    
                    // Remove old image if it exists
                    $oldFilename = $product->getImageFilename();
                    if ($oldFilename) {
                        $oldImagePath = $this->getParameter('product_images_directory').'/'.$oldFilename;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $product->setImageFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading the image.');
                }
            }
            
            $product->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            
            $this->addFlash('success', 'Product updated successfully!');
            
            return $this->redirectToRoute('app_admin_products');
        }
        
        return $this->render('admin/product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }
    
    #[Route('/admin/products/{id}/delete', name: 'app_admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            // Remove product image if it exists
            $filename = $product->getImageFilename();
            if ($filename) {
                $imagePath = $this->getParameter('product_images_directory').'/'.$filename;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            $entityManager->remove($product);
            $entityManager->flush();
            
            $this->addFlash('success', 'Product deleted successfully!');
        }
        
        return $this->redirectToRoute('app_admin_products');
    }
}
