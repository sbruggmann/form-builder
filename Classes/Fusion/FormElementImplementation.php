<?php
namespace Neos\Form\Builder\Fusion;

use Neos\Flow\Validation\Validator\NotEmptyValidator;
use Neos\Form\Core\Model\AbstractFormElement;
use Neos\Form\Core\Model\Page;
use Neos\Fusion\Exception as FusionException;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class FormElementImplementation extends AbstractFusionObject
{

    public function getPath(): string
    {
        return $this->path;
    }

    public function evaluate()
    {
        $context = $this->runtime->getCurrentContext();
        // TODO error handling if "parentRenderable" is not available
        if (array_key_exists('parentRenderable', $context)) {
            /** @var Page $renderable */
            $renderable = $context['parentRenderable'];

            /** @var AbstractFormElement $element */
            $element = $renderable->createElement($this->getIdentifier(), $this->getFormElementType());
            $element->setLabel($this->getLabel());
            $element->setDefaultValue($this->getDefaultValue());

            foreach ($this->getProperties() as $propertyName => $propertyValue) {
                $element->setProperty($propertyName, $propertyValue);
            }
            foreach ($this->getRenderingOptions() as $optionName => $optionValue) {
                $element->setRenderingOption($optionName, $optionValue);
            }

            if ($this->isRequired()) {
                $element->addValidator(new NotEmptyValidator());
            }

            $this->runtime->pushContext('element', $element);
            $this->runtime->evaluate($this->path . '/validators');
            $this->runtime->popContext();
        }
    }

    private function getFormElementType(): string
    {
        $formElementType = $this->fusionValue('formElementType');
        if ($formElementType === null) {
            throw new FusionException(sprintf('Missing formElementType for Form Element Fusion object "%s" at "%s"', $this->fusionObjectName, $this->path), 1502461560);
        }
        return $formElementType;
    }

    private function getIdentifier(): string
    {
        $identifier = $this->fusionValue('identifier');
        // HACK is there a cleaner way to determine the element "key"
        if ($identifier === null) {
            preg_match('/\/([^\/<>]+)(?!.*\/)/', $this->path, $matches);
            $identifier = $matches[1];
        }
        return $identifier;
    }

    /**
     * @return string|null
     */
    private function getLabel()
    {
        return $this->fusionValue('label');
    }

    /**
     * @return mixed|null
     */
    private function getDefaultValue()
    {
        return $this->fusionValue('defaultValue');
    }

    private function getProperties(): array
    {
        return $this->fusionValue('properties');
    }

    private function getRenderingOptions(): array
    {
        return $this->fusionValue('renderingOptions');
    }

    private function isRequired(): bool
    {
        return (bool)$this->fusionValue('required');
    }
}
