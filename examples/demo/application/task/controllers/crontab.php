<?php
/**
 * crontab
 */
class Task_CrontabController extends Desire_Application_Controller_Abstract
{
	public function indexAction()
	{
		while($tasks = $this->getTasks())//没任务的时候释放一次
		{
			print_r($tasks);
			foreach ($tasks as $task) {
				$task =
				$this->execute($task['id']);
			}

			if ($timestamp) {
				time_sleep_until($timestamp);
			} else {

			}
		}
	}

	public function collectorAction()
	{

	}

	protected function execute($id)
	{
		//set nexttime
	}

	protected function getTasks()
	{
		return array(
			'controller' => 'top',
			'action' => 'syncWiki'
		);
	}
}
