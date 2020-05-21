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
		$this->assertContains('checked', $crawler->filter('input[name="first_post_always_show"]')->eq(1)->parents()->html());
		$this->assertContains('No', $crawler->filter('input[name="first_post_always_show"]')->eq(1)->parents()->text());

		$form = $crawler->selectButton('update')->form([
			'first_post_always_show'	=> 1,
		]);
		$crawler = self::submit($form);
		$this->assertContains($this->lang('FORUM_UPDATED'), $crawler->filter('.successbox')->text());

		$crawler = self::request('GET', "adm/index.php?i=acp_forums&icat=7&mode=manage&parent_id=1&f=2&action=edit&sid={$this->sid}");
		$this->assertContains($this->lang('FIRST_POST_ALWAYS_SHOW'), $crawler->filter('fieldset')->eq(2)->text());
		$this->assertContains('checked', $crawler->filter('input[name="first_post_always_show"]')->eq(0)->parents()->html());
		$this->assertContains('Yes', $crawler->filter('input[name="first_post_always_show"]')->eq(0)->parents()->text());

		$form = $crawler->selectButton('update')->form([
			'first_post_always_show'	=> 0,
		]);
		$crawler = self::submit($form);
		$this->assertContains($this->lang('FORUM_UPDATED'), $crawler->filter('.successbox')->text());

		$crawler = self::request('GET', "adm/index.php?i=acp_forums&icat=7&mode=manage&parent_id=1&f=2&action=edit&sid={$this->sid}");
		$this->assertContains('checked', $crawler->filter('input[name="first_post_always_show"]')->eq(1)->parents()->html());
		$this->assertContains('No', $crawler->filter('input[name="first_post_always_show"]')->eq(1)->parents()->text());
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

	public function test_first_post_on_pages()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang_ext('rxu/firstpostoneverypage', 'first_post_on_every_page');

		$this->get_db();
		$sql = 'SELECT p.post_id, t.forum_id, t.topic_id, t.topic_title FROM ' . POSTS_TABLE . ' p,  ' . TOPICS_TABLE . ' t
			WHERE p.post_id = t.topic_first_post_id
			ORDER BY post_id DESC';
		$result = $this->db->sql_query_limit($sql, 1);
		$topic = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Set 5 posts per page
		$crawler = self::request('GET', "adm/index.php?i=acp_board&mode=post&sid={$this->sid}");
		$form = $crawler->selectButton('submit')->form([
			'config[posts_per_page]'	=> 5,
		]);
		$crawler = self::submit($form);
		$this->assertContains($this->lang('CONFIG_UPDATED'), $crawler->filter('.successbox')->text());
		
		// Create 10 topic replies
		for ($i = 0; $i < 10; $i++)
		{
			$this->create_post($topic['forum_id'], $topic['topic_id'], 'Re: ' . $topic['topic_title'], "This is " . ($i + 1) . " reply to the topic {$topic['topic_id']}.");
		}

		// Checking the first post on every page option
		$crawler = self::request('GET', "posting.php?mode=edit&f={$topic['forum_id']}&sid={$this->sid}&p={$topic['post_id']}");
		$this->assertContains($this->lang('FIRST_POST_SHOW'), $crawler->filter('label[for="topic_first_post_show"]')->text());
		$this->assertNotContains('checked', $crawler->filter('label[for="topic_first_post_show"]')->html());

		$form = $crawler->selectButton('post')->form();
		$form['topic_first_post_show']->tick();
		$crawler = self::submit($form);

		// Browse 2nd page of the topic and ensure 1st topic post is there
		$crawler = self::request('GET', "viewtopic.php?t={$topic['topic_id']}&start=5&sid={$this->sid}");
		$this->assertContains('2', $crawler->filter('div[class="pagination"] > ul > li[class="active"] > span')->text());
		$this->assertEquals("p{$topic['post_id']}", $crawler->filter('.post')->attr('id'));
		$this->assertNotEmpty($crawler->filter('div[id="post_content' . $topic['post_id'] . '"] > div[class="content"]')->text());
	}

	public function test_first_post_with_forum_option()
	{
		$this->login();
		$this->admin_login();

		$this->add_lang('acp/forums');
		$this->add_lang_ext('rxu/firstpostoneverypage', ['info_acp_first_post_on_every_page', 'first_post_on_every_page']);

		// Set forum option
		$crawler = self::request('GET', "adm/index.php?i=acp_forums&icat=7&mode=manage&parent_id=1&f=2&action=edit&sid={$this->sid}");
		$this->assertContains($this->lang('FIRST_POST_ALWAYS_SHOW'), $crawler->filter('fieldset')->eq(2)->text());
		$this->assertContains('checked', $crawler->filter('input[name="first_post_always_show"]')->eq(1)->parents()->html());
		$this->assertContains('No', $crawler->filter('input[name="first_post_always_show"]')->eq(1)->parents()->text());

		$form = $crawler->selectButton('update')->form([
			'first_post_always_show'	=> 1,
		]);
		$crawler = self::submit($form);
		$this->assertContains($this->lang('FORUM_UPDATED'), $crawler->filter('.successbox')->text());

		$this->get_db();
		$sql = 'SELECT p.post_id, t.forum_id, t.topic_id FROM ' . POSTS_TABLE . ' p,  ' . TOPICS_TABLE . ' t
			WHERE p.post_id = t.topic_first_post_id
			ORDER BY post_id DESC';
		$result = $this->db->sql_query_limit($sql, 1);
		$topic = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		// Checking that first post on every page option is checked and editless
		$crawler = self::request('GET', "posting.php?mode=edit&f={$topic['forum_id']}&sid={$this->sid}&p={$topic['post_id']}");
		$this->assertContains($this->lang('FIRST_POST_SHOW'), $crawler->filter('label[for="topic_first_post_show"]')->text());
		$this->assertContains('checked', $crawler->filter('label[for="topic_first_post_show"]')->html());
		$this->assertContains('disabled', $crawler->filter('label[for="topic_first_post_show"]')->html());

		// Browse 2nd page of the topic and ensure 1st topic post is there and it's 1st on the page
		$crawler = self::request('GET', "viewtopic.php?t={$topic['topic_id']}&start=5&sid={$this->sid}");
		$this->assertContains('2', $crawler->filter('div[class="pagination"] > ul > li[class="active"] > span')->text());
		$this->assertEquals("p{$topic['post_id']}", $crawler->filter('.post')->attr('id'));
	}
}
