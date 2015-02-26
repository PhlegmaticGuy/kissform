<?php

namespace mindplay\kissform;

/**
 * This class represents a labeled checkbox structure, e.g. span.checkbox > label > input
 */
class CheckboxField extends Field implements RenderableField
{
    /**
     * @var string
     */
    public $checked_value = '1';

    /**
     * @var string overrides the default checkbox label (provided by Field::$label)
     */
    public $label;

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $label = $this->label ?: $renderer->getLabel($this);

        $input = $renderer->tag(
            'input',
            array(
                'name'    => $renderer->createName($this),
                'value'   => $this->checked_value,
                'checked' => $model->getInput($this) == $this->checked_value ? 'checked' : null,
                'type'    => 'checkbox',
            )
        );

        return
            '<div class="checkbox">'
            . ($label ? $renderer->tag('label', array(), $input . $renderer->softEncode($label)) : $input)
            . '</div>';
    }
}
