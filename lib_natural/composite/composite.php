<?php
/**
 * @package     LibNatural
 *
 * @copyright   Copyright (C) 2005 - 2014 Red Snapper Ltd. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;
mb_internal_encoding( 'UTF-8' );

JLoader::import('cms.application.applicationcms');
JLoader::import('joomla.application.component.modelitem');

//Helper extensions

class CompositeJInput extends JInput
{

	public static function getData(JInput $j) {
		return $j->data;
	}

	public static function setData(JInput $j, array $a) {
		$j->data = $a;
	}

}

class CompositeJApplication extends JApplicationCms
{
	public static function setTemplate($app, $tmpl = null) {
		$app->template = $tmpl;
	}
}

class NComposite
{
	public static function makeView($jc, $cname, $cpath, $tpath, $layout, $name, $type) {
		$view = null;
		$prefix = $cname . 'view';
		if (file_exists($tpath)) {
			$view = $jc->getView($name,$type,$prefix,array( 'layout' => $layout, 'base_path' => $cpath, 'template_path' => $tpath));
		} else {
			$view = $jc->getView($name,$type,$prefix,array( 'layout' => 'default', 'base_path' => $cpath ));
		}
		if (! $view ) {
			$view = null;
			$app  = JFactory::getApplication();
			$app->enqueueMessage( 'Failed to make view with path ' . $tpath . ', layout ' . $layout . ', name ' . $name . ', type ' . $type ,'error');
		}
		return $view;
	}


//temporary storage..
	protected $j_tmp_input;
	protected $j_tmp_array;
	protected $j_tmp_template;

//used by doComposite, set up during pushState..
	protected $j_type;
	protected $j_menu;

	public function doComposite($i) {
		if ($i->type === "alias") {
			$ni = $i->params['aliasoptions'];
			$i = $j_menu->getItem($ni);
		}
		CompositeJApplication::setTemplate($j_app);
		$j_menu->setActive($i->id);
		$j_app->input->set('Itemid',$i->id);
		$layout="default";
		foreach ($i->query as $key => $value) {
			if ($key === "id") {
				$j_app->input->set("a_id",$value);
			}
			if ($key === "layout") {
				$layout=$value;
			}
			$j_app->input->set($key,$value);
		}
		$option = $i->query['option'];
		$cbase = substr($option,4);
		$ccbase = ucfirst($cbase);
		$cclass= $ccbase . 'Controller';
		$template = $j_app->getTemplate(true)->template;
		$cpath = JPATH_BASE . '/components/' . $option ;
		$lang->load($option);
		$lang->load($option,$cpath);
		$vname = $i->query['view'];
		$dpath = 'components.' . $option;
		$tpath = JPATH_THEMES . '/' . $template . '/' . $j_type . '/' . $option .'/'. $vname .'/';
//There must be a better way..
		JLoader::import($dpath. '.controller', JPATH_BASE );
		JLoader::import($dpath . '.models.' . $vname, JPATH_BASE );
		JLoader::import($dpath . '.models.' . $vname . 's', JPATH_BASE );
		JLoader::import($dpath . '.models.archive', JPATH_BASE );
		JLoader::import($dpath . '.models.category', JPATH_BASE );
		JLoader::import($dpath . '.models.categories', JPATH_BASE );
		JLoader::import($dpath . '.helpers.route', JPATH_BASE );
		JLoader::import($dpath . '.helpers.category', JPATH_BASE );
		JLoader::import($dpath . '.helpers.query', JPATH_BASE );
		JFormHelper::addFormPath($cpath . '/models/' . 'forms');
		JFormHelper::addFormPath($cpath . '/models/' . 'form');
		$jc = new $cclass(array( 'base_path' => $cpath, 'view_path' => $cpath . '/views/'));
		$jv = NComposite::makeView($jc, $ccbase, $cpath, $tpath, $layout, $vname , $j_type );
		try {
			$jc->display();
		}
		catch (Exception $e) {
			$j_app->enqueueMessage( $e->getMessage() . ' while rendering ' . $tpath . ' with layout ' . $template ,'error');
		}
	}

//keep a request safe while making changes...
	public function pushState() {
		$j_app  = JFactory::getApplication();
		$lang = JFactory::getLanguage();
		$j_tmp_array = CompositeJInput::getData($j_app->input);
		$j_tmp_input = $j_app->input;
		$j_tmp_template = $j_app->getTemplate(true);
		$j_app->input->set('hitcount',0);
		$document = JFactory::getDocument();
		$j_type = $document->getType(); //'html'
		$j_menu = $j_app->getMenu();
	}

//restore a request after making changes...
	public function popState() {
		$j_app->input = $j_tmp_input;
		CompositeJInput::setData($j_app->input,$j_tmp_array);
		$j_menu->setActive($this->item->id);
		CompositeJApplication::setTemplate($j_app,$j_tmp_template);
	}

}

