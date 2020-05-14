<?php

namespace GaylordP\UploadBundle\Form\Type;

use GaylordP\UploadBundle\Entity\Media;
use GaylordP\UploadBundle\Form\DataTransformer\UploadTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File as FileConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploadType extends AbstractType
{
    private $requestStack;
    private $uploadDirectory;
    private $validator;
    private $uploadTransformer;

    public function __construct(
        RequestStack $requestStack,
        string $uploadDirectory,
        ValidatorInterface $validator,
        UploadTransformer $uploadTransformer
    ) {
        $this->requestStack = $requestStack;
        $this->uploadDirectory = $uploadDirectory;
        $this->validator = $validator;
        $this->uploadTransformer = $uploadTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addModelTransformer($this->uploadTransformer)
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

        $file = $form->getData()->getFile();

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
        $view->vars['row_attr']['data-controller'] = $this->requestStack->getCurrentRequest()->attributes->get('_controller');
        $view->vars['row_attr']['data-form-name'] = $form->getConfig()->getName();
        $view->vars['row_attr']['data-constraint-maxsize'] = $constraints['maxSize'];
        $view->vars['row_attr']['data-constraint-maxsize-binary'] = (new FileConstraint($constraints))->maxSize;
        $view->vars['row_attr']['data-constraint-mime'] = implode(',', $constraints['mimeTypes']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
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
