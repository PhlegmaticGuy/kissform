<?php

use mindplay\kissform\BoolField;
use mindplay\kissform\TokenField;
use mindplay\kissform\DateTimeField;
use mindplay\kissform\EnumField;
use mindplay\kissform\Field;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\IntField;
use mindplay\kissform\TextField;
use mindplay\kissform\InputValidator;

require __DIR__ . '/header.php';

header('Content-type: text/plain');

if (coverage()) {
    $filter = coverage()->filter();

    $filter->addDirectoryToWhitelist(dirname(__DIR__) . '/src');

    coverage()->start('test');
}

class SampleDescriptor
{
    const OPTION_ONE = 'one';
    const OPTION_TWO = 'two';

    /** @var TokenField */
    public $token;

    /** @var EnumField */
    public $enum;

    /** @var TextField */
    public $text;

    /** @var IntField */
    public $int;

    /** @var BoolField */
    public $bool;

    public function __construct()
    {
        $this->token = new TokenField('token');

        $this->enum = new EnumField('enum');

        $this->enum->options = array(
            self::OPTION_ONE => 'Option One',
            self::OPTION_TWO => 'Option Two',
        );

        $this->text = new TextField('text');

        $this->int = new IntField('int');

        $this->bool = new BoolField('bool');
    }
}

class ValidationDescriptor
{
    const CAUSE_PROGRAMMERS = 'p';
    const CAUSE_ARTISTS = 'a';

    /** @var TextField */
    public $email;

    /** @var TextField */
    public $confirm_email;

    /** @var IntField */
    public $donation;

    /** @var TextField */
    public $password;

    /** @var BoolField */
    public $agree;

    /** @var EnumField */
    public $cause;

    public function __construct()
    {
        $this->email = new TextField('email');

        $this->confirm_email = new TextField('confirm_email');

        $this->donation = new IntField('donation');
        $this->donation->min_value = 100;
        $this->donation->max_value = 1000;

        $this->password = new TextField('password');

        $this->agree = new BoolField('agree');

        $this->cause = new EnumField('cause');
        $this->cause->options = array(
            self::CAUSE_PROGRAMMERS => 'Starving Programmers',
            self::CAUSE_ARTISTS => 'Starving Artists',
        );
    }
}

/**
 * Test a validation function against known valid and invalid values.
 *
 * @param Field    $field    Field instance where $name === 'value'
 * @param Closure  $function function (FormValidator $v, Field $f): void; should invoke the validation function
 * @param string[] $valid    list of valid values
 * @param string[] $invalid  list of invalid values
 */
function testValidator(Field $field, $function, array $valid, array $invalid)
{
    $field->label = 'Value';

    foreach ($valid as $valid_value) {
        $validator = new InputValidator(array('value' => $valid_value));

        call_user_func($function, $validator, $field);

        ok(!isset($validator->errors['value']), "field " . get_class($field) . " accepts value: " . format($valid_value));
    }

    foreach ($invalid as $invalid_value) {
        $validator = new InputValidator(array('value' => $invalid_value));

        call_user_func($function, $validator, $field);

        ok(isset($validator->errors['value']), "field " . get_class($field) . " rejects value: " . format($invalid_value) . " (" . @$validator->errors['value'] . ")");
    }
}

test(
    'handles name, id and class-attributes',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(array());

        eq($form->text($type->text, array('class' => array('foo', 'bar'))), '<input class="form-control foo bar" name="text" type="text"/>', 'merges multi-value class attribute');

        eq(invoke($form, 'createName', array($type->text)), 'text', 'name without prefix');
        eq(invoke($form, 'createId', array($type->text)), null, 'no id attribute when $id_prefix is NULL');

        $form->name_prefix = 'form';
        $form->id_prefix = 'form';

        eq(invoke($form, 'createName', array($type->text)), 'form[text]', 'name with prefix');
        eq(invoke($form, 'createId', array($type->text)), 'form-text', 'id with defined prefix');
    }
);

