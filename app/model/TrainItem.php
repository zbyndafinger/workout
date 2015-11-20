<?php

namespace App\Model;

class TrainItem extends BaseModel
{
	private $table = 'trainItems';

	/**
	 * Pridani noveho zaznamu o cviceni
	 * @param array $data
	 * @return
	 */
	public function addItem($data)
	{
		return $this->database->table($this->table)->insert($data);
	}

	public function getItemsByBlock($blockId)
	{
		return $this->database->table($this->table)->where('block_id', $blockId)->fetchAll();
	}
}