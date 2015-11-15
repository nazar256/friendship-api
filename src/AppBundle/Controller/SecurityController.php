<?php

/**
 * Controller container
 * PHP version 5.6
 * @category Class
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     http://friendship-api.dev
 */

namespace AppBundle\Controller;

use AppBundle\Controller\Base\RestController;
use AppBundle\Document\User;
use AppBundle\Helper\Dictionary\SystemService;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Controller which is responsible for logging in and out
 * @category Controller
 * @package  AppBundle
 * @author   nazar <jura_n@bk.ru>
 * @license  MIT @link https://opensource.org/licenses/MIT
 * @link     /api/login
 */
class SecurityController extends RestController
{
    const MSG_NOT_FOUND = 'User with email %s was not found.';

    /**
     * Login action
     *
     * @param Request $request request object
     *
     * @return User|\FOS\RestBundle\View\View
     * @throws NotFoundHttpException|UnauthorizedHttpException
     *
     * @Post(path="/login")
     * @ApiDoc(
     *  resource=true,
     *  description="Logs user in",
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
     *      401 = "Unauthorized (wrong password)",
     *      404 = "Not found (wrong email)"
     *  }
     * )
     */
    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        if ($error) {
            return $this->view($error, Codes::HTTP_UNAUTHORIZED);
        }
        $plainPassword = $request->request->get('password');
        $email = $request->request->get('email');

        /**
         * User who tries to login
         * @var User $user
         */
        $user = $this
            ->get(SystemService::ODM)
            ->getRepository('AppBundle:User')
            ->findOneBy(
                [
                    '_email' => $email
                ]
            );
        if (empty($user)) {
            throw new NotFoundHttpException(sprintf(self::MSG_NOT_FOUND, $email));
        }

        /**
         * Symfony password encoder
         * @var UserPasswordEncoder $encoder
         */
        $encoder = $this->container->get('security.password_encoder');
        $passwordIsValid = $encoder->isPasswordValid($user, $plainPassword);
        if (!$passwordIsValid) {
            throw new UnauthorizedHttpException('Password is invalid');
        }
        $this->_authenticateUser($user);

        return $user;
    }

    /**
     * Authenticates user session
     * @param UserInterface $user user
     * @return null
     */
    private function _authenticateUser(UserInterface $user)
    {
        // Authenticating user
        $token = new UsernamePasswordToken(
            $user,
            null,
            'app_user_provider',
            $user->getRoles()
        );
        $this
            ->get('security.token_storage')
            ->setToken($token);

        //now dispatch the login event
        $request = $this->get("request");
        $event = new InteractiveLoginEvent($request, $token);
        $this
            ->get("event_dispatcher")
            ->dispatch("security.interactive_login", $event);
    }

    /**
     * Invalidates user session
     *
     * @param Request $request request object
     *
     * @return bool
     *
     * @Get("/logout")
     * @ApiDoc(
     *  resource=true,
     *  description="Logs user out"
     * )
     */
    public function logoutAction(Request $request)
    {
        try {
            $request
                ->getSession()
                ->invalidate();
            $this
                ->get('security.token_storage')
                ->setToken(null);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}