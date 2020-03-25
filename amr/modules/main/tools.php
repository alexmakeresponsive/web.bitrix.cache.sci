<?php

use Amr\Main\Classes\Tools\CUtil;


function GetPagePath($page=false, $get_index_page=null)
{
	if (null === $get_index_page)
	{
		if (defined('BX_DISABLE_INDEX_PAGE'))
			$get_index_page = !BX_DISABLE_INDEX_PAGE;
		else
			$get_index_page = true;
	}

	if($page===false && $_SERVER["REQUEST_URI"]<>"")
		$page = $_SERVER["REQUEST_URI"];
	if($page===false)
		$page = $_SERVER["SCRIPT_NAME"];

	$sPath = $page;

	static $terminate = array("?", "#");
	foreach($terminate as $term)
	{
		if(($found = strpos($sPath, $term)) !== false)
		{
			$sPath = substr($sPath, 0, $found);
		}
	}

	//nginx fix
	$sPath = preg_replace("/%+[0-9a-f]{0,1}$/i", "", $sPath);

	$sPath = urldecode($sPath);

	//Decoding UTF uri
	$sPath = CUtil::ConvertToLangCharset($sPath);

	if(substr($sPath, -1, 1) == "/" && $get_index_page)
	{
		$sPath .= GetDirectoryIndex($sPath);
	}

	$sPath = Rel2Abs("/", $sPath);

	static $aSearch = array("<", ">", "\"", "'", "%", "\r", "\n", "\t", "\\");
	static $aReplace = array("&lt;", "&gt;", "&quot;", "&#039;", "%25", "%0d", "%0a", "%09", "%5C");
	$sPath = str_replace($aSearch, $aReplace, $sPath);

	return $sPath;
}

function GetDirPath($sPath)
{
	if(strlen($sPath))
	{
		$p = strrpos($sPath, "/");
		if($p === false)
			return '/';
		else
			return substr($sPath, 0, $p+1);
	}
	else
	{
		return '/';
	}
}

function GetDirectoryIndex($path, $strDirIndex=false)
{
	return GetDirIndex($path, $strDirIndex);
}

function GetDirIndex($path, $strDirIndex=false)
{
	$doc_root = ($_SERVER["DOCUMENT_ROOT"] <> ''? $_SERVER["DOCUMENT_ROOT"] : $GLOBALS["DOCUMENT_ROOT"]);
	$dir = GetDirPath($path);
	$arrDirIndex = GetDirIndexArray($strDirIndex);
	if(is_array($arrDirIndex) && !empty($arrDirIndex))
	{
		foreach($arrDirIndex as $page_index)
			if(file_exists($doc_root.$dir.$page_index))
				return $page_index;
	}
	return "index.php";
}

function GetDirIndexArray($strDirIndex=false)
{
	static $arDefault = array("index.php", "index.html", "index.htm", "index.phtml", "default.html", "index.php3");

	if($strDirIndex === false && !defined("DIRECTORY_INDEX"))
		return $arDefault;

	if($strDirIndex === false && defined("DIRECTORY_INDEX"))
		$strDirIndex = DIRECTORY_INDEX;

	$arrRes = array();
	$arr = explode(" ", $strDirIndex);
	foreach($arr as $page_index)
	{
		$page_index = trim($page_index);
		if($page_index <> '')
			$arrRes[] = $page_index;
	}
	return $arrRes;
}

function Rel2Abs($curdir, $relpath)
{
	if($relpath == "")
		return false;

	if(substr($relpath, 0, 1) == "/" || preg_match("#^[a-z]:/#i", $relpath))
	{
		$res = $relpath;
	}
	else
	{
		if(substr($curdir, 0, 1) != "/" && !preg_match("#^[a-z]:/#i", $curdir))
			$curdir = "/".$curdir;
		if(substr($curdir, -1) != "/")
			$curdir .= "/";
		$res = $curdir.$relpath;
	}

	if(($p = strpos($res, "\0")) !== false)
		$res = substr($res, 0, $p);

	$res = _normalizePath($res);

	if(substr($res, 0, 1) !== "/" && !preg_match("#^[a-z]:/#i", $res))
		$res = "/".$res;

	$res = rtrim($res, ".\\+ ");

	return $res;
}

function _normalizePath($strPath)
{
	$strResult = '';
	if($strPath <> '')
	{
		if(strncasecmp(PHP_OS, "WIN", 3) == 0)
		{
			//slashes doesn't matter for Windows
			$strPath = str_replace("\\", "/", $strPath);
		}

		$arPath = explode('/', $strPath);
		$nPath = count($arPath);
		$pathStack = array();

		for ($i = 0; $i < $nPath; $i++)
		{
			if ($arPath[$i] === ".")
				continue;
			if (($arPath[$i] === '') && ($i !== ($nPath - 1)) && ($i !== 0))
				continue;

			if ($arPath[$i] === "..")
				array_pop($pathStack);
			else
				array_push($pathStack, $arPath[$i]);
		}

		$strResult = implode("/", $pathStack);
	}
	return $strResult;
}

