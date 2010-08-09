<?php
/**
 * Base TestCase for testing forms
 * Contains auto tests
 *
 * @package    sfPhpunitPlugin
 * @author     Maxim Oleinik <maxim.oleinik@gmail.com>
 */


/**
 * Form validation test item
 *
 * @see sfPHPUnitFormTestCase::getValidationTestingPlan()
 */
class sfPHPUnitFormValidationItem
{
    private
        $_input,
        $_errors,
        $_errorsCount;


    /**
     * Construct
     *
     * @param array $input          - "name" => "My Name"
     * @param array $expectedErrors - "name" => "invalid"
     * @return void
     */
    public function __construct(array $input, array $expectedErrors = array())
    {
        $this->_input  = $input;
        $this->_errors = $expectedErrors;
        $this->_errorsCount = count($expectedErrors);
    }


    /**
     * Get incoming data
     *
     * @return array
     */
    public function getInput()
    {
        return $this->_input;
    }


    /**
     * Get list of expected errors
     *
     * @return array
     */
    public function getExpectedErrors()
    {
        return $this->_errors;
    }


    /**
     * Count expected errors
     *
     * @return int
     */
    public function getErrorsCount()
    {
        return $this->_errorsCount;
    }

}


/**
 * Form tester
 */
abstract class sfPHPUnitFormTestCase extends myUnitTestCase
{
    /**
     * Test form saving
     */
    protected $saveForm = true;

    /**
     * Test form
     */
    protected $form;


    // Fixtures
    // -------------------------------------------------------------------------

    /**
     * SetUp
     */
    public function setUp()
    {
        parent::setup();
        $this->form = $this->makeForm();
    }


    /**
     * Create form
     *
     * Example:
     *   return new ArticleForm;
     *
     * @return sfForm
     */
    abstract protected function makeForm();


    /**
     * Get expected form fields (use fields)
     *
     * @return array - array("title", "content")
     */
    abstract protected function getFields();


    /**
     * Form validation testing plan
     *
     * Example:
     *   return array(
     *       'Empty request' => new myFormValidationTestItem(
     *           array(),
     *           array(
     *               'title'       => 'required',
     *               'description' => 'required',
     *               '_csrf_token' => 'required',
     *           )),
     *
     *       'No errors' => new myFormValidationTestItem(
     *           $this->makeInput(array('title' => 'My Article'))
     *           ),
     *   );
     *
     * @return array sfPHPUnitFormValidationItem
     */
    abstract protected function getValidationTestingPlan();


    /**
     * Get valid data
     *
     * @return array - array("title" => "My title")
     */
    abstract protected function getValidData();


    /**
     * Get valid input with csrf token
     *
     * @return array
     */
    protected function getValidInput()
    {
        return $this->makeInput($this->getValidData());
    }


    /**
     * Add CSRF token to input
     *
     * @param  array $input
     * @return array
     */
    protected function makeInput(array $input)
    {
        if ($this->form->isCsrfProtected()) {
            $input[$this->form->getCsrfFieldName()] = $this->form->getCSRFtoken();
        }
        return $input;
    }


    // Assertions
    // -------------------------------------------------------------------------


    /**
     * Assert form fields
     *
     * @param  array  $expected - array(name, title)
     * @param  sfForm $form
     * @param  string $message
     */
    protected function assertFormFields($expected, sfForm $form, $message = null)
    {
        sort($expected);

        $actual = array_keys($form->getWidgetSchema()->getFields());
        sort($actual);

        $this->assertEquals($expected, $actual, $message);
    }


    /**
     * Assert form is valid
     *
     * @param sfForm $form
     * @param string $message
     */
    protected function assertFormIsValid(sfForm $form, $message = null)
    {
        $message = $message ? $message.PHP_EOL : null;

        if ($form->hasErrors()) {
            $this->fail($this->makeErrorMess($form, $message.'Expected form is valid'));
        }
    }


