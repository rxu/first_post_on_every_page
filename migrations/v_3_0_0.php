<?php
/**
 *
 * First Post On Every Page extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, rxu, https://www.phpbbguru.net
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace rxu\firstpostoneverypage\migrations;

class v_3_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_column_exists($this->table_prefix . 'forums', 'first_post_always_show');
	}

	static public function depends_on()
	{
			return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'topics' => [
					'topic_first_post_show' => ['BOOL', '0'],
				],
				$this->table_prefix . 'forums' => [
					'first_post_always_show' => ['BOOL', '0'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'topics' => ['topic_first_post_show'],
				$this->table_prefix . 'forums' => ['first_post_always_show'],
			],
		];
	}
}
