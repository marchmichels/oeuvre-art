<?php
/**
 * Author: Marc Michels
 * Date: 8/22/22
 * File: ArtFormType.php
 * Description: The ArtFormType Class controls the art upload form.
 * Extends: AbstractType
 * Public Methods: buildForm - builds art upload form
 *                 configureOptions - sets options for form building
 */

namespace App\Form;

use App\Entity\Art;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;


class ArtFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('artfile', FileType::class, [
                'label' => false,

                'mapped' => false,

                'required' => true,

                'constraints' => [
                    new Image([
                        'maxSize' => '10M'
                    ])
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'upload', 'attr' => ['class' => 'btn-dark', 'disabled' => 'true']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Art::class,
        ]);
    }
}
