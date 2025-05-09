<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a product name']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 5],
                'required' => false,
            ])
            ->add('price', MoneyType::class, [
                'attr' => ['class' => 'form-control'],
                'currency' => 'EUR',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a price']),
                ],
            ])
            ->add('artist', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('genre', ChoiceType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => false,
                'choices' => [
                    'Rock' => 'Rock',
                    'Jazz' => 'Jazz',
                    'Blues' => 'Blues',
                    'Classical' => 'Classical',
                    'Electronic' => 'Electronic',
                    'Hip Hop' => 'Hip Hop',
                    'Pop' => 'Pop',
                    'Punk' => 'Punk',
                    'Metal' => 'Metal',
                    'Funk' => 'Funk',
                    'Soul' => 'Soul',
                    'Reggae' => 'Reggae',
                    'Soundtrack' => 'Soundtrack',
                    'Other' => 'Other',
                ],
            ])
            ->add('releaseYear', TextType::class, [
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('imageFile', FileType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Product Image',
                'mapped' => false,
                'required' => false,
            ])
            ->add('stock', IntegerType::class, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new PositiveOrZero(['message' => 'Stock cannot be negative']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
