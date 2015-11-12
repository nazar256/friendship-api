<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Base\RestController;
use AppBundle\Document\User;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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
     * @ApiDoc(
     *  resource=true,
     *  description="Returns user data",
     *  output = {
     *      "class"="AppBundle\Document\User",
     *      "parsers"={"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *  },
     *  statusCodes={
     *      404="Not found"
     *  }
     * )
     * @param User $user
     * @return User
     */
    public function getAction(User $user)
    {
        return $user;
    }

    /**
     * @Post("/register")
     * @ApiDoc(
     *  resource=true,
     *  description="Creates a new user",
     *  input = {
     *      "class" = "user",
     *      "options" = {"method" = "POST"},
     *      "name" = ""
     *  },
     *  output = {
     *      "class"="AppBundle\Document\User",
     *      "parsers"={"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *  },
     *  statusCodes={
     *      201="User successfully created",
     *      422="Validation failed"
     *  }
     * )
     * @View(statusCode=201, templateVar="user", template="json")
     * @param Request $request
     * @return User
     */
    public function postAction(Request $request)
    {
        return $this->createResource($request);
    }

    /**
     * @Post("/{user}/friendship/request")
     * @ApiDoc(
     *  resource=false,
     *  description="Creates a friendship request of current user to desired one",
     *  statusCodes={
     *      401 = "Unauthorized - log in first",
     *      204 = "Successfully requested"
     *  }
     * )
     * @param User $user
     */
    public function requestFriendshipAction(User $user)
    {
        $currentUser = $this->getUser();
        if ( !$currentUser instanceof User) {
            throw new UnauthorizedHttpException('Probably you are not authorized');
        }

        $user->addRequest($currentUser->getId());
        $dm = $this->get('doctrine.odm.mongodb.document_manager');
        $dm->flush();
    }
}