function getLocalPath($path, $baseFolder = "/amr")
{
	$root = rtrim($_SERVER["DOCUMENT_ROOT"], "\\/");

	static $hasLocalDir = null;
	if($hasLocalDir === null)
	{
		$hasLocalDir = is_dir($root."/local");
	}

	if($hasLocalDir && file_exists($root."/local/".$path))
	{
		return "/local/".$path;
	}
	elseif(file_exists($root.$baseFolder."/".$path))
	{
		return $baseFolder."/".$path;
	}
	return false;
}

function htmlspecialcharsEx($str)
{
	static $search =  array("&amp;",     "&lt;",     "&gt;",     "&quot;",     "&#34;",     "&#x22;",     "&#39;",     "&#x27;",     "<",    ">",    "\"");
	static $replace = array("&amp;amp;", "&amp;lt;", "&amp;gt;", "&amp;quot;", "&amp;#34;", "&amp;#x22;", "&amp;#39;", "&amp;#x27;", "&lt;", "&gt;", "&quot;");
	return str_replace($search, $replace, $str);
}

function htmlspecialcharsbx($string, $flags = ENT_COMPAT, $doubleEncode = true)
{
	//function for php 5.4 where default encoding is UTF-8
	return htmlspecialchars($string, $flags, (defined("BX_UTF")? "UTF-8" : "ISO-8859-1"), $doubleEncode);
}

/*
This function emulates php internal function basename
but does not behave badly on broken locale settings
*/
function bx_basename($path, $ext="")
{
	$path = rtrim($path, "\\/");
	if(preg_match("#[^\\\\/]+$#", $path, $match))
		$path = $match[0];

	if($ext)
	{
		$ext_len = strlen($ext);
		if(strlen($path) > $ext_len && substr($path, -$ext_len) == $ext)
			$path = substr($path, 0, -$ext_len);
	}

	return $path;
}



















use Amr\Main\Lib\EventManager;


function RegisterModule($id)
{
	\Bitrix\Main\ModuleManager::registerModule($id);
}

function UnRegisterModule($id)
{
	\Bitrix\Main\ModuleManager::unRegisterModule($id);
}

function AddEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $CALLBACK, $SORT=100, $FULL_PATH = false)
{
	$eventManager = EventManager::getInstance();
	return $eventManager->addEventHandlerCompatible($FROM_MODULE_ID, $MESSAGE_ID, $CALLBACK, $FULL_PATH, $SORT);
}

function RemoveEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $iEventHandlerKey)
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	return $eventManager->removeEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $iEventHandlerKey);
}

function GetModuleEvents($MODULE_ID, $MESSAGE_ID, $bReturnArray = false)
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$arrResult = $eventManager->findEventHandlers($MODULE_ID, $MESSAGE_ID);

	foreach($arrResult as $k => $event)
	{
		$arrResult[$k]['FROM_MODULE_ID'] = $MODULE_ID;
		$arrResult[$k]['MESSAGE_ID'] = $MESSAGE_ID;
	}

	if($bReturnArray)
	{
		return $arrResult;
	}
	else
	{
		$resRS = new CDBResult;
		$resRS->InitFromArray($arrResult);
		return $resRS;
	}
}

/**
 * @param $arEvent
 * @param null $param1
 * @param null $param2
 * @param null $param3
 * @param null $param4
 * @param null $param5
 * @param null $param6
 * @param null $param7
 * @param null $param8
 * @param null $param9
 * @param null $param10
 * @return bool|mixed|null
 *
 * @deprecated
 */
