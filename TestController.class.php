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
class TestController extends AdminController {
	//test
    public function lqstest(){
        $html=I('request.html');
        $this->display("$html");
    }
	//学生列表
	public function student_list(){
		$student_name=I('request.student_name','');
		$student_num=I('request.student_num','');
		$class_id=I('request.class_id','');

		$limit_num=I('request.limit_num',10);
        $this->assign("limit_num",$limit_num);
		$page=I('request.p',1);
		$url=U("test/student_list",array('p'=>$page));
		$this->assign("url",$url);
        $batch_id=M('batch')->where("status=1")->getField('id');
        if($batch_id){
            $batch_name_new=M('batch')->where("id=$batch_id")->getField('batch_name');
        }else{
            $batch_name_new='还没有设置批次';
        }


        $this->assign("batch_name_new",$batch_name_new);

        $where="batch_id=$batch_id";

		$class_list=D("class")->field("*,if(status=1,'启用','弃用') as status_name")->select();
		$this->assign("class_list",$class_list);

		if($student_name){
			$where.=" and student_name like '%".$student_name."%'";
		}
		if($student_num){
			$where.=" and student_num like '%".$student_num."%'";
		}
		if(!empty($class_id)){
			$where.=" and c_id=$class_id";
		}
		$pageLimit = pageLimit('student',$where,$limit_num);

		$student_list=D("student")->where($where)->order("id desc")
            ->limit($pageLimit['limit'])
            ->field("*,if(sex=1,'男','女') as sex,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i:%s') as create_time")
            ->select();
		$this->assign("student_list",$student_list);
		$this->assign('page', $pageLimit['page']);
		//echo "<pre>";	print_r($student_list);die;
		$this->display();
	}

