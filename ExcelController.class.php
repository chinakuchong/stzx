<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;
use User\Api\UserApi as UserApi;
header("content-type:text/html;charset=utf-8");
/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
//setlocale(LC_ALL,array('zh_CN.gbk','zh_CN.gb2312','zh_CN.gb18030'));
//setlocale(LC_ALL, 'zh_CN');
class ExcelController extends AdminController {
	/**
	 * 导入excel数据
	 */

    public function test(){
        $a=M('class')->where("status=1")->getField('class_name,id');
        print_r($a);die;
    }
    //上传学生信息
	public function studentImport(){
        $type=I("request.type",1);//1 默认更新以及新增 2删除新增
        if($type==1){
            $this->ajaxReturn(['status'=>-1,'msg'=>'系统忙请重试']);
        }else{
            $this->ajaxReturn(['status'=>1,'msg'=>'成功']);
        }
        die;
		// 导入csv格式的数据
		$upFile = $_FILES['file'];
		//print_r($upFile);exit;
		/*var_dump($_FILES["file"]["type"]);die;
		if($_FILES["file"]["type"]!='csv'){
			$this->ajaxReturn(['status'=>-1,'msg'=>'请另存为csv文件上传！']);
		}*/
        $type=I("request.type",1);//1 默认更新以及新增 2删除新增
        //var_dump($type);die;
		if ($upFile['error']==0 && !empty($upFile)) {
			$dirpath = 'Uploads/Excel';
			$filename=date('Y-m-d').'_'.rand(10000,99999).'.xlsx';
			//$filename='10000.xlsx';
			$queryPath = './'.$dirpath.'/'.$filename;
			//move_uploaded_file将浏览器缓存file转移到服务器文件夹
			//var_dump($_FILES['file']['tmp_name']);//die;
			if(move_uploaded_file($_FILES['file']['tmp_name'],$queryPath)){
				//echo $queryPath;
			}else{
				$this->ajaxReturn(['status'=>-1,'msg'=>'系统忙请重试']);
			}
            // 检测
           $class_list= M("classes")->where("state=1")->select();
           $school_list= M("school")->where("state=1")->select();
//            //获取数据流
//            $file = fopen($queryPath, 'r');
//            //var_dump(filesize($queryPath));echo "<br/>";
//            stream_filter_append($file, "convert.iconv.gbk/utf-8");
//            $aaaa='';
//            while (($buffer = fgets($file, 4096)) !== false) {
//                //var_dump($buffer);die;
//                $aaaa.=$buffer;
//            }
//            //echo $aaaa;die;
//            $myfile = fopen($queryPath, "w");
//            fwrite($myfile, $aaaa);
            $data=import_excel($queryPath);
//			echo "<pre/>";print_r($data);die;
			if(empty($data)){
				$this->ajaxReturn(['status'=>-1,'文件不能为空']);
			}else{





				if($data[1][0]=='姓名'  || !is_int($data[1][0])){//有表头删除这个数组
					unset($data[1]);
				}
				$count=0;//错误数量
				$true_num=0;//导入正确的学生数量
				M()->startTrans();//开启事务

                //班级列表
                $class_list=M('class')->where("status=1")->getField('class_name,id');
                $sex_list=['男'=>1,'女'=>2];
                //删除batch_id的数据 重新插入;
                if($type==2){
                    M('student')->where("batch_id=$batch_id")->delete();
                }

                foreach($data as $key=>$arr){
                    foreach($arr as $key2=>$val){
                        $val=str_replace(array("\r\n", "\r", "\n","\t"), "",$val);
                        $val=str_replace(' ', '',  $val);
                        $arr[$key2]=$val;
                    }
                    $data[$key]=$arr;
                }

                //echo "<pre/>";print_r($data);die;
                //var_dump($data);die;
				foreach($data as $key=>$val){
					//准考证号
					//var_dump($val);die;
                    $student['batch_id']=$batch_id;
					$student['student_name']=$val[0];
                    $class_cr=$val[1];

                    //var_dump($class_cr);die;
                    $student['c_id']=$class_list[$class_cr];
                    $student['student_num']=$val[2];
                    $student['zkz_num']=$val[3];
                    $sex=$val[4];

                    $student['sex']=$sex_list[$sex];
                    $student['id_card']=$val[5];
                    $student['mobile']=$val[6];
                    $student['status']=1;
                    $student['add_time']=time();


                    //var_dump($student);die;

                    $update_check=M('student')->where("batch_id=$batch_id and id_card='".$val[5]."'")->find();
                    $update_check_id=$update_check['id'];


                    if($update_check_id){
                        $a=M('student')->where("id=$update_check_id")->save($student);
                        if(empty($a) && $a!==0){
                            $count++;
                        }else{
                            $true_num++;
                        }
                    }else{
                        $a=M('student')->add($student);
                        //插入登录数据
                        if(empty($a)){
                            $count++;
                        }else{
                            $true_num++;
                        }
                    }
                    //$a=M('student')->add($student);
				}
				//var_dump($count);die;
				if($count>0){
					M()->rollback();//回滚
					$this->ajaxReturn(['status'=>-3,"true_num"=>0,'msg'=>'系统忙，请重试！']);
				}else{
					M()->commit();//事务提交
					$this->ajaxReturn(['status'=>1,"true_num"=>$true_num,'msg'=>'上传成功！']);

				}

			}

		}else{
			$this->ajaxReturn(['status'=>-1,'上传失败']);
		}

	}
    //学生导出
    public function studentDc(){
        $batch_id=M('batch')->where("status=1")->getField('id');
        if(empty($batch_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'还没有设置批次']);
        }
        $student_list=M('student')
            ->where("batch_id=$batch_id")
            ->join(" s left join __CLASS__ c on s.c_id=c.id")
            ->field("s.student_name,c.class_name,s.student_num,s.zkz_num,if(s.sex=1,'男','女')sex,s.id_card,s.mobile")
            ->order("s.zkz_num")
            //->limit($page_ajax,$limit_num)
            ->select();
        $header=['姓名','班级','学号','准考证号','性别','身份证','联系方式'];
        $zidun_array=['student_name','class_name','student_num','zkz_num','sex','id_card','mobile'];
        $filename='./Uploads/Excel/studentdaochu.xlsx';
        //create_csv($student_list,$header,$filename);
        excel_daochu($student_list,$header,$filename,$zidun_array);
    }
    //学生成绩导出
    public function studentResultDc(){
        $batch_id=M('batch')->where("status=1")->getField('id');
        if(empty($batch_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'还没有设置批次']);
        }
        $where="s.batch_id=$batch_id";
        $student_list=M('test_student')->where($where)
            ->join("ts left join __STUDENT__ s on ts.s_id =s.id")
            ->join(" left join __TEST_LIST__ tl on ts.t_id =tl.id")
            ->join(" left join __TEST_ROOM__ r on tl.r_id =r.id")
            ->join(" left join __CLASS__ c on s.c_id =c.id")
            ->field("s.id,s.zkz_num,s.student_name,s.student_num,r.room_name,r.id as room_id,s.id_card,c.class_name,ts.test_num_all,s.status,ts.pfx,ts.is_cxdf,FROM_UNIXTIME(ts.create_time,'%Y-%m-%d %H:%i') as create_time")
            ->select();

        $result=[];
        foreach($student_list as $key=>$val){

            $data['zkz_num']=$val['zkz_num'];
            $data['student_name']=$val['student_name'];
            $data['test_num_all']=$val['test_num_all'];
            if($val['is_cxdf']){
                $pfx=json_decode($val['pfx']);
                $data['test_result1']=$pfx['test_result1'];
                $data['test_result2']=$pfx['test_result2'];
                $data['test_result3']=$pfx['test_result3'];
                $data['test_result4']=$pfx['test_result4'];
                $data['test_result5']=$pfx['test_result5'];
                $data['test_result6']=$pfx['test_result6'];
                $data['test_result7']=$pfx['test_result7'];
                $data['test_result8']=$pfx['test_result8'];
                //var_dump($pfx);die;
            }else{
                //echo $val['id'];
                $pfx= M('test_teacher')->where("batch_id=$batch_id and  status=1 and s_id=".$val['id'])
                    ->field("cast(avg(test_result1) as decimal(5,2)) as test_result1,cast(avg(test_result2) as decimal(5,2)) as test_result2,cast(avg(test_result3) as decimal(5,2)) as test_result3,cast(avg(test_result4) as decimal(5,2)) as test_result4,cast(avg(test_result5) as decimal(5,2)) as test_result5,cast(avg(test_result6) as decimal(5,2)) as test_result6,cast(avg(test_result7) as decimal(5,2)) as test_result7,cast(avg(test_result8) as decimal(5,2)) as test_result8")
                    ->find();
                //echo "<pre/>";print_r($pfx);die;
                $data['test_result1']=$pfx['test_result1'];
                $data['test_result2']=$pfx['test_result2'];
                $data['test_result3']=$pfx['test_result3'];
                $data['test_result4']=$pfx['test_result4'];
                $data['test_result5']=$pfx['test_result5'];
                $data['test_result6']=$pfx['test_result6'];
                $data['test_result7']=$pfx['test_result7'];
                $data['test_result8']=$pfx['test_result8'];
            }
            $data['id_card']=$val['id_card'];
            $data['room_id']=$val['room_id'];
            $data['room_name']=$val['room_name'];
            $data['create_time']=$val['create_time'];
            $result[]=$data;
        }

        //die;
        $header=['准考证号','姓名','总分','一','二','三','四','五','六','七','八','证件号码','考点代码','考场名称','考试时间'];
        $filename='./Uploads/Excel/studentcj_daochu.xlsx';
        $zidun_array=['zkz_num','student_name','test_num_all','test_result1','test_result2','test_result3','test_result4','test_result5','test_result6','test_result7','test_result8','id_card','room_id','room_name','create_time'];

        //create_csv($result,$header,$filename);
        excel_daochu($result,$header,$filename,$zidun_array);

    }
    //上传用户信息
	public function userImport(){

        include('./Application/User/Conf/config.php');

		// 导入csv格式的数据
		$upFile = $_FILES['file'];
		//print_r($upFile);exit;
		/*var_dump($_FILES["file"]["type"]);die;
		if($_FILES["file"]["type"]!='csv'){
			$this->ajaxReturn(['status'=>-1,'msg'=>'请另存为csv文件上传！']);
		}*/
        $type=I("request.type",1);//1 默认更新以及新增 2删除新增
        //var_dump($type);die;
		if ($upFile['error']==0 && !empty($upFile)) {
			$dirpath = 'Uploads/Excel';
			$filename=date('Y-m-d').'_'.rand(10000,99999).'.xlsx';
			$queryPath = './'.$dirpath.'/'.$filename;
			//move_uploaded_file将浏览器缓存file转移到服务器文件夹
			//var_dump($queryPath);die;
			if(move_uploaded_file($_FILES['file']['tmp_name'],$queryPath)){
				//echo $queryPath;
			}else{
				$this->ajaxReturn(['status'=>-2,'msg'=>'系统忙请重试']);

			}
            $batch_id=M('batch')->where("status=1")->getField('id');
            if(empty($batch_id)){
                $this->ajaxReturn(['status'=>-1,'msg'=>'还没有设置批次']);
            }
//            $file = fopen($queryPath, 'r');
//            //var_dump(filesize($queryPath));echo "<br/>";
//            stream_filter_append($file, "convert.iconv.gbk/utf-8");
//
//            $aaaa='';
//            while (($buffer = fgets($file, 4096)) !== false) {
//                //var_dump($buffer);die;
//                $aaaa.=$buffer;
//            }
//            //echo $aaaa;die;
//
//            $myfile = fopen($queryPath, "w");
//            fwrite($myfile, $aaaa);
            $data=import_excel($queryPath);

			//echo "<pre/>";print_r($data);die;
			if(empty($data)){
				$this->ajaxReturn(['status'=>-1,'文件不能为空']);
			}else{
				if($data[1][0]=='姓名'  || !is_int($data[1][0])){//有表头删除这个数组
					unset($data[1]);
				}
				$count=0;//错误数量
				$true_num=0;//导入正确的学生数量
				M()->startTrans();//开启事务

                //班级列表
                $category_list=M('class_category')->where("pid>0")->getField('category_name,id');
                $sex_list=['男'=>1,'女'=>2];
                //删除batch_id的数据 重新插入;
                if($type==2){
                    M('member')->where("uid>2")->delete();
                    M('ucenter_member')->where("id>2")->delete();
                }

                foreach($data as $key=>$arr){
                    foreach($arr as $key2=>$val){
                        $val=str_replace(array("\r\n", "\r", "\n","\t"), "",$val);
                        $val=str_replace(' ', '',  $val);
                        //$val=iconv('gb18030','UTF-8',$val);
                        $arr[$key2]=$val;
                    }
                    $data[$key]=$arr;
                }

                //var_dump($data);die;
				foreach($data as $key=>$val){
					//准考证号
					//var_dump($val);die;

                    // 先检测是否有这个用户

                    $update_check=M('member')->where("id_card='".$val[2]."'")->find();
                    $update_check_uid=$update_check['uid'];
                    //var_dump($update_check);die;

                    $member['card_name']=$val[0];
                    $member['nickname']=$val[0];
                    //var_dump($val[1]);die;
                    $member['sex']=$sex_list[$val[1]]?$sex_list[$val[1]]:1;
                    $member['id_card']=$val[2];

                    $member['mobile']=$val[3];
                    $course=$val[4];
                    $course_list=explode('_#@#_',$course);
                    $member['course']='';
                    foreach($course_list as $key_c=>$arr){
                        if($category_list[$arr]){
                            $member['course'].=$category_list[$arr].',';
                        }
                    }
                    if($member['course']){
                        $member['course']=substr($member['course'],0,-1);
                    }
                    $member['status']=1;

                    if($update_check_uid){
                        $a=M('member')->where("uid=$update_check_uid")->save($member);
                        if(empty($a) && $a!==0){
                            $count++;
                        }
                    }else{
                        $a=M('member')->add($member);
                        //插入登录数据
                        $member_c['id']=$a;
                        $member_c['status']=1;
                        $member_c['email']='';
                        $member_c['username']=$member['id_card'];
                        $member_c['password']=$this->think_ucenter_md5('123456', UC_AUTH_KEY);

                        $b=M('ucenter_member')->add($member_c);
                        if(empty($a) ||empty($b)){
                            $count++;
                        }else{
                            //给用户分配权限 默认考官
                            $data_group['uid']=$b;
                            $data_group['group_id']=1;
                            M("auth_group_access")->add($data_group);
                            $true_num++;
                            //echo 99;echo "<br/>";
                        }
                    }

				}
				//var_dump($count);die;
				if($count>0){
					M()->rollback();//回滚
					$this->ajaxReturn(['status'=>-3,"true_num"=>0,'msg'=>'系统忙，请重试！']);
				}else{
					M()->commit();//事务提交
					$this->ajaxReturn(['status'=>1,"true_num"=>$true_num,'msg'=>'上传成功！']);

				}

			}

		}else{
			$this->ajaxReturn(['status'=>-1,'上传失败']);
		}
	}
     private function think_ucenter_md5($str, $key = 'ThinkUCenter'){
        return '' === $str ? '' : md5(sha1($str) . $key);
    }
    //考官导出
    public function userDc(){
        $batch_id=M('batch')->where("status=1")->getField('id');
        if(empty($batch_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'还没有设置批次']);
        }
        $user_list=M('member')
            ->where("uid>2")
            ->field("card_name,if(sex=1,'男','女')sex,id_card,mobile,course")
            //->limit($page_ajax,$limit_num)
            ->select();
        $category_list=M('class_category')->getField('id,category_name');

        foreach($user_list as $key=>$val){
            $course_list=explode(',',$val['course']);
            $zy='';
            foreach($course_list as $key2=>$arr){
                if($category_list[$arr]){
                    $zy.=$category_list[$arr]."_#@#_";
                }
            }
            if($zy){
                //截取最后五位
                $res=substr($zy,0,-5);
            }
            $user_list[$key]['course']=$res;
            $user_list[$key]['id_card']=$val['id_card'];
        }

        $header=['姓名','性别','身份证','联系方式','教授课程'];
        $filename='./Uploads/Excel/userdaochu.xlsx';
        $zidun_array=['card_name','sex','id_card','mobile','course'];
        //create_csv($user_list,$header,$filename);
        excel_daochu($user_list,$header,$filename,$zidun_array);
    }


    //学生赋值批次数据导入 按钮操作；
    public function pcdr(){

        //当前的被导入的批次id
        $new_batch_id=M('batch')->where("status=1")->getField('id');
        $class_ids=I('request.class_ids','');
        //复制的批次
        $batch_old_id=I('request.batch_old_id','');
        if(empty($class_ids) || empty($batch_old_id)){
            $this->ajaxReturn(['status'=>-1,'参数不正确']);
        }
        //var_dump($class_ids);die;
        $data=M('student')->where("batch_id=$batch_old_id and find_in_set(`c_id`,'".$class_ids."')")->select();
        //echo "<pre/>";print_r($data);die;
        if(empty($data)){
            $this->ajaxReturn(['status'=>-1,'导入学生不能为空']);
        }
        if(empty($data)){
            $this->ajaxReturn(['status'=>-1,'导入学生不能为空']);
        }else{

            $count=0;//错误数量
            $true_num=0;//导入正确的学生数量
            M()->startTrans();//开启事务
            //班级列表
            $class_list=M('class')->where("status=1")->getField('class_name,id');
            $sex_list=['男'=>1,'女'=>2];
            //删除batch_id的数据 重新插入;
            M('student')->where("batch_id=$new_batch_id")->delete();
            //echo "<pre/>";print_r($data);die;
            foreach($data as $key=>$val){
                //准考证号
                //var_dump($val);die;
                $student['batch_id']=$new_batch_id;
                $student['student_name']=$val['student_name'];
                $student['c_id']=$val['c_id'];
                $student['student_num']=$val['student_num'];
                $student['zkz_num']=$val['zkz_num'];
                $student['sex']=$val['sex'];
                $student['id_card']=$val['id_card'];
                $student['mobile']=$val['mobile'];
                $student['status']=$val['status'];
                $student['add_time']=time();
                $a=M('student')->add($student);

                if((empty($a))){
                    $count++;
                }else{
                    $true_num++;
                    //echo 99;echo "<br/>";
                }

            }
            //var_dump($count);die;
            if($count>0){
                M()->rollback();//回滚
                $this->ajaxReturn(['status'=>-3,"true_num"=>0,'msg'=>'系统忙，请重试！']);
            }else{
                M()->commit();//事务提交
                $this->ajaxReturn(['status'=>1,"true_num"=>$true_num,'msg'=>'复制成功！']);

            }

        }

    }


	//导出报名文件 csv格式
	public function csvExport(){
		//从数据库去除要导出的数据的条件 ，再用create_csv($data,$header=null,$filename='simple.csv') 方法导出；

		$batch_id=I("request.batch_id");
		$status=I("request.status",0);
		if(empty($batch_id)||($status<-1 ||$status>5)){
			//$this->ajaxReturn(['status'=>-1,'参数不正确']);
			die('参数不正确');
		}
		$batch_list=M("batch")->field("id,batch_name")->select();
		if($status==0){
			$where='s.status <>0';
		}else{
			$where="s.status=$status";
		}
        //$where =" s.status>=2";
		if($batch_id){
			$where.=" and s.batch_id=$batch_id";
		}
       // $where.=" and s.examinations_num=23 and (s.yz_zwh_num=29 or s.yz_zwh_num=28) ";
		$student_list=M('student')
			//->fetchSql()
			->where($where)
			->join(" s left join __STUDENT_RESULT__ r on s.id=r.student_id")
			->field("s.*,if((SUBSTR(s.id_code,17,1))%2>0,'男','女') sex,FROM_UNIXTIME(s.submit_time,'%Y-%m-%d %H:%i:%s') as submit_time,FROM_UNIXTIME(s.adopt_time,'%Y-%m-%d %H:%i:%s') as adopt_time,
			(CASE  s.status when -1 then '未通过' when 1 then '已报名' when 2 then '已审核' when 3 then '已打印' when 4 then '已导入成绩' when 5 then '发布' end) status_name,
			r.yuwen,r.shuxue,r.yingyu,r.huaxue,r.wuli,r.fujiafen,r.total_points,if(s.hk_type=1,'城市','农村')hk_type")
			->order("s.examinations_num ,s.yz_zwh_num")
			//->limit($page_ajax,$limit_num)
			->select();
        //echo "<pre/>";print_r($student_list);die;

		foreach($student_list as $key=>$val){
			$old_result=M("student_old_result")->where("student_id=".$val['id'])->select();
			foreach($old_result as $key2=>$arr){
				$student_list[$key]['old_yuwen'.($key2+1)]=$arr['yuwen']?$arr['yuwen']:0;
				$student_list[$key]['old_shuxue'.($key2+1)]=$arr['shuxue']?$arr['shuxue']:0;
				$student_list[$key]['old_yingyu'.($key2+1)]=$arr['yingyu']?$arr['yingyu']:0;
				$student_list[$key]['old_wuli'.($key2+1)]=$arr['wuli']?$arr['wuli']:0;
				$student_list[$key]['old_huaxue'.($key2+1)]=$arr['huaxue']?$arr['huaxue']:0;
				$student_list[$key]['old_zhengzhi'.($key2+1)]=$arr['zhengzhi']?$arr['zhengzhi']:0;
				$student_list[$key]['old_lishi'.($key2+1)]=$arr['lishi']?$arr['lishi']:0;
				$student_list[$key]['old_test_grade'.($key2+1)]=$arr['test_grade']?$arr['test_grade']:0;
				$student_list[$key]['old_class_num'.($key2+1)]=$arr['class_num']?$arr['class_num']:0;
				$student_list[$key]['old_rank_num'.($key2+1)]=$arr['rank_num']?$arr['rank_num']:0;

			}
		}
		$student_list_new=[];
		foreach($student_list as $key=>$val){

            $val['prize']=msubstr($val['prize'],0,200);
            //$val['postal_address']='';
            $val['prize']=str_replace(array("\r\n", "\r", "\n","\t"), "", $val['prize']);
            $val['prize']=str_replace(' ', '',  $val['prize']);
            $val['prize']='';


            $val['postal_address']=msubstr($val['postal_address'],0,200);
            //$val['postal_address']='';
            $val['postal_address']=str_replace(array("\r\n", "\r", "\n","\t"), "", $val['postal_address']);
            $val['postal_address']=str_replace(' ', '',  $val['postal_address']);



			$student_list_new[]=$this->numHandle_0($val);
		}

      /* $student_list_new[0]['prize']='';
       $student_list_new[0]['student_name']='lqs';*/
		//echo "<pre/>";print_r($student_list_new);die;
		$header=['姓名','身份证','性别','状态','不通过原因','最后处理时间','年份','准考证号','座位号','毕业学校','所在县区','中考准考证','所在年级学生的数量','所在班级学生的数量','学生获奖情况','学生联系方式',
			'邮政编码','详细通讯录及联系人','政治面貌','民族','户口类型','户口详细地址','家长1姓名','家长1称谓','家长1工作单位','家长1职位','家长1电话','家长2姓名','家长2称谓','家长2工作单位','家长2职位','家长2电话',
			'模拟考语文1','模拟考数学1','模拟考英语1','模拟考物理1','模拟考化学1','模拟考政治1','模拟考历史1','模拟考总分1','模拟考班级排名1','模拟考年级排名1','模拟考语文2','模拟考数学2','模拟考英语2','模拟考物理2','模拟考化学2','模拟考政治2','模拟考历史2','模拟考总分2','模拟考班级排名2','模拟考年级排名2',
			'一中语文','一中数学','一中英语','一中物理','一中化学','一中附加分','一中总分'];
		$filename='./Uploads/Excel/daochu.csv';
		create_csv($student_list_new,$header,$filename);

	}

}