<?php
defined('IN_TS') or die('Access Denied.');

//用户是否登录
$userid = aac('user')->isLogin();

//普通不用不允许编辑内容
if($TS_SITE['isallowedit'] && $TS_USER ['isadmin'] == 0) tsNotice('系统不允许用户编辑内容，请联系管理员编辑！');

switch($ts){
	
	//编辑帖子
	case "":
		$topicid = tsIntval($_GET['topicid']);

		if($topicid == 0){
			header("Location: ".SITE_URL);
			exit;
		}
		
		$topicNum = $new['group']->findCount('group_topic',array(
			'topicid'=>$topicid,
		));

		if($topicNum==0){
			header("Location: ".SITE_URL);
			exit;
		}
		
		$strTopic = $new['group']->find('group_topic',array(
			'topicid'=>$topicid,
		));
		
		$strTopic['title'] = tsTitle($strTopic['title']);
		$strTopic['content'] = tsDecode($strTopic['content']);
		
		$strGroup = $new['group']->find('group',array(
			'groupid'=>$strTopic['groupid'],
		));
		
		$strGroupUser = $new['group']->find('group_user',array(
			'userid'=>$userid,
			'groupid'=>$strTopic['groupid'],
		));
		
		//print_r($strGroupUser);exit;

		if($strTopic['userid'] == $userid || $strGroup['userid']==$userid || $TS_USER['isadmin']==1 || $strGroupUser['isadmin']==1){
			$arrGroupType = $new['group']->findAll('group_topic_type',array(
				'groupid'=>$strGroup['groupid'],
			));
			
			//找出TAG
			$arrTags = aac('tag')->getObjTagByObjid('topic', 'topicid', $topicid);
			foreach($arrTags as $key=>$item){
				$arrTag[] = $item['tagname'];
			}
			$strTopic['tag'] = arr2str($arrTag);
			
			$title = '编辑帖子';
			include template("topic_edit");
			
		}else{

			header("Location: ".SITE_URL);
			exit;

		}
		break;
		
	//编辑帖子执行	
	case "do":


        $authcode = strtolower ( $_POST ['authcode'] );

        if ($TS_SITE['isauthcode']) {
            if ($authcode != $_SESSION ['verify']) {
                tsNotice ( "验证码输入有误，请重新输入！" );
            }
        }
		
		$topicid = intval($_POST['topicid']);
		$typeid = intval($_POST['typeid']);
		
		$title = trim($_POST['title']);
		
		//echo br2nl($_POST['content']);exit;
		
		$content = tsClean($_POST['content']);
		$content2 = emptyText($_POST['content']);

        $score = intval($_POST ['score']);#积分

		$iscomment = intval($_POST['iscomment']);
		$iscommentshow = intval($_POST['iscommentshow']);
		
		if($topicid == '' || $title=='' || $content2=='') tsNotice("都不能为空的哦!");

        if($score<0){
            tsNotice ( '积分填写有误！' );
        }

		if($TS_USER['isadmin']==0){
		
			//过滤内容开始
			aac('system')->antiWord($title);
			aac('system')->antiWord($content);
			//过滤内容结束
		
		}
		
		$strTopic = $new['group']->find('group_topic',array(
			'topicid'=>$topicid,
		));
		
		$strGroup = $new['group']->find('group',array(
			'groupid'=>$strTopic['groupid'],
		));
		
		$strGroupUser = $new['group']->find('group_user',array(
			'userid'=>$userid,
			'groupid'=>$strTopic['groupid'],
		));
		
		if($strTopic['userid']==$userid || $strGroup['userid']==$userid || $TS_USER['isadmin']==1 || $strGroupUser['isadmin']==1){

			$new['group']->update('group_topic',array(
				'topicid'=>$topicid,
			),array(
				'typeid' => $typeid,
				'title'=>$title,
				'content'=>$content,
				'score'=>$score,
				'iscomment' => $iscomment,
				'iscommentshow' => $iscommentshow,
			));
			


			//处理标签
			$tag = trim($_POST['tag']);
			if($tag){
				aac('tag')->delIndextag('topic','topicid',$topicid);
				aac('tag') -> addTag('topic', 'topicid', $topicid, $tag); 
			}


			#用户记录
			aac('pubs')->addLogs('group_topic','topicid',$topicid,$userid,$title,$content,1);

			
			header("Location: ".tsUrl('group','topic',array('id'=>$topicid)));
			
		}else{
			header("Location: ".SITE_URL);
			exit;
		}
		break;

}