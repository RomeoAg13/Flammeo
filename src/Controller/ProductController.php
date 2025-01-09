<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ProductController extends AbstractController
{
    
    #[Route('/', name: 'homepage')]
    public function homepage(ProductRepository $productRepository, Request $request): Response
    {
        $session = $request->getSession();
        $blase = $session->get('name');
        $connecte = $session->get('connecte');	
        $products = $productRepository->findAll();
        return $this->render('homepage/index.html.twig', [
            'controller_name' => 'ProductController',
            'products' => $products,
            'name' => $blase,
            'connecte' => $connecte
        ]);
    }

    #[Route('/product/add', name: 'product_add', methods : ['POST'])]
    public function add(Request $request, ProductRepository $productRepository): Response
    {	
        $data = json_decode($request->getContent(), associative:true);

        $name = $data['name'];
        $price = $data['price'];

        $product = new Product($name, $price);

        $productRepository->save($product);

        return $this->redirectToRoute('product_list');

    }

    #[Route('/product/update', name:'product_add', methods: ['PUT'])]
    public function update(Request $request, ProductRepository $productRepository, EntityManager $entityManager) : Response
    {
        $data = json_decode($request-> getContent(), true);
        $id = $data['id'];
        $name = $data['name'];
        $price = $data['price'];

        $product = $productRepository->find($id);
        if ($product){
            $product->setName($name);
            $product->setPrice($price);

            $entityManager->flush();

        }
        return $this-> redirectToRoute('product_list');
    }

    #[Route('/product/delete/{id}', name: 'product_delete', methods: ['DELETE'])]
    public function delete($id, ProductRepository $productRepository, EntityManager $entityManager): Response
    {
        $product = $productRepository->find($id);
        if ($product){
            $entityManager->remove($product);
            $entityManager->flush();
        }
        return $this->redirectToRoute('product_list');
    }


    #[Route('/products', name: 'all_product_list')]
    public function allProducts(ProductRepository $productRepository, Request $request): Response
    {
        $session = $request->getSession();
        $blase = $session->get('name');
        $connecte = $session->get('connecte');	
        $products = $productRepository->findAll();
        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
            'products' => $products,
            'name' => $blase,
            'connecte' => $connecte
        ]);
    }

    #[Route('/cart', name: 'cart_list')]
    public function cart(ProductRepository $productRepository, SessionInterface $session, Request $request): Response
    {
        $session = $request->getSession();
        $blase = $session->get('name');
        $connecte = $session->get('connecte');	
        $token = $request->headers->get('token');
        $cart = $session->get('cart', []);
        $products = $productRepository->findBy(['id' => $cart]);
        $totalPrice = 0;
        foreach ($products as $product) {
            $totalPrice += $product->getPrice();
        }
        if ($connecte) {
            return $this->render('cart/index.html.twig', [
                'controller_name' => 'ProductController',
                'token' => $token,
                'products' => $products,
                'totalPrice' => $totalPrice,
                'name' => $blase,
                'connecte' => $connecte
            ]);
        } else {
           return $this->redirectToRoute('login');
        }
        
    }
    
    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function addToCart($id, ProductRepository $productRepository, SessionInterface $session): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException("The product doesn't exists");
        }
        $cart = $session->get('cart', []);
        if (!in_array($id, $cart)) {
            $cart[] = $id;
        }
        $session->set('cart', $cart);
        return $this->redirectToRoute('cart_list');
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function removeFromCart(int $id, SessionInterface $session, Request $request): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', []);
        if (($key = array_search($id, $cart)) !== false) {
            unset($cart[$key]);
        }
        $session->set('cart', array_values($cart));
        return $this->redirectToRoute('cart_list');
    }


    #[Route('/details/{id}', name: 'detail_game')]
    public function detailGame($id, ProductRepository $productRepository, SessionInterface $session): Response
    {
        $product = $productRepository->find($id);
        if (!$product) {
            throw $this->createNotFoundException("The product doesn't exist");
        }

        $blase = $session->get('name');
        $connecte = $session->get('connecte');
        
        return $this->render('details/index.html.twig', [
            'product' => $product,
            'name' => $blase,
            'connecte' => $connecte
        ]);
    }

}