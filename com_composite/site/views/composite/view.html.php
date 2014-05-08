<?php
/**
 * @copyright	Copyright ©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Restricted access');

JLoader::import('joomla.application.component.view');

class CompositeViewComposite extends JViewLegacy
{
	function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->composition = $this->get('Composite');
		if (count($errors = $this->get('Errors')))
		{
			JLog::add($errors,JLog::WARNING,'jerror');
			return false;
		}
		parent::display($tpl);
	}
}
