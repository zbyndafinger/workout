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
		$form->addText('dateTrain', 'Date of train');
		$i = 0;
		
		$invalidateCallback = function() use($presenter){
			$presenter->invalidateControl('blocks');
		};

		$blocks = $form->addDynamic('blocks', function (Container $block) use ($i, $exerciseModel, $invalidateCallback) {
				
				$j = 0;
				$exercises = $block->addDynamic('exercises', 
					function (Container $exercise) use ($i, $j, $exerciseModel, $invalidateCallback) {
					
					$exercisesList = $exerciseModel->getAll()->order('name')->fetchPairs('id', 'name');
					
					$exercise->addSelect('exercise', 'Exercise', $exercisesList);
					$exercise->addText('sets', 'sets');
					$exercise->addText('reps', 'reps');
					$exercise->addText('rest', 'Rest');
					$exercise->addSelect('unitRest', '', array(
							'min',
							's',
						));
					$exercise->addText('ledderFrom', 'from');
					$exercise->addText('ledderTo', 'To');
					
					$exercise->addText('moreWeightValue', 'More Weight');
					
					$exercise->addSelect('unitMoreWeight', '', array(
							'Kg',
							'Lb',
						));
					
					$exercise->addText('hold', 'Hold');
					$exercise->addSelect('unitHold', '', array(
							'min',
							's',
						));
					$exercise->addSubmit('removeExercise', '')
						->addRemoveOnClick($invalidateCallback);
					$j++;
				}, 1, TRUE);	
				
				$exercises->addSubmit('addExercise', 'Add exercise')->setValidationScope(FALSE)
					->addCreateOnClick($invalidateCallback)->setAttribute('class', 'ajax box');
				
				$block->addText('blockRest', 'Rest after block');
				$block->addSelect('unitBlockRest', 'Unit block rest', array(
							'min',
							's',
						));
				$block->addText('repsOfBlock', 'Reps of block');
		
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
				'dateTrain' => date('Y-m-d H:i:s', strtotime($values->dateTrain)),
			);

		$trainId = $this->trainModel->addTrain($trainData);

		foreach($values->blocks as $bkey => $block) {
			
			$blockData = array(
					'train_id' => $trainId,
					'number' => $bkey + 1,
					'blockRest' => $block->blockRest,
					'unitBlockRest' => $block->unitBlockRest,
					'repsOfBlock' => $block->repsOfBlock,
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
				);
				$this->trainItem->addItem($exercisesData);
			}
		}
	}

}
