<?php
include_once './Services/Component/classes/class.ilPluginConfigGUI.php';
/**
 * Example user interface plugin
 *
 * @author Guido Vollbach <gvollbach@databay.de>
 * @version $Id$
 *
 */
class ilMassMaticsConfigGUI extends ilPluginConfigGUI
{


	/**
	 * @var ilCtrl
	 */
	protected $ilCtrl;

	/**
	 * @var ilLanguage
	 */
	protected $lng;

	/**
	 * @var ilSetting
	 */
	protected $ilSetting;

	/**
	 * @var ilTemplate
	 */
	protected $tpl;

	/**
	 * ilMassMaticsConfigGUI constructor.
	 */
	public function __construct()
	{
		/**
		 * ilLanguage $lng
		 * 
		 * 
		 */
		global $lng, $ilCtrl, $ilSetting, $tpl;
		
		$this->ilCtrl 		= $ilCtrl;
		$this->lng 			= $lng;
		$this->ilSetting 	= $ilSetting;
		$this->tpl 			= $tpl;
	}

	/**
	 * Handles all commmands, default is "configure"
	 */
	function performCommand($cmd)
	{

		switch ($cmd)
		{
			case "configure":
			case "save":
				$this->$cmd();
				break;

		}
	}

	/**
	 * Configure screen
	 */
	function configure()
	{
		$form = $this->initConfigurationForm();
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		
		$pl = $this->getPluginObject();

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		$ti = new ilTextInputGUI($pl->txt("massmatics_url"), "massmatics_url");
		$ti->setRequired(true);
		$ti->setValue($this->ilSetting->get('massmatics_url'));
		$form->addItem($ti);
		
		$ti = new ilTextInputGUI($pl->txt("width"), "width");
		$ti->setRequired(true);
		$ti->setValue($this->ilSetting->get('massmatics_width'));
		$form->addItem($ti);

		$ti = new ilTextInputGUI($pl->txt("height"), "height");
		$ti->setRequired(true);
		$ti->setValue($this->ilSetting->get('massmatics_height'));
		$form->addItem($ti);

		$form->addCommandButton("save", $this->lng->txt("save"));

		$form->setTitle($pl->txt("massmatics_plugin_configuration"));
		$form->setFormAction($this->ilCtrl->getFormAction($this));

		return $form;
	}

	/**
	 * Save form input (currently does not save anything to db)
	 *
	 */
	public function save()
	{
		$pl = $this->getPluginObject();

		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			$url	= $form->getInput("massmatics_url");
			$width 	= $form->getInput("width");
			$height = $form->getInput("height");

			$this->ilSetting->set('massmatics_url', $url);
			$this->ilSetting->set('massmatics_width', $width);
			$this->ilSetting->set('massmatics_height', $height);

			ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
			$this->ilCtrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$this->tpl->setContent($form->getHtml());
		}
	}

}