<?php
  /**************************************************************************\
  * eGroupWare API - Commononly used functions                               *
  * This file written by Dan Kuykendall <seek3r@phpgroupware.org>            *
  * and Joseph Engo <jengo@phpgroupware.org>                                 *
  * and Mark Peters <skeeter@phpgroupware.org>                               *
  * and Lars Kneschke <lkneschke@linux-at-work.de>                           *
  * Functions commonly used by eGroupWare developers                         *
  * Copyright (C) 2000, 2001 Dan Kuykendall                                  *
  * Copyright (C) 2003 Lars Kneschke                                         *
  * -------------------------------------------------------------------------*
  * This library is part of the eGroupWare API                               *
  * http://www.egroupware.org                                                *
  * ------------------------------------------------------------------------ *
  * This library is free software; you can redistribute it and/or modify it  *
  * under the terms of the GNU Lesser General Public License as published by *
  * the Free Software Foundation; either version 2.1 of the License,         *
  * or any later version.                                                    *
  * This library is distributed in the hope that it will be useful, but      *
  * WITHOUT ANY WARRANTY; without even the implied warranty of               *
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     *
  * See the GNU Lesser General Public License for more details.              *
  * You should have received a copy of the GNU Lesser General Public License *
  * along with this library; if not, write to the Free Software Foundation,  *
  * Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            *
  \**************************************************************************/

  /* $Id$ */

	$d1 = strtolower(@substr(EGW_API_INC,0,3));
	$d2 = strtolower(@substr(EGW_SERVER_ROOT,0,3));
	$d3 = strtolower(@substr(EGW_APP_INC,0,3));
	if($d1 == 'htt' || $d1 == 'ftp' || $d2 == 'htt' || $d2 == 'ftp' || $d3 == 'htt' || $d3 == 'ftp')
	{
		echo 'Failed attempt to break in via an old Security Hole!<br>'."\n";
		exit;
	}
	unset($d1);unset($d2);unset($d3);

	/*!
	@class common
	@abstract common class that contains commonly used functions
	*/
	class common
	{
		var $debug_info; // An array with debugging info from the API
		var $found_files;

		/*!
		@function cmp_version
		@abstract Compares two Version strings and return 1 if str2 is newest (bigger version number) than str1
		@discussion This function checks for major version only.
		@param $str1
		@param $str2
		*/
		function cmp_version($str1,$str2,$debug=False)
		{
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)",$str1,$regs);
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)",$str2,$regs2);
			if($debug) { echo "<br>$regs[0] - $regs2[0]"; }

			for($i=1;$i<5;$i++)
			{
				if($debug) { echo "<br>$i: $regs[$i] - $regs2[$i]"; }
				if($regs2[$i] == $regs[$i])
				{
					continue;
				}
				if($regs2[$i] > $regs[$i])
				{
					return 1;
				}
				elseif($regs2[$i] < $regs[$i])
				{
					return 0;
				}
			}
		}

		/*!
		@function cmp_version_long
		@abstract Compares two Version strings and return 1 if str2 is newest (bigger version number) than str1
		@discussion This function checks all fields. cmp_version() checks release version only.
		@param $str1
		@param $str2
		*/
		function cmp_version_long($str1,$str2,$debug=False)
		{
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)\.([0-9]*)",$str1,$regs);
			ereg("([0-9]+)\.([0-9]+)\.([0-9]+)[a-zA-Z]*([0-9]*)\.([0-9]*)",$str2,$regs2);
			if($debug) { echo "<br>$regs[0] - $regs2[0]"; }

			for($i=1;$i<6;$i++)
			{
				if($debug) { echo "<br>$i: $regs[$i] - $regs2[$i]"; }

				if($regs2[$i] == $regs[$i])
				{
					if($debug) { echo ' are equal...'; }
					continue;
				}
				if($regs2[$i] > $regs[$i])
				{
					if($debug) { echo ', and a > b'; }
					return 1;
				}
				elseif($regs2[$i] < $regs[$i])
				{
					if($debug) { echo ', and a < b'; }
					return 0;
				}
			}
			if($debug) { echo ' - all equal.'; }
		}

		// Convert an array into the format needed for the access column.
		/*!
		@function array_to_string
		@abstract Convert an array into the format needed for the access column
		@param $access
		@param $array
		*/
		function array_to_string($access,$array)
		{
			$this->debug_info[] = 'array_to_string() is a depreciated function - use ACL instead';
			$s = '';
			if ($access == 'group' || $access == 'public' || $access == 'none')
			{
				if (count($array))
				{
					while ($t = each($array))
					{
						$s .= ',' . $t[1];
					}
					$s .= ',';
				}
				if (! count($array) && $access == 'none')
				{
					$s = '';
				}
			}
			return $s;
		}
		
		/**
		* genrate a unique id, which can be used for syncronisation
		*
		* @param string $_appName the appname
		* @param string $_eventID the id of the content
		* @return string the unique id
		*/
		function generate_uid($_appName, $_eventID)
		{
			if(empty($_appName) || empty($_eventID)) return false;
			
			$suffix = $GLOBALS['egw_info']['server']['hostname'] ? $GLOBALS['egw_info']['server']['hostname'] : 'local';
			$prefix = $_appName.'-'.$_eventID.'-'.$GLOBALS['egw_info']['server']['install_id'];
			
			return $prefix;
		}
		
		/**
		* get the local content id from a global UID
		*
		* @param sting $_globalUid the global UID
		* @return int local egw content id
		*/
		function get_egwId($_globalUid)
		{
			if(empty($_globalUid)) return false;
			
			$globalUidParts = explode('-',$_globalUid);
			
			return $globalUidParts[1];
		}

		// This is used for searching the access fields
		/*!
		@function sql_search
		@abstract this function is used for searching the access fields
		@param $table
		@param $owner
		*/
		function sql_search($table,$owner=0)
		{
			$this->debug_info[] = 'sql_search() is a deprecated function - use ACL instead';
			$s = '';
			if (!$owner)
			{
				$owner = $GLOBALS['egw_info']['user']['account_id'];
			}
			$groups = $GLOBALS['egw']->accounts->membership((int)$owner);
			if(@is_array($groups))
			{
				while ($group = each($groups))
				{
					$s .= " OR $table LIKE '%," . $group[2] . ",%'";
				}
			}
			return $s;
		}

		// return a array of installed languages
		/*!
		@function getInstalledLanguages
		@abstract return an array of installed languages
		@result $installedLanguages; an array containing the installed languages
		*/
		function getInstalledLanguages()
		{
			$GLOBALS['egw']->db->query('SELECT DISTINCT lang FROM phpgw_lang');
			while (@$GLOBALS['egw']->db->next_record())
			{
				$installedLanguages[$GLOBALS['egw']->db->f('lang')] = $GLOBALS['egw']->db->f('lang');
			}

			return $installedLanguages;
		}

		// return the preferred language of the users
		// it's using HTTP_ACCEPT_LANGUAGE (send from the users browser)
		// and ...(to find out which languages are installed)
		/*!
		@function getPreferredLanguage
		@abstract return the preferred langugae of the users
		@discussion it uses HTTP_ACCEPT_LANGUAGE (from the users browser) <br>
		and .... to find out which languages are installed
		*/
		function getPreferredLanguage()
		{
			// create a array of languages the user is accepting
			$userLanguages = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$supportedLanguages = $this->getInstalledLanguages();

			// find usersupported language
//			while (list($key,$value) = each($userLanguages))
			foreach($userLanguages as $key => $value)
			{
				// remove everything behind '-' example: de-de
				$value = trim($value);
				$pieces = explode('-', $value);
				$value = $pieces[0];
				# print 'current lang $value<br>';
				if ($supportedLanguages[$value])
				{
					$retValue=$value;
					break;
				}
			}

			// no usersupported language found -> return english
			if (empty($retValue))
			{
				$retValue='en';
			}

			return $retValue;
		}

		/*!
		@function ldap_addslashes
		@abstract escapes a string for use in searchfilters meant for ldap_search.
		Escaped Characters are: '*', '(', ')', ' ', '\', NUL
		It's actually a PHP-Bug, that we have to escape space.
		For all other Characters, refer to RFC2254.
		@param $string either a string to be escaped, or an array of values to be escaped
		*/
		function ldap_addslashes($string='')
		{
			return str_replace(array('\\','*','(',')','\0',' '),array('\\\\','\*','\(','\)','\\0','\20'),$string);
		}

		// connect to the ldap server and return a handle
		/*!
		@function ldapConnect
		@abstract connect to the ldap server and return a handle
		@param $host ldap host
		@param $dn ldap_root_dn
		@param $passwd ldap_root_pw
		*/
		function ldapConnect($host='', $dn='', $passwd='')
		{
			if(!function_exists('ldap_connect'))
			{
				/* log does not exist in setup(, yet) */
				if(is_object($GLOBALS['egw']->log))
				{
					$GLOBALS['egw']->log->message('F-Abort, LDAP support unavailable');
					$GLOBALS['egw']->log->commit();
				}

				printf('<b>Error: LDAP support unavailable</b><br>',$host);
				return False;
			}

			if(!$host)
			{
				$host = $GLOBALS['egw_info']['server']['ldap_host'];
			}

			if(!$dn)
			{
				$dn = $GLOBALS['egw_info']['server']['ldap_root_dn'];
			}

			if(!$passwd)
			{
				$passwd = $GLOBALS['egw_info']['server']['ldap_root_pw'];
			}

			// connect to ldap server
			if(!$ds = ldap_connect($host))
			{
				/* log does not exist in setup(, yet) */
				if(is_object($GLOBALS['egw']->log))
				{
					$GLOBALS['egw']->log->message('F-Abort, Failed connecting to LDAP server');
					$GLOBALS['egw']->log->commit();
				}

				printf("<b>Error: Can't connect to LDAP server %s!</b><br>",$host);
				return False;
			}

			if($GLOBALS['egw_info']['server']['ldap_version3'])
			{
				if(!ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,3))
				{
					$GLOBALS['egw_info']['server']['ldap_version3'] = False;
				}
			}

			// bind as admin
			if(!ldap_bind($ds,$dn,$passwd))
			{
				if(is_object($GLOBALS['egw']->log))
				{
					$GLOBALS['egw']->log->message('F-Abort, Failed binding to LDAP server');
					$GLOBALS['egw']->log->commit();
				}

				printf("<b>Error: Can't bind to LDAP server: %s!</b><br>",$dn);
				return False;
			}

			return $ds;
		}

		/*!
		@function egw_exit
		@abstract function to stop running an app
		@discussion used to stop running an app in the middle of execution <br>
		There may need to be some cleanup before hand
		@param $call_footer boolean value to if true then call footer else exit
		*/
		function egw_exit($call_footer = False)
		{
			if (!defined('EGW_EXIT'))
			{
				define('EGW_EXIT',True);

				if ($call_footer)
				{
					$this->egw_footer();
				}
			}
			exit;
		}

		function egw_final()
		{
			if (!defined('EGW_FINAL'))
			{
				define('EGW_FINAL',True);

				if (is_object($GLOBALS['egw']->accounts))
				{
					$GLOBALS['egw']->accounts->save_session_cache();
				}
				// call the asyncservice check_run function if it is not explicitly set to cron-only
				//
				if (!$GLOBALS['egw_info']['server']['asyncservice'])	// is default
				{
					ExecMethod('phpgwapi.asyncservice.check_run','fallback');
				}
				/* Clean up mcrypt */
				if (@is_object($GLOBALS['egw']->crypto))
				{
					$GLOBALS['egw']->crypto->cleanup();
					unset($GLOBALS['egw']->crypto);
				}
				$GLOBALS['egw']->db->disconnect();
			}
		}

		/*!
		@function randomstring
		@abstract return a random string of size $size
		@param $size int-size of random string to return
		*/
		function randomstring($size)
		{
			$s = '';
			srand((double)microtime()*1000000);
			$random_char = array(
				'0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f',
				'g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v',
				'w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L',
				'M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
			);

			for ($i=0; $i<$size; $i++)
			{
				$s .= $random_char[rand(1,61)];
			}
			return $s;
		}

		// Look at the note towards the top of this file (jengo)
		function filesystem_separator()
		{
			return filesystem_separator();
		}

		/*!
		@function error_list
		@abstract This is used for reporting errors in a nice format.
		@param $error - array of errors
		*/
		function error_list($errors,$text='Error')
		{
			if (! is_array($errors))
			{
				return False;
			}

			$html_error = '<table border="0" width="100%"><tr><td align="right"><b>' . lang($text)
				. '</b>: </td><td align="left">' . $errors[0] . '</td></tr>';
			for ($i=1; $i<count($errors); $i++)
			{
				$html_error .= '<tr><td>&nbsp;</td><td align="left">' . $errors[$i] . '</td></tr>';
			}
			return $html_error . '</table>';
		}

		/*!
		@function check_owner
		@abstract none yet
		@param $record ?
		@param $link ?
		@param $label ?
		@param $extravars
		*/
		// This is a depreciated function - use ACL instead (jengo)
		function check_owner($record,$link,$label,$extravars = '')
		{
			$this->debug_info[] = 'check_owner() is a depreciated function - use ACL instead';
			/*
			$s = '<a href="' . $GLOBALS['egw']->link($link,$extravars) . '"> ' . lang($label) . ' </a>';
			if (ereg('^[0-9]+$',$record))
			{
				if ($record != $GLOBALS['egw_info']['user']['account_id'])
				{
					$s = '&nbsp;';
				}
			}
			else
			{
				if ($record != $GLOBALS['egw_info']['user']['userid'])
				{
					$s = '&nbsp';
				}
			}

			return $s;
			*/
		}

		/*!
		@function display_fullname
		@abstract return the fullname of a user
		@param $lid account loginid
		@param $firstname firstname
		@param $lastname lastname
		*/
		function display_fullname($lid = '', $firstname = '', $lastname = '')
		{
			if (! $lid && ! $firstname && ! $lastname)
			{
				$lid       = $GLOBALS['egw_info']['user']['account_lid'];
				$firstname = $GLOBALS['egw_info']['user']['firstname'];
				$lastname  = $GLOBALS['egw_info']['user']['lastname'];
			}

			$display = $GLOBALS['egw_info']['user']['preferences']['common']['account_display'];

			if ($firstname && $lastname)
			{
				$delimiter = ', ';
			}
			else
			{
				$delimiter = '';
			}
			
			$name = '';
			switch($display)
			{
				case 'firstname':
					$name = $firstname . ' ' . $lastname;
					break;
				case 'lastname':
					$name = $lastname . $delimiter . $firstname;
					break;
				case 'username':
					$name = $lid;
					break;
				case 'firstall':
					$name = $firstname . ' ' . $lastname . ' ['.$lid.']';
					break;
				case 'lastall':
					$name = $lastname . $delimiter . $firstname . ' ['.$lid.']';
					break;
				case 'all':
					/* fall through */
				default:
					$name = '['.$lid.'] ' . $firstname . ' ' . $lastname;
			}
			return $name;
		}

		/*!
		@function grab_owner_name
		@abstract grab the owner name
		@param $id account id
		*/
		function grab_owner_name($accountid = '')
		{
			$GLOBALS['egw']->accounts->get_account_name($accountid,$lid,$fname,$lname);
			return $this->display_fullname($lid,$fname,$lname);
		}

		/*!
		@function create_tabs
		@abstract create tabs
		@param $tabs ?
		@param $selected ?
		@param $fontsize optional
		*/
		function create_tabs($tabs, $selected, $fontsize = '')
		{
			$output_text = '<table border="0" cellspacing="0" cellpadding="0"><tr>';

			/* This is a php3 workaround */
			if(EGW_IMAGES_DIR == 'EGW_IMAGES_DIR')
			{
				$ir = ExecMethod('phpgwapi.phpgw.common.get_image_path', 'phpgwapi');
			}
			else
			{
				$ir = EGW_IMAGES_DIR;
			}

			if ($fontsize)
			{
				$fs  = '<font size="' . $fontsize . '">';
				$fse = '</font>';
			}

			$i = 1;
			while ($tab = each($tabs))
			{
				if ($tab[0] == $selected)
				{
					if ($i == 1)
					{
						$output_text .= '<td align="right"><img src="' . $ir . '/tabs-start1.gif"></td>';
					}

					$output_text .= '<td align="left" background="' . $ir . '/tabs-bg1.gif">&nbsp;<b><a href="'
						. $tab[1]['link'] . '" class="tablink" '.$tab[1]['target'].'>' . $fs . $tab[1]['label']
						. $fse . '</a></b>&nbsp;</td>';
					if ($i == count($tabs))
					{
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-end1.gif"></td>';
					}
					else
					{
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepr.gif"></td>';
					}
				}
				else
				{
					if ($i == 1)
					{
						$output_text .= '<td align="right"><img src="' . $ir . '/tabs-start0.gif"></td>';
					}
					$output_text .= '<td align="left" background="' . $ir . '/tabs-bg0.gif">&nbsp;<b><a href="'
						. $tab[1]['link'] . '" class="tablink" '.$tab[1]['target'].'>' . $fs . $tab[1]['label'] . $fse
						. '</a></b>&nbsp;</td>';
					if (($i + 1) == $selected)
					{
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepl.gif"></td>';
					}
					elseif ($i == $selected || $i != count($tabs))
					{
						$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepm.gif"></td>';
					}
					elseif ($i == count($tabs))
					{
						if ($i == $selected)
						{
							$output_text .= '<td align="left"><img src="' . $ir . '/tabs-end1.gif"></td>';
						}
						else
						{
							$output_text .= '<td align="left"><img src="' . $ir . '/tabs-end0.gif"></td>';
						}
					}
					else
					{
						if ($i != count($tabs))
						{
							$output_text .= '<td align="left"><img src="' . $ir . '/tabs-sepr.gif"></td>';
						}
					}
				}
				$i++;
				$output_text .= "\n";
			}
			$output_text .= "</table>\n";
			return $output_text;
		}

		/*!
		@function get_app_dir
		@abstract get directory of application
		@discussion $appname can either be passed or derived from $GLOBALS['egw_info']['flags']['currentapp'];
		@param $appname name of application
		*/
		function get_app_dir($appname = '')
		{
			if ($appname == '')
			{
				$appname = $GLOBALS['egw_info']['flags']['currentapp'];
			}
			if ($appname == 'logout' || $appname == 'login')
			{
				$appname = 'phpgwapi';
			}

			$appdir         = EGW_INCLUDE_ROOT . '/'.$appname;
			$appdir_default = EGW_SERVER_ROOT . '/'.$appname;

			if (@is_dir ($appdir))
			{
				return $appdir;
			}
			elseif (@is_dir ($appdir_default))
			{
				return $appdir_default;
			}
			else
			{
				return False;
			}
		}

		/*!
		@function get_inc_dir
		@abstract get inc (include dir) of application
		@discussion $appname can either be passed or derived from $GLOBALS['egw_info']['flags']['currentapp'];
		@param $appname name of application
		*/
		function get_inc_dir($appname = '')
		{
			if (! $appname)
			{
				$appname = $GLOBALS['egw_info']['flags']['currentapp'];
			}
			if ($appname == 'logout' || $appname == 'login' || $appname == 'about')
			{
				$appname = 'phpgwapi';
			}

			$incdir         = EGW_INCLUDE_ROOT . '/' . $appname . '/inc';
			$incdir_default = EGW_SERVER_ROOT . '/' . $appname . '/inc';

			if (@is_dir ($incdir))
			{
				return $incdir;
			}
			elseif (@is_dir ($incdir_default))
			{
				return $incdir_default;
			}
			else
			{
				return False;
			}
		}

		/*!
		@function list_themes
		@abstract list themes available
		@note themes can either be css file like in HEAD (if the template has a css-dir and has css-files in is) \
			or ordinary .14 themes-files
		*/
		function list_themes()
		{
			$tpl_dir = $this->get_tpl_dir('phpgwapi');

			if ($dh = @opendir($tpl_dir . SEP . 'css'))
			{
				while ($file = readdir($dh))
				{
					if (eregi("\.css$", $file) && $file != 'phpgw.css')
					{
						$list[] = substr($file,0,strpos($file,'.'));
					}
				}
			}
			if(!is_array($list))
			{
				$dh = opendir(EGW_SERVER_ROOT . '/phpgwapi/themes');
				while ($file = readdir($dh))
				{
					if (eregi("\.theme$", $file))
					{
						$list[] = substr($file,0,strpos($file,'.'));
					}
				}
			}
			closedir($dh);
			reset ($list);
			return $list;
		}

		/**
		* List available templates
		*
		* @returns array alphabetically sorted list of templates
		*/
		function list_templates()
		{
			$d = dir(EGW_SERVER_ROOT . '/phpgwapi/templates');
			while ($entry=$d->read())
			{
			   if ($entry != '..' && is_file(EGW_SERVER_ROOT . '/phpgwapi/templates/' . $entry .'/setup/setup.inc.php')
				 )
				{
					$list[$entry]['name'] = $entry;
					$f = EGW_SERVER_ROOT . '/phpgwapi/templates/' . $entry . '/setup/setup.inc.php';
					if (file_exists ($f))
					{
						include($f);
						$list[$entry]['title'] = $GLOBALS['egw_info']['template'][$entry]['title'];
					}
					else
					{
						$list[$entry]['title'] = $entry;
					}
				}
			}
			//_debug_array($GLOBALS['egw_info'][template]);
			//die();
			
			$d->close();
			ksort($list);
			return $list;
		}

		/*!
		@function get_tpl_dir
		@abstract get template dir of an application
		@param $appname appication name optional can be derived from $GLOBALS['egw_info']['flags']['currentapp'];
		*/
		function get_tpl_dir($appname = '')
		{
			if (! $appname)
			{
				$appname = $GLOBALS['egw_info']['flags']['currentapp'];
			}
			if ($appname == 'logout' || $appname == 'login')
			{
				$appname = 'phpgwapi';
			}

			if (!isset($GLOBALS['egw_info']['server']['template_set']) && isset($GLOBALS['egw_info']['user']['preferences']['common']['template_set']))
			{
				$GLOBALS['egw_info']['server']['template_set'] = $GLOBALS['egw_info']['user']['preferences']['common']['template_set'];
			}

			// Setting this for display of template choices in user preferences
			if ($GLOBALS['egw_info']['server']['template_set'] == 'user_choice')
			{
				$GLOBALS['egw_info']['server']['usrtplchoice'] = 'user_choice';
			}

			if (($GLOBALS['egw_info']['server']['template_set'] == 'user_choice' ||
				!isset($GLOBALS['egw_info']['server']['template_set'])) &&
				isset($GLOBALS['egw_info']['user']['preferences']['common']['template_set']))
			{
				$GLOBALS['egw_info']['server']['template_set'] = $GLOBALS['egw_info']['user']['preferences']['common']['template_set'];
			}
			elseif ($GLOBALS['egw_info']['server']['template_set'] == 'user_choice' ||
				!isset($GLOBALS['egw_info']['server']['template_set']))
			{
				$GLOBALS['egw_info']['server']['template_set'] = 'default';
			}

			$tpldir         = EGW_SERVER_ROOT . '/' . $appname . '/templates/' . $GLOBALS['egw_info']['server']['template_set'];
			$tpldir_default = EGW_SERVER_ROOT . '/' . $appname . '/templates/default';

			if (@is_dir($tpldir))
			{
				return $tpldir;
			}
			elseif (@is_dir($tpldir_default))
			{
				return $tpldir_default;
			}
			else
			{
				return False;
			}
		}

		/*!
		@function is_image_dir
		@abstract checks if image_dir exists and has more than just a navbar-icon
		@note this is just a workaround for idots, better to use find_image, which has a fallback \
			on a per image basis to the default dir
		*/
		function is_image_dir($dir)
		{
			if (!@is_dir($dir))
			{
				return False;
			}
			if ($d = opendir($dir))
			{
				while ($f = readdir($d))
				{
					$ext = strtolower(strrchr($f,'.'));
					if (($ext == '.gif' || $ext == '.png') && strstr($f,'navbar') === False)
					{
						return True;
					}
				}
			}
			return False;
		}

		/*!
		@function get_image_dir
		@abstract get image dir of an application
		@param $appname application name optional can be derived from $GLOBALS['egw_info']['flags']['currentapp'];
		*/
		function get_image_dir($appname = '')
		{
			if ($appname == '')
			{
				$appname = $GLOBALS['egw_info']['flags']['currentapp'];
			}
			if (empty($GLOBALS['egw_info']['server']['template_set']))
			{
				$GLOBALS['egw_info']['server']['template_set'] = 'default';
			}

			$imagedir            = EGW_SERVER_ROOT . '/' . $appname . '/templates/'
				. $GLOBALS['egw_info']['server']['template_set'] . '/images';
			$imagedir_default    = EGW_SERVER_ROOT . '/' . $appname . '/templates/default/images';
			$imagedir_olddefault = EGW_SERVER_ROOT . '/' . $appname . '/images';

			if ($this->is_image_dir ($imagedir))
			{
				return $imagedir;
			}
			elseif ($this->is_image_dir ($imagedir_default))
			{
				return $imagedir_default;
			}
			elseif ($this->is_image_dir ($imagedir_olddefault))
			{
				return $imagedir_olddefault;
			}
			else
			{
				return False;
			}
		}

		/*!
		@function get_image_path
		@abstract get image path of an application
		@param $appname appication name optional can be derived from $GLOBALS['egw_info']['flags']['currentapp'];
		*/
		function get_image_path($appname = '')
		{
			if ($appname == '')
			{
				$appname = $GLOBALS['egw_info']['flags']['currentapp'];
			}

			if (empty($GLOBALS['egw_info']['server']['template_set']))
			{
				$GLOBALS['egw_info']['server']['template_set'] = 'default';
			}

			$imagedir            = EGW_SERVER_ROOT . '/'.$appname.'/templates/'.$GLOBALS['egw_info']['server']['template_set'].'/images';
			$imagedir_default    = EGW_SERVER_ROOT . '/'.$appname.'/templates/default/images';
			$imagedir_olddefault = EGW_SERVER_ROOT . '/'.$appname.'/images';

			if ($this->is_image_dir ($imagedir))
			{
				return $GLOBALS['egw_info']['server']['webserver_url'].'/'.$appname.'/templates/'.$GLOBALS['egw_info']['server']['template_set'].'/images';
			}
			elseif ($this->is_image_dir ($imagedir_default))
			{
				return $GLOBALS['egw_info']['server']['webserver_url'].'/'.$appname.'/templates/default/images';
			}
			elseif ($this->is_image_dir ($imagedir_olddefault))
			{
				return $GLOBALS['egw_info']['server']['webserver_url'].'/'.$appname.'/images';
			}
			else
			{
				return False;
			}
		}

		function find_image($appname,$image)
		{
			$imagedir = '/'.$appname.'/templates/'.$GLOBALS['egw_info']['user']['preferences']['common']['template_set'].'/images';
			
			if (!@is_array($this->found_files[$appname]))
			{
				$imagedir_olddefault = '/'.$appname.'/images';
				$imagedir_default    = '/'.$appname.'/templates/default/images';
				
				if (@is_dir(EGW_INCLUDE_ROOT.$imagedir_olddefault))
				{
					$d = dir(EGW_INCLUDE_ROOT.$imagedir_olddefault);
					while (false != ($entry = $d->read()))
					{
						if ($entry != '.' && $entry != '..')
						{
							$this->found_files[$appname][$entry] = $imagedir_olddefault;
						}
					}
					$d->close();
				}

				if (@is_dir(EGW_INCLUDE_ROOT.$imagedir_default))
				{
					$d = dir(EGW_INCLUDE_ROOT.$imagedir_default);
					while (false != ($entry = $d->read()))
					{
						if ($entry != '.' && $entry != '..')
						{
							$this->found_files[$appname][$entry] = $imagedir_default;
						}
					}
					$d->close();
				}

				if (@is_dir(EGW_INCLUDE_ROOT.$imagedir))
				{
					$d = dir(EGW_INCLUDE_ROOT.$imagedir);
					while (false != ($entry = $d->read()))
					{
						if ($entry != '.' && $entry != '..')
						{
							$this->found_files[$appname][$entry] = $imagedir;
						}
					}
					$d->close();
				}
			}
			
			if (!$GLOBALS['egw_info']['server']['image_type'])
			{
				// priority: GIF->JPG->PNG
				$img_type=array('.gif','.jpg','.png');
			}
			else
			{
				// priority: : PNG->JPG->GIF
				$img_type=array('.png','.jpg','.gif');
			}

			// first look in the selected template dir
			if(@$this->found_files[$appname][$image.$img_type[0]]==$imagedir)
			{
				$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$image.$img_type[0]].'/'.$image.$img_type[0];
			}
			elseif(@$this->found_files[$appname][$image.$img_type[1]]==$imagedir)
			{
				$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$image.$img_type[1]].'/'.$image.$img_type[1];
			}
			elseif(@$this->found_files[$appname][$image.$img_type[2]]==$imagedir)
			{
				$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$image.$img_type[2]].'/'.$image.$img_type[2];
			}
			// then look everywhere else
			elseif(isset($this->found_files[$appname][$image.$img_type[0]]))
			{
				$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$image.$img_type[0]].'/'.$image.$img_type[0];
			}
			elseif(isset($this->found_files[$appname][$image.$img_type[1]]))
			{
				$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$image.$img_type[1]].'/'.$image.$img_type[1];
			}
			elseif(isset($this->found_files[$appname][$image.$img_type[2]]))
			{
				$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$image.$img_type[2]].'/'.$image.$img_type[2];
			}
			elseif(isset($this->found_files[$appname][$image]))
			{
				$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$image].'/'.$image;
			}
			else
			{
				// searching the image in the api-dirs
				if (!isset($this->found_files['phpgwapi']))
				{
					$this->find_image('phpgwapi','');
				}

				if(isset($this->found_files['phpgwapi'][$image.$img_type[0]]))
				{
					$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image.$img_type[0]].'/'.$image.$img_type[0];
				}
				elseif(isset($this->found_files['phpgwapi'][$image.$img_type[1]]))
				{
					$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image.$img_type[1]].'/'.$image.$img_type[1];
				}
				elseif(isset($this->found_files['phpgwapi'][$image.$img_type[2]]))
				{
					$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image.$img_type[2]].'/'.$image.$img_type[2];
				}
				elseif(isset($this->found_files['phpgwapi'][$image]))
				{
					$imgfile = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files['phpgwapi'][$image].'/'.$image;
				}
				else
				{
					$imgfile = '';
				}
			}
			return $imgfile;
		}

		function image($appname,$image='',$ext='',$use_lang=True)
		{
			if (!is_array($image))
			{
				if (empty($image))
				{
					return '';
				}
				$image = array($image);
			}
			if ($use_lang)
			{
				while (list(,$img) = each($image))
				{
					$lang_images[] = $img . '_' . $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
					$lang_images[] = $img;
				}
				$image = $lang_images;
			}
			while (empty($image_found) && list(,$img) = each($image))
			{
				if(isset($this->found_files[$appname][$img.$ext]))
				{
					$image_found = $GLOBALS['egw_info']['server']['webserver_url'].$this->found_files[$appname][$img.$ext].'/'.$img.$ext;
				}
				else
				{
					$image_found = $this->find_image($appname,$img.$ext);
				}
			}
			return $image_found;
		}

		function image_on($appname,$image,$extension='_on')
		{
			$with_extension = $this->image($appname,$image,$extension);
			$without_extension = $this->image($appname,$image);
			if($with_extension != '')
			{
				return $with_extension;
			}
			elseif($without_extension != '')
			{
				return $without_extension;
			}
			else
			{
				return '';
			}
		}

		/*!
		@function navbar
		@abstract none yet
		@discussion *someone wanna add some detail here*
		*/
		function navbar()
		{
			

			list($first) = each($GLOBALS['egw_info']['user']['apps']);
			if(is_array($GLOBALS['egw_info']['user']['apps']['admin']) && $first != 'admin')
			{
				$newarray['admin'] = $GLOBALS['egw_info']['user']['apps']['admin'];
				foreach($GLOBALS['egw_info']['user']['apps'] as $index => $value)
				{
					if($index != 'admin')
					{
						$newarray[$index] = $value;
					}
				}
				$GLOBALS['egw_info']['user']['apps'] = $newarray;
				reset($GLOBALS['egw_info']['user']['apps']);
			}
			unset($index);
			unset($value);
			unset($newarray);

			foreach($GLOBALS['egw_info']['user']['apps'] as $app => $data)
			{
				if (is_long($app))
				{
					continue;
				}

				if ($app == 'preferences' || $GLOBALS['egw_info']['apps'][$app]['status'] != 2 && $GLOBALS['egw_info']['apps'][$app]['status'] != 3)
				{
					$GLOBALS['egw_info']['navbar'][$app]['title'] = $GLOBALS['egw_info']['apps'][$app]['title'];
					$GLOBALS['egw_info']['navbar'][$app]['url']   = $GLOBALS['egw']->link('/' . $app . '/index.php',$GLOBALS['egw_info']['flags']['params'][$app]);
					$GLOBALS['egw_info']['navbar'][$app]['name']  = $app;

					// create popup target
					if ($data['status'] == 4)
					{
						$GLOBALS['egw_info']['navbar'][$app]['target'] = ' target="'.$app.'" onClick="'."if (this != '') { window.open(this+'".
							(strstr($GLOBALS['egw_info']['navbar'][$app]['url'],'?') || 
							ini_get('session.use_trans_sid') && $GLOBALS['egw_info']['server']['sessions_type'] == 'php4' ?'&':'?').
							"referer='+encodeURI(location),this.target,'width=800,height=600,scrollbars=yes,resizable=yes'); return false; } else { return true; }".'"';
					}

					if ($app != $GLOBALS['egw_info']['flags']['currentapp'])
					{
						$GLOBALS['egw_info']['navbar'][$app]['icon']  = $this->image($app,Array('navbar','nonav'));
						$GLOBALS['egw_info']['navbar'][$app]['icon_hover']  = $this->image_on($app,Array('navbar','nonav'),'-over');
					}
					else
					{
						$GLOBALS['egw_info']['navbar'][$app]['icon']  = $this->image_on($app,Array('navbar','nonav'),'-over');
						$GLOBALS['egw_info']['navbar'][$app]['icon_hover']  = $this->image($app,Array('navbar','nonav'));
					}

//					if($GLOBALS['egw_info']['navbar'][$app]['icon'] == '')
//					{
//						$GLOBALS['egw_info']['navbar'][$app]['icon']  = $this->image('phpgwapi','nonav');
//					}
				}
			}
			if ($GLOBALS['egw_info']['flags']['currentapp'] == 'preferences' || $GLOBALS['egw_info']['flags']['currentapp'] == 'about')
			{
				$app = $app_title = 'eGroupWare';
			}
			else
			{
				$app = $GLOBALS['egw_info']['flags']['currentapp'];
				$app_title = $GLOBALS['egw_info']['apps'][$app]['title'];
			}

			if ($GLOBALS['egw_info']['user']['apps']['preferences'])	// preferences last
			{
				$prefs = $GLOBALS['egw_info']['navbar']['preferences'];
				unset($GLOBALS['egw_info']['navbar']['preferences']);
				$GLOBALS['egw_info']['navbar']['preferences'] = $prefs;
			}

			// We handle this here becuase its special
			$GLOBALS['egw_info']['navbar']['about']['title'] = lang('About %1',$app_title);

			$GLOBALS['egw_info']['navbar']['about']['url']   = $GLOBALS['egw']->link('/about.php','app='.$app);
			$GLOBALS['egw_info']['navbar']['about']['icon']  = $this->image('phpgwapi',Array('about','nonav'));
			$GLOBALS['egw_info']['navbar']['about']['icon_hover']  = $this->image_on('phpgwapi',Array('about','nonav'),'-over');

			$GLOBALS['egw_info']['navbar']['logout']['title'] = lang('Logout');
			$GLOBALS['egw_info']['navbar']['logout']['url']   = $GLOBALS['egw']->link('/logout.php');
			$GLOBALS['egw_info']['navbar']['logout']['icon']  = $this->image('phpgwapi',Array('logout','nonav'));
			$GLOBALS['egw_info']['navbar']['logout']['icon_hover']  = $this->image_on('phpgwapi',Array('logout','nonav'),'-over');
		}

		/*!
		@function app_header
		@abstract load header.inc.php for an application
		*/
		function app_header()
		{
			if (file_exists(EGW_APP_INC . '/header.inc.php'))
			{
				include(EGW_APP_INC . '/header.inc.php');
			}
		}

		/*!
		@function egw_header
		@abstract load the phpgw header
		*/
		function egw_header()
		{
			// add a content-type header to overwrite an existing default charset in apache (AddDefaultCharset directiv)
			header('Content-type: text/html; charset='.$GLOBALS['egw']->translation->charset());

			ob_end_flush();
			include_once(EGW_INCLUDE_ROOT . '/phpgwapi/templates/' . $GLOBALS['egw_info']['server']['template_set']
				. '/head.inc.php');
			$this->navbar(False);
			include_once(EGW_INCLUDE_ROOT . '/phpgwapi/templates/' . $GLOBALS['egw_info']['server']['template_set']
				. '/navbar.inc.php');
			if (!@$GLOBALS['egw_info']['flags']['nonavbar'] && !@$GLOBALS['egw_info']['flags']['navbar_target'])
			{
				echo parse_navbar();
			}
		}

		function egw_footer()
		{
			if (!defined('EGW_FOOTER'))
			{
				define('EGW_FOOTER',True);
				if (!isset($GLOBALS['egw_info']['flags']['nofooter']) || !$GLOBALS['egw_info']['flags']['nofooter'])
				{
					include(EGW_API_INC . '/footer.inc.php');
				}
			}
		}

		/**
		* Used by template headers for including CSS in the header
		*
		* This first loads up the basic global CSS definitions, which support
		* the selected user theme colors.  Next we load up the app CSS.  This is
		* all merged into the selected theme's css.tpl file.
		*
		* @author Dave Hall (*based* on verdilak? css inclusion code)
		*/
		function get_css()
		{
			$tpl = createObject('phpgwapi.Template', $this->get_tpl_dir('phpgwapi'));
			$tpl->set_file('css', 'css.tpl');
			$tpl->set_var($GLOBALS['egw_info']['theme']);
			$app_css = '';
			if(@isset($_GET['menuaction']))
			{
				list($app,$class,$method) = explode('.',$_GET['menuaction']);
				if(is_array($GLOBALS[$class]->public_functions) &&
					$GLOBALS[$class]->public_functions['css'])
				{
					$app_css .= $GLOBALS[$class]->css();
				}
			}
			if (isset($GLOBALS['egw_info']['flags']['css']))
			{
				$app_css .= $GLOBALS['egw_info']['flags']['css'];
			}
			$tpl->set_var('app_css', $app_css);

			// search for app specific css file
			if(@isset($GLOBALS['egw_info']['flags']['currentapp']))
			{
				$appname = $GLOBALS['egw_info']['flags']['currentapp'];

				if(file_exists(EGW_SERVER_ROOT . SEP . $appname . SEP
					. 'templates' . SEP . $GLOBALS['egw_info']['server']['template_set']
					. SEP . 'app.css')
				)
				{
					$tpl->set_var('css_file', '<LINK href="'.$GLOBALS['egw_info']['server']['webserver_url']
						. "/$appname/templates/".$GLOBALS['egw_info']['server']['template_set']
						. "/app.css".'" type=text/css rel=StyleSheet>');
				}
				elseif(file_exists(EGW_SERVER_ROOT . SEP . $appname . SEP
					. 'templates' . SEP . 'default'
					. SEP . 'app.css')
				)
				{
					$tpl->set_var('css_file', '<LINK href="'.$GLOBALS['egw_info']['server']['webserver_url']
					."/$appname/templates/default/app.css".'" type=text/css rel=StyleSheet>');
				}
			}

			return $tpl->subst('css');
		}

		/**
		* Used by the template headers for including javascript in the header
		*
		* The method is included here to make it easier to change the js support
		* in phpgw.  One change then all templates will support it (as long as they
		* include a call to this method).
		*
		* @author Dave Hall (*vaguely based* on verdilak? css inclusion code)
		* @return string the javascript to be included
		*/
		function get_java_script()
		{
			$java_script = '';
			
			if(!@is_object($GLOBALS['egw']->js))
			{
				$GLOBALS['egw']->js =& CreateObject('phpgwapi.javascript');
			}
			
			// always include javascript helper functions
			$GLOBALS['egw']->js->validate_file('jsapi','jsapi');

			//viniciuscb: in Concisus this condition is inexistent, and in all
			//pages the javascript globals are inserted. Today, because
			//filescenter needs these javascript globals, this
			//include_jsbackend is a must to the javascript globals be
			//included.
			if ($GLOBALS['egw_info']['flags']['include_jsbackend'])
			{
				if (!$GLOBALS['egw_info']['flags']['nojsapi'])
				{
					$GLOBALS['egw']->js->validate_jsapi();
				}
				
				if(@is_object($GLOBALS['egw']->js))
				{
					$java_script .= $GLOBALS['egw']->js->get_javascript_globals();
				}
			}

			if ($GLOBALS['egw_info']['flags']['include_xajax'])
			{
				require_once(EGW_SERVER_ROOT.'/phpgwapi/inc/xajax.inc.php');
				$xajax = new xajax($GLOBALS['egw']->link('/xajax.php'));
				$xajax->registerFunction("doXMLHTTP");

				$java_script .= $xajax->getJavascript();
			}

			/* this flag is for all javascript code that has to be put before other jscode. 
			Think of conf vars etc...  (pim@lingewoud.nl) */
			if (isset($GLOBALS['egw_info']['flags']['java_script_thirst']))
			{
				$java_script .= $GLOBALS['egw_info']['flags']['java_script_thirst'] . "\n";
			}
			
			if(@is_object($GLOBALS['egw']->js))
			{
				$java_script .= $GLOBALS['egw']->js->get_script_links();
			}

			if(@isset($_GET['menuaction']))
			{
				list($app,$class,$method) = explode('.',$_GET['menuaction']);
				if(is_array($GLOBALS[$class]->public_functions) &&
					$GLOBALS[$class]->public_functions['java_script'])
				{
					$java_script .= $GLOBALS[$class]->java_script();
				}
			}
			if (isset($GLOBALS['egw_info']['flags']['java_script']))
			{
				$java_script .= $GLOBALS['egw_info']['flags']['java_script'] . "\n";
			}
			return $java_script;
		}

		/**
		* Returns on(Un)Load attributes from js class
		*
		*@author Dave Hall - skwashd at egroupware.org
		*@returns string body attributes
		*/
		function get_body_attribs()
		{
			if(@is_object($GLOBALS['egw']->js))
			{
				return $GLOBALS['egw']->js->get_body_attribs();
			}
			else
			{
				return '';
			}
		}

		function hex2bin($data)
		{
			$len = strlen($data);
			return @pack('H' . $len, $data);
		}

		/*!
		@function encrypt
		@abstract encrypt data passed to the function
		@param $data data (string?) to be encrypted
		*/
		function encrypt($data)
		{
			return $GLOBALS['egw']->crypto->encrypt($data);
		}

		/*!
		@function decrypt
		@abstract decrypt $data
		@param $data data to be decrypted
		*/
		function decrypt($data)
		{
			return $GLOBALS['egw']->crypto->decrypt($data);
		}

		/*!
		@function encrypt_password
		@abstract legacy wrapper for newer auth class function, encrypt_password
		@abstract uses the encryption type set in setup and calls the appropriate encryption functions
		@param $password password to encrypt
		*/
		function encrypt_password($password,$sql=False)
		{
			if(!@is_object($GLOBALS['egw']->auth))
			{
				$GLOBALS['egw']->auth = CreateObject('phpgwapi.auth');
			}
			return $GLOBALS['egw']->auth->encrypt_password($password,$sql);
		}

		/*!
		@function find_portal_order
		@abstract find the current position of the app is the users portal_order preference
		@param $app application id to find current position - required
		@discussion No discussion
		*/
		function find_portal_order($app)
		{
			if(!is_array($GLOBALS['egw_info']['user']['preferences']['portal_order']))
			{
				return -1;
			}
			@reset($GLOBALS['egw_info']['user']['preferences']['portal_order']);
			while(list($seq,$appid) = each($GLOBALS['egw_info']['user']['preferences']['portal_order']))
			{
				if($appid == $app)
				{
					@reset($GLOBALS['egw_info']['user']['preferences']['portal_order']);
					return $seq;
				}
			}
			@reset($GLOBALS['egw_info']['user']['preferences']['portal_order']);
			return -1;
		}

		/*!
		@function hook
		@abstract temp wrapper to new hooks class
		*/
		function hook($location, $appname = '', $no_permission_check = False)
		{
			echo '$'."GLOBALS['phpgw']common->hook()".' has been replaced. Please change to the new $'."GLOBALS['phpgw']hooks->process()".'. For now this will act as a wrapper<br>';
			return $GLOBALS['egw']->hooks->process($location, $order, $no_permission_check);
		}

		/*!
		@function hook_single
		@abstract temp wrapper to new hooks class
		*/
		// Note: $no_permission_check should *ONLY* be used when it *HAS* to be. (jengo)
		function hook_single($location, $appname = '', $no_permission_check = False)
		{
			echo '$'."GLOBALS['phpgw']common->hook_single()".' has been replaced. Please change to the new $'."GLOBALS['phpgw']hooks->single()".'. For now this will act as a wrapper<br>';
			return $GLOBALS['egw']->hooks->single($location, $order, $no_permission_check);
		}

		/*!
		@function hook_count
		@abstract temp wrapper to new hooks class
		*/
		function hook_count($location)
		{
			echo '$'."GLOBALS['phpgw']common->hook_count()".' has been replaced. Please change to the new $'."GLOBALS['phpgw']hooks->count()".'. For now this will act as a wrapper<br>';
			return $GLOBALS['egw']->hooks->count($location);
		}

		/* Wrapper to the session->appsession() */
		function appsession($data = '##NOTHING##')
		{
			$this->debug_info[] = '$phpgw->common->appsession() is a depreciated function'
				. ' - use $phpgw->session->appsession() instead';

			return $GLOBALS['egw']->session->appsession('default','',$data);
		}

		/*!
		@function show_date
		@abstract show current date
		@param $t time - optional can be pulled from user preferences
		@param $format - optional can be pulled from user prefernces
		*/
		function show_date($t = '', $format = '')
		{
			if(!is_object($GLOBALS['egw']->datetime))
			{
				$GLOBALS['egw']->datetime = createobject('phpgwapi.datetime');
			}

			if (!$t)
			{
				$t = $GLOBALS['egw']->datetime->gmtnow;
			}

			//  + (date('I') == 1?3600:0)
			$t += $GLOBALS['egw']->datetime->tz_offset;

			if (! $format)
			{
				$format = $GLOBALS['egw_info']['user']['preferences']['common']['dateformat'] . ' - ';
				if ($GLOBALS['egw_info']['user']['preferences']['common']['timeformat'] == '12')
				{
					$format .= 'h:i a';
				}
				else
				{
					$format .= 'H:i';
				}
			}
			return adodb_date($format,$t);
		}

		/*!
		@function dateformatorder
		@abstract
		@param $yearstr year - string
		@param $monthstr month - string
		@param $day day - string
		@param $add_seperator boolean defaults to false
		*/
		function dateformatorder($yearstr,$monthstr,$daystr,$add_seperator = False)
		{
			$dateformat = strtolower($GLOBALS['egw_info']['user']['preferences']['common']['dateformat']);
			$sep = substr($GLOBALS['egw_info']['user']['preferences']['common']['dateformat'],1,1);

			$dlarr[strpos($dateformat,'y')] = $yearstr;
			$dlarr[strpos($dateformat,'m')] = $monthstr;
			$dlarr[strpos($dateformat,'d')] = $daystr;
			ksort($dlarr);

			if ($add_seperator)
			{
				return (implode($sep,$dlarr));
			}
			else
			{
				return (implode(' ',$dlarr));
			}
		}

		/*!
		@function formattime
		@abstract format the time takes settings from user preferences
		@param $hour hour
		@param $min minutes
		@param $sec defaults to ''
		*/
		function formattime($hour,$min,$sec='')
		{
			$h12 = $hour;
			if ($GLOBALS['egw_info']['user']['preferences']['common']['timeformat'] == '12')
			{
				if ($hour >= 12)
				{
					$ampm = ' pm';
				}
				else
				{
					$ampm = ' am';
				}

				$h12 %= 12;

				if ($h12 == 0 && $hour)
				{
					$h12 = 12;
				}
				if ($h12 == 0 && !$hour)
				{
					$h12 = 0;
				}
			}
			else
			{
				$h12 = $hour;
			}

			if ($sec !== '')
			{
				$sec = ":$sec";
			}

			return "$h12:$min$sec$ampm";
		}

		// This is not the best place for it, but it needs to be shared bewteen Aeromail and SM
		/*!
		@function get_email_passwd_ex
		@abstract uses code in /email class msg to obtain the appropriate password for email
		@param  (none - it will abtain the info it needs on its own)
		*/
		/*
		function get_email_passwd_ex()
		{
			// ----  Create the email Message Class  if needed  -----
			if (is_object($GLOBALS['egw']->msg))
			{
				$do_free_me = False;
			}
			else
			{
				$GLOBALS['egw']->msg = CreateObject('email.mail_msg');
				$do_free_me = True;
			}
			// use the Msg class to obtain the appropriate password
			$tmp_prefs = $GLOBALS['egw']->preferences->read();
			if (!isset($tmp_prefs['email']['passwd']))
			{
				$email_passwd = $GLOBALS['egw_info']['user']['passwd'];
			}
			else
			{
				$email_passwd = $GLOBALS['egw']->msg->decrypt_email_passwd($tmp_prefs['email']['passwd']);
			}
			// cleanup and return
			if ($do_free_me)
			{
				unset ($GLOBALS['egw']->msg);
			}
			return $email_passwd;
		}
		*/

		// This is not the best place for it, but it needs to be shared bewteen Aeromail and SM
		/*!
		@function create_emailpreferences
		@abstract create email preferences
		@discussion This is not the best place for it, but it needs to be shared between Aeromail and SM
		@param $prefs
		@param $account_id -optional defaults to : phpgw_info['user']['account_id']
		*/
		function create_emailpreferences($prefs='',$accountid='')
		{
			return $GLOBALS['egw']->preferences->create_email_preferences($accountid);
			// ----  Create the email Message Class  if needed  -----
			if (is_object($GLOBALS['egw']->msg))
			{
				$do_free_me = False;
			}
			else
			{
				$GLOBALS['egw']->msg = CreateObject('email.mail_msg');
				$do_free_me = True;
			}

			// this sets the preferences into the phpgw_info structure
			$GLOBALS['egw']->msg->create_email_preferences();

			// cleanup and return
			if ($do_free_me)
			{
				unset ($GLOBALS['egw']->msg);
			}
		}

		/*
		function create_emailpreferences($prefs,$accountid='')
		{
			$account_id = get_account_id($accountid);

			// NEW EMAIL PASSWD METHOD (shared between SM and aeromail)
			$prefs['email']['passwd'] = $this->get_email_passwd_ex();

			// Add default preferences info
			if (!isset($prefs['email']['userid']))
			{
				if ($GLOBALS['egw_info']['server']['mail_login_type'] == 'vmailmgr')
				{
					$prefs['email']['userid'] = $GLOBALS['egw']->accounts->id2name($account_id)
						. '@' . $GLOBALS['egw_info']['server']['mail_suffix'];
				}
				else
				{
					$prefs['email']['userid'] = $GLOBALS['egw']->accounts->id2name($account_id);
				}
			}
			// Set Server Mail Type if not defined
			if (empty($GLOBALS['egw_info']['server']['mail_server_type']))
			{
				$GLOBALS['egw_info']['server']['mail_server_type'] = 'imap';
			}

			// OLD EMAIL PASSWD METHOD
			if (!isset($prefs['email']['passwd']))
			{
				$prefs['email']['passwd'] = $GLOBALS['egw_info']['user']['passwd'];
			}
			else
			{
				$prefs['email']['passwd'] = $this->decrypt($prefs['email']['passwd']);
			}
			// NEW EMAIL PASSWD METHOD Located at the begining of this function

			if (!isset($prefs['email']['address']))
			{
				$prefs['email']['address'] = $GLOBALS['egw']->accounts->id2name($account_id)
					. '@' . $GLOBALS['egw_info']['server']['mail_suffix'];
			}
			if (!isset($prefs['email']['mail_server']))
			{
				$prefs['email']['mail_server'] = $GLOBALS['egw_info']['server']['mail_server'];
			}
			if (!isset($prefs['email']['mail_server_type']))
			{
				$prefs['email']['mail_server_type'] = $GLOBALS['egw_info']['server']['mail_server_type'];
			}
			if (!isset($prefs['email']['imap_server_type']))
			{
				$prefs['email']['imap_server_type'] = $GLOBALS['egw_info']['server']['imap_server_type'];
			}
			// These sets the mail_port server variable
			if ($prefs['email']['mail_server_type']=='imap')
			{
				$prefs['email']['mail_port'] = '143';
			}
			elseif ($prefs['email']['mail_server_type']=='pop3')
			{
				$prefs['email']['mail_port'] = '110';
			}
			elseif ($prefs['email']['mail_server_type']=='imaps')
			{
				$prefs['email']['mail_port'] = '993';
			}
			elseif ($prefs['email']['mail_server_type']=='pop3s')
			{
				$prefs['email']['mail_port'] = '995';
			}
			// This is going to be used to switch to the nntp class
			if (isset($GLOBALS['egw_info']['flags']['newsmode']) &&
				$GLOBALS['egw_info']['flags']['newsmode'])
			{
				$prefs['email']['mail_server_type'] = 'nntp';
			}
			// DEBUG
			//echo "<br>prefs['email']['passwd']: " .$prefs['email']['passwd'] .'<br>';
			return $prefs;
		}
		*/

		// This will be moved into the applications area.
		/*!
		@function check_code
		@abstract ?
		@discussion This will be moved into the applications area
		*/
		function check_code($code)
		{
			$s = '<br>';
			switch ($code)
			{
				case 13:	$s .= lang('Your message has been sent');break;
				case 14:	$s .= lang('New entry added sucessfully');break;
				case 15:	$s .= lang('Entry updated sucessfully');	break;
				case 16:	$s .= lang('Entry has been deleted sucessfully'); break;
				case 18:	$s .= lang('Password has been updated');	break;
				case 38:	$s .= lang('Password could not be changed');	break;
				case 19:	$s .= lang('Session has been killed');	break;
				case 27:	$s .= lang('Account has been updated');	break;
				case 28:	$s .= lang('Account has been created');	break;
				case 29:	$s .= lang('Account has been deleted');	break;
				case 30:	$s .= lang('Your settings have been updated'); break;
				case 31:	$s .= lang('Group has been added');	break;
				case 32:	$s .= lang('Group has been deleted');	break;
				case 33:	$s .= lang('Group has been updated');	break;
				case 34:	$s .= lang('Account has been deleted') . '<p>'
						. lang('Error deleting %1 %2 directory',lang('users'),' '.lang('private').' ')
						. ',<br>' . lang('Please %1 by hand',lang('delete')) . '<br><br>'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br>' . lang('permissions to the files/users directory')
						. '<br>' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['egw_info']['server']['files_dir'] . '/users/');
					break;
				case 35:	$s .= lang('Account has been updated') . '<p>'
						. lang('Error renaming %1 %2 directory',lang('users'),
						' '.lang('private').' ')
						. ',<br>' . lang('Please %1 by hand',
						lang('rename')) . '<br><br>'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br>' . lang('permissions to the files/users directory')
						. '<br>' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['egw_info']['server']['files_dir'] . '/users/');
					break;
				case 36:	$s .= lang('Account has been created') . '<p>'
						. lang('Error creating %1 %2 directory',lang('users'),
						' '.lang('private').' ')
						. ',<br>' . lang('Please %1 by hand',
						lang('create')) . '<br><br>'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br>' . lang('permissions to the files/users directory')
						. '<br>' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['egw_info']['server']['files_dir'] . '/users/');
					break;
				case 37:	$s .= lang('Group has been added') . '<p>'
						. lang('Error creating %1 %2 directory',lang('groups'),' ')
						. ',<br>' . lang('Please %1 by hand',
						lang('create')) . '<br><br>'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br>' . lang('permissions to the files/users directory')
						. '<br>' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['egw_info']['server']['files_dir'] . '/groups/');
					break;
				case 38:	$s .= lang('Group has been deleted') . '<p>'
						. lang('Error deleting %1 %2 directory',lang('groups'),' ')
						. ',<br>' . lang('Please %1 by hand',
						lang('delete')) . '<br><br>'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br>' . lang('permissions to the files/users directory')
						. '<br>' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['egw_info']['server']['files_dir'] . '/groups/');
					break;
				case 39:	$s .= lang('Group has been updated') . '<p>'
						. lang('Error renaming %1 %2 directory',lang('groups'),' ')
						. ',<br>' . lang('Please %1 by hand',
						lang('rename')) . '<br><br>'
						. lang('To correct this error for the future you will need to properly set the')
						. '<br>' . lang('permissions to the files/users directory')
						. '<br>' . lang('On *nix systems please type: %1','chmod 770 '
						. $GLOBALS['egw_info']['server']['files_dir'] . '/groups/');
					break;
				case 40: $s .= lang('You have not entered a title').'.';
					break;
				case 41: $s .= lang('You have not entered a valid time of day').'.';
					break;
				case 42: $s .= lang('You have not entered a valid date').'.';
					break;
				case 43: $s .= lang('You have not entered participants').'.';
					break;
				default:	return '';
			}
			return $s;
		}
		/*!
		@function phpgw_error
		@abstract process error message
		@param $error error
		@param $line line
		@param $file file
		*/
		function phpgw_error($error,$line = '', $file = '')
		{
			echo '<p><b>eGroupWare internal error:</b><p>'.$error;
			if ($line)
			{
				echo 'Line: '.$line;
			}
			if ($file)
			{
				echo 'File: '.$file;
			}
			echo '<p>Your session has been halted.';
			exit;
		}

		/*!
		@function create_phpcode_from_array
		@abstract create phpcode from array
		@param $array - array
		*/
		function create_phpcode_from_array($array)
		{
			while (list($key, $val) = each($array))
			{
				if (is_array($val))
				{
					while (list($key2, $val2) = each($val))
					{
						if (is_array($val2))
						{
							while (list($key3, $val3) = each ($val2))
							{
								if (is_array($val3))
								{
									while (list($key4, $val4) = each ($val3))
									{
										$s .= "\$GLOBALS['egw_info']['" . $key . "']['" . $key2 . "']['" . $key3 . "']['" .$key4 . "']='" . $val4 . "';";
										$s .= "\n";
									}
								}
								else
								{
									$s .= "\$GLOBALS['egw_info']['" . $key . "']['" . $key2 . "']['" . $key3 . "']='" . $val3 . "';";
									$s .= "\n";
								}
							}
						}
						else
						{
							$s .= "\$GLOBALS['egw_info']['" . $key ."']['" . $key2 . "']='" . $val2 . "';";
							$s .= "\n";
						}
					}
				}
				else
				{
					$s .= "\$GLOBALS['egw_info']['" . $key . "']='" . $val . "';";
					$s .= "\n";
				}
			}
			return $s;
		}

		// This will return the full phpgw_info array, used for debugging
		/*!
		@function debug_list_array_contents
		@abstract return the full phpgw_info array for debugging
		@param array - array
		*/
		function debug_list_array_contents($array)
		{
			while (list($key, $val) = each($array))
			{
				if (is_array($val))
				{
					while (list($key2, $val2) = each($val))
					{
						if (is_array($val2))
						{
							while (list($key3, $val3) = each ($val2))
							{
								if (is_array($val3))
								{
									while (list($key4, $val4) = each ($val3))
									{
										echo $$array . "[$key][$key2][$key3][$key4]=$val4<br>";
									}
								}
								else
								{
									echo $$array . "[$key][$key2][$key3]=$val3<br>";
								}
							}
						}
						else
						{
							echo $$array . "[$key][$key2]=$val2<br>";
						}
					}
				}
				else
				{
					echo $$array . "[$key]=$val<br>";
				}
			}
		}

		// This will return a list of functions in the API
		/*!
		@function debug_list_core_functions
		@abstract return a list of functionsin the API
		*/
		function debug_list_core_functions()
		{
			echo '<br><b>core functions</b><br>';
			echo '<pre>';
			chdir(EGW_INCLUDE_ROOT . '/phpgwapi');
			system("grep -r '^[ \t]*function' *");
			echo '</pre>';
		}

		// This will return a value for the next id an app/class may need to insert values into ldap.
		/*!
		@function next_id
		@abstract return the next higher value for an integer, and increment it in the db.
		*/
		function next_id($appname,$min=0,$max=0)
		{
			if (!$appname)
			{
				return -1;
			}

			$GLOBALS['egw']->db->query("SELECT id FROM phpgw_nextid WHERE appname='".$appname."'",__LINE__,__FILE__);
			while( $GLOBALS['egw']->db->next_record() )
			{
				$id = $GLOBALS['egw']->db->f('id');
			}

			if (empty($id) || !$id)
			{
				$id = 1;
				$GLOBALS['egw']->db->query("INSERT INTO phpgw_nextid (appname,id) VALUES ('".$appname."',".$id.")",__LINE__,__FILE__);
			}
			elseif($id<$min)
			{
				$id = $min;
				$GLOBALS['egw']->db->query("UPDATE phpgw_nextid SET id=".$id." WHERE appname='".$appname."'",__LINE__,__FILE__);
			}
			elseif ($max && ($id > $max))
			{
				return False;
			}
			else
			{
				$id = $id + 1;
				$GLOBALS['egw']->db->query("UPDATE phpgw_nextid SET id=".$id." WHERE appname='".$appname."'",__LINE__,__FILE__);
			}

			return (int)$id;
		}

		// This will return a value for the last id entered, which an app may need to check
		// values for ldap.
		/*!
		@function last_id
		@abstract return the current id in the next_id table for a particular app/class.
		*/
		function last_id($appname,$min=0,$max=0)
		{
			if (!$appname)
			{
				return -1;
			}

			$GLOBALS['egw']->db->query("SELECT id FROM phpgw_nextid WHERE appname='".$appname."'",__LINE__,__FILE__);
			while( $GLOBALS['egw']->db->next_record() )
			{
				$id = $GLOBALS['egw']->db->f('id');
			}

			if (empty($id) || !$id)
			{
				if($min)
				{
					$id = $min;
				}
				else
				{
					$id = 1;
				}
				$GLOBALS['egw']->db->query("INSERT INTO phpgw_nextid (appname,id) VALUES ('".$appname."',".$id.")",__LINE__,__FILE__);
			}
			elseif($id<$min)
			{
				$id = $min;
				$GLOBALS['egw']->db->query("UPDATE phpgw_nextid SET id=".$id." WHERE appname='".$appname."'",__LINE__,__FILE__);
			}
			elseif ($max && ($id > $max))
			{
				return False;
			}
			return (int)$id;
		}
		
		// some depricated functions for the migration
		function phpgw_exit($call_footer = False)
		{
			$this->egw_exit($call_footer);
		}

		function phpgw_final()
		{
			$this->egw_final();
		}
		
		function phpgw_header()
		{
			$this->egw_header();
		}
		
		function phpgw_footer()
		{
			$this->egw_footer();
		}
	}//end common class
