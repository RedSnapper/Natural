<?php
/**
 * @copyright	Copyright ©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die('Restricted access');
JLoader::import('joomla.application.component.modellist');

class CompositeModelCategory extends JModelList
{
	protected $composition;
	public function getComposite()
	{
		if (!isset($this->composition))
		{
			$composition  = 'Composite Category';
		}
		return $this->composition;
	}
}
