<?php

namespace App\Lib\Controller;

use App\Exception\FormValidationException;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Form\Extension\Core\Type\FormType;

trait FormTrait
{
    public function createFormBuilder(): FormBuilderInterface
    {
        $validator = Validation::createValidator();

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory();

        return $factory->createNamedBuilder(
            '',
            FormType::class,
            null,
            ['allow_extra_fields' => true]
        );
    }

    /**
     * @param FormInterface $form
     * @throws FormValidationException
     */
    public function validateForm(FormInterface $form): void
    {
        if (!$form->isSubmitted()) {
            throw new FormValidationException('Form submit is required');
        }

        if (!$form->isValid()) {

            $formErrors = ['common' => ''];

            /** @var FormError $error */
            foreach ($form->getErrors() as $error) {
                $formErrors['common'] .= $error->getMessage() . ' ';
            }

            /** @var FormErrorIterator $errors */
            foreach ($form->getErrors(true, false) as $errors) {
                foreach ($errors as $error) {
                    $formErrors[$error->getOrigin()->getName()] = $error->getMessage();
                }
            }

            throw new FormValidationException('Submitted fields are invalid', $formErrors);
        }
    }
}