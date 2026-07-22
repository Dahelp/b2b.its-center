<?php

namespace app\models;

use ishop\App;

class Category extends AppModel {

    public function getIds($id){ //старый вариант
        $cats = App::$app->getProperty('cats');
        $ids = null;
        foreach($cats as $k => $v){
            if($v['parent_id'] == $id){
                $ids .= $k . ',';
                $ids .= $this->getIds($k);
            }
        }
        return $ids;
    }

	public function getIdsArray($id) { // новый вариант
		$cats = App::$app->getProperty('cats');
		$ids = [];
		foreach($cats as $k => $v){
			if($v['parent_id'] == $id){
				$ids[] = $k;
				$ids = array_merge($ids, $this->getIdsArray($k));
			}
		}
		return $ids;
	}
}