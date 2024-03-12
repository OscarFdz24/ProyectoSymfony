<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Incidencia;
use App\Entity\Cliente;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class IncidenciasController extends AbstractController
{
    #[IsGranted("ROLE_USER")]
    #[Route('/verIncidencias', name: 'verTodosLasIncidencias')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $incidencias = $entityManager->getRepository(Incidencia::class)
            ->createQueryBuilder('i')
            ->orderBy('i.fecha_creacion', 'DESC') // Ordenar por fecha de creación de forma descendente
            ->getQuery()
            ->getResult();

        return $this->render('incidencias/verIncidencias.html.twig', [
            'incidencias' => $incidencias
        ]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/incidencia/delete/{clienteId}/{id}', name: 'deleteIncidencia')]
    public function deleteIncidencia(int $clienteId, Incidencia $incidencia, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($incidencia);
        $entityManager->flush();
        $this->addFlash('success', 'Incidencia eliminada correctamente.');
        return $this->redirectToRoute('verCliente', ['id' => $clienteId]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/incidencia/edit/{clienteId}/{id}', name: 'editarIncidencia')]
    public function editarIncidencia(int $clienteId, Incidencia $incidencia, EntityManagerInterface $entityManager, Request $request): Response
    {
        $formularioIncidencia = $this->createFormBuilder($incidencia)
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'required' => true,
            ])
            ->add('estado', TextareaType::class, [
                'label' => 'Estado',
                'required' => true,
            ])
            // Fecha y prioridad no son campos modificables
            ->add('Guardar', SubmitType::class, ['label' => 'Guardar'])
            ->getForm();

        $formularioIncidencia->handleRequest($request);
        if ($formularioIncidencia->isSubmitted() && $formularioIncidencia->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Incidencia editada correctamente.');
            // Redirigir de vuelta a la vista de detalles del cliente junto con sus incidencias
            return $this->redirectToRoute('verCliente', ['id' => $clienteId]);
        }

        return $this->render('incidencias/editarIncidencia.html.twig', [
            'formularioIncidencia' => $formularioIncidencia->createView(),
        ]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/cliente/{id}/add_incidencia', name: 'addIncidencia')]
    public function añadirIncidencia(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        $cliente = $entityManager->getRepository(Cliente::class)->find($id);

        if (!$cliente) {
            throw $this->createNotFoundException('Cliente no encontrado');
        }

        $incidencia = new Incidencia();
        // Establecer la fecha actual en la incidencia
        $incidencia->setFechaCreacion(new \DateTime());
        $formularioIncidencia = $this->createFormBuilder($incidencia)
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'required' => true,
            ])
            ->add('estado', TextType::class, [
                'label' => 'Estado',
                'required' => true,
            ])
            ->add('Guardar', SubmitType::class, ['label' => 'Guardar'])
            ->getForm();

        $formularioIncidencia->handleRequest($request);
        if ($formularioIncidencia->isSubmitted() && $formularioIncidencia->isValid()) {
            // Asigna el cliente a la incidencia antes de persistirla
            $incidencia->setCliente($cliente);

            $entityManager->persist($incidencia);
            $entityManager->flush();
            $this->addFlash('success', 'Incidencia añadido correctamente.');
            return $this->redirectToRoute('verCliente', ['id' => $id]);
        }

        return $this->render('incidencias/añadirIncidencia.html.twig', [
            'formularioIncidencia' => $formularioIncidencia->createView(),
            'cliente' => $cliente, // Pasa el cliente a la plantilla
        ]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/add_incidencia2', name: 'addIncidencia2')]
    public function añadirIncidencia2(EntityManagerInterface $entityManager, Request $request): Response
    {

        $incidencia = new Incidencia();
        // Establecer la fecha actual en la incidencia
        $incidencia->setFechaCreacion(new \DateTime());

        $formularioIncidencia = $this->createFormBuilder($incidencia)
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'required' => true,
            ])
            ->add('estado', TextType::class, [
                'label' => 'Estado',
                'required' => true,
            ])
            // Agregar campo de selección para elegir cliente
            ->add('cliente', EntityType::class, [
                'class' => Cliente::class,
                'choice_label' => 'nombre', // Cambiar a 'apellidos' si prefieres mostrar los apellidos
                'label' => 'Cliente',
                'placeholder' => 'Selecciona un cliente',
                'required' => true,
            ])
            ->add('Guardar', SubmitType::class, ['label' => 'Guardar'])
            ->getForm();

        $formularioIncidencia->handleRequest($request);
        if ($formularioIncidencia->isSubmitted() && $formularioIncidencia->isValid()) {
            // Asigna el cliente seleccionado a la incidencia antes de persistirla
            $incidencia->setCliente($formularioIncidencia->get('cliente')->getData());

            $entityManager->persist($incidencia);
            $entityManager->flush();
            $this->addFlash('success', 'Incidencia añadida correctamente.');
            return $this->redirectToRoute('verTodosLasIncidencias');
        }

        return $this->render('incidencias/agregarIncidencia.html.twig', [
            'formularioIncidencia' => $formularioIncidencia->createView()
        ]);
    }
    #[IsGranted("ROLE_USER")]
    #[Route('/incidencia/{id}', name: 'verIncidencia')]
    public function verIncidencia(Incidencia $incidencia): Response
    {
        return $this->render('incidencias/verIncidencia.html.twig', [
            'incidencia' => $incidencia
        ]);
    }
}
