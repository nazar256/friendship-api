<?php

/**
 * Class container
 * PHP version 5.6
 * @category Class
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     /api/users
 */

namespace AppBundle\Controller;

use AppBundle\Controller\Base\RestController;
use AppBundle\Document\User;
use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * REST controller
 * @category Controller
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     /api/users
 */
class UserController extends RestController
{
    /**
     * {@inheritdoc}
     */
    protected $formName = 'user';

    /**
     * {@inheritdoc}
     */
    protected $entityName = 'AppBundle:User';

    /**
     * Returns current user info
     *
     * @return User
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns user data",
     *  output = {
     *      "class"="AppBundle\Document\User",
     *      "parsers"={"Nelmio\ApiDocBundle\Parser\JmsMetadataParser"}
     *  },
     *  statusCodes={
     *      401="Unauthorized"
     *  }
     * )
     */
    public function getMeAction()
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new UnauthorizedHttpException('Probably you are not authorized');
        }

        return $currentUser;
    }

    /**
     * Registers a new user
     *
     * @param Request $request request
     *
     * @return User
     *
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
     */
    public function postAction(Request $request)
    {
        return $this->createResource($request);
    }

    /**
     * Route to add friends (which also accepts friend requests)
     *
     * @param User $friend User to link current user with
     *
     * @return null
     *
     * @ApiDoc(
     *  resource=false,
     *  description="Creates a friendship request of current user to desired one",
     *  statusCodes={
     *      401 = "Unauthorized - log in first",
     *      204 = "Successfully requested",
     *      409 = "User already added as friend"
     *  }
     * )
     * @Annotations\Link(requirements={"friend"="\w{24}"})
     */
    public function linkAction(User $friend)
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw new UnauthorizedHttpException('Probably you are not authorized');
        }
        $currentUserId = $currentUser->getId();
        $friendId = $friend->getId();
        if ($currentUser->hasFriend($friendId)) {
            $conflictMessage = sprintf('user %s is already your friend', $friendId);
            throw new ConflictHttpException($conflictMessage);
        }

        // Add a friend (subscription) and make friendship request
        $currentUser->addFriend($friendId);
        $friend->addRequest($currentUserId);

        // Accept removes request if exists any
        $currentUser->removeRequest($friendId);

        // Persist changes to DB
        $documentManager = $this->get('doctrine.odm.mongodb.document_manager');
        $documentManager->flush();
    }

    /**
     * Returns list of found friends IDs for specified nesting level
     *
     * @param ParamFetcherInterface $paramFetcher Symfony param fetcher
     *
     * @return string[]
     *
     * @Annotations\Get("/me/friends")
     * @Annotations\QueryParam(
     *     name="nesting",
     *     requirements="\d+",
     *     strict=true,
     *     default="0",
     *     description="Nesting level to query friends"
     * )
     * @ApiDoc(
     *  resource=false,
     *  statusCodes={
     *      401="Unauthorized"
     *  }
     * )
     */
    public function getMyFriendsAction(ParamFetcherInterface $paramFetcher)
    {
        $nestingLevel = $paramFetcher->get('nesting');
        $documentManager = $this->get('doctrine.odm.mongodb.document_manager');
        /**
         * Current user to query friends
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        $friends = $documentManager
            ->getRepository('AppBundle:User')
            ->findNestedFriends($currentUser->getId(), $nestingLevel);

        return $friends;
    }
}
