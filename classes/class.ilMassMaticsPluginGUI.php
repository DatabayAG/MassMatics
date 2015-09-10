<?php

include_once("./Services/COPage/classes/class.ilPageComponentPluginGUI.php");
 
/**
 *
 * @author Guido Vollbach <gvollbach@databay.de>
 * @version $Id$
 *
 * @ilCtrl_isCalledBy ilMassMaticsPluginGUI: ilPCPluggedGUI
 */
class ilMassMaticsPluginGUI extends ilPageComponentPluginGUI
{
	/**
	 * ilMassMaticsPluginGUI constructor.
	 */
	public function __construct()
	{
		global $ilCtrl, $lng, $ilSetting,  $ilTabs, $tpl, $ilUser;
		
		$this->ctrl 	= $ilCtrl;
		$this->tpl 		= $tpl;
		$this->ilTabs 	= $ilTabs;
		$this->lng 		= $lng;
		$this->ilSetting= $ilSetting;
		$this->ilUser 	= $ilUser;
	}


	function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass();

		switch($next_class)
		{
			default:
				// perform valid commands
				$cmd = $this->ctrl->getCmd();
				if (in_array($cmd, array("create", "save", "edit", "edit2", "update", "cancel")))
				{
					$this->$cmd();
				}
				break;
		}
	}
	
	

	function insert()
	{
		$form = $this->initForm(true);
		$this->tpl->setContent($form->getHTML());
	}
	
	/**
	 * Save new pc example element
	 */
	public function create()
	{
		$form = $this->initForm(true);
		if ($form->checkInput())
		{
			$properties = array(
				"ct_id" => $form->getInput("ct_id"),
				"auth_pwd" => $form->getInput("auth_pwd")
				);
			if ($this->createElement($properties))
			{
				ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$this->tpl->setContent($form->getHtml());
	}
	

	function edit()
	{		
		$this->setTabs("edit");
		
		$form = $this->initForm();
		$this->tpl->setContent($form->getHTML());		
	}
	

	function update()
	{
		$form = $this->initForm(true);
		if ($form->checkInput())
		{
			$properties = array(
				"ct_id" => $form->getInput("ct_id"),
				"auth_pwd" => $form->getInput("auth_pwd")
				);
			if ($this->updateElement($properties))
			{
				ilUtil::sendSuccess($this->lng->txt("msg_obj_modified"), true);
				$this->returnToParent();
			}
		}

		$form->setValuesByPost();
		$this->tpl->setContent($form->getHtml());

	}
	
	public function initForm($a_create = false)
	{
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();

		$ct_id = new ilTextInputGUI($this->getPlugin()->txt("ct_id"), "ct_id");
		$ct_id->setMaxLength(40);
		$ct_id->setSize(40);
		$ct_id->setRequired(true);
		$form->addItem($ct_id);
		
		$auth_pwd = new ilTextInputGUI($this->getPlugin()->txt("auth_pwd"), "auth_pwd");
		$auth_pwd->setMaxLength(40);
		$auth_pwd->setSize(40);
		$auth_pwd->setRequired(true);
		$form->addItem($auth_pwd);
		
		if (!$a_create)
		{
			$prop = $this->getProperties();
			$ct_id->setValue($prop["ct_id"]);
			$auth_pwd->setValue($prop["auth_pwd"]);
		}
		
		if ($a_create)
		{
			$this->addCreationButton($form);
			$form->addCommandButton("cancel", $this->lng->txt("cancel"));
			$form->setTitle($this->getPlugin()->txt("cmd_insert"));
		}
		else
		{
			$form->addCommandButton("update", $this->lng->txt("save"));
			$form->addCommandButton("cancel", $this->lng->txt("cancel"));
			$form->setTitle($this->getPlugin()->txt("edit_ex_el"));
		}
		
		$form->setFormAction($this->ctrl->getFormAction($this));
		
		return $form;
	}

	/**
	 * Cancel
	 */
	function cancel()
	{
		$this->returnToParent();
	}
	
	/**
	 * Get HTML for element
	 *
	 * @param string $a_mode (edit, presentation, preview, offline)s
	 * @return string $html
	 */
	function getElementHTML($a_mode, array $a_properties, $a_plugin_version)
	{
		$pl = $this->getPlugin();
		$tpl = $pl->getTemplate("tpl.content.html");
		$tpl->setVariable("URL", $this->getMassMaticsUrl($a_properties));
		$tpl->setVariable("WIDTH", $this->ilSetting->get('massmatics_width'));
		$tpl->setVariable("HEIGHT", $this->ilSetting->get('massmatics_height'));
		return $tpl->get();
	}
	
	function setTabs($a_active)
	{
		$pl = $this->getPlugin();
		$this->ilTabs->addTab("edit", $pl->txt("config"),
			$this->ctrl->getLinkTarget($this, "edit"));

		$this->ilTabs->activateTab($a_active);
	}
	
	function getMassMaticsUrl($a_properties)
	{
		$url = $this->ilSetting->get('massmatics_url');
		$url .= '?user=' . $this->ilUser->getId();
		$url .= '&ct_id=' . $a_properties['ct_id'];
		return $this->buildMassMaticsUrlWithUrlSigner($url,  $a_properties['auth_pwd']);
	}
	
	private function buildMassMaticsUrlWithUrlSigner($url, $auth_pwd)
	{
		require_once($this->plugin->getDirectory() . '/lib/urlsigner.class.php');
		$url_signer = new URLSigner();
		return $url_signer->appendMAC($url, $auth_pwd);
	}
}

?>
