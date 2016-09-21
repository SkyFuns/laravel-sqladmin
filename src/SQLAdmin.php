<?php

namespace Upstriving\Curd;

class SQLAdmin
{
	/**
	 * 生成select语句
	 *
	 * @param string $tablename 表名
	 * @param string $queryString 生成查询字段sql片段，空则为*
	 * @param array $whereArr 查询条件数组，如果值为array，则使用in，仅限数字的情况（不支持非数字类型字段的in操作）
	 * 如果数组中包含type值，则按照type值的原则处理。1，like 2, > 3, < 4, between and；多项用|隔开 5,单个!=数组为not in
	 * 下划线开头的键值代表功能键值 _order排序，_limit限制
	 *
	 *
	 * @param string $ext where之后的sql片段
	 * @return string sql语句
	 */
	public function makeSelect($tablename,$queryString='',$whereArr=array(),$ext=''){
		$queryString = empty($queryString)?'*':$queryString;
		$vals = array();
		$keys = array();
		if($whereArr){
			//处理特殊字符
			$fields = array();//需要特殊处理的字段
			foreach($whereArr as $k=>$v){
				if($v===NULL)continue;
				if($k == '_oorderr_'){
					$_v = explode('|',$v);
					if(count($_v) == 2 && in_array(strtolower($_v[1]),array('asc','desc'))){
						$_order = ' order by '.$_v[0].' '.$_v[1];
					}
				}
				elseif($k == '_llimitt_'){
					$_v = explode('|',$v);
					$_tmp = array();
					if(isset($_v[0]))$_tmp[0] = intval($_v[0]);
					if(isset($_v[1])){
						$_tmp[1] = intval($_v[1]);
						$_tmp[0] = ($_tmp[0]-1)*$_tmp[1];
						$_tmp[0] = ($_tmp[0]<0)?0:$_tmp[0];
					}
					$_limit = ' limit '.implode(',',$_tmp);
				}
				else{
					if(is_array($v)){
						if(isset($v['type'])){
							if(isset($v['value'])){
								if( $v['type'] == 1 ){
									$keys[] = $k." LIKE ?";
									$vals[]	= $v['value'];
								}elseif($v['type'] == 2){
									$keys[] = $k." >= ?";
									$vals[]	= $v['value'];
								}
								elseif($v['type'] == 3){
									$keys[] = $k." <= ?";
									$vals[]	= $v['value'];
								}elseif($v['type'] == 4){
									$v['value'] = explode('|',$v['value']);
									if(count($v['value']) == 2){
										$keys[] = $k." between ? and ?";
										$vals[]	= $v['value'][0];
										$vals[]	= $v['value'][1];
									}
								}elseif($v['type'] == 5){
									if(!is_array($v['value'])){
										$keys[] = $k." != ?";
										$vals[]	= $v['value'];
									}else{
										$keys[] = $k.' NOT IN ('.(rtrim(str_repeat("?,",count($v['value'])),',')).')';
										$vals = array_merge($vals,$v['value']);
									}
								}
							}
						}
						else{
							$keys[] = $k.' IN ('.(rtrim(str_repeat("?,",count($v)?count($v):1),',')).')';
							empty($v) && $v = array('');
							$vals = array_merge($vals,$v);
						}
					}
					else
					{
						$keys[] = $k.'=?';
						$vals[] = $v;
					}
				}
			}
		}
		$ext .= !empty($_order)?$_order:'';
		$ext .= !empty($_limit)?$_limit:'';
		$sql = "select {$queryString} from {$tablename}".(!empty($keys)?(' where '.implode(' and ',$keys)):'')
		.($ext?(' '.$ext):'');
		return array('sql'=>$sql, 'data'=>$vals);
	}

	public function makeInsert($tablename, $arr){
		$keys = array_keys($arr);
		$vals = array_values($arr);
		$paras = array_fill(0, count($keys),"?");
		$sql = "insert into {$tablename} (`" . join("`,`", $keys) . "`) values(" . join(",", $paras) . ")";
		return array('sql'=>$sql, 'data'=>$vals);
	}

	public function makeReplace($tablename, $arr){
		$keys = array_keys($arr);
		$vals = array_values($arr);
		$paras = array_fill(0, count($keys),"?");
		$sql = "replace into {$tablename} (`" . join("`,`", $keys) . "`) values(" . join(",", $paras) . ")";
		return array('sql'=>$sql, 'data'=>$vals);
	}

	/**
	 * 更新基础方法
	 *
	 * @param string $tablename 表名
	 * @param array $arr 更新数组
	 * @param array $where 条件数组
	 * @return array sql语句数组
	 */
	public function makeUpdate($tablename, $arr , $where){
		$vals = array();
		foreach($arr as $k=>$v){
			$keys[] = '`' . $k . '`' .'=?';
			$vals[] = $v;
		}
		foreach($where as $k=>$v){
			if(is_array($v)){
				$w_keys[] = $k.' IN ('.(rtrim(str_repeat("?,",count($v)),',')).')';
				$vals = array_merge($vals,$v);
			}
			else{
				$w_keys[] = '`'.$k.'`=?';
				$vals[] = $v;
			}
		}
		$keys = join(",", $keys);
		$w_keys = join(" and ", $w_keys);
		$sql = "update {$tablename} set {$keys} where {$w_keys}";
		return array('sql'=>$sql, 'data'=>$vals);
	}

	/**
	 * 封装删除方法
	 *
	 * @param string $tablename 表名字
	 * @param array $where	删除条件
	 * @return mixed 参数错误返回false，正确返回数组
	 */
	function makeDelete($tablename, $where){
		if(!is_array($where) || empty($where))return false;
		$vals = array();
		foreach($where as $k=>$v){
			if(is_array($v)){
				$w_keys[] = $k.' IN ('.(rtrim(str_repeat("?,",count($v)),',')).')';
				$vals = array_merge($vals,$v);
			}
			else{
				$w_keys[] = '`'.$k.'`=?';
				$vals[] = $v;
			}
		}
		$w_keys = join(" and ", $w_keys);
		$sql = "delete from {$tablename} where {$w_keys}";
		return array('sql'=>$sql, 'data'=>$vals);
	}
}