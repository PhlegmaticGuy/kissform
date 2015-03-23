<?php

namespace mindplay\kissform;

/**
 * This class provides information about a <select> input and available options.
 */
class SelectField extends Field implements RenderableField, HasOptions
{
    /**
     * @var string[] map where option values map to option labels
     */
    protected $options;

    /**
     * @var string label of disabled first option (often directions or a description)
     */
    public $disabled;

    /**
     * @param string   $name    field name
     * @param string[] $options map where option values map to option labels
     */
    public function __construct($name, array $options)
    {
        parent::__construct($name);

        $this->options = $options;
    }

    /**
     * @see HasOptions::getOptions()
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $selected = $model->getInput($this);

        $options = $this->getOptions();

        $values = array_map('strval', array_keys($options));

        if (! in_array($selected, $values, true)) {
            $selected = null; // selected value isn't present in the list of options
        }

        $html = '';

        if ($this->disabled !== null) {
            $html .= '<option' . $renderer->attrs(array('disabled' => true, 'selected' => ($selected == ''))) . '>'
                . $renderer->encode($this->disabled) . '</option>';
        }

        foreach ($options as $value => $label) {
            $equal = is_numeric($selected)
                ? $value == $selected // loose comparison works well for NULLs and numbers
                : $value === $selected; // strict comparison for everything else

            $html .= '<option' . $renderer->attrs(array('value' => $value, 'selected' => $equal)) . '>'
                . $renderer->encode($label) . '</option>';
        }

        return $renderer->tag(
            'select',
            $renderer->merge(
                array(
                    'name' => $renderer->createName($this),
                    'id' => $renderer->createId($this),
                    'class' => $renderer->input_class,
                ),
                $attr
            ),
            $html
        );
    }
}
