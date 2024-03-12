<?php

namespace App\Controller;

use App\Entity\Cliente;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ClientesController extends AbstractController
{
    #[IsGranted("ROLE_USER")]
    #[Route('/clientes/add', name: 'addCliente')]
    public function añadirCliente(EntityManagerInterface $entityManager, Request $request): Response
    {
        $cliente = new Cliente();
        $formularioCliente = $this->createFormBuilder($cliente)
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'required' => true,
            ])
            ->add('apellidos', TextType::class, [
                'label' => 'Apellidos',
                'required' => true,
            ])
            ->add('telefono', TelType::class, [ // Cambia el tipo de campo a TelType
                'label' => 'Teléfono',
                'required' => true,
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'required' => true,
            ])
            ->add('Guardar', SubmitType::class, ['label' => 'Guardar'])
            ->getForm();

        $formularioCliente->handleRequest($request);
        if ($formularioCliente->isSubmitted() && $formularioCliente->isValid()) {
            $cliente = $formularioCliente->getData();
            $entityManager->persist($cliente);
            $entityManager->flush();
            $this->addFlash('success', 'Cliente añadido correctamente.');

            return $this->redirectToRoute('verTodosLosClientes');
        }

        return $this->render('clientes/añadirCliente.html.twig', [
            'formularioCliente' => $formularioCliente->createView(),
        ]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/verClientes', name: 'verTodosLosClientes')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $clientes = $entityManager->getRepository(Cliente::class)->findAll();
        return $this->render('clientes/verClientes.html.twig', [
            'clientes' => $clientes
        ]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/cliente/{id}', name: 'verCliente')]
    public function verCliente(Cliente $cliente): Response
    {
        return $this->render('clientes/verCliente.html.twig', [
            'cliente' => $cliente,
            'incidencias' => $cliente->getIncidencias()
        ]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/verClientes/{id}', name: 'deleteCliente')]
    public function deleteCliente(Cliente $cliente, EntityManagerInterface $entityManager): Response
    {
        // Obtener todas las incidencias asociadas al cliente
        $incidencias = $cliente->getIncidencias();

        // Eliminar cada incidencia asociada
        foreach ($incidencias as $incidencia) {
            $entityManager->remove($incidencia);
        }

        // Eliminar el cliente
        $entityManager->remove($cliente);

        // Persistir los cambios en la base de datos
        $entityManager->flush();

        $this->addFlash('success', 'Cliente eliminado correctamente.');

        return $this->redirectToRoute('verTodosLosClientes');
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/cliente/edit/{id}', name: 'editarCliente')]
    public function editarCliente(Cliente $cliente, EntityManagerInterface $entityManager, Request $request): Response
    {
        $formularioCliente = $this->createFormBuilder($cliente)
            ->add('nombre', TextType::class, [
                'label' => 'Nombre',
                'required' => true,
            ])
            ->add('apellidos', TextType::class, [
                'label' => 'Apellidos',
                'required' => true,
            ])
            ->add('telefono', TelType::class, [
                'label' => 'Teléfono',
                'required' => true,
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección',
                'required' => true,
            ])
            ->add('Guardar', SubmitType::class, ['label' => 'Guardar'])
            ->getForm();

        $formularioCliente->handleRequest($request);
        if ($formularioCliente->isSubmitted() && $formularioCliente->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Cliente editado correctamente.');
            return $this->redirectToRoute('verTodosLosClientes');
        }

        return $this->render('clientes/editarCliente.html.twig', [
            'formularioCliente' => $formularioCliente->createView(),
        ]);
    }
}
