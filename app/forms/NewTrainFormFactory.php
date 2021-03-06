<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Forms\Container;


class NewTrainFormFactory extends Nette\Object
{
	
	private $trainModel;

	private $exerciseModel;

	private $user;

	private $blockModel;

	private $trainItem;

	/**
	 * Form pro pridani noveho treninku
	 * @param
	 * @return Form
	 */
	public function create($exerciseModel, $trainModel, $blockModel, $trainItem, $presenter)
	{
		$this->trainModel = $trainModel;
		$this->exerciseModel = $exerciseModel;
		$this->blockModel = $blockModel;
		$this->trainItem = $trainItem;
		$this->user = $presenter->user;
		
		$form = new Form;
		$form->addText('name', 'Name');
		$form->addText('dateTrain', 'Date of train')
				->setDefaultValue(date('d m Y'));
		$i = 0;
		
		$invalidateCallback = function() use($presenter){
			$presenter->invalidateControl('blocks');
		};

		$userId = $this->user->getId();

		$blocks = $form->addDynamic('blocks', function (Container $block) use ($i, $exerciseModel, $userId, $invalidateCallback) {
				
				$j = 0;
				$exercises = $block->addDynamic('exercises', 
					function (Container $exercise) use ($i, $j, $userId, $exerciseModel, $invalidateCallback) {
					
					$exercisesList = $exerciseModel->getAll($userId)->fetchPairs('id', 'name');
					
					$exercise->addSelect('exercise', 'Exercise', $exercisesList);
					$exercise->addText('sets', 'sets')
						->addCondition(FORM::FILLED)
							->addRule(FORM::NUMERIC, 'Sets has to be a number');

					$exercise->addText('reps', 'reps')
						->addCondition(FORM::FILLED)
							->addRule(FORM::NUMERIC, 'Reps has to be a number');
					$exercise->addText('rest', 'Rest')
						->addCondition(FORM::FILLED)
							->addRule(FORM::NUMERIC, 'Rest has to be a number');
					$exercise->addSelect('unitRest', '', array(
							'min' => 'min',
							's' => 's',
						));
					$exercise->addText('ledderFrom', 'from')
						->addCondition(FORM::FILLED)
							->addRule(FORM::NUMERIC, 'Ledder from has to be a number');
					$exercise->addText('ledderTo', 'To')
						->addCondition(FORM::FILLED)
							->addRule(FORM::NUMERIC, 'Ledder to has to be a number');;
					
					$exercise->addText('moreWeightValue', 'More Weight')
						->addCondition(FORM::FILLED)
							->addRule(FORM::NUMERIC, 'More weight has to be a number');
					
					$exercise->addSelect('unitMoreWeight', '', array(
							'Kg' => 'Kg',
							'Lb' => 'Kg',
						));
					
					$exercise->addText('hold', 'Hold')
						->addCondition(FORM::FILLED)
							->addRule(FORM::NUMERIC, 'Hold has to be a number');
					$exercise->addSelect('unitHold', '', array(
							's' => 's',
							'min' => 'min',
						));
					$exercise->addSubmit('removeExercise', '')
						->addRemoveOnClick($invalidateCallback);
					$j++;
				}, 1, TRUE);	
				
				$exercises->addSubmit('addExercise', 'Add exercise')->setValidationScope(FALSE)
					->addCreateOnClick($invalidateCallback)->setAttribute('class', 'ajax box');
				
				$block->addText('blockRest', 'Rest after block')
					->addCondition(FORM::FILLED)
						->addRule(FORM::NUMERIC, 'Rest after block has to be a number');
				$block->addSelect('unitBlockRest', 'Unit block rest', array(
							'min' => 'min',
							's' => 's',
						));
				$block->addText('repsOfBlock', 'Reps of block')->setValue(1)
					->addCondition(FORM::FILLED)
						->addRule(FORM::NUMERIC, 'Reps of block has to be a number');
				$block->addSubmit('removeBlock', '')
					->addRemoveOnClick($invalidateCallback);
				$i++;

				
    	}, 1, TRUE);
		$blocks->addSubmit('addBlock', 'Add block')->setValidationScope(FALSE)
			->addCreateOnClick($invalidateCallback)->setAttribute('class', 'ajax box');
		
		$form->addSubmit('saveTrain', 'Save train')->setAttribute('class', 'box');

		$form->onSuccess[] = array($this, 'formSucceeded');
		
		return $form;
	}

	public function formSucceeded(Form $form, $values)
	{
		$trainData = array(
				'user_id' => $this->user->getId(),
				'name' => $values->name,
				'dateCreated' => date('Y-m-d H:i:s'),
				'dateTrain' => date('Y-m-d', strtotime(str_replace(' ', '-', $values->dateTrain))),
			);
		$trainId = $this->trainModel->addTrain($trainData);

		foreach($values->blocks as $bkey => $block) {
			
			$blockData = array(
					'train_id' => $trainId,
					'number' => $bkey + 1,
					'blockRest' => $block->blockRest,
					'unitBlockRest' => $block->unitBlockRest,
					'repsOfBlock' => $block->repsOfBlock < 1 ? 1 : $block->repsOfBlock,
				);
			$blockId = $this->blockModel->addBlock($blockData);
			foreach($block->exercises as $ekey => $exercise) {
				$exercisesData = array(
					'block_id' => $blockId,
					'exercise_id' => $exercise->exercise,
					'sets' => $exercise->sets,
					'reps' => $exercise->reps,
					'rest' => $exercise->rest,
					'unitRest' => $exercise->unitRest,
					'ledderFrom' => $exercise->ledderFrom,
					'ledderTo' => $exercise->ledderTo,
					'moreWeightValue' => $exercise->moreWeightValue,
					'unitMoreWeight' => $exercise->unitMoreWeight,
					'hold' => $exercise->hold,
					'unitHold' => $exercise->unitHold,
				);
				$this->trainItem->addItem($exercisesData);
			}
		}
	}

}
