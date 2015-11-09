<?php

namespace AppBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use AppBundle\Document\User;

/**
 * Class ResourceHandler
 * Handler for resource logic for REST controller (processes forms for controller)
 */
class ResourceHandler
{
    const SERVICE_ID = 'app_bundle.resource';

    /** @var  ObjectManager */
    private $objectManager;

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var  ObjectRepository */
    private $repository;

    /** @var  string */
    private $type;

    /** @var TokenStorage */
    private $tokenStorage;

    /**
     * @param ObjectManager     $objectManager
     * @param FormFactoryInterface $formFactory
     * @param TokenStorage         $tokenStorage
     */
    public function __construct(
        ObjectManager $objectManager,
        FormFactoryInterface $formFactory,
        TokenStorage $tokenStorage
    ) {
        $this->objectManager = $objectManager;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param string $entityName
     * @return  $this
     */
    public function setDocumentName($entityName)
    {
        $this->repository = $this->objectManager->getRepository($entityName);

        return $this;
    }

    /**
     * @param AbstractType|string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param integer $entityId
     * @return null|object
     */
    public function get($entityId)
    {
        $entity = $this->getRepository()
                       ->findOneBy(['id' => $entityId, 'owner' => $this->getCurrentUserId()]);
        if ( !$entity) {
            throw new NotFoundHttpException("Object with id $entityId was not found");
        }

        return $entity;
    }

    /**
     * Repository should be accessed only by this method, not directly
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        if ( !$this->repository) {
            throw new NoSuchPropertyException('You must set entity name first to use this handler');
        }

        return $this->repository;
    }

    /**
     * @return null
     */
    private function getCurrentUserId()
    {
        $currentUser = $this->tokenStorage->getToken()
                                          ->getUser();
        $currentUserId = ($currentUser instanceof User) ? $currentUser->getId() : null;

        return $currentUserId;
    }

    /**
     * @return object[]|null
     * @throws NotFoundHttpException
     */
    public function getList()
    {
        $entities = $this->getRepository()
                         ->findBy(['owner' => $this->getCurrentUserId()]);
        if (empty($entities)) {
            throw new NotFoundHttpException("No object was found");
        }

        return $entities;
    }

    /**
     * @param object $entity
     * @param array  $parameters
     * @return object
     */
    public function put($entity, array $parameters)
    {
        if ( !$entity->getOwner() == $this->getCurrentUserId()) {
            throw new AccessDeniedHttpException('Sorry, you do not have permissions to edit this object');
        }

        return $this->processForm($entity, $parameters, 'PUT');
    }

    /**
     * @param object $document
     * @param array  $parameters
     * @param string $method
     * @return object|Form
     */
    protected function processForm($document, array $parameters, $method = "PUT")
    {
        /** @var Form $form */
        $form = $this->formFactory->create($this->type, $document, ['method' => $method]);

        $form->submit($parameters, 'PATCH' !== $method);

        if ($form->isValid()) {
            $document = $form->getData();
            $this->objectManager->persist($document);
            $this->objectManager->flush();

            return $document;
        }

        return $form;
    }

    /**
     * @param object $entity
     * @param array  $parameters
     * @return object
     */
    public function patch($entity, array $parameters)
    {
        if ( !$entity->getOwner() == $this->getCurrentUserId()) {
            throw new AccessDeniedHttpException('Sorry, you do not have permissions to edit this object');
        }

        return $this->processForm($entity, $parameters, 'PATCH');
    }

    /**
     * @param array $parameters
     * @return object
     */
    public function post(array $parameters)
    {
        $className = $this->getRepository()
                          ->getClassName();

        return $this->processForm(new $className, $parameters, 'POST');
    }
}