test(
    'builds input groups',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(array());

        eq($form->group($type->text) . $form->endGroup(), '<div class="form-group"></div>');

        $form->errors['text'] = 'some error';

        eq($form->group($type->text), '<div class="form-group has-error">');

        $type->text->required = true;

        eq($form->group($type->text), '<div class="form-group is-required has-error">');

        eq($form->group($type->text, array('class' => 'foo')), '<div class="form-group is-required has-error foo">', 'merge with one class');

        eq($form->group($type->text, array('class' => array('foo', 'bar'))), '<div class="form-group is-required has-error foo bar">', 'merge with multiple classes');
    }
);

test(
    'builds various text input tags',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(array());

        eq($form->text($type->text), '<input class="form-control" name="text" type="text"/>', 'basic input with no value-attribute');

        $form->input['text'] = 'Hello World';

        eq($form->text($type->text), '<input class="form-control" name="text" type="text" value="Hello World"/>', 'basic input with value-attribute');

        $type->text->max_length = 50;

        eq($form->text($type->text), '<input class="form-control" maxlength="50" name="text" type="text" value="Hello World"/>', 'input with value and maxlength-attributes');

        $type->text->placeholder = 'hello';

        eq($form->text($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="text" value="Hello World"/>', 'input with value, maxlength and placeholder-attributes');
        eq($form->text($type->text, array('data-foo' => 'bar')), '<input class="form-control" data-foo="bar" maxlength="50" name="text" placeholder="hello" type="text" value="Hello World"/>', 'input with custom data-attribute overridden');
        eq($form->text($type->text, array('placeholder' => 'override')), '<input class="form-control" maxlength="50" name="text" placeholder="override" type="text" value="Hello World"/>', 'input with placeholder-attribute overridden');

        $form->input['text'] = 'this & that';

        eq($form->text($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="text" value="this &amp; that"/>', 'input with value-attribute escaped as HTML');

        eq($form->password($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="password" value="this &amp; that"/>', 'input with type=password');

        eq($form->hidden($type->text), '<input name="text" type="hidden" value="this &amp; that"/>', 'hidden input (no class, placeholder or maxlength, etc.)');

        eq($form->email($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="email" value="this &amp; that"/>', 'input with type=email (html5)');

        eq($form->textarea($type->text), '<textarea class="form-control" name="text" placeholder="hello">this &amp; that</textarea>', 'simple textarea with content');

        ok(preg_match('#<input name="token" type="hidden" value="\w+"/>#', $form->token($type->token, 'abc123')) === 1, 'hidden input with CSRF token');
    }
);

test(
    'builds label tags',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(array());

        expect(
            'RuntimeException',
            'because no id-attribute can be created',
            function () use ($form, $type) {
                $form->label($type->text);
            }
        );

        $form = new InputRenderer(array(), 'form');

        eq($form->label($type->text), '', 'returns an empty string for unlabeled input');

        $type->text->label = 'Name:';

        eq($form->label($type->text), '<label class="control-label" for="form-text">Name:</label>');
    }
);

test(
    'builds labeled checkboxes',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(array(), 'form');

        $type->bool->label = 'I agree';

        eq($form->checkbox($type->bool), '<div class="checkbox"><label><input name="form[bool]" type="checkbox" value="1"/>I agree</label></div>');
    }
);

test(
    'builds select/option tags',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(array());

        $type->enum->required = false;

        eq($form->select($type->enum), '<select name="enum"><option value="one">Option One</option><option value="two">Option Two</option></select>');
    }
);

test(
    'builds date/time text inputs',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(array());

        $form->input['text'] = '1975-07-07';

        eq($form->date($type->text), '<input class="form-control" data-ui="datepicker" name="text" readonly="readonly" type="text" value="1975-07-07"/>');

        $form->input['text'] = '1975-07-07 12:00:00';

        eq($form->datetime($type->text), '<input class="form-control" data-ui="datetimepicker" name="text" readonly="readonly" type="text" value="1975-07-07 12:00:00"/>');
    }
);

test(
    'validator behavior',
    function () {
        $validator = new InputValidator(array());

        $type = new ValidationDescriptor();

        expect(
            'RuntimeException',
            'undefined property access',
            function () use ($validator) {
                $validator->foo_bar;
            }
        );

        eq($validator->valid, true, 'no errors initially (is valid)');
        eq($validator->invalid, false, 'no errors initially (is not invalid)');

        $validator->error($type->email, 'some error');

        eq($validator->valid, false, 'errors are present (is not valid)');
        eq($validator->invalid, true, 'errors are present (is invalid)');

        eq($validator->errors['email'], 'some error', 'error messages get formatted');

        $validator->error($type->email, 'some other error');

        eq($validator->errors['email'], 'some error', 'first error message is retained');

        $validator->clear($type->email);

        eq($validator->valid, true, 'error message cleared');

        $validator->error($type->email, 'some error again');

        $validator->reset();

        eq($validator->valid, true, 'errors have been cleared');
    }
);

test(
    'validate required()',
    function () {
        testValidator(
            new TextField('value'),
            function (InputValidator $v, Field $f) {
                $v->required($f);
            },
            array('a', 'bbb'),
            array('', null)
        );
    }
);

test(
    'validate email()',
    function () {
        testValidator(
            new TextField('value'),
            function (InputValidator $v, TextField $f) {
                $v->email($f);
            },
            array('a@b.com', 'foo@bar.dk'),
            array(123, '123', 'foo@', '@foo.com')
        );
    }
);

test(
    'validate range()',
    function () {
        $int_field = new IntField('value');
        $int_field->min_value = 100;
        $int_field->max_value = 1000;

        testValidator(
            $int_field,
            function (InputValidator $v, IntField $f) {
                $v->range($f);
            },
            array(100, 500, 1000),
            array(99, 1001, -1, null, '')
        );
    }
);

test(
    'validate minValue()',
    function () {
        $int_field = new IntField('value');
        $int_field->min_value = 100;

        testValidator(
            $int_field,
            function (InputValidator $v, IntField $f) {
                $v->minValue($f);
            },
            array(100, 1000),
            array(-1, 0, null, '')
        );
    }
);

test(
    'validate maxValue()',
    function () {
        $int_field = new IntField('value');
        $int_field->max_value = 1000;

        testValidator(
            $int_field,
            function (InputValidator $v, IntField $f) {
                $v->maxValue($f);
            },
            array(-1, 0, 1000),
            array(1001, null, '')
        );
    }
);

test(
    'validate int()',
    function () {
        testValidator(
            new IntField('value'),
            function (InputValidator $v, IntField $f) {
                $v->int($f);
            },
            array('0', '-1', '1', '123'),
            array('', null, '-', 'foo', '0.0', '1.0', '123.4')
        );
    }
);

test(
    'validate numeric()',
    function () {
        testValidator(
            new IntField('value'),
            function (InputValidator $v, IntField $f) {
                $v->numeric($f);
            },
            array('0', '-1', '1', '123', '0.0', '-1.0', '-1.1', '123.4', '123.1'),
            array('', null, '-', 'foo')
        );
    }
);

test(
    'validate confirm()',
    function () {
        $field = new TextField('value');
        $other = new TextField('other');

        $validator = new InputValidator(array('value' => 'foo', 'other' => 'foo'));
        $validator->confirm($field, $other);
        ok(!isset($validator->errors['value']), 'value field has no error');
        ok(!isset($validator->errors['other']), 'other field has no error');

        $validator = new InputValidator(array('value' => 'foo', 'other' => 'bar'));
        $validator->confirm($field, $other);
        ok(!isset($validator->errors['value']), 'value field has no error');
        ok(isset($validator->errors['other']), 'other field has error');
    }
);

test(
    'validate length()',
    function () {
        $field = new TextField('value');
        $field->min_length = 5;
        $field->max_length = 10;

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->length($f);
            },
            array('12345','1234567890'),
            array('', null, '1234', '12345678901')
        );
    }
);

test(
    'validate minLength()',
    function () {
        $field = new TextField('value');
        $field->min_length = 5;

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->minLength($f);
            },
            array('12345','1234567890'),
            array('', null, '1234')
        );
    }
);

