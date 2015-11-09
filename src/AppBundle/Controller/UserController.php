<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Base\RestController;
use AppBundle\Document\User;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
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
     * @ApiDoc(
     *  resource=true,
     *  description="Creates a new user",
     *  input = {
     *      "class" = "user",
     *      "options" = {"method" = "POST"},
     *      "name" = ""
     *  },
     *  output = "AppBundle\Document\User",
     *  statusCodes={
     *      201="User successfully created",
     *      422="Validation failed"
     *  }
     * )
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
