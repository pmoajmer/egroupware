<?php
/**
 * eGroupWare - abstract base class for tracking (history log, notifications, ...)
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package etemplate
 * @subpackage api
 * @copyright (c) 2007 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$ 
 */

require_once(EGW_API_INC.'/class.html.inc.php');

/**
 * Abstract base class for trackering:
 *  - logging all modifications of an entry
 *  - notifying users about changes in an entry
 * 
 * You need to extend these class in your application:
 *	1. set the required class-vars: app, id_field
 *	2. optional set class-vars: creator_field, assigned_field, check2prefs
 *	3. implement the required methods: get_config, get_details
 *	4. optionally re-implement: get_subject, get_body, get_attachments, get_link, get_message
 * They are all documented in this file via phpDocumentor comments.
 */
class bo_tracking
{
	/**
	 * Application we are tracking
	 *
	 * @var string
	 */
	var $app;
	/**
	 * Name of the id-field, used as id in the history log (required!)
	 *
	 * @var string
	 */
	var $id_field;
	/**
	 * Name of the field with the creator id, if the creator of an entry should be notified
	 *
	 * @var string
	 */
	var $creator_field;
	/**
	 * Name of the field with the id(s) of assinged users, if they should be notified
	 *
	 * @var string
	 */
	var $assigned_field;
	/**
	 * Can be used to map the following prefs to different names:
	 *  - notify_creator  - user wants to be notified for items he created
	 *  - notify_assigned - user wants to be notified for items assigned to him
	 *  - notify_html     - user wants his notifications as html-email
	 * @var array
	 */
	var $check2pref;
	/**
	 * Translate field-name to 2-char history status
	 *
	 * @var array
	 */
	var $field2history = array();
	/**
	 * Should the user (passed to the track method or current user if not passed) be used as sender or get_config('sender')
	 *
	 * @var boolean
	 */
	var $prefer_user_as_sender = true;
	/**
	 * Should the current user be email-notified (about change he made himself)
	 * 
	 * Popup notifications are never send to the current user!
	 *
	 * @var boolean
	 */
	var $notify_current_user = false;
	
	/**
	 * Array with error-messages if track($data,$old) returns false
	 *
	 * @var array
	 */
	var $errors = array();

	/**
	 * instance of the historylog object for the app we are tracking
	 *
	 * @access private
	 * @var historylog
	 */
	var $historylog;
	
	/**
	 * Current user, can be set via bo_tracking::track(,,$user)
	 * 
	 * @access private
	 * @var int;
	 */
	var $user;
	/**
	 * Saved user preferences, if send_notifications need to set an other language
	 *
	 * @access private
	 * @var array
	 */
	var $save_prefs;
	/**
	 * Datetime format of the currently notified user (send_notificaton)
	 *
	 * @var string
	 */
	var $datetime_format;
	/**
	 * Offset to server-time of the currently notified user (send_notificaton)
	 *
	 * @var int
	 */
	var $tz_offset_s;
	/**
	 * Reference to the html class
	 *
	 * @var html
	 */
	var $html;
	/**
	 * Should the class allow html content (for notifications)
	 * 
	 * @var boolean
	 */
	var $html_content_allow = false;
	
	/**
	 * Constructor
	 *
	 * @return bo_tracking
	 */
	function bo_tracking()
	{
		$this->html =& html::singleton();
	}

	/**
	 * Get a config value, which can depend on $data and $old
	 * 
	 * Need to be implemented in your extended tracking class!
	 *
	 * @abstract 
	 * @param string $what possible values are:
	 * 	- 'copy' array of email addresses notifications should be copied too, can depend on $data
	 *  - 'lang' string lang code for copy mail
	 *  - 'subject' string subject line for the notification of $data,$old, defaults to link-title
	 * @param array $data current entry
	 * @param array $old=null old/last state of the entry or null for a new entry
	 * @return mixed
	 */
	function get_config($name,$data,$old=null)
	{
		die('You need to extend the bo_tracking class, to be able to use it (abstract base class)!');		
	}
	