test(
    'validate maxLength()',
    function () {
        $field = new TextField('value');
        $field->max_length = 10;

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->length($f);
            },
            array('12345','1234567890','',null),
            array('12345678901')
        );
    }
);

test(
    'validate checked()',
    function () {
        $field = new BoolField('value');

        testValidator(
            $field,
            function (InputValidator $v, BoolField $f) {
                $v->checked($f);
            },
            array($field->checked_value, true),
            array('', '0', null, 'true')
        );
    }
);

test(
    'validate selected()',
    function () {
        $field = new EnumField('value');
        $field->options = array(
            '1' => 'foo',
            '2' => 'bar',
        );

        testValidator(
            $field,
            function (InputValidator $v, EnumField $f) {
                $v->selected($f);
            },
            array('1', '2', true),
            array('0', '3', null)
        );
    }
);

test(
    'validate password()',
    function () {
        $field = new TextField('value');

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->password($f);
            },
            array('aA1', 'aA', 'a1', '1a'),
            array('111', 'aaa', 'AAA')
        );
    }
);

test(
    'validate datetime()',
    function () {
        $field = new DateTimeField('value');
        $field->setTimeZone('UTC');
        $field->format = 'Y-m-d';

        testValidator(
            $field,
            function (InputValidator $v, DateTimeField $f) {
                $v->datetime($f);
            },
            array('1975-07-07', '2014-01-01', '2014-12-31'),
            array('2014-1-1', '2014', '2014-13-01', '2014-12-32', '2014-0-1', '2014-1-0')
        );
    }
);

