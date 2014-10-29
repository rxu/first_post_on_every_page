<?php
/**
*
* First Post On Every Page extension for the phpBB Forum Software package.
*
* @copyright (c) 2013 phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/

namespace rxu\FirstPostOnEveryPage\migrations;

class v_2_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['first_post_on_every_page_version']) && version_compare($this->config['first_post_on_every_page_version'], '2.0.0', '>=');
	}

	static public function depends_on()
	{
			return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		// If 'topic_first_post_show' column exists, most likely this is an upgrade from the 3.0 MOD
		if (!$this->db_tools->sql_column_exists($this->table_prefix . 'topics', 'topic_first_post_show'))
		{
			return 	array(
				'add_columns' => array(
					$this->table_prefix . 'topics' => array(
						'topic_first_post_show' => array('BOOL', '0'),
					),
				),
			);
		}
		return array(
		);
	}

	public function revert_schema()
	{
		return 	array(
			'drop_columns' => array(
				$this->table_prefix . 'topics' => array('topic_first_post_show'),
			),
		);
	}

	public function update_data()
	{
		return array(
			// Add configs
			// Current version
			array('config.add', array('first_post_on_every_page_version', '2.0.0')),
		);
	}
}
