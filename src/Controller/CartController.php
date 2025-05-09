<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    #[Route('', name: 'app_cart')]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cart = null;
        
        if ($user) {
            $cart = $cartRepository->findOneBy(['user' => $user]);
        }
        
        // Explicitly pass an empty cart to avoid template errors
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
            'cartItems' => $cart ? $cart->getCartItems() : [], // Ensure cartItems is always defined
        ]);
    }
    
    #[Route('/add/{id}', name: 'app_cart_add')]
    public function add(
        Request $request, 
        Product $product, 
        CartRepository $cartRepository, 
        CartItemRepository $cartItemRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check if user is logged in
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to add items to cart.');
            return $this->redirectToRoute('app_login');
        }
        
        // Get or create user's cart
        $cart = $cartRepository->findOneBy(['user' => $user]);
        
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
        }
        
        // Check if product is already in cart
        $cartItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $cartItem = $item;
                break;
            }
        }
        
        $quantity = $request->query->getInt('quantity', 1);
        
        if ($cartItem) {
            // Update quantity if product already in cart
            $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
        } else {
            // Add new cart item
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cart->addCartItem($cartItem);
            $entityManager->persist($cartItem);
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Product added to cart!');
        
        $referer = $request->headers->get('referer');
        return $referer ? $this->redirect($referer) : $this->redirectToRoute('app_home');
    }
    
    #[Route('/remove/{id}', name: 'app_cart_remove')]
    public function remove(
        CartItem $cartItem, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check if user is the owner of the cart
        $user = $this->getUser();
        
        if (!$user || $cartItem->getCart()->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You cannot modify this cart.');
        }
        
        $entityManager->remove($cartItem);
        $entityManager->flush();
        
        $this->addFlash('success', 'Item removed from cart.');
        
        return $this->redirectToRoute('app_cart');
    }
    
    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(
        Request $request,
        CartItem $cartItem, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check if user is the owner of the cart
        $user = $this->getUser();
        
        if (!$user || $cartItem->getCart()->getUser()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('You cannot modify this cart.');
        }

        // Validate CSRF token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('update-item-' . $cartItem->getId(), $token)) {
            $this->addFlash('error', 'Invalid form submission.');
            return $this->redirectToRoute('app_cart');
        }
        
        $quantity = (int) $request->request->get('quantity', 1);
        
        if ($quantity <= 0) {
            $entityManager->remove($cartItem);
            $this->addFlash('success', 'Item removed from cart.');
        } else {
            $cartItem->setQuantity($quantity);
            $this->addFlash('success', 'Cart updated successfully.');
        }
        
        $entityManager->flush();
        
        return $this->redirectToRoute('app_cart');
    }
    
    #[Route('/checkout', name: 'app_cart_checkout')]
    public function checkout(CartRepository $cartRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);
        
        // Check if cart exists first, then check items
        if (!$cart) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }
        
        if (count($cart->getCartItems()) === 0) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }
        
        return $this->render('cart/checkout.html.twig', [
            'cart' => $cart,
            'cartItems' => $cart->getCartItems(), // Pass items separately
        ]);
    }
}