    /**
     * Assert form has erros
     *
     * @param sfForm $form
     * @param int    $errorsCount
     * @param string $message
     */
    protected function assertFormHasErros(sfForm $form, $errorsCount, $message = null)
    {
        $message = $message ? $message.PHP_EOL : null;

        if (!$form->hasErrors()) {
            $this->fail($this->makeErrorMess($form, $message.'Expected form HAS errors'));
        }
        $this->assertEquals((int)$errorsCount, $form->getErrorSchema()->count(), $this->makeErrorMess($form, $message.'Errors count'));
    }


    /**
     * Assert form error
     *
     * @param sfForm $form
     * @param string $field         - Form field name
     * @param string $expectedError - Expected error, "required"
     * @param string $message
     */
    protected function assertFormError(sfForm $form, $field, $expectedError, $message = null)
    {
        $message = $message ? $message.PHP_EOL : null;

        // Global error
        if (!$field) {
            $this->assertTrue($form->hasGlobalErrors(), $this->makeErrorMess($form, $message."Expected form HAS global errors"));
            $errors = implode('; ', $form->getGlobalErrors());
            $this->assertContains($expectedError, $errors, $this->makeErrorMess($form, $message."Global errors contains next text"));
        } else {
            $errors = $form->getErrorSchema();
            $this->assertTrue(isset($errors[$field]), $this->makeErrorMess($form, $message."Expected field `{$field}` HAS error"));
            $error = $errors[$field]->getCode();
            $this->assertEquals($expectedError, $error, $this->makeErrorMess($form, $message."Expected error `{$expectedError}` for field `{$field}`"));
        }
    }


    /**
     * Make error messge
     *
     * Display incoming data and errors list
     *
     * @param sfForm $form
     * @param string $message
     */
    protected function makeErrorMess(sfForm $form, $message)
    {
        return sprintf("%s\n\nErrors: %s\n\nInput:\n%s",
            $message,
            $form->getErrorSchema(),
            var_export($form->getTaintedValues(), true)
        );
    }


    /**
     * Clean input to search in database
     *
     * @param  array $input
     * @return array
     */
    protected function cleanInput(array $input)
    {
        if (isset($input[$this->form->getCsrfFieldName()])) {
            unset($input[$this->form->getCsrfFieldName()]);
        }

        return $input;
    }


    // Tests
    // -------------------------------------------------------------------------


    /**
     * Check form fields
     *
     * @see getFields()
     */
    public function testAutoFields()
    {
        $this->assertFormFields($this->getFields(), $this->form, get_class($this->form));
    }


    /**
     * Check validation
     *
     * @see getValidationTestingPlan()
     */
    public function testAutoValidation()
    {
        foreach ($this->getValidationTestingPlan() as $name => $item) {
            $form = $this->makeForm();
            $form->bind($input = $item->getInput(), array());

            // Valid
            if (!$item->getErrorsCount()) {
                $this->assertFormIsValid($form, $name);

                if ($this->saveForm && $this->form instanceof sfFormObject) {
                    $object = $form->save();
                    $this->assertEquals(1, $this->queryFind(get_class($object), $this->cleanInput($input))->count(), $name.PHP_EOL.'Expected found 1 object');
                }

            // Errors
            } else {
                $this->assertFormHasErros($form, $item->getErrorsCount(), $name);
                foreach ($item->getExpectedErrors() as $field => $error) {
                    $this->assertFormError($form, $field, $error, $name);
                }
            }

        }
    }


    /**
     * Check valid input
     *
     * @see getValidInput()
     */
    public function testAutoFormIsValid()
    {
        $input = $this->getValidInput();
        $this->form->bind($input, array());
        $this->assertFormIsValid($this->form);

        if ($this->saveForm && $this->form instanceof sfFormObject) {
            $object = $this->form->save();
            $this->assertEquals(1, count($this->find(get_class($object), $this->cleanInput($input))), 'Expected found 1 object');
        }
    }

}
