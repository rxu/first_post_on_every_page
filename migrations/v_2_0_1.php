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

class v_2_0_1 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['first_post_on_every_page_version']) && version_compare($this->config['first_post_on_every_page_version'], '2.0.1', '>=');
	}

	static public function depends_on()
	{
		return array('\rxu\FirstPostOnEveryPage\migrations\v_2_0_0');
	}

	public function update_schema()
	{
		return 	array(
			'add_columns' => array(
				$this->table_prefix . 'forums' => array(
					'first_post_always_show' => array('BOOL', '0'),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns' => array(
				$this->table_prefix . 'forums' => array('first_post_always_show'),
			),
		);
	}

	public function update_data()
	{
		return array(
			// Add configs
			// Current version
			array('config.add', array('first_post_on_every_page_version', '2.0.1')),
		);
	}
}
