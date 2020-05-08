<?php

namespace GaylordP\UploadBundle\Form\Type;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploadType extends AbstractType
{
    private $uploadDirectory;
    private $validator;

    public function __construct(
        ParameterBagInterface $parameters,
        ValidatorInterface $validator
    ) {
        $this->uploadDirectory = $parameters->get('upload_directory');
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::POST_SUBMIT, [
                $this,
                'onPostSubmit',
            ])
        ;
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();

        $constraints = $form->getConfig()->getOption('upload_constraints');
        $fileConstraints = new FileConstraint($constraints);

        $file = new File($form->getData());

        $errors = $this->validator->validate($file, $fileConstraints);
        foreach ($errors as $error) {
            $form->addError(
                new FormError(
                    $file->getFileName() . ' : ' . $error->getMessage()
                )
            );
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $constraints = $form->getConfig()->getOption('upload_constraints');

        $view->vars['row_attr']['id'] = 'dropzone-' . $view->vars['id'];
        $view->vars['row_attr']['class'] = 'dropzone';
        $view->vars['row_attr']['data-type'] = get_class($form->getRoot()->getConfig()->getType()->getInnerType());
        $view->vars['row_attr']['data-constraint-maxsize'] = $constraints['maxSize'];
        $view->vars['row_attr']['data-constraint-maxsize-binary'] = (new FileConstraint($constraints))->maxSize;
        $view->vars['row_attr']['data-constraint-mime'] = implode(',', $constraints['mimeTypes']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'initial_files' => [],
            'upload_constraints' => [
                'maxSize' => '2G',
                'mimeTypes' => [
                    'image/gif',
                    'image/jpeg',
                    'image/png',
                    'video/mp4',
                ],
            ],
        ]);
    }

    public function getParent()
    {
        return HiddenType::class;
    }
}
