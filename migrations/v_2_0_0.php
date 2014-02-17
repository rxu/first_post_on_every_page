<?php
/**
*
* @package first_post_on_every_page
* @copyright (c) 2014 rxu
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace rxu\first_post_on_every_page\migrations;

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
		return 	array(
			'add_columns' => array(
				$this->table_prefix . 'topics' => array(
					'topic_first_post_show' => array('BOOL', '0'),
				),
			),
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
