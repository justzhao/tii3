<?php

class Task_ErrorController extends Desire_Application_Controller_ErrorAbstract
{
	public function errorAction()
	{
		$e = $this->getDispatcher()->getError();
		if (Desire_Config::isDebugMode()) {
			$trace = $e->getTrace();

			$result = 'Exception: "';
			$result .= $e->getMessage();
			$result .= '" @ ';
			if ($trace[0]['class'] != '') {
				$result .= $trace[0]['class'];
				$result .= '->';
			}
			$result .= $trace[0]['function'];
			$result .= "();\n";
			$result .= $e->getTraceAsString();
			echo $result;
		} else {
			echo $e->getMessage();
		}
	}
}
