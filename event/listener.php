<?php
/**
 *
 * @package FirstPostOnEveryPage
 * @copyright (c) 2014 Ruslan Uzdenov (rxu)
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace rxu\FirstPostOnEveryPage\event;

/**
* Event listener
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path, $php_ext)
	{
		$this->template = $template;
		$this->user = $user;
		$this->auth = $auth;
		$this->db = $db;
		$this->config = $config;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
	}

	static public function getSubscribedEvents()
	{
		return array(
			'core.modify_submit_post_data'		=> 'modify_posting_data',
			'core.submit_post_end'				=> 'first_post_sticky',
			'core.viewtopic_get_post_data'		=> 'modify_viewtopic_post_list',
			'core.posting_modify_template_vars'	=> 'modify_posting_template_vars',
		);
	}

	public function modify_posting_data($event)
	{
		$post_data = $event['post_data'];
		$post_data['topic_first_post_show'] = (isset($post_data['topic_first_post_show'])) ? $post_data['topic_first_post_show'] : 0;
		$event['post_data'] = $post_data;
	}

	public function first_post_sticky($event)
	{
		global $mode, $post_data;
		$data = $event['data'];
		$post_id = $data['post_id'];
		$topic_id = $data['topic_id'];
		$forum_id = $data['forum_id'];

		$topic_first_post_show = (isset($_POST['topic_first_post_show'])) ? true : false;
		// Show/Unshow first post on every page
		if (($mode == 'edit' && $post_id == $data['topic_first_post_id']) || $mode == 'post')
		{
			$perm_show_unshow = ($this->auth->acl_get('m_lock', $forum_id) || ($this->auth->acl_get('f_user_lock', $forum_id) && $this->user->data['is_registered'] && !empty($data['topic_poster']) && $this->user->data['user_id'] == $data['topic_poster'])) ? true : false;

			if ($post_data['topic_first_post_show'] != $topic_first_post_show && $perm_show_unshow)
			{
				$sql = 'UPDATE ' . TOPICS_TABLE . '
					SET topic_first_post_show = ' . (($topic_first_post_show) ? 1 : 0) . " 
					WHERE topic_id = $topic_id";
				$this->db->sql_query($sql);
			}
		}
	}

	public function modify_viewtopic_post_list($event)
	{
		$post_list = $event['post_list'];
		$topic_data = $event['topic_data'];
		$sql_ary = $event['sql_ary'];

		if ($topic_data['topic_first_post_show'] && ($post_list[0] != (int) $topic_data['topic_first_post_id']))
		{
			foreach ($post_list as $key => $value)
			{
				$post_list[$key+1] = $value;
			}
			$post_list[0] = (int) $topic_data['topic_first_post_id'];
		}

		$sql_ary['WHERE'] = $this->db->sql_in_set('p.post_id', $post_list) . ' AND u.user_id = p.poster_id';

		$event['post_list'] = $post_list;
		$event['sql_ary'] = $sql_ary;
	}

	public function modify_posting_template_vars($event)
	{
		$post_data = $event['post_data'];
		$mode = $event['mode'];
		$forum_id = $event['forum_id'];
		$post_id = $event['post_id'];

		$this->user->add_lang_ext('rxu/FirstPostOnEveryPage', 'first_post_on_every_page');

		// Do show show first post on every page checkbox only in first post
		$first_post_show_allowed = false;
		if (($mode == 'edit' && $post_id == $post_data['topic_first_post_id']) || $mode == 'post')
		{
			$first_post_show_allowed = true;
		}

		$first_post_show_checked = (isset($post_data['topic_first_post_show'])) ? $post_data['topic_first_post_show'] : 0;
		$this->template->assign_vars(array(
			'S_FIRST_POST_SHOW_ALLOWED'		=> ($first_post_show_allowed  && ($this->auth->acl_get('m_lock', $forum_id) || ($this->auth->acl_get('f_user_lock', $forum_id) && $this->user->data['is_registered'] && !empty($post_data['topic_poster']) && $this->user->data['user_id'] == $post_data['topic_poster']))) ? true : false,
			'S_FIRST_POST_SHOW_CHECKED'		=> ($first_post_show_checked) ? ' checked="checked"' : '',
		));
	}
}
