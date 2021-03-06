<?php

namespace App\Model;

class Train extends BaseModel
{
	private $table = 'trains';

	/**
	 * Pridani noveho treninku
	 * @param array $data
	 * @return
	 */
	public function addTrain($data)
	{
		$trainId = $this->database->table($this->table)->insert($data);
		if ($this->saveHash($trainId))
			return $trainId;
		return FALSE;
	}

	public function editTrain($data, $id)
	{
		return $this->database->table($this->table)->where('id', $id)->update($data);
	}

	public function getTrainById($trainId)
	{
		return $this->database->table($this->table)->where('id', $trainId)->fetch();
	}

	public function getLastTrainByUser($userId)
	{
		return $this->database->table($this->table)->where('user_id', $userId)->order('dateCreated DESC')->limit(1)->fetch();
	}

	public function getTrainsByUser($userId)
	{
		return $this->database->table($this->table)->where('user_id', $userId)->order('dateCreated DESC')->fetchAll();
	}

	public function getTrainByHash($hash)
	{
		return $this->database->table($this->table)->where('hash', $hash)->fetch();
	}

	public function deleteTrain($trainId)
	{
		return $this->database->table($this->table)->where('id', $trainId)->delete();
	}

	/**
	 * Seznam cviku a jejich pocet zpetne za dany pocet dni
	 * @param  int $userId Id uzivatele
	 * @param  string $period pocet dni zpetne
	 * @return array
	 */
	public function getStatisticsPeriod($userId, $period = NULL)
	{
		return $this->database->query("
			SELECT
			e.name,
			IF (i.sets = 0 && i.reps > 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.reps),
			IF (i.sets = 0 && i.reps = 0 && i.ledderFrom > 0 && i.ledderTo > 0, SUM(b.repsOfBlock * IF (i.ledderFrom < i.ledderTo,i.ledderTo * (i.ledderFrom + i.ledderTo) / 2,i.ledderFrom * (i.ledderFrom + i.ledderTo) / 2)),
			IF (i.sets > 0 && i.reps > 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.sets * i.reps),
			IF (i.sets > 0 && i.reps = 0 && i.ledderFrom > 0 && i.ledderTo > 0, SUM(b.repsOfBlock * i.sets *  IF (i.ledderFrom < i.ledderTo,i.ledderTo * (i.ledderFrom + i.ledderTo) / 2,i.ledderFrom * (i.ledderFrom + i.ledderTo) / 2)),
			IF (i.sets = 0 && i.reps = 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.hold), 1
			)
			)
			)
			)
			)
			as setsRepsSum,
			i.exercise_id
			FROM trains t,blocks b, trainitems i, exercises e
			WHERE t.id = b.train_id
			AND b.id = i.block_id
			AND i.exercise_id = e.id
			AND t.user_id = $userId
			AND date_format(t.dateTrain, '%Y-%m-%d') 
			BETWEEN date_format(date_sub(NOW(), INTERVAL $period), '%Y-%m-%d') 
			AND date_format(NOW(), '%Y-%m-%d')
			GROUP BY i.exercise_id
			ORDER BY setsRepsSum DESC
			")->fetchAll();
	}

	/**
	 * Seznam cviku a jejich pocet od pocatku
	 * @param  int $userId Id uzivatele
	 * @return array
	 */
	public function getStatisticsTotal($userId)
	{
		return $this->database->query("
			SELECT
			e.name,
			IF (i.sets = 0 && i.reps > 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.reps),
			IF (i.sets = 0 && i.reps = 0 && i.ledderFrom > 0 && i.ledderTo > 0, SUM(b.repsOfBlock * IF (i.ledderFrom < i.ledderTo,i.ledderTo * (i.ledderFrom + i.ledderTo) / 2,i.ledderFrom * (i.ledderFrom + i.ledderTo) / 2)),
			IF (i.sets > 0 && i.reps > 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.sets * i.reps),
			IF (i.sets > 0 && i.reps = 0 && i.ledderFrom > 0 && i.ledderTo > 0, SUM(b.repsOfBlock * i.sets *  IF (i.ledderFrom < i.ledderTo,i.ledderTo * (i.ledderFrom + i.ledderTo) / 2,i.ledderFrom * (i.ledderFrom + i.ledderTo) / 2)),
			IF (i.sets = 0 && i.reps = 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.hold), 1
			)
			)
			)
			)
			)
			as setsRepsSum,
			i.exercise_id
			FROM trains t,blocks b, trainitems i, exercises e
			WHERE t.id = b.train_id
			AND b.id = i.block_id
			AND i.exercise_id = e.id
			AND t.user_id = $userId
			GROUP BY i.exercise_id
			ORDER BY setsRepsSum DESC
			")->fetchAll();
	}

	/**
	 * Vrati seznam vsech cviku pro dany trenink a soucet opakovanich pro kazdy cvik
	 * @param  int $trainId ID treninku
	 * @return array
	 */
	public function getSumExerciseByTrain($trainId)
	{
		return $this->database->query("
			SELECT
			e.name,
			IF (i.sets = 0 && i.reps > 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.reps),
			IF (i.sets = 0 && i.reps = 0 && i.ledderFrom > 0 && i.ledderTo > 0, SUM(b.repsOfBlock * IF (i.ledderFrom < i.ledderTo,i.ledderTo * (i.ledderFrom + i.ledderTo) / 2,i.ledderFrom * (i.ledderFrom + i.ledderTo) / 2)),
			IF (i.sets > 0 && i.reps > 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.sets * i.reps),
			IF (i.sets > 0 && i.reps = 0 && i.ledderFrom > 0 && i.ledderTo > 0, SUM(b.repsOfBlock * i.sets *  IF (i.ledderFrom < i.ledderTo,i.ledderTo * (i.ledderFrom + i.ledderTo) / 2,i.ledderFrom * (i.ledderFrom + i.ledderTo) / 2)),
			IF (i.sets = 0 && i.reps = 0 && i.ledderFrom = 0 && i.ledderTo = 0, SUM(b.repsOfBlock * i.hold), 1
			)
			)
			)
			)
			)
			as setsRepsSum,
			i.exercise_id
			FROM trains t,blocks b, trainitems i, exercises e
			WHERE t.id = b.train_id
			AND b.id = i.block_id
			AND i.exercise_id = e.id
			AND t.id = $trainId
			GROUP BY i.exercise_id
			ORDER BY setsRepsSum DESC
		")->fetchAll();

	}

	public function saveHash($trainId) {
		return $this->database->table($this->table)->where('id', $trainId)->update(array(
				'hash' => sha1($trainId . date('YmdHis') . 'fWe8X23ksdLoiweJ#qwfe'),
			));
	}

	public function updateShare($trainId)
	{
		return $this->database->table($this->table)->where('id', $trainId)->update(array(
				'isShare' => 1,
			));
	}
	
}