<?php
defined ( '_JEXEC' ) or die;
class plgContentArticle extends JPlugin {
	protected $autoloadLanguage = true;
	function onContentPrepareForm($form, $data) {
		$app = JFactory::getApplication();
		$option = $app->input->get('option');
		switch($option) {
			case 'com_content': {
				if ( $app->isAdmin() ) {
					JForm::addFormPath(__DIR__ . '/forms');
					$form->loadFile('default', false);
				}
			} break;
			default: break;
		}
		return true;
	}
}
