<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/libs/autoload.php');

class IPSMusicCastDevice extends IPSModule
{
public function Create()
{
        parent::Create();
		$this->RegisterPropertyString('DeviceID', ''); //Device ID
		$this->RegisterPropertyString('Host', ''); //Device IP
		$this->RegisterPropertyString('Name', ''); //Device Name
		$this->RegisterPropertyString('GroupID', ''); //Group ID
		$this->RegisterPropertyBoolean('Coordinator', false); //Is Device a Coordinator?
		$this->RegisterPropertyInteger('NetworkInterface', 0); //Interface ID for Multicast

		$this->RegisterVariableBoolean("Power", "Power");
		IPS_SetVariableCustomProfile($this->GetIDForIdent("Power"), "~Switch");
		IPS_SetPosition($this->GetIDForIdent("Power"), 0);
		$this->EnableAction("Power");
		
		$this->RegisterVariableString("GroupName", "Group Name");
		IPS_SetPosition($this->GetIDForIdent("GroupName"), 10);
		
		$this->RegisterVariableInteger("State", "State");
		IPS_SetVariableCustomProfile($this->GetIDForIdent("State"), "MUC_State");
		IPS_SetPosition($this->GetIDForIdent("State"), 15);
		$this->EnableAction("State");
		
		$this->RegisterVariableInteger("Previous", "Previous");
		IPS_SetVariableCustomProfile($this->GetIDForIdent("Previous"), "MUC_Previous");
		IPS_SetPosition($this->GetIDForIdent("Previous"), 20);
		$this->EnableAction("Previous");
		
		$this->RegisterVariableInteger("Next", "Next");
		IPS_SetVariableCustomProfile($this->GetIDForIdent("Next"), "MUC_Next");
		IPS_SetPosition($this->GetIDForIdent("Next"), 25);
		$this->EnableAction("Next");
		
		$this->RegisterVariableInteger("Volume", "Volume");
		IPS_SetPosition($this->GetIDForIdent("Volume"), 30);
		IPS_SetVariableCustomProfile($this->GetIDForIdent("Volume"), "MUC_Volume");
		$this->EnableAction("Volume");
		
		$this->RegisterVariableBoolean("Mute", "Mute");
		IPS_SetVariableCustomProfile($this->GetIDForIdent("Mute"), "MUC_Mute");
		IPS_SetPosition($this->GetIDForIdent("Mute"), 35);
		$this->EnableAction("Mute");
		
		$this->RegisterVariableBoolean("Shuffle", "Shuffle");
		IPS_SetVariableCustomProfile($this->GetIDForIdent("Shuffle"), "~Switch");
		IPS_SetPosition($this->GetIDForIdent("Shuffle"), 40);
		$this->EnableAction("Shuffle");
		
		$this->RegisterVariableBoolean("Repeat", "Repeat");
		IPS_SetVariableCustomProfile($this->GetIDForIdent("Repeat"), "~Switch");
		IPS_SetPosition($this->GetIDForIdent("Repeat"), 45);
		$this->EnableAction("Repeat");

		$this->RegisterVariableInteger("Input", "Input");
		IPS_SetPosition($this->GetIDForIdent("Input"), 50);
		$this->EnableAction("Input");

		$this->RegisterVariableInteger("Favorite", "Favorite");
		IPS_SetPosition($this->GetIDForIdent("Favorite"), 51);
		$this->EnableAction("Favorite");
		
		$this->RegisterVariableString("Playtime", "Play Time");
		IPS_SetPosition($this->GetIDForIdent("Playtime"), 55);
		
		$this->RegisterVariableString("Title", "Title");
		IPS_SetPosition($this->GetIDForIdent("Title"), 60);
		
		$this->RegisterVariableString("Artist", "Artist");
		IPS_SetPosition($this->GetIDForIdent("Artist"), 65);
		
		$this->RegisterVariableString("Album", "Album");
		IPS_SetPosition($this->GetIDForIdent("Album"), 70);
		
		$this->RegisterVariableString("AlbumArt", "Cover");
		IPS_SetPosition($this->GetIDForIdent("AlbumArt"), 75);
		IPS_SetVariableCustomProfile($this->GetIDForIdent("AlbumArt"), "~HTMLBox");

		// register update timer for Device subscription
		$this->RegisterTimer('subscribeDevicesTimer', 2000, 'MUC_subscribeDevice($_IPS[\'TARGET\']);');

		// register onetime-timer for Device Setup
		$this->RegisterTimer('setupOneTimeTimer', 2000, 'MUC_DeviceSetup($_IPS[\'TARGET\']);');
    }

public function Destroy()
{
        parent::Destroy();
		//Funktioniert nicht, warum?
		
		/*if (IPS_VariableProfileExists("MUC_Input_" . $this->ReadPropertyString('DeviceID')))
		{
			IPS_DeleteVariableProfile("MUC_Input_" . $this->ReadPropertyString('DeviceID'));
		}*/
    }

public function ApplyChanges()
{
        parent::ApplyChanges();
		$this->ConnectParent("{82347F20-F541-41E1-AC5B-A636FD3AE2D8}");
		SetValueInteger($this->GetIDForIdent("Previous"),1);
		SetValueInteger($this->GetIDForIdent("Next"),1);
    }

protected function getMusicCastNetworkObj()
{
		$MUCNetworkObj = new MusicCast\Network;
		$MUCNetworkObj->setNetworkInterface($this->ReadPropertyInteger('NetworkInterface'));
		return $MUCNetworkObj;
}

protected function getMusicCastClientObj()
{
	try {
		$DeviceIP = $this->ReadPropertyString('Host');
		return new MusicCast\Client(['host' => $DeviceIP,'port' => 80,]);
	}
	catch (Exception $e) {
		echo 'Error: ',  $e->getMessage(), "\n";
		//$this->SetStatus(104);
		exit(1);
	}
}

protected function getMusicCastDeviceObj()
{
	try {
		$DeviceIP = $this->ReadPropertyString('Host');
		return new MusicCast\Device($DeviceIP);
	}
	catch (Exception $e) {
		echo 'Error: ',  $e->getMessage(), "\n";
		//$this->SetStatus(104);
		exit(1);
	}
}

protected function getMusicCastSpeakerObj($MUCDeviceObj)
{
	try {
		return new MusicCast\Speaker($MUCDeviceObj);
	}
	catch (Exception $e) {
		echo 'Error: ',  $e->getMessage(), "\n";
		//$this->SetStatus(104);
		exit(1);
	}
}

protected function getMusicCastControllerObj($MUCControllerObj,$MUCNetworkObj)
{
	try {
		return new MusicCast\Controller($MUCControllerObj,$MUCNetworkObj,1);
	}
	catch (Exception $e) {
		echo 'Error: ',  $e->getMessage(), "\n";
		//$this->SetStatus(104);
		exit(1);
	}
}

protected function getMusicCastFavoriteObj($ID,$MUCFavoritArray,$MUCSpeakerObj)
{
	try {
		return new MusicCast\Favorite($ID,$MUCFavoritArray,$MUCSpeakerObj);
	}
	catch (Exception $e) {
		echo 'Error: ',  $e->getMessage(), "\n";
		//$this->SetStatus(104);
		exit(1);
	}
}

public function subscribeDevice()
	{
		try {
			$this->updateSpeakerIP();
			$musicCastClientObj = $this->getMusicCastClientObj();
			$result = $musicCastClientObj->api('events')->subscribe();
		}
		catch (Exception $e) {
			echo 'Error: ',  $e->getMessage(), "\n";
			//$this->SetStatus(104);
			exit(1);
		}
		IPS_LogMessage("MUC ". $this->ReadPropertyString('Name'), "Subscribe Device: " . $this->ReadPropertyString('Host'));
		$timer = 540000; //9 Minuten
		$this->SetTimerInterval('subscribeDevicesTimer', $timer);
	}

public function updateSpeakerIP()
{
		//Clear cache
		$tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "musiccast";
		@$this->delete_files($tempPath);
		//Compare IPs
		$CurrentIP = $this->getSpeakerIPbyName($this->ReadPropertyString('Name'));
//		$CurrentIP = $this->getSpeakerByIp($this->ReadPropertyString('Host'));
		if($CurrentIP != $this->ReadPropertyString('Host'))
			{
				IPS_LogMessage("MUC " . $this->ReadPropertyString('Name'), "Device IP updated");
				IPS_SetProperty($this->InstanceID, "Host", $CurrentIP);
				IPS_ApplyChanges($this->InstanceID);
			} else {
				IPS_LogMessage("MUC " . $this->ReadPropertyString('Name'), "Device IP is still valid");
			}
		$this->SetStatus(102);
}

//Get Data from UDP Socket
public function ReceiveData($JSONString)
{
			$data = json_decode($JSONString);
			//Parse and write values to our buffer
			$this->SetBuffer("MusicCastDeviceBuffer", utf8_decode($data->Buffer));
			$MUC_Buffer = $this->GetBuffer("MusicCastDeviceBuffer");
			$MUC_Buffer_JSON = json_decode($MUC_Buffer,false);
			//print_r($MUC_Buffer_JSON);
			if ($MUC_Buffer_JSON->device_id == $this->ReadPropertyString('DeviceID'))
			{
				if (key($MUC_Buffer_JSON) == 'main') {
					if (property_exists($MUC_Buffer_JSON->main, 'mute')) {
							IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [Main]", $MUC_Buffer_JSON->main->mute);
							$this->SetValue("Mute", $MUC_Buffer_JSON->main->mute);
						}
					if (property_exists($MUC_Buffer_JSON->main, 'volume')) {
							IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [Main] Volume", $MUC_Buffer_JSON->main->volume);
							$this->SetValue("Volume", $MUC_Buffer_JSON->main->volume);
						}
					if (property_exists($MUC_Buffer_JSON->main, 'input')) {
							IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [Main] Input", $MUC_Buffer_JSON->main->input);
							SetValue($this->GetIDForIdent('Input'), $this->getVariableIntegerbyName($this->GetIDForIdent('Input'),$MUC_Buffer_JSON->main->input));
							if($MUC_Buffer_JSON->main->input == "net_radio")
							{
								IPS_SetHidden($this->GetIDForIdent("Playtime"),false);
							} else {
								IPS_SetHidden($this->GetIDForIdent("Playtime"),true);
							}
						}
					if (property_exists($MUC_Buffer_JSON->main, 'signal_info_updated')) {
							IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [Main]->signal_info_updated", $MUC_Buffer_JSON->main->signal_info_updated);
							$this->signalInfoUpdated();
						}	
					if (property_exists($MUC_Buffer_JSON->main, 'power')) {
							IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [Main]->power", $MUC_Buffer_JSON->main->power);
							if ($MUC_Buffer_JSON->main->power == 'on')
							{
								SetValue($this->GetIDForIdent('Power'),true);
								$this->setHiddenDeviceVariable($this->GetIDForIdent('Power'),false);
							}else
							{
								SetValue($this->GetIDForIdent('Power'),false);
								$this->setHiddenDeviceVariable($this->GetIDForIdent('Power'),true);
							}
						}
				}
				if (key($MUC_Buffer_JSON) == 'netusb')
				{
					
					if (property_exists($MUC_Buffer_JSON->netusb, 'play_time')) {
						$playtime = gmdate("H:i:s", $MUC_Buffer_JSON->netusb->play_time);
						$this->SetValue("Playtime", $playtime);
					}
					if (property_exists($MUC_Buffer_JSON->netusb, 'play_info_updated')) {
						IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [netusb]->player_info_updated", $MUC_Buffer_JSON->netusb->play_info_updated);
						$this->playInfoUpdated();
					}
					if(key($MUC_Buffer_JSON->netusb) == 'preset_control')
					{
						if (property_exists($MUC_Buffer_JSON->netusb->preset_control, 'num')) {
							IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [netusb]->preset_control", $MUC_Buffer_JSON->netusb->preset_control->type);
							$FavoriteID = $MUC_Buffer_JSON->netusb->preset_control->num;
							$FavoriteID = $FavoriteID-1;
							$this->SetValue("Favorite", $FavoriteID);
						}
					}
				}
				if (key($MUC_Buffer_JSON) == 'dist')
				{

					if (property_exists($MUC_Buffer_JSON->dist, 'dist_info_updated')) {
						IPS_LogMessage("MUC " . $this->ReadPropertyString('Name') . " [dist]->dist_info_updated", $MUC_Buffer_JSON->dist->dist_info_updated);
						$this->updateCoordinatorStatus();
					}
				}
			}
		}

public function playInfoUpdated()
	{
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		$MUCDeviceObj = $this->getMusicCastDeviceObj();
		$MUCSpeakerObj = $this->getMusicCastSpeakerObj($MUCDeviceObj);
		IPS_LogMessage("MUC " . $this->ReadPropertyString('Name'), "Play Info Update");
		if ($this->ReadPropertyBoolean('Coordinator') == true)
		{
			$MUCControllerObj = $this->getMusicCastControllerObj($MUCSpeakerObj,$MUCNetworkObj,1);
			//Update Album Details
			$StateDetails = $MUCControllerObj->getStateDetails();
			$this->SetValue("Title", $this->getObjProp($StateDetails->track,"title"));
			$this->SetValue("Artist", $this->getObjProp($StateDetails->track,"artist"));
			$this->SetValue("Album", $this->getObjProp($StateDetails->track,"album"));
			if ($this->getObjProp($StateDetails->track,"albumArt") == "")
			{
				IPS_SetHidden($this->GetIDForIdent('AlbumArt'),true);
			}else{
				$AlbumArtHTML = "<img src='" . $this->getObjProp($StateDetails->track,"albumArt") . "' style='height: 20%; width: 20%; object-fit: fill;' />";
				$this->SetValue("AlbumArt", $AlbumArtHTML);
				IPS_SetHidden($this->GetIDForIdent('AlbumArt'),false);
			}
			//Update Repeat
			$this->SetValue("Repeat", $MUCControllerObj->getRepeat());
			//Update Shuffle
			$this->SetValue("Shuffle", $MUCControllerObj->getShuffle());
		} else { //Speaker is slave
		}
			
			
	}

public function signalInfoUpdated()
	{
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		$MUCDeviceObj = $this->getMusicCastDeviceObj();
		$MUCSpeakerObj = $this->getMusicCastSpeakerObj($MUCDeviceObj);
		IPS_LogMessage("MUC " . $this->ReadPropertyString('Name'), "Signal Info Update");
		if ($this->ReadPropertyBoolean('Coordinator') == true)
		{
			$MUCControllerObj = $this->getMusicCastControllerObj($MUCSpeakerObj,$MUCNetworkObj,1);

			//Update State (play,stop,pause)
			$this->SetValue("State", $MUCControllerObj->getState());

			//Update Group Infos
			$this->SetValue("GroupName", $MUCControllerObj->getGroupName());
			IPS_SetProperty($this->InstanceID, "GroupID", $MUCControllerObj->getGroup());
			IPS_ApplyChanges($this->InstanceID);
		} else { //Speaker is slave
			//Update Group Infos
			$this->SetValue("GroupName", $MUCSpeakerObj->getGroupName());
			IPS_SetProperty($this->InstanceID, "GroupID", $MUCSpeakerObj->getGroup());
			IPS_ApplyChanges($this->InstanceID);
		}
	}

	
protected function updateCoordinatorStatus()
{
	try {
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		$MUCDeviceObj = $this->getMusicCastDeviceObj();
		$MUCSpeakerObj = $this->getMusicCastSpeakerObj($MUCDeviceObj);
		$SpeakerIsCoordinator = $MUCSpeakerObj->isCoordinator();
		IPS_SetProperty($this->InstanceID, "Coordinator", $SpeakerIsCoordinator);
		IPS_ApplyChanges($this->InstanceID);
		if ($this->ReadPropertyBoolean('Coordinator') == true)
		{
			IPS_LogMessage("MUC " . $this->ReadPropertyString('Name'), "Is Coordinator: true");
			$this->setHiddenDeviceVariableforSlave(false);
		} else{
			IPS_LogMessage("MUC " . $this->ReadPropertyString('Name'), "Is Coordinator: false");
			$this->setHiddenDeviceVariableforSlave(true);
		}
	}
	catch (Exception $e) {
		echo 'Error: ',  $e->getMessage(), "\n";
		exit(1);
	}
}

//function extract protected properties
protected function getObjProp($obj, $val){
	$propGetter = Closure::bind( function($prop){return $this->$prop;}, $obj, $obj );
	return $propGetter($val);
}

public function RequestAction($Ident, $Value) {
	try {
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		$MUCDeviceObj = $this->getMusicCastDeviceObj();
		$MUCSpeakerObj = $this->getMusicCastSpeakerObj($MUCDeviceObj);

	if ($this->ReadPropertyBoolean('Coordinator') == true)
	{
		$MUCControllerObj = $this->getMusicCastControllerObj($MUCSpeakerObj,$MUCNetworkObj,1);
		switch($Ident) {
			case "Mute":
				if($Value == true)
				{
					$result = $MUCControllerObj->mute();
				}else{
					$result = $MUCControllerObj->unmute();
				}
				break;
			case "Power":
				if($Value == false)
				{
					$result = $MUCControllerObj->standBy();
					//$this->setHiddenDeviceVariable($this->GetIDForIdent($Ident),true);
				}else{
					$result = $MUCControllerObj->powerOn();
					$this->subscribeDevice();
					//$this->signalInfoUpdated();
					//$this->setHiddenDeviceVariable($this->GetIDForIdent($Ident),false);
				}
				break;
			case "Volume":
				$result = $MUCControllerObj->setVolume($Value);
				break;
			case "State":
				$result = $MUCControllerObj->setState($Value);
				break;
			case "Input":
				$InputName = $this->getVariableValueName($this->GetIDForIdent($Ident),$Value);
				$result = $MUCControllerObj->setInput($InputName);
				break;
			case "Previous":
				{$result = $MUCControllerObj->previous();}
				break;
			case "Next":
				{$result = $MUCControllerObj->next();}
				break;
			case "Repeat":
				{$result = $MUCControllerObj->toggleRepeat();}
				break;
			case "Shuffle":
				{$result = $MUCControllerObj->toggleShuffle();}
				break;
			case "Favorite":
				$FavoriteName = $this->getVariableValueName($this->GetIDForIdent($Ident),$Value);
				$MUCFavoritArray = array(
					"text" => $FavoriteName,
					"input" => "net_radio",
				);
				$MUCFavoriterObj = $this->getMusicCastFavoriteObj($Value+1,$MUCFavoritArray,$MUCSpeakerObj);
				$MUCFavoriterObj->play();
				break;
			default:
				throw new Exception("Invalid Ident");
		}
	} else
		{ //slave
			switch($Ident) {
			case "Mute":
				if($Value == true)
				{
					$result = $MUCSpeakerObj->mute();
				}else{
					$result = $MUCSpeakerObj->unmute();
				}
				break;
			case "Power":
				if($Value == false)
				{
					$result = $MUCSpeakerObj->standBy();
					//$this->setHiddenDeviceVariable($this->GetIDForIdent($Ident),true);
				}else{
					$result = $MUCSpeakerObj->powerOn();
					//$this->setHiddenDeviceVariable($this->GetIDForIdent($Ident),false);
					$this->subscribeDevice();
					//$this->signalInfoUpdated();
				}
				break;
			case "Volume":
				$result = $MUCSpeakerObj->setVolume($Value);
				break;
			default:
				throw new Exception("Invalid Ident");
		}
	}
	}
	catch (Exception $e) {
		echo 'Error: ',  $e->getMessage(), "\n";
		exit(1);
	}
}
//Versteckt alle Objekte wenn Power vom Device off ist und Host is Coordinator
protected function setHiddenDeviceVariable($PowerChildrenID,$hide)
{
	if ($this->ReadPropertyBoolean('Coordinator') == true)
	{
		$ParentID = IPS_GetParent($PowerChildrenID);
		$ChildrenIDs = IPS_GetChildrenIDs($ParentID);
		foreach ($ChildrenIDs as $ChildrenID)
		{
			if(IPS_GetName($ChildrenID) != "Power")
			{
			IPS_SetHidden($ChildrenID,$hide);
			}
		}
	}
}

//Versteckt alle Objekte die nicht vom Slave benutzt werden können
protected function setHiddenDeviceVariableforSlave($hide)
{
	//IPS_SetProperty($this->InstanceID, "Host", $CurrentIP);
	//$ParentID = IPS_GetParent($PowerChildrenID);
	$ChildrenIDs = IPS_GetChildrenIDs($this->InstanceID);
	foreach ($ChildrenIDs as $ChildrenID)
	{
		if(IPS_GetName($ChildrenID) != "Power")
		{
			IPS_SetHidden($ChildrenID,$hide);
		}
	}
}
//Wird ausgefürt nachdem die Instanze angelegt wurde (durch Timer)
public function DeviceSetup()
{
	$this->SetTimerInterval('setupOneTimeTimer', 0);
	$this->createInputVariablenprofile();
	IPS_SetVariableCustomProfile($this->GetIDForIdent("Input"), "MUC_Input_" . $this->ReadPropertyString('DeviceID'));
	$this->createFavoriteVariablenprofile();
	IPS_SetVariableCustomProfile($this->GetIDForIdent("Favorite"), "MUC_Favorite_" . $this->ReadPropertyString('DeviceID'));
	$this->updateSpeakerIP();
	$this->signalInfoUpdated();
	$this->updateCoordinatorStatus();
}
protected function getVariableValueName($VariableID,$VariableValue)
{
	$VariableObject = IPS_GetVariable($VariableID);
	$VariableCustomProfileName = $VariableObject['VariableCustomProfile'];
	$VariableProfileObject = IPS_GetVariableProfile($VariableCustomProfileName);
	$VariableProfileValueName = $VariableProfileObject['Associations'][$VariableValue]['Name'];
	return $VariableProfileValueName;
}

protected function getVariableIntegerbyName($VariableID,$VariableValueName)
{
	$i=0;
	$VariableObject = IPS_GetVariable($VariableID);
	$VariableCustomProfileName = $VariableObject['VariableCustomProfile'];
	$VariableProfileObject = IPS_GetVariableProfile($VariableCustomProfileName);
	foreach($VariableProfileObject['Associations'] as $VariableProfileObjectSub)
	{
		if ($VariableProfileObjectSub['Name'] == $VariableValueName)
		{
			$VariableProfileValueInt = $i;
		}
		$i++;
	}
	
	return $VariableProfileValueInt;
}

protected function getSpeakerIPbyName($SpeakerName)
{
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		try {
				$speaker = $MUCNetworkObj->getSpeakerByName($SpeakerName);
				if (isset($speaker)) {
					$DeviceObj = $this->getObjProp($speaker,"device");
					$DeviceIP = $this->getObjProp($DeviceObj,"ip");
					return $DeviceIP;
					}
				else {
					throw new Exception('No Device found.');
					}
			}
		catch (Exception $e) {
				$this->SetStatus(104);
				echo 'Error: ',  $e->getMessage(), "\n";
				exit(1);
		}
}

protected function getSpeakerByIp($IP)
{
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		try {
				$speaker = $MUCNetworkObj->getSpeakerByIp($IP);
				if (isset($speaker)) {
					$DeviceObj = $this->getObjProp($speaker,"device");
					$DeviceIP = $this->getObjProp($DeviceObj,"ip");
					return $DeviceIP;
					}
				else {
					throw new Exception('No Device found.');
					}
			}
		catch (Exception $e) {
				$this->SetStatus(104);
				echo 'Error: ',  $e->getMessage(), "\n";
				exit(1);
		}
}

//Input Variable Profile create
protected function createInputVariablenprofile()
	{
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		$MUCDeviceObj = $this->getMusicCastDeviceObj();
		$MUCSpeakerObj = $this->getMusicCastSpeakerObj($MUCDeviceObj);
		//$MUCControllerObj = $this->getMusicCastControllerObj($MUCSpeakerObj,$MUCNetworkObj,1);

		$DeviceID = $this->ReadPropertyString('DeviceID');
		$Inputs = $MUCSpeakerObj->getInputList();
		
		$VariablenProfileName = "MUC_Input_" . $DeviceID;
		if (!IPS_VariableProfileExists($VariablenProfileName)) 
		{
			IPS_CreateVariableProfile($VariablenProfileName, 1);
			$i = 0;
			foreach ($Inputs as $Input){
				IPS_SetVariableProfileAssociation($VariablenProfileName, $i, $Input, "Speaker",-1);
				$i++;
			}
		}
	}

//Favorite Variable Profile create
protected function createFavoriteVariablenprofile()
	{
		$MUCNetworkObj = $this->getMusicCastNetworkObj();
		$MUCDeviceObj = $this->getMusicCastDeviceObj();
		$MUCSpeakerObj = $this->getMusicCastSpeakerObj($MUCDeviceObj);

		$DeviceID = $this->ReadPropertyString('DeviceID');
		$Favorites = $MUCSpeakerObj->getFavorites();
		
		$VariablenProfileName = "MUC_Favorite_" . $DeviceID;
		if (!IPS_VariableProfileExists($VariablenProfileName)) 
		{
			IPS_CreateVariableProfile($VariablenProfileName, 1);
			$i = 0;
			foreach ($Favorites as $Favorite){
				$FavoriteName =  $this->getObjProp($Favorite,"name");
				$FavoriteInput = $this->getObjProp($Favorite,"input");
				if ($FavoriteInput == "net_radio") IPS_SetVariableProfileAssociation($VariablenProfileName, $i, $FavoriteName, "Speaker",-1);
				$i++;
			}
		}
	}
	
protected function getInstanceNameExist($InstanceName)
		{
			$Instanceexist = false;
			$Instances = IPS_GetInstanceList();
			foreach($Instances as $Instance)
			{
				$InstanceObject = IPS_GetInstance($Instance);
				$InstanceObjectName = IPS_GetName($InstanceObject['InstanceID']);
				
				if ($InstanceObjectName == $InstanceName) $Instanceexist = true;
			}
			return $Instanceexist;
		}

protected function delete_files($target) {
    if(is_dir($target)){
        $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

        foreach( $files as $file ){
            $this->delete_files( $file );      
        }

        rmdir( $target );
    } elseif(is_file($target)) {
        unlink( $target );  
    }
}
}