	/**
	 * Tracks the changes in one entry $data, by comparing it with the last version in $old
	 *
	 * @param array $data current entry
	 * @param array $old=null old/last state of the entry or null for a new entry
	 * @param int $user=null user who made the changes, default to current user
	 * @param boolean $deleted=null can be set to true to let the tracking know the item got deleted or undelted
	 * @return int/boolean false on error, integer number of changes logged or true for new entries ($old == null)
	 */
	function track($data,$old=null,$user=null,$deleted=null)
	{
		$this->user = !is_null($user) ? $user : $GLOBALS['egw_info']['user']['account_id'];

		$changes = true;

		if ($old && $this->field2history)
		{
			$changes = $this->save_history($data,$old,$deleted);
		}
		// do not run do_notifications if we have no changes
		if ($changes && !$this->do_notifications($data,$old,$deleted))
		{
			$changes = false;
		}
		return $changes;
	}
	
	/**
	 * Save changes to the history log
	 *
	 * @internal use only track($data,$old)
	 * @param array $data current entry
	 * @param array $old=null old/last state of the entry or null for a new entry
	 * @param boolean $deleted=null can be set to true to let the tracking know the item got deleted or undelted
	 * @return int number of log-entries made
	 */
	function save_history($data,$old,$deleted=null)
	{
		$changes = 0;
		foreach($this->field2history as $name => $status)
		{
			if ($old[$name] != $data[$name] && !(!$old[$name] && !$data[$name]))
			{
				if (!is_object($this->historylog))
				{
					require_once(EGW_API_INC.'/class.historylog.inc.php');
					$this->historylog =& new historylog($this->app);
				}
				$this->historylog->add($status,$data[$this->id_field],
					is_array($data[$name]) ? implode(',',$data[$name]) : $data[$name],
					is_array($old[$name]) ? implode(',',$old[$name]) : $old[$name]);
				++$changes;
			}
		}
		return $changes;
	}
	
	/**
	 * sending all notifications for the changed entry
	 *
	 * @internal use only track($data,$old,$user)
	 * @param array $data current entry
	 * @param array $old=null old/last state of the entry or null for a new entry
	 * @param boolean $deleted=null can be set to true to let the tracking know the item got deleted or undelted
	 * @return boolean true on success, false on error (error messages are in $this->errors)
	 */
	function do_notifications($data,$old,$deleted=null)
	{
		$this->errors = $email_sent = array();

		if (!$this->notify_current_user)		// should we notify the current user about his own changes
		{
			//error_log("do_notificaton() adding user=$this->user to email_sent, to not notify him");
			$email_sent[] = $GLOBALS['egw']->accounts->id2name($this->user,'account_email');
		}

		// entry creator
		if ($this->creator_field && ($email = $GLOBALS['egw']->accounts->id2name($data[$this->creator_field],'account_email')) &&
			!in_array($email, $email_sent)) 
		{
			$this->send_notification($data,$old,$email,$data[$this->creator_field],'notify_creator');
			$email_sent[] = $email;	
		}

		// assigned / responsible users
		if ($this->assigned_field)
		{
			//error_log("bo_tracking::do_notifications() data[$this->assigned_field]=".print_r($data[$this->assigned_field],true).", old[$this->assigned_field]=".print_r($old[$this->assigned_field],true));
			$assignees = $old_assignees = array();
			if ($data[$this->assigned_field])	// current assignments
			{
				$assignees = is_array($data[$this->assigned_field]) ? 
					$data[$this->assigned_field] : explode(',',$data[$this->assigned_field]);
			}
			if ($old && $old[$this->assigned_field])
			{
				$old_assignees = is_array($old[$this->assigned_field]) ? 
					$old[$this->assigned_field] : explode(',',$old[$this->assigned_field]);
			}
			foreach(array_unique(array_merge($assignees,$old_assignees)) as $assignee)
			{
				//error_log("bo_tracking::do_notifications() assignee=$assignee, type=".$GLOBALS['egw']->accounts->get_type($assignee).", email=".$GLOBALS['egw']->accounts->id2name($assignee,'account_email'));
				if (!$assignee) continue;

				// item assignee is a user
				if ($GLOBALS['egw']->accounts->get_type($assignee) == 'u')
				{
					if (($email = $GLOBALS['egw']->accounts->id2name($assignee,'account_email')) && !in_array($email, $email_sent))
					{
						$this->send_notification($data,$old,$email,$assignee,'notify_assigned',
							in_array($assignee,$assignees) !== in_array($assignee,$old_assignees) || $deleted);	// assignment changed
						$email_sent[] = $email;	
					}
				}
				else	// item assignee is a group
				{
					foreach($GLOBALS['egw']->accounts->members($assignee,true) as $u)
					{
						if (($email = $GLOBALS['egw']->accounts->id2name($u,'account_email')) && !in_array($email, $email_sent))
						{
							$this->send_notification($data,$old,$email,$u,'notify_assigned',
								in_array($u,$assignees) !== in_array($u,$old_assignees) || $deleted);	// assignment changed
							$email_sent[] = $email;
						}
					}
				}
			}
		}

		// notification copies
		if (($copies = $this->get_config('copy',$data,$old)))
		{
			$lang = $this->get_config('lang',$data,$old);
			foreach($copies as $email)
			{
				if (strchr($email,'@') !== false && !in_array($email, $email_sent)) 
				{
					$this->send_notification($data,$old,$email,$lang,'notify_copy');
					$email_sent[] = $email;
				}
			}
		}

		// restore the user enviroment
		if ($this->save_prefs) $GLOBALS['egw_info']['user'] = $this->save_prefs; unset($this->save_prefs);
		if ($GLOBALS['egw_info']['user']['preferences']['common']['lang'] != $GLOBALS['egw']->translation->userlang)
		{
			$GLOBALS['egw']->translation->init();			
		}
		return !count($this->errors);
	}

