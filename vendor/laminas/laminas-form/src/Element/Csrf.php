<?php

declare(strict_types=1);

namespace Laminas\Form\Element;

use Laminas\Filter\StringTrim;
use Laminas\Form\Element;
use Laminas\Form\ElementPrepareAwareInterface;
use Laminas\Form\FormInterface;
use Laminas\InputFilter\InputProviderInterface;
use Laminas\Validator\Csrf as CsrfValidator;

use function array_merge;
use function assert;

class Csrf extends Element implements InputProviderInterface, ElementPrepareAwareInterface
{
    /**
     * Seed attributes
     *
     * @var array
     */
    protected $attributes = [
        'type' => 'hidden',
    ];

    /** @var array */
    protected $csrfValidatorOptions = [];

    /** @var null|CsrfValidator */
    protected $csrfValidator;

    /**
     * Accepted options for Csrf:
     * - csrf_options: an array used in the Csrf
     *
     * @return $this
     */
    public function setOptions(iterable $options)
    {
        parent::setOptions($options);

        if (isset($this->options['csrf_options'])) {
            $this->setCsrfValidatorOptions($this->options['csrf_options']);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getCsrfValidatorOptions(): array
    {
        return $this->csrfValidatorOptions;
    }

    /**
     * @param  array $options
     * @return $this
     */
    public function setCsrfValidatorOptions(array $options)
    {
        $this->csrfValidatorOptions = $options;
        return $this;
    }

    /**
     * Get CSRF validator
     */
    public function getCsrfValidator(): CsrfValidator
    {
        if (null === $this->csrfValidator) {
            $csrfOptions = $this->getCsrfValidatorOptions();
            $csrfOptions = array_merge($csrfOptions, ['name' => $this->getName()]);
            $this->setCsrfValidator(new CsrfValidator($csrfOptions));
            assert(null !== $this->csrfValidator);
        }
        return $this->csrfValidator;
    }

    /**
     * @return $this
     */
    public function setCsrfValidator(CsrfValidator $validator)
    {
        $this->csrfValidator = $validator;
        return $this;
    }

    /**
     * Retrieve value
     *
     * Retrieves the hash from the validator
     */
    public function getValue(): string
    {
        $validator = $this->getCsrfValidator();
        return $validator->getHash();
    }

    /**
     * Override: get attributes
     *
     * Seeds 'value' attribute with validator hash
     */
    public function getAttributes(): array
    {
        $attributes          = parent::getAttributes();
        $validator           = $this->getCsrfValidator();
        $attributes['value'] = $validator->getHash();
        return $attributes;
    }

    /**
     * Provide default input rules for this element
     *
     * Attaches the captcha as a validator.
     *
     * @return array
     */
    public function getInputSpecification(): array
    {
        return [
            'name'       => $this->getName(),
            'required'   => true,
            'filters'    => [
                ['name' => StringTrim::class],
            ],
            'validators' => [
                $this->getCsrfValidator(),
            ],
        ];
    }

    /**
     * Prepare the form element
     */
    public function prepareElement(FormInterface $form): void
    {
        $this->getCsrfValidator()->getHash(true);
    }
}
