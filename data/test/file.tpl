<?php
/**
 * Unit test case for class {testClassName}
 */
class {testClassName} extends {baseTestName}
{
	/**
	 * {className}
	 *
	 * @var {className}
	 */
	protected $o;

	protected function _start()
	{
		$this->o = new {className}();
	}
{methods}
	protected function _end()
	{
	}

}