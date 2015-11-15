<?php

/**
 * MongoDB document
 * PHP version 5.6
 * @category Document
 * @package AppBundle
 * @author nazar <jura_n@bk.ru>
 * @license MIT @link https://opensource.org/licenses/MIT
 * @link http://friendship-api.dev
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UserType
 * @category Controller
 * @package AppBundle
 * @author nazar <jura_n@bk.ru>
 * @license MIT @link https://opensource.org/licenses/MIT
 * @link /api/users
 */
class UserType extends AbstractType
{
    /**
     * {@inheritdoc}
     * @param FormBuilderInterface $builder form builder
     * @param array                $options form options
     * @return null
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', 'email')
            ->add('password', 'password');
    }

    /**
     * {@inheritdoc}
     * @param OptionsResolver $resolver resolver
     * @return null
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'      => 'AppBundle\Document\User',
                'csrf_protection' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getName()
    {
        return 'user';
    }
}
