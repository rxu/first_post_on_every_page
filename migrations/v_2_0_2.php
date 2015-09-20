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

class v_2_0_2 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['first_post_on_every_page_version']) && version_compare($this->config['first_post_on_every_page_version'], '2.0.2', '>=');
	}

	static public function depends_on()
	{
		return array('\rxu\FirstPostOnEveryPage\migrations\v_2_0_1');
	}

	public function update_data()
	{
		return array(
			// Update current version data
			array('config.update', array('first_post_on_every_page_version', '2.0.2')),
		);
	}
}
