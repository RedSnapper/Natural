<?php
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
