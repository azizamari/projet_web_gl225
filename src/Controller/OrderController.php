<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/admin/orders', name: 'app_admin_orders')]
    public function adminOrders(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $orders = $orderRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
        ]);
    }
    
    #[Route('/admin/order/{id}/status', name: 'app_admin_order_status_update', methods: ['POST'])]
    public function updateOrderStatus(Order $order, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $status = $request->request->get('status');
        
        // Validate status
        $validStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED
        ];
        
        if (!in_array($status, $validStatuses)) {
            $this->addFlash('error', 'Invalid status');
            return $this->redirectToRoute('app_admin_orders');
        }
        
        $order->setStatus($status);
        $entityManager->flush();
        
        $this->addFlash('success', 'Order status updated successfully');
        return $this->redirectToRoute('app_admin_orders');
    }
    #[Route('/create', name: 'app_order_create', methods: ['POST'])]
    public function create(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);
        
        if (!$cart || count($cart->getCartItems()) === 0) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('app_cart');
        }
        
        // Create new order
        $order = new Order();
        $order->setUser($user);
        $order->setShippingAddress($request->request->get('shipping_address'));
        $order->setPaymentMethod($request->request->get('payment_method'));
        
        // Add cart items to order
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrderRef($order);
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPrice($cartItem->getProduct()->getPrice());
            
            $order->addOrderItem($orderItem);
            
            // Update product stock
            $product = $cartItem->getProduct();
            $newStock = max(0, $product->getStock() - $cartItem->getQuantity());
            $product->setStock($newStock);
        }
        
        // Calculate total price
        $order->calculateTotalPrice();
        
        // Save order to database
        $entityManager->persist($order);
        
        // Clear the cart
        foreach ($cart->getCartItems() as $cartItem) {
            $entityManager->remove($cartItem);
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Your order has been placed successfully! Order reference: ' . $order->getReference());
        
        return $this->redirectToRoute('app_order_success', ['reference' => $order->getReference()]);
    }
    
    #[Route('/success/{reference}', name: 'app_order_success')]
    public function success(string $reference, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $order = $orderRepository->findByReference($reference);
        
        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Order not found');
        }
        
        return $this->render('order/success.html.twig', [
            'order' => $order,
        ]);
    }
    
    #[Route('/my-orders', name: 'app_my_orders')]
    public function myOrders(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $user = $this->getUser();
        $orders = $orderRepository->findByUser($user);
        
        return $this->render('order/my_orders.html.twig', [
            'orders' => $orders,
        ]);
    }
    
    #[Route('/{reference}', name: 'app_order_details')]
    public function details(string $reference, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        $order = $orderRepository->findByReference($reference);
        
        if (!$order || $order->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Order not found');
        }
        
        return $this->render('order/details.html.twig', [
            'order' => $order,
        ]);
    }
}
