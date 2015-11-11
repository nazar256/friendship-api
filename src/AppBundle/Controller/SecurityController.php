<?php

namespace AppBundle\Controller;

use AppBundle\Controller\Base\RestController;
use AppBundle\Document\User;
use AppBundle\Helper\Dictionary\SystemService;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Util\Codes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class SecurityController
 * @package AppBundle\Controller
 */
class SecurityController extends RestController
{
    const MSG_NOT_FOUND = 'User with email %s was not found.';

    /**
     * @Post(path="/login")
     * @param Request $request
     * @return User|\FOS\RestBundle\View\View
     * @throws NotFoundHttpException|UnauthorizedHttpException
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

        /** @var User $user */
        $user = $this
            ->get(SystemService::ODM)
            ->getRepository('AppBundle:User')
            ->findOneBy(
                [
                    'email' => $email
                ]
            );
        if (empty($user)) {
            throw new NotFoundHttpException(sprintf(self::MSG_NOT_FOUND, $email));
        }

        /** @var UserPasswordEncoder $encoder */
        $encoder = $this->container->get('security.password_encoder');
        $passwordIsValid = $encoder->isPasswordValid($user, $plainPassword);
        if ( !$passwordIsValid) {
            throw new UnauthorizedHttpException('Password is invalid');
        }
        $this->authenticateUser($user);

        return $user;
    }

    /**
     * @param UserInterface $user
     */
    private function authenticateUser(UserInterface $user)
    {
        // Authenticating user
        $token = new UsernamePasswordToken($user, null, 'app_user_provider', $user->getRoles());
        $this->get('security.token_storage')
             ->setToken($token);

        //now dispatch the login event
        $request = $this->get("request");
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")
             ->dispatch("security.interactive_login", $event);
    }

    /**
     * @param Request $request
     * @Get("/logout")
     * @return bool
     */
    public function logoutAction(Request $request)
    {
        try {
            $request->getSession()
                    ->invalidate();
            $this->get('security.token_storage')
                 ->setToken(null);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}