<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Manager\ChannelTypeManager;
use Oro\Bundle\IntegrationBundle\Provider\ChannelTypeInterface;

class ChannelType extends AbstractType
{
    const NAME            = 'oro_integration_channel_form';
    const TYPE_FIELD_NAME = 'type';

    /** @var ChannelTypeManager */
    protected $cmt;

    /**
     * @param ChannelTypeManager $cmt
     */
    public function __construct(ChannelTypeManager $cmt)
    {
        $this->cmt = $cmt;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $keys    = $this->cmt->getRegisteredTypes()->getKeys();
        $values  = $this->cmt->getRegisteredTypes()->map(
            function (ChannelTypeInterface $type) {
                return $type->getLabel();
            }
        )->toArray();
        $choices = array_combine($keys, $values);
        $builder->add(self::TYPE_FIELD_NAME, 'choice', ['required' => true, 'choices' => $choices]);
        $builder->add('name', 'text', ['required' => true]);

        /**
         * Remove type field to deny change "channel type" if channel saved
         */
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if ($data === null) {
                    return;
                }

                if ($data instanceof Channel && $data->getId()) {
                    $form->remove(self::TYPE_FIELD_NAME);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\IntegrationBundle\Entity\Channel',
                'intention'  => 'channel',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
