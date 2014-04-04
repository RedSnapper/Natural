<?php
defined('_JEXEC') or die('Restricted access');

JLoader::import('joomla.application.component.view');

class CompositeViewComposite extends JViewLegacy
{
	function display($tpl = null)
	{
		$this->composition = $this->get('Composite');
		if (count($errors = $this->get('Errors')))
		{
			JLog::add($errors,JLog::WARNING,'jerror');
			return false;
		}
		parent::display($tpl);
	}
}
