<?php 

namespace Core\Base\Controllers;

use Core\Page;
use Core\Service;

class MerakiController extends BaseController
{	 
	public function onDispatch() 
	{
		$this->set_layout('layout_default');
	}

	protected function get_hotspot_by_id ($id) {
		$hotspot = $this->db->query_one("SELECT * FROM _hotspots WHERE id=:id", array('id'=>$id));

		if (!$hotspot) {
			Service::error("La borne possédant l'ID ".$id." n'a pas été trouvée en BDD");
		}

		return $hotspot;
	}

	public function page_remote_data_storage() {

		$id = $this->request->fromRoute('id', null);
		$is_test = $this->request->fromRoute('is_test', 0);

		if (!$id) {
			Service::error("Aucune ID borne définie");
		}

		$hotspot = $this->get_hotspot_by_id($id);

		if ($this->request->isPost()) {

			if (isset($this->request->post->data)) {
				$data = json_decode($this->request->post->data);
			} else {
				Service::error("Aucune donnée reçues");
			}
			
			if (!isset($data->secret)) {
				Service::error("Mot de passe secret inexistant");
			
			} else if ($data->secret != $hotspot->secret) {
				Service::error("Mot de passe secret incorrect");
			} 

			switch ($data->version) {
				case '0.0':
				case '1.0':

					foreach ($data->probing as $probe) {

						$current_data = array();

						$current_data['ap_mac'] = $probe->ap_mac;
						$current_data['rssi'] = $probe->rssi;

						// TODO
						// il semble que le champs is_associated ne soit pas défini en V2, revoir la table et le model 
						$current_data['is_associated'] = $probe->is_associated == 'true' ? 1 : 0;
						$current_data['client_mac'] = $probe->client_mac;

						// TODO
						// A vérifier, si on doit mettre time() ou seen_time, 
						// car ça pourrait ne pas coller si la borne renvoi des mac qui sont plus là depuis un moment

						$time = explode(' ', $probe->last_seen);
						$current_data['seen_time'] = strtotime($time[1].' '.$time[2].' '.$time[5].' '.$time[3]);
						// Base format : "Tue Oct 13 16:03:19.737 UTC 2015"
					
						if ($is_test) {
							echo "Les données suivantes ont été extraites";
							var_dump($current_data);
						} else {
							$this->db->insert('_meraki_data_storage')->values($current_data)->execute();
						}
					}
					
					break;
				
				case '2.0':
					break;
			}


		} else {

			if (time() <= $hotspot->synchronisation_time) {
				exit($hotspot->validator);
			} else {
				exit();
			}
		}

	}
}
?>