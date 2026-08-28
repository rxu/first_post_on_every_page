<?php
/**
*
* First Post On Every Page extension for the phpBB Forum Software package.
*
* @copyright (c) 2020, rxu, https://www.phpbbguru.net
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace rxu\firstpostoneverypage\tests\unit\event;

class listener_test extends \phpbb_test_case
{
	public function test_injected_first_post_retains_core_visibility_predicate()
	{
		$db = $this->createMock('\phpbb\db\driver\driver_interface');
		$db->method('sql_in_set')->willReturn('p.post_id IN (10, 20)');

		$content_visibility = $this->createMock('\phpbb\content_visibility');
		$content_visibility->expects($this->once())
			->method('get_visibility_sql')
			->with('post', 3, 'p.')
			->willReturn('p.post_visibility = 1');

		$listener = new \rxu\firstpostoneverypage\event\listener(
			$this->createMock('\phpbb\auth\auth'),
			$db,
			$content_visibility,
			$this->createMock('\phpbb\language\language'),
			$this->createMock('\phpbb\request\request_interface'),
			$this->createMock('\phpbb\template\template'),
			$this->createMock('\phpbb\user')
		);

		$event = new \phpbb\event\data(array(
			'post_list' => array(20),
			'topic_data' => array('topic_first_post_show' => 1, 'first_post_always_show' => 0, 'topic_first_post_id' => 10, 'forum_id' => 3),
			'sql_ary' => array('WHERE' => 'original visibility predicate'),
		));

		$listener->modify_viewtopic_post_list($event);

		$this->assertSame(array(10, 20), array_values($event['post_list']));
		$this->assertStringContainsString('p.post_visibility = 1', $event['sql_ary']['WHERE']);
	}
}
