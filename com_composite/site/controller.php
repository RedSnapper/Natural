<?php
/**
 * @copyright	Copyright ©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Restricted access');

JLoader::import('joomla.application.component.controller');

class CompositeController extends JControllerLegacy
{

	public function display($cachable = false, $urlparams = false)
	{
		$view   = $this->input->get('view', 'contacts');
		$layout = $this->input->get('layout', 'default', 'string');
		$id     = $this->input->getInt('id');
		parent::display();
		return $this;
	}


}
