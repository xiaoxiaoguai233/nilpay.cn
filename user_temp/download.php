<?php
include("../includes/common.php");

if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$type = isset($_GET['type'])?trim($_GET['type']):null;
$batch=$_GET['batch'];

function display_type($type){
	if($type==1)
		return '֧����';
	elseif($type==2)
		return '΢��';
	elseif($type==3)
		return 'QQǮ��';
	elseif($type==4)
		return '���п�';
	else
		return 1;
}

$remark = mb_convert_encoding($conf['transfer_desc'], "GB2312", "UTF-8");

if($type == 'alipay'){
	$data='';
	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' and type=1 order by id asc");
	$i=0;
	while($row = $rs->fetch())
	{
		$i++;
		$data.=$i.','.$row['account'].','.mb_convert_encoding($row['username'], "GB2312", "UTF-8").','.$row['realmoney'].','.$remark."\r\n";
	}
	
	$date=date("Ymd");
	$file="֧�������������ļ�ģ��\r\n";
	$file.="��ţ����,�տ֧�����˺ţ����,�տ���������,�������λ��Ԫ��,��ע��ѡ�\r\n";
	$file.=$data;
}else{
	$data='';
	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' order by type asc,id asc");
	$i=0;
	while($row = $rs->fetch())
	{
		$i++;
		$data.=$i.','.display_type($row['type']).','.$row['account'].','.mb_convert_encoding($row['username'], "GB2312", "UTF-8").','.$row['realmoney'].','.$remark."\r\n";
	}
	
	$date=date("Ymd");
	$file="�̻���ˮ��,�տʽ,�տ��˺�,�տ�������,�����Ԫ��,��������\r\n";
	$file.=$data;
}

$file_name='pay_'.$batch.'.csv';
$file_size=strlen($file);
header("Content-Description: File Transfer");
header("Content-Type:application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $file;
?>