	/**
	 * Sending a notification to the given email-address
	 * 
	 * Called by track() or externally for sending async notifications
	 *
	 * @param array $data current entry
	 * @param array $old=null old/last state of the entry or null for a new entry
	 * @param string $email address to send the notification to
	 * @param string $user_or_lang='en' user-id or 2 char lang-code for a non-system user
	 * @param string $check=null pref. to check if a notification is wanted
	 * @param boolean $assignment_changed=true the assignment of the user $user_or_lang changed
	 * @return boolean true on success or false on error (error-message is in $this->errors)
	 */
	function send_notification($data,$old,$email,$user_or_lang,$check=null,$assignment_changed=true)
	{
		//error_log("bo_trackering::send_notification(,,'$email',$user_or_lang,$check)");
		if (!$email) return false;

		if (!$this->save_prefs) $this->save_prefs = $GLOBALS['egw_info']['user'];
		
		if (is_numeric($user_or_lang))	// user --> read everything from his prefs
		{
			if ($user_or_lang != $this->user)
			{
				$GLOBALS['egw']->preferences->preferences($user_or_lang);
				$GLOBALS['egw_info']['user']['preferences'] = $GLOBALS['egw']->preferences->read_repository();
			}
			if ($check && $this->check2pref) $check = $this->check2pref[$check];
			if ($check && !$GLOBALS['egw_info']['user']['preferences'][$this->app][$check])
			{
				return false;	// no notification requested
			}
			if ($check && $GLOBALS['egw_info']['user']['preferences'][$this->app][$check] === 'assignment' && !$assignment_changed)
			{
				return false;	// only notification about changed assignment requested
			}
			if($this->user == $user_or_lang && !$this->notify_current_user)
			{ 
				return false;  // no popup for own actions
			}
		}
		else
		{
			// for the notification copy, we use the default-prefs plus the language from the the tracker config
			$GLOBALS['egw_info']['user']['preferences'] = $GLOBALS['egw']->preferences->default;
			$GLOBALS['egw_info']['user']['preferences']['common']['lang'] = $user_or_lang;
		}
		if ($lang != $GLOBALS['egw']->translation->userlang)	// load the right language if needed
		{
			$GLOBALS['egw']->translation->init();
		}
		
		$sender = $this->get_sender($data,$old);
		$subject = $this->get_subject($data,$old);
		$attachments = $this->get_attachments($data,$old);
		// does the user want html-emails
		$html_email = !!$GLOBALS['egw_info']['user']['preferences']['tracker'][$this->check2pref ? $this->check2pref['notify_html'] : 'notify_html'];

		/* send over notification_app or alternative old-style mail class
		 * in future, we can make the notification app able to send mails
		 * for non-system users, so the else part below could be dropped
		 */
		if (is_numeric($user_or_lang) && $GLOBALS['egw_info']['apps']['notifications']['enabled']) {
			// send via notification_app
			require_once(EGW_INCLUDE_ROOT. '/notifications/inc/class.notification.inc.php');
			try {
				$notification = new notification();
				$notification->set_receivers(array($user_or_lang));
				$notification->set_message($this->get_body($html_email,$data,$old));
				$notification->set_sender($this->user);
				$notification->set_subject($subject);
				// does not work atm
				//$notification->set_links(array($this->get_notification_link($data,$old)));
				if(is_array($attachments)) { $notification->set_attachments($attachments); }
				$notification->send();
			}
			catch (Exception $exception) {
				$this->errors[] = $exception->getMessage();
				return false;
			}
		} else {
			// PHPMailer aka send-class, seems not to be able to send more then one mail, IF we need to authenticate to the SMTP server
			// There for the object is newly created for ever mail, 'til this get fixed in PHPMailer.
			$notification_sent = false;
			//if(!is_object($GLOBALS['egw']->send))
			//{
			 require_once(EGW_API_INC.'/class.send.inc.php');
			 $GLOBALS['egw']->send = $send =& new send();
			//}
		
			$send->ClearAddresses();
			$send->ClearAttachments();

			$send->IsHTML($html_email);		

			if (preg_match('/^(.+) *<(.+)>/',$email,$matches))	// allow to use eg. "Ralf Becker <ralf@egw.org>" as address
			{
			 $send->AddAddress($matches[2],$matches[1]);
			}
			else
			{
			 $send->AddAddress($email,is_numeric($user_or_lang) ? $GLOBALS['egw']->accounts->id2name($user_or_lang,'account_fullname') : '');
			}
			$send->AddCustomHeader("X-eGroupWare-type: {$this->app}update");
		
			if (preg_match('/^(.+) *<(.+)>/',$sender,$matches))	// allow to use eg. "Ralf Becker <ralf@egw.org>" as sender
			{
			 $send->From = $matches[2];
			 $send->FromName = $matches[1];
			}
			else
			{
			 $send->From = $sender;
			 $send->FromName = '';
			}
			$send->Subject = $subject;
			$send->Body = "<html>\n<body>\n".$this->get_body($html_email,$data,$old)."</body>\n</html>\n";
			
			foreach($attachments as $attachment)
			{
			 	if (isset($attachment['content']))
				{
				 	$send->AddStringAttachment($attachment['content'],$attachment['filename'],$attachment['encoding'],$attachment['mimetype']);
				}
				elseif (isset($attachment['path']))
				{
				 	$send->AddAttachment($attachment['path'],$attachment['filename'],$attachment['encoding'],$attachment['$mimetype']);
				}
			}
		
			//echo "<p>bo_trackering::send_notification(): sending <pre>".print_r($send,true)."</pre>\n";
			$notification_sent = $send->Send();
			if(!$notification_sent) {
		 		$this->errors[] = lang('Error while notifying %1: %2',$email,$send->ErrorInfo);
		 		return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Return date+time formatted for the currently notified user (prefs in $GLOBALS['egw_info']['user']['preferences'])
	 *
	 * @param int $timestamp
	 * @param boolean $do_time=true true=allways (default), false=never print the time, null=print time if != 00:00
	 * @return string
	 */
	function datetime($timestamp,$do_time=true)
	{
		if (is_null($do_time))
		{
			$do_time = date('H:i',$timestamp+$this->tz_offset_s) != '00:00';
		}
		$format = $GLOBALS['egw_info']['user']['preferences']['common']['dateformat'];
		if ($do_time) $format .= ' '.($GLOBALS['egw_info']['user']['preferences']['common']['timeformat'] != 12 ? 'H:i' : 'h:i a');

		//error_log("bo_tracking::datetime($timestamp,$do_time)=date('$format',$timestamp+$this->tz_offset_s)='".date($format,$timestamp+$this->tz_offset_s).'\')');
		return date($format,$timestamp+3600 * $GLOBALS['egw_info']['user']['preferences']['common']['tz_offset']);
	}
	
	/**
	 * Get sender address
	 * 
	 * The default implementation prefers depending on the prefer_user_as_sender class-var the user over
	 * what is returned by get_config('sender'). 
	 * 
	 * @param int $user account_lid of user
	 * @param array $data
	 * @param array $old
	 * @return string
	 */
	function get_sender($data,$old)
	{
		$sender = $this->get_config('sender',$data,$old);
		//echo "<p>bo_tracking::get_sender() get_config('sender',...)='".htmlspecialchars($sender)."'</p>\n";

		if (($this->prefer_user_as_sender || !$sender) && $this->user && 
			($email = $GLOBALS['egw']->accounts->id2name($this->user,'account_email')))
		{
			$name = $GLOBALS['egw']->accounts->id2name($this->user,'account_fullname');
			
			$sender = $name ? $name.' <'.$email.'>' : $email;
		}
		elseif(!$sender)
		{
			$sender = 'eGroupWare '.lang($this->app).' <noreply@'.$GLOBALS['egw_info']['server']['mail_suffix'].'>';
		}
		//echo "<p>bo_tracking::get_sender()='".htmlspecialchars($sender)."'</p>\n";
		return $sender;
	}

	/**
	 * Get the subject for a given entry, can be reimplemented
	 * 
	 * Default implementation uses the link-title
	 *
	 * @param array $data
	 * @param array $old
	 * @return string
	 */
	function get_subject($data,$old)
	{
		if (!is_object($GLOBALS['egw']->link))
		{
			require_once(EGW_API_INC.'/class.bolink.inc.php');
			$GLOBALS['egw']->link =& new bolink();
		}
		return $GLOBALS['egw']->link->title($this->app,$data[$this->id_field]);
	}

	/**
	 * Get the modified / new message (1. line of mail body) for a given entry, can be reimplemented
	 * 
	 * Default implementation does nothing
	 *
	 * @param array $data
	 * @param array $old
	 * @return string
	 */
	function get_message($data,$old)
	{
		return '';
	}

	/**
	 * Get a link to view the entry, can be reimplemented
	 * 
	 * Default implementation checks get_config('link') (appending the id) or link::view($this->app,$id)
	 *
	 * @param array $data
	 * @param array $old
	 * @param string $allow_popup=false if true return array(link,popup-size) incl. session info an evtl. partial url (no host-part)
	 * @return string/array string with link (!$allow_popup) or array(link,popup-size), popup size is something like '640x480'
	 */
	function get_link($data,$old,$allow_popup=false)
	{
		if (($link = $this->get_config('link',$data,$old)))
		{
			if (strpos($link,$this->id_field.'=') === false)
			{
				$link .= '&'.$this->id_field.'='.$data[$this->id_field];
			}
		}
		else
		{
			if (!is_object($GLOBALS['egw']->link))
			{
				require_once(EGW_API_INC.'/class.bolink.inc.php');
				$GLOBALS['egw']->link =& new bolink();
			}
			if (($view = $GLOBALS['egw']->link->view($this->app,$data[$this->id_field])))
			{
				$link = $GLOBALS['egw']->link('/index.php',$view);
				$popup = $GLOBALS['egw']->link->is_popup($this->app,'view');
			}
		}
		if ($link{0} == '/')
		{
			$link = ($_SERVER['HTTPS'] || $GLOBALS['egw_info']['server']['enforce_ssl'] ? 'https://' : 'http://').
				($GLOBALS['egw_info']['server']['hostname'] ? $GLOBALS['egw_info']['server']['hostname'] : $_SERVER['HTTP_HOST']).$link;
		}
		if (!$allow_popup)
		{
			// remove the session-id in the notification mail!
			$link = preg_replace('/(sessionid|kp3|domain)=[^&]+&?/','',$link);
			
			if ($popup) $link .= '&nopopup=1';
		}
		return $allow_popup ? array($link,$popup) : $link;
	}
	
	/**
	 * Get a link for notifications to view the entry
	 *
	 * @param array $data
	 * @param array $old
	 * @return array with link
	 */
	function get_notification_link($data,$old)
	{
		if (!is_object($GLOBALS['egw']->link))
		{
			require_once(EGW_API_INC.'/class.bolink.inc.php');
			$GLOBALS['egw']->link =& new bolink();
		}
		if($view = $GLOBALS['egw']->link->view($this->app,$data[$this->id_field])) {
			return array(	'menuaction' => $view['menuaction'],
							'params' => array (	'action' => $view['action'],
												'action_id' => $view['action_id'],
												),
							'text' => $data['info_subject'],
							);
		} else {
			return false;
		}
	}
		
	
	/**
     * Get the body of the notification message, can be reimplemented
     *
     * @param boolean $html_email
     * @param array $data
     * @param array $old
     * @return string
     */
    function get_body($html_email,$data,$old)
    {
        $body = '';
        if ($html_email)
        {    
            $body = '<table cellspacing="2" cellpadding="0" border="0" width="100%">'."\n";
        }
        // new or modified message
        if (($message = $this->get_message($data,$old)))
        {
            $body .= $this->format_line($html_email,'message',false,$message);
        }
        if (($link = $this->get_link($data,$old)))
        {
            $body .= $this->format_line($html_email,'link',false,lang('You can respond by visiting:'),$link);
        }
        foreach($this->get_details($data) as $name => $detail)
        {
            // if there's no old entry, the entry is not modified by definition
            // if both values are '', 0 or null, we count them as equal too
            $modified = $old && $data[$name] != $old[$name] && !(!$data[$name] && !$old[$name]);
            //if ($modified) error_log("data[$name]=".print_r($data[$name],true).", old[$name]=".print_r($old[$name],true)." --> modified=".(int)$modified);
            if (empty($detail['value']) && !$modified) continue;    // skip unchanged, empty values
            
            $body .= $this->format_line($html_email,$detail['type'],$modified,
                ($detail['label'] ? $detail['label'].': ':'').$detail['value']);
        }
        if ($html_email)
        {
            $body .= "</table>\n";
        }
        
        return $body;
    }
	
	/**
	 * Format one line to the mail body
	 *
	 * @internal 
	 * @param boolean $html_mail
	 * @param string $type 'link', 'message', 'summary', 'multiline', 'reply' and ''=regular content
	 * @param boolean $modified mark field as modified
	 * @param string $line
	 * @param string $link=null
	 * @return string
	 */
	function format_line($html_mail,$type,$modified,$line,$link=null)
	{
		$content = '';
		
		if ($html_mail)
		{
			if (!$this->html_content_allow) $line = $this->html->htmlspecialchars($line);	// XSS

			$color = $modified ? 'red' : false;
			$size  = $html_mail == 'medium' ? 'medium' : 'small';
			$bold = false;
			$background = '#FFFFF1';
			switch($type)
			{
				case 'message':
					$background = '#D3DCE3;';
					$bold = true;
					break;
				case 'link':
					$background = '#F1F1F1';
					break;
				case 'summary':
					$background = '#F1F1F1';
					$bold = true;
					break;
				case 'multiline':
					// Only Convert nl2br on non-html content
					$pos = strpos($line, '<br');
					if ($pos===false) 
					{
						$line = nl2br($line);	
					}				
					break;
				case 'reply':
					$background = '#F1F1F1';					
					break;
				default:
					$size = $size == 'small' ? 'x-small' : 'small';
			}
			$style = ($bold ? 'font-weight:bold;' : '').($size ? 'font-size:'.$size.';' : '').($color?'color:'.$color:'');
			
			$content = '<tr style="background-color: '.$background.';"><td style="'.$style.'">';
		}
		else	// text-mail
		{
			if ($type == 'reply') $content = str_repeat('-',64)."\n"; 

			if ($modified) $content .= '> ';
		}
		$content .= $line;

		if ($link)
		{
			$content .= ' ';
			
			if ($html_mail)
			{
				$content .= $this->html->a_href($link,$link,'','target="_blank"');
			}
			else
			{
				$content .= $link;
			}
		}
		if ($html_mail) $content .= '</td></tr>';
		
		$content .= "\n";
		
		return $content;
	}
	
	/**
	 * Get the attachments for a notification mail
	 *
	 * @param array $data
	 * @param array $old
	 * @return array with values for either 'content' or 'path' and optionally 'mimetype', 'filename' and 'encoding'
	 */
	function get_attachments($data,$old)
	{
	 	return array();
	}
}
