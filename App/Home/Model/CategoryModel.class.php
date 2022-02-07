<?php

namespace Home\Model;
use Think\Model;


class CategoryModel extends Model{
	
	//栏目树
	static public function cate_tree($arr,$id,$level=0){
		$sub = array();
		foreach($arr as $key => $value){
			if ($value['pid'] == $id){
				$value['level'] = $level + 1;
				$sub[] = $value;
				$sub = array_merge($sub,self::cate_tree($arr,$value['id'],$level+1));
			}
		}
		return $sub;
	}
	
	public function catelist(&$catelist){
		foreach($catelist as $k => $v){
			if($v['level'] == 1) continue;
			$catelist[$k]['name'] = str_repeat('┗━',$v['level']).$v['name'];
		}
	}
}


?>