function ExecuteModuleEvent($arEvent, $param1=NULL, $param2=NULL, $param3=NULL, $param4=NULL, $param5=NULL, $param6=NULL, $param7=NULL, $param8=NULL, $param9=NULL, $param10=NULL)
{
	$CNT_PREDEF = 10;
	$r = true;
	if($arEvent["TO_MODULE_ID"] <> '' && $arEvent["TO_MODULE_ID"] <> 'main')
	{
		if(!CModule::IncludeModule($arEvent["TO_MODULE_ID"]))
			return null;
		$r = include_once($_SERVER["DOCUMENT_ROOT"].getLocalPath("modules/".$arEvent["TO_MODULE_ID"]."/include.php"));
	}
	elseif($arEvent["TO_PATH"] <> '' && file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"]))
	{
		$r = include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"]);
	}
	elseif($arEvent["FULL_PATH"]<>"" && file_exists($arEvent["FULL_PATH"]))
	{
		$r = include_once($arEvent["FULL_PATH"]);
	}

	if(($arEvent["TO_CLASS"] == '' || $arEvent["TO_METHOD"] == '') && !is_set($arEvent, "CALLBACK"))
		return $r;

	$args = array();
	if (is_array($arEvent["TO_METHOD_ARG"]) && count($arEvent["TO_METHOD_ARG"]) > 0)
	{
		foreach ($arEvent["TO_METHOD_ARG"] as $v)
			$args[] = $v;
	}

	$nArgs = func_num_args();
	for($i = 1; $i <= $CNT_PREDEF; $i++)
	{
		if($i > $nArgs)
			break;
		$args[] = &${"param".$i};
	}

	for($i = $CNT_PREDEF + 1; $i < $nArgs; $i++)
		$args[] = func_get_arg($i);

	//TODO: �������� �������� �� EventManager::getInstance()->getLastEvent();
	global $BX_MODULE_EVENT_LAST;
	$BX_MODULE_EVENT_LAST = $arEvent;

	if(is_set($arEvent, "CALLBACK"))
	{
		$resmod = call_user_func_array($arEvent["CALLBACK"], $args);
	}
	else
	{
		//php bug: http://bugs.php.net/bug.php?id=47948
		class_exists($arEvent["TO_CLASS"]);
		$resmod = call_user_func_array(array($arEvent["TO_CLASS"], $arEvent["TO_METHOD"]), $args);
	}

	return $resmod;
}

function ExecuteModuleEventEx($arEvent, $arParams = array())
{
	$r = true;

	if(
		isset($arEvent["TO_MODULE_ID"])
		&& $arEvent["TO_MODULE_ID"]<>""
		&& $arEvent["TO_MODULE_ID"]<>"main"
	)
	{
		if(!CModule::IncludeModule($arEvent["TO_MODULE_ID"]))
			return null;
	}
	elseif(
		isset($arEvent["TO_PATH"])
		&& $arEvent["TO_PATH"]<>""
		&& file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"])
	)
	{
		$r = include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT.$arEvent["TO_PATH"]);
	}
	elseif(
		isset($arEvent["FULL_PATH"])
		&& $arEvent["FULL_PATH"]<>""
		&& file_exists($arEvent["FULL_PATH"])
	)
	{
		$r = include_once($arEvent["FULL_PATH"]);
	}

	if(array_key_exists("CALLBACK", $arEvent))
	{
		//TODO: �������� �������� �� EventManager::getInstance()->getLastEvent();
		global $BX_MODULE_EVENT_LAST;
		$BX_MODULE_EVENT_LAST = $arEvent;

		if(isset($arEvent["TO_METHOD_ARG"]) && is_array($arEvent["TO_METHOD_ARG"]) && count($arEvent["TO_METHOD_ARG"]))
			$args = array_merge($arEvent["TO_METHOD_ARG"], $arParams);
		else
			$args = $arParams;

		return call_user_func_array($arEvent["CALLBACK"], $args);
	}
	elseif($arEvent["TO_CLASS"] != "" && $arEvent["TO_METHOD"] != "")
	{
		//TODO: �������� �������� �� EventManager::getInstance()->getLastEvent();
		global $BX_MODULE_EVENT_LAST;
		$BX_MODULE_EVENT_LAST = $arEvent;

		if(is_array($arEvent["TO_METHOD_ARG"]) && count($arEvent["TO_METHOD_ARG"]))
			$args = array_merge($arEvent["TO_METHOD_ARG"], $arParams);
		else
			$args = $arParams;

		//php bug: http://bugs.php.net/bug.php?id=47948
		class_exists($arEvent["TO_CLASS"]);
		return call_user_func_array(array($arEvent["TO_CLASS"], $arEvent["TO_METHOD"]), $args);
	}
	else
	{
		return $r;
	}
}

function UnRegisterModuleDependences($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS="", $TO_METHOD="", $TO_PATH="", $TO_METHOD_ARG = array())
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->unRegisterEventHandler($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS, $TO_METHOD, $TO_PATH, $TO_METHOD_ARG);
}

function RegisterModuleDependences($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS="", $TO_METHOD="", $SORT=100, $TO_PATH="", $TO_METHOD_ARG = array())
{
	$eventManager = \Bitrix\Main\EventManager::getInstance();
	$eventManager->registerEventHandlerCompatible($FROM_MODULE_ID, $MESSAGE_ID, $TO_MODULE_ID, $TO_CLASS, $TO_METHOD, $SORT, $TO_PATH, $TO_METHOD_ARG);
}

function IsModuleInstalled($module_id)
{
	return \Bitrix\Main\ModuleManager::isModuleInstalled($module_id);
}

function GetModuleID($str)
{
	$arr = explode("/",$str);
	$i = array_search("modules",$arr);
	return $arr[$i+1];
}

/**
 * Returns TRUE if version1 >= version2
 * version1 = "XX.XX.XX"
 * version2 = "XX.XX.XX"
 */
function CheckVersion($version1, $version2)
{
	$arr1 = explode(".",$version1);
	$arr2 = explode(".",$version2);
	if (intval($arr2[0])>intval($arr1[0])) return false;
	elseif (intval($arr2[0])<intval($arr1[0])) return true;
	else
	{
		if (intval($arr2[1])>intval($arr1[1])) return false;
		elseif (intval($arr2[1])<intval($arr1[1])) return true;
		else
		{
			if (intval($arr2[2])>intval($arr1[2])) return false;
			elseif (intval($arr2[2])<intval($arr1[2])) return true;
			else return true;
		}
	}
}
