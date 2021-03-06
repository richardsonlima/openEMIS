<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

//App::uses('AppController', 'Controller');
App::uses('Sanitize', 'Utility');
App::uses('Converter', 'Translations.Lib');
class TranslationsController extends AppController {
	public $components = array('Paginator');

	public $languageOptions = array(
			'ara' => 'العربية',
			'chi' => '中文',
			'fre' => 'Français',
			'rus' => 'русский',
			'spa' => 'español'
		);

	public $accessMapping = array(
		'compile' => 'update',
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->set('contentHeader', $this->Message->getLabel('Translation.title'));
		$this->set('portletHeader', $this->Message->getLabel('Translation.title'));

		$this->bodyTitle = $this->Message->getLabel('admin.title');
		$this->Navigation->addCrumb($this->Message->getLabel('admin.title'), array('controller' => 'Admin', 'action' => 'view', 'plugin' => false));
		$this->Navigation->addCrumb('Translations', array('controller' => 'Translations', 'action' => 'index'));

		$this->set('model', 'Translation');
		$this->set('tabElement', '../Admin/tabs');
	}

	public function index() {
		$selectedLang = empty($this->params['pass'][0]) ? 'ara' : $this->params['pass'][0];
		$this->Session->write('Translation.selectedLang', $selectedLang);

		$this->Navigation->addCrumb('List of Translations');
		$header = __('List of Translations');

		$searchKey = $this->Session->read('Translation.SearchField');
		$languageOptions = $this->languageOptions;

		if ($this->request->is('post', 'put')) {
			if (isset($this->request->data['Translation']['SearchField'])) {
				$searchKey = $this->request->data['Translation']['SearchField'];

				$this->Session->delete('Translation.SearchField');
				$this->Session->write('Translation.SearchField', $searchKey);
			}
		} 
		if (!empty($searchKey)) {
			$searchField = Sanitize::escape(trim($searchKey));
			$options['conditions']['Translation.eng LIKE'] = '%' . $searchField . '%';
		}
		
		$options['order'] = array('Translation.eng' => 'asc');
		//$conditions = array('order' => array('Translation.eng' => 'asc'), 'conditions' => array('Translation.eng LIKE' => '%home%'));
		$this->Paginator->settings = array_merge(array('limit' => 30, 'maxLimit' => 100), $options);

		$data = $this->Paginator->paginate('Translation');
		if (empty($data)){
			$this->Message->alert('general.search.noResult');
		}
		if(empty($data)) $this->Message->alert('general.view.noRecords');
		$this->set(compact('header', 'data', 'languageOptions', 'selectedLang', 'searchKey'));
	}

	public function view() {
		if (empty($this->params['pass'][0])) {
			return $this->redirect(array('action' => 'index'));
		}

		$id = $this->params['pass'][0];

		$this->Navigation->addCrumb('Translation Details');
		$header = __('Translation Details');

		$data = $this->Translation->findById($id);

		$fields = $this->Translation->getFields();
		$this->Session->write('Translation.id', $id);

		$this->set(compact('header', 'data', 'fields', 'id'));
	}

	public function edit() {
		$this->Navigation->addCrumb('Edit Translation');
		$header = __('Edit Translation');
		$fields = $this->Translation->getFields();
		$this->set(compact('header','fields'));
		$this->setupAddEditForm('edit');
		$this->render('edit');
	}

	public function add() {
		$this->Navigation->addCrumb('Add Translation');
		$header = __('Add Translation');
		$fields = $this->Translation->getFields();
		$this->set(compact('header','fields'));
		$this->setupAddEditForm('edit');
		$this->render('edit');
	}

	private function setupAddEditForm($type) {
		$id = empty($this->params['pass'][0]) ? 0 : $this->params['pass'][0];
		if ($this->request->is('post') || $this->request->is('put')) {
			$this->request->data['Translation']['code'] = empty($this->request->data['Translation']['code'])? NULL: nl2br($this->request->data['Translation']['code']);
			$this->request->data['Translation']['eng'] = nl2br($this->request->data['Translation']['eng']);
			$this->request->data['Translation']['ara'] = empty($this->request->data['Translation']['ara'])? NULL: nl2br($this->request->data['Translation']['ara']);
			$this->request->data['Translation']['spa'] = empty($this->request->data['Translation']['spa'])? NULL: nl2br($this->request->data['Translation']['spa']);
			$this->request->data['Translation']['chi'] = empty($this->request->data['Translation']['chi'])? NULL: nl2br($this->request->data['Translation']['chi']);
			$this->request->data['Translation']['rus'] = empty($this->request->data['Translation']['rus'])? NULL: nl2br($this->request->data['Translation']['rus']);
			$this->request->data['Translation']['fre'] = empty($this->request->data['Translation']['fre'])? NULL: nl2br($this->request->data['Translation']['fre']);
			if ($this->Translation->save($this->request->data)) {
				$this->Message->alert('general.' . $type . '.success');
				return $this->redirect(array('action' => 'index'));
			}
		} else {
			$this->recursive = -1;
			$data = $this->Translation->findById($id);
			if (!empty($data)) {
				$this->request->data = $data;
			}
		}
	}

