<?
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__ . '/libs/autoload.php');

class IPSMusicCast extends IPSModule
{
	public function Create()
    {
        parent::Create();
		$this->RegisterPropertyInteger('NetworkInterface', 0); //Interface ID for Multicast
		//Globale Variablen Profile erstellen
		if (!IPS_VariableProfileExists("MUC_Mute"))
		{
		IPS_CreateVariableProfile("MUC_Mute", 0);
		IPS_SetVariableProfileValues("MUC_Mute", 0, 1, 1);
		IPS_SetVariableProfileAssociation("MUC_Mute", 0, "Unmute", "Speaker", -1);
		IPS_SetVariableProfileAssociation("MUC_Mute", 1, "Mute", "Speaker", -1);
		}
		if (!IPS_VariableProfileExists("MUC_Volume"))
		{
		IPS_CreateVariableProfile("MUC_Volume", 1);
		IPS_SetVariableProfileValues("MUC_Volume", 0, 60, 1);
		IPS_SetVariableProfileIcon("MUC_Volume",  "Intensity");
		IPS_SetVariableProfileText("MUC_Volume", "", "%");
		}
		if (!IPS_VariableProfileExists("MUC_State"))
		{
		IPS_CreateVariableProfile("MUC_State", 1);
		IPS_SetVariableProfileAssociation("MUC_State", 201, "stop", "Speaker", -1);
		IPS_SetVariableProfileAssociation("MUC_State", 202, "play", "Speaker", -1);
		IPS_SetVariableProfileAssociation("MUC_State", 203, "pause", "Speaker", -1);
		}
		if (!IPS_VariableProfileExists("MUC_Previous"))
		{
		IPS_CreateVariableProfile("MUC_Previous", 1);
		IPS_SetVariableProfileValues("MUC_Previous", 0, 1, 0);
		IPS_SetVariableProfileAssociation("MUC_Previous", 1, "previous", "", -1);
		}
		if (!IPS_VariableProfileExists("MUC_Next"))
		{
		IPS_CreateVariableProfile("MUC_Next", 1);
		IPS_SetVariableProfileValues("MUC_Next", 0, 1, 0);
		IPS_SetVariableProfileAssociation("MUC_Next", 1, "next", "", -1);
		}
    }

	public function Destroy()
		{
		parent::Destroy();
			//Globale Variablen Profile löschen
			if (IPS_VariableProfileExists("MUC_Mute"))
			{IPS_DeleteVariableProfile("MUC_Mute");}
			if (IPS_VariableProfileExists("MUC_Volume"))
			{IPS_DeleteVariableProfile("MUC_Volume");}
			if (IPS_VariableProfileExists("MUC_State"))
			{IPS_DeleteVariableProfile("MUC_State");}
			if (IPS_VariableProfileExists("MUC_Previous"))
			{IPS_DeleteVariableProfile("MUC_Previous");}
			if (IPS_VariableProfileExists("MUC_Next"))
			{IPS_DeleteVariableProfile("MUC_Next");}

		}
//Modul gespeichert
	public function ApplyChanges()
		{
			parent::ApplyChanges();

			// UDP Socket erstellen oder verbinden
			$this->ConnectParent("{82347F20-F541-41E1-AC5B-A636FD3AE2D8}");
			$UDPSocket = $this->GetParentId(); //UDP Socket ID finden
			//Properties für das UDP Socket einstellen
			IPS_SetProperty($UDPSocket, "BindPort", 41100);
			IPS_SetProperty($UDPSocket, "Host", "192.168.133.10");
			IPS_SetProperty($UDPSocket, "Port", 41100);
			IPS_SetProperty($UDPSocket, "Open", true);
			IPS_ApplyChanges($UDPSocket);
			//$this->SetStatus(104);
		}
    protected function getMusicCastNetworkObj()
    {
			return new MusicCast\Network;
	}

	public function GetMCSpeakers()
		{
			//Delete Cache Folder
			$tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "musiccast";
			@$this->delete_files($tempPath);
			//Get all Speakers in current Network
			$MUCNetworkObj = $this->getMusicCastNetworkObj();
			$MUCNetworkObj->setNetworkInterface($this->ReadPropertyInteger('NetworkInterface'));
	
			try {
	//			$MUCNetworkObj->getSpeakers();
				$MUCNetworkObj->getSpeakerByIp('192.168.128.139');
			}
			catch (Exception $e) {
				echo 'Error: ',  $e->getMessage(), "\n";
				$this->SetStatus(104);
				exit(1);
			}
			$this->SetStatus(102);

			$MUCNetworkArray = $this->getObjProp($MUCNetworkObj,"speakers");
			//$SpeakerIPs[];
			$SpeakerIPs = array_keys($MUCNetworkArray);
			
			//Liest bestehende MusicCast Geräte aus ips aus
			$MusicCastInstances = (IPS_GetInstanceListByModuleID('{332C2875-7503-4054-95BE-250081AF03DF}'));
			$ExistingDevicesIDs = [];
			foreach ($MusicCastInstances as $MusicCastInstance)
			{
				array_push($ExistingDevicesIDs, IPS_GetProperty($MusicCastInstance, "DeviceID"));
			}
			//Speaker Instancen erstellen
				foreach ($SpeakerIPs as $SpeakerIP)
				{
					$musicCastDevice = new MusicCast\Device($SpeakerIP);
					$musicCastSpeaker = new MusicCast\Speaker($musicCastDevice);
					$SpeakerName = $musicCastSpeaker->getName();
					$SpeakerID = $musicCastSpeaker->getUuid();
					$SpeakerIsCoordinator = $musicCastSpeaker->isCoordinator();
					//Prüfe ob Instance schon vorhanden
						if (!in_array($SpeakerID, $ExistingDevicesIDs))
						{
							IPS_LogMessage("MusicCast", "Add new Speaker/Controller to system: " . $SpeakerName . " IP: " .$SpeakerIP);
							
							$NewMUCDevice = IPS_CreateInstance('{332C2875-7503-4054-95BE-250081AF03DF}');
							IPS_SetName($NewMUCDevice,$SpeakerName);
							IPS_SetProperty($NewMUCDevice, "DeviceID", $SpeakerID);
							IPS_SetProperty($NewMUCDevice, "Host", $SpeakerIP);
							IPS_SetProperty($NewMUCDevice, "Name", $SpeakerName);
							IPS_SetProperty($NewMUCDevice, "Coordinator", $SpeakerIsCoordinator);
							IPS_ApplyChanges($NewMUCDevice);

							
						} else {
							IPS_LogMessage("MusicCast", "Speaker/Controller " . $SpeakerName . " already exist. -> skip");
						}
					}
				echo "Done, see Message Log for more Infos";
		}
	
    /**
     * get connected parent instance id
     * @return mixed
     */
    protected function GetParentId()
    {
        $instance = @IPS_GetInstance($this->InstanceID);
        return $instance['ConnectionID'];
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
	//function extract protected properties
	protected function getObjProp($obj, $val){
		$propGetter = Closure::bind( function($prop){return $this->$prop;}, $obj, $obj );
		return $propGetter($val);
	}
}
?>
