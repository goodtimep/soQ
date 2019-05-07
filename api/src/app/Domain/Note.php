<?php
namespace App\Domain;

use App\Model\Note as ModelNote;
use App\Model\Notecategory as ModelCate;
use App\Common\Tools;
use PhalApi\Tool;
use App\Model\KeyWord as ModelKeyWord;

class Note {
	/**用户笔记数量 */
	public function getUserNotesNumber($uid)
	{
		$m=new ModelNote();
		return $m->getNotesCountByUserId($uid);
	}
    /**
     * 根据分类Id查找笔记
		 * @param cateid 分类id
     * @param num 获取前几个
     */
    public function getNotesByCateId($cateid,$uid,$page=0, $num = 0)
    {
      $m=new ModelNote();
		
			$min=Tools::getPageRange($page,$num);
			
		  return $m->getNotesByCateId($cateid,$uid,$min,$num);
		}
		/**根据Id得到某个笔记 */
		public function getNoteById($id)
		{
			$m=new ModelNote();
			$cm=new ModelCate();
			$km=new ModelKeyWord();
			
			$data=$m->getNoteById($id);
			$data["Keys"]=$km->gesKeyWordsByIds($data["KeyWords"]);
			$data["Category"]=$cm->getNameById($data["NoteCategoryId"]);
			return $data;
		}

    /**根据关键字查找用户笔记 */
    public function getNotesByKeywords($uid,$cid=0, $keys)
    {
      $m=new ModelNote();
      return $m->getNotesByKeywords($uid,$cid,$keys);
		}
		/**
		 * 更具关键字Id查找用户笔记
		 */
		public function getNotesByKeyIds($uid,$cid=0,$kid)
		{
			$m=new ModelNote();
			if($kid=="") return $m->getNotesByCateId($cid,$uid);
			$data=$m->getBykeyId($uid,$cid,$kid);
			return $data;
		}
		/**
		 * 增加一条笔记
		 * @param  data  包含了用户笔记信息的数组，其中data['NoteCategory']可能是分来id或者是分类名称
		 * @author ipso
		 * 
		 */
		public function add($data){
			// $cid=Tools::judgeCategoryId($keys);
			$keys = Tools::ExtractKeyWords($data["Content"].$data["Headline"]);
			$data["KeyWords"]=implode(",",Tools::GetValueByKey($keys,"Id"));
			$data["KeysWeight"]=implode(",",Tools::GetValueByKey($keys,"Weight"));
			 
			// 将数据写入数据库
			$model = new ModelNote();
			$sql = $model -> insertOne($data);
			return $sql;
		}

		/**
		 * 更新一条数据
		 * @param  data  包含了用户笔记信息的数组，其中data['NoteCategory']可能是分来id或者是分类名称
		 * @author ipso
		 */
		public function update($data){
			// $flag = is_int($data['NoteCategory']);
			// if($flag == true){
			// 	$data['NoteCategoryId'] = $data['NoteCategory'];
			// 	unset($data['NoteCategory']);
			// }else{
			// 	$cateModel = new ModelCate();
			// 	$cateid = $cateModel -> getCidByName($data['NoteCategory']);
			// 	$data['NoteCategoryId'] = $cateid;
			// 	unset($data['NoteCategory']);
			// }
			// 将数据写入数据库
			$keys = Tools::ExtractKeyWords($data["Content"].$data["Headline"]);
			$data["KeyWords"]=implode(",",Tools::GetValueByKey($keys,"Id"));
			$data["KeysWeight"]=implode(",",Tools::GetValueByKey($keys,"Weight"));
			
			$model = new ModelNote();
			$sql = $model -> updateOne($data);
			return $sql;
		}

		public function delete($nid){
			$model = new ModelNote();
			return $model->deleteOne($nid);
		}

		/**
		 * 获取笔记数量
		 */
		public function getCount(){
			$model = new ModelNote();
			return $model -> getCount(); 
		}

		/**
		 * 获取用户笔记数量
		 */
		public function getCountByUserId($uid){
			$model = new ModelNote();
			return $model -> getCountByUserId($uid); 
		}
		/**
		 * 获取limit限制内的所有记录
		 * @param  begin  开始位置
		 * @param  length 获取记录的数量
		 * @author ipso
		 */
		public function getByLimit($begin, $length=10){
			$model = new ModelNote();
			$sql = $model -> getByLimit($begin,$length);
			return $sql;
		}

		/**
		 * 更新
		 */
		public function updateCategory($data)
		{
			$model=new ModelCate();
			return $sql=$model->updateCategory($data);
		}
		/**
		 * 删除分类，以及分类笔记
		 */
		public function deleteCategory($data)
		{
			$model=new ModelCate();
			$model->deleteCategory($data);
			$mn=new ModelNote();
			return $mn->deleteCate($data["UserId"],$data["Id"]);
		}
		/**
		 * 获取用户笔记的所有关键字
		 * @param id 用户id
		 * @param cid 用户笔记分类id
		 */
		public function getKeysByUserNotes($id,$cid=0){
			$arr=$this->getNotesByCateId($cid,$id,0,0); // 获取用户所有笔记
			$keys=Tools::GetValueByKey($arr,"KeyWords");
			$temp="";

			for($i=0;$i<count($keys);$i++)
			{
				if($keys[$i]=="0,") continue; //去除 0干扰
				$temp=$temp.",".$keys[$i];
			}
			// $keys=array(); //存储去重后的关键字Id 似乎没用
			// for($i=1;$i<strlen($temp);$i++) // 获取keys数组
			// {
			// 	$t=$i;
			// 	while($i<strlen($temp)&&$temp[$i]!=","){
			// 		$i++;
			// 	}
			// 	$subtemp=substr($temp,$t,$i-$t);
			// 	for($j=0;$j<count($keys);$j++) if($keys[$j]==$subtemp) break;
			// 	if($j==count($keys)) array_push($keys,$subtemp);
			// }
			
			$km=new ModelKeyWord();
			$data=$km->gesKeyWordsByIds($temp);// 获取所有的keys
		
			for($i=1;$i<strlen($temp);$i++) //统计数量
			{
				$t=$i;
				while($i<strlen($temp)&&$temp[$i]!=","){
					$i++;
				}
				$subtemp=substr($temp,$t,$i-$t);
				for($j=0;$j<count($data);$j++)
				{
					if($subtemp==$data[$j]["Id"])
					{
						if(array_key_exists("Count",$data[$j])) $data[$j]["Count"]++;
						else $data[$j]["Count"]=1;
					}
				}
			}
			Tools::unsetKeys($data,"Weight");
			return $data;
		}
}