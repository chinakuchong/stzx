<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi;

/**
 * 后台用户控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class ClassController extends AdminController {
	//老师列表
    public function teacher_list(){
		$where="1";
		$teacher_list=D("teacher")->where($where)->select();
		$this->display("");
	}
	//编辑和新增页面
	public function teacher_info(){
		$id=I("request.id");
		$teacher_list=D("teacher")->where("id=$id")->find();
		$this->display("");
	}
	public function teacher_edit(){

		$id=I("request.id");
		$data['teacher_name']=I("request.teacher_name",'');
		$data['status']=I("request.status",0);

		if($id){
			$teacher_result=D("teacher")->where("id=$id")->save($data);
		}else{
			$teacher_result=D("teacher")->add($data);
		}

		if($teacher_result){
			$this->success('成功', U('Admin/class/teacher_list'));
		}else{
			$this->error("失败",U('Admin/class/teacher_list'));
		}

	}


	//班级列表
	public function class_list(){
		$where="1 ";
		$class_list=D("class")->join(" c left join __CLASS_CATEGORY__ cc on c.c_id=cc.id")->where($where)->field("c.id,c.class_name,if(c.status=1,'启用','弃用') as status_name,cc.category_name")->select();
		$this->assign("class_list",$class_list);

		$this->display("");
	}

//编辑和新增页面
	public function class_info(){
		$id=I("request.id",0);
        $class_category_list=M('class_category')->where("status=1 and pid>0")->field("id,category_name")->select();
        $this->assign("class_category_list",$class_category_list);
		if($id){
			$class_info=D("class")->where("id=$id")->find();
			$this->assign("info",$class_info);
		}

		$this->display("");
	}
	public function class_edit(){

		$id=I("request.id");
		$data['class_name']=I("request.class_name",'');
		$data['status']=I("request.status",0);
		$data['c_id']=I("request.c_id",0);
        if(empty($data['c_id'])){
            $this->error('请选择类别');
        }

		if($id){
			$teacher_result=D("class")->where("id=$id")->save($data);
		}else{
			$teacher_result=D("class")->add($data);
		}

		if($teacher_result){
			$this->success('成功', U('Admin/class/class_list'));
		}else{
			$this->error("失败",U('Admin/class/class_list'));
		}

	}
	//批次列表
	public function batch_list(){
		$where="1";
		$class_list=D("batch")->where($where)->field("*,if(status=1,'启用','弃用') as status_name")->select();
		$this->assign("class_list",$class_list);
		$this->display("");
	}

//编辑和新增页面
	public function batch_info(){
		$id=I("request.id",0);
		if($id){
			$class_info=D("batch")->where("id=$id")->find();
			$this->assign("info",$class_info);
		}

		$this->display("");
	}
	public function batch_edit(){

		$id=I("request.id");
		$data['batch_name']=I("request.batch_name",'');
		$data['status']=I("request.status",0);

        if($data['status']==1){
            $teacher_result=D("batch")->where("1=1")->setField('status',0);
        }

        if($id){
            $teacher_result=D("batch")->where("id=$id")->save($data);
        }else{
            $teacher_result=D("batch")->add($data);
        }

		if($teacher_result){
			$this->success('成功', U('Admin/class/batch_list'));
		}else{
			$this->error("失败",U('Admin/class/batch_list'));
		}

	}
	//评分项列表
	public function score_list(){
		$where="1";
		$class_list=D("test_score")->where($where)->field("*,if(status=1,'启用','弃用') as status_name")->select();
		$this->assign("score_list",$class_list);
		$this->display("");
	}

//编辑和新增页面
	public function score_info(){
		$id=I("request.id",0);
		if($id){
			$class_info=D("test_score")->where("id=$id")->find();
			$this->assign("info",$class_info);
		}

		$this->display("");
	}
	public function score_edit(){

		$id=I("request.id");
		$data['score_name']=I("request.score_name",'');
		$data['weight']=I("request.weight",'');
		//$data['status']=I("request.status",0);

		if($id){
			$teacher_result=D("test_score")->where("id=$id")->save($data);
		}else{
			$teacher_result=D("test_score")->add($data);
		}

		if($teacher_result){
			$this->success('成功', U('Admin/class/score_list'));
		}else{
			$this->error("失败",U('Admin/class/score_list'));
		}

	}
	//考试分类列表
	public function class_category_list(){
		$where="c.pid>0";
		$class_category=D("class_category")->join("c left join __CLASS_CATEGORY__ cc on c.pid=cc.id")->where($where)->field("c.*,if(c.status=1,'启用','弃用') as status_name,cc.category_name as p_name")->select();
		$this->assign("class_list",$class_category);
		$this->display("");
	}

//编辑和新增页面
	public function class_category_info(){
		$id=I("request.id",0);
        $class_pid_list=D("class_category")->where("pid=0 and status=1")->select();
        $this->assign("class_pid_list",$class_pid_list);

		if($id){
			$class_info=D("class_category")->where("id=$id")->find();
			$this->assign("info",$class_info);
		}
		$this->display("");
	}
	public function class_category_edit(){

		$id=I("request.id");
		$data['category_name']=I("request.category_name",'');
		$data['status']=I("request.status",0);
		$data['pid']=I("request.pid",0);
//print_r($data);die;
		if($id){
			$teacher_result=D("class_category")->where("id=$id")->save($data);
		}else{
			$teacher_result=D("class_category")->add($data);
		}

		if($teacher_result){
			$this->success('成功', U('Admin/class/class_category_list'));
		}else{
			$this->error("失败",U('Admin/class/class_category_list'));
		}

	}
	//编辑和新增页面
	public function subject_info(){
		$id=I("request.id",0);
		$class_category_list=D("class_category")->where("status=1 and pid>0")->select();
		$this->assign("class_category_list",$class_category_list);

		if($id){
			$class_info=D("subject")->where("id=$id")->find();
            //var_dump($class_info);die;
			$this->assign("info",$class_info);
		}
		$this->display("");
	}
	public function subject_edit(){

		$id=I("request.id");
		$data['c_id']=I("request.c_id",'');
		$data['subject_name']=I("request.subject_name",'');
		$data['content']=I("request.content",'');
		$data['status']=I("request.status",0);
		$data['is_bd']=I("request.is_bd",0);
//print_r($data);die;
		if($id){
			$teacher_result=D("subject")->where("id=$id")->save($data);
		}else{
			$teacher_result=D("subject")->add($data);
		}

		if($teacher_result){
			$this->success('成功', U('Admin/class/subject_list'));
		}else{
			$this->error("失败",U('Admin/class/subject_list'));
		}

	}
	//题目列表
	public function subject_list(){
		$where="1";
        $limit_num=I('request.limit_num',10);
        $this->assign("limit_num",$limit_num);
        $pageLimit = pageLimit('subject',$where,$limit_num);
        $this->assign('page', $pageLimit['page']);

		$subject_list=D("subject")->join(" s left join __CLASS_CATEGORY__ c on s.c_id=c.id")->where($where)
            ->field("s.id,s.subject_name,s.content,c.category_name,if(s.status=1,'启用','弃用') as status_name,if(s.is_bd=1,'必答','选讲') as tmc_name")
            ->limit($pageLimit['limit'])
			->order('s.id desc')
            ->select();
		$this->assign("subject_list",$subject_list);

		$this->display("");
	}
}