test(
    'validate token()',
    function () {
        $field = new TokenField('value');

        $secret = 'abc123';

        $not_valid_yet =  $field->createToken($secret);

        testValidator(
            $field,
            function (InputValidator $v, TokenField $f) use ($secret) {
                $v->token($f, $secret);
            },
            array(),
            array($not_valid_yet)
        );

        $field->timestamp += $field->valid_from;
        $valid = $not_valid_yet;

        testValidator(
            $field,
            function (InputValidator $v, TokenField $f) use ($secret) {
                $v->token($f, $secret);
            },
            array($valid), // now it's valid!
            array('', null, '1' . $valid) // never valid
        );
    }
);

test(
    'TokenField token expiration',
    function () {
        $field = new TokenField('token');

        $secret = 'abc123';

        ok(strlen($field->createToken($secret)) > 0, 'it creates a token');
        ok($field->createToken($secret) !== $field->createToken($secret), 'it creates unique tokens');

        $token = $field->createToken($secret);

        ok($field->checkToken($token, $secret) === false, 'token invalid when submitted too soon');

        $timestamp = $field->timestamp;

        $field->timestamp = $timestamp + $field->valid_from;

        ok($field->checkToken($token, $secret) === true, 'token valid when submitted before expiration');

        $field->timestamp = $timestamp + $field->valid_to;

        ok($field->checkToken($token, $secret) === true, 'token valid when submitted on time');

        ok($field->checkToken($token, $secret . '1') === false, 'token invalid when using the wrong secret');
        ok($field->checkToken('1' . $token, $secret) === false, 'token invalid after tampering');

        $field->timestamp = $timestamp + $field->valid_to + 1;

        ok($field->checkToken($token, $secret) === false, 'token invalid when submitted after expiration');
    }
);

if (coverage()) {
    coverage()->stop();

    $report = new PHP_CodeCoverage_Report_Text(10, 90, false, false);
    echo $report->process(coverage(), false);

    $report = new PHP_CodeCoverage_Report_Clover();
    $report->process(coverage(), __DIR__ . '/build/logs/clover.xml');
}

exit(status());
