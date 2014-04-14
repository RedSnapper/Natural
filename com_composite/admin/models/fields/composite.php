<?php
defined('_JEXEC') or die;

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