	//编辑和新增页面
	public function student_info(){
		$id=I("request.id",0);
		$student_info=D("student")->where("id=$id")->find();

		$result['status']=1;
		$result['info']=$student_info?$student_info:'';

		$this->ajaxReturn($result);
	}
	public function student_edit(){

		$id=I("request.id");
		$c_id=I("request.class_id");
		$division=I("request.division");
		$data['status']=I("request.status",0);

		if($id){
			$data['student_name'] = I("request.student_name");
			$data['student_num'] =I("request.student_num");
			$data['mobile'] = I("request.mobile");
			$data['sex'] =I("request.sex");
			$data['id_card'] =I("request.id_card");
			$data['zkz_num'] =I("request.zkz_num");
			$data['c_id'] = $c_id;
			$data['add_time'] = time();

			$teacher_result=D("student")->where("id=$id")->save($data);
		}else{
			//批量写入
            $batch_id=M('batch')->where("status=1")->getField('id');
			$content=I("request.content");
			//print_r($content);die;
			$content_list=explode('_@#_',$content);
            //var_dump($content);die;
            $error=0;
            $error_name='';
			foreach($content_list as $key =>$val) {

				if ($val) {
					$val_list = explode($division, $val);
					//echo "<pre>";       print_r($val_list);die;
					$val_count = count($val_list);//数组的数量
//var_dump($val_list);die;
					if ($val_count != 6) {
						$result['status'] = -2;//姓名，学号，电话，性别都要填
						$result['msg'] = "请按照姓名,学号,电话,性别,身份证,准考证 来传值";//姓名，学号，电话，性别都要填
						$this->ajaxReturn($result);
					}
                    $data['batch_id']=$batch_id;
					$data['student_name'] = $val_list[0];
					$data['student_num'] = $val_list[1];
					$data['mobile'] = $val_list[2];
					$data['id_card'] = $val_list[4];
					$data['zkz_num'] = $val_list[5];
					if($val_list[3]=='男'){
						$data['sex'] = 1;
					}else if($val_list[3]=="女"){
						$data['sex'] = 2;
					}else{
						$result['status'] = -2;//姓名，学号，电话，性别都要填
						$result['msg'] = "请按照姓名,学号,电话,性别,身份证,准考证 来传值";//姓名，学号，电话，性别都要填
						$this->ajaxReturn($result);
					}

					$data['c_id'] = $c_id;
					$data['add_time'] = time();
					$sql_data[] = $data;
                    $student_old= M('student')->where("batch_id=$batch_id and zkz_num='".$val_list[5]."' and id_card='".$val_list[4]."'")->find();
                    if($student_old){
                        $error++;
                        $error_name.=$data['student_name'].',';
                    }
				}
			}
           //echo $error; var_dump($sql_data);die;
            if($error>0){
                $error_name.=' 已经存在了 不能重复导入';
                $this->ajaxReturn(['status'=>-1,'msg'=>$error_name]);
            }
			$teacher_result=D("student")->addAll($sql_data);

		}
        //var_dump($teacher_result);die;
		if($teacher_result){
			$result['status'] = 1;//
			$result['msg'] ='成功';

			$this->ajaxReturn($result);
		}else{
			$result['status'] = -1;//
			$result['msg'] ='系统忙请重试';
				$this->ajaxReturn($result);
		}
	}
	//删除学生
	public function student_del(){

		$student_id=I("request.id",0);
		$test_list=D("test_student")->where("s_id=$student_id")->getField('t_id');
		if($test_list){
			$result['status']=-2;
			$result['msg']='该学生在考试计划中不能删除';
		}else{
			$student_result=D("student")->where("id=$student_id")->delete();
			if($student_result){
				$result['status']=1;
				$result['msg']='成功';
			}else{
				$result['status']=-1;
				$result['msg']='删除失败刷新重试';
			}
		}
        $this->ajaxReturn($result);
	}
	//考场列表
	public function index(){
		//以及分类
		$test_db=D('test_list');
		$page=I('request.page',1);
		$search=I('request.search');
		$category_id=I('request.category_id');
		$category_pid=I('request.category_pid');
		$status=I('request.status','');
		$limit_num=I('request.limit_num',10);
		$this->assign("limit_num",$limit_num);
        $batch_id=M('batch')->where("status=1")->getField('id');

        $where="batch_id=$batch_id";
		$where1="t.batch_id=$batch_id";
		if($search){
			$where.=" and question like '%".$search."%'";
			$where1.=" and s.question like '%".$search."%'";
		}
		//print_r($where);die;
		$pageLimit = pageLimit('test_list',$where,$limit_num);
        $this->assign('page', $pageLimit['page']);

		$test_list=$test_db->join("t left join __CLASS_CATEGORY__ c on t.c_id=c.id")
            ->join("left join __TEST_ROOM__ r on r.id=t.r_id")
			->where($where1)
			->field("t.id,t.test_name,t.status,t.zk_t_id,t.t_id,t.subject_num,r.room_name,'编辑' as  status_name,
			c.category_name,FROM_UNIXTIME(t.create_time,'%Y-%m-%d %H:%i:%s') as add_time")
            //(case t.status when 1 then '继续答题' when 2 then '查看详情' else '编辑' end)as status_name ,
			->order("t.create_time desc,t.status")
            ->group('t.id')
			->limit($pageLimit['limit'])->select();
		//老师数据
        //print_r($test_list);die;
		$teacher_list=D("member")->getField('uid as id,nickname as teacher_name');
		S('teacher_list',$teacher_list);

		foreach($test_list as $key=>$val){
			$teachers=explode(',',$val['t_id']);

			$count=count($teachers);

			$val['url']=U("test/addtest",array("id"=>$val['id']));

            $val['teacher_zk']=$teacher_list[$val['zk_t_id']];
			foreach($teachers as $key2 =>$arr){

				if($count==($key2+1) || $count==1){
					$val['teacher_arr'].=$teacher_list[$arr];
				}else{
					$val['teacher_arr'].=$teacher_list[$arr].',';
				}
			}
			$test_list[$key]=$val;
		}
		//echo "<pre>";	print_r($test_list);die;
		$test_list=$test_list?$test_list:null;
		$this->assign("test_list",$test_list);
		$this->display();
	}


	//删除考试
	public function testDel(){
		$id=I("request.id",0);
		//检测是否有学生打分
		$student_result=D("test_student")->where("t_id=$id and status=1")->select();
		if($student_result){
			$result['status']=-2;
			$result['msg']="考试一开始不可删除";
		}else{

			$student_result=D("test_list")->where("id=$id ")->delete();
			if($student_result){
				$result['status']=1;
				$result['msg']="删除成功";
			}else{
				$result['status']=-1;
				$result['msg']="系统忙";
			}
		}
		$this->ajaxReturn($result);
	}
    //通过学科id筛选 老师
    public function courseTeacher(){
        $course_id=intval(I("request.category_id",0));
        $test_id=intval(I("request.test_id",0));
        if($course_id && $course_id>0){
            if($test_id){
                $test_info=D('test_list')->where("id=$test_id")->find();
                $teacher_check_list=explode(",",$test_info['t_id']);
                $student_check_list=explode(",",$test_info['s_id']);
            }
            $teacher_list=M('member')->where("find_in_set($course_id,course)")->field("uid,nickname")->select();
            $batch_id=M('batch')->where("status=1")->getField('id');
            /*//考官赋值
            $select_list=arraY('主考官','考官一','考官二','考官三','考官四','考官五');
            $data['select_list']=$select_list;*/
            foreach($teacher_list as $key=>$val){
                $result=M("test_list")
                    ->where("batch_id=$batch_id and (".$val['uid']." in(t_id) or zk_t_id=".$val['uid'].")")
                    ->find();
                if($result){
                    $room_name=M('test_room')->where("id=".$result['r_id'])->find();
                    $teacher_list[$key]['nickname']=$val['nickname']." (已分配".$room_name['place'].$room_name['room_name'].")";

                }else{
                    $teacher_list[$key]['nickname']=$val['nickname']." (暂未分配考场)";
                }
            }
            $data['teacher_list']=$teacher_list?$teacher_list:null;;
            $result['list']=$data;
            $result['status']=1;
            $result['status']=1;
            $result['msg']="成功";
        }else{
            $result['status']=-1;
            $result['msg']="参数错误";
        }
        $this->ajaxReturn($result);
    }
    //检测考场是否被占用
    public function roomCheck(){

    }
	//新增 考场详情页  对应的考场数据
	public function addtest(){
		$test_id=I("request.id",0);
		if($test_id){
			$test_info=D('test_list')->where("id=$test_id")->find();

			$teacher_check_list=explode(",",$test_info['t_id']);
		}
		//print_r($teacher_check_list);die;
        $batch_id=M('batch')->where("status=1")->getField('id');
		$teacher_list=D("member")
            ->Field('uid as id,nickname as teacher_name')->select();


		//考官赋值
        $select_list=arraY('主考官','考官一','考官二','考官三','考官四','考官五');
		$teacher_check_list=$teacher_check_list?$teacher_check_list:array();
		//echo "<Pre>";print_r($select_list);//die;
		//echo "<Pre>"; print_r($teacher_check_list);//die;
		///echo "<Pre>"; print_r($teacher_list);die;
        $test_info=$test_info?$test_info:null;
		$this->assign("test_info",json_encode($test_info));
		//考官最少人数
		$this->assign("test_teacher_num",C('TEST_TEACHER_NUM'));
		$this->assign("select_list",$select_list);
		$this->assign("teacher_list",$teacher_list);
		$this->assign("teacher_check_list",$teacher_check_list);

		$category_list=D("class_category")->where("status=1 and pid>0")->field("id,category_name")->select();
		$room_list=D("test_room")->where("status=1")->Field('id,category_id,room_name')->select();
		foreach($category_list as $key=>$val ){
			foreach($room_list as $key2=>$arr){
				if($val['id']==$arr['category_id']){
					$category_list[$key]['list'][]=$arr;
				}
			}
		}
		//echo "<pre>";print_r($category_list);die;
		$this->assign('category_list',json_encode($category_list));

		$this->display();
	}
    //对老师和考场的关系进行绑定
    public function teacherRoomSave(){
        //当前批次的id
        $batch_id=M('batch')->where("status=1")->getField('id');

        $test_id=I('request.test_id',0);
        $data['zk_t_id']=I('zk_t_id');
        $data['t_id']=I('t_id');
        $data['r_id']=I('r_id');
        $data['c_id']=I('category_id');
        $where="batch_id=$batch_id";

        if(empty($data['zk_t_id']) || empty($data['t_id']) || empty($data['r_id']) || empty($data['c_id'])){
            $result['status']=-1;
            $result['msg']="参数不正确";
            $this->ajaxReturn($result);
        }

        if($test_id){//编辑
            $where.=" and id<>$test_id ";
        }
        $where_room=$where." and r_id=".$data['r_id'];
        //var_dump($where_room);die;
        $room_check=M('test_list')->where("$where_room")->select();
        //var_dump($room_check);die;
        if($room_check){
            $result['status']=-1;
            $room_name=M('test_room')->where("id=".$data['r_id'])->getField('room_name');
            $result['msg']=$room_name."已经被占用了";
            $this->ajaxReturn($result);
        }
        //检测 教师是否在别的考场当考官
        $check_ids=explode(',',$data['t_id'].",".$data['zk_t_id']);
        foreach($check_ids as $key=>$val){
            $where_zk_t=$where." and (find_in_set($val,t_id) or zk_t_id=$val)";
            $where_zk_t_check=M('test_list')->where("$where_zk_t")->select();
            if($where_zk_t_check){
                $result['status']=-1;
                $room_name=M('member')->where("uid=$val")->getField('nickname');
                $result['msg']=$room_name."已经安排在另外的考场了";
                $this->ajaxReturn($result);
            }
        }
        $data['batch_id']=$batch_id;
        $data['create_time']=time();
        if($test_id){//编辑
            $check=M('test_list')->where("id=$test_id")->save($data);
        }else{
            $check=M('test_list')->add($data);
        }

        //var_dump($check);die;
        if(!empty($check) || $check===0){
            $result['status']=1;
            $result['msg']=$room_name."成功";
        }else{
            $result['status']=-1;
            $result['msg']=$room_name."系统忙";
        }
        $this->ajaxReturn($result);
    }
    //考试学生列表
    public function testStudent(){

        $user_id=UID;//考官id
        $batch_id=M('batch')->where("status=1")->getField('id');
        //获取当前考试id
        if($batch_id){
            $test_info=M('test_teacher')->where("teacher_id=$user_id and batch_id=$batch_id")->field("t_id,is_zkg")->find();
            $test_id=$test_info['t_id'];
            $this->assign("is_zkg",$test_info['is_zkg']);//是否是主考官
            $this->assign("test_id",$test_id);//是否是主考官
            $limit_num=I('request.limit_num',10);
            $search=I('request.$search','');
            $this->assign("limit_num",$limit_num);
            if($test_id){
                $where="ts.t_id=$test_id";
                $where1="t_id=$test_id";
                /*$sql=M('test_student')->where($where1)
               ->select(false);
           //var_dump($sql);die;
           $pageLimit = pageLimitLeft($sql,$limit_num);*/
                $pageLimit = pageLimit('test_student',$where1,$limit_num);

                //var_dump($pageLimit['page']);die;

                $student_list=M('test_student')->where($where)
                    ->join("ts left join __STUDENT__ s on ts.s_id =s.id")
                    ->join(" left join __CLASS__ c on s.c_id =c.id")
                    ->join(" left join __CLASS_CATEGORY__ cc on cc.id =c.c_id")
                    ->join(" left join __TEST_LIST__ tl on ts.t_id =tl.id")
                    ->join(" left join __TEST_ROOM__ r on tl.r_id =r.id")
                    ->field("ts.id as ts_id,s.id as student_id,s.student_name,if(s.sex=1,'男','女')as sex_name,r.room_name,FROM_UNIXTIME(ts.create_time,'%Y-%m-%d %H:%i:%s') as time,s.student_num,s.zkz_num,cc.category_name,c.class_name,(case ts.status when 0 then '等待面试' when -1 then '开始面试 'when 1 then '面试已完成' end) as status_name,ts.status")
                    ->order("ts.status ")
                    ->group('ts.id')
                    ->limit($pageLimit['limit'])
                    ->select();
            }else {
                $student_list = [];
                $pageLimit['page'] = 1;
            }
        }else{
            $student_list=null;
            $pageLimit['page']=1;
        }

        $this->assign('page', $pageLimit['page']);
        $this->assign("student_list",$student_list);

        //echo "<pre/>";print_r($student_list);die;
        $this->display('testlist');

    }
    //主考官宣讲完 考试规则 改变学生状态
    public function testStudentStatus(){
        $user_id=UID;//考官id
        $batch_id=M('batch')->where("status=1")->getField('id');
        //获取当前考试id
        $student_id=I('request.student_id',0);

        $test_info=M('test_teacher')->where("teacher_id=$user_id and batch_id=$batch_id")->field("t_id,is_zkg")->find();
        $test_id=$test_info['t_id'];
        $this->assign("is_zkg",$test_info['is_zkg']);//是否是主考官
        if(empty($student_id)){
            $result['status']=-1;
            $result['msg']='参数错误';
        } else if($test_info['is_zkg']==1){
            $a=M('test_student')->where("s_id= $student_id and t_id=$test_id")->setField('status',-1);
            if($a || $a===0){
                $result['status']=1;
                $result['msg']='成功';
            }else{
                $result['status']=-1;
                $result['msg']='系统忙';
            }
        }else{
            $result['status']=-1;
            $result['msg']='不是主考官不能进行此操作';
        }

        $this->ajaxReturn($result);

    }
	//开始考试
	public function testtabs(){
		//检测该老师是否是这场考试的考官
		$user_id=UID;

        $batch_id=M('batch')->where("status=1")->getField('id');

        $test_id=I("request.test_id");
        $student_id=I("request.student_id");
        $test_info=M('test_teacher')->where("teacher_id=$user_id and batch_id=$batch_id and s_id=$student_id")
            //->field("t_id,is_zkg,status")
            ->find();
        $test_id=$test_info['t_id'];
        $this->assign("is_zkg",$test_info['is_zkg']);//是否是主考官
		$this->assign("test_id",$test_id);
		$this->assign("student_id",$student_id);
		if(empty($test_id)){
			$this->error('参数不正确',U("test/testStudent"),1);
		}
        //print_r($test_info);
        //die;
        $this->assign("is_zgk_df_status",0);
        if($test_info['status']==1 && $test_info['is_zkg']!=1){
            //考官打完分
			$this->error('已经打过分了',U("test/testStudent"),1);
		}else if($test_info['status']==1 && $test_info['is_zkg']==1){
            //主考官已经打完分 再次打完分
            $this->assign("is_zgk_df_status",1);
            //$test_result=A('student')->testYjdf($test_id,$student_id,$user_id);
            //$this->assign("is_zgk_df_data",json_encode($test_result));
        }

		//老师列表
		$teacher_list=explode(",",D("test_list")->where("id=$test_id and batch_id=$batch_id")->getField('t_id'));
        $teacher_list[]=M('test_list')->where("id=$test_id and batch_id=$batch_id")->getField('zk_t_id');
		if(in_array($user_id,$teacher_list)){

		}else{
			$this->error('不是本场考试的考官不能进入管理',U("test/testStudent"));
		}
		//检测是否已经结束
		$status=D("test_list")->where("id=$test_id")->getField('status');
		if($status==2){
			$this->error('本场考试已经结束',U("test/testStudent"));
		}
		$this->display();
	}
	//开始考试 返回学生数据 可能是中途断网或者课件休息，可以提取未答题的学生 再次答题；
	public function test_start(){
		$test_id=I("request.test_id",'');
		$student_id=I("request.student_id",'');
		if(empty($test_id) || empty($student_id)){
            $data['status']=-1;
            $data['msg']='参数不正确';
			$this->ajaxReturn($data);
		}
		$student_model=A('Admin/student');
		$result=$student_model->studentInfo($test_id,$student_id);
		//echo "<pre>";print_r($result);die;
        if($result){
            $data['status']=1;
            $data['msg']='成功';
            $data['data']=$result;
        }else{
            $data['status']=-1;
            $data['msg']='参数不正确';
            $data['data']=$result;
        }
		$this->ajaxReturn($data);

	}
    //抽题员 首页
    public function chouti(){
        $this->display("");
    }

    //返回学生信息
    public function studentCheck(){

        $id_card=I('request.id_card','');
        $zkz_num=I('request.zkz_num','');
        //var_dump($zkz_num);die;
        //当前批次的id
        $batch_id=M('batch')->where("status=1")->getField('id');
        $category_info= M('student')->join("s left join __CLASS__ c on s.c_id=c.id")
            ->join(" left join __CLASS_CATEGORY__ cc on c.c_id=cc.id")
            ->where(" s.batch_id=$batch_id and (id_card='".$id_card."'  or zkz_num='".$zkz_num."')")
            ->Field('s.id,s.student_name,s.zkz_num,s.id_card,s.mobile,cc.category_name')
            ->find();
        //->select(false);
        //var_dump($category_info);die;
        if(empty($category_info)){
            $result['status']=-1;
            $result['msg']='未查到';
        }else{
            //检测当前学生是否已经参加了考试
            $student_id=$category_info['id'];
           $student_result= M("test_student")->join("ts left join jy_test_list tl on ts.t_id=tl.id")->where(" ts.s_id=$student_id and tl.batch_id=$batch_id")->select();
            if(!empty($student_result)){
                $result['status']=-1;
                $result['msg']='每个考生每次模拟考只能参加1门考试，你已经参加过了！';
            }else{
                $result['status']=1;
                $result['data']=$category_info;
                $result['msg']='成功';
            }

        }

        $this->ajaxReturn($result);

    }

    //抽屉员抽题 传入学生的班级，学科信息
    public function choutiAjax(){
        $student_id=I("request.student_id",0);
        if(empty($student_id)){
            $result['status']=-1;
            $result['msg']='参数错误';
            $this->ajaxReturn($result);
        }
        //检测学生是否已经抽过题目了

        $category_info= M('student')->join("s left join __CLASS__ c on s.c_id=c.id")->join(" left join __CLASS_CATEGORY__ cc on c.c_id=cc.id")->where("s.id=$student_id")->Field('cc.category_name,cc.id,cc.num_xj,cc.bxt_num,cc.xjt_num')->find();
        $category_id=$category_info['id'];
        $batch_id=M('batch')->where("status=1")->getField('id');
        //检测题库是否数量足够
        $subject_list_bx=M('subject')->where("status=1 and c_id=$category_id and is_bd=1")->getField('id',true);
        $subject_list_xj=M('subject')->where("status=1 and c_id=$category_id and is_bd=0")->getField('id',true);

        if( (count($subject_list_bx)<$category_info['bxt_num']) || (count($subject_list_xj)<$category_info['xjt_num'])){//题库数量不够
            $result['status']=-1;
            $result['msg']='题库数量不够';
            $this->ajaxReturn($result);
        }
        //print_r($subject_list_bx);die;
        //抽必选题 不显示需要重新传过来
        shuffle($subject_list_bx);//打乱数组
        shuffle($subject_list_xj);//打乱数组
        for($i=0;$i<$category_info['bxt_num'];$i++){
            $bx_ids[]=$subject_list_bx[$i];
        }
        //抽选讲题
        for($i=0;$i<$category_info['xjt_num'];$i++){
            $xj_ids[]=$subject_list_xj[$i];
        }
        //必选题直接返回题目id
        $result['bx_ids']=$bx_ids;
        $result['category_id']=$category_id;//待卡科目
        $result['category_name']=$category_info['category_name'];//待卡科目
        //选讲题返回题目类型 有客户选择
        foreach($xj_ids as $key=>$val){
            $xj_list[]=M('subject')->where("id=$val")->field("id,subject_name,content,ask")->find();
        }
        $result['xj_subject']['list']=$xj_list;
        $result['xj_subject']['xjt_num']=$category_info['xjt_num'];
        $result['xj_subject']['num_xj']=$category_info['num_xj'];

        //返回考场列表
       $room_list= M("test_room")->where("r.status=1 and r.category_id=$category_id and t.id>0 and t.batch_id=$batch_id ")
           ->join("r left join __TEST_LIST__ t on r.id=t.r_id ")
           ->field("r.room_name,r.id,r.bm,t.id as test_id")->select();

        //print_r($room_list);die;
        foreach($room_list as $Key=>$val){

            $test_info=M('test_list')->where("batch_id=$batch_id and r_id=".$val['id'])->find();
            if(empty($test_info)){
                $room_list[$Key]['wait_student']=0;
            }else{
                $wait_student=M('test_student')->where("status=0 and t_id=".$test_info['id'])->count();
                $room_list[$Key]['wait_student']=$wait_student>0?$wait_student:0;
            }
        }
        $room_list2=$room_list;
        //echo "<pre/>";print_r($room_list);die;

        array_multisort(array_column($room_list2,'wait_student'),SORT_ASC,$room_list2);
        //echo "<pre/>";print_r($room_list2);die;

        $xz_id= $room_list2[0]['id'];
        foreach($room_list as $key=>$val){
            if($xz_id==$val['id']){
                $room_list[$key]['is_xz']=1;
            }else{
                $room_list[$key]['is_xz']=0;
            }
        }
        //echo "<pre/>";print_r($room_list);die;
        $result['room_list']=$room_list;
        $data['data']=$result;
        $data['status']=1;
        $data['msg']='成功';

        $this->ajaxReturn($data);

    }
    //获取当前考场的待考数量
    public function testWaitNum(){


    }

    public function student_daoru(){
        $batch_id=M('batch')->where("status=1")->getField('id');
        $batch_name_new=M('batch')->where("id=$batch_id")->getField('batch_name');

        $this->assign("batch_name_new",$batch_name_new);

        //展示所有的批次班级数据 二级联动
        $batch_list=M('batch')->where("id<>$batch_id ")->field("id,batch_name")->select();
        foreach($batch_list as $key=>$val){
            $student_batch_list=D("student")->join("s left join __BATCH__ b on s.batch_id=b.id")
                ->join(" left join __CLASS__ c on s.c_id=c.id")
                ->join(" left join __CLASS_CATEGORY__ cc on c.c_id=cc.id")
                ->where("s.batch_id=".$val['id'])
                ->group("s.c_id")
                ->field("s.c_id,c.class_name,c.c_id as category_id,cc.category_name")
                ->select();
            $category=[];
            foreach($student_batch_list as $key_c=>$arr){

                $class_info['c_id']=$arr['c_id'];
                $class_info['class_name']=$arr['class_name'];
                $category[$arr['category_id']]['class_list'][]=$class_info;;
                $category[$arr['category_id']]['category_name']=$arr['category_name'];
                $category[$arr['category_id']]['category_id']=$arr['category_id'];
                unset($class_info);
                unset($category_info);
            }
            $batch_list[$key]['category_list']=$category;
        }

        foreach($batch_list as $key=>$val){
            foreach($val['category_list'] as $key_c=>$arr){
                $batch_list[$key]['category'][]=$arr;
            }
            unset($batch_list[$key]['category_list']);
        }
        //echo "<pre/>";print_r($batch_list);die;
        $this->assign("batch_list",json_encode($batch_list));
        $this->display();

    }
    //考场列表的数据
    public function addroom(){
        $room_name=I('request.room_name','');

        $limit_num=I('request.limit_num',10);
        //$limit_num=1;
        $this->assign("limit_num",$limit_num);
        $page=I('request.p',1);
        $url=U("test/addroom",array('p'=>$page));
        $this->assign("url",$url);
        $batch_id=M('batch')->where("status=1")->getField('id');
        $where="1";

        $class_category_list=D("class_category")->getField("id,category_name");
        $class_category_list2=D("class_category")->where("status=1 and pid>0")->Field("id,category_name")->select();

        $this->assign("class_category_list",json_encode($class_category_list2));

        if($room_name){
            $where.=" and room_name like '%".$room_name."%'";
        }
        $pageLimit = pageLimit('test_room',$where,$limit_num);
        $room_list=D("test_room")->where($where)->order("id desc")	->limit($pageLimit['limit'])
            ->field("id,room_name,category_id,place,bm,if(status=1,'启用','弃用') as status_name")->select();
        foreach($room_list as $key=>$arr){
            $room_list[$key]['category_name']=$class_category_list[$arr['category_id']];
        }
        //echo "<pre>";	print_r($room_list);die;

        $this->assign("room_list",$room_list);
        $this->assign('page', $pageLimit['page']);
        //echo "<pre>";	print_r($student_list);die;
        $this->display();
    }
    //考场详情数据展示
    public function testRoomInfo(){
        $room_id=I('request.id',0);
        if(empty($room_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'参数不正确']);
        }
        $room_info=M('test_room')->where("id=$room_id")->find();
        $room_info=$room_info?$room_info:null;
        $this->ajaxReturn(['status'=>1,'msg'=>'成功','data'=>$room_info]);

    }
    //删除考场
    public function testRoomDel(){
        $room_id=I('request.id');
        if(empty($room_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'参数不正确']);
        }
        $room_status=M('test_student')->join(" ts left join __TEST_LIST__ tl on ts.t_id=tl.id")->where("tl.r_id=$room_id")->select();
        if($room_status){
            $this->ajaxReturn(['status'=>-1,'msg'=>'该考场已经有学生参加考试，不能删除']);
        }
        $a=M('test_room')->where("id=$room_id")->delete();
        if(empty($a)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'系统忙']);
        }else{
            $this->ajaxReturn(['status'=>1,'msg'=>'成功']);

        }
    }
    //考场新增，保存显示页面
    public function testRoomSave(){
        $room_id=I('request.room_id',0);
        $saveData['room_name']=I('request.room_name','');
        $saveData['category_id']=I('request.category_id',0);
        $saveData['place']=I('request.place','');
        $saveData['bm']=I('request.bm','');
        $saveData['status']=I('request.status','');
        if($room_id){
            $a=M('test_room')->where("id=$room_id")->save($saveData);
        }else{
            $a=M('test_room')->add($saveData);
        }
        if($a || $a===0){
            $this->ajaxReturn(['status'=>1,'msg'=>'成功']);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'失败']);
        }

    }

    //保存学生抽题的记录  选讲 写入数据库 一次抽题员选讲，一次考官必答题；
    public function choutiSave(){
        //获取所有参数
        $batch_id=M('batch')->where("status=1")->getField('id');
        $student_id=I('request.student_id',0);   //学生id
        $r_id=I('request.r_id',0);    //考场id


        if(empty($student_id)|| empty($batch_id)|| empty($r_id)){
            $data['status']=-1;
            $data['msg']='参数不正确';
            $this->ajaxReturn($data);
        }

        $student_data['bd_subject']=I('request.bx_ids',0);
        $student_data['xj_subject']=I('request.xj_ids',0);
        //进行2个表的操作     学生成绩表 和老师打分表；
        //学生成绩表
        $test_student=M('test_student');
        $test_teacher=M('test_teacher');

        $test_id=M("test_list")->where("batch_id=$batch_id and r_id=$r_id")->getField('id');
        if(empty($test_id) || empty($student_id) || empty($r_id) || empty($student_data['bd_subject']) || empty($student_data['xj_subject'])){
            $data['status']=-1;
            $data['msg']='参数错误';
            $this->ajaxReturn($data);
        }
        $student_data['t_id']=$test_id;
        $student_data['s_id']=$student_id;

        $student_data2['t_id']=$test_id;
        $student_data2['s_id']=$student_id;

        $check_s=$test_student->where($student_data2)->find();
        $test_info=M("test_list")->where("id=$test_id")->find();
        $teacher_list[]=['is_zkg'=>1,"id"=>$test_info['zk_t_id']];
        $teacher_s=explode(",",$test_info['t_id']);
        foreach($teacher_s as $key=>$val){
            $a=['is_zkg'=>0,'id'=>$val];
            $teacher_list[]=$a;
        }
        foreach($teacher_list as $key=>$val){

            $data_check="batch_id=$batch_id and t_id=".$val['id'];
            //var_dump($data_check);die;
            $check_t=$test_teacher->where($data_check)->find();
            if($check_t){
                $check_t_1=1;
            }
        }

        if($check_s || $check_t_1){
            $data['status']=-1;
            $data['msg']='每个考生每次模拟只能参加一次考试';
            $this->ajaxReturn($data);
        }

        M()->startTrans();//开启事务

        //老师打分表 sql
        $m=M('test_teacher');
        $data_sql="INSERT INTO jy_test_teacher (s_id,t_id,teacher_id,batch_id,is_zkg,create_time) VALUES ";
        foreach($teacher_list as $key2=>$arr){
            if($arr['is_zkg']==1){
                $data_sql.="(".$student_id.','.$test_id.",".$arr['id'].','.$batch_id.",1,".time()."),";
            }else{
                $data_sql.="(".$student_id.','.$test_id.",".$arr['id'].','.$batch_id.",0,".time()."),";
            }
        }
        $data_sql = substr($data_sql,0,strlen($data_sql)-1);

        $a=$m->execute($data_sql);
        $student_data['create_time']=time();
        $test_student_add=M('test_student')->add($student_data);

        //var_dump($a);var_dump($test_student_add);die;
        if($test_student_add && $a){
            M()->commit();//事务提交
            $data['status']=1;
            $data['msg']='成功';
            $this->ajaxReturn($data);
        }else{
            M()->rollback();//回滚*/
            $data['status']=-1;
            $data['msg']='系统忙';
            $this->ajaxReturn($data);
        }

    }


}