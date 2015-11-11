<?php

namespace AppBundle\DocumentListeners;

use AppBundle\Document\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

/**
 * Subscribes to User document changes in order to encrypt password on the fly
 */
class UserSubscriber implements EventSubscriber
{
    private $passwordEncoder;

    /**
     * @param UserPasswordEncoder $passwordEncoder
     */
    public function __construct(UserPasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'preUpdate',
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->encryptUserPassword($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->encryptUserPassword($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    private function encryptUserPassword(LifecycleEventArgs $args)
    {
        $user = $args->getDocument();
        if ($user instanceof User) {
        }
    }
}