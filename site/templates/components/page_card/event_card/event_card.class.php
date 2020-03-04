<?php
namespace ProcessWire;

class EventCard extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		$this->projectService = $this->getService("ProjectService");
	}

	public function getAjax($ajaxArgs = []){
		$output = $this->getAjaxOf($this->page);
		if(isset($output['url'])) unset($output['url']);
		if(isset($output['httpUrl'])) unset($output['httpUrl']);
		if(isset($output['template'])) unset($output['template']);
		if(isset($output['name'])) unset($output['name']);

		$output['timestamp_from'] = $this->page->getUnformatted('timestamp_from');
		$output['timestamp_until'] = $this->page->getUnformatted('timestamp_until');
		$output['intro'] = $this->page->intro;

		if(wire('input')->get('htmlOutput')){
			$output['html'] = $this->renderView();
		}

		if($this->page->main_image){
			$output['main_image'] = $this->getAjaxOf($this->page->main_image->height(300));
		}

		if($this->page->color){
			$output['color'] = $this->page->color;
		}

		// Should the periods of the appointment be displayed?
		if(!wire('input')->get('hide_time_periods')){
			$output['time_periods'] = array();
			foreach($this->page->time_periods->sort('timestamp_from') as $period){
				$zr = array(
					'id' => $period->id,
					'title' => $period->title,
					'description_text' => $period->description_text
				);

				// time periods:
				$zr['timestamp_from'] = $period->getUnformatted('timestamp_from');
				$zr['timestamp_until'] = $period->getUnformatted('timestamp_until');

				// Rollen:
				$zr['project_roles'] = array();
				foreach($period->project_roles as $projectRole){
					$rolleOutput = array(
						'id' => $projectRole->project_roles->id,
						'besetzungen' => $projectRole->casts->explode('id')
					);

					$zr['project_roles'][$projectRole->project_roles->id] = $rolleOutput;
				}

				// categories:
				$zr['categories'] = $period->event_categories->explode('id');

				// cast:
				if($period->cast instanceof Page && $period->cast->id){
					$zr['cast'] = array(
						'id' => $period->cast->id,
						'title' => $period->cast->title,
						'name' => $period->cast->name
					);
				}

				// location:
				if($period->location instanceof Page && $period->location->id){
					$zr['location'] = $period->location->id;
				}

				$output['time_periods'][] = $zr;
			}
		}

		return $output;
	}
}
