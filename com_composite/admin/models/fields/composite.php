<?php
defined('_JEXEC') or die;

JFormHelper::loadFieldClass('list');
class JFormFieldComposite extends JFormFieldList
{
	protected $type = 'Composite';
	protected function getOptions()
	{
		$app = JApplication::getInstance('site');
		$mitems  = $app->getMenu()->getMenu();
		$mtypes = array();
		$options  = array();
		foreach ($mitems as $item) {
		  array_push($mtypes,$item->menutype);
		}
		$mtypes = array_unique($mtypes);
		foreach ($mtypes as $key => $value)
		{
			$options[] = JHtml::_('select.option', $value, ucfirst($value));
		}
		$options = array_merge(parent::getOptions(), $options);
		return $options;
	}
}
