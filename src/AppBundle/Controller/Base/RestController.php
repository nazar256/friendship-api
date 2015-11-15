<?php

namespace AppBundle\Controller\Base;

use AppBundle\Handler\ResourceHandler;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for REST controllers
 */
abstract class RestController extends FOSRestController
{
    /**
     * Form alias name for resource entity of this controller
     * @var string
     */
    protected $formName = 'form';

    /**
     * Doctrine repository name for resource entity of this controller
     * @var string
     */
    protected $entityName = null;

    /**
     * @param Request $request
     * @return object
     */
    protected function createResource(Request $request)
    {
        $entityOrForm = $this->getHandler()
                             ->post($request->request->all());

        return $entityOrForm;
    }

    /**
     * @return ResourceHandler
     */
    protected function getHandler()
    {
        /** @var ResourceHandler $resourceHandler */
        $resourceHandler = $this->get(ResourceHandler::SERVICE_ID);

        return $resourceHandler
            ->setDocumentName($this->entityName)
            ->setType($this->formName);
    }
}