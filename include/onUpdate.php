<?php
function xoops_module_update_tad_uploader(&$module, $old_version) {
    GLOBAL $xoopsDB;
    
		if(chk_chk1()) go_update1();
		if(!chk_chk2()) go_update2();
		if(!chk_chk3()) go_update3();
		if(!chk_chk4()) go_update4();
		if(!chk_chk5()) go_update5();
	 mk_dir(XOOPS_ROOT_PATH."/uploads/tad_uploader_batch");

    return true;
}


//�ˬd�O�_�ݭn��s
function chk_chk1(){
	global $xoopsDB;
	$sql="select count(*) from ".$xoopsDB->prefix("tad_uploader_dl_log");
	$result=$xoopsDB->query($sql);
	if(empty($result)) return true;
	return false;
}

//�����s�� 1.2
function go_update1(){
	global $xoopsDB;
	$sql="CREATE TABLE IF NOT EXISTS ".$xoopsDB->prefix("tad_uploader_dl_log")." (
  `log_sn` smallint(5) unsigned NOT NULL auto_increment,
  `uid` smallint(5) unsigned NOT NULL,
  `dl_time` datetime NOT NULL,
  `from_ip` varchar(15) NOT NULL,
  `cfsn` smallint(5) unsigned NOT NULL,
  PRIMARY KEY  (`log_sn`)
	)";
	$xoopsDB->queryF($sql) or redirect_header(XOOPS_URL,3,  mysql_error());

	return true;
}


//�s�W�s�����
function chk_chk2(){
	global $xoopsDB;
	$sql="select count(`file_url`) from ".$xoopsDB->prefix("tad_uploader_file");
	$result=$xoopsDB->query($sql);
	if(empty($result)) return false;
	return true;
}

function go_update2(){
	global $xoopsDB;
	$sql="ALTER TABLE ".$xoopsDB->prefix("tad_uploader_file")." ADD `file_url` varchar(255) NOT NULL  default '' after `up_date`";
	$xoopsDB->queryF($sql) or redirect_header(XOOPS_URL."/modules/system/admin.php?fct=modulesadmin",30,  mysql_error());
	return true;
}


//�s�W�Ƨ����
function chk_chk3(){
	global $xoopsDB;
	$sql="select count(`cf_sort`) from ".$xoopsDB->prefix("tad_uploader_file");
	$result=$xoopsDB->query($sql);
	if(empty($result)) return false;
	return true;
}

function go_update3(){
	global $xoopsDB;
	$sql="ALTER TABLE ".$xoopsDB->prefix("tad_uploader_file")." ADD `cf_sort` smallint(5) unsigned NOT NULL default '0'";
	$xoopsDB->queryF($sql) or redirect_header(XOOPS_URL."/modules/system/admin.php?fct=modulesadmin",30,  mysql_error());
	return true;
}



function chk_chk4(){
	if(is_dir(XOOPS_ROOT_PATH."/uploads/tad_uploader_batch")){
		return true;
	}
	return false;
}

function go_update4(){
  global $xoopsDB;
  $dir=XOOPS_ROOT_PATH."/uploads/tad_uploader";
  mk_dir($dir."_batch");

	$sql = "select cfsn,uid,cf_name from ".$xoopsDB->prefix("tad_uploader_file")." where file_url=''";
	$result = $xoopsDB->query($sql) or die($sql);

	while(list($cfsn,$uid,$cf_name)=$xoopsDB->fetchRow($result)){
		//�h���v����
		if(!is_dir($dir."/user_{$uid}")){
		  mk_dir($dir."/user_{$uid}");
  	}
  	rename_win("{$dir}/{$cfsn}_{$cf_name}","{$dir}/user_{$uid}/{$cfsn}_{$cf_name}");
	}
	return true;
}


//�ˬd�O�_�����ؿ����ɮ�
function chk_chk5(){
	global $xoopsDB;
	$sql="select count(*) from ".$xoopsDB->prefix("tad_uploader_file")." where cat_sn=0";
	$result=$xoopsDB->query($sql);
	if(empty($result)) return false;
	return true;
}

function go_update5(){
	global $xoopsDB,$xoopsUser;
	$uid=$xoopsUser->uid();
	$sql="insert into ".$xoopsDB->prefix("tad_uploader")." (`cat_title`, `cat_desc`, `cat_enable`, `uid`, `of_cat_sn`, `cat_share`, `cat_sort`, `cat_count`) VALUES ('"._MD_TADUP_ROOT."' , '' , '1' , '$uid' , 0 , 1 , 0 , 0)";
	$xoopsDB->queryF($sql);
 	$cat_sn=$xoopsDB->getInsertId();
  	
	$sql="update ".$xoopsDB->prefix("tad_uploader_file")." set `cat_sn`='$cat_sn' where `cat_sn`= 0 ";
	$xoopsDB->queryF($sql);
	return true;
}



//�إߥؿ�
function mk_dir($dir=""){
    //�Y�L�ؿ��W�٨q�Xĵ�i�T��
    if(empty($dir))return;
    //�Y�ؿ����s�b���ܫإߥؿ�
    if (!is_dir($dir)) {
        umask(000);
        //�Y�إߥ��Ѩq�Xĵ�i�T��
        mkdir($dir, 0777);
    }
}

//�����ؿ�
function full_copy( $source="", $target=""){
	if ( is_dir( $source ) ){
		@mkdir( $target );
		$d = dir( $source );
		while ( FALSE !== ( $entry = $d->read() ) ){
			if ( $entry == '.' || $entry == '..' ){
				continue;
			}

			$Entry = $source . '/' . $entry;
			if ( is_dir( $Entry ) )	{
				full_copy( $Entry, $target . '/' . $entry );
				continue;
			}
			copy( $Entry, $target . '/' . $entry );
		}
		$d->close();
	}else{
		copy( $source, $target );
	}
}


function rename_win($oldfile,$newfile) {
   if (!rename($oldfile,$newfile)) {
      if (copy ($oldfile,$newfile)) {
         unlink($oldfile);
         return TRUE;
      }
      return FALSE;
   }
   return TRUE;
}


//���Y��
function thumbnail($filename="",$thumb_name="",$type="image/jpeg",$width="120"){

	ini_set('memory_limit', '50M');
	// Get new sizes
	list($old_width, $old_height) = getimagesize($filename);

	$percent=($old_width>$old_height)?round($width/$old_width,2):round($width/$old_height,2);

	$newwidth = ($old_width>$old_height)?$width:$old_width * $percent;
	$newheight = ($old_width>$old_height)?$old_height * $percent:$width;

	// Load
	$thumb = imagecreatetruecolor($newwidth, $newheight);
	if($type=="image/jpeg" or $type=="image/jpg" or $type=="image/pjpg" or $type=="image/pjpeg"){
		$source = imagecreatefromjpeg($filename);
		$type="image/jpeg";
	}elseif($type=="image/png"){
		$source = imagecreatefrompng($filename);
		$type="image/png";
	}elseif($type=="image/gif"){
		$source = imagecreatefromgif($filename);
		$type="image/gif";
	}

	// Resize
	imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $old_width, $old_height);

  header("Content-type: image/png");
	imagepng($thumb,$thumb_name);

	return;
	exit;
}
?>