	public function delete() {
		if ($this->Session->check('Translation.id')) {
			$id = $this->Session->read('Translation.id');
			if ($this->Translation->delete($id)) {
				$this->Message->alert('general.delete.success');
			} else {
				$this->Message->alert('general.delete.failed');
			}

			$this->Session->delete('Translation.id');
			return $this->redirect(array('action' => 'index'));
		}
	}

	public function compile() {
		if ($this->Session->check('Translation.selectedLang')) {
			$lang = $this->Session->read('Translation.selectedLang');

			$this->generatePO($lang);
			$this->generateMO($lang);

			$this->Message->alert('general.translation.success');
			return $this->redirect(array('action' => 'index','plugin'=>false));
		}
	}

	public function importPOFile($lang = 'ara') {
		$this->autoRender = false;
		$localDir = App::path('locales');
		$localDir = $localDir[0];
		$localeImportFile = $localDir . $this->convertLangCodeFromCoreToSchool($lang) . DS . 'LC_MESSAGES' . DS . 'update.po';
		if (is_file($localeImportFile)) {
			//echo '<meta content="text/html; charset=utf-8" http-equiv="Content-Type">';
			$translations = I18n::loadPo($localeImportFile);

			$saveData = array();
			$counter = 0;
			foreach ($translations as $tKey => $tValue) {
				if (!empty($tKey)) {
					$saveData[$counter]['eng'] = $tKey;
					$saveData[$counter][$lang] = $tValue;
					$counter ++;
				}
			}

			$data = $this->Translation->find('all');

			if (!empty($data)) {
				foreach ($saveData as $tKey => $tValue) {

					$conditions = array(
						'Translation.eng' => $tValue['eng'],
					);
					if ($this->Translation->hasAny($conditions)) {
						$this->Translation->recursive = -1;
						$transData = $this->Translation->findByEng($tValue['eng'], array('id'));
						$saveData[$tKey]['id'] = $transData['Translation']['id'];
					}
				}
			}

			if ($this->Translation->saveAll($saveData)) {
				return $this->redirect(array('action' => 'index', $lang));
			}
		}
	}

	public function convertLangCodeFromCoreToSchool($thisCode) {
		switch($thisCode) {
			case 'chi': return 'zho';
			case 'fre': return 'fra';
		}
		return $thisCode;
	}

	public function generatePO($lang = 'ara') {
		$this->autoRender = false;

		$localDir = App::path('locales');
		$localDir = $localDir[0];
		$localeImportFile = $localDir . $this->convertLangCodeFromCoreToSchool($lang) . DS . 'LC_MESSAGES' . DS . 'default.po';

		$data = $this->Translation->find('all', array('fields' => array('eng', $lang)));
		
		if (!file_exists($localeImportFile)) {
			$opFile = fopen($localeImportFile, 'w');
			fclose($opFile);
		}

		chmod($localeImportFile, 0666);
		
		if (is_writable($localeImportFile)) {
			$opFile = fopen($localeImportFile, 'w');
			fwrite($opFile, "msgid \"\"\n");
			fwrite($opFile, "msgstr \"\"\n");
	
			$format = '"%s\n"';
			fprintf($opFile, $format, 'Project-Id-Version: Openemis Version 2.0');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'POT-Creation-Date: 2013-01-17 02:33+0000');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'PO-Revision-Date: ' . date('Y-m-d H:i:sP'));
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Last-Translator: ');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Language-Team: ');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'MIME-Version: 1.0');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Content-Type: text/plain; charset=UTF-8');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Content-Transfer-Encoding: 8bit');
			fwrite($opFile, "\n");
			fprintf($opFile, $format, 'Language: ' . $lang);
			fwrite($opFile, "\n");
	
	
			foreach ($data as $translateWord) {
				$key = $translateWord['Translation']['eng'];
				$value = $translateWord['Translation'][$lang];
				fwrite($opFile, "\n");
				fwrite($opFile, "msgid \"$key\"\n");
				fwrite($opFile, "msgstr \"$value\"\n");
			}
			fclose($opFile);
		} 
	}

	public function generateMO($lang = 'ara') {
		$this->autoRender = false;

		$localDir = App::path('locales');
		$localDir = $localDir[0];
		$source = $localDir . $this->convertLangCodeFromCoreToSchool($lang) . DS . 'LC_MESSAGES' . DS . 'default.po';
		$destination = $localDir . $this->convertLangCodeFromCoreToSchool($lang) . DS . 'LC_MESSAGES' . DS . 'default.mo';

		Converter::convertToMo($source, $destination);
	}

	public function populatetransDB() {
		$this->autoRender = false;
		$this->render = false;
		pr($this->Translation->populate_database(
				$this->Message->getAllData()));
	}



}
