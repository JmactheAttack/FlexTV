<?php

namespace digitalhigh\widget\template;

use digitalhigh\widget\exception\widgetException;
use digitalhigh\widget\curl\curlGet;

class widgetSystemMonitor {
	// Unique ID for each widget
	// Data store for other values
	private $data;
	// Required values in order for other things to work
	const required = ['uri','token'];
	// Set these accordingly
	const maxWidth = 3;
	const maxHeight = 3;
	const minWidth = 1;
	const minHeight = 1;
	const refreshInterval = 15;
	const type = "systemMonitor";
	/**
	 * widgetSystemMonitor constructor.
	 * @param $data
	 * @throws widgetException
	 */
	function __construct($data) {
		$data['type'] = self::type;

		foreach(self::required as $key)	{
			if (!isset($data[$key])) throw new widgetException("Missing required key $key");
		}
		$this->data = $data;
		$this->data['service-status'] = $data['service-status'] ?? "offline";
		$this->data['gs-max-width'] = self::maxWidth;
		$this->data['gs-min-width'] = self::minWidth;
		$this->data['gs-max-height'] = self::maxHeight;
		$this->data['gs-min-height'] = self::minHeight;
		$this->data['limit'] = $data['limit'] ?? 20;
	}


	public function update($force=false) {
		$lastUpdate = $this->data['lastUpdate'];
		$int = self::refreshInterval;
		$total = $lastUpdate + $int;
		$now = time();
		if ($now > $total || $force) {
			$this->data['lastUpdate'] = time();
			// Do stuff here to update
			$stats = $this->fetchSystemStats();
			if ($stats) {
				unset($stats['size']);
				$stats['stamp'] = $now;
				$data = $this->data['stats'] ?? [];
				array_push($data,$stats);
				$data = array_slice($data, 0, $this->data['limit']);
				$this->data['stats'] = $data;
			}
		}
		return $this->serialize();
	}

	private function fetchSystemStats() {
		$server = findDevice("Id", $this->data['target'], "Server");
		$uri = $server['Uri'];
		$token = $server['Token'];
		$url = "$uri/stats/system?X-Plex-Accept=json&X-Plex-Token=$token";
		$data = new curlGet($url);
		return $data['MediaContainer'] ?? false;
	}


	public function serialize() {
		return $this->data;
	}

	public static function widgetHTML() {
		// As odd as it may seem, this is where we set our "default" values for the widget.
		// Auto-position will be turned off when the widget is created.
		$attributes = [
			'gs-x' => 7,
			'gs-y' => 0,
			'gs-width' =>3,
			'gs-height' => 3,
			'type' => self::type,
			'gs-min-width' => self::minWidth,
			'gs-min-height' => self::minHeight,
			'gs-max-width' => self::maxWidth,
			'gs-max-height' => self::maxHeight,
			'gs-auto-position' => true
		];
		$attributeStrings = [];
		foreach($attributes as $key => $value) $attributeStrings[] ="data-${key}='${value}'";
		$attributeString = join(" ", $attributeStrings);
		return '
		<div class="widgetCard card m-0 grid-stack-item '.self::type.'" '.$attributeString.'>
			<div class="grid-stack-item-content">
				<!-- Optional header to show buttons, drag handle, and a title -->
				<h4 class="card-header d-flex justify-content-between align-items-center text-white px-3">
					<span class="d-flex align-items-center">
						<i class="material-icons dragHandle editItem">drag_indicator</i></span>Server Status
					<span>
						<button type="button" class="btn btn-settings editItem widgetMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="material-icons">more_vert</i>
						</button>
						<div class="dropdown-menu dropdown-menu-right">
							<button class="dropdown-item widgetEdit" type="button">Edit</button>
							<button class="dropdown-item widgetRefresh" type="button">Refresh</button>
							<div class="dropdown-divider"></div>
							<button class="dropdown-item widgetDelete" type="button">Delete</button>
						</div>
					</span>
				</h4>
				
				<!-- Card body goes here -->
				<div class="card-content">
					<div class="canvas">
						<div class="progress">
	                        <div class="progress-bar cpu-progress" role="progressbar" style="width: 25%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">25%</div>
						</div>
						<div class="progress">
	                        <div class="progress-bar memory-progress" role="progressbar" style="width: 25%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">25%</div>
						</div>
					</div>
				</div>
				
				<div class="card-settings">
                    <!-- Card setting markup goes here -->
                    <div class="form-group">
                        <label class="appLabel" for="serverList">Target</label>
                        <select class="form-control custom-select serverList statInput" data-for="target" title="Target">
                        </select>
                    </div>
	            </div>
			</div>
		</div>
		';
	}

	/**
	 * CSS Defined here will be prepended with the className of the widget, whis is
	 * determined by the class name. So, it's safe to re-use selectors within the cards, and not define
	 * additional classes. I'm lazy, so be sure classes have a newline before and between them...
	 * @return string
	 */
	public static function widgetCSS() {
		return '
		
			.someSelector {
				background: black;
				text-align: center;
			}
			
			.anotherSelector {
				width: 50%;
			}
		';
	}


	public static function widgetJS() {
		$init = '
		if window.hasOwnProperty("devices") {
		
		}
		';
		return [];
	}

}