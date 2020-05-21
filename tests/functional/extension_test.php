<?php
/**
 *
 * First Post On Every Page extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, rxu, https://www.phpbbguru.net
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace rxu\firstpostoneverypage\tests\functional;

/**
 * @group functional
 */
class extension_test extends \phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('rxu/firstpostoneverypage');
	}

	public function test_forum_setting()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang('acp/forums');
		$this->add_lang_ext('rxu/firstpostoneverypage', 'info_acp_first_post_on_every_page');

		$crawler = self::request('GET', "adm/index.php?i=acp_forums&icat=7&mode=manage&parent_id=1&f=2&action=edit&sid={$this->sid}");
		$this->assertContains($this->lang('FIRST_POST_ALWAYS_SHOW'), $crawler->filter('fieldset')->eq(2)->text());

		$form = $crawler->selectButton('update')->form([
			'first_post_always_show'	=> 1,
		]);
		$crawler = self::submit($form);
		$this->assertContains($this->lang('FORUM_UPDATED'), $crawler->filter('.successbox')->text());

		$crawler = self::request('GET', "adm/index.php?i=acp_forums&icat=7&mode=manage&parent_id=1&f=2&action=edit&sid={$this->sid}");
		$this->assertContains($this->lang('FIRST_POST_ALWAYS_SHOW'), $crawler->filter('fieldset')->eq(2)->text());

		$form = $crawler->selectButton('update')->form([
			'first_post_always_show'	=> 0,
		]);
		$crawler = self::submit($form);
		$this->assertContains($this->lang('FORUM_UPDATED'), $crawler->filter('.successbox')->text());
	}

	public function test_post_option()
	{
		$this->login();

		$this->add_lang_ext('rxu/firstpostoneverypage', 'first_post_on_every_page');

		$this->get_db();
		$sql = 'SELECT p.post_id, t.forum_id FROM ' . POSTS_TABLE . ' p,  ' . TOPICS_TABLE . ' t
			WHERE p.post_id = t.topic_first_post_id
			ORDER BY post_id DESC LIMIT 1';
		$result = $this->db->sql_query($sql);
		$post = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Test checking the option
		$crawler = self::request('GET', "posting.php?mode=edit&f={$post['forum_id']}&sid={$this->sid}&p={$post['post_id']}");
		$this->assertContains($this->lang('FIRST_POST_SHOW'), $crawler->filter('label[for="topic_first_post_show"]')->text());
		$this->assertNotContains('checked', $crawler->filter('label[for="topic_first_post_show"]')->html());

		$form = $crawler->selectButton('post')->form();
		$form['topic_first_post_show']->tick();
		$crawler = self::submit($form);

		$crawler = self::request('GET', "posting.php?mode=edit&f={$post['forum_id']}&sid={$this->sid}&p={$post['post_id']}");
		$this->assertContains($this->lang('FIRST_POST_SHOW'), $crawler->filter('label[for="topic_first_post_show"]')->text());
		$this->assertContains('checked', $crawler->filter('label[for="topic_first_post_show"]')->html());

		// Test unchecking the option
		$crawler = self::request('GET', "posting.php?mode=edit&f={$post['forum_id']}&sid={$this->sid}&p={$post['post_id']}");
		$this->assertContains($this->lang('FIRST_POST_SHOW'), $crawler->filter('label[for="topic_first_post_show"]')->text());
		$this->assertContains('checked', $crawler->filter('label[for="topic_first_post_show"]')->html());

		$form = $crawler->selectButton('post')->form();
		$form['topic_first_post_show']->untick();
		$crawler = self::submit($form);

		$crawler = self::request('GET', "posting.php?mode=edit&f={$post['forum_id']}&sid={$this->sid}&p={$post['post_id']}");
		$this->assertContains($this->lang('FIRST_POST_SHOW'), $crawler->filter('label[for="topic_first_post_show"]')->text());
		$this->assertNotContains('checked', $crawler->filter('label[for="topic_first_post_show"]')->html());
	}
}
