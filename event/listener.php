<?php
/**
 *
 * First Post On Every Page extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, rxu, https://www.phpbbguru.net
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace rxu\firstpostoneverypage\event;

/**
* Event listener
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request_interface */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/**
	 * Constructor
	 *
	 * @param \phpbb\auth\auth                     $auth             Auth object
	 * @param \phpbb\db\driver\driver_interface    $db               DBAL object
	 * @param \phpbb\language\language          $language         Language object
	 * @param \phpbb\request\request_interface     $request          Request object
	 * @param \phpbb\template\template             $template         Template object
	 * @param \phpbb\user                          $user             User object
	 */
	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\language\language $language,
		\phpbb\request\request_interface $request,
		\phpbb\template\template $template,
		\phpbb\user $user
	)
	{
		$this->auth = $auth;
		$this->db = $db;
		$this->language = $language;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
	}

	static public function getSubscribedEvents()
	{
		return [
			'core.posting_modify_submit_post_after'		=> 'first_post_sticky',
			'core.viewtopic_get_post_data'				=> 'modify_viewtopic_post_list',
			'core.posting_modify_template_vars'			=> 'modify_posting_template_vars',
			'core.acp_manage_forums_initialise_data'	=> 'acp_manage_forums_initialise_data',
			'core.acp_manage_forums_request_data'		=> 'acp_manage_forums_request_data',
			'core.acp_manage_forums_display_form'		=> 'acp_manage_forums_display_form',
		];
	}

	public function first_post_sticky($event)
	{
		$post_data = $event['post_data'];
		$data = $event['data'];
		$post_id = (int) $data['post_id'];
		$topic_id = (int) $data['topic_id'];
		$forum_id = (int) $data['forum_id'];
		$mode = $event['mode'];

		// Set initial value for the new topic
		$post_data['topic_first_post_show'] = (isset($post_data['topic_first_post_show'])) ? $post_data['topic_first_post_show'] : 0;

		// Check if the checkbox has been checked
		$topic_first_post_show = $this->request->is_set_post('topic_first_post_show');

		// Show/Unshow first post on every page
		if (($mode == 'edit' && $post_id == $data['topic_first_post_id']) || $mode == 'post')
		{
			$perm_show_unshow = (
				$this->auth->acl_get('m_lock', $forum_id) ||
				(
					$this->auth->acl_get('f_user_lock', $forum_id) && $this->user->data['is_registered'] &&
					!empty($post_data['poster_id']) && $this->user->data['user_id'] == $post_data['poster_id']
				)
			);

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

		if (($topic_data['topic_first_post_show'] || $topic_data['first_post_always_show']) && ($post_list[0] != (int) $topic_data['topic_first_post_id']))
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

		$this->language->add_lang('first_post_on_every_page', 'rxu/firstpostoneverypage');

		// Do show show first post on every page checkbox only in first post
		$first_post_show_allowed = $first_post_always_show = false;
		if (
			(($mode == 'edit' && $post_id == $post_data['topic_first_post_id']) || $mode == 'post') &&
			(
				$this->auth->acl_get('m_lock', $forum_id) ||
				(
					$this->auth->acl_get('f_user_lock', $forum_id) && $this->user->data['is_registered'] &&
					!empty($post_data['poster_id']) && $this->user->data['user_id'] == $post_data['poster_id']
				)
			)
		)
		{
			$first_post_show_allowed = true;
			$first_post_always_show = isset($post_data['first_post_always_show']) && (int) $post_data['first_post_always_show'] == 1;
		}

		$first_post_show_checked = (isset($post_data['topic_first_post_show'])) ? $post_data['topic_first_post_show'] : 0;
		$this->template->assign_vars([
			'S_FIRST_POST_SHOW_ALLOWED'		=> $first_post_always_show || $first_post_show_allowed,
			'S_FIRST_POST_SHOW_CHECKED'		=> ($first_post_always_show || $first_post_show_checked) ? ' checked="checked"' : '',
			'S_FIRST_POST_SHOW_READONLY'	=> ($first_post_always_show) ? ' disabled="disabled"' : '',
		]);
	}

	// ACP functions
	public function acp_manage_forums_initialise_data($event)
	{
		$forum_data = $event['forum_data'];
		$forum_data += [
			'first_post_always_show'	=> 0,
		];

		$event['forum_data'] = $forum_data;
	}

	public function acp_manage_forums_request_data($event)
	{
		$forum_data = $event['forum_data'];

		$forum_data += [
			'first_post_always_show' => $this->request->variable('first_post_always_show', 0),
		];

		$event['forum_data'] = $forum_data;
	}

	public function acp_manage_forums_display_form($event)
	{
		$forum_data = $event['forum_data'];
		$template_data = $event['template_data'];

		$template_data += [
			'S_FIRST_POST_ALWAYS_SHOW'	=> $forum_data['first_post_always_show'],
		];

		$event['template_data'] = $template_data;
	}
	// ACP functions
}
