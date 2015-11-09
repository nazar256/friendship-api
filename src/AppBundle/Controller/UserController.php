<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Base\RestController;
use AppBundle\Document\User;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserController
 * @package AppBundle\Controller
 */
class UserController extends RestController
{

    /** @var string */
    protected $formName = 'user';

    /** @var string */
    protected $entityName = 'AppBundle:User';

    /**
     * @param User $user
     * @return User
     */
    public function getAction(User $user)
    {
        return $user;
    }

    /**
     * @Post("/register")
     * @View(statusCode=201, templateVar="user", template="json")
     * @param Request $request
     * @return User
     */
    public function postAction(Request $request)
    {
        return $this->createResource($request);
    }
}
