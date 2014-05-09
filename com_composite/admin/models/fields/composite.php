<?php
defined('_JEXEC') or die;
/**
 * @copyright	Copyright ©2013-2014 Red Snapper Ltd. All rights reserved.
 * @license		GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */
JFormHelper::loadFieldClass('list');
class JFormFieldComposite extends JFormFieldList
{
	protected $type = 'Composite';
	protected function getOptions()
	{
		$app = JApplication::getInstance('site');
		$mmodel	= JModelLegacy::getInstance('menus', 'menusModel');
		$mitems	=  $mmodel->getItems();
		$options  = array();
		foreach ($mitems as $item)
		{
			$options[] = JHtml::_('select.option', $item->menutype, $item->title);
		}
		$options = array_merge(parent::getOptions(), $options);
		return $options;
	}
}
