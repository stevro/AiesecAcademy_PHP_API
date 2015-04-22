<?php

namespace Aiesec\TaskListBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TaskType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('name')
                ->add('description')
                ->add('deadline', 'date', array('widget' => 'single_text', 'format' => 'yyyy-MM-dd'))
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $resolver->setDefaults(array(
            'data_class' => 'Aiesec\TaskListBundle\Entity\Task',
            'csrf_protection' => false,
            'validation_groups' => function(FormInterface $form) {
                $data = $form->getData();

                if (null !== $data->getId()) {
                    return array('Edit', 'Default');
                }

                return array('Create', 'Default');
            },
                ));
            }

            /**
             * @return string
             */
            public function getName()
            {
                return 'aiesec_task';